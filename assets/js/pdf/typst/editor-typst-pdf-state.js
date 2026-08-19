(function () {
    if (window.almadenTypstPdfState) return;

    const shared = window.almadenTypstPdfShared = window.almadenTypstPdfShared || {
        currentPdfUrl: '',
        currentPdfBlob: null,
        currentPdfBytes: null,
        currentPdfDocument: null,
        currentGeometry: null,
        currentLayout: 'single',
        showTextBounds: false,
        pendingUniversalCounter: null,
        imageBlocks: []
    };

    const PDFJS_WORKER_SRC = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    const PREVIEW_CACHE_DB = 'almaden-bookster-pdf-preview';
    const PREVIEW_CACHE_STORE = 'compiled-previews';
    // Bump whenever server-side pagination or running-header/footer semantics
    // change; the persistent key otherwise reuses a PDF compiled by old code.
    const PREVIEW_CACHE_VERSION = 'v11';
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

    function syncPublicState() {
        if (window.almadenTypstPdf && typeof window.almadenTypstPdf === 'object') {
            window.almadenTypstPdf.state = buildPreviewContract();
        }
    }

    function rebuildUniversalCounter(totalPages = null) {
        const preview = bookState?.pdfPreview?.universalCounter && typeof bookState.pdfPreview.universalCounter === 'object'
            ? bookState.pdfPreview.universalCounter
            : {};
        const rawChapters = Array.isArray(shared.pendingUniversalCounter?.chapters)
            ? shared.pendingUniversalCounter.chapters
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
            version: Number(preview.version || shared.pendingUniversalCounter?.version || 1),
            ready: !!pages && resolvedChapters.length > 0,
            source: String(preview.source || shared.pendingUniversalCounter?.source || 'full-book'),
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
        syncPublicState();
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

        const chapterIndex = Array.isArray(counter.chapters)
            ? counter.chapters.findIndex(entry => String(entry?.id || '') === String(chapter.id || ''))
            : -1;
        // The first chapter owns the physical opening of the book. Include all
        // structural pages before its content so page 1 is visible in chapter
        // mode when an even-page start inserts an opening parity page.
        const visibleStart = chapterIndex === 0 ? 1 : startPage;
        const safeStart = Math.max(1, Math.min(pageCount, visibleStart));
        const safeEnd = Math.max(safeStart, Math.min(pageCount, endPage));
        const pages = [];
        for (let pageNumber = safeStart; pageNumber <= safeEnd; pageNumber += 1) {
            pages.push(pageNumber);
        }
        return pages.length ? pages : Array.from({ length: pageCount }, (_, index) => index + 1);
    }

    function payload() {
        // The active textarea is the source of truth. Sync it before snapshotting
        // chapters so Typst never receives an earlier image placeholder.
        if (typeof syncRawEditorToState === 'function') {
            syncRawEditorToState();
        }
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

    function snapshotPreviewMetadata(geometry, integrity) {
        return {
            geometry: geometry || null,
            integrity: integrity || null,
            openingDebug: window.almadenTypstOpeningDebug || [],
            pageFlowMap: window.almadenPageTemplateFlowMap || [],
            pageTemplateResults: window.almadenPageTemplateResults || [],
            universalCounter: shared.pendingUniversalCounter || getUniversalCounter() || null,
            imageBlocks: shared.imageBlocks || []
        };
    }

    function applyPreviewMetadata(metadata = {}) {
        shared.currentGeometry = metadata.geometry || shared.currentGeometry;
        window.almadenTypstOpeningDebug = metadata.openingDebug || [];
        window.almadenPageTemplateFlowMap = metadata.pageFlowMap || [];
        window.almadenPageTemplateResults = metadata.pageTemplateResults || [];
        shared.pendingUniversalCounter = metadata.universalCounter || null;
        shared.imageBlocks = Array.isArray(metadata.imageBlocks) ? metadata.imageBlocks : [];
        rebuildUniversalCounter();
        window.almadenPageTemplateState?.reconcileResults?.();
    }

    window.almadenTypstPdfState = {
        shared,
        constants: {
            PDFJS_WORKER_SRC,
            PREVIEW_CACHE_DB,
            PREVIEW_CACHE_STORE,
            PREVIEW_CACHE_VERSION,
            PREVIEW_CACHE_MAX_AGE
        },
        getZoomFactor,
        normalizeLayout,
        normalizePreviewMode,
        normalizePreviewAssetMode,
        getPreviewModeCopy,
        getTypstPreviewDebounceMs,
        reportPreviewPerformance,
        stableStringify,
        getCompilePayloadSignature,
        getPersistentCacheKey,
        openPreviewCache,
        readPersistentPreview,
        writePersistentPreview,
        buildTypstCompilePayload,
        buildPreviewContract,
        normalizeUniversalCounterEntry,
        rebuildUniversalCounter,
        getUniversalCounter,
        getActiveChapterCounterEntry,
        getVisiblePreviewPages,
        payload,
        readError,
        snapshotPreviewMetadata,
        applyPreviewMetadata,
        syncPublicState
    };
})();
