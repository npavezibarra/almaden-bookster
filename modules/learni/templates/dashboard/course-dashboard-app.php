<?php

use AlmadenBookster\Learni\Dashboard\CreatorDashboard;
use AlmadenBookster\Learni\Dashboard\CourseEditorHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_user_id      = get_current_user_id();
$current_user         = wp_get_current_user();
$courses              = CreatorDashboard::get_user_courses( $current_user_id );
$selected_course_id   = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
$selected_course      = CreatorDashboard::get_selected_course( $selected_course_id, $current_user_id );
$selected_course_card = CreatorDashboard::get_selected_course_card( $selected_course_id, $current_user_id );
$active_tab           = isset( $_GET['tab'] ) ? sanitize_key( (string) $_GET['tab'] ) : 'curso';
$course_catalog_url   = function_exists( 'almaden_bookster_get_course_archive_page_url' ) ? almaden_bookster_get_course_archive_page_url() : home_url( '/' );
$course_creator_url   = function_exists( 'almaden_bookster_get_course_creator_page_url' ) ? almaden_bookster_get_course_creator_page_url() : home_url( '/' );
$page_title           = function_exists( 'almaden_bookster_get_course_creator_title' ) ? almaden_bookster_get_course_creator_title() : __( 'Sala de clases', 'almaden-bookster' );
$editor_state         = $selected_course ? CourseEditorHandler::get_editor_state( (int) $selected_course_id, $current_user_id ) : array(
		'course' => $selected_course_card,
		'post' => $selected_course,
		'lessons' => array(),
		'quiz' => array( 'quiz_id' => 0, 'quiz' => null, 'questions_json' => '' ),
		'certificate' => array( 'title' => '', 'message' => '', 'logo_id' => 0, 'signature_attachment_id' => 0, 'signature' => '', 'signature_label' => '' ),
	);
$creator_tabs = array(
	'curso' => __( 'Curso', 'almaden-bookster' ),
	'lecciones' => __( 'Lecciones', 'almaden-bookster' ),
	'evaluacion' => __( 'Evaluación', 'almaden-bookster' ),
	'certificado' => __( 'Certificado', 'almaden-bookster' ),
	'meta' => __( 'Meta', 'almaden-bookster' ),
);

