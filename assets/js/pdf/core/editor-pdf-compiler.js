// ============================================================
// MÓDULO: editor-pdf-compiler.js
// Responsabilidad: Motor de renderizado y paginación de PDF usando Paged.js
// ============================================================

window._pdfCompileCounter = 0;

window.applySpreadPageLayout = function(scroller) {
    const targetScroller = typeof scroller === 'string'
        ? document.getElementById(scroller)
        : scroller;

    if (!targetScroller) {
        return;
    }

    const pages = Array.from(targetScroller.querySelectorAll('.pagedjs_pages > .pagedjs_page'));
    const isSpreadView = targetScroller.classList.contains('spread-view');

    const visiblePages = pages.filter(page => !page.querySelector('.book-start-dummy-page'));
    const firstVisiblePage = visiblePages[0] || null;
    const firstVisibleIsLeft = !!(firstVisiblePage && firstVisiblePage.classList.contains('pagedjs_left_page'));

    pages.forEach((page) => {
        if (!page) return;

        const isDummyPage = !!page.querySelector('.book-start-dummy-page');

        if (!isSpreadView) {
            page.style.removeProperty('display');
            page.style.removeProperty('grid-row');
            page.style.removeProperty('grid-column');
            page.style.removeProperty('justify-self');
            page.style.removeProperty('order');
            if (isDummyPage) {
                page.style.removeProperty('display');
            }
            return;
        }

        if (isDummyPage) {
            page.style.setProperty('display', 'none', 'important');
            page.style.removeProperty('grid-row');
            page.style.removeProperty('grid-column');
            page.style.removeProperty('justify-self');
            page.style.removeProperty('order');
            return;
        }

        const visibleIndex = visiblePages.indexOf(page) + 1;
        const isLeftPage = page.classList.contains('pagedjs_left_page');
        const column = isLeftPage ? 1 : 2;
        const row = firstVisibleIsLeft
            ? Math.floor((visibleIndex + 1) / 2)
            : (visibleIndex === 1 ? 1 : Math.floor((visibleIndex + 2) / 2));

        page.style.removeProperty('display');
        page.style.setProperty('grid-row', String(row), 'important');
        page.style.setProperty('grid-column', String(column), 'important');
        page.style.setProperty('justify-self', column === 1 ? 'end' : 'start', 'important');
        page.style.setProperty('order', String(visibleIndex), 'important');
        page.setAttribute('data-page-number', String(visibleIndex));
        page.dataset.pageNumber = String(visibleIndex);
    });
};

