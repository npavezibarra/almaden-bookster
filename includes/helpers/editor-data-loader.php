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

$commerce_relation = function_exists( 'almaden_bookster_get_book_wc_relation' ) ? almaden_bookster_get_book_wc_relation( $book_id ) : array(
	'book_id' => $book_id,
	'product_id' => (int) get_post_meta( $book_id, '_almaden_wc_product_id', true ),
	'parent_product_id' => (int) get_post_meta( $book_id, '_almaden_wc_parent_product_id', true ),
	'product_mode' => get_post_meta( $book_id, '_almaden_wc_product_mode', true ) ?: 'none',
);
$woocommerce_status = function_exists( 'almaden_bookster_get_woocommerce_status' ) ? almaden_bookster_get_woocommerce_status() : array(
	'active' => false,
	'installed' => false,
);

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
		$is_toc = get_post_meta( $cp->ID, '_is_toc', true );
		$toc_hide_header = get_post_meta( $cp->ID, '_toc_hide_header', true );
		$toc_hide_page_numbers = get_post_meta( $cp->ID, '_toc_hide_page_numbers', true );
		$toc_hide_title = get_post_meta( $cp->ID, '_toc_hide_title', true );
		$toc_title_text = get_post_meta( $cp->ID, '_toc_title_text', true );
		$hide_all_headers_footers = get_post_meta( $cp->ID, '_hide_all_headers_footers', true );
		$hide_header = get_post_meta( $cp->ID, '_hide_header', true );
		$hide_footer = get_post_meta( $cp->ID, '_hide_footer', true );
		if ( '1' === (string) $is_toc ) {
			$toc_hide_header = '' === (string) $toc_hide_header ? '1' : $toc_hide_header;
			$toc_hide_page_numbers = '' === (string) $toc_hide_page_numbers ? '1' : $toc_hide_page_numbers;
			$toc_hide_title = '' === (string) $toc_hide_title ? '0' : $toc_hide_title;
			if ( '' === trim( (string) $toc_title_text ) ) {
				$toc_title_text = $cp->post_title ?: 'Índice';
			}
		}

		if ( '' === (string) $hide_all_headers_footers ) {
			$hide_all_headers_footers = ( '1' === (string) $hide_header && '1' === (string) $hide_footer ) ? '1' : '0';
		}
		if ( '' === (string) $hide_header && '1' === (string) $hide_all_headers_footers ) {
			$hide_header = '1';
		}
		if ( '' === (string) $hide_footer && '1' === (string) $hide_all_headers_footers ) {
			$hide_footer = '1';
		}

		$saved_chapters[] = array(
			'id'                       => strval( $cp->ID ),
			'title'                    => $cp->post_title,
			'content'                  => $cp->post_content,
			'parity_image'             => get_post_meta( $cp->ID, '_parity_image', true ),
			'opening_page_mode'       => get_post_meta( $cp->ID, '_opening_page_mode', true ),
			'opening_blank_intentional' => get_post_meta( $cp->ID, '_opening_blank_intentional', true ),
			'opening_block_enabled'   => get_post_meta( $cp->ID, '_opening_block_enabled', true ),
			'opening_block_horizontal_align' => get_post_meta( $cp->ID, '_opening_block_horizontal_align', true ),
			'opening_block_vertical_align' => get_post_meta( $cp->ID, '_opening_block_vertical_align', true ),
			'hide_opening'            => get_post_meta( $cp->ID, '_hide_opening', true ),
			'hide_title'               => get_post_meta( $cp->ID, '_hide_title', true ),
			'hide_header'              => $hide_header,
			'hide_footer'              => $hide_footer,
			'hide_all_headers_footers' => $hide_all_headers_footers,
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
			'chapter_prefix_align'     => get_post_meta( $cp->ID, '_chapter_prefix_align', true ),
			'opening_separate_content' => get_post_meta( $cp->ID, '_opening_separate_content', true ),
			'chapter_blank_before'     => get_post_meta( $cp->ID, '_chapter_blank_before', true ),
			'chapter_blank_after'      => get_post_meta( $cp->ID, '_chapter_blank_after', true ),
			'chapter_image_enabled'    => get_post_meta( $cp->ID, '_chapter_image_enabled', true ),
			'chapter_image_mode'       => get_post_meta( $cp->ID, '_chapter_image_mode', true ),
			'chapter_image_url'        => get_post_meta( $cp->ID, '_chapter_image_url', true ),
			'chapter_image_inner_width' => get_post_meta( $cp->ID, '_chapter_image_inner_width', true ),
			'chapter_image_inner_header' => get_post_meta( $cp->ID, '_chapter_image_inner_header', true ),
			'chapter_image_inner_footer' => get_post_meta( $cp->ID, '_chapter_image_inner_footer', true ),
			'parity_image_mode'        => get_post_meta( $cp->ID, '_parity_image_mode', true ),
			'parity_image_width'       => get_post_meta( $cp->ID, '_parity_image_width', true ),
			'parity_image_height'      => get_post_meta( $cp->ID, '_parity_image_height', true ),
			'is_toc'                   => $is_toc,
			'is_credits'               => get_post_meta( $cp->ID, '_is_credits', true ),
			'credits_font_family'      => get_post_meta( $cp->ID, '_credits_font_family', true ),
			'credits_align'            => get_post_meta( $cp->ID, '_credits_align', true ),
			'credits_vertical_align'   => get_post_meta( $cp->ID, '_credits_vertical_align', true ),
			'credits_font_size'        => get_post_meta( $cp->ID, '_credits_font_size', true ),
			'credits_letter_spacing'   => get_post_meta( $cp->ID, '_credits_letter_spacing', true ),
			'credits_font_weight'      => get_post_meta( $cp->ID, '_credits_font_weight', true ),
			'credits_hide_header'      => get_post_meta( $cp->ID, '_credits_hide_header', true ),
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
			'toc_hide_header'         => $toc_hide_header,
			'toc_hide_page_numbers'   => $toc_hide_page_numbers,
			'toc_hide_title'          => $toc_hide_title,
			'toc_separate_opening_content' => get_post_meta( $cp->ID, '_toc_separate_opening_content', true ),
			'toc_item_align'          => get_post_meta( $cp->ID, '_toc_item_align', true ),
			'toc_leader_style'         => get_post_meta( $cp->ID, '_toc_leader_style', true ),
			'toc_leader_position'      => get_post_meta( $cp->ID, '_toc_leader_position', true ),
			'toc_title_text'           => $toc_title_text,
			'toc_title_align'          => get_post_meta( $cp->ID, '_toc_title_align', true ),
			'toc_title_font_family'    => get_post_meta( $cp->ID, '_toc_title_font_family', true ),
			'toc_title_font_size'      => get_post_meta( $cp->ID, '_toc_title_font_size', true ),
			'toc_title_font_style'     => get_post_meta( $cp->ID, '_toc_title_font_style', true ),
			'toc_title_text_transform' => get_post_meta( $cp->ID, '_toc_title_text_transform', true ),
			'toc_title_font_weight'    => get_post_meta( $cp->ID, '_toc_title_font_weight', true ),
			'toc_title_letter_spacing' => get_post_meta( $cp->ID, '_toc_title_letter_spacing', true ),
			'toc_title_padding_top'    => get_post_meta( $cp->ID, '_toc_title_padding_top', true ),
			'toc_title_padding_bottom' => get_post_meta( $cp->ID, '_toc_title_padding_bottom', true ),
			'toc_title_line_height'    => get_post_meta( $cp->ID, '_toc_title_line_height', true ),
		);
	}
}

