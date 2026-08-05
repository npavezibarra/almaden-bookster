// Page selection and preset assignment for the Typst/PDF.js preview.
(function () {
    let selectedPageNumber = null;
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

    function getTemplates() {
        const settings = window.bookState?.settings || {};
        return Array.isArray(settings.page_templates) ? settings.page_templates : [];
    }

    function getTemplateForSelectedPage() {
        if (!Number.isFinite(selectedPageNumber) || selectedPageNumber < 1) return null;
        return getTemplates().find(template => Number(template?.page_number) === selectedPageNumber) || null;
    }

    function updateAction() {
        const button = document.getElementById('pdf-page-template-action');
        if (!button) return;

        const label = button.querySelector('span');
        const selected = Number.isFinite(selectedPageNumber) && selectedPageNumber > 0;
        button.classList.toggle('hidden', !selected);
        button.classList.toggle('inline-flex', selected);
        if (label) label.textContent = selected ? `Plantilla: página ${selectedPageNumber}` : 'Aplicar plantilla';
    }

    function updateModalActions() {
        const removeButton = document.getElementById('page-template-remove');
        const confirmButton = document.getElementById('page-template-confirm');
        const hasTemplate = !!getTemplateForSelectedPage();

        if (removeButton) {
            removeButton.classList.toggle('hidden', !hasTemplate);
            removeButton.setAttribute('aria-hidden', hasTemplate ? 'false' : 'true');
        }
        if (confirmButton) {
            confirmButton.textContent = hasTemplate ? 'Reemplazar plantilla' : 'Aplicar plantilla';
        }
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

    function selectPage(page) {
        selectedPageNumber = getPageNumber(page);
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

        window.bookState.settings = window.bookState.settings || {};
        const nextTemplate = {
            id: `page-${selectedPageNumber}-one-column-one-image`,
            page_number: selectedPageNumber,
            template_id: 'one-column-one-image',
            placeholder: { enabled: true }
        };
        const existingTemplates = getTemplates();
        window.bookState.settings.page_templates = [
            ...existingTemplates.filter(template => Number(template?.page_number) !== selectedPageNumber),
            nextTemplate
        ].sort((left, right) => Number(left.page_number) - Number(right.page_number));

        closeModal();
        const saved = typeof window.savePDFSettings === 'function'
            ? await window.savePDFSettings(true, true)
            : true;
        if (!saved) {
            if (typeof window.showToast === 'function') {
                window.showToast('No se pudo guardar la plantilla.', 'fa-solid fa-circle-exclamation');
            }
            return;
        }

        if (typeof window.compilePDFPreview === 'function') {
            await window.compilePDFPreview(true);
        }
        if (typeof window.showToast === 'function') {
            window.showToast(`Plantilla asignada a la página ${selectedPageNumber}.`, 'fa-solid fa-table-cells-large');
        }
    }

    async function removeTemplate() {
        if (!Number.isFinite(selectedPageNumber) || selectedPageNumber < 1 || !window.bookState) return;

        window.bookState.settings = window.bookState.settings || {};
        const existingTemplates = getTemplates();
        const nextTemplates = existingTemplates.filter(template => Number(template?.page_number) !== selectedPageNumber);
        if (nextTemplates.length === existingTemplates.length) {
            closeModal();
            return;
        }

        window.bookState.settings.page_templates = nextTemplates.sort((left, right) => Number(left.page_number) - Number(right.page_number));

        closeModal();
        const saved = typeof window.savePDFSettings === 'function'
            ? await window.savePDFSettings(true, true)
            : true;
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

    function openModal() {
        if (!Number.isFinite(selectedPageNumber) || selectedPageNumber < 1) return;
        const modal = document.getElementById('page-template-modal');
        const dialog = modal?.querySelector('[data-page-template-dialog]');
        const target = document.getElementById('page-template-target-page');
        if (!modal || !dialog) return;

        if (target) target.textContent = String(selectedPageNumber);
        updateModalActions();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => {
            modal.classList.remove('opacity-0');
            dialog.classList.remove('scale-95');
            dialog.classList.add('scale-100');
        });
    }

    function bindModal() {
        if (modalBound) return;
        modalBound = true;
        const modal = document.getElementById('page-template-modal');
        const confirm = document.getElementById('page-template-confirm');
        const remove = document.getElementById('page-template-remove');
        if (!modal || !confirm) return;

        modal.addEventListener('click', event => {
            if (event.target === modal || event.target.closest('[data-page-template-close]')) closeModal();
        });
        confirm.addEventListener('click', applyTemplate);
        if (remove) remove.addEventListener('click', removeTemplate);
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
                if (page) selectPage(page);
            });
        }
        updateSelection(root);
        updateModalActions();
    }

    window.almadenPageTemplateUI = { bind, openModal, closeModal };
})();
