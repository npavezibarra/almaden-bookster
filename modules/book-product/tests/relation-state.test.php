<?php
define( 'ABSPATH', __DIR__ );

$GLOBALS['abp_test_meta'] = array();

function absint( $value ) {
	return abs( (int) $value );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function metadata_exists( $type, $id, $key ) {
	return array_key_exists( $key, $GLOBALS['abp_test_meta'][ $id ] ?? array() );
}

function get_post_meta( $id, $key, $single = false ) {
	return $GLOBALS['abp_test_meta'][ $id ][ $key ] ?? '';
}

function update_post_meta( $id, $key, $value ) {
	$GLOBALS['abp_test_meta'][ $id ][ $key ] = $value;
	return true;
}

function delete_post_meta( $id, $key ) {
	unset( $GLOBALS['abp_test_meta'][ $id ][ $key ] );
	return true;
}

require_once dirname( __DIR__ ) . '/includes/class-relation-repository.php';

use AlmadenBookster\BookProduct\Relation_Repository;

function abp_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$empty = Relation_Repository::get( 10 );
abp_assert( 0 === $empty['parent_product_id'], 'A new book starts without a product.' );
abp_assert( false === $empty['legacy'], 'A new book is not a legacy relation.' );

update_post_meta( 10, '_almaden_wc_product_id', 202 );
update_post_meta( 10, '_almaden_wc_parent_product_id', 200 );
update_post_meta( 10, '_almaden_wc_product_mode', 'variation' );
$legacy = Relation_Repository::get( 10 );
abp_assert( 200 === $legacy['parent_product_id'], 'Legacy parent is preserved.' );
abp_assert( 202 === $legacy['ebook_product_id'], 'Legacy variation becomes the ebook slot.' );
abp_assert( true === $legacy['legacy'], 'Legacy state is marked for discovery.' );

$saved = Relation_Repository::save( 10, array(
	'parent_product_id'   => 200,
	'physical_product_id' => 201,
	'ebook_product_id'    => 202,
	'both_product_id'     => 203,
) );
abp_assert( false === $saved['legacy'], 'Saving upgrades the relation schema.' );
abp_assert( 202 === get_post_meta( 10, '_almaden_wc_product_id', true ), 'Legacy access anchor points to ebook.' );
abp_assert( 10 === get_post_meta( 201, '_almaden_book_product_book_id', true ), 'Physical slot receives the module reverse link.' );
abp_assert( '' === get_post_meta( 201, '_almaden_book_id', true ), 'Physical slot does not grant legacy reader access.' );
abp_assert( 10 === get_post_meta( 202, '_almaden_book_id', true ), 'Ebook slot grants legacy reader access.' );
abp_assert( 10 === get_post_meta( 203, '_almaden_book_id', true ), 'Both slot also grants legacy reader access.' );

$saved['ebook_product_id'] = 0;
Relation_Repository::save( 10, $saved );
abp_assert( '' === get_post_meta( 202, '_almaden_book_id', true ), 'Unlinking ebook removes its access reverse link.' );
abp_assert( 10 === get_post_meta( 203, '_almaden_book_id', true ), 'Both slot keeps granting reader access.' );
abp_assert( 10 === get_post_meta( 200, '_almaden_book_id', true ), 'Parent keeps access while an access slot exists.' );

Relation_Repository::clear( 10 );
abp_assert( ! metadata_exists( 'post', 10, Relation_Repository::META_KEY ), 'Clearing removes the module relation.' );
abp_assert( '' === get_post_meta( 10, '_almaden_wc_product_id', true ), 'Clearing removes legacy metadata.' );

echo "Book Product relation tests passed.\n";
