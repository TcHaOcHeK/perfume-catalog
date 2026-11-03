<?php
// 1. Получаем URL от пользователя
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';

// 2. Очищаем от опасностей (защита от XSS)
$url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
$url = explode('/', $url);

$entity = $url[0] ?? '';
$page = $url[1] ?? '';

$entity = $entity != '' ? $entity : 'catalog';// переход на главную страницу
$entity = (str_contains($entity, '.html')) ? str_replace('.html', '', $entity): $entity;

preg_match('/(\d+)-([a-z0-9-]+)\.html$/', $page, $m);

$pageId = !(empty($m)) ? $m[1] : false;

require __DIR__ . "/templates/{$entity}.php";
