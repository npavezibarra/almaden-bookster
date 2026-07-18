<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_current_user_primary_publisher_id() {
	$user_id = get_current_user_id();
	if ( $user_id <= 0 ) {
		return 0;
	}

	return absint( get_user_meta( $user_id, '_almaden_primary_publisher_id', true ) );
}

function almaden_bookster_get_author_primary_publisher_id( $author_id ) {
	$author_id = absint( $author_id );
	if ( $author_id <= 0 ) {
		return 0;
	}

	$primary_publisher_id = absint( get_user_meta( $author_id, '_almaden_primary_publisher_id', true ) );
	if ( $primary_publisher_id > 0 ) {
		return $primary_publisher_id;
	}

	if ( function_exists( 'almaden_bookster_get_user_publisher_memberships' ) ) {
		$memberships = almaden_bookster_get_user_publisher_memberships( $author_id );
		if ( ! empty( $memberships ) && isset( $memberships[0]['publisher_id'] ) ) {
			return absint( $memberships[0]['publisher_id'] );
		}
	}

	return 0;
}

function almaden_bookster_can_edit_author_profile( $author_id ) {
	$author_id = absint( $author_id );
	if ( $author_id <= 0 || ! is_user_logged_in() ) {
		return false;
	}

	if ( current_user_can( 'manage_options' ) || current_user_can( 'almaden_manage_books' ) ) {
		return true;
	}

	if ( get_current_user_id() === $author_id ) {
		return true;
	}

	$primary_publisher_id = almaden_bookster_get_author_primary_publisher_id( $author_id );
	if ( $primary_publisher_id <= 0 || ! function_exists( 'almaden_bookster_user_is_publisher_member' ) ) {
		return false;
	}

	return almaden_bookster_user_is_publisher_member( get_current_user_id(), $primary_publisher_id, array( 'owner', 'editor' ) );
}

function almaden_bookster_sanitize_author_payload( $raw_author ) {
	$raw_author = is_array( $raw_author ) ? $raw_author : array();

	$bio = isset( $raw_author['bio'] ) ? wp_kses_post( wp_unslash( $raw_author['bio'] ) ) : '';
	$bio_plain = trim( wp_strip_all_tags( $bio ) );
	if ( '' !== $bio_plain ) {
		$normalized_bio = wp_strip_all_tags( html_entity_decode( $bio_plain, ENT_QUOTES, get_bloginfo( 'charset' ) ) );
		$word_count     = preg_match_all( '/[\p{L}\p{N}][\p{L}\p{N}\p{Mn}\p{Pd}\']*/u', $normalized_bio, $matches );
		$word_count     = false === $word_count ? 0 : (int) $word_count;
		if ( $word_count > 500 ) {
			return new WP_Error( 'almaden_author_bio_too_long', __( 'La biografía no puede superar 500 palabras.', 'almaden-bookster' ) );
		}
	}

	$socials = almaden_bookster_get_author_social_link_defaults();
	foreach ( $socials as $network => $unused ) {
		$socials[ $network ] = isset( $raw_author[ 'social_' . $network ] ) ? esc_url_raw( wp_unslash( $raw_author[ 'social_' . $network ] ) ) : '';
	}

	return array(
		'name'         => isset( $raw_author['name'] ) ? sanitize_text_field( wp_unslash( $raw_author['name'] ) ) : '',
		'email'        => isset( $raw_author['email'] ) ? sanitize_email( wp_unslash( $raw_author['email'] ) ) : '',
		'bio'          => $bio,
		'photo_id'     => isset( $raw_author['photo_id'] ) ? absint( $raw_author['photo_id'] ) : 0,
		'backcover_id' => isset( $raw_author['backcover_id'] ) ? absint( $raw_author['backcover_id'] ) : 0,
		'socials'      => $socials,
	);
}

