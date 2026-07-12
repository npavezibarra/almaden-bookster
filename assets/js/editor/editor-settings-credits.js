window.addCustomCreditRow = function(role = '', name = '') {
    const container = document.getElementById('custom-credits-container');
    if (!container) return;

    const row = document.createElement('div');
    row.className = 'flex items-center gap-2 mb-2 custom-credit-row';
    row.innerHTML = `
        <input type="text" placeholder="Rol (ej: Traducción)" value="${role}" class="credit-role w-1/3 bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black">
        <input type="text" placeholder="Nombre (ej: Ana Pérez)" value="${name}" class="credit-name w-1/2 bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-black flex-1">
        <button type="button" class="btn-remove-custom-credit w-8 h-8 flex items-center justify-center text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar fila">
            <i class="fa-solid fa-trash-can text-xs"></i>
        </button>
    `;
    container.appendChild(row);

    const updateCustomCredits = () => {
        bookState.settings.credits_custom = getCustomCreditsJSON();
        if (typeof refreshEditorDisplay === 'function') refreshEditorDisplay(false);
    };

    row.querySelector('.credit-role').addEventListener('input', updateCustomCredits);
    row.querySelector('.credit-name').addEventListener('input', updateCustomCredits);
    row.querySelector('.btn-remove-custom-credit').addEventListener('click', () => {
        row.remove();
        updateCustomCredits();
    });
};

window.getCustomCreditsJSON = function() {
    const container = document.getElementById('custom-credits-container');
    if (!container) return '[]';
    const rows = container.querySelectorAll('.custom-credit-row');
    const credits = [];
    rows.forEach(row => {
        const role = row.querySelector('.credit-role').value.trim();
        const name = row.querySelector('.credit-name').value.trim();
        if (role || name) {
            credits.push({ role, name });
        }
    });
    return JSON.stringify(credits);
};

window.renderCustomCredits = function(creditsJSON) {
    const container = document.getElementById('custom-credits-container');
    if (!container) return;
    container.innerHTML = '';
    let credits = [];
    try {
        if (creditsJSON) {
            credits = typeof creditsJSON === 'string' ? JSON.parse(creditsJSON) : creditsJSON;
        }
    } catch(e) {
        console.error("Error parsing custom credits JSON", e);
    }
    
    if (Array.isArray(credits) && credits.length > 0) {
        credits.forEach(c => addCustomCreditRow(c.role, c.name));
    } else {
        // Add one empty row by default
        addCustomCreditRow();
    }
};

window.initCreditsForm = function() {
    const settings = bookState.settings;
    
    // Fill inputs
    document.getElementById('setting-credits-edition').value = settings.credits_edition || '';
    document.getElementById('setting-credits-date').value = settings.credits_date || '';
    document.getElementById('setting-credits-copyright').value = settings.credits_copyright || 'Queda rigurosamente prohibida, sin la autorización escrita de los titulares del "copyright", bajo las sanciones establecidas en las leyes, la reproducción parcial o total de esta obra por cualquier medio o procedimiento.';
    document.getElementById('setting-credits-printer').value = settings.credits_printer || '';
    document.getElementById('setting-credits-blank-before').value = settings.credits_blank_before || 0;
    document.getElementById('setting-credits-blank-after').value = settings.credits_blank_after || 0;
    renderCustomCredits(settings.credits_custom);

    // Bind real-time update events
    const inputs = [
        'setting-credits-edition', 'setting-credits-date', 'setting-credits-copyright',
        'setting-credits-printer', 'setting-credits-blank-before', 'setting-credits-blank-after'
    ];

    inputs.forEach(id => {
        const el = document.getElementById(id);
        if (el && !el.hasAttribute('data-bound')) {
            el.setAttribute('data-bound', 'true');
            el.addEventListener('input', () => {
                // Determine the property name in settings by replacing 'setting-' and dashes with underscores
                const key = id.replace('setting-', '').replace(/-/g, '_');
                bookState.settings[key] = el.value;
                if (typeof refreshEditorDisplay === 'function') refreshEditorDisplay(false);
            });
        }
    });
};
// ------------------------------------------

// Mostrar / Ocultar campos de dimensiones personalizados
