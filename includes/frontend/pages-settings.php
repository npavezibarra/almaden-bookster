<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_pages_settings_defaults() {
	return array(
		'creator_page_id' => 0,
		'creator_slug'    => 'almaden-booklist',
		'creator_title'   => 'Taller',
		'dashboard_page_id' => 0,
		'dashboard_slug'    => 'dashboard',
		'dashboard_title'   => 'Dashboard',
		'course_creator_page_id' => 0,
		'course_creator_slug'    => 'almaden-cursos',
		'course_creator_title'   => 'Cursos',
		'course_archive_page_id' => 0,
		'course_archive_slug'    => 'sala-de-clases',
		'course_archive_title'   => 'Sala de clases',
		'authors_page_id' => 0,
		'authors_slug'    => 'autores',
		'authors_title'   => 'Autores',
		'store_page_id'   => 0,
		'store_slug'      => 'bookshelf',
		'store_title'     => 'Ebook Store',
		'store_menu_label' => 'Ebook Store',
		'store_menu_enabled' => 1,
	);
}

function almaden_bookster_get_pages_settings() {
	$saved_settings = get_option( 'almaden_bookster_pages_settings', array() );

	if ( ! is_array( $saved_settings ) ) {
		$saved_settings = array();
	}

	return wp_parse_args( $saved_settings, almaden_bookster_get_pages_settings_defaults() );
}

