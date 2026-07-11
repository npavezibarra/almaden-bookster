// ============================================================
// MÓDULO: editor-pdf-html-hyphenation.js
// Responsabilidad: Silabación y guionado en español (Hyphenation)
//                  y etiquetado de ediciones.
// ============================================================

window.ALMADEN_SOFT_HYPHEN = '\u00AD';

window.almadenGetSpanishEditionLabel = function(editionValue) {
    const editionNumber = parseInt(String(editionValue || '').trim(), 10);
    if (!Number.isFinite(editionNumber) || editionNumber <= 0) {
        return '';
    }

    const unitWords = {
        1: 'Primera',
        2: 'Segunda',
        3: 'Tercera',
        4: 'Cuarta',
        5: 'Quinta',
        6: 'Sexta',
        7: 'Séptima',
        8: 'Octava',
        9: 'Novena'
    };

    if (unitWords[editionNumber]) {
        return `${unitWords[editionNumber]} Edición`;
    }

    const tensWords = {
        10: 'Décima',
        20: 'Vigésima',
        30: 'Trigésima',
        40: 'Cuadragésima',
        50: 'Quincuagésima',
        60: 'Sexagésima',
        70: 'Septuagésima',
        80: 'Octogésima',
        90: 'Nonagésima',
        100: 'Centésima'
    };

    if (tensWords[editionNumber]) {
        return `${tensWords[editionNumber]} Edición`;
    }

    if (editionNumber > 10 && editionNumber < 20) {
        const teenWords = {
            11: 'Undécima',
            12: 'Duodécima',
            13: 'Decimotercera',
            14: 'Decimocuarta',
            15: 'Decimoquinta',
            16: 'Decimosexta',
            17: 'Decimoséptima',
            18: 'Decimoctava',
            19: 'Decimonovena'
        };

        return `${teenWords[editionNumber] || `Décima ${editionNumber - 10}a`} Edición`;
    }

    const tens = Math.floor(editionNumber / 10) * 10;
    const units = editionNumber % 10;
    if (tensWords[tens] && unitWords[units]) {
        return `${tensWords[tens]} ${unitWords[units].toLowerCase()} Edición`;
    }

    return `Edición ${editionNumber}`;
};