window.applyActiveNumericPageFooters = function(scroller, firstPhysicalPageNumber, chapterFirstPhysicalPageNumber) {
    if (!scroller || !Number.isFinite(firstPhysicalPageNumber)) {
        return;
    }

    let overrideStyle = document.getElementById('bookster-active-footer-overrides');
    if (!overrideStyle) {
        overrideStyle = document.createElement('style');
        overrideStyle.id = 'bookster-active-footer-overrides';
        overrideStyle.textContent = `
            #pdf-scroller .pagedjs_margin-content.bookster-page-number-override::after {
                content: attr(data-bookster-page-number) !important;
            }
        `;
        document.head.appendChild(overrideStyle);
    }

    const activeChapter = (bookState.chapters || []).find(ch => ch.id === bookState.activeChapterId);
    const settings = bookState.settings || {};
    if (!activeChapter) {
        return;
    }

    const getFooterBoxClass = (boxName) => `.pagedjs_margin-${boxName} .pagedjs_margin-content`;
    const getResolvedFooterBox = (align, isEven) => {
        if (align === 'center') return 'bottom-center';
        if (align === 'left') return 'bottom-left';
        if (align === 'right') return 'bottom-right';
        if (align === 'outer') return isEven ? 'bottom-left' : 'bottom-right';
        if (align === 'inner') return isEven ? 'bottom-right' : 'bottom-left';
        return 'bottom-center';
    };

    const hideCreditsPageNumber = activeChapter.is_credits === '1' && activeChapter.credits_hide_page_number === '1';
    const hideTocPageNumber = activeChapter.is_toc === '1' && activeChapter.toc_hide_page_numbers === '1';
    const hideAllHeadersFooters = activeChapter.hide_all_headers_footers === '1';

    const pages = Array.from(scroller.querySelectorAll('.pagedjs_pages > .pagedjs_page'));
    let physicalPageNumber = firstPhysicalPageNumber;

    pages.forEach((page) => {
        if (page.querySelector('.book-start-dummy-page')) {
            return;
        }

        const footerContents = Array.from(page.querySelectorAll(
            '.pagedjs_margin-bottom-left .pagedjs_margin-content, ' +
            '.pagedjs_margin-bottom-center .pagedjs_margin-content, ' +
            '.pagedjs_margin-bottom-right .pagedjs_margin-content'
        ));

        footerContents.forEach((footerEl) => {
            delete footerEl.dataset.booksterPageNumber;
            footerEl.classList.remove('bookster-page-number-override');
        });

        const isFirstChapterPage = Number.isFinite(chapterFirstPhysicalPageNumber) && physicalPageNumber === chapterFirstPhysicalPageNumber;
        const isEvenPage = physicalPageNumber % 2 === 0;

        let shouldRenderPageNumber = false;
        let targetBox = null;

        if (!hideAllHeadersFooters && !hideCreditsPageNumber && !hideTocPageNumber) {
            if (isFirstChapterPage) {
                const firstFooterType =
                    (activeChapter.first_page_footer_type && activeChapter.first_page_footer_type !== 'global')
                        ? activeChapter.first_page_footer_type
                        : (settings.first_page_footer_type || 'page_number');

                if (firstFooterType === 'page_number') {
                    shouldRenderPageNumber = true;
                    targetBox = 'bottom-center';
                }
            } else {
                const footerType = isEvenPage
                    ? (settings.footer_even_type || 'page_number')
                    : (settings.footer_odd_type || 'page_number');

                if (footerType === 'page_number') {
                    shouldRenderPageNumber = true;
                    targetBox = getResolvedFooterBox(settings.footer_align || 'center', isEvenPage);
                }
            }
        }

        if (shouldRenderPageNumber && targetBox) {
            const targetFooter = page.querySelector(getFooterBoxClass(targetBox));
            if (targetFooter) {
                targetFooter.dataset.booksterPageNumber = String(physicalPageNumber);
                targetFooter.classList.add('bookster-page-number-override');
            }
        }

        physicalPageNumber += 1;
    });
};

window.getBookPageMapSignature = function() {
    const chapters = (bookState.chapters || []).map(ch => ({
        id: ch.id,
        title: ch.title,
        content: ch.content,
        is_toc: ch.is_toc,
        is_credits: ch.is_credits,
        parity_image: ch.parity_image,
        start_parity: ch.start_parity,
        hide_all_headers_footers: ch.hide_all_headers_footers,
        toc_hide_page_numbers: ch.toc_hide_page_numbers,
        credits_hide_page_number: ch.credits_hide_page_number
    }));

    return JSON.stringify({
        settings: bookState.settings || {},
        chapters
    });
};

window.ensureBookPageMap = async function() {
    if (window._isEnsuringBookPageMap) {
        return window._bookPageMapPromise || Promise.resolve(0);
    }

    window._isEnsuringBookPageMap = true;
    const expectedSignature = typeof window.getBookPageMapSignature === 'function'
        ? window.getBookPageMapSignature()
        : '';

    window._bookPageMapPromise = (async () => {
        let dummyScroller = document.getElementById('dummy-pdf-scroller');
        if (!dummyScroller) {
            dummyScroller = document.createElement('div');
            dummyScroller.id = 'dummy-pdf-scroller';
            dummyScroller.style.position = 'absolute';
            dummyScroller.style.visibility = 'hidden';
            dummyScroller.style.pointerEvents = 'none';
            dummyScroller.style.zIndex = '-9999';
            dummyScroller.style.top = '0';
            dummyScroller.style.left = '0';

            const realScroller = document.getElementById('pdf-scroller');
            let w = realScroller ? realScroller.clientWidth : 0;
            if (w <= 0) w = 800;
            dummyScroller.style.width = w + 'px';
            document.body.appendChild(dummyScroller);
        }

        const totalPages = await _compilePDFPreviewInternal(false, 'dummy-pdf-scroller', true);
        dummyScroller.innerHTML = '';
        if (totalPages > 0) {
            window._bookPageMapSignature = expectedSignature;
        }
        return totalPages || 0;
    })().finally(() => {
        window._isEnsuringBookPageMap = false;
        window._bookPageMapPromise = null;
    });

    return window._bookPageMapPromise;
};

