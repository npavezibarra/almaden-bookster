<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function almaden_bookster_parse_docx_import_document( $path, $filename ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'zip_missing', 'ZipArchive no está disponible en este servidor.' );
	}

	$zip = new ZipArchive();
	if ( true !== $zip->open( $path ) ) {
		return new WP_Error( 'docx_open_failed', 'No se pudo abrir el archivo DOCX.' );
	}

	$document_xml = $zip->getFromName( 'word/document.xml' );
	if ( false === $document_xml ) {
		$zip->close();
		return new WP_Error( 'docx_invalid', 'El DOCX no contiene document.xml.' );
	}

	$style_map = array();
	$styles_xml = $zip->getFromName( 'word/styles.xml' );
	if ( false !== $styles_xml ) {
		$styles_dom = new DOMDocument();
		if ( @$styles_dom->loadXML( $styles_xml ) ) {
			$styles_xpath = new DOMXPath( $styles_dom );
			$styles_xpath->registerNamespace( 'w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main' );
			foreach ( $styles_xpath->query( '//w:style' ) as $style ) {
				$style_id = $style->getAttributeNS( 'http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'styleId' );
				$name_node = $styles_xpath->query( './w:name', $style )->item( 0 );
				$name = $name_node ? $name_node->getAttributeNS( 'http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val' ) : $style_id;
				if ( $style_id ) {
					$style_map[ $style_id ] = $name;
				}
			}
		}
	}
	$zip->close();

	$dom = new DOMDocument();
	$dom->preserveWhiteSpace = true;
	$dom->formatOutput = false;
	if ( ! @$dom->loadXML( $document_xml ) ) {
		return new WP_Error( 'docx_xml_invalid', 'No se pudo leer el XML del DOCX.' );
	}

	$xpath = new DOMXPath( $dom );
	$xpath->registerNamespace( 'w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main' );

	$blocks = array();
	foreach ( $xpath->query( '//w:body/w:p' ) as $paragraph ) {
		$style_id = '';
		$style_name = '';
		$p_style = $xpath->query( './w:pPr/w:pStyle', $paragraph )->item( 0 );
		if ( $p_style instanceof DOMElement ) {
			$style_id = $p_style->getAttributeNS( 'http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val' );
			$style_name = isset( $style_map[ $style_id ] ) ? $style_map[ $style_id ] : $style_id;
		}

		$text = almaden_bookster_docx_paragraph_to_markdown( $paragraph, $xpath );
		$style_key = almaden_bookster_normalize_import_style_key( $style_name );
		$heading_number = almaden_bookster_import_heading_number_from_style_key( $style_key );

		if ( '' === trim( $text ) ) {
			$blocks[] = array(
				'type'          => 'blank',
				'text'          => '',
				'style_key'     => $style_key,
				'style_label'   => $style_name ?: 'Normal',
				'heading_level' => 0,
			);
			continue;
		}

		$is_heading = in_array( $style_key, array( 'title', 'subtitle', 'heading-1', 'heading-2', 'heading-3', 'heading-4', 'heading-5', 'heading-6' ), true );
		$blocks[] = array(
			'type'          => $is_heading ? 'heading' : 'paragraph',
			'text'          => trim( $text ),
			'style_key'     => $style_key,
			'style_label'   => $style_name ?: 'Normal',
			'heading_level' => $heading_number,
		);
	}

	return array(
		'title'       => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
		'blocks'      => $blocks,
		'block_count' => count( array_filter( $blocks, function( $block ) { return 'blank' !== $block['type']; } ) ),
	);
}

function almaden_bookster_docx_paragraph_to_markdown( DOMElement $paragraph, DOMXPath $xpath ) {
	$pieces = array();
	foreach ( $xpath->query( './w:r', $paragraph ) as $run ) {
		$pieces[] = almaden_bookster_docx_run_to_markdown( $run, $xpath );
	}
	return implode( '', $pieces );
}

function almaden_bookster_docx_run_to_markdown( DOMElement $run, DOMXPath $xpath ) {
	$text = '';
	$has_break = false;
	$bold = false;
	$italic = false;
	$underline = false;

	$rpr = $xpath->query( './w:rPr', $run )->item( 0 );
	if ( $rpr instanceof DOMElement ) {
		$bold = $xpath->query( './w:b', $rpr )->length > 0;
		$italic = $xpath->query( './w:i', $rpr )->length > 0;
		$underline = $xpath->query( './w:u', $rpr )->length > 0;
	}

	foreach ( $run->childNodes as $child ) {
		if ( XML_TEXT_NODE === $child->nodeType ) {
			$text .= $child->nodeValue;
		} elseif ( XML_ELEMENT_NODE === $child->nodeType ) {
			if ( 't' === $child->localName || 'delText' === $child->localName || 'instrText' === $child->localName ) {
				$text .= $child->textContent;
			} elseif ( 'br' === $child->localName ) {
				$has_break = true;
			} elseif ( 'tab' === $child->localName ) {
				$text .= "\t";
			}
		}
	}

	if ( $has_break && '' === trim( $text ) ) {
		return "\n";
	}

	$text = html_entity_decode( $text, ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
	$text = htmlspecialchars( $text, ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
	if ( '' === $text ) {
		return '';
	}

	if ( $bold && $italic ) {
		$text = '***' . $text . '***';
	} elseif ( $bold ) {
		$text = '**' . $text . '**';
	} elseif ( $italic ) {
		$text = '*' . $text . '*';
	}

	if ( $underline ) {
		$text = '<u>' . $text . '</u>';
	}

	return $text;
}
