// ============================================================
// MÓDULO: editor-pdf-compiler.js
// Responsabilidad: Motor de paginación y renderizado de páginas
// virtuales del libro en el panel de Vista Previa.
// ============================================================

// Nota: Las funciones createNewPageElement y renderPageFootnotes
// se han movido a editor-pdf-dom.js para cumplir con los límites de tamaño.
// Paginación interactiva: divide inteligentemente el contenido en hojas virtuales
window._pdfCompileCounter = 0;
async function compilePDFPreview(scrollToActive = false, targetScrollerId = 'pdf-scroller', forceFull = false) {
    try {
        const currentVersion = ++window._pdfCompileCounter;
        const scroller = document.getElementById(targetScrollerId);
        if (!scroller) return;
    
    const previousScrollTop = scroller.scrollTop || 0;

    if (!bookState.chapters || bookState.chapters.length === 0) {
        if (targetScrollerId === 'pdf-scroller') {
            scroller.innerHTML = '<div class="text-center text-slate-400 py-10">Crea o selecciona un capítulo para comenzar.</div>';
            const indicator = document.getElementById('pdf-page-indicator');
            if (indicator) indicator.textContent = '0 Páginas';
        }
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
    
    if (forceFull || window.currentPreviewMode === 'full') {
        window.bookChapterPages = {};
        window.bookChapterPreParityPages = {};
    }

    for (let index = 0; index < bookState.chapters.length; index++) {
        const chapter = bookState.chapters[index];
        
        // Optimización: Si estamos en modo "active" (Capítulo Actual), omitimos los demás
        if (!forceFull && window.currentPreviewMode === 'active' && chapter.id !== bookState.activeChapterId) {
            continue;
        }
        
        // Si estamos en modo active, recuperamos el número de página real ANTES de aplicar paridad
        if (!forceFull && window.currentPreviewMode === 'active') {
            currentPageNumber = (window.bookChapterPreParityPages && window.bookChapterPreParityPages[chapter.id]) ? window.bookChapterPreParityPages[chapter.id] : 1;
        }

        // Guardamos el número de página antes de la paridad (para cuando estemos en active)
        window.bookChapterPreParityPages = window.bookChapterPreParityPages || {};
        window.bookChapterPreParityPages[chapter.id] = currentPageNumber;

        // Determinar paridad (AHORA SI se ejecuta en modo active)
        const chapterStartParity = (chapter.start_parity && chapter.start_parity !== 'any') ? chapter.start_parity : settings.chapter_start_parity;
        
        if (index > 0 && chapterStartParity && chapterStartParity !== 'any') {
            const isOdd = (currentPageNumber % 2 === 1);
            if (chapterStartParity === 'odd') {
                if (!isOdd) {
                    const blankPage = window.createNewPageElement(currentPageNumber, chapter, false, true);
                    blankPage.setAttribute('data-chapter-id', chapter.id);
                    scroller.appendChild(blankPage);
                    currentPageNumber++;
                } else {
                    const pureBlankPage = window.createNewPageElement(currentPageNumber, { ...chapter, parity_image: null }, false, true);
                    pureBlankPage.setAttribute('data-chapter-id', chapter.id);
                    scroller.appendChild(pureBlankPage);
                    currentPageNumber++;

                    const parityPage = window.createNewPageElement(currentPageNumber, chapter, false, true);
                    parityPage.setAttribute('data-chapter-id', chapter.id);
                    scroller.appendChild(parityPage);
                    currentPageNumber++;
                }
            } else if (chapterStartParity === 'even' && isOdd) {
                const blankPage = window.createNewPageElement(currentPageNumber, chapter, false, true);
                blankPage.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(blankPage);
                currentPageNumber++;
            }
        }

        // Registrar la página de inicio del capítulo
        window.bookChapterPages = window.bookChapterPages || {};
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
            let tocChapterCount = 0;
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
                if (c.is_toc != '1' && c.exclude_from_numbering !== '1') {
                    tocChapterCount++;
                    let prefix = '';
                    if (enumerateType === 'decimal') {
                        prefix = `${tocChapterCount}. `;
                    } else if (enumerateType === 'roman') {
                        prefix = `${toRoman(tocChapterCount)}. `;
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
            const hasSubtitle = chapter.subtitle_text && chapter.subtitle_text.trim() !== '' && chapter.is_toc !== '1';
            let extraTitleStyle = hasSubtitle ? 'padding-bottom: 0 !important;' : '';
            let titleHtml = `<div class="${titleClass}" style="${extraTitleStyle}">${chapter.title.trim()}</div>`;
            
            // Lógica de prefijo de capítulo
            if (settings.chapter_prefix_show == 1 && chapter.is_toc != '1' && chapter.exclude_from_numbering !== '1') {
                
                // Calcular el chapterNumber real (ignorando los excluidos)
                let chapterNumber = 0;
                for (let i = 0; i <= index; i++) {
                    const c = bookState.chapters[i];
                    if (c.is_toc !== '1' && c.exclude_from_numbering !== '1') {
                        chapterNumber++;
                    }
                }
                
                let prefixText = settings.chapter_prefix_template || 'Capítulo {N}';
                prefixText = prefixText.replace('{N}', chapterNumber);
                
                if (prefixText.includes('{R}')) {
                    const toRoman = (num) => {
                        const roman = {M:1000,CM:900,D:500,CD:400,C:100,XC:90,L:50,XL:40,X:10,IX:9,V:5,IV:4,I:1};
                        let str = '';
                        for (let i of Object.keys(roman)) {
                            let q = Math.floor(num / roman[i]);
                            num -= q * roman[i];
                            str += i.repeat(q);
                        }
                        return str;
                    };
                    prefixText = prefixText.replace('{R}', toRoman(chapterNumber));
                }
                
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
            
            // Subtitle Logic
            if (chapter.subtitle_text && chapter.subtitle_text.trim() !== '' && chapter.is_toc !== '1') {
                const subText = chapter.subtitle_text.trim().replace(/\n/g, '<br>');
                const subStyles = [];
                if (chapter.subtitle_font_family) subStyles.push(`font-family: '${chapter.subtitle_font_family}', serif`);
                if (chapter.subtitle_font_size) subStyles.push(`font-size: ${chapter.subtitle_font_size}pt`);
                if (chapter.subtitle_align) subStyles.push(`text-align: ${chapter.subtitle_align}`);
                if (chapter.subtitle_font_style) subStyles.push(`font-style: ${chapter.subtitle_font_style}`);
                if (chapter.subtitle_font_weight) subStyles.push(`font-weight: ${chapter.subtitle_font_weight}`);
                if (chapter.subtitle_text_transform) subStyles.push(`text-transform: ${chapter.subtitle_text_transform}`);
                if (chapter.subtitle_letter_spacing) subStyles.push(`letter-spacing: ${chapter.subtitle_letter_spacing}px`);
                if (chapter.subtitle_margin_top) subStyles.push(`margin-top: ${chapter.subtitle_margin_top}cm`);
                if (chapter.subtitle_margin_bottom) subStyles.push(`margin-bottom: ${chapter.subtitle_margin_bottom}cm`);
                
                const subtitleHtml = `<div class="chapter-subtitle" style="line-height: 1.4; width: 100%; ${subStyles.join('; ')}">${subText}</div>`;
                titleHtml = titleHtml + subtitleHtml;
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
        let currentPageEl = window.createNewPageElement(currentPageNumber, chapter, isFirstPageOfChapter, false);
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

        // Nota: splitParagraphAcrossPages() se invoca ahora desde el scope global
        // y está definido en editor-pdf-pagination.js
        // Iterar usando un while para poder inyectar partes remanentes
        while (childNodes.length > 0) {
            const node = childNodes.shift();
            const clonedNode = node.cloneNode(true);
            currentInnerContainer.appendChild(clonedNode);

            const isBox = clonedNode.nodeType === Node.ELEMENT_NODE && clonedNode.classList && clonedNode.classList.contains('almaden-box');

            let hasRealContentBefore = false;
            for (let i = 0; i < currentInnerContainer.childNodes.length - 1; i++) {
                const child = currentInnerContainer.childNodes[i];
                if (child.nodeType === Node.ELEMENT_NODE) {
                    // Ignorar contenedores de título de capítulo
                    if (child.classList.contains('chapter-title-container') || 
                        child.classList.contains('chapter-main-title') || 
                        child.classList.contains('toc-main-title') || 
                        child.classList.contains('chapter-prefix-wrapper')) {
                        continue;
                    }
                    
                    // Ignorar P/DIV vacíos que solo son saltos de línea y no tienen imágenes
                    // Excepción: las cajas (.almaden-box) siempre se consideran contenido real, incluso si están vacías
                    if ((child.tagName === 'P' || child.tagName === 'DIV') && !child.classList.contains('almaden-box') && child.textContent.trim() === '' && child.querySelectorAll('img, svg, canvas, iframe, video').length === 0) {
                        continue;
                    }
                    if (child.tagName === 'BR') {
                        continue;
                    }
                    hasRealContentBefore = true;
                    break;
                }
                if (child.nodeType === Node.TEXT_NODE && child.textContent.trim() !== '') {
                    hasRealContentBefore = true;
                    break;
                }
            }

            // --- SALTO DE PÁGINA ANTES DE UN BOX ---
            // Si el nodo es un box, debe empezar en una página nueva (solo si la actual ya tiene contenido real)
            if (isBox && hasRealContentBefore) {
                currentInnerContainer.removeChild(clonedNode);
                childNodes.unshift(node); // Devolver a la cola
                
                // Forzar salto de página
                currentPageNumber++;
                isFirstPageOfChapter = false;
                currentPageEl = window.createNewPageElement(currentPageNumber, chapter, isFirstPageOfChapter, false);
                currentPageEl.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(currentPageEl);
                currentInnerContainer = currentPageEl.querySelector('.pdf-content-inner');
                currentFootnotesContainer = currentPageEl.querySelector('.pdf-footnotes');
                activePageFootnotes = [];
                currentMaxContentHeight = measureActualMaxHeight(currentPageEl);
                continue;
            }

            // --- SALTO DE PÁGINA MANUAL ---
            if (clonedNode.nodeType === Node.ELEMENT_NODE && clonedNode.classList && clonedNode.classList.contains('pdf-page-break')) {
                currentInnerContainer.removeChild(clonedNode); // No imprimir el nodo invisible
                
                // Forzar salto de página
                currentPageNumber++;
                isFirstPageOfChapter = false;
                currentPageEl = window.createNewPageElement(currentPageNumber, chapter, isFirstPageOfChapter, false);
                currentPageEl.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(currentPageEl);
                currentInnerContainer = currentPageEl.querySelector('.pdf-content-inner');
                currentFootnotesContainer = currentPageEl.querySelector('.pdf-footnotes');
                activePageFootnotes = [];
                currentMaxContentHeight = measureActualMaxHeight(currentPageEl);
                continue;
            }

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
            if (footnotesAdded) window.renderPageFootnotes(currentFootnotesContainer, activePageFootnotes);

            const footnotesHeight = currentFootnotesContainer && !currentFootnotesContainer.classList.contains('hidden')
                ? currentFootnotesContainer.offsetHeight : 0;

            const effectiveHeight = currentInnerContainer.offsetHeight;

            if (effectiveHeight + footnotesHeight > currentMaxContentHeight) {
                // Intento dividir el nodo
                let remainderNode = null;
                if (['P', 'UL', 'OL', 'DIV', 'BLOCKQUOTE'].includes(clonedNode.tagName)) {
                    // splitParagraphAcrossPages modifica clonedNode inplace (dejando solo lo que cabe)
                    remainderNode = window.splitParagraphAcrossPages(clonedNode, currentInnerContainer, footnotesHeight, currentMaxContentHeight);
                }

                if (!remainderNode) {
                    // Si el elemento es el ÚNICO contenido real en la página y aún así no cabe, debemos forzar su
                    // inserción para evitar un bucle infinito (ej. una imagen gigante o un [box height=100%]).
                    if (!hasRealContentBefore) {
                        // Lo dejamos en la página actual aunque desborde visualmente.
                        // El código creará una nueva página abajo para los siguientes elementos.
                    } else {
                        // No se pudo dividir (o no es un P). Quitamos el nodo entero y lo pasamos a la siguiente página
                        currentInnerContainer.removeChild(clonedNode);
                        // Re-encolamos el nodo entero al principio
                        childNodes.unshift(node);

                        // --- NUEVA LÓGICA: Prevención de viudas de encabezado (H1, H2, H3) ---
                        let lastChild = currentInnerContainer.lastElementChild;
                        // Solo empujar el encabezado a la siguiente página si NO es el primer elemento de la página
                        while (lastChild && /^H[123]$/.test(lastChild.tagName) && currentInnerContainer.childNodes.length > 1) {
                            const orphanedHeading = currentInnerContainer.removeChild(lastChild);
                            childNodes.unshift(orphanedHeading);
                            lastChild = currentInnerContainer.lastElementChild;
                        }
                    }
                } else if (remainderNode === true) {
                    // El párrafo cupo completo gracias a la remoción del margin-bottom durante el test.
                    // No hacemos nada, se queda en esta página.
                } else if (remainderNode && remainderNode.textContent.trim() !== '') {
                    // Se dividió. remainderNode tiene la segunda mitad.
                    childNodes.unshift(remainderNode);

                    // --- NUEVA LÓGICA: Prevención de viuda de encabezado + 1 sola línea ---
                    if (remainderNode && remainderNode !== true) {
                        const prevElement = clonedNode.previousElementSibling;
                        if (prevElement && /^H[123]$/.test(prevElement.tagName)) {
                            // Si la porción que quedó es menor a ~45px y NO estamos ya al principio de la página
                            // (length > 2 significa que hay algo antes del encabezado y el texto actual)
                            if (clonedNode.offsetHeight < 45 && currentInnerContainer.childNodes.length > 2) {
                                currentInnerContainer.removeChild(clonedNode);
                                const orphanedHeading = currentInnerContainer.removeChild(prevElement);
                                
                                childNodes.shift(); // Quitar el remainderNode
                                childNodes.unshift(node); // Encolar P original completo
                                childNodes.unshift(orphanedHeading); // Encolar encabezado
                            }
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
                window.renderPageFootnotes(currentFootnotesContainer, activePageFootnotes);

                // Crear nueva página
                currentPageNumber++;
                isFirstPageOfChapter = false;
                currentPageEl = window.createNewPageElement(currentPageNumber, chapter, isFirstPageOfChapter, false);
                currentPageEl.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(currentPageEl);
                currentInnerContainer = currentPageEl.querySelector('.pdf-content-inner');
                currentFootnotesContainer = currentPageEl.querySelector('.pdf-footnotes');
                activePageFootnotes = [];
                currentMaxContentHeight = measureActualMaxHeight(currentPageEl);
            }

            // --- SALTO DE PÁGINA DESPUÉS DE UN BOX ---
            // Si este elemento era un box y hay más elementos reales por procesar, forzamos que el siguiente elemento
            // caiga en una nueva página inyectando un page-break en la cola.
            if (isBox) {
                // Asegurar que las páginas generadas por [box] sigan los ajustes de cabecera y pie de la 'Primera Página'
                // Solo modificamos la cabecera/pie actual si realmente queremos que este box se comporte como primera página.
                // Sin embargo, las cabeceras y pies ya fueron renderizados correctamente al crear la página.
                // No debemos sobrescribir todas las páginas con la lógica de 'firstPageHeaderType'.
                
                // Simplemente inyectamos el salto de página si hay más contenido.

                let hasRealContentAfter = false;
                for (let i = 0; i < childNodes.length; i++) {
                    const child = childNodes[i];
                    if (child.nodeType === Node.ELEMENT_NODE) {
                        if ((child.tagName === 'P' || child.tagName === 'DIV') && !child.classList.contains('almaden-box') && child.textContent.trim() === '' && child.querySelectorAll('img, svg, canvas, iframe, video').length === 0) {
                            continue;
                        }
                        if (child.tagName === 'BR') {
                            continue;
                        }
                        hasRealContentAfter = true;
                        break;
                    } else if (child.nodeType === Node.TEXT_NODE && child.textContent.trim() !== '') {
                        hasRealContentAfter = true;
                        break;
                    }
                }
                if (hasRealContentAfter) {
                    const manualBreak = document.createElement('div');
                    manualBreak.className = 'pdf-page-break';
                    childNodes.unshift(manualBreak);
                }
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

    // Calcular la cantidad de páginas de cada capítulo
    window.bookChapterLengths = window.bookChapterLengths || {};
    const totalPages = currentPageNumber - 1;

    if (forceFull || window.currentPreviewMode === 'full') {
        bookState.chapters.forEach((ch, idx) => {
            const start = window.bookChapterPages[ch.id];
            const nextStart = idx + 1 < bookState.chapters.length ? window.bookChapterPages[bookState.chapters[idx+1].id] : currentPageNumber;
            window.bookChapterLengths[ch.id] = nextStart - start;
        });
    } else if (window.currentPreviewMode === 'active' && targetScrollerId === 'pdf-scroller') {
        window.bookChapterLengths[bookState.activeChapterId] = currentPageNumber - (window.bookChapterPages[bookState.activeChapterId] || 1);
    }

    let globalTotalPages = 0;
    if (Object.keys(window.bookChapterLengths).length > 0) {
        Object.values(window.bookChapterLengths).forEach(val => {
            globalTotalPages += (parseInt(val) || 0);
        });
    } else {
        globalTotalPages = totalPages;
    }

    const totalPagesSidebarEl = document.getElementById('total-pages-sidebar');
    if (totalPagesSidebarEl) {
        totalPagesSidebarEl.textContent = globalTotalPages;
    }
    
    if (typeof renderSidebar === 'function' && targetScrollerId === 'pdf-scroller') {
        renderSidebar();
    }
    if (tempContainer.parentNode) document.body.removeChild(tempContainer);

    if (targetScrollerId === 'pdf-scroller') {
        const indicator = document.getElementById('pdf-page-indicator');
        let displayedPages = scroller.querySelectorAll('.pdf-page').length;
        if (indicator) indicator.textContent = `${displayedPages} ${displayedPages === 1 ? 'Página' : 'Páginas'}`;
    }

        if (targetScrollerId === 'pdf-scroller') {
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
        
        return totalPages;
    } catch (e) {
        console.error("PDF Compiler Error:", e);
        alert("CRITICAL ERROR in PDF Compiler:\n" + e.message + "\nLine: " + e.lineNumber + "\nStack:\n" + e.stack);
    }
}
