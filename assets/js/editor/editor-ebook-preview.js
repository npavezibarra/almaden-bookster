// Preview Ebook dentro del editor.

let ebookPreviewCurrentChapterIndex = -1;

function getEbookPreviewState() {
    return (typeof bookState !== 'undefined' && bookState) ? bookState : (window.bookState || null);
}

function getEbookPreviewChapters() {
    const state = getEbookPreviewState();
    return Array.isArray(state?.chapters) ? state.chapters : [];
}

function isEbookPreviewChapterVisible(chapter) {
    if (!chapter) return false;
    return chapter.is_toc !== '1' && chapter.is_credits !== '1';
}

function getEbookPreviewVisibleChapterIndexes() {
    const chapters = getEbookPreviewChapters();
    const indexes = [];
    chapters.forEach((chapter, index) => {
        if (isEbookPreviewChapterVisible(chapter)) {
            indexes.push(index);
        }
    });
    return indexes;
}

function getEbookPreviewChapterIndexById(chapterId) {
    const chapters = getEbookPreviewChapters();
    return chapters.findIndex((chapter) => String(chapter?.id ?? '') === String(chapterId ?? ''));
}

function getEbookPreviewVisibleChapterNumber(index) {
    const chapters = getEbookPreviewChapters();
    let chapterNumber = 0;
    for (let i = 0; i <= index; i++) {
        const chapter = chapters[i];
        if (isEbookPreviewChapterVisible(chapter) && chapter.exclude_from_numbering !== '1') {
            chapterNumber += 1;
        }
    }
    return chapterNumber;
}

function escapeEbookPreviewHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function toEbookPreviewRomanNumeral(num) {
    const map = {
        M: 1000,
        CM: 900,
        D: 500,
        CD: 400,
        C: 100,
        XC: 90,
        L: 50,
        XL: 40,
        X: 10,
        IX: 9,
        V: 5,
        IV: 4,
        I: 1,
    };
    let remaining = Number(num) || 0;
    let out = '';
    Object.keys(map).forEach((key) => {
        const repeat = Math.floor(remaining / map[key]);
        if (repeat > 0) {
            out += key.repeat(repeat);
            remaining -= repeat * map[key];
        }
    });
    return out || String(num || '');
}

function getEbookMarkdownRenderer() {
    if (!window.__almadenEbookMarkdownRenderer && typeof window.markdownit === 'function') {
        const instance = window.markdownit({ html: true, breaks: true });
        if (typeof window.markdownitFootnote === 'function') {
            instance.use(window.markdownitFootnote);
        }
        window.__almadenEbookMarkdownRenderer = instance;
    }
    return window.__almadenEbookMarkdownRenderer || null;
}

function normalizeEbookPreviewImages(html) {
    if (!html) return html;

    const template = document.createElement('template');
    template.innerHTML = html;
    template.content.querySelectorAll('img[data-preview-src]').forEach((img) => {
        const previewSrc = img.getAttribute('data-preview-src') || '';
        if (previewSrc) {
            img.setAttribute('src', previewSrc);
        }
    });
    return template.innerHTML;
}

function syncEbookPreviewNavTitleVisibility() {
    const scroller = document.getElementById('ebook-chapter-scroll-area');
    const navTitle = document.getElementById('ebook-chapter-nav-title');
    if (!scroller || !navTitle) return;

    const shouldShow = scroller.scrollTop > 200;
    navTitle.classList.toggle('opacity-100', shouldShow);
    navTitle.classList.toggle('opacity-0', !shouldShow);
}

function bindEbookPreviewScrollState() {
    const scroller = document.getElementById('ebook-chapter-scroll-area');
    if (!scroller || scroller.dataset.ebookPreviewScrollBound === '1') return;

    scroller.dataset.ebookPreviewScrollBound = '1';
    scroller.addEventListener('scroll', () => {
        syncEbookPreviewNavTitleVisibility();
    });
}