if ( empty( $saved_chapters ) ) {
	$legacy_content = isset( $book->post_content ) ? (string) $book->post_content : '';
	$saved_chapters[] = array(
		'id'                         => 'legacy-book-content',
		'title'                      => $book_title ?: 'Capítulo 1',
		'content'                    => $legacy_content,
		'parity_image'               => '',
		'opening_page_mode'          => '',
		'opening_blank_intentional'  => '',
		'opening_block_enabled'      => '',
		'opening_block_horizontal_align' => '',
		'opening_block_vertical_align' => '',
		'hide_opening'               => '0',
		'hide_title'                 => '0',
		'hide_header'                => '0',
		'hide_footer'                => '0',
		'hide_all_headers_footers'   => '0',
		'exclude_from_numbering'     => '0',
		'custom_running_header'      => '',
		'subtitle_text'              => '',
		'subtitle_font_family'       => '',
		'subtitle_align'             => '',
		'subtitle_font_size'         => '',
		'subtitle_letter_spacing'    => '',
		'subtitle_font_style'        => '',
		'subtitle_text_transform'    => '',
		'subtitle_font_weight'       => '',
		'subtitle_margin_top'        => '',
		'subtitle_margin_bottom'     => '',
		'drop_cap_enabled'           => '',
		'disable_hyphenation'        => '',
		'start_parity'               => 'any',
		'first_page_header_type'     => '',
		'first_page_header_custom'   => '',
		'first_page_footer_type'     => '',
		'first_page_footer_custom'   => '',
		'chapter_prefix_align'       => 'center',
		'opening_separate_content'   => '',
		'chapter_blank_before'       => '0',
		'chapter_blank_after'        => '0',
		'chapter_image_enabled'      => '',
		'chapter_image_mode'         => '',
		'chapter_image_url'          => '',
		'chapter_image_inner_width'   => '',
		'chapter_image_inner_header'  => '',
		'chapter_image_inner_footer'  => '',
		'parity_image_mode'          => '',
		'parity_image_width'         => '',
		'parity_image_height'        => '',
		'is_toc'                     => '0',
		'is_credits'                 => '0',
		'credits_font_family'        => '',
		'credits_align'              => '',
		'credits_vertical_align'     => '',
		'credits_font_size'          => '',
		'credits_letter_spacing'     => '',
		'credits_font_weight'        => '',
		'credits_hide_header'        => '0',
		'credits_hide_page_number'   => '0',
		'credits_margin_top'         => '',
		'credits_margin_bottom'      => '',
		'toc_font_family'            => '',
		'toc_font_size'              => '',
		'toc_enumerate'              => '',
		'toc_font_style'             => '',
		'toc_font_weight'            => '',
		'toc_text_transform'         => '',
		'toc_letter_spacing'         => '',
		'toc_line_height'            => '',
		'toc_item_spacing'           => '',
		'toc_hide_header'            => '0',
		'toc_hide_page_numbers'      => '0',
		'toc_hide_title'             => '0',
		'toc_separate_opening_content' => '',
		'toc_item_align'             => '',
		'toc_leader_style'           => '',
		'toc_leader_position'        => '',
		'toc_title_text'             => '',
		'toc_title_align'            => '',
		'toc_title_font_family'      => '',
		'toc_title_font_size'        => '',
		'toc_title_font_style'       => '',
		'toc_title_text_transform'   => '',
		'toc_title_font_weight'      => '',
		'toc_title_letter_spacing'   => '',
		'toc_title_padding_top'      => '',
		'toc_title_padding_bottom'   => '',
		'toc_title_line_height'      => '',
	);
}

