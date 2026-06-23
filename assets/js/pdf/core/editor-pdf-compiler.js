// ============================================================
// MÓDULO: editor-pdf-compiler.js
// Responsabilidad: Motor de renderizado y paginación de PDF usando Paged.js
// ============================================================

window._pdfCompileCounter = 0;

async function _compilePDFPreviewInternal(scrollToActive = false, targetScrollerId = 'pdf-scroller', forceFull = false) {
    console.log("[BOOKSTER-DEBUG] _compilePDFPreviewInternal started", { scrollToActive, targetScrollerId, forceFull, currentVersion: window._pdfCompileCounter + 1 });
    let scroller = null;
    try {
        if (window.isPrintingPDF && !forceFull) {
            console.log("[BOOKSTER-DEBUG] _compilePDFPreviewInternal aborted: window.isPrintingPDF is true");
            return;
        }
        
        const currentVersion = ++window._pdfCompileCounter;
        scroller = document.getElementById(targetScrollerId);
        if (!scroller) {
            console.warn("[BOOKSTER-DEBUG] _compilePDFPreviewInternal aborted: scroller element not found", targetScrollerId);
            return;
        }

        // Evitar error de offsetParent nulo si el visor principal está oculto (por ejemplo, en modo 'edit' / Solo Editor)
        if (targetScrollerId === 'pdf-scroller' && scroller.offsetParent === null && !window.isPrintingPDF) {
            console.log("[BOOKSTER-DEBUG] _compilePDFPreviewInternal aborted: scroller is hidden (display: none)");
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
            console.log("[BOOKSTER-DEBUG] compilePDFPreview: bookState.chapters is empty");
            if (targetScrollerId === 'pdf-scroller') {
                scroller.innerHTML = '<div class="text-center text-slate-400 py-10">Crea o selecciona un capítulo para comenzar.</div>';
                const indicator = document.getElementById('pdf-page-indicator');
                if (indicator) indicator.textContent = '0 Páginas';
            }
            return;
        }
 
        const settings = bookState.settings || {};
        console.log("[BOOKSTER-DEBUG] compilePDFPreview: current bookState.settings", settings);
        
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
                
                // Si la página de inicio es impar y mayor a 1, comenzamos con una página par (en blanco) para ver la paridad en doble página
                if (startPageNum > 1 && startPageNum % 2 !== 0) {
                    prependBlankPage = true;
                }
                
                pageCounterReset = prependBlankPage ? (startPageNum - 2) : (startPageNum - 1);
                
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

        // Log style tag contents
        const styleEl = document.getElementById('dynamic-pdf-settings');
        console.log("[BOOKSTER-DEBUG] Current dynamic-pdf-settings style element content:", styleEl ? styleEl.innerHTML : "NOT FOUND IN DOM!");

        // Pequeño retardo para dar tiempo a pintar el spinner
        await new Promise(resolve => setTimeout(resolve, 50));
        
        if (window._pdfCompileCounter !== currentVersion) {
            console.log("[BOOKSTER-DEBUG] compilePDFPreview aborted: new version has been scheduled");
            return;
        }

        // Esperar a que la hoja de estilos de Google Fonts se cargue y procese completamente
        const googleFontsLink = document.getElementById('google-fonts-stylesheet');
        if (googleFontsLink && !googleFontsLink.sheet) {
            console.log("[BOOKSTER-DEBUG] Waiting for Google Fonts stylesheet link to load...");
            await new Promise((resolve) => {
                googleFontsLink.onload = resolve;
                googleFontsLink.onerror = resolve;
            });
            console.log("[BOOKSTER-DEBUG] Google Fonts stylesheet link loaded!");
        }

        if (window._pdfCompileCounter !== currentVersion) {
            console.log("[BOOKSTER-DEBUG] compilePDFPreview aborted: new version has been scheduled");
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
                console.log("[BOOKSTER-DEBUG] Pre-rendering content for font discovery. Waiting for document.fonts.ready...");
                await document.fonts.ready;
                console.log("[BOOKSTER-DEBUG] document.fonts.ready resolved!");
                // Retardo extra para asegurar la actualización de las métricas en el motor de Chrome
                await new Promise(resolve => setTimeout(resolve, 150));
            } catch (fontErr) {
                console.warn("[BOOKSTER-DEBUG] Non-blocking error loading fonts via document.fonts.ready:", fontErr);
            }
        }
        
        // Limpiar el contenedor temporal
        fontPreloadContainer.remove();

        if (window._pdfCompileCounter !== currentVersion) {
            console.log("[BOOKSTER-DEBUG] compilePDFPreview aborted after fonts load: new version has been scheduled");
            return;
        }

        // Limpiar el scroller
        scroller.innerHTML = '';

        if (!window.Paged) {
            console.error("[BOOKSTER-DEBUG] Paged.js polyfill is not loaded!");
            scroller.innerHTML = '<div class="text-red-500 text-center py-10">Error: Paged.js no está cargado en el editor.</div>';
            return;
        }

        console.log("[BOOKSTER-DEBUG] Instantiating Paged.Previewer and starting preview rendering...");
        const previewer = new window.Paged.Previewer();
        
        // Pasar la hoja de estilos dinámica directamente a Paged.js para que compile las reglas @page y break-before
        const customStyles = styleEl ? { "dynamic-pdf-settings": styleEl.innerHTML } : null;
        const stylesArray = customStyles ? [customStyles] : [];
        
        // Paged.js procesa el HTML y lo inyecta formateado en páginas en el contenedor scroller
        await previewer.preview(fullBookHTML, stylesArray, scroller);

        if (window._pdfCompileCounter !== currentVersion) {
            console.log("[BOOKSTER-DEBUG] compilePDFPreview aborted after previewer.preview: new version has been scheduled");
            return;
        }

        // ============================================================
        // LAYOUT DEBUGGER FOR SPLIT ELEMENTS
        // ============================================================
        try {
            console.group("[BOOKSTER-LAYOUT-DEBUGGER]");
            const splitElements = scroller.querySelectorAll('p[data-split-to], li[data-split-to], h1[data-split-to], h2[data-split-to], h3[data-split-to]');
            console.log(`Found ${splitElements.length} split elements in the document.`);
            
            splitElements.forEach((el, index) => {
                const rect = el.getBoundingClientRect();
                const pageContent = el.closest('.pagedjs_page_content');
                const pageContentRect = pageContent ? pageContent.getBoundingClientRect() : null;
                const computedStyle = window.getComputedStyle(el);
                
                console.log(`%c[Element ${index + 1}] tag: ${el.tagName}, class: "${el.className}", id: "${el.id}"`, 'font-weight: bold; color: #1e3a8a;');
                console.log('Text content:', el.textContent);
                console.log('Computed styles:', {
                    display: computedStyle.display,
                    fontFamily: computedStyle.fontFamily,
                    fontSize: computedStyle.fontSize,
                    lineHeight: computedStyle.lineHeight,
                    textAlign: computedStyle.textAlign,
                    textAlignLast: computedStyle.textAlignLast,
                    height: computedStyle.height,
                    marginTop: computedStyle.marginTop,
                    marginBottom: computedStyle.marginBottom
                });
                
                if (pageContentRect) {
                    console.log('Page Content Area bottom:', pageContentRect.bottom, 'height:', pageContentRect.height);
                    console.log('Element bottom:', rect.bottom, 'height:', rect.height, 'top:', rect.top);
                    
                    const excess = rect.bottom - pageContentRect.bottom;
                    if (excess > 0.1) {
                        console.warn(`%cElement overflows page content area by ${excess.toFixed(2)}px!`, 'color: red; font-weight: bold;');
                    } else {
                        console.log(`%cElement fits within page content area (remaining space: ${Math.abs(excess).toFixed(2)}px)`, 'color: green;');
                    }
                    
                    // Medir coordenadas de cada palabra individualmente recorriendo los nodos de texto
                    let textNode = null;
                    const walk = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null, false);
                    while (textNode = walk.nextNode()) {
                        const text = textNode.nodeValue;
                        const wordRegex = /\S+/g;
                        let match;
                        while ((match = wordRegex.exec(text)) !== null) {
                            const word = match[0];
                            const startOffset = match.index;
                            const endOffset = startOffset + word.length;
                            
                            const range = document.createRange();
                            range.setStart(textNode, startOffset);
                            range.setEnd(textNode, endOffset);
                            const wordRect = range.getBoundingClientRect();
                            const isClipped = wordRect.bottom > pageContentRect.bottom + 0.5;
                            console.log(`  Word: "${word}" | bottom: ${wordRect.bottom.toFixed(2)}px | pageContentBottom: ${pageContentRect.bottom.toFixed(2)}px | ${isClipped ? '%cCLIPPED (Invisible)' : 'VISIBLE'}`, isClipped ? 'color: red; font-weight: bold;' : '');
                        }
                    }
                } else {
                    console.error('No parent .pagedjs_page_content found for this element!');
                }
            });
            console.groupEnd();
        } catch (dbErr) {
            console.error('[BOOKSTER-LAYOUT-DEBUGGER] Error while running debugger:', dbErr);
        }

        // Obtener cantidad de páginas y registrar estadísticas
        const totalPages = scroller.querySelectorAll('.pagedjs_page').length;
        console.log("[BOOKSTER-DEBUG] Paged.js completed. Total pages rendered:", totalPages);
        
        window.bookChapterPages = window.bookChapterPages || {};
        window.bookChapterLengths = window.bookChapterLengths || {};

        if (!isSingleChapterMode) {
            window.bookChapterPages = {};
            window.bookChapterLengths = {};
            
            bookState.chapters.forEach((ch, idx) => {
                const el = scroller.querySelector(`.chapter-section-${ch.id} .chapter-main-title, .chapter-section-${ch.id} .toc-main-title, .chapter-section-${ch.id} .credits-page-content`) 
                           || scroller.querySelector(`.chapter-section-${ch.id}`);
                if (el) {
                    const pageEl = el.closest('.pagedjs_page');
                    if (pageEl) {
                        const pageNum = parseInt(pageEl.getAttribute('data-page-number')) || 1;
                        window.bookChapterPages[ch.id] = pageNum;
                    }
                }
            });

            // Calcular extensión en páginas de cada capítulo
            bookState.chapters.forEach((ch, idx) => {
                const start = window.bookChapterPages[ch.id] || 1;
                const nextStart = idx + 1 < bookState.chapters.length ? (window.bookChapterPages[bookState.chapters[idx+1].id] || totalPages + 1) : totalPages + 1;
                window.bookChapterLengths[ch.id] = nextStart - start;
            });

            // Actualizar los números de página en el Índice (TOC)
            window.updateTOCPagesInCache(scroller, bookState);

            // Actualizar contadores del panel lateral
            const totalPagesSidebarEl = document.getElementById('total-pages-sidebar');
            if (totalPagesSidebarEl) {
                totalPagesSidebarEl.textContent = totalPages;
            }
        }
        
        if (typeof renderSidebar === 'function' && targetScrollerId === 'pdf-scroller') {
            renderSidebar();
        }

        const indicator = document.getElementById('pdf-page-indicator');
        if (indicator) {
            if (isSingleChapterMode) {
                const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
                if (activeChapter) {
                    const start = window.bookChapterPages[activeChapter.id] || 1;
                    const actualRenderedPages = totalPages - (prependBlankPage ? 1 : 0);
                    const end = start + Math.max(1, actualRenderedPages) - 1;
                    const totalBookPages = parseInt(document.getElementById('total-pages-sidebar')?.textContent) || totalPages;
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
        console.error("[BOOKSTER-DEBUG] PDF Compiler Error caught:", e, e.stack);
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
        console.log("[BOOKSTER-DEBUG] compilePDFPreview queued: compilation in progress");
        return;
    }

    window._isPdfCompiling = true;
    try {
        await _compilePDFPreviewInternal(scrollToActive, targetScrollerId, forceFull);
    } finally {
        window._isPdfCompiling = false;
        if (window._pdfCompilePending) {
            const next = window._pdfCompilePending;
            window._pdfCompilePending = null;
            console.log("[BOOKSTER-DEBUG] compilePDFPreview processing queued run");
            setTimeout(() => {
                compilePDFPreview(next.scrollToActive, next.targetScrollerId, next.forceFull);
            }, 0);
        }
    }
}
