<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_publishers_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'almaden_publishers';
}

function almaden_bookster_get_publisher_members_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'almaden_publisher_members';
}

function almaden_bookster_get_publisher_page_settings_defaults() {
	return array(
		'page_id' => 0,
		'slug'    => 'editorial',
		'title'   => 'Editorial',
	);
}

function almaden_bookster_get_publisher_page_settings() {
	$saved_settings = get_option( 'almaden_bookster_publisher_page_settings', array() );

	if ( ! is_array( $saved_settings ) ) {
		$saved_settings = array();
	}

	return wp_parse_args( $saved_settings, almaden_bookster_get_publisher_page_settings_defaults() );
}

function almaden_bookster_get_publisher_page_id() {
	$settings = almaden_bookster_get_publisher_page_settings();
	return isset( $settings['page_id'] ) ? absint( $settings['page_id'] ) : 0;
}

function almaden_bookster_get_publisher_page_slug() {
	$settings = almaden_bookster_get_publisher_page_settings();
	return isset( $settings['slug'] ) && '' !== $settings['slug'] ? $settings['slug'] : 'editorial';
}

function almaden_bookster_get_publisher_page_title() {
	$settings = almaden_bookster_get_publisher_page_settings();
	return isset( $settings['title'] ) && '' !== $settings['title'] ? $settings['title'] : 'Editorial';
}

function almaden_bookster_get_publisher_page_url( $publisher_slug = '' ) {
	$base_slug = trim( almaden_bookster_get_publisher_page_slug(), '/' );
	$base_url  = home_url( '/' . $base_slug . '/' );

	if ( '' === trim( (string) $publisher_slug ) ) {
		return $base_url;
	}

	return trailingslashit( $base_url . sanitize_title( $publisher_slug ) );
}

function almaden_bookster_get_book_publisher_meta_key() {
	return '_almaden_publisher_id';
}

function almaden_bookster_sanitize_publisher_data( $raw_publisher ) {
	$raw_publisher = is_array( $raw_publisher ) ? $raw_publisher : array();

	return array(
		'slug'        => isset( $raw_publisher['slug'] ) ? sanitize_title( wp_unslash( $raw_publisher['slug'] ) ) : '',
		'name'        => isset( $raw_publisher['name'] ) ? sanitize_text_field( wp_unslash( $raw_publisher['name'] ) ) : '',
		'legal_name'  => isset( $raw_publisher['legal_name'] ) ? sanitize_text_field( wp_unslash( $raw_publisher['legal_name'] ) ) : '',
		'rut'         => isset( $raw_publisher['rut'] ) ? sanitize_text_field( wp_unslash( $raw_publisher['rut'] ) ) : '',
		'description' => isset( $raw_publisher['description'] ) ? wp_kses_post( wp_unslash( $raw_publisher['description'] ) ) : '',
		'email'       => isset( $raw_publisher['email'] ) ? sanitize_email( wp_unslash( $raw_publisher['email'] ) ) : '',
		'phone'       => isset( $raw_publisher['phone'] ) ? sanitize_text_field( wp_unslash( $raw_publisher['phone'] ) ) : '',
		'website'     => isset( $raw_publisher['website'] ) ? esc_url_raw( wp_unslash( $raw_publisher['website'] ) ) : '',
		'logo'        => isset( $raw_publisher['logo'] ) ? absint( $raw_publisher['logo'] ) : 0,
		'banner'      => isset( $raw_publisher['banner'] ) ? absint( $raw_publisher['banner'] ) : 0,
		'keywords'    => isset( $raw_publisher['keywords'] ) ? sanitize_textarea_field( wp_unslash( $raw_publisher['keywords'] ) ) : '',
		'status'      => isset( $raw_publisher['status'] ) ? sanitize_key( wp_unslash( $raw_publisher['status'] ) ) : 'pending',
	);
}

