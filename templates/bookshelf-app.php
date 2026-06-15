<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure Tailwind is loaded. We can use the same Tailwind output as the editor or a CDN for simplicity if it's a standalone frontend.
// Since the plugin uses its own build, we'll enqueue it.
wp_enqueue_style( 'almaden-bookster-tailwind', plugins_url( '../assets/css/output.css', __FILE__ ), array(), '1.0.0' );

// Query published books
$args = array(
	'post_type'      => 'almaden-books',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'meta_query'     => array(
		array(
			'key'   => '_almaden_is_published',
			'value' => '1',
		),
	),
);
$published_books = new WP_Query( $args );

// We also need the helper for cover thumbnails
require_once plugin_dir_path( __FILE__ ) . '../includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bookshelf</title>
	<?php wp_head(); ?>
	<style>
		body {
			background-color: #f8fafc; /* slate-50 */
		}
		.bookshelf-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
			gap: 2rem;
		}
		.book-cover-container {
			transition: transform 0.3s ease, box-shadow 0.3s ease;
		}
		.book-cover-container:hover {
			transform: translateY(-5px);
			box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
		}
	</style>
</head>
<body class="font-sans antialiased text-slate-800">

	<header class="bg-white border-b border-slate-200 sticky top-0 z-50">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
			<div class="flex justify-between h-16 items-center">
				<div class="flex items-center gap-3">
					<svg class="w-8 h-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
					</svg>
					<h1 class="text-2xl font-bold tracking-tight">Bookshelf</h1>
				</div>
			</div>
		</div>
	</header>

	<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
		<div class="mb-10 text-center">
			<h2 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">Catálogo de Publicaciones</h2>
			<p class="mt-4 max-w-2xl text-xl text-slate-500 mx-auto">Explora nuestra colección de libros editoriales, maquetados y publicados profesionalmente.</p>
		</div>

		<?php if ( $published_books->have_posts() ) : ?>
			<div class="bookshelf-grid">
				<?php while ( $published_books->have_posts() ) : $published_books->the_post(); 
					$cover_thumbnail_html = almaden_get_cover_thumbnail_html( get_the_ID() );
					$author = get_post_meta( get_the_ID(), '_almaden_book_author', true );
				?>
					<div class="flex flex-col items-center group cursor-pointer book-cover-container bg-white p-4 rounded-xl shadow-sm border border-slate-200">
						<div class="w-full flex justify-center bg-slate-100 rounded-lg overflow-hidden mb-4 relative" style="min-height: 350px;">
							<?php if ( ! empty( $cover_thumbnail_html ) ) : ?>
								<div class="w-full h-full flex items-center justify-center">
									<?php echo str_replace('border-b', '', $cover_thumbnail_html); ?>
								</div>
							<?php else : ?>
								<div class="w-full h-full flex items-center justify-center text-slate-300">
									<svg class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
									</svg>
								</div>
							<?php endif; ?>
							
							<!-- Overlay hover effect -->
							<div class="absolute inset-0 bg-indigo-900 bg-opacity-0 group-hover:bg-opacity-10 transition-all duration-300"></div>
						</div>
						
						<div class="w-full text-center px-2">
							<h3 class="text-lg font-bold text-slate-900 mb-1 line-clamp-2 leading-tight"><?php the_title(); ?></h3>
							<?php if ( $author ) : ?>
								<p class="text-sm font-medium text-slate-500 line-clamp-1"><?php echo esc_html( $author ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				<?php endwhile; ?>
			</div>
			<?php wp_reset_postdata(); ?>
		<?php else : ?>
			<div class="text-center py-24 bg-white rounded-2xl border border-slate-200 border-dashed">
				<svg class="mx-auto h-16 w-16 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
				</svg>
				<h3 class="mt-4 text-lg font-medium text-slate-900">No hay libros publicados</h3>
				<p class="mt-2 text-sm text-slate-500">Los libros que marques como "Publish ebook" aparecerán aquí.</p>
			</div>
		<?php endif; ?>
	</main>

	<!-- Script para escalar portadas igual que en el dashboard -->
	<script>
		function scaleThumbnails() {
			document.querySelectorAll('.cover-thumbnail-wrapper').forEach(wrapper => {
				const targetWidth = wrapper.clientWidth;
				const frontCoverPx = parseFloat(wrapper.getAttribute('data-front-cover-px'));
				const startPx = parseFloat(wrapper.getAttribute('data-start-px'));
				if (frontCoverPx > 0) {
					const scale = targetWidth / frontCoverPx;
					const spread = wrapper.querySelector('.cover-spread-container');
					if (spread) {
						spread.style.transform = `scale(${scale}) translateX(${-startPx}px)`;
					}
				}
			});
		}
		window.addEventListener('resize', scaleThumbnails);
		scaleThumbnails();
		window.addEventListener('load', scaleThumbnails);
	</script>
	<?php wp_footer(); ?>
</body>
</html>
