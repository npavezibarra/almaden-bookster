const creditsScriptBaseUrl = (() => {
    const currentScript = document.currentScript;
    if (!currentScript || !currentScript.src) return '';
    return currentScript.src.replace(/editor-settings-credits\.js(?:\?.*)?$/, '');
})();

let creditsAdvancedModulesPromise = null;

function creditsLoadScriptOnce(src) {
    return new Promise((resolve, reject) => {
        if (!src) {
            resolve();
            return;
        }

        const existing = Array.from(document.querySelectorAll('script[src]')).find((script) => script.src === src);
        if (existing && existing.dataset.almadenLoaded === '1') {
            resolve();
            return;
        }

        if (existing && existing.dataset.almadenLoading === '1') {
            existing.addEventListener('load', () => resolve(), { once: true });
            existing.addEventListener('error', () => reject(new Error(`No se pudo cargar ${src}`)), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = src;
        script.async = false;
        script.dataset.almadenLoading = '1';
        script.addEventListener('load', () => {
            script.dataset.almadenLoaded = '1';
            script.dataset.almadenLoading = '0';
            resolve();
        }, { once: true });
        script.addEventListener('error', () => {
            script.dataset.almadenLoading = '0';
            reject(new Error(`No se pudo cargar ${src}`));
        }, { once: true });
        document.head.appendChild(script);
    });
}

function creditsEnsureAdvancedModules() {
    const builderReady = typeof creditsBuildAdvancedEditorMarkup === 'function';
    const readerReady = typeof creditsReadAdvancedCreditsConfig === 'function';
    const binderReady = typeof creditsBindCreditsAdvancedEvents === 'function';
    if (builderReady && readerReady && binderReady) {
        return Promise.resolve(true);
    }

    if (!creditsAdvancedModulesPromise) {
        const builderSrc = `${creditsScriptBaseUrl}editor-settings-credits-advanced.js`;
        const formSrc = `${creditsScriptBaseUrl}editor-settings-credits-advanced-form.js`;
        creditsAdvancedModulesPromise = creditsLoadScriptOnce(builderSrc).then(() => creditsLoadScriptOnce(formSrc));
    }

    return creditsAdvancedModulesPromise;
}

creditsEnsureAdvancedModules().catch((error) => {
    console.warn('No se pudieron cargar los módulos avanzados de créditos:', error);
});

function creditsSyncStateFromForm() {
    if (typeof bookState === 'undefined' || !bookState) return;
    const config = creditsGetConfigFromForm();
    const legacy = creditsConfigToLegacy(config);
    bookState.settings.credits_config = config;
    Object.assign(bookState.settings, legacy);
    try {
        localStorage.setItem(creditsGetStorageKey(), JSON.stringify(config));
    } catch (error) {
        // Ignore storage failures and keep the server-backed state.
    }
    creditsScheduleRemoteSave(config);
    if (typeof compilePDFPreview === 'function') {
        // Credit settings are independent of the editable chapter surface.
        // Refresh them even while that surface has unsaved visual changes.
        compilePDFPreview(false, 'pdf-scroller', false, true);
    } else if (typeof refreshEditorDisplay === 'function') {
        refreshEditorDisplay(false);
    }
    if (bookState && bookState.viewMode === 'ebook' && typeof refreshEbookPreview === 'function') {
        refreshEbookPreview(false);
    }
}

function creditsBindRootEvents(root) {
    if (!root || root.dataset.creditsBound === '1') return;

    root.addEventListener('click', (event) => {
        const tabButton = event.target.closest('[data-credits-tab]');
        if (tabButton && root.contains(tabButton)) {
            event.preventDefault();
            creditsRenderTabState(root, tabButton.getAttribute('data-credits-tab'));
            return;
        }

        const actionButton = event.target.closest('[data-credits-action]');
        if (!actionButton || !root.contains(actionButton)) return;
        const action = actionButton.getAttribute('data-credits-action');

        if (action === 'add-person') {
            event.preventDefault();
            creditsAppendRow('person');
            creditsRenderTabState(root, 'people');
            return;
        }

        if (action === 'add-collaborator') {
            event.preventDefault();
            creditsAppendRow('collaborator');
            creditsRenderTabState(root, 'collaborators');
            return;
        }

        if (action === 'add-logo') {
            event.preventDefault();
            creditsAppendRow('logo');
            creditsRenderTabState(root, 'logos');
            return;
        }

        if (action === 'remove-row') {
            event.preventDefault();
            const row = actionButton.closest('[data-credits-row]');
            if (row) {
                row.remove();
                const container = actionButton.closest('[id$="-container"]');
                if (container && container.children.length === 0 && container.id === 'credits-people-container') {
                    container.insertAdjacentHTML('beforeend', creditsBuildPersonRow());
                    const firstRow = container.querySelector('[data-credits-row="person"]');
                    if (firstRow && typeof creditsUpdatePersonCustomRoleField === 'function') {
                        creditsUpdatePersonCustomRoleField(firstRow);
                    }
                }
                creditsSyncStateFromForm();
            }
            return;
        }

        if (action === 'choose-image') {
            event.preventDefault();
            const row = actionButton.closest('[data-credits-row]');
            if (!row) return;
            const targetInputName = actionButton.getAttribute('data-credits-target-input') || 'logo_url';
            const targetPreviewSelector = actionButton.getAttribute('data-credits-target-preview') || '[data-credits-preview-image]';
            const targetInput = row.querySelector(`[data-credits-field="${targetInputName}"]`);
            const preview = row.querySelector(targetPreviewSelector);
            const mediaFrame = creditsEnsureMediaFrame((url) => {
                if (targetInput) targetInput.value = url;
                if (preview) {
                    preview.src = url;
                    preview.classList.remove('hidden');
                }
                const placeholder = row.querySelector('[data-credits-preview-placeholder]');
                if (placeholder) placeholder.classList.add('hidden');
                creditsUpdateLogoRowPreview(row);
                creditsSyncStateFromForm();
                creditsForceRemoteSave(creditsGetConfigFromForm()).catch((error) => {
                    console.warn('No se pudo guardar el logo de créditos:', error);
                });
            });
            if (mediaFrame) {
                mediaFrame.open();
            }
        }
    });

    root.addEventListener('input', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLElement)) return;
        if (!input.matches('[data-credits-field], #setting-credits-edition, #setting-credits-date, #setting-credits-isbn, #setting-credits-printer, #setting-credits-blank-before, #setting-credits-blank-after, #setting-credits-copyright, #setting-credits-license')) return;
        if (input.matches('[data-credits-field="role"]')) {
            const row = input.closest('[data-credits-row="person"]');
            if (row && typeof creditsUpdatePersonCustomRoleField === 'function') {
                creditsUpdatePersonCustomRoleField(row);
            }
        }
        const field = input.getAttribute('data-credits-field');
        if (field === 'logo_url') {
            creditsUpdateImagePreview(input);
        }
        if (field === 'logo_url' || field === 'position' || field === 'size_px') {
            creditsUpdateLogoRowPreview(input.closest('[data-credits-row]'));
        }
        if (field === 'collaborators_visible' && typeof creditsUpdateCollaboratorsVisibilityState === 'function') {
            creditsUpdateCollaboratorsVisibilityState(root);
        }
        creditsSyncStateFromForm();
    });

    root.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLElement)) return;
        if (!input.matches('[data-credits-field], #setting-credits-edition, #setting-credits-date, #setting-credits-isbn, #setting-credits-printer, #setting-credits-blank-before, #setting-credits-blank-after, #setting-credits-copyright, #setting-credits-license')) return;
        if (input.matches('[data-credits-field="role"]')) {
            const row = input.closest('[data-credits-row="person"]');
            if (row && typeof creditsUpdatePersonCustomRoleField === 'function') {
                creditsUpdatePersonCustomRoleField(row);
            }
        }
        const field = input.getAttribute('data-credits-field');
        if (field === 'logo_url') {
            creditsUpdateImagePreview(input);
        }
        if (field === 'logo_url' || field === 'position' || field === 'size_px') {
            creditsUpdateLogoRowPreview(input.closest('[data-credits-row]'));
        }
        if (field === 'collaborators_visible' && typeof creditsUpdateCollaboratorsVisibilityState === 'function') {
            creditsUpdateCollaboratorsVisibilityState(root);
        }
        creditsSyncStateFromForm();
    });

    root.dataset.creditsBound = '1';

    root.querySelectorAll('[data-credits-row="person"]').forEach((row) => {
        if (typeof creditsUpdatePersonCustomRoleField === 'function') {
            creditsUpdatePersonCustomRoleField(row);
        }
    });

    if (typeof creditsBindCreditsAdvancedEvents === 'function') {
        creditsBindCreditsAdvancedEvents(root);
    }
}

