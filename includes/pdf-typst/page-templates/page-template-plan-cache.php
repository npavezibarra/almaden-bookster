<?php
/**
 * Incremental cache for previously measured page-template source patches.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_page_template_plan_cache_dir() {
	$base = function_exists( 'get_temp_dir' ) ? get_temp_dir() : sys_get_temp_dir();
	return rtrim( (string) $base, '/\\' ) . '/almaden-bookster-template-plans-v1';
}

function almaden_bookster_typst_page_template_plan_dependency( $source, $next_anchor = '' ) {
	$source = (string) $source;
	$next_anchor = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $next_anchor );
	if ( '' === $next_anchor ) {
		return $source;
	}
	$marker = '<' . $next_anchor . '>';
	$offset = strpos( $source, $marker );
	return false === $offset ? $source : substr( $source, 0, $offset + strlen( $marker ) );
}

function almaden_bookster_typst_page_template_plan_key( $source, $template, $context, $next_anchor = '' ) {
	$cache_context = (array) $context;
	unset( $cache_context['templates'] );
	$cache_template = (array) $template;
	unset( $cache_template['resolved_page'] );
	foreach ( array_keys( $cache_template ) as $key ) {
		if ( 0 === strpos( (string) $key, '_' ) ) {
			unset( $cache_template[ $key ] );
		}
	}
	static $implementation = '';
	if ( '' === $implementation ) {
		$implementation_files = array( 'page-template-compiler.php', 'page-template-composer.php', 'page-template-layout-probe.php', 'page-template-word-flow.php', 'page-template-placeholder.php' );
		$implementation = hash( 'sha256', implode( '|', array_map( static function ( $file ) {
			$path = __DIR__ . '/' . $file;
			return is_file( $path ) ? hash_file( 'sha256', $path ) : $file;
		}, $implementation_files ) ) );
	}
	$payload = array(
		'version' => 1,
		'implementation' => $implementation,
		'dependency' => almaden_bookster_typst_page_template_plan_dependency( $source, $next_anchor ),
		'template' => $cache_template,
		'context' => $cache_context,
		'next_anchor' => (string) $next_anchor,
	);
	$encoded = function_exists( 'wp_json_encode' ) ? wp_json_encode( $payload ) : json_encode( $payload );
	return hash( 'sha256', (string) $encoded );
}

function almaden_bookster_typst_page_template_plan_patch( $before, $after ) {
	$before = (string) $before;
	$after = (string) $after;
	$before_length = strlen( $before );
	$after_length = strlen( $after );
	$prefix = 0;
	$prefix_limit = min( $before_length, $after_length );
	while ( $prefix < $prefix_limit && $before[ $prefix ] === $after[ $prefix ] ) {
		++$prefix;
	}
	$suffix = 0;
	$suffix_limit = min( $before_length - $prefix, $after_length - $prefix );
	while ( $suffix < $suffix_limit && $before[ $before_length - $suffix - 1 ] === $after[ $after_length - $suffix - 1 ] ) {
		++$suffix;
	}
	$removed = substr( $before, $prefix, $before_length - $prefix - $suffix );
	return array(
		'offset' => $prefix,
		'remove_length' => strlen( $removed ),
		'remove_hash' => hash( 'sha256', $removed ),
		'replacement' => substr( $after, $prefix, $after_length - $prefix - $suffix ),
	);
}

function almaden_bookster_typst_page_template_apply_plan_patch( $source, $patch ) {
	$source = (string) $source;
	$offset = max( 0, (int) ( $patch['offset'] ?? -1 ) );
	$remove_length = max( 0, (int) ( $patch['remove_length'] ?? -1 ) );
	if ( $offset > strlen( $source ) || $offset + $remove_length > strlen( $source ) ) {
		return null;
	}
	$removed = substr( $source, $offset, $remove_length );
	if ( ! hash_equals( (string) ( $patch['remove_hash'] ?? '' ), hash( 'sha256', $removed ) ) ) {
		return null;
	}
	return substr( $source, 0, $offset ) . (string) ( $patch['replacement'] ?? '' ) . substr( $source, $offset + $remove_length );
}

function almaden_bookster_typst_page_template_plan_read( $key ) {
	if ( ! preg_match( '/^[a-f0-9]{64}$/', (string) $key ) ) {
		return null;
	}
	$path = almaden_bookster_typst_page_template_plan_cache_dir() . '/' . $key . '.json';
	$max_age = defined( 'DAY_IN_SECONDS' ) ? 7 * DAY_IN_SECONDS : 604800;
	if ( ! is_file( $path ) || filemtime( $path ) < time() - $max_age ) {
		return null;
	}
	$entry = json_decode( (string) file_get_contents( $path ), true );
	if ( ! is_array( $entry ) || ! is_array( $entry['patch'] ?? null ) ) {
		return null;
	}
	@touch( $path );
	return $entry;
}

function almaden_bookster_typst_page_template_plan_write( $key, $entry ) {
	$dir = almaden_bookster_typst_page_template_plan_cache_dir();
	if ( ! is_dir( $dir ) && ! mkdir( $dir, 0700, true ) && ! is_dir( $dir ) ) {
		return false;
	}
	$path = $dir . '/' . $key . '.json';
	$tmp = $path . '.' . uniqid( 'tmp-', true );
	$encoded = function_exists( 'wp_json_encode' ) ? wp_json_encode( $entry ) : json_encode( $entry );
	$written = false !== file_put_contents( $tmp, $encoded, LOCK_EX ) && rename( $tmp, $path );
	@chmod( $path, 0600 );
	@unlink( $tmp );
	$files = glob( $dir . '/*.json' );
	if ( is_array( $files ) && count( $files ) > 128 ) {
		usort( $files, static function ( $left, $right ) {
			$right_time = @filemtime( $right );
			$left_time = @filemtime( $left );
			return ( false === $right_time ? 0 : $right_time ) <=> ( false === $left_time ? 0 : $left_time );
		} );
		foreach ( array_slice( $files, 128 ) as $stale ) {
			@unlink( $stale );
		}
	}
	return $written;
}
