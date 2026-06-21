// --- PLANTILLAS DE AJUSTES ---
let cachedTemplates = null;

function loadSettingsTemplates() {
    const container = document.getElementById('templates-container');
    if (!container) return;

    if (cachedTemplates) {
        renderTemplates(cachedTemplates);
        return;
    }

    const data = new FormData();
    data.append('action', 'almaden_get_settings_templates');
    data.append('book_id', bookState.bookId);
    data.append('nonce', bookState.settingsNonce);

    fetch(bookState.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(res => res.json())
    .then(res => {
        if (res.success && res.data && res.data.templates) {
            cachedTemplates = res.data.templates;
            renderTemplates(cachedTemplates);
        } else {
            container.innerHTML = '<div class="text-[10px] text-rose-500 italic">Error al cargar plantillas.</div>';
        }
    })
    .catch(err => {
        console.error('Error fetching templates:', err);
        container.innerHTML = '<div class="text-[10px] text-rose-500 italic">Error de conexión.</div>';
    });
}

function renderTemplates(templates) {
    const container = document.getElementById('templates-container');
    if (!container) return;

    if (templates.length === 0) {
        container.innerHTML = '<div class="text-[10px] text-[var(--text-muted)] italic">No hay plantillas disponibles.</div>';
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
            </div>
            <div class="mt-3 flex items-center justify-between">
                <button class="text-[10px] font-semibold text-white bg-black hover:bg-neutral-800 rounded px-3 py-1 transition" onclick="applySettingsTemplate('${tpl.id}')">
                    Aplicar Plantilla
                </button>
                <button class="text-[10px] text-rose-500 hover:text-rose-700 transition" onclick="deleteSettingsTemplate('${tpl.id}')" title="Eliminar Plantilla">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(div);
    });
}

function applySettingsTemplate(templateId) {
    if (!cachedTemplates) return;
    const tpl = cachedTemplates.find(t => t.id === templateId);
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
        btnTemplates.addEventListener('click', loadSettingsTemplates);
    }
});

function promptSaveCurrentAsTemplate() {
    const name = prompt("Introduce un nombre para la nueva plantilla (ej. 'Mi Estilo Favorito'):");
    if (!name || name.trim() === '') return;

    // Collect all form data from settings modal
    const form = document.getElementById('settings-form');
    if (!form) return;
    const data = new FormData(form);
    
    data.append('action', 'almaden_save_settings_template');
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
            alert('¡Plantilla guardada con éxito!');
            cachedTemplates = null; // Force reload
            loadSettingsTemplates();
        } else {
            alert('Error: ' + (res.data || 'No se pudo guardar la plantilla'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión al guardar la plantilla.');
    });
}

function deleteSettingsTemplate(templateId) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta plantilla de forma permanente?')) {
        return;
    }

    const data = new FormData();
    data.append('action', 'almaden_delete_settings_template');
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
            cachedTemplates = null; // Force reload
            loadSettingsTemplates();
        } else {
            alert('Error: ' + (res.data || 'No se pudo eliminar la plantilla'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de conexión al eliminar la plantilla.');
    });
}

