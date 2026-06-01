// cover-dimensions.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;

    // References to UI that affects dimensions
    const frontFlapWidth = document.getElementById('front-flap-width');
    const backFlapWidth = document.getElementById('back-flap-width');

    function updateDimensions() {
        const thicknessMmPerPage = parseFloat(el.paperTypeSelect.value) || 0.06;
        let pages = parseInt(el.pageCountInput.value, 10);
        
        if (isNaN(pages) || pages < 20) pages = 20;
        
        // Spine width calculation
        const spineWidthMm = thicknessMmPerPage * pages;
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
        const frontFlapMm = parseFloat(frontFlapWidth.value) || 0;
        const backFlapMm = parseFloat(backFlapWidth.value) || 0;
        
        let frontFlapPx = (frontFlapMm / 10) * s.pxPerCm;
        let backFlapPx = (backFlapMm / 10) * s.pxPerCm;
        
        let frontCoverPx = s.pageWidthPx;
        let backCoverPx = s.pageWidthPx;
        
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

        const estimatedSpinePx = (0.06 * 150 / 10) * s.pxPerCm;
        const frontFlapPx = ((parseFloat(frontFlapWidth.value) || 0) / 10) * s.pxPerCm;
        const backFlapPx = ((parseFloat(backFlapWidth.value) || 0) / 10) * s.pxPerCm;
        
        const totalWidthPx = (s.pageWidthPx * 2) + estimatedSpinePx + frontFlapPx + backFlapPx + (2 * bleedPx);
        const totalHeightPx = actualHeightPx;

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
    el.zoomInBtn.addEventListener('click', zoomIn);
    el.zoomOutBtn.addEventListener('click', zoomOut);
    
    frontFlapWidth.addEventListener('input', updateFlaps);
    backFlapWidth.addEventListener('input', updateFlaps);

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
