<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once __DIR__ . '/import/import-mapping.php';
require_once __DIR__ . '/import/import-builder.php';
require_once __DIR__ . '/import/import-parsers.php';
require_once __DIR__ . '/import/parser-docx.php';
require_once __DIR__ . '/import/parser-rtf.php';
require_once __DIR__ . '/import/parser-txt.php';
require_once __DIR__ . '/import/parser-pdf.php';
require_once __DIR__ . '/import/import-ajax.php';
