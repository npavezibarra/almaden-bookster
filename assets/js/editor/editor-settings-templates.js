// --- BOOK TEMPLATES ---
let cachedBookTemplates = null;
let activeBookTemplateGroup = 'system';

function getBookTemplateStatusEl() {
    return document.getElementById('book-template-save-status');
}

function getBookTemplateSaveButton() {
    return document.getElementById('save-book-template-btn');
}

function setBookTemplateStatus(message, type = 'info') {
    const el = getBookTemplateStatusEl();
    if (!el) return;

    if (!message) {
        el.textContent = '';
        el.className = 'mt-2 text-[10px] hidden';
        return;
    }

    const tones = {
        info: 'text-[var(--text-muted)]',
        success: 'text-emerald-600 dark:text-emerald-400',
        error: 'text-rose-600 dark:text-rose-400',
    };
    el.textContent = message;
    el.className = `mt-2 text-[10px] ${tones[type] || tones.info}`;
}

function setBookTemplateButtonLoading(isLoading) {
    const button = getBookTemplateSaveButton();
    if (!button) return;

    if (!button.dataset.originalHtml) {
        button.dataset.originalHtml = button.innerHTML;
    }
    button.disabled = isLoading;
    button.classList.toggle('opacity-60', isLoading);
    button.classList.toggle('cursor-wait', isLoading);
    button.innerHTML = isLoading
        ? '<i class="fa-solid fa-spinner fa-spin"></i><span>Creando plantilla...</span>'
        : button.dataset.originalHtml;
}

function getBookTemplatesAjaxNonce() {
    if (typeof window.almadenBookTemplatesNonce === 'string' && window.almadenBookTemplatesNonce) {
        return window.almadenBookTemplatesNonce;
    }
    return typeof bookState !== 'undefined' && bookState && bookState.settingsNonce
        ? bookState.settingsNonce
        : '';
}

function getBookTemplatesRequestContext() {
    const state = typeof bookState !== 'undefined' && bookState ? bookState : {};
    return {
        ajaxUrl: state.ajaxUrl || window.ajaxurl || `${window.location.origin}/wp-admin/admin-ajax.php`,
        bookId: Number.isFinite(Number(state.bookId)) ? Number(state.bookId) : 0,
        nonce: getBookTemplatesAjaxNonce(),
    };
}

function buildCurrentBookTemplateFlatSettings() {
    const getVal = (id, fallback = '') => {
        const el = document.getElementById(id);
        return el ? el.value : fallback;
    };
    const getCleanVal = (id) => {
        const el = document.getElementById(id);
        return el ? el.value.replace(',', '.') : '';
    };
    const parseVal = (id, fallback) => {
        const value = parseFloat(getCleanVal(id));
        return Number.isNaN(value) ? fallback : value;
    };
    const getChecked = (id) => {
        const el = document.getElementById(id);
        return el ? (el.checked ? 1 : 0) : 0;
    };
    const getBookLanguage = () => getVal('setting-book-language', getVal('setting-content-language', 'es'));
    const getBookFlowMode = () => {
        const el = document.getElementById('setting-book-chapter-flow-mode');
        return el && el.value === 'left' ? 'left' : 'continuous';
    };
    const getLegacyParityFromFlowMode = () => (getBookFlowMode() === 'left' ? 'even' : 'any');
    const creditsConfig = typeof getCreditsConfigFromForm === 'function'
        ? getCreditsConfigFromForm()
        : ((typeof bookState !== 'undefined' && bookState.settings?.credits_config) || {});
    const creditsLegacy = typeof almadenCreditsConfigToLegacy === 'function'
        ? almadenCreditsConfigToLegacy(creditsConfig)
        : {
            credits_edition: getVal('setting-credits-edition'),
            credits_date: getVal('setting-credits-date'),
            credits_isbn: getVal('setting-credits-isbn'),
            credits_copyright: getVal('setting-credits-copyright'),
            credits_printer: getVal('setting-credits-printer'),
            credits_blank_before: parseVal('setting-credits-blank-before', 0),
            credits_blank_after: parseVal('setting-credits-blank-after', 0),
            credits_license: getVal('setting-credits-license'),
            credits_custom: typeof getCustomCreditsJSON === 'function' ? getCustomCreditsJSON() : '[]',
        };

    if (typeof almadenBuildPDFSettingsState === 'function') {
        return almadenBuildPDFSettingsState({
            getVal,
            getCleanVal,
            getChecked,
            parseVal,
            getBookLanguage,
            getBookFlowMode,
            getLegacyParityFromFlowMode,
            creditsConfig,
            creditsLegacy,
        });
    }

    return {
        page_size: getVal('setting-page-size'),
        chapter_title_font_family: getVal('setting-chapter-title-font-family'),
        chapter_title_font_size: parseVal('setting-chapter-title-font-size', 24),
        credits_config: creditsConfig,
        ...creditsLegacy,
    };
}

