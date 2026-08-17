(function (window, document) {
    'use strict';

    const config = window.almadenContentProtectionConfig || {};
    if (!config.enabled || !config.blockPrint) return;

    window.addEventListener('beforeprint', () => {
        document.body.classList.add('almaden-protected-print');
        if (window.AlmadenProtectionTelemetry) window.AlmadenProtectionTelemetry.record('print');
    });
    window.addEventListener('afterprint', () => {
        document.body.classList.remove('almaden-protected-print');
    });
})(window, document);
