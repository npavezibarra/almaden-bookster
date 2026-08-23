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
		'contractor_page_id' => 0,
		'contractor_slug'    => 'contractor',
		'contractor_title'   => 'Contractor',
		'user_access_manager_page_id' => 0,
		'user_access_manager_slug'    => 'user-access',
		'user_access_manager_title'   => 'User Access',
		'dashboard_page_id' => 0,
		'dashboard_slug'    => 'dashboard',
		'dashboard_title'   => 'Dashboard',
		'reading_stats_page_id' => 0,
		'reading_stats_slug'    => 'my-reading-stats',
		'reading_stats_title'   => 'My Reading Stats',
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

function almaden_bookster_get_custom_pages_settings() {
	$saved_settings = get_option( 'almaden_bookster_custom_pages_settings', array() );

	if ( ! is_array( $saved_settings ) ) {
		return array();
	}

	$custom_pages = array();
	foreach ( $saved_settings as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$slot_key = isset( $item['slot_key'] ) ? sanitize_key( (string) $item['slot_key'] ) : '';
		if ( '' === $slot_key ) {
			continue;
		}

		$custom_pages[] = array(
			'slot_key'   => $slot_key,
			'page_id'    => isset( $item['page_id'] ) ? absint( $item['page_id'] ) : 0,
			'title'      => isset( $item['title'] ) ? sanitize_text_field( wp_unslash( $item['title'] ) ) : '',
			'slug'       => isset( $item['slug'] ) ? sanitize_title( wp_unslash( $item['slug'] ) ) : '',
			'page_type'  => isset( $item['page_type'] ) ? ( 'regular' === sanitize_key( (string) $item['page_type'] ) ? 'regular' : 'shell' ) : 'shell',
			'admin_only' => ! empty( $item['admin_only'] ) ? 1 : 0,
		);
	}

	return $custom_pages;
}

function almaden_bookster_get_custom_page_settings_by_slot( $slot_key ) {
	$slot_key = sanitize_key( (string) $slot_key );

	foreach ( almaden_bookster_get_custom_pages_settings() as $item ) {
		if ( isset( $item['slot_key'] ) && $slot_key === (string) $item['slot_key'] ) {
			return $item;
		}
	}

	return array();
}

function almaden_bookster_sanitize_custom_pages_settings( $raw_pages ) {
	if ( ! is_array( $raw_pages ) ) {
		return array();
	}

	$custom_pages = array();
	foreach ( $raw_pages as $raw_key => $raw_page ) {
		if ( ! is_array( $raw_page ) ) {
			continue;
		}

		$slot_key = isset( $raw_page['slot_key'] ) ? sanitize_key( (string) wp_unslash( $raw_page['slot_key'] ) ) : sanitize_key( (string) $raw_key );
		if ( '' === $slot_key ) {
			continue;
		}

		$title = isset( $raw_page['title'] ) ? sanitize_text_field( wp_unslash( $raw_page['title'] ) ) : '';
		$slug  = isset( $raw_page['slug'] ) ? sanitize_title( wp_unslash( $raw_page['slug'] ) ) : '';
		$page_type = isset( $raw_page['page_type'] ) ? sanitize_key( (string) wp_unslash( $raw_page['page_type'] ) ) : 'shell';
		if ( ! in_array( $page_type, array( 'shell', 'regular' ), true ) ) {
			$page_type = 'shell';
		}

		$custom_pages[] = array(
			'slot_key'   => $slot_key,
			'page_id'    => isset( $raw_page['page_id'] ) ? absint( $raw_page['page_id'] ) : 0,
			'title'      => $title,
			'slug'       => $slug,
			'page_type'  => $page_type,
			'admin_only' => ! empty( $raw_page['admin_only'] ) ? 1 : 0,
		);
	}

	return $custom_pages;
}

function almaden_bookster_get_custom_page_url( $slug ) {
	$slug = trim( (string) $slug, '/' );

	if ( '' === $slug ) {
		return '';
	}

	return home_url( '/' . $slug . '/' );
}