function almaden_bookster_get_publisher_by_id( $publisher_id ) {
	global $wpdb;

	$publisher_id = absint( $publisher_id );
	if ( $publisher_id <= 0 ) {
		return null;
	}

	$table_name = almaden_bookster_get_publishers_table_name();
	return $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $publisher_id ),
		ARRAY_A
	);
}

function almaden_bookster_get_publisher_by_slug( $slug ) {
	global $wpdb;

	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug ) {
		return null;
	}

	$table_name = almaden_bookster_get_publishers_table_name();
	return $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM $table_name WHERE slug = %s", $slug ),
		ARRAY_A
	);
}

function almaden_bookster_save_publisher( $publisher_data, $publisher_id = 0 ) {
	global $wpdb;

	$data = almaden_bookster_sanitize_publisher_data( $publisher_data );
	$table_name = almaden_bookster_get_publishers_table_name();
	$now = current_time( 'mysql' );

	if ( '' === $data['slug'] || '' === $data['name'] ) {
		return new WP_Error( 'almaden_invalid_publisher', __( 'La editorial requiere slug y nombre.', 'almaden-bookster' ) );
	}

	$payload = array(
		'slug'        => $data['slug'],
		'name'        => $data['name'],
		'legal_name'  => $data['legal_name'],
		'rut'         => $data['rut'],
		'description' => $data['description'],
		'email'       => $data['email'],
		'phone'       => $data['phone'],
		'website'     => $data['website'],
		'logo'        => $data['logo'],
		'banner'      => $data['banner'],
		'keywords'    => $data['keywords'],
		'status'      => $data['status'],
		'updated_at'  => $now,
	);

	if ( $publisher_id > 0 ) {
		$result = $wpdb->update( $table_name, $payload, array( 'id' => absint( $publisher_id ) ) );
		if ( false === $result ) {
			return new WP_Error( 'almaden_publisher_update_failed', __( 'No se pudo actualizar la editorial.', 'almaden-bookster' ) );
		}

		return absint( $publisher_id );
	}

	$payload['created_at'] = $now;
	$inserted = $wpdb->insert( $table_name, $payload );
	if ( false === $inserted ) {
		return new WP_Error( 'almaden_publisher_insert_failed', __( 'No se pudo guardar la editorial.', 'almaden-bookster' ) );
	}

	return absint( $wpdb->insert_id );
}

function almaden_bookster_get_book_publisher_id( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return 0;
	}

	return absint( get_post_meta( $book_id, almaden_bookster_get_book_publisher_meta_key(), true ) );
}

function almaden_bookster_set_book_publisher_id( $book_id, $publisher_id ) {
	$book_id = absint( $book_id );
	$publisher_id = absint( $publisher_id );

	if ( $book_id <= 0 ) {
		return false;
	}

	if ( $publisher_id > 0 ) {
		return (bool) update_post_meta( $book_id, almaden_bookster_get_book_publisher_meta_key(), $publisher_id );
	}

	return delete_post_meta( $book_id, almaden_bookster_get_book_publisher_meta_key() );
}

function almaden_bookster_sync_existing_book_publisher_meta( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 || 'almaden-books' !== get_post_type( $book_id ) ) {
		return false;
	}

	$meta_key = almaden_bookster_get_book_publisher_meta_key();
	if ( metadata_exists( 'post', $book_id, $meta_key ) ) {
		return true;
	}

	return add_post_meta( $book_id, $meta_key, 0, true );
}

function almaden_bookster_sync_publisher_page() {
	$settings = almaden_bookster_get_publisher_page_settings();
	$slug     = almaden_bookster_get_publisher_page_slug();
	$title    = almaden_bookster_get_publisher_page_title();
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

		if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
			$settings['page_id'] = absint( $new_page_id );
			$settings['slug']    = $slug;
			$settings['title']   = $title;
			update_option( 'almaden_bookster_publisher_page_settings', $settings );
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
		$settings['page_id'] = (int) $page->ID;
		$settings['slug']    = $slug;
		$settings['title']   = $title;
		update_option( 'almaden_bookster_publisher_page_settings', $settings );
	}
}

