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
    const dims = window.calculatePageDimensions(settings);
    const unit = dims.unit;
    const conversionFactor = dims.conversionFactor;
    const pageWidthPx = dims.pageWidthPx;
    const MAX_PAGE_CONTENT_HEIGHT = dims.maxPageContentHeight;

    // Contenedor temporal oculto para medir elementos sin mostrarlos
    const tempContainer = document.createElement('div');
    tempContainer.style.position = 'absolute';
    tempContainer.style.visibility = 'hidden';
    tempContainer.style.width = `${pageWidthPx - (parseFloat(settings.margin_left_odd ?? settings.margin_left ?? 2) + parseFloat(settings.margin_right_odd ?? settings.margin_right ?? 2) + parseFloat(settings.padding_left ?? 0) + parseFloat(settings.padding_right ?? 0)) * conversionFactor}px`;
    tempContainer.className = 'pdf-content';
    document.body.appendChild(tempContainer);

    const isolatedScroller = document.createElement('div');
    isolatedScroller.style.position = 'absolute';
    isolatedScroller.style.visibility = 'hidden';
    isolatedScroller.style.top = '0';
    isolatedScroller.style.left = '0';
    isolatedScroller.style.width = scroller.clientWidth + 'px';
    isolatedScroller.className = scroller.className;
    document.body.appendChild(isolatedScroller);

    scroller.innerHTML = '';
    let currentPageNumber = 1;
    
    if (forceFull || window.currentPreviewMode === 'full') {
        window.bookChapterPages = {};
        window.bookChapterPreParityPages = {};
        window.pdfPagesCache = {}; // Inicializamos el caché de páginas virtuales (como objeto para indexar por pageNum)
    }

    // Helper para virtualizar páginas y ahorrar memoria
    function virtualizePage(pageEl, pageNum) {
        if (window.currentPreviewMode === 'full' && targetScrollerId === 'pdf-scroller') {
            window.pdfPagesCache[pageNum] = pageEl.innerHTML;
            pageEl.setAttribute('data-virtual-page', pageNum);
            
            if (!window.isPrintingPDF) {
                pageEl.innerHTML = '<div class="virtual-placeholder" style="display:flex; height:100%; align-items:center; justify-content:center; color:#e2e8f0; font-size:14px;"><i class="fa-solid fa-file-lines fa-3x mb-2"></i></div>';
                pageEl.classList.add('is-virtualized');
            }
        }
    }

    for (let index = 0; index < bookState.chapters.length; index++) {
        const chapter = bookState.chapters[index];
        
        // Optimización: Si estamos en modo "active" (Capítulo Actual), omitimos los demás
        if (!forceFull && window.currentPreviewMode === 'active' && chapter.id !== bookState.activeChapterId) {
            continue;
        }

        // --- YIELD AL MAIN THREAD ---
        // Evita que el navegador colapse al procesar el libro completo
        if (window.currentPreviewMode === 'full') {
            await new Promise(r => setTimeout(r, 0)); 
        }
        
        // Si estamos en modo active, recuperamos el número de página real ANTES de aplicar paridad
        if (!forceFull && window.currentPreviewMode === 'active') {
            currentPageNumber = (window.bookChapterPreParityPages && window.bookChapterPreParityPages[chapter.id]) ? window.bookChapterPreParityPages[chapter.id] : 1;
        }

        // Guardamos el número de página antes de la paridad (para cuando estemos en active)
        window.bookChapterPreParityPages = window.bookChapterPreParityPages || {};
        window.bookChapterPreParityPages[chapter.id] = currentPageNumber;

        // Determinar paridad y generar páginas en blanco correspondientes
        currentPageNumber = window.handleChapterParity(chapter, index, settings, currentPageNumber, scroller, virtualizePage);

        // Registrar la página de inicio del capítulo
        window.bookChapterPages = window.bookChapterPages || {};
        window.bookChapterPages[chapter.id] = currentPageNumber;

        // Extraer definiciones de notas al pie de este capítulo
        const footnoteDefs = {};
        chapter.content.replace(/(?:^|\n)\[\^([^\]]+)\]:\s*([^\n]+)/g, (match, id, text) => {
            footnoteDefs[id] = text.trim();
            return '';
        });

        let compiledHtml = window.buildChapterHTML(chapter, index, settings, bookState);
        tempContainer.innerHTML = compiledHtml;

        // Esperar a que las imágenes se carguen para poder medir su altura real
        const isExporting = window.isPrintingPDF || forceFull;
        const images = Array.from(tempContainer.querySelectorAll('img'));
        const imagePromises = images.map(img => {
            if (isExporting) {
                img.removeAttribute('loading'); // FORCE LOAD EVEN IF OFF-SCREEN
                if (img.complete) return Promise.resolve();
                return new Promise(resolve => {
                    img.onload = resolve;
                    img.onerror = resolve; // Si falla, continuamos igual
                });
            } else {
                // Modo Vista Previa (Lazy Loading)
                // Asignar un placeholder de dimensiones prestablecidas para que la paginación no se rompa
                if (!img.getAttribute('height') && !img.style.height) {
                    img.style.minHeight = '150px';
                    img.style.objectFit = 'contain';
                }
                return Promise.resolve();
            }
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
        isolatedScroller.appendChild(currentPageEl);
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
            let wasSplit = false;
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
                    if ((child.tagName === 'P' || child.tagName === 'DIV') && child.textContent.trim() === '' && child.querySelectorAll('img, svg, canvas, iframe, video').length === 0) {
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
                scroller.appendChild(currentPageEl);
                // --- SUGERENCIA 5: Time Slicing ---
                // Ceder el control al navegador para evitar congelamiento de UI
                await new Promise(resolve => setTimeout(resolve, 0));
                if (window._pdfCompileCounter !== currentVersion) {
                    if (tempContainer.parentNode) document.body.removeChild(tempContainer);
                    if (isolatedScroller.parentNode) document.body.removeChild(isolatedScroller);
                    return;
                }
                currentPageNumber++;
                isFirstPageOfChapter = false;
                currentPageEl = window.createNewPageElement(currentPageNumber, chapter, isFirstPageOfChapter, false);
                currentPageEl.setAttribute('data-chapter-id', chapter.id);
                isolatedScroller.appendChild(currentPageEl);
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
                scroller.appendChild(currentPageEl);
                // --- SUGERENCIA 5: Time Slicing ---
                // Ceder el control al navegador para evitar congelamiento de UI
                await new Promise(resolve => setTimeout(resolve, 0));
                if (window._pdfCompileCounter !== currentVersion) {
                    if (tempContainer.parentNode) document.body.removeChild(tempContainer);
                    if (isolatedScroller.parentNode) document.body.removeChild(isolatedScroller);
                    return;
                }
                currentPageNumber++;
                isFirstPageOfChapter = false;
                currentPageEl = window.createNewPageElement(currentPageNumber, chapter, isFirstPageOfChapter, false);
                currentPageEl.setAttribute('data-chapter-id', chapter.id);
                isolatedScroller.appendChild(currentPageEl);
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
                    wasSplit = true;

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

                // --- VIRTUALIZATION ---
                virtualizePage(currentPageEl, currentPageNumber);

                // Crear nueva página
                scroller.appendChild(currentPageEl);
                // --- SUGERENCIA 5: Time Slicing ---
                // Ceder el control al navegador para evitar congelamiento de UI
                await new Promise(resolve => setTimeout(resolve, 0));
                if (window._pdfCompileCounter !== currentVersion) {
                    if (tempContainer.parentNode) document.body.removeChild(tempContainer);
                    if (isolatedScroller.parentNode) document.body.removeChild(isolatedScroller);
                    return;
                }
                currentPageNumber++;
                isFirstPageOfChapter = false;
                currentPageEl = window.createNewPageElement(currentPageNumber, chapter, isFirstPageOfChapter, false);
                currentPageEl.setAttribute('data-chapter-id', chapter.id);
                isolatedScroller.appendChild(currentPageEl);
                currentInnerContainer = currentPageEl.querySelector('.pdf-content-inner');
                currentFootnotesContainer = currentPageEl.querySelector('.pdf-footnotes');
                activePageFootnotes = [];
                currentMaxContentHeight = measureActualMaxHeight(currentPageEl);
            }

            // --- SALTO DE PÁGINA DESPUÉS DE UN BOX ---
            // Si este elemento era un box y hay más elementos reales por procesar, forzamos que el siguiente elemento
            // caiga en una nueva página inyectando un page-break en la cola.
            if (isBox && !wasSplit) {
                // Asegurar que las páginas generadas por [box] sigan los ajustes de cabecera y pie de la 'Primera Página'
                // Solo modificamos la cabecera/pie actual si realmente queremos que este box se comporte como primera página.
                // Sin embargo, las cabeceras y pies ya fueron renderizados correctamente al crear la página.
                // No debemos sobrescribir todas las páginas con la lógica de 'firstPageHeaderType'.
                
                // Simplemente inyectamos el salto de página si hay más contenido.

                let hasRealContentAfter = false;
                for (let i = 0; i < childNodes.length; i++) {
                    const child = childNodes[i];
                    if (child.nodeType === Node.ELEMENT_NODE) {
                        if ((child.tagName === 'P' || child.tagName === 'DIV') && child.textContent.trim() === '' && child.querySelectorAll('img, svg, canvas, iframe, video').length === 0) {
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

        // --- VIRTUALIZATION (Última página del capítulo) ---
        virtualizePage(currentPageEl, currentPageNumber);
        scroller.appendChild(currentPageEl);
        
        // --- SUGERENCIA 5: Time Slicing ---
        // Ceder el control al navegador para evitar congelamiento de UI
        await new Promise(resolve => setTimeout(resolve, 0));
        if (window._pdfCompileCounter !== currentVersion) {
            if (tempContainer.parentNode) document.body.removeChild(tempContainer);
            if (isolatedScroller.parentNode) document.body.removeChild(isolatedScroller);
            return;
        }

        currentPageNumber++;
    }

    // Segunda pasada: Rellenar la Tabla de Contenidos (Índice) si existe
    window.updateTOCPagesInCache(scroller, bookState);

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
    if (isolatedScroller.parentNode) document.body.removeChild(isolatedScroller);

    if (targetScrollerId === 'pdf-scroller') {
        const indicator = document.getElementById('pdf-page-indicator');
        let displayedPages = scroller.querySelectorAll('.pdf-page').length;
        if (indicator) indicator.textContent = `${displayedPages} ${displayedPages === 1 ? 'Página' : 'Páginas'}`;
        
        // --- INICIAR VIRTUALIZACIÓN ---
        if (window.currentPreviewMode === 'full' && typeof window.initPDFVirtualization === 'function') {
            window.initPDFVirtualization(scroller);
        }
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