function creditsPopulateForm(config) {
    const root = document.getElementById('credits-editor-root');
    if (!root) return;
    const normalized = creditsNormalizeConfig(config);
    creditsSuppressRemoteSave = true;
    try {
        root.innerHTML = creditsBuildEditorMarkup(normalized);
        creditsBindRootEvents(root);
        if (typeof creditsUpdateCollaboratorsVisibilityState === 'function') {
            creditsUpdateCollaboratorsVisibilityState(root);
        }
        creditsRenderTabState(root, root.dataset.activeCreditsTab || 'logos');
        creditsSyncStateFromForm();
    } finally {
        creditsSuppressRemoteSave = false;
    }
}

window.almadenGetCreditsRoleLabel = function(value) {
    const found = CREDITS_ROLE_OPTIONS.find((option) => option.value === String(value || ''));
    return found ? found.label : 'Autor';
};

window.almadenGetCreditsCompanyTypeLabel = function(value) {
    const found = CREDITS_COMPANY_TYPE_OPTIONS.find((option) => option.value === String(value || ''));
    return found ? found.label : 'Empresa';
};

window.almadenGetCreditsLogoPositionLabel = function(value) {
    const found = CREDITS_LOGO_POSITION_OPTIONS.find((option) => option.value === String(value || ''));
    return found ? found.label : 'Centro';
};

