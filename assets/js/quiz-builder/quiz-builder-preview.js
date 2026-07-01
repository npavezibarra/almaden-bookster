(function () {
	const QB = window.ALMADEN_QUIZ_BUILDER = window.ALMADEN_QUIZ_BUILDER || {};

	let interactiveState = {
		index: -1, // -1: intro, 0..N-1: question, N: results
		questions: [],
		title: '',
		answers: {}
	};

	function clone(value) {
		if (!value || typeof value !== 'object') return null;
		try { return JSON.parse(JSON.stringify(value)); } catch (e) { return null; }
	}

	function startInteractiveQuizPreview() {
		const loadedQuiz = QB.getLoadedQuiz ? QB.getLoadedQuiz() : null;
		const currentChapter = QB.currentChapter ? QB.currentChapter() : null;
		const bookTitle = QB.getBookTitle ? QB.getBookTitle() : '';
		const previewOverlay = QB.getEl ? QB.getEl('almaden-quiz-preview-overlay') : document.getElementById('almaden-quiz-preview-overlay');

		const chapterTitle = currentChapter && currentChapter.title ? String(currentChapter.title) : '';
		const quizTitle = loadedQuiz && typeof loadedQuiz.quiz_title === 'string' && loadedQuiz.quiz_title.trim() !== ''
			? loadedQuiz.quiz_title.trim()
			: (chapterTitle || bookTitle);

		if (QB.ensureLoadedQuiz) {
			QB.ensureLoadedQuiz();
		}

		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || loadedQuiz.questions.length === 0) {
			window.alert('No hay preguntas cargadas en el quiz para previsualizar. Carga un JSON o edita el preview.');
			return;
		}

		interactiveState = {
			index: -1,
			questions: clone(loadedQuiz.questions),
			title: quizTitle,
			answers: {}
		};

		if (previewOverlay) {
			previewOverlay.style.display = 'flex';
		}

		renderInteractiveQuizStep();
	}

	function renderInteractiveQuizStep() {
		const getEl = QB.getEl || ((id) => document.getElementById(id));
		const previewBody = getEl('almaden-quiz-preview-body');
		const previewOverlay = getEl('almaden-quiz-preview-overlay');

		if (!previewBody) return;

		const escapeHtml = QB.esc || ((s) => String(s || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;'));

		if (interactiveState.index === -1) {
			previewBody.innerHTML = `
				<div class="learni-quiz-intro">
					<div class="learni-quiz-intro__kicker">EVALUACIÓN DE PRUEBA</div>
					<div class="learni-quiz-intro__title">${escapeHtml(interactiveState.title)}</div>
					<div class="learni-quiz-intro__text">Este preview muestra el diseño y la experiencia de la evaluación para los alumnos. No se guardará progreso ni resultados.</div>
					<div class="learni-quiz-actions">
						<button type="button" class="learni-btn" id="almaden-preview-quiz-begin">Comenzar</button>
						<button type="button" class="learni-btn secondary" id="almaden-preview-quiz-cancel">Cancelar</button>
					</div>
				</div>
			`;

			const beginBtn = getEl('almaden-preview-quiz-begin');
			if (beginBtn) {
				beginBtn.addEventListener('click', () => {
					interactiveState.index = 0;
					renderInteractiveQuizStep();
				});
			}

			const cancelBtn = getEl('almaden-preview-quiz-cancel');
			if (cancelBtn) {
				cancelBtn.addEventListener('click', () => {
					if (previewOverlay) previewOverlay.style.display = 'none';
				});
			}
			return;
		}

		if (interactiveState.index >= interactiveState.questions.length) {
			let correctCount = 0;
			interactiveState.questions.forEach((q, qIdx) => {
				const selectedIndex = interactiveState.answers[qIdx];
				if (q.answers && q.answers[selectedIndex] && q.answers[selectedIndex].correct) {
					correctCount++;
				}
			});
			const percent = Math.round((correctCount / interactiveState.questions.length) * 100);

			previewBody.innerHTML = `
				<div class="learni-quiz-results">
					<div class="learni-quiz-results__kicker">Evaluación completada</div>
					<div class="learni-quiz-results__score" style="font-size: 48px; font-weight: 800; text-align: center; margin: 24px 0; color: #16a34a;">
						${percent}%
					</div>
					<div class="learni-quiz-results__text" style="text-align: center; margin-bottom: 24px;">
						Respondiste correctamente <strong>${correctCount} de ${interactiveState.questions.length}</strong> preguntas.
					</div>
					<div class="learni-quiz-actions" style="justify-content: center;">
						<button type="button" class="learni-btn" id="almaden-preview-quiz-close">Cerrar Preview</button>
					</div>
				</div>
			`;

			const closeBtn = getEl('almaden-preview-quiz-close');
			if (closeBtn) {
				closeBtn.addEventListener('click', () => {
					if (previewOverlay) previewOverlay.style.display = 'none';
				});
			}
			return;
		}

		const index = interactiveState.index;
		const q = interactiveState.questions[index];
		const isLast = index === interactiveState.questions.length - 1;
		const answers = Array.isArray(q.answers) ? q.answers : [];

		let answersHtml = '';
		answers.forEach((ans, aIndex) => {
			const isChecked = interactiveState.answers[index] === aIndex ? ' checked="checked"' : '';
			const choiceLabel = String.fromCharCode(65 + aIndex);
			answersHtml += `
				<label class="learni-quiz-a">
					<input type="radio" name="q" value="${aIndex}"${isChecked}>
					<span class="learni-quiz-a__label">${choiceLabel}</span>
					<span class="learni-quiz-a__text">${escapeHtml(ans.text || '')}</span>
				</label>
			`;
		});

		previewBody.innerHTML = `
			<form id="almaden-preview-quiz-form" class="learni-quiz-form">
				<div class="learni-quiz-q">
					<div class="learni-quiz-q__meta">Pregunta ${index + 1} de ${interactiveState.questions.length}</div>
					<div class="learni-quiz-q__text">${escapeHtml(q.question_text || '')}</div>
				</div>
				<div class="learni-quiz-a-list">
					${answersHtml}
				</div>
				<div class="learni-quiz-actions">
					${index > 0 ? '<button type="button" class="learni-btn secondary" id="almaden-preview-quiz-back">Atrás</button>' : ''}
					<button type="submit" class="learni-btn" id="almaden-preview-quiz-submit">${isLast ? 'Enviar' : 'Siguiente'}</button>
				</div>
			</form>
		`;

		const form = getEl('almaden-preview-quiz-form');
		if (form) {
			form.addEventListener('submit', (e) => {
				e.preventDefault();
				const chosen = form.querySelector('input[name="q"]:checked');
				if (!chosen) {
					window.alert('Por favor elige una respuesta.');
					return;
				}
				interactiveState.answers[index] = Number(chosen.value);
				interactiveState.index++;
				renderInteractiveQuizStep();
			});
		}

		const backBtn = getEl('almaden-preview-quiz-back');
		if (backBtn) {
			backBtn.addEventListener('click', () => {
				interactiveState.index = Math.max(0, interactiveState.index - 1);
				renderInteractiveQuizStep();
			});
		}
	}

	function startCustomQuizPreview(customQuiz) {
		const bookTitle = QB.getBookTitle ? QB.getBookTitle() : '';
		const previewOverlay = QB.getEl ? QB.getEl('almaden-quiz-preview-overlay') : document.getElementById('almaden-quiz-preview-overlay');

		if (!customQuiz || !Array.isArray(customQuiz.questions) || customQuiz.questions.length === 0) {
			window.alert('No hay preguntas en el quiz completo para previsualizar.');
			return;
		}

		interactiveState = {
			index: -1,
			questions: clone(customQuiz.questions),
			title: customQuiz.quiz_title || bookTitle,
			answers: {}
		};

		if (previewOverlay) {
			previewOverlay.style.display = 'flex';
		}

		renderInteractiveQuizStep();
	}

	function startInteractiveFullBookQuizPreview(cfg, quizId) {
		const previewOverlay = QB.getEl ? QB.getEl('almaden-quiz-preview-overlay') : document.getElementById('almaden-quiz-preview-overlay');
		const previewBody = QB.getEl ? QB.getEl('almaden-quiz-preview-body') : document.getElementById('almaden-quiz-preview-body');

		if (!quizId) return;

		if (previewOverlay) previewOverlay.style.display = 'flex';
		if (previewBody) {
			previewBody.innerHTML = '<div style="padding: 40px; text-align: center; font-weight: 500; font-family: \'Urbanist\', sans-serif;">Cargando quiz completo...</div>';
		}

		const formData = new FormData();
		formData.append('action', 'almaden_get_quiz_data');
		formData.append('quiz_id', quizId);

		fetch(cfg.homeUrl + 'wp-admin/admin-ajax.php', {
			method: 'POST',
			body: formData
		})
		.then(res => res.json())
		.then(res => {
			if (res.success && res.data.quiz && Array.isArray(res.data.quiz.questions) && res.data.quiz.questions.length > 0) {
				const quiz = res.data.quiz;
				startCustomQuizPreview(quiz);
			} else {
				if (previewBody) {
					previewBody.innerHTML = '<div style="padding: 40px; text-align: center; color: #dc2626; font-weight: 600; font-family: \'Urbanist\', sans-serif;">No hay preguntas guardadas en el quiz completo de este libro.</div>';
				}
			}
		})
		.catch(() => {
			if (previewBody) {
				previewBody.innerHTML = '<div style="padding: 40px; text-align: center; color: #dc2626; font-weight: 600; font-family: \'Urbanist\', sans-serif;">Error de red al cargar el quiz completo.</div>';
			}
		});
	}

	QB.startInteractiveQuizPreview = startInteractiveQuizPreview;
	QB.startCustomQuizPreview = startCustomQuizPreview;
	QB.startInteractiveFullBookQuizPreview = startInteractiveFullBookQuizPreview;
})();