async function _compilePDFPreviewInternal(scrollToActive = false, targetScrollerId = 'pdf-scroller', forceFull = false) {
    let scroller = null;
    try {
        if (window.isPrintingPDF && !forceFull) {
            return;
        }
        
        const currentVersion = ++window._pdfCompileCounter;
        scroller = document.getElementById(targetScrollerId);
        if (!scroller) {
            return;
        }

        // Evitar error de offsetParent nulo si el visor principal está oculto (por ejemplo, en modo 'edit' / Solo Editor)
        if (targetScrollerId === 'pdf-scroller' && scroller.offsetParent === null && !window.isPrintingPDF) {
            return;
        }

        const isSingleChapterMode = (targetScrollerId === 'pdf-scroller') && !forceFull && (window.currentPreviewMode === 'active') && bookState.activeChapterId;
        if (targetScrollerId === 'pdf-scroller') {
            if (isSingleChapterMode) {
                scroller.classList.add('single-chapter-mode');
            } else {
                scroller.classList.remove('single-chapter-mode');
            }
        }
 
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
        const creditsBlankBefore = Math.max(0, parseInt(settings.credits_blank_before || 0, 10) || 0);
        const creditsBlankAfter = Math.max(0, parseInt(settings.credits_blank_after || 0, 10) || 0);

        const buildCreditsBlankPage = (chapterId = '') => `
            <section class="credits-blank-page"${chapterId ? ` data-chapter-id="${chapterId}"` : ''}>
                <div style="height: 1px;"></div>
            </section>
        `;
        
        // Mostrar cargador mientras Paged.js maqueta
        scroller.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full w-full py-20 text-slate-500 dark:text-slate-400 gap-4">
                <i class="fa-solid fa-spinner fa-spin text-4xl text-slate-400"></i>
                <span class="text-lg">Maquetando libro con Paged.js...</span>
            </div>
        `;
        
        // Determinar si renderizamos el libro completo o solo el capítulo activo
        let needsDummyPage = false;
        let pageCounterReset = 0;
        let prependBlankPage = false;
        let startPageNum = 1;
        let previewFirstPhysicalPageNumber = 1;

        // Construir HTML continuo
        let fullBookHTML = '';

        if (isSingleChapterMode) {
            const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
            if (activeChapter) {
                const activeIndex = bookState.chapters.indexOf(activeChapter);
                let cachedPageNum = window.bookChapterPages ? window.bookChapterPages[activeChapter.id] : undefined;
                if (cachedPageNum !== undefined && cachedPageNum !== null) {
                    startPageNum = cachedPageNum;
                } else {
                    if (activeChapter.parity_image) {
                        startPageNum = 3;
                    } else if (activeIndex === 0) {
                        startPageNum = 1;
                    } else {
                        const chapterStartParity = (activeChapter.start_parity && activeChapter.start_parity !== 'any') ? activeChapter.start_parity : settings.chapter_start_parity;
                        startPageNum = (chapterStartParity === 'even') ? 2 : 3;
                    }
                }

                previewFirstPhysicalPageNumber = startPageNum;
                
                // Si la página de inicio es impar y mayor a 1, comenzamos con una página par (en blanco) para ver la paridad en doble página
                if (startPageNum > 1 && startPageNum % 2 !== 0) {
                    prependBlankPage = true;
                }

                if (activeChapter.parity_image || prependBlankPage) {
                    previewFirstPhysicalPageNumber = Math.max(1, startPageNum - 1);
                }
                
                pageCounterReset = Math.max(0, previewFirstPhysicalPageNumber - 1);
                
                if (startPageNum > 1) {
                    needsDummyPage = true;
                }
                
                fullBookHTML = `<div class="book-container" lang="${settings.content_language || 'es'}" style="counter-reset: page ${pageCounterReset};">`;
                
                if (needsDummyPage) {
                    fullBookHTML += `
                        <div class="book-start-dummy-page">
                            <div style="height: 1px;"></div>
                        </div>
                    `;
                }
                
                if (activeChapter.parity_image) {
                    fullBookHTML += `
                        <section class="chapter-parity-section-${activeChapter.id} pdf-content">
                            <div class="chapter-parity-blank-page"></div>
                        </section>
                        <section class="chapter-section-${activeChapter.id} pdf-content" id="chapter-section-${activeChapter.id}">
                            <div class="chapter-metadata-title" style="visibility: hidden; height: 0; line-height: 0; margin: 0; padding: 0; overflow: hidden; position: absolute;">${activeChapter.title || 'Sin título'}</div>
                            ${window.buildChapterHTML(activeChapter, activeIndex, settings, bookState)}
                        </section>
                    `;
                } else if (activeChapter.is_credits === '1') {
                    for (let i = 0; i < creditsBlankBefore; i++) {
                        fullBookHTML += buildCreditsBlankPage(activeChapter.id);
                    }

                    const compiledHtml = window.buildChapterHTML(activeChapter, activeIndex, settings, bookState);
                    fullBookHTML += `
                        <section class="chapter-section-${activeChapter.id} pdf-content" id="chapter-section-${activeChapter.id}">
                            <div class="chapter-metadata-title" style="visibility: hidden; height: 0; line-height: 0; margin: 0; padding: 0; overflow: hidden; position: absolute;">${activeChapter.title || 'Sin título'}</div>
                            ${compiledHtml}
                        </section>
                    `;                    

                    for (let i = 0; i < creditsBlankAfter; i++) {
                        fullBookHTML += buildCreditsBlankPage(activeChapter.id);
                    }
                } else {
                    if (prependBlankPage) {
                        // Insertar página en blanco a la izquierda del pliego (global)
                        fullBookHTML += `
                            <section class="chapter-preview-blank-page">
                                <div style="height: 1px;"></div>
                            </section>
                        `;
                    }
                    
                    const compiledHtml = window.buildChapterHTML(activeChapter, activeIndex, settings, bookState);
                    fullBookHTML += `
                        <section class="chapter-section-${activeChapter.id} pdf-content" id="chapter-section-${activeChapter.id}">
                            <div class="chapter-metadata-title" style="visibility: hidden; height: 0; line-height: 0; margin: 0; padding: 0; overflow: hidden; position: absolute;">${activeChapter.title || 'Sin título'}</div>
                            ${compiledHtml}
                        </section>
                    `;
                }
            } else {
                fullBookHTML = `<div class="book-container" lang="${settings.content_language || 'es'}">`;
            }
        } else {
            // Full Book Mode: check if first chapter starts on left page
            const firstCh = bookState.chapters[0];
            if (firstCh) {
                const firstChStartParity = (firstCh.start_parity && firstCh.start_parity !== 'any') ? firstCh.start_parity : settings.chapter_start_parity;
                if (firstCh.parity_image || firstChStartParity === 'even') {
                    needsDummyPage = true;
                }
            }
            
            fullBookHTML = `<div class="book-container" lang="${settings.content_language || 'es'}">`;
            if (needsDummyPage) {
                fullBookHTML += `
                    <div class="book-start-dummy-page">
                        <div style="height: 1px;"></div>
                    </div>
                `;
            }
            for (let index = 0; index < bookState.chapters.length; index++) {
                const chapter = bookState.chapters[index];
                const compiledHtml = window.buildChapterHTML(chapter, index, settings, bookState);
                
                if (chapter.parity_image) {
                    fullBookHTML += `
                        <section class="chapter-parity-section-${chapter.id} pdf-content">
                            <div class="chapter-parity-blank-page"></div>
                        </section>
                        <section class="chapter-section-${chapter.id} pdf-content" id="chapter-section-${chapter.id}">
                            <div class="chapter-metadata-title" style="visibility: hidden; height: 0; line-height: 0; margin: 0; padding: 0; overflow: hidden; position: absolute;">${chapter.title || 'Sin título'}</div>
                            ${compiledHtml}
                        </section>
                    `;
                } else if (chapter.is_credits === '1') {
                    for (let i = 0; i < creditsBlankBefore; i++) {
                        fullBookHTML += buildCreditsBlankPage(chapter.id);
                    }

                    fullBookHTML += `
                        <section class="chapter-section-${chapter.id} pdf-content" id="chapter-section-${chapter.id}">
                            <div class="chapter-metadata-title" style="visibility: hidden; height: 0; line-height: 0; margin: 0; padding: 0; overflow: hidden; position: absolute;">${chapter.title || 'Sin título'}</div>
                            ${compiledHtml}
                        </section>
                    `;

                    for (let i = 0; i < creditsBlankAfter; i++) {
                        fullBookHTML += buildCreditsBlankPage(chapter.id);
                    }
                } else {
                    fullBookHTML += `
                        <section class="chapter-section-${chapter.id} pdf-content" id="chapter-section-${chapter.id}">
                            <div class="chapter-metadata-title" style="visibility: hidden; height: 0; line-height: 0; margin: 0; padding: 0; overflow: hidden; position: absolute;">${chapter.title || 'Sin título'}</div>
                            ${compiledHtml}
                        </section>
                    `;
                }
            }
        }
        fullBookHTML += '</div>';
        const styleEl = document.getElementById('dynamic-pdf-settings');

        // Pequeño retardo para dar tiempo a pintar el spinner
        await new Promise(resolve => setTimeout(resolve, 50));
        
        if (window._pdfCompileCounter !== currentVersion) {
            return;
        }

        // Esperar a que la hoja de estilos de Google Fonts se cargue y procese completamente
        const googleFontsLink = document.getElementById('google-fonts-stylesheet');
        if (googleFontsLink && !googleFontsLink.sheet) {
            await new Promise((resolve) => {
                googleFontsLink.onload = resolve;
                googleFontsLink.onerror = resolve;
            });
        }

        if (window._pdfCompileCounter !== currentVersion) {
            return;
        }

        // Crear contenedor oculto en el DOM para forzar la carga de fuentes reales del libro
        const fontPreloadContainer = document.createElement('div');
        fontPreloadContainer.id = 'pdf-font-preload-trigger';
        fontPreloadContainer.style.cssText = 'position: absolute; top: -9999px; left: -9999px; visibility: hidden; opacity: 0; pointer-events: none; height: 1px; overflow: hidden;';
        fontPreloadContainer.innerHTML = fullBookHTML;
        document.body.appendChild(fontPreloadContainer);

        // Esperar a que las fuentes se carguen completamente para evitar descuadres en Paged.js
        if (document.fonts) {
            try {
                await document.fonts.ready;
                // Retardo extra para asegurar la actualización de las métricas en el motor de Chrome
                await new Promise(resolve => setTimeout(resolve, 150));
            } catch (fontErr) {
            }
        }
        
        // Limpiar el contenedor temporal
        fontPreloadContainer.remove();

        if (window._pdfCompileCounter !== currentVersion) {
            return;
        }

        // Limpiar el scroller
        scroller.innerHTML = '';

        if (!window.Paged) {
            scroller.innerHTML = '<div class="text-red-500 text-center py-10">Error: Paged.js no está cargado en el editor.</div>';
            return;
        }

        const previewer = new window.Paged.Previewer();
        
        // Pasar la hoja de estilos dinámica directamente a Paged.js para que compile las reglas @page y break-before
        const customStyles = styleEl ? { "dynamic-pdf-settings": styleEl.innerHTML } : null;
        const stylesArray = customStyles ? [customStyles] : [];
        
        // Paged.js procesa el HTML y lo inyecta formateado en páginas en el contenedor scroller
        await previewer.preview(fullBookHTML, stylesArray, scroller);

        if (typeof window.applySpreadPageLayout === 'function') {
            window.applySpreadPageLayout(scroller);
        }

        if (window._pdfCompileCounter !== currentVersion) {
            return;
        }

        // Obtener cantidad de páginas y registrar estadísticas
        const renderedPages = Array.from(scroller.querySelectorAll('.pagedjs_pages > .pagedjs_page'));
        const totalPages = renderedPages.length;
        const pageIndexMap = new Map();
        let visiblePageNumber = 0;
        renderedPages.forEach((page) => {
            if (!page.querySelector('.book-start-dummy-page')) {
                visiblePageNumber += 1;
            }
            pageIndexMap.set(page, visiblePageNumber);
        });
        
        window.bookChapterPages = window.bookChapterPages || {};
        window.bookChapterLengths = window.bookChapterLengths || {};
        window.bookChapterPhysicalLengths = window.bookChapterPhysicalLengths || {};

        if (!isSingleChapterMode) {
            window.bookChapterPages = {};
            window.bookChapterLengths = {};
            window.bookChapterPhysicalLengths = {};

            const chapterStartPagesRaw = {};

            const resolveChapterStartPageElement = (chapter) => {
                if (!chapter) return null;

                if (chapter.is_credits === '1') {
                    const creditsLead = scroller.querySelector(`.credits-blank-page[data-chapter-id="${chapter.id}"]`);
                    if (creditsLead) {
                        const creditsPage = creditsLead.closest('.pagedjs_page');
                        if (creditsPage) return creditsPage;
                    }
                }

                const baseSelector = chapter.is_credits === '1'
                    ? `.chapter-section-${chapter.id} .credits-page-content, .chapter-section-${chapter.id}`
                    : `.chapter-section-${chapter.id} .chapter-main-title, .chapter-section-${chapter.id} .toc-main-title, .chapter-section-${chapter.id}`;

                const el = scroller.querySelector(baseSelector);
                return el ? el.closest('.pagedjs_page') : null;
            };
            
            bookState.chapters.forEach((ch) => {
                const pageEl = resolveChapterStartPageElement(ch);
                if (pageEl) {
                    chapterStartPagesRaw[ch.id] = pageIndexMap.get(pageEl) || null;
                } else {
                    chapterStartPagesRaw[ch.id] = null;
                }
            });

            window.bookChapterPages = { ...chapterStartPagesRaw };

            bookState.chapters.forEach((ch, idx) => {
                const rawStart = chapterStartPagesRaw[ch.id];
                const nextChapter = idx + 1 < bookState.chapters.length
                    ? bookState.chapters[idx + 1]
                    : null;
                const rawNextStart = nextChapter ? chapterStartPagesRaw[nextChapter.id] : null;

                const safeStart = rawStart || 1;
                const safeNextStart = rawNextStart || (safeStart + 1);
                const physicalChapterLength = Math.max(1, safeNextStart - safeStart);
                window.bookChapterPhysicalLengths[ch.id] = physicalChapterLength;

                let chapterLength = physicalChapterLength;
                if (ch.is_toc === '1' && idx + 1 < bookState.chapters.length) {
                    // El blanco de paridad que fuerza al siguiente capítulo a comenzar en página impar
                    // no forma parte visual del TOC.
                    chapterLength = Math.max(1, chapterLength - 1);
                }

                const isIntermediateChapter = idx > 0 && idx < (bookState.chapters.length - 1);
                if (isIntermediateChapter && chapterLength % 2 !== 0) {
                    chapterLength += 1;
                }

                window.bookChapterLengths[ch.id] = chapterLength;
            });
            window.bookTotalCalculatedPages = visiblePageNumber;

            // Actualizar los números de página en el Índice (TOC)
            window.updateTOCPagesInCache(scroller, bookState);

            // Actualizar contadores del panel lateral
            const totalPagesSidebarEl = document.getElementById('total-pages-sidebar');
            if (totalPagesSidebarEl) {
                totalPagesSidebarEl.textContent = window.bookTotalCalculatedPages || totalPages;
            }
        } else if (typeof window.updateTOCPagesInCache === 'function') {
            // En modo capítulo activo el índice también debe usar el mapa global real.
            window.updateTOCPagesInCache(scroller, bookState);
        }

        if (isSingleChapterMode && typeof window.applyActiveNumericPageFooters === 'function') {
            const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
            const chapterFirstPhysicalPageNumber = activeChapter
                ? (window.bookChapterPages?.[activeChapter.id] || startPageNum)
                : startPageNum;
            window.applyActiveNumericPageFooters(
                scroller,
                previewFirstPhysicalPageNumber,
                chapterFirstPhysicalPageNumber
            );
        }
        
        if (typeof renderSidebar === 'function' && targetScrollerId === 'pdf-scroller') {
            renderSidebar();
        }

        const indicator = document.getElementById('pdf-page-indicator');
        if (indicator) {
            if (isSingleChapterMode) {
                const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
                if (activeChapter) {
                    const visibleChapterPages = renderedPages.filter(page => !page.querySelector('.book-start-dummy-page')).length;
                    const start = previewFirstPhysicalPageNumber;
                    const end = start + Math.max(visibleChapterPages, 1) - 1;
                    const totalBookPages = window.bookTotalCalculatedPages || parseInt(document.getElementById('total-pages-sidebar')?.textContent) || totalPages;
                    indicator.textContent = `Págs. ${start} - ${end} de ${totalBookPages}`;
                } else {
                    indicator.textContent = `${totalPages} ${totalPages === 1 ? 'Página' : 'Páginas'}`;
                }
            } else {
                indicator.textContent = `${totalPages} ${totalPages === 1 ? 'Página' : 'Páginas'}`;
            }
        }

        if (scrollToActive) {
            setTimeout(() => {
                const activeHeading = scroller.querySelector(`#chapter-section-${bookState.activeChapterId}`);
                const activePage = activeHeading ? activeHeading.closest('.pagedjs_page') : null;
                if (activePage) activePage.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        } else {
            scroller.scrollTop = previousScrollTop;
        }

        if (typeof window.renderRuler === 'function') {
            const rulerWrapper = document.getElementById('pdf-ruler-wrapper');
            if (rulerWrapper && !rulerWrapper.classList.contains('hidden')) {
                setTimeout(window.renderRuler, 150);
            }
        }

        return totalPages;
    } catch (e) {
        if (scroller) {
            scroller.innerHTML = '<div class="text-red-500 text-center py-10">Error en el maquetador de PDF: ' + e.message + '</div>';
        }
    }
}

