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
		'book_chapter_flow_mode' => 'left',
		'chapter_transition_blank_mode' => 'intentional_text',
		'chapter_transition_blank_text' => 'Página intencional',
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
		'title'   => 'Capítulo ' . $chapter_number,
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
		'background: context {',
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
		'#text(font: "Inter Tight", size: 12pt, weight: 700, style: "italic", tracking: 3.75pt)',
		'Capítulo 1',
		'#line(length: 100%, stroke: 0.35pt)',
	'#set page(width: 14cm, height: 20cm, margin: (top: ',
		'#align(center)[ ÍNDICE ]',
	'header: context {',
	'footer: context {',
);
foreach ( $required_typography as $required ) {
	if ( false === strpos( $document['source'], $required ) ) {
		fwrite( STDERR, 'Falta configuración Typst: ' . $required . PHP_EOL );
		exit( 1 );
	}
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

$output   = isset( $argv[1] ) ? $argv[1] : sys_get_temp_dir() . '/almaden-typst-regression.typ';
$document['source'] .= "\n#context { [#metadata(query(<almaden-chapter-start>).map(mark => (title: mark.value, page: mark.location().page()))) <almaden-parity-test-report>] }\n";
file_put_contents( $output, $document['source'] );
file_put_contents( $output . '.expected.txt', $document['semantic_text'] );
echo $output . PHP_EOL;
