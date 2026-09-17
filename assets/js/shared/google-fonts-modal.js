/**
 * AlmadenBookster — Google Fonts Floating Modal JS
 *
 * Provides live search, visual preview, per-user installation,
 * and automatic synchronization with font selectors across editors.
 *
 * @package AlmadenBookster
 */

(function ($) {
	'use strict';

	window.AlmadenGoogleFontsModal = {
		allCatalogFonts: [],
		installedFonts: [],
		activeTriggerSelect: null,
		hasLoadedCatalog: false,

		init: function () {
			this.bindEvents();
			this.collectInstalledFonts();
			this.updateAllFontSelectors();
		},


		bindEvents: function () {
			const self = this;

			// Close modal
			$(document).on('click', '#almaden-modal-fonts-close, #almaden-modal-fonts-done-btn', function () {
				self.close();
			});

			// Close on backdrop click
			$(document).on('click', '#almaden-google-fonts-modal', function (e) {
				if ($(e.target).is('#almaden-google-fonts-modal')) {
					self.close();
				}
			});

			// ESC key close
			$(document).on('keydown', function (e) {
				if (e.key === 'Escape' && $('#almaden-google-fonts-modal').is(':visible')) {
					self.close();
				}
			});

			// Load Catalog button
			$(document).on('click', '#almaden-modal-load-catalog', function () {
				self.loadCatalog();
			});

			// Search input filter
			$(document).on('input', '#almaden-modal-font-search', function () {
				self.filterCatalog();
			});

			// Sort select change
			$(document).on('change', '#almaden-modal-font-sort', function () {
				if (self.hasLoadedCatalog) {
					self.loadCatalog();
				}
			});

			// Automatically intercept font select dropdown changes
			$(document).on('change', 'select', function () {
				const val = $(this).val();
				if (val === '__add_google_font__') {
					// Revert select value to previous option
					const prevVal = $(this).data('prev-font-value') || $(this).find('option:first-child').val();
					$(this).val(prevVal);

					self.open(this);
				} else {
					$(this).data('prev-font-value', val);
				}
			});

			// Store initial value on focus/click for dropdowns
			$(document).on('focus click', 'select', function () {
				if (!$(this).data('prev-font-value')) {
					$(this).data('prev-font-value', $(this).val());
				}
			});
		},

		collectInstalledFonts: function () {
			if (window.coverData && Array.isArray(window.coverData.installedFonts)) {
				this.installedFonts = window.coverData.installedFonts;
			} else if (window.bookState && Array.isArray(window.bookState.installedFonts)) {
				this.installedFonts = window.bookState.installedFonts;
			} else if (window.almadenInstalledFonts && Array.isArray(window.almadenInstalledFonts)) {
				this.installedFonts = window.almadenInstalledFonts;
			}
		},


		open: function (triggerSelect) {
			this.activeTriggerSelect = triggerSelect || null;
			this.collectInstalledFonts();
			this.renderUserFontsList();

			$('#almaden-google-fonts-modal').css('display', 'flex').removeClass('hidden');

			if (!this.hasLoadedCatalog) {
				this.loadCatalog();
			} else {
				this.filterCatalog();
			}
		},

		close: function () {
			$('#almaden-google-fonts-modal').hide().addClass('hidden');
			this.activeTriggerSelect = null;
		},

		getAjaxConfig: function () {
			let ajaxUrl = (window.almadenFonts && window.almadenFonts.ajaxUrl)
				|| (window.coverData && window.coverData.ajaxUrl)
				|| (window.bookState && window.bookState.ajaxUrl)
				|| (window.almadenEditorData && window.almadenEditorData.ajaxUrl)
				|| '/wp-admin/admin-ajax.php';

			let nonce = (window.almadenFonts && window.almadenFonts.nonce)
				|| (window.coverData && window.coverData.fontsNonce)
				|| (window.bookState && window.bookState.fontsNonce)
				|| (window.coverData && window.coverData.nonce)
				|| (window.bookState && window.bookState.nonce)
				|| '';

			return { ajaxUrl: ajaxUrl, nonce: nonce };
		},

		loadCatalog: function () {
			const self = this;
			const config = this.getAjaxConfig();
			const sort = $('#almaden-modal-font-sort').val() || 'popularity';

			$('#almaden-modal-fonts-grid').addClass('hidden');
			$('#almaden-modal-fonts-loading').removeClass('hidden').addClass('flex');

			$.post(config.ajaxUrl, {
				action: 'almaden_search_google_fonts',
				nonce: config.nonce,
				sort: sort
			}, function (res) {
				$('#almaden-modal-fonts-loading').addClass('hidden').removeClass('flex');
				$('#almaden-modal-fonts-grid').removeClass('hidden');

				if (res.success && Array.isArray(res.data)) {
					self.allCatalogFonts = res.data;
					self.hasLoadedCatalog = true;
					self.filterCatalog();
				} else {
					$('#almaden-modal-fonts-grid').html(
						'<div class="col-span-full text-center py-8 text-red-500 font-medium">' +
						(res.data || 'Error al cargar el catálogo de fuentes.') +
						'</div>'
					);
				}
			}).fail(function (xhr) {
				$('#almaden-modal-fonts-loading').addClass('hidden').removeClass('flex');
				let errorMsg = 'Error de conexión con el servidor.';
				if (xhr && xhr.responseJSON && xhr.responseJSON.data) {
					errorMsg = xhr.responseJSON.data;
				} else if (xhr && xhr.responseText && xhr.responseText !== '-1' && xhr.responseText !== '0') {
					errorMsg = xhr.responseText;
				} else if (xhr && (xhr.status === 403 || xhr.responseText === '-1')) {
					errorMsg = 'Error de seguridad (nonce expirado o inválido). Por favor recarga la página.';
				}
				$('#almaden-modal-fonts-grid').removeClass('hidden').html(
					'<div class="col-span-full text-center py-8 text-red-500 font-medium">' + errorMsg + '</div>'
				);
			});
		},


		filterCatalog: function () {
			const query = $('#almaden-modal-font-search').val().toLowerCase().trim();
			if (!this.allCatalogFonts.length) return;

			let filtered = this.allCatalogFonts;
			if (query) {
				filtered = this.allCatalogFonts.filter(function (f) {
					return f.family.toLowerCase().indexOf(query) !== -1;
				});
			}

			this.renderCatalogGrid(filtered);
		},

		renderCatalogGrid: function (fonts) {
			const grid = $('#almaden-modal-fonts-grid');
			grid.empty();

			if (!fonts.length) {
				grid.html('<div class="col-span-full text-center py-8 text-gray-400">No se encontraron fuentes con ese nombre.</div>');
				return;
			}

			// Limit to 100 for visual performance
			const displayFonts = fonts.slice(0, 100);
			this.injectPreviewStylesheet(displayFonts);

			const installedFamilies = this.installedFonts.map(function (f) {
				return typeof f === 'string' ? f.toLowerCase() : (f.family || '').toLowerCase();
			});

			const self = this;
			displayFonts.forEach(function (font) {
				const isInstalled = installedFamilies.indexOf(font.family.toLowerCase()) !== -1;
				const card = self.buildFontCard(font, isInstalled);
				grid.append(card);
			});
		},

		buildFontCard: function (font, isInstalled) {
			const self = this;
			const card = $('<div>').addClass('p-4 border border-gray-200 rounded-xl bg-white hover:border-gray-400 hover:shadow-md transition flex flex-col justify-between gap-3');

			const top = $('<div>').addClass('flex items-center justify-between');
			top.append($('<span>').addClass('font-bold text-gray-900 text-sm').text(font.family));
			top.append($('<span>').addClass('px-2 py-0.5 text-[10px] uppercase font-semibold bg-gray-100 text-gray-600 rounded-md').text(font.category || 'font'));

			const preview = $('<div>')
				.addClass('text-lg py-2 text-gray-800 border-y border-dashed border-gray-100 overflow-hidden text-ellipsis whitespace-nowrap')
				.css('font-family', "'" + font.family + "', " + (font.category || 'sans-serif'))
				.text('El veloz murciélago hindú 123');

			const actions = $('<div>').addClass('flex items-center justify-between pt-1');
			const variantsCount = (font.variants || []).length;
			actions.append($('<span>').addClass('text-[11px] text-gray-400').text(variantsCount ? variantsCount + ' estilos' : 'Standard'));

			const btn = $('<button>').attr('type', 'button').addClass('px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5');

			if (isInstalled) {
				btn.addClass('bg-emerald-50 text-emerald-700 border border-emerald-200 cursor-default')
					.html('<i class="fa-solid fa-circle-check text-xs"></i> <span>Instalada</span>');
			} else {
				btn.addClass('bg-black hover:bg-neutral-800 text-white shadow-sm')
					.html('<i class="fa-solid fa-plus text-xs"></i> <span>Instalar</span>')
					.on('click', function () {
						self.installFont(font, $(this), card);
					});
			}

			actions.append(btn);
			card.append(top, preview, actions);
			return card;
		},

		injectPreviewStylesheet: function (fonts) {
			$('link[data-almaden-modal-preview]').remove();

			const batchSize = 25;
			for (let i = 0; i < fonts.length; i += batchSize) {
				const batch = fonts.slice(i, i + batchSize);
				const families = batch.map(function (f) {
					return 'family=' + encodeURIComponent(f.family);
				}).join('&');

				const link = $('<link>')
					.attr('rel', 'stylesheet')
					.attr('data-almaden-modal-preview', 'true')
					.attr('href', 'https://fonts.googleapis.com/css2?' + families + '&display=swap');
				$('head').append(link);
			}
		},

		installFont: function (font, btn, card) {
			const self = this;
			const config = this.getAjaxConfig();

			btn.prop('disabled', true).html('<i class="fa-solid fa-spinner animate-spin"></i> <span>Guardando...</span>');

			$.post(config.ajaxUrl, {
				action: 'almaden_install_font',
				nonce: config.nonce,
				family: font.family,
				category: font.category || 'serif',
				variants: Array.isArray(font.variants) ? font.variants.join(',') : (font.variants || ''),
				subsets: Array.isArray(font.subsets) ? font.subsets.join(',') : (font.subsets || '')
			}, function (res) {
				if (res.success) {
					btn.removeClass('bg-black hover:bg-neutral-800 text-white shadow-sm')
						.addClass('bg-emerald-50 text-emerald-700 border border-emerald-200 cursor-default')
						.prop('disabled', false)
						.html('<i class="fa-solid fa-circle-check text-xs"></i> <span>Instalada</span>')
						.off('click');


					// Dynamically load Google Font CSS into page document
					self.loadGoogleFontStylesheet(font.family);

					// Record in local array
					self.installedFonts.push({
						family: font.family,
						category: font.category || 'serif',
						source: res.data && res.data.scope === 'user' ? 'user' : 'installed'
					});

					// Refresh dropdowns and auto-select
					self.updateAllFontSelectors(font.family);
					self.renderUserFontsList();
				} else {
					btn.prop('disabled', false).html('<i class="fa-solid fa-plus text-xs"></i> <span>Instalar</span>');
					alert(res.data || 'Error al instalar la fuente.');
				}
			}).fail(function () {
				btn.prop('disabled', false).html('<i class="fa-solid fa-plus text-xs"></i> <span>Instalar</span>');
				alert('Error de conexión al instalar la fuente.');
			});
		},

		uninstallFont: function (family) {
			const self = this;
			const config = this.getAjaxConfig();

			if (!confirm('¿Eliminar «' + family + '» de tus fuentes instaladas?')) return;

			$.post(config.ajaxUrl, {
				action: 'almaden_uninstall_font',
				nonce: config.nonce,
				family: family
			}, function (res) {
				if (res.success) {
					self.installedFonts = self.installedFonts.filter(function (f) {
						const fName = typeof f === 'string' ? f : f.family;
						return fName.toLowerCase() !== family.toLowerCase();
					});

					self.renderUserFontsList();
					self.filterCatalog();
					self.updateAllFontSelectors();
				} else {
					alert(res.data || 'Error al desinstalar la fuente.');
				}
			});
		},

		loadGoogleFontStylesheet: function (family) {
			const fontSlug = family.replace(/ /g, '+');
			const fontUrl = 'https://fonts.googleapis.com/css2?family=' + fontSlug + ':ital,wght@0,400;0,700;1,400&display=swap';
			if (!$('link[href="' + fontUrl + '"]').length) {
				$('head').append('<link rel="stylesheet" href="' + fontUrl + '">');
			}
		},

		renderUserFontsList: function () {
			const container = $('#almaden-modal-user-fonts-list, #tab-typography-user-fonts-list');
			container.empty();

			if (!this.installedFonts.length) {
				container.html('<span class="text-xs text-gray-400 italic">Aún no has instalado fuentes adicionales.</span>');
				return;
			}

			const self = this;
			this.installedFonts.forEach(function (font) {
				const family = typeof font === 'string' ? font : font.family;
				const category = typeof font === 'string' ? 'font' : (font.category || 'font');

				const badge = $('<div>').addClass('inline-flex items-center gap-1.5 px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium border border-gray-200');
				badge.append($('<span>').text(family));
				badge.append($('<span>').addClass('text-[10px] text-gray-400 uppercase').text('(' + category + ')'));

				const delBtn = $('<button>')
					.attr('type', 'button')
					.addClass('text-gray-400 hover:text-red-600 transition ml-1')
					.html('<i class="fa-solid fa-xmark"></i>')
					.on('click', function () {
						self.uninstallFont(family);
					});

				badge.append(delBtn);
				container.append(badge);
			});
		},

		updateAllFontSelectors: function (selectedFamily) {
			const self = this;

			// Sync with coverData or global objects if present
			if (window.coverData) {
				window.coverData.installedFonts = self.installedFonts;
			}
			if (window.almadenInstalledFonts) {
				window.almadenInstalledFonts = self.installedFonts;
			}

			// Find all select elements that contain font options or have typography IDs
			const fontSelects = $('select').filter(function () {
				const id = (this.id || '').toLowerCase();
				const name = (this.name || '').toLowerCase();
				return id.indexOf('font') !== -1 || name.indexOf('font') !== -1 || $(this).find('option[value="__add_google_font__"]').length > 0;
			});

			fontSelects.each(function () {
				const select = $(this);
				const currentVal = select.val();

				// Ensure __add_google_font__ is at the end
				let hasAddOption = select.find('option[value="__add_google_font__"]').length > 0;

				// Append new installed fonts if missing
				self.installedFonts.forEach(function (f) {
					const family = typeof f === 'string' ? f : f.family;
					if (select.find('option[value="' + family + '"]').length === 0) {
						let opt = $('<option>').val(family).text(family);
						if (hasAddOption) {
							select.find('option[value="__add_google_font__"]').before(opt);
						} else {
							select.append(opt);
						}
					}
				});

				if (!hasAddOption) {
					select.append('<option value="__add_google_font__">+ Explorar Google Fonts...</option>');
				}
			});

			// Auto select the newly installed font on active trigger select
			if (selectedFamily && self.activeTriggerSelect) {
				const $trig = $(self.activeTriggerSelect);
				$trig.val(selectedFamily).trigger('change');
				$trig.data('prev-font-value', selectedFamily);
			}
		}
	};

	$(document).ready(function () {
		window.AlmadenGoogleFontsModal.init();
	});

})(jQuery);
