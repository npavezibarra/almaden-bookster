<?php
define( 'ALMADEN_TYPST_TESTING', true );

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		return trim( preg_replace( '/[\r\n\t]+/', ' ', strip_tags( (string) $value ) ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $value ) {
		return trim( (string) $value );
	}
}

require_once dirname( __DIR__ ) . '/includes/pdf-typst/typst-document.php';

$prefix_visibility = almaden_bookster_typst_chapter_opening_visibility(
	array(
		'title'       => 'Capítulo con cabecera en blanco',
		'hide_header' => '1',
	),
	array(
		'chapter_prefix_show' => 1,
	)
);
if ( empty( $prefix_visibility['show_prefix'] ) ) {
	fwrite( STDERR, 'Ocultar la cabecera corrida ocultó también el prefijo editorial del capítulo.' . PHP_EOL );
	exit( 1 );
}

$problem_paragraph = 'En los próximos años, la ventaja competitiva no dependerá únicamente del acceso a la tecnología, sino de la habilidad para integrarla de manera inteligente en procesos, organizaciones y proyectos personales. Quienes aprendan a combinar pensamiento crítico, conocimiento especializado y herramientas de inteligencia artificial estarán mejor preparados para adaptarse a un entorno en constante cambio y para convertir los avances tecnológicos en oportunidades reales de crecimiento. La inteligencia artificial está transformando la forma en que las personas crean, aprenden y trabajan. Lo que antes requería equipos completos o largos procesos técnicos ahora puede realizarse en cuestión de minutos, permitiendo que individuos y pequeñas organizaciones desarrollen proyectos con una velocidad sin precedentes. Sin embargo, esta aceleración no elimina la importancia del criterio humano; por el contrario, hace aún más valiosa la capacidad de formular buenas preguntas, evaluar resultados y tomar decisiones fundamentadas.';

$payload = array(
	'title'    => 'Regresión de paginación',
	'settings' => array(
		'unit'                   => 'cm',
		'page_width'             => 14,
		'page_height'            => 20,
		'margin_top'             => 2,
		'margin_bottom'          => 2,
		'margin_left_odd'        => 2.4,
		'margin_right_odd'       => 1.8,
		'margin_left_even'       => 1.8,
		'margin_right_even'      => 2.4,
		'padding_top'            => 0.2,
		'padding_bottom'         => 0.3,
		'padding_left'           => 0.2,
		'padding_right'          => 0.2,
		'bleeding'               => 0.3,
		'font_size_content'      => 12,
		'font_family_content'    => 'Libertinus Serif',
		'font_weight_content'    => '500',
		'line_height_content'    => 1.2,
		'content_text_align'     => 'justify',
		'content_text_align_last' => 'left',
		'content_hyphenation'    => '1',
		'content_hyphenation_exceptions' => 'tecnología; organizaciones',
		'content_paragraph_indent' => 0,
		'content_paragraph_spacing' => 4,
		'book_language'          => 'es',
		'chapter_title_font_size' => 22,
		'chapter_prefix_show'     => 1,
		'chapter_prefix_font_family' => 'Inter Tight',
		'chapter_prefix_font_size' => 16,
		'chapter_prefix_font_weight' => '700',
		'chapter_prefix_font_style' => 'italic',
		'chapter_prefix_letter_spacing' => 5,
		'chapter_prefix_ornament' => 'line_below',
		'chapter_prefix_align'     => 'right',
		'book_chapter_flow_mode' => 'left',
		'chapter_transition_blank_mode' => 'intentional_text',
		'chapter_transition_blank_text' => 'Página intencional',
		'footnote_mode'          => 'chapter',
		'footnote_chapter_new_page' => 1,
		'page_styles'             => array(
			array(
				'page_number' => 1,
				'style'       => array(
					'background'  => array(
						'type'  => 'color',
						'color' => '#f6efe2',
					),
					'text_colors' => array(
						'content' => '#2d2926',
						'header'  => '#315c47',
						'footer'  => '#315c47',
						'opening' => '#7a3028',
					),
				),
			),
		),
	),
	'chapters' => array(
		array(
			'title'   => 'Índice',
			'content' => '',
			'is_toc'  => '1',
			'toc_font_family' => 'Inter Tight',
			'toc_font_size'   => 14,
			'toc_font_weight'  => '700',
			'toc_font_style'   => 'italic',
			'toc_letter_spacing' => 0.4,
			'toc_line_height'  => 1.3,
			'toc_item_align'   => 'right',
			'toc_leader_style' => 'dashed',
			'toc_enumerate'    => 'decimal',
			'toc_title_font_family' => 'Outfit',
			'toc_title_font_size' => 26,
			'toc_title_font_style' => 'normal',
			'toc_title_font_weight' => '800',
			'toc_title_text_transform' => 'uppercase',
			'toc_title_padding_top' => 0.4,
			'toc_title_padding_bottom' => 1.2,
			'toc_title_line_height' => 1.1,
		),
			array(
				'title'   => 'Introducción',
				'content' => "La **inteligencia artificial** está transformando el trabajo.\n\n" .
					"A medida que estas herramientas se vuelven más accesibles, surge un nuevo desafío.\n\n" .
				$problem_paragraph . "\n\n" . $problem_paragraph .
					"\n\nTexto con nota[^1] y <foreign lang=\"en\">a complete foreign sentence</foreign>.\n\n" .
					"> Una cita que debe conservarse.\n\n[align=center]\nTexto centrado.\n[/align]\n\n" .
					"- Primer elemento\n- Segundo elemento\n\n[gap:3mm]\n\n" .
					"* Tercer elemento\n* Cuarto elemento\n\n[gap:3mm]\n\n" .
					"[^1]: Esta nota debe aparecer completa.",
				'chapter_blank_before' => '2',
				'chapter_blank_after'  => '1',
			),
	),
);

