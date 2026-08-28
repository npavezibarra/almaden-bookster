const almadenReaderHighlightsPageState = {
    activeChapterId: 'all',
    returnChapterIndex: -1,
    loading: false,
    loaded: false,
    error: ''
};

function getReaderHighlightsPageView() {
    return document.getElementById('almaden-view-highlights');
}

function isReaderHighlightsPageOpen() {
    const view = getReaderHighlightsPageView();
    return Boolean(view && !view.classList.contains('hidden'));
}

function getReaderHighlightsPageChapters() {
    if (!bookData || !Array.isArray(bookData.chapters)) {
        return [];
    }

    return bookData.chapters.filter(chapter => chapter.is_toc !== '1' && chapter.is_credits !== '1');
}

function getReaderHighlightsPageChapterIndexById(chapterId) {
    if (!bookData || !Array.isArray(bookData.chapters)) {
        return -1;
    }

    const normalizedChapterId = String(chapterId || '');
    return bookData.chapters.findIndex(chapter => String(chapter.id || '') === normalizedChapterId);
}

function getReaderHighlightsPageChapterById(chapterId) {
    const chapterIndex = getReaderHighlightsPageChapterIndexById(chapterId);
    if (chapterIndex < 0 || !bookData || !Array.isArray(bookData.chapters)) {
        return null;
    }

    return bookData.chapters[chapterIndex] || null;
}

function getReaderHighlightsPageItems() {
    const highlights = getSortedBookHighlights().slice();
    const activeChapterId = almadenReaderHighlightsPageState.activeChapterId;
    const filtered = activeChapterId === 'all'
        ? highlights
        : highlights.filter(highlight => String(highlight.chapter_id || 0) === activeChapterId);

    return filtered.sort((a, b) => {
        const dateA = Date.parse(String(a.created_at || '').replace(' ', 'T')) || 0;
        const dateB = Date.parse(String(b.created_at || '').replace(' ', 'T')) || 0;
        if (dateA !== dateB) return dateB - dateA;
        return (parseInt(b.id, 10) || 0) - (parseInt(a.id, 10) || 0);
    });
}

function createReaderHighlightsPageIconButton(iconClass, title, className) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = className;
    button.title = title;
    button.setAttribute('aria-label', title);

    const icon = document.createElement('i');
    icon.className = iconClass;
    button.appendChild(icon);
    return button;
}

function renderReaderHighlightsPageFilters() {
    const container = document.getElementById('reader-highlights-page-filters');
    if (!container) return;

    container.innerHTML = '';
    const highlights = getSortedBookHighlights();
    const counts = new Map();
    highlights.forEach(highlight => {
        const key = String(highlight.chapter_id || 0);
        counts.set(key, (counts.get(key) || 0) + 1);
    });

    const heading = document.createElement('div');
    heading.className = 'reader-highlights-page-filter-heading';
    heading.textContent = 'Capítulos';
    container.appendChild(heading);

    const filters = [
        { id: 'all', title: 'Todos', count: highlights.length },
        ...getReaderHighlightsPageChapters().map(chapter => ({
            id: String(chapter.id),
            title: chapter.title || 'Capítulo',
            count: counts.get(String(chapter.id)) || 0
        }))
    ];

    filters.forEach(filter => {
        const row = document.createElement('div');
        row.className = 'reader-highlights-page-filter-row';
        row.dataset.chapterId = filter.id;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'reader-highlights-page-filter';
        button.dataset.chapterId = filter.id;
        button.setAttribute('aria-pressed', String(almadenReaderHighlightsPageState.activeChapterId === filter.id));
        if (almadenReaderHighlightsPageState.activeChapterId === filter.id) {
            button.classList.add('is-active');
        }
        if (!filter.count && filter.id !== 'all') {
            button.classList.add('is-empty');
        }

        const label = document.createElement('span');
        label.textContent = filter.title;
        const count = document.createElement('span');
        count.className = 'reader-highlights-page-filter-count';
        count.textContent = String(filter.count);

        button.appendChild(label);
        button.appendChild(count);
        button.addEventListener('click', () => {
            almadenReaderHighlightsPageState.activeChapterId = filter.id;
            renderReaderHighlightsPage();
            const scrollRoot = document.getElementById('reader-highlights-page-scroll');
            if (scrollRoot) scrollRoot.scrollTop = 0;
        });

        row.appendChild(button);

        container.appendChild(row);
    });
}

