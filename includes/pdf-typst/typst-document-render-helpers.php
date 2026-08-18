<?php
/**
 * Build a complete Typst book document from editor state.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

require_once __DIR__ . '/typst-document-helpers.php';
require_once __DIR__ . '/typst-toc.php';

function almaden_bookster_typst_chapter_counter_label( $chapter_id ) {
	$chapter_id = preg_replace( '/[^0-9A-Za-z_-]/', '', (string) $chapter_id );
	return '' !== $chapter_id ? 'almaden-chapter-start-' . $chapter_id : '';
}

function almaden_bookster_typst_append_chapter_counter_report( $source, $chapters ) {
	$source = (string) $source;
	$chapters = is_array( $chapters ) ? $chapters : array();
	$report_entries = array();

	foreach ( $chapters as $chapter_index => $chapter ) {
		if ( ! is_array( $chapter ) ) {
			continue;
		}

		$chapter_id = trim( (string) ( $chapter['id'] ?? (string) ( $chapter_index + 1 ) ) );
		$label = almaden_bookster_typst_chapter_counter_label( $chapter_id );
		if ( '' === $label ) {
			continue;
		}

		$kind = 'chapter';
		if ( '1' === (string) ( $chapter['is_toc'] ?? '' ) ) {
			$kind = 'toc';
		} elseif ( almaden_bookster_typst_bool( $chapter['is_credits'] ?? false ) ) {
			$kind = 'credits';
		}

		$report_entries[] = '(sequence: ' . ( $chapter_index + 1 ) . ', id: "' . almaden_bookster_typst_escape_string( $chapter_id ) . '", kind: "' . $kind . '", page: if query(<' . $label . '>).len() > 0 { query(<' . $label . '>).first().location().page() } else { none })';
	}

	if ( empty( $report_entries ) ) {
		return $source;
	}

	return $source . "\n#context [#metadata((" . implode( ', ', $report_entries ) . ")) <almaden-chapter-counter-report>]\n";
}

function almaden_bookster_typst_render_credits( $config, $author_label, $book_title, $fallbacks, &$assets, $resolve_font, $cover_settings = array(), $asset_mode = 'original' ) {
	$config = is_array( $config ) ? $config : array();
	$styles = isset( $config['section_styles'] ) && is_array( $config['section_styles'] ) ? $config['section_styles'] : array();
	$order  = isset( $config['section_order'] ) && is_array( $config['section_order'] )
		? $config['section_order']
		: array( 'logos', 'people', 'editorial', 'legal', 'collaborators' );
	$sections = array();
	$role_labels = array(
		'author' => 'Autor', 'coauthor' => 'Coautor', 'editor' => 'Editor', 'translator' => 'Traductor',
		'designer' => 'Diseñador', 'proofreader' => 'Corrector', 'photographer' => 'Fotógrafo', 'other' => 'Otro',
	);
	$license_labels = array( 'all_rights_reserved' => 'Todos los derechos reservados', 'creative_commons' => 'Creative Commons' );
	$format_date = static function ( $value ) {
		if ( ! preg_match( '/^(\d{4})-(\d{2})/', trim( (string) $value ), $match ) ) {
			return trim( (string) $value );
		}
		$months = array( 1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre' );
		return ( $months[ (int) $match[2] ] ?? $match[2] ) . ' ' . $match[1];
	};
	$text = static function ( $value ) {
		return almaden_bookster_typst_escape_markup( trim( (string) $value ) );
	};

	$editorial = is_array( $config['editorial'] ?? null ) ? $config['editorial'] : array();
	$people_style = almaden_bookster_typst_credits_text_style(
		$styles['people'] ?? array(),
		$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font, $fallbacks['tracking'] ?? 0, $fallbacks['align'] ?? ''
	);
	$people_gap_px = is_numeric( $styles['people']['item_gap_px'] ?? null ) ? max( 0, min( 80, (float) $styles['people']['item_gap_px'] ) ) : 10;
	$people_align = almaden_bookster_typst_credits_alignment( $people_style['align'] ?? '' );
	$editorial_rows = array();
	if ( '' !== trim( (string) ( $editorial['edition_number'] ?? '' ) ) ) {
		$editorial_rows[] = '#strong[' . $text( max( 0, (int) $editorial['edition_number'] ) . '.ª edición' ) . ']';
	}
	foreach ( array( 'publication_date' => 'Fecha de publicación', 'isbn' => 'ISBN', 'printer' => 'Imprenta' ) as $key => $label ) {
		$value = 'publication_date' === $key ? $format_date( $editorial[ $key ] ?? '' ) : trim( (string) ( $editorial[ $key ] ?? '' ) );
		if ( '' !== $value ) {
			$editorial_rows[] = '#strong[' . $text( $label . ':' ) . '] ' . $text( $value );
		}
	}
	$editorial_style = almaden_bookster_typst_credits_text_style(
		$styles['editorial'] ?? array(),
		$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font, $fallbacks['tracking'] ?? 0, $fallbacks['align'] ?? ''
	);
	$sections['editorial'] = almaden_bookster_typst_credits_line_block( implode( "\n#linebreak()\n", $editorial_rows ), $editorial_style['align'] );

	$people_rows = array();
	foreach ( (array) ( $config['people'] ?? array() ) as $person ) {
		$parts = array();
		$name  = trim( (string) ( $person['name'] ?? '' ) );
		$role  = trim( (string) ( $person['role'] ?? '' ) );
		$custom_role_title = trim( (string) ( $person['custom_role_title'] ?? '' ) );
		if ( '' !== $name ) {
			$parts[] = '#strong[' . $text( $name ) . ']';
		}
		if ( '' !== $role ) {
			$parts[] = '#emph[' . $text( 'other' === $role && '' !== $custom_role_title ? $custom_role_title : ( $role_labels[ $role ] ?? $role ) ) . ']';
		} elseif ( '' !== $custom_role_title ) {
			$parts[] = '#emph[' . $text( $custom_role_title ) . ']';
		}
		if ( ! empty( $person['show_contact'] ) ) {
			foreach ( array( 'email', 'website' ) as $key ) {
				$value = trim( (string) ( $person[ $key ] ?? '' ) );
				if ( 'website' === $key ) {
					$value = preg_replace( '#^https?://#i', '', $value );
				}
				if ( '' !== $value ) {
					$parts[] = $text( $value );
				}
			}
		}
		if ( $parts ) {
			$people_rows[] = almaden_bookster_typst_credits_line_block(
				'#block(breakable: false)[' . "\n" . implode( "\n#linebreak()\n", $parts ) . "\n]",
				$people_align
			);
		}
	}
	$people_gap_pt = round( $people_gap_px * 0.75, 3 );
	$sections['people'] = implode( $people_gap_pt > 0 ? "\n#v(" . $people_gap_pt . "pt)\n" : "\n\n", $people_rows );

	$collaborator_rows = array();
	$collaborator_section_style = almaden_bookster_typst_credits_text_style(
		$styles['collaborators'] ?? array(),
		$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font, $fallbacks['tracking'] ?? 0, $fallbacks['align'] ?? ''
	);
	$collaborator_styles = is_array( $config['collaborators_styles'] ?? null ) ? $config['collaborators_styles'] : array();
	$collaborator_title_style = almaden_bookster_typst_credits_text_style(
		$collaborator_styles['title'] ?? array(),
		$collaborator_section_style['family'], $collaborator_section_style['size'], $collaborator_section_style['weight'], $fallbacks['line_height'], $resolve_font,
		$collaborator_section_style['tracking'], $collaborator_section_style['align']
	);
	$collaborator_item_style = almaden_bookster_typst_credits_text_style(
		$collaborator_styles['item'] ?? array(),
		$collaborator_section_style['family'], $collaborator_section_style['size'], $collaborator_section_style['weight'], $fallbacks['line_height'], $resolve_font,
		$collaborator_section_style['tracking'], $collaborator_section_style['align']
	);
	$collaborator_image_width = is_numeric( $collaborator_styles['image_max_width'] ?? null )
		? max( 24, min( 400, (float) $collaborator_styles['image_max_width'] ) ) * 0.75
		: 72;
	$collaborator_title = trim( (string) ( $config['collaborators_title'] ?? '' ) );
	$collaborator_entries = array();
	if ( ! isset( $config['collaborators_visible'] ) || ! empty( $config['collaborators_visible'] ) ) {
		foreach ( (array) ( $config['collaborators'] ?? array() ) as $collaborator ) {
			$parts = array();
			$text_parts = array();
			$image = almaden_bookster_typst_register_upload( $collaborator['logo_url'] ?? '', $assets, $asset_mode );
			if ( '' !== $image ) {
				$parts[] = almaden_bookster_typst_credits_line_block( '#box(width: ' . round( $collaborator_image_width, 2 ) . 'pt)[#image("' . almaden_bookster_typst_escape_string( $image ) . '", width: 100%, fit: "contain")]', $collaborator_section_style['align'] );
			}
			foreach ( array( 'name', 'type', 'website', 'text' ) as $key ) {
				$value = trim( (string) ( $collaborator[ $key ] ?? '' ) );
				if ( 'website' === $key ) {
					$value = preg_replace( '#^https?://#i', '', $value );
				}
				if ( '' !== $value ) {
					$text_parts[] = $text( $value );
				}
			}
			if ( $text_parts ) {
				$parts[] = almaden_bookster_typst_credits_styled_block( implode( "\n#linebreak()\n", $text_parts ), $collaborator_item_style );
			}
			if ( $parts ) {
				$collaborator_entries[] = '#block(breakable: false)[' . "\n" . implode( "\n#v(4pt)\n", $parts ) . "\n]";
			}
		}
	}
	if ( $collaborator_entries ) {
		if ( '' !== $collaborator_title ) {
			$collaborator_rows[] = almaden_bookster_typst_credits_styled_block( $text( $collaborator_title ), $collaborator_title_style );
		}
		$collaborator_rows = array_merge( $collaborator_rows, $collaborator_entries );
	}
	$sections['collaborators'] = implode( "\n\n", $collaborator_rows );

	$logo_rows = array();
	foreach ( (array) ( $config['logos'] ?? array() ) as $logo ) {
		$parts = array();
		$logo_align = almaden_bookster_typst_credits_alignment( $logo['position'] ?? 'center' );
		$logo_source = strtolower( trim( (string) ( $logo['logo_source'] ?? 'image' ) ) );
		if ( 'text' === $logo_source ) {
			$resolved_title = trim( (string) $book_title );
			if ( '' !== $resolved_title ) {
				$title_text = almaden_bookster_typst_transform_title( $resolved_title, $logo['title_text_transform'] ?? 'none' );
				$title_style = almaden_bookster_typst_credits_text_style(
					array(
						'font_family'    => $logo['title_font_family'] ?? '',
						'font_size'      => $logo['title_font_size'] ?? '',
						'font_weight'    => $logo['title_font_weight'] ?? '700',
						'line_height'    => $logo['title_line_height'] ?? 1.05,
						'text_align'     => $logo_align,
						'letter_spacing' => $logo['title_letter_spacing'] ?? 0,
					),
					$fallbacks['family'], 24, '700', 1.05, $resolve_font, 0, $logo_align
				);
				$parts[] = almaden_bookster_typst_credits_styled_block( $text( $title_text ), $title_style );
			}
		} else {
			$image_url = almaden_bookster_typst_resolve_credits_logo_url( $logo, $cover_settings );
			$image = almaden_bookster_typst_register_upload( $image_url, $assets, $asset_mode );
			if ( '' !== $image ) {
				$size = max( 24, min( 400, (int) ( $logo['size_px'] ?? 120 ) ) ) * 0.75;
				$parts[] = '#align(' . $logo_align . ')[#box(width: ' . round( $size, 2 ) . 'pt)[#image("' . almaden_bookster_typst_escape_string( $image ) . '", width: 100%, fit: "contain")]]';
			}
		}
		if ( almaden_bookster_typst_bool( $logo['show_author_name'] ?? false ) && '' !== trim( (string) $author_label ) ) {
			$author_style = almaden_bookster_typst_credits_text_style(
				array(
					'font_family'   => $logo['author_font_family'] ?? '',
					'font_size'     => $logo['author_font_size'] ?? 16,
					'font_weight'   => $logo['author_font_weight'] ?? $fallbacks['weight'],
					'letter_spacing' => $logo['author_letter_spacing'] ?? 0,
					'text_align'    => $logo['position'] ?? 'center',
				),
				$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font
			);
			$author = $text( almaden_bookster_typst_transform_title( $author_label, $logo['author_text_transform'] ?? 'none' ) );
			$parts[] = almaden_bookster_typst_credits_styled_block( $author, $author_style );
		}
		if ( $parts ) {
			$logo_rows[] = implode( "\n#v(" . max( 0, (int) ( $logo['author_gap_px'] ?? 10 ) ) * 0.75 . "pt)\n", $parts );
		}
	}
	$sections['logos'] = implode( "\n\n", $logo_rows );

	$legal = is_array( $config['legal'] ?? null ) ? $config['legal'] : array();
	$legal_rows = array();
	if ( '' !== trim( (string) ( $legal['copyright_text'] ?? '' ) ) ) {
		$legal_rows[] = $text( $legal['copyright_text'] );
	}
	if ( '' !== trim( (string) ( $legal['license'] ?? '' ) ) ) {
		$legal_rows[] = $text( $license_labels[ $legal['license'] ] ?? $legal['license'] );
	}
	$legal_style = almaden_bookster_typst_credits_text_style(
		$styles['legal'] ?? array(),
		$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font, $fallbacks['tracking'] ?? 0, $fallbacks['align'] ?? ''
	);
	$sections['legal'] = almaden_bookster_typst_credits_line_block( implode( "\n#linebreak()\n", $legal_rows ), $legal_style['align'] );

	$main_sections = array();
	$collaborator_sections = array();
	foreach ( $order as $section_id ) {
		if ( empty( $sections[ $section_id ] ) ) {
			continue;
		}
		if ( 'collaborators' === $section_id ) {
			$collaborator_sections[] = $section_id;
			continue;
		}
		$main_sections[] = $section_id;
	}

	$output = '';
	if ( ! empty( $main_sections ) ) {
		$main_content = '';
		foreach ( $main_sections as $section_index => $section_id ) {
			$section_style = almaden_bookster_typst_credits_text_style(
				$styles[ $section_id ] ?? array(),
				$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font, $fallbacks['tracking'] ?? 0, $fallbacks['align'] ?? ''
			);
			$main_content .= almaden_bookster_typst_credits_styled_block( $sections[ $section_id ], $section_style );
			if ( $section_index < count( $main_sections ) - 1 ) {
				$main_content .= $section_style['separator'] ? "\n#v(6pt)\n#line(length: 100%, stroke: 0.25pt)\n#v(6pt)\n" : "\n#v(8pt)\n";
			}
		}

		if ( '' !== trim( $main_content ) ) {
			$vertical_align = almaden_bookster_typst_credits_vertical_alignment( $config['vertical_align'] ?? 'bottom' );
			$place_alignment = 'center + top';
			if ( 'center' === $vertical_align ) {
				$place_alignment = 'center + horizon';
			} elseif ( 'bottom' === $vertical_align ) {
				$place_alignment = 'center + bottom';
			}

			$output .= "#block(width: 100%, height: 100%)[\n";
			$output .= '#align(' . $place_alignment . ')[ ' . "\n";
			$output .= "#block(width: 100%, breakable: false)[\n";
			$output .= $main_content . "\n";
			$output .= "]\n";
			$output .= "]\n";
			$output .= "]\n";
		}
	}

	if ( ! empty( $collaborator_sections ) ) {
		$collaborator_output = '';
		foreach ( $collaborator_sections as $section_index => $section_id ) {
			$section_style = almaden_bookster_typst_credits_text_style(
				$styles[ $section_id ] ?? array(),
				$fallbacks['family'], $fallbacks['size'], $fallbacks['weight'], $fallbacks['line_height'], $resolve_font, $fallbacks['tracking'] ?? 0, $fallbacks['align'] ?? ''
			);
			$collaborator_output .= almaden_bookster_typst_credits_styled_block( $sections[ $section_id ], $section_style );
			if ( $section_index < count( $collaborator_sections ) - 1 ) {
				$collaborator_output .= $section_style['separator'] ? "\n#v(6pt)\n#line(length: 100%, stroke: 0.25pt)\n#v(6pt)\n" : "\n#v(8pt)\n";
			}
		}

		if ( '' !== trim( $collaborator_output ) ) {
			if ( '' !== trim( $output ) ) {
				$output .= "#pagebreak()\n";
			}
			$output .= $collaborator_output;
		}
	}

	return $output;
}

function almaden_bookster_typst_pt_to_unit( $pt, $unit ) {
	$pt = (float) $pt;
	switch ( $unit ) {
		case 'mm':
			return $pt * 25.4 / 72.0;
		case 'cm':
			return $pt * 2.54 / 72.0;
		case 'in':
			return $pt / 72.0;
		case 'pt':
		default:
			return $pt;
	}
}

function almaden_bookster_typst_length_literal( $value, $unit ) {
	return round( (float) $value, 4 ) . $unit;
}

function almaden_bookster_typst_running_element_has_content( $type, $custom = '' ) {
	$type = almaden_bookster_typst_normalize_header_footer_type( $type );
	if ( 'blank' === $type ) {
		return false;
	}
	if ( 'custom' === $type ) {
		return '' !== trim( (string) $custom );
	}
	return true;
}

/**
 * Register an image only when it resolves inside the WordPress uploads directory.
 */