function almaden_bookster_sanitize_pages_settings( $raw_settings ) {
	$defaults = almaden_bookster_get_pages_settings_defaults();
	$current_settings = almaden_bookster_get_pages_settings();
	$slug     = isset( $raw_settings['creator_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['creator_slug'] ) ) : $defaults['creator_slug'];
	$title    = isset( $raw_settings['creator_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['creator_title'] ) ) : $defaults['creator_title'];
	$dashboard_slug  = isset( $raw_settings['dashboard_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['dashboard_slug'] ) ) : ( isset( $current_settings['dashboard_slug'] ) && '' !== $current_settings['dashboard_slug'] ? $current_settings['dashboard_slug'] : $defaults['dashboard_slug'] );
	$dashboard_title = isset( $raw_settings['dashboard_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['dashboard_title'] ) ) : ( isset( $current_settings['dashboard_title'] ) && '' !== $current_settings['dashboard_title'] ? $current_settings['dashboard_title'] : $defaults['dashboard_title'] );
	$course_creator_slug  = isset( $raw_settings['course_creator_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['course_creator_slug'] ) ) : $defaults['course_creator_slug'];
	$course_creator_title = isset( $raw_settings['course_creator_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['course_creator_title'] ) ) : $defaults['course_creator_title'];
	$course_archive_slug  = isset( $raw_settings['course_archive_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['course_archive_slug'] ) ) : $defaults['course_archive_slug'];
	$course_archive_title = isset( $raw_settings['course_archive_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['course_archive_title'] ) ) : $defaults['course_archive_title'];
	$authors_slug  = isset( $raw_settings['authors_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['authors_slug'] ) ) : $defaults['authors_slug'];
	$authors_title = isset( $raw_settings['authors_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['authors_title'] ) ) : $defaults['authors_title'];
	$store_slug  = isset( $raw_settings['store_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['store_slug'] ) ) : $defaults['store_slug'];
	$store_title = isset( $raw_settings['store_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['store_title'] ) ) : $defaults['store_title'];
	$store_menu_label = isset( $raw_settings['store_menu_label'] ) ? sanitize_text_field( wp_unslash( $raw_settings['store_menu_label'] ) ) : $defaults['store_menu_label'];
	$store_menu_enabled = ! empty( $raw_settings['store_menu_enabled'] ) ? 1 : 0;

	if ( '' === $slug ) {
		$slug = $defaults['creator_slug'];
	}

	if ( '' === $title ) {
		$title = $defaults['creator_title'];
	}

	if ( '' === $course_creator_slug ) {
		$course_creator_slug = $defaults['course_creator_slug'];
	}

	if ( '' === $course_creator_title ) {
		$course_creator_title = $defaults['course_creator_title'];
	}

	if ( '' === $course_archive_slug ) {
		$course_archive_slug = $defaults['course_archive_slug'];
	}

	if ( '' === $course_archive_title ) {
		$course_archive_title = $defaults['course_archive_title'];
	}

	if ( '' === $authors_slug ) {
		$authors_slug = $defaults['authors_slug'];
	}

	if ( '' === $authors_title ) {
		$authors_title = $defaults['authors_title'];
	}

	if ( '' === $store_slug ) {
		$store_slug = $defaults['store_slug'];
	}

	if ( '' === $store_title ) {
		$store_title = $defaults['store_title'];
	}

	if ( '' === $store_menu_label ) {
		$store_menu_label = $defaults['store_menu_label'];
	}

	return array(
		'creator_page_id' => isset( $raw_settings['creator_page_id'] ) ? absint( $raw_settings['creator_page_id'] ) : 0,
		'creator_slug'    => $slug,
		'creator_title'   => $title,
		'dashboard_page_id' => isset( $raw_settings['dashboard_page_id'] ) ? absint( $raw_settings['dashboard_page_id'] ) : ( isset( $current_settings['dashboard_page_id'] ) ? absint( $current_settings['dashboard_page_id'] ) : 0 ),
		'dashboard_slug'    => $dashboard_slug,
		'dashboard_title'   => $dashboard_title,
		'course_creator_page_id' => isset( $raw_settings['course_creator_page_id'] ) ? absint( $raw_settings['course_creator_page_id'] ) : 0,
		'course_creator_slug'    => $course_creator_slug,
		'course_creator_title'   => $course_creator_title,
		'course_archive_page_id' => isset( $raw_settings['course_archive_page_id'] ) ? absint( $raw_settings['course_archive_page_id'] ) : 0,
		'course_archive_slug'    => $course_archive_slug,
		'course_archive_title'   => $course_archive_title,
		'authors_page_id' => isset( $raw_settings['authors_page_id'] ) ? absint( $raw_settings['authors_page_id'] ) : 0,
		'authors_slug'    => $authors_slug,
		'authors_title'   => $authors_title,
		'store_page_id'   => isset( $raw_settings['store_page_id'] ) ? absint( $raw_settings['store_page_id'] ) : 0,
		'store_slug'      => $store_slug,
		'store_title'     => $store_title,
		'store_menu_label'=> $store_menu_label,
		'store_menu_enabled' => $store_menu_enabled,
	);
}

function almaden_bookster_get_creator_page_id() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['creator_page_id'] ) ? absint( $settings['creator_page_id'] ) : 0;
}

function almaden_bookster_get_dashboard_page_id() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['dashboard_page_id'] ) ? absint( $settings['dashboard_page_id'] ) : 0;
}

function almaden_bookster_get_course_creator_page_id() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['course_creator_page_id'] ) ? absint( $settings['course_creator_page_id'] ) : 0;
}

function almaden_bookster_get_course_archive_page_id() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['course_archive_page_id'] ) ? absint( $settings['course_archive_page_id'] ) : 0;
}

function almaden_bookster_get_creator_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['creator_slug'] ) && '' !== $settings['creator_slug'] ? $settings['creator_slug'] : 'almaden-booklist';
}

function almaden_bookster_get_creator_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['creator_title'] ) && '' !== $settings['creator_title'] ? $settings['creator_title'] : 'Taller';
}

function almaden_bookster_get_dashboard_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['dashboard_slug'] ) && '' !== $settings['dashboard_slug'] ? $settings['dashboard_slug'] : 'dashboard';
}

function almaden_bookster_get_dashboard_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['dashboard_title'] ) && '' !== $settings['dashboard_title'] ? $settings['dashboard_title'] : 'Dashboard';
}

function almaden_bookster_get_course_creator_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['course_creator_slug'] ) && '' !== $settings['course_creator_slug'] ? $settings['course_creator_slug'] : 'almaden-cursos';
}

function almaden_bookster_get_course_creator_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['course_creator_title'] ) && '' !== $settings['course_creator_title'] ? $settings['course_creator_title'] : 'Cursos';
}

function almaden_bookster_get_course_archive_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['course_archive_slug'] ) && '' !== $settings['course_archive_slug'] ? $settings['course_archive_slug'] : 'sala-de-clases';
}

function almaden_bookster_get_course_archive_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['course_archive_title'] ) && '' !== $settings['course_archive_title'] ? $settings['course_archive_title'] : 'Sala de clases';
}

