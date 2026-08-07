<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$book_id = get_the_ID();
$book_title = get_the_title();
$book_settings = function_exists( 'almaden_get_book_pdf_settings' ) ? almaden_get_book_pdf_settings( $book_id ) : array();
$book_language = function_exists( 'almaden_bookster_get_book_language_from_settings' )
	? almaden_bookster_get_book_language_from_settings( $book_settings, 'es' )
	: 'es';
$author = function_exists( 'almaden_bookster_get_book_author_display_label' ) ? almaden_bookster_get_book_author_display_label( $book_id, get_post_meta( $book_id, '_almaden_book_author', true ) ) : get_post_meta( $book_id, '_almaden_book_author', true );
$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
if ( empty( $source_book_id ) ) {
	$source_book_id = $book_id;
}

require_once dirname( __FILE__ ) . '/../../includes/helpers/cover-thumbnail.php';
$cover_html = almaden_get_cover_thumbnail_html( $book_id );
$fonts_url = almaden_get_thumbnail_fonts_url();

$book_excerpt = get_post_field( 'post_excerpt', $book_id );
if ( empty( $book_excerpt ) ) {
	$book_excerpt = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $book_id ) ), 42 );
}

$book_categories = get_the_terms( $book_id, 'category' );
$book_category_names = array();
if ( ! is_wp_error( $book_categories ) && is_array( $book_categories ) ) {
	foreach ( $book_categories as $category ) {
		$book_category_names[] = $category->name;
	}
}

$terms_url = function_exists( 'almaden_bookster_get_book_terms_url' ) ? almaden_bookster_get_book_terms_url() : home_url( '/' );

$chapters_query = new WP_Query(
	array(
		'post_type'      => 'book_chapter',
		'post_parent'    => $source_book_id,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	)
);

$chapters = array();
if ( $chapters_query->have_posts() ) {
	while ( $chapters_query->have_posts() ) {
		$chapters_query->the_post();
		$chapters[] = array(
			'id'    => get_the_ID(),
			'title' => get_the_title(),
			'locked' => true,
		);
	}
	wp_reset_postdata();
}

$has_reader_access = function_exists( 'almaden_bookster_user_can_access_book' ) ? almaden_bookster_user_can_access_book( $book_id ) : is_user_logged_in();
$purchase_url = function_exists( 'almaden_bookster_get_book_purchase_url' ) ? almaden_bookster_get_book_purchase_url( $book_id ) : home_url( '/' );
$book_product_id = function_exists( 'almaden_bookster_get_book_product_id' ) ? almaden_bookster_get_book_product_id( $book_id ) : 0;
$return_url = function_exists( 'almaden_bookster_get_book_return_url' ) ? almaden_bookster_get_book_return_url( $book_id ) : ( function_exists( 'almaden_bookster_get_store_page_url' ) ? almaden_bookster_get_store_page_url() : home_url( '/' ) );
$wide_size = '1300px';
if ( function_exists( 'wp_get_global_settings' ) ) {
	$wide_size_val = wp_get_global_settings( array( 'layout', 'wideSize' ) );
	if ( ! empty( $wide_size_val ) ) {
		$wide_size = $wide_size_val;
	}
}

