<?php

use AlmadenBookster\Learni\Dashboard\CreatorDashboard;
use AlmadenBookster\Learni\Dashboard\CourseEditorHandler;
use AlmadenBookster\Learni\PostTypes\Course;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user_id = get_current_user_id();
$courses         = CreatorDashboard::get_user_courses( $current_user_id );
$selected_course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
$selected_course = CreatorDashboard::get_selected_course( $selected_course_id, $current_user_id );
$selected_course_card = CreatorDashboard::get_selected_course_card( $selected_course_id, $current_user_id );
$course_created  = isset( $_GET['course_created'] ) && '1' === (string) $_GET['course_created'];
$active_tab      = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : 'curso';
$course_archive_url = function_exists( 'almaden_bookster_get_course_archive_page_url' ) ? almaden_bookster_get_course_archive_page_url() : home_url( '/' );
$booklist_url    = function_exists( 'almaden_bookster_get_creator_page_url' ) ? almaden_bookster_get_creator_page_url() : admin_url();
$authors_url     = function_exists( 'almaden_bookster_get_authors_page_url' ) ? almaden_bookster_get_authors_page_url() : admin_url();
$store_url       = function_exists( 'almaden_bookster_get_store_page_url' ) ? almaden_bookster_get_store_page_url() : admin_url();
$page_title      = function_exists( 'almaden_bookster_get_course_archive_title' ) ? almaden_bookster_get_course_archive_title() : __( 'Sala de clases', 'almaden-bookster' );

almaden_bookster_render_app_shell_start(
		array(
			'title'          => $page_title . ' - Almaden',
			'body_class'     => array( 'min-h-screen', 'flex', 'flex-col', 'theme-light', 'almaden-learni-dashboard' ),
			'active_nav_key' => 'course_archive',
			'logo_text'      => 'almaden',
		)
	);
