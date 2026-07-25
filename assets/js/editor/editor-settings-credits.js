let creditsMediaFrame = null;
let creditsRemoteSaveTimer = null;
let creditsRemoteSaveQueuedConfig = null;
let creditsRemoteSavePromise = Promise.resolve(null);
let creditsSuppressRemoteSave = false;

const CREDITS_ROLE_OPTIONS = [
    { value: 'author', label: 'Autor' },
    { value: 'coauthor', label: 'Coautor' },
    { value: 'editor', label: 'Editor' },
    { value: 'translator', label: 'Traductor' },
    { value: 'designer', label: 'Diseñador' },
    { value: 'proofreader', label: 'Corrector' },
    { value: 'photographer', label: 'Fotógrafo' },
    { value: 'other', label: 'Otro' },
];

const CREDITS_COMPANY_TYPE_OPTIONS = [
    { value: 'company', label: 'Empresa' },
    { value: 'foundation', label: 'Fundación' },
    { value: 'patron', label: 'Mecenas' },
    { value: 'university', label: 'Universidad' },
];

const CREDITS_LOGO_POSITION_OPTIONS = [
    { value: 'left', label: 'Izquierda' },
    { value: 'center', label: 'Centro' },
    { value: 'right', label: 'Derecha' },
];

const CREDITS_LICENSE_OPTIONS = [
    { value: 'all_rights_reserved', label: 'Todos los derechos reservados' },
    { value: 'creative_commons', label: 'Creative Commons' },
];

function creditsEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function creditsOptionMarkup(options, selected) {
    return options.map((option) => (
        `<option value="${creditsEscapeHtml(option.value)}"${String(selected) === option.value ? ' selected' : ''}>${creditsEscapeHtml(option.label)}</option>`
    )).join('');
}

function creditsNormalizeLogoPosition(value) {
    const normalized = String(value || '').trim().toLowerCase();
    if (['left', 'center', 'right'].includes(normalized)) {
        return normalized;
    }
    return 'center';
}

function creditsNormalizeLogoSize(value) {
    const parsed = parseInt(value, 10);
    if (!Number.isFinite(parsed)) {
        return 120;
    }
    return Math.min(Math.max(parsed, 24), 400);
}

function creditsNormalizePublicationDate(value) {
    const raw = String(value || '').trim();
    if (!raw) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        return raw.slice(0, 7);
    }
    if (/^\d{4}-\d{2}$/.test(raw)) {
        return raw;
    }
    return raw;
}

function creditsLogoPositionJustify(value) {
    const normalized = creditsNormalizeLogoPosition(value);
    if (normalized === 'left') return 'flex-start';
    if (normalized === 'right') return 'flex-end';
    return 'center';
}

function creditsGetStorageKey() {
    const bookId = typeof bookState !== 'undefined' && bookState && bookState.bookId ? bookState.bookId : 'default';
    return `almaden_credits_config_${bookId}`;
}

function creditsGetAjaxEndpoint() {
    if (typeof bookState !== 'undefined' && bookState && bookState.ajaxUrl) {
        return bookState.ajaxUrl;
    }
    if (typeof ajaxurl !== 'undefined' && ajaxurl) {
        return ajaxurl;
    }
    return '';
}

