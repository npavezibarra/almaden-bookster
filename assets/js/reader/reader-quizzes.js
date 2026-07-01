(function () {
    const cfg = window.bookData || {};
    const flowSettings = cfg.quizFlowSettings || {};
    const chapters = Array.isArray(cfg.chapters) ? cfg.chapters : [];

    // Load approved quizzes from backend context first, fallback to localStorage
    let approvedQuizzes = Array.isArray(cfg.approvedQuizzes) ? cfg.approvedQuizzes.map(Number) : [];
    if (approvedQuizzes.length === 0) {
        try {
            approvedQuizzes = JSON.parse(localStorage.getItem('almaden_approved_quizzes') || '[]');
        } catch (e) {
            approvedQuizzes = [];
        }
    }

    // Export helpers
    window.ALMADEN_READER_QUIZZES = {
        getApprovedQuizzes: () => approvedQuizzes,
        clearApprovedQuizzes: () => {
            approvedQuizzes = [];
            localStorage.setItem('almaden_approved_quizzes', '[]');
        },
        approveQuiz: (quizId) => {
            const id = Number(quizId);
            if (!approvedQuizzes.includes(id)) {
                approvedQuizzes.push(id);
                localStorage.setItem('almaden_approved_quizzes', JSON.stringify(approvedQuizzes));
            }
        }
    };

    // Intercept showChapterView
    const originalShowChapterView = window.showChapterView;
    window.showChapterView = function (index) {
        if (!flowSettings.is_mandatory) {
            originalShowChapterView(index);
            updateTakeQuizButton(index);
            return;
        }

        // We are jumping to a chapter at index. Let's see if there are any unpassed quizzes in the chapters prior to this index.
        const unpassedQuizChapter = findUnpassedQuizChapterBefore(index);
        if (unpassedQuizChapter) {
            alert('Debes aprobar la evaluación de lectura antes de avanzar a este capítulo.');
            startQuizPlayer(unpassedQuizChapter.quiz_id, unpassedQuizChapter.title, () => {
                originalShowChapterView(index);
                updateTakeQuizButton(index);
            });
            return;
        }

        originalShowChapterView(index);
        updateTakeQuizButton(index);
    };

    // Intercept goToNextChapter
    const originalGoToNextChapter = window.goToNextChapter;
    window.goToNextChapter = function () {
        if (!flowSettings.is_mandatory) {
            return originalGoToNextChapter();
        }

        const nextIndex = getNextChapterIndex();
        if (nextIndex < 0) {
            return originalGoToNextChapter();
        }

        const unpassedQuizChapter = findUnpassedQuizChapterBefore(nextIndex);
        if (unpassedQuizChapter) {
            alert('Debes aprobar la evaluación de lectura del capítulo para continuar.');
            startQuizPlayer(unpassedQuizChapter.quiz_id, unpassedQuizChapter.title, () => {
                originalGoToNextChapter();
            });
            return;
        }

        originalGoToNextChapter();
    };

    function getNextChapterIndex() {
        if (typeof currentChapterIndex === 'undefined' || currentChapterIndex < 0) return -1;
        let nextIndex = currentChapterIndex + 1;
        while (nextIndex < chapters.length && (chapters[nextIndex].is_toc === '1' || chapters[nextIndex].is_credits === '1')) {
            nextIndex++;
        }
        return nextIndex < chapters.length ? nextIndex : -1;
    }

    function findUnpassedQuizChapterBefore(targetIndex) {
        // Look from index 0 up to targetIndex - 1 (inclusive)
        // If there's any chapter with a quiz_id that is NOT approved, return it
        for (let i = 0; i < targetIndex; i++) {
            const ch = chapters[i];
            if (ch.is_toc === '1' || ch.is_credits === '1') continue;
            
            const quizId = parseInt(ch.quiz_id, 10);
            if (quizId > 0 && !approvedQuizzes.includes(quizId)) {
                // Check if this quiz matches flow_mode requirements.
                if (flowSettings.flow_mode === 'interval') {
                    // Is this chapter an interval boundary?
                    const chNumber = chapters.filter(c => c.is_toc !== '1' && c.is_credits !== '1' && chapters.indexOf(c) <= i).length;
                    const interval = parseInt(flowSettings.interval_chapters, 10) || 3;
                    if (chNumber % interval !== 0 && i !== chapters.length - 1) {
                        // Not an interval boundary and not the absolute last chapter, skip enforcement
                        continue;
                    }
                }
                return ch;
            }
        }
        return null;
    }

    // Quiz Player State
    let playerState = {
        index: -1,
        questions: [],
        title: '',
        answers: {},
        quizId: 0,
        onSuccess: null
    };

    function shuffleArray(array) {
        const arr = array.slice();
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            const temp = arr[i];
            arr[i] = arr[j];
            arr[j] = temp;
        }
        return arr;
    }

    function startQuizPlayer(quizId, chapterTitle, onSuccess) {
        const overlay = document.getElementById('almaden-quiz-player-overlay');
        const body = document.getElementById('almaden-quiz-player-body');
        if (!overlay || !body) return;

        body.innerHTML = '<div style="padding: 40px; text-align: center; font-weight: 500;">Cargando evaluación...</div>';
        overlay.style.display = 'flex';

        const formData = new FormData();
        formData.append('action', 'almaden_get_quiz_data');
        formData.append('quiz_id', quizId);

        fetch(window.location.origin + '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data.quiz && Array.isArray(res.data.quiz.questions) && res.data.quiz.questions.length > 0) {
                const quiz = res.data.quiz;
                let loadedQuestions = quiz.questions;
                if (flowSettings.question_order === 'random') {
                    loadedQuestions = shuffleArray(loadedQuestions);
                }
                if (flowSettings.answer_order === 'random') {
                    loadedQuestions.forEach(q => {
                        if (Array.isArray(q.answers)) {
                            q.answers = shuffleArray(q.answers);
                        }
                    });
                }
                playerState = {
                    index: -1,
                    questions: loadedQuestions,
                    title: quiz.quiz_title || chapterTitle,
                    answers: {},
                    quizId: quizId,
                    onSuccess: onSuccess
                };
                renderPlayerStep();
            } else {
                body.innerHTML = `<div style="padding: 40px; text-align: center; color: #dc2626; font-weight: 600;">Error: ${res.data || 'No se pudo cargar el quiz.'}</div>`;
            }
        })
        .catch(err => {
            body.innerHTML = '<div style="padding: 40px; text-align: center; color: #dc2626; font-weight: 600;">Error de red al cargar la evaluación.</div>';
        });
    }

    function renderPlayerStep() {
        const body = document.getElementById('almaden-quiz-player-body');
        const overlay = document.getElementById('almaden-quiz-player-overlay');
        if (!body) return;

        const esc = (s) => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');

        if (playerState.index === -1) {
            body.innerHTML = `
                <div class="learni-quiz-intro">
                    <div class="learni-quiz-intro__kicker">EVALUACIÓN DE APRENDIZAJE</div>
                    <div class="learni-quiz-intro__title">${esc(playerState.title)}</div>
                    <div class="learni-quiz-intro__text">Responde esta evaluación sobre lo leído para validar tu aprendizaje y desbloquear el contenido siguiente. Requiere un <strong>${flowSettings.passing_score || 80}%</strong> para aprobar.</div>
                    <div class="learni-quiz-actions">
                        <button type="button" class="learni-btn" id="almaden-player-quiz-begin">Comenzar</button>
                        <button type="button" class="learni-btn secondary" id="almaden-player-quiz-cancel">Cerrar</button>
                    </div>
                </div>
            `;
            document.getElementById('almaden-player-quiz-begin').onclick = () => {
                playerState.index = 0;
                renderPlayerStep();
            };
            document.getElementById('almaden-player-quiz-cancel').onclick = () => {
                overlay.style.display = 'none';
            };
            return;
        }

        if (playerState.index >= playerState.questions.length) {
            body.innerHTML = '<div style="padding: 40px; text-align: center; font-weight: 500;">Enviando respuestas y calificando...</div>';

            let correctCount = 0;
            playerState.questions.forEach((q, qIdx) => {
                const selIdx = playerState.answers[qIdx];
                if (q.answers && q.answers[selIdx] && q.answers[selIdx].correct) {
                    correctCount++;
                }
            });
            const percent = Math.round((correctCount / playerState.questions.length) * 100);

            // Send score to backend via AJAX
            const formData = new FormData();
            formData.append('action', 'almaden_submit_quiz_result');
            formData.append('book_id', cfg.bookId);
            formData.append('quiz_id', playerState.quizId);
            formData.append('score', percent);

            fetch(window.location.origin + '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    const passed = res.data.passed;
                    const required = res.data.required_score;
                    if (passed) {
                        window.ALMADEN_READER_QUIZZES.approveQuiz(playerState.quizId);
                        
                        body.innerHTML = `
                            <div class="learni-quiz-results">
                                <div class="learni-quiz-results__kicker" style="color: #16a34a;">¡FELICITACIONES! APROBADO</div>
                                <div class="learni-quiz-results__score" style="font-size: 48px; font-weight: 800; text-align: center; margin: 24px 0; color: #16a34a;">
                                    ${percent}%
                                </div>
                                <div class="learni-quiz-results__text" style="text-align: center; margin-bottom: 24px;">
                                    Respondiste correctamente <strong>${correctCount} de ${playerState.questions.length}</strong> preguntas.<br>Has desbloqueado las siguientes páginas.
                                </div>
                                <div class="learni-quiz-actions" style="justify-content: center;">
                                    <button type="button" class="learni-btn" id="almaden-player-quiz-close">Continuar Lectura</button>
                                </div>
                            </div>
                        `;
                        document.getElementById('almaden-player-quiz-close').onclick = () => {
                            overlay.style.display = 'none';
                            if (playerState.onSuccess) playerState.onSuccess();
                        };
                    } else {
                        body.innerHTML = `
                            <div class="learni-quiz-results">
                                <div class="learni-quiz-results__kicker" style="color: #dc2626;">EVALUACIÓN REPROBADA</div>
                                <div class="learni-quiz-results__score" style="font-size: 48px; font-weight: 800; text-align: center; margin: 24px 0; color: #dc2626;">
                                    ${percent}%
                                </div>
                                <div class="learni-quiz-results__text" style="text-align: center; margin-bottom: 24px;">
                                    Obtuviste <strong>${correctCount} de ${playerState.questions.length}</strong> correctas. Necesitas un mínimo de <strong>${required}%</strong> para avanzar.
                                </div>
                                <div class="learni-quiz-actions" style="justify-content: center;">
                                    <button type="button" class="learni-btn" id="almaden-player-quiz-retry">Reintentar</button>
                                    <button type="button" class="learni-btn secondary" id="almaden-player-quiz-close-fail">Estudiar Más</button>
                                </div>
                            </div>
                        `;
                        document.getElementById('almaden-player-quiz-retry').onclick = () => {
                            playerState.index = 0;
                            playerState.answers = {};
                            renderPlayerStep();
                        };
                        document.getElementById('almaden-player-quiz-close-fail').onclick = () => {
                            overlay.style.display = 'none';
                        };
                    }
                } else {
                    body.innerHTML = `<div style="padding: 40px; text-align: center; color: #dc2626; font-weight: 600;">Error: ${res.data || 'No se pudo calificar.'}</div>`;
                }
            })
            .catch(() => {
                body.innerHTML = '<div style="padding: 40px; text-align: center; color: #dc2626; font-weight: 600;">Error de red al calificar.</div>';
            });
            return;
        }

        const idx = playerState.index;
        const q = playerState.questions[idx];
        const isLast = idx === playerState.questions.length - 1;
        const answers = Array.isArray(q.answers) ? q.answers : [];

        let answersHtml = '';
        answers.forEach((ans, aIdx) => {
            const isChecked = playerState.answers[idx] === aIdx ? ' checked="checked"' : '';
            answersHtml += `
                <label class="learni-quiz-a">
                    <input type="radio" name="q" value="${aIdx}"${isChecked}>
                    <span class="learni-quiz-a__label">${String.fromCharCode(65 + aIdx)}</span>
                    <span class="learni-quiz-a__text">${esc(ans.text || '')}</span>
                </label>
            `;
        });

        body.innerHTML = `
            <form id="almaden-player-quiz-form" class="learni-quiz-form">
                <div class="learni-quiz-q">
                    <div class="learni-quiz-q__meta">Pregunta ${idx + 1} de ${playerState.questions.length}</div>
                    <div class="learni-quiz-q__text">${esc(q.question_text || '')}</div>
                </div>
                <div class="learni-quiz-a-list">
                    ${answersHtml}
                </div>
                <div class="learni-quiz-actions">
                    ${idx > 0 ? '<button type="button" class="learni-btn secondary" id="almaden-player-quiz-back">Atrás</button>' : ''}
                    <button type="submit" class="learni-btn" id="almaden-player-quiz-submit">${isLast ? 'Enviar' : 'Siguiente'}</button>
                </div>
            </form>
        `;

        const form = document.getElementById('almaden-player-quiz-form');
        form.onsubmit = (e) => {
            e.preventDefault();
            const chosen = form.querySelector('input[name="q"]:checked');
            if (!chosen) {
                alert('Por favor elige una respuesta.');
                return;
            }
            playerState.answers[idx] = Number(chosen.value);
            playerState.index++;
            renderPlayerStep();
        };

        const back = document.getElementById('almaden-player-quiz-back');
        if (back) {
            back.onclick = () => {
                playerState.index = Math.max(0, playerState.index - 1);
                renderPlayerStep();
            };
        }
    }

    function updateTakeQuizButton(index) {
        const btn = document.getElementById('btn-take-quiz');
        if (!btn) return;

        const ch = chapters[index];
        if (!ch || ch.is_toc === '1' || ch.is_credits === '1') {
            btn.classList.add('hidden');
            return;
        }

        const quizId = parseInt(ch.quiz_id, 10);
        if (quizId <= 0) {
            btn.classList.add('hidden');
            return;
        }

        // Check if this matches flow_mode requirements.
        if (flowSettings.flow_mode === 'interval') {
            const chNumber = chapters.filter(c => c.is_toc !== '1' && c.is_credits !== '1' && chapters.indexOf(c) <= index).length;
            const interval = parseInt(flowSettings.interval_chapters, 10) || 3;
            if (chNumber % interval !== 0 && index !== chapters.length - 1) {
                btn.classList.add('hidden');
                return;
            }
        }

        btn.classList.remove('hidden');

        const approved = approvedQuizzes.includes(quizId);
        if (approved) {
            btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Quiz Aprobado';
            btn.className = "mx-auto px-6 py-2.5 rounded-full bg-green-50 text-green-700 border border-green-200 font-semibold text-sm flex items-center gap-2 cursor-pointer transition-all";
        } else {
            btn.innerHTML = '<i class="fa-solid fa-circle-question"></i> Tomar Quiz';
            btn.className = "mx-auto px-6 py-2.5 rounded-full bg-black hover:bg-gray-800 text-white font-semibold text-sm flex items-center gap-2 cursor-pointer transition-all";
        }

        btn.onclick = () => {
            startQuizPlayer(quizId, ch.title, () => {
                updateTakeQuizButton(index);
            });
        };
    }

    // Modal close hooks
    const closeBtn = document.getElementById('almaden-quiz-player-close-btn');
    const closeBackdrop = document.getElementById('almaden-quiz-player-close-backdrop');
    const hidePlayer = () => {
        const overlay = document.getElementById('almaden-quiz-player-overlay');
        if (overlay) overlay.style.display = 'none';
    };
    if (closeBtn) closeBtn.onclick = hidePlayer;
    if (closeBackdrop) closeBackdrop.onclick = hidePlayer;
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') hidePlayer();
    });

    // Run on initial load when page renders
    window.addEventListener('load', () => {
        if (typeof currentChapterIndex !== 'undefined' && currentChapterIndex >= 0) {
            updateTakeQuizButton(currentChapterIndex);
        }
    });
})();
