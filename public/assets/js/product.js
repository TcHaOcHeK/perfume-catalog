/**
 * Страница товара
 * Модуль для отображения детальной информации о товаре
 * Файл: ../public/assets/js/product.js
 */

(function() {
    'use strict';

    const API_URL = '../src/get_product.php';
    const BASE_IMAGE_PATH = '../public/assets/img/';

    // DOM элементы
    const elements = {
        productImage: document.getElementById('productImage'),
        prevImageBtn: document.getElementById('prevImage'),
        nextImageBtn: document.getElementById('nextImage'),
        thumbnailsContainer: document.getElementById('thumbnailsContainer'),
        productTitle: document.getElementById('productTitle'),
        productSKU: document.getElementById('productSKU'),
        productPrice: document.getElementById('productPrice'),
        productDescription: document.getElementById('productDescription'),
        productVolume: document.getElementById('productVolume'),
        productType: document.getElementById('productType'),
        productFamily: document.getElementById('productFamily'),
        productLongevity: document.getElementById('productLongevity'),
        productDate: document.getElementById('productDate'),
        topNotes: document.getElementById('topNotes'),
        heartNotes: document.getElementById('heartNotes'),
        baseNotes: document.getElementById('baseNotes'),
        brandBreadcrumb: document.getElementById('brandBreadcrumb'),
        productBreadcrumb: document.getElementById('productBreadcrumb'),
        pageTitle: document.getElementById('pageTitle'),
        metaDescription: document.getElementById('metaDescription'),
        ogTitle: document.getElementById('ogTitle'),
        ogDescription: document.getElementById('ogDescription'),
        ogImage: document.getElementById('ogImage'),
        productSchema: document.getElementById('productSchema')
    };

    let state = {
        productId: window.PRODUCT_ID || null,
        product: null,
        images: [],
        currentImageIndex: 0
    };

    function init() {
        if (!state.productId) {
            showError('Product ID not found');
            return;
        }
        loadProduct();
        setupEventListeners();
    }

    async function loadProduct() {
        try {
            const response = await fetch(`${API_URL}?id=${state.productId}`);

            if (!response.ok) {
                if (response.status === 404) {
                    showError('Product not found');
                } else {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return;
            }

            const data = await response.json();

            if (data.success && data.product) {
                state.product = data.product;
                state.images = data.images;
                renderProduct();
                updateMetaTags();
                updateSchema();
            } else {
                throw new Error('Invalid data format');
            }
        } catch (error) {
            console.error('Error loading product:', error);
            showError('Failed to load product information');
        }
    }

    function renderProduct() {
        const product = state.product;

        elements.productTitle.textContent = product.title;
        elements.productSKU.textContent = product.sku || '#' + String(product.id).padStart(8, '0');
        elements.productPrice.textContent = product.formatted_price;
        elements.productDescription.textContent = product.description || '';
        elements.productVolume.textContent = product.volumes || '100 ml';
        elements.productType.textContent = product.type_name || 'Eau de Parfum';
        elements.productFamily.textContent = product.family_name || 'Floral';
        elements.productLongevity.textContent = product.longevity || '6-8 hours';
        elements.productDate.textContent = product.formatted_date;

        // Хлебные крошки
        elements.brandBreadcrumb.textContent = product.brand_name || 'Brand';
        elements.brandBreadcrumb.href = `catalog.php?brand=${product.brand_id}`;
        elements.productBreadcrumb.textContent = product.title;

        renderGallery();
        renderFragranceNotes(product.fragrance_notes);
    }

    function renderGallery() {
        if (!state.images || state.images.length === 0) {
            elements.productImage.src = BASE_IMAGE_PATH + 'placeholder.svg';
            elements.productImage.alt = state.product?.title || 'Product';
            elements.thumbnailsContainer.style.display = 'none';
            return;
        }

        const mainImageIndex = state.images.findIndex(img => img.is_main == 1);
        state.currentImageIndex = mainImageIndex >= 0 ? mainImageIndex : 0;

        updateCurrentImage();
        renderThumbnails();
    }

    function updateCurrentImage() {
        if (state.images.length > 0) {
            const image = state.images[state.currentImageIndex];
            const fileName = getImageFileName(image.image_path);
            elements.productImage.src = BASE_IMAGE_PATH + fileName;
            elements.productImage.alt = state.product.title;
        }
    }

    function renderThumbnails() {
        if (state.images.length <= 1) {
            elements.thumbnailsContainer.style.display = 'none';
            return;
        }

        elements.thumbnailsContainer.style.display = 'flex';
        elements.thumbnailsContainer.innerHTML = state.images.map((image, index) => `
            <button class="product-gallery__thumbnail ${index === state.currentImageIndex ? 'active' : ''}" 
                    data-index="${index}" 
                    aria-label="Image ${index + 1}">
                <img src="${BASE_IMAGE_PATH}${getImageFileName(image.image_path)}" 
                     alt="Thumbnail ${index + 1}"
                     onerror="this.src='../public/assets/img/placeholder.svg'">
            </button>
        `).join('');

        elements.thumbnailsContainer.querySelectorAll('.product-gallery__thumbnail').forEach(thumb => {
            thumb.addEventListener('click', (e) => {
                state.currentImageIndex = parseInt(e.currentTarget.dataset.index);
                updateCurrentImage();
                renderThumbnails();
            });
        });
    }

    function renderFragranceNotes(notes) {
        if (!notes) return;
        elements.topNotes.innerHTML = (notes.top || []).map(note => `<li>${escapeHtml(note)}</li>`).join('');
        elements.heartNotes.innerHTML = (notes.heart || []).map(note => `<li>${escapeHtml(note)}</li>`).join('');
        elements.baseNotes.innerHTML = (notes.base || []).map(note => `<li>${escapeHtml(note)}</li>`).join('');
    }

    function updateMetaTags() {
        const product = state.product;
        const title = `${product.title} | Parfum Catalog`;
        const description = product.description || '';
        const image = state.images.length > 0
            ? BASE_IMAGE_PATH + getImageFileName(state.images[0].image_path)
            : BASE_IMAGE_PATH + 'placeholder.svg';

        document.title = title;
        if (elements.pageTitle) elements.pageTitle.textContent = title;
        if (elements.metaDescription) elements.metaDescription.content = description;
        if (elements.ogTitle) elements.ogTitle.content = title;
        if (elements.ogDescription) elements.ogDescription.content = description;
        if (elements.ogImage) elements.ogImage.content = window.location.origin + image;
    }

    function updateSchema() {
        const product = state.product;
        const image = state.images.length > 0
            ? window.location.origin + BASE_IMAGE_PATH + getImageFileName(state.images[0].image_path)
            : window.location.origin + BASE_IMAGE_PATH + 'placeholder.svg';

        const schema = {
            "@context": "https://schema.org/",
            "@type": "Product",
            "name": product.title,
            "image": image,
            "description": product.description,
            "sku": product.sku || '#' + String(product.id).padStart(8, '0'),
            "brand": { "@type": "Brand", "name": product.brand_name },
            "offers": {
                "@type": "Offer",
                "url": window.location.href,
                "priceCurrency": "RUB",
                "price": product.price,
                "availability": "https://schema.org/InStock"
            }
        };

        if (elements.productSchema) {
            elements.productSchema.textContent = JSON.stringify(schema);
        }
    }

    function setupEventListeners() {
        elements.prevImageBtn?.addEventListener('click', () => {
            if (state.images.length > 0) {
                state.currentImageIndex = (state.currentImageIndex - 1 + state.images.length) % state.images.length;
                updateCurrentImage();
                renderThumbnails();
            }
        });

        elements.nextImageBtn?.addEventListener('click', () => {
            if (state.images.length > 0) {
                state.currentImageIndex = (state.currentImageIndex + 1) % state.images.length;
                updateCurrentImage();
                renderThumbnails();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (state.images?.length > 1) {
                if (e.key === 'ArrowLeft') elements.prevImageBtn?.click();
                if (e.key === 'ArrowRight') elements.nextImageBtn?.click();
            }
        });
    }

    function getImageFileName(path) {
        if (!path) return 'placeholder.svg';
        const parts = path.split('/');
        return parts[parts.length - 1];
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showError(message) {
        const main = document.querySelector('.product-main');
        if (main) {
            main.innerHTML = `
                <div style="text-align:center;padding:60px 20px;">
                    <h1 style="color:#1E1E1E;margin-bottom:20px;">Error</h1>
                    <p style="color:#666;margin-bottom:30px;">${escapeHtml(message)}</p>
                    <a href="catalog.php" class="btn btn--primary">Back to catalog</a>
                </div>
            `;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();