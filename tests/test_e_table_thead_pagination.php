<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-fonts.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-markup.php';
$raw = <<<'RAW'

[html]
<table><thead><tr><th>Header</th></tr></thead><tbody><tr><td>Data</td></tr></tbody></table>
[/html]

RAW;
$rendered = almaden_bookster_typst_render_blocks( $raw );
if ( false === strpos( $rendered, '#table' ) && false === strpos( $rendered, '#par' ) ) {
    fwrite( STDERR, "Failed to render $file\n" );
    exit( 1 );
}
echo str_replace('.php', '-ok', 'test_e_table_thead_pagination.php') . "\n";
