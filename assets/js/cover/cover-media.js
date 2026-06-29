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

    function setMediaImage(targetEl, url, fit) {
        targetEl.style.backgroundImage = `url(${url})`;

        let mediaImg = targetEl.querySelector(':scope > img.cover-media-image');
        if (!mediaImg) {
            mediaImg = document.createElement('img');
            mediaImg.className = `cover-media-image ${fit === 'contain' ? 'cover-media-image--contain' : 'cover-media-image--cover'}`;
            mediaImg.alt = '';
            mediaImg.setAttribute('aria-hidden', 'true');
            mediaImg.style.zIndex = '0';
            targetEl.prepend(mediaImg);
        } else {
            mediaImg.classList.remove('cover-media-image--cover', 'cover-media-image--contain');
            mediaImg.classList.add(fit === 'contain' ? 'cover-media-image--contain' : 'cover-media-image--cover');
        }

        mediaImg.src = url;
    }

    function clearMediaImage(targetEl) {
        const mediaImg = targetEl.querySelector(':scope > img.cover-media-image');
        if (mediaImg) {
            mediaImg.remove();
        }
    }

    function openMediaUploader(title, onSelect) {
        if (mediaFrame) {
            mediaFrame.open();
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
            onSelect(attachment.url);
        });
        
        mediaFrame.open();
    }

    function applyImageToCover(url, targetEl, inputEl, clearBtn) {
        targetEl.querySelectorAll(':scope > :not(img.cover-media-image)').forEach(node => node.remove());
        setMediaImage(targetEl, url, 'cover');
        inputEl.value = url;
        clearBtn.classList.remove('hidden');
    }

    function applySpreadImage(url) {
        setMediaImage(el.coverSpread, url, 'cover');
        uploadSpread.value = url;
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
    }

    // Bind Image Buttons
    btnFront.addEventListener('click', () => {
        openMediaUploader('Seleccionar Portada', (url) => {
            applyImageToCover(url, el.frontCover, uploadFront, clearFront);
            if (uploadSpread.value) clearSpread.click();
        });
    });

    btnBack.addEventListener('click', () => {
        openMediaUploader('Seleccionar Contraportada', (url) => {
            applyImageToCover(url, el.backCover, uploadBack, clearBack);
            if (uploadSpread.value) clearSpread.click();
        });
    });

    btnSpine.addEventListener('click', () => {
        openMediaUploader('Seleccionar Lomo', (url) => {
            applyImageToCover(url, el.spine, uploadSpine, clearSpine);
            if (uploadSpread.value) clearSpread.click();
        });
    });

    btnSpread.addEventListener('click', () => openMediaUploader('Seleccionar Spread', applySpreadImage));

    function openMediaUploaderTarget(inputEl, targetEl, clearBtn) {
        openMediaUploader('Seleccionar Imagen', (url) => {
            applyImageToCover(url, targetEl, inputEl, clearBtn);
        });
    }

    btnFrontFlapImage.addEventListener('click', () => openMediaUploaderTarget(uploadFrontFlapImage, el.frontFlap, clearFrontFlap));
    btnBackFlapImage.addEventListener('click', () => openMediaUploaderTarget(uploadBackFlapImage, el.backFlap, clearBackFlap));

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
        spineColorPicker.value = '#f9fafb';
        el.spine.innerHTML = '<div class="spine-text text-xs text-gray-400 font-semibold uppercase tracking-wider rotate-90 whitespace-nowrap">Lomo</div>';
        clearSpine.classList.add('hidden');
    });
    
    clearFrontFlap.addEventListener('click', () => {
        el.frontFlap.style.backgroundImage = '';
        clearMediaImage(el.frontFlap);
        el.frontFlap.style.backgroundColor = '';
        uploadFrontFlapImage.value = '';
        frontFlapColorPicker.value = '#ffffff';
        const span = el.frontFlap.querySelector('span');
        if (span) span.style.display = 'block';
        clearFrontFlap.classList.add('hidden');
    });

    clearBackFlap.addEventListener('click', () => {
        el.backFlap.style.backgroundImage = '';
        clearMediaImage(el.backFlap);
        el.backFlap.style.backgroundColor = '';
        uploadBackFlapImage.value = '';
        backFlapColorPicker.value = '#ffffff';
        const span = el.backFlap.querySelector('span');
        if (span) span.style.display = 'block';
        clearBackFlap.classList.add('hidden');
    });
    
    clearSpread.addEventListener('click', () => {
        el.coverSpread.style.backgroundImage = 'none';
        clearMediaImage(el.coverSpread);
        uploadSpread.value = '';
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
        if (uploadFrontFlapImage.value) applyImageToCover(uploadFrontFlapImage.value, el.frontFlap, uploadFrontFlapImage, clearFrontFlap);
        if (uploadBackFlapImage.value) applyImageToCover(uploadBackFlapImage.value, el.backFlap, uploadBackFlapImage, clearBackFlap);

        if (uploadFront.value) applyImageToCover(uploadFront.value, el.frontCover, uploadFront, clearFront);
        if (uploadBack.value) applyImageToCover(uploadBack.value, el.backCover, uploadBack, clearBack);
        if (uploadSpine.value) {
            applyImageToCover(uploadSpine.value, el.spine, uploadSpine, clearSpine);
        } else if (spineColorPicker.value && spineColorPicker.value !== '#f9fafb') {
            el.spine.style.backgroundColor = spineColorPicker.value;
            el.spine.innerHTML = '';
            clearSpine.classList.remove('hidden');
        }
    });

    // Accordions
    toggleImagesBtn.addEventListener('click', () => {
        el.imagesContent.classList.toggle('hidden');
        el.imagesContent.classList.toggle('flex');
        document.getElementById('images-section-icon').classList.toggle('-rotate-90');
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