function applyEbookPreviewStyles() {
    const state = getEbookPreviewState();
    const settings = state?.settings || {};
    const styleId = 'editor-ebook-preview-styles';
    let styleEl = document.getElementById(styleId);
    if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = styleId;
        document.head.appendChild(styleEl);
    }

    const bgType = settings.ebook_bg_type || 'color';
    const bgOpacity = settings.ebook_bg_opacity !== undefined ? settings.ebook_bg_opacity : 1;
    const toRgba = (hex, opacity) => {
        if (!hex || typeof hex !== 'string' || !hex.startsWith('#')) return hex;
        let color = hex.slice(1);
        if (color.length === 3) {
            color = color.split('').map((ch) => ch + ch).join('');
        }
        const r = parseInt(color.slice(0, 2), 16) || 0;
        const g = parseInt(color.slice(2, 4), 16) || 0;
        const b = parseInt(color.slice(4, 6), 16) || 0;
        return `rgba(${r}, ${g}, ${b}, ${opacity})`;
    };

    let previewBg = '#f6f3ed';
    if (bgType === 'color') {
        previewBg = toRgba(settings.ebook_bg_color || '#ffffff', bgOpacity);
    } else if (bgType === 'image' && settings.ebook_bg_image) {
        const overlayAlpha = 1 - bgOpacity;
        const overlayColor = `rgba(0, 0, 0, ${overlayAlpha})`;
        previewBg = `linear-gradient(${overlayColor}, ${overlayColor}), url('${settings.ebook_bg_image}')`;
    }

    const chapterFontFamily = `'${settings.ebook_font_family_content || 'Merriweather'}', Georgia, serif`;
    const headingFontFamily = `'${settings.ebook_font_family_headings || 'Playfair Display'}', serif`;
    const chapterTitleAlign = settings.ebook_chapter_title_align || 'center';
    const chapterTitleTextTransform = settings.ebook_chapter_title_text_transform || 'none';
    const chapterTitlePaddingTop = settings.ebook_chapter_title_padding_top !== undefined ? settings.ebook_chapter_title_padding_top : 2;
    const chapterTitlePaddingBottom = settings.ebook_chapter_title_padding_bottom !== undefined ? settings.ebook_chapter_title_padding_bottom : 2;
    const chapterTitlePaddingLeft = settings.ebook_chapter_title_padding_left !== undefined ? settings.ebook_chapter_title_padding_left : 0;
    const chapterTitlePaddingRight = settings.ebook_chapter_title_padding_right !== undefined ? settings.ebook_chapter_title_padding_right : 0;
    const textAlign = settings.ebook_text_align_justify == 1 ? 'justify' : 'left';
    const hyphenation = settings.ebook_hyphenation == 1 ? 'auto' : 'none';
    const contentSize = Math.min(52, Math.max(18, parseFloat(settings.ebook_font_size_content || 18)));
    const headingSize = Math.min(52, Math.max(18, parseFloat(settings.ebook_font_size_headings || 32)));
    const lineHeightContent = Math.max(1, parseFloat(settings.ebook_line_height_content || 1.8));
    const lineHeightHeadings = Math.max(1, parseFloat(settings.ebook_line_height_headings || 1.3));
    const textColor = settings.ebook_text_color || '#333333';
    const headingColor = settings.ebook_heading_color || '#111111';
    const navbarBg = 'rgba(255,255,255,0.95)';

    styleEl.textContent = `
        #ebook-preview-pane {
            background: ${previewBg} !important;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            background-attachment: ${bgType === 'image' ? 'fixed' : 'scroll'} !important;
            color: ${textColor} !important;
        }

        #ebook-page-shell {
            width: 100% !important;
            max-width: 1300px !important;
            margin: 0 auto !important;
            background: transparent !important;
            box-sizing: border-box !important;
        }

        #ebook-view-index,
        #ebook-view-chapter {
            width: 100% !important;
            min-height: 0 !important;
            background: transparent !important;
        }

        #ebook-index-panel {
            background-color: #ffffff !important;
            color: #111111 !important;
        }

        #ebook-cover-panel {
            background-color: ${settings.ebook_cover_panel_bg_color ? toRgba(settings.ebook_cover_panel_bg_color, settings.ebook_cover_panel_bg_opacity !== undefined ? settings.ebook_cover_panel_bg_opacity : 1) : 'transparent'} !important;
        }

        #ebook-chapter-navbar {
            background-color: ${navbarBg} !important;
            border-color: #f3f4f6 !important;
        }

        #ebook-chapter-nav-title {
            pointer-events: none !important;
            white-space: nowrap !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        #ebook-chapter-scroll-area {
            color: ${textColor} !important;
        }

        #ebook-chapter-content {
            font-family: ${chapterFontFamily} !important;
            font-size: ${contentSize}px !important;
            line-height: ${lineHeightContent} !important;
            text-align: ${textAlign} !important;
            hyphens: ${hyphenation} !important;
            -webkit-hyphens: ${hyphenation} !important;
            color: ${textColor} !important;
        }

        #ebook-chapter-content .reader-chapter-title {
            font-family: ${headingFontFamily} !important;
            font-size: ${headingSize}px !important;
            line-height: ${lineHeightHeadings} !important;
            font-weight: ${settings.ebook_font_weight_headings || 'bold'} !important;
            text-transform: ${chapterTitleTextTransform} !important;
            text-align: ${chapterTitleAlign} !important;
            padding-top: ${chapterTitlePaddingTop}em !important;
            padding-bottom: ${chapterTitlePaddingBottom}em !important;
            padding-left: ${chapterTitlePaddingLeft}em !important;
            padding-right: ${chapterTitlePaddingRight}em !important;
            color: ${headingColor} !important;
            margin: 0;
            width: 100%;
        }

        #ebook-chapter-content .reader-chapter-prefix {
            font-family: '${settings.ebook_chapter_prefix_font_family || 'Playfair Display'}', serif !important;
            font-size: ${settings.ebook_chapter_prefix_font_size || 16}px !important;
            font-weight: ${settings.ebook_chapter_prefix_font_weight || 'normal'} !important;
            font-style: ${settings.ebook_chapter_prefix_font_style || 'normal'} !important;
            letter-spacing: ${settings.ebook_chapter_prefix_letter_spacing || 0}px !important;
            line-height: 1.2 !important;
            text-align: ${chapterTitleAlign} !important;
            color: ${headingColor} !important;
            margin-bottom: 0.5rem;
        }

        #ebook-chapter-content .reader-chapter-prefix.prefix-below {
            margin-top: 0.5rem;
        }

        #ebook-chapter-content .reader-chapter-subtitle {
            font-family: ${headingFontFamily} !important;
            color: ${headingColor} !important;
        }

        #ebook-chapter-content .reader-chapter-ornament-line {
            width: 50px;
            height: 1px;
            background-color: ${headingColor} !important;
            margin: 0.5rem auto;
            opacity: 0.45;
        }

        #ebook-chapter-content .reader-chapter-ornament-asterisks {
            text-align: center;
            letter-spacing: 0.5em;
            color: ${headingColor} !important;
            margin: 0.5rem 0;
            opacity: 0.7;
        }

        #ebook-chapter-content .prose,
        #ebook-chapter-content.prose {
            font-family: ${chapterFontFamily} !important;
            color: ${textColor} !important;
        }

        #ebook-chapter-content .prose h1,
        #ebook-chapter-content .prose h2,
        #ebook-chapter-content .prose h3,
        #ebook-chapter-content h1,
        #ebook-chapter-content h2,
        #ebook-chapter-content h3 {
            font-family: ${headingFontFamily} !important;
            color: ${headingColor} !important;
        }

        #ebook-chapter-content .prose p.drop-cap::first-letter,
        #ebook-chapter-content p.drop-cap::first-letter {
            float: left;
            font-size: 3.5em;
            line-height: 0.85;
            margin-right: 0.1em;
            margin-top: 0.05em;
            margin-bottom: -0.1em;
            font-weight: bold;
            font-family: ${headingFontFamily} !important;
            color: ${headingColor} !important;
        }

        #ebook-chapters-list .ebook-chapter-item {
            width: 100%;
            border-bottom: 1px solid rgba(229, 231, 235, 0.9);
        }

        #ebook-chapters-list .ebook-chapter-item:last-child {
            border-bottom: 0;
        }
    `;
}

