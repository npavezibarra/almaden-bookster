<?php
/**
 * Page-template module entry point.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

require_once __DIR__ . '/page-template-registry.php';
require_once __DIR__ . '/page-template-slots.php';
require_once __DIR__ . '/page-template-identity.php';
require_once __DIR__ . '/page-template-normalizer.php';
require_once __DIR__ . '/page-template-persistence.php';
require_once __DIR__ . '/page-template-placeholder.php';
require_once __DIR__ . '/page-template-transition.php';
require_once __DIR__ . '/page-template-composer.php';
require_once __DIR__ . '/page-template-word-flow.php';
