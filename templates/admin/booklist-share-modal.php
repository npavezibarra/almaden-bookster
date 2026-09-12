<?php
/**
 * Modal template for sharing books with other users.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$share_nonce = wp_create_nonce( 'almaden_share_book_nonce' );
?>
<div id="share-modal" class="fixed inset-0 z-50 hidden" aria-label="<?php esc_attr_e( 'Compartir libro', 'almaden-bookster' ); ?>" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-950/45 backdrop-blur-sm transition-opacity" aria-hidden="true" data-share-modal-backdrop onclick="closeShareModal()"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div id="share-modal-panel" class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl ring-1 ring-black/5 transition-all duration-200 opacity-0 scale-95">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <span class="text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-400"><?php esc_html_e( 'Taller', 'almaden-bookster' ); ?></span>
                        <h3 class="text-lg font-bold text-slate-900 leading-tight"><?php esc_html_e( 'Compartir libro', 'almaden-bookster' ); ?></h3>
                    </div>
                    <button type="button" onclick="closeShareModal()" class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="<?php esc_attr_e( 'Cerrar', 'almaden-bookster' ); ?>">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-5">
                    <!-- Book Title Preview -->
                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-black text-white flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-book text-xs"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-slate-500 font-medium truncate"><?php esc_html_e( 'Libro seleccionado', 'almaden-bookster' ); ?></p>
                            <h4 id="share-modal-book-title" class="text-sm font-bold text-slate-900 truncate"></h4>
                        </div>
                    </div>

                    <!-- User Search Input -->
                    <div class="relative">
                        <label for="share-user-search-input" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                            <?php esc_html_e( 'Buscar usuario o email', 'almaden-bookster' ); ?>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" id="share-user-search-input" autocomplete="off" placeholder="<?php esc_attr_e( 'Escribe un nombre o correo electrónico...', 'almaden-bookster' ); ?>" class="block w-full rounded-xl border border-slate-300 pl-10 pr-10 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-black focus:ring-1 focus:ring-black outline-none transition shadow-sm">
                            <div id="share-search-spinner" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none hidden text-slate-400">
                                <i class="fa-solid fa-spinner fa-spin text-sm"></i>
                            </div>
                        </div>

                        <!-- Autocomplete Dropdown List -->
                        <div id="share-autocomplete-dropdown" class="hidden absolute left-0 right-0 top-full mt-1.5 z-20 max-h-56 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-xl divide-y divide-slate-100">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>

                    <!-- Selected User Preview Box -->
                    <div id="share-selected-user-box" class="hidden p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                        <div class="flex items-center gap-3 min-w-0">
                            <img id="share-selected-user-avatar" src="" alt="" class="w-8 h-8 rounded-full object-cover shrink-0">
                            <div class="min-w-0">
                                <p id="share-selected-user-name" class="text-sm font-semibold text-slate-900 truncate"></p>
                                <p id="share-selected-user-email" class="text-xs text-slate-500 truncate"></p>
                            </div>
                        </div>
                        <button type="button" onclick="clearSelectedShareUser()" class="p-1 text-slate-400 hover:text-red-500 transition rounded-lg hover:bg-slate-100" title="<?php esc_attr_e( 'Quitar selección', 'almaden-bookster' ); ?>">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Action Button -->
                    <div class="pt-1">
                        <button type="button" id="share-submit-btn" onclick="submitShareBook()" disabled class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-transparent text-sm font-semibold rounded-xl text-white bg-black hover:bg-gray-800 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed transition-all shadow-sm">
                            <i id="share-submit-spinner" class="fa-solid fa-spinner fa-spin hidden"></i>
                            <span id="share-submit-text"><?php esc_html_e( 'Compartir', 'almaden-bookster' ); ?></span>
                        </button>
                    </div>

                    <!-- People with Access Section -->
                    <div class="pt-3 border-t border-slate-100">
                        <h5 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-3">
                            <?php esc_html_e( 'Personas con acceso', 'almaden-bookster' ); ?>
                        </h5>
                        <div id="share-existing-list" class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.almadenShareConfig = {
        ajaxUrl: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
        nonce: "<?php echo esc_js( $share_nonce ); ?>"
    };
</script>
