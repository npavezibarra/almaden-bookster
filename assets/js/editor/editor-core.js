// Manejo del estado, editor y lógica principal

window.editorLastSelection = { start: 0, end: 0 };

function trackEditorSelection() {
    const textarea = document.getElementById('editor-textarea');
    if (textarea && document.activeElement === textarea) {
        window.editorLastSelection.start = textarea.selectionStart;
        window.editorLastSelection.end = textarea.selectionEnd;
        window.editorSelectionSurface = 'raw';
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

    // Poblar los campos de configuración en el DOM para evitar sobreescritura con campos vacíos
    if (typeof window.populateSettingsForm === 'function') {
        window.populateSettingsForm();
    }
    if (typeof window.initCreditsForm === 'function') {
        window.initCreditsForm();
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
            // Después de calcular todas las páginas, re-renderizamos solo la superficie visible
            if (bookState.viewMode === 'split' && typeof refreshSplitPreview === 'function') {
                refreshSplitPreview(false);
            } else if (typeof refreshEditorDisplay === 'function') {
                refreshEditorDisplay(false);
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
    
    // Check if URL has open_settings flag
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('open_settings') === '1') {
        if (typeof toggleSettingsModal === 'function') {
            setTimeout(() => toggleSettingsModal(true), 500);
        }
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
                if (typeof updateVisualEditorFromState === 'function' && bookState.viewMode === 'split') {
                    updateVisualEditorFromState();
                }
                if (bookState.viewMode === 'split' && typeof scheduleSplitPreviewRefresh === 'function') {
                    scheduleSplitPreviewRefresh(false);
                }
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
                if (bookState.viewMode === 'split' && typeof scheduleSplitPreviewRefresh === 'function') {
                    scheduleSplitPreviewRefresh(false);
                }
                saveStateToLocalStorage();
            }
        });
    }

    if (bookTitle) {
        bookTitle.addEventListener('input', () => {
            bookState.title = bookTitle.value;
            if (bookState.viewMode === 'split' && typeof scheduleSplitPreviewRefresh === 'function') {
                scheduleSplitPreviewRefresh(false);
            }
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
