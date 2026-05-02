<?php
$productId = $id ?? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$productId) {
    http_response_code(404);  // Лучше 404, чем редирект
    require __DIR__ . '/errors/404.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="" id="metaDescription">
    <meta property="og:title" content="" id="ogTitle">
    <meta property="og:description" content="" id="ogDescription">
    <meta property="og:type" content="product">
    <meta property="og:image" content="" id="ogImage">
    <title id="pageTitle">Товар | Parfum Catalog</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
    <link rel="stylesheet" href="../public/assets/css/product.css">
    <link rel="icon" type="image/svg+xml" href="../public/assets/img/favicon.svg">

    <!-- Schema.org markup -->
    <script type="application/ld+json" id="productSchema"></script>
</head>
<body class="product-page">
<!-- Хлебные крошки -->
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item">
            <a href="catalog.php" class="breadcrumbs__link">Каталог</a>
        </li>
        <li class="breadcrumbs__item">
            <a href="#" class="breadcrumbs__link" id="brandBreadcrumb">Бренд</a>
        </li>
        <li class="breadcrumbs__item">
            <span class="breadcrumbs__current" id="productBreadcrumb">Товар</span>
        </li>
    </ol>
</nav>

<!-- Основной контент -->
<main class="product-main container">
    <article class="product-detail" itemscope itemtype="https://schema.org/Product">
        <!-- Левая часть: Галерея изображений -->
        <div class="product-gallery">
            <div class="product-gallery__container">
                <div class="product-gallery__image-wrapper">
                    <img src="" alt="" class="product-gallery__image" id="productImage" itemprop="image" onerror="this.src='../public/assets/img/placeholder.svg'">

                    <!-- Стрелки теперь внутри wrapper -->
                    <button class="product-gallery__nav product-gallery__nav--prev" aria-label="Предыдущее изображение" id="prevImage">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>

                    <button class="product-gallery__nav product-gallery__nav--next" aria-label="Следующее изображение" id="nextImage">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Миниатюры -->
            <div class="product-gallery__thumbnails" id="thumbnailsContainer">
                <!-- Миниатюры будут добавлены через JavaScript -->
            </div>
        </div>

        <!-- Правая часть: Информация о товаре -->
        <div class="product-info">
            <div class="product-info__header">
                <h1 class="product-info__title" itemprop="name" id="productTitle"></h1>
                <a href="catalog.php" class="btn-catalog">← Back to catalog</a>
            </div>

            <p class="product-info__sku" id="productSKU"></p>
            <p class="product-info__price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                <span class="product-info__price-value" id="productPrice" itemprop="price"></span>
                <span class="product-info__price-currency" itemprop="priceCurrency" content="RUB">₽</span>
                <link itemprop="availability" href="https://schema.org/InStock">
            </p>

            <div class="product-info__description" itemprop="description" id="productDescription"></div>

            <!-- Характеристики -->
            <div class="product-characteristics">
                <h2 class="product-characteristics__title">Characteristics:</h2>
                <div class="product-characteristics__grid">
                    <div class="characteristic-item">
                        <span class="characteristic-item__label">Volume:</span>
                        <span class="characteristic-item__value" id="productVolume"></span>
                    </div>
                    <div class="characteristic-item">
                        <span class="characteristic-item__label">Type:</span>
                        <span class="characteristic-item__value" id="productType"></span>
                    </div>
                    <div class="characteristic-item">
                        <span class="characteristic-item__label">Family:</span>
                        <span class="characteristic-item__value" id="productFamily"></span>
                    </div>
                    <div class="characteristic-item">
                        <span class="characteristic-item__label">Longevity:</span>
                        <span class="characteristic-item__value" id="productLongevity"></span>
                    </div>
                </div>
            </div>

            <!-- Пирамида аромата -->
            <div class="fragrance-pyramid">
                <h2 class="fragrance-pyramid__title">Fragrance Pyramid:</h2>
                <div class="fragrance-pyramid__grid">
                    <div class="pyramid-column">
                        <h3 class="pyramid-column__title">Top notes:</h3>
                        <ul class="pyramid-column__list" id="topNotes"></ul>
                    </div>
                    <div class="pyramid-column">
                        <h3 class="pyramid-column__title">Heart notes:</h3>
                        <ul class="pyramid-column__list" id="heartNotes"></ul>
                    </div>
                    <div class="pyramid-column">
                        <h3 class="pyramid-column__title">Base notes:</h3>
                        <ul class="pyramid-column__list" id="baseNotes"></ul>
                    </div>
                </div>
            </div>

            <div class="product-info__date-wrapper">
                <p class="footer-inline">&copy; 2026 Parfum Catalog. Все права защищены.</p>
                <p class="product-info__date">Added on <span id="productDate"></span></p>
            </div>
        </div>
    </article>
</main>


<script>
    // Передаём ID товара из PHP в JS
    window.PRODUCT_ID = <?= json_encode($productId) ?>;
</script>
<script src="../public/assets/js/product.js"></script>
</body>
</html>