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
		'manage_options',
		'almaden-bookster',
		'almaden_bookster_fonts_page_render',
		'dashicons-book-alt',
		26
	);

	add_submenu_page(
		'almaden-bookster',
		'Google Fonts',
		'Google Fonts',
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
		$plugin_url . 'assets/js/admin-fonts-page.js',
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
	$api_key         = get_option( 'almaden_google_fonts_api_key', '' );
	$installed_fonts = almaden_bookster_get_installed_fonts_list();
	?>
	<div class="wrap almaden-fonts-wrap">
		<div class="almaden-fonts-header">
			<div class="almaden-fonts-header-title">
				<span class="dashicons dashicons-editor-textcolor"></span>
				<h1>Google Fonts — AlmadenBookster</h1>
			</div>
			<p class="almaden-fonts-subtitle">Busca, previsualiza e instala tipografías de Google Fonts para usar en la maquetación PDF de tus libros.</p>
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
	<?php
}
