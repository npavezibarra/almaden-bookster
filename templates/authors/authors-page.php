<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$authors = get_users(
	array(
		'role'    => 'author',
		'orderby' => 'display_name',
		'order'   => 'ASC',
		'number'  => -1,
	)
);

$author_book_counts = array();
$book_ids           = get_posts(
	array(
		'post_type'      => 'almaden-books',
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
	)
);

foreach ( $book_ids as $book_id ) {
	$book_author_ids = function_exists( 'almaden_bookster_get_book_author_ids' ) ? almaden_bookster_get_book_author_ids( $book_id ) : array();
	foreach ( $book_author_ids as $author_id ) {
		$author_id = absint( $author_id );
		if ( $author_id <= 0 ) {
			continue;
		}

		if ( ! isset( $author_book_counts[ $author_id ] ) ) {
			$author_book_counts[ $author_id ] = 0;
		}

		$author_book_counts[ $author_id ]++;
	}
}

$authors_total = count( $authors );
?>
<style id="almaden-authors-page-style">
	html,
	body {
		background-color: #f5f5f5;
	}

	#almaden-authors-page,
	#almaden-authors-page.almaden-app-content-shell {
		background-color: #f5f5f5;
	}
</style>

<main id="almaden-authors-page" class="almaden-app-content-shell pb-16">
	<section class="pt-0">
		<div class="w-full">
			<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
				<div>
					<p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Directorio</p>
					<h1 class="mt-3 text-4xl font-semibold tracking-tight text-slate-900 sm:text-5xl">Autores</h1>
					<p class="mt-4 text-lg leading-8 text-slate-600">
						<?php
						echo esc_html(
							sprintf(
								_n( 'Hay %d autor registrado en la plataforma.', 'Hay %d autores registrados en la plataforma.', $authors_total, 'almaden-bookster' ),
								$authors_total
							)
						);
						?>
					</p>
				</div>

				<button
					type="button"
					class="inline-flex w-fit items-center justify-center rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-[0_12px_30px_rgba(15,23,42,0.12)] transition hover:bg-slate-800"
				>
					Añadir autor
				</button>
			</div>
		</div>
	</section>

	<section class="mt-10">
		<?php if ( ! empty( $authors ) ) : ?>
			<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3" id="almaden-authors-list">
				<?php foreach ( $authors as $author ) : ?>
					<?php
					$author_id          = absint( $author->ID );
					$author_slug        = function_exists( 'almaden_bookster_get_author_user_slug' ) ? almaden_bookster_get_author_user_slug( $author_id ) : sanitize_title( $author->display_name );
					$author_url         = '' !== $author_slug ? almaden_bookster_get_author_page_url( $author_slug ) : '';
					$author_photo_id    = function_exists( 'almaden_bookster_get_author_profile_photo_id' ) ? almaden_bookster_get_author_profile_photo_id( $author_id ) : 0;
					$author_photo_url   = $author_photo_id > 0 ? wp_get_attachment_image_url( $author_photo_id, 'medium' ) : '';
					$author_bio         = trim( (string) get_user_meta( $author_id, 'description', true ) );
					$author_books_count = isset( $author_book_counts[ $author_id ] ) ? absint( $author_book_counts[ $author_id ] ) : 0;
					$author_socials     = function_exists( 'almaden_bookster_get_author_social_links' ) ? almaden_bookster_get_author_social_links( $author_id ) : array();
					$author_hero_bg     = function_exists( 'almaden_bookster_get_author_hero_background_settings' ) ? almaden_bookster_get_author_hero_background_settings( $author_id ) : array();
					$author_hero_image  = '';
					if ( is_array( $author_hero_bg ) && isset( $author_hero_bg['type'] ) && 'image' === sanitize_key( (string) $author_hero_bg['type'] ) && ! empty( $author_hero_bg['image_id'] ) ) {
						$author_hero_image = wp_get_attachment_image_url( absint( $author_hero_bg['image_id'] ), 'full' ) ?: '';
					}
					$author_card_id     = 'almaden-author-' . $author_id;
					?>

					<article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_12px_30px_rgba(15,23,42,0.06)] transition-transform duration-200 hover:-translate-y-1" id="<?php echo esc_attr( $author_card_id ); ?>" data-author-id="<?php echo esc_attr( $author_id ); ?>">
						<div
							class="h-32 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700"
							id="<?php echo esc_attr( $author_card_id . '-cover' ); ?>"
							data-author-id="<?php echo esc_attr( $author_id ); ?>"
							<?php echo $author_hero_image ? 'style="background-image:url(' . esc_url( $author_hero_image ) . ');background-position:center;background-repeat:no-repeat;background-size:cover;"' : ''; ?>
						></div>
						<div class="px-6 pb-6" id="<?php echo esc_attr( $author_card_id . '-body' ); ?>">
							<div class="-mt-12 flex items-end gap-4" id="<?php echo esc_attr( $author_card_id . '-header' ); ?>">
								<div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border-4 border-white bg-slate-100 shadow-lg" id="<?php echo esc_attr( $author_card_id . '-photo-wrap' ); ?>">
									<?php if ( $author_photo_url ) : ?>
										<img src="<?php echo esc_url( $author_photo_url ); ?>" alt="<?php echo esc_attr( $author->display_name ); ?>" class="h-full w-full object-cover" id="<?php echo esc_attr( $author_card_id . '-photo' ); ?>">
									<?php else : ?>
										<span class="text-2xl font-semibold tracking-tight text-slate-500" id="<?php echo esc_attr( $author_card_id . '-photo-initial' ); ?>"><?php echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( strtoupper( $author->display_name ), 0, 1 ) : substr( strtoupper( $author->display_name ), 0, 1 ) ); ?></span>
									<?php endif; ?>
								</div>

								<div class="min-w-0 flex-1 pb-1" id="<?php echo esc_attr( $author_card_id . '-identity' ); ?>">
									<h2 class="truncate text-2xl font-semibold tracking-tight text-slate-900" id="<?php echo esc_attr( $author_card_id . '-name' ); ?>"><?php echo esc_html( $author->display_name ); ?></h2>
								</div>
							</div>

							<div class="mt-5 flex flex-wrap gap-2" id="<?php echo esc_attr( $author_card_id . '-badges' ); ?>">
								<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600" id="<?php echo esc_attr( $author_card_id . '-books-count' ); ?>"><?php echo esc_html( sprintf( _n( '%d libro', '%d libros', $author_books_count, 'almaden-bookster' ), $author_books_count ) ); ?></span>
								<span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700" id="<?php echo esc_attr( $author_card_id . '-role' ); ?>">Autor</span>
								<?php if ( ! empty( array_filter( $author_socials ) ) ) : ?>
									<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700" id="<?php echo esc_attr( $author_card_id . '-social-badge' ); ?>">Social</span>
								<?php endif; ?>
							</div>

							<div class="mt-4" id="<?php echo esc_attr( $author_card_id . '-bio-wrap' ); ?>">
								<?php if ( '' !== $author_bio ) : ?>
									<p class="text-sm leading-7 text-slate-600" id="<?php echo esc_attr( $author_card_id . '-bio' ); ?>">
										<?php echo esc_html( wp_trim_words( wp_strip_all_tags( $author_bio ), 26 ) ); ?>
									</p>
								<?php else : ?>
									<p class="text-sm leading-7 text-slate-500" id="<?php echo esc_attr( $author_card_id . '-bio-empty' ); ?>">Este autor todavía no tiene una biografía pública.</p>
								<?php endif; ?>
							</div>

							<div class="mt-6 flex items-center justify-between gap-4" id="<?php echo esc_attr( $author_card_id . '-actions' ); ?>">
								<?php if ( $author_url ) : ?>
									<a href="<?php echo esc_url( $author_url ); ?>" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-800" id="<?php echo esc_attr( $author_card_id . '-profile-link' ); ?>">
										Ver perfil
									</a>
								<?php else : ?>
									<span class="inline-flex items-center rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-400" id="<?php echo esc_attr( $author_card_id . '-profile-empty' ); ?>">Sin perfil público</span>
								<?php endif; ?>

								<?php if ( $author_photo_url ) : ?>
									<span class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400" id="<?php echo esc_attr( $author_card_id . '-active-state' ); ?>">Perfil activo</span>
								<?php endif; ?>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="rounded-3xl border border-amber-200 bg-amber-50 px-6 py-8 text-amber-900">
				<p class="text-lg font-semibold">Aún no hay autores registrados.</p>
				<p class="mt-2 text-sm leading-7 text-amber-800">Cuando agregues usuarios con rol Autor, aparecerán aquí para que la editorial pueda gestionarlos y enlazarlos con sus libros.</p>
			</div>
		<?php endif; ?>
	</section>
</main>
