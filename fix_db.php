<?php
require_once('../../../wp-load.php');
global $wpdb;
$table_name = $wpdb->prefix . 'almaden_book_settings';
$columns = $wpdb->get_col("DESCRIBE $table_name", 0);

$data_keys = array(
	'book_id', 'unit', 'page_size', 'page_width', 'page_height', 'margin_top', 'margin_bottom',
	'margin_left', 'margin_right', 'margin_left_odd', 'margin_right_odd', 'margin_left_even',
	'margin_right_even', 'padding_top', 'padding_bottom', 'padding_left', 'padding_right',
	'bleeding', 'export_grayscale', 'font_family_content', 'font_size_content', 'line_height_content',
	'content_text_align', 'content_hyphenation', 'content_language', 'content_paragraph_indent',
	'content_paragraph_spacing', 'font_family_headings', 'font_family_h1', 'font_family_h2',
	'font_family_h3', 'font_weight_h1', 'font_weight_h2', 'font_weight_h3', 'font_size_h1',
	'font_size_h2', 'font_size_h3', 'header_font_family', 'header_font_size', 'header_font_weight',
	'header_font_style', 'header_letter_spacing', 'header_even_type', 'header_even_custom',
	'header_odd_type', 'header_odd_custom', 'footer_font_family', 'footer_font_size',
	'footer_font_weight', 'footer_font_style', 'footer_letter_spacing', 'footer_even_type',
	'footer_odd_type', 'first_page_header_type', 'first_page_header_custom', 'first_page_footer_type',
	'first_page_footer_custom', 'chapter_start_parity', 'parity_image_mode', 'chapter_page_one_align',
	'chapter_page_one_vertical', 'chapter_title_font_family', 'chapter_title_font_size',
	'chapter_title_font_weight', 'chapter_title_font_style', 'chapter_title_align',
	'chapter_title_padding_top', 'chapter_title_padding_bottom', 'chapter_title_padding_left',
	'chapter_title_padding_right', 'chapter_title_line_height', 'chapter_title_text_transform',
	'chapter_prefix_show', 'chapter_prefix_template', 'chapter_prefix_position',
	'chapter_prefix_font_family', 'chapter_prefix_font_size', 'chapter_prefix_font_weight',
	'chapter_prefix_font_style', 'chapter_prefix_letter_spacing', 'chapter_prefix_ornament',
	'header_margin_top', 'header_margin_bottom', 'header_align', 'footer_margin_top',
	'footer_margin_bottom', 'footer_align'
);

$missing = array();
foreach ($data_keys as $key) {
	if (!in_array($key, $columns)) {
		$missing[] = $key;
	}
}
echo json_encode($missing);
