<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
function almaden_bookster_get_book_authors_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'almaden_book_authors';
}
function almaden_bookster_get_book_author_edit_tokens( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return '';
	}

	$legacy = get_post_meta( $book_id, 'book_author', true );
	if ( '' !== trim( (string) $legacy ) ) {
		return trim( (string) $legacy );
	}

	$legacy = get_post_meta( $book_id, '_almaden_book_author', true );
	if ( '' !== trim( (string) $legacy ) ) {
		return trim( (string) $legacy );
	}

	$authors = function_exists( 'almaden_bookster_get_book_authors' ) ? almaden_bookster_get_book_authors( $book_id ) : array();
	if ( empty( $authors ) ) {
		return '';
	}

	$tokens = array();
	foreach ( $authors as $author ) {
		if ( ! empty( $author['user_email'] ) ) {
			$tokens[] = $author['user_email'];
			continue;
		}

		if ( ! empty( $author['user_login'] ) ) {
			$tokens[] = $author['user_login'];
		}
	}

	return implode( ', ', array_filter( array_map( 'trim', $tokens ) ) );
}

function almaden_bookster_get_book_author_display_label( $book_id, $fallback = '' ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return $fallback;
	}

	$authors = function_exists( 'almaden_bookster_get_book_authors' ) ? almaden_bookster_get_book_authors( $book_id ) : array();
	if ( ! empty( $authors ) ) {
		$labels = array();
		foreach ( $authors as $author ) {
			if ( ! empty( $author['display_name'] ) ) {
				$labels[] = $author['display_name'];
			}
		}

		if ( ! empty( $labels ) ) {
			return implode( ', ', $labels );
		}
	}

	$legacy = get_post_meta( $book_id, 'book_author', true );
	if ( '' !== trim( (string) $legacy ) ) {
		return trim( (string) $legacy );
	}

	$legacy = get_post_meta( $book_id, '_almaden_book_author', true );
	if ( '' !== trim( (string) $legacy ) ) {
		return trim( (string) $legacy );
	}

	$post_author_id = absint( get_post_field( 'post_author', $book_id ) );
	if ( $post_author_id > 0 ) {
		$user = get_user_by( 'id', $post_author_id );
		if ( $user && ! empty( $user->display_name ) ) {
			return $user->display_name;
		}
	}

	return $fallback;
}

function almaden_bookster_parse_book_author_tokens( $raw_authors ) {
	$raw_authors = is_string( $raw_authors ) ? trim( $raw_authors ) : '';
	if ( '' === $raw_authors ) {
		return array();
	}

	$tokens = preg_split( '/[,\n;|]+/', $raw_authors );
	if ( empty( $tokens ) ) {
		return array();
	}

	$tokens = array_map(
		function ( $token ) {
			return trim( sanitize_text_field( $token ) );
		},
		$tokens
	);

	return array_values( array_filter( $tokens ) );
}

function almaden_bookster_find_user_for_book_author_token( $token ) {
	$token = trim( sanitize_text_field( (string) $token ) );
	if ( '' === $token ) {
		return null;
	}

	if ( is_email( $token ) ) {
		$user = get_user_by( 'email', $token );
		if ( $user ) {
			return $user;
		}
	}

	$user = get_user_by( 'login', $token );
	if ( $user ) {
		return $user;
	}

	$user = get_user_by( 'slug', $token );
	if ( $user ) {
		return $user;
	}

	$users = get_users(
		array(
			'number'     => 20,
			'fields'     => 'all',
			'search'     => '*' . $token . '*',
			'search_columns' => array( 'display_name', 'user_email', 'user_login', 'user_nicename' ),
		)
	);

	foreach ( $users as $candidate ) {
		if (
			strtolower( $candidate->display_name ) === strtolower( $token ) ||
			strtolower( $candidate->user_email ) === strtolower( $token ) ||
			strtolower( $candidate->user_login ) === strtolower( $token ) ||
			strtolower( $candidate->user_nicename ) === strtolower( $token )
		) {
			return $candidate;
		}
	}

	return null;
}

