<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$pageTitle = 'Корзина — ИМСИТ Мерч';

// удаление позиции
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    verifyCsrfToken();
    $cartId = (int) ($_POST['cart_id'] ?? 0);
    $stmt = $db->prepare('DELETE FROM cart WHERE id = ? AND user_id = ?');
    $stmt->execute([$cartId, $userId]);
    header('Location: /cart.php');
    exit;
}

// получаем корзину
$stmt = $db->prepare('
    SELECT c.id AS cart_id, c.quantity, p.id AS product_id, p.name, p.price, p.image_main, s.name AS size_name
    FROM cart c
    JOIN products p ON c.product_id = p.id
    JOIN sizes s ON c.size_id = s.id
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
');
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $i) {
    $total += $i['price'] * $i['quantity'];
}

include 'includes/header.php';
?>

<div class="container my-5">
    <h1 class="mb-4 fw-bold"><i class="bi bi-cart3 me-2"></i>Корзина</h1>

    <?php if (empty($items)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x" style="font-size:4rem;color:#ccc"></i>
            <p class="mt-3 text-muted fs-5">Корзина пуста</p>
            <a href="/catalog.php" class="btn btn-imsit">Перейти в каталог</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Фото</th>
                        <th>Товар</th>
                        <th>Размер</th>
                        <th>Цена</th>
                        <th>Кол-во</th>
                        <th>Сумма</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $i): ?>
                        <tr>
                            <td>
                                <?php
                                $img = $i['image_main'] && file_exists(__DIR__ . '/uploads/products/' . $i['image_main'])
                                    ? '/uploads/products/' . htmlspecialchars($i['image_main'])
                                    : 'https://via.placeholder.com/80x80?text=Фото';
                                ?>
                                <img src="<?= $img ?>" alt="" width="60" class="rounded">
                            </td>
                            <td><a href="/product.php?id=<?= $i['product_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($i['name']) ?></a></td>
                            <td><?= htmlspecialchars($i['size_name']) ?></td>
                            <td><?= number_format($i['price'], 0, '.', ' ') ?> ₽</td>
                            <td><?= $i['quantity'] ?></td>
                            <td class="fw-bold"><?= number_format($i['price'] * $i['quantity'], 0, '.', ' ') ?> ₽</td>
                            <td>
                                <form method="post" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_id" value="<?= $i['cart_id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Удалить"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end fw-bold fs-5">Итого:</td>
                        <td class="fw-bold fs-5 text-primary"><?= number_format($total, 0, '.', ' ') ?> ₽</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="d-flex justify-content-between mt-3">
            <a href="/catalog.php" class="btn btn-outline-secondary">Продолжить покупки</a>
            <a href="/checkout.php" class="btn btn-imsit btn-lg">Оформить заказ</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
