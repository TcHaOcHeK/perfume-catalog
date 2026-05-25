<?php
// 1. Получаем URL от пользователя
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';

// 2. Очищаем от опасностей (защита от XSS)
$url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
$url = explode('/', $url);

$entity = !empty($url) ? $url[0] : false;
$page = !empty($url) ? $url[1] : false;

//var_dump($entity);
$entity = (str_contains($entity, '.html')) ? str_replace('.html', '', $entity): $entity;
//var_dump($entity); die;
//if ($entity.)
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//var_dump($page); die;

preg_match('/(\d+)-([a-z0-9-]+)\.html$/', $page, $m);

$pageId = !(empty($m)) ? $m[1] : false;

require __DIR__ . "/../templates/{$entity}.php";