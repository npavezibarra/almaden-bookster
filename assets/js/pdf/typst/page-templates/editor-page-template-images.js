// Slot image manager for Typst page templates.
(function () {
    let modalBound = false;
    let mediaFrame = null;

    function getTemplates() {
        return window.almadenPageTemplateState?.getTemplates?.() || [];
    }

    function normalizeId(value) {
        return String(value || '').toLowerCase().replace(/[^a-z0-9_-]/g, '');
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getTemplateDefinition(templateId) {
        const registry = window.almadenPageTemplateRegistry || {};
        return registry[normalizeId(templateId)] || null;
    }

    function getTemplateLabel(templateId) {
        const definition = getTemplateDefinition(templateId);
        return definition?.label || templateId || 'Plantilla';
    }

    function makeSlotAnchor(instanceId, slotId) {
        return `almaden-template-slot-${normalizeId(instanceId)}-${normalizeId(slotId)}`;
    }

    function getAllSlots() {
        const templates = window.almadenPageTemplateState?.getAppliedTemplates?.() || getTemplates();
        return templates.flatMap(template => {
            const slots = Array.isArray(template?.slots) ? template.slots : [];
            const instanceId = window.almadenPageTemplateState.getInstanceId(template);
            return slots.map(slot => ({
                page_number: window.almadenPageTemplateState.getResolvedPage(template),
                template_id: template?.template_id || '',
                template_label: getTemplateLabel(template?.template_id || ''),
                instance_id: instanceId,
                slot_id: slot?.id || '',
                slot_label: slot?.label || slot?.id || 'Slot',
                slot_kind: slot?.kind || 'image',
                attachment_id: Number(slot?.attachment_id) || 0,
                url: slot?.preview_url || slot?.url || slot?.original_url || '',
                anchor_id: makeSlotAnchor(instanceId, slot?.id || '')
            }));
        });
    }

    function getSlotPreview(slot) {
        return slot.url || '';
    }

    function renderRows() {
        const list = document.getElementById('page-template-images-list');
        if (!list) return;

        const slots = getAllSlots();
        if (!slots.length) {
            list.innerHTML = `
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                    No hay rectángulos configurados todavía.
                </div>
            `;
            return;
        }

        list.innerHTML = slots.map(slot => {
            const preview = getSlotPreview(slot);
            return `
                <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-600">Página ${escapeHtml(slot.page_number)}</p>
                        <p class="mt-1 text-base font-extrabold text-slate-900">${escapeHtml(slot.slot_label)}</p>
                        <p class="mt-1 font-mono text-[11px] text-slate-500 break-all">${escapeHtml(slot.anchor_id)}</p>
                        <p class="mt-1 text-xs text-slate-500">Plantilla: ${escapeHtml(slot.template_label)}</p>
                    </div>
                    <div class="flex flex-col items-start gap-2 sm:items-end">
                        <div class="flex items-center gap-3">
                            <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                ${preview ? `<img src="${escapeHtml(preview)}" alt="" class="h-full w-full object-cover">` : '<span class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Vacío</span>'}
                            </div>
                            <div class="text-right">
                                <p class="text-[11px] font-semibold text-slate-500">${escapeHtml(slot.slot_kind)}</p>
                                <p class="text-[11px] text-slate-500">ID: ${escapeHtml(slot.slot_id)}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="rounded-lg bg-black px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-neutral-800" data-page-template-slot-upload data-instance-id="${slot.instance_id}" data-slot-id="${slot.slot_id}">
                                Upload Image
                            </button>
                            <button type="button" class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50" data-page-template-slot-clear data-instance-id="${slot.instance_id}" data-slot-id="${slot.slot_id}">
                                Quitar
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function ensureModal() {
        let modal = document.getElementById('page-template-images-modal');
        if (modal) return modal;

        modal = document.createElement('div');
        modal.id = 'page-template-images-modal';
        modal.className = 'fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/55 p-4 opacity-0 backdrop-blur-sm transition-opacity duration-200';
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML = `
            <div data-page-template-images-dialog class="flex h-[80vh] w-full max-w-4xl scale-95 flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl transition-transform duration-200">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-600">Imágenes de plantillas</p>
                        <h3 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">Slots con imagen</h3>
                    </div>
                    <button type="button" data-page-template-images-close class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto bg-slate-50 p-6">
                    <div id="page-template-images-list" class="space-y-3"></div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button type="button" data-page-template-images-close class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-200">Cerrar</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        return modal;
    }

    function closeModal() {
        const modal = document.getElementById('page-template-images-modal');
        const dialog = modal?.querySelector('[data-page-template-images-dialog]');
        if (!modal || !dialog) return;
        modal.classList.add('opacity-0');
        dialog.classList.remove('scale-100');
        dialog.classList.add('scale-95');
        window.setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
        }, 180);
    }

    function openMediaUploader(rowData) {
        const applySelection = (attachment) => {
            if (!attachment) return;
            const templates = getTemplates();
            const templateIndex = templates.findIndex(template => (
                window.almadenPageTemplateState.getInstanceId(template) === normalizeId(rowData.instance_id)
            ));
            if (templateIndex < 0) return;

            const slot = (templates[templateIndex].slots || []).find(entry => normalizeId(entry?.id) === normalizeId(rowData.slot_id));
            if (!slot) return;

            const originalUrl = attachment.originalUrl || attachment.originalImageURL || attachment.url || '';
            const previewUrl = attachment.previewUrl || attachment.sizes?.medium?.url || attachment.sizes?.thumbnail?.url || originalUrl;
            slot.attachment_id = Number(attachment.id) || 0;
            slot.url = originalUrl || previewUrl || '';
            slot.original_url = originalUrl || slot.url;
            slot.preview_url = previewUrl || slot.url;
            window.bookState.settings.page_templates = templates;

            saveAndRefresh(`Imagen asignada a ${rowData.slot_id}.`);
        };

        if (window.AlmadenBooksterMediaPicker && bookState && bookState.bookId && bookState.mediaPickerNonce) {
            window.AlmadenBooksterMediaPicker.open({
                bookId: bookState.bookId,
                ajaxUrl: bookState.ajaxUrl,
                nonce: bookState.mediaPickerNonce,
                title: `Asignar imagen a ${rowData.slot_label}`,
                buttonText: 'Usar esta imagen'
            }).then(applySelection).catch(() => {});
            return;
        }

        if (typeof wp === 'undefined' || !wp.media) {
            if (typeof window.showToast === 'function') {
                window.showToast('La biblioteca multimedia no está disponible.', 'fa-solid fa-circle-exclamation');
            }
            return;
        }

        window.bookState = window.bookState || {};
        window.bookState.settings = window.bookState.settings || {};

        if (mediaFrame) {
            mediaFrame.off('select');
        } else {
            mediaFrame = wp.media({
                title: `Asignar imagen a ${rowData.slot_label}`,
                button: { text: 'Usar esta imagen' },
                multiple: false,
                library: { type: 'image' }
            });
        }

        mediaFrame.on('select', function () {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            applySelection(attachment);
        });

        mediaFrame.open();
    }

    function clearSlotImage(rowData) {
        window.bookState = window.bookState || {};
        window.bookState.settings = window.bookState.settings || {};
        const templates = getTemplates();
        const templateIndex = templates.findIndex(template => (
            window.almadenPageTemplateState.getInstanceId(template) === normalizeId(rowData.instance_id)
        ));
        if (templateIndex < 0) return;

        const slot = (templates[templateIndex].slots || []).find(entry => normalizeId(entry?.id) === normalizeId(rowData.slot_id));
        if (!slot) return;

        slot.attachment_id = 0;
        slot.url = '';
        slot.original_url = '';
        slot.preview_url = '';
        window.bookState.settings.page_templates = templates;

        saveAndRefresh(`Imagen quitada de ${rowData.slot_id}.`);
    }

    async function saveAndRefresh(message) {
        const saved = typeof window.savePDFSettings === 'function'
            ? await window.savePDFSettings(true, true)
            : true;
        if (!saved) {
            if (typeof window.showToast === 'function') {
                window.showToast('No se pudieron guardar los cambios del slot.', 'fa-solid fa-circle-exclamation');
            }
            return;
        }

        renderRows();
        if (typeof window.compilePDFPreview === 'function') {
            await window.compilePDFPreview(true);
        }
        if (message && typeof window.showToast === 'function') {
            window.showToast(message, 'fa-solid fa-image');
        }
    }

    function openModal() {
        const modal = ensureModal();
        const dialog = modal?.querySelector('[data-page-template-images-dialog]');
        if (!modal || !dialog) return;

        renderRows();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            dialog.classList.remove('scale-95');
            dialog.classList.add('scale-100');
        });
    }

    function bind() {
        if (modalBound) return;
        modalBound = true;
        const modal = ensureModal();
        modal.addEventListener('click', event => {
            if (event.target === modal || event.target.closest('[data-page-template-images-close]')) {
                closeModal();
                return;
            }

            const uploadBtn = event.target.closest('[data-page-template-slot-upload]');
            if (uploadBtn) {
                openMediaUploader({
                    instance_id: uploadBtn.dataset.instanceId,
                    slot_id: uploadBtn.dataset.slotId,
                    slot_label: uploadBtn.closest('div')?.querySelector('.text-base')?.textContent || uploadBtn.dataset.slotId
                });
                return;
            }

            const clearBtn = event.target.closest('[data-page-template-slot-clear]');
            if (clearBtn) {
                clearSlotImage({
                    instance_id: clearBtn.dataset.instanceId,
                    slot_id: clearBtn.dataset.slotId
                });
            }
        });

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    }

    window.almadenPageTemplateImagesUI = { bind, openModal, closeModal, refresh: renderRows };
})();
