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
        <div class="${contentClass}" style="${contentStyle}" lang="${settings.content_language || 'es'}"></div>
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
        let currentContentContainer  = currentPageEl.querySelector('.pdf-content');
        let currentFootnotesContainer = currentPageEl.querySelector('.pdf-footnotes');

        let currentHeight = 0;
        let activePageFootnotes = [];

        childNodes.forEach(node => {
            const clonedNode = node.cloneNode(true);
            currentContentContainer.appendChild(clonedNode);

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

            const nodeHeight      = clonedNode.offsetHeight || 25;
            const footnotesHeight = currentFootnotesContainer && !currentFootnotesContainer.classList.contains('hidden')
                ? currentFootnotesContainer.offsetHeight : 0;

            if (currentHeight + nodeHeight + footnotesHeight > MAX_PAGE_CONTENT_HEIGHT) {
                currentContentContainer.removeChild(clonedNode);
                activePageFootnotes = previousPageFootnotes;
                renderPageFootnotes(currentFootnotesContainer, activePageFootnotes);

                currentPageNumber++;
                isFirstPageOfChapter = false;
                currentPageEl = createNewPageElement(currentPageNumber, chapter.title, isFirstPageOfChapter, false);
                currentPageEl.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(currentPageEl);
                currentContentContainer  = currentPageEl.querySelector('.pdf-content');
                currentFootnotesContainer = currentPageEl.querySelector('.pdf-footnotes');

                activePageFootnotes = [];
                currentContentContainer.appendChild(clonedNode);

                // Re-procesar notas al pie para la nueva página
                const newFootnoteRefs = [];
                if (clonedNode.classList && clonedNode.classList.contains('pdf-footnote-ref')) newFootnoteRefs.push(clonedNode);
                if (clonedNode.querySelectorAll) clonedNode.querySelectorAll('.pdf-footnote-ref').forEach(ref => newFootnoteRefs.push(ref));
                newFootnoteRefs.forEach(ref => {
                    const fnId  = ref.getAttribute('data-footnote-id');
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
