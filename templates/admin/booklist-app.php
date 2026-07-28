<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( __FILE__ ) . '/../../includes/helpers/cover-thumbnail.php';

// Fetch all books
$args = array(
    'post_type'      => 'almaden-books',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
);
$books_query = new WP_Query( $args );

// Check if a book was just created or deleted
$book_created = isset( $_GET['book_created'] ) && $_GET['book_created'] == '1';
$book_deleted = isset( $_GET['book_deleted'] ) && $_GET['book_deleted'] == '1';
$book_imported = isset( $_GET['book_imported'] ) && $_GET['book_imported'] == '1';
$book_imported_error = isset( $_GET['book_imported_error'] ) ? sanitize_text_field( $_GET['book_imported_error'] ) : '';
$publisher_created = isset( $_GET['publisher_created'] ) && $_GET['publisher_created'] == '1';
$publisher_tour_completed = isset( $_GET['publisher_tour_completed'] ) && $_GET['publisher_tour_completed'] == '1';
$show_publisher_tour = function_exists( 'almaden_bookster_should_show_publisher_tour' ) ? almaden_bookster_should_show_publisher_tour() : false;

$current_user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
$is_logged_in = function_exists( 'is_user_logged_in' ) ? is_user_logged_in() : false;
$current_user_name = '';
$current_user_avatar = '';
$logout_url = '';
$nav_items = array(
	array(
		'key'   => 'creator',
		'label' => 'Taller',
		'url'   => function_exists( 'almaden_bookster_get_creator_page_url' ) ? almaden_bookster_get_creator_page_url() : home_url( '/' ),
	),
	array(
		'key'   => 'authors',
		'label' => function_exists( 'almaden_bookster_get_authors_title' ) ? almaden_bookster_get_authors_title() : 'Autores',
		'url'   => function_exists( 'almaden_bookster_get_authors_page_url' ) ? almaden_bookster_get_authors_page_url() : home_url( '/' ),
	),
	array(
		'key'   => 'store',
		'label' => function_exists( 'almaden_bookster_get_store_title' ) ? almaden_bookster_get_store_title() : 'Ebook Store',
		'url'   => function_exists( 'almaden_bookster_get_store_page_url' ) ? almaden_bookster_get_store_page_url() : home_url( '/' ),
	),
);