function buildEbookChapterPrefixHtml(chapter, index) {
    const state = getEbookPreviewState();
    const settings = state?.settings || {};
    if (chapter.hide_title === '1' || chapter.is_credits === '1' || chapter.is_toc === '1') {
        return '';
    }

    if (settings.ebook_chapter_prefix_show != 1) {
        return '';
    }

    if (chapter.exclude_from_numbering === '1') {
        return '';
    }

    const chapterNumber = getEbookPreviewVisibleChapterNumber(index);
    let prefixText = String(settings.ebook_chapter_prefix_template || 'Capítulo {N}').replace('{N}', chapterNumber);

    if (prefixText.includes('{R}')) {
        prefixText = prefixText.replace('{R}', toEbookPreviewRomanNumeral(chapterNumber));
    }

    let ornamentHtml = '';
    if (settings.ebook_chapter_prefix_ornament === 'line_below') {
        ornamentHtml = '<div class="reader-chapter-ornament-line"></div>';
    } else if (settings.ebook_chapter_prefix_ornament === 'line_above_below') {
        ornamentHtml = '<div class="reader-chapter-ornament-line"></div>';
        prefixText = '<div class="reader-chapter-ornament-line"></div>' + prefixText;
    } else if (settings.ebook_chapter_prefix_ornament === 'asterisks') {
        ornamentHtml = '<div class="reader-chapter-ornament-asterisks">***</div>';
    }

    const position = settings.ebook_chapter_prefix_position || 'above';
    const extraClass = position === 'below' ? ' prefix-below' : '';
    return `<div class="reader-chapter-prefix${extraClass}">${prefixText}${ornamentHtml}</div>`;
}

