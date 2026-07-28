// ============================================================
// MÓDULO: editor-pdf-language.js
// Responsabilidad: Resolver idioma base del libro y herencia
// por nodo para render PDF, EPUB y serialización.
// ============================================================

window.almadenNormalizeLanguageCode = function(language, fallback = 'es') {
    const safeFallback = String(fallback || 'es').trim().toLowerCase() || 'es';
    const safeLanguage = String(language || '').trim().toLowerCase();
    if (!safeLanguage) return safeFallback;
    if (/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/.test(safeLanguage)) {
        return safeLanguage;
    }
    return safeFallback;
};

window.almadenGetBookLanguage = function(settings, fallback = 'es') {
    const source = settings || {};
    return window.almadenNormalizeLanguageCode(
        source.book_language || source.content_language || '',
        fallback
    );
};

window.almadenIsSpanishLanguage = function(language) {
    return String(language || '').trim().toLowerCase().startsWith('es');
};

window.almadenGetEffectiveNodeLanguage = function(node, fallbackLanguage = 'es') {
    let current = node;
    while (current && current.nodeType === Node.ELEMENT_NODE) {
        const lang = current.getAttribute ? current.getAttribute('lang') : '';
        if (lang) {
            return window.almadenNormalizeLanguageCode(lang, fallbackLanguage);
        }
        current = current.parentElement;
    }
    return window.almadenNormalizeLanguageCode(fallbackLanguage, 'es');
};

window.almadenShouldUseCustomHyphenation = function(language) {
    return window.almadenIsSpanishLanguage(language);
};

window.almadenHyphenateTextByLanguage = function(text, language, exceptionSet) {
    if (window.almadenShouldUseCustomHyphenation(language) && typeof window.almadenApplyHyphenationToText === 'function') {
        return window.almadenApplyHyphenationToText(text, exceptionSet);
    }
    return text;
};
