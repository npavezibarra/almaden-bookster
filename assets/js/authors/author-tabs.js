document.addEventListener('DOMContentLoaded', function () {
	const tabs = Array.from(document.querySelectorAll('#almaden-author-tabs .almaden-author-tab'));
	const panels = Array.from(document.querySelectorAll('#almaden-author-panel .almaden-author-tabpanel'));

	function activateTab(tabName) {
		tabs.forEach(function (tab) {
			const isActive = tab.dataset.tab === tabName;
			tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
		});

		panels.forEach(function (panel) {
			panel.hidden = panel.id !== 'almaden-author-panel-' + tabName;
		});
	}

	tabs.forEach(function (tab) {
		tab.addEventListener('click', function () {
			activateTab(this.dataset.tab);
		});
	});

	if (tabs.length) {
		activateTab('posts');
	}
});
