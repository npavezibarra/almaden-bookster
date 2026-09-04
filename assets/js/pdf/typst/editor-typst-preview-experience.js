// Keeps the last confirmed PDF visible while Typst composes a newer revision.
(function () {
    const originalCompile = window.compilePDFPreview;
    if (typeof originalCompile !== 'function' || window.almadenTypstPreviewExperience) return;

    const QUIET_DELAY_MS = 1000;
    const ACTION_DELAY_MS = 0;
    const MAX_WAIT_MS = 3000;
    let pendingBatch = null;
    let activeCompilePromise = null;
    let activeContinuity = null;
    let requestedRevision = 0;

    function clearBatchTimers(batch) {
        if (!batch) return;
        window.clearTimeout(batch.quietTimer);
        window.clearTimeout(batch.maxTimer);
    }

    function resolveBatch(batch, value) {
        if (!batch || typeof batch.resolve !== 'function') return;
        batch.resolve(value);
        batch.resolve = null;
    }

    function getScroller() {
        return document.getElementById('pdf-scroller');
    }

    function positionContinuityLayer(continuity) {
        if (!continuity?.layer?.isConnected || !continuity.scroller?.isConnected) return;
        const bounds = continuity.scroller.getBoundingClientRect();
        Object.assign(continuity.layer.style, {
            top: `${bounds.top}px`,
            left: `${bounds.left}px`,
            width: `${bounds.width}px`,
            height: `${bounds.height}px`
        });
    }

    function setContinuityMessage(message, tone = 'working') {
        if (!activeContinuity?.badge) return;
        activeContinuity.badge.textContent = message;
        activeContinuity.badge.dataset.tone = tone;
        activeContinuity.badge.style.background = tone === 'error'
            ? 'rgba(190, 24, 93, 0.94)'
            : 'rgba(15, 23, 42, 0.9)';
    }

    function createContinuityLayer() {
        if (activeContinuity) return activeContinuity;
        const scroller = getScroller();
        const hasPreview = window.almadenTypstPdf?.hasCurrentPreview?.();
        if (!scroller || !hasPreview || !scroller.firstChild) return null;

        const layer = document.createElement('div');
        const badge = document.createElement('div');
        const originalNodes = Array.from(scroller.childNodes);
        const continuity = {
            scroller,
            layer,
            badge,
            originalNodes,
            className: scroller.className,
            style: scroller.getAttribute('style') || '',
            scrollLeft: scroller.scrollLeft,
            scrollTop: scroller.scrollTop
        };

        layer.id = 'typst-preview-continuity-layer';
        layer.className = scroller.className;
        layer.setAttribute('aria-busy', 'true');
        layer.style.cssText = continuity.style;
        Object.assign(layer.style, {
            position: 'fixed',
            zIndex: '45',
            margin: '0',
            overflow: 'auto',
            background: '#e2e8f0',
            pointerEvents: 'auto'
        });

        badge.id = 'typst-preview-continuity-status';
        badge.setAttribute('role', 'status');
        badge.setAttribute('aria-live', 'polite');
        Object.assign(badge.style, {
            position: 'absolute',
            top: '12px',
            right: '12px',
            zIndex: '2',
            padding: '8px 12px',
            borderRadius: '999px',
            color: '#fff',
            fontSize: '12px',
            fontWeight: '700',
            boxShadow: '0 8px 24px rgba(15, 23, 42, 0.2)'
        });

        originalNodes.forEach(node => layer.appendChild(node));
        layer.appendChild(badge);
        layer.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
        }, true);
        document.body.appendChild(layer);
        layer.scrollLeft = continuity.scrollLeft;
        layer.scrollTop = continuity.scrollTop;
        activeContinuity = continuity;
        positionContinuityLayer(continuity);
        setContinuityMessage('Actualizando composición...');
        return continuity;
    }

    function closeContinuityLayer(restorePrevious = false) {
        const continuity = activeContinuity;
        if (!continuity) return;
        activeContinuity = null;

        if (restorePrevious && continuity.scroller?.isConnected) {
            continuity.scroller.className = continuity.className;
            continuity.scroller.setAttribute('style', continuity.style);
            continuity.scroller.replaceChildren(...continuity.originalNodes);
            continuity.scroller.scrollLeft = continuity.scrollLeft;
            continuity.scroller.scrollTop = continuity.scrollTop;
        }
        continuity.layer.remove();
    }

    function reportResponsiveness(context) {
        const metrics = {
            revision: context.revision,
            queuedMs: Math.max(0, Math.round(context.startedAt - context.queuedAt)),
            compileAndRenderMs: Math.max(0, Math.round(performance.now() - context.startedAt)),
            totalMs: Math.max(0, Math.round(performance.now() - context.queuedAt)),
            applied: !!context.result
        };
        if (window.bookState) {
            window.bookState.pdfPreview = window.bookState.pdfPreview || {};
            window.bookState.pdfPreview.lastResponsiveness = { ...metrics, at: Date.now() };
        }
        console.info('[Typst preview responsiveness]', metrics);
    }

    async function runCompile(args, queuedAt, revision) {
        const startedAt = performance.now();
        createContinuityLayer();
        setContinuityMessage('Actualizando composición...');

        let result = 0;
        try {
            // The existing scheduler's third argument only skips its short debounce;
            // it does not change chapter/full preview mode.
            const immediateArgs = [args[0], args[1] || 'pdf-scroller', true, ...args.slice(3)];
            result = await Promise.resolve(originalCompile(...immediateArgs));
        } catch (error) {
            console.warn('No se pudo actualizar el preview Typst.', error);
        }

        const isLatest = revision === requestedRevision;
        if (isLatest) {
            if (result) {
                closeContinuityLayer(false);
            } else {
                setContinuityMessage('No se pudo actualizar. Se conserva la versión anterior.', 'error');
                closeContinuityLayer(true);
                if (typeof window.showToast === 'function') {
                    window.showToast('No se pudo actualizar el PDF. Se conserva la última versión válida.', 'fa-solid fa-circle-exclamation');
                }
            }
            window.almadenTypstProvisionalText?.settle?.(!!result);
        }
        reportResponsiveness({ revision, queuedAt, startedAt, result });
        return result;
    }

    function flushPendingBatch() {
        const batch = pendingBatch;
        if (!batch) return;
        if (activeCompilePromise) {
            clearBatchTimers(batch);
            batch.quietTimer = null;
            batch.maxTimer = null;
            return;
        }
        pendingBatch = null;
        clearBatchTimers(batch);
        activeCompilePromise = runCompile(batch.args, batch.queuedAt, batch.revision);
        activeCompilePromise.then(result => resolveBatch(batch, result)).finally(() => {
            activeCompilePromise = null;
            if (pendingBatch) flushPendingBatch();
        });
    }

    function queueCompile(args, forceImmediate = false) {
        requestedRevision += 1;
        const revision = requestedRevision;
        const scrollToActive = args[0] === true;
        const delay = forceImmediate || scrollToActive ? ACTION_DELAY_MS : QUIET_DELAY_MS;

        if (!pendingBatch) {
            const queuedAt = performance.now();
            pendingBatch = { args, queuedAt, revision, quietTimer: null, maxTimer: null, resolve: null };
            pendingBatch.promise = new Promise(resolve => {
                pendingBatch.resolve = resolve;
            });
            pendingBatch.maxTimer = window.setTimeout(flushPendingBatch, forceImmediate ? 0 : MAX_WAIT_MS);
        } else {
            pendingBatch.args = args;
            pendingBatch.revision = revision;
        }

        window.clearTimeout(pendingBatch.quietTimer);
        pendingBatch.quietTimer = window.setTimeout(flushPendingBatch, delay);
        window.almadenTypstProvisionalText?.showPending?.();
        if (activeContinuity) setContinuityMessage('Cambios pendientes...');
        return pendingBatch.promise;
    }

    function compileWithContinuity(...args) {
        const targetScrollerId = args[1] || 'pdf-scroller';
        const forceImmediate = args[2] === true;
        if (targetScrollerId !== 'pdf-scroller') return originalCompile(...args);

        if (!forceImmediate) return queueCompile(args);

        return queueCompile(args, true);
    }

    function compileEditorAction(scrollToActive = true) {
        window.almadenTypstProvisionalText?.showPending?.();
        return compileWithContinuity(scrollToActive, 'pdf-scroller', true);
    }

    window.addEventListener('resize', () => positionContinuityLayer(activeContinuity));
    window.compilePDFPreview = compileWithContinuity;
    window.almadenTypstPreviewExperience = {
        compileEditorAction,
        flush: flushPendingBatch,
        isUpdating: () => !!activeCompilePromise || !!pendingBatch,
        delays: { quiet: QUIET_DELAY_MS, action: ACTION_DELAY_MS, maxWait: MAX_WAIT_MS }
    };
})();
