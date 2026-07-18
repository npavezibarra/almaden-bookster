<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_author_profile_photo_meta_key() {
	return '_almaden_author_profile_photo_id';
}

function almaden_bookster_get_author_backcover_meta_key() {
	return '_almaden_author_profile_backcover_id';
}

function almaden_bookster_get_author_hero_background_meta_key() {
	return '_almaden_author_hero_background';
}

function almaden_bookster_get_author_social_links_meta_key() {
	return '_almaden_author_social_links';
}

function almaden_bookster_get_author_profile_flag_meta_key() {
	return '_almaden_author_profile_enabled';
}

function almaden_bookster_get_author_slug_meta_key() {
	return '_almaden_author_slug';
}

function almaden_bookster_get_author_hero_background_defaults() {
	return array(
		'type'            => 'color',
		'image_id'        => 0,
		'color'           => '#ebff43',
		'gradient_from'   => '#ebff43',
		'gradient_to'     => '#f5f5ef',
		'gradient_angle'  => 90,
		'overlay_color'   => '#000000',
		'overlay_opacity' => 0,
	);
}

function almaden_bookster_hex_to_rgb_triplet( $hex_color ) {
	$hex_color = sanitize_hex_color( $hex_color );
	if ( ! $hex_color ) {
		return false;
	}

	$hex_color = ltrim( $hex_color, '#' );
	if ( 3 === strlen( $hex_color ) ) {
		$hex_color = $hex_color[0] . $hex_color[0] . $hex_color[1] . $hex_color[1] . $hex_color[2] . $hex_color[2];
	}

	if ( 6 !== strlen( $hex_color ) ) {
		return false;
	}

	return array(
		hexdec( substr( $hex_color, 0, 2 ) ),
		hexdec( substr( $hex_color, 2, 2 ) ),
		hexdec( substr( $hex_color, 4, 2 ) ),
	);
}

function almaden_bookster_get_author_hero_background_settings( $user_id ) {
	$user_id = absint( $user_id );
	if ( $user_id <= 0 ) {
		return almaden_bookster_get_author_hero_background_defaults();
	}

	$saved = get_user_meta( $user_id, almaden_bookster_get_author_hero_background_meta_key(), true );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return wp_parse_args( $saved, almaden_bookster_get_author_hero_background_defaults() );
}

function almaden_bookster_sanitize_author_hero_background_settings( $raw_settings ) {
	$raw_settings = is_array( $raw_settings ) ? $raw_settings : array();
	$defaults     = almaden_bookster_get_author_hero_background_defaults();

	$type = isset( $raw_settings['type'] ) ? sanitize_key( $raw_settings['type'] ) : $defaults['type'];
	if ( ! in_array( $type, array( 'image', 'color', 'gradient' ), true ) ) {
		$type = $defaults['type'];
	}

	$image_id = isset( $raw_settings['image_id'] ) ? absint( $raw_settings['image_id'] ) : 0;
	$color    = isset( $raw_settings['color'] ) ? sanitize_hex_color( wp_unslash( $raw_settings['color'] ) ) : '';
	$from     = isset( $raw_settings['gradient_from'] ) ? sanitize_hex_color( wp_unslash( $raw_settings['gradient_from'] ) ) : '';
	$to       = isset( $raw_settings['gradient_to'] ) ? sanitize_hex_color( wp_unslash( $raw_settings['gradient_to'] ) ) : '';
	$angle    = isset( $raw_settings['gradient_angle'] ) ? absint( $raw_settings['gradient_angle'] ) : $defaults['gradient_angle'];
	$angle    = max( 0, min( 360, $angle ) );
	$overlay_color   = isset( $raw_settings['overlay_color'] ) ? sanitize_hex_color( wp_unslash( $raw_settings['overlay_color'] ) ) : '';
	$overlay_opacity = isset( $raw_settings['overlay_opacity'] ) ? floatval( wp_unslash( $raw_settings['overlay_opacity'] ) ) : $defaults['overlay_opacity'];
	$overlay_opacity = max( 0, min( 1, $overlay_opacity ) );

	return array(
		'type'            => $type,
		'image_id'        => $image_id,
		'color'           => $color ? $color : $defaults['color'],
		'gradient_from'   => $from ? $from : $defaults['gradient_from'],
		'gradient_to'     => $to ? $to : $defaults['gradient_to'],
		'gradient_angle'  => $angle,
		'overlay_color'   => $overlay_color ? $overlay_color : $defaults['overlay_color'],
		'overlay_opacity' => $overlay_opacity,
	);
}

