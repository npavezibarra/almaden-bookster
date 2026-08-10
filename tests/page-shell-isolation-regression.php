<?php

$root = dirname( __DIR__ );

$read = static function( $relative_path ) use ( $root ) {
	$contents = file_get_contents( $root . '/' . $relative_path );
	if ( false === $contents ) {
		fwrite( STDERR, "No se pudo leer {$relative_path}.\n" );
		exit( 1 );
	}
	return $contents;
};

$assert_contains = static function( $contents, $needle, $message ) {
	if ( false === strpos( $contents, $needle ) ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
};

$assert_not_contains = static function( $contents, $needle, $message ) {
	if ( false !== strpos( $contents, $needle ) ) {
		fwrite( STDERR, $message . "\n" );
		exit( 1 );
	}
};

$plugin = $read( 'almaden-bookster.php' );
if ( ! preg_match( '/function almaden_bookster_activate_plugin\(\) \{(?<body>.*?)register_activation_hook/s', $plugin, $activation_match ) ) {
	fwrite( STDERR, "No se encontro el callback de activacion.\n" );
	exit( 1 );
}
if ( preg_match( '/almaden_bookster_sync_[a-z0-9_]+_page\s*\(/', $activation_match['body'] ) ) {
	fwrite( STDERR, "La activacion no debe sincronizar paginas del sitio.\n" );
	exit( 1 );
}

$frontend = $read( 'includes/frontend.php' );
$assert_not_contains(
	$frontend,
	"add_action( 'init', 'almaden_bookster_create_page'",
	'El frontend no debe crear paginas durante init.'
);

$onboarding = $read( 'includes/publishers/onboarding.php' );
$assert_not_contains(
	$onboarding,
	"add_action( 'init', 'almaden_bookster_sync_publisher_onboarding_page'",
	'El onboarding no debe crear su pagina durante init.'
);

$pages_admin = $read( 'includes/admin/admin-pages.php' );
$assert_contains(
	$pages_admin,
	"case 'shell_home':",
	'Pages debe conservar la sincronizacion explicita de la entrada al shell.'
);
$assert_contains(
	$pages_admin,
	'almaden_bookster_mark_shell_page( $sync_section );',
	'Las paginas sincronizadas deben identificarse como rutas del shell.'
);
$assert_not_contains(
	$pages_admin,
	'function almaden_bookster_sync_all_pages()',
	'No debe existir una operacion que cree todas las paginas implicitamente.'
);

$page_settings = $read( 'includes/frontend/pages-settings.php' );
$assert_contains(
	$page_settings,
	"'store_menu_enabled' => 0",
	'La tienda no debe entrar al menu de WordPress por defecto.'
);

$distribution = $read( 'includes/admin/admin-distribution.php' );
$assert_contains(
	$distribution,
	"'bookshelf_page_policy'       => 'manual'",
	'Bookshelf debe requerir configuracion manual por defecto.'
);
$assert_contains(
	$distribution,
	"'menu_injection_enabled'      => 0",
	'La inyeccion global de menu debe estar apagada por defecto.'
);

$menus = $read( 'includes/frontend/pages-menus.php' );
$assert_not_contains(
	$menus,
	"add_action( 'init', 'almaden_bookster_sync_bookshelf_navigation'",
	'La carga publica no debe modificar menus persistidos.'
);
$assert_contains(
	$menus,
	"add_filter( 'wp_list_pages_excludes', 'almaden_bookster_exclude_shell_pages_from_page_lists'",
	'Las rutas internas deben quedar fuera de las listas automaticas de paginas.'
);

fwrite( STDOUT, "Page shell isolation regression: OK\n" );
