<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encrypt data using WordPress salts.
 *
 * @param string $data The data to encrypt.
 * @return string|false Base64 encoded encrypted string, or false on failure.
 */
function almaden_encrypt_key( $data ) {
	if ( empty( $data ) ) {
		return $data;
	}

	$key = defined( 'LOGGED_IN_KEY' ) ? LOGGED_IN_KEY : 'almaden-fallback-key';
	$salt = defined( 'LOGGED_IN_SALT' ) ? LOGGED_IN_SALT : 'almaden-fallback-salt';
	$encryption_key = hash( 'sha256', $key . $salt );

	$iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
	$iv = openssl_random_pseudo_bytes( $iv_length );

	$encrypted = openssl_encrypt( $data, 'aes-256-cbc', $encryption_key, 0, $iv );
	if ( $encrypted === false ) {
		return false;
	}

	return base64_encode( $encrypted . '::' . $iv );
}

/**
 * Decrypt data using WordPress salts.
 *
 * @param string $data The base64 encoded encrypted string.
 * @return string|false Decrypted data, or false on failure.
 */
function almaden_decrypt_key( $data ) {
	if ( empty( $data ) ) {
		return $data;
	}

	$decoded = base64_decode( $data );
	if ( $decoded === false || strpos( $decoded, '::' ) === false ) {
		return false;
	}

	list( $encrypted_data, $iv ) = explode( '::', $decoded, 2 );

	$key = defined( 'LOGGED_IN_KEY' ) ? LOGGED_IN_KEY : 'almaden-fallback-key';
	$salt = defined( 'LOGGED_IN_SALT' ) ? LOGGED_IN_SALT : 'almaden-fallback-salt';
	$encryption_key = hash( 'sha256', $key . $salt );

	return openssl_decrypt( $encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv );
}