function almaden_bookster_sanitize_pages_settings( $raw_settings ) {
	$defaults = almaden_bookster_get_pages_settings_defaults();
	$current_settings = almaden_bookster_get_pages_settings();
	$slug     = isset( $raw_settings['creator_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['creator_slug'] ) ) : $defaults['creator_slug'];
	$title    = isset( $raw_settings['creator_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['creator_title'] ) ) : $defaults['creator_title'];
	$shell_home_slug  = isset( $raw_settings['shell_home_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['shell_home_slug'] ) ) : ( isset( $current_settings['shell_home_slug'] ) && '' !== $current_settings['shell_home_slug'] ? $current_settings['shell_home_slug'] : $defaults['shell_home_slug'] );
	$shell_home_title = isset( $raw_settings['shell_home_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['shell_home_title'] ) ) : ( isset( $current_settings['shell_home_title'] ) && '' !== $current_settings['shell_home_title'] ? $current_settings['shell_home_title'] : $defaults['shell_home_title'] );
	$shell_home_menu_enabled = ! empty( $raw_settings['shell_home_menu_enabled'] ) ? 1 : 0;
	$contractor_slug  = isset( $raw_settings['contractor_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['contractor_slug'] ) ) : ( isset( $current_settings['contractor_slug'] ) && '' !== $current_settings['contractor_slug'] ? $current_settings['contractor_slug'] : $defaults['contractor_slug'] );
	$contractor_title = isset( $raw_settings['contractor_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['contractor_title'] ) ) : ( isset( $current_settings['contractor_title'] ) && '' !== $current_settings['contractor_title'] ? $current_settings['contractor_title'] : $defaults['contractor_title'] );
	$user_access_manager_slug = isset( $raw_settings['user_access_manager_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['user_access_manager_slug'] ) ) : ( isset( $current_settings['user_access_manager_slug'] ) && '' !== $current_settings['user_access_manager_slug'] ? $current_settings['user_access_manager_slug'] : $defaults['user_access_manager_slug'] );
	$user_access_manager_title = isset( $raw_settings['user_access_manager_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['user_access_manager_title'] ) ) : ( isset( $current_settings['user_access_manager_title'] ) && '' !== $current_settings['user_access_manager_title'] ? $current_settings['user_access_manager_title'] : $defaults['user_access_manager_title'] );
	$dashboard_slug  = isset( $raw_settings['dashboard_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['dashboard_slug'] ) ) : ( isset( $current_settings['dashboard_slug'] ) && '' !== $current_settings['dashboard_slug'] ? $current_settings['dashboard_slug'] : $defaults['dashboard_slug'] );
	$dashboard_title = isset( $raw_settings['dashboard_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['dashboard_title'] ) ) : ( isset( $current_settings['dashboard_title'] ) && '' !== $current_settings['dashboard_title'] ? $current_settings['dashboard_title'] : $defaults['dashboard_title'] );
	$reading_stats_slug  = isset( $raw_settings['reading_stats_slug'] ) ? sanitize_title( wp_unslash( $raw_settings['reading_stats_slug'] ) ) : ( isset( $current_settings['reading_stats_slug'] ) && '' !== $current_settings['reading_stats_slug'] ? $current_settings['reading_stats_slug'] : $defaults['reading_stats_slug'] );
	$reading_stats_title = isset( $raw_settings['reading_stats_title'] ) ? sanitize_text_field( wp_unslash( $raw_settings['reading_stats_title'] ) ) : ( isset( $current_settings['reading_stats_title'] ) && '' !== $current_settings['reading_stats_title'] ? $current_settings['reading_stats_title'] : $defaults['reading_stats_title'] );
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

	if ( '' === $contractor_slug ) {
		$contractor_slug = $defaults['contractor_slug'];
	}

	if ( '' === $contractor_title ) {
		$contractor_title = $defaults['contractor_title'];
	}
	if ( '' === $user_access_manager_slug ) { $user_access_manager_slug = $defaults['user_access_manager_slug']; }
	if ( '' === $user_access_manager_title ) { $user_access_manager_title = $defaults['user_access_manager_title']; }

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

	if ( '' === $reading_stats_slug ) {
		$reading_stats_slug = $defaults['reading_stats_slug'];
	}

	if ( '' === $reading_stats_title ) {
		$reading_stats_title = $defaults['reading_stats_title'];
	}

	return array(
		'creator_page_id' => isset( $raw_settings['creator_page_id'] ) ? absint( $raw_settings['creator_page_id'] ) : 0,
		'creator_slug'    => $slug,
		'creator_title'   => $title,
		'shell_home_page_id' => isset( $raw_settings['shell_home_page_id'] ) ? absint( $raw_settings['shell_home_page_id'] ) : ( isset( $current_settings['shell_home_page_id'] ) ? absint( $current_settings['shell_home_page_id'] ) : 0 ),
		'shell_home_slug'    => $shell_home_slug,
		'shell_home_title'   => $shell_home_title,
		'shell_home_menu_enabled' => $shell_home_menu_enabled,
		'contractor_page_id' => isset( $raw_settings['contractor_page_id'] ) ? absint( $raw_settings['contractor_page_id'] ) : ( isset( $current_settings['contractor_page_id'] ) ? absint( $current_settings['contractor_page_id'] ) : 0 ),
		'contractor_slug'    => $contractor_slug,
		'contractor_title'   => $contractor_title,
		'user_access_manager_page_id' => isset( $raw_settings['user_access_manager_page_id'] ) ? absint( $raw_settings['user_access_manager_page_id'] ) : ( isset( $current_settings['user_access_manager_page_id'] ) ? absint( $current_settings['user_access_manager_page_id'] ) : 0 ),
		'user_access_manager_slug'    => $user_access_manager_slug,
		'user_access_manager_title'   => $user_access_manager_title,
		'dashboard_page_id' => isset( $raw_settings['dashboard_page_id'] ) ? absint( $raw_settings['dashboard_page_id'] ) : ( isset( $current_settings['dashboard_page_id'] ) ? absint( $current_settings['dashboard_page_id'] ) : 0 ),
		'dashboard_slug'    => $dashboard_slug,
		'dashboard_title'   => $dashboard_title,
		'reading_stats_page_id' => isset( $raw_settings['reading_stats_page_id'] ) ? absint( $raw_settings['reading_stats_page_id'] ) : ( isset( $current_settings['reading_stats_page_id'] ) ? absint( $current_settings['reading_stats_page_id'] ) : 0 ),
		'reading_stats_slug'    => $reading_stats_slug,
		'reading_stats_title'   => $reading_stats_title,
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

function almaden_bookster_get_contractor_page_id() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['contractor_page_id'] ) ? absint( $settings['contractor_page_id'] ) : 0;
}

function almaden_bookster_get_contractor_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['contractor_slug'] ) && '' !== $settings['contractor_slug'] ? $settings['contractor_slug'] : 'contractor';
}

function almaden_bookster_get_contractor_title() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['contractor_title'] ) && '' !== $settings['contractor_title'] ? $settings['contractor_title'] : 'Contractor';
}

function almaden_bookster_get_contractor_page_url( $query_args = array() ) {
	$slug = trim( almaden_bookster_get_contractor_slug(), '/' );
	$url  = home_url( '/' . $slug . '/' );

	if ( empty( $query_args ) ) {
		return $url;
	}

	return add_query_arg( $query_args, $url );
}

function almaden_bookster_get_user_access_manager_page_id() {
	$settings = almaden_bookster_get_pages_settings();
	return isset( $settings['user_access_manager_page_id'] ) ? absint( $settings['user_access_manager_page_id'] ) : 0;
}

function almaden_bookster_get_user_access_manager_slug() {
	$settings = almaden_bookster_get_pages_settings();
	return ! empty( $settings['user_access_manager_slug'] ) ? sanitize_title( $settings['user_access_manager_slug'] ) : 'user-access';
}

function almaden_bookster_get_user_access_manager_title() {
	$settings = almaden_bookster_get_pages_settings();
	return ! empty( $settings['user_access_manager_title'] ) ? (string) $settings['user_access_manager_title'] : 'User Access';
}

function almaden_bookster_get_user_access_manager_page_url( $query_args = array() ) {
	$url = home_url( '/' . trim( almaden_bookster_get_user_access_manager_slug(), '/' ) . '/' );
	return empty( $query_args ) ? $url : add_query_arg( $query_args, $url );
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

function almaden_bookster_get_contractor_settings_defaults() {
	return array(
		'company_name' => 'almaden',
		'logo_id'      => 0,
		'logo_width'   => 160,
	);
}

function almaden_bookster_get_contractor_settings() {
	$saved_settings = get_option( 'almaden_bookster_contractor_settings', array() );
	if ( ! is_array( $saved_settings ) ) {
		$saved_settings = array();
	}

	return wp_parse_args( $saved_settings, almaden_bookster_get_contractor_settings_defaults() );
}

function almaden_bookster_sanitize_contractor_settings( $raw_settings ) {
	$defaults = almaden_bookster_get_contractor_settings_defaults();
	$current_settings = almaden_bookster_get_contractor_settings();
	$company_name = isset( $raw_settings['company_name'] )
		? sanitize_text_field( wp_unslash( $raw_settings['company_name'] ) )
		: ( isset( $current_settings['company_name'] ) ? $current_settings['company_name'] : $defaults['company_name'] );
	$logo_width = isset( $raw_settings['logo_width'] )
		? absint( $raw_settings['logo_width'] )
		: ( isset( $current_settings['logo_width'] ) ? absint( $current_settings['logo_width'] ) : (int) $defaults['logo_width'] );
	if ( $logo_width < 40 ) {
		$logo_width = 40;
	}
	if ( $logo_width > 300 ) {
		$logo_width = 300;
	}

	return array(
		'company_name' => $company_name,
		'logo_id'      => isset( $raw_settings['logo_id'] ) ? absint( $raw_settings['logo_id'] ) : ( isset( $current_settings['logo_id'] ) ? absint( $current_settings['logo_id'] ) : 0 ),
		'logo_width'   => $logo_width,
	);
}

function almaden_bookster_get_contractor_company_name() {
	$settings = almaden_bookster_get_contractor_settings();
	return isset( $settings['company_name'] ) ? (string) $settings['company_name'] : '';
}

function almaden_bookster_get_contractor_logo_id() {
	$settings = almaden_bookster_get_contractor_settings();
	return isset( $settings['logo_id'] ) ? absint( $settings['logo_id'] ) : 0;
}

function almaden_bookster_get_contractor_logo_url() {
	$logo_id = almaden_bookster_get_contractor_logo_id();
	if ( $logo_id <= 0 ) {
		return '';
	}

	return function_exists( 'wp_get_attachment_image_url' ) ? (string) wp_get_attachment_image_url( $logo_id, 'full' ) : '';
}

function almaden_bookster_get_contractor_logo_width() {
	$settings = almaden_bookster_get_contractor_settings();
	$width = isset( $settings['logo_width'] ) ? absint( $settings['logo_width'] ) : 0;
	if ( $width < 40 ) {
		$width = 40;
	}
	if ( $width > 300 ) {
		$width = 300;
	}

	return $width;
}

function almaden_bookster_handle_contractor_settings_save() {
	if ( ! function_exists( 'almaden_bookster_user_can_manage_books' ) || ! almaden_bookster_user_can_manage_books() ) {
		wp_die( 'Permisos insuficientes.' );
	}

	if ( ! isset( $_POST['almaden_contractor_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_contractor_nonce'], 'almaden_bookster_contractor_settings' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$settings = almaden_bookster_sanitize_contractor_settings(
		array(
			'company_name' => isset( $_POST['company_name'] ) ? wp_unslash( $_POST['company_name'] ) : '',
			'logo_id'      => isset( $_POST['logo_id'] ) ? absint( $_POST['logo_id'] ) : 0,
			'logo_width'   => isset( $_POST['logo_width'] ) ? absint( $_POST['logo_width'] ) : 0,
		)
	);

	update_option( 'almaden_bookster_contractor_settings', $settings );

	$redirect_url = function_exists( 'almaden_bookster_get_contractor_page_url' ) ? almaden_bookster_get_contractor_page_url( array( 'settings-updated' => '1' ) ) : admin_url( 'admin.php' );
	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_post_almaden_bookster_save_contractor_settings', 'almaden_bookster_handle_contractor_settings_save' );

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
