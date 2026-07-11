(function () {
	const QB = window.ALMADEN_QUIZ_BUILDER = window.ALMADEN_QUIZ_BUILDER || {};

	function createBlankAnswer(index, isCorrect) {
		return {
			text: 'Answer ' + (index + 1),
			correct: !!isCorrect
		};
	}

	function createBlankQuestion(index) {
		return {
			title: 'Question ' + (index + 1),
			question_text: '',
			answers: [
				createBlankAnswer(0, true),
				createBlankAnswer(1, false)
			]
		};
	}

	function ensureLoadedQuiz() {
		let loadedQuiz = QB.getLoadedQuiz ? QB.getLoadedQuiz() : null;
		let activePreviewQuestionIndex = QB.getActivePreviewQuestionIndex ? QB.getActivePreviewQuestionIndex() : 0;
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
					createBlankAnswer(0, true),
					createBlankAnswer(1, false)
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

		if (QB.setActivePreviewQuestionIndex) {
			if (activePreviewQuestionIndex >= loadedQuiz.questions.length) {
				activePreviewQuestionIndex = loadedQuiz.questions.length - 1;
			}
			if (activePreviewQuestionIndex < 0) {
				activePreviewQuestionIndex = 0;
			}
			QB.setActivePreviewQuestionIndex(activePreviewQuestionIndex);
		}
		return loadedQuiz;
	}

	function getActiveQuestion() {
		const loadedQuiz = QB.getLoadedQuiz ? QB.getLoadedQuiz() : null;
		const activePreviewQuestionIndex = QB.getActivePreviewQuestionIndex ? QB.getActivePreviewQuestionIndex() : 0;
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || loadedQuiz.questions.length === 0) {
			return null;
		}
		return loadedQuiz.questions[activePreviewQuestionIndex] || loadedQuiz.questions[0] || null;
	}

	function setActiveQuestion(index) {
		const loadedQuiz = QB.getLoadedQuiz ? QB.getLoadedQuiz() : null;
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || loadedQuiz.questions.length === 0) {
			return;
		}
		const nextIndex = Math.max(0, Math.min(index, loadedQuiz.questions.length - 1));
		if (QB.setActivePreviewQuestionIndex) {
			QB.setActivePreviewQuestionIndex(nextIndex);
		}
		renderPreview();
	}

	function addQuestion() {
		const loadedQuiz = QB.getLoadedQuiz ? QB.getLoadedQuiz() : null;
		if (!ensureLoadedQuiz()) return;
		loadedQuiz.questions.push(createBlankQuestion(loadedQuiz.questions.length));
		if (QB.setActivePreviewQuestionIndex) {
			QB.setActivePreviewQuestionIndex(loadedQuiz.questions.length - 1);
		}
		renderPreview();
	}

	function removeQuestion(index) {
		const loadedQuiz = QB.getLoadedQuiz ? QB.getLoadedQuiz() : null;
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions)) return;
		if (loadedQuiz.questions.length <= 1) {
			loadedQuiz.questions = [createBlankQuestion(0)];
			if (QB.setActivePreviewQuestionIndex) {
				QB.setActivePreviewQuestionIndex(0);
			}
			renderPreview();
			return;
		}
		const nextIndex = Math.max(0, Math.min(index, loadedQuiz.questions.length - 1));
		loadedQuiz.questions.splice(nextIndex, 1);
		if (QB.setActivePreviewQuestionIndex) {
			QB.setActivePreviewQuestionIndex(Math.min(nextIndex, loadedQuiz.questions.length - 1));
		}
		renderPreview();
	}

	function duplicateQuestion(index) {
		const loadedQuiz = QB.getLoadedQuiz ? QB.getLoadedQuiz() : null;
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
				: [createBlankAnswer(0, true), createBlankAnswer(1, false)]
		};
		loadedQuiz.questions.splice(index + 1, 0, cloneQuestion);
		if (QB.setActivePreviewQuestionIndex) {
			QB.setActivePreviewQuestionIndex(index + 1);
		}
		renderPreview();
	}

	function addAnswer(questionIndex) {
		const loadedQuiz = QB.getLoadedQuiz ? QB.getLoadedQuiz() : null;
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || !loadedQuiz.questions[questionIndex]) return;
		const question = loadedQuiz.questions[questionIndex];
		question.answers = Array.isArray(question.answers) ? question.answers : [];
		question.answers.push({ text: 'Answer ' + (question.answers.length + 1), correct: false });
		renderPreview();
	}

	function removeAnswer(questionIndex, answerIndex) {
		const loadedQuiz = QB.getLoadedQuiz ? QB.getLoadedQuiz() : null;
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
		const loadedQuiz = QB.getLoadedQuiz ? QB.getLoadedQuiz() : null;
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

	function renderPreview() {
		const loadedQuiz = QB.getLoadedQuiz ? QB.getLoadedQuiz() : null;
		const activePreviewQuestionIndex = QB.getActivePreviewQuestionIndex ? QB.getActivePreviewQuestionIndex() : 0;
		const getEl = QB.getEl || ((id) => document.getElementById(id));
		const previewEmpty = getEl('almaden-preview-empty');
		const previewList = getEl('almaden-preview-list');
		const previewSummary = getEl('almaden-preview-summary');

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
		const bookTitle = QB.getBookTitle ? QB.getBookTitle() : '';
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
				'  <input type="text" data-preview-answer-text="' + answerIndex + '" value="' + (QB.esc ? QB.esc(answer.text || '') : (answer.text || '')) + '" placeholder="Answer text">',
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
			'        <h4>' + (QB.esc ? QB.esc(activeQuestion && activeQuestion.title ? activeQuestion.title : ('Question ' + questionIndexDisplay)) : (activeQuestion && activeQuestion.title ? activeQuestion.title : ('Question ' + questionIndexDisplay))) + '</h4>',
			'        <p class="almaden-helper">Edita cada slide antes de guardar. Los cambios quedan en memoria para la siguiente fase.</p>',
			'      </div>',
			'      <div class="almaden-slide-actions">',
			'        <button type="button" class="almaden-btn almaden-btn--ghost" data-preview-action="duplicate-question">Duplicate slide</button>',
			'      </div>',
			'    </div>',
			'    <div class="almaden-slide-field">',
			'      <label>Question text</label>',
			'      <textarea data-preview-field="question-text">' + (QB.esc ? QB.esc(activeQuestion && activeQuestion.question_text ? activeQuestion.question_text : '') : (activeQuestion && activeQuestion.question_text ? activeQuestion.question_text : '')) + '</textarea>',
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
		
		if (QB.updateChapterView) {
			QB.updateChapterView();
		}
	}

	QB.createBlankQuestion = createBlankQuestion;
	QB.ensureLoadedQuiz = ensureLoadedQuiz;
	QB.getActiveQuestion = getActiveQuestion;
	QB.setActiveQuestion = setActiveQuestion;
	QB.addQuestion = addQuestion;
	QB.removeQuestion = removeQuestion;
	QB.duplicateQuestion = duplicateQuestion;
	QB.addAnswer = addAnswer;
	QB.removeAnswer = removeAnswer;
	QB.setAnswerCorrect = setAnswerCorrect;
	QB.renderPreview = renderPreview;
})();