function buildCurrentPDFTemplateSettings(flatSettings) {
    const pdf = {};
    Object.entries(flatSettings || {}).forEach(([key, value]) => {
        if (!key.startsWith('ebook_') && !['book_language', 'content_language', 'book_authors'].includes(key)) {
            pdf[key] = value;
        }
    });
    const currentSettings = typeof bookState !== 'undefined' && bookState?.settings ? bookState.settings : {};
    pdf.page_templates = Array.isArray(currentSettings.page_templates) ? currentSettings.page_templates : [];
    pdf.page_styles = Array.isArray(currentSettings.page_styles) ? currentSettings.page_styles : [];
    return pdf;
}

function buildCurrentEbookTemplateSettings(flatSettings) {
    return Object.fromEntries(
        Object.entries(flatSettings || {}).filter(([key]) => key.startsWith('ebook_'))
    );
}

function buildCurrentGlobalTemplateSettings(flatSettings) {
    return {
        book_language: flatSettings?.book_language || flatSettings?.content_language || 'es',
    };
}

function buildCurrentBookTemplateSettings() {
    const flatSettings = buildCurrentBookTemplateFlatSettings();
    return {
        pdf: buildCurrentPDFTemplateSettings(flatSettings),
        ebook: buildCurrentEbookTemplateSettings(flatSettings),
        global: buildCurrentGlobalTemplateSettings(flatSettings),
    };
}

function normalizeBookTemplateSettings(settings) {
    if (settings?.pdf || settings?.ebook || settings?.global) {
        return {
            pdf: settings.pdf && typeof settings.pdf === 'object' ? settings.pdf : {},
            ebook: settings.ebook && typeof settings.ebook === 'object' ? settings.ebook : {},
            global: settings.global && typeof settings.global === 'object' ? settings.global : {},
        };
    }

    const scoped = { pdf: {}, ebook: {}, global: {} };
    Object.entries(settings || {}).forEach(([key, value]) => {
        if (key.startsWith('ebook_')) {
            scoped.ebook[key] = value;
        } else if (key === 'book_language' || key === 'content_language') {
            scoped.global.book_language = value;
        } else if (key !== 'book_authors') {
            scoped.pdf[key] = value;
        }
    });
    return scoped;
}

function flattenBookTemplateSettings(settings) {
    const scoped = normalizeBookTemplateSettings(settings);
    const flat = { ...scoped.pdf, ...scoped.ebook, ...scoped.global };
    if (scoped.global.book_language) {
        flat.content_language = scoped.global.book_language;
    }
    return flat;
}

function createBookTemplateRequestData(action) {
    const context = getBookTemplatesRequestContext();
    const data = new FormData();
    data.append('action', action);
    data.append('book_id', String(context.bookId));
    if (context.nonce) {
        data.append('nonce', context.nonce);
    }
    return { context, data };
}

async function parseBookTemplateResponse(response) {
    const raw = await response.text();
    let payload = null;
    try {
        payload = raw ? JSON.parse(raw) : null;
    } catch (error) {
        throw new Error(raw || 'El servidor devolvió una respuesta inválida.');
    }
    if (!response.ok || !payload || !payload.success) {
        throw new Error(payload?.data || payload?.message || 'No se pudo completar la operación.');
    }
    return payload;
}

