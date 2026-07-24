(function ($) {
	'use strict';

	$(function () {
		const data = window.almadenBlogPostData || {};
		if (!$('#almaden-blog-post-app').length) {
			return;
		}

		const $list = $('#almaden-blog-post-list');
		const $panel = $('#almaden-blog-editor-panel');
		const $form = $('#almaden-blog-post-form');
		const $title = $('#almaden-blog-post-title');
		const $editor = $('#almaden-blog-editor');
		const $excerpt = $('#almaden-blog-post-excerpt');
		const $postId = $('#almaden-blog-post-id');
		const $thumbId = $('#almaden-blog-post-thumbnail-id');
		const $preview = $('#almaden-blog-post-preview');
		const $coverPreview = $('#almaden-blog-cover-preview');
		const $coverUpload = $('#almaden-blog-cover-upload');
		const $coverRemove = $('#almaden-blog-cover-remove');
		const $placeholder = $('#almaden-blog-placeholder');
		const strings = (data && data.i18n) || {};

		let savedRange = null;
		let loadingPosts = false;

		function t(key, fallback) {
			return (strings && Object.prototype.hasOwnProperty.call(strings, key) && strings[key]) ? strings[key] : fallback;
		}

		function ajax(action, payload) {
			return $.ajax({
				url: data.ajaxUrl,
				type: 'POST',
				data: Object.assign({
					action: action,
					nonce: data.nonce
				}, payload || {})
			});
		}

		function setMessage(message) {
			$list.html('<div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">' + message + '</div>');
		}

		function getEditor() {
			return $editor.get(0);
		}

		function isRangeInsideEditor(range) {
			const editor = getEditor();
			return !!(range && editor && editor.contains(range.startContainer) && editor.contains(range.endContainer));
		}

		function saveSelection(force) {
			const sel = window.getSelection ? window.getSelection() : null;
			if (!sel || sel.rangeCount === 0) {
				return;
			}
			const range = sel.getRangeAt(0);
			if (!force && !isRangeInsideEditor(range)) {
				return;
			}
			if (isRangeInsideEditor(range)) {
				savedRange = range.cloneRange();
			}
		}

		function restoreSelection() {
			const sel = window.getSelection ? window.getSelection() : null;
			if (!sel || !savedRange || !isRangeInsideEditor(savedRange)) {
				return false;
			}
			sel.removeAllRanges();
			sel.addRange(savedRange);
			return true;
		}

		function focusEditor() {
			const editor = getEditor();
			if (editor) {
				editor.focus();
			}
		}

		function placeCaretAtEnd() {
			const editor = getEditor();
			const sel = window.getSelection ? window.getSelection() : null;
			if (!editor || !sel || !document.createRange) {
				return;
			}
			const range = document.createRange();
			range.selectNodeContents(editor);
			range.collapse(false);
			sel.removeAllRanges();
			sel.addRange(range);
			savedRange = range.cloneRange();
		}

		function insertHtml(html) {
			const editor = getEditor();
			if (!editor) {
				return false;
			}

			const sel = window.getSelection ? window.getSelection() : null;
			let range = sel && sel.rangeCount > 0 ? sel.getRangeAt(0) : null;
			if (!isRangeInsideEditor(range)) {
				range = savedRange && isRangeInsideEditor(savedRange) ? savedRange.cloneRange() : null;
			}
			if (!range) {
				placeCaretAtEnd();
				range = sel && sel.rangeCount > 0 ? sel.getRangeAt(0) : null;
			}
			if (!range) {
				return false;
			}

			range.deleteContents();
			const container = document.createElement('div');
			container.innerHTML = html;
			const fragment = document.createDocumentFragment();
			let lastNode = null;
			while (container.firstChild) {
				lastNode = fragment.appendChild(container.firstChild);
			}
			range.insertNode(fragment);

			if (sel && lastNode) {
				const next = document.createRange();
				next.setStartAfter(lastNode);
				next.collapse(true);
				sel.removeAllRanges();
				sel.addRange(next);
				savedRange = next.cloneRange();
			}
			return true;
		}

		function getClosestBlock(node) {
			const editor = getEditor();
			if (!node || !editor) {
				return null;
			}
			let current = node.nodeType === Node.ELEMENT_NODE ? node : node.parentNode;
			while (current && current !== editor) {
				if (current.nodeType === Node.ELEMENT_NODE && /^(P|DIV|H1|H2|H3|H4|H5|H6)$/i.test(current.tagName)) {
					return current;
				}
				current = current.parentNode;
			}
			return null;
		}

		function replaceBlockTag(tagName) {
			const editor = getEditor();
			if (!editor) {
				return false;
			}
			const sel = window.getSelection ? window.getSelection() : null;
			let range = sel && sel.rangeCount > 0 ? sel.getRangeAt(0) : null;
			if (!isRangeInsideEditor(range)) {
				range = savedRange && isRangeInsideEditor(savedRange) ? savedRange.cloneRange() : null;
			}
			if (!range) {
				return false;
			}
			const block = getClosestBlock(range.startContainer) || getClosestBlock(range.commonAncestorContainer);
			if (!block || !block.parentNode) {
				return false;
			}

			const replacement = document.createElement(String(tagName).toUpperCase());
			replacement.innerHTML = block.innerHTML;
			block.parentNode.replaceChild(replacement, block);

			if (sel) {
				const next = document.createRange();
				next.selectNodeContents(replacement);
				next.collapse(false);
				sel.removeAllRanges();
				sel.addRange(next);
				savedRange = next.cloneRange();
			}
			return true;
		}

		function execCommand(cmd, value) {
			try {
				focusEditor();
				restoreSelection();
				const normalizedValue = cmd === 'formatBlock' && value ? (String(value).charAt(0) === '<' ? value : '<' + value + '>') : value;
				document.execCommand(cmd, false, normalizedValue || null);
				saveSelection(true);
			} catch (err) {
				// ignore
			}
		}

		function updatePlaceholder() {
			const html = ($editor.html() || '').trim().toLowerCase();
			const text = ($editor.text() || '').replace(/\u00a0/g, ' ').trim();
			const hasImage = html.includes('<img') || html.includes('<figure');
			const empty = !hasImage && text === '' && (html === '' || html === '<br>' || html === '<p><br></p>' || html === '<p></p>');
			$placeholder.toggle(empty);
		}

		function autoResizeTitle() {
			const el = $title.get(0);
			if (!el) {
				return;
			}
			el.style.height = 'auto';
			el.style.height = el.scrollHeight + 'px';
		}

		function setCover(url, id) {
			const hasUrl = !!url;
			$thumbId.val(id || 0);
			$coverPreview.toggle(hasUrl);
			$coverPreview.find('img').attr('src', url || '');
			$coverUpload.toggle(!hasUrl);
			$coverRemove.toggle(hasUrl);
		}

		function resetForm() {
			$postId.val(0);
			$thumbId.val(0);
			$title.val('');
			$editor.html('<p><br></p>');
			$excerpt.val('');
			$preview.attr('href', '#').addClass('hidden').hide();
			setCover('', 0);
			updatePlaceholder();
			autoResizeTitle();
			$panel.removeClass('hidden');
		}

		function showEditor() {
			$panel.removeClass('hidden');
			$('html, body').animate({ scrollTop: 0 }, 160);
		}

		function hideEditor() {
			$panel.addClass('hidden');
		}

		function renderPosts(posts) {
			if (!posts || !posts.length) {
				setMessage(t('noPostsYet', 'Aún no has creado posts.'));
				return;
			}

			const html = posts.map(function (post) {
				const thumb = post.thumbnail_url ? '<img src="' + post.thumbnail_url + '" alt="" class="h-full w-full object-cover">' : '<div class="flex h-full w-full items-center justify-center bg-slate-100 text-slate-300">•</div>';
				const badgeClass = post.status === 'publish' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600';
				const badgeText = post.status_label || (post.status === 'publish' ? t('published', 'Publicado') : t('draft', 'Borrador'));
				return [
					'<article class="almaden-blog-card group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg" data-post-id="' + post.id + '">',
						'<button type="button" class="block w-full text-left" data-blog-edit>',
							'<div class="aspect-[4/3] overflow-hidden bg-slate-50">' + thumb + '</div>',
							'<div class="space-y-3 p-4">',
								'<div class="flex items-center justify-between gap-2">',
									'<span class="rounded-full px-3 py-1 text-xs font-semibold ' + badgeClass + '">' + badgeText + '</span>',
									'<span class="text-xs text-slate-400">' + (post.date || '') + '</span>',
								'</div>',
								'<h3 class="line-clamp-2 text-base font-semibold leading-snug text-slate-950">' + (post.title || '') + '</h3>',
							'</div>',
						'</button>',
						'<div class="flex gap-2 border-t border-slate-100 px-4 py-3">',
						'<button type="button" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-950" data-blog-edit>' + t('edit', 'Editar') + '</button>',
							'<button type="button" class="rounded-full border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:border-rose-300" data-blog-delete>Eliminar</button>',
						'</div>',
					'</article>'
				].join('');
			}).join('');

			$list.html(html);
		}

		function loadPosts() {
			if (loadingPosts) {
				return;
			}
			loadingPosts = true;
			setMessage(t('loading', 'Cargando...'));
			ajax('almaden_blog_post_list', {})
				.done(function (response) {
					const posts = response && response.success && response.data && response.data.posts ? response.data.posts : [];
					renderPosts(posts);
				})
				.fail(function () {
					setMessage(t('errorGeneric', 'No se pudieron cargar los posts.'));
				})
				.always(function () {
					loadingPosts = false;
				});
		}

		function openPost(postId) {
			const id = parseInt(postId || 0, 10) || 0;
			if (!id) {
				return;
			}

			ajax('almaden_blog_post_get', { post_id: id })
				.done(function (response) {
					const post = response && response.success && response.data ? response.data.post : null;
					if (!post) {
						return;
					}
					$postId.val(post.id || 0);
					$title.val(post.title || '');
					$editor.html(post.content || '<p><br></p>');
					$excerpt.val(post.excerpt || '');
					setCover(post.thumbnail_url || '', post.thumbnail_id || 0);
					if (post.permalink) {
						$preview.attr('href', post.permalink).removeClass('hidden').show();
					}
					updatePlaceholder();
					autoResizeTitle();
					showEditor();
				});
		}

		function savePost(status) {
			const payload = {
				post_id: parseInt($postId.val() || 0, 10) || 0,
				title: $title.val(),
				content: $editor.html(),
				excerpt: $excerpt.val(),
				status: status,
				thumbnail_id: parseInt($thumbId.val() || 0, 10) || 0
			};

			if (!payload.title.trim()) {
				window.alert(t('errorTitleRequired', 'Debes agregar un título.'));
				return;
			}

			$form.find('[data-blog-action]').prop('disabled', true);
			ajax('almaden_blog_post_save', payload)
				.done(function (response) {
					if (!response || !response.success || !response.data || !response.data.post) {
						window.alert(t('errorGeneric', 'No se pudo guardar el post.'));
						return;
					}

					const post = response.data.post;
					$postId.val(post.id || 0);
					if (post.thumbnail_id) {
						$thumbId.val(post.thumbnail_id);
					}
					if (post.permalink) {
						$preview.attr('href', post.permalink).removeClass('hidden').show();
					}
					loadPosts();
				})
				.fail(function () {
					window.alert(t('errorGeneric', 'No se pudo guardar el post.'));
				})
				.always(function () {
					$form.find('[data-blog-action]').prop('disabled', false);
				});
		}

		function deletePost(postId) {
			if (!window.confirm(t('deleteConfirm', '¿Eliminar este post?'))) {
				return;
			}
			ajax('almaden_blog_post_delete', { post_id: postId })
				.done(function (response) {
					if (response && response.success) {
						if (parseInt($postId.val() || 0, 10) === parseInt(postId, 10)) {
							resetForm();
							hideEditor();
						}
						loadPosts();
					}
				});
		}

		function openMediaFrame(mode) {
			if (!window.wp || !wp.media) {
				return;
			}

			const frame = wp.media({
				title: mode === 'cover' ? t('coverImage', 'Imagen de portada') : t('insertImage', 'Insertar imagen'),
				button: { text: t('selectImage', 'Selecciona una imagen') },
				multiple: false
			});

			frame.on('select', function () {
				const attachment = frame.state().get('selection').first().toJSON();
				if (mode === 'cover') {
					setCover(attachment.url || '', attachment.id || 0);
					return;
				}

				const figureHtml = [
					'<figure class="almaden-blog-inline-figure">',
						'<img src="' + (attachment.url || '') + '" data-attachment-id="' + (attachment.id || 0) + '" alt="">',
						'<figcaption class="almaden-blog-inline-caption" contenteditable="false" data-placeholder="Escribe un texto para esta imagen..."></figcaption>',
					'</figure>'
				].join('');
				focusEditor();
				if (!restoreSelection()) {
					placeCaretAtEnd();
				}
				insertHtml(figureHtml);
				updatePlaceholder();
			});

			frame.open();
		}

		$(document).on('mousedown', '#almaden-blog-editor, .almaden-blog-tool-btn, .blog-heading-item', function () {
			saveSelection(true);
		});

		$(document).on('selectionchange', function () {
			saveSelection();
		});

		$(document).on('input keyup blur focus change', '#almaden-blog-editor', function () {
			updatePlaceholder();
			saveSelection();
		});

		$(document).on('input', '#almaden-blog-post-title', autoResizeTitle);

		$(document).on('click', '#almaden-blog-open-create', function () {
			resetForm();
			showEditor();
		});

		$(document).on('click', '[data-blog-edit]', function (e) {
			e.preventDefault();
			const postId = $(this).closest('[data-post-id]').data('post-id');
			openPost(postId);
		});

		$(document).on('click', '[data-blog-delete]', function (e) {
			e.preventDefault();
			const postId = $(this).closest('[data-post-id]').data('post-id');
			deletePost(postId);
		});

		$(document).on('click', '.almaden-blog-tool-btn', function (e) {
			e.preventDefault();
			const cmd = $(this).data('cmd');
			if (cmd === 'back') {
				hideEditor();
				return;
			}
			if (cmd === 'heading') {
				$(this).siblings('[data-blog-heading-menu]').toggleClass('hidden');
				return;
			}
			if (cmd === 'insertImage') {
				openMediaFrame('inline');
				return;
			}
			execCommand(cmd);
		});

		$(document).on('click', '.blog-heading-item', function (e) {
			e.preventDefault();
			$('[data-blog-heading-menu]').addClass('hidden');
			const tag = $(this).data('tag');
			if (tag === 'p') {
				execCommand('formatBlock', '<p>');
				return;
			}
			restoreSelection();
			if (!replaceBlockTag(tag)) {
				execCommand('formatBlock', tag);
			}
		});

		$(document).on('click', '[data-blog-action]', function (e) {
			e.preventDefault();
			savePost($(this).data('blog-action') || 'draft');
		});

		$(document).on('click', '#almaden-blog-cover-upload', function () {
			openMediaFrame('cover');
		});

		$(document).on('click', '#almaden-blog-cover-remove', function () {
			setCover('', 0);
		});

		$(document).on('click', function (e) {
			if ($(e.target).closest('[data-blog-heading-dropdown]').length) {
				return;
			}
			$('[data-blog-heading-menu]').addClass('hidden');
		});

		window.refreshActiveList = loadPosts;

		resetForm();
		hideEditor();
		loadPosts();
	});
})(jQuery);