?>
<main class="almaden-app-content-shell flex-1 pb-8">
	<div class="space-y-6">
		<?php if ( $course_created ) : ?>
			<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900">
				<?php esc_html_e( 'Curso creado correctamente. Ahora puedes editarlo desde la lista.', 'almaden-bookster' ); ?>
			</div>
		<?php endif; ?>

		<section class="almaden-learni-hero rounded-[2rem] text-white overflow-hidden">
			<div class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr] p-6 sm:p-8 lg:p-10">
				<div class="space-y-4">
					<div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-white/85">
						<span class="h-2 w-2 rounded-full bg-amber-300"></span>
						<?php esc_html_e( 'Creator Dashboard', 'almaden-bookster' ); ?>
					</div>
					<div>
						<h1 class="text-3xl sm:text-4xl lg:text-5xl font-semibold tracking-tight">
							<?php echo esc_html( $page_title ); ?>
						</h1>
						<p class="mt-3 max-w-2xl text-white/75 text-base sm:text-lg">
							<?php esc_html_e( 'Crea, organiza y publica tus cursos desde una sola pantalla. El grid muestra progreso, estado y acceso rápido a lecciones y quizzes.', 'almaden-bookster' ); ?>
						</p>
					</div>
					<div class="flex flex-wrap gap-3 text-sm">
						<span class="rounded-full bg-white/10 px-3 py-1"><?php echo esc_html( (string) count( $courses ) ); ?> <?php esc_html_e( 'cursos', 'almaden-bookster' ); ?></span>
						<span class="rounded-full bg-white/10 px-3 py-1"><?php esc_html_e( 'Lecciones y quizzes nativos', 'almaden-bookster' ); ?></span>
						<span class="rounded-full bg-white/10 px-3 py-1"><?php esc_html_e( 'Flujo modular', 'almaden-bookster' ); ?></span>
					</div>
				</div>

				<div class="almaden-learni-surface rounded-3xl p-5 text-slate-900">
					<h2 class="text-lg font-semibold"><?php esc_html_e( 'Crear curso', 'almaden-bookster' ); ?></h2>
					<p class="mt-1 text-sm text-slate-500">
						<?php esc_html_e( 'Se creará como borrador y aparecerá inmediatamente en tu grid.', 'almaden-bookster' ); ?>
					</p>
					<form class="mt-4 space-y-3" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="almaden_learni_create_course">
						<?php wp_nonce_field( 'almaden_learni_create_course' ); ?>
						<input type="text" name="course_title" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500" placeholder="<?php esc_attr_e( 'Título del curso', 'almaden-bookster' ); ?>">
						<textarea name="course_description" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500" placeholder="<?php esc_attr_e( 'Descripción breve del curso', 'almaden-bookster' ); ?>"></textarea>
						<button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
							<?php esc_html_e( 'Crear curso', 'almaden-bookster' ); ?>
						</button>
					</form>
				</div>
			</div>
		</section>

		<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
			<section class="space-y-4">
				<div class="flex items-end justify-between gap-4">
					<div>
						<h2 class="text-2xl font-semibold text-slate-950"><?php esc_html_e( 'Mis cursos', 'almaden-bookster' ); ?></h2>
						<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Grid personal con el estado actual de tus cursos.', 'almaden-bookster' ); ?></p>
					</div>
					<a href="<?php echo esc_url( $course_url ); ?>" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-amber-300 hover:text-slate-950">
						<?php esc_html_e( 'Refrescar', 'almaden-bookster' ); ?>
					</a>
				</div>

				<?php if ( ! empty( $courses ) ) : ?>
					<div class="grid gap-5 md:grid-cols-2 2xl:grid-cols-3">
						<?php foreach ( $courses as $course ) : ?>
							<a href="<?php echo esc_url( add_query_arg( array( 'course_id' => (int) $course['id'] ), $course_url ) ); ?>" class="almaden-learni-card group overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition">
								<div class="aspect-[16/9] bg-slate-100">
									<?php if ( ! empty( $course['thumbnail_url'] ) ) : ?>
										<img src="<?php echo esc_url( $course['thumbnail_url'] ); ?>" alt="" class="h-full w-full object-cover">
									<?php else : ?>
										<div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-900 to-slate-700 text-white">
											<span class="text-sm font-semibold uppercase tracking-[0.3em] text-white/70"><?php esc_html_e( 'Curso', 'almaden-bookster' ); ?></span>
										</div>
									<?php endif; ?>
								</div>
								<div class="space-y-4 p-5">
									<div class="flex items-start justify-between gap-4">
										<div>
											<h3 class="text-lg font-semibold text-slate-950 group-hover:text-amber-700">
												<?php echo esc_html( $course['title'] ); ?>
											</h3>
											<p class="mt-1 text-xs uppercase tracking-[0.2em] text-slate-400">
												<?php echo esc_html( $course['status_label'] ); ?>
											</p>
										</div>
										<span class="rounded-full px-3 py-1 text-xs font-semibold almaden-learni-pill">
											<?php echo esc_html( $course['lesson_count'] ); ?> <?php esc_html_e( 'lecciones', 'almaden-bookster' ); ?>
										</span>
									</div>
									<p class="text-sm leading-6 text-slate-500">
										<?php echo esc_html( $course['excerpt'] ); ?>
									</p>
									<div class="flex flex-wrap gap-2 text-xs font-medium text-slate-600">
										<span class="rounded-full bg-slate-100 px-3 py-1">
											<?php echo esc_html( $course['updated'] ); ?>
										</span>
										<span class="rounded-full bg-slate-100 px-3 py-1">
											<?php echo $course['has_quiz'] ? esc_html__( 'Quiz listo', 'almaden-bookster' ) : esc_html__( 'Sin quiz', 'almaden-bookster' ); ?>
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
						<h3 class="mt-4 text-xl font-semibold text-slate-950"><?php esc_html_e( 'Todavía no tienes cursos', 'almaden-bookster' ); ?></h3>
						<p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">
							<?php esc_html_e( 'Usa el panel de la derecha para crear tu primer curso. Después aparecerá aquí como una tarjeta editable.', 'almaden-bookster' ); ?>
						</p>
					</div>
				<?php endif; ?>
			</section>

			<aside class="space-y-4">
				<div class="almaden-learni-surface rounded-3xl p-5">
					<h2 class="text-lg font-semibold text-slate-950"><?php esc_html_e( 'Acciones rápidas', 'almaden-bookster' ); ?></h2>
					<div class="mt-4 grid gap-3">
						<a href="<?php echo esc_url( $booklist_url ); ?>" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-amber-300 hover:text-slate-950">
							<?php esc_html_e( 'Ir al taller de libros', 'almaden-bookster' ); ?>
						</a>
						<a href="<?php echo esc_url( $authors_url ); ?>" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-amber-300 hover:text-slate-950">
							<?php esc_html_e( 'Gestionar autores', 'almaden-bookster' ); ?>
						</a>
						<a href="<?php echo esc_url( $store_url ); ?>" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-amber-300 hover:text-slate-950">
							<?php esc_html_e( 'Ver catálogo', 'almaden-bookster' ); ?>
						</a>
					</div>
				</div>

				<?php if ( $selected_course && $selected_course_card ) : ?>
					<div class="almaden-learni-surface rounded-3xl p-5">
						<div class="flex items-start justify-between gap-3">
							<div>
								<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'Curso seleccionado', 'almaden-bookster' ); ?></p>
								<h3 class="mt-1 text-xl font-semibold text-slate-950"><?php echo esc_html( $selected_course->post_title ); ?></h3>
							</div>
							<span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
								<?php echo esc_html( $selected_course_card['status_label'] ); ?>
							</span>
						</div>
						<p class="mt-4 text-sm leading-6 text-slate-500">
							<?php echo esc_html( wp_trim_words( wp_strip_all_tags( (string) $selected_course->post_content ), 24 ) ); ?>
						</p>
						<div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
							<div class="flex items-center justify-between border-b border-slate-200 pb-2">
								<span><?php esc_html_e( 'Capítulos / lecciones', 'almaden-bookster' ); ?></span>
								<strong class="text-slate-950"><?php echo esc_html( (string) $selected_course_card['lesson_count'] ); ?></strong>
							</div>
							<div class="flex items-center justify-between border-b border-slate-200 py-2">
								<span><?php esc_html_e( 'Quiz', 'almaden-bookster' ); ?></span>
								<strong class="text-slate-950"><?php echo esc_html( $selected_course_card['has_quiz'] ? __( 'Asignado', 'almaden-bookster' ) : __( 'Pendiente', 'almaden-bookster' ) ); ?></strong>
							</div>
							<div class="flex items-center justify-between pt-2">
								<span><?php esc_html_e( 'Última edición', 'almaden-bookster' ); ?></span>
								<strong class="text-slate-950"><?php echo esc_html( (string) $selected_course_card['updated'] ); ?></strong>
							</div>
						</div>
						<div class="mt-4 grid gap-3">
							<a href="<?php echo esc_url( add_query_arg( array( 'course_id' => (int) $selected_course->ID, 'tab' => 'curso' ), $course_url ) ); ?>" class="rounded-2xl bg-slate-950 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-slate-800">
								<?php esc_html_e( 'Abrir editor', 'almaden-bookster' ); ?>
							</a>
							<a href="<?php echo esc_url( get_edit_post_link( (int) $selected_course->ID, 'raw' ) ); ?>" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-center text-sm font-medium text-slate-700 transition hover:border-amber-300 hover:text-slate-950">
								<?php esc_html_e( 'Editar en WP', 'almaden-bookster' ); ?>
							</a>
						</div>
					</div>
				<?php endif; ?>
			</aside>
		</div>

		<?php if ( $selected_course && $selected_course_card ) : ?>
			<?php $editor_state = CourseEditorHandler::get_editor_state( (int) $selected_course->ID, $current_user_id ); ?>
			<section class="almaden-learni-surface rounded-[2rem] p-5 sm:p-6 lg:p-8 space-y-6">
				<?php include __DIR__ . '/sections/course/nav.php'; ?>
				<?php include __DIR__ . '/sections/course/mode-curso.php'; ?>
				<?php include __DIR__ . '/sections/course/mode-lecciones.php'; ?>
				<?php include __DIR__ . '/sections/course/mode-evaluacion.php'; ?>
				<?php include __DIR__ . '/sections/course/mode-meta.php'; ?>
			</section>
		<?php endif; ?>
	</div>
</main>
<?php
almaden_bookster_render_app_shell_end();
