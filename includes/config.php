<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'imsit_shop');
define('DB_USER', 'imsit_user');        // создай этого пользователя MySQL на сервере
define('DB_PASS', 'change_me_on_server');  // поменяй на реальный пароль на сервере
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'ИМСИТ Shop');
define('SITE_URL', 'https://wwffsq.space');
define('UPLOADS_DIR', __DIR__ . '/../public/images/');
define('UPLOADS_URL', SITE_URL . '/public/images/');

// подключение к базе данных (PDO)
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Ошибка подключения к БД: ' . $e->getMessage());
        }
    }
    return $pdo;
}