(function () {
    const ajaxUrl = window.almadenAjaxUrl || (window.location.origin + '/wp-admin/admin-ajax.php');
    let activeTab = 'readings';

    function getBookId() {
        return window.bookData && Number(window.bookData.bookId) ? Number(window.bookData.bookId) : 0;
    }

    function getNonce() {
        return window.almadenReaderProgressNonce || '';
    }

    function getRuntimeBookData() {
        return window.bookData && typeof window.bookData === 'object' ? window.bookData : {};
    }

    function getReadingState() {
        const fallback = {
            totalChapters: 0,
            readChapters: 0,
            remainingChapters: 0,
            completionPercent: 0,
            activeRound: 1,
            readChapterIds: [],
            chapters: {}
        };
        return Object.assign({}, fallback, getRuntimeBookData().chapterReadProgress || {});
    }

    function getQuizState() {
        const fallback = {
            totalQuizzes: 0,
            completedQuizzes: 0,
            remainingQuizzes: 0,
            allQuizzesCompleted: false,
            resetAvailable: false,
            quizzes: []
        };
        return Object.assign({}, fallback, getRuntimeBookData().quizProgress || {});
    }

    function getReadableChapters() {
        const cfg = getRuntimeBookData();
        return Array.isArray(cfg.chapters)
            ? cfg.chapters
                .map((chapter, index) => ({ chapter, index }))
                .filter(({ chapter }) => chapter && chapter.is_toc !== '1' && chapter.is_credits !== '1')
            : [];
    }

    function getReadChapterSet() {
        const state = getReadingState();
        return new Set((Array.isArray(state.readChapterIds) ? state.readChapterIds : []).map((id) => Number(id)));
    }

    function getResumeChapterEntry() {
        const chapters = getReadableChapters();
        const readSet = getReadChapterSet();
        let firstUnread = null;
        let lastTrackable = null;

        chapters.forEach((entry) => {
            const chapter = entry.chapter || {};
            const chapterId = Number(chapter.id || 0);
            if (!chapterId || chapter.locked) {
                return;
            }

            lastTrackable = entry;
            if (!firstUnread && !readSet.has(chapterId)) {
                firstUnread = entry;
            }
        });

        return firstUnread || lastTrackable || null;
    }

    function getLastReadChapterEntry() {
        const chapters = getReadableChapters();
        const readSet = getReadChapterSet();
        let lastRead = null;

        chapters.forEach((entry) => {
            const chapter = entry.chapter || {};
            const chapterId = Number(chapter.id || 0);
            if (!chapterId || chapter.locked || !readSet.has(chapterId)) {
                return;
            }
            lastRead = entry;
        });

        return lastRead;
    }

    function getNextChapterEntry() {
        const chapters = getReadableChapters();
        const readSet = getReadChapterSet();

        for (const entry of chapters) {
            const chapter = entry.chapter || {};
            const chapterId = Number(chapter.id || 0);
            if (!chapterId || chapter.locked) {
                continue;
            }
            if (!readSet.has(chapterId)) {
                return entry;
            }
        }

        return chapters.length ? chapters[chapters.length - 1] : null;
    }

    function getResumeChapterIndex() {
        const entry = getResumeChapterEntry();
        return entry ? Number(entry.index || 0) : -1;
    }

    function getResumeChapterTitle() {
        const entry = getResumeChapterEntry();
        if (!entry || !entry.chapter) {
            return '';
        }
        return String(entry.chapter.title || 'Capítulo');
    }

    function getReadingChapterItems() {
        const state = getReadingState();
        const readSet = new Set((Array.isArray(state.readChapterIds) ? state.readChapterIds : []).map((id) => Number(id)));

        return getReadableChapters().map((entry) => {
            const chapter = entry.chapter || {};
            const chapterId = Number(chapter.id || 0);
            const isRead = chapterId > 0 && readSet.has(chapterId);
            return {
                index: Number(entry.index || 0),
                chapter,
                number: chapter.page || (Number(entry.index || 0) + 1),
                isRead
            };
        });
    }

    function ensureUI() {
        if (document.getElementById('almaden-progress-panel')) {
            return;
        }

        const style = document.createElement('style');
        style.textContent = `
            @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=Inter:wght@300;400;500&display=swap');

            #almaden-progress-panel, #almaden-progress-backdrop { display: none; }
            #almaden-progress-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(17, 24, 39, 0.4);
                backdrop-filter: blur(6px);
                z-index: 60;
            }
            #almaden-progress-panel {
                position: fixed;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
                width: min(92vw, 42rem);
                max-height: 84vh;
                overflow: hidden;
                z-index: 61;
                background: #fff;
                border: 1px solid #f4f4f5;
                border-radius: 1.5rem;
                box-shadow: 0 20px 60px rgba(0,0,0,0.16);
                flex-direction: column;
                font-family: 'Outfit', sans-serif;
            }
            #almaden-progress-panel.almaden-open, #almaden-progress-backdrop.almaden-open { display: flex; }
            #almaden-progress-backdrop.almaden-open { display: block; }
            .almaden-progress-head {
                padding: 1.15rem 1.25rem .8rem;
                border-bottom: 1px solid #f4f4f5;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }
            .almaden-progress-title {
                margin: 0;
                font-size: 1.1rem;
                line-height: 1.15;
                color: #111827;
                font-weight: 400;
                letter-spacing: -0.03em;
            }
            .almaden-progress-kicker {
                margin: 0 0 .3rem;
                font-size: 10px;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: .2em;
                color: #a1a1aa;
            }
            .almaden-progress-close {
                width: 1.9rem;
                height: 1.9rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 0;
                border-radius: 999px;
                border: 1px solid transparent;
                background: #fff;
                color: #a1a1aa;
                font-weight: 500;
                font-size: .9rem;
                cursor: pointer;
                transition: all .18s ease;
                flex: 0 0 auto;
            }
            .almaden-progress-close:hover {
                color: #111827;
                background: #f4f4f5;
            }
            .almaden-progress-close:focus,
            .almaden-progress-close:focus-visible,
            .almaden-progress-tab:focus,
            .almaden-progress-tab:focus-visible,
            .almaden-progress-btn:focus,
            .almaden-progress-btn:focus-visible {
                outline: none;
                box-shadow: none;
            }
            .almaden-progress-tabs {
                display: flex;
                gap: .4rem;
                padding: .9rem 1.25rem .35rem;
            }
            .almaden-progress-tab {
                flex: 1 1 0;
                border: 1px solid transparent;
                border-radius: .65rem;
                background: transparent;
                color: #71717a;
                font-weight: 500;
                font-size: .72rem;
                letter-spacing: .08em;
                text-transform: none;
                padding: .55rem .9rem;
                cursor: pointer;
                transition: all .18s ease;
            }
            .almaden-progress-tab.is-active {
                background: #111827;
                color: #fff;
                border-color: #111827;
                box-shadow: 0 1px 2px rgba(0,0,0,.06);
            }
            .almaden-progress-tab:not(.is-active):hover {
                background: #fafafa;
                color: #111827;
            }
            .almaden-progress-body {
                padding: .85rem 1.25rem 1.1rem;
                overflow: auto;
                font-family: 'Outfit', sans-serif;
            }
            .almaden-progress-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0,1fr));
                gap: .6rem;
            }
            .almaden-progress-stat {
                border-radius: .95rem;
                background: #f1f1f1;
                border: 1px solid rgba(228, 228, 231, .9);
                color: #111827;
                padding: .85rem .9rem;
                min-height: 4.3rem;
            }
            .almaden-progress-stat small {
                display: block;
                color: #a1a1aa;
                text-transform: uppercase;
                letter-spacing: .14em;
                font-size: 9px;
                font-weight: 500;
                margin-bottom: .25rem;
            }
            .almaden-progress-stat strong {
                font-size: 1.1rem;
                line-height: 1;
                font-weight: 600;
            }
            .almaden-progress-card {
                border: 1px solid rgba(228, 228, 231, .9);
                border-radius: .95rem;
                padding: .95rem 1rem;
                background: #f1f1f1;
                box-shadow: 0 1px 0 rgba(15,23,42,.02);
                transition: border-color .18s ease, box-shadow .18s ease;
            }
            .almaden-progress-card:hover {
                border-color: #d4d4d8;
            }
            .almaden-progress-current-label,
            .almaden-progress-next-label {
                font-size: 10px;
                font-weight: 600;
                letter-spacing: .16em;
                text-transform: uppercase;
                color: #a1a1aa;
                margin: 0 0 .3rem;
            }
            .almaden-progress-current-line {
                margin: 0;
                font-size: .84rem;
                line-height: 1.35;
                color: #71717a;
                font-family: 'Inter', sans-serif;
            }
            .almaden-progress-current-line strong {
                font-weight: 500;
                color: #374151;
            }
            .almaden-progress-next-label-inline {
                margin: .7rem 0 .2rem;
                font-size: 10px;
                font-weight: 600;
                letter-spacing: .16em;
                text-transform: uppercase;
                color: #a1a1aa;
            }
            .almaden-progress-current-title {
                margin: 0;
                font-size: 24px;
                line-height: 1.25;
                font-weight: 600;
                color: #111827;
            }
            .almaden-progress-next-title {
                margin: 0;
                font-size: .92rem;
                line-height: 1.45;
                font-weight: 400;
                color: #6b7280;
            }
            .almaden-progress-note {
                color: #71717a;
                font-size: .8rem;
                line-height: 1.45;
                font-family: 'Inter', sans-serif;
            }
            .almaden-progress-section {
                margin-top: 1rem;
            }
            .almaden-progress-section-title {
                margin: 0 0 .75rem;
                font-size: 10px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .12em;
                color: #a1a1aa;
                padding-left: .25rem;
                font-family: 'Outfit', sans-serif;
            }
            .almaden-progress-resume {
                display: grid;
                grid-template-columns: 1fr auto;
                gap: 1rem;
                align-items: center;
            }
            .almaden-progress-resume h4,
            .almaden-progress-quiz h4 {
                margin: 0;
                color: #111827;
                font-size: .94rem;
                line-height: 1.35;
                font-weight: 500;
            }
            .almaden-progress-resume p,
            .almaden-progress-quiz p {
                margin: .25rem 0 0;
                color: #64748b;
                font-size: .88rem;
                line-height: 1.5;
            }
            .almaden-progress-quiz__meta-right {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: .55rem;
                flex: 0 0 auto;
                margin-left: auto;
            }
            .almaden-progress-quiz__score {
                color: #111827;
                font-size: .88rem;
                font-weight: 500;
                line-height: 1.2;
                text-align: right;
            }
            .almaden-progress-quiz__score strong {
                font-weight: 600;
            }
            .almaden-progress-resume-actions {
                display: flex;
                justify-content: flex-start;
                margin-top: 1rem;
            }
            .almaden-progress-btn {
                border-radius: .65rem;
                padding: .55rem .85rem;
                font-weight: 500;
                font-size: .75rem;
                letter-spacing: .08em;
                text-transform: none;
                border: 1px solid transparent;
                cursor: pointer;
                transition: all .18s ease;
            }
            .almaden-progress-btn.primary {
                background: #111827;
                color: #fff;
                box-shadow: 0 1px 2px rgba(0,0,0,.06);
            }
            .almaden-progress-btn.primary:hover {
                background: #000;
            }
            .almaden-progress-btn.secondary {
                background: #fff;
                border-color: #e5e7eb;
                color: #374151;
            }
            .almaden-progress-btn.secondary:hover {
                background: #f8fafc;
            }
            .almaden-progress-btn:disabled {
                opacity: .45;
                cursor: not-allowed;
            }
            .almaden-progress-list {
                display: grid;
                gap: .6rem;
            }
            .almaden-progress-chip {
                display: inline-flex;
                align-items: center;
                gap: .35rem;
                border-radius: 999px;
                padding: .24rem .5rem;
                font-size: 10px;
                font-weight: 500;
                text-transform: none;
                letter-spacing: 0;
                white-space: nowrap;
                background: #f4f4f5;
                color: #52525b;
                font-family: 'Inter', sans-serif;
            }
            .almaden-progress-chip.is-pass {
                background: #dcfce7;
                color: #166534;
            }
            .almaden-progress-chip.is-wait {
                background: #f4f4f5;
                color: #52525b;
            }
            .almaden-progress-quiz {
                border: 1px solid rgba(228, 228, 231, .9);
                border-radius: .95rem;
                background: #f1f1f1;
                padding: .85rem 1rem;
                transition: border-color .18s ease;
            }
            .almaden-progress-quiz:hover {
                border-color: #d4d4d8;
            }
            .almaden-progress-quiz__meta {
                display: flex;
                justify-content: space-between;
                gap: 1rem;
                align-items: flex-start;
            }
            .almaden-progress-quiz__subtitle {
                margin: .3rem 0 0;
                font-size: .8rem;
                color: #52525b;
                font-weight: 500;
            }
            .almaden-progress-quiz__scores {
                margin-top: .55rem;
                color: #71717a;
                font-size: .8rem;
                line-height: 1.45;
            }
            .almaden-progress-quiz__actions {
                display: flex;
                justify-content: flex-end;
                margin-top: .7rem;
            }
            .almaden-progress-empty {
                border: 1px dashed #e4e4e7;
                border-radius: .95rem;
                padding: .85rem .95rem;
                color: #71717a;
                background: #f1f1f1;
                font-size: .84rem;
            }
            @media (max-width: 720px) {
                #almaden-progress-panel {
                    width: min(96vw, 42rem);
                    top: 50%;
                    left: 50%;
                    right: auto;
                    transform: translate(-50%, -50%);
                    max-height: calc(100vh - 2rem);
                }
                .almaden-progress-grid {
                    grid-template-columns: 1fr;
                }
                .almaden-progress-resume {
                    grid-template-columns: 1fr;
                }
                .almaden-progress-quiz__meta {
                    flex-direction: column;
                    gap: .45rem;
                }
            }
        `;
        document.head.appendChild(style);

        const backdrop = document.createElement('div');
        backdrop.id = 'almaden-progress-backdrop';
        backdrop.addEventListener('click', closePanel);

        const panel = document.createElement('aside');
        panel.id = 'almaden-progress-panel';
        panel.innerHTML = `
            <div class="almaden-progress-head">
                <div>
                    <p class="almaden-progress-kicker">Resultados y progreso</p>
                    <h3 class="almaden-progress-title">Mi avance del ebook</h3>
                </div>
                <button type="button" id="almaden-progress-close" class="almaden-progress-close" aria-label="Cerrar">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="almaden-progress-tabs" role="tablist" aria-label="Progreso del libro">
                <button type="button" class="almaden-progress-tab" data-progress-tab="readings">Lecturas</button>
                <button type="button" class="almaden-progress-tab" data-progress-tab="quizzes">Quizzes</button>
            </div>
            <div class="almaden-progress-body" id="almaden-progress-body"></div>
        `;

        document.body.appendChild(backdrop);
        document.body.appendChild(panel);
        document.getElementById('almaden-progress-close').addEventListener('click', closePanel);
        panel.querySelectorAll('[data-progress-tab]').forEach((button) => {
            button.addEventListener('click', () => {
                activeTab = button.getAttribute('data-progress-tab') || 'readings';
                render();
            });
        });
    }

    function setActiveTabStyles() {
        const panel = document.getElementById('almaden-progress-panel');
        if (!panel) {
            return;
        }

        panel.querySelectorAll('[data-progress-tab]').forEach((button) => {
            const isActive = button.getAttribute('data-progress-tab') === activeTab;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    function getResumeChapterHtml() {
        const entry = getResumeChapterEntry();
        if (!entry || !entry.chapter) {
            return {
                title: 'No quedan capítulos pendientes',
                subtitle: 'Puedes revisar nuevamente el libro completo o cambiar de pestaña.',
                disabled: true
            };
        }

        const chapter = entry.chapter;
        const state = getReadingState();
        const readSet = new Set((Array.isArray(state.readChapterIds) ? state.readChapterIds : []).map((id) => Number(id)));
        const chapterId = Number(chapter.id || 0);
        const isRead = chapterId > 0 && readSet.has(chapterId);

        return {
            title: isRead ? 'Revisar último capítulo leído' : 'Continuar lectura',
            subtitle: `${isRead ? 'Último capítulo disponible: ' : 'Sigue con: '}${chapter.title || 'Capítulo'}`,
            disabled: false,
            index: Number(entry.index || 0)
        };
    }

    function renderReadings() {
        const state = getReadingState();
        const resume = getResumeChapterHtml();
        const lastReadEntry = getLastReadChapterEntry();
        const nextChapterEntry = getNextChapterEntry();
        const lastReadTitle = lastReadEntry && lastReadEntry.chapter ? String(lastReadEntry.chapter.title || 'Capítulo') : 'Aún no has leído ningún capítulo';
        const nextChapterTitle = nextChapterEntry && nextChapterEntry.chapter ? String(nextChapterEntry.chapter.title || 'Capítulo') : 'No hay capítulos pendientes';
        const body = document.getElementById('almaden-progress-body');
        if (!body) {
            return;
        }

        body.innerHTML = `
            <div class="almaden-progress-grid">
                <div class="almaden-progress-stat">
                    <small>Completados</small>
                    <strong>${Number(state.readChapters || 0)}/${Number(state.totalChapters || 0)}</strong>
                </div>
                <div class="almaden-progress-stat">
                    <small>Avance</small>
                    <strong>${Number(state.completionPercent || 0)}%</strong>
                </div>
                <div class="almaden-progress-stat">
                    <small>Restantes</small>
                    <strong>${Number(state.remainingChapters || 0)}</strong>
                </div>
            </div>
            <div class="almaden-progress-section">
                <div class="almaden-progress-card">
                    <p class="almaden-progress-current-line">Último capítulo: <strong>${escapeHtml(lastReadTitle)}</strong></p>
                    <p class="almaden-progress-next-label-inline">Próximo capítulo:</p>
                    <h4 class="almaden-progress-current-title">
                        ${Number(state.totalChapters || 0) > 0 ? escapeHtml(nextChapterTitle) : 'Todavía no hay capítulos disponibles.'}
                    </h4>
                    <div class="almaden-progress-resume-actions">
                        <button type="button" class="almaden-progress-btn primary" id="almaden-progress-read-chapter" ${resume.disabled ? 'disabled' : ''}>
                            Continuar
                        </button>
                    </div>
                </div>
            </div>
        `;

        const readBtn = document.getElementById('almaden-progress-read-chapter');
        if (readBtn) {
            readBtn.addEventListener('click', () => {
                const index = Number(resume.index || getResumeChapterIndex());
                if (index < 0 || typeof window.showChapterView !== 'function') {
                    return;
                }
                closePanel();
                window.showChapterView(index);
            });
        }
    }

    function renderQuizzes() {
        const state = getQuizState();
        const quizzes = Array.isArray(state.quizzes) ? state.quizzes : [];
        const body = document.getElementById('almaden-progress-body');
        if (!body) {
            return;
        }

        body.innerHTML = `
            <div class="almaden-progress-grid">
                <div class="almaden-progress-stat">
                    <small>Quizzes</small>
                    <strong>${Number(state.totalQuizzes || 0)}</strong>
                </div>
                <div class="almaden-progress-stat">
                    <small>Aprobados</small>
                    <strong>${Number(state.completedQuizzes || 0)}</strong>
                </div>
                <div class="almaden-progress-stat">
                    <small>Restantes</small>
                    <strong>${Number(state.remainingQuizzes || 0)}</strong>
                </div>
            </div>
            <div class="almaden-progress-section">
                <p class="almaden-progress-section-title">Quizzes del libro</p>
                <div class="almaden-progress-list">
                    ${
                        quizzes.length
                            ? quizzes.map((quiz) => {
                                const attempts = Number(quiz.attempts || 0);
                                const bestScore = Number(quiz.best_score || 0);
                                const latestScore = Number(quiz.latest_score || 0);
                                const quizTitle = String(quiz.chapter_title || 'Quiz');
                                const quizId = Number(quiz.quiz_id || 0);
                                const hasAttempts = attempts > 0;
                                const scoreValue = bestScore > 0 ? bestScore : latestScore;
                                const scoreText = hasAttempts ? `Correctas <strong>${scoreValue}%</strong>` : '';
                                const actionLabel = hasAttempts ? 'Reiniciar quiz' : 'Tomar quiz';
                                const actionType = hasAttempts ? 'restart' : 'take';
                                const actionButton = `<button type="button" class="almaden-progress-btn secondary" data-progress-quiz-id="${quizId}" data-progress-quiz-title="${escapeHtml(quizTitle)}" data-progress-quiz-action="${actionType}">${actionLabel}</button>`;
                                return `
                                    <article class="almaden-progress-quiz">
                                        <div class="almaden-progress-quiz__meta">
                                            <div>
                                                <h4>${escapeHtml(quizTitle)}</h4>
                                                <p class="almaden-progress-quiz__subtitle">Quiz ${quizId}</p>
                                                ${hasAttempts ? `<p class="almaden-progress-quiz__score" style="margin-top:.4rem;">${scoreText}</p>` : ''}
                                            </div>
                                            <div class="almaden-progress-quiz__meta-right">
                                                ${actionButton}
                                            </div>
                                        </div>
                                        ${!hasAttempts ? '<div class="almaden-progress-quiz__scores" style="margin-top:.55rem;">Sin intentos todavía.</div>' : ''}
                                    </article>
                                `;
                            }).join('')
                            : '<div class="almaden-progress-empty">Este libro todavía no tiene quizzes vinculados.</div>'
                    }
                </div>
            </div>
        `;

        body.querySelectorAll('[data-progress-quiz-id]').forEach((button) => {
            button.addEventListener('click', () => {
                const quizId = Number(button.getAttribute('data-progress-quiz-id') || 0);
                const quizTitle = button.getAttribute('data-progress-quiz-title') || 'Quiz';
                const action = button.getAttribute('data-progress-quiz-action') || 'take';
                if (!quizId || !window.ALMADEN_READER_QUIZZES || typeof window.ALMADEN_READER_QUIZZES.openQuiz !== 'function') {
                    return;
                }
                if (action === 'restart') {
                    const data = new FormData();
                    data.append('action', 'almaden_reset_quiz_progress');
                    data.append('book_id', String(getBookId()));
                    data.append('quiz_id', String(quizId));
                    data.append('nonce', getNonce());

                    fetch(ajaxUrl, { method: 'POST', body: data })
                        .then((res) => res.json())
                        .then((res) => {
                            if (!res || !res.success) {
                                return;
                            }
                            if (window.ALMADEN_READER_QUIZZES && typeof window.ALMADEN_READER_QUIZZES.removeApprovedQuiz === 'function') {
                                window.ALMADEN_READER_QUIZZES.removeApprovedQuiz(quizId);
                            }
                            if (res.data && res.data.progress) {
                                syncFromState(res.data.progress);
                            } else {
                                fetchProgress();
                            }
                            window.ALMADEN_READER_QUIZZES.openQuiz(quizId, quizTitle, () => {
                                fetchProgress();
                            });
                        })
                        .catch(() => {});
                    return;
                }

                window.ALMADEN_READER_QUIZZES.openQuiz(quizId, quizTitle, () => {
                    fetchProgress();
                });
            });
        });
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function render() {
        ensureUI();
        setActiveTabStyles();
        if (activeTab === 'quizzes') {
            renderQuizzes();
            return;
        }
        renderReadings();
    }

    function syncFromState(nextState) {
        if (window.bookData && typeof window.bookData === 'object' && nextState) {
            window.bookData.quizProgress = nextState;
        }
        if (document.getElementById('almaden-progress-panel') && document.getElementById('almaden-progress-panel').classList.contains('almaden-open')) {
            render();
        }
    }

    function openPanel() {
        ensureUI();
        document.getElementById('almaden-progress-panel').classList.add('almaden-open');
        document.getElementById('almaden-progress-backdrop').classList.add('almaden-open');
        render();
        fetchProgress();
    }

    function closePanel() {
        const panel = document.getElementById('almaden-progress-panel');
        const backdrop = document.getElementById('almaden-progress-backdrop');
        if (panel) {
            panel.classList.remove('almaden-open');
        }
        if (backdrop) {
            backdrop.classList.remove('almaden-open');
        }
    }

    function fetchProgress() {
        const bookId = getBookId();
        if (!bookId) {
            return;
        }

        const data = new FormData();
        data.append('action', 'almaden_get_book_progress');
        data.append('book_id', String(bookId));
        data.append('nonce', getNonce());

        fetch(ajaxUrl, { method: 'POST', body: data })
            .then((res) => res.json())
            .then((res) => {
                if (res && res.success && res.data && res.data.progress) {
                    syncFromState(res.data.progress);
                }
            })
            .catch(() => {});
    }

    function injectButton() {
        const anchor = document.getElementById('btn-reader-highlights');
        if (!anchor || document.getElementById('btn-reader-progress')) {
            return;
        }

        const button = document.createElement('button');
        button.id = 'btn-reader-progress';
        button.type = 'button';
        button.className = 'p-2 text-gray-800 hover:bg-gray-100 rounded text-sm w-9 h-9 flex items-center justify-center transition-colors mr-2';
        button.title = 'Resultados y progreso';
        button.setAttribute('aria-label', 'Resultados y progreso');
        button.innerHTML = '<i class="fa-solid fa-chart-line"></i>';
        button.addEventListener('click', openPanel);
        anchor.insertAdjacentElement('afterend', button);
    }

    function init() {
        injectButton();
        window.ALMADEN_READER_PROGRESS = {
            open: openPanel,
            close: closePanel,
            refresh: fetchProgress,
            syncFromState: syncFromState,
            getState: getQuizState,
            getReadingState: getReadingState,
            setTab: (tab) => {
                activeTab = tab === 'quizzes' ? 'quizzes' : 'readings';
                render();
            }
        };

        window.addEventListener('almaden:chapter-read-status-updated', () => {
            if (document.getElementById('almaden-progress-panel') && document.getElementById('almaden-progress-panel').classList.contains('almaden-open')) {
                render();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
