(function (window) {
    'use strict';

    const config = window.almadenContentProtectionConfig || {};
    if (!config.enabled || !config.telemetryEnabled) return;

    const allowedEvents = new Set(['copy', 'cut', 'drag', 'print', 'capture_shortcut', 'chapter_load_error']);
    const lastSent = new Map();

    function getChapterId() {
        if (window.AlmadenWatermark) return window.AlmadenWatermark.currentChapterId();
        if (!window.AlmadenChapterLoader || !window.bookData) return 0;
        const index = window.AlmadenChapterLoader.diagnostics().activeIndex;
        const chapter = window.bookData.chapters[index];
        return chapter ? parseInt(chapter.id, 10) || 0 : 0;
    }

    function record(eventType) {
        if (!allowedEvents.has(eventType)) return;
        const chapterId = getChapterId();
        const key = `${eventType}|${chapterId}`;
        const now = Date.now();
        if (now - (lastSent.get(key) || 0) < 10000) return;
        lastSent.set(key, now);

        const formData = new FormData();
        formData.append('action', 'almaden_bookster_record_protection_event');
        formData.append('nonce', String(config.telemetryNonce || ''));
        formData.append('book_id', String(config.bookId || 0));
        formData.append('chapter_id', String(chapterId));
        formData.append('event_type', eventType);
        fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            keepalive: true,
            body: formData
        }).catch(() => {});
    }

    window.AlmadenProtectionTelemetry = Object.freeze({record});
})(window);
