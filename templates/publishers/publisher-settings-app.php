<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$can_manage_publisher = isset( $can_manage_publisher ) ? (bool) $can_manage_publisher : false;
$settings_updated     = isset( $_GET['settings-updated'] ) && '1' === $_GET['settings-updated'];
$publisher_exists     = is_array( $publisher ) && ! empty( $publisher );
$publisher_settings   = isset( $publisher_settings ) && is_array( $publisher_settings ) ? $publisher_settings : almaden_bookster_get_publisher_settings_defaults();
$profile_url          = $publisher_exists ? almaden_bookster_get_publisher_page_url( $publisher['slug'] ) : almaden_bookster_get_publisher_page_url();
$settings_url         = $publisher_exists ? almaden_bookster_get_publisher_settings_url( $publisher['slug'] ) : almaden_bookster_get_publisher_settings_url();
$back_url             = $publisher_exists ? $profile_url : almaden_bookster_get_publisher_page_url();

if ( ! $publisher_exists ) :
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php esc_html_e( 'Editorial no encontrada', 'almaden-bookster' ); ?></title>
	<?php wp_head(); ?>
</head>
<body>
	<main style="max-width: 860px; margin: 3rem auto; padding: 1rem;">
		<h1><?php esc_html_e( 'Editorial no encontrada', 'almaden-bookster' ); ?></h1>
		<p><?php esc_html_e( 'No pudimos cargar el panel de ajustes porque no encontramos la editorial solicitada.', 'almaden-bookster' ); ?></p>
		<a href="<?php echo esc_url( almaden_bookster_get_publisher_page_url() ); ?>"><?php esc_html_e( 'Volver al directorio', 'almaden-bookster' ); ?></a>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
<?php
	return;
endif;

