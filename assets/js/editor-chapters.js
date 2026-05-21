// Cuenta y actualiza las palabras en tiempo real
function updateWordCounts() {
    const textEl = document.getElementById('editor-textarea');
    if (!textEl) return;
    const text = textEl.value;
    const cleanText = text.trim();
    const wordCount = cleanText === '' ? 0 : cleanText.split(/\s+/).length;
    
    const currentWordCountEl = document.getElementById('current-word-count');
    if (currentWordCountEl) {
        currentWordCountEl.textContent = `${wordCount} ${wordCount === 1 ? 'palabra' : 'palabras'}`;
    }

    // Calcular palabras totales del libro completo
    let total = 0;
    bookState.chapters.forEach(c => {
        const cText = c.content.trim();
        total += cText === '' ? 0 : cText.split(/\s+/).length;
    });
    const totalWordsEl = document.getElementById('total-words');
    if (totalWordsEl) {
        totalWordsEl.textContent = total.toLocaleString();
    }
}

// Renderiza los capítulos en la barra lateral con interacciones premium
function renderSidebar() {
    const listContainer = document.getElementById('chapters-list');
    if (!listContainer) return;
    listContainer.innerHTML = '';

    bookState.chapters.forEach((chapter, index) => {
        const isActive = chapter.id === bookState.activeChapterId;
        
        const chapterEl = document.createElement('div');
        chapterEl.className = `group flex items-center justify-between p-3 rounded-xl cursor-pointer transition-all border ${
            isActive 
            ? 'bg-gradient-to-r from-indigo-50/80 to-indigo-100/30 border-indigo-200 dark:from-indigo-950/40 dark:to-slate-900/40 dark:border-indigo-800' 
            : 'border-transparent hover:bg-[var(--bg-app)]'
        }`;
        chapterEl.setAttribute('onclick', `selectChapter('${chapter.id}')`);

        chapterEl.innerHTML = `
            <div class="flex items-center gap-3 overflow-hidden">
                <span class="text-xs font-bold text-indigo-500/80 dark:text-indigo-400/80 group-hover:scale-110 transition-transform">${index + 1}</span>
                <div class="truncate">
                    <h4 class="text-sm font-semibold truncate ${isActive ? 'text-indigo-700 dark:text-indigo-400' : 'text-[var(--text-main)]'}">${chapter.title || 'Capítulo sin título'}</h4>
                    <p class="text-[10px] text-[var(--text-muted)] truncate">${getExcerpt(chapter.content)}</p>
                </div>
            </div>
            <!-- Acciones del Capítulo -->
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity no-print">
                <button onclick="event.stopPropagation(); moveChapterUp(${index})" class="p-1 hover:text-indigo-600 dark:hover:text-indigo-400 text-[var(--text-muted)] transition" title="Subir capítulo">
                    <i class="fa-solid fa-chevron-up text-xs"></i>
                </button>
                <button onclick="event.stopPropagation(); deleteChapter('${chapter.id}')" class="p-1 hover:text-rose-600 text-[var(--text-muted)] transition" title="Eliminar capítulo">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                </button>
            </div>
        `;

        listContainer.appendChild(chapterEl);
    });

    // Actualizar contador del total de capítulos
    const chapterCountEl = document.getElementById('chapter-count');
    if (chapterCountEl) {
        chapterCountEl.textContent = bookState.chapters.length;
    }
}