$payload['settings']['credits_config'] = array(
	'editorial' => array(
		'edition_number'  => '1',
		'publication_date'=> '2026-08',
		'printer'         => 'A Impresores',
		'blank_before'    => 2,
		'blank_after'     => 2,
	),
	'people' => array(
		array(
			'name'         => 'John Q. Est',
			'role'         => 'author',
			'show_contact' => 0,
		),
		array(
			'name'         => 'Mariana Villegas',
			'role'         => 'editor',
			'website'      => 'http://pontara.cl',
			'show_contact' => 1,
		),
	),
	'logos' => array(
		array(
			'show_author_name'   => 1,
			'author_font_family' => 'Google Sans',
			'author_font_size'   => 16,
		),
	),
	'legal' => array(
		'copyright_text' => 'Queda rigurosamente prohibida la reproducción parcial o total de esta obra.',
		'license'        => 'all_rights_reserved',
	),
	'section_order' => array( 'logos', 'people', 'editorial', 'collaborators', 'legal' ),
	'section_styles' => array(
		'people' => array(
			'font_family' => 'Inter Tight',
			'text_align'  => 'center',
		),
	),
);
for ( $chapter_number = 2; $chapter_number <= 17; ++$chapter_number ) {
	$payload['chapters'][] = array(
		'title'   => 17 === $chapter_number
			? 'Capítulo 17 con un título deliberadamente largo que debe ocupar dos líneas en el índice'
			: 'Capítulo ' . $chapter_number,
		'content' => 'Contenido de prueba para comprobar un índice largo.',
	);
}
$payload['chapters'][] = array(
	'title'                => 'Créditos',
	'content'              => 'Este placeholder no debe aparecer en el PDF.',
	'is_credits'           => '1',
	'credits_author_label' => 'Johnny Q. Est',
);

