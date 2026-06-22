// cover-layers-panel.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;

    s.selectedLayerIds = s.selectedLayerIds || [];

    window.CoverEditor.actions.renderLayersPanel = function() {
        el.layersList.innerHTML = '';
        if (s.textLayers.length === 0) {
            el.layersList.innerHTML = '<div class="text-xs text-gray-400 text-center py-4">No hay capas</div>';
            return;
        }

        // Obtener elementos raíz (capas sin parentId y grupos) ordenados por zIndex descendente
        const rootItems = s.textLayers.filter(l => !l.parentId).sort((a, b) => (b.zIndex || 0) - (a.zIndex || 0));

        rootItems.forEach(item => {
            renderItemRow(item, false);
            
            // Si es un grupo y no está colapsado, renderizar sus hijos indentados
            if (item.type === 'group' && !item.collapsed) {
                const children = s.textLayers.filter(l => l.parentId === item.id).sort((a, b) => (b.zIndex || 0) - (a.zIndex || 0));
                children.forEach(child => {
                    renderItemRow(child, true);
                });
            }
        });
    };

    function renderItemRow(layer, isChild = false) {
        const btn = document.createElement('div');
        btn.className = `w-full text-left px-3 py-2 text-xs rounded border transition flex items-center gap-2 cursor-move ${
            s.activeLayerId === layer.id 
                ? 'bg-indigo-50 border-indigo-200 text-indigo-800 font-semibold shadow-sm shadow-indigo-50' 
                : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-100'
        } ${isChild ? 'ml-6 w-[calc(100%-1.5rem)]' : ''}`;
        
        btn.draggable = true;
        btn.dataset.layerId = layer.id;

        // Checkbox de selección (no mostrar para grupos, solo para elementos agrupables)
        let checkboxHtml = '';
        if (layer.type !== 'group') {
            const isChecked = s.selectedLayerIds.includes(layer.id);
            checkboxHtml = `<input type="checkbox" class="layer-select-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mr-1" data-id="${layer.id}" ${isChecked ? 'checked' : ''} onclick="event.stopPropagation();" />`;
        }

        // Icono y colapsar para grupos
        let iconHtml = '';
        if (layer.type === 'group') {
            const folderIcon = layer.isBookLogo ? 'fa-solid fa-award text-yellow-500' : 'fa-solid fa-folder text-indigo-500';
            iconHtml = `
                <button class="toggle-collapse-btn p-0.5 hover:bg-gray-200 rounded text-gray-500 mr-0.5" onclick="event.stopPropagation(); window.CoverEditor.actions.toggleGroupCollapse('${layer.id}');">
                    <i class="fa-solid ${layer.collapsed ? 'fa-chevron-right' : 'fa-chevron-down'} text-[9px]"></i>
                </button>
                <i class="${folderIcon}"></i>
            `;
        } else {
            const layerIcon = layer.type === 'image' ? 'regular fa-image' : (layer.type === 'shape' ? (layer.shapeType === 'circle' ? 'solid fa-circle' : 'solid fa-square') : 'solid fa-t');
            iconHtml = `<i class="fa-${layerIcon} text-gray-400"></i>`;
        }

        // Nombre de capa
        let nameHtml = '';
        if (layer.type === 'group') {
            nameHtml = `<span class="truncate flex-1 font-bold">${layer.name || 'Grupo'}</span>`;
            if (layer.isBookLogo) {
                nameHtml += `<span class="text-[8px] bg-yellow-100 text-yellow-800 font-bold px-1 py-0.5 rounded ml-1 tracking-wider">LOGO</span>`;
            }
        } else {
            nameHtml = `<span class="truncate flex-1 pointer-events-none">${layer.type === 'image' ? 'Imagen' : (layer.type === 'shape' ? 'Forma' : (layer.text || 'Texto vacío'))}</span>`;
        }

        btn.innerHTML = `
            ${checkboxHtml}
            ${iconHtml}
            ${nameHtml}
            <i class="fa-solid fa-grip-vertical text-gray-300 ml-auto pointer-events-none"></i>
        `;

        // Eventos
        btn.addEventListener('click', () => {
            if (window.CoverEditor.actions.selectLayer) {
                window.CoverEditor.actions.selectLayer(layer.id);
            }
        });

        // Evento de cambio en checkbox
        const cb = btn.querySelector('.layer-select-checkbox');
        if (cb) {
            cb.addEventListener('change', (e) => {
                const id = layer.id;
                if (e.target.checked) {
                    if (!s.selectedLayerIds.includes(id)) s.selectedLayerIds.push(id);
                } else {
                    s.selectedLayerIds = s.selectedLayerIds.filter(x => x !== id);
                }
            });
        }

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

            s.textLayers.sort((a, b) => (a.zIndex || 0) - (b.zIndex || 0));
            
            const draggedIndex = s.textLayers.findIndex(l => l.id === draggedId);
            const targetIndex = s.textLayers.findIndex(l => l.id === layer.id);
            
            if (draggedIndex > -1 && targetIndex > -1) {
                const [draggedLayer] = s.textLayers.splice(draggedIndex, 1);
                
                // Si arrastramos sobre un grupo, meterlo dentro
                if (layer.type === 'group') {
                    draggedLayer.parentId = layer.id;
                } else if (isChild) {
                    draggedLayer.parentId = layer.parentId;
                } else {
                    draggedLayer.parentId = null;
                }

                s.textLayers.splice(targetIndex, 0, draggedLayer);
                
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
    }

    // Toggle collapse/expand de grupo
    window.CoverEditor.actions.toggleGroupCollapse = function(groupId) {
        const group = s.textLayers.find(l => l.id === groupId);
        if (group && group.type === 'group') {
            group.collapsed = !group.collapsed;
            window.CoverEditor.actions.renderLayersPanel();
        }
    };
});