function almaden_bookster_normalize_book_authors_payload( $book_id, $raw_authors ) {
	$book_id = absint( $book_id );
	$entries = array();
	$tokens  = array();

	if ( is_array( $raw_authors ) ) {
		$tokens = $raw_authors;
	} else {
		$tokens = almaden_bookster_parse_book_author_tokens( $raw_authors );
	}

	$sort_order = 1;
	foreach ( $tokens as $token ) {
		$user_id = 0;
		$label   = '';

		if ( is_array( $token ) ) {
			$user_id = isset( $token['user_id'] ) ? absint( $token['user_id'] ) : 0;
			$label   = isset( $token['display_name'] ) ? sanitize_text_field( $token['display_name'] ) : '';
			$resolved_user = $user_id > 0 ? get_user_by( 'id', $user_id ) : null;
			if ( ! $resolved_user ) {
				if ( ! empty( $token['user_email'] ) ) {
					$resolved_user = get_user_by( 'email', sanitize_email( $token['user_email'] ) );
				}
				if ( ! $resolved_user && ! empty( $token['user_login'] ) ) {
					$resolved_user = get_user_by( 'login', sanitize_user( $token['user_login'], true ) );
				}
				if ( ! $resolved_user && ! empty( $label ) ) {
					$resolved_user = almaden_bookster_find_user_for_book_author_token( $label );
				}
			}

			if ( $resolved_user ) {
				$user_id = absint( $resolved_user->ID );
				if ( '' === $label && ! empty( $resolved_user->display_name ) ) {
					$label = sanitize_text_field( $resolved_user->display_name );
				}
			}
		} else {
			$user = almaden_bookster_find_user_for_book_author_token( $token );
			if ( $user ) {
				$user_id = absint( $user->ID );
				$label   = sanitize_text_field( $user->display_name );
				if ( '' === $label ) {
					$label = sanitize_text_field( $user->user_login );
				}
			} else {
				$label = sanitize_text_field( $token );
			}
		}

		if ( $user_id <= 0 ) {
			continue;
		}

		if ( isset( $entries[ $user_id ] ) ) {
			continue;
		}

		$author_user = get_user_by( 'id', $user_id );
		$entries[ $user_id ] = array(
			'book_id'      => $book_id,
			'user_id'      => $user_id,
			'role'         => isset( $token['role'] ) ? sanitize_key( $token['role'] ) : 'author',
			'sort_order'   => $sort_order,
			'status'       => 'active',
			'source_label' => '' !== $label ? $label : ( $author_user && ! empty( $author_user->display_name ) ? $author_user->display_name : '' ),
			'display_name' => $author_user && ! empty( $author_user->display_name ) ? $author_user->display_name : $label,
			'user_login'   => $author_user && ! empty( $author_user->user_login ) ? $author_user->user_login : '',
			'user_email'   => $author_user && ! empty( $author_user->user_email ) ? $author_user->user_email : '',
		);
		$sort_order++;
	}

	return array_values( $entries );
}

function almaden_bookster_get_book_authors( $book_id ) {
	global $wpdb;

	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return array();
	}

	$table_name = almaden_bookster_get_book_authors_table_name();
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE book_id = %d AND status = %s ORDER BY sort_order ASC, id ASC",
			$book_id,
			'active'
		),
		ARRAY_A
	);

	if ( empty( $rows ) ) {
		return array();
	}

	foreach ( $rows as &$row ) {
		$row['user_id'] = absint( $row['user_id'] );
		$row['sort_order'] = absint( $row['sort_order'] );
		$row['role'] = sanitize_key( $row['role'] );
		$row['status'] = sanitize_key( $row['status'] );
		$row['source_label'] = isset( $row['source_label'] ) ? sanitize_text_field( $row['source_label'] ) : '';

		$user = get_user_by( 'id', $row['user_id'] );
		if ( $user ) {
			$row['display_name'] = ! empty( $user->display_name ) ? $user->display_name : $user->user_login;
			$row['user_login']    = $user->user_login;
			$row['user_email']    = $user->user_email;
		} else {
			$row['display_name'] = $row['source_label'];
			$row['user_login']    = '';
			$row['user_email']    = '';
		}
	}
	unset( $row );

	return $rows;
}

