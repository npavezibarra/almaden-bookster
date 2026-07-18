<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$author_slug = get_query_var( 'almaden_author_slug', '' );
$author      = '' !== trim( (string) $author_slug ) ? almaden_bookster_get_author_by_slug( $author_slug ) : null;

$extra_head_html = sprintf(
	'<link rel="stylesheet" href="%1$s"><link rel="stylesheet" href="%2$s">',
	esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/authors/author-page.css' ) . '?v=' . esc_attr( filemtime( dirname( __FILE__ ) . '/../../assets/css/authors/author-page.css' ) ),
	esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/authors/author-page-responsive.css' ) . '?v=' . esc_attr( filemtime( dirname( __FILE__ ) . '/../../assets/css/authors/author-page-responsive.css' ) )
);

almaden_bookster_render_app_shell_start(
	array(
		'title'           => ( is_object( $author ) && ! empty( $author ) ? $author->display_name : almaden_bookster_get_author_page_title() ) . ' - Almaden',
		'body_id'         => 'almaden-author-app-body',
		'active_nav_key'  => 'authors',
		'extra_head_html' => $extra_head_html,
	)
);

require dirname( __FILE__ ) . '/author-page.php';

?>
<script src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/authors/author-tabs.js' ) . '?v=' . esc_attr( filemtime( dirname( __FILE__ ) . '/../../assets/js/authors/author-tabs.js' ) ); ?>"></script>
<script src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/authors/author-photo.js' ) . '?v=' . esc_attr( filemtime( dirname( __FILE__ ) . '/../../assets/js/authors/author-photo.js' ) ); ?>"></script>
<script src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/authors/author-hero.js' ) . '?v=' . esc_attr( filemtime( dirname( __FILE__ ) . '/../../assets/js/authors/author-hero.js' ) ); ?>"></script>
<?php

almaden_bookster_render_app_shell_end();
