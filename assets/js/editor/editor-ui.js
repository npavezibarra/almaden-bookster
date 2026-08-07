// editor-ui.js

const PDF_PREVIEW_ZOOM_STORAGE_KEY = 'almaden_pdf_preview_zoom';
const PDF_PREVIEW_ZOOM_LEVELS = new Set(['0.25', '0.5', '0.75', '1', '2']);
const PDF_PREVIEW_LAYOUT_STORAGE_KEY = 'almaden_pdf_preview_layout';

function normalizePdfPreviewLayout(value) {
    return String(value ?? 'single') === 'spread' ? 'spread' : 'single';
}

function normalizePdfPreviewZoom(value) {
    const normalized = String(value ?? '0.75');
    return PDF_PREVIEW_ZOOM_LEVELS.has(normalized) ? normalized : '0.75';
}

function getPdfPreviewZoomFactor() {
    const scroller = document.getElementById('pdf-scroller');
    const zoomSelect = document.getElementById('pdf-preview-zoom');
    const rawZoom = scroller?.dataset?.previewZoom || zoomSelect?.value || '1';
    const zoom = parseFloat(rawZoom);
    return Number.isFinite(zoom) && zoom > 0 ? zoom : 1;
}

function applyPdfPreviewZoom(value, persist = true) {
    const scroller = document.getElementById('pdf-scroller');
    const zoomSelect = document.getElementById('pdf-preview-zoom');
    const normalized = normalizePdfPreviewZoom(value);

    if (scroller) {
        scroller.dataset.previewZoom = normalized;
        if (window.almadenTypstPdf && typeof window.almadenTypstPdf.applyZoom === 'function') {
            scroller.style.zoom = '';
            scroller.style.webkitZoom = '';
            window.almadenTypstPdf.applyZoom();
        } else {
            scroller.style.zoom = normalized;
            scroller.style.webkitZoom = normalized;
        }
    }

    if (zoomSelect && zoomSelect.value !== normalized) {
        zoomSelect.value = normalized;
    }

    if (persist) {
        localStorage.setItem(PDF_PREVIEW_ZOOM_STORAGE_KEY, normalized);
    }

    if (typeof window.renderRuler === 'function') {
        window.renderRuler();
    }
}

function initPdfPreviewZoom() {
    const zoomSelect = document.getElementById('pdf-preview-zoom');
    const savedZoom = normalizePdfPreviewZoom(localStorage.getItem(PDF_PREVIEW_ZOOM_STORAGE_KEY));

    applyPdfPreviewZoom(savedZoom, false);

    if (zoomSelect && !zoomSelect.dataset.zoomBound) {
        zoomSelect.dataset.zoomBound = '1';
        zoomSelect.value = savedZoom;
        zoomSelect.addEventListener('change', function() {
            applyPdfPreviewZoom(this.value, true);
        });
    }
}

function applyPdfPreviewLayout(layout, persist = true) {
    const scroller = document.getElementById('pdf-scroller');
    const singleBtn = document.getElementById('btn-preview-layout-single');
    const spreadBtn = document.getElementById('btn-toggle-spread');
    const normalized = normalizePdfPreviewLayout(layout);
    const isSpread = normalized === 'spread';

    if (scroller) {
        scroller.dataset.previewLayout = normalized;
        scroller.classList.toggle('spread-view', isSpread);
        if (window.almadenTypstPdf && typeof window.almadenTypstPdf.applyLayout === 'function') {
            window.almadenTypstPdf.applyLayout(normalized);
        }
    }

    if (singleBtn) {
        singleBtn.className = isSpread
            ? 'px-2.5 py-1 rounded-sm text-[var(--text-muted)] hover:text-[var(--text-main)] transition'
            : 'px-2.5 py-1 rounded-sm bg-black text-white shadow-sm transition';
        singleBtn.setAttribute('aria-pressed', isSpread ? 'false' : 'true');
    }

    if (spreadBtn) {
        spreadBtn.className = isSpread
            ? 'px-2.5 py-1 rounded-sm bg-black text-white shadow-sm transition'
            : 'px-2.5 py-1 rounded-sm text-[var(--text-muted)] hover:text-[var(--text-main)] transition';
        spreadBtn.setAttribute('aria-pressed', isSpread ? 'true' : 'false');
    }

    if (persist) {
        localStorage.setItem(PDF_PREVIEW_LAYOUT_STORAGE_KEY, normalized);
    }

    if (typeof window.renderRuler === 'function') {
        window.renderRuler();
    }
}

