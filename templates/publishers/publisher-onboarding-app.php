<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$onboarding_status  = isset( $_GET['onboarding_status'] ) ? sanitize_key( wp_unslash( $_GET['onboarding_status'] ) ) : '';
$onboarding_message = isset( $_GET['onboarding_message'] ) ? sanitize_text_field( wp_unslash( $_GET['onboarding_message'] ) ) : '';
$login_url          = wp_login_url( almaden_bookster_get_publisher_onboarding_url() );
$taller_url         = almaden_bookster_get_creator_page_url();
$css_file           = dirname( __DIR__, 2 ) . '/assets/css/publishers/publisher-onboarding.css';
$js_file            = dirname( __DIR__, 2 ) . '/assets/js/publishers/publisher-onboarding.js';

$extra_head_html = sprintf(
	'<link rel="stylesheet" href="%1$s"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">',
	esc_url( plugins_url( '../../assets/css/publishers/publisher-onboarding.css', __FILE__ ) ) . '?v=' . esc_attr( file_exists( $css_file ) ? (string) filemtime( $css_file ) : '1' )
);

almaden_bookster_render_app_shell_start(
	array(
		'title'           => almaden_bookster_get_publisher_onboarding_title() . ' - Almaden Bookster',
		'body_id'         => 'almaden-publisher-onboarding-body',
		'active_nav_key'  => 'publisher',
		'extra_head_html' => $extra_head_html,
	)
);
?>
<main id="almaden-publisher-onboarding" class="almaden-app-content-shell">
	<div class="almaden-topbar">
		<div class="almaden-brand">
			<span class="almaden-brand-mark">A</span>
			<span>almaden bookster</span>
		</div>
	</div>

	<div class="almaden-layout">
		<section class="almaden-hero">
			<span class="almaden-kicker"><?php esc_html_e( 'Alta de editorial', 'almaden-bookster' ); ?></span>
			<h1><?php esc_html_e( 'Crea tu editorial y entra al taller en un solo flujo.', 'almaden-bookster' ); ?></h1>
			<p><?php esc_html_e( 'Este onboarding está pensado para convertir visitas en cuentas activas: recogemos los datos mínimos, dejamos tu editorial lista y luego te llevamos al taller para crear tu primer libro.', 'almaden-bookster' ); ?></p>

			<ul class="almaden-benefits">
				<li><div><strong><?php esc_html_e( 'Alta guiada', 'almaden-bookster' ); ?></strong><span><?php esc_html_e( 'El formulario está dividido en pasos cortos para reducir fricción y mantener el foco.', 'almaden-bookster' ); ?></span></div></li>
				<li><div><strong><?php esc_html_e( 'Cuenta + editorial', 'almaden-bookster' ); ?></strong><span><?php esc_html_e( 'Creamos el usuario, la editorial y la membresía de propietario en una sola operación.', 'almaden-bookster' ); ?></span></div></li>
				<li><div><strong><?php esc_html_e( 'Listo para publicar', 'almaden-bookster' ); ?></strong><span><?php esc_html_e( 'Al terminar, accedes al taller con permisos para empezar tu primer libro de inmediato.', 'almaden-bookster' ); ?></span></div></li>
			</ul>
		</section>

		<section class="almaden-card">
			<?php if ( 'error' === $onboarding_status && '' !== $onboarding_message ) : ?>
				<div class="almaden-alert almaden-alert--error"><?php echo esc_html( $onboarding_message ); ?></div>
			<?php elseif ( 'success' === $onboarding_status ) : ?>
				<div class="almaden-alert almaden-alert--success"><?php esc_html_e( 'Tu editorial fue creada correctamente.', 'almaden-bookster' ); ?></div>
			<?php endif; ?>

			<form id="publisher-onboarding-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="almaden_create_publisher" />
				<?php wp_nonce_field( 'almaden_publisher_onboarding', 'almaden_publisher_onboarding_nonce' ); ?>

				<div class="almaden-stepper" role="tablist" aria-label="<?php esc_attr_e( 'Pasos del onboarding', 'almaden-bookster' ); ?>">
					<button type="button" class="is-active" data-step-nav="1"><span class="almaden-step-number">Paso 1</span><span class="almaden-step-label"><?php esc_html_e( 'Editorial', 'almaden-bookster' ); ?></span></button>
					<button type="button" data-step-nav="2"><span class="almaden-step-number">Paso 2</span><span class="almaden-step-label"><?php esc_html_e( 'Contacto', 'almaden-bookster' ); ?></span></button>
					<button type="button" data-step-nav="3"><span class="almaden-step-number">Paso 3</span><span class="almaden-step-label"><?php esc_html_e( 'Cuenta', 'almaden-bookster' ); ?></span></button>
					<button type="button" data-step-nav="4"><span class="almaden-step-number">Paso 4</span><span class="almaden-step-label"><?php esc_html_e( 'Revisar', 'almaden-bookster' ); ?></span></button>
				</div>

				<section class="almaden-step is-active" data-step="1">
					<div class="almaden-grid">
						<div class="almaden-field">
							<label for="publisher_name"><?php esc_html_e( 'Nombre editorial', 'almaden-bookster' ); ?></label>
							<input id="publisher_name" name="publisher_name" type="text" required />
							<p class="almaden-field-help"><?php esc_html_e( 'El nombre que verán tus lectores y tu equipo.', 'almaden-bookster' ); ?></p>
						</div>
						<div class="almaden-field">
							<label for="publisher_legal_name"><?php esc_html_e( 'Razón social', 'almaden-bookster' ); ?></label>
							<input id="publisher_legal_name" name="publisher_legal_name" type="text" />
						</div>
					</div>
					<div class="almaden-grid">
						<div class="almaden-field">
							<label for="publisher_rut"><?php esc_html_e( 'RUT', 'almaden-bookster' ); ?></label>
							<input id="publisher_rut" name="publisher_rut" type="text" />
						</div>
						<div class="almaden-field">
							<label for="publisher_keywords"><?php esc_html_e( 'Keywords', 'almaden-bookster' ); ?></label>
							<input id="publisher_keywords" name="publisher_keywords" type="text" placeholder="<?php esc_attr_e( 'ficción, ensayo, infantil', 'almaden-bookster' ); ?>" />
						</div>
					</div>
					<div class="almaden-field">
						<label for="publisher_description"><?php esc_html_e( 'Descripción', 'almaden-bookster' ); ?></label>
						<textarea id="publisher_description" name="publisher_description" placeholder="<?php esc_attr_e( 'Cuéntanos qué tipo de catálogo publicas y cuál es tu propuesta.', 'almaden-bookster' ); ?>"></textarea>
					</div>
				</section>

				<section class="almaden-step" data-step="2">
					<div class="almaden-grid">
						<div class="almaden-field">
							<label for="contact_name"><?php esc_html_e( 'Nombre de contacto', 'almaden-bookster' ); ?></label>
							<input id="contact_name" name="contact_name" type="text" required />
						</div>
						<div class="almaden-field">
							<label for="publisher_phone"><?php esc_html_e( 'Teléfono', 'almaden-bookster' ); ?></label>
							<input id="publisher_phone" name="publisher_phone" type="text" />
						</div>
					</div>
					<div class="almaden-grid">
						<div class="almaden-field">
							<label for="publisher_email"><?php esc_html_e( 'Correo', 'almaden-bookster' ); ?></label>
							<input id="publisher_email" name="publisher_email" type="email" required />
						</div>
						<div class="almaden-field">
							<label for="publisher_website"><?php esc_html_e( 'Sitio web', 'almaden-bookster' ); ?></label>
							<input id="publisher_website" name="publisher_website" type="url" placeholder="https://..." />
						</div>
					</div>
				</section>

				<section class="almaden-step" data-step="3">
					<div class="almaden-grid">
						<div class="almaden-field">
							<label for="account_password"><?php esc_html_e( 'Contraseña', 'almaden-bookster' ); ?></label>
							<input id="account_password" name="account_password" type="password" minlength="8" required />
						</div>
						<div class="almaden-field">
							<label for="account_password_confirm"><?php esc_html_e( 'Confirmar contraseña', 'almaden-bookster' ); ?></label>
							<input id="account_password_confirm" name="account_password_confirm" type="password" minlength="8" required />
						</div>
					</div>
					<div class="almaden-upload">
						<div class="almaden-field" style="margin-bottom: 0;">
							<label for="publisher_logo_file"><?php esc_html_e( 'Logo', 'almaden-bookster' ); ?></label>
							<input id="publisher_logo_file" name="publisher_logo_file" type="file" accept="image/*" />
							<p class="almaden-field-help"><?php esc_html_e( 'Sube un logo cuadrado o horizontal. Lo usaremos en el perfil público de tu editorial.', 'almaden-bookster' ); ?></p>
						</div>
					</div>
				</section>

				<section class="almaden-step" data-step="4">
					<h3 style="margin: 0 0 0.85rem; font-size: 1.1rem;"><?php esc_html_e( 'Revisa antes de crear la editorial', 'almaden-bookster' ); ?></h3>
					<div class="almaden-summary">
						<div class="almaden-summary-item">
							<span><?php esc_html_e( 'Editorial', 'almaden-bookster' ); ?></span>
							<strong data-summary-field="publisher_name">-</strong>
							<div class="almaden-field-help" data-summary-field="publisher_legal_name"></div>
						</div>
						<div class="almaden-summary-item">
							<span><?php esc_html_e( 'Contacto', 'almaden-bookster' ); ?></span>
							<strong data-summary-field="contact_name">-</strong>
							<div class="almaden-field-help" data-summary-field="publisher_email"></div>
						</div>
						<div class="almaden-summary-item">
							<span><?php esc_html_e( 'Keywords', 'almaden-bookster' ); ?></span>
							<div class="almaden-field-help" data-summary-field="publisher_keywords"></div>
						</div>
					</div>
					<p class="almaden-note"><?php esc_html_e( 'Al enviar el formulario se crea la cuenta de usuario, la editorial y tu membresía de propietario. Después te llevamos al taller para continuar con tu catálogo.', 'almaden-bookster' ); ?></p>
				</section>

				<div class="almaden-actions">
					<button type="button" class="almaden-btn almaden-btn--ghost" data-step-back><?php esc_html_e( 'Volver', 'almaden-bookster' ); ?></button>
					<div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
						<button type="button" class="almaden-btn almaden-btn--ghost" data-step-next><?php esc_html_e( 'Siguiente', 'almaden-bookster' ); ?></button>
						<button type="submit" class="almaden-btn almaden-btn--primary" data-step-submit><?php esc_html_e( 'Crear editorial', 'almaden-bookster' ); ?></button>
					</div>
				</div>
			</form>
		</section>
	</div>
</main>

<script src="<?php echo esc_url( plugins_url( '../../assets/js/publishers/publisher-onboarding.js', __FILE__ ) ); ?>?v=<?php echo esc_attr( file_exists( $js_file ) ? (string) filemtime( $js_file ) : '1' ); ?>" defer></script>
<?php
almaden_bookster_render_app_shell_end();
