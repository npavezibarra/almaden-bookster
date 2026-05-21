// ============================================================
// MÓDULO: editor-pdf-compiler.js
// Responsabilidad: Motor de paginación y renderizado de páginas
// virtuales del libro en el panel de Vista Previa.
// ============================================================

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
        <div class="${contentClass}" style="${contentStyle}" lang="${settings.content_language || 'es'}">
            <div class="pdf-content-inner"></div>
        </div>
        <div class="pdf-footnotes hidden"></div>
        <div class="pdf-footer text-xs">${footerHtml}</div>
    `;

    return pageDiv;
}

// Paginación interactiva: divide inteligentemente el contenido en hojas virtuales
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
    const headerMarginTopPx    = (settings.header_margin_top    !== undefined ? parseFloat(settings.header_margin_top)    : 1.0) * conversionFactor;
    const headerMarginBottomPx = (settings.header_margin_bottom !== undefined ? parseFloat(settings.header_margin_bottom) : 0.5) * conversionFactor;
    const footerMarginTopPx    = (settings.footer_margin_top    !== undefined ? parseFloat(settings.footer_margin_top)    : 0.5) * conversionFactor;
    const footerMarginBottomPx = (settings.footer_margin_bottom !== undefined ? parseFloat(settings.footer_margin_bottom) : 1.0) * conversionFactor;

    const totalHeaderHeightPx = headerMarginTopPx + headerFontPx + headerMarginBottomPx;
    const totalFooterHeightPx = footerMarginTopPx + footerFontPx + footerMarginBottomPx;
    const paddingTopPx    = (parseFloat(settings.padding_top)    || 0) * conversionFactor;
    const paddingBottomPx = (parseFloat(settings.padding_bottom) || 0) * conversionFactor;

    const MAX_PAGE_CONTENT_HEIGHT = pageHeightPx - (totalHeaderHeightPx + totalFooterHeightPx + paddingTopPx + paddingBottomPx) - 20;

    // Contenedor temporal oculto para medir elementos sin mostrarlos
    const tempContainer = document.createElement('div');
    tempContainer.style.position = 'absolute';
    tempContainer.style.visibility = 'hidden';
    tempContainer.style.width = `${pageWidthPx - (parseFloat(settings.margin_left_odd ?? settings.margin_left ?? 2) + parseFloat(settings.margin_right_odd ?? settings.margin_right ?? 2) + parseFloat(settings.padding_left ?? 0) + parseFloat(settings.padding_right ?? 0)) * conversionFactor}px`;
    tempContainer.className = 'pdf-content';
    document.body.appendChild(tempContainer);

    scroller.innerHTML = '';
    let currentPageNumber = 1;

    bookState.chapters.forEach((chapter, index) => {
        // Forzar paridad de página de inicio de capítulo si corresponde
        if (index > 0 && settings.chapter_start_parity && settings.chapter_start_parity !== 'any') {
            const isOdd = (currentPageNumber % 2 === 1);
            if (settings.chapter_start_parity === 'odd' && !isOdd) {
                const blankPage = createNewPageElement(currentPageNumber, '', false, true);
                blankPage.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(blankPage);
                currentPageNumber++;
            } else if (settings.chapter_start_parity === 'even' && isOdd) {
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

        let compiledHtml = compileMarkdownToHTML(chapter.content);
        if (chapter.title && chapter.title.trim() !== '') {
            compiledHtml = `<div class="chapter-main-title">${chapter.title.trim()}</div>\n\n` + compiledHtml;
        }
        tempContainer.innerHTML = compiledHtml;
        const childNodes = Array.from(tempContainer.childNodes);

        let isFirstPageOfChapter = true;
        let currentPageEl = createNewPageElement(currentPageNumber, chapter.title, isFirstPageOfChapter, false);
        currentPageEl.setAttribute('data-chapter-id', chapter.id);
        scroller.appendChild(currentPageEl);
        let currentInnerContainer = currentPageEl.querySelector('.pdf-content-inner');
        let currentFootnotesContainer = currentPageEl.querySelector('.pdf-footnotes');

        let activePageFootnotes = [];

        // Helper para dividir párrafos entre páginas
        function splitParagraphAcrossPages(pNode, innerContainer, footnotesHeight, maxTotalHeight) {
            if (pNode.tagName !== 'P') return null;
            const originalChildNodes = Array.from(pNode.childNodes);
            pNode.innerHTML = '';
            
            const secondHalfNode = pNode.cloneNode(false);
            let overflowed = false;

            function processChild(child, target1, target2) {
                if (overflowed) {
                    target2.appendChild(child.cloneNode(true));
                    return;
                }
                if (child.nodeType === Node.TEXT_NODE) {
                    const words = child.textContent.split(/(\s+)/);
                    let remainderText = '';
                    const textNode1 = document.createTextNode('');
                    target1.appendChild(textNode1);
                    
                    for (let i = 0; i < words.length; i++) {
                        if (overflowed) {
                            remainderText += words[i];
                        } else {
                            const prevText = textNode1.data;
                            textNode1.data += words[i];
                            if (innerContainer.offsetHeight + footnotesHeight > maxTotalHeight) {
                                overflowed = true;
                                textNode1.data = prevText;
                                remainderText += words[i];
                            }
                        }
                    }
                    if (remainderText) target2.appendChild(document.createTextNode(remainderText));
                } else if (child.nodeType === Node.ELEMENT_NODE) {
                    target1.appendChild(child);
                    if (innerContainer.offsetHeight + footnotesHeight > maxTotalHeight) {
                        target1.removeChild(child);
                        const part1 = child.cloneNode(false);
                        const part2 = child.cloneNode(false);
                        target1.appendChild(part1);
                        target2.appendChild(part2);
                        
                        Array.from(child.childNodes).forEach(sub => processChild(sub, part1, part2));
                        
                        if (part1.childNodes.length === 0) target1.removeChild(part1);
                        if (part2.childNodes.length === 0) target2.removeChild(part2);
                    }
                }
            }

            originalChildNodes.forEach(child => processChild(child, pNode, secondHalfNode));
            if (pNode.textContent.trim() === '') return null;
            return secondHalfNode;
        }

        // Iterar usando un while para poder inyectar partes remanentes
        while (childNodes.length > 0) {
            const node = childNodes.shift();
            const clonedNode = node.cloneNode(true);
            currentInnerContainer.appendChild(clonedNode);

            const footnoteRefsInNode = [];
            if (clonedNode.classList && clonedNode.classList.contains('pdf-footnote-ref')) footnoteRefsInNode.push(clonedNode);
            if (clonedNode.querySelectorAll) clonedNode.querySelectorAll('.pdf-footnote-ref').forEach(ref => footnoteRefsInNode.push(ref));

            const previousPageFootnotes = [...activePageFootnotes];
            let footnotesAdded = false;
            footnoteRefsInNode.forEach(ref => {
                const fnId  = ref.getAttribute('data-footnote-id');
                const fnNum = ref.getAttribute('data-footnote-number');
                if (fnId && footnoteDefs[fnId] && !activePageFootnotes.some(fn => fn.id === fnId)) {
                    activePageFootnotes.push({ id: fnId, number: fnNum, text: footnoteDefs[fnId] });
                    footnotesAdded = true;
                }
            });
            if (footnotesAdded) renderPageFootnotes(currentFootnotesContainer, activePageFootnotes);

            const footnotesHeight = currentFootnotesContainer && !currentFootnotesContainer.classList.contains('hidden')
                ? currentFootnotesContainer.offsetHeight : 0;

            if (currentInnerContainer.offsetHeight + footnotesHeight > MAX_PAGE_CONTENT_HEIGHT) {
                // Intento dividir el nodo
                let remainderNode = null;
                if (clonedNode.tagName === 'P') {
                    // splitParagraphAcrossPages modifica clonedNode inplace (dejando solo lo que cabe)
                    remainderNode = splitParagraphAcrossPages(clonedNode, currentInnerContainer, footnotesHeight, MAX_PAGE_CONTENT_HEIGHT);
                }

                if (!remainderNode) {
                    // No se pudo dividir (o no es un P). Quitamos el nodo entero y lo pasamos a la siguiente página
                    currentInnerContainer.removeChild(clonedNode);
                    activePageFootnotes = previousPageFootnotes;
                    renderPageFootnotes(currentFootnotesContainer, activePageFootnotes);
                    // Re-encolamos el nodo entero al principio
                    childNodes.unshift(node);
                } else {
                    // Se dividió. remainderNode tiene la segunda mitad.
                    childNodes.unshift(remainderNode);
                }

                // Crear nueva página
                currentPageNumber++;
                isFirstPageOfChapter = false;
                currentPageEl = createNewPageElement(currentPageNumber, chapter.title, isFirstPageOfChapter, false);
                currentPageEl.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(currentPageEl);
                currentInnerContainer = currentPageEl.querySelector('.pdf-content-inner');
                currentFootnotesContainer = currentPageEl.querySelector('.pdf-footnotes');
                activePageFootnotes = [];
            }
        }

        currentPageNumber++;
    });

    const totalPages = currentPageNumber - 1;
    if (tempContainer.parentNode) document.body.removeChild(tempContainer);

    const indicator = document.getElementById('pdf-page-indicator');
    if (indicator) indicator.textContent = `${totalPages} ${totalPages === 1 ? 'Página' : 'Páginas'}`;

    // Scroll suave al capítulo activo
    setTimeout(() => {
        const activePage = scroller.querySelector(`.pdf-page[data-chapter-id="${bookState.activeChapterId}"]`);
        if (activePage) activePage.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}
