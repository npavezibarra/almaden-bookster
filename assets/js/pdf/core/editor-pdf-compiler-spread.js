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
    const forceFirstVisibleLeft = targetScroller.classList.contains('single-chapter-left-start');

    const visiblePages = pages.filter(page => !page.querySelector('.book-start-dummy-page'));
    // The Paged.js side classes are unreliable for generated blank pages.
    // The book's visible sequence is authoritative: odd pages are right,
    // even pages are left. Individual left-start previews are the exception.
    const firstVisibleIsLeft = forceFirstVisibleLeft;

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
        const isLeftPage = forceFirstVisibleLeft
            ? visibleIndex % 2 === 1
            : visibleIndex % 2 === 0;
        const column = isLeftPage ? 1 : 2;
        const row = firstVisibleIsLeft
            ? Math.ceil(visibleIndex / 2)
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

    // Version the style node so a live editor session cannot retain the old
    // pseudo-element-only implementation after a hot reload.
    let overrideStyle = document.getElementById('bookster-active-footer-overrides-v8');
    if (!overrideStyle) {
        overrideStyle = document.createElement('style');
        overrideStyle.id = 'bookster-active-footer-overrides-v8';
        overrideStyle.textContent = `
            #pdf-scroller .pagedjs_margin-content.bookster-header-hidden::after,
            #pdf-scroller .pagedjs_margin-content.bookster-page-number-hidden::after {
                content: "" !important;
            }
            #pdf-scroller .pagedjs_margin-content.bookster-header-override::after,
            #pdf-scroller .pagedjs_margin-content.bookster-page-number-override::after {
                content: "" !important;
            }
            #pdf-scroller .pagedjs_margin-content .bookster-active-margin-text {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                white-space: nowrap !important;
                overflow: visible !important;
                font: inherit !important;
                letter-spacing: inherit !important;
                line-height: inherit !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: static !important;
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
    const getHeaderBoxClass = (boxName) => `.pagedjs_margin-${boxName} .pagedjs_margin-content`;
    const getResolvedHeaderBox = (align, isEven) => {
        if (align === 'center') return 'top-center';
        if (align === 'left') return 'top-left';
        if (align === 'right') return 'top-right';
        if (align === 'outer') return isEven ? 'top-left' : 'top-right';
        if (align === 'inner') return isEven ? 'top-right' : 'top-left';
        return 'top-center';
    };
    const getResolvedFooterBox = (align, isEven) => {
        if (align === 'center') return 'bottom-center';
        if (align === 'left') return 'bottom-left';
        if (align === 'right') return 'bottom-right';
        if (align === 'outer') return isEven ? 'bottom-left' : 'bottom-right';
        if (align === 'inner') return isEven ? 'bottom-right' : 'bottom-left';
        return 'bottom-center';
    };
    const setMarginContent = (element, text, className) => {
        if (!element) return;
        element.classList.remove('bookster-header-hidden', 'bookster-header-override', 'bookster-page-number-hidden', 'bookster-page-number-override');
        element.classList.add(className);
        element.dataset.booksterContent = text || '';
        element.textContent = '';
        if (text) {
            const textNode = document.createElement('span');
            textNode.className = 'bookster-active-margin-text';
            textNode.textContent = text;
            element.appendChild(textNode);
        }
    };
    const setMarginTextAlignment = (element, align, isEven) => {
        if (!element) return;
        let textAlign = 'center';
        if (align === 'left') textAlign = 'left';
        else if (align === 'right') textAlign = 'right';
        else if (align === 'outer') textAlign = isEven ? 'left' : 'right';
        else if (align === 'inner') textAlign = isEven ? 'right' : 'left';
        element.style.setProperty('text-align', textAlign, 'important');
    };
    const setHeaderExteriorPlacement = (element, align, isEven) => {
        if (!element || !['outer', 'inner'].includes(align)) return;
        const placeLeft = align === 'outer' ? isEven : !isEven;
        element.style.setProperty('position', 'absolute', 'important');
        element.style.setProperty('top', 'calc(var(--pagedjs-margin-top) / 2)', 'important');
        element.style.setProperty('transform', 'translateY(-50%)', 'important');
        element.style.setProperty('width', 'max-content', 'important');
        element.style.setProperty('max-width', 'none', 'important');
        element.style.setProperty(
            'left',
            placeLeft ? 'var(--pagedjs-margin-left)' : 'auto',
            'important'
        );
        element.style.setProperty(
            'right',
            placeLeft ? 'auto' : 'var(--pagedjs-margin-right)',
            'important'
        );
    };

    const hideCreditsPageNumber = activeChapter.is_credits === '1' && activeChapter.credits_hide_page_number === '1';
    const hideTocPageNumber = activeChapter.is_toc === '1' && activeChapter.toc_hide_page_numbers === '1';
    const hideAllHeadersFooters = activeChapter.hide_all_headers_footers === '1';

    const pages = Array.from(scroller.querySelectorAll('.pagedjs_pages > .pagedjs_page'));
    let physicalPageNumber = firstPhysicalPageNumber;
    const forceFirstVisibleLeft = scroller.classList.contains('single-chapter-left-start');
    let visiblePageIndex = 0;

    pages.forEach((page) => {
        if (page.querySelector('.book-start-dummy-page')) {
            return;
        }

        visiblePageIndex += 1;

        const footerContents = Array.from(page.querySelectorAll(
            '.pagedjs_margin-bottom-left .pagedjs_margin-content, ' +
            '.pagedjs_margin-bottom-center .pagedjs_margin-content, ' +
            '.pagedjs_margin-bottom-right .pagedjs_margin-content'
        ));

        footerContents.forEach((footerEl) => {
            delete footerEl.dataset.booksterPageNumber;
            setMarginContent(footerEl, '', 'bookster-page-number-hidden');
            footerEl.textContent = '';
        });

        const headerContents = Array.from(page.querySelectorAll(
            '.pagedjs_margin-top-left .pagedjs_margin-content, ' +
            '.pagedjs_margin-top-center .pagedjs_margin-content, ' +
            '.pagedjs_margin-top-right .pagedjs_margin-content'
        ));
        headerContents.forEach((headerEl) => {
            setMarginContent(headerEl, '', 'bookster-header-hidden');
            headerEl.textContent = '';
        });

        // In active left-flow previews the first page is visually moved to
        // the left, so footer alignment must follow the displayed side rather
        // than the original Paged.js page parity.
        const isEvenPage = forceFirstVisibleLeft
            ? visiblePageIndex % 2 === 1
            : physicalPageNumber % 2 === 0;
        const footerType = isEvenPage
            ? (settings.footer_even_type || 'page_number')
            : (settings.footer_odd_type || 'page_number');
        const headerType = isEvenPage
            ? (settings.header_even_type || 'book_title')
            : (settings.header_odd_type || 'chapter_title');

        let shouldRenderPageNumber = false;
        let targetBox = null;

        if (!hideAllHeadersFooters && !hideCreditsPageNumber && !hideTocPageNumber) {
            if (footerType === 'page_number') {
                shouldRenderPageNumber = true;
                targetBox = getResolvedFooterBox(settings.footer_align || 'center', isEvenPage);
            }
        }

        if (shouldRenderPageNumber && targetBox) {
            const targetFooter = page.querySelector(getFooterBoxClass(targetBox));
            if (targetFooter) {
                // Paged.js may leave counter(page) at 1 in an isolated preview.
                // Replace the generated margin content with the book number.
                setMarginTextAlignment(targetFooter, settings.footer_align || 'center', isEvenPage);
                setMarginContent(targetFooter, String(physicalPageNumber), 'bookster-page-number-override');
            }
        }

        if (!hideAllHeadersFooters && headerType !== 'blank') {
            const headerBox = getResolvedHeaderBox(settings.header_align || 'center', isEvenPage);
            const targetHeader = page.querySelector(getHeaderBoxClass(headerBox));
            let headerText = '';
            if (headerType === 'book_title') headerText = bookState.title || 'Libro';
            else if (headerType === 'chapter_title') headerText = activeChapter.title || '';
            else if (headerType === 'page_number') headerText = String(physicalPageNumber);
            else if (headerType === 'author') headerText = 'Autor';
            else if (headerType === 'custom') {
                headerText = isEvenPage
                    ? (settings.header_even_custom || '')
                    : (settings.header_odd_custom || '');
            }
            if (targetHeader) {
                setMarginTextAlignment(targetHeader, settings.header_align || 'center', isEvenPage);
                setHeaderExteriorPlacement(targetHeader, settings.header_align || 'center', isEvenPage);
                setMarginContent(targetHeader, headerText, 'bookster-header-override');
            }
        }

        physicalPageNumber += 1;
    });
};
