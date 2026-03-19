<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

$pageTitle = 'Контакты — ИМСИТ Мерч';
include 'includes/header.php';
?>

<div class="container my-5">
    <h1 class="fw-bold mb-4"><i class="bi bi-telephone me-2"></i>Контакты</h1>

    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm text-center p-4">
                <i class="bi bi-geo-alt-fill text-primary" style="font-size:2.5rem"></i>
                <h5 class="mt-3">Адрес</h5>
                <p class="text-muted mb-0">г. Краснодар,<br>ул. Зиповская, 5</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm text-center p-4">
                <i class="bi bi-telephone-fill text-primary" style="font-size:2.5rem"></i>
                <h5 class="mt-3">Телефон</h5>
                <p class="text-muted mb-0"><a href="tel:+78612335988" class="text-decoration-none">+7 (861) 233-59-88</a></p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm text-center p-4">
                <i class="bi bi-envelope-fill text-primary" style="font-size:2.5rem"></i>
                <h5 class="mt-3">Email</h5>
                <p class="text-muted mb-0"><a href="mailto:imsit@imsit.ru" class="text-decoration-none">imsit@imsit.ru</a></p>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm text-center p-4">
                <i class="bi bi-globe text-primary" style="font-size:2.5rem"></i>
                <h5 class="mt-3">Сайт</h5>
                <p class="text-muted mb-0"><a href="https://imsit.ru" target="_blank" class="text-decoration-none">imsit.ru</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