almaden_bookster_render_app_shell_start(
	array(
		'title'           => $page_title . ' - Almaden',
		'body_class'      => array_merge(
			array( 'min-h-screen', 'flex', 'flex-col', 'theme-light', 'almaden-learni-dashboard', 'almaden-learni-creator' ),
			$selected_course ? array( 'almaden-learni-course-mode-open' ) : array()
		),
		'active_nav_key'  => 'course_creator',
		'logo_text'       => 'almaden',
		'extra_head_html' => '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">',
	)
);
?>
<main id="almaden-course-editor" class="almaden-app-content-shell flex-1 pb-8">
	<div class="grid gap-6 xl:grid-cols-[300px_minmax(0,1fr)]">
		<aside id="almaden-course-creator-sidebar" class="almaden-learni-creator-sidebar<?php echo $selected_course ? ' hidden' : ''; ?>">
			<div class="flex items-center gap-4">
				<?php echo get_avatar( (int) $current_user_id, 80, '', (string) ( $current_user->display_name ?? '' ), array( 'class' => 'h-20 w-20 rounded-full object-cover' ) ); ?>
				<div>
					<p class="text-sm font-semibold text-slate-950"><?php echo esc_html( (string) ( $current_user->display_name ?? $current_user->user_login ?? '' ) ); ?></p>
					<p class="text-xs uppercase tracking-[0.22em] text-slate-400"><?php esc_html_e( 'Operaciones 2', 'almaden-bookster' ); ?></p>
				</div>
			</div>

			<nav class="mt-8 space-y-1">
				<a id="almaden-course-creator-link-listing" href="#almaden-course-listing" class="almaden-learni-creator-nav-link is-active">
					<span class="dashicons dashicons-plus-alt2"></span>
					<span><?php esc_html_e( 'Mis cursos', 'almaden-bookster' ); ?></span>
				</a>
			</nav>

			<div class="mt-8 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
				<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'Atajo', 'almaden-bookster' ); ?></p>
				<a id="almaden-course-creator-link-catalog" href="<?php echo esc_url( $course_catalog_url ); ?>" class="mt-3 inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-amber-300 hover:text-slate-950">
					<?php esc_html_e( 'Ir al catálogo', 'almaden-bookster' ); ?>
				</a>
			</div>
		</aside>

		<div class="space-y-6">
			<?php include __DIR__ . '/sections/course/create-course-panel.php'; ?>

			<?php if ( ! $selected_course ) : ?>
				<section id="almaden-course-listing" class="space-y-4">
					<div class="flex items-end justify-between gap-4">
						<div>
							<h2 class="text-2xl font-semibold text-slate-950"><?php esc_html_e( 'Mis cursos publicados', 'almaden-bookster' ); ?></h2>
							<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Selecciona una tarjeta para abrir el editor completo.', 'almaden-bookster' ); ?></p>
						</div>
						<div class="flex flex-wrap items-center gap-3">
							<a href="<?php echo esc_url( $course_catalog_url ); ?>" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-amber-300 hover:text-slate-950">
								<?php esc_html_e( 'Ver catálogo', 'almaden-bookster' ); ?>
							</a>
							<button
								id="almaden-course-creator-open-create"
								type="button"
								class="inline-flex items-center justify-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
								data-almaden-toggle-create-course
							>
								<?php esc_html_e( 'Crear curso', 'almaden-bookster' ); ?>
							</button>
						</div>
					</div>

					<?php
					$course_url = $course_creator_url;
					include __DIR__ . '/sections/course/list-my-courses.php';
					?>
				</section>
			<?php endif; ?>

			<?php if ( $selected_course && $selected_course_card ) : ?>
				<section class="space-y-6">
					<?php
					$course_url = add_query_arg( array(), $course_creator_url );
					$course_url = remove_query_arg( array( 'course_id', 'tab' ), $course_url );
					$course_url = trailingslashit( $course_url ) . '#almaden-course-listing';
					include __DIR__ . '/sections/course/nav.php';
					?>

					<?php if ( 'lecciones' !== $active_tab && 'evaluacion' !== $active_tab && 'certificado' !== $active_tab ) : ?>
						<div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
							<label class="mt-4 block">
								<span class="sr-only"><?php esc_html_e( 'Título del curso', 'almaden-bookster' ); ?></span>
								<input
									type="text"
									name="course_title"
									form="almaden-course-sidebar-form"
									value="<?php echo esc_attr( $selected_course->post_title ); ?>"
									placeholder="<?php esc_attr_e( 'Título de curso', 'almaden-bookster' ); ?>"
									autocomplete="off"
									aria-label="<?php esc_attr_e( 'Título del curso', 'almaden-bookster' ); ?>"
									class="w-full border-0 bg-transparent text-center text-4xl font-semibold tracking-tight text-slate-950 outline-none placeholder:text-slate-300 focus:ring-0 sm:text-5xl"
								>
							</label>
							<div class="mt-6 h-px bg-amber-500/70"></div>
						</div>
					<?php endif; ?>

					<div class="mt-6 space-y-8">
						<?php include __DIR__ . '/sections/course/mode-curso.php'; ?>
						<?php include __DIR__ . '/sections/course/mode-lecciones.php'; ?>
						<?php include __DIR__ . '/sections/course/mode-evaluacion.php'; ?>
						<?php include __DIR__ . '/sections/course/mode-certificado.php'; ?>
						<?php include __DIR__ . '/sections/course/mode-meta.php'; ?>
					</div>
				</section>
			<?php endif; ?>
		</div>
	</div>
</main>
<?php
almaden_bookster_render_app_shell_end();
