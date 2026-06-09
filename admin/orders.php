<?php
require_once __DIR__ . '/../includes/functions.php';
if (!isAdmin()) redirect('../login.php');

$db = getDB();
$status = $_GET['status'] ?? '';

$where  = $status ? "WHERE o.status = ?" : "";
$params = $status ? [$status] : [];
$orders = $db->prepare("SELECT o.*, COALESCE(u.name,'Гость') as user_name FROM orders o LEFT JOIN users u ON o.user_id=u.id $where ORDER BY o.created_at DESC");
$orders->execute($params);
$orders = $orders->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Заказы — Админ ИМСИТ Shop</title>
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
  <div class="admin-header">
    <h1>Управление заказами</h1>
    <p>Обработка и отслеживание заказов</p>
  </div>

  <!-- Filters -->
  <div style="display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap">
    <?php
    $statuses = [''=> 'Все', 'pending'=>'Ожидает', 'processing'=>'Обработка', 'shipped'=>'Доставляется', 'delivered'=>'Доставлен', 'cancelled'=>'Отменён'];
    foreach($statuses as $key=>$label): ?>
    <a href="orders.php<?= $key?"?status=$key":'' ?>" class="filter-item <?= $status===$key?'active':'' ?>" style="border-radius:50px;padding:8px 18px;font-size:13px">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

  <table class="data-table">
    <thead>
      <tr><th>№</th><th>Покупатель</th><th>Телефон</th><th>Сумма</th><th>Оплата</th><th>Статус</th><th>Дата</th><th></th></tr>
    </thead>
    <tbody>
      <?php if(empty($orders)): ?>
      <tr><td colspan="8" style="text-align:center;padding:32px;color:var(--ink-muted)">Заказов не найдено</td></tr>
      <?php else: ?>
      <?php foreach($orders as $o): ?>
      <tr>
        <td><strong>#<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></strong></td>
        <td><?= e($o['full_name']) ?><br><small style="color:var(--ink-muted)"><?= e($o['email']) ?></small></td>
        <td><?= e($o['phone']) ?></td>
        <td style="font-family:'Playfair Display',serif;font-weight:700"><?= formatPrice($o['total']) ?></td>
        <td><?= $o['payment_method']==='card'?'💳':'💵' ?></td>
        <td>
          <select onchange="changeOrderStatus(<?= $o['id'] ?>, this.value)" style="padding:6px 10px;border-radius:8px;border:1.5px solid var(--border);font-family:inherit;font-size:13px;font-weight:600;cursor:pointer">
            <?php foreach(['pending'=>'Ожидает','processing'=>'Обработка','shipped'=>'Доставляется','delivered'=>'Доставлен','cancelled'=>'Отменён'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $o['status']===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td style="color:var(--ink-muted);font-size:13px"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
        <td><a href="order-detail.php?id=<?= $o['id'] ?>" style="font-weight:700;font-size:13px;color:var(--accent)">Детали →</a></td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</main>
</div>
<div class="toast" id="toast"></div>
<script src="../public/js/main.js"></script>
</body>
</html>
