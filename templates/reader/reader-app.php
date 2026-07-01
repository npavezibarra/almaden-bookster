<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the current book
$book_id = get_the_ID();
$book_title = get_the_title();
$author = get_post_meta( $book_id, '_almaden_book_author', true );

$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
if ( empty( $source_book_id ) ) {
	$source_book_id = $book_id;
}

// Get the cover HTML
require_once dirname( __FILE__ ) . '/../../includes/helpers/cover-thumbnail.php';
$cover_html = almaden_get_cover_thumbnail_html( $book_id );
$fonts_url = almaden_get_thumbnail_fonts_url();

// Fetch chapters
$chapters_query = new WP_Query( array(
	'post_type'      => 'book_chapter',
	'post_parent'    => $source_book_id,
	'posts_per_page' => -1,
	'orderby'        => 'menu_order',
	'order'          => 'ASC',
) );

$chapters = array();
$page_counter = 1;
if ( $chapters_query->have_posts() ) {
	while ( $chapters_query->have_posts() ) {
		$chapters_query->the_post();
		$chapters[] = array(
			'id'         => get_the_ID(),
			'title'      => get_the_title(),
			'content'    => get_the_content(),
			'page'       => $page_counter,
			'hide_title' => get_post_meta( get_the_ID(), '_hide_title', true ),
			'is_toc'     => get_post_meta( get_the_ID(), '_is_toc', true ),
			'is_credits' => get_post_meta( get_the_ID(), '_is_credits', true ),
			'credits_hide_page_number' => get_post_meta( get_the_ID(), '_credits_hide_page_number', true ),
			'exclude_from_numbering' => get_post_meta( get_the_ID(), '_exclude_from_numbering', true ),
			'subtitle_text'            => get_post_meta( get_the_ID(), '_subtitle_text', true ),
			'subtitle_font_family'     => get_post_meta( get_the_ID(), '_subtitle_font_family', true ),
			'subtitle_align'           => get_post_meta( get_the_ID(), '_subtitle_align', true ),
			'subtitle_font_size'       => get_post_meta( get_the_ID(), '_subtitle_font_size', true ),
			'subtitle_letter_spacing'  => get_post_meta( get_the_ID(), '_subtitle_letter_spacing', true ),
			'subtitle_font_style'      => get_post_meta( get_the_ID(), '_subtitle_font_style', true ),
			'subtitle_text_transform'  => get_post_meta( get_the_ID(), '_subtitle_text_transform', true ),
			'subtitle_font_weight'     => get_post_meta( get_the_ID(), '_subtitle_font_weight', true ),
			'subtitle_margin_top'      => get_post_meta( get_the_ID(), '_subtitle_margin_top', true ),
			'subtitle_margin_bottom'   => get_post_meta( get_the_ID(), '_subtitle_margin_bottom', true ),
			'quiz_id'                  => function_exists( 'almaden_bookster_learni_get_quiz_id_for_chapter' ) ? (int) almaden_bookster_learni_get_quiz_id_for_chapter( get_the_ID() ) : 0,
		);
		$page_counter++;
	}
	wp_reset_postdata();
}

// Fetch global book settings
$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
$settings_book_id = $book_id;
$book_settings = function_exists('almaden_get_book_pdf_settings') ? almaden_get_book_pdf_settings( $settings_book_id ) : array();


$cover_settings = get_post_meta( $settings_book_id, '_almaden_cover_settings', true );
$fallback_cover_url = '';
if ( is_array( $cover_settings ) && !empty( $cover_settings['front_image'] ) ) {
    $fallback_cover_url = $cover_settings['front_image'];
} elseif ( is_array( $cover_settings ) && !empty( $cover_settings['spread_image'] ) ) {
    $fallback_cover_url = $cover_settings['spread_image'];
}

$has_reader_access = function_exists( 'almaden_bookster_user_can_access_book' ) ? almaden_bookster_user_can_access_book( $book_id ) : is_user_logged_in();
$book_product_id = function_exists( 'almaden_bookster_get_book_product_id' ) ? almaden_bookster_get_book_product_id( $book_id ) : 0;
$purchase_url = function_exists( 'almaden_bookster_get_book_purchase_url' ) ? almaden_bookster_get_book_purchase_url( $book_id ) : home_url( '/' );
$book_highlights = array();
if ( $has_reader_access && is_user_logged_in() && function_exists( 'almaden_bookster_get_user_book_highlights' ) ) {
	$book_highlights = almaden_bookster_get_user_book_highlights( $book_id, get_current_user_id() );
}

