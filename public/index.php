<?php
// 1. Получаем URL от пользователя
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';

//echo "<pre style='background:#fff3cd; padding:20px; font-family:monospace;'>";
//echo "🔍 <strong>ОТЛАДКА РОУТИНГА:</strong>\n";
//echo "📥 \$_GET['url'] = '" . htmlspecialchars($url) . "'\n";
//echo "📥 \$_SERVER['REQUEST_URI'] = '" . htmlspecialchars($_SERVER['REQUEST_URI']) . "'\n";
//echo "📁 __DIR__ = '" . __DIR__ . "'\n";
//echo "</pre><hr>";

// 2. Очищаем от опасностей (защита от XSS)
$url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

// 3. Роутинг через if/elseif (работает с регулярками!)

// Главная и каталог
if ($url === 'catalog' || $url === 'catalog.html') {
    $pageTitle = 'Каталог парфюмерии';
    require __DIR__ . '/../templates/catalog.php';

// Страница товара: /3-nocturne-iris.html
} elseif (preg_match('/(\d+)-([a-z0-9-]+)\.html$/', $url, $m)) {
    $id = (int)$m[1];           // 3

    $pageTitle = 'Товар #' . $id;
    require __DIR__ . '/../templates/product.php';

// Страница бренда: brand/chanel.html
} elseif (preg_match('/^brand\/([a-z0-9-]+)\.html$/', $url, $m)) {
    $brandSlug = $m[1];
    $pageTitle = 'Бренд: ' . $brandSlug;
    require __DIR__ . '/../templates/brand.php';


// Вход для админа
} elseif ($url === 'login' || $url === 'login.html') {
    $pageTitle = 'Вход для администратора';
    require __DIR__ . '/../templates/login.php';

// 404 - страница не найдена
} else {
    http_response_code(404);
    require __DIR__ . '/../templates/errors/404.php';
}