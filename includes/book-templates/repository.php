<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ALMADEN_BOOK_TEMPLATE_POST_TYPE = 'alm_book_template';
const ALMADEN_BOOK_TEMPLATE_PAYLOAD_META = '_almaden_book_template_payload';
const ALMADEN_BOOK_TEMPLATE_SCHEMA_VERSION = 1;

function almaden_bookster_register_book_template_post_type() {
	register_post_type(
		ALMADEN_BOOK_TEMPLATE_POST_TYPE,
		array(
			'label'               => __( 'Plantillas de libro', 'almaden-bookster' ),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array( 'title', 'author' ),
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'almaden_bookster_register_book_template_post_type', 0 );

function almaden_bookster_book_template_directories() {
	return array(
		'builtin' => plugin_dir_path( dirname( __DIR__ ) ) . 'includes/templates/book-templates/',
		'custom'  => plugin_dir_path( dirname( __DIR__ ) ) . 'includes/templates/book-templates/custom/',
		'legacy'  => plugin_dir_path( dirname( __DIR__ ) ) . 'includes/templates/settings/',
	);
}

function almaden_bookster_sanitize_book_template_value( $value ) {
	if ( is_array( $value ) ) {
		$sanitized = array();
		foreach ( $value as $key => $item ) {
			$clean_key = is_int( $key ) ? $key : sanitize_key( (string) $key );
			$sanitized[ $clean_key ] = almaden_bookster_sanitize_book_template_value( $item );
		}
		return $sanitized;
	}

	if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
		return $value;
	}

	return sanitize_text_field( (string) $value );
}

function almaden_bookster_normalize_book_template_payload( $json, $source = 'builtin', $fallback_id = '' ) {
	if ( ! is_array( $json ) ) {
		return null;
	}

	$name = isset( $json['name'] ) ? sanitize_text_field( $json['name'] ) : '';
	$settings = isset( $json['settings'] ) && is_array( $json['settings'] )
		? almaden_bookster_sanitize_book_template_value( $json['settings'] )
		: array();

	if ( '' === $name || empty( $settings ) ) {
		return null;
	}

	$template_id = isset( $json['id'] ) ? sanitize_title( $json['id'] ) : sanitize_title( $fallback_id );
	if ( '' === $template_id ) {
		$template_id = sanitize_title( $name );
	}

	$origin = in_array( $source, array( 'builtin', 'legacy' ), true ) ? 'system' : 'user';

	return array(
		'id'              => $template_id,
		'kind'            => 'book-template',
		'name'            => $name,
		'description'     => isset( $json['description'] ) ? sanitize_text_field( $json['description'] ) : '',
		'visibility'      => 'system' === $origin ? 'public' : 'private',
		'origin'          => $origin,
		'source'          => $source,
		'schema_version'  => isset( $json['schema_version'] ) ? max( 1, absint( $json['schema_version'] ) ) : ALMADEN_BOOK_TEMPLATE_SCHEMA_VERSION,
		'settings'        => $settings,
		'preview'         => isset( $json['preview'] ) && is_array( $json['preview'] ) ? almaden_bookster_sanitize_book_template_value( $json['preview'] ) : array(),
		'sample_chapters' => isset( $json['sample_chapters'] ) && is_array( $json['sample_chapters'] ) ? almaden_bookster_sanitize_book_template_value( $json['sample_chapters'] ) : array(),
	);
}

/**
 * Return only plugin-owned templates. The former custom directory is handled
 * exclusively by the one-time migration below.
 */
function almaden_bookster_collect_book_template_files() {
	$dirs = almaden_bookster_book_template_directories();
	$files = array();

	foreach ( array( 'legacy', 'builtin' ) as $source ) {
		$dir = $dirs[ $source ] ?? '';
		if ( ! is_dir( $dir ) ) {
			continue;
		}

		$matches = glob( trailingslashit( $dir ) . '*.json' );
		foreach ( is_array( $matches ) ? $matches : array() as $path ) {
			$files[] = array(
				'path'     => $path,
				'source'   => $source,
				'priority' => 'builtin' === $source ? 2 : 1,
			);
		}
	}

	usort(
		$files,
		static function( $left, $right ) {
			if ( $left['priority'] === $right['priority'] ) {
				return strcmp( $left['path'], $right['path'] );
			}
			return $left['priority'] <=> $right['priority'];
		}
	);

	return $files;
}

function almaden_bookster_read_system_book_templates() {
	$templates = array();

	foreach ( almaden_bookster_collect_book_template_files() as $entry ) {
		$content = file_get_contents( $entry['path'] );
		if ( false === $content ) {
			continue;
		}

		$json = json_decode( $content, true );
		if ( ! is_array( $json ) ) {
			continue;
		}

		$template = almaden_bookster_normalize_book_template_payload(
			$json,
			$entry['source'],
			basename( $entry['path'], '.json' )
		);
		if ( ! $template ) {
			continue;
		}

		$template['template_key'] = $template['id'];
		$template['id'] = 'system:' . $template['id'];
		$template['owner_id'] = null;
		$template['can_update'] = false;
		$template['can_delete'] = false;
		$template['_path'] = $entry['path'];
		$templates[ $template['template_key'] ] = $template;
	}

	return array_values( $templates );
}

function almaden_bookster_get_system_book_template( $template_id ) {
	$template_key = preg_replace( '/^system:/', '', sanitize_text_field( (string) $template_id ) );
	foreach ( almaden_bookster_read_system_book_templates() as $template ) {
		if ( $template['template_key'] === $template_key ) {
			return $template;
		}
	}
	return null;
}

function almaden_bookster_normalize_personal_book_template_post( $post ) {
	$post = get_post( $post );
	if ( ! $post || ALMADEN_BOOK_TEMPLATE_POST_TYPE !== $post->post_type ) {
		return null;
	}

	$payload = get_post_meta( $post->ID, ALMADEN_BOOK_TEMPLATE_PAYLOAD_META, true );
	if ( ! is_array( $payload ) ) {
		return null;
	}

	$payload['id'] = 'user:' . $post->ID;
	$payload['name'] = $post->post_title;
	$payload['origin'] = 'user';
	$payload['source'] = 'user';
	$payload['visibility'] = 'private';
	$payload['owner_id'] = (int) $post->post_author;
	$payload['can_update'] = (int) $post->post_author === get_current_user_id() || current_user_can( 'manage_options' );
	$payload['can_delete'] = $payload['can_update'];
	unset( $payload['_path'] );

	return $payload;
}

function almaden_bookster_get_personal_book_templates( $user_id ) {
	$user_id = absint( $user_id );
	if ( $user_id <= 0 ) {
		return array();
	}

	$posts = get_posts(
		array(
			'post_type'      => ALMADEN_BOOK_TEMPLATE_POST_TYPE,
			'post_status'    => 'private',
			'author'         => $user_id,
			'posts_per_page' => -1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	return array_values( array_filter( array_map( 'almaden_bookster_normalize_personal_book_template_post', $posts ) ) );
}

function almaden_bookster_parse_personal_book_template_id( $template_id ) {
	$template_id = sanitize_text_field( (string) $template_id );
	if ( 0 !== strpos( $template_id, 'user:' ) ) {
		return 0;
	}
	return absint( substr( $template_id, 5 ) );
}

function almaden_bookster_get_personal_book_template( $template_id ) {
	$post_id = almaden_bookster_parse_personal_book_template_id( $template_id );
	return $post_id > 0 ? almaden_bookster_normalize_personal_book_template_post( $post_id ) : null;
}

function almaden_bookster_user_can_mutate_personal_book_template( $template_id, $user_id = 0 ) {
	$post_id = almaden_bookster_parse_personal_book_template_id( $template_id );
	$post = $post_id > 0 ? get_post( $post_id ) : null;
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

	if ( ! $post || ALMADEN_BOOK_TEMPLATE_POST_TYPE !== $post->post_type || $user_id <= 0 ) {
		return false;
	}

	return (int) $post->post_author === $user_id || current_user_can( 'manage_options' );
}

function almaden_bookster_save_personal_book_template( $payload, $user_id, $template_id = '' ) {
	$user_id = absint( $user_id );
	$normalized = almaden_bookster_normalize_book_template_payload( $payload, 'user' );
	if ( $user_id <= 0 || ! $normalized ) {
		return new WP_Error( 'invalid_template', __( 'La plantilla no contiene un nombre y ajustes válidos.', 'almaden-bookster' ) );
	}

	$post_id = almaden_bookster_parse_personal_book_template_id( $template_id );
	$post_data = array(
		'post_type'   => ALMADEN_BOOK_TEMPLATE_POST_TYPE,
		'post_status' => 'private',
		'post_title'  => $normalized['name'],
		'post_author' => $user_id,
	);

	if ( $post_id > 0 ) {
		$post_data['ID'] = $post_id;
		$result = wp_update_post( wp_slash( $post_data ), true );
	} else {
		$result = wp_insert_post( wp_slash( $post_data ), true );
	}

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$stored_payload = array(
		'id'              => 'user:' . $result,
		'kind'            => 'book-template',
		'name'            => $normalized['name'],
		'description'     => $normalized['description'],
		'origin'          => 'user',
		'source'          => 'user',
		'visibility'      => 'private',
		'schema_version'  => $normalized['schema_version'],
		'settings'        => $normalized['settings'],
		'preview'         => $normalized['preview'],
		'sample_chapters' => $normalized['sample_chapters'],
	);
	update_post_meta( $result, ALMADEN_BOOK_TEMPLATE_PAYLOAD_META, $stored_payload );

	return almaden_bookster_normalize_personal_book_template_post( $result );
}

function almaden_bookster_get_legacy_template_migration_owner_id() {
	$admin_email = sanitize_email( (string) get_option( 'admin_email' ) );
	$admin = $admin_email ? get_user_by( 'email', $admin_email ) : false;
	if ( $admin ) {
		return (int) $admin->ID;
	}

	$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ids' ) );
	return ! empty( $admins ) ? (int) $admins[0] : 0;
}

function almaden_bookster_migrate_legacy_custom_book_templates() {
	if ( get_option( 'almaden_bookster_personal_templates_migrated' ) ) {
		return;
	}

	$owner_id = almaden_bookster_get_legacy_template_migration_owner_id();
	if ( $owner_id <= 0 ) {
		return;
	}

	$dirs = almaden_bookster_book_template_directories();
	$matches = is_dir( $dirs['custom'] ) ? glob( trailingslashit( $dirs['custom'] ) . '*.json' ) : array();
	foreach ( is_array( $matches ) ? $matches : array() as $path ) {
		$content = file_get_contents( $path );
		$json = false !== $content ? json_decode( $content, true ) : null;
		if ( ! is_array( $json ) ) {
			continue;
		}

		$template = almaden_bookster_save_personal_book_template( $json, $owner_id );
		if ( ! is_wp_error( $template ) ) {
			$post_id = almaden_bookster_parse_personal_book_template_id( $template['id'] );
			update_post_meta( $post_id, '_almaden_legacy_template_file', basename( $path ) );
		}
	}

	update_option( 'almaden_bookster_personal_templates_migrated', gmdate( 'c' ), false );
}
add_action( 'init', 'almaden_bookster_migrate_legacy_custom_book_templates', 20 );