function almaden_bookster_get_book_author_ids( $book_id ) {
	$authors = almaden_bookster_get_book_authors( $book_id );
	if ( empty( $authors ) ) {
		return array();
	}

	return array_values(
		array_filter(
			array_map(
				static function ( $author ) {
					return isset( $author['user_id'] ) ? absint( $author['user_id'] ) : 0;
				},
				$authors
			)
		)
	);
}

function almaden_bookster_set_book_authors( $book_id, $authors, $raw_label = '' ) {
	global $wpdb;

	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return false;
	}

	$table_name = almaden_bookster_get_book_authors_table_name();
	$normalized  = almaden_bookster_normalize_book_authors_payload( $book_id, $authors );

	$wpdb->delete(
		$table_name,
		array( 'book_id' => $book_id ),
		array( '%d' )
	);

	if ( empty( $normalized ) ) {
		if ( '' !== trim( (string) $raw_label ) ) {
			update_post_meta( $book_id, 'book_author', sanitize_text_field( $raw_label ) );
			update_post_meta( $book_id, '_almaden_book_author', sanitize_text_field( $raw_label ) );
		}
		return true;
	}

	foreach ( $normalized as $author ) {
		$wpdb->insert(
			$table_name,
			array(
				'book_id'      => $book_id,
				'user_id'      => absint( $author['user_id'] ),
				'role'         => sanitize_key( $author['role'] ),
				'sort_order'   => absint( $author['sort_order'] ),
				'status'       => 'active',
				'source_label' => sanitize_text_field( $author['source_label'] ),
				'created_at'   => current_time( 'mysql' ),
				'updated_at'   => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
		);
	}

	$label = '' !== trim( (string) $raw_label )
		? sanitize_text_field( $raw_label )
		: implode(
			', ',
			array_filter(
				array_map(
					static function ( $author ) {
						return ! empty( $author['display_name'] ) ? $author['display_name'] : '';
					},
					$normalized
				)
			)
		);

	update_post_meta( $book_id, 'book_author', $label );
	update_post_meta( $book_id, '_almaden_book_author', $label );

	return true;
}

function almaden_bookster_sync_book_authors_from_input( $book_id, $raw_authors ) {
	$book_id = absint( $book_id );
	$raw_authors = is_string( $raw_authors ) ? trim( $raw_authors ) : '';

	if ( $book_id <= 0 ) {
		return false;
	}

	if ( '' === $raw_authors ) {
		return false;
	}

	$payload = almaden_bookster_normalize_book_authors_payload( $book_id, $raw_authors );
	if ( empty( $payload ) ) {
		return almaden_bookster_set_book_authors( $book_id, array(), $raw_authors );
	}

	return almaden_bookster_set_book_authors( $book_id, $payload, $raw_authors );
}

function almaden_bookster_user_is_book_author( $book_id, $user_id = null ) {
	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

	if ( $book_id <= 0 || $user_id <= 0 ) {
		return false;
	}

	$authors = almaden_bookster_get_book_author_ids( $book_id );
	if ( in_array( $user_id, $authors, true ) ) {
		return true;
	}

	$post_author_id = absint( get_post_field( 'post_author', $book_id ) );
	return $post_author_id > 0 && $post_author_id === $user_id;
}

function almaden_bookster_create_book_authors_table() {
	global $wpdb;

	$db_version_option = 'almaden_bookster_book_authors_db_version';
	$db_version        = '1.0.0';
	$table_name        = almaden_bookster_get_book_authors_table_name();
	$table_exists      = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;

	if ( get_option( $db_version_option ) === $db_version && $table_exists ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE $table_name (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		book_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned NOT NULL,
		role varchar(50) DEFAULT 'author' NOT NULL,
		sort_order int(11) NOT NULL DEFAULT 0,
		status varchar(20) DEFAULT 'active' NOT NULL,
		source_label varchar(191) DEFAULT '' NOT NULL,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY book_user_role (book_id, user_id, role),
		KEY book_id (book_id),
		KEY user_id (user_id),
		KEY sort_order (sort_order),
		KEY status (status)
	) $charset_collate;";

	dbDelta( $sql );
	if ( function_exists( 'almaden_bookster_migrate_book_authors' ) ) {
		almaden_bookster_migrate_book_authors();
	}
	update_option( $db_version_option, $db_version );
}
add_action( 'init', 'almaden_bookster_create_book_authors_table' );
