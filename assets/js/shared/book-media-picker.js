window.AlmadenBooksterMediaPicker = window.AlmadenBooksterMediaPicker || (function () {
    'use strict';

    const state = {
        modal: null,
        resolve: null,
        reject: null,
        loading: false,
        items: [],
        filteredItems: [],
        query: '',
        config: null,
        uploadQueue: [],
    };

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function ensureModal() {
        if (state.modal) return state.modal;

        const modal = document.createElement('div');
        modal.id = 'almaden-book-media-picker-modal';
        modal.className = 'fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/60 p-4 opacity-0 backdrop-blur-sm transition-opacity duration-200';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div class="flex h-[86vh] w-full max-w-6xl scale-95 flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl transition-transform duration-200">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">Media scoped</p>
                        <h3 data-book-media-title class="mt-1 truncate text-2xl font-extrabold tracking-tight text-slate-900">Selector de imágenes</h3>
                        <p data-book-media-folder class="mt-1 text-sm text-slate-500">Carpeta del libro</p>
                    </div>
                    <button type="button" data-book-media-close class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-slate-50 px-6 py-4">
                    <label class="flex min-w-[240px] flex-1 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 shadow-sm">
                        <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
                        <input data-book-media-search type="search" class="w-full border-0 bg-transparent p-0 text-sm outline-none focus:ring-0" placeholder="Buscar imagen..." />
                    </label>
                    <input data-book-media-file type="file" class="hidden" accept="image/*" multiple />
                    <button type="button" data-book-media-upload class="inline-flex items-center gap-2 rounded-2xl bg-black px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-800">
                        <i class="fa-solid fa-upload"></i>
                        <span>Subir al libro</span>
                    </button>
                </div>

                <div class="flex-1 overflow-hidden bg-slate-50">
                    <div class="flex h-full flex-col lg:flex-row">
                        <div class="flex-1 overflow-y-auto p-5">
                            <div data-book-media-status class="hidden rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900"></div>
                            <div data-book-media-loading class="hidden rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">Cargando imágenes del libro...</div>
                            <div data-book-media-empty class="hidden rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">No hay imágenes en esta carpeta todavía.</div>
                            <div data-book-media-grid class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4"></div>
                        </div>
                        <div class="w-full border-t border-slate-200 bg-white px-5 py-5 lg:w-80 lg:border-t-0 lg:border-l">
                            <div class="sticky top-0 space-y-4">
                                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">Selección</p>
                                    <div data-book-media-preview-empty class="mt-3 rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500">
                                        Haz clic en una imagen para seleccionarla.
                                    </div>
                                    <div data-book-media-preview class="hidden space-y-3">
                                        <img data-book-media-preview-image alt="" class="h-48 w-full rounded-2xl border border-slate-200 bg-white object-contain" />
                                        <div class="space-y-1 text-sm">
                                            <p data-book-media-preview-title class="font-semibold text-slate-900"></p>
                                            <p data-book-media-preview-meta class="text-xs text-slate-500"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" data-book-media-select class="flex-1 rounded-2xl bg-black px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50">Usar imagen</button>
                                    <button type="button" data-book-media-cancel class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        state.modal = modal;

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                close(null);
            }
        });

        modal.querySelector('[data-book-media-close]')?.addEventListener('click', () => close(null));
        modal.querySelector('[data-book-media-cancel]')?.addEventListener('click', () => close(null));
        modal.querySelector('[data-book-media-search]')?.addEventListener('input', (event) => {
            state.query = String(event.target.value || '').trim().toLowerCase();
            renderItems();
        });
        modal.querySelector('[data-book-media-upload]')?.addEventListener('click', () => {
            modal.querySelector('[data-book-media-file]')?.click();
        });
        modal.querySelector('[data-book-media-file]')?.addEventListener('change', (event) => {
            const files = Array.from(event.target.files || []);
            event.target.value = '';
            if (!files.length) return;
            uploadFiles(files);
        });
        modal.querySelector('[data-book-media-select]')?.addEventListener('click', () => {
            if (state.selectedItem) {
                close(state.selectedItem);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && state.modal && !state.modal.classList.contains('hidden')) {
                close(null);
            }
        });

        return modal;
    }

    function getConfig() {
        return state.config || {};
    }

    function setLoading(isLoading) {
        state.loading = !!isLoading;
        const modal = ensureModal();
        modal.querySelector('[data-book-media-loading]')?.classList.toggle('hidden', !state.loading);
        modal.querySelector('[data-book-media-grid]')?.classList.toggle('opacity-50', state.loading);
    }

    function getSearchableText(item) {
        return [
            item.title,
            item.filename,
            item.url,
            item.originalUrl,
            item.previewUrl
        ].filter(Boolean).join(' ').toLowerCase();
    }

    function matchesQuery(item) {
        if (!state.query) return true;
        return getSearchableText(item).includes(state.query);
    }

    function renderPreview(item) {
        const modal = ensureModal();
        const preview = modal.querySelector('[data-book-media-preview]');
        const empty = modal.querySelector('[data-book-media-preview-empty]');
        const image = modal.querySelector('[data-book-media-preview-image]');
        const title = modal.querySelector('[data-book-media-preview-title]');
        const meta = modal.querySelector('[data-book-media-preview-meta]');
        const selectButton = modal.querySelector('[data-book-media-select]');
        if (!preview || !empty || !image || !title || !meta) return;

        if (!item) {
            preview.classList.add('hidden');
            empty.classList.remove('hidden');
            if (selectButton) {
                selectButton.disabled = true;
            }
            return;
        }

        empty.classList.add('hidden');
        preview.classList.remove('hidden');
        image.src = item.previewUrl || item.originalUrl || item.url || '';
        image.alt = item.title || 'Imagen';
        title.textContent = item.title || 'Imagen';
        meta.textContent = `${item.width || '?'} x ${item.height || '?'} px`;
        if (selectButton) {
            selectButton.disabled = false;
        }
    }

    function renderItems() {
        const modal = ensureModal();
        const grid = modal.querySelector('[data-book-media-grid]');
        const empty = modal.querySelector('[data-book-media-empty]');
        if (!grid || !empty) return;

        const items = state.items.filter(matchesQuery);
        state.filteredItems = items;
        if (!items.length) {
            grid.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }

        empty.classList.add('hidden');
        grid.innerHTML = items.map((item) => {
            const active = state.selectedItem && Number(state.selectedItem.id) === Number(item.id);
            const src = item.previewUrl || item.originalUrl || item.url || '';
            return `
                <button type="button" data-book-media-item="${escapeHtml(item.id)}" class="group overflow-hidden rounded-3xl border ${active ? 'border-black ring-2 ring-black/10' : 'border-slate-200'} bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                    <div class="aspect-square bg-slate-100">
                        ${src ? `<img src="${escapeHtml(src)}" alt="" class="h-full w-full object-cover">` : '<div class="flex h-full items-center justify-center text-xs font-semibold text-slate-400">Sin vista previa</div>'}
                    </div>
                    <div class="space-y-1 p-3">
                        <p class="truncate text-sm font-semibold text-slate-900">${escapeHtml(item.title || 'Imagen')}</p>
                        <p class="truncate text-[11px] text-slate-500">${escapeHtml(item.filename || item.url || '')}</p>
                    </div>
                </button>
            `;
        }).join('');

        grid.querySelectorAll('[data-book-media-item]').forEach((button) => {
            button.addEventListener('click', () => {
                const itemId = Number(button.getAttribute('data-book-media-item') || 0);
                const item = state.items.find((entry) => Number(entry.id) === itemId) || null;
                state.selectedItem = item;
                renderPreview(item);
                renderItems();
            });
        });
    }

    function showStatus(message) {
        const modal = ensureModal();
        const status = modal.querySelector('[data-book-media-status]');
        if (!status) return;
        if (!message) {
            status.classList.add('hidden');
            status.textContent = '';
            return;
        }
        status.textContent = message;
        status.classList.remove('hidden');
    }

    function normalizeConfig(options = {}) {
        return {
            bookId: Number(options.bookId || 0),
            ajaxUrl: String(options.ajaxUrl || ''),
            nonce: String(options.nonce || ''),
            title: String(options.title || 'Seleccionar imagen'),
            buttonText: String(options.buttonText || 'Usar imagen'),
        };
    }

    function validateConfig(config) {
        if (!config.bookId || !config.ajaxUrl || !config.nonce) {
            throw new Error('Selector de imágenes no configurado.');
        }
    }

    function fetchJson(formData, requestConfig = null) {
        const config = requestConfig || getConfig();
        return fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData,
            headers: { Accept: 'application/json' },
        }).then((response) => response.text().then((body) => {
            if (!body.trim()) {
                throw new Error(`El servidor no respondió (HTTP ${response.status}). Revisa el registro de PHP.`);
            }
            try {
                return JSON.parse(body);
            } catch (error) {
                console.error('Respuesta inválida del selector de imágenes:', body.slice(0, 500));
                throw new Error(`El servidor devolvió una respuesta inválida (HTTP ${response.status}).`);
            }
        }));
    }

    function list(options = {}) {
        const config = normalizeConfig(options);
        validateConfig(config);
        const payload = new FormData();
        payload.append('action', 'almaden_bookster_book_media_list');
        payload.append('book_id', String(config.bookId));
        payload.append('nonce', config.nonce);
        return fetchJson(payload, config).then((result) => {
            if (!result || !result.success || !result.data) {
                throw new Error((result && result.data && result.data.message) || 'No se pudo cargar el media del libro.');
            }
            return result.data;
        });
    }

    function upload(options = {}, file) {
        const config = normalizeConfig(options);
        validateConfig(config);
        if (!file) throw new Error('No se recibió archivo.');
        const payload = new FormData();
        payload.append('action', 'almaden_bookster_book_media_upload');
        payload.append('book_id', String(config.bookId));
        payload.append('nonce', config.nonce);
        payload.append('file', file);
        return fetchJson(payload, config).then((result) => {
            if (!result || !result.success || !result.data) {
                throw new Error((result && result.data && result.data.message) || 'No se pudo subir la imagen.');
            }
            return result.data;
        });
    }

    function loadItems() {
        const config = getConfig();
        const payload = new FormData();
        payload.append('action', 'almaden_bookster_book_media_list');
        payload.append('book_id', String(config.bookId || 0));
        payload.append('nonce', String(config.nonce || ''));

        setLoading(true);
        showStatus('');

        return fetchJson(payload)
            .then((result) => {
                if (!result || !result.success || !result.data) {
                    throw new Error((result && result.data && result.data.message) || 'No se pudo cargar el media del libro.');
                }
                state.items = Array.isArray(result.data.attachments) ? result.data.attachments : [];
                state.filteredItems = state.items.slice();
                renderItems();
                const modal = ensureModal();
                const folder = modal.querySelector('[data-book-media-folder]');
                if (folder) {
                    folder.textContent = result.data.folder ? `Carpeta: ${result.data.folder}` : 'Carpeta: libro actual';
                }
                setLoading(false);
                return state.items;
            })
            .catch((error) => {
                setLoading(false);
                showStatus(error instanceof Error ? error.message : String(error));
                throw error;
            });
    }

    function uploadFiles(files) {
        const config = getConfig();
        const queue = Array.from(files || []).filter(Boolean);
        if (!queue.length) return Promise.resolve([]);

        const uploadNext = () => {
            if (!queue.length) {
                return Promise.resolve([]);
            }

            const file = queue.shift();
            const payload = new FormData();
            payload.append('action', 'almaden_bookster_book_media_upload');
            payload.append('book_id', String(config.bookId || 0));
            payload.append('nonce', String(config.nonce || ''));
            payload.append('file', file);

            setLoading(true);
            return fetchJson(payload)
                .then((result) => {
                    if (!result || !result.success || !result.data) {
                        throw new Error((result && result.data && result.data.message) || 'No se pudo subir la imagen.');
                    }
                    state.selectedItem = result.data;
                    return loadItems().then(() => result.data);
                })
                .then((data) => uploadNext().then((rest) => [data].concat(rest)))
                .catch((error) => {
                    showStatus(error instanceof Error ? error.message : String(error));
                    throw error;
                });
        };

        return uploadNext();
    }

    function open(options = {}) {
        const config = normalizeConfig(options);
        validateConfig(config);

        state.config = config;
        state.items = [];
        state.filteredItems = [];
        state.query = '';
        state.selectedItem = null;

        const modal = ensureModal();
        modal.querySelector('[data-book-media-title]').textContent = config.title;
        const selectButton = modal.querySelector('[data-book-media-select]');
        if (selectButton) {
            selectButton.textContent = config.buttonText;
            selectButton.disabled = true;
        }
        const search = modal.querySelector('[data-book-media-search]');
        if (search) {
            search.value = '';
        }
        renderPreview(null);
        renderItems();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('div')?.classList.remove('scale-95');
            modal.querySelector('div')?.classList.add('scale-100');
        });

        return loadItems();
    }

    function close(selectedItem) {
        const modal = state.modal;
        if (!modal) return;

        modal.classList.add('opacity-0');
        modal.querySelector('div')?.classList.remove('scale-100');
        modal.querySelector('div')?.classList.add('scale-95');
        window.setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
        }, 180);

        const selectButton = modal.querySelector('[data-book-media-select]');
        if (selectButton) {
            selectButton.disabled = true;
        }

        const resolve = state.resolve;
        state.resolve = null;
        state.reject = null;

        if (resolve) {
            resolve(selectedItem || null);
        }
    }

    function openWithPromise(options = {}) {
        return new Promise((resolve, reject) => {
            state.resolve = resolve;
            state.reject = reject;
            open(options).catch((error) => {
                state.resolve = null;
                state.reject = null;
                reject(error);
            });
        });
    }

    return {
        open: openWithPromise,
        close,
        list,
        upload,
        isReady: function (bookId) {
            return !!(bookId && typeof bookId === 'number');
        }
    };
})();
