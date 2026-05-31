// Manejo del estado, editor y lógica principal

window.editorLastSelection = { start: 0, end: 0 };

function trackEditorSelection() {
    const textarea = document.getElementById('editor-textarea');
    if (textarea && document.activeElement === textarea) {
        window.editorLastSelection.start = textarea.selectionStart;
        window.editorLastSelection.end = textarea.selectionEnd;
    }
}

// Datos del libro por defecto (si el usuario ingresa por primera vez)
const DEFAULT_CHAPTERS = [
    {
        id: "cap-1",
        title: "Capítulo I: El Primer Suspiro",
        content: "# Capítulo I\n## El Primer Suspiro\n\nEl viento soplaba furioso contra las ventanas de la antigua cabaña. Aquella noche de invierno no parecía diferente a las anteriores, pero el destino ya había trazado su línea de no retorno. Daniel, sentado frente a su rústica mesa de madera, sostenía una pluma gastada.\n\n*\"Las palabras tienen el poder de dar vida, pero también de arrebatarla\"*, murmuró para sus adentros.\n\nFrente a él yacía un manuscrito antiguo encuadernado en cuero desgastado. Nadie debía saber lo que contenía, pero las sombras acechaban más de lo usual en los rincones de la habitación. De repente, un golpe seco resonó en la puerta principal. Tres toques rítmicos, seguidos de un profundo silencio.\n\n> Aquel que busca respuestas en las sombras debe estar preparado para ver lo que las sombras revelan.\n\n- Daniel apagó la vela rápidamente.\n- El silencio de la casa se volvió ensordecedor.\n- Con sigilo, deslizó la mano por debajo de la mesa buscando la vieja llave de latón."
    },
    {
        id: "cap-2",
        title: "Capítulo II: Sombras en el Umbral",
        content: "# Capítulo II\n## Sombras en el Umbral\n\nAl no recibir respuesta, la cerradura crujió levemente. Una ráfaga de aire helado invadió la sala de estar cuando la puerta cedió. Una silueta alta y envuelta en una capa oscura se recortó contra la pálida luz de la luna que se filtraba a través de las nubes grises.\n\nDaniel retrocedió hasta sentir el frío muro a su espalda.\n\n—Sé que estás aquí, Daniel —dijo una voz suave, pero cargada de una extraña vibración que hizo erizar su piel—.\n\nLa figura se despojó lentamente de su capucha, revelando unos ojos claros que parecían brillar con luz propia en medio de la penumbra. No buscaba confrontación, buscaba el manuscrito que reposaba sobre la mesa.\n\n**\"El destino se ha cumplido hoy,\"** pensó Daniel desesperado mientras recordaba las advertencias de su mentor."
    }
];

// Estado global para el modo de renderizado ('active' o 'full')
window.currentPreviewMode = 'active';

// Al iniciar la aplicación
window.onload = function() {
    // Cargar tema de LocalStorage si existe (preferencia visual)
    const savedTheme = localStorage.getItem('bookcraft_theme');
    if (savedTheme) {
        bookState.theme = savedTheme;
    }

    // Aplicar el título del libro
    const titleInput = document.getElementById('book-title-input');
    if (titleInput) {
        titleInput.value = bookState.title;
    }

    // Inicializar Listeners
    initEventListeners();

    // Renderizar Sidebar
    renderSidebar();

    // Cargar el capítulo activo
    loadActiveChapter();

    // Aplicar Tema Guardado
    changeTheme(bookState.theme);

    // Aplicar Vista
    setViewMode(bookState.viewMode);
    
    // Aplicar maquetación dinámica del PDF
    applyDynamicPDFStyles();
    
    // Inicializar el conteo de páginas oculto para tener todos los capítulos medidos
    if (window.currentPreviewMode === 'active' && typeof window.calculateAllPagesBackground === 'function') {
        setTimeout(async () => {
            await window.calculateAllPagesBackground();
            // Después de calcular todas las páginas, re-renderizamos la vista activa
            // para que actualice los números de página (especialmente útil si estamos viendo el Índice)
            if (typeof compilePDFPreview === 'function') {
                compilePDFPreview(false, 'pdf-scroller', false);
            }
        }, 1000); // Dar 1 segundo para que la página cargue completa
    }
    
    // Configurar visibilidad del botón de imagen de paridad
    if (typeof updateParityButtonVisibility === 'function') {
        updateParityButtonVisibility();
    }
    
    // Inicializar la vista de doble página si estaba guardada
    if (typeof initSpreadView === 'function') {
        initSpreadView();
    }
    
    showToast("¡Bienvenido de vuelta a tu manuscrito!", "fa-solid fa-book-open");
};

