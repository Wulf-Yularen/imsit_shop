<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

$db = getDB();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// получаем товар
$stmt = $db->prepare('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

// если не найден или скрыт — 404
if (!$product || !$product['is_active']) {
    http_response_code(404);
    $pageTitle = 'Товар не найден';
    include 'includes/header.php';
    echo '<div class="container my-5 text-center"><h1>404</h1><p>Товар не найден.</p><a href="/catalog.php" class="btn btn-imsit">В каталог</a></div>';
    include 'includes/footer.php';
    exit;
}

// размеры
$stmt = $db->prepare('SELECT ps.*, s.name AS size_name FROM product_sizes ps JOIN sizes s ON ps.size_id = s.id WHERE ps.product_id = ? ORDER BY s.id');
$stmt->execute([$id]);
$sizes = $stmt->fetchAll();

// добавление в корзину
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    verifyCsrfToken();
    $sizeId = (int) ($_POST['size_id'] ?? 0);

    if ($sizeId <= 0) {
        $message = 'Выберите размер.';
    } else {
        // проверяем, есть ли уже в корзине
        $stmt = $db->prepare('SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND size_id = ?');
        $stmt->execute([$_SESSION['user_id'], $id, $sizeId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $db->prepare('UPDATE cart SET quantity = quantity + 1 WHERE id = ?');
            $stmt->execute([$existing['id']]);
        } else {
            $stmt = $db->prepare('INSERT INTO cart (user_id, product_id, size_id, quantity, added_at) VALUES (?, ?, ?, 1, NOW())');
            $stmt->execute([$_SESSION['user_id'], $id, $sizeId]);
        }
        $message = 'Товар добавлен в корзину!';
    }
}

$pageTitle = htmlspecialchars($product['name']) . ' — ИМСИТ Мерч';
include 'includes/header.php';
?>

<div class="container my-5">
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/catalog.php">Каталог</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- фото -->
        <div class="col-md-6">
            <?php
            $img = $product['image_main'] && file_exists(__DIR__ . '/uploads/products/' . $product['image_main'])
                ? '/uploads/products/' . htmlspecialchars($product['image_main'])
                : 'https://via.placeholder.com/600x600?text=Нет+фото';
            ?>
            <img src="<?= $img ?>" class="img-fluid rounded-3 shadow" alt="<?= htmlspecialchars($product['name']) ?>">
        </div>

        <!-- информация -->
        <div class="col-md-6">
            <h1 class="fw-bold mb-2"><?= htmlspecialchars($product['name']) ?></h1>
            <span class="badge bg-secondary mb-3"><?= htmlspecialchars($product['category_name'] ?? '') ?></span>
            <p class="display-6 fw-bold text-primary mb-3"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</p>
            <p class="text-muted mb-4"><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>

            <?php if (isLoggedIn()): ?>
                <form method="post">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Размер:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($sizes as $s): ?>
                                <?php if ($s['quantity'] > 0): ?>
                                    <input type="radio" class="btn-check" name="size_id" id="size_<?= $s['size_id'] ?>" value="<?= $s['size_id'] ?>" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="size_<?= $s['size_id'] ?>"><?= htmlspecialchars($s['size_name']) ?></label>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary" disabled><?= htmlspecialchars($s['size_name']) ?></button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-imsit btn-lg"><i class="bi bi-cart-plus me-2"></i>Добавить в корзину</button>
                </form>
            <?php else: ?>
                <a href="/login.php?redirect=<?= urlencode('/product.php?id=' . $id) ?>" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Войдите чтобы купить
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
