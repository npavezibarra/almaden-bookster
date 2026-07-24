// ============================================================
// MÓDULO: editor-pdf-dom.js
// Responsabilidad: Helpers para crear el DOM virtual de las
// páginas PDF y gestionar la estructura física de cada página.
// ============================================================

// Crea la estructura HTML limpia de una página física virtual del libro
window.createNewPageElement = function(pageNumber, chapter, isFirstPageOfChapter = false, isBlankPage = false) {
    const isOdd = (pageNumber % 2 !== 0);
    const pageDiv = document.createElement('div');
    pageDiv.className = 'pdf-page' + (isBlankPage ? ' blank-page' : '') + (isOdd ? ' page-odd' : ' page-even');

    const settings = bookState.settings || {};
    const parityImageUrl = chapter ? chapter.parity_image : null;
    const openingPageMode = window.getEffectiveOpeningPageMode ? window.getEffectiveOpeningPageMode(chapter) : 'none';
    const chapterTitle = chapter ? chapter.title : '';

    const firstPageHeaderType = (chapter && chapter.first_page_header_type && chapter.first_page_header_type !== 'global') ? chapter.first_page_header_type : (settings.first_page_header_type || 'blank');
    const firstPageHeaderCustom = (chapter && chapter.first_page_header_custom) ? chapter.first_page_header_custom : (settings.first_page_header_custom || '');
    const firstPageFooterType = (chapter && chapter.first_page_footer_type && chapter.first_page_footer_type !== 'global') ? chapter.first_page_footer_type : (settings.first_page_footer_type || 'page_number');
    const firstPageFooterCustom = (chapter && chapter.first_page_footer_custom) ? chapter.first_page_footer_custom : (settings.first_page_footer_custom || '');
    const customRunningHeader = (chapter && chapter.custom_running_header) ? chapter.custom_running_header : null;
    const isToc = (chapter && chapter.is_toc == '1');
    const pageOneVertical = settings.chapter_page_one_vertical || 'top';
    const disableHyphenation = (chapter && chapter.disable_hyphenation === '1');
    const chapterTitleAlign = ['left', 'center', 'right'].includes(String(settings.chapter_title_align || '').toLowerCase())
        ? String(settings.chapter_title_align).toLowerCase()
        : 'center';

    if (isBlankPage) {
        if (openingPageMode === 'image' && parityImageUrl) {
            const mode = (chapter && chapter.parity_image_mode) ? chapter.parity_image_mode : 'content';
            
            if (mode === 'bleed') {
                pageDiv.innerHTML = `
                    <div class="parity-bleed-container" style="position: absolute; inset: 0; z-index: 0;">
                        <img src="${parityImageUrl}" alt="Página de paridad" style="width: 100%; height: 100%; object-fit: cover;" />
                    </div>
                `;
            } else if (mode === 'custom') {
                const imgWidth = chapter.parity_image_width ? `${chapter.parity_image_width}%` : '100%';
                const imgHeight = chapter.parity_image_height ? `${chapter.parity_image_height}%` : 'auto';
                pageDiv.innerHTML = `
                    <div class="pdf-content" style="padding: 0; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; position: relative; z-index: 0;">
                        <img src="${parityImageUrl}" alt="Página de paridad" style="width: ${imgWidth}; height: ${imgHeight}; object-fit: contain;" />
                    </div>
                `;
            } else { // mode === 'content'
                pageDiv.innerHTML = `
                    <div class="pdf-content" style="padding: 0; display: flex; width: 100%; height: 100%; position: relative; z-index: 0;">
                        <img src="${parityImageUrl}" alt="Página de paridad" style="width: 100%; height: 100%; object-fit: contain;" />
                    </div>
                `;
            }
        } else {
            pageDiv.innerHTML = '';
        }
        return pageDiv;
    }

    const isEven = (pageNumber % 2 === 0);

    // Header Content
    let headerHtml = '&nbsp;';
    let headerType = isFirstPageOfChapter ? firstPageHeaderType : (isEven ? (settings.header_even_type || 'book_title') : (settings.header_odd_type || 'chapter_title'));
    if (isToc && chapter.toc_hide_header !== '0') {
        headerType = 'blank';
    }
    if (chapter && chapter.hide_all_headers_footers === '1') {
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
            headerHtml = `<span>Autor</span>`;
        } else if (headerType === 'custom') {
            const customText = isFirstPageOfChapter ? firstPageHeaderCustom : (isEven ? (settings.header_even_custom || '') : (settings.header_odd_custom || ''));
            headerHtml = `<span>${customText}</span>`;
        }
    }

    // Footer Content
    let footerHtml = '&nbsp;';
    let footerType = isFirstPageOfChapter ? firstPageFooterType : (isEven ? (settings.footer_even_type || 'page_number') : (settings.footer_odd_type || 'page_number'));
    
    if (chapter && chapter.hide_all_headers_footers === '1') {
        footerType = 'blank';
    }
    
    if (chapter && chapter.is_credits == '1' && chapter.credits_hide_page_number === '1') {
        footerType = 'blank';
    }

    if (isToc && chapter.toc_hide_page_numbers !== '0') {
        footerType = 'blank';
    }
    
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
        const align = (isToc && chapter.toc_title_align) ? chapter.toc_title_align : chapterTitleAlign;
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
            <div class="pdf-content-inner" style="display: flow-root; ${(chapter && chapter.is_credits == '1') ? 'height: calc(100% - 4px);' : ''}"></div>
        </div>
        <div class="pdf-footer text-xs">${footerHtml}</div>
        <div class="global-trim-line"></div>
    `;

    return pageDiv;
};
