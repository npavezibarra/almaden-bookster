<?php
/**
 * Typst fragments for page-template image placeholders.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_page_template_placeholder( $template ) {
	$template_id = is_array( $template ) ? ( $template['template_id'] ?? '' ) : '';
	if ( 'one-column-one-image' !== $template_id ) {
		return '';
	}

	return '#rect(width: 100%, height: 100%, fill: rgb("ff9d00"), stroke: 0.5pt + rgb("a85c00"))';
}