function renderReaderHighlightsPageToolbar() {
    const title = document.getElementById('reader-highlights-page-toolbar-title');
    const button = document.getElementById('reader-highlights-page-toolbar-read');
    if (!title || !button) return;

    const activeChapterId = almadenReaderHighlightsPageState.activeChapterId;
    const chapter = activeChapterId === 'all' ? null : getReaderHighlightsPageChapterById(activeChapterId);

    if (!chapter) {
        title.textContent = 'Todos los capítulos';
        button.hidden = true;
        button.dataset.chapterId = '';
        return;
    }

    title.textContent = chapter.title || 'Capítulo';
    button.hidden = false;
    button.dataset.chapterId = String(chapter.id || '');
    button.onclick = () => {
        const chapterIndex = getReaderHighlightsPageChapterIndexById(chapter.id);
        if (chapterIndex >= 0 && typeof showChapterView === 'function') {
            showChapterView(chapterIndex);
        }
    };
}

function openReaderHighlightFromPage(highlight) {
    const chapterIndex = getReaderHighlightChapterIndex(highlight);
    if (chapterIndex < 0 || typeof showChapterView !== 'function') return;

    almadenReaderHighlightState.pendingFocusHighlightId = String(highlight.id || '');
    showChapterView(chapterIndex);
}

function createReaderHighlightsPageCard(highlight) {
    const highlightId = getReaderHighlightKey(highlight.id);
    const comments = getReaderHighlightComments(highlightId);
    const card = document.createElement('article');
    card.className = 'reader-highlights-feed-card';
    card.dataset.highlightId = highlightId;

    const meta = document.createElement('header');
    meta.className = 'reader-highlights-feed-meta';

    const chapter = document.createElement('span');
    chapter.className = 'reader-highlights-feed-chapter';
    chapter.textContent = getReaderHighlightChapterTitle(highlight);

    const date = document.createElement('time');
    date.className = 'reader-highlights-feed-date';
    date.textContent = formatReaderDate(highlight.created_at);
    if (highlight.created_at) date.dateTime = String(highlight.created_at).replace(' ', 'T');

    meta.appendChild(chapter);
    meta.appendChild(date);

    const quoteButton = document.createElement('button');
    quoteButton.type = 'button';
    quoteButton.className = 'reader-highlights-feed-quote-button';
    quoteButton.title = 'Abrir en el capítulo';
    quoteButton.addEventListener('click', () => openReaderHighlightFromPage(highlight));

    const quote = document.createElement('mark');
    quote.className = 'reader-highlights-feed-quote';
    quote.dataset.almadenProtectedExcerpt = 'highlight';
    quote.textContent = String(highlight.selected_text || '').replace(/\s+/g, ' ').trim();
    quoteButton.appendChild(quote);

    const actionRow = document.createElement('div');
    actionRow.className = 'reader-highlights-feed-actions';

    const commentButton = createReaderHighlightsPageIconButton(
        'fa-solid fa-comment-dots',
        'Agregar comentario',
        'reader-highlights-feed-action'
    );
    commentButton.addEventListener('click', () => {
        openReaderHighlightCommentComposer(highlight);
    });

    const deleteButton = createReaderHighlightsPageIconButton(
        'fa-solid fa-trash-can',
        'Borrar highlight',
        'reader-highlights-feed-action reader-highlights-feed-delete'
    );
    deleteButton.addEventListener('click', () => deleteReaderHighlight(highlight));

    actionRow.appendChild(commentButton);
    actionRow.appendChild(deleteButton);

    const quoteSection = document.createElement('div');
    quoteSection.className = 'reader-highlights-feed-quote-section';
    quoteSection.appendChild(quoteButton);
    quoteSection.appendChild(actionRow);

    const commentsSection = document.createElement('section');
    commentsSection.className = 'reader-highlights-feed-comments';
    commentsSection.setAttribute('aria-label', 'Comentarios');

    const commentsHeading = document.createElement('div');
    commentsHeading.className = 'reader-highlights-feed-comments-heading';
    commentsHeading.textContent = comments.length === 1 ? '1 comentario' : `${comments.length} comentarios`;
    commentsSection.appendChild(commentsHeading);

    const commentsBody = document.createElement('div');
    commentsBody.className = 'reader-highlights-feed-comments-body';
    renderReaderHighlightCommentsSection(highlight, commentsBody);

    if (!comments.length && almadenReaderHighlightState.commentComposerHighlightId !== highlightId) {
        const empty = document.createElement('p');
        empty.className = 'reader-highlights-feed-comments-empty';
        empty.textContent = 'Todavía no hay comentarios para este highlight.';
        commentsBody.appendChild(empty);
    }

    commentsSection.appendChild(commentsBody);
    card.appendChild(meta);
    card.appendChild(quoteSection);
    card.appendChild(commentsSection);
    return card;
}

