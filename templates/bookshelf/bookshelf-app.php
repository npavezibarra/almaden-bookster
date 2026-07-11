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
			padding: 1.5rem 1rem;
		}
		.almaden-bookshelf-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
			gap: 1.5rem;
			align-items: start;
		}
		.almaden-book-card {
			display: block;
			background-color: transparent;
			padding: 0;
			border-radius: 0;
			box-shadow: none;
			border: 0;
			cursor: pointer;
			transition: opacity 0.2s ease;
			text-decoration: none !important;
			overflow: visible;
		}
		.almaden-book-card:hover {
			opacity: 0.92;
		}
		.almaden-book-cover-wrap {
			width: 100%;
			background-color: transparent;
			overflow: hidden;
			position: relative;
			display: flex;
			align-items: flex-start;
			justify-content: flex-start;
			border-radius: 0;
		}
		.almaden-catalog-filters {
			display: grid;
			grid-template-columns: 1fr;
			gap: 0.5rem;
			margin: 0;
		}
		.almaden-catalog-filters input,
		.almaden-catalog-filters select {
			width: 100%;
			border: 1px solid #d1d5db;
			border-radius: 1rem;
			padding: 0.65rem 0.9rem;
			background: #fff;
			font-size: 0.95rem;
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
			border-bottom: 0;
			border-radius: 0 !important;
			box-shadow: none !important;
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
		@media (min-width: 768px) {
			.almaden-catalog-filters {
				grid-template-columns: minmax(0, 1fr) 180px;
			}
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
    <nav id="bookshelf-top-nav" class="border-b border-gray-200 bg-white">
        <div id="bookshelf-top-nav-inner" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div id="bookshelf-top-nav-row" class="flex justify-between h-16">
                <div id="bookshelf-top-nav-brand-group" class="flex items-center">
                    <div id="bookshelf-top-nav-brand" class="flex-shrink-0 flex items-center text-black">
                        <span id="bookshelf-top-nav-brand-text" class="text-2xl tracking-tight urbanist-almaden-logo">almaden</span>
                    </div>
                    
                    <div id="bookshelf-top-nav-links" class="hidden sm:ml-8 sm:flex sm:space-x-6 items-center">
                        <?php if ( current_user_can( 'almaden_manage_books' ) || current_user_can( 'manage_options' ) ) : ?>
                            <a id="bookshelf-link-creator" href="<?php echo esc_url( almaden_bookster_get_creator_page_url() ); ?>" class="border-b-2 border-transparent text-gray-500 hover:text-black hover:border-gray-300 px-1 pt-1 text-sm font-medium h-full flex items-center transition-colors">
                                Taller
                            </a>
                        <?php endif; ?>
                        <a id="bookshelf-link-store" href="<?php echo esc_url( almaden_bookster_get_store_page_url() ); ?>" class="border-b-2 border-black text-black px-1 pt-1 text-sm font-medium h-full flex items-center">
                            <?php echo esc_html( almaden_bookster_get_store_title() ); ?>
                        </a>
                    </div>
                </div>
                <div id="bookshelf-top-nav-actions" class="flex items-center space-x-4">
                    <a id="bookshelf-link-admin" href="<?php echo esc_url( admin_url() ); ?>" class="text-sm font-medium text-gray-500 hover:text-black transition-colors">Volver a WP</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main id="bookshelf-main" class="flex-1 bg-gray-50 p-6 sm:p-8">
        <div class="max-w-7xl mx-auto almaden-bookshelf-wrapper" id="bookshelf-app-container">
            <div id="bookshelf-catalog-header" class="mb-4">
                <div id="bookshelf-catalog-header-copy" class="flex flex-col gap-2">
                    <p id="bookshelf-catalog-eyebrow" class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-400">Ebook Store</p>
                    <h1 id="bookshelf-catalog-title" class="text-xl md:text-2xl font-bold text-gray-900">Explora los ebooks publicados</h1>
                    <p id="bookshelf-catalog-description" class="text-sm text-gray-600 max-w-2xl">Busca por título, autor o categoría.</p>
                </div>
                <div id="bookshelf-catalog-filters" class="almaden-catalog-filters mt-3">
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
                <div id="bookshelf-grid" class="almaden-bookshelf-grid">
                    <?php while ( $published_books->have_posts() ) : $published_books->the_post(); 
                        $cover_thumbnail_html = almaden_get_cover_thumbnail_html( get_the_ID() );
                        $author = get_post_meta( get_the_ID(), '_almaden_book_author', true );
                        $terms = get_the_terms( get_the_ID(), 'category' );
                        $term_slugs = array();
                        if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
                            foreach ( $terms as $term ) {
                                $term_slugs[] = $term->slug;
                            }
                        }
                    ?>
                        <a id="bookshelf-book-<?php echo esc_attr( get_the_ID() ); ?>" href="<?php echo esc_url( get_permalink() ); ?>" class="almaden-book-card" aria-label="<?php echo esc_attr( get_the_title() ); ?>" data-book-id="<?php echo esc_attr( get_the_ID() ); ?>" data-book-title="<?php echo esc_attr( strtolower( get_the_title() ) ); ?>" data-book-author="<?php echo esc_attr( strtolower( $author ) ); ?>" data-book-categories="<?php echo esc_attr( implode( ' ', $term_slugs ) ); ?>">
                            <div id="bookshelf-book-cover-<?php echo esc_attr( get_the_ID() ); ?>" class="almaden-book-cover-wrap">
                                <?php if ( ! empty( $cover_thumbnail_html ) ) : ?>
                                    <?php echo str_replace('border-b', '', $cover_thumbnail_html); ?>
                                <?php else : ?>
                                    <svg id="bookshelf-book-cover-fallback-<?php echo esc_attr( get_the_ID() ); ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <div id="bookshelf-empty-state" class="almaden-empty-state">
                    <svg id="bookshelf-empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <h3 id="bookshelf-empty-state-title">No hay ebooks publicados</h3>
                    <p id="bookshelf-empty-state-description">Los ebooks que marques como publicados aparecerán aquí.</p>
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
