<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_pages_settings_defaults() {
	return array(
		'creator_page_id' => 0,
		'creator_slug'    => 'almaden-booklist',
		'creator_title'   => 'Taller',
		'shell_home_page_id' => 0,
		'shell_home_slug'    => 'almaden-home',
		'shell_home_title'   => 'Almaden App',
		'shell_home_menu_enabled' => 0,
		'dashboard_page_id' => 0,
		'dashboard_slug'    => 'dashboard',
		'dashboard_title'   => 'Dashboard',
		'course_creator_page_id' => 0,
		'course_creator_slug'    => 'sala-de-clases',
		'course_creator_title'   => 'Sala de clases',
		'course_archive_page_id' => 0,
		'course_archive_slug'    => 'almaden-cursos',
		'course_archive_title'   => 'Cursos',
		'blog_creator_page_id' => 0,
		'blog_creator_slug'    => 'blog-editor',
		'blog_creator_title'   => 'Blog',
		'authors_page_id' => 0,
		'authors_slug'    => 'autores',
		'authors_title'   => 'Autores',
		'store_page_id'   => 0,
		'store_slug'      => 'bookshelf',
		'store_title'     => 'Ebook Store',
		'store_menu_label' => 'Ebook Store',
		'store_menu_enabled' => 0,
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
	$shell_home_slug  = isset( $raw_settings['shell_home_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['shell_home_slug'] ) ) : ( isset( $current_settings['shell_home_slug'] ) && '' !== $current_settings['shell_home_slug'] ? $current_settings['shell_home_slug'] : $defaults['shell_home_slug'] );
	$shell_home_title = isset( $raw_settings['shell_home_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['shell_home_title'] ) ) : ( isset( $current_settings['shell_home_title'] ) && '' !== $current_settings['shell_home_title'] ? $current_settings['shell_home_title'] : $defaults['shell_home_title'] );
	$shell_home_menu_enabled = ! empty( $raw_settings['shell_home_menu_enabled'] ) ? 1 : 0;
	$dashboard_slug  = isset( $raw_settings['dashboard_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['dashboard_slug'] ) ) : ( isset( $current_settings['dashboard_slug'] ) && '' !== $current_settings['dashboard_slug'] ? $current_settings['dashboard_slug'] : $defaults['dashboard_slug'] );
	$dashboard_title = isset( $raw_settings['dashboard_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['dashboard_title'] ) ) : ( isset( $current_settings['dashboard_title'] ) && '' !== $current_settings['dashboard_title'] ? $current_settings['dashboard_title'] : $defaults['dashboard_title'] );
	$course_creator_slug  = isset( $raw_settings['course_creator_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['course_creator_slug'] ) ) : $defaults['course_creator_slug'];
	$course_creator_title = isset( $raw_settings['course_creator_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['course_creator_title'] ) ) : $defaults['course_creator_title'];
	$course_archive_slug  = isset( $raw_settings['course_archive_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['course_archive_slug'] ) ) : $defaults['course_archive_slug'];
	$course_archive_title = isset( $raw_settings['course_archive_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['course_archive_title'] ) ) : $defaults['course_archive_title'];
	$blog_creator_slug  = isset( $raw_settings['blog_creator_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['blog_creator_slug'] ) ) : $defaults['blog_creator_slug'];
	$blog_creator_title = isset( $raw_settings['blog_creator_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['blog_creator_title'] ) ) : $defaults['blog_creator_title'];
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

	if ( '' === $shell_home_slug ) {
		$shell_home_slug = $defaults['shell_home_slug'];
	}

	if ( '' === $shell_home_title ) {
		$shell_home_title = $defaults['shell_home_title'];
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

	if ( '' === $blog_creator_slug ) {
		$blog_creator_slug = $defaults['blog_creator_slug'];
	}

	if ( '' === $blog_creator_title ) {
		$blog_creator_title = $defaults['blog_creator_title'];
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
		'shell_home_page_id' => isset( $raw_settings['shell_home_page_id'] ) ? absint( $raw_settings['shell_home_page_id'] ) : ( isset( $current_settings['shell_home_page_id'] ) ? absint( $current_settings['shell_home_page_id'] ) : 0 ),
		'shell_home_slug'    => $shell_home_slug,
		'shell_home_title'   => $shell_home_title,
		'shell_home_menu_enabled' => $shell_home_menu_enabled,
		'dashboard_page_id' => isset( $raw_settings['dashboard_page_id'] ) ? absint( $raw_settings['dashboard_page_id'] ) : ( isset( $current_settings['dashboard_page_id'] ) ? absint( $current_settings['dashboard_page_id'] ) : 0 ),
		'dashboard_slug'    => $dashboard_slug,
		'dashboard_title'   => $dashboard_title,
		'course_creator_page_id' => isset( $raw_settings['course_creator_page_id'] ) ? absint( $raw_settings['course_creator_page_id'] ) : 0,
		'course_creator_slug'    => $course_creator_slug,
		'course_creator_title'   => $course_creator_title,
		'course_archive_page_id' => isset( $raw_settings['course_archive_page_id'] ) ? absint( $raw_settings['course_archive_page_id'] ) : 0,
		'course_archive_slug'    => $course_archive_slug,
		'course_archive_title'   => $course_archive_title,
		'blog_creator_page_id' => isset( $raw_settings['blog_creator_page_id'] ) ? absint( $raw_settings['blog_creator_page_id'] ) : 0,
		'blog_creator_slug'    => $blog_creator_slug,
		'blog_creator_title'   => $blog_creator_title,
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

function almaden_bookster_get_shell_home_page_id() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['shell_home_page_id'] ) ? absint( $settings['shell_home_page_id'] ) : 0;
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

function almaden_bookster_get_shell_home_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['shell_home_slug'] ) && '' !== $settings['shell_home_slug'] ? $settings['shell_home_slug'] : 'almaden-home';
}

function almaden_bookster_get_shell_home_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['shell_home_title'] ) && '' !== $settings['shell_home_title'] ? $settings['shell_home_title'] : 'Almaden App';
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
	return isset( $settings['course_creator_slug'] ) && '' !== $settings['course_creator_slug'] ? $settings['course_creator_slug'] : 'sala-de-clases';
}

function almaden_bookster_get_course_creator_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['course_creator_title'] ) && '' !== $settings['course_creator_title'] ? $settings['course_creator_title'] : 'Sala de clases';
}

function almaden_bookster_get_course_archive_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['course_archive_slug'] ) && '' !== $settings['course_archive_slug'] ? $settings['course_archive_slug'] : 'almaden-cursos';
}

function almaden_bookster_get_course_archive_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['course_archive_title'] ) && '' !== $settings['course_archive_title'] ? $settings['course_archive_title'] : 'Cursos';
}

function almaden_bookster_get_blog_creator_page_id() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['blog_creator_page_id'] ) ? absint( $settings['blog_creator_page_id'] ) : 0;
}

function almaden_bookster_get_blog_creator_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['blog_creator_slug'] ) && '' !== $settings['blog_creator_slug'] ? $settings['blog_creator_slug'] : 'blog-editor';
}

function almaden_bookster_get_blog_creator_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['blog_creator_title'] ) && '' !== $settings['blog_creator_title'] ? $settings['blog_creator_title'] : 'Blog';
}

function almaden_bookster_get_blog_creator_page_url( $query_args = array() ) {
	$slug = trim( almaden_bookster_get_blog_creator_slug(), '/' );
	$url  = home_url( '/' . $slug . '/' );

	if ( empty( $query_args ) ) {
		return $url;
	}

	return add_query_arg( $query_args, $url );
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

function almaden_bookster_get_bookshelf_page_id() {
	return almaden_bookster_get_store_page_id();
}

function almaden_bookster_get_store_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['store_slug'] ) && '' !== $settings['store_slug'] ? $settings['store_slug'] : 'bookshelf';
}

function almaden_bookster_get_bookshelf_slug() {
	return almaden_bookster_get_store_slug();
}

function almaden_bookster_get_store_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['store_title'] ) && '' !== $settings['store_title'] ? $settings['store_title'] : 'Ebook Store';
}

function almaden_bookster_get_bookshelf_title() {
	return almaden_bookster_get_store_title();
}

function almaden_bookster_get_store_menu_label() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['store_menu_label'] ) && '' !== $settings['store_menu_label'] ? $settings['store_menu_label'] : 'Ebook Store';
}

function almaden_bookster_get_bookshelf_page_url( $query_args = array() ) {
	return almaden_bookster_get_store_page_url( $query_args );
}

function almaden_bookster_is_store_menu_enabled() {
	$settings = almaden_bookster_get_pages_settings();
	return ! empty( $settings['store_menu_enabled'] );
}

function almaden_bookster_is_shell_home_menu_enabled() {
	$settings = almaden_bookster_get_pages_settings();
	return ! empty( $settings['shell_home_menu_enabled'] );
}

function almaden_bookster_get_quiz_page_settings_defaults() {
	return array(
		'page_id' => 0,
		'slug'    => 'almaden-book-quiz',
		'title'   => 'Book Quiz',
	);
}

function almaden_bookster_get_quiz_page_settings() {
	$saved_settings = get_option( 'almaden_bookster_quiz_page_settings', array() );

	if ( ! is_array( $saved_settings ) ) {
		$saved_settings = array();
	}

	return wp_parse_args( $saved_settings, almaden_bookster_get_quiz_page_settings_defaults() );
}

function almaden_bookster_get_quiz_page_id() {
	$settings = almaden_bookster_get_quiz_page_settings();
	return isset( $settings['page_id'] ) ? absint( $settings['page_id'] ) : 0;
}

function almaden_bookster_get_quiz_page_slug() {
	$settings = almaden_bookster_get_quiz_page_settings();
	return isset( $settings['slug'] ) && '' !== $settings['slug'] ? $settings['slug'] : 'almaden-book-quiz';
}

function almaden_bookster_get_quiz_page_title() {
	$settings = almaden_bookster_get_quiz_page_settings();
	return isset( $settings['title'] ) && '' !== $settings['title'] ? $settings['title'] : 'Book Quiz';
}

function almaden_bookster_get_quiz_page_url() {
	return home_url( '/' . trim( almaden_bookster_get_quiz_page_slug(), '/' ) . '/' );
}

function almaden_bookster_get_publisher_onboarding_page_settings_defaults() {
	return array(
		'page_id' => 0,
		'slug'    => 'crear-editorial',
		'title'   => 'Crear editorial',
	);
}

function almaden_bookster_get_publisher_onboarding_page_settings() {
	$saved_settings = get_option( 'almaden_bookster_publisher_onboarding_page_settings', array() );

	if ( ! is_array( $saved_settings ) ) {
		$saved_settings = array();
	}

	return wp_parse_args( $saved_settings, almaden_bookster_get_publisher_onboarding_page_settings_defaults() );
}

function almaden_bookster_get_publisher_onboarding_page_id() {
	$settings = almaden_bookster_get_publisher_onboarding_page_settings();
	return isset( $settings['page_id'] ) ? absint( $settings['page_id'] ) : 0;
}

function almaden_bookster_get_publisher_onboarding_page_slug() {
	$settings = almaden_bookster_get_publisher_onboarding_page_settings();
	return isset( $settings['slug'] ) && '' !== $settings['slug'] ? $settings['slug'] : 'crear-editorial';
}

function almaden_bookster_get_publisher_onboarding_page_title() {
	$settings = almaden_bookster_get_publisher_onboarding_page_settings();
	return isset( $settings['title'] ) && '' !== $settings['title'] ? $settings['title'] : 'Crear editorial';
}

function almaden_bookster_get_publisher_onboarding_page_url() {
	return home_url( '/' . trim( almaden_bookster_get_publisher_onboarding_page_slug(), '/' ) . '/' );
}

function almaden_bookster_get_page_sync_state( $page_id, $slug, $title = '' ) {
	$page_id = absint( $page_id );
	$slug    = sanitize_title( (string) $slug );
	$title   = sanitize_text_field( (string) $title );
	$page    = null;
	$source  = '';

	if ( $page_id > 0 ) {
		$candidate = get_post( $page_id );
		if ( $candidate && 'page' === $candidate->post_type ) {
			$page   = $candidate;
			$source = 'id';
		}
	}

	if ( ! $page && '' !== $slug ) {
		$candidate = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $candidate && 'page' === $candidate->post_type ) {
			$page   = $candidate;
			$source = 'slug';
		}
	}

	$status = 'missing';
	$status_label = __( 'No encontrada', 'almaden-bookster' );

	if ( $page instanceof WP_Post ) {
		if ( 'id' === $source ) {
			$status = 'found-id';
			$status_label = __( 'Detectada por ID', 'almaden-bookster' );
		} elseif ( 'slug' === $source ) {
			$status = 'found-slug';
			$status_label = __( 'Detectada por slug', 'almaden-bookster' );
		} else {
			$status = 'found';
			$status_label = __( 'Detectada', 'almaden-bookster' );
		}
	}

	return array(
		'page'         => $page instanceof WP_Post ? $page : null,
		'source'       => $source,
		'status'       => $status,
		'label'        => $status_label,
		'exists'       => $page instanceof WP_Post,
		'page_id'      => $page instanceof WP_Post ? (int) $page->ID : 0,
		'page_title'   => $page instanceof WP_Post ? (string) $page->post_title : '',
		'page_slug'    => $page instanceof WP_Post ? (string) $page->post_name : '',
		'current_url'  => $page instanceof WP_Post ? get_permalink( $page->ID ) : home_url( '/' . trim( $slug, '/' ) . '/' ),
		'requested_slug' => $slug,
		'requested_title' => $title,
	);
}

function almaden_bookster_user_can_manage_courses() {
	return current_user_can( 'manage_options' ) || current_user_can( 'manage_almaden_learni' ) || current_user_can( 'edit_posts' );
}

function almaden_bookster_get_frontend_page_access_mode( $page_key ) {
	$page_key = sanitize_key( (string) $page_key );

	$access_map = array(
		'shell_home'     => 'public',
		'dashboard'      => 'public',
		'authors'        => 'public',
		'publisher'      => 'public',
		'store'          => 'public',
		'creator'        => 'private',
		'course_creator' => 'private',
		'course_archive' => 'private',
		'blog_creator'   => 'private',
	);

	return isset( $access_map[ $page_key ] ) ? $access_map[ $page_key ] : 'private';
}

function almaden_bookster_user_can_access_frontend_page( $page_key, $user_id = null ) {
	$page_key = sanitize_key( (string) $page_key );
	$mode     = almaden_bookster_get_frontend_page_access_mode( $page_key );

	if ( 'public' === $mode ) {
		return true;
	}

	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $user_id <= 0 ) {
		return false;
	}

	switch ( $page_key ) {
		case 'creator':
			return function_exists( 'almaden_bookster_user_can_manage_books' ) ? almaden_bookster_user_can_manage_books() : ( current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' ) );
		case 'course_creator':
			return function_exists( 'almaden_bookster_user_can_manage_courses' ) ? almaden_bookster_user_can_manage_courses() : ( current_user_can( 'manage_options' ) || current_user_can( 'manage_almaden_learni' ) || current_user_can( 'edit_posts' ) );
		case 'course_archive':
			return true;
		case 'blog_creator':
			return current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' );
		default:
			return current_user_can( 'manage_options' );
	}
}

function almaden_bookster_get_creator_page_url( $query_args = array() ) {
	$slug = trim( almaden_bookster_get_creator_slug(), '/' );
	$url  = home_url( '/' . $slug . '/' );

	if ( empty( $query_args ) ) {
		return $url;
	}

	return add_query_arg( $query_args, $url );
}

function almaden_bookster_get_shell_home_page_url( $query_args = array() ) {
	$slug = trim( almaden_bookster_get_shell_home_slug(), '/' );
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
