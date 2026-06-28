// ============================================================
// MÓDULO: editor-pdf-styles-semantic.js
// Responsabilidad: estilos para pagebreak manual y referencias.
// ============================================================

function getPDFStylesSemantic(settings) {
    const smartSpanishHyphenation = !['0', 0].includes(settings.content_hyphenation)
        && String(settings.content_language || 'es').toLowerCase().startsWith('es');
    const contentHyphenationMode = (settings.content_hyphenation === 0 || settings.content_hyphenation === '0')
        ? 'none'
        : (smartSpanishHyphenation ? 'manual' : 'auto');

    return `
        .pdf-content .pdf-page-break {
            display: block !important;
            width: 100% !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            break-after: page !important;
            page-break-after: always !important;
        }

        .pdf-content .pdf-manual-page-segment[data-manual-page-start="1"] {
            break-before: page !important;
            page-break-before: always !important;
        }

        .pdf-content .pdf-manual-blank-page {
            break-after: page !important;
            page-break-after: always !important;
            min-height: calc(
                var(--pagedjs-pagebox-height)
                - var(--pagedjs-margin-top)
                - var(--pagedjs-margin-bottom)
                - 1px
            ) !important;
        }

        .pdf-content .pdf-manual-blank-page-spacer {
            display: block !important;
            width: 100% !important;
            min-height: calc(
                var(--pagedjs-pagebox-height)
                - var(--pagedjs-margin-top)
                - var(--pagedjs-margin-bottom)
                - 1px
            ) !important;
            height: calc(
                var(--pagedjs-pagebox-height)
                - var(--pagedjs-margin-top)
                - var(--pagedjs-margin-bottom)
                - 1px
            ) !important;
        }

        .pdf-page.blank-page .pdf-header,
        .pdf-page.blank-page .pdf-footer,
        .pdf-page.blank-page .global-trim-line {
            display: none !important;
        }

        .pdf-page.blank-page .pdf-content {
            padding: 0 !important;
        }

        .pdf-content .chapter-semantic-references p,
        .pdf-content .chapter-semantic-references ul,
        .pdf-content .chapter-semantic-references ol,
        .pdf-content .chapter-semantic-references li,
        .pdf-content .chapter-semantic-references h2,
        .pdf-content .chapter-semantic-references h3,
        .pdf-content .chapter-semantic-references blockquote,
        .pdf-content .chapter-semantic-references [data-split-from],
        .pdf-content .chapter-semantic-references [data-split-to] {
            text-align: left !important;
            text-align-last: auto !important;
            text-indent: 0 !important;
            hyphens: ${contentHyphenationMode} !important;
            -webkit-hyphens: ${contentHyphenationMode} !important;
            word-spacing: normal !important;
        }

        .pdf-content .chapter-semantic-references ul,
        .pdf-content .chapter-semantic-references ol {
            margin-left: 0 !important;
            padding-left: 1.25em !important;
        }

        .pdf-content .chapter-semantic-references li {
            list-style-position: outside !important;
            margin-left: 0 !important;
            padding-left: 0 !important;
        }

        .pdf-content .chapter-semantic-references .almaden-align-left,
        .pdf-content .chapter-semantic-references .almaden-align-left * {
            text-align: left !important;
            text-align-last: auto !important;
            text-indent: 0 !important;
        }
    `;
}
