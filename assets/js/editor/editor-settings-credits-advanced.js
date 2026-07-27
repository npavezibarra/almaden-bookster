function creditsGetCreditsSectionDefinitions() {
    return [
        { id: 'editorial', label: 'Editorial', description: 'Datos generales del libro que aparecerán en la página de créditos.' },
        { id: 'people', label: 'Personas', description: 'Autores, editores y demás roles personales del libro.' },
        { id: 'collaborators', label: 'Colaboradores', description: 'Sellos, fundaciones, mecenas o entidades asociadas.' },
        { id: 'logos', label: 'Logo', description: 'Logo principal del libro o una imagen personalizada.' },
        { id: 'legal', label: 'Legal', description: 'Texto legal y tipo de licencia para esta edición.' },
    ];
}

function creditsGetCreditsAvailableFonts() {
    const families = new Set(CREDITS_FALLBACK_FONT_FAMILIES || []);
    const installed = typeof bookState !== 'undefined' && bookState && Array.isArray(bookState.installedFonts)
        ? bookState.installedFonts
        : [];

    installed.forEach((font) => {
        const family = String(font && typeof font === 'object' ? font.family : font || '').trim();
        if (family) families.add(family);
    });

    return Array.from(families);
}

function creditsBuildCreditsFontOptions(selected, includeBlank = true) {
    const blank = includeBlank ? '<option value="">Heredar</option>' : '';
    return `${blank}${creditsOptionMarkup(
        creditsGetCreditsAvailableFonts().map((family) => ({ value: family, label: family })),
        selected || ''
    )}`;
}

function creditsBuildCreditsSectionTabs(order, activeTab) {
    const definitions = creditsGetCreditsSectionDefinitions();
    const labelById = definitions.reduce((acc, item) => {
        acc[item.id] = item.label;
        return acc;
    }, {});
    return (Array.isArray(order) ? order : [])
        .filter((id, index, array) => id && array.indexOf(id) === index)
        .map((sectionId) => {
            const isActive = sectionId === activeTab;
            return `
                <button type="button"
                    data-credits-tab="${creditsEscapeHtml(sectionId)}"
                    draggable="true"
                    title="Arrastra para reordenar"
                    class="credits-tab-btn rounded-2xl border px-4 py-3 text-sm font-semibold transition ${isActive ? 'bg-black text-white border-black shadow-sm' : 'bg-white text-[var(--text-main)] border-[var(--border-color)] hover:bg-[var(--bg-sidebar)]'}">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-grip-vertical text-[10px] opacity-60"></i>
                        ${creditsEscapeHtml(labelById[sectionId] || sectionId)}
                    </span>
                </button>
            `;
        })
        .join('');
}

function creditsBuildCreditsSectionStyleCard(sectionId, label, style = {}) {
    const fields = {
        show_separator: style.show_separator === 1 || style.show_separator === '1' || style.show_separator === true,
        font_family: String(style.font_family || ''),
        font_size: String(style.font_size || ''),
        letter_spacing: String(style.letter_spacing || ''),
        line_height: String(style.line_height || ''),
        text_align: String(style.text_align || ''),
    };

    return `
        <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/30 p-5 space-y-4" data-credits-style-section="${creditsEscapeHtml(sectionId)}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h6 class="text-base font-extrabold uppercase tracking-[0.2em] text-[var(--text-main)]">Estilo de ${creditsEscapeHtml(label)}</h6>
                    <p class="mt-1 text-sm text-[var(--text-muted)]">Ajusta tipografía, alineación y separador para este bloque.</p>
                </div>
                <label class="inline-flex items-center gap-3 text-sm font-semibold text-[var(--text-main)]">
                    <input type="checkbox" data-credits-field="section_${creditsEscapeHtml(sectionId)}_show_separator" ${fields.show_separator ? 'checked' : ''} class="h-5 w-5 rounded border-[var(--border-color)] text-black focus:ring-black">
                    Mostrar separador
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Tipografía</label>
                    <select data-credits-field="section_${creditsEscapeHtml(sectionId)}_font_family" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        ${creditsBuildCreditsFontOptions(fields.font_family)}
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Tamaño de fuente (px)</label>
                    <input type="number" min="8" max="72" step="1" data-credits-field="section_${creditsEscapeHtml(sectionId)}_font_size" value="${creditsEscapeHtml(fields.font_size)}" placeholder="Heredar" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Letter spacing (px)</label>
                    <input type="number" step="0.1" min="-10" max="20" data-credits-field="section_${creditsEscapeHtml(sectionId)}_letter_spacing" value="${creditsEscapeHtml(fields.letter_spacing)}" placeholder="Heredar" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Line height</label>
                    <input type="number" step="0.1" min="0.5" max="3" data-credits-field="section_${creditsEscapeHtml(sectionId)}_line_height" value="${creditsEscapeHtml(fields.line_height)}" placeholder="Heredar" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Alineación</label>
                    <select data-credits-field="section_${creditsEscapeHtml(sectionId)}_text_align" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        ${creditsOptionMarkup([
                            { value: '', label: 'Heredar alineación' },
                            { value: 'left', label: 'Izquierda' },
                            { value: 'center', label: 'Centro' },
                            { value: 'right', label: 'Derecha' },
                        ], fields.text_align)}
                    </select>
                </div>
            </div>
        </div>
    `;
}

