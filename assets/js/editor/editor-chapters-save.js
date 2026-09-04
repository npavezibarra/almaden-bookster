// assets/js/editor/editor-chapters-save.js

let saveTimeout = null;
let activeSavePromise = null;
let pendingSaveBatch = null;

function runPendingSave() {
    if (activeSavePromise || !pendingSaveBatch) return;
    const batch = pendingSaveBatch;
    pendingSaveBatch = null;
    clearTimeout(saveTimeout);
    saveTimeout = null;

    activeSavePromise = Promise.resolve(batch.task()).catch(() => false);
    activeSavePromise.then(result => batch.resolvers.forEach(resolve => resolve(result))).finally(() => {
        activeSavePromise = null;
        if (pendingSaveBatch) runPendingSave();
    });
}

function scheduleSerializedSave(task, delay) {
    const promise = new Promise(resolve => {
        if (!pendingSaveBatch) {
            pendingSaveBatch = { task, resolvers: [resolve] };
        } else {
            pendingSaveBatch.task = task;
            pendingSaveBatch.resolvers.push(resolve);
        }
    });

    clearTimeout(saveTimeout);
    if (!activeSavePromise) {
        saveTimeout = setTimeout(runPendingSave, Math.max(0, delay));
    }
    return promise;
}

async function calculateAllPagesBackground() {
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
        const realScroller = document.getElementById('pdf-scroller');
        let w = realScroller ? realScroller.clientWidth : 0;
        if (w <= 0) w = 800;
        dummyScroller.style.width = `${w}px`;
        document.body.appendChild(dummyScroller);
    }

    const compilePromise = typeof compilePDFPreview === 'function'
        ? Promise.resolve(compilePDFPreview(false, 'dummy-pdf-scroller', true))
        : Promise.resolve(0);
    const timeoutPromise = new Promise((resolve) => {
        setTimeout(() => resolve(0), 5000);
    });
    const totalPages = await Promise.race([compilePromise, timeoutPromise]).catch(() => 0);

    dummyScroller.innerHTML = '';
    return totalPages;
}

