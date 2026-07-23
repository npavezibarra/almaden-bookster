<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function almaden_bookster_parse_rtf_import_document( $path, $filename ) {
	$raw = file_get_contents( $path );
	if ( false === $raw ) {
		return new WP_Error( 'rtf_read_failed', 'No se pudo leer el archivo RTF.' );
	}

	$text = almaden_bookster_rtf_to_text( $raw );
	return almaden_bookster_blocks_from_plain_text( $text, $filename, 'rtf' );
}

function almaden_bookster_rtf_to_text( $rtf ) {
	$text = '';
	$len = strlen( $rtf );

	for ( $i = 0; $i < $len; $i++ ) {
		$char = $rtf[ $i ];

		if ( '\\' === $char ) {
			$i++;
			if ( $i >= $len ) {
				break;
			}

			$next = $rtf[ $i ];
			if ( in_array( $next, array( '\\', '{', '}' ), true ) ) {
				$text .= $next;
				continue;
			}

			if ( "'" === $next && $i + 2 < $len ) {
				$hex = substr( $rtf, $i + 1, 2 );
				if ( ctype_xdigit( $hex ) ) {
					$decoded = @iconv( 'CP1252', 'UTF-8//IGNORE', pack( 'H*', $hex ) );
					$text .= false !== $decoded ? $decoded : '';
					$i += 2;
				}
				continue;
			}

			$word = '';
			while ( $i < $len && ctype_alpha( $rtf[ $i ] ) ) {
				$word .= $rtf[ $i ];
				$i++;
			}
			$param = '';
			while ( $i < $len && ( '-' === $rtf[ $i ] || ctype_digit( $rtf[ $i ] ) ) ) {
				$param .= $rtf[ $i ];
				$i++;
			}

			if ( $i < $len && ' ' === $rtf[ $i ] ) {
				continue;
			}

			switch ( $word ) {
				case 'par':
				case 'line':
					$text .= "\n";
					break;
				case 'tab':
					$text .= "\t";
					break;
				case 'emdash':
					$text .= '—';
					break;
				case 'endash':
					$text .= '–';
					break;
				case 'bullet':
					$text .= '•';
					break;
				default:
					break;
			}
			continue;
		}

		if ( "\r" !== $char ) {
			$text .= $char;
		}
	}

	return preg_replace( "/\n{3,}/", "\n\n", trim( $text ) );
}