async function requestBookTemplates(action, decorateData) {
    const { context, data } = createBookTemplateRequestData(action);
    if (typeof decorateData === 'function') {
        decorateData(data);
    }
    const response = await fetch(context.ajaxUrl, { method: 'POST', body: data });
    return parseBookTemplateResponse(response);
}

async function loadBookTemplates() {
    const container = document.getElementById('templates-container');
    if (!container) return [];

    if (cachedBookTemplates) {
        renderBookTemplates(cachedBookTemplates);
        return cachedBookTemplates;
    }

    container.replaceChildren(createBookTemplateMessage('Cargando plantillas de libro...', true));
    setBookTemplateStatus('Cargando plantillas de libro...', 'info');

    try {
        const payload = await requestBookTemplates('almaden_get_book_templates');
        cachedBookTemplates = Array.isArray(payload.data?.templates) ? payload.data.templates : [];
        updateBookTemplateCounts(cachedBookTemplates);
        renderBookTemplates(cachedBookTemplates);
        setBookTemplateStatus('');
        return cachedBookTemplates;
    } catch (error) {
        console.error('Error fetching book templates:', error);
        container.replaceChildren(createBookTemplateMessage(error.message || 'No se pudieron cargar las plantillas.', false, true));
        setBookTemplateStatus(error.message || 'No se pudieron cargar las plantillas.', 'error');
        return [];
    }
}

function createBookTemplateMessage(message, loading = false, isError = false) {
    const element = document.createElement('div');
    element.className = `col-span-full text-[10px] italic ${isError ? 'text-rose-500' : 'text-[var(--text-muted)]'}`;
    if (loading) {
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-spinner fa-spin mr-1';
        element.appendChild(icon);
    }
    element.appendChild(document.createTextNode(message));
    return element;
}

function updateBookTemplateCounts(templates) {
    const systemCount = templates.filter((template) => template.origin === 'system').length;
    const personalCount = templates.filter((template) => template.origin === 'user').length;
    const systemEl = document.getElementById('book-template-count-system');
    const personalEl = document.getElementById('book-template-count-personal');
    if (systemEl) systemEl.textContent = String(systemCount);
    if (personalEl) personalEl.textContent = String(personalCount);
}

function switchBookTemplateGroup(group) {
    activeBookTemplateGroup = group === 'personal' ? 'personal' : 'system';
    document.querySelectorAll('[data-book-template-group]').forEach((button) => {
        const active = button.dataset.bookTemplateGroup === activeBookTemplateGroup;
        button.setAttribute('aria-selected', active ? 'true' : 'false');
        button.classList.toggle('bg-black', active);
        button.classList.toggle('text-white', active);
        button.classList.toggle('text-[var(--text-muted)]', !active);
    });
    renderBookTemplates(cachedBookTemplates || []);
}

function createBookTemplateAction(label, className, handler, iconClass = '') {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = className;
    if (iconClass) {
        const icon = document.createElement('i');
        icon.className = iconClass;
        button.appendChild(icon);
    }
    const labelNode = document.createElement('span');
    labelNode.textContent = label;
    button.appendChild(labelNode);
    button.addEventListener('click', handler);
    return button;
}

