// cover-layers-canvas.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;

    window.CoverEditor.actions.renderTextLayers = function() {
        const existingLayers = el.coverSpread.querySelectorAll('.text-layer');
        existingLayers.forEach(layer => layer.remove());

        s.textLayers.forEach(layer => {
            if (layer.type === 'group') return; // Saltar renderizado visual del grupo
            
            const div = document.createElement('div');
            div.className = 'text-layer absolute cursor-move';
            div.dataset.id = layer.id;
            
            // Delinear si la capa misma está seleccionada, o si pertenece al grupo seleccionado
            if (s.activeLayerId === layer.id || (layer.parentId && s.activeLayerId === layer.parentId)) {
                div.classList.add('outline', 'outline-2', 'outline-indigo-500', 'bg-indigo-50', 'bg-opacity-10');
            } else {
                div.classList.add('outline', 'outline-1', 'outline-transparent', 'hover:outline-dashed', 'hover:outline-gray-400');
            }

            div.style.left = `${layer.x}%`;
            div.style.top = `${layer.y}%`;
            div.style.transform = `rotate(${layer.rotation || 0}deg)`;
            div.style.zIndex = layer.zIndex || 30;
            
            if (layer.type === 'image') {
                div.style.overflow = 'hidden';
                if (!layer.width) div.style.width = '200px';
                if (!layer.height) div.style.height = '200px';

                const img = document.createElement('img');
                img.src = layer.previewUrl || layer.url;
                img.alt = '';
                img.setAttribute('aria-hidden', 'true');
                img.className = 'cover-media-image cover-media-image--contain';
                img.loading = 'eager';
                img.decoding = 'async';
                img.style.objectFit = 'contain';
                div.appendChild(img);
            } else if (layer.type === 'shape') {
                div.style.opacity = (layer.opacity !== undefined ? layer.opacity : 100) / 100;
                if (layer.shapeType === 'circle') div.style.borderRadius = '50%';
                else div.style.borderRadius = '0';
                
                const c1 = window.CoverEditor.utils.hexToRgba(layer.color1 || '#000000', layer.color1Opacity !== undefined ? layer.color1Opacity : 100);
                const c2 = window.CoverEditor.utils.hexToRgba(layer.color2 || '#ffffff', layer.color2Opacity !== undefined ? layer.color2Opacity : 100);

                if (layer.isGradient) {
                    div.style.background = `linear-gradient(${layer.gradientAngle || 90}deg, ${c1}, ${c2})`;
                } else {
                    div.style.background = c1;
                }
                if (!layer.width) div.style.width = '150px';
                if (!layer.height) div.style.height = '150px';
            } else {
                div.style.fontFamily = `'${layer.fontFamily}', serif`;
                div.style.fontSize = `${layer.fontSize}px`;
                div.style.fontWeight = layer.fontWeight || 400;
                div.style.fontStyle = layer.fontStyle || 'normal';
                div.style.lineHeight = layer.lineHeight || 1.2;
                div.style.letterSpacing = `${layer.letterSpacing || 0}px`;
                div.style.color = layer.color;
                div.style.textAlign = layer.textAlign;
                div.style.whiteSpace = 'pre-wrap';
                div.style.fontSynthesis = 'none';
                
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
                if (window.CoverEditor.actions.selectLayer) {
                    window.CoverEditor.actions.selectLayer(layer.id);
                }
                if (window.CoverEditor.utils.isLayerLocked && window.CoverEditor.utils.isLayerLocked(layer)) {
                    e.stopPropagation();
                    return;
                }
                s.isDragging = true;
                s.dragStartX = e.clientX;
                s.dragStartY = e.clientY;
                s.layerStartX = layer.x;
                s.layerStartY = layer.y;
                e.stopPropagation();
            });

            el.coverSpread.appendChild(div);
        });
    };
});
