(function () {
    function rawSurface() {
        return typeof getRawEditorSurface === 'function' ? getRawEditorSurface() : null;
    }

    function figureAtOffset(text, offset) {
        const figures = String(text || '').matchAll(/<figure\b[^>]*data-image-block="1"[^>]*>[\s\S]*?<\/figure>/gi);
        for (const match of figures) {
            const start = match.index || 0;
            if (offset >= start && offset <= start + match[0].length) return match[0];
        }
        return '';
    }

    function elementFromMarkup(markup) {
        if (!markup) return null;
        const doc = new DOMParser().parseFromString(markup, 'text/html');
        return doc.querySelector('figure[data-image-block="1"]');
    }

    function getRawFigure(blockId = '') {
        const textarea = rawSurface();
        if (!textarea) return '';
        if (!blockId) return figureAtOffset(textarea.value, textarea.selectionStart || 0);
        const escaped = String(blockId).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        return textarea.value.match(new RegExp(`<figure\\b[^>]*data-image-block-id="${escaped}"[^>]*>[\\s\\S]*?<\\/figure>`, 'i'))?.[0] || '';
    }

    function rawBlockIdAtCursor() {
        return elementFromMarkup(getRawFigure())?.getAttribute('data-image-block-id') || '';
    }

    function openRawBlock(blockId = '') {
        const element = elementFromMarkup(getRawFigure(blockId));
        if (!element || typeof readImageBlockStateFromElement !== 'function') return false;
        setImageViewportState(readImageBlockStateFromElement(element));
        openImageViewportModal();
        return true;
    }

    function openBlockById(blockId) {
        if (openRawBlock(blockId)) return true;
        const needle = `data-image-block-id="${String(blockId || '')}"`;
        const chapter = (window.bookState?.chapters || []).find(item => String(item?.content || '').includes(needle));
        if (!chapter || typeof window.selectChapter !== 'function') return false;
        if (typeof window.syncRawEditorToState === 'function') window.syncRawEditorToState();
        window.selectChapter(chapter.id);
        window.requestAnimationFrame(() => openRawBlock(blockId));
        return true;
    }

    function clamp(value, min, max, fallback) {
        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) ? Math.min(max, Math.max(min, parsed)) : fallback;
    }

    function applyElementLayout(figure) {
        const constraints = getImageViewportLayoutConstraints();
        const mode = figure.dataset.heightMode === 'fixed' ? 'fixed' : 'auto';
        const percent = clamp(figure.dataset.heightPercent, 15, 90, 45);
        const frame = figure.querySelector('.pdf-book-image-frame');
        const image = frame?.querySelector('img');
        const caption = figure.querySelector('.pdf-book-image-caption');
        figure.style.marginTop = `${clamp(figure.dataset.marginTopMm, 0, 30, 0) * 3.7795}px`;
        figure.style.marginBottom = `${clamp(figure.dataset.marginBottomMm, 0, 30, 0) * 3.7795}px`;
        if (frame) {
            frame.style.height = mode === 'fixed' ? `${constraints.contentHeightPx * percent / 100}px` : 'auto';
            frame.style.overflow = mode === 'fixed' ? 'hidden' : 'visible';
        }
        if (image) {
            image.style.height = mode === 'fixed' ? '100%' : 'auto';
            image.style.objectFit = mode === 'fixed' ? (figure.dataset.fit || 'cover') : 'contain';
            image.style.objectPosition = figure.dataset.position || '50% 50%';
        }
        if (caption) {
            caption.style.marginTop = `${clamp(figure.dataset.captionGapMm, 0, 10, 1.5) * 3.7795}px`;
            caption.style.textAlign = ['left', 'center', 'right'].includes(figure.dataset.captionAlign) ? figure.dataset.captionAlign : 'left';
        }
    }

    function applyLayouts(root = document) {
        if (root.matches?.('figure.pdf-book-image-block[data-image-block="1"]')) applyElementLayout(root);
        root.querySelectorAll?.('figure.pdf-book-image-block[data-image-block="1"]').forEach(applyElementLayout);
    }

    function updateToolbar() {
        const button = document.getElementById('editor-image-toolbar-btn');
        if (!button) return;
        const editing = !!rawBlockIdAtCursor();
        button.title = editing ? 'Editar imagen seleccionada' : 'Insertar imagen';
        button.classList.toggle('bg-sky-100', editing);
        button.classList.toggle('text-sky-700', editing);
    }

    document.addEventListener('selectionchange', updateToolbar);
    document.addEventListener('input', event => {
        if (event.target === rawSurface()) updateToolbar();
    });
    document.addEventListener('DOMContentLoaded', () => {
        const observer = new MutationObserver(records => {
            records.forEach(record => record.addedNodes.forEach(node => {
                if (node.nodeType === Node.ELEMENT_NODE) applyLayouts(node);
            }));
        });
        observer.observe(document.body, { childList: true, subtree: true });
        applyLayouts(document);
    });

    window.almadenImageLayout = {
        applyLayouts,
        getRawBlockIdAtCursor: rawBlockIdAtCursor,
        openRawBlock,
        openBlockById,
    };
})();
