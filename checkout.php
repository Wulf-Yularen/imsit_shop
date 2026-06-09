<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$pageTitle = 'Оформление заказа — ИМСИТ Мерч';

// корзина
$stmt = $db->prepare('
    SELECT c.quantity, c.size_id, p.id AS product_id, p.name, p.price, p.image_main, s.name AS size_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN sizes s ON c.size_id = s.id
    WHERE c.user_id = ?
');
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

// если корзина пуста — на страницу корзины
if (empty($items)) {
    header('Location: /cart.php');
    exit;
}

$total = 0;
foreach ($items as $i) {
    $total += $i['price'] * $i['quantity'];
}

// данные пользователя для предзаполнения
$stmt = $db->prepare('SELECT full_name, phone, email FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$errors = [];
$success = false;

// обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $comment = trim($_POST['comment'] ?? '');

    if ($fullName === '') $errors[] = 'Укажите ФИО';
    if ($phone === '') {
        $errors[] = 'Укажите телефон';
    } elseif (!preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $phone)) {
        $errors[] = 'Некорректный формат телефона';
    }
    if ($email === '') {
        $errors[] = 'Укажите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный формат email';
    }

    if (empty($errors)) {
        $db->beginTransaction();
        try {
            // проверяем остатки и списываем
            $stmtCheck = $db->prepare('SELECT ps.quantity FROM product_sizes ps WHERE ps.product_id = ? AND ps.size_id = ? FOR UPDATE');
            $stmtDeduct = $db->prepare('UPDATE product_sizes SET quantity = quantity - ? WHERE product_id = ? AND size_id = ?');

            foreach ($items as $i) {
                $stmtCheck->execute([$i['product_id'], $i['size_id']]);
                $stock = (int) $stmtCheck->fetchColumn();

                if ($stock < $i['quantity']) {
                    $errors[] = 'Недостаточно товара «' . $i['name'] . '» (размер ' . $i['size_name'] . '): на складе ' . $stock . ' шт., в корзине ' . $i['quantity'] . ' шт.';
                }
            }

            if (!empty($errors)) {
                $db->rollBack();
            } else {
                // списываем остатки
                foreach ($items as $i) {
                    $stmtDeduct->execute([$i['quantity'], $i['product_id'], $i['size_id']]);
                }

                // пересчитываем total из базы (безопасно)
                $stmt = $db->prepare('SELECT SUM(p.price * c.quantity) FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?');
                $stmt->execute([$userId]);
                $total = (float) $stmt->fetchColumn();

                // создаём заказ
                $stmt = $db->prepare('INSERT INTO orders (user_id, full_name, phone, email, address, comment, total, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$userId, $fullName, $phone, $email, $address, $comment, $total, 'pending']);
                $orderId = $db->lastInsertId();

                // позиции заказа
                $stmt = $db->prepare('INSERT INTO order_items (order_id, product_id, size_id, product_name, size_name, price, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)');
                foreach ($items as $i) {
                    $stmt->execute([$orderId, $i['product_id'], $i['size_id'], $i['name'], $i['size_name'], $i['price'], $i['quantity']]);
                }

                // очищаем корзину
                $del = $db->prepare('DELETE FROM cart WHERE user_id = ?');
                $del->execute([$userId]);

                $db->commit();

                // редирект в профиль
                $_SESSION['order_success'] = 'Заказ #' . $orderId . ' успешно оформлен!';
                header('Location: /profile.php');
                exit;
            }
        } catch (\Exception $e) {
            $db->rollBack();
            $errors[] = 'Ошибка при оформлении заказа. Попробуйте ещё раз.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <h1 class="mb-4 fw-bold">Оформление заказа</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <div class="row g-5">
        <!-- форма -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Данные получателя</h5>
                    <form method="post">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label">ФИО <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($_POST['full_name'] ?? $user['full_name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Телефон <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? $user['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Адрес доставки</label>
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Комментарий</label>
                            <textarea name="comment" class="form-control" rows="2"><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-imsit btn-lg w-100"><i class="bi bi-check-circle me-2"></i>Подтвердить заказ</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- сводка -->
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Ваш заказ</h5>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($items as $i): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <div>
                                    <?= htmlspecialchars($i['name']) ?> <small class="text-muted">(<?= htmlspecialchars($i['size_name']) ?>) × <?= $i['quantity'] ?></small>
                                </div>
                                <span class="fw-bold"><?= number_format($i['price'] * $i['quantity'], 0, '.', ' ') ?> ₽</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="d-flex justify-content-between fs-5 fw-bold">
                        <span>Итого:</span>
                        <span class="text-primary"><?= number_format($total, 0, '.', ' ') ?> ₽</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
