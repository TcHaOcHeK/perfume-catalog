<?php
// 1. Получаем URL от пользователя
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';

// 2. Очищаем от опасностей (защита от XSS)
$url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
$url = explode('/', $url);

$entity = !empty($url) ? $url[0] : false;
$page = !empty($url) ? $url[1] : false;

//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//var_dump($page); die;

preg_match('/(\d+)-([a-z0-9-]+)\.html$/', $page, $m);

$pageId = !(empty($m)) ? $m[1] : false;

require __DIR__ . "/../templates/{$entity}.php";

// Главная и каталог
//if ($url === 'catalog' || $url === 'catalog.html') {
//    $pageTitle = 'Каталог парфюмерии';
//    require __DIR__ . '/../templates/catalog.php';
//
//} elseif (preg_match('/brand-(\d+)-([a-z0-9-]+)\.html$/', $url, $m)) {
//    $brandSlug = (int)$m[1];
//    $pageTitle = 'Бренд: ' . $brandSlug;
//    require __DIR__ . '/../templates/brand.php';
//
//// Страница товара: /3-nocturne-iris.html
//} elseif (preg_match('/(\d+)-([a-z0-9-]+)\.html$/', $url, $m)) {
//    $id = (int)$m[1];           // 3
//    $pageTitle = 'Товар #' . $id;
//    require __DIR__ . '/../templates/product.php';
//
//// Вход для админа
//} elseif ($url === 'login' || $url === 'login.html') {
//    $pageTitle = 'Вход для администратора';
//    require __DIR__ . '/../templates/login.php';
//
//// 404 - страница не найдена
//} else {
//    http_response_code(404);
//    require __DIR__ . '/../templates/errors/404.php';
//}