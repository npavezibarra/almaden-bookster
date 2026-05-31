// ============================================================
// MÓDULO: editor-pdf-pagination.js
// Responsabilidad: Función compleja de medición de DOM y 
// partición de párrafos para el salto de página exacto.
// ============================================================

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

    function processChild(child, target1, target2) {
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

                        // Intentar división usando la capacidad nativa del navegador para respetar sílabas
                        let wrappedIndex = -1;
                        const isHyphenable = useHyphenation && word.trim().length >= 4 && !word.includes('-');
                        
                        if (isHyphenable && prevText.trim().length > 0) {
                            // Restauramos word para que el navegador lo renderice y nos diga dónde lo cortó
                            textNode1.data = prevText + word;
                            const range = document.createRange();
                            
                            // 1. Ubicar la coordenada "Y" de la línea actual (usando el último caracter de prevText)
                            let baseTop = 0;
                            for (let k = prevText.length - 1; k >= 0; k--) {
                                if (prevText[k].trim().length > 0) {
                                    range.setStart(textNode1, k);
                                    range.setEnd(textNode1, k + 1);
                                    baseTop = range.getBoundingClientRect().top;
                                    break;
                                }
                            }
                            
                            // 2. Buscar en qué caracter de 'word' ocurre el salto de línea visual
                            if (baseTop > 0) {
                                const wordStart = prevText.length;
                                for (let c = 0; c < word.length; c++) {
                                    if (word[c].trim().length > 0) {
                                        range.setStart(textNode1, wordStart + c);
                                        range.setEnd(textNode1, wordStart + c + 1);
                                        const charRect = range.getBoundingClientRect();
                                        if (charRect.top > baseTop + 5) { // +5 tolerancia
                                            wrappedIndex = c;
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                        let splitSuccessfully = false;
                        
                        if (wrappedIndex > 1) { // Asegurar al menos 2 caracteres en la página actual
                            const part1 = word.substring(0, wrappedIndex);
                            const part2 = word.substring(wrappedIndex);
                            
                            const cleanRemainder = part2.replace(/[.,;:¡!¿?'"”"»]/g, '');
                            if (cleanRemainder.length >= 2) { // Al menos 2 caracteres en la sgte pág
                                // Aplicar la división: primera parte con guión normal en pág actual
                                textNode1.data = prevText + part1 + '-';
                                
                                // Verificar que el guión explícito no rompa la altura máxima
                                if (getEffectiveHeight() + footnotesHeight <= maxTotalHeight) {
                                    remainderText += part2;
                                    splitSuccessfully = true;
                                }
                            }
                        }
                        
                        if (!splitSuccessfully) {
                            // No se pudo dividir (o la regla de sílabas impidió hacerlo bien): mover toda la palabra
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
            target1.appendChild(child);
            if (getEffectiveHeight() + footnotesHeight > maxTotalHeight) {
                target1.removeChild(child);
                const part1 = child.cloneNode(false);
                const part2 = child.cloneNode(false);
                target1.appendChild(part1);
                target2.appendChild(part2);
                
                Array.from(child.childNodes).forEach(sub => processChild(sub, part1, part2));
                
                if (part1.childNodes.length === 0) target1.removeChild(part1);
                if (part2.childNodes.length === 0) target2.removeChild(part2);
            }
        }
    }

    originalChildNodes.forEach(child => processChild(child, pNode, secondHalfNode));
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
    
    return secondHalfNode;
};
