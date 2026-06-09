<?php
// шаблон шапки сайта
$cartCount = isLoggedIn() ? getCartCount() : 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'ИМСИТ Мерч') ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription ?? 'Официальный интернет-магазин мерча Академии ИМСИТ') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --imsit-blue: #0d6efd;
            --imsit-dark: #0a1628;
            --imsit-gradient: linear-gradient(135deg, #0a1628 0%, #1a3a6e 100%);
            --imsit-gold: linear-gradient(135deg, #f0c27f 0%, #4b6cb7 100%);
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Inter', 'Segoe UI', Roboto, sans-serif;
        }
        main { flex: 1; }
        .navbar { background: var(--imsit-gradient) !important; }
        .navbar-brand {
            font-weight: 800;
            letter-spacing: 1.5px;
            font-size: 1.35rem;
            display: flex;
            align-items: center;
            gap: 0;
        }
        .brand-accent {
            background: var(--imsit-gold);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .brand-sep {
            color: rgba(255,255,255,.3);
            margin: 0 6px;
            font-weight: 300;
        }
        .brand-sub {
            font-weight: 400;
            letter-spacing: .5px;
            opacity: .85;
            text-transform: lowercase;
        }
        .card-product {
            transition: box-shadow .25s, transform .25s;
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }
        .card-product:hover {
            box-shadow: 0 8px 30px rgba(13,110,253,.18);
            transform: translateY(-4px);
        }
        .card-product img {
            height: 240px;
            object-fit: cover;
        }
        .hero-section {
            background: var(--imsit-gradient);
            color: #fff;
            padding: 80px 0;
        }
        .badge-cart {
            position: absolute;
            top: -4px;
            right: -8px;
            font-size: .65rem;
        }
        footer {
            background: var(--imsit-dark);
            color: #adb5bd;
        }
        .btn-imsit {
            background: var(--imsit-blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 28px;
        }
        .btn-imsit:hover { background: #0b5ed7; color: #fff; }
        .badge-out-of-stock {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 2;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/index.php">
            <i class="bi bi-mortarboard-fill me-2"></i><span class="brand-accent">ИМСИТ</span><span class="brand-sep">·</span><span class="brand-sub">Мерч</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/catalog.php">Каталог</a></li>
                <li class="nav-item"><a class="nav-link" href="/about.php">О нас</a></li>
                <li class="nav-item"><a class="nav-link" href="/contacts.php">Контакты</a></li>
                <?php if (isAdmin()): ?>
                    <li class="nav-item"><a class="nav-link text-warning" href="/admin/index.php"><i class="bi bi-gear-fill"></i> Панель администратора</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="/cart.php">
                            <i class="bi bi-cart3"></i> Корзина
                            <?php if ($cartCount > 0): ?>
                                <span class="badge bg-danger badge-cart"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/profile.php"><i class="bi bi-person-circle"></i> Кабинет</a></li>
                    <li class="nav-item"><a class="nav-link" href="/logout.php">Выйти</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login.php">Войти</a></li>
                    <li class="nav-item"><a class="nav-link" href="/register.php">Регистрация</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main>
