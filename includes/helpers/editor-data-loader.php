<?php
$book_id = isset( $_GET['book_id'] ) ? intval( $_GET['book_id'] ) : 0;
$book = get_post( $book_id );

if ( ! $book || $book->post_type !== 'almaden-books' ) {
	wp_die( 'Libro no encontrado.' );
}

$book_title = $book->post_title;
$book_author_label = function_exists( 'almaden_bookster_get_book_author_display_label' ) ? almaden_bookster_get_book_author_display_label( $book_id, '' ) : '';
$book_authors_input_value = get_post_meta( $book_id, 'book_author', true );
if ( '' === trim( (string) $book_authors_input_value ) ) {
	$book_authors_input_value = get_post_meta( $book_id, '_almaden_book_author', true );
}
if ( '' === trim( (string) $book_authors_input_value ) && function_exists( 'almaden_bookster_get_book_author_edit_tokens' ) ) {
	$book_authors_input_value = almaden_bookster_get_book_author_edit_tokens( $book_id );
}

$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
if ( empty( $source_book_id ) ) {
	$source_book_id = $book_id;
}

$chapter_posts = get_posts( array(
	'post_type'      => 'book_chapter',
	'post_parent'    => $source_book_id,
	'posts_per_page' => -1,
	'orderby'        => 'menu_order',
	'order'          => 'ASC',
) );

