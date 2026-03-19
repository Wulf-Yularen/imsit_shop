<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

requireAdmin();

$db = getDB();
$pageTitle = 'Панель администратора — ИМСИТ Мерч';
$message = '';
$messageType = 'success';

// === обработка POST-запросов ===

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $action = $_POST['action'] ?? '';

    // добавление товара
    if ($action === 'add_product') {
        $name = trim($_POST['name'] ?? '');
        $catId = (int) ($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

        $imageName = null;
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $imageName = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/products/' . $imageName);
            }
        }

        $stmt = $db->prepare('INSERT INTO products (category_id, name, description, price, image_main, is_featured, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())');
        $stmt->execute([$catId, $name, $description, $price, $imageName, $isFeatured]);
        $productId = $db->lastInsertId();

        // создаём размеры с quantity=0
        $sizes = $db->query('SELECT id FROM sizes')->fetchAll();
        $stmt = $db->prepare('INSERT INTO product_sizes (product_id, size_id, quantity) VALUES (?, ?, 0)');
        foreach ($sizes as $s) {
            $stmt->execute([$productId, $s['id']]);
        }
        $message = 'Товар добавлен';
    }

    // редактирование товара
    if ($action === 'edit_product') {
        $pid = (int) ($_POST['product_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $catId = (int) ($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

        // фото — если загружено новое
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $imageName = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/products/' . $imageName);
                $stmt = $db->prepare('UPDATE products SET image_main = ? WHERE id = ?');
                $stmt->execute([$imageName, $pid]);
            }
        }

        $stmt = $db->prepare('UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, is_featured = ? WHERE id = ?');
        $stmt->execute([$catId, $name, $description, $price, $isFeatured, $pid]);

        // обновляем остатки по размерам
        if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
            foreach ($_POST['sizes'] as $sizeId => $qty) {
                $stmt = $db->prepare('UPDATE product_sizes SET quantity = ? WHERE product_id = ? AND size_id = ?');
                $stmt->execute([(int)$qty, $pid, (int)$sizeId]);
            }
        }
        $message = 'Товар обновлён';
    }

    // скрыть/показать товар
    if ($action === 'toggle_active') {
        $pid = (int) ($_POST['product_id'] ?? 0);
        $stmt = $db->prepare('UPDATE products SET is_active = NOT is_active WHERE id = ?');
        $stmt->execute([$pid]);
        $message = 'Статус товара изменён';
    }

    // удалить товар
    if ($action === 'delete_product') {
        $pid = (int) ($_POST['product_id'] ?? 0);
        // проверяем, есть ли в заказах
        $stmt = $db->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
        $stmt->execute([$pid]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Нельзя удалить товар — есть связанные заказы';
            $messageType = 'danger';
        } else {
            $db->prepare('DELETE FROM product_sizes WHERE product_id = ?')->execute([$pid]);
            $db->prepare('DELETE FROM cart WHERE product_id = ?')->execute([$pid]);
            $db->prepare('DELETE FROM products WHERE id = ?')->execute([$pid]);
            $message = 'Товар удалён';
        }
    }

    // изменить статус заказа
    if ($action === 'update_order_status') {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'];
        if (in_array($status, $allowed)) {
            $stmt = $db->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute([$status, $orderId]);
            $message = 'Статус заказа #' . $orderId . ' обновлён';
        }
    }

    // добавить категорию
    if ($action === 'add_category') {
        $catName = trim($_POST['cat_name'] ?? '');
        $catSlug = trim($_POST['cat_slug'] ?? '');
        if ($catName && $catSlug) {
            $stmt = $db->prepare('INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, 0)');
            $stmt->execute([$catName, $catSlug]);
            $message = 'Категория добавлена';
        }
    }

    // удалить категорию
    if ($action === 'delete_category') {
        $catId = (int) ($_POST['category_id'] ?? 0);
        $stmt = $db->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $stmt->execute([$catId]);
        if ($stmt->fetchColumn() > 0) {
            $message = 'Нельзя удалить категорию — есть товары';
            $messageType = 'danger';
        } else {
            $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$catId]);
            $message = 'Категория удалена';
        }
    }

    // удалить пользователя
    if ($action === 'delete_user') {
        $uid = (int) ($_POST['user_id'] ?? 0);
        if ($uid === (int) $_SESSION['user_id']) {
            $message = 'Нельзя удалить самого себя';
            $messageType = 'danger';
        } else {
            // обнуляем user_id в заказах
            $db->prepare('UPDATE orders SET user_id = NULL WHERE user_id = ?')->execute([$uid]);
            $db->prepare('DELETE FROM cart WHERE user_id = ?')->execute([$uid]);
            $db->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
            $message = 'Пользователь удалён';
        }
    }
}

