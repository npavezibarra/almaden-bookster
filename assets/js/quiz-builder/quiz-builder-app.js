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
	const previewQuizBtn = $('almaden-preview-quiz-btn');
	const previewOverlay = $('almaden-quiz-preview-overlay');
	const previewBody = $('almaden-quiz-preview-body');
	const closeBackdrop = $('almaden-quiz-preview-close-backdrop');
	const closeBtn = $('almaden-quiz-preview-close-btn');
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
		if (activeCaption) activeCaption.textContent = 'Chapter ' + (chapter.order || activeChapterIndex + 1);
		if (chapterRaw) chapterRaw.textContent = chapter.content || 'Este capítulo no tiene contenido.';
		if (quizIdField) quizIdField.value = chapter.quiz_id ? String(chapter.quiz_id) : '0';
		if (previewQuizBtn) {
			if (chapter.quiz_id && Number(chapter.quiz_id) > 0) {
				previewQuizBtn.classList.remove('is-disabled');
				previewQuizBtn.removeAttribute('title');
			} else {
				previewQuizBtn.classList.add('is-disabled');
				previewQuizBtn.setAttribute('title', 'Por favor, guarda el quiz primero para poder previsualizarlo.');
			}
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
		const rawQuestions = Array.isArray(payload) ? payload : (Array.isArray(sourceQuiz.questions) ? sourceQuiz.questions : (Array.isArray(payload.questions) ? payload.questions : []));
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
		
		// Find first occurrence of '{' or '['
		const firstCurly = text.indexOf('{');
		const firstSquare = text.indexOf('[');
		
		if (firstCurly >= 0 || firstSquare >= 0) {
			let first = -1;
			let startChar = '';
			let endChar = '';
			
			if (firstCurly >= 0 && (firstSquare < 0 || firstCurly < firstSquare)) {
				first = firstCurly;
				startChar = '{';
				endChar = '}';
			} else {
				first = firstSquare;
				startChar = '[';
				endChar = ']';
			}
			
			let depth = 0;
			let inString = false;
			let escaped = false;
			for (let i = first; i < text.length; i++) {
				const ch = text[i];
				if (escaped) { escaped = false; continue; }
				if (ch === '\\') { escaped = true; continue; }
				if (ch === '"') { inString = !inString; continue; }
				if (inString) continue;
				
				if (ch === startChar) {
					depth++;
				} else if (ch === endChar) {
					depth--;
					if (depth === 0) {
						const parsed = tryParse(text.slice(first, i + 1));
						if (parsed) return parsed;
					}
				}
			}
			
			// Fallback: try the other start character if standard matched brace extraction failed
			if (firstCurly >= 0 && firstSquare >= 0) {
				const secondFirst = (startChar === '{') ? firstSquare : firstCurly;
				const secStartChar = (startChar === '{') ? '[' : '{';
				const secEndChar = (startChar === '{') ? ']' : '}';
				
				depth = 0;
				inString = false;
				escaped = false;
				for (let i = secondFirst; i < text.length; i++) {
					const ch = text[i];
					if (escaped) { escaped = false; continue; }
					if (ch === '\\') { escaped = true; continue; }
					if (ch === '"') { inString = !inString; continue; }
					if (inString) continue;
					
					if (ch === secStartChar) {
						depth++;
					} else if (ch === secEndChar) {
						depth--;
						if (depth === 0) {
							const parsed = tryParse(text.slice(secondFirst, i + 1));
							if (parsed) return parsed;
						}
					}
				}
			}
		}
		
		// Question Recovery Parser (Handles copy-paste truncations and malformed wrapping)
		const recoveredQuestions = [];
		let pos = 0;
		while (true) {
			const startCurly = text.indexOf('{', pos);
			if (startCurly < 0) break;
			
			let depth = 0;
			let inString = false;
			let escaped = false;
			let matchedEnd = -1;
			
			for (let i = startCurly; i < text.length; i++) {
				const ch = text[i];
				if (escaped) { escaped = false; continue; }
				if (ch === '\\') { escaped = true; continue; }
				if (ch === '"') { inString = !inString; continue; }
				if (inString) continue;
				
				if (ch === '{') {
					depth++;
				} else if (ch === '}') {
					depth--;
					if (depth === 0) {
						matchedEnd = i;
						break;
					}
				}
			}
			
			if (matchedEnd >= 0) {
				const sliceStr = text.slice(startCurly, matchedEnd + 1);
				const parsedObj = tryParse(sliceStr);
				if (parsedObj && typeof parsedObj === 'object') {
					const hasText = !!(parsedObj.question_text || parsedObj.prompt || parsedObj.text);
					const hasAnswers = Array.isArray(parsedObj.answers) || Array.isArray(parsedObj.options);
					if (hasText || hasAnswers) {
						recoveredQuestions.push(parsedObj);
						pos = matchedEnd + 1;
						continue;
					}
				}
			}
			pos = startCurly + 1;
		}
		
		if (recoveredQuestions.length > 0) {
			console.log('Almaden Quiz Builder: recovered ' + recoveredQuestions.length + ' questions from malformed/truncated JSON.');
			return {
				quiz_title: bookTitle || 'Quiz',
				questions: recoveredQuestions
			};
		}
		
		return null;
	}
	function ensureLoadedQuiz() {
		if (!loadedQuiz) return null;
		if (!Array.isArray(loadedQuiz.questions) || loadedQuiz.questions.length === 0) {
			loadedQuiz.questions = [createBlankQuestion(0)];
		}
		loadedQuiz.questions = loadedQuiz.questions.map((question, index) => {
			const safeQuestion = question && typeof question === 'object' ? question : {};
			const rawAnswers = Array.isArray(safeQuestion.answers) ? safeQuestion.answers : [];
			let answers = rawAnswers
				.filter((answer) => answer && typeof answer === 'object')
				.map((answer, answerIndex) => ({
					text: String(answer.text || ''),
					correct: !!answer.correct,
					sort_order: answerIndex
				}))
				.filter((answer) => answer.text.trim() !== '');

			if (answers.length === 0) {
				answers = [
					{ text: 'Answer 1', correct: true },
					{ text: 'Answer 2', correct: false }
				];
			}
			if (!answers.some((answer) => answer.correct)) {
				answers[0].correct = true;
			}
			return {
				title: String(safeQuestion.title || 'Question ' + (index + 1)),
				question_text: String(safeQuestion.question_text || ''),
				answers: answers
			};
		});

		if (activePreviewQuestionIndex >= loadedQuiz.questions.length) {
			activePreviewQuestionIndex = loadedQuiz.questions.length - 1;
		}
		if (activePreviewQuestionIndex < 0) {
			activePreviewQuestionIndex = 0;
		}
		return loadedQuiz;
	}

	function getActiveQuestion() {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || loadedQuiz.questions.length === 0) {
			return null;
		}
		return loadedQuiz.questions[activePreviewQuestionIndex] || loadedQuiz.questions[0] || null;
	}

	function setActiveQuestion(index) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || loadedQuiz.questions.length === 0) {
			return;
		}
		const nextIndex = Math.max(0, Math.min(index, loadedQuiz.questions.length - 1));
		activePreviewQuestionIndex = nextIndex;
		renderPreview();
	}

	function addQuestion() {
		if (!ensureLoadedQuiz()) return;
		loadedQuiz.questions.push(createBlankQuestion(loadedQuiz.questions.length));
		activePreviewQuestionIndex = loadedQuiz.questions.length - 1;
		renderPreview();
	}

	function removeQuestion(index) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions)) return;
		if (loadedQuiz.questions.length <= 1) {
			loadedQuiz.questions = [createBlankQuestion(0)];
			activePreviewQuestionIndex = 0;
			renderPreview();
			return;
		}
		const nextIndex = Math.max(0, Math.min(index, loadedQuiz.questions.length - 1));
		loadedQuiz.questions.splice(nextIndex, 1);
		activePreviewQuestionIndex = Math.min(nextIndex, loadedQuiz.questions.length - 1);
		renderPreview();
	}

	function duplicateQuestion(index) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || !loadedQuiz.questions[index]) return;
		const source = loadedQuiz.questions[index];
		const cloneQuestion = {
			title: String(source.title || 'Question ' + (loadedQuiz.questions.length + 1)),
			question_text: String(source.question_text || ''),
			answers: Array.isArray(source.answers)
				? source.answers.map((answer, answerIndex) => ({
					text: String(answer.text || ''),
					correct: !!answer.correct,
					sort_order: answerIndex
				}))
				: [{ text: 'Answer 1', correct: true }, { text: 'Answer 2', correct: false }]
		};
		loadedQuiz.questions.splice(index + 1, 0, cloneQuestion);
		activePreviewQuestionIndex = index + 1;
		renderPreview();
	}

	function addAnswer(questionIndex) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || !loadedQuiz.questions[questionIndex]) return;
		const question = loadedQuiz.questions[questionIndex];
		question.answers = Array.isArray(question.answers) ? question.answers : [];
		question.answers.push({ text: 'Answer ' + (question.answers.length + 1), correct: false });
		renderPreview();
	}

	function removeAnswer(questionIndex, answerIndex) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || !loadedQuiz.questions[questionIndex]) return;
		const question = loadedQuiz.questions[questionIndex];
		if (!Array.isArray(question.answers) || question.answers.length <= 2) return;
		question.answers.splice(answerIndex, 1);
		if (!question.answers.some((answer) => answer.correct)) {
			question.answers[0].correct = true;
		}
		renderPreview();
	}

	function setAnswerCorrect(questionIndex, answerIndex, checked) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || !loadedQuiz.questions[questionIndex]) return;
		const question = loadedQuiz.questions[questionIndex];
		if (!Array.isArray(question.answers) || !question.answers[answerIndex]) return;
		if (checked) {
			question.answers.forEach((answer, index) => {
				answer.correct = index === answerIndex;
			});
		} else {
			question.answers[answerIndex].correct = false;
			if (!question.answers.some((answer) => answer.correct) && question.answers[0]) {
				question.answers[0].correct = true;
			}
		}
		renderPreview();
	}

	function escapeHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function renderPreview() {
		if (!previewEmpty || !previewList || !previewSummary) return;
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || loadedQuiz.questions.length === 0) {
			previewEmpty.hidden = false;
			previewList.hidden = true;
			previewList.innerHTML = '';
			previewSummary.textContent = 'No hay un quiz cargado todavía.';
			return;
		}
		ensureLoadedQuiz();
		previewEmpty.hidden = true;
		previewList.hidden = false;
		const activeQuestion = getActiveQuestion();
		const loadedTitle = typeof loadedQuiz.quiz_title === 'string' && loadedQuiz.quiz_title.trim() !== ''
			? loadedQuiz.quiz_title.trim()
			: (typeof loadedQuiz.title === 'string' && loadedQuiz.title.trim() !== '' ? loadedQuiz.title.trim() : 'Quiz cargado');
		previewSummary.textContent = loadedTitle + ' · ' + loadedQuiz.questions.length + ' preguntas';

		const questionCount = loadedQuiz.questions.length;
		const questionIndexDisplay = activePreviewQuestionIndex + 1;
		const answers = activeQuestion && Array.isArray(activeQuestion.answers) ? activeQuestion.answers : [];
		const dotHtml = loadedQuiz.questions.map((question, index) => {
			const hasContent = !!((question && String(question.title || '').trim() !== '') || (question && String(question.question_text || '').trim() !== ''));
			return '<button type="button" class="almaden-slide-dot' + (index === activePreviewQuestionIndex ? ' is-active' : '') + (hasContent ? ' is-filled' : '') + '" data-preview-go-to="' + index + '" aria-label="Go to question ' + (index + 1) + '"></button>';
		}).join('');
		const answerHtml = answers.map((answer, answerIndex) => {
			return [
				'<div class="almaden-answer-row">',
				'  <input type="checkbox" data-preview-answer-correct="' + answerIndex + '"' + (answer.correct ? ' checked' : '') + '>',
				'  <input type="text" data-preview-answer-text="' + answerIndex + '" value="' + escapeHtml(answer.text || '') + '" placeholder="Answer text">',
				'  <button type="button" class="almaden-btn almaden-btn--ghost" data-preview-action="remove-answer" data-preview-answer-index="' + answerIndex + '">Remove</button>',
				'</div>'
			].join('');
		}).join('');

		previewList.innerHTML = [
			'<div class="almaden-preview-shell">',
			'  <div class="almaden-preview-nav">',
			'    <div class="almaden-preview-nav-group">',
			'      <button type="button" class="almaden-btn almaden-btn--ghost" data-preview-action="prev">Previous</button>',
			'      <span class="almaden-slide-indicator">Slide ' + questionIndexDisplay + ' / ' + questionCount + '</span>',
			'      <button type="button" class="almaden-btn almaden-btn--ghost" data-preview-action="next">Next</button>',
			'    </div>',
			'    <div class="almaden-preview-nav-group">',
			'      <button type="button" class="almaden-btn almaden-btn--soft" data-preview-action="add-question">Add slide</button>',
			'      <button type="button" class="almaden-btn almaden-btn--danger" data-preview-action="remove-question">Delete slide</button>',
			'    </div>',
			'  </div>',
			'  <div class="almaden-slide-dots" aria-label="Question navigation">',
			dotHtml,
			'  </div>',
			'  <div class="almaden-preview-card">',
			'    <div class="almaden-slide-head">',
			'      <div>',
			'        <h4>' + escapeHtml(activeQuestion && activeQuestion.title ? activeQuestion.title : ('Question ' + questionIndexDisplay)) + '</h4>',
			'        <p class="almaden-helper">Edita cada slide antes de guardar. Los cambios quedan en memoria para la siguiente fase.</p>',
			'      </div>',
			'      <div class="almaden-slide-actions">',
			'        <button type="button" class="almaden-btn almaden-btn--ghost" data-preview-action="duplicate-question">Duplicate slide</button>',
			'      </div>',
			'    </div>',
			'    <div class="almaden-slide-field">',
			'      <label>Question title</label>',
			'      <input type="text" data-preview-field="question-title" value="' + escapeHtml(activeQuestion && activeQuestion.title ? activeQuestion.title : '') + '">',
			'    </div>',
			'    <div class="almaden-slide-field">',
			'      <label>Question text</label>',
			'      <textarea data-preview-field="question-text">' + escapeHtml(activeQuestion && activeQuestion.question_text ? activeQuestion.question_text : '') + '</textarea>',
			'    </div>',
			'    <div class="almaden-slide-field">',
			'      <div class="almaden-preview-nav">',
			'        <label>Answers</label>',
			'        <button type="button" class="almaden-btn almaden-btn--ghost" data-preview-action="add-answer">Add answer</button>',
			'      </div>',
			'      <div class="almaden-answer-list">',
			answerHtml,
			'      </div>',
			'    </div>',
			'  </div>',
			'</div>'
		].join('');

		const questionCounter = activeQuestion && Array.isArray(activeQuestion.answers) ? activeQuestion.answers.filter((answer) => answer && answer.correct).length : 0;
		if (previewSummary) {
			previewSummary.textContent = loadedTitle + ' · ' + loadedQuiz.questions.length + ' preguntas · ' + questionCounter + ' correctas en esta slide';
		}
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
	let interactiveState = {
		index: -1, // -1: intro, 0..N-1: question, N: results
		questions: [],
		title: '',
		answers: {}
	};

	function startInteractiveQuizPreview() {
		const chapter = currentChapter();
		const chapterTitle = chapter && chapter.title ? String(chapter.title) : '';
		const quizTitle = loadedQuiz && typeof loadedQuiz.quiz_title === 'string' && loadedQuiz.quiz_title.trim() !== ''
			? loadedQuiz.quiz_title.trim()
			: (chapterTitle || bookTitle);

		ensureLoadedQuiz();

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
		if (!previewBody) return;

		const escapeHtml = (s) => String(s || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');

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

			const beginBtn = $('almaden-preview-quiz-begin');
			if (beginBtn) {
				beginBtn.addEventListener('click', () => {
					interactiveState.index = 0;
					renderInteractiveQuizStep();
				});
			}

			const cancelBtn = $('almaden-preview-quiz-cancel');
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

			const closeBtn = $('almaden-preview-quiz-close');
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

		const form = $('almaden-preview-quiz-form');
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

		const backBtn = $('almaden-preview-quiz-back');
		if (backBtn) {
			backBtn.addEventListener('click', () => {
				interactiveState.index = Math.max(0, interactiveState.index - 1);
				renderInteractiveQuizStep();
			});
		}
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
		if (previewQuizBtn) {
			previewQuizBtn.addEventListener('click', (event) => {
				event.preventDefault();
				startInteractiveQuizPreview();
			});
		}
		const hidePreviewOverlay = () => {
			if (previewOverlay) previewOverlay.style.display = 'none';
		};
		if (closeBtn) closeBtn.addEventListener('click', hidePreviewOverlay);
		if (closeBackdrop) closeBackdrop.addEventListener('click', hidePreviewOverlay);
		window.addEventListener('keydown', (event) => {
			if (event.key === 'Escape') {
				hidePreviewOverlay();
			}
		});
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
