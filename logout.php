<?php
session_start();
// завершаем сессию
$_SESSION = [];
session_destroy();
header('Location: /index.php');
exit;
