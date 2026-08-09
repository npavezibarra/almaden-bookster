<?php
/**
 * Slot helpers for Typst page templates.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_page_template_normalize_slot_id( $value ) {
	return strtolower( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $value ) );
}

function almaden_bookster_typst_page_template_escape_string( $value ) {
	if ( function_exists( 'almaden_bookster_typst_escape_string' ) ) {
		return almaden_bookster_typst_escape_string( $value );
	}

	return str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), (string) $value );
}

function almaden_bookster_typst_page_template_sanitize_url( $value ) {
	$value = trim( (string) $value );
	if ( function_exists( 'esc_url_raw' ) ) {
		return esc_url_raw( $value );
	}

	return $value;
}

function almaden_bookster_typst_page_template_absint( $value ) {
	if ( function_exists( 'absint' ) ) {
		return absint( $value );
	}

	return max( 0, (int) $value );
}

function almaden_bookster_typst_page_template_filetype( $file_path ) {
	if ( function_exists( 'wp_check_filetype' ) ) {
		return wp_check_filetype( $file_path );
	}
	if ( ! is_file( $file_path ) ) {
		return array(
			'ext'  => strtolower( (string) pathinfo( $file_path, PATHINFO_EXTENSION ) ),
			'type' => '',
		);
	}

	return array(
		'type' => function_exists( 'mime_content_type' ) ? mime_content_type( $file_path ) : '',
	);
}

function almaden_bookster_typst_page_template_attachment_source_path( $attachment_id ) {
	return function_exists( 'almaden_bookster_typst_resolve_attachment_original_path' )
		? almaden_bookster_typst_resolve_attachment_original_path( $attachment_id )
		: '';
}

function almaden_bookster_typst_page_template_attachment_id_from_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return 0;
	}

	$attachment_id = 0;
	if ( function_exists( 'attachment_url_to_postid' ) ) {
		$attachment_id = attachment_url_to_postid( esc_url_raw( $url ) );
		if ( $attachment_id <= 0 && preg_match( '/-scaled(?=\.[a-zA-Z0-9]+$)/', $url ) ) {
			$attachment_id = attachment_url_to_postid( preg_replace( '/-scaled(?=\.[a-zA-Z0-9]+$)/', '', $url ) );
		}
	}

	return almaden_bookster_typst_page_template_absint( $attachment_id );
}

function almaden_bookster_typst_page_template_attachment_id_from_slot( $slot ) {
	if ( ! is_array( $slot ) ) {
		return 0;
	}

	$attachment_id = almaden_bookster_typst_page_template_absint( $slot['attachment_id'] ?? 0 );
	if ( $attachment_id > 0 ) {
		return $attachment_id;
	}

	foreach ( array( 'original_url', 'url', 'preview_url' ) as $key ) {
		$attachment_id = almaden_bookster_typst_page_template_attachment_id_from_url( $slot[ $key ] ?? '' );
		if ( $attachment_id > 0 ) {
			return $attachment_id;
		}
	}

	return 0;
}

/**
 * Resolve a persisted WordPress media URL to its local uploads path.
 *
 * This is intentionally independent of attachment_url_to_postid(). The latter
 * can return zero for resized, scaled, or legacy media URLs even though the
 * file still exists locally and is valid for the Typst compilation root.
 */
function almaden_bookster_typst_page_template_source_path_from_url( $url ) {
	return function_exists( 'almaden_bookster_typst_resolve_upload_path_from_url' )
		? almaden_bookster_typst_resolve_upload_path_from_url( $url )
		: '';
}

function almaden_bookster_typst_page_template_slot_source_path( $slot, $asset_mode = 'original' ) {
	if ( function_exists( 'almaden_bookster_typst_resolve_image_path_for_asset_mode' ) ) {
		return almaden_bookster_typst_resolve_image_path_for_asset_mode( $slot, $asset_mode );
	}

	$attachment_id = almaden_bookster_typst_page_template_attachment_id_from_slot( $slot );
	if ( $attachment_id > 0 ) {
		$source_path = almaden_bookster_typst_page_template_attachment_source_path( $attachment_id );
		if ( '' !== $source_path ) {
			return $source_path;
		}
	}

	if ( is_array( $slot ) ) {
		foreach ( array( 'original_url', 'url', 'preview_url' ) as $key ) {
			$source_path = almaden_bookster_typst_page_template_source_path_from_url( $slot[ $key ] ?? '' );
			if ( '' !== $source_path ) {
				return $source_path;
			}
		}
	}

	return '';
}