$saved_chapters = array();
if ( $chapter_posts ) {
	foreach ( $chapter_posts as $cp ) {
		$saved_chapters[] = array(
			'id'                       => strval( $cp->ID ),
			'title'                    => $cp->post_title,
			'content'                  => $cp->post_content,
			'parity_image'             => get_post_meta( $cp->ID, '_parity_image', true ),
			'opening_page_mode'       => get_post_meta( $cp->ID, '_opening_page_mode', true ),
			'opening_blank_intentional' => get_post_meta( $cp->ID, '_opening_blank_intentional', true ),
			'opening_block_enabled'   => get_post_meta( $cp->ID, '_opening_block_enabled', true ),
			'hide_title'               => get_post_meta( $cp->ID, '_hide_title', true ),
			'hide_all_headers_footers' => get_post_meta( $cp->ID, '_hide_all_headers_footers', true ),
			'exclude_from_numbering'   => get_post_meta( $cp->ID, '_exclude_from_numbering', true ),
			'custom_running_header'    => get_post_meta( $cp->ID, '_custom_running_header', true ),
			
			'subtitle_text'            => get_post_meta( $cp->ID, '_subtitle_text', true ),
			'subtitle_font_family'     => get_post_meta( $cp->ID, '_subtitle_font_family', true ),
			'subtitle_align'           => get_post_meta( $cp->ID, '_subtitle_align', true ),
			'subtitle_font_size'       => get_post_meta( $cp->ID, '_subtitle_font_size', true ),
			'subtitle_letter_spacing'  => get_post_meta( $cp->ID, '_subtitle_letter_spacing', true ),
			'subtitle_font_style'      => get_post_meta( $cp->ID, '_subtitle_font_style', true ),
			'subtitle_text_transform'  => get_post_meta( $cp->ID, '_subtitle_text_transform', true ),
			'subtitle_font_weight'     => get_post_meta( $cp->ID, '_subtitle_font_weight', true ),
			'subtitle_margin_top'      => get_post_meta( $cp->ID, '_subtitle_margin_top', true ),
			'subtitle_margin_bottom'   => get_post_meta( $cp->ID, '_subtitle_margin_bottom', true ),
			
			'drop_cap_enabled'         => get_post_meta( $cp->ID, '_drop_cap_enabled', true ),
			'disable_hyphenation'      => get_post_meta( $cp->ID, '_disable_hyphenation', true ),
			'start_parity'             => get_post_meta( $cp->ID, '_start_parity', true ),
			'first_page_header_type'   => get_post_meta( $cp->ID, '_first_page_header_type', true ),
			'first_page_header_custom' => get_post_meta( $cp->ID, '_first_page_header_custom', true ),
			'first_page_footer_type'   => get_post_meta( $cp->ID, '_first_page_footer_type', true ),
			'first_page_footer_custom' => get_post_meta( $cp->ID, '_first_page_footer_custom', true ),
			'parity_image_mode'        => get_post_meta( $cp->ID, '_parity_image_mode', true ),
			'parity_image_width'       => get_post_meta( $cp->ID, '_parity_image_width', true ),
			'parity_image_height'      => get_post_meta( $cp->ID, '_parity_image_height', true ),
			'is_toc'                   => get_post_meta( $cp->ID, '_is_toc', true ),
			'is_credits'               => get_post_meta( $cp->ID, '_is_credits', true ),
			'credits_font_family'      => get_post_meta( $cp->ID, '_credits_font_family', true ),
			'credits_align'            => get_post_meta( $cp->ID, '_credits_align', true ),
			'credits_font_size'        => get_post_meta( $cp->ID, '_credits_font_size', true ),
			'credits_letter_spacing'   => get_post_meta( $cp->ID, '_credits_letter_spacing', true ),
			'credits_font_weight'      => get_post_meta( $cp->ID, '_credits_font_weight', true ),
			'credits_hide_page_number' => get_post_meta( $cp->ID, '_credits_hide_page_number', true ),
			'credits_margin_top'       => get_post_meta( $cp->ID, '_credits_margin_top', true ),
			'credits_margin_bottom'    => get_post_meta( $cp->ID, '_credits_margin_bottom', true ),
			'toc_font_family'          => get_post_meta( $cp->ID, '_toc_font_family', true ),
			'toc_font_size'            => get_post_meta( $cp->ID, '_toc_font_size', true ),
			'toc_enumerate'            => get_post_meta( $cp->ID, '_toc_enumerate', true ),
			'toc_font_style'           => get_post_meta( $cp->ID, '_toc_font_style', true ),
			'toc_font_weight'          => get_post_meta( $cp->ID, '_toc_font_weight', true ),
			'toc_text_transform'       => get_post_meta( $cp->ID, '_toc_text_transform', true ),
			'toc_letter_spacing'       => get_post_meta( $cp->ID, '_toc_letter_spacing', true ),
			'toc_line_height'          => get_post_meta( $cp->ID, '_toc_line_height', true ),
			'toc_item_spacing'         => get_post_meta( $cp->ID, '_toc_item_spacing', true ),
			'toc_hide_header'         => get_post_meta( $cp->ID, '_toc_hide_header', true ),
			'toc_hide_page_numbers'   => get_post_meta( $cp->ID, '_toc_hide_page_numbers', true ),
			'toc_item_align'          => get_post_meta( $cp->ID, '_toc_item_align', true ),
			'toc_leader_style'         => get_post_meta( $cp->ID, '_toc_leader_style', true ),
			'toc_leader_position'      => get_post_meta( $cp->ID, '_toc_leader_position', true ),
			'toc_title_align'          => get_post_meta( $cp->ID, '_toc_title_align', true ),
			'toc_title_font_family'    => get_post_meta( $cp->ID, '_toc_title_font_family', true ),
			'toc_title_font_size'      => get_post_meta( $cp->ID, '_toc_title_font_size', true ),
			'toc_title_font_style'     => get_post_meta( $cp->ID, '_toc_title_font_style', true ),
			'toc_title_text_transform' => get_post_meta( $cp->ID, '_toc_title_text_transform', true ),
			'toc_title_font_weight'    => get_post_meta( $cp->ID, '_toc_title_font_weight', true ),
			'toc_title_padding_top'    => get_post_meta( $cp->ID, '_toc_title_padding_top', true ),
			'toc_title_padding_bottom' => get_post_meta( $cp->ID, '_toc_title_padding_bottom', true ),
			'toc_title_line_height'    => get_post_meta( $cp->ID, '_toc_title_line_height', true ),
		);
	}
}
if ( ! is_array( $saved_chapters ) || empty( $saved_chapters ) ) {
	$saved_chapters = array(
		array(
			'id' => 'cap-1',
			'title' => 'Capítulo I: El Primer Suspiro',
			'content' => "# Capítulo I\n## El Primer Suspiro\n\nEl viento soplaba furioso contra las ventanas de la antigua cabaña. Aquella noche de invierno no parecía diferente a las anteriores, pero el destino ya había trazado su línea de no retorno. Daniel, sentado frente a su rústica mesa de madera, sostenía una pluma gastada.\n\n*\"Las palabras tienen el poder de dar vida, pero también de arrebatarla\"*, murmuró para sus adentros.\n\nFrente a él yacía un manuscrito antiguo encuadernado en cuero desgastado. Nadie debía saber lo que contenía, pero las sombras acechaban más de lo usual en los rincones de la habitación. De repente, un golpe seco resonó en la puerta principal. Tres toques rítmicos, seguidos de un profundo silencio.\n\n> Aquel que busca respuestas en las sombras debe estar preparado para ver lo que las sombras revelan.\n\n- Daniel apagó la vela rápidamente.\n- El silencio de la casa se volvió ensordecedor.\n- Con sigilo, deslizó la mano por debajo de la mesa buscando la vieja llave de latón."
		)
	);
}

