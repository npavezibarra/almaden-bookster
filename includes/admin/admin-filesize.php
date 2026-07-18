<?php
/**
 * AlmadenBookster - Admin FileSize page.
 *
 * Lists plugin files with their line count and size so maintainers can spot
 * the heaviest areas of the codebase.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_register_filesize_menu() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	add_submenu_page(
		'almaden-bookster',
		'FileSize',
		'FileSize',
		'manage_options',
		'almaden-bookster-filesize',
		'almaden_bookster_render_filesize_page'
	);
}
add_action( 'admin_menu', 'almaden_bookster_register_filesize_menu', 21 );

function almaden_bookster_filesize_admin_enqueue( $hook ) {
	if ( strpos( $hook, 'almaden-bookster-filesize' ) === false ) {
		return;
	}

	wp_enqueue_style(
		'almaden-filesize-admin-css',
		plugin_dir_url( dirname( __DIR__, 2 ) . '/almaden-bookster.php' ) . 'assets/css/admin-filesize-page.css',
		array(),
		'1.0.0'
	);
}
add_action( 'admin_enqueue_scripts', 'almaden_bookster_filesize_admin_enqueue' );

function almaden_bookster_filesize_plugin_root() {
	return dirname( __DIR__, 2 );
}

function almaden_bookster_filesize_count_lines( $path ) {
	$handle = @fopen( $path, 'rb' );
	if ( ! $handle ) {
		return 0;
	}

	$lines = 0;
	while ( ! feof( $handle ) ) {
		$chunk = fgets( $handle );
		if ( false !== $chunk ) {
			$lines++;
		}
	}

	fclose( $handle );

	return $lines;
}

function almaden_bookster_filesize_build_report() {
	$root  = almaden_bookster_filesize_plugin_root();
	$items = array();

	try {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$root,
				FilesystemIterator::SKIP_DOTS
			)
		);
	} catch ( Exception $e ) {
		return array();
	}

	foreach ( $iterator as $file_info ) {
		if ( ! $file_info->isFile() ) {
			continue;
		}

		$absolute_path = $file_info->getPathname();
		$relative_path  = ltrim( str_replace( $root, '', $absolute_path ), DIRECTORY_SEPARATOR );
		$relative_path  = str_replace( DIRECTORY_SEPARATOR, '/', $relative_path );

		$bytes = (int) filesize( $absolute_path );
		$lines = almaden_bookster_filesize_count_lines( $absolute_path );

		$items[] = array(
			'file'  => $relative_path,
			'lines' => $lines,
			'bytes' => $bytes,
			'kb'    => $bytes / 1024,
		);
	}

	return $items;
}

function almaden_bookster_filesize_sort_report( array $items, $orderby, $order ) {
	$orderby = in_array( $orderby, array( 'lines', 'size' ), true ) ? $orderby : 'lines';
	$order   = 'asc' === strtolower( (string) $order ) ? 'asc' : 'desc';

	usort(
		$items,
		function ( $left, $right ) use ( $orderby, $order ) {
			if ( 'size' === $orderby ) {
				$left_value  = $left['bytes'];
				$right_value = $right['bytes'];
			} else {
				$left_value  = $left['lines'];
				$right_value = $right['lines'];
			}

			if ( $left_value === $right_value ) {
				return strcasecmp( $left['file'], $right['file'] );
			}

			$result = ( $left_value < $right_value ) ? -1 : 1;

			return 'asc' === $order ? $result : -$result;
		}
	);

	return $items;
}

function almaden_bookster_render_filesize_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permisos insuficientes.', 'almaden-bookster' ) );
	}

	$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'lines';
	$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'desc';

	$report = almaden_bookster_filesize_sort_report( almaden_bookster_filesize_build_report(), $orderby, $order );

	$summary = array(
		'files' => count( $report ),
		'lines' => array_sum( wp_list_pluck( $report, 'lines' ) ),
		'bytes' => array_sum( wp_list_pluck( $report, 'bytes' ) ),
		'kb'    => array_sum( wp_list_pluck( $report, 'kb' ) ),
	);

	$base_url = admin_url( 'admin.php?page=almaden-bookster-filesize' );

	require dirname( __DIR__, 2 ) . '/templates/admin/filesize-app.php';
}
