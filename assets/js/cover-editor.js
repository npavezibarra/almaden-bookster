document.addEventListener('DOMContentLoaded', () => {
    // 1. Initial Data
    const DPI = 96; // Standard CSS DPI
    const CM_TO_INCH = 2.54;
    const pxPerCm = DPI / CM_TO_INCH; // ~37.795px per cm

    // Elements
    const paperTypeSelect = document.getElementById('paper-type');
    const pageCountInput = document.getElementById('page-count');
    const zoomInBtn = document.getElementById('zoom-in');
    const zoomOutBtn = document.getElementById('zoom-out');
    const zoomLevelText = document.getElementById('zoom-level');
    
    const coverScaler = document.getElementById('cover-scaler');
    const backCover = document.getElementById('back-cover');
    const spine = document.getElementById('spine');
    const frontCover = document.getElementById('front-cover');

    // State
    let zoomLevel = 1.0;

    // Dimensions
    // coverData is injected by PHP in cover-app.php
    const pageWidthCm = coverData.pageWidthCm;
    const pageHeightCm = coverData.pageHeightCm;
    
    const pageWidthPx = pageWidthCm * pxPerCm;
    const pageHeightPx = pageHeightCm * pxPerCm;

    // 2. Functions
    function updateDimensions() {
        const thicknessMmPerPage = parseFloat(paperTypeSelect.value);
        let pages = parseInt(pageCountInput.value, 10);
        
        if (isNaN(pages) || pages < 20) pages = 20;
        
        // El grosor del lomo en milímetros
        const spineWidthMm = thicknessMmPerPage * pages;
        const spineWidthCm = spineWidthMm / 10;
        const spineWidthPx = spineWidthCm * pxPerCm;

        // Update DOM
        backCover.style.width = `${pageWidthPx}px`;
        backCover.style.height = `${pageHeightPx}px`;

        frontCover.style.width = `${pageWidthPx}px`;
        frontCover.style.height = `${pageHeightPx}px`;

        spine.style.width = `${spineWidthPx}px`;
        spine.style.height = `${pageHeightPx}px`;
    }

    function updateZoom() {
        coverScaler.style.transform = `scale(${zoomLevel})`;
        zoomLevelText.textContent = `${Math.round(zoomLevel * 100)}%`;
    }

    function zoomIn() {
        if (zoomLevel < 3.0) {
            zoomLevel += 0.1;
            // Corregir redondeo de punto flotante en JS
            zoomLevel = Math.round(zoomLevel * 10) / 10;
            updateZoom();
        }
    }

    function zoomOut() {
        if (zoomLevel > 0.2) {
            zoomLevel -= 0.1;
            zoomLevel = Math.round(zoomLevel * 10) / 10;
            updateZoom();
        }
    }

    // Calcular zoom inicial para que encaje en la pantalla
    function fitToScreen() {
        const containerWidth = document.getElementById('workspace-container').clientWidth;
        const containerHeight = document.getElementById('workspace-container').clientHeight;
        
        // Total spread width = (2 * pageWidth) + spineWidth. Estimado inicial.
        const estimatedSpinePx = (0.06 * 150 / 10) * pxPerCm;
        const totalWidthPx = (pageWidthPx * 2) + estimatedSpinePx;
        const totalHeightPx = pageHeightPx;

        const margin = 80; // Margen de seguridad (40px padding x 2)
        
        const scaleX = (containerWidth - margin) / totalWidthPx;
        const scaleY = (containerHeight - margin) / totalHeightPx;

        // Tomar el menor factor de escala para que no se salga de la pantalla
        let optimalScale = Math.min(scaleX, scaleY);
        
        // Redondear a un decimal
        optimalScale = Math.floor(optimalScale * 10) / 10;
        
        // Limitar entre 0.2 y 1.5
        if (optimalScale < 0.2) optimalScale = 0.2;
        if (optimalScale > 1.5) optimalScale = 1.0;

        zoomLevel = optimalScale;
        updateZoom();
    }

    // 3. Event Listeners
    paperTypeSelect.addEventListener('change', updateDimensions);
    pageCountInput.addEventListener('input', updateDimensions);
    zoomInBtn.addEventListener('click', zoomIn);
    zoomOutBtn.addEventListener('click', zoomOut);

    // Wheel zoom (opcional, como en el Content Editor)
    document.getElementById('workspace-container').addEventListener('wheel', (e) => {
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            if (e.deltaY < 0) {
                zoomIn();
            } else {
                zoomOut();
            }
        }
    });

    // 4. Initialize
    updateDimensions();
    fitToScreen();
});
