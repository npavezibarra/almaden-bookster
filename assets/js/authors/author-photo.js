document.addEventListener('DOMContentLoaded', function () {
	const photoEditBtn = document.getElementById('almaden-author-photo-edit-btn');
	const photoModal = document.getElementById('almaden-author-photo-modal');
	const photoModalBackdrop = document.getElementById('almaden-author-photo-modal-backdrop');
	const photoModalCloseBtn = document.getElementById('almaden-author-photo-modal-close');
	const photoModalCancelBtn = document.getElementById('almaden-author-photo-cancel-btn');
	const photoForm = document.getElementById('almaden-author-photo-form');
	const photoUploadEndpoint = photoForm ? (photoForm.dataset.uploadEndpoint || photoForm.getAttribute('action') || '') : '';
	const photoFileInput = document.getElementById('almaden-author-photo-file');
	const photoChooseBtn = document.getElementById('almaden-author-photo-choose-btn');
	const photoClearBtn = document.getElementById('almaden-author-photo-clear-btn');
	const photoDropzone = document.getElementById('almaden-author-photo-dropzone');
	const photoDropzoneEmpty = document.getElementById('almaden-author-photo-dropzone-empty');
	const photoDropzonePreview = document.getElementById('almaden-author-photo-dropzone-preview');
	const photoCanvas = document.getElementById('almaden-author-photo-crop-canvas');
	const photoZoomInput = document.getElementById('almaden-author-photo-zoom');
	const photoCard = document.getElementById('almaden-author-photo-card');
	const currentPhotoUrl = photoCard && photoCard.dataset ? (photoCard.dataset.currentPhotoUrl || '') : '';

	if (!(photoModal && photoForm && photoFileInput && photoCanvas)) {
		return;
	}

	const ctx = photoCanvas.getContext('2d');
	const outputSize = 1200;
	photoCanvas.width = outputSize;
	photoCanvas.height = outputSize;

	const state = {
		image: null,
		imageUrl: '',
		zoom: 1,
		offsetX: 0,
		offsetY: 0,
		dragging: false,
		dragStartX: 0,
		dragStartY: 0,
		offsetStartX: 0,
		offsetStartY: 0,
	};

	function clamp(value, min, max) {
		return Math.min(Math.max(value, min), max);
	}

	function showEmptyState() {
		if (photoDropzoneEmpty) {
			photoDropzoneEmpty.classList.remove('hidden');
		}
		if (photoDropzonePreview) {
			photoDropzonePreview.classList.add('hidden');
		}
		if (photoDropzone) {
			photoDropzone.classList.remove('is-dragover');
		}
	}

	function showPreviewState() {
		if (photoDropzoneEmpty) {
			photoDropzoneEmpty.classList.add('hidden');
		}
		if (photoDropzonePreview) {
			photoDropzonePreview.classList.remove('hidden');
		}
	}

	function resetCropState() {
		state.zoom = 1;
		state.offsetX = 0;
		state.offsetY = 0;
		if (photoZoomInput) {
			photoZoomInput.value = '1';
		}
	}

	function drawCrop() {
		if (!state.image) {
			ctx.clearRect(0, 0, outputSize, outputSize);
			return;
		}

		const image = state.image;
		const baseScale = Math.max(outputSize / image.naturalWidth, outputSize / image.naturalHeight);
		const scale = baseScale * state.zoom;
		const drawWidth = image.naturalWidth * scale;
		const drawHeight = image.naturalHeight * scale;
		const centeredX = (outputSize - drawWidth) / 2;
		const centeredY = (outputSize - drawHeight) / 2;
		const minX = outputSize - drawWidth;
		const minY = outputSize - drawHeight;
		const maxX = 0;
		const maxY = 0;
		const x = clamp(centeredX + state.offsetX, minX, maxX);
		const y = clamp(centeredY + state.offsetY, minY, maxY);

		state.offsetX = x - centeredX;
		state.offsetY = y - centeredY;

		ctx.clearRect(0, 0, outputSize, outputSize);
		ctx.fillStyle = '#f8fafc';
		ctx.fillRect(0, 0, outputSize, outputSize);
		ctx.drawImage(image, x, y, drawWidth, drawHeight);
	}

	function loadImageFromUrl(url) {
		if (!url) {
			return;
		}

		const image = new Image();
		image.onload = function () {
			state.image = image;
			state.imageUrl = url;
			resetCropState();
			showPreviewState();
			drawCrop();
		};
		image.src = url;
	}

	function loadFile(file) {
		if (!file) {
			state.image = null;
			state.imageUrl = '';
			resetCropState();
			showEmptyState();
			drawCrop();
			return;
		}

		const reader = new FileReader();
		reader.onload = function (event) {
			const image = new Image();
			image.onload = function () {
				state.image = image;
				state.imageUrl = event.target.result;
				resetCropState();
				showPreviewState();
				drawCrop();
			};
			image.src = event.target.result;
		};
		reader.readAsDataURL(file);
	}

	function openModal() {
		photoModal.hidden = false;
		document.body.style.overflow = 'hidden';
		if (currentPhotoUrl && !state.imageUrl) {
			loadImageFromUrl(currentPhotoUrl);
		} else if (state.image) {
			showPreviewState();
			drawCrop();
		} else {
			showEmptyState();
		}
	}

	function closeModal() {
		photoModal.hidden = true;
		document.body.style.overflow = '';
		if (photoDropzone) {
			photoDropzone.classList.remove('is-dragover');
		}
		photoFileInput.value = '';
		state.image = null;
		state.imageUrl = '';
		resetCropState();
		if (currentPhotoUrl) {
			loadImageFromUrl(currentPhotoUrl);
		} else {
			showEmptyState();
			drawCrop();
		}
	}

	function clearSelection() {
		photoFileInput.value = '';
		state.image = null;
		state.imageUrl = '';
		resetCropState();
		if (currentPhotoUrl) {
			loadImageFromUrl(currentPhotoUrl);
		} else {
			showEmptyState();
			drawCrop();
		}
	}

	if (photoEditBtn) {
		photoEditBtn.addEventListener('click', openModal);
	}
	if (photoModalBackdrop) {
		photoModalBackdrop.addEventListener('click', closeModal);
	}
	if (photoModalCloseBtn) {
		photoModalCloseBtn.addEventListener('click', closeModal);
	}
	if (photoModalCancelBtn) {
		photoModalCancelBtn.addEventListener('click', closeModal);
	}

	window.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && photoModal && !photoModal.hidden) {
			closeModal();
		}
	});

	if (photoChooseBtn) {
		photoChooseBtn.addEventListener('click', function () {
			photoFileInput.click();
		});
	}

	if (photoClearBtn) {
		photoClearBtn.addEventListener('click', clearSelection);
	}

	if (photoDropzone) {
		photoDropzone.addEventListener('click', function (event) {
			if (event.target.closest('button, input, a, canvas')) {
				return;
			}
			photoFileInput.click();
		});

		photoDropzone.addEventListener('dragover', function (event) {
			event.preventDefault();
			photoDropzone.classList.add('is-dragover');
		});

		photoDropzone.addEventListener('dragleave', function () {
			photoDropzone.classList.remove('is-dragover');
		});

		photoDropzone.addEventListener('drop', function (event) {
			event.preventDefault();
			photoDropzone.classList.remove('is-dragover');
			const files = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files : null;
			if (!files || !files.length) {
				return;
			}
			photoFileInput.files = files;
			loadFile(files[0]);
		});
	}

	photoFileInput.addEventListener('change', function () {
		const file = this.files && this.files.length ? this.files[0] : null;
		loadFile(file);
	});

	if (photoZoomInput) {
		photoZoomInput.addEventListener('input', function () {
			state.zoom = parseFloat(this.value || '1');
			drawCrop();
		});
	}

	photoCanvas.addEventListener('pointerdown', function (event) {
		if (!state.image) {
			return;
		}
		state.dragging = true;
		state.dragStartX = event.clientX;
		state.dragStartY = event.clientY;
		state.offsetStartX = state.offsetX;
		state.offsetStartY = state.offsetY;
		photoCanvas.classList.add('is-dragging');
		photoCanvas.setPointerCapture(event.pointerId);
	});

	window.addEventListener('pointermove', function (event) {
		if (!state.dragging || !state.image) {
			return;
		}

		const rect = photoCanvas.getBoundingClientRect();
		const scaleX = photoCanvas.width / rect.width;
		const scaleY = photoCanvas.height / rect.height;
		const deltaX = (event.clientX - state.dragStartX) * scaleX;
		const deltaY = (event.clientY - state.dragStartY) * scaleY;
		state.offsetX = state.offsetStartX + deltaX;
		state.offsetY = state.offsetStartY + deltaY;
		drawCrop();
	});

	window.addEventListener('pointerup', function () {
		if (!state.dragging) {
			return;
		}
		state.dragging = false;
		photoCanvas.classList.remove('is-dragging');
	});

	photoForm.addEventListener('submit', function (event) {
		event.preventDefault();

		if (!state.image) {
			alert('Debes subir una imagen antes de guardar.');
			return;
		}

		const tempCanvas = document.createElement('canvas');
		tempCanvas.width = outputSize;
		tempCanvas.height = outputSize;
		const tempCtx = tempCanvas.getContext('2d');
		tempCtx.drawImage(photoCanvas, 0, 0);

		tempCanvas.toBlob(function (blob) {
			if (!blob) {
				alert('No pudimos procesar la imagen.');
				return;
			}

			const formData = new FormData(photoForm);
			formData.set('author_profile_photo_file', new File([ blob ], 'author-profile-photo.jpg', { type: 'image/jpeg' }));

			fetch(photoUploadEndpoint, {
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
						throw new Error(text || 'No pudimos guardar la foto.');
					});
				})
				.then(function (payload) {
					if (!payload || !payload.success) {
						throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'No pudimos guardar la foto.');
					}

					window.location.reload();
				})
				.catch(function (error) {
					alert(error.message || 'No pudimos guardar la foto.');
				});
		}, 'image/jpeg', 0.95);
	});
	if (currentPhotoUrl) {
		loadImageFromUrl(currentPhotoUrl);
	}
});
