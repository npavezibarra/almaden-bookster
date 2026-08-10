<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_sync_creator_page() {
	$settings = almaden_bookster_get_pages_settings();
	$slug     = almaden_bookster_get_creator_slug();
	$title    = almaden_bookster_get_creator_title();
	$page_id  = isset( $settings['creator_page_id'] ) ? absint( $settings['creator_page_id'] ) : 0;
	$page     = $page_id > 0 ? get_post( $page_id ) : null;

	if ( $page && 'page' !== $page->post_type ) {
		$page = null;
	}

	if ( ! $page ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
	}

	if ( ! $page ) {
		$new_page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
			)
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
			$settings['creator_page_id'] = absint( $new_page_id );
			$settings['creator_slug']    = $slug;
			$settings['creator_title']   = $title;
			update_option( 'almaden_bookster_pages_settings', $settings );
		}

		return;
	}

	$updates = array( 'ID' => $page->ID );

	if ( $page->post_name !== $slug ) {
		$updates['post_name'] = $slug;
	}

	if ( $page->post_title !== $title ) {
		$updates['post_title'] = $title;
	}

	if ( count( $updates ) > 1 ) {
		wp_update_post( $updates );
	}

	if ( $page_id !== (int) $page->ID ) {
		$settings['creator_page_id'] = (int) $page->ID;
		$settings['creator_slug']    = $slug;
		$settings['creator_title']   = $title;
		update_option( 'almaden_bookster_pages_settings', $settings );
	}
}

function almaden_bookster_sync_shell_home_page() {
	$settings = almaden_bookster_get_pages_settings();
	$slug     = almaden_bookster_get_shell_home_slug();
	$title    = almaden_bookster_get_shell_home_title();
	$page_id  = isset( $settings['shell_home_page_id'] ) ? absint( $settings['shell_home_page_id'] ) : 0;
	$page     = $page_id > 0 ? get_post( $page_id ) : null;

	if ( $page && 'page' !== $page->post_type ) {
		$page = null;
	}

	if ( ! $page ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
	}

	if ( ! $page ) {
		$new_page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
			)
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
			$settings['shell_home_page_id'] = absint( $new_page_id );
			$settings['shell_home_slug']    = $slug;
			$settings['shell_home_title']   = $title;
			update_option( 'almaden_bookster_pages_settings', $settings );
		}

		return;
	}

	$updates = array( 'ID' => $page->ID );

	if ( $page->post_name !== $slug ) {
		$updates['post_name'] = $slug;
	}

	if ( $page->post_title !== $title ) {
		$updates['post_title'] = $title;
	}

	if ( count( $updates ) > 1 ) {
		wp_update_post( $updates );
	}

	if ( $page_id !== (int) $page->ID ) {
		$settings['shell_home_page_id'] = (int) $page->ID;
		$settings['shell_home_slug']    = $slug;
		$settings['shell_home_title']   = $title;
		update_option( 'almaden_bookster_pages_settings', $settings );
	}
}

function almaden_bookster_sync_dashboard_page() {
	$settings = almaden_bookster_get_pages_settings();
	$slug     = almaden_bookster_get_dashboard_slug();
	$title    = almaden_bookster_get_dashboard_title();
	$page_id  = isset( $settings['dashboard_page_id'] ) ? absint( $settings['dashboard_page_id'] ) : 0;
	$page     = $page_id > 0 ? get_post( $page_id ) : null;

	if ( $page && 'page' !== $page->post_type ) {
		$page = null;
	}

	if ( ! $page ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
	}

	if ( ! $page ) {
		$new_page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			)
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
			$settings['dashboard_page_id'] = absint( $new_page_id );
			$settings['dashboard_slug']    = $slug;
			$settings['dashboard_title']   = $title;
			update_option( 'almaden_bookster_pages_settings', $settings );
		}

		return;
	}

	$updates = array( 'ID' => $page->ID );

	if ( $page->post_name !== $slug ) {
		$updates['post_name'] = $slug;
	}

	if ( $page->post_title !== $title ) {
		$updates['post_title'] = $title;
	}

	if ( count( $updates ) > 1 ) {
		wp_update_post( $updates );
	}

	if ( $page_id !== (int) $page->ID ) {
		$settings['dashboard_page_id'] = (int) $page->ID;
		$settings['dashboard_slug']    = $slug;
		$settings['dashboard_title']   = $title;
		update_option( 'almaden_bookster_pages_settings', $settings );
	}
}