function almaden_bookster_get_publishers( $args = array() ) {
	global $wpdb;

	$table_name = almaden_bookster_get_publishers_table_name();
	$defaults = array(
		'status' => 'active',
		'orderby' => 'name',
		'order' => 'ASC',
	);
	$args = wp_parse_args( $args, $defaults );

	$where = '1=1';
	$params = array();

	if ( '' !== (string) $args['status'] ) {
		$where .= ' AND status = %s';
		$params[] = sanitize_key( $args['status'] );
	}

	$orderby = in_array( $args['orderby'], array( 'name', 'created_at', 'updated_at', 'slug' ), true ) ? $args['orderby'] : 'name';
	$order   = 'DESC' === strtoupper( (string) $args['order'] ) ? 'DESC' : 'ASC';

	$sql = "SELECT * FROM $table_name WHERE $where ORDER BY $orderby $order";
	if ( ! empty( $params ) ) {
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
	}

	return $wpdb->get_results( $sql, ARRAY_A );
}

function almaden_bookster_get_publisher_books( $publisher_id ) {
	$publisher_id = absint( $publisher_id );
	if ( $publisher_id <= 0 ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => 'almaden-books',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'   => almaden_bookster_get_book_publisher_meta_key(),
					'value' => $publisher_id,
				),
				array(
					'key'   => '_almaden_is_published',
					'value' => '1',
				),
			),
		)
	);
}

function almaden_bookster_get_publisher_members( $publisher_id ) {
	global $wpdb;

	$publisher_id = absint( $publisher_id );
	if ( $publisher_id <= 0 ) {
		return array();
	}

	$table_name = almaden_bookster_get_publisher_members_table_name();
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE publisher_id = %d AND status = %s ORDER BY joined_at ASC, id ASC",
			$publisher_id,
			'active'
		),
		ARRAY_A
	);
}

function almaden_bookster_get_publisher_role_label( $role ) {
	switch ( sanitize_key( (string) $role ) ) {
		case 'owner':
			return __( 'Propietario', 'almaden-bookster' );
		case 'editor':
			return __( 'Editor', 'almaden-bookster' );
		case 'author':
			return __( 'Autor', 'almaden-bookster' );
		case 'corrector':
			return __( 'Corrector', 'almaden-bookster' );
		case 'designer':
			return __( 'Diseñador', 'almaden-bookster' );
		default:
			return ucfirst( sanitize_text_field( (string) $role ) );
	}
}

function almaden_bookster_create_publishers_tables() {
	global $wpdb;

	$db_version_option = 'almaden_bookster_publishers_db_version';
	$db_version        = '1.2.0';
	$publishers_table   = almaden_bookster_get_publishers_table_name();
	$members_table      = almaden_bookster_get_publisher_members_table_name();

	$publishers_exists = almaden_bookster_table_exists( $publishers_table );
	$members_exists    = almaden_bookster_table_exists( $members_table );

	if ( get_option( $db_version_option ) === $db_version && $publishers_exists && $members_exists ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();

	$sql_publishers = "CREATE TABLE $publishers_table (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		slug varchar(191) NOT NULL,
		name varchar(191) NOT NULL,
		legal_name varchar(191) DEFAULT '' NOT NULL,
		rut varchar(50) DEFAULT '' NOT NULL,
		description longtext NOT NULL,
		email varchar(190) DEFAULT '' NOT NULL,
		phone varchar(50) DEFAULT '' NOT NULL,
		website varchar(190) DEFAULT '' NOT NULL,
		logo bigint(20) unsigned DEFAULT 0 NOT NULL,
		banner bigint(20) unsigned DEFAULT 0 NOT NULL,
		keywords longtext NOT NULL,
		settings_json longtext NULL,
		status varchar(20) DEFAULT 'pending' NOT NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY slug (slug),
		KEY status (status)
	) $charset_collate;";

	$sql_members = "CREATE TABLE $members_table (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		publisher_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		role varchar(50) DEFAULT 'owner' NOT NULL,
		status varchar(20) DEFAULT 'active' NOT NULL,
		joined_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY publisher_user (publisher_id, user_id),
		KEY publisher_id (publisher_id),
		KEY user_id (user_id),
		KEY role (role),
		KEY status (status)
	) $charset_collate;";

	dbDelta( $sql_publishers );
	dbDelta( $sql_members );

	almaden_bookster_migrate_publisher_book_meta();

	update_option( $db_version_option, $db_version );
}