function almaden_bookster_save_author_user( $author_data, $user_id = 0 ) {
	$author_data = is_array( $author_data ) ? $author_data : array();

	$sanitized = almaden_bookster_sanitize_author_payload( $author_data );
	if ( is_wp_error( $sanitized ) ) {
		return $sanitized;
	}

	$name  = isset( $sanitized['name'] ) ? trim( (string) $sanitized['name'] ) : '';
	$email = isset( $sanitized['email'] ) ? trim( (string) $sanitized['email'] ) : '';

	if ( '' === $name ) {
		return new WP_Error( 'almaden_author_name_required', __( 'El nombre del autor es obligatorio.', 'almaden-bookster' ) );
	}

	if ( '' === $email || ! is_email( $email ) ) {
		return new WP_Error( 'almaden_author_email_required', __( 'Necesitamos un correo válido para el autor.', 'almaden-bookster' ) );
	}

	$existing_user = get_user_by( 'email', $email );
	$user          = null;

	if ( $user_id > 0 ) {
		$user = get_user_by( 'id', absint( $user_id ) );
	}

	if ( ! $user && $existing_user ) {
		$user = $existing_user;
	}

	$existing_slug = '';
	if ( $user ) {
		$existing_slug = trim( (string) get_user_meta( $user->ID, almaden_bookster_get_author_slug_meta_key(), true ) );
	}

	$author_slug_for_media = '' !== $existing_slug ? sanitize_title( $existing_slug ) : almaden_bookster_resolve_unique_author_slug( $name );

	$photo_id = isset( $sanitized['photo_id'] ) ? absint( $sanitized['photo_id'] ) : 0;
	if ( ! empty( $_FILES['author_photo_file'] ) && ! empty( $_FILES['author_photo_file']['name'] ) ) {
		$uploaded_photo_id = almaden_bookster_handle_author_photo_upload( 'author_photo_file', $author_slug_for_media );
		if ( is_wp_error( $uploaded_photo_id ) ) {
			return $uploaded_photo_id;
		}

		if ( $uploaded_photo_id > 0 ) {
			$photo_id = $uploaded_photo_id;
		}
	}

	$backcover_id = isset( $sanitized['backcover_id'] ) ? absint( $sanitized['backcover_id'] ) : 0;
	if ( ! empty( $_FILES['author_backcover_file'] ) && ! empty( $_FILES['author_backcover_file']['name'] ) ) {
		$uploaded_backcover_id = almaden_bookster_handle_author_photo_upload( 'author_backcover_file', $author_slug_for_media );
		if ( is_wp_error( $uploaded_backcover_id ) ) {
			return $uploaded_backcover_id;
		}

		if ( $uploaded_backcover_id > 0 ) {
			$backcover_id = $uploaded_backcover_id;
		}
	}

	$is_new_user = false;
	if ( ! $user ) {
		$user_login = function_exists( 'almaden_bookster_generate_unique_user_login' )
			? almaden_bookster_generate_unique_user_login( $email, $name )
			: sanitize_user( current( explode( '@', $email ) ), true );

		if ( '' === $user_login ) {
			$user_login = 'author';
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => $user_login,
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 20, true, true ),
				'display_name' => $name,
				'first_name'   => $name,
				'nickname'     => $name,
				'role'         => 'author',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$user = get_user_by( 'id', absint( $user_id ) );
		$is_new_user = true;
	} else {
		$user_id = absint( $user->ID );
	}

	if ( ! $user ) {
		return new WP_Error( 'almaden_author_user_missing', __( 'No pudimos resolver el usuario del autor.', 'almaden-bookster' ) );
	}

	$primary_publisher_id = almaden_bookster_get_current_user_primary_publisher_id();
	if ( $primary_publisher_id > 0 && 0 === absint( get_user_meta( $user_id, '_almaden_primary_publisher_id', true ) ) ) {
		update_user_meta( $user_id, '_almaden_primary_publisher_id', $primary_publisher_id );
	}

	$current_roles = is_array( $user->roles ) ? $user->roles : array();
	if ( ! in_array( 'administrator', $current_roles, true ) && ! in_array( 'editor', $current_roles, true ) && ! in_array( 'author', $current_roles, true ) ) {
		$user->set_role( 'author' );
	}

	$update_result = wp_update_user(
		array(
			'ID'           => $user_id,
			'display_name' => $name,
			'first_name'   => $name,
			'nickname'     => $name,
			'user_email'   => $email,
			'description'  => isset( $sanitized['bio'] ) ? $sanitized['bio'] : '',
		)
	);

	if ( is_wp_error( $update_result ) ) {
		return $update_result;
	}

	if ( '' === $existing_slug ) {
		update_user_meta( $user_id, almaden_bookster_get_author_slug_meta_key(), $author_slug_for_media );
	} else {
		$author_slug_for_media = sanitize_title( $existing_slug );
	}

	update_user_meta( $user_id, almaden_bookster_get_author_profile_photo_meta_key(), $photo_id );
	update_user_meta( $user_id, almaden_bookster_get_author_backcover_meta_key(), $backcover_id );
	update_user_meta( $user_id, almaden_bookster_get_author_social_links_meta_key(), isset( $sanitized['socials'] ) ? $sanitized['socials'] : almaden_bookster_get_author_social_link_defaults() );
	update_user_meta( $user_id, almaden_bookster_get_author_profile_flag_meta_key(), 1 );
	if ( $primary_publisher_id > 0 && function_exists( 'almaden_bookster_upsert_publisher_member' ) ) {
		almaden_bookster_upsert_publisher_member( $primary_publisher_id, $user_id, 'author', 'active' );
	}

	return array(
		'user_id'   => absint( $user_id ),
		'is_new'    => $is_new_user,
	);
}

