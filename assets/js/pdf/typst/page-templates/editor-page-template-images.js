// Central Image Setter for image slots created by Typst page templates.
(function () {
    'use strict';

    const state = {
        bound: false,
        filter: 'missing',
        collapsed: new Set(),
        initializedChapters: false,
        pendingChanges: 0,
        compiling: false,
        saveFailed: false,
        saveQueue: Promise.resolve()
    };

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getIndex() {
        return window.almadenImageSetterData?.buildIndex?.() || {
            chapters: [], rows: [], totals: { slots: 0, assigned: 0, missing: 0 }
        };
    }

    function getTemplates() {
        return window.almadenPageTemplateState?.getTemplates?.() || [];
    }

    function findSlot(instanceId, slotId) {
        const normalize = window.almadenPageTemplateState?.normalizeId || (value => String(value || ''));
        const template = getTemplates().find(entry => (
            window.almadenPageTemplateState.getInstanceId(entry) === normalize(instanceId)
        ));
        const slot = (template?.slots || []).find(entry => normalize(entry?.id) === normalize(slotId));
        return template && slot ? { template, slot } : null;
    }

    function showToast(message, icon = 'fa-solid fa-image') {
        if (typeof window.showToast === 'function') window.showToast(message, icon);
    }

    function ensureToolbarButton() {
        let button = document.getElementById('image-setter-action');
        if (button) return button;
        const controls = document.getElementById('pdf-text-bounds-toggle')?.parentElement;
        if (!controls) return null;

        button = document.createElement('button');
        button.id = 'image-setter-action';
        button.type = 'button';
        button.className = 'inline-flex h-7 items-center gap-1.5 rounded-md border border-[var(--border-color)] bg-[var(--bg-app)] px-2.5 text-[10px] font-bold text-[var(--text-main)] transition hover:border-amber-400 hover:bg-amber-50';
        button.title = 'Asignar imágenes a las plantillas del libro';
        button.innerHTML = `
            <i class="fa-solid fa-images" aria-hidden="true"></i>
            <span>SET IMAGES</span>
            <span data-image-setter-toolbar-count class="min-w-4 rounded bg-amber-100 px-1 text-center text-[9px] text-amber-800">0</span>
            <span data-image-setter-toolbar-pending class="hidden h-1.5 w-1.5 rounded-full bg-amber-500" aria-label="Cambios pendientes"></span>
        `;
        button.addEventListener('click', openModal);
        controls.appendChild(button);
        return button;
    }

    function updateToolbar() {
        const button = ensureToolbarButton();
        if (!button) return;
        const totals = getIndex().totals;
        const count = button.querySelector('[data-image-setter-toolbar-count]');
        if (count) count.textContent = String(totals.missing);
        button.querySelector('[data-image-setter-toolbar-pending]')?.classList.toggle('hidden', state.pendingChanges < 1);
        button.setAttribute('aria-label', `Asignar imágenes. ${totals.missing} pendientes`);
    }

    function ensureModal() {
        let modal = document.getElementById('page-template-images-modal');
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = 'page-template-images-modal';
        modal.className = 'fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/55 p-3 opacity-0 backdrop-blur-sm transition-opacity duration-150';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div data-image-setter-dialog role="dialog" aria-modal="true" aria-labelledby="image-setter-title" class="flex h-[88vh] w-full max-w-6xl scale-95 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-2xl transition-transform duration-150">
                <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-amber-600">Image Setter</p>
                        <h3 id="image-setter-title" class="mt-1 text-lg font-extrabold text-slate-900">Asignar imágenes</h3>
                        <p data-image-setter-summary class="mt-1 text-xs text-slate-500"></p>
                    </div>
                    <button type="button" data-image-setter-close class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Cerrar y actualizar PDF" title="Cerrar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </header>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-5 py-3">
                    <div class="flex items-center rounded-md border border-slate-200 bg-white p-0.5 text-xs font-semibold" role="group" aria-label="Filtrar imágenes">
                        <button type="button" data-image-setter-filter="missing" class="rounded-sm px-3 py-1.5">Pendientes</button>
                        <button type="button" data-image-setter-filter="all" class="rounded-sm px-3 py-1.5">Todas</button>
                        <button type="button" data-image-setter-filter="assigned" class="rounded-sm px-3 py-1.5">Asignadas</button>
                    </div>
                    <p class="text-[11px] text-slate-500">Preview ligero; el original se conserva para imprenta.</p>
                </div>
                <div id="page-template-images-list" class="flex-1 overflow-y-auto bg-white"></div>
                <footer class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
                    <p data-image-setter-status class="min-w-0 truncate text-xs text-slate-500">Sin cambios pendientes.</p>
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" data-image-setter-close class="rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">Cerrar</button>
                        <button type="button" data-image-setter-apply class="rounded-md bg-black px-3 py-2 text-xs font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-40">
                            <i class="fa-solid fa-rotate mr-1.5"></i>Actualizar PDF
                        </button>
                    </div>
                </footer>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }

    function renderRatio(row) {
        const width = Math.max(12, Math.min(64, Math.round(row.ratio.value * 36)));
        const label = `${row.ratio.width}:${row.ratio.height}`;
        return `<span class="block h-9 max-w-full border border-amber-700 bg-amber-400" style="width:${width}px" title="Proporción ${label}" aria-label="Proporción ${label}"></span>`;
    }

    function renderRow(row) {
        const preview = row.previewUrl
            ? `<img src="${escapeHtml(row.previewUrl)}" alt="" loading="lazy" decoding="async" class="h-12 w-12 object-cover">`
            : row.assigned
                ? '<i class="fa-solid fa-image text-slate-400" title="Imagen asignada sin preview"></i>'
                : '<span class="text-[9px] font-bold uppercase text-slate-400">Vacío</span>';
        const actionLabel = row.configured ? 'Reemplazar' : 'Subir';
        const statusLabel = row.assigned
            ? ''
            : row.configured
                ? 'No disponible para PDF'
                : 'Sin imagen';
        return `
            <div class="grid min-h-[72px] grid-cols-[52px_minmax(0,1fr)_60px_42px] items-center gap-3 border-t border-slate-100 px-5 py-2.5 sm:grid-cols-[56px_minmax(0,1fr)_72px_76px_126px]" data-image-setter-row="${escapeHtml(row.key)}">
                <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-slate-50">${preview}</div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 shrink-0 rounded-full ${row.assigned ? 'bg-emerald-500' : 'bg-amber-500'}"></span>
                        <p class="truncate text-sm font-bold text-slate-900">${escapeHtml(row.slotLabel)}</p>
                    </div>
                    <p class="mt-1 truncate text-[11px] ${row.assigned ? 'text-slate-500' : 'text-amber-700'}">${escapeHtml(row.templateLabel)}${statusLabel ? ` · ${escapeHtml(statusLabel)}` : ''}</p>
                </div>
                <button type="button" data-image-setter-page="${row.pageNumber}" class="justify-self-start rounded-md px-2 py-1 text-xs font-bold text-slate-700 hover:bg-slate-100" title="Ir a la página ${row.pageNumber}">Pág. ${row.pageNumber || '?'}</button>
                <div class="flex justify-center">${renderRatio(row)}</div>
                <div class="col-span-4 flex justify-end gap-1.5 sm:col-span-1">
                    <button type="button" data-image-setter-upload data-instance-id="${escapeHtml(row.instanceId)}" data-slot-id="${escapeHtml(row.slotId)}" class="inline-flex h-8 items-center gap-1.5 rounded-md bg-black px-2.5 text-[11px] font-semibold text-white hover:bg-neutral-800">
                        <i class="fa-solid fa-upload"></i><span>${actionLabel}</span>
                    </button>
                    <button type="button" data-image-setter-clear data-instance-id="${escapeHtml(row.instanceId)}" data-slot-id="${escapeHtml(row.slotId)}" class="${row.configured ? 'inline-flex' : 'hidden'} h-8 w-8 items-center justify-center rounded-md border border-rose-200 text-rose-600 hover:bg-rose-50" aria-label="Quitar imagen" title="Quitar imagen">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
        `;
    }

    function renderChapter(chapter) {
        const rows = window.almadenImageSetterData.filterRows(chapter.rows, state.filter);
        if (state.filter !== 'all' && !rows.length) return '';
        const assigned = chapter.rows.filter(row => row.assigned).length;
        const collapsed = state.collapsed.has(chapter.key);
        return `
            <section data-image-setter-chapter-section="${escapeHtml(chapter.key)}" class="border-b border-slate-200">
                <button type="button" data-image-setter-chapter="${escapeHtml(chapter.key)}" class="flex w-full items-center justify-between gap-4 bg-slate-50 px-5 py-3 text-left hover:bg-slate-100" aria-expanded="${collapsed ? 'false' : 'true'}">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-extrabold text-slate-900">${escapeHtml(chapter.title)}</span>
                        <span class="mt-0.5 block text-[11px] text-slate-500">${assigned}/${chapter.rows.length} asignadas</span>
                    </span>
                    <i class="fa-solid fa-chevron-${collapsed ? 'down' : 'up'} text-xs text-slate-400"></i>
                </button>
                <div class="${collapsed ? 'hidden' : ''}">${rows.map(renderRow).join('')}</div>
            </section>
        `;
    }

    function updatePendingStatus() {
        const modal = ensureModal();
        const status = modal.querySelector('[data-image-setter-status]');
        const apply = modal.querySelector('[data-image-setter-apply]');
        if (status) {
            status.textContent = state.compiling
                ? 'Actualizando composición Typst...'
                : state.saveFailed
                    ? 'No se pudieron guardar los cambios.'
                    : state.pendingChanges
                        ? `${state.pendingChanges} cambio${state.pendingChanges === 1 ? '' : 's'} pendiente${state.pendingChanges === 1 ? '' : 's'} de actualizar.`
                        : 'Sin cambios pendientes.';
        }
        if (apply) {
            apply.disabled = state.compiling || state.pendingChanges < 1 || state.saveFailed;
            apply.innerHTML = state.compiling
                ? '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i>Actualizando...'
                : '<i class="fa-solid fa-rotate mr-1.5"></i>Actualizar PDF';
        }
    }

    function render() {
        const modal = ensureModal();
        const index = getIndex();
        if (!state.initializedChapters) {
            index.chapters.filter(chapter => !chapter.rows.some(row => !row.assigned)).forEach(chapter => state.collapsed.add(chapter.key));
            state.initializedChapters = true;
        }
        const summary = modal.querySelector('[data-image-setter-summary]');
        if (summary) summary.textContent = `${index.totals.slots} espacios · ${index.totals.assigned} asignados · ${index.totals.missing} pendientes`;
        modal.querySelectorAll('[data-image-setter-filter]').forEach(button => {
            const active = button.dataset.imageSetterFilter === state.filter;
            button.classList.toggle('bg-black', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('text-slate-600', !active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        const list = modal.querySelector('#page-template-images-list');
        if (list) {
            const content = index.chapters.map(renderChapter).join('');
            list.innerHTML = content || '<div class="p-8 text-center text-sm text-slate-500">No hay espacios de imagen para este filtro.</div>';
        }
        updatePendingStatus();
        updateToolbar();
    }

    function queueSave(message) {
        state.pendingChanges += 1;
        state.saveFailed = false;
        render();
        state.saveQueue = state.saveQueue.catch(() => false).then(async () => {
            const saved = typeof window.savePDFSettings === 'function'
                ? await window.savePDFSettings(true, true)
                : true;
            if (!saved) {
                state.saveFailed = true;
                updatePendingStatus();
                throw new Error('No se pudieron guardar los cambios del Image Setter.');
            }
            showToast(message);
            return true;
        });
        return state.saveQueue;
    }

    function recordAssignmentTrace(event, target, row) {
        const template = target?.template || {};
        const slot = target?.slot || {};
        const trace = Array.isArray(window.almadenPageTemplateAssignmentTrace)
            ? window.almadenPageTemplateAssignmentTrace
            : [];
        trace.push({
            timestamp: new Date().toISOString(),
            event,
            instanceId: String(template.instance_id || template.id || ''),
            slotId: String(slot.id || ''),
            authoredPage: Number(template.page_number || 0),
            resolvedPage: Number(row?.pageNumber || template.resolved_page || 0),
            attachmentId: Number(slot.attachment_id || 0),
            url: String(slot.url || ''),
            originalUrl: String(slot.original_url || ''),
            previewUrl: String(slot.preview_url || '')
        });
        window.almadenPageTemplateAssignmentTrace = trace.slice(-50);
    }

    async function applyPendingChanges() {
        if (state.compiling) return false;
        try {
            await state.saveQueue;
            if (state.saveFailed || state.pendingChanges < 1) return !state.saveFailed;
            state.compiling = true;
            updatePendingStatus();
            if (typeof window.compilePDFPreview === 'function') await window.compilePDFPreview(true);
            state.pendingChanges = 0;
            state.compiling = false;
            render();
            showToast('PDF actualizado con las imágenes asignadas.', 'fa-solid fa-circle-check');
            return true;
        } catch (error) {
            state.compiling = false;
            state.saveFailed = true;
            updatePendingStatus();
            showToast(error?.message || 'No se pudo actualizar el PDF.', 'fa-solid fa-circle-exclamation');
            return false;
        }
    }

    async function assignImage(instanceId, slotId, row) {
        const target = findSlot(instanceId, slotId);
        if (!target) return;
        if (!window.AlmadenBooksterMediaPicker || !window.bookState?.bookId) {
            showToast('El selector de imágenes no está disponible.', 'fa-solid fa-circle-exclamation');
            return;
        }
        try {
            const attachment = await window.AlmadenBooksterMediaPicker.open({
                bookId: window.bookState.bookId,
                ajaxUrl: window.bookState.ajaxUrl,
                nonce: window.bookState.mediaPickerNonce,
                title: `Asignar imagen a ${row?.slotLabel || target.slot.id}`,
                buttonText: 'Usar esta imagen'
            });
            if (!attachment) return;
            const originalUrl = attachment.originalUrl || attachment.originalImageURL || attachment.url || '';
            const previewUrl = attachment.previewUrl || '';
            target.slot.attachment_id = Number(attachment.id) || 0;
            target.slot.url = originalUrl || previewUrl;
            target.slot.original_url = originalUrl || target.slot.url;
            target.slot.preview_url = previewUrl;
            window.almadenPageTemplateAssetDiagnostics = [];
            recordAssignmentTrace('assigned', target, row);
            window.bookState.settings.page_templates = getTemplates();
            queueSave(`Imagen asignada a ${row?.slotLabel || target.slot.id}.`);
        } catch (error) {
            showToast(error?.message || 'No se pudo seleccionar la imagen.', 'fa-solid fa-circle-exclamation');
        }
    }

    function clearImage(instanceId, slotId) {
        const target = findSlot(instanceId, slotId);
        if (!target) return;
        target.slot.attachment_id = 0;
        target.slot.url = '';
        target.slot.original_url = '';
        target.slot.preview_url = '';
        window.almadenPageTemplateAssetDiagnostics = [];
        recordAssignmentTrace('cleared', target, getRows().find(row => row.instanceId === instanceId && row.slotId === slotId));
        window.bookState.settings.page_templates = getTemplates();
        queueSave(`Imagen quitada de ${target.slot.label || target.slot.id}.`);
    }

    function hideModal() {
        const modal = ensureModal();
        const dialog = modal.querySelector('[data-image-setter-dialog]');
        modal.classList.add('opacity-0');
        dialog?.classList.remove('scale-100');
        dialog?.classList.add('scale-95');
        window.setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
        }, 150);
    }

    async function closeModal() {
        if (state.compiling) return;
        if (state.pendingChanges && !(await applyPendingChanges())) return;
        hideModal();
    }

    function openModal() {
        const modal = ensureModal();
        render();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            modal.querySelector('[data-image-setter-dialog]')?.classList.replace('scale-95', 'scale-100');
        });
    }

    function scrollToPage(pageNumber) {
        const findPage = () => document.querySelector(`#pdf-scroller [data-page-number="${Number(pageNumber)}"]`);
        const scroll = (attempt = 0) => {
            const page = findPage();
            if (page) {
                page.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            if (attempt < 12) window.setTimeout(() => scroll(attempt + 1), 120);
        };
        if (!findPage() && typeof window.setPdfPreviewMode === 'function') window.setPdfPreviewMode('full');
        scroll();
    }

    async function navigateToPage(pageNumber) {
        if (state.pendingChanges && !(await applyPendingChanges())) return;
        hideModal();
        scrollToPage(pageNumber);
    }

    function bind() {
        if (state.bound) return;
        state.bound = true;
        const modal = ensureModal();
        ensureToolbarButton();
        updateToolbar();
        modal.addEventListener('click', event => {
            if (event.target === modal || event.target.closest('[data-image-setter-close]')) {
                closeModal();
                return;
            }
            const filter = event.target.closest('[data-image-setter-filter]');
            if (filter) {
                state.filter = filter.dataset.imageSetterFilter || 'missing';
                render();
                return;
            }
            const chapter = event.target.closest('[data-image-setter-chapter]');
            if (chapter) {
                const key = chapter.dataset.imageSetterChapter;
                state.collapsed.has(key) ? state.collapsed.delete(key) : state.collapsed.add(key);
                render();
                return;
            }
            const page = event.target.closest('[data-image-setter-page]');
            if (page) {
                navigateToPage(page.dataset.imageSetterPage);
                return;
            }
            const upload = event.target.closest('[data-image-setter-upload]');
            if (upload) {
                const row = getIndex().rows.find(entry => entry.instanceId === upload.dataset.instanceId && entry.slotId === upload.dataset.slotId);
                assignImage(upload.dataset.instanceId, upload.dataset.slotId, row);
                return;
            }
            const clear = event.target.closest('[data-image-setter-clear]');
            if (clear) {
                clearImage(clear.dataset.instanceId, clear.dataset.slotId);
                return;
            }
            if (event.target.closest('[data-image-setter-apply]')) applyPendingChanges();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    }

    window.almadenPageTemplateImagesUI = {
        bind,
        openModal,
        closeModal,
        refresh: render,
        applyPendingChanges
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind, { once: true });
    } else {
        bind();
    }
})();
