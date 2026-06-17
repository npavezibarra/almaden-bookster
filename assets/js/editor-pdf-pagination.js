// ============================================================
// MÓDULO: editor-pdf-pagination.js
// Responsabilidad: Función compleja de medición de DOM y 
// partición de párrafos para el salto de página exacto.
// ============================================================

// Heurística básica para partición de sílabas en español
window.isValidSpanishHyphenation = function(word, index) {
    if (index < 2 || index > word.length - 2) return false;
    
    // Evitar separar ch, ll, rr
    const pair = word.substring(index - 1, index + 1).toLowerCase();
    if (pair === 'ch' || pair === 'll' || pair === 'rr') return false;
    
    // Evitar separar grupos consonánticos inseparables
    const inseparableConsonants = ['pr','pl','br','bl','fr','fl','tr','dr','cr','cl','gr','gl'];
    if (inseparableConsonants.includes(pair)) return false;
    
    // Evitar dejar una sola vocal aislada
    const part1 = word.substring(0, index);
    const part2 = word.substring(index);
    const vowels = /[aeiouáéíóúüAEIOUÁÉÍÓÚÜ]/;
    if (!vowels.test(part1) || !vowels.test(part2)) return false;

    // Intentar cortar preferiblemente entre consonantes o vocal-consonante.
    // Evitar cortar C-V porque en español la consonante suele ir con la vocal siguiente (ej. ca-sa).
    const charBefore = word[index - 1].toLowerCase();
    const charAfter = word[index].toLowerCase();
    const isVowelBefore = vowels.test(charBefore);
    const isVowelAfter = vowels.test(charAfter);
    if (!isVowelBefore && isVowelAfter) return false;
    
    return true;
};

