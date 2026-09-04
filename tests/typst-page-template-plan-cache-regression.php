<?php
define( 'ALMADEN_TYPST_TESTING', true );

require_once dirname( __DIR__ ) . '/includes/pdf-typst/page-templates/bootstrap.php';

$source = <<<'TYPST'
#metadata("almaden-flow-1") <almaden-flow-1>
#par[Texto de la plantilla actual.]
#metadata("almaden-flow-2") <almaden-flow-2>
#par[Texto de un capitulo posterior.]
TYPST;
$template = array(
	'instance_id' => 'tpl-cache',
	'template_id' => 'one-column-one-image',
	'page_number' => 2,
	'anchor' => array( 'flow_id' => 'almaden-flow-1' ),
);
$context = array( 'columns_count' => 2, 'columns_gap' => 0.8, 'unit' => 'cm' );
$key = almaden_bookster_typst_page_template_plan_key( $source, $template, $context, 'almaden-flow-2' );
$later_edit = str_replace( 'Texto de un capitulo posterior.', 'Contenido posterior completamente editado.', $source );
$later_key = almaden_bookster_typst_page_template_plan_key( $later_edit, $template, $context, 'almaden-flow-2' );
if ( $key !== $later_key ) {
	fwrite( STDERR, "Una edicion posterior invalido innecesariamente el plan anterior.\n" );
	exit( 1 );
}
$resolved_template = $template;
$resolved_template['resolved_page'] = 19;
if ( $key !== almaden_bookster_typst_page_template_plan_key( $source, $resolved_template, $context, 'almaden-flow-2' ) ) {
	fwrite( STDERR, "La pagina resuelta invalido un plan ligado a la misma ancla.\n" );
	exit( 1 );
}

$local_edit = str_replace( 'Texto de la plantilla actual.', 'Texto local modificado.', $source );
$local_key = almaden_bookster_typst_page_template_plan_key( $local_edit, $template, $context, 'almaden-flow-2' );
if ( $key === $local_key ) {
	fwrite( STDERR, "Una edicion dentro de la zona no invalido el plan.\n" );
	exit( 1 );
}

$updated = str_replace( '#par[Texto de la plantilla actual.]', '#page[Plantilla medida.]', $source );
$patch = almaden_bookster_typst_page_template_plan_patch( $source, $updated );
if ( $updated !== almaden_bookster_typst_page_template_apply_plan_patch( $source, $patch ) ) {
	fwrite( STDERR, "El parche incremental no reconstruyo la composicion medida.\n" );
	exit( 1 );
}
if ( null !== almaden_bookster_typst_page_template_apply_plan_patch( $local_edit, $patch ) ) {
	fwrite( STDERR, "El parche se aplico sobre una fuente incompatible.\n" );
	exit( 1 );
}

echo "typst-page-template-plan-cache-regression-ok\n";
