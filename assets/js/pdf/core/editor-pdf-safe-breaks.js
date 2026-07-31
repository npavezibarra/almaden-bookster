// ============================================================
// MODULO: editor-pdf-safe-breaks.js
// Responsabilidad: Reparar solo BreakTokens que retroceden.
// ============================================================

window.almadenResolveBreakTokenOffset = function(context) {
    const previousOffset = Number(context.previousOffset);
    const nativeOffset = Number(context.nativeOffset);
    const relativeOffset = Number(context.relativeOffset);
    const sourceLength = Number(context.sourceLength);
    const renderedLength = Number(context.renderedLength);

    if (
        Number.isInteger(previousOffset)
        && Number.isInteger(relativeOffset)
        && renderedLength === sourceLength - previousOffset
    ) {
        const absoluteOffset = previousOffset + relativeOffset;
        if (absoluteOffset > previousOffset && absoluteOffset <= sourceLength) {
            return absoluteOffset;
        }
    }

    return nativeOffset;
};

(function registerAlmadenMonotonicBreakTokens() {
    if (!window.Paged || !window.Paged.Handler || window.__almadenSafeBreaksRegistered) {
        return;
    }

    class AlmadenMonotonicBreakTokens extends window.Paged.Handler {
        constructor(chunker, polisher, caller) {
            super(chunker, polisher, caller);
            this.lastOffsetByTextNode = new WeakMap();
        }

        onBreakToken(breakToken, overflow) {
            const sourceNode = breakToken && breakToken.node;
            if (!sourceNode || sourceNode.nodeType !== Node.TEXT_NODE) {
                return breakToken;
            }

            const sourceLength = String(sourceNode.textContent || '').length;
            const previousOffset = this.lastOffsetByTextNode.get(sourceNode);
            const nativeOffset = Number(breakToken.offset);
            const relativeOffset = overflow ? Number(overflow.startOffset) : NaN;
            const renderedLength = overflow && overflow.startContainer
                ? String(overflow.startContainer.textContent || '').length
                : NaN;

            breakToken.offset = window.almadenResolveBreakTokenOffset({
                previousOffset,
                nativeOffset,
                relativeOffset,
                sourceLength,
                renderedLength
            });

            if (
                Number.isInteger(previousOffset)
                && Number.isInteger(Number(breakToken.offset))
                && Number(breakToken.offset) <= previousOffset
            ) {
                console.warn('Paged.js produjo un BreakToken no monotónico.', {
                    previousOffset,
                    nativeOffset: Number(breakToken.offset)
                });
            }

            const finalOffset = Number(breakToken.offset);
            if (Number.isInteger(finalOffset) && finalOffset > 0) {
                this.lastOffsetByTextNode.set(sourceNode, finalOffset);
            }

            return breakToken;
        }
    }

    window.Paged.registerHandlers(AlmadenMonotonicBreakTokens);
    window.__almadenSafeBreaksRegistered = true;
    window.almadenPdfSafeBreakStrategy = 'monotonic-continuation-offsets';
})();