function creditsGetDefaultConfig() {
    return {
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
        logos: [],
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
    const logosSource = Array.isArray(source.logos) ? source.logos : [];

    const config = JSON.parse(JSON.stringify(defaults));
    config.editorial.edition_number = String(editorialSource.edition_number || source.credits_edition || '').trim();
    config.editorial.publication_date = creditsNormalizePublicationDate(editorialSource.publication_date || source.credits_date || '');
    config.editorial.isbn = String(editorialSource.isbn || source.credits_isbn || '').trim();
    config.editorial.printer = String(editorialSource.printer || source.credits_printer || '').trim();
    config.editorial.blank_before = Number.isFinite(parseInt(editorialSource.blank_before ?? source.credits_blank_before ?? 0, 10))
        ? Math.max(0, parseInt(editorialSource.blank_before ?? source.credits_blank_before ?? 0, 10))
        : 0;
    config.editorial.blank_after = Number.isFinite(parseInt(editorialSource.blank_after ?? source.credits_blank_after ?? 0, 10))
        ? Math.max(0, parseInt(editorialSource.blank_after ?? source.credits_blank_after ?? 0, 10))
        : 0;

    config.people = peopleSource
        .map((item) => {
            if (!item || typeof item !== 'object') return null;
            const name = String(item.name || '').trim();
            const role = CREDITS_ROLE_OPTIONS.some((opt) => opt.value === String(item.role || 'author'))
                ? String(item.role || 'author')
                : 'author';
            const email = String(item.email || '').trim();
            const website = String(item.website || '').trim();
            const showContact = item.show_contact === true || item.show_contact === 1 || item.show_contact === '1';
            if (!name && !email && !website) return null;
            return {
                name,
                role,
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

    config.logos = logosSource
        .map((item) => {
            if (!item || typeof item !== 'object') return null;
            const logoUrl = String(item.logo_url || item.image_url || item.url || '').trim();
            const name = String(item.name || '').trim();
            const position = creditsNormalizeLogoPosition(item.position || item.align || 'center');
            const size_px = creditsNormalizeLogoSize(item.size_px ?? item.size ?? 120);
            const url = String(item.url || item.website || '').trim();
            if (!logoUrl && !name && !url) return null;
            return {
                logo_url: logoUrl,
                name,
                position,
                size_px,
                url,
            };
        })
        .filter(Boolean);

    config.legal.copyright_text = String(legalSource.copyright_text || source.credits_copyright || defaults.legal.copyright_text).trim();
    config.legal.license = CREDITS_LICENSE_OPTIONS.some((opt) => opt.value === String(legalSource.license || source.credits_license || defaults.legal.license))
        ? String(legalSource.license || source.credits_license || defaults.legal.license)
        : defaults.legal.license;

    return config;
}

function creditsConfigToLegacy(config) {
    const normalized = creditsNormalizeConfig(config);
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
            }))
        ),
    };
}

function creditsScheduleRemoteSave(config) {
    if (creditsSuppressRemoteSave) return;
    if (typeof bookState === 'undefined' || !bookState || !bookState.bookId) return;

    creditsRemoteSaveQueuedConfig = creditsNormalizeConfig(config);
    if (window.almadenCreditsDebug) {
        window.almadenCreditsDebug.log('save_scheduled', window.almadenCreditsDebug.summarize(creditsRemoteSaveQueuedConfig));
    }

    if (creditsRemoteSaveTimer) {
        clearTimeout(creditsRemoteSaveTimer);
    }

    creditsRemoteSaveTimer = setTimeout(() => {
        creditsRemoteSaveTimer = null;
        creditsFlushRemoteSave().catch((error) => {
            console.warn('No se pudo guardar la configuración de créditos:', error);
        });
    }, 900);
}

function creditsPersistRemoteConfig(config) {
    const endpoint = creditsGetAjaxEndpoint();
    if (!endpoint) {
        return Promise.reject(new Error('No se encontró el endpoint AJAX para guardar los créditos.'));
    }

    const debug = window.almadenCreditsDebug || null;
    const traceId = debug ? debug.createTraceId(bookState.bookId) : '';
    const payload = new FormData();
    payload.append('action', 'almaden_save_credits_config');
    payload.append('book_id', String(bookState.bookId));
    payload.append('nonce', String(bookState.nonce || ''));
    payload.append('credits_config', JSON.stringify(config));
    payload.append('credits_debug_trace_id', traceId);

    if (debug) {
        debug.log('request_started', {
            trace_id: traceId,
            endpoint,
            config: debug.summarize(config),
        });
    }

    return fetch(endpoint, {
        method: 'POST',
        body: payload,
    })
        .then((response) => {
            if (debug) {
                debug.log('response_received', {
                    trace_id: traceId,
                    http_status: response.status,
                    ok: response.ok,
                });
            }
            if (!response.ok) {
                throw new Error(`El servidor respondió con HTTP ${response.status}.`);
            }
            return response.json();
        })
        .then((res) => {
            if (!res || !res.success || !res.data || !res.data.credits_config) {
                const message = res && res.data && res.data.message
                    ? String(res.data.message)
                    : 'WordPress no confirmó el guardado de los créditos.';
                throw new Error(message);
            }

            const canonical = creditsNormalizeConfig(res.data.credits_config);
            if (typeof bookState !== 'undefined' && bookState && bookState.settings) {
                bookState.settings.credits_config = canonical;
                Object.assign(bookState.settings, creditsConfigToLegacy(canonical));
            }

            try {
                localStorage.setItem(creditsGetStorageKey(), JSON.stringify(canonical));
            } catch (error) {
                // Ignoramos errores de storage para no romper la edición.
            }

            if (typeof refreshEditorDisplay === 'function') {
                refreshEditorDisplay(false);
            }
            if (debug) {
                debug.log('save_confirmed', {
                    trace_id: traceId,
                    config: debug.summarize(canonical),
                    server_debug: res.data.debug || null,
                });
            }
            return canonical;
        })
        .catch((error) => {
            if (debug) {
                debug.log('save_failed', {
                    trace_id: traceId,
                    message: error instanceof Error ? error.message : String(error),
                    config: debug.summarize(config),
                });
            }
            throw error;
        });
}

