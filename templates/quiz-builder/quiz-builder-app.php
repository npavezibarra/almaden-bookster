<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$book_id = isset( $_GET['book_id'] ) ? intval( $_GET['book_id'] ) : 0;
$book    = $book_id > 0 ? get_post( $book_id ) : null;

if ( ! $book || $book->post_type !== 'almaden-books' ) {
	wp_die( 'Libro no encontrado.' );
}

if ( ! is_user_logged_in() || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_post', $book_id ) ) ) {
	wp_die( 'No tienes permisos para crear o editar quizzes para este libro.' );
}

if ( ! function_exists( 'almaden_bookster_learni_integration_active' ) || ! almaden_bookster_learni_integration_active() ) {
	wp_die( 'La integración con Learni está desactivada o no está disponible.' );
}

$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
if ( empty( $source_book_id ) ) {
	$source_book_id = $book_id;
}

$requested_chapter_id = isset( $_GET['chapter_id'] ) ? absint( $_GET['chapter_id'] ) : 0;

$quiz_settings = array(
	'question_count'      => 5,
	'alternatives_count'   => 4,
	'difficulty'          => 'medium',
	'target_language'     => 'es',
	'style'               => 'clear',
	'include_explanations'=> 0,
);

$chapter_posts = get_posts(
	array(
		'post_type'      => 'book_chapter',
	'post_parent'    => $source_book_id,
	'posts_per_page' => -1,
	'orderby'        => 'menu_order',
	'order'          => 'ASC',
	)
);

$chapter_items = array();
foreach ( $chapter_posts as $index => $chapter ) {
	if ( '1' === (string) get_post_meta( $chapter->ID, '_is_toc', true ) || '1' === (string) get_post_meta( $chapter->ID, '_is_credits', true ) ) {
		continue;
	}

	$chapter_number = (int) $chapter->menu_order > 0 ? (int) $chapter->menu_order : ( $index + 1 );
	$chapter_label  = (string) $chapter->post_title;
	$chapter_key    = sanitize_title( 'chapter ' . $chapter_number );
	$chapter_counter_key = $chapter_key !== '' ? $chapter_key : 'chapter-' . (string) $chapter_number;
	$chapter_quiz_id = 0;
	if ( function_exists( 'almaden_bookster_learni_get_quiz_id_for_chapter' ) ) {
		$chapter_quiz_id = (int) almaden_bookster_learni_get_quiz_id_for_chapter( $chapter->ID );
	}
	$chapter_question_count = 0;
	if ( $chapter_quiz_id > 0 && class_exists( '\\LearniStandalone\\QuizEditor\\QuizEditor' ) ) {
		$chapter_quiz_data = \LearniStandalone\QuizEditor\QuizEditor::get_quiz_data( $chapter_quiz_id );
		if ( is_array( $chapter_quiz_data ) && ! empty( $chapter_quiz_data['questions'] ) && is_array( $chapter_quiz_data['questions'] ) ) {
			$chapter_question_count = count( $chapter_quiz_data['questions'] );
		}
	}

	$chapter_items[] = array(
		'id'            => (int) $chapter->ID,
		'key'           => $chapter_counter_key,
		'title'         => $chapter_label,
		'order'         => $chapter_number,
		'quiz_id'       => $chapter_quiz_id,
		'content'       => wp_strip_all_tags( (string) $chapter->post_content ),
		'question_count'=> $chapter_question_count,
	);
}

if ( empty( $chapter_items ) ) {
	$chapter_items[] = array(
		'id'            => 0,
		'key'           => 'chapter-1',
		'title'         => 'Chapter 1',
		'order'         => 1,
		'content'       => 'Este libro todavía no tiene capítulos para mostrar.',
		'question_count'=> 0,
	);
}

