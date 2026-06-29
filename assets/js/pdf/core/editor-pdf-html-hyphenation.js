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
    return text.replace(/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:['’][A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*/g, (match) => {
        return almadenHyphenateSpanishWord(match, exceptionSet);
    });
}

window.almadenApplyHyphenationToHtml = function(html, settings) {
    const language = String(settings.content_language || 'es').toLowerCase();
    const hyphenationEnabled = !(settings.content_hyphenation === 0 || settings.content_hyphenation === '0');
    if (!hyphenationEnabled || !language.startsWith('es')) {
        return html;
    }

    const exceptionsRaw = String(settings.content_hyphenation_exceptions || '');
    const exceptionSet = new Set(
        exceptionsRaw
            .split(/[\n,;]+/)
            .map((word) => almadenNormalizeHyphenationKey(word))
            .filter(Boolean)
    );

    const container = document.createElement('div');
    container.innerHTML = html;

    const targets = container.querySelectorAll('p, li, blockquote, .chapter-subtitle, .credits-page-content p');
    targets.forEach((element) => {
        const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null);
        const textNodes = [];
        let node;
        while ((node = walker.nextNode())) {
            if (node.nodeValue && node.nodeValue.trim()) {
                textNodes.push(node);
            }
        }

        textNodes.forEach((textNode) => {
            const langCarrier = textNode.parentElement && textNode.parentElement.closest('[lang]');
            if (langCarrier) {
                const nodeLang = String(langCarrier.getAttribute('lang') || '').toLowerCase();
                if (nodeLang && !nodeLang.startsWith(language)) {
                    return;
                }
            }

            const original = textNode.nodeValue;
            const hyphenated = almadenApplyHyphenationToText(original, exceptionSet);
            if (hyphenated !== original) {
                textNode.nodeValue = hyphenated;
            }
        });
    });

    return container.innerHTML;
};
