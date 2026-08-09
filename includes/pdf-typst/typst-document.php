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
require_once __DIR__ . '/typst-document-render-helpers.php';
require_once __DIR__ . '/typst-document-context.php';
require_once __DIR__ . '/typst-document-prefix.php';
require_once __DIR__ . '/typst-chapter-flow.php';
function almaden_bookster_build_typst_document( $payload ) {
	$context = almaden_bookster_typst_build_document_context( $payload );
	extract( $context, EXTR_SKIP );
	$prefix = almaden_bookster_typst_build_document_prefix( $context, $payload );
	extract( $prefix, EXTR_SKIP );
	$asset_mode = function_exists( 'almaden_bookster_typst_normalize_asset_mode' )
		? almaden_bookster_typst_normalize_asset_mode( $asset_mode ?? 'original' )
		: ( 'original' === (string) ( $asset_mode ?? '' ) ? 'original' : 'optimized' );
	$inline_font_cache  = array();
	$resolve_inline_font = static function ( $family ) use ( &$inline_font_assets, &$inline_font_error, &$inline_font_cache ) {
		$family = almaden_bookster_typst_font_family( $family, '' );
		if ( '' === $family ) {
			return '';
		}
		$key = strtolower( $family );
		if ( isset( $inline_font_cache[ $key ] ) ) {
			return $inline_font_cache[ $key ];
		}
		$resolved = almaden_bookster_typst_resolve_font( $family, 400 );
		if ( is_wp_error( $resolved ) ) {
			$inline_font_error = $resolved;
			return '';
		}
		$inline_font_assets = array_merge( $inline_font_assets, (array) ( $resolved['files'] ?? array() ) );
		$inline_font_cache[ $key ] = $resolved['family'];
		return $inline_font_cache[ $key ];
	};
	$rendered     = 0;
	$numbered_chapter_index = 0;
	$book_reference_groups = array();
	$opening_debug = array();
	foreach ( $chapters as $chapter_index => $chapter ) {
		if ( ! is_array( $chapter ) ) {
			continue;
		}
		$title   = isset( $chapter['title'] ) ? (string) $chapter['title'] : '';
		$content = isset( $chapter['content'] ) ? (string) $chapter['content'] : '';
		foreach ( almaden_bookster_typst_inline_font_families( $content ) as $inline_font_family ) {
			$resolve_inline_font( $inline_font_family );
		}
		$is_toc  = isset( $chapter['is_toc'] ) && '1' === (string) $chapter['is_toc'];
		$is_credits = almaden_bookster_typst_is_credits_chapter( $chapter );
		if ( $is_toc ) {
			$toc_title_text = almaden_bookster_typst_toc_title_text( $chapter );
			if ( '' !== trim( $toc_title_text ) ) {
				$title = $toc_title_text;
			}
		}
		// Credits are global book metadata. Prefer the canonical settings payload so
		// a stale chapter snapshot cannot override the editor's latest saved values.
		$credits_config = isset( $settings['credits_config'] ) && is_array( $settings['credits_config'] )
			? $settings['credits_config']
			: ( isset( $chapter['credits_config'] ) && is_array( $chapter['credits_config'] ) ? $chapter['credits_config'] : array() );
		$blank_before = $is_credits
			? almaden_bookster_typst_credits_blank_count( $settings, 'before' )
			: ( ! $is_toc ? almaden_bookster_typst_chapter_blank_count( $chapter, 'before' ) : 0 );
		$blank_after  = $is_credits
			? almaden_bookster_typst_credits_blank_count( $settings, 'after' )
			: ( ! $is_toc ? almaden_bookster_typst_chapter_blank_count( $chapter, 'after' ) : 0 );
		$chapter_hide_header = almaden_bookster_typst_bool( $chapter['hide_header'] ?? false ) || almaden_bookster_typst_bool( $chapter['hide_all_headers_footers'] ?? false );
		$chapter_hide_footer = almaden_bookster_typst_bool( $chapter['hide_footer'] ?? false ) || almaden_bookster_typst_bool( $chapter['hide_all_headers_footers'] ?? false );
		$credit_margin_top = $margin_top;
		$credit_margin_bottom = $margin_bot;
		if ( $is_credits && is_numeric( $chapter['credits_margin_top'] ?? null ) ) {
			$credit_margin_top = max( 0, min( 30, (float) $chapter['credits_margin_top'] ) );
		}
		if ( $is_credits && is_numeric( $chapter['credits_margin_bottom'] ?? null ) ) {
			$credit_margin_bottom = max( 0, min( 30, (float) $chapter['credits_margin_bottom'] ) );
		}
		$source .= almaden_bookster_typst_chapter_start_breaks( $settings, $rendered, $blank_before, $is_credits );
		++$rendered;

		$chapter_top_margin = $is_credits ? $credit_margin_top : $margin_top;
		$chapter_bottom_margin = $is_credits ? $credit_margin_bottom : $margin_bot;
		$chapter_header_reserve = $chapter_hide_header ? 0 : $header_reserve;
		$chapter_footer_reserve = $chapter_hide_footer ? 0 : $footer_reserve;
		$source .= '#set page(margin: (top: ' . ( $chapter_top_margin + $chapter_header_reserve ) . $unit . ', bottom: ' . ( $chapter_bottom_margin + $chapter_footer_reserve ) . $unit . ', inside: ' . $margin_inside . $unit . ', outside: ' . $margin_outside . $unit . '))' . "\n";

		$chapter_label_id = preg_replace( '/[^0-9A-Za-z_-]/', '', (string) ( $chapter['id'] ?? (string) ( $chapter_index + 1 ) ) );
		$source .= '#metadata("' . almaden_bookster_typst_escape_string( $title ) . '") <almaden-chapter-start>' . "\n";
		if ( '' !== $chapter_label_id ) {
			$source .= '#metadata("' . almaden_bookster_typst_escape_string( $title ) . '") <almaden-chapter-start-' . almaden_bookster_typst_escape_string( $chapter_label_id ) . '>' . "\n";
		}
		if ( $chapter_hide_header ) {
			$source .= '#metadata("' . almaden_bookster_typst_escape_string( $title ) . '") <almaden-hide-header>' . "\n";
		}
		if ( $chapter_hide_footer ) {
			$source .= '#metadata("' . almaden_bookster_typst_escape_string( $title ) . '") <almaden-hide-footer>' . "\n";
		}
		if ( $is_credits && almaden_bookster_typst_bool( $chapter['credits_hide_header'] ?? false ) ) {
			$source .= '#metadata("credits") <almaden-hide-header>' . "\n";
		}
		if ( $is_credits && almaden_bookster_typst_bool( $chapter['credits_hide_page_number'] ?? false ) ) {
			$source .= '#metadata("credits") <almaden-hide-footer>' . "\n";
		}

		$image_url = '';
		if ( ! empty( $chapter['parity_image'] ) ) {
			$image_url = $chapter['parity_image'];
		} elseif ( ! empty( $chapter['chapter_image_enabled'] ) && '1' === (string) $chapter['chapter_image_enabled'] ) {
			$image_url = $chapter['chapter_image_url'] ?? '';
		}
		$image_asset = almaden_bookster_typst_register_upload( $image_url, $assets, $asset_mode );
		if ( '' !== $image_asset ) {
			$source .= '#align(center + horizon)[#image("' . almaden_bookster_typst_escape_string( $image_asset ) .
				'", width: 100%, height: 100%, fit: "contain")]' . "\n#pagebreak()\n\n";
		}
		if ( $is_credits ) {
			$source .= '#set page(margin: (top: ' . ( $credit_margin_top + $chapter_header_reserve ) . $unit . ', bottom: ' . ( $credit_margin_bottom + $chapter_footer_reserve ) . $unit . ', inside: ' . $margin_inside . $unit . ', outside: ' . $margin_outside . $unit . '))' . "\n";
		}

		$chapter_number = null;
		if ( ! $is_toc && ! $is_credits && '1' !== (string) ( $chapter['exclude_from_numbering'] ?? '0' ) ) {
			++$numbered_chapter_index;
			$chapter_number = $numbered_chapter_index;
		}

		$opening_visibility = almaden_bookster_typst_chapter_opening_visibility( $chapter, $settings );
		$separate_opening = isset( $chapter['opening_separate_content'] ) && '' !== (string) $chapter['opening_separate_content']
			? almaden_bookster_typst_bool( $chapter['opening_separate_content'] )
			: almaden_bookster_typst_bool( $settings['book_separate_opening_content'] ?? true );
		$separate_opening = $separate_opening && ! $is_toc && ! $is_credits && ! empty( $opening_visibility['has_visible_content'] );
		$opening_alignment = strtolower( trim( (string) ( $settings['chapter_page_one_align'] ?? 'center-top' ) ) );
		if ( ! in_array( $opening_alignment, array( 'left-top', 'center-top', 'right-top', 'left-center', 'center-center', 'right-center', 'left-bottom', 'center-bottom', 'right-bottom' ), true ) ) {
			$opening_alignment = 'center-top';
		}
		list( $opening_horizontal, $opening_vertical ) = explode( '-', $opening_alignment, 2 );
		$opening_vertical_typst = 'center' === $opening_vertical ? 'horizon' : $opening_vertical;
		$opening_place_alignment = $opening_horizontal . ' + ' . $opening_vertical_typst;
		$opening_line_alignment = $separate_opening ? $opening_horizontal : $title_align;
		$opening_debug[] = array(
			'chapter_id'             => (string) ( $chapter['id'] ?? '' ),
			'title'                  => $title,
			'configured_alignment'   => $opening_alignment,
			'configured_separation'  => $settings['book_separate_opening_content'] ?? null,
			'chapter_separation'     => $chapter['opening_separate_content'] ?? null,
			'opening_visible'        => ! empty( $opening_visibility['has_visible_content'] ),
			'separated_page'         => $separate_opening,
			'typst_place_alignment'  => $opening_place_alignment,
			'source_strategy'        => $separate_opening ? 'full-page-box-and-place' : 'inline-opening',
		);
		$opening_lines = array();
		$show_title = $opening_visibility['show_title'];
		$show_prefix = $opening_visibility['show_prefix'] && null !== $chapter_number;
		$show_subtitle = $opening_visibility['show_subtitle'];
		$prefix_position = isset( $settings['chapter_prefix_position'] ) && 'below' === strtolower( trim( (string) $settings['chapter_prefix_position'] ) ) ? 'below' : 'above';
		$subtitle_align = isset( $chapter['subtitle_align'] ) && in_array( $chapter['subtitle_align'], array( 'left', 'center', 'right' ), true )
			? $chapter['subtitle_align']
			: ( isset( $settings['chapter_subtitle_align'] ) && in_array( $settings['chapter_subtitle_align'], array( 'left', 'center', 'right' ), true ) ? $settings['chapter_subtitle_align'] : 'center' );

		if ( $show_prefix && 'above' === $prefix_position ) {
			$prefix_template = isset( $settings['chapter_prefix_template'] ) ? (string) $settings['chapter_prefix_template'] : 'Capítulo {N}';
			$prefix_text = str_replace(
				array( '{N}', '{R}' ),
				array(
					(string) $chapter_number,
					almaden_bookster_typst_toc_roman( $chapter_number ),
				),
				$prefix_template
			);
			$opening_lines[] = almaden_bookster_typst_render_chapter_prefix( $prefix_text, $prefix_style, $opening_line_alignment, $prefix_ornament ?? 'none' );
		}

		if ( $show_title ) {
			$display_title = almaden_bookster_typst_transform_title( $title, $title_transform );
			$opening_lines[] = '#align(' . $opening_line_alignment . ')[#heading(level: 1, outlined: true)[#text(font: "' . almaden_bookster_typst_escape_string( $title_font_family ) . '", size: ' . $title_size . 'pt, weight: ' . $title_font_weight . ', style: "' . almaden_bookster_typst_escape_string( $title_font_style ) . '", tracking: ' . $title_letter_spacing . 'pt)[' . almaden_bookster_typst_escape_markup( $display_title ) . ']]]' ;
		}

		if ( $show_prefix && 'below' === $prefix_position ) {
			$prefix_template = isset( $settings['chapter_prefix_template'] ) ? (string) $settings['chapter_prefix_template'] : 'Capítulo {N}';
			$prefix_text = str_replace(
				array( '{N}', '{R}' ),
				array(
					(string) $chapter_number,
					almaden_bookster_typst_toc_roman( $chapter_number ),
				),
				$prefix_template
			);
			$opening_lines[] = almaden_bookster_typst_render_chapter_prefix( $prefix_text, $prefix_style, $opening_line_alignment, $prefix_ornament ?? 'none' );
		}

		if ( $show_subtitle ) {
			$subtitle_text = trim( (string) ( $chapter['subtitle_text'] ?? '' ) );
			$subtitle_font_family = almaden_bookster_typst_font_family( $chapter['subtitle_font_family'] ?? ( $settings['chapter_subtitle_font_family'] ?? $font_family ), $font_family );
			$subtitle_font_size = almaden_bookster_typst_number( $chapter, 'subtitle_font_size', almaden_bookster_typst_number( $settings, 'chapter_subtitle_font_size', 12, 6, 72 ), 6, 72 );
			$subtitle_font_weight = almaden_bookster_typst_font_weight( $chapter['subtitle_font_weight'] ?? ( $settings['chapter_subtitle_font_weight'] ?? $font_weight ), $font_weight );
			$subtitle_font_style = strtolower( trim( (string) ( $chapter['subtitle_font_style'] ?? ( $settings['chapter_subtitle_font_style'] ?? 'normal' ) ) ) );
			if ( ! in_array( $subtitle_font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
				$subtitle_font_style = 'normal';
			}
			$subtitle_letter_spacing = almaden_bookster_typst_number( $chapter, 'subtitle_letter_spacing', almaden_bookster_typst_number( $settings, 'chapter_subtitle_letter_spacing', 0, -20, 20 ), -20, 20 );
			$subtitle_text_transform = strtolower( trim( (string) ( $chapter['subtitle_text_transform'] ?? ( $settings['chapter_subtitle_text_transform'] ?? 'none' ) ) ) );
			$subtitle_display = almaden_bookster_typst_transform_title( $subtitle_text, $subtitle_text_transform );
			$opening_lines[] = '#align(' . ( $separate_opening ? $opening_horizontal : $subtitle_align ) . ')[#text(font: "' . almaden_bookster_typst_escape_string( $subtitle_font_family ) . '", size: ' . round( max( 6, $subtitle_font_size * 0.75 ), 3 ) . 'pt, weight: ' . $subtitle_font_weight . ', style: "' . almaden_bookster_typst_escape_string( $subtitle_font_style ) . '", tracking: ' . round( $subtitle_letter_spacing * 0.75, 3 ) . 'pt)[' . almaden_bookster_typst_escape_markup( $subtitle_display ) . ']]';
		}

		if ( ! empty( $opening_lines ) ) {
			if ( $separate_opening ) {
				$source .= '#almaden-page-styled("opening")[' . "\n";
				$source .= '#box(width: 100%, height: 100%)[' . "\n";
				$source .= '#place(' . $opening_place_alignment . ')[' . "\n";
				$source .= '#block(breakable: false)[' . "\n";
				$source .= implode( "\n#v(3mm)\n", $opening_lines ) . "\n";
				$source .= "]\n]\n]\n]\n#pagebreak()\n\n";
			} else {
				$source .= '#almaden-page-styled("opening")[' . "\n";
				$source .= ( $show_title ? '#v(10mm)' : '#v(4mm)' ) . "\n";
				$source .= implode( "\n#v(3mm)\n", $opening_lines ) . "\n";
				if ( $show_title ) {
					$source .= '#v(' . $title_gap . $unit . ')' . "\n\n";
				} else {
					$source .= "\n";
				}
				$source .= "]\n";
			}
		}

		if ( $is_toc ) {
			$toc_title_source = almaden_bookster_typst_render_toc(
				$chapter,
				$chapters,
				$settings,
				array(
					'title_family'      => $title_font_family,
					'title_size'        => $title_size,
					'title_weight'      => $title_font_weight,
					'title_line_height' => 1.2,
					'item_family'       => $font_family,
					'item_size'         => $font_size,
					'item_weight'       => $font_weight,
					'item_line_height'  => $line_height,
				),
				$assets,
				$resolve_toc_font,
				! ( isset( $chapter['toc_hide_title'] ) && '1' === (string) $chapter['toc_hide_title'] )
			);
			$source .= $toc_title_source;
			continue;
		}

		if ( $is_credits && ! empty( $credits_config ) ) {
			$credits_font_family = almaden_bookster_typst_font_family( $chapter['credits_font_family'] ?? '', $font_family );
			$credits_font_size = almaden_bookster_typst_number( $chapter, 'credits_font_size', $font_size, 5, 72 );
			$credits_font_weight = almaden_bookster_typst_font_weight( $chapter['credits_font_weight'] ?? $font_weight, $font_weight );
			$credits_tracking = almaden_bookster_typst_number( $chapter, 'credits_letter_spacing', 0, -20, 20 );
			$credits_align = almaden_bookster_typst_credits_alignment( $chapter['credits_align'] ?? '' );
			$source .= almaden_bookster_typst_render_credits(
				$credits_config,
				$chapter['credits_author_label'] ?? '',
				array(
					'family'      => $credits_font_family,
					'size'        => $credits_font_size,
					'weight'      => $credits_font_weight,
					'line_height' => $line_height,
					'tracking'    => $credits_tracking,
					'align'       => $credits_align,
				),
				$assets,
				$resolve_credits_font,
				$payload['coverSettings'] ?? ( $payload['cover_settings'] ?? array() ),
				$asset_mode
			);
		} else {
			if ( $page_columns_enabled && ! $is_credits ) {
				$source .= '#set page(columns: ' . $page_columns_count . ')' . "\n";
				$source .= '#set columns(gutter: ' . almaden_bookster_typst_length_literal( $page_columns_gap, $unit ) . ')' . "\n";
			}
			$chapter_footnotes = 'page' === $footnote_mode
				? array(
					'raw' => $content,
					'definitions' => array(),
					'order' => array(),
					'numbers' => array(),
				)
				: almaden_bookster_typst_collect_footnote_data( $content );
			$chapter_endnotes = array();
			foreach ( (array) ( $chapter_footnotes['order'] ?? array() ) as $footnote_id ) {
				if ( empty( $chapter_footnotes['definitions'][ $footnote_id ] ) ) {
					continue;
				}
				$chapter_endnotes[] = array(
					'id'     => $footnote_id,
					'number' => (int) ( $chapter_footnotes['numbers'][ $footnote_id ] ?? 0 ),
					'body'   => (string) $chapter_footnotes['definitions'][ $footnote_id ],
				);
			}
			$content_render_options = array(
				'hyphenation_exceptions' => $hyphenation_exceptions,
				'heading_styles'         => array(
					1 => array(
						'font_family'    => $heading1_font_family,
						'font_size'      => $heading1_font_size,
						'font_weight'    => $heading1_font_weight,
						'font_style'     => $heading1_font_style,
						'letter_spacing' => 0,
					),
					2 => array(
						'font_family'    => $heading2_font_family,
						'font_size'      => $heading2_font_size,
						'font_weight'    => $heading2_font_weight,
						'font_style'     => $heading2_font_style,
						'letter_spacing' => 0,
					),
					3 => array(
						'font_family'    => $heading3_font_family,
						'font_size'      => $heading3_font_size,
						'font_weight'    => $heading3_font_weight,
						'font_style'     => $heading3_font_style,
						'letter_spacing' => 0,
					),
				),
			);
			if ( 'page' !== $footnote_mode ) {
				$content_render_options['footnotes'] = $chapter_footnotes;
				$content_render_options['footnote_mode'] = $footnote_mode;
			}
			$content_align = $justify ? $last_align : $text_align;
			$uses_page_templates = ! empty( $page_template_context['templates'] );
			if ( $uses_page_templates ) {
				// Physical template pages must stay at document level. #set align keeps
				// the chapter alignment without wrapping those pages in a container.
				$source .= '#set align(' . $content_align . ')' . "\n";
			} else {
				$source .= '#align(' . $content_align . ")[\n";
			}
			$source       .= almaden_bookster_typst_render_blocks(
				$chapter_footnotes['raw'] ?? $content,
				$content_render_options
			) . "\n";
			if ( $uses_page_templates ) {
				$source .= '#set align(left)' . "\n";
			} else {
				$source .= "]\n";
			}
			if ( ! empty( $chapter_endnotes ) ) {
				if ( 'chapter' === $footnote_mode ) {
					$source .= "\n" . almaden_bookster_typst_render_footnote_entries(
						$chapter_endnotes,
						array(
							'title'         => $footnote_chapter_title,
							'title_level'   => 2,
							'font_family'   => $footnote_font_family,
							'font_size'     => $footnote_font_size,
							'font_weight'   => $footnote_font_weight,
							'align'         => $footnote_align,
							'leading'       => $footnote_line_height,
							'letter_spacing'=> $footnote_letter_spacing,
							'hyphenate'     => $footnote_hyphenate,
							'entry_gap'     => $footnote_entry_spacing,
							'heading_margin'=> 0.7,
						)
					);
				} elseif ( 'book' === $footnote_mode ) {
					$book_reference_groups[] = array(
						'title'   => $title,
						'entries' => $chapter_endnotes,
					);
				}
			}
			if ( $page_columns_enabled && ! $is_credits ) {
				$source .= '#set page(columns: 1)' . "\n";
			}
		}
		$plain_content  = almaden_bookster_typst_plain_text( $content );
		if ( '' !== $plain_content ) {
			$plain_parts[] = $plain_content;
		}
		$plain_extras = array_merge( $plain_extras, almaden_bookster_typst_plain_footnotes( $content ) );
		for ( $blank_index = 0; $blank_index < $blank_after; ++$blank_index ) {
			$source .= "\n#pagebreak()\n";
			$source .= '#metadata("' . ( $is_credits ? 'credits-after' : 'chapter-after' ) . '") <almaden-intentional-blank>' . "\n";
		}
		if ( $is_credits ) {
			$source .= '#set page(margin: (top: ' . ( $margin_top + $chapter_header_reserve ) . $unit . ', bottom: ' . ( $margin_bot + $chapter_footer_reserve ) . $unit . ', inside: ' . $margin_inside . $unit . ', outside: ' . $margin_outside . $unit . '))' . "\n";
		}
	}

	$GLOBALS['almaden_bookster_typst_opening_debug'] = $opening_debug;

	if ( 'book' === $footnote_mode && ! empty( $book_reference_groups ) ) {
		$source .= "\n#pagebreak()\n\n";
		$source .= '#metadata("' . almaden_bookster_typst_escape_string( $footnote_book_title ) . '") <almaden-chapter-start>' . "\n";
		$source .= '#heading(level: 1)[#text(font: "' . almaden_bookster_typst_escape_string( $footnote_font_family ) . '", size: ' . max( 12, round( $footnote_font_size * 1.9, 2 ) ) . 'pt, weight: ' . $footnote_font_weight . ')[ ' . almaden_bookster_typst_escape_markup( $footnote_book_title ) . ' ]]' . "\n";
		$source .= '#v(0.7cm)' . "\n";
		foreach ( $book_reference_groups as $reference_group ) {
			if ( empty( $reference_group['entries'] ) ) {
				continue;
			}
			$source .= almaden_bookster_typst_render_footnote_entries(
				$reference_group['entries'],
				array(
					'title'       => $reference_group['title'] ?? '',
					'title_level' => 2,
					'font_family' => $footnote_font_family,
					'font_size'   => $footnote_font_size,
					'font_weight' => $footnote_font_weight,
					'align'       => $footnote_align,
					'leading'     => $footnote_line_height,
					'letter_spacing' => $footnote_letter_spacing,
					'hyphenate'   => $footnote_hyphenate,
					'entry_gap'   => $footnote_entry_spacing,
				)
			);
		}
	}

	$font_assets = array_filter(
		array_merge(
			$font_error ? array() : $font['files'],
			$title_font_error ? array() : $title_font['files'],
			$header_font_error ? array() : $header_font['files'],
			$footer_font_error ? array() : $footer_font['files'],
			$footnote_font_error ? array() : $footnote_font_assets,
			$heading1_font_error ? array() : $heading1_font['files'],
			$heading2_font_error ? array() : $heading2_font['files'],
			$heading3_font_error ? array() : $heading3_font['files'],
			$toc_font_assets,
			$credits_font_assets,
			$inline_font_assets
		)
	);
	$source = almaden_bookster_typst_compose_page_templates( $source, $page_template_context, $assets );
	$source = almaden_bookster_typst_append_chapter_counter_report( $source, $chapters );

	return array(
		'source'        => $source,
		'page_templates' => $page_template_context['templates'],
		'page_template_context' => $page_template_context,
		'semantic_text' => implode( ' ', $plain_parts ),
		'semantic_extras' => $plain_extras,
		'assets'        => $assets,
		'font_assets'   => array_values( array_unique( $font_assets ) ),
		'build_error'   => $font_error ?: $title_font_error ?: $header_font_error ?: $footer_font_error ?: $heading1_font_error ?: $heading2_font_error ?: $heading3_font_error ?: $toc_font_error ?: $credits_font_error ?: $inline_font_error,
		'heading_styles' => array(
			1 => array(
				'font_family'    => $heading1_font_family,
				'font_size'      => $heading1_font_size,
				'font_weight'    => $heading1_font_weight,
				'font_style'     => $heading1_font_style,
				'letter_spacing' => 0,
			),
			2 => array(
				'font_family'    => $heading2_font_family,
				'font_size'      => $heading2_font_size,
				'font_weight'    => $heading2_font_weight,
				'font_style'     => $heading2_font_style,
				'letter_spacing' => 0,
			),
			3 => array(
				'font_family'    => $heading3_font_family,
				'font_size'      => $heading3_font_size,
				'font_weight'    => $heading3_font_weight,
				'font_style'     => $heading3_font_style,
				'letter_spacing' => 0,
			),
		),
		'typography'    => array(
			'family'            => $font_family,
			'size'              => $font_size,
			'weight'            => $font_weight,
			'line_height'       => $line_height,
			'align'             => $text_align,
			'last_align'        => $last_align,
			'hyphenate'         => $hyphenate,
			'paragraph_indent'  => $paragraph_indent,
			'paragraph_spacing' => $paragraph_gap,
		),
		'geometry'      => array(
			'unit'           => $unit,
			'width'          => $width,
			'height'         => $height,
			'top'            => $margin_top,
			'bottom'         => $margin_bot,
			'content_top'    => $margin_top + $header_reserve,
			'content_bottom' => $margin_bot + $footer_reserve,
			'inside'         => $margin_inside,
			'outside'        => $margin_outside,
			'bleed'          => $bleed,
		),
		'source_hash'   => hash( 'sha256', implode( "\n", array_merge( $plain_parts, $plain_extras ) ) ),
	);
}