// Obtiene un fragmento breve de texto para previsualizar en el sidebar
function getExcerpt(content) {
    if (!content) return 'Vacío...';
    // Remover sintaxis de títulos o markdown para extracto
    const clean = content.replace(/[#*>\-_[\]]/g, '').trim();
    if (clean.length > 35) return clean.substring(0, 35) + '...';
    return clean || 'Vacío...';
}

// Carga el capítulo activo en los campos del editor
function loadActiveChapter() {
    const activeId = bookState.activeChapterId;
    const chapter = bookState.chapters.find(c => c.id === activeId);
    
    const titleInput = document.getElementById('chapter-title-input');
    const textInput = document.getElementById('editor-textarea');

    if (chapter) {
        if (titleInput) titleInput.value = chapter.title;
        if (textInput) textInput.value = chapter.content;
        updateWordCounts();
        compilePDFPreview();
    } else if (bookState.chapters.length > 0) {
        // Si el activo no existe pero hay capítulos, seleccionar el primero
        bookState.activeChapterId = bookState.chapters[0].id;
        loadActiveChapter();
    } else {
        // No hay capítulos
        if (titleInput) titleInput.value = '';
        if (textInput) textInput.value = '';
        updateWordCounts();
        compilePDFPreview();
    }
}

// Selecciona un capítulo específico
function selectChapter(id) {
    bookState.activeChapterId = id;
    loadActiveChapter();
    renderSidebar();
    saveStateToLocalStorage();
}

// Crea un nuevo capítulo
function createNewChapter() {
    const newIndex = bookState.chapters.length + 1;
    const newId = `cap-${Date.now()}`;
    const newChapter = {
        id: newId,
        title: `Capítulo ${newIndex}: Título Nuevo`,
        content: `# Capítulo ${newIndex}\n## Título Nuevo\n\nComienza a escribir la historia de este capítulo aquí...`
    };

    bookState.chapters.push(newChapter);
    bookState.activeChapterId = newId;
    
    renderSidebar();
    loadActiveChapter();
    saveStateToLocalStorage();
    showToast("Capítulo creado", "fa-solid fa-plus-circle");
}

// Elimina un capítulo
function deleteChapter(id) {
    const chapterIndex = bookState.chapters.findIndex(c => c.id === id);
    if (chapterIndex === -1) return;

    if (confirm(`¿Estás seguro de que deseas eliminar "${bookState.chapters[chapterIndex].title}"? Esta acción no se puede deshacer.`)) {
        bookState.chapters.splice(chapterIndex, 1);
        
        // Si borramos el capítulo activo, reasignar activo
        if (bookState.activeChapterId === id) {
            if (bookState.chapters.length > 0) {
                bookState.activeChapterId = bookState.chapters[Math.max(0, chapterIndex - 1)].id;
            } else {
                bookState.activeChapterId = null;
            }
        }

        renderSidebar();
        loadActiveChapter();
        saveStateToLocalStorage();
        showToast("Capítulo eliminado", "fa-solid fa-trash-can");
    }
}

// Mueve un capítulo hacia arriba en el índice
function moveChapterUp(index) {
    if (index === 0) return; // Ya es el primero
    
    // Intercambiar
    const temp = bookState.chapters[index];
    bookState.chapters[index] = bookState.chapters[index - 1];
    bookState.chapters[index - 1] = temp;

    renderSidebar();
    saveStateToLocalStorage();
    showToast("Índice reordenado", "fa-solid fa-sort");
}

let saveTimeout;
// Guarda el estado actual en la base de datos de WordPress (autosave)
function saveStateToLocalStorage() {
    const statusIndicator = document.getElementById('save-status');
    if (statusIndicator) {
        statusIndicator.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs mr-1"></i> Guardando...';
        statusIndicator.className = 'flex items-center gap-1 font-semibold text-amber-500';
    }

    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        const data = new FormData();
        data.append('action', 'almaden_save_book');
        data.append('book_id', bookState.bookId);
        data.append('nonce', bookState.nonce);
        data.append('title', bookState.title);
        data.append('chapters', JSON.stringify(bookState.chapters));

        fetch(bookState.ajaxUrl, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                if (statusIndicator) {
                    statusIndicator.innerHTML = '<i class="fa-solid fa-cloud-arrow-up text-xs mr-1"></i> Guardado';
                    statusIndicator.className = 'flex items-center gap-1 font-semibold text-emerald-600';
                }
            } else {
                if (statusIndicator) {
                    statusIndicator.innerHTML = '<i class="fa-solid fa-circle-exclamation text-xs mr-1"></i> Error';
                    statusIndicator.className = 'flex items-center gap-1 font-semibold text-rose-600';
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (statusIndicator) {
                statusIndicator.innerHTML = '<i class="fa-solid fa-wifi text-xs mr-1"></i> Error red';
                statusIndicator.className = 'flex items-center gap-1 font-semibold text-rose-600';
            }
        });
    }, 1000); // 1 segundo de debounce para evitar saturar el servidor
}
