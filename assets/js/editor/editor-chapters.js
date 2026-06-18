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

let draggedChapterIndex = null;

// Renderiza los capítulos en la barra lateral con interacciones premium
function renderSidebar() {
    const listContainer = document.getElementById('chapters-list');
    if (!listContainer) return;
    listContainer.innerHTML = '';

    let chapterDisplayNumber = 1;

    bookState.chapters.forEach((chapter, index) => {
        const isActive = chapter.id === bookState.activeChapterId;
        
        const chapterEl = document.createElement('div');
        chapterEl.className = `group flex items-center justify-between p-3 rounded-xl cursor-pointer transition-all border ${
            isActive 
            ? 'bg-neutral-100 border-neutral-300 dark:bg-neutral-800 dark:border-neutral-700' 
            : 'border-transparent hover:bg-[var(--bg-app)]'
        }`;
        chapterEl.setAttribute('onclick', `selectChapter('${chapter.id}')`);

        let chapterPagesStr = '';
        if (window.bookChapterLengths && window.bookChapterLengths[chapter.id] !== undefined) {
            chapterPagesStr = `<span class="text-[9px] text-neutral-400 font-medium whitespace-nowrap"><i class="fa-regular fa-file-lines mr-0.5"></i> ${window.bookChapterLengths[chapter.id]} p.</span>`;
        }

        // Determinar número a mostrar en sidebar
        let displayStr = '-';
        if (chapter.is_toc !== '1' && chapter.is_credits !== '1' && chapter.exclude_from_numbering !== '1') {
            displayStr = chapterDisplayNumber;
            chapterDisplayNumber++;
        }

        chapterEl.innerHTML = `
            <div class="flex items-center gap-3 overflow-hidden w-full">
                <span class="text-xs font-bold text-neutral-500/80 dark:text-neutral-400/80 group-hover:scale-110 transition-transform">${displayStr}</span>
                <div class="truncate flex-1">
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="text-sm font-bold truncate ${isActive ? 'text-black dark:text-white' : 'text-[var(--text-main)]'}">
                            ${chapter.is_toc == '1' ? '<i class="fa-solid fa-list-ol mr-1"></i> ' : ''}${chapter.is_credits == '1' ? '<i class="fa-solid fa-copyright mr-1"></i> ' : ''}${chapter.title || 'Capítulo sin título'}
                        </h4>
                        ${chapterPagesStr}
                    </div>
                </div>
            </div>
            <!-- Acciones del Capítulo -->
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity no-print">
                <button onclick="event.stopPropagation(); moveChapterUp(${index})" class="p-1 hover:text-black dark:hover:text-white text-[var(--text-muted)] transition" title="Subir capítulo">
                    <i class="fa-solid fa-chevron-up text-xs"></i>
                </button>
                <button onclick="event.stopPropagation(); deleteChapter('${chapter.id}')" class="p-1 hover:text-rose-600 text-[var(--text-muted)] transition" title="Eliminar capítulo">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                </button>
            </div>
        `;

        chapterEl.setAttribute('draggable', 'true');
        
        chapterEl.addEventListener('dragstart', (e) => {
            draggedChapterIndex = index;
            e.dataTransfer.effectAllowed = 'move';
            // setTimeout prevents the element from disappearing while being dragged
            setTimeout(() => {
                chapterEl.classList.add('opacity-30', 'scale-95');
                document.body.classList.add('is-dragging-chapter');
            }, 0);
        });

        chapterEl.addEventListener('dragend', (e) => {
            chapterEl.classList.remove('opacity-30', 'scale-95');
            document.body.classList.remove('is-dragging-chapter');
            draggedChapterIndex = null;
            renderSidebar();
        });

        chapterEl.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        chapterEl.addEventListener('dragenter', (e) => {
            e.preventDefault();
            if (draggedChapterIndex !== null && draggedChapterIndex !== index) {
                chapterEl.classList.add('ring-2', 'ring-black', 'ring-offset-1', 'scale-[1.02]', 'bg-neutral-100', 'dark:bg-neutral-850/40');
                chapterEl.classList.remove('border-transparent');
            }
        });

        chapterEl.addEventListener('dragleave', (e) => {
            chapterEl.classList.remove('ring-2', 'ring-black', 'ring-offset-1', 'scale-[1.02]', 'bg-neutral-100', 'dark:bg-neutral-850/40');
            if (!isActive) chapterEl.classList.add('border-transparent');
        });

        chapterEl.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            if (draggedChapterIndex !== null && draggedChapterIndex !== index) {
                const draggedItem = bookState.chapters.splice(draggedChapterIndex, 1)[0];
                bookState.chapters.splice(index, 0, draggedItem);
                
                renderSidebar();
                saveStateToLocalStorage();
                showToast("Capítulos reordenados", "fa-solid fa-sort");
            }
        });

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
        if (textInput) {
            textInput.value = chapter.content;
            if (chapter.is_toc === '1' || chapter.is_credits === '1') {
                textInput.readOnly = true;
                textInput.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                textInput.readOnly = false;
                textInput.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
        updateWordCounts();
        compilePDFPreview(true);
    } else if (bookState.chapters.length > 0) {
        // Si el activo no existe pero hay capítulos, seleccionar el primero
        bookState.activeChapterId = bookState.chapters[0].id;
        loadActiveChapter();
    } else {
        // No hay capítulos
        if (titleInput) titleInput.value = '';
        if (textInput) textInput.value = '';
        updateWordCounts();
        compilePDFPreview(true);
    }
}

// Selecciona un capítulo específico
function selectChapter(id) {
    bookState.activeChapterId = id;
    localStorage.setItem(`almaden_active_chapter_${bookState.bookId}`, id);
    loadActiveChapter();
    renderSidebar();
    saveStateToLocalStorage();
}

// Crea un nuevo capítulo
function createNewChapter(isToc = false, isCredits = false) {
    // Si ya existe un TOC, no permitir otro
    if (isToc && bookState.chapters.some(c => c.is_toc == '1')) {
        showToast("Ya existe un Índice", "fa-solid fa-circle-exclamation");
        return;
    }

    if (isCredits && bookState.chapters.some(c => c.is_credits == '1')) {
        showToast("Ya existe una página de Créditos", "fa-solid fa-circle-exclamation");
        return;
    }

    const newIndex = bookState.chapters.length + 1;
    const newId = `cap-${Date.now()}`;
    const newChapter = {
        id: newId,
        title: isToc ? 'Índice' : (isCredits ? 'Créditos' : `Capítulo ${newIndex}: Título Nuevo`),
        content: isToc ? `En este capítulo se generará automáticamente el Índice de contenidos.` : (isCredits ? `En este capítulo se generará automáticamente la página de Créditos.` : `# Capítulo ${newIndex}\n## Título Nuevo\n\nComienza a escribir la historia de este capítulo aquí...`),
        is_toc: isToc ? '1' : '0',
        is_credits: isCredits ? '1' : '0'
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
function saveStateToLocalStorage(immediate = false) {
    const statusIndicator = document.getElementById('save-status');
    
    // Indicador visual de 'Pendiente' en cuanto se escribe, antes de guardar
    if (statusIndicator && !immediate && !saveTimeout) {
        statusIndicator.innerHTML = '<i class="fa-solid fa-pen text-xs mr-1"></i> Editando...';
        statusIndicator.className = 'flex items-center gap-1 font-semibold text-slate-500';
    }

    clearTimeout(saveTimeout);

    const calculateAllPagesBackground = async () => {
        let dummyScroller = document.getElementById('dummy-pdf-scroller');
        if (!dummyScroller) {
            dummyScroller = document.createElement('div');
            dummyScroller.id = 'dummy-pdf-scroller';
            dummyScroller.style.position = 'absolute';
            dummyScroller.style.visibility = 'hidden';
            dummyScroller.style.pointerEvents = 'none';
            dummyScroller.style.zIndex = '-9999';
            dummyScroller.style.top = '0';
            dummyScroller.style.left = '0';
            // Necesitamos que tenga dimensiones similares al visor real para que CSS de las páginas funcione igual
            const realScroller = document.getElementById('pdf-scroller');
            if (realScroller) {
                dummyScroller.style.width = realScroller.clientWidth + 'px';
            }
            document.body.appendChild(dummyScroller);
        }
        
        // Esperamos el cálculo del motor PDF (forceFull = true)
        const totalPages = await compilePDFPreview(false, 'dummy-pdf-scroller', true);
        dummyScroller.innerHTML = ''; // Liberar memoria
        return totalPages;
    };
    
    window.calculateAllPagesBackground = calculateAllPagesBackground;

    const executeSave = async () => {
        saveTimeout = null;
        if (statusIndicator) {
            statusIndicator.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs mr-1"></i> Guardando...';
            statusIndicator.className = 'flex items-center gap-1 font-semibold text-amber-500';
        }
        
        let totalPages = 0;
        
        // Compilar PDF JUSTO ANTES de guardar (para que refleje los cambios recientes)
        if (typeof compilePDFPreview === 'function') {
            // 1. Calcular las páginas reales totales en background de forma invisible PRIMERO
            // Esto actualiza window.bookChapterPages con las posiciones correctas
            if (typeof calculateAllPagesBackground === 'function') {
                totalPages = await calculateAllPagesBackground();
            }
            
            // 2. Actualizar la vista principal normalmente AHORA, usando los datos frescos
            compilePDFPreview();
        }

        const data = new FormData();
        data.append('action', 'almaden_save_book');
        data.append('book_id', bookState.bookId);
        data.append('nonce', bookState.nonce);
        data.append('title', bookState.title);
        data.append('chapters', JSON.stringify(bookState.chapters));
        data.append('total_pages', totalPages || 0);

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
                
                // Update temporary chapter IDs with real database IDs to prevent metadata loss
                if (res.chapters && Array.isArray(res.chapters)) {
                    let stateChanged = false;
                    res.chapters.forEach(serverCh => {
                        if (serverCh.old_id) {
                            const localCh = bookState.chapters.find(c => c.id === serverCh.old_id);
                            if (localCh) {
                                localCh.id = serverCh.id;
                                if (bookState.activeChapterId === serverCh.old_id) {
                                    bookState.activeChapterId = serverCh.id;
                                    localStorage.setItem(`almaden_active_chapter_${bookState.bookId}`, serverCh.id);
                                }
                                stateChanged = true;
                            }
                        }
                    });
                    if (stateChanged) {
                        renderSidebar();
                    }
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
    };

    if (immediate) {
        executeSave();
    } else {
        saveTimeout = setTimeout(executeSave, 15000); // 15 segundos de autosave
    }
}