$book_title       = get_the_title( $book_id );
$book_editor_url  = home_url( '/almaden-book-editor/?book_id=' . $book_id );
$booklist_url     = home_url( '/almaden-booklist/' );
$active_chapter   = $chapter_items[0];
$active_chapter_index = 0;
if ( $requested_chapter_id > 0 ) {
	foreach ( $chapter_items as $item_index => $chapter_item ) {
		if ( isset( $chapter_item['id'] ) && (int) $chapter_item['id'] === $requested_chapter_id ) {
			$active_chapter = $chapter_item;
			$active_chapter_index = (int) $item_index;
			break;
		}
	}
}
$active_quiz_id = isset( $active_chapter['quiz_id'] ) ? (int) $active_chapter['quiz_id'] : 0;
$active_quiz_data = null;
if ( $active_quiz_id > 0 && class_exists( '\\LearniStandalone\\QuizEditor\\QuizEditor' ) ) {
	$active_quiz_data = \LearniStandalone\QuizEditor\QuizEditor::get_quiz_data( $active_quiz_id );
}
$saved_notice     = isset( $_GET['saved'] ) && '1' === (string) $_GET['saved'];
?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Book Quiz - <?php echo esc_html( $book_title ); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<style>
		:root {
			--bg: #f4f7fb;
			--panel: rgba(255, 255, 255, 0.92);
			--panel-strong: #ffffff;
			--text: #101828;
			--muted: #667085;
			--line: #e5e7eb;
			--soft: #f8fafc;
			--soft-2: #eef2f7;
			--accent: #111827;
			--success: #16a34a;
		}
		* { box-sizing: border-box; }
		html, body { min-height: 100%; }
		body {
			margin: 0;
			font-family: "Urbanist", sans-serif;
			background: radial-gradient(circle at top, #ffffff 0%, #f6f8fc 56%, #edf2f8 100%);
			color: var(--text);
		}
		button, input, textarea, select {
			font: inherit;
		}
		button {
			cursor: pointer;
		}
		.almaden-quiz-page {
			min-height: 100vh;
			display: flex;
			flex-direction: column;
		}
		.almaden-quiz-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 18px;
			padding: 22px 26px;
			border-bottom: 1px solid rgba(229, 231, 235, 0.9);
			background: rgba(255, 255, 255, 0.75);
			backdrop-filter: blur(10px);
		}
		.almaden-quiz-header h1 {
			margin: 0;
			font-size: 22px;
			line-height: 1.1;
			letter-spacing: -0.04em;
		}
		.almaden-quiz-kicker {
			margin: 0 0 4px;
			font-size: 12px;
			font-weight: 700;
			letter-spacing: 0.22em;
			text-transform: uppercase;
			color: #94a3b8;
		}
		.almaden-quiz-subtitle {
			margin: 6px 0 0;
			font-size: 14px;
			color: var(--muted);
		}
		.almaden-quiz-header-actions {
			display: flex;
			align-items: center;
			gap: 10px;
			flex-wrap: wrap;
			justify-content: flex-end;
		}
		.almaden-chip {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 8px 12px;
			border-radius: 999px;
			border: 1px solid var(--line);
			background: rgba(255, 255, 255, 0.92);
			color: #475467;
			font-size: 12px;
			font-weight: 600;
			white-space: nowrap;
		}
		.almaden-chip--success {
			border-color: rgba(34, 197, 94, 0.20);
			background: rgba(34, 197, 94, 0.08);
			color: var(--success);
		}
		.almaden-chip--ghost {
			background: #f8fafc;
		}
		.almaden-quiz-workspace {
			flex: 1;
			display: grid;
			grid-template-columns: minmax(300px, 0.34fr) minmax(0, 0.66fr);
			min-height: 0;
		}
		.almaden-panel {
			min-width: 0;
			min-height: 0;
		}
		.almaden-sidebar {
			background: rgba(255, 255, 255, 0.72);
			border-right: 1px solid var(--line);
			padding: 18px;
			overflow: auto;
		}
		.almaden-sidebar-card,
		.almaden-workspace-card {
			background: var(--panel);
			border: 1px solid rgba(229, 231, 235, 0.95);
			border-radius: 24px;
			box-shadow: 0 18px 50px rgba(15, 23, 42, 0.06);
			overflow: hidden;
		}
		.almaden-sidebar-card-head,
		.almaden-workspace-head {
			padding: 18px 18px 16px;
			border-bottom: 1px solid rgba(229, 231, 235, 0.85);
		}
		.almaden-sidebar-card-head {
			display: flex;
			align-items: flex-start;
			justify-content: space-between;
			gap: 12px;
			flex-wrap: wrap;
		}
		.almaden-sidebar-title,
		.almaden-workspace-title {
			margin: 0;
			font-size: 20px;
			line-height: 1.15;
			letter-spacing: -0.04em;
		}
		.almaden-sidebar-label,
		.almaden-workspace-label {
			margin: 0 0 4px;
			font-size: 12px;
			letter-spacing: 0.22em;
			text-transform: uppercase;
			color: #94a3b8;
			font-weight: 700;
		}
		.almaden-sidebar-meta {
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
			align-items: center;
			margin-top: 12px;
		}
		.almaden-sidebar-list {
			padding: 12px;
			display: grid;
			gap: 10px;
		}
		.almaden-chapter-item {
			width: 100%;
			border: 1px solid var(--line);
			border-radius: 18px;
			background: #fff;
			padding: 14px 14px 14px 16px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			text-align: left;
			cursor: pointer;
			transition: all 0.18s ease;
		}
		.almaden-chapter-item:hover {
			background: #fafbfc;
			border-color: #dbe2ea;
		}
		.almaden-chapter-item:focus-visible {
			outline: 3px solid rgba(59, 130, 246, 0.22);
			outline-offset: 2px;
		}
		.almaden-chapter-item.is-active {
			background: #111827;
			border-color: #111827;
			box-shadow: 0 12px 24px rgba(17, 24, 39, 0.16);
			color: #fff;
		}
		.almaden-chapter-item.is-active .almaden-chapter-subtitle,
		.almaden-chapter-item.is-active .almaden-question-count {
			color: rgba(255, 255, 255, 0.74);
		}
		.almaden-chapter-copy {
			width: 36px;
			height: 36px;
			border-radius: 999px;
			border: 1px solid var(--line);
			background: #fff;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			flex: 0 0 auto;
			color: #344054;
			transition: all 0.18s ease;
		}
		.almaden-chapter-copy:hover {
			border-color: #111827;
			color: #111827;
		}
		.almaden-chapter-item.is-active .almaden-chapter-copy {
			border-color: rgba(255, 255, 255, 0.22);
			background: rgba(255, 255, 255, 0.12);
			color: #fff;
		}
		.almaden-chapter-content {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			min-width: 0;
			flex: 1;
		}
		.almaden-chapter-title {
			margin: 0;
			font-size: 16px;
			line-height: 1.2;
			font-weight: 800;
			letter-spacing: -0.03em;
			word-break: break-word;
		}
		.almaden-chapter-subtitle {
			margin: 5px 0 0;
			font-size: 11px;
			letter-spacing: 0.18em;
			text-transform: uppercase;
			color: #98a2b3;
			font-weight: 700;
		}
		.almaden-chapter-meta {
			display: flex;
			align-items: center;
			gap: 8px;
			flex: 0 0 auto;
		}
		.almaden-question-count {
			font-size: 16px;
			font-weight: 800;
			color: var(--success);
			white-space: nowrap;
		}
		.almaden-question-count.is-empty {
			width: 28px;
			height: 28px;
			border-radius: 999px;
			border: 4px solid #cfd5df;
			color: transparent;
			background: transparent;
		}
		.almaden-workspace {
			padding: 18px;
			overflow: auto;
			min-width: 0;
		}
		.almaden-workspace-card {
			min-height: 100%;
			display: flex;
			flex-direction: column;
		}
		.almaden-workspace-head {
			display: flex;
			align-items: flex-start;
			justify-content: space-between;
			gap: 14px;
			flex-wrap: wrap;
		}
		.almaden-workspace-actions {
			display: flex;
			gap: 10px;
			flex-wrap: wrap;
			align-items: center;
			justify-content: flex-end;
		}
		.almaden-tabbar {
			padding: 12px 18px 0;
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
			border-bottom: 1px solid rgba(229, 231, 235, 0.85);
		}
		.almaden-tab {
			border: 1px solid var(--line);
			border-bottom: 0;
			border-radius: 18px 18px 0 0;
			background: #f8fafc;
			color: #475467;
			padding: 12px 14px;
			font-size: 13px;
			font-weight: 700;
			letter-spacing: -0.01em;
			margin-bottom: -1px;
		}
		.almaden-tab.is-active {
			background: #fff;
			color: #111827;
			border-color: #dbe2ea;
		}
		.almaden-tabpanels {
			padding: 18px;
			display: grid;
			gap: 18px;
			flex: 1;
		}
		.almaden-tabpanel {
			display: none;
		}
		.almaden-tabpanel.is-active {
			display: block;
		}
		.almaden-card {
			border: 1px solid rgba(229, 231, 235, 0.95);
			border-radius: 22px;
			background: #fff;
			box-shadow: 0 14px 32px rgba(15, 23, 42, 0.05);
			overflow: hidden;
		}
		.almaden-card-head {
			padding: 18px 18px 16px;
			border-bottom: 1px solid rgba(229, 231, 235, 0.85);
		}
		.almaden-card-head h3 {
			margin: 0;
			font-size: 18px;
			line-height: 1.15;
			letter-spacing: -0.03em;
		}
		.almaden-card-body {
			padding: 18px;
		}
		.almaden-grid {
			display: grid;
			gap: 16px;
		}
		.almaden-grid--settings {
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}
		.almaden-field {
			display: grid;
			gap: 8px;
		}
		.almaden-field label {
			font-size: 13px;
			font-weight: 700;
			color: #344054;
		}
		.almaden-field input,
		.almaden-field select,
		.almaden-field textarea {
			width: 100%;
			border: 1px solid #dbe2ea;
			border-radius: 14px;
			padding: 13px 14px;
			background: #fff;
			color: #101828;
			outline: none;
		}
		.almaden-field textarea {
			min-height: 190px;
			resize: vertical;
			line-height: 1.5;
		}
		.almaden-field input:focus,
		.almaden-field select:focus,
		.almaden-field textarea:focus {
			border-color: #94a3b8;
			box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.15);
		}
		.almaden-settings-note {
			font-size: 13px;
			color: var(--muted);
			line-height: 1.6;
			margin: 0;
		}
		.almaden-prompt-toolbar,
		.almaden-preview-toolbar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			flex-wrap: wrap;
			margin-top: 14px;
		}
		.almaden-btn {
			border: 0;
			border-radius: 999px;
			padding: 12px 18px;
			font-size: 14px;
			font-weight: 800;
			letter-spacing: -0.01em;
			transition: all 0.18s ease;
		}
		.almaden-btn--dark {
			background: #111827;
			color: #fff;
		}
		.almaden-btn--dark:hover {
			background: #000;
		}
		.almaden-btn--soft {
			background: #eef2ff;
			color: #1f2937;
		}
		.almaden-btn--soft:hover {
			background: #e0e7ff;
		}
		.almaden-btn--ghost {
			background: #fff;
			border: 1px solid var(--line);
			color: #344054;
		}
		.almaden-btn--ghost:hover {
			border-color: #94a3b8;
		}
		.almaden-btn--danger {
			background: #dc2626;
			color: #fff;
		}
		.almaden-btn--danger:hover {
			background: #b91c1c;
		}
		.almaden-helper {
			margin: 0;
			font-size: 13px;
			line-height: 1.6;
			color: var(--muted);
		}
		.almaden-raw {
			white-space: pre-wrap;
			word-break: break-word;
			font-family: "Courier New", monospace;
			font-size: 13px;
			line-height: 1.8;
			color: #334155;
			background: #fbfdff;
			border: 1px solid #e5e7eb;
			border-radius: 18px;
			padding: 18px;
			min-height: 320px;
		}
		.almaden-preview-empty {
			border: 1px dashed #dbe2ea;
			border-radius: 20px;
			padding: 24px;
			background: #fbfdff;
			color: var(--muted);
		}
		.almaden-preview-list {
			display: grid;
			gap: 12px;
		}
		.almaden-preview-shell {
			display: grid;
			gap: 16px;
		}
		.almaden-preview-nav {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			flex-wrap: wrap;
		}
		.almaden-preview-nav-group {
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: wrap;
		}
		.almaden-slide-indicator {
			font-size: 12px;
			font-weight: 800;
			color: #475467;
			letter-spacing: 0.02em;
		}
		.almaden-slide-dots {
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
		}
		.almaden-slide-dot {
			width: 10px;
			height: 10px;
			border-radius: 999px;
			border: 0;
			background: #cfd5df;
			padding: 0;
		}
		.almaden-slide-dot.is-active {
			background: #111827;
		}
		.almaden-slide-dot.is-filled {
			background: #6b7280;
		}
		.almaden-preview-card {
			border: 1px solid #e5e7eb;
			border-radius: 22px;
			background: #fff;
			padding: 18px;
			box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
		}
		.almaden-slide-head {
			display: flex;
			align-items: flex-start;
			justify-content: space-between;
			gap: 12px;
			margin-bottom: 16px;
			flex-wrap: wrap;
		}
		.almaden-slide-head h4 {
			margin: 0;
			font-size: 16px;
			letter-spacing: -0.03em;
		}
		.almaden-slide-field {
			display: grid;
			gap: 8px;
			margin-top: 14px;
		}
		.almaden-slide-field label {
			font-size: 13px;
			font-weight: 700;
			color: #344054;
		}
		.almaden-slide-field input,
		.almaden-slide-field textarea {
			width: 100%;
			border: 1px solid #dbe2ea;
			border-radius: 14px;
			padding: 13px 14px;
			background: #fff;
			color: #101828;
			outline: none;
		}
		.almaden-slide-field textarea {
			min-height: 150px;
			resize: vertical;
		}
		.almaden-answer-list {
			display: grid;
			gap: 10px;
		}
		.almaden-answer-row {
			display: grid;
			grid-template-columns: auto minmax(0, 1fr) auto;
			gap: 10px;
			align-items: center;
			padding: 12px 12px 12px 14px;
			border: 1px solid #e5e7eb;
			border-radius: 16px;
			background: #fbfdff;
		}
		.almaden-answer-row input[type="text"] {
			border: 1px solid #dbe2ea;
			border-radius: 12px;
			padding: 11px 12px;
			background: #fff;
		}
		.almaden-answer-row input[type="checkbox"] {
			width: 18px;
			height: 18px;
			accent-color: #111827;
		}
		.almaden-slide-actions {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
		}
		.almaden-preview-empty {
			border: 1px solid #e5e7eb;
			border-radius: 18px;
			background: #fff;
			padding: 16px;
		}
		.almaden-slide-empty-state {
			border: 1px dashed #dbe2ea;
			border-radius: 20px;
			padding: 24px;
			background: #fbfdff;
			color: var(--muted);
			line-height: 1.6;
		}
		@media (max-width: 1100px) {
			.almaden-quiz-workspace {
				grid-template-columns: 1fr;
			}
			.almaden-sidebar {
				border-right: 0;
				border-bottom: 1px solid var(--line);
			}
		}
		@media (max-width: 720px) {
			.almaden-quiz-header {
				flex-direction: column;
				align-items: stretch;
			}
			.almaden-grid--settings {
				grid-template-columns: 1fr;
			}
			.almaden-tabbar {
				padding-inline: 14px;
			}
			.almaden-tabpanels {
				padding: 14px;
			}
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'almaden-quiz-page' ); ?>>
<?php wp_body_open(); ?>
<div class="almaden-quiz-page">
	<header class="almaden-quiz-header">
		<div>
			<p class="almaden-quiz-kicker">Create Quiz</p>
			<h1><?php echo esc_html( $book_title ); ?></h1>
			<p class="almaden-quiz-subtitle">Diseño base del nuevo editor de quizzes para este libro.</p>
		</div>
		<div class="almaden-quiz-header-actions">
			<a href="<?php echo esc_url( $booklist_url ); ?>" class="almaden-chip almaden-chip--ghost">Volver al taller</a>
			<a href="<?php echo esc_url( $book_editor_url ); ?>" class="almaden-chip">Editar contenido</a>
			<?php if ( $saved_notice ) : ?>
				<span class="almaden-chip almaden-chip--success">Saved</span>
			<?php endif; ?>
			<span class="almaden-chip almaden-chip--success">Learni ready</span>
			<span class="almaden-chip">Book ID <?php echo esc_html( $book_id ); ?></span>
			<span class="almaden-chip" id="almaden-active-quiz-chip">Quiz ID <?php echo esc_html( $active_quiz_id > 0 ? $active_quiz_id : 0 ); ?></span>
		</div>
	</header>

	<main class="almaden-quiz-workspace">
		<aside class="almaden-panel almaden-sidebar">
			<div class="almaden-sidebar-card">
				<div class="almaden-sidebar-card-head">
					<div>
						<p class="almaden-sidebar-label">Chapter list</p>
						<h2 class="almaden-sidebar-title">Chapters</h2>
					</div>
					<div class="almaden-sidebar-meta">
						<span class="almaden-chip"><?php echo esc_html( count( $chapter_items ) ); ?> chapters</span>
					</div>
				</div>
				<div class="almaden-sidebar-list" id="almaden-chapter-list">
					<?php foreach ( $chapter_items as $index => $chapter_item ) : ?>
						<div
							role="button"
							tabindex="0"
							class="almaden-chapter-item<?php echo (int) $active_chapter_index === (int) $index ? ' is-active' : ''; ?>"
							data-chapter-index="<?php echo esc_attr( (string) $index ); ?>"
							data-chapter-key="<?php echo esc_attr( $chapter_item['key'] ); ?>"
							data-chapter-title="<?php echo esc_attr( $chapter_item['title'] ); ?>"
							data-chapter-order="<?php echo esc_attr( (string) $chapter_item['order'] ); ?>"
							data-quiz-id="<?php echo esc_attr( (string) ( $chapter_item['quiz_id'] ?? 0 ) ); ?>"
							data-chapter-content="<?php echo esc_attr( $chapter_item['content'] ); ?>"
							data-question-count="<?php echo esc_attr( (string) $chapter_item['question_count'] ); ?>"
						>
							<div class="almaden-chapter-content">
								<div>
									<h3 class="almaden-chapter-title"><?php echo esc_html( $chapter_item['title'] !== '' ? $chapter_item['title'] : sprintf( 'Chapter %d', $chapter_item['order'] ) ); ?></h3>
									<p class="almaden-chapter-subtitle">Chapter <?php echo esc_html( $chapter_item['order'] ); ?> · <?php echo esc_html( $chapter_item['key'] ); ?></p>
								</div>
								<div class="almaden-chapter-meta">
									<span class="almaden-question-count<?php echo $chapter_item['question_count'] > 0 ? '' : ' is-empty'; ?>" data-chapter-count-label="<?php echo esc_attr( (string) $chapter_item['question_count'] ); ?>">
										<?php echo $chapter_item['question_count'] > 0 ? esc_html( (string) $chapter_item['question_count'] ) . ' ✓' : ''; ?>
									</span>
									<button type="button" class="almaden-chapter-copy" data-copy-chapter-index="<?php echo esc_attr( (string) $index ); ?>" title="Copy prompt" aria-label="Copy prompt">
										<i class="fa-solid fa-copy"></i>
									</button>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</aside>

		<section class="almaden-panel almaden-workspace">
			<div class="almaden-workspace-card">
				<div class="almaden-workspace-head">
					<div>
						<p class="almaden-workspace-label">Quiz workspace</p>
						<h2 class="almaden-workspace-title" id="almaden-active-chapter-title"><?php echo esc_html( $active_chapter['title'] ); ?></h2>
						<p class="almaden-quiz-subtitle" id="almaden-active-chapter-caption">Chapter <?php echo esc_html( $active_chapter['order'] ); ?> · <?php echo esc_html( $active_chapter['key'] ); ?></p>
					</div>
					<div class="almaden-workspace-actions">
						<button type="button" class="almaden-btn almaden-btn--ghost" id="almaden-copy-active-prompt">Copy Prompt</button>
						<button type="button" class="almaden-btn almaden-btn--dark" id="almaden-save-quiz">Save Quiz</button>
					</div>
				</div>

				<div class="almaden-tabbar" role="tablist" aria-label="Quiz editor sections">
					<button type="button" class="almaden-tab is-active" role="tab" aria-selected="true" data-tab-target="prompt-settings">Prompt Settings</button>
					<button type="button" class="almaden-tab" role="tab" aria-selected="false" data-tab-target="enter-prompt">Enter Prompt</button>
					<button type="button" class="almaden-tab" role="tab" aria-selected="false" data-tab-target="quiz-preview">Quiz Preview</button>
					<button type="button" class="almaden-tab" role="tab" aria-selected="false" data-tab-target="chapter-content">Chapter Content</button>
				</div>

				<div class="almaden-tabpanels">
					<section class="almaden-tabpanel is-active" id="almaden-tab-prompt-settings" data-tab-panel="prompt-settings" role="tabpanel">
						<div class="almaden-card">
							<div class="almaden-card-head">
								<h3>Prompt Settings</h3>
							</div>
							<div class="almaden-card-body">
								<div class="almaden-grid almaden-grid--settings">
									<div class="almaden-field">
										<label for="almaden-setting-question-count">Cantidad de preguntas</label>
										<input id="almaden-setting-question-count" type="number" min="1" max="25" value="<?php echo esc_attr( (string) $quiz_settings['question_count'] ); ?>">
									</div>
									<div class="almaden-field">
										<label for="almaden-setting-alternatives-count">Alternativas por pregunta</label>
										<input id="almaden-setting-alternatives-count" type="number" min="2" max="8" value="<?php echo esc_attr( (string) $quiz_settings['alternatives_count'] ); ?>">
									</div>
									<div class="almaden-field">
										<label for="almaden-setting-difficulty">Nivel de dificultad</label>
										<select id="almaden-setting-difficulty">
											<option value="easy">Fácil</option>
											<option value="medium" selected>Media</option>
											<option value="hard">Difícil</option>
										</select>
									</div>
									<div class="almaden-field">
										<label for="almaden-setting-style">Estilo</label>
										<select id="almaden-setting-style">
											<option value="clear" selected>Clara y directa</option>
											<option value="analytical">Analítica</option>
											<option value="narrative">Narrativa</option>
										</select>
									</div>
								</div>
								<p class="almaden-settings-note" style="margin-top:16px;">
									Estos controles ya quedan listos para la siguiente fase. En esta base todavía no se guardan en backend, pero sí se usan para construir el prompt al copiarlo.
								</p>
							</div>
						</div>
					</section>

					<section class="almaden-tabpanel" id="almaden-tab-enter-prompt" data-tab-panel="enter-prompt" role="tabpanel">
						<div class="almaden-card">
							<div class="almaden-card-head">
								<h3>Enter Prompt</h3>
							</div>
							<div class="almaden-card-body">
								<div class="almaden-field">
									<label for="almaden-prompt-input">Pega aquí el JSON o el resultado bruto del LLM</label>
									<textarea id="almaden-prompt-input" placeholder="Pega aquí el prompt con JSON devuelto por el LLM"></textarea>
								</div>
								<div class="almaden-prompt-toolbar">
									<p class="almaden-helper">El sistema intentará extraer y validar el JSON aunque venga acompañado por texto adicional.</p>
									<button type="button" class="almaden-btn almaden-btn--dark" id="almaden-load-prompt">Load</button>
								</div>
							</div>
						</div>
					</section>

					<section class="almaden-tabpanel" id="almaden-tab-quiz-preview" data-tab-panel="quiz-preview" role="tabpanel">
						<div class="almaden-card">
							<div class="almaden-card-head">
								<h3>Quiz Preview</h3>
							</div>
							<div class="almaden-card-body">
								<div class="almaden-slide-empty-state" id="almaden-preview-empty">
									Aquí se mostrará la vista previa del quiz cargado. En esta primera fase dejamos el contenedor listo para el preview tipo slides.
								</div>
								<div class="almaden-preview-list" id="almaden-preview-list" hidden></div>
								<div class="almaden-preview-toolbar">
									<p class="almaden-helper" id="almaden-preview-summary">No hay un quiz cargado todavía.</p>
									<button type="button" class="almaden-btn almaden-btn--soft" id="almaden-preview-focus">Ir al preview</button>
								</div>
							</div>
						</div>
					</section>

					<section class="almaden-tabpanel" id="almaden-tab-chapter-content" data-tab-panel="chapter-content" role="tabpanel">
						<div class="almaden-card">
							<div class="almaden-card-head">
								<h3>Chapter Content</h3>
							</div>
							<div class="almaden-card-body">
								<div class="almaden-raw" id="almaden-chapter-raw"><?php echo esc_html( $active_chapter['content'] ); ?></div>
							</div>
						</div>
					</section>
				</div>
			</div>
		</section>
	</main>

	<form id="almaden-book-quiz-save-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:none;">
	<input type="hidden" name="action" value="almaden_save_book_quiz">
	<input type="hidden" name="book_id" value="<?php echo esc_attr( (string) $book_id ); ?>">
	<input type="hidden" name="quiz_id" id="almaden-quiz-id" value="<?php echo esc_attr( (string) $active_quiz_id ); ?>">
	<input type="hidden" name="quiz_payload_json" id="almaden-quiz-payload-json" value="">
	<?php wp_nonce_field( 'almaden_save_book_quiz_' . $book_id ); ?>
