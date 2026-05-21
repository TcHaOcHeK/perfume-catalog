<?php
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : '';
$url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
$url = explode('/', $url);

$page = !empty($url) ? $url[1] : false;
preg_match('/(\d+)-([a-z0-9-]+)\.html$/', $page, $m);
$brandId = !(empty($m)) ? (int)$m[1] : false;
//var_dump($brandId);die;

if (!$brandId) {
    http_response_code(404);
    require __DIR__ . '/perfume-catalog/templates/errors/404.php';
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Парфюмерия бренда - премиальные ароматы">
    <meta property="og:title" content="Parfum Catalog">
    <meta property="og:description" content="Коллекция ароматов">
    <meta property="og:type" content="website">
    <title id="pageTitle">Бренд | Parfum Catalog</title>
    <link rel="stylesheet" href="/perfume-catalog/public/assets/css/style.css">
    <link rel="stylesheet" href="/perfume-catalog/public/assets/css/brand.css">
    <link rel="icon" type="image/svg+xml" href="/perfume-catalog/public/assets/img/favicon.svg">
</head>
<body>

<!-- Шапка -->
<header class="header">
    <div class="container header__container">
        <!-- Логотип -->
        <a href="/perfume-catalog/public/catalog.html" class="logo">
            <img src="/perfume-catalog/public/assets/img/logo.svg" alt="Parfum Catalog Logo" class="logo__image">
        </a>

        <!-- Центральная часть -->
        <div class="header__center">
            <div class="header-search">
                <div class="header-search__wrapper">
                    <svg class="header-search__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" class="header-search__input" id="searchInput" placeholder="Search" aria-label="Поиск товаров">
                </div>
            </div>
        </div>

        <!-- Пользователь -->
        <div class="header__user">
            <a href="/perfume-catalog/public/login" class="user-btn" aria-label="Личный кабинет">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M12 14c-6 0-8 3-8 6v1h16v-1c0-3-2-6-8-6z"></path>
                </svg>
            </a>
        </div>
    </div>
</header>

<!-- Хлебные крошки -->
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="/perfume-catalog/public/catalog.html" class="breadcrumbs__link">Каталог</a>
        </li>
        <li class="breadcrumbs__item">
            <span class="breadcrumbs__current" id="brandName">Бренд</span>
        </li>
    </ol>
</nav>

<!-- Основной контент -->
<main class="brand-main">
    <!-- Hero секция с информацией о бренде -->
    <section class="brand-hero">
        <div class="container">
            <div class="brand-hero__content">
                <div class="brand-hero__text">
                    <h1 class="brand-hero__title" id="brandTitle"></h1>
                    <div class="brand-hero__description" id="brandDescription"></div>
                </div>
                <div class="brand-hero__image">
                    <img src="" alt="" id="brandImage" class="brand-hero__img">
                </div>
            </div>
        </div>
    </section>

    <!-- Популярные товары бренда -->
    <section class="brand-products">
        <div class="container">
            <h2 class="brand-products__title">Popular product</h2>
            <div class="products-grid" id="productsGrid">
                <!-- Карточки товаров будут добавлены через JavaScript -->
            </div>
        </div>
    </section>
</main>

<!-- Кнопка возврата -->
<div class="back-to-catalog">
    <div class="container">
        <a href="/perfume-catalog/public/catalog.html" class="btn-back">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back in catalog
        </a>
    </div>
</div>

<!-- Подвал -->
<footer class="footer">
    <div class="container footer__container">
        <p class="footer__text">&copy; 2026 Parfum Catalog. Все права защищены.</p>
    </div>
</footer>

<script>
    window.BRAND_ID = <?= json_encode($brandId) ?>;
</script>
<script src="/perfume-catalog/public/assets/js/brand.js"></script>
</body>
</html>