function createBookTemplateCard(template) {
    const card = document.createElement('article');
    card.className = 'border border-[var(--border-color)] rounded-lg p-3 bg-[var(--bg-sidebar)] flex flex-col justify-between gap-4';

    const content = document.createElement('div');
    const heading = document.createElement('h5');
    heading.className = 'text-xs font-bold text-[var(--text-main)]';
    heading.textContent = template.name || 'Plantilla sin nombre';
    content.appendChild(heading);
    card.appendChild(content);

    const actions = document.createElement('div');
    actions.className = 'flex flex-wrap items-center gap-2';
    actions.appendChild(createBookTemplateAction(
        'Aplicar',
        'text-[10px] font-semibold text-white bg-black hover:bg-neutral-800 rounded px-3 py-1.5 transition inline-flex items-center gap-1.5',
        () => applyBookTemplate(template.id),
        'fa-solid fa-wand-magic-sparkles'
    ));

    if (template.origin === 'user' && template.can_update) {
        actions.appendChild(createBookTemplateAction(
            'Actualizar',
            'text-[10px] font-semibold text-[var(--text-main)] border border-[var(--border-color)] hover:bg-[var(--bg-app)] rounded px-3 py-1.5 transition inline-flex items-center gap-1.5',
            () => updateBookTemplateFromCurrentSettings(template.id),
            'fa-solid fa-rotate'
        ));
    }

    if (template.origin === 'user' && template.can_promote) {
        actions.appendChild(createBookTemplateAction(
            'Convertir a estándar',
            'text-[10px] font-semibold text-[var(--text-main)] border border-[var(--border-color)] hover:bg-[var(--bg-app)] rounded px-3 py-1.5 transition inline-flex items-center gap-1.5',
            () => promoteBookTemplateToStandard(template.id),
            'fa-solid fa-star'
        ));
    }

    actions.appendChild(createBookTemplateAction(
        'JSON',
        'text-[10px] font-semibold text-[var(--text-main)] border border-[var(--border-color)] hover:bg-[var(--bg-app)] rounded px-3 py-1.5 transition inline-flex items-center gap-1.5',
        () => downloadBookTemplate(template.id),
        'fa-solid fa-download'
    ));

    if (template.origin === 'user' && template.can_delete) {
        const deleteButton = createBookTemplateAction(
            'Eliminar',
            'ml-auto text-[10px] font-semibold text-rose-600 hover:text-rose-700 rounded px-2 py-1.5 transition inline-flex items-center gap-1.5',
            () => deleteBookTemplate(template.id),
            'fa-solid fa-trash'
        );
        actions.appendChild(deleteButton);
    }

    card.appendChild(actions);
    return card;
}

function renderBookTemplates(templates) {
    const container = document.getElementById('templates-container');
    if (!container) return;

    const origin = activeBookTemplateGroup === 'personal' ? 'user' : 'system';
    const visibleTemplates = (Array.isArray(templates) ? templates : []).filter((template) => template.origin === origin);
    container.replaceChildren();

    if (!visibleTemplates.length) {
        const message = origin === 'user'
            ? 'Todavía no tienes plantillas. Crea una con los ajustes actuales o importa un JSON.'
            : 'No hay plantillas estándar disponibles.';
        container.appendChild(createBookTemplateMessage(message));
        return;
    }

    visibleTemplates.forEach((template) => container.appendChild(createBookTemplateCard(template)));
}

