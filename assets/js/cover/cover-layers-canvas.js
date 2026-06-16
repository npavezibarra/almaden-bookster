// cover-layers-canvas.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;

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

    window.CoverEditor.actions.renderTextLayers = function() {
        const existingLayers = el.coverSpread.querySelectorAll('.text-layer');
        existingLayers.forEach(layer => layer.remove());

        s.textLayers.forEach(layer => {
            const div = document.createElement('div');
            div.className = 'text-layer absolute cursor-move';
            div.dataset.id = layer.id;
            
            if (s.activeLayerId === layer.id) {
                div.classList.add('outline', 'outline-2', 'outline-indigo-500', 'bg-indigo-50', 'bg-opacity-10');
            } else {
                div.classList.add('outline', 'outline-1', 'outline-transparent', 'hover:outline-dashed', 'hover:outline-gray-400');
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
            } else if (layer.type === 'shape') {
                div.style.opacity = (layer.opacity !== undefined ? layer.opacity : 100) / 100;
                if (layer.shapeType === 'circle') div.style.borderRadius = '50%';
                else div.style.borderRadius = '0';
                
                const c1 = hexToRgba(layer.color1 || '#000000', layer.color1Opacity !== undefined ? layer.color1Opacity : 100);
                const c2 = hexToRgba(layer.color2 || '#ffffff', layer.color2Opacity !== undefined ? layer.color2Opacity : 100);

                if (layer.isGradient) {
                    div.style.background = `linear-gradient(${layer.gradientAngle || 90}deg, ${c1}, ${c2})`;
                } else {
                    div.style.background = c1;
                }
                if (!layer.width) div.style.width = '150px';
                if (!layer.height) div.style.height = '150px';
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
                if (window.CoverEditor.actions.selectLayer) {
                    window.CoverEditor.actions.selectLayer(layer.id);
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