$approved_quizzes = array();
if ( is_user_logged_in() ) {
	$approved_quizzes = get_user_meta( get_current_user_id(), '_almaden_passed_quizzes', true );
	if ( ! is_array( $approved_quizzes ) ) {
		$approved_quizzes = array();
	}
}

// Get the layout wide size from WordPress
$wide_size = '1300px';
if ( function_exists( 'wp_get_global_settings' ) ) {
    $wide_size_val = wp_get_global_settings( array( 'layout', 'wideSize' ) );
    if ( ! empty( $wide_size_val ) ) {
        $wide_size = $wide_size_val;
    }
}

// Encode to JSON for frontend
$book_data_json = wp_json_encode( array(
	'bookId' => $book_id,
	'title'    => $book_title,
	'author'   => $author,
	'settings' => $book_settings,
	'chapters' => $chapters,
	'cover_url' => $fallback_cover_url,
	'userCanAccess' => $has_reader_access,
	'productId' => $book_product_id,
	'purchaseUrl' => $purchase_url,
	'highlights' => $book_highlights,
	'quizFlowSettings' => function_exists( 'almaden_bookster_learni_get_quiz_flow_settings' ) ? almaden_bookster_learni_get_quiz_flow_settings( $book_id ) : array(),
	'approvedQuizzes' => $approved_quizzes,
) );
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $book_title ); ?> - Reader</title>
    
    <!-- Tailwind Config -->
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
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts for Cover -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?php echo esc_url($fonts_url); ?>" rel="stylesheet">
    <!-- Urbanist Font for UI -->
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&amp;display=swap" rel="stylesheet">
    <!-- Markdown Parser -->
    <script src="https://cdn.jsdelivr.net/npm/markdown-it@13.0.1/dist/markdown-it.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/markdown-it-footnote@3.0.3/dist/markdown-it-footnote.min.js"></script>

    <link rel="stylesheet" href="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/reader-app.css' ); ?>?v=<?php echo filemtime( dirname( __FILE__ ) . '/../../assets/css/reader-app.css' ); ?>">
    <link rel="stylesheet" href="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/css/quiz-builder/quiz-builder-app.css' ); ?>?v=<?php echo filemtime( dirname( __FILE__ ) . '/../../assets/css/quiz-builder/quiz-builder-app.css' ); ?>">
    <style>
        
        /* User Requested Constraints */
        div#view-index,
        header#chapter-navbar,
        main#chapter-scroll-area {
            max-width: <?php echo esc_attr( $wide_size ); ?>;
            margin: auto;
        }
    </style>
