<?php
/**
 * Plugin Name: AlmadenBookster
 * Description: Plugin personalizado AlmadenBookster.
 * Version: 1.0.0
 * Author: Tu Nombre
 * Text Domain: almaden-bookster
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// --- Módulos de Google Fonts (Admin) ---
require_once plugin_dir_path( __FILE__ ) . 'includes/frontend/pages.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/frontend/app-shell.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/admin-pages.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/admin-fonts.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/admin-fonts-page.php';
require_once plugin_dir_path( __FILE__ ) . 'modules/learni/init.php';
require_once plugin_dir_path( __FILE__ ) . 'modules/blog-post/init.php';
require_once plugin_dir_path( __FILE__ ) . 'modules/login-register/init.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/integrations/learni-integration.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/authors/authors.php';

// Modulos CPT
require_once plugin_dir_path( __FILE__ ) . 'includes/cpt/cpt.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/books/book-authors.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/books/book-authors-hooks.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/publishers/publishers.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/publishers/permissions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/publishers/settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/publishers/onboarding.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/publishers/tour.php';

// --- Frontend Booklist y Creación Automática de Página ---
require_once plugin_dir_path( __FILE__ ) . 'includes/frontend.php';

// --- Manejadores AJAX ---
require_once plugin_dir_path( __FILE__ ) . 'includes/ajax/ajax-credits-json.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ajax/ajax-settings-pdf.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ajax/ajax-settings-credits.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ajax/ajax-credits-persistence.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ajax/ajax-save-book.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ajax/ajax-publish.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ajax/ajax-settings-templates.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ajax/ajax-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ajax/ajax-cover.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/ajax/ajax-user-prefs.php';

// --- Configuraciones Generales y Seguridad ---
require_once plugin_dir_path( __FILE__ ) . 'includes/helpers/crypto.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/admin-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin/admin-filesize.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/io/gdrive-client.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/io/epub-export.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/helpers/cover-thumbnail.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/io/process-utils.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/io/book-import.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/io/document-import.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/io/book-export.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/io/cover-pdf-export.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/payments/woocommerce-integration.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/frontend/access-control.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/reader/highlights.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/reader/highlight-comments.php';


// --- Crear Tabla Especial de Ajustes de PDF ---

function almaden_bookster_create_settings_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'almaden_book_settings';
	
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
	
		if ( get_option( 'almaden_bookster_db_version' ) !== '2.4.2' || ! $table_exists ) {
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			book_id bigint(20) NOT NULL,
			unit varchar(10) DEFAULT 'cm' NOT NULL,
			page_size varchar(20) DEFAULT 'A4' NOT NULL,
			page_width float DEFAULT 21.0 NOT NULL,
			page_height float DEFAULT 29.7 NOT NULL,
			margin_top float DEFAULT 2.5 NOT NULL,
			margin_bottom float DEFAULT 2.5 NOT NULL,
			margin_left float DEFAULT 2.0 NOT NULL,
			margin_right float DEFAULT 2.0 NOT NULL,
			margin_left_odd float DEFAULT 2.0 NOT NULL,
			margin_right_odd float DEFAULT 2.0 NOT NULL,
			margin_left_even float DEFAULT 2.0 NOT NULL,
			margin_right_even float DEFAULT 2.0 NOT NULL,
			padding_top float DEFAULT 0.0 NOT NULL,
			padding_bottom float DEFAULT 0.0 NOT NULL,
			padding_left float DEFAULT 0.0 NOT NULL,
			padding_right float DEFAULT 0.0 NOT NULL,
			bleeding float DEFAULT 0.0 NOT NULL,
			export_grayscale tinyint(1) DEFAULT 0 NOT NULL,
			font_family_content varchar(50) DEFAULT 'Merriweather' NOT NULL,
			font_weight_content varchar(20) DEFAULT 'normal' NOT NULL,
			font_size_content float DEFAULT 11.5 NOT NULL,
			line_height_content float DEFAULT 1.65 NOT NULL,
			content_text_align varchar(20) DEFAULT 'justify' NOT NULL,
			content_text_align_last varchar(20) DEFAULT 'left' NOT NULL,
			content_hyphenation tinyint(1) DEFAULT 1 NOT NULL,
			content_language varchar(10) DEFAULT 'es' NOT NULL,
			content_hyphenation_exceptions varchar(2000) DEFAULT '' NOT NULL,
			content_paragraph_indent float DEFAULT 0.0 NOT NULL,
			content_paragraph_spacing float DEFAULT 14.0 NOT NULL,
			font_family_headings varchar(50) DEFAULT '' NOT NULL,
			font_family_h1 varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			font_weight_h1 varchar(20) DEFAULT 'bold' NOT NULL,
			font_style_h1 varchar(20) DEFAULT 'normal' NOT NULL,
			font_size_h1 float DEFAULT 24.0 NOT NULL,
			line_height_h1 float DEFAULT 1.3 NOT NULL,
			text_align_h1 varchar(20) DEFAULT 'center' NOT NULL,
			margin_top_h1 float DEFAULT 24.0 NOT NULL,
			margin_bottom_h1 float DEFAULT 16.0 NOT NULL,
			font_family_h2 varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			font_weight_h2 varchar(20) DEFAULT 'bold' NOT NULL,
			font_style_h2 varchar(20) DEFAULT 'italic' NOT NULL,
			font_size_h2 float DEFAULT 18.0 NOT NULL,
			line_height_h2 float DEFAULT 1.4 NOT NULL,
			text_align_h2 varchar(20) DEFAULT 'left' NOT NULL,
			margin_top_h2 float DEFAULT 20.0 NOT NULL,
			margin_bottom_h2 float DEFAULT 12.0 NOT NULL,
			font_family_h3 varchar(50) DEFAULT 'Merriweather' NOT NULL,
			font_weight_h3 varchar(20) DEFAULT 'bold' NOT NULL,
			font_style_h3 varchar(20) DEFAULT 'normal' NOT NULL,
			font_size_h3 float DEFAULT 14.0 NOT NULL,
			line_height_h3 float DEFAULT 1.4 NOT NULL,
			text_align_h3 varchar(20) DEFAULT 'left' NOT NULL,
			margin_top_h3 float DEFAULT 16.0 NOT NULL,
			margin_bottom_h3 float DEFAULT 8.0 NOT NULL,
			header_font_family varchar(50) DEFAULT 'Merriweather' NOT NULL,
			header_font_size float DEFAULT 8.5 NOT NULL,
			header_font_weight varchar(20) DEFAULT 'normal' NOT NULL,
			header_font_style varchar(20) DEFAULT 'normal' NOT NULL,
			header_text_transform varchar(20) DEFAULT 'none' NOT NULL,
			header_align varchar(20) DEFAULT 'center' NOT NULL,
			header_letter_spacing float DEFAULT 0.1 NOT NULL,
			header_even_type varchar(50) DEFAULT 'book_title' NOT NULL,
			header_even_custom varchar(255) DEFAULT '' NOT NULL,
			header_odd_type varchar(50) DEFAULT 'chapter_title' NOT NULL,
			header_odd_custom varchar(255) DEFAULT '' NOT NULL,
			header_margin_top float DEFAULT 1.0 NOT NULL,
			header_margin_bottom float DEFAULT 0.5 NOT NULL,
			footer_font_family varchar(50) DEFAULT 'Merriweather' NOT NULL,
			footer_font_size float DEFAULT 9.0 NOT NULL,
			footer_font_weight varchar(20) DEFAULT 'normal' NOT NULL,
			footer_font_style varchar(20) DEFAULT 'normal' NOT NULL,
			footer_text_transform varchar(20) DEFAULT 'none' NOT NULL,
			footer_align varchar(20) DEFAULT 'center' NOT NULL,
			footer_letter_spacing float DEFAULT 0.1 NOT NULL,
			footer_even_type varchar(50) DEFAULT 'page_number' NOT NULL,
			footer_odd_type varchar(50) DEFAULT 'page_number' NOT NULL,
			footer_margin_top float DEFAULT 0.5 NOT NULL,
			footer_margin_bottom float DEFAULT 1.0 NOT NULL,
			footnote_font_family varchar(50) DEFAULT 'Merriweather' NOT NULL,
			footnote_font_size float DEFAULT 8.5 NOT NULL,
			footnote_font_weight varchar(20) DEFAULT 'normal' NOT NULL,
			footnote_align varchar(20) DEFAULT 'justify' NOT NULL,
			footnote_call_scale float DEFAULT 0.65 NOT NULL,
			footnote_call_raise float DEFAULT 0.18 NOT NULL,
			footnote_padding_top float DEFAULT 0.15 NOT NULL,
			footnote_padding_bottom float DEFAULT 0.15 NOT NULL,
			footnote_padding_left float DEFAULT 0.0 NOT NULL,
			footnote_padding_right float DEFAULT 0.0 NOT NULL,
			footnote_separator_show tinyint(1) DEFAULT 0 NOT NULL,
			footnote_separator_align varchar(10) DEFAULT 'left' NOT NULL,
			footnote_separator_width varchar(10) DEFAULT '100' NOT NULL,
			footnote_separator_thickness float DEFAULT 0.25 NOT NULL,
			footnote_separator_margin_bottom float DEFAULT 0.15 NOT NULL,
			first_page_header_type varchar(50) DEFAULT 'blank' NOT NULL,
			first_page_header_custom varchar(255) DEFAULT '' NOT NULL,
			first_page_footer_type varchar(50) DEFAULT 'page_number' NOT NULL,
			first_page_footer_custom varchar(255) DEFAULT '' NOT NULL,
			book_start_page_footer_type varchar(20) DEFAULT 'blank' NOT NULL,
			ebook_bg_type varchar(50) DEFAULT 'color' NOT NULL,
			ebook_bg_color varchar(50) DEFAULT '#ffffff' NOT NULL,
			ebook_bg_image varchar(255) DEFAULT '' NOT NULL,
			ebook_bg_opacity float DEFAULT 1.0 NOT NULL,
			ebook_cover_panel_bg_type varchar(50) DEFAULT 'image' NOT NULL,
			ebook_cover_panel_bg_color varchar(50) DEFAULT 'transparent' NOT NULL,
			ebook_cover_panel_bg_image varchar(255) DEFAULT '' NOT NULL,
			ebook_cover_panel_bg_opacity float DEFAULT 1.0 NOT NULL,
			ebook_font_family_content varchar(50) DEFAULT 'Merriweather' NOT NULL,
			ebook_font_size_content float DEFAULT 18.0 NOT NULL,
			ebook_font_weight_content varchar(50) DEFAULT 'normal' NOT NULL,
			ebook_line_height_content float DEFAULT 1.8 NOT NULL,
			ebook_font_family_headings varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			ebook_font_size_headings float DEFAULT 32.0 NOT NULL,
			ebook_font_weight_headings varchar(50) DEFAULT 'bold' NOT NULL,
			ebook_line_height_headings float DEFAULT 1.3 NOT NULL,
			ebook_text_align_justify tinyint(1) DEFAULT 0 NOT NULL,
			ebook_hyphenation tinyint(1) DEFAULT 0 NOT NULL,
			ebook_chapter_title_align varchar(20) DEFAULT 'center' NOT NULL,
			ebook_chapter_title_text_transform varchar(20) DEFAULT 'none' NOT NULL,
			ebook_chapter_title_padding_top float DEFAULT 2.0 NOT NULL,
			ebook_chapter_title_padding_bottom float DEFAULT 2.0 NOT NULL,
			ebook_chapter_title_padding_left float DEFAULT 0.0 NOT NULL,
			ebook_chapter_title_padding_right float DEFAULT 0.0 NOT NULL,
			ebook_chapter_prefix_show tinyint(1) DEFAULT 0 NOT NULL,
			ebook_chapter_prefix_template varchar(255) DEFAULT 'Capítulo {N}' NOT NULL,
			ebook_chapter_prefix_position varchar(10) DEFAULT 'above' NOT NULL,
			ebook_chapter_prefix_font_family varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			ebook_chapter_prefix_font_size float DEFAULT 16.0 NOT NULL,
			ebook_chapter_prefix_font_weight varchar(20) DEFAULT 'normal' NOT NULL,
			ebook_chapter_prefix_font_style varchar(20) DEFAULT 'normal' NOT NULL,
			ebook_chapter_prefix_letter_spacing float DEFAULT 0.0 NOT NULL,
			ebook_chapter_prefix_ornament varchar(20) DEFAULT 'none' NOT NULL,
			chapter_start_parity varchar(20) DEFAULT 'any' NOT NULL,
			parity_image_mode varchar(20) DEFAULT 'content' NOT NULL,
			chapter_image_mode varchar(30) DEFAULT 'page_blank' NOT NULL,
			chapter_image_url varchar(255) DEFAULT '' NOT NULL,
			chapter_image_inner_width float DEFAULT 100.0 NOT NULL,
			chapter_image_inner_header tinyint(1) DEFAULT 0 NOT NULL,
			chapter_image_inner_footer tinyint(1) DEFAULT 0 NOT NULL,
			chapter_page_one_align varchar(20) DEFAULT 'center' NOT NULL,
			chapter_page_one_vertical varchar(20) DEFAULT 'top' NOT NULL,
			chapter_title_font_family varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			chapter_title_font_size float DEFAULT 24.0 NOT NULL,
			chapter_title_font_weight varchar(20) DEFAULT 'bold' NOT NULL,
			chapter_title_font_style varchar(20) DEFAULT 'normal' NOT NULL,
			chapter_title_text_transform varchar(20) DEFAULT 'none' NOT NULL,
			chapter_title_align varchar(20) DEFAULT 'center' NOT NULL,
			chapter_title_line_height float DEFAULT 1.2 NOT NULL,
			chapter_title_padding_top float DEFAULT 0.0 NOT NULL,
			chapter_title_padding_bottom float DEFAULT 1.5 NOT NULL,
			chapter_title_padding_left float DEFAULT 0.0 NOT NULL,
			chapter_title_padding_right float DEFAULT 0.0 NOT NULL,
			chapter_prefix_show tinyint(1) DEFAULT 0 NOT NULL,
			chapter_prefix_template varchar(255) DEFAULT 'Capítulo {N}' NOT NULL,
			chapter_prefix_position varchar(10) DEFAULT 'above' NOT NULL,
			chapter_prefix_font_family varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			chapter_prefix_font_size float DEFAULT 16.0 NOT NULL,
			chapter_prefix_font_weight varchar(20) DEFAULT 'normal' NOT NULL,
			chapter_prefix_font_style varchar(20) DEFAULT 'normal' NOT NULL,
			chapter_prefix_letter_spacing float DEFAULT 0.0 NOT NULL,
			chapter_prefix_ornament varchar(20) DEFAULT 'none' NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY book_id (book_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'almaden_bookster_db_version', '2.4.2' );
	}
}
add_action( 'init', 'almaden_bookster_create_settings_table' );

function almaden_bookster_get_highlights_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'almaden_book_highlights';
}

function almaden_bookster_create_highlights_table() {
	global $wpdb;
	$table_name = almaden_bookster_get_highlights_table_name();
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

	if ( get_option( 'almaden_bookster_highlights_db_version' ) !== '1.0.0' || ! $table_exists ) {
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			book_id bigint(20) NOT NULL,
			chapter_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			selected_text longtext NOT NULL,
			start_offset int(11) NOT NULL DEFAULT 0,
			end_offset int(11) NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY book_user_chapter (book_id, user_id, chapter_id),
			KEY user_book (user_id, book_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( 'almaden_bookster_highlights_db_version', '1.0.0' );
	}
}
add_action( 'init', 'almaden_bookster_create_highlights_table' );

function almaden_bookster_get_highlight_comments_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'almaden_book_highlight_comments';
}

function almaden_bookster_create_highlight_comments_table() {
	global $wpdb;
	$table_name = almaden_bookster_get_highlight_comments_table_name();
	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

	if ( get_option( 'almaden_bookster_highlight_comments_db_version' ) !== '1.0.0' || ! $table_exists ) {
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			highlight_id bigint(20) NOT NULL,
			book_id bigint(20) NOT NULL,
			chapter_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			comment_text longtext NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY highlight_id (highlight_id),
			KEY book_chapter (book_id, chapter_id),
			KEY user_book (user_id, book_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( 'almaden_bookster_highlight_comments_db_version', '1.0.0' );
	}
}
add_action( 'init', 'almaden_bookster_create_highlight_comments_table' );

function almaden_bookster_activate_plugin() {
		almaden_bookster_sync_book_capabilities();
	if ( class_exists( '\AlmadenBookster\Learni\Module' ) ) {
		\AlmadenBookster\Learni\Module::activate();
	}
	if ( class_exists( '\AlmadenBookster\Auth\Module' ) ) {
		\AlmadenBookster\Auth\Module::activate();
	}
		almaden_bookster_sync_creator_page();
		almaden_bookster_sync_shell_home_page();
		almaden_bookster_sync_dashboard_page();
		almaden_bookster_sync_course_creator_page();
			almaden_bookster_sync_store_page();
			almaden_bookster_sync_authors_page();
			almaden_bookster_sync_author_page();
			almaden_bookster_sync_publisher_page();
			almaden_bookster_sync_publisher_onboarding_page();
			almaden_bookster_sync_quiz_page();
			almaden_bookster_create_book_authors_table();
		almaden_bookster_create_settings_table();
		almaden_bookster_create_highlights_table();
	almaden_bookster_create_highlight_comments_table();
	if ( function_exists( 'almaden_bookster_create_quiz_progress_tables' ) ) {
		almaden_bookster_create_quiz_progress_tables();
	}
	if ( function_exists( 'almaden_bookster_schedule_cover_thumbnail_backfill_cron' ) ) {
		almaden_bookster_schedule_cover_thumbnail_backfill_cron();
	}
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'almaden_bookster_activate_plugin' );

function almaden_bookster_deactivate_plugin() {
	if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
		wp_clear_scheduled_hook( 'almaden_bookster_cover_thumbnail_backfill_event' );
	}
}
register_deactivation_hook( __FILE__, 'almaden_bookster_deactivate_plugin' );

function almaden_bookster_sync_book_capabilities() {
	$roles = array( 'administrator', 'editor' );

	foreach ( $roles as $role_key ) {
		$role = get_role( $role_key );

		if ( $role && ! $role->has_cap( 'almaden_manage_books' ) ) {
			$role->add_cap( 'almaden_manage_books' );
		}
	}
}

add_action( 'init', 'almaden_bookster_sync_book_capabilities' );

add_action('init', function() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'almaden_book_settings';
	$columns = $wpdb->get_col("DESCRIBE $table_name", 0);
	$needed_columns = [
		'margin_left_odd' => 'float DEFAULT 2.0 NOT NULL',
		'margin_right_odd' => 'float DEFAULT 2.0 NOT NULL',
		'margin_left_even' => 'float DEFAULT 2.0 NOT NULL',
		'margin_right_even' => 'float DEFAULT 2.0 NOT NULL',
		'padding_top' => 'float DEFAULT 0.0 NOT NULL',
		'padding_bottom' => 'float DEFAULT 0.0 NOT NULL',
		'padding_left' => 'float DEFAULT 0.0 NOT NULL',
		'padding_right' => 'float DEFAULT 0.0 NOT NULL',
		'bleeding' => 'float DEFAULT 0.0 NOT NULL',
		'chapter_title_padding_left' => 'float DEFAULT 0.0 NOT NULL',
		'chapter_title_padding_right' => 'float DEFAULT 0.0 NOT NULL',
		'export_grayscale' => 'tinyint(1) DEFAULT 0 NOT NULL',
		'header_even_type' => "varchar(50) DEFAULT 'book_title' NOT NULL",
		'header_text_transform' => "varchar(20) DEFAULT 'none' NOT NULL",
		'footer_text_transform' => "varchar(20) DEFAULT 'none' NOT NULL",
		'header_even_custom' => "varchar(255) DEFAULT '' NOT NULL",
		'header_odd_type' => "varchar(50) DEFAULT 'chapter_title' NOT NULL",
		'header_odd_custom' => "varchar(255) DEFAULT '' NOT NULL",
		'footer_even_type' => "varchar(50) DEFAULT 'page_number' NOT NULL",
		'footer_odd_type' => "varchar(50) DEFAULT 'page_number' NOT NULL",
		'first_page_header_type' => "varchar(50) DEFAULT 'blank' NOT NULL",
		'first_page_header_custom' => "varchar(255) DEFAULT '' NOT NULL",
		'first_page_footer_type' => "varchar(50) DEFAULT 'page_number' NOT NULL",
		'first_page_footer_custom' => "varchar(255) DEFAULT '' NOT NULL",
		'book_start_page_footer_type' => "varchar(20) DEFAULT 'blank' NOT NULL",
		'footnote_font_family' => "varchar(50) DEFAULT 'Merriweather' NOT NULL",
		'footnote_font_size' => 'float DEFAULT 8.5 NOT NULL',
		'footnote_font_weight' => "varchar(20) DEFAULT 'normal' NOT NULL",
		'footnote_align' => "varchar(20) DEFAULT 'justify' NOT NULL",
		'footnote_call_scale' => 'float DEFAULT 0.65 NOT NULL',
		'footnote_call_raise' => 'float DEFAULT 0.18 NOT NULL',
		'footnote_padding_top' => 'float DEFAULT 0.15 NOT NULL',
		'footnote_padding_bottom' => 'float DEFAULT 0.15 NOT NULL',
		'footnote_padding_left' => 'float DEFAULT 0.0 NOT NULL',
		'footnote_padding_right' => 'float DEFAULT 0.0 NOT NULL',
		'content_hyphenation_exceptions' => "varchar(2000) DEFAULT '' NOT NULL",
		'chapter_start_parity' => "varchar(20) DEFAULT 'any' NOT NULL",
		'parity_image_mode' => "varchar(20) DEFAULT 'content' NOT NULL",
		'chapter_page_one_align' => "varchar(20) DEFAULT 'center' NOT NULL",
		'chapter_page_one_vertical' => "varchar(20) DEFAULT 'top' NOT NULL",
		'chapter_prefix_show' => 'tinyint(1) DEFAULT 0 NOT NULL',
		'chapter_prefix_template' => "varchar(255) DEFAULT 'Capítulo {N}' NOT NULL",
		'chapter_prefix_position' => "varchar(10) DEFAULT 'above' NOT NULL",
		'chapter_prefix_font_family' => "varchar(50) DEFAULT 'Playfair Display' NOT NULL",
		'chapter_prefix_font_size' => 'float DEFAULT 16.0 NOT NULL',
		'chapter_prefix_font_weight' => "varchar(20) DEFAULT 'normal' NOT NULL",
		'chapter_prefix_font_style' => "varchar(20) DEFAULT 'normal' NOT NULL",
		'chapter_prefix_letter_spacing' => 'float DEFAULT 0.0 NOT NULL',
		'chapter_prefix_ornament' => "varchar(20) DEFAULT 'none' NOT NULL",
		'chapter_title_line_height' => 'float DEFAULT 1.2 NOT NULL',
		'chapter_title_text_transform' => "varchar(20) DEFAULT 'none' NOT NULL",
		'ebook_bg_type' => "varchar(20) DEFAULT 'color' NOT NULL",
		'ebook_bg_color' => "varchar(20) DEFAULT '#ffffff' NOT NULL",
		'ebook_bg_image' => "varchar(255) DEFAULT '' NOT NULL",
		'ebook_bg_opacity' => "float DEFAULT 1.0 NOT NULL",
		'ebook_cover_panel_bg_type' => "varchar(20) DEFAULT 'image' NOT NULL",
		'ebook_cover_panel_bg_color' => "varchar(20) DEFAULT 'transparent' NOT NULL",
		'ebook_cover_panel_bg_image' => "varchar(255) DEFAULT '' NOT NULL",
		'ebook_cover_panel_bg_opacity' => "float DEFAULT 1.0 NOT NULL",
		'ebook_font_family_content' => "varchar(50) DEFAULT 'Merriweather' NOT NULL",
		'ebook_font_size_content' => "float DEFAULT 18.0 NOT NULL",
		'ebook_font_weight_content' => "varchar(20) DEFAULT 'normal' NOT NULL",
		'font_weight_content' => "varchar(20) DEFAULT 'normal' NOT NULL",
		'ebook_line_height_content' => "float DEFAULT 1.8 NOT NULL",
		'ebook_font_family_headings' => "varchar(50) DEFAULT 'Playfair Display' NOT NULL",
		'ebook_font_size_headings' => "float DEFAULT 32.0 NOT NULL",
		'ebook_font_weight_headings' => "varchar(20) DEFAULT 'bold' NOT NULL",
		'ebook_line_height_headings' => "float DEFAULT 1.3 NOT NULL",
		'ebook_text_align_justify' => "tinyint(1) DEFAULT 0 NOT NULL",
		'ebook_hyphenation' => "tinyint(1) DEFAULT 0 NOT NULL",
		'ebook_chapter_title_align' => "varchar(20) DEFAULT 'center' NOT NULL",
		'ebook_chapter_title_text_transform' => "varchar(20) DEFAULT 'none' NOT NULL",
		'ebook_chapter_title_padding_top' => "float DEFAULT 2.0 NOT NULL",
		'ebook_chapter_title_padding_bottom' => "float DEFAULT 2.0 NOT NULL",
		'ebook_chapter_title_padding_left' => "float DEFAULT 0.0 NOT NULL",
		'ebook_chapter_title_padding_right' => "float DEFAULT 0.0 NOT NULL",
		'ebook_chapter_prefix_show' => "tinyint(1) DEFAULT 0 NOT NULL",
		'ebook_chapter_prefix_template' => "varchar(255) DEFAULT 'Capítulo {N}' NOT NULL",
		'ebook_chapter_prefix_position' => "varchar(10) DEFAULT 'above' NOT NULL",
		'ebook_chapter_prefix_font_family' => "varchar(50) DEFAULT 'Playfair Display' NOT NULL",
		'ebook_chapter_prefix_font_size' => "float DEFAULT 16.0 NOT NULL",
		'ebook_chapter_prefix_font_weight' => "varchar(20) DEFAULT 'normal' NOT NULL",
		'ebook_chapter_prefix_font_style' => "varchar(20) DEFAULT 'normal' NOT NULL",
		'ebook_chapter_prefix_letter_spacing' => "float DEFAULT 0.0 NOT NULL",
		'ebook_chapter_prefix_ornament' => "varchar(20) DEFAULT 'none' NOT NULL",
		'footnote_font_family' => "varchar(50) DEFAULT 'Merriweather' NOT NULL",
		'footnote_font_size' => 'float DEFAULT 8.5 NOT NULL',
		'footnote_font_weight' => "varchar(20) DEFAULT 'normal' NOT NULL",
		'footnote_align' => "varchar(20) DEFAULT 'justify' NOT NULL",
		'footnote_padding_top' => 'float DEFAULT 0.15 NOT NULL',
		'footnote_padding_bottom' => 'float DEFAULT 0.15 NOT NULL',
		'footnote_padding_left' => 'float DEFAULT 0.0 NOT NULL',
		'footnote_padding_right' => 'float DEFAULT 0.0 NOT NULL'
	];
	foreach ($needed_columns as $col => $def) {
		if (!empty($columns) && !in_array($col, $columns)) {
			$wpdb->query("ALTER TABLE $table_name ADD COLUMN $col $def");
		}
	}
});
