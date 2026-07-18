document.addEventListener('DOMContentLoaded', function () {
	const modal = document.getElementById('author-modal');
	const modalPanel = document.getElementById('author-modal-panel');
	const openBtn = document.getElementById('open-author-modal-btn');
	const closeBtn = document.getElementById('close-author-modal-btn');
	const cancelBtns = document.querySelectorAll('.cancel-author-modal-btn');
	const steps = Array.from(document.querySelectorAll('.wizard-step'));
	const step1Inputs = [document.getElementById('author_name'), document.getElementById('author_email')];
	const photoInput = document.getElementById('author_photo_id');
	const photoFileInput = document.getElementById('author_photo_file');
	const dropzone = document.getElementById('author-photo-dropzone');
	const photoPreview = document.getElementById('author-photo-preview');
	const photoPreviewWrap = document.getElementById('author-photo-preview-wrap');
	const photoEmptyState = document.getElementById('author-photo-empty-state');
	const photoFilename = document.getElementById('author-photo-filename');
	const selectPhotoBtn = document.getElementById('select-author-photo-btn');
	const removePhotoBtn = document.getElementById('remove-author-photo-btn');
	const backcoverInput = document.getElementById('author_backcover_id');
	const backcoverFileInput = document.getElementById('author_backcover_file');
	const backcoverDropzone = document.getElementById('author-backcover-dropzone');
	const backcoverPreview = document.getElementById('author-backcover-preview');
	const backcoverPreviewWrap = document.getElementById('author-backcover-preview-wrap');
	const backcoverEmptyState = document.getElementById('author-backcover-empty-state');
	const backcoverFilename = document.getElementById('author-backcover-filename');
	const selectBackcoverBtn = document.getElementById('select-author-backcover-btn');
	const removeBackcoverBtn = document.getElementById('remove-author-backcover-btn');
	const form = document.getElementById('create-author-form');

	function createUploadField(config) {
		const fileInput = config.fileInput;
		const dropzone = config.dropzone;
		const preview = config.preview;
		const previewWrap = config.previewWrap;
		const emptyState = config.emptyState;
		const filename = config.filename;
		const selectBtn = config.selectBtn;
		const removeBtn = config.removeBtn;
		const selectedIdInput = config.selectedIdInput;

		function clearPreview() {
			if (preview) {
				preview.src = '';
			}
			if (previewWrap) {
				previewWrap.classList.add('hidden');
			}
			if (emptyState) {
				emptyState.classList.remove('hidden');
			}
			if (filename) {
				filename.textContent = '';
			}
			if (fileInput) {
				fileInput.value = '';
			}
		}

		function clearSelectedFile() {
			if (fileInput) {
				fileInput.value = '';
			}
			if (selectedIdInput) {
				selectedIdInput.value = '0';
			}
		}

		function setPreviewFromFile(file) {
			if (!file || !preview || !previewWrap || !emptyState || !filename) {
				clearPreview();
				return;
			}

			const reader = new FileReader();
			reader.onload = function (event) {
				preview.src = event.target.result;
				previewWrap.classList.remove('hidden');
				emptyState.classList.add('hidden');
				filename.textContent = file.name;
			};
			reader.readAsDataURL(file);
		}

		if (selectBtn && fileInput) {
			selectBtn.addEventListener('click', function (event) {
				event.preventDefault();
				fileInput.click();
			});
		}

		if (dropzone && fileInput) {
			dropzone.addEventListener('click', function (event) {
				if (event.target.closest('button, input, a')) {
					return;
				}
				fileInput.click();
			});

			dropzone.addEventListener('dragover', function (event) {
				event.preventDefault();
				dropzone.classList.add('border-black', 'bg-slate-100');
			});

			dropzone.addEventListener('dragleave', function () {
				dropzone.classList.remove('border-black', 'bg-slate-100');
			});

			dropzone.addEventListener('drop', function (event) {
				event.preventDefault();
				dropzone.classList.remove('border-black', 'bg-slate-100');
				const files = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files : null;
				if (!files || !files.length) {
					return;
				}
				fileInput.files = files;
				setPreviewFromFile(files[0]);
			});
		}

		if (fileInput) {
			fileInput.addEventListener('change', function () {
				const file = this.files && this.files.length ? this.files[0] : null;
				if (file) {
					setPreviewFromFile(file);
				} else {
					clearPreview();
				}
			});
		}

		if (removeBtn) {
			removeBtn.addEventListener('click', function () {
				clearSelectedFile();
				clearPreview();
			});
		}

		return {
			clearPreview: clearPreview,
			clearSelectedFile: clearSelectedFile,
			setPreviewFromFile: setPreviewFromFile,
		};
	}

	const photoField = createUploadField({
		fileInput: photoFileInput,
		dropzone: dropzone,
		preview: photoPreview,
		previewWrap: photoPreviewWrap,
		emptyState: photoEmptyState,
		filename: photoFilename,
		selectBtn: selectPhotoBtn,
		removeBtn: removePhotoBtn,
		selectedIdInput: photoInput,
	});

	const backcoverField = createUploadField({
		fileInput: backcoverFileInput,
		dropzone: backcoverDropzone,
		preview: backcoverPreview,
		previewWrap: backcoverPreviewWrap,
		emptyState: backcoverEmptyState,
		filename: backcoverFilename,
		selectBtn: selectBackcoverBtn,
		removeBtn: removeBackcoverBtn,
		selectedIdInput: backcoverInput,
	});

	function showStep(stepNumber) {
		steps.forEach(function (step) {
			const isActive = step.dataset.step === String(stepNumber);
			step.classList.toggle('hidden', !isActive);
			step.hidden = !isActive;
		});
	}

	function openModal() {
		if (!modal || !modalPanel) {
			return;
		}
		modal.classList.remove('hidden');
		modalPanel.classList.remove('opacity-0', 'scale-95');
		modalPanel.classList.add('opacity-100', 'scale-100');
		showStep(1);
	}

	function closeModal() {
		if (!modal || !modalPanel || !form) {
			return;
		}
		modalPanel.classList.add('opacity-0', 'scale-95');
		modalPanel.classList.remove('opacity-100', 'scale-100');
		setTimeout(function () {
			modal.classList.add('hidden');
			form.reset();
			if (photoInput) {
				photoInput.value = '0';
			}
			if (backcoverInput) {
				backcoverInput.value = '0';
			}
			photoField.clearSelectedFile();
			photoField.clearPreview();
			backcoverField.clearSelectedFile();
			backcoverField.clearPreview();
			showStep(1);
		}, 180);
	}

	if (openBtn) {
		openBtn.addEventListener('click', openModal);
	}

	if (closeBtn) {
		closeBtn.addEventListener('click', closeModal);
	}

	cancelBtns.forEach(function (btn) {
		btn.addEventListener('click', closeModal);
	});

	if (modal) {
		modal.addEventListener('click', function (event) {
			if (event.target === modal) {
				closeModal();
			}
		});
	}

	document.querySelectorAll('.next-step-btn').forEach(function (btn) {
		btn.addEventListener('click', function () {
			const nextStep = this.dataset.next;
			if (nextStep === '2') {
				if (!step1Inputs[0].value.trim() || !step1Inputs[1].value.trim()) {
					step1Inputs[0].reportValidity();
					step1Inputs[1].reportValidity();
					return;
				}
			}
			showStep(nextStep);
		});
	});

	document.querySelectorAll('.prev-step-btn').forEach(function (btn) {
		btn.addEventListener('click', function () {
			showStep(this.dataset.prev);
		});
	});

	if (form) {
		form.addEventListener('submit', function (event) {
			const bio = document.getElementById('author_bio');
			if (bio && bio.value.trim()) {
				const words = bio.value.trim().split(/\s+/).filter(Boolean).length;
				if (words > 500) {
					alert('La biografía no puede superar 500 palabras.');
					event.preventDefault();
					return;
				}
			}
		});
	}
});
