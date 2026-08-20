(function () {
    'use strict';

    const request = async (root, action, payload = {}) => {
        const body = new URLSearchParams({
            action,
            book_id: root.dataset.bookId || '',
            nonce: root.dataset.nonce || '',
        });
        Object.entries(payload).forEach(([key, value]) => {
            if (value !== undefined && value !== null) body.append(key, String(value));
        });

        const response = await fetch(root.dataset.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString(),
        });
        const json = await response.json().catch(() => null);
        if (!response.ok || !json || !json.success) {
            const message = json?.data?.message || json?.data || 'No se pudo completar la operación.';
            throw new Error(String(message));
        }
        return json.data;
    };

    window.AlmadenBookProductAPI = {
        search: (root, term) => request(root, 'almaden_book_product_search', { term }),
        getState: (root) => request(root, 'almaden_book_product_state'),
        link: (root, productId, ebookPrice) => request(root, 'almaden_book_product_link', {
            product_id: productId,
            ebook_price: ebookPrice,
        }),
        create: (root, data) => request(root, 'almaden_book_product_create', data),
        update: (root, data) => request(root, 'almaden_book_product_update', data),
        updateStatus: (root, status) => request(root, 'almaden_book_product_update_status', { status }),
        addFormat: (root, format, price, stock = '') => request(root, 'almaden_book_product_add_format', {
            format, price, stock,
        }),
        updateFormat: (root, format, price, stock = '') => request(root, 'almaden_book_product_update_format', {
            format, price, stock,
        }),
        unlinkFormat: (root, format) => request(root, 'almaden_book_product_unlink_format', { format }),
        unlinkProduct: (root) => request(root, 'almaden_book_product_unlink_product'),
		saveSamples: (root, chapterIds) => request(root, 'almaden_book_product_save_samples', {
			chapter_ids: JSON.stringify(chapterIds),
		}),
    };
})();