function almaden_bookster_typst_page_template_asset_path_from_source( $source_path, &$assets ) {
	if ( '' === $source_path ) {
		return '';
	}

	$filetype = almaden_bookster_typst_page_template_filetype( $source_path );
	$extension = '';
	if ( ! empty( $filetype['ext'] ) ) {
		$extension = '.' . strtolower( ltrim( (string) $filetype['ext'], '.' ) );
	} else {
		$path_info = pathinfo( $source_path );
		if ( ! empty( $path_info['extension'] ) ) {
			$extension = '.' . strtolower( ltrim( (string) $path_info['extension'], '.' ) );
		}
	}
	if ( '' === $extension ) {
		$extension = '.img';
	}

	// A missing legacy asset must remain deterministic. Using time() here made
	// an unchanged manuscript produce a different Typst source on every request,
	// which prevented both browser and server preview caches from ever hitting.
	$file_exists = is_file( $source_path );
	$file_mtime  = $file_exists ? (int) filemtime( $source_path ) : 0;
	$file_size   = $file_exists ? (int) filesize( $source_path ) : 0;
	$cache_key = hash( 'sha256', $source_path . '|' . $file_mtime . '|' . $file_size ) . $extension;
	$assets[ $cache_key ] = $source_path;

	return 'assets/' . $cache_key;
}

function almaden_bookster_typst_page_template_attachment_asset_path( $attachment_id, &$assets ) {
	return almaden_bookster_typst_page_template_asset_path_from_source(
		almaden_bookster_typst_page_template_attachment_source_path( $attachment_id ),
		$assets
	);
}

function almaden_bookster_typst_page_template_definition_slots( $template_id ) {
	$definition = almaden_bookster_typst_get_page_template_definition( $template_id );
	if ( ! is_array( $definition ) || empty( $definition['slots'] ) || ! is_array( $definition['slots'] ) ) {
		return array();
	}

	$slots = array();
	foreach ( $definition['slots'] as $index => $slot ) {
		if ( ! is_array( $slot ) ) {
			continue;
		}
		$slot_id = almaden_bookster_typst_page_template_normalize_slot_id( $slot['id'] ?? '' );
		if ( '' === $slot_id ) {
			$slot_id = 'slot-' . ( $index + 1 );
		}
		$slots[] = array(
			'id'    => $slot_id,
			'label' => trim( (string) ( $slot['label'] ?? ( 'Slot ' . ( $index + 1 ) ) ) ),
			'kind'  => strtolower( trim( (string) ( $slot['kind'] ?? 'image' ) ) ) ?: 'image',
		);
	}

	return $slots;
}

function almaden_bookster_typst_page_template_normalize_slots( $template_id, $slots_value ) {
	$definition_slots = almaden_bookster_typst_page_template_definition_slots( $template_id );
	$slots_value = is_array( $slots_value ) ? $slots_value : array();
	$existing = array();
	foreach ( $slots_value as $slot ) {
		if ( ! is_array( $slot ) ) {
			continue;
		}
		$slot_id = almaden_bookster_typst_page_template_normalize_slot_id( $slot['id'] ?? '' );
		if ( '' === $slot_id ) {
			continue;
		}
		$existing[ $slot_id ] = $slot;
	}

	$normalized = array();
	$seen = array();
	$source_slots = ! empty( $definition_slots ) ? $definition_slots : array_values( $existing );

	foreach ( $source_slots as $index => $definition_slot ) {
		if ( ! is_array( $definition_slot ) ) {
			continue;
		}
		$slot_id = almaden_bookster_typst_page_template_normalize_slot_id( $definition_slot['id'] ?? '' );
		if ( '' === $slot_id || isset( $seen[ $slot_id ] ) ) {
			continue;
		}
		$merged = array_merge(
			array(
				'id'            => $slot_id,
				'label'         => trim( (string) ( $definition_slot['label'] ?? ( 'Slot ' . ( $index + 1 ) ) ) ),
				'kind'          => strtolower( trim( (string) ( $definition_slot['kind'] ?? 'image' ) ) ) ?: 'image',
				'attachment_id' => 0,
				'url'           => '',
				'preview_url'   => '',
				'original_url'  => '',
			),
			is_array( $existing[ $slot_id ] ?? null ) ? $existing[ $slot_id ] : array()
		);
		$merged['id'] = $slot_id;
		$merged['label'] = trim( (string) ( $merged['label'] ?? $definition_slot['label'] ?? ( 'Slot ' . ( $index + 1 ) ) ) );
		$merged['kind'] = strtolower( trim( (string) ( $merged['kind'] ?? 'image' ) ) ) ?: 'image';
		$merged['attachment_id'] = almaden_bookster_typst_page_template_absint( $merged['attachment_id'] ?? 0 );
		if ( $merged['attachment_id'] <= 0 ) {
			$merged['attachment_id'] = almaden_bookster_typst_page_template_attachment_id_from_slot( $merged );
		}
		$merged['url'] = almaden_bookster_typst_page_template_sanitize_url( $merged['url'] ?? '' );
		$merged['preview_url'] = almaden_bookster_typst_page_template_sanitize_url( $merged['preview_url'] ?? '' );
		$merged['original_url'] = almaden_bookster_typst_page_template_sanitize_url( $merged['original_url'] ?? $merged['url'] ?? '' );
		$normalized[] = $merged;
		$seen[ $slot_id ] = true;
	}

	foreach ( $existing as $slot_id => $slot ) {
		if ( isset( $seen[ $slot_id ] ) ) {
			continue;
		}
		$normalized[] = array(
			'id'            => $slot_id,
			'label'         => trim( (string) ( $slot['label'] ?? $slot_id ) ),
			'kind'          => strtolower( trim( (string) ( $slot['kind'] ?? 'image' ) ) ) ?: 'image',
			'attachment_id' => almaden_bookster_typst_page_template_absint( $slot['attachment_id'] ?? 0 ),
			'url'           => almaden_bookster_typst_page_template_sanitize_url( $slot['url'] ?? '' ),
			'preview_url'   => almaden_bookster_typst_page_template_sanitize_url( $slot['preview_url'] ?? '' ),
			'original_url'  => almaden_bookster_typst_page_template_sanitize_url( $slot['original_url'] ?? $slot['url'] ?? '' ),
		);
		$last_index = count( $normalized ) - 1;
		if ( $normalized[ $last_index ]['attachment_id'] <= 0 ) {
			$normalized[ $last_index ]['attachment_id'] = almaden_bookster_typst_page_template_attachment_id_from_slot( $normalized[ $last_index ] );
		}
	}

	return array_values( $normalized );
}

