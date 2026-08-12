(function () {
    const state = window.almadenTypstPdfState;
    const view = window.almadenTypstPdfView;

    if (!state || !view) {
        console.warn('No se pudo inicializar el preview Typst porque faltan módulos auxiliares.');
        return;
    }
    if (window.almadenTypstPdfMain && window.almadenTypstPdfMain.initialized) {
        return;
    }

    const shared = state.shared;
    let compileTimer = null;
    let compileSequence = 0;
    let activeController = null;
    let pendingCompilePromise = null;
    let pendingCompileResolve = null;
    let pendingCompileSignature = null;
    let currentCompileSignature = null;

    window.almadenTypstPdfMain = window.almadenTypstPdfMain || {};
    window.almadenTypstPdfMain.renderSequence = 0;

    function clearPendingPromise(result = 0) {
        if (pendingCompileResolve) {
            pendingCompileResolve(result);
            pendingCompileResolve = null;
        }
    }

    async function compileTypstPreview(options = {}) {
        const startedAt = performance.now();
        const compilePayload = state.buildTypstCompilePayload(options);
        const compileSignature = state.getCompilePayloadSignature(compilePayload);

        if (!options.forceRecompile && shared.currentPdfBlob && currentCompileSignature === compileSignature) {
            await view.renderPdfPreview();
            state.reportPreviewPerformance('memory', startedAt);
            return 1;
        }

        const sequence = ++compileSequence;
        if (activeController) activeController.abort();
        activeController = new AbortController();
        shared.pendingUniversalCounter = null;
        const previewModeCopy = state.getPreviewModeCopy();
        if (!bookState.pdfPreview || typeof bookState.pdfPreview !== 'object') {
            bookState.pdfPreview = {};
        }
        bookState.pdfPreview.lastCompileSignature = compileSignature;

        if (!options.bypassPersistentCache) {
            view.setStatus('Cargando la última versión disponible del PDF...');
            const cachedPreview = await state.readPersistentPreview(compileSignature);
            if (sequence !== compileSequence) return 0;
            if (cachedPreview) {
                const cachedMetadata = cachedPreview.metadata || {};
                state.applyPreviewMetadata(cachedMetadata);
                await view.showPdf(cachedPreview.blob, cachedMetadata.geometry, cachedMetadata.integrity, 'browser');
                currentCompileSignature = compileSignature;
                window.pdfContentIntegrity = cachedMetadata.integrity?.status === 'warning'
                    ? { valid: true, engine: 'typst', warning: cachedMetadata.integrity.message, cache: 'browser' }
                    : { valid: true, engine: 'typst', cache: 'browser' };
                state.reportPreviewPerformance('browser-cache', startedAt);
                return 1;
            }
        }

        view.setStatus(`Componiendo ${previewModeCopy.message} con Typst y renderizando el PDF...`);
        console.info('[Typst opening layout: request]', {
            alignment: compilePayload?.settings?.chapter_page_one_align || '',
            separateOpening: compilePayload?.settings?.book_separate_opening_content,
            chapters: (compilePayload?.chapters || []).map(chapter => ({
                id: chapter?.id,
                title: chapter?.title,
                separateOpening: chapter?.opening_separate_content,
                hideOpening: chapter?.hide_opening
            }))
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
            if (!response.ok) throw new Error(await state.readError(response));

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
                            selected_ids: Array.isArray(result?.debug?.selected_ids) ? result.debug.selected_ids : []
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
                    shared.pendingUniversalCounter = JSON.parse(decodeURIComponent(universalCounterHeader));
                    state.rebuildUniversalCounter();
                } catch (error) {
                    console.warn('No se pudo leer el contador universal Typst.', error);
                }
            } else {
                shared.pendingUniversalCounter = null;
                state.rebuildUniversalCounter();
            }

            const imageBlocksHeader = response.headers.get('X-Almaden-Image-Blocks');
            if (imageBlocksHeader) {
                try {
                    shared.imageBlocks = JSON.parse(decodeURIComponent(imageBlocksHeader));
                } catch (error) {
                    shared.imageBlocks = [];
                    console.warn('No se pudo leer la geometría de imágenes Typst.', error);
                }
            } else {
                shared.imageBlocks = [];
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

            const metadata = state.snapshotPreviewMetadata(geometry, integrity);
            await view.showPdf(blob, geometry, integrity, serverCacheHit ? 'server' : '');
            currentCompileSignature = compileSignature;
            state.writePersistentPreview(compileSignature, blob, metadata).catch(error => {
                console.warn('No se pudo guardar el caché local del PDF.', error);
            });
            window.pdfContentIntegrity = integrity && integrity.status === 'warning'
                ? { valid: true, engine: 'typst', warning: integrity.message, cache: serverCacheHit ? 'server' : 'miss' }
                : { valid: true, engine: 'typst', cache: serverCacheHit ? 'server' : 'miss' };
            if (integrity && integrity.status === 'warning') {
                console.warn('Typst PDF integrity warning:', integrity.message);
            }
            state.reportPreviewPerformance(serverCacheHit ? 'server-cache' : 'typst', startedAt);
            return 1;
        } catch (error) {
            if (error.name === 'AbortError') return 0;
            console.warn('[Typst compile failed]', {
                message: error.message,
                pageTemplates: window.bookState?.settings?.page_templates || [],
                pageTemplateResults: window.almadenPageTemplateResults || [],
                openingDebug: window.almadenTypstOpeningDebug || null,
                pageFlowMap: window.almadenPageTemplateFlowMap || []
            });
            window.pdfContentIntegrity = { valid: false, engine: 'typst', error: error.message };
            view.setStatus(`Error en el maquetador Typst: ${error.message}`, true);
            return 0;
        }
    }

    function scheduleTypstPreview(scrollToActive = false, targetScrollerId = 'pdf-scroller', forceFull = false) {
        if (targetScrollerId !== 'pdf-scroller') return Promise.resolve(0);
        const compilePayload = state.buildTypstCompilePayload();
        const compileSignature = state.getCompilePayloadSignature(compilePayload);
        const debounceMs = forceFull ? 0 : state.getTypstPreviewDebounceMs();

        if (forceFull) {
            clearTimeout(compileTimer);
            pendingCompilePromise = null;
            pendingCompileSignature = null;
            clearPendingPromise(0);
            return compileTypstPreview({ compilePayload });
        }

        if (compileTimer && pendingCompileSignature === compileSignature && pendingCompilePromise) {
            return pendingCompilePromise;
        }

        clearPendingPromise(0);
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
            if (!valid || !shared.currentPdfBlob || !shared.currentPdfUrl) return;
            const link = document.createElement('a');
            const safeTitle = String(bookState.title || 'libro').trim().replace(/[^\p{L}\p{N}._-]+/gu, '-');
            link.href = shared.currentPdfUrl;
            link.download = `${safeTitle || 'libro'}.pdf`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            if (state.normalizePreviewAssetMode(bookState?.pdfPreview?.assetMode || bookState?.settings?.pdf_preview_asset_mode) !== 'original') {
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

    function refresh() {
        if (shared.currentPdfBlob) {
            view.renderPdfPreview().catch(error => {
                console.warn('No se pudo refrescar el preview del PDF Typst.', error);
            });
        }
    }

    window.compilePDFPreview = scheduleTypstPreview;
    window.triggerPrint = downloadTypstPdf;
    window.almadenTypstPdf = {
        state: state.buildPreviewContract(),
        compile: compileTypstPreview,
        download: downloadTypstPdf,
        applyZoom: view.applyZoom,
        applyLayout: view.applyLayout,
        hasCurrentPreview: view.hasCurrentPreview,
        refresh
    };
    window.almadenTypstPdfMain.initialized = true;

    state.syncPublicState();
})();
