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

    const firstPageHeaderType = (chapter && chapter.first_page_header_type && chapter.first_page_header_type !== 'global') ? chapter.first_page_header_type : (settings.first_page_header_type || 'blank');
    const firstPageHeaderCustom = (chapter && chapter.first_page_header_custom) ? chapter.first_page_header_custom : (settings.first_page_header_custom || '');
    const firstPageFooterType = (chapter && chapter.first_page_footer_type && chapter.first_page_footer_type !== 'global') ? chapter.first_page_footer_type : (settings.first_page_footer_type || 'page_number');
    const firstPageFooterCustom = (chapter && chapter.first_page_footer_custom) ? chapter.first_page_footer_custom : (settings.first_page_footer_custom || '');
    const customRunningHeader = (chapter && chapter.custom_running_header) ? chapter.custom_running_header : null;
    const isToc = (chapter && chapter.is_toc == '1');
    const pageOneVertical = (isToc && chapter.toc_page_one_vertical) ? chapter.toc_page_one_vertical : ((chapter && chapter.page_one_vertical) ? chapter.page_one_vertical : (settings.chapter_page_one_vertical || 'top'));
    const disableHyphenation = (chapter && chapter.disable_hyphenation === '1');

    if (isBlankPage) {
        if (parityImageUrl) {
            const mode = (chapter && chapter.parity_image_mode) ? chapter.parity_image_mode : 'content';
            
            if (mode === 'bleed') {
                pageDiv.innerHTML = `
                    <div class="parity-bleed-container" style="position: absolute; z-index: 0;">
                        <img src="${parityImageUrl}" alt="Página de paridad" style="width: 100%; height: 100%; object-fit: cover;" />
                    </div>
                `;
            } else if (mode === 'custom') {
                const imgWidth = chapter.parity_image_width ? `${chapter.parity_image_width}%` : '100%';
                const imgHeight = chapter.parity_image_height ? `${chapter.parity_image_height}%` : 'auto';
                pageDiv.innerHTML = `
                    <div class="pdf-header opacity-0" style="visibility:hidden;">&nbsp;</div>
                    <div class="pdf-content" style="padding: 0; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; position: relative; z-index: 0;">
                        <img src="${parityImageUrl}" alt="Página de paridad" style="width: ${imgWidth}; height: ${imgHeight}; object-fit: contain;" />
                    </div>
                    <div class="pdf-footer opacity-0" style="visibility:hidden;">&nbsp;</div>
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
                    <span class="text-[var(--text-muted)] tracking-[1em] text-lg print:visible" style="opacity: 0.3;">. . .</span>
                </div>
                <div class="pdf-footer opacity-0" style="visibility:hidden;">&nbsp;</div>
            `;
        }
        pageDiv.innerHTML += '<div class="global-trim-line"></div>';
        return pageDiv;
    }

    const isEven = (pageNumber % 2 === 0);

    // Header Content
    let headerHtml = '&nbsp;';
    let headerType = isFirstPageOfChapter ? firstPageHeaderType : (isEven ? (settings.header_even_type || 'book_title') : (settings.header_odd_type || 'chapter_title'));
    if (chapter && chapter.is_toc == '1') {
        headerType = 'blank';
    }
    
    if (headerType !== 'blank') {
        if (headerType === 'book_title') {
            headerHtml = `<span>${bookState.title}</span>`;
        } else if (headerType === 'chapter_title') {
            headerHtml = `<span>${customRunningHeader ? customRunningHeader : (chapterTitle || 'Sin título')}</span>`;
        } else if (headerType === 'page_number') {
            headerHtml = `<span>${pageNumber}</span>`;
        } else if (headerType === 'author') {
            headerHtml = `<span>Autor</span>`; // We might need to pull the author name if we have it, for now generic or skip. Wait, usually they don't use 'author' without setting it. Let's just output "Autor" as placeholder if not defined.
        } else if (headerType === 'custom') {
            const customText = isFirstPageOfChapter ? firstPageHeaderCustom : (isEven ? (settings.header_even_custom || '') : (settings.header_odd_custom || ''));
            headerHtml = `<span>${customText}</span>`;
        }
    }

    // Footer Content
    let footerHtml = '&nbsp;';
    const footerType = isFirstPageOfChapter ? firstPageFooterType : (isEven ? (settings.footer_even_type || 'page_number') : (settings.footer_odd_type || 'page_number'));
    
    if (footerType !== 'blank') {
        if (footerType === 'page_number') {
            footerHtml = `<span>${pageNumber}</span>`;
        } else if (footerType === 'book_title') {
            footerHtml = `<span>${bookState.title}</span>`;
        } else if (footerType === 'chapter_title') {
            footerHtml = `<span>${customRunningHeader ? customRunningHeader : (chapterTitle || 'Sin título')}</span>`;
        } else if (footerType === 'author') {
            footerHtml = `<span>Autor</span>`;
        } else if (footerType === 'custom') {
            const customText = isFirstPageOfChapter ? firstPageFooterCustom : '';
            footerHtml = `<span>${customText}</span>`;
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
        const align = (isToc && chapter.toc_title_align) ? chapter.toc_title_align : (settings.chapter_page_one_align || 'center');
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
        <div class="global-trim-line"></div>
    `;

    return pageDiv;
}

// Paginación interactiva: divide inteligentemente el contenido en hojas virtuales
window._pdfCompileCounter = 0;
async function compilePDFPreview(scrollToActive = false) {
    const currentVersion = ++window._pdfCompileCounter;
    const scroller = document.getElementById('pdf-scroller');
    if (!scroller) return;
    
    const previousScrollTop = scroller.scrollTop;

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
    window.bookChapterPages = {};

    for (let index = 0; index < bookState.chapters.length; index++) {
        const chapter = bookState.chapters[index];
        
        // Optimización: Si estamos en modo "active" (Capítulo Actual), omitimos los demás
        if (window.currentPreviewMode === 'active' && chapter.id !== bookState.activeChapterId) {
            continue;
        }
        // Determinar paridad
        const chapterStartParity = (chapter.start_parity && chapter.start_parity !== 'any') ? chapter.start_parity : settings.chapter_start_parity;
        
        if (index > 0 && chapterStartParity && chapterStartParity !== 'any') {
            const isOdd = (currentPageNumber % 2 === 1);
            if (chapterStartParity === 'odd') {
                if (!isOdd) {
                    // Terminó en impar -> página actual es par (Izquierda).
                    // Insertamos la página de paridad o página en blanco a la izquierda.
                    const blankPage = createNewPageElement(currentPageNumber, chapter, false, true);
                    blankPage.setAttribute('data-chapter-id', chapter.id);
                    scroller.appendChild(blankPage);
                    currentPageNumber++;
                } else {
                    // Terminó en par -> página actual es impar (Derecha).
                    // Como el capítulo DEBE iniciar a la derecha, y su imagen de paridad a la izquierda,
                    // debemos insertar UNA página impar intencionalmente en blanco, y luego UNA página par con la paridad.
                    
                    // 1. Página en blanco pura a la derecha (Odd)
                    const pureBlankPage = createNewPageElement(currentPageNumber, { ...chapter, parity_image: null }, false, true);
                    pureBlankPage.setAttribute('data-chapter-id', chapter.id);
                    scroller.appendChild(pureBlankPage);
                    currentPageNumber++;

                    // 2. Página de paridad a la izquierda (Even)
                    const parityPage = createNewPageElement(currentPageNumber, chapter, false, true);
                    parityPage.setAttribute('data-chapter-id', chapter.id);
                    scroller.appendChild(parityPage);
                    currentPageNumber++;
                }
            } else if (chapterStartParity === 'even' && isOdd) {
                const blankPage = createNewPageElement(currentPageNumber, chapter, false, true);
                blankPage.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(blankPage);
                currentPageNumber++;
            }
        }

        // Registrar la página de inicio del capítulo
        window.bookChapterPages[chapter.id] = currentPageNumber;

        // Extraer definiciones de notas al pie de este capítulo
        const footnoteDefs = {};
        chapter.content.replace(/(?:^|\n)\[\^([^\]]+)\]:\s*([^\n]+)/g, (match, id, text) => {
            footnoteDefs[id] = text.trim();
            return '';
        });

        let compiledHtml = '';
        if (chapter.is_toc == '1') {
            let tocHtml = '<div class="toc-spacer" style="height: 20px;"></div>';
            let chapterCount = 0;
            const enumerateType = chapter.toc_enumerate || 'none';
            
            function toRoman(num) {
                const roman = {M:1000,CM:900,D:500,CD:400,C:100,XC:90,L:50,XL:40,X:10,IX:9,V:5,IV:4,I:1};
                let str = '';
                for (let i of Object.keys(roman)) {
                    let q = Math.floor(num / roman[i]);
                    num -= q * roman[i];
                    str += i.repeat(q);
                }
                return str;
            }

            bookState.chapters.forEach((c) => {
                if (c.is_toc != '1') {
                    chapterCount++;
                    let prefix = '';
                    if (enumerateType === 'decimal') {
                        prefix = `${chapterCount}. `;
                    } else if (enumerateType === 'roman') {
                        prefix = `${toRoman(chapterCount)}. `;
                    } else if (enumerateType === 'bullet') {
                        prefix = `• `;
                    }
                    
                    tocHtml += `<div class="toc-item" data-target-id="${c.id}">
                        <div class="toc-title-wrapper"><span class="toc-title">${prefix}${c.title || 'Capítulo'}</span></div>
                        <span class="toc-page">000</span>
                    </div>`;
                }
            });
            compiledHtml = tocHtml;
        } else {
            compiledHtml = compileMarkdownToHTML(chapter.content);
        }
        
        // Letra Capitular (Drop Cap)
        if (chapter.drop_cap_enabled === '1') {
            // Reemplazar la primera p para agregar la clase drop-cap
            compiledHtml = compiledHtml.replace(/<p>/, '<p class="drop-cap">');
        }

        if (chapter.title && chapter.title.trim() !== '' && chapter.hide_title !== '1') {
            const titleClass = chapter.is_toc == '1' ? 'toc-main-title' : 'chapter-main-title';
            let titleHtml = `<div class="${titleClass}">${chapter.title.trim()}</div>`;
            
            // Lógica de prefijo de capítulo
            if (settings.chapter_prefix_show == 1 && chapter.is_toc != '1') {
                const chapterNumber = index + 1; // Contador simple por ahora
                let prefixText = (settings.chapter_prefix_template || 'Capítulo {N}').replace('{N}', chapterNumber);
                
                let ornamentHtml = '';
                if (settings.chapter_prefix_ornament === 'line_below') {
                    ornamentHtml = '<div class="chapter-prefix-line"></div>';
                } else if (settings.chapter_prefix_ornament === 'line_above_below') {
                    ornamentHtml = '<div class="chapter-prefix-line"></div>'; // Usaremos CSS para el before/after
                } else if (settings.chapter_prefix_ornament === 'asterisks') {
                    ornamentHtml = '<div class="chapter-prefix-asterisks">***</div>';
                }

                const prefixHtml = `
                    <div class="chapter-prefix-wrapper" data-ornament="${settings.chapter_prefix_ornament}">
                        <div class="chapter-prefix-text">${prefixText}</div>
                        ${ornamentHtml}
                    </div>
                `;

                if (settings.chapter_prefix_position === 'below') {
                    titleHtml = titleHtml + prefixHtml;
                } else {
                    titleHtml = prefixHtml + titleHtml;
                }
            }
            
            compiledHtml = titleHtml + `\n\n` + compiledHtml;
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
                return innerContainer.offsetHeight;
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

                                // Intentar división usando la capacidad nativa del navegador para respetar sílabas
                                let wrappedIndex = -1;
                                const isHyphenable = useHyphenation && word.trim().length >= 4 && !word.includes('-');
                                
                                if (isHyphenable && prevText.trim().length > 0) {
                                    // Restauramos word para que el navegador lo renderice y nos diga dónde lo cortó
                                    textNode1.data = prevText + word;
                                    const range = document.createRange();
                                    
                                    // 1. Ubicar la coordenada "Y" de la línea actual (usando el último caracter de prevText)
                                    let baseTop = 0;
                                    for (let k = prevText.length - 1; k >= 0; k--) {
                                        if (prevText[k].trim().length > 0) {
                                            range.setStart(textNode1, k);
                                            range.setEnd(textNode1, k + 1);
                                            baseTop = range.getBoundingClientRect().top;
                                            break;
                                        }
                                    }
                                    
                                    // 2. Buscar en qué caracter de 'word' ocurre el salto de línea visual
                                    if (baseTop > 0) {
                                        const wordStart = prevText.length;
                                        for (let c = 0; c < word.length; c++) {
                                            if (word[c].trim().length > 0) {
                                                range.setStart(textNode1, wordStart + c);
                                                range.setEnd(textNode1, wordStart + c + 1);
                                                const charRect = range.getBoundingClientRect();
                                                if (charRect.top > baseTop + 5) { // +5 tolerancia
                                                    wrappedIndex = c;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }

                                let splitSuccessfully = false;
                                
                                if (wrappedIndex > 1) { // Asegurar al menos 2 caracteres en la página actual
                                    const part1 = word.substring(0, wrappedIndex);
                                    const part2 = word.substring(wrappedIndex);
                                    
                                    const cleanRemainder = part2.replace(/[.,;:¡!¿?'"”"»]/g, '');
                                    if (cleanRemainder.length >= 2) { // Al menos 2 caracteres en la sgte pág
                                        // Aplicar la división: primera parte con guión normal en pág actual
                                        textNode1.data = prevText + part1 + '-';
                                        
                                        // Verificar que el guión explícito no rompa la altura máxima
                                        if (getEffectiveHeight() + footnotesHeight <= maxTotalHeight) {
                                            remainderText += part2;
                                            splitSuccessfully = true;
                                        }
                                    }
                                }
                                
                                if (!splitSuccessfully) {
                                    // No se pudo dividir (o la regla de sílabas impidió hacerlo bien): mover toda la palabra
                                    // FIX: remover el espacio en blanco colgante al final de la línea
                                    const trailingSpaceMatch = prevText.match(/\s+$/);
                                    if (trailingSpaceMatch) {
                                        textNode1.data = prevText.substring(0, prevText.length - trailingSpaceMatch[0].length);
                                        remainderText += trailingSpaceMatch[0] + word;
                                    } else {
                                        textNode1.data = prevText;
                                        remainderText += word;
                                    }
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

            const effectiveHeight = currentInnerContainer.offsetHeight;

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

    // Segunda pasada: Rellenar la Tabla de Contenidos (Índice) si existe
    const tocItems = scroller.querySelectorAll('.toc-item');
    tocItems.forEach(item => {
        const targetId = item.getAttribute('data-target-id');
        const pageSpan = item.querySelector('.toc-page');
        if (targetId && pageSpan && window.bookChapterPages[targetId]) {
            pageSpan.textContent = window.bookChapterPages[targetId];
        } else if (pageSpan) {
            pageSpan.textContent = '-';
        }
    });

    const totalPages = currentPageNumber - 1;
    if (tempContainer.parentNode) document.body.removeChild(tempContainer);

    const indicator = document.getElementById('pdf-page-indicator');
    if (indicator) indicator.textContent = `${totalPages} ${totalPages === 1 ? 'Página' : 'Páginas'}`;

    if (scrollToActive) {
        // Scroll suave al capítulo activo
        setTimeout(() => {
            const activePage = scroller.querySelector(`.pdf-page[data-chapter-id="${bookState.activeChapterId}"]`);
            if (activePage) activePage.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    } else {
        // Restaurar scroll previo para evitar saltos al auto-guardar
        scroller.scrollTop = previousScrollTop;
    }
}