$document = almaden_bookster_build_typst_document( $payload );
if ( false !== strpos( $document['source'], "#context {\n#set text" ) ) {
	fwrite( STDERR, 'El índice quedó envuelto en modo código y Typst rechazará sus llamadas de contenido.' . PHP_EOL );
	exit( 1 );
}
if ( false !== strpos( $document['source'], 'fill: context' ) ) {
	fwrite( STDERR, 'Un parámetro fill recibió contenido contextual en vez de un color Typst.' . PHP_EOL );
	exit( 1 );
}
$required_typography = array(
		'background: almaden-page-background()',
		'rect(width: 100%, height: 100%, fill: almaden-page-style-color("fill"))',
		'#set text(fill: rgb("111111"), font: "Libertinus Serif", size: 12pt, weight: 500, lang: "es", hyphenate: true',
		'#line(length: 100%, stroke: 0.35pt)',
		'#set par(justify: true, leading: 0.2em, spacing: 4pt, first-line-indent: 0pt)',
		'#align(left)[',
		'#text(hyphenate: false)[tecnología]',
		'#let almaden-current-chapter-title() = context {',
		'#metadata("Índice") <almaden-chapter-start>',
		'#metadata("Introducción") <almaden-chapter-start-2>',
		'#metadata("chapter-before") <almaden-intentional-blank>',
		'#metadata("chapter-after") <almaden-intentional-blank>',
		'#metadata("intentional_text") <almaden-chapter-parity-break>',
		'#metadata("intentional_text") <almaden-transition-2>',
		'#pagebreak(to: "even")',
		'place(center + horizon)[#text(fill: rgb("111111"))[Página intencional]]',
		'#let almaden-is-chapter-transition-page() = {',
		'#set text(font: "Outfit", size: 19.5pt, weight: 800, style: "normal", tracking: 0pt)',
		'#set text(font: "Inter Tight", size: 10.5pt, weight: 700, style: "italic", tracking: 0.3pt)',
		'#align(right)[#text(font: "Inter Tight", size: 16pt, weight: 700, style: "italic", tracking: 5pt)',
		'#text(font: "Inter Tight", size: 16pt, weight: 700, style: "italic", tracking: 5pt)',
		'#block(width: 100%, breakable: false, inset: (top: 0cm, bottom: 1.5cm, left: 0cm, right: 0cm))',
		'#set par(justify: false, first-line-indent: 0pt, leading: 0.2em, spacing: 0pt)',
		'Capítulo 1',
		'#line(length: 100%, stroke: 0.35pt)',
	'#set page(width: 14cm, height: 20cm, margin: (top: ',
		'#align(center)[ ÍNDICE ]',
	'header: context {',
	'footer: context {',
);
$list_count = substr_count( $document['source'], '#list(' );
if ( $list_count < 2 ) {
	fwrite( STDERR, 'La sintaxis Markdown con bullets `*` no se convirtió en listas Typst.' . PHP_EOL );
	exit( 1 );
}
$tight_list = almaden_bookster_typst_render_blocks( "* Primer elemento\n* Segundo elemento\nTexto posterior sin línea vacía." );
if ( ! preg_match( '/#list\([\s\S]*?\)\s+#par\[Texto posterior sin línea vacía\.\]/', $tight_list ) ) {
	fwrite( STDERR, 'Una lista seguida por texto sin línea vacía no se cerró antes del párrafo Typst.' . PHP_EOL );
	exit( 1 );
}
foreach ( $required_typography as $required ) {
	if ( false === strpos( $document['source'], $required ) ) {
		fwrite( STDERR, 'Falta configuración Typst: ' . $required . PHP_EOL );
		exit( 1 );
	}
}

