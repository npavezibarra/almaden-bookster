document.addEventListener('DOMContentLoaded', function () {
	const heroEditBtn = document.getElementById('almaden-author-hero-edit-btn');
	const heroModal = document.getElementById('almaden-author-hero-modal');
	const heroModalBackdrop = document.getElementById('almaden-author-hero-modal-backdrop');
	const heroModalCloseBtn = document.getElementById('almaden-author-hero-modal-close');
	const heroModalCancelBtn = document.getElementById('almaden-author-hero-cancel-btn');
	const heroForm = document.getElementById('almaden-author-hero-form');
	const heroUploadEndpoint = heroForm ? (heroForm.dataset.uploadEndpoint || heroForm.getAttribute('action') || '') : '';
	const heroFileInput = document.getElementById('almaden-author-hero-file');
	const heroChooseBtn = document.getElementById('almaden-author-hero-choose-btn');
	const heroClearBtn = document.getElementById('almaden-author-hero-clear-btn');
	const heroDropzone = document.getElementById('almaden-author-hero-dropzone');
	const heroDropzoneEmpty = document.getElementById('almaden-author-hero-dropzone-empty');
	const heroModeButtons = Array.from(document.querySelectorAll('#almaden-author-hero-modal .almaden-hero-mode-btn'));
	const heroPanels = Array.from(document.querySelectorAll('#almaden-author-hero-modal .almaden-hero-mode-panel'));
	const heroModeInput = document.getElementById('almaden-author-hero-background-mode');
	const heroImageIdInput = document.getElementById('almaden-author-hero-background-image-id');
	const heroColorInput = document.getElementById('almaden-author-hero-color');
	const heroGradientFromInput = document.getElementById('almaden-author-hero-gradient-from');
	const heroGradientToInput = document.getElementById('almaden-author-hero-gradient-to');
	const heroGradientAngleInput = document.getElementById('almaden-author-hero-gradient-angle');
	const heroOverlayColorInput = document.getElementById('almaden-author-hero-overlay-color-input');
	const heroOverlayOpacityInput = document.getElementById('almaden-author-hero-overlay-opacity-input');
	const heroOverlayColorHidden = document.getElementById('almaden-author-hero-overlay-color');
	const heroOverlayOpacityHidden = document.getElementById('almaden-author-hero-overlay-opacity');
	const heroHeroCard = document.getElementById('almaden-author-hero');
	const heroInitialState = {
		mode: heroHeroCard && heroHeroCard.dataset ? (heroHeroCard.dataset.heroBackgroundType || 'color') : 'color',
		imageId: heroHeroCard && heroHeroCard.dataset ? parseInt(heroHeroCard.dataset.heroBackgroundImageId || '0', 10) || 0 : 0,
		imageUrl: heroHeroCard && heroHeroCard.dataset ? (heroHeroCard.dataset.heroBackgroundImageUrl || '') : '',
		color: heroHeroCard && heroHeroCard.dataset ? (heroHeroCard.dataset.heroBackgroundColor || '#ebff43') : '#ebff43',
		gradientFrom: heroHeroCard && heroHeroCard.dataset ? (heroHeroCard.dataset.heroGradientFrom || '#ebff43') : '#ebff43',
		gradientTo: heroHeroCard && heroHeroCard.dataset ? (heroHeroCard.dataset.heroGradientTo || '#f5f5ef') : '#f5f5ef',
		gradientAngle: heroHeroCard && heroHeroCard.dataset ? parseInt(heroHeroCard.dataset.heroGradientAngle || '90', 10) || 90 : 90,
		overlayColor: heroHeroCard && heroHeroCard.dataset ? (heroHeroCard.dataset.heroOverlayColor || '#000000') : '#000000',
		overlayOpacity: heroHeroCard && heroHeroCard.dataset ? Math.min(Math.max(parseFloat(heroHeroCard.dataset.heroOverlayOpacity || '0') || 0, 0), 1) : 0,
	};
	const heroState = {
		mode: heroInitialState.mode,
		imageId: heroInitialState.imageId,
		imageUrl: heroInitialState.imageUrl,
		color: heroInitialState.color,
		gradientFrom: heroInitialState.gradientFrom,
		gradientTo: heroInitialState.gradientTo,
		gradientAngle: heroInitialState.gradientAngle,
		overlayColor: heroInitialState.overlayColor,
		overlayOpacity: heroInitialState.overlayOpacity,
	};

	function clampHeroAngle(value) {
		return Math.min(Math.max(parseInt(value || '0', 10) || 0, 0), 360);
	}

	function clampHeroOpacity(value) {
		return Math.min(Math.max(parseFloat(value || '0') || 0, 0), 1);
	}

	function hexToRgb(value) {
		const hex = (value || '').replace('#', '');
		if (!hex) {
			return null;
		}

		const normalized = hex.length === 3 ? hex.split('').map(function (char) {
			return char + char;
		}).join('') : hex;

		if (normalized.length !== 6) {
			return;
		}

		const r = parseInt(normalized.slice(0, 2), 16);
		const g = parseInt(normalized.slice(2, 4), 16);
		const b = parseInt(normalized.slice(4, 6), 16);
		if ([r, g, b].some(function (component) { return Number.isNaN(component); })) {
			return null;
		}

		return { r: r, g: g, b: b };
	}

	function syncHeroOverlayInputs() {
		if (heroOverlayColorHidden) {
			heroOverlayColorHidden.value = heroState.overlayColor;
		}
		if (heroOverlayOpacityHidden) {
			heroOverlayOpacityHidden.value = String(heroState.overlayOpacity);
		}
		if (heroOverlayColorInput) {
			heroOverlayColorInput.value = heroState.overlayColor;
		}
		if (heroOverlayOpacityInput) {
			heroOverlayOpacityInput.value = String(heroState.overlayOpacity);
		}
	}

	function renderHeroDropzonePreview() {
		if (!heroDropzone) {
			return;
		}

		heroDropzone.style.backgroundImage = '';
		heroDropzone.style.backgroundColor = '';
		heroDropzone.style.backgroundSize = '';
		heroDropzone.style.backgroundPosition = '';
		heroDropzone.style.backgroundRepeat = '';

		if (heroState.mode === 'image') {
			const imageUrl = heroState.imageUrl;
			if (imageUrl) {
				const rgb = hexToRgb(heroState.overlayColor);
				const overlay = rgb && heroState.overlayOpacity > 0 ? 'linear-gradient(rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + heroState.overlayOpacity + '), rgba(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ', ' + heroState.overlayOpacity + ')), ' : '';
				heroDropzone.style.backgroundImage = overlay + 'url("' + imageUrl + '")';
				heroDropzone.style.backgroundPosition = 'center';
				heroDropzone.style.backgroundRepeat = 'no-repeat';
				heroDropzone.style.backgroundSize = 'cover';
				if (heroDropzoneEmpty) {
					heroDropzoneEmpty.classList.add('hidden');
				}
				heroDropzone.classList.remove('is-empty');
				return;
			}

			heroDropzone.style.backgroundColor = '#f8fafc';
			if (heroDropzoneEmpty) {
				heroDropzoneEmpty.classList.remove('hidden');
			}
			heroDropzone.classList.add('is-empty');
			return;
		}

		if (heroState.mode === 'gradient') {
			heroDropzone.style.backgroundImage = 'linear-gradient(' + clampHeroAngle(heroState.gradientAngle) + 'deg, ' + (heroState.gradientFrom || '#ebff43') + ', ' + (heroState.gradientTo || '#f5f5ef') + ')';
			heroDropzone.style.backgroundPosition = 'center';
			heroDropzone.style.backgroundRepeat = 'no-repeat';
			heroDropzone.style.backgroundSize = 'cover';
			if (heroDropzoneEmpty) {
				heroDropzoneEmpty.classList.add('hidden');
			}
			heroDropzone.classList.remove('is-empty');
			return;
		}

		heroDropzone.style.backgroundColor = heroState.color || '#ebff43';
		if (heroDropzoneEmpty) {
			heroDropzoneEmpty.classList.add('hidden');
		}
		heroDropzone.classList.remove('is-empty');
	}

	function setHeroMode(mode) {
		heroState.mode = mode;
		if (heroModeInput) {
			heroModeInput.value = mode;
		}

		heroModeButtons.forEach(function (button) {
			const isActive = button.dataset.heroMode === mode;
			button.setAttribute('aria-selected', isActive ? 'true' : 'false');
			button.classList.toggle('is-active', isActive);
		});

		heroPanels.forEach(function (panel) {
			panel.hidden = panel.dataset.heroPanel !== mode;
		});

		renderHeroDropzonePreview();
	}

	function resetHeroStateToInitial() {
		heroState.mode = heroInitialState.mode;
		heroState.imageId = heroInitialState.imageId;
		heroState.imageUrl = heroInitialState.imageUrl;
		heroState.color = heroInitialState.color;
		heroState.gradientFrom = heroInitialState.gradientFrom;
		heroState.gradientTo = heroInitialState.gradientTo;
		heroState.gradientAngle = heroInitialState.gradientAngle;
		heroState.overlayColor = heroInitialState.overlayColor;
		heroState.overlayOpacity = heroInitialState.overlayOpacity;

		if (heroModeInput) {
			heroModeInput.value = heroState.mode;
		}
		if (heroImageIdInput) {
			heroImageIdInput.value = String(heroState.imageId || 0);
		}
		if (heroColorInput) {
			heroColorInput.value = heroState.color;
		}
		if (heroGradientFromInput) {
			heroGradientFromInput.value = heroState.gradientFrom;
		}
		if (heroGradientToInput) {
			heroGradientToInput.value = heroState.gradientTo;
		}
		if (heroGradientAngleInput) {
			heroGradientAngleInput.value = String(heroState.gradientAngle);
		}
		syncHeroOverlayInputs();

		setHeroMode(heroState.mode);
		if (heroFileInput) {
			heroFileInput.value = '';
		}
		renderHeroDropzonePreview();
	}

	function loadHeroFile(file) {
		if (!file) {
			renderHeroDropzonePreview();
			return;
		}

		const reader = new FileReader();
		reader.onload = function (event) {
			const result = event.target.result || '';
			heroState.imageId = 0;
			heroState.imageUrl = result;
			if (heroImageIdInput) {
				heroImageIdInput.value = '0';
			}
			renderHeroDropzonePreview();
		};
		reader.readAsDataURL(file);
	}

	function openHeroModal() {
		if (!(heroModal && heroForm && heroFileInput && heroDropzone)) {
			return;
		}

		resetHeroStateToInitial();
		heroModal.hidden = false;
		document.body.style.overflow = 'hidden';
		renderHeroDropzonePreview();
	}

	function closeHeroModal() {
		if (!(heroModal && heroForm)) {
			return;
		}

		heroModal.hidden = true;
		document.body.style.overflow = '';
		if (heroDropzone) {
			heroDropzone.classList.remove('is-dragover');
		}
		resetHeroStateToInitial();
	}

	function clearHeroSelection() {
		if (heroFileInput) {
			heroFileInput.value = '';
		}
		heroState.imageId = 0;
		heroState.imageUrl = '';
		if (heroImageIdInput) {
			heroImageIdInput.value = '0';
		}
		renderHeroDropzonePreview();
	}

	if (heroEditBtn) {
		heroEditBtn.addEventListener('click', openHeroModal);
	}
	if (heroModalBackdrop) {
		heroModalBackdrop.addEventListener('click', closeHeroModal);
	}
	if (heroModalCloseBtn) {
		heroModalCloseBtn.addEventListener('click', closeHeroModal);
	}
	if (heroModalCancelBtn) {
		heroModalCancelBtn.addEventListener('click', closeHeroModal);
	}

	if (heroModeButtons.length) {
		heroModeButtons.forEach(function (button) {
			button.addEventListener('click', function () {
				setHeroMode(this.dataset.heroMode || 'color');
			});
		});
	}

	if (heroChooseBtn && heroFileInput) {
		heroChooseBtn.addEventListener('click', function () {
			heroFileInput.click();
		});
	}

	if (heroClearBtn) {
		heroClearBtn.addEventListener('click', clearHeroSelection);
	}

	if (heroDropzone && heroFileInput) {
		heroDropzone.addEventListener('click', function (event) {
			if (event.target.closest('button, input, a')) {
				return;
			}
			if (heroState.mode === 'image') {
				heroFileInput.click();
			}
		});

		heroDropzone.addEventListener('dragover', function (event) {
			event.preventDefault();
			heroDropzone.classList.add('is-dragover');
		});

		heroDropzone.addEventListener('dragleave', function () {
			heroDropzone.classList.remove('is-dragover');
		});

		heroDropzone.addEventListener('drop', function (event) {
			event.preventDefault();
			heroDropzone.classList.remove('is-dragover');
			const files = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files : null;
			if (!files || !files.length) {
				return;
			}
			heroFileInput.files = files;
			heroState.mode = 'image';
			setHeroMode('image');
			loadHeroFile(files[0]);
		});
	}

	if (heroFileInput) {
		heroFileInput.addEventListener('change', function () {
			const file = this.files && this.files.length ? this.files[0] : null;
			if (file) {
				heroState.mode = 'image';
				setHeroMode('image');
			}
			loadHeroFile(file);
		});
	}

	if (heroColorInput) {
		heroColorInput.addEventListener('input', function () {
			heroState.color = this.value || '#ebff43';
			renderHeroDropzonePreview();
		});
	}

	if (heroGradientFromInput) {
		heroGradientFromInput.addEventListener('input', function () {
			heroState.gradientFrom = this.value || '#ebff43';
			renderHeroDropzonePreview();
		});
	}

	if (heroGradientToInput) {
		heroGradientToInput.addEventListener('input', function () {
			heroState.gradientTo = this.value || '#f5f5ef';
			renderHeroDropzonePreview();
		});
	}

	if (heroGradientAngleInput) {
		heroGradientAngleInput.addEventListener('input', function () {
			heroState.gradientAngle = clampHeroAngle(this.value);
			renderHeroDropzonePreview();
		});
	}

	if (heroOverlayColorInput) {
		heroOverlayColorInput.addEventListener('input', function () {
			heroState.overlayColor = this.value || '#000000';
			syncHeroOverlayInputs();
			renderHeroDropzonePreview();
		});
	}

	if (heroOverlayOpacityInput) {
		heroOverlayOpacityInput.addEventListener('input', function () {
			heroState.overlayOpacity = clampHeroOpacity(this.value);
			syncHeroOverlayInputs();
			renderHeroDropzonePreview();
		});
	}

	window.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			if (heroModal && !heroModal.hidden) {
				closeHeroModal();
				return;
			}
			if (photoModal && !photoModal.hidden) {
				closeModal();
			}
		}
	});

	if (heroForm) {
		heroForm.addEventListener('submit', function (event) {
			event.preventDefault();

			const currentMode = heroModeInput ? (heroModeInput.value || 'color') : 'color';
			if (currentMode === 'image' && heroImageIdInput && heroImageIdInput.value === '0' && !(heroFileInput && heroFileInput.files && heroFileInput.files.length)) {
				alert('Debes subir una imagen para continuar.');
				return;
			}

			syncHeroOverlayInputs();

			const formData = new FormData(heroForm);
			if (currentMode === 'image' && heroFileInput && heroFileInput.files && heroFileInput.files.length) {
				formData.set('author_hero_background_file', heroFileInput.files[0]);
			}

			fetch(heroUploadEndpoint, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			})
				.then(function (response) {
					const contentType = response.headers.get('content-type') || '';
					if (contentType.indexOf('application/json') !== -1) {
						return response.json();
					}

					return response.text().then(function (text) {
						throw new Error(text || 'No pudimos guardar el fondo.');
					});
				})
				.then(function (payload) {
					if (!payload || !payload.success) {
						throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'No pudimos guardar el fondo.');
					}

					window.location.reload();
				})
				.catch(function (error) {
					alert(error.message || 'No pudimos guardar el fondo.');
				});
		});
	}

});