function saveStateToLocalStorage(immediate = false) {
    const statusIndicator = document.getElementById('save-status');

    if (statusIndicator && !immediate) {
        statusIndicator.innerHTML = activeSavePromise
            ? '<i class="fa-solid fa-clock text-xs mr-1"></i> Cambios pendientes'
            : '<i class="fa-solid fa-pen text-xs mr-1"></i> Editando...';
        statusIndicator.className = 'flex items-center gap-1 font-semibold text-slate-500';
    }

    window.calculateAllPagesBackground = calculateAllPagesBackground;

    const executeSave = async () => {
        saveTimeout = null;
        let saveCompleted = false;
        if (statusIndicator) {
            statusIndicator.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs mr-1"></i> Guardando...';
            statusIndicator.className = 'flex items-center gap-1 font-semibold text-amber-500';
        }

        const saveWatchdog = setTimeout(() => {
            if (saveCompleted) return;
            if (statusIndicator) {
                statusIndicator.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs mr-1"></i> Guardando en segundo plano...';
                statusIndicator.className = 'flex items-center gap-1 font-semibold text-amber-600';
            }
        }, 10000);

        try {
            if (typeof syncRawEditorToState === 'function') {
                syncRawEditorToState();
            }
            const saveRevision = window.visualEditorRevision || 0;
            const guardedEmptyChapters = (bookState.chapters || []).filter(chapter => {
                const current = String(chapter.content || '').trim();
                const lastSaved = String(chapter._lastSavedContent || '').trim();
                return current === '' && lastSaved !== '';
            });
            let allowedEmptyChapterIds = [];
            if (guardedEmptyChapters.length) {
                const chapterNames = guardedEmptyChapters.map(chapter => `“${chapter.title || 'Sin título'}”`).join(', ');
                if (!immediate) {
                    if (statusIndicator) {
                        statusIndicator.innerHTML = '<i class="fa-solid fa-shield-halved text-xs mr-1"></i> Vacío sin guardar';
                        statusIndicator.className = 'flex items-center gap-1 font-semibold text-amber-600';
                    }
                    if (typeof showToast === 'function') {
                        showToast(`Se evitó vaciar accidentalmente ${chapterNames}. Usa Guardar para confirmarlo.`, 'fa-solid fa-shield-halved');
                    }
                    return false;
                }

                const confirmed = window.confirm(`El contenido de ${chapterNames} quedará completamente vacío. ¿Deseas guardarlo así?`);
                if (!confirmed) {
                    if (statusIndicator) {
                        statusIndicator.innerHTML = '<i class="fa-solid fa-shield-halved text-xs mr-1"></i> Guardado cancelado';
                        statusIndicator.className = 'flex items-center gap-1 font-semibold text-amber-600';
                    }
                    return false;
                }
                allowedEmptyChapterIds = guardedEmptyChapters.map(chapter => String(chapter.id));
            }

            let totalPages = 0;

            if (typeof compilePDFPreview === 'function' || typeof refreshEditorDisplay === 'function') {
                if (immediate && typeof calculateAllPagesBackground === 'function') {
                    totalPages = await calculateAllPagesBackground();
                } else {
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
                        hide_header: chapter.hide_header ?? (chapter.hide_all_headers_footers === '1' ? '1' : '0'),
                        hide_footer: chapter.hide_footer ?? (chapter.hide_all_headers_footers === '1' ? '1' : '0'),
                        hide_all_headers_footers: chapter.hide_all_headers_footers,
                        exclude_from_numbering: chapter.exclude_from_numbering,
                        chapter_blank_before: chapter.chapter_blank_before,
                        chapter_blank_after: chapter.chapter_blank_after,
                        custom_running_header: chapter.custom_running_header,
                        subtitle_text: chapter.subtitle_text,
                        subtitle_show: chapter.subtitle_show,
                        subtitle_font_family: chapter.subtitle_font_family,
                        subtitle_align: chapter.subtitle_align,
                        subtitle_font_size: chapter.subtitle_font_size,
                        subtitle_letter_spacing: chapter.subtitle_letter_spacing,
                        subtitle_font_style: chapter.subtitle_font_style,
                        subtitle_text_transform: chapter.subtitle_text_transform,
                        subtitle_font_weight: chapter.subtitle_font_weight,
                        subtitle_margin_top: chapter.subtitle_margin_top,
                        subtitle_margin_bottom: chapter.subtitle_margin_bottom,
                        subtitle_padding_top: chapter.subtitle_padding_top,
                        subtitle_padding_bottom: chapter.subtitle_padding_bottom,
                        subtitle_padding_left: chapter.subtitle_padding_left,
                        subtitle_padding_right: chapter.subtitle_padding_right,
                        drop_cap_enabled: chapter.drop_cap_enabled,
                        disable_hyphenation: chapter.disable_hyphenation,
                        start_parity: chapter.start_parity,
                        first_page_header_type: chapter.first_page_header_type,
                        first_page_header_custom: chapter.first_page_header_custom,
                        first_page_footer_type: chapter.first_page_footer_type,
                        first_page_footer_custom: chapter.first_page_footer_custom,
                        opening_separate_content: chapter.opening_separate_content,
                        chapter_image_override: chapter.chapter_image_override,
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
						toc_leader_thickness: chapter.toc_leader_thickness,
						toc_leader_min_width: chapter.toc_leader_min_width,
						toc_number_font_family: chapter.toc_number_font_family,
						toc_number_font_size: chapter.toc_number_font_size,
						toc_number_font_weight: chapter.toc_number_font_weight,
						toc_number_font_style: chapter.toc_number_font_style,
						toc_number_letter_spacing: chapter.toc_number_letter_spacing,
						toc_page_font_family: chapter.toc_page_font_family,
						toc_page_font_size: chapter.toc_page_font_size,
						toc_page_font_weight: chapter.toc_page_font_weight,
						toc_page_font_style: chapter.toc_page_font_style,
						toc_page_letter_spacing: chapter.toc_page_letter_spacing,
                        toc_hide_title: chapter.toc_hide_title,
                        toc_title_text: chapter.toc_title_text,
                        toc_title_align: chapter.toc_title_align,
                        toc_title_font_family: chapter.toc_title_font_family,
                        toc_title_font_size: chapter.toc_title_font_size,
                        toc_title_font_style: chapter.toc_title_font_style,
                        toc_title_text_transform: chapter.toc_title_text_transform,
                        toc_title_font_weight: chapter.toc_title_font_weight,
                        toc_title_letter_spacing: chapter.toc_title_letter_spacing,
                        toc_title_padding_top: chapter.toc_title_padding_top,
                        toc_title_padding_bottom: chapter.toc_title_padding_bottom,
                        toc_title_line_height: chapter.toc_title_line_height,
                        toc_page_number_offset: chapter.toc_page_number_offset
                    };

                    return payloadChapter;
                })
                : [];
            data.append('chapters', JSON.stringify(chaptersPayload));
            data.append('allow_empty_chapter_ids', JSON.stringify(allowedEmptyChapterIds));
            data.append('total_pages', totalPages || 0);
            const creditsConfig = typeof window.getCreditsConfigFromForm === 'function'
                ? window.getCreditsConfigFromForm()
                : (bookState.settings && bookState.settings.credits_config ? bookState.settings.credits_config : null);
            if (creditsConfig) {
                data.append('credits_config', JSON.stringify(creditsConfig));
            }

            const commerceState = typeof window.getCommerceStateFromForm === 'function'
                ? window.getCommerceStateFromForm()
                : (bookState.commerce || null);
            if (commerceState && commerceState.relation) {
                data.append('almaden_wc_relation_mode', commerceState.relation.product_mode || 'none');
                data.append('almaden_wc_product_id', commerceState.relation.product_id || 0);
                data.append('almaden_wc_parent_product_id', commerceState.relation.parent_product_id || 0);
                if (commerceState.create_wc_product) {
                    data.append('almaden_create_wc_product', '1');
                }
            }

            const response = await fetch(bookState.ajaxUrl, {
                method: 'POST',
                body: data
            });
            const responseText = await response.text();
            let res = null;
            try {
                res = JSON.parse(responseText);
            } catch (error) {
                throw new Error(`Respuesta de guardado inválida (HTTP ${response.status}).`);
            }
            if (!response.ok) {
                throw new Error(res?.data?.message || `El servidor respondió HTTP ${response.status}.`);
            }
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

                if (res.chapters && Array.isArray(res.chapters)) {
                    let stateChanged = false;
                    res.chapters.forEach(serverCh => {
                        if (serverCh.old_id) {
                            const localCh = bookState.chapters.find(c => c.id === serverCh.old_id);
                            if (localCh) {
                                const currentContent = localCh.content;
                                const currentTitle = localCh.title;
                                Object.assign(localCh, serverCh);
                                if (!isLatestVisualRevision) {
                                    localCh.content = currentContent;
                                    localCh.title = currentTitle;
                                }
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
                                const currentContent = localCh.content;
                                const currentTitle = localCh.title;
                                Object.assign(localCh, serverCh);
                                if (!isLatestVisualRevision) {
                                    localCh.content = currentContent;
                                    localCh.title = currentTitle;
                                }
                                stateChanged = true;
                            }
                        }
                    });
                    if (stateChanged) {
                        renderSidebar();
                    }
                }
                if (isLatestVisualRevision) {
                    bookState.chapters.forEach(chapter => {
                        chapter._lastSavedContent = String(chapter.content || '');
                    });
                }
            } else {
                if (statusIndicator) {
                    statusIndicator.innerHTML = '<i class="fa-solid fa-circle-exclamation text-xs mr-1"></i> Error';
                    statusIndicator.className = 'flex items-center gap-1 font-semibold text-rose-600';
                }
                const errorMessage = res?.data?.message || (typeof res?.data === 'string' ? res.data : 'No se pudo guardar el libro.');
                if (typeof showToast === 'function') {
                    showToast(errorMessage, 'fa-solid fa-circle-exclamation');
                }
            }
            saveCompleted = true;
            return !!res.success;
        } catch (err) {
            console.error('No se pudo guardar el libro.', err);
            if (statusIndicator) {
                const offline = typeof navigator !== 'undefined' && navigator.onLine === false;
                statusIndicator.innerHTML = offline
                    ? '<i class="fa-solid fa-wifi text-xs mr-1"></i> Sin conexión'
                    : '<i class="fa-solid fa-server text-xs mr-1"></i> Servidor ocupado';
                statusIndicator.className = 'flex items-center gap-1 font-semibold text-rose-600';
            }
            if (typeof showToast === 'function') {
                showToast('No se pudo confirmar el guardado. Tus cambios siguen en el editor.', 'fa-solid fa-circle-exclamation');
            }
            return false;
        } finally {
            saveCompleted = true;
            clearTimeout(saveWatchdog);
        }
    };

    const autosaveDelay = immediate ? 0 : 6000;
    return scheduleSerializedSave(executeSave, autosaveDelay);
}

window.saveStateToLocalStorage = saveStateToLocalStorage;
window.almadenBookSaveCoordinator = {
    schedule: scheduleSerializedSave,
    isSaving: () => !!activeSavePromise,
    hasPending: () => !!pendingSaveBatch
};
