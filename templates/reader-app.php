<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the current book
$book_id = get_the_ID();
$book_title = get_the_title();
$author = get_post_meta( $book_id, '_almaden_book_author', true );

// Get the cover HTML
require_once dirname( __FILE__ ) . '/../includes/cover-thumbnail.php';
$cover_html = almaden_get_cover_thumbnail_html( $book_id );
$fonts_url = almaden_get_thumbnail_fonts_url();

// Fetch chapters
$chapters_query = new WP_Query( array(
	'post_type'      => 'book_chapter',
	'post_parent'    => $book_id,
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
			'hide_title' => get_post_meta( get_the_ID(), '_almaden_chapter_hide_title', true ),
		);
		$page_counter++;
	}
	wp_reset_postdata();
}

// Fetch global book settings
$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
$settings_book_id = !empty($source_book_id) ? $source_book_id : $book_id;
$book_settings = function_exists('almaden_get_book_pdf_settings') ? almaden_get_book_pdf_settings( $settings_book_id ) : array();

$cover_settings = get_post_meta( $settings_book_id, '_almaden_cover_settings', true );
$fallback_cover_url = '';
if ( is_array( $cover_settings ) && !empty( $cover_settings['front_image'] ) ) {
    $fallback_cover_url = $cover_settings['front_image'];
} elseif ( is_array( $cover_settings ) && !empty( $cover_settings['spread_image'] ) ) {
    $fallback_cover_url = $cover_settings['spread_image'];
}

// Encode to JSON for frontend
$book_data_json = wp_json_encode( array(
	'title'    => $book_title,
	'author'   => $author,
	'settings' => $book_settings,
	'chapters' => $chapters,
	'cover_url'=> $fallback_cover_url,
) );
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $book_title ); ?> - Reader</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts for Cover -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?php echo esc_url($fonts_url); ?>" rel="stylesheet">
    <!-- Inter Font for UI -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Markdown Parser -->
    <script src="https://cdn.jsdelivr.net/npm/markdown-it@13.0.1/dist/markdown-it.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }

        /* Cover CSS requirements */
        .cover-thumbnail-wrapper {
            width: 100%;
            background-color: #ffffff;
            overflow: hidden;
            position: relative;
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

        /* Typography for Reader Content */
        .prose {
            font-family: Georgia, serif;
            font-size: 1.125rem;
            line-height: 1.8;
            color: #333;
            max-width: 700px;
            margin: 0 auto;
        }
        .prose h1, .prose h2, .prose h3 {
            font-family: 'Inter', sans-serif;
            color: #111;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .prose p {
            margin-bottom: 1.5rem;
        }
        .prose blockquote {
            border-left: 4px solid #ccc;
            padding-left: 1rem;
            color: #555;
            font-style: italic;
        }

        /* Transitions */
        .fade-enter {
            opacity: 0;
            transform: translateY(10px);
        }
        .fade-enter-active {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 300ms ease, transform 300ms ease;
        }
        
        /* Custom scrollbar for webkit */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Flip Mode CSS */
        .mode-flip #chapter-scroll-area {
            overflow: hidden !important;
            display: flex;
            align-items: center;
            position: relative;
        }
        .mode-flip #chapter-content {
            height: calc(100vh - 12rem);
            column-width: calc(50vw - 6rem);
            column-gap: 6rem;
            column-fill: auto;
            max-width: none;
            margin: 0;
            overflow: visible;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .mode-flip .prose h1, .mode-flip .prose h2, .mode-flip .prose h3 {
            break-after: avoid;
        }
        
        @media (max-width: 768px) {
            .mode-flip #chapter-content {
                column-width: calc(100vw - 3rem);
                column-gap: 3rem;
            }
        }
        
        /* User Requested Constraints */
        div#view-index,
        header#chapter-navbar {
            max-width: var(--wp--style--global--wide-size, 1300px);
            margin: auto;
        }
    </style>