function creditsFlushRemoteSave() {
    if (!creditsRemoteSaveQueuedConfig) {
        return creditsRemoteSavePromise;
    }

    const config = creditsRemoteSaveQueuedConfig;
    creditsRemoteSaveQueuedConfig = null;

    // Serializa las escrituras para que una respuesta antigua nunca pise
    // una configuración más reciente que todavía está en cola.
    creditsRemoteSavePromise = creditsRemoteSavePromise
        .catch(() => null)
        .then(() => creditsPersistRemoteConfig(config));

    return creditsRemoteSavePromise;
}

function creditsForceRemoteSave(config) {
    if (creditsSuppressRemoteSave) return Promise.resolve(null);
    if (typeof bookState === 'undefined' || !bookState || !bookState.bookId) return Promise.resolve(null);

    if (creditsRemoteSaveTimer) {
        clearTimeout(creditsRemoteSaveTimer);
        creditsRemoteSaveTimer = null;
    }

    creditsRemoteSaveQueuedConfig = creditsNormalizeConfig(config || creditsGetConfigFromForm());
    return creditsFlushRemoteSave();
}

function creditsGetFieldValue(root, selector, fallback = '') {
    const el = root ? root.querySelector(selector) : null;
    return el ? el.value : fallback;
}

function creditsGetFieldChecked(root, selector) {
    const el = root ? root.querySelector(selector) : null;
    return el && el.checked ? 1 : 0;
}

function creditsSetInputValue(root, selector, value) {
    const el = root ? root.querySelector(selector) : null;
    if (!el) return;
    el.value = value ?? '';
}

function creditsSetCheckboxValue(root, selector, value) {
    const el = root ? root.querySelector(selector) : null;
    if (!el) return;
    el.checked = value === 1 || value === '1' || value === true;
}

function creditsBuildPersonRow(person = {}) {
    const selectedRole = CREDITS_ROLE_OPTIONS.some((opt) => opt.value === String(person.role || 'author'))
        ? String(person.role || 'author')
        : 'author';
    return `
        <div class="credits-person-row rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/40 p-4 space-y-4" data-credits-row="person">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Nombre</label>
                    <input type="text" data-credits-field="name" value="${creditsEscapeHtml(person.name || '')}" placeholder="Fernando Villegas" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Rol</label>
                    <select data-credits-field="role" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        ${creditsOptionMarkup(CREDITS_ROLE_OPTIONS, selectedRole)}
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Email</label>
                    <input type="email" data-credits-field="email" value="${creditsEscapeHtml(person.email || '')}" placeholder="correo@dominio.com" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Sitio Web</label>
                    <input type="url" data-credits-field="website" value="${creditsEscapeHtml(person.website || '')}" placeholder="https://..." class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                </div>
            </div>
            <div class="flex items-center justify-between gap-4 pt-1">
                <label class="inline-flex items-center gap-3 text-sm text-[var(--text-main)] font-medium">
                    <input type="checkbox" data-credits-field="show_contact" ${person.show_contact ? 'checked' : ''} class="h-4 w-4 rounded border-[var(--border-color)] text-black focus:ring-black">
                    Mostrar contacto en página de créditos
                </label>
                <button type="button" data-credits-action="remove-row" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-100 transition">
                    <i class="fa-solid fa-trash-can"></i>
                    Eliminar
                </button>
            </div>
        </div>
    `;
}

