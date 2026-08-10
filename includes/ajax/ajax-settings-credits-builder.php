<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build an in-memory chapter body for legacy/generic PDF render paths.
 *
 * The canonical source remains _almaden_credits_config. This fallback prevents
 * an older renderer from printing the historical placeholder stored in
 * book_chapter.post_content.
 */
function almaden_bookster_build_credits_chapter_content( $credits_config, $book_author_label = '' ) {
	$config = almaden_bookster_normalize_credits_config( $credits_config );
	$section_html = array(
		'editorial'     => '',
		'people'        => '',
		'collaborators' => '',
		'logos'         => '',
		'legal'         => '',
	);

	$format_date = static function ( $value ) {
		$value = trim( (string) $value );
		if ( ! preg_match( '/^(\d{4})-(\d{2})(?:-\d{2})?$/', $value, $matches ) ) {
			return $value;
		}

		$months = array(
			1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
			'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
		);
		$month = (int) $matches[2];
		return isset( $months[ $month ] ) ? $months[ $month ] . ' ' . $matches[1] : $value;
	};

	$role_labels = array(
		'author'       => 'Autor',
		'coauthor'     => 'Coautor',
		'editor'       => 'Editor',
		'translator'   => 'Traductor',
		'designer'     => 'Diseñador',
		'proofreader'  => 'Corrector',
		'photographer' => 'Fotógrafo',
		'other'        => 'Otro',
	);
	$license_labels = array(
		'all_rights_reserved' => 'Todos los derechos reservados',
		'creative_commons'    => 'Creative Commons',
	);
	$people_style = isset( $config['section_styles']['people'] ) && is_array( $config['section_styles']['people'] )
		? $config['section_styles']['people']
		: array();
	$people_text_align = in_array( $people_style['text_align'] ?? '', array( 'left', 'center', 'right' ), true ) ? $people_style['text_align'] : '';
	$people_align_items = 'right' === $people_text_align ? 'flex-end' : ( 'center' === $people_text_align ? 'center' : ( 'left' === $people_text_align ? 'flex-start' : '' ) );
	$people_alignment_style = $people_text_align ? 'text-align:' . $people_text_align . ';text-align-last:' . $people_text_align . ';' : '';
	$people_align_items_style = $people_align_items ? 'align-items:' . $people_align_items . ';' : '';
	$people_align_self_style = $people_align_items ? 'align-self:' . $people_align_items . ';' : '';

	$editorial_rows = array();
	if ( '' !== trim( (string) $config['editorial']['edition_number'] ) ) {
		$edition = absint( $config['editorial']['edition_number'] );
		$editorial_rows[] = $edition > 0
			? sprintf( '<p><strong>%s edición</strong></p>', esc_html( $edition . '.ª' ) )
			: '';
	}
	if ( '' !== trim( (string) $config['editorial']['publication_date'] ) ) {
		$editorial_rows[] = '<p><strong>Fecha de publicación:</strong> ' . esc_html( $format_date( $config['editorial']['publication_date'] ) ) . '</p>';
	}
	if ( '' !== trim( (string) $config['editorial']['isbn'] ) ) {
		$editorial_rows[] = '<p><strong>ISBN:</strong> ' . esc_html( $config['editorial']['isbn'] ) . '</p>';
	}
	if ( '' !== trim( (string) $config['editorial']['printer'] ) ) {
		$editorial_rows[] = '<p><strong>Imprenta:</strong> ' . esc_html( $config['editorial']['printer'] ) . '</p>';
	}
	$editorial_rows = array_filter( $editorial_rows );
	if ( $editorial_rows ) {
		$section_html['editorial'] = '<div class="credits-editorial-section">' . implode( '', $editorial_rows ) . '</div>';
	}

	$people_rows = array();
	foreach ( $config['people'] as $person ) {
		$name = trim( (string) ( $person['name'] ?? '' ) );
		$role = trim( (string) ( $person['role'] ?? '' ) );
		$custom_role_title = trim( (string) ( $person['custom_role_title'] ?? '' ) );
		$email = trim( (string) ( $person['email'] ?? '' ) );
		$website = trim( (string) ( $person['website'] ?? '' ) );
		if ( '' === $name && '' === $role && '' === $custom_role_title && '' === $email && '' === $website ) {
			continue;
		}

		$row_parts = array();
		if ( '' !== $name ) {
			$row_parts[] = '<span class="credits-person-name" style="display:block;width:fit-content;max-width:100%;' . esc_attr( $people_align_self_style ) . 'white-space:normal;overflow-wrap:anywhere;' . esc_attr( $people_alignment_style ) . 'font-weight:700;">' . esc_html( $name ) . '</span>';
		}
		if ( '' !== $role ) {
			$role_label = 'other' === $role && '' !== $custom_role_title ? $custom_role_title : ( $role_labels[ $role ] ?? $role );
			$row_parts[] = '<span class="credits-person-role" style="display:block;width:fit-content;max-width:100%;' . esc_attr( $people_align_self_style ) . 'white-space:normal;overflow-wrap:anywhere;' . esc_attr( $people_alignment_style ) . 'font-style:italic;"><em>' . esc_html( $role_label ) . '</em></span>';
		} elseif ( '' !== $custom_role_title ) {
			$row_parts[] = '<span class="credits-person-role" style="display:block;width:fit-content;max-width:100%;' . esc_attr( $people_align_self_style ) . 'white-space:normal;overflow-wrap:anywhere;' . esc_attr( $people_alignment_style ) . 'font-style:italic;"><em>' . esc_html( $custom_role_title ) . '</em></span>';
		}
		if ( ! empty( $person['show_contact'] ) ) {
			if ( '' !== $email ) {
				$row_parts[] = '<span class="credits-person-email" style="display:block;width:fit-content;max-width:100%;' . esc_attr( $people_align_self_style ) . 'white-space:normal;overflow-wrap:anywhere;' . esc_attr( $people_alignment_style ) . 'font-style:italic;">' . esc_html( $email ) . '</span>';
			}
			if ( '' !== $website ) {
				$row_parts[] = '<span class="credits-person-website" style="display:block;width:fit-content;max-width:100%;' . esc_attr( $people_align_self_style ) . 'white-space:normal;overflow-wrap:anywhere;' . esc_attr( $people_alignment_style ) . '">' . esc_html( preg_replace( '#^https?://#i', '', $website ) ) . '</span>';
			}
		}
		if ( $row_parts ) {
			$people_rows[] = '<div class="credits-person-entry" style="display:flex;flex-direction:column;' . esc_attr( $people_align_items_style ) . 'width:100%;margin:0 0 0.9em 0;' . esc_attr( $people_alignment_style ) . '">' . implode( '<div style="height:2px;"></div>', $row_parts ) . '</div>';
		}
	}
	if ( $people_rows ) {
		$section_html['people'] = '<div class="credits-people-section" style="display:flex;flex-direction:column;' . esc_attr( $people_align_items_style ) . 'width:100%;' . esc_attr( $people_alignment_style ) . '">' . implode( '', $people_rows ) . '</div>';
	}

	if ( ! empty( $config['collaborators_visible'] ) ) {
		$collaborator_rows = array();
		foreach ( $config['collaborators'] as $collaborator ) {
			$name = trim( (string) ( $collaborator['name'] ?? '' ) );
			$website = trim( (string) ( $collaborator['website'] ?? '' ) );
			$text = trim( (string) ( $collaborator['text'] ?? '' ) );
			$logo_url = trim( (string) ( $collaborator['logo_url'] ?? '' ) );
			if ( '' === $name && '' === $website && '' === $text && '' === $logo_url ) {
				continue;
			}

			$row = '<div class="credits-collaborator-cell">';
			if ( '' !== $logo_url ) {
				$row .= '<img src="' . esc_url( $logo_url ) . '" alt="">';
			}
			if ( '' !== $name ) {
				$row .= '<p class="credits-collaborator-name">' . esc_html( $name ) . '</p>';
			}
			if ( '' !== $website ) {
				$row .= '<p class="credits-collaborator-website">' . esc_html( preg_replace( '#^https?://#i', '', $website ) ) . '</p>';
			}
			if ( '' !== $text ) {
				$row .= '<p class="credits-collaborator-text">' . esc_html( $text ) . '</p>';
			}
			$collaborator_rows[] = $row . '</div>';
		}
		if ( $collaborator_rows ) {
			$title = trim( (string) $config['collaborators_title'] );
			$title_html = '' !== $title ? '<div class="credits-section-title">' . esc_html( $title ) . '</div>' : '';
			$section_html['collaborators'] = '<div class="credits-collaborators-section">' . $title_html . implode( '', $collaborator_rows ) . '</div>';
		}
	}

	$logo_rows = array();
	foreach ( $config['logos'] as $logo ) {
		$logo_url = trim( (string) ( $logo['logo_url'] ?? '' ) );
		$show_author = ! empty( $logo['show_author_name'] ) && '' !== trim( (string) $book_author_label );
		if ( '' === $logo_url && ! $show_author ) {
			continue;
		}

		$position = in_array( $logo['position'] ?? '', array( 'left', 'center', 'right' ), true ) ? $logo['position'] : 'center';
		$size = max( 24, min( 400, absint( $logo['size_px'] ?? 120 ) ) );
		$row = '<div class="credits-logo-row" style="text-align:' . esc_attr( $position ) . '">';
		if ( '' !== $logo_url ) {
			$row .= '<img src="' . esc_url( $logo_url ) . '" alt="" style="width:' . $size . 'px;max-width:100%;height:auto">';
		}
		if ( $show_author ) {
			$row .= '<div class="credits-logo-author">' . esc_html( $book_author_label ) . '</div>';
		}
		$logo_rows[] = $row . '</div>';
	}
	if ( $logo_rows ) {
		$section_html['logos'] = '<div class="credits-logos-section">' . implode( '', $logo_rows ) . '</div>';
	}

	$legal_text = trim( (string) ( $config['legal']['copyright_text'] ?? '' ) );
	$license = trim( (string) ( $config['legal']['license'] ?? '' ) );
	if ( '' !== $legal_text || '' !== $license ) {
		$legal_html = '<div class="credits-legal-section">';
		if ( '' !== $legal_text ) {
			$legal_html .= '<div class="credits-copyright"><p>' . nl2br( esc_html( $legal_text ) ) . '</p></div>';
		}
		if ( '' !== $license ) {
			$legal_html .= '<div class="credits-license"><p>' . esc_html( $license_labels[ $license ] ?? $license ) . '</p></div>';
		}
		$section_html['legal'] = $legal_html . '</div>';
	}

	$html = '<div class="content-box credits-page-content"><div class="credits-sections-flow">';
	$rendered = 0;
	foreach ( $config['section_order'] as $section_id ) {
		if ( empty( $section_html[ $section_id ] ) ) {
			continue;
		}

		$style = $config['section_styles'][ $section_id ] ?? array();
		$inline = array();
		if ( ! empty( $style['font_family'] ) ) {
			$inline[] = 'font-family:' . esc_attr( $style['font_family'] );
		}
		if ( '' !== (string) ( $style['font_size'] ?? '' ) ) {
			$inline[] = 'font-size:' . (float) $style['font_size'] . 'px';
		}
		if ( '' !== (string) ( $style['letter_spacing'] ?? '' ) ) {
			$inline[] = 'letter-spacing:' . (float) $style['letter_spacing'] . 'px';
		}
		if ( '' !== (string) ( $style['line_height'] ?? '' ) ) {
			$inline[] = 'line-height:' . (float) $style['line_height'];
		}
		if ( in_array( $style['text_align'] ?? '', array( 'left', 'center', 'right' ), true ) ) {
			$inline[] = 'text-align:' . $style['text_align'];
		}
		if ( $rendered > 0 && ! empty( $style['show_separator'] ) ) {
			$html .= '<div class="credits-section-separator"></div>';
		}
		$html .= '<div class="credits-section-block credits-section-' . esc_attr( $section_id ) . '" style="' . esc_attr( implode( ';', $inline ) ) . '">' . $section_html[ $section_id ] . '</div>';
		++$rendered;
	}
	$html .= '</div></div>';

	if ( 0 === $rendered ) {
		return '';
	}

	$plain_source = preg_replace( '#</(?:strong|span|em)>#i', ' ', $html );
	$plain_source = preg_replace( '#<(?:br\s*/?|/p|/div)>#i', "\n", $plain_source );
	$plain_text = html_entity_decode(
		wp_strip_all_tags( $plain_source ),
		ENT_QUOTES | ENT_HTML5,
		'UTF-8'
	);
	$plain_text = preg_replace( '/[ \t]{2,}/', ' ', $plain_text );
	$plain_text = preg_replace( "/[ \t]+\n/", "\n", $plain_text );
	$plain_text = preg_replace( "/\n{3,}/", "\n\n", $plain_text );

	return trim( $plain_text );
}
