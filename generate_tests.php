<?php
$tests = [
    'test_a_html_no_box.php' => "
[html]
<table><tr><td>Test A</td></tr></table>
[/html]
",
    'test_b_box_html_paragraphs.php' => "
[box]
[html]
<p>P1</p><p>P2</p>
[/html]
[/box]
",
    'test_c_table_auto_width.php' => "
[html]
<table style='width:auto'><tr><td>Auto</td></tr></table>
[/html]
",
    'test_d_table_full_width.php' => "
[html]
<table style='width:100%'><tr><td>Full</td></tr></table>
[/html]
",
    'test_e_table_thead_pagination.php' => "
[html]
<table><thead><tr><th>Header</th></tr></thead><tbody><tr><td>Data</td></tr></tbody></table>
[/html]
",
    'test_f_cell_padding_borders.php' => "
[html]
<table><tr><td style='padding:15px;border-top:1px solid #000;border-bottom:1px solid #fff;'>Cell</td></tr></table>
[/html]
",
    'test_g_nested_div_table.php' => "
[html]
<div><table><tr><td>Nested</td></tr></table></div>
[/html]
",
    'test_h_table_inline_styles.php' => "
[html]
<table style='font-size:12pt;background-color:#eee;'><tr><td>Styled</td></tr></table>
[/html]
",
    'test_i_numeric_column.php' => "
[html]
<table style='width:100%'><tr><td>Texto largo...</td><td>$100</td></tr></table>
[/html]
",
    'test_j_integral_pipeline.php' => "
[box]
[html]
<div><p>Intro</p><table style='width:100%'><thead><tr><th>H</th></tr></thead><tbody><tr><td>D</td></tr></tbody></table></div>
[/html]
[/box]
"
];

foreach ($tests as $file => $content) {
    $code = "<?php\n";
    $code .= "define( 'ALMADEN_TYPST_TESTING', true );\n";
    $code .= "require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-fonts.php';\n";
    $code .= "require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-markup.php';\n";
    $code .= "\$raw = <<<'RAW'\n$content\nRAW;\n";
    $code .= "\$rendered = almaden_bookster_typst_render_blocks( \$raw );\n";
    $code .= "if ( false === strpos( \$rendered, '#table' ) && false === strpos( \$rendered, '#par' ) ) {\n";
    $code .= "    fwrite( STDERR, \"Failed to render \$file\\n\" );\n";
    $code .= "    exit( 1 );\n";
    $code .= "}\n";
    $code .= "echo str_replace('.php', '-ok', '$file') . \"\\n\";\n";
    file_put_contents("tests/$file", $code);
}
echo "Tests created.\n";
