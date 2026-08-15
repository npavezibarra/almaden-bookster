// --- BOOK TEMPLATES ---
let cachedBookTemplates = null;

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

    const baseClasses = 'mt-2 text-[10px]';
    const toneClasses = {
        info: 'text-[var(--text-muted)]',
        success: 'text-emerald-600 dark:text-emerald-400',
        error: 'text-rose-600 dark:text-rose-400',
    };

    el.textContent = message;
    el.className = `${baseClasses} ${toneClasses[type] || toneClasses.info}`;
    el.classList.remove('hidden');
}

function setBookTemplateButtonLoading(isLoading) {
    const btn = getBookTemplateSaveButton();
    if (!btn) return;

    if (!btn.dataset.originalHtml) {
        btn.dataset.originalHtml = btn.innerHTML;
    }

    btn.disabled = isLoading;
    btn.classList.toggle('opacity-60', isLoading);
    btn.classList.toggle('cursor-wait', isLoading);

    if (isLoading) {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Guardando plantilla...</span>';
    } else {
        btn.innerHTML = btn.dataset.originalHtml;
    }
}

function getBookTemplatesAjaxNonce() {
    if (typeof window.almadenBookTemplatesNonce === 'string' && window.almadenBookTemplatesNonce) {
        return window.almadenBookTemplatesNonce;
    }

    return bookState && bookState.settingsNonce ? bookState.settingsNonce : '';
}

function buildCurrentBookTemplateSettings() {
    const getVal = (id, fallback = '') => {
        const el = document.getElementById(id);
        return el ? el.value : fallback;
    };

    const getCleanVal = (id) => {
        const el = document.getElementById(id);
        return el ? el.value.replace(',', '.') : '';
    };

    const parseVal = (id, fallback) => {
        const val = getCleanVal(id);
        const clean = parseFloat(val);
        return isNaN(clean) ? fallback : clean;
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
        : (bookState.settings?.credits_config || {});
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
            credits_custom: typeof getCustomCreditsJSON === 'function' ? getCustomCreditsJSON() : '[]'
        };

    const stateBuilder = typeof almadenBuildPDFSettingsState === 'function'
        ? almadenBuildPDFSettingsState
        : null;

    if (stateBuilder) {
        return stateBuilder({
            getVal,
            getCleanVal,
            getChecked,
            parseVal,
            getBookLanguage,
            getBookFlowMode,
            getLegacyParityFromFlowMode,
            creditsConfig,
            creditsLegacy
        });
    }

    return {
        page_size: getVal('setting-page-size'),
        chapter_title_font_family: getVal('setting-chapter-title-font-family'),
        chapter_title_font_size: parseVal('setting-chapter-title-font-size', 24),
        credits_config: creditsConfig,
        credits_edition: creditsLegacy.credits_edition || '',
        credits_date: creditsLegacy.credits_date || '',
        credits_isbn: creditsLegacy.credits_isbn || '',
        credits_copyright: creditsLegacy.credits_copyright || '',
        credits_printer: creditsLegacy.credits_printer || '',
        credits_blank_before: creditsLegacy.credits_blank_before ?? 0,
        credits_blank_after: creditsLegacy.credits_blank_after ?? 0,
        credits_license: creditsLegacy.credits_license || 'all_rights_reserved',
        credits_custom: creditsLegacy.credits_custom || '[]'
    };
}

function appendSettingsSnapshotToFormData(formData, snapshot) {
    Object.entries(snapshot || {}).forEach(([key, value]) => {
        if (value === undefined || value === null) return;
        if (typeof value === 'object') {
            formData.append(key, JSON.stringify(value));
            return;
        }
        formData.append(key, value);
    });
}

function loadBookTemplates() {
    const container = document.getElementById('templates-container');
    if (!container) return;

    if (cachedBookTemplates) {
        renderBookTemplates(cachedBookTemplates);
        return;
    }

    setBookTemplateStatus('Cargando plantillas de libro...', 'info');

    const data = new FormData();
    data.append('action', 'almaden_get_book_templates');
    data.append('book_id', bookState.bookId);
    const nonce = getBookTemplatesAjaxNonce();
    if (nonce) {
        data.append('nonce', nonce);
    }

    fetch(bookState.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {
        if (res.success && res.data && res.data.templates) {
            cachedBookTemplates = res.data.templates;
            renderBookTemplates(cachedBookTemplates);
            setBookTemplateStatus('');
        } else {
            container.innerHTML = '<div class="text-[10px] text-rose-500 italic">Error al cargar plantillas de libro.</div>';
            setBookTemplateStatus('No se pudieron cargar las plantillas de libro.', 'error');
        }
    })
    .catch(err => {
        console.error('Error fetching book templates:', err);
        container.innerHTML = '<div class="text-[10px] text-rose-500 italic">Error de conexión.</div>';
        setBookTemplateStatus('Error de conexión al cargar plantillas.', 'error');
    });
}