// Cargar ajustes del libro
$pdf_settings = almaden_get_book_pdf_settings( $book_id );
if ( function_exists( 'almaden_bookster_get_book_language_from_settings' ) ) {
	$pdf_settings['book_language'] = almaden_bookster_get_book_language_from_settings( $pdf_settings, 'es' );
}

if ( ! function_exists( 'almaden_bookster_normalize_page_one_alignment_meta' ) ) {
	function almaden_bookster_normalize_page_one_alignment_meta( $combined_value, $legacy_vertical = 'top', $legacy_horizontal = 'center' ) {
		$combined_value = strtolower( trim( str_replace( array( '/', ' ' ), '-', (string) $combined_value ) ) );
		$parts = array_values( array_filter( explode( '-', $combined_value ) ) );

		if ( count( $parts ) >= 2 ) {
			$horizontal = in_array( $parts[0], array( 'left', 'center', 'right' ), true ) ? $parts[0] : '';
			$vertical = in_array( $parts[1], array( 'top', 'center', 'bottom' ), true ) ? $parts[1] : '';
			if ( $horizontal && $vertical ) {
				return array(
					'horizontal' => $horizontal,
					'vertical'   => $vertical,
					'combined'   => $horizontal . '-' . $vertical,
				);
			}
		}

		$horizontal = in_array( strtolower( (string) $legacy_horizontal ), array( 'left', 'center', 'right' ), true ) ? strtolower( (string) $legacy_horizontal ) : 'center';
		$vertical = in_array( strtolower( (string) $legacy_vertical ), array( 'top', 'center', 'bottom' ), true ) ? strtolower( (string) $legacy_vertical ) : 'top';
		if ( 'half' === strtolower( (string) $legacy_vertical ) ) {
			$vertical = 'center';
		}

		return array(
			'horizontal' => $horizontal,
			'vertical'   => $vertical,
			'combined'   => $horizontal . '-' . $vertical,
		);
	}
}

