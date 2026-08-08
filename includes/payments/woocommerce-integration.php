<?php
/**
 * WooCommerce integration bootstrap.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/woocommerce-relation.php';
require_once __DIR__ . '/woocommerce-products.php';
require_once __DIR__ . '/woocommerce-access.php';
require_once __DIR__ . '/woocommerce-hooks.php';
require_once __DIR__ . '/woocommerce-provider.php';
