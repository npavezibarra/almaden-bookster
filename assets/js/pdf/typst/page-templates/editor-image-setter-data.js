// Builds the chapter/page/slot index consumed by the Image Setter UI.
(function () {
    'use strict';

    function normalizeId(value) {
        return String(value || '').toLowerCase().replace(/[^a-z0-9_-]/g, '');
    }

    function getRegistry() {
        return window.almadenPageTemplateRegistry || {};
    }

    function getDefinition(templateId) {
        return getRegistry()[normalizeId(templateId)] || null;
    }

    function normalizeRatio(value) {
        const width = Math.max(1, Number(value?.width || value?.[0] || 1));
        const height = Math.max(1, Number(value?.height || value?.[1] || 1));
        return { width, height, value: width / height };
    }

    function getChapterCounter() {
        const chapters = window.bookState?.pdfPreview?.universalCounter?.chapters;
        return Array.isArray(chapters) ? chapters : [];
    }

    function normalizeChapter(chapter, counterEntry, index) {
        const id = String(chapter?.id || counterEntry?.id || `chapter-${index + 1}`);
        return {
            id,
            key: `chapter-${normalizeId(id) || index + 1}`,
            title: String(chapter?.title || counterEntry?.title || `Capítulo ${index + 1}`),
            sequence: Number(counterEntry?.sequence || index + 1),
            startPage: Number(counterEntry?.startPage || 0) || null,
            endPage: Number(counterEntry?.endPage || 0) || null,
            rows: []
        };
    }

    function getChapters() {
        const source = Array.isArray(window.bookState?.chapters) ? window.bookState.chapters : [];
        const counter = getChapterCounter();
        const counterById = new Map(counter.map(entry => [String(entry?.id || ''), entry]));
        const chapters = source.map((chapter, index) => (
            normalizeChapter(chapter, counterById.get(String(chapter?.id || '')), index)
        ));

        counter.forEach((entry, index) => {
            const id = String(entry?.id || '');
            if (chapters.some(chapter => chapter.id === id)) return;
            chapters.push(normalizeChapter(null, entry, source.length + index));
        });

        return chapters.sort((left, right) => {
            const leftPage = left.startPage || Number.MAX_SAFE_INTEGER;
            const rightPage = right.startPage || Number.MAX_SAFE_INTEGER;
            return leftPage - rightPage || left.sequence - right.sequence;
        });
    }

    function findChapterForPage(chapters, pageNumber) {
        const page = Number(pageNumber) || 0;
        const exact = chapters.find(chapter => (
            chapter.startPage && chapter.endPage && page >= chapter.startPage && page <= chapter.endPage
        ));
        if (exact) return exact;

        const following = chapters.find(chapter => chapter.startPage && page > 0 && page < chapter.startPage);
        if (following) return following;

        const preceding = chapters.slice().reverse().find(chapter => chapter.startPage && page >= chapter.startPage);
        return preceding || null;
    }

    function getAssetDiagnostic(template, slot) {
        const diagnostics = Array.isArray(window.almadenPageTemplateAssetDiagnostics)
            ? window.almadenPageTemplateAssetDiagnostics
            : [];
        const instanceId = normalizeId(template?.instance_id || template?.id);
        const slotId = normalizeId(slot?.id);
        return diagnostics.find(entry => (
            normalizeId(entry?.instance_id) === instanceId
            && normalizeId(entry?.slot_id) === slotId
        )) || null;
    }

    function makeSlotRow(template, slot) {
        const definition = getDefinition(template?.template_id);
        const slotDefinition = (definition?.slots || []).find(entry => (
            normalizeId(entry?.id) === normalizeId(slot?.id)
        ));
        const pageNumber = window.almadenPageTemplateState?.getResolvedPage?.(template)
            || Number(template?.resolved_page || template?.page_number)
            || 0;
        const attachmentId = Number(slot?.attachment_id) || 0;
        const previewUrl = String(slot?.preview_url || '');
        const diagnostic = getAssetDiagnostic(template, slot);
        const configured = !!(attachmentId || slot?.url || slot?.original_url);
        const assigned = diagnostic ? !!diagnostic.renderable : configured;

        return {
            key: `${window.almadenPageTemplateState?.getInstanceId?.(template) || normalizeId(template?.instance_id)}:${normalizeId(slot?.id)}`,
            instanceId: window.almadenPageTemplateState?.getInstanceId?.(template) || normalizeId(template?.instance_id),
            slotId: normalizeId(slot?.id),
            slotLabel: String(slot?.label || slotDefinition?.label || slot?.id || 'Imagen'),
            templateId: normalizeId(template?.template_id),
            templateLabel: String(definition?.label || template?.template_id || 'Plantilla'),
            pageNumber,
            attachmentId,
            configured,
            assigned,
            pdfReady: diagnostic ? !!diagnostic.renderable : null,
            diagnostic,
            previewUrl,
            ratio: normalizeRatio(slotDefinition?.aspect_ratio),
            slot
        };
    }

    function getRows() {
        const templates = window.almadenPageTemplateState?.getAppliedTemplates?.()
            || window.almadenPageTemplateState?.getTemplates?.()
            || [];
        return templates.flatMap(template => (
            Array.isArray(template?.slots) ? template.slots.map(slot => makeSlotRow(template, slot)) : []
        )).sort((left, right) => left.pageNumber - right.pageNumber || left.slotLabel.localeCompare(right.slotLabel));
    }

    function buildIndex() {
        const chapters = getChapters();
        const ungrouped = {
            id: 'ungrouped',
            key: 'chapter-ungrouped',
            title: 'Sin capítulo',
            sequence: Number.MAX_SAFE_INTEGER,
            startPage: null,
            endPage: null,
            rows: []
        };

        getRows().forEach(row => {
            const chapter = findChapterForPage(chapters, row.pageNumber) || ungrouped;
            row.chapterId = chapter.id;
            row.chapterTitle = chapter.title;
            chapter.rows.push(row);
        });

        if (ungrouped.rows.length) chapters.push(ungrouped);
        const rows = chapters.flatMap(chapter => chapter.rows);
        return {
            chapters,
            rows,
            totals: {
                slots: rows.length,
                assigned: rows.filter(row => row.assigned).length,
                missing: rows.filter(row => !row.assigned).length
            }
        };
    }

    function filterRows(rows, filter) {
        if (filter === 'assigned') return rows.filter(row => row.assigned);
        if (filter === 'missing') return rows.filter(row => !row.assigned);
        return rows.slice();
    }

    window.almadenImageSetterData = {
        buildIndex,
        filterRows,
        normalizeRatio
    };
})();
