// Renders an approximate changed paragraph while Typst prepares the real PDF.
(function () {
    const confirmedContent = new Map();
    const OVERLAY_SELECTOR = '[data-typst-provisional-text="1"]';

    function getActiveChapter() {
        const chapters = Array.isArray(window.bookState?.chapters) ? window.bookState.chapters : [];
        return chapters.find(chapter => String(chapter?.id || '') === String(window.bookState?.activeChapterId || '')) || null;
    }

    function captureConfirmedState() {
        const chapters = Array.isArray(window.bookState?.chapters) ? window.bookState.chapters : [];
        chapters.forEach(chapter => {
            confirmedContent.set(String(chapter?.id || ''), String(chapter?.content || ''));
        });
    }

    function getPreviewRoot() {
        return document.getElementById('typst-preview-continuity-layer')
            || document.getElementById('pdf-scroller');
    }

    function remove() {
        document.querySelectorAll(OVERLAY_SELECTOR).forEach(node => node.remove());
    }

    function isTruthy(value, fallback = false) {
        if (value === '' || value === null || typeof value === 'undefined') return fallback;
        return value === true || value === 1 || value === '1' || String(value).toLowerCase() === 'true';
    }

    function chapterHasSeparateOpening(chapter) {
        const chapterSetting = chapter?.opening_separate_content;
        const globalSetting = window.bookState?.settings?.book_separate_opening_content;
        return isTruthy(chapterSetting, isTruthy(globalSetting, true));
    }

    function getCaretPosition(content) {
        const textarea = typeof window.getRawEditorSurface === 'function'
            ? window.getRawEditorSurface()
            : document.querySelector('[data-editor-surface="raw"]');
        if (textarea && Number.isInteger(textarea.selectionStart)) {
            return Math.max(0, Math.min(content.length, textarea.selectionStart));
        }
        const storedPosition = Number(window.editorLastSelection?.start || 0);
        return Math.max(0, Math.min(content.length, storedPosition));
    }

    function getChangedParagraph(content, caret) {
        let start = content.lastIndexOf('\n\n', Math.max(0, caret - 1));
        start = start < 0 ? 0 : start + 2;
        let end = content.indexOf('\n\n', caret);
        end = end < 0 ? content.length : end;
        let paragraph = content.slice(start, end).trim();

        if (!paragraph) {
            const fallbackStart = Math.max(0, caret - 160);
            const fallbackEnd = Math.min(content.length, caret + 320);
            paragraph = content.slice(fallbackStart, fallbackEnd).trim();
        }
        return paragraph.length > 900 ? `${paragraph.slice(0, 897)}...` : paragraph;
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[character]));
    }

    function renderApproximateMarkdown(value) {
        let html = escapeHtml(value)
            .replace(/&lt;figure\b[\s\S]*?&lt;\/figure&gt;/gi, '<em>[Imagen]</em>')
            .replace(/&lt;img\b[\s\S]*?\/?&gt;/gi, '<em>[Imagen]</em>')
            .replace(/\[size=(\d+(?:\.\d+)?)\]/gi, (match, size) => {
                const safeSize = Math.max(8, Math.min(48, Number(size) || 12));
                return `<span style="font-size:${safeSize}px">`;
            })
            .replace(/\[\/size\]/gi, '</span>')
            .replace(/\[font=(?:&quot;)?[^\]]+(?:&quot;)?\]/gi, '<span>')
            .replace(/\[\/font\]/gi, '</span>')
            .replace(/&lt;u&gt;/gi, '<u>')
            .replace(/&lt;\/u&gt;/gi, '</u>')
            .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
            .replace(/\*([^*]+)\*/g, '<em>$1</em>')
            .replace(/^#{1,6}\s+/gm, '')
            .replace(/^&gt;\s?/gm, '')
            .replace(/\n/g, '<br>');
        return html;
    }

    function getCandidatePages(root, chapter) {
        const pages = Array.from(root?.querySelectorAll?.('[data-page-number]') || [])
            .filter(page => page.dataset.blank !== '1');
        if (pages.length > 1 && chapterHasSeparateOpening(chapter)) {
            return pages.slice(1);
        }
        return pages;
    }

    function choosePage(pages, content, caret) {
        if (!pages.length) return null;
        const progress = content.length > 0 ? caret / content.length : 0;
        const index = Math.min(pages.length - 1, Math.max(0, Math.floor(progress * pages.length)));
        return pages[index];
    }

    function createOverlay(paragraph) {
        const settings = window.bookState?.settings || {};
        const overlay = document.createElement('div');
        const label = document.createElement('div');
        const content = document.createElement('div');

        overlay.dataset.typstProvisionalText = '1';
        overlay.setAttribute('aria-label', 'Vista provisional del último cambio');
        Object.assign(overlay.style, {
            position: 'absolute',
            left: '7%',
            right: '7%',
            bottom: '7%',
            zIndex: '4',
            maxHeight: '38%',
            overflow: 'hidden',
            padding: '12px 14px',
            border: '1px dashed #d97706',
            borderRadius: '8px',
            background: 'rgba(255, 251, 235, 0.96)',
            color: String(settings.color_content || '#111827'),
            boxShadow: '0 10px 30px rgba(15, 23, 42, 0.16)',
            pointerEvents: 'none'
        });
        Object.assign(label.style, {
            marginBottom: '6px',
            color: '#b45309',
            fontFamily: 'sans-serif',
            fontSize: '9px',
            fontWeight: '800',
            letterSpacing: '0.12em',
            textTransform: 'uppercase'
        });
        Object.assign(content.style, {
            fontFamily: String(settings.font_family_content || 'serif'),
            fontSize: '12px',
            lineHeight: '1.35',
            textAlign: String(settings.content_text_align || 'left')
        });

        label.textContent = 'Vista provisional · Typst está recomponiendo';
        content.innerHTML = renderApproximateMarkdown(paragraph);
        overlay.append(label, content);
        return overlay;
    }

    function showPending() {
        const chapter = getActiveChapter();
        if (!chapter) return;
        const current = String(chapter.content || '');
        const confirmed = confirmedContent.get(String(chapter.id || ''));
        remove();
        if (typeof confirmed !== 'string' || current === confirmed) return;

        const root = getPreviewRoot();
        const caret = getCaretPosition(current);
        const pages = getCandidatePages(root, chapter);
        const page = choosePage(pages, current, caret);
        const paragraph = getChangedParagraph(current, caret);
        if (!page || !paragraph) return;

        page.appendChild(createOverlay(paragraph));
    }

    function settle(success) {
        remove();
        if (success) captureConfirmedState();
    }

    captureConfirmedState();
    document.addEventListener('input', event => {
        if (!event.target?.matches?.('[data-editor-surface="raw"]')) return;
        window.requestAnimationFrame(showPending);
    });
    window.almadenTypstProvisionalText = {
        captureConfirmedState,
        remove,
        settle,
        showPending
    };
})();
