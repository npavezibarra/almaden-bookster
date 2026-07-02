<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// We need the helper for cover thumbnails
require_once dirname( __FILE__ ) . '/../../includes/helpers/cover-thumbnail.php';

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
$catalog_categories = array();
if ( $published_books->have_posts() ) {
	while ( $published_books->have_posts() ) {
		$published_books->the_post();
		$terms = get_the_terms( get_the_ID(), 'category' );
		if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$catalog_categories[ $term->slug ] = $term->name;
			}
		}
	}
	wp_reset_postdata();
	$published_books->rewind_posts();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookCraft - <?php echo esc_html( almaden_bookster_get_store_title() ); ?></title>
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
    <link href="<?php echo esc_url( almaden_get_thumbnail_fonts_url() ); ?>" rel="stylesheet">
    <!-- Urbanist Font for UI -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons para UI -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: "Urbanist", sans-serif;
            background-color: #fcfcfc;
            color: #111;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1; 
        }
        ::-webkit-scrollbar-thumb {
            background: #d1d5db; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af; 
        }
        .urbanist-almaden-logo {
            font-family: "Urbanist", sans-serif;
            font-optical-sizing: auto;
            font-weight: 700;
            font-size: 34px !important;
            font-style: normal;
        }

		/* Scoped styles to avoid theme conflicts */
		.almaden-bookshelf-wrapper {
			max-width: 1200px;
			margin: 0 auto;
			padding: 2rem 1rem;
		}
		.almaden-bookshelf-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
			gap: 2rem;
		}
		.almaden-book-card {
			display: flex;
			flex-direction: column;
			align-items: stretch;
			background-color: #ffffff;
			padding: 0;
			border-radius: 1.5rem;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
			border: 1px solid #e5e7eb;
			cursor: pointer;
			transition: transform 0.3s ease, box-shadow 0.3s ease;
			text-decoration: none !important;
			overflow: hidden;
		}
		.almaden-book-card:hover {
			transform: translateY(-5px);
			box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
		}
		.almaden-book-cover-wrap {
			width: 100%;
			background-color: transparent;
			overflow: hidden;
			position: relative;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.almaden-book-copy {
			display: flex;
			flex-direction: column;
			gap: 0.6rem;
			padding: 1.2rem 1.2rem 1.35rem;
		}
		.almaden-book-meta {
			display: flex;
			flex-wrap: wrap;
			gap: 0.45rem;
		}
		.almaden-book-pill {
			display: inline-flex;
			align-items: center;
			border-radius: 999px;
			border: 1px solid #e5e7eb;
			background: #f9fafb;
			color: #6b7280;
			font-size: 0.7rem;
			font-weight: 700;
			letter-spacing: 0.04em;
			text-transform: uppercase;
			padding: 0.35rem 0.65rem;
		}
		.almaden-book-summary {
			color: #4b5563;
			font-size: 0.94rem;
			line-height: 1.65;
			margin: 0;
		}
		.almaden-book-cta {
			margin-top: auto;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 0.5rem;
			border-radius: 999px;
			background: #111827;
			color: #fff;
			font-size: 0.88rem;
			font-weight: 700;
			padding: 0.85rem 1rem;
			transition: background-color 0.2s ease, transform 0.2s ease;
		}
		.almaden-book-cta:hover {
			background: #000000;
			transform: translateY(-1px);
		}
		.almaden-catalog-filters {
			display: grid;
			grid-template-columns: 1fr;
			gap: 0.75rem;
			margin: 0 0 1.5rem;
		}
		.almaden-catalog-filters input,
		.almaden-catalog-filters select {
			width: 100%;
			border: 1px solid #d1d5db;
			border-radius: 999px;
			padding: 0.8rem 1rem;
			background: #fff;
		}
		.almaden-catalog-filters input:focus,
		.almaden-catalog-filters select:focus {
			outline: none;
			border-color: #111827;
			box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.08);
		}
		.cover-thumbnail-wrapper {
			width: 100%;
			background-color: #ffffff;
			overflow: hidden;
			position: relative;
			border-bottom: 1px solid #e2e8f0;
		}
		.cover-spread-container {
			position: absolute;
			top: 0;
			left: 0;
		}
		.cover-spread-container .absolute { position: absolute; }
		.cover-spread-container .inset-0 { top: 0; right: 0; bottom: 0; left: 0; }
		.cover-spread-container .top-0 { top: 0; }
		.cover-spread-container .bottom-0 { bottom: 0; }
		.cover-spread-container .bg-cover { background-size: cover; }
		.cover-spread-container .bg-center { background-position: center; }
		
		.almaden-book-cover-wrap svg {
			width: 64px;
			height: 64px;
			color: #cbd5e1;
		}
		.almaden-book-info {
			text-align: center;
			width: 100%;
		}
		.almaden-book-title {
			font-size: 1.125rem;
			font-weight: 700;
			color: #0f172a;
			margin: 0 0 0.25rem 0;
			line-height: 1.3;
		}
		.almaden-book-author {
			font-size: 0.875rem;
			font-weight: 500;
			color: #64748b;
			margin: 0;
		}
		.almaden-empty-state {
			text-align: center;
			padding: 4rem 2rem;
			background-color: #ffffff;
			border-radius: 1rem;
			border: 2px dashed #e2e8f0;
		}
		.almaden-empty-state svg {
			width: 64px;
			height: 64px;
			color: #cbd5e1;
			margin: 0 auto 1rem auto;
		}
		.almaden-empty-state h3 {
			font-size: 1.125rem;
			font-weight: 500;
			color: #0f172a;
			margin: 0 0 0.5rem 0;
		}
		.almaden-empty-state p {
			font-size: 0.875rem;
			color: #64748b;
			margin: 0;
		}
    </style>
    <?php wp_head(); ?>
