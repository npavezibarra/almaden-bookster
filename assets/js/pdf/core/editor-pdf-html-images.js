// ============================================================
// MÓDULO: editor-pdf-html.js
// Responsabilidad: Procesar el Markdown de un capítulo y 
// convertirlo en HTML con títulos, subtítulos, prefijos y TOC.
// ============================================================

function markEditableChapterBlocks(html) {
    const container = document.createElement('div');
    container.innerHTML = html;
    Array.from(container.children).forEach((block, index) => {
        block.setAttribute('data-editor-block-id', `block-${index}`);
    });
    return container.innerHTML;
}

function parseImageBlockViewportValue(value, fallback = '') {
    const normalized = String(value || '').trim();
    return normalized || fallback;
}

function parseImageBlockAspectRatio(widthValue, heightValue) {
    const width = parseFloat(String(widthValue || '100').replace('%', ''));
    const height = parseFloat(String(heightValue || '100').replace('%', ''));
    const safeWidth = Number.isFinite(width) && width > 0 ? width : 100;
    const safeHeight = Number.isFinite(height) && height > 0 ? height : 100;
    return safeWidth / safeHeight;
}

function parseImageBlockPercentValue(value, fallback = 100) {
    const parsed = parseFloat(String(value || '').replace('%', ''));
    return Number.isFinite(parsed) ? parsed : fallback;
}

function getImageBlockLayoutConstraints(settings = (bookState && bookState.settings) || {}) {
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

    const contentWidthOddPx = Math.max(trimWidth - leftMarginOdd - rightMarginOdd, 1) * conversionFactor;
    const contentWidthEvenPx = Math.max(trimWidth - leftMarginEven - rightMarginEven, 1) * conversionFactor;
    const contentWidthPx = Math.max(Math.min(contentWidthOddPx, contentWidthEvenPx), 1);

    const captionReservePx = 48;
    const usableHeightPx = Math.max(contentHeightPx - captionReservePx, 1);
    const maxHeightPercent = Math.max(30, Math.floor((usableHeightPx / contentWidthPx) * 100));

    return {
        maxHeightPercent,
        minHeightPercent: 30,
    };
}