function setPdfPreviewLayout(layout) {
    applyPdfPreviewLayout(layout, true);
}

// Modos de vista del espacio de trabajo (Dividido, Editor Raw, Solo PDF)
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

    bookState.viewMode = mode;

    if (mode === 'split') {
        if (editorPane) editorPane.classList.remove('hidden');
        if (previewPane) previewPane.classList.remove('hidden');
        if (splitBtn) splitBtn.className = "px-3 py-1.5 rounded-md bg-black text-white shadow-sm transition";
        if (typeof refreshSplitPreview === 'function') {
            refreshSplitPreview(true);
        } else if (typeof compilePDFPreview === 'function') {
            compilePDFPreview(true);
        }
    } else if (mode === 'edit') {
        if (editorPane) editorPane.classList.remove('hidden');
        if (previewPane) previewPane.classList.add('hidden');
        if (editBtn) editBtn.className = "px-3 py-1.5 rounded-md bg-black text-white shadow-sm transition";
    } else if (mode === 'preview') {
        if (editorPane) editorPane.classList.add('hidden');
        if (previewPane) previewPane.classList.remove('hidden');
        if (previewBtn) previewBtn.className = "px-3 py-1.5 rounded-md bg-black text-white shadow-sm transition";
        if (typeof compilePDFPreview === 'function') {
            compilePDFPreview(true);
        }
    }

    if (typeof saveStateToLocalStorage === 'function') {
        saveStateToLocalStorage();
    }
}

// Cambia el tema visual del editor (Claro, Sepia, Oscuro)
function changeTheme(themeName) {
    const body = document.body;
    const isSidebarCollapsed = body.classList.contains('sidebar-collapsed');
    body.className = ''; // Limpiar clases
    
    if (themeName === 'light') {
        body.classList.add('theme-light', 'h-full', 'overflow-hidden', 'flex', 'flex-col');
    } else if (themeName === 'sepia') {
        body.classList.add('theme-sepia', 'h-full', 'overflow-hidden', 'flex', 'flex-col');
    } else if (themeName === 'dark') {
        body.classList.add('theme-dark', 'h-full', 'overflow-hidden', 'flex', 'flex-col');
    }

    if (isSidebarCollapsed) {
        body.classList.add('sidebar-collapsed');
    }
    
    bookState.theme = themeName;
    localStorage.setItem('bookcraft_theme', themeName);
}

function applySidebarCollapsedState(isCollapsed) {
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const toggleIcon = document.getElementById('sidebar-toggle-icon');
    const toggleButton = document.getElementById('sidebar-toggle-btn');
    const toolbarSlot = document.getElementById('sidebar-toggle-toolbar-slot');
    const sidebarSlot = document.getElementById('sidebar-toggle-sidebar-slot');

    body.classList.toggle('sidebar-collapsed', isCollapsed);
    localStorage.setItem('almaden_sidebar_collapsed', isCollapsed ? 'true' : 'false');

    if (sidebar) {
        sidebar.setAttribute('aria-hidden', isCollapsed ? 'true' : 'false');
    }

    if (toggleButton) {
        const targetSlot = isCollapsed ? toolbarSlot : sidebarSlot;
        if (targetSlot && toggleButton.parentElement !== targetSlot) {
            targetSlot.appendChild(toggleButton);
        }
    }

    if (toggleIcon) {
        toggleIcon.className = isCollapsed ? 'fa-solid fa-chevron-right text-[13px]' : 'fa-solid fa-bars text-[13px]';
    }

    if (toggleButton) {
        toggleButton.title = isCollapsed ? 'Mostrar capítulos' : 'Ocultar capítulos';
        toggleButton.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
    }
}

