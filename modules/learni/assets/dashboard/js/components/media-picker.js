window.AlmadenLearni = window.AlmadenLearni || {};

(function (ns) {
	'use strict';

	var qa = ns.qa;

	var mediaFrames = {};

	function getMediaPreviewElements(target) {
		return {
			input: document.querySelector('[data-almaden-media-input="' + target + '"]'),
			preview: document.querySelector('[data-almaden-media-preview="' + target + '"]'),
			empty: document.querySelector('[data-almaden-media-empty="' + target + '"]'),
			remove: document.querySelector('[data-almaden-media-remove][data-media-target="' + target + '"]'),
		};
	}

	function getAttachmentUrl(attachment) {
		if (!attachment) {
			return '';
		}

		if (attachment.sizes) {
			var preferred = attachment.sizes.medium_large || attachment.sizes.large || attachment.sizes.full;
			if (preferred && preferred.url) {
				return preferred.url;
			}
		}

		return attachment.url || '';
	}

	function syncMediaPreview(target, attachment) {
		var parts = getMediaPreviewElements(target);
		var url = attachment ? getAttachmentUrl(attachment) : '';
		var id = attachment && attachment.id ? String(attachment.id) : '0';

		if (parts.input) {
			parts.input.value = id;
		}

		if (parts.preview) {
			var img = parts.preview.querySelector('img');
			if (img && url) {
				img.src = url;
			}
			parts.preview.style.display = url ? '' : 'none';
		}

		if (parts.empty) {
			parts.empty.style.display = url ? 'none' : '';
		}

		if (parts.remove) {
			parts.remove.style.display = url ? '' : 'none';
		}
	}

	function openMediaPicker(target, title, buttonText) {
		if (!window.wp || !wp.media) {
			return;
		}

		if (!mediaFrames[target]) {
			mediaFrames[target] = wp.media({
				title: title || 'Seleccionar imagen',
				button: {
					text: buttonText || 'Usar imagen'
				},
				multiple: false,
				library: {
					type: 'image'
				}
			});

			mediaFrames[target].on('select', function () {
				var selection = mediaFrames[target].state().get('selection');
				var attachment = selection && selection.first ? selection.first().toJSON() : null;
				syncMediaPreview(target, attachment);
			});
		}

		mediaFrames[target].open();
	}

	function bindMediaPickers(root) {
		qa(root, '[data-almaden-media-picker]').forEach(function (button) {
			button.addEventListener('click', function () {
				var target = button.getAttribute('data-media-target') || '';
				if (!target) {
					return;
				}

				openMediaPicker(
					target,
					button.getAttribute('data-media-title') || '',
					button.getAttribute('data-media-button') || ''
				);
			});
		});

		qa(root, '[data-almaden-media-remove]').forEach(function (button) {
			button.addEventListener('click', function () {
				var target = button.getAttribute('data-media-target') || '';
				if (!target) {
					return;
				}

				syncMediaPreview(target, null);
			});
		});

		qa(root, '[data-almaden-media-input]').forEach(function (input) {
			var target = input.getAttribute('data-almaden-media-input') || '';
			if (!target) {
				return;
			}

			if (parseInt(input.value || '0', 10) <= 0) {
				var parts = getMediaPreviewElements(target);
				var previewImage = parts.preview ? parts.preview.querySelector('img') : null;
				var hasRenderedPreview = !!(parts.preview && parts.preview.style.display !== 'none' && previewImage && previewImage.getAttribute('src'));
				if (!hasRenderedPreview) {
					syncMediaPreview(target, null);
				}
			}
		});
	}

	ns.bindMediaPickers = bindMediaPickers;
})(window.AlmadenLearni);
