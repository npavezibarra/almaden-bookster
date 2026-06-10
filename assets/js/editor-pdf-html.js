// ============================================================
// MÓDULO: editor-pdf-html.js
// Responsabilidad: Procesar el Markdown de un capítulo y 
// convertirlo en HTML con títulos, subtítulos, prefijos y TOC.
// ============================================================

window.buildChapterHTML = function(chapter, index, settings, bookState) {
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
    
    return compiledHtml;
};

window.updateTOCPagesInCache = function(scroller, bookState) {
    if (window.currentPreviewMode === 'full' && scroller.id === 'pdf-scroller') {
        Object.keys(window.pdfPagesCache).forEach(pageNum => {
            let html = window.pdfPagesCache[pageNum];
            if (html && html.includes('toc-item')) {
                let modified = false;
                
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                bookState.chapters.forEach(ch => {
                    if (window.bookChapterPages[ch.id]) {
                        const items = doc.querySelectorAll(`.toc-item[data-target-id="${ch.id}"]`);
                        items.forEach(item => {
                            const pageSpan = item.querySelector('.toc-page');
                            if (pageSpan) {
                                pageSpan.textContent = window.bookChapterPages[ch.id];
                                modified = true;
                            }
                        });
                    }
                });
                
                if (modified) {
                    html = doc.body.innerHTML;
                    window.pdfPagesCache[pageNum] = html;
                    
                    const activePage = scroller.querySelector(`.pdf-page[data-virtual-page="${pageNum}"]`);
                    if (activePage && !activePage.classList.contains('is-virtualized')) {
                        activePage.innerHTML = html;
                    }
                }
            }
        });
    } else {
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
    }
};
