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

    // Re-render ruler if visible
    if (typeof window.renderRuler === 'function') {
        setTimeout(window.renderRuler, 10);
    }
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

// Ruler Logic
window.toggleRuler = function() {
    const ruler = document.getElementById('pdf-ruler-wrapper');
    const btn = document.getElementById('btn-toggle-ruler');
    if (!ruler || !btn) return;
    
    ruler.classList.toggle('hidden');
    const isVisible = !ruler.classList.contains('hidden');
    
    if (isVisible) {
        btn.classList.add('text-black', 'dark:text-white');
        btn.classList.remove('text-[var(--text-muted)]');
        if (typeof window.renderRuler === 'function') window.renderRuler();
    } else {
        btn.classList.remove('text-black', 'dark:text-white');
        btn.classList.add('text-[var(--text-muted)]');
    }
}

window.renderRuler = function() {
    const wrapper = document.getElementById('pdf-ruler-wrapper');
    const ruler = document.getElementById('pdf-ruler');
    const scroller = document.getElementById('pdf-scroller');
    if (!wrapper || !ruler || !scroller || wrapper.classList.contains('hidden')) return;

    // 1cm ≈ 37.7952755906px
    const unitPixels = 37.7952755906;
    
    // Ruler should be as wide as the scrollable area
    const totalWidth = Math.max(scroller.clientWidth, scroller.scrollWidth) + 1000; // Extra width for scrolling safety
    ruler.style.width = totalWidth + 'px';
    
    // Align ruler with horizontal scroll
    ruler.style.left = -scroller.scrollLeft + 'px';
    
    let center = totalWidth / 2;
    
    // Exact spine calculation based on DOM
    const pages = Array.from(scroller.querySelectorAll('.pagedjs_page'));
    const firstPage = pages[0];
    
    if (scroller.classList.contains('spread-view')) {
        // En Paged.js, la primera página (página 1, impar/derecha) se muestra sola a la derecha en la vista spread,
        // por lo que no tiene una página izquierda (even) acompañándola.
        // Si hay una página par (left) e impar (right) visibles al mismo tiempo:
        const evenPage = scroller.querySelector('.pagedjs_page.pagedjs_left_page');
        const oddPage = scroller.querySelector('.pagedjs_page.pagedjs_right_page');
        
        if (evenPage && oddPage) {
            // Caso normal de dos páginas contiguas: el 0 (lomo) es el borde izquierdo de la página derecha.
            center = oddPage.offsetLeft;
        } else if (firstPage) {
            if (firstPage.classList.contains('pagedjs_right_page')) {
                // Si solo se muestra la página 1 (derecha), el lomo divisor/0 va justo en su borde izquierdo
                center = firstPage.offsetLeft;
            } else if (firstPage.classList.contains('pagedjs_left_page')) {
                // Si solo se muestra una página izquierda, el lomo divisor/0 va en su borde derecho
                center = firstPage.offsetLeft + firstPage.offsetWidth;
            }
        }
    } else {
        // Vista de una sola página:
        if (firstPage) {
            if (firstPage.classList.contains('pagedjs_right_page')) {
                center = firstPage.offsetLeft;
            } else if (firstPage.classList.contains('pagedjs_left_page')) {
                center = firstPage.offsetLeft + firstPage.offsetWidth;
            } else {
                center = firstPage.offsetLeft;
            }
        }
    }

    const maxUnitsRight = Math.ceil((totalWidth - center) / unitPixels) + 2;
    const maxUnitsLeft = Math.ceil(center / unitPixels) + 2;
    const maxUnits = Math.max(maxUnitsRight, maxUnitsLeft);

    let html = '';
    // Draw 0 at center
    html += `<div style="position: absolute; top: 0; bottom: 0; border-left: 1px solid #ef4444; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; left: ${center}px; z-index: 10;">
        <span style="font-size: 8px; color: #ef4444; font-weight: bold; line-height: 1;  margin-bottom: 2px;">0</span>
    </div>`;

    for (let i = 1; i <= maxUnits; i++) {
        // Right
        html += `<div style="position: absolute; top: 0; bottom: 0; border-left: 1px solid #9ca3af; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; left: ${center + (i * unitPixels)}px;">
            <span style="font-size: 8px; color: #4b5563; line-height: 1;  margin-bottom: 2px;">${i}</span>
        </div>`;
        // Left
        html += `<div style="position: absolute; top: 0; bottom: 0; border-left: 1px solid #9ca3af; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; left: ${center - (i * unitPixels)}px;">
            <span style="font-size: 8px; color: #4b5563; line-height: 1;  margin-bottom: 2px;">-${i}</span>
        </div>`;

        // Sub-ticks
        for (let j = 1; j < 10; j++) {
            const subTickOffset = (i - 1 + (j / 10)) * unitPixels;
            const tickHeight = j === 5 ? '10px' : '6px';
            // Right subtick
            html += `<div style="position: absolute; bottom: 0; border-left: 1px solid #d1d5db; height: ${tickHeight}; left: ${center + subTickOffset}px;"></div>`;
            // Left subtick
            html += `<div style="position: absolute; bottom: 0; border-left: 1px solid #d1d5db; height: ${tickHeight}; left: ${center - subTickOffset}px;"></div>`;
        }
    }

    ruler.innerHTML = html;
}

// Ensure scroll syncs ruler
document.addEventListener('DOMContentLoaded', () => {
    const scroller = document.getElementById('pdf-scroller');
    if (scroller) {
        scroller.addEventListener('scroll', () => {
            const wrapper = document.getElementById('pdf-ruler-wrapper');
            const ruler = document.getElementById('pdf-ruler');
            if (wrapper && !wrapper.classList.contains('hidden') && ruler) {
                ruler.style.left = -scroller.scrollLeft + 'px';
            }
        });

        // Add ResizeObserver to re-render ruler when scroller size changes
        const resizeObserver = new ResizeObserver(() => {
            const wrapper = document.getElementById('pdf-ruler-wrapper');
            if (wrapper && !wrapper.classList.contains('hidden')) {
                if (typeof window.renderRuler === 'function') window.renderRuler();
            }
        });
        resizeObserver.observe(scroller);
    }
});

window.addEventListener('resize', () => {
    if (typeof window.renderRuler === 'function') {
        window.renderRuler();
    }
});
