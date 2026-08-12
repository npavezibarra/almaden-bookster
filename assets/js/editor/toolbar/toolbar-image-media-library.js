let imageViewportMediaItems = [];
let imageViewportMediaQuery = '';

function getImageViewportMediaConfig() {
    if (!window.bookState) return null;
    const config = {
        bookId: Number(window.bookState.bookId || 0),
        ajaxUrl: String(window.bookState.ajaxUrl || ''),
        nonce: String(window.bookState.mediaPickerNonce || ''),
    };
    return config.bookId && config.ajaxUrl && config.nonce ? config : null;
}

function escapeImageViewportMediaHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function setImageViewportStage(stage) {
    const modal = getImageViewportEditorModal();
    if (!modal) return;
    const isLibrary = stage === 'library';
    const library = modal.querySelector('#image-viewport-library-stage');
    const adjust = modal.querySelector('#image-viewport-adjust-stage');
    const libraryStep = modal.querySelector('#image-viewport-library-step');
    const adjustStep = modal.querySelector('#image-viewport-adjust-step');
    const title = modal.querySelector('#image-viewport-title');
    const hasImage = !!getImageViewportState().src;

    if (library) library.classList.toggle('hidden', !isLibrary);
    if (adjust) adjust.classList.toggle('hidden', isLibrary);
    if (adjustStep) adjustStep.disabled = !hasImage;
    if (libraryStep) libraryStep.className = `rounded-full px-3 py-1.5 ${isLibrary ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-500'}`;
    if (adjustStep) adjustStep.className = `rounded-full px-3 py-1.5 disabled:cursor-not-allowed ${!isLibrary && hasImage ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-500'}`;
    if (title) title.textContent = isLibrary ? 'Elegir imagen' : 'Recortar y ajustar';
    modal.dataset.imageViewportStage = stage;
}

function showImageViewportAdjustStage() {
    if (!getImageViewportState().src) return;
    setImageViewportStage('adjust');
    updateImageViewportModalView();
}

function getBooksterAttachmentImageUrls(attachment) {
    if (!attachment || typeof attachment !== 'object') return { originalUrl: '', previewUrl: '' };
    const sizes = attachment.sizes || {};
    const previewUrl = attachment.previewUrl || attachment.preview_url
        || (sizes.medium_large && sizes.medium_large.url)
        || (sizes.large && sizes.large.url)
        || (sizes.medium && sizes.medium.url)
        || (sizes.thumbnail && sizes.thumbnail.url)
        || attachment.url || '';
    const originalUrl = attachment.originalImageURL || attachment.original_url
        || attachment.fullUrl || attachment.originalUrl || attachment.url || previewUrl || '';
    return { originalUrl, previewUrl };
}

function selectImageViewportAttachment(attachment) {
    if (!attachment) return;
    const urls = getBooksterAttachmentImageUrls(attachment);
    const imgUrl = urls.originalUrl || urls.previewUrl || attachment.url;
    setImageViewportState({
        src: imgUrl,
        previewSrc: urls.previewUrl || imgUrl,
        alt: attachment.alt || attachment.title || 'Imagen del libro',
        isPlaceholder: false,
        committed: false,
    });
    showImageViewportAdjustStage();
}

function setImageViewportMediaStatus(message, isLoading = false) {
    const modal = getImageViewportEditorModal();
    if (!modal) return;
    const status = modal.querySelector('#image-viewport-media-status');
    const loading = modal.querySelector('#image-viewport-media-loading');
    if (loading) loading.classList.toggle('hidden', !isLoading);
    if (status) {
        status.textContent = message || '';
        status.classList.toggle('hidden', !message);
    }
}

function renderImageViewportLibrary() {
    const modal = getImageViewportEditorModal();
    if (!modal) return;
    const grid = modal.querySelector('#image-viewport-media-grid');
    const empty = modal.querySelector('#image-viewport-media-empty');
    if (!grid || !empty) return;

    const items = imageViewportMediaItems.filter((item) => {
        if (!imageViewportMediaQuery) return true;
        return [item.title, item.filename, item.url].filter(Boolean).join(' ').toLowerCase().includes(imageViewportMediaQuery);
    });
    empty.classList.toggle('hidden', items.length > 0);
    grid.innerHTML = items.map((item) => {
        const src = item.previewUrl || item.originalUrl || item.url || '';
        return `<button type="button" data-image-viewport-media-id="${escapeImageViewportMediaHtml(item.id)}" class="group overflow-hidden rounded-3xl border border-slate-200 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:border-slate-400 hover:shadow-md">
            <div class="aspect-square bg-slate-100">${src ? `<img src="${escapeImageViewportMediaHtml(src)}" alt="" class="h-full w-full object-cover">` : '<div class="flex h-full items-center justify-center text-xs font-semibold text-slate-400">Sin vista previa</div>'}</div>
            <div class="p-3"><p class="truncate text-sm font-semibold text-slate-900">${escapeImageViewportMediaHtml(item.title || 'Imagen')}</p><p class="mt-1 truncate text-[11px] text-slate-500">${escapeImageViewportMediaHtml(item.filename || '')}</p></div>
        </button>`;
    }).join('');
    grid.querySelectorAll('[data-image-viewport-media-id]').forEach((button) => {
        button.addEventListener('click', () => {
            const item = imageViewportMediaItems.find((entry) => String(entry.id) === button.dataset.imageViewportMediaId);
            selectImageViewportAttachment(item);
        });
    });
}

function filterImageViewportLibrary(value) {
    imageViewportMediaQuery = String(value || '').trim().toLowerCase();
    renderImageViewportLibrary();
}

function loadImageViewportLibrary() {
    const config = getImageViewportMediaConfig();
    const picker = window.AlmadenBooksterMediaPicker;
    if (!config || !picker || typeof picker.list !== 'function') return Promise.reject(new Error('Biblioteca del libro no disponible.'));
    setImageViewportMediaStatus('', true);
    return picker.list(config).then((data) => {
        imageViewportMediaItems = Array.isArray(data.attachments) ? data.attachments : [];
        setImageViewportMediaStatus('', false);
        renderImageViewportLibrary();
    }).catch((error) => {
        setImageViewportMediaStatus(error instanceof Error ? error.message : String(error), false);
        throw error;
    });
}

function openImageMediaPicker() {
    setImageViewportStage('library');
    const search = document.getElementById('image-viewport-media-search');
    if (search) search.value = '';
    imageViewportMediaQuery = '';
    loadImageViewportLibrary().catch(() => {});
}

function uploadImageViewportFile(file) {
    const config = getImageViewportMediaConfig();
    const picker = window.AlmadenBooksterMediaPicker;
    if (!file || !config || !picker || typeof picker.upload !== 'function') return;
    setImageViewportMediaStatus('Subiendo imagen...', true);
    picker.upload(config, file).then((attachment) => {
        imageViewportMediaItems.unshift(attachment);
        setImageViewportMediaStatus('', false);
        selectImageViewportAttachment(attachment);
    }).catch((error) => setImageViewportMediaStatus(error instanceof Error ? error.message : String(error), false));
}

document.getElementById('image-viewport-media-file')?.addEventListener('change', (event) => {
    const file = event.target.files && event.target.files[0];
    event.target.value = '';
    uploadImageViewportFile(file);
});

window.openImageMediaPicker = openImageMediaPicker;
window.showImageViewportAdjustStage = showImageViewportAdjustStage;
window.filterImageViewportLibrary = filterImageViewportLibrary;
