<?php
/**
 * Registry for page-template definitions supported by the Typst engine.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_page_template_registry() {
	return array(
		'one-column-one-image' => array(
			'id'             => 'one-column-one-image',
			'label'          => '1 col 1 image',
			'placement'      => 'physical-page',
			'placeholder'    => true,
			'future_image'   => true,
		),
	);
}

function almaden_bookster_typst_get_page_template_definition( $template_id ) {
	$templates   = almaden_bookster_typst_page_template_registry();
	$template_id = strtolower( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $template_id ) );

	return $templates[ $template_id ] ?? null;
}
