let imageViewportBlockCounter = 0;

function readImageBlockStateFromElement(block) {
    if (!block) return createImageViewportState();

    const img = block.querySelector('img');
    const captionNode = block.querySelector('figcaption.pdf-book-image-caption');
    const originalSrc = img ? (img.getAttribute('data-original-src') || img.getAttribute('src') || '') : '';
    const previewSrc = img ? (img.getAttribute('data-preview-src') || img.getAttribute('src') || '') : '';
    const rawState = createImageViewportState({
        blockId: block.getAttribute('data-image-block-id') || '',
        src: originalSrc,
        previewSrc,
        alt: img ? (img.getAttribute('alt') || '') : '',
        caption: captionNode ? (captionNode.textContent || '').trim() : '',
        viewportWidth: '100%',
        viewportHeight: block.getAttribute('data-viewport-height') || '100%',
        zoom: block.getAttribute('data-zoom') || '1',
        fit: block.getAttribute('data-fit') || 'cover',
        position: block.getAttribute('data-position') || '50% 50%',
        isPlaceholder: !img || !img.getAttribute('src'),
        isNewBlock: false,
        inserted: true,
        committed: true,
    });
    const normalized = normalizeImageViewportStateToLayout(rawState);
    return {
        ...normalized.state,
        layoutNormalized: normalized.needsNormalization,
        layoutMaxHeightPercent: normalized.maxHeightPercent,
    };
}

function escapeSelectorValue(value) {
    const text = String(value || '');
    if (window.CSS && typeof window.CSS.escape === 'function') {
        return window.CSS.escape(text);
    }
    return text.replace(/["\\]/g, '\\$&');
}

function createImageViewportState(overrides = {}) {
    imageViewportBlockCounter += 1;
    return {
        blockId: `image-block-${Date.now()}-${imageViewportBlockCounter}`,
        src: '',
        previewSrc: '',
        alt: '',
        caption: '',
        viewportWidth: '100%',
        viewportHeight: '100%',
        zoom: '1',
        fit: 'cover',
        position: '50% 50%',
        isPlaceholder: true,
        isNewBlock: false,
        inserted: false,
        committed: false,
        dragging: false,
        dragStartX: 0,
        dragStartY: 0,
        dragStartPositionX: 50,
        dragStartPositionY: 50,
        ...overrides,
    };
}

function getImageViewportState() {
    if (!window.imageViewportEditorState) {
        window.imageViewportEditorState = createImageViewportState();
    }
    return window.imageViewportEditorState;
}

function setImageViewportState(nextState = {}) {
    window.imageViewportEditorState = {
        ...getImageViewportState(),
        ...nextState,
    };
    return window.imageViewportEditorState;
}

function clampImageViewportNumber(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

function parseImageViewportPosition(position) {
    const parts = String(position || '50% 50%').trim().split(/\s+/);
    const x = parseFloat(parts[0]);
    const y = parseFloat(parts[1] || parts[0]);
    return {
        x: Number.isFinite(x) ? x : 50,
        y: Number.isFinite(y) ? y : 50,
    };
}

function formatImageViewportPosition(x, y) {
    return `${clampImageViewportNumber(x, 0, 100).toFixed(2)}% ${clampImageViewportNumber(y, 0, 100).toFixed(2)}%`;
}

function getImageViewportAspectRatio(viewportWidth, viewportHeight) {
    const width = parseFloat(String(viewportWidth || '100').replace('%', ''));
    const height = parseFloat(String(viewportHeight || '100').replace('%', ''));
    const safeWidth = Number.isFinite(width) && width > 0 ? width : 100;
    const safeHeight = Number.isFinite(height) && height > 0 ? height : 100;
    return safeWidth / safeHeight;
}

function getImageViewportLayoutConstraints() {
    const settings = typeof bookState !== 'undefined' && bookState && bookState.settings ? bookState.settings : {};
    const geometry = typeof window.resolvePDFGeometry === 'function'
        ? window.resolvePDFGeometry(settings)
        : null;
    const unit = (geometry && geometry.unit) || settings.unit || 'cm';
    const conversionFactor = (geometry && geometry.conversionFactor)
        || (unit === 'cm' ? 37.7952755906 : 96);
    const trimWidth = (geometry && geometry.width) || parseFloat(settings.page_width) || 21;
    const contentHeightPx = Math.max((geometry && geometry.maxPageContentHeight) || 0, 1);

    const leftMarginOdd = parseFloat(settings.margin_left_odd ?? settings.margin_left ?? 2.0) || 0;
    const rightMarginOdd = parseFloat(settings.margin_right_odd ?? settings.margin_right ?? 2.0) || 0;
    const leftMarginEven = parseFloat(settings.margin_left_even ?? settings.margin_left ?? 2.0) || 0;
    const rightMarginEven = parseFloat(settings.margin_right_even ?? settings.margin_right ?? 2.0) || 0;

    const contentWidthOddPx = Math.max((trimWidth - leftMarginOdd - rightMarginOdd), 1) * conversionFactor;
    const contentWidthEvenPx = Math.max((trimWidth - leftMarginEven - rightMarginEven), 1) * conversionFactor;
    const contentWidthPx = Math.max(Math.min(contentWidthOddPx, contentWidthEvenPx), 1);

    const captionReservePx = 48;
    const usableHeightPx = Math.max(contentHeightPx - captionReservePx, 1);
    const maxHeightPercent = Math.max(30, Math.floor((usableHeightPx / contentWidthPx) * 100));

    return {
        unit,
        trimWidth,
        contentWidthPx,
        contentHeightPx,
        usableHeightPx,
        captionReservePx,
        maxHeightPercent,
        minHeightPercent: 30,
    };
}

function parseImageViewportPercentValue(value, fallback = 100) {
    const parsed = parseFloat(String(value || '').replace('%', ''));
    return Number.isFinite(parsed) ? parsed : fallback;
}

function normalizeImageViewportStateToLayout(state, constraints = getImageViewportLayoutConstraints()) {
    const nextState = { ...state };
    const parsedHeight = parseImageViewportPercentValue(nextState.viewportHeight, 100);
    const clampedHeight = clampImageViewportNumber(parsedHeight, constraints.minHeightPercent, constraints.maxHeightPercent);
    const normalizedHeight = `${clampedHeight}%`;
    const needsNormalization = normalizedHeight !== nextState.viewportHeight;

    if (needsNormalization) {
        nextState.viewportHeight = normalizedHeight;
    }

    return {
        state: nextState,
        needsNormalization,
        maxHeightPercent: constraints.maxHeightPercent,
    };
}

function countImageViewportWords(text) {
    return String(text || '').trim().match(/\S+/g)?.length || 0;
}
