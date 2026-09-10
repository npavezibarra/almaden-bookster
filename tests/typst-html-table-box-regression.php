<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-fonts.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-markup.php';

$raw = <<<'RAW'
[box]
[html]
<div style="background:#f3f3f3;border:1px solid #d9d9d9;padding:18px 22px;border-radius:4px;line-height:1.65;text-align:left;overflow-x:auto;">
<table style="width:100%;border-collapse:collapse;text-align:left;">
<thead>
<tr>
<th style="padding:10px;border-bottom:2px solid #bdbdbd;"><strong>Estrategia</strong></th>
<th style="padding:10px;border-bottom:2px solid #bdbdbd;"><strong>Dedicación</strong></th>
<th style="padding:10px;border-bottom:2px solid #bdbdbd;"><strong>Descripción</strong></th>
</tr>
</thead>
<tbody>
<tr>
<td style="padding:10px;border-bottom:1px solid #d9d9d9;"><em>Turnkey</em></td>
<td style="padding:10px;border-bottom:1px solid #d9d9d9;">Muy baja</td>
<td style="padding:10px;border-bottom:1px solid #d9d9d9;">Propiedad comprada lista para arrendar, con administración incluida.</td>
</tr>
</tbody>
</table>
</div>
[/html]
[/box]
RAW;

$rendered = almaden_bookster_typst_render_blocks( $raw );

if ( false === strpos( $rendered, '#table(columns: (1fr, 1fr, 1fr)' ) ) {
	fwrite( STDERR, "La tabla HTML dentro de box no se renderizó como tabla Typst.\n" );
	exit( 1 );
}

foreach ( array( 'Estrategia', 'Dedicación', 'Descripción', 'Turnkey', 'Muy baja' ) as $expected ) {
	if ( false === strpos( $rendered, $expected ) ) {
		fwrite( STDERR, "Falta contenido de celda esperado: {$expected}\n" );
		exit( 1 );
	}
}

if ( false !== strpos( $rendered, '#par[#strong[Estrategia]]' ) ) {
	fwrite( STDERR, "La tabla HTML se siguió aplanando como párrafos.\n" );
	exit( 1 );
}

echo "typst-html-table-box-regression-ok\n";
