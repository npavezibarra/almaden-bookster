<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$access_args = isset( $almaden_bookster_shell_access_denied_args ) && is_array( $almaden_bookster_shell_access_denied_args ) ? $almaden_bookster_shell_access_denied_args : array();

almaden_bookster_render_app_shell_start(
	array(
		'title'          => isset( $access_args['title'] ) ? $access_args['title'] : 'No tienes acceso a esta página - Almaden',
		'body_id'        => isset( $access_args['body_id'] ) ? $access_args['body_id'] : 'almaden-shell-access-denied-body',
		'active_nav_key' => '',
	)
);
?>
<main id="almaden-shell-access-denied" class="almaden-app-content-shell flex-1 pb-16">
	<section class="flex min-h-[60vh] items-center py-12">
		<div class="w-full rounded-[2rem] border border-gray-200 bg-white p-8 shadow-sm sm:p-10">
			<p class="text-xs font-semibold uppercase tracking-[0.26em] text-gray-400">Acceso restringido</p>
			<h1 class="mt-4 max-w-2xl text-3xl font-bold tracking-tight text-black sm:text-4xl">No tienes acceso a esta página</h1>
			<p class="mt-4 max-w-2xl text-base leading-7 text-gray-600">
				<?php echo esc_html( isset( $access_args['message'] ) ? $access_args['message'] : 'Solo usuarios WordPress con rol administrador, autor o editor pueden entrar.' ); ?>
			</p>
			<p class="mt-3 max-w-2xl text-base leading-7 text-gray-500">
				<?php echo esc_html( isset( $access_args['submessage'] ) ? $access_args['submessage'] : 'Aunque hayas comprado en WooCommerce, esta zona sigue restringida por rol.' ); ?>
			</p>
			<div class="mt-8">
				<?php
				if ( function_exists( 'almaden_bookster_render_login_register_button' ) ) {
					almaden_bookster_render_login_register_button();
				}
				?>
			</div>
		</div>
	</section>
</main>
<?php
almaden_bookster_render_app_shell_end();
