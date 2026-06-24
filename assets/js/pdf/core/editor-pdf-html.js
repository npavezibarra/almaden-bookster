// ============================================================
// MÓDULO: editor-pdf-html.js
// Responsabilidad: Procesar el Markdown de un capítulo y 
// convertirlo en HTML con títulos, subtítulos, prefijos y TOC.
// ============================================================

const ALMADEN_SOFT_HYPHEN = '\u00AD';

function almadenNormalizeHyphenationKey(word) {
    return String(word || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

function almadenIsSpanishVowel(ch) {
    return /[aeiouáéíóúü]/i.test(ch);
}

function almadenIsStrongVowel(ch) {
    return /[aáeéoó]/i.test(ch);
}

function almadenHasAccent(ch) {
    return /[áéíóú]/i.test(ch);
}

function almadenIsWeakVowel(ch) {
    return /[iíuúü]/i.test(ch);
}

function almadenIsFrontVowel(ch) {
    return /[eiéií]/i.test(ch);
}

function almadenAllowedSpanishOnset(cluster) {
    const normalized = String(cluster || '').toLowerCase();
    return [
        'bl', 'br', 'cl', 'cr', 'dr', 'fl', 'fr', 'gl', 'gr', 'pl', 'pr', 'tr', 'tl'
    ].includes(normalized);
}

function almadenTokenizeSpanishWord(word) {
    const chars = Array.from(String(word || '').normalize('NFC'));
    const tokens = [];

    for (let i = 0; i < chars.length; i++) {
        const ch = chars[i];
        const lower = ch.toLowerCase();
        const next = chars[i + 1] || '';
        const nextLower = next.toLowerCase();
        const next2 = chars[i + 2] || '';

        if (lower === 'c' && nextLower === 'h') {
            tokens.push({ text: ch + next, type: 'cons' });
            i++;
            continue;
        }

        if (lower === 'l' && nextLower === 'l') {
            tokens.push({ text: ch + next, type: 'cons' });
            i++;
            continue;
        }

        if (lower === 'r' && nextLower === 'r') {
            tokens.push({ text: ch + next, type: 'cons' });
            i++;
            continue;
        }

        if (lower === 'q' && nextLower === 'u' && almadenIsFrontVowel(next2)) {
            tokens.push({ text: ch + next, type: 'cons' });
            i++;
            continue;
        }

        if (lower === 'g' && nextLower === 'u' && almadenIsFrontVowel(next2) && next !== 'ü') {
            tokens.push({ text: ch + next, type: 'cons' });
            i++;
            continue;
        }

        tokens.push({
            text: ch,
            type: almadenIsSpanishVowel(ch) ? 'vowel' : 'cons'
        });
    }

    return tokens;
}

function almadenIsSpanishDiphthong(left, right) {
    if (!left || !right) return false;
    const leftVowel = left.text;
    const rightVowel = right.text;

    if (!almadenIsSpanishVowel(leftVowel) || !almadenIsSpanishVowel(rightVowel)) return false;
    if (almadenIsStrongVowel(leftVowel) && almadenIsStrongVowel(rightVowel)) return false;
    if (almadenHasAccent(leftVowel) && almadenIsWeakVowel(leftVowel)) return false;
    if (almadenHasAccent(rightVowel) && almadenIsWeakVowel(rightVowel)) return false;
    if (almadenIsWeakVowel(leftVowel) && almadenIsWeakVowel(rightVowel)) {
        return !(almadenHasAccent(leftVowel) || almadenHasAccent(rightVowel));
    }

    return true;
}

function almadenIsSpanishTriphthong(a, b, c) {
    if (!a || !b || !c) return false;
    if (!almadenIsWeakVowel(a.text) || !almadenIsStrongVowel(b.text) || !almadenIsWeakVowel(c.text)) return false;
    if (almadenHasAccent(a.text) || almadenHasAccent(b.text) || almadenHasAccent(c.text)) return false;
    return true;
}

function almadenSplitSpanishWordIntoSyllables(word) {
    const tokens = almadenTokenizeSpanishWord(word);
    if (tokens.length < 2) {
        return [word];
    }

    const vowelGroups = [];
    let i = 0;

    while (i < tokens.length) {
        if (tokens[i].type !== 'vowel') {
            i++;
            continue;
        }

        const start = i;
        let end = i + 1;
        const next = tokens[end];
        const next2 = tokens[end + 1];

        if (next && next.type === 'vowel') {
            if (next2 && next2.type === 'vowel' && almadenIsSpanishTriphthong(tokens[start], next, next2)) {
                end = i + 3;
            } else if (almadenIsSpanishDiphthong(tokens[start], next)) {
                end = i + 2;
            }
        }

        vowelGroups.push({ start, end });
        i = end;
    }

    if (vowelGroups.length <= 1) {
        return [word];
    }

    const boundaries = [];
    let currentStart = 0;

    for (let g = 0; g < vowelGroups.length - 1; g++) {
        const current = vowelGroups[g];
        const next = vowelGroups[g + 1];
        const clusterStart = current.end;
        const clusterEnd = next.start;
        const cluster = tokens.slice(clusterStart, clusterEnd);
        let breakAt = clusterStart;

        if (cluster.length === 1) {
            breakAt = clusterStart;
        } else if (cluster.length === 2) {
            const clusterText = cluster.map((t) => t.text).join('').toLowerCase();
            breakAt = almadenAllowedSpanishOnset(clusterText) ? clusterStart : clusterStart + 1;
        } else if (cluster.length >= 3) {
            const lastTwoText = cluster.slice(-2).map((t) => t.text).join('').toLowerCase();
            breakAt = almadenAllowedSpanishOnset(lastTwoText) ? (clusterEnd - 2) : (clusterEnd - 1);
        } else {
            breakAt = current.end;
        }

        if (breakAt > currentStart) {
            boundaries.push(breakAt);
            currentStart = breakAt;
        }
    }

    const syllables = [];
    let startIndex = 0;

    boundaries.forEach((boundary) => {
        syllables.push(tokens.slice(startIndex, boundary).map((t) => t.text).join(''));
        startIndex = boundary;
    });

    syllables.push(tokens.slice(startIndex).map((t) => t.text).join(''));

    return syllables.filter(Boolean);
}

function almadenHyphenateSpanishWord(word, exceptionSet) {
    const normalized = String(word || '').normalize('NFC');
    const exceptionKey = almadenNormalizeHyphenationKey(normalized);
    if (!normalized || normalized.length < 6 || !/[aeiouáéíóúü]/i.test(normalized) || exceptionSet.has(exceptionKey)) {
        return normalized;
    }

    const syllables = almadenSplitSpanishWordIntoSyllables(normalized);
    if (syllables.length < 2) {
        return normalized;
    }

    return syllables.join(ALMADEN_SOFT_HYPHEN);
}

function almadenApplyHyphenationToText(text, exceptionSet) {
    if (!text) return text;
    return text.replace(/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:['’][A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*/g, (match) => {
        return almadenHyphenateSpanishWord(match, exceptionSet);
    });
}

function almadenApplyHyphenationToHtml(html, settings) {
    const language = String(settings.content_language || 'es').toLowerCase();
    const hyphenationEnabled = !(settings.content_hyphenation === 0 || settings.content_hyphenation === '0');
    if (!hyphenationEnabled || !language.startsWith('es')) {
        return html;
    }

    const exceptionsRaw = String(settings.content_hyphenation_exceptions || '');
    const exceptionSet = new Set(
        exceptionsRaw
            .split(/[\n,;]+/)
            .map((word) => almadenNormalizeHyphenationKey(word))
            .filter(Boolean)
    );

    const container = document.createElement('div');
    container.innerHTML = html;

    const targets = container.querySelectorAll('p, li, blockquote, .chapter-subtitle, .credits-page-content p');
    targets.forEach((element) => {
        const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null);
        const textNodes = [];
        let node;
        while ((node = walker.nextNode())) {
            if (node.nodeValue && node.nodeValue.trim()) {
                textNodes.push(node);
            }
        }

        textNodes.forEach((textNode) => {
            const original = textNode.nodeValue;
            const hyphenated = almadenApplyHyphenationToText(original, exceptionSet);
            if (hyphenated !== original) {
                textNode.nodeValue = hyphenated;
            }
        });
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
            creditsHtml += `<p><strong>Número de edición:</strong> ${settings.credits_edition}</p>`;
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
        compiledHtml = compileMarkdownToHTML(chapter.content);
    }
    
    // Letra Capitular (Drop Cap)
    if (chapter.drop_cap_enabled === '1') {
        // Reemplazar la primera p para agregar la clase drop-cap
        compiledHtml = compiledHtml.replace(/<p>/, '<p class="drop-cap">');
    }

    if (chapter.title && chapter.title.trim() !== '' && chapter.hide_title !== '1' && chapter.is_credits !== '1') {
        const titleClass = chapter.is_toc == '1' ? 'toc-main-title' : 'chapter-main-title';
        const hasSubtitle = chapter.subtitle_text && chapter.subtitle_text.trim() !== '' && chapter.is_toc !== '1';
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

        compiledHtml = `
            <div class="chapter-opening-block" style="min-height: ${openerMinHeightEm}em;">
                <div class="chapter-opening-content" data-align="${chapterTitleAlign}">
                    ${titleHtml}
                </div>
            </div>
        ` + `\n\n` + compiledHtml;
    }

    if (chapter.disable_hyphenation !== '1') {
        compiledHtml = almadenApplyHyphenationToHtml(compiledHtml, settings);
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
