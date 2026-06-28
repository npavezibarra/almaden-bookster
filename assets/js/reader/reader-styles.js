// reader-styles.js

// Generate dynamic CSS for Chapter Titles
function generateDynamicStyles() {
    const settings = bookData.settings || {};
    let css = '';
    let readerBgColor = '#ffffff';
    let readerTextColor = '#333';
    let readerHeadingColor = '#111';

    // 1. Reading Page (Chapter View) Styles from User Prefs
    if (userPrefs.theme === 'beige') {
        readerBgColor = '#F4F3EB';
    } else if (userPrefs.theme === 'black') {
        readerBgColor = '#111111';
        readerTextColor = '#dddddd';
        readerHeadingColor = '#ffffff';
    }

    // 2. Landing Page (Index View) Global Styles from Admin Settings
    const bodyBgType = settings.ebook_bg_type || 'color';
    let bodyBgCSS = '';
    const bodyBgOpacity = settings.ebook_bg_opacity !== undefined ? settings.ebook_bg_opacity : 1.0;

    const hexToRgba = (hex, opacity) => {
        if (!hex || typeof hex !== 'string' || !hex.startsWith('#')) return hex;
        let c = hex.replace('#', '');
        if (c.length === 3) c = c.split('').map(ch => ch + ch).join('');
        const r = parseInt(c.substring(0,2), 16) || 0;
        const g = parseInt(c.substring(2,4), 16) || 0;
        const b = parseInt(c.substring(4,6), 16) || 0;
        return `rgba(${r}, ${g}, ${b}, ${opacity})`;
    };

    if (bodyBgType === 'color') {
        const color = settings.ebook_bg_color || '#ffffff';
        bodyBgCSS = `background-color: ${hexToRgba(color, bodyBgOpacity)} !important;`;
    } else if (bodyBgType === 'image' && settings.ebook_bg_image) {
        // En lugar de ::before, usamos un linear-gradient para oscurecer/aclarar la imagen según la opacidad.
        // Por defecto oscurecemos (negro) para que el texto sea legible.
        const overlayAlpha = 1.0 - bodyBgOpacity;
        const overlayColor = `rgba(0, 0, 0, ${overlayAlpha})`;
        
        bodyBgCSS = `background-image: linear-gradient(${overlayColor}, ${overlayColor}), url('${settings.ebook_bg_image}') !important;
                     background-size: cover !important;
                     background-attachment: fixed !important;
                     background-position: center !important;
                     background-repeat: no-repeat !important;`;
    }
    
    css += `
    body {
        ${bodyBgCSS}
        color: ${readerTextColor} !important;
    }
    
    #reader-index-panel {
        background-color: #ffffff !important;
        color: #111111 !important;
    }
    
    #view-chapter {
        background-color: ${readerBgColor} !important;
    }`;

    css += `
    #reading-progress-bar {
        background-color: ${readerHeadingColor} !important;
        opacity: 0.2;
    }`;

    // ... Cover styling ...
    const coverBgType = settings.ebook_cover_panel_bg_type || 'image';
    const coverBgOpacity = settings.ebook_cover_panel_bg_opacity !== undefined ? settings.ebook_cover_panel_bg_opacity : 1.0;

    if (coverBgType === 'color') {
        const coverColor = settings.ebook_cover_panel_bg_color || 'transparent';
        css += `
        #reader-cover-panel {
            background-color: ${hexToRgba(coverColor, coverBgOpacity)} !important;
        }`;
    } else {
        const coverImage = settings.ebook_cover_panel_bg_image || bookData.cover_url || '';
        if (coverImage) {
            const overlayAlpha = 1.0 - coverBgOpacity;
            const overlayColor = `rgba(0, 0, 0, ${overlayAlpha})`;
            css += `
            #reader-cover-panel {
                background-image: linear-gradient(${overlayColor}, ${overlayColor}), url('${coverImage}') !important;
                background-size: cover !important;
                background-position: center !important;
                background-repeat: no-repeat !important;
            }`;
        }
    }

    // Typography overrides
    const sizeOffset = userPrefs.fontSizeOffset || 0;
    const lhOffset = userPrefs.lineHeightOffset || 0;

    // The reader controls only scale the chapter body. Metadata such as title,
    // subtitle and prefix keep the book's configured sizing.
    const baseSizeChapterTitle = Math.min(52, Math.max(18, parseFloat(settings.ebook_font_size_headings || 32.0)));
    const baseSizeContentHeading = Math.min(52, Math.max(18, parseFloat(settings.ebook_font_size_headings || 32.0) + (sizeOffset * 4)));
    const baseSizeContent = Math.min(52, Math.max(18, parseFloat(settings.ebook_font_size_content || 18.0) + (sizeOffset * 2)));

    const baseLhChapterTitle = Math.max(1.0, parseFloat(settings.ebook_line_height_headings || 1.3));
    const baseLhContentHeading = Math.max(1.0, parseFloat(settings.ebook_line_height_headings || 1.3) + lhOffset);
    const baseLhContent = Math.max(1.0, parseFloat(settings.ebook_line_height_content || 1.8) + lhOffset);
    
    let ffHeading = `'${settings.ebook_font_family_headings || 'Playfair Display'}', serif`;
    let ffContent = `'${settings.ebook_font_family_content || 'Merriweather'}', Georgia, serif`;
    


    css += `
        .reader-chapter-title {
            font-family: ${ffHeading} !important;
            font-size: ${baseSizeChapterTitle}px !important;
            font-weight: ${settings.ebook_font_weight_headings || 'bold'} !important;
            line-height: ${settings.ebook_chapter_title_line_height !== undefined ? settings.ebook_chapter_title_line_height : baseLhChapterTitle} !important;
            text-transform: ${settings.ebook_chapter_title_text_transform || 'none'} !important;
            text-align: ${settings.ebook_chapter_title_align || 'center'} !important;
            padding-top: ${settings.ebook_chapter_title_padding_top !== undefined ? settings.ebook_chapter_title_padding_top : 2}em !important;
            padding-bottom: ${settings.ebook_chapter_title_padding_bottom !== undefined ? settings.ebook_chapter_title_padding_bottom : 2}em !important;
            padding-left: ${settings.ebook_chapter_title_padding_left !== undefined ? settings.ebook_chapter_title_padding_left : 0}em !important;
            padding-right: ${settings.ebook_chapter_title_padding_right !== undefined ? settings.ebook_chapter_title_padding_right : 0}em !important;
            margin: 0;
            width: 100%;
            color: ${readerHeadingColor} !important;
        }

        .reader-chapter-prefix {
            font-family: '${settings.ebook_chapter_prefix_font_family || 'Playfair Display'}', serif !important;
            font-size: ${settings.ebook_chapter_prefix_font_size || 16}px !important;
            font-weight: ${settings.ebook_chapter_prefix_font_weight || 'normal'} !important;
            font-style: ${settings.ebook_chapter_prefix_font_style || 'normal'} !important;
            letter-spacing: ${settings.ebook_chapter_prefix_letter_spacing || 0}px !important;
            line-height: 1.2 !important;
            text-align: ${settings.ebook_chapter_title_align || 'center'} !important;
            margin-bottom: 0.5rem;
            color: ${readerHeadingColor} !important;
        }
        
        .reader-chapter-prefix.prefix-below {
            margin-top: 0.5rem;
            margin-bottom: 0;
        }

        .reader-chapter-ornament-line {
            width: 50px;
            height: 1px;
            background-color: ${readerHeadingColor} !important;
            margin: 0.5rem auto;
            opacity: 0.5;
        }
        
        .reader-chapter-ornament-asterisks {
            text-align: center;
            letter-spacing: 0.5em;
            color: ${readerHeadingColor} !important;
            margin: 0.5rem 0;
            opacity: 0.7;
        }
    `;
    const textAlign = (settings.ebook_text_align_justify == 1) ? 'justify' : 'left';
    const hyphens = (settings.ebook_hyphenation == 1) ? 'auto' : 'none';

    css += `
        .prose {
            font-family: ${ffContent} !important;
            font-size: ${baseSizeContent}px !important;
            font-weight: ${settings.ebook_font_weight_content || 'normal'} !important;
            line-height: ${baseLhContent} !important;
            text-align: ${textAlign} !important;
            hyphens: ${hyphens} !important;
            -webkit-hyphens: ${hyphens} !important;
            color: ${readerTextColor} !important;
        }
        .prose h1, .prose h2, .prose h3 {
            font-family: ${ffHeading} !important;
            font-size: ${baseSizeContentHeading}px !important;
            font-weight: ${settings.ebook_font_weight_headings || 'bold'} !important;
            line-height: ${baseLhContentHeading} !important;
            color: ${readerHeadingColor} !important;
        }
        
        .prose p.drop-cap::first-letter {
            float: left;
            font-size: 3.5em;
            line-height: 0.85;
            margin-right: 0.1em;
            margin-top: 0.05em;
            margin-bottom: -0.1em;
            font-weight: bold;
            font-family: ${ffHeading} !important;
            color: ${readerHeadingColor} !important;
        }
        /* Theme Overrides for Tailwind Utility Classes */
        #view-index {
            background-color: transparent !important;
        }
        
        #chapter-navbar {
            background-color: ${userPrefs.theme === 'black' ? 'rgba(17,17,17,0.95)' : (userPrefs.theme === 'beige' ? 'rgba(244,243,235,0.95)' : 'rgba(255,255,255,0.95)')} !important;
            border-color: ${userPrefs.theme === 'black' ? '#333' : '#f3f4f6'} !important;
        }
        
        #reader-prefs-panel, #footnote-popup {
            background-color: ${readerBgColor} !important;
            border-color: ${userPrefs.theme === 'black' ? '#333' : '#e5e7eb'} !important;
            color: ${readerTextColor} !important;
        }

        /* Inner popover buttons and lines */
        #reader-prefs-panel .bg-gray-100 {
            background-color: ${userPrefs.theme === 'black' ? '#222' : (userPrefs.theme === 'beige' ? '#e8e6d9' : '#f3f4f6')} !important;
        }
        #reader-prefs-panel .bg-gray-300 {
            background-color: ${userPrefs.theme === 'black' ? '#444' : '#d1d5db'} !important;
        }
        #reader-prefs-panel button:hover {
            background-color: ${userPrefs.theme === 'black' ? '#333' : (userPrefs.theme === 'beige' ? '#f4f3eb' : '#fff')} !important;
        }
        #reader-prefs-panel .border-t {
            border-color: ${userPrefs.theme === 'black' ? '#333' : '#f3f4f6'} !important;
        }

        /* Footnotes */
        .footnote-ref a {
            color: ${readerHeadingColor} !important;
            text-decoration: none;
            font-weight: bold;
        }
        
        .footnote-ref a:hover {
            text-decoration: underline;
        }

        .footnote-btn {
            color: ${readerHeadingColor} !important;
        }

        #btn-mode-scroll.bg-gray-100, #btn-mode-flip.bg-gray-100 {
            background-color: ${userPrefs.theme === 'black' ? '#333' : (userPrefs.theme === 'beige' ? '#e8e6d9' : '#f3f4f6')} !important;
        }
        
        #btn-reader-prefs, #btn-mode-scroll, #btn-mode-flip, #chapter-nav-title {
            color: ${readerHeadingColor} !important;
        }

        .footnote-btn {
            background-color: ${userPrefs.theme === 'black' ? '#333' : '#e5e7eb'} !important;
            color: ${readerHeadingColor} !important;
        }
        
        .footnote-btn:hover {
            background-color: ${userPrefs.theme === 'black' ? '#444' : '#d1d5db'} !important;
        }
        
        #chapters-list > div:hover {
            background-color: #f9fafb !important;
        }
    `;
    
    // Check if we need to update an existing style block or create a new one
    let styleEl = document.getElementById('dynamic-reader-styles');
    if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'dynamic-reader-styles';
        document.head.appendChild(styleEl);
    }
    styleEl.innerHTML = css;
}

// Call it on load
generateDynamicStyles();