function almaden_bookster_handle_create_author() {
	if ( ! is_user_logged_in() ) {
		auth_redirect();
	}

	if ( ! current_user_can( 'almaden_manage_books' ) && ! current_user_can( 'manage_options' ) ) {
		wp_die( 'No tienes permisos para crear autores.' );
	}

	if ( ! isset( $_POST['almaden_author_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_author_nonce'], 'almaden_create_author_nonce' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$result = almaden_bookster_save_author_user(
		array(
			'name'      => isset( $_POST['author_name'] ) ? wp_unslash( $_POST['author_name'] ) : '',
			'email'     => isset( $_POST['author_email'] ) ? wp_unslash( $_POST['author_email'] ) : '',
			'bio'       => isset( $_POST['author_bio'] ) ? wp_unslash( $_POST['author_bio'] ) : '',
			'photo_id'  => isset( $_POST['author_photo_id'] ) ? absint( $_POST['author_photo_id'] ) : 0,
			'backcover_id' => isset( $_POST['author_backcover_id'] ) ? absint( $_POST['author_backcover_id'] ) : 0,
			'social_x'       => isset( $_POST['author_social_x'] ) ? wp_unslash( $_POST['author_social_x'] ) : '',
			'social_facebook' => isset( $_POST['author_social_facebook'] ) ? wp_unslash( $_POST['author_social_facebook'] ) : '',
			'social_instagram'=> isset( $_POST['author_social_instagram'] ) ? wp_unslash( $_POST['author_social_instagram'] ) : '',
			'social_linkedin' => isset( $_POST['author_social_linkedin'] ) ? wp_unslash( $_POST['author_social_linkedin'] ) : '',
		)
	);

	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ) );
	}

	$redirect_url = almaden_bookster_get_authors_page_url(
		array(
			'author_created' => '1',
		)
	);

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_post_almaden_create_author', 'almaden_bookster_handle_create_author' );

