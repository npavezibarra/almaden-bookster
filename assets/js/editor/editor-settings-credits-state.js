function creditsNormalizeOptionalInteger(value, min, max, fallback = '') {
    const raw = String(value ?? '').trim();
    if (!raw) return fallback;
    const parsed = parseInt(raw, 10);
    if (!Number.isFinite(parsed)) return fallback;
    return Math.min(Math.max(parsed, min), max);
}

function creditsNormalizeOptionalDecimal(value, min, max, fallback = '') {
    const raw = String(value ?? '').trim();
    if (!raw) return fallback;
    const parsed = parseFloat(raw.replace(',', '.'));
    if (!Number.isFinite(parsed)) return fallback;
    return Math.min(Math.max(parsed, min), max);
}

function creditsNormalizeLogoTextTransform(value) {
    const normalized = String(value || '').trim().toLowerCase();
    return ['none', 'uppercase', 'lowercase', 'capitalize'].includes(normalized) ? normalized : 'none';
}

function creditsNormalizeVerticalAlign(value) {
    const normalized = String(value || '').trim().toLowerCase();
    return ['top', 'center', 'bottom'].includes(normalized) ? normalized : 'top';
}

function creditsNormalizeLogoFontWeight(value) {
    const normalized = String(value || '').trim();
    return ['', '300', '400', '500', '600', '700', '800'].includes(normalized) ? normalized : '';
}

function creditsGetDefaultSectionStyle() {
    return {
        show_separator: 0,
        font_family: '',
        font_size: '',
        letter_spacing: '',
        line_height: '',
        text_align: '',
        item_gap_px: '',
    };
}

function creditsGetDefaultSectionOrder() {
    return ['editorial', 'people', 'collaborators', 'logos', 'legal'];
}

function creditsGetDefaultCollaboratorsStyles() {
    return {
        title: {
            font_family: '',
            font_size: 12,
            font_weight: '700',
            line_height: 1.2,
        },
        item: {
            font_family: '',
            font_size: 10,
            font_weight: '400',
            line_height: 1.3,
        },
        image_max_width: 96,
    };
}

function creditsNormalizeTextStyleValue(value, defaults) {
    const source = value && typeof value === 'object' ? value : {};
    const fontWeight = String(source.font_weight || defaults.font_weight || '400');
    return {
        font_family: String(source.font_family || defaults.font_family || '').trim(),
        font_size: creditsNormalizeOptionalInteger(source.font_size ?? defaults.font_size, 8, 36, defaults.font_size || ''),
        font_weight: ['300', '400', '500', '600', '700', '800'].includes(fontWeight) ? fontWeight : (defaults.font_weight || '400'),
        line_height: creditsNormalizeOptionalDecimal(source.line_height ?? defaults.line_height, 0.5, 3, defaults.line_height || ''),
    };
}

function creditsNormalizeSectionStyleValue(value) {
    const defaults = creditsGetDefaultSectionStyle();
    const source = value && typeof value === 'object' ? value : {};
    const align = String(source.text_align || '').trim().toLowerCase();

    return {
        show_separator: source.show_separator === 1 || source.show_separator === '1' || source.show_separator === true ? 1 : 0,
        font_family: String(source.font_family || defaults.font_family || '').trim(),
        font_size: creditsNormalizeOptionalInteger(source.font_size ?? defaults.font_size, 8, 72, ''),
        letter_spacing: creditsNormalizeOptionalDecimal(source.letter_spacing ?? defaults.letter_spacing, -10, 20, ''),
        line_height: creditsNormalizeOptionalDecimal(source.line_height ?? defaults.line_height, 0.5, 3, ''),
        text_align: ['left', 'center', 'right'].includes(align) ? align : '',
        item_gap_px: creditsNormalizeOptionalInteger(source.item_gap_px ?? defaults.item_gap_px, 0, 80, ''),
    };
}

