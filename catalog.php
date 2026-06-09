<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

$pageTitle = 'Каталог — ИМСИТ Мерч';
$db = getDB();

// фильтры
$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$search = trim($_GET['search'] ?? '');

// категории для фильтра
$cats = $db->query('SELECT * FROM categories ORDER BY sort_order, name')->fetchAll();

// товары
$sql = 'SELECT p.*, c.name AS category_name, COALESCE((SELECT SUM(ps.quantity) FROM product_sizes ps WHERE ps.product_id = p.id), 0) AS total_stock FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1';
$params = [];

if ($categoryId > 0) {
    $sql .= ' AND p.category_id = ?';
    $params[] = $categoryId;
}
if ($search !== '') {
    $sql .= ' AND p.name LIKE ?';
    $params[] = '%' . $search . '%';
}
$sql .= ' ORDER BY p.created_at DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container my-5">
    <h1 class="mb-4 fw-bold">Каталог товаров</h1>

    <!-- фильтры -->
    <form method="get" class="row g-3 mb-4 p-3 bg-light rounded-3">
        <div class="col-md-4">
            <select name="category" class="form-select">
                <option value="0">Все категории</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $categoryId === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Поиск по названию..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-imsit w-100"><i class="bi bi-search me-1"></i> Применить</button>
        </div>
    </form>

    <!-- товары -->
    <?php if (empty($products)): ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-search me-2"></i>Товары не найдены
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($products as $p): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card card-product h-100 shadow-sm position-relative">
                        <?php if ((int)$p['total_stock'] <= 0): ?>
                            <span class="badge bg-danger badge-out-of-stock">Нет в наличии</span>
                        <?php endif; ?>
                        <?php
                        $img = $p['image_main'] && file_exists(__DIR__ . '/uploads/products/' . $p['image_main'])
                            ? '/uploads/products/' . htmlspecialchars($p['image_main'])
                            : 'https://via.placeholder.com/400x400?text=Нет+фото';
                        ?>
                        <img src="<?= $img ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>">
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-secondary mb-2 align-self-start"><?= htmlspecialchars($p['category_name'] ?? 'Без категории') ?></span>
                            <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
                            <p class="fw-bold text-primary mt-auto mb-2"><?= number_format($p['price'], 0, '.', ' ') ?> ₽</p>
                            <a href="/product.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm">Подробнее</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
