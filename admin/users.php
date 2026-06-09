<?php
require_once __DIR__ . '/../includes/functions.php';
if (!isAdmin()) redirect('../login.php');

$db = getDB();
$users = $db->query("
    SELECT u.*, COUNT(o.id) as order_count, COALESCE(SUM(o.total),0) as total_spent
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Пользователи — Админ ИМСИТ Shop</title>
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
    <h1>Пользователи</h1>
    <p>Управление аккаунтами покупателей</p>
  </div>

  <table class="data-table">
    <thead>
      <tr><th>ID</th><th>Имя</th><th>Email</th><th>Телефон</th><th>Роль</th><th>Заказов</th><th>Потрачено</th><th>Дата рег.</th></tr>
    </thead>
    <tbody>
      <?php foreach($users as $u): ?>
      <tr>
        <td style="color:var(--ink-muted);font-size:13px">#<?= $u['id'] ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--ink);color:var(--white);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0">
              <?= mb_strtoupper(mb_substr($u['name'],0,1)) ?>
            </div>
            <strong><?= e($u['name']) ?></strong>
          </div>
        </td>
        <td style="color:var(--ink-muted)"><?= e($u['email']) ?></td>
        <td><?= e($u['phone'] ?? '—') ?></td>
        <td>
          <span class="badge" style="background:<?= $u['role']==='admin'?'#3a7bd5':'var(--surface)' ?>;color:<?= $u['role']==='admin'?'#fff':'var(--ink)' ?>">
            <?= $u['role'] === 'admin' ? '⭐ Админ' : 'Покупатель' ?>
          </span>
        </td>
        <td style="text-align:center"><strong><?= $u['order_count'] ?></strong></td>
        <td style="font-family:'Playfair Display',serif;font-weight:700"><?= formatPrice((float)$u['total_spent']) ?></td>
        <td style="color:var(--ink-muted);font-size:13px"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>
</div>
<script src="../public/js/main.js"></script>
</body>
</html>