function almaden_bookster_get_authors_page_id() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['authors_page_id'] ) ? absint( $settings['authors_page_id'] ) : 0;
}

function almaden_bookster_get_authors_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['authors_slug'] ) && '' !== $settings['authors_slug'] ? $settings['authors_slug'] : 'autores';
}

function almaden_bookster_get_authors_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['authors_title'] ) && '' !== $settings['authors_title'] ? $settings['authors_title'] : 'Autores';
}

function almaden_bookster_get_authors_page_url( $query_args = array() ) {
	$slug = trim( almaden_bookster_get_authors_slug(), '/' );
	$url  = home_url( '/' . $slug . '/' );

	if ( empty( $query_args ) ) {
		return $url;
	}

	return add_query_arg( $query_args, $url );
}

function almaden_bookster_get_store_page_id() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['store_page_id'] ) ? absint( $settings['store_page_id'] ) : 0;
}

function almaden_bookster_get_store_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['store_slug'] ) && '' !== $settings['store_slug'] ? $settings['store_slug'] : 'bookshelf';
}

function almaden_bookster_get_store_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['store_title'] ) && '' !== $settings['store_title'] ? $settings['store_title'] : 'Ebook Store';
}

function almaden_bookster_get_store_menu_label() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['store_menu_label'] ) && '' !== $settings['store_menu_label'] ? $settings['store_menu_label'] : 'Ebook Store';
}

function almaden_bookster_is_store_menu_enabled() {
	$settings = almaden_bookster_get_pages_settings();
	return ! empty( $settings['store_menu_enabled'] );
}

function almaden_bookster_get_creator_page_url( $query_args = array() ) {
	$slug = trim( almaden_bookster_get_creator_slug(), '/' );
	$url  = home_url( '/' . $slug . '/' );

	if ( empty( $query_args ) ) {
		return $url;
	}

	return add_query_arg( $query_args, $url );
}

function almaden_bookster_get_dashboard_page_url( $query_args = array() ) {
	$slug = trim( almaden_bookster_get_dashboard_slug(), '/' );
	$url  = home_url( '/' . $slug . '/' );

	if ( empty( $query_args ) ) {
		return $url;
	}

	return add_query_arg( $query_args, $url );
}

function almaden_bookster_get_course_creator_page_url( $query_args = array() ) {
	$slug = trim( almaden_bookster_get_course_creator_slug(), '/' );
	$url  = home_url( '/' . $slug . '/' );

	if ( empty( $query_args ) ) {
		return $url;
	}

	return add_query_arg( $query_args, $url );
}

function almaden_bookster_get_course_archive_page_url( $query_args = array() ) {
	$slug = trim( almaden_bookster_get_course_archive_slug(), '/' );
	$url  = home_url( '/' . $slug . '/' );

	if ( empty( $query_args ) ) {
		return $url;
	}

	return add_query_arg( $query_args, $url );
}

function almaden_bookster_get_store_page_url( $query_args = array() ) {
	$slug = trim( almaden_bookster_get_store_slug(), '/' );
	$url  = home_url( '/' . $slug . '/' );

	if ( empty( $query_args ) ) {
		return $url;
	}

	return add_query_arg( $query_args, $url );
}

function almaden_bookster_user_can_manage_books() {
	return current_user_can( 'almaden_manage_books' ) || current_user_can( 'manage_options' );
}

