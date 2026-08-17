<?php

$plugin_root = dirname( __DIR__ );
$template = file_get_contents( $plugin_root . '/templates/reader/reader-app.php' );
$endpoint = file_get_contents( $plugin_root . '/includes/reader/highlight-comments.php' );
$page_script = file_get_contents( $plugin_root . '/assets/js/reader/reader-highlights-page.js' );
$navigation = file_get_contents( $plugin_root . '/assets/js/reader/reader-navigation.js' );
$styles = file_get_contents( $plugin_root . '/assets/css/reader-app.css' );

$assertions = array(
	'expanded view exists beside the reader states' => false !== strpos( $template, 'id="almaden-view-highlights"' ),
	'drawer exposes an icon-only expand control' => false !== strpos( $template, 'id="btn-expand-reader-highlights"' ) && false !== strpos( $template, 'aria-label="Expandir highlights"' ),
	'expanded view script is loaded' => false !== strpos( $template, 'reader-highlights-page.js' ),
	'expanded view exposes a full-width toolbar for chapter reading' => false !== strpos( $template, 'reader-highlights-page-toolbar' ) && false !== strpos( $template, 'reader-highlights-page-toolbar-inner' ) && false !== strpos( $styles, '.reader-highlights-page-toolbar {' ) && false !== strpos( $styles, 'width: 100%;' ),
	'feed endpoint is registered' => false !== strpos( $endpoint, "wp_ajax_almaden_list_book_highlights_feed" ),
	'feed endpoint groups comments with highlights' => false !== strpos( $endpoint, "'comments_by_highlight'" ) && false !== strpos( $endpoint, 'highlight_id IN' ),
	'all and chapter filters are supported' => false !== strpos( $page_script, "activeChapterId: 'all'" ) && false !== strpos( $page_script, 'reader-highlights-page-filter' ),
	'chapter toolbar updates the active chapter' => false !== strpos( $page_script, 'renderReaderHighlightsPageToolbar' ) && false !== strpos( $page_script, 'reader-highlights-page-toolbar-title' ),
	'feed cards navigate back to their source highlight' => false !== strpos( $page_script, 'pendingFocusHighlightId' ) && false !== strpos( $page_script, 'showChapterView(chapterIndex)' ),
	'chapter and index navigation close the expanded view' => 2 <= substr_count( $navigation, "getElementById('almaden-view-highlights')" ),
	'mobile chapter filters use a horizontal layout' => false !== strpos( $styles, '.reader-highlights-page-filters' ) && false !== strpos( $styles, 'flex-direction: row;' ),
	'expanded view uses neutral palette overrides' => false !== strpos( $styles, '--reader-neutral-bg' ) && false !== strpos( $styles, '--reader-neutral-text-muted' ),
);

$failed = array_keys( array_filter( $assertions, static fn( $passed ) => ! $passed ) );
if ( ! empty( $failed ) ) {
	fwrite( STDERR, "Reader highlights page regressions failed:\n- " . implode( "\n- ", $failed ) . "\n" );
	exit( 1 );
}

echo "Reader highlights page regression checks passed.\n";
