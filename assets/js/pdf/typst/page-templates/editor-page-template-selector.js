// Page selection and preset assignment for the Typst/PDF.js preview.
(function () {
    let selectedPageNumber = null;
    let activeTab = 'template';
    let selectedTemplateId = '';
    let modalBound = false;

    function getSelectablePage(element) {
        const page = element && element.closest ? element.closest('[data-page-number]') : null;
        const pageNumber = Number.parseInt(page?.dataset.pageNumber || '', 10);
        return page && page.dataset.blank !== '1' && Number.isFinite(pageNumber) && pageNumber > 0
            ? page
            : null;
    }

    function getPageNumber(page) {
        return Number.parseInt(page?.dataset.pageNumber || '', 10);
    }

    function normalizeId(value) {
        return String(value || '').toLowerCase().replace(/[^a-z0-9_-]/g, '');
    }

    function getTemplates() {
        return window.almadenPageTemplateState?.getTemplates?.() || [];
    }

    function getTemplateRegistry() {
        return window.almadenPageTemplateRegistry || {};
    }

    function getTemplateDefinition(templateId) {
        return getTemplateRegistry()[normalizeId(templateId)] || null;
    }

    function getTemplateOptions() {
        return Object.values(getTemplateRegistry());
    }

    function getDefaultTemplateId() {
        const currentTemplate = getTemplateForSelectedPage();
        const currentId = normalizeId(currentTemplate?.template_id || '');
        if (currentId && getTemplateDefinition(currentId)) {
            return currentId;
        }
        const firstTemplate = getTemplateOptions()[0];
        return normalizeId(firstTemplate?.id || '');
    }

    function setSelectedTemplateId(templateId, shouldRender = true) {
        const nextId = normalizeId(templateId);
        if (!nextId || !getTemplateDefinition(nextId)) {
            return;
        }
        selectedTemplateId = nextId;
        if (shouldRender) {
            renderTemplateOptions();
            updateModalActions();
        }
    }

    function renderTemplateOptions() {
        window.almadenPageTemplateOptions?.render?.(selectedTemplateId);
    }

    function buildTemplateSlots(templateId, existingSlots = []) {
        const definition = getTemplateDefinition(templateId);
        const defaultSlots = Array.isArray(definition?.slots) ? definition.slots : [];
        const existingMap = new Map(
            (Array.isArray(existingSlots) ? existingSlots : []).map(slot => [normalizeId(slot?.id), slot])
        );

        const normalized = defaultSlots.map((slot, index) => {
            const slotId = normalizeId(slot?.id) || `slot-${index + 1}`;
            const existing = existingMap.get(slotId) || {};
            return {
                id: slotId,
                label: slot?.label || existing.label || slotId,
                kind: slot?.kind || existing.kind || 'image',
                attachment_id: Number(existing.attachment_id) || 0,
                url: existing.url || '',
                preview_url: existing.preview_url || existing.url || '',
                original_url: existing.original_url || existing.url || ''
            };
        });

        existingMap.forEach((slot, slotId) => {
            if (normalized.some(entry => normalizeId(entry.id) === slotId)) {
                return;
            }
            normalized.push({
                id: slotId,
                label: slot?.label || slotId,
                kind: slot?.kind || 'image',
                attachment_id: Number(slot?.attachment_id) || 0,
                url: slot?.url || '',
                preview_url: slot?.preview_url || slot?.url || '',
                original_url: slot?.original_url || slot?.url || ''
            });
        });

        return normalized;
    }

    function getTemplateForSelectedPage() {
        if (!Number.isFinite(selectedPageNumber) || selectedPageNumber < 1) return null;
        return window.almadenPageTemplateState?.getTemplateAtPage?.(selectedPageNumber) || null;
    }

    function getSelectedPageNumber() {
        return selectedPageNumber;
    }

    function cloneTemplates(templates) {
        return JSON.parse(JSON.stringify(Array.isArray(templates) ? templates : []));
    }

    function clonePageStyles(styles) {
        return JSON.parse(JSON.stringify(Array.isArray(styles) ? styles : []));
    }

    async function persistTemplates() {
        return typeof window.savePDFSettings === 'function'
            ? window.savePDFSettings(true, true)
            : true;
    }

    async function rollbackTemplates(previousTemplates) {
        window.bookState.settings.page_templates = previousTemplates;
        await persistTemplates();
        if (typeof window.compilePDFPreview === 'function') {
            await window.compilePDFPreview(true);
        }
    }

    function updateAction() {
        const button = document.getElementById('pdf-page-template-action');
        if (!button) return;

        const label = button.querySelector('span');
        const selected = Number.isFinite(selectedPageNumber) && selectedPageNumber > 0;
        button.classList.toggle('hidden', !selected);
        button.classList.toggle('inline-flex', selected);
        if (label) label.textContent = selected ? `Configurar página ${selectedPageNumber}` : 'Aplicar plantilla';
    }

    function updateModalActions() {
        const removeButton = document.getElementById('page-template-remove');
        const confirmButton = document.getElementById('page-template-confirm');
        const hasTemplate = !!getTemplateForSelectedPage();
        const hasStyle = !!window.almadenPageStyleState?.getStyleAtSelectedPage?.();
        const hasAnyConfiguration = hasTemplate || hasStyle;
        const templateFooter = document.querySelector('[data-page-template-footer="template"]');
        const styleFooter = document.querySelector('[data-page-template-footer="style"]');
        const templatePanel = document.querySelector('[data-page-template-panel="template"]');
        const stylePanel = document.querySelector('[data-page-template-panel="style"]');
        const templateTabButton = document.querySelector('[data-page-template-tab-button="template"]');
        const styleTabButton = document.querySelector('[data-page-template-tab-button="style"]');
        const resetButtons = document.querySelectorAll('[data-page-template-reset]');

        if (removeButton) {
            removeButton.classList.toggle('hidden', !hasTemplate);
            removeButton.setAttribute('aria-hidden', hasTemplate ? 'false' : 'true');
        }
        const imagesButton = document.getElementById('page-template-images');
        if (imagesButton) {
            imagesButton.classList.toggle('hidden', !hasTemplate);
            imagesButton.setAttribute('aria-hidden', hasTemplate ? 'false' : 'true');
        }
        if (confirmButton) {
            confirmButton.textContent = hasTemplate ? 'Reemplazar plantilla' : 'Aplicar plantilla';
        }
        resetButtons.forEach(button => {
            button.classList.toggle('hidden', !hasAnyConfiguration);
            button.setAttribute('aria-hidden', hasAnyConfiguration ? 'false' : 'true');
        });
        const templateOptions = document.querySelectorAll('[data-page-template-option]');
        templateOptions.forEach(option => {
            const optionTemplateId = normalizeId(option.dataset.pageTemplateOption || '');
            const selected = optionTemplateId && optionTemplateId === normalizeId(selectedTemplateId);
            option.setAttribute('aria-pressed', selected ? 'true' : 'false');
            option.classList.toggle('border-amber-500', selected);
            option.classList.toggle('bg-amber-50', selected);
            option.classList.toggle('border-slate-200', !selected);
            option.classList.toggle('bg-white', !selected);
        });

        if (templateFooter) {
            templateFooter.classList.toggle('hidden', activeTab !== 'template');
        }
        if (styleFooter) {
            styleFooter.classList.toggle('hidden', activeTab !== 'style');
        }
        if (templatePanel) {
            templatePanel.classList.toggle('hidden', activeTab !== 'template');
        }
        if (stylePanel) {
            stylePanel.classList.toggle('hidden', activeTab !== 'style');
        }
        if (templateTabButton) {
            templateTabButton.classList.toggle('bg-black', activeTab === 'template');
            templateTabButton.classList.toggle('text-white', activeTab === 'template');
            templateTabButton.classList.toggle('bg-white', activeTab !== 'template');
            templateTabButton.classList.toggle('text-slate-600', activeTab !== 'template');
        }
        if (styleTabButton) {
            styleTabButton.classList.toggle('bg-black', activeTab === 'style');
            styleTabButton.classList.toggle('text-white', activeTab === 'style');
            styleTabButton.classList.toggle('bg-white', activeTab !== 'style');
            styleTabButton.classList.toggle('text-slate-600', activeTab !== 'style');
        }
        window.almadenPageStyleUI?.refresh?.();
    }

    function updateSelection(root) {
        root.querySelectorAll('[data-page-template-selection]').forEach(marker => marker.remove());
        root.querySelectorAll('[data-page-number]').forEach(page => {
            const active = getPageNumber(page) === selectedPageNumber && page.dataset.blank !== '1';
            page.classList.toggle('ring-4', active);
            page.classList.toggle('ring-amber-400', active);
            page.classList.toggle('ring-offset-2', active);
            page.setAttribute('aria-pressed', active ? 'true' : 'false');
            if (active) {
                const marker = document.createElement('div');
                marker.dataset.pageTemplateSelection = '1';
                marker.className = 'pointer-events-none absolute inset-0 border-2 border-amber-500 bg-amber-400/10';
                page.appendChild(marker);
            }
        });
        updateAction();
    }

    function deselectPage(root = document.getElementById('pdf-scroller')) {
        selectedPageNumber = null;
        if (root) updateSelection(root);
    }

    function selectPage(page) {
        const pageNumber = getPageNumber(page);
        if (pageNumber === selectedPageNumber) {
            deselectPage();
            return;
        }
        selectedPageNumber = pageNumber;
        const root = document.getElementById('pdf-scroller');
        if (root) updateSelection(root);
    }

    function closeModal() {
        const modal = document.getElementById('page-template-modal');
        const dialog = modal?.querySelector('[data-page-template-dialog]');
        if (!modal || !dialog) return;
        modal.classList.add('opacity-0');
        dialog.classList.remove('scale-100');
        dialog.classList.add('scale-95');
        window.setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
        }, 180);
    }

    async function applyTemplate() {
        if (!Number.isFinite(selectedPageNumber) || selectedPageNumber < 1 || !window.bookState) return;
        const templateId = getTemplateDefinition(selectedTemplateId) ? normalizeId(selectedTemplateId) : getDefaultTemplateId();
        if (!templateId) return;

        window.bookState.settings = window.bookState.settings || {};
        const existingTemplate = getTemplateForSelectedPage();
        const previousTemplates = cloneTemplates(getTemplates());
        const instanceId = existingTemplate
            ? window.almadenPageTemplateState.getInstanceId(existingTemplate)
            : window.almadenPageTemplateState.createInstanceId();
        const nextTemplate = {
            id: instanceId,
            instance_id: instanceId,
            page_number: Number(existingTemplate?.page_number) || selectedPageNumber,
            resolved_page: selectedPageNumber,
            anchor: existingTemplate?.anchor?.flow_id
                ? existingTemplate.anchor
                : window.almadenPageTemplateState.getAnchorForPage(selectedPageNumber),
            template_id: templateId,
            placeholder: { enabled: true },
            slots: buildTemplateSlots(templateId, existingTemplate?.slots || [])
        };
        const existingTemplates = getTemplates();
        window.bookState.settings.page_templates = [
            ...existingTemplates.filter(template => window.almadenPageTemplateState.getInstanceId(template) !== instanceId),
            nextTemplate
        ].sort((left, right) => Number(left.resolved_page || left.page_number) - Number(right.resolved_page || right.page_number));

        closeModal();
        const saved = await persistTemplates();
        if (!saved) {
            window.bookState.settings.page_templates = previousTemplates;
            if (typeof window.showToast === 'function') {
                window.showToast('No se pudo guardar la plantilla.', 'fa-solid fa-circle-exclamation');
            }
            return;
        }

        if (typeof window.compilePDFPreview === 'function') {
            await window.compilePDFPreview(true);
        }
        const result = window.almadenPageTemplateState.getResult(instanceId);
        if (!result?.applied) {
            await rollbackTemplates(previousTemplates);
            if (typeof window.showToast === 'function') {
                const reason = result?.debug?.reason ? ` (${result.debug.reason})` : '';
                window.showToast(`La plantilla no pudo anclarse y el cambio fue revertido${reason}.`, 'fa-solid fa-circle-exclamation');
            }
            return;
        }
        if (typeof window.showToast === 'function') {
            window.showToast(`Plantilla asignada a la página ${result.resolved_page || selectedPageNumber}.`, 'fa-solid fa-table-cells-large');
        }
    }

    async function removeTemplate() {
        if (!Number.isFinite(selectedPageNumber) || selectedPageNumber < 1 || !window.bookState) return;

        window.bookState.settings = window.bookState.settings || {};
        const existingTemplates = getTemplates();
        const selectedTemplate = getTemplateForSelectedPage();
        const instanceId = window.almadenPageTemplateState?.getInstanceId?.(selectedTemplate);
        const nextTemplates = existingTemplates.filter(template => (
            window.almadenPageTemplateState.getInstanceId(template) !== instanceId
        ));
        if (nextTemplates.length === existingTemplates.length) {
            closeModal();
            return;
        }

        window.bookState.settings.page_templates = nextTemplates.sort((left, right) => (
            Number(left.resolved_page || left.page_number) - Number(right.resolved_page || right.page_number)
        ));

        closeModal();
        const saved = await persistTemplates();
        if (!saved) {
            if (typeof window.showToast === 'function') {
                window.showToast('No se pudo quitar la plantilla.', 'fa-solid fa-circle-exclamation');
            }
            return;
        }

        if (typeof window.compilePDFPreview === 'function') {
            await window.compilePDFPreview(true);
        }
        if (typeof window.showToast === 'function') {
            window.showToast(`Plantilla quitada de la página ${selectedPageNumber}.`, 'fa-solid fa-trash-can');
        }
    }

    async function resetPage() {
        if (!Number.isFinite(selectedPageNumber) || selectedPageNumber < 1 || !window.bookState) return;

        const existingTemplates = getTemplates();
        const existingStyles = window.almadenPageStyleState?.getStyles?.() || [];
        const selectedTemplate = getTemplateForSelectedPage();
        const selectedStyle = window.almadenPageStyleState?.getStyleAtSelectedPage?.();
        const templateInstanceId = window.almadenPageTemplateState?.getInstanceId?.(selectedTemplate);
        const styleInstanceId = window.almadenPageStyleState?.getStyleInstanceId?.(selectedStyle);
        const previousTemplates = cloneTemplates(existingTemplates);
        const previousStyles = clonePageStyles(existingStyles);

        const nextTemplates = existingTemplates.filter(template => (
            window.almadenPageTemplateState.getInstanceId(template) !== templateInstanceId
        ));
        const nextStyles = existingStyles.filter(style => (
            window.almadenPageStyleState.getStyleInstanceId(style) !== styleInstanceId
        ));

        if (nextTemplates.length === existingTemplates.length && nextStyles.length === existingStyles.length) {
            closeModal();
            return;
        }

        window.bookState.settings = window.bookState.settings || {};
        window.bookState.settings.page_templates = nextTemplates.sort((left, right) => (
            Number(left.resolved_page || left.page_number) - Number(right.resolved_page || right.page_number)
        ));
        window.bookState.settings.page_styles = nextStyles.sort((left, right) => (
            Number(left.resolved_page || left.page_number) - Number(right.resolved_page || right.page_number)
        ));

        closeModal();
        const saved = await persistTemplates();
        if (!saved) {
            window.bookState.settings.page_templates = previousTemplates;
            window.bookState.settings.page_styles = previousStyles;
            if (typeof window.showToast === 'function') {
                window.showToast('No se pudo reiniciar la página.', 'fa-solid fa-circle-exclamation');
            }
            return;
        }

        if (typeof window.compilePDFPreview === 'function') {
            await window.compilePDFPreview(true);
        }
        if (typeof window.showToast === 'function') {
            window.showToast(`Página ${selectedPageNumber} reiniciada.`, 'fa-solid fa-rotate-left');
        }
    }

    function openModal() {
        if (!Number.isFinite(selectedPageNumber) || selectedPageNumber < 1) return;
        activeTab = 'template';
        const modal = document.getElementById('page-template-modal');
        const dialog = modal?.querySelector('[data-page-template-dialog]');
        const target = document.getElementById('page-template-target-page');
        if (!modal || !dialog) return;

        if (target) target.textContent = String(selectedPageNumber);
        selectedTemplateId = getDefaultTemplateId();
        renderTemplateOptions();
        updateModalActions();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        updateModalActions();
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            dialog.classList.remove('scale-95');
            dialog.classList.add('scale-100');
        });
    }

    function setActiveTab(tab) {
        activeTab = tab === 'style' ? 'style' : 'template';
        updateModalActions();
    }

    function bindModal() {
        if (modalBound) return;
        modalBound = true;
        const modal = document.getElementById('page-template-modal');
        const confirm = document.getElementById('page-template-confirm');
        const remove = document.getElementById('page-template-remove');
        const resetButtons = document.querySelectorAll('[data-page-template-reset]');
        const templateTab = document.querySelector('[data-page-template-tab-button="template"]');
        const styleTab = document.querySelector('[data-page-template-tab-button="style"]');
        if (!modal || !confirm) return;

        modal.addEventListener('click', event => {
            if (event.target === modal || event.target.closest('[data-page-template-close]')) closeModal();
            const templateOption = event.target.closest('[data-page-template-option]');
            if (templateOption) {
                setSelectedTemplateId(templateOption.dataset.pageTemplateOption || '', true);
            }
        });
        if (templateTab) {
            templateTab.addEventListener('click', () => setActiveTab('template'));
        }
        if (styleTab) {
            styleTab.addEventListener('click', () => setActiveTab('style'));
        }
        confirm.addEventListener('click', applyTemplate);
        if (remove) remove.addEventListener('click', removeTemplate);
        resetButtons.forEach(button => {
            button.addEventListener('click', resetPage);
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    }

    function bind(root) {
        if (!root) return;
        bindModal();
        const selectedPageStillExists = Array.from(root.querySelectorAll('[data-page-number]')).some(page => (
            page.dataset.blank !== '1' && getPageNumber(page) === selectedPageNumber
        ));
        if (selectedPageNumber !== null && !selectedPageStillExists) selectedPageNumber = null;
        root.querySelectorAll('[data-page-number]').forEach(page => {
            if (page.dataset.blank !== '1') page.classList.add('cursor-pointer');
        });
        if (!root.dataset.pageTemplateSelectionBound) {
            root.dataset.pageTemplateSelectionBound = '1';
            root.addEventListener('click', event => {
                if (event.target.closest('button, a, input, select, textarea')) return;
                const page = getSelectablePage(event.target);
                if (page) {
                    selectPage(page);
                    return;
                }
                deselectPage(root);
            });
        }
        updateSelection(root);
        updateModalActions();
    }

    window.almadenPageTemplateUI = {
        bind, openModal, closeModal, setActiveTab, getSelectedPageNumber, deselectPage
    };
})();
