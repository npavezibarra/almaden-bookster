<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Navbar -->
<nav id="almaden-cover-navbar" class="h-16 bg-white border-b border-gray-200 px-4 flex items-center justify-between shrink-0 z-10 relative shadow-sm">
    <div class="flex items-center gap-3">
        <a href="<?php echo esc_url( almaden_bookster_get_creator_page_url() ); ?>" class="text-gray-500 hover:text-black transition flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 shrink-0" title="Volver a Taller">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="flex flex-col justify-center">
            <h1 class="font-bold text-base text-gray-900 serif truncate max-w-xs" style="margin: 0 !important; padding: 0 !important; line-height: 1.2 !important; color: #111827 !important;" title="<?php echo esc_attr( $book_title ); ?>"><?php echo esc_html( $book_title ); ?></h1>
            <span class="text-[11px] text-gray-400 uppercase tracking-wider font-semibold" style="margin: 0 !important; padding: 0 !important; line-height: 1.2 !important;">Editor de Portada</span>
        </div>
    </div>
    
    <div class="flex items-center gap-6">
        <!-- Zoom Controls -->
        <div class="flex items-center gap-1 bg-gray-100 rounded-md p-1">
            <button id="guide-toggle-btn" class="w-8 h-8 flex items-center justify-center rounded text-gray-600 hover:bg-white hover:shadow-sm transition" title="Mostrar/ocultar guías" aria-pressed="true">
                <i class="fa-solid fa-eye text-xs"></i>
            </button>
            <button id="ruler-toggle-btn" class="w-8 h-8 flex items-center justify-center rounded text-gray-600 hover:bg-white hover:shadow-sm transition" title="Mostrar/ocultar regla" aria-pressed="false">
                <i class="fa-solid fa-ruler-combined text-xs"></i>
            </button>
            <button id="zoom-out" class="w-8 h-8 flex items-center justify-center rounded text-gray-600 hover:bg-white hover:shadow-sm transition" title="Reducir (Ctrl/Cmd + Scroll)">
                <i class="fa-solid fa-minus text-xs"></i>
            </button>
            <span id="zoom-level" class="text-xs font-medium w-12 text-center" style="color: #000 !important; font-weight: 700;">100%</span>
            <button id="zoom-in" class="w-8 h-8 flex items-center justify-center rounded text-gray-600 hover:bg-white hover:shadow-sm transition" title="Aumentar (Ctrl/Cmd + Scroll)">
                <i class="fa-solid fa-plus text-xs"></i>
            </button>
        </div>
        <button id="export-pdf-btn" class="bg-white border border-gray-300 text-gray-700 rounded font-semibold hover:bg-gray-50 transition shadow-sm flex items-center" style="font-size: 13px !important; padding: 9px 16px !important; gap: 4px !important;">
            <i class="fa-solid fa-file-pdf text-red-500"></i> Descargar PDF
        </button>
        <button id="save-cover-btn" class="bg-black text-white rounded font-semibold hover:bg-gray-800 transition shadow-sm flex items-center" style="font-size: 13px !important; padding: 9px 16px !important; gap: 4px !important; color: #fff !important;">
            <i class="fa-solid fa-floppy-disk"></i> Guardar Portada
        </button>
    </div>
</nav>
