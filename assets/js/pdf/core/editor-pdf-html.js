// ============================================================
// MÓDULO: editor-pdf-html.js
// Responsabilidad: Procesar el Markdown de un capítulo y 
// convertirlo en HTML con títulos, subtítulos, prefijos y TOC.
// ============================================================

function markEditableChapterBlocks(html) {
    const container = document.createElement('div');
    container.innerHTML = html;
    Array.from(container.children).forEach((block, index) => {
        block.setAttribute('data-editor-block-id', `block-${index}`);
    });
    return container.innerHTML;
}

window.buildChapterHTML = function(chapter, index, settings, bookState) {
    let compiledHtml = '';
    const chapterTitleAlign = ['left', 'center', 'right'].includes(String(settings.chapter_title_align || '').toLowerCase())
        ? String(settings.chapter_title_align).toLowerCase()
        : 'center';
    
    if (chapter.is_toc == '1') {
        let tocHtml = '<div class="toc-spacer" style="height: 20px;"></div><div class="toc-container">';
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

        // Calculate max prefix length to ensure uniform column width for the numbers
        let tempCount = 0;
        let maxPrefixLen = 0;
        bookState.chapters.forEach((c) => {
            if (c.is_toc != '1' && c.is_credits != '1' && c.exclude_from_numbering !== '1') {
                tempCount++;
                let prefix = '';
                if (enumerateType === 'decimal') {
                    prefix = `${tempCount}. `;
                } else if (enumerateType === 'roman') {
                    prefix = `${toRoman(tempCount)}. `;
                } else if (enumerateType === 'bullet') {
                    prefix = `• `;
                }
                if (prefix.length > maxPrefixLen) {
                    maxPrefixLen = prefix.length;
                }
            }
        });

        bookState.chapters.forEach((c) => {
            if (c.is_toc != '1' && c.is_credits != '1' && c.exclude_from_numbering !== '1') {
                tocChapterCount++;
                let prefix = '';
                if (enumerateType === 'decimal') {
                    prefix = `${tocChapterCount}. `;
                } else if (enumerateType === 'roman') {
                    prefix = `${toRoman(tocChapterCount)}. `;
                } else if (enumerateType === 'bullet') {
                    prefix = `• `;
                }
                
                let titleHtml = c.title || 'Capítulo';
                let numberStyle = maxPrefixLen > 0 ? `style="width: ${maxPrefixLen}ch; display: inline-block; text-align: left; flex-shrink: 0;"` : '';
                tocHtml += `<div class="toc-item toc-item-h1" data-target-id="${c.id}">
                    <div class="toc-number" ${numberStyle}>${prefix}</div>
                    <div class="toc-title-wrapper">
                        <span class="toc-title">${titleHtml}</span>
                        <span class="toc-page">000</span>
                    </div>
                </div>`;
            }
        });
        tocHtml += '</div>'; // Cierra .toc-container
        compiledHtml = tocHtml;
    } else if (chapter.is_credits == '1') {
        let creditsHtml = '<div class="content-box credits-page-content" style="display: flex; flex-direction: column; height: calc(100% - 4px);">';
        let parsedTopContent = '';
        if (chapter.content && chapter.content.trim() !== '') {
            parsedTopContent = compileMarkdownToHTML(chapter.content);
        }
        creditsHtml += `<div class="credits-top-section" style="flex-grow: 1; margin-bottom: 2em;">${parsedTopContent}</div>`;
        creditsHtml += '<div class="credits-bottom-section" style="font-size: 0.85em; line-height: 1.4; padding-bottom: 2cm;">';
        
        if (settings.credits_edition) {
            const editionLabel = typeof window.almadenGetSpanishEditionLabel === 'function'
                ? window.almadenGetSpanishEditionLabel(settings.credits_edition)
                : '';
            if (editionLabel) {
                creditsHtml += `<p><strong>${editionLabel}</strong></p>`;
            }
        }
        if (settings.credits_date) {
            let formattedDate = settings.credits_date;
            if (/^\d{4}-\d{2}$/.test(settings.credits_date)) {
                const [year, month] = settings.credits_date.split('-');
                const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                const monthName = months[parseInt(month, 10) - 1];
                formattedDate = `${monthName} ${year}`;
            }
            creditsHtml += `<p><strong>Fecha de publicación:</strong> ${formattedDate}</p>`;
        }
        
        let customCredits = [];
        try {
            if (typeof settings.credits_custom === 'string') {
                customCredits = JSON.parse(settings.credits_custom);
            } else if (Array.isArray(settings.credits_custom)) {
                customCredits = settings.credits_custom;
            }
        } catch(e) {}
        
        if (customCredits && customCredits.length > 0) {
            creditsHtml += '<div class="credits-custom-list" style="margin-top: 1em; margin-bottom: 1em;">';
            customCredits.forEach(c => {
                if (c.role && c.name) {
                    creditsHtml += `<p><strong>${c.role}:</strong> ${c.name}</p>`;
                }
            });
            creditsHtml += '</div>';
        }
        
        if (settings.credits_copyright) {
            creditsHtml += `<div class="credits-copyright" style="margin-top: 2em; margin-bottom: 2em; text-align: justify;"><p>${settings.credits_copyright.replace(/\\n/g, '<br>')}</p></div>`;
        }
        
        if (settings.credits_printer) {
            creditsHtml += `<div class="credits-printer" style="margin-top: 2em; text-align: center; font-size: 0.9em; opacity: 0.8;"><p>Impreso por ${settings.credits_printer}</p></div>`;
        }
        
        creditsHtml += '</div></div>';
        compiledHtml = creditsHtml;
    } else {
        const editableHtml = markEditableChapterBlocks(compileMarkdownToHTML(chapter.content));
        compiledHtml = `<div class="chapter-editable-content" data-editor-content="chapter">${editableHtml}</div>`;
    }
    
    // Letra Capitular (Drop Cap)
    if (chapter.drop_cap_enabled === '1') {
        // Reemplazar la primera p para agregar la clase drop-cap
        compiledHtml = compiledHtml.replace(/<p>/, '<p class="drop-cap">');
    }

    if (chapter.title && chapter.title.trim() !== '' && chapter.hide_title !== '1' && chapter.is_credits !== '1') {
        const titleClass = chapter.is_toc == '1' ? 'toc-main-title' : 'chapter-main-title';
        const hasSubtitle = chapter.subtitle_text && chapter.subtitle_text.trim() !== '' && chapter.is_toc !== '1';
        const openingBlockEnabled = chapter.is_toc == '1' ? true : (chapter.opening_block_enabled !== '0');
        let extraTitleStyle = hasSubtitle ? 'padding-bottom: 0 !important;' : '';
        let titleHtml = `<h1 class="${titleClass}" style="${extraTitleStyle}">${chapter.title.trim()}</h1>`;
        let openerMinHeightEm = hasSubtitle ? 8.5 : 7.25;
        
        // Lógica de prefijo de capítulo
        if (settings.chapter_prefix_show == 1 && chapter.is_toc != '1' && chapter.exclude_from_numbering !== '1') {
            
            // Calcular el chapterNumber real (ignorando los excluidos)
            let chapterNumber = 0;
            for (let i = 0; i <= index; i++) {
                const c = bookState.chapters[i];
                if (c.is_toc !== '1' && c.is_credits !== '1' && c.exclude_from_numbering !== '1') {
                    chapterNumber++;
                }
            }
            
            const escapeHtml = (value) => String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

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

            const prefixTemplate = settings.chapter_prefix_template || 'Capítulo {N}';
            const prefixText = prefixTemplate
                .split(/(\{N\}|\{R\})/g)
                .map(part => {
                    if (part === '{N}') return `<span class="chapter-prefix-number">${chapterNumber}</span>`;
                    if (part === '{R}') return `<span class="chapter-prefix-number">${toRoman(chapterNumber)}</span>`;
                    return escapeHtml(part);
                })
                .join('');
            
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

            openerMinHeightEm += 0.8;
        }
        
        // Subtitle Logic
        const showGlobalSubtitle = settings.chapter_subtitle_show == 1 || settings.chapter_subtitle_show === undefined;
        if (chapter.subtitle_text && chapter.subtitle_text.trim() !== '' && chapter.is_toc !== '1' && showGlobalSubtitle) {
            const subText = chapter.subtitle_text.trim().replace(/\n/g, '<br>');
            const subStyles = [];
            
            const fontF = chapter.subtitle_font_family || settings.chapter_subtitle_font_family;
            if (fontF) subStyles.push(`font-family: '${fontF}', serif`);
            
            const fontSz = chapter.subtitle_font_size || settings.chapter_subtitle_font_size;
            if (fontSz) subStyles.push(`font-size: ${fontSz}pt`);
            
            const align = chapter.subtitle_align || settings.chapter_subtitle_align;
            const safeSubtitleAlign = ['left', 'center', 'right'].includes(String(align || '').toLowerCase())
                ? String(align).toLowerCase()
                : 'center';
            subStyles.push(`text-align: ${safeSubtitleAlign}`);
            subStyles.push('text-align-last: auto');
            subStyles.push('word-spacing: normal');
            subStyles.push('hyphens: none');
            
            const fStyle = chapter.subtitle_font_style || settings.chapter_subtitle_font_style;
            if (fStyle) subStyles.push(`font-style: ${fStyle}`);
            
            const fWeight = chapter.subtitle_font_weight || settings.chapter_subtitle_font_weight;
            if (fWeight) subStyles.push(`font-weight: ${fWeight}`);
            
            const tTransform = chapter.subtitle_text_transform || settings.chapter_subtitle_text_transform;
            if (tTransform) subStyles.push(`text-transform: ${tTransform}`);
            
            const lSpacing = chapter.subtitle_letter_spacing || settings.chapter_subtitle_letter_spacing;
            if (lSpacing) subStyles.push(`letter-spacing: ${lSpacing}px`);
            
            const mTop = chapter.subtitle_margin_top !== undefined && chapter.subtitle_margin_top !== '' ? chapter.subtitle_margin_top : settings.chapter_subtitle_margin_top;
            if (mTop !== undefined && mTop !== '') subStyles.push(`margin-top: ${mTop}cm`);
            
            const mBot = chapter.subtitle_margin_bottom !== undefined && chapter.subtitle_margin_bottom !== '' ? chapter.subtitle_margin_bottom : settings.chapter_subtitle_margin_bottom;
            if (mBot !== undefined && mBot !== '') subStyles.push(`margin-bottom: ${mBot}cm`);
            
            const subtitleHtml = `<div class="chapter-subtitle" style="line-height: 1.4; width: 100%; ${subStyles.join('; ')}">${subText}</div>`;
            titleHtml = titleHtml + subtitleHtml;
        }

        const openingContentHtml = `
            <div class="chapter-opening-content" data-align="${chapterTitleAlign}">
                ${titleHtml}
            </div>
        `;

        compiledHtml = (
            openingBlockEnabled
                ? `
            <div class="chapter-opening-block" style="min-height: ${openerMinHeightEm}em;">
                ${openingContentHtml}
            </div>
        `
                : openingContentHtml
        ) + `\n\n` + compiledHtml;
    }

    if (chapter.disable_hyphenation !== '1' && typeof window.almadenApplyHyphenationToHtml === 'function') {
        compiledHtml = window.almadenApplyHyphenationToHtml(compiledHtml, settings);
    }

    if (window.applySemanticChapterPostProcessing) {
        compiledHtml = window.applySemanticChapterPostProcessing(chapter, compiledHtml);
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
