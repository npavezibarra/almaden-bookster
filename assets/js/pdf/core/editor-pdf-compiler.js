// ============================================================
// MÓDULO: editor-pdf-compiler.js
// Responsabilidad: Motor de renderizado y paginación de PDF usando Paged.js
// ============================================================

window._pdfCompileCounter = 0;

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
        
        // Mostrar cargador mientras Paged.js maqueta
        scroller.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full w-full py-20 text-slate-500 dark:text-slate-400 gap-4">
                <i class="fa-solid fa-spinner fa-spin text-4xl text-slate-400"></i>
                <span class="text-lg">Maquetando libro con Paged.js...</span>
            </div>
        `;
        
        // Construir HTML continuo usando el constructor modular
        const buildResult = window.buildContinuousBookHTML(
            isSingleChapterMode,
            bookState,
            settings,
            window.bookChapterPages
        );
        const fullBookHTML = buildResult.fullBookHTML;
        const previewFirstPhysicalPageNumber = buildResult.previewFirstPhysicalPageNumber;
        const startPageNum = buildResult.startPageNum;

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