</head>
<body>
    <script>
        const bookData = <?php echo $book_data_json; ?>;
        const almadenAjaxUrl = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
        const almadenReaderHighlightNonce = "<?php echo esc_js( wp_create_nonce( 'almaden_book_highlight_' . $book_id ) ); ?>";
        window.almadenAjaxUrl = almadenAjaxUrl;
        window.almadenReaderHighlightNonce = almadenReaderHighlightNonce;
        const userDBPrefs = <?php
            if ( is_user_logged_in() ) {
                $prefs = get_user_meta( get_current_user_id(), 'almaden_bookster_reader_prefs', true );
                echo is_array($prefs) ? json_encode($prefs) : 'null';
            } else {
                echo 'null';
            }
        ?>;
    </script>

    <?php if ( $has_reader_access ) : ?>
    <!-- STATE: INDEX -->
    <div id="view-index" class="w-full h-full flex flex-col md:flex-row">
        <!-- Left Side: Cover -->
        <div id="reader-cover-panel" class="w-full md:w-1/2 h-1/2 md:h-full flex items-center md:items-start justify-center p-8 md:p-16 lg:p-24 border-b md:border-b-0 md:border-r border-gray-200">
            <div id="reader-cover-wrapper" class="w-full max-w-sm" style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
                <?php echo $cover_html; ?>
            </div>
        </div>
        
        <!-- Right Side: Chapters List -->
        <div id="reader-index-panel" class="w-full md:w-1/2 h-1/2 md:h-full overflow-y-auto p-8 md:p-16 lg:p-24 relative">
            <div id="reader-index-header" class="flex justify-between items-center mb-12">
                <h2 id="reader-book-title" class="text-2xl md:text-3xl font-bold text-gray-900"><?php echo esc_html( $book_title ); ?></h2>
                <a id="reader-btn-back" href="<?php echo esc_url( home_url( '/bookshelf/' ) ); ?>" class="px-5 py-2 bg-transparent border border-gray-200 hover:bg-black/5 rounded-full text-sm font-semibold text-gray-700 shadow-sm transition-colors">Volver</a>
            </div>

            <div class="space-y-1" id="chapters-list">
                <!-- Chapters will be rendered here via JS -->
            </div>
        </div>
    </div>

    <!-- STATE: CHAPTER -->
    <div id="view-chapter" class="w-full h-full flex flex-col hidden">
        <!-- Sticky Top Navigation -->
        <header id="chapter-navbar" class="w-full h-16 border-b border-gray-100 flex items-center justify-between px-6 backdrop-blur sticky top-0 z-50 transition-colors" style="background-color: inherit;">
            <button onclick="showIndexView()" class="flex items-center text-gray-500 hover:text-black transition-colors font-medium" title="Índice">
                <i class="fa-solid fa-list-ul mr-2 text-sm"></i>
            </button>
            <h3 class="text-sm font-semibold text-gray-800 truncate px-4 absolute left-1/2 transform -translate-x-1/2 opacity-0 transition-opacity duration-300" id="chapter-nav-title">Chapter Title</h3>
            <div class="flex items-center space-x-1 relative">
                <!-- Preferences Button & Panel -->
                <button id="btn-reader-prefs" onclick="togglePrefsPanel()" class="p-2 text-gray-800 hover:bg-gray-100 rounded text-base h-9 flex items-center justify-center transition-colors font-serif font-bold mr-2" title="Preferencias de Lectura">
                    aA
                </button>
                <button id="btn-reader-highlights" onclick="toggleReaderHighlightsPanel()" class="p-2 text-gray-800 hover:bg-gray-100 rounded text-sm w-9 h-9 flex items-center justify-center transition-colors mr-2" title="Mis highlights">
                    <i class="fa-solid fa-bookmark"></i>
                </button>
                <div id="reader-prefs-panel" class="absolute right-0 top-full mt-2 w-64 bg-white border border-gray-200 shadow-xl rounded-lg p-4 hidden flex-col gap-4 z-50">
                    <div class="flex justify-between items-center bg-gray-100 rounded p-1">
                        <button onclick="changeFontSize(-1)" class="flex-1 py-1 text-center hover:bg-white hover:shadow-sm rounded transition-all text-sm">A-</button>
                        <div class="w-px h-4 bg-gray-300"></div>
                        <button onclick="changeFontSize(1)" class="flex-1 py-1 text-center hover:bg-white hover:shadow-sm rounded transition-all text-lg font-medium">A+</button>
                    </div>

                    <div class="flex justify-between items-center bg-gray-100 rounded p-1 text-sm text-gray-600">
                        <button onclick="changeLineHeight(-0.1)" class="flex-1 py-1 text-center hover:bg-white hover:shadow-sm rounded transition-all" title="Reducir interlineado"><i class="fa-solid fa-compress"></i></button>
                        <div class="w-px h-4 bg-gray-300"></div>
                        <button onclick="changeLineHeight(0.1)" class="flex-1 py-1 text-center hover:bg-white hover:shadow-sm rounded transition-all" title="Aumentar interlineado"><i class="fa-solid fa-expand"></i></button>
                    </div>
                    <div class="flex justify-around items-center pt-2 border-t border-gray-100">
                        <button onclick="changeTheme('')" class="w-8 h-8 rounded-full border border-gray-300 bg-transparent flex items-center justify-center ring-offset-2 hover:ring-2 ring-gray-300 transition-all" title="Colores Originales del Libro">
                            <i class="fa-solid fa-rotate-left text-xs text-gray-500"></i>
                        </button>
                        <button onclick="changeTheme('white')" class="w-8 h-8 rounded-full border border-gray-300 bg-white ring-offset-2 hover:ring-2 ring-gray-300 transition-all" title="Tema Blanco"></button>
                        <button onclick="changeTheme('beige')" class="w-8 h-8 rounded-full border border-gray-300 bg-[#F4F3EB] ring-offset-2 hover:ring-2 ring-gray-300 transition-all" title="Tema Sepia"></button>
                        <button onclick="changeTheme('black')" class="w-8 h-8 rounded-full border border-gray-600 bg-gray-900 ring-offset-2 hover:ring-2 ring-gray-300 transition-all" title="Tema Oscuro"></button>
                    </div>
                </div>

                <div class="w-px h-5 bg-gray-200 mx-1"></div>

                <button onclick="toggleReadingMode('scroll')" id="btn-mode-scroll" class="p-2 text-gray-800 bg-gray-100 rounded text-sm w-9 h-9 flex items-center justify-center transition-colors" title="Scroll View">
                    <i class="fa-solid fa-arrows-up-down"></i>
                </button>
                <button onclick="toggleReadingMode('flip')" id="btn-mode-flip" class="p-2 text-gray-400 hover:text-gray-800 rounded text-sm w-9 h-9 flex items-center justify-center transition-colors" title="Two-Page Flip View">
                    <i class="fa-solid fa-book-open"></i>
                </button>
            </div>
            
            <!-- Reading Progress Bar -->
            <div id="reading-progress-container" class="absolute bottom-0 left-0 w-full h-[3px] bg-transparent overflow-hidden">
                <div id="reading-progress-bar" class="h-full w-0 transition-all duration-100 ease-out"></div>
            </div>
        </header>

        <!-- Chapter Content Area -->
        <main class="flex-1 overflow-y-auto p-6 md:p-12 relative" id="chapter-scroll-area">
            <!-- Flip Controls -->
            <button id="btn-flip-prev" onclick="flipPrev()" class="absolute left-0 top-1/2 transform -translate-y-1/2 p-4 text-gray-300 hover:text-black hidden z-10 text-3xl transition-colors">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button id="btn-flip-next" onclick="flipNext()" class="absolute right-0 top-1/2 transform -translate-y-1/2 p-4 text-gray-300 hover:text-black hidden z-10 text-3xl transition-colors">
                <i class="fa-solid fa-chevron-right"></i>
            </button>
            <div id="chapter-content" class="prose">
                <!-- Markdown content will be rendered here -->
            </div>
            
            <div id="chapter-footer-nav" class="max-w-[700px] mx-auto mt-20 pt-8 pb-12 border-t border-gray-100 flex justify-between">
                <button id="btn-prev-chapter" onclick="goToPrevChapter()" class="text-gray-500 hover:text-black flex items-center hidden font-medium transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Anterior
                </button>
                <button id="btn-next-chapter" onclick="goToNextChapter()" class="text-gray-500 hover:text-black flex items-center ml-auto hidden font-medium transition-colors">
                    Siguiente <i class="fa-solid fa-arrow-right ml-2"></i>
                </button>
            </div>
        </main>
    </div>

    <!-- Highlights Drawer -->
    <div id="reader-highlights-backdrop" class="fixed inset-0 bg-black/20 hidden z-40"></div>
    <aside id="reader-highlights-panel" class="fixed right-4 top-20 w-[min(92vw,28rem)] max-h-[calc(100vh-6rem)] bg-white border border-gray-200 shadow-2xl rounded-3xl overflow-hidden hidden z-50 flex flex-col">
        <div class="flex items-start justify-between gap-4 p-5 border-b border-gray-100">
            <div>
                <h4 class="text-lg font-semibold text-gray-900">Mis highlights</h4>
            </div>
            <button id="btn-close-reader-highlights" class="w-9 h-9 rounded-full border border-gray-200 text-gray-500 hover:text-black hover:bg-gray-50 transition-colors" title="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="reader-highlights-list" class="p-4 overflow-y-auto space-y-3">
            <div class="text-sm text-gray-400 italic px-1 py-2">Abre el panel para ver tus highlights guardados.</div>
        </div>
    </aside>

    <!-- Footnote Popup -->
    <div id="footnote-popup" class="fixed hidden z-50 bg-white border border-gray-200 shadow-xl rounded-lg p-4 text-sm text-gray-800 max-w-xs md:max-w-sm font-sans prose prose-sm transition-opacity duration-200 opacity-0 pointer-events-none" style="transform: translate(-50%, -100%); margin-top: -10px;">
        <div id="footnote-popup-content"></div>
        <div class="absolute w-3 h-3 bg-white border-b border-r border-gray-200 transform rotate-45 left-1/2 -ml-1.5 -bottom-1.5"></div>
    </div>

    <!-- Highlight Toolbar -->
    <div id="highlight-toolbar" class="fixed hidden z-50 bg-white border border-gray-200 shadow-lg rounded-full px-3 py-2 items-center gap-2 text-sm">
        <button id="btn-save-highlight" class="w-10 h-10 rounded-full bg-yellow-300 hover:bg-yellow-400 text-gray-900 font-semibold transition-colors flex items-center justify-center shadow-sm" title="Resaltar" aria-label="Resaltar">
            <span class="w-4 h-4 rounded-full bg-yellow-500 border border-yellow-600 inline-block"></span>
        </button>
        <button id="btn-open-comment-highlight" class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium transition-colors flex items-center justify-center" title="Comentar aquí" aria-label="Comentar aquí">
            <i class="fa-solid fa-comment-dots text-sm"></i>
        </button>
        <button id="btn-cancel-highlight" class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium transition-colors flex items-center justify-center" title="Cancelar" aria-label="Cancelar">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>
    </div>

    <!-- Highlight Comment Composer -->
    <div id="highlight-comment-composer" class="fixed hidden z-50 w-[min(92vw,26rem)] bg-white border border-gray-200 shadow-2xl rounded-3xl p-4">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-400">Comentar aquí</p>
                <p class="text-sm text-gray-500 mt-1">Tu comentario también guardará el highlight.</p>
            </div>
            <button id="btn-close-comment-composer" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center transition-colors" title="Cerrar">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        <textarea id="highlight-comment-input" rows="4" class="w-full resize-y rounded-2xl border border-gray-200 px-4 py-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-yellow-300" placeholder="Escribe tu comentario..."></textarea>
        <div class="mt-3 flex justify-end gap-2">
            <button id="btn-cancel-comment-composer" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm transition-colors">Cancelar</button>
            <button id="btn-save-comment-composer" class="px-4 py-2 rounded-full bg-black hover:bg-gray-800 text-white font-semibold text-sm transition-colors">Guardar comentario</button>
        </div>
    </div>

    <!-- Real Quiz Player Overlay -->
    <div id="almaden-quiz-player-overlay" class="almaden-quiz-overlay" style="display: none; z-index: 9999;">
        <div class="almaden-quiz-overlay-backdrop" id="almaden-quiz-player-close-backdrop"></div>
        <div class="almaden-quiz-overlay-panel">
            <div class="almaden-quiz-overlay-head">
                <h3 class="almaden-quiz-overlay-title" id="almaden-quiz-player-title">Evaluación de Lectura</h3>
                <button type="button" class="almaden-quiz-overlay-close" id="almaden-quiz-player-close-btn">&times;</button>
            </div>
            <div class="almaden-quiz-overlay-body learni-learner" id="almaden-quiz-player-body">
            </div>
        </div>
    </div>

    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/reader/reader-prefs.js' ); ?>?v=<?php echo filemtime( dirname( __FILE__ ) . '/../../assets/js/reader/reader-prefs.js' ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/reader/reader-styles.js' ); ?>?v=<?php echo filemtime( dirname( __FILE__ ) . '/../../assets/js/reader/reader-styles.js' ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/almaden-shortcodes.js' ); ?>?v=<?php echo filemtime( dirname( __FILE__ ) . '/../../assets/js/almaden-shortcodes.js' ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/reader/reader-highlights.js' ); ?>?v=<?php echo filemtime( dirname( __FILE__ ) . '/../../assets/js/reader/reader-highlights.js' ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/reader/reader-navigation.js' ); ?>?v=<?php echo filemtime( dirname( __FILE__ ) . '/../../assets/js/reader/reader-navigation.js' ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/reader/reader-quizzes.js' ); ?>?v=<?php echo filemtime( dirname( __FILE__ ) . '/../../assets/js/reader/reader-quizzes.js' ); ?>"></script>
    <script src="<?php echo esc_url( plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'assets/js/reader/reader-app.js' ); ?>?v=<?php echo filemtime( dirname( __FILE__ ) . '/../../assets/js/reader/reader-app.js' ); ?>"></script>
    <?php else : ?>
        <div class="min-h-screen flex items-center justify-center bg-neutral-50 px-6 py-12">
            <div class="w-full max-w-xl rounded-3xl border border-gray-200 bg-white p-8 shadow-[0_30px_80px_-30px_rgba(0,0,0,0.25)]">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-400 mb-3">Acceso restringido</p>
                <h1 class="text-3xl font-bold text-gray-900 mb-4"><?php echo esc_html( $book_title ); ?></h1>
                <p class="text-gray-600 leading-relaxed mb-8">
                    Debes comprar este ebook para leerlo y guardar highlights en tu cuenta.
                </p>
                <a href="<?php echo esc_url( $purchase_url ); ?>" class="inline-flex items-center rounded-full bg-black px-5 py-3 text-white font-semibold hover:bg-gray-800 transition-colors">
                    Ir a comprar
                </a>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
