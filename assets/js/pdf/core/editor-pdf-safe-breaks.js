// ============================================================
// MODULO: editor-pdf-safe-breaks.js
// Responsabilidad: Evitar que Paged.js corte texto dentro de
// palabras o elementos inline y deje fragmentos invisibles.
// ============================================================

(function registerAlmadenSafeBreaks() {
    if (!window.Paged || !window.Paged.Handler || window.__almadenSafeBreaksRegistered) {
        return;
    }

    const WORD_CHAR_RE = /[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9]/;
    const SOFT_HYPHEN = '\u00AD';
    const SAFE_BOUNDARY_RE = /[\s\u00ad\-–—.,;:!?¿¡()[\]{}"“”‘’'«»/]/;
    const SENSITIVE_INLINE_SELECTOR = [
        'em',
        'strong',
        'i',
        'b',
        'u',
        'span[lang]',
        'span[style]',
        '.almaden-inline'
    ].join(',');

    function isTextNode(node) {
        return node && node.nodeType === Node.TEXT_NODE;
    }

    function isInsideSensitiveInline(node) {
        return !!(
            node &&
            node.parentElement &&
            node.parentElement.closest(SENSITIVE_INLINE_SELECTOR)
        );
    }

    function isUnsafeWordOffset(text, offset) {
        if (!text || offset <= 0 || offset >= text.length) {
            return false;
        }

        const before = text.charAt(offset - 1);
        const after = text.charAt(offset);

        if (before === SOFT_HYPHEN || after === SOFT_HYPHEN) {
            return false;
        }

        return WORD_CHAR_RE.test(before) && WORD_CHAR_RE.test(after);
    }

    function previousSafeOffset(text, offset) {
        for (let i = Math.min(offset, text.length) - 1; i >= 0; i--) {
            if (SAFE_BOUNDARY_RE.test(text.charAt(i))) {
                return i + 1;
            }
        }

        return 0;
    }

    function getSafeOffsetForTextNode(node, offset) {
        if (!isTextNode(node)) {
            return offset;
        }

        const text = node.textContent || '';
        const normalizedOffset = Number(offset || 0);

        if (normalizedOffset <= 0 || normalizedOffset >= text.length) {
            return normalizedOffset;
        }

        if (isUnsafeWordOffset(text, normalizedOffset)) {
            const safeOffset = previousSafeOffset(text, normalizedOffset);
            return safeOffset > 0 ? safeOffset : normalizedOffset;
        }

        if (isInsideSensitiveInline(node)) {
            const safeOffset = previousSafeOffset(text, normalizedOffset);

            if (safeOffset < normalizedOffset) {
                return safeOffset;
            }
        }

        return normalizedOffset;
    }

    class AlmadenSafeBreaksHandler extends window.Paged.Handler {
        onOverflow(overflow) {
            if (!overflow || !isTextNode(overflow.startContainer)) {
                return overflow;
            }

            const safeOffset = getSafeOffsetForTextNode(overflow.startContainer, overflow.startOffset);

            if (safeOffset < overflow.startOffset) {
                overflow.setStart(overflow.startContainer, safeOffset);
            }

            return overflow;
        }
    }

    window.Paged.registerHandlers(AlmadenSafeBreaksHandler);
    window.__almadenSafeBreaksRegistered = true;
})();
