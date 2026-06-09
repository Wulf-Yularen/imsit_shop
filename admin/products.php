<?php
require_once __DIR__ . '/../includes/functions.php';
if (!isAdmin()) redirect('../login.php');

$db  = getDB();
$msg = '';

// Delete product
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM products WHERE id = ?")->execute([(int)$_GET['delete']]);
    $msg = 'Товар удалён';
}

// Toggle active
if (isset($_GET['toggle'])) {
    $db->prepare("UPDATE products SET is_active = 1 - is_active WHERE id = ?")->execute([(int)$_GET['toggle']]);
    redirect('products.php');
}

$products = $db->query("
    SELECT p.*, c.name as cat_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Товары — Админ ИМСИТ Shop</title>
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
  <div class="admin-header" style="display:flex;align-items:flex-start;justify-content:space-between">
    <div>
      <h1>Товары</h1>
      <p>Управление ассортиментом магазина</p>
    </div>
    <a href="product-edit.php" class="btn-primary">+ Добавить товар</a>
  </div>

  <?php if($msg): ?>
  <div class="alert alert-success" style="margin-bottom:20px"><?= e($msg) ?></div>
  <?php endif; ?>

  <table class="data-table">
    <thead>
      <tr><th>ID</th><th>Название</th><th>Категория</th><th>Цена</th><th>Склад</th><th>Статус</th><th>Действия</th></tr>
    </thead>
    <tbody>
      <?php foreach($products as $p): ?>
      <tr>
        <td style="color:var(--ink-muted);font-size:13px">#<?= $p['id'] ?></td>
        <td>
          <strong style="font-size:14px"><?= e($p['name']) ?></strong>
          <?php if($p['is_featured']): ?><span class="badge" style="background:#fff3cd;color:#856404;margin-left:8px">ХИТ</span><?php endif; ?>
        </td>
        <td style="color:var(--ink-muted)"><?= e($p['cat_name'] ?? '—') ?></td>
        <td style="font-family:'Playfair Display',serif;font-weight:700"><?= formatPrice($p['price']) ?></td>
        <td>
          <span style="font-weight:700;color:<?= $p['stock']>0?'#2a9d5c':'var(--accent)' ?>">
            <?= $p['stock'] ?> шт.
          </span>
        </td>
        <td>
          <a href="products.php?toggle=<?= $p['id'] ?>">
            <span class="badge" style="background:<?= $p['is_active']?'#d4edda':'#f8d7da' ?>;color:<?= $p['is_active']?'#155724':'#721c24' ?>;cursor:pointer">
              <?= $p['is_active'] ? 'Активен' : 'Скрыт' ?>
            </span>
          </a>
        </td>
        <td>
          <div style="display:flex;gap:8px">
            <a href="product-edit.php?id=<?= $p['id'] ?>" class="btn-outline" style="padding:6px 14px;font-size:13px">Редакт.</a>
            <button onclick="confirmDelete('products.php?delete=<?= $p['id'] ?>', 'Удалить товар «<?= e(addslashes($p['name'])) ?>»?')"
              style="padding:6px 14px;border-radius:50px;border:1.5px solid var(--accent);background:transparent;color:var(--accent);font-family:inherit;font-size:13px;font-weight:700;cursor:pointer">
              Удалить
            </button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>
</div>
<div class="toast" id="toast"></div>
<script src="../public/js/main.js"></script>
</body>
</html>
