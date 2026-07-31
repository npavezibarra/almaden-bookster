window.isCreditsChapter = function(chapter) {
    if (!chapter || typeof chapter !== 'object') return false;
    const chapterTitle = String(chapter.title || '').trim().toLocaleLowerCase('es');
    const chapterContent = String(chapter.content || '').replace(/<[^>]+>/g, ' ').trim();
    const isGeneratedCreditsPlaceholder = /^en este cap[ií]tulo se generar[aá] autom[aá]ticamente la p[aá]gina de cr[eé]ditos\.?$/i.test(chapterContent);
    return String(chapter.is_credits ?? '') === '1'
        || chapter.is_credits === true
        || (chapterTitle === 'créditos' && isGeneratedCreditsPlaceholder);
};

window.buildChapterHTML = function(chapter, index, settings, bookState, options = {}) {
    let compiledHtml = '';
    const includeOpeningBlock = options.includeOpeningBlock !== false;
    const openingVariant = options.openingVariant || 'standard';
    const isCreditsChapter = window.isCreditsChapter(chapter);
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
    } else if (isCreditsChapter) {
        const defaultCreditsConfig = typeof window.almadenGetCreditsConfigDefaults === 'function'
            ? window.almadenGetCreditsConfigDefaults()
            : {
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
                collaborators_styles: {
                    title: { font_family: '', font_size: 12, font_weight: '700', line_height: 1.2 },
                    item: { font_family: '', font_size: 10, font_weight: '400', line_height: 1.3 },
                    image_max_width: 96,
                },
                logos: [],
                section_order: ['editorial', 'people', 'collaborators', 'logos', 'legal'],
                section_styles: {
                    editorial: { show_separator: 0, font_family: '', font_size: '', letter_spacing: '', line_height: '', text_align: '' },
                    people: { show_separator: 0, font_family: '', font_size: '', letter_spacing: '', line_height: '', text_align: '' },
                    collaborators: { show_separator: 0, font_family: '', font_size: '', letter_spacing: '', line_height: '', text_align: '' },
                    logos: { show_separator: 0, font_family: '', font_size: '', letter_spacing: '', line_height: '', text_align: '' },
                    legal: { show_separator: 0, font_family: '', font_size: '', letter_spacing: '', line_height: '', text_align: '' },
                },
                legal: {
                    copyright_text: 'Queda rigurosamente prohibida, sin la autorización escrita de los titulares del "copyright", bajo las sanciones establecidas en las leyes, la reproducción parcial o total de esta obra por cualquier medio o procedimiento.',
                    license: 'all_rights_reserved',
                }
            };
        // Credits are book-level structured data. Never use the chapter's
        // placeholder body as a source for this generated page.
        const creditsSource = settings.credits_config
            || (bookState && bookState.settings && bookState.settings.credits_config)
            || settings
            || {};
        let rawCreditsSource = creditsSource;
        if (typeof rawCreditsSource === 'string') {
            try {
                rawCreditsSource = JSON.parse(rawCreditsSource);
            } catch (error) {
                rawCreditsSource = {};
            }
        }
        if (!rawCreditsSource || typeof rawCreditsSource !== 'object') {
            rawCreditsSource = {};
        }
        const creditsConfig = typeof window.almadenNormalizeCreditsConfig === 'function'
            ? window.almadenNormalizeCreditsConfig(creditsSource)
            : {
                ...defaultCreditsConfig,
                editorial: {
                    ...defaultCreditsConfig.editorial,
                    ...(rawCreditsSource.editorial && typeof rawCreditsSource.editorial === 'object' ? rawCreditsSource.editorial : {}),
                    edition_number: rawCreditsSource.editorial?.edition_number || rawCreditsSource.credits_edition || '',
                    publication_date: rawCreditsSource.editorial?.publication_date || rawCreditsSource.credits_date || '',
                    isbn: rawCreditsSource.editorial?.isbn || rawCreditsSource.credits_isbn || '',
                    printer: rawCreditsSource.editorial?.printer || rawCreditsSource.credits_printer || '',
                    blank_before: rawCreditsSource.editorial?.blank_before ?? rawCreditsSource.credits_blank_before ?? 0,
                    blank_after: rawCreditsSource.editorial?.blank_after ?? rawCreditsSource.credits_blank_after ?? 0,
                },
                people: Array.isArray(rawCreditsSource.people) ? rawCreditsSource.people : [],
                collaborators: Array.isArray(rawCreditsSource.collaborators) ? rawCreditsSource.collaborators : [],
                collaborators_visible: rawCreditsSource.collaborators_visible ?? rawCreditsSource.credits_collaborators_visible ?? defaultCreditsConfig.collaborators_visible,
                collaborators_title: rawCreditsSource.collaborators_title || rawCreditsSource.credits_collaborators_title || defaultCreditsConfig.collaborators_title,
                collaborators_styles: {
                    ...defaultCreditsConfig.collaborators_styles,
                    ...(rawCreditsSource.collaborators_styles && typeof rawCreditsSource.collaborators_styles === 'object' ? rawCreditsSource.collaborators_styles : {}),
                },
                logos: Array.isArray(rawCreditsSource.logos) ? rawCreditsSource.logos : [],
                section_order: Array.isArray(rawCreditsSource.section_order) ? rawCreditsSource.section_order : defaultCreditsConfig.section_order.slice(),
                section_styles: {
                    ...defaultCreditsConfig.section_styles,
                    ...(rawCreditsSource.section_styles && typeof rawCreditsSource.section_styles === 'object' ? rawCreditsSource.section_styles : {}),
                },
                legal: {
                    ...defaultCreditsConfig.legal,
                    ...(rawCreditsSource.legal && typeof rawCreditsSource.legal === 'object' ? rawCreditsSource.legal : {}),
                    copyright_text: rawCreditsSource.legal?.copyright_text || rawCreditsSource.credits_copyright || defaultCreditsConfig.legal.copyright_text,
                    license: rawCreditsSource.legal?.license || rawCreditsSource.credits_license || defaultCreditsConfig.legal.license,
                },
            };
        creditsConfig.editorial = {
            ...defaultCreditsConfig.editorial,
            ...(creditsConfig.editorial || {}),
        };
        creditsConfig.collaborators_styles = {
            ...defaultCreditsConfig.collaborators_styles,
            ...(creditsConfig.collaborators_styles || {}),
            title: {
                ...defaultCreditsConfig.collaborators_styles.title,
                ...(creditsConfig.collaborators_styles && creditsConfig.collaborators_styles.title ? creditsConfig.collaborators_styles.title : {}),
            },
            item: {
                ...defaultCreditsConfig.collaborators_styles.item,
                ...(creditsConfig.collaborators_styles && creditsConfig.collaborators_styles.item ? creditsConfig.collaborators_styles.item : {}),
            },
        };
        creditsConfig.section_order = Array.isArray(creditsConfig.section_order) && creditsConfig.section_order.length
            ? creditsConfig.section_order
            : defaultCreditsConfig.section_order.slice();
        creditsConfig.section_styles = {
            ...defaultCreditsConfig.section_styles,
            ...(creditsConfig.section_styles || {}),
        };
        creditsConfig.legal = {
            ...defaultCreditsConfig.legal,
            ...(creditsConfig.legal || {}),
        };
        if (!String(creditsConfig.legal.copyright_text || '').trim()) {
            creditsConfig.legal.copyright_text = defaultCreditsConfig.legal.copyright_text;
        }
        if (!String(creditsConfig.legal.license || '').trim()) {
            creditsConfig.legal.license = defaultCreditsConfig.legal.license;
        }
        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
        const formatCreditsWebsite = (value) => String(value || '')
            .trim()
            .replace(/^https?:\/\//i, '');
        const formatCreditsDate = (value) => {
            const raw = String(value || '').trim();
            if (!raw) return '';
            if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
                const [year, month] = raw.split('-');
                const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                const monthName = months[parseInt(month, 10) - 1] || '';
                return `${monthName} ${year}`;
            }
            if (/^\d{4}-\d{2}$/.test(raw)) {
                const [year, month] = raw.split('-');
                const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                const monthName = months[parseInt(month, 10) - 1] || '';
                return `${monthName} ${year}`;
            }
            return raw;
        };
        const roleLabel = typeof window.almadenGetCreditsRoleLabel === 'function' ? window.almadenGetCreditsRoleLabel : (value) => value;
        const logoPositionJustify = (value) => {
            const normalized = String(value || '').toLowerCase();
            if (normalized === 'left') return 'flex-start';
            if (normalized === 'right') return 'flex-end';
            return 'center';
        };
        const licenseLabel = typeof window.almadenGetCreditsLicenseLabel === 'function' ? window.almadenGetCreditsLicenseLabel : (value) => value;
        const normalizeSectionStyle = (sectionId) => {
            const defaultStyle = { show_separator: 0, font_family: '', font_size: '', letter_spacing: '', line_height: '', text_align: '', item_gap_px: '' };
            const source = creditsConfig.section_styles && typeof creditsConfig.section_styles === 'object' ? creditsConfig.section_styles[sectionId] : null;
            const style = source && typeof source === 'object' ? source : defaultStyle;
            const textAlign = ['left', 'center', 'right'].includes(String(style.text_align || '').toLowerCase()) ? String(style.text_align).toLowerCase() : '';
            return {
                show_separator: style.show_separator === 1 || style.show_separator === '1' || style.show_separator === true,
                font_family: String(style.font_family || '').trim(),
                font_size: style.font_size !== undefined && style.font_size !== '' && !Number.isNaN(parseFloat(style.font_size)) ? Math.min(Math.max(parseFloat(style.font_size), 8), 72) : '',
                letter_spacing: style.letter_spacing !== undefined && style.letter_spacing !== '' && !Number.isNaN(parseFloat(style.letter_spacing)) ? Math.min(Math.max(parseFloat(style.letter_spacing), -10), 20) : '',
                line_height: style.line_height !== undefined && style.line_height !== '' && !Number.isNaN(parseFloat(style.line_height)) ? Math.min(Math.max(parseFloat(style.line_height), 0.5), 3) : '',
                text_align: textAlign,
                item_gap_px: style.item_gap_px !== undefined && style.item_gap_px !== '' && !Number.isNaN(parseFloat(style.item_gap_px)) ? Math.min(Math.max(parseFloat(style.item_gap_px), 0), 80) : '',
            };
        };
        const cssFontFamily = (value) => {
            const family = String(value || '').trim();
            if (!family) return '';
            return `'${family.replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;
        };
        const normalizeCollaboratorsTextStyle = (value, defaults) => {
            const source = value && typeof value === 'object' ? value : {};
            const fontSize = parseInt(source.font_size ?? defaults.font_size, 10);
            const lineHeight = parseFloat(source.line_height ?? defaults.line_height);
            const fontWeight = String(source.font_weight ?? defaults.font_weight);
            return {
                font_family: String(source.font_family || '').trim(),
                font_size: Number.isFinite(fontSize) ? Math.min(Math.max(fontSize, 8), 36) : defaults.font_size,
                font_weight: ['300', '400', '500', '600', '700', '800'].includes(fontWeight) ? fontWeight : defaults.font_weight,
                line_height: Number.isFinite(lineHeight) ? Math.min(Math.max(lineHeight, 0.5), 3) : defaults.line_height,
            };
        };
        const collaboratorsStylesSource = creditsConfig.collaborators_styles && typeof creditsConfig.collaborators_styles === 'object'
            ? creditsConfig.collaborators_styles
            : {};
        const collaboratorsStyles = {
            title: normalizeCollaboratorsTextStyle(
                collaboratorsStylesSource.title,
                { font_family: '', font_size: 12, font_weight: '700', line_height: 1.2 }
            ),
            item: normalizeCollaboratorsTextStyle(
                collaboratorsStylesSource.item,
                { font_family: '', font_size: 10, font_weight: '400', line_height: 1.3 }
            ),
            image_max_width: Math.min(Math.max(parseInt(collaboratorsStylesSource.image_max_width || 96, 10) || 96, 60), 140),
        };
        const buildCollaboratorsTextStyle = (style) => [
            style.font_family ? `font-family: ${cssFontFamily(style.font_family)} !important;` : '',
            `font-size: ${style.font_size}px !important;`,
            `font-weight: ${style.font_weight} !important;`,
            `line-height: ${style.line_height} !important;`,
        ].filter(Boolean).join(' ');
        const buildSectionStyle = (sectionId) => {
            const style = normalizeSectionStyle(sectionId);
            const css = [];
            if (style.font_family) css.push(`font-family: ${cssFontFamily(style.font_family)} !important;`);
            if (style.font_size !== '') css.push(`font-size: ${style.font_size}px !important;`);
            if (style.letter_spacing !== '') css.push(`letter-spacing: ${style.letter_spacing}px !important;`);
            if (style.line_height !== '') css.push(`line-height: ${style.line_height} !important;`);
            if (style.text_align) {
                css.push(`text-align: ${style.text_align} !important;`);
                css.push(`text-align-last: ${style.text_align} !important;`);
            }

            const selector = `.credits-page-content .credits-section-block.credits-section-${sectionId}, .credits-page-content .credits-section-block.credits-section-${sectionId} *`;
            const styleTag = css.length > 0
                ? `<style>${selector} { ${css.join(' ')} }</style>`
                : '';

            return {
                style,
                css: css.join(' '),
                styleTag,
            };
        };
        const wrapSection = (sectionId, innerHtml, options = {}) => {
            if (!innerHtml) return '';
            const { style, css, styleTag } = buildSectionStyle(sectionId);
            // The separator belongs to this section and is drawn after it, matching Typst.
            const separator = style.show_separator && options.hasNext
                ? '<div class="credits-section-separator" style="width: 100%; border-top: 0.25px solid #000; margin: 1.25em 0 1em 0;"></div>'
                : '';
            return `${styleTag}${separator}<div class="credits-section-block credits-section-${sectionId}" style="${css}">${innerHtml}</div>`;
        };
        const legalSectionStyle = normalizeSectionStyle('legal');
        const legalTextAlignment = legalSectionStyle.text_align || '';
        const legalAlignmentStyle = legalTextAlignment
            ? `text-align: ${legalTextAlignment} !important; text-align-last: ${legalTextAlignment} !important;`
            : '';
        const legalBlockAlignItems = legalTextAlignment === 'right'
            ? 'flex-end'
            : (legalTextAlignment === 'center' ? 'center' : 'flex-start');
        const legalBlockWidth = legalTextAlignment === 'left' ? '100%' : '72%';
        const legalSectionLayoutStyle = `width: 100%; display: flex; flex-direction: column; align-items: ${legalBlockAlignItems};`;
        const legalBlockLayoutStyle = `width: ${legalBlockWidth}; max-width: 100%; padding-left: 0; padding-right: 0; ${legalAlignmentStyle}`;
        const legalParagraphLayoutStyle = `width: 100%; margin: 0; padding: 0; ${legalAlignmentStyle}`;
        const peopleSectionStyle = normalizeSectionStyle('people');
        const peopleTextAlignment = peopleSectionStyle.text_align;
        const peopleItemGapPx = peopleSectionStyle.item_gap_px === '' ? 10 : peopleSectionStyle.item_gap_px;
        const peopleAlignItems = peopleTextAlignment === 'right'
            ? 'flex-end'
            : (peopleTextAlignment === 'center' ? 'center' : 'flex-start');
        const peopleAlignmentStyle = peopleTextAlignment
            ? `text-align: ${peopleTextAlignment} !important; text-align-last: ${peopleTextAlignment} !important;`
            : '';
        const peopleSectionLayoutStyle = `width: 100%; display: flex; flex-direction: column; ${peopleTextAlignment ? `align-items: ${peopleAlignItems};` : ''}`;
        const peopleEntryLayoutStyle = `display: flex; flex-direction: column; width: 100%; ${peopleTextAlignment ? `align-items: ${peopleAlignItems};` : ''} ${peopleAlignmentStyle}`;
        const peopleLineLayoutStyle = `display: block; width: fit-content; max-width: 100%; ${peopleTextAlignment ? `align-self: ${peopleAlignItems};` : ''} white-space: normal; overflow-wrap: anywhere; ${peopleAlignmentStyle}`;
        const collaboratorsSectionStyle = normalizeSectionStyle('collaborators');
        const collaboratorsTextAlignment = collaboratorsSectionStyle.text_align;
        const collaboratorsAlignItems = collaboratorsTextAlignment === 'right'
            ? 'flex-end'
            : (collaboratorsTextAlignment === 'left' ? 'flex-start' : 'center');
        const collaboratorsAlignmentStyle = collaboratorsTextAlignment
            ? `text-align: ${collaboratorsTextAlignment} !important; text-align-last: ${collaboratorsTextAlignment} !important;`
            : '';
        const collaboratorsSectionLayoutStyle = `width: 100%; display: flex; flex-direction: column; ${collaboratorsTextAlignment ? `align-items: ${collaboratorsAlignItems};` : ''}`;
        const collaboratorsCellLayoutStyle = `display: flex; flex-direction: column; ${collaboratorsTextAlignment ? `align-items: ${collaboratorsAlignItems};` : ''} min-width: 0; height: 100%; break-inside: avoid; page-break-inside: avoid; ${buildCollaboratorsTextStyle(collaboratorsStyles.item)} ${collaboratorsAlignmentStyle}`;
        const collaboratorsTextStackLayoutStyle = `width: fit-content; max-width: 100%; display: flex; flex-direction: column; ${collaboratorsTextAlignment ? `align-items: ${collaboratorsAlignItems}; text-align: ${collaboratorsTextAlignment};` : ''} justify-content: flex-end;`;
        // Keep credits as normal flowing content so Paged can create as many
        // pages as needed. Each tab becomes one ordered, independently styled block.
        let creditsHtml = '<div class="content-box credits-page-content">';
        creditsHtml += '<div class="credits-sections-flow">';
        const sectionOrder = Array.isArray(creditsConfig.section_order) && creditsConfig.section_order.length > 0
            ? creditsConfig.section_order
            : ['editorial', 'people', 'collaborators', 'logos', 'legal'];
        const normalizedSectionOrder = [];
        sectionOrder.forEach((sectionId) => {
            const id = String(sectionId || '').trim();
            if (['editorial', 'people', 'collaborators', 'logos', 'legal'].includes(id) && !normalizedSectionOrder.includes(id)) {
                normalizedSectionOrder.push(id);
            }
        });
        ['editorial', 'people', 'collaborators', 'logos', 'legal'].forEach((sectionId) => {
            if (!normalizedSectionOrder.includes(sectionId)) {
                normalizedSectionOrder.push(sectionId);
            }
        });

        const sectionHtml = {
            editorial: '',
            people: '',
            collaborators: '',
            logos: '',
            legal: '',
        };

        if (creditsConfig.editorial && (
            creditsConfig.editorial.edition_number
            || creditsConfig.editorial.publication_date
            || creditsConfig.editorial.isbn
            || creditsConfig.editorial.printer
        )) {
            let editorialHtml = '<div class="credits-editorial-section" style="margin-top: 0;">';
            if (creditsConfig.editorial.edition_number) {
                const editionLabel = typeof window.almadenGetSpanishEditionLabel === 'function'
                    ? window.almadenGetSpanishEditionLabel(creditsConfig.editorial.edition_number)
                    : '';
                if (editionLabel) {
                    editorialHtml += `<p style="margin: 0 0 0.45em 0;"><strong>${escapeHtml(editionLabel)}</strong></p>`;
                }
            }
            if (creditsConfig.editorial.publication_date) {
                editorialHtml += `<p style="margin: 0 0 0.45em 0;"><strong>Fecha de publicación:</strong> ${escapeHtml(formatCreditsDate(creditsConfig.editorial.publication_date))}</p>`;
            }
            if (creditsConfig.editorial.isbn) {
                editorialHtml += `<p style="margin: 0 0 0.45em 0;"><strong>ISBN:</strong> ${escapeHtml(creditsConfig.editorial.isbn)}</p>`;
            }
            if (creditsConfig.editorial.printer) {
                editorialHtml += `<p style="margin: 0 0 0.45em 0;"><strong>Imprenta:</strong> ${escapeHtml(creditsConfig.editorial.printer)}</p>`;
            }
            editorialHtml += '</div>';
            sectionHtml.editorial = editorialHtml;
        }

        if (Array.isArray(creditsConfig.people) && creditsConfig.people.length > 0) {
            let peopleHtml = `<div class="credits-people-section" style="margin-top: 1.6em; ${peopleSectionLayoutStyle}">`;
            creditsConfig.people.forEach((person) => {
                if (!person || (!person.name && !person.role && !person.email && !person.website)) return;
                const role = roleLabel(person.role || 'author');
                peopleHtml += `<div class="credits-person-entry" style="${peopleEntryLayoutStyle}; margin: 0 0 ${peopleItemGapPx}px 0;">`;
                if (person.name) {
                    peopleHtml += `<span class="credits-person-name" style="${peopleLineLayoutStyle}; font-weight: 700;">${escapeHtml(person.name)}</span>`;
                }
                if (role) {
                    peopleHtml += `<span class="credits-person-role" style="${peopleLineLayoutStyle}; font-style: italic;">${escapeHtml(role)}</span>`;
                }
                if (person.show_contact === 1 || person.show_contact === '1') {
                    if (person.email) peopleHtml += `<span class="credits-person-email" style="${peopleLineLayoutStyle}; font-style: italic;">${escapeHtml(person.email)}</span>`;
                    if (person.website) peopleHtml += `<span class="credits-person-website" style="${peopleLineLayoutStyle};">${escapeHtml(formatCreditsWebsite(person.website))}</span>`;
                }
                peopleHtml += '</div>';
            });
            peopleHtml += '</div>';
            sectionHtml.people = peopleHtml;
        }

        const collaboratorsVisible = creditsConfig.collaborators_visible === 1 || creditsConfig.collaborators_visible === '1' || creditsConfig.collaborators_visible === true;
        const validCollaborators = Array.isArray(creditsConfig.collaborators)
            ? creditsConfig.collaborators.filter((item) => item && (item.name || item.type || item.logo_url || item.website || item.text))
            : [];
        if (collaboratorsVisible && validCollaborators.length > 0) {
            let collaboratorsHtml = `<div class="credits-collaborators-section" style="margin-top: 1.6em; ${collaboratorsSectionLayoutStyle}">`;
            const collaboratorsTitle = String(creditsConfig.collaborators_title || 'Colaboradores').trim();
            const collaboratorsTitleStyle = buildCollaboratorsTextStyle(collaboratorsStyles.title);
            const collaboratorsItemStyle = buildCollaboratorsTextStyle(collaboratorsStyles.item);
            const collaboratorsImageSlotHeight = Math.max(96, collaboratorsStyles.image_max_width);
            const collaboratorsTextAreaHeight = Number.parseFloat((collaboratorsStyles.item.line_height * 2).toFixed(2));
            const renderCollaboratorCell = (item) => {
                let cellHtml = `<div class="credits-collaborator-cell" style="${collaboratorsCellLayoutStyle};">`;
                cellHtml += `<div class="credits-collaborator-image-area" style="height: ${collaboratorsImageSlotHeight}px; display: flex; align-items: center; justify-content: center; width: 100%;">`;
                if (item.logo_url) {
                    cellHtml += `<img src="${escapeHtml(item.logo_url)}" alt="" style="width: auto; max-width: ${collaboratorsStyles.image_max_width}px !important; height: auto; max-height: ${collaboratorsImageSlotHeight}px; object-fit: contain; display: block; margin: 0 auto;">`;
                }
                cellHtml += `</div><div class="credits-collaborator-text-area" style="flex: 1 1 auto; min-height: ${collaboratorsTextAreaHeight}em; display: flex; flex-direction: column; justify-content: flex-end; align-items: ${collaboratorsAlignItems}; width: 100%; padding-top: 0.55em; box-sizing: border-box;"><div class="credits-collaborator-text-stack" style="${collaboratorsTextStackLayoutStyle}">`;
                if (item.name) {
                    cellHtml += `<p class="credits-collaborator-name" style="margin: 0; width: fit-content; max-width: 100%; text-align: inherit; ${collaboratorsItemStyle}">${escapeHtml(item.name)}</p>`;
                }
                if (item.type) {
                    cellHtml += `<p class="credits-collaborator-type" style="margin: 0; width: fit-content; max-width: 100%; font-style: italic; text-align: inherit; ${collaboratorsItemStyle}">${escapeHtml(item.type)}</p>`;
                }
                if (item.website) {
                    cellHtml += `<p class="credits-collaborator-website" style="margin: 0; width: fit-content; max-width: 100%; opacity: 0.8; text-align: inherit; overflow-wrap: anywhere; ${collaboratorsItemStyle}">${escapeHtml(formatCreditsWebsite(item.website))}</p>`;
                }
                if (item.text) {
                    cellHtml += `<p class="credits-collaborator-text" style="margin: 0; width: fit-content; max-width: 100%; text-align: inherit; ${collaboratorsItemStyle}">${escapeHtml(item.text)}</p>`;
                }
                cellHtml += '</div></div></div>';
                return cellHtml;
            };

            for (let rowStart = 0; rowStart < validCollaborators.length; rowStart += 3) {
                const rowItems = validCollaborators.slice(rowStart, rowStart + 3);
                const isFirstRow = rowStart === 0;
                collaboratorsHtml += `<div class="credits-collaborators-row${isFirstRow ? ' credits-collaborators-first-row' : ''}" style="break-inside: avoid; page-break-inside: avoid;${isFirstRow ? '' : ' margin-top: 1.25em;'}">`;
                if (isFirstRow && collaboratorsTitle) {
                    collaboratorsHtml += `<div class="credits-section-title" style="margin-bottom: 0.75em; break-after: avoid; page-break-after: avoid; ${collaboratorsTitleStyle}">${escapeHtml(collaboratorsTitle)}</div>`;
                }
                const collaboratorsJustifyItems = collaboratorsTextAlignment === 'right'
                    ? 'end'
                    : (collaboratorsTextAlignment === 'left' ? 'start' : (collaboratorsTextAlignment === 'center' ? 'center' : ''));
                collaboratorsHtml += `<div class="credits-collaborators-grid-row" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1.25em; width: 100%; ${collaboratorsJustifyItems ? `justify-items: ${collaboratorsJustifyItems};` : ''}">`;
                rowItems.forEach((item) => {
                    collaboratorsHtml += renderCollaboratorCell(item);
                });
                collaboratorsHtml += '</div></div>';
            }
            collaboratorsHtml += '</div>';
            sectionHtml.collaborators = collaboratorsHtml;
        }

        if (Array.isArray(creditsConfig.logos) && creditsConfig.logos.length > 0) {
            let logosHtml = '<div class="credits-logos-section" style="margin-top: 1.6em;">';
            creditsConfig.logos.forEach((item) => {
                if (!item || typeof item !== 'object') return;
                const logoUrl = creditsResolveLogoUrl(item);
                const logoSource = String(item.logo_source || item.source_type || item.mode || 'image').trim().toLowerCase() === 'cover_logo'
                    ? 'cover_logo'
                    : 'image';
                const logoSize = Math.max(24, Math.min(400, parseInt(item.size_px || item.size || 120, 10) || 120));
                const logoAlign = logoPositionJustify(item.position || 'center');
                const showAuthorName = item.show_author_name === 1 || item.show_author_name === '1' || item.show_author_name === true;
                if (!logoUrl && !showAuthorName) return;
                const authorLabel = String((bookState && bookState.bookAuthorLabel) || item.author_name || '').trim();
                const authorFontFamily = String(item.author_font_family || '').trim();
                const authorFontSize = Math.max(8, Math.min(48, parseInt(item.author_font_size || 16, 10) || 16));
                const authorFontWeight = String(item.author_font_weight || '').trim();
                const authorLetterSpacing = String(item.author_letter_spacing ?? '').trim();
                const authorGapPx = String(item.author_gap_px ?? '').trim() === ''
                    ? 10
                    : Math.max(0, Math.min(100, parseInt(item.author_gap_px, 10) || 0));
                const authorTextTransform = String(item.author_text_transform || 'none').trim().toLowerCase();
                const safeTextTransform = ['none', 'uppercase', 'lowercase', 'capitalize'].includes(authorTextTransform) ? authorTextTransform : 'none';
                logosHtml += `<div class="credits-logo-row" style="display: flex; flex-direction: column; align-items: ${logoAlign}; margin-bottom: 0.95em;">`;
                if (logoUrl) {
                    logosHtml += `<img src="${escapeHtml(logoUrl)}" alt="" style="width: ${logoSize}px; height: auto; max-width: 100%; object-fit: contain; display: block;">`;
                }
                if (showAuthorName && authorLabel) {
                    const authorInlineStyles = [
                        `margin-top: ${authorGapPx}px`,
                        `font-family: ${creditsCssFontFamilyValue(authorFontFamily)}`,
                        `font-size: ${authorFontSize}px`,
                        authorFontWeight ? `font-weight: ${authorFontWeight}` : '',
                        authorLetterSpacing !== '' ? `letter-spacing: ${authorLetterSpacing}px` : '',
                        'line-height: 1.2',
                        'text-align: inherit',
                        `text-transform: ${safeTextTransform}`,
                    ].filter(Boolean).join('; ');
                    logosHtml += `<div class="credits-logo-author" style="${authorInlineStyles};">${escapeHtml(authorLabel)}</div>`;
                }
                logosHtml += '</div>';
            });
            logosHtml += '</div>';
            sectionHtml.logos = logosHtml;
        }

        if (creditsConfig.legal && creditsConfig.legal.copyright_text) {
            let legalHtml = `<div class="credits-legal-section" style="margin-top: 1.6em; ${legalSectionLayoutStyle}">`;
            const copyrightHtml = escapeHtml(creditsConfig.legal.copyright_text || '').replace(/\n/g, '<br>');
            legalHtml += `<div class="credits-copyright" style="margin-bottom: 1.2em; ${legalBlockLayoutStyle}"><p style="${legalParagraphLayoutStyle}">${copyrightHtml}</p></div>`;
            if (creditsConfig.legal.license) {
                legalHtml += `<div class="credits-license" style="margin-top: 1em; font-size: 0.9em; opacity: 0.8; ${legalBlockLayoutStyle}"><p style="${legalParagraphLayoutStyle}">${escapeHtml(licenseLabel(creditsConfig.legal.license || 'all_rights_reserved'))}</p></div>`;
            }
            legalHtml += '</div>';
            sectionHtml.legal = legalHtml;
        } else if (creditsConfig.legal && creditsConfig.legal.license) {
            sectionHtml.legal = `<div class="credits-legal-section" style="margin-top: 1.6em; ${legalSectionLayoutStyle}"><div class="credits-license" style="font-size: 0.9em; opacity: 0.8; ${legalBlockLayoutStyle}"><p style="${legalParagraphLayoutStyle}">${escapeHtml(licenseLabel(creditsConfig.legal.license || 'all_rights_reserved'))}</p></div></div>`;
        }

        const renderableSectionIds = normalizedSectionOrder.filter((sectionId) => sectionHtml[sectionId]);
        renderableSectionIds.forEach((sectionId, sectionIndex) => {
            creditsHtml += wrapSection(sectionId, sectionHtml[sectionId], {
                hasNext: sectionIndex < renderableSectionIds.length - 1,
            });
        });

        creditsHtml += '</div></div>';
        compiledHtml = creditsHtml;
    } else {
        const contentWithoutDuplicateTitle = stripLeadingDuplicateChapterHeading(chapter.content, chapter.title);
        const editableHtml = markEditableChapterBlocks(compileMarkdownToHTML(contentWithoutDuplicateTitle));
        compiledHtml = `<div class="chapter-editable-content" data-editor-content="chapter">${editableHtml}</div>`;
    }
    
    // Letra Capitular (Drop Cap)
    if (chapter.drop_cap_enabled === '1') {
        // Reemplazar la primera p para agregar la clase drop-cap
        compiledHtml = compiledHtml.replace(/<p>/, '<p class="drop-cap">');
    }

    const openingHtml = includeOpeningBlock && !isCreditsChapter
        ? buildChapterOpeningHtml(chapter, index, settings, bookState, { variant: openingVariant })
        : '';

    if (openingHtml) {
        compiledHtml = openingHtml + `\n\n` + compiledHtml;
    }

    if (chapter.disable_hyphenation !== '1' && typeof window.almadenApplyHyphenationToHtml === 'function') {
        compiledHtml = window.almadenApplyHyphenationToHtml(compiledHtml, settings);
    }

    if (typeof window.normalizeImageBlocksInHtml === 'function') {
        compiledHtml = window.normalizeImageBlocksInHtml(compiledHtml);
    }

    if (window.applySemanticChapterPostProcessing) {
        compiledHtml = window.applySemanticChapterPostProcessing(chapter, compiledHtml);
    }
    
    return compiledHtml;
};

window.buildChapterOpeningHtml = buildChapterOpeningHtml;
window.normalizeImageBlocksInHtml = normalizeImageBlocksInHtml;

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