if ( ! $can_manage_publisher ) :
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $publisher['name'] ); ?> - <?php esc_html_e( 'Ajustes', 'almaden-bookster' ); ?></title>
	<style>
		html {
			margin-top: 0 !important;
		}
		body { font-family: system-ui, sans-serif; background: #f8fafc; color: #0f172a; }
		#almaden-publisher-settings { max-width: 860px; margin: 0 auto; padding: 3rem 1rem; }
		.almaden-settings-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1.25rem; padding: 1.5rem; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06); }
		.almaden-settings-btn { display:inline-flex; align-items:center; justify-content:center; padding:0.9rem 1.1rem; border-radius:999px; background:#111827; color:#fff; text-decoration:none; font-weight:700; }
	</style>
	<?php wp_head(); ?>
	<style id="almaden-publisher-settings-overrides">
		html {
			margin-top: 0 !important;
		}
		main {
			padding-top: 20px !important;
			background-color: #f9fafb;
		}
	</style>
</head>
<body>
	<main id="almaden-publisher-settings">
		<div class="almaden-settings-card">
			<h1><?php esc_html_e( 'No tienes permisos para editar esta editorial', 'almaden-bookster' ); ?></h1>
			<p><?php esc_html_e( 'Solo los miembros propietarios o editores de la editorial pueden acceder a este panel.', 'almaden-bookster' ); ?></p>
			<p><a class="almaden-settings-btn" href="<?php echo esc_url( $profile_url ); ?>"><?php esc_html_e( 'Volver al perfil público', 'almaden-bookster' ); ?></a></p>
		</div>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
<?php
	return;
endif;

$legal        = isset( $publisher_settings['legal'] ) && is_array( $publisher_settings['legal'] ) ? $publisher_settings['legal'] : array();
$contact      = isset( $publisher_settings['contact'] ) && is_array( $publisher_settings['contact'] ) ? $publisher_settings['contact'] : array();
$branding     = isset( $publisher_settings['branding'] ) && is_array( $publisher_settings['branding'] ) ? $publisher_settings['branding'] : array();
$preferences   = isset( $publisher_settings['preferences'] ) && is_array( $publisher_settings['preferences'] ) ? $publisher_settings['preferences'] : array();
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $publisher['name'] ); ?> - <?php esc_html_e( 'Ajustes de editorial', 'almaden-bookster' ); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<style>
		html {
			margin-top: 0 !important;
		}
		:root {
			--bg: #f6f3ee;
			--panel: #ffffff;
			--ink: #0f172a;
			--muted: #64748b;
			--line: #e2e8f0;
			--accent: #8f4b2a;
			--accent-2: #111827;
		}
		* { box-sizing: border-box; }
		body { margin: 0; font-family: "Inter", sans-serif; color: var(--ink); background: linear-gradient(180deg, #fbf8f3 0%, var(--bg) 100%); }
		a { color: inherit; }
		#almaden-publisher-settings { max-width: 1240px; margin: 0 auto; padding: 2rem 1rem 4rem; }
		.almaden-header {
			display: flex; justify-content: space-between; gap: 1rem; align-items: flex-end; margin-bottom: 1.5rem;
		}
		.almaden-header h1 {
			margin: 0; font-family: "Fraunces", serif; font-size: clamp(2rem, 4vw, 3.4rem); line-height: 0.98; letter-spacing: -0.04em;
		}
		.almaden-header p { margin: 0.5rem 0 0; color: var(--muted); max-width: 68ch; line-height: 1.6; }
		.almaden-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }
		.almaden-btn {
			display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
			padding: 0.9rem 1.1rem; border-radius: 999px; text-decoration: none; font-weight: 800; border: 1px solid transparent;
		}
		.almaden-btn--primary { background: var(--accent-2); color: #fff; }
		.almaden-btn--ghost { background: #fff; color: var(--ink); border-color: var(--line); }
		.almaden-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr); gap: 1.2rem; align-items: start; }
		.almaden-panel {
			background: var(--panel); border: 1px solid var(--line); border-radius: 1.35rem; padding: 1.35rem; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
		}
		.almaden-stack { display: grid; gap: 1rem; }
		.almaden-section h2 { margin: 0 0 0.85rem; font-size: 1.05rem; letter-spacing: -0.02em; }
		.almaden-section p.almaden-help { margin: 0 0 1rem; color: var(--muted); font-size: 0.92rem; line-height: 1.55; }
		.almaden-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.95rem; }
		.almaden-field { display: grid; gap: 0.4rem; }
		.almaden-field label { font-size: 0.85rem; font-weight: 700; color: #334155; }
		.almaden-field input,
		.almaden-field select,
		.almaden-field textarea {
			width: 100%; border: 1px solid #d6dbe3; border-radius: 0.95rem; padding: 0.9rem 1rem; font: inherit; background: #fff;
		}
		.almaden-field textarea { min-height: 120px; resize: vertical; }
		.almaden-field input:focus,
		.almaden-field select:focus,
		.almaden-field textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 4px rgba(143, 75, 42, 0.08); }
		.almaden-full { grid-column: 1 / -1; }
		.almaden-sidebar {
			display: grid; gap: 0.95rem;
		}
		.almaden-pill {
			display: inline-flex; padding: 0.35rem 0.7rem; border-radius: 999px; background: #f8fafc; color: #334155; font-size: 0.74rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase;
		}
		.almaden-card-muted { color: var(--muted); font-size: 0.92rem; line-height: 1.55; }
		.almaden-notice {
			padding: 0.95rem 1rem; border-radius: 1rem; background: #ecfdf3; border: 1px solid #abefc6; color: #166534; margin-bottom: 1rem;
		}
		.almaden-actions-row { display: flex; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; align-items: center; margin-top: 1rem; }
		.almaden-meta-list { margin: 0; padding-left: 1rem; color: var(--muted); line-height: 1.55; }
		@media (max-width: 960px) {
			.almaden-grid { grid-template-columns: 1fr; }
			.almaden-form-grid { grid-template-columns: 1fr; }
			.almaden-header { flex-direction: column; align-items: flex-start; }
		}
	</style>
	<?php wp_head(); ?>
	<style id="almaden-publisher-settings-overrides-2">
		html {
			margin-top: 0 !important;
		}
		main {
			padding-top: 20px !important;
			background-color: #f9fafb;
		}
	</style>
