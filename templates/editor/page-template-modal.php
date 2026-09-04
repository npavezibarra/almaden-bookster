<div id="page-template-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 p-4 opacity-0 backdrop-blur-sm transition-opacity duration-200" aria-hidden="true">
    <div data-page-template-dialog class="flex w-full max-w-4xl scale-95 flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl transition-transform duration-200">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-600">Configuración de página</p>
                <h3 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">Editar página <span id="page-template-target-page">-</span></h3>
            </div>
            <button type="button" data-page-template-close class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="border-b border-slate-200 px-6">
            <div class="flex gap-2 overflow-x-auto py-3">
                <button type="button" data-page-template-tab-button="template" class="rounded-full border border-slate-200 bg-black px-4 py-2 text-xs font-bold text-white transition whitespace-nowrap">
                    Plantilla
                </button>
                <button type="button" data-page-template-tab-button="style" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-100 whitespace-nowrap">
                    Estilo
                </button>
            </div>
        </div>

        <div class="max-h-[64vh] overflow-y-auto px-6 py-6">
            <div data-page-template-panel="template" class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-amber-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700">Plantilla activa</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700">La plantilla cambia la estructura de la página seleccionada y define qué imagen o imágenes se reservan para ese bloque.</p>
                </div>

                <div id="page-template-options" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                        Cargando plantillas...
                    </div>
                </div>

                <p class="text-xs leading-5 text-slate-500">La vista previa muestra la estructura general de cada plantilla. La imagen se podrá asignar después desde el panel de imágenes y el texto se recompone con Typst al aplicar el cambio.</p>
            </div>

            <div data-page-template-panel="style" class="hidden space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-700">Estilo por página</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">El estilo actúa sobre la apariencia visual de esta página seleccionada y puede coexistir con una plantilla.</p>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                            <div>
                                <p class="text-sm font-extrabold text-slate-900">Fondo</p>
                                <p class="text-xs text-slate-500">Color, gradient o imagen.</p>
                            </div>
                            <select id="page-style-background-type" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-black">
                                <option value="color">Color</option>
                                <option value="gradient">Gradient</option>
                                <option value="image">Image</option>
                            </select>
                        </div>

                        <div data-page-style-section="color" class="space-y-2">
                            <label class="block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Color de fondo</label>
                            <div class="flex items-center gap-3">
                                <input id="page-style-background-color" type="color" value="#ffffff" class="h-11 w-14 cursor-pointer rounded-xl border border-slate-200 bg-white p-1">
                                <div class="flex-1">
                                    <input id="page-style-background-color-text" type="text" value="#FFFFFF" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs uppercase text-slate-700 focus:outline-none focus:ring-2 focus:ring-black">
                                </div>
                            </div>
                        </div>

                        <div data-page-style-section="gradient" class="hidden space-y-3">
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 mb-2">Gradient</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <span class="mb-1 block text-[10px] font-semibold text-slate-500">Color 1</span>
                                        <input id="page-style-gradient-from" type="color" value="#ffffff" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1">
                                    </div>
                                    <div>
                                        <span class="mb-1 block text-[10px] font-semibold text-slate-500">Color 2</span>
                                        <input id="page-style-gradient-to" type="color" value="#f3f4f6" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1">
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold text-slate-500">Ángulo</label>
                                <input id="page-style-gradient-angle" type="number" min="0" max="360" step="1" value="135" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700 focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                        </div>

                        <div data-page-style-section="image" class="hidden space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <label class="block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Imagen de fondo</label>
                                    <p class="text-[10px] text-slate-500">Se reutiliza la biblioteca multimedia.</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" id="page-style-background-image-select" class="rounded-xl bg-black px-3 py-2 text-xs font-semibold text-white transition hover:bg-neutral-800">Elegir imagen</button>
                                    <button type="button" id="page-style-background-image-clear" class="rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">Quitar</button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white">
                                    <img id="page-style-background-image-preview" alt="" class="hidden h-full w-full object-cover">
                                    <span id="page-style-background-image-empty" class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Vacío</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-slate-700">Imagen seleccionada</p>
                                    <p id="page-style-background-image-label" class="mt-1 break-all text-[11px] text-slate-500">No hay imagen cargada.</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-500">Overlay</label>
                                    <input id="page-style-background-overlay-color" type="color" value="#000000" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1">
                                </div>
                                <div>
                                    <label class="mb-1 block text-[10px] font-semibold text-slate-500">Opacidad</label>
                                    <input id="page-style-background-overlay-opacity" type="range" min="0" max="1" step="0.01" value="0.35" class="w-full">
                                    <p class="mt-1 text-[10px] text-slate-500">Valor: <span id="page-style-background-overlay-opacity-value">0.35</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div>
                            <p class="text-sm font-extrabold text-slate-900">Colores de texto</p>
                            <p class="text-xs text-slate-500">Cada zona puede heredar o recibir su propio color.</p>
                        </div>

                        <div class="flex gap-2 overflow-x-auto border-b border-slate-100 pb-2">
                            <button type="button" data-page-style-text-tab-button="general" class="rounded-full border border-slate-200 bg-black px-3 py-1.5 text-xs font-bold text-white transition whitespace-nowrap">
                                General
                            </button>
                            <button type="button" data-page-style-text-tab-button="opening" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-100 whitespace-nowrap">
                                Apertura
                            </button>
                        </div>

                        <div data-page-style-text-panel="general" class="grid gap-3">
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Content</label>
                                <input id="page-style-text-color-content" type="color" value="#111111" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1">
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Cabecera</label>
                                <input id="page-style-text-color-header" type="color" value="#111111" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1">
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Pie</label>
                                <input id="page-style-text-color-footer" type="color" value="#111111" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1">
                            </div>
                        </div>

                        <div data-page-style-text-panel="opening" class="hidden grid gap-3">
                            <div>
								<label class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Prefijo del capítulo</label>
                                <input id="page-style-text-color-opening-prefix" type="color" value="#111111" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1">
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Título</label>
                                <input id="page-style-text-color-opening-title" type="color" value="#111111" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1">
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">Metadata</label>
                                <input id="page-style-text-color-opening-subtitle" type="color" value="#111111" class="h-11 w-full cursor-pointer rounded-xl border border-slate-200 bg-white p-1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-end">
            <div data-page-template-footer="template" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <button id="page-template-images" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100" onclick="window.almadenPageTemplateImagesUI?.openModal()">
                    Imágenes
                </button>
                <button id="page-template-remove" type="button" class="hidden rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-700">
                    Quitar plantilla
                </button>
                <button data-page-template-reset type="button" class="hidden rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-700">
                    Reset
                </button>
                <button type="button" data-page-template-close class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                <button id="page-template-confirm" type="button" class="rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white transition hover:bg-neutral-800">Aplicar plantilla</button>
            </div>

            <div data-page-template-footer="style" class="hidden flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <button id="page-style-remove" type="button" class="hidden rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-700">
                    Quitar estilo
                </button>
                <button data-page-template-reset type="button" class="hidden rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-700">
                    Reset
                </button>
                <button type="button" data-page-template-close class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Cancelar</button>
                <button id="page-style-confirm" type="button" class="rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white transition hover:bg-neutral-800">Guardar estilo</button>
            </div>
        </div>
    </div>
</div>