function renderReaderHighlightsPage() {
    if (!isReaderHighlightsPageOpen()) return;

    renderReaderHighlightsPageFilters();
    renderReaderHighlightsPageToolbar();
    const feed = document.getElementById('reader-highlights-page-feed');
    if (!feed) return;
    feed.innerHTML = '';

    if (almadenReaderHighlightsPageState.loading) {
        const loading = document.createElement('div');
        loading.className = 'reader-highlights-page-status';
        loading.textContent = 'Cargando highlights y comentarios…';
        feed.appendChild(loading);
        return;
    }

    if (almadenReaderHighlightsPageState.error) {
        const error = document.createElement('div');
        error.className = 'reader-highlights-page-status is-error';
        error.textContent = almadenReaderHighlightsPageState.error;
        feed.appendChild(error);
        return;
    }

    const items = getReaderHighlightsPageItems();
    if (!items.length) {
        const empty = document.createElement('div');
        empty.className = 'reader-highlights-page-status';
        empty.textContent = almadenReaderHighlightsPageState.activeChapterId === 'all'
            ? 'Todavía no tienes highlights en este libro.'
            : 'No tienes highlights en este capítulo.';
        feed.appendChild(empty);
        return;
    }

    items.forEach(highlight => feed.appendChild(createReaderHighlightsPageCard(highlight)));
}

async function loadReaderHighlightsPageFeed() {
    const bookId = getReaderBookId();
    if (!bookId || almadenReaderHighlightsPageState.loading) return;

    almadenReaderHighlightsPageState.loading = true;
    almadenReaderHighlightsPageState.error = '';
    renderReaderHighlightsPage();

    try {
        const params = new URLSearchParams();
        params.set('action', 'almaden_list_book_highlights_feed');
        params.set('nonce', typeof almadenReaderHighlightNonce !== 'undefined' ? almadenReaderHighlightNonce : '');
        params.set('book_id', String(bookId));

        const response = await fetch(`${getReaderAjaxUrl()}?${params.toString()}`, {
            method: 'GET',
            credentials: 'same-origin'
        });
        const payload = await response.json();
        if (!payload || !payload.success || !payload.data || !Array.isArray(payload.data.highlights)) {
            throw new Error((payload && payload.data) || 'No se pudo cargar el feed.');
        }

        bookData.highlights = payload.data.highlights;
        const commentsByHighlight = payload.data.comments_by_highlight || {};
        payload.data.highlights.forEach(highlight => {
            const key = getReaderHighlightKey(highlight.id);
            almadenReaderHighlightState.commentsByHighlightId[key] = Array.isArray(commentsByHighlight[key])
                ? commentsByHighlight[key]
                : [];
        });
        almadenReaderHighlightsPageState.loaded = true;
    } catch (error) {
        console.error(error);
        almadenReaderHighlightsPageState.error = 'No se pudieron cargar tus highlights. Intenta nuevamente.';
    } finally {
        almadenReaderHighlightsPageState.loading = false;
        renderReaderHighlightsPage();
    }
}

function setReaderHighlightsPageChrome(isActive) {
    const html = document.documentElement;
    const body = document.body;
    if (!html || !body) return;

    if (isActive) {
        html.style.setProperty('background-color', '#ffffff', 'important');
        html.style.setProperty('background-image', 'none', 'important');
        body.style.setProperty('background-color', '#ffffff', 'important');
        body.style.setProperty('background-image', 'none', 'important');
        return;
    }

    html.style.removeProperty('background-color');
    html.style.removeProperty('background-image');
    body.style.removeProperty('background-color');
    body.style.removeProperty('background-image');
}

function showReaderHighlightsPage() {
    if (typeof currentChapterIndex === 'number' && currentChapterIndex >= 0) {
        almadenReaderHighlightsPageState.returnChapterIndex = currentChapterIndex;
    }

    setReaderHighlightsPageChrome(true);
    closeReaderHighlightsPanel();
    const indexView = document.getElementById('almaden-view-index');
    const chapterView = document.getElementById('almaden-view-chapter');
    const highlightsView = getReaderHighlightsPageView();
    if (indexView) indexView.classList.add('hidden');
    if (chapterView) chapterView.classList.add('hidden');
    if (highlightsView) highlightsView.classList.remove('hidden');

    renderReaderHighlightsPage();
    loadReaderHighlightsPageFeed();
}

function closeReaderHighlightsPage() {
    setReaderHighlightsPageChrome(false);
    const returnIndex = almadenReaderHighlightsPageState.returnChapterIndex;
    if (returnIndex >= 0 && typeof showChapterView === 'function') {
        showChapterView(returnIndex);
        return;
    }
    if (typeof showIndexView === 'function') showIndexView();
}

document.addEventListener('DOMContentLoaded', () => {
    const expandButton = document.getElementById('btn-expand-reader-highlights');
    const closeButton = document.getElementById('btn-close-highlights-page');
    const indexButton = document.getElementById('btn-highlights-page-index');

    if (expandButton) expandButton.addEventListener('click', showReaderHighlightsPage);
    if (closeButton) closeButton.addEventListener('click', closeReaderHighlightsPage);
    if (indexButton) indexButton.addEventListener('click', showIndexView);
});
