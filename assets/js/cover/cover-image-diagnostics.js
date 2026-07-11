// cover-image-diagnostics.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;

    const frontInput = document.getElementById('upload-front-cover');
    const backInput = document.getElementById('upload-back-cover');
    const frontPanel = document.getElementById('front-cover-diagnostics');
    const backPanel = document.getElementById('back-cover-diagnostics');
    const overallPanel = document.getElementById('cover-editorial-diagnostics');

    if (!frontInput || !backInput || !frontPanel || !backPanel || !overallPanel) {
        return;
    }

    const coverDataRef = (typeof coverData !== 'undefined' && coverData) ? coverData : {};
    const requiredDpi = parseFloat(coverDataRef.minPrintDpi) || 300;
    const bleedMm = parseFloat(coverDataRef.bleedMm) || 5;
    const safeMarginMm = parseFloat(coverDataRef.safeMarginMm) || 4;
    const coverWidthCm = parseFloat(s.pageWidthCm) || parseFloat(coverDataRef.pageWidthCm) || 14;
    const coverHeightCm = parseFloat(s.pageHeightCm) || parseFloat(coverDataRef.pageHeightCm) || 21;

    const builtinFonts = new Set([
        'Inter',
        'Merriweather',
        'Playfair Display',
        'Lora',
        'Cinzel',
        'Cormorant Garamond',
        'Outfit'
    ]);

    function formatPx(value) {
        return new Intl.NumberFormat('es-ES').format(Math.round(value || 0));
    }

    function formatDpi(value) {
        return new Intl.NumberFormat('es-ES', {
            maximumFractionDigits: 0
        }).format(Math.round(value || 0));
    }

    function formatCm(value) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: 1,
            maximumFractionDigits: 2
        }).format(Number(value) || 0);
    }

    function formatMm(value) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 1
        }).format(Number(value) || 0);
    }

    function formatPercent(value) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(Number(value) || 0);
    }

    function normalizeHex(hex) {
        if (!hex) return '#000000';
        let value = String(hex).trim();
        if (value.length === 4 && value[0] === '#') {
            value = '#' + value[1] + value[1] + value[2] + value[2] + value[3] + value[3];
        }
        if (/^#[0-9a-fA-F]{6}$/.test(value)) {
            return value.toUpperCase();
        }
        return '#000000';
    }

    function hexToRgb(hex) {
        const value = normalizeHex(hex);
        const match = value.match(/^#([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{2})$/);
        if (!match) {
            return { r: 0, g: 0, b: 0 };
        }
        return {
            r: parseInt(match[1], 16),
            g: parseInt(match[2], 16),
            b: parseInt(match[3], 16)
        };
    }

    function isPureBlack(hex) {
        return normalizeHex(hex) === '#000000';
    }

    function isNearBlack(hex) {
        const { r, g, b } = hexToRgb(hex);
        const luminance = (0.299 * r) + (0.587 * g) + (0.114 * b);
        return luminance <= 60;
    }

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

    function renderNoImage(panel, label) {
        setPanelBase(panel, label, `Selecciona una imagen para ver si cumple con ${formatCm(coverWidthCm)} x ${formatCm(coverHeightCm)} cm a ${requiredDpi} dpi.`);
    }

    function getLayerById(id) {
        return (s.textLayers || []).find(layer => layer && layer.id === id) || null;
    }

    function getAllowedFonts() {
        const allowed = new Set(builtinFonts);
        const installed = Array.isArray(coverDataRef.installedFonts) ? coverDataRef.installedFonts : [];
        installed.forEach(font => {
            if (font && font.family) {
                allowed.add(String(font.family).trim());
            }
        });
        return allowed;
    }

    function getCoveredRect() {
        if (!el.coverSpread) {
            return null;
        }
        const rect = el.coverSpread.getBoundingClientRect();
        if (!rect.width || !rect.height) {
            return null;
        }
        return rect;
    }

    function getImageSourceLabel(data) {
        if (!data) return 'desconocido';
        return data.is_original_source === false ? 'versión escalada' : (data.file_name || 'desconocido');
    }

    function imageChecks(data) {
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

    function renderImageResult(panel, label, data) {
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

        s.coverPreflight = s.coverPreflight || {};
        const key = label.toLowerCase().includes('contraportada') ? 'back' : 'front';
        s.coverPreflight[key] = {
            label,
            ...data,
            issues,
            success,
            generatedAt: Date.now()
        };
        s.coverPreflight.lastUpdatedAt = Date.now();
        s.coverPreflight.lastStatus = success ? 'ok' : 'warning';
    }

    async function fetchDiagnostics(targetKey, imageUrl) {
        const panel = targetKey === 'front' ? frontPanel : backPanel;
        const label = targetKey === 'front' ? 'Validación de portada' : 'Validación de contraportada';

        if (!imageUrl) {
            renderNoImage(panel, label);
            return null;
        }

        renderLoading(panel, label);

        const formData = new FormData();
        formData.append('action', 'almaden_get_cover_image_diagnostics');
        formData.append('book_id', coverDataRef.bookId || 0);
        formData.append('nonce', coverDataRef.nonce || '');
        formData.append('image_url', imageUrl);
        formData.append('target_width_cm', String(coverWidthCm));
        formData.append('target_height_cm', String(coverHeightCm));

        try {
            const ajaxUrl = coverDataRef.ajaxUrl || window.ajaxurl || (window.location.origin + '/wp-admin/admin-ajax.php');
            const response = await fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            });

            const payload = await response.json();
            if (!payload || !payload.success) {
                throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'No se pudo analizar la imagen.');
            }

            const data = payload.data;
            if (!data || !data.has_image) {
                s.coverPreflight = s.coverPreflight || {};
                s.coverPreflight[targetKey] = {
                    label,
                    has_image: false,
                    issues: [],
                    success: false,
                    generatedAt: Date.now()
                };
                s.coverPreflight.lastUpdatedAt = Date.now();
                renderNoImage(panel, label);
                return null;
            }

            renderImageResult(panel, label, data);
            return data;
        } catch (error) {
            panel.className = 'mt-3 rounded-xl border border-red-200 bg-red-50/80 px-3 py-3 text-[11px] leading-relaxed text-red-800';
            panel.innerHTML = `
                <div class="font-semibold uppercase tracking-wider mb-1">${label}</div>
                <div>No se pudo analizar la imagen: ${error.message || 'error desconocido'}.</div>
            `;
            s.coverPreflight = s.coverPreflight || {};
            s.coverPreflight[targetKey] = {
                label,
                has_image: false,
                issues: ['No se pudo analizar la imagen'],
                success: false,
                generatedAt: Date.now()
            };
            s.coverPreflight.lastUpdatedAt = Date.now();
            s.coverPreflight.lastStatus = 'error';
            return null;
        }
    }

    function analyzeEditorialLayout() {
        const report = {
            bleedMm,
            safeMarginMm,
            bleedOk: true,
            safeAreaOk: true,
            fontOk: true,
            blackOk: true,
            colorFlowOk: true,
            hardIssues: [],
            warnings: [],
            notes: []
        };

        const coverRect = getCoveredRect();
        if (!coverRect) {
            report.notes.push('Aún no hay lienzo disponible para validar el layout.');
            return report;
        }

        const bleedPx = (bleedMm / 10) * (s.pxPerCm || 37.795);
        const safeMarginPx = (safeMarginMm / 10) * (s.pxPerCm || 37.795);
        const safeInsetPx = bleedPx + safeMarginPx;
        const coverWidthPx = coverRect.width;
        const coverHeightPx = coverRect.height;
        const allowedFonts = getAllowedFonts();
        const layerNodes = Array.from(el.coverSpread.querySelectorAll('.text-layer'));

        if (Math.abs(bleedMm - 5) > 0.01) {
            report.bleedOk = false;
            report.hardIssues.push(`El sangrado del editor no está en 5 mm (actual: ${formatMm(bleedMm)} mm).`);
        }

        if (layerNodes.length === 0) {
            report.notes.push('No hay capas editoriales para revisar.');
        }

        layerNodes.forEach(node => {
            const layer = getLayerById(node.dataset.id);
            if (!layer || layer.type === 'group') {
                return;
            }

            const rect = node.getBoundingClientRect();
            const x0 = rect.left - coverRect.left;
            const y0 = rect.top - coverRect.top;
            const x1 = rect.right - coverRect.left;
            const y1 = rect.bottom - coverRect.top;
            const layerName = layer.name || layer.text || layer.id || 'capa';

            const nearEdge =
                x0 < safeInsetPx ||
                y0 < safeInsetPx ||
                x1 > (coverWidthPx - safeInsetPx) ||
                y1 > (coverHeightPx - safeInsetPx);

            if (nearEdge) {
                report.safeAreaOk = false;
                report.hardIssues.push(`"${layerName}" está demasiado cerca del corte o del sangrado.`);
            }

            if (layer.type === 'text') {
                const fontFamily = String(layer.fontFamily || '').trim();
                if (fontFamily && !allowedFonts.has(fontFamily)) {
                    report.fontOk = false;
                    report.hardIssues.push(`La tipografía "${fontFamily}" no está disponible para incrustarse o no figura en la biblioteca instalada.`);
                }

                const textColor = normalizeHex(layer.color || '#000000');
                if (isNearBlack(textColor) && !isPureBlack(textColor)) {
                    report.blackOk = false;
                    report.hardIssues.push(`El texto "${layerName}" usa un negro no puro (${textColor}). Para impresión conviene 100% negro.`);
                }
            }

            if (layer.type === 'shape') {
                const fillColor = normalizeHex(layer.color1 || '#000000');
                const widthPx = parseFloat(layer.width || 0);
                const heightPx = parseFloat(layer.height || 0);
                const areaPx = Math.max(widthPx, 0) * Math.max(heightPx, 0);

                if (Math.min(widthPx, heightPx) > 0 && Math.min(widthPx, heightPx) <= 4) {
                    report.warnings.push(`La forma "${layerName}" es muy delgada y puede comportarse como una línea fina.`);
                }

                if (isNearBlack(fillColor) && !isPureBlack(fillColor)) {
                    report.blackOk = false;
                    report.hardIssues.push(`La forma "${layerName}" usa un negro no puro (${fillColor}).`);
                }

                if (isPureBlack(fillColor) && areaPx >= 12000) {
                    report.warnings.push(`La forma "${layerName}" es una masa negra grande. Si la imprenta lo exige, podría requerir negro enriquecido.`);
                }
            }
        });

        report.colorFlowOk = true;
        report.notes.push('El export final se convierte a CMYK con Ghostscript.');
        report.notes.push('El editor no puede garantizar desde aquí un perfil Adobe RGB 1998 incrustado; eso requiere preflight de PDF final o control externo.');

        return report;
    }

    function renderEditorialDiagnostics() {
        const report = analyzeEditorialLayout();
        const front = s.coverPreflight && s.coverPreflight.front ? s.coverPreflight.front : null;
        const back = s.coverPreflight && s.coverPreflight.back ? s.coverPreflight.back : null;
        const imageIssues = [];

        if (front && Array.isArray(front.issues)) {
            imageIssues.push(...front.issues.map(issue => `Portada: ${issue}`));
        }
        if (back && Array.isArray(back.issues)) {
            imageIssues.push(...back.issues.map(issue => `Contraportada: ${issue}`));
        }

        const overallFailures = [
            ...imageIssues,
            ...report.hardIssues
        ];

        const overallWarnings = report.warnings;
        const overallNotes = report.notes;
        const success = overallFailures.length === 0;
        const maybeWarningOnly = success && overallWarnings.length > 0;
        const statusClasses = success
            ? (maybeWarningOnly ? 'border-amber-200 bg-amber-50/90 text-amber-900' : 'border-emerald-200 bg-emerald-50/90 text-emerald-900')
            : 'border-red-200 bg-red-50/90 text-red-900';
        const badgeClasses = success
            ? (maybeWarningOnly ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800')
            : 'bg-red-100 text-red-800';

        const checks = [
            {
                label: 'Sangrado',
                ok: report.bleedOk,
                detail: `${formatMm(report.bleedMm)} mm configurados`
            },
            {
                label: 'Área segura',
                ok: report.safeAreaOk,
                detail: `${formatMm(report.safeMarginMm)} mm desde el corte`
            },
            {
                label: 'Tipografías',
                ok: report.fontOk,
                detail: report.fontOk ? 'fuentes disponibles para incrustación' : 'hay tipografías no reconocidas'
            },
            {
                label: 'Negros',
                ok: report.blackOk,
                detail: report.blackOk ? 'sin negros problemáticos detectados' : 'hay negros no puros'
            },
            {
                label: 'Color',
                ok: report.colorFlowOk,
                detail: 'export final a CMYK'
            }
        ];

        overallPanel.className = `mt-3 rounded-xl border px-3 py-3 text-[11px] leading-relaxed ${statusClasses}`;
        overallPanel.innerHTML = `
            <div class="flex items-center justify-between gap-2 mb-2">
                <div class="font-semibold uppercase tracking-wider">Verificación de preprensa</div>
                <div class="text-[10px] font-bold px-2 py-0.5 rounded-full ${badgeClasses}">
                    ${success ? (maybeWarningOnly ? 'Cumple con observaciones' : 'Cumple') : 'No cumple'}
                </div>
            </div>
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
            <div class="pt-2">
                <div class="font-semibold uppercase tracking-wider mb-1">Observaciones</div>
                <div class="space-y-1 text-gray-700">
                    ${overallFailures.length ? `<div>${overallFailures.map(item => `• ${item}`).join('<br>')}</div>` : '<div>Sin fallos críticos detectados.</div>'}
                    ${overallWarnings.length ? `<div class="text-amber-700">${overallWarnings.map(item => `• ${item}`).join('<br>')}</div>` : ''}
                    ${overallNotes.length ? `<div class="text-gray-500">${overallNotes.map(item => `• ${item}`).join('<br>')}</div>` : ''}
                </div>
            </div>
        `;

        s.coverPreflight = s.coverPreflight || {};
        s.coverPreflight.editorial = {
            ...report,
            success,
            warnings: overallWarnings,
            hardIssues: overallFailures,
            generatedAt: Date.now()
        };
        s.coverPreflight.lastUpdatedAt = Date.now();
        s.coverPreflight.lastStatus = success ? (maybeWarningOnly ? 'warning' : 'ok') : 'error';
    }

    let refreshTimer = null;
    function scheduleRefresh() {
        if (refreshTimer) {
            window.clearTimeout(refreshTimer);
        }
        refreshTimer = window.setTimeout(() => {
            refreshAll();
        }, 120);
    }

    async function refreshAll() {
        await Promise.all([
            fetchDiagnostics('front', frontInput.value || ''),
            fetchDiagnostics('back', backInput.value || '')
        ]);
        renderEditorialDiagnostics();
    }

    window.CoverEditor.actions.refreshCoverImageDiagnostics = scheduleRefresh;

    if (el.paperTypeSelect) {
        el.paperTypeSelect.addEventListener('change', scheduleRefresh);
    }
    if (el.spineWidthMode) {
        el.spineWidthMode.addEventListener('change', scheduleRefresh);
    }
    if (el.spineWidthMm) {
        el.spineWidthMm.addEventListener('input', scheduleRefresh);
    }

    window.CoverEditor.actions.getCoverPreflightSummary = function() {
        const preflight = s.coverPreflight || {};
        const front = preflight.front || null;
        const back = preflight.back || null;
        const editorial = preflight.editorial || null;
        const entries = [front, back].filter(Boolean);
        const failing = entries.filter(entry => entry.success === false);
        if (editorial && editorial.hardIssues && editorial.hardIssues.length) {
            failing.push({
                label: 'Verificación de preprensa',
                issues: editorial.hardIssues,
                success: false
            });
        }
        const ready = !!(front && back && editorial);

        return {
            ready,
            front,
            back,
            editorial,
            failing,
            hasFailingIssues: failing.length > 0,
            lastUpdatedAt: preflight.lastUpdatedAt || 0,
            lastStatus: preflight.lastStatus || 'unknown'
        };
    };

    function observeLayoutChanges() {
        if (!window.MutationObserver || !el.coverSpread) {
            return;
        }

        const observer = new MutationObserver(scheduleRefresh);
        const targets = [el.coverSpread, el.frontCover, el.backCover, el.spine, el.frontFlap, el.backFlap].filter(Boolean);
        targets.forEach(target => {
            observer.observe(target, {
                subtree: true,
                childList: true,
                attributes: true,
                attributeFilter: ['style', 'class', 'data-id']
            });
        });
        window.CoverEditor.actions.coverPreflightObserver = observer;
    }

    observeLayoutChanges();
    refreshAll();
});
