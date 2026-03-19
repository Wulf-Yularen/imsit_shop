<?php
// функции авторизации

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: /login.php');
        exit;
    }
}

// количество товаров в корзине (для бейджа)
function getCartCount() {
    if (!isLoggedIn()) return 0;
    $db = getDB();
    $stmt = $db->prepare('SELECT SUM(quantity) FROM cart WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return (int) $stmt->fetchColumn();
}
