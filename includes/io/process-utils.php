<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Find the Chrome binary used for headless PDF generation.
 */
function almaden_bookster_find_chrome_binary() {
	$candidates = array(
		'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
		'/Applications/Chromium.app/Contents/MacOS/Chromium',
	);

	foreach ( $candidates as $candidate ) {
		if ( file_exists( $candidate ) && is_executable( $candidate ) ) {
			return $candidate;
		}
	}

	$names = array( 'google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser' );
	foreach ( $names as $name ) {
		$found = trim( (string) shell_exec( 'command -v ' . escapeshellarg( $name ) . ' 2>/dev/null' ) );
		if ( ! empty( $found ) && file_exists( $found ) ) {
			return $found;
		}
	}

	return '';
}

/**
 * Find the Ghostscript binary used to convert the PDF to CMYK.
 */
function almaden_bookster_find_ghostscript_binary() {
	$candidates = array(
		'/Applications/Local.app/Contents/Resources/extraResources/lightning-services/php-8.2.29+0/bin/darwin-arm64/ghostscript/bin/gs',
		'/Applications/Local.app/Contents/Resources/extraResources/lightning-services/php-8.4.6+0/bin/darwin-arm64/ghostscript/bin/gs',
	);

	foreach ( $candidates as $candidate ) {
		if ( file_exists( $candidate ) && is_executable( $candidate ) ) {
			return $candidate;
		}
	}

	$found = trim( (string) shell_exec( 'command -v gs 2>/dev/null' ) );
	if ( ! empty( $found ) && file_exists( $found ) ) {
		return $found;
	}

	return '';
}

/**
 * Execute a shell command and capture stdout/stderr.
 */
function almaden_bookster_run_process( $command, &$stdout = '', &$stderr = '', $timeout_seconds = 60 ) {
	$stdout = '';
	$stderr = '';

	if ( ! function_exists( 'proc_open' ) ) {
		return new WP_Error( 'process_unavailable', 'La función proc_open no está disponible en este servidor.' );
	}

	$descriptors = array(
		1 => array( 'pipe', 'w' ),
		2 => array( 'pipe', 'w' ),
	);

	$process = proc_open( $command, $descriptors, $pipes );
	if ( ! is_resource( $process ) ) {
		return new WP_Error( 'process_open_failed', 'No se pudo iniciar el proceso externo.' );
	}

	stream_set_blocking( $pipes[1], false );
	stream_set_blocking( $pipes[2], false );

	$start = microtime( true );
	$exit_code = null;

	while ( true ) {
		$status = proc_get_status( $process );
		if ( false === $status ) {
			break;
		}

		$stdout .= stream_get_contents( $pipes[1] );
		$stderr .= stream_get_contents( $pipes[2] );

		if ( ! $status['running'] ) {
			$exit_code = $status['exitcode'];
			break;
		}

		if ( ( microtime( true ) - $start ) > $timeout_seconds ) {
			proc_terminate( $process, 9 );
			$stderr .= "\nProceso cancelado por exceder el tiempo límite.";
			$exit_code = 124;
			break;
		}

		usleep( 250000 );
	}

	$stdout .= stream_get_contents( $pipes[1] );
	$stderr .= stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	if ( null === $exit_code ) {
		$exit_code = proc_close( $process );
	} elseif ( 0 === $exit_code ) {
		proc_close( $process );
	} else {
		// When a timeout or failure occurs, avoid blocking on proc_close().
		@proc_terminate( $process, 9 );
	}

	if ( 0 !== $exit_code ) {
		return new WP_Error( 'process_failed', trim( $stderr ) !== '' ? trim( $stderr ) : 'El proceso terminó con código ' . intval( $exit_code ) . '.' );
	}

	return true;
}

