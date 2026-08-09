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
    let showTextBounds = false;
    let pendingUniversalCounter = null;
    let pendingCompilePromise = null;
    let pendingCompileResolve = null;
    let pendingCompileSignature = null;
    let currentCompileSignature = null;
    const PREVIEW_CACHE_DB = 'almaden-bookster-pdf-preview';
    const PREVIEW_CACHE_STORE = 'compiled-previews';
    const PREVIEW_CACHE_VERSION = 'v2';
    const PREVIEW_CACHE_MAX_AGE = 7 * 24 * 60 * 60 * 1000;

    function getZoomFactor() {
        const select = document.getElementById('pdf-preview-zoom');
        const raw = select ? Number.parseFloat(select.value) : 0.75;
        return Number.isFinite(raw) && raw > 0 ? raw : 0.75;
    }

    function normalizeLayout(value) {
        return String(value ?? 'single') === 'spread' ? 'spread' : 'single';
    }

    function normalizePreviewMode(value) {
        return String(value ?? 'chapter') === 'full' ? 'full' : 'chapter';
    }

    function normalizePreviewAssetMode(value) {
        return String(value ?? 'optimized') === 'original' ? 'original' : 'optimized';
    }

    function getPreviewModeCopy() {
        const mode = normalizePreviewMode(bookState?.pdfPreview?.mode || bookState?.settings?.pdf_preview_mode);
        return mode === 'full'
            ? {
                mode,
                label: 'PDF completo',
                message: 'el PDF completo'
            }
            : {
                mode,
                label: 'capítulo actual',
                message: 'el capítulo actual'
            };
    }

    function getTypstPreviewDebounceMs() {
        const previewMode = normalizePreviewMode(bookState?.pdfPreview?.mode || bookState?.settings?.pdf_preview_mode);
        const chapterCount = Array.isArray(bookState?.chapters) ? bookState.chapters.length : 0;
        if (previewMode === 'chapter') {
            return chapterCount > 20 ? 260 : 220;
        }
        return chapterCount > 20 ? 480 : 360;
    }

    function reportPreviewPerformance(source, startedAt) {
        const durationMs = Math.max(0, Math.round(performance.now() - startedAt));
        if (!bookState.pdfPreview || typeof bookState.pdfPreview !== 'object') {
            bookState.pdfPreview = {};
        }
        bookState.pdfPreview.lastLoad = { source, durationMs, at: Date.now() };
        console.info('[Typst preview performance]', { source, durationMs });
    }

    function stableStringify(value) {
        if (value === null || typeof value !== 'object') {
            return JSON.stringify(value);
        }
        if (Array.isArray(value)) {
            return `[${value.map(item => stableStringify(item)).join(',')}]`;
        }

        const entries = Object.keys(value)
            .sort()
            .filter(key => typeof value[key] !== 'undefined')
            .map(key => `${JSON.stringify(key)}:${stableStringify(value[key])}`);
        return `{${entries.join(',')}}`;
    }

    function getCompilePayloadSignature(compilePayload) {
        try {
            // The counter is compiler output mirrored into bookState. Excluding it
            // prevents a successful compile from invalidating its own cache key.
            // Viewer mode only changes which pages PDF.js paints, not the PDF.
            const preview = compilePayload?.preview && typeof compilePayload.preview === 'object'
                ? compilePayload.preview
                : {};
            const signaturePreview = { ...preview };
            delete signaturePreview.universalCounter;
            delete signaturePreview.mode;
            delete signaturePreview.counterMode;
            const signaturePayload = {
                ...compilePayload,
                preview: signaturePreview
            };
            return stableStringify(signaturePayload);
        } catch (error) {
            console.warn('No se pudo serializar la firma del payload Typst.', error);
            return `${Date.now()}`;
        }
    }

    function getPersistentCacheKey(signature) {
        let left = 2166136261;
        let right = 2246822519;
        for (let index = 0; index < signature.length; index += 1) {
            const code = signature.charCodeAt(index);
            left = Math.imul(left ^ code, 16777619);
            right = Math.imul(right ^ code, 3266489917);
        }
        const digest = `${(left >>> 0).toString(16)}${(right >>> 0).toString(16)}`;
        return `${PREVIEW_CACHE_VERSION}:${bookState.bookId}:${digest}:${signature.length}`;
    }

    function openPreviewCache() {
        if (typeof indexedDB === 'undefined') return Promise.resolve(null);
        return new Promise(resolve => {
            let request;
            try {
                request = indexedDB.open(PREVIEW_CACHE_DB, 1);
            } catch (error) {
                resolve(null);
                return;
            }
            request.onupgradeneeded = () => {
                const database = request.result;
                if (!database.objectStoreNames.contains(PREVIEW_CACHE_STORE)) {
                    const store = database.createObjectStore(PREVIEW_CACHE_STORE, { keyPath: 'key' });
                    store.createIndex('bookId', 'bookId', { unique: false });
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => resolve(null);
            request.onblocked = () => resolve(null);
        });
    }

    async function readPersistentPreview(signature) {
        const database = await openPreviewCache();
        if (!database) return null;
        const key = getPersistentCacheKey(signature);
        return new Promise(resolve => {
            const transaction = database.transaction(PREVIEW_CACHE_STORE, 'readonly');
            const request = transaction.objectStore(PREVIEW_CACHE_STORE).get(key);
            request.onsuccess = () => {
                const record = request.result;
                const fresh = record
                    && record.signatureLength === signature.length
                    && record.createdAt > Date.now() - PREVIEW_CACHE_MAX_AGE
                    && record.blob instanceof Blob
                    && record.blob.size > 0;
                resolve(fresh ? record : null);
            };
            request.onerror = () => resolve(null);
            transaction.oncomplete = () => database.close();
            transaction.onerror = () => database.close();
        });
    }

    async function writePersistentPreview(signature, blob, metadata) {
        if (!(blob instanceof Blob) || !blob.size) return;
        const database = await openPreviewCache();
        if (!database) return;
        const record = {
            key: getPersistentCacheKey(signature),
            bookId: String(bookState.bookId),
            signatureLength: signature.length,
            blob,
            metadata,
            createdAt: Date.now()
        };
        await new Promise(resolve => {
            const transaction = database.transaction(PREVIEW_CACHE_STORE, 'readwrite');
            transaction.objectStore(PREVIEW_CACHE_STORE).put(record);
            transaction.oncomplete = resolve;
            transaction.onerror = resolve;
            transaction.onabort = resolve;
        });

        // Keep a small rolling cache per book so chapter and full modes coexist.
        await new Promise(resolve => {
            const transaction = database.transaction(PREVIEW_CACHE_STORE, 'readwrite');
            const store = transaction.objectStore(PREVIEW_CACHE_STORE);
            const index = store.index('bookId');
            const request = index.getAll(String(bookState.bookId));
            request.onsuccess = () => {
                request.result
                    .sort((left, right) => Number(right.createdAt || 0) - Number(left.createdAt || 0))
                    .slice(6)
                    .forEach(stale => store.delete(stale.key));
            };
            transaction.oncomplete = resolve;
            transaction.onerror = resolve;
            transaction.onabort = resolve;
        });
        database.close();
    }

    function buildTypstCompilePayload(options = {}) {
        const compilePayload = options.compilePayload && typeof options.compilePayload === 'object'
            ? options.compilePayload
            : payload();

        if (options.assetMode) {
            compilePayload.preview = compilePayload.preview && typeof compilePayload.preview === 'object'
                ? { ...compilePayload.preview, assetMode: normalizePreviewAssetMode(options.assetMode) }
                : { assetMode: normalizePreviewAssetMode(options.assetMode) };
        }

        return compilePayload;
    }

    function buildPreviewContract() {
        const preview = bookState?.pdfPreview && typeof bookState.pdfPreview === 'object'
            ? bookState.pdfPreview
            : {};
        const settings = bookState?.settings || {};

        return {
            mode: normalizePreviewMode(preview.mode || settings.pdf_preview_mode),
            assetMode: normalizePreviewAssetMode(preview.assetMode || settings.pdf_preview_asset_mode),
            counterMode: String(preview.counterMode || settings.pdf_preview_counter_mode || 'global') === 'local' ? 'local' : 'global',
            universalCounter: {
                version: Number(preview.universalCounter?.version || 1),
                ready: !!preview.universalCounter?.ready,
                source: String(preview.universalCounter?.source || 'full-book'),
                totals: {
                    pages: preview.universalCounter?.totals?.pages ?? null,
                    blankPages: preview.universalCounter?.totals?.blankPages ?? null,
                    chapters: preview.universalCounter?.totals?.chapters ?? null
                },
                chapters: Array.isArray(preview.universalCounter?.chapters)
                    ? preview.universalCounter.chapters.slice()
                    : []
            }
        };
    }

    function normalizeUniversalCounterEntry(entry, fallback = {}) {
        const chapterId = String(entry?.id || fallback.id || '').trim();
        const startPage = Number.parseInt(entry?.page ?? fallback.page ?? 0, 10) || 0;
        const sequence = Number.parseInt(entry?.sequence ?? fallback.sequence ?? 0, 10) || 0;
        const kind = String(entry?.kind || fallback.kind || 'chapter');
        const sourceChapter = Array.isArray(bookState?.chapters)
            ? bookState.chapters.find(chapter => String(chapter?.id || '') === chapterId)
            : null;

        return {
            sequence: sequence > 0 ? sequence : null,
            id: chapterId,
            title: String(sourceChapter?.title || fallback.title || entry?.title || '').trim(),
            kind,
            startPage: startPage > 0 ? startPage : null
        };
    }

    function rebuildUniversalCounter(totalPages = null) {
        const preview = bookState?.pdfPreview?.universalCounter && typeof bookState.pdfPreview.universalCounter === 'object'
            ? bookState.pdfPreview.universalCounter
            : {};
        const rawChapters = Array.isArray(pendingUniversalCounter?.chapters)
            ? pendingUniversalCounter.chapters
            : Array.isArray(preview.chapters)
                ? preview.chapters
                : [];
        const normalizedChapters = rawChapters
            .map((entry, index) => normalizeUniversalCounterEntry(entry, { sequence: index + 1 }))
            .filter(entry => entry.id || entry.startPage);

        const sortedChapters = normalizedChapters
            .slice()
            .sort((left, right) => {
                const leftPage = Number(left.startPage || Number.MAX_SAFE_INTEGER);
                const rightPage = Number(right.startPage || Number.MAX_SAFE_INTEGER);
                if (leftPage !== rightPage) return leftPage - rightPage;
                return Number(left.sequence || 0) - Number(right.sequence || 0);
            });

        const resolvedChapters = sortedChapters
            .filter(chapter => Number(chapter.startPage || 0) > 0)
            .map((chapter, index, filtered) => {
                const nextStart = filtered[index + 1] ? Number(filtered[index + 1].startPage || 0) : 0;
                const startPage = Number(chapter.startPage || 0);
                const endPage = nextStart > startPage ? nextStart - 1 : (Number.isFinite(totalPages) && totalPages > 0 ? totalPages : null);
                const pageCount = startPage > 0 && Number.isFinite(endPage) ? Math.max(0, endPage - startPage + 1) : null;

                return {
                    ...chapter,
                    startPage: startPage > 0 ? startPage : null,
                    endPage: Number.isFinite(endPage) && endPage > 0 ? endPage : null,
                    pageCount,
                    localStartPage: startPage > 0 ? 1 : null,
                    globalOffset: startPage > 0 ? startPage - 1 : null
                };
            });

        const pages = Number.isFinite(totalPages) && totalPages > 0
            ? totalPages
            : Number(preview.totals?.pages || 0) || null;

        const universalCounter = {
            version: Number(preview.version || pendingUniversalCounter?.version || 1),
            ready: !!pages && resolvedChapters.length > 0,
            source: String(preview.source || pendingUniversalCounter?.source || 'full-book'),
            totals: {
                pages,
                blankPages: preview.totals?.blankPages ?? null,
                chapters: resolvedChapters.length
            },
            chapters: resolvedChapters
        };

        if (!bookState.pdfPreview || typeof bookState.pdfPreview !== 'object') {
            bookState.pdfPreview = {};
        }
        bookState.pdfPreview.universalCounter = universalCounter;
        if (window.almadenTypstPdf && typeof window.almadenTypstPdf === 'object') {
            window.almadenTypstPdf.state = buildPreviewContract();
            window.almadenTypstPdf.state.universalCounter = universalCounter;
        }

        return universalCounter;
    }

    function getUniversalCounter() {
        return bookState?.pdfPreview?.universalCounter && typeof bookState.pdfPreview.universalCounter === 'object'
            ? bookState.pdfPreview.universalCounter
            : null;
    }

    function getActiveChapterCounterEntry() {
        const counter = getUniversalCounter();
        if (!counter || !counter.ready || !Array.isArray(counter.chapters)) {
            return null;
        }
        const activeChapterId = String(bookState?.activeChapterId || '').trim();
        if (!activeChapterId) {
            return null;
        }
        return counter.chapters.find(entry => String(entry?.id || '') === activeChapterId) || null;
    }

    function getVisiblePreviewPages(pageCount) {
        const mode = normalizePreviewMode(bookState?.pdfPreview?.mode || bookState?.settings?.pdf_preview_mode);
        if ('chapter' !== mode) {
            return Array.from({ length: pageCount }, (_, index) => index + 1);
        }

        const counter = getUniversalCounter();
        const chapter = getActiveChapterCounterEntry();
        if (!counter || !chapter) {
            return Array.from({ length: pageCount }, (_, index) => index + 1);
        }

        const startPage = Math.max(1, Number(chapter.startPage || 0));
        const endPage = Math.max(startPage, Number(chapter.endPage || startPage || 0));
        if (!startPage || !endPage) {
            return Array.from({ length: pageCount }, (_, index) => index + 1);
        }

        const safeStart = Math.max(1, Math.min(pageCount, startPage));
        const safeEnd = Math.max(safeStart, Math.min(pageCount, endPage));
        const pages = [];
        for (let pageNumber = safeStart; pageNumber <= safeEnd; pageNumber += 1) {
            pages.push(pageNumber);
        }
        return pages.length ? pages : Array.from({ length: pageCount }, (_, index) => index + 1);
    }

    function updateGeometryIndicator() {
        const indicator = document.getElementById('pdf-geometry-indicator');
        if (!indicator || !currentGeometry) return;
        const g = currentGeometry;
        indicator.textContent = `${g.width} × ${g.height} ${g.unit} · márgenes ${g.inside}/${g.outside} ${g.unit}`;
        const contentTop = g.content_top ?? g.top;
        const contentBottom = g.content_bottom ?? g.bottom;
        indicator.title = `Hoja ${g.width} × ${g.height} ${g.unit}. Área de texto: superior ${contentTop}, inferior ${contentBottom}, interior ${g.inside}, exterior ${g.outside} ${g.unit}. Sangrado ${g.bleed} ${g.unit}.`;
    }

    function getTextBounds(pageNumber) {
        const g = currentGeometry || {};
        const width = Number.parseFloat(g.width);
        const height = Number.parseFloat(g.height);
        const top = Number.parseFloat(g.content_top ?? g.top);
        const bottom = Number.parseFloat(g.content_bottom ?? g.bottom);
        const inside = Number.parseFloat(g.inside);
        const outside = Number.parseFloat(g.outside);

        if (![width, height, top, bottom, inside, outside].every(Number.isFinite) || width <= 0 || height <= 0) {
            return null;
        }

        // Typst uses left binding: odd pages have their inside margin on the left.
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

    function updateTextBounds(root = document.getElementById('pdf-scroller')) {
        if (!root) return;

        root.querySelectorAll('[data-text-bounds-overlay]').forEach(overlay => overlay.remove());
        if (!showTextBounds) return;

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

        toggle.setAttribute('aria-pressed', showTextBounds ? 'true' : 'false');
        toggle.title = showTextBounds ? 'Ocultar límites del área de texto' : 'Mostrar límites del área de texto';
        toggle.setAttribute('aria-label', toggle.title);
        toggle.classList.toggle('border-cyan-600', showTextBounds);
        toggle.classList.toggle('bg-cyan-50', showTextBounds);
        toggle.classList.toggle('text-cyan-800', showTextBounds);
    }

    function bindTextBoundsToggle() {
        const toggle = document.getElementById('pdf-text-bounds-toggle');
        if (!toggle || toggle.dataset.bound === '1') return;

        toggle.dataset.bound = '1';
        toggle.addEventListener('click', () => {
            showTextBounds = !showTextBounds;
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
                    <div class="text-sm leading-relaxed">${String(message).replace(/[&<>"']/g, char => ({
                        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
                    }[char]))}</div>
                </div>
            </div>`;
    }

    function payload() {
        // RAW is the canonical manuscript. A rendered PDF/visual surface is an
        // output artifact and must never overwrite chapter content while a
        // preview is being prepared.
        const rawChapters = Array.isArray(bookState.chapters) ? bookState.chapters : [];
        let chapters = rawChapters.map(chapter => {
            const copy = { ...chapter };
            delete copy._lastSavedContent;
            return copy;
        });

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
            preview: buildPreviewContract(),
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
        rebuildUniversalCounter(pageCount);
        const visiblePages = getVisiblePreviewPages(pageCount);

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
        root.dataset.visualEditorSurface = '1';
        scroller.appendChild(root);

        if (layout === 'single') {
            for (const pageNumber of visiblePages) {
                if (sequence !== renderSequence) return;
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
                if (sequence !== renderSequence) return;

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
        updateGeometryIndicator();
        updateTextBounds(root);
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

    function applyPreviewMetadata(metadata = {}) {
        currentGeometry = metadata.geometry || currentGeometry;
        window.almadenTypstOpeningDebug = metadata.openingDebug || [];
        window.almadenPageTemplateFlowMap = metadata.pageFlowMap || [];
        window.almadenPageTemplateResults = metadata.pageTemplateResults || [];
        pendingUniversalCounter = metadata.universalCounter || null;
        rebuildUniversalCounter();
        window.almadenPageTemplateState?.reconcileResults?.();
    }

    function snapshotPreviewMetadata(geometry, integrity) {
        return {
            geometry: geometry || null,
            integrity: integrity || null,
            openingDebug: window.almadenTypstOpeningDebug || [],
            pageFlowMap: window.almadenPageTemplateFlowMap || [],
            pageTemplateResults: window.almadenPageTemplateResults || [],
            universalCounter: pendingUniversalCounter || getUniversalCounter() || null
        };
    }

    async function showPdf(blob, geometry, integrity = null, cacheSource = '') {
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

    async function compileTypstPreview(options = {}) {
        const startedAt = performance.now();
        const compilePayload = buildTypstCompilePayload(options);
        const compileSignature = getCompilePayloadSignature(compilePayload);

        if (!options.forceRecompile && currentPdfBlob && currentCompileSignature === compileSignature) {
            await renderPdfPreview();
            reportPreviewPerformance('memory', startedAt);
            return 1;
        }

        const sequence = ++compileSequence;
        if (activeController) activeController.abort();
        activeController = new AbortController();
        pendingUniversalCounter = null;
        const previewModeCopy = getPreviewModeCopy();
        if (!bookState.pdfPreview || typeof bookState.pdfPreview !== 'object') {
            bookState.pdfPreview = {};
        }
        bookState.pdfPreview.lastCompileSignature = compileSignature;

        if (!options.bypassPersistentCache) {
            setStatus('Cargando la última versión disponible del PDF...');
            const cachedPreview = await readPersistentPreview(compileSignature);
            if (sequence !== compileSequence) return 0;
            if (cachedPreview) {
                const cachedMetadata = cachedPreview.metadata || {};
                applyPreviewMetadata(cachedMetadata);
                await showPdf(cachedPreview.blob, cachedMetadata.geometry, cachedMetadata.integrity, 'browser');
                currentCompileSignature = compileSignature;
                window.pdfContentIntegrity = cachedMetadata.integrity?.status === 'warning'
                    ? { valid: true, engine: 'typst', warning: cachedMetadata.integrity.message, cache: 'browser' }
                    : { valid: true, engine: 'typst', cache: 'browser' };
                reportPreviewPerformance('browser-cache', startedAt);
                return 1;
            }
        }

        setStatus(`Componiendo ${previewModeCopy.message} con Typst y renderizando el PDF...`);
        console.info('[Typst opening layout: request]', {
            alignment: compilePayload?.settings?.chapter_page_one_align || '',
            separateOpening: compilePayload?.settings?.book_separate_opening_content,
            chapters: (compilePayload?.chapters || []).map(chapter => ({
                id: chapter?.id,
                title: chapter?.title,
                separateOpening: chapter?.opening_separate_content,
                hideOpening: chapter?.hide_opening,
            })),
        });

        const form = new FormData();
        form.append('action', 'almaden_compile_typst_pdf');
        form.append('nonce', bookState.nonce);
        form.append('book_id', String(bookState.bookId));
        form.append('payload', JSON.stringify(compilePayload));

        try {
            const response = await fetch(bookState.ajaxUrl, {
                method: 'POST',
                body: form,
                credentials: 'same-origin',
                signal: activeController.signal
            });
            if (!response.ok) throw new Error(await readError(response));

            let geometry = null;
            const serverCacheStatus = response.headers.get('X-Almaden-Typst-Cache') || 'MISS-NOSTORE';
            const serverCacheHit = serverCacheStatus === 'HIT';
            console.info('[Typst preview cache]', { status: serverCacheStatus });
            const geometryHeader = response.headers.get('X-Almaden-PDF-Geometry');
            if (geometryHeader) {
                try {
                    geometry = JSON.parse(decodeURIComponent(geometryHeader));
                } catch (error) {
                    console.warn('No se pudo leer la geometría del PDF.', error);
                }
            }

            const openingDebugHeader = response.headers.get('X-Almaden-Typst-Opening-Debug');
            if (openingDebugHeader) {
                try {
                    window.almadenTypstOpeningDebug = JSON.parse(decodeURIComponent(openingDebugHeader));
                    console.info('[Typst opening layout: document]', window.almadenTypstOpeningDebug);
                } catch (error) {
                    console.warn('No se pudo leer el diagnóstico de apertura Typst.', error);
                }
            }

			let integrity = null;
			const pageFlowHeader = response.headers.get('X-Almaden-Page-Flow');
			if (pageFlowHeader) {
				try {
					window.almadenPageTemplateFlowMap = JSON.parse(decodeURIComponent(pageFlowHeader));
				} catch (error) {
					console.warn('No se pudo leer el mapa de flujo Typst.', error);
				}
			}
			const pageTemplateResultsHeader = response.headers.get('X-Almaden-Page-Template-Results');
            if (pageTemplateResultsHeader) {
                try {
                    window.almadenPageTemplateResults = JSON.parse(decodeURIComponent(pageTemplateResultsHeader));
                    window.almadenPageTemplateState?.reconcileResults?.();
					console.info(
						'Typst page-template results:',
						window.almadenPageTemplateResults.map(result => ({
							instance_id: result?.instance_id,
							page: result?.resolved_page || result?.page,
							anchor: result?.anchor?.flow_id || '',
							flow_rows: result?.flow_rows,
							applied: result?.applied,
							reason: result?.debug?.reason || '',
							fallback_used: !!result?.debug?.fallback_used,
							selected_ids: Array.isArray(result?.debug?.selected_ids) ? result.debug.selected_ids : [],
						}))
					);
					const skipped = window.almadenPageTemplateResults.find(result => result && !result.applied && result?.debug?.reason !== 'no_rows_for_legacy_page');
					if (skipped && typeof window.showToast === 'function') {
						const reason = skipped?.debug?.reason ? ` (${skipped.debug.reason})` : '';
						window.showToast(`No se pudo aplicar la plantilla en la página ${skipped.page}${reason}.`, 'fa-solid fa-circle-exclamation');
                    }
                } catch (error) {
                    console.warn('No se pudo leer el resultado de plantillas Typst.', error);
                }
            }
            const universalCounterHeader = response.headers.get('X-Almaden-Universal-Counter');
            if (universalCounterHeader) {
                try {
                    pendingUniversalCounter = JSON.parse(decodeURIComponent(universalCounterHeader));
                    rebuildUniversalCounter();
                } catch (error) {
                    console.warn('No se pudo leer el contador universal Typst.', error);
                }
            } else {
                pendingUniversalCounter = null;
                rebuildUniversalCounter();
            }
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

            const metadata = snapshotPreviewMetadata(geometry, integrity);
            await showPdf(blob, geometry, integrity, serverCacheHit ? 'server' : '');
            currentCompileSignature = compileSignature;
            writePersistentPreview(compileSignature, blob, metadata).catch(error => {
                console.warn('No se pudo guardar el caché local del PDF.', error);
            });
            window.pdfContentIntegrity = integrity && integrity.status === 'warning'
                ? { valid: true, engine: 'typst', warning: integrity.message, cache: serverCacheHit ? 'server' : 'miss' }
                : { valid: true, engine: 'typst', cache: serverCacheHit ? 'server' : 'miss' };
            if (integrity && integrity.status === 'warning') {
                console.warn('Typst PDF integrity warning:', integrity.message);
            }
            reportPreviewPerformance(serverCacheHit ? 'server-cache' : 'typst', startedAt);
            return 1;
        } catch (error) {
            if (error.name === 'AbortError') return 0;
            console.warn('[Typst compile failed]', {
                message: error.message,
                pageTemplates: window.bookState?.settings?.page_templates || [],
                pageTemplateResults: window.almadenPageTemplateResults || [],
                openingDebug: window.almadenTypstOpeningDebug || null,
                pageFlowMap: window.almadenPageTemplateFlowMap || [],
            });
            window.pdfContentIntegrity = { valid: false, engine: 'typst', error: error.message };
            setStatus(`Error en el maquetador Typst: ${error.message}`, true);
            return 0;
        }
    }

    function scheduleTypstPreview(scrollToActive = false, targetScrollerId = 'pdf-scroller', forceFull = false) {
        if (targetScrollerId !== 'pdf-scroller') return Promise.resolve(0);
        const compilePayload = buildTypstCompilePayload();
        const compileSignature = getCompilePayloadSignature(compilePayload);
        const debounceMs = forceFull ? 0 : getTypstPreviewDebounceMs();

        if (forceFull) {
            if (pendingCompileResolve) {
                pendingCompileResolve(0);
                pendingCompileResolve = null;
            }
            clearTimeout(compileTimer);
            pendingCompilePromise = null;
            pendingCompileSignature = null;
            return compileTypstPreview({ compilePayload });
        }

        if (compileTimer && pendingCompileSignature === compileSignature && pendingCompilePromise) {
            return pendingCompilePromise;
        }

        if (pendingCompileResolve) {
            pendingCompileResolve(0);
            pendingCompileResolve = null;
        }

        clearTimeout(compileTimer);
        pendingCompileSignature = compileSignature;
        pendingCompilePromise = new Promise(resolve => {
            pendingCompileResolve = resolve;
            compileTimer = setTimeout(() => {
                const resolvePending = pendingCompileResolve;
                pendingCompileResolve = null;
                pendingCompilePromise = null;
                pendingCompileSignature = null;
                compileTypstPreview({ compilePayload }).then(result => {
                    if (typeof resolvePending === 'function') {
                        resolvePending(result);
                    }
                });
            }, debounceMs);
        });

        return pendingCompilePromise;
    }

    async function downloadTypstPdf() {
        const button = document.getElementById('btn-export-pdf');
        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span class="hidden sm:inline">Compilando...</span>';
        }
        try {
            const valid = await compileTypstPreview({ assetMode: 'original' });
            if (!valid || !currentPdfBlob || !currentPdfUrl) return;
            const link = document.createElement('a');
            const safeTitle = String(bookState.title || 'libro').trim().replace(/[^\p{L}\p{N}._-]+/gu, '-');
            link.href = currentPdfUrl;
            link.download = `${safeTitle || 'libro'}.pdf`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            if (normalizePreviewAssetMode(bookState?.pdfPreview?.assetMode || bookState?.settings?.pdf_preview_asset_mode) !== 'original') {
                window.setTimeout(() => {
                    window.compilePDFPreview?.(true, 'pdf-scroller', true);
                }, 0);
            }
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
        state: buildPreviewContract(),
        compile: compileTypstPreview,
        download: downloadTypstPdf,
        applyZoom,
        applyLayout,
        hasCurrentPreview: () => !!currentPdfBlob,
        refresh: () => {
            if (currentPdfBlob) {
                renderPdfPreview().catch(error => {
                    console.warn('No se pudo refrescar el preview del PDF Typst.', error);
                });
            }
        }
    };

    bindTextBoundsToggle();

    window.addEventListener('resize', () => {
        if (currentPdfBlob) {
            renderPdfPreview().catch(error => {
                console.warn('No se pudo re-renderizar el PDF Typst tras resize.', error);
            });
        }
    });
})();
