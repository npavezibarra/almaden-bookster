/**
 * AlmadenBookster — Admin Google Fonts Page JS
 *
 * Handles API key saving, font catalog loading/searching,
 * font preview rendering, and install/uninstall actions.
 */
(function ($) {
	'use strict';

	let allFonts = [];
	let installedFamilies = [];

	// Collect initially installed font families from the DOM
	function collectInstalledFamilies() {
		installedFamilies = [];
		$('#almaden-installed-list .almaden-installed-item, #almaden-bundled-list .almaden-installed-item').each(function () {
			installedFamilies.push($(this).data('family'));
		});
	}

	// ─── API Key ────────────────────────────────────────────────
	$('#almaden-save-api-key').on('click', function () {
		const btn = $(this);
		const apiKey = $('#almaden-api-key-input').val().trim();

		btn.prop('disabled', true).text('Guardando...');

		$.post(almadenFonts.ajaxUrl, {
			action: 'almaden_save_fonts_api_key',
			nonce: almadenFonts.nonce,
			api_key: apiKey,
		}, function (res) {
			btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Guardar Key');
			const status = $('#almaden-api-key-status');
			if (res.success) {
				status.html('<span class="almaden-msg-success">✓ ' + res.data.message + '</span>');
			} else {
				status.html('<span class="almaden-msg-error">✗ ' + res.data + '</span>');
			}
			setTimeout(function () { status.fadeOut(300, function () { $(this).html('').show(); }); }, 3000);
		});
	});

	// ─── Load Catalog ───────────────────────────────────────────
	$('#almaden-load-fonts').on('click', loadCatalog);

	function loadCatalog() {
		const grid = $('#almaden-fonts-grid');
		const loading = $('#almaden-fonts-loading');
		const sort = $('#almaden-font-sort').val();

		grid.hide();
		loading.show();

		$.post(almadenFonts.ajaxUrl, {
			action: 'almaden_search_google_fonts',
			nonce: almadenFonts.nonce,
			sort: sort,
		}, function (res) {
			loading.hide();
			grid.show();

			if (res.success) {
				allFonts = res.data;
				renderFonts(allFonts);
			} else {
				grid.html('<div class="almaden-fonts-empty"><p class="almaden-msg-error">' + res.data + '</p></div>');
			}
		}).fail(function () {
			loading.hide();
			grid.show().html('<div class="almaden-fonts-empty"><p class="almaden-msg-error">Error de conexión.</p></div>');
		});
	}

	// ─── Search Filter ──────────────────────────────────────────
	$('#almaden-font-search').on('input', function () {
		const query = $(this).val().toLowerCase().trim();
		if (!allFonts.length) return;

		if (!query) {
			renderFonts(allFonts);
			return;
		}

		const filtered = allFonts.filter(function (f) {
			return f.family.toLowerCase().indexOf(query) !== -1;
		});
		renderFonts(filtered);
	});

	// ─── Render Font Cards ──────────────────────────────────────
	function renderFonts(fonts) {
		const grid = $('#almaden-fonts-grid');
		grid.empty();

		if (!fonts.length) {
			grid.html('<div class="almaden-fonts-empty"><p>No se encontraron fuentes con ese criterio.</p></div>');
			return;
		}

		// Limit displayed fonts to 200 for performance
		const displayFonts = fonts.slice(0, 200);

		// Batch-load Google Fonts for preview
		injectPreviewFonts(displayFonts);

		displayFonts.forEach(function (font) {
			const isInstalled = installedFamilies.indexOf(font.family) !== -1;
			const card = buildFontCard(font, isInstalled);
			grid.append(card);
		});
	}

	function buildFontCard(font, isInstalled) {
		const card = $('<div>').addClass('almaden-font-card' + (isInstalled ? ' is-installed' : ''));

		const header = $('<div>').addClass('almaden-font-card-header');
		header.append($('<span>').addClass('almaden-font-card-name').text(font.family));
		header.append($('<span>').addClass('almaden-category-badge').text(font.category));

		const preview = $('<div>').addClass('almaden-font-preview')
			.css('font-family', "'" + font.family + "', " + font.category)
			.text('El veloz murciélago hindú');

		const actions = $('<div>').addClass('almaden-font-card-actions');
		const btn = $('<button>').addClass('button almaden-install-btn');

		if (isInstalled) {
			btn.addClass('installed')
				.html('<span class="dashicons dashicons-yes-alt"></span> Instalada');
		} else {
			btn.html('<span class="dashicons dashicons-download"></span> Instalar')
				.on('click', function () { installFont(font, $(this), card); });
		}

		actions.append(btn);
		card.append(header, preview, actions);
		return card;
	}

	// ─── Inject <link> tags for font preview ────────────────────
	function injectPreviewFonts(fonts) {
		// Remove any previously injected preview links
		$('link[data-almaden-preview]').remove();

		// Build families in batches of 30 to avoid URL limits
		const batchSize = 30;
		for (let i = 0; i < fonts.length; i += batchSize) {
			const batch = fonts.slice(i, i + batchSize);
			const families = batch.map(function (f) {
				return 'family=' + encodeURIComponent(f.family);
			}).join('&');

			const link = $('<link>')
				.attr('rel', 'stylesheet')
				.attr('data-almaden-preview', 'true')
				.attr('href', 'https://fonts.googleapis.com/css2?' + families + '&display=swap');
			$('head').append(link);
		}
	}

	// ─── Install Font ───────────────────────────────────────────
	function installFont(font, btn, card) {
		btn.prop('disabled', true).text('Instalando...');

		$.post(almadenFonts.ajaxUrl, {
			action: 'almaden_install_font',
			nonce: almadenFonts.nonce,
			family: font.family,
			category: font.category,
			variants: (font.variants || []).join(','),
			subsets: (font.subsets || []).join(','),
		}, function (res) {
			if (res.success) {
				btn.addClass('installed')
					.prop('disabled', false)
					.html('<span class="dashicons dashicons-yes-alt"></span> Instalada')
					.off('click');
				card.addClass('is-installed');
				installedFamilies.push(font.family);
				addInstalledItem(font);
				updateInstalledCount();
			} else {
				btn.prop('disabled', false).html('<span class="dashicons dashicons-download"></span> Instalar');
				alert(res.data);
			}
		});
	}

	// ─── Add to installed list ──────────────────────────────────
	function addInstalledItem(font) {
		$('#almaden-installed-list .almaden-no-fonts').remove();

		const item = $('<div>').addClass('almaden-installed-item').attr('data-family', font.family);
		const info = $('<div>').addClass('almaden-installed-info');
		info.append($('<strong>').text(font.family));
		info.append($('<span>').addClass('almaden-category-badge').text(font.category));

		const btn = $('<button>')
			.addClass('button almaden-uninstall-btn')
			.attr('data-family', font.family)
			.html('<span class="dashicons dashicons-trash"></span> Desinstalar');

		item.append(info, btn);
		$('#almaden-installed-list').append(item);
	}

	// ─── Uninstall Font (delegated) ─────────────────────────────
	$(document).on('click', '.almaden-uninstall-btn', function () {
		const btn = $(this);
		const family = btn.data('family');

		if (!confirm('¿Desinstalar la fuente «' + family + '»?')) return;

		btn.prop('disabled', true).text('Eliminando...');

		$.post(almadenFonts.ajaxUrl, {
			action: 'almaden_uninstall_font',
			nonce: almadenFonts.nonce,
			family: family,
		}, function (res) {
			if (res.success) {
				btn.closest('.almaden-installed-item').slideUp(200, function () { $(this).remove(); updateInstalledCount(); });

				// Update catalog card if visible
				installedFamilies = installedFamilies.filter(function (f) { return f !== family; });
				$('.almaden-font-card.is-installed').each(function () {
					const cardName = $(this).find('.almaden-font-card-name').text();
					if (cardName === family) {
						$(this).removeClass('is-installed');
						const installBtn = $(this).find('.almaden-install-btn');
						installBtn.removeClass('installed')
							.html('<span class="dashicons dashicons-download"></span> Instalar')
							.on('click', function () {
								const fontObj = allFonts.find(function (f) { return f.family === family; });
								if (fontObj) installFont(fontObj, installBtn, $(this).closest('.almaden-font-card'));
							});
					}
				});
			} else {
				btn.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> Desinstalar');
				alert(res.data);
			}
		});
	});

	function updateInstalledCount() {
		const count = $('#almaden-installed-list .almaden-installed-item').length;
		$('#almaden-installed-count').text(count);
		if (count === 0) {
			$('#almaden-installed-list').html('<p class="almaden-no-fonts">No hay fuentes instaladas aún. Explora el catálogo para instalar algunas.</p>');
		}
	}

	// ─── Init ───────────────────────────────────────────────────
	$(document).ready(function () {
		collectInstalledFamilies();
	});

})(jQuery);
