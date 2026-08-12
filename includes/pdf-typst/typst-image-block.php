<?php
/**
 * Render editable image figures and their geometry markers for Typst.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_image_number( $value, $fallback, $min, $max ) {
	$value = is_numeric( $value ) ? (float) $value : (float) $fallback;
	return max( $min, min( $max, $value ) );
}

function almaden_bookster_typst_image_align( $value ) {
	return in_array( $value, array( 'left', 'center', 'right' ), true ) ? $value : 'left';
}

function almaden_bookster_typst_image_origin( $value ) {
	preg_match_all( '/[0-9.]+/', (string) $value, $matches );
	$x = (float) ( $matches[0][0] ?? 50 );
	$y = (float) ( $matches[0][1] ?? 50 );
	$horizontal = $x < 34 ? 'left' : ( $x > 66 ? 'right' : 'center' );
	$vertical = $y < 34 ? 'top' : ( $y > 66 ? 'bottom' : 'horizon' );
	return $vertical . ' + ' . $horizontal;
}

function almaden_bookster_typst_image_report( $block_id, $body ) {
	if ( '' === $block_id ) {
		return $body;
	}
	$token = substr( hash( 'sha256', $block_id ), 0, 20 );
	$start = 'almaden-image-start-' . $token;
	$end   = 'almaden-image-end-' . $token;
	$report = '#context [#metadata((' .
		'id: "' . almaden_bookster_typst_escape_string( $block_id ) . '", ' .
		'page: query(<' . $start . '>).first().location().page(), ' .
		'x: query(<' . $start . '>).first().location().position().x, ' .
		'y: query(<' . $start . '>).first().location().position().y, ' .
		'bottom: query(<' . $end . '>).first().location().position().y' .
		')) <almaden-image-report>]';

	return '#block(breakable: false, width: 100%)[' . "\n" .
		'#metadata("") <' . $start . ">\n" . $body . "\n" .
		'#metadata("") <' . $end . ">\n]" . "\n" . $report;
}

function almaden_bookster_typst_render_content_image_block( $html, &$assets, $options = array() ) {
	$html = trim( (string) $html );
	if ( '' === $html || ! preg_match( '/<img\b[^>]*>/i', $html, $img_match ) ) {
		return '';
	}
	$figure_attrs = preg_match( '/<figure\b[^>]*>/i', $html, $figure_match )
		? almaden_bookster_typst_parse_html_attributes( $figure_match[0] ) : array();
	$img_attrs = almaden_bookster_typst_parse_html_attributes( $img_match[0] );
	$asset_mode = (string) ( $options['asset_mode'] ?? 'original' );
	$source = array(
		'url'          => trim( (string) ( $img_attrs['src'] ?? '' ) ),
		'original_url' => trim( (string) ( $img_attrs['data-original-src'] ?? $img_attrs['src'] ?? '' ) ),
		'preview_url'  => trim( (string) ( $img_attrs['data-preview-src'] ?? '' ) ),
	);
	$image_url = almaden_bookster_typst_resolve_image_url_for_asset_mode( $source, $asset_mode );
	$image_asset = almaden_bookster_typst_register_upload( array_merge( $source, array( 'url' => $image_url ) ), $assets, $asset_mode );
	if ( '' === $image_asset ) {
		return '';
	}

	$mode = 'fixed' === ( $figure_attrs['data-height-mode'] ?? 'auto' ) ? 'fixed' : 'auto';
	$height_percent = almaden_bookster_typst_image_number( $figure_attrs['data-height-percent'] ?? 45, 45, 15, 90 );
	$content_height = almaden_bookster_typst_image_number( $options['content_height'] ?? 20, 20, 1, 100 );
	$unit = in_array( $options['unit'] ?? 'cm', array( 'mm', 'cm', 'in', 'pt' ), true ) ? $options['unit'] : 'cm';
	$margin_top = almaden_bookster_typst_image_number( $figure_attrs['data-margin-top-mm'] ?? 0, 0, 0, 30 );
	$margin_bottom = almaden_bookster_typst_image_number( $figure_attrs['data-margin-bottom-mm'] ?? 0, 0, 0, 30 );
	$gap = almaden_bookster_typst_image_number( $figure_attrs['data-caption-gap-mm'] ?? 1.5, 1.5, 0, 10 );
	$caption = preg_match( '/<figcaption\b[^>]*>([\s\S]*?)<\/figcaption>/i', $html, $caption_match )
		? trim( html_entity_decode( preg_replace( '/<[^>]+>/', ' ', $caption_match[1] ), ENT_QUOTES, 'UTF-8' ) ) : '';
	$unit_per_mm = array( 'mm' => 1, 'cm' => 0.1, 'in' => 1 / 25.4, 'pt' => 72 / 25.4 );
	$reserved = ( $margin_top + $margin_bottom + ( '' !== $caption ? $gap + 6 : 0 ) ) * $unit_per_mm[ $unit ];
	$height = round( min( $content_height * $height_percent / 100, max( $content_height * 0.15, $content_height - $reserved ) ), 4 );
	$fit = (string) ( $figure_attrs['data-fit'] ?? 'cover' );
	$fit = in_array( $fit, array( 'cover', 'contain' ), true ) ? $fit : 'cover';
	$zoom = almaden_bookster_typst_image_number( $figure_attrs['data-zoom'] ?? 1, 1, 0.5, 2.5 );
	$zoom_percent = round( $zoom * 100, 2 ) . '%';
	$origin = almaden_bookster_typst_image_origin( $figure_attrs['data-position'] ?? '50% 50%' );
	$fixed_image = '#image("' . almaden_bookster_typst_escape_string( $image_asset ) . '", width: 100%, height: ' . $height . $unit . ', fit: "' . $fit . '")';
	$image = 'fixed' === $mode
		? '#box(width: 100%, height: ' . $height . $unit . ', clip: true)[#scale(x: ' . $zoom_percent . ', y: ' . $zoom_percent . ', origin: ' . $origin . ')[' . $fixed_image . ']]'
		: '#image("' . almaden_bookster_typst_escape_string( $image_asset ) . '", width: 100%)';

	$body = '#v(' . $margin_top . 'mm)' . "\n";
	$body .= '#align(center)[' . $image . ']';
	if ( '' !== $caption ) {
		$align = almaden_bookster_typst_image_align( $figure_attrs['data-caption-align'] ?? 'left' );
		$body .= "\n#v(" . $gap . "mm)\n#align(" . $align . ')[#text(size: 8.5pt, style: "italic")[ ' . almaden_bookster_typst_escape_markup( $caption ) . ' ]]';
	}
	$body .= "\n#v(" . $margin_bottom . 'mm)';

	return almaden_bookster_typst_image_report( trim( (string) ( $figure_attrs['data-image-block-id'] ?? '' ) ), $body );
}