// Inicializador de Event Listeners
function initEventListeners() {
    const textarea = document.getElementById('editor-textarea');
    const chapterTitle = document.getElementById('chapter-title-input');
    const bookTitle = document.getElementById('book-title-input');

    if (textarea) {
        textarea.addEventListener('input', () => {
            const activeId = bookState.activeChapterId;
            const chapter = bookState.chapters.find(c => c.id === activeId);
            if (chapter) {
                chapter.content = textarea.value;
                updateWordCounts();
                saveStateToLocalStorage();
            }
        });

        // Rastrear la selección para restaurarla al hacer clic en botones
        textarea.addEventListener('mouseup', trackEditorSelection);
        textarea.addEventListener('keyup', trackEditorSelection);
        textarea.addEventListener('focus', trackEditorSelection);
    }
    
    document.addEventListener('selectionchange', () => {
        const textarea = document.getElementById('editor-textarea');
        if (textarea && document.activeElement === textarea) {
            trackEditorSelection();
        }
    });

    if (chapterTitle) {
        chapterTitle.addEventListener('input', () => {
            const activeId = bookState.activeChapterId;
            const chapter = bookState.chapters.find(c => c.id === activeId);
            if (chapter) {
                chapter.title = chapterTitle.value;
                renderSidebar(); // Actualiza el sidebar en tiempo real
                saveStateToLocalStorage();
            }
        });
    }

    if (bookTitle) {
        bookTitle.addEventListener('input', () => {
            bookState.title = bookTitle.value;
            saveStateToLocalStorage();
        });
    }

    // Ajustar el ancho y visibilidad al cambiar tamaño de pantalla
    window.addEventListener('resize', () => {
        if (window.innerWidth < 1024) {
            // En móviles, se fuerza vista de editor si está en dividido
            if (bookState.viewMode === 'split') {
                setViewMode('edit');
            }
        }
    });

    const btnToggleSpread = document.getElementById('btn-toggle-spread');
    if (btnToggleSpread) {
        btnToggleSpread.addEventListener('click', toggleSpreadView);
    }
    
    // Botón de Guardado Manual
    const btnManualSave = document.getElementById('btn-manual-save');
    if (btnManualSave) {
        btnManualSave.addEventListener('click', () => {
            saveStateToLocalStorage(true); // Pasar true para guardar y compilar de inmediato
        });
    }
}