</head>
<body>
	<main id="almaden-publisher-settings">
		<header class="almaden-header">
			<div>
				<span class="almaden-pill"><?php esc_html_e( 'Panel de editorial', 'almaden-bookster' ); ?></span>
				<h1><?php esc_html_e( 'Ajustes de editorial', 'almaden-bookster' ); ?></h1>
				<p><?php esc_html_e( 'Aquí puedes ajustar la configuración legal, financiera, de contacto y de branding sin mezclarla con el taller o el perfil público.', 'almaden-bookster' ); ?></p>
			</div>
			<div class="almaden-actions">
				<a class="almaden-btn almaden-btn--ghost" href="<?php echo esc_url( $profile_url ); ?>"><?php esc_html_e( 'Ver perfil público', 'almaden-bookster' ); ?></a>
				<a class="almaden-btn almaden-btn--ghost" href="<?php echo esc_url( almaden_bookster_get_creator_page_url() ); ?>"><?php esc_html_e( 'Volver al taller', 'almaden-bookster' ); ?></a>
			</div>
		</header>

		<?php if ( $settings_updated ) : ?>
			<div class="almaden-notice"><?php esc_html_e( 'Los ajustes de la editorial se guardaron correctamente.', 'almaden-bookster' ); ?></div>
		<?php endif; ?>

		<div class="almaden-grid">
			<section class="almaden-panel">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="almaden_save_publisher_settings">
					<input type="hidden" name="publisher_id" value="<?php echo esc_attr( absint( $publisher['id'] ) ); ?>">
					<?php wp_nonce_field( 'almaden_save_publisher_settings', 'almaden_publisher_settings_nonce' ); ?>

					<div class="almaden-stack">
						<section class="almaden-section">
							<h2><?php esc_html_e( 'Identidad pública', 'almaden-bookster' ); ?></h2>
							<p class="almaden-help"><?php esc_html_e( 'Esta información alimenta el directorio público y la URL de la editorial.', 'almaden-bookster' ); ?></p>
							<div class="almaden-form-grid">
								<div class="almaden-field">
									<label for="publisher_name"><?php esc_html_e( 'Nombre', 'almaden-bookster' ); ?></label>
									<input id="publisher_name" name="publisher_name" type="text" value="<?php echo esc_attr( $publisher['name'] ); ?>" required>
								</div>
								<div class="almaden-field">
									<label for="publisher_slug"><?php esc_html_e( 'Slug', 'almaden-bookster' ); ?></label>
									<input id="publisher_slug" name="publisher_slug" type="text" value="<?php echo esc_attr( $publisher['slug'] ); ?>" required>
								</div>
								<div class="almaden-field almaden-full">
									<label for="publisher_description"><?php esc_html_e( 'Descripción', 'almaden-bookster' ); ?></label>
									<textarea id="publisher_description" name="publisher_description"><?php echo esc_textarea( $publisher['description'] ); ?></textarea>
								</div>
								<div class="almaden-field">
									<label for="publisher_keywords"><?php esc_html_e( 'Keywords', 'almaden-bookster' ); ?></label>
									<input id="publisher_keywords" name="publisher_keywords" type="text" value="<?php echo esc_attr( $publisher['keywords'] ); ?>">
								</div>
								<div class="almaden-field">
									<label for="publisher_status"><?php esc_html_e( 'Estado', 'almaden-bookster' ); ?></label>
									<select id="publisher_status" name="publisher_status">
										<option value="pending" <?php selected( $publisher['status'], 'pending' ); ?>><?php esc_html_e( 'Pendiente', 'almaden-bookster' ); ?></option>
										<option value="active" <?php selected( $publisher['status'], 'active' ); ?>><?php esc_html_e( 'Activa', 'almaden-bookster' ); ?></option>
										<option value="inactive" <?php selected( $publisher['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactiva', 'almaden-bookster' ); ?></option>
									</select>
								</div>
							</div>
						</section>

						<section class="almaden-section">
							<h2><?php esc_html_e( 'Datos legales y financieros', 'almaden-bookster' ); ?></h2>
							<div class="almaden-form-grid">
								<div class="almaden-field">
									<label for="publisher_legal_name"><?php esc_html_e( 'Razón social', 'almaden-bookster' ); ?></label>
									<input id="publisher_legal_name" name="publisher_legal_name" type="text" value="<?php echo esc_attr( $publisher['legal_name'] ); ?>">
								</div>
								<div class="almaden-field">
									<label for="publisher_rut"><?php esc_html_e( 'RUT', 'almaden-bookster' ); ?></label>
									<input id="publisher_rut" name="publisher_rut" type="text" value="<?php echo esc_attr( $publisher['rut'] ); ?>">
								</div>
								<div class="almaden-field almaden-full">
									<label for="billing_name"><?php esc_html_e( 'Nombre de facturación', 'almaden-bookster' ); ?></label>
									<input id="billing_name" name="billing_name" type="text" value="<?php echo esc_attr( $legal['billing_name'] ?? '' ); ?>">
								</div>
								<div class="almaden-field almaden-full">
									<label for="legal_address"><?php esc_html_e( 'Dirección legal', 'almaden-bookster' ); ?></label>
									<textarea id="legal_address" name="legal_address"><?php echo esc_textarea( $legal['legal_address'] ?? '' ); ?></textarea>
								</div>
								<div class="almaden-field">
									<label for="legal_representative"><?php esc_html_e( 'Representante legal', 'almaden-bookster' ); ?></label>
									<input id="legal_representative" name="legal_representative" type="text" value="<?php echo esc_attr( $legal['legal_representative'] ?? '' ); ?>">
								</div>
								<div class="almaden-field">
									<label for="tax_id"><?php esc_html_e( 'NIF / ID tributario', 'almaden-bookster' ); ?></label>
									<input id="tax_id" name="tax_id" type="text" value="<?php echo esc_attr( $legal['tax_id'] ?? '' ); ?>">
								</div>
								<div class="almaden-field almaden-full">
									<label for="financial_terms"><?php esc_html_e( 'Términos financieros', 'almaden-bookster' ); ?></label>
									<textarea id="financial_terms" name="financial_terms"><?php echo esc_textarea( $legal['financial_terms'] ?? '' ); ?></textarea>
								</div>
							</div>
						</section>

						<section class="almaden-section">
							<h2><?php esc_html_e( 'Contacto operativo', 'almaden-bookster' ); ?></h2>
							<div class="almaden-form-grid">
								<div class="almaden-field">
									<label for="contact_name"><?php esc_html_e( 'Nombre de contacto', 'almaden-bookster' ); ?></label>
									<input id="contact_name" name="contact_name" type="text" value="<?php echo esc_attr( $contact['contact_name'] ?? '' ); ?>">
								</div>
								<div class="almaden-field">
									<label for="contact_email"><?php esc_html_e( 'Email de contacto', 'almaden-bookster' ); ?></label>
									<input id="contact_email" name="contact_email" type="email" value="<?php echo esc_attr( $contact['contact_email'] ?? '' ); ?>">
								</div>
								<div class="almaden-field">
									<label for="contact_phone"><?php esc_html_e( 'Teléfono', 'almaden-bookster' ); ?></label>
									<input id="contact_phone" name="contact_phone" type="text" value="<?php echo esc_attr( $contact['contact_phone'] ?? '' ); ?>">
								</div>
								<div class="almaden-field">
									<label for="contact_notes"><?php esc_html_e( 'Notas', 'almaden-bookster' ); ?></label>
									<input id="contact_notes" name="contact_notes" type="text" value="<?php echo esc_attr( $contact['contact_notes'] ?? '' ); ?>">
								</div>
							</div>
						</section>

						<section class="almaden-section">
							<h2><?php esc_html_e( 'Branding', 'almaden-bookster' ); ?></h2>
							<div class="almaden-form-grid">
								<div class="almaden-field">
									<label for="publisher_email"><?php esc_html_e( 'Correo público', 'almaden-bookster' ); ?></label>
									<input id="publisher_email" name="publisher_email" type="email" value="<?php echo esc_attr( $publisher['email'] ); ?>">
								</div>
								<div class="almaden-field">
									<label for="publisher_phone"><?php esc_html_e( 'Teléfono público', 'almaden-bookster' ); ?></label>
									<input id="publisher_phone" name="publisher_phone" type="text" value="<?php echo esc_attr( $publisher['phone'] ); ?>">
								</div>
								<div class="almaden-field">
									<label for="publisher_website"><?php esc_html_e( 'Sitio web', 'almaden-bookster' ); ?></label>
									<input id="publisher_website" name="publisher_website" type="url" value="<?php echo esc_attr( $publisher['website'] ); ?>">
								</div>
								<div class="almaden-field">
									<label for="support_email"><?php esc_html_e( 'Email de soporte', 'almaden-bookster' ); ?></label>
									<input id="support_email" name="support_email" type="email" value="<?php echo esc_attr( $branding['support_email'] ?? '' ); ?>">
								</div>
								<div class="almaden-field">
									<label for="primary_color"><?php esc_html_e( 'Color principal', 'almaden-bookster' ); ?></label>
									<input id="primary_color" name="primary_color" type="text" value="<?php echo esc_attr( $branding['primary_color'] ?? '#111827' ); ?>">
								</div>
								<div class="almaden-field">
									<label for="secondary_color"><?php esc_html_e( 'Color secundario', 'almaden-bookster' ); ?></label>
									<input id="secondary_color" name="secondary_color" type="text" value="<?php echo esc_attr( $branding['secondary_color'] ?? '#8f4b2a' ); ?>">
								</div>
								<div class="almaden-field">
									<label for="logo_alt"><?php esc_html_e( 'Texto alternativo del logo', 'almaden-bookster' ); ?></label>
									<input id="logo_alt" name="logo_alt" type="text" value="<?php echo esc_attr( $branding['logo_alt'] ?? '' ); ?>">
								</div>
								<div class="almaden-field">
									<label for="brand_notes"><?php esc_html_e( 'Notas de branding', 'almaden-bookster' ); ?></label>
									<input id="brand_notes" name="brand_notes" type="text" value="<?php echo esc_attr( $branding['brand_notes'] ?? '' ); ?>">
								</div>
								<div class="almaden-field">
									<label for="publisher_logo"><?php esc_html_e( 'ID logo', 'almaden-bookster' ); ?></label>
									<input id="publisher_logo" name="publisher_logo" type="number" min="0" value="<?php echo esc_attr( absint( $publisher['logo'] ) ); ?>">
								</div>
								<div class="almaden-field">
									<label for="publisher_banner"><?php esc_html_e( 'ID banner', 'almaden-bookster' ); ?></label>
									<input id="publisher_banner" name="publisher_banner" type="number" min="0" value="<?php echo esc_attr( absint( $publisher['banner'] ) ); ?>">
								</div>
							</div>
						</section>

						<section class="almaden-section">
							<h2><?php esc_html_e( 'Preferencias', 'almaden-bookster' ); ?></h2>
							<div class="almaden-form-grid">
								<div class="almaden-field">
									<label for="language"><?php esc_html_e( 'Idioma', 'almaden-bookster' ); ?></label>
									<select id="language" name="language">
										<option value="es" <?php selected( $preferences['language'] ?? 'es', 'es' ); ?>>Español</option>
										<option value="en" <?php selected( $preferences['language'] ?? 'es', 'en' ); ?>>English</option>
										<option value="pt" <?php selected( $preferences['language'] ?? 'es', 'pt' ); ?>>Português</option>
									</select>
								</div>
								<div class="almaden-field">
									<label for="default_status"><?php esc_html_e( 'Estado por defecto', 'almaden-bookster' ); ?></label>
									<select id="default_status" name="default_status">
										<option value="active" <?php selected( $preferences['default_status'] ?? 'active', 'active' ); ?>><?php esc_html_e( 'Activa', 'almaden-bookster' ); ?></option>
										<option value="pending" <?php selected( $preferences['default_status'] ?? 'active', 'pending' ); ?>><?php esc_html_e( 'Pendiente', 'almaden-bookster' ); ?></option>
										<option value="inactive" <?php selected( $preferences['default_status'] ?? 'active', 'inactive' ); ?>><?php esc_html_e( 'Inactiva', 'almaden-bookster' ); ?></option>
									</select>
								</div>
								<div class="almaden-field">
									<label><input type="checkbox" name="show_public_email" value="1" <?php checked( ! empty( $preferences['show_public_email'] ) ); ?>> <?php esc_html_e( 'Mostrar correo público', 'almaden-bookster' ); ?></label>
								</div>
								<div class="almaden-field">
									<label><input type="checkbox" name="show_public_phone" value="1" <?php checked( ! empty( $preferences['show_public_phone'] ) ); ?>> <?php esc_html_e( 'Mostrar teléfono público', 'almaden-bookster' ); ?></label>
								</div>
								<div class="almaden-field">
									<label><input type="checkbox" name="allow_inquiries" value="1" <?php checked( ! empty( $preferences['allow_inquiries'] ) ); ?>> <?php esc_html_e( 'Permitir consultas', 'almaden-bookster' ); ?></label>
								</div>
								<div class="almaden-field almaden-full">
									<label for="future_notes"><?php esc_html_e( 'Notas para futuras funciones', 'almaden-bookster' ); ?></label>
									<textarea id="future_notes" name="future_notes"><?php echo esc_textarea( $preferences['future_notes'] ?? '' ); ?></textarea>
								</div>
							</div>
						</section>
					</div>

					<div class="almaden-actions-row">
						<a href="<?php echo esc_url( $back_url ); ?>" class="almaden-btn almaden-btn--ghost"><?php esc_html_e( 'Volver al perfil', 'almaden-bookster' ); ?></a>
						<button type="submit" class="almaden-btn almaden-btn--primary"><?php esc_html_e( 'Guardar ajustes', 'almaden-bookster' ); ?></button>
					</div>
				</form>
			</section>

			<aside class="almaden-panel almaden-sidebar">
				<div>
					<span class="almaden-pill"><?php esc_html_e( 'Resumen', 'almaden-bookster' ); ?></span>
					<h2 style="margin: 0.75rem 0 0;"><?php echo esc_html( $publisher['name'] ); ?></h2>
					<p class="almaden-card-muted"><?php echo esc_html( $publisher['slug'] ); ?></p>
				</div>

				<div>
					<h3 style="margin: 0 0 0.5rem;"><?php esc_html_e( 'Qué cubre este panel', 'almaden-bookster' ); ?></h3>
					<ul class="almaden-meta-list">
						<li><?php esc_html_e( 'Datos legales y financieros para crecer sin hacks dispersos.', 'almaden-bookster' ); ?></li>
						<li><?php esc_html_e( 'Canales de contacto y branding reutilizables en próximas pantallas.', 'almaden-bookster' ); ?></li>
						<li><?php esc_html_e( 'Preferencias listas para futuras automatizaciones.', 'almaden-bookster' ); ?></li>
					</ul>
				</div>

				<div>
					<h3 style="margin: 0 0 0.5rem;"><?php esc_html_e( 'URLs útiles', 'almaden-bookster' ); ?></h3>
					<p class="almaden-card-muted" style="margin: 0 0 0.35rem;"><?php esc_html_e( 'Perfil público', 'almaden-bookster' ); ?>: <a href="<?php echo esc_url( $profile_url ); ?>"><?php echo esc_html( $profile_url ); ?></a></p>
					<p class="almaden-card-muted" style="margin: 0;"><?php esc_html_e( 'Ajustes', 'almaden-bookster' ); ?>: <a href="<?php echo esc_url( $settings_url ); ?>"><?php echo esc_html( $settings_url ); ?></a></p>
				</div>
			</aside>
		</div>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
