// cover-layers-panel.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;

    window.CoverEditor.actions.renderLayersPanel = function() {
        el.layersList.innerHTML = '';
        if (s.textLayers.length === 0) {
            el.layersList.innerHTML = '<div class="text-xs text-gray-400 text-center py-4">No hay capas</div>';
            return;
        }

        const sortedLayers = [...s.textLayers].sort((a, b) => (b.zIndex || 0) - (a.zIndex || 0));

        sortedLayers.forEach(layer => {
            const btn = document.createElement('div');
            btn.className = `w-full text-left px-3 py-2 text-xs rounded border transition flex items-center gap-2 cursor-move ${
                s.activeLayerId === layer.id 
                    ? 'bg-indigo-50 border-indigo-200 text-indigo-800 font-semibold' 
                    : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-100'
            }`;
            btn.draggable = true;
            btn.dataset.layerId = layer.id;
            
            btn.innerHTML = `
                <i class="fa-${layer.type === 'image' ? 'regular fa-image' : (layer.type === 'shape' ? (layer.shapeType === 'circle' ? 'solid fa-circle' : 'solid fa-square') : 'solid fa-t')} text-gray-400"></i>
                <span class="truncate flex-1 pointer-events-none">${layer.type === 'image' ? 'Imagen' : (layer.type === 'shape' ? 'Forma' : (layer.text || 'Texto vacío'))}</span>
                <i class="fa-solid fa-grip-vertical text-gray-300 ml-auto pointer-events-none"></i>
            `;

            btn.addEventListener('click', () => {
                if (window.CoverEditor.actions.selectLayer) {
                    window.CoverEditor.actions.selectLayer(layer.id);
                }
            });

            // Drag and drop logic
            btn.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', layer.id);
                btn.classList.add('opacity-50');
            });

            btn.addEventListener('dragend', () => {
                btn.classList.remove('opacity-50');
                const placeholders = el.layersList.querySelectorAll('.border-t-2');
                placeholders.forEach(p => p.classList.remove('border-t-2', 'border-indigo-500'));
            });

            btn.addEventListener('dragover', (e) => {
                e.preventDefault();
                btn.classList.add('border-t-2', 'border-indigo-500');
            });

            btn.addEventListener('dragleave', () => {
                btn.classList.remove('border-t-2', 'border-indigo-500');
            });

            btn.addEventListener('drop', (e) => {
                e.preventDefault();
                btn.classList.remove('border-t-2', 'border-indigo-500');
                const draggedId = e.dataTransfer.getData('text/plain');
                if (!draggedId || draggedId === layer.id) return;

                // Sort textLayers by zIndex ascending so we have a reliable order
                s.textLayers.sort((a, b) => (a.zIndex || 0) - (b.zIndex || 0));
                
                const draggedIndex = s.textLayers.findIndex(l => l.id === draggedId);
                const targetIndex = s.textLayers.findIndex(l => l.id === layer.id);
                
                if (draggedIndex > -1 && targetIndex > -1) {
                    const [draggedLayer] = s.textLayers.splice(draggedIndex, 1);
                    // Insert at target index
                    s.textLayers.splice(targetIndex, 0, draggedLayer);
                    
                    // Reassign z-indexes sequentially
                    s.textLayers.forEach((l, i) => {
                        l.zIndex = 30 + i;
                    });
                    
                    if (window.CoverEditor.actions.renderTextLayers) {
                        window.CoverEditor.actions.renderTextLayers();
                    }
                    window.CoverEditor.actions.renderLayersPanel();
                }
            });

            el.layersList.appendChild(btn);
        });
    };
});
