(function () {
    if (window.almadenTypstPdfView) return;

    const state = window.almadenTypstPdfState;
    if (!state) {
        console.warn('No se pudo inicializar el visor Typst porque falta el módulo de estado.');
        return;
    }

    const shared = state.shared;
    const PDFJS_WORKER_SRC = state.constants.PDFJS_WORKER_SRC;

    function getRenderSequence() {
        return Number(window.almadenTypstPdfMain?.renderSequence || 0);
    }

    function bumpRenderSequence() {
        const nextSequence = getRenderSequence() + 1;
        if (!window.almadenTypstPdfMain || typeof window.almadenTypstPdfMain !== 'object') {
            window.almadenTypstPdfMain = {};
        }
        window.almadenTypstPdfMain.renderSequence = nextSequence;
        return nextSequence;
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function updateGeometryIndicator() {
        const indicator = document.getElementById('pdf-geometry-indicator');
        if (!indicator || !shared.currentGeometry) return;
        const g = shared.currentGeometry;
        indicator.textContent = `${g.width} × ${g.height} ${g.unit} · márgenes ${g.inside}/${g.outside} ${g.unit}`;
        const contentTop = g.content_top ?? g.top;
        const contentBottom = g.content_bottom ?? g.bottom;
        indicator.title = `Hoja ${g.width} × ${g.height} ${g.unit}. Área de texto: superior ${contentTop}, inferior ${contentBottom}, interior ${g.inside}, exterior ${g.outside} ${g.unit}. Sangrado ${g.bleed} ${g.unit}.`;
    }

    function getTextBounds(pageNumber) {
        const g = shared.currentGeometry || {};
        const width = Number.parseFloat(g.width);
        const height = Number.parseFloat(g.height);
        const top = Number.parseFloat(g.content_top ?? g.top);
        const bottom = Number.parseFloat(g.content_bottom ?? g.bottom);
        const inside = Number.parseFloat(g.inside);
        const outside = Number.parseFloat(g.outside);

        if (![width, height, top, bottom, inside, outside].every(Number.isFinite) || width <= 0 || height <= 0) {
            return null;
        }

        const oddPage = pageNumber % 2 === 1;
        const left = oddPage ? inside : outside;
        const right = oddPage ? outside : inside;

        return {
            top: `${(top / height) * 100}%`,
            right: `${(right / width) * 100}%`,
            bottom: `${(bottom / height) * 100}%`,
            left: `${(left / width) * 100}%`
        };
    }

    function getBleedGuideOffsetPx(shell) {
        const g = shared.currentGeometry || {};
        const bleed = Number.parseFloat(g.bleed);
        const width = Number.parseFloat(g.width);
        const height = Number.parseFloat(g.height);
        const shellWidth = shell ? Number.parseFloat(shell.clientWidth) : NaN;
        const shellHeight = shell ? Number.parseFloat(shell.clientHeight) : NaN;

        if (![bleed, width, height, shellWidth, shellHeight].every(Number.isFinite) || bleed <= 0 || width <= 0 || height <= 0) {
            return null;
        }

        const pxPerUnit = Math.min(shellWidth / width, shellHeight / height);
        const bleedPx = bleed * pxPerUnit;
        return bleedPx > 0 ? bleedPx : null;
    }

    function getFullBleedChapterImageUrl(pageNumber) {
        const counterChapters = window.bookState?.pdfPreview?.universalCounter?.chapters;
        const chapters = window.bookState?.chapters;
        if (!Array.isArray(counterChapters) || !Array.isArray(chapters)) return '';

        const counterEntry = counterChapters.find(entry => Number(entry?.startPage || 0) === pageNumber);
        if (!counterEntry) return '';

        const chapter = chapters.find(entry => String(entry?.id || '') === String(counterEntry.id || ''));
        if (!chapter) return '';

        const override = String(chapter.chapter_image_override ?? chapter.chapter_image_enabled ?? '');
        const bookDefault = window.bookState?.settings?.chapter_image_default === true
            || String(window.bookState?.settings?.chapter_image_default || '') === '1';
        const imageEnabled = override === '1' || (override !== '0' && bookDefault);
        const imageMode = String(chapter.chapter_image_mode || '').toLowerCase();
        const imageUrl = String(chapter.chapter_image_url || '').trim();
        return imageEnabled && imageMode === 'image_full_page' ? imageUrl : '';
    }

    function normalizeHexColor(value, fallback = '#ffffff') {
        const raw = String(value || '').trim().toLowerCase();
        return /^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/.test(raw) ? raw : fallback;
    }

    function getPageStyleForPage(pageNumber) {
        const target = Number(pageNumber) || 0;
        if (target < 1) return null;

        const direct = window.almadenPageStyleState?.getStyleForPage?.(target);
        if (direct) return direct;

        const styles = Array.isArray(window.bookState?.settings?.page_styles)
            ? window.bookState.settings.page_styles
            : [];
        return styles.find(style => Number(style?.resolved_page || style?.page_number) === target) || null;
    }

    function getPageStyleBackdrop(pageNumber) {
        const style = getPageStyleForPage(pageNumber);
        const background = style?.style?.background || {};
        const type = String(background.type || 'color').toLowerCase();

        if (type === 'gradient') {
            const gradient = background.gradient || {};
            const stops = Array.isArray(gradient.stops) ? gradient.stops : [];
            const stopA = normalizeHexColor(stops[0]?.color, '#ffffff');
            const stopB = normalizeHexColor(stops[1]?.color, '#f3f4f6');
            const angle = Number.isFinite(Number(gradient.angle)) ? Math.max(0, Math.min(360, Number(gradient.angle))) : 135;
            return {
                backgroundImage: `linear-gradient(${angle}deg, ${stopA} 0%, ${stopB} 100%)`,
                backgroundColor: stopA
            };
        }

        if (type === 'image') {
            const overlay = background.overlay || {};
            return {
                backgroundColor: normalizeHexColor(overlay.color, '#ffffff')
            };
        }

        return {
            backgroundColor: normalizeHexColor(background.color, '#ffffff')
        };
    }

    function updateBleedGuides(root = document.getElementById('pdf-scroller')) {
        if (!root) return;

        root.querySelectorAll('[data-bleed-guide-backdrop]').forEach(overlay => overlay.remove());
        root.querySelectorAll('[data-bleed-guide-trim]').forEach(overlay => overlay.remove());
        root.querySelectorAll('[data-bleed-guide-page-limit]').forEach(overlay => overlay.remove());
        root.querySelectorAll('[data-bleed-guide-image]').forEach(overlay => overlay.remove());
        root.querySelectorAll('canvas[data-bleed-source-hidden="1"]').forEach(canvas => {
            canvas.style.visibility = canvas.dataset.bleedOriginalVisibility || '';
            delete canvas.dataset.bleedOriginalVisibility;
            delete canvas.dataset.bleedSourceHidden;
        });
        root.querySelectorAll('[data-page-number]').forEach(shell => {
            shell.style.overflow = 'visible';
        });

        const g = shared.currentGeometry || {};
        const bleed = Number.parseFloat(g.bleed);
        if (!Number.isFinite(bleed) || bleed <= 0) return;

        root.querySelectorAll('[data-page-number]').forEach(shell => {
            const bleedPx = getBleedGuideOffsetPx(shell);
            if (!bleedPx) return;

            const pageNumber = Number.parseInt(shell.dataset.pageNumber, 10);
            const isOddPage = pageNumber % 2 === 1;
            const imageUrl = getFullBleedChapterImageUrl(pageNumber);
            const backdropPaint = getPageStyleBackdrop(pageNumber);
            const backdrop = document.createElement('div');
            backdrop.dataset.bleedGuideBackdrop = '1';
            backdrop.className = 'pointer-events-none absolute box-border z-0';
            backdrop.style.top = `-${bleedPx}px`;
            backdrop.style.right = isOddPage ? `-${bleedPx}px` : '0';
            backdrop.style.bottom = `-${bleedPx}px`;
            backdrop.style.left = isOddPage ? '0' : `-${bleedPx}px`;
            backdrop.style.overflow = 'hidden';
            if (backdropPaint.backgroundImage) {
                backdrop.style.backgroundImage = backdropPaint.backgroundImage;
                backdrop.style.backgroundSize = 'cover';
                backdrop.style.backgroundPosition = 'center center';
                backdrop.style.backgroundRepeat = 'no-repeat';
            }
            backdrop.style.backgroundColor = backdropPaint.backgroundColor || '#ffffff';
            shell.insertBefore(backdrop, shell.firstChild);

            const canvas = shell.querySelector('canvas');
            if (canvas) {
                canvas.style.position = 'relative';
                canvas.style.zIndex = '1';
            }

            if (imageUrl) {
                const imageLayer = document.createElement('img');
                imageLayer.dataset.bleedGuideImage = '1';
                imageLayer.alt = '';
                imageLayer.draggable = false;
                imageLayer.className = 'pointer-events-none absolute z-10 block';
                imageLayer.style.top = `-${bleedPx}px`;
                imageLayer.style.left = isOddPage ? '0' : `-${bleedPx}px`;
                imageLayer.style.width = `calc(100% + ${bleedPx}px)`;
                imageLayer.style.height = `calc(100% + ${bleedPx * 2}px)`;
                imageLayer.style.maxWidth = 'none';
                imageLayer.style.maxHeight = 'none';
                imageLayer.style.objectFit = 'cover';
                imageLayer.style.objectPosition = 'center center';
                imageLayer.style.opacity = '0';
                imageLayer.addEventListener('load', () => {
                    if (!imageLayer.isConnected || !shell.contains(imageLayer)) return;
                    if (canvas) {
                        canvas.dataset.bleedOriginalVisibility = canvas.style.visibility || '';
                        canvas.dataset.bleedSourceHidden = '1';
                        canvas.style.visibility = 'hidden';
                    }
                    imageLayer.style.opacity = '1';
                }, { once: true });
                imageLayer.addEventListener('error', () => imageLayer.remove(), { once: true });
                shell.appendChild(imageLayer);
                imageLayer.src = imageUrl;
            }

            const trimFrame = document.createElement('div');
            trimFrame.dataset.bleedGuideTrim = '1';
            trimFrame.className = 'pointer-events-none absolute box-border z-20';
            trimFrame.style.top = `-${bleedPx}px`;
            trimFrame.style.bottom = `-${bleedPx}px`;
            trimFrame.style.left = isOddPage ? '0px' : `-${bleedPx}px`;
            trimFrame.style.right = isOddPage ? `-${bleedPx}px` : '0px';
            trimFrame.style.borderTop = '2px dotted rgba(34, 197, 94, 0.78)';
            trimFrame.style.borderBottom = '2px dotted rgba(34, 197, 94, 0.78)';
            trimFrame.style.borderLeft = isOddPage ? '0' : '2px dotted rgba(34, 197, 94, 0.78)';
            trimFrame.style.borderRight = isOddPage ? '2px dotted rgba(34, 197, 94, 0.78)' : '0';
            shell.appendChild(trimFrame);

            const pageLimit = document.createElement('div');
            pageLimit.dataset.bleedGuidePageLimit = '1';
            pageLimit.className = 'pointer-events-none absolute box-border z-20';
            pageLimit.style.inset = '0';
            pageLimit.style.border = '1px solid rgba(34, 197, 94, 0.78)';
            pageLimit.title = `Límite de corte de la página ${pageNumber}`;
            shell.appendChild(pageLimit);
        });
    }

    function updateTextBounds(root = document.getElementById('pdf-scroller')) {
        if (!root) return;

        root.querySelectorAll('[data-text-bounds-overlay]').forEach(overlay => overlay.remove());
        if (!shared.showTextBounds) return;

        root.querySelectorAll('[data-page-number]').forEach(shell => {
            if (shell.dataset.blank === '1') return;

            const pageNumber = Number.parseInt(shell.dataset.pageNumber, 10);
            const bounds = getTextBounds(pageNumber);
            if (!bounds) return;

            const overlay = document.createElement('div');
            overlay.dataset.textBoundsOverlay = '1';
            overlay.className = 'pointer-events-none absolute z-10 border border-dashed border-cyan-500 bg-cyan-300/10';
            overlay.style.top = bounds.top;
            overlay.style.right = bounds.right;
            overlay.style.bottom = bounds.bottom;
            overlay.style.left = bounds.left;
            overlay.title = `Área de texto de la página ${pageNumber}`;
            shell.appendChild(overlay);
        });
    }

    function updateTextBoundsToggle() {
        const toggle = document.getElementById('pdf-text-bounds-toggle');
        if (!toggle) return;

        toggle.setAttribute('aria-pressed', shared.showTextBounds ? 'true' : 'false');
        toggle.title = shared.showTextBounds ? 'Ocultar límites del área de texto' : 'Mostrar límites del área de texto';
        toggle.setAttribute('aria-label', toggle.title);
        toggle.classList.toggle('border-cyan-600', shared.showTextBounds);
        toggle.classList.toggle('bg-cyan-50', shared.showTextBounds);
        toggle.classList.toggle('text-cyan-800', shared.showTextBounds);
    }

    function bindTextBoundsToggle() {
        const toggle = document.getElementById('pdf-text-bounds-toggle');
        if (!toggle || toggle.dataset.bound === '1') return;

        toggle.dataset.bound = '1';
        toggle.addEventListener('click', () => {
            shared.showTextBounds = !shared.showTextBounds;
            updateTextBoundsToggle();
            updateTextBounds();
        });
        updateTextBoundsToggle();
    }

    function setStatus(message, isError = false) {
        const scroller = document.getElementById('pdf-scroller');
        if (!scroller) return;
        scroller.className = 'flex-1 overflow-hidden relative bg-slate-200';
        scroller.innerHTML = `
            <div class="flex h-full min-h-[240px] w-full items-center justify-center p-8 text-center ${isError ? 'text-red-500' : 'text-[var(--text-muted)]'}">
                <div class="max-w-3xl">
                    <i class="fa-solid ${isError ? 'fa-triangle-exclamation' : 'fa-spinner fa-spin'} mb-3 text-3xl"></i>
                    <div class="text-sm leading-relaxed">${escapeHtml(message)}</div>
                </div>
            </div>`;
    }

    function ensurePdfJs() {
        if (!window.pdfjsLib) {
            throw new Error('PDF.js no se cargó en el editor.');
        }
        if (window.pdfjsLib.GlobalWorkerOptions && !window.pdfjsLib.GlobalWorkerOptions.workerSrc) {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER_SRC;
        }
    }

    function destroyCurrentPdfDocument() {
        if (shared.currentPdfDocument && typeof shared.currentPdfDocument.destroy === 'function') {
            try {
                shared.currentPdfDocument.destroy();
            } catch (error) {
                // Cleanup failures are safe to ignore because the next render replaces the document.
            }
        }
        shared.currentPdfDocument = null;
        shared.currentPdfBytes = null;
    }

    async function loadPdfDocument() {
        if (shared.currentPdfDocument) {
            return shared.currentPdfDocument;
        }
        ensurePdfJs();
        if (!shared.currentPdfBytes && shared.currentPdfBlob) {
            shared.currentPdfBytes = await shared.currentPdfBlob.arrayBuffer();
        }
        if (!shared.currentPdfBytes) {
            throw new Error('No hay PDF disponible para renderizar.');
        }
        const loadingTask = window.pdfjsLib.getDocument({
            data: shared.currentPdfBytes,
            useWorkerFetch: false
        });
        shared.currentPdfDocument = await loadingTask.promise;
        return shared.currentPdfDocument;
    }

    function makePageShell(pageNumber, totalPages, isBlank = false) {
        const shell = document.createElement('div');
        shell.className = 'relative overflow-hidden bg-white border border-emerald-500/60 shadow-[0_10px_28px_rgba(15,23,42,0.12)]';
        shell.setAttribute('aria-label', isBlank ? `Página ${pageNumber} en blanco` : `Página ${pageNumber} de ${totalPages}`);
        shell.dataset.pageNumber = String(pageNumber);
        shell.dataset.blank = isBlank ? '1' : '0';
        shell.style.width = 'var(--almaden-pdf-page-width, 560px)';
        shell.style.minHeight = 'var(--almaden-pdf-page-height, 792px)';
        shell.innerHTML = isBlank
            ? '<div class="absolute inset-0 flex items-center justify-center text-[10px] font-bold uppercase tracking-[0.3em] text-slate-300">Blank</div>'
            : '';
        return shell;
    }

    async function renderPdfPageToCanvas(pdfDocument, pageNumber, canvas, scale, sequence) {
        const page = await pdfDocument.getPage(pageNumber);
        if (sequence !== getRenderSequence()) {
            if (typeof page.cleanup === 'function') page.cleanup();
            return;
        }

        const viewport = page.getViewport({ scale });
        const outputScale = Math.max(1, window.devicePixelRatio || 1);
        const context = canvas.getContext('2d', { alpha: false });

        canvas.width = Math.floor(viewport.width * outputScale);
        canvas.height = Math.floor(viewport.height * outputScale);
        canvas.style.width = `${Math.floor(viewport.width)}px`;
        canvas.style.height = `${Math.floor(viewport.height)}px`;

        const transform = outputScale !== 1
            ? [outputScale, 0, 0, outputScale, 0, 0]
            : null;

        await page.render({
            canvasContext: context,
            viewport,
            transform
        }).promise;

        if (typeof page.cleanup === 'function') {
            page.cleanup();
        }
    }

    async function renderPdfPreview() {
        const scroller = document.getElementById('pdf-scroller');
        if (!scroller || !shared.currentPdfBlob) return;

        const sequence = bumpRenderSequence();
        const layout = state.normalizeLayout(scroller.dataset.previewLayout || shared.currentLayout);
        const zoomFactor = state.getZoomFactor();
        shared.currentLayout = layout;

        const pdfDocument = await loadPdfDocument();
        if (sequence !== getRenderSequence()) return;

        const pageCount = pdfDocument.numPages || 0;
        if (!pageCount) {
            setStatus('El PDF no contiene páginas.', true);
            return;
        }
        state.rebuildUniversalCounter(pageCount);
        const visiblePages = state.getVisiblePreviewPages(pageCount);

        const samplePage = await pdfDocument.getPage(1);
        const sampleViewport = samplePage.getViewport({ scale: 1 });
        if (typeof samplePage.cleanup === 'function') {
            samplePage.cleanup();
        }

        const scrollerWidth = Math.max(320, scroller.clientWidth || 0);
        const horizontalPadding = layout === 'spread' ? 96 : 56;
        const pageGap = layout === 'spread' ? 18 : 28;
        const visibleWidth = Math.max(240, scrollerWidth - horizontalPadding);
        const pageSlotWidth = layout === 'spread'
            ? Math.max(180, ((visibleWidth - pageGap) / 2))
            : visibleWidth;
        const fitScale = pageSlotWidth / sampleViewport.width;
        const renderScale = Math.max(0.2, Math.min(2.5, fitScale * zoomFactor));
        const pageWidthPx = Math.floor(sampleViewport.width * renderScale);
        const pageHeightPx = Math.floor(sampleViewport.height * renderScale);

        scroller.className = 'flex-1 overflow-auto relative bg-slate-200';
        scroller.innerHTML = '';
        scroller.style.setProperty('--almaden-pdf-page-width', `${pageWidthPx}px`);
        scroller.style.setProperty('--almaden-pdf-page-height', `${pageHeightPx}px`);

        const root = document.createElement('div');
        root.className = layout === 'spread'
            ? 'flex flex-col items-center gap-14 py-10'
            : 'flex flex-col items-center gap-8 py-8';
        root.dataset.visualEditorSurface = '1';
        scroller.appendChild(root);

        if (layout === 'single') {
            for (const pageNumber of visiblePages) {
                if (sequence !== getRenderSequence()) return;
                const shell = makePageShell(pageNumber, pageCount, false);
                const canvas = document.createElement('canvas');
                canvas.className = 'block';
                shell.appendChild(canvas);
                root.appendChild(shell);
                await renderPdfPageToCanvas(pdfDocument, pageNumber, canvas, renderScale, sequence);
            }
        } else {
            const spreadPages = visiblePages.slice();
            const rows = [];
            let cursor = 0;
            if (spreadPages.length > 0 && spreadPages[0] % 2 === 1) {
                rows.push({
                    left: { pageNumber: Math.max(0, spreadPages[0] - 1), blank: true },
                    right: { pageNumber: spreadPages[0], blank: false }
                });
                cursor = 1;
            }
            while (cursor < spreadPages.length) {
                const leftPageNumber = spreadPages[cursor];
                const rightPageNumber = spreadPages[cursor + 1] || 0;
                rows.push({
                    left: {
                        pageNumber: leftPageNumber,
                        blank: false
                    },
                    right: {
                        pageNumber: rightPageNumber || (leftPageNumber + 1),
                        blank: cursor + 1 >= spreadPages.length
                    }
                });
                cursor += 2;
            }

            for (let rowIndex = 0; rowIndex < rows.length; rowIndex += 1) {
                if (sequence !== getRenderSequence()) return;

                const row = document.createElement('div');
                row.className = 'grid items-start justify-center';
                row.style.gridTemplateColumns = `${pageWidthPx}px ${pageWidthPx}px`;
                row.style.columnGap = `${pageGap}px`;

                const leftPage = rows[rowIndex].left;
                const rightPage = rows[rowIndex].right;

                const leftShell = makePageShell(leftPage.pageNumber, pageCount, !!leftPage.blank);
                const rightShell = makePageShell(rightPage.pageNumber, pageCount, !!rightPage.blank);

                if (!leftPage.blank && leftPage.pageNumber > 0) {
                    const canvas = document.createElement('canvas');
                    canvas.className = 'block';
                    leftShell.appendChild(canvas);
                    await renderPdfPageToCanvas(pdfDocument, leftPage.pageNumber, canvas, renderScale, sequence);
                }

                if (!rightPage.blank && rightPage.pageNumber > 0) {
                    const canvas = document.createElement('canvas');
                    canvas.className = 'block';
                    rightShell.appendChild(canvas);
                    await renderPdfPageToCanvas(pdfDocument, rightPage.pageNumber, canvas, renderScale, sequence);
                }

                row.appendChild(leftShell);
                row.appendChild(rightShell);
                root.appendChild(row);
            }
        }

        if (window.almadenPageTemplateUI && typeof window.almadenPageTemplateUI.bind === 'function') {
            window.almadenPageTemplateUI.bind(root);
        }
        if (window.almadenPageTemplateImagesUI && typeof window.almadenPageTemplateImagesUI.bind === 'function') {
            window.almadenPageTemplateImagesUI.bind(root);
        }
        window.almadenTypstImageOverlays?.bind?.(root);
        updateGeometryIndicator();
        updateBleedGuides(root);
        updateTextBounds(root);
    }

    function applyZoom() {
        if (!shared.currentPdfBlob) return;
        renderPdfPreview().catch(error => {
            console.warn('No se pudo aplicar el zoom del PDF Typst.', error);
        });
    }

    function applyLayout(layout) {
        const normalized = state.normalizeLayout(layout);
        shared.currentLayout = normalized;
        const scroller = document.getElementById('pdf-scroller');
        if (scroller) {
            scroller.dataset.previewLayout = normalized;
            scroller.classList.toggle('spread-view', normalized === 'spread');
        }
        if (shared.currentPdfBlob) {
            renderPdfPreview().catch(error => {
                console.warn('No se pudo aplicar el layout del PDF Typst.', error);
            });
        }
    }

    async function showPdf(blob, geometry, integrity = null, cacheSource = '') {
        const scroller = document.getElementById('pdf-scroller');
        if (!scroller) return;

        destroyCurrentPdfDocument();
        if (shared.currentPdfUrl) URL.revokeObjectURL(shared.currentPdfUrl);

        shared.currentPdfUrl = URL.createObjectURL(blob);
        shared.currentPdfBlob = blob;
        shared.currentGeometry = geometry || null;
        shared.currentLayout = state.normalizeLayout(scroller.dataset.previewLayout || shared.currentLayout);

        scroller.className = 'flex-1 overflow-auto relative bg-slate-200';
        scroller.innerHTML = `
            <div class="flex h-full min-h-[240px] w-full items-center justify-center p-8 text-center text-[var(--text-muted)]">
                <div class="max-w-3xl">
                    <i class="fa-solid fa-spinner fa-spin mb-3 text-3xl"></i>
                    <div class="text-sm leading-relaxed">Renderizando páginas con PDF.js...</div>
                </div>
            </div>`;

        try {
            shared.currentPdfBytes = await blob.arrayBuffer();
            ensurePdfJs();
            await renderPdfPreview();
            const indicator = document.getElementById('pdf-page-indicator');
            if (indicator) {
                if (integrity && integrity.status === 'warning') {
                    indicator.textContent = 'PDF generado con advertencia';
                } else if (cacheSource === 'browser' || cacheSource === 'server') {
                    indicator.textContent = 'PDF recuperado del caché';
                } else {
                    indicator.textContent = 'PDF verificado';
                }
            }
        } catch (error) {
            setStatus(`Error al renderizar el PDF Typst: ${error.message}`, true);
            throw error;
        }
    }

    bindTextBoundsToggle();

    window.addEventListener('resize', () => {
        if (shared.currentPdfBlob) {
            renderPdfPreview().catch(error => {
                console.warn('No se pudo re-renderizar el PDF Typst tras resize.', error);
            });
        }
    });

    window.almadenTypstPdfView = {
        showPdf,
        renderPdfPreview,
        applyZoom,
        applyLayout,
        bindTextBoundsToggle,
        updateTextBounds,
        updateBleedGuides,
        updateGeometryIndicator,
        setStatus,
        hasCurrentPreview: () => !!shared.currentPdfBlob,
        refresh: () => {
            if (shared.currentPdfBlob) {
                renderPdfPreview().catch(error => {
                    console.warn('No se pudo refrescar el preview del PDF Typst.', error);
                });
            }
        }
    };
})();
