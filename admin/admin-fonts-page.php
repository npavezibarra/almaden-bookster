<?php
/**
 * AlmadenBookster — Admin Google Fonts Page
 *
 * Registers the WP admin menu and renders the Google Fonts
 * management page where admins can search, preview, install
 * and uninstall fonts.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register admin menu and submenu pages.
 */
function almaden_bookster_admin_menu() {
	add_menu_page(
		'AlmadenBookster',
		'AlmadenBookster',
		'almaden_manage_books',
		'almaden-bookster',
		'almaden_bookster_fonts_page_render',
		'dashicons-book-alt',
		26
	);

	add_submenu_page(
		'almaden-bookster',
		'Google APIs',
		'Google APIs',
		'manage_options',
		'almaden-bookster',
		'almaden_bookster_fonts_page_render'
	);
}
add_action( 'admin_menu', 'almaden_bookster_admin_menu' );

/**
 * Enqueue admin assets only on our page.
 */
function almaden_bookster_admin_enqueue( $hook ) {
	if ( strpos( $hook, 'almaden-bookster' ) === false ) {
		return;
	}

	$plugin_url = plugin_dir_url( dirname( __FILE__ ) );

	wp_enqueue_style(
		'almaden-fonts-admin-css',
		$plugin_url . 'assets/css/admin-fonts-page.css',
		array(),
		'1.0.0'
	);

	wp_enqueue_script(
		'almaden-fonts-admin-js',
		$plugin_url . 'assets/js/admin/admin-fonts-page.js',
		array( 'jquery' ),
		'1.0.0',
		true
	);

	wp_localize_script( 'almaden-fonts-admin-js', 'almadenFonts', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'almaden_fonts_nonce' ),
	) );
}
add_action( 'admin_enqueue_scripts', 'almaden_bookster_admin_enqueue' );

/**
 * Render the Google Fonts admin page.
 */