function applyBookTemplate(templateId) {
    const template = cachedBookTemplates?.find((item) => item.id === templateId);
    if (!template || !template.settings) return;

    if (!confirm(`¿Aplicar la plantilla "${template.name}"?\nEsto reemplazará los ajustes actuales del libro.`)) {
        return;
    }

    const fieldOverrides = {
        ebook_bg_color: 'setting-ebook-bg-color-text',
        ebook_cover_panel_bg_color: 'setting-ebook-cover-panel-bg-color-text',
        ebook_font_family_headings: 'setting-ebook-chapter-title-font-family',
        ebook_font_size_headings: 'setting-ebook-chapter-title-font-size',
        ebook_font_weight_headings: 'setting-ebook-chapter-title-font-weight',
        ebook_line_height_headings: 'setting-ebook-chapter-title-line-height',
        ebook_subtitle_show: 'setting-ebook-chapter-subtitle-show',
        ebook_subtitle_font_family: 'setting-ebook-chapter-subtitle-font-family',
        ebook_subtitle_font_size: 'setting-ebook-chapter-subtitle-font-size',
        ebook_subtitle_align: 'setting-ebook-chapter-subtitle-align',
        ebook_subtitle_font_style: 'setting-ebook-chapter-subtitle-font-style',
        ebook_subtitle_text_transform: 'setting-ebook-chapter-subtitle-text-transform',
        ebook_subtitle_font_weight: 'setting-ebook-chapter-subtitle-font-weight',
        ebook_subtitle_padding_top: 'setting-ebook-chapter-subtitle-padding-top',
        ebook_subtitle_padding_bottom: 'setting-ebook-chapter-subtitle-padding-bottom',
        ebook_subtitle_letter_spacing: 'setting-ebook-chapter-subtitle-letter-spacing',
    };

    const scopedSettings = normalizeBookTemplateSettings(template.settings);
    const flatSettings = flattenBookTemplateSettings(scopedSettings);
    Object.entries(flatSettings).forEach(([key, value]) => {
        if (value && typeof value === 'object') return;
        const fieldId = fieldOverrides[key] || `setting-${key.replace(/_/g, '-')}`;
        const field = document.getElementById(fieldId);
        if (!field) return;
        if (field.type === 'checkbox') {
            field.checked = value === true || value === 1 || value === '1';
        } else {
            field.value = value ?? '';
        }
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    });

    if (typeof bookState !== 'undefined' && bookState) {
        bookState.settings = { ...(bookState.settings || {}), ...flatSettings };
        if (scopedSettings.pdf.credits_config && typeof initCreditsForm === 'function') {
            initCreditsForm();
        }
    }

    if (typeof toggleEbookBgType === 'function') toggleEbookBgType();
    if (typeof toggleCoverPanelBgType === 'function') toggleCoverPanelBgType();

    if (typeof savePDFSettings === 'function') {
        savePDFSettings();
    }
}

async function promptSaveCurrentAsBookTemplate() {
    const name = prompt('Nombre de la nueva plantilla:');
    if (!name || !name.trim()) return;

    setBookTemplateButtonLoading(true);
    setBookTemplateStatus('Creando plantilla...', 'info');
    try {
        const payload = await requestBookTemplates('almaden_create_book_template', (data) => {
            data.append('template_name', name.trim());
            data.append('settings', JSON.stringify(buildCurrentBookTemplateSettings()));
        });
        cachedBookTemplates = null;
        activeBookTemplateGroup = 'personal';
        await loadBookTemplates();
        switchBookTemplateGroup('personal');
        setBookTemplateStatus(`Plantilla "${payload.data.template.name}" creada con éxito.`, 'success');
    } catch (error) {
        console.error(error);
        setBookTemplateStatus(error.message || 'No se pudo crear la plantilla.', 'error');
    } finally {
        setBookTemplateButtonLoading(false);
    }
}

async function updateBookTemplateFromCurrentSettings(templateId) {
    const template = cachedBookTemplates?.find((item) => item.id === templateId && item.origin === 'user');
    if (!template) return;
    if (!confirm('¿Estás seguro que quieres actualizar esta plantilla?')) {
        return;
    }

    setBookTemplateStatus(`Actualizando "${template.name}"...`, 'info');
    try {
        await requestBookTemplates('almaden_update_book_template', (data) => {
            data.append('template_id', template.id);
            data.append('template_name', template.name);
            data.append('template_description', template.description || '');
            data.append('settings', JSON.stringify(buildCurrentBookTemplateSettings()));
        });
        cachedBookTemplates = null;
        await loadBookTemplates();
        switchBookTemplateGroup('personal');
        setBookTemplateStatus(`Plantilla "${template.name}" actualizada.`, 'success');
    } catch (error) {
        console.error(error);
        setBookTemplateStatus(error.message || 'No se pudo actualizar la plantilla.', 'error');
    }
}

