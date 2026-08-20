(function () {
    'use strict';

    const root = document.getElementById('almaden-book-product-panel');
    const content = document.getElementById('almaden-book-product-content');
    const status = document.getElementById('almaden-book-product-status');
    const initial = document.getElementById('almaden-book-product-initial-state');
    const api = window.AlmadenBookProductAPI;
    if (!root || !content || !status || !initial || !api) return;

    let state = JSON.parse(initial.textContent || '{}');
    let mode = 'link';
    let editing = false;
    let selectedProduct = null;
    let searchResults = [];
    let activeResult = -1;
    let searchTimer = null;

    const formatOrder = ['both', 'physical', 'ebook'];
    const formatLabels = {
        both: 'Ambos',
        physical: 'Físico',
        ebook: 'Ebook',
    };

    const escape = (value) => String(value ?? '').replace(/[&<>'"]/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;',
    }[char]));

    const money = (value) => value === '' || value === null ? 'Sin precio' : escape(value);
    const icon = (name) => {
        const paths = {
            link: '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M10.59 13.41a1.99 1.99 0 0 0 2.82 0l3.18-3.18a2 2 0 0 0-2.83-2.83l-1.41 1.41"/><path d="M13.41 10.59a1.99 1.99 0 0 0-2.82 0l-3.18 3.18a2 2 0 1 0 2.83 2.83l1.41-1.41"/><path d="M8 16l-1.5 1.5a4 4 0 1 1-5.66-5.66L2.34 10.5"/><path d="M16 8l1.5-1.5a4 4 0 1 1 5.66 5.66L21.66 13.5"/></svg>',
            unlink: '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M10.59 13.41a1.99 1.99 0 0 0 2.82 0l3.18-3.18a2 2 0 0 0-2.83-2.83l-1.41 1.41"/><path d="M13.41 10.59a1.99 1.99 0 0 0-2.82 0l-3.18 3.18a2 2 0 1 0 2.83 2.83l1.41-1.41"/><path d="M3 3l18 18"/></svg>',
            save: '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M5 4h11l3 3v13H5z"/><path d="M8 4v6h7V4"/><path d="M8 18h8"/><path d="M12 11v5"/></svg>',
        };
        return paths[name] || '';
    };

    const actionButton = (action, label, iconName, format = '', className = 'abp-icon-button') => `
        <button type="button" class="${className}" data-action="${action}"${format ? ` data-format="${format}"` : ''} aria-label="${escape(label)}" title="${escape(label)}">
            ${icon(iconName)}
        </button>`;

    const parseSuggestedPriceBase = (value) => {
        const raw = String(value ?? '').trim();
        if (!raw) return null;
        const separatorIndex = Math.max(raw.lastIndexOf('.'), raw.lastIndexOf(','));
        if (separatorIndex >= 0) {
            const decimals = raw.slice(separatorIndex + 1).replace(/[^\d]/g, '');
            if (decimals.length > 0 && decimals.length < 3) {
                return null;
            }
        }
        const digits = raw.replace(/[^\d]/g, '');
        return digits ? Number(digits) : null;
    };

    const formatSuggestedPrice = (value) => {
        const integer = Math.max(0, Math.floor(Number(value) || 0));
        const suggested = Math.max(990, Math.floor((integer * 0.8) / 1000) * 1000 + 990);
        return suggested.toLocaleString('es-CL');
    };

    const getSourcePrice = (selectorList, fallback = '') => {
        for (const selector of selectorList) {
            const element = document.querySelector(selector);
            if (element && String(element.value ?? '').trim() !== '') {
                return element.value;
            }
        }
        return fallback;
    };

    const suggestedBothPrice = () => {
        const physicalPrice = getSourcePrice([
            '#abp-create-physical-price',
            '[data-format-price="physical"]',
        ], state.formats?.physical?.price ?? '');
        const ebookPrice = getSourcePrice([
            '#abp-create-ebook-price',
            '[data-format-price="ebook"]',
        ], state.formats?.ebook?.price ?? '');
        const physical = parseSuggestedPriceBase(physicalPrice);
        const ebook = parseSuggestedPriceBase(ebookPrice);
        if (!physical || !ebook) {
            return '';
        }
        return formatSuggestedPrice(physical + ebook);
    };

    const refreshBothPriceHint = () => {
        const placeholder = suggestedBothPrice();
        document.querySelectorAll('#abp-create-both-price, [data-format-price="both"]').forEach((input) => {
            if (placeholder) {
                input.setAttribute('placeholder', placeholder);
            }
        });
    };

    const setBusy = (busy, message = '') => {
        root.classList.toggle('is-busy', busy);
        status.textContent = message;
        status.className = `abp-status${message ? ' is-visible' : ''}`;
    };

    const setError = (error) => {
        status.textContent = error?.message || String(error);
        status.className = 'abp-status is-visible is-error';
        root.classList.remove('is-busy');
    };

    const syncLegacyEditorState = () => {
        window.getCommerceStateFromForm = () => null;
        if (!window.bookState) return;
        const relation = state.relation || {};
        const productId = relation.ebook_product_id || relation.both_product_id || relation.physical_product_id || relation.parent_product_id || 0;
        window.bookState.commerce = window.bookState.commerce || {};
        window.bookState.commerce.relation = {
            product_id: productId,
            parent_product_id: relation.parent_product_id && relation.parent_product_id !== productId
                ? relation.parent_product_id
                : 0,
            product_mode: productId ? (relation.parent_product_id !== productId ? 'variation' : 'variable_parent') : 'none',
        };
    };

    const applyState = (nextState, message = '', options = {}) => {
        state = nextState || {};
        selectedProduct = null;
        searchResults = [];
        if (!options.keepEditing) {
            editing = false;
        }
        setBusy(false, message);
        syncLegacyEditorState();
        render();
    };

    const perform = async (message, operation, successMessage, options = {}) => {
        try {
            setBusy(true, message);
            const data = await operation();
            applyState(data.state, successMessage, options);
        } catch (error) {
            setError(error);
        }
    };

    const bookTitle = () => document.getElementById('book-title-input')?.value || state.product?.name || '';

    const modeSwitch = () => `
        <fieldset class="abp-mode-switch">
            <legend class="screen-reader-text">Selecciona cómo configurar el producto</legend>
            <label class="abp-mode-option${mode === 'link' ? ' is-active' : ''}">
                <input type="radio" name="abp-mode" value="link" ${mode === 'link' ? 'checked' : ''}>
                <span>Vincular producto</span>
            </label>
            <label class="abp-mode-option${mode === 'create' ? ' is-active' : ''}">
                <input type="radio" name="abp-mode" value="create" ${mode === 'create' ? 'checked' : ''}>
                <span>Crear producto</span>
            </label>
        </fieldset>`;

    const renderSearchResults = () => {
        const results = document.getElementById('abp-search-results');
        if (!results) return;
        if (!searchResults.length) {
            results.innerHTML = '<div class="abp-empty-result">No encontramos productos.</div>';
            results.hidden = false;
            return;
        }
        results.innerHTML = searchResults.map((product, index) => `
            <button type="button" class="abp-result${index === activeResult ? ' is-active' : ''}" role="option"
                data-result-index="${index}" aria-selected="${index === activeResult}" ${product.claimed ? 'disabled' : ''}>
                <span><strong>${escape(product.name)}</strong><small>#${product.id}${product.sku ? ` · SKU ${escape(product.sku)}` : ''}</small></span>
                <span class="abp-result-meta">${product.claimed ? 'Vinculado' : escape(product.type)}</span>
            </button>`).join('');
        results.hidden = false;
    };

    const linkPanel = () => {
        if (selectedProduct) {
            return `
                <div class="abp-card abp-selected-product">
                    <div><small>Producto seleccionado</small><strong>${escape(selectedProduct.name)}</strong><span>#${selectedProduct.id} · ${escape(selectedProduct.type)}</span></div>
                    <button type="button" class="abp-text-button" data-action="clear-selection">Cambiar</button>
                </div>
                <div class="abp-inline-field">
                    <label for="abp-link-ebook-price">Precio Ebook <span>(si aún no existe)</span></label>
                    <input id="abp-link-ebook-price" type="text" inputmode="decimal" placeholder="9.99">
                </div>
                <button type="button" class="abp-primary-button" data-action="link-selected">
                    ${selectedProduct.type === 'simple' ? 'Convertir y vincular' : 'Vincular producto'}
                </button>`;
        }

        return `
            <div class="abp-search-wrap">
                <label for="abp-product-search">Buscar producto</label>
                <input id="abp-product-search" type="search" autocomplete="off" placeholder="Nombre, SKU o ID"
                    role="combobox" aria-autocomplete="list" aria-controls="abp-search-results" aria-expanded="false">
                <div id="abp-search-results" class="abp-results" role="listbox" hidden></div>
            </div>`;
    };

    const createSlot = (format, label) => {
        const defaults = {
            physical: { checked: true, price: '19.99', stock: '0' },
            ebook: { checked: true, price: '9.99', stock: '' },
            both: { checked: false, price: '', stock: '0' },
        }[format];
        const stockInput = format === 'physical' || format === 'both'
            ? `<span>Inventario<input id="abp-create-${format}-stock" type="number" min="0" step="1" placeholder="0" value="${escape(defaults.stock)}"></span>`
            : '';
        const pricePlaceholder = format === 'both' ? (suggestedBothPrice() || '24.990') : (format === 'physical' ? '19.99' : '9.99');

        return `
            <article class="abp-create-slot">
                <span class="abp-slot-title"><input id="abp-create-${format}" type="checkbox"${defaults.checked ? ' checked' : ''}> ${label}</span>
                <span>Precio<input id="abp-create-${format}-price" type="text" inputmode="decimal" placeholder="${pricePlaceholder}" value="${escape(defaults.price)}"></span>
                ${stockInput}
            </article>`;
    };

    const createPanel = () => `
        <div class="abp-create-fields">
            <label>Título del producto<input id="abp-create-title" type="text" value="${escape(bookTitle())}"></label>
            <label>Descripción<textarea id="abp-create-description" rows="3"></textarea></label>
        </div>
        <div class="abp-variations" aria-label="Variaciones a crear">
            ${createSlot('both', 'Ambos')}
            ${createSlot('physical', 'Físico')}
            ${createSlot('ebook', 'Ebook')}
        </div>
        <button type="button" class="abp-primary-button" data-action="create-product">Crear producto variable</button>`;

    const editorHeader = () => `
        <div class="abp-edit-header">
            <div class="abp-create-fields">
                <label>Título del producto<input id="abp-product-title" type="text" value="${escape(state.product?.name || '')}"></label>
                <label>Descripción<textarea id="abp-product-description" rows="3">${escape(state.product?.description || '')}</textarea></label>
            </div>
            <div class="abp-edit-actions">
                <button type="button" class="abp-primary-button" data-action="save-product">Guardar datos del producto</button>
                <button type="button" class="abp-text-button" data-action="cancel-editing">Volver</button>
            </div>
        </div>`;

    const formatCard = (format, label, editable = false) => {
        const item = state.formats?.[format] || { linked: false };
        const hasStock = format === 'physical' || format === 'both';
        const priceValue = item.price === null || item.price === undefined ? '' : item.price;
        const stockValue = item.stock === null || item.stock === undefined ? '' : item.stock;

        if (!editable && item.linked) {
            return `
                <article class="abp-format-card is-linked">
                    <div class="abp-format-name"><strong>${label}</strong><span>${hasStock && item.stock !== null ? `Stock actual ${escape(item.stock)}` : 'Precio editable desde aquí'}</span></div>
                    <label>Precio<input data-format-price="${format}" type="text" inputmode="decimal" placeholder="${format === 'both' ? (suggestedBothPrice() || '24.990') : (format === 'physical' ? '19.99' : '9.99')}" value="${escape(priceValue)}"></label>
                    ${hasStock ? `<label>Inventario<input data-format-stock="${format}" type="number" min="0" step="1" placeholder="0" value="${escape(stockValue)}"></label>` : ''}
                    <div class="abp-format-actions">
                        ${actionButton('update-format', `Guardar ${label}`, 'save', format)}
                        ${actionButton('unlink-format', `Desvincular ${label}`, 'unlink', format, 'abp-icon-button is-danger')}
                    </div>
                </article>`;
        }

        if (!editable && item.available) {
            return `
                <article class="abp-format-card">
                    <div class="abp-format-name"><strong>${label}</strong><span>La variación existe, pero no está vinculada</span></div>
                    <div class="abp-format-actions">
                        ${actionButton('add-format', `Vincular ${label}`, 'link', format)}
                    </div>
                </article>`;
        }

        const action = editable ? 'update-format' : 'add-format';
        const buttonLabel = editable
            ? (item.linked || item.available ? `Actualizar ${label}` : `Crear ${label}`)
            : `Crear ${label}`;
        const pricePlaceholder = format === 'both'
            ? (suggestedBothPrice() || '24.990')
            : (format === 'physical' ? '19.99' : '9.99');

        return `
            <article class="abp-format-card">
                <div class="abp-format-name"><strong>${label}</strong><span>${editable && item.linked ? 'Variación vinculada y editable' : editable && item.available ? 'Variación disponible en el producto padre' : 'Añadir al producto vinculado'}</span></div>
                <label>Precio<input data-format-price="${format}" type="text" inputmode="decimal" placeholder="${pricePlaceholder}" value="${escape(priceValue)}"></label>
                ${hasStock ? `<label>Inventario<input data-format-stock="${format}" type="number" min="0" step="1" placeholder="0" value="${escape(stockValue)}"></label>` : ''}
                <div class="abp-format-actions">
                    ${actionButton(action, buttonLabel, editable ? 'save' : 'link', format)}
                    ${editable && item.linked ? actionButton('unlink-format', `Desvincular ${label}`, 'unlink', format, 'abp-icon-button is-danger') : ''}
                </div>
            </article>`;
    };

    const linkedPanel = () => `
        <div class="abp-product-summary">
            <div><small>Producto WooCommerce</small><strong>${escape(state.product?.name || '')}</strong><span>#${escape(state.product?.id || '')} · ${escape(state.product?.type || '')}</span></div>
            <div class="abp-summary-actions">
                ${actionButton('edit-product', 'Editar producto', 'save', '', 'abp-icon-button')}
                ${actionButton('unlink-product', 'Desvincular producto', 'unlink', '', 'abp-icon-button is-danger')}
            </div>
        </div>
        <h4 class="abp-section-label">Variaciones</h4>
        <div class="abp-variations">${formatOrder.map((format) => formatCard(format, formatLabels[format], false)).join('')}</div>`;

    const editPanel = () => `
        ${editorHeader()}
        <h4 class="abp-section-label">Variaciones</h4>
        <div class="abp-variations">${formatOrder.map((format) => formatCard(format, formatLabels[format], true)).join('')}</div>`;

    const selectResult = (index) => {
        const product = searchResults[index];
        if (!product || product.claimed) return;
        selectedProduct = product;
        render();
    };

    const runSearch = async (input) => {
        const term = input.value.trim();
        if (term.length < 2) {
            searchResults = [];
            const results = document.getElementById('abp-search-results');
            if (results) results.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            return;
        }
        try {
            const data = await api.search(root, term);
            searchResults = data.results || [];
            activeResult = -1;
            renderSearchResults();
            input.setAttribute('aria-expanded', 'true');
        } catch (error) {
            setError(error);
        }
    };

    const handleSearchKeys = (event) => {
        if (!searchResults.length) return;
        if (event.key === 'ArrowDown') activeResult = Math.min(activeResult + 1, searchResults.length - 1);
        else if (event.key === 'ArrowUp') activeResult = Math.max(activeResult - 1, 0);
        else if (event.key === 'Enter' && activeResult >= 0) {
            event.preventDefault();
            selectResult(activeResult);
            return;
        } else if (event.key === 'Escape') {
            const results = document.getElementById('abp-search-results');
            if (results) results.hidden = true;
            event.currentTarget.setAttribute('aria-expanded', 'false');
            return;
        } else {
            return;
        }
        event.preventDefault();
        renderSearchResults();
    };

    const getFormatPayload = (format) => ({
        price: content.querySelector(`[data-format-price="${format}"]`)?.value || '',
        stock: content.querySelector(`[data-format-stock="${format}"]`)?.value || '',
    });

    const render = () => {
        if (state.linked) {
            content.innerHTML = editing ? editPanel() : linkedPanel();
        } else {
            content.innerHTML = `${modeSwitch()}<div class="abp-mode-panel">${mode === 'link' ? linkPanel() : createPanel()}</div>`;
        }
        bindEvents();
    };

    const bindEvents = () => {
        content.querySelectorAll('input[name="abp-mode"]').forEach((input) => input.addEventListener('change', () => {
            mode = input.value;
            render();
        }));

        const search = document.getElementById('abp-product-search');
        search?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => runSearch(search), 300);
        });
        search?.addEventListener('keydown', handleSearchKeys);

        content.querySelectorAll('[data-result-index]').forEach((button) => button.addEventListener('click', () => {
            selectResult(Number(button.dataset.resultIndex));
        }));

        content.querySelector('[data-action="clear-selection"]')?.addEventListener('click', () => {
            selectedProduct = null;
            render();
        });

        content.querySelector('[data-action="link-selected"]')?.addEventListener('click', () => {
            if (!selectedProduct) return;
            const price = document.getElementById('abp-link-ebook-price')?.value.trim() || '';
            if (!price && selectedProduct.type === 'simple') {
                setError(new Error('Indica el precio del Ebook antes de convertir el producto.'));
                return;
            }
            if (selectedProduct.type === 'simple' && !window.confirm('Este producto se convertirá en variable y sus valores actuales pasarán a la variación Física. ¿Continuar?')) return;
            perform('Vinculando producto…', () => api.link(root, selectedProduct.id, price), 'Producto vinculado.');
        });

        content.querySelector('[data-action="create-product"]')?.addEventListener('click', () => {
            const data = {
                title: document.getElementById('abp-create-title')?.value || '',
                description: document.getElementById('abp-create-description')?.value || '',
                both: document.getElementById('abp-create-both')?.checked ? 1 : 0,
                both_price: document.getElementById('abp-create-both-price')?.value || '',
                both_stock: document.getElementById('abp-create-both-stock')?.value || '',
                physical: document.getElementById('abp-create-physical')?.checked ? 1 : 0,
                physical_price: document.getElementById('abp-create-physical-price')?.value || '',
                physical_stock: document.getElementById('abp-create-physical-stock')?.value || '',
                ebook: document.getElementById('abp-create-ebook')?.checked ? 1 : 0,
                ebook_price: document.getElementById('abp-create-ebook-price')?.value || '',
            };
            perform('Creando producto…', () => api.create(root, data), 'Producto creado y vinculado.');
        });

        content.querySelector('[data-action="edit-product"]')?.addEventListener('click', () => {
            editing = true;
            render();
        });

        content.querySelector('[data-action="cancel-editing"]')?.addEventListener('click', () => {
            editing = false;
            render();
        });

        content.querySelector('[data-action="save-product"]')?.addEventListener('click', () => {
            const data = {
                title: document.getElementById('abp-product-title')?.value || '',
                description: document.getElementById('abp-product-description')?.value || '',
            };
            perform('Guardando datos del producto…', () => api.update(root, data), 'Datos del producto actualizados.', { keepEditing: true });
        });

        content.querySelectorAll('[data-action="add-format"]').forEach((button) => button.addEventListener('click', () => {
            const format = button.dataset.format;
            const payload = getFormatPayload(format);
            perform(`Creando ${formatLabels[format]}…`, () => api.addFormat(root, format, payload.price, payload.stock), `${formatLabels[format]} vinculado.`, { keepEditing: true });
        }));

        content.querySelectorAll('[data-action="update-format"]').forEach((button) => button.addEventListener('click', () => {
            const format = button.dataset.format;
            const payload = getFormatPayload(format);
            perform(`Actualizando ${formatLabels[format]}…`, () => api.updateFormat(root, format, payload.price, payload.stock), `${formatLabels[format]} actualizado.`, { keepEditing: true });
        }));

        content.querySelectorAll('[data-action="unlink-format"]').forEach((button) => button.addEventListener('click', () => {
            const format = button.dataset.format;
            const label = formatLabels[format] || 'Formato';
            if (!window.confirm(`¿Desvincular ${label}? El producto de WooCommerce no será eliminado.`)) return;
            perform(`Desvinculando ${label}…`, () => api.unlinkFormat(root, format), `${label} desvinculado.`);
        }));

        content.querySelectorAll('#abp-create-physical-price, #abp-create-ebook-price, [data-format-price="physical"], [data-format-price="ebook"]').forEach((input) => {
            input.addEventListener('input', refreshBothPriceHint);
        });

        refreshBothPriceHint();

        content.querySelector('[data-action="unlink-product"]')?.addEventListener('click', () => {
            if (!window.confirm('¿Desvincular este producto del libro? El producto y sus variaciones no serán eliminados.')) return;
            perform('Desvinculando producto…', () => api.unlinkProduct(root), 'Producto desvinculado.');
        });
    };

    syncLegacyEditorState();
    render();
})();
