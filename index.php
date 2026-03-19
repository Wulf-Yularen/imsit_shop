<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

$pageTitle = 'ИМСИТ Мерч — Официальный магазин';
$pageDescription = 'Официальный интернет-магазин мерча Академии ИМСИТ. Футболки, худи, кепки, значки и другие аксессуары.';

// популярные товары
$db = getDB();
$stmt = $db->prepare('SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_featured = 1 AND p.is_active = 1 ORDER BY p.created_at DESC LIMIT 8');
$stmt->execute();
$featured = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- hero-секция -->
<section class="hero-section text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">Официальный мерч Академии ИМСИТ</h1>
        <p class="lead mb-4 opacity-75">Носи с гордостью</p>
        <a href="/catalog.php" class="btn btn-imsit btn-lg px-5">
            <i class="bi bi-bag me-2"></i>Перейти в каталог
        </a>
    </div>
</section>

<!-- хиты продаж -->
<section class="container my-5">
    <h2 class="text-center mb-4 fw-bold">Популярные товары</h2>
    <?php if (empty($featured)): ?>
        <p class="text-center text-muted">Скоро здесь появятся хиты!</p>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($featured as $p): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="card card-product h-100 shadow-sm">
                        <?php
                        $img = $p['image_main'] && file_exists(__DIR__ . '/uploads/products/' . $p['image_main'])
                            ? '/uploads/products/' . htmlspecialchars($p['image_main'])
                            : 'https://via.placeholder.com/400x400?text=Нет+фото';
                        ?>
                        <img src="<?= $img ?>" class="card-img-top" alt="<?= htmlspecialchars($p['name']) ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
                            <p class="fw-bold text-primary mt-auto mb-2"><?= number_format($p['price'], 0, '.', ' ') ?> ₽</p>
                            <a href="/product.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm">Подробнее</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- преимущества -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-md-4">
                <i class="bi bi-patch-check-fill text-primary" style="font-size:3rem"></i>
                <h5 class="mt-3">Официальная продукция академии</h5>
                <p class="text-muted">Только лицензированный мерч с символикой ИМСИТ</p>
            </div>
            <div class="col-md-4">
                <i class="bi bi-truck text-primary" style="font-size:3rem"></i>
                <h5 class="mt-3">Быстрая доставка по России</h5>
                <p class="text-muted">Отправим ваш заказ в кратчайшие сроки</p>
            </div>
            <div class="col-md-4">
                <i class="bi bi-credit-card text-primary" style="font-size:3rem"></i>
                <h5 class="mt-3">Удобная оплата</h5>
                <p class="text-muted">Принимаем карты, СБП и наличные при получении</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