$chapter_controls_payload = array(
	'title'    => 'Controles de capítulos',
	'settings' => array(
		'unit'                           => 'cm',
		'page_width'                     => 14,
		'page_height'                    => 21,
		'margin_top'                     => 2,
		'margin_bottom'                  => 2,
		'font_family_content'            => 'Libertinus Serif',
		'font_size_content'              => 11,
		'book_language'                  => 'es',
		'book_separate_opening_content'  => 1,
		'chapter_page_one_align'         => 'right-bottom',
		'chapter_prefix_show'            => 1,
		'chapter_prefix_template'        => 'PREFIJO {N}',
		'chapter_prefix_position'        => 'below',
		'chapter_prefix_align'           => 'center',
		'chapter_prefix_font_family'     => 'Libertinus Serif',
		'chapter_prefix_font_size'       => 18,
		'chapter_prefix_font_weight'     => 800,
		'chapter_prefix_font_style'      => 'italic',
		'chapter_prefix_letter_spacing'  => 2.5,
		'chapter_prefix_ornament'        => 'asterisks',
		'chapter_title_font_family'      => 'Libertinus Serif',
		'chapter_title_font_size'        => 32,
		'chapter_title_font_weight'      => 600,
		'chapter_title_font_style'       => 'italic',
		'chapter_title_align'            => 'right',
		'chapter_title_text_transform'   => 'uppercase',
		'chapter_title_letter_spacing'   => 1.5,
		'chapter_title_padding_top'      => 0.4,
		'chapter_title_padding_bottom'   => 0.6,
		'chapter_title_padding_left'     => 0.7,
		'chapter_title_padding_right'    => 0.8,
		'chapter_title_line_height'      => 1.4,
		'chapter_title_hyphenate'        => 1,
		'chapter_subtitle_show'          => 1,
		'chapter_subtitle_font_family'   => 'Libertinus Serif',
		'chapter_subtitle_font_size'     => 14,
		'chapter_subtitle_align'         => 'left',
		'chapter_subtitle_font_style'    => 'italic',
		'chapter_subtitle_text_transform'=> 'uppercase',
		'chapter_subtitle_font_weight'   => 500,
		'chapter_subtitle_margin_top'    => 0.2,
		'chapter_subtitle_margin_bottom' => 0.4,
		'chapter_subtitle_letter_spacing'=> 0.8,
	),
	'chapters' => array(
		array(
			'title'         => 'Título de control',
			'subtitle_text' => 'Subtítulo de control',
			'content'       => 'Contenido de control.',
		),
	),
);
$chapter_controls_document = almaden_bookster_build_typst_document( $chapter_controls_payload );
$chapter_controls_source = $chapter_controls_document['source'];
$chapter_control_fragments = array(
	'#place(right + bottom)',
	'#block(width: 100%, breakable: false, inset: (top: 0.4cm, bottom: 0.6cm, left: 0.7cm, right: 0.8cm))',
	'#set par(justify: false, first-line-indent: 0pt, leading: 0.4em, spacing: 0pt)',
	'#align(right)[#heading(level: 1, outlined: true)[#text(font: "Libertinus Serif", size: 32pt, weight: 600, style: "italic", tracking: 1.5pt, hyphenate: true)[TÍTULO DE CONTROL]]]',
	'#block(width: 100%, breakable: false)',
	'#align(center)[#text(font: "Libertinus Serif", size: 18pt, weight: 800, style: "italic", tracking: 2.5pt)[PREFIJO 1]]',
	'#align(center)[\*\*\*]',
	'#block(width: 100%, breakable: false, inset: (top: 0.2cm, bottom: 0.4cm))',
	'#align(left)[#text(font: "Libertinus Serif", size: 14pt, weight: 500, style: "italic", tracking: 0.8pt)[SUBTÍTULO DE CONTROL]]',
);
foreach ( $chapter_control_fragments as $fragment ) {
	if ( false === strpos( $chapter_controls_source, $fragment ) ) {
		fwrite( STDERR, 'Un control de la sección Capítulos no llegó al Typst: ' . $fragment . PHP_EOL );
		exit( 1 );
	}
}
if ( false === strpos( $chapter_controls_source, '<almaden-chapter-opening>' ) || false === strpos( $chapter_controls_source, 'is_chapter_opening_page' ) || false === strpos( $chapter_controls_source, 'use_first_page_config = is_chapter_opening_page or is_first_text_page_after_image or is_first_chapter_page' ) ) {
	fwrite( STDERR, 'La cabecera y el pie de la primera página no están vinculados a la apertura real.' . PHP_EOL );
	exit( 1 );
}

