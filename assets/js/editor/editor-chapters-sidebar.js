// assets/js/editor/editor-chapters-sidebar.js

let draggedChapterIndex = null;

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
        chapterEl.id = getChapterItemDomId(chapter.id);
        chapterEl.dataset.chapterId = String(chapter.id);
        chapterEl.className = `group flex items-stretch justify-between w-full cursor-pointer transition-colors pl-[9px] ${
            isActive
                ? 'bg-neutral-100 dark:bg-neutral-800'
                : 'bg-transparent hover:bg-[var(--bg-app)]'
        }`;
        chapterEl.setAttribute('onclick', `selectChapter('${chapter.id}')`);

        let chapterPagesStr = '';
        if (window.bookChapterLengths && window.bookChapterLengths[chapter.id] !== undefined) {
            chapterPagesStr = `<span class="text-[9px] text-neutral-400 font-medium whitespace-nowrap"><i class="fa-regular fa-file-lines mr-0.5"></i> ${window.bookChapterLengths[chapter.id]} p.</span>`;
        }

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
            setTimeout(() => {
                chapterEl.classList.add('opacity-30', 'scale-95');
                document.body.classList.add('is-dragging-chapter');
            }, 0);
        });

        chapterEl.addEventListener('dragend', () => {
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

        chapterEl.addEventListener('dragleave', () => {
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
                showToast('Capítulos reordenados', 'fa-solid fa-sort');
            }
        });

        listContainer.appendChild(chapterEl);
    });

    const chapterCountEl = document.getElementById('chapter-count');
    if (chapterCountEl) {
        chapterCountEl.textContent = bookState.chapters.length;
    }

    if (bookState && bookState.viewMode === 'ebook' && typeof refreshEbookPreview === 'function') {
        refreshEbookPreview(false);
    }
}

window.renderSidebar = renderSidebar;
