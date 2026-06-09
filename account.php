<?php
require_once __DIR__ . '/includes/functions.php';
if (!isLoggedIn()) redirect('login.php');

$db   = getDB();
$user = currentUser();
$tab  = $_GET['tab'] ?? 'orders';
$msg  = '';

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'profile') {
    $name    = trim($_POST['name'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $db->prepare("UPDATE users SET name=?, phone=?, address=? WHERE id=?")->execute([$name, $phone, $address, $user['id']]);
    $_SESSION['user_name'] = $name;
    $user = currentUser();
    $msg = 'Профиль обновлён';
}

// Orders
$orders = $db->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$orders->execute([$user['id']]);
$orders = $orders->fetchAll();

$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Личный кабинет — ИМСИТ Shop</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;600;700;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="public/css/style.css">
</head>
<body>

<header class="header">
  <div class="header-inner container">
    <a href="index.php" class="logo"><span class="logo-icon">✦</span><div class="logo-text"><span class="logo-main">ИМСИТ</span><span class="logo-sub">Shop</span></div></a>
    <nav class="nav">
      <a href="index.php" class="nav-link">Главная</a>
      <a href="catalog.php" class="nav-link">Каталог</a>
    </nav>
    <div class="header-actions">
      <a href="cart.php" class="btn-icon cart-btn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <?php if($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
      </a>
      <a href="logout.php" class="btn-outline">Выйти</a>
    </div>
  </div>
</header>

<div class="page-header">
  <div class="container">
    <h1>Личный кабинет</h1>
    <p>Здравствуйте, <?= e($user['name']) ?>!</p>
  </div>
</div>

<div class="container">
  <div class="account-layout">
    <!-- Sidebar nav -->
    <nav class="account-nav">
      <div class="account-nav-card">
        <div class="account-nav-user">
          <strong><?= e($user['name']) ?></strong>
          <span><?= e($user['email']) ?></span>
        </div>
        <div class="account-nav-links">
          <a href="?tab=orders" class="account-nav-link <?= $tab==='orders'?'active':'' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Мои заказы
          </a>
          <a href="?tab=profile" class="account-nav-link <?= $tab==='profile'?'active':'' ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Профиль
          </a>
          <?php if(isAdmin()): ?>
          <a href="admin/index.php" class="account-nav-link" style="color:var(--accent)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            Админ-панель
          </a>
          <?php endif; ?>
          <a href="logout.php" class="account-nav-link" style="color:var(--accent)">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Выйти
          </a>
        </div>
      </div>
    </nav>

    <!-- Content -->
    <main style="padding:0">
      <?php if($msg): ?>
      <div class="alert alert-success" style="margin-bottom:20px"><?= e($msg) ?></div>
      <?php endif; ?>

      <?php if($tab === 'orders'): ?>
      <div style="background:var(--white);border-radius:var(--radius);border:1.5px solid var(--border);padding:28px">
        <h2 style="font-family:'Playfair Display',serif;font-size:24px;margin-bottom:24px">История заказов</h2>
        <?php if(empty($orders)): ?>
        <div style="text-align:center;padding:48px 0">
          <div style="font-size:48px;margin-bottom:12px">📦</div>
          <p style="color:var(--ink-muted);font-size:16px">У вас пока нет заказов</p>
          <a href="catalog.php" class="btn-primary" style="margin-top:16px;display:inline-flex">Перейти в каталог</a>
        </div>
        <?php else: ?>
        <table class="data-table">
          <thead>
            <tr><th>№</th><th>Дата</th><th>Сумма</th><th>Статус</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach($orders as $o): ?>
            <tr>
              <td><strong>#<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></strong></td>
              <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
              <td style="font-family:'Playfair Display',serif;font-weight:700"><?= formatPrice($o['total']) ?></td>
              <td>
                <?php
                $labels = ['pending'=>'Ожидает','processing'=>'Обработка','shipped'=>'Доставляется','delivered'=>'Доставлен','cancelled'=>'Отменён'];
                ?>
                <span class="badge badge-<?= $o['status'] ?>"><?= $labels[$o['status']] ?? $o['status'] ?></span>
              </td>
              <td><a href="order.php?id=<?= $o['id'] ?>" style="font-weight:700;font-size:13px;color:var(--accent)">Детали →</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

      <?php elseif($tab === 'profile'): ?>
      <div style="background:var(--white);border-radius:var(--radius);border:1.5px solid var(--border);padding:32px;max-width:520px">
        <h2 style="font-family:'Playfair Display',serif;font-size:24px;margin-bottom:24px">Мои данные</h2>
        <form method="POST" action="?tab=profile">
          <div class="form-group"><label>Имя</label><input type="text" name="name" value="<?= e($user['name']) ?>" required></div>
          <div class="form-group"><label>Email</label><input type="email" value="<?= e($user['email']) ?>" disabled style="opacity:.6"></div>
          <div class="form-group"><label>Телефон</label><input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>"></div>
          <div class="form-group"><label>Адрес доставки</label><input type="text" name="address" value="<?= e($user['address'] ?? '') ?>" placeholder="Город, улица, дом"></div>
          <button type="submit" class="btn-primary">Сохранить изменения</button>
        </form>
      </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<div class="toast" id="toast"></div>
<script src="public/js/main.js"></script>
</body>
</html>
