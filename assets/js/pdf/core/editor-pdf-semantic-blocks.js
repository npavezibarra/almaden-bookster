// ============================================================
// MÓDULO: editor-pdf-semantic-blocks.js
// Responsabilidad: post-procesar capítulos con semántica especial
// (referencias) y convertir [pagebreak] en segmentos reales.
// ============================================================

function almadenNormalizeSemanticLabel(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

function almadenCountMatches(text, pattern) {
    const matches = String(text || '').match(pattern);
    return matches ? matches.length : 0;
}

window.chapterUsesReferencesLayout = function(chapter) {
    const title = almadenNormalizeSemanticLabel(chapter && chapter.title);
    const content = String((chapter && chapter.content) || '');
    const normalizedContent = almadenNormalizeSemanticLabel(content);

    const titleLooksLikeReferences = /^(referencias?|references?|bibliografia|bibliography)$/.test(title);
    const bulletCount = almadenCountMatches(content, /^\s*-\s+/gm);
    const headingCount = almadenCountMatches(content, /^\s*##\s+/gm);
    const thematicHint = /(literatura|historia|ensayos|obras|teatrales|musicales|revistas|peliculas|pel[ií]culas|bibliograf|referenc)/.test(normalizedContent);

    return titleLooksLikeReferences || (bulletCount >= 6 && headingCount >= 1 && thematicHint);
};

window.applyManualPageSegments = function(html) {
    if (!html || html.indexOf('pdf-page-break') === -1) {
        return html;
    }

    const container = document.createElement('div');
    container.innerHTML = html;

    const segments = [[]];
    const childNodes = Array.from(container.childNodes);

    childNodes.forEach((node) => {
        const isBreakMarker = node.nodeType === Node.ELEMENT_NODE
            && node.classList
            && node.classList.contains('pdf-page-break');

        if (isBreakMarker) {
            segments.push([]);
            return;
        }

        if (node.nodeType === Node.TEXT_NODE && !node.textContent.trim()) {
            return;
        }

        segments[segments.length - 1].push(node.cloneNode(true));
    });

    if (segments.length <= 1) {
        return html;
    }

    const rebuilt = document.createElement('div');

    segments.forEach((nodes, index) => {
        const segment = document.createElement('div');
        segment.className = 'pdf-manual-page-segment';

        if (index > 0) {
            segment.setAttribute('data-manual-page-start', '1');
        }

        if (nodes.length === 0) {
            segment.classList.add('pdf-manual-blank-page');
            segment.innerHTML = '<div class="pdf-manual-blank-page-spacer" aria-hidden="true"></div>';
        } else {
            nodes.forEach((child) => segment.appendChild(child));
        }

        rebuilt.appendChild(segment);
    });

    return rebuilt.innerHTML;
};

window.applySemanticChapterPostProcessing = function(chapter, compiledHtml) {
    let processed = window.applyManualPageSegments
        ? window.applyManualPageSegments(compiledHtml)
        : compiledHtml;

    if (window.chapterUsesReferencesLayout && window.chapterUsesReferencesLayout(chapter)) {
        processed = `<div class="chapter-semantic-references">${processed}</div>`;
    }

    return processed;
};
