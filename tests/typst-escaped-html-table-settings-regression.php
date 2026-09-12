<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-fonts.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-markup.php';

$raw = <<<'RAW'
[box]
[html]
\<table style="width:100%;border-collapse:collapse;">
\<thead>
\<tr>
\<th style="">\<strong>Estrategia\</strong>\</th>
\<th style="">\<strong>Dedicación\</strong>\</th>
\<th style="">\<strong>Descripción\</strong>\</th>
\</tr>
\</thead>
\<tbody>
\<tr>
\<td style="">\<em>Turnkey\</em>\</td>
\<td style="">Muy baja\</td>
\<td style="">Propiedad comprada lista para arrendar, con administración incluida.\</td>
\</tr>
\</tbody>
\</table>
[/html]
[/box]
RAW;

$rendered = almaden_bookster_typst_render_blocks(
	$raw,
	array(
		'table_style' => array(
			'font_family'     => 'Inter',
			'font_size'       => 14,
			'font_weight'     => 'bold',
			'font_style'      => 'italic',
			'line_height'     => 1.8,
			'align'           => 'right',
			'cell_padding'    => 9,
			'border_width'    => 2,
			'letter_spacing'  => 0.7,
			'border_color'    => '#123456',
			'header_bg_color' => '#abcdef',
			'cell_bg_color'   => '#ffffff',
		),
	)
);

foreach (
	array(
		'#table(columns: (auto, auto, 68fr)',
		'font: "Inter"',
		'size: 14pt',
		'weight: 700',
		'style: "italic"',
		'#set align(right)',
		'tracking: 0.7pt',
		
		'inset: 9pt',
		'stroke: 2pt + rgb("123456")',
		'fill: rgb("abcdef")',
		'fill: rgb("ffffff")',
		'Estrategia',
		'Turnkey',
	) as $expected
) {
	if ( false === strpos( $rendered, $expected ) ) {
		fwrite( STDERR, "Falta salida esperada: {$expected}\n{$rendered}\n" );
		exit( 1 );
	}
}

if ( false !== strpos( $rendered, 'font-size:6pt' ) || false !== strpos( $rendered, 'inset: 4pt' ) ) {
	fwrite( STDERR, "La tabla siguió usando estilos inline en vez de ajustes globales.\n{$rendered}\n" );
	exit( 1 );
}

echo "typst-escaped-html-table-settings-regression-ok\n";
