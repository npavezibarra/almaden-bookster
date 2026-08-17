(function (window, document) {
    'use strict';

    const config = window.almadenContentProtectionConfig || {};
    if (!config.enabled) return;

    let currentChapterId = 0;

    function update(chapterId) {
        currentChapterId = parseInt(chapterId, 10) || 0;
    }

    window.AlmadenWatermark = Object.freeze({
        update,
        currentChapterId: () => currentChapterId
    });
})(window, document);
