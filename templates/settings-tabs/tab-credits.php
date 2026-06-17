<div id="tab-credits" class="setting-tab-content space-y-5 hidden">
    <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-4 shadow-sm">
        <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
            <i class="fa-solid fa-list-check text-[10px]"></i> Configuración de Página de Créditos
        </h4>
        
        <p class="text-[10px] text-[var(--text-muted)] mt-1 mb-3">
            Para que esta página aparezca en tu libro, debes añadir un nuevo capítulo y marcar la opción "Créditos" en sus ajustes.
        </p>

        <!-- Campos Fijos -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Número de Edición</label>
                <input id="setting-credits-edition" type="text" placeholder="Ej: Primera Edición" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Fecha de Publicación</label>
                <input id="setting-credits-date" type="text" placeholder="Ej: Mayo 2024" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Advertencia de Copyright</label>
            <textarea id="setting-credits-copyright" rows="3" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-2 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">Queda rigurosamente prohibida, sin la autorización escrita de los titulares del "copyright", bajo las sanciones establecidas en las leyes, la reproducción parcial o total de esta obra por cualquier medio o procedimiento.</textarea>
        </div>

        <div>
            <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Imprenta</label>
            <input id="setting-credits-printer" type="text" placeholder="Ej: Impreso en Chile por XXXXX" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Páginas Blancas Anteriores</label>
                <input id="setting-credits-blank-before" type="number" min="0" value="0" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-[9px] font-semibold text-[var(--text-muted)] mb-1">Páginas Blancas Posteriores</label>
                <input id="setting-credits-blank-after" type="number" min="0" value="0" class="w-full bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
    </div>

    <!-- Créditos Dinámicos -->
    <div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] shadow-sm">
        <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wider flex items-center justify-between border-b border-[var(--border-color)] pb-2 mb-3">
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-users text-[10px]"></i> Créditos Personalizados</span>
            <button type="button" onclick="addCustomCreditRow()" class="px-2 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded text-[10px] font-bold transition-colors">
                <i class="fa-solid fa-plus mr-1"></i> Añadir Rol
            </button>
        </h4>
        
        <div id="custom-credits-container" class="space-y-2">
            <!-- Dynamic rows will be inserted here -->
        </div>
        
        <!-- Hidden input to store JSON data -->
        <input type="hidden" id="setting-credits-custom-json" value="[]">
    </div>
</div>