function creditsNormalizeCollaboratorsStylesValue(value, fallbackSectionStyle = {}) {
    const defaults = creditsGetDefaultCollaboratorsStyles();
    const source = value && typeof value === 'object' ? value : {};
    const fallback = fallbackSectionStyle && typeof fallbackSectionStyle === 'object' ? fallbackSectionStyle : {};
    return {
        title: creditsNormalizeTextStyleValue(source.title || {}, {
            font_family: fallback.font_family || defaults.title.font_family,
            font_size: fallback.font_size || defaults.title.font_size,
            font_weight: defaults.title.font_weight,
            line_height: fallback.line_height || defaults.title.line_height,
        }),
        item: creditsNormalizeTextStyleValue(source.item || {}, {
            font_family: fallback.font_family || defaults.item.font_family,
            font_size: fallback.font_size || defaults.item.font_size,
            font_weight: defaults.item.font_weight,
            line_height: fallback.line_height || defaults.item.line_height,
        }),
        image_max_width: Math.min(Math.max(parseInt(source.image_max_width ?? defaults.image_max_width, 10) || defaults.image_max_width, 60), 140),
    };
}

function creditsNormalizeLogoValue(value) {
    const source = value && typeof value === 'object' ? value : {};
    const logoUrl = String(source.logo_url || source.image_url || source.url || '').trim();
    const hasMeaningfulData = String(source.logo_source || '') === 'cover_logo'
        || String(source.logo_source || '') === 'text'
        || logoUrl
        || source.show_author_name === true
        || source.show_author_name === 1
        || source.show_author_name === '1'
        || String(source.author_font_family || '').trim()
        || String(source.author_font_size || '').trim()
        || String(source.author_font_weight || '').trim()
        || String(source.author_letter_spacing || '').trim()
        || String(source.author_gap_px || '').trim()
        || String(source.author_text_transform || '').trim()
        || String(source.title_font_family || '').trim()
        || String(source.title_font_size || '').trim()
        || String(source.title_font_weight || '').trim()
        || String(source.title_letter_spacing || '').trim()
        || String(source.title_line_height || '').trim()
        || String(source.title_text_transform || '').trim();

    if (!hasMeaningfulData) {
        return null;
    }

    return {
        logo_source: (() => {
            const normalized = String(source.logo_source || source.source_type || source.mode || 'image').trim().toLowerCase();
            return ['image', 'cover_logo', 'text'].includes(normalized) ? normalized : 'image';
        })(),
        logo_url: logoUrl,
        position: creditsNormalizeLogoPosition(source.position || source.align || 'center'),
        size_px: creditsNormalizeLogoSize(source.size_px ?? source.size ?? 120),
        show_author_name: source.show_author_name === true || source.show_author_name === 1 || source.show_author_name === '1' ? 1 : 0,
        author_font_family: String(source.author_font_family || '').trim(),
        author_font_size: creditsNormalizeOptionalInteger(source.author_font_size ?? 16, 8, 48, 16),
        author_font_weight: creditsNormalizeLogoFontWeight(source.author_font_weight || ''),
        author_letter_spacing: creditsNormalizeOptionalDecimal(source.author_letter_spacing ?? '', -10, 20, ''),
        author_gap_px: creditsNormalizeOptionalInteger(source.author_gap_px ?? 10, 0, 100, 10),
        author_text_transform: creditsNormalizeLogoTextTransform(source.author_text_transform || 'none'),
        title_font_family: String(source.title_font_family || '').trim(),
        title_font_size: creditsNormalizeOptionalInteger(source.title_font_size ?? '', 8, 72, ''),
        title_font_weight: creditsNormalizeLogoFontWeight(source.title_font_weight || ''),
        title_letter_spacing: creditsNormalizeOptionalDecimal(source.title_letter_spacing ?? '', -10, 20, ''),
        title_line_height: creditsNormalizeOptionalDecimal(source.title_line_height ?? '', 0.5, 3, ''),
        title_text_transform: creditsNormalizeLogoTextTransform(source.title_text_transform || 'none'),
    };
}

