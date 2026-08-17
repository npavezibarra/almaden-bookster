(function (window, document) {
    'use strict';

    const config = Object.assign({
        enabled: true,
        blockClipboard: true,
        blockDrag: true,
        protectedSelector: '[data-almaden-protected-content], [data-almaden-protected-excerpt]',
        allowedSelector: '[data-almaden-copy-allowed], input, textarea, select, [contenteditable="true"]',
        notice: 'La copia de texto está desactivada en este ebook. Puedes guardarlo como highlight.'
    }, window.almadenContentProtectionConfig || {});

    let noticeTimer = null;

    function getElement(node) {
        if (!node) return null;
        return node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
    }

    function closest(node, selector) {
        const element = getElement(node);
        return element && typeof element.closest === 'function' ? element.closest(selector) : null;
    }

    function isAllowedTarget(target) {
        return Boolean(closest(target, config.allowedSelector));
    }

    function getProtectedRoots() {
        return Array.from(document.querySelectorAll(config.protectedSelector));
    }

    function rangeIntersectsNode(range, node) {
        if (!range || !node) return false;
        try {
            return range.intersectsNode(node);
        } catch (error) {
            return node.contains(getElement(range.commonAncestorContainer));
        }
    }

    function selectionIntersectsProtectedContent(selection) {
        if (!selection || selection.rangeCount === 0 || selection.isCollapsed) return false;

        const protectedRoots = getProtectedRoots();
        for (let rangeIndex = 0; rangeIndex < selection.rangeCount; rangeIndex += 1) {
            const range = selection.getRangeAt(rangeIndex);
            if (protectedRoots.some(root => rangeIntersectsNode(range, root))) {
                return true;
            }
        }
        return false;
    }

    function eventTouchesProtectedContent(event) {
        if (isAllowedTarget(event.target)) return false;
        const selection = window.getSelection ? window.getSelection() : null;
        return selectionIntersectsProtectedContent(selection) || Boolean(closest(event.target, config.protectedSelector));
    }

    function showNotice() {
        const notice = document.getElementById('almaden-content-protection-notice');
        if (!notice) return;

        window.clearTimeout(noticeTimer);
        notice.textContent = config.notice;
        notice.hidden = false;
        notice.dataset.visible = 'true';
        noticeTimer = window.setTimeout(() => {
            notice.dataset.visible = 'false';
            window.setTimeout(() => {
                if (notice.dataset.visible === 'false') notice.hidden = true;
            }, 180);
        }, 2800);
    }

    function cancelEvent(event) {
        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }
    }

    function clearClipboardPayload(event) {
        if (!event.clipboardData) return;
        try {
            event.clipboardData.clearData();
            event.clipboardData.setData('text/plain', '');
            event.clipboardData.setData('text/html', '');
        } catch (error) {
            // preventDefault remains the authoritative protection when a browser
            // does not expose a mutable clipboard payload.
        }
    }

    function handleClipboard(event) {
        if (!config.enabled || !config.blockClipboard || !eventTouchesProtectedContent(event)) return;
        clearClipboardPayload(event);
        cancelEvent(event);
        showNotice();
        if (window.AlmadenProtectionTelemetry) window.AlmadenProtectionTelemetry.record(event.type);
    }

    function handleShortcut(event) {
        if (!config.enabled || !config.blockClipboard || event.altKey) return;
        const isCopyOrCut = (event.metaKey || event.ctrlKey) && ['c', 'x'].includes(String(event.key || '').toLowerCase());
        if (isCopyOrCut && eventTouchesProtectedContent(event)) {
            showNotice();
        }
    }

    function handleDragStart(event) {
        if (!config.enabled || !config.blockDrag || !eventTouchesProtectedContent(event)) return;
        cancelEvent(event);
        showNotice();
        if (window.AlmadenProtectionTelemetry) window.AlmadenProtectionTelemetry.record('drag');
    }

    function init() {
        if (!config.enabled || (window.AlmadenContentProtection && window.AlmadenContentProtection.initialized)) return;
        document.addEventListener('copy', handleClipboard, true);
        document.addEventListener('cut', handleClipboard, true);
        document.addEventListener('keydown', handleShortcut, true);
        document.addEventListener('dragstart', handleDragStart, true);
        window.AlmadenContentProtection = Object.freeze({
            initialized: true,
            selectionIntersectsProtectedContent,
            showNotice
        });
    }

    init();
})(window, document);
