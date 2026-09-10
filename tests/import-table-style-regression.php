<?php
define( 'ABSPATH', true );

function get_bloginfo( $key ) {
	return 'UTF-8';
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

require_once dirname( __DIR__ ) . '/includes/io/import/parser-txt.php';
require_once dirname( __DIR__ ) . '/includes/io/import/import-mapping.php';
require_once dirname( __DIR__ ) . '/includes/io/import/import-builder.php';

$blocks = array(
	array(
		'type'        => 'heading',
		'text'        => 'Capítulo 1',
		'style_key'   => 'heading-1',
		'style_label' => 'Heading 1',
	),
	array(
		'type'        => 'paragraph',
		'text'        => "**Para reflexionar**\n\nTexto con \"comillas\".",
		'style_key'   => 'table',
		'style_label' => 'Tabla',
	),
);

$chapters = almaden_bookster_split_blocks_into_chapters(
	$blocks,
	array( 'chapter_separator' => 'heading-1' ),
	array(
		'enabled'       => '1',
		'background'    => '#eeeeee',
		'border_color'  => '#cccccc',
		'border_width'  => 2,
		'padding_y'     => 10,
		'padding_x'     => 12,
		'border_radius' => 6,
		'font_size'     => 13.5,
		'line_height'   => 1.55,
		'text_align'    => 'justify',
	)
);

$content = $chapters[0]['content'] ?? '';
$required = array(
	'[box]',
	'[html]',
	'background:#eeeeee;border:2px solid #cccccc;padding:10px 12px;border-radius:6px;line-height:1.55;text-align:justify;font-size:13.5px;',
	'<strong>Para reflexionar</strong>',
	'Texto con &quot;comillas&quot;.',
	'[/html]',
	'[/box]',
);

foreach ( $required as $fragment ) {
	if ( false === strpos( $content, $fragment ) ) {
		fwrite( STDERR, 'Falta fragmento de estilo de tabla importada: ' . $fragment . "\n" );
		exit( 1 );
	}
}

echo "import-table-style-regression-ok\n";
