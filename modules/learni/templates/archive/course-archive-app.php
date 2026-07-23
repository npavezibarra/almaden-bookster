<?php
/**
 * Catálogo de cursos públicos.
 */

use AlmadenBookster\Learni\Dashboard\CreatorDashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$courses            = CreatorDashboard::get_public_courses( 24 );
$page_title         = function_exists( 'almaden_bookster_get_course_archive_title' ) ? almaden_bookster_get_course_archive_title() : __( 'Cursos', 'almaden-bookster' );
$course_creator_url = function_exists( 'almaden_bookster_get_course_creator_page_url' ) ? almaden_bookster_get_course_creator_page_url() : home_url( '/' );
$store_url          = function_exists( 'almaden_bookster_get_store_page_url' ) ? almaden_bookster_get_store_page_url() : home_url( '/' );

almaden_bookster_render_app_shell_start(
	array(
		'title'          => $page_title . ' - Almaden',
		'body_class'     => array( 'min-h-screen', 'flex', 'flex-col', 'theme-light', 'almaden-learni-dashboard', 'almaden-learni-catalog' ),
		'active_nav_key' => 'store',
		'logo_text'      => 'almaden',
	)
);
?>
<main id="almaden-course-listing" class="almaden-app-content-shell flex-1 pb-8">
	<div class="space-y-6">
		<section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
			<div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
				<div class="space-y-4">
					<div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
						<span class="h-2 w-2 rounded-full bg-amber-400"></span>
						<?php esc_html_e( 'Catálogo', 'almaden-bookster' ); ?>
					</div>
					<div>
						<h1 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">
							<?php echo esc_html( $page_title ); ?>
						</h1>
						<p class="mt-3 max-w-2xl text-base text-slate-500 sm:text-lg">
							<?php esc_html_e( 'Explora los cursos publicados y abre cada ficha para revisar su contenido, precio y materiales disponibles.', 'almaden-bookster' ); ?>
						</p>
					</div>
					<div class="flex flex-wrap gap-3 text-sm">
						<span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600"><?php echo esc_html( (string) count( $courses ) ); ?> <?php esc_html_e( 'cursos publicados', 'almaden-bookster' ); ?></span>
						<span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600"><?php esc_html_e( 'Compra segura', 'almaden-bookster' ); ?></span>
						<span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600"><?php esc_html_e( 'Acceso inmediato', 'almaden-bookster' ); ?></span>
					</div>
				</div>

				<div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5 text-slate-900">
					<h2 class="text-lg font-semibold"><?php esc_html_e( 'Abrir curso', 'almaden-bookster' ); ?></h2>
					<p class="mt-1 text-sm text-slate-500">
						<?php esc_html_e( 'Si eres creador, puedes ir a la Sala de clases para editar tus cursos.', 'almaden-bookster' ); ?>
					</p>
					<div class="mt-4 grid gap-3">
						<a href="<?php echo esc_url( $course_creator_url ); ?>" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
							<?php esc_html_e( 'Ir a Sala de clases', 'almaden-bookster' ); ?>
						</a>
						<a href="<?php echo esc_url( $store_url ); ?>" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-amber-300 hover:text-slate-950">
							<?php esc_html_e( 'Ver tienda', 'almaden-bookster' ); ?>
						</a>
					</div>
				</div>
			</div>
		</section>

		<section class="space-y-4">
			<div class="flex items-end justify-between gap-4">
				<div>
					<h2 class="text-2xl font-semibold text-slate-950"><?php esc_html_e( 'Cursos publicados', 'almaden-bookster' ); ?></h2>
					<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Selecciona un curso para ver su ficha pública.', 'almaden-bookster' ); ?></p>
				</div>
			</div>

			<?php if ( ! empty( $courses ) ) : ?>
				<div class="grid gap-5 md:grid-cols-2 2xl:grid-cols-3">
					<?php foreach ( $courses as $course ) : ?>
						<?php
						$course_id = isset( $course['id'] ) ? (int) $course['id'] : 0;
						$card_url  = isset( $course['url'] ) && '' !== (string) $course['url'] ? (string) $course['url'] : get_permalink( $course_id );
						?>
						<a href="<?php echo esc_url( $card_url ); ?>" class="almaden-learni-course-card group overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
							<div class="relative aspect-[16/9] bg-slate-100">
								<span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600 shadow-sm">
									<?php echo esc_html( $course['lesson_count'] ); ?> <?php esc_html_e( 'lecciones', 'almaden-bookster' ); ?>
								</span>
								<?php if ( ! empty( $course['thumbnail_url'] ) ) : ?>
									<img src="<?php echo esc_url( $course['thumbnail_url'] ); ?>" alt="" class="h-full w-full object-cover">
								<?php else : ?>
									<div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-900 to-slate-700 text-white">
										<span class="text-sm font-semibold uppercase tracking-[0.3em] text-white/70"><?php esc_html_e( 'Curso', 'almaden-bookster' ); ?></span>
									</div>
								<?php endif; ?>
							</div>
							<div class="space-y-4 p-5">
								<div>
									<p class="text-xs uppercase tracking-[0.22em] text-slate-400"><?php echo esc_html( $course['author_name'] ?? '' ); ?></p>
									<h3 class="mt-2 text-[1.35rem] font-semibold leading-tight text-slate-950 group-hover:text-amber-700">
										<?php echo esc_html( $course['title'] ); ?>
									</h3>
								</div>
								<p class="text-sm leading-6 text-slate-500">
									<?php echo esc_html( $course['excerpt'] ); ?>
								</p>
								<div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-4">
									<div class="space-y-1">
										<p class="text-sm font-semibold text-slate-950"><?php echo esc_html( $course['price_label'] ); ?></p>
										<p class="text-xs uppercase tracking-[0.18em] text-slate-400"><?php echo esc_html( $course['published'] ?? '' ); ?></p>
									</div>
									<span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-900">
										<?php echo $course['has_quiz'] ? esc_html__( 'Con evaluación', 'almaden-bookster' ) : esc_html__( 'Sin evaluación', 'almaden-bookster' ); ?>
									</span>
								</div>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="rounded-[2rem] border border-dashed border-slate-300 bg-white/70 p-10 text-center">
					<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
						<span class="dashicons dashicons-welcome-add-page"></span>
					</div>
					<h3 class="mt-4 text-xl font-semibold text-slate-950"><?php esc_html_e( 'Todavía no hay cursos publicados', 'almaden-bookster' ); ?></h3>
					<p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">
						<?php esc_html_e( 'Cuando un curso pase a publicado, aparecerá aquí como una tarjeta para explorar o comprar.', 'almaden-bookster' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</section>
	</div>
</main>
<?php
almaden_bookster_render_app_shell_end();
