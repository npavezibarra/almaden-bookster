<?php
define( 'ALMADEN_TYPST_TESTING', true );
require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-markup.php';

$raw = <<<RAW
[align=justify]
La edición de Adiós Valparaíso. It's working.
[/align]
RAW;

$rendered = almaden_bookster_typst_render_blocks( $raw );

if ( false !== strpos( $rendered, '#[#set par(justify: true)' ) ) {
	fwrite( STDERR, "El bloque justify siguió generando Typst inválido.\n" );
	exit( 1 );
}

if ( false === strpos( $rendered, '#set par(justify: true)' ) || false === strpos( $rendered, '#set par(justify: false)' ) ) {
	fwrite( STDERR, "El bloque justify no cerró y abrió el estado de párrafo correctamente.\n" );
	exit( 1 );
}

if ( false === strpos( $rendered, "La edición de Adiós Valparaíso. It's working." ) ) {
	fwrite( STDERR, "El contenido con apóstrofe y punto no llegó intacto al renderizado.\n" );
	exit( 1 );
}

$entity_rendered = almaden_bookster_typst_render_blocks( '&quot;Cita importada&quot; y &#8212; guion.' );
if ( false === strpos( $entity_rendered, '"Cita importada" y — guion.' ) ) {
	fwrite( STDERR, "Las entidades HTML importadas no se normalizaron antes de renderizar Typst.\n" );
	exit( 1 );
}

echo "typst-justify-regression-ok\n";
