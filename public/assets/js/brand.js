/**
 * Страница бренда
 * Модуль для отображения информации о бренде и его популярных товаров
 */

(function() {
    'use strict';

    const API_URL = '/perfume-catalog/src/get_products.php';
    const BASE_IMAGE_PATH = '/perfume-catalog/public/assets/img/';

    // DOM элементы
    const elements = {
        brandTitle: document.getElementById('brandTitle'),
        brandDescription: document.getElementById('brandDescription'),
        brandImage: document.getElementById('brandImage'),
        brandName: document.getElementById('brandName'),
        pageTitle: document.getElementById('pageTitle'),
        productsGrid: document.getElementById('productsGrid'),
        searchInput: document.getElementById('searchInput')
    };

    let state = {
        brandId: window.BRAND_ID || null,
        brand: null,
        products: [],
        search: ''
    };

    // ========================================
    // Инициализация
    // ========================================

    function init() {
        if (!state.brandId) {
            showError('Brand ID not found');
            return;
        }
        loadBrandData();
        setupEventListeners();
    }

    // ========================================
    // Загрузка данных
    // ========================================

    async function loadBrandData() {
        try {
            // Загружаем все товары, затем фильтруем по бренду
            const response = await fetch(`${API_URL}?action=getProducts&brand=${state.brandId}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.products) {
                // Получаем товары бренда
                state.products = data.products.map(product => ({
                    id: product.id,
                    sku: product.sku,
                    title: product.title,
                    brand: product.brand_name || 'Бренд не указан',
                    brandDescription: product.brand_description || null,
                    type: product.type_name || 'Тип не указан',
                    gender: product.gender,
                    price: parseFloat(product.price),
                    image: product.image || null,
                    views: parseInt(product.views) || 0,
                    releaseDate: product.release_date,
                    description: product.description || ''
                }));

                // Если есть товары, берем информацию о бренде из первого товара
                if (state.products.length > 0) {
                    const firstProduct = state.products[0];
                    state.brand = {
                        id: state.brandId,
                        name: firstProduct.brand,
                        description: getBrandDescription(firstProduct.brand, firstProduct.brandDescription)
                    };

                    renderBrandInfo();
                    renderProducts();
                } else {
                    showError('No products found for this brand');
                }
            } else {
                throw new Error('Invalid data format');
            }
        } catch (error) {
            console.error('Error loading brand data:', error);
            showError('Failed to load brand information');
        }
    }

    // ========================================
    // Рендеринг
    // ========================================

    function renderBrandInfo() {
        const brand = state.brand;

        elements.brandTitle.textContent = brand.name;
        elements.brandName.textContent = brand.name;
        elements.brandDescription.innerHTML = brand.description;
        elements.pageTitle.textContent = `${brand.name} | Parfum Catalog`;

        // Устанавливаем изображение бренда (можно добавить отдельное поле в БД)
        const brandImageMap = {
            'Maison': '/perfume-catalog/public/assets/img/maison-lumeria.svg',
            'Mauntin': '/perfume-catalog/public/assets/img/maurtin-rock.svg',
            'Nocturne': '/perfume-catalog/public/assets/img/nocturne-iris.svg',
            'Peak': '/perfume-catalog/public/assets/img/peak-ecstasy.svg'
        };

        const imageUrl = brandImageMap[brand.name] || BASE_IMAGE_PATH + 'placeholder.svg';
        elements.brandImage.src = imageUrl;
        elements.brandImage.alt = `${brand.name} Brand`;
    }

    function renderProducts() {
        // Сортируем по популярности (views) и берем топ-4
        const sortedProducts = [...state.products]
            .sort((a, b) => b.views - a.views)
            .slice(0, 4);

        // Фильтруем по поиску если есть
        let productsToShow = sortedProducts;
        if (state.search) {
            const searchLower = state.search.toLowerCase();
            productsToShow = sortedProducts.filter(product =>
                product.title.toLowerCase().includes(searchLower) ||
                product.brand.toLowerCase().includes(searchLower) ||
                (product.description && product.description.toLowerCase().includes(searchLower))
            );
        }

        if (productsToShow.length === 0) {
            elements.productsGrid.innerHTML = `
                <div class="no-products" style="text-align: center; padding: 40px; color: var(--color-text-secondary); grid-column: 1 / -1;">
                    <p style="font-size: 1.2rem;">No products found</p>
                </div>
            `;
            return;
        }

        elements.productsGrid.innerHTML = productsToShow.map(product => `
            <article class="product-card" data-product-id="${product.id}">
                <div class="product-card__image-wrapper">
                    <img 
                        src="${product.image || BASE_IMAGE_PATH + 'placeholder.svg'}" 
                        alt="${product.title}" 
                        class="product-card__image"
                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23E0E0E0%22 width=%22200%22 height=%22200%22/%3E%3Ctext fill=%22%23999%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo image%3C/text%3E%3C/svg%3E'"
                    >
                </div>
                <div class="product-card__content">
                    <span class="product-card__brand">${product.brand}</span>
                    <span class="product-card__sku">${product.sku}</span>
                    <h3 class="product-card__title">
                       <a href="/perfume-catalog/public/${product.id}/${generateSlug(product.title)}.html" class="product-card__link-text">${product.title}</a>
                    </h3>
                    <p class="product-card__price">${formatPrice(product.price)}</p>
                </div>
            </article>
        `).join('');
    }

    // ========================================
    // Вспомогательные функции
    // ========================================

    function getBrandDescription(brandName, description = null) {

        if (description !== null && description !== undefined && description.trim() !== '') {
            return description;
        }


        const descriptions = {
            'Maison': `<p>Maison Lumière is the embodiment of Mediterranean light in every bottle.</p>
                <p>A warm trail of white flowers and wood creates an aura of sophistication and femininity.</p>
                <p>Ideal for those who appreciate elegant simplicity.</p>`,
            'Mauntin': `<p>Mauntin represents bold compositions for the modern adventurer.</p>
                <p>Woody and spicy notes create powerful, memorable fragrances.</p>
                <p>Perfect for those who dare to stand out.</p>`,
            'Nocturne': `<p>Nocturne captures the mystery and romance of the night.</p>
                <p>Floral and oriental compositions for elegant, sophisticated individuals.</p>
                <p>Each fragrance tells a unique story.</p>`,
            'Peak': `<p>Peak embodies freshness and aquatic energy.</p>
                <p>Citrus and marine notes for active, dynamic personalities.</p>
                <p>The choice of those who reach for the top.</p>`
        };

        return descriptions[brandName] || `<p>Premium fragrances from ${brandName}.</p>`;
    }

    function generateSlug(text) {
        const converter = {
            'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd',
            'е': 'e', 'ё': 'yo', 'ж': 'zh', 'з': 'z', 'и': 'i',
            'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n',
            'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't',
            'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c', 'ч': 'ch',
            'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '',
            'э': 'e', 'ю': 'yu', 'я': 'ya',
            ' ': '-', ',': '', '.': '', '!': '', '?': '',
            ':': '', ';': '', '(': '', ')': '', '[': '', ']': ''
        };

        return text
            .toLowerCase()
            .trim()
            .split('')
            .map(char => converter[char] || char)
            .join('')
            .replace(/[^a-z0-9\-]/g, '')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function formatPrice(price) {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            minimumFractionDigits: 0
        }).format(price);
    }

    function showError(message) {
        const main = document.querySelector('.brand-main');
        if (main) {
            main.innerHTML = `
                <div style="text-align:center;padding:60px 20px;">
                    <h1 style="color:#1E1E1E;margin-bottom:20px;">Error</h1>
                    <p style="color:#666;margin-bottom:30px;">${escapeHtml(message)}</p>
                    <a href="/perfume-catalog/public/catalog" class="btn-back" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:#1E1E1E;color:#fff;border-radius:50px;text-decoration:none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Back to catalog
                    </a>
                </div>
            `;
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ========================================
    // Обработчики событий
    // ========================================

    function setupEventListeners() {
        // Поиск по бренду
        elements.searchInput.addEventListener('input', debounce((e) => {
            state.search = e.target.value.trim();
            renderProducts();
        }, 300));
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

    // ========================================
    // Запуск приложения
    // ========================================

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();