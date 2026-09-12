<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-fonts.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-markup.php';
$raw = <<<'RAW'

[html]
<table style='font-size:12pt;background-color:#eee;'><tr><td>Styled</td></tr></table>
[/html]

RAW;
$rendered = almaden_bookster_typst_render_blocks( $raw );
if ( false === strpos( $rendered, '#table' ) && false === strpos( $rendered, '#par' ) ) {
    fwrite( STDERR, "Failed to render $file\n" );
    exit( 1 );
}
echo str_replace('.php', '-ok', 'test_h_table_inline_styles.php') . "\n";
