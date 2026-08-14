<?php
$creation_template = function_exists( 'almaden_bookster_get_book_template_payload_for_seed' )
    ? almaden_bookster_get_book_template_payload_for_seed( 'literat', 'Literat' )
    : null;
$creation_template_id = ( $creation_template && ! empty( $creation_template['id'] ) )
    ? sanitize_key( $creation_template['id'] )
    : 'literat';
$creation_template_name = ( $creation_template && ! empty( $creation_template['name'] ) )
    ? sanitize_text_field( $creation_template['name'] )
    : 'Literat';
?>
    <div id="create-modal" class="fixed inset-0 z-50 hidden" aria-labelledby="create-book-modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-950/45 backdrop-blur-sm" aria-hidden="true" data-create-modal-backdrop></div>

        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
                <div id="modal-panel" class="relative w-full max-w-4xl transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl ring-1 ring-black/5 opacity-0 scale-95 transition-all duration-300">
                    <div class="flex items-start justify-between border-b border-slate-100 px-6 py-5 sm:px-8">
                        <div class="pr-6">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.35em] text-slate-400">Taller</p>
                        </div>
                        <button type="button" id="close-modal-btn" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Cerrar">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 pt-5 sm:px-8">
                        <div class="flex items-center justify-between gap-4">
                            <span id="step-indicator" class="text-sm font-semibold text-slate-500">Paso 1 de 3</span>
                        </div>
                        <div class="mt-3 h-1 rounded-full bg-slate-100">
                            <div id="progress-bar" class="h-1 w-1/3 rounded-full bg-black transition-all duration-300"></div>
                        </div>
                    </div>

                    <div class="px-6 pb-6 pt-6 sm:px-8 sm:pb-8">
                        <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" id="create-book-form">
                            <input type="hidden" name="action" value="almaden_create_book">
                            <input type="hidden" name="book_template" id="book_template" value="<?php echo esc_attr( $creation_template_id ); ?>">
                            <input type="hidden" name="book_template_label" id="book_template_label" value="<?php echo esc_attr( $creation_template_name ); ?>">
                            <?php wp_nonce_field( 'almaden_create_book_nonce', 'almaden_nonce' ); ?>

                            <section id="wizard-step-1" class="wizard-step space-y-6">
                                <div>
                                    <h4 class="text-xl font-semibold tracking-tight text-slate-900">Información básica</h4>
                                    <p class="mt-2 text-sm text-slate-500">Comencemos con los datos principales de tu obra.</p>
                                </div>

                                <div class="space-y-5">
                                    <div>
                                        <label for="book_title" class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Título del libro</label>
                                        <input type="text" name="book_title" id="book_title" required placeholder="Ej. El Sueño Eterno" class="mt-2 block w-full border-0 border-b border-slate-300 bg-transparent px-0 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-black focus:ring-0">
                                    </div>
                                    <div>
                                        <label for="book_author" class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Nombre del autor</label>
                                        <input type="text" name="book_author" id="book_author" required placeholder="Ej. Ana Pérez" class="mt-2 block w-full border-0 border-b border-slate-300 bg-transparent px-0 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-black focus:ring-0">
                                    </div>
                                </div>
                            </section>

                            <section id="wizard-step-2" class="wizard-step hidden space-y-6">
                                <div>
                                    <h4 class="text-xl font-semibold tracking-tight text-slate-900">Tamaño del libro</h4>
                                    <p class="mt-2 text-sm text-slate-500">Selecciona el formato físico ideal para tu publicación.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label data-size-card class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-900 hover:bg-slate-50">
                                        <input type="radio" name="almaden_book_size" value="novela" class="hidden size-radio" checked>
                                        <div>
                                            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Estándar</span>
                                            <h5 class="mt-1 text-base font-semibold text-slate-900">Novela</h5>
                                        </div>
                                        <p class="mt-4 text-sm text-slate-500">13.3 x 20.3 cm</p>
                                    </label>

                                    <label data-size-card class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-900 hover:bg-slate-50">
                                        <input type="radio" name="almaden_book_size" value="digest" class="hidden size-radio">
                                        <div>
                                            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Popular</span>
                                            <h5 class="mt-1 text-base font-semibold text-slate-900">Digest</h5>
                                        </div>
                                        <p class="mt-4 text-sm text-slate-500">14 x 21.6 cm</p>
                                    </label>

                                    <label data-size-card class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-900 hover:bg-slate-50">
                                        <input type="radio" name="almaden_book_size" value="trade" class="hidden size-radio">
                                        <div>
                                            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Amplio</span>
                                            <h5 class="mt-1 text-base font-semibold text-slate-900">Trade</h5>
                                        </div>
                                        <p class="mt-4 text-sm text-slate-500">15.2 x 22.9 cm</p>
                                    </label>

                                    <label data-size-card class="flex flex-col justify-between rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-900 hover:bg-slate-50">
                                        <input type="radio" name="almaden_book_size" value="custom" class="hidden size-radio">
                                        <div>
                                            <span class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">A medida</span>
                                            <h5 class="mt-1 text-base font-semibold text-slate-900">Custom</h5>
                                        </div>
                                        <p class="mt-4 text-sm text-slate-500">Dimensiones personalizadas</p>
                                    </label>
                                </div>
                            </section>

                            <section id="wizard-step-custom" class="wizard-step hidden space-y-6">
                                <div>
                                    <h4 class="text-xl font-semibold tracking-tight text-slate-900">Dimensiones personalizadas</h4>
                                    <p class="mt-2 text-sm text-slate-500">Define el ancho, alto y los márgenes internos de tu libro.</p>
                                </div>

                                <div id="custom-dimensions-container" class="hidden space-y-6">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="almaden_custom_width" class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Ancho (cm)</label>
                                            <input type="number" step="0.1" min="10" max="50" name="almaden_custom_width" id="almaden_custom_width" value="14.8" class="mt-2 block w-full border-0 border-b border-slate-300 bg-transparent px-0 py-3 text-sm text-slate-900 outline-none transition focus:border-black focus:ring-0">
                                        </div>
                                        <div>
                                            <label for="almaden_custom_height" class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Alto (cm)</label>
                                            <input type="number" step="0.1" min="10" max="50" name="almaden_custom_height" id="almaden_custom_height" value="21.0" class="mt-2 block w-full border-0 border-b border-slate-300 bg-transparent px-0 py-3 text-sm text-slate-900 outline-none transition focus:border-black focus:ring-0">
                                        </div>
                                    </div>

                                    <div>
                                        <h5 class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Márgenes internos (cm)</h5>
                                        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                                            <div>
                                                <label for="almaden_custom_margin_top" class="block text-[11px] font-medium text-slate-500">Superior</label>
                                                <input type="number" step="0.1" min="0.5" max="5" name="almaden_custom_margin_top" id="almaden_custom_margin_top" value="2.0" class="mt-1 block w-full border-0 border-b border-slate-300 bg-transparent px-0 py-2.5 text-sm text-slate-900 outline-none transition focus:border-black focus:ring-0">
                                            </div>
                                            <div>
                                                <label for="almaden_custom_margin_bottom" class="block text-[11px] font-medium text-slate-500">Inferior</label>
                                                <input type="number" step="0.1" min="0.5" max="5" name="almaden_custom_margin_bottom" id="almaden_custom_margin_bottom" value="2.0" class="mt-1 block w-full border-0 border-b border-slate-300 bg-transparent px-0 py-2.5 text-sm text-slate-900 outline-none transition focus:border-black focus:ring-0">
                                            </div>
                                            <div>
                                                <label for="almaden_custom_margin_outer" class="block text-[11px] font-medium text-slate-500">Exterior</label>
                                                <input type="number" step="0.1" min="0.5" max="5" name="almaden_custom_margin_outer" id="almaden_custom_margin_outer" value="1.8" class="mt-1 block w-full border-0 border-b border-slate-300 bg-transparent px-0 py-2.5 text-sm text-slate-900 outline-none transition focus:border-black focus:ring-0">
                                            </div>
                                            <div>
                                                <label for="almaden_custom_margin_inner" class="block text-[11px] font-medium text-slate-500">Interior</label>
                                                <input type="number" step="0.1" min="0.5" max="5" name="almaden_custom_margin_inner" id="almaden_custom_margin_inner" value="2.5" class="mt-1 block w-full border-0 border-b border-slate-300 bg-transparent px-0 py-2.5 text-sm text-slate-900 outline-none transition focus:border-black focus:ring-0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section id="wizard-step-templates" class="wizard-step hidden space-y-6">
                                <div>
                                    <h4 class="text-xl font-semibold tracking-tight text-slate-900">Plantilla de páginas</h4>
                                    <p class="mt-2 text-sm text-slate-500">Elige el estilo tipográfico para el interior de tu libro.</p>
                                </div>

                                <div id="template-preview-main" class="hidden w-full" aria-hidden="true">
                                    <!-- Vista preliminar deshabilitada temporalmente. -->
                                </div>

                                <div class="flex justify-start pt-2">
                                    <button type="button" data-template-button data-template-value="<?php echo esc_attr( $creation_template_id ); ?>" data-template-label="<?php echo esc_attr( $creation_template_name ); ?>" class="rounded-2xl border border-black bg-black px-3 py-3 text-center text-xs font-semibold text-white transition hover:border-black hover:bg-slate-900"><?php echo esc_html( $creation_template_name ); ?></button>
                                </div>
                            </section>

                            <div class="mt-8 flex items-center justify-between border-t border-slate-100 pt-5">
                                <button type="button" class="cancel-modal-btn text-sm font-semibold leading-6 text-slate-700 transition hover:text-slate-950">Cancelar</button>
                                <div class="flex items-center gap-3">
                                    <button type="button" id="prev-btn" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-950">Anterior</button>
                                    <button type="button" id="next-btn" class="rounded-2xl bg-black px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Siguiente</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
