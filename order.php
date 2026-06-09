<?php
require_once __DIR__ . '/includes/functions.php';
if (!isLoggedIn()) redirect('login.php');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch();
if (!$order) redirect('account.php');

$items = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

$statusLabels = ['pending'=>'Ожидает обработки','processing'=>'В обработке','shipped'=>'Доставляется','delivered'=>'Доставлен','cancelled'=>'Отменён'];
$cartCount = getCartCount();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Заказ #<?= str_pad($id,5,'0',STR_PAD_LEFT) ?> — ИМСИТ Shop</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;600;700;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="public/css/style.css">
</head>
<body>

<header class="header">
  <div class="header-inner container">
    <a href="index.php" class="logo"><span class="logo-icon">✦</span><div class="logo-text"><span class="logo-main">ИМСИТ</span><span class="logo-sub">Shop</span></div></a>
     <nav class="nav">
      <a href="index.php" class="nav-link active">Главная</a>
      <a href="catalog.php" class="nav-link">Каталог</a>
      <a href="about.php" class="nav-link">О магазине</a>
    </nav>
    <div class="header-actions">
      <a href="cart.php" class="btn-icon cart-btn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <?php if($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
      </a>
      <a href="account.php" class="btn-outline">Кабинет</a>
    </div>
  </div>
</header>

<div class="page-header">
  <div class="container">
    <h1>Заказ #<?= str_pad($id,5,'0',STR_PAD_LEFT) ?></h1>
    <p>Оформлен <?= date('d.m.Y в H:i', strtotime($order['created_at'])) ?></p>
  </div>
</div>

<div class="container" style="padding:48px 24px">
  <a href="account.php" class="btn-outline" style="margin-bottom:28px;display:inline-flex">← Мои заказы</a>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:24px">
    <div>
      <!-- Status tracker -->
      <div style="background:var(--white);border-radius:var(--radius);border:1.5px solid var(--border);padding:28px;margin-bottom:20px">
        <h3 style="font-family:'Playfair Display',serif;font-size:20px;margin-bottom:20px">Статус заказа</h3>
        <?php
        $steps = ['pending','processing','shipped','delivered'];
        $currentIdx = array_search($order['status'], $steps);
        if ($order['status'] === 'cancelled') $currentIdx = -1;
        ?>
        <?php if($order['status'] === 'cancelled'): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:16px;background:#fde8e8;border-radius:12px;color:#c0392b">
          <span style="font-size:24px">✗</span>
          <strong>Заказ отменён</strong>
        </div>
        <?php else: ?>
        <div style="display:flex;gap:0;position:relative">
          <?php foreach($steps as $i => $step): ?>
          <div style="flex:1;text-align:center;position:relative">
            <div style="width:36px;height:36px;border-radius:50%;background:<?= $i<=$currentIdx?'var(--ink)':'var(--border)' ?>;color:<?= $i<=$currentIdx?'var(--white)':'var(--ink-muted)' ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;margin:0 auto 8px">
              <?= $i < $currentIdx ? '✓' : ($i+1) ?>
            </div>
            <div style="font-size:12px;font-weight:700;color:<?= $i<=$currentIdx?'var(--ink)':'var(--ink-muted)' ?>">
              <?= ['Принят','Обработка','Доставляется','Доставлен'][$i] ?>
            </div>
            <?php if($i < count($steps)-1): ?>
            <div style="position:absolute;top:18px;left:50%;width:100%;height:2px;background:<?= $i<$currentIdx?'var(--ink)':'var(--border)' ?>;z-index:0"></div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Items -->
      <div style="background:var(--white);border-radius:var(--radius);border:1.5px solid var(--border);padding:28px">
        <h3 style="font-family:'Playfair Display',serif;font-size:20px;margin-bottom:20px">Товары в заказе</h3>
        <?php foreach($items as $item): ?>
        <div style="display:flex;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border)">
          <div>
            <strong><?= e($item['product_name']) ?></strong>
            <div style="font-size:13px;color:var(--ink-muted)"><?= $item['quantity'] ?> шт. × <?= formatPrice($item['price']) ?></div>
          </div>
          <strong style="font-family:'Playfair Display',serif;font-size:18px"><?= formatPrice($item['price'] * $item['quantity']) ?></strong>
        </div>
        <?php endforeach; ?>
        <div style="display:flex;justify-content:space-between;padding-top:16px;font-family:'Playfair Display',serif;font-size:24px;font-weight:700">
          <span>Итого</span><span><?= formatPrice($order['total']) ?></span>
        </div>
      </div>
    </div>

    <!-- Info -->
    <div>
      <div style="background:var(--white);border-radius:var(--radius);border:1.5px solid var(--border);padding:24px">
        <h3 style="font-family:'Playfair Display',serif;font-size:18px;margin-bottom:16px">Детали доставки</h3>
        <div style="display:flex;flex-direction:column;gap:12px">
          <div><div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-muted)">Получатель</div><strong><?= e($order['full_name']) ?></strong></div>
          <div><div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-muted)">Телефон</div><?= e($order['phone']) ?></div>
          <div><div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-muted)">Email</div><?= e($order['email']) ?></div>
          <div><div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-muted)">Адрес</div><?= e($order['address']) ?></div>
          <div><div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-muted)">Оплата</div><?= $order['payment_method']==='card'?'💳 Карта':'💵 Наличные' ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="public/js/main.js"></script>
</body>
</html>
