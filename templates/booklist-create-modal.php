    <!-- Modal Form (Hidden by default) -->
    <div id="create-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Background backdrop -->
        <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <!-- Modal panel -->
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100 opacity-0 scale-95" id="modal-panel">
                    
                    <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                        <button type="button" id="close-modal-btn" class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-black focus:ring-offset-2 transition-colors">
                            <span class="sr-only">Cerrar</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-black" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-xl font-bold leading-6 text-gray-900 serif" id="modal-title">Crear Nuevo Libro</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Ingresa los detalles básicos para comenzar tu proyecto editorial.</p>
                                </div>
                                
                                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" class="mt-6 space-y-4">
                                    <input type="hidden" name="action" value="almaden_create_book">
                                    <?php wp_nonce_field( 'almaden_create_book_nonce', 'almaden_nonce' ); ?>
                                    
                                    <div>
                                        <label for="book_title" class="block text-sm font-medium leading-6 text-gray-900">Título del Libro <span class="text-red-500">*</span></label>
                                        <div class="mt-1">
                                            <input type="text" name="book_title" id="book_title" required class="block w-full rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-black sm:text-sm sm:leading-6 bg-gray-50/50">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="book_author" class="block text-sm font-medium leading-6 text-gray-900">Autor(es) <span class="text-red-500">*</span></label>
                                        <div class="mt-1">
                                            <input type="text" name="book_author" id="book_author" required class="block w-full rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-black sm:text-sm sm:leading-6 bg-gray-50/50">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="book_content" class="block text-sm font-medium leading-6 text-gray-900">Sinopsis o Descripción breve</label>
                                        <div class="mt-1">
                                            <textarea id="book_content" name="book_content" rows="3" class="block w-full rounded-md border-0 py-2 px-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-black sm:text-sm sm:leading-6 bg-gray-50/50 resize-none"></textarea>
                                        </div>
                                    </div>

                                    <div class="mt-6 flex items-center justify-end gap-x-3 border-t border-gray-100 pt-5">
                                        <button type="button" id="cancel-modal-btn" class="text-sm font-semibold leading-6 text-gray-900 hover:text-gray-600 transition-colors">Cancelar</button>
                                        <button type="submit" class="rounded-md bg-black px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black transition-colors">Crear Libro</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
