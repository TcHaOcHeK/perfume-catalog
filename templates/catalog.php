<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Каталог парфюмерии - широкий выбор духов от премиум брендов">
    <meta name="keywords" content="парфюмерия, духи, каталог, купить">
    <title>Каталог товаров | Парфюмерный каталог</title>

    <!-- Open Graph -->
    <meta property="og:title" content="Каталог парфюмерии">
    <meta property="og:description" content="Широкий выбор духов от премиум брендов">
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://localhost/perfume-catalog/public/catalog.php">

    <link rel="stylesheet" href="public/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="public/assets/img/logo.svg">
</head>
<body class="catalog-page">
<!-- Header -->
<header class="header">
    <div class="header-container">
        <a href="catalog.php" class="header-logo">
            <img src="public/assets/img/logo.svg" alt="Логотип парфюмерного каталога" class="logo-image">
        </a>

        <button class="btn-filters" aria-label="Открыть фильтры" aria-expanded="false">
            <svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
            </svg>
            <span>Filters</span>
        </button>

        <form class="search-form" action="catalog.php" method="GET" role="search">
            <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input
                type="search"
                name="q"
                class="search-input"
                placeholder="Search"
                aria-label="Поиск по каталогу"
                value=""
            >
        </form>

        <a href="login.php" class="user-icon" aria-label="Личный кабинет">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </a>
    </div>
</header>

<!-- Main Content -->
<main class="catalog-main">
    <!-- Sorting -->
    <div class="sorting-bar">
        <div class="sorting-container">
            <button class="sort-btn active" data-sort="price">Price</button>
            <button class="sort-btn" data-sort="popular">Popular</button>
            <button class="sort-btn" data-sort="ago">Ago</button>
        </div>
    </div>

    <!-- Products Grid -->
    <div class="products-grid">
        <!-- Product Card 1 -->
        <article class="product-card" itemscope itemtype="https://schema.org/Product">
            <a href="product.php?id=1" class="product-link">
                <div class="product-image-wrapper">
                    <img
                        src="public/assets/img/products/perfume1.jpg"
                        alt="Maison Lumeria - парфюмерная вода"
                        class="product-image"
                        itemprop="image"
                    >
                </div>
                <div class="product-info">
                    <h3 class="product-name" itemprop="name">Maison Lumeria</h3>
                    <p class="product-sku">#00000012</p>
                    <p class="product-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                        <span itemprop="price" content="12000">12.000 $</span>
                        <meta itemprop="priceCurrency" content="USD">
                    </p>
                </div>
            </a>
        </article>

        <!-- Product Card 2 -->
        <article class="product-card" itemscope itemtype="https://schema.org/Product">
            <a href="product.php?id=2" class="product-link">
                <div class="product-image-wrapper">
                    <img
                        src="public/assets/img/products/perfume2.jpg"
                        alt="Mauntin Rock - парфюмерная вода"
                        class="product-image"
                        itemprop="image"
                    >
                </div>
                <div class="product-info">
                    <h3 class="product-name" itemprop="name">Mauntin Rock</h3>
                    <p class="product-sku">#00000013</p>
                    <p class="product-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                        <span itemprop="price" content="16000">16.000 $</span>
                        <meta itemprop="priceCurrency" content="USD">
                    </p>
                </div>
            </a>
        </article>

        <!-- Product Card 3 -->
        <article class="product-card" itemscope itemtype="https://schema.org/Product">
            <a href="product.php?id=3" class="product-link">
                <div class="product-image-wrapper">
                    <img
                        src="public/assets/img/products/perfume3.jpg"
                        alt="Nocturne Iris - парфюмерная вода"
                        class="product-image"
                        itemprop="image"
                    >
                </div>
                <div class="product-info">
                    <h3 class="product-name" itemprop="name">Nocturne Iris</h3>
                    <p class="product-sku">#00000014</p>
                    <p class="product-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                        <span itemprop="price" content="10000">10.000 $</span>
                        <meta itemprop="priceCurrency" content="USD">
                    </p>
                </div>
            </a>
        </article>

        <!-- Product Card 4 -->
        <article class="product-card" itemscope itemtype="https://schema.org/Product">
            <a href="product.php?id=4" class="product-link">
                <div class="product-image-wrapper">
                    <img
                        src="public/assets/img/products/perfume4.jpg"
                        alt="Peak Ecstasy - парфюмерная вода"
                        class="product-image"
                        itemprop="image"
                    >
                </div>
                <div class="product-info">
                    <h3 class="product-name" itemprop="name">Peak Ecstasy</h3>
                    <p class="product-sku">#00000015</p>
                    <p class="product-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                        <span itemprop="price" content="20000">20.000 $</span>
                        <meta itemprop="priceCurrency" content="USD">
                    </p>
                </div>
            </a>
        </article>

        <!-- Product Card 5 -->
        <article class="product-card" itemscope itemtype="https://schema.org/Product">
            <a href="product.php?id=5" class="product-link">
                <div class="product-image-wrapper">
                    <img
                        src="public/assets/img/products/perfume5.jpg"
                        alt="Nocturne Iris - парфюмерная вода"
                        class="product-image"
                        itemprop="image"
                    >
                </div>
                <div class="product-info">
                    <h3 class="product-name" itemprop="name">Nocturne Iris</h3>
                    <p class="product-sku">#00000016</p>
                    <p class="product-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                        <span itemprop="price" content="10000">10.000 $</span>
                        <meta itemprop="priceCurrency" content="USD">
                    </p>
                </div>
            </a>
        </article>

        <!-- Product Card 6 -->
        <article class="product-card" itemscope itemtype="https://schema.org/Product">
            <a href="product.php?id=6" class="product-link">
                <div class="product-image-wrapper">
                    <img
                        src="public/assets/img/products/perfume6.jpg"
                        alt="Maison Lumeria - парфюмерная вода"
                        class="product-image"
                        itemprop="image"
                    >
                </div>
                <div class="product-info">
                    <h3 class="product-name" itemprop="name">Maison Lumeria</h3>
                    <p class="product-sku">#00000012</p>
                    <p class="product-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                        <span itemprop="price" content="12000">12.000 $</span>
                        <meta itemprop="priceCurrency" content="USD">
                    </p>
                </div>
            </a>
        </article>
    </div>

    <!-- Pagination -->
    <div class="pagination-wrapper">
        <div class="pagination-items">
            <span class="items-label">Items per page:</span>
            <a href="?per_page=6" class="items-btn active">6</a>
            <a href="?per_page=12" class="items-btn">12</a>
            <a href="?per_page=18" class="items-btn">18</a>
            <a href="?per_page=24" class="items-btn">24</a>
            <span class="items-dots">...</span>
        </div>

        <nav class="pagination" aria-label="Навигация по страницам">
            <a href="?page=1" class="pagination-link prev">previous</a>
            <a href="?page=1" class="pagination-link">1</a>
            <a href="?page=2" class="pagination-link active">2</a>
            <a href="?page=3" class="pagination-link">3</a>
            <a href="?page=4" class="pagination-link">4</a>
            <a href="?page=5" class="pagination-link">5</a>
            <a href="?page=6" class="pagination-link">6</a>
            <a href="?page=3" class="pagination-link next">next</a>
        </nav>
    </div>
</main>

<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <p>&copy; 2026 Парфюмерный каталог. Все права защищены.</p>
    </div>
</footer>

<script src="public/assets/js/catalog.js"></script>
</body>
</html>