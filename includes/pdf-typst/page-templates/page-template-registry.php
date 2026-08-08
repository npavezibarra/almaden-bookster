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
			'id'          => 'one-column-one-image',
			'label'       => '1 col 1 image',
			'placement'   => 'physical-page',
			'layout'      => 'split',
			'placeholder' => true,
			'future_image' => true,
			'preview'     => array(
				'type'   => 'split',
				'left'   => '#cbd5e1',
				'right'  => '#f59e0b',
				'frame'  => '#94a3b8',
				'canvas' => '#ffffff',
			),
			'slots'       => array(
				array(
					'id'    => 'image-1',
					'label' => 'Imagen 1',
					'kind'  => 'image',
				),
			),
		),
		'upper-image-bottom-text-split' => array(
			'id'          => 'upper-image-bottom-text-split',
			'label'       => 'Upper Image, Bottom Text Split',
			'placement'   => 'physical-page',
			'layout'      => 'upper-bottom-split',
			'placeholder' => true,
			'future_image' => true,
			'preview'     => array(
				'type'   => 'upper-bottom-split',
				'canvas' => '#ffffff',
				'frame'  => '#94a3b8',
				'fill'   => '#f59e0b',
				'text'   => '#cbd5e1',
			),
			'slots'       => array(
				array(
					'id'    => 'image-1',
					'label' => 'Imagen 1',
					'kind'  => 'image',
				),
			),
		),
		'inner-full-page' => array(
			'id'          => 'inner-full-page',
			'label'       => 'Inner Full Page',
			'placement'   => 'content-area',
			'layout'      => 'full',
			'placeholder' => true,
			'future_image' => true,
			'preview'     => array(
				'type'   => 'full',
				'frame'  => '#9ca3af',
				'margin' => '#f3f4f6',
				'fill'   => '#f59e0b',
				'canvas' => '#ffffff',
			),
			'slots'       => array(
				array(
					'id'    => 'image-1',
					'label' => 'Imagen 1',
					'kind'  => 'image',
				),
			),
		),
	);
}

function almaden_bookster_typst_get_page_template_definition( $template_id ) {
	$templates   = almaden_bookster_typst_page_template_registry();
	$template_id = strtolower( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $template_id ) );

	return $templates[ $template_id ] ?? null;
}
