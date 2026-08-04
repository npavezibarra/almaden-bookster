// Typst-backed PDF preview rendered with PDF.js.
// The RAW manuscript remains the only source; the viewer only changes how
// the compiled PDF is displayed on screen.
(function () {
    const PDFJS_WORKER_SRC = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    let currentPdfUrl = '';
    let currentPdfBlob = null;
    let currentPdfBytes = null;
    let currentPdfDocument = null;
    let compileTimer = null;
    let compileSequence = 0;
    let renderSequence = 0;
    let activeController = null;
    let currentGeometry = null;
    let currentLayout = 'single';

    function getZoomFactor() {
        const select = document.getElementById('pdf-preview-zoom');
        const raw = select ? Number.parseFloat(select.value) : 0.75;
        return Number.isFinite(raw) && raw > 0 ? raw : 0.75;
    }

    function normalizeLayout(value) {
        return String(value ?? 'single') === 'spread' ? 'spread' : 'single';
    }

    function updateGeometryIndicator() {
        const indicator = document.getElementById('pdf-geometry-indicator');
        if (!indicator || !currentGeometry) return;
        const g = currentGeometry;
        indicator.textContent = `${g.width} × ${g.height} ${g.unit} · márgenes ${g.inside}/${g.outside} ${g.unit}`;
        indicator.title = `Hoja ${g.width} × ${g.height} ${g.unit}. Márgenes efectivos: superior ${g.top}, inferior ${g.bottom}, interior ${g.inside}, exterior ${g.outside} ${g.unit}. Sangrado ${g.bleed} ${g.unit}.`;
    }

    function setStatus(message, isError = false) {
        const scroller = document.getElementById('pdf-scroller');
        if (!scroller) return;
        scroller.className = 'flex-1 overflow-hidden relative bg-slate-200';
        scroller.innerHTML = `
            <div class="flex h-full min-h-[240px] w-full items-center justify-center p-8 text-center ${isError ? 'text-red-500' : 'text-[var(--text-muted)]'}">
                <div class="max-w-3xl">
                    <i class="fa-solid ${isError ? 'fa-triangle-exclamation' : 'fa-spinner fa-spin'} mb-3 text-3xl"></i>
                    <div class="text-sm leading-relaxed">${String(message).replace(/[&<>"']/g, char => ({
                        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                    }[char]))}</div>
                </div>
            </div>`;
    }

    function payload() {
        if (typeof syncVisualEditorToState === 'function') {
            syncVisualEditorToState();
        } else if (typeof syncRawEditorToState === 'function') {
            syncRawEditorToState();
        }

        const rawChapters = Array.isArray(bookState.chapters) ? bookState.chapters : [];
        let chapters = rawChapters.map(chapter => ({ ...chapter }));

        if (!chapters.length) {
            const activeChapterId = String(bookState.activeChapterId || 'cap-1');
            const titleInput = document.getElementById('chapter-title-input');
            const textarea = typeof getRawEditorSurface === 'function' ? getRawEditorSurface() : null;
            const rawContent = textarea && typeof textarea.value === 'string' ? textarea.value : '';
            const fallbackTitle = titleInput && String(titleInput.value || '').trim()
                ? String(titleInput.value).trim()
                : String(bookState.title || 'Capítulo 1').trim();

            chapters = [{
                id: activeChapterId,
                title: fallbackTitle || 'Capítulo 1',
                content: rawContent
            }];
        }

        return {
            title: bookState.title || '',
            settings: bookState.settings || {},
            coverSettings: bookState.coverSettings || bookState.cover_settings || (bookState.settings && (bookState.settings.coverSettings || bookState.settings.cover_settings)) || {},
            chapters
        };
    }

    async function readError(response) {
        try {
            const data = await response.json();
            return data?.data?.message || data?.message || `Error HTTP ${response.status}`;
        } catch (error) {
            return `El compilador devolvió una respuesta inválida (HTTP ${response.status}).`;
        }
    }

    async function ensurePdfJs() {
        if (!window.pdfjsLib) {
            throw new Error('PDF.js no se cargó en el editor.');
        }
        if (window.pdfjsLib.GlobalWorkerOptions && !window.pdfjsLib.GlobalWorkerOptions.workerSrc) {
            window.pdfjsLib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER_SRC;
        }
    }

    function destroyCurrentPdfDocument() {
        if (currentPdfDocument && typeof currentPdfDocument.destroy === 'function') {
            try {
                currentPdfDocument.destroy();
            } catch (error) {
                // Swallow cleanup failures; a new compile will replace it.
            }
        }
        currentPdfDocument = null;
        currentPdfBytes = null;
    }

    async function loadPdfDocument() {
        if (currentPdfDocument) {
            return currentPdfDocument;
        }
        await ensurePdfJs();
        if (!currentPdfBytes && currentPdfBlob) {
            currentPdfBytes = await currentPdfBlob.arrayBuffer();
        }
        if (!currentPdfBytes) {
            throw new Error('No hay PDF disponible para renderizar.');
        }
        const loadingTask = window.pdfjsLib.getDocument({
            data: currentPdfBytes,
            useWorkerFetch: false
        });
        currentPdfDocument = await loadingTask.promise;
        return currentPdfDocument;
    }

    function makePageShell(pageNumber, totalPages, isBlank = false) {
        const shell = document.createElement('div');
        shell.className = 'relative overflow-hidden bg-white border border-slate-300 shadow-[0_10px_28px_rgba(15,23,42,0.12)]';
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
        if (sequence !== renderSequence) {
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
        if (!scroller || !currentPdfBlob) return;

        const sequence = ++renderSequence;
        const layout = normalizeLayout(scroller.dataset.previewLayout || currentLayout);
        const zoomFactor = getZoomFactor();
        currentLayout = layout;

        const pdfDocument = await loadPdfDocument();
        if (sequence !== renderSequence) return;

        const pageCount = pdfDocument.numPages || 0;
        if (!pageCount) {
            setStatus('El PDF no contiene páginas.', true);
            return;
        }

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
            ? 'flex flex-col items-center gap-8 py-8'
            : 'flex flex-col items-center gap-8 py-8';
        scroller.appendChild(root);

        if (layout === 'single') {
            for (let pageNumber = 1; pageNumber <= pageCount; pageNumber += 1) {
                if (sequence !== renderSequence) return;
                const shell = makePageShell(pageNumber, pageCount, false);
                const canvas = document.createElement('canvas');
                canvas.className = 'block';
                shell.appendChild(canvas);
                root.appendChild(shell);
                await renderPdfPageToCanvas(pdfDocument, pageNumber, canvas, renderScale, sequence);
            }
        } else {
            const totalPhysicalPages = pageCount % 2 === 0 ? pageCount : pageCount + 1;
            const totalRows = Math.ceil((totalPhysicalPages + 1) / 2);

            for (let rowIndex = 0; rowIndex < totalRows; rowIndex += 1) {
                if (sequence !== renderSequence) return;

                const row = document.createElement('div');
                row.className = 'grid items-start justify-center';
                row.style.gridTemplateColumns = `${pageWidthPx}px ${pageWidthPx}px`;
                row.style.columnGap = `${pageGap}px`;

                const leftPageNumber = rowIndex === 0 ? 0 : rowIndex * 2;
                const rightPageNumber = rowIndex === 0 ? 1 : (rowIndex * 2) + 1;

                const leftBlank = rowIndex === 0 || leftPageNumber > pageCount;
                const rightBlank = rightPageNumber > pageCount;

                const leftShell = makePageShell(leftPageNumber || 0, pageCount, leftBlank);
                const rightShell = makePageShell(rightPageNumber, pageCount, rightBlank);

                if (!leftBlank && leftPageNumber > 0) {
                    const canvas = document.createElement('canvas');
                    canvas.className = 'block';
                    leftShell.appendChild(canvas);
                    await renderPdfPageToCanvas(pdfDocument, leftPageNumber, canvas, renderScale, sequence);
                }

                if (!rightBlank) {
                    const canvas = document.createElement('canvas');
                    canvas.className = 'block';
                    rightShell.appendChild(canvas);
                    await renderPdfPageToCanvas(pdfDocument, rightPageNumber, canvas, renderScale, sequence);
                }

                row.appendChild(leftShell);
                row.appendChild(rightShell);
                root.appendChild(row);
            }
        }

        updateGeometryIndicator();
    }

    function applyZoom() {
        if (!currentPdfBlob) return;
        renderPdfPreview().catch(error => {
            console.warn('No se pudo aplicar el zoom del PDF Typst.', error);
        });
    }

    function applyLayout(layout) {
        const normalized = normalizeLayout(layout);
        currentLayout = normalized;
        const scroller = document.getElementById('pdf-scroller');
        if (scroller) {
            scroller.dataset.previewLayout = normalized;
            scroller.classList.toggle('spread-view', normalized === 'spread');
        }
        if (currentPdfBlob) {
            renderPdfPreview().catch(error => {
                console.warn('No se pudo aplicar el layout del PDF Typst.', error);
            });
        }
    }

    async function showPdf(blob, geometry, integrity = null) {
        const scroller = document.getElementById('pdf-scroller');
        if (!scroller) return;

        destroyCurrentPdfDocument();
        if (currentPdfUrl) URL.revokeObjectURL(currentPdfUrl);

        currentPdfUrl = URL.createObjectURL(blob);
        currentPdfBlob = blob;
        currentGeometry = geometry || null;
        currentLayout = normalizeLayout(scroller.dataset.previewLayout || currentLayout);

        scroller.className = 'flex-1 overflow-auto relative bg-slate-200';
        scroller.innerHTML = `
            <div class="flex h-full min-h-[240px] w-full items-center justify-center p-8 text-center text-[var(--text-muted)]">
                <div class="max-w-3xl">
                    <i class="fa-solid fa-spinner fa-spin mb-3 text-3xl"></i>
                    <div class="text-sm leading-relaxed">Renderizando páginas con PDF.js...</div>
                </div>
            </div>`;

        try {
            currentPdfBytes = await blob.arrayBuffer();
            await ensurePdfJs();
            await renderPdfPreview();
            const indicator = document.getElementById('pdf-page-indicator');
            if (indicator) {
                indicator.textContent = integrity && integrity.status === 'warning'
                    ? 'PDF generado con advertencia'
                    : 'PDF verificado';
            }
        } catch (error) {
            setStatus(`Error al renderizar el PDF Typst: ${error.message}`, true);
            throw error;
        }
    }

    async function compileTypstPreview() {
        const sequence = ++compileSequence;
        if (activeController) activeController.abort();
        activeController = new AbortController();
        setStatus('Componiendo el libro con Typst y renderizando el PDF...');

        const form = new FormData();
        form.append('action', 'almaden_compile_typst_pdf');
        form.append('nonce', bookState.nonce);
        form.append('book_id', String(bookState.bookId));
        form.append('payload', JSON.stringify(payload()));

        try {
            const response = await fetch(bookState.ajaxUrl, {
                method: 'POST',
                body: form,
                credentials: 'same-origin',
                signal: activeController.signal
            });
            if (!response.ok) throw new Error(await readError(response));

            let geometry = null;
            const geometryHeader = response.headers.get('X-Almaden-PDF-Geometry');
            if (geometryHeader) {
                try {
                    geometry = JSON.parse(decodeURIComponent(geometryHeader));
                } catch (error) {
                    console.warn('No se pudo leer la geometría del PDF.', error);
                }
            }

            let integrity = null;
            const integrityHeader = response.headers.get('X-Almaden-PDF-Integrity');
            if (integrityHeader) {
                try {
                    integrity = JSON.parse(decodeURIComponent(integrityHeader));
                } catch (error) {
                    console.warn('No se pudo leer la integridad del PDF.', error);
                }
            }

            const blob = await response.blob();
            if (sequence !== compileSequence) return 0;
            if (blob.type && blob.type !== 'application/pdf') {
                throw new Error('El servidor no devolvió un archivo PDF.');
            }

            await showPdf(blob, geometry, integrity);
            window.pdfContentIntegrity = integrity && integrity.status === 'warning'
                ? { valid: true, engine: 'typst', warning: integrity.message }
                : { valid: true, engine: 'typst' };
            if (integrity && integrity.status === 'warning') {
                console.warn('Typst PDF integrity warning:', integrity.message);
            }
            return 1;
        } catch (error) {
            if (error.name === 'AbortError') return 0;
            window.pdfContentIntegrity = { valid: false, engine: 'typst', error: error.message };
            setStatus(`Error en el maquetador Typst: ${error.message}`, true);
            return 0;
        }
    }

    function scheduleTypstPreview(scrollToActive = false, targetScrollerId = 'pdf-scroller', forceFull = false) {
        if (targetScrollerId !== 'pdf-scroller') return Promise.resolve(0);
        clearTimeout(compileTimer);
        return new Promise(resolve => {
            compileTimer = setTimeout(() => compileTypstPreview().then(resolve), forceFull ? 0 : 350);
        });
    }

    async function downloadTypstPdf() {
        const button = document.getElementById('btn-export-pdf');
        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span class="hidden sm:inline">Compilando...</span>';
        }
        try {
            const valid = await compileTypstPreview();
            if (!valid || !currentPdfBlob || !currentPdfUrl) return;
            const link = document.createElement('a');
            const safeTitle = String(bookState.title || 'libro').trim().replace(/[^\p{L}\p{N}._-]+/gu, '-');
            link.href = currentPdfUrl;
            link.download = `${safeTitle || 'libro'}.pdf`;
            document.body.appendChild(link);
            link.click();
            link.remove();
        } finally {
            if (button) {
                button.disabled = false;
                button.innerHTML = '<i class="fa-solid fa-file-pdf text-sm"></i><span class="hidden sm:inline">Descargar PDF</span>';
            }
        }
    }

    window.compilePDFPreview = scheduleTypstPreview;
    window.triggerPrint = downloadTypstPdf;
    window.almadenTypstPdf = {
        compile: compileTypstPreview,
        download: downloadTypstPdf,
        applyZoom,
        applyLayout
    };

    window.addEventListener('resize', () => {
        if (currentPdfBlob) {
            renderPdfPreview().catch(error => {
                console.warn('No se pudo re-renderizar el PDF Typst tras resize.', error);
            });
        }
    });
})();
