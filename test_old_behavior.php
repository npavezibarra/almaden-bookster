<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-fonts.php';

// Just dummy functions
function almaden_bookster_typst_css_length_pt($val, $def) { return (float)$val; }
function almaden_bookster_typst_parse_style_declarations($s) {
    preg_match_all('/([a-z-]+)\s*:\s*([^;]+)/i', $s, $m, PREG_SET_ORDER);
    $r = []; foreach($m as $x) $r[$x[1]] = $x[2]; return $r;
}
function almaden_bookster_typst_parse_html_attributes($s) {
    return ['style' => 'width:100%;border-collapse:collapse;text-align:left;font-size:6pt;line-height:1.25;'];
}

$html = '<table style="width:100%;border-collapse:collapse;text-align:left;font-size:6pt;line-height:1.25;">';
$table_style = ['font_size' => 14];

if ( preg_match( '/<table\b([^>]*)>/i', (string) $html, $table_tag ) ) {
    $table_attrs = almaden_bookster_typst_parse_html_attributes( $table_tag[1] );
    $inline_style = almaden_bookster_typst_parse_style_declarations( $table_attrs['style'] ?? '' );
    if ( isset( $inline_style['font-size'] ) ) {
        $table_style['font_size'] = almaden_bookster_typst_css_length_pt( $inline_style['font-size'], $table_style['font_size'] ?? 10, 5, 100 );
    }
}
print_r($table_style);
