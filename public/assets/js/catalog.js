/**
 * Каталог парфюмерии
 * Модуль для отображения товаров, фильтрации, сортировки и поиска
 */

(function() {
    'use strict';

    // ========================================
    // Константы и конфигурация
    // ========================================

    const API_URL = '../src/get_products.php';

    // ========================================
    // DOM элементы
    // ========================================

    const elements = {
        productsGrid: document.getElementById('productsGrid'),
        paginationNumbers: document.getElementById('paginationNumbers'),
        prevPage: document.getElementById('prevPage'),
        nextPage: document.getElementById('nextPage'),
        filtersForm: document.getElementById('filtersForm'),
        searchInput: document.getElementById('searchInput'),
        filterToggle: document.getElementById('filterToggle'),
        filterDropdown: document.getElementById('filterDropdown'),
        overlay: document.getElementById('overlay'),
        resetFilters: document.getElementById('resetFilters'),
        sortButtons: document.querySelectorAll('.sort-btn'),
        itemsButtons: document.querySelectorAll('.items-btn')
    };

    // ========================================
    // Состояние приложения
    // ========================================

    let state = {
        products: [],
        filteredProducts: [],
        currentPage: 1,
        itemsPerPage: 12,
        filters: {
            brand: '',
            type: '',
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
            const response = await fetch(`${API_URL}?action=getProducts`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

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
                renderProducts();
                renderPagination();
            } else {
                throw new Error('Неверный формат данных');
            }
        } catch (error) {
            console.error('Ошибка загрузки товаров:', error);
            // Моковые данные
            state.products = getMockProducts();
            state.filteredProducts = [...state.products];
            renderProducts();
            renderPagination();
        }
    }

    function getMockProducts() {
        return [
            {
                id: 1,
                sku: '#00000012',
                title: 'Maison Lumeria',
                brand: 'Maison',
                type: 'Eau de Parfum',
                gender: 'female',
                price: 12000,
                image: '../public/assets/img/maison-lumeria.jpg',
                views: 120,
                releaseDate: '2026-01-05',
                description: 'Mediterranean light'
            },
            {
                id: 2,
                sku: '#00000016',
                title: 'Mauntin Rock',
                brand: 'Mauntin',
                type: 'Eau de Parfum',
                gender: 'unisex',
                price: 16000,
                image: '../public/assets/img/maurtin-rock.jpg',
                views: 85,
                releaseDate: '2025-11-20',
                description: 'Bold composition'
            },
            {
                id: 3,
                sku: '#00000010',
                title: 'Nocturne Iris',
                brand: 'Nocturne',
                type: 'Eau de Toilette',
                gender: 'female',
                price: 10000,
                image: '../public/assets/img/nocturne-iris.jpg',
                views: 200,
                releaseDate: '2026-02-14',
                description: 'Floral bouquet'
            },
            {
                id: 4,
                sku: '#00000020',
                title: 'Peak Ecstasy',
                brand: 'Peak',
                type: 'Parfum',
                gender: 'male',
                price: 20000,
                image: '../public/assets/img/peak-ecstasy.jpg',
                views: 60,
                releaseDate: '2025-09-10',
                description: 'Aquatic freshness'
            },
            {
                id: 5,
                sku: '#00000010',
                title: 'Nocturne Iris',
                brand: 'Nocturne',
                type: 'Eau de Toilette',
                gender: 'female',
                price: 10000,
                image: '../public/assets/img/nocturne-iris.jpg',
                views: 200,
                releaseDate: '2026-02-14',
                description: 'Floral bouquet'
            },
            {
                id: 6,
                sku: '#00000012',
                title: 'Maison Lumeria',
                brand: 'Maison',
                type: 'Eau de Parfum',
                gender: 'female',
                price: 12000,
                image: '../public/assets/img/maison-lumeria.jpg',
                views: 120,
                releaseDate: '2026-01-05',
                description: 'Mediterranean light'
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
        renderPagination();
        closeFilterDropdown();
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
        const start = (state.currentPage - 1) * state.itemsPerPage;
        const end = start + state.itemsPerPage;
        const productsToShow = state.filteredProducts.slice(start, end);

        if (productsToShow.length === 0) {
            elements.productsGrid.innerHTML = `
                <div class="no-products" style="text-align: center; padding: 40px; color: var(--color-text-secondary); grid-column: 1 / -1;">
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
                    <span class="product-card__sku">${product.sku}</span>
                    <h3 class="product-card__title">
                        <a href="product.html?id=${product.id}" class="product-card__link-text">${product.title}</a>
                    </h3>
                    <p class="product-card__price">${formatPrice(product.price)}</p>
                </div>
            </article>
        `).join('');
    }

    function renderPagination() {
        const totalPages = Math.ceil(state.filteredProducts.length / state.itemsPerPage);

        if (totalPages <= 1) {
            elements.paginationNumbers.innerHTML = '';
            elements.prevPage.disabled = true;
            elements.nextPage.disabled = true;
            return;
        }

        // Previous button
        elements.prevPage.disabled = state.currentPage === 1;

        // Next button
        elements.nextPage.disabled = state.currentPage === totalPages;

        // Numbers
        let html = '';
        const maxVisible = 5;
        let startPage = Math.max(1, state.currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);

        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            if (i === state.currentPage) {
                html += `<button class="pagination__number pagination__number--active">${i}</button>`;
            } else {
                html += `<button class="pagination__number" data-page="${i}">${i}</button>`;
            }
        }

        elements.paginationNumbers.innerHTML = html;
    }

    // ========================================
    // Обработчики событий
    // ========================================

    function setupEventListeners() {
        // Поиск
        elements.searchInput.addEventListener('input', debounce((e) => {
            state.search = e.target.value.trim();
            applyFilters();
        }, 300));

        // Фильтры - выпадающее меню
        elements.filterToggle.addEventListener('click', toggleFilterDropdown);
        elements.overlay.addEventListener('click', closeFilterDropdown);

        // Форма фильтров
        elements.filtersForm.addEventListener('submit', (e) => {
            e.preventDefault();
            updateFiltersFromForm();
            applyFilters();
        });

        elements.resetFilters.addEventListener('click', resetAllFilters);

        // Навигация по фильтрам (стрелки)
        document.querySelectorAll('.filter-select__prev, .filter-select__next').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const filterType = e.target.dataset.filter;
                const direction = e.target.classList.contains('filter-select__prev') ? -1 : 1;
                navigateFilter(filterType, direction);
            });
        });

        // Сортировка
        elements.sortButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                elements.sortButtons.forEach(b => b.classList.remove('sort-btn--active'));
                btn.classList.add('sort-btn--active');
                state.sortBy = btn.dataset.sort;
                applyFilters();
            });
        });

        // Количество товаров на странице
        elements.itemsButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                elements.itemsButtons.forEach(b => b.classList.remove('items-btn--active'));
                btn.classList.add('items-btn--active');
                state.itemsPerPage = parseInt(btn.dataset.items);
                state.currentPage = 1;
                renderProducts();
                renderPagination();
            });
        });

        // Пагинация
        elements.prevPage.addEventListener('click', () => {
            if (state.currentPage > 1) {
                state.currentPage--;
                renderProducts();
                renderPagination();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });

        elements.nextPage.addEventListener('click', () => {
            const totalPages = Math.ceil(state.filteredProducts.length / state.itemsPerPage);
            if (state.currentPage < totalPages) {
                state.currentPage++;
                renderProducts();
                renderPagination();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });

        elements.paginationNumbers.addEventListener('click', (e) => {
            if (e.target.classList.contains('pagination__number')) {
                const page = parseInt(e.target.dataset.page);
                if (page && page !== state.currentPage) {
                    state.currentPage = page;
                    renderProducts();
                    renderPagination();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        });

        // Закрытие dropdown при клике вне
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.filter-dropdown')) {
                closeFilterDropdown();
            }
        });
    }

    function toggleFilterDropdown() {
        const isActive = elements.filterDropdown.classList.contains('filter-dropdown__menu--active');
        if (isActive) {
            closeFilterDropdown();
        } else {
            openFilterDropdown();
        }
    }

    function openFilterDropdown() {
        elements.filterDropdown.classList.add('filter-dropdown__menu--active');
        elements.overlay.classList.add('overlay--active');
        elements.filterToggle.setAttribute('aria-expanded', 'true');
    }

    function closeFilterDropdown() {
        elements.filterDropdown.classList.remove('filter-dropdown__menu--active');
        elements.overlay.classList.remove('overlay--active');
        elements.filterToggle.setAttribute('aria-expanded', 'false');
    }

    function navigateFilter(filterType, direction) {
        const select = document.querySelector(`select[name="${filterType}"]`);
        if (!select) return;

        const options = Array.from(select.options);
        const currentIndex = select.selectedIndex;
        let newIndex = currentIndex + direction;

        if (newIndex >= 0 && newIndex < options.length) {
            select.selectedIndex = newIndex;
            updateFiltersFromForm();
            applyFilters();
        }
    }

    function updateFiltersFromForm() {
        const formData = new FormData(elements.filtersForm);

        state.filters = {
            brand: formData.get('brand') || '',
            type: formData.get('type') || '',
            priceFrom: formData.get('priceFrom') ? parseFloat(formData.get('priceFrom')) : null,
            priceTo: formData.get('priceTo') ? parseFloat(formData.get('priceTo')) : null,
            dateFrom: formData.get('dateFrom') || '',
            dateTo: formData.get('dateTo') || ''
        };
    }

    function resetAllFilters() {
        elements.filtersForm.reset();

        state.filters = {
            brand: '',
            type: '',
            priceFrom: null,
            priceTo: null,
            dateFrom: '',
            dateTo: ''
        };
        state.search = '';
        state.sortBy = 'popularity';
        elements.searchInput.value = '';

        // Сброс активной сортировки
        elements.sortButtons.forEach(btn => {
            btn.classList.remove('sort-btn--active');
            if (btn.dataset.sort === 'popularity') {
                btn.classList.add('sort-btn--active');
            }
        });

        applyFilters();
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

    function setupAccessibility() {
        // Escape для закрытия dropdown
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeFilterDropdown();
            }
        });

        // Trap focus в фильтре
        elements.filterDropdown.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                const focusableElements = elements.filterDropdown.querySelectorAll(
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