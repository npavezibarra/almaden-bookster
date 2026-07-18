/**
 * Auth Modal JS
 */

// Global functions (available immediately for inline onclick handlers)
window.PLAuthOpenModal = function (view) {
    console.info('[pl-auth] PLAuthOpenModal called', { view: view });
    window.__plAuthDebug = window.__plAuthDebug || {};
    window.__plAuthDebug.openCalled = true;
    if (typeof initPlAuth === 'function') {
        initPlAuth();
    }
    var overlay = document.getElementById('pl-auth-overlay');
    if (overlay) {
        console.info('[pl-auth] opening overlay');
        window.__plAuthDebug.overlayFound = true;
        overlay.classList.add('is-open');
        // If we have showView available, use it
        if (typeof window.plAuthShowView === 'function') {
            window.plAuthShowView(view === 'register' ? 'register' : 'login');
        }
    } else {
        console.warn('[pl-auth] overlay not found when opening modal');
    }
};

window.PLAuthCloseModal = function () {
    var overlay = document.getElementById('pl-auth-overlay');
    if (overlay) {
        console.info('[pl-auth] closing overlay');
        overlay.classList.remove('is-open');
    } else {
        console.warn('[pl-auth] overlay not found when closing modal');
    }
};

function initPlAuth() {
    var overlay = document.getElementById('pl-auth-overlay');
    // The modal is intentionally not rendered for authenticated users. The nav
    // still needs to hydrate after a successful login, so do not stop the whole
    // auth script when the overlay is absent.
    if (!overlay) {
        hydrateAuthenticatedNav();
        return;
    }
    if (overlay.hasAttribute('data-pl-auth-inited')) {
        return;
    }
    overlay.setAttribute('data-pl-auth-inited', '1');
    console.info('[pl-auth] init overlay', overlay);

    var closeBtn = overlay.querySelector('[data-pl-auth-close]');
    var tabs = overlay.querySelectorAll('[data-pl-auth-view]');
    var form = overlay.querySelector('[data-pl-auth-form]');
    var modeInput = overlay.querySelector('[data-pl-auth-mode]');
    var submitBtn = overlay.querySelector('[data-pl-auth-submit]');
    var title = overlay.querySelector('[data-pl-auth-title]');
    var copy = overlay.querySelector('[data-pl-auth-copy]');
    var footerCopy = overlay.querySelector('[data-pl-auth-footer-copy]');
    var toggleLink = overlay.querySelector('[data-pl-auth-toggle-link]');
    var message = overlay.querySelector('[data-pl-auth-message]');
    var messageText = overlay.querySelector('[data-pl-auth-message-text]');
    var loginOnly = overlay.querySelector('[data-pl-auth-login-row]');
    var registerFields = overlay.querySelectorAll('.pl-auth-register-only');
    var initialView = overlay.getAttribute('data-initial-view') || 'login';
    var notice = overlay.getAttribute('data-notice') || '';
    var error = overlay.getAttribute('data-error') || '';
    var autoOpen = overlay.getAttribute('data-auto-open') === '1';
    var emailField = document.getElementById('pl-auth-email');
    var emailConfirmField = document.getElementById('pl-auth-email-confirm');
    var firstNameField = document.getElementById('pl-auth-first-name');
    var lastNameField = document.getElementById('pl-auth-last-name');
    var emailLabel = overlay.querySelector('[data-pl-auth-email-label]');
    var inlineMessage = overlay.querySelector('[data-pl-auth-inline-message]');
    var forgotLink = overlay.querySelector('[data-pl-auth-forgot-link]');
    var passwordField = overlay.querySelector('[data-pl-auth-password-field]');
    
    if (typeof plAuthData === 'undefined') {
        console.error('[pl-auth] plAuthData not found');
        return;
    }

    console.info('[pl-auth] plAuthData loaded', {
        isSpanish: plAuthData.isSpanish,
        loginUrl: plAuthData.loginUrl,
        registerUrl: plAuthData.registerUrl
    });

    var forgotNonce = plAuthData.forgotNonce;
    var ajaxUrl = plAuthData.ajaxUrl;
    var labels = plAuthData.labels;
    var loginUrl = plAuthData.loginUrl;
    var registerUrl = plAuthData.registerUrl;
    var isSpanish = plAuthData.isSpanish;

    var forgotTimer = null;
    var lastForgotEmail = '';
    var hasSentReset = false;

    function getCurrentRedirectUrl() {
        try {
            var url = new URL(window.location.href);
            [
                'pl_auth_view',
                'pl_auth_notice',
                'pl_auth_error',
                'pl_auth_unverified',
                'pl_auth_unverified_after_quiz'
            ].forEach(function (key) {
                url.searchParams.delete(key);
            });
            url.hash = '';
            return url.toString();
        } catch (e) {
            return window.location.href;
        }
    }

    function syncRedirectField() {
        var redirectField = overlay.querySelector('[data-pl-auth-redirect]');
        var form = overlay.querySelector('[data-pl-auth-form]');
        var action = getCurrentRedirectUrl();
        if (!redirectField) {
            console.warn('[pl-auth] redirect field not found');
            return;
        }

        redirectField.value = action;
        if (form) {
            form.action = action;
        }
        console.info('[pl-auth] synced redirect url', redirectField.value);
    }

    function hydrateAuthenticatedNav() {
        if (typeof plAuthData === 'undefined' || !plAuthData.currentUserUrl) return;

        var actionRoots = document.querySelectorAll('[data-almaden-auth-nav-actions]');
        if (!actionRoots || !actionRoots.length) return;

        fetch(plAuthData.currentUserUrl, { credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                var payload = result && result.data ? result.data : null;
                if (!result || !result.success || !payload || !payload.isLoggedIn) return;

                actionRoots.forEach(function (root) {
                    if (!root) return;
                    if (root.querySelector('[data-almaden-user-menu-root]')) return;

                    root.innerHTML = '' +
                        '<div class="relative" data-almaden-user-menu-root>' +
                            '<button type="button" data-almaden-user-menu-button aria-haspopup="true" aria-expanded="false" class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm transition hover:border-gray-300 hover:bg-gray-50">' +
                                (payload.avatarHtml || '') +
                                '<span data-almaden-user-name class="max-w-[12rem] truncate"></span>' +
                                '<svg class="h-4 w-4 text-gray-500 transition-transform duration-200" data-almaden-user-menu-caret viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" /></svg>' +
                            '</button>' +
                            '<div id="almaden-app-user-menu" data-almaden-user-menu class="invisible absolute right-0 top-full z-50 mt-3 w-56 translate-y-1 rounded-[1.5rem] border border-gray-200 bg-white p-2 opacity-0 transition-all duration-200">' +
                                '<a href="' + (payload.logoutUrl || '#') + '" class="flex items-center rounded-[1.1rem] px-4 py-3 text-base font-semibold text-gray-900 transition hover:bg-gray-50 hover:text-black">Cerrar sesión</a>' +
                            '</div>' +
                        '</div>';

                    var nameNode = root.querySelector('[data-almaden-user-name]');
                    if (nameNode) {
                        nameNode.textContent = payload.name || '';
                    }

                    var menuRoot = root.querySelector('[data-almaden-user-menu-root]');
                    var button = root.querySelector('[data-almaden-user-menu-button]');
                    var menu = root.querySelector('[data-almaden-user-menu]');
                    var caret = root.querySelector('[data-almaden-user-menu-caret]');

                    if (!menuRoot || !button || !menu) return;

                    function closeMenu() {
                        menu.classList.add('invisible', 'opacity-0', 'translate-y-1');
                        menu.classList.remove('visible', 'opacity-100', 'translate-y-0');
                        button.setAttribute('aria-expanded', 'false');
                        if (caret) caret.style.transform = 'rotate(0deg)';
                    }

                    function openMenu() {
                        menu.classList.remove('invisible', 'opacity-0', 'translate-y-1');
                        menu.classList.add('visible', 'opacity-100', 'translate-y-0');
                        button.setAttribute('aria-expanded', 'true');
                        if (caret) caret.style.transform = 'rotate(180deg)';
                    }

                    button.addEventListener('click', function (event) {
                        event.stopPropagation();
                        var isOpen = button.getAttribute('aria-expanded') === 'true';
                        if (isOpen) {
                            closeMenu();
                        } else {
                            openMenu();
                        }
                    });

                    menu.addEventListener('click', function (event) {
                        event.stopPropagation();
                    });

                    document.addEventListener('click', closeMenu);
                    document.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') closeMenu();
                    });
                });
            })
            .catch(function () {
                // A failed status request must never leave a stale loading state
                // or break the login modal; the server-rendered nav remains usable.
            });
    }

    function prefillFromQuery() {
        try {
            var params = new URLSearchParams(window.location.search || '');
            var emailKeys = ['invite_email', 'signup_email', 'email'];
            var firstNameKeys = ['invite_first_name', 'signup_first_name', 'first_name'];
            var lastNameKeys = ['invite_last_name', 'signup_last_name', 'last_name'];

            var prefillEmail = '';
            emailKeys.some(function (key) {
                var v = (params.get(key) || '').trim();
                if (v) { prefillEmail = v; return true; }
                return false;
            });

            if (prefillEmail && emailField && !emailField.value) { emailField.value = prefillEmail; }
            if (prefillEmail && emailConfirmField && !emailConfirmField.value) { emailConfirmField.value = prefillEmail; }

            var prefillFirst = '';
            firstNameKeys.some(function (key) {
                var v = (params.get(key) || '').trim();
                if (v) { prefillFirst = v; return true; }
                return false;
            });
            if (prefillFirst && firstNameField && !firstNameField.value) { firstNameField.value = prefillFirst; }

            var prefillLast = '';
            lastNameKeys.some(function (key) {
                var v = (params.get(key) || '').trim();
                if (v) { prefillLast = v; return true; }
                return false;
            });
            if (prefillLast && lastNameField && !lastNameField.value) { lastNameField.value = prefillLast; }
        } catch (e) {}
    }

    function setMessage(type, text) {
        if (!message) return;
        if (!text) {
            if (messageText) {
                messageText.textContent = '';
            } else {
                message.textContent = '';
            }
            message.className = 'pl-auth-message';
            overlay.classList.remove('pl-auth-minimal-error');
            return;
        }
        if (messageText) {
            messageText.textContent = text;
        } else {
            message.textContent = text;
        }
        message.className = 'pl-auth-message is-visible ' + (type === 'error' ? 'is-error' : 'is-notice');
    }

    function showView(view) {
        var isRegister = view === 'register';
        var isForgot = view === 'forgot';
        var isLogin = view === 'login';

        tabs.forEach(function (tab) {
            tab.classList.toggle('is-active', tab.getAttribute('data-pl-auth-view') === view);
        });

        if (modeInput) modeInput.value = view;
        if (submitBtn) {
            submitBtn.textContent = isRegister ? labels.create_account : labels.login;
            submitBtn.classList.toggle('pl-auth-hidden', isForgot);
        }
        if (title) title.textContent = isForgot ? labels.forgot_title : (isRegister ? labels.register_title : labels.welcome);
        if (copy) copy.textContent = isForgot ? labels.forgot_copy : (isRegister ? labels.register_copy : labels.login_copy);

        // Login view uses logo instead of title/copy.
        overlay.classList.toggle('pl-auth-login-logo', isLogin);

        if (emailField) {
            emailField.type = (isRegister || isForgot) ? 'email' : 'text';
            emailField.setAttribute('autocomplete', (isRegister || isForgot) ? 'email' : 'username');
            emailField.setAttribute('inputmode', (isRegister || isForgot) ? 'email' : 'text');
            emailField.setAttribute('placeholder', isRegister ? (isSpanish ? 'correo@ejemplo.com' : 'email@domain.com') : (isForgot ? (isSpanish ? 'correo@ejemplo.com' : 'email@domain.com') : (isSpanish ? 'correo@ejemplo.com o usuario' : 'email@domain.com or username')));
        }
        if (emailLabel) emailLabel.textContent = (isRegister || isForgot) ? labels.email : labels.login_identifier;

        if (footerCopy && toggleLink) {
            footerCopy.textContent = isForgot ? (isSpanish ? "¿Recordaste tu contraseña?" : "Remembered your password?") : (isRegister ? labels.already_account : labels.new_here);
            toggleLink.textContent = isForgot ? labels.back_to_login : (isRegister ? labels.back_to_login : labels.create_account_link);
            toggleLink.setAttribute('href', isForgot ? loginUrl : (isRegister ? loginUrl : registerUrl));
        }

        if (loginOnly) loginOnly.classList.toggle('pl-auth-hidden', isRegister || isForgot);
        registerFields.forEach(function (field) { field.classList.toggle('pl-auth-hidden', !isRegister); });
        if (passwordField) passwordField.classList.toggle('pl-auth-hidden', isForgot);
        if (inlineMessage) { inlineMessage.style.display = 'none'; inlineMessage.textContent = ''; inlineMessage.style.color = '#000000'; }
        hasSentReset = false;
        // Keep actions visibility controlled by the current message/error state.
    }

    // Export showView to window so PLAuthOpenModal can use it
    window.plAuthShowView = showView;

    function openModal(view) {
        showView(view);
        syncRedirectField();
        overlay.classList.add('is-open');
    }

    prefillFromQuery();

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            showView(tab.getAttribute('data-pl-auth-view') === 'register' ? 'register' : 'login');
            syncRedirectField();
            setMessage('', '');
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            if (overlay.classList.contains('is-loading')) return;
            window.PLAuthCloseModal();
        });
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target && event.target.closest ? event.target.closest('[data-pl-auth-open], [data-rcp-auth-open]') : null;
        if (!trigger) return;
        event.preventDefault();
        console.info('[pl-auth] trigger clicked', {
            view: trigger.getAttribute('data-pl-auth-view') || 'login',
            node: trigger
        });
        window.PLAuthOpenModal(trigger.getAttribute('data-pl-auth-view') === 'register' ? 'register' : 'login');
    });

    if (toggleLink) {
        toggleLink.addEventListener('click', function (event) {
            event.preventDefault();
            var current = modeInput ? modeInput.value : 'login';
            if (current === 'forgot') { showView('login'); } else { showView(current === 'register' ? 'login' : 'register'); }
            syncRedirectField();
            setMessage('', '');
        });
    }

    if (forgotLink) {
        forgotLink.addEventListener('click', function (event) {
            event.preventDefault();
            showView('forgot');
            syncRedirectField();
            setMessage('', '');
            overlay.classList.remove('pl-auth-minimal-error');
        });
    }

    function setInlineMessage(type, text) {
        if (!inlineMessage) return;
        if (!text) { inlineMessage.style.display = 'none'; inlineMessage.textContent = ''; inlineMessage.style.color = '#000000'; return; }
        inlineMessage.style.display = 'block';
        inlineMessage.textContent = text;
        inlineMessage.style.color = type === 'error' ? '#b91c1c' : '#000000';
    }

    function debounceForgotProbe() {
        if (!emailField) return;
        var email = (emailField.value || '').trim().toLowerCase();
        if (forgotTimer) window.clearTimeout(forgotTimer);
        if (!email || email.indexOf('@') === -1) { setInlineMessage('', ''); lastForgotEmail = ''; hasSentReset = false; return; }

        forgotTimer = window.setTimeout(function () {
            if ((modeInput ? modeInput.value : 'login') !== 'forgot') return;
            if (email === lastForgotEmail && hasSentReset) return;
            lastForgotEmail = email;
            setInlineMessage('', '');

            var url = ajaxUrl + '?action=pl_auth_forgot_password_probe' + '&nonce=' + encodeURIComponent(forgotNonce) + '&email=' + encodeURIComponent(email);
            fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (data) {
                if (!data || !data.success || !data.data) return;
                if (data.data.invalid) return;
                if (!data.data.exists) { hasSentReset = false; setInlineMessage('error', labels.email_not_registered); return; }
                hasSentReset = true;
                setInlineMessage('notice', labels.reset_sent);
            }).catch(function () {});
        }, 450);
    }

    if (emailField) {
        emailField.addEventListener('input', function () { if ((modeInput ? modeInput.value : 'login') !== 'forgot') return; debounceForgotProbe(); });
        emailField.addEventListener('blur', function () { if ((modeInput ? modeInput.value : 'login') !== 'forgot') return; debounceForgotProbe(); });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            syncRedirectField();
            var view = modeInput ? modeInput.value : 'login';
            var email = document.getElementById('pl-auth-email');
            var emailConfirm = document.getElementById('pl-auth-email-confirm');
            var password = document.getElementById('pl-auth-password');
            var passwordConfirm = document.getElementById('pl-auth-password-confirm');

            if (view === 'forgot') { event.preventDefault(); debounceForgotProbe(); return; }

            if (view === 'register') {
                var emailValue = email ? email.value.trim() : '';
                var emailConfirmValue = emailConfirm ? emailConfirm.value.trim() : '';
                var passwordValue = password ? password.value : '';
                var passwordConfirmValue = passwordConfirm ? passwordConfirm.value : '';

                if (emailValue !== emailConfirmValue) { event.preventDefault(); setMessage('error', plAuthData.messages.email_mismatch); return; }
                if (passwordValue !== passwordConfirmValue) { event.preventDefault(); setMessage('error', plAuthData.messages.password_mismatch); return; }
            }

            if (submitBtn) submitBtn.disabled = true;
            if (view === 'register') { overlay.classList.add('is-loading'); if (closeBtn) closeBtn.disabled = true; }
        });
    }

    function applyNoticeError() {
        if (notice === 'verification_sent') {
            setMessage('notice', isSpanish ? "Hemos enviado un correo de confirmación. Por favor, revisa tu bandeja de entrada." : "We sent a confirmation email. Please check your inbox.");
            return;
        }

        if (notice === 'verified') {
            setMessage('notice', isSpanish ? "Tu correo ha sido verificado. Ahora puedes iniciar sesión." : "Your email has been confirmed. You can now log in.");
            return;
        }

        if (!error) {
            return;
        }

        var messageMap = isSpanish ? {
            invalid_nonce: 'No pudimos verificar tu solicitud. Por favor, inténtalo de nuevo.',
            missing_login: 'Por favor, ingresa tu correo y contraseña.',
            invalid_credentials: 'Esas credenciales son incorrectas.',
            invalid_login: 'Los datos de acceso no son válidos.',
            pl_auth_unverified: 'Tu cuenta aún no está verificada. Por favor, confirma tu correo primero.',
            invalid_email: 'Por favor, ingresa un correo electrónico válido.',
            invalid_username: 'No pudimos crear tu usuario. Por favor, intenta con otro correo electrónico.',
            email_mismatch: 'Los correos electrónicos no coinciden.',
            weak_password: 'La contraseña debe tener al menos 8 caracteres.',
            password_mismatch: 'Las contraseñas no coinciden.',
            account_exists: 'Ya existe una cuenta con ese correo electrónico.',
            create_failed: 'No pudimos crear tu cuenta. Por favor, inténtalo de nuevo.',
            invalid_token: 'El enlace de confirmación no es válido o ha expirado.',
            token_expired: 'El enlace de confirmación ha expirado.',
            pl_uc_role_blocked: 'Solo administradores o editores pueden ingresar mientras el sitio está en construcción.'
        } : {
            invalid_nonce: 'We could not verify your request. Please try again.',
            missing_login: 'Please enter your email and password.',
            invalid_credentials: 'Those credentials are incorrect.',
            invalid_login: 'The login details were not valid.',
            pl_auth_unverified: 'Your account is not verified yet. Please confirm your email address first.',
            invalid_email: 'Please enter a valid email address.',
            invalid_username: 'We could not create your user. Please try a different email address.',
            email_mismatch: 'The email addresses do not match.',
            weak_password: 'Your password must be at least 8 characters long.',
            password_mismatch: 'The passwords do not match.',
            account_exists: 'An account already exists with that email address.',
            create_failed: 'We could not create your account. Please try again.',
            invalid_token: 'The confirmation link is invalid or expired.',
            token_expired: 'The confirmation link is invalid or expired.',
            pl_uc_role_blocked: 'Only administrators or editors can log in while the site is under construction.'
        };

        setMessage('error', messageMap[error] || (isSpanish ? 'Algo salió mal. Por favor, inténtalo de nuevo.' : 'Something went wrong. Please try again.'));
        overlay.classList.toggle('pl-auth-minimal-error', error === 'invalid_credentials');
    }

    if (autoOpen) {
        console.info('[pl-auth] auto open enabled', initialView);
        openModal(initialView === 'register' ? 'register' : 'login');
    } else {
        showView(initialView === 'register' ? 'register' : 'login');
    }

    applyNoticeError();
    hydrateAuthenticatedNav();
    console.info('[pl-auth] init complete');
}

// Initial attempt
console.info('[pl-auth] auth modal script loaded');
initPlAuth();

// DOMContentLoaded fallback
document.addEventListener('DOMContentLoaded', function () {
    console.info('[pl-auth] DOMContentLoaded fallback');
    initPlAuth();
});

// Export to window
window.initPlAuth = initPlAuth;
