(function () {
	'use strict';

	function q(root, selector) {
		return root.querySelector(selector);
	}

	function qa(root, selector) {
		return Array.prototype.slice.call(root.querySelectorAll(selector));
	}

	function createAnswer(answer, questionIndex, answerIndex) {
		var row = document.createElement('div');
		row.className = 'almaden-learni-answer';
		row.innerHTML = [
			'<input type="text" class="almaden-learni-answer__text" placeholder="Respuesta">',
			'<label class="almaden-learni-answer__correct">',
			'<input type="checkbox" class="almaden-learni-answer__check">',
			'<span>Correcta</span>',
			'</label>',
			'<button type="button" class="almaden-learni-mini-btn almaden-learni-answer__remove">Eliminar</button>'
		].join('');

		q(row, '.almaden-learni-answer__text').value = answer && answer.text ? answer.text : '';
		q(row, '.almaden-learni-answer__check').checked = !!(answer && answer.correct);

		q(row, '.almaden-learni-answer__remove').addEventListener('click', function () {
			row.remove();
		});

		return row;
	}

	function createQuestion(question, index) {
		var card = document.createElement('article');
		card.className = 'almaden-learni-question';
		card.dataset.questionIndex = String(index);
		card.innerHTML = [
			'<div class="almaden-learni-question__head">',
			'<div>',
			'<p class="almaden-learni-question__eyebrow">Pregunta</p>',
			'<h4 class="almaden-learni-question__title">Pregunta ' + (index + 1) + '</h4>',
			'</div>',
			'<button type="button" class="almaden-learni-mini-btn almaden-learni-question__remove">Eliminar pregunta</button>',
			'</div>',
			'<div class="almaden-learni-question__body">',
			'<input type="text" class="almaden-learni-input almaden-learni-question__name" placeholder="Título de la pregunta">',
			'<textarea class="almaden-learni-textarea almaden-learni-question__text" rows="5" placeholder="Texto de la pregunta"></textarea>',
			'<div class="almaden-learni-answer-list"></div>',
			'<button type="button" class="almaden-learni-mini-btn almaden-learni-question__add-answer">Agregar respuesta</button>',
			'</div>'
		].join('');

		q(card, '.almaden-learni-question__name').value = question && question.title ? question.title : '';
		q(card, '.almaden-learni-question__text').value = question && question.question_text ? question.question_text : '';

		var answersWrap = q(card, '.almaden-learni-answer-list');
		var answers = question && Array.isArray(question.answers) ? question.answers : [];
		if (answers.length === 0) {
			answers = [
				{ text: '', correct: true },
				{ text: '', correct: false }
			];
		}
		answers.forEach(function (answer, answerIndex) {
			answersWrap.appendChild(createAnswer(answer, index, answerIndex));
		});

		q(card, '.almaden-learni-question__add-answer').addEventListener('click', function () {
			answersWrap.appendChild(createAnswer({ text: '', correct: false }, index, answersWrap.children.length));
		});

		q(card, '.almaden-learni-question__remove').addEventListener('click', function () {
			card.remove();
			relabelQuestions();
		});

		return card;
	}

	function relabelQuestions() {
		qa(document, '.almaden-learni-question').forEach(function (card, idx) {
			card.dataset.questionIndex = String(idx);
			var title = q(card, '.almaden-learni-question__title');
			if (title) {
				title.textContent = 'Pregunta ' + (idx + 1);
			}
		});
	}

	function collectQuestions(root) {
		return qa(root, '.almaden-learni-question').map(function (card) {
			var title = q(card, '.almaden-learni-question__name').value.trim();
			var text = q(card, '.almaden-learni-question__text').value.trim();
			var answers = qa(card, '.almaden-learni-answer').map(function (answerCard) {
				return {
					text: q(answerCard, '.almaden-learni-answer__text').value.trim(),
					correct: q(answerCard, '.almaden-learni-answer__check').checked
				};
			}).filter(function (answer) {
				return answer.text !== '';
			});

			return {
				title: title,
				question_text: text,
				answers: answers
			};
		}).filter(function (question) {
			return question.title !== '' || question.question_text !== '' || question.answers.length > 0;
		});
	}

	function initForm(form) {
		var editor = q(form, '[data-quiz-editor]');
		var hidden = q(form, '[data-quiz-json]');
		var addQuestionBtn = q(form, '[data-quiz-add-question]');
		if (!editor || !hidden) {
			return;
		}

		if (editor.dataset.quizQuestions) {
			try {
				var parsed = JSON.parse(editor.dataset.quizQuestions);
				if (Array.isArray(parsed) && parsed.length > 0) {
					editor.innerHTML = '';
					parsed.forEach(function (question, idx) {
						editor.appendChild(createQuestion(question, idx));
					});
				}
			} catch (err) {
				// Ignore malformed JSON and fall back to starter state.
			}
		}

		if (editor.children.length === 0) {
			editor.appendChild(createQuestion({ title: 'Pregunta 1', question_text: '', answers: [{ text: '', correct: true }, { text: '', correct: false }] }, 0));
		}

		addQuestionBtn.addEventListener('click', function () {
			editor.appendChild(createQuestion({ title: '', question_text: '', answers: [{ text: '', correct: true }, { text: '', correct: false }] }, editor.children.length));
			relabelQuestions();
		});

		form.addEventListener('submit', function () {
			hidden.value = JSON.stringify(collectQuestions(editor), null, 2);
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-almaden-quiz-form]').forEach(initForm);
	});
})();
