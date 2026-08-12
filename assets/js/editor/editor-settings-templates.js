// --- BOOK TEMPLATES ---
let cachedBookTemplates = null;

function loadBookTemplates() {
    const container = document.getElementById('templates-container');
    if (!container) return;

    if (cachedBookTemplates) {
        renderBookTemplates(cachedBookTemplates);
        return;
    }

    const data = new FormData();
    data.append('action', 'almaden_get_book_templates');
    data.append('book_id', bookState.bookId);
    data.append('nonce', bookState.settingsNonce);

    fetch(bookState.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {
        if (res.success && res.data && res.data.templates) {
            cachedBookTemplates = res.data.templates;
            renderBookTemplates(cachedBookTemplates);
        } else {
            container.innerHTML = '<div class="text-[10px] text-rose-500 italic">Error al cargar plantillas de libro.</div>';
        }
    })
    .catch(err => {
        console.error('Error fetching book templates:', err);
        container.innerHTML = '<div class="text-[10px] text-rose-500 italic">Error de conexión.</div>';
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
                <p class="text-[9px] text-[var(--text-muted)] leading-tight">${tpl.description || ''}</p>
                <div class="mt-2 flex flex-wrap gap-1">
                    <span class="inline-flex items-center rounded-full border border-[var(--border-color)] px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-[var(--text-muted)]">${tpl.visibility || 'private'}</span>
                    ${tpl.sample_chapters && tpl.sample_chapters.length ? '<span class="inline-flex items-center rounded-full border border-[var(--border-color)] px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-[var(--text-muted)]">Con muestras</span>' : ''}
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between">
                <button class="text-[10px] font-semibold text-white bg-black hover:bg-neutral-800 rounded px-3 py-1 transition" onclick="applyBookTemplate('${tpl.id}')">
                    Aplicar plantilla
                </button>
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

    // Collect all form data from settings modal
    const form = document.getElementById('settings-form');
    if (!form) return;
    const data = new FormData(form);
    
    data.append('action', 'almaden_save_book_template');
    data.append('template_name', name.trim());
    data.append('book_id', bookState.bookId);
    data.append('nonce', bookState.settingsNonce);

    fetch(bookState.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            alert('¡Plantilla de libro guardada con éxito!');
            cachedBookTemplates = null; // Force reload
            loadBookTemplates();
        } else {
            alert('Error: ' + (res.data || 'No se pudo guardar la plantilla de libro'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión al guardar la plantilla de libro.');
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
    data.append('nonce', bookState.settingsNonce);

    fetch(bookState.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            cachedBookTemplates = null; // Force reload
            loadBookTemplates();
        } else {
            alert('Error: ' + (res.data || 'No se pudo eliminar la plantilla de libro'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión al eliminar la plantilla de libro.');
    });
}

// Backward-compatible aliases for legacy callers.
function loadSettingsTemplates() { return loadBookTemplates(); }
function renderTemplates(templates) { return renderBookTemplates(templates); }
function applySettingsTemplate(templateId) { return applyBookTemplate(templateId); }
function promptSaveCurrentAsTemplate() { return promptSaveCurrentAsBookTemplate(); }
function deleteSettingsTemplate(templateId) { return deleteBookTemplate(templateId); }
