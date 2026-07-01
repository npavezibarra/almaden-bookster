(function () {
	const cfg = window.ALMADEN_QUIZ_BUILDER_DATA || {};
	const chapters = Array.isArray(cfg.chapters) ? cfg.chapters : [];
	const bookTitle = String(cfg.bookTitle || '');
	let activeChapterIndex = Number.isFinite(Number(cfg.initialActiveChapterIndex)) ? Number(cfg.initialActiveChapterIndex) : 0;
	let loadedQuiz = clone(cfg.initialQuizData);
	let activePreviewQuestionIndex = 0;
	let activeTab = 'prompt-settings';
	const $ = (id) => document.getElementById(id);
	const chapterList = $('almaden-chapter-list');
	const activeTitle = $('almaden-active-chapter-title');
	const activeCaption = $('almaden-active-chapter-caption');
	const chapterRaw = $('almaden-chapter-raw');
	const promptInput = $('almaden-prompt-input');
	const loadPromptBtn = $('almaden-load-prompt');
	const previewEmpty = $('almaden-preview-empty');
	const previewList = $('almaden-preview-list');
	const previewSummary = $('almaden-preview-summary');
	const previewFocus = $('almaden-preview-focus');
	const copyActivePromptBtn = $('almaden-copy-active-prompt');
	const saveQuizBtn = $('almaden-save-quiz');
	const saveQuizForm = $('almaden-book-quiz-save-form');
	const saveQuizPayloadField = $('almaden-quiz-payload-json');
	const quizIdField = $('almaden-quiz-id');
	const activeQuizChip = $('almaden-active-quiz-chip');
	const questionCountField = $('almaden-setting-question-count');
	const alternativesCountField = $('almaden-setting-alternatives-count');
	const difficultyField = $('almaden-setting-difficulty');
	const styleField = $('almaden-setting-style');
	const tabButtons = Array.from(document.querySelectorAll('[data-tab-target]'));
	const tabPanels = Array.from(document.querySelectorAll('[data-tab-panel]'));
	function clone(value) {
		if (!value || typeof value !== 'object') return null;
		try { return JSON.parse(JSON.stringify(value)); } catch (e) { return null; }
	}
	function currentChapter() {
		return chapters[activeChapterIndex] || null;
	}
	function getPromptSettings() {
		const q = questionCountField ? parseInt(questionCountField.value, 10) : 5;
		const a = alternativesCountField ? parseInt(alternativesCountField.value, 10) : 4;
		return {
			questionCount: Number.isFinite(q) && q > 0 ? q : 5,
			alternativesCount: Number.isFinite(a) && a > 0 ? a : 4,
			difficulty: difficultyField ? String(difficultyField.value || 'medium') : 'medium',
			style: styleField ? String(styleField.value || 'clear') : 'clear'
		};
	}
	function esc(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}
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
		if (activeCaption) activeCaption.textContent = 'Chapter ' + (chapter.order || activeChapterIndex + 1) + ' · ' + (chapter.key || '');
		if (chapterRaw) chapterRaw.textContent = chapter.content || 'Este capítulo no tiene contenido.';
		if (quizIdField) quizIdField.value = chapter.quiz_id ? String(chapter.quiz_id) : '0';
		if (activeQuizChip) activeQuizChip.textContent = 'Quiz ID ' + (chapter.quiz_id ? String(chapter.quiz_id) : '0');
	}
	function activateChapter(index) {
		if (!chapters[index]) return;
		activeChapterIndex = index;
		const chapter = currentChapter();
		loadedQuiz = chapter && chapter.quiz_data ? clone(chapter.quiz_data) : null;
		activePreviewQuestionIndex = 0;
		updateChapterList();
		updateChapterView();
		renderPreview();
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
			'- Número de preguntas: ' + settings.questionCount,
			'- Alternativas por pregunta: ' + settings.alternativesCount,
			'- Dificultad: ' + settings.difficulty,
			'- Estilo: ' + settings.style,
			'- El JSON final debe seguir el formato de Learni/Bookster.',
			'- No incluyas texto fuera del JSON final.',
			'- La salida debe ser un objeto JSON único y completo.',
			'',
			'Contenido del capítulo:',
			chapter.content || '',
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
	QB.getLoadedQuiz = () => loadedQuiz;
	QB.setLoadedQuiz = (value) => { loadedQuiz = value; };
	QB.getActivePreviewQuestionIndex = () => activePreviewQuestionIndex;
	QB.setActivePreviewQuestionIndex = (value) => { activePreviewQuestionIndex = Number.isFinite(Number(value)) ? Number(value) : 0; };
	QB.getBookTitle = () => bookTitle;
	QB.esc = esc;
	function createBlankQuestion(index) {
		return { title: 'Question ' + (index + 1), question_text: '', answers: [{ text: 'Answer 1', correct: true }, { text: 'Answer 2', correct: false }] };
	}
	function normalizeQuizPayload(payload) {
		if (!payload || typeof payload !== 'object') return null;
		const sourceQuiz = payload.quiz && typeof payload.quiz === 'object' ? payload.quiz : payload;
		const sourceSettings = sourceQuiz.settings && typeof sourceQuiz.settings === 'object' ? sourceQuiz.settings : (payload.settings && typeof payload.settings === 'object' ? payload.settings : {});
		const rawQuestions = Array.isArray(sourceQuiz.questions) ? sourceQuiz.questions : (Array.isArray(payload.questions) ? payload.questions : []);
		if (!rawQuestions.length) return null;
		const questions = rawQuestions.filter(Boolean).map((question, index) => {
			const rawAnswers = Array.isArray(question.answers) ? question.answers : (Array.isArray(question.options) ? question.options : []);
			const answers = rawAnswers.filter(Boolean).map((answer, answerIndex) => ({
				text: String(answer.text || answer.answer_text || answer.label || ''),
				correct: !!(answer.correct || answer.is_correct || answer.isCorrect),
				sort_order: answerIndex
			})).filter((answer) => answer.text.trim() !== '');
			return {
				title: String(question.title || question.question_title || 'Question ' + (index + 1)),
				question_text: String(question.question_text || question.prompt || question.text || ''),
				chapter_key: String(question.chapter_key || sourceQuiz.chapter_key || ''),
				chapter_id: Number.isFinite(Number(question.chapter_id)) ? Number(question.chapter_id) : (Number.isFinite(Number(sourceQuiz.chapter_id)) ? Number(sourceQuiz.chapter_id) : 0),
				chapter_title: String(question.chapter_title || sourceQuiz.chapter_title || ''),
				answers: answers.length ? answers : [{text: 'Answer 1', correct: true, sort_order: 0}, {text: 'Answer 2', correct: false, sort_order: 1}]
			};
		});
		return {
			quiz_title: String(payload.quiz_title || payload.title || sourceQuiz.title || bookTitle || 'Quiz'),
			scope: String(payload.scope || sourceQuiz.scope || sourceSettings.scope || 'chapter'),
			book_title: String(payload.book_title || sourceQuiz.book_title || bookTitle || ''),
			chapter_title: String(payload.chapter_title || sourceQuiz.chapter_title || ''),
			chapter_key: String(payload.chapter_key || sourceQuiz.chapter_key || ''),
			settings: {
				passing_score: Number.isFinite(Number(sourceSettings.passing_score)) ? Number(sourceSettings.passing_score) : 80,
				time_limit_seconds: Number.isFinite(Number(sourceSettings.time_limit_seconds)) ? Number(sourceSettings.time_limit_seconds) : 0,
				question_order: String(sourceSettings.question_order || 'in_order'),
				shuffle_answers: sourceSettings.shuffle_answers ? 1 : 0,
				show_points: sourceSettings.show_points ? 1 : 0,
				run_once: sourceSettings.run_once ? 1 : 0,
				force_solve: sourceSettings.force_solve ? 1 : 0,
				restart_cooldown_days: Number.isFinite(Number(sourceSettings.restart_cooldown_days)) ? Number(sourceSettings.restart_cooldown_days) : 0
			},
			questions: questions
		};
	}
	function extractJsonFromRawText(raw) {
		const text = String(raw || '').replace(/^\uFEFF/, '').replace(/[“”]/g, '"').replace(/[‘’]/g, "'").replace(/\u00A0/g, ' ').trim();
		if (!text) return null;
		const fenced = text.match(/```(?:json)?\s*([\s\S]*?)\s*```/i);
		const tryParse = (input) => {
			try { return JSON.parse(input); } catch (e) { return null; }
		};
		if (fenced && fenced[1]) {
			const parsed = tryParse(fenced[1].trim());
			if (parsed) return parsed;
		}
		const direct = tryParse(text);
		if (direct) return direct;
		const first = text.indexOf('{');
		if (first < 0) return null;
		let depth = 0, inString = false, escaped = false;
		for (let i = first; i < text.length; i++) {
			const ch = text[i];
			if (escaped) { escaped = false; continue; }
			if (ch === '\\') { escaped = true; continue; }
			if (ch === '"') { inString = !inString; continue; }
			if (inString) continue;
			if (ch === '{') depth++;
			if (ch === '}') {
				depth--;
				if (depth === 0) return tryParse(text.slice(first, i + 1));
			}
		}
		return null;
	}
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
			loadedQuiz = { quiz_title: bookTitle, scope: 'chapter', settings: getPromptSettings(), questions: [createBlankQuestion(0)] };
		}
		ensureLoadedQuiz();
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
		const parsed = extractJsonFromRawText(raw);
		if (!parsed) return window.alert('El contenido pegado no es JSON válido.');
		const normalized = normalizeQuizPayload(parsed);
		if (!normalized) return window.alert('El JSON no contiene una estructura de quiz válida.');
		loadedQuiz = normalized;
		activePreviewQuestionIndex = 0;
		renderPreview();
		setActiveTab('quiz-preview');
	}
	function bindChapterEvents() {
		if (!chapterList) return;
		chapterList.addEventListener('click', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) return;
			const copyButton = target.closest('[data-copy-chapter-index]');
			if (copyButton) {
				event.stopPropagation();
				const index = Number(copyButton.getAttribute('data-copy-chapter-index'));
				const chapter = chapters[index];
				if (chapter) copyText(chapterPrompt(chapter), copyButton);
				return;
			}
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
			if (goTo) return setActiveQuestion(Number(goTo.getAttribute('data-preview-go-to')));
			const actionNode = target.closest('[data-preview-action]');
			if (!actionNode) return;
			const action = actionNode.getAttribute('data-preview-action');
			const currentIndex = activePreviewQuestionIndex;
			if (action === 'prev') return setActiveQuestion(currentIndex - 1);
			if (action === 'next') return setActiveQuestion(currentIndex + 1);
			if (action === 'add-question') return addQuestion();
			if (action === 'remove-question') return removeQuestion(currentIndex);
			if (action === 'duplicate-question') return duplicateQuestion(currentIndex);
			if (action === 'add-answer') return addAnswer(currentIndex);
			if (action === 'remove-answer') return removeAnswer(currentIndex, Number(actionNode.getAttribute('data-preview-answer-index')));
		});
		previewList.addEventListener('input', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) return;
			const q = getActiveQuestion();
			if (!q) return;
			const titleField = target.closest('[data-preview-field="question-title"]');
			if (titleField instanceof HTMLInputElement) {
				q.title = titleField.value;
				renderPreview();
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
			if (answerCorrectField instanceof HTMLInputElement) {
				const answerIndex = Number(answerCorrectField.getAttribute('data-preview-answer-correct'));
				setAnswerCorrect(activePreviewQuestionIndex, answerIndex, answerCorrectField.checked);
			}
		});
	}
	function bindGlobalEvents() {
		tabButtons.forEach((button) => button.addEventListener('click', () => setActiveTab(button.getAttribute('data-tab-target') || 'prompt-settings')));
		if (copyActivePromptBtn) copyActivePromptBtn.addEventListener('click', () => {
			const chapter = currentChapter();
			if (chapter) copyText(chapterPrompt(chapter), copyActivePromptBtn);
		});
		if (loadPromptBtn) loadPromptBtn.addEventListener('click', loadPromptPayload);
		if (previewFocus) previewFocus.addEventListener('click', () => setActiveTab('quiz-preview'));
		if (saveQuizBtn) saveQuizBtn.addEventListener('click', saveQuiz);
	}
	if (loadedQuiz) {
		loadedQuiz = normalizeQuizPayload(loadedQuiz) || loadedQuiz;
	}
	bindChapterEvents();
	bindPreviewEvents();
	bindGlobalEvents();
	updateChapterList();
	updateChapterView();
	setActiveTab(activeTab);
	renderPreview();
})();
