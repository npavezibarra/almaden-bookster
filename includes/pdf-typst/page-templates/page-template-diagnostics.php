<?php
/**
 * Read-only asset diagnostics for Typst page-template image slots.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_page_template_slot_asset_diagnostic( $template, $slot, $asset_mode = 'original' ) {
	$slot = is_array( $slot ) ? $slot : array();
	$template = is_array( $template ) ? $template : array();
	$attachment_id = almaden_bookster_typst_page_template_attachment_id_from_slot( $slot );
	$source_url = function_exists( 'almaden_bookster_typst_resolve_image_url_for_asset_mode' )
		? almaden_bookster_typst_resolve_image_url_for_asset_mode( $slot, $asset_mode )
		: '';
	$source_path = almaden_bookster_typst_page_template_slot_source_path( $slot, $asset_mode );
	$assigned = $attachment_id > 0
		|| '' !== trim( (string) ( $slot['url'] ?? '' ) )
		|| '' !== trim( (string) ( $slot['preview_url'] ?? '' ) )
		|| '' !== trim( (string) ( $slot['original_url'] ?? '' ) );
	$renderable = '' !== $source_path && is_file( $source_path );

	if ( ! $assigned ) {
		$reason = 'unassigned';
	} elseif ( '' === $source_url ) {
		$reason = 'missing_source_url';
	} elseif ( ! $renderable ) {
		$reason = 'source_file_unavailable';
	} else {
		$reason = 'renderable';
	}

	return array(
		'instance_id' => (string) ( $template['instance_id'] ?? $template['id'] ?? '' ),
		'page_number' => (int) ( $template['resolved_page'] ?? $template['page_number'] ?? 0 ),
		'template_id' => almaden_bookster_typst_page_template_normalize_slot_id( $template['template_id'] ?? '' ),
		'slot_id' => almaden_bookster_typst_page_template_normalize_slot_id( $slot['id'] ?? '' ),
		'asset_mode' => (string) $asset_mode,
		'assigned' => $assigned,
		'attachment_id' => $attachment_id,
		'source_url' => $source_url,
		'source_path' => $source_path,
		'renderable' => $renderable,
		'reason' => $reason,
	);
}

function almaden_bookster_typst_page_template_asset_diagnostics( $templates, $asset_mode = 'original' ) {
	$diagnostics = array();
	foreach ( (array) $templates as $template ) {
		if ( ! is_array( $template ) ) {
			continue;
		}
		foreach ( (array) ( $template['slots'] ?? array() ) as $slot ) {
			if ( ! is_array( $slot ) ) {
				continue;
			}
			$diagnostics[] = almaden_bookster_typst_page_template_slot_asset_diagnostic( $template, $slot, $asset_mode );
		}
	}

	return $diagnostics;
}

function almaden_bookster_typst_page_template_asset_audit( $templates, $asset_mode = 'original' ) {
	$diagnostics = almaden_bookster_typst_page_template_asset_diagnostics( $templates, $asset_mode );
	$attachment_instances = array();
	$page_instances = array();
	$counts = array(
		'total'       => count( $diagnostics ),
		'assigned'    => 0,
		'renderable'  => 0,
		'unresolved'  => 0,
		'unassigned'  => 0,
	);

	foreach ( $diagnostics as $diagnostic ) {
		if ( ! empty( $diagnostic['assigned'] ) ) {
			++$counts['assigned'];
		} else {
			++$counts['unassigned'];
		}
		if ( ! empty( $diagnostic['renderable'] ) ) {
			++$counts['renderable'];
		} elseif ( ! empty( $diagnostic['assigned'] ) ) {
			++$counts['unresolved'];
		}

		$attachment_id = (int) ( $diagnostic['attachment_id'] ?? 0 );
		if ( $attachment_id > 0 ) {
			$attachment_instances[ $attachment_id ][] = array(
				'instance_id' => $diagnostic['instance_id'],
				'page_number' => $diagnostic['page_number'],
				'slot_id'     => $diagnostic['slot_id'],
			);
		}
		$page_number = (int) ( $diagnostic['page_number'] ?? 0 );
		if ( $page_number > 0 ) {
			$page_instances[ $page_number ][] = array(
				'instance_id' => $diagnostic['instance_id'],
				'template_id' => $diagnostic['template_id'],
				'slot_id'     => $diagnostic['slot_id'],
				'attachment_id' => (int) ( $diagnostic['attachment_id'] ?? 0 ),
			);
		}
	}

	$duplicates = array();
	foreach ( $attachment_instances as $attachment_id => $instances ) {
		if ( count( $instances ) > 1 ) {
			$duplicates[] = array(
				'attachment_id' => (int) $attachment_id,
				'instances'     => $instances,
			);
		}
	}

	$page_collisions = array();
	foreach ( $page_instances as $page_number => $instances ) {
		if ( count( $instances ) > 1 ) {
			$page_collisions[] = array(
				'page_number' => (int) $page_number,
				'instances' => $instances,
			);
		}
	}

	return array(
		'version'      => 1,
		'scope'        => 'full-book',
		'asset_mode'   => (string) $asset_mode,
		'counts'       => $counts,
		'duplicates'   => $duplicates,
		'page_collisions' => $page_collisions,
		'diagnostics'  => $diagnostics,
	);
}
