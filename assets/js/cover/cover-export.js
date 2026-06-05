// cover-export.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;
    
    const exportPdfBtn = document.getElementById('export-pdf-btn');
    if (!exportPdfBtn) return;

    // References to UI that affects dimensions (same as cover-dimensions.js)
    const frontFlapWidth = document.getElementById('front-flap-width');
    const backFlapWidth = document.getElementById('back-flap-width');

    function triggerPrint() {
        const thicknessMmPerPage = parseFloat(el.paperTypeSelect.value) || 0.06;
        let pages = parseInt(el.pageCountInput.value, 10);
        if (isNaN(pages) || pages < 20) pages = 20;
        
        // Spine width
        const spineWidthMm = thicknessMmPerPage * pages;

        // Bleed (5mm)
        const BLEED_MM = 5;

        // Cover width
        const coverWidthMm = s.pageWidthCm * 10;
        const heightMm = s.pageHeightCm * 10;

        // Flaps calculation
        const frontFlapMm = parseFloat(frontFlapWidth.value) || 0;
        const backFlapMm = parseFloat(backFlapWidth.value) || 0;

        // Total dimensions
        const totalWidthMm = frontFlapMm + coverWidthMm + spineWidthMm + coverWidthMm + backFlapMm + (BLEED_MM * 2);
        const totalHeightMm = heightMm + (BLEED_MM * 2);

        // We need to ensure the transform scale is temporarily removed for printing
        // but we can do that safely with CSS media print properties.
        let styleEl = document.getElementById('print-export-style');
        if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = 'print-export-style';
            document.head.appendChild(styleEl);
        }

        styleEl.innerHTML = `
            @media print {
                nav, aside {
                    display: none !important;
                }
                html, body {
                    height: auto !important;
                    overflow: visible !important;
                    background: white !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                #workspace-container {
                    display: block !important;
                    height: auto !important;
                    overflow: visible !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    background: white !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    position: static !important;
                    transform: none !important;
                    justify-content: flex-start !important;
                    align-items: flex-start !important;
                }
                #cover-scaler {
                    padding: 0 !important;
                    transform: none !important;
                    margin: 0 !important;
                }
                #cover-spread {
                    box-shadow: none !important;
                    margin: 0 !important;
                    border: none !important;
                    /* Ensure exact mm physical dimensions on paper */
                    width: ${totalWidthMm}mm !important;
                    height: ${totalHeightMm}mm !important;
                }
                
                /* The bleed guides shouldn't print */
                #bleed-guide {
                    display: none !important;
                }
                
                /* Remove visual dashed borders from cover parts */
                .cover-part {
                    border: none !important;
                    outline: none !important;
                }
                
                /* Hide any inner dashed lines (spine/flap folds) */
                .cover-part > div.border-dashed {
                    display: none !important;
                }
                
                /* Ensure active layer highlighting doesn't print */
                .text-layer {
                    border: none !important;
                    outline: none !important;
                    box-shadow: none !important;
                }
                .text-layer.bg-indigo-50 {
                    background-color: transparent !important;
                }

                @page {
                    size: ${totalWidthMm}mm ${totalHeightMm}mm;
                    margin: 0px;
                }
            }
        `;

        // Small delay to allow the browser to apply the styles before opening print dialog
        setTimeout(() => {
            // Deselect any active layer so borders don't show
            const sActive = window.CoverEditor.state.activeLayerId;
            if (sActive) {
                // To avoid rewriting selectLayer here, we just fake a click on workspace
                const mousedownEvent = new MouseEvent('mousedown', {
                    bubbles: true,
                    cancelable: true,
                    view: window
                });
                el.workspaceContainer.dispatchEvent(mousedownEvent);
            }

            window.print();
        }, 300);
    }

    exportPdfBtn.addEventListener('click', triggerPrint);
});
