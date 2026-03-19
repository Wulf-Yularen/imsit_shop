<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$pageTitle = 'Регистрация — ИМСИТ Мерч';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $login = trim($_POST['login'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($login === '') $errors[] = 'Введите логин';
    if ($email === '') $errors[] = 'Введите email';
    if (strlen($password) < 6) $errors[] = 'Пароль минимум 6 символов';
    if ($password !== $password2) $errors[] = 'Пароли не совпадают';

    if (empty($errors)) {
        $db = getDB();

        // проверяем уникальность
        $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE login = ?');
        $stmt->execute([$login]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Этот логин уже занят';

        $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'Этот email уже используется';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare('INSERT INTO users (login, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$login, $email, $hash, 'user']);

        $_SESSION['user_id'] = $db->lastInsertId();
        $_SESSION['login'] = $login;
        $_SESSION['role'] = 'user';

        header('Location: /index.php');
        exit;
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4"><i class="bi bi-person-plus me-2"></i>Регистрация</h3>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                        </div>
                    <?php endif; ?>
                    <form method="post">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label">Логин</label>
                            <input type="text" name="login" class="form-control" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль (мин. 6 символов)</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Подтвердите пароль</label>
                            <input type="password" name="password2" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-imsit w-100 mb-3">Зарегистрироваться</button>
                    </form>
                    <p class="text-center mb-0"><small>Уже есть аккаунт? <a href="/login.php">Войдите</a></small></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
