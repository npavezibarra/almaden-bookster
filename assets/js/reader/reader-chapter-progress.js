(function () {
    const ajaxUrl = window.almadenAjaxUrl || (window.location.origin + '/wp-admin/admin-ajax.php');
    let saving = false;

    function getBookId() {
        return window.bookData && Number(window.bookData.bookId) ? Number(window.bookData.bookId) : 0;
    }

    function canTrack() {
        return Boolean(window.bookData && window.bookData.canTrackChapterProgress);
    }

    function getState() {
        const fallback = {
            totalChapters: 0,
            readChapters: 0,
            remainingChapters: 0,
            completionPercent: 0,
            chapters: {}
        };
        return Object.assign({}, fallback, (window.bookData && window.bookData.chapterReadProgress) || {});
    }

    function isTrackableChapter(chapter) {
        return Boolean(chapter && chapter.id && chapter.is_toc !== '1' && chapter.is_credits !== '1' && !chapter.locked);
    }

    function isChapterRead(chapterId) {
        const entry = getState().chapters[String(chapterId)];
        return Boolean(entry && entry.read);
    }

    function currentChapter() {
        if (!window.bookData || !Array.isArray(window.bookData.chapters) || typeof currentChapterIndex !== 'number') {
            return null;
        }
        return window.bookData.chapters[currentChapterIndex] || null;
    }

    function syncState(progress) {
        if (!window.bookData || !progress) return;
        window.bookData.chapterReadProgress = progress;
        window.dispatchEvent(new CustomEvent('almaden:chapter-read-status-updated', { detail: progress }));
        updateToggleButton();
    }

    function updateToggleButton() {
        const button = document.getElementById('btn-toggle-chapter-read');
        const chapter = currentChapter();
        if (!button) return;

        const visible = canTrack() && isTrackableChapter(chapter);
        button.classList.toggle('hidden', !visible);
        if (!visible) return;

        const read = isChapterRead(chapter.id);
        const icon = button.querySelector('i');
        const label = button.querySelector('span');
        button.title = read ? 'Marcar capítulo como no leído' : 'Marcar capítulo como leído';
        if (icon) icon.className = read ? 'fa-solid fa-circle-check mr-2 text-emerald-600' : 'fa-regular fa-circle-check mr-2';
        if (label) label.textContent = read ? 'Leído' : 'Marcar como leído';
    }

    function setChapterState(chapterId, isRead) {
        if (!canTrack() || !chapterId || saving) return Promise.resolve();
        saving = true;
        updateToggleButton();
        const data = new FormData();
        data.append('action', 'almaden_set_chapter_read_state');
        data.append('book_id', String(getBookId()));
        data.append('chapter_id', String(chapterId));
        data.append('is_read', isRead ? '1' : '0');
        data.append('nonce', window.almadenReaderProgressNonce || '');

        return fetch(ajaxUrl, { method: 'POST', body: data })
            .then((response) => response.json())
            .then((response) => {
                if (response && response.success && response.data && response.data.progress) {
                    syncState(response.data.progress);
                }
            })
            .catch(() => {})
            .finally(() => {
                saving = false;
                updateToggleButton();
            });
    }

    function markCurrentChapterRead() {
        const chapter = currentChapter();
        if (!isTrackableChapter(chapter) || isChapterRead(chapter.id)) return;
        setChapterState(chapter.id, true);
    }

    function restartCurrentBookReading() {
        if (!canTrack()) return Promise.resolve();
        const message = '¿Seguro que deseas reiniciar esta lectura? Se conservará el historial y comenzará una nueva vuelta.';
        if (!window.confirm(message)) return Promise.resolve();

        const data = new FormData();
        data.append('action', 'almaden_restart_book_chapter_reading');
        data.append('book_id', String(getBookId()));
        data.append('nonce', window.almadenReaderProgressNonce || '');

        saving = true;
        updateToggleButton();

        return fetch(ajaxUrl, { method: 'POST', body: data })
            .then((response) => response.json())
            .then((response) => {
                if (response && response.success && response.data && response.data.progress) {
                    syncState(response.data.progress);
                }
            })
            .catch(() => {})
            .finally(() => {
                saving = false;
                updateToggleButton();
            });
    }

    function init() {
        const button = document.getElementById('btn-toggle-chapter-read');
        if (button) {
            button.addEventListener('click', () => {
                const chapter = currentChapter();
                if (isTrackableChapter(chapter)) {
                    setChapterState(chapter.id, !isChapterRead(chapter.id));
                }
            });
        }

        window.ALMADEN_READER_CHAPTER_PROGRESS = {
            isChapterRead,
            markCurrentChapterRead,
            restartCurrentBookReading,
            updateToggleButton,
            getState
        };
    }

    init();
})();
