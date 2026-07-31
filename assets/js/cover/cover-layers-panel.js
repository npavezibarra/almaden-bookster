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
        const isLocked = window.CoverEditor.utils.isLayerLocked ? window.CoverEditor.utils.isLayerLocked(layer) : !!layer.locked;
        const btn = document.createElement('div');
        btn.className = `w-full text-left px-3 py-2 text-xs rounded border transition flex items-center gap-2 cursor-move ${
            s.activeLayerId === layer.id 
                ? 'bg-indigo-50 border-indigo-200 text-indigo-800 font-semibold shadow-sm shadow-indigo-50' 
                : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-100'
        } ${isChild ? 'ml-6 w-[calc(100%-1.5rem)]' : ''} ${isLocked ? 'opacity-75' : ''}`;
        
        btn.draggable = !isLocked;
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
        const layerName = getLayerDisplayName(layer);
        let nameHtml = '';
        if (layer.type === 'group') {
            nameHtml = `<span class="truncate flex-1 font-bold">${layerName}</span>`;
            if (layer.isBookLogo) {
                nameHtml += `<span class="text-[8px] bg-yellow-100 text-yellow-800 font-bold px-1 py-0.5 rounded ml-1 tracking-wider">LOGO</span>`;
            }
        } else {
            nameHtml = `<span class="truncate flex-1 pointer-events-none">${layerName}</span>`;
        }

        const lockIcon = isLocked ? 'fa-lock' : 'fa-unlock';
        const lockTitle = isLocked ? 'Desbloquear capa' : 'Bloquear capa';
        const deleteDisabled = isLocked ? 'opacity-40 cursor-not-allowed' : 'hover:bg-red-50 hover:text-red-600';

        btn.innerHTML = `
            ${checkboxHtml}
            ${iconHtml}
            ${nameHtml}
            <button type="button" class="layer-duplicate-btn w-6 h-6 rounded flex items-center justify-center text-gray-400 transition hover:bg-indigo-50 hover:text-indigo-600" title="Duplicar capa" aria-label="Duplicar capa" onclick="event.stopPropagation(); window.CoverEditor.actions.duplicateLayer('${layer.id}');">
                <i class="fa-solid fa-copy text-[10px]"></i>
            </button>
            <button type="button" class="layer-lock-btn w-6 h-6 rounded flex items-center justify-center text-gray-400 transition ${isLocked ? 'bg-gray-100 text-gray-600' : 'hover:bg-gray-100'}" title="${lockTitle}" aria-label="${lockTitle}" onclick="event.stopPropagation(); window.CoverEditor.actions.toggleLayerLock('${layer.id}');">
                <i class="fa-solid ${lockIcon} text-[10px]"></i>
            </button>
            <button type="button" class="layer-delete-btn w-6 h-6 rounded flex items-center justify-center text-gray-400 transition ${deleteDisabled}" title="Eliminar capa" aria-label="Eliminar capa" onclick="event.stopPropagation(); window.CoverEditor.actions.deleteLayer('${layer.id}');">
                <i class="fa-solid fa-trash text-[10px]"></i>
            </button>
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
            if (isLocked) {
                e.preventDefault();
                return;
            }
            e.dataTransfer.setData('text/plain', layer.id);
            btn.classList.add('opacity-50');
        });

        btn.addEventListener('dragend', () => {
            btn.classList.remove('opacity-50');
            const placeholders = el.layersList.querySelectorAll('.border-t-2');
            placeholders.forEach(p => p.classList.remove('border-t-2', 'border-indigo-500'));
        });

        btn.addEventListener('dragover', (e) => {
            if (isLocked) return;
            e.preventDefault();
            btn.classList.add('border-t-2', 'border-indigo-500');
        });

        btn.addEventListener('dragleave', () => {
            btn.classList.remove('border-t-2', 'border-indigo-500');
        });

        btn.addEventListener('drop', (e) => {
            if (isLocked) return;
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

    function getLayerDisplayName(layer) {
        if (layer.name) {
            return layer.name;
        }

        if (layer.type === 'group') return 'Grupo';
        if (layer.type === 'image') return 'Imagen';
        if (layer.type === 'shape') return 'Forma';
        return layer.text || 'Texto vacío';
    }

    // Toggle collapse/expand de grupo
    window.CoverEditor.actions.toggleGroupCollapse = function(groupId) {
        const group = s.textLayers.find(l => l.id === groupId);
        if (group && group.type === 'group') {
            group.collapsed = !group.collapsed;
            window.CoverEditor.actions.renderLayersPanel();
        }
    };

    window.CoverEditor.actions.toggleLayerLock = function(layerId) {
        const layer = s.textLayers.find(l => l.id === layerId);
        if (!layer) {
            return;
        }

        layer.locked = !layer.locked;
        if (s.activeLayerId === layerId && window.CoverEditor.actions.renderTextLayers) {
            window.CoverEditor.actions.renderTextLayers();
        }
        window.CoverEditor.actions.renderLayersPanel();
    };

    window.CoverEditor.actions.duplicateLayer = function(layerId) {
        const sourceLayer = s.textLayers.find(layer => layer.id === layerId);
        if (!sourceLayer) {
            return;
        }

        const maxZ = Math.max(...s.textLayers.map(layer => layer.zIndex || 30), 30);
        const cloneLayer = (layer, overrides = {}) => ({
            ...JSON.parse(JSON.stringify(layer)),
            ...overrides,
            id: window.CoverEditor.utils.generateId()
        });
        const sourceIndex = s.textLayers.findIndex(layer => layer.id === layerId);
        const duplicate = cloneLayer(sourceLayer, {
            name: `${getLayerDisplayName(sourceLayer)} (copy)`,
            zIndex: maxZ + 1
        });
        const layersToInsert = [duplicate];

        // A duplicated group includes copies of its direct children so it remains
        // a usable group rather than an empty container.
        if (sourceLayer.type === 'group') {
            s.textLayers
                .filter(layer => layer.parentId === sourceLayer.id)
                .forEach(child => {
                    layersToInsert.push(cloneLayer(child, {
                        parentId: duplicate.id,
                        zIndex: maxZ + 1.01
                    }));
                });
        }

        s.textLayers.splice(sourceIndex + 1, 0, ...layersToInsert);

        // Match the expected duplicate behavior: keep every visual property, but
        // offset the copy enough to make both objects immediately distinguishable.
        if (window.CoverEditor.utils.moveLayerByPixels) {
            window.CoverEditor.utils.moveLayerByPixels(duplicate, 24, 24, { useCurrentPosition: true });
        }
        s.selectedLayerIds = [duplicate.id];

        if (window.CoverEditor.actions.renderTextLayers) {
            window.CoverEditor.actions.renderTextLayers();
        }
        window.CoverEditor.actions.selectLayer(duplicate.id);
    };

    window.CoverEditor.actions.deleteLayer = function(layerId) {
        const layer = s.textLayers.find(l => l.id === layerId);
        if (!layer) {
            return;
        }

        if (window.CoverEditor.utils.isLayerLocked && window.CoverEditor.utils.isLayerLocked(layer)) {
            alert('Esta capa está bloqueada. Desbloquéala antes de eliminarla.');
            return;
        }

        if (layer.type === 'group') {
            s.textLayers.forEach(item => {
                if (item.parentId === layer.id) {
                    item.parentId = null;
                }
            });
        }

        s.textLayers = s.textLayers.filter(item => item.id !== layerId);
        s.selectedLayerIds = (s.selectedLayerIds || []).filter(id => id !== layerId);

        if (s.activeLayerId === layerId) {
            window.CoverEditor.actions.selectLayer(null);
        } else {
            if (window.CoverEditor.actions.renderTextLayers) {
                window.CoverEditor.actions.renderTextLayers();
            }
            window.CoverEditor.actions.renderLayersPanel();
        }
    };
});
