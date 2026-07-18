(function () {
	const form = document.getElementById('publisher-onboarding-form');
	if (!form) return;

	const storageKey = 'almadenPublisherOnboardingDraft';
	const steps = Array.from(form.querySelectorAll('.almaden-step'));
	const navButtons = Array.from(form.querySelectorAll('[data-step-nav]'));
	const nextButton = form.querySelector('[data-step-next]');
	const backButton = form.querySelector('[data-step-back]');
	const submitButton = form.querySelector('[data-step-submit]');
	const summaryFields = Array.from(form.querySelectorAll('[data-summary-field]'));
	let currentStep = 1;

	function fieldValue(name) {
		const field = form.querySelector('[name="' + name + '"]');
		return field ? (field.value || '') : '';
	}

	function updateSummary() {
		summaryFields.forEach((node) => {
			const name = node.getAttribute('data-summary-field');
			const value = fieldValue(name).trim();
			node.textContent = value || '-';
		});
	}

	function saveDraft() {
		const payload = {};
		form.querySelectorAll('input, textarea').forEach((field) => {
			if (!field.name || field.type === 'password' || field.type === 'file' || field.type === 'hidden') return;
			payload[field.name] = field.value;
		});
		try {
			localStorage.setItem(storageKey, JSON.stringify(payload));
		} catch (error) {}
	}

	function restoreDraft() {
		try {
			const raw = localStorage.getItem(storageKey);
			if (!raw) return;
			const payload = JSON.parse(raw);
			Object.keys(payload).forEach((name) => {
				const field = form.querySelector('[name="' + name + '"]');
				if (field && field.type !== 'file' && field.type !== 'password') {
					field.value = payload[name];
				}
			});
		} catch (error) {}
	}

	function showStep(step) {
		currentStep = Math.min(Math.max(step, 1), steps.length);
		steps.forEach((node) => {
			node.classList.toggle('is-active', Number(node.getAttribute('data-step')) === currentStep);
		});
		navButtons.forEach((button) => {
			button.classList.toggle('is-active', Number(button.getAttribute('data-step-nav')) === currentStep);
		});
		backButton.style.visibility = currentStep === 1 ? 'hidden' : 'visible';
		nextButton.style.display = currentStep === steps.length ? 'none' : 'inline-flex';
		submitButton.style.display = currentStep === steps.length ? 'inline-flex' : 'none';
		updateSummary();
		window.scrollTo({ top: form.offsetTop - 24, behavior: 'smooth' });
	}

	restoreDraft();
	showStep(1);
	updateSummary();

	form.addEventListener('input', function () {
		saveDraft();
		updateSummary();
	});

	navButtons.forEach((button) => {
		button.addEventListener('click', function () {
			showStep(Number(this.getAttribute('data-step-nav')));
		});
	});

	nextButton.addEventListener('click', function () {
		if (!form.reportValidity()) return;
		showStep(currentStep + 1);
	});

	backButton.addEventListener('click', function () {
		showStep(currentStep - 1);
	});

	form.addEventListener('submit', function () {
		try {
			localStorage.removeItem(storageKey);
		} catch (error) {}
	});
})();
