window.AlmadenLearni = window.AlmadenLearni || {};

(function (ns) {
	'use strict';

	var qa = ns.qa;

	function bindCreateCourseToggle(root) {
		var stateRoot = root.body || root.documentElement || root;
		var panel = root.querySelector('#almaden-create-course-panel');
		var creatorSidebar = root.querySelector('#almaden-course-creator-sidebar');
		if (!panel) {
			return;
		}

		function setCreateMode(open) {
			panel.hidden = !open;
			if (creatorSidebar) {
				creatorSidebar.hidden = open;
			}
			if (stateRoot && stateRoot.classList) {
				stateRoot.classList.toggle('is-create-course-open', open);
			}

			if (open) {
				var firstField = panel.querySelector('input[name="course_title"]');
				if (firstField) {
					firstField.focus();
				}
				panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		}

		setCreateMode(false);

		qa(root, '[data-almaden-toggle-create-course]').forEach(function (button) {
			button.addEventListener('click', function () {
				setCreateMode(panel.hidden);
			});
		});

		function updateCollaboratorsInput() {
			var hidden = panel.querySelector('[data-almaden-create-collaborators]');
			if (!hidden) {
				return;
			}

			var names = qa(panel, '[data-almaden-create-teacher-name]').map(function (input) {
				return (input.value || '').trim();
			}).filter(function (name) {
				return name.length > 0;
			});

			hidden.value = names.join(', ');
		}

		function bindTeachersRepeater() {
			var list = panel.querySelector('[data-almaden-create-teachers-list]');
			var addButton = panel.querySelector('[data-almaden-create-teacher-add]');
			if (!list || !addButton) {
				return;
			}

			function bindRow(row) {
				var input = row.querySelector('[data-almaden-create-teacher-name]');
				var removeButton = row.querySelector('[data-almaden-create-teacher-remove]');
				if (input) {
					input.addEventListener('input', updateCollaboratorsInput);
				}
				if (removeButton) {
					removeButton.addEventListener('click', function () {
						if (list.querySelectorAll('[data-almaden-create-teacher-row]').length > 1) {
							row.remove();
						} else if (input) {
							input.value = '';
						}
						updateCollaboratorsInput();
					});
				}
			}

			qa(list, '[data-almaden-create-teacher-row]').forEach(bindRow);

			addButton.addEventListener('click', function () {
				var row = document.createElement('div');
				row.className = 'flex items-center gap-3 rounded-[1.25rem] border border-slate-200 bg-white p-3';
				row.setAttribute('data-almaden-create-teacher-row', '');
				row.innerHTML = [
					'<input type="text" class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2 text-sm outline-none placeholder:text-slate-300" placeholder="Nombre del profesor" data-almaden-create-teacher-name>',
					'<button type="button" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 transition hover:border-rose-200 hover:text-rose-600" data-almaden-create-teacher-remove>Quitar</button>'
				].join('');
				list.appendChild(row);
				bindRow(row);
				var input = row.querySelector('[data-almaden-create-teacher-name]');
				if (input) {
					input.focus();
				}
				updateCollaboratorsInput();
			});

			updateCollaboratorsInput();
		}

		function bindCreateTabs() {
			var tabButtons = qa(panel, '[data-almaden-create-tab]');
			var tabPanels = qa(panel, '[data-almaden-create-panel]');
			if (!tabButtons.length || !tabPanels.length) {
				return;
			}

			function activateTab(tabKey) {
				tabButtons.forEach(function (button) {
					var isActive = button.getAttribute('data-almaden-create-tab') === tabKey;
					button.classList.toggle('is-active', isActive);
					button.classList.toggle('bg-white', isActive);
					button.classList.toggle('text-slate-950', isActive);
					button.classList.toggle('text-slate-500', !isActive);
				});

				tabPanels.forEach(function (tabPanel) {
					tabPanel.classList.toggle('hidden', tabPanel.getAttribute('data-almaden-create-panel') !== tabKey);
				});
			}

			tabButtons.forEach(function (button) {
				button.addEventListener('click', function () {
					activateTab(button.getAttribute('data-almaden-create-tab') || 'description');
				});
			});

			activateTab('description');
		}

		bindCreateTabs();
		bindTeachersRepeater();

		window.addEventListener('pageshow', function (event) {
			if (event && event.persisted) {
				setCreateMode(false);
			}
		});
	}

	function bindCourseEditorTabs(root) {
		var panel = root.querySelector('#almaden-learni-tab-curso');
		if (!panel) {
			return;
		}

		var tabButtons = qa(panel, '[data-almaden-course-tab]');
		var tabPanels = qa(panel, '[data-almaden-course-panel]');
		if (!tabButtons.length || !tabPanels.length) {
			return;
		}

		function activateTab(tabKey) {
			tabButtons.forEach(function (button) {
				var isActive = button.getAttribute('data-almaden-course-tab') === tabKey;
				button.classList.toggle('is-active', isActive);
				button.classList.toggle('bg-white', isActive);
				button.classList.toggle('text-slate-950', isActive);
				button.classList.toggle('text-slate-500', !isActive);
			});

			tabPanels.forEach(function (tabPanel) {
				tabPanel.classList.toggle('hidden', tabPanel.getAttribute('data-almaden-course-panel') !== tabKey);
			});
		}

		tabButtons.forEach(function (button) {
			button.addEventListener('click', function () {
				activateTab(button.getAttribute('data-almaden-course-tab') || 'description');
			});
		});

		activateTab('description');
	}

	ns.bindCreateCourseToggle = bindCreateCourseToggle;
	ns.bindCourseEditorTabs = bindCourseEditorTabs;
})(window.AlmadenLearni);