// Cargar ajustes del libro
$pdf_settings = almaden_get_book_pdf_settings( $book_id );

// Cargar fuentes instaladas desde la tabla de Google Fonts
$installed_fonts = almaden_bookster_get_installed_fonts_list();

// Construir URL dinámica de Google Fonts CDN con las fuentes instaladas y TODOS sus pesos
$font_families_for_cdn = array();
// Default built-ins (Inter, Merriweather, Playfair Display, Lora, Cinzel, Cormorant Garamond, Outfit)
$font_families_for_cdn[] = 'Inter:wght@100;200;300;400;500;600;700;800;900';
$font_families_for_cdn[] = 'Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900';
$font_families_for_cdn[] = 'Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900';
$font_families_for_cdn[] = 'Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700';
$font_families_for_cdn[] = 'Cinzel:wght@400;500;600;700;800;900';
$font_families_for_cdn[] = 'Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700';
$font_families_for_cdn[] = 'Outfit:wght@100;200;300;400;500;600;700;800;900';

foreach ( $installed_fonts as $ifont ) {
	$family_slug = str_replace( ' ', '+', $ifont['family'] );
	
	$variants_str = isset($ifont['variants']) ? $ifont['variants'] : '';
	if ( empty($variants_str) ) {
		// Fallback
		$font_families_for_cdn[] = $family_slug . ':ital,wght@0,400;0,700;1,400';
		continue;
	}

	$variants_arr = explode(',', $variants_str);
	$tuples = array();
	foreach ( $variants_arr as $v ) {
		$v = trim($v);
		if ( empty($v) ) continue;
		
		$ital = 0;
		$wght = 400;
		
		if ( strpos($v, 'italic') !== false ) {
			$ital = 1;
			$w_str = str_replace('italic', '', $v);
			if ( $w_str === '' || $w_str === 'regular' ) {
				$wght = 400;
			} else {
				$wght = intval($w_str);
			}
		} else {
			if ( $v === 'regular' ) {
				$wght = 400;
			} else {
				$wght = intval($v);
			}
		}
		
		if ($wght >= 100 && $wght <= 900) {
			$tuples[] = $ital . ',' . $wght;
		}
	}
	
	if ( empty($tuples) ) {
		$font_families_for_cdn[] = $family_slug . ':ital,wght@0,400;0,700;1,400';
	} else {
		// API v2 requires them to be sorted
		sort($tuples);
		$font_families_for_cdn[] = $family_slug . ':ital,wght@' . implode(';', $tuples);
	}
}
$google_fonts_url = 'https://fonts.googleapis.com/css2?' . implode( '&', array_map( function( $f ) { return 'family=' . $f; }, $font_families_for_cdn ) ) . '&display=swap';
