<?php
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ADMIN_SECRET_CODE', '2026');
// ── Аутентификация ──────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, phone, address, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function login(string $email, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        mergeGuestCart($user['id']);
        return true;
    }
    return false;
}

function logout(): void {
    session_destroy();
    header('Location: /imsit_shop/index.php');
    exit;
}

function register(string $name, string $email, string $password, string $phone = '', string $adminCode = ''): bool|string {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) return 'Email уже зарегистрирован';

    $role = ($adminCode !== '' && $adminCode === ADMIN_SECRET_CODE) ? 'admin' : 'user';

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $hash, $phone, $role]);
    return true;
}

// ── Корзина ─────────────────────────────────────────────────────
function getCartKey(): array {
    if (isLoggedIn()) {
        return ['user_id = ?', [$_SESSION['user_id']]];
    }
    if (empty($_SESSION['cart_session'])) {
        $_SESSION['cart_session'] = bin2hex(random_bytes(16));
    }
    return ['session_id = ?', [$_SESSION['cart_session']]];
}

function getCart(): array {
    $db = getDB();
    [$where, $params] = getCartKey();
    $stmt = $db->prepare("
        SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.image, p.stock
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.$where
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getCartCount(): int {
    $db = getDB();
    [$where, $params] = getCartKey();
    $stmt = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE $where");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function getCartTotal(): float {
    $items = getCart();
    return array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
}

function addToCart(int $productId, int $qty = 1): void {
    $db = getDB();
    [$where, $params] = getCartKey();
    $stmt = $db->prepare("SELECT id, quantity FROM cart WHERE $where AND product_id = ?");
    $stmt->execute([...$params, $productId]);
    $existing = $stmt->fetch();
    if ($existing) {
        $db->prepare("UPDATE cart SET quantity = quantity + ? WHERE id = ?")->execute([$qty, $existing['id']]);
    } else {
        if (isLoggedIn()) {
            $db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)")->execute([$_SESSION['user_id'], $productId, $qty]);
        } else {
            $db->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?,?,?)")->execute([$_SESSION['cart_session'], $productId, $qty]);
        }
    }
}

function updateCartItem(int $cartId, int $qty): void {
    $db = getDB();
    if ($qty <= 0) {
        $db->prepare("DELETE FROM cart WHERE id = ?")->execute([$cartId]);
    } else {
        $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?")->execute([$qty, $cartId]);
    }
}

function removeFromCart(int $cartId): void {
    getDB()->prepare("DELETE FROM cart WHERE id = ?")->execute([$cartId]);
}

function clearCart(): void {
    $db = getDB();
    [$where, $params] = getCartKey();
    $db->prepare("DELETE FROM cart WHERE $where")->execute($params);
}

function mergeGuestCart(int $userId): void {
    if (empty($_SESSION['cart_session'])) return;
    $db = getDB();
    $sessId = $_SESSION['cart_session'];
    $items = $db->prepare("SELECT product_id, quantity FROM cart WHERE session_id = ?");
    $items->execute([$sessId]);
    foreach ($items->fetchAll() as $item) {
        addToCart($item['product_id'], $item['quantity']);
    }
    $db->prepare("DELETE FROM cart WHERE session_id = ?")->execute([$sessId]);
    unset($_SESSION['cart_session']);
}

// ── Хелперы ─────────────────────────────────────────────────────
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header("Location: $url"); exit; }
function formatPrice(float $p): string { return number_format($p, 2, '.', ' ') . ' ₽'; }