// cover-layers.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;

    const toggleTextsBtn = document.getElementById('toggle-texts-section');
    const textsIcon = document.getElementById('texts-section-icon');
    const addTextLayerBtn = document.getElementById('add-text-layer-btn');
    const addImageLayerBtn = document.getElementById('add-image-layer-btn');
    const deleteTextBtn = document.getElementById('delete-text-btn');

    const textOnlyProps = document.querySelectorAll('.text-only-prop');

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

    function renderTextLayers() {
        const existingLayers = el.coverSpread.querySelectorAll('.text-layer');
        existingLayers.forEach(layer => layer.remove());

        s.textLayers.forEach(layer => {
            const div = document.createElement('div');
            div.className = 'text-layer absolute cursor-move';
            div.dataset.id = layer.id;
            
            if (s.activeLayerId === layer.id) {
                div.classList.add('border-2', 'border-indigo-500', 'bg-indigo-50', 'bg-opacity-10');
            } else {
                div.classList.add('border', 'border-transparent', 'hover:border-dashed', 'hover:border-gray-400');
            }

            div.style.left = `${layer.x}%`;
            div.style.top = `${layer.y}%`;
            div.style.transform = `rotate(${layer.rotation || 0}deg)`;
            div.style.zIndex = layer.zIndex || 30;
            
            if (layer.type === 'image') {
                div.style.backgroundImage = `url(${layer.url})`;
                div.style.backgroundSize = 'contain';
                div.style.backgroundRepeat = 'no-repeat';
                div.style.backgroundPosition = 'center';
                if (!layer.width) div.style.width = '200px';
                if (!layer.height) div.style.height = '200px';
            } else {
                div.style.fontFamily = layer.fontFamily;
                div.style.fontSize = `${layer.fontSize}px`;
                div.style.color = layer.color;
                div.style.textAlign = layer.textAlign;
                div.style.whiteSpace = 'pre-wrap';
                div.style.lineHeight = '1.2';
                
                if (layer.hyphens) {
                    div.style.hyphens = 'auto';
                    div.style.webkitHyphens = 'auto';
                    div.lang = 'es';
                } else {
                    div.style.hyphens = 'none';
                    div.style.webkitHyphens = 'none';
                }
                
                div.textContent = layer.text;
            }
            
            if (layer.width) div.style.width = `${layer.width}px`;
            else if (layer.type !== 'image') div.style.width = 'auto';

            if (layer.height) div.style.height = `${layer.height}px`;
            else if (layer.type !== 'image') div.style.height = 'auto';

            div.addEventListener('mousedown', (e) => {
                if (e.target !== div) return; 
                selectLayer(layer.id);
                s.isDragging = true;
                s.dragStartX = e.clientX;
                s.dragStartY = e.clientY;
                s.layerStartX = layer.x;
                s.layerStartY = layer.y;
                e.stopPropagation();
            });

            el.coverSpread.appendChild(div);
        });
    }

    function selectLayer(id) {
        s.activeLayerId = id;
        renderTextLayers();

        if (id) {
            const layer = s.textLayers.find(l => l.id === id);
            el.textPropertiesPanel.classList.remove('hidden');
            el.textPropertiesPanel.classList.add('flex');
            
            el.textsContent.classList.remove('hidden');
            el.textsContent.classList.add('flex');
            textsIcon.classList.add('-rotate-90');
            
            if (layer.type === 'image') {
                textOnlyProps.forEach(el => el.classList.add('hidden'));
            } else {
                textOnlyProps.forEach(el => el.classList.remove('hidden'));
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

        renderLayersPanel();
    }

    function renderLayersPanel() {
        el.layersList.innerHTML = '';
        if (s.textLayers.length === 0) {
            el.layersList.innerHTML = '<div class="text-xs text-gray-400 text-center py-4">No hay capas</div>';
            return;
        }

        const sortedLayers = [...s.textLayers].sort((a, b) => (b.zIndex || 0) - (a.zIndex || 0));

        sortedLayers.forEach(layer => {
            const btn = document.createElement('button');
            btn.className = `w-full text-left px-3 py-2 text-xs rounded border transition flex items-center gap-2 ${
                s.activeLayerId === layer.id 
                    ? 'bg-indigo-50 border-indigo-200 text-indigo-800 font-semibold' 
                    : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-100'
            }`;
            
            btn.innerHTML = `
                <i class="fa-${layer.type === 'image' ? 'regular fa-image' : 'solid fa-t'} text-gray-400"></i>
                <span class="truncate flex-1">${layer.type === 'image' ? 'Imagen' : (layer.text || 'Texto vacío')}</span>
            `;

            btn.addEventListener('click', () => {
                selectLayer(layer.id);
            });

            el.layersList.appendChild(btn);
        });
    }

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
            renderTextLayers();
        }
    });

    document.addEventListener('mouseup', () => {
        s.isDragging = false;
    });

    el.workspaceContainer.addEventListener('mousedown', (e) => {
        if (e.target === el.workspaceContainer || e.target === el.coverScaler || e.target === el.coverSpread || e.target.classList.contains('cover-part')) {
            selectLayer(null);
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
        renderTextLayers();
        renderLayersPanel();
        selectLayer(newLayer.id);
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
                    renderTextLayers();
                    renderLayersPanel();
                    selectLayer(newLayer.id);
                });
            }
        });
    }

    deleteTextBtn.addEventListener('click', () => {
        if (!s.activeLayerId) return;
        s.textLayers = s.textLayers.filter(l => l.id !== s.activeLayerId);
        selectLayer(null);
    });

    // Binding Inputs
    propTextContent.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.text = e.target.value; renderTextLayers(); renderLayersPanel(); }
    });
    propFontFamily.addEventListener('change', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.fontFamily = e.target.value; renderTextLayers(); }
    });
    propFontSize.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.fontSize = parseInt(e.target.value) || 12; renderTextLayers(); }
    });
    propRotation.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.rotation = parseFloat(e.target.value) || 0; renderTextLayers(); }
    });
    propWidth.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.width = parseInt(e.target.value) || null; renderTextLayers(); }
    });
    propHeight.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.height = parseInt(e.target.value) || null; renderTextLayers(); }
    });
    propTextColor.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { 
            layer.color = e.target.value; 
            propTextColorHex.value = e.target.value;
            renderTextLayers(); 
        }
    });
    propTextColorHex.addEventListener('input', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { 
            layer.color = e.target.value; 
            propTextColor.value = e.target.value;
            renderTextLayers(); 
        }
    });
    propAlignBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const layer = s.textLayers.find(l => l.id === s.activeLayerId);
            if (layer) {
                layer.textAlign = e.currentTarget.dataset.align;
                renderTextLayers();
                selectLayer(s.activeLayerId);
            }
        });
    });

    propHyphens.addEventListener('change', (e) => {
        const layer = s.textLayers.find(l => l.id === s.activeLayerId);
        if (layer) { layer.hyphens = e.target.checked; renderTextLayers(); }
    });

    // Exports
    window.CoverEditor.actions.renderTextLayers = renderTextLayers;
    window.CoverEditor.actions.renderLayersPanel = renderLayersPanel;
});
