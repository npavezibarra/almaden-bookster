// assets/js/cover/cover-image-diagnostics-render.js

(function() {
    const Diagnostics = window.CoverEditorDiagnostics = window.CoverEditorDiagnostics || {};

    function setPanelBase(panel, title, helperText) {
        panel.className = 'mt-3 rounded-xl border border-dashed border-gray-200 bg-gray-50/80 px-3 py-3 text-[11px] leading-relaxed text-gray-600';
        panel.innerHTML = `
            <div class="font-semibold text-gray-700 uppercase tracking-wider mb-1">${title}</div>
            <div class="text-gray-500">${helperText}</div>
        `;
    }

    function renderLoading(panel, label) {
        panel.className = 'mt-3 rounded-xl border border-dashed border-gray-200 bg-gray-50/80 px-3 py-3 text-[11px] leading-relaxed text-gray-600';
        panel.innerHTML = `
            <div class="font-semibold text-gray-700 uppercase tracking-wider mb-1">${label}</div>
            <div class="text-gray-500">Analizando imagen...</div>
        `;
    }

    function renderNoImage(panel, label, coverWidthCm, coverHeightCm, requiredDpi) {
        const formatCm = Diagnostics.formatCm || ((value) => Number(value || 0).toString());
        setPanelBase(panel, label, `Selecciona una imagen para ver si cumple con ${formatCm(coverWidthCm)} x ${formatCm(coverHeightCm)} cm a ${requiredDpi} dpi.`);
    }

    function getImageSourceLabel(data) {
        if (!data) return 'desconocido';
        return data.is_original_source === false ? 'versión escalada' : (data.file_name || 'desconocido');
    }

    function imageChecks(data) {
        const formatPx = Diagnostics.formatPx || ((value) => String(Math.round(value || 0)));
        const formatDpi = Diagnostics.formatDpi || ((value) => String(Math.round(value || 0)));
        const formatPercent = Diagnostics.formatPercent || ((value) => String(Number(value || 0)));
        const isOriginal = data.is_original_source !== false;
        const formatFriendly = data.format_is_print_friendly !== false;
        return [
            {
                label: 'Resolución mínima',
                ok: data.width_px >= data.min_width_px && data.height_px >= data.min_height_px,
                detail: `${formatPx(data.width_px)} x ${formatPx(data.height_px)} px`
            },
            {
                label: 'DPI efectivo',
                ok: data.effective_dpi_x >= data.required_dpi && data.effective_dpi_y >= data.required_dpi,
                detail: `${formatDpi(data.effective_dpi_x)} x ${formatDpi(data.effective_dpi_y)} dpi`
            },
            {
                label: 'Relación de aspecto',
                ok: (data.aspect_ratio_diff_pct || 0) <= 2,
                detail: `${formatPercent(data.aspect_ratio_diff_pct || 0)}% de diferencia`
            },
            {
                label: 'Fuente original',
                ok: isOriginal,
                detail: isOriginal ? 'archivo original' : 'se detectó una versión escalada'
            },
            {
                label: 'Formato de archivo',
                ok: formatFriendly,
                detail: (data.image_ext || data.image_mime || 'desconocido').toString()
            }
        ];
    }

    function renderImageResult(panel, label, data, requiredDpi) {
        const formatPx = Diagnostics.formatPx || ((value) => String(Math.round(value || 0)));
        const formatDpi = Diagnostics.formatDpi || ((value) => String(Math.round(value || 0)));
        const formatCm = Diagnostics.formatCm || ((value) => Number(value || 0).toString());
        const issues = Array.isArray(data.issues) ? data.issues : [];
        const success = !!data.meets_requirements;
        const statusClasses = success
            ? 'border-emerald-200 bg-emerald-50/90 text-emerald-900'
            : 'border-amber-200 bg-amber-50/90 text-amber-900';
        const badgeClasses = success
            ? 'bg-emerald-100 text-emerald-800'
            : 'bg-amber-100 text-amber-800';
        const checks = imageChecks(data);

        panel.className = `mt-3 rounded-xl border px-3 py-3 text-[11px] leading-relaxed ${statusClasses}`;
        panel.innerHTML = `
            <div class="flex items-center justify-between gap-2 mb-2">
                <div class="font-semibold uppercase tracking-wider">${label}</div>
                <div class="text-[10px] font-bold px-2 py-0.5 rounded-full ${badgeClasses}">
                    ${success ? 'Cumple' : 'No cumple'}
                </div>
            </div>
            <div class="space-y-1">
                <div><span class="font-semibold">Dimensiones reales:</span> ${formatPx(data.width_px)} x ${formatPx(data.height_px)} px</div>
                <div><span class="font-semibold">DPI efectivo:</span> ${formatDpi(data.effective_dpi_x)} x ${formatDpi(data.effective_dpi_y)} dpi</div>
                <div><span class="font-semibold">Tamaño físico a 300 dpi:</span> ${formatCm(data.physical_width_cm_at_300)} x ${formatCm(data.physical_height_cm_at_300)} cm</div>
                <div><span class="font-semibold">Mínimo requerido:</span> ${formatPx(data.min_width_px)} x ${formatPx(data.min_height_px)} px</div>
                <div><span class="font-semibold">Peso del archivo:</span> ${formatCm(data.file_size_mb)} MB</div>
                <div><span class="font-semibold">Archivo usado:</span> ${getImageSourceLabel(data)}</div>
                <div class="pt-2">
                    <div class="font-semibold uppercase tracking-wider mb-1">Preflight</div>
                    <div class="space-y-1.5">
                        ${checks.map(check => {
                            const chip = check.ok ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800';
                            const icon = check.ok ? 'fa-circle-check' : 'fa-triangle-exclamation';
                            return `
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-2">
                                        <i class="fa-solid ${icon} mt-0.5 ${check.ok ? 'text-emerald-600' : 'text-red-600'}"></i>
                                        <div>
                                            <div class="font-medium">${check.label}</div>
                                            <div class="text-[10px] opacity-75">${check.detail}</div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full ${chip}">
                                        ${check.ok ? 'OK' : 'Revisar'}
                                    </span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
                <div class="pt-1 font-medium">${success
                    ? `La imagen cumple para ${formatCm(data.target_width_cm)} x ${formatCm(data.target_height_cm)} cm a ${requiredDpi} dpi.`
                    : `No cumple: ${issues.join(' y ')}.`
                }</div>
            </div>
        `;
    }

    Object.assign(Diagnostics, {
        setPanelBase,
        renderLoading,
        renderNoImage,
        getImageSourceLabel,
        imageChecks,
        renderImageResult
    });
})();