function renderBookTemplates(templates) {
    const container = document.getElementById('templates-container');
    if (!container) return;

    if (templates.length === 0) {
        container.innerHTML = '<div class="text-[10px] text-[var(--text-muted)] italic">No hay plantillas de libro disponibles.</div>';
        return;
    }

    container.innerHTML = '';
    templates.forEach(tpl => {
        const div = document.createElement('div');
        div.className = 'border border-[var(--border-color)] rounded-lg p-3 hover:bg-[var(--bg-sidebar)] transition cursor-pointer flex flex-col justify-between';
        div.innerHTML = `
            <div>
                <h5 class="text-xs font-bold text-[var(--text-main)] mb-1">${tpl.name}</h5>
                <div class="mt-2 flex flex-wrap gap-1">
                    <span class="inline-flex items-center rounded-full border border-[var(--border-color)] px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-[var(--text-muted)]">${tpl.visibility || 'private'}</span>
                    ${tpl.sample_chapters && tpl.sample_chapters.length ? '<span class="inline-flex items-center rounded-full border border-[var(--border-color)] px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-[var(--text-muted)]">Con muestras</span>' : ''}
                </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2 justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <button class="text-[10px] font-semibold text-white bg-black hover:bg-neutral-800 rounded px-3 py-1 transition" onclick="applyBookTemplate('${tpl.id}')">
                        Aplicar plantilla
                    </button>
                    <button class="text-[10px] font-semibold text-[var(--text-main)] bg-transparent border border-[var(--border-color)] hover:bg-[var(--bg-app)] rounded px-3 py-1 transition" onclick="downloadBookTemplate('${tpl.id}')" title="Descargar plantilla">
                        Descargar JSON
                    </button>
                </div>
                <button class="text-[10px] text-rose-500 hover:text-rose-700 transition" onclick="deleteBookTemplate('${tpl.id}')" title="Eliminar plantilla">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(div);
    });
}

function applyBookTemplate(templateId) {
    if (!cachedBookTemplates) return;
    const tpl = cachedBookTemplates.find(t => t.id === templateId);
    if (!tpl) return;

    if (!confirm(`¿Estás seguro de que deseas aplicar la plantilla "${tpl.name}"?\nEsto sobrescribirá tus configuraciones actuales de página, tipografía y cabeceras.`)) {
        return;
    }

    const s = tpl.settings;
    if (!s) return;

    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) {
            if (el.type === 'checkbox') {
                el.checked = val == 1;
            } else {
                el.value = val;
            }
            // Trigger change event just in case there are listeners
            el.dispatchEvent(new Event('change'));
        }
    };

    // Aplicar valores a los inputs del DOM
    Object.keys(s).forEach(key => {
        const id = 'setting-' + key.replace(/_/g, '-');
        setVal(id, s[key]);
    });

    // Guardar los ajustes en BD y forzar re-render (esto llamará a savePDFSettings -> update bookState -> compilePDFPreview)
    if (typeof savePDFSettings === 'function') {
        savePDFSettings();
    }
}

// Escuchar cuando se abre el modal o se cambia de pestaña para cargar plantillas
document.addEventListener('DOMContentLoaded', () => {
    const btnTemplates = document.getElementById('btn-tab-templates');
    if (btnTemplates) {
        btnTemplates.addEventListener('click', loadBookTemplates);
    }
});

function promptSaveCurrentAsBookTemplate() {
    const name = prompt("Introduce un nombre para la nueva plantilla de libro (ej. 'Mi Estilo Favorito'):");
    if (!name || name.trim() === '') return;

    const settingsSnapshot = buildCurrentBookTemplateSettings();
    const data = new FormData();
    appendSettingsSnapshotToFormData(data, settingsSnapshot);
    
    data.append('action', 'almaden_save_book_template');
    data.append('template_name', name.trim());
    data.append('book_id', bookState.bookId);
    const nonce = getBookTemplatesAjaxNonce();
    if (nonce) {
        data.append('nonce', nonce);
    }

    setBookTemplateButtonLoading(true);
    setBookTemplateStatus('Guardando plantilla de libro...', 'info');

    fetch(bookState.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            const savedName = res.data && res.data.template ? res.data.template.name : name.trim();
            setBookTemplateStatus(`Plantilla "${savedName}" guardada con éxito.`, 'success');
            cachedBookTemplates = null; // Force reload from disk so gallery stays in sync
            setBookTemplateStatus('Actualizando galería de plantillas...', 'info');
            loadBookTemplates();
        } else {
            setBookTemplateStatus('No se pudo guardar la plantilla de libro.', 'error');
            alert('Error: ' + (res.data || 'No se pudo guardar la plantilla de libro'));
        }
    })
    .catch(err => {
        console.error(err);
        setBookTemplateStatus('Error de conexión al guardar la plantilla.', 'error');
        alert('Error de conexión al guardar la plantilla de libro.');
    })
    .finally(() => {
        setBookTemplateButtonLoading(false);
    });
}

function deleteBookTemplate(templateId) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta plantilla de libro de forma permanente?')) {
        return;
    }

    const data = new FormData();
    data.append('action', 'almaden_delete_book_template');
    data.append('template_id', templateId);
    data.append('book_id', bookState.bookId);
    const nonce = getBookTemplatesAjaxNonce();
    if (nonce) {
        data.append('nonce', nonce);
    }

    fetch(bookState.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            cachedBookTemplates = null; // Force reload
            loadBookTemplates();
            setBookTemplateStatus('Plantilla eliminada.', 'success');
        } else {
            alert('Error: ' + (res.data || 'No se pudo eliminar la plantilla de libro'));
            setBookTemplateStatus('No se pudo eliminar la plantilla de libro.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión al eliminar la plantilla de libro.');
        setBookTemplateStatus('Error de conexión al eliminar la plantilla.', 'error');
    });
}

function downloadBookTemplate(templateId) {
    const data = new FormData();
    data.append('action', 'almaden_download_book_template');
    data.append('template_id', templateId);
    data.append('book_id', bookState.bookId || 0);
    const nonce = getBookTemplatesAjaxNonce();
    if (nonce) {
        data.append('nonce', nonce);
    }

    fetch(bookState.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(async res => {
        if (!res.ok) {
            let message = 'No se pudo descargar la plantilla.';
            try {
                const payload = await res.json();
                message = payload?.data || payload?.message || message;
            } catch (e) {
                try {
                    const text = await res.text();
                    if (text) message = text;
                } catch (err) {
                    // Ignore secondary parsing failures.
                }
            }
            throw new Error(message);
        }

        const contentDisposition = res.headers.get('content-disposition') || '';
        const filenameMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)|filename=\"?([^\";]+)\"?/i);
        const filename = filenameMatch
            ? decodeURIComponent(filenameMatch[1] || filenameMatch[2] || 'book-template.json')
            : 'book-template.json';
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename.endsWith('.json') ? filename : `${filename}.json`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    })
    .catch(err => {
        console.error(err);
        alert(err.message || 'No se pudo descargar la plantilla.');
    });
}

function promptUploadBookTemplate() {
    const input = document.getElementById('book-template-upload-input');
    if (!input) return;
    input.value = '';
    input.click();
}

function handleBookTemplateUpload(event) {
    const input = event && event.target ? event.target : null;
    const file = input && input.files && input.files[0] ? input.files[0] : null;
    if (!file) return;

    const data = new FormData();
    data.append('action', 'almaden_upload_book_template');
    data.append('template_file', file);
    data.append('book_id', bookState.bookId || 0);

    const nonce = getBookTemplatesAjaxNonce();
    if (nonce) {
        data.append('nonce', nonce);
    }

    setBookTemplateStatus('Cargando plantilla JSON...', 'info');

    fetch(bookState.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(async res => {
        const payload = await res.json().catch(() => null);
        if (!res.ok || !payload || !payload.success) {
            throw new Error((payload && payload.data) || 'No se pudo cargar la plantilla.');
        }
        return payload;
    })
    .then(res => {
        const importedName = res.data && res.data.template ? res.data.template.name : file.name;
        cachedBookTemplates = null;
        setBookTemplateStatus(`Plantilla "${importedName}" cargada con éxito.`, 'success');
        loadBookTemplates();
    })
    .catch(err => {
        console.error(err);
        setBookTemplateStatus(err.message || 'No se pudo cargar la plantilla.', 'error');
        alert(err.message || 'No se pudo cargar la plantilla.');
    })
    .finally(() => {
        if (input) {
            input.value = '';
        }
    });
}

// Backward-compatible aliases for legacy callers.
function loadSettingsTemplates() { return loadBookTemplates(); }
function renderTemplates(templates) { return renderBookTemplates(templates); }
function applySettingsTemplate(templateId) { return applyBookTemplate(templateId); }
function promptSaveCurrentAsTemplate() { return promptSaveCurrentAsBookTemplate(); }
function deleteSettingsTemplate(templateId) { return deleteBookTemplate(templateId); }