$page_one_alignment = almaden_bookster_normalize_page_one_alignment_meta(
	isset( $pdf_settings['chapter_page_one_align'] ) ? $pdf_settings['chapter_page_one_align'] : '',
	isset( $pdf_settings['chapter_page_one_vertical'] ) ? $pdf_settings['chapter_page_one_vertical'] : 'top',
	isset( $pdf_settings['chapter_title_align'] ) ? $pdf_settings['chapter_title_align'] : 'center'
);
$pdf_settings['chapter_page_one_align'] = $page_one_alignment['combined'];
$pdf_settings['chapter_page_one_vertical'] = $page_one_alignment['vertical'];

// Credits are structured book data, not editable chapter prose. Give every
// render path a generated chapter body so a generic/legacy compiler cannot
// fall back to the historical placeholder stored in post_content.
if (
	! empty( $saved_chapters )
	&& ! empty( $pdf_settings['credits_config'] )
	&& function_exists( 'almaden_bookster_build_credits_chapter_content' )
) {
	$generated_credits_content = almaden_bookster_build_credits_chapter_content(
		$pdf_settings['credits_config'],
		$book_author_label
	);
	foreach ( $saved_chapters as &$saved_chapter ) {
		if ( '1' !== (string) ( $saved_chapter['is_credits'] ?? '' ) ) {
			continue;
		}
		$saved_chapter['content'] = $generated_credits_content;
		$saved_chapter['credits_config'] = $pdf_settings['credits_config'];
		$saved_chapter['credits_author_label'] = $book_author_label;
	}
	unset( $saved_chapter );
}

// Migrar en memoria la configuración heredada de Chapter Image al nivel del capítulo.
// Si el capítulo todavía no tiene metadatos propios, usamos los valores globales
// para que el modal abra con una base consistente y el siguiente guardado los
// persista ya por capítulo.
if ( ! empty( $saved_chapters ) ) {
	$legacy_chapter_image_mode = isset( $pdf_settings['chapter_image_mode'] ) ? $pdf_settings['chapter_image_mode'] : 'page_blank';
	$legacy_chapter_image_url = isset( $pdf_settings['chapter_image_url'] ) ? $pdf_settings['chapter_image_url'] : '';
	$legacy_chapter_image_inner_width = isset( $pdf_settings['chapter_image_inner_width'] ) ? $pdf_settings['chapter_image_inner_width'] : 100;
	$legacy_chapter_image_inner_header = isset( $pdf_settings['chapter_image_inner_header'] ) ? $pdf_settings['chapter_image_inner_header'] : 0;
	$legacy_chapter_image_inner_footer = isset( $pdf_settings['chapter_image_inner_footer'] ) ? $pdf_settings['chapter_image_inner_footer'] : 0;
	$legacy_chapter_image_enabled = (
		( isset( $pdf_settings['chapter_image_enabled'] ) && '1' === (string) $pdf_settings['chapter_image_enabled'] )
		|| ( 'page_blank' !== (string) $legacy_chapter_image_mode )
		|| ! empty( $legacy_chapter_image_url )
	) ? '1' : '0';

	foreach ( $saved_chapters as &$saved_chapter ) {
		if ( empty( $saved_chapter['chapter_image_enabled'] ) ) {
			$saved_chapter['chapter_image_enabled'] = $legacy_chapter_image_enabled;
		}
		if ( empty( $saved_chapter['chapter_image_mode'] ) ) {
			$saved_chapter['chapter_image_mode'] = $legacy_chapter_image_mode;
		}
		if ( empty( $saved_chapter['chapter_image_url'] ) ) {
			$saved_chapter['chapter_image_url'] = $legacy_chapter_image_url;
		}
		if ( empty( $saved_chapter['chapter_image_inner_width'] ) ) {
			$saved_chapter['chapter_image_inner_width'] = $legacy_chapter_image_inner_width;
		}
		if ( empty( $saved_chapter['chapter_image_inner_header'] ) ) {
			$saved_chapter['chapter_image_inner_header'] = $legacy_chapter_image_inner_header;
		}
		if ( empty( $saved_chapter['chapter_image_inner_footer'] ) ) {
			$saved_chapter['chapter_image_inner_footer'] = $legacy_chapter_image_inner_footer;
		}
	}
	unset( $saved_chapter );
}

// Cargar fuentes disponibles, incluyendo las que vienen incluidas con el plugin
$installed_fonts = function_exists( 'almaden_bookster_get_available_fonts_list' ) ? almaden_bookster_get_available_fonts_list() : almaden_bookster_get_installed_fonts_list();
$google_fonts_url = function_exists( 'almaden_bookster_build_google_fonts_url' ) ? almaden_bookster_build_google_fonts_url( $installed_fonts ) : '';
