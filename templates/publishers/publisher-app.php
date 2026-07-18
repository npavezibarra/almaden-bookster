<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$publisher_slug = get_query_var( 'almaden_publisher_slug', '' );
$publisher      = '' !== trim( (string) $publisher_slug ) ? almaden_bookster_get_publisher_by_slug( $publisher_slug ) : null;

almaden_bookster_render_app_shell_start(
	array(
		'title'          => ( is_array( $publisher ) && ! empty( $publisher ) ? $publisher['name'] : almaden_bookster_get_publisher_page_title() ) . ' - Almaden',
		'body_id'        => 'almaden-publisher-app-body',
		'active_nav_key' => 'publisher',
	)
);

require dirname( __FILE__ ) . '/publisher-page.php';

almaden_bookster_render_app_shell_end();
