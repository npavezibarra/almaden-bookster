<?php
/**
 * AlmadenBookster — Editor App State Initialization Script
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>
	window.onerror = function(msg, url, line, col, error) {
		alert("JS Error: " + msg + "\nLine: " + line + "\nFile: " + url.split('/').pop());
		return false;
	};
	// Estado Global
	let bookState = {
		title: <?php echo json_encode( $book_title ); ?>,
		chapters: <?php echo json_encode( $saved_chapters ); ?>,
		activeChapterId: localStorage.getItem('almaden_active_chapter_<?php echo $book_id; ?>') || <?php echo json_encode( !empty($saved_chapters) ? $saved_chapters[0]['id'] : '' ); ?>,
		theme: "light",
		viewMode: "split",
		bookId: <?php echo $book_id; ?>,
		bookAuthorLabel: <?php echo json_encode( $book_author_label ); ?>,
		bookAuthorsInputValue: <?php echo json_encode( $book_authors_input_value ); ?>,
		ajaxUrl: <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		nonce: <?php echo json_encode( wp_create_nonce( 'almaden_save_book_nonce_' . $book_id ) ); ?>,
		fontsNonce: <?php echo json_encode( wp_create_nonce( 'almaden_fonts_nonce' ) ); ?>,
		mediaPickerNonce: <?php echo json_encode( wp_create_nonce( 'almaden_bookster_media_picker_' . $book_id ) ); ?>,

		settings: <?php echo json_encode( $pdf_settings ); ?>,
		settingsNonce: <?php echo json_encode( wp_create_nonce( 'almaden_save_settings_nonce_' . $book_id ) ); ?>,
		documentImportNonce: <?php echo json_encode( wp_create_nonce( 'almaden_document_import_nonce_' . $book_id ) ); ?>,
		installedFonts: <?php echo json_encode( $installed_fonts ); ?>,
		coverSettings: <?php echo json_encode( get_post_meta( $book_id, '_almaden_cover_settings', true ) ?: get_post_meta( $source_book_id, '_almaden_cover_settings', true ) ); ?>,
		pdfPreview: {
			mode: <?php echo json_encode( $pdf_settings['pdf_preview_mode'] ?? 'chapter' ); ?>,
			assetMode: <?php echo json_encode( $pdf_settings['pdf_preview_asset_mode'] ?? 'optimized' ); ?>,
			counterMode: <?php echo json_encode( $pdf_settings['pdf_preview_counter_mode'] ?? 'global' ); ?>,
			universalCounter: {
				version: 1,
				ready: false,
				source: 'full-book',
				totals: {
					pages: null,
					blankPages: null,
					chapters: null
				},
				chapters: []
			}
		},
		commerce: <?php echo json_encode( array(
			'woocommerceActive' => ! empty( $woocommerce_status['active'] ),
			'woocommerceInstalled' => ! empty( $woocommerce_status['installed'] ),
			'relation' => $commerce_relation,
		) ); ?>
	};
	bookState.chapters.forEach((chapter) => {
		chapter._lastSavedContent = String(chapter.content || '');
	});
	window.bookState = bookState;
	window.PagedConfig = {
		auto: false,
		settings: {
			hyphenGlyph: '-'
		}
	};
</script>
