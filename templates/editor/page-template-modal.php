<div id="page-template-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 p-4 opacity-0 backdrop-blur-sm transition-opacity duration-200" aria-hidden="true">
    <div data-page-template-dialog class="w-full max-w-md scale-95 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl transition-transform duration-200">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-amber-600">Plantilla de página</p>
                <h3 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">Aplicar a la página <span id="page-template-target-page">-</span></h3>
            </div>
            <button type="button" data-page-template-close class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-6">
            <button type="button" data-page-template-option="one-column-one-image" class="group w-full rounded-2xl border-2 border-amber-500 bg-amber-50 p-4 text-left transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <span class="text-sm font-extrabold text-slate-900">1 col 1 image</span>
                        <p class="mt-1 text-xs leading-5 text-slate-600">Texto a la izquierda y placeholder de imagen a la derecha.</p>
                    </div>
                    <div class="grid h-14 w-20 grid-cols-[0.4fr_0.6fr] gap-1 rounded-lg border border-slate-400 bg-white p-1">
                        <span class="rounded-sm bg-slate-300"></span>
                        <span class="rounded-sm bg-amber-500"></span>
                    </div>
                </div>
            </button>
            <p class="mt-4 text-xs leading-5 text-slate-500">La imagen se agregará en una fase posterior. El texto se recompondrá con Typst al aplicar la plantilla.</p>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button id="page-template-images" type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100" onclick="window.almadenPageTemplateImagesUI?.openModal()">
                Imágenes
            </button>
            <button id="page-template-remove" type="button" class="hidden rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 hover:text-rose-700">
                Quitar plantilla
            </button>
            <button type="button" data-page-template-close class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-200">Cancelar</button>
            <button id="page-template-confirm" type="button" class="rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white transition hover:bg-neutral-800">Aplicar plantilla</button>
        </div>
    </div>
</div>