function almadenNormalizeHyphenationKey(word) {
    return String(word || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}

function almadenIsSpanishVowel(ch) {
    return /[aeiouáéíóúü]/i.test(ch);
}

function almadenIsStrongVowel(ch) {
    return /[aáeéoó]/i.test(ch);
}

function almadenHasAccent(ch) {
    return /[áéíóú]/i.test(ch);
}

function almadenIsWeakVowel(ch) {
    return /[iíuúü]/i.test(ch);
}

function almadenIsFrontVowel(ch) {
    return /[eiéií]/i.test(ch);
}

function almadenAllowedSpanishOnset(cluster) {
    const normalized = String(cluster || '').toLowerCase();
    return [
        'bl', 'br', 'cl', 'cr', 'dr', 'fl', 'fr', 'gl', 'gr', 'pl', 'pr', 'tr', 'tl'
    ].includes(normalized);
}

function almadenTokenizeSpanishWord(word) {
    const chars = Array.from(String(word || '').normalize('NFC'));
    const tokens = [];

    for (let i = 0; i < chars.length; i++) {
        const ch = chars[i];
        const lower = ch.toLowerCase();
        const next = chars[i + 1] || '';
        const nextLower = next.toLowerCase();
        const next2 = chars[i + 2] || '';

        if (lower === 'c' && nextLower === 'h') {
            tokens.push({ text: ch + next, type: 'cons' });
            i++;
            continue;
        }

        if (lower === 'l' && nextLower === 'l') {
            tokens.push({ text: ch + next, type: 'cons' });
            i++;
            continue;
        }

        if (lower === 'r' && nextLower === 'r') {
            tokens.push({ text: ch + next, type: 'cons' });
            i++;
            continue;
        }

        if (lower === 'q' && nextLower === 'u' && almadenIsFrontVowel(next2)) {
            tokens.push({ text: ch + next, type: 'cons' });
            i++;
            continue;
        }

        if (lower === 'g' && nextLower === 'u' && almadenIsFrontVowel(next2) && next !== 'ü') {
            tokens.push({ text: ch + next, type: 'cons' });
            i++;
            continue;
        }

        tokens.push({
            text: ch,
            type: almadenIsSpanishVowel(ch) ? 'vowel' : 'cons'
        });
    }

    return tokens;
}

function almadenIsSpanishDiphthong(left, right) {
    if (!left || !right) return false;
    const leftVowel = left.text;
    const rightVowel = right.text;

    if (!almadenIsSpanishVowel(leftVowel) || !almadenIsSpanishVowel(rightVowel)) return false;
    if (almadenIsStrongVowel(leftVowel) && almadenIsStrongVowel(rightVowel)) return false;
    if (almadenHasAccent(leftVowel) && almadenIsWeakVowel(leftVowel)) return false;
    if (almadenHasAccent(rightVowel) && almadenIsWeakVowel(rightVowel)) return false;
    if (almadenIsWeakVowel(leftVowel) && almadenIsWeakVowel(rightVowel)) {
        return !(almadenHasAccent(leftVowel) || almadenHasAccent(rightVowel));
    }

    return true;
}

function almadenIsSpanishTriphthong(a, b, c) {
    if (!a || !b || !c) return false;
    if (!almadenIsWeakVowel(a.text) || !almadenIsStrongVowel(b.text) || !almadenIsWeakVowel(c.text)) return false;
    if (almadenHasAccent(a.text) || almadenHasAccent(b.text) || almadenHasAccent(c.text)) return false;
    return true;
}

function almadenSplitSpanishWordIntoSyllables(word) {
    const tokens = almadenTokenizeSpanishWord(word);
    if (tokens.length < 2) {
        return [word];
    }

    const vowelGroups = [];
    let i = 0;

    while (i < tokens.length) {
        if (tokens[i].type !== 'vowel') {
            i++;
            continue;
        }

        const start = i;
        let end = i + 1;
        const next = tokens[end];
        const next2 = tokens[end + 1];

        if (next && next.type === 'vowel') {
            if (next2 && next2.type === 'vowel' && almadenIsSpanishTriphthong(tokens[start], next, next2)) {
                end = i + 3;
            } else if (almadenIsSpanishDiphthong(tokens[start], next)) {
                end = i + 2;
            }
        }

        vowelGroups.push({ start, end });
        i = end;
    }

    if (vowelGroups.length <= 1) {
        return [word];
    }

    const boundaries = [];
    let currentStart = 0;

    for (let g = 0; g < vowelGroups.length - 1; g++) {
        const current = vowelGroups[g];
        const next = vowelGroups[g + 1];
        const clusterStart = current.end;
        const clusterEnd = next.start;
        const cluster = tokens.slice(clusterStart, clusterEnd);
        let breakAt = clusterStart;

        if (cluster.length === 1) {
            breakAt = clusterStart;
        } else if (cluster.length === 2) {
            const clusterText = cluster.map((t) => t.text).join('').toLowerCase();
            breakAt = almadenAllowedSpanishOnset(clusterText) ? clusterStart : clusterStart + 1;
        } else if (cluster.length >= 3) {
            const lastTwoText = cluster.slice(-2).map((t) => t.text).join('').toLowerCase();
            breakAt = almadenAllowedSpanishOnset(lastTwoText) ? (clusterEnd - 2) : (clusterEnd - 1);
        } else {
            breakAt = current.end;
        }

        if (breakAt > currentStart) {
            boundaries.push(breakAt);
            currentStart = breakAt;
        }
    }

    const syllables = [];
    let startIndex = 0;

    boundaries.forEach((boundary) => {
        syllables.push(tokens.slice(startIndex, boundary).map((t) => t.text).join(''));
        startIndex = boundary;
    });

    syllables.push(tokens.slice(startIndex).map((t) => t.text).join(''));

    return syllables.filter(Boolean);
}

function almadenIsSingleSpanishVowelSyllable(syllable) {
    return String(syllable || '').length === 1 && almadenIsSpanishVowel(syllable);
}

function almadenHyphenateSpanishWord(word, exceptionSet) {
    const normalized = String(word || '').normalize('NFC');
    const exceptionKey = almadenNormalizeHyphenationKey(normalized);
    if (!normalized || normalized.length < 6 || !/[aeiouáéíóúü]/i.test(normalized) || exceptionSet.has(exceptionKey)) {
        return normalized;
    }

    const syllables = almadenSplitSpanishWordIntoSyllables(normalized);
    if (syllables.length < 2) {
        return normalized;
    }

    const parts = [];
    for (let i = 0; i < syllables.length; i++) {
        const syllable = syllables[i];
        parts.push(syllable);

        const nextSyllable = syllables[i + 1];
        if (!nextSyllable) {
            continue;
        }

        // Never leave a single vowel dangling at the end of a line.
        if (almadenIsSingleSpanishVowelSyllable(syllable)) {
            continue;
        }

        parts.push(window.ALMADEN_SOFT_HYPHEN);
    }

    return parts.join('');
}

function almadenApplyHyphenationToText(text, exceptionSet) {
    if (!text) return text;
    const cleanText = String(text).replace(/\u00AD/g, '');
    return cleanText.replace(/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:['’][A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*/g, (match) => {
        return almadenHyphenateSpanishWord(match, exceptionSet);
    });
}

window.almadenApplyHyphenationToHtml = function(html, settings) {
    if (!html || !settings || settings.content_hyphenation != 1) {
        return html;
    }

    const defaultLanguage = String(settings.content_language || 'es').trim().toLowerCase();
    if (!defaultLanguage.startsWith('es')) {
        return html;
    }

    if (typeof document === 'undefined') {
        return html;
    }

    const exceptionSet = new Set(
        String(settings.content_hyphenation_exceptions || '')
            .split(/[,;\n]/g)
            .map((item) => almadenNormalizeHyphenationKey(item))
            .filter(Boolean)
    );

    const template = document.createElement('template');
    template.innerHTML = html;

    const skipSelector = [
        'script',
        'style',
        'noscript',
        'code',
        'pre',
        'kbd',
        'samp',
        'svg',
        'math',
        'textarea',
        'input',
        'select',
        'option',
        '.chapter-prefix-text',
        '.chapter-prefix-number',
        '.chapter-main-title',
        '.chapter-subtitle',
        '.toc-item',
        '.toc-title',
        '.toc-page',
        '[data-footnote-id]',
        '[data-footnote-call]'
    ].join(',');

    const walker = document.createTreeWalker(
        template.content,
        NodeFilter.SHOW_TEXT,
        {
            acceptNode(node) {
                if (!node || !node.nodeValue || !node.nodeValue.trim()) {
                    return NodeFilter.FILTER_REJECT;
                }

                const parent = node.parentElement;
                if (!parent || parent.closest(skipSelector)) {
                    return NodeFilter.FILTER_REJECT;
                }

                const nearestLangHost = parent.closest('[lang]');
                const effectiveLang = nearestLangHost
                    ? String(nearestLangHost.getAttribute('lang') || '').trim().toLowerCase()
                    : defaultLanguage;

                if (!effectiveLang.startsWith('es')) {
                    return NodeFilter.FILTER_REJECT;
                }

                return NodeFilter.FILTER_ACCEPT;
            }
        }
    );

    const textNodes = [];
    while (walker.nextNode()) {
        textNodes.push(walker.currentNode);
    }

    textNodes.forEach((node) => {
        node.nodeValue = almadenApplyHyphenationToText(node.nodeValue, exceptionSet);
    });

    return template.innerHTML;
};
