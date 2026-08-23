<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$contractor_settings = function_exists( 'almaden_bookster_get_contractor_settings' ) ? almaden_bookster_get_contractor_settings() : array();
$company_name = isset( $contractor_settings['company_name'] ) ? (string) $contractor_settings['company_name'] : '';
$logo_id = isset( $contractor_settings['logo_id'] ) ? absint( $contractor_settings['logo_id'] ) : 0;
$logo_width = function_exists( 'almaden_bookster_get_contractor_logo_width' ) ? absint( almaden_bookster_get_contractor_logo_width() ) : 160;
if ( $logo_width < 40 ) {
	$logo_width = 40;
}
if ( $logo_width > 300 ) {
	$logo_width = 300;
}
$logo_url = $logo_id > 0 && function_exists( 'wp_get_attachment_image_url' ) ? (string) wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
$logo_alt = '' !== trim( $company_name ) ? $company_name : 'Logo del shell';
$company_name_preview = '' !== trim( $company_name ) ? $company_name : 'Sin nombre de la empresa';

if ( function_exists( 'almaden_bookster_render_app_shell_start' ) ) {
	almaden_bookster_render_app_shell_start(
		array(
			'title'          => 'Contractor - Almaden',
			'body_id'        => 'almaden-contractor-body',
			'active_nav_key' => '',
		)
	);
}
?>
<main class="mx-auto w-full max-w-5xl px-6 pb-12 pt-8">
	<div class="mb-8 max-w-3xl">
		<p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-400">Almaden Shell</p>
		<h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950">Contractor</h1>
		<p class="mt-4 text-lg leading-8 text-slate-600">Aquí configuramos la marca de la empresa que instala Almaden Bookster. Este logo y este nombre se usan en el navbar del shell para todas las páginas internas.</p>
	</div>

	<?php if ( isset( $_GET['settings-updated'] ) && '1' === (string) $_GET['settings-updated'] ) : ?>
		<div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900">
			Se guardó la configuración del contractor correctamente.
		</div>
	<?php endif; ?>

	<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
		<section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-6">
				<input type="hidden" name="action" value="almaden_bookster_save_contractor_settings" />
				<?php wp_nonce_field( 'almaden_bookster_contractor_settings', 'almaden_contractor_nonce' ); ?>

				<div class="space-y-2">
					<label for="contractor_company_name" class="block text-sm font-semibold text-slate-900">Nombre de la empresa</label>
					<input id="contractor_company_name" name="company_name" type="text" value="<?php echo esc_attr( $company_name ); ?>" placeholder="Nombre de la empresa" class="w-full rounded-2xl border border-slate-300 bg-transparent px-4 py-3 text-base text-slate-900 outline-none transition focus:border-slate-900 focus:ring-2 focus:ring-slate-200" />
					<p class="text-sm text-slate-500">Este nombre se muestra como respaldo de marca y ayuda a identificar quién instaló o administra el shell.</p>
				</div>

				<div class="space-y-3">
					<div class="flex items-center justify-between gap-4">
						<div>
							<p class="text-sm font-semibold text-slate-900">Logo del shell</p>
							<p class="text-sm text-slate-500">Se mostrará en el navbar de todas las páginas del shell.</p>
						</div>
						<button type="button" id="contractor-select-logo" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Seleccionar logo</button>
					</div>

					<input type="hidden" id="contractor_logo_id" name="logo_id" value="<?php echo esc_attr( (string) $logo_id ); ?>" />

					<div class="flex items-center gap-4 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-4">
						<div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200">
							<img
								id="contractor-logo-preview"
								src="<?php echo esc_url( '' !== $logo_url ? $logo_url : 'data:image/gif;base64,R0lGODlhAQABAAAAACw=' ); ?>"
								alt="<?php echo esc_attr( $logo_alt ); ?>"
								class="<?php echo '' !== $logo_url ? 'h-full w-full object-contain' : 'hidden'; ?>"
							/>
							<span id="contractor-logo-placeholder" class="<?php echo '' === $logo_url ? 'text-xs font-semibold uppercase tracking-[0.22em] text-slate-400' : 'hidden'; ?>">Sin logo</span>
						</div>
						<div class="min-w-0 flex-1">
							<p class="text-sm font-semibold text-slate-900"><?php echo esc_html( '' !== $company_name ? $company_name : 'Nombre de la empresa' ); ?></p>
							<p class="mt-1 text-sm text-slate-500">Usamos este logo como identidad visual del shell. Puedes cambiarlo cuando quieras.</p>
							<button type="button" id="contractor-remove-logo" class="mt-3 text-sm font-semibold text-rose-600 transition hover:text-rose-700 <?php echo '' !== $logo_url ? '' : 'hidden'; ?>">Quitar logo</button>
						</div>
					</div>
				</div>

				<div class="space-y-3">
					<div class="flex items-center justify-between gap-4">
						<label for="contractor_logo_width" class="block text-sm font-semibold text-slate-900">Ancho del logo</label>
						<span id="contractor-logo-width-value" class="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700"><?php echo esc_html( (string) $logo_width ); ?>px</span>
					</div>
					<input
						id="contractor_logo_width"
						name="logo_width"
						type="range"
						min="40"
						max="300"
						step="1"
						value="<?php echo esc_attr( (string) $logo_width ); ?>"
						class="w-full accent-slate-900"
					/>
					<p class="text-sm text-slate-500">Controla el ancho del logo en el navbar del shell. Puedes moverlo entre 40px y 300px.</p>
				</div>

				<div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4">
					<button type="submit" class="rounded-full bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">Guardar cambios</button>
					<span class="text-sm text-slate-500">Al guardar, el logo del shell se actualiza en todas las páginas internas.</span>
				</div>
			</form>
		</section>

		<aside class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
			<p class="text-[11px] font-semibold uppercase tracking-[0.28em] text-slate-400">Vista previa</p>
			<div class="mt-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
						<div class="flex items-center gap-3">
					<?php if ( '' !== $logo_url ) : ?>
						<img
							id="contractor-shell-logo-preview"
							src="<?php echo esc_url( $logo_url ); ?>"
							alt="<?php echo esc_attr( $logo_alt ); ?>"
							class="h-10 w-auto object-contain object-left"
							style="width: <?php echo esc_attr( (string) $logo_width ); ?>px; max-width: <?php echo esc_attr( (string) $logo_width ); ?>px;"
						/>
					<?php endif; ?>
					<div>
						<p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">Shell navbar</p>
						<p class="mt-1 text-xl font-black tracking-tight text-slate-950"><?php echo esc_html( $company_name_preview ); ?></p>
					</div>
				</div>
			</div>
		</aside>
	</div>
