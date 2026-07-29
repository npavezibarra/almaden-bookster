let chapterWordCountCache = {};

function getWordCount(text) {
    if (typeof text !== 'string') return 0;
    const cleanText = text.trim();
    return cleanText === '' ? 0 : cleanText.split(/\s+/).length;
}

// Cuenta y actualiza las palabras en tiempo real
function updateWordCounts() {
    const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
    const text = activeChapter ? (activeChapter.content || '') : '';
    const wordCount = getWordCount(text);
    
    const currentWordCountEl = document.getElementById('current-word-count');
    if (currentWordCountEl) {
        currentWordCountEl.textContent = `${wordCount} ${wordCount === 1 ? 'palabra' : 'palabras'}`;
    }

    // Calcular palabras totales del libro completo usando caché para optimizar rendimiento
    let total = 0;
    bookState.chapters.forEach(c => {
        if (c.id === bookState.activeChapterId) {
            total += wordCount;
            chapterWordCountCache[c.id] = { length: text.length, count: wordCount };
        } else {
            const content = c.content || '';
            const cached = chapterWordCountCache[c.id];
            if (!cached || cached.length !== content.length) {
                const count = getWordCount(content);
                chapterWordCountCache[c.id] = { length: content.length, count: count };
                total += count;
            } else {
                total += cached.count;
            }
        }
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
    listContainer.classList.remove('space-y-1');
    listContainer.classList.add('divide-y', 'divide-[var(--border-color)]', 'space-y-0');

    let chapterDisplayNumber = 1;

    bookState.chapters.forEach((chapter, index) => {
        const isActive = chapter.id === bookState.activeChapterId;
        
        const chapterEl = document.createElement('div');
        chapterEl.className = `group flex items-stretch justify-between w-full cursor-pointer transition-colors ${
            isActive
                ? 'bg-neutral-100 dark:bg-neutral-800'
                : 'bg-transparent hover:bg-[var(--bg-app)]'
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
            <div class="flex items-center gap-3 overflow-hidden flex-1 pl-4 pr-2 py-3">
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
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity no-print pr-2 self-center">
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
                chapterEl.classList.add('ring-2', 'ring-black', 'ring-offset-1', 'scale-[1.01]');
            }
        });

        chapterEl.addEventListener('dragleave', (e) => {
            chapterEl.classList.remove('ring-2', 'ring-black', 'ring-offset-1', 'scale-[1.01]');
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
    const textInput = typeof getRawEditorSurface === 'function' ? getRawEditorSurface() : null;
    const rawEditorContainer = document.getElementById('raw-editor-container');

    if (chapter) {
        if (titleInput) titleInput.value = chapter.title;
        
        const creditsContainer = document.getElementById('credits-editor-container');
        
        if (chapter.is_credits === '1') {
            if (rawEditorContainer) rawEditorContainer.classList.add('hidden');
            if (textInput) {
                textInput.classList.remove('flex-1');
                textInput.style.minHeight = '150px';
                textInput.style.flex = 'none';
                textInput.value = chapter.content || '';
                textInput.placeholder = 'Escribe contenido o inserta imágenes para la sección superior de los créditos...';
                textInput.readOnly = false;
                textInput.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            if (creditsContainer) creditsContainer.classList.remove('hidden');
            if (typeof initCreditsForm === 'function') initCreditsForm();
        } else {
            if (creditsContainer) creditsContainer.classList.add('hidden');
            if (rawEditorContainer) rawEditorContainer.classList.remove('hidden');
            if (textInput) {
                textInput.classList.add('flex-1');
                textInput.style.minHeight = '400px';
                textInput.style.flex = '';
                textInput.value = chapter.content || '';
                textInput.placeholder = 'Escribe tu historia aquí utilizando formato simple o las herramientas de arriba...';
                if (chapter.is_toc === '1') {
                    textInput.readOnly = true;
                    textInput.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    textInput.readOnly = false;
                    textInput.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        }
        updateWordCounts();
        if (bookState.viewMode === 'preview' && typeof compilePDFPreview === 'function') {
            compilePDFPreview(true);
        } else if (typeof refreshEditorDisplay === 'function') {
            refreshEditorDisplay(false);
        }
    } else if (bookState.chapters.length > 0) {
        // Si el activo no existe pero hay capítulos, seleccionar el primero
        bookState.activeChapterId = bookState.chapters[0].id;
        loadActiveChapter();
    } else {
        // No hay capítulos
        if (titleInput) titleInput.value = '';
        if (textInput) textInput.value = '';
        if (rawEditorContainer) rawEditorContainer.classList.remove('hidden');
        updateWordCounts();
        if (bookState.viewMode === 'preview' && typeof compilePDFPreview === 'function') {
            compilePDFPreview(true);
        } else if (typeof refreshEditorDisplay === 'function') {
            refreshEditorDisplay(false);
        }
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
        is_credits: isCredits ? '1' : '0',
        start_parity: isToc ? 'even' : 'any',
        opening_separate_content: '',
        credits_hide_header: '0',
        credits_hide_page_number: '0',
        credits_margin_top: '',
        credits_margin_bottom: '',
        toc_hide_header: isToc ? '1' : '0',
        toc_hide_page_numbers: isToc ? '1' : '0',
        toc_separate_opening_content: ''
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
        const chapter = bookState.chapters[chapterIndex];
        const chapterIdIsPersisted = typeof chapter.id === 'string' ? /^[0-9]+$/.test(chapter.id) : Number.isInteger(chapter.id);

        const removeChapterLocally = () => {
            bookState.chapters.splice(chapterIndex, 1);

            // Si borramos el capítulo activo, reasignar activo
            if (bookState.activeChapterId === id) {
                if (bookState.chapters.length > 0) {
                    bookState.activeChapterId = bookState.chapters[Math.max(0, chapterIndex - 1)].id;
                } else {
                    bookState.activeChapterId = null;
                }
            }

            if (bookState.activeChapterId) {
                localStorage.setItem(`almaden_active_chapter_${bookState.bookId}`, bookState.activeChapterId);
            } else {
                localStorage.removeItem(`almaden_active_chapter_${bookState.bookId}`);
            }

            renderSidebar();
            loadActiveChapter();
            saveStateToLocalStorage(true);
            showToast(`"${chapter.title}" fue eliminado`, "fa-solid fa-trash-can");
        };

        if (!chapterIdIsPersisted) {
            removeChapterLocally();
            return;
        }

        const data = new FormData();
        data.append('action', 'almaden_delete_book_chapter');
        data.append('book_id', bookState.bookId);
        data.append('chapter_id', id);
        data.append('nonce', bookState.nonce);

        fetch(bookState.ajaxUrl, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(res => {
            if (!res.success) {
                const message = res && res.data ? res.data : 'No se pudo eliminar el capítulo.';
                showToast(message, "fa-solid fa-circle-exclamation");
                return;
            }
            removeChapterLocally();
        })
        .catch(() => {
            showToast("Error al eliminar el capítulo", "fa-solid fa-circle-exclamation");
        });
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
            let w = realScroller ? realScroller.clientWidth : 0;
            if (w <= 0) w = 800; // Fallback si está oculto en modo Solo Editor
            dummyScroller.style.width = w + 'px';
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

        // Asegura que la última edición visual se vuelque al capítulo activo antes de serializar.
        let visualChapterContent = null;
        if (bookState && bookState.viewMode === 'split' && typeof getVisualEditorSurface === 'function' && typeof serializeVisualEditorSurface === 'function') {
            const surface = getVisualEditorSurface();
            if (surface) {
                if (typeof syncVisualEditorToState === 'function') {
                    visualChapterContent = syncVisualEditorToState();
                } else {
                    visualChapterContent = serializeVisualEditorSurface(surface);
                }

                if (visualChapterContent === null) {
                    // Si la vista visual no expone un bloque editable para este capítulo,
                    // preservamos la versión del textarea en lugar de reemplazarla por vacío.
                    if (typeof syncRawEditorToState === 'function') {
                        syncRawEditorToState();
                    }
                } else {
                    const activeChapter = bookState.chapters.find(ch => ch.id === bookState.activeChapterId);
                    if (activeChapter) {
                        activeChapter.content = visualChapterContent;
                    }
                    const textarea = typeof getRawEditorSurface === 'function' ? getRawEditorSurface() : null;
                    if (textarea) {
                        textarea.value = visualChapterContent;
                    }
                }
            }
        } else if (typeof syncRawEditorToState === 'function') {
            syncRawEditorToState();
        }
        const saveRevision = window.visualEditorRevision || 0;
        
        let totalPages = 0;
        
        // Compilar PDF JUSTO ANTES de guardar cuando estamos en modo de vista previa.
        if (typeof compilePDFPreview === 'function' || typeof refreshEditorDisplay === 'function') {
            if (immediate && typeof calculateAllPagesBackground === 'function') {
                // 1. Calcular las páginas reales totales de fondo SOLO en guardado manual
                // Esto actualiza window.bookChapterPages con las posiciones correctas
                totalPages = await calculateAllPagesBackground();
            } else {
                // En autosave, rescatamos el total de páginas previo para evitar Layout Thrashing
                const totalPagesSidebarEl = document.getElementById('total-pages-sidebar');
                if (totalPagesSidebarEl) {
                    totalPages = parseInt(totalPagesSidebarEl.textContent) || 0;
                }
            }
            
            if (bookState.viewMode === 'preview' && typeof compilePDFPreview === 'function') {
                compilePDFPreview();
            }
        }

        const data = new FormData();
        data.append('action', 'almaden_save_book');
        data.append('book_id', bookState.bookId);
        data.append('nonce', bookState.nonce);
        data.append('title', bookState.title);
        const chaptersPayload = Array.isArray(bookState.chapters)
            ? bookState.chapters.map((chapter) => {
                const payloadChapter = {
                    id: chapter.id,
                    title: chapter.title,
                    content: chapter.content,
                    parity_image: chapter.parity_image,
                    opening_page_mode: chapter.opening_page_mode,
                    opening_blank_intentional: chapter.opening_blank_intentional,
                    opening_block_enabled: chapter.opening_block_enabled,
                    opening_block_horizontal_align: chapter.opening_block_horizontal_align,
                    opening_block_vertical_align: chapter.opening_block_vertical_align,
                    hide_title: chapter.hide_title,
                    hide_all_headers_footers: chapter.hide_all_headers_footers,
                    exclude_from_numbering: chapter.exclude_from_numbering,
                    custom_running_header: chapter.custom_running_header,
                    subtitle_text: chapter.subtitle_text,
                    subtitle_font_family: chapter.subtitle_font_family,
                    subtitle_align: chapter.subtitle_align,
                    subtitle_font_size: chapter.subtitle_font_size,
                    subtitle_letter_spacing: chapter.subtitle_letter_spacing,
                    subtitle_font_style: chapter.subtitle_font_style,
                    subtitle_text_transform: chapter.subtitle_text_transform,
                    subtitle_font_weight: chapter.subtitle_font_weight,
                    subtitle_margin_top: chapter.subtitle_margin_top,
                    subtitle_margin_bottom: chapter.subtitle_margin_bottom,
                    drop_cap_enabled: chapter.drop_cap_enabled,
                    disable_hyphenation: chapter.disable_hyphenation,
                    start_parity: chapter.start_parity,
                    first_page_header_type: chapter.first_page_header_type,
                    first_page_header_custom: chapter.first_page_header_custom,
                    first_page_footer_type: chapter.first_page_footer_type,
                    first_page_footer_custom: chapter.first_page_footer_custom,
                    opening_separate_content: chapter.opening_separate_content,
                    chapter_image_enabled: chapter.chapter_image_enabled,
                    chapter_image_mode: chapter.chapter_image_mode,
                    chapter_image_url: chapter.chapter_image_url,
                    chapter_image_inner_width: chapter.chapter_image_inner_width,
                    chapter_image_inner_header: chapter.chapter_image_inner_header,
                    chapter_image_inner_footer: chapter.chapter_image_inner_footer,
                    parity_image_mode: chapter.parity_image_mode,
                    parity_image_width: chapter.parity_image_width,
                    parity_image_height: chapter.parity_image_height,
                    is_toc: chapter.is_toc,
                    is_credits: chapter.is_credits,
                    credits_font_family: chapter.credits_font_family,
                    credits_align: chapter.credits_align,
                    credits_font_size: chapter.credits_font_size,
                    credits_letter_spacing: chapter.credits_letter_spacing,
                    credits_font_weight: chapter.credits_font_weight,
                    credits_hide_header: chapter.credits_hide_header,
                    credits_hide_page_number: chapter.credits_hide_page_number,
                    credits_margin_top: chapter.credits_margin_top,
                    credits_margin_bottom: chapter.credits_margin_bottom,
                    toc_font_family: chapter.toc_font_family,
                    toc_font_size: chapter.toc_font_size,
                    toc_enumerate: chapter.toc_enumerate,
                    toc_font_style: chapter.toc_font_style,
                    toc_font_weight: chapter.toc_font_weight,
                    toc_text_transform: chapter.toc_text_transform,
                    toc_letter_spacing: chapter.toc_letter_spacing,
                    toc_line_height: chapter.toc_line_height,
                    toc_item_spacing: chapter.toc_item_spacing,
                    toc_hide_header: chapter.toc_hide_header,
                    toc_hide_page_numbers: chapter.toc_hide_page_numbers,
                    toc_separate_opening_content: chapter.toc_separate_opening_content,
                    toc_item_align: chapter.toc_item_align,
                    toc_leader_style: chapter.toc_leader_style,
                    toc_leader_position: chapter.toc_leader_position,
                    toc_title_align: chapter.toc_title_align,
                    toc_title_font_family: chapter.toc_title_font_family,
                    toc_title_font_size: chapter.toc_title_font_size,
                    toc_title_font_style: chapter.toc_title_font_style,
                    toc_title_text_transform: chapter.toc_title_text_transform,
                    toc_title_font_weight: chapter.toc_title_font_weight,
                    toc_title_padding_top: chapter.toc_title_padding_top,
                    toc_title_padding_bottom: chapter.toc_title_padding_bottom,
                    toc_title_line_height: chapter.toc_title_line_height
                };

                if (visualChapterContent !== null && chapter.id === bookState.activeChapterId) {
                    payloadChapter.content = visualChapterContent;
                }
                return payloadChapter;
            })
            : [];
        data.append('chapters', JSON.stringify(chaptersPayload));
        data.append('total_pages', totalPages || 0);
        const creditsConfig = typeof window.getCreditsConfigFromForm === 'function'
            ? window.getCreditsConfigFromForm()
            : (bookState.settings && bookState.settings.credits_config ? bookState.settings.credits_config : null);
        if (creditsConfig && typeof window.saveCreditsConfig === 'function') {
            try {
                await window.saveCreditsConfig(creditsConfig);
            } catch (error) {
                console.warn('No se pudo sincronizar la configuracion de creditos antes del guardado general:', error);
            }
        }
        if (creditsConfig) {
            data.append('credits_config', JSON.stringify(creditsConfig));
        }

        fetch(bookState.ajaxUrl, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                const isLatestVisualRevision = saveRevision === (window.visualEditorRevision || 0);
                if (isLatestVisualRevision) {
                    window.visualEditorIsDirty = false;
                    const activeElement = document.activeElement;
                    window.visualEditorIsEditing = typeof isVisualEditorSurface === 'function'
                        ? isVisualEditorSurface(activeElement)
                        : false;
                    if (statusIndicator) {
                        statusIndicator.innerHTML = '<i class="fa-solid fa-cloud-arrow-up text-xs mr-1"></i> Guardado';
                        statusIndicator.className = 'flex items-center gap-1 font-semibold text-emerald-600';
                    }
                } else if (statusIndicator) {
                    statusIndicator.innerHTML = '<i class="fa-solid fa-pen text-xs mr-1"></i> Editando...';
                    statusIndicator.className = 'flex items-center gap-1 font-semibold text-slate-500';
                }
                
                // Update temporary chapter IDs with real database IDs to prevent metadata loss
                if (res.chapters && Array.isArray(res.chapters)) {
                    let stateChanged = false;
                    res.chapters.forEach(serverCh => {
                        if (serverCh.old_id) {
                            const localCh = bookState.chapters.find(c => c.id === serverCh.old_id);
                            if (localCh) {
                                Object.assign(localCh, serverCh);
                                localCh.id = serverCh.id;
                                if (bookState.activeChapterId === serverCh.old_id) {
                                    bookState.activeChapterId = serverCh.id;
                                    localStorage.setItem(`almaden_active_chapter_${bookState.bookId}`, serverCh.id);
                                }
                                stateChanged = true;
                            }
                        } else {
                            const localCh = bookState.chapters.find(c => c.id === serverCh.id);
                            if (localCh) {
                                Object.assign(localCh, serverCh);
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
        const autosaveDelay = (bookState && bookState.viewMode === 'split') ? 1200 : 15000;
        saveTimeout = setTimeout(executeSave, autosaveDelay);
    }
}
