<?php
/**
 * AlmadenBookster — Google Fonts Floating Modal Template
 *
 * Renders the floating modal panel for searching, previewing, and installing Google Fonts.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="almaden-google-fonts-modal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 text-gray-800 font-sans" style="display: none;">
	<div class="bg-white w-full max-w-4xl max-h-[85vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-gray-200 animate-in fade-in zoom-in duration-200">
		
		<!-- Modal Header -->
		<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50 to-white">
			<div class="flex items-center gap-3">
				<div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-900 flex items-center justify-center text-xl font-bold">
					<i class="fa-solid fa-font"></i>
				</div>
				<div>
					<h3 class="text-lg font-bold text-gray-900 leading-tight">Explorar e Instalar Google Fonts</h3>
					<p class="text-xs text-gray-500">Agrega tipografías a tu catálogo. Estarán disponibles de inmediato en tus selectores.</p>
				</div>
			</div>
			<button type="button" id="almaden-modal-fonts-close" class="w-8 h-8 rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex items-center justify-center transition">
				<i class="fa-solid fa-xmark text-lg"></i>
			</button>
		</div>

		<!-- Modal Search Bar & Filter -->
		<div class="p-6 pb-4 border-b border-gray-100 bg-gray-50/50 flex flex-wrap items-center gap-3">
			<div class="relative flex-1 min-w-[220px]">
				<i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
				<input type="text" id="almaden-modal-font-search" placeholder="Buscar tipografía por nombre..." class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-black focus:border-black bg-white shadow-sm" />
			</div>

			<select id="almaden-modal-font-sort" class="text-sm border border-gray-300 rounded-xl px-3 py-2 bg-white focus:ring-2 focus:ring-black focus:border-black shadow-sm cursor-pointer">
				<option value="popularity">Más Populares</option>
				<option value="trending">Tendencia</option>
				<option value="alpha">Alfabético (A-Z)</option>
				<option value="date">Más Recientes</option>
			</select>

			<button type="button" id="almaden-modal-load-catalog" class="px-4 py-2 bg-black hover:bg-neutral-800 text-white font-semibold text-sm rounded-xl shadow transition flex items-center gap-2">
				<i class="fa-solid fa-cloud-arrow-down"></i>
				<span>Cargar Catálogo</span>
			</button>
		</div>

		<!-- Modal Body / Content Grid -->
		<div class="flex-1 overflow-y-auto p-6 space-y-6">
			
			<!-- Loading state -->
			<div id="almaden-modal-fonts-loading" class="hidden flex-col items-center justify-center py-12 text-center text-gray-500">
				<div class="w-10 h-10 border-4 border-black border-t-transparent rounded-full animate-spin mb-3"></div>
				<p class="text-sm font-medium">Consultando catálogo de Google Fonts...</p>
			</div>

			<!-- Grid area -->
			<div id="almaden-modal-fonts-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
				<div class="col-span-full py-12 text-center text-gray-400">
					<i class="fa-solid fa-font text-4xl mb-3 text-gray-300 block"></i>
					<p class="text-sm">Haz clic en <strong>"Cargar Catálogo"</strong> o escribe en la búsqueda para explorar fuentes.</p>
				</div>
			</div>

			<!-- Installed list summary -->
			<div class="pt-6 border-t border-gray-100">
				<h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3 flex items-center gap-2">
					<i class="fa-solid fa-bookmark text-gray-700"></i>
					<span>Tus fuentes activas</span>
				</h4>
				<div id="almaden-modal-user-fonts-list" class="flex flex-wrap gap-2">
					<!-- Populated by JS -->
				</div>
			</div>
		</div>


		<!-- Modal Footer -->
		<div class="px-6 py-3 border-t border-gray-100 bg-gray-50 flex items-center justify-between text-xs text-gray-500">
			<span>Las fuentes que instales se asociarán a tu usuario.</span>
			<button type="button" id="almaden-modal-fonts-done-btn" class="px-4 py-1.5 bg-gray-900 hover:bg-black text-white font-semibold rounded-lg transition">
				Listo
			</button>
		</div>

	</div>
</div>
