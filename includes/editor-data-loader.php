<?php
$book_id = isset( $_GET['book_id'] ) ? intval( $_GET['book_id'] ) : 0;
$book = get_post( $book_id );

if ( ! $book || $book->post_type !== 'almaden-books' ) {
	wp_die( 'Libro no encontrado.' );
}

$book_title = $book->post_title;
$chapter_posts = get_posts( array(
	'post_type'      => 'book_chapter',
	'post_parent'    => $book_id,
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
			'page_one_vertical'        => get_post_meta( $cp->ID, '_page_one_vertical', true ),
			'start_parity'             => get_post_meta( $cp->ID, '_start_parity', true ),
			'first_page_header_type'   => get_post_meta( $cp->ID, '_first_page_header_type', true ),
			'first_page_header_custom' => get_post_meta( $cp->ID, '_first_page_header_custom', true ),
			'first_page_footer_type'   => get_post_meta( $cp->ID, '_first_page_footer_type', true ),
			'first_page_footer_custom' => get_post_meta( $cp->ID, '_first_page_footer_custom', true ),
			'parity_image_mode'        => get_post_meta( $cp->ID, '_parity_image_mode', true ),
			'parity_image_width'       => get_post_meta( $cp->ID, '_parity_image_width', true ),
			'parity_image_height'      => get_post_meta( $cp->ID, '_parity_image_height', true ),
			'is_toc'                   => get_post_meta( $cp->ID, '_is_toc', true ),
			'toc_font_family'          => get_post_meta( $cp->ID, '_toc_font_family', true ),
			'toc_font_size'            => get_post_meta( $cp->ID, '_toc_font_size', true ),
			'toc_enumerate'            => get_post_meta( $cp->ID, '_toc_enumerate', true ),
			'toc_font_style'           => get_post_meta( $cp->ID, '_toc_font_style', true ),
			'toc_font_weight'          => get_post_meta( $cp->ID, '_toc_font_weight', true ),
			'toc_text_transform'       => get_post_meta( $cp->ID, '_toc_text_transform', true ),
			'toc_letter_spacing'       => get_post_meta( $cp->ID, '_toc_letter_spacing', true ),
			'toc_line_height'          => get_post_meta( $cp->ID, '_toc_line_height', true ),
			'toc_leader_style'         => get_post_meta( $cp->ID, '_toc_leader_style', true ),
			'toc_leader_position'      => get_post_meta( $cp->ID, '_toc_leader_position', true ),
			'toc_title_align'          => get_post_meta( $cp->ID, '_toc_title_align', true ),
			'toc_page_one_vertical'    => get_post_meta( $cp->ID, '_toc_page_one_vertical', true ),
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

// Cargar ajustes del libro desde la tabla especial
global $wpdb;
$settings_table = $wpdb->prefix . 'almaden_book_settings';
$db_settings = array();
if ( $wpdb->get_var( "SHOW TABLES LIKE '$settings_table'" ) === $settings_table ) {
	$db_settings = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $settings_table WHERE book_id = %d", $book_id ), ARRAY_A );
}

$pdf_settings = array(
	'unit'                       => 'cm',
	'page_size'                  => 'A4',
	'page_width'                 => 21.0,
	'page_height'                => 29.7,
	'margin_top'                 => 2.5,
	'margin_bottom'              => 2.5,
	'margin_left'                => 2.0,
	'margin_right'               => 2.0,
	'margin_left_odd'            => 2.0,
	'margin_right_odd'           => 2.0,
	'margin_left_even'           => 2.0,
	'margin_right_even'          => 2.0,
	'padding_top'                => 0.0,
	'padding_bottom'             => 0.0,
	'padding_left'               => 0.0,
	'padding_right'              => 0.0,
	'bleeding'                   => 0.0,
	'export_grayscale'           => 0,
	'font_family_content'        => 'Merriweather',
	'font_size_content'          => 11.5,
	'line_height_content'        => 1.65,
	'content_text_align'         => 'justify',
	'content_hyphenation'        => 1,
	'content_language'           => 'es',
	'content_paragraph_indent'   => 0.0,
	'content_paragraph_spacing'  => 14.0,
	'font_family_headings'       => 'Playfair Display',
	'font_family_h1'             => 'Playfair Display',
	'font_family_h2'             => 'Playfair Display',
	'font_family_h3'             => 'Playfair Display',
	'font_weight_h1'             => 'bold',
	'font_weight_h2'             => 'bold',
	'font_weight_h3'             => 'bold',
	'font_size_h1'               => 24.0,
	'font_size_h2'               => 16.0,
	'font_size_h3'               => 13.0,
	'header_font_family'         => 'Merriweather',
	'header_font_size'           => 8.5,
	'header_font_weight'         => 'normal',
	'header_font_style'          => 'normal',
	'header_letter_spacing'      => 0.1,
	'header_even_type'           => 'book_title',
	'header_even_custom'         => '',
	'header_odd_type'            => 'chapter_title',
	'header_odd_custom'          => '',
	'footer_font_family'         => 'Merriweather',
	'footer_font_size'           => 9.0,
	'footer_font_weight'         => 'normal',
	'footer_font_style'          => 'normal',
	'footer_letter_spacing'      => 0.0,
	'footer_even_type'           => 'page_number',
	'footer_odd_type'            => 'page_number',
	'first_page_header_type'     => 'blank',
	'first_page_header_custom'   => '',
	'first_page_footer_type'     => 'page_number',
	'first_page_footer_custom'   => '',
	'chapter_start_parity'       => 'any',
	'parity_image_mode'          => 'content',
	'chapter_page_one_align'     => 'center',
	'chapter_page_one_vertical'  => 'top',
	'chapter_title_font_family'  => 'Playfair Display',
	'chapter_title_font_size'    => 24.0,
	'chapter_title_font_weight'  => 'bold',
	'chapter_title_font_style'   => 'normal',
	'chapter_title_align'        => 'center',
	'chapter_title_padding_top'  => 0.0,
	'chapter_title_padding_bottom'=> 1.5,
	'chapter_title_padding_left' => 0.0,
	'chapter_title_padding_right'=> 0.0,
	'chapter_title_line_height'  => 1.2,
	'chapter_title_text_transform' => 'none',
	'chapter_prefix_show'        => 0,
	'chapter_prefix_template'    => 'Capítulo {N}',
	'chapter_prefix_position'    => 'above',
	'chapter_prefix_font_family' => 'Playfair Display',
	'chapter_prefix_font_size'   => 16.0,
	'chapter_prefix_font_weight' => 'normal',
	'chapter_prefix_font_style'  => 'normal',
	'chapter_prefix_letter_spacing' => 0.0,
	'chapter_prefix_ornament'    => 'none',
	'header_margin_top'          => 1.0,
	'header_margin_bottom'       => 0.5,
	'header_align'               => 'center',
	'footer_margin_top'          => 0.5,
	'footer_margin_bottom'       => 1.0,
	'footer_align'               => 'center'
);

if ( $db_settings ) {
	foreach ( $pdf_settings as $key => $default ) {
		if ( isset( $db_settings[$key] ) ) {
			if ( is_float( $default ) ) {
				$pdf_settings[$key] = floatval( $db_settings[$key] );
			} elseif ( is_int( $default ) ) {
				$pdf_settings[$key] = intval( $db_settings[$key] );
			} else {
				$pdf_settings[$key] = $db_settings[$key];
			}
		}
	}
}

// Fallback de retrocompatibilidad: si los márgenes par/impar no existen (BD antigua), usar los globales
if ( ! isset( $pdf_settings['margin_left_odd'] ) ) {
	$pdf_settings['margin_left_odd'] = $pdf_settings['margin_left'];
	$pdf_settings['margin_right_odd'] = $pdf_settings['margin_right'];
	$pdf_settings['margin_left_even'] = $pdf_settings['margin_left'];
	$pdf_settings['margin_right_even'] = $pdf_settings['margin_right'];
}

// Cargar fuentes instaladas desde la tabla de Google Fonts
$installed_fonts = almaden_bookster_get_installed_fonts_list();

// Construir URL dinámica de Google Fonts CDN con las fuentes instaladas y TODOS sus pesos
$font_families_for_cdn = array();
// Default built-ins (Inter, Merriweather, Playfair Display)
$font_families_for_cdn[] = 'Inter:wght@100;200;300;400;500;600;700;800;900';
$font_families_for_cdn[] = 'Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900';
$font_families_for_cdn[] = 'Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900';

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
