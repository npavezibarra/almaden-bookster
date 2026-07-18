/**
 * Login success notice (toast)
 */
(function () {
    if (typeof plAuthLoginNotice === 'undefined' || !plAuthLoginNotice) {
        return;
    }

    try {
        var params = new URLSearchParams(window.location.search || '');
        if ((params.get('pl_auth_notice') || '') !== (plAuthLoginNotice.noticeKey || 'login_success')) {
            return;
        }
    } catch (e) {}

    var username = (plAuthLoginNotice.username || '').trim() || 'usuario';
    var isSpanish = !!plAuthLoginNotice.isSpanish;
    var text = isSpanish
        ? ('Hola ' + username + ', has ingresado a Almaden Bookster')
        : ('Hi ' + username + ', you are now signed in to Almaden Bookster');

    var root = document.createElement('div');
    root.className = 'pl-auth-login-notice';
    root.setAttribute('role', 'status');
    root.setAttribute('aria-live', 'polite');
    root.innerHTML = '<div class="pl-auth-login-notice__card"></div>';
    root.querySelector('.pl-auth-login-notice__card').textContent = text;
    document.body.appendChild(root);

    // Remove the notice query param so refresh doesn't show it again.
    try {
        var url = new URL(window.location.href);
        url.searchParams.delete('pl_auth_notice');
        window.history.replaceState({}, document.title, url.toString());
    } catch (e) {}

    window.setTimeout(function () {
        root.classList.add('is-visible');
    }, 20);

    window.setTimeout(function () {
        root.classList.add('is-hiding');
        window.setTimeout(function () {
            if (root && root.parentNode) root.parentNode.removeChild(root);
        }, 240);
    }, 2600);
})();
