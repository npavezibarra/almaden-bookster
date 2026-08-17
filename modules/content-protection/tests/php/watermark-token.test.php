<?php
define( 'ABSPATH', __DIR__ );

function absint( $value ) { return abs( (int) $value ); }
function get_current_user_id() { return 123; }
function wp_salt( $scheme = 'auth' ) { return 'test-salt-' . $scheme; }
function apply_filters( $hook, $value ) { return $value; }
function wp_nonce_tick() { return 42; }
function wp_json_encode( $value ) { return json_encode( $value ); }

require_once dirname( __DIR__, 2 ) . '/includes/class-watermark-token.php';

$token = AlmadenBookster\ContentProtection\Watermark_Token::for_book( 77, 123 );
if ( ! preg_match( '/^ALM-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}$/', $token ) ) {
	fwrite( STDERR, "FAIL: invalid watermark token format.\n" );
	exit( 1 );
}
if ( false !== strpos( $token, '123' ) || false !== strpos( $token, '77' ) ) {
	fwrite( STDERR, "FAIL: raw identifiers leaked into watermark token.\n" );
	exit( 1 );
}
fwrite( STDOUT, "PASS: watermark token is signed and pseudonymous.\n" );