</head>
<body class="min-h-screen flex flex-col theme-light">

    <!-- Top Navigation -->
    <nav class="border-b border-gray-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center text-black">
                        <span class="text-2xl tracking-tight urbanist-almaden-logo">almaden</span>
                    </div>
                    
                    <div class="hidden sm:ml-8 sm:flex sm:space-x-6 items-center">
                        <?php if ( current_user_can( 'almaden_manage_books' ) || current_user_can( 'manage_options' ) ) : ?>
                            <a href="<?php echo esc_url( almaden_bookster_get_creator_page_url() ); ?>" class="border-b-2 border-transparent text-gray-500 hover:text-black hover:border-gray-300 px-1 pt-1 text-sm font-medium h-full flex items-center transition-colors">
                                Taller
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( almaden_bookster_get_store_page_url() ); ?>" class="border-b-2 border-black text-black px-1 pt-1 text-sm font-medium h-full flex items-center">
                            <?php echo esc_html( almaden_bookster_get_store_title() ); ?>
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?php echo esc_url( admin_url() ); ?>" class="text-sm font-medium text-gray-500 hover:text-black transition-colors">Volver a WP</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 bg-gray-50 p-6 sm:p-8">
        <div class="max-w-7xl mx-auto almaden-bookshelf-wrapper" id="bookshelf-app-container">
            <div class="mb-6 rounded-[1.5rem] border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-2">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-400">Ebook Store</p>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Explora los ebooks publicados</h1>
                    <p class="text-sm md:text-base text-gray-600 max-w-3xl">Busca por título, autor o categoría y abre la ficha de cada ebook para ver su descripción y acceder a la compra.</p>
                </div>
                <div class="almaden-catalog-filters mt-5 md:grid-cols-[1fr_220px]">
                    <input type="search" id="almaden-catalog-search" placeholder="Buscar por título o autor...">
                    <select id="almaden-catalog-category">
                        <option value="">Todas las categorías</option>
                        <?php foreach ( $catalog_categories as $category_slug => $category_name ) : ?>
                            <option value="<?php echo esc_attr( $category_slug ); ?>"><?php echo esc_html( $category_name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php if ( $published_books->have_posts() ) : ?>
                <div class="almaden-bookshelf-grid">
                    <?php while ( $published_books->have_posts() ) : $published_books->the_post(); 
                        $cover_thumbnail_html = almaden_get_cover_thumbnail_html( get_the_ID() );
                        $author = get_post_meta( get_the_ID(), '_almaden_book_author', true );
                        $summary = get_the_excerpt();
                        if ( empty( $summary ) ) {
                            $summary = wp_trim_words( wp_strip_all_tags( get_the_content() ), 24 );
                        }
                        $terms = get_the_terms( get_the_ID(), 'category' );
                        $term_slugs = array();
                        $term_names = array();
                        if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
                            foreach ( $terms as $term ) {
                                $term_slugs[] = $term->slug;
                                $term_names[] = $term->name;
                            }
                        }
                    ?>
                        <a href="<?php echo esc_url( get_permalink() ); ?>" class="almaden-book-card" data-book-title="<?php echo esc_attr( strtolower( get_the_title() ) ); ?>" data-book-author="<?php echo esc_attr( strtolower( $author ) ); ?>" data-book-categories="<?php echo esc_attr( implode( ' ', $term_slugs ) ); ?>">
                            <div class="almaden-book-cover-wrap">
                                <?php if ( ! empty( $cover_thumbnail_html ) ) : ?>
                                    <?php echo str_replace('border-b', '', $cover_thumbnail_html); ?>
                                <?php else : ?>
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="almaden-book-copy">
                                <div class="almaden-book-meta">
                                    <?php if ( ! empty( $term_names ) ) : ?>
                                        <?php foreach ( array_slice( $term_names, 0, 2 ) as $term_name ) : ?>
                                            <span class="almaden-book-pill"><?php echo esc_html( $term_name ); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h2 class="almaden-book-title"><?php echo esc_html( get_the_title() ); ?></h2>
                                    <?php if ( ! empty( $author ) ) : ?>
                                        <p class="almaden-book-author"><?php echo esc_html( $author ); ?></p>
                                    <?php endif; ?>
                                </div>
                                <p class="almaden-book-summary"><?php echo esc_html( $summary ); ?></p>
                                <span class="almaden-book-cta">
                                    Ver ebook
                                    <i class="fa-solid fa-arrow-right text-[0.8em]"></i>
                                </span>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <div class="almaden-empty-state">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <h3>No hay ebooks publicados</h3>
                    <p>Los ebooks que marques como publicados aparecerán aquí.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Script para escalar portadas igual que en el dashboard -->
    <script>
        (function () {
            const searchInput = document.getElementById('almaden-catalog-search');
            const categorySelect = document.getElementById('almaden-catalog-category');
            const cards = Array.from(document.querySelectorAll('.almaden-book-card'));
            if (!searchInput || !categorySelect || !cards.length) return;

            function applyFilters() {
                const query = searchInput.value.trim().toLowerCase();
                const category = categorySelect.value.trim().toLowerCase();

                cards.forEach(card => {
                    const title = (card.dataset.bookTitle || '');
                    const author = (card.dataset.bookAuthor || '');
                    const categories = (card.dataset.bookCategories || '');
                    const matchesQuery = !query || title.includes(query) || author.includes(query);
                    const matchesCategory = !category || categories.includes(category);
                    card.style.display = (matchesQuery && matchesCategory) ? '' : 'none';
                });
            }

            searchInput.addEventListener('input', applyFilters);
            categorySelect.addEventListener('change', applyFilters);
        })();

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
