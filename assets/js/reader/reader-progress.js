(function () {
    const ajaxUrl = window.almadenAjaxUrl || (window.location.origin + '/wp-admin/admin-ajax.php');

    function getBookId() {
        return window.bookData && Number(window.bookData.bookId) ? Number(window.bookData.bookId) : 0;
    }

    function getNonce() {
        return window.almadenReaderProgressNonce || '';
    }

    function getState() {
        const fallback = {
            totalQuizzes: 0,
            completedQuizzes: 0,
            remainingQuizzes: 0,
            allQuizzesCompleted: false,
            resetAvailable: false,
            quizzes: [],
            attempts: []
        };
        const bookData = window.bookData || {};
        return Object.assign({}, fallback, bookData.quizProgress || {});
    }

    function ensureUI() {
        if (document.getElementById('almaden-progress-panel')) {
            return;
        }

        const style = document.createElement('style');
        style.textContent = `
            #almaden-progress-panel, #almaden-progress-backdrop { display: none; }
            #almaden-progress-backdrop {
                position: fixed; inset: 0; background: rgba(17, 24, 39, 0.45); z-index: 60;
            }
            #almaden-progress-panel {
                position: fixed; right: 1rem; top: 5rem; width: min(92vw, 34rem);
                max-height: calc(100vh - 7rem); overflow: hidden;
                z-index: 61; background: #fff; border: 1px solid #e5e7eb;
                border-radius: 1.5rem; box-shadow: 0 30px 80px rgba(0,0,0,0.18);
                flex-direction: column;
            }
            #almaden-progress-panel.almaden-open, #almaden-progress-backdrop.almaden-open { display: flex; }
            #almaden-progress-backdrop.almaden-open { display: block; }
            .almaden-progress-head { padding: 1rem 1.1rem; border-bottom: 1px solid #f3f4f6; display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; }
            .almaden-progress-body { padding: 1rem 1.1rem 1.1rem; overflow:auto; }
            .almaden-progress-card { border:1px solid #e5e7eb; border-radius: 1rem; padding: .9rem 1rem; background:#fafafa; }
            .almaden-progress-grid { display:grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap:.75rem; }
            .almaden-progress-stat { border-radius: 1rem; background:#111827; color:#fff; padding:.85rem 1rem; }
            .almaden-progress-stat small { display:block; opacity:.65; text-transform:uppercase; letter-spacing:.16em; font-size:10px; margin-bottom:.35rem; }
            .almaden-progress-stat strong { font-size:1.4rem; line-height:1; }
            .almaden-progress-list { display:grid; gap:.7rem; }
            .almaden-progress-quiz { border:1px solid #e5e7eb; border-radius:1rem; background:#fff; padding:.9rem 1rem; }
            .almaden-progress-quiz__meta { display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; }
            .almaden-progress-chip { border-radius:999px; padding:.25rem .6rem; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
            .almaden-progress-chip.is-pass { background:#dcfce7; color:#166534; }
            .almaden-progress-chip.is-wait { background:#f3f4f6; color:#6b7280; }
            .almaden-progress-actions { display:flex; gap:.6rem; flex-wrap:wrap; margin-top:1rem; }
            .almaden-progress-btn { border-radius:999px; padding:.75rem 1rem; font-weight:700; border:1px solid transparent; cursor:pointer; }
            .almaden-progress-btn.primary { background:#111827; color:#fff; }
            .almaden-progress-btn.secondary { background:#fff; border-color:#e5e7eb; color:#374151; }
            .almaden-progress-btn:disabled { opacity:.45; cursor:not-allowed; }
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
                    <p style="margin:0 0 .25rem; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.18em; color:#6b7280;">Resultados y progreso</p>
                    <h3 style="margin:0; font-size:1.1rem; color:#111827;">Mi avance del ebook</h3>
                </div>
                <button type="button" id="almaden-progress-close" class="almaden-progress-btn secondary" style="padding:.55rem .8rem;">Cerrar</button>
            </div>
            <div class="almaden-progress-body" id="almaden-progress-body"></div>
        `;

        document.body.appendChild(backdrop);
        document.body.appendChild(panel);
        document.getElementById('almaden-progress-close').addEventListener('click', closePanel);
    }

    function render(state) {
        const body = document.getElementById('almaden-progress-body');
        if (!body) {
            return;
        }

        const total = Number(state.totalQuizzes || 0);
        const completed = Number(state.completedQuizzes || 0);
        const remaining = Number(state.remainingQuizzes || Math.max(0, total - completed));
        const percent = total > 0 ? Math.round((completed / total) * 100) : 0;
        const quizzes = Array.isArray(state.quizzes) ? state.quizzes : [];
        const attempts = Array.isArray(state.attempts) ? state.attempts : [];

        body.innerHTML = `
            <div class="almaden-progress-grid">
                <div class="almaden-progress-stat"><small>Completados</small><strong>${completed}/${total}</strong></div>
                <div class="almaden-progress-stat"><small>Avance</small><strong>${percent}%</strong></div>
                <div class="almaden-progress-stat"><small>Restantes</small><strong>${remaining}</strong></div>
            </div>
            <div style="margin-top:1rem" class="almaden-progress-card">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
                    <div>
                        <p style="margin:0; font-size:12px; text-transform:uppercase; letter-spacing:.18em; color:#6b7280;">Sesión actual</p>
                        <p style="margin:.25rem 0 0; font-size:14px; color:#374151;">${state.allQuizzesCompleted ? 'Todos los quizzes fueron completados.' : 'Sigue avanzando capítulo a capítulo.'}</p>
                    </div>
                    <span class="almaden-progress-chip ${state.resetAvailable ? 'is-pass' : 'is-wait'}">${state.resetAvailable ? 'Reset disponible' : 'Bloqueado'}</span>
                </div>
                <div class="almaden-progress-actions">
                    <button type="button" class="almaden-progress-btn primary" id="almaden-progress-refresh">Actualizar</button>
                    <button type="button" class="almaden-progress-btn secondary" id="almaden-progress-reset" ${state.resetAvailable ? '' : 'disabled'}>Reset progreso</button>
                </div>
            </div>
            <div style="margin-top:1rem;">
                <p style="margin:0 0 .75rem; font-size:12px; text-transform:uppercase; letter-spacing:.18em; color:#6b7280;">Quizzes del libro</p>
                <div class="almaden-progress-list">
                    ${quizzes.length ? quizzes.map((quiz) => `
                        <article class="almaden-progress-quiz">
                            <div class="almaden-progress-quiz__meta">
                                <div>
                                    <h4 style="margin:0; font-size:15px; color:#111827;">${escapeHtml(quiz.chapter_title || 'Capítulo')}</h4>
                                    <p style="margin:.2rem 0 0; font-size:13px; color:#6b7280;">Quiz ${quiz.quiz_id}</p>
                                </div>
                                <span class="almaden-progress-chip ${quiz.completed ? 'is-pass' : 'is-wait'}">${quiz.completed ? 'Aprobado' : 'Pendiente'}</span>
                            </div>
                            <p style="margin:.8rem 0 0; font-size:13px; color:#4b5563;">Intentos: <strong>${quiz.attempts || 0}</strong> · Mejor puntaje: <strong>${quiz.best_score || 0}%</strong> · Último puntaje: <strong>${quiz.latest_score || 0}%</strong></p>
                        </article>
                    `).join('') : '<div class="almaden-progress-card">Este libro todavía no tiene quizzes vinculados.</div>'}
                </div>
            </div>
            <div style="margin-top:1rem;">
                <p style="margin:0 0 .75rem; font-size:12px; text-transform:uppercase; letter-spacing:.18em; color:#6b7280;">Intentos recientes</p>
                <div class="almaden-progress-list">
                    ${attempts.length ? attempts.map((attempt) => `
                        <article class="almaden-progress-quiz">
                            <div class="almaden-progress-quiz__meta">
                                <div>
                                    <h4 style="margin:0; font-size:15px; color:#111827;">Quiz ${attempt.quiz_id}</h4>
                                    <p style="margin:.2rem 0 0; font-size:13px; color:#6b7280;">${attempt.created_at || ''}</p>
                                </div>
                                <span class="almaden-progress-chip ${attempt.passed ? 'is-pass' : 'is-wait'}">${attempt.passed ? 'Aprobado' : 'Pendiente'}</span>
                            </div>
                            <p style="margin:.8rem 0 0; font-size:13px; color:#4b5563;">Puntaje: <strong>${attempt.score || 0}%</strong> · Requerido: <strong>${attempt.required_score || 0}%</strong> · Intento #${attempt.attempt_number || 1}</p>
                        </article>
                    `).join('') : '<div class="almaden-progress-card">Todavía no hay intentos guardados en esta sesión.</div>'}
                </div>
            </div>
        `;

        const refreshBtn = document.getElementById('almaden-progress-refresh');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                fetchProgress();
            });
        }

        const resetBtn = document.getElementById('almaden-progress-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                resetProgress();
            });
        }
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function syncFromState(nextState) {
        if (window.bookData && typeof window.bookData === 'object' && nextState) {
            window.bookData.quizProgress = nextState;
        }
        render(nextState || getState());
    }

    function openPanel() {
        ensureUI();
        document.getElementById('almaden-progress-panel').classList.add('almaden-open');
        document.getElementById('almaden-progress-backdrop').classList.add('almaden-open');
        render(getState());
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

    function resetProgress() {
        const state = getState();
        if (!state.resetAvailable) {
            return;
        }

        if (!window.confirm('Esto reiniciará el avance visible del libro. Tu historial quedará guardado. ¿Continuar?')) {
            return;
        }

        const data = new FormData();
        data.append('action', 'almaden_reset_book_progress');
        data.append('book_id', String(getBookId()));
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
            getState: getState
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
