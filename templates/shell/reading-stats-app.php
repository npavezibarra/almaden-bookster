<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$reading_stats = function_exists( 'almaden_bookster_get_user_reading_stats' ) ? almaden_bookster_get_user_reading_stats() : array();
$books         = isset( $reading_stats['books'] ) && is_array( $reading_stats['books'] ) ? $reading_stats['books'] : array();
$activity      = isset( $reading_stats['activity'] ) && is_array( $reading_stats['activity'] ) ? $reading_stats['activity'] : array();
$highlights    = isset( $reading_stats['recentHighlights'] ) && is_array( $reading_stats['recentHighlights'] ) ? $reading_stats['recentHighlights'] : array();
$bookshelf_url  = function_exists( 'almaden_bookster_get_store_page_url' ) ? almaden_bookster_get_store_page_url() : home_url( '/' );

$format_datetime = static function( $datetime ) {
	$datetime = trim( (string) $datetime );
	if ( '' === $datetime ) {
		return '';
	}

	$timestamp = strtotime( $datetime );
	if ( ! $timestamp ) {
		return $datetime;
	}

	$date_format = function_exists( 'get_option' ) ? get_option( 'date_format' ) : 'Y-m-d';
	$time_format = function_exists( 'get_option' ) ? get_option( 'time_format' ) : 'H:i';

	if ( function_exists( 'wp_date' ) ) {
		return wp_date( $date_format . ' ' . $time_format, $timestamp );
	}

	if ( function_exists( 'date_i18n' ) ) {
		return date_i18n( $date_format . ' ' . $time_format, $timestamp );
	}

	return date( $date_format . ' ' . $time_format, $timestamp );
};

