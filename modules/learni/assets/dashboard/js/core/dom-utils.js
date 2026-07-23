window.AlmadenLearni = window.AlmadenLearni || {};

(function (ns) {
	'use strict';

	function q(root, selector) {
		return root.querySelector(selector);
	}

	function qa(root, selector) {
		return Array.prototype.slice.call(root.querySelectorAll(selector));
	}

	function copyToClipboard(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}

		var temp = document.createElement('textarea');
		temp.value = text;
		temp.setAttribute('readonly', 'readonly');
		temp.style.position = 'fixed';
		temp.style.left = '-9999px';
		document.body.appendChild(temp);
		temp.select();
		document.execCommand('copy');
		document.body.removeChild(temp);
		return Promise.resolve();
	}

	ns.q = q;
	ns.qa = qa;
	ns.copyToClipboard = copyToClipboard;
})(window.AlmadenLearni);
