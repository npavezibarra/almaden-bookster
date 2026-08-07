(function() {
    const getCommerceState = () => {
        const modeEl = document.getElementById('almaden-wc-relation-mode');
        const productIdEl = document.getElementById('almaden-wc-product-id');
        const parentIdEl = document.getElementById('almaden-wc-parent-product-id');
        const createEl = document.getElementById('almaden-wc-create-product');

        return {
            relation: {
                product_mode: modeEl ? modeEl.value : (bookState?.commerce?.relation?.product_mode || 'none'),
                product_id: productIdEl ? parseInt(productIdEl.value || '0', 10) || 0 : (bookState?.commerce?.relation?.product_id || 0),
                parent_product_id: parentIdEl ? parseInt(parentIdEl.value || '0', 10) || 0 : (bookState?.commerce?.relation?.parent_product_id || 0),
            },
            create_wc_product: createEl ? (createEl.checked ? 1 : 0) : 0,
        };
    };

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    };

    const formatModeLabel = (mode) => {
        const value = String(mode || 'none');
        if (value === 'simple') return 'PRODUCTO SIMPLE';
        if (value === 'variable_parent') return 'VARIABLE PADRE';
        if (value === 'variation') return 'VARIACIÓN EBOOK';
        if (value === 'none') return 'SIN VÍNCULO';
        return value.toUpperCase();
    };

    const syncCommerceStatus = () => {
        const state = getCommerceState();
        setText('commerce-current-mode-label', formatModeLabel(state.relation.product_mode));
        setText('commerce-current-product-id', state.relation.product_id > 0 ? String(state.relation.product_id) : '-');
        setText('commerce-current-parent-id', state.relation.parent_product_id > 0 ? String(state.relation.parent_product_id) : '-');

        if (!window.bookState) {
            return;
        }
        window.bookState.commerce = window.bookState.commerce || {};
        window.bookState.commerce.relation = state.relation;
        window.bookState.commerce.create_wc_product = state.create_wc_product;
    };

    window.populateCommerceForm = function() {
        if (!window.bookState || !window.bookState.commerce) {
            return;
        }

        const relation = window.bookState.commerce.relation || {};
        const modeEl = document.getElementById('almaden-wc-relation-mode');
        const productIdEl = document.getElementById('almaden-wc-product-id');
        const parentIdEl = document.getElementById('almaden-wc-parent-product-id');
        const createEl = document.getElementById('almaden-wc-create-product');

        if (modeEl) modeEl.value = relation.product_mode || 'none';
        if (productIdEl) productIdEl.value = relation.product_id || 0;
        if (parentIdEl) parentIdEl.value = relation.parent_product_id || 0;
        if (createEl) createEl.checked = Boolean(window.bookState.commerce.create_wc_product);

        syncCommerceStatus();
    };

    window.getCommerceStateFromForm = function() {
        return getCommerceState();
    };

    document.addEventListener('DOMContentLoaded', () => {
        const inputs = [
            document.getElementById('almaden-wc-relation-mode'),
            document.getElementById('almaden-wc-product-id'),
            document.getElementById('almaden-wc-parent-product-id'),
            document.getElementById('almaden-wc-create-product'),
        ].filter(Boolean);

        inputs.forEach((input) => {
            input.addEventListener('change', syncCommerceStatus);
            input.addEventListener('input', syncCommerceStatus);
        });

        syncCommerceStatus();
    });
})();