// An empty per-chapter override must inherit the configured book-level family.
$inherited_subtitle_payload = $chapter_controls_payload;
$inherited_subtitle_payload['settings']['font_family_content'] = 'Libertinus Serif';
$inherited_subtitle_payload['settings']['chapter_subtitle_font_family'] = 'Cormorant Garamond';
$inherited_subtitle_payload['chapters'][0]['subtitle_font_family'] = '';
$inherited_subtitle_source = almaden_bookster_build_typst_document( $inherited_subtitle_payload )['source'];
if ( false === strpos( $inherited_subtitle_source, '#text(font: "Cormorant Garamond", size: 14pt' ) ) {
	fwrite( STDERR, 'La familia global del subtítulo no se heredó cuando el override del capítulo está vacío.' . PHP_EOL );
	exit( 1 );
}
$title_position = strpos( $chapter_controls_source, 'TÍTULO DE CONTROL' );
$prefix_position = strpos( $chapter_controls_source, 'PREFIJO 1' );
if ( false === $title_position || false === $prefix_position || $prefix_position < $title_position ) {
	fwrite( STDERR, 'La posición inferior del prefijo no se respetó.' . PHP_EOL );
	exit( 1 );
}
$toc_title_style_count = substr_count( $document['source'], '#set text(font: "Outfit", size: 19.5pt, weight: 800, style: "normal", tracking: 0pt)' );
if ( 1 !== $toc_title_style_count ) {
	fwrite( STDERR, 'El índice está generando más de un título visible en el Typst base.' . PHP_EOL );
	exit( 1 );
}

$full_blank_source = almaden_bookster_typst_chapter_parity_break(
	array(
		'book_chapter_flow_mode'         => 'left',
		'chapter_transition_blank_mode' => 'full_blank',
	)
);
if ( false === strpos( $full_blank_source, '#metadata("full_blank") <almaden-chapter-parity-break>' ) || false === strpos( $full_blank_source, '#pagebreak(to: "even")' ) ) {
	fwrite( STDERR, 'El modo Full Blanco no generó una transición limpia.' . PHP_EOL );
	exit( 1 );
}

$header_footer_source = almaden_bookster_typst_chapter_parity_break(
	array(
		'book_chapter_flow_mode'         => 'left',
		'chapter_transition_blank_mode' => 'blank_with_header_footer',
	)
);
if ( false === strpos( $header_footer_source, '#metadata("blank_with_header_footer") <almaden-chapter-parity-break>' ) ) {
	fwrite( STDERR, 'El blanco con cabecera y pie perdió su marcador de transición.' . PHP_EOL );
	exit( 1 );
}

$continuous_source = almaden_bookster_typst_chapter_parity_break(
	array(
		'book_chapter_flow_mode' => 'continuous',
	)
);
if ( '' !== $continuous_source ) {
	fwrite( STDERR, 'El flujo continuo generó una transición de paridad.' . PHP_EOL );
	exit( 1 );
}

$toc_title_override_payload = $payload;
$toc_title_override_payload['chapters'][0]['toc_title_text'] = 'Contenido';
$toc_title_override_payload['chapters'][0]['toc_hide_title'] = '1';
$toc_title_override_document = almaden_bookster_build_typst_document( $toc_title_override_payload );
if ( false === strpos( $toc_title_override_document['source'], '#metadata("Contenido") <almaden-chapter-start>' ) ) {
	fwrite( STDERR, 'El título del Índice no aceptó el texto personalizado.' . PHP_EOL );
	exit( 1 );
}
if ( false !== strpos( $toc_title_override_document['source'], '#set text(font: "Outfit", size: 19.5pt, weight: 800, style: "normal", tracking: 0pt)' ) ) {
	fwrite( STDERR, 'El título oculto del Índice siguió generando el heading visible.' . PHP_EOL );
	exit( 1 );
}
if ( false === strpos( $toc_title_override_document['source'], 'Contenido' ) ) {
	fwrite( STDERR, 'El texto personalizado del Índice no llegó al Typst generado.' . PHP_EOL );
	exit( 1 );
}

