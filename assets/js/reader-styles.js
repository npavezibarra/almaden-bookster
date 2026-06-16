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
    if (bodyBgType === 'color') {
        bodyBgCSS = `background-color: ${settings.ebook_bg_color || '#ffffff'} !important;`;
    } else if (bodyBgType === 'image' && settings.ebook_bg_image) {
        bodyBgCSS = `background-image: url('${settings.ebook_bg_image}') !important; background-size: cover !important; background-attachment: fixed !important; background-position: center !important; background-repeat: no-repeat !important;`;
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
    if (coverBgType === 'color') {
        const coverColor = settings.ebook_cover_panel_bg_color || 'transparent';
        css += `
        #reader-cover-panel {
            background-color: ${coverColor} !important;
        }`;
    } else {
        const coverImage = settings.ebook_cover_panel_bg_image || bookData.cover_url || '';
        if (coverImage) {
            css += `
            #reader-cover-panel {
                background-image: url('${coverImage}') !important;
                background-size: cover !important;
                background-position: center !important;
                background-repeat: no-repeat !important;
            }`;
        }
    }

    // Typography overrides
    const sizeOffset = userPrefs.fontSizeOffset || 0;
    const lhOffset = userPrefs.lineHeightOffset || 0;
    
    let calculatedHeading = parseFloat(settings.ebook_font_size_headings || 32.0) + (sizeOffset * 4);
    let calculatedContent = parseFloat(settings.ebook_font_size_content || 18.0) + (sizeOffset * 2);
    
    const baseSizeHeading = Math.min(52, Math.max(18, calculatedHeading));
    const baseSizeContent = Math.min(52, Math.max(18, calculatedContent));
    
    const baseLhHeading = Math.max(1.0, parseFloat(settings.ebook_line_height_headings || 1.3) + lhOffset);
    const baseLhContent = Math.max(1.0, parseFloat(settings.ebook_line_height_content || 1.8) + lhOffset);
    
    let ffHeading = `'${settings.ebook_font_family_headings || 'Playfair Display'}', serif`;
    let ffContent = `'${settings.ebook_font_family_content || 'Merriweather'}', Georgia, serif`;
    
    if (userPrefs.fontFamily === 'sans-serif') {
        ffHeading = "'Inter', sans-serif";
        ffContent = "'Inter', sans-serif";
    } else if (userPrefs.fontFamily === 'serif') {
        ffHeading = "Georgia, serif";
        ffContent = "Georgia, serif";
    }

    css += `
        .reader-chapter-title {
            font-family: ${ffHeading} !important;
            font-size: ${baseSizeHeading}px !important;
            font-weight: ${settings.ebook_font_weight_headings || 'bold'} !important;
            line-height: ${baseLhHeading} !important;
            text-transform: ${settings.chapter_title_text_transform || 'none'};
            text-align: ${settings.chapter_title_align || 'center'};
            padding-top: ${settings.chapter_title_padding_top || 0.0}cm;
            padding-bottom: ${settings.chapter_title_padding_bottom || 1.5}cm;
            padding-left: ${settings.chapter_title_padding_left || 0.0}cm;
            padding-right: ${settings.chapter_title_padding_right || 0.0}cm;
            margin: 0;
            width: 100%;
            color: ${readerHeadingColor} !important;
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
            font-size: ${baseSizeHeading}px !important;
            font-weight: ${settings.ebook_font_weight_headings || 'bold'} !important;
            line-height: ${baseLhHeading} !important;
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
        
        #btn-reader-prefs, #btn-mode-scroll, #btn-mode-flip, #chapter-nav-title, #reader-book-title {
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
            background-color: ${userPrefs.theme === 'black' ? '#222' : (userPrefs.theme === 'beige' ? '#e8e6d9' : '#f9fafb')} !important;
        }
        
        #chapters-list span {
            color: ${readerTextColor} !important;
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
