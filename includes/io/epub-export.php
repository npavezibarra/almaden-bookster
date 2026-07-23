<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle ePub Export
 */
function almaden_export_epub_handler() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Permisos insuficientes.' );
	}

	if ( ! isset( $_POST['almaden_epub_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_epub_nonce'], 'almaden_export_epub_nonce' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! $book_id ) {
		wp_die( 'ID de libro inválido.' );
	}

	$book = get_post( $book_id );
	if ( ! $book || $book->post_type !== 'almaden-books' ) {
		wp_die( 'Libro no encontrado.' );
	}

	// Obtener capítulos
	$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
	if ( empty( $source_book_id ) ) {
		$source_book_id = $book_id;
	}

	$chapters = get_posts( array(
		'post_type'      => 'book_chapter',
		'post_parent'    => $source_book_id,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	) );

	// Preparamos el ZIP
	$tmp_file = wp_tempnam( 'epub_' );
	$zip = new ZipArchive();
	if ( $zip->open( $tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
		wp_die( 'No se pudo crear el archivo ZIP temporal para el ePub.' );
	}

	// 1. Mimetype (debe ser el primer archivo, sin compresión)
	$zip->addFromString( 'mimetype', 'application/epub+zip' );
	$zip->setCompressionName( 'mimetype', ZipArchive::CM_STORE ); // Sin compresión

	// 2. META-INF/container.xml
	$container_xml = '<?xml version="1.0"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
	<rootfiles>
		<rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
	</rootfiles>
</container>';
	$zip->addFromString( 'META-INF/container.xml', $container_xml );

	// Variables para los manifiestos
	$manifest_items = '';
	$spine_items = '';
	$nav_points = '';
	$play_order = 1;

	// OEBPS/Styles/style.css
	$style_css = '
body { font-family: serif; margin: 5%; text-align: justify; }
h1, h2, h3 { text-align: center; margin-top: 1em; margin-bottom: 1em; }
p { margin-bottom: 0.5em; text-indent: 1.5em; }
p:first-of-type { text-indent: 0; }
';
	$zip->addFromString( 'OEBPS/Styles/style.css', $style_css );
	$manifest_items .= '<item id="css" href="Styles/style.css" media-type="text/css"/>' . "\n";

	// 3. Procesar Capítulos (OEBPS/Text/*.xhtml)
	foreach ( $chapters as $index => $chapter ) {
		$chapter_id = 'chapter_' . $chapter->ID;
		$filename = 'Text/' . $chapter_id . '.xhtml';
		
		// Normalizar HTML a XHTML simple
		$content = $chapter->post_content;

		$strip_leading_duplicate_heading = function( $markdown, $title ) {
			$markdown = (string) $markdown;
			$title = trim( wp_strip_all_tags( html_entity_decode( (string) $title, ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' ) ) );
			if ( '' === $markdown || '' === $title ) {
				return $markdown;
			}

			$lines = preg_split( "/\r\n|\r|\n/", $markdown );
			$collected = array();
			$target = preg_replace( '/\s+/', ' ', $title );
			$found_body = false;

			foreach ( $lines as $line ) {
				$trimmed = trim( $line );
				if ( '' === $trimmed ) {
					if ( ! $found_body ) {
						continue;
					}
					$collected[] = $line;
					continue;
				}

				if ( ! $found_body && preg_match( '/^(#{1,6})\s+(.*)$/', $trimmed, $matches ) ) {
					$heading_text = trim( wp_strip_all_tags( preg_replace( array( '/\*\*\*(.*?)\*\*\*/s', '/\*\*(.*?)\*\*/s', '/\*(.*?)\*/s' ), '$1', $matches[2] ) ) );
					$heading_text = preg_replace( '/\s+/', ' ', $heading_text );
					if ( $heading_text === $target ) {
						$found_body = true;
						continue;
					}
				}

				$found_body = true;
				$collected[] = $line;
			}

			return implode( "\n", $collected );
		};

		$content = $strip_leading_duplicate_heading( $content, $chapter->post_title );

		// Convertir pseudo-markdown a HTML (similar a editor-markdown.js)
		$content = str_replace( array('<u\>', '</u>'), array('<u>', '</u>'), $content );
		$content = preg_replace( '/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $content );
		$content = preg_replace( '/\*(.*?)\*/s', '<em>$1</em>', $content );
		$content = preg_replace( '/\[lang:([a-zA-Z-]{2,10})\]([\s\S]*?)\[\/lang\]/s', '<span class="almaden-foreign" lang="$1"><em>$2</em></span>', $content );
		$content = preg_replace( '/<foreign\s+lang=(?:"|\')([a-zA-Z-]{2,10})(?:"|\')\s*>([\s\S]*?)<\/foreign>/i', '<span class="almaden-foreign" lang="$1"><em>$2</em></span>', $content );
		$content = preg_replace( '/<lang\s+code=(?:"|\')([a-zA-Z-]{2,10})(?:"|\')\s*>([\s\S]*?)<\/lang>/i', '<span class="almaden-foreign" lang="$1"><em>$2</em></span>', $content );
		$content = preg_replace( '/\[size=([0-9]+(?:\.[0-9]+)?)(px|pt|em|rem)?\]([\s\S]*?)\[\/size\]/is', '<span style="font-size: $1$2;">$3</span>', $content );
		$content = preg_replace( '/\[font=(?:&quot;|&#039;|"|\')([^\]]+?)(?:&quot;|&#039;|"|\')\]([\s\S]*?)\[\/font\]/is', '<span style="font-family: \'$1\', serif;">$2</span>', $content );
		
		// Remover shortcodes de columnas y page_break ya que ePub las maneja nativamente o no las soporta igual
		$content = preg_replace( '/\[box\s*([^\]]*)\]/is', '<div class="almaden-box">', $content );
		$content = preg_replace( '/\[\/box\]/is', '</div>', $content );
		$content = preg_replace( '/\[columns\s*([^\]]*)\]/is', '<div class="almaden-columns">', $content );
		$content = preg_replace( '/\[\/columns\]/is', '</div>', $content );
		$content = preg_replace( '/\[col\s*([^\]]*)\]/is', '<div class="almaden-col">', $content );
		$content = preg_replace( '/\[\/col\]/is', '</div>', $content );
		$content = preg_replace( '/\[align=([a-zA-Z]+)\]/is', '<div style="text-align: $1;">', $content );
		$content = preg_replace( '/\[\/align\]/is', '</div>', $content );
		$content = preg_replace( '/\[page[-_]?break\]/is', '<div style="page-break-after: always;"></div>', $content );
		$content = preg_replace( '/\[gap:([0-9]+(?:\.[0-9]+)?)\]/is', '<hr style="height: $1mm; border: none; margin: 0; padding: 0; background: transparent; clear: both;" />', $content );
		$content = preg_replace_callback( '/^(#{1,6})\s+(.+)$/m', function( $matches ) {
			$level = min( 6, strlen( $matches[1] ) );
			return '<h' . $level . '>' . $matches[2] . '</h' . $level . '>';
		}, $content );
		
		// wpautop converts double line breaks to paragraphs
		$content = wpautop( $content );
		
		// Eliminar tags no compatibles de forma rápida (dejando lo básico de formato y estructura)
		$content = strip_tags( $content, '<p><a><b><strong><i><em><h1><h2><h3><h4><h5><h6><ul><ol><li><br><blockquote><span><div>' );
		
		$xhtml = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>' . esc_html( $chapter->post_title ) . '</title>
<link rel="stylesheet" type="text/css" href="../Styles/style.css"/>
</head>
<body>
<h1>' . esc_html( $chapter->post_title ) . '</h1>
' . $content . '
</body>
</html>';
		
		$zip->addFromString( 'OEBPS/' . $filename, $xhtml );

		// Añadir al manifiesto y spine
		$manifest_items .= '<item id="' . $chapter_id . '" href="' . $filename . '" media-type="application/xhtml+xml"/>' . "\n";
		$spine_items .= '<itemref idref="' . $chapter_id . '"/>' . "\n";
		
		// Añadir al índice interactivo
		$nav_points .= '
		<navPoint id="navPoint-' . $play_order . '" playOrder="' . $play_order . '">
			<navLabel><text>' . esc_html( $chapter->post_title ) . '</text></navLabel>
			<content src="' . $filename . '"/>
		</navPoint>';
		$play_order++;
	}

	// 4. OEBPS/content.opf
	$uuid = wp_generate_uuid4();
	$content_opf = '<?xml version="1.0" encoding="utf-8"?>
<package xmlns="http://www.idpf.org/2007/opf" unique-identifier="BookId" version="2.0">
	<metadata xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:opf="http://www.idpf.org/2007/opf">
		<dc:title>' . esc_html( $book->post_title ) . '</dc:title>
		<dc:language>es</dc:language>
		<dc:identifier id="BookId" opf:scheme="UUID">urn:uuid:' . $uuid . '</dc:identifier>
		<dc:creator opf:role="aut">Almaden Bookster</dc:creator>
	</metadata>
	<manifest>
		<item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>
		' . $manifest_items . '
	</manifest>
	<spine toc="ncx">
		' . $spine_items . '
	</spine>
</package>';
	$zip->addFromString( 'OEBPS/content.opf', $content_opf );

	// 5. OEBPS/toc.ncx
	$toc_ncx = '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE ncx PUBLIC "-//NISO//DTD ncx 2005-1//EN" "http://www.daisy.org/z3986/2005/ncx-2005-1.dtd">
<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1">
	<head>
		<meta name="dtb:uid" content="urn:uuid:' . $uuid . '"/>
		<meta name="dtb:depth" content="1"/>
		<meta name="dtb:totalPageCount" content="0"/>
		<meta name="dtb:maxPageNumber" content="0"/>
	</head>
	<docTitle>
		<text>' . esc_html( $book->post_title ) . '</text>
	</docTitle>
	<navMap>
		' . $nav_points . '
	</navMap>
</ncx>';
	$zip->addFromString( 'OEBPS/toc.ncx', $toc_ncx );

	// Cerrar ZIP
	$zip->close();

	// Servir archivo
	$filename_download = sanitize_title( $book->post_title ) . '.epub';
	
	header( 'Content-Type: application/epub+zip' );
	header( 'Content-Disposition: attachment; filename="' . $filename_download . '"' );
	header( 'Content-Length: ' . filesize( $tmp_file ) );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );
	
	readfile( $tmp_file );
	unlink( $tmp_file ); // Borrar temporal
	exit;
}
add_action( 'admin_post_almaden_export_epub', 'almaden_export_epub_handler' );
