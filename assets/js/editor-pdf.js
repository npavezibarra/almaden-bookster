// Renderiza el listado de notas al pie correspondientes a la página
function renderPageFootnotes(container, footnotes) {
    if (!container) return;
    if (!footnotes || footnotes.length === 0) {
        container.innerHTML = '';
        container.classList.add('hidden');
        return;
    }

    container.classList.remove('hidden');
    container.innerHTML = footnotes.map(fn => `
        <div class="pdf-footnote-item">
            <span class="footnote-num font-semibold mr-1">${fn.number}.</span> ${fn.text}
        </div>
    `).join('');
}

// Paginación interactiva: divide inteligentemente el contenido para que entre en hojas A4 virtuales
function compilePDFPreview() {
    const scroller = document.getElementById('pdf-scroller');
    if (!scroller) return;

    if (!bookState.chapters || bookState.chapters.length === 0) {
        scroller.innerHTML = '<div class="text-center text-slate-400 py-10">Crea o selecciona un capítulo para comenzar.</div>';
        const indicator = document.getElementById('pdf-page-indicator');
        if (indicator) indicator.textContent = '0 Páginas';
        return;
    }

    const settings = bookState.settings || {};
    const unit = settings.unit || 'cm';
    let width = settings.page_width || 21;
    let height = settings.page_height || 29.7;

    if (settings.page_size === 'A4') {
        width = (unit === 'cm') ? 21.0 : (21.0 / 2.54);
        height = (unit === 'cm') ? 29.7 : (29.7 / 2.54);
    } else if (settings.page_size === 'Letter') {
        width = (unit === 'cm') ? (8.5 * 2.54) : 8.5;
        height = (unit === 'cm') ? (11.0 * 2.54) : 11.0;
    }

    const conversionFactor = (unit === 'cm') ? 37.795 : 96.0;
    const pageHeightPx = height * conversionFactor;
    const pageWidthPx = width * conversionFactor;

    const headerFontPx = (parseFloat(settings.header_font_size) || 8.5) * 1.333;
    const footerFontPx = (parseFloat(settings.footer_font_size) || 9.0) * 1.333;
    const headerMarginTopPx = (settings.header_margin_top !== undefined ? parseFloat(settings.header_margin_top) : 1.0) * conversionFactor;
    const headerMarginBottomPx = (settings.header_margin_bottom !== undefined ? parseFloat(settings.header_margin_bottom) : 0.5) * conversionFactor;
    const footerMarginTopPx = (settings.footer_margin_top !== undefined ? parseFloat(settings.footer_margin_top) : 0.5) * conversionFactor;
    const footerMarginBottomPx = (settings.footer_margin_bottom !== undefined ? parseFloat(settings.footer_margin_bottom) : 1.0) * conversionFactor;

    const totalHeaderHeightPx = headerMarginTopPx + headerFontPx + headerMarginBottomPx;
    const totalFooterHeightPx = footerMarginTopPx + footerFontPx + footerMarginBottomPx;

    const paddingTopPx = (parseFloat(settings.padding_top) || 0) * conversionFactor;
    const paddingBottomPx = (parseFloat(settings.padding_bottom) || 0) * conversionFactor;

    const MAX_PAGE_CONTENT_HEIGHT = pageHeightPx - (totalHeaderHeightPx + totalFooterHeightPx + paddingTopPx + paddingBottomPx) - 20;

    // Crear un contenedor temporal oculto en pantalla para medir elementos
    const tempContainer = document.createElement('div');
    tempContainer.style.position = 'absolute';
    tempContainer.style.visibility = 'hidden';
    tempContainer.style.width = `${pageWidthPx - (parseFloat(settings.margin_left_odd ?? settings.margin_left ?? 2) + parseFloat(settings.margin_right_odd ?? settings.margin_right ?? 2) + parseFloat(settings.padding_left ?? 0) + parseFloat(settings.padding_right ?? 0)) * conversionFactor}px`;
    tempContainer.className = 'pdf-content';
    document.body.appendChild(tempContainer);

    scroller.innerHTML = '';
    let currentPageNumber = 1;

    bookState.chapters.forEach((chapter, index) => {
        // 1. Forzar paridad de página de inicio de capítulo si corresponde
        if (index > 0 && settings.chapter_start_parity && settings.chapter_start_parity !== 'any') {
            const isOdd = (currentPageNumber % 2 === 1);
            if (settings.chapter_start_parity === 'odd' && !isOdd) {
                // Siguiente página debería ser impar, pero es par. Inyectamos página en blanco
                const blankPage = createNewPageElement(currentPageNumber, '', false, true);
                blankPage.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(blankPage);
                currentPageNumber++;
            } else if (settings.chapter_start_parity === 'even' && isOdd) {
                // Siguiente página debería ser par, pero es impar. Inyectamos página en blanco
                const blankPage = createNewPageElement(currentPageNumber, '', false, true);
                blankPage.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(blankPage);
                currentPageNumber++;
            }
        }

        // Extraer definiciones de notas al pie de este capítulo
        const footnoteDefs = {};
        chapter.content.replace(/(?:^|\n)\[\^([^\]]+)\]:\s*([^\n]+)/g, (match, id, text) => {
            footnoteDefs[id] = text.trim();
            return '';
        });

        // Convertir texto de este capítulo a HTML
        let compiledHtml = compileMarkdownToHTML(chapter.content);
        if (chapter.title && chapter.title.trim() !== '') {
            // Se inyecta el título del capítulo como div al inicio del contenido HTML
            compiledHtml = `<div class="chapter-main-title">${chapter.title.trim()}</div>\n\n` + compiledHtml;
        }
        tempContainer.innerHTML = compiledHtml;
        const childNodes = Array.from(tempContainer.childNodes);

        let isFirstPageOfChapter = true;
        let currentPageEl = createNewPageElement(currentPageNumber, chapter.title, isFirstPageOfChapter, false);
        currentPageEl.setAttribute('data-chapter-id', chapter.id);
        scroller.appendChild(currentPageEl);
        let currentContentContainer = currentPageEl.querySelector('.pdf-content');
        let currentFootnotesContainer = currentPageEl.querySelector('.pdf-footnotes');

        let currentHeight = 0;
        let activePageFootnotes = [];

        childNodes.forEach(node => {
            const clonedNode = node.cloneNode(true);
            currentContentContainer.appendChild(clonedNode);

            // Buscar referencias a notas al pie en el nodo agregado
            const footnoteRefsInNode = [];
            if (clonedNode.classList && clonedNode.classList.contains('pdf-footnote-ref')) {
                footnoteRefsInNode.push(clonedNode);
            }
            if (clonedNode.querySelectorAll) {
                clonedNode.querySelectorAll('.pdf-footnote-ref').forEach(ref => {
                    footnoteRefsInNode.push(ref);
                });
            }

            // Respaldar notas de la página por si hay desbordamiento
            const previousPageFootnotes = [...activePageFootnotes];

            // Registrar notas nuevas encontradas
            let footnotesAdded = false;
            footnoteRefsInNode.forEach(ref => {
                const fnId = ref.getAttribute('data-footnote-id');
                const fnNum = ref.getAttribute('data-footnote-number');
                if (fnId && footnoteDefs[fnId] && !activePageFootnotes.some(fn => fn.id === fnId)) {
                    activePageFootnotes.push({ id: fnId, number: fnNum, text: footnoteDefs[fnId] });
                    footnotesAdded = true;
                }
            });

            if (footnotesAdded) {
                renderPageFootnotes(currentFootnotesContainer, activePageFootnotes);
            }

            // Medir la altura real renderizada en el DOM
            const nodeHeight = clonedNode.offsetHeight || 25;
            const footnotesHeight = currentFootnotesContainer && !currentFootnotesContainer.classList.contains('hidden') 
                ? currentFootnotesContainer.offsetHeight 
                : 0;

            if (currentHeight + nodeHeight + footnotesHeight > MAX_PAGE_CONTENT_HEIGHT) {
                currentContentContainer.removeChild(clonedNode);
                activePageFootnotes = previousPageFootnotes;
                renderPageFootnotes(currentFootnotesContainer, activePageFootnotes);

                currentPageNumber++;
                isFirstPageOfChapter = false;
                currentPageEl = createNewPageElement(currentPageNumber, chapter.title, isFirstPageOfChapter, false);
                currentPageEl.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(currentPageEl);
                currentContentContainer = currentPageEl.querySelector('.pdf-content');
                currentFootnotesContainer = currentPageEl.querySelector('.pdf-footnotes');

                activePageFootnotes = [];
                currentContentContainer.appendChild(clonedNode);

                // Re-procesar notas al pie en la nueva página
                const newFootnoteRefs = [];
                if (clonedNode.classList && clonedNode.classList.contains('pdf-footnote-ref')) {
                    newFootnoteRefs.push(clonedNode);
                }
                if (clonedNode.querySelectorAll) {
                    clonedNode.querySelectorAll('.pdf-footnote-ref').forEach(ref => {
                        newFootnoteRefs.push(ref);
                    });
                }
                newFootnoteRefs.forEach(ref => {
                    const fnId = ref.getAttribute('data-footnote-id');
                    const fnNum = ref.getAttribute('data-footnote-number');
                    if (fnId && footnoteDefs[fnId] && !activePageFootnotes.some(fn => fn.id === fnId)) {
                        activePageFootnotes.push({ id: fnId, number: fnNum, text: footnoteDefs[fnId] });
                    }
                });
                renderPageFootnotes(currentFootnotesContainer, activePageFootnotes);

                currentHeight = clonedNode.offsetHeight || 25;
            } else {
                currentHeight += nodeHeight;
            }
        });
        
        // Al terminar un capítulo, incrementamos el número de página para el siguiente
        currentPageNumber++;
    });

    // Ajustar número final de páginas
    const totalPages = currentPageNumber - 1;

    if (tempContainer.parentNode) {
        document.body.removeChild(tempContainer);
    }

    const indicator = document.getElementById('pdf-page-indicator');
    if (indicator) {
        indicator.textContent = `${totalPages} ${totalPages === 1 ? 'Página' : 'Páginas'}`;
    }

    // Scroll suave al capítulo activo
    setTimeout(() => {
        const activePage = scroller.querySelector(`.pdf-page[data-chapter-id="${bookState.activeChapterId}"]`);
        if (activePage) {
            activePage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 100);
}

// Crea la estructura HTML limpia de una página física virtual del libro
function createNewPageElement(pageNumber, chapterTitle, isFirstPageOfChapter = false, isBlankPage = false) {
    const pageDiv = document.createElement('div');
    pageDiv.className = 'pdf-page' + (isBlankPage ? ' blank-page' : '');
    
    const settings = bookState.settings || {};
    
    if (isBlankPage) {
        pageDiv.innerHTML = `
            <div class="pdf-header opacity-0" style="visibility:hidden;">&nbsp;</div>
            <div class="pdf-content flex items-center justify-center h-full" lang="${settings.content_language || 'es'}">
                <span class="text-xs text-[var(--text-muted)] italic print:hidden">(Página en blanco)</span>
            </div>
            <div class="pdf-footer opacity-0" style="visibility:hidden;">&nbsp;</div>
        `;
        return pageDiv;
    }

    const isEven = (pageNumber % 2 === 0);
    
    // Header Content
    let headerHtml = '&nbsp;';
    const showHeader = !isFirstPageOfChapter || (parseInt(settings.show_header_page_one) === 1);
    if (showHeader) {
        const headerType = isEven ? (settings.header_even_type || 'book_title') : (settings.header_odd_type || 'chapter_title');
        if (headerType === 'book_title') {
            headerHtml = `<span>${bookState.title}</span>`;
        } else if (headerType === 'chapter_title') {
            headerHtml = `<span>${chapterTitle || 'Sin título'}</span>`;
        } else if (headerType === 'custom') {
            const customText = isEven ? (settings.header_even_custom || '') : (settings.header_odd_custom || '');
            headerHtml = `<span>${customText}</span>`;
        }
    }

    // Footer Content
    let footerHtml = '&nbsp;';
    if (showHeader) {
        const footerType = isEven ? (settings.footer_even_type || 'page_number') : (settings.footer_odd_type || 'page_number');
        if (footerType === 'page_number') {
            footerHtml = `<span>${pageNumber}</span>`;
        }
    }

    // Alignment and layout for chapter start
    let contentClass = 'pdf-content';
    let contentStyle = '';
    if (isFirstPageOfChapter) {
        contentClass += ' chapter-first-page';
        const align = settings.chapter_page_one_align || 'center';
        contentStyle += ` text-align: ${align};`;
        if (settings.chapter_page_one_vertical === 'half') {
            contentClass += ' flex flex-col justify-center';
        }
    }

    pageDiv.innerHTML = `
        <div class="pdf-header text-xs">${headerHtml}</div>
        <div class="${contentClass}" style="${contentStyle}" lang="${settings.content_language || 'es'}"></div>
        <div class="pdf-footnotes hidden"></div>
        <div class="pdf-footer text-xs">${footerHtml}</div>
    `;

    return pageDiv;
}

// Activa la orden de impresión nativa de la previsualización PDF
function triggerPrint() {
    compilePDFPreview();

    const settings = bookState.settings || {};
    const unit = settings.unit || 'cm';
    let width = settings.page_width || 21;
    let height = settings.page_height || 29.7;

    if (settings.page_size === 'A4') {
        width = (unit === 'cm') ? 21.0 : (21.0 / 2.54);
        height = (unit === 'cm') ? 29.7 : (29.7 / 2.54);
    } else if (settings.page_size === 'Letter') {
        width = (unit === 'cm') ? (8.5 * 2.54) : 8.5;
        height = (unit === 'cm') ? (11.0 * 2.54) : 11.0;
    }

    // Recoger todas las páginas del scroller
    const scroller = document.getElementById('pdf-scroller');
    const pagesHtml = scroller ? scroller.innerHTML : '<p>Sin contenido</p>';

    // Recoger todos los estilos de la página actual (incluyendo fuentes de Google y estilos dinámicos)
    let stylesHtml = '';
    document.querySelectorAll('link[rel="stylesheet"], style').forEach(el => {
        stylesHtml += el.outerHTML;
    });

    // Construir el HTML de la ventana de impresión limpia
    const printContent = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>${bookState.title || 'Libro'}</title>
    ${stylesHtml}
    <style>
        @page {
            size: ${width}${unit} ${height}${unit};
            margin: 0mm;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
        }
        .pdf-page {
            margin: 0 !important;
            box-shadow: none !important;
            border: none !important;
            page-break-after: always;
            page-break-inside: avoid;
            break-after: page;
        }
        .pdf-page:last-child {
            page-break-after: auto;
        }
    </style>
</head>
<body>
    ${pagesHtml}
</body>
</html>`;

    const printWin = window.open('', '_blank', `width=${Math.round(width * 37.8)},height=${Math.round(height * 37.8)}`);
    if (!printWin) {
        showToast('Activa los popups para este sitio e intenta de nuevo.', 'fa-solid fa-triangle-exclamation');
        return;
    }
    printWin.document.open();
    printWin.document.write(printContent);
    printWin.document.close();

    // Esperar a que carguen las fuentes antes de lanzar el diálogo de impresión
    printWin.onload = function() {
        setTimeout(() => {
            printWin.focus();
            printWin.print();
        }, 1000);
    };
}

function exportHTML() {
    showToast("Generando PDF de alta resolución... Por favor espera.", "fa-solid fa-spinner fa-spin");
    
    // Configuración actual
    const settings = bookState.settings || {};
    const unit = settings.unit || 'cm';
    let width = settings.page_width || 21;
    let height = settings.page_height || 29.7;

    if (settings.page_size === 'A4') {
        width = (unit === 'cm') ? 21.0 : (21.0 / 2.54);
        height = (unit === 'cm') ? 29.7 : (29.7 / 2.54);
    } else if (settings.page_size === 'Letter') {
        width = (unit === 'cm') ? (8.5 * 2.54) : 8.5;
        height = (unit === 'cm') ? (11.0 * 2.54) : 11.0;
    }

    // Asegurarnos de que el panel está compilado
    compilePDFPreview();

    const scrollerPane = document.getElementById('pdf-scroller');
    
    // Aplicamos una clase al body para modificar visualmente el panel de preview
    // y que html2canvas lo lea perfecto (sin márgenes ni sombras ni cabeceras de UI).
    document.body.classList.add('exporting-pdf-mode');

    // Regla CSS para ocultar sangrías y sombras temporalmente
    const tempStyle = document.createElement('style');
    tempStyle.innerHTML = `
        .exporting-pdf-mode #pdf-scroller {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
            gap: 0 !important;
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
        }
        .exporting-pdf-mode .pdf-page {
            margin: 0 !important;
            box-shadow: none !important;
            border: none !important;
            page-break-after: always !important;
            transform: none !important;
            /* Forza al tamaño exacto durante renderizado */
            width: ${width}${unit} !important;
            height: ${height}${unit} !important;
            /* html2canvas tiene un bug grave con hyphens y text-align justify */
            hyphens: none !important;
        }
        .exporting-pdf-mode .pdf-page::after {
            display: none !important;
        }
    `;
    document.head.appendChild(tempStyle);

    // Pequeño timeout para que el navegador pinte los nuevos estilos antes de capturar
    setTimeout(() => {
        // Convertir a cm para jsPDF
        const widthCm = (unit === 'in') ? width * 2.54 : width;
        const heightCm = (unit === 'in') ? height * 2.54 : height;

        const opt = {
            margin:       0,
            filename:     `${bookState.title ? bookState.title.replace(/\s+/g, '_') : 'Libro'}.pdf`,
            image:        { type: 'jpeg', quality: 1.0 },
            html2canvas:  { 
                scale: 2, 
                useCORS: true,
                scrollY: 0,
                scrollX: 0
            },
            jsPDF:        { unit: 'cm', format: [widthCm, heightCm], orientation: 'portrait' },
            pagebreak:    { mode: 'css' }
        };

        html2pdf().set(opt).from(scrollerPane).save().then(() => {
            showToast("¡PDF descargado con éxito!", "fa-solid fa-check");
            document.body.classList.remove('exporting-pdf-mode');
            document.head.removeChild(tempStyle);
        }).catch(err => {
            console.error(err);
            showToast("Error al exportar el PDF.", "fa-solid fa-triangle-exclamation");
            document.body.classList.remove('exporting-pdf-mode');
            document.head.removeChild(tempStyle);
        });
    }, 500); // 500ms para asegurar el repintado
}

// Inyecta dinámicamente las reglas de CSS en base a la configuración de maquetación actual
function applyDynamicPDFStyles() {
    const settings = bookState.settings;
    const styleEl = document.getElementById('dynamic-pdf-settings');
    if (!settings || !styleEl) return;

    const unit = settings.unit || 'cm';
    let width = settings.page_width || 21;
    let height = settings.page_height || 29.7;

    if (settings.page_size === 'A4') {
        width = (unit === 'cm') ? 21.0 : (21.0 / 2.54);
        height = (unit === 'cm') ? 29.7 : (29.7 / 2.54);
    } else if (settings.page_size === 'Letter') {
        width = (unit === 'cm') ? (8.5 * 2.54) : 8.5;
        height = (unit === 'cm') ? (11.0 * 2.54) : 11.0;
    }

    const bleeding = parseFloat(settings.bleeding) || 0;

    styleEl.innerHTML = `
        @page {
            size: ${width}${unit} ${height}${unit};
            margin: 0mm;
        }
        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
            }
            .pdf-page {
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
            .pdf-page::after {
                display: none !important;
            }
        }
        .pdf-page {
            display: flex !important;
            flex-direction: column !important;
            width: ${width}${unit} !important;
            height: ${height}${unit} !important;
            min-height: ${height}${unit} !important;
            padding: 0 !important;
            margin-top: calc(12px + ${bleeding}${unit}) !important;
            margin-bottom: calc(12px + ${bleeding}${unit}) !important;
            border: ${bleeding > 0 ? '2px dashed #f59e0b' : '1px solid #e2e8f0'} !important;
            position: relative;
            box-sizing: border-box !important;
            background-color: white !important;
            color: #1e293b !important;
        }
        ${bleeding > 0 ? `
        .pdf-page::after {
            content: "Línea de Sangría (${bleeding}${unit})";
            position: absolute;
            top: -15px;
            left: 0;
            font-size: 8px;
            color: #f59e0b;
            font-family: sans-serif;
            font-weight: bold;
        }
        ` : ''}
        .pdf-header {
            box-sizing: border-box !important; margin: 0 !important;
            padding-top: ${settings.header_margin_top !== undefined ? settings.header_margin_top : 1.0}${unit} !important;
            padding-bottom: ${settings.header_margin_bottom !== undefined ? settings.header_margin_bottom : 0.5}${unit} !important;
            font-family: '${settings.header_font_family || 'Merriweather'}', serif !important;
            font-size: ${settings.header_font_size || 8.5}pt !important;
            font-weight: ${settings.header_font_weight || 'normal'} !important;
            font-style: ${settings.header_font_style || 'normal'} !important;
            letter-spacing: ${settings.header_letter_spacing || 0.1}pt !important;
            color: #475569 !important;
            text-align: ${settings.header_align || 'center'} !important;
        }
        .pdf-content {
            flex: 1 !important;
            box-sizing: border-box !important;
            padding-top: ${settings.padding_top}${unit} !important;
            padding-bottom: ${settings.padding_bottom}${unit} !important;
            margin-left: 0px !important;
            margin-right: 0px !important;
            font-family: '${settings.font_family_content || 'Merriweather'}', serif !important;
            font-size: ${settings.font_size_content || 11.5}pt !important;
            line-height: ${settings.line_height_content || 1.65} !important;
        }
        .pdf-content p, .pdf-content ul, .pdf-content ol {
            font-family: '${settings.font_family_content || 'Merriweather'}', serif !important;
            font-size: ${settings.font_size_content || 11.5}pt !important;
            line-height: ${settings.line_height_content || 1.65} !important;
            text-align: ${settings.content_text_align || 'justify'} !important;
            hyphens: ${parseInt(settings.content_hyphenation) === 1 ? 'auto' : 'none'} !important;
            -webkit-hyphens: ${parseInt(settings.content_hyphenation) === 1 ? 'auto' : 'none'} !important;
        }
        .pdf-content p {
            margin-bottom: ${settings.content_paragraph_spacing !== undefined ? settings.content_paragraph_spacing : 14.0}pt !important;
            text-indent: ${settings.content_paragraph_indent !== undefined ? settings.content_paragraph_indent : 0.0}pt !important;
        }
        .pdf-content .chapter-main-title + p, 
        .pdf-content h1 + p, 
        .pdf-content h2 + p {
            text-indent: 0 !important;
        }
        /* Encabezados y Tipografía en General */
        .pdf-content h1 {
            font-family: '${settings.font_family_h1 || 'Playfair Display'}', serif !important;
            font-weight: ${settings.font_weight_h1 || 'bold'} !important;
            font-size: ${settings.font_size_h1 || 24}pt !important;
            margin-top: 30px !important;
            margin-bottom: 20px !important;
            line-height: 1.3 !important;
            page-break-after: avoid;
        }
        .pdf-content h2 {
            font-family: '${settings.font_family_h2 || 'Playfair Display'}', serif !important;
            font-weight: ${settings.font_weight_h2 || 'bold'} !important;
            font-size: ${settings.font_size_h2 || 16}pt !important;
            margin-top: 25px !important;
            margin-bottom: 15px !important;
            line-height: 1.3 !important;
            page-break-after: avoid;
        }
        .pdf-content h3 {
            font-family: '${settings.font_family_h3 || 'Playfair Display'}', serif !important;
            font-weight: ${settings.font_weight_h3 || 'bold'} !important;
            font-size: ${settings.font_size_h3 || 13}pt !important;
            margin-top: 20px !important;
            margin-bottom: 10px !important;
            line-height: 1.3 !important;
            page-break-after: avoid;
        }
        /* Título Principal de Capítulo (Chapter Name) */
        .pdf-content .chapter-main-title {
            font-family: '${settings.chapter_title_font_family || 'Playfair Display'}', serif !important;
            font-size: ${settings.chapter_title_font_size || 24}pt !important;
            font-weight: ${settings.chapter_title_font_weight || 'bold'} !important;
            font-style: ${settings.chapter_title_font_style || 'normal'} !important;
            text-align: ${settings.chapter_title_align || 'center'} !important;
            padding-top: ${parseFloat(settings.chapter_title_padding_top ?? 0)}${unit} !important;
            padding-bottom: ${parseFloat(settings.chapter_title_padding_bottom ?? 1.5)}${unit} !important;
            page-break-after: avoid;
            width: 100%;
        }
        .pdf-footnotes {
            box-sizing: border-box !important;
            margin-top: 8px !important;
            padding-top: 6px !important;
            padding-bottom: 4px !important;
            font-family: '${settings.font_family_content || 'Merriweather'}', serif !important;
            background-color: white !important;
            color: #475569 !important;
        }
        .pdf-footnote-item {
            margin-bottom: 4px !important;
            line-height: 1.4 !important;
            text-align: left !important;
            font-size: ${Math.round((settings.font_size_content || 11.5) * 0.75 * 10) / 10}pt !important;
        }
        .pdf-footer {
            box-sizing: border-box !important; margin: 0 !important;
            padding-top: ${settings.footer_margin_top !== undefined ? settings.footer_margin_top : 0.5}${unit} !important;
            padding-bottom: ${settings.footer_margin_bottom !== undefined ? settings.footer_margin_bottom : 1.0}${unit} !important;
            font-family: '${settings.footer_font_family || 'Merriweather'}', serif !important;
            font-size: ${settings.footer_font_size || 9.0}pt !important;
            font-weight: ${settings.footer_font_weight || 'normal'} !important;
            font-style: ${settings.footer_font_style || 'normal'} !important;
            letter-spacing: ${settings.footer_letter_spacing || 0.0}pt !important;
            color: #475569 !important;
            text-align: ${settings.footer_align || 'center'} !important;
        }

        /* Márgenes de Página Impar (Odd) */
        .pdf-page:nth-child(odd) .pdf-header,
        .pdf-page:nth-child(odd) .pdf-content,
        .pdf-page:nth-child(odd) .pdf-footnotes,
        .pdf-page:nth-child(odd) .pdf-footer {
            padding-left: ${parseFloat(settings.margin_left_odd ?? settings.margin_left ?? 2) + parseFloat(settings.padding_left ?? 0)}${unit} !important;
            padding-right: ${parseFloat(settings.margin_right_odd ?? settings.margin_right ?? 2) + parseFloat(settings.padding_right ?? 0)}${unit} !important;
        }

        /* Márgenes de Página Par (Even) */
        .pdf-page:nth-child(even) .pdf-header,
        .pdf-page:nth-child(even) .pdf-content,
        .pdf-page:nth-child(even) .pdf-footnotes,
        .pdf-page:nth-child(even) .pdf-footer {
            padding-left: ${parseFloat(settings.margin_left_even ?? settings.margin_left ?? 2) + parseFloat(settings.padding_left ?? 0)}${unit} !important;
            padding-right: ${parseFloat(settings.margin_right_even ?? settings.margin_right ?? 2) + parseFloat(settings.padding_right ?? 0)}${unit} !important;
        }
    `;

    compilePDFPreview();
}