// Cambiar modo de Vista Previa
function changePreviewMode(mode) {
    window.currentPreviewMode = mode;
    // Mostrar un pequeño indicador de carga si es full
    if (mode === 'full') {
        const scroller = document.getElementById('pdf-scroller');
        if (scroller) {
            scroller.innerHTML = '<div class="flex items-center justify-center h-full w-full text-indigo-500 gap-2"><i class="fa-solid fa-spinner fa-spin"></i> Compilando libro completo...</div>';
        }
        // Usar un setTimeout para permitir que el DOM renderice el spinner antes de congelar
        setTimeout(() => {
            compilePDFPreview();
        }, 100);
    } else {
        compilePDFPreview();
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
        if (splitBtn) splitBtn.className = "px-3 py-1.5 rounded-md bg-indigo-600 text-white shadow-sm transition";
    } else if (mode === 'edit') {
        if (editorPane) editorPane.classList.remove('hidden');
        if (previewPane) previewPane.classList.add('hidden');
        if (editBtn) editBtn.className = "px-3 py-1.5 rounded-md bg-indigo-600 text-white shadow-sm transition";
    } else if (mode === 'preview') {
        if (editorPane) editorPane.classList.add('hidden');
        if (previewPane) previewPane.classList.remove('hidden');
        if (previewBtn) previewBtn.className = "px-3 py-1.5 rounded-md bg-indigo-600 text-white shadow-sm transition";
    }

    bookState.viewMode = mode;
    saveStateToLocalStorage();
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

// Funciones para la barra de formato Markdown
function wrapText(prefix, suffix) {
    const textarea = document.getElementById('editor-textarea');
    if (!textarea) return;
    
    // Si el textarea no tiene el foco (ej: se hizo clic en un botón de la toolbar),
    // restaurar la última selección conocida
    if (document.activeElement !== textarea && typeof window.editorLastSelection !== 'undefined') {
        textarea.selectionStart = window.editorLastSelection.start || 0;
        textarea.selectionEnd = window.editorLastSelection.end || 0;
    }

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    
    // Si hay texto seleccionado, lo envuelve
    if (start !== end) {
        const selectedText = text.substring(start, end);
        textarea.value = text.substring(0, start) + prefix + selectedText + suffix + text.substring(end);
        textarea.selectionStart = start + prefix.length;
        textarea.selectionEnd = end + prefix.length;
    } else {
        // Si no hay selección, inserta los marcadores y pone el cursor en medio
        textarea.value = text.substring(0, start) + prefix + suffix + text.substring(start);
        textarea.selectionStart = textarea.selectionEnd = start + prefix.length;
    }
    
    textarea.focus();
    triggerEditorUpdate();
}

function addPrefix(prefix) {
    const textarea = document.getElementById('editor-textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    
    // Encontrar el inicio de la línea donde está el cursor
    let lineStart = text.lastIndexOf('\n', start - 1);
    lineStart = lineStart === -1 ? 0 : lineStart + 1;
    
    textarea.value = text.substring(0, lineStart) + prefix + text.substring(lineStart);
    
    textarea.selectionStart = start + prefix.length;
    textarea.selectionEnd = end + prefix.length;
    textarea.focus();
    
    triggerEditorUpdate();
}

let mediaUploader;
function openMediaUploader() {
    if (mediaUploader) {
        mediaUploader.open();
        return;
    }

    if (typeof wp === 'undefined' || !wp.media) {
        showToast("Error: Media API no está disponible. Guarda y recarga la página.", "fa-solid fa-triangle-exclamation");
        return;
    }

    mediaUploader = wp.media({
        title: 'Seleccionar Imagen',
        button: { text: 'Insertar en el capítulo' },
        multiple: false,
        library: { type: 'image' }
    });

    mediaUploader.on('select', function() {
        const attachment = mediaUploader.state().get('selection').first().toJSON();
        const imgUrl = attachment.url;
        const imgAlt = attachment.alt || attachment.title || 'Imagen del libro';
        
        // El wrapper HTML con tamaño pequeño para el editor, que el compilador pasará tal cual
        const imageTag = `\n<img src="${imgUrl}" alt="${imgAlt}" width="150" class="pdf-book-image" />\n`;
        
        insertAtCursor(imageTag);
    });

    mediaUploader.open();
}

let parityMediaUploader;
function openParityImageUploader() {
    if (!bookState.activeChapterId) {
        showToast("Selecciona un capítulo primero.", "fa-solid fa-circle-exclamation");
        return;
    }

    if (parityMediaUploader) {
        parityMediaUploader.open();
        return;
    }

    if (typeof wp === 'undefined' || !wp.media) {
        showToast("Error: Media API no está disponible.", "fa-solid fa-triangle-exclamation");
        return;
    }

    parityMediaUploader = wp.media({
        title: 'Seleccionar Imagen para Página en Blanco (Paridad)',
        button: { text: 'Establecer como imagen de paridad' },
        multiple: false,
        library: { type: 'image' }
    });

    parityMediaUploader.on('select', function() {
        const attachment = parityMediaUploader.state().get('selection').first().toJSON();
        const imgUrl = attachment.url;
        
        const chapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
        if (chapter) {
            chapter.parity_image = imgUrl;
            showToast("Imagen de paridad asignada al capítulo", "fa-solid fa-image");
            
            // Mark as dirty and auto-save
            triggerEditorUpdate();
            
            // Si el motor ya está compilando, podríamos forzar un refresco
            if (typeof compilePDFPreview === 'function') {
                compilePDFPreview();
            }
        }
    });

    parityMediaUploader.open();
}

function updateParityButtonVisibility() {
    const btn = document.getElementById('btn-parity-image');
    if (!btn) return;
    
    if (bookState && bookState.settings && bookState.settings.chapter_start_parity === 'odd') {
        btn.classList.remove('hidden');
    } else {
        btn.classList.add('hidden');
    }
}

function insertAtCursor(text) {
    const textarea = document.getElementById('editor-textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const value = textarea.value;
    
    textarea.value = value.substring(0, start) + text + value.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + text.length;
    textarea.focus();
    triggerEditorUpdate();
}

function triggerEditorUpdate() {
    const textarea = document.getElementById('editor-textarea');
    if (!textarea) return;
    
    const activeId = bookState.activeChapterId;
    const chapter = bookState.chapters.find(c => c.id === activeId);
    if (chapter) {
        chapter.content = textarea.value;
        if (typeof updateWordCounts === 'function') updateWordCounts();
        if (typeof compilePDFPreview === 'function') compilePDFPreview();
        if (typeof saveStateToLocalStorage === 'function') saveStateToLocalStorage();
    }
}

// Selector de lenguaje para hyphens
function toggleLangDropdown() {
    const dropdown = document.getElementById('lang-dropdown');
    dropdown.classList.toggle('hidden');
}

// Cierra el dropdown si se hace click fuera
document.addEventListener('click', function(event) {
    const wrapper = document.getElementById('lang-selector-wrapper');
    const dropdown = document.getElementById('lang-dropdown');
    if (wrapper && dropdown && !wrapper.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

function applyLanguage(langCode) {
    wrapText(`[lang:${langCode}]`, '[/lang]');
    document.getElementById('lang-dropdown').classList.add('hidden');
}

function removeLanguage() {
    // Esto es un poco más complejo, requiere buscar en la selección actual
    // y remover las etiquetas. Por simplicidad, el usuario puede borrarlas a mano.
    // Dejaremos la estructura por si más adelante queremos hacer un regex replace en la selección.
    showToast("Borra las etiquetas [lang] manualmente en el editor.", "fa-solid fa-circle-info");
    document.getElementById('lang-dropdown').classList.add('hidden');
}

// Funciones para aplicar estilos desde la barra de herramientas
function applyFontSize() {
    const input = document.getElementById('toolbar-font-size');
    if (input && input.value) {
        wrapText(`[size=${input.value}]`, '[/size]');
        // Devolvemos el foco al editor
        const textarea = document.getElementById('editor-textarea');
        if (textarea) textarea.focus();
    }
}

function applyFontFamily(fontName) {
    if (fontName) {
        wrapText(`[font="${fontName}"]`, '[/font]');
        // Reset the select box to show "Fuente..." again
        const select = document.getElementById('toolbar-font-family');
        if (select) select.selectedIndex = 0;
        
        const textarea = document.getElementById('editor-textarea');
        if (textarea) textarea.focus();
    }
}