</form>
</div>

<script>
(function () {
	const chapters = <?php echo wp_json_encode( $chapter_items ); ?>;
	const bookTitle = <?php echo wp_json_encode( (string) $book_title ); ?>;
	const initialQuizData = <?php echo wp_json_encode( is_array( $active_quiz_data ) ? $active_quiz_data : null ); ?>;
	const initialActiveChapterIndex = <?php echo (int) $active_chapter_index; ?>;
	const chapterList = document.getElementById('almaden-chapter-list');
	const activeTitle = document.getElementById('almaden-active-chapter-title');
	const activeCaption = document.getElementById('almaden-active-chapter-caption');
	const chapterRaw = document.getElementById('almaden-chapter-raw');
	const promptInput = document.getElementById('almaden-prompt-input');
	const loadPromptBtn = document.getElementById('almaden-load-prompt');
	const previewEmpty = document.getElementById('almaden-preview-empty');
	const previewList = document.getElementById('almaden-preview-list');
	const previewSummary = document.getElementById('almaden-preview-summary');
	const previewFocus = document.getElementById('almaden-preview-focus');
	const copyActivePromptBtn = document.getElementById('almaden-copy-active-prompt');
	const saveQuizBtn = document.getElementById('almaden-save-quiz');
	const saveQuizForm = document.getElementById('almaden-book-quiz-save-form');
	const saveQuizPayloadField = document.getElementById('almaden-quiz-payload-json');
	const quizIdField = document.getElementById('almaden-quiz-id');
	const activeQuizChip = document.getElementById('almaden-active-quiz-chip');
	const questionCountField = document.getElementById('almaden-setting-question-count');
	const alternativesCountField = document.getElementById('almaden-setting-alternatives-count');
	const difficultyField = document.getElementById('almaden-setting-difficulty');
	const styleField = document.getElementById('almaden-setting-style');
	const tabButtons = Array.from(document.querySelectorAll('[data-tab-target]'));
	const tabPanels = Array.from(document.querySelectorAll('[data-tab-panel]'));

	let activeChapterIndex = initialActiveChapterIndex;
	let loadedQuiz = null;
	let activePreviewQuestionIndex = 0;
	let activeTab = 'prompt-settings';

	function getChapter(index) {
		return chapters[index] || null;
	}

	function getPromptSettings() {
		const questionCount = questionCountField ? parseInt(questionCountField.value, 10) : 5;
		const alternativesCount = alternativesCountField ? parseInt(alternativesCountField.value, 10) : 4;
		return {
			questionCount: Number.isFinite(questionCount) && questionCount > 0 ? questionCount : 5,
			alternativesCount: Number.isFinite(alternativesCount) && alternativesCount > 0 ? alternativesCount : 4,
			difficulty: difficultyField ? String(difficultyField.value || 'medium') : 'medium',
			style: styleField ? String(styleField.value || 'clear') : 'clear'
		};
	}

	function getDifficultyLabel(value) {
		const map = {
			easy: 'Fácil',
			medium: 'Media',
			hard: 'Difícil'
		};

		return map[String(value || '').toLowerCase()] || 'Media';
	}

	function getStyleLabel(value) {
		const map = {
			clear: 'Clara y directa',
			analytical: 'Analítica',
			narrative: 'Narrativa'
		};

		return map[String(value || '').toLowerCase()] || 'Clara y directa';
	}

	function updateTabState() {
		tabButtons.forEach((button) => {
			const isActive = button.getAttribute('data-tab-target') === activeTab;
			button.classList.toggle('is-active', isActive);
			button.setAttribute('aria-selected', isActive ? 'true' : 'false');
		});

		tabPanels.forEach((panel) => {
			panel.classList.toggle('is-active', panel.getAttribute('data-tab-panel') === activeTab);
		});
	}

	function updateChapterListState() {
		if (!chapterList) {
			return;
		}

		chapterList.querySelectorAll('[data-chapter-index]').forEach((node) => {
			const index = Number(node.getAttribute('data-chapter-index'));
			node.classList.toggle('is-active', index === activeChapterIndex);
		});
	}

	function updateChapterView() {
		const chapter = getChapter(activeChapterIndex);
		if (!chapter) {
			return;
		}

		if (activeTitle) {
			activeTitle.textContent = chapter.title || ('Chapter ' + (chapter.order || (activeChapterIndex + 1)));
		}
		if (activeCaption) {
			activeCaption.textContent = 'Chapter ' + (chapter.order || (activeChapterIndex + 1)) + ' · ' + (chapter.key || '');
		}
		if (chapterRaw) {
			chapterRaw.textContent = chapter.content || 'Este capítulo no tiene contenido.';
		}
		if (quizIdField) {
			quizIdField.value = chapter && chapter.quiz_id ? String(chapter.quiz_id) : '0';
		}
		if (activeQuizChip) {
			activeQuizChip.textContent = 'Quiz ID ' + (chapter && chapter.quiz_id ? String(chapter.quiz_id) : '0');
		}
	}

	function chapterPrompt(chapter) {
		const settings = getPromptSettings();
		const chapterNumber = chapter.order || (activeChapterIndex + 1);
		const chapterKey = chapter.key || ('chapter-' + chapterNumber);
		const difficultyLabel = getDifficultyLabel(settings.difficulty);
		const styleLabel = getStyleLabel(settings.style);
		const questionTarget = settings.questionCount;
		const alternativesTarget = settings.alternativesCount;
		const quizTitle = `${bookTitle} · ${chapter.title || ('Chapter ' + chapterNumber)}`;

		return [
			'ACTÚA COMO UN DISEÑADOR EXPERTO DE QUIZZES PARA UN LIBRO.',
			'Tu única tarea es crear un quiz basado en el contenido del capítulo indicado abajo.',
			'Usa únicamente el contenido del capítulo como fuente. No agregues información externa ni inventes datos.',
			`Genera exactamente ${questionTarget} preguntas.`,
			`Cada pregunta debe tener exactamente ${alternativesTarget} alternativas.`,
			`La dificultad objetivo es: ${difficultyLabel}.`,
			`El estilo deseado es: ${styleLabel}.`,
			'Prioriza comprensión, relaciones entre ideas, causas, consecuencias e interpretación por sobre memoria literal.',
			'Evita preguntas cuya respuesta pueda deducirse por longitud, tecnicismo o singularidad de una alternativa.',
			'Todas las alternativas deben pertenecer a la misma categoría semántica.',
			'Las alternativas deben tener longitudes parecidas.',
			'No uses "Todas las anteriores", "Ninguna de las anteriores", "Siempre" ni "Nunca".',
			'Distribuye la respuesta correcta de forma variada entre las alternativas.',
			'Cada pregunta debe evaluar una sola idea.',
			'No reutilices el mismo fragmento del texto para varias preguntas.',
			'Si una pregunta puede responderse sin leer el capítulo, descártala.',
			'Los distractores deben ser verosímiles para alguien que leyó superficialmente el texto.',
			'La respuesta correcta debe quedar claramente identificada en cada pregunta.',
			'Devuelve exclusivamente JSON válido, sin markdown, sin viñetas, sin explicaciones, sin saludos y sin texto adicional.',
			'',
			'Contexto:',
			`- Libro: ${bookTitle}`,
			`- Capítulo: ${chapter.title || ('Chapter ' + chapterNumber)}`,
			`- Identificador: ${chapterKey}`,
			`- Título sugerido del quiz: ${quizTitle}`,
			`- Número de preguntas: ${questionTarget}`,
			`- Alternativas por pregunta: ${alternativesTarget}`,
			`- Dificultad: ${difficultyLabel}`,
			`- Estilo: ${styleLabel}`,
			'- El JSON final debe seguir el formato de Learni/Bookster.',
			'- No incluyas texto fuera del JSON final.',
			'- La salida debe ser un objeto JSON único y completo.',
			'',
			'Contenido del capítulo:',
			chapter.content || '',
			'',
			'Formato de salida requerido:',
			'{',
			`  "quiz_title": ${JSON.stringify(quizTitle)},`,
			'  "scope": "chapter",',
			`  "book_title": ${JSON.stringify(bookTitle)},`,
			`  "chapter_title": ${JSON.stringify(chapter.title || ('Chapter ' + chapterNumber))},`,
			`  "chapter_key": ${JSON.stringify(chapterKey)},`,
			'  "settings": {',
			'    "passing_score": 80,',
			'    "time_limit_seconds": 0,',
			'    "question_order": "in_order",',
			'    "shuffle_answers": 1,',
			'    "show_points": 0,',
			'    "run_once": 0,',
			'    "force_solve": 1,',
			'    "restart_cooldown_days": 0,',
			`    "question_count": ${questionTarget},`,
			`    "alternatives_count": ${alternativesTarget},`,
			`    "difficulty": ${JSON.stringify(settings.difficulty)},`,
			`    "style": ${JSON.stringify(settings.style)}`,
			'  },',
			'  "questions": [',
			'    {',
			'      "title": "Pregunta 1",',
			'      "question_text": "Texto de la pregunta",',
			'      "answers": [',
			'        { "text": "Opción 1", "correct": false },',
			'        { "text": "Opción correcta", "correct": true }',
			'      ]',
			'    }',
			'  ]',
			'}'
		].join('\n');
	}

	async function copyText(text, button) {
		if (!text) {
			return;
		}

		if (navigator.clipboard && navigator.clipboard.writeText) {
			await navigator.clipboard.writeText(text);
		} else {
			const holder = document.createElement('textarea');
			holder.value = text;
			holder.setAttribute('readonly', 'readonly');
			holder.style.position = 'fixed';
			holder.style.left = '-9999px';
			document.body.appendChild(holder);
			holder.select();
			document.execCommand('copy');
			document.body.removeChild(holder);
		}

		if (button) {
			const original = button.innerHTML;
			button.innerHTML = '<i class="fa-solid fa-check"></i>';
			setTimeout(() => {
				button.innerHTML = original;
			}, 1500);
		}
	}

	async function copyPromptForChapter(index, button) {
		const chapter = getChapter(index);
		if (!chapter) {
			return;
		}

		await copyText(chapterPrompt(chapter), button);
	}

	function setActiveChapter(index) {
		if (!chapters[index]) {
			return;
		}

		activeChapterIndex = index;
		updateChapterListState();
		updateChapterView();
		if (activeTab === 'chapter-content') {
			updateTabState();
		}
	}

	function setActiveTab(tabName) {
		activeTab = tabName;
		updateTabState();
	}

	function buildSavePayload() {
		const chapter = getChapter(activeChapterIndex);
		const chapterKey = chapter && chapter.key ? String(chapter.key) : ('chapter-' + (activeChapterIndex + 1));
		const chapterId = chapter && chapter.id ? Number(chapter.id) : 0;
		const chapterTitle = chapter && chapter.title ? String(chapter.title) : '';
		const quizTitle = loadedQuiz && typeof loadedQuiz.quiz_title === 'string' && loadedQuiz.quiz_title.trim() !== ''
			? loadedQuiz.quiz_title.trim()
			: (chapterTitle !== '' ? chapterTitle : bookTitle);
		const settings = loadedQuiz && loadedQuiz.settings && typeof loadedQuiz.settings === 'object'
			? { ...loadedQuiz.settings }
			: {};
		const questions = Array.isArray(loadedQuiz && loadedQuiz.questions) ? loadedQuiz.questions.map((question) => ({
			title: String(question && question.title ? question.title : ''),
			question_text: String(question && question.question_text ? question.question_text : ''),
			chapter_key: String(question && question.chapter_key ? question.chapter_key : chapterKey),
			chapter_id: Number.isFinite(Number(question && question.chapter_id)) ? Number(question.chapter_id) : chapterId,
			chapter_title: String(question && question.chapter_title ? question.chapter_title : chapterTitle),
			answers: Array.isArray(question && question.answers) ? question.answers.map((answer) => ({
				text: String(answer && answer.text ? answer.text : ''),
				correct: !!(answer && answer.correct)
			})) : []
		})) : [];

		return {
			quiz_title: quizTitle,
			scope: String(loadedQuiz && loadedQuiz.scope ? loadedQuiz.scope : 'chapter'),
			book_title: bookTitle,
			chapter_title: chapterTitle,
			chapter_key: chapterKey,
			chapter_id: chapterId,
			settings: {
				passing_score: Number.isFinite(Number(settings.passing_score)) ? Number(settings.passing_score) : 80,
				time_limit_seconds: Number.isFinite(Number(settings.time_limit_seconds)) ? Number(settings.time_limit_seconds) : 0,
				question_order: String(settings.question_order || 'in_order'),
				shuffle_answers: settings.shuffle_answers ? 1 : 0,
				show_points: settings.show_points ? 1 : 0,
				run_once: settings.run_once ? 1 : 0,
				force_solve: settings.force_solve ? 1 : 0,
				restart_cooldown_days: Number.isFinite(Number(settings.restart_cooldown_days)) ? Number(settings.restart_cooldown_days) : 0,
				scope: String(loadedQuiz && loadedQuiz.scope ? loadedQuiz.scope : 'chapter'),
				book_title: bookTitle,
				chapter_title: chapterTitle,
				chapter_key: chapterKey,
				chapter_id: chapterId
			},
			questions: questions
		};
	}

	function saveQuiz() {
		if (!saveQuizForm || !saveQuizPayloadField) {
			window.alert('No se pudo preparar el formulario de guardado.');
			return;
		}

		if (!loadedQuiz) {
			loadedQuiz = {
				quiz_title: bookTitle,
				scope: 'chapter',
				settings: getPromptSettings(),
				questions: [createBlankQuestion(0)]
			};
		}

		ensureLoadedQuiz();

		const payload = buildSavePayload();
		saveQuizPayloadField.value = JSON.stringify(payload);
		if (quizIdField) {
			const chapter = getChapter(activeChapterIndex);
			quizIdField.value = chapter && chapter.quiz_id ? String(chapter.quiz_id) : '0';
		}
		saveQuizForm.submit();
	}

	function extractJsonFromRawText(raw) {
		const text = String(raw || '')
			.replace(/^\uFEFF/, '')
			.replace(/[\u2018\u2019]/g, "'")
			.replace(/[\u201C\u201D]/g, '"')
			.replace(/\u00A0/g, ' ')
			.trim();
		if (!text) {
			return null;
		}

		const fencedMatch = text.match(/```(?:json)?\s*([\s\S]*?)\s*```/i);
		if (fencedMatch && fencedMatch[1]) {
			const fencedText = fencedMatch[1]
				.replace(/^\uFEFF/, '')
				.replace(/[\u2018\u2019]/g, "'")
				.replace(/[\u201C\u201D]/g, '"')
				.replace(/\u00A0/g, ' ')
				.trim();
			try {
				return JSON.parse(fencedText);
			} catch (error) {
				// Continue with other tolerant strategies.
			}
		}

		try {
			return JSON.parse(text);
		} catch (error) {
			// Continue with a tolerant extraction.
		}

		const parseBalancedJson = (inputText) => {
			const openIndex = inputText.indexOf('{');
			if (openIndex < 0) {
				return null;
			}

			let depth = 0;
			let inString = false;
			let escaped = false;
			let endIndex = -1;

			for (let i = openIndex; i < inputText.length; i++) {
				const char = inputText[i];
				if (escaped) {
					escaped = false;
					continue;
				}
				if (char === '\\') {
					escaped = true;
					continue;
				}
				if (char === '"') {
					inString = !inString;
					continue;
				}
				if (inString) {
					continue;
				}
				if (char === '{') {
					depth++;
				} else if (char === '}') {
					depth--;
					if (depth === 0) {
						endIndex = i;
						break;
					}
				}
			}

			if (endIndex <= openIndex) {
				return null;
			}

			const candidate = inputText.slice(openIndex, endIndex + 1)
				.replace(/^\uFEFF/, '')
				.replace(/[\u2018\u2019]/g, "'")
				.replace(/[\u201C\u201D]/g, '"')
				.replace(/\u00A0/g, ' ')
				.trim();

			try {
				return JSON.parse(candidate);
			} catch (error) {
				return null;
			}
		};

		const balanced = parseBalancedJson(text);
		if (balanced) {
			return balanced;
		}

		const fenceStripped = text
			.replace(/^\s*```(?:json)?\s*/i, '')
			.replace(/\s*```\s*$/i, '')
			.replace(/^\s*json\s*/i, '')
			.trim();
		if (fenceStripped && fenceStripped !== text) {
			const balancedFence = parseBalancedJson(fenceStripped);
			if (balancedFence) {
				return balancedFence;
			}
			try {
				return JSON.parse(fenceStripped);
			} catch (error) {
				// Keep trying.
			}
		}

		return null;
	}

	function normalizeQuizPayload(payload) {
		if (!payload || typeof payload !== 'object') {
			return null;
		}

		const sourceQuiz = (payload.quiz && typeof payload.quiz === 'object') ? payload.quiz : payload;
		const sourceSettings = (sourceQuiz.settings && typeof sourceQuiz.settings === 'object')
			? sourceQuiz.settings
			: ((payload.settings && typeof payload.settings === 'object') ? payload.settings : {});
		const rawQuestions = Array.isArray(sourceQuiz.questions)
			? sourceQuiz.questions
			: (Array.isArray(payload.questions) ? payload.questions : (Array.isArray(payload.data) ? payload.data : []));

		if (!Array.isArray(rawQuestions) || rawQuestions.length === 0) {
			return null;
		}

		const questions = rawQuestions
			.filter((question) => question && typeof question === 'object')
			.map((question, index) => {
				const rawAnswers = Array.isArray(question.answers)
					? question.answers
					: (Array.isArray(question.options) ? question.options : []);

				const answers = rawAnswers
					.filter((answer) => answer && typeof answer === 'object')
					.map((answer, answerIndex) => ({
						text: String(answer.text || answer.answer_text || answer.label || ''),
						correct: !!(answer.correct || answer.is_correct || answer.isCorrect),
						sort_order: answerIndex
					}))
					.filter((answer) => answer.text.trim() !== '');

				return {
					title: String(question.title || question.question_title || `Question ${index + 1}`),
					question_text: String(question.question_text || question.prompt || question.text || ''),
					chapter_key: String(question.chapter_key || sourceQuiz.chapter_key || ''),
					chapter_id: Number.isFinite(Number(question.chapter_id)) ? Number(question.chapter_id) : Number.isFinite(Number(sourceQuiz.chapter_id)) ? Number(sourceQuiz.chapter_id) : 0,
					chapter_title: String(question.chapter_title || sourceQuiz.chapter_title || ''),
					answers: answers.length > 0 ? answers : [
						{ text: 'Answer 1', correct: true, sort_order: 0 },
						{ text: 'Answer 2', correct: false, sort_order: 1 }
					]
				};
			});

		return {
			quiz_title: String(payload.quiz_title || payload.title || sourceQuiz.title || bookTitle || 'Quiz'),
			scope: String(payload.scope || sourceQuiz.scope || sourceSettings.scope || 'chapter'),
			book_title: String(payload.book_title || sourceQuiz.book_title || bookTitle || ''),
			chapter_title: String(payload.chapter_title || sourceQuiz.chapter_title || ''),
			chapter_key: String(payload.chapter_key || sourceQuiz.chapter_key || ''),
			settings: {
				passing_score: Number.isFinite(Number(sourceSettings.passing_score)) ? Number(sourceSettings.passing_score) : 80,
				time_limit_seconds: Number.isFinite(Number(sourceSettings.time_limit_seconds)) ? Number(sourceSettings.time_limit_seconds) : 0,
				question_order: String(sourceSettings.question_order || 'in_order'),
				shuffle_answers: sourceSettings.shuffle_answers ? 1 : 0,
				show_points: sourceSettings.show_points ? 1 : 0,
				run_once: sourceSettings.run_once ? 1 : 0,
				force_solve: sourceSettings.force_solve ? 1 : 0,
				restart_cooldown_days: Number.isFinite(Number(sourceSettings.restart_cooldown_days)) ? Number(sourceSettings.restart_cooldown_days) : 0
			},
			questions: questions
		};
	}

	function escapeHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function createBlankAnswer(index, isCorrect) {
		return {
			text: 'Answer ' + (index + 1),
			correct: !!isCorrect
		};
	}

	function createBlankQuestion(index) {
		return {
			title: 'Question ' + (index + 1),
			question_text: '',
			answers: [
				createBlankAnswer(0, true),
				createBlankAnswer(1, false)
			]
		};
	}

	function ensureLoadedQuiz() {
		if (!loadedQuiz) {
			return null;
		}

		if (!Array.isArray(loadedQuiz.questions) || loadedQuiz.questions.length === 0) {
			loadedQuiz.questions = [createBlankQuestion(0)];
		}

		loadedQuiz.questions = loadedQuiz.questions.map((question, index) => {
			const safeQuestion = question && typeof question === 'object' ? question : {};
			const rawAnswers = Array.isArray(safeQuestion.answers) ? safeQuestion.answers : [];
			let answers = rawAnswers
				.filter((answer) => answer && typeof answer === 'object')
				.map((answer, answerIndex) => ({
					text: String(answer.text || ''),
					correct: !!answer.correct,
					sort_order: answerIndex
				}))
				.filter((answer) => answer.text.trim() !== '');

			if (answers.length === 0) {
				answers = [
					createBlankAnswer(0, true),
					createBlankAnswer(1, false)
				];
			}

			if (!answers.some((answer) => answer.correct)) {
				answers[0].correct = true;
			}

			return {
				title: String(safeQuestion.title || 'Question ' + (index + 1)),
				question_text: String(safeQuestion.question_text || ''),
				answers: answers
			};
		});

		if (activePreviewQuestionIndex >= loadedQuiz.questions.length) {
			activePreviewQuestionIndex = loadedQuiz.questions.length - 1;
		}
		if (activePreviewQuestionIndex < 0) {
			activePreviewQuestionIndex = 0;
		}

		return loadedQuiz;
	}

	function getActivePreviewQuestion() {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || loadedQuiz.questions.length === 0) {
			return null;
		}

		return loadedQuiz.questions[activePreviewQuestionIndex] || loadedQuiz.questions[0] || null;
	}

	function setActivePreviewQuestion(index) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || loadedQuiz.questions.length === 0) {
			return;
		}

		const nextIndex = Math.max(0, Math.min(index, loadedQuiz.questions.length - 1));
		activePreviewQuestionIndex = nextIndex;
		renderPreview();
	}

	function addPreviewQuestion() {
		if (!ensureLoadedQuiz()) {
			return;
		}

		loadedQuiz.questions.push(createBlankQuestion(loadedQuiz.questions.length));
		activePreviewQuestionIndex = loadedQuiz.questions.length - 1;
		renderPreview();
	}

	function removePreviewQuestion(index) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions)) {
			return;
		}

		if (loadedQuiz.questions.length <= 1) {
			loadedQuiz.questions = [createBlankQuestion(0)];
			activePreviewQuestionIndex = 0;
			renderPreview();
			return;
		}

		const nextIndex = Math.max(0, Math.min(index, loadedQuiz.questions.length - 1));
		loadedQuiz.questions.splice(nextIndex, 1);
		activePreviewQuestionIndex = Math.min(nextIndex, loadedQuiz.questions.length - 1);
		renderPreview();
	}

	function addPreviewAnswer(questionIndex) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || !loadedQuiz.questions[questionIndex]) {
			return;
		}

		const question = loadedQuiz.questions[questionIndex];
		question.answers = Array.isArray(question.answers) ? question.answers : [];
		question.answers.push(createBlankAnswer(question.answers.length, false));
		renderPreview();
	}

	function duplicatePreviewQuestion(index) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || !loadedQuiz.questions[index]) {
			return;
		}

		const source = loadedQuiz.questions[index];
		const clone = {
			title: String(source.title || 'Question ' + (loadedQuiz.questions.length + 1)),
			question_text: String(source.question_text || ''),
			answers: Array.isArray(source.answers)
				? source.answers.map((answer, answerIndex) => ({
					text: String(answer.text || ''),
					correct: !!answer.correct,
					sort_order: answerIndex
				}))
				: [createBlankAnswer(0, true), createBlankAnswer(1, false)]
		};

		loadedQuiz.questions.splice(index + 1, 0, clone);
		activePreviewQuestionIndex = index + 1;
		renderPreview();
	}

	function removePreviewAnswer(questionIndex, answerIndex) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || !loadedQuiz.questions[questionIndex]) {
			return;
		}

		const question = loadedQuiz.questions[questionIndex];
		if (!Array.isArray(question.answers) || question.answers.length <= 2) {
			return;
		}

		question.answers.splice(answerIndex, 1);
		if (!question.answers.some((answer) => answer.correct)) {
			question.answers[0].correct = true;
		}
		renderPreview();
	}

	function updatePreviewField(questionIndex, field, value) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || !loadedQuiz.questions[questionIndex]) {
			return;
		}

		loadedQuiz.questions[questionIndex][field] = value;
	}

	function updatePreviewAnswer(questionIndex, answerIndex, field, value) {
		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || !loadedQuiz.questions[questionIndex]) {
			return;
		}

		const question = loadedQuiz.questions[questionIndex];
		if (!Array.isArray(question.answers) || !question.answers[answerIndex]) {
			return;
		}

		if (field === 'correct' && value) {
			question.answers.forEach((answer, index) => {
				answer.correct = index === answerIndex;
			});
			renderPreview();
			return;
		}

		if (field === 'correct' && !value) {
			question.answers[answerIndex].correct = false;
			if (!question.answers.some((answer) => answer.correct) && question.answers[0]) {
				question.answers[0].correct = true;
			}
			renderPreview();
			return;
		}

		if (field !== 'correct') {
			question.answers[answerIndex][field] = value;
		}
	}

	function renderPreview() {
		if (!previewEmpty || !previewList || !previewSummary) {
			return;
		}

		if (!loadedQuiz || !Array.isArray(loadedQuiz.questions) || loadedQuiz.questions.length === 0) {
			previewEmpty.hidden = false;
			previewList.hidden = true;
			previewList.innerHTML = '';
			previewSummary.textContent = 'No hay un quiz cargado todavía.';
			return;
		}

		ensureLoadedQuiz();
		previewEmpty.hidden = true;
		previewList.hidden = false;
		const activeQuestion = getActivePreviewQuestion();
		const loadedTitle = typeof loadedQuiz.quiz_title === 'string' && loadedQuiz.quiz_title.trim() !== ''
			? loadedQuiz.quiz_title.trim()
			: (typeof loadedQuiz.title === 'string' && loadedQuiz.title.trim() !== '' ? loadedQuiz.title.trim() : 'Quiz cargado');
		previewSummary.textContent = loadedTitle + ' · ' + loadedQuiz.questions.length + ' preguntas';

		const questionCount = loadedQuiz.questions.length;
		const questionIndexDisplay = activePreviewQuestionIndex + 1;
		const answers = activeQuestion && Array.isArray(activeQuestion.answers) ? activeQuestion.answers : [];
		const dotHtml = loadedQuiz.questions.map((question, index) => {
			const hasContent = !!((question && String(question.title || '').trim() !== '') || (question && String(question.question_text || '').trim() !== ''));
			return '<button type="button" class="almaden-slide-dot' + (index === activePreviewQuestionIndex ? ' is-active' : '') + (hasContent ? ' is-filled' : '') + '" data-preview-go-to="' + index + '" aria-label="Go to question ' + (index + 1) + '"></button>';
		}).join('');
		const answerHtml = answers.map((answer, answerIndex) => {
			return [
				'<div class="almaden-answer-row">',
				'  <input type="checkbox" data-preview-answer-correct="' + answerIndex + '"' + (answer.correct ? ' checked' : '') + '>',
				'  <input type="text" data-preview-answer-text="' + answerIndex + '" value="' + escapeHtml(answer.text || '') + '" placeholder="Answer text">',
				'  <button type="button" class="almaden-btn almaden-btn--ghost" data-preview-action="remove-answer" data-preview-answer-index="' + answerIndex + '">Remove</button>',
				'</div>'
			].join('');
		}).join('');

		previewList.innerHTML = [
			'<div class="almaden-preview-shell">',
			'  <div class="almaden-preview-nav">',
			'    <div class="almaden-preview-nav-group">',
			'      <button type="button" class="almaden-btn almaden-btn--ghost" data-preview-action="prev">Previous</button>',
			'      <span class="almaden-slide-indicator">Slide ' + questionIndexDisplay + ' / ' + questionCount + '</span>',
			'      <button type="button" class="almaden-btn almaden-btn--ghost" data-preview-action="next">Next</button>',
			'    </div>',
			'    <div class="almaden-preview-nav-group">',
			'      <button type="button" class="almaden-btn almaden-btn--soft" data-preview-action="add-question">Add slide</button>',
			'      <button type="button" class="almaden-btn almaden-btn--danger" data-preview-action="remove-question">Delete slide</button>',
			'    </div>',
			'  </div>',
			'  <div class="almaden-slide-dots" aria-label="Question navigation">',
			dotHtml,
			'  </div>',
			'  <div class="almaden-preview-card">',
			'    <div class="almaden-slide-head">',
			'      <div>',
			'        <h4>' + escapeHtml(activeQuestion && activeQuestion.title ? activeQuestion.title : ('Question ' + questionIndexDisplay)) + '</h4>',
			'        <p class="almaden-helper">Edita cada slide antes de guardar. Los cambios quedan en memoria para la siguiente fase.</p>',
			'      </div>',
			'      <div class="almaden-slide-actions">',
			'        <button type="button" class="almaden-btn almaden-btn--ghost" data-preview-action="duplicate-question">Duplicate slide</button>',
			'      </div>',
			'    </div>',
			'    <div class="almaden-slide-field">',
			'      <label>Question title</label>',
			'      <input type="text" data-preview-field="question-title" value="' + escapeHtml(activeQuestion && activeQuestion.title ? activeQuestion.title : '') + '">',
			'    </div>',
			'    <div class="almaden-slide-field">',
			'      <label>Question text</label>',
			'      <textarea data-preview-field="question-text">' + escapeHtml(activeQuestion && activeQuestion.question_text ? activeQuestion.question_text : '') + '</textarea>',
			'    </div>',
			'    <div class="almaden-slide-field">',
			'      <div class="almaden-preview-nav">',
			'        <label>Answers</label>',
			'        <button type="button" class="almaden-btn almaden-btn--ghost" data-preview-action="add-answer">Add answer</button>',
			'      </div>',
			'      <div class="almaden-answer-list">',
			answerHtml,
			'      </div>',
			'    </div>',
			'  </div>',
			'</div>'
		].join('');

		const questionCounter = activeQuestion && Array.isArray(activeQuestion.answers) ? activeQuestion.answers.filter((answer) => answer && answer.correct).length : 0;
		if (previewSummary) {
			previewSummary.textContent = loadedTitle + ' · ' + loadedQuiz.questions.length + ' preguntas · ' + questionCounter + ' correctas en esta slide';
		}
	}

	function loadPromptPayload() {
		const raw = promptInput ? String(promptInput.value || '').trim() : '';
		if (!raw) {
			window.alert('Pega primero el JSON generado por el LLM.');
			return;
		}

		const parsed = extractJsonFromRawText(raw);
		if (!parsed) {
			window.alert('El contenido pegado no es JSON válido.');
			return;
		}

		const normalized = normalizeQuizPayload(parsed);
		if (!normalized) {
			window.alert('El JSON no contiene una estructura de quiz válida.');
			return;
		}

		loadedQuiz = normalized;
		activePreviewQuestionIndex = 0;
		ensureLoadedQuiz();
		renderPreview();
		setActiveTab('quiz-preview');
	}

	function bindChapterInteractions() {
		if (!chapterList) {
			return;
		}

		chapterList.addEventListener('click', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) {
				return;
			}

			const copyButton = target.closest('[data-copy-chapter-index]');
			if (copyButton) {
				event.stopPropagation();
				const index = Number(copyButton.getAttribute('data-copy-chapter-index'));
				copyPromptForChapter(index, copyButton);
				return;
			}

			const item = target.closest('[data-chapter-index]');
			if (!item) {
				return;
			}

			const index = Number(item.getAttribute('data-chapter-index'));
			setActiveChapter(index);
			setActiveTab('chapter-content');
		});

		chapterList.addEventListener('keydown', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) {
				return;
			}

			if (target.closest('[data-copy-chapter-index]')) {
				return;
			}

			if (event.key !== 'Enter' && event.key !== ' ') {
				return;
			}

			const item = target.closest('[data-chapter-index]');
			if (!item) {
				return;
			}

			event.preventDefault();
			const index = Number(item.getAttribute('data-chapter-index'));
			setActiveChapter(index);
			setActiveTab('chapter-content');
		});
	}

	tabButtons.forEach((button) => {
		button.addEventListener('click', () => {
			setActiveTab(button.getAttribute('data-tab-target') || 'prompt-settings');
		});
	});

	if (copyActivePromptBtn) {
		copyActivePromptBtn.addEventListener('click', () => {
			copyPromptForChapter(activeChapterIndex, copyActivePromptBtn);
		});
	}

	if (loadPromptBtn) {
		loadPromptBtn.addEventListener('click', loadPromptPayload);
	}

	if (previewFocus) {
		previewFocus.addEventListener('click', () => {
			setActiveTab('quiz-preview');
		});
	}

	if (saveQuizBtn) {
		saveQuizBtn.addEventListener('click', saveQuiz);
	}

	if (previewList) {
		previewList.addEventListener('click', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) {
				return;
			}

			const goTo = target.closest('[data-preview-go-to]');
			if (goTo) {
				const index = Number(goTo.getAttribute('data-preview-go-to'));
				setActivePreviewQuestion(index);
				return;
			}

			const actionNode = target.closest('[data-preview-action]');
			if (!actionNode) {
				return;
			}

			const action = actionNode.getAttribute('data-preview-action');
			const currentIndex = activePreviewQuestionIndex;

			if (action === 'prev') {
				setActivePreviewQuestion(currentIndex - 1);
				return;
			}
			if (action === 'next') {
				setActivePreviewQuestion(currentIndex + 1);
				return;
			}
			if (action === 'add-question') {
				addPreviewQuestion();
				return;
			}
			if (action === 'remove-question') {
				removePreviewQuestion(currentIndex);
				return;
			}
			if (action === 'add-answer') {
				addPreviewAnswer(currentIndex);
				return;
			}
			if (action === 'remove-answer') {
				const answerIndex = Number(actionNode.getAttribute('data-preview-answer-index'));
				removePreviewAnswer(currentIndex, answerIndex);
				return;
			}
			if (action === 'duplicate-question') {
				duplicatePreviewQuestion(currentIndex);
			}
		});

		previewList.addEventListener('input', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) {
				return;
			}

			const titleField = target.closest('[data-preview-field="question-title"]');
			if (titleField instanceof HTMLInputElement) {
				updatePreviewField(activePreviewQuestionIndex, 'title', titleField.value);
				const titleHeading = previewList.querySelector('.almaden-slide-head h4');
				if (titleHeading) {
					titleHeading.textContent = titleField.value.trim() !== '' ? titleField.value : ('Question ' + (activePreviewQuestionIndex + 1));
				}
				return;
			}

			const textField = target.closest('[data-preview-field="question-text"]');
			if (textField instanceof HTMLTextAreaElement) {
				updatePreviewField(activePreviewQuestionIndex, 'question_text', textField.value);
				return;
			}

			const answerTextField = target.closest('[data-preview-answer-text]');
			if (answerTextField instanceof HTMLInputElement) {
				const answerIndex = Number(answerTextField.getAttribute('data-preview-answer-text'));
				updatePreviewAnswer(activePreviewQuestionIndex, answerIndex, 'text', answerTextField.value);
			}
		});

		previewList.addEventListener('change', (event) => {
			const target = event.target;
			if (!(target instanceof HTMLElement)) {
				return;
			}

			const answerCorrectField = target.closest('[data-preview-answer-correct]');
			if (answerCorrectField instanceof HTMLInputElement) {
				const answerIndex = Number(answerCorrectField.getAttribute('data-preview-answer-correct'));
				updatePreviewAnswer(activePreviewQuestionIndex, answerIndex, 'correct', answerCorrectField.checked);
			}
		});
	}

	[questionCountField, alternativesCountField, difficultyField, styleField].forEach((field) => {
		if (field) {
			field.addEventListener('change', () => {
				if (activeChapterIndex >= 0) {
					// Rebuild the clipboard prompt on demand only.
				}
			});
		}
	});

	if (initialQuizData) {
		const hydratedQuiz = normalizeQuizPayload(initialQuizData);
		if (hydratedQuiz) {
			loadedQuiz = hydratedQuiz;
			activePreviewQuestionIndex = 0;
		}
	}

	bindChapterInteractions();
	updateChapterListState();
	updateChapterView();
	updateTabState();
	renderPreview();
})();
</script>
<?php wp_footer(); ?>
</body>
</html>
