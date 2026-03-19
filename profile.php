<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

requireLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$pageTitle = 'Личный кабинет — ИМСИТ Мерч';

// обновление профиля
$profileMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    verifyCsrfToken();
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $stmt = $db->prepare('UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?');
    $stmt->execute([$fullName, $phone, $email, $userId]);
    $profileMsg = 'Данные обновлены.';
}

// сообщение об успешном заказе
$orderSuccess = '';
if (isset($_SESSION['order_success'])) {
    $orderSuccess = $_SESSION['order_success'];
    unset($_SESSION['order_success']);
}

// данные пользователя
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

// история заказов
$stmt = $db->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

// статусы
$statusLabels = [
    'pending' => ['Ожидает', 'warning'],
    'confirmed' => ['Подтверждён', 'primary'],
    'shipped' => ['Отправлен', 'info'],
    'completed' => ['Выполнен', 'success'],
    'cancelled' => ['Отменён', 'danger'],
];

include 'includes/header.php';
?>

<div class="container my-5">
    <h1 class="mb-4 fw-bold"><i class="bi bi-person-circle me-2"></i>Личный кабинет</h1>

    <?php if ($orderSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($orderSuccess) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($profileMsg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($profileMsg) ?></div>
    <?php endif; ?>

    <div class="row g-5">
        <!-- мои данные -->
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><h5 class="mb-0">Мои данные</h5></div>
                <div class="card-body">
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mb-3">
                            <label class="form-label">ФИО</label>
                            <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Телефон</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-imsit w-100">Сохранить</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- история заказов -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white"><h5 class="mb-0">История заказов</h5></div>
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <p class="text-muted text-center py-3">Заказов пока нет</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>#</th><th>Дата</th><th>Сумма</th><th>Статус</th></tr></thead>
                                <tbody>
                                    <?php foreach ($orders as $o): ?>
                                        <?php $s = $statusLabels[$o['status']] ?? ['Неизвестно', 'secondary']; ?>
                                        <tr>
                                            <td><?= $o['id'] ?></td>
                                            <td><?= date('d.m.Y', strtotime($o['created_at'])) ?></td>
                                            <td><?= number_format($o['total'], 0, '.', ' ') ?> ₽</td>
                                            <td><span class="badge bg-<?= $s[1] ?>"><?= $s[0] ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