function almaden_bookster_typst_page_template_slot_anchor_id( $template, $slot ) {
	$instance_id = almaden_bookster_typst_page_template_normalize_slot_id( $template['instance_id'] ?? $template['id'] ?? 'template' );
	$slot_id = almaden_bookster_typst_page_template_normalize_slot_id( $slot['id'] ?? 'slot' );
	return 'almaden-template-slot-' . $instance_id . '-' . $slot_id;
}

function almaden_bookster_typst_page_template_attachment_data_uri( $attachment_id ) {
	$source_path = almaden_bookster_typst_page_template_attachment_source_path( $attachment_id );
	if ( '' === $source_path ) {
		return '';
	}

	$filetype = almaden_bookster_typst_page_template_filetype( $source_path );
	$mime_type = ! empty( $filetype['type'] ) ? $filetype['type'] : mime_content_type( $source_path );
	if ( empty( $mime_type ) ) {
		$mime_type = 'application/octet-stream';
	}

	$contents = file_get_contents( $source_path );
	if ( false === $contents ) {
		return '';
	}

	return 'data:' . $mime_type . ';base64,' . base64_encode( $contents );
}

function almaden_bookster_typst_page_template_slot_visual( $template, $slot ) {
	$empty_assets = array();
	return almaden_bookster_typst_page_template_slot_visual_with_assets( $template, $slot, $empty_assets );
}

function almaden_bookster_typst_page_template_slot_visual_with_assets( $template, $slot, &$assets, $asset_mode = 'original' ) {
	$asset_path = almaden_bookster_typst_page_template_asset_path_from_source(
		almaden_bookster_typst_page_template_slot_source_path( $slot, $asset_mode ),
		$assets
	);
	if ( '' !== $asset_path ) {
		return '#image("' . almaden_bookster_typst_page_template_escape_string( $asset_path ) . '", width: 100%, height: 100%, fit: "cover")';
	}

	return '#rect(width: 100%, height: 100%, fill: rgb("ff9d00"), stroke: 0.5pt + rgb("a85c00"))';
}

function almaden_bookster_typst_page_template_render_slot( $template, $slot, &$assets = array(), $asset_mode = 'original' ) {
	$template_id = almaden_bookster_typst_page_template_normalize_slot_id( $template['template_id'] ?? '' );
	$slot_id = almaden_bookster_typst_page_template_normalize_slot_id( $slot['id'] ?? '' );
	$anchor_id = almaden_bookster_typst_page_template_slot_anchor_id( $template, $slot );
	$attachment_id = almaden_bookster_typst_page_template_attachment_id_from_slot( $slot );
	$meta = array(
		'instance_id'  => (string) ( $template['instance_id'] ?? $template['id'] ?? '' ),
		'page_number'   => (int) ( $template['page_number'] ?? 0 ),
		'template_id'   => $template_id,
		'slot_id'       => $slot_id,
		'slot_kind'     => strtolower( trim( (string) ( $slot['kind'] ?? 'image' ) ) ) ?: 'image',
		'attachment_id' => $attachment_id,
		'label'         => trim( (string) ( $slot['label'] ?? $slot_id ) ),
	);

	$entries = array();
	foreach ( $meta as $key => $value ) {
		if ( is_int( $value ) ) {
			$entries[] = $key . ': ' . $value;
		} else {
			$entries[] = $key . ': "' . almaden_bookster_typst_page_template_escape_string( (string) $value ) . '"';
		}
	}

	$metadata = '#metadata((' . implode( ', ', $entries ) . ')) <' . $anchor_id . '>';
	$visual = almaden_bookster_typst_page_template_slot_visual_with_assets( $template, $slot, $assets, $asset_mode );

	return "#block(width: 100%, height: 100%)[\n$metadata\n$visual\n]";
}