function creditsBuildCollaboratorsStylesCard(style = {}) {
    const title = style.title && typeof style.title === 'object' ? style.title : {};
    const item = style.item && typeof style.item === 'object' ? style.item : {};
    const imageMaxWidth = parseInt(style.image_max_width || 96, 10) || 96;
    const buildTextStyleInputs = (prefix, values, label) => `
        <div class="rounded-2xl border border-[var(--border-color)] bg-white/70 p-4 space-y-4">
            <h6 class="text-sm font-bold text-[var(--text-main)]">${creditsEscapeHtml(label)}</h6>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Tipografía</label>
                    <select data-credits-field="${prefix}_font_family" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        ${creditsBuildCreditsFontOptions(values.font_family)}
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Tamaño (px)</label>
                    <input type="number" min="8" max="36" step="1" data-credits-field="${prefix}_font_size" value="${creditsEscapeHtml(values.font_size || '')}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Peso</label>
                    <select data-credits-field="${prefix}_font_weight" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        ${creditsOptionMarkup([
                            { value: '300', label: 'Light (300)' },
                            { value: '400', label: 'Normal (400)' },
                            { value: '500', label: 'Medium (500)' },
                            { value: '600', label: 'Semibold (600)' },
                            { value: '700', label: 'Bold (700)' },
                            { value: '800', label: 'Extra Bold (800)' },
                        ], values.font_weight || '400')}
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Line height</label>
                    <input type="number" min="0.5" max="3" step="0.1" data-credits-field="${prefix}_line_height" value="${creditsEscapeHtml(values.line_height || '')}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                </div>
            </div>
        </div>
    `;

    return `
        <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/30 p-5 space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h6 class="text-base font-extrabold uppercase tracking-[0.2em] text-[var(--text-main)]">Estilo de colaboradores</h6>
                    <p class="mt-1 text-sm text-[var(--text-muted)]">Define la tipografía del título, de cada tarjeta y el ancho máximo de las imágenes.</p>
                </div>
            </div>
            <div class="space-y-4">
                ${buildTextStyleInputs('collaborators_title', title, 'Título de la sección')}
                ${buildTextStyleInputs('collaborators_item', item, 'Texto de cada colaborador')}
                <div class="rounded-2xl border border-[var(--border-color)] bg-white/70 p-4">
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Ancho máximo de imagen (px)</label>
                    <input type="number" min="60" max="140" step="1" data-credits-field="collaborators_image_max_width" value="${creditsEscapeHtml(imageMaxWidth)}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                </div>
            </div>
        </div>
    `;
}

