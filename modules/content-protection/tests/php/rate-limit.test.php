<?php
define( 'ABSPATH', __DIR__ );
define( 'MINUTE_IN_SECONDS', 60 );

$almaden_test_transients = array();
$almaden_test_alerts = array();

function absint( $value ) { return abs( (int) $value ); }
function get_current_user_id() { return 55; }
function wp_salt( $scheme = 'auth' ) { return 'test-salt-' . $scheme; }
function get_transient( $key ) { global $almaden_test_transients; return $almaden_test_transients[ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { global $almaden_test_transients; $almaden_test_transients[ $key ] = $value; return true; }
function do_action( $hook, ...$args ) { global $almaden_test_alerts; $almaden_test_alerts[] = array( $hook, $args ); }

class Almaden_Test_Wpdb {
	public $prefix = 'wp_';
	public function prepare( $query, ...$args ) { return $query; }
	public function query( $query ) { return true; }
}
$wpdb = new Almaden_Test_Wpdb();

require_once dirname( __DIR__, 2 ) . '/includes/class-watermark-token.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-protection-telemetry.php';

for ( $index = 1; $index <= 24; $index++ ) {
	$result = AlmadenBookster\ContentProtection\Protection_Telemetry::check_chapter_rate( 9, $index );
	if ( empty( $result['allowed'] ) ) {
		fwrite( STDERR, "FAIL: normal threshold blocked too early.\n" );
		exit( 1 );
	}
}
$blocked = AlmadenBookster\ContentProtection\Protection_Telemetry::check_chapter_rate( 9, 25 );
if ( ! empty( $blocked['allowed'] ) || 120 !== $blocked['retry_after'] ) {
	fwrite( STDERR, "FAIL: abnormal sequential delivery was not blocked.\n" );
	exit( 1 );
}
fwrite( STDOUT, "PASS: abnormal sequential chapter delivery is rate limited.\n" );

