<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-fonts.php';
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-markup.php';

$raw = <<<'RAW'
[box]
[html]
\<div style="background:#f3f3f3;border:3px solid #d9d9d9;padding:18px 22px;border-radius:6px;line-height:1.65;text-align:left;">

\<p>\<strong>En la práctica\</strong>\</p>

\<p>Este bloque debe conservar su padding porque no contiene una tabla con celdas.\</p>

\</div>
[/html]
[/box]
RAW;

$rendered = almaden_bookster_typst_render_blocks(
	$raw,
	array(
		'table_style' => array(
			'cell_padding'  => 4,
			'border_width'  => 2,
			'border_color'  => '#123456',
			'cell_bg_color' => '#ffffff',
		),
	)
);

if ( false === strpos( $rendered, 'inset: 13.5pt' ) ) {
	fwrite( STDERR, "El box con párrafos perdió su padding externo.\n{$rendered}\n" );
	exit( 1 );
}

if ( false !== strpos( $rendered, '#table(' ) ) {
	fwrite( STDERR, "El box con párrafos se interpretó como tabla.\n{$rendered}\n" );
	exit( 1 );
}

echo "typst-html-paragraph-box-padding-regression-ok\n";
