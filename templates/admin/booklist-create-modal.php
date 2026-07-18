    <!-- Modal Form (Hidden by default) -->
    <div id="create-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Background backdrop -->
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <!-- Modal panel -->
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100 opacity-0 scale-95" id="modal-panel">
                    
                    <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block z-20">
                        <button type="button" id="close-modal-btn" class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2 transition-colors">
                            <span class="sr-only">Cerrar</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 relative">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" id="create-book-form">
                                    <input type="hidden" name="action" value="almaden_create_book">
                                    <?php wp_nonce_field( 'almaden_create_book_nonce', 'almaden_nonce' ); ?>
                                    
                                    <!-- SLIDE 1: Info Básica -->
                                    <div id="wizard-slide-1" class="wizard-slide block">
                                        <h3 class="text-xl font-bold leading-6 text-gray-900 serif" id="modal-title-1">Crear Nuevo Libro</h3>
                                        <div class="mt-2 mb-6">
                                            <p class="text-sm text-gray-500">Ingresa los detalles básicos para comenzar tu proyecto editorial.</p>
                                        </div>
                                        
                                        <div class="space-y-4">
                                            <div>
                                                <label for="book_title" class="block text-sm font-medium leading-6 text-gray-900">Título del Libro <span class="text-red-500">*</span></label>
                                                <div class="mt-1">
                                                    <input type="text" name="book_title" id="book_title" required class="block w-full rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-black sm:text-sm sm:leading-6 bg-gray-50/50">
                                                </div>
                                            </div>
                                            <div>
                                                <label for="book_author" class="block text-sm font-medium leading-6 text-gray-900">Autor(es) <span class="text-red-500">*</span></label>
                                                <div class="mt-1">
                                                    <input type="text" name="book_author" id="book_author" required placeholder="correo@editorial.com, usuario2" class="block w-full rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-black sm:text-sm sm:leading-6 bg-gray-50/50">
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500">Separa varios autores con coma. Para vincular permisos, usa correos o nombres de usuario.</p>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-6 flex items-center justify-end gap-x-3 border-t border-gray-100 pt-5">
                                            <button type="button" class="cancel-modal-btn text-sm font-semibold leading-6 text-gray-900 hover:text-gray-600 transition-colors">Cancelar</button>
                                            <button type="button" id="btn-next-1" class="rounded-md bg-black px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition-colors">Siguiente</button>
                                        </div>
                                    </div>
                                    
                                    <!-- SLIDE 2: Formato -->
                                    <div id="wizard-slide-2" class="wizard-slide hidden">
                                        <h3 class="text-xl font-bold leading-6 text-gray-900 serif">Formato de publicación</h3>
                                        <div class="mt-2 mb-6">
                                            <p class="text-sm text-gray-500">¿En qué formatos planeas publicar este libro? Puedes seleccionar ambos.</p>
                                        </div>
                                        
                                        <div class="space-y-4">
                                            <label class="flex items-start p-4 border border-gray-200 rounded-md cursor-pointer hover:bg-gray-50 transition-colors">
                                                <div class="flex h-6 items-center">
                                                    <input type="checkbox" name="almaden_book_format[]" value="ebook" class="h-4 w-4 rounded border-gray-300 text-black focus:ring-black format-checkbox">
                                                </div>
                                                <div class="ml-3 text-sm leading-6">
                                                    <span class="font-medium text-gray-900">Ebook (Digital)</span>
                                                    <p class="text-gray-500">Optimizado para lectura en pantallas y dispositivos móviles.</p>
                                                </div>
                                            </label>
                                            <label class="flex items-start p-4 border border-gray-200 rounded-md cursor-pointer hover:bg-gray-50 transition-colors">
                                                <div class="flex h-6 items-center">
                                                    <input type="checkbox" name="almaden_book_format[]" value="impreso" class="h-4 w-4 rounded border-gray-300 text-black focus:ring-black format-checkbox">
                                                </div>
                                                <div class="ml-3 text-sm leading-6">
                                                    <span class="font-medium text-gray-900">Impreso (Físico)</span>
                                                    <p class="text-gray-500">Formato maquetado en páginas para enviar a imprenta (PDF).</p>
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-5">
                                            <button type="button" class="btn-prev text-sm font-semibold leading-6 text-gray-900 hover:text-gray-600 transition-colors" data-prev="1">Volver</button>
                                            <button type="button" id="btn-next-2" class="rounded-md bg-black px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition-colors" disabled>Siguiente</button>
                                        </div>
                                    </div>
                                    
                                    <!-- SLIDE 3: Tamaño de impresión -->
                                    <div id="wizard-slide-3" class="wizard-slide hidden">
                                        <h3 class="text-xl font-bold leading-6 text-gray-900 serif">Tamaño de impresión</h3>
                                        <div class="mt-2 mb-6">
                                            <p class="text-sm text-gray-500">Selecciona las dimensiones físicas de la página del libro.</p>
                                        </div>
                                        
                                        <div class="space-y-4">
                                            <label class="flex items-center p-3 border border-gray-200 rounded-md cursor-pointer hover:bg-gray-50 transition-colors">
                                                <input type="radio" name="almaden_book_size" value="14x21" class="h-4 w-4 border-gray-300 text-black focus:ring-black size-radio" checked>
                                                <span class="ml-3 font-medium text-gray-900 text-sm">14 x 21 cm (Estándar)</span>
                                            </label>
                                            <label class="flex items-center p-3 border border-gray-200 rounded-md cursor-pointer hover:bg-gray-50 transition-colors">
                                                <input type="radio" name="almaden_book_size" value="15x23" class="h-4 w-4 border-gray-300 text-black focus:ring-black size-radio">
                                                <span class="ml-3 font-medium text-gray-900 text-sm">15 x 23 cm (Novela grande)</span>
                                            </label>
                                            
                                            <div class="border border-gray-200 rounded-md transition-colors hover:bg-gray-50 group">
                                                <label class="flex items-center p-3 cursor-pointer">
                                                    <input type="radio" name="almaden_book_size" value="custom" class="h-4 w-4 border-gray-300 text-black focus:ring-black size-radio">
                                                    <span class="ml-3 font-medium text-gray-900 text-sm">Tamaño Personalizado</span>
                                                </label>
                                                
                                                <div id="custom-dimensions-container" class="hidden px-4 pb-4 border-t border-gray-100 pt-3">
                                                    <div class="flex space-x-4">
                                                        <div class="flex-1">
                                                            <label for="almaden_custom_width" class="block text-xs font-medium leading-6 text-gray-500">Ancho (cm) <span class="text-red-500">*</span></label>
                                                            <div class="mt-1">
                                                                <input type="number" step="0.1" min="10" max="50" name="almaden_custom_width" id="almaden_custom_width" class="block w-full rounded-md border-0 py-1.5 px-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-black sm:text-sm sm:leading-6">
                                                            </div>
                                                        </div>
                                                        <div class="flex-1">
                                                            <label for="almaden_custom_height" class="block text-xs font-medium leading-6 text-gray-500">Alto (cm) <span class="text-red-500">*</span></label>
                                                            <div class="mt-1">
                                                                <input type="number" step="0.1" min="10" max="50" name="almaden_custom_height" id="almaden_custom_height" class="block w-full rounded-md border-0 py-1.5 px-2 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-black sm:text-sm sm:leading-6">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-5">
                                            <button type="button" class="btn-prev text-sm font-semibold leading-6 text-gray-900 hover:text-gray-600 transition-colors" data-prev="2">Volver</button>
                                            <button type="button" id="btn-next-3" class="rounded-md bg-black px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 transition-colors">CREAR LIBRO</button>
                                        </div>
                                    </div>
                                    
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const slides = document.querySelectorAll('.wizard-slide');
        const form = document.getElementById('create-book-form');
        const btnNext1 = document.getElementById('btn-next-1');
        const btnNext2 = document.getElementById('btn-next-2');
        const btnNext3 = document.getElementById('btn-next-3');
        const formatCheckboxes = document.querySelectorAll('.format-checkbox');
        const sizeRadios = document.querySelectorAll('.size-radio');
        const inputTitle = document.getElementById('book_title');
        const inputAuthor = document.getElementById('book_author');
        
        // Reset and hide all slides
        function showSlide(slideId) {
            slides.forEach(slide => slide.classList.add('hidden'));
            document.getElementById('wizard-slide-' + slideId).classList.remove('hidden');
        }

        // Logic for Slide 1 -> Slide 2
        btnNext1.addEventListener('click', function(e) {
            if (!inputTitle.value.trim() || !inputAuthor.value.trim()) {
                inputTitle.reportValidity();
                inputAuthor.reportValidity();
                return;
            }
            showSlide(2);
        });

        // Logic for Slide 2 -> Slide 3 or Submit
        formatCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateSlide2Button);
        });

        function updateSlide2Button() {
            const hasEbook = document.querySelector('input[value="ebook"]').checked;
            const hasImpreso = document.querySelector('input[value="impreso"]').checked;
            
            btnNext2.disabled = (!hasEbook && !hasImpreso);
            
            if (hasImpreso) {
                btnNext2.textContent = 'Siguiente';
                btnNext2.type = 'button';
            } else if (hasEbook) {
                btnNext2.textContent = 'CREAR LIBRO';
                btnNext2.type = 'button'; 
            }
        }

        btnNext2.addEventListener('click', function(e) {
            const hasEbook = document.querySelector('input[value="ebook"]').checked;
            const hasImpreso = document.querySelector('input[value="impreso"]').checked;
            
            if (hasImpreso) {
                showSlide(3);
            } else if (hasEbook) {
                form.submit();
            }
        });

        // Logic for Slide 3 or Submit
        const customDimensionsContainer = document.getElementById('custom-dimensions-container');
        const customWidthInput = document.getElementById('almaden_custom_width');
        const customHeightInput = document.getElementById('almaden_custom_height');

        sizeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'custom') {
                    customDimensionsContainer.classList.remove('hidden');
                    customWidthInput.required = true;
                    customHeightInput.required = true;
                } else {
                    customDimensionsContainer.classList.add('hidden');
                    customWidthInput.required = false;
                    customHeightInput.required = false;
                }
            });
        });

        btnNext3.addEventListener('click', function(e) {
            const selectedSize = document.querySelector('input[name="almaden_book_size"]:checked').value;
            if (selectedSize === 'custom') {
                if (!customWidthInput.value || !customHeightInput.value) {
                    customWidthInput.reportValidity();
                    customHeightInput.reportValidity();
                    return;
                }
            }
            form.submit();
        });

        // Prev buttons
        document.querySelectorAll('.btn-prev').forEach(btn => {
            btn.addEventListener('click', function() {
                const prevTarget = this.getAttribute('data-prev');
                showSlide(prevTarget);
            });
        });

        // Hooking open modal to reset to slide 1
        // We override the global openModal function slightly to add our logic
        // This relies on the openBtn event listener in booklist-ui.js
        const openBtn = document.getElementById('open-modal-btn');
        if (openBtn) {
            openBtn.addEventListener('click', function() {
                form.reset();
                showSlide(1);
                updateSlide2Button();
            });
        }

        // Cancel button
        document.querySelectorAll('.cancel-modal-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (window.closeModal) window.closeModal();
            });
        });
    });
    </script>