function almaden_bookster_sync_course_creator_page() {
	$settings = almaden_bookster_get_pages_settings();
	$slug     = almaden_bookster_get_course_creator_slug();
	$title    = almaden_bookster_get_course_creator_title();
	$page_id  = isset( $settings['course_creator_page_id'] ) ? absint( $settings['course_creator_page_id'] ) : 0;
	$page     = $page_id > 0 ? get_post( $page_id ) : null;

	if ( $page && 'page' !== $page->post_type ) {
		$page = null;
	}

	if ( ! $page ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
	}

	if ( ! $page ) {
		$new_page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
			)
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
			$settings['course_creator_page_id'] = absint( $new_page_id );
			$settings['course_creator_slug']    = $slug;
			$settings['course_creator_title']   = $title;
			update_option( 'almaden_bookster_pages_settings', $settings );
		}

		return;
	}

	$updates = array( 'ID' => $page->ID );

	if ( $page->post_name !== $slug ) {
		$updates['post_name'] = $slug;
	}

	if ( $page->post_title !== $title ) {
		$updates['post_title'] = $title;
	}

	if ( count( $updates ) > 1 ) {
		wp_update_post( $updates );
	}

	if ( $page_id !== (int) $page->ID ) {
		$settings['course_creator_page_id'] = (int) $page->ID;
		$settings['course_creator_slug']    = $slug;
		$settings['course_creator_title']   = $title;
		update_option( 'almaden_bookster_pages_settings', $settings );
	}
}

function almaden_bookster_sync_course_archive_page() {
	$settings = almaden_bookster_get_pages_settings();
	$slug     = almaden_bookster_get_course_archive_slug();
	$title    = almaden_bookster_get_course_archive_title();
	$page_id  = isset( $settings['course_archive_page_id'] ) ? absint( $settings['course_archive_page_id'] ) : 0;
	$page     = $page_id > 0 ? get_post( $page_id ) : null;

	if ( $page && 'page' !== $page->post_type ) {
		$page = null;
	}

	if ( ! $page ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
	}

	if ( ! $page ) {
		$new_page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
			)
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
			$settings['course_archive_page_id'] = absint( $new_page_id );
			$settings['course_archive_slug']    = $slug;
			$settings['course_archive_title']   = $title;
			update_option( 'almaden_bookster_pages_settings', $settings );
		}

		return;
	}

	$updates = array( 'ID' => $page->ID );

	if ( $page->post_name !== $slug ) {
		$updates['post_name'] = $slug;
	}

	if ( $page->post_title !== $title ) {
		$updates['post_title'] = $title;
	}

	if ( count( $updates ) > 1 ) {
		wp_update_post( $updates );
	}

	if ( $page_id !== (int) $page->ID ) {
		$settings['course_archive_page_id'] = (int) $page->ID;
		$settings['course_archive_slug']    = $slug;
		$settings['course_archive_title']   = $title;
		update_option( 'almaden_bookster_pages_settings', $settings );
	}
}

function almaden_bookster_sync_blog_creator_page() {
	$settings = almaden_bookster_get_pages_settings();
	$slug     = almaden_bookster_get_blog_creator_slug();
	$title    = almaden_bookster_get_blog_creator_title();
	$page_id  = isset( $settings['blog_creator_page_id'] ) ? absint( $settings['blog_creator_page_id'] ) : 0;
	$page     = $page_id > 0 ? get_post( $page_id ) : null;

	if ( $page && 'page' !== $page->post_type ) {
		$page = null;
	}

	if ( ! $page ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
	}

	if ( ! $page ) {
		$new_page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
			)
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
			$settings['blog_creator_page_id'] = absint( $new_page_id );
			$settings['blog_creator_slug']    = $slug;
			$settings['blog_creator_title']   = $title;
			update_option( 'almaden_bookster_pages_settings', $settings );
		}

		return;
	}

	$updates = array( 'ID' => $page->ID );

	if ( $page->post_name !== $slug ) {
		$updates['post_name'] = $slug;
	}

	if ( $page->post_title !== $title ) {
		$updates['post_title'] = $title;
	}

	if ( count( $updates ) > 1 ) {
		wp_update_post( $updates );
	}

	if ( $page_id !== (int) $page->ID ) {
		$settings['blog_creator_page_id'] = (int) $page->ID;
		$settings['blog_creator_slug']    = $slug;
		$settings['blog_creator_title']   = $title;
		update_option( 'almaden_bookster_pages_settings', $settings );
	}
}

