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

function almaden_bookster_sync_store_page() {
	$settings = almaden_bookster_get_pages_settings();
	$slug     = almaden_bookster_get_store_slug();
	$title    = almaden_bookster_get_store_title();
	$page_id  = isset( $settings['store_page_id'] ) ? absint( $settings['store_page_id'] ) : 0;
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
			$settings['store_page_id'] = absint( $new_page_id );
			$settings['store_slug']    = $slug;
			$settings['store_title']   = $title;
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
		$settings['store_page_id'] = (int) $page->ID;
		$settings['store_slug']    = $slug;
		$settings['store_title']   = $title;
		update_option( 'almaden_bookster_pages_settings', $settings );
	}
}