function creditsGetDefaultConfig() {
    return {
        vertical_align: 'bottom',
        editorial: {
            edition_number: '',
            publication_date: '',
            isbn: '',
            printer: '',
            blank_before: 0,
            blank_after: 0,
        },
        people: [],
        collaborators: [],
        collaborators_visible: 1,
        collaborators_title: 'Colaboradores',
        collaborators_styles: creditsGetDefaultCollaboratorsStyles(),
        logos: [{
            logo_source: 'text',
            logo_url: '',
            position: 'center',
            size_px: 120,
            show_author_name: 0,
            author_font_family: '',
            author_font_size: 16,
            author_font_weight: '',
            author_letter_spacing: '',
            author_gap_px: 10,
            author_text_transform: 'none',
            title_font_family: '',
            title_font_size: 24,
            title_font_weight: '700',
            title_letter_spacing: '',
            title_line_height: '',
            title_text_transform: 'none',
        }],
        section_order: creditsGetDefaultSectionOrder(),
        section_styles: creditsGetDefaultSectionOrder().reduce((acc, sectionId) => {
            acc[sectionId] = creditsGetDefaultSectionStyle();
            return acc;
        }, {}),
        legal: {
            copyright_text: 'Queda rigurosamente prohibida, sin la autorización escrita de los titulares del "copyright", bajo las sanciones establecidas en las leyes, la reproducción parcial o total de esta obra por cualquier medio o procedimiento.',
            license: 'all_rights_reserved',
        },
    };
}

