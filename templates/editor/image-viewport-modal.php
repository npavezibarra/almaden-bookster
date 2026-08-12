<!-- VIEWPORT EDITOR DE IMAGEN -->
<div id="image-viewport-modal" class="fixed inset-0 z-50 hidden opacity-0 bg-slate-900/60 backdrop-blur-sm transition-all duration-200 no-print" onclick="if (event.target === this) closeImageViewportModal();">
    <div data-image-viewport-panel class="absolute left-1/2 top-4 w-[min(820px,calc(100vw-1.5rem))] -translate-x-1/2 rounded-[24px] bg-white shadow-2xl border border-slate-200 scale-95 transition-transform duration-200 overflow-hidden flex flex-col max-h-[calc(100vh-2rem)]">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
            <div>
                <p class="text-[11px] uppercase tracking-[0.2em] text-slate-400 font-semibold">Imagen del libro</p>
                <h3 id="image-viewport-title" class="text-xl md:text-2xl font-extrabold tracking-tight text-slate-900">Agregar imagen</h3>
                <div class="mt-2 flex items-center gap-2 text-[11px] font-bold" aria-label="Etapas del editor de imagen">
                    <button id="image-viewport-library-step" type="button" onclick="openImageMediaPicker()" class="rounded-full bg-slate-900 px-3 py-1 text-white">1. Biblioteca</button>
                    <span class="text-slate-300"><i class="fa-solid fa-chevron-right"></i></span>
                    <button id="image-viewport-adjust-step" type="button" onclick="showImageViewportAdjustStage()" class="rounded-full bg-slate-100 px-3 py-1 text-slate-500 disabled:cursor-not-allowed" disabled>2. Ajustar</button>
                </div>
            </div>
            <button type="button" onclick="closeImageViewportModal()" class="w-9 h-9 rounded-full border border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition flex items-center justify-center" title="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="image-viewport-library-stage" class="p-5 flex-1 overflow-y-auto">
            <div class="flex flex-wrap items-center gap-3">
                <label class="flex min-w-[240px] flex-1 items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
                    <input id="image-viewport-media-search" type="search" oninput="filterImageViewportLibrary(this.value)" class="w-full border-0 bg-transparent p-0 text-sm outline-none focus:ring-0" placeholder="Buscar en las imágenes del libro..." />
                </label>
                <input id="image-viewport-media-file" type="file" class="hidden" accept="image/*" />
                <button type="button" onclick="document.getElementById('image-viewport-media-file').click()" class="inline-flex items-center gap-2 rounded-2xl bg-black px-5 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                    <i class="fa-solid fa-upload"></i>
                    <span>Subir imagen</span>
                </button>
            </div>
            <div id="image-viewport-media-status" class="hidden mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900"></div>
            <div id="image-viewport-media-loading" class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">Cargando imágenes del libro...</div>
            <div id="image-viewport-media-empty" class="hidden mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                No hay imágenes en esta carpeta. Puedes subir la primera con un solo clic.
            </div>
            <div id="image-viewport-media-grid" class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4"></div>
        </div>

        <div id="image-viewport-adjust-stage" class="hidden p-5 flex-1 overflow-y-auto">
            <div class="grid gap-4 xl:grid-cols-[260px_minmax(0,1fr)]">
                <aside id="image-viewport-controls" class="space-y-3">
                    <div class="rounded-[20px] border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-sm font-bold text-slate-900">Zoom</h4>
                            <span id="image-viewport-zoom-value" class="rounded-full bg-white px-2 py-1 text-[11px] font-semibold text-slate-500">1.00x</span>
                        </div>
                        <input id="image-viewport-zoom" type="range" min="0.5" max="2.5" step="0.01" value="1" oninput="updateImageViewportControl('zoom', this.value)" class="w-full accent-black">
                        <button id="image-viewport-reset-transform" type="button" onclick="resetImageViewportTransform()" class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">Recentrar imagen</button>
                    </div>

                    <div class="rounded-[20px] border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-sm font-bold text-slate-900">Altura del recorte</h4>
                            <span id="image-viewport-height-value" class="rounded-full bg-white px-2 py-1 text-[11px] font-semibold text-slate-500">Automática</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" data-image-viewport-mode="auto" onclick="updateImageViewportControl('heightMode', 'auto')" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">Auto</button>
                            <button type="button" data-image-viewport-mode="fixed" onclick="updateImageViewportControl('heightMode', 'fixed')" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">Manual</button>
                        </div>
                        <input id="image-viewport-height-mode" type="hidden" value="auto">
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" data-image-viewport-preset="25" onclick="updateImageViewportControl('heightPercent', '25')" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold text-slate-600">25%</button>
                            <button type="button" data-image-viewport-preset="35" onclick="updateImageViewportControl('heightPercent', '35')" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold text-slate-600">35%</button>
                            <button type="button" data-image-viewport-preset="50" onclick="updateImageViewportControl('heightPercent', '50')" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold text-slate-600">50%</button>
                            <button type="button" data-image-viewport-preset="65" onclick="updateImageViewportControl('heightPercent', '65')" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold text-slate-600">65%</button>
                        </div>
                        <input id="image-viewport-height" type="range" min="15" max="90" step="1" value="45" oninput="updateImageViewportControl('heightPercent', this.value)" class="mt-3 w-full accent-black">
                        <p id="image-viewport-height-limit" class="mt-2 text-xs font-semibold text-slate-500">Límite calculado: 100%</p>
                        <p id="image-viewport-height-warning" class="hidden mt-2 text-xs font-semibold text-amber-700"></p>
                    </div>

                    <div class="rounded-[20px] border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-3 flex items-center justify-between">
                            <h4 class="text-sm font-bold text-slate-900">Encuadre fino</h4>
                            <span class="text-[11px] font-semibold text-slate-400">Arrastra o usa sliders</span>
                        </div>
                        <label class="block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Horizontal</label>
                        <input id="image-viewport-position-x" type="range" min="0" max="100" step="0.5" value="50" oninput="updateImageViewportControl('positionX', this.value)" class="mt-2 w-full accent-black">
                        <label class="mt-3 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Vertical</label>
                        <input id="image-viewport-position-y" type="range" min="0" max="100" step="0.5" value="50" oninput="updateImageViewportControl('positionY', this.value)" class="mt-2 w-full accent-black">
                    </div>

                    <div class="rounded-[20px] border border-slate-200 bg-slate-50 p-3">
                        <h4 class="mb-3 text-sm font-bold text-slate-900">Espacio y pie</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="text-xs font-semibold text-slate-600">Superior (mm)<input id="image-viewport-margin-top" type="number" min="0" max="30" step="0.5" oninput="updateImageViewportControl('marginTopMm', this.value)" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Inferior (mm)<input id="image-viewport-margin-bottom" type="number" min="0" max="30" step="0.5" oninput="updateImageViewportControl('marginBottomMm', this.value)" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Pie (mm)<input id="image-viewport-caption-gap" type="number" min="0" max="10" step="0.5" oninput="updateImageViewportControl('captionGapMm', this.value)" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Alinear<select id="image-viewport-caption-align" onchange="updateImageViewportControl('captionAlign', this.value)" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"><option value="left">Izquierda</option><option value="center">Centro</option><option value="right">Derecha</option></select></label>
                        </div>
                    </div>
                </aside>

                <div class="min-w-0">
                    <div id="image-viewport-preview-frame" class="relative min-h-[430px] overflow-hidden rounded-[26px] border border-slate-200 bg-[radial-gradient(circle_at_top,_rgba(241,245,249,0.95),_rgba(226,232,240,0.72)_42%,_rgba(203,213,225,0.86)_100%)] p-4 shadow-inner">
                        <div class="absolute inset-x-4 top-4 z-10 flex items-center justify-between gap-3">
                            <div class="rounded-full bg-white/85 px-3 py-1 text-[11px] font-semibold text-slate-500 shadow-sm">Arrastra la imagen dentro del encuadre</div>
                            <div id="image-viewport-fit-badges" class="flex items-center gap-2">
                                <button type="button" data-image-viewport-fit="cover" onclick="updateImageViewportControl('fit', 'cover')" class="rounded-full border border-slate-200 bg-white/90 px-3 py-1 text-[11px] font-semibold text-slate-600">Cover</button>
                                <button type="button" data-image-viewport-fit="contain" onclick="updateImageViewportControl('fit', 'contain')" class="rounded-full border border-slate-200 bg-white/90 px-3 py-1 text-[11px] font-semibold text-slate-600">Contain</button>
                            </div>
                        </div>

                        <div id="image-viewport-preview-viewport-shell" class="absolute inset-4 flex items-center justify-center">
                            <div id="image-viewport-page-shell" class="relative w-full max-w-full overflow-hidden rounded-[22px] border border-white/70 bg-white shadow-[0_25px_80px_rgba(15,23,42,0.16)]">
                                <div id="image-viewport-preview-viewport" class="absolute left-0 top-0 overflow-hidden cursor-grab" style="width: 100%; height: auto;">
                                <img id="image-viewport-preview-image" class="hidden w-full h-full select-none" alt="" draggable="false" />
                                <div id="image-viewport-preview-grid" class="pointer-events-none absolute inset-0 hidden">
                                    <div class="absolute left-1/3 top-0 h-full w-px bg-white/60"></div>
                                    <div class="absolute left-2/3 top-0 h-full w-px bg-white/60"></div>
                                    <div class="absolute left-0 top-1/3 h-px w-full bg-white/60"></div>
                                    <div class="absolute left-0 top-2/3 h-px w-full bg-white/60"></div>
                                </div>
                                <div class="pointer-events-none absolute inset-0 ring-1 ring-black/5"></div>
                                </div>
                            </div>
                        </div>

                        <div id="image-viewport-empty-state" class="absolute inset-0 flex items-center justify-center text-center px-4 py-6">
                            <p class="text-sm font-semibold text-slate-500">Selecciona una imagen en la biblioteca.</p>
                        </div>
                    </div>

                    <p class="mt-2 text-xs text-slate-500">El encuadre que ves aquí es el que usamos para el PDF. En modo manual puedes recortar con zoom y altura; en modo automático respetamos la proporción completa.</p>

                    <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <label for="image-viewport-caption" class="text-sm font-bold text-slate-900">Pie de foto</label>
                            <span id="image-viewport-caption-count" class="text-xs font-semibold text-slate-500">0/50 palabras</span>
                        </div>
                        <textarea id="image-viewport-caption" rows="2" maxlength="600" oninput="updateImageViewportControl('caption', this.value)" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 resize-none" placeholder="Ej.: James Joyce en París, 1924."></textarea>
                        <p class="mt-1.5 text-xs text-slate-500">Este texto será visible debajo de la imagen en el PDF.</p>
                        <p id="image-viewport-caption-warning" class="hidden mt-2 text-xs font-semibold text-rose-600">Máximo 50 palabras.</p>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button id="image-viewport-change-btn" type="button" onclick="openImageMediaPicker()" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-100 transition flex items-center gap-2"><i class="fa-solid fa-images"></i><span>Cambiar imagen</span></button>
                <button id="image-viewport-transform-btn" type="button" onclick="resetImageViewportTransform()" class="px-3 py-2 rounded-xl border border-sky-200 text-sky-700 text-xs font-semibold hover:bg-sky-50 transition flex items-center gap-2"><i class="fa-solid fa-crop-simple"></i><span id="image-viewport-transform-label">Reiniciar encuadre</span></button>
                <button id="image-viewport-remove-btn" type="button" onclick="removeCurrentImageBlock()" class="px-3 py-2 rounded-xl border border-rose-200 text-rose-700 text-xs font-semibold hover:bg-rose-50 transition flex items-center gap-2"><i class="fa-solid fa-trash"></i><span>Eliminar</span></button>
                <button id="image-viewport-save-btn" type="button" onclick="saveImageViewportState()" class="ml-auto px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition flex items-center gap-2"><i class="fa-solid fa-floppy-disk"></i><span id="image-viewport-save-label">Guardar imagen</span></button>
            </div>
        </div>
    </div>
</div>