function creditsBuildCollaboratorRow(item = {}) {
    const logoUrl = item.logo_url || '';
    return `
        <div class="credits-collaborator-row rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/40 p-4 space-y-4" data-credits-row="collaborator">
            <div class="grid grid-cols-1 md:grid-cols-[160px_1fr] gap-4">
                <div class="space-y-2">
                    <div class="overflow-hidden rounded-xl border border-[var(--border-color)] bg-white p-2">
                        <img data-credits-preview-image src="${logoUrl ? creditsEscapeHtml(logoUrl) : 'data:image/gif;base64,R0lGODlhAQABAAAAACw='}" alt="" class="h-16 w-full rounded-xl object-contain bg-white border border-[var(--border-color)] p-2${logoUrl ? '' : ' hidden'}">
                        <div data-credits-preview-placeholder class="flex h-16 w-full items-center justify-center rounded-xl border border-dashed border-[var(--border-color)] bg-white text-[11px] font-semibold text-[var(--text-muted)]${logoUrl ? ' hidden' : ''}">Sin logo</div>
                    </div>
                    <button type="button" data-credits-action="choose-image" class="w-full rounded-xl bg-black px-3 py-2 text-xs font-semibold text-white hover:bg-neutral-800 transition" data-credits-target-input="logo_url" data-credits-target-preview="[data-credits-preview-image]">
                        <i class="fa-solid fa-image mr-1"></i>
                        Seleccionar logo
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Nombre empresa</label>
                        <input type="text" data-credits-field="name" value="${creditsEscapeHtml(item.name || '')}" placeholder="Editorial XXXXX" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Tipo</label>
                        <select data-credits-field="type" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            ${creditsOptionMarkup(CREDITS_COMPANY_TYPE_OPTIONS, item.type || 'company')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Sitio Web</label>
                        <input type="url" data-credits-field="website" value="${creditsEscapeHtml(item.website || '')}" placeholder="https://..." class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Texto opcional</label>
                        <input type="text" data-credits-field="text" value="${creditsEscapeHtml(item.text || '')}" placeholder="Agradecimiento o detalle" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">URL de logo</label>
                        <input type="url" data-credits-field="logo_url" value="${creditsEscapeHtml(item.logo_url || '')}" placeholder="https://..." class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" data-credits-action="remove-row" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-100 transition">
                    <i class="fa-solid fa-trash-can"></i>
                    Eliminar
                </button>
            </div>
        </div>
    `;
}

function creditsBuildLogoRow(item = {}) {
    const logoUrl = item.logo_url || '';
    const logoPosition = creditsNormalizeLogoPosition(item.position || 'center');
    const logoSize = creditsNormalizeLogoSize(item.size_px || 120);
    const logoJustify = creditsLogoPositionJustify(logoPosition);
    const hasLogo = !!logoUrl;
    return `
        <div class="credits-logo-row rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/40 p-4 space-y-4" data-credits-row="logo">
            <div class="grid grid-cols-1 md:grid-cols-[190px_1fr] gap-4">
                <div class="space-y-2">
                    <div data-credits-logo-preview-box class="flex min-h-36 w-full overflow-hidden rounded-xl border border-[var(--border-color)] bg-white p-3" style="justify-content: ${logoJustify}; align-items: center;">
                        <img data-credits-preview-image src="${hasLogo ? creditsEscapeHtml(logoUrl) : 'data:image/gif;base64,R0lGODlhAQABAAAAACw='}" alt="" class="rounded-xl object-contain bg-white border border-[var(--border-color)] p-2${hasLogo ? '' : ' hidden'}" style="width: ${logoSize}px; height: auto; max-width: 100%;">
                        <div data-credits-preview-placeholder class="flex h-20 w-full items-center justify-center rounded-xl border border-dashed border-[var(--border-color)] bg-white text-[11px] font-semibold text-[var(--text-muted)]${hasLogo ? ' hidden' : ''}">Sin imagen</div>
                    </div>
                    <button type="button" data-credits-action="choose-image" class="w-full rounded-xl bg-black px-3 py-2 text-xs font-semibold text-white hover:bg-neutral-800 transition" data-credits-target-input="logo_url" data-credits-target-preview="[data-credits-preview-image]">
                        <i class="fa-solid fa-image mr-1"></i>
                        Subir logo
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Nombre</label>
                        <input type="text" data-credits-field="name" value="${creditsEscapeHtml(item.name || '')}" placeholder="Logo editorial" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Posición</label>
                        <select data-credits-field="position" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            ${creditsOptionMarkup(CREDITS_LOGO_POSITION_OPTIONS, logoPosition)}
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)]">Tamaño (px)</label>
                            <span data-credits-size-label class="text-[11px] font-semibold text-[var(--text-muted)]">${logoSize} px</span>
                        </div>
                        <input type="range" data-credits-field="size_px" min="24" max="400" step="1" value="${creditsEscapeHtml(logoSize)}" class="w-full accent-black">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">URL</label>
                        <input type="url" data-credits-field="url" value="${creditsEscapeHtml(item.url || '')}" placeholder="https://..." class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">URL de imagen</label>
                        <input type="url" data-credits-field="logo_url" value="${creditsEscapeHtml(item.logo_url || '')}" placeholder="https://..." class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" data-credits-action="remove-row" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-100 transition">
                    <i class="fa-solid fa-trash-can"></i>
                    Eliminar
                </button>
            </div>
        </div>
    `;
}

