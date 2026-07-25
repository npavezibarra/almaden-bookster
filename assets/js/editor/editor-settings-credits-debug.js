(function() {
    const entries = [];
    let sequence = 0;

    function summarize(config) {
        const source = config && typeof config === 'object' ? config : {};
        const logos = Array.isArray(source.logos) ? source.logos : [];

        return {
            people_count: Array.isArray(source.people) ? source.people.length : 0,
            collaborators_count: Array.isArray(source.collaborators) ? source.collaborators.length : 0,
            logo_count: logos.length,
            logo_urls: logos.map((logo) => String(logo && logo.logo_url ? logo.logo_url : '')),
            edition_number: String(source.editorial && source.editorial.edition_number ? source.editorial.edition_number : ''),
        };
    }

    function createTraceId(bookId) {
        sequence += 1;
        return `credits-${String(bookId || 'unknown')}-${Date.now()}-${sequence}`;
    }

    function log(event, details) {
        const entry = {
            timestamp: new Date().toISOString(),
            event: String(event || 'unknown'),
            details: details && typeof details === 'object' ? details : {},
        };

        entries.push(entry);
        if (entries.length > 100) {
            entries.shift();
        }

        console.info('[Almaden Credits]', entry.event, entry);
        return entry;
    }

    window.almadenCreditsDebug = {
        createTraceId,
        getReport: function() {
            return entries.slice();
        },
        log,
        summarize,
    };
})();
