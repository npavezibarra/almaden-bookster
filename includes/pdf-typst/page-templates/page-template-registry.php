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
					'id'           => 'image-1',
					'label'        => 'Imagen 1',
					'kind'         => 'image',
					'aspect_ratio' => array( 'width' => 3, 'height' => 5 ),
				),
			),
		),
		'one-image-one-column' => array(
			'id'          => 'one-image-one-column',
			'label'       => '1 image 1 col',
			'placement'   => 'physical-page',
			'layout'      => 'image-left-split',
			'placeholder' => true,
			'future_image' => true,
			'preview'     => array(
				'type'   => 'image-left-split',
				'left'   => '#f59e0b',
				'right'  => '#cbd5e1',
				'frame'  => '#94a3b8',
				'canvas' => '#ffffff',
			),
			'slots'       => array(
				array(
					'id'           => 'image-1',
					'label'        => 'Imagen 1',
					'kind'         => 'image',
					'aspect_ratio' => array( 'width' => 3, 'height' => 5 ),
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
					'id'           => 'image-1',
					'label'        => 'Imagen 1',
					'kind'         => 'image',
					'aspect_ratio' => array( 'width' => 2, 'height' => 1 ),
				),
			),
		),
		'image-top-two-column-bottom' => array(
			'id'          => 'image-top-two-column-bottom',
			'label'       => '1 image top / 2 col bottom',
			'placement'   => 'physical-page',
			'layout'      => 'image-top-two-column-bottom',
			'placeholder' => true,
			'future_image' => true,
			'preview'     => array(
				'type'   => 'image-top-two-column-bottom',
				'canvas' => '#ffffff',
				'frame'  => '#94a3b8',
				'fill'   => '#f59e0b',
				'text'   => '#cbd5e1',
			),
			'slots'       => array(
				array(
					'id'           => 'image-1',
					'label'        => 'Imagen 1',
					'kind'         => 'image',
					'aspect_ratio' => array( 'width' => 3, 'height' => 1 ),
				),
			),
		),
		'four-images' => array(
			'id'          => 'four-images',
			'label'       => '4 images',
			'placement'   => 'content-area',
			'layout'      => 'four-images-grid',
			'placeholder' => true,
			'future_image' => true,
			'preview'     => array(
				'type'   => 'four-images-grid',
				'canvas' => '#ffffff',
				'frame'  => '#94a3b8',
				'fill'   => '#f59e0b',
			),
			'slots'       => array(
				array(
					'id'           => 'image-1',
					'label'        => 'Imagen 1',
					'kind'         => 'image',
					'aspect_ratio' => array( 'width' => 5, 'height' => 4 ),
				),
				array(
					'id'           => 'image-2',
					'label'        => 'Imagen 2',
					'kind'         => 'image',
					'aspect_ratio' => array( 'width' => 5, 'height' => 4 ),
				),
				array(
					'id'           => 'image-3',
					'label'        => 'Imagen 3',
					'kind'         => 'image',
					'aspect_ratio' => array( 'width' => 5, 'height' => 4 ),
				),
				array(
					'id'           => 'image-4',
					'label'        => 'Imagen 4',
					'kind'         => 'image',
					'aspect_ratio' => array( 'width' => 5, 'height' => 4 ),
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
					'id'           => 'image-1',
					'label'        => 'Imagen 1',
					'kind'         => 'image',
					'aspect_ratio' => array( 'width' => 6, 'height' => 5 ),
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
