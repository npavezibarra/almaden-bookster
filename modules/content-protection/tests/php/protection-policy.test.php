<?php
/** Unit test for global rollback, rollout, and per-book overrides. */

define( 'ABSPATH', __DIR__ );

$test_options = array();
$test_meta    = array();

function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function get_option( $name, $default = false ) { global $test_options; return array_key_exists( $name, $test_options ) ? $test_options[ $name ] : $default; }
function get_post_meta( $id, $key ) { global $test_meta; return $test_meta[ $id ][ $key ] ?? ''; }
function apply_filters( $hook, $value ) { return $value; }
function wp_salt() { return 'stable-policy-test-salt'; }

require_once dirname( __DIR__, 2 ) . '/includes/class-protection-policy.php';

use AlmadenBookster\ContentProtection\Protection_Policy;

function assert_policy( $expected, $book_id, $message ) {
	$actual = Protection_Policy::resolve( $book_id );
	if ( $expected !== $actual['enabled'] ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$test_options[ Protection_Policy::ENABLED_OPTION ] = '0';
$test_meta[7][ Protection_Policy::BOOK_META ] = 'enabled';
assert_policy( false, 7, 'global emergency switch must override a book opt-in' );

$test_options[ Protection_Policy::ENABLED_OPTION ] = '1';
$test_options[ Protection_Policy::ROLLOUT_OPTION ] = 0;
assert_policy( true, 7, 'book opt-in must override a zero-percent rollout' );

$test_meta[7][ Protection_Policy::BOOK_META ] = 'disabled';
$test_options[ Protection_Policy::ROLLOUT_OPTION ] = 100;
assert_policy( false, 7, 'book opt-out must override full rollout' );

unset( $test_meta[7] );
assert_policy( true, 7, 'inherited book must join a full rollout' );

$test_options[ Protection_Policy::ROLLOUT_OPTION ] = 0;
assert_policy( false, 7, 'inherited book must stay out of a paused rollout' );

fwrite( STDOUT, "PASS: feature flags and book overrides resolve safely.\n" );