window._isPdfCompiling = false;
window._pdfCompilePending = null;

async function compilePDFPreview(scrollToActive = false, targetScrollerId = 'pdf-scroller', forceFull = false) {
    if (window._isPdfCompiling) {
        window._pdfCompilePending = { scrollToActive, targetScrollerId, forceFull };
        return;
    }

    window._isPdfCompiling = true;
    try {
        const activeChapterId = bookState && bookState.activeChapterId;
        const pageMapSignature = typeof window.getBookPageMapSignature === 'function'
            ? window.getBookPageMapSignature()
            : '';
        const needsGlobalPageMap =
            targetScrollerId === 'pdf-scroller' &&
            !forceFull &&
            window.currentPreviewMode === 'active' &&
            activeChapterId &&
            typeof window.ensureBookPageMap === 'function' &&
            (!window.bookChapterPages ||
                !window.bookChapterPages[activeChapterId] ||
                window._bookPageMapSignature !== pageMapSignature);

        if (needsGlobalPageMap) {
            window._isPdfCompiling = false;
            await window.ensureBookPageMap();
            window._isPdfCompiling = true;
        }

        await _compilePDFPreviewInternal(scrollToActive, targetScrollerId, forceFull);
    } finally {
        window._isPdfCompiling = false;
        if (window._pdfCompilePending) {
            const next = window._pdfCompilePending;
            window._pdfCompilePending = null;
            setTimeout(() => {
                compilePDFPreview(next.scrollToActive, next.targetScrollerId, next.forceFull);
            }, 0);
        }
    }
}
