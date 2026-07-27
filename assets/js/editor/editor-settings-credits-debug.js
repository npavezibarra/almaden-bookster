(function() {
    const entries = [];
    let sequence = 0;

    function summarize(config) {
        const source = config && typeof config === 'object' ? config : {};
        const logos = Array.isArray(source.logos) ? source.logos : [];
        const firstLogo = logos.length > 0 ? (logos[0] && typeof logos[0] === 'object' ? logos[0] : {}) : {};

        return {
            people_count: Array.isArray(source.people) ? source.people.length : 0,
            collaborators_count: Array.isArray(source.collaborators) ? source.collaborators.length : 0,
            collaborators_visible: source.collaborators_visible === 1 || source.collaborators_visible === '1' || source.collaborators_visible === true,
            collaborators_title: String(source.collaborators_title || ''),
            collaborators_styles: source.collaborators_styles && typeof source.collaborators_styles === 'object'
                ? {
                    title: source.collaborators_styles.title && typeof source.collaborators_styles.title === 'object'
                        ? {
                            font_family: String(source.collaborators_styles.title.font_family || ''),
                            font_size: String(source.collaborators_styles.title.font_size || ''),
                            font_weight: String(source.collaborators_styles.title.font_weight || ''),
                            line_height: String(source.collaborators_styles.title.line_height || ''),
                        }
                        : {},
                    item: source.collaborators_styles.item && typeof source.collaborators_styles.item === 'object'
                        ? {
                            font_family: String(source.collaborators_styles.item.font_family || ''),
                            font_size: String(source.collaborators_styles.item.font_size || ''),
                            font_weight: String(source.collaborators_styles.item.font_weight || ''),
                            line_height: String(source.collaborators_styles.item.line_height || ''),
                        }
                        : {},
                    image_max_width: String(source.collaborators_styles.image_max_width || ''),
                }
                : {},
            logo_count: logos.length,
            logo_source: String(firstLogo.logo_source || 'image'),
            logo_urls: logos.map((logo) => String(logo && logo.logo_url ? logo.logo_url : '')),
            logo_show_author_name: firstLogo.show_author_name === 1 || firstLogo.show_author_name === '1' || firstLogo.show_author_name === true,
            logo_author_font_family: String(firstLogo.author_font_family || ''),
            logo_author_font_size: String(firstLogo.author_font_size || ''),
            logo_author_font_weight: String(firstLogo.author_font_weight || ''),
            logo_author_letter_spacing: firstLogo.author_letter_spacing === '' || firstLogo.author_letter_spacing === null || typeof firstLogo.author_letter_spacing === 'undefined'
                ? ''
                : String(firstLogo.author_letter_spacing),
            logo_author_gap_px: firstLogo.author_gap_px === '' || firstLogo.author_gap_px === null || typeof firstLogo.author_gap_px === 'undefined'
                ? ''
                : String(firstLogo.author_gap_px),
            logo_author_text_transform: String(firstLogo.author_text_transform || 'none'),
            section_order: Array.isArray(source.section_order) ? source.section_order.slice() : [],
            section_styles: source.section_styles && typeof source.section_styles === 'object'
                ? Object.keys(source.section_styles).reduce((acc, key) => {
                    const style = source.section_styles[key] && typeof source.section_styles[key] === 'object' ? source.section_styles[key] : {};
                    acc[key] = {
                        show_separator: style.show_separator === 1 || style.show_separator === '1' || style.show_separator === true,
                        font_family: String(style.font_family || ''),
                        font_size: String(style.font_size || ''),
                        letter_spacing: String(style.letter_spacing || ''),
                        line_height: String(style.line_height || ''),
                        text_align: String(style.text_align || ''),
                    };
                    return acc;
                }, {})
                : {},
            edition_number: String(source.editorial && source.editorial.edition_number ? source.editorial.edition_number : ''),
        };
    }

    function createTraceId(bookId) {
        sequence += 1;
        return `credits-${String(bookId || 'unknown')}-${Date.now()}-${sequence}`;
    }

    function log(event, details) {
        const entry = {
            timestamp: new Date().toISOString(),
            event: String(event || 'unknown'),
            details: details && typeof details === 'object' ? details : {},
        };

        entries.push(entry);
        if (entries.length > 100) {
            entries.shift();
        }

        console.info('[Almaden Credits]', entry.event, entry);
        return entry;
    }

    window.almadenCreditsDebug = {
        createTraceId,
        getReport: function() {
            return entries.slice();
        },
        log,
        summarize,
    };
})();
