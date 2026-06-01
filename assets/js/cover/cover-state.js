// cover-state.js
window.CoverEditor = window.CoverEditor || {};

document.addEventListener('DOMContentLoaded', () => {
    const DPI = 96;
    const CM_TO_INCH = 2.54;
    const pxPerCm = DPI / CM_TO_INCH; // ~37.795px per cm

    // Base Dimensions
    let pageWidthCm = typeof coverData !== 'undefined' ? coverData.pageWidthCm : 14;
    let pageHeightCm = typeof coverData !== 'undefined' ? coverData.pageHeightCm : 21;
    
    if (!pageWidthCm || isNaN(pageWidthCm) || pageWidthCm <= 0) pageWidthCm = 14;
    if (!pageHeightCm || isNaN(pageHeightCm) || pageHeightCm <= 0) pageHeightCm = 21;
    
    // Shared State Object
    window.CoverEditor.state = {
        DPI,
        CM_TO_INCH,
        pxPerCm,
        pageWidthCm,
        pageHeightCm,
        pageWidthPx: pageWidthCm * pxPerCm,
        pageHeightPx: pageHeightCm * pxPerCm,
        
        zoomLevel: 1.0,
        textLayers: [],
        activeLayerId: null,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,
        layerStartX: 0,
        layerStartY: 0
    };

    // Shared DOM Elements
    window.CoverEditor.elements = {
        paperTypeSelect: document.getElementById('paper-type'),
        pageCountInput: document.getElementById('page-count'),
        zoomInBtn: document.getElementById('zoom-in'),
        zoomOutBtn: document.getElementById('zoom-out'),
        zoomLevelText: document.getElementById('zoom-level'),
        
        coverScaler: document.getElementById('cover-scaler'),
        coverSpread: document.getElementById('cover-spread'),
        backCover: document.getElementById('back-cover'),
        spine: document.getElementById('spine'),
        frontCover: document.getElementById('front-cover'),
        frontFlap: document.getElementById('front-flap'),
        backFlap: document.getElementById('back-flap'),
        bleedGuide: document.getElementById('bleed-guide'),
        workspaceContainer: document.getElementById('workspace-container'),

        // Panels
        imagesContent: document.getElementById('images-section-content'),
        flapsContent: document.getElementById('flaps-section-content'),
        textsContent: document.getElementById('texts-section-content'),
        textPropertiesPanel: document.getElementById('text-properties-panel'),
        layersList: document.getElementById('layers-list')
    };

    // Callback registry to allow cross-module function calls
    window.CoverEditor.actions = {
        updateDimensions: null,
        fitToScreen: null,
        renderTextLayers: null,
        renderLayersPanel: null,
        applyImageToCover: null,
        applySpreadImage: null
    };
});
