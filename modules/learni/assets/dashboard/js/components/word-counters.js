window.AlmadenLearni = window.AlmadenLearni || {};

(function (ns) {
	'use strict';

	var q = ns.q;
	var qa = ns.qa;

	function countWords(text) {
		return (text || '')
			.trim()
			.split(/\s+/)
			.filter(function (word) {
				return word.length > 0;
			}).length;
	}

	function updateWordCount(field) {
		if (!field || !field.dataset || !field.dataset.wordcountSource) {
			return;
		}

		var target = document.querySelector('[data-wordcount-target="' + field.dataset.wordcountSource + '"]');
		if (!target) {
			return;
		}

		var label = field.getAttribute('aria-label') || field.name || 'Campo';
		var words = countWords(field.value || field.textContent || '');
		target.textContent = words + ' palabras en ' + label.toLowerCase();
	}

	function bindWordCounters(root) {
		qa(root, '[data-wordcount-source]').forEach(function (field) {
			updateWordCount(field);
			field.addEventListener('input', function () {
				updateWordCount(field);
			});
		});
	}

	function bindCertificateWordCounter(root) {
		qa(root, '[data-almaden-certificate-wordcount-source]').forEach(function (field) {
			var target = q(root, '[data-almaden-certificate-wordcount-target="' + field.getAttribute('data-almaden-certificate-wordcount-source') + '"]');
			if (!target) {
				return;
			}

			function update() {
				var words = countWords(field.value || '');
				target.textContent = words + ' / 50 palabras';
			}

			update();
			field.addEventListener('input', update);
		});
	}

	ns.bindWordCounters = bindWordCounters;
	ns.bindCertificateWordCounter = bindCertificateWordCounter;
})(window.AlmadenLearni);
