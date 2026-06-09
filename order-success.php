<?php
require_once __DIR__ . '/includes/functions.php';
$orderId = (int)($_GET['id'] ?? 0);
$db = getDB();
$order = null;
if ($orderId) {
    $order = $db->prepare("SELECT * FROM orders WHERE id = ?")->execute([$orderId]) ? $db->query("SELECT * FROM orders WHERE id = $orderId")->fetch() : null;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Заказ оформлен — ИМСИТ Shop</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;600;700;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="public/css/style.css">
</head>
<body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--ink);padding:40px">
  <div style="background:var(--white);border-radius:24px;padding:56px 48px;max-width:480px;width:100%;text-align:center">
    <div style="font-size:72px;margin-bottom:20px;animation:bounce 1s ease">✅</div>
    <h1 style="font-family:'Playfair Display',serif;font-size:36px;font-weight:900;margin-bottom:12px">Заказ принят!</h1>
    <p style="color:var(--ink-muted);font-size:16px;margin-bottom:8px">Спасибо за покупку в ИМСИТ Shop</p>
    <?php if($orderId): ?>
    <div style="background:var(--surface);border-radius:12px;padding:16px 24px;margin:24px 0;border:1.5px solid var(--border)">
      <span style="font-size:13px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-muted)">Номер заказа</span>
      <div style="font-family:'Playfair Display',serif;font-size:28px;font-weight:700;margin-top:4px">#<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?></div>
    </div>
    <?php endif; ?>
    <p style="color:var(--ink-muted);font-size:14px;line-height:1.7;margin-bottom:28px">Мы свяжемся с вами для подтверждения заказа. Ожидайте звонка в рабочее время.</p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <a href="catalog.php" class="btn-primary">Продолжить покупки</a>
      <?php if(isLoggedIn()): ?><a href="account.php" class="btn-outline">Мои заказы</a><?php endif; ?>
    </div>
  </div>
</div>
<style>@keyframes bounce{0%,100%{transform:scale(1)}50%{transform:scale(1.15)}}</style>
</body>
</html>
