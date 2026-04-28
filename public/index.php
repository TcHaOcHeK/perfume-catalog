<?php
require_once '../config/db.php';

$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';

switch (true) {
    case $url === '' || $url === 'catalog':
        $pageTitle = "Каталог";
        require_once '../templates/catalog.php';
        break;
    case  $url === 'login':
        $pageTitle = "Регистрация";
        require_once '../templates/login.php';
        break;

    default:
        http_response_code(404);
        echo "<h1>404 - Страница не найдена</h1><p><a href='index.php'>Вернуться</a></p>";
        break;
}
?>