<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Workspace -->
<main id="workspace-container" class="flex-1">
    <div id="cover-scaler">
    <div id="cover-spread">
        <!-- Bleed Guide -->
        <div id="bleed-guide" class="absolute border-2 border-dashed border-red-500 pointer-events-none z-20"></div>

        <!-- Back Flap -->
        <div id="back-flap" class="cover-part flex items-center justify-center text-gray-300 overflow-hidden" style="width: 0px; border: none;">
            <span class="text-sm font-semibold uppercase tracking-widest text-gray-200 transform -rotate-90 whitespace-nowrap">Solapa</span>
        </div>

        <!-- Back Cover -->
        <div id="back-cover" class="cover-part flex items-center justify-center text-gray-300 overflow-hidden">
            <span class="text-xl font-semibold uppercase tracking-widest text-gray-200">Contraportada</span>
        </div>
        
        <!-- Spine -->
        <div id="spine" class="cover-part overflow-hidden" title="Lomo">
            <div class="spine-text text-xs text-gray-400 font-semibold uppercase tracking-wider rotate-90 whitespace-nowrap">Lomo</div>
        </div>
        
        <!-- Front Cover -->
        <div id="front-cover" class="cover-part flex items-center justify-center text-gray-300 overflow-hidden">
            <span class="text-xl font-semibold uppercase tracking-widest text-gray-200">Portada</span>
        </div>

        <!-- Front Flap -->
        <div id="front-flap" class="cover-part flex items-center justify-center text-gray-300 overflow-hidden" style="width: 0px; border: none;">
            <span class="text-sm font-semibold uppercase tracking-widest text-gray-200 transform rotate-90 whitespace-nowrap">Solapa</span>
        </div>
    </div>
    </div>
</main>