</head>
<body>
    <script>
        const bookData = <?php echo $book_data_json; ?>;
    </script>

    <!-- STATE: INDEX -->
    <div id="view-index" class="w-full h-full flex flex-col md:flex-row bg-white">
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
                <a id="reader-btn-back" href="<?php echo esc_url( home_url( '/bookshelf/' ) ); ?>" class="px-5 py-2 bg-white border border-gray-200 hover:bg-gray-50 rounded-full text-sm font-semibold text-gray-700 shadow-sm transition-colors">Volver</a>
            </div>

            <div class="space-y-1" id="chapters-list">
                <!-- Chapters will be rendered here via JS -->
            </div>
        </div>
    </div>

    <!-- STATE: CHAPTER -->
    <div id="view-chapter" class="w-full h-full flex flex-col bg-white hidden">
        <!-- Sticky Top Navigation -->
        <header id="chapter-navbar" class="w-full h-16 border-b border-gray-100 flex items-center justify-between px-6 bg-white/95 backdrop-blur sticky top-0 z-50">
            <button onclick="showIndexView()" class="flex items-center text-gray-500 hover:text-black transition-colors font-medium">
                <i class="fa-solid fa-list-ul mr-2 text-sm"></i> Índice
            </button>
            <h3 class="text-sm font-semibold text-gray-800 truncate px-4 absolute left-1/2 transform -translate-x-1/2" id="chapter-nav-title">Chapter Title</h3>
            <div class="flex items-center space-x-1">
                <button onclick="toggleReadingMode('scroll')" id="btn-mode-scroll" class="p-2 text-gray-800 bg-gray-100 rounded text-sm w-9 h-9 flex items-center justify-center transition-colors" title="Scroll View">
                    <i class="fa-solid fa-arrows-up-down"></i>
                </button>
                <button onclick="toggleReadingMode('flip')" id="btn-mode-flip" class="p-2 text-gray-400 hover:text-gray-800 rounded text-sm w-9 h-9 flex items-center justify-center transition-colors" title="Two-Page Flip View">
                    <i class="fa-solid fa-book-open"></i>
                </button>
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

    <script>
        const md = window.markdownit({ html: true, breaks: true });
        
        // Generate dynamic CSS for Chapter Titles
        function generateDynamicStyles() {
            const settings = bookData.settings || {};
            let css = '';
            
            if ((settings.ebook_bg_type || 'color') === 'color') {
                css += `
                body {
                    background-color: ${settings.ebook_bg_color || '#ffffff'} !important;
                }`;
            } else {
                css += `
                body {
                    background-image: url('${settings.ebook_bg_image || ''}') !important;
                    background-color: ${settings.ebook_bg_color || '#ffffff'} !important;
                    background-size: cover !important;
                    background-attachment: fixed !important;
                    background-position: center !important;
                    background-repeat: no-repeat !important;
                }`;
            }

            const coverBgType = settings.ebook_cover_panel_bg_type || 'image';
            if (coverBgType === 'color') {
                const coverColor = settings.ebook_cover_panel_bg_color || 'transparent';
                css += `
                #reader-cover-panel {
                    background-color: ${coverColor} !important;
                }`;
            } else {
                const coverImage = settings.ebook_cover_panel_bg_image || bookData.cover_url || '';
                if (coverImage) {
                    css += `
                    #reader-cover-panel {
                        background-image: url('${coverImage}') !important;
                        background-size: cover !important;
                        background-position: center !important;
                        background-repeat: no-repeat !important;
                    }`;
                }
            }

            css += `
                .reader-chapter-title {
                    font-family: '${settings.chapter_title_font_family || 'Playfair Display'}', serif;
                    font-size: ${settings.chapter_title_font_size || 24.0}pt;
                    font-weight: ${settings.chapter_title_font_weight || 'bold'};
                    font-style: ${settings.chapter_title_font_style || 'normal'};
                    text-transform: ${settings.chapter_title_text_transform || 'none'};
                    text-align: ${settings.chapter_title_align || 'center'};
                    padding-top: ${settings.chapter_title_padding_top || 0.0}cm;
                    padding-bottom: ${settings.chapter_title_padding_bottom || 1.5}cm;
                    padding-left: ${settings.chapter_title_padding_left || 0.0}cm;
                    padding-right: ${settings.chapter_title_padding_right || 0.0}cm;
                    line-height: ${settings.chapter_title_line_height || 1.2};
                    margin: 0;
                    width: 100%;
                    color: #111;
                }
            `;
            
            const styleEl = document.createElement('style');
            styleEl.id = 'dynamic-reader-styles';
            styleEl.innerHTML = css;
            document.head.appendChild(styleEl);
        }
        generateDynamicStyles();
        
        let currentChapterIndex = -1;
        let readingMode = 'scroll'; // 'scroll' or 'flip'
        let currentFlipPage = 0;

        // Reading Mode Toggle
        function toggleReadingMode(mode) {
            readingMode = mode;
            const scrollBtn = document.getElementById('btn-mode-scroll');
            const flipBtn = document.getElementById('btn-mode-flip');
            const viewChapter = document.getElementById('view-chapter');
            const chapterContent = document.getElementById('chapter-content');
            const footerNav = document.getElementById('chapter-footer-nav');
            
            // Reset transforms
            currentFlipPage = 0;
            chapterContent.style.transform = 'translateX(0)';
            
            if (mode === 'flip') {
                flipBtn.classList.replace('text-gray-400', 'text-gray-800');
                flipBtn.classList.add('bg-gray-100');
                scrollBtn.classList.replace('text-gray-800', 'text-gray-400');
                scrollBtn.classList.remove('bg-gray-100');
                
                viewChapter.classList.add('mode-flip');
                footerNav.classList.add('hidden'); // hide scroll footer nav
                
                // Need a tiny delay for CSS layout to calculate columns
                setTimeout(updateFlipButtons, 100);
            } else {
                scrollBtn.classList.replace('text-gray-400', 'text-gray-800');
                scrollBtn.classList.add('bg-gray-100');
                flipBtn.classList.replace('text-gray-800', 'text-gray-400');
                flipBtn.classList.remove('bg-gray-100');
                
                viewChapter.classList.remove('mode-flip');
                footerNav.classList.remove('hidden');
                document.getElementById('btn-flip-prev').classList.add('hidden');
                document.getElementById('btn-flip-next').classList.add('hidden');
            }
        }
        
        function updateFlipButtons() {
            if (readingMode !== 'flip') return;
            const scrollArea = document.getElementById('chapter-scroll-area');
            const chapterContent = document.getElementById('chapter-content');
            
            // A "page" view is the exact visible width of the scroll area
            const viewWidth = scrollArea.clientWidth;
            const totalWidth = chapterContent.scrollWidth;
            
            const maxPages = Math.ceil((totalWidth - 10) / viewWidth) - 1; // -10px threshold to avoid empty last page
            
            const btnPrev = document.getElementById('btn-flip-prev');
            const btnNext = document.getElementById('btn-flip-next');
            
            // Show/Hide Previous Button
            if (currentFlipPage > 0) {
                btnPrev.classList.remove('hidden');
            } else {
                btnPrev.classList.add('hidden');
            }
            
            // Show/Hide Next Button
            if (currentFlipPage < maxPages) {
                btnNext.classList.remove('hidden');
            } else {
                btnNext.classList.add('hidden');
            }
        }

        function flipNext() {
            const scrollArea = document.getElementById('chapter-scroll-area');
            const chapterContent = document.getElementById('chapter-content');
            const viewWidth = scrollArea.clientWidth;
            const maxPages = Math.ceil((chapterContent.scrollWidth - 10) / viewWidth) - 1;
            
            if (currentFlipPage < maxPages) {
                currentFlipPage++;
                chapterContent.style.transform = `translateX(-${currentFlipPage * viewWidth}px)`;
                updateFlipButtons();
            }
        }

        function flipPrev() {
            if (currentFlipPage > 0) {
                currentFlipPage--;
                const viewWidth = document.getElementById('chapter-scroll-area').clientWidth;
                const chapterContent = document.getElementById('chapter-content');
                chapterContent.style.transform = `translateX(-${currentFlipPage * viewWidth}px)`;
                updateFlipButtons();
            }
        }
        
        window.addEventListener('resize', () => {
            if (readingMode === 'flip') {
                currentFlipPage = 0;
                document.getElementById('chapter-content').style.transform = 'translateX(0)';
                updateFlipButtons();
            }
        });

        // Cover thumbnail scaling
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
        window.addEventListener('load', scaleThumbnails);
        
        // Timeout to ensure fonts loaded before scaling
        setTimeout(scaleThumbnails, 100);
        setTimeout(scaleThumbnails, 500);

        // Render chapters list
        function renderIndex() {
            const listContainer = document.getElementById('chapters-list');
            listContainer.innerHTML = '';

            if (!bookData.chapters || bookData.chapters.length === 0) {
                listContainer.innerHTML = '<p class="text-gray-400 italic">Este libro no tiene capítulos aún.</p>';
                return;
            }

            bookData.chapters.forEach((chapter, index) => {
                const item = document.createElement('div');
                item.className = 'flex justify-between items-center py-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors group px-4 -mx-4 rounded-md';
                item.onclick = () => showChapterView(index);

                item.innerHTML = `
                    <span class="text-gray-800 font-bold group-hover:text-black text-lg transition-colors">${chapter.title}</span>
                    <span class="text-gray-400 font-medium">${chapter.page}</span>
                `;
                listContainer.appendChild(item);
            });
        }

        // Navigation functions
        function showIndexView() {
            document.getElementById('view-chapter').classList.add('hidden');
            const viewIndex = document.getElementById('view-index');
            viewIndex.classList.remove('hidden');
            
            // Animation reset
            viewIndex.classList.remove('fade-enter-active');
            viewIndex.classList.add('fade-enter');
            requestAnimationFrame(() => {
                viewIndex.classList.add('fade-enter-active');
            });
            
            currentChapterIndex = -1;
            scaleThumbnails(); // Re-scale if needed
        }

        function showChapterView(index) {
            currentChapterIndex = index;
            const chapter = bookData.chapters[index];
            
            document.getElementById('view-index').classList.add('hidden');
            const viewChapter = document.getElementById('view-chapter');
            viewChapter.classList.remove('hidden');
            
            // Pre-process shortcodes
            let processedContent = chapter.content;
            
            // Handle [lang:*]...[/lang] shortcodes -> italics
            processedContent = processedContent.replace(/\[lang:[^\]]+\](.*?)\[\/lang\]/gs, '<i>$1</i>');
            
            // Handle [font="..."]...[/font] shortcodes -> span with font-family
            processedContent = processedContent.replace(/\[font="([^"]+)"\](.*?)\[\/font\]/gs, '<span style="font-family: \'$1\', serif;">$2</span>');
            
            // Handle [align=...]...[/align] shortcodes -> div with text-align
            processedContent = processedContent.replace(/\[align=([a-z]+)\](.*?)\[\/align\]/gs, '<div style="text-align: $1;">$2</div>');

            // Content injection
            document.getElementById('chapter-nav-title').textContent = chapter.title;
            
            let finalHtml = md.render(processedContent);
            if (chapter.hide_title !== '1') {
                finalHtml = `<div class="reader-chapter-title">${chapter.title}</div>` + finalHtml;
            }
            
            document.getElementById('chapter-content').innerHTML = finalHtml;
            
            // Reset state
            document.getElementById('chapter-scroll-area').scrollTop = 0;
            currentFlipPage = 0;
            document.getElementById('chapter-content').style.transform = 'translateX(0)';
            if (readingMode === 'flip') {
                setTimeout(updateFlipButtons, 100);
            }

            // Nav buttons
            const btnPrev = document.getElementById('btn-prev-chapter');
            const btnNext = document.getElementById('btn-next-chapter');
            
            if (index > 0) {
                btnPrev.classList.remove('hidden');
            } else {
                btnPrev.classList.add('hidden');
            }

            if (index < bookData.chapters.length - 1) {
                btnNext.classList.remove('hidden');
            } else {
                btnNext.classList.add('hidden');
            }
            
            // Animation
            viewChapter.classList.remove('fade-enter-active');
            viewChapter.classList.add('fade-enter');
            requestAnimationFrame(() => {
                viewChapter.classList.add('fade-enter-active');
            });
        }

        function goToNextChapter() {
            if (currentChapterIndex < bookData.chapters.length - 1) {
                showChapterView(currentChapterIndex + 1);
            }
        }

        function goToPrevChapter() {
            if (currentChapterIndex > 0) {
                showChapterView(currentChapterIndex - 1);
            }
        }

        // Initialize
        renderIndex();
    </script>
</body>
</html>
