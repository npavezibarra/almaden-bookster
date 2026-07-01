(function () {
	const QB = window.ALMADEN_QUIZ_BUILDER = window.ALMADEN_QUIZ_BUILDER || {};

	function normalizeQuizPayload(payload) {
		if (!payload || typeof payload !== 'object') return null;
		const bookTitle = QB.getBookTitle ? QB.getBookTitle() : '';
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
		const bookTitle = QB.getBookTitle ? QB.getBookTitle() : '';
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
			
			// Fallback: try the other start character if matched brace extraction failed
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
			console.log('Almaden Quiz Parser: recovered ' + recoveredQuestions.length + ' questions from malformed/truncated JSON.');
			return {
				quiz_title: bookTitle || 'Quiz',
				questions: recoveredQuestions
			};
		}
		
		return null;
	}

	QB.normalizeQuizPayload = normalizeQuizPayload;
	QB.extractJsonFromRawText = extractJsonFromRawText;
})();