almaden_bookster_render_app_shell_start(
	array(
		'title'          => function_exists( 'almaden_bookster_get_reading_stats_title' ) ? almaden_bookster_get_reading_stats_title() . ' - Almaden' : 'My Reading Stats - Almaden',
		'body_id'        => 'almaden-reading-stats-body',
		'active_nav_key' => 'reading_stats',
	)
);
?>
<main id="almaden-reading-stats-page" class="almaden-app-content-shell flex-1 pb-16">
	<section class="py-10">
		<div>
			<p class="mb-3 text-xs font-semibold uppercase tracking-[0.3em] text-gray-400">Reading overview</p>
			<h1 class="max-w-2xl text-4xl font-bold tracking-tight text-black sm:text-5xl">My Reading Stats</h1>
			<p class="mt-5 max-w-2xl text-lg leading-8 text-gray-600">Un resumen de tus highlights, quizzes y actividad reciente. La idea es que esta pantalla funcione como tu panel personal de lectura dentro del shell de Almaden.</p>
			<div class="mt-6 flex flex-wrap gap-3">
				<a href="<?php echo esc_url( $bookshelf_url ); ?>" class="inline-flex items-center rounded-full border border-black bg-black px-5 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
					Ir al bookshelf
				</a>
				<a href="#almaden-reading-stats-books" class="inline-flex items-center rounded-full border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-900 transition hover:border-gray-300 hover:bg-gray-50">
					Ver tus libros
				</a>
			</div>
		</div>
	</section>

	<section id="almaden-reading-stats-books" class="grid gap-6 lg:grid-cols-[0.92fr_1.08fr]">
		<aside class="rounded-[2rem] border border-gray-200 bg-white p-6 shadow-sm">
			<div class="flex items-end justify-between gap-4">
				<div>
					<p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Bookshelf</p>
					<h2 class="mt-2 text-2xl font-bold text-black">Libros con actividad</h2>
				</div>
			</div>

			<?php if ( empty( $books ) ) : ?>
				<div class="mt-6 rounded-[1.5rem] border border-dashed border-gray-200 bg-gray-50 p-6">
					<p class="text-base font-semibold text-black">Todavia no hay actividad registrada.</p>
					<p class="mt-2 text-sm leading-6 text-gray-600">Abre un ebook, guarda un highlight o completa un quiz para que aparezca aqui.</p>
				</div>
			<?php else : ?>
				<div class="mt-6 space-y-4">
					<?php foreach ( $books as $book ) : ?>
						<?php
						$book_title = isset( $book['bookTitle'] ) ? (string) $book['bookTitle'] : '';
						$book_url   = isset( $book['bookUrl'] ) ? (string) $book['bookUrl'] : '';
						$last_activity = isset( $book['lastActivityAt'] ) ? (string) $book['lastActivityAt'] : '';
						$book_completion = isset( $book['completionRate'] ) ? (int) $book['completionRate'] : 0;
						?>
						<article class="rounded-[1.5rem] border border-gray-200 bg-white p-5 transition hover:border-gray-300">
							<div class="flex items-start justify-between gap-4">
								<div class="min-w-0">
									<?php if ( '' !== $book_url ) : ?>
										<a href="<?php echo esc_url( $book_url ); ?>" class="block truncate text-lg font-semibold text-black hover:underline"><?php echo esc_html( $book_title ); ?></a>
									<?php else : ?>
										<p class="truncate text-lg font-semibold text-black"><?php echo esc_html( $book_title ); ?></p>
									<?php endif; ?>
									<p class="mt-1 text-sm leading-6 text-gray-500">
										<?php echo esc_html( ! empty( $last_activity ) ? $format_datetime( $last_activity ) : 'Sin actividad' ); ?>
									</p>
								</div>
								<div class="rounded-full bg-black px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white">
									<?php echo esc_html( $book_completion ); ?>%
								</div>
							</div>
							<div class="mt-4 grid gap-2 sm:grid-cols-3">
								<div class="rounded-[1rem] bg-gray-50 px-3 py-2">
									<p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400">Highlights</p>
									<p class="mt-1 text-sm font-semibold text-black"><?php echo esc_html( number_format_i18n( isset( $book['highlightCount'] ) ? (int) $book['highlightCount'] : 0 ) ); ?></p>
								</div>
								<div class="rounded-[1rem] bg-gray-50 px-3 py-2">
									<p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400">Intentos</p>
									<p class="mt-1 text-sm font-semibold text-black"><?php echo esc_html( number_format_i18n( isset( $book['attemptCount'] ) ? (int) $book['attemptCount'] : 0 ) ); ?></p>
								</div>
								<div class="rounded-[1rem] bg-gray-50 px-3 py-2">
									<p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400">Quizzes</p>
									<p class="mt-1 text-sm font-semibold text-black"><?php echo esc_html( sprintf( '%d/%d', isset( $book['quizCompletedCount'] ) ? (int) $book['quizCompletedCount'] : 0, isset( $book['totalQuizCount'] ) ? (int) $book['totalQuizCount'] : 0 ) ); ?></p>
								</div>
							</div>
							<?php if ( ! empty( $book['lastHighlightExcerpt'] ) ) : ?>
								<p class="mt-4 line-clamp-2 text-sm leading-6 text-gray-600"><?php echo esc_html( $book['lastHighlightExcerpt'] ); ?></p>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</aside>

		<div class="space-y-6">
			<section class="rounded-[2rem] border border-gray-200 bg-white p-6 shadow-sm">
				<div class="flex items-end justify-between gap-4">
					<div>
						<p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Actividad</p>
						<h2 class="mt-2 text-2xl font-bold text-black">Actividad reciente</h2>
					</div>
				</div>

				<?php if ( empty( $activity ) ) : ?>
					<div class="mt-6 rounded-[1.5rem] border border-dashed border-gray-200 bg-gray-50 p-6">
						<p class="text-base font-semibold text-black">Aun no hay eventos recientes.</p>
						<p class="mt-2 text-sm leading-6 text-gray-600">Cuando agregues actividad, aqui veremos highlights, quizzes y avances por libro.</p>
					</div>
				<?php else : ?>
					<div class="mt-6 space-y-3">
						<?php foreach ( $activity as $item ) : ?>
							<?php
							$item_type = isset( $item['type'] ) ? (string) $item['type'] : 'highlight';
							$item_title = isset( $item['bookTitle'] ) ? (string) $item['bookTitle'] : '';
							$item_url = isset( $item['bookUrl'] ) ? (string) $item['bookUrl'] : '';
							$item_detail = isset( $item['detail'] ) ? (string) $item['detail'] : '';
							$item_chapter = isset( $item['chapterTitle'] ) ? (string) $item['chapterTitle'] : '';
							?>
							<article class="rounded-[1.35rem] border border-gray-200 bg-gray-50 p-4">
								<div class="flex flex-wrap items-center gap-2">
									<span class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500"><?php echo esc_html( 'attempt' === $item_type ? 'Quiz' : 'Highlight' ); ?></span>
									<?php if ( '' !== $item_chapter ) : ?>
										<span class="text-sm font-medium text-gray-500"><?php echo esc_html( $item_chapter ); ?></span>
									<?php endif; ?>
								</div>
								<div class="mt-3">
									<?php if ( '' !== $item_url ) : ?>
										<a href="<?php echo esc_url( $item_url ); ?>" class="text-base font-semibold text-black hover:underline"><?php echo esc_html( $item_title ); ?></a>
									<?php else : ?>
										<p class="text-base font-semibold text-black"><?php echo esc_html( $item_title ); ?></p>
									<?php endif; ?>
									<p class="mt-2 text-sm leading-6 text-gray-600"><?php echo esc_html( $item_detail ); ?></p>
									<p class="mt-2 text-xs font-medium uppercase tracking-[0.18em] text-gray-400"><?php echo esc_html( ! empty( $item['createdAt'] ) ? $format_datetime( $item['createdAt'] ) : '' ); ?></p>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>

			<section class="rounded-[2rem] border border-gray-200 bg-white p-6 shadow-sm">
				<div class="flex items-end justify-between gap-4">
					<div>
						<p class="text-xs font-semibold uppercase tracking-[0.22em] text-gray-400">Highlights</p>
						<h2 class="mt-2 text-2xl font-bold text-black">Tus highlights recientes</h2>
					</div>
				</div>

				<?php if ( empty( $highlights ) ) : ?>
					<div class="mt-6 rounded-[1.5rem] border border-dashed border-gray-200 bg-gray-50 p-6">
						<p class="text-base font-semibold text-black">Aun no guardaste highlights.</p>
						<p class="mt-2 text-sm leading-6 text-gray-600">Usa el reader para resaltar fragmentos y apareceran aqui con su contexto de lectura.</p>
					</div>
				<?php else : ?>
					<div class="mt-6 grid gap-3">
						<?php foreach ( $highlights as $highlight ) : ?>
							<article class="rounded-[1.35rem] border border-gray-200 bg-white p-4">
								<div class="flex flex-wrap items-center gap-2">
									<p class="text-sm font-semibold text-black"><?php echo esc_html( isset( $highlight['bookTitle'] ) ? (string) $highlight['bookTitle'] : '' ); ?></p>
									<?php if ( ! empty( $highlight['chapterTitle'] ) ) : ?>
										<span class="text-sm text-gray-400">/</span>
										<p class="text-sm text-gray-500"><?php echo esc_html( $highlight['chapterTitle'] ); ?></p>
									<?php endif; ?>
								</div>
								<p class="mt-3 text-sm leading-6 text-gray-600"><?php echo esc_html( isset( $highlight['text'] ) ? (string) $highlight['text'] : '' ); ?></p>
								<p class="mt-3 text-xs font-medium uppercase tracking-[0.18em] text-gray-400"><?php echo esc_html( ! empty( $highlight['createdAt'] ) ? $format_datetime( $highlight['createdAt'] ) : '' ); ?></p>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
		</div>
	</section>
</main>
<?php
almaden_bookster_render_app_shell_end();