</main>

<script>
(function () {
	const selectButton = document.getElementById('contractor-select-logo');
	const removeButton = document.getElementById('contractor-remove-logo');
	const logoIdField = document.getElementById('contractor_logo_id');
	const logoWidthField = document.getElementById('contractor_logo_width');
	const logoWidthValue = document.getElementById('contractor-logo-width-value');
	const previewImage = document.getElementById('contractor-logo-preview');
	const shellLogoPreview = document.getElementById('contractor-shell-logo-preview');
	const placeholder = document.getElementById('contractor-logo-placeholder');
	if (!selectButton || !logoIdField) {
		return;
	}

	function syncLogoWidth(width) {
		const px = Math.max(40, Math.min(300, parseInt(width, 10) || 160));
		if (logoWidthValue) {
			logoWidthValue.textContent = px + 'px';
		}
		if (shellLogoPreview) {
			shellLogoPreview.style.width = px + 'px';
			shellLogoPreview.style.maxWidth = px + 'px';
		}
		if (logoWidthField) {
			logoWidthField.value = String(px);
		}
	}

	function setPreview(url, alt) {
		if (!previewImage || !placeholder) return;
		if (url) {
			previewImage.src = url;
			previewImage.alt = alt || 'Logo';
			previewImage.classList.remove('hidden');
			placeholder.classList.add('hidden');
			if (removeButton) removeButton.classList.remove('hidden');
		} else {
			previewImage.removeAttribute('src');
			previewImage.classList.add('hidden');
			placeholder.classList.remove('hidden');
			if (removeButton) removeButton.classList.add('hidden');
		}
	}

	selectButton.addEventListener('click', function () {
		if (typeof wp === 'undefined' || !wp.media) {
			return;
		}

		const frame = wp.media({
			title: 'Seleccionar logo del contractor',
			button: { text: 'Usar este logo' },
			multiple: false,
		});

		frame.on('select', function () {
			const attachment = frame.state().get('selection').first().toJSON();
			if (!attachment || !attachment.id) {
				return;
			}

			logoIdField.value = String(attachment.id);
			const url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : (attachment.url || '');
			setPreview(url, attachment.alt || attachment.title || 'Logo');
		});

		frame.open();
	});

	if (removeButton) {
		removeButton.addEventListener('click', function () {
			logoIdField.value = '0';
			setPreview('', '');
		});
	}

	if (logoWidthField) {
		syncLogoWidth(logoWidthField.value);
		logoWidthField.addEventListener('input', function () {
			syncLogoWidth(logoWidthField.value);
		});
	}
})();
</script>
<?php
if ( function_exists( 'almaden_bookster_render_app_shell_end' ) ) {
	almaden_bookster_render_app_shell_end();
}