$toc_visible_title_payload = $payload;
$toc_visible_title_payload['chapters'][0]['toc_title_text'] = 'Contenido';
$toc_visible_title_payload['chapters'][0]['toc_hide_title'] = '0';
$toc_visible_title_payload['chapters'][0]['toc_hide_header'] = '1';
$toc_visible_title_payload['chapters'][0]['toc_hide_page_numbers'] = '1';
$toc_visible_title_payload['chapters'][0]['hide_header'] = '0';
$toc_visible_title_payload['chapters'][0]['hide_footer'] = '0';
$toc_visible_title_payload['chapters'][0]['toc_page_number_offset'] = '-1.2';
$toc_visible_title_payload['chapters'][0]['toc_enumerate'] = 'roman';
$toc_visible_title_document = almaden_bookster_build_typst_document( $toc_visible_title_payload );
if ( false === strpos( $toc_visible_title_document['source'], '#set text(font: "Outfit", size: 19.5pt, weight: 800, style: "normal", tracking: 0pt)' ) ) {
	fwrite( STDERR, 'El título visible del Índice dejó de renderizarse cuando la cabecera se ocultó.' . PHP_EOL );
	exit( 1 );
}
if ( false === strpos( $toc_visible_title_document['source'], '#metadata("Contenido") <almaden-hide-header>' ) ) {
	fwrite( STDERR, 'El Índice no propagó la ocultación de cabecera a los metadatos del PDF.' . PHP_EOL );
	exit( 1 );
}
if ( false === strpos( $toc_visible_title_document['source'], '#metadata("Contenido") <almaden-hide-footer>' ) ) {
	fwrite( STDERR, 'El Índice no propagó la ocultación del pie a los metadatos del PDF.' . PHP_EOL );
	exit( 1 );
}
if ( false === strpos( $toc_visible_title_document['source'], '#move(dy: -1.2pt)' ) ) {
	fwrite( STDERR, 'El nuevo ajuste vertical del número de página no llegó al render del Índice.' . PHP_EOL );
	exit( 1 );
}
if ( false === strpos( $toc_visible_title_document['source'], 'columns: (number-width, 1fr, page-width)' ) ) {
	fwrite( STDERR, 'El Índice no conserva las celdas de numeración, contenido central y página.' . PHP_EOL );
	exit( 1 );
}
if ( false === strpos( $toc_visible_title_document['source'], 'let toc-main = [#align(right)[#toc-title#h(toc-gutter)#toc-leader]]' ) ) {
	fwrite( STDERR, 'El título y el leader no comparten el flujo de la celda central.' . PHP_EOL );
	exit( 1 );
}
if ( 1 !== substr_count( $toc_visible_title_document['source'], '#let toc-number-samples = ' ) || false === strpos( $toc_visible_title_document['source'], '[I.]' ) || false === strpos( $toc_visible_title_document['source'], '[XVII.]' ) ) {
	fwrite( STDERR, 'Las numeraciones del Índice no se reunieron en una muestra compartida.' . PHP_EOL );
	exit( 1 );
}
if ( false === strpos( $toc_visible_title_document['source'], 'toc-number-samples.fold(0pt, (current, sample) => calc.max(current, measure(sample).width))' ) ) {
	fwrite( STDERR, 'La columna de numeración no usa el ancho real de su número más ancho.' . PHP_EOL );
	exit( 1 );
}
if ( '#repeat([.], gap: 0.2em)' !== almaden_bookster_typst_toc_leader_fill( 'dotted' ) ) {
	fwrite( STDERR, 'El leader punteado del Índice no usa puntos tipográficos sobre la línea base del texto.' . PHP_EOL );
	exit( 1 );
}
if ( false === strpos( almaden_bookster_typst_toc_leader_fill( 'solid', 0.5 ), '#line(length: 100%, stroke: 0.5pt)' ) ) {
	fwrite( STDERR, 'El leader continuo del Índice no conserva su línea flexible.' . PHP_EOL );
	exit( 1 );
}
if ( '' !== almaden_bookster_typst_toc_leader_fill( 'none' ) ) {
	fwrite( STDERR, 'El modo sin leader del Índice todavía genera contenido visual.' . PHP_EOL );
	exit( 1 );
}
if ( false === strpos( $toc_visible_title_document['source'], '#box(width: 1fr, inset: 0pt)[#repeat([-], gap: 0.3em)]' ) ) {
	fwrite( STDERR, 'La línea del Índice no completa la última línea del título.' . PHP_EOL );
	exit( 1 );
}
if ( false === strpos( $toc_visible_title_document['source'], 'align: (left + top, left + top, right + bottom)' ) ) {
	fwrite( STDERR, 'Las celdas del Índice no conservan el número de página junto a la última línea del título.' . PHP_EOL );
	exit( 1 );
}

