/**
 * Каталог парфюмерии
 * Модуль для отображения товаров, фильтрации, сортировки и поиска
 */

(function() {
    'use strict';

    // ========================================
    // Константы и конфигурация
    // ========================================

    const ITEMS_PER_PAGE = 12;
    const API_URL = '../src/get_products.php'; // Эндпоинт для получения данных

    // ========================================
    // DOM элементы
    // ========================================

    const elements = {
        productsGrid: document.getElementById('productsGrid'),
        productsCount: document.getElementById('productsCount'),
        pagination: document.getElementById('pagination'),
        filtersForm: document.getElementById('filtersForm'),
        searchInput: document.getElementById('searchInput'),
        searchClear: document.getElementById('searchClear'),
        sortSelect: document.getElementById('sortSelect'),
        resetFilters: document.getElementById('resetFilters'),
        sidebar: document.getElementById('sidebar'),
        overlay: document.getElementById('overlay'),
        closeSidebar: document.getElementById('closeSidebar'),
        filterToggle: document.querySelector('.header__filter-toggle'),
        searchToggle: document.querySelector('.header__search-toggle')
    };

    // ========================================
    // Состояние приложения
    // ========================================

    let state = {
        products: [],
        filteredProducts: [],
        currentPage: 1,
        filters: {
            brand: '',
            type: '',
            gender: '',
            priceFrom: null,
            priceTo: null,
            dateFrom: '',
            dateTo: ''
        },
        search: '',
        sortBy: 'popularity'
    };

    // ========================================
    // Инициализация
    // ========================================

    function init() {
        loadProducts();
        setupEventListeners();
        setupAccessibility();
    }

    // ========================================
    // Загрузка данных
    // ========================================

    async function loadProducts() {
        try {
            console.log('Запрос к API:', `${API_URL}?action=getProducts`);

            const response = await fetch(`${API_URL}?action=getProducts`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Получены данные:', data);

            if (data.success && data.products) {
                state.products = data.products.map(product => ({
                    id: product.id,
                    sku: product.sku,
                    title: product.title,
                    brand: product.brand_name || 'Бренд не указан',
                    type: product.type_name || 'Тип не указан',
                    gender: product.gender,
                    price: parseFloat(product.price),
                    image: product.image || null,
                    views: parseInt(product.views) || 0,
                    releaseDate: product.release_date,
                    description: product.description || ''
                }));

                state.filteredProducts = [...state.products];

                console.log('Товаров загружено:', state.products.length);

                renderProducts();
                updateProductsCount();
                renderPagination();
            } else {
                throw new Error('Неверный формат данных от API');
            }
        } catch (error) {
            console.error('Ошибка загрузки товаров:', error);
            // Показываем моковые данные для демонстрации
            console.log('Используем моковые данные...');
            state.products = getMockProducts();
            state.filteredProducts = [...state.products];

            renderProducts();
            updateProductsCount();
            renderPagination();
        }
    }

    // Моковые данные (для демонстрации)
    function getMockProducts() {
        return [
            {
                id: 1,
                sku: '000000012',
                title: 'Maison Lumeria',
                brand: 'Maison',
                type: 'Eau de Parfum',
                gender: 'female',
                price: 12000,
                image: '../public/assets/img/maison-lumeria.jpg',
                views: 120,
                releaseDate: '2026-01-05',
                description: 'Maison Lumeria is the embodiment of Mediterranean light...'
            },
            {
                id: 2,
                sku: '000000016',
                title: 'Maurtin Rock',
                brand: 'Maurtin',
                type: 'Eau de Parfum',
                gender: 'unisex',
                price: 16000,
                image: '../public/assets/img/maurtin-rock.jpg',
                views: 85,
                releaseDate: '2025-11-20',
                description: 'Bold and energetic composition...'
            },
            {
                id: 3,
                sku: '000000010',
                title: 'Nocturne Iris',
                brand: 'Nocturne',
                type: 'Eau de Toilette',
                gender: 'female',
                price: 10000,
                image: '../public/assets/img/nocturne-iris.jpg',
                views: 200,
                releaseDate: '2026-02-14',
                description: 'Delicate floral bouquet...'
            },
            {
                id: 4,
                sku: '000000020',
                title: 'Peak Ecstasy',
                brand: 'Peak',
                type: 'Parfum',
                gender: 'male',
                price: 20000,
                image: '../public/assets/img/peak-ecstasy.jpg',
                views: 60,
                releaseDate: '2025-09-10',
                description: 'Intense aquatic freshness...'
            }
        ];
    }

    // ========================================
    // Фильтрация и сортировка
    // ========================================

    function applyFilters() {
        let filtered = [...state.products];

        // Поиск
        if (state.search) {
            const searchLower = state.search.toLowerCase();
            filtered = filtered.filter(product =>
                product.title.toLowerCase().includes(searchLower) ||
                product.brand.toLowerCase().includes(searchLower) ||
                (product.description && product.description.toLowerCase().includes(searchLower))
            );
        }

        // Фильтры
        if (state.filters.brand) {
            filtered = filtered.filter(p => p.brand === getBrandNameById(state.filters.brand));
        }

        if (state.filters.type) {
            filtered = filtered.filter(p => p.type === getTypeNameById(state.filters.type));
        }

        if (state.filters.gender) {
            filtered = filtered.filter(p => p.gender === state.filters.gender);
        }

        if (state.filters.priceFrom !== null) {
            filtered = filtered.filter(p => p.price >= state.filters.priceFrom);
        }

        if (state.filters.priceTo !== null) {
            filtered = filtered.filter(p => p.price <= state.filters.priceTo);
        }

        // Сортировка
        filtered = sortProducts(filtered);

        state.filteredProducts = filtered;
        state.currentPage = 1;

        renderProducts();
        updateProductsCount();
        renderPagination();
    }

    function sortProducts(products) {
        const sorted = [...products];

        switch (state.sortBy) {
            case 'price-asc':
                sorted.sort((a, b) => a.price - b.price);
                break;
            case 'price-desc':
                sorted.sort((a, b) => b.price - a.price);
                break;
            case 'novelty':
                sorted.sort((a, b) => new Date(b.releaseDate) - new Date(a.releaseDate));
                break;
            case 'popularity':
            default:
                sorted.sort((a, b) => b.views - a.views);
                break;
        }

        return sorted;
    }

    // ========================================
    // Рендеринг
    // ========================================

    function renderProducts() {
        const start = (state.currentPage - 1) * ITEMS_PER_PAGE;
        const end = start + ITEMS_PER_PAGE;
        const productsToShow = state.filteredProducts.slice(start, end);

        if (productsToShow.length === 0) {
            elements.productsGrid.innerHTML = `
                <div class="no-products" style="text-align: center; padding: 40px; color: var(--color-text-secondary);">
                    <p style="font-size: 1.2rem; margin-bottom: 20px;">Товары не найдены</p>
                    <button class="btn btn--primary" onclick="resetAllFilters()" style="padding: 12px 24px; cursor: pointer;">
                        Сбросить фильтры
                    </button>
                </div>
            `;
            return;
        }

        elements.productsGrid.innerHTML = productsToShow.map(product => `
            <article class="product-card" data-product-id="${product.id}">
                <div class="product-card__image-wrapper">
                    <img 
                        src="${product.image || '../public/assets/img/placeholder.jpg'}" 
                        alt="${product.title}" 
                        class="product-card__image"
                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23E0E0E0%22 width=%22200%22 height=%22200%22/%3E%3Ctext fill=%22%23999%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3EНет фото%3C/text%3E%3C/svg%3E'"
                    >
                </div>
                <div class="product-card__content">
                    <span class="product-card__brand">${product.brand}</span>
                    <h3 class="product-card__title">
                        <a href="product.html?id=${product.id}" class="product-card__link-text">${product.title}</a>
                    </h3>
                    <p class="product-card__price">${formatPrice(product.price)}</p>
                </div>
                <div class="product-card__footer">
                    <a href="product.html?id=${product.id}" class="product-card__link" title="Подробнее о ${product.title}">Подробнее</a>
                </div>
            </article>
        `).join('');
    }

    function renderPagination() {
        const totalPages = Math.ceil(state.filteredProducts.length / ITEMS_PER_PAGE);

        if (totalPages <= 1) {
            elements.pagination.innerHTML = '';
            return;
        }

        let html = '<ul class="pagination__list">';

        // Previous
        if (state.currentPage > 1) {
            html += `
                <li>
                    <a href="#" class="pagination__link" data-page="${state.currentPage - 1}" aria-label="Предыдущая страница">
                        ← prev
                    </a>
                </li>
            `;
        }

        // Pages
        const maxVisiblePages = 5;
        let startPage = Math.max(1, state.currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        if (startPage > 1) {
            html += `<li><a href="#" class="pagination__link" data-page="1">1</a></li>`;
            if (startPage > 2) {
                html += `<li><span class="pagination__dots">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            if (i === state.currentPage) {
                html += `<li><span class="pagination__current">${i}</span></li>`;
            } else {
                html += `<li><a href="#" class="pagination__link" data-page="${i}">${i}</a></li>`;
            }
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<li><span class="pagination__dots">...</span></li>`;
            }
            html += `<li><a href="#" class="pagination__link" data-page="${totalPages}">${totalPages}</a></li>`;
        }

        // Next
        if (state.currentPage < totalPages) {
            html += `
                <li>
                    <a href="#" class="pagination__link" data-page="${state.currentPage + 1}" aria-label="Следующая страница">
                        next →
                    </a>
                </li>
            `;
        }

        html += '</ul>';
        elements.pagination.innerHTML = html;
    }

    function updateProductsCount() {
        const count = state.filteredProducts.length;
        elements.productsCount.innerHTML = `Найдено товаров: <strong>${count}</strong>`;
    }

    // ========================================
    // Обработчики событий
    // ========================================

    function setupEventListeners() {
        // Поиск
        elements.searchInput.addEventListener('input', debounce((e) => {
            state.search = e.target.value.trim();
            elements.searchClear.style.display = state.search ? 'block' : 'none';
            applyFilters();
        }, 300));

        elements.searchClear.addEventListener('click', () => {
            elements.searchInput.value = '';
            state.search = '';
            elements.searchClear.style.display = 'none';
            applyFilters();
            elements.searchInput.focus();
        });

        // Сортировка
        elements.sortSelect.addEventListener('change', (e) => {
            state.sortBy = e.target.value;
            applyFilters();
        });

        // Фильтры
        elements.filtersForm.addEventListener('submit', (e) => {
            e.preventDefault();
            updateFiltersFromForm();
            applyFilters();
            closeSidebar();
        });

        elements.resetFilters.addEventListener('click', resetAllFilters);

        // Мобильное меню фильтров
        if (elements.filterToggle) {
            elements.filterToggle.addEventListener('click', openSidebar);
        }

        if (elements.closeSidebar) {
            elements.closeSidebar.addEventListener('click', closeSidebar);
        }

        if (elements.overlay) {
            elements.overlay.addEventListener('click', closeSidebar);
        }

        // Пагинация
        elements.pagination.addEventListener('click', (e) => {
            e.preventDefault();
            if (e.target.classList.contains('pagination__link')) {
                const page = parseInt(e.target.dataset.page);
                if (page && page !== state.currentPage) {
                    state.currentPage = page;
                    renderProducts();
                    renderPagination();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        });

        // Обработка изменения размера окна
        window.addEventListener('resize', debounce(() => {
            if (window.innerWidth > 1024) {
                closeSidebar();
            }
        }, 200));
    }

    function updateFiltersFromForm() {
        const formData = new FormData(elements.filtersForm);

        state.filters = {
            brand: formData.get('brand') || '',
            type: formData.get('type') || '',
            gender: formData.get('gender') || '',
            priceFrom: formData.get('priceFrom') ? parseFloat(formData.get('priceFrom')) : null,
            priceTo: formData.get('priceTo') ? parseFloat(formData.get('priceTo')) : null,
            dateFrom: formData.get('dateFrom') || '',
            dateTo: formData.get('dateTo') || ''
        };
    }

    function resetAllFilters() {
        // Сброс формы
        elements.filtersForm.reset();

        // Сброс состояния
        state.filters = {
            brand: '',
            type: '',
            gender: '',
            priceFrom: null,
            priceTo: null,
            dateFrom: '',
            dateTo: ''
        };
        state.search = '';
        state.sortBy = 'popularity';
        elements.sortSelect.value = 'popularity';
        elements.searchInput.value = '';
        elements.searchClear.style.display = 'none';

        applyFilters();
    }

    // Мобильное меню
    function openSidebar() {
        elements.sidebar.classList.add('sidebar--active');
        elements.overlay.classList.add('overlay--active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        elements.sidebar.classList.remove('sidebar--active');
        elements.overlay.classList.remove('overlay--active');
        document.body.style.overflow = '';
    }

    // ========================================
    // Вспомогательные функции
    // ========================================

    function formatPrice(price) {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0
        }).format(price);
    }

    function getBrandNameById(id) {
        const brands = {
            '1': 'Maison',
            '2': 'Maurtin',
            '3': 'Nocturne',
            '4': 'Peak'
        };
        return brands[id] || '';
    }

    function getTypeNameById(id) {
        const types = {
            '1': 'Eau de Parfum',
            '2': 'Eau de Toilette',
            '3': 'Parfum',
            '4': 'Toilet water'
        };
        return types[id] || '';
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function showError(message) {
        console.error(message);
        alert(message);
    }

    function setupAccessibility() {
        // Управление фокусом для клавиатурной навигации
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && elements.sidebar.classList.contains('sidebar--active')) {
                closeSidebar();
            }
        });

        // Trap focus в мобильном меню
        elements.sidebar.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                const focusableElements = elements.sidebar.querySelectorAll(
                    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
                );
                const firstFocusable = focusableElements[0];
                const lastFocusable = focusableElements[focusableElements.length - 1];

                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        lastFocusable.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        firstFocusable.focus();
                        e.preventDefault();
                    }
                }
            }
        });
    }

    // ========================================
    // Запуск приложения
    // ========================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();