document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('create-modal');
    const form = document.getElementById('create-book-form');

    if (!modal || !form) {
        return;
    }

    const stepIndicator = document.getElementById('step-indicator');
    const progressBar = document.getElementById('progress-bar');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    const titleInput = document.getElementById('book_title');
    const authorInput = document.getElementById('book_author');
    const templateInput = document.getElementById('book_template');
    const templateLabelInput = document.getElementById('book_template_label');
    const initialTemplateId = templateInput?.value || '';
    const initialTemplateLabel = templateLabelInput?.value || 'Literat';

    const slides = {
        1: document.getElementById('wizard-step-1'),
        2: document.getElementById('wizard-step-2'),
        custom: document.getElementById('wizard-step-custom'),
        templates: document.getElementById('wizard-step-templates')
    };

    const sizeRadios = Array.from(document.querySelectorAll('.size-radio'));
    const sizeCards = Array.from(document.querySelectorAll('[data-size-card]'));
    const templateButtons = Array.from(document.querySelectorAll('[data-template-button]'));

    const customContainer = document.getElementById('custom-dimensions-container');
    const customWidthInput = document.getElementById('almaden_custom_width');
    const customHeightInput = document.getElementById('almaden_custom_height');

    const customMarginInputs = [
        document.getElementById('almaden_custom_margin_top'),
        document.getElementById('almaden_custom_margin_bottom'),
        document.getElementById('almaden_custom_margin_outer'),
        document.getElementById('almaden_custom_margin_inner')
    ].filter(Boolean);

    const templatePreviewMain = document.getElementById('template-preview-main');

    function normalizeTemplateId(templateId) {
        const value = String(templateId || '').trim();
        if (value === 'literary' || value === 'literat') {
            return initialTemplateId || value;
        }

        return value || initialTemplateId;
    }

    let currentStep = 1;
    let isCustomSize = false;

    function getTotalSteps() {
        return isCustomSize ? 4 : 3;
    }

    function getStepSlide() {
        if (currentStep === 1) {
            return slides[1];
        }

        if (currentStep === 2) {
            return slides[2];
        }

        if (currentStep === 3 && isCustomSize) {
            return slides.custom;
        }

        if (currentStep === 4) {
            return slides.templates;
        }

        if (currentStep === 3) {
            return slides.templates;
        }

        return slides.templates;
    }

    function setSlideVisibility(activeSlide) {
        Object.values(slides).forEach((slide) => {
            if (!slide) {
                return;
            }
            slide.classList.add('hidden');
        });

        if (activeSlide) {
            activeSlide.classList.remove('hidden');
        }
    }

    function updateSizeCardState() {
        sizeCards.forEach((card) => {
            const radio = card.querySelector('input[type="radio"]');
            const isSelected = radio && radio.checked;

            card.classList.toggle('border-slate-900', isSelected);
            card.classList.toggle('bg-slate-50', isSelected);
            card.classList.toggle('shadow-sm', isSelected);
            card.classList.toggle('ring-1', isSelected);
            card.classList.toggle('ring-slate-900/10', isSelected);

            if (!isSelected) {
                card.classList.remove('border-slate-900', 'bg-slate-50', 'shadow-sm', 'ring-1', 'ring-slate-900/10');
            }
        });
    }

    const literatPreview = {
        name: 'Literat',
        fontFamily: `'Cormorant Garamond', serif`,
        pageWidthCm: 14,
        pageHeightCm: 21,
        marginCm: 2,
        pageShadow: 'shadow-[0_12px_30px_-10px_rgba(15,23,42,0.18)]',
        pageBorder: 'border-slate-200',
        titleColor: 'text-slate-900',
        bodyColor: 'text-slate-700',
        metaColor: 'text-slate-400',
        accentLine: 'bg-slate-200',
        pageNumberColor: 'text-slate-600',
        maxDisplayHeightPx: 380,
        h1Pt: 21,
        h1Weight: 500,
        h2Pt: 16,
        h2Italic: true,
        h3Pt: 13,
        bodyPt: 12,
        bodyLineHeight: 1.2,
        notePt: 9,
        footnotePt: 6,
        pageNumberPx: 6,
        headingFontFamily: `'Cormorant Garamond', serif`,
        bodyFontFamily: `'Cormorant Garamond', serif`,
        page1: {
            eyebrow: 'CAPÍTULO I',
            title: 'El Comienzo del Viaje',
            paragraphs: [
                'En un lugar de la mancha, de cuyo nombre no quiero acordarme, no ha mucho tiempo que vivía un hidalgo de los de lanza en astillero. La luz de la mañana penetraba suavemente por el ventanuco.',
                'Su biblioteca olía a papel antiguo, cera y tabaco frío. Aquella mañana, al abrir la ventana, sintió que algo estaba por cambiar en el orden de su vida.'
            ],
            quote: '“La lectura es la respiración secreta de la memoria.”',
            bullets: ['Apertura narrativa', 'Tensión inicial', 'Detalle atmosférico'],
            footer: '1'
        },
        page2: {
            eyebrow: 'SECCIÓN 1.1',
            title: '1.1 Análisis Estructural',
            paragraphs: [
                'Los elementos compositivos interactúan mediante proporciones áureas, manteniendo una jerarquía visual clara y un equilibrio estético riguroso en todo el cuerpo de la obra.',
                'Cada bloque de información ha sido optimizado para asegurar una lectura fluida, con subtítulos, citas y notas al pie bien diferenciadas.'
            ],
            bullets: ['Jerarquía tipográfica', 'Márgenes consistentes', 'Lectura fluida'],
            noteTitle: 'Nota al pie',
            note: 'Datos obtenidos del registro tipográfico ISO. Esta línea funciona como referencia secundaria dentro de la composición.',
            footer: '2'
        },
        page3: {
            eyebrow: 'JAMES JOYCE',
            title: 'La revolución del estilo',
            imageCaption: 'Retrato del artista adolescente y el monólogo interior',
            note: 'Con Retrato del artista adolescente (1916), Joyce inauguró una etapa de intensa experimentación formal. La novela narra el crecimiento intelectual y espiritual de Stephen Dedalus.',
            footer: '3'
        }
    };

    function getLiteratScale() {
        const pageHeightInches = literatPreview.pageHeightCm / 2.54;
        const nativePageHeightPx = pageHeightInches * 96;

        return literatPreview.maxDisplayHeightPx / nativePageHeightPx;
    }

    function cmToPx(cm) {
        return ((cm / 2.54) * 96) * getLiteratScale();
    }

    function ptToPx(pt) {
        return ((pt * 96) / 72) * getLiteratScale();
    }

    function renderPageNumber(footer) {
        return `
            <div class="border-t border-slate-100 pt-3 text-center ${literatPreview.pageNumberColor}" style="font-size: ${literatPreview.pageNumberPx}px; line-height: 1;">${footer}</div>
        `;
    }

    function renderLiteratPageOne() {
        const paddingPx = cmToPx(literatPreview.marginCm);
        const bodyFontSize = ptToPx(literatPreview.bodyPt);
        const titleFontSize = ptToPx(literatPreview.h1Pt);
        const quoteFontSize = ptToPx(16);
        const bulletFontSize = ptToPx(12);
        const metaFontSize = ptToPx(16);

        return `
            <article class="relative flex h-full w-full flex-col overflow-hidden border ${literatPreview.pageBorder} bg-white ${literatPreview.pageShadow} text-slate-900" style="aspect-ratio: 14 / 21; padding: ${paddingPx}px; font-family: ${literatPreview.fontFamily};">
                <div class="flex-1">
                    <div class="mb-3 uppercase tracking-[0.28em] ${literatPreview.metaColor}" style="font-size: ${metaFontSize}px; line-height: 1; font-weight: 600;">${literatPreview.page1.eyebrow}</div>
                    <h1 class="${literatPreview.titleColor}" style="font-size: ${titleFontSize}px; line-height: 1.02; font-weight: 600; font-family: ${literatPreview.headingFontFamily};">${literatPreview.page1.title}</h1>
                    <div class="mt-4 space-y-3 ${literatPreview.bodyColor}" style="font-size: ${bodyFontSize}px; line-height: ${literatPreview.bodyLineHeight}; font-family: ${literatPreview.bodyFontFamily};">
                        ${literatPreview.page1.paragraphs.map((paragraph) => `<p class="text-justify">${paragraph}</p>`).join('')}
                    </div>
                    <blockquote class="mt-4 border-l border-slate-200 pl-3 italic text-slate-700" style="font-size: ${quoteFontSize}px; line-height: 1.35; font-family: ${literatPreview.bodyFontFamily};">
                        ${literatPreview.page1.quote}
                    </blockquote>
                    <ul class="mt-4 space-y-1 pl-4 text-slate-700" style="font-size: ${bulletFontSize}px; line-height: 1.35; list-style: disc; font-family: ${literatPreview.bodyFontFamily};">
                        ${literatPreview.page1.bullets.map((bullet) => `<li>${bullet}</li>`).join('')}
                    </ul>
                </div>
                ${renderPageNumber(literatPreview.page1.footer)}
            </article>
        `;
    }

    function renderLiteratPageTwo() {
        const paddingPx = cmToPx(literatPreview.marginCm);
        const bodyFontSize = ptToPx(literatPreview.bodyPt);
        const titleFontSize = ptToPx(literatPreview.h2Pt);
        const sectionFontSize = ptToPx(literatPreview.h3Pt);
        const noteFontSize = ptToPx(literatPreview.h3Pt);
        const footnoteFontSize = ptToPx(literatPreview.notePt);

        return `
            <article class="relative flex h-full w-full flex-col overflow-hidden border ${literatPreview.pageBorder} bg-white ${literatPreview.pageShadow} text-slate-900" style="aspect-ratio: 14 / 21; padding: ${paddingPx}px; font-family: ${literatPreview.fontFamily};">
                <div class="flex-1">
                    <div class="flex items-start border-b border-slate-100 pb-2">
                        <span class="uppercase tracking-[0.28em] ${literatPreview.metaColor}" style="font-size: ${sectionFontSize}px; line-height: 1; font-weight: 600; font-family: ${literatPreview.headingFontFamily};">${literatPreview.page2.eyebrow}</span>
                    </div>
                    <h2 class="mt-4 ${literatPreview.titleColor}" style="font-size: ${titleFontSize}px; line-height: 1.05; font-weight: 400; font-style: italic; font-family: ${literatPreview.headingFontFamily};">${literatPreview.page2.title}</h2>
                    <div class="mt-4 space-y-3 ${literatPreview.bodyColor}" style="font-size: ${bodyFontSize}px; line-height: ${literatPreview.bodyLineHeight}; font-family: ${literatPreview.bodyFontFamily};">
                        ${literatPreview.page2.paragraphs.map((paragraph) => `<p class="text-justify">${paragraph}</p>`).join('')}
                    </div>
                    <h3 class="mt-4 ${literatPreview.titleColor}" style="font-size: ${sectionFontSize}px; line-height: 1.15; font-weight: 400; font-family: ${literatPreview.headingFontFamily};">1.1.1 Variables</h3>
                    <ul class="mt-3 space-y-1.5 pl-4 ${literatPreview.bodyColor}" style="font-size: ${noteFontSize}px; line-height: 1.35; list-style: disc; font-family: ${literatPreview.bodyFontFamily};">
                        ${literatPreview.page2.bullets.map((bullet) => `<li>${bullet}</li>`).join('')}
                    </ul>
                    <div class="mt-4 border-t border-dashed border-slate-200 pt-3">
                        <div class="font-semibold uppercase tracking-[0.22em] ${literatPreview.metaColor}" style="font-size: ${footnoteFontSize}px; line-height: 1; font-family: ${literatPreview.headingFontFamily};">${literatPreview.page2.noteTitle}</div>
                        <p class="mt-2 text-slate-500" style="font-size: ${footnoteFontSize}px; line-height: 1.45; font-family: ${literatPreview.bodyFontFamily};">${literatPreview.page2.note}</p>
                    </div>
                </div>
                ${renderPageNumber(literatPreview.page2.footer)}
            </article>
        `;
    }

    function renderLiteratPageThree() {
        const paddingPx = cmToPx(literatPreview.marginCm);
        const bodyFontSize = ptToPx(literatPreview.bodyPt);
        const titleFontSize = ptToPx(literatPreview.h1Pt);
        const metaFontSize = ptToPx(literatPreview.h3Pt);
        const captionFontSize = ptToPx(literatPreview.h3Pt);
        const noteFontSize = ptToPx(literatPreview.notePt);
        const imageHeightPx = cmToPx(7.8);

        return `
            <article class="relative flex h-full w-full flex-col overflow-hidden border ${literatPreview.pageBorder} bg-white ${literatPreview.pageShadow} text-slate-900" style="aspect-ratio: 14 / 21; padding: ${paddingPx}px; font-family: ${literatPreview.fontFamily};">
                <div class="flex-1">
                    <div class="mb-4 text-center uppercase tracking-[0.34em] ${literatPreview.metaColor}" style="font-size: ${metaFontSize}px; line-height: 1; font-weight: 600; font-family: ${literatPreview.headingFontFamily};">${literatPreview.page3.eyebrow}</div>
                    <h1 class="text-center ${literatPreview.titleColor}" style="font-size: ${titleFontSize}px; line-height: 1.1; font-weight: ${literatPreview.h1Weight}; font-family: ${literatPreview.headingFontFamily};">${literatPreview.page3.title}</h1>
                    <h2 class="mt-2 text-center italic text-slate-700" style="font-size: ${ptToPx(literatPreview.h2Pt)}px; line-height: 1.15; font-weight: 400; font-family: ${literatPreview.headingFontFamily};">${literatPreview.page3.imageCaption}</h2>
                    <div class="mt-4 overflow-hidden rounded-[0.9rem] border border-slate-200 bg-slate-50 shadow-inner" style="height: ${imageHeightPx}px;">
                        <div class="flex h-full w-full items-center justify-center">
                            <div class="flex h-[78%] w-[88%] items-center justify-center rounded-[1rem] border border-dashed border-slate-300 bg-white text-slate-400" style="font-size: ${ptToPx(12)}px; line-height: 1; font-family: ${literatPreview.bodyFontFamily};">
                                Imagen
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3 ${literatPreview.bodyColor}" style="font-size: ${bodyFontSize}px; line-height: ${literatPreview.bodyLineHeight}; font-family: ${literatPreview.bodyFontFamily};">
                        <p class="text-justify">${literatPreview.page3.note}</p>
                        <p class="text-justify">La composición deja respirar el texto, alternando bloques tipográficos y una figura dominante que ocupa la mitad del área útil en altura.</p>
                    </div>
                    <div class="mt-4 border-t border-dashed border-slate-200 pt-3">
                        <div class="text-slate-500" style="font-size: ${noteFontSize}px; line-height: 1.4; font-family: ${literatPreview.bodyFontFamily};">* Nota al pie: Datos obtenidos del registro tipográfico ISO.</div>
                    </div>
                </div>
                ${renderPageNumber(literatPreview.page3.footer)}
            </article>
        `;
    }

    function renderTemplatePreview(templateId) {
        if (!templatePreviewMain || templatePreviewMain.classList.contains('hidden')) {
            return;
        }

        templatePreviewMain.className = 'w-full';

        templatePreviewMain.innerHTML = `
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3 md:gap-8 items-start justify-items-stretch">
                ${renderLiteratPageOne()}
                ${renderLiteratPageTwo()}
                ${renderLiteratPageThree()}
            </div>
        `;
    }

    function setTemplate(templateId) {
        const normalizedId = normalizeTemplateId(templateId);

        if (templateInput) {
            templateInput.value = normalizedId;
        }

        if (templateLabelInput) {
            const selectedButton = templateButtons.find((button) => (
                normalizeTemplateId(button.getAttribute('data-template-value')) === normalizedId
            ));
            templateLabelInput.value = selectedButton?.getAttribute('data-template-label') || initialTemplateLabel;
        }

        templateButtons.forEach((button) => {
            button.className = 'rounded-2xl border border-black bg-black px-3 py-3 text-center text-xs font-semibold text-white transition hover:border-black hover:bg-slate-900';
        });

        renderTemplatePreview(normalizedId);
    }

    function updateCustomFieldVisibility() {
        const sizeValue = sizeRadios.find((radio) => radio.checked)?.value || '';
        isCustomSize = sizeValue === 'custom';

        if (customContainer) {
            customContainer.classList.toggle('hidden', !isCustomSize);
        }

        if (customWidthInput) {
            customWidthInput.required = isCustomSize;
        }

        if (customHeightInput) {
            customHeightInput.required = isCustomSize;
        }

        customMarginInputs.forEach((input) => {
            input.required = isCustomSize;
        });
    }

    function updateFooterState() {
        const totalSteps = getTotalSteps();
        const atFirstStep = currentStep === 1;
        const atFinalStep = currentStep === totalSteps;

        if (stepIndicator) {
            stepIndicator.textContent = `Paso ${currentStep} de ${totalSteps}`;
        }

        if (progressBar) {
            progressBar.style.width = `${Math.max(1, (currentStep / totalSteps) * 100)}%`;
        }

        if (prevBtn) {
            prevBtn.style.opacity = atFirstStep ? '0' : '1';
            prevBtn.style.pointerEvents = atFirstStep ? 'none' : 'auto';
        }

        if (nextBtn) {
            if (atFinalStep) {
                nextBtn.textContent = 'CREAR LIBRO';
            } else {
                nextBtn.textContent = 'Siguiente';
            }
        }
    }

    function updateView() {
        const totalSteps = getTotalSteps();
        if (currentStep > totalSteps) {
            currentStep = totalSteps;
        }

        setSlideVisibility(getStepSlide());
        updateFooterState();
        updateSizeCardState();
    }

    function validateStepOne() {
        const titleOk = !!(titleInput && titleInput.value.trim());
        const authorOk = !!(authorInput && authorInput.value.trim());

        if (!titleOk && titleInput) {
            titleInput.reportValidity();
        }

        if (!authorOk && authorInput) {
            authorInput.reportValidity();
        }

        return titleOk && authorOk;
    }

    function validateCustomSize() {
        const widthOk = !!(customWidthInput && customWidthInput.value.trim());
        const heightOk = !!(customHeightInput && customHeightInput.value.trim());

        if (!widthOk && customWidthInput) {
            customWidthInput.reportValidity();
        }

        if (!heightOk && customHeightInput) {
            customHeightInput.reportValidity();
        }

        return widthOk && heightOk;
    }

    function submitForm() {
        if (form.requestSubmit) {
            form.requestSubmit();
            return;
        }

        form.submit();
    }

    function goNext() {
        if (currentStep === 1) {
            if (!validateStepOne()) {
                return;
            }
            currentStep = 2;
            updateView();
            return;
        }

        if (currentStep === 2) {
            currentStep = 3;
            updateView();
            return;
        }

        if (isCustomSize && currentStep === 3) {
            if (!validateCustomSize()) {
                return;
            }

            currentStep = 4;
            updateView();
            return;
        }

        submitForm();
    }

    function goPrev() {
        if (currentStep > 1) {
            currentStep -= 1;
            updateView();
        }
    }

    function resetWizard() {
        form.reset();
        currentStep = 1;
        updateCustomFieldVisibility();
        setTemplate(initialTemplateId);
        updateSizeCardState();
        updateView();

        if (titleInput) {
            window.setTimeout(() => {
                titleInput.focus();
            }, 50);
        }
    }

    sizeRadios.forEach((radio) => {
        radio.addEventListener('change', function() {
            updateCustomFieldVisibility();

            if (currentStep > getTotalSteps()) {
                currentStep = getTotalSteps();
            }

            updateView();
        });
    });

    sizeCards.forEach((card) => {
        card.addEventListener('click', function() {
            const radio = card.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });

    templateButtons.forEach((button) => {
        button.addEventListener('click', function() {
            const templateId = button.getAttribute('data-template-value') || initialTemplateId;
            setTemplate(templateId);
        });
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', goPrev);
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', goNext);
    }

    modal.addEventListener('click', function(event) {
        if (event.target === modal || event.target.matches('[data-create-modal-backdrop]')) {
            if (window.closeModal) {
                window.closeModal();
            }
        }
    });

    document.addEventListener('almaden-book-create-modal:open', resetWizard);
    document.addEventListener('almaden-book-create-modal:close', function() {
        currentStep = 1;
    });

    updateCustomFieldVisibility();
    setTemplate(initialTemplateId);
    updateView();
});
