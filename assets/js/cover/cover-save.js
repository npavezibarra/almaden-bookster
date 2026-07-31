// cover-save.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;
    const act = window.CoverEditor.actions;

    const saveCoverBtn = document.getElementById('save-cover-btn');
    const spineWidthMode = document.getElementById('spine-width-mode');
    const spineWidthMm = document.getElementById('spine-width-mm');
    const frontFlapWidth = document.getElementById('front-flap-width');
    const backFlapWidth = document.getElementById('back-flap-width');
    const foldXMm = document.getElementById('fold-x');
    const uploadFront = document.getElementById('upload-front-cover');
    const uploadFrontAttachment = document.getElementById('upload-front-cover-attachment-id');
    const uploadBack = document.getElementById('upload-back-cover');
    const uploadBackAttachment = document.getElementById('upload-back-cover-attachment-id');
    const uploadSpine = document.getElementById('upload-spine-image');
    const uploadSpineAttachment = document.getElementById('upload-spine-image-attachment-id');
    const spineColorPicker = document.getElementById('spine-color-picker');
    const uploadSpread = document.getElementById('upload-full-spread');
    const uploadSpreadAttachment = document.getElementById('upload-full-spread-attachment-id');
    const uploadFrontFlapImage = document.getElementById('upload-front-flap-image');
    const uploadFrontFlapAttachment = document.getElementById('upload-front-flap-image-attachment-id');
    const frontFlapColorPicker = document.getElementById('front-flap-color-picker');
    const uploadBackFlapImage = document.getElementById('upload-back-flap-image');
    const uploadBackFlapAttachment = document.getElementById('upload-back-flap-image-attachment-id');
    const backFlapColorPicker = document.getElementById('back-flap-color-picker');

    const clearFront = document.getElementById('clear-front-cover');
    const clearBack = document.getElementById('clear-back-cover');
    const clearSpine = document.getElementById('clear-spine');
    const clearFrontFlap = document.getElementById('clear-front-flap');
    const clearBackFlap = document.getElementById('clear-back-flap');

    saveCoverBtn.addEventListener('click', () => {
        saveCoverBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
        
        const data = new FormData();
        data.append('action', 'almaden_save_cover_settings');
        data.append('book_id', coverData.bookId);
        data.append('nonce', coverData.nonce);
        data.append('paper_type', el.paperTypeSelect.value);
        data.append('spine_width_mode', spineWidthMode.value);
        data.append('spine_width_mm', spineWidthMm.value);
        data.append('front_flap_width', frontFlapWidth.value);
        data.append('back_flap_width', backFlapWidth.value);
        data.append('fold_x', foldXMm ? foldXMm.value : '0');
        data.append('front_image', uploadFront.value);
        data.append('front_image_attachment_id', uploadFrontAttachment ? uploadFrontAttachment.value : '0');
        data.append('back_image', uploadBack.value);
        data.append('back_image_attachment_id', uploadBackAttachment ? uploadBackAttachment.value : '0');
        data.append('spine_image', uploadSpine.value);
        data.append('spine_image_attachment_id', uploadSpineAttachment ? uploadSpineAttachment.value : '0');
        data.append('spine_color', spineColorPicker.value);
        data.append('spread_image', uploadSpread.value);
        data.append('spread_image_attachment_id', uploadSpreadAttachment ? uploadSpreadAttachment.value : '0');
        
        data.append('front_flap_image', uploadFrontFlapImage.value);
        data.append('front_flap_image_attachment_id', uploadFrontFlapAttachment ? uploadFrontFlapAttachment.value : '0');
        data.append('front_flap_color', frontFlapColorPicker.value);
        data.append('back_flap_image', uploadBackFlapImage.value);
        data.append('back_flap_image_attachment_id', uploadBackFlapAttachment ? uploadBackFlapAttachment.value : '0');
        data.append('back_flap_color', backFlapColorPicker.value);

        data.append('text_layers', JSON.stringify(s.textLayers));

        fetch(coverData.ajaxUrl, {
            method: 'POST',
            body: data
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                saveCoverBtn.innerHTML = '<i class="fa-solid fa-check text-emerald-400"></i> Guardado';
                setTimeout(() => {
                    saveCoverBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Portada';
                }, 2000);
            } else {
                alert('Error al guardar: ' + response.data);
                saveCoverBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Portada';
            }
        })
        .catch(err => {
            console.error(err);
            saveCoverBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Portada';
        });
    });

    function initCoverState() {
        if (typeof coverData !== 'undefined' && coverData.settings) {
            const settings = coverData.settings;
            const roundUpMm = window.CoverEditor.utils && typeof window.CoverEditor.utils.roundUpMm === 'function'
                ? window.CoverEditor.utils.roundUpMm
                : function roundUpMm(value) {
                    const num = parseFloat(value);
                    return Number.isFinite(num) && num > 0 ? Math.ceil(num) : 0;
                };
            const resolveMedia = (baseKey) => {
                const previewSafe = settings[`${baseKey}_preview_safe`] === true;
                const originalUrl = settings[`${baseKey}_original_url`] || settings[baseKey] || '';
                const previewUrl = settings[`${baseKey}_preview_url`] || '';
                return {
                    originalUrl,
                    previewUrl,
                    attachmentId: settings[`${baseKey}_attachment_id`] || 0,
                    previewSafe
                };
            };

            if (settings.paper_type) el.paperTypeSelect.value = settings.paper_type;

            if (settings.spine_width_mode) {
                spineWidthMode.value = settings.spine_width_mode;
            }
            if (settings.spine_width_mm !== undefined && settings.spine_width_mm !== null && settings.spine_width_mm !== '') {
                spineWidthMm.value = roundUpMm(settings.spine_width_mm);
            }
            
            const frontFlap = settings.front_flap_width || settings.front_flap;
            if (frontFlap) {
                frontFlapWidth.value = roundUpMm(frontFlap);
            }
            
            const backFlap = settings.back_flap_width || settings.back_flap;
            if (backFlap) {
                backFlapWidth.value = roundUpMm(backFlap);
            }

            const foldX = settings.fold_x !== undefined && settings.fold_x !== null && settings.fold_x !== ''
                ? settings.fold_x
                : settings.fold_x_mm;
            if (foldXMm && foldX !== undefined && foldX !== null && foldX !== '') {
                foldXMm.value = roundUpMm(foldX);
            }
            
            if (settings.spread_image) {
                act.applySpreadImage(resolveMedia('spread_image'));
            } else {
                if (settings.front_image) {
                    act.applyImageToCover(resolveMedia('front_image'), el.frontCover, uploadFront, uploadFrontAttachment, clearFront);
                }
                if (settings.back_image) {
                    act.applyImageToCover(resolveMedia('back_image'), el.backCover, uploadBack, uploadBackAttachment, clearBack);
                }
                
                if (settings.spine_image) {
                    act.applyImageToCover(resolveMedia('spine_image'), el.spine, uploadSpine, uploadSpineAttachment, clearSpine);
                } else if (settings.spine_color && settings.spine_color !== '#f9fafb') {
                    spineColorPicker.value = settings.spine_color;
                    el.spine.style.backgroundColor = settings.spine_color;
                    el.spine.innerHTML = '';
                    clearSpine.classList.remove('hidden');
                }
            }
            
            if (settings.front_flap_image) {
                act.applyImageToCover(resolveMedia('front_flap_image'), el.frontFlap, uploadFrontFlapImage, uploadFrontFlapAttachment, clearFrontFlap);
            } else if (settings.front_flap_color && settings.front_flap_color !== '#ffffff') {
                frontFlapColorPicker.value = settings.front_flap_color;
                el.frontFlap.style.backgroundColor = settings.front_flap_color;
                const span = el.frontFlap.querySelector('span');
                if (span) span.style.display = 'none';
                clearFrontFlap.classList.remove('hidden');
            }

            if (settings.back_flap_image) {
                act.applyImageToCover(resolveMedia('back_flap_image'), el.backFlap, uploadBackFlapImage, uploadBackFlapAttachment, clearBackFlap);
            } else if (settings.back_flap_color && settings.back_flap_color !== '#ffffff') {
                backFlapColorPicker.value = settings.back_flap_color;
                el.backFlap.style.backgroundColor = settings.back_flap_color;
                const span = el.backFlap.querySelector('span');
                if (span) span.style.display = 'none';
                clearBackFlap.classList.remove('hidden');
            }

            if (settings.text_layers && Array.isArray(settings.text_layers)) {
                s.textLayers = settings.text_layers.filter(l => l && typeof l === 'object' && l.id);
            }
        }

        act.updateDimensions();
        act.renderTextLayers();
        act.renderLayersPanel();
        act.fitToScreen();
    }

    initCoverState();
});