$hidden_title_payload = array(
	'title'    => 'Capítulo oculto',
	'settings' => array(
		'unit'                => 'cm',
		'page_width'          => 14,
		'page_height'         => 20,
		'margin_top'          => 2,
		'margin_bottom'       => 2,
		'font_family_content' => 'Libertinus Serif',
		'font_size_content'   => 12,
		'book_language'       => 'es',
		'chapter_title_font_size' => 22,
	),
	'chapters' => array(
		array(
			'title'      => 'Capítulo oculto',
			'hide_title' => '1',
			'content'    => 'Texto de prueba.',
		),
	),
);
$hidden_title_document = almaden_bookster_build_typst_document( $hidden_title_payload );
if ( false !== strpos( $hidden_title_document['source'], '#heading(level: 1, outlined: true)' ) ) {
	fwrite( STDERR, 'Un título oculto siguió generando el heading de apertura en Typst.' . PHP_EOL );
	exit( 1 );
}
if ( false === strpos( $hidden_title_document['source'], 'Texto de prueba.' ) ) {
	fwrite( STDERR, 'El capítulo oculto perdió su contenido principal.' . PHP_EOL );
	exit( 1 );
}

$chapter_endnote_payload = $hidden_title_payload;
$chapter_endnote_payload['settings']['footnote_mode'] = 'chapter';
$chapter_endnote_payload['settings']['footnote_chapter_new_page'] = 1;
$chapter_endnote_payload['chapters'][0]['content'] = "Texto con referencia[^1].\n\n[^1]: Nota al final del capítulo.";
$chapter_endnote_document = almaden_bookster_build_typst_document( $chapter_endnote_payload );
if ( ! preg_match( '/#pagebreak\(weak: true\)\s+#block\[\s+#v\([^)]*\)\s+#heading/', $chapter_endnote_document['source'] ) ) {
	fwrite( STDERR, 'Las referencias de capítulo no comenzaron en una página nueva cuando se solicitó.' . PHP_EOL );
	exit( 1 );
}
$chapter_endnote_payload['settings']['footnote_chapter_new_page'] = 0;
$inline_chapter_endnote_document = almaden_bookster_build_typst_document( $chapter_endnote_payload );
if ( false !== strpos( $inline_chapter_endnote_document['source'], '#pagebreak(weak: true)' ) ) {
	fwrite( STDERR, 'Las referencias de capítulo insertaron un salto de página con la opción desmarcada.' . PHP_EOL );
	exit( 1 );
}

$output   = isset( $argv[1] ) ? $argv[1] : sys_get_temp_dir() . '/almaden-typst-regression.typ';
$document['source'] .= "\n#context { [#metadata(query(<almaden-chapter-start>).map(mark => (title: mark.value, page: mark.location().page()))) <almaden-parity-test-report>] }\n";
file_put_contents( $output, $document['source'] );
file_put_contents( $output . '.expected.txt', $document['semantic_text'] );
echo $output . PHP_EOL;
