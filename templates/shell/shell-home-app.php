<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

almaden_bookster_render_app_shell_start(
	array(
		'title'          => function_exists( 'almaden_bookster_get_shell_home_title' ) ? almaden_bookster_get_shell_home_title() . ' - Almaden' : 'Almaden App - Almaden',
		'body_id'        => 'almaden-shell-home-body',
		'active_nav_key' => 'shell_home',
	)
);

$shell_links = array(
	array(
		'key'   => 'creator',
		'label' => function_exists( 'almaden_bookster_get_creator_title' ) ? almaden_bookster_get_creator_title() : 'Taller',
		'url'   => function_exists( 'almaden_bookster_get_creator_page_url' ) ? almaden_bookster_get_creator_page_url() : home_url( '/' ),
		'desc'  => 'Gestiona libros, capítulos y exportaciones.',
	),
	array(
		'key'   => 'authors',
		'label' => function_exists( 'almaden_bookster_get_authors_title' ) ? almaden_bookster_get_authors_title() : 'Autores',
		'url'   => function_exists( 'almaden_bookster_get_authors_page_url' ) ? almaden_bookster_get_authors_page_url() : home_url( '/' ),
		'desc'  => 'Consulta y administra el directorio de autores.',
	),
	array(
		'key'   => 'course_archive',
		'label' => function_exists( 'almaden_bookster_get_course_archive_title' ) ? almaden_bookster_get_course_archive_title() : 'Cursos',
		'url'   => function_exists( 'almaden_bookster_get_course_archive_page_url' ) ? almaden_bookster_get_course_archive_page_url() : home_url( '/' ),
		'desc'  => 'Explora la sala pública de cursos.',
	),
	array(
		'key'   => 'store',
		'label' => function_exists( 'almaden_bookster_get_store_title' ) ? almaden_bookster_get_store_title() : 'Ebook Store',
		'url'   => function_exists( 'almaden_bookster_get_store_page_url' ) ? almaden_bookster_get_store_page_url() : home_url( '/' ),
		'desc'  => 'Entra al catálogo público de ebooks.',
	),
	array(
		'key'   => 'reading_stats',
		'label' => function_exists( 'almaden_bookster_get_reading_stats_title' ) ? almaden_bookster_get_reading_stats_title() : 'My Reading Stats',
		'url'   => function_exists( 'almaden_bookster_get_reading_stats_page_url' ) ? almaden_bookster_get_reading_stats_page_url() : home_url( '/' ),
		'desc'  => 'Revisa tus highlights, actividad y progreso de lectura.',
	),
);

$shell_links = array_values(
	array_filter(
		$shell_links,
		static function( $item ) {
			$item_key = isset( $item['key'] ) ? sanitize_key( (string) $item['key'] ) : '';
			if ( '' === $item_key ) {
				return true;
			}

			return function_exists( 'almaden_bookster_user_can_access_frontend_page' ) ? almaden_bookster_user_can_access_frontend_page( $item_key ) : true;
		}
	)
);
?>
<main id="almaden-shell-home" class="almaden-app-content-shell flex-1 pb-16">
	<section class="grid gap-10 py-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
		<div>
			<p class="mb-3 text-xs font-semibold uppercase tracking-[0.3em] text-gray-400">Almaden Shell</p>
			<h1 class="max-w-2xl text-4xl font-bold tracking-tight text-black sm:text-5xl">Almaden App</h1>
			<p class="mt-5 max-w-2xl text-lg leading-8 text-gray-600">Esta página es el Home del shell. WordPress puede mostrarla en su menú regular y, desde aquí, el usuario entra a las secciones internas de la aplicación sin mezclar la navegación del sitio con la del shell.</p>
			<div class="mt-8 flex flex-wrap gap-3">
				<?php if ( ! empty( $shell_links ) ) : ?>
					<?php foreach ( $shell_links as $link ) : ?>
						<a href="<?php echo esc_url( $link['url'] ); ?>" class="inline-flex items-center rounded-full border border-black bg-black px-5 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
							<?php echo esc_html( $link['label'] ); ?>
						</a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<div class="rounded-[2rem] border border-gray-200 bg-white p-6 shadow-sm">
			<p class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-400">Qué hace este Home</p>
			<ul class="mt-5 space-y-4 text-sm leading-6 text-gray-600">
				<li>Concentra el acceso principal al shell en una sola URL configurable.</li>
				<li>Permite mostrar u ocultar ese enlace en el menú público de WordPress.</li>
				<li>Mantiene separados el menú del shell (`almaden-app-nav`) y el menú regular del sitio.</li>
			</ul>
		</div>
	</section>

	<section class="pb-10">
		<div class="mb-5 flex items-end justify-between gap-4">
			<div>
				<p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Accesos rápidos</p>
				<h2 class="mt-2 text-2xl font-bold text-black">Secciones del shell</h2>
			</div>
		</div>
		<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
			<?php foreach ( $shell_links as $link ) : ?>
				<a href="<?php echo esc_url( $link['url'] ); ?>" class="group rounded-[1.5rem] border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
					<p class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-400"><?php echo esc_html( $link['label'] ); ?></p>
					<p class="mt-3 text-base leading-7 text-gray-600"><?php echo esc_html( $link['desc'] ); ?></p>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
</main>
<?php
almaden_bookster_render_app_shell_end();
