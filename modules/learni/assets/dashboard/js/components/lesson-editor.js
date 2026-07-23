window.AlmadenLearni = window.AlmadenLearni || {};

(function (ns) {
	'use strict';

	var qa = ns.qa;

	function bindLessonCreatorToggle(root) {
		var panel = root.querySelector('#almaden-lesson-creator-panel');
		if (!panel) {
			return;
		}

		qa(root, '[data-almaden-toggle-lesson-creator]').forEach(function (button) {
			button.addEventListener('click', function () {
				var shouldOpen = panel.hasAttribute('hidden');
				panel.hidden = !shouldOpen;
				if (shouldOpen) {
					var firstField = panel.querySelector('input[name="lesson_title"]');
					if (firstField) {
						firstField.focus();
					}
					panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
				}
			});
		});
	}

	function setLessonAccordionState(card, isOpen) {
		if (!card) {
			return;
		}

		var panel = card.querySelector('[data-almaden-lesson-panel]');
		var toggle = card.querySelector('[data-almaden-lesson-toggle]');
		var icon = card.querySelector('[data-almaden-lesson-toggle-icon]');

		card.classList.toggle('almaden-learni-lesson-card--open', !!isOpen);

		if (panel) {
			panel.hidden = !isOpen;
		}

		if (toggle) {
			toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		}

		if (icon) {
			icon.className = 'dashicons ' + (isOpen ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-right-alt2');
		}
	}

	function bindLessonAccordion(root) {
		var cards = qa(root, '[data-almaden-lesson-card]');
		if (!cards.length) {
			return;
		}

		cards.forEach(function (card) {
			setLessonAccordionState(card, false);
		});

		qa(root, '[data-almaden-lesson-toggle]').forEach(function (button) {
			button.addEventListener('click', function () {
				var card = button.closest('[data-almaden-lesson-card]');
				if (!card) {
					return;
				}

				var panel = card.querySelector('[data-almaden-lesson-panel]');
				setLessonAccordionState(card, !!(panel && panel.hasAttribute('hidden')));
			});
		});
	}

	var draggingOutlineItem = null;

	function getLessonSortableRoot() {
		return document.querySelector('[data-almaden-outline-sortable]');
	}

	function getLessonItems(root) {
		return qa(root, '[data-almaden-outline-item]');
	}

	function clearLessonDropTargets(root) {
		getLessonItems(root).forEach(function (item) {
			item.classList.remove('almaden-learni-lesson--drop-target');
		});
	}

	function submitLessonOrder(root) {
		var actionUrl = root.getAttribute('data-order-action') || '';
		var courseId = root.getAttribute('data-course-id') || '';
		var nonce = root.getAttribute('data-order-nonce') || '';

		if (!actionUrl || !courseId || !nonce) {
			return;
		}

		var form = document.createElement('form');
		form.method = 'post';
		form.action = actionUrl;
		form.style.display = 'none';

		function appendField(name, value) {
			var input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			input.value = value;
			form.appendChild(input);
		}

		appendField('action', 'almaden_learni_save_outline_order');
		appendField('course_id', courseId);
		appendField('_wpnonce', nonce);

		var outlineItems = getLessonItems(root).map(function (item) {
			var type = item.getAttribute('data-outline-item-type') || '';
			var id = item.getAttribute('data-outline-item-id') || '';
			var labelField = type === 'section'
				? item.querySelector('input[name="section_label"]')
				: item.querySelector('input[name="lesson_title"]');

			return {
				type: type,
				id: parseInt(id || '0', 10) || 0,
				label: labelField ? (labelField.value || '').trim() : ''
			};
		});

		appendField('outline_items_json', JSON.stringify(outlineItems));

		document.body.appendChild(form);
		form.submit();
	}

	function bindLessonSorting(root) {
		var sortable = root || getLessonSortableRoot();
		if (!sortable) {
			return;
		}

		qa(sortable, '[data-almaden-lesson-drag-handle]').forEach(function (handle) {
			handle.setAttribute('draggable', 'true');
		});

		sortable.addEventListener('dragstart', function (event) {
			var handle = event.target.closest('[data-almaden-lesson-drag-handle]');
			var item = event.target.closest('[data-almaden-outline-item]');
			if (!handle || !item || !sortable.contains(item)) {
				event.preventDefault();
				return;
			}

			draggingOutlineItem = item;
			item.classList.add('almaden-learni-lesson--dragging');
			event.dataTransfer.effectAllowed = 'move';
			try {
				event.dataTransfer.setData('text/plain', item.getAttribute('data-outline-item-id') || '');
			} catch (err) {
				// Some browsers disallow custom drag payloads; moving the DOM still works.
			}
		});

		sortable.addEventListener('dragover', function (event) {
			if (!draggingOutlineItem) {
				return;
			}

			var targetItem = event.target.closest('[data-almaden-outline-item]');
			if (!targetItem || targetItem === draggingOutlineItem || !sortable.contains(targetItem)) {
				return;
			}

			event.preventDefault();
			clearLessonDropTargets(sortable);
			targetItem.classList.add('almaden-learni-lesson--drop-target');

			var rect = targetItem.getBoundingClientRect();
			var next = (event.clientY - rect.top) / (rect.height || 1) > 0.5;
			sortable.insertBefore(draggingOutlineItem, next ? targetItem.nextSibling : targetItem);
		});

		sortable.addEventListener('drop', function (event) {
			if (!draggingOutlineItem) {
				return;
			}

			event.preventDefault();
			clearLessonDropTargets(sortable);
			submitLessonOrder(sortable);
		});

		sortable.addEventListener('dragend', function () {
			if (draggingOutlineItem) {
				draggingOutlineItem.classList.remove('almaden-learni-lesson--dragging');
			}
			clearLessonDropTargets(sortable);
			draggingOutlineItem = null;
		});
	}

	ns.bindLessonCreatorToggle = bindLessonCreatorToggle;
	ns.bindLessonAccordion = bindLessonAccordion;
	ns.bindLessonSorting = bindLessonSorting;
})(window.AlmadenLearni);
