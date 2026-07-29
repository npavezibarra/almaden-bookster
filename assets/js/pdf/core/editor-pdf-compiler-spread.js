// ============================================================
// MÓDULO: editor-pdf-compiler-spread.js
// Responsabilidad: Renderizado/post-procesado de pliegos y visualización de pie de página numérico.
// ============================================================

window.applySpreadPageLayout = function(scroller, options = {}) {
    const targetScroller = typeof scroller === 'string'
        ? document.getElementById(scroller)
        : scroller;

    if (!targetScroller) {
        return;
    }

    const physicalPageModel = window.applyPdfPhysicalPageModel
        ? window.applyPdfPhysicalPageModel(targetScroller, options)
        : null;
    const pages = physicalPageModel
        ? physicalPageModel.pages
        : Array.from(targetScroller.querySelectorAll('.pagedjs_pages > .pagedjs_page'));
    const isSpreadView = targetScroller.classList.contains('spread-view');
    const visiblePages = physicalPageModel
        ? physicalPageModel.visibleEntries.map(entry => entry.page)
        : pages.filter(page => !page.querySelector('.book-start-dummy-page'));

    const setImageBleedVars = (page, isLeftPage) => {
        const computedStyles = window.getComputedStyle(page);
        const bleedTop = isLeftPage
            ? computedStyles.getPropertyValue('--pagedjs-bleed-left-top').trim()
            : computedStyles.getPropertyValue('--pagedjs-bleed-right-top').trim();
        const bleedBottom = isLeftPage
            ? computedStyles.getPropertyValue('--pagedjs-bleed-left-bottom').trim()
            : computedStyles.getPropertyValue('--pagedjs-bleed-right-bottom').trim();
        const bleedOuter = isLeftPage
            ? computedStyles.getPropertyValue('--pagedjs-bleed-left-left').trim()
            : computedStyles.getPropertyValue('--pagedjs-bleed-right-right').trim();

        page.style.setProperty('--bookster-image-bleed-top', bleedTop || '0px');
        page.style.setProperty('--bookster-image-bleed-bottom', bleedBottom || '0px');
        page.style.setProperty('--bookster-image-bleed-left', isLeftPage ? (bleedOuter || '0px') : '0px');
        page.style.setProperty('--bookster-image-bleed-right', isLeftPage ? '0px' : (bleedOuter || '0px'));
    };

    pages.forEach((page) => {
        if (!page) return;

        const isDummyPage = !!page.querySelector('.book-start-dummy-page');
        const isTransitionBlankFullPage = !!page.querySelector('.chapter-transition-blank-page--full');
        const isTransitionBlankIntentionalTextPage = !!page.querySelector('.chapter-transition-blank-page--intentional-text');
        const isBookEndBlankFullPage = !!page.querySelector('.book-end-blank-page--full');
        const isBookEndBlankIntentionalTextPage = !!page.querySelector('.book-end-blank-page--intentional-text');
        page.classList.toggle('bookster-transition-blank-full-page', isTransitionBlankFullPage);
        page.classList.toggle('bookster-transition-blank-intentional-text-page', isTransitionBlankIntentionalTextPage);
        page.classList.toggle('bookster-book-end-blank-full-page', isBookEndBlankFullPage);
        page.classList.toggle('bookster-book-end-blank-intentional-text-page', isBookEndBlankIntentionalTextPage);

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
            page.classList.remove(
                'pagedjs_left_page',
                'pagedjs_right_page',
                'bookster-left-page',
                'bookster-right-page'
            );
            return;
        }

        const pageNumber = window.getPdfPhysicalPageNumberForPage
            ? window.getPdfPhysicalPageNumberForPage(page)
            : parseInt(page.getAttribute('data-page-number') || '', 10);
        const safePageNumber = Number.isFinite(pageNumber) && pageNumber > 0
            ? pageNumber
            : (visiblePages.indexOf(page) + 1);
        const isLeftPage = window.getPdfPhysicalPageSide
            ? window.getPdfPhysicalPageSide(safePageNumber) === 'left'
            : (safePageNumber % 2 === 0);
        const column = isLeftPage ? 1 : 2;
        // Page 1 stands alone on the right. Reading spreads begin at 2 | 3,
        // then continue as 4 | 5, 6 | 7, and so on.
        const row = Math.floor(safePageNumber / 2) + 1;

        page.style.removeProperty('display');
        page.style.setProperty('grid-row', String(row), 'important');
        page.style.setProperty('grid-column', String(column), 'important');
        page.style.setProperty('justify-self', column === 1 ? 'end' : 'start', 'important');
        page.style.setProperty('order', String(safePageNumber), 'important');
        setImageBleedVars(page, isLeftPage);
        page.setAttribute('data-page-number', String(safePageNumber));
        page.dataset.pageNumber = String(safePageNumber);
        return;
    });
};

