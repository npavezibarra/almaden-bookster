function creditsEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function creditsOptionMarkup(options, selected) {
    return options.map((option) => (
        `<option value="${creditsEscapeHtml(option.value)}"${String(selected) === option.value ? ' selected' : ''}>${creditsEscapeHtml(option.label)}</option>`
    )).join('');
}

function creditsNormalizeLogoPosition(value) {
    const normalized = String(value || '').trim().toLowerCase();
    if (['left', 'center', 'right'].includes(normalized)) {
        return normalized;
    }
    return 'center';
}

function creditsNormalizeLogoSize(value) {
    const parsed = parseInt(value, 10);
    if (!Number.isFinite(parsed)) {
        return 120;
    }
    return Math.min(Math.max(parsed, 24), 400);
}

function creditsNormalizePublicationDate(value) {
    const raw = String(value || '').trim();
    if (!raw) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        return raw.slice(0, 7);
    }
    if (/^\d{4}-\d{2}$/.test(raw)) {
        return raw;
    }
    return raw;
}

function creditsLogoPositionJustify(value) {
    const normalized = creditsNormalizeLogoPosition(value);
    if (normalized === 'left') return 'flex-start';
    if (normalized === 'right') return 'flex-end';
    return 'center';
}

function creditsGetStorageKey() {
    const bookId = typeof bookState !== 'undefined' && bookState && bookState.bookId ? bookState.bookId : 'default';
    return `almaden_credits_config_${bookId}`;
}

function creditsGetAjaxEndpoint() {
    if (typeof bookState !== 'undefined' && bookState && bookState.ajaxUrl) {
        return bookState.ajaxUrl;
    }
    if (typeof ajaxurl !== 'undefined' && ajaxurl) {
        return ajaxurl;
    }
    return '';
}