function buildEbookChapterSubtitleHtml(chapter) {
    const state = getEbookPreviewState();
    const settings = state?.settings || {};
    const showSubtitle = settings.ebook_subtitle_show == 1 || settings.ebook_subtitle_show === undefined;
    if (!showSubtitle || !chapter.subtitle_text || !String(chapter.subtitle_text).trim()) {
        return '';
    }

    const subtitleText = String(chapter.subtitle_text).trim().replace(/\n/g, '<br>');
    const subtitleStyles = [];

    if (settings.ebook_subtitle_font_family) subtitleStyles.push(`font-family: '${settings.ebook_subtitle_font_family}', serif !important`);
    if (settings.ebook_subtitle_font_size) subtitleStyles.push(`font-size: ${settings.ebook_subtitle_font_size}pt !important`);
    if (settings.ebook_subtitle_align) subtitleStyles.push(`text-align: ${settings.ebook_subtitle_align} !important`);
    if (settings.ebook_subtitle_font_style) subtitleStyles.push(`font-style: ${settings.ebook_subtitle_font_style} !important`);
    if (settings.ebook_subtitle_font_weight) subtitleStyles.push(`font-weight: ${settings.ebook_subtitle_font_weight} !important`);
    if (settings.ebook_subtitle_text_transform) subtitleStyles.push(`text-transform: ${settings.ebook_subtitle_text_transform} !important`);
    if (settings.ebook_subtitle_letter_spacing) subtitleStyles.push(`letter-spacing: ${settings.ebook_subtitle_letter_spacing}px !important`);
    if (settings.ebook_subtitle_padding_top !== undefined && settings.ebook_subtitle_padding_top !== '') {
        subtitleStyles.push(`padding-top: ${settings.ebook_subtitle_padding_top}em !important; margin-top: 0 !important`);
    }
    if (settings.ebook_subtitle_padding_bottom !== undefined && settings.ebook_subtitle_padding_bottom !== '') {
        subtitleStyles.push(`padding-bottom: ${settings.ebook_subtitle_padding_bottom}em !important; margin-bottom: 0 !important`);
    }

    return `<div class="reader-chapter-subtitle" style="line-height: 1.4; width: 100%; opacity: 0.85; ${subtitleStyles.join('; ')}">${subtitleText}</div>`;
}

