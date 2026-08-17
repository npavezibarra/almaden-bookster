<?php
/**
 * Minimal unit test for stripping chapter bodies from the initial payload.
 */

define( 'ABSPATH', __DIR__ );

function apply_filters( $hook_name, $value ) {
	return $value;
}

function absint( $value ) {
	return abs( (int) $value );
}

function is_user_logged_in() {
	return true;
}

function get_option( $name, $default = false ) {
	return $default;
}

function get_post_meta() {
	return '';
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
}

function wp_salt() {
	return 'test-salt';
}

require_once dirname( __DIR__, 2 ) . '/includes/class-protection-policy.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-content-protection.php';

$chapters = array(
	array( 'id' => 10, 'title' => 'Uno', 'content' => 'Texto secreto uno' ),
	array( 'id' => 11, 'title' => 'Dos', 'content' => 'Texto secreto dos' ),
);
$prepared = AlmadenBookster\ContentProtection\Content_Protection::prepare_chapters( $chapters, 7 );

foreach ( $prepared as $chapter ) {
	if ( array_key_exists( 'content', $chapter ) ) {
		fwrite( STDERR, "FAIL: chapter content remains in initial payload.\n" );
		exit( 1 );
	}
}

fwrite( STDOUT, "PASS: initial chapter payload contains metadata only.\n" );
