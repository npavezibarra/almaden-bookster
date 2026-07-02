<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Navbar -->
<nav class="h-16 bg-white border-b border-gray-200 px-4 flex items-center justify-between shrink-0 z-10 relative shadow-sm">
    <div class="flex items-center gap-4">
        <a href="<?php echo esc_url( almaden_bookster_get_creator_page_url() ); ?>" class="text-gray-500 hover:text-black transition flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100" title="Volver a Taller">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="font-bold text-lg leading-none serif truncate max-w-xs" title="<?php echo esc_attr( $book_title ); ?>"><?php echo esc_html( $book_title ); ?></h1>
            <span class="text-xs text-gray-400 uppercase tracking-wider font-semibold">Editor de Portada</span>
        </div>
    </div>
    
    <div class="flex items-center gap-6">
        <!-- Paper Type Selector -->
        <div class="flex items-center gap-2">
            <label for="paper-type" class="text-xs font-medium text-gray-500 uppercase tracking-wider">Papel Interior:</label>
            <select id="paper-type" class="text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black cursor-pointer">
                <option value="0.06">Crema 90g (0.06mm/pág)</option>
                <option value="0.05">Blanco 80g (0.05mm/pág)</option>
                <option value="0.045">Fino 70g (0.045mm/pág)</option>
            </select>
        </div>
        
        <!-- Page Count -->
        <div class="flex items-center gap-2">
            <label for="page-count" class="text-xs font-medium text-gray-500 uppercase tracking-wider">Páginas:</label>
            <input type="number" id="page-count" value="<?php echo esc_attr( $total_pages > 0 ? $total_pages : 0 ); ?>" class="w-20 text-sm border border-gray-300 rounded-md px-2 py-1.5 text-center bg-gray-100 cursor-not-allowed text-gray-500" readonly title="Este valor se calcula automáticamente desde el Content Editor.">
        </div>

        <div class="h-6 w-px bg-gray-300"></div>

        <!-- Spine Width -->
        <div class="flex items-center gap-2">
            <label for="spine-width-mode" class="text-xs font-medium text-gray-500 uppercase tracking-wider">Lomo:</label>
            <select id="spine-width-mode" class="text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black cursor-pointer bg-white">
                <option value="auto">Auto</option>
                <option value="manual">Manual</option>
            </select>
            <input type="number" id="spine-width-mm" min="0" step="0.01" inputmode="decimal" class="w-24 text-sm border border-gray-300 rounded-md px-2 py-1.5 text-center bg-gray-100 text-gray-600" readonly title="Se calcula automáticamente en modo Auto.">
            <span class="text-xs text-gray-400 uppercase tracking-wider">mm</span>
        </div>

        <div class="h-6 w-px bg-gray-300"></div>

        <!-- Zoom Controls -->
        <div class="flex items-center gap-1 bg-gray-100 rounded-md p-1">
            <button id="ruler-toggle-btn" class="w-8 h-8 flex items-center justify-center rounded text-gray-600 hover:bg-white hover:shadow-sm transition" title="Mostrar/ocultar regla" aria-pressed="false">
                <i class="fa-solid fa-ruler-combined text-xs"></i>
            </button>
            <button id="zoom-out" class="w-8 h-8 flex items-center justify-center rounded text-gray-600 hover:bg-white hover:shadow-sm transition" title="Reducir (Ctrl/Cmd + Scroll)">
                <i class="fa-solid fa-minus text-xs"></i>
            </button>
            <span id="zoom-level" class="text-xs font-medium w-12 text-center">100%</span>
            <button id="zoom-in" class="w-8 h-8 flex items-center justify-center rounded text-gray-600 hover:bg-white hover:shadow-sm transition" title="Aumentar (Ctrl/Cmd + Scroll)">
                <i class="fa-solid fa-plus text-xs"></i>
            </button>
        </div>
        <button id="export-pdf-btn" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50 transition shadow-sm flex items-center gap-2">
            <i class="fa-solid fa-file-pdf text-red-500"></i> Descargar PDF
        </button>
        <button id="save-cover-btn" class="bg-black text-white px-5 py-2 rounded-md text-sm font-medium hover:bg-gray-800 transition shadow-sm flex items-center gap-2">
            <i class="fa-solid fa-floppy-disk"></i> Guardar Portada
        </button>
    </div>
</nav>