async function promoteBookTemplateToStandard(templateId) {
    const template = cachedBookTemplates?.find((item) => item.id === templateId && item.origin === 'user');
    if (!template || !template.can_promote) return;
    if (!confirm('¿Convertir esta plantilla en estándar?')) {
        return;
    }

    setBookTemplateStatus(`Convirtiendo "${template.name}" en estándar...`, 'info');
    try {
        await requestBookTemplates('almaden_promote_book_template_to_standard', (data) => {
            data.append('template_id', template.id);
        });
        cachedBookTemplates = null;
        await loadBookTemplates();
        switchBookTemplateGroup('system');
        setBookTemplateStatus(`Plantilla "${template.name}" convertida en estándar.`, 'success');
    } catch (error) {
        console.error(error);
        setBookTemplateStatus(error.message || 'No se pudo convertir la plantilla en estándar.', 'error');
    }
}

async function deleteBookTemplate(templateId) {
    const template = cachedBookTemplates?.find((item) => item.id === templateId && item.origin === 'user');
    if (!template) return;
    if (!confirm(`¿Eliminar definitivamente la plantilla "${template.name}"?`)) {
        return;
    }

    setBookTemplateStatus(`Eliminando "${template.name}"...`, 'info');
    try {
        await requestBookTemplates('almaden_delete_book_template', (data) => data.append('template_id', template.id));
        cachedBookTemplates = null;
        await loadBookTemplates();
        switchBookTemplateGroup('personal');
        setBookTemplateStatus('Plantilla eliminada.', 'success');
    } catch (error) {
        console.error(error);
        setBookTemplateStatus(error.message || 'No se pudo eliminar la plantilla.', 'error');
    }
}

async function downloadBookTemplate(templateId) {
    const { context, data } = createBookTemplateRequestData('almaden_download_book_template');
    data.append('template_id', templateId);

    try {
        const response = await fetch(context.ajaxUrl, { method: 'POST', body: data });
        if (!response.ok) {
            const raw = await response.text();
            let message = 'No se pudo descargar la plantilla.';
            try {
                const payload = JSON.parse(raw);
                message = payload?.data || payload?.message || message;
            } catch (error) {
                if (raw) message = raw;
            }
            throw new Error(message);
        }

        const disposition = response.headers.get('content-disposition') || '';
        const match = disposition.match(/filename="?([^";]+)"?/i);
        const filename = match ? match[1] : 'book-template.json';
        const blobUrl = URL.createObjectURL(await response.blob());
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(blobUrl);
    } catch (error) {
        console.error(error);
        setBookTemplateStatus(error.message || 'No se pudo descargar la plantilla.', 'error');
    }
}

function promptUploadBookTemplate() {
    const input = document.getElementById('book-template-upload-input');
    if (!input) return;
    input.value = '';
    input.click();
}

async function handleBookTemplateUpload(event) {
    const input = event?.target || null;
    const file = input?.files?.[0] || null;
    if (!file) return;

    setBookTemplateStatus('Importando plantilla JSON...', 'info');
    try {
        const payload = await requestBookTemplates('almaden_upload_book_template', (data) => {
            data.append('template_file', file);
        });
        cachedBookTemplates = null;
        activeBookTemplateGroup = 'personal';
        await loadBookTemplates();
        switchBookTemplateGroup('personal');
        setBookTemplateStatus(`Plantilla "${payload.data.template.name}" importada con éxito.`, 'success');
    } catch (error) {
        console.error(error);
        setBookTemplateStatus(error.message || 'No se pudo importar la plantilla.', 'error');
    } finally {
        if (input) input.value = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('btn-tab-templates')?.addEventListener('click', loadBookTemplates);
    document.querySelectorAll('[data-book-template-group]').forEach((button) => {
        button.addEventListener('click', () => switchBookTemplateGroup(button.dataset.bookTemplateGroup));
    });
});

// Backward-compatible aliases for legacy callers.
function loadSettingsTemplates() { return loadBookTemplates(); }
function renderTemplates(templates) { return renderBookTemplates(templates); }
function applySettingsTemplate(templateId) { return applyBookTemplate(templateId); }
function promptSaveCurrentAsTemplate() { return promptSaveCurrentAsBookTemplate(); }
function deleteSettingsTemplate(templateId) { return deleteBookTemplate(templateId); }