function renderEbookMarkdown(content) {
    let processedContent = String(content || '');
    if (window.AlmadenShortcodes && typeof window.AlmadenShortcodes.parseInline === 'function') {
        processedContent = window.AlmadenShortcodes.parseInline(processedContent);
    }
    if (window.AlmadenShortcodes && typeof window.AlmadenShortcodes.parseStructural === 'function') {
        processedContent = window.AlmadenShortcodes.parseStructural(processedContent, false);
    }

    const md = getEbookMarkdownRenderer();
    const html = md ? md.render(processedContent) : `<p>${escapeEbookPreviewHtml(processedContent).replace(/\n/g, '<br>')}</p>`;
    return normalizeEbookPreviewImages(html);
}

function showEbookFootnote(event, btn, htmlContent) {
    event.stopPropagation();

    const popup = document.getElementById('ebook-footnote-popup');
    const content = document.getElementById('ebook-footnote-popup-content');
    if (!popup || !content) return;

    content.innerHTML = htmlContent;
    popup.classList.remove('hidden');

    const rect = btn.getBoundingClientRect();
    popup.style.top = `${rect.top}px`;
    popup.style.left = `${rect.left + (rect.width / 2)}px`;

    requestAnimationFrame(() => {
        popup.classList.remove('opacity-0', 'pointer-events-none');
        popup.classList.add('opacity-100');
    });
}

function hideEbookFootnotePopup() {
    const popup = document.getElementById('ebook-footnote-popup');
    if (!popup) return;
    popup.classList.add('hidden', 'opacity-0', 'pointer-events-none');
    popup.classList.remove('opacity-100');
}

