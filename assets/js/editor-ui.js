// editor-ui.js

// Modos de vista del espacio de trabajo (Dividido, Solo Editor, Solo PDF)
function setViewMode(mode) {
    const editorPane = document.getElementById('editor-pane');
    const previewPane = document.getElementById('pdf-preview-pane');
    const splitBtn = document.getElementById('view-split-btn');
    const editBtn = document.getElementById('view-edit-btn');
    const previewBtn = document.getElementById('view-preview-btn');

    // Resetear clases de botones
    [splitBtn, editBtn, previewBtn].forEach(btn => {
        if (btn) {
            btn.className = "px-3 py-1.5 rounded-md text-[var(--text-muted)] hover:text-[var(--text-main)] transition";
        }
    });

    if (mode === 'split') {
        if (editorPane) editorPane.classList.remove('hidden');
        if (previewPane) previewPane.classList.remove('hidden');
        if (splitBtn) splitBtn.className = "px-3 py-1.5 rounded-md bg-black text-white shadow-sm transition";
    } else if (mode === 'edit') {
        if (editorPane) editorPane.classList.remove('hidden');
        if (previewPane) previewPane.classList.add('hidden');
        if (editBtn) editBtn.className = "px-3 py-1.5 rounded-md bg-black text-white shadow-sm transition";
    } else if (mode === 'preview') {
        if (editorPane) editorPane.classList.add('hidden');
        if (previewPane) previewPane.classList.remove('hidden');
        if (previewBtn) previewBtn.className = "px-3 py-1.5 rounded-md bg-black text-white shadow-sm transition";
    }

    bookState.viewMode = mode;
    if (typeof saveStateToLocalStorage === 'function') {
        saveStateToLocalStorage();
    }
}

// Cambia el tema visual del editor (Claro, Sepia, Oscuro)
function changeTheme(themeName) {
    const body = document.body;
    body.className = ''; // Limpiar clases
    
    if (themeName === 'light') {
        body.classList.add('theme-light', 'h-full', 'overflow-hidden', 'flex', 'flex-col');
    } else if (themeName === 'sepia') {
        body.classList.add('theme-sepia', 'h-full', 'overflow-hidden', 'flex', 'flex-col');
    } else if (themeName === 'dark') {
        body.classList.add('theme-dark', 'h-full', 'overflow-hidden', 'flex', 'flex-col');
    }
    
    bookState.theme = themeName;
    localStorage.setItem('bookcraft_theme', themeName);
}

// Mostrar / Ocultar la barra lateral de capítulos
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar-panel');
    const toggleIcon = document.getElementById('sidebar-toggle-icon');
    
    if (sidebar && toggleIcon) {
        if (sidebar.classList.contains('w-80')) {
            // Contraer lateral
            sidebar.classList.remove('w-80', 'opacity-100');
            sidebar.classList.add('w-0', 'opacity-0', 'pointer-events-none');
            toggleIcon.classList.remove('fa-chevron-left');
            toggleIcon.classList.add('fa-chevron-right');
        } else {
            // Expandir lateral
            sidebar.classList.remove('w-0', 'opacity-0', 'pointer-events-none');
            sidebar.classList.add('w-80', 'opacity-100');
            toggleIcon.classList.remove('fa-chevron-right');
            toggleIcon.classList.add('fa-chevron-left');
        }
    }
}

// Spread View Logic
function toggleSpreadView() {
    const scroller = document.getElementById('pdf-scroller');
    const btn = document.getElementById('btn-toggle-spread');
    if (!scroller || !btn) return;

    scroller.classList.toggle('spread-view');
    const isSpread = scroller.classList.contains('spread-view');
    
    // Save to localStorage
    localStorage.setItem('bookcraft_spread_view', isSpread ? 'true' : 'false');
    
    // Update icon
    btn.innerHTML = isSpread ? '<i class="fa-solid fa-book-open"></i>' : '<i class="fa-solid fa-file-lines"></i>';
}

function initSpreadView() {
    const isSpread = localStorage.getItem('bookcraft_spread_view') === 'true';
    if (isSpread) {
        const scroller = document.getElementById('pdf-scroller');
        const btn = document.getElementById('btn-toggle-spread');
        if (scroller) scroller.classList.add('spread-view');
        if (btn) btn.innerHTML = '<i class="fa-solid fa-book-open"></i>';
    }
}

// Muestra notificaciones personalizadas dinámicas de la aplicación
function showToast(message, iconClass = "fa-solid fa-circle-check") {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    const toastIcon = document.getElementById('toast-icon');

    if (toast && toastMessage && toastIcon) {
        toastMessage.textContent = message;
        toastIcon.innerHTML = `<i class="${iconClass}"></i>`;

        // Efecto flotante de aparición fluida
        toast.classList.remove('translate-y-10', 'opacity-0', 'pointer-events-none');
        toast.classList.add('translate-y-0', 'opacity-100');

        setTimeout(() => {
            toast.classList.add('translate-y-10', 'opacity-0', 'pointer-events-none');
            toast.classList.remove('translate-y-0', 'opacity-100');
        }, 3000);
    }
}

// Selector de lenguaje para hyphens
function toggleLangDropdown() {
    const dropdown = document.getElementById('lang-dropdown');
    if (dropdown) dropdown.classList.toggle('hidden');
}

function toggleAddChapterDropdown() {
    const dropdown = document.getElementById('add-chapter-dropdown');
    if (dropdown) dropdown.classList.toggle('hidden');
}

// Cierra el dropdown si se hace click fuera
document.addEventListener('click', function(event) {
    const langWrapper = document.getElementById('lang-selector-wrapper');
    const langDropdown = document.getElementById('lang-dropdown');
    if (langWrapper && langDropdown && !langWrapper.contains(event.target)) {
        langDropdown.classList.add('hidden');
    }

    const addChapterWrapper = document.getElementById('add-chapter-dropdown-wrapper');
    const addChapterDropdown = document.getElementById('add-chapter-dropdown');
    if (addChapterWrapper && addChapterDropdown && !addChapterWrapper.contains(event.target)) {
        addChapterDropdown.classList.add('hidden');
    }
});
