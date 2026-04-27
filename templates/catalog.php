<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Каталог парфюмерии - широкий выбор ароматов от премиум брендов">
    <meta property="og:title" content="Парфюмерный каталог">
    <meta property="og:description" content="Найдите свой идеальный аромат">
    <meta property="og:type" content="website">
    <title>Каталог парфюмерии | Parfum Catalog</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
    <link rel="stylesheet" href="../public/assets/css/catalog.css">
    <link rel="icon" type="image/svg+xml" href="../public/assets/img/favicon.svg">
</head>
<body>
<!-- Шапка -->
<header class="header">
    <div class="container header__container">
        <a href="index.html" class="logo">
            <img src="../public/assets/img/logo.svg" alt="Parfum Catalog Logo" class="logo__image">
        </a>

        <nav class="nav header__nav">
            <ul class="nav__list">
                <li class="nav__item"><a href="index.html" class="nav__link" title="Главная">Главная</a></li>
                <li class="nav__item"><a href="catalog.php" class="nav__link nav__link--active" title="Каталог">Каталог</a></li>
                <li class="nav__item"><a href="#" class="nav__link" title="Бренды">Бренды</a></li>
                <li class="nav__item"><a href="#" class="nav__link" title="О нас">О нас</a></li>
            </ul>
        </nav>

        <div class="header__actions">
            <button class="btn-icon header__search-toggle" aria-label="Поиск" title="Открыть поиск">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </button>
            <button class="btn-icon header__filter-toggle" aria-label="Фильтры" title="Открыть фильтры">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                </svg>
            </button>
        </div>
    </div>
</header>

<!-- Хлебные крошки -->
<nav class="breadcrumbs container" aria-label="Хлебные крошки">
    <ol class="breadcrumbs__list">
        <li class="breadcrumbs__item"><a href="index.html" class="breadcrumbs__link" title="Главная">Главная</a></li>
        <li class="breadcrumbs__item"><span class="breadcrumbs__current">Каталог</span></li>
    </ol>
</nav>

<!-- Основной контент -->
<main class="main container">
    <div class="catalog">
        <!-- Боковая панель с фильтрами -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar__header">
                <h2 class="sidebar__title">Фильтры</h2>
                <button class="btn-icon sidebar__close" aria-label="Закрыть фильтры" id="closeSidebar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <form class="filters" id="filtersForm">
                <!-- Бренд -->
                <div class="filter-group">
                    <h3 class="filter-group__title">Бренд</h3>
                    <div class="filter-group__content">
                        <select class="form-select" id="filterBrand" name="brand">
                            <option value="">Все бренды</option>
                            <option value="1">Maison</option>
                            <option value="2">Maurtin</option>
                            <option value="3">Nocturne</option>
                            <option value="4">Peak</option>
                        </select>
                    </div>
                </div>

                <!-- Тип -->
                <div class="filter-group">
                    <h3 class="filter-group__title">Тип</h3>
                    <div class="filter-group__content">
                        <select class="form-select" id="filterType" name="type">
                            <option value="">Все типы</option>
                            <option value="1">Eau de Parfum</option>
                            <option value="2">Eau de Toilette</option>
                            <option value="3">Parfum</option>
                            <option value="4">Toilet water</option>
                        </select>
                    </div>
                </div>

                <!-- Пол -->
                <div class="filter-group">
                    <h3 class="filter-group__title">Пол</h3>
                    <div class="filter-group__content">
                        <select class="form-select" id="filterGender" name="gender">
                            <option value="">Все</option>
                            <option value="male">Мужской</option>
                            <option value="female">Женский</option>
                            <option value="unisex">Унисекс</option>
                        </select>
                    </div>
                </div>

                <!-- Цена -->
                <div class="filter-group">
                    <h3 class="filter-group__title">Цена</h3>
                    <div class="filter-group__content">
                        <div class="price-range">
                            <div class="price-range__inputs">
                                <input type="number" class="form-input price-range__input" id="priceFrom" placeholder="От" min="0">
                                <span class="price-range__separator">—</span>
                                <input type="number" class="form-input price-range__input" id="priceTo" placeholder="До" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Дата выпуска -->
                <div class="filter-group">
                    <h3 class="filter-group__title">Дата выпуска</h3>
                    <div class="filter-group__content">
                        <div class="date-range">
                            <input type="date" class="form-input" id="dateFrom" placeholder="От">
                            <input type="date" class="form-input" id="dateTo" placeholder="До">
                        </div>
                    </div>
                </div>

                <div class="filters__actions">
                    <button type="button" class="btn btn--secondary" id="resetFilters">Сбросить</button>
                    <button type="submit" class="btn btn--primary">Применить</button>
                </div>
            </form>
        </aside>

        <!-- Основной контент каталога -->
        <div class="catalog__content">
            <!-- Поиск и сортировка -->
            <div class="catalog__toolbar">
                <div class="search">
                    <div class="search__wrapper">
                        <svg class="search__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input type="text" class="search__input" id="searchInput" placeholder="Поиск по названию, описанию, характеристикам..." aria-label="Поиск товаров">
                        <button class="search__clear" id="searchClear" aria-label="Очистить поиск" style="display: none;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="sort">
                    <select class="form-select sort__select" id="sortSelect" aria-label="Сортировка">
                        <option value="popularity">По популярности</option>
                        <option value="novelty">По новизне</option>
                        <option value="price-asc">По цене (возрастание)</option>
                        <option value="price-desc">По цене (убывание)</option>
                    </select>
                </div>
            </div>

            <!-- Счётчик товаров -->
            <div class="catalog__info">
                <span class="catalog__count" id="productsCount">Найдено товаров: <strong>0</strong></span>
            </div>

            <!-- Сетка товаров -->
            <div class="products-grid" id="productsGrid">
                <!-- Карточки товаров будут добавлены через JavaScript -->
            </div>

            <!-- Пагинация -->
            <nav class="pagination" id="pagination" aria-label="Навигация по страницам">
                <ul class="pagination__list">
                    <!-- Пагинация будет добавлена через JavaScript -->
                </ul>
            </nav>
        </div>
    </div>
</main>

<!-- Подвал -->
<footer class="footer">
    <div class="container footer__container">
        <p class="footer__text">&copy; 2026 Parfum Catalog. Все права защищены.</p>
    </div>
</footer>

<!-- Оверлей для мобильных фильтров -->
<div class="overlay" id="overlay"></div>

<script src="../public/assets/js/catalog.js"></script>
</body>
</html>