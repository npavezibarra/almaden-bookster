function creditsScheduleRemoteSave(config) {
    if (creditsSuppressRemoteSave) return;
    if (typeof bookState === 'undefined' || !bookState || !bookState.bookId) return;

    creditsRemoteSaveQueuedConfig = creditsNormalizeConfig(config);
    if (window.almadenCreditsDebug) {
        window.almadenCreditsDebug.log('save_scheduled', window.almadenCreditsDebug.summarize(creditsRemoteSaveQueuedConfig));
    }

    if (creditsRemoteSaveTimer) {
        clearTimeout(creditsRemoteSaveTimer);
    }

    creditsRemoteSaveTimer = setTimeout(() => {
        creditsRemoteSaveTimer = null;
        creditsFlushRemoteSave().catch((error) => {
            console.warn('No se pudo guardar la configuración de créditos:', error);
        });
    }, 900);
}

function creditsPersistRemoteConfig(config) {
    const endpoint = creditsGetAjaxEndpoint();
    if (!endpoint) {
        return Promise.reject(new Error('No se encontró el endpoint AJAX para guardar los créditos.'));
    }

    const debug = window.almadenCreditsDebug || null;
    const traceId = debug ? debug.createTraceId(bookState.bookId) : '';
    const payload = new FormData();
    payload.append('action', 'almaden_save_credits_config');
    payload.append('book_id', String(bookState.bookId));
    payload.append('nonce', String(bookState.nonce || ''));
    payload.append('credits_config', JSON.stringify(config));
    payload.append('credits_debug_trace_id', traceId);

    if (debug) {
        debug.log('request_started', {
            trace_id: traceId,
            endpoint,
            config: debug.summarize(config),
        });
    }

    return fetch(endpoint, {
        method: 'POST',
        body: payload,
    })
        .then((response) => {
            if (debug) {
                debug.log('response_received', {
                    trace_id: traceId,
                    http_status: response.status,
                    ok: response.ok,
                });
            }
            if (!response.ok) {
                throw new Error(`El servidor respondió con HTTP ${response.status}.`);
            }
            return response.json();
        })
        .then((res) => {
            if (!res || !res.success || !res.data || !res.data.credits_config) {
                const message = res && res.data && res.data.message
                    ? String(res.data.message)
                    : 'WordPress no confirmó el guardado de los créditos.';
                throw new Error(message);
            }

            const canonical = creditsNormalizeConfig(res.data.credits_config);
            if (typeof bookState !== 'undefined' && bookState && bookState.settings) {
                bookState.settings.credits_config = canonical;
                Object.assign(bookState.settings, creditsConfigToLegacy(canonical));
            }

            try {
                localStorage.setItem(creditsGetStorageKey(), JSON.stringify(canonical));
            } catch (error) {
                // Ignoramos errores de storage para no romper la edición.
            }

            if (typeof refreshEditorDisplay === 'function') {
                refreshEditorDisplay(false);
            }
            if (debug) {
                debug.log('save_confirmed', {
                    trace_id: traceId,
                    config: debug.summarize(canonical),
                    server_debug: res.data.debug || null,
                });
            }
            return canonical;
        })
        .catch((error) => {
            if (debug) {
                debug.log('save_failed', {
                    trace_id: traceId,
                    message: error instanceof Error ? error.message : String(error),
                    config: debug.summarize(config),
                });
            }
            throw error;
        });
}

function creditsFlushRemoteSave() {
    if (!creditsRemoteSaveQueuedConfig) {
        return creditsRemoteSavePromise;
    }

    const config = creditsRemoteSaveQueuedConfig;
    creditsRemoteSaveQueuedConfig = null;

    // Serializa las escrituras para que una respuesta antigua nunca pise
    // una configuración más reciente que todavía está en cola.
    creditsRemoteSavePromise = creditsRemoteSavePromise
        .catch(() => null)
        .then(() => creditsPersistRemoteConfig(config));

    return creditsRemoteSavePromise;
}

function creditsForceRemoteSave(config) {
    if (creditsSuppressRemoteSave) return Promise.resolve(null);
    if (typeof bookState === 'undefined' || !bookState || !bookState.bookId) return Promise.resolve(null);

    if (creditsRemoteSaveTimer) {
        clearTimeout(creditsRemoteSaveTimer);
        creditsRemoteSaveTimer = null;
    }

    creditsRemoteSaveQueuedConfig = creditsNormalizeConfig(config || creditsGetConfigFromForm());
    return creditsFlushRemoteSave();
}

