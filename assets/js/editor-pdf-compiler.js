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
function createNewPageElement(pageNumber, chapter, isFirstPageOfChapter = false, isBlankPage = false) {
    const pageDiv = document.createElement('div');
    pageDiv.className = 'pdf-page' + (isBlankPage ? ' blank-page' : '');

    const settings = bookState.settings || {};
    
    // Extraer ajustes locales con fallback a globales
    const parityImageUrl = chapter ? chapter.parity_image : null;
    const chapterTitle = chapter ? chapter.title : '';
    const parityImageMode = (chapter && chapter.parity_image_mode) ? chapter.parity_image_mode : (settings.parity_image_mode || 'content');
    const showHeaderPageOne = (chapter && chapter.show_header_page_one === '1') ? true : (parseInt(settings.show_header_page_one) === 1);
    const customRunningHeader = (chapter && chapter.custom_running_header) ? chapter.custom_running_header : null;
    const pageOneVertical = (chapter && chapter.page_one_vertical) ? chapter.page_one_vertical : (settings.chapter_page_one_vertical || 'top');
    const disableHyphenation = (chapter && chapter.disable_hyphenation === '1');

    if (isBlankPage) {
        if (parityImageUrl) {
            const mode = parityImageMode;
            
            if (mode === 'bleed') {
                pageDiv.classList.add('pdf-page-has-bleed');
                pageDiv.innerHTML = `
                    <div class="parity-bleed-container" style="position: absolute; z-index: 0;">
                        <img src="${parityImageUrl}" alt="Página de paridad" style="width: 100%; height: 100%; object-fit: cover;" />
                    </div>
                    <!-- Línea de corte visual (no se imprime) -->
                    <div class="print:hidden" style="position: absolute; top: 0; bottom: 0; left: 0; right: 0; border: 1px dashed red; pointer-events: none; z-index: 10;"></div>
                `;
            } else if (mode === 'fullpage') {
                pageDiv.innerHTML = `
                    <div style="position: absolute; top: 0; bottom: 0; left: 0; right: 0; z-index: 0;">
                        <img src="${parityImageUrl}" alt="Página de paridad" style="width: 100%; height: 100%; object-fit: cover;" />
                    </div>
                `;
            } else { // mode === 'content'
                pageDiv.innerHTML = `
                    <div class="pdf-header opacity-0" style="visibility:hidden;">&nbsp;</div>
                    <div class="pdf-content" style="padding: 0; display: flex; width: 100%; height: 100%; position: relative; z-index: 0;">
                        <img src="${parityImageUrl}" alt="Página de paridad" style="width: 100%; height: 100%; object-fit: contain;" />
                    </div>
                    <div class="pdf-footer opacity-0" style="visibility:hidden;">&nbsp;</div>
                `;
            }
        } else {
            pageDiv.innerHTML = `
                <div class="pdf-header opacity-0" style="visibility:hidden;">&nbsp;</div>
                <div class="pdf-content flex items-center justify-center h-full" lang="${settings.content_language || 'es'}">
                    <span class="text-xs text-[var(--text-muted)] italic print:hidden">(Página en blanco)</span>
                </div>
                <div class="pdf-footer opacity-0" style="visibility:hidden;">&nbsp;</div>
            `;
        }
        return pageDiv;
    }

    const isEven = (pageNumber % 2 === 0);

    // Header Content
    let headerHtml = '&nbsp;';
    const showHeader = !isFirstPageOfChapter || showHeaderPageOne;
    if (showHeader) {
        const headerType = isEven ? (settings.header_even_type || 'book_title') : (settings.header_odd_type || 'chapter_title');
        if (headerType === 'book_title') {
            headerHtml = `<span>${bookState.title}</span>`;
        } else if (headerType === 'chapter_title') {
            headerHtml = `<span>${customRunningHeader ? customRunningHeader : (chapterTitle || 'Sin título')}</span>`;
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
    
    if (disableHyphenation) {
        contentStyle += ' hyphens: none;';
    }
    
    if (isFirstPageOfChapter) {
        contentClass += ' chapter-first-page';
        const align = settings.chapter_page_one_align || 'center';
        contentStyle += ` text-align: ${align};`;
        if (pageOneVertical === 'half' || pageOneVertical === 'center') {
            contentClass += ' flex flex-col justify-center';
        } else if (pageOneVertical === 'bottom') {
            contentClass += ' flex flex-col justify-end';
        }
    }

    pageDiv.innerHTML = `
        <div class="pdf-header text-xs">${headerHtml}</div>
        <div class="${contentClass}" style="${contentStyle}" lang="${settings.content_language || 'es'}">
            <div class="pdf-content-inner" style="display: flow-root;"></div>
        </div>
        <div class="pdf-footnotes hidden"></div>
        <div class="pdf-footer text-xs">${footerHtml}</div>
    `;

    return pageDiv;
}

// Paginación interactiva: divide inteligentemente el contenido en hojas virtuales
window._pdfCompileCounter = 0;
async function compilePDFPreview() {
    const currentVersion = ++window._pdfCompileCounter;
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
    let width = parseFloat(settings.page_width) || 21.0;
    let height = parseFloat(settings.page_height) || 29.7;

    if (settings.page_size === 'A4') {
        width = (unit === 'cm') ? 21.0 : (21.0 / 2.54);
        height = (unit === 'cm') ? 29.7 : (29.7 / 2.54);
    } else if (settings.page_size === 'Letter') {
        width = (unit === 'cm') ? (8.5 * 2.54) : 8.5;
        height = (unit === 'cm') ? (11.0 * 2.54) : 11.0;
    }

    const conversionFactor = (unit === 'cm') ? 37.7952755906 : 96.0;
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

    for (let index = 0; index < bookState.chapters.length; index++) {
        const chapter = bookState.chapters[index];
        // Determinar paridad
        const chapterStartParity = (chapter.start_parity && chapter.start_parity !== 'any') ? chapter.start_parity : settings.chapter_start_parity;
        
        if (index > 0 && chapterStartParity && chapterStartParity !== 'any') {
            const isOdd = (currentPageNumber % 2 === 1);
            if (chapterStartParity === 'odd' && !isOdd) {
                const blankPage = createNewPageElement(currentPageNumber, chapter, false, true);
                blankPage.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(blankPage);
                currentPageNumber++;
            } else if (chapterStartParity === 'even' && isOdd) {
                const blankPage = createNewPageElement(currentPageNumber, chapter, false, true);
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
        
        // Letra Capitular (Drop Cap)
        if (chapter.drop_cap_enabled === '1') {
            // Reemplazar la primera p para agregar la clase drop-cap
            compiledHtml = compiledHtml.replace(/<p>/, '<p class="drop-cap">');
        }

        if (chapter.title && chapter.title.trim() !== '' && chapter.hide_title !== '1') {
            compiledHtml = `<div class="chapter-main-title">${chapter.title.trim()}</div>\n\n` + compiledHtml;
        }
        tempContainer.innerHTML = compiledHtml;

        // Esperar a que las imágenes se carguen para poder medir su altura real
        const images = Array.from(tempContainer.querySelectorAll('img'));
        const imagePromises = images.map(img => {
            if (img.complete) return Promise.resolve();
            return new Promise(resolve => {
                img.onload = resolve;
                img.onerror = resolve; // Si falla, continuamos igual
            });
        });
        
        if (imagePromises.length > 0) {
            await Promise.all(imagePromises);
            // Si el usuario siguió escribiendo y gatilló otra compilación, abortamos esta
            if (window._pdfCompileCounter !== currentVersion) {
                if (tempContainer.parentNode) document.body.removeChild(tempContainer);
                return;
            }
        }

        const childNodes = Array.from(tempContainer.childNodes).filter(node => {
            if (node.nodeType === Node.ELEMENT_NODE && (node.classList.contains('uploader-inline') || node.classList.contains('uploader-editor'))) {
                return false;
            }
            return true;
        });

        let isFirstPageOfChapter = true;
        let currentPageEl = createNewPageElement(currentPageNumber, chapter, isFirstPageOfChapter, false);
        currentPageEl.setAttribute('data-chapter-id', chapter.id);
        scroller.appendChild(currentPageEl);
        let currentInnerContainer = currentPageEl.querySelector('.pdf-content-inner');
        let currentFootnotesContainer = currentPageEl.querySelector('.pdf-footnotes');

        let activePageFootnotes = [];

        // Medir la altura REAL disponible para contenido directamente del DOM.
        // El cálculo teórico (MAX_PAGE_CONTENT_HEIGHT) sub-estima la altura del header/footer
        // porque ignora el multiplicador line-height heredado, lo que provoca que la última
        // línea sea recortada visualmente por overflow:hidden en .pdf-content.
        function measureActualMaxHeight(pageEl) {
            const pdfContent = pageEl.querySelector('.pdf-content');
            if (!pdfContent) return MAX_PAGE_CONTENT_HEIGHT;
            const style = window.getComputedStyle(pdfContent);
            const pt = parseFloat(style.paddingTop)  || 0;
            const pb = parseFloat(style.paddingBottom) || 0;
            // clientHeight ya excluye el borde; restamos padding y 2px de seguridad sub-pixel
            return pdfContent.clientHeight - pt - pb - 2;
        }

        let currentMaxContentHeight = measureActualMaxHeight(currentPageEl);

        // Helper para dividir párrafos entre páginas
        function splitParagraphAcrossPages(pNode, innerContainer, footnotesHeight, maxTotalHeight) {
            if (pNode.tagName !== 'P') return null;
            const originalChildNodes = Array.from(pNode.childNodes);
            pNode.innerHTML = '';
            
            const secondHalfNode = pNode.cloneNode(false);
            let overflowed = false;

            function getEffectiveHeight() {
                let bottomMargin = 0;
                const lastChild = innerContainer.lastElementChild;
                if (lastChild) {
                    const style = window.getComputedStyle(lastChild);
                    bottomMargin = parseFloat(style.marginBottom) || 0;
                }
                return innerContainer.offsetHeight - bottomMargin;
            }

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
                    const useHyphenation = bookState.settings && parseInt(bookState.settings.content_hyphenation) === 1;

                    for (let i = 0; i < words.length; i++) {
                        if (overflowed) {
                            remainderText += words[i];
                        } else {
                            const prevText = textNode1.data;
                            textNode1.data += words[i];
                            if (getEffectiveHeight() + footnotesHeight > maxTotalHeight) {
                                overflowed = true;
                                textNode1.data = prevText;
                                const word = words[i];

                                // Intentar división con guión si hyphenation está activo y la palabra es larga
                                const isHyphenable = useHyphenation && word.length >= 4 && !word.includes('-');
                                let splitAt = 0;

                                if (isHyphenable) {
                                    // Búsqueda descendente: máximo de caracteres que caben con guión al final
                                    for (let chars = word.length - 2; chars >= 2; chars--) {
                                        textNode1.data = prevText + word.substring(0, chars) + '\u2011'; // guión no-breaking
                                        if (getEffectiveHeight() + footnotesHeight <= maxTotalHeight) {
                                            splitAt = chars;
                                            break;
                                        }
                                    }
                                }

                                const cleanRemainder = word.substring(splitAt).replace(/[.,;:¡!¿?'"”"»]/g, '');
                                if (splitAt >= 2 && cleanRemainder.length >= 2) {
                                    // Aplicar la división: primera parte con guión en pág actual, resto en la siguiente
                                    textNode1.data = prevText + word.substring(0, splitAt) + '\u2011';
                                    remainderText += word.substring(splitAt);
                                } else {
                                    // No se puede dividir: mover la palabra completa a la siguiente página
                                    textNode1.data = prevText;
                                    remainderText += word;
                                }
                            }
                        }
                    }
                    if (remainderText) target2.appendChild(document.createTextNode(remainderText));
                } else if (child.nodeType === Node.ELEMENT_NODE) {
                    target1.appendChild(child);
                    if (getEffectiveHeight() + footnotesHeight > maxTotalHeight) {
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
            
            // --- FIX FOR HANGING HYPHENS AND INDENTATION ON SPLIT PARAGRAPHS ---
            pNode.classList.add('split-paragraph-start');
            secondHalfNode.classList.add('split-paragraph-continuation');
            
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

            let bottomMargin = 0;
            const lastChild = currentInnerContainer.lastElementChild;
            if (lastChild) {
                const style = window.getComputedStyle(lastChild);
                bottomMargin = parseFloat(style.marginBottom) || 0;
            }
            const effectiveHeight = currentInnerContainer.offsetHeight - bottomMargin;

            if (effectiveHeight + footnotesHeight > currentMaxContentHeight) {
                // Intento dividir el nodo
                let remainderNode = null;
                if (clonedNode.tagName === 'P') {
                    // splitParagraphAcrossPages modifica clonedNode inplace (dejando solo lo que cabe)
                    remainderNode = splitParagraphAcrossPages(clonedNode, currentInnerContainer, footnotesHeight, currentMaxContentHeight);
                }

                if (!remainderNode) {
                    // No se pudo dividir (o no es un P). Quitamos el nodo entero y lo pasamos a la siguiente página
                    currentInnerContainer.removeChild(clonedNode);
                    // Re-encolamos el nodo entero al principio
                    childNodes.unshift(node);

                    // --- NUEVA LÓGICA: Prevención de viudas de encabezado (H1, H2, H3) ---
                    let lastChild = currentInnerContainer.lastElementChild;
                    while (lastChild && /^H[123]$/.test(lastChild.tagName)) {
                        const orphanedHeading = currentInnerContainer.removeChild(lastChild);
                        childNodes.unshift(orphanedHeading);
                        lastChild = currentInnerContainer.lastElementChild;
                    }
                } else {
                    // Se dividió. remainderNode tiene la segunda mitad.
                    childNodes.unshift(remainderNode);

                    // --- NUEVA LÓGICA: Prevención de viuda de encabezado + 1 sola línea ---
                    const prevElement = clonedNode.previousElementSibling;
                    if (prevElement && /^H[123]$/.test(prevElement.tagName)) {
                        // Si la porción que quedó es menor a ~45px (aprox 1 o 1.5 líneas), pasamos todo a la sgte pág
                        if (clonedNode.offsetHeight < 45) {
                            currentInnerContainer.removeChild(clonedNode);
                            const orphanedHeading = currentInnerContainer.removeChild(prevElement);
                            
                            childNodes.shift(); // Quitar el remainderNode
                            childNodes.unshift(node); // Encolar P original completo
                            childNodes.unshift(orphanedHeading); // Encolar encabezado
                        }
                    }
                }

                // Reconstruir footnotes para la página actual basados en los nodos que realmente quedaron
                activePageFootnotes = [];
                currentInnerContainer.querySelectorAll('.pdf-footnote-ref').forEach(ref => {
                    const fnId  = ref.getAttribute('data-footnote-id');
                    const fnNum = ref.getAttribute('data-footnote-number');
                    if (fnId && footnoteDefs[fnId] && !activePageFootnotes.some(fn => fn.id === fnId)) {
                        activePageFootnotes.push({ id: fnId, number: fnNum, text: footnoteDefs[fnId] });
                    }
                });
                renderPageFootnotes(currentFootnotesContainer, activePageFootnotes);

                // Crear nueva página
                currentPageNumber++;
                isFirstPageOfChapter = false;
                currentPageEl = createNewPageElement(currentPageNumber, chapter, isFirstPageOfChapter, false);
                currentPageEl.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(currentPageEl);
                currentInnerContainer = currentPageEl.querySelector('.pdf-content-inner');
                currentFootnotesContainer = currentPageEl.querySelector('.pdf-footnotes');
                activePageFootnotes = [];
                currentMaxContentHeight = measureActualMaxHeight(currentPageEl);
            }
        }

        currentPageNumber++;
    }

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
