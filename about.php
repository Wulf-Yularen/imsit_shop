<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

$pageTitle = 'Об академии — ИМСИТ Мерч';
include 'includes/header.php';
?>

<div class="container my-5">
    <h1 class="fw-bold mb-4"><i class="bi bi-building me-2"></i>Об Академии ИМСИТ</h1>

    <div class="row g-5">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <p class="lead">
                        <strong>Академия маркетинга и социально-информационных технологий — ИМСИТ</strong> — один из ведущих
                        негосударственных вузов Юга России, основанный в <strong>1994 году</strong>.
                    </p>
                    <p>
                        Академия расположена в городе <strong>Краснодаре</strong>, по адресу <strong>ул. Зиповская, 5</strong>.
                        За годы работы вуз подготовил более <strong>20 000 специалистов</strong> в самых разных областях.
                    </p>
                    <h5 class="mt-4 mb-3">Направления подготовки</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-code-slash text-primary fs-4 me-3"></i>
                                <span>Программирование</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-robot text-primary fs-4 me-3"></i>
                                <span>Искусственный интеллект</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-shield-lock text-primary fs-4 me-3"></i>
                                <span>Кибербезопасность</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-graph-up-arrow text-primary fs-4 me-3"></i>
                                <span>Маркетинг</span>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <p class="mb-0">
                        <i class="bi bi-award text-warning me-2"></i>
                        Академия ИМСИТ — <strong>лауреат конкурса Министерства образования и науки РФ</strong>.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-primary text-white shadow">
                <div class="card-body text-center p-4">
                    <i class="bi bi-mortarboard-fill" style="font-size:4rem"></i>
                    <h3 class="mt-3">ИМСИТ</h3>
                    <p class="opacity-75">С 1994 года</p>
                    <hr class="border-white opacity-25">
                    <h2 class="mb-0">20 000+</h2>
                    <p class="opacity-75">выпускников</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
