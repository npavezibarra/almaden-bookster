(function (window) {
    'use strict';

    const config = window.almadenContentProtectionConfig || {};
    if (!config.enabled || config.chapterDelivery !== 'on_demand') return;

    const cache = new Map();
    const requests = new Map();
    let activeIndex = -1;

    function retainedIndexes(index) {
        const retained = new Set([index]);
        if (window.bookData && Array.isArray(window.bookData.chapters) && index + 1 < window.bookData.chapters.length) {
            retained.add(index + 1);
        }
        return retained;
    }

    function prune(index) {
        const retained = retainedIndexes(index);
        Array.from(cache.keys()).forEach(key => {
            if (!retained.has(key)) cache.delete(key);
        });
        requests.forEach((request, key) => {
            if (!retained.has(key)) {
                request.controller.abort();
                requests.delete(key);
            }
        });
    }

    function getChapter(index) {
        const chapters = window.bookData && Array.isArray(window.bookData.chapters) ? window.bookData.chapters : [];
        return chapters[index] || null;
    }

    function getErrorMessage(payload) {
        if (payload && payload.data && typeof payload.data.message === 'string') return payload.data.message;
        if (payload && typeof payload.data === 'string') return payload.data;
        return config.chapterError || 'No fue posible cargar este capítulo.';
    }

    function requestChapter(index) {
        if (requests.has(index)) return requests.get(index).promise;
        const chapter = getChapter(index);
        if (!chapter || !chapter.id) return Promise.reject(new Error(config.chapterError || 'Capítulo inválido.'));

        const controller = new AbortController();
        const formData = new FormData();
        formData.append('action', 'almaden_bookster_load_reader_chapter');
        formData.append('nonce', String(config.chapterNonce || ''));
        formData.append('book_id', String(config.bookId || 0));
        formData.append('chapter_id', String(chapter.id));

        const request = {controller, promise: null};
        const promise = fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            body: formData,
            signal: controller.signal
        }).then(async response => {
            const payload = await response.json().catch(() => null);
            if (!response.ok || !payload || !payload.success || !payload.data || parseInt(payload.data.chapterId, 10) !== parseInt(chapter.id, 10)) {
                throw new Error(getErrorMessage(payload));
            }
            return String(payload.data.content || '');
        }).catch(error => {
            if (error && error.name !== 'AbortError' && window.AlmadenProtectionTelemetry) {
                window.AlmadenProtectionTelemetry.record('chapter_load_error');
            }
            throw error;
        }).finally(() => {
            if (requests.get(index) === request) requests.delete(index);
        });

        request.promise = promise;
        requests.set(index, request);
        return promise;
    }

    function prefetchAdjacent(index) {
        const adjacentIndex = index + 1;
        if (!getChapter(adjacentIndex) || cache.has(adjacentIndex)) return;
        requestChapter(adjacentIndex).then(content => {
            if (retainedIndexes(activeIndex).has(adjacentIndex)) {
                cache.set(adjacentIndex, content);
                prune(activeIndex);
            }
        }).catch(error => {
            if (error && error.name !== 'AbortError' && window.console) {
                console.debug('[AlmadenBookster] Adjacent chapter prefetch skipped.');
            }
        });
    }

    async function ensureChapterContent(index) {
        activeIndex = index;
        prune(index);
        if (!cache.has(index)) {
            const content = await requestChapter(index);
            if (activeIndex !== index) throw new DOMException('Chapter request superseded.', 'AbortError');
            cache.set(index, content);
        }
        prune(index);
        window.setTimeout(() => prefetchAdjacent(index), 0);
        return cache.get(index);
    }

    function release() {
        activeIndex = -1;
        cache.clear();
        requests.forEach(request => request.controller.abort());
        requests.clear();
    }

    window.AlmadenChapterLoader = Object.freeze({
        ensureChapterContent,
        release,
        diagnostics: () => ({activeIndex, cachedIndexes: Array.from(cache.keys())})
    });
})(window);
