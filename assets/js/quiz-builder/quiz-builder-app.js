(function () {
	const cfg = window.ALMADEN_QUIZ_BUILDER_DATA || {};
	const chapters = Array.isArray(cfg.chapters) ? cfg.chapters : [];
	const bookTitle = String(cfg.bookTitle || '');
	let activeChapterIndex = Number.isFinite(Number(cfg.initialActiveChapterIndex)) ? Number(cfg.initialActiveChapterIndex) : 0;
	let loadedQuiz = clone(cfg.initialQuizData);
	let activePreviewQuestionIndex = 0;
	let activeTab = 'prompt-settings';

	const $ = (id) => document.getElementById(id);
	const chapterList = $('almaden-chapter-list'), activeTitle = $('almaden-active-chapter-title'), activeCaption = $('almaden-active-chapter-caption'), chapterRaw = $('almaden-chapter-raw');
	const promptInput = $('almaden-prompt-input'), loadPromptBtn = $('almaden-load-prompt'), previewEmpty = $('almaden-preview-empty'), previewList = $('almaden-preview-list');
	const previewSummary = $('almaden-preview-summary'), previewFocus = $('almaden-preview-focus'), copyActivePromptBtn = $('almaden-copy-active-prompt'), saveQuizBtn = $('almaden-save-quiz');
	const saveQuizForm = $('almaden-book-quiz-save-form'), saveQuizPayloadField = $('almaden-quiz-payload-json'), quizIdField = $('almaden-quiz-id'), previewChapterBtn = $('almaden-preview-chapter-btn');
	const previewBookBtn = $('almaden-preview-book-btn'), previewOverlay = $('almaden-quiz-preview-overlay'), previewBody = $('almaden-quiz-preview-body'), closeBackdrop = $('almaden-quiz-preview-close-backdrop');
	const closeBtn = $('almaden-quiz-preview-close-btn'), questionCountField = $('almaden-setting-question-count'), alternativesCountField = $('almaden-setting-alternatives-count');
	const difficultyField = $('almaden-setting-difficulty'), styleField = $('almaden-setting-style');
	const tabButtons = Array.from(document.querySelectorAll('[data-tab-target]'));
	const tabPanels = Array.from(document.querySelectorAll('[data-tab-panel]'));

	function clone(v) { if (!v || typeof v !== 'object') return null; try { return JSON.parse(JSON.stringify(v)); } catch (e) { return null; } }
	function currentChapter() { return chapters[activeChapterIndex] || null; }
	function getPromptSettings() {
		const q = questionCountField ? parseInt(questionCountField.value, 10) : 5, a = alternativesCountField ? parseInt(alternativesCountField.value, 10) : 4;
		return {
			questionCount: Number.isFinite(q) && q > 0 ? q : 5,
			alternativesCount: Number.isFinite(a) && a > 0 ? a : 4,
			difficulty: difficultyField ? String(difficultyField.value || 'medium') : 'medium',
			style: styleField ? String(styleField.value || 'clear') : 'clear'
		};
	}
	function esc(v) { return String(v || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
	function setActiveTab(name) {
		activeTab = name;
		tabButtons.forEach((button) => {
			const isActive = button.getAttribute('data-tab-target') === name;
			button.classList.toggle('is-active', isActive);
			button.setAttribute('aria-selected', isActive ? 'true' : 'false');
		});
		tabPanels.forEach((panel) => {
			panel.classList.toggle('is-active', panel.getAttribute('data-tab-panel') === name);
		});
	}
	function updateChapterList() {
		if (!chapterList) return;
		chapterList.querySelectorAll('[data-chapter-index]').forEach((node) => {
			node.classList.toggle('is-active', Number(node.getAttribute('data-chapter-index')) === activeChapterIndex);
		});
	}
	function updateChapterView() {
		const chapter = currentChapter();
		if (!chapter) return;
		if (activeTitle) activeTitle.textContent = chapter.title || ('Chapter ' + (chapter.order || activeChapterIndex + 1));
		if (activeCaption) activeCaption.textContent = 'Chapter ' + (chapter.order || activeChapterIndex + 1);
		if (chapterRaw) chapterRaw.textContent = chapter.content || 'Este capítulo no tiene contenido.';
		if (quizIdField) quizIdField.value = chapter.quiz_id ? String(chapter.quiz_id) : '0';
		
		const hasQuiz = chapter.quiz_id && Number(chapter.quiz_id) > 0;
		if (previewChapterBtn) {
			previewChapterBtn.classList.toggle('is-disabled', !hasQuiz);
			if (hasQuiz) previewChapterBtn.removeAttribute('title');
			else previewChapterBtn.setAttribute('title', 'Guarda el quiz primero para poder previsualizar.');
		}
		if (previewBookBtn) {
			previewBookBtn.classList.toggle('is-disabled', !hasQuiz);
			if (hasQuiz) previewBookBtn.removeAttribute('title');
			else previewBookBtn.setAttribute('title', 'Guarda el quiz primero para poder previsualizar.');
		}
	}
	function activateChapter(index) {
		if (!chapters[index]) return;
		activeChapterIndex = index;
		const chapter = currentChapter();
		loadedQuiz = chapter && chapter.quiz_data ? clone(chapter.quiz_data) : null;
		activePreviewQuestionIndex = 0;
		updateChapterList();
		updateChapterView();
		if (QB.renderPreview) QB.renderPreview();
		setActiveTab('chapter-content');
	}
	function copyText(text, button) {
		if (!text) return Promise.resolve();
		const done = () => {
			if (button) {
				const original = button.innerHTML;
				button.innerHTML = '<i class="fa-solid fa-check"></i>';
				setTimeout(() => { button.innerHTML = original; }, 1200);
			}
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text).then(done);
		}
		const ta = document.createElement('textarea');
		ta.value = text;
		ta.setAttribute('readonly', 'readonly');
		ta.style.position = 'fixed';
		ta.style.left = '-9999px';
		document.body.appendChild(ta);
		ta.select();
		document.execCommand('copy');
		document.body.removeChild(ta);
		done();
		return Promise.resolve();
	}
	function chapterPrompt(chapter) {
		const settings = getPromptSettings();
		const chapterNumber = chapter.order || (activeChapterIndex + 1);
		const chapterKey = chapter.key || ('chapter-' + chapterNumber);
		const quizTitle = bookTitle + ' · ' + (chapter.title || ('Chapter ' + chapterNumber));

		const flowSettings = QB.flowSettings || {};
		let contentToUse = chapter.content || '';
		let evaluatedChaptersText = '';

		if (flowSettings.flow_mode === 'interval') {
			const n = parseInt(flowSettings.interval_chapters, 10) || 3;
			const startIndex = Math.max(0, activeChapterIndex - n + 1);
			const rangeChapters = chapters.slice(startIndex, activeChapterIndex + 1);
			contentToUse = rangeChapters.map(ch => `[CAPÍTULO ${ch.order || (chapters.indexOf(ch) + 1)}: ${ch.title}]\n${ch.content || ''}`).join('\n\n');
			const chNumbers = rangeChapters.map(ch => ch.order || (chapters.indexOf(ch) + 1));
			evaluatedChaptersText = `El quiz debe evaluar acumulativamente los capítulos: ${chNumbers.join(', ')}.`;
		} else {
			evaluatedChaptersText = `El quiz debe evaluar el capítulo ${chapter.order || (activeChapterIndex + 1)}.`;
		}

		const lines = [
			'ACTÚA COMO UN DISEÑADOR EXPERTO DE QUIZZES PARA UN LIBRO.',
			'Tu única tarea es crear un quiz basado en el contenido del capítulo indicado abajo.',
			'Usa únicamente el contenido del capítulo como fuente. No agregues información externa ni inventes datos.',
			'Genera exactamente ' + settings.questionCount + ' preguntas.',
			'Cada pregunta debe tener exactamente ' + settings.alternativesCount + ' alternativas.',
			'La dificultad objetivo es: ' + settings.difficulty + '.',
			'El estilo deseado es: ' + settings.style + '.',
			'Prioriza comprensión, relaciones entre ideas, causas, consecuencias e interpretación por sobre memoria literal.',
			'Evita preguntas cuya respuesta pueda deducirse por longitud, tecnicismo o singularidad de una alternativa.',
			'Todas las alternativas deben pertenecer a la misma categoría semántica.',
			'Las alternativas deben tener longitudes parecidas.',
			'No uses "Todas las anteriores", "Ninguna de las anteriores", "Siempre" ni "Nunca".',
			'Distribuye la respuesta correcta de forma variada entre las alternativas.',
			'Cada pregunta debe evaluar una sola idea.',
			'No reutilices el mismo fragmento del texto para varias preguntas.',
			'Si una pregunta puede responderse sin leer el capítulo, descártala.',
			'Los distractores deben ser verosímiles para alguien que leyó superficialmente el texto.',
			'Devuelve exclusivamente JSON válido, sin markdown, sin viñetas, sin explicaciones, sin saludos y sin texto adicional.',
			'',
			'Contexto:',
			'- Libro: ' + bookTitle,
			'- Capítulo: ' + (chapter.title || ('Chapter ' + chapterNumber)),
			'- Identificador: ' + chapterKey,
			'- Título sugerido del quiz: ' + quizTitle,
			'- Alcance de evaluación: ' + evaluatedChaptersText,
			'- Número de preguntas: ' + settings.questionCount,
			'- Alternativas por pregunta: ' + settings.alternativesCount,
			'- Dificultad: ' + settings.difficulty,
			'- Estilo: ' + settings.style,
			'- El JSON final debe seguir el formato de Learni/Bookster.',
			'- No incluyas texto fuera del JSON final.',
			'- La salida debe ser un objeto JSON único y completo.',
			'',
			'Contenido del texto de referencia:',
			contentToUse,
			'',
			'Formato de salida requerido:',
			'{',
			'  "quiz_title": ' + JSON.stringify(quizTitle) + ',',
			'  "scope": "chapter",',
			'  "book_title": ' + JSON.stringify(bookTitle) + ',',
			'  "chapter_title": ' + JSON.stringify(chapter.title || ('Chapter ' + chapterNumber)) + ',',
			'  "chapter_key": ' + JSON.stringify(chapterKey) + ',',
			'  "settings": {',
			'    "passing_score": 80,',
			'    "time_limit_seconds": 0,',
			'    "question_order": "in_order",',
			'    "shuffle_answers": 1,',
			'    "show_points": 0,',
			'    "run_once": 0,',
			'    "force_solve": 1,',
			'    "restart_cooldown_days": 0,',
			'    "question_count": ' + settings.questionCount + ',',
			'    "alternatives_count": ' + settings.alternativesCount + ',',
			'    "difficulty": ' + JSON.stringify(settings.difficulty) + ',',
			'    "style": ' + JSON.stringify(settings.style),
			'  },',
			'  "questions": [',
			'    {',
			'      "title": "Pregunta 1",',
			'      "question_text": "Texto de la pregunta",',
			'      "answers": [',
			'        { "text": "Opción 1", "correct": false },',
			'        { "text": "Opción correcta", "correct": true }',
			'      ]',
			'    }',
			'  ]',
			'}'
		];
		return lines.join('\n');
	}

	const QB = window.ALMADEN_QUIZ_BUILDER = window.ALMADEN_QUIZ_BUILDER || {};
	QB.flowSettings = clone(cfg.quizFlowSettings) || {};
	QB.getLoadedQuiz = () => loadedQuiz;
	QB.setLoadedQuiz = (value) => { loadedQuiz = value; };
	QB.getActivePreviewQuestionIndex = () => activePreviewQuestionIndex;
	QB.setActivePreviewQuestionIndex = (value) => { activePreviewQuestionIndex = Number.isFinite(Number(value)) ? Number(value) : 0; };
	QB.getBookTitle = () => bookTitle;
	QB.getEl = $;
	QB.esc = esc;
	QB.currentChapter = currentChapter;
	QB.updateChapterView = updateChapterView;

	function buildSavePayload() {
		const chapter = currentChapter();
		const chapterKey = chapter && chapter.key ? String(chapter.key) : ('chapter-' + (activeChapterIndex + 1));
		const chapterId = chapter && chapter.id ? Number(chapter.id) : 0;
		const chapterTitle = chapter && chapter.title ? String(chapter.title) : '';
		const quizTitle = loadedQuiz && typeof loadedQuiz.quiz_title === 'string' && loadedQuiz.quiz_title.trim() !== '' ? loadedQuiz.quiz_title.trim() : (chapterTitle || bookTitle);
		const settings = loadedQuiz && loadedQuiz.settings && typeof loadedQuiz.settings === 'object' ? { ...loadedQuiz.settings } : {};
		const questions = Array.isArray(loadedQuiz && loadedQuiz.questions) ? loadedQuiz.questions.map((question) => ({
			title: String(question && question.title ? question.title : ''),
			question_text: String(question && question.question_text ? question.question_text : ''),
			chapter_key: String(question && question.chapter_key ? question.chapter_key : chapterKey),
			chapter_id: Number.isFinite(Number(question && question.chapter_id)) ? Number(question.chapter_id) : chapterId,
			chapter_title: String(question && question.chapter_title ? question.chapter_title : chapterTitle),
			answers: Array.isArray(question && question.answers) ? question.answers.map((answer) => ({
				text: String(answer && answer.text ? answer.text : ''),
				correct: !!(answer && answer.correct)
			})) : []
		})) : [];
		return {
			quiz_title: quizTitle,
			scope: String(loadedQuiz && loadedQuiz.scope ? loadedQuiz.scope : 'chapter'),
			book_title: bookTitle,
			chapter_title: chapterTitle,
			chapter_key: chapterKey,
			chapter_id: chapterId,
			settings: {
				passing_score: Number.isFinite(Number(settings.passing_score)) ? Number(settings.passing_score) : 80,
				time_limit_seconds: Number.isFinite(Number(settings.time_limit_seconds)) ? Number(settings.time_limit_seconds) : 0,
				question_order: String(settings.question_order || 'in_order'),
				shuffle_answers: settings.shuffle_answers ? 1 : 0,
				show_points: settings.show_points ? 1 : 0,
				run_once: settings.run_once ? 1 : 0,
				force_solve: settings.force_solve ? 1 : 0,
				restart_cooldown_days: Number.isFinite(Number(settings.restart_cooldown_days)) ? Number(settings.restart_cooldown_days) : 0,
				scope: String(loadedQuiz && loadedQuiz.scope ? loadedQuiz.scope : 'chapter'),
				book_title: bookTitle,
				chapter_title: chapterTitle,
				chapter_key: chapterKey,
				chapter_id: chapterId
			},
			questions: questions
		};
	}

	function saveQuiz() {
		if (!saveQuizForm || !saveQuizPayloadField) {
			window.alert('No se pudo preparar el formulario de guardado.');
			return;
		}
		if (!loadedQuiz) {
			loadedQuiz = { quiz_title: bookTitle, scope: 'chapter', settings: getPromptSettings(), questions: [QB.createBlankQuestion(0)] };
		}
		if (QB.ensureLoadedQuiz) QB.ensureLoadedQuiz();
		saveQuizPayloadField.value = JSON.stringify(buildSavePayload());
		if (quizIdField) {
			const chapter = currentChapter();
			quizIdField.value = chapter && chapter.quiz_id ? String(chapter.quiz_id) : '0';
		}
		saveQuizForm.submit();
	}

	function loadPromptPayload() {
		const raw = promptInput ? String(promptInput.value || '').trim() : '';
		if (!raw) return window.alert('Pega primero el JSON generado por el LLM.');
		if (!QB.extractJsonFromRawText || !QB.normalizeQuizPayload) {
			return window.alert('El módulo de análisis de JSON no está cargado.');
		}
		const parsed = QB.extractJsonFromRawText(raw);
		if (!parsed) return window.alert('El contenido pegado no es JSON válido.');
		const normalized = QB.normalizeQuizPayload(parsed);
		if (!normalized) return window.alert('El JSON no contiene una estructura de quiz válida.');
		loadedQuiz = normalized;
		activePreviewQuestionIndex = 0;
		if (QB.renderPreview) QB.renderPreview();
		setActiveTab('quiz-preview');
	}

	function bindChapterEvents() {
		if (!chapterList) return;
		chapterList.addEventListener('click', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) return;
			const item = target.closest('[data-chapter-index]');
			if (!item) return;
			activateChapter(Number(item.getAttribute('data-chapter-index')));
		});
		chapterList.addEventListener('keydown', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) return;
			if (event.key !== 'Enter' && event.key !== ' ') return;
			const item = target.closest('[data-chapter-index]');
			if (!item) return;
			event.preventDefault();
			activateChapter(Number(item.getAttribute('data-chapter-index')));
		});
	}

	function bindPreviewEvents() {
		if (!previewList) return;
		previewList.addEventListener('click', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) return;
			const goTo = target.closest('[data-preview-go-to]');
			if (goTo && QB.setActiveQuestion) return QB.setActiveQuestion(Number(goTo.getAttribute('data-preview-go-to')));
			const actionNode = target.closest('[data-preview-action]');
			if (!actionNode) return;
			const action = actionNode.getAttribute('data-preview-action');
			const currentIndex = activePreviewQuestionIndex;
			if (action === 'prev' && QB.setActiveQuestion) return QB.setActiveQuestion(currentIndex - 1);
			if (action === 'next' && QB.setActiveQuestion) return QB.setActiveQuestion(currentIndex + 1);
			if (action === 'add-question' && QB.addQuestion) return QB.addQuestion();
			if (action === 'remove-question' && QB.removeQuestion) return QB.removeQuestion(currentIndex);
			if (action === 'duplicate-question' && QB.duplicateQuestion) return QB.duplicateQuestion(currentIndex);
			if (action === 'add-answer' && QB.addAnswer) return QB.addAnswer(currentIndex);
			if (action === 'remove-answer' && QB.removeAnswer) return QB.removeAnswer(currentIndex, Number(actionNode.getAttribute('data-preview-answer-index')));
		});
		previewList.addEventListener('input', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) return;
			if (!QB.getActiveQuestion) return;
			const q = QB.getActiveQuestion();
			if (!q) return;
			const titleField = target.closest('[data-preview-field="question-title"]');
			if (titleField instanceof HTMLInputElement) {
				q.title = titleField.value;
				if (QB.renderPreview) QB.renderPreview();
				return;
			}
			const textField = target.closest('[data-preview-field="question-text"]');
			if (textField instanceof HTMLTextAreaElement) {
				q.question_text = textField.value;
				return;
			}
			const answerTextField = target.closest('[data-preview-answer-text]');
			if (answerTextField instanceof HTMLInputElement) {
				const answerIndex = Number(answerTextField.getAttribute('data-preview-answer-text'));
				if (q.answers && q.answers[answerIndex]) q.answers[answerIndex].text = answerTextField.value;
			}
		});
		previewList.addEventListener('change', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) return;
			const answerCorrectField = target.closest('[data-preview-answer-correct]');
			if (answerCorrectField instanceof HTMLInputElement && QB.setAnswerCorrect) {
				const answerIndex = Number(answerCorrectField.getAttribute('data-preview-answer-correct'));
				QB.setAnswerCorrect(activePreviewQuestionIndex, answerIndex, answerCorrectField.checked);
			}
		});
	}

	function bindGlobalEvents() {
		tabButtons.forEach((button) => button.addEventListener('click', () => setActiveTab(button.getAttribute('data-tab-target') || 'prompt-settings')));
		if (copyActivePromptBtn) copyActivePromptBtn.addEventListener('click', () => {
			const c = currentChapter(); if (c) copyText(chapterPrompt(c), copyActivePromptBtn);
		});
		if (loadPromptBtn) loadPromptBtn.addEventListener('click', loadPromptPayload);
		if (previewFocus) previewFocus.addEventListener('click', () => setActiveTab('quiz-preview'));
		if (saveQuizBtn) saveQuizBtn.addEventListener('click', saveQuiz);
		
		if (previewChapterBtn) previewChapterBtn.addEventListener('click', (e) => {
			e.preventDefault(); if (QB.startInteractiveQuizPreview) QB.startInteractiveQuizPreview();
		});
		if (previewBookBtn) previewBookBtn.addEventListener('click', (e) => {
			e.preventDefault();
			const chapter = currentChapter();
			const quizId = chapter && chapter.quiz_id ? Number(chapter.quiz_id) : 0;
			if (QB.startInteractiveFullBookQuizPreview) QB.startInteractiveFullBookQuizPreview(cfg, quizId);
		});

		const hideOverlay = () => { if (previewOverlay) previewOverlay.style.display = 'none'; };
		if (closeBtn) closeBtn.addEventListener('click', hideOverlay);
		if (closeBackdrop) closeBackdrop.addEventListener('click', hideOverlay);
		window.addEventListener('keydown', (e) => { if (e.key === 'Escape') hideOverlay(); });
	}

	function initFlowSettingsUI() {
		const flowMode = $('almaden-flow-mode'), flowIntervalCont = $('almaden-flow-interval-field');
		const flowInterval = $('almaden-flow-interval'), flowMandatory = $('almaden-flow-mandatory');
		const flowPassingScore = $('almaden-flow-passing-score'), saveBtn = $('almaden-save-flow-settings');
		const statusText = $('almaden-flow-settings-status'), flowSettings = QB.flowSettings || {};

		if (!flowMode) return;

		flowMode.value = flowSettings.flow_mode || 'every_chapter';
		if (flowInterval) flowInterval.value = flowSettings.interval_chapters || 3;
		if (flowMandatory) flowMandatory.value = flowSettings.is_mandatory ? '1' : '0';
		if (flowPassingScore) flowPassingScore.value = flowSettings.passing_score || 80;

		const flowQOrder = $('almaden-flow-question-order'), flowAOrder = $('almaden-flow-answer-order');
		if (flowQOrder) flowQOrder.value = flowSettings.question_order || 'ordered';
		if (flowAOrder) flowAOrder.value = flowSettings.answer_order || 'ordered';

		const toggleInterval = () => {
			if (flowIntervalCont) flowIntervalCont.style.display = flowMode.value === 'interval' ? 'block' : 'none';
		};
		flowMode.addEventListener('change', toggleInterval);
		toggleInterval();

		if (saveBtn) {
			saveBtn.addEventListener('click', () => {
				saveBtn.disabled = true;
				if (statusText) {
					statusText.textContent = 'Guardando...';
					statusText.style.color = '#64748b';
				}

				const formData = new FormData();
				formData.append('action', 'almaden_save_quiz_flow_settings');
				formData.append('book_id', cfg.bookId);
				formData.append('flow_mode', flowMode.value);
				formData.append('interval_chapters', flowInterval ? flowInterval.value : 3);
				formData.append('is_mandatory', flowMandatory ? flowMandatory.value : 0);
				formData.append('passing_score', flowPassingScore ? flowPassingScore.value : 80);
				formData.append('question_order', flowQOrder ? flowQOrder.value : 'ordered');
				formData.append('answer_order', flowAOrder ? flowAOrder.value : 'ordered');

				const saveForm = $('almaden-book-quiz-save-form');
				let nonce = '';
				if (saveForm) {
					const input = saveForm.querySelector('input[name="_wpnonce"]');
					if (input) nonce = input.value;
				}
				formData.append('nonce', nonce);

				fetch(cfg.homeUrl + 'wp-admin/admin-ajax.php', {
					method: 'POST',
					body: formData
				})
				.then(res => res.json())
				.then(res => {
					saveBtn.disabled = false;
					if (res.success) {
						QB.flowSettings = res.data.settings;
						if (statusText) {
							statusText.textContent = '✓ Guardado';
							statusText.style.color = '#16a34a';
							setTimeout(() => { statusText.textContent = ''; }, 3000);
						}
					} else {
						if (statusText) {
							statusText.textContent = '❌ ' + (res.data || 'Error');
							statusText.style.color = '#dc2626';
						}
					}
				})
				.catch(() => {
					saveBtn.disabled = false;
					if (statusText) {
						statusText.textContent = '❌ Error de red';
						statusText.style.color = '#dc2626';
					}
				});
			});
		}
	}

	if (loadedQuiz && QB.normalizeQuizPayload) {
		loadedQuiz = QB.normalizeQuizPayload(loadedQuiz) || loadedQuiz;
	}
	bindChapterEvents();
	bindPreviewEvents();
	bindGlobalEvents();
	initFlowSettingsUI();
	updateChapterList();
	updateChapterView();
	setActiveTab(activeTab);
	if (QB.renderPreview) QB.renderPreview();
})();