function creditsGetBookCoverLogoUrlFromEditorState() {
    try {
        const state = typeof bookState !== 'undefined' && bookState ? bookState : window.bookState || null;
        let coverSettings = state && state.coverSettings ? state.coverSettings : {};
        if (typeof coverSettings === 'string' && coverSettings.trim() !== '') {
            coverSettings = JSON.parse(coverSettings);
        }
        if (!coverSettings || typeof coverSettings !== 'object') return '';

        let textLayers = coverSettings.text_layers || [];
        if (typeof textLayers === 'string' && textLayers.trim() !== '') {
            textLayers = JSON.parse(textLayers);
        }
        if (!Array.isArray(textLayers) || !textLayers.length) return '';

        const logoGroup = textLayers.find((layer) => layer && layer.type === 'group' && (layer.isBookLogo === true || layer.isBookLogo === 'true'));
        if (!logoGroup) return '';
        const groupId = String(logoGroup.id || '');
        const imageLayer = textLayers.find((layer) => layer
            && layer.type === 'image'
            && String(layer.parentId || '') === groupId
            && String(layer.url || '').trim() !== '');
        return imageLayer ? String(imageLayer.url || '').trim() : '';
    } catch (error) {
        return '';
    }
}

function creditsBuildLogoControls(logo = {}) {
    const source = String(logo.logo_source || logo.source_type || logo.mode || 'image').trim().toLowerCase() === 'cover_logo' ? 'cover_logo' : 'image';
    const uploadedUrl = String(logo.logo_url || logo.image_url || logo.url || '').trim();
    const coverUrl = creditsGetBookCoverLogoUrlFromEditorState();
    const activeUrl = source === 'cover_logo' ? coverUrl : uploadedUrl;
    const showAuthorName = logo.show_author_name === 1 || logo.show_author_name === '1' || logo.show_author_name === true;
    const authorFontFamily = String(logo.author_font_family || '').trim();
    const authorFontSize = parseInt(logo.author_font_size || 16, 10) || 16;
    const authorFontWeight = String(logo.author_font_weight || '').trim();
    const authorLetterSpacing = String(logo.author_letter_spacing ?? '').trim();
    const authorGapPx = String(logo.author_gap_px ?? '').trim() === ''
        ? 10
        : Math.max(0, Math.min(100, parseInt(logo.author_gap_px, 10) || 0));
    const authorTextTransform = creditsNormalizeLogoTextTransform(logo.author_text_transform || 'none');
    const authorName = String((bookState && bookState.bookAuthorLabel) || '').trim();

    return `
        <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/30 p-5 space-y-5">
            <input type="hidden" data-credits-field="logo_source" value="${creditsEscapeHtml(source)}">
            <input type="hidden" data-credits-field="logo_url" value="${creditsEscapeHtml(uploadedUrl)}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h6 class="text-base font-extrabold uppercase tracking-[0.2em] text-[var(--text-main)]">Logo del libro</h6>
                    <p class="mt-1 text-sm text-[var(--text-muted)]">Elige entre una imagen subida o el logo declarado en la portada del libro.</p>
                </div>
                <div class="inline-flex overflow-hidden rounded-2xl border border-[var(--border-color)] bg-white">
                    <button type="button" data-credits-logo-source-option="image" class="px-4 py-2 text-sm font-semibold ${source === 'image' ? 'bg-black text-white' : 'text-[var(--text-main)] hover:bg-[var(--bg-sidebar)]'}">Imagen</button>
                    <button type="button" data-credits-logo-source-option="cover_logo" ${coverUrl ? '' : 'disabled'} class="px-4 py-2 text-sm font-semibold ${source === 'cover_logo' ? 'bg-black text-white' : 'text-[var(--text-main)] hover:bg-[var(--bg-sidebar)]'} ${coverUrl ? '' : 'opacity-40 cursor-not-allowed'}">Logo</button>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-5">
                <div class="space-y-3">
                    <div class="rounded-2xl border border-[var(--border-color)] bg-white p-4">
                        <div class="flex min-h-40 items-center justify-center overflow-hidden rounded-xl border border-dashed border-[var(--border-color)] bg-[var(--bg-app)]/60 p-4">
                            <img data-credits-logo-preview src="${activeUrl ? creditsEscapeHtml(activeUrl) : 'data:image/gif;base64,R0lGODlhAQABAAAAACw='}" alt="" class="${activeUrl ? '' : 'hidden'} max-w-full object-contain">
                            <div data-credits-logo-placeholder class="text-center text-xs font-semibold text-[var(--text-muted)] ${activeUrl ? 'hidden' : ''}">
                                ${source === 'cover_logo' && !coverUrl ? 'No se encontró una capa LOGO en la portada.' : 'Sin imagen seleccionada'}
                            </div>
                        </div>
                    </div>
                    <button type="button" data-credits-action="choose-logo-image" class="w-full rounded-xl bg-black px-4 py-3 text-sm font-semibold text-white hover:bg-neutral-800 transition ${source === 'cover_logo' ? 'opacity-50 cursor-not-allowed' : ''}">
                        <i class="fa-solid fa-image mr-1"></i>
                        Subir imagen
                    </button>
                    <p class="text-[11px] text-[var(--text-muted)]">
                        ${coverUrl ? 'El logo de portada se toma desde la capa marcada como LOGO en Edit Book Cover.' : 'Para usar esta opción, marca una capa como LOGO en Edit Book Cover.'}
                    </p>
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Posición</label>
                            <select data-credits-field="logo_position" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                                ${creditsOptionMarkup(CREDITS_LOGO_POSITION_OPTIONS, creditsNormalizeLogoPosition(logo.position || 'center'))}
                            </select>
                        </div>
                        <div>
                            <div class="flex items-center justify-between gap-3 mb-1">
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)]">Tamaño (px)</label>
                                <span data-credits-logo-size-label class="text-[11px] font-semibold text-[var(--text-muted)]">${creditsNormalizeLogoSize(logo.size_px || 120)} px</span>
                            </div>
                            <input type="range" min="24" max="400" step="1" data-credits-field="logo_size_px" value="${creditsEscapeHtml(creditsNormalizeLogoSize(logo.size_px || 120))}" class="w-full accent-black">
                        </div>
                    </div>
                    <label class="inline-flex items-center gap-3 text-sm font-semibold text-[var(--text-main)]">
                        <input type="checkbox" data-credits-field="show_author_name" ${showAuthorName ? 'checked' : ''} class="h-5 w-5 rounded border-[var(--border-color)] text-black focus:ring-black">
                        Mostrar nombre del autor debajo del logo
                    </label>
                    <div data-credits-logo-author-controls class="rounded-2xl border border-[var(--border-color)] bg-white/70 p-4 space-y-4 ${showAuthorName ? '' : 'hidden'}">
                        <div class="text-sm font-bold text-[var(--text-main)]">${creditsEscapeHtml(authorName || 'Autor del libro')}</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Tipografía del autor</label>
                                <select data-credits-field="author_font_family" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                                    ${creditsBuildCreditsFontOptions(authorFontFamily)}
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Tamaño autor (px)</label>
                                <input type="number" min="8" max="48" step="1" data-credits-field="author_font_size" value="${creditsEscapeHtml(authorFontSize)}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Peso</label>
                                <select data-credits-field="author_font_weight" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                                    ${creditsOptionMarkup([
                                        { value: '', label: 'Heredar' },
                                        { value: '300', label: 'Light (300)' },
                                        { value: '400', label: 'Normal (400)' },
                                        { value: '500', label: 'Medium (500)' },
                                        { value: '600', label: 'Semibold (600)' },
                                        { value: '700', label: 'Bold (700)' },
                                        { value: '800', label: 'Extra Bold (800)' },
                                    ], authorFontWeight)}
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Letter spacing (px)</label>
                                <input type="number" min="-10" max="20" step="0.1" data-credits-field="author_letter_spacing" value="${creditsEscapeHtml(authorLetterSpacing)}" placeholder="Heredar" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Transformación del texto</label>
                                <select data-credits-field="author_text_transform" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                                    ${creditsOptionMarkup([
                                        { value: 'none', label: 'Normal' },
                                        { value: 'uppercase', label: 'ALL CAPS' },
                                    ], authorTextTransform)}
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Espacio entre logo y autor (px)</label>
                                <input type="number" min="0" max="100" step="1" data-credits-field="author_gap_px" value="${creditsEscapeHtml(authorGapPx)}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function creditsBuildAdvancedEditorMarkup(config) {
    const normalized = creditsNormalizeConfig(config || {});
    const defs = creditsGetCreditsSectionDefinitions();
    const defById = defs.reduce((acc, item) => {
        acc[item.id] = item;
        return acc;
    }, {});
    const sectionOrder = Array.isArray(normalized.section_order) && normalized.section_order.length
        ? normalized.section_order.slice()
        : defs.map((item) => item.id);
    const activeTab = sectionOrder.includes('editorial') ? 'editorial' : sectionOrder[0];
    const editorial = normalized.editorial || creditsGetDefaultConfig().editorial;
    const legal = normalized.legal || creditsGetDefaultConfig().legal;
    const people = Array.isArray(normalized.people) ? normalized.people : [];
    const collaborators = Array.isArray(normalized.collaborators) ? normalized.collaborators : [];
    const collaboratorsVisible = normalized.collaborators_visible === 1 || normalized.collaborators_visible === '1' || normalized.collaborators_visible === true;
    const logo = Array.isArray(normalized.logos) && normalized.logos.length ? normalized.logos[0] : {};

    const panels = {
        editorial: `
            <section data-credits-panel="editorial" class="space-y-5">
                <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/30 p-5">
                    <h5 class="text-lg font-bold text-[var(--text-main)]">Editorial</h5>
                    <p class="mt-1 text-sm text-[var(--text-muted)]">Datos generales del libro que aparecerán en la página de créditos.</p>
                    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Número de edición</label>
                            <input id="setting-credits-edition" data-credits-field="edition_number" type="text" placeholder="Primera edición" value="${creditsEscapeHtml(editorial.edition_number || '')}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Fecha de publicación</label>
                            <input id="setting-credits-date" data-credits-field="publication_date" type="month" value="${creditsEscapeHtml(creditsNormalizePublicationDate(editorial.publication_date || ''))}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">ISBN</label>
                            <input id="setting-credits-isbn" data-credits-field="isbn" type="text" placeholder="978-..." value="${creditsEscapeHtml(editorial.isbn || '')}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Imprenta</label>
                            <input id="setting-credits-printer" data-credits-field="printer" type="text" placeholder="Impreso en..." value="${creditsEscapeHtml(editorial.printer || '')}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Páginas blancas iniciales</label>
                            <input id="setting-credits-blank-before" data-credits-field="blank_before" type="number" min="0" value="${creditsEscapeHtml(editorial.blank_before ?? 0)}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Páginas blancas finales</label>
                            <input id="setting-credits-blank-after" data-credits-field="blank_after" type="number" min="0" value="${creditsEscapeHtml(editorial.blank_after ?? 0)}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                    </div>
                </div>
                ${creditsBuildCreditsSectionStyleCard('editorial', defById.editorial.label, normalized.section_styles && normalized.section_styles.editorial ? normalized.section_styles.editorial : {})}
            </section>
        `,
        people: `
            <section data-credits-panel="people" class="hidden space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h5 class="text-lg font-bold text-[var(--text-main)]">Personas</h5>
                        <p class="text-sm text-[var(--text-muted)]">Autores, editores y demás roles personales del libro.</p>
                    </div>
                    <button type="button" data-credits-action="add-person" class="rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-800 transition">
                        <i class="fa-solid fa-plus mr-1"></i> Agregar persona
                    </button>
                </div>
                <div id="credits-people-container" class="space-y-3">
                    ${(people.length ? people : [{}]).map((person) => creditsBuildPersonRow(person)).join('')}
                </div>
                ${creditsBuildCreditsSectionStyleCard('people', defById.people.label, normalized.section_styles && normalized.section_styles.people ? normalized.section_styles.people : {})}
            </section>
        `,
        collaborators: `
            <section data-credits-panel="collaborators" class="hidden space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h5 class="text-lg font-bold text-[var(--text-main)]">Colaboradores</h5>
                        <p class="text-sm text-[var(--text-muted)]">Sellos, fundaciones, mecenas o entidades asociadas.</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <label class="inline-flex items-center gap-3 text-sm font-semibold text-[var(--text-main)]">
                            <input type="checkbox" data-credits-field="collaborators_visible" ${collaboratorsVisible ? 'checked' : ''} class="h-5 w-5 rounded border-[var(--border-color)] text-black focus:ring-black">
                            Visible
                        </label>
                        <button type="button" data-credits-action="add-collaborator" ${collaboratorsVisible ? '' : 'disabled'} class="rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-800 transition ${collaboratorsVisible ? '' : 'opacity-50 cursor-not-allowed'}">
                            <i class="fa-solid fa-plus mr-1"></i> Agregar colaborador
                        </button>
                    </div>
                </div>
                <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/30 p-5 space-y-4">
                    <div data-credits-collaborators-body class="${collaboratorsVisible ? '' : 'pointer-events-none opacity-50'} space-y-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Título de la sección</label>
                            <input type="text" data-credits-field="collaborators_title" value="${creditsEscapeHtml(normalized.collaborators_title || '')}" placeholder="Colaboradores" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        </div>
                        <div id="credits-collaborators-container" class="space-y-3">
                            ${(collaborators.length ? collaborators : [{}]).map((item) => creditsBuildCollaboratorRow(item)).join('')}
                        </div>
                    </div>
                    <p class="text-[11px] text-[var(--text-muted)]">Si lo ocultas, el contenido se mantiene guardado pero no se mostrará en PDF ni en ebook.</p>
                </div>
                ${creditsBuildCollaboratorsStylesCard(normalized.collaborators_styles || {})}
                ${creditsBuildCreditsSectionStyleCard('collaborators', defById.collaborators.label, normalized.section_styles && normalized.section_styles.collaborators ? normalized.section_styles.collaborators : {})}
            </section>
        `,
        logos: `
            <section data-credits-panel="logos" class="hidden space-y-4">
                ${creditsBuildLogoControls(logo)}
                ${creditsBuildCreditsSectionStyleCard('logos', defById.logos.label, normalized.section_styles && normalized.section_styles.logos ? normalized.section_styles.logos : {})}
            </section>
        `,
        legal: `
            <section data-credits-panel="legal" class="hidden space-y-4">
                <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/30 p-5">
                    <h5 class="text-lg font-bold text-[var(--text-main)]">Legal</h5>
                    <p class="mt-1 text-sm text-[var(--text-muted)]">Texto legal y tipo de licencia para esta edición.</p>
                    <div class="mt-5 space-y-4">
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Texto Copyright</label>
                            <textarea id="setting-credits-copyright" data-credits-field="copyright_text" rows="4" class="w-full rounded-2xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black resize-y">${creditsEscapeHtml(legal.copyright_text || '')}</textarea>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Licencia</label>
                            <select id="setting-credits-license" data-credits-field="license" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                                ${creditsOptionMarkup(CREDITS_LICENSE_OPTIONS, legal.license || 'all_rights_reserved')}
                            </select>
                        </div>
                    </div>
                </div>
                ${creditsBuildCreditsSectionStyleCard('legal', defById.legal.label, normalized.section_styles && normalized.section_styles.legal ? normalized.section_styles.legal : {})}
            </section>
        `,
    };

    return `
        <div class="border border-[var(--border-color)] rounded-[28px] bg-[var(--bg-app)] shadow-sm overflow-hidden">
            <div class="border-b border-[var(--border-color)] px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="h-11 w-11 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
                        <i class="fa-solid fa-copyright"></i>
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-black dark:text-white">Configuración de Página de Créditos</h4>
                        <p class="text-xs text-[var(--text-muted)]">Edita la información estructurada que se usará para generar la página de créditos.</p>
                    </div>
                </div>
            </div>
            <div class="px-6 pt-5">
                <div class="flex flex-wrap gap-2 border-b border-[var(--border-color)] pb-4" data-credits-tabs>
                    ${creditsBuildCreditsSectionTabs(sectionOrder, activeTab)}
                </div>
            </div>
            <div class="px-6 py-6 space-y-6">
                ${sectionOrder.map((sectionId) => panels[sectionId] || '').join('')}
            </div>
        </div>
    `;
}
