<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( __FILE__ ) . '/../includes/cover-thumbnail.php';

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

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookCraft - Mis Libros</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="<?php echo esc_url( almaden_get_thumbnail_fonts_url() ); ?>" rel="stylesheet">
    <!-- Font Awesome Icons para UI -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo esc_url( plugins_url( '../assets/css/editor-style.css?v=' . time(), __FILE__ ) ); ?>">
    <script>
        var ajaxurl = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
        let bookState = {
            bookId: 0,
            settings: {},
            settingsNonce: '',
            ajaxUrl: ajaxurl,
            installedFonts: []
        };

        // Fallback for missing editor functions
        function showToast(message, iconClass) {
            alert(message);
        }
    </script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background-color: #fcfcfc;
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
    </style>
</head>
<body class="min-h-screen flex flex-col theme-light">

    <!-- Top Navigation -->
    <nav class="border-b border-gray-200 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center gap-3">
                        <svg class="h-8 w-8 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span class="font-semibold text-xl tracking-tight serif">BookCraft</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="<?php echo esc_url( admin_url() ); ?>" class="text-sm font-medium text-gray-500 hover:text-black transition-colors">Volver a WP</a>
                    <button id="open-modal-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors shadow-sm">
                        <svg class="mr-2 -ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Crear Libro
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <?php if ( $book_created || $book_deleted ) : ?>
        <div id="success-toast" class="mb-8 bg-black text-white p-4 rounded-lg shadow-lg flex items-center justify-between animate-fade-in-down">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-<?php echo $book_created ? 'green' : 'gray'; ?>-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <?php if ( $book_created ) : ?>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    <?php else : ?>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    <?php endif; ?>
                </svg>
                <span class="font-medium text-sm">
                    <?php echo $book_created ? 'Libro creado exitosamente.' : 'Libro eliminado con éxito.'; ?>
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
                <h1 class="text-3xl font-bold text-black leading-tight">Mis Libros</h1>
                <p class="mt-2 text-sm text-gray-500">Gestiona y edita tus proyectos editoriales.</p>
            </div>
        </div>

        <?php if ( $books_query->have_posts() ) : ?>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <?php while ( $books_query->have_posts() ) : $books_query->the_post(); 
                    $author = get_post_meta( get_the_ID(), 'book_author', true );
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
                    
                    // Obtener total de páginas
                    $total_pages = get_post_meta( get_the_ID(), '_almaden_total_pages', true );
                    $pages_count = $total_pages ? intval($total_pages) : 0;
                    
                    // Obtener configuración de la portada
                    $cover_thumbnail_html = almaden_get_cover_thumbnail_html( get_the_ID() );
                    
                    // Estado de publicación
                    $is_published = get_post_meta( get_the_ID(), '_almaden_is_published', true ) === '1';
                ?>
                    <div class="book-card bg-white overflow-hidden border border-gray-200 rounded-xl flex flex-col sm:flex-row h-full group relative">
                        <div class="w-full sm:w-2/5 flex-shrink-0 border-b sm:border-b-0 sm:border-r border-gray-200 bg-gray-50 flex items-center justify-center">
                            <?php if ( ! empty( $cover_thumbnail_html ) ) : ?>
                                <div class="w-full h-full flex items-center relative">
                                    <?php echo str_replace('border-b', '', $cover_thumbnail_html); ?>
                                    <?php if ( $is_published ) : ?>
                                        <div class="absolute top-2 left-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded shadow-sm">
                                            Publicado
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else : ?>
                                <div class="w-full h-48 sm:h-full flex items-center justify-center text-gray-400 relative">
                                    <svg class="h-12 w-12 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <?php if ( $is_published ) : ?>
                                        <div class="absolute top-2 left-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded shadow-sm">
                                            Publicado
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="w-full sm:w-3/5 flex flex-col flex-1">
                            <div class="p-6 flex-1 flex flex-col">
                                <div class="flex items-start justify-end mb-4">
                                    <div class="flex items-center gap-2">
                                        <?php if ( $pages_count > 0 ) : ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                <?php echo $pages_count; ?> págs.
                                            </span>
                                        <?php endif; ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?php echo $chapter_count; ?> cap.
                                        </span>
                                        <div class="relative z-10 dropdown-container">
                                            <button type="button" onclick="this.nextElementSibling.classList.toggle('hidden');" class="text-gray-400 hover:text-gray-600 transition-colors bg-white rounded p-1 border border-transparent hover:border-gray-200 hover:bg-gray-50" title="Opciones">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                                </svg>
                                            </button>
                                            <div class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                <div class="py-1">
                                                    <a href="#" onclick="openBookSettings(<?php echo get_the_ID(); ?>, '<?php echo wp_create_nonce('almaden_save_settings_nonce_' . get_the_ID()); ?>'); event.preventDefault();" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex justify-between items-center"><span class="settings-text-<?php echo get_the_ID(); ?>">Settings</span><i class="fa-solid fa-spinner fa-spin hidden settings-spinner-<?php echo get_the_ID(); ?>"></i></a>
                                                    
                                                    <a href="#" onclick="togglePublishBook(<?php echo get_the_ID(); ?>, <?php echo $is_published ? 'true' : 'false'; ?>); event.preventDefault();" class="block px-4 py-2 text-sm <?php echo $is_published ? 'text-orange-600 hover:bg-orange-50' : 'text-green-600 hover:bg-green-50'; ?> flex justify-between items-center">
                                                        <span class="publish-text-<?php echo get_the_ID(); ?>"><?php echo $is_published ? 'Unpublish ebook' : 'Publish ebook'; ?></span>
                                                        <i class="fa-solid fa-spinner fa-spin hidden publish-spinner-<?php echo get_the_ID(); ?>"></i>
                                                    </a>

                                                    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
                                                        <input type="hidden" name="action" value="almaden_duplicate_book">
                                                        <input type="hidden" name="book_id" value="<?php echo get_the_ID(); ?>">
                                                        <?php wp_nonce_field( 'almaden_duplicate_book_nonce', 'almaden_duplicate_nonce' ); ?>
                                                        <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Duplicar</button>
                                                    </form>
                                                    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
                                                        <input type="hidden" name="action" value="almaden_export_epub">
                                                        <input type="hidden" name="book_id" value="<?php echo get_the_ID(); ?>">
                                                        <?php wp_nonce_field( 'almaden_export_epub_nonce', 'almaden_epub_nonce' ); ?>
                                                        <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export ePub</button>
                                                    </form>
                                                    <a href="#" onclick="uploadBookToDrive(<?php echo get_the_ID(); ?>); event.preventDefault();" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex justify-between items-center"><span class="drive-text-<?php echo get_the_ID(); ?>">Subir a Google Drive</span><i class="fa-solid fa-spinner fa-spin hidden drive-spinner-<?php echo get_the_ID(); ?>"></i></a>
                                                </div>
                                                <div class="py-1">
                                                    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este libro? Esta acción borrará el libro de forma permanente.');">
                                                        <input type="hidden" name="action" value="almaden_delete_book">
                                                        <input type="hidden" name="book_id" value="<?php echo get_the_ID(); ?>">
                                                        <?php wp_nonce_field( 'almaden_delete_book_nonce', 'almaden_delete_nonce' ); ?>
                                                        <button type="submit" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Eliminar</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <h3 class="text-lg font-bold text-gray-900 serif leading-tight mb-1">
                                    <?php echo esc_html( get_the_title() ); ?>
                                </h3>
                                
                                <?php if ( ! empty( $author ) ) : ?>
                                    <p class="text-sm font-medium text-gray-500 mb-3">por <?php echo esc_html( $author ); ?></p>
                                <?php endif; ?>
                                
                                <p class="text-sm text-gray-600 line-clamp-3 mb-4 flex-1">
                                    <?php echo $content_preview ? esc_html($content_preview) : '<span class="italic text-gray-400">Sin descripción...</span>'; ?>
                                </p>

                                <div class="mt-2 flex flex-col gap-2">
                                    <a href="<?php echo esc_url( $editor_url ); ?>" class="w-full inline-flex justify-center items-center py-2 px-3 bg-black text-white text-xs font-semibold rounded-md hover:bg-gray-800 transition-colors">
                                        EDIT CONTENT
                                    </a>
                                    <a href="<?php echo esc_url( home_url( '/almaden-book-cover/?book_id=' . get_the_ID() ) ); ?>" class="w-full inline-flex justify-center items-center py-2 px-3 bg-white text-gray-700 border border-gray-300 text-xs font-semibold rounded-md hover:bg-gray-50 transition-colors">
                                        EDIT BOOK COVER
                                    </a>
                                </div>
                            </div>
                            
                            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Última edición</span>
                                <span class="text-xs text-gray-500"><?php echo get_the_modified_date('d M Y'); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        <?php else : ?>
            <div class="text-center py-20 bg-white border border-gray-200 border-dashed rounded-xl">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No hay libros</h3>
                <p class="mt-1 text-sm text-gray-500">Comienza creando tu primer proyecto editorial.</p>
                <div class="mt-6">
                    <button type="button" onclick="openModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-black hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
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
    <div id="create-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Background backdrop -->
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <!-- Modal panel -->
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100 opacity-0 scale-95" id="modal-panel">
                    
                    <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                        <button type="button" id="close-modal-btn" class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2 transition-colors">
                            <span class="sr-only">Cerrar</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-black" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-xl font-bold leading-6 text-gray-900 serif" id="modal-title">Crear Nuevo Libro</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Ingresa los detalles básicos para comenzar tu proyecto editorial.</p>
                                </div>
                                
                                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" class="mt-6 space-y-4">
                                    <input type="hidden" name="action" value="almaden_create_book">
                                    <?php wp_nonce_field( 'almaden_create_book_nonce', 'almaden_nonce' ); ?>
                                    
                                    <div>
                                        <label for="book_title" class="block text-sm font-medium leading-6 text-gray-900">Título del Libro <span class="text-red-500">*</span></label>
                                        <div class="mt-1">
                                            <input type="text" name="book_title" id="book_title" required class="block w-full rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-black sm:text-sm sm:leading-6 bg-gray-50/50">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="book_author" class="block text-sm font-medium leading-6 text-gray-900">Autor(es) <span class="text-red-500">*</span></label>
                                        <div class="mt-1">
                                            <input type="text" name="book_author" id="book_author" required class="block w-full rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-black sm:text-sm sm:leading-6 bg-gray-50/50">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="book_content" class="block text-sm font-medium leading-6 text-gray-900">Sinopsis o Descripción breve</label>
                                        <div class="mt-1">
                                            <textarea id="book_content" name="book_content" rows="3" class="block w-full rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-black sm:text-sm sm:leading-6 bg-gray-50/50 resize-none"></textarea>
                                        </div>
                                    </div>

                                    <div class="mt-6 flex items-center justify-end gap-x-3 border-t border-gray-100 pt-5">
                                        <button type="button" id="cancel-modal-btn" class="text-sm font-semibold leading-6 text-gray-900 hover:text-gray-600 transition-colors">Cancelar</button>
                                        <button type="submit" class="rounded-md bg-black px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black transition-colors">Crear Libro</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        const modal = document.getElementById('create-modal');
        const modalPanel = document.getElementById('modal-panel');
        const openBtn = document.getElementById('open-modal-btn');
        const closeBtn = document.getElementById('close-modal-btn');
        const cancelBtn = document.getElementById('cancel-modal-btn');
        
        function openModal() {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalPanel.classList.remove('opacity-0', 'scale-95');
                modalPanel.classList.add('opacity-100', 'scale-100');
            }, 10);
            document.getElementById('book_title').focus();
        }

        function closeModal() {
            modalPanel.classList.remove('opacity-100', 'scale-100');
            modalPanel.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        if (openBtn) openBtn.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        // Scale Cover Thumbnails
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
        // Call twice: once immediately, once on full load
        scaleThumbnails();
        window.addEventListener('load', scaleThumbnails);

        function closeAddModal() {
            document.getElementById('add-book-modal').classList.add('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            var isClickInside = false;
            document.querySelectorAll('.dropdown-container').forEach(function(container) {
                if (container.contains(event.target)) {
                    isClickInside = true;
                } else {
                    var dropdown = container.querySelector('.hidden.absolute');
                    if(dropdown && !dropdown.classList.contains('hidden')){
                        dropdown.classList.add('hidden');
                    }
                }
            });
            if(isClickInside) {
                // If a button was clicked, ensure others are closed
                var targetBtn = event.target.closest('button');
                if(targetBtn) {
                    var targetContainer = targetBtn.closest('.dropdown-container');
                    document.querySelectorAll('.dropdown-container').forEach(function(container) {
                        if(container !== targetContainer) {
                            var dropdown = container.querySelector('.hidden.absolute');
                            if(dropdown && !dropdown.classList.contains('hidden')){
                                dropdown.classList.add('hidden');
                            }
                        }
                    });
                }
            }
        });

        // Function to load and open settings
        function openBookSettings(bookId, nonce) {
            const spinner = document.querySelector('.settings-spinner-' + bookId);
            if (spinner) spinner.classList.remove('hidden');
            
            const formData = new FormData();
            formData.append('action', 'almaden_get_book_settings');
            formData.append('book_id', bookId);
            formData.append('nonce', nonce);

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (spinner) spinner.classList.add('hidden');
                if (data.success) {
                    bookState.bookId = bookId;
                    bookState.settings = data.data.settings;
                    bookState.settingsNonce = nonce;
                    toggleSettingsModal(true);
                } else {
                    alert('Error cargando los ajustes.');
                }
            })
            .catch(err => {
                if (spinner) spinner.classList.add('hidden');
                alert('Error de conexión.');
            });
        }

        // Function to export book to Google Drive
        function uploadBookToDrive(bookId) {
            const spinner = document.querySelector('.drive-spinner-' + bookId);
            const textSpan = document.querySelector('.drive-text-' + bookId);
            
            if (spinner) spinner.classList.remove('hidden');
            if (textSpan) textSpan.textContent = 'Subiendo...';
            
            const formData = new FormData();
            formData.append('action', 'almaden_export_book_to_drive');
            formData.append('book_id', bookId);
            // Reusing the general admin-ajax architecture. We can rely on user capabilities in backend.

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (spinner) spinner.classList.add('hidden');
                if (textSpan) textSpan.textContent = 'Subir a Google Drive';
                
                if (data.success) {
                    alert('¡Éxito! ' + data.data);
                } else {
                    alert('Error: ' + data.data);
                }
            })
            .catch(err => {
                if (spinner) spinner.classList.add('hidden');
                if (textSpan) textSpan.textContent = 'Subir a Google Drive';
                alert('Error de red al intentar subir a Google Drive.');
            });
        }

        // Function to toggle publish status
        function togglePublishBook(bookId, isPublished) {
            const spinner = document.querySelector('.publish-spinner-' + bookId);
            const textSpan = document.querySelector('.publish-text-' + bookId);
            
            if (spinner) spinner.classList.remove('hidden');
            
            const action = isPublished ? 'unpublish' : 'publish';
            const formData = new FormData();
            formData.append('action', 'almaden_toggle_publish_book');
            formData.append('book_id', bookId);
            formData.append('publish_action', action);

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload to show updated status UI
                } else {
                    if (spinner) spinner.classList.add('hidden');
                    alert('Error: ' + data.data);
                }
            })
            .catch(err => {
                if (spinner) spinner.classList.add('hidden');
                alert('Error de red.');
            });
        }
    </script>
    
    <!-- Include the Settings Modal and JS -->
    <?php include plugin_dir_path( __FILE__ ) . 'editor-settings-modal.php'; ?>
    <script src="<?php echo esc_url( plugins_url( '../assets/js/editor-settings.js?v=' . time(), __FILE__ ) ); ?>"></script>
</body>
</html>