if ( $has_reader_access ) {
	require_once dirname( __FILE__ ) . '/../reader/reader-app.php';
	return;
}
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( $book_language ); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $book_title ); ?> - Ebook Store</title>
	<script>
		window.tailwind = window.tailwind || {};
		window.tailwind.config = {
			theme: {
				extend: {
					fontFamily: {
						sans: ['"Urbanist"', 'sans-serif']
					}
				}
			}
		};
	</script>
	<script src="https://cdn.tailwindcss.com"></script>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="<?php echo esc_url( $fonts_url ); ?>" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&amp;display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/reader-app.css' ); ?>?v=<?php echo esc_attr( filemtime( dirname( __FILE__ ) . '/../../assets/css/reader-app.css' ) ); ?>">
	<style>
		html {
			margin-top: 0 !important;
		}
		body { font-family: 'Urbanist', sans-serif; background: linear-gradient(180deg, #f8f5f0 0%, #ffffff 30%, #f3f0ea 100%); }
		#ebook-single-app { min-height: 100vh; }
	</style>
	<style id="almaden-ebook-overrides">
		html {
			margin-top: 0 !important;
		}
		main {
			padding-top: 20px !important;
			background-color: #f9fafb;
		}
	</style>
</head>
<body>
<main id="ebook-single-app" class="px-4 pb-8 md:px-8 lg:px-12">
	<div class="mx-auto max-w-7xl">
		<div class="mb-6 flex items-center justify-between gap-4">
			<div>
				<p class="text-xs uppercase tracking-[0.3em] text-neutral-500">Ebook Store</p>
				<h1 class="mt-2 text-3xl md:text-5xl font-semibold text-neutral-900"><?php echo esc_html( $book_title ); ?></h1>
			</div>
			<a href="<?php echo esc_url( $return_url ); ?>" class="rounded-full border border-neutral-200 bg-white px-5 py-2 text-sm font-semibold text-neutral-700 shadow-sm transition hover:bg-neutral-50">Volver</a>
		</div>

		<div class="grid gap-8 lg:grid-cols-[minmax(0,380px)_1fr]">
			<section class="rounded-[2rem] border border-neutral-200 bg-white/90 p-5 shadow-[0_25px_80px_rgba(0,0,0,0.06)]">
				<div class="overflow-hidden rounded-[1.5rem] border border-neutral-200 bg-neutral-50">
					<?php echo $cover_html ? $cover_html : '<div class="flex aspect-[3/4] items-center justify-center bg-gradient-to-br from-stone-200 to-stone-100 text-neutral-500">Sin portada</div>'; ?>
				</div>

				<div class="mt-5 space-y-4">
					<div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-neutral-500">
						<?php if ( ! empty( $book_category_names ) ) : ?>
							<?php foreach ( $book_category_names as $category_name ) : ?>
								<span class="rounded-full border border-neutral-200 px-3 py-1"><?php echo esc_html( $category_name ); ?></span>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<div>
						<p class="text-sm font-semibold uppercase tracking-[0.22em] text-neutral-500">Autor</p>
						<p class="mt-1 text-lg font-medium text-neutral-900"><?php echo esc_html( $author ? $author : 'Almaden Bookster' ); ?></p>
					</div>
					<div>
						<p class="text-sm font-semibold uppercase tracking-[0.22em] text-neutral-500">Descripción</p>
						<p class="mt-2 text-sm leading-7 text-neutral-700"><?php echo wp_kses_post( wpautop( $book_excerpt ) ); ?></p>
					</div>
				</div>
			</section>

			<section class="space-y-6">
				<div class="rounded-[2rem] border border-neutral-200 bg-white/90 p-6 shadow-[0_25px_80px_rgba(0,0,0,0.06)]">
					<div class="flex flex-wrap items-center justify-between gap-4">
						<div>
							<p class="text-sm font-semibold uppercase tracking-[0.22em] text-neutral-500">Capítulos</p>
							<h2 class="mt-1 text-2xl font-semibold text-neutral-900">Vista previa del ebook</h2>
						</div>
						<div class="rounded-full bg-neutral-100 px-4 py-2 text-sm font-medium text-neutral-600">
							<?php echo count( $chapters ); ?> capítulos
						</div>
					</div>

					<div class="mt-6 grid gap-3 sm:grid-cols-2">
						<?php if ( ! empty( $chapters ) ) : ?>
							<?php foreach ( $chapters as $index => $chapter ) : ?>
								<article class="rounded-2xl border border-dashed border-neutral-300 bg-neutral-50 p-4">
									<div class="flex items-start justify-between gap-3">
										<div>
											<p class="text-xs font-semibold uppercase tracking-[0.22em] text-neutral-400">Capítulo <?php echo esc_html( (string) ( $index + 1 ) ); ?></p>
											<h3 class="mt-1 text-base font-semibold text-neutral-900"><?php echo esc_html( $chapter['title'] ); ?></h3>
										</div>
										<span class="rounded-full bg-neutral-900 px-3 py-1 text-xs font-semibold text-white">Bloqueado</span>
									</div>
									<p class="mt-3 text-sm leading-6 text-neutral-600">Compra el ebook para desbloquear este capítulo y acceder al contenido completo, quizzes y progreso personal.</p>
								</article>
							<?php endforeach; ?>
						<?php else : ?>
							<div class="rounded-2xl border border-dashed border-neutral-300 bg-neutral-50 p-6 text-sm text-neutral-600 sm:col-span-2">
								Este ebook todavía no tiene capítulos publicados.
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="rounded-[2rem] border border-neutral-200 bg-[#161311] p-6 text-white shadow-[0_25px_80px_rgba(0,0,0,0.10)]">
					<p class="text-sm font-semibold uppercase tracking-[0.22em] text-white/60">Acceso</p>
					<h2 class="mt-2 text-2xl font-semibold">Compra el ebook para desbloquear todo el contenido</h2>
					<p class="mt-3 max-w-2xl text-sm leading-7 text-white/75">Al comprar, tendrás acceso a la lectura completa, quizzes asociados y tu página personal de resultados y progreso.</p>

					<div class="mt-5 flex flex-col gap-3">
						<label class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-white/80">
							<input id="ebook-terms-checkbox" type="checkbox" class="mt-1 h-4 w-4 rounded border-white/30 bg-transparent text-white focus:ring-white" />
							<span>Acepto los <a class="font-semibold underline decoration-white/40 underline-offset-4" href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener noreferrer">términos y condiciones</a> antes de continuar con la compra.</span>
						</label>

						<a id="ebook-buy-button" href="<?php echo esc_url( $purchase_url ); ?>" class="inline-flex items-center justify-center rounded-full bg-white px-6 py-3 text-sm font-semibold text-neutral-900 transition hover:scale-[1.01] hover:bg-neutral-100" aria-disabled="true" data-product-id="<?php echo esc_attr( $book_product_id ); ?>" data-terms-required="1">Comprar Ebook</a>
					</div>

					<p class="mt-4 text-xs leading-6 text-white/50">La compra se gestiona mediante WooCommerce. El botón se activará cuando aceptes los términos.</p>
				</div>
			</section>
		</div>
	</div>
</main>
<script>
(function () {
	const checkbox = document.getElementById('ebook-terms-checkbox');
	const button = document.getElementById('ebook-buy-button');
	if (!checkbox || !button) {
		return;
	}

	const purchaseUrl = button.getAttribute('href');
	const disabledStyles = ['pointer-events-none', 'opacity-50', 'cursor-not-allowed'];
	const enabledStyles = ['pointer-events-auto', 'opacity-100', 'cursor-pointer'];

	function syncButtonState() {
		const enabled = checkbox.checked;
		button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
		button.setAttribute('tabindex', enabled ? '0' : '-1');
		if (enabled) {
			button.setAttribute('href', purchaseUrl);
			button.classList.remove(...disabledStyles);
			button.classList.add(...enabledStyles);
		} else {
			button.removeAttribute('href');
			button.classList.remove(...enabledStyles);
			button.classList.add(...disabledStyles);
		}
	}

	checkbox.addEventListener('change', syncButtonState);
	syncButtonState();
})();
</script>
</body>
</html>