function renderEbookFootnotes() {
    const chapterContent = document.getElementById('ebook-chapter-content');
    if (!chapterContent) return;

    const footnotesSection = chapterContent.querySelector('.footnotes');
    if (!footnotesSection) return;

    const footnotesMap = {};
    footnotesSection.querySelectorAll('li[id]').forEach((item) => {
        const match = String(item.id || '').match(/fn(\d+)/);
        if (!match) return;
        const backref = item.querySelector('.footnote-backref, a[href^="#fnref"]');
        if (backref) backref.remove();
        footnotesMap[match[1]] = item.innerHTML.trim();
    });

    footnotesSection.remove();
    const separator = chapterContent.querySelector('.footnotes-sep');
    if (separator) separator.remove();

    chapterContent.querySelectorAll('.footnote-ref a, sup a[href^="#fn"]').forEach((ref) => {
        const refMatch = String(ref.id || ref.getAttribute('href') || '').match(/fnref(\d+)|#fn(\d+)/);
        const id = refMatch ? (refMatch[1] || refMatch[2]) : '';
        if (!id || !footnotesMap[id]) return;

        const btn = document.createElement('button');
        btn.className = 'footnote-btn relative inline-flex items-center justify-center w-[1.125rem] h-[1.125rem] rounded-full bg-gray-200 hover:bg-gray-300 text-[10px] font-bold text-gray-700 transition-colors mx-0.5 align-super cursor-pointer select-none';
        btn.textContent = id;
        btn.onclick = (event) => showEbookFootnote(event, btn, footnotesMap[id]);
        ref.parentElement.replaceWith(btn);
    });
}

function updateEbookChapterNavigation(index) {
    const chapters = getEbookPreviewChapters();
    const visibleIndexes = getEbookPreviewVisibleChapterIndexes();
    const currentVisiblePosition = visibleIndexes.indexOf(index);
    const prevBtn = document.getElementById('ebook-btn-prev-chapter');
    const nextBtn = document.getElementById('ebook-btn-next-chapter');
    const navTitle = document.getElementById('ebook-chapter-nav-title');
    const scroller = document.getElementById('ebook-chapter-scroll-area');
    const chapter = chapters[index];

    if (navTitle && chapter) {
        navTitle.textContent = String(chapter.title || 'Capítulo');
    }

    syncEbookPreviewNavTitleVisibility();

    if (prevBtn) {
        prevBtn.classList.toggle('hidden', currentVisiblePosition <= 0);
    }
    if (nextBtn) {
        nextBtn.classList.toggle('hidden', currentVisiblePosition === -1 || currentVisiblePosition >= visibleIndexes.length - 1);
    }
}

function renderEbookIndexView() {
    applyEbookPreviewStyles();

    const pane = document.getElementById('ebook-preview-pane');
    const indexView = document.getElementById('ebook-view-index');
    const chapterView = document.getElementById('ebook-view-chapter');
    const listContainer = document.getElementById('ebook-chapters-list');
    const countLabel = document.getElementById('ebook-preview-chapter-count');
    const chapters = getEbookPreviewChapters();
    let visibleCount = 0;

    if (pane) pane.classList.remove('hidden');
    if (indexView) indexView.classList.remove('hidden');
    if (chapterView) chapterView.classList.add('hidden');
    const navTitle = document.getElementById('ebook-chapter-nav-title');
    if (navTitle) {
        navTitle.classList.remove('opacity-100');
        navTitle.classList.add('opacity-0');
    }

    hideEbookFootnotePopup();

    if (countLabel) {
        visibleCount = chapters.reduce((acc, chapter) => acc + (isEbookPreviewChapterVisible(chapter) ? 1 : 0), 0);
        countLabel.textContent = visibleCount === 1 ? '1 capítulo disponible' : `${visibleCount} capítulos disponibles`;
    }

    if (!listContainer) return;

    listContainer.innerHTML = '';
    const visibleIndexes = getEbookPreviewVisibleChapterIndexes();

    if (!visibleIndexes.length) {
        listContainer.innerHTML = '<p class="text-gray-400 italic">Este libro no tiene capítulos aún.</p>';
        return;
    }

    visibleIndexes.forEach((chapterIndex, visiblePosition) => {
        const chapter = chapters[chapterIndex];
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'ebook-chapter-item group flex w-full justify-between items-center gap-4 py-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors text-left px-4 -mx-4 rounded-md';
        item.onclick = () => showEbookChapterView(chapterIndex);
        item.innerHTML = `
            <span class="flex-1 min-w-0 text-gray-800 font-medium group-hover:text-black text-lg transition-colors">${escapeEbookPreviewHtml(chapter.title || `Capítulo ${visiblePosition + 1}`)}</span>
            <span class="shrink-0 text-gray-400 font-medium text-sm leading-none text-right">${escapeEbookPreviewHtml(chapter.page || visiblePosition + 1)}</span>
        `;
        listContainer.appendChild(item);
    });
}

function renderEbookChapterView(index, options = {}) {
    applyEbookPreviewStyles();

    const chapters = getEbookPreviewChapters();
    const chapter = chapters[index];
    if (!chapter || !isEbookPreviewChapterVisible(chapter)) {
        renderEbookIndexView();
        return;
    }

    const pane = document.getElementById('ebook-preview-pane');
    const indexView = document.getElementById('ebook-view-index');
    const chapterView = document.getElementById('ebook-view-chapter');
    const chapterContent = document.getElementById('ebook-chapter-content');
    const countLabel = document.getElementById('ebook-preview-chapter-count');

    if (pane) pane.classList.remove('hidden');
    if (indexView) indexView.classList.add('hidden');
    if (chapterView) chapterView.classList.remove('hidden');

    if (countLabel) {
        const chapterNumber = getEbookPreviewVisibleChapterNumber(index);
        countLabel.textContent = chapterNumber > 0 ? `Capítulo ${chapterNumber}` : 'Vista capítulo';
    }

    if (!chapterContent) return;

    const chapterTitle = String(chapter.title || '').trim();
    let html = renderEbookMarkdown(chapter.content || '');

    if (chapter.hide_title !== '1' && chapter.is_credits !== '1') {
        const prefixHtml = buildEbookChapterPrefixHtml(chapter, index);
        const subtitleHtml = buildEbookChapterSubtitleHtml(chapter);
        const titleHtml = `<div class="reader-chapter-title"${subtitleHtml ? ' style="padding-bottom:0!important"' : ''}>${escapeEbookPreviewHtml(chapterTitle || 'Capítulo')}</div>`;
        html = prefixHtml + titleHtml + subtitleHtml + html;
    }

    if (chapter.drop_cap_enabled === '1') {
        html = html.replace(/<p>/, '<p class="drop-cap">');
    }

    chapterContent.innerHTML = html;
    renderEbookFootnotes();
    updateEbookChapterNavigation(index);
    updateEbookChapterButtons(index);
    bindEbookPreviewScrollState();

    if (!options.preserveScroll) {
        const scroller = document.getElementById('ebook-chapter-scroll-area');
        if (scroller) scroller.scrollTop = 0;
    }

    syncEbookPreviewNavTitleVisibility();
}

function updateEbookChapterButtons(index) {
    const visibleIndexes = getEbookPreviewVisibleChapterIndexes();
    const pos = visibleIndexes.indexOf(index);
    const prevBtn = document.getElementById('ebook-btn-prev-chapter');
    const nextBtn = document.getElementById('ebook-btn-next-chapter');

    if (prevBtn) {
        prevBtn.classList.toggle('hidden', pos <= 0);
    }
    if (nextBtn) {
        nextBtn.classList.toggle('hidden', pos === -1 || pos >= visibleIndexes.length - 1);
    }
}

function refreshEbookPreview(scrollToTop = false) {
    applyEbookPreviewStyles();

    const previousScrollTop = (() => {
        const scroller = document.getElementById('ebook-chapter-scroll-area');
        return scroller ? scroller.scrollTop : 0;
    })();

    const chapters = getEbookPreviewChapters();
    if (!chapters.length) {
        renderEbookIndexView();
        return;
    }

    const currentChapter = chapters[ebookPreviewCurrentChapterIndex];
    if (ebookPreviewCurrentChapterIndex >= 0 && currentChapter && isEbookPreviewChapterVisible(currentChapter)) {
        renderEbookChapterView(ebookPreviewCurrentChapterIndex, { preserveScroll: !scrollToTop });
        if (!scrollToTop) {
            const scroller = document.getElementById('ebook-chapter-scroll-area');
            if (scroller) scroller.scrollTop = previousScrollTop;
        }
    } else {
        renderEbookIndexView();
    }

    syncEbookPreviewNavTitleVisibility();
}

function showEbookIndexView() {
    ebookPreviewCurrentChapterIndex = -1;
    renderEbookIndexView();
}

function showEbookChapterView(index) {
    const chapters = getEbookPreviewChapters();
    const chapter = chapters[index];
    if (!chapter || !isEbookPreviewChapterVisible(chapter)) {
        showEbookIndexView();
        return;
    }

    ebookPreviewCurrentChapterIndex = index;

    if (getEbookPreviewState() && String(getEbookPreviewState().activeChapterId ?? '') !== String(chapter.id ?? '')) {
        getEbookPreviewState().activeChapterId = chapter.id;
        if (typeof renderSidebar === 'function') {
            renderSidebar();
        }
    }

    renderEbookChapterView(index);

    if (typeof loadActiveChapter === 'function') {
        loadActiveChapter();
    }
}

function showEbookPrevChapter() {
    const visibleIndexes = getEbookPreviewVisibleChapterIndexes();
    const currentPosition = visibleIndexes.indexOf(ebookPreviewCurrentChapterIndex);
    if (currentPosition > 0) {
        showEbookChapterView(visibleIndexes[currentPosition - 1]);
    }
}

function showEbookNextChapter() {
    const visibleIndexes = getEbookPreviewVisibleChapterIndexes();
    const currentPosition = visibleIndexes.indexOf(ebookPreviewCurrentChapterIndex);
    if (currentPosition >= 0 && currentPosition < visibleIndexes.length - 1) {
        showEbookChapterView(visibleIndexes[currentPosition + 1]);
    }
}

window.refreshEbookPreview = refreshEbookPreview;
window.showEbookIndexView = showEbookIndexView;
window.showEbookChapterView = showEbookChapterView;
window.showEbookPrevChapter = showEbookPrevChapter;
window.showEbookNextChapter = showEbookNextChapter;
window.hideEbookFootnotePopup = hideEbookFootnotePopup;
