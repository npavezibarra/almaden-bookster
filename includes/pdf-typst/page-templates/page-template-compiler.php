<?php
/**
 * Orchestrates measured page-template composition during Typst compilation.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_compile_page_templates( &$document, $input, $temp_dir, $query_document ) {
	if ( empty( $document['page_templates'] ) ) {
		return true;
	}
	$flow_context = $document['page_template_context'] ?? array( 'templates' => $document['page_templates'], 'columns_count' => 2, 'columns_gap' => 0.8, 'unit' => 'cm' );
	$template_assets = isset( $document['assets'] ) && is_array( $document['assets'] ) ? $document['assets'] : array();
	$sync_assets = static function () use ( $temp_dir, &$template_assets ) {
		return almaden_bookster_typst_stage_assets( $template_assets, $temp_dir );
	};
	$read_flow_map = static function () use ( $query_document ) {
		return $query_document( '<almaden-flow-report>' );
	};
	$templates = array_values( (array) ( $flow_context['templates'] ?? array() ) );
	$GLOBALS['almaden_bookster_typst_page_template_asset_diagnostics'] =
		almaden_bookster_typst_page_template_asset_diagnostics( $templates );
	$GLOBALS['almaden_bookster_typst_page_template_asset_audit'] =
		almaden_bookster_typst_page_template_asset_audit( $templates );
	$asset_result = $sync_assets();
	if ( is_wp_error( $asset_result ) ) {
		return $asset_result;
	}
	$initial_flow_map = $read_flow_map();
	foreach ( $templates as &$candidate ) {
		if ( '' !== (string) ( $candidate['anchor']['flow_id'] ?? '' ) ) {
			continue;
		}
		$stored_page = (int) ( $candidate['resolved_page'] ?? $candidate['page_number'] ?? 0 );
		$transition = almaden_bookster_typst_page_template_transition_row_on_page( $initial_flow_map, $stored_page );
		if ( ! $transition && $stored_page !== (int) ( $candidate['page_number'] ?? 0 ) ) {
			$transition = almaden_bookster_typst_page_template_transition_row_on_page( $initial_flow_map, (int) $candidate['page_number'] );
		}
		if ( $transition ) {
			$candidate['page_number'] = (int) $transition['page'];
			$candidate['resolved_page'] = (int) $transition['page'];
			$candidate['anchor'] = array( 'flow_id' => (string) $transition['id'] );
			$candidate['_transition_migrated'] = true;
			continue;
		}
		$resolution = almaden_bookster_typst_resolve_page_template( $candidate, $initial_flow_map );
		if ( ! empty( $resolution['applied'] ) ) {
			$candidate = $resolution['template'];
			$candidate['_legacy_migrated'] = true;
		}
	}
	unset( $candidate );
	usort( $templates, static function ( $left, $right ) {
		$left_order = almaden_bookster_typst_page_template_flow_order( $left['anchor']['flow_id'] ?? '' );
		$right_order = almaden_bookster_typst_page_template_flow_order( $right['anchor']['flow_id'] ?? '' );
		return $left_order === $right_order
			? (int) ( $left['resolved_page'] ?? $left['page_number'] ?? 0 ) <=> (int) ( $right['resolved_page'] ?? $right['page_number'] ?? 0 )
			: $left_order <=> $right_order;
	} );

	foreach ( $templates as $template_index => $stored_template ) {
		$current_order = almaden_bookster_typst_page_template_flow_order( $stored_template['anchor']['flow_id'] ?? '' );
		$next_anchor = '';
		for ( $next_index = $template_index + 1, $count = count( $templates ); $next_index < $count; ++$next_index ) {
			$candidate_anchor = (string) ( $templates[ $next_index ]['anchor']['flow_id'] ?? '' );
			$candidate_order = almaden_bookster_typst_page_template_flow_order( $candidate_anchor );
			if ( PHP_INT_MAX !== $candidate_order && $candidate_order > $current_order ) {
				$next_anchor = $candidate_anchor;
				break;
			}
		}
		$plan_key = almaden_bookster_typst_page_template_plan_key( $document['source'], $stored_template, $flow_context, $next_anchor );
		$cached = almaden_bookster_typst_page_template_plan_read( $plan_key );
		if ( is_array( $cached ) ) {
			$cached_source = almaden_bookster_typst_page_template_apply_plan_patch( $document['source'], $cached['patch'] );
			if ( is_string( $cached_source ) && $cached_source !== $document['source'] ) {
				$template_assets = array_merge( $template_assets, (array) ( $cached['assets'] ?? array() ) );
				$result = is_array( $cached['result'] ?? null ) ? $cached['result'] : array();
				$result['debug'] = array_merge( (array) ( $result['debug'] ?? array() ), array( 'plan_cache' => 'hit' ) );
				$GLOBALS['almaden_bookster_typst_page_template_results'][] = $result;
				$document['source'] = $cached_source;
				file_put_contents( $input, $document['source'], LOCK_EX );
				continue;
			}
		}

		$asset_result = $sync_assets();
		if ( is_wp_error( $asset_result ) ) {
			return $asset_result;
		}
		$flow_map = $read_flow_map();
		$resolution = almaden_bookster_typst_resolve_page_template( $stored_template, $flow_map );
		$instance_id = (string) ( $stored_template['instance_id'] ?? $stored_template['id'] ?? '' );
		if ( empty( $resolution['applied'] ) ) {
			$GLOBALS['almaden_bookster_typst_page_template_results'][] = array(
				'instance_id' => $instance_id,
				'requested_page' => (int) ( $stored_template['page_number'] ?? 0 ),
				'resolved_page' => (int) ( $stored_template['resolved_page'] ?? $stored_template['page_number'] ?? 0 ),
				'page' => (int) ( $stored_template['resolved_page'] ?? $stored_template['page_number'] ?? 0 ),
				'flow_rows' => 0,
				'applied' => false,
				'anchor' => $stored_template['anchor'] ?? array(),
				'debug' => array( 'reason' => $resolution['reason'] ?? 'anchor_not_resolved' ),
			);
			continue;
		}
		$template = $resolution['template'];
		$target_page = (int) ( $template['page_number'] ?? 0 );
		$template_flow_map = almaden_bookster_typst_page_template_rows_before_anchor( $flow_map, $next_anchor );
		$flow_rows = almaden_bookster_typst_page_template_target_rows( $template_flow_map, $template );
		if ( empty( $flow_map ) || ! function_exists( 'almaden_bookster_typst_apply_page_template_flow' ) ) {
			return new WP_Error( 'typst_template_flow', 'No se pudo medir el flujo para aplicar las plantillas de página.' );
		}

		$word_probe = array();
		$page_start_probe = almaden_bookster_typst_page_template_prepare_page_start_probe( $document['source'], $template_flow_map, $template );
		if ( ! empty( $page_start_probe['source'] ) ) {
			file_put_contents( $input, $page_start_probe['source'], LOCK_EX );
			$page_start_layout = almaden_bookster_typst_page_template_page_start_layout( $document['source'], $template_flow_map, $template, $query_document( '<almaden-template-page-start-report>' ) );
			if ( ! empty( $page_start_layout ) ) {
				$layout_probe = almaden_bookster_typst_page_template_prepare_layout_probe( $document['source'], $flow_context, $template, $page_start_layout );
				if ( ! empty( $layout_probe['source'] ) ) {
					file_put_contents( $input, $layout_probe['source'], LOCK_EX );
					$page_start_layout = almaden_bookster_typst_page_template_refine_layout( $page_start_layout, $layout_probe, $query_document( '<almaden-template-probe-report>' ) );
				}
				if ( 'upper-bottom-split' === almaden_bookster_typst_page_template_layout_mode( $template ) ) {
					$right_probe = almaden_bookster_typst_page_template_prepare_upper_bottom_right_layout_probe( $document['source'], $flow_context, $template, $page_start_layout );
					if ( ! empty( $right_probe['source'] ) ) {
						file_put_contents( $input, $right_probe['source'], LOCK_EX );
						$page_start_layout = almaden_bookster_typst_page_template_refine_upper_bottom_right_layout( $right_probe, $query_document( '<almaden-template-probe-report>' ) );
					}
				}
				$word_probe = array( 'layout' => $page_start_layout, 'extra_page_ids' => $page_start_layout['extra_page_ids'] ?? array() );
			}
		}
		if ( empty( $word_probe ) ) {
			$word_probe = almaden_bookster_typst_page_template_prepare_word_probe( $document['source'], $flow_context, $template_flow_map, $template );
			if ( ! empty( $word_probe['source'] ) ) {
				file_put_contents( $input, $word_probe['source'], LOCK_EX );
				$cut = almaden_bookster_typst_page_template_probe_cut( $word_probe, $query_document( '<almaden-template-probe-report>' ) );
				if ( ! empty( $cut ) ) {
					$word_probe['cut'] = $cut;
					if ( 'upper-bottom-split' === almaden_bookster_typst_page_template_layout_mode( $template ) ) {
						$right_probe = almaden_bookster_typst_page_template_prepare_upper_bottom_right_probe( $document['source'], $flow_context, $template_flow_map, $template, $cut );
						if ( ! empty( $right_probe['source'] ) ) {
							file_put_contents( $input, $right_probe['source'], LOCK_EX );
							$word_probe['layout'] = almaden_bookster_typst_page_template_refine_upper_bottom_right_layout( $right_probe, $query_document( '<almaden-template-probe-report>' ) );
						}
					}
				}
			}
		}

		$source_before = $document['source'];
		$assets_before = $template_assets;
		$updated_source = almaden_bookster_typst_apply_page_template_flow( $source_before, $flow_context, $template_flow_map, $template, $word_probe, $template_assets );
		$result = array(
			'instance_id' => $instance_id,
			'requested_page' => (int) ( $stored_template['page_number'] ?? 0 ),
			'resolved_page' => $target_page,
			'page' => $target_page,
			'flow_rows' => count( $flow_rows ),
			'applied' => $updated_source !== $source_before,
			'anchor' => $template['anchor'],
			'legacy_migrated' => ! empty( $resolution['legacy_migrated'] ) || ! empty( $stored_template['_legacy_migrated'] ) || ! empty( $stored_template['_transition_migrated'] ),
			'debug' => $GLOBALS['almaden_bookster_typst_page_template_debug'] ?? array(),
		);
		$GLOBALS['almaden_bookster_typst_page_template_results'][] = $result;
		if ( $updated_source === $source_before ) {
			file_put_contents( $input, $source_before, LOCK_EX );
			continue;
		}
		$added_assets = array();
		foreach ( $template_assets as $name => $path ) {
			if ( ! isset( $assets_before[ $name ] ) || $assets_before[ $name ] !== $path ) {
				$added_assets[ $name ] = $path;
			}
		}
		almaden_bookster_typst_page_template_plan_write( $plan_key, array(
			'patch' => almaden_bookster_typst_page_template_plan_patch( $source_before, $updated_source ),
			'result' => $result,
			'assets' => $added_assets,
		) );
		$document['source'] = $updated_source;
		file_put_contents( $input, $document['source'], LOCK_EX );
	}

	$asset_result = $sync_assets();
	if ( is_wp_error( $asset_result ) ) {
		return $asset_result;
	}
	$GLOBALS['almaden_bookster_typst_page_flow_map'] = $read_flow_map();
	$document['assets'] = $template_assets;
	return true;
}
