<?php
/**
 * Static regression checks for the non-blocking Typst preview experience.
 */

$root       = dirname( __DIR__ );
$experience = $root . '/assets/js/pdf/typst/editor-typst-preview-experience.js';
$provisional = $root . '/assets/js/pdf/typst/editor-typst-provisional-text.js';
$loader     = $root . '/templates/editor/editor-app-scripts.php';
$failures   = array();

if ( ! is_file( $experience ) ) {
	$failures[] = 'Missing Typst preview experience module.';
} else {
	$source = file_get_contents( $experience );
	$required_fragments = array(
		'const QUIET_DELAY_MS = 1000',
		'const ACTION_DELAY_MS = 0',
		'const MAX_WAIT_MS = 3000',
		'let activeCompilePromise = null',
		"layer.id = 'typst-preview-continuity-layer'",
		'continuity.scroller.replaceChildren(...continuity.originalNodes)',
		'window.compilePDFPreview = compileWithContinuity',
		'compileEditorAction',
	);
	foreach ( $required_fragments as $fragment ) {
		if ( false === strpos( $source, $fragment ) ) {
			$failures[] = 'Missing experience contract: ' . $fragment;
		}
	}
	if ( substr_count( $source, 'chapter.content =') > 0 ) {
		$failures[] = 'The preview experience must not write manuscript content.';
	}
}

$toolbar = $root . '/assets/js/editor/toolbar/toolbar-core.js';
if ( ! is_file( $toolbar ) ) {
	$failures[] = 'Missing toolbar core module.';
} else {
	$toolbar_source = file_get_contents( $toolbar );
	foreach ( array( 'refreshPreviewAfterToolbarAction', 'compileEditorAction(true)' ) as $fragment ) {
		if ( false === strpos( $toolbar_source, $fragment ) ) {
			$failures[] = 'Missing immediate toolbar contract: ' . $fragment;
		}
	}
}

if ( ! is_file( $provisional ) ) {
	$failures[] = 'Missing Typst provisional text module.';
} else {
	$provisional_source = file_get_contents( $provisional );
	foreach ( array( 'Vista provisional', 'showPending', 'settle', 'data-typst-provisional-text' ) as $fragment ) {
		if ( false === strpos( $provisional_source, $fragment ) ) {
			$failures[] = 'Missing provisional text contract: ' . $fragment;
		}
	}
}

$loader_source = file_get_contents( $loader );
$engine_at      = strpos( $loader_source, 'editor-typst-pdf.js');
$experience_at  = strpos( $loader_source, 'editor-typst-preview-experience.js');
$provisional_at = strpos( $loader_source, 'editor-typst-provisional-text.js');
if ( false === $engine_at || false === $experience_at || false === $provisional_at || $experience_at < $engine_at || $provisional_at < $experience_at ) {
	$failures[] = 'The experience module must load after the Typst engine.';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Typst preview experience regression checks passed.\n";