if ( $is_logged_in && $current_user ) {
	$current_user_name = trim( (string) ( $current_user->display_name ?? '' ) );
	if ( '' === $current_user_name ) {
		$current_user_name = trim( (string) ( $current_user->user_login ?? '' ) );
	}

	if ( function_exists( 'get_avatar' ) ) {
		$current_user_avatar = get_avatar( (int) $current_user->ID, 32, '', $current_user_name, array( 'class' => 'h-8 w-8 rounded-full object-cover' ) );
	}

	$current_request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$logout_redirect = home_url( $current_request_uri );
	$logout_url = function_exists( 'wp_logout_url' ) ? wp_logout_url( $logout_redirect ) : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookCraft - Taller</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&amp;display=swap" rel="stylesheet">
    <!-- Font Awesome Icons para UI -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo esc_url( plugins_url( '../../assets/css/editor-style.css?v=' . time(), __FILE__ ) ); ?>">
    <script>
        var ajaxurl = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
        let bookState = {
            bookId: 0,
            settings: {},
            settingsNonce: '',
            ajaxUrl: ajaxurl,
            installedFonts: <?php echo json_encode( function_exists( 'almaden_bookster_get_installed_fonts_list' ) ? almaden_bookster_get_installed_fonts_list() : array() ); ?>,
            coverSettings: {}
        };

        // Fallback for missing editor functions
        function showToast(message, iconClass) {
            alert(message);
        }
    </script>
    <style>
        html {
            margin-top: 0 !important;
        }
        body {
            font-family: "Urbanist", sans-serif;
            background-color: #f5f5f5;
            color: #111;
        }
        h1, h2, h3, .serif {
            font-family: inherit;
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
        .book-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .book-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        /* Glassmorphism modal */
        .glass-modal {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translate3d(0, -10px, 0); }
            to { opacity: 1; transform: translate3d(0, 0, 0); }
        }
        .animate-fade-in-down { animation: fadeInDown 0.4s ease-out; }
        .urbanist-almaden-logo {
            font-family: "Urbanist", sans-serif;
            font-optical-sizing: auto;
            font-weight: 700;
            font-size: 34px !important;
            font-style: normal;
        }
    </style>
    <?php wp_head(); ?>
    <style id="almaden-booklist-overrides">
        html {
            margin-top: 0 !important;
        }
        main {
            padding-top: 20px !important;
            background-color: #f5f5f5;
        }
        #almaden-app-user-menu {
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col theme-light">

    <?php echo almaden_bookster_render_shared_nav( 'creator' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    <!-- Main Content -->
    <main id="almaden-workshop" class="almaden-app-content-shell flex-1 pb-10" style="background-color: #f5f5f5;">
        <div class="mx-auto w-full max-w-7xl px-8">
            <?php if ( $book_created || $book_deleted || $book_imported || ! empty( $book_imported_error ) ) : ?>
            <div id="success-toast" class="mb-8 bg-black text-white p-4 rounded-lg shadow-lg flex items-center justify-between animate-fade-in-down">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-<?php echo ($book_created || $book_imported) ? 'amber' : 'rose'; ?>-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <?php if ( $book_created || $book_imported ) : ?>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <?php else : ?>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <?php endif; ?>
                    </svg>
                    <span class="font-medium text-sm">
                        <?php 
                        if ( $book_created ) {
                            echo 'Libro creado exitosamente.';
                        } elseif ( $book_deleted ) {
                            echo 'Libro eliminado con éxito.';
                        } elseif ( $book_imported ) {
                            echo 'Libro importado exitosamente.';
                        } else {
                            echo 'Error al importar libro: ' . esc_html( $book_imported_error );
                        }
                        ?>
                    </span>
                </div>
                <button onclick="document.getElementById('success-toast').style.display='none'" class="text-gray-400 hover:text-white">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <?php endif; ?>

            <div class="mb-8 border-b border-gray-200 pb-5 flex justify-between items-end">
                <div>
                    <h1 class="text-3xl font-bold text-black leading-tight">Taller</h1>
                    <p class="mt-2 text-sm text-gray-500">Gestiona y edita tus proyectos editoriales.</p>
                </div>
                <div class="flex items-center">
                        <button id="open-modal-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors shadow-sm">
                            <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        Crear Libro
                        </button>
                    <button id="upload-book-btn" onclick="document.getElementById('upload-book-file').click();" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors shadow-sm ml-2">
                        <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Upload Book
                    </button>
                    <form id="upload-book-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" enctype="multipart/form-data" style="display: none;">
                        <input type="hidden" name="action" value="almaden_upload_book">
                        <?php wp_nonce_field( 'almaden_upload_book_nonce', 'almaden_upload_nonce' ); ?>
                        <input type="file" id="upload-book-file" name="book_zip" accept=".zip" onchange="document.getElementById('upload-book-form').submit();">
                    </form>
                </div>
            </div>

            <?php if ( $publisher_created ) : ?>
            <div class="mb-8 bg-slate-50 border border-slate-200 text-slate-900 p-4 rounded-xl flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <strong class="block text-sm font-semibold">Editorial creada correctamente.</strong>
                    <span class="text-sm text-slate-700">Ya tienes acceso al taller. El siguiente paso es crear tu primer libro.</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( $publisher_tour_completed ) : ?>
            <div class="mb-8 bg-slate-50 border border-slate-200 text-slate-800 p-4 rounded-xl">
                <strong class="block text-sm font-semibold">Onboarding completado.</strong>
                <span class="text-sm text-slate-700">Ya puedes trabajar en el taller sin el panel guiado inicial.</span>
            </div>
            <?php endif; ?>

            <?php if ( $publisher_created || $show_publisher_tour ) : ?>
                <?php include plugin_dir_path( __FILE__ ) . 'booklist-onboarding.php'; ?>
            <?php endif; ?>

            <?php if ( $books_query->have_posts() ) : ?>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <?php while ( $books_query->have_posts() ) : $books_query->the_post(); 
                    $author = function_exists( 'almaden_bookster_get_book_author_display_label' ) ? almaden_bookster_get_book_author_display_label( get_the_ID(), get_post_meta( get_the_ID(), 'book_author', true ) ) : get_post_meta( get_the_ID(), 'book_author', true );
                    $content_preview = wp_trim_words( get_the_content(), 15 );
                    $editor_url = home_url( '/almaden-book-editor/?book_id=' . get_the_ID() );
                    
                    // Contar capítulos
                    $source_book_id = get_post_meta( get_the_ID(), '_almaden_source_book_id', true );
                    if ( empty( $source_book_id ) ) {
                        $source_book_id = get_the_ID();
                    }
                    $chapter_count_query = get_posts( array(
                        'post_type' => 'book_chapter',
                        'post_parent' => $source_book_id,
                        'fields' => 'ids',
                        'posts_per_page' => -1
                    ) );
                    $chapter_count = count($chapter_count_query);
                    $has_saved_chapter_content = false;
                    foreach ( $chapter_count_query as $chapter_id ) {
                        $chapter_content = (string) get_post_field( 'post_content', $chapter_id );
                        if ( '' !== trim( wp_strip_all_tags( $chapter_content ) ) ) {
                            $has_saved_chapter_content = true;
                            break;
                        }
                    }
                    
                    // Obtener total de páginas
                    $total_pages = get_post_meta( get_the_ID(), '_almaden_total_pages', true );
                    $pages_count = $total_pages ? intval($total_pages) : 0;
                    
                    // Obtener configuración de la portada
                    $cover_thumbnail_html = almaden_get_cover_thumbnail_html( get_the_ID() );
                    
                    $is_published = get_post_meta( get_the_ID(), '_almaden_is_published', true ) === '1';
                    $can_toggle_publish = $has_saved_chapter_content;
                    $can_manage_quiz = is_user_logged_in() && ( function_exists( 'almaden_bookster_user_can_manage_book' ) ? almaden_bookster_user_can_manage_book( get_the_ID() ) : ( current_user_can( 'manage_options' ) || current_user_can( 'edit_post', get_the_ID() ) ) );
                    $book_quiz_id = 0;
                    $book_quiz_status_label = '';
                    if ( $can_manage_quiz && function_exists( 'almaden_bookster_learni_integration_active' ) && almaden_bookster_learni_integration_active() ) {
                        $book_quiz_id = function_exists( 'almaden_bookster_learni_get_quiz_id' ) ? (int) almaden_bookster_learni_get_quiz_id( get_the_ID() ) : 0;
                        $book_quiz_status_label = $book_quiz_id > 0 ? 'Quiz vinculado' : 'Sin quiz';
                    }
                    ?>
                    <div id="book-card-<?php echo get_the_ID(); ?>" class="book-card bg-white overflow-hidden border border-gray-200 rounded-xl flex flex-col sm:flex-row h-full group relative">
                        <div id="book-cover-<?php echo get_the_ID(); ?>" class="w-full sm:w-2/5 flex-shrink-0 border-b sm:border-b-0 sm:border-r border-gray-200 bg-gray-50 flex items-center justify-center">
                            <?php if ( ! empty( $cover_thumbnail_html ) ) : ?>
                                <div class="w-full h-full flex items-center relative">
                                    <?php echo str_replace('border-b', '', $cover_thumbnail_html); ?>
                                </div>
                            <?php else : ?>
                                <div class="w-full h-56 sm:h-full min-h-[18rem] flex items-center justify-center px-6 py-8 relative bg-[radial-gradient(circle_at_top,_rgba(15,23,42,0.02),_transparent_42%),linear-gradient(180deg,#fbfbfc_0%,#f6f7fb_100%)]">
                                    <div class="max-w-[18rem] text-center">
                                        <div class="mx-auto mb-6 h-px w-20 bg-slate-200"></div>
                                        <p class="text-[0.7rem] uppercase tracking-[0.4em] text-slate-400">Portada provisional</p>
                                        <h3 class="mt-5 text-3xl md:text-4xl font-semibold leading-tight text-slate-900 serif">
                                            <?php echo esc_html( get_the_title() ); ?>
                                        </h3>
                                        <?php if ( ! empty( $author ) ) : ?>
                                            <p class="mt-4 text-base md:text-lg font-medium text-slate-500">
                                                <?php echo esc_html( $author ); ?>
                                            </p>
                                        <?php endif; ?>
                                        <div class="mx-auto mt-6 h-px w-24 bg-slate-200"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="w-full sm:w-3/5 flex flex-col flex-1">
                            <div class="p-6 flex-1 flex flex-col">
                                <div class="flex items-start justify-between gap-3 mb-4">
                                    <div class="min-w-0">
                                        <h3 id="book-title-<?php echo get_the_ID(); ?>" class="text-lg font-bold text-gray-900 serif leading-tight mb-1">
                                            <?php echo esc_html( get_the_title() ); ?>
                                        </h3>
                                        <?php if ( ! empty( $author ) ) : ?>
                                            <p id="book-author-<?php echo get_the_ID(); ?>" class="text-sm font-medium text-gray-500">por <?php echo esc_html( $author ); ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="relative z-10 dropdown-container ml-1 shrink-0">
                                        <button type="button" id="options-trigger-<?php echo get_the_ID(); ?>" onclick="this.nextElementSibling.classList.toggle('hidden');" class="text-gray-400 hover:text-gray-600 transition-colors bg-white rounded p-1 border border-transparent hover:border-gray-200 hover:bg-gray-50" title="Opciones">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                            </svg>
                                        </button>
                                        <div id="options-dropdown-<?php echo get_the_ID(); ?>" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                            <div class="py-1">
                                                <a href="#" id="option-settings-<?php echo get_the_ID(); ?>" onclick="openBookSettings(<?php echo get_the_ID(); ?>, '<?php echo wp_create_nonce('almaden_save_settings_nonce_' . get_the_ID()); ?>'); event.preventDefault();" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex justify-between items-center"><span class="settings-text-<?php echo get_the_ID(); ?>">Settings</span><i class="fa-solid fa-spinner fa-spin hidden settings-spinner-<?php echo get_the_ID(); ?>"></i></a>
                                                
                                                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
                                                    <input type="hidden" name="action" value="almaden_duplicate_book">
                                                    <input type="hidden" name="book_id" value="<?php echo get_the_ID(); ?>">
                                                    <?php wp_nonce_field( 'almaden_duplicate_book_nonce', 'almaden_duplicate_nonce' ); ?>
                                                    <button type="submit" id="option-duplicate-<?php echo get_the_ID(); ?>" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Duplicar</button>
                                                </form>
                                                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
                                                    <input type="hidden" name="action" value="almaden_export_epub">
                                                    <input type="hidden" name="book_id" value="<?php echo get_the_ID(); ?>">
                                                    <?php wp_nonce_field( 'almaden_export_epub_nonce', 'almaden_epub_nonce' ); ?>
                                                    <button type="submit" id="option-export-epub-<?php echo get_the_ID(); ?>" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export ePub</button>
                                                </form>
                                                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
                                                    <input type="hidden" name="action" value="almaden_download_book">
                                                    <input type="hidden" name="book_id" value="<?php echo get_the_ID(); ?>">
                                                    <?php wp_nonce_field( 'almaden_download_book_nonce', 'almaden_download_nonce' ); ?>
                                                    <button type="submit" id="option-download-<?php echo get_the_ID(); ?>" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Download Book (Backup)</button>
                                                </form>
                                                <a href="#" id="option-drive-<?php echo get_the_ID(); ?>" onclick="uploadBookToDrive(<?php echo get_the_ID(); ?>); event.preventDefault();" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex justify-between items-center"><span class="drive-text-<?php echo get_the_ID(); ?>">Subir a Google Drive</span><i class="fa-solid fa-spinner fa-spin hidden drive-spinner-<?php echo get_the_ID(); ?>"></i></a>
                                            </div>
                                            <div class="py-1">
                                                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este libro? Esta acción borrará el libro de forma permanente.');">
                                                    <input type="hidden" name="action" value="almaden_delete_book">
                                                    <input type="hidden" name="book_id" value="<?php echo get_the_ID(); ?>">
                                                    <?php wp_nonce_field( 'almaden_delete_book_nonce', 'almaden_delete_nonce' ); ?>
                                                    <button type="submit" id="option-delete-<?php echo get_the_ID(); ?>" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Eliminar</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex-1 flex flex-col">
                                    <div class="flex items-center gap-2 mt-auto mb-4">
                                        <?php if ( $pages_count > 0 ) : ?>
                                            <div class="inline-flex items-baseline gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-slate-800 leading-none">
                                                <span class="text-sm font-bold"><?php echo $pages_count; ?></span>
                                                <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">págs.</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="inline-flex items-baseline gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-slate-800 leading-none">
                                            <span class="text-sm font-bold"><?php echo $chapter_count; ?></span>
                                            <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">cap.</span>
                                        </div>
                                        <?php if ( $book_quiz_status_label !== '' ) : ?>
                                            <div class="inline-flex items-baseline gap-1 px-2.5 py-1 rounded-full <?php echo $book_quiz_id > 0 ? 'bg-slate-900 text-white' : 'bg-amber-50 text-amber-700'; ?> leading-none">
                                                <span class="text-[10px] font-semibold uppercase tracking-wider"><?php echo esc_html( $book_quiz_status_label ); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
 
                                <div class="mt-2 flex flex-col gap-2">
                                    <a href="<?php echo esc_url( $editor_url ); ?>" id="btn-edit-content-<?php echo get_the_ID(); ?>" class="w-full inline-flex justify-center items-center py-2 px-3 bg-black text-white text-xs font-semibold rounded-md hover:bg-gray-800 transition-colors">
                                        EDIT CONTENT
                                    </a>
                                    <a href="<?php echo esc_url( home_url( '/almaden-book-cover/?book_id=' . get_the_ID() ) ); ?>" id="btn-edit-cover-<?php echo get_the_ID(); ?>" class="w-full inline-flex justify-center items-center py-2 px-3 bg-white text-gray-700 border border-gray-300 text-xs font-semibold rounded-md hover:bg-gray-50 transition-colors">
                                        EDIT BOOK COVER
                                    </a>
                                    <?php if ( $can_manage_quiz && function_exists( 'almaden_bookster_learni_integration_active' ) && almaden_bookster_learni_integration_active() ) : ?>
                                        <?php if ( $has_saved_chapter_content ) : ?>
                                            <a href="<?php echo esc_url( home_url( '/almaden-book-quiz/?book_id=' . get_the_ID() ) ); ?>" id="btn-create-quiz-<?php echo get_the_ID(); ?>" class="w-full inline-flex justify-center items-center py-2 px-3 <?php echo $book_quiz_id > 0 ? 'bg-slate-900 text-white' : 'bg-amber-500 text-white'; ?> text-xs font-semibold rounded-md <?php echo $book_quiz_id > 0 ? 'hover:bg-slate-800' : 'hover:bg-amber-600'; ?> transition-colors shadow-sm">
                                                <?php echo $book_quiz_id > 0 ? 'EDIT QUIZ' : 'CREATE QUIZ'; ?>
                                            </a>
                                        <?php else : ?>
                                            <button type="button" id="btn-create-quiz-<?php echo get_the_ID(); ?>" class="w-full inline-flex justify-center items-center py-2 px-3 bg-slate-200 text-slate-400 text-xs font-semibold rounded-md cursor-not-allowed shadow-sm" disabled aria-disabled="true" title="Necesitas guardar al menos un capítulo con contenido para crear el quiz.">
                                                CREATE QUIZ
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <label class="relative inline-flex items-center <?php echo $can_toggle_publish ? 'cursor-pointer' : 'cursor-not-allowed opacity-60'; ?>" title="<?php echo esc_attr( $can_toggle_publish ? 'Publicar en ' . almaden_bookster_get_store_title() : 'Necesitas al menos un capítulo con contenido para publicar este libro.' ); ?>">
                                    <input type="checkbox" id="publish-toggle-<?php echo get_the_ID(); ?>" class="sr-only peer" onchange="togglePublishBook(<?php echo get_the_ID(); ?>, !this.checked)" <?php checked( $is_published, true ); ?> <?php disabled( ! $can_toggle_publish ); ?> aria-disabled="<?php echo $can_toggle_publish ? 'false' : 'true'; ?>">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-black rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-500"></div>
                                    <span class="ml-2 text-xs font-semibold text-slate-500 publish-text-<?php echo get_the_ID(); ?>"><?php echo $is_published ? 'Publicado' : esc_html( almaden_bookster_get_store_title() ); ?></span>
                                    <i class="ml-2 fa-solid fa-spinner fa-spin hidden text-gray-500 publish-spinner-<?php echo get_the_ID(); ?>"></i>
                                </label>
                                <div class="flex items-center justify-between gap-3 sm:justify-end">
                                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Última edición</span>
                                    <span class="text-xs text-gray-500"><?php echo get_the_modified_date('d M Y'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        <?php else : ?>
            <div id="booklist-empty-state" class="text-center py-20 bg-white border border-gray-200 border-dashed rounded-xl">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No hay libros</h3>
                <p class="mt-1 text-sm text-gray-500">Comienza creando tu primer proyecto editorial.</p>
                <div class="mt-6">
                    <button type="button" id="empty-create-book-btn" onclick="openModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Crear Nuevo Libro
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal Form (Hidden by default) -->
    <?php include plugin_dir_path( __FILE__ ) . 'booklist-create-modal.php'; ?>

    <script src="<?php echo esc_url( plugins_url( '../../assets/js/admin/booklist-ui.js?v=' . time(), __FILE__ ) ); ?>"></script>
    
    <!-- Include the Settings Modal and JS -->
    <?php include plugin_dir_path( __FILE__ ) . '../editor/editor-settings-modal.php'; ?>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-tabs.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-fields.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits-constants.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits-utils.js?v=' . time(), __FILE__ ) ); ?>"></script>

    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits-state.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits-ui.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits-events.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-credits.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-templates.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-state.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <script src="<?php echo esc_url( plugins_url( '../../assets/js/editor/editor-settings-api.js?v=' . time(), __FILE__ ) ); ?>"></script>
    <?php if ( class_exists( '\AlmadenBookster\Auth\UI\Renderer' ) ) : ?>
        <?php echo \AlmadenBookster\Auth\UI\Renderer::get_auth_modal_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php endif; ?>
    <?php almaden_bookster_render_user_menu_script(); ?>
    <?php wp_footer(); ?>
</body>
</html>