window.almadenGetCreditsLicenseLabel = function(value) {
    const found = CREDITS_LICENSE_OPTIONS.find((option) => option.value === String(value || ''));
    return found ? found.label : 'Todos los derechos reservados';
};

window.almadenGetCreditsConfigDefaults = creditsGetDefaultConfig;
window.almadenNormalizeCreditsConfig = creditsNormalizeConfig;
window.almadenCreditsConfigToLegacy = creditsConfigToLegacy;

window.getCreditsConfigFromForm = function() {
    return creditsGetConfigFromForm();
};

window.getCreditsConfigJSON = function() {
    return JSON.stringify(creditsGetConfigFromForm());
};

window.saveCreditsConfig = function(config) {
    return creditsForceRemoteSave(config || creditsGetConfigFromForm());
};

window.getCustomCreditsJSON = function() {
    const config = creditsGetConfigFromForm();
    return JSON.stringify(
        (config.people || []).map((person) => ({
            role: person.role || '',
            name: person.name || '',
            custom_role_title: person.custom_role_title || '',
        }))
    );
};

window.renderCustomCredits = function(creditsJSON) {
    const root = document.getElementById('credits-editor-root');
    if (!root) return;
    const current = creditsGetConfigFromForm();
    let incoming = creditsJSON;
    if (typeof incoming === 'string') {
        try {
            incoming = JSON.parse(incoming);
        } catch (error) {
            incoming = [];
        }
    }
    if (Array.isArray(incoming)) {
        current.people = incoming.map((item) => ({
            name: item && item.name ? String(item.name) : '',
            role: item && item.role ? String(item.role) : 'author',
            custom_role_title: item && item.custom_role_title ? String(item.custom_role_title) : '',
            email: '',
            website: '',
            show_contact: 0,
        }));
    } else if (incoming && typeof incoming === 'object') {
        Object.assign(current, creditsNormalizeConfig(incoming));
    }
    creditsPopulateForm(current);
};

window.addCustomCreditRow = function(role = '', name = '', customRoleTitle = '') {
    creditsAppendRow('person', {
        role: role || 'author',
        name: name || '',
        custom_role_title: customRoleTitle || '',
    });
};

window.initCreditsForm = function() {
    const root = document.getElementById('credits-editor-root');
    if (!root) return;

    const initialize = () => {
        // WordPress es la fuente autoritativa. La copia local solo se actualiza
        // después de recibir una confirmación válida del servidor.
        const serverConfig = creditsNormalizeConfig(bookState.settings.credits_config || bookState.settings || {});
        creditsPopulateForm(serverConfig);
        if (window.almadenCreditsDebug) {
            window.almadenCreditsDebug.log('form_initialized_from_server', {
                book_id: bookState.bookId,
                config: window.almadenCreditsDebug.summarize(serverConfig),
            });
        }

        try {
            localStorage.setItem(creditsGetStorageKey(), JSON.stringify(serverConfig));
        } catch (error) {
            // El editor puede funcionar aunque el navegador bloquee localStorage.
        }
    };

    if (typeof creditsBuildAdvancedEditorMarkup !== 'function'
        || typeof creditsReadAdvancedCreditsConfig !== 'function'
        || typeof creditsBindCreditsAdvancedEvents !== 'function') {
        creditsEnsureAdvancedModules()
            .then(() => {
                if (document.getElementById('credits-editor-root')) {
                    initialize();
                }
            })
            .catch((error) => {
                console.warn('No se pudieron inicializar los créditos avanzados:', error);
                initialize();
            });
        return;
    }

    initialize();
};
