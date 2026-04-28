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
        <!-- Логотип -->
        <a href="index.html" class="logo">
            <img src="../public/assets/img/logo.svg" alt="Parfum Catalog Logo" class="logo__image">
        </a>

        <!-- Центральная часть: Фильтры и Поиск -->
        <div class="header__center">
            <!-- Кнопка фильтров -->
            <div class="filter-dropdown">
                <button class="filter-dropdown__toggle" id="filterToggle" aria-label="Фильтры" aria-expanded="false">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    <span>Filters</span>
                </button>

                <!-- Выпадающий список фильтров -->
                <div class="filter-dropdown__menu" id="filterDropdown">
                    <form class="filters-form" id="filtersForm">
                        <!-- Бренд -->
                        <div class="filter-row">
                            <label class="filter-label">Brand</label>
                            <div class="filter-select-wrapper">
                                <button type="button" class="filter-select__prev" data-filter="brand" aria-label="Предыдущий бренд">&lt;</button>
                                <select class="filter-select" id="filterBrand" name="brand">
                                    <option value="">Все бренды</option>
                                    <option value="1">Maison</option>
                                    <option value="2">Maurtin</option>
                                    <option value="3">Nocturne</option>
                                    <option value="4">Peak</option>
                                </select>
                                <button type="button" class="filter-select__next" data-filter="brand" aria-label="Следующий бренд">&gt;</button>
                            </div>
                        </div>

                        <!-- Тип -->
                        <div class="filter-row">
                            <label class="filter-label">Type</label>
                            <div class="filter-select-wrapper">
                                <button type="button" class="filter-select__prev" data-filter="type" aria-label="Предыдущий тип">&lt;</button>
                                <select class="filter-select" id="filterType" name="type">
                                    <option value="">Все типы</option>
                                    <option value="1">Eau de Parfum</option>
                                    <option value="2">Eau de Toilette</option>
                                    <option value="3">Parfum</option>
                                    <option value="4">Toilet water</option>
                                </select>
                                <button type="button" class="filter-select__next" data-filter="type" aria-label="Следующий тип">&gt;</button>
                            </div>
                        </div>

                        <!-- Цена -->
                        <div class="filter-row">
                            <label class="filter-label">Price</label>
                            <div class="filter-price-range">
                                <input type="number" class="filter-input" id="priceFrom" name="priceFrom" placeholder="0" min="0">
                                <span class="filter-separator">—</span>
                                <input type="number" class="filter-input" id="priceTo" name="priceTo" placeholder="100000" min="0">
                            </div>
                        </div>

                        <!-- Дата -->
                        <div class="filter-row">
                            <label class="filter-label">Date</label>
                            <div class="filter-date-range">
                                <input type="text" class="filter-input" id="dateFrom" name="dateFrom" placeholder="01.01.2026">
                                <span class="filter-separator">—</span>
                                <input type="text" class="filter-input" id="dateTo" name="dateTo" placeholder="12.2026">
                            </div>
                        </div>

                        <div class="filters-form__actions">
                            <button type="button" class="btn btn--text" id="closeFilters">Закрыть</button>
                            <button type="button" class="btn btn--text" id="resetFilters">Сбросить</button>
                            <button type="submit" class="btn btn--primary filters-form__apply">Применить</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Поиск -->
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
            <button class="user-btn" aria-label="Личный кабинет">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M12 14c-6 0-8 3-8 6v1h16v-1c0-3-2-6-8-6z"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Сортировка -->
    <div class="header__sort">
        <div class="container">
            <div class="sort-buttons">
                <button class="sort-btn" data-sort="price-asc">Price</button>
                <button class="sort-btn sort-btn--active" data-sort="popularity">Popular</button>
                <button class="sort-btn" data-sort="novelty">Ago</button>
            </div>
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
    <!-- Сетка товаров -->
    <div class="products-grid" id="productsGrid">
        <!-- Карточки товаров будут добавлены через JavaScript -->
    </div>

    <!-- Нижняя панель: количество товаров и пагинация -->
    <div class="catalog-footer">
        <div class="catalog-footer__left">
            <span class="items-per-page-label">Items per page:</span>
            <div class="items-per-page">
                <button class="items-btn" data-items="6">6</button>
                <button class="items-btn items-btn--active" data-items="12">12</button>
                <button class="items-btn" data-items="18">18</button>
                <button class="items-btn" data-items="24">24</button>
                <span class="items-more">...</span>
            </div>
        </div>

        <nav class="pagination" id="pagination" aria-label="Навигация по страницам">
            <button class="pagination__btn" id="prevPage" disabled>previous</button>
            <div class="pagination__numbers" id="paginationNumbers">
                <!-- Номера страниц будут добавлены через JavaScript -->
            </div>
            <button class="pagination__btn" id="nextPage">next</button>
        </nav>
    </div>
</main>

<!-- Подвал -->
<footer class="footer">
    <div class="container footer__container">
        <p class="footer__text">&copy; 2026 Parfum Catalog. Все права защищены.</p>
    </div>
</footer>

<script src="../public/assets/js/catalog.js"></script>
</body>
</html>