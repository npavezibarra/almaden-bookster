<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Right Sidebar (Layers Panel) -->
<aside class="w-64 bg-white border-l border-gray-200 flex flex-col shrink-0 shadow-sm z-10 text-gray-800 min-h-0">
    <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
        <h2 class="font-bold text-sm uppercase tracking-wider text-gray-800">Capas (Layers)</h2>
    </div>
    
    <div class="p-2 border-b border-gray-100 grid grid-cols-3 gap-1 bg-white">
        <button id="add-text-layer-btn" class="bg-white border border-gray-300 text-gray-700 py-1 rounded font-semibold hover:bg-gray-50 transition flex items-center justify-center shadow-sm" style="font-size: 12px !important;" title="Texto">
            <i class="fa-solid fa-t"></i>
        </button>
        <button id="add-image-layer-btn" class="bg-white border border-gray-300 text-gray-700 py-1 rounded font-semibold hover:bg-gray-50 transition flex items-center justify-center shadow-sm" style="font-size: 12px !important;" title="Imagen">
            <i class="fa-regular fa-image"></i>
        </button>
        <button id="add-shape-layer-btn" class="bg-white border border-gray-300 text-gray-700 py-1 rounded font-semibold hover:bg-gray-50 transition flex items-center justify-center shadow-sm" style="font-size: 12px !important;" title="Forma">
            <i class="fa-solid fa-shapes"></i>
        </button>
    </div>

    <!-- Group Action Panel -->
    <div class="p-2 border-b border-gray-100 bg-white" id="group-layers-btn-container" title="Selecciona al menos una capa para agrupar">
        <button id="group-layers-btn" class="w-full bg-black text-white rounded font-semibold transition flex items-center justify-center shadow-sm opacity-50 cursor-not-allowed" style="font-size: 10px !important; padding: 4px 0 !important; gap: 4px !important; color: #fff !important;" title="Selecciona al menos una capa para agrupar" disabled>
            <i class="fa-solid fa-folder-plus"></i> Agrupar Capas
        </button>
    </div>

    <div id="layers-list" class="flex-1 min-h-0 overflow-y-auto bg-gray-50 flex flex-col p-2 pb-4 gap-1">
        <!-- Layers will be rendered here via JS -->
    </div>
</aside>
