<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-fonts.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-markup.php';
$raw = <<<'RAW'

[box]
[html]
<div><p>Intro</p><table style='width:100%'><thead><tr><th>H</th></tr></thead><tbody><tr><td>D</td></tr></tbody></table></div>
[/html]
[/box]

RAW;
$rendered = almaden_bookster_typst_render_blocks( $raw );
if ( false === strpos( $rendered, '#table' ) && false === strpos( $rendered, '#par' ) ) {
    fwrite( STDERR, "Failed to render $file\n" );
    exit( 1 );
}
echo str_replace('.php', '-ok', 'test_j_integral_pipeline.php') . "\n";