// Helper para dividir párrafos entre páginas
window.splitParagraphAcrossPages = function(pNode, innerContainer, footnotesHeight, maxTotalHeight) {
    if (!['P', 'UL', 'OL', 'DIV', 'BLOCKQUOTE'].includes(pNode.tagName)) return null;
    
    // Aplicar clase tempranamente para que el cálculo de altura ignore el margin-bottom
    // que será removido de todas formas al dividir el párrafo, evitando huecos al final.
    pNode.classList.add('split-paragraph-start');
    
    const originalChildNodes = Array.from(pNode.childNodes);
    pNode.innerHTML = '';
    
    const secondHalfNode = pNode.cloneNode(false);
    secondHalfNode.classList.remove('split-paragraph-start');
    
    let overflowed = false;

    function getEffectiveHeight() {
        return innerContainer.offsetHeight;
    }

    function processChild(child, target1, target2, currentLang = 'es') {
        if (overflowed) {
            target2.appendChild(child.cloneNode(true));
            return;
        }
        if (child.nodeType === Node.TEXT_NODE) {
            const words = child.textContent.split(/(\s+)/);
            let remainderText = '';
            const textNode1 = document.createTextNode('');
            target1.appendChild(textNode1);
            const useHyphenation = bookState.settings && parseInt(bookState.settings.content_hyphenation) === 1;

            for (let i = 0; i < words.length; i++) {
                if (overflowed) {
                    remainderText += words[i];
                } else {
                    const prevText = textNode1.data;
                    textNode1.data += words[i];
                    if (getEffectiveHeight() + footnotesHeight > maxTotalHeight) {
                        overflowed = true;
                        textNode1.data = prevText;
                        const word = words[i];

                        // Intentar división mediante prueba activa de altura (búsqueda lineal/binaria)
                        let splitSuccessfully = false;
                        const isSpanish = currentLang.toLowerCase().startsWith('es');
                        const isHyphenable = useHyphenation && isSpanish && word.trim().length >= 4 && !word.includes('-');
                        
                        if (isHyphenable && prevText.trim().length > 0) {
                            let bestBreak = -1;
                            
                            // Probamos cortes activos, desde la letra 2 hasta la penúltima
                            for (let c = 2; c < word.length - 1; c++) {
                                if (window.isValidSpanishHyphenation(word, c)) {
                                    textNode1.data = prevText + word.substring(0, c) + '-';
                                    if (getEffectiveHeight() + footnotesHeight <= maxTotalHeight) {
                                        bestBreak = c;
                                    } else {
                                        // Si ya excedimos la altura con este corte, cortes más largos también lo harán
                                        break;
                                    }
                                }
                            }
                            
                            if (bestBreak > 1) {
                                const part1 = word.substring(0, bestBreak);
                                const part2 = word.substring(bestBreak);
                                
                                const cleanRemainder = part2.replace(/[.,;:¡!¿?'"”"»]/g, '');
                                if (cleanRemainder.length >= 2) {
                                    textNode1.data = prevText + part1 + '-';
                                    remainderText += part2;
                                    splitSuccessfully = true;
                                }
                            }
                        }
                        
                        if (!splitSuccessfully) {
                            // No se pudo dividir ortográficamente o no cupo ni un pedazo: mover toda la palabra
                            // FIX: remover el espacio en blanco colgante al final de la línea
                            const trailingSpaceMatch = prevText.match(/\s+$/);
                            if (trailingSpaceMatch) {
                                textNode1.data = prevText.substring(0, prevText.length - trailingSpaceMatch[0].length);
                                remainderText += trailingSpaceMatch[0] + word;
                            } else {
                                textNode1.data = prevText;
                                remainderText += word;
                            }
                        }
                    }
                }
            }
            if (remainderText) target2.appendChild(document.createTextNode(remainderText));
        } else if (child.nodeType === Node.ELEMENT_NODE) {
            const elLang = child.getAttribute ? child.getAttribute('lang') : null;
            const newLang = elLang || currentLang;

            target1.appendChild(child);
            if (getEffectiveHeight() + footnotesHeight > maxTotalHeight) {
                target1.removeChild(child);
                
                // Never split LI across pages if we already have content on the current page (push whole LI to next page)
                if (child.tagName === 'LI' && target1.childNodes.length > 0) {
                    overflowed = true;
                    target2.appendChild(child.cloneNode(true));
                    return;
                }

                const part1 = child.cloneNode(false);
                const part2 = child.cloneNode(false);
                target1.appendChild(part1);
                target2.appendChild(part2);
                
                Array.from(child.childNodes).forEach(sub => processChild(sub, part1, part2, newLang));
                
                if (part1.childNodes.length === 0) {
                    target1.removeChild(part1);
                } else if (part2.childNodes.length > 0) {
                    // Both part1 and part2 have content: this child was split!
                    if (part2.classList) {
                        part2.classList.add('split-paragraph-continuation');
                    }
                }
                
                if (part2.childNodes.length === 0) target2.removeChild(part2);
            }
        }
    }

    const rootLang = pNode.getAttribute ? (pNode.getAttribute('lang') || 'es') : 'es';
    originalChildNodes.forEach(child => processChild(child, pNode, secondHalfNode, rootLang));
    if (pNode.textContent.trim() === '') return null;
    
    // Si la segunda mitad no tiene texto, significa que el párrafo completo cupo
    // perfectamente en la página actual (gracias a que le quitamos el margin-bottom).
    if (secondHalfNode.textContent.trim() === '') {
        // En este caso, no necesitamos que sea un "split-paragraph-start", ya que no se dividió.
        // Pero lo dejamos así o le devolvemos su estado. Mejor solo retornamos true.
        pNode.classList.remove('split-paragraph-start');
        return true; 
    }
    
    // --- FIX FOR HANGING HYPHENS AND INDENTATION ON SPLIT PARAGRAPHS ---
    pNode.classList.add('split-paragraph-start');
    secondHalfNode.classList.add('split-paragraph-continuation');
    
    // Find the deepmost element that was actually split so we only justify that one
    function markDeepmostSplitElement(node) {
        let lastElement = null;
        const inlineTags = ['EM', 'STRONG', 'SPAN', 'A', 'I', 'B', 'U', 'S', 'CODE', 'MARK'];
        
        for (let i = node.childNodes.length - 1; i >= 0; i--) {
            const child = node.childNodes[i];
            if (child.nodeType === Node.ELEMENT_NODE) {
                if (inlineTags.includes(child.tagName)) {
                    if (node.classList) node.classList.add('deep-split-start');
                    return;
                }
                lastElement = child;
                break;
            } else if (child.nodeType === Node.TEXT_NODE && child.textContent.trim() !== '') {
                node.classList.add('deep-split-start');
                return;
            }
        }
        if (lastElement) {
            markDeepmostSplitElement(lastElement);
        } else {
            if (node.classList) node.classList.add('deep-split-start');
        }
    }
    markDeepmostSplitElement(pNode);
    
    // FIX: Remove trailing space from the absolute last text node of the split paragraph.
    // When a node spans across inline elements (like <em>), the trailing space of the previous node
    // gets left behind, preventing text-align-last: justify from reaching the margin.
    function removeGlobalTrailingSpace(node) {
        for (let i = node.childNodes.length - 1; i >= 0; i--) {
            const child = node.childNodes[i];
            if (child.nodeType === Node.TEXT_NODE && child.textContent.length > 0) {
                const match = child.textContent.match(/\s+$/);
                if (match) {
                    child.textContent = child.textContent.substring(0, child.textContent.length - match[0].length);
                }
                if (child.textContent.trim() !== '') return true; // Stop searching once we found real text
            } else if (child.nodeType === Node.ELEMENT_NODE) {
                if (removeGlobalTrailingSpace(child)) return true;
            }
        }
        return false;
    }
    removeGlobalTrailingSpace(pNode);
    
    return secondHalfNode;
};