function almaden_bookster_get_author_hero_background_style( $user_id ) {
	$settings = almaden_bookster_get_author_hero_background_settings( $user_id );
	$type     = isset( $settings['type'] ) ? sanitize_key( $settings['type'] ) : 'color';

	switch ( $type ) {
		case 'image':
			$image_id = isset( $settings['image_id'] ) ? absint( $settings['image_id'] ) : 0;
			if ( $image_id > 0 ) {
				$image_url = wp_get_attachment_image_url( $image_id, 'full' );
				if ( $image_url ) {
					$overlay_color   = isset( $settings['overlay_color'] ) ? sanitize_hex_color( $settings['overlay_color'] ) : '';
					$overlay_opacity = isset( $settings['overlay_opacity'] ) ? floatval( $settings['overlay_opacity'] ) : 0;
					$overlay_opacity = max( 0, min( 1, $overlay_opacity ) );
					$overlay_layers  = '';
					if ( $overlay_color && $overlay_opacity > 0 ) {
						$rgb = almaden_bookster_hex_to_rgb_triplet( $overlay_color );
						if ( $rgb ) {
							$overlay_layers = sprintf( 'linear-gradient(rgba(%1$d,%2$d,%3$d,%4$.3f),rgba(%1$d,%2$d,%3$d,%4$.3f)),', $rgb[0], $rgb[1], $rgb[2], $overlay_opacity );
						}
					}

					return sprintf(
						'background-image:%1$surl(%2$s);background-position:center;background-repeat:no-repeat;background-size:cover;',
						$overlay_layers,
						esc_url_raw( $image_url )
					);
				}
			}
			break;

		case 'gradient':
			$angle = isset( $settings['gradient_angle'] ) ? absint( $settings['gradient_angle'] ) : 90;
			$angle = max( 0, min( 360, $angle ) );
			$from  = isset( $settings['gradient_from'] ) ? sanitize_hex_color( $settings['gradient_from'] ) : '';
			$to    = isset( $settings['gradient_to'] ) ? sanitize_hex_color( $settings['gradient_to'] ) : '';
			if ( $from && $to ) {
				return sprintf( 'background-image:linear-gradient(%1$ddeg,%2$s,%3$s);', $angle, esc_attr( $from ), esc_attr( $to ) );
			}
			break;

		case 'color':
		default:
			$color = isset( $settings['color'] ) ? sanitize_hex_color( $settings['color'] ) : '';
			if ( $color ) {
				return sprintf( 'background-color:%s;', esc_attr( $color ) );
			}
			break;
	}

	$default_color = isset( $settings['color'] ) ? sanitize_hex_color( $settings['color'] ) : '';
	if ( $default_color ) {
		return sprintf( 'background-color:%s;', esc_attr( $default_color ) );
	}

	return 'background-color:#ebff43;';
}

function almaden_bookster_filter_author_upload_dir( $uploads ) {
	global $almaden_bookster_author_upload_slug;

	$author_slug = isset( $almaden_bookster_author_upload_slug ) ? sanitize_title( (string) $almaden_bookster_author_upload_slug ) : '';
	if ( '' === $author_slug ) {
		return $uploads;
	}

	$current_subdir = isset( $uploads['subdir'] ) ? trim( (string) $uploads['subdir'], '/' ) : '';
	$author_subdir  = 'authors/' . $author_slug;
	$new_subdir     = '' !== $current_subdir ? $current_subdir . '/' . $author_subdir : $author_subdir;

	$uploads['subdir'] = '/' . ltrim( $new_subdir, '/' );
	$uploads['path']   = trailingslashit( $uploads['basedir'] ) . ltrim( $new_subdir, '/' );
	$uploads['url']    = trailingslashit( $uploads['baseurl'] ) . ltrim( $new_subdir, '/' );

	wp_mkdir_p( $uploads['path'] );

	return $uploads;
}

function almaden_bookster_handle_author_photo_upload( $file_key, $author_slug = '' ) {
	if ( empty( $_FILES[ $file_key ] ) || empty( $_FILES[ $file_key ]['name'] ) ) {
		return 0;
	}

	if ( ! function_exists( 'media_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	global $almaden_bookster_author_upload_slug;
	$previous_upload_slug = isset( $almaden_bookster_author_upload_slug ) ? $almaden_bookster_author_upload_slug : '';

	$almaden_bookster_author_upload_slug = sanitize_title( (string) $author_slug );
	add_filter( 'upload_dir', 'almaden_bookster_filter_author_upload_dir' );

	$attachment_id = media_handle_upload( $file_key, 0 );

	remove_filter( 'upload_dir', 'almaden_bookster_filter_author_upload_dir' );
	$almaden_bookster_author_upload_slug = $previous_upload_slug;

	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	return absint( $attachment_id );
}

function almaden_bookster_get_author_social_link_defaults() {
	return array(
		'x'         => '',
		'facebook'  => '',
		'instagram' => '',
		'linkedin'  => '',
	);
}

function almaden_bookster_get_author_social_links( $user_id ) {
	$user_id = absint( $user_id );
	if ( $user_id <= 0 ) {
		return almaden_bookster_get_author_social_link_defaults();
	}

	$saved = get_user_meta( $user_id, almaden_bookster_get_author_social_links_meta_key(), true );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return wp_parse_args( $saved, almaden_bookster_get_author_social_link_defaults() );
}

function almaden_bookster_get_author_profile_photo_id( $user_id ) {
	$user_id = absint( $user_id );
	if ( $user_id <= 0 ) {
		return 0;
	}

	return absint( get_user_meta( $user_id, almaden_bookster_get_author_profile_photo_meta_key(), true ) );
}

function almaden_bookster_get_author_backcover_id( $user_id ) {
	$user_id = absint( $user_id );
	if ( $user_id <= 0 ) {
		return 0;
	}

	return absint( get_user_meta( $user_id, almaden_bookster_get_author_backcover_meta_key(), true ) );
}

