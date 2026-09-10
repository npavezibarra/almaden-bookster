<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-fonts.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-markup.php';

$raw = <<<'RAW'
[box]
[html]
\<table style="width:100%;border-collapse:collapse;text-align:left;font-size:6pt;line-height:1.25;">
\<thead>
\<tr>
\<th style="padding:4px;border:1px solid #d9d9d9;">\<strong>Estrategia\</strong>\</th>
\<th style="padding:4px;border:1px solid #d9d9d9;">\<strong>Dedicación\</strong>\</th>
\<th style="padding:4px;border:1px solid #d9d9d9;">\<strong>Descripción\</strong>\</th>
\</tr>
\</thead>
\<tbody>
\<tr>
\<td style="padding:4px;border:1px solid #d9d9d9;">\<em>Turnkey\</em>\</td>
\<td style="padding:4px;border:1px solid #d9d9d9;">Muy baja\</td>
\<td style="padding:4px;border:1px solid #d9d9d9;">Propiedad comprada lista para arrendar, con administración incluida.\</td>
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
		'#table(columns: (1fr, 1fr, 1fr)',
		'font: "Inter"',
		'size: 14pt',
		'weight: 700',
		'style: "italic"',
		'#set align(right)',
		'tracking: 0.7pt',
		'inset: 0pt',
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
