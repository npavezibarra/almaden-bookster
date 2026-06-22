<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Right Sidebar (Layers Panel) -->
<aside class="w-64 bg-white border-l border-gray-200 flex flex-col shrink-0 shadow-sm z-10 text-gray-800">
    <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
        <h2 class="font-bold text-sm uppercase tracking-wider text-gray-800">Capas (Layers)</h2>
    </div>
    
    <div class="p-2 border-b border-gray-100 grid grid-cols-3 gap-1 bg-white">
        <button id="add-text-layer-btn" class="bg-white border border-gray-300 text-gray-700 py-1.5 rounded text-[10px] font-semibold hover:bg-gray-50 transition flex items-center justify-center gap-0.5 shadow-sm">
            <i class="fa-solid fa-t"></i> Texto
        </button>
        <button id="add-image-layer-btn" class="bg-white border border-gray-300 text-gray-700 py-1.5 rounded text-[10px] font-semibold hover:bg-gray-50 transition flex items-center justify-center gap-0.5 shadow-sm">
            <i class="fa-regular fa-image"></i> Imagen
        </button>
        <button id="add-shape-layer-btn" class="bg-white border border-gray-300 text-gray-700 py-1.5 rounded text-[10px] font-semibold hover:bg-gray-50 transition flex items-center justify-center gap-0.5 shadow-sm">
            <i class="fa-solid fa-shapes"></i> Forma
        </button>
    </div>

    <!-- Group Action Panel -->
    <div class="p-2 border-b border-gray-100 bg-white">
        <button id="group-layers-btn" class="w-full bg-indigo-600 border border-indigo-700 text-white py-1.5 rounded text-xs font-semibold hover:bg-indigo-700 transition flex items-center justify-center gap-1.5 shadow-sm shadow-indigo-100">
            <i class="fa-solid fa-folder-plus"></i> Agrupar Capas
        </button>
    </div>

    <div id="layers-list" class="flex-1 overflow-y-auto bg-gray-50 flex flex-col p-2 gap-1">
        <!-- Layers will be rendered here via JS -->
    </div>
</aside>
