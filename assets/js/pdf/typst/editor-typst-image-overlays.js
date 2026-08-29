(function () {
    if (window.almadenTypstImageOverlays) return;

    const shared = window.almadenTypstPdfState?.shared;

    function number(value) {
        const parsed = Number.parseFloat(String(value ?? '').replace(/[^0-9.-]/g, ''));
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function unitToPoints(value, unit) {
        const factors = { mm: 72 / 25.4, cm: 72 / 2.54, in: 72, pt: 1 };
        return number(value) * (factors[unit] || factors.cm);
    }

    function horizontalBounds(pageNumber, geometry) {
        const bleed = Math.max(number(geometry.bleed), 0);
        const width = Math.max(number(geometry.physical_width) || number(geometry.width) + (2 * bleed), 1);
        const odd = pageNumber % 2 === 1;
        const left = bleed + number(odd ? geometry.inside : geometry.outside);
        const right = bleed + number(odd ? geometry.outside : geometry.inside);
        return { left: left / width * 100, width: (width - left - right) / width * 100 };
    }

    function createOverlay(report, shell, geometry) {
        const page = Number.parseInt(report.page, 10);
        const physicalHeight = number(geometry.physical_height)
            || number(geometry.height) + (2 * Math.max(number(geometry.bleed), 0));
        const pageHeightPt = Math.max(unitToPoints(physicalHeight, geometry.unit), 1);
        const topPt = Math.max(0, number(report.y));
        const bottomPt = Math.max(topPt + 8, number(report.bottom));
        const horizontal = horizontalBounds(page, geometry);
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.pdfImageOverlay = '1';
        button.dataset.imageBlockId = String(report.id || '');
        button.className = 'absolute z-20 rounded-sm border-2 border-transparent bg-transparent cursor-pointer transition hover:border-sky-500 hover:bg-sky-400/10 focus:border-sky-600 focus:bg-sky-400/10 focus:outline-none';
        button.style.left = `${horizontal.left}%`;
        button.style.width = `${horizontal.width}%`;
        button.style.top = `${topPt / pageHeightPt * 100}%`;
        button.style.height = `${Math.max(1.2, (bottomPt - topPt) / pageHeightPt * 100)}%`;
        button.title = 'Editar imagen';
        button.setAttribute('aria-label', 'Editar imagen del libro');
        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopImmediatePropagation();
            shell.closest('#pdf-scroller')?.querySelectorAll('[data-pdf-image-overlay]').forEach(item => {
                item.classList.toggle('border-sky-600', item === button);
                item.classList.toggle('bg-sky-400/10', item === button);
            });
            window.almadenImageLayout?.openBlockById?.(button.dataset.imageBlockId);
        }, true);
        return button;
    }

    function bind(root) {
        if (!root || !shared) return;
        root.querySelectorAll('[data-pdf-image-overlay]').forEach(item => item.remove());
        const geometry = shared.currentGeometry || {};
        (Array.isArray(shared.imageBlocks) ? shared.imageBlocks : []).forEach(report => {
            const page = Number.parseInt(report?.page, 10);
            const shell = root.querySelector(`[data-page-number="${page}"][data-blank="0"]`);
            if (shell && report?.id) shell.appendChild(createOverlay(report, shell, geometry));
        });
    }

    window.almadenTypstImageOverlays = { bind };
})();
