<?php
/**
 * Apply print-production page boxes to a Typst PDF without rerasterizing it.
 *
 * Typst clips page backgrounds to the trim area when its native `bleed` option
 * is used. Almaden therefore composes the physical sheet directly and appends
 * an incremental PDF update that declares the inset TrimBox afterwards.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_unit_to_points( $value, $unit ) {
	$factors = array(
		'mm' => 72 / 25.4,
		'cm' => 72 / 2.54,
		'in' => 72,
		'pt' => 1,
	);
	$factor = $factors[ (string) $unit ] ?? $factors['cm'];
	return max( 0, (float) $value ) * $factor;
}

function almaden_bookster_typst_pdf_number( $value ) {
	$number = rtrim( rtrim( number_format( (float) $value, 5, '.', '' ), '0' ), '.' );
	return '-0' === $number || '' === $number ? '0' : $number;
}

/**
 * Add BleedBox and TrimBox entries as a valid incremental PDF update.
 *
 * @return true|WP_Error
 */
function almaden_bookster_typst_apply_print_boxes( $pdf_path, $geometry ) {
	$geometry = is_array( $geometry ) ? $geometry : array();
	$bleed_points = almaden_bookster_typst_unit_to_points( $geometry['bleed'] ?? 0, $geometry['unit'] ?? 'cm' );
	if ( $bleed_points <= 0 ) {
		return true;
	}
	$pdf = is_string( $pdf_path ) && is_file( $pdf_path ) ? file_get_contents( $pdf_path ) : false;
	if ( false === $pdf || 0 !== strpos( $pdf, '%PDF-' ) ) {
		return new WP_Error( 'typst_print_boxes_invalid_pdf', 'No se pudo abrir el PDF para declarar sus cajas de impresión.' );
	}

	$matches = array();
	preg_match_all(
		'/([0-9]+)\s+([0-9]+)\s+obj\s*(<<(?:(?!endobj)[\s\S])*?\/Type\s*\/Page(?!s)\b(?:(?!endobj)[\s\S])*?>>)\s*endobj/',
		$pdf,
		$matches,
		PREG_SET_ORDER
	);
	if ( empty( $matches ) ) {
		return new WP_Error( 'typst_print_boxes_pages_missing', 'No se encontraron páginas editables en el PDF generado.' );
	}

	$updated_objects = array();
	foreach ( $matches as $match ) {
		$dictionary = (string) $match[3];
		$media = array();
		if ( ! preg_match( '/\/MediaBox\s*\[\s*(-?[0-9.]+)\s+(-?[0-9.]+)\s+(-?[0-9.]+)\s+(-?[0-9.]+)\s*\]/', $dictionary, $media ) ) {
			continue;
		}
		$x0 = (float) $media[1];
		$y0 = (float) $media[2];
		$x1 = (float) $media[3];
		$y1 = (float) $media[4];
		if ( $x1 - $x0 <= 2 * $bleed_points || $y1 - $y0 <= 2 * $bleed_points ) {
			return new WP_Error( 'typst_print_boxes_geometry', 'El sangrado supera el tamaño físico de la página.' );
		}

		$media_box = implode( ' ', array_map( 'almaden_bookster_typst_pdf_number', array( $x0, $y0, $x1, $y1 ) ) );
		$trim_box = implode(
			' ',
			array_map(
				'almaden_bookster_typst_pdf_number',
				array( $x0 + $bleed_points, $y0 + $bleed_points, $x1 - $bleed_points, $y1 - $bleed_points )
			)
		);
		$dictionary = preg_replace( '/\s*\/(?:BleedBox|TrimBox)\s*\[[^\]]*\]/', '', $dictionary );
		$dictionary = substr_replace(
			$dictionary,
			' /BleedBox [' . $media_box . '] /TrimBox [' . $trim_box . '] ',
			strrpos( $dictionary, '>>' ),
			0
		);
		$updated_objects[] = array(
			'number'     => (int) $match[1],
			'generation' => (int) $match[2],
			'dictionary' => $dictionary,
		);
	}
	if ( empty( $updated_objects ) ) {
		return new WP_Error( 'typst_print_boxes_media_missing', 'Las páginas del PDF no declaran un MediaBox propio.' );
	}

	$start_matches = array();
	if ( ! preg_match( '/startxref\s+([0-9]+)\s+%%EOF\s*$/s', $pdf, $start_matches ) ) {
		return new WP_Error( 'typst_print_boxes_xref_missing', 'El PDF no contiene una tabla de referencias compatible.' );
	}
	$previous_xref = (int) $start_matches[1];
	$trailer_pos = strrpos( substr( $pdf, 0, strrpos( $pdf, 'startxref' ) ), 'trailer' );
	if ( false === $trailer_pos || ! preg_match( '/trailer\s*(<<[\s\S]*?>>)\s*$/', substr( $pdf, $trailer_pos, strrpos( $pdf, 'startxref' ) - $trailer_pos ), $trailer_match ) ) {
		return new WP_Error( 'typst_print_boxes_trailer_missing', 'El PDF no contiene un trailer compatible.' );
	}
	$trailer = preg_replace( '/\s*\/Prev\s+[0-9]+/', '', (string) $trailer_match[1] );
	$trailer = substr_replace( $trailer, ' /Prev ' . $previous_xref . ' ', strrpos( $trailer, '>>' ), 0 );

	$append = "\n";
	$xref_entries = array();
	foreach ( $updated_objects as $object ) {
		$offset = strlen( $pdf ) + strlen( $append );
		$append .= $object['number'] . ' ' . $object['generation'] . " obj\n" . $object['dictionary'] . "\nendobj\n";
		$xref_entries[] = array(
			'number'     => $object['number'],
			'generation' => $object['generation'],
			'offset'     => $offset,
		);
	}
	usort( $xref_entries, static function ( $left, $right ) { return $left['number'] <=> $right['number']; } );
	$xref_offset = strlen( $pdf ) + strlen( $append );
	$append .= "xref\n";
	foreach ( $xref_entries as $entry ) {
		$append .= $entry['number'] . " 1\n";
		$append .= sprintf( "%010d %05d n \n", $entry['offset'], $entry['generation'] );
	}
	$append .= "trailer\n" . $trailer . "\nstartxref\n" . $xref_offset . "\n%%EOF\n";

	$temporary = $pdf_path . '.print-boxes-' . ( function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid() );
	if ( false === file_put_contents( $temporary, $pdf . $append, LOCK_EX ) || ! rename( $temporary, $pdf_path ) ) {
		@unlink( $temporary );
		return new WP_Error( 'typst_print_boxes_write_failed', 'No se pudieron guardar las cajas de impresión del PDF.' );
	}
	return true;
}