function almaden_bookster_register_publisher_routes() {
	$base_slug = trim( almaden_bookster_get_publisher_page_slug(), '/' );
	if ( '' === $base_slug ) {
		$base_slug = 'editorial';
	}

	add_rewrite_rule(
		'^' . preg_quote( $base_slug, '/' ) . '/([^/]+)/?$',
		'index.php?pagename=' . $base_slug . '&almaden_publisher_slug=$matches[1]',
		'top'
	);
}
add_action( 'init', 'almaden_bookster_register_publisher_routes', 20 );

function almaden_bookster_maybe_flush_publisher_rewrite_rules() {
	$rewrite_version_option = 'almaden_bookster_publisher_rewrite_version';
	$rewrite_version        = '1.1.0';

	if ( get_option( $rewrite_version_option ) === $rewrite_version ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( $rewrite_version_option, $rewrite_version );
}
add_action( 'init', 'almaden_bookster_maybe_flush_publisher_rewrite_rules', 99 );

function almaden_bookster_register_publisher_query_vars( $vars ) {
	$vars[] = 'almaden_publisher_slug';
	return $vars;
}
add_filter( 'query_vars', 'almaden_bookster_register_publisher_query_vars' );

function almaden_bookster_render_publisher_page_content( $content ) {
	if ( ! is_page( almaden_bookster_get_publisher_page_slug() ) || ! is_main_query() ) {
		return $content;
	}

	if ( ! in_the_loop() ) {
		return $content;
	}

	if ( 'settings' === get_query_var( 'almaden_publisher_view', '' ) ) {
		return $content;
	}

	$publisher_slug = get_query_var( 'almaden_publisher_slug', '' );
	$publisher      = '' !== trim( (string) $publisher_slug ) ? almaden_bookster_get_publisher_by_slug( $publisher_slug ) : null;
	$publishers     = '';

	ob_start();
	require plugin_dir_path( __FILE__ ) . '../../templates/publishers/publisher-page.php';
	$publishers = ob_get_clean();

	return $publishers;
}
add_filter( 'the_content', 'almaden_bookster_render_publisher_page_content', 20 );

function almaden_bookster_load_publisher_app_page() {
	if ( ! is_page( almaden_bookster_get_publisher_page_slug() ) || ! is_main_query() ) {
		return;
	}

	if ( 'settings' === get_query_var( 'almaden_publisher_view', '' ) ) {
		return;
	}

	show_admin_bar( false );

	$template_path = dirname( __FILE__ ) . '/../../templates/publishers/publisher-app.php';
	if ( file_exists( $template_path ) ) {
		require_once $template_path;
		exit;
	}

	wp_die( 'Plantilla de editorial no encontrada.' );
}
add_action( 'template_redirect', 'almaden_bookster_load_publisher_app_page', 5 );

function almaden_bookster_migrate_publisher_book_meta() {
	$book_ids = get_posts(
		array(
			'post_type'      => 'almaden-books',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	if ( empty( $book_ids ) ) {
		return;
	}

	foreach ( $book_ids as $book_id ) {
		almaden_bookster_sync_existing_book_publisher_meta( $book_id );
	}
}
