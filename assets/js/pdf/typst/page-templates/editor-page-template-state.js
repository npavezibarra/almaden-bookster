// Shared stable-instance state for Typst page-template controls.
(function () {
    function normalizeId(value) {
        return String(value || '').toLowerCase().replace(/[^a-z0-9_-]/g, '');
    }

    function getTemplates() {
        const settings = window.bookState?.settings || {};
        return Array.isArray(settings.page_templates) ? settings.page_templates : [];
    }

    function getResults() {
        return Array.isArray(window.almadenPageTemplateResults)
            ? window.almadenPageTemplateResults
            : [];
    }

    function getInstanceId(template) {
        return normalizeId(template?.instance_id || template?.id || '');
    }

    function createInstanceId() {
        const uuid = typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
            ? crypto.randomUUID()
            : `${Date.now()}-${Math.random().toString(16).slice(2)}`;
        return `tpl-${normalizeId(uuid)}`;
    }

    function getResult(instanceId) {
        const normalized = normalizeId(instanceId);
        return getResults().find(result => normalizeId(result?.instance_id) === normalized) || null;
    }

    function getResolvedPage(template) {
        const result = getResult(getInstanceId(template));
        if (result?.applied) {
            return Number(result.resolved_page || result.page) || 0;
        }
        return Number(template?.resolved_page || template?.page_number) || 0;
    }

    function getAppliedTemplates() {
        const templates = getTemplates();
        const results = getResults();
        if (!results.length) {
            return templates;
        }
        return templates.filter(template => getResult(getInstanceId(template))?.applied);
    }

    function getTemplateAtPage(pageNumber) {
        const target = Number(pageNumber) || 0;
        const applied = getAppliedTemplates().find(template => getResolvedPage(template) === target);
        if (applied) return applied;
        if (getResults().length) return null;
        return getTemplates().find(template => getResolvedPage(template) === target) || null;
    }

    function getAnchorForPage(pageNumber) {
        const target = Number(pageNumber) || 0;
        const rows = (Array.isArray(window.almadenPageTemplateFlowMap) ? window.almadenPageTemplateFlowMap : [])
            .filter(row => Number(row?.page) === target && /^almaden-(?:flow|transition)-\d+$/.test(String(row?.id || '')))
            .sort((left, right) => {
                const leftTransition = left?.kind === 'transition' || String(left?.id || '').startsWith('almaden-transition-');
                const rightTransition = right?.kind === 'transition' || String(right?.id || '').startsWith('almaden-transition-');
                if (leftTransition !== rightTransition) return leftTransition ? -1 : 1;
                const leftOrder = Number(String(left.id).match(/\d+$/)?.[0]) || Number.MAX_SAFE_INTEGER;
                const rightOrder = Number(String(right.id).match(/\d+$/)?.[0]) || Number.MAX_SAFE_INTEGER;
                return leftOrder - rightOrder;
            });
        return rows.length ? { flow_id: rows[0].id } : { flow_id: '' };
    }

    function reconcileResults() {
        getTemplates().forEach(template => {
            const result = getResult(getInstanceId(template));
            if (!result?.applied) return;
            template.instance_id = getInstanceId(template);
            template.id = template.instance_id;
            template.resolved_page = Number(result.resolved_page || result.page) || template.resolved_page;
            if (result.anchor?.flow_id) {
                template.anchor = { flow_id: result.anchor.flow_id };
            }
        });
        window.almadenPageTemplateImagesUI?.refresh?.();
    }

    window.almadenPageTemplateState = {
        normalizeId,
        getTemplates,
        getResults,
        getInstanceId,
        createInstanceId,
        getResult,
        getResolvedPage,
        getAppliedTemplates,
        getTemplateAtPage,
        getAnchorForPage,
        reconcileResults
    };
})();
