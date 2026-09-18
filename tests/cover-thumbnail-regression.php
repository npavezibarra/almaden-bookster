<?php
/**
 * Source-level regression checks for cover thumbnail trim and typography.
 */

$root = dirname( __DIR__ );
$generator = file_get_contents( $root . '/includes/helpers/cover-thumbnail-generator.php' );
$renderer = file_get_contents( $root . '/includes/helpers/cover-thumbnail-renderer.php' );
$cover_app = file_get_contents( $root . '/templates/cover/cover-app.php' );

$failures = array();

if ( false === strpos( $generator, '$page_width_px / $page_height_px' ) ) {
	$failures[] = 'Snapshot dimensions are not based on the final trim ratio.';
}

if ( false === strpos( $renderer, 'data-front-cover-px="<?php echo esc_attr($pageWidthPx); ?>"' ) ) {
	$failures[] = 'The thumbnail viewport width still includes bleed or fold area.';
}

if ( false === strpos( $renderer, 'data-start-y-px="<?php echo esc_attr($bleedPx); ?>"' ) ) {
	$failures[] = 'The thumbnail does not crop the top bleed.';
}

foreach ( array( 'font-size:', 'font-weight:', 'line-height:', 'letter-spacing:', 'font-family:' ) as $property ) {
	if ( false === strpos( $renderer, $property ) ) {
		$failures[] = 'Missing text style in thumbnail renderer: ' . $property;
	}
}

if ( false === strpos( $generator, 'document.fonts.load' ) || false === strpos( $generator, 'document.fonts.ready' ) ) {
	$failures[] = 'Headless capture does not wait for text layer fonts.';
}

if ( false === strpos( $cover_app, ':not(.text-layer):not(.text-layer *)' ) ) {
	$failures[] = 'Cover editor UI font override still affects canvas text layers.';
}

if ( false === strpos( $generator, "snapshot_renderer_version'] = 'trim-v2-font-ready-v1'" ) ) {
	$failures[] = 'Renderer changes will not invalidate existing snapshots.';
}

if ( false === strpos( $renderer, '$snapshot[\'version\'] !== $current_version' ) ) {
	$failures[] = 'Stale snapshot attachments are still rendered after a renderer change.';
}

if ( ! empty( $failures ) ) {
	fwrite( STDERR, implode( "\n", $failures ) . "\n" );
	exit( 1 );
}

echo "Cover thumbnail regression checks passed.\n";
