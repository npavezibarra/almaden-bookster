// Pure rendering helpers for the page template picker.
(function () {
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

    function renderPreview(definition) {
        const preview = definition?.preview || {};
        const type = String(preview.type || 'split');
        const canvas = escapeHtml(preview.canvas || '#ffffff');
        const frame = escapeHtml(preview.frame || '#cbd5e1');

        if ('full' === type) {
            const margin = escapeHtml(preview.margin || '#f3f4f6');
            const fill = escapeHtml(preview.fill || '#f59e0b');
            return `
                <div class="flex h-full w-full items-center justify-center rounded-xl border border-slate-200 p-2" style="background:${canvas};">
                    <div class="flex h-full w-full items-center justify-center rounded-lg border-2" style="border-color:${frame}; background:${margin};">
                        <div class="h-[84%] w-[84%] rounded-md border" style="border-color:${frame}; background:${fill};"></div>
                    </div>
                </div>
            `;
        }

        if ('upper-bottom-split' === type) {
            const fill = escapeHtml(preview.fill || '#f59e0b');
            const text = escapeHtml(preview.text || '#cbd5e1');
            return `
                <div class="flex h-full w-full items-center justify-center rounded-xl border border-slate-200 p-2" style="background:${canvas};">
                    <div class="grid h-full w-full grid-cols-[1fr_1fr_0.85fr] grid-rows-[1.1fr_0.9fr] gap-1 rounded-lg border p-1" style="border-color:${frame}; background:${canvas};">
                        <span class="col-span-2 row-span-1 rounded-sm" style="background:${fill};"></span>
                        <span class="col-start-3 row-span-2 rounded-sm" style="background:${text};"></span>
                        <span class="col-start-1 row-start-2 rounded-sm" style="background:${text};"></span>
                        <span class="col-start-2 row-start-2 rounded-sm" style="background:${text};"></span>
                    </div>
                </div>
            `;
        }

        const left = escapeHtml(preview.left || '#cbd5e1');
        const right = escapeHtml(preview.right || '#f59e0b');
        return `
            <div class="flex h-full w-full items-center justify-center rounded-xl border border-slate-200 p-2" style="background:${canvas};">
                <div class="grid h-full w-full grid-cols-[0.44fr_0.56fr] gap-1 rounded-lg border p-1" style="border-color:${frame}; background:${canvas};">
                    <span class="rounded-sm" style="background:${left};"></span>
                    <span class="rounded-sm" style="background:${right};"></span>
                </div>
            </div>
        `;
    }

    function render(selectedTemplateId) {
        const container = document.getElementById('page-template-options');
        if (!container) return;

        const options = Object.values(window.almadenPageTemplateRegistry || {});
        if (!options.length) {
            container.innerHTML = `
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                    No hay plantillas registradas.
                </div>
            `;
            return;
        }

        container.innerHTML = options.map(definition => {
            const templateId = normalizeId(definition?.id || '');
            const label = definition?.label || templateId || 'Plantilla';
            const selected = templateId === normalizeId(selectedTemplateId);
            return `
                <button type="button" data-page-template-option="${escapeHtml(templateId)}" class="group flex min-h-[170px] flex-col rounded-2xl border-2 p-3 text-left transition focus:outline-none focus:ring-2 focus:ring-amber-500 ${selected ? 'border-amber-500 bg-amber-50' : 'border-slate-200 bg-white hover:border-amber-300 hover:bg-amber-50/70'}" aria-pressed="${selected ? 'true' : 'false'}">
                    <div class="flex-1">${renderPreview(definition)}</div>
                    <div class="mt-3 text-[11px] font-semibold leading-tight text-slate-700">${escapeHtml(label)}</div>
                </button>
            `;
        }).join('');
    }

    window.almadenPageTemplateOptions = { render };
})();
