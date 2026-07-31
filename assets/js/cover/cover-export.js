// cover-export.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;
    const utils = window.CoverEditor.utils;

    const exportPdfBtn = document.getElementById('export-pdf-btn');
    if (!exportPdfBtn) return;
    const EXPORT_FORM_ID = 'cover-export-download-form';
    let exportBusyUntil = 0;

    function ensureDownloadTargets() {
        let form = document.getElementById(EXPORT_FORM_ID);
        if (!form) {
            form = document.createElement('form');
            form.id = EXPORT_FORM_ID;
            form.method = 'POST';
            form.action = coverData.exportUrl || (window.location.origin + '/wp-admin/admin-post.php');
            form.target = '_self';
            form.style.display = 'none';
            document.body.appendChild(form);
        }

        return { form };
    }

    function buildExportPayload() {
        const getFieldValue = (id, fallback = '') => {
            const field = document.getElementById(id);
            return field && field.value !== undefined ? field.value : fallback;
        };

        return {
            paper_type: getFieldValue('paper-type', el.paperTypeSelect ? el.paperTypeSelect.value : '0.06'),
            page_count: getFieldValue('page-count', el.pageCountInput ? el.pageCountInput.value : '0'),
            spine_width_mode: utils.getSpineWidthMode ? utils.getSpineWidthMode() : 'auto',
            spine_width_mm: getFieldValue('spine-width-mm', ''),
            front_flap_width: getFieldValue('front-flap-width', '0'),
            back_flap_width: getFieldValue('back-flap-width', '0'),
            fold_x: getFieldValue('fold-x', '0'),
            front_image: getFieldValue('upload-front-cover', ''),
            front_image_attachment_id: getFieldValue('upload-front-cover-attachment-id', '0'),
            back_image: getFieldValue('upload-back-cover', ''),
            back_image_attachment_id: getFieldValue('upload-back-cover-attachment-id', '0'),
            spine_image: getFieldValue('upload-spine-image', ''),
            spine_image_attachment_id: getFieldValue('upload-spine-image-attachment-id', '0'),
            spine_color: getFieldValue('spine-color-picker', '#f9fafb'),
            spread_image: getFieldValue('upload-full-spread', ''),
            spread_image_attachment_id: getFieldValue('upload-full-spread-attachment-id', '0'),
            front_flap_image: getFieldValue('upload-front-flap-image', ''),
            front_flap_image_attachment_id: getFieldValue('upload-front-flap-image-attachment-id', '0'),
            front_flap_color: getFieldValue('front-flap-color-picker', '#ffffff'),
            back_flap_image: getFieldValue('upload-back-flap-image', ''),
            back_flap_image_attachment_id: getFieldValue('upload-back-flap-image-attachment-id', '0'),
            back_flap_color: getFieldValue('back-flap-color-picker', '#ffffff'),
            text_layers: JSON.parse(JSON.stringify(s.textLayers || [])).map(layer => ({
                ...layer,
                fontWeight: layer.fontWeight || 400,
                fontStyle: layer.fontStyle || 'normal',
                lineHeight: layer.lineHeight || 1.2,
                letterSpacing: layer.letterSpacing || 0
            })),
            page_width_cm: s.pageWidthCm,
            page_height_cm: s.pageHeightCm
        };
    }

    function getPreflightSummary() {
        if (window.CoverEditor.actions && typeof window.CoverEditor.actions.getCoverPreflightSummary === 'function') {
            return window.CoverEditor.actions.getCoverPreflightSummary();
        }

        return {
            ready: false,
            front: null,
            back: null,
            failing: [],
            hasFailingIssues: false,
            lastUpdatedAt: 0,
            lastStatus: 'unknown'
        };
    }

    function buildPreflightMessage(summary) {
        const items = [];
        if (summary.front && summary.front.issues && summary.front.issues.length) {
            items.push(`Portada: ${summary.front.issues.join(', ')}`);
        }
        if (summary.back && summary.back.issues && summary.back.issues.length) {
            items.push(`Contraportada: ${summary.back.issues.join(', ')}`);
        }
        if (summary.editorial && summary.editorial.hardIssues && summary.editorial.hardIssues.length) {
            items.push(`Preflight general: ${summary.editorial.hardIssues.join(', ')}`);
        }

        if (items.length === 0) {
            items.push('Hay advertencias que revisar antes de exportar.');
        }

        return items.join('\n');
    }

    async function triggerExport() {
        const exportPdfBtn = document.getElementById('export-pdf-btn');
        const originalLabel = exportPdfBtn ? exportPdfBtn.innerHTML : '';
        const coverDataRef = (typeof coverData !== 'undefined' && coverData) ? coverData : (window.coverData || {});
        const now = Date.now();

        if (now < exportBusyUntil) {
            return;
        }

        const exportNonce = coverDataRef.exportNonce || coverDataRef.nonce || '';
        const { form } = ensureDownloadTargets();
        const preflight = getPreflightSummary();

        if (exportPdfBtn) {
            exportPdfBtn.disabled = true;
            exportPdfBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-red-500"></i> Generando PDF...';
        }
        exportBusyUntil = now + 120000;

        try {
            if (preflight.hasFailingIssues) {
                const shouldContinue = window.confirm(
                    'El preflight detectó problemas en la portada o contraportada.\n\n' +
                    buildPreflightMessage(preflight) +
                    '\n\n¿Igual quieres exportar el PDF?'
                );
                if (!shouldContinue) {
                    exportBusyUntil = 0;
                    if (exportPdfBtn) {
                        exportPdfBtn.disabled = false;
                        exportPdfBtn.innerHTML = originalLabel || '<i class="fa-solid fa-file-pdf text-red-500"></i> Descargar PDF';
                    }
                    return;
                }
            } else if (!preflight.ready) {
                const shouldContinue = window.confirm(
                    'Aún no tengo un preflight completo de portada/contraportada.\n\n¿Quieres exportar de todos modos?'
                );
                if (!shouldContinue) {
                    exportBusyUntil = 0;
                    if (exportPdfBtn) {
                        exportPdfBtn.disabled = false;
                        exportPdfBtn.innerHTML = originalLabel || '<i class="fa-solid fa-file-pdf text-red-500"></i> Descargar PDF';
                    }
                    return;
                }
            }

            Array.from(form.querySelectorAll('input[name]')).forEach(input => input.remove());

            const fields = {
                action: 'almaden_export_cover_pdf',
                book_id: coverDataRef.bookId || 0,
                nonce: exportNonce,
                cover_payload: JSON.stringify(buildExportPayload())
            };

            Object.entries(fields).forEach(([name, value]) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value;
                form.appendChild(input);
            });

            form.action = coverDataRef.exportUrl || form.action;
            form.target = '_self';
            form.submit();

            window.setTimeout(() => {
                if (Date.now() < exportBusyUntil) {
                    exportBusyUntil = 0;
                    if (exportPdfBtn) {
                        exportPdfBtn.disabled = false;
                        exportPdfBtn.innerHTML = originalLabel || '<i class="fa-solid fa-file-pdf text-red-500"></i> Descargar PDF';
                    }
                }
            }, 2000);
        } catch (error) {
            console.error(error);
            const message = error && error.message ? error.message : 'No se pudo generar el PDF CMYK.';
            alert(message);
            exportBusyUntil = 0;
        } finally {
            if (exportPdfBtn && Date.now() >= exportBusyUntil) {
                exportPdfBtn.disabled = false;
                exportPdfBtn.innerHTML = originalLabel || '<i class="fa-solid fa-file-pdf text-red-500"></i> Descargar PDF';
            }
        }
    }

    exportPdfBtn.addEventListener('click', triggerExport);
});