// Mostrar / Ocultar la barra lateral de capítulos
function toggleSidebar() {
    const isCollapsed = !document.body.classList.contains('sidebar-collapsed');
    applySidebarCollapsedState(isCollapsed);
}

// Spread View Logic
function toggleSpreadView() {
    const scroller = document.getElementById('pdf-scroller');
    const current = scroller && scroller.classList.contains('spread-view') ? 'spread' : 'single';
    applyPdfPreviewLayout(current === 'spread' ? 'single' : 'spread', true);
}

function initSpreadView() {
    const legacySpread = localStorage.getItem('bookcraft_spread_view');
    const savedLayout = normalizePdfPreviewLayout(
        localStorage.getItem(PDF_PREVIEW_LAYOUT_STORAGE_KEY) || (legacySpread === 'true' ? 'spread' : 'single')
    );
    applyPdfPreviewLayout(savedLayout, false);
}

function initSidebarCollapseState() {
    const isCollapsed = localStorage.getItem('almaden_sidebar_collapsed') === 'true';
    applySidebarCollapsedState(isCollapsed);
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

document.addEventListener('DOMContentLoaded', () => {
    initSidebarCollapseState();
    initPdfPreviewZoom();
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

    const zoom = getPdfPreviewZoomFactor();

    // 1cm ≈ 37.7952755906px
    const unitPixels = 37.7952755906 * zoom;
    
    // Ruler should be as wide as the scrollable area
    const totalWidth = (Math.max(scroller.clientWidth, scroller.scrollWidth) * zoom) + 1000; // Extra width for scrolling safety
    ruler.style.width = totalWidth + 'px';
    
    // Align ruler with horizontal scroll
    ruler.style.left = -(scroller.scrollLeft * zoom) + 'px';
    
    let origin = totalWidth / 2;
    const pagesContainer = scroller.querySelector('[data-visual-editor-surface="1"]');

    if (pagesContainer) {
        const firstPage = pagesContainer.querySelector('[data-page-number]:not([data-blank="1"])')
            || pagesContainer.querySelector('[data-page-number]');
        origin = firstPage
            ? firstPage.offsetLeft * zoom
            : pagesContainer.offsetLeft * zoom;
    }

    const maxUnitsRight = Math.ceil((totalWidth - origin) / unitPixels) + 2;
    const maxUnitsLeft = Math.ceil(origin / unitPixels) + 2;
    const maxUnits = Math.max(maxUnitsRight, maxUnitsLeft);

    let html = '';
    // Draw 0 at center
    html += `<div style="position: absolute; top: 0; bottom: 0; border-left: 1px solid #ef4444; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; left: ${origin}px; z-index: 10;">
        <span style="font-size: 8px; color: #ef4444; font-weight: bold; line-height: 1;  margin-bottom: 2px;">0</span>
    </div>`;

    for (let i = 1; i <= maxUnits; i++) {
        // Right
        html += `<div style="position: absolute; top: 0; bottom: 0; border-left: 1px solid #9ca3af; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; left: ${origin + (i * unitPixels)}px;">
            <span style="font-size: 8px; color: #4b5563; line-height: 1;  margin-bottom: 2px;">${i}</span>
        </div>`;
        // Left
        html += `<div style="position: absolute; top: 0; bottom: 0; border-left: 1px solid #9ca3af; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; left: ${origin - (i * unitPixels)}px;">
            <span style="font-size: 8px; color: #4b5563; line-height: 1;  margin-bottom: 2px;">-${i}</span>
        </div>`;

        // Sub-ticks
        for (let j = 1; j < 10; j++) {
            const subTickOffset = (i - 1 + (j / 10)) * unitPixels;
            const tickHeight = j === 5 ? '10px' : '6px';
            // Right subtick
            html += `<div style="position: absolute; bottom: 0; border-left: 1px solid #d1d5db; height: ${tickHeight}; left: ${origin + subTickOffset}px;"></div>`;
            // Left subtick
            html += `<div style="position: absolute; bottom: 0; border-left: 1px solid #d1d5db; height: ${tickHeight}; left: ${origin - subTickOffset}px;"></div>`;
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
                ruler.style.left = -(scroller.scrollLeft * getPdfPreviewZoomFactor()) + 'px';
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