// === данные для страницы ===
$products = $db->query('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC')->fetchAll();
$categories = $db->query('SELECT * FROM categories ORDER BY sort_order, name')->fetchAll();
$allSizes = $db->query('SELECT * FROM sizes ORDER BY id')->fetchAll();
$orders = $db->query('SELECT o.*, u.login FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC')->fetchAll();
$users = $db->query('SELECT * FROM users ORDER BY id')->fetchAll();

$statusLabels = [
    'pending' => ['Ожидает', 'warning'],
    'confirmed' => ['Подтверждён', 'primary'],
    'shipped' => ['Отправлен', 'info'],
    'completed' => ['Выполнен', 'success'],
    'cancelled' => ['Отменён', 'danger'],
];

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid my-4 px-4">
    <h1 class="mb-4 fw-bold"><i class="bi bi-gear-fill me-2"></i>Панель администратора</h1>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- навигация по секциям -->
    <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-products">Товары</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-orders">Заказы</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-users">Пользователи</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-categories">Категории</a></li>
    </ul>

    <div class="tab-content">

    <!-- ===================== ТОВАРЫ ===================== -->
    <div class="tab-pane fade show active" id="tab-products">

        <!-- форма добавления -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><h5 class="mb-0">Добавить товар</h5></div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_product">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Название</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Категория</label>
                            <select name="category_id" class="form-select">
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Цена (₽)</label>
                            <input type="number" name="price" class="form-control" min="0" step="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Фото</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Описание</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12 d-flex align-items-center gap-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_featured" class="form-check-input" id="feat_new">
                                <label class="form-check-label" for="feat_new">Хит продаж</label>
                            </div>
                            <button type="submit" class="btn btn-imsit">Добавить</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- таблица товаров -->
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr><th>Фото</th><th>Название</th><th>Категория</th><th>Цена</th><th>Статус</th><th>Хит</th><th>Действия</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <?php
                                $img = $p['image_main'] && file_exists(__DIR__ . '/../uploads/products/' . $p['image_main'])
                                    ? '/uploads/products/' . htmlspecialchars($p['image_main'])
                                    : 'https://via.placeholder.com/50x50?text=—';
                                ?>
                                <img src="<?= $img ?>" width="50" class="rounded">
                            </td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
                            <td><?= number_format($p['price'], 0, '.', ' ') ?> ₽</td>
                            <td>
                                <?php if ($p['is_active']): ?>
                                    <span class="badge bg-success">Активен</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Скрыт</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $p['is_featured'] ? '<i class="bi bi-star-fill text-warning"></i>' : '' ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id'] ?>"><i class="bi bi-pencil"></i></button>
                                <form method="post" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-sm btn-outline-secondary" title="Скрыть/Показать"><i class="bi bi-eye"></i></button>
                                </form>
                                <form method="post" class="d-inline" onsubmit="return confirm('Удалить товар?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>

                        <!-- модалка редактирования -->
                        <?php
                        $stmtSizes = $db->prepare('SELECT ps.size_id, ps.quantity, s.name AS size_name FROM product_sizes ps JOIN sizes s ON ps.size_id = s.id WHERE ps.product_id = ? ORDER BY s.id');
                        $stmtSizes->execute([$p['id']]);
                        $prodSizes = $stmtSizes->fetchAll();
                        ?>
                        <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form method="post" enctype="multipart/form-data">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="edit_product">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Редактировать: <?= htmlspecialchars($p['name']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Название</label>
                                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Категория</label>
                                                    <select name="category_id" class="form-select">
                                                        <?php foreach ($categories as $c): ?>
                                                            <option value="<?= $c['id'] ?>" <?= $p['category_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Цена (₽)</label>
                                                    <input type="number" name="price" class="form-control" value="<?= $p['price'] ?>" min="0" required>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Описание</label>
                                                    <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Новое фото (необязательно)</label>
                                                    <input type="file" name="image" class="form-control" accept="image/*">
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check mt-4">
                                                        <input type="checkbox" name="is_featured" class="form-check-input" id="feat_<?= $p['id'] ?>" <?= $p['is_featured'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="feat_<?= $p['id'] ?>">Хит продаж</label>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label fw-bold">Остатки по размерам:</label>
                                                    <div class="row g-2">
                                                        <?php foreach ($prodSizes as $ps): ?>
                                                            <div class="col-auto">
                                                                <div class="input-group input-group-sm" style="width:120px">
                                                                    <span class="input-group-text"><?= htmlspecialchars($ps['size_name']) ?></span>
                                                                    <input type="number" name="sizes[<?= $ps['size_id'] ?>]" class="form-control" value="<?= $ps['quantity'] ?>" min="0">
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                            <button type="submit" class="btn btn-imsit">Сохранить</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===================== ЗАКАЗЫ ===================== -->
    <div class="tab-pane fade" id="tab-orders">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr><th>#</th><th>Дата</th><th>Покупатель</th><th>Сумма</th><th>Статус</th><th>Действия</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <?php $s = $statusLabels[$o['status']] ?? ['?', 'secondary']; ?>
                        <tr>
                            <td><?= $o['id'] ?></td>
                            <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
                            <td><?= htmlspecialchars($o['login'] ?? $o['full_name']) ?></td>
                            <td><?= number_format($o['total'], 0, '.', ' ') ?> ₽</td>
                            <td><span class="badge bg-<?= $s[1] ?>"><?= $s[0] ?></span></td>
                            <td>
                                <form method="post" class="d-inline-flex gap-1">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="update_order_status">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" class="form-select form-select-sm" style="width:auto">
                                        <?php foreach ($statusLabels as $key => $label): ?>
                                            <option value="<?= $key ?>" <?= $o['status'] === $key ? 'selected' : '' ?>><?= $label[0] ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Сохранить</button>
                                </form>
                                <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#orderModal<?= $o['id'] ?>"><i class="bi bi-eye"></i> Детали</button>
                            </td>
                        </tr>

                        <!-- модалка деталей заказа -->
                        <?php
                        $stmtItems = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
                        $stmtItems->execute([$o['id']]);
                        $orderItems = $stmtItems->fetchAll();
                        ?>
                        <div class="modal fade" id="orderModal<?= $o['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Заказ #<?= $o['id'] ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>ФИО:</strong> <?= htmlspecialchars($o['full_name']) ?></p>
                                        <p><strong>Телефон:</strong> <?= htmlspecialchars($o['phone']) ?></p>
                                        <p><strong>Email:</strong> <?= htmlspecialchars($o['email']) ?></p>
                                        <p><strong>Адрес:</strong> <?= htmlspecialchars($o['address'] ?? '—') ?></p>
                                        <p><strong>Комментарий:</strong> <?= htmlspecialchars($o['comment'] ?? '—') ?></p>
                                        <hr>
                                        <table class="table table-sm">
                                            <thead><tr><th>Товар</th><th>Размер</th><th>Цена</th><th>Кол-во</th><th>Сумма</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($orderItems as $oi): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($oi['product_name']) ?></td>
                                                        <td><?= htmlspecialchars($oi['size_name']) ?></td>
                                                        <td><?= number_format($oi['price'], 0, '.', ' ') ?> ₽</td>
                                                        <td><?= $oi['quantity'] ?></td>
                                                        <td><?= number_format($oi['price'] * $oi['quantity'], 0, '.', ' ') ?> ₽</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot><tr><td colspan="4" class="text-end fw-bold">Итого:</td><td class="fw-bold"><?= number_format($o['total'], 0, '.', ' ') ?> ₽</td></tr></tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===================== ПОЛЬЗОВАТЕЛИ ===================== -->
    <div class="tab-pane fade" id="tab-users">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th>Дата регистрации</th><th>Действия</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['login']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge bg-danger">Администратор</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Пользователь</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Удалить пользователя?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Удалить</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Вы</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ===================== КАТЕГОРИИ ===================== -->
    <div class="tab-pane fade" id="tab-categories">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white"><h5 class="mb-0">Добавить категорию</h5></div>
            <div class="card-body">
                <form method="post" class="row g-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_category">
                    <div class="col-md-5">
                        <input type="text" name="cat_name" class="form-control" placeholder="Название" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="cat_slug" class="form-control" placeholder="Slug (латиницей)" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-imsit w-100">Добавить</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr><th>ID</th><th>Название</th><th>Slug</th><th>Действия</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td><?= htmlspecialchars($c['name']) ?></td>
                            <td><code><?= htmlspecialchars($c['slug']) ?></code></td>
                            <td>
                                <form method="post" class="d-inline" onsubmit="return confirm('Удалить категорию?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?= $c['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Удалить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    </div><!-- /tab-content -->
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
