<?php
require_once __DIR__ . '/../includes/functions.php';
if (!isAdmin()) redirect('../login.php');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$order = $db->prepare("SELECT o.*, COALESCE(u.name,'Гость') as user_name FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.id=?");
$order->execute([$id]);
$order = $order->fetch();
if (!$order) redirect('orders.php');

$items = $db->prepare("SELECT oi.*, p.slug FROM order_items oi LEFT JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
$items->execute([$id]);
$items = $items->fetchAll();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? $order['status'];
    $db->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$status, $id]);
    $order['status'] = $status;
}

$statusLabels = ['pending'=>'Ожидает','processing'=>'Обработка','shipped'=>'Доставляется','delivered'=>'Доставлен','cancelled'=>'Отменён'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Заказ #<?= str_pad($id,5,'0',STR_PAD_LEFT) ?> — Админ ИМСИТ Shop</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;600;700;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
<div class="admin-layout">
<!-- Sidebar -->
<aside class="admin-sidebar">
  <div class="admin-logo">
    <a href="../index.php" style="display:flex;align-items:center;gap:10px;color:var(--white)">
      <span style="font-size:18px;color:var(--accent)">✦</span>
      <div><div style="font-family:'Playfair Display',serif;font-weight:900;font-size:17px">ИМСИТ Shop</div><div style="font-size:11px;opacity:.4;letter-spacing:.1em">ADMIN PANEL</div></div>
    </a>
  </div>
  <nav class="admin-nav">
    <a href="index.php" class="admin-nav-link active">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Дашборд
    </a>
    <a href="orders.php" class="admin-nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
      Заказы
    </a>
    <a href="products.php" class="admin-nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
      Товары
    </a>
    <a href="users.php" class="admin-nav-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Пользователи
    </a>
    <a href="../account.php" class="admin-nav-link" style="margin-top:20px;border-top:1px solid rgba(255,255,255,.08);padding-top:20px">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
  Личный кабинет
</a>
  </nav>
</aside>

<main class="admin-content">
  <div class="admin-header" style="display:flex;align-items:center;justify-content:space-between">
    <div>
      <h1>Заказ #<?= str_pad($id,5,'0',STR_PAD_LEFT) ?></h1>
      <p>Оформлен <?= date('d.m.Y в H:i', strtotime($order['created_at'])) ?></p>
    </div>
    <a href="orders.php" class="btn-outline">← Все заказы</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:24px">
    <div>
      <!-- Items -->
      <div style="background:var(--white);border-radius:var(--radius);border:1.5px solid var(--border);padding:28px;margin-bottom:20px">
        <h3 style="font-family:'Playfair Display',serif;font-size:20px;margin-bottom:20px">Состав заказа</h3>
        <table class="data-table">
          <thead><tr><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Итого</th></tr></thead>
          <tbody>
            <?php foreach($items as $item): ?>
            <tr>
              <td>
                <strong><?= e($item['product_name']) ?></strong>
                <?php if($item['product_id']): ?>
                <br><a href="../product.php?id=<?= $item['product_id'] ?>" target="_blank" style="font-size:12px;color:var(--accent)">Перейти к товару →</a>
                <?php endif; ?>
              </td>
              <td><?= formatPrice($item['price']) ?></td>
              <td style="text-align:center"><strong><?= $item['quantity'] ?></strong></td>
              <td style="font-family:'Playfair Display',serif;font-weight:700"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div style="text-align:right;margin-top:16px;padding-top:16px;border-top:2px solid var(--ink)">
          <span style="font-family:'Playfair Display',serif;font-size:24px;font-weight:700">Итого: <?= formatPrice($order['total']) ?></span>
        </div>
      </div>

      <!-- Customer info -->
      <div style="background:var(--white);border-radius:var(--radius);border:1.5px solid var(--border);padding:28px">
        <h3 style="font-family:'Playfair Display',serif;font-size:20px;margin-bottom:20px">Данные покупателя</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div><div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-muted);margin-bottom:4px">Имя</div><strong><?= e($order['full_name']) ?></strong></div>
          <div><div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-muted);margin-bottom:4px">Email</div><a href="mailto:<?= e($order['email']) ?>" style="color:var(--accent)"><?= e($order['email']) ?></a></div>
          <div><div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-muted);margin-bottom:4px">Телефон</div><a href="tel:<?= e($order['phone']) ?>"><?= e($order['phone']) ?></a></div>
          <div><div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-muted);margin-bottom:4px">Оплата</div><?= $order['payment_method']==='card'?'💳 Банковская карта':'💵 Наличные' ?></div>
          <div style="grid-column:span 2"><div style="font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-muted);margin-bottom:4px">Адрес доставки</div><?= e($order['address']) ?></div>
        </div>
      </div>
    </div>

    <!-- Status control -->
    <div>
      <div style="background:var(--white);border-radius:var(--radius);border:1.5px solid var(--border);padding:24px;position:sticky;top:90px">
        <h3 style="font-family:'Playfair Display',serif;font-size:18px;margin-bottom:20px">Статус заказа</h3>
        <form method="POST">
          <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:20px">
            <?php foreach($statusLabels as $key => $label): ?>
            <label style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:10px;border:1.5px solid <?= $order['status']===$key?'var(--ink)':'var(--border)' ?>;cursor:pointer;transition:border-color .2s">
              <input type="radio" name="status" value="<?= $key ?>" <?= $order['status']===$key?'checked':'' ?> style="accent-color:var(--ink)">
              <span class="badge badge-<?= $key ?>"><?= $label ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <button type="submit" class="btn-primary" style="width:100%;justify-content:center">Сохранить статус</button>
        </form>
      </div>
    </div>
  </div>
</main>
</div>
<div class="toast" id="toast"></div>
<script src="../public/js/main.js"></script>
</body>
</html>
