// cover-layers.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;

    const toggleTextsBtn = document.getElementById('toggle-texts-section');
    const textsIcon = document.getElementById('texts-section-icon');
    const addTextLayerBtn = document.getElementById('add-text-layer-btn');
    const addImageLayerBtn = document.getElementById('add-image-layer-btn');
    const addShapeLayerBtn = document.getElementById('add-shape-layer-btn');
    const deleteTextBtn = document.getElementById('delete-text-btn');

    const textOnlyProps = document.querySelectorAll('.text-only-prop');
    const shapeOnlyProps = document.querySelectorAll('.shape-only-prop');

    // Shape properties inputs
    const propShapeType = document.getElementById('prop-shape-type');
    const propShapeOpacity = document.getElementById('prop-shape-opacity');
    const propShapeOpacityVal = document.getElementById('prop-shape-opacity-val');
    const propShapeIsGradient = document.getElementById('prop-shape-is-gradient');
    const propShapeColor1 = document.getElementById('prop-shape-color1');
    const propShapeColor1Opacity = document.getElementById('prop-shape-color1-opacity');
    const propShapeColor2 = document.getElementById('prop-shape-color2');
    const propShapeColor2Opacity = document.getElementById('prop-shape-color2-opacity');
    const propShapeColor2Container = document.getElementById('prop-shape-color2-container');
    const propShapeAngle = document.getElementById('prop-shape-angle');
    const propShapeAngleContainer = document.getElementById('prop-shape-angle-container');

    // Text properties inputs
    const propTextContent = document.getElementById('prop-text-content');
    const propFontFamily = document.getElementById('prop-font-family');
    const propFontSize = document.getElementById('prop-font-size');
    const propRotation = document.getElementById('prop-rotation');
    const propWidth = document.getElementById('prop-width');
    const propHeight = document.getElementById('prop-height');
    const propTextColor = document.getElementById('prop-text-color');
    const propTextColorHex = document.getElementById('prop-text-color-hex');
    const propAlignBtns = document.querySelectorAll('.prop-align-btn');
    const propHyphens = document.getElementById('prop-hyphens');

    // Populate Fonts
    if (typeof coverData !== 'undefined' && coverData.installedFonts && coverData.installedFonts.length > 0) {
        propFontFamily.innerHTML = '';
        coverData.installedFonts.forEach(font => {
            const option = document.createElement('option');
            option.value = font.family;
            option.textContent = font.family;
            propFontFamily.appendChild(option);
        });
    }

    function generateId() {
        return Math.random().toString(36).substr(2, 9);
    }

    function hexToRgba(hex, opacity) {
        let r = 0, g = 0, b = 0;
        if (hex.length === 4) {
            r = parseInt(hex[1] + hex[1], 16);
            g = parseInt(hex[2] + hex[2], 16);
            b = parseInt(hex[3] + hex[3], 16);
        } else if (hex.length === 7) {
            r = parseInt(hex.substring(1, 3), 16);
            g = parseInt(hex.substring(3, 5), 16);
            b = parseInt(hex.substring(5, 7), 16);
        }
        return `rgba(${r}, ${g}, ${b}, ${opacity / 100})`;
    }
    window.CoverEditor.actions.selectLayer = function(id) {
        s.activeLayerId = id;
        if (window.CoverEditor.actions.renderTextLayers) {
            window.CoverEditor.actions.renderTextLayers();
        }

        if (id) {
            const layer = s.textLayers.find(l => l.id === id);
            el.textPropertiesPanel.classList.remove('hidden');
            el.textPropertiesPanel.classList.add('flex');
            
            el.textsContent.classList.remove('hidden');
            el.textsContent.classList.add('flex');
            textsIcon.classList.add('-rotate-90');
            
            if (layer.type === 'image') {
                textOnlyProps.forEach(el => el.classList.add('hidden'));
                shapeOnlyProps.forEach(el => el.classList.add('hidden'));
            } else if (layer.type === 'shape') {
                textOnlyProps.forEach(el => el.classList.add('hidden'));
                shapeOnlyProps.forEach(el => el.classList.remove('hidden'));
                
                propShapeType.value = layer.shapeType || 'rectangle';
                propShapeOpacity.value = layer.opacity !== undefined ? layer.opacity : 100;
                propShapeOpacityVal.textContent = propShapeOpacity.value + '%';
                propShapeIsGradient.checked = !!layer.isGradient;
                propShapeColor1.value = layer.color1 || '#000000';
                propShapeColor1Opacity.value = layer.color1Opacity !== undefined ? layer.color1Opacity : 100;
                propShapeColor2.value = layer.color2 || '#ffffff';
                propShapeColor2Opacity.value = layer.color2Opacity !== undefined ? layer.color2Opacity : 100;
                propShapeAngle.value = layer.gradientAngle || 90;
                
                if (layer.isGradient) {
                    propShapeColor2Container.style.display = 'flex';
                    propShapeAngleContainer.style.display = 'block';
                } else {
                    propShapeColor2Container.style.display = 'none';
                    propShapeAngleContainer.style.display = 'none';
                }
            } else {
                textOnlyProps.forEach(el => el.classList.remove('hidden'));
                shapeOnlyProps.forEach(el => el.classList.add('hidden'));
                propTextContent.value = layer.text || '';
                propFontFamily.value = layer.fontFamily || (coverData.installedFonts && coverData.installedFonts[0] ? coverData.installedFonts[0].family : 'Inter');
                propFontSize.value = layer.fontSize;
                propTextColor.value = layer.color;
                propTextColorHex.value = layer.color;
                propHyphens.checked = !!layer.hyphens;
                
                propAlignBtns.forEach(btn => {
                    if (btn.dataset.align === layer.textAlign) {
                        btn.classList.add('bg-white', 'shadow-sm', 'text-indigo-600');
                        btn.classList.remove('text-gray-600');
                    } else {
                        btn.classList.remove('bg-white', 'shadow-sm', 'text-indigo-600');
                        btn.classList.add('text-gray-600');
                    }
                });
            }
            
            propRotation.value = layer.rotation || 0;
            propWidth.value = layer.width || '';
            propHeight.value = layer.height || '';
        } else {
            el.textPropertiesPanel.classList.remove('flex');
            el.textPropertiesPanel.classList.add('hidden');
        }

        if (window.CoverEditor.actions.renderLayersPanel) {
            window.CoverEditor.actions.renderLayersPanel();
        }
    };

    // Drag Logic
    document.addEventListener('mousemove', (e) => {
        if (!s.isDragging || !s.activeLayerId) return;

        const dx = e.clientX - s.dragStartX;
        const dy = e.clientY - s.dragStartY;
        
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) {
            const rect = el.coverSpread.getBoundingClientRect();
            const spreadWidthPx = rect.width / s.zoomLevel;
            const spreadHeightPx = rect.height / s.zoomLevel;

            layer.x = s.layerStartX + (dx / s.zoomLevel / spreadWidthPx) * 100;
            layer.y = s.layerStartY + (dy / s.zoomLevel / spreadHeightPx) * 100;
            window.CoverEditor.actions.renderTextLayers();
        }
    });

    document.addEventListener('mouseup', () => {
        s.isDragging = false;
    });

    el.workspaceContainer.addEventListener('mousedown', (e) => {
        if (e.target === el.workspaceContainer || e.target === el.coverScaler || e.target === el.coverSpread || e.target.classList.contains('cover-part')) {
            window.CoverEditor.actions.selectLayer(null);
        }
    });

    // Buttons
    toggleTextsBtn.addEventListener('click', () => {
        el.textsContent.classList.toggle('hidden');
        el.textsContent.classList.toggle('flex');
        textsIcon.classList.toggle('-rotate-90');
    });

    addTextLayerBtn.addEventListener('click', () => {
        const newLayer = {
            id: generateId(),
            type: 'text',
            text: 'Nuevo Texto',
            x: 50,
            y: 50,
            fontFamily: coverData.installedFonts && coverData.installedFonts[0] ? coverData.installedFonts[0].family : 'Inter',
            fontSize: 48,
            rotation: 0,
            width: null,
            height: null,
            color: '#000000',
            textAlign: 'center',
            hyphens: false,
            zIndex: 30 + s.textLayers.length
        };
        s.textLayers.push(newLayer);
        window.CoverEditor.actions.renderTextLayers();
        window.CoverEditor.actions.renderLayersPanel();
        window.CoverEditor.actions.selectLayer(newLayer.id);
    });

    if (addImageLayerBtn) {
        addImageLayerBtn.addEventListener('click', () => {
            if (window.CoverEditor.actions.openMediaUploader) {
                window.CoverEditor.actions.openMediaUploader('Seleccionar Imagen para Capa', (url) => {
                    const newLayer = {
                        id: generateId(),
                        type: 'image',
                        url: url,
                        x: 50,
                        y: 50,
                        rotation: 0,
                        width: 200,
                        height: 200,
                        zIndex: 30 + s.textLayers.length
                    };
                    s.textLayers.push(newLayer);
                    window.CoverEditor.actions.renderTextLayers();
                    window.CoverEditor.actions.renderLayersPanel();
                    window.CoverEditor.actions.selectLayer(newLayer.id);
                });
            }
        });
    }

    if (addShapeLayerBtn) {
        addShapeLayerBtn.addEventListener('click', () => {
            const newLayer = {
                id: generateId(),
                type: 'shape',
                shapeType: 'rectangle',
                color1: '#cccccc',
                color1Opacity: 100,
                color2: '#999999',
                color2Opacity: 100,
                isGradient: false,
                gradientAngle: 90,
                opacity: 100,
                x: 50,
                y: 50,
                rotation: 0,
                width: 150,
                height: 150,
                zIndex: 30 + s.textLayers.length
            };
            s.textLayers.push(newLayer);
            window.CoverEditor.actions.renderTextLayers();
            window.CoverEditor.actions.renderLayersPanel();
            window.CoverEditor.actions.selectLayer(newLayer.id);
        });
    }

    deleteTextBtn.addEventListener('click', () => {
        if (!s.activeLayerId) return;
        s.textLayers = s.textLayers.filter(l => l.id !== s.activeLayerId);
        window.CoverEditor.actions.selectLayer(null);
    });

    // Binding Inputs
    propTextContent.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.text = e.target.value; window.CoverEditor.actions.renderTextLayers(); window.CoverEditor.actions.renderLayersPanel(); }
    });
    propFontFamily.addEventListener('change', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.fontFamily = e.target.value; window.CoverEditor.actions.renderTextLayers(); }
    });
    propFontSize.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.fontSize = parseInt(e.target.value) || 12; window.CoverEditor.actions.renderTextLayers(); }
    });
    propRotation.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.rotation = parseFloat(e.target.value) || 0; window.CoverEditor.actions.renderTextLayers(); }
    });
    propWidth.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.width = parseInt(e.target.value) || null; window.CoverEditor.actions.renderTextLayers(); }
    });
    propHeight.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.height = parseInt(e.target.value) || null; window.CoverEditor.actions.renderTextLayers(); }
    });
    propTextColor.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { 
            layer.color = e.target.value; 
            propTextColorHex.value = e.target.value;
            window.CoverEditor.actions.renderTextLayers(); 
        }
    });
    propTextColorHex.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { 
            layer.color = e.target.value; 
            propTextColor.value = e.target.value;
            window.CoverEditor.actions.renderTextLayers(); 
        }
    });
    propAlignBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const layer = s.textLayers.find(l => l.id === s.activeLayerId);
            if (layer) {
                layer.textAlign = e.currentTarget.dataset.align;
                window.CoverEditor.actions.renderTextLayers();
                window.CoverEditor.actions.selectLayer(s.activeLayerId);
            }
        });
    });

    propHyphens.addEventListener('change', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.hyphens = e.target.checked; window.CoverEditor.actions.renderTextLayers(); }
    });

    // Shape Binding Inputs
    if (propShapeType) {
        propShapeType.addEventListener('change', (e) => {
            const layer = s.textLayers.find(l => l.id === s.activeLayerId);
            if (layer && layer.type === 'shape') { layer.shapeType = e.target.value; window.CoverEditor.actions.renderTextLayers(); window.CoverEditor.actions.renderLayersPanel(); }
        });
    }
    if (propShapeOpacity) {
        propShapeOpacity.addEventListener('input', (e) => {
            const layer = s.textLayers.find(l => l.id === s.activeLayerId);
            if (layer && layer.type === 'shape') { 
                layer.opacity = parseInt(e.target.value) || 0; 
                propShapeOpacityVal.textContent = layer.opacity + '%';
                window.CoverEditor.actions.renderTextLayers(); 
            }
        });
    }
    if (propShapeIsGradient) {
        propShapeIsGradient.addEventListener('change', (e) => {
            const layer = s.textLayers.find(l => l.id === s.activeLayerId);
            if (layer && layer.type === 'shape') { 
                layer.isGradient = e.target.checked; 
                window.CoverEditor.actions.selectLayer(s.activeLayerId); // Re-run selectLayer to show/hide gradient controls
            }
        });
    }
    if (propShapeColor1) {
        propShapeColor1.addEventListener('input', (e) => {
            const layer = s.textLayers.find(l => l.id === s.activeLayerId);
            if (layer && layer.type === 'shape') { layer.color1 = e.target.value; window.CoverEditor.actions.renderTextLayers(); }
        });
    }
    if (propShapeColor1Opacity) {
        propShapeColor1Opacity.addEventListener('input', (e) => {
            const layer = s.textLayers.find(l => l.id === s.activeLayerId);
            if (layer && layer.type === 'shape') { layer.color1Opacity = parseInt(e.target.value) || 0; window.CoverEditor.actions.renderTextLayers(); }
        });
    }
    if (propShapeColor2) {
        propShapeColor2.addEventListener('input', (e) => {
            const layer = s.textLayers.find(l => l.id === s.activeLayerId);
            if (layer && layer.type === 'shape') { layer.color2 = e.target.value; window.CoverEditor.actions.renderTextLayers(); }
        });
    }
    if (propShapeColor2Opacity) {
        propShapeColor2Opacity.addEventListener('input', (e) => {
            const layer = s.textLayers.find(l => l.id === s.activeLayerId);
            if (layer && layer.type === 'shape') { layer.color2Opacity = parseInt(e.target.value) || 0; window.CoverEditor.actions.renderTextLayers(); }
        });
    }
    if (propShapeAngle) {
        propShapeAngle.addEventListener('input', (e) => {
            const layer = s.textLayers.find(l => l.id === s.activeLayerId);
            if (layer && layer.type === 'shape') { layer.gradientAngle = parseInt(e.target.value) || 0; window.CoverEditor.actions.renderTextLayers(); }
        });
    }

    // Exports
    window.CoverEditor.actions.renderTextLayers = renderTextLayers;
    window.CoverEditor.actions.renderLayersPanel = renderLayersPanel;
});