function creditsNormalizeConfig(rawConfig) {
    const defaults = creditsGetDefaultConfig();
    let source = rawConfig || {};

    if (typeof source === 'string') {
        try {
            source = JSON.parse(source);
        } catch (error) {
            source = {};
        }
    }

    if (!source || typeof source !== 'object') {
        source = {};
    }

    const editorialSource = source.editorial && typeof source.editorial === 'object' ? source.editorial : source;
    const legalSource = source.legal && typeof source.legal === 'object' ? source.legal : source;

    let peopleSource = [];
    if (Array.isArray(source.people)) {
        peopleSource = source.people;
    } else if (Array.isArray(source.credits_custom)) {
        peopleSource = source.credits_custom;
    } else if (typeof source.credits_custom === 'string' && source.credits_custom.trim() !== '') {
        try {
            const decoded = JSON.parse(source.credits_custom);
            if (Array.isArray(decoded)) {
                peopleSource = decoded;
            }
        } catch (error) {
            peopleSource = [];
        }
    }
    const collaboratorsSource = Array.isArray(source.collaborators) ? source.collaborators : [];
    const collaboratorsVisibleSource = source.collaborators_visible ?? source.credits_collaborators_visible ?? defaults.collaborators_visible;
    const logosSource = Array.isArray(source.logos) ? source.logos : [];
    const sectionOrderSource = Array.isArray(source.section_order)
        ? source.section_order
        : (Array.isArray(source.credits_section_order) ? source.credits_section_order : []);
    const sectionStylesSource = source.section_styles && typeof source.section_styles === 'object'
        ? source.section_styles
        : (source.credits_section_styles && typeof source.credits_section_styles === 'object' ? source.credits_section_styles : {});
    const collaboratorsTitleSource = String(source.collaborators_title || source.credits_collaborators_title || defaults.collaborators_title || '').trim();
    const collaboratorsStylesSource = source.collaborators_styles && typeof source.collaborators_styles === 'object'
        ? source.collaborators_styles
        : (source.credits_collaborators_styles && typeof source.credits_collaborators_styles === 'object' ? source.credits_collaborators_styles : {});
    const verticalAlignSource = source.vertical_align || source.credits_vertical_align || defaults.vertical_align;

    const config = JSON.parse(JSON.stringify(defaults));
    config.editorial.edition_number = String(editorialSource.edition_number || source.credits_edition || '').trim();
    config.editorial.publication_date = creditsNormalizePublicationDate(editorialSource.publication_date || source.credits_date || '');
    config.editorial.isbn = String(editorialSource.isbn || source.credits_isbn || '').trim();
    config.editorial.printer = String(editorialSource.printer || source.credits_printer || '').trim();
    config.editorial.blank_before = creditsNormalizeOptionalInteger(editorialSource.blank_before ?? source.credits_blank_before ?? 0, 0, 999, 0);
    config.editorial.blank_after = creditsNormalizeOptionalInteger(editorialSource.blank_after ?? source.credits_blank_after ?? 0, 0, 999, 0);

    config.people = peopleSource
        .map((item) => {
            if (!item || typeof item !== 'object') return null;
            const name = String(item.name || '').trim();
            const role = CREDITS_ROLE_OPTIONS.some((opt) => opt.value === String(item.role || 'author'))
                ? String(item.role || 'author')
                : 'author';
            const customRoleTitle = role === 'other' ? String(item.custom_role_title || '').trim() : '';
            const email = String(item.email || '').trim();
            const website = String(item.website || '').trim();
            const showContact = item.show_contact === true || item.show_contact === 1 || item.show_contact === '1';
            if (!name && !email && !website && !customRoleTitle) return null;
            return {
                name,
                role,
                custom_role_title: customRoleTitle,
                email,
                website,
                show_contact: showContact ? 1 : 0,
            };
        })
        .filter(Boolean);

    config.collaborators = collaboratorsSource
        .map((item) => {
            if (!item || typeof item !== 'object') return null;
            const logoUrl = String(item.logo_url || item.image_url || '').trim();
            const name = String(item.name || item.company_name || '').trim();
            const type = CREDITS_COMPANY_TYPE_OPTIONS.some((opt) => opt.value === String(item.type || 'company'))
                ? String(item.type || 'company')
                : 'company';
            const website = String(item.website || '').trim();
            const text = String(item.text || item.optional_text || '').trim();
            if (!logoUrl && !name && !website && !text) return null;
            return {
                logo_url: logoUrl,
                name,
                type,
                website,
                text,
            };
        })
        .filter(Boolean);
    config.collaborators_visible = collaboratorsVisibleSource === 1 || collaboratorsVisibleSource === '1' || collaboratorsVisibleSource === true ? 1 : 0;

    const logoRows = logosSource.length ? logosSource : [creditsNormalizeLogoValue({
        logo_source: source.credits_logo_source,
        logo_url: source.credits_logo_url || source.credits_logo_image_url || '',
        position: source.credits_logo_position,
        size_px: source.credits_logo_size_px,
        show_author_name: source.credits_logo_show_author_name,
        author_font_family: source.credits_logo_author_font_family,
        author_font_size: source.credits_logo_author_font_size,
        author_font_weight: source.credits_logo_author_font_weight,
        author_letter_spacing: source.credits_logo_author_letter_spacing,
        author_gap_px: source.credits_logo_author_gap_px,
        author_text_transform: source.credits_logo_author_text_transform,
    })].filter(Boolean);

    config.logos = logoRows
        .map((item) => {
            const normalizedLogo = creditsNormalizeLogoValue(item);
            if (!normalizedLogo) return null;
            return normalizedLogo;
        })
        .filter(Boolean);

    config.legal.copyright_text = String(legalSource.copyright_text || source.credits_copyright || defaults.legal.copyright_text).trim();
    config.legal.license = CREDITS_LICENSE_OPTIONS.some((opt) => opt.value === String(legalSource.license || source.credits_license || defaults.legal.license))
        ? String(legalSource.license || source.credits_license || defaults.legal.license)
        : defaults.legal.license;
    config.collaborators_title = collaboratorsTitleSource || defaults.collaborators_title;
    config.collaborators_styles = creditsNormalizeCollaboratorsStylesValue(collaboratorsStylesSource, config.section_styles.collaborators || defaults.section_styles.collaborators || {});
    config.vertical_align = creditsNormalizeVerticalAlign(verticalAlignSource);
    config.section_order = creditsGetDefaultSectionOrder().filter((sectionId) => true);
    if (Array.isArray(sectionOrderSource) && sectionOrderSource.length) {
        const normalizedOrder = [];
        sectionOrderSource.forEach((item) => {
            const sectionId = String(item || '').trim();
            if (creditsGetDefaultSectionOrder().includes(sectionId) && !normalizedOrder.includes(sectionId)) {
                normalizedOrder.push(sectionId);
            }
        });
        creditsGetDefaultSectionOrder().forEach((sectionId) => {
            if (!normalizedOrder.includes(sectionId)) {
                normalizedOrder.push(sectionId);
            }
        });
        config.section_order = normalizedOrder;
    }
    const sectionStyleDefaults = defaults.section_styles || {};
    const normalizedSectionStyles = {};
    creditsGetDefaultSectionOrder().forEach((sectionId) => {
        normalizedSectionStyles[sectionId] = creditsNormalizeSectionStyleValue(sectionStylesSource[sectionId] || sectionStyleDefaults[sectionId] || {});
    });
    config.section_styles = normalizedSectionStyles;

    return config;
}

