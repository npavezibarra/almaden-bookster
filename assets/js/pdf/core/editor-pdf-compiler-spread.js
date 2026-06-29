// ============================================================
// MÓDULO: editor-pdf-compiler-spread.js
// Responsabilidad: Renderizado/post-procesado de pliegos y visualización de pie de página numérico.
// ============================================================

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
