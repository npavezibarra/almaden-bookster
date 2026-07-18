<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

almaden_bookster_render_app_shell_start(
	array(
		'title'          => almaden_bookster_get_dashboard_title() . ' - Almaden',
		'body_id'        => 'almaden-dashboard-app-body',
		'active_nav_key' => 'dashboard',
	)
);
?>
<main id="almaden-dashboard-page" class="almaden-app-content-shell flex-1 pb-16" style="min-height: 60vh;">
	<!-- Dashboard sin contenido por ahora -->
</main>
<?php
almaden_bookster_render_app_shell_end();