function creditsConfigToLegacy(config) {
    const normalized = creditsNormalizeConfig(config);
    const firstLogo = Array.isArray(normalized.logos) && normalized.logos.length ? normalized.logos[0] : {};
    return {
        credits_edition: normalized.editorial.edition_number || '',
        credits_date: normalized.editorial.publication_date || '',
        credits_isbn: normalized.editorial.isbn || '',
        credits_copyright: normalized.legal.copyright_text || '',
        credits_printer: normalized.editorial.printer || '',
        credits_blank_before: parseInt(normalized.editorial.blank_before || 0, 10) || 0,
        credits_blank_after: parseInt(normalized.editorial.blank_after || 0, 10) || 0,
        credits_license: normalized.legal.license || 'all_rights_reserved',
        credits_custom: JSON.stringify(
            normalized.people.map((person) => ({
                role: person.role || '',
                name: person.name || '',
                custom_role_title: person.custom_role_title || '',
            }))
        ),
        credits_collaborators_visible: normalized.collaborators_visible ? 1 : 0,
        credits_collaborators_title: normalized.collaborators_title || '',
        credits_collaborators_styles: JSON.stringify(normalized.collaborators_styles || {}),
        credits_section_order: JSON.stringify(normalized.section_order || []),
        credits_section_styles: JSON.stringify(normalized.section_styles || {}),
        credits_vertical_align: normalized.vertical_align || 'bottom',
        credits_logo_source: firstLogo.logo_source || 'image',
        credits_logo_url: firstLogo.logo_url || '',
        credits_logo_position: firstLogo.position || 'center',
        credits_logo_size_px: parseInt(firstLogo.size_px || 120, 10) || 120,
        credits_logo_show_author_name: firstLogo.show_author_name ? 1 : 0,
        credits_logo_author_font_family: firstLogo.author_font_family || '',
        credits_logo_author_font_size: parseInt(firstLogo.author_font_size || 16, 10) || 16,
        credits_logo_author_font_weight: firstLogo.author_font_weight || '',
        credits_logo_author_letter_spacing: firstLogo.author_letter_spacing === '' || firstLogo.author_letter_spacing === null || typeof firstLogo.author_letter_spacing === 'undefined'
            ? ''
            : Number(firstLogo.author_letter_spacing),
        credits_logo_author_gap_px: String(firstLogo.author_gap_px ?? '').trim() === ''
            ? 10
            : Math.max(0, Math.min(100, parseInt(firstLogo.author_gap_px, 10) || 0)),
        credits_logo_author_text_transform: firstLogo.author_text_transform || 'none',
        credits_logo_title_font_family: firstLogo.title_font_family || '',
        credits_logo_title_font_size: parseInt(firstLogo.title_font_size || '', 10) || '',
        credits_logo_title_font_weight: firstLogo.title_font_weight || '',
        credits_logo_title_letter_spacing: firstLogo.title_letter_spacing === '' || firstLogo.title_letter_spacing === null || typeof firstLogo.title_letter_spacing === 'undefined'
            ? ''
            : Number(firstLogo.title_letter_spacing),
        credits_logo_title_line_height: firstLogo.title_line_height === '' || firstLogo.title_line_height === null || typeof firstLogo.title_line_height === 'undefined'
            ? ''
            : Number(firstLogo.title_line_height),
        credits_logo_title_text_transform: firstLogo.title_text_transform || 'none',
    };
}
