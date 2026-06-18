<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// We need the helper for cover thumbnails
require_once dirname( __FILE__ ) . '/../includes/cover-thumbnail.php';

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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookCraft - Bookshelf</title>
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
			align-items: center;
			background-color: transparent;
			padding: 0;
			border-radius: 0.5rem;
			box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
			border: none;
			cursor: pointer;
			transition: transform 0.3s ease, box-shadow 0.3s ease;
			text-decoration: none !important;
		}
		.almaden-book-card:hover {
			transform: translateY(-5px);
			box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
		}
		.almaden-book-cover-wrap {
			width: 100%;
			background-color: transparent;
			border-radius: 0.5rem;
			overflow: hidden;
			position: relative;
			display: flex;
			align-items: center;
			justify-content: center;
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
                        <a href="<?php echo esc_url( home_url('/almaden-booklist/') ); ?>" class="border-b-2 border-transparent text-gray-500 hover:text-black hover:border-gray-300 px-1 pt-1 text-sm font-medium h-full flex items-center transition-colors">
                            Taller
                        </a>
                        <a href="<?php echo esc_url( home_url('/bookshelf/') ); ?>" class="border-b-2 border-black text-black px-1 pt-1 text-sm font-medium h-full flex items-center">
                            Bookshelf
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
            <?php if ( $published_books->have_posts() ) : ?>
                <div class="almaden-bookshelf-grid">
                    <?php while ( $published_books->have_posts() ) : $published_books->the_post(); 
                        $cover_thumbnail_html = almaden_get_cover_thumbnail_html( get_the_ID() );
                        $author = get_post_meta( get_the_ID(), '_almaden_book_author', true );
                    ?>
                        <a href="<?php echo esc_url( get_permalink() ); ?>" class="almaden-book-card">
                            <div class="almaden-book-cover-wrap">
                                <?php if ( ! empty( $cover_thumbnail_html ) ) : ?>
                                    <?php echo str_replace('border-b', '', $cover_thumbnail_html); ?>
                                <?php else : ?>
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                <?php endif; ?>
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
                    <h3>No hay libros publicados</h3>
                    <p>Los libros que marques como "Publish ebook" aparecerán aquí.</p>
                </div>
            <?php endif; ?>
        </div>
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
