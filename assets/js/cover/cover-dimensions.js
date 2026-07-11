// cover-dimensions.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;
    const utils = window.CoverEditor.utils;

    // References to UI that affects dimensions
    const frontFlapWidth = document.getElementById('front-flap-width');
    const backFlapWidth = document.getElementById('back-flap-width');
    const foldXWrapper = el.foldXWrapper;
    const foldXMm = el.foldXMm;

    function updateRulerOverlay(totalSpreadWidth, actualHeightPx) {
        const rulerSizePx = s.rulerSizePx || 24;
        const showGuides = s.showGuides !== false;
        const showRuler = !!s.showRuler && showGuides;
        const extraPx = showRuler ? rulerSizePx : 0;
        const layoutWidthPx = totalSpreadWidth + extraPx;
        const layoutHeightPx = actualHeightPx + extraPx;

        s.coverLayoutWidthPx = layoutWidthPx;
        s.coverLayoutHeightPx = layoutHeightPx;

        if (el.coverStage) {
            el.coverStage.style.width = `${layoutWidthPx}px`;
            el.coverStage.style.height = `${layoutHeightPx}px`;
        }

        if (el.coverSpread) {
            el.coverSpread.style.marginLeft = showRuler ? `${rulerSizePx}px` : '0px';
            el.coverSpread.style.marginTop = showRuler ? `${rulerSizePx}px` : '0px';
        }

        if (el.rulerOverlay) {
            el.rulerOverlay.style.display = showRuler ? 'block' : 'none';
            el.rulerOverlay.style.width = `${layoutWidthPx}px`;
            el.rulerOverlay.style.height = `${layoutHeightPx}px`;
        }

        if (el.bleedGuide) {
            el.bleedGuide.style.display = showGuides ? 'block' : 'none';
        }

        document.body.classList.toggle('cover-guides-hidden', !showGuides);

        if (showRuler) {
            if (el.rulerHorizontal) {
                const rulerWidth = Math.max(0, layoutWidthPx - rulerSizePx);
                const major = s.pxPerCm;
                const minor = major / 2;
                const totalCm = Math.ceil(rulerWidth / major);
                const horizontalMarks = [];

                for (let i = 0; i <= totalCm; i++) {
                    const x = i * major;
                    horizontalMarks.push(`<span class="ruler-tick ruler-tick--major" style="left:${x}px; top:0; width:1px; height:24px;"></span>`);
                    if (i < totalCm) {
                        horizontalMarks.push(`<span class="ruler-tick ruler-tick--minor" style="left:${x + minor}px; top:8px; width:1px; height:16px;"></span>`);
                    }
                    if (i > 0) {
                        horizontalMarks.push(`<span class="ruler-label ruler-label--horizontal" style="left:${x}px;">${i}</span>`);
                    }
                }
                el.rulerHorizontal.innerHTML = horizontalMarks.join('');
            }
            if (el.rulerVertical) {
                const rulerHeight = Math.max(0, layoutHeightPx - rulerSizePx);
                const major = s.pxPerCm;
                const minor = major / 2;
                const totalCm = Math.ceil(rulerHeight / major);
                const verticalMarks = [];

                for (let i = 0; i <= totalCm; i++) {
                    const y = i * major;
                    verticalMarks.push(`<span class="ruler-tick ruler-tick--major" style="top:${y}px; left:0; height:1px; width:24px;"></span>`);
                    if (i < totalCm) {
                        verticalMarks.push(`<span class="ruler-tick ruler-tick--minor" style="top:${y + minor}px; left:8px; height:1px; width:16px;"></span>`);
                    }
                    if (i > 0) {
                        verticalMarks.push(`<span class="ruler-label ruler-label--vertical" style="top:${y}px;">${i}</span>`);
                    }
                }
                el.rulerVertical.innerHTML = verticalMarks.join('');
            }
        }

        if (el.rulerToggleBtn) {
            el.rulerToggleBtn.setAttribute('aria-pressed', showRuler ? 'true' : 'false');
            el.rulerToggleBtn.classList.toggle('bg-white', showRuler);
            el.rulerToggleBtn.classList.toggle('shadow-sm', showRuler);
            el.rulerToggleBtn.classList.toggle('text-indigo-600', showRuler);
            el.rulerToggleBtn.classList.toggle('text-gray-600', !showRuler);
        }

        if (el.guideToggleBtn) {
            el.guideToggleBtn.setAttribute('aria-pressed', showGuides ? 'true' : 'false');
            el.guideToggleBtn.classList.toggle('bg-white', showGuides);
            el.guideToggleBtn.classList.toggle('shadow-sm', showGuides);
            el.guideToggleBtn.classList.toggle('text-indigo-600', showGuides);
            el.guideToggleBtn.classList.toggle('text-gray-600', !showGuides);
            const icon = el.guideToggleBtn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', showGuides);
                icon.classList.toggle('fa-eye-slash', !showGuides);
            }
        }
    }

    function updateDimensions() {
        const thicknessMmPerPage = parseFloat(el.paperTypeSelect.value) || 0.06;
        let pages = parseInt(el.pageCountInput.value, 10);

        if (isNaN(pages) || pages < 20) pages = 20;

        const spineWidthMode = utils.getSpineWidthMode ? utils.getSpineWidthMode() : 'auto';
        const autoSpineWidthMm = thicknessMmPerPage * pages;
        const roundUpMm = utils.roundUpMm || function roundUpMm(value) {
            const num = parseFloat(value);
            return Number.isFinite(num) && num > 0 ? Math.ceil(num) : 0;
        };
        let spineWidthMm = autoSpineWidthMm;

        if (spineWidthMode === 'manual') {
            const manualSpineWidthMm = parseFloat(el.spineWidthMm.value);
            spineWidthMm = (!isNaN(manualSpineWidthMm) && manualSpineWidthMm > 0) ? manualSpineWidthMm : autoSpineWidthMm;
            el.spineWidthMm.value = roundUpMm(spineWidthMm);
            el.spineWidthMm.readOnly = false;
            el.spineWidthMm.classList.remove('bg-gray-100', 'text-gray-600');
            el.spineWidthMm.classList.add('bg-white', 'text-gray-800');
            el.spineWidthMm.title = 'Ingresa un ancho manual en mm.';
        } else {
            spineWidthMm = autoSpineWidthMm;
            el.spineWidthMm.value = roundUpMm(autoSpineWidthMm);
            el.spineWidthMm.readOnly = true;
            el.spineWidthMm.classList.add('bg-gray-100', 'text-gray-600');
            el.spineWidthMm.classList.remove('bg-white', 'text-gray-800');
            el.spineWidthMm.title = 'Se calcula automáticamente en modo Auto.';
        }

        spineWidthMm = roundUpMm(spineWidthMm);
        const spineWidthCm = spineWidthMm / 10;
        const spineWidthPx = spineWidthCm * s.pxPerCm;

        // Bleed (5mm)
        const BLEED_MM = 5;
        const bleedPx = (BLEED_MM / 10) * s.pxPerCm;
        const actualHeightPx = s.pageHeightPx + (2 * bleedPx);

        // Update Bleed Guide
        el.bleedGuide.style.top = `${bleedPx}px`;
        el.bleedGuide.style.bottom = `${bleedPx}px`;
        el.bleedGuide.style.left = `${bleedPx}px`;
        el.bleedGuide.style.right = `${bleedPx}px`;

        // Update Spine DOM
        el.spine.style.width = `${spineWidthPx}px`;
        el.spine.style.height = `${actualHeightPx}px`;

        // Flaps calculation
        const frontFlapMm = roundUpMm(parseFloat(frontFlapWidth.value) || 0);
        const backFlapMm = roundUpMm(parseFloat(backFlapWidth.value) || 0);
        const hasFlaps = frontFlapMm > 0 || backFlapMm > 0;
        const foldXValueMm = hasFlaps ? roundUpMm(parseFloat(foldXMm ? foldXMm.value : 0) || 0) : 0;

        if (foldXWrapper) {
            foldXWrapper.classList.toggle('hidden', !hasFlaps);
        }
        
        let frontFlapPx = (frontFlapMm / 10) * s.pxPerCm;
        let backFlapPx = (backFlapMm / 10) * s.pxPerCm;
        
        let frontCoverPx = s.pageWidthPx + ((foldXValueMm / 10) * s.pxPerCm);
        let backCoverPx = s.pageWidthPx + ((foldXValueMm / 10) * s.pxPerCm);
        
        if (frontFlapMm > 0) {
            frontFlapPx += bleedPx; // Outer bleed on flap
            el.frontFlap.style.border = '1px dashed #d1d5db';
            const span = el.frontFlap.querySelector('span');
            if (span) span.classList.remove('hidden');
        } else {
            frontCoverPx += bleedPx; // Outer bleed on cover
            el.frontFlap.style.border = 'none';
            const span = el.frontFlap.querySelector('span');
            if (span) span.classList.add('hidden');
        }
        
        if (backFlapMm > 0) {
            backFlapPx += bleedPx; // Outer bleed on flap
            el.backFlap.style.border = '1px dashed #d1d5db';
            const span = el.backFlap.querySelector('span');
            if (span) span.classList.remove('hidden');
        } else {
            backCoverPx += bleedPx; // Outer bleed on cover
            el.backFlap.style.border = 'none';
            const span = el.backFlap.querySelector('span');
            if (span) span.classList.add('hidden');
        }

        el.frontCover.style.width = `${frontCoverPx}px`;
        el.frontCover.style.height = `${actualHeightPx}px`;
        
        el.backCover.style.width = `${backCoverPx}px`;
        el.backCover.style.height = `${actualHeightPx}px`;

        const totalSpreadWidth = frontCoverPx + backCoverPx + spineWidthPx + frontFlapPx + backFlapPx;
        el.coverSpread.style.width = `${totalSpreadWidth}px`;
        el.coverSpread.style.height = `${actualHeightPx}px`;

        el.frontFlap.style.width = `${frontFlapPx}px`;
        el.frontFlap.style.height = `${actualHeightPx}px`;

        el.backFlap.style.width = `${backFlapPx}px`;
        el.backFlap.style.height = `${actualHeightPx}px`;

        updateRulerOverlay(totalSpreadWidth, actualHeightPx);
    }

    function updateZoom() {
        el.coverScaler.style.transform = `scale(${s.zoomLevel})`;
        el.zoomLevelText.textContent = `${Math.round(s.zoomLevel * 100)}%`;
    }

    function zoomIn() {
        if (s.zoomLevel < 3.0) {
            s.zoomLevel += 0.1;
            s.zoomLevel = Math.round(s.zoomLevel * 10) / 10;
            updateZoom();
        }
    }

    function zoomOut() {
        if (s.zoomLevel > 0.2) {
            s.zoomLevel -= 0.1;
            s.zoomLevel = Math.round(s.zoomLevel * 10) / 10;
            updateZoom();
        }
    }

    function fitToScreen() {
        const containerWidth = el.workspaceContainer.clientWidth;
        const containerHeight = el.workspaceContainer.clientHeight;
        
        const BLEED_MM = 5;
        const bleedPx = (BLEED_MM / 10) * s.pxPerCm;
        const actualHeightPx = s.pageHeightPx + (2 * bleedPx);

        const estimatedSpinePx = ((utils.getSpineWidthMm ? utils.getSpineWidthMm() : (0.06 * 150)) / 10) * s.pxPerCm;
        const roundUpMm = utils.roundUpMm || function roundUpMm(value) {
            const num = parseFloat(value);
            return Number.isFinite(num) && num > 0 ? Math.ceil(num) : 0;
        };
        const frontFlapPx = (roundUpMm(frontFlapWidth.value) / 10) * s.pxPerCm;
        const backFlapPx = (roundUpMm(backFlapWidth.value) / 10) * s.pxPerCm;
        const hasFlaps = frontFlapPx > 0 || backFlapPx > 0;
        const foldXValueMm = hasFlaps && foldXMm ? roundUpMm(foldXMm.value) : 0;
        const foldXPx = (foldXValueMm / 10) * s.pxPerCm;

        const baseWidthPx = (s.pageWidthPx * 2) + (2 * foldXPx) + estimatedSpinePx + frontFlapPx + backFlapPx + (2 * bleedPx);
        const totalWidthPx = s.coverLayoutWidthPx || baseWidthPx;
        const totalHeightPx = s.coverLayoutHeightPx || actualHeightPx;

        const margin = 80;
        const scaleX = (containerWidth - margin) / totalWidthPx;
        const scaleY = (containerHeight - margin) / totalHeightPx;

        let optimalScale = Math.min(scaleX, scaleY);
        optimalScale = Math.floor(optimalScale * 10) / 10;
        
        if (optimalScale < 0.2) optimalScale = 0.2;
        if (optimalScale > 1.5) optimalScale = 1.0;

        s.zoomLevel = optimalScale;
        updateZoom();
    }

    function updateFlaps() {
        updateDimensions();
        fitToScreen();
    }

    // Bind Event Listeners
    el.paperTypeSelect.addEventListener('change', updateDimensions);
    el.pageCountInput.addEventListener('input', updateDimensions);
    el.spineWidthMode.addEventListener('change', updateDimensions);
    el.spineWidthMm.addEventListener('input', updateDimensions);
    if (el.rulerToggleBtn) {
        el.rulerToggleBtn.addEventListener('click', () => {
            s.showRuler = !s.showRuler;
            updateDimensions();
            fitToScreen();
        });
    }
    if (el.guideToggleBtn) {
        el.guideToggleBtn.addEventListener('click', () => {
            s.showGuides = !s.showGuides;
            updateDimensions();
            fitToScreen();
        });
    }
    el.zoomInBtn.addEventListener('click', zoomIn);
    el.zoomOutBtn.addEventListener('click', zoomOut);
    
    frontFlapWidth.addEventListener('input', updateFlaps);
    backFlapWidth.addEventListener('input', updateFlaps);
    if (foldXMm) {
        foldXMm.addEventListener('input', updateFlaps);
    }

    el.workspaceContainer.addEventListener('wheel', (e) => {
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            if (e.deltaY < 0) zoomIn();
            else zoomOut();
        }
    });

    // Register cross-module functions
    window.CoverEditor.actions.updateDimensions = updateDimensions;
    window.CoverEditor.actions.fitToScreen = fitToScreen;
});
