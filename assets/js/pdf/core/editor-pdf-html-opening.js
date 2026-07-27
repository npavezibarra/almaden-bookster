function normalizeMarkdownHeadingText(text) {
    return String(text || '')
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&quot;/g, '"')
        .replace(/&#039;/g, "'")
        .replace(/\s+/g, ' ')
        .trim();
}

function stripLeadingDuplicateChapterHeading(markdownText, chapterTitle) {
    if (!markdownText || !chapterTitle) return markdownText || '';

    const lines = String(markdownText).split('\n');
    const targetTitle = normalizeMarkdownHeadingText(chapterTitle);
    let foundContent = false;

    for (let i = 0; i < lines.length; i++) {
        const rawLine = lines[i];
        const trimmed = rawLine.trim();

        if (trimmed === '') {
            if (!foundContent) continue;
            break;
        }

        if (!foundContent) {
            const headingMatch = trimmed.match(/^(#{1,6})\s+(.*)$/);
            if (headingMatch) {
                const headingText = normalizeMarkdownHeadingText(
                    headingMatch[2].replace(/\*\*\*(.*?)\*\*\*/g, '$1')
                        .replace(/\*\*(.*?)\*\*/g, '$1')
                        .replace(/\*(.*?)\*/g, '$1')
                );
                if (headingText === targetTitle) {
                    lines.splice(0, i + 1);
                    while (lines.length > 0 && lines[0].trim() === '') {
                        lines.shift();
                    }
                    return lines.join('\n');
                }
            }
        }

        foundContent = true;
    }

    return markdownText;
}

function getOpeningPageVerticalAlign(chapter, settings) {
    const chapterValue = String(chapter && chapter.opening_block_vertical_align ? chapter.opening_block_vertical_align : '').toLowerCase();
    if (['top', 'center', 'bottom'].includes(chapterValue)) {
        return chapterValue;
    }

    const globalValue = String(settings && settings.chapter_page_one_vertical ? settings.chapter_page_one_vertical : 'top').toLowerCase();
    if (globalValue === 'half') return 'center';
    if (['top', 'center', 'bottom'].includes(globalValue)) return globalValue;
    return 'top';
}

function getOpeningPageHorizontalAlign(chapter, settings) {
    const chapterValue = String(chapter && chapter.opening_block_horizontal_align ? chapter.opening_block_horizontal_align : '').toLowerCase();
    if (['left', 'center', 'right'].includes(chapterValue)) {
        return chapterValue;
    }

    const globalValue = String(settings && settings.chapter_title_align ? settings.chapter_title_align : 'center').toLowerCase();
    if (['left', 'center', 'right'].includes(globalValue)) {
        return globalValue;
    }

    return 'center';
}

function getOpeningPageAlignmentStyles(chapter, settings) {
    const verticalAlign = getOpeningPageVerticalAlign(chapter, settings);
    const horizontalAlign = getOpeningPageHorizontalAlign(chapter, settings);

    return {
        verticalAlign,
        horizontalAlign,
        justifyContent: verticalAlign === 'center'
            ? 'center'
            : (verticalAlign === 'bottom' ? 'flex-end' : 'flex-start'),
        alignItems: horizontalAlign === 'left'
            ? 'flex-start'
            : (horizontalAlign === 'right' ? 'flex-end' : 'center'),
        textAlign: horizontalAlign,
    };
}

function buildChapterOpeningHtml(chapter, index, settings, bookState, options = {}) {
    if (!chapter) return '';

    const forceRenderOpeningBlock = options.forceRenderOpeningBlock === true;
    const openingBlockEnabled = forceRenderOpeningBlock || chapter.is_toc == '1' ? true : (chapter.opening_block_enabled !== '0');
    const forceRenderTitle = options.forceRenderTitle === true;
    const hasTitle = chapter.title && chapter.title.trim() !== '';
    const shouldRenderTitle = forceRenderTitle ? hasTitle : (hasTitle && chapter.hide_title !== '1');
    const shouldRenderOpening = shouldRenderTitle && chapter.is_credits !== '1';
    if (!openingBlockEnabled || !shouldRenderOpening) {
        return '';
    }

    const titleClass = chapter.is_toc == '1' ? 'toc-main-title' : 'chapter-main-title';
    const hasSubtitle = chapter.subtitle_text && chapter.subtitle_text.trim() !== '' && chapter.is_toc !== '1';
    let extraTitleStyle = hasSubtitle ? 'padding-bottom: 0 !important;' : '';
    let titleHtml = `<h1 class="${titleClass}" style="${extraTitleStyle}">${chapter.title.trim()}</h1>`;
    let openerMinHeightEm = hasSubtitle ? 8.5 : 7.25;

    if (settings.chapter_prefix_show == 1 && chapter.is_toc != '1' && chapter.exclude_from_numbering !== '1') {
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
            ornamentHtml = '<div class="chapter-prefix-line"></div>';
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
        <div class="chapter-opening-content" data-align="${getOpeningPageHorizontalAlign(chapter, settings)}">
            ${titleHtml}
        </div>
    `;

    const alignStyles = getOpeningPageAlignmentStyles(chapter, settings);
    const openingClass = options.variant === 'blank-page'
        ? 'chapter-opening-page-block chapter-opening-page-block--blank'
        : 'chapter-opening-block';
    const blockStyle = options.variant === 'blank-page'
        ? [
            'display: flex !important',
            'flex-direction: column !important',
            'width: 100% !important',
            'min-height: 100% !important',
            'height: 100% !important',
            'flex: 1 1 auto !important',
            'box-sizing: border-box !important',
            `justify-content: ${alignStyles.justifyContent} !important`,
            `align-items: ${alignStyles.alignItems} !important`,
            `text-align: ${alignStyles.textAlign} !important`,
        ].join('; ')
        : `min-height: ${openerMinHeightEm}em;`;

    return `
        <div class="${openingClass}" style="${blockStyle}">
            ${openingContentHtml}
        </div>
    `;
}

