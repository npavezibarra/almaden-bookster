<?php
use AlmadenBookster\Learni\Dashboard\CreatorDashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_archive_title = function_exists( 'almaden_bookster_get_course_archive_title' ) ? almaden_bookster_get_course_archive_title() : __( 'Sala de clases', 'almaden-bookster' );
$course_archive_url   = function_exists( 'almaden_bookster_get_course_archive_page_url' ) ? almaden_bookster_get_course_archive_page_url() : home_url( '/' );
$booklist_url         = function_exists( 'almaden_bookster_get_creator_page_url' ) ? almaden_bookster_get_creator_page_url() : home_url( '/' );
$authors_url          = function_exists( 'almaden_bookster_get_authors_page_url' ) ? almaden_bookster_get_authors_page_url() : home_url( '/' );
$store_url            = function_exists( 'almaden_bookster_get_store_page_url' ) ? almaden_bookster_get_store_page_url() : home_url( '/' );
$courses              = CreatorDashboard::get_public_courses( 24 );

if ( function_exists( 'almaden_bookster_render_app_shell_start' ) ) {
	almaden_bookster_render_app_shell_start(
		array(
			'title'          => $course_archive_title . ' - Almaden',
			'body_class'     => array( 'min-h-screen', 'flex', 'flex-col', 'theme-light', 'almaden-course-archive' ),
			'active_nav_key' => 'course_archive',
			'logo_text'      => 'almaden',
		)
	);
}
?>
<main id="almaden-course-archive-main" class="almaden-app-content-shell flex-1 bg-slate-50">
	<div class="pb-16 pt-8">
		<section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8 lg:p-10">
			<div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
				<div>
					<p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Learni</p>
					<h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950 sm:text-5xl"><?php echo esc_html( $course_archive_title ); ?></h1>
					<p class="mt-4 max-w-3xl text-base leading-7 text-slate-600">
						<?php esc_html_e( 'Explora todos los cursos publicados en la plataforma. Cada tarjeta resume el progreso editorial del curso y su estado de publicación.', 'almaden-bookster' ); ?>
					</p>
				</div>
				<div class="flex flex-wrap gap-3">
					<span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
						<?php echo esc_html( (string) count( $courses ) ); ?> <?php esc_html_e( 'cursos publicados', 'almaden-bookster' ); ?>
					</span>
				</div>
			</div>
		</section>

		<section class="mt-8">
			<?php if ( ! empty( $courses ) ) : ?>
				<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
					<?php foreach ( $courses as $course ) : ?>
						<article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_14px_40px_rgba(15,23,42,0.06)] transition-transform duration-200 hover:-translate-y-1">
							<a href="<?php echo esc_url( $course['url'] ); ?>" class="block">
								<div class="aspect-[16/9] bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700">
									<?php if ( ! empty( $course['thumbnail_url'] ) ) : ?>
										<img src="<?php echo esc_url( $course['thumbnail_url'] ); ?>" alt="<?php echo esc_attr( $course['title'] ); ?>" class="h-full w-full object-cover">
									<?php else : ?>
										<div class="flex h-full w-full items-center justify-center text-white">
											<div class="text-center">
												<p class="text-xs font-semibold uppercase tracking-[0.3em] text-white/60">Curso</p>
												<h2 class="mt-2 text-2xl font-semibold"><?php echo esc_html( $course['title'] ); ?></h2>
											</div>
										</div>
									<?php endif; ?>
								</div>
							</a>
							<div class="space-y-4 p-5">
								<div class="flex items-start justify-between gap-4">
									<div class="min-w-0">
										<p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400"><?php echo esc_html( $course['status_label'] ); ?></p>
										<h2 class="mt-1 truncate text-xl font-semibold tracking-tight text-slate-950"><?php echo esc_html( $course['title'] ); ?></h2>
										<p class="mt-1 text-sm text-slate-500">
											<?php echo esc_html( $course['author_name'] ? $course['author_name'] : __( 'Autor desconocido', 'almaden-bookster' ) ); ?>
										</p>
									</div>
									<span class="shrink-0 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
										<?php echo esc_html( (string) $course['lesson_count'] ); ?> <?php esc_html_e( 'lecciones', 'almaden-bookster' ); ?>
									</span>
								</div>

								<p class="text-sm leading-6 text-slate-600">
									<?php echo esc_html( $course['excerpt'] ); ?>
								</p>

								<div class="flex flex-wrap gap-2 text-xs font-medium text-slate-600">
									<span class="rounded-full bg-slate-100 px-3 py-1">
										<?php echo esc_html( $course['published'] ); ?>
									</span>
									<span class="rounded-full bg-slate-100 px-3 py-1">
										<?php echo $course['has_quiz'] ? esc_html__( 'Quiz listo', 'almaden-bookster' ) : esc_html__( 'Sin quiz', 'almaden-bookster' ); ?>
									</span>
								</div>

								<div class="flex items-center justify-between pt-2">
									<a href="<?php echo esc_url( $course['url'] ); ?>" class="inline-flex items-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
										<?php esc_html_e( 'Ver curso', 'almaden-bookster' ); ?>
									</a>
									<?php if ( is_user_logged_in() && current_user_can( 'edit_post', (int) $course['id'] ) ) : ?>
										<a href="<?php echo esc_url( get_edit_post_link( (int) $course['id'], 'raw' ) ); ?>" class="text-sm font-medium text-slate-500 transition hover:text-slate-950">
											<?php esc_html_e( 'Editar', 'almaden-bookster' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center">
					<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
						<span class="text-xl font-semibold">0</span>
					</div>
					<h2 class="mt-4 text-xl font-semibold text-slate-950"><?php esc_html_e( 'Todavía no hay cursos publicados', 'almaden-bookster' ); ?></h2>
					<p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">
						<?php esc_html_e( 'Cuando un curso pase a estado publicado, aparecerá aquí automáticamente como parte del archivo público.', 'almaden-bookster' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</section>
	</div>
</main>
<?php
if ( function_exists( 'almaden_bookster_render_app_shell_end' ) ) {
	almaden_bookster_render_app_shell_end();
}
