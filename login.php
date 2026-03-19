<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/csrf.php';

// если уже залогинен — на главную
if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$pageTitle = 'Вход — ИМСИТ Мерч';
$error = '';
$redirect = $_GET['redirect'] ?? '/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    $db = getDB();
    $stmt = $db->prepare('SELECT id, login, password_hash, role FROM users WHERE login = ?');
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login'] = $user['login'];
        $_SESSION['role'] = $user['role'];

        $redir = $_POST['redirect'] ?? '/index.php';
        header('Location: ' . $redir);
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4"><i class="bi bi-box-arrow-in-right me-2"></i>Вход</h3>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <?= csrfField() ?>
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                        <div class="mb-3">
                            <label class="form-label">Логин</label>
                            <input type="text" name="login" class="form-control" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-imsit w-100 mb-3">Войти</button>
                    </form>
                    <p class="text-center mb-0"><small>Нет аккаунта? <a href="/register.php">Зарегистрируйтесь</a></small></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