window.applyActiveNumericPageFooters = function(scroller, firstPhysicalPageNumber, chapterFirstPhysicalPageNumber) {
    if (!scroller || !Number.isFinite(firstPhysicalPageNumber)) {
        return;
    }

    // Version the style node so a live editor session cannot retain the old
    // pseudo-element-only implementation after a hot reload.
    let overrideStyle = document.getElementById('bookster-active-footer-overrides-v9');
    if (!overrideStyle) {
        overrideStyle = document.createElement('style');
        overrideStyle.id = 'bookster-active-footer-overrides-v9';
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
    const hideCreditsHeader = activeChapter.is_credits === '1' && activeChapter.credits_hide_header === '1';
    const hideTocHeader = activeChapter.is_toc === '1' && activeChapter.toc_hide_header !== '0';
    const hideTocPageNumber = activeChapter.is_toc === '1' && activeChapter.toc_hide_page_numbers !== '0';
    const hideAllHeadersFooters = activeChapter.hide_all_headers_footers === '1';
    const firstPageHeaderShow = settings.first_page_header_show === undefined ? true : String(settings.first_page_header_show) !== '0';
    const firstPageFooterShow = settings.first_page_footer_show === undefined ? true : String(settings.first_page_footer_show) !== '0';

    const pages = Array.from(scroller.querySelectorAll('.pagedjs_pages > .pagedjs_page'));
    const physicalPageModel = window.buildPdfPhysicalPageModel
        ? window.buildPdfPhysicalPageModel(scroller, { firstPhysicalPageNumber })
        : null;
    const physicalPageByElement = physicalPageModel
        ? new Map(physicalPageModel.entries.map(entry => [entry.page, entry]))
        : null;
    let physicalPageNumber = firstPhysicalPageNumber;
    const singleChapterRule = window.getSingleChapterBookRule
        ? window.getSingleChapterBookRule(bookState, settings)
        : null;
    const leadingPageIndex = pages.findIndex(page => page.querySelector('.book-start-leading-page'));
    const chapterContentPages = window.getPdfChapterContentPages
        ? window.getPdfChapterContentPages(activeChapter, pages)
        : [];
    const firstChapterContentPageIndex = chapterContentPages.length > 0
        ? pages.indexOf(chapterContentPages[0])
        : -1;
    const forceBookStartSequence = Boolean(
        singleChapterRule
        && singleChapterRule.shouldUseBookStartAsPageOne
        && leadingPageIndex >= 0
        && firstChapterContentPageIndex > leadingPageIndex
    );
    const useCanonicalNumbers = Boolean(physicalPageModel && physicalPageModel.visibleEntries.length > 0);
    let visiblePageIndex = 0;

    pages.forEach((page, pageIndex) => {
        if (page.querySelector('.book-start-dummy-page')) {
            return;
        }

        const modelEntry = physicalPageByElement ? physicalPageByElement.get(page) : null;
        const canonicalPageNumber = modelEntry && Number.isFinite(modelEntry.physicalPageNumber)
            ? modelEntry.physicalPageNumber
            : NaN;
        const isBookStartLeadingPage = forceBookStartSequence && pageIndex === leadingPageIndex;
        const forcedPageNumber = useCanonicalNumbers
            ? null
            : (isBookStartLeadingPage
                ? 1
                : (forceBookStartSequence && pageIndex >= firstChapterContentPageIndex
                    ? 2 + (pageIndex - firstChapterContentPageIndex)
                    : null));
        if (!useCanonicalNumbers && forceBookStartSequence && forcedPageNumber === null) {
            return;
        }
        const currentPageNumber = useCanonicalNumbers
            ? canonicalPageNumber
            : (forcedPageNumber ?? physicalPageNumber);

        visiblePageIndex += 1;
        // The rendered chapter marker is the source of truth. A configured
        // physical number can be stale while Paged.js is rebuilding pages.
        const isFirstChapterPage = firstChapterContentPageIndex >= 0
            ? pageIndex === firstChapterContentPageIndex
            : (Number.isFinite(chapterFirstPhysicalPageNumber)
                ? currentPageNumber === chapterFirstPhysicalPageNumber
                : (forceBookStartSequence ? currentPageNumber === 2 : false));
        const isEditorialChapterStart = isFirstChapterPage
            && activeChapter.is_toc !== '1'
            && activeChapter.is_credits !== '1';
        const isTransitionBlankFullPage = !!page.querySelector('.chapter-transition-blank-page--full');
        const isTransitionBlankIntentionalTextPage = !!page.querySelector('.chapter-transition-blank-page--intentional-text');
        const isBookEndBlankFullPage = !!page.querySelector('.book-end-blank-page--full');
        const isBookEndBlankIntentionalTextPage = !!page.querySelector('.book-end-blank-page--intentional-text');
        if (isTransitionBlankFullPage || isTransitionBlankIntentionalTextPage || isBookEndBlankFullPage || isBookEndBlankIntentionalTextPage) {
            if (!useCanonicalNumbers && !forceBookStartSequence) {
                physicalPageNumber += 1;
            }
            return;
        }

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

        const isEvenPage = currentPageNumber % 2 === 0;
        const footerType = isEvenPage
            ? (settings.footer_even_type || 'page_number')
            : (settings.footer_odd_type || 'page_number');
        const headerType = isFirstChapterPage
            ? (settings.first_page_header_type || 'blank')
            : (isEvenPage
                ? (settings.header_even_type || 'book_title')
                : (settings.header_odd_type || 'chapter_title'));
        const firstPageFooterType = settings.first_page_footer_type || 'page_number';
        // The first editorial page is the TOC target. Its folio is mandatory;
        // generic first-page footer settings only apply to non-editorial pages.
        const effectiveFooterType = isEditorialChapterStart
            ? 'page_number'
            : (isFirstChapterPage ? firstPageFooterType : footerType);

        let shouldRenderPageNumber = false;
        let targetBox = null;

        if (isBookStartLeadingPage) {
            shouldRenderPageNumber = true;
            targetBox = getResolvedFooterBox(settings.footer_align || 'center', false);
        } else if (!hideAllHeadersFooters && !hideCreditsPageNumber && !hideTocPageNumber && (isEditorialChapterStart || !isFirstChapterPage || firstPageFooterShow)) {
            if (effectiveFooterType === 'page_number') {
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
                setMarginContent(targetFooter, String(currentPageNumber), 'bookster-page-number-override');
            }
        }

        if (!isBookStartLeadingPage && !hideAllHeadersFooters && !hideTocHeader && !hideCreditsHeader && headerType !== 'blank' && (!isFirstChapterPage || firstPageHeaderShow)) {
            const headerBox = getResolvedHeaderBox(settings.header_align || 'center', isEvenPage);
            const targetHeader = page.querySelector(getHeaderBoxClass(headerBox));
            let headerText = '';
            if (isFirstChapterPage) {
                if (headerType === 'book_title') headerText = bookState.title || 'Libro';
                else if (headerType === 'chapter_title') headerText = activeChapter.title || '';
                else if (headerType === 'page_number') headerText = String(currentPageNumber);
                else if (headerType === 'author') headerText = 'Autor';
                else if (headerType === 'custom') {
                    headerText = settings.first_page_header_custom || '';
                }
            } else {
                if (headerType === 'book_title') headerText = bookState.title || 'Libro';
                else if (headerType === 'chapter_title') headerText = activeChapter.title || '';
                else if (headerType === 'page_number') headerText = String(currentPageNumber);
                else if (headerType === 'author') headerText = 'Autor';
                else if (headerType === 'custom') {
                    headerText = isEvenPage
                        ? (settings.header_even_custom || '')
                        : (settings.header_odd_custom || '');
                }
            }
            if (targetHeader) {
                setMarginTextAlignment(targetHeader, settings.header_align || 'center', isEvenPage);
                setHeaderExteriorPlacement(targetHeader, settings.header_align || 'center', isEvenPage);
                setMarginContent(targetHeader, headerText, 'bookster-header-override');
            }
        }

        if (!useCanonicalNumbers && !forceBookStartSequence) {
            physicalPageNumber += 1;
        }
    });
};
