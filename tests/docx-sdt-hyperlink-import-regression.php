<?php
define( 'ABSPATH', true );

function get_bloginfo( $key ) {
	return 'UTF-8';
}

function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

class WP_Error {
	public $code;
	public $message;
	public function __construct( $code = '', $message = '' ) {
		$this->code = $code;
		$this->message = $message;
	}
}

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

require_once dirname( __DIR__ ) . '/includes/io/import/parser-txt.php';
require_once dirname( __DIR__ ) . '/includes/io/import/parser-docx.php';

if ( ! class_exists( 'ZipArchive' ) ) {
	fwrite( STDERR, "ZipArchive no está disponible.\n" );
	exit( 1 );
}

$tmp = tempnam( sys_get_temp_dir(), 'almaden-docx-sdt-' );
$docx = $tmp . '.docx';
rename( $tmp, $docx );

$document_xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <w:body>
    <w:p>
      <w:r><w:t>Párrafo con </w:t></w:r>
      <w:hyperlink r:id="rId1">
        <w:r><w:rPr><w:u/></w:rPr><w:t>https://www.invertirparaextranjeros.com/recursos</w:t></w:r>
      </w:hyperlink>
      <w:r><w:t> y texto final.</w:t></w:r>
    </w:p>

    <w:p>
      <w:r><w:t>Texto con palabra </w:t></w:r>
      <w:sdt>
        <w:sdtContent>
          <w:r><w:t>ahorrarles</w:t></w:r>
        </w:sdtContent>
      </w:sdt>
      <w:r><w:t> en inline SDT.</w:t></w:r>
    </w:p>

    <w:sdt>
      <w:sdtContent>
        <w:p>
          <w:r><w:t>Párrafo 1 dentro de SDT a nivel de body</w:t></w:r>
        </w:p>
        <w:p>
          <w:r><w:t>Párrafo 2 dentro de SDT a nivel de body</w:t></w:r>
        </w:p>
      </w:sdtContent>
    </w:sdt>

    <w:p>
      <w:r><w:t>Texto con </w:t></w:r>
      <w:ins>
        <w:r><w:t>revisión insertada</w:t></w:r>
      </w:ins>
      <w:r><w:t> correcto.</w:t></w:r>
    </w:p>

    <w:tbl>
      <w:tr>
        <w:tc>
          <w:sdt>
            <w:sdtContent>
              <w:p><w:r><w:t>Celda con SDT</w:t></w:r></w:p>
            </w:sdtContent>
          </w:sdt>
        </w:tc>
        <w:tc>
          <w:p><w:r><w:t>Celda normal</w:t></w:r></w:p>
        </w:tc>
      </w:tr>
    </w:tbl>

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

$parsed = almaden_bookster_parse_docx_import_document( $docx, 'fixture-sdt.docx' );
@unlink( $docx );

if ( is_wp_error( $parsed ) ) {
	fwrite( STDERR, "Error parseando documento: " . $parsed->message . "\n" );
	exit( 1 );
}

$texts = array_values( array_filter( array_map( function( $block ) {
	return 'blank' === $block['type'] ? '' : $block['text'];
}, $parsed['blocks'] ) ) );

if ( count( $texts ) !== 6 ) {
	fwrite( STDERR, "Se esperaban 6 bloques no vacíos; llegaron " . count( $texts ) . ".\n" );
	var_dump( $texts );
	exit( 1 );
}

if ( false === strpos( $texts[0], 'https://www.invertirparaextranjeros.com/recursos' ) ) {
	fwrite( STDERR, "Fallo en extracción de hipervínculo.\n" );
	exit( 1 );
}

if ( false === strpos( $texts[1], 'ahorrarles' ) ) {
	fwrite( STDERR, "Fallo en extracción de inline SDT.\n" );
	exit( 1 );
}

if ( 'Párrafo 1 dentro de SDT a nivel de body' !== $texts[2] || 'Párrafo 2 dentro de SDT a nivel de body' !== $texts[3] ) {
	fwrite( STDERR, "Fallo en extracción de párrafos dentro de SDT a nivel de body.\n" );
	exit( 1 );
}

if ( false === strpos( $texts[4], 'revisión insertada' ) ) {
	fwrite( STDERR, "Fallo en extracción de ins.\n" );
	exit( 1 );
}

if ( false === strpos( $texts[5], 'Celda con SDT' ) || false === strpos( $texts[5], 'Celda normal' ) ) {
	fwrite( STDERR, "Fallo en extracción de celda de tabla con SDT.\n" );
	exit( 1 );
}

echo "docx-sdt-hyperlink-import-regression-ok\n";