function almaden_bookster_sync_authors_page() {
	$settings = almaden_bookster_get_pages_settings();
	$slug     = almaden_bookster_get_authors_slug();
	$title    = almaden_bookster_get_authors_title();
	$page_id  = isset( $settings['authors_page_id'] ) ? absint( $settings['authors_page_id'] ) : 0;
	$page     = $page_id > 0 ? get_post( $page_id ) : null;

	if ( $page && 'page' !== $page->post_type ) {
		$page = null;
	}

	if ( ! $page ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
	}

	if ( ! $page ) {
		$new_page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
			)
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
			$settings['authors_page_id'] = absint( $new_page_id );
			$settings['authors_slug']    = $slug;
			$settings['authors_title']   = $title;
			update_option( 'almaden_bookster_pages_settings', $settings );
		}

		return;
	}

	$updates = array( 'ID' => $page->ID );

	if ( $page->post_name !== $slug ) {
		$updates['post_name'] = $slug;
	}

	if ( $page->post_title !== $title ) {
		$updates['post_title'] = $title;
	}

	if ( count( $updates ) > 1 ) {
		wp_update_post( $updates );
	}

	if ( $page_id !== (int) $page->ID ) {
		$settings['authors_page_id'] = (int) $page->ID;
		$settings['authors_slug']    = $slug;
		$settings['authors_title']   = $title;
		update_option( 'almaden_bookster_pages_settings', $settings );
	}
}

function almaden_bookster_sync_store_page( $force_create = false ) {
	$settings = almaden_bookster_get_pages_settings();
	$slug     = almaden_bookster_get_store_slug();
	$title    = almaden_bookster_get_store_title();
	$page_id  = isset( $settings['store_page_id'] ) ? absint( $settings['store_page_id'] ) : 0;
	$page_policy = function_exists( 'almaden_bookster_get_bookshelf_page_policy' ) ? almaden_bookster_get_bookshelf_page_policy() : 'auto_create';
	$auto_create_enabled = function_exists( 'almaden_bookster_should_auto_create_bookshelf_page' ) ? almaden_bookster_should_auto_create_bookshelf_page() : true;
	$page     = $page_id > 0 ? get_post( $page_id ) : null;

	if ( 'disabled' === $page_policy && ! $force_create ) {
		return;
	}

	if ( $page && 'page' !== $page->post_type ) {
		$page = null;
	}

	if ( ! $page ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
	}

	if ( ! $page ) {
		if ( ! $force_create && ! $auto_create_enabled ) {
			return;
		}

		$new_page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
			)
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
			$settings['store_page_id'] = absint( $new_page_id );
			$settings['store_slug']    = $slug;
			$settings['store_title']   = $title;
			update_option( 'almaden_bookster_pages_settings', $settings );
			update_post_meta( (int) $new_page_id, '_almaden_bookster_render_template', 'templates/bookshelf/bookshelf-app.php' );
		}

		return;
	}

	$updates = array( 'ID' => $page->ID );

	if ( $page->post_name !== $slug ) {
		$updates['post_name'] = $slug;
	}

	if ( $page->post_title !== $title ) {
		$updates['post_title'] = $title;
	}

	if ( count( $updates ) > 1 ) {
		wp_update_post( $updates );
	}

	update_post_meta( (int) $page->ID, '_almaden_bookster_render_template', 'templates/bookshelf/bookshelf-app.php' );

	if ( $page_id !== (int) $page->ID ) {
		$settings['store_page_id'] = (int) $page->ID;
		$settings['store_slug']    = $slug;
		$settings['store_title']   = $title;
		update_option( 'almaden_bookster_pages_settings', $settings );
	}
}

function almaden_bookster_sync_bookshelf_page() {
	almaden_bookster_sync_store_page();
}

function almaden_bookster_sync_quiz_page() {
	$settings = function_exists( 'almaden_bookster_get_quiz_page_settings' ) ? almaden_bookster_get_quiz_page_settings() : array();
	$slug     = function_exists( 'almaden_bookster_get_quiz_page_slug' ) ? almaden_bookster_get_quiz_page_slug() : 'almaden-book-quiz';
	$title    = function_exists( 'almaden_bookster_get_quiz_page_title' ) ? almaden_bookster_get_quiz_page_title() : 'Book Quiz';
	$page_id  = isset( $settings['page_id'] ) ? absint( $settings['page_id'] ) : 0;
	$page     = $page_id > 0 ? get_post( $page_id ) : null;

	if ( $page && 'page' !== $page->post_type ) {
		$page = null;
	}

	if ( ! $page ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
	}

	if ( ! $page ) {
		$new_page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
			)
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id && is_array( $settings ) ) {
			$settings['page_id'] = absint( $new_page_id );
			$settings['slug']    = $slug;
			$settings['title']   = $title;
			update_option( 'almaden_bookster_quiz_page_settings', $settings );
		}

		return;
	}

	$updates = array( 'ID' => $page->ID );

	if ( $page->post_name !== $slug ) {
		$updates['post_name'] = $slug;
	}

	if ( $page->post_title !== $title ) {
		$updates['post_title'] = $title;
	}

	if ( count( $updates ) > 1 ) {
		wp_update_post( $updates );
	}

	if ( $page_id !== (int) $page->ID && is_array( $settings ) ) {
		$settings['page_id'] = (int) $page->ID;
		$settings['slug']    = $slug;
		$settings['title']   = $title;
		update_option( 'almaden_bookster_quiz_page_settings', $settings );
	}
}
