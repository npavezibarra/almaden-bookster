<?php
/**
 * Page-style module entry point.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

require_once __DIR__ . '/page-style-normalizer.php';
require_once __DIR__ . '/page-style-persistence.php';
