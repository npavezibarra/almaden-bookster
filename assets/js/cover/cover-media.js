// cover-media.js
document.addEventListener('DOMContentLoaded', () => {
    const el = window.CoverEditor.elements;

    // Elements local to this script
    const btnFront = document.getElementById('btn-front-cover');
    const btnBack = document.getElementById('btn-back-cover');
    const btnSpine = document.getElementById('btn-spine-image');
    const btnSpread = document.getElementById('btn-full-spread');

    const uploadFront = document.getElementById('upload-front-cover');
    const uploadBack = document.getElementById('upload-back-cover');
    const uploadSpine = document.getElementById('upload-spine-image');
    const spineColorPicker = document.getElementById('spine-color-picker');
    const uploadSpread = document.getElementById('upload-full-spread');

    const clearFront = document.getElementById('clear-front-cover');
    const clearBack = document.getElementById('clear-back-cover');
    const clearSpine = document.getElementById('clear-spine');
    const clearSpread = document.getElementById('clear-full-spread');

    const toggleImagesBtn = document.getElementById('toggle-images-section');
    const toggleFlapsBtn = document.getElementById('toggle-flaps-section');
    const flapsIcon = document.getElementById('flaps-section-icon');

    const btnFrontFlapImage = document.getElementById('btn-front-flap-image');
    const uploadFrontFlapImage = document.getElementById('upload-front-flap-image');
    const frontFlapColorPicker = document.getElementById('front-flap-color-picker');
    const clearFrontFlap = document.getElementById('clear-front-flap');

    const btnBackFlapImage = document.getElementById('btn-back-flap-image');
    const uploadBackFlapImage = document.getElementById('upload-back-flap-image');
    const backFlapColorPicker = document.getElementById('back-flap-color-picker');
    const clearBackFlap = document.getElementById('clear-back-flap');

    // WP Media Frame
    let mediaFrame;
    const previewCache = window.CoverEditor.state.coverImagePreviewCache || (window.CoverEditor.state.coverImagePreviewCache = {});

    function getAttachmentPreviewUrl(attachment) {
        if (!attachment || typeof attachment !== 'object') {
            return '';
        }

        const sizes = attachment.sizes || {};
        // Keep the editor responsive. The original stays stored for PDF export;
        // the canvas only receives a WordPress-generated screen preview.
        const preferredSizes = ['medium_large', 'medium', 'large', 'thumbnail'];
        for (const sizeName of preferredSizes) {
            if (sizes[sizeName] && sizes[sizeName].url) {
                return sizes[sizeName].url;
            }
        }

        return '';
    }

    function setMediaImage(targetEl, url, fit) {
        if (!url) {
            return;
        }

        let mediaImg = targetEl.querySelector(':scope > img.cover-media-image');
        if (!mediaImg) {
            mediaImg = document.createElement('img');
            mediaImg.className = `cover-media-image ${fit === 'contain' ? 'cover-media-image--contain' : 'cover-media-image--cover'}`;
            mediaImg.alt = '';
            mediaImg.setAttribute('aria-hidden', 'true');
            mediaImg.loading = 'eager';
            mediaImg.decoding = 'async';
            mediaImg.style.zIndex = '0';
            targetEl.prepend(mediaImg);
        } else {
            mediaImg.classList.remove('cover-media-image--cover', 'cover-media-image--contain');
            mediaImg.classList.add(fit === 'contain' ? 'cover-media-image--contain' : 'cover-media-image--cover');
        }

        if (mediaImg.src !== url) {
            mediaImg.src = url;
        }
        targetEl.dataset.previewUrl = url;
    }

    function refreshImageDiagnostics(force = false) {
        if (window.CoverEditor.actions && typeof window.CoverEditor.actions.refreshCoverImageDiagnostics === 'function') {
            window.CoverEditor.actions.refreshCoverImageDiagnostics(force);
        }
    }

    function resolveCoverImagePreview(attachment, originalUrl) {
        const attachmentId = attachment && attachment.id ? String(attachment.id) : '0';
        const cacheKey = `${attachmentId}|${originalUrl || ''}`;

        if (previewCache[cacheKey]) {
            return Promise.resolve(previewCache[cacheKey]);
        }

        if (typeof coverData === 'undefined' || !coverData.ajaxUrl || !coverData.nonce || !coverData.bookId) {
            const fallback = {
                attachmentId: attachment && attachment.id ? attachment.id : 0,
                originalUrl: originalUrl || '',
                previewUrl: getAttachmentPreviewUrl(attachment),
                previewSafe: !!getAttachmentPreviewUrl(attachment)
            };
            previewCache[cacheKey] = fallback;
            return Promise.resolve(fallback);
        }

        const data = new FormData();
        data.append('action', 'almaden_get_cover_image_preview');
        data.append('book_id', coverData.bookId);
        data.append('nonce', coverData.nonce);
        data.append('attachment_id', attachment && attachment.id ? attachment.id : '0');
        data.append('image_url', originalUrl || '');

        return fetch(coverData.ajaxUrl, {
            method: 'POST',
            body: data
        })
            .then(response => response.json())
            .then(payload => {
                const previewData = payload && payload.success && payload.data ? payload.data : null;
                const previewSafe = previewData && Object.prototype.hasOwnProperty.call(previewData, 'previewSafe')
                    ? !!previewData.previewSafe
                    : true;
                const fallbackPreview = previewSafe ? getAttachmentPreviewUrl(attachment) : '';
                const resolved = {
                    attachmentId: previewData && previewData.attachmentId ? previewData.attachmentId : (attachment && attachment.id ? attachment.id : 0),
                    originalUrl: previewData && previewData.originalUrl ? previewData.originalUrl : (originalUrl || ''),
                    previewUrl: previewData && previewData.previewUrl ? previewData.previewUrl : fallbackPreview,
                    previewSafe
                };
                previewCache[cacheKey] = resolved;
                return resolved;
            })
            .catch(() => {
                const fallback = {
                    attachmentId: attachment && attachment.id ? attachment.id : 0,
                    originalUrl: originalUrl || '',
                    previewUrl: '',
                    previewSafe: false
                };
                previewCache[cacheKey] = fallback;
                return fallback;
            });
    }

    function clearMediaImage(targetEl) {
        const mediaImg = targetEl.querySelector(':scope > img.cover-media-image');
        if (mediaImg) {
            mediaImg.remove();
        }
    }

    function clearCoverPart(targetEl) {
        targetEl.style.backgroundImage = 'none';
        targetEl.style.backgroundColor = 'white';
        targetEl.style.border = '1px dashed #d1d5db';
        targetEl.innerHTML = '';
        delete targetEl.dataset.previewUrl;
    }

    function openMediaUploader(title, onSelect) {
        const handleSelection = (attachment) => {
            if (!attachment) return;
            const originalUrl = attachment.originalUrl || attachment.originalImageURL || attachment.url || '';
            resolveCoverImagePreview(attachment, originalUrl).then((previewData) => {
                const previewSafe = previewData && Object.prototype.hasOwnProperty.call(previewData, 'previewSafe')
                    ? !!previewData.previewSafe
                    : true;
                onSelect({
                    attachmentId: previewData.attachmentId || attachment.id || 0,
                    originalUrl: previewData.originalUrl || originalUrl,
                    previewUrl: previewData.previewUrl || (previewSafe ? originalUrl : ''),
                    previewSafe,
                    attachment
                });
            });
        };

        if (window.AlmadenBooksterMediaPicker && coverData && coverData.bookId && coverData.mediaPickerNonce) {
            window.AlmadenBooksterMediaPicker.open({
                bookId: coverData.bookId,
                ajaxUrl: coverData.ajaxUrl,
                nonce: coverData.mediaPickerNonce,
                title: title,
                buttonText: 'Usar esta imagen'
            }).then(handleSelection).catch(() => {});
            return;
        }

        if (mediaFrame) {
            mediaFrame.off('select'); // clear previous listeners
        } else {
            mediaFrame = wp.media({
                title: title,
                button: { text: 'Usar esta imagen' },
                multiple: false
            });
        }

        mediaFrame.on('select', function() {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            handleSelection(attachment);
        });

        mediaFrame.open();
    }

    function getMediaValue(source, key, fallback = '') {
        if (!source) {
            return fallback;
        }

        if (typeof source === 'string') {
            return (key === 'originalUrl' || key === 'previewUrl' || key === 'url') ? source : fallback;
        }

        if (Object.prototype.hasOwnProperty.call(source, key) && source[key]) {
            return source[key];
        }

        return fallback;
    }

    function applyImageToCover(source, targetEl, inputEl, attachmentInputEl, clearBtn) {
        const previewUrl = getMediaValue(source, 'previewUrl', getMediaValue(source, 'originalUrl', getMediaValue(source, 'url', '')));
        const originalUrl = getMediaValue(source, 'originalUrl', getMediaValue(source, 'url', previewUrl));
        const attachmentId = getMediaValue(source, 'attachmentId', 0);
        const previewSafe = source && typeof source === 'object' && Object.prototype.hasOwnProperty.call(source, 'previewSafe')
            ? !!source.previewSafe
            : true;
        const displayUrl = previewUrl || (previewSafe ? originalUrl : '');

        if (displayUrl) {
            targetEl.querySelectorAll(':scope > :not(img.cover-media-image)').forEach(node => node.remove());
            setMediaImage(targetEl, displayUrl, 'cover');
        } else {
            clearMediaImage(targetEl);
            targetEl.innerHTML = '';
            targetEl.style.backgroundImage = 'none';
            targetEl.style.backgroundColor = 'white';
        }
        inputEl.value = originalUrl || previewUrl || '';
        if (attachmentInputEl) {
            attachmentInputEl.value = attachmentId ? String(attachmentId) : '0';
        }
        clearBtn.classList.remove('hidden');
        refreshImageDiagnostics();
    }

    function applySpreadImage(source) {
        const previewUrl = getMediaValue(source, 'previewUrl', getMediaValue(source, 'originalUrl', getMediaValue(source, 'url', '')));
        const originalUrl = getMediaValue(source, 'originalUrl', getMediaValue(source, 'url', previewUrl));
        const attachmentId = getMediaValue(source, 'attachmentId', 0);
        const previewSafe = source && typeof source === 'object' && Object.prototype.hasOwnProperty.call(source, 'previewSafe')
            ? !!source.previewSafe
            : true;
        const displayUrl = previewUrl || (previewSafe ? originalUrl : '');

        if (displayUrl) {
            setMediaImage(el.coverSpread, displayUrl, 'cover');
        } else {
            clearMediaImage(el.coverSpread);
            el.coverSpread.style.backgroundImage = 'none';
        }
        uploadSpread.value = originalUrl || previewUrl || '';
        const uploadSpreadAttachmentId = document.getElementById('upload-full-spread-attachment-id');
        if (uploadSpreadAttachmentId) {
            uploadSpreadAttachmentId.value = attachmentId ? String(attachmentId) : '0';
        }
        clearSpread.classList.remove('hidden');

        // clear individuals visually but keep their hidden values empty
        if (uploadFront.value) clearFront.click();
        if (uploadBack.value) clearBack.click();
        if (uploadSpine.value || spineColorPicker.value !== '#f9fafb') clearSpine.click();

        // make the parts transparent
        el.frontCover.style.backgroundColor = 'transparent';
        el.backCover.style.backgroundColor = 'transparent';
        el.spine.style.backgroundColor = 'transparent';
        el.frontCover.style.border = 'none';
        el.backCover.style.border = 'none';
        el.spine.style.border = 'none';

        // hide texts
        el.frontCover.innerHTML = '';
        el.backCover.innerHTML = '';
        el.spine.innerHTML = '';
        refreshImageDiagnostics();
    }

    // Bind Image Buttons
    btnFront.addEventListener('click', () => {
        openMediaUploader('Seleccionar Portada', (media) => {
            applyImageToCover(media, el.frontCover, uploadFront, document.getElementById('upload-front-cover-attachment-id'), clearFront);
            if (uploadSpread.value) clearSpread.click();
        });
    });

    btnBack.addEventListener('click', () => {
        openMediaUploader('Seleccionar Contraportada', (media) => {
            applyImageToCover(media, el.backCover, uploadBack, document.getElementById('upload-back-cover-attachment-id'), clearBack);
            if (uploadSpread.value) clearSpread.click();
        });
    });

    btnSpine.addEventListener('click', () => {
        openMediaUploader('Seleccionar Lomo', (media) => {
            applyImageToCover(media, el.spine, uploadSpine, document.getElementById('upload-spine-image-attachment-id'), clearSpine);
            if (uploadSpread.value) clearSpread.click();
        });
    });

    btnSpread.addEventListener('click', () => openMediaUploader('Seleccionar Spread', applySpreadImage));

    btnFrontFlapImage.addEventListener('click', () => {
        openMediaUploader('Seleccionar Imagen', (media) => {
            applyImageToCover(media, el.frontFlap, uploadFrontFlapImage, document.getElementById('upload-front-flap-image-attachment-id'), clearFrontFlap);
        });
    });
    btnBackFlapImage.addEventListener('click', () => {
        openMediaUploader('Seleccionar Imagen', (media) => {
            applyImageToCover(media, el.backFlap, uploadBackFlapImage, document.getElementById('upload-back-flap-image-attachment-id'), clearBackFlap);
        });
    });

    // Colors
    spineColorPicker.addEventListener('input', (e) => {
        el.spine.style.backgroundColor = e.target.value;
        el.spine.style.backgroundImage = 'none';
        clearSpine.classList.remove('hidden');
        if (uploadSpread.value) clearSpread.click();
    });

    frontFlapColorPicker.addEventListener('input', (e) => {
        el.frontFlap.style.backgroundColor = e.target.value;
        el.frontFlap.style.backgroundImage = 'none';
        const span = el.frontFlap.querySelector('span');
        if (span) span.style.display = 'none';
        clearFrontFlap.classList.remove('hidden');
    });

    backFlapColorPicker.addEventListener('input', (e) => {
        el.backFlap.style.backgroundColor = e.target.value;
        el.backFlap.style.backgroundImage = 'none';
        const span = el.backFlap.querySelector('span');
        if (span) span.style.display = 'none';
        clearBackFlap.classList.remove('hidden');
    });

    // Clear Logic
    clearSpine.addEventListener('click', () => {
        el.spine.style.backgroundImage = 'none';
        clearMediaImage(el.spine);
        el.spine.style.backgroundColor = '#f9fafb';
        uploadSpine.value = '';
        const spineAttachmentInput = document.getElementById('upload-spine-image-attachment-id');
        if (spineAttachmentInput) spineAttachmentInput.value = '0';
        spineColorPicker.value = '#f9fafb';
        el.spine.innerHTML = '<div class="spine-text text-xs text-gray-400 font-semibold uppercase tracking-wider rotate-90 whitespace-nowrap">Lomo</div>';
        clearSpine.classList.add('hidden');
        refreshImageDiagnostics();
    });

    clearFrontFlap.addEventListener('click', () => {
        el.frontFlap.style.backgroundImage = '';
        clearMediaImage(el.frontFlap);
        el.frontFlap.style.backgroundColor = '';
        uploadFrontFlapImage.value = '';
        const frontFlapAttachmentInput = document.getElementById('upload-front-flap-image-attachment-id');
        if (frontFlapAttachmentInput) frontFlapAttachmentInput.value = '0';
        frontFlapColorPicker.value = '#ffffff';
        const span = el.frontFlap.querySelector('span');
        if (span) span.style.display = 'block';
        clearFrontFlap.classList.add('hidden');
        refreshImageDiagnostics();
    });

    clearBackFlap.addEventListener('click', () => {
        el.backFlap.style.backgroundImage = '';
        clearMediaImage(el.backFlap);
        el.backFlap.style.backgroundColor = '';
        uploadBackFlapImage.value = '';
        const backFlapAttachmentInput = document.getElementById('upload-back-flap-image-attachment-id');
        if (backFlapAttachmentInput) backFlapAttachmentInput.value = '0';
        backFlapColorPicker.value = '#ffffff';
        const span = el.backFlap.querySelector('span');
        if (span) span.style.display = 'block';
        clearBackFlap.classList.add('hidden');
        refreshImageDiagnostics();
    });

    clearFront.addEventListener('click', () => {
        clearCoverPart(el.frontCover);
        uploadFront.value = '';
        const frontAttachmentInput = document.getElementById('upload-front-cover-attachment-id');
        if (frontAttachmentInput) frontAttachmentInput.value = '0';
        clearFront.classList.add('hidden');
        refreshImageDiagnostics();
    });

    clearBack.addEventListener('click', () => {
        clearCoverPart(el.backCover);
        uploadBack.value = '';
        const backAttachmentInput = document.getElementById('upload-back-cover-attachment-id');
        if (backAttachmentInput) backAttachmentInput.value = '0';
        clearBack.classList.add('hidden');
        refreshImageDiagnostics();
    });

    clearSpread.addEventListener('click', () => {
        el.coverSpread.style.backgroundImage = 'none';
        clearMediaImage(el.coverSpread);
        uploadSpread.value = '';
        const spreadAttachmentInput = document.getElementById('upload-full-spread-attachment-id');
        if (spreadAttachmentInput) spreadAttachmentInput.value = '0';
        clearSpread.classList.add('hidden');

        // restore parts
        el.frontCover.style.backgroundColor = 'white';
        el.backCover.style.backgroundColor = 'white';
        el.spine.style.backgroundColor = '#f9fafb';
        el.frontCover.style.border = '1px dashed #d1d5db';
        el.backCover.style.border = '1px dashed #d1d5db';
        el.spine.style.borderLeft = '1px solid #e5e7eb';
        el.spine.style.borderRight = '1px solid #e5e7eb';

        el.frontCover.innerHTML = '<span class="text-xl font-semibold uppercase tracking-widest text-gray-200">Portada</span>';
        el.backCover.innerHTML = '<span class="text-xl font-semibold uppercase tracking-widest text-gray-200">Contraportada</span>';
        el.spine.innerHTML = '<div class="spine-text text-xs text-gray-400 font-semibold uppercase tracking-wider rotate-90 whitespace-nowrap">Lomo</div>';

        // If there are images in the individuals, re-trigger them visually
        if (uploadFrontFlapImage.value) applyImageToCover({
            originalUrl: uploadFrontFlapImage.value,
            previewUrl: uploadFrontFlapImage.value,
            attachmentId: document.getElementById('upload-front-flap-image-attachment-id') ? document.getElementById('upload-front-flap-image-attachment-id').value : 0
        }, el.frontFlap, uploadFrontFlapImage, document.getElementById('upload-front-flap-image-attachment-id'), clearFrontFlap);
        if (uploadBackFlapImage.value) applyImageToCover({
            originalUrl: uploadBackFlapImage.value,
            previewUrl: uploadBackFlapImage.value,
            attachmentId: document.getElementById('upload-back-flap-image-attachment-id') ? document.getElementById('upload-back-flap-image-attachment-id').value : 0
        }, el.backFlap, uploadBackFlapImage, document.getElementById('upload-back-flap-image-attachment-id'), clearBackFlap);

        if (uploadFront.value) applyImageToCover({
            originalUrl: uploadFront.value,
            previewUrl: uploadFront.value,
            attachmentId: document.getElementById('upload-front-cover-attachment-id') ? document.getElementById('upload-front-cover-attachment-id').value : 0
        }, el.frontCover, uploadFront, document.getElementById('upload-front-cover-attachment-id'), clearFront);
        if (uploadBack.value) applyImageToCover({
            originalUrl: uploadBack.value,
            previewUrl: uploadBack.value,
            attachmentId: document.getElementById('upload-back-cover-attachment-id') ? document.getElementById('upload-back-cover-attachment-id').value : 0
        }, el.backCover, uploadBack, document.getElementById('upload-back-cover-attachment-id'), clearBack);
        if (uploadSpine.value) {
            applyImageToCover({
                originalUrl: uploadSpine.value,
                previewUrl: uploadSpine.value,
                attachmentId: document.getElementById('upload-spine-image-attachment-id') ? document.getElementById('upload-spine-image-attachment-id').value : 0
            }, el.spine, uploadSpine, document.getElementById('upload-spine-image-attachment-id'), clearSpine);
        } else if (spineColorPicker.value && spineColorPicker.value !== '#f9fafb') {
            el.spine.style.backgroundColor = spineColorPicker.value;
            el.spine.innerHTML = '';
            clearSpine.classList.remove('hidden');
        }
        refreshImageDiagnostics();
    });

    // Accordions
    toggleImagesBtn.addEventListener('click', () => {
        el.imagesContent.classList.toggle('hidden');
        el.imagesContent.classList.toggle('flex');
        document.getElementById('images-section-icon').classList.toggle('-rotate-90');

        if (!el.imagesContent.classList.contains('hidden')) {
            refreshImageDiagnostics(true);
        }
    });

    toggleFlapsBtn.addEventListener('click', () => {
        el.flapsContent.classList.toggle('hidden');
        el.flapsContent.classList.toggle('flex');
        flapsIcon.classList.toggle('-rotate-90');
    });

    // Register exports
    window.CoverEditor.actions.applyImageToCover = applyImageToCover;
    window.CoverEditor.actions.applySpreadImage = applySpreadImage;
    window.CoverEditor.actions.openMediaUploader = openMediaUploader;
});