function almaden_bookster_fonts_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) {
		$pages_url = admin_url( 'admin.php?page=almaden-bookster-pages' );
		?>
		<div class="wrap">
			<h1>AlmadenBookster</h1>
			<p>Este panel está disponible para administradores y editores de libros. Usa <strong>Pages</strong> para configurar la ruta del creador interno.</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $pages_url ); ?>">Abrir Pages</a>
			</p>
		</div>
		<?php
		return;
	}

	$api_key         = get_option( 'almaden_google_fonts_api_key', '' );
	$installed_fonts = almaden_bookster_get_installed_fonts_list();

	$gdrive_client_email = get_option( 'bookcraft_gdrive_client_email', '' );
	$gdrive_has_private_key = get_option( 'bookcraft_gdrive_private_key' ) ? true : false;
	$gdrive_folder_id = get_option( 'bookcraft_gdrive_folder_id', '' );
	?>
	<div class="wrap almaden-fonts-wrap">
		<div class="almaden-fonts-header">
			<div class="almaden-fonts-header-title">
				<span class="dashicons dashicons-google"></span>
				<h1>Google APIs — AlmadenBookster</h1>
			</div>
			<p class="almaden-fonts-subtitle">Gestiona las conexiones a los servicios de Google (Drive para exportación de PDFs y Fonts para tipografías).</p>
		</div>

		<!-- API KEY SECTION -->
		<div class="almaden-fonts-card almaden-api-key-card">
			<h2><span class="dashicons dashicons-admin-network"></span> Configuración de API Key</h2>
			<p class="description">Necesitas una API Key gratuita de Google Cloud para acceder al catálogo de fuentes. <a href="https://developers.google.com/fonts/docs/developer_api?hl=es" target="_blank">Obtener clave →</a></p>
			<div class="almaden-api-key-form">
				<input type="text" id="almaden-api-key-input" value="<?php echo esc_attr( $api_key ); ?>" placeholder="Introduce tu Google Fonts API Key..." class="regular-text" />
				<button type="button" id="almaden-save-api-key" class="button button-primary">
					<span class="dashicons dashicons-saved"></span> Guardar Key
				</button>
			</div>
			<div id="almaden-api-key-status"></div>
		</div>

		<!-- GOOGLE DRIVE SECTION -->
		<div class="almaden-fonts-card almaden-api-key-card" style="margin-top: 20px;">
			<h2><span class="dashicons dashicons-cloud-saved"></span> Google Drive Service Account</h2>
			<p class="description">Configura aquí tu Service Account para permitir que BookCraft guarde automáticamente los PDFs en tu Google Drive.</p>
			
			<form id="almaden-gdrive-settings-form" style="margin-top: 15px;">
				<?php wp_nonce_field( 'almaden_gdrive_settings', 'almaden_gdrive_nonce' ); ?>
				
				<table class="form-table" style="margin-bottom: 20px;">
					<tr>
						<th scope="row"><label for="gdrive_client_email">Client Email</label></th>
						<td>
							<input type="email" name="gdrive_client_email" id="gdrive_client_email" value="<?php echo esc_attr( $gdrive_client_email ); ?>" class="regular-text" style="width:100%;" placeholder="e.g. bookcraft@my-project.iam.gserviceaccount.com">
							<p class="description" style="font-size: 13px;">El correo electrónico de tu Service Account de Google Cloud.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gdrive_private_key">Private Key</label></th>
						<td>
							<textarea name="gdrive_private_key" id="gdrive_private_key" rows="5" class="large-text code" style="width:100%;" placeholder="-----BEGIN PRIVATE KEY-----..."><?php echo $gdrive_has_private_key ? '***PROTECTED***' : ''; ?></textarea>
							<p class="description" style="font-size: 13px;">Copia y pega toda la llave privada. Por seguridad, no se mostrará después de guardarla.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gdrive_folder_id">Folder ID</label></th>
						<td>
							<input type="text" name="gdrive_folder_id" id="gdrive_folder_id" value="<?php echo esc_attr( $gdrive_folder_id ); ?>" class="regular-text" style="width:100%;">
							<p class="description" style="font-size: 13px;">El ID de la carpeta principal de Google Drive compartida con el Client Email.</p>
						</td>
					</tr>
				</table>

				<p class="submit" style="padding: 0; margin: 0;">
					<button type="submit" class="button button-primary" id="save-gdrive-settings">
						<span class="dashicons dashicons-saved"></span> Guardar Drive
					</button>
					<button type="button" class="button button-secondary" id="test-gdrive-connection" style="margin-left:10px;">
						<span class="dashicons dashicons-update"></span> Probar Conexión
					</button>
					<span class="spinner" id="gdrive-spinner"></span>
				</p>
				
				<div id="gdrive-message" style="margin-top:15px; padding:10px; display:none;"></div>
			</form>
		</div>

		<!-- SEARCH & CATALOG SECTION -->
		<div class="almaden-fonts-card">
			<h2><span class="dashicons dashicons-search"></span> Explorar Catálogo de Google Fonts</h2>
			<div class="almaden-search-bar">
				<input type="text" id="almaden-font-search" placeholder="Buscar fuentes por nombre..." class="regular-text" />
				<select id="almaden-font-sort">
					<option value="popularity">Popularidad</option>
					<option value="trending">Tendencia</option>
					<option value="alpha">Alfabético</option>
					<option value="date">Más recientes</option>
					<option value="style">Estilos</option>
				</select>
				<button type="button" id="almaden-load-fonts" class="button button-primary">
					<span class="dashicons dashicons-download"></span> Cargar Catálogo
				</button>
			</div>
			<div id="almaden-fonts-grid" class="almaden-fonts-grid">
				<div class="almaden-fonts-empty">
					<span class="dashicons dashicons-editor-textcolor"></span>
					<p>Haz clic en <strong>"Cargar Catálogo"</strong> para explorar las fuentes disponibles.</p>
				</div>
			</div>
			<div id="almaden-fonts-loading" class="almaden-fonts-loading" style="display:none;">
				<span class="spinner is-active"></span> Cargando fuentes...
			</div>
		</div>

		<!-- INSTALLED FONTS SECTION -->
		<div class="almaden-fonts-card almaden-installed-card">
			<h2><span class="dashicons dashicons-yes-alt"></span> Fuentes Instaladas <span id="almaden-installed-count" class="almaden-badge"><?php echo count( $installed_fonts ); ?></span></h2>
			<p class="description">Estas fuentes estarán disponibles en los selectores de tipografía del editor BookCraft.</p>
			<div id="almaden-installed-list" class="almaden-installed-list">
				<?php if ( empty( $installed_fonts ) ) : ?>
					<p class="almaden-no-fonts">No hay fuentes instaladas aún. Explora el catálogo para instalar algunas.</p>
				<?php else : ?>
					<?php foreach ( $installed_fonts as $font ) : ?>
						<div class="almaden-installed-item" data-family="<?php echo esc_attr( $font['family'] ); ?>">
							<div class="almaden-installed-info">
								<strong><?php echo esc_html( $font['family'] ); ?></strong>
								<span class="almaden-category-badge"><?php echo esc_html( $font['category'] ); ?></span>
							</div>
							<button type="button" class="button almaden-uninstall-btn" data-family="<?php echo esc_attr( $font['family'] ); ?>">
								<span class="dashicons dashicons-trash"></span> Desinstalar
							</button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<script>
	jQuery(document).ready(function($) {
		$('#almaden-gdrive-settings-form').on('submit', function(e) {
			e.preventDefault();
			$('#gdrive-spinner').addClass('is-active');
			$('#gdrive-message').hide();
			
			var data = {
				action: 'almaden_save_gdrive_settings',
				_wpnonce: $('#almaden_gdrive_nonce').val(),
				gdrive_client_email: $('#gdrive_client_email').val(),
				gdrive_private_key: $('#gdrive_private_key').val(),
				gdrive_folder_id: $('#gdrive_folder_id').val()
			};
			
			$.post(ajaxurl, data, function(response) {
				$('#gdrive-spinner').removeClass('is-active');
				$('#gdrive-message').show().removeClass('notice-error notice-success');
				if (response.success) {
					$('#gdrive-message').addClass('notice notice-success').html('<p>' + response.data + '</p>');
					if ($('#gdrive_private_key').val() && $('#gdrive_private_key').val() !== '***PROTECTED***') {
						$('#gdrive_private_key').val('***PROTECTED***');
					}
				} else {
					$('#gdrive-message').addClass('notice notice-error').html('<p>' + response.data + '</p>');
				}
			});
		});

		$('#test-gdrive-connection').on('click', function(e) {
			e.preventDefault();
			$('#gdrive-spinner').addClass('is-active');
			$('#gdrive-message').hide();
			
			var data = {
				action: 'almaden_test_gdrive_connection',
				_wpnonce: $('#almaden_gdrive_nonce').val()
			};
			
			$.post(ajaxurl, data, function(response) {
				$('#gdrive-spinner').removeClass('is-active');
				$('#gdrive-message').show().removeClass('notice-error notice-success');
				if (response.success) {
					$('#gdrive-message').addClass('notice notice-success').html('<p><strong>¡Conexión Exitosa!</strong> ' + response.data + '</p>');
				} else {
					$('#gdrive-message').addClass('notice notice-error').html('<p><strong>Error de Conexión:</strong> ' + response.data + '</p>');
				}
			}).fail(function() {
				$('#gdrive-spinner').removeClass('is-active');
				$('#gdrive-message').show().addClass('notice notice-error').html('<p>Error interno del servidor al intentar probar la conexión.</p>');
			});
		});
	});
	</script>
	<?php
}