function almaden_bookster_handle_update_author_photo() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Debes iniciar sesión para editar la foto.', 'almaden-bookster' ) ), 401 );
	}

	$author_id = isset( $_POST['author_id'] ) ? absint( $_POST['author_id'] ) : 0;
	if ( $author_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'No pudimos resolver el autor.', 'almaden-bookster' ) ), 400 );
	}

	if ( ! almaden_bookster_can_edit_author_profile( $author_id ) ) {
		wp_send_json_error( array( 'message' => __( 'No tienes permiso para editar este autor.', 'almaden-bookster' ) ), 403 );
	}

	if ( ! isset( $_POST['almaden_author_photo_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_author_photo_nonce'], 'almaden_update_author_photo' ) ) {
		wp_send_json_error( array( 'message' => __( 'Validación de seguridad fallida.', 'almaden-bookster' ) ), 403 );
	}

	if ( empty( $_FILES['author_profile_photo_file'] ) || empty( $_FILES['author_profile_photo_file']['name'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Debes subir una imagen para continuar.', 'almaden-bookster' ) ), 400 );
	}

	$author_slug = almaden_bookster_get_author_user_slug( $author_id );
	if ( '' === trim( $author_slug ) ) {
		$author = get_user_by( 'id', $author_id );
		$author_slug = $author ? sanitize_title( $author->display_name ? $author->display_name : $author->user_login ) : 'author';
	}

	$photo_id = almaden_bookster_handle_author_photo_upload( 'author_profile_photo_file', $author_slug );
	if ( is_wp_error( $photo_id ) ) {
		wp_send_json_error( array( 'message' => $photo_id->get_error_message() ), 500 );
	}

	update_user_meta( $author_id, almaden_bookster_get_author_profile_photo_meta_key(), $photo_id );
	update_user_meta( $author_id, almaden_bookster_get_author_profile_flag_meta_key(), 1 );

	$photo_url = wp_get_attachment_image_url( $photo_id, 'large' );
	wp_send_json_success(
		array(
			'photo_id'  => absint( $photo_id ),
			'photo_url' => $photo_url ? $photo_url : '',
		)
	);
}
add_action( 'admin_post_almaden_update_author_photo', 'almaden_bookster_handle_update_author_photo' );
add_action( 'wp_ajax_almaden_update_author_photo', 'almaden_bookster_handle_update_author_photo' );
add_action( 'wp_ajax_nopriv_almaden_update_author_photo', 'almaden_bookster_handle_update_author_photo' );

function almaden_bookster_handle_update_author_hero_background() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Debes iniciar sesión para editar el fondo del autor.', 'almaden-bookster' ) ), 401 );
	}

	$author_id = isset( $_POST['author_id'] ) ? absint( $_POST['author_id'] ) : 0;
	if ( $author_id <= 0 ) {
		wp_send_json_error( array( 'message' => __( 'No pudimos resolver el autor.', 'almaden-bookster' ) ), 400 );
	}

	if ( ! almaden_bookster_can_edit_author_profile( $author_id ) ) {
		wp_send_json_error( array( 'message' => __( 'No tienes permiso para editar este autor.', 'almaden-bookster' ) ), 403 );
	}

	if ( ! isset( $_POST['almaden_author_hero_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_author_hero_nonce'], 'almaden_update_author_hero_background' ) ) {
		wp_send_json_error( array( 'message' => __( 'Validación de seguridad fallida.', 'almaden-bookster' ) ), 403 );
	}

	$mode = isset( $_POST['author_hero_background_mode'] ) ? sanitize_key( wp_unslash( $_POST['author_hero_background_mode'] ) ) : 'color';
	if ( ! in_array( $mode, array( 'image', 'color', 'gradient' ), true ) ) {
		$mode = 'color';
	}

	$author_slug = almaden_bookster_get_author_user_slug( $author_id );
	if ( '' === trim( $author_slug ) ) {
		$author = get_user_by( 'id', $author_id );
		$author_slug = $author ? sanitize_title( $author->display_name ? $author->display_name : $author->user_login ) : 'author';
	}

	$background = almaden_bookster_get_author_hero_background_defaults();
	$background['type'] = $mode;
	$background['overlay_color'] = isset( $_POST['author_hero_background_overlay_color'] ) ? ( sanitize_hex_color( wp_unslash( $_POST['author_hero_background_overlay_color'] ) ) ?: $background['overlay_color'] ) : $background['overlay_color'];
	$background['overlay_opacity'] = isset( $_POST['author_hero_background_overlay_opacity'] ) ? max( 0, min( 1, floatval( wp_unslash( $_POST['author_hero_background_overlay_opacity'] ) ) ) ) : $background['overlay_opacity'];

	if ( 'image' === $mode ) {
		$image_id = isset( $_POST['author_hero_background_image_id'] ) ? absint( $_POST['author_hero_background_image_id'] ) : 0;
		if ( ! empty( $_FILES['author_hero_background_file'] ) && ! empty( $_FILES['author_hero_background_file']['name'] ) ) {
			$uploaded_image_id = almaden_bookster_handle_author_photo_upload( 'author_hero_background_file', $author_slug );
			if ( is_wp_error( $uploaded_image_id ) ) {
				wp_send_json_error( array( 'message' => $uploaded_image_id->get_error_message() ), 500 );
			}

			if ( $uploaded_image_id > 0 ) {
				$image_id = $uploaded_image_id;
			}
		}

		if ( $image_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Debes subir una imagen para el fondo del hero.', 'almaden-bookster' ) ), 400 );
		}

		$background['image_id'] = $image_id;
	} elseif ( 'gradient' === $mode ) {
		$background['gradient_from']  = isset( $_POST['author_hero_gradient_from'] ) ? ( sanitize_hex_color( wp_unslash( $_POST['author_hero_gradient_from'] ) ) ?: $background['gradient_from'] ) : $background['gradient_from'];
		$background['gradient_to']    = isset( $_POST['author_hero_gradient_to'] ) ? ( sanitize_hex_color( wp_unslash( $_POST['author_hero_gradient_to'] ) ) ?: $background['gradient_to'] ) : $background['gradient_to'];
		$background['gradient_angle'] = isset( $_POST['author_hero_gradient_angle'] ) ? max( 0, min( 360, absint( $_POST['author_hero_gradient_angle'] ) ) ) : $background['gradient_angle'];
	} else {
		$background['color'] = isset( $_POST['author_hero_background_color'] ) ? ( sanitize_hex_color( wp_unslash( $_POST['author_hero_background_color'] ) ) ?: $background['color'] ) : $background['color'];
	}

	update_user_meta( $author_id, almaden_bookster_get_author_hero_background_meta_key(), $background );
	update_user_meta( $author_id, almaden_bookster_get_author_profile_flag_meta_key(), 1 );

	$style = almaden_bookster_get_author_hero_background_style( $author_id );
	wp_send_json_success(
		array(
			'background' => $background,
			'style'      => $style,
		)
	);
}
add_action( 'admin_post_almaden_update_author_hero_background', 'almaden_bookster_handle_update_author_hero_background' );
add_action( 'wp_ajax_almaden_update_author_hero_background', 'almaden_bookster_handle_update_author_hero_background' );
add_action( 'wp_ajax_nopriv_almaden_update_author_hero_background', 'almaden_bookster_handle_update_author_hero_background' );