function creditsBuildEditorMarkup(config) {
    const editorial = config.editorial || creditsGetDefaultConfig().editorial;
    const legal = config.legal || creditsGetDefaultConfig().legal;
    const people = Array.isArray(config.people) ? config.people : [];
    const collaborators = Array.isArray(config.collaborators) ? config.collaborators : [];
    const logos = Array.isArray(config.logos) ? config.logos : [];

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
                    <button type="button" data-credits-tab="editorial" class="credits-tab-btn rounded-xl border px-4 py-2 text-sm font-semibold transition bg-black text-white border-black">Editorial</button>
                    <button type="button" data-credits-tab="people" class="credits-tab-btn rounded-xl border px-4 py-2 text-sm font-semibold transition bg-white text-[var(--text-main)] border-[var(--border-color)]">Personas</button>
                    <button type="button" data-credits-tab="collaborators" class="credits-tab-btn rounded-xl border px-4 py-2 text-sm font-semibold transition bg-white text-[var(--text-main)] border-[var(--border-color)]">Colaboradores</button>
                    <button type="button" data-credits-tab="logos" class="credits-tab-btn rounded-xl border px-4 py-2 text-sm font-semibold transition bg-white text-[var(--text-main)] border-[var(--border-color)]">Logos</button>
                    <button type="button" data-credits-tab="legal" class="credits-tab-btn rounded-xl border px-4 py-2 text-sm font-semibold transition bg-white text-[var(--text-main)] border-[var(--border-color)]">Legal</button>
                </div>
            </div>

            <div class="px-6 py-6 space-y-6">
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
                </section>

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
                        ${people.length ? people.map((person) => creditsBuildPersonRow(person)).join('') : creditsBuildPersonRow()}
                    </div>
                </section>

                <section data-credits-panel="collaborators" class="hidden space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h5 class="text-lg font-bold text-[var(--text-main)]">Colaboradores</h5>
                            <p class="text-sm text-[var(--text-muted)]">Sellos, fundaciones, mecenas o entidades asociadas.</p>
                        </div>
                        <button type="button" data-credits-action="add-collaborator" class="rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-800 transition">
                            <i class="fa-solid fa-plus mr-1"></i> Agregar colaborador
                        </button>
                    </div>
                    <div id="credits-collaborators-container" class="space-y-3">
                        ${collaborators.length ? collaborators.map((item) => creditsBuildCollaboratorRow(item)).join('') : ''}
                    </div>
                </section>

                <section data-credits-panel="logos" class="hidden space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h5 class="text-lg font-bold text-[var(--text-main)]">Logos</h5>
                            <p class="text-sm text-[var(--text-muted)]">Logos adicionales que quieres registrar para la página de créditos.</p>
                        </div>
                        <button type="button" data-credits-action="add-logo" class="rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-800 transition">
                            <i class="fa-solid fa-plus mr-1"></i> Agregar logo
                        </button>
                    </div>
                    <div id="credits-logos-container" class="space-y-3">
                        ${logos.length ? logos.map((item) => creditsBuildLogoRow(item)).join('') : ''}
                    </div>
                </section>

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
                </section>
            </div>
        </div>
    `;
}

function creditsRenderTabState(root, activeTab) {
    const tabs = root ? root.querySelectorAll('[data-credits-tab]') : [];
    const panels = root ? root.querySelectorAll('[data-credits-panel]') : [];
    tabs.forEach((tab) => {
        const isActive = tab.getAttribute('data-credits-tab') === activeTab;
        tab.classList.toggle('bg-black', isActive);
        tab.classList.toggle('text-white', isActive);
        tab.classList.toggle('border-black', isActive);
        tab.classList.toggle('bg-white', !isActive);
        tab.classList.toggle('text-[var(--text-main)]', !isActive);
        tab.classList.toggle('border-[var(--border-color)]', !isActive);
    });
    panels.forEach((panel) => {
        panel.classList.toggle('hidden', panel.getAttribute('data-credits-panel') !== activeTab);
    });
    if (root) {
        root.dataset.activeCreditsTab = activeTab;
    }
}

function creditsAppendRow(rowType, data = {}) {
    const containerMap = {
        person: '#credits-people-container',
        collaborator: '#credits-collaborators-container',
        logo: '#credits-logos-container',
    };
    const renderMap = {
        person: creditsBuildPersonRow,
        collaborator: creditsBuildCollaboratorRow,
        logo: creditsBuildLogoRow,
    };
    const container = document.querySelector(containerMap[rowType] || '');
    if (!container || !renderMap[rowType]) return;
    container.insertAdjacentHTML('beforeend', renderMap[rowType](data));
    creditsSyncStateFromForm();
}

function creditsEnsureMediaFrame(onSelect) {
    if (typeof wp === 'undefined' || !wp.media) {
        alert('La biblioteca multimedia de WordPress no está disponible.');
        return null;
    }

    creditsMediaFrame = wp.media({
        title: 'Seleccionar imagen',
        button: {
            text: 'Usar imagen',
        },
        multiple: false,
    });

    creditsMediaFrame.on('select', () => {
        const attachment = creditsMediaFrame.state().get('selection').first().toJSON();
        if (attachment && attachment.url && typeof onSelect === 'function') {
            onSelect(attachment.url);
        }
    });

    return creditsMediaFrame;
}

function creditsUpdateImagePreview(inputEl) {
    if (!inputEl) return;
    const row = inputEl.closest('[data-credits-row]');
    if (!row) return;
    const preview = row.querySelector('[data-credits-preview-image]');
    const placeholder = row.querySelector('[data-credits-preview-placeholder]');
    const value = String(inputEl.value || '').trim();
    if (preview) {
        preview.src = value || 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
        preview.classList.toggle('hidden', !value);
    }
    if (placeholder) {
        placeholder.classList.toggle('hidden', !!value);
    }
}

function creditsUpdateLogoRowPreview(row) {
    if (!row || row.getAttribute('data-credits-row') !== 'logo') return;
    const positionInput = row.querySelector('[data-credits-field="position"]');
    const sizeInput = row.querySelector('[data-credits-field="size_px"]');
    const logoInput = row.querySelector('[data-credits-field="logo_url"]');
    const previewBox = row.querySelector('[data-credits-logo-preview-box]');
    const preview = row.querySelector('[data-credits-preview-image]');
    const placeholder = row.querySelector('[data-credits-preview-placeholder]');
    const sizeLabel = row.querySelector('[data-credits-size-label]');
    const position = creditsNormalizeLogoPosition(positionInput ? positionInput.value : 'center');
    const size = creditsNormalizeLogoSize(sizeInput ? sizeInput.value : 120);
    const hasLogo = !!(logoInput && String(logoInput.value || '').trim());

    if (previewBox) {
        previewBox.style.justifyContent = creditsLogoPositionJustify(position);
    }
    if (preview) {
        preview.style.width = `${size}px`;
        preview.style.height = 'auto';
        preview.style.maxWidth = '100%';
        preview.classList.toggle('hidden', !hasLogo);
    }
    if (placeholder) {
        placeholder.classList.toggle('hidden', hasLogo);
    }
    if (sizeLabel) {
        sizeLabel.textContent = `${size} px`;
    }
}

function creditsReadRepeaterRows(containerSelector, rowType) {
    const container = document.querySelector(containerSelector);
    if (!container) return [];
    return Array.from(container.querySelectorAll(`[data-credits-row="${rowType}"]`)).map((row) => {
        const getField = (field) => {
            const input = row.querySelector(`[data-credits-field="${field}"]`);
            if (!input) return '';
            if (input.type === 'checkbox') return input.checked ? 1 : 0;
            return String(input.value || '').trim();
        };

        if (rowType === 'person') {
            const item = {
                name: getField('name'),
                role: getField('role') || 'author',
                email: getField('email'),
                website: getField('website'),
                show_contact: getField('show_contact') ? 1 : 0,
            };
            return (item.name || item.email || item.website) ? item : null;
        }

        if (rowType === 'collaborator') {
            const item = {
                logo_url: getField('logo_url'),
                name: getField('name'),
                type: getField('type') || 'company',
                website: getField('website'),
                text: getField('text'),
            };
            return (item.logo_url || item.name || item.website || item.text) ? item : null;
        }

        const item = {
            logo_url: getField('logo_url'),
            name: getField('name'),
            position: creditsNormalizeLogoPosition(getField('position') || 'center'),
            size_px: creditsNormalizeLogoSize(getField('size_px') || 120),
            url: getField('url'),
        };
        return (item.logo_url || item.name || item.url) ? item : null;
    }).filter(Boolean);
}

function creditsGetConfigFromForm() {
    const root = document.getElementById('credits-editor-root');
    if (!root) {
        return creditsNormalizeConfig(bookState.settings.credits_config || bookState.settings || {});
    }

    const config = creditsGetDefaultConfig();
    config.editorial.edition_number = creditsGetFieldValue(root, '#setting-credits-edition');
    config.editorial.publication_date = creditsNormalizePublicationDate(creditsGetFieldValue(root, '#setting-credits-date'));
    config.editorial.isbn = creditsGetFieldValue(root, '#setting-credits-isbn');
    config.editorial.printer = creditsGetFieldValue(root, '#setting-credits-printer');
    config.editorial.blank_before = parseInt(creditsGetFieldValue(root, '#setting-credits-blank-before', '0'), 10) || 0;
    config.editorial.blank_after = parseInt(creditsGetFieldValue(root, '#setting-credits-blank-after', '0'), 10) || 0;
    config.people = creditsReadRepeaterRows('#credits-people-container', 'person');
    config.collaborators = creditsReadRepeaterRows('#credits-collaborators-container', 'collaborator');
    config.logos = creditsReadRepeaterRows('#credits-logos-container', 'logo');
    config.legal.copyright_text = creditsGetFieldValue(root, '#setting-credits-copyright', config.legal.copyright_text);
    config.legal.license = creditsGetFieldValue(root, '#setting-credits-license', 'all_rights_reserved') || 'all_rights_reserved';

    return creditsNormalizeConfig(config);
}

function creditsSyncStateFromForm() {
    if (typeof bookState === 'undefined' || !bookState) return;
    const config = creditsGetConfigFromForm();
    const legacy = creditsConfigToLegacy(config);
    bookState.settings.credits_config = config;
    Object.assign(bookState.settings, legacy);
    try {
        localStorage.setItem(creditsGetStorageKey(), JSON.stringify(config));
    } catch (error) {
        // Ignore storage failures and keep the server-backed state.
    }
    creditsScheduleRemoteSave(config);
    if (typeof refreshEditorDisplay === 'function') {
        refreshEditorDisplay(false);
    }
}

function creditsBindRootEvents(root) {
    if (!root || root.dataset.creditsBound === '1') return;

    root.addEventListener('click', (event) => {
        const tabButton = event.target.closest('[data-credits-tab]');
        if (tabButton && root.contains(tabButton)) {
            event.preventDefault();
            creditsRenderTabState(root, tabButton.getAttribute('data-credits-tab'));
            return;
        }

        const actionButton = event.target.closest('[data-credits-action]');
        if (!actionButton || !root.contains(actionButton)) return;
        const action = actionButton.getAttribute('data-credits-action');

        if (action === 'add-person') {
            event.preventDefault();
            creditsAppendRow('person');
            creditsRenderTabState(root, 'people');
            return;
        }

        if (action === 'add-collaborator') {
            event.preventDefault();
            creditsAppendRow('collaborator');
            creditsRenderTabState(root, 'collaborators');
            return;
        }

        if (action === 'add-logo') {
            event.preventDefault();
            creditsAppendRow('logo');
            creditsRenderTabState(root, 'logos');
            return;
        }

        if (action === 'remove-row') {
            event.preventDefault();
            const row = actionButton.closest('[data-credits-row]');
            if (row) {
                row.remove();
                const container = actionButton.closest('[id$="-container"]');
                if (container && container.children.length === 0 && container.id === 'credits-people-container') {
                    container.insertAdjacentHTML('beforeend', creditsBuildPersonRow());
                }
                creditsSyncStateFromForm();
            }
            return;
        }

        if (action === 'choose-image') {
            event.preventDefault();
            const row = actionButton.closest('[data-credits-row]');
            if (!row) return;
            const targetInputName = actionButton.getAttribute('data-credits-target-input') || 'logo_url';
            const targetPreviewSelector = actionButton.getAttribute('data-credits-target-preview') || '[data-credits-preview-image]';
            const targetInput = row.querySelector(`[data-credits-field="${targetInputName}"]`);
            const preview = row.querySelector(targetPreviewSelector);
            const mediaFrame = creditsEnsureMediaFrame((url) => {
                if (targetInput) targetInput.value = url;
                if (preview) {
                    preview.src = url;
                    preview.classList.remove('hidden');
                }
                const placeholder = row.querySelector('[data-credits-preview-placeholder]');
                if (placeholder) placeholder.classList.add('hidden');
                creditsUpdateLogoRowPreview(row);
                creditsSyncStateFromForm();
                creditsForceRemoteSave(creditsGetConfigFromForm()).catch((error) => {
                    console.warn('No se pudo guardar el logo de créditos:', error);
                });
            });
            if (mediaFrame) {
                mediaFrame.open();
            }
        }
    });

    root.addEventListener('input', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLElement)) return;
        if (!input.matches('[data-credits-field], #setting-credits-edition, #setting-credits-date, #setting-credits-isbn, #setting-credits-printer, #setting-credits-blank-before, #setting-credits-blank-after, #setting-credits-copyright, #setting-credits-license')) return;
        const field = input.getAttribute('data-credits-field');
        if (field === 'logo_url') {
            creditsUpdateImagePreview(input);
        }
        if (field === 'logo_url' || field === 'position' || field === 'size_px') {
            creditsUpdateLogoRowPreview(input.closest('[data-credits-row]'));
        }
        creditsSyncStateFromForm();
    });

    root.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLElement)) return;
        if (!input.matches('[data-credits-field], #setting-credits-edition, #setting-credits-date, #setting-credits-isbn, #setting-credits-printer, #setting-credits-blank-before, #setting-credits-blank-after, #setting-credits-copyright, #setting-credits-license')) return;
        const field = input.getAttribute('data-credits-field');
        if (field === 'logo_url') {
            creditsUpdateImagePreview(input);
        }
        if (field === 'logo_url' || field === 'position' || field === 'size_px') {
            creditsUpdateLogoRowPreview(input.closest('[data-credits-row]'));
        }
        creditsSyncStateFromForm();
    });

    root.dataset.creditsBound = '1';
}

function creditsPopulateForm(config) {
    const root = document.getElementById('credits-editor-root');
    if (!root) return;
    const normalized = creditsNormalizeConfig(config);
    creditsSuppressRemoteSave = true;
    try {
        root.innerHTML = creditsBuildEditorMarkup(normalized);
        creditsBindRootEvents(root);
        creditsRenderTabState(root, root.dataset.activeCreditsTab || 'editorial');
        creditsSyncStateFromForm();
    } finally {
        creditsSuppressRemoteSave = false;
    }
}

window.almadenGetCreditsRoleLabel = function(value) {
    const found = CREDITS_ROLE_OPTIONS.find((option) => option.value === String(value || ''));
    return found ? found.label : 'Autor';
};

window.almadenGetCreditsCompanyTypeLabel = function(value) {
    const found = CREDITS_COMPANY_TYPE_OPTIONS.find((option) => option.value === String(value || ''));
    return found ? found.label : 'Empresa';
};

window.almadenGetCreditsLogoPositionLabel = function(value) {
    const found = CREDITS_LOGO_POSITION_OPTIONS.find((option) => option.value === String(value || ''));
    return found ? found.label : 'Centro';
};

window.almadenGetCreditsLicenseLabel = function(value) {
    const found = CREDITS_LICENSE_OPTIONS.find((option) => option.value === String(value || ''));
    return found ? found.label : 'Todos los derechos reservados';
};

window.almadenGetCreditsConfigDefaults = creditsGetDefaultConfig;
window.almadenNormalizeCreditsConfig = creditsNormalizeConfig;
window.almadenCreditsConfigToLegacy = creditsConfigToLegacy;

window.getCreditsConfigFromForm = function() {
    return creditsGetConfigFromForm();
};

window.getCreditsConfigJSON = function() {
    return JSON.stringify(creditsGetConfigFromForm());
};

window.saveCreditsConfig = function(config) {
    return creditsForceRemoteSave(config || creditsGetConfigFromForm());
};

window.getCustomCreditsJSON = function() {
    const config = creditsGetConfigFromForm();
    return JSON.stringify(
        (config.people || []).map((person) => ({
            role: person.role || '',
            name: person.name || '',
        }))
    );
};

window.renderCustomCredits = function(creditsJSON) {
    const root = document.getElementById('credits-editor-root');
    if (!root) return;
    const current = creditsGetConfigFromForm();
    let incoming = creditsJSON;
    if (typeof incoming === 'string') {
        try {
            incoming = JSON.parse(incoming);
        } catch (error) {
            incoming = [];
        }
    }
    if (Array.isArray(incoming)) {
        current.people = incoming.map((item) => ({
            name: item && item.name ? String(item.name) : '',
            role: item && item.role ? String(item.role) : 'author',
            email: '',
            website: '',
            show_contact: 0,
        }));
    } else if (incoming && typeof incoming === 'object') {
        Object.assign(current, creditsNormalizeConfig(incoming));
    }
    creditsPopulateForm(current);
};

window.addCustomCreditRow = function(role = '', name = '') {
    creditsAppendRow('person', {
        role: role || 'author',
        name: name || '',
    });
};

window.initCreditsForm = function() {
    const root = document.getElementById('credits-editor-root');
    if (!root) return;

    // WordPress es la fuente autoritativa. La copia local solo se actualiza
    // después de recibir una confirmación válida del servidor.
    const serverConfig = creditsNormalizeConfig(bookState.settings.credits_config || bookState.settings || {});
    creditsPopulateForm(serverConfig);
    if (window.almadenCreditsDebug) {
        window.almadenCreditsDebug.log('form_initialized_from_server', {
            book_id: bookState.bookId,
            config: window.almadenCreditsDebug.summarize(serverConfig),
        });
    }

    try {
        localStorage.setItem(creditsGetStorageKey(), JSON.stringify(serverConfig));
    } catch (error) {
        // El editor puede funcionar aunque el navegador bloquee localStorage.
    }
};
