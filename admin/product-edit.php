<?php
require_once __DIR__ . '/../includes/functions.php';
if (!isAdmin()) redirect('../login.php');

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
$msg = '';
$product = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    if (!$product) redirect('products.php');
}

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $slug        = trim($_POST['slug'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 0);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    // Auto-generate slug
    if (!$slug && $name) {
        $slug = mb_strtolower(preg_replace('/[^a-zA-Z0-9а-яёА-ЯЁ\s-]/u', '', $name));
        $slug = preg_replace('/\s+/', '-', trim($slug));
        $slug = substr($slug, 0, 100) . '-' . time();
    }

    // Handle image upload
    $image = $product['image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $ext  = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $filename = 'product-' . time() . '.' . $ext;
            $dest = __DIR__ . '/../public/images/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $image = $filename;
            }
        }
    }

    if (!$name || $price <= 0) {
        $msg = 'error:Заполните название и цену';
    } else {
        if ($id) {
            $db->prepare("UPDATE products SET name=?, slug=?, category_id=?, description=?, price=?, stock=?, image=?, is_featured=?, is_active=? WHERE id=?")
               ->execute([$name, $slug, $category_id ?: null, $description, $price, $stock, $image, $is_featured, $is_active, $id]);
            $msg = 'success:Товар обновлён';
            $product = array_merge($product, compact('name','slug','category_id','description','price','stock','image','is_featured','is_active'));
        } else {
            $db->prepare("INSERT INTO products (name, slug, category_id, description, price, stock, image, is_featured, is_active) VALUES (?,?,?,?,?,?,?,?,?)")
               ->execute([$name, $slug, $category_id ?: null, $description, $price, $stock, $image, $is_featured, $is_active]);
            redirect('products.php');
        }
    }
}

[$msgType, $msgText] = $msg ? explode(':', $msg, 2) : ['', ''];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $id ? 'Редактирование' : 'Новый товар' ?> — Админ ИМСИТ Shop</title>
<link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;600;700;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
<div class="admin-layout">
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
      <h1><?= $id ? 'Редактировать товар' : 'Добавить товар' ?></h1>
      <p><?= $id ? e($product['name']) : 'Новый товар в каталог' ?></p>
    </div>
    <a href="products.php" class="btn-outline">← Назад к товарам</a>
  </div>

  <?php if($msgText): ?>
  <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>" style="margin-bottom:24px"><?= e($msgText) ?></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 320px;gap:28px;max-width:1000px">
    <form method="POST" enctype="multipart/form-data">

      <div style="background:var(--white);border-radius:var(--radius);border:1.5px solid var(--border);padding:32px;margin-bottom:20px">
        <h3 style="font-family:'Playfair Display',serif;font-size:20px;margin-bottom:24px">Основная информация</h3>

        <div class="form-group">
          <label>Название товара *</label>
          <input type="text" name="name" required value="<?= e($product['name'] ?? '') ?>" placeholder="Например: Ручка гелевая ИМСИТ">
        </div>

        <div class="form-group">
          <label>Категория</label>
          <select name="category_id">
            <option value="">— Без категории —</option>
            <?php foreach($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
              <?= e($cat['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Описание</label>
          <textarea name="description" rows="5" placeholder="Подробное описание товара..."
            style="resize:vertical"><?= e($product['description'] ?? '') ?></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label>Цена (₽) *</label>
            <input type="number" name="price" step="0.01" min="0" required value="<?= $product['price'] ?? '' ?>" placeholder="0.00">
          </div>
          <div class="form-group">
            <label>Остаток на складе</label>
            <input type="number" name="stock" min="0" value="<?= $product['stock'] ?? '0' ?>">
          </div>
        </div>
      </div>

      <div style="background:var(--white);border-radius:var(--radius);border:1.5px solid var(--border);padding:32px;margin-bottom:20px">
        <h3 style="font-family:'Playfair Display',serif;font-size:20px;margin-bottom:24px">Изображение</h3>
        <?php if(!empty($product['image'])): ?>
        <div style="margin-bottom:16px">
          <img src="../public/images/<?= e($product['image']) ?>" alt="Текущее изображение"
               style="width:120px;height:120px;object-fit:cover;border-radius:12px;border:1.5px solid var(--border)">
          <p style="font-size:13px;color:var(--ink-muted);margin-top:8px">Загрузите новое изображение для замены</p>
        </div>
        <?php endif; ?>
        <div class="form-group">
          <label>Загрузить изображение (JPG, PNG, WebP)</label>
          <input type="file" name="image" accept="image/*"
            style="padding:10px;border:2px dashed var(--border);border-radius:10px;background:var(--surface);cursor:pointer">
        </div>
      </div>

      <div style="display:flex;gap:16px">
        <button type="submit" class="btn-primary btn-lg" style="flex:1;justify-content:center">
          <?= $id ? '💾 Сохранить изменения' : '✚ Создать товар' ?>
        </button>
        <a href="products.php" class="btn-ghost btn-lg">Отмена</a>
      </div>
    </form>

    <!-- Sidebar options -->
    <div>
      <div style="background:var(--white);border-radius:var(--radius);border:1.5px solid var(--border);padding:24px;margin-bottom:16px">
        <h3 style="font-family:'Playfair Display',serif;font-size:18px;margin-bottom:20px">Публикация</h3>
        <label style="display:flex;align-items:center;gap:12px;cursor:pointer;margin-bottom:16px">
          <input type="checkbox" name="is_active" form="product-form" <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>
            style="width:18px;height:18px;accent-color:var(--ink)">
          <div>
            <strong style="display:block">Активен</strong>
            <span style="font-size:13px;color:var(--ink-muted)">Товар виден в каталоге</span>
          </div>
        </label>
        <label style="display:flex;align-items:center;gap:12px;cursor:pointer">
          <input type="checkbox" name="is_featured" form="product-form" <?= ($product['is_featured'] ?? 0) ? 'checked' : '' ?>
            style="width:18px;height:18px;accent-color:var(--accent)">
          <div>
            <strong style="display:block">Хит продаж</strong>
            <span style="font-size:13px;color:var(--ink-muted)">Показывать на главной</span>
          </div>
        </label>
      </div>

      <?php if($id): ?>
      <div style="background:var(--white);border-radius:var(--radius);border:1.5px solid var(--border);padding:24px">
        <h3 style="font-family:'Playfair Display',serif;font-size:18px;margin-bottom:16px">Быстрые действия</h3>
        <a href="../product.php?id=<?= $id ?>" target="_blank" class="btn-outline" style="width:100%;justify-content:center;margin-bottom:10px;display:flex">
          👁 Просмотр на сайте
        </a>
        <button onclick="confirmDelete('products.php?delete=<?= $id ?>', 'Удалить этот товар?')"
          style="width:100%;padding:11px;border-radius:50px;border:1.5px solid var(--accent);background:transparent;color:var(--accent);font-family:inherit;font-size:14px;font-weight:700;cursor:pointer">
          🗑 Удалить товар
        </button>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Hidden checkboxes need to be inside the form -->
  <script>
    // Move checkboxes inside form on load
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.querySelector('form');
      document.querySelectorAll('input[form="product-form"]').forEach(el => {
        el.removeAttribute('form');
        form.appendChild(el.closest('label') || el);
      });
    });
  </script>
</main>
</div>
<div class="toast" id="toast"></div>
<script src="../public/js/main.js"></script>
</body>
</html>
