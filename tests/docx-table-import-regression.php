<?php
define( 'ABSPATH', true );

function get_bloginfo( $key ) {
	return 'UTF-8';
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

class WP_Error {
	public function __construct() {}
}

require_once dirname( __DIR__ ) . '/includes/io/import/parser-txt.php';
require_once dirname( __DIR__ ) . '/includes/io/import/parser-docx.php';

if ( ! class_exists( 'ZipArchive' ) ) {
	fwrite( STDERR, "ZipArchive no está disponible.\n" );
	exit( 1 );
}

$tmp = tempnam( sys_get_temp_dir(), 'almaden-docx-table-' );
$docx = $tmp . '.docx';
rename( $tmp, $docx );

$document_xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p><w:r><w:t>Antes</w:t></w:r></w:p>
    <w:tbl>
      <w:tr>
        <w:tc>
          <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Para reflexionar</w:t></w:r></w:p>
          <w:p><w:r><w:t>Texto dentro de tabla</w:t></w:r></w:p>
        </w:tc>
      </w:tr>
    </w:tbl>
    <w:p><w:r><w:t>Después</w:t></w:r></w:p>
    <w:sectPr/>
  </w:body>
</w:document>
XML;

$zip = new ZipArchive();
if ( true !== $zip->open( $docx, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
	fwrite( STDERR, "No se pudo crear el DOCX temporal.\n" );
	exit( 1 );
}
$zip->addFromString( '[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/></Types>' );
$zip->addFromString( 'word/document.xml', $document_xml );
$zip->close();

$parsed = almaden_bookster_parse_docx_import_document( $docx, 'fixture.docx' );
@unlink( $docx );

$texts = array_values( array_filter( array_map( function( $block ) {
	return 'blank' === $block['type'] ? '' : $block['text'];
}, $parsed['blocks'] ) ) );

if ( count( $texts ) !== 3 ) {
	fwrite( STDERR, "Se esperaban 3 bloques no vacíos; llegaron " . count( $texts ) . ".\n" );
	exit( 1 );
}

if ( 'Antes' !== $texts[0] || false === strpos( $texts[1], 'Para reflexionar' ) || false === strpos( $texts[1], 'Texto dentro de tabla' ) || 'Después' !== $texts[2] ) {
	fwrite( STDERR, "La tabla DOCX no se importó en orden con su contenido.\n" );
	exit( 1 );
}

echo "docx-table-import-regression-ok\n";
