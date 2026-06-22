<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- Sidebar Izquierdo -->
<aside class="w-72 bg-white border-r border-gray-200 flex flex-col shrink-0 shadow-sm z-10 overflow-y-auto text-gray-800">
    <!-- Section: Imágenes -->
    <div class="p-4 border-b border-gray-100 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition select-none" id="toggle-images-section">
        <h2 class="font-bold text-sm uppercase tracking-wider text-gray-800">Imágenes</h2>
        <i class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-200" id="images-section-icon"></i>
    </div>
    
    <div class="hidden flex-col gap-6 bg-white pb-4" id="images-section-content">
        <div class="px-4 pt-4">
            <p class="text-xs text-gray-500">Asigna las imágenes para la cubierta del libro.</p>
        </div>
        
        <div class="px-4 flex flex-col gap-6">
            <!-- Portada -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Portada (Front Cover)</label>
                <button type="button" id="btn-front-cover" class="block w-full text-sm font-semibold bg-gray-100 text-gray-700 py-2 px-4 rounded-md border border-gray-300 hover:bg-gray-200 transition mb-2 text-center">
                    <i class="fa-solid fa-image mr-1"></i> Seleccionar Imagen
                </button>
                <input type="hidden" id="upload-front-cover" />
                <button id="clear-front-cover" class="hidden text-xs text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash mr-1"></i> Eliminar Portada</button>
            </div>
            
            <div class="h-px bg-gray-200"></div>

            <!-- Contraportada -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Contraportada (Back Cover)</label>
                <button type="button" id="btn-back-cover" class="block w-full text-sm font-semibold bg-gray-100 text-gray-700 py-2 px-4 rounded-md border border-gray-300 hover:bg-gray-200 transition mb-2 text-center">
                    <i class="fa-solid fa-image mr-1"></i> Seleccionar Imagen
                </button>
                <input type="hidden" id="upload-back-cover" />
                <button id="clear-back-cover" class="hidden text-xs text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash mr-1"></i> Eliminar Contraportada</button>
            </div>

            <div class="h-px bg-gray-200"></div>

            <!-- Lomo -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Lomo (Spine)</label>
                <div class="flex gap-2 mb-2">
                    <div class="flex-1">
                        <button type="button" id="btn-spine-image" class="block w-full text-xs font-semibold bg-gray-100 text-gray-700 py-2 px-2 rounded-md border border-gray-300 hover:bg-gray-200 transition text-center">
                            <i class="fa-solid fa-image mr-1"></i> Imagen
                        </button>
                        <input type="hidden" id="upload-spine-image" />
                    </div>
                    <div class="flex items-center gap-1 border border-gray-300 rounded-md px-2 bg-gray-50 hover:bg-gray-100 transition">
                        <i class="fa-solid fa-fill-drip text-gray-400 text-xs"></i>
                        <input type="color" id="spine-color-picker" value="#f9fafb" class="block w-6 h-6 p-0 border-0 rounded cursor-pointer bg-transparent" title="Color de Fondo" />
                    </div>
                </div>
                <button id="clear-spine" class="hidden text-xs text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash mr-1"></i> Limpiar Lomo</button>
            </div>

            <div class="h-px bg-gray-200"></div>

            <!-- Spread Completo -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Spread Completo</label>
                <p class="text-xs text-gray-500 mb-3">Reemplaza portada, contraportada y lomo con una sola imagen.</p>
                <button type="button" id="btn-full-spread" class="block w-full text-sm font-semibold bg-gray-100 text-gray-700 py-2 px-4 rounded-md border border-gray-300 hover:bg-gray-200 transition mb-2 text-center">
                    <i class="fa-solid fa-image mr-1"></i> Seleccionar Imagen
                </button>
                <input type="hidden" id="upload-full-spread" />
                <button id="clear-full-spread" class="hidden text-xs text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash mr-1"></i> Eliminar Spread</button>
            </div>
        </div>
    </div>

    <!-- Section: Solapas -->
    <div class="p-4 border-b border-gray-100 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition select-none border-t" id="toggle-flaps-section">
        <h2 class="font-bold text-sm uppercase tracking-wider text-gray-800">Solapas</h2>
        <i class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-200 -rotate-90" id="flaps-section-icon"></i>
    </div>
    
    <div class="hidden flex-col gap-6 bg-white pb-4" id="flaps-section-content">
        <div class="px-4 pt-4">
            <p class="text-xs text-gray-500">Agrega solapas a tu cubierta (ancho en mm).</p>
        </div>
        
        <div class="px-4 flex flex-col gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Solapa Portada (mm)</label>
                <input type="number" id="front-flap-width" value="0" min="0" class="block w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:ring-black focus:border-black mb-2" />
                <div class="flex gap-2 mb-2">
                    <div class="flex-1">
                        <button type="button" id="btn-front-flap-image" class="block w-full text-xs font-semibold bg-gray-100 text-gray-700 py-2 px-2 rounded-md border border-gray-300 hover:bg-gray-200 transition text-center">
                            <i class="fa-solid fa-image mr-1"></i> Imagen
                        </button>
                        <input type="hidden" id="upload-front-flap-image" />
                    </div>
                    <div class="flex items-center gap-1 border border-gray-300 rounded-md px-2 bg-gray-50 hover:bg-gray-100 transition">
                        <i class="fa-solid fa-fill-drip text-gray-400 text-xs"></i>
                        <input type="color" id="front-flap-color-picker" value="#ffffff" class="block w-6 h-6 p-0 border-0 rounded cursor-pointer bg-transparent" title="Color de Fondo" />
                    </div>
                </div>
                <button id="clear-front-flap" class="hidden text-xs text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash mr-1"></i> Limpiar Fondo</button>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Solapa Contraportada (mm)</label>
                <input type="number" id="back-flap-width" value="0" min="0" class="block w-full text-sm border border-gray-300 rounded-md px-3 py-2 focus:ring-black focus:border-black mb-2" />
                <div class="flex gap-2 mb-2">
                    <div class="flex-1">
                        <button type="button" id="btn-back-flap-image" class="block w-full text-xs font-semibold bg-gray-100 text-gray-700 py-2 px-2 rounded-md border border-gray-300 hover:bg-gray-200 transition text-center">
                            <i class="fa-solid fa-image mr-1"></i> Imagen
                        </button>
                        <input type="hidden" id="upload-back-flap-image" />
                    </div>
                    <div class="flex items-center gap-1 border border-gray-300 rounded-md px-2 bg-gray-50 hover:bg-gray-100 transition">
                        <i class="fa-solid fa-fill-drip text-gray-400 text-xs"></i>
                        <input type="color" id="back-flap-color-picker" value="#ffffff" class="block w-6 h-6 p-0 border-0 rounded cursor-pointer bg-transparent" title="Color de Fondo" />
                    </div>
                </div>
                <button id="clear-back-flap" class="hidden text-xs text-red-600 hover:text-red-800 font-medium"><i class="fa-solid fa-trash mr-1"></i> Limpiar Fondo</button>
            </div>
        </div>
    </div>

    <!-- Section: Textos y Capas -->
    <div class="p-4 border-b border-gray-100 flex justify-between items-center cursor-pointer hover:bg-gray-50 transition select-none border-t" id="toggle-texts-section">
        <h2 class="font-bold text-sm uppercase tracking-wider text-gray-800">Textos y Capas</h2>
        <i class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-200 -rotate-90" id="texts-section-icon"></i>
    </div>

    <div class="hidden flex-col gap-4 bg-white pb-4" id="texts-section-content">
        <div class="px-4 pt-4">
            <p class="text-[10px] text-gray-500 leading-tight">Haz clic en un texto de la portada o en el panel de capas (derecha) para editar sus propiedades.</p>
        </div>
        
        <!-- Text & Image & Shape Properties Panel -->
        <div id="text-properties-panel" class="hidden px-4 flex-col gap-3 pt-2 border-t border-gray-100">
            <div class="flex justify-between items-center mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-black">Propiedades</span>
                <button id="delete-text-btn" class="text-red-500 hover:text-red-700" title="Eliminar Capa">
                    <i class="fa-solid fa-trash text-sm"></i>
                </button>
            </div>

            <div class="text-only-prop">
                <label class="block text-xs font-semibold text-gray-700 mb-1">Contenido</label>
                <textarea id="prop-text-content" rows="2" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black"></textarea>
            </div>

            <div class="text-only-prop">
                <label class="block text-xs font-semibold text-gray-700 mb-1">Tipografía</label>
                <select id="prop-font-family" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black">
                    <!-- Populated via JS -->
                </select>
            </div>

            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Rotación (°)</label>
                    <input type="number" id="prop-rotation" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black" />
                </div>
                <div class="flex-1 text-only-prop">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Tamaño (px)</label>
                    <input type="number" id="prop-font-size" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black" />
                </div>
            </div>

            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Ancho (px)</label>
                    <input type="number" id="prop-width" placeholder="Auto" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black" />
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Alto (px)</label>
                    <input type="number" id="prop-height" placeholder="Auto" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black" />
                </div>
            </div>

            <div class="text-only-prop">
                <label class="block text-xs font-semibold text-gray-700 mb-1">Color</label>
                <div class="flex items-center gap-1">
                    <input type="color" id="prop-text-color" class="block w-8 h-8 p-0 border-0 rounded cursor-pointer" />
                    <input type="text" id="prop-text-color-hex" class="flex-1 text-xs border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black" />
                </div>
            </div>

            <div class="text-only-prop">
                <label class="block text-xs font-semibold text-gray-700 mb-1">Alineación</label>
                <div class="flex bg-gray-100 rounded-md p-1 gap-1">
                    <button class="prop-align-btn flex-1 py-1 rounded hover:bg-white text-gray-600 transition" data-align="left" title="Izquierda">
                        <i class="fa-solid fa-align-left text-xs"></i>
                    </button>
                    <button class="prop-align-btn flex-1 py-1 rounded hover:bg-white text-gray-600 transition" data-align="center" title="Centro">
                        <i class="fa-solid fa-align-center text-xs"></i>
                    </button>
                    <button class="prop-align-btn flex-1 py-1 rounded hover:bg-white text-gray-600 transition" data-align="right" title="Derecha">
                        <i class="fa-solid fa-align-right text-xs"></i>
                    </button>
                    <button class="prop-align-btn flex-1 py-1 rounded hover:bg-white text-gray-600 transition" data-align="justify" title="Justificar">
                        <i class="fa-solid fa-align-justify text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2 mt-1 text-only-prop">
                <input type="checkbox" id="prop-hyphens" class="rounded border-gray-300 text-black focus:ring-black cursor-pointer" />
                <label for="prop-hyphens" class="text-xs font-semibold text-gray-700 cursor-pointer">Separación por sílabas (guiones)</label>
            </div>

            <!-- SHAPE PROPERTIES -->
            <div class="shape-only-prop hidden">
                <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo de Forma</label>
                <select id="prop-shape-type" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black">
                    <option value="rectangle">Rectángulo</option>
                    <option value="circle">Círculo</option>
                </select>
            </div>

            <div class="shape-only-prop hidden">
                <label class="block text-xs font-semibold text-gray-700 mb-1">Opacidad (%)</label>
                <input type="range" id="prop-shape-opacity" min="0" max="100" value="100" class="w-full accent-indigo-600" />
                <div class="text-xs text-center text-gray-500 mt-1" id="prop-shape-opacity-val">100%</div>
            </div>

            <div class="shape-only-prop hidden">
                <label class="block text-xs font-semibold text-gray-700 mb-2">Fondo (Background)</label>
                
                <div class="flex items-center gap-2 mb-2">
                    <input type="checkbox" id="prop-shape-is-gradient" class="rounded border-gray-300 text-black focus:ring-black cursor-pointer" />
                    <label for="prop-shape-is-gradient" class="text-xs font-semibold text-gray-700 cursor-pointer">Usar Degradado Lineal</label>
                </div>
                
                <div class="flex items-center justify-between gap-2 mb-2">
                    <div class="flex flex-col items-center flex-1">
                        <span class="text-[10px] text-gray-500 uppercase font-bold mb-1" id="label-color-1">Color 1</span>
                        <input type="color" id="prop-shape-color1" value="#000000" class="block w-full h-8 p-0 border-0 rounded cursor-pointer mb-1" />
                        <input type="range" id="prop-shape-color1-opacity" min="0" max="100" value="100" class="w-full accent-indigo-600 h-1" />
                    </div>
                    <div class="flex flex-col items-center flex-1" id="prop-shape-color2-container" style="display: none;">
                        <span class="text-[10px] text-gray-500 uppercase font-bold mb-1">Color 2</span>
                        <input type="color" id="prop-shape-color2" value="#ffffff" class="block w-full h-8 p-0 border-0 rounded cursor-pointer mb-1" />
                        <input type="range" id="prop-shape-color2-opacity" min="0" max="100" value="100" class="w-full accent-indigo-600 h-1" />
                    </div>
                </div>

                <div id="prop-shape-angle-container" style="display: none;">
                    <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1 mt-2">Ángulo (°)</label>
                    <input type="number" id="prop-shape-angle" value="90" min="0" max="360" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-black focus:border-black" />
                </div>
            </div>
        </div>

        <!-- Group Properties Panel (NEW) -->
        <div id="group-properties-panel" class="hidden px-4 flex-col gap-3 pt-2 border-t border-gray-100">
            <div class="flex justify-between items-center mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-black">Propiedades de Grupo</span>
                <button id="ungroup-btn" class="text-red-500 hover:text-red-700 flex items-center gap-1 text-xs font-semibold" title="Desagrupar capas">
                    <i class="fa-solid fa-object-ungroup"></i> Desagrupar
                </button>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Nombre del Grupo</label>
                <input type="text" id="prop-group-name" class="block w-full text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-black focus:border-black" />
            </div>

            <div class="flex items-center gap-2 mt-2">
                <input type="checkbox" id="prop-group-is-logo" class="rounded border-gray-300 text-black focus:ring-black cursor-pointer" />
                <label for="prop-group-is-logo" class="text-xs font-semibold text-gray-700 cursor-pointer flex items-center gap-1.5">
                    <i class="fa-solid fa-award text-yellow-500"></i> Logo Oficial del Libro
                </label>
            </div>
        </div>
    </div>
</aside>
