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

function normalizeOpeningPageAlignmentValue(rawValue) {
    const normalized = String(rawValue || '')
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-')
        .replace(/\//g, '-');

    const parts = normalized.split('-').filter(Boolean);
    if (parts.length >= 2) {
        const horizontal = ['left', 'center', 'right'].includes(parts[0]) ? parts[0] : '';
        const vertical = ['top', 'center', 'bottom'].includes(parts[1]) ? parts[1] : '';
        if (horizontal && vertical) {
            return `${horizontal}-${vertical}`;
        }
    }

    return '';
}

function deriveOpeningPageAlignment(settings) {
    const combined = normalizeOpeningPageAlignmentValue(settings && settings.chapter_page_one_align);
    if (combined) {
        const [horizontal, vertical] = combined.split('-');
        return { horizontal, vertical, combined };
    }

    const legacyHorizontal = ['left', 'center', 'right'].includes(String(settings && settings.chapter_page_one_align ? settings.chapter_page_one_align : '').toLowerCase())
        ? String(settings.chapter_page_one_align).toLowerCase()
        : (['left', 'center', 'right'].includes(String(settings && settings.chapter_title_align ? settings.chapter_title_align : '').toLowerCase())
            ? String(settings.chapter_title_align).toLowerCase()
            : 'center');
    const legacyVertical = ['top', 'center', 'bottom'].includes(String(settings && settings.chapter_page_one_vertical ? settings.chapter_page_one_vertical : '').toLowerCase())
        ? String(settings.chapter_page_one_vertical).toLowerCase()
        : (String(settings && settings.chapter_page_one_vertical ? settings.chapter_page_one_vertical : '').toLowerCase() === 'half' ? 'center' : 'top');

    return {
        horizontal: legacyHorizontal,
        vertical: legacyVertical,
        combined: `${legacyHorizontal}-${legacyVertical}`,
    };
}

// The separated opening page has one authoritative 3x3 position.  It must
// not inherit legacy per-chapter controls, which belong to the old flow mode.
window.getSeparateOpeningPageAlignment = function(settings) {
    // The preview can be rebuilt before the asynchronous settings state is
    // replaced. The visible selector is therefore the authoritative source
    // for the separated-opening canvas during that render.
    const control = typeof document !== 'undefined'
        ? document.getElementById('setting-chapter-page-one-align')
        : null;
    const selectedAlignment = normalizeOpeningPageAlignmentValue(control && control.value);
    if (selectedAlignment) {
        const [horizontal, vertical] = selectedAlignment.split('-');
        return { horizontal, vertical, combined: selectedAlignment };
    }

    return deriveOpeningPageAlignment(settings);
};

function getOpeningPageVerticalAlign(chapter, settings) {
    return getOpeningPageAlignment(chapter, settings).vertical;
}

function getOpeningPageHorizontalAlign(chapter, settings) {
    return getOpeningPageAlignment(chapter, settings).horizontal;
}

function getOpeningPageAlignmentStyles(chapter, settings) {
    const resolvedAlignment = getOpeningPageAlignment(chapter, settings);
    const verticalAlign = resolvedAlignment.vertical;
    const horizontalAlign = resolvedAlignment.horizontal;

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

function getOpeningPageAlignment(chapter, settings) {
    const globalAlignment = deriveOpeningPageAlignment(settings);
    const chapterHorizontal = String(chapter && chapter.opening_block_horizontal_align ? chapter.opening_block_horizontal_align : '').toLowerCase();
    const chapterVertical = String(chapter && chapter.opening_block_vertical_align ? chapter.opening_block_vertical_align : '').toLowerCase();
    const hasExplicitChapterOverride = (
        ['left', 'center', 'right'].includes(chapterHorizontal) && chapterHorizontal !== 'center'
    ) || (
        ['top', 'center', 'bottom'].includes(chapterVertical) && chapterVertical !== 'top'
    ) || (
        chapterHorizontal === 'center' && chapterVertical === 'center'
    );

    if (hasExplicitChapterOverride) {
        return {
            horizontal: ['left', 'center', 'right'].includes(chapterHorizontal) ? chapterHorizontal : globalAlignment.horizontal,
            vertical: ['top', 'center', 'bottom'].includes(chapterVertical) ? chapterVertical : globalAlignment.vertical,
            combined: `${['left', 'center', 'right'].includes(chapterHorizontal) ? chapterHorizontal : globalAlignment.horizontal}-${['top', 'center', 'bottom'].includes(chapterVertical) ? chapterVertical : globalAlignment.vertical}`,
        };
    }

    return globalAlignment;
}

function getOpeningPageAbsolutePositionStyles(chapter, settings) {
    const resolvedAlignment = getOpeningPageAlignment(chapter, settings);
    const verticalAlign = resolvedAlignment.vertical;
    const horizontalAlign = resolvedAlignment.horizontal;
    const styles = [
        'position: absolute !important',
        'display: block !important',
        'width: fit-content !important',
        'max-width: 100% !important',
        'margin: 0 !important',
        'padding: 0 !important',
        'box-sizing: border-box !important',
        'z-index: 1 !important',
    ];

    if (horizontalAlign === 'left') {
        styles.push('left: 0 !important', 'right: auto !important');
    } else if (horizontalAlign === 'right') {
        styles.push('right: 0 !important', 'left: auto !important');
    } else {
        styles.push('left: 50% !important', 'right: auto !important');
    }

    if (verticalAlign === 'top') {
        styles.push('top: 0 !important', 'bottom: auto !important');
    } else if (verticalAlign === 'bottom') {
        styles.push('bottom: 0 !important', 'top: auto !important');
    } else {
        styles.push('top: 50% !important', 'bottom: auto !important');
    }

    const transforms = [];
    if (horizontalAlign === 'center') {
        transforms.push('translateX(-50%)');
    }
    if (verticalAlign === 'center') {
        transforms.push('translateY(-50%)');
    }
    styles.push(`transform: ${transforms.length ? transforms.join(' ') : 'none'} !important`);

    return styles.join('; ');
}

function buildChapterOpeningHtml(chapter, index, settings, bookState, options = {}) {
    if (!chapter) return '';

    const forceRenderOpeningBlock = options.forceRenderOpeningBlock === true;
    const separateOpeningCanvas = options.separateOpeningCanvas === true;
    const openingBlockEnabled = forceRenderOpeningBlock || chapter.is_toc == '1' ? true : (chapter.opening_block_enabled !== '0');
    const openingVisibility = window.getChapterOpeningVisibility
        ? window.getChapterOpeningVisibility(chapter, settings)
        : {
            hasTitle: !!(chapter.title && chapter.title.trim() !== ''),
            showTitle: !!(chapter.title && chapter.title.trim() !== '' && chapter.hide_title !== '1' && chapter.is_credits !== '1' && chapter.is_toc !== '1'),
            showPrefix: false,
            showSubtitle: false,
            hasVisibleContent: !!(chapter.title && chapter.title.trim() !== '' && chapter.hide_title !== '1' && chapter.is_credits !== '1' && chapter.is_toc !== '1'),
        };

    if (!openingBlockEnabled || !openingVisibility.hasVisibleContent) {
        return '';
    }

    const titleClass = chapter.is_toc == '1' ? 'toc-main-title' : 'chapter-main-title';
    const hasSubtitle = openingVisibility.showSubtitle;
    const hasTitle = openingVisibility.showTitle;
    const hasPrefix = openingVisibility.showPrefix;
    const prefixPosition = String(settings.chapter_prefix_position || 'above').toLowerCase() === 'below' ? 'below' : 'above';
    const openingParts = [];
    let openerMinHeightEm = 0;

    if (hasPrefix) {
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
        if (prefixPosition === 'above') {
            openingParts.push(prefixHtml);
        }
        if (hasTitle) {
            const titleExtraStyle = hasSubtitle ? 'padding-bottom: 0 !important;' : '';
            openingParts.push(`<h1 class="${titleClass}" style="${titleExtraStyle}">${chapter.title.trim()}</h1>`);
        }
        if (prefixPosition === 'below') {
            openingParts.push(prefixHtml);
        }
    }

    const showGlobalSubtitle = settings.chapter_subtitle_show == 1 || settings.chapter_subtitle_show === undefined;
    if (hasSubtitle && showGlobalSubtitle) {
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
        openingParts.push(subtitleHtml);
    }

    if (hasTitle) {
        openerMinHeightEm = hasSubtitle ? 8.5 : 7.25;
        if (hasPrefix) {
            openerMinHeightEm += 0.8;
        }
    } else if (hasSubtitle) {
        openerMinHeightEm = hasPrefix ? 3.0 : 2.2;
    } else if (hasPrefix) {
        openerMinHeightEm = 1.2;
    }

    const openingContentHtml = `
        <div class="chapter-opening-content" data-align="${getOpeningPageHorizontalAlign(chapter, settings)}">
            ${openingParts.join('\n')}
        </div>
    `;

    const alignStyles = getOpeningPageAlignmentStyles(chapter, settings);
    const openingClass = options.variant === 'blank-page'
        ? 'chapter-opening-page-block chapter-opening-page-block--blank'
        : 'chapter-opening-block';
    const blockStyle = [
        'position: static !important',
        `display: ${separateOpeningCanvas ? 'inline-block' : 'block'} !important`,
        'width: fit-content !important',
        'max-width: 100% !important',
        'height: fit-content !important',
        'margin: 0 !important',
        'padding: 0 !important',
        'box-sizing: border-box !important',
        'z-index: 1 !important',
        `text-align: ${alignStyles.textAlign} !important`,
    ].filter(Boolean).join('; ');

    return `
        <div class="${openingClass}" style="${blockStyle}">
            ${openingContentHtml}
        </div>
    `;
}