function creditsCssFontFamilyValue(value) {
    const family = String(value || '').trim();
    if (!family) {
        return 'inherit';
    }
    return `'${family.replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;
}

function creditsGetBookCoverLogoUrl() {
    try {
        const state = typeof bookState !== 'undefined' && bookState
            ? bookState
            : (window.bookState || null);
        let coverSettings = state && state.coverSettings ? state.coverSettings : {};
        if (typeof coverSettings === 'string' && coverSettings.trim() !== '') {
            coverSettings = JSON.parse(coverSettings);
        }
        if (!coverSettings || typeof coverSettings !== 'object') {
            return '';
        }

        let textLayers = coverSettings.text_layers || [];
        if (typeof textLayers === 'string' && textLayers.trim() !== '') {
            textLayers = JSON.parse(textLayers);
        }
        if (!Array.isArray(textLayers) || textLayers.length === 0) {
            return '';
        }

        const logoGroup = textLayers.find((layer) => layer
            && layer.type === 'group'
            && (layer.isBookLogo === true || layer.isBookLogo === 'true'));
        if (!logoGroup) {
            return '';
        }

        const groupId = String(logoGroup.id || '');
        const imageLayer = textLayers.find((layer) => layer
            && layer.type === 'image'
            && String(layer.parentId || '') === groupId
            && String(layer.url || '').trim() !== '');

        return imageLayer ? String(imageLayer.url || '').trim() : '';
    } catch (error) {
        return '';
    }
}

function creditsResolveLogoUrl(item = {}) {
    const sourceType = String(item.logo_source || item.source_type || item.mode || 'image').trim().toLowerCase() === 'cover_logo'
        ? 'cover_logo'
        : 'image';
    const uploadedLogoUrl = String(item.logo_url || item.image_url || item.url || '').trim();
    if (sourceType === 'cover_logo') {
        return creditsGetBookCoverLogoUrl();
    }
    return uploadedLogoUrl;
}

function normalizeImageBlockViewportHeight(value, settings) {
    const constraints = getImageBlockLayoutConstraints(settings);
    const parsed = parseImageBlockPercentValue(value, 100);
    const clamped = Math.min(Math.max(parsed, constraints.minHeightPercent), constraints.maxHeightPercent);
    return {
        value: `${clamped}%`,
        clamped,
        wasClamped: `${clamped}%` !== String(value || '').trim(),
        constraints,
    };
}

function normalizeImageBlockElement(block) {
    if (!block || block.nodeType !== Node.ELEMENT_NODE) return;

    const img = block.querySelector('img');
    const caption = block.querySelector('figcaption.pdf-book-image-caption');
    const hasImage = !!(img && img.getAttribute('src'));
    if (!block.getAttribute('data-image-block-id')) {
        block.setAttribute('data-image-block-id', `image-block-${Date.now()}-${Math.floor(Math.random() * 100000)}`);
    }
    const heightNormalization = normalizeImageBlockViewportHeight(block.getAttribute('data-viewport-height'), bookState && bookState.settings ? bookState.settings : {});
    const viewportHeight = heightNormalization.value;
    const zoom = parseFloat(block.getAttribute('data-zoom') || '1');
    const fit = parseImageBlockViewportValue(block.getAttribute('data-fit'), 'cover');
    const position = parseImageBlockViewportValue(block.getAttribute('data-position'), '50% 50%');

    let frame = Array.from(block.children || []).find((child) => {
        return child && child.nodeType === Node.ELEMENT_NODE && child.classList.contains('pdf-book-image-frame');
    });

    if (!frame) {
        frame = document.createElement('div');
        frame.className = 'pdf-book-image-frame';

        const directMediaChild = Array.from(block.children || []).find((child) => {
            if (!child || child.nodeType !== Node.ELEMENT_NODE) return false;
            const tag = child.tagName.toLowerCase();
            return tag === 'img' || child.classList.contains('pdf-book-image-placeholder');
        });

        if (directMediaChild) {
            frame.appendChild(directMediaChild);
        }

        if (caption) {
            block.insertBefore(frame, caption);
        } else {
            block.insertBefore(frame, block.firstChild);
        }
    }

    block.classList.toggle('is-empty', !hasImage);
    block.style.display = 'block';
    block.style.maxWidth = '100%';
    block.style.overflow = 'visible';
    block.style.boxSizing = 'border-box';
    // The editor now treats image blocks as full-width figures.
    block.style.width = '100%';
    block.style.position = 'relative';
    block.setAttribute('data-viewport-height', viewportHeight);
    if (heightNormalization.wasClamped) {
        block.setAttribute('data-viewport-height-normalized', '1');
    } else {
        block.removeAttribute('data-viewport-height-normalized');
    }

    if (frame) {
        frame.classList.toggle('is-empty', !hasImage);
        frame.style.display = 'block';
        frame.style.width = '100%';
        frame.style.height = 'auto';
        frame.style.aspectRatio = `${parseImageBlockAspectRatio('100%', viewportHeight)}`;
        frame.style.position = 'relative';
        frame.style.overflow = 'hidden';
        frame.style.boxSizing = 'border-box';
    }

    let editButton = block.querySelector('.pdf-book-image-edit-handle');
    if (!editButton) {
        editButton = document.createElement('button');
        editButton.type = 'button';
        editButton.className = 'pdf-book-image-edit-handle no-print';
        editButton.innerHTML = hasImage ? '<i class="fa-solid fa-sliders"></i><span>Transform</span>' : '<i class="fa-solid fa-image"></i><span>Select</span>';
        editButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const blockId = block.getAttribute('data-image-block-id') || '';
            if (typeof window.openImageViewportFromBlock === 'function') {
                window.openImageViewportFromBlock(blockId);
            }
        });
        block.appendChild(editButton);
    } else {
        editButton.innerHTML = hasImage ? '<i class="fa-solid fa-sliders"></i><span>Transform</span>' : '<i class="fa-solid fa-image"></i><span>Select</span>';
    }

    if (!img) return;

    img.style.display = 'block';
    img.style.position = 'absolute';
    img.style.inset = '0';
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = fit;
    img.style.objectPosition = position;

    if (!Number.isNaN(zoom) && zoom !== 1) {
        img.style.transform = `scale(${zoom})`;
        img.style.transformOrigin = position;
    } else {
        img.style.transform = '';
        img.style.transformOrigin = position;
    }

    if (caption) {
        caption.style.display = 'block';
    }
}

function normalizeImageBlocksInHtml(html) {
    if (!html) return html;

    const container = document.createElement('div');
    container.innerHTML = html;
    container.querySelectorAll('figure.pdf-book-image-block, [data-image-block="1"]').forEach((block) => {
        normalizeImageBlockElement(block);
    });
    return container.innerHTML;
}

