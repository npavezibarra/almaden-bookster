<?php
/**
 * Build Typst source for book-level chapter parity transitions.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_uses_left_chapter_flow( $settings ) {
	$settings = is_array( $settings ) ? $settings : array();
	$mode = strtolower( trim( (string) ( $settings['book_chapter_flow_mode'] ?? '' ) ) );

	if ( '' !== $mode ) {
		return 'left' === $mode;
	}

	return 'even' === strtolower( trim( (string) ( $settings['chapter_start_parity'] ?? 'any' ) ) );
}

function almaden_bookster_typst_chapter_transition_mode( $settings ) {
	$mode = strtolower( trim( (string) ( $settings['chapter_transition_blank_mode'] ?? 'full_blank' ) ) );

	return in_array( $mode, array( 'full_blank', 'blank_with_header_footer', 'intentional_text' ), true )
		? $mode
		: 'full_blank';
}

function almaden_bookster_typst_chapter_parity_break( $settings, $transition_index = 0 ) {
	if ( ! almaden_bookster_typst_uses_left_chapter_flow( $settings ) ) {
		return '';
	}

	$mode = almaden_bookster_typst_chapter_transition_mode( $settings );
	$transition_index = max( 1, (int) $transition_index );

	return '#metadata("' . $mode . '") <almaden-chapter-parity-break>' . "\n" .
		'#metadata("' . $mode . '") <almaden-transition-' . $transition_index . '>' .
		"\n#pagebreak(to: \"even\")\n\n";
}

function almaden_bookster_typst_chapter_start_breaks( $settings, $rendered, $blank_before, $is_credits ) {
	$uses_left_flow = almaden_bookster_typst_uses_left_chapter_flow( $settings );
	$blank_before = max( 0, (int) $blank_before );
	$source = '';

	if ( $rendered > 0 && ( ! $uses_left_flow || $blank_before > 0 ) ) {
		$source .= "\n#pagebreak()\n\n";
	}
	for ( $blank_index = 0; $blank_index < $blank_before; ++$blank_index ) {
		$source .= '#metadata("' . ( $is_credits ? 'credits-before' : 'chapter-before' ) . '") <almaden-intentional-blank>' . "\n";
		if ( ! $uses_left_flow || $blank_index + 1 < $blank_before ) {
			$source .= "#pagebreak()\n\n";
		}
	}

	return $source . almaden_bookster_typst_chapter_parity_break( $settings, (int) $rendered + 1 );
}
