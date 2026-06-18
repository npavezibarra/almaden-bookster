// Compila texto de sintaxis markdown básica a HTML estructurado
function compileMarkdownToHTML(markdownText, appendFootnotes = false) {
    if (!markdownText) return '<p class="text-slate-400 italic">Comienza a escribir en el editor para ver tu contenido maquetado aquí...</p>';

    // Extraer definiciones de notas al pie
    const footnoteDefs = {};
    const footnoteRefs = [];
    const idMap = {};
    let refCounter = 1;

    const parseInlineMarkdown = (text) => {
        let t = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");
            
        t = t.replace(/&lt;u&gt;/g, "<u>").replace(/&lt;\/u&gt;/g, "</u>");
        t = t.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        t = t.replace(/\*(.*?)\*/g, '<em>$1</em>');
        t = window.AlmadenShortcodes.parseInline(t);
        
        return t;
    };

    // Limpiar definiciones del markdown y guardarlas
    let cleanMarkdown = markdownText.replace(/(?:^|\n)\[\^([^\]]+)\]:\s*([^\n]+)/g, (match, id, text) => {
        footnoteDefs[id] = parseInlineMarkdown(text.trim());
        return '';
    });

    // Extraer tags <img> antes de escapar para preservarlos como HTML real
    const imgPlaceholders = {};
    let imgCounter = 0;
    cleanMarkdown = cleanMarkdown.replace(/<img\s[^>]*\/>/gi, (match) => {
        const key = `%%IMG_PLACEHOLDER_${imgCounter++}%%`;
        imgPlaceholders[key] = match;
        return key;
    });

    // Extraer bloques [html]...[/html] para permitir inyección de código puro sin escapar
    const rawHtmlPlaceholders = {};
    let rawHtmlCounter = 0;
    cleanMarkdown = cleanMarkdown.replace(/\[html\]([\s\S]*?)\[\/html\]/gi, (match, content) => {
        const key = `%%RAW_HTML_PLACEHOLDER_${rawHtmlCounter++}%%`;
        rawHtmlPlaceholders[key] = content;
        return key;
    });

    // Extraer shortcodes de maquetación antes de escapar el HTML
    const shortcodePlaceholders = {};
    let scCounter = 0;

    // Extraer shortcodes estructurales (box, columns, align, gap, pagebreak)
    cleanMarkdown = window.AlmadenShortcodes.parseStructural(cleanMarkdown, true, (html) => {
        const key = `%%SC_PLACEHOLDER_${scCounter++}%%`;
        shortcodePlaceholders[key] = html;
        return key;
    });

    // Aplicar parseo inline al cuerpo principal
    let html = parseInlineMarkdown(cleanMarkdown);

    // Reemplazar referencias inline de notas al pie
    html = html.replace(/\[\^([^\]]+)\]/g, (match, id) => {
        if (footnoteDefs[id] !== undefined) {
            if (!idMap[id]) {
                idMap[id] = refCounter++;
                footnoteRefs.push(id);
            }
            const index = idMap[id];
            return `<span class="pdf-footnote-ref font-semibold align-super text-[9px]" data-footnote-id="${id}" data-footnote-number="${index}"><sup>${index}</sup></span>`;
        }
        return match;
    });

    // Dividir por líneas para identificar bloques (títulos, listas, citas, párrafos)
    const lines = html.split('\n');
    let compiledBlocks = [];
    let inList = false;
    let listType = ''; // 'ul' o 'ol'

    for (let i = 0; i < lines.length; i++) {
        let line = lines[i].trim();

        // Manejo de títulos principales (# Título)
        if (line.startsWith('# ')) {
            if (inList) { compiledBlocks.push(`</${listType}>`); inList = false; }
            compiledBlocks.push(`<h1>${line.substring(2)}</h1>`);
        }
        // Manejo de subtítulos (## Subtítulo)
        else if (line.startsWith('## ')) {
            if (inList) { compiledBlocks.push(`</${listType}>`); inList = false; }
            compiledBlocks.push(`<h2>${line.substring(3)}</h2>`);
        }
        // Manejo de citas (> Cita)
        else if (line.startsWith('&gt; ')) {
            if (inList) { compiledBlocks.push(`</${listType}>`); inList = false; }
            compiledBlocks.push(`<blockquote>${line.substring(5)}</blockquote>`);
        }
        // Manejo de listas desordenadas (- Elemento)
        else if (line.startsWith('- ')) {
            if (!inList || listType !== 'ul') {
                if (inList) compiledBlocks.push(`</${listType}>`);
                compiledBlocks.push('<ul>');
                inList = true;
                listType = 'ul';
            }
            compiledBlocks.push(`<li>${line.substring(2)}</li>`);
        }
        // Manejo de listas ordenadas (1. Elemento)
        else if (/^\d+\.\s/.test(line)) {
            const match = line.match(/^\d+\.\s/);
            if (!inList || listType !== 'ol') {
                if (inList) compiledBlocks.push(`</${listType}>`);
                compiledBlocks.push('<ol>');
                inList = true;
                listType = 'ol';
            }
            compiledBlocks.push(`<li>${line.substring(match[0].length)}</li>`);
        }
        // Espacio en blanco (Separador de bloques)
        else if (line === '') {
            if (inList) {
                compiledBlocks.push(`</${listType}>`);
                inList = false;
            }
        }
        // Imagen (placeholder %%IMG_PLACEHOLDER_N%%)
        else if (/^%%IMG_PLACEHOLDER_\d+%%$/.test(line)) {
            if (inList) { compiledBlocks.push(`</${listType}>`); inList = false; }
            compiledBlocks.push(line); // se restaurará con el img real después
        }
        // Shortcodes estructurales (placeholder %%SC_PLACEHOLDER_N%%)
        else if (/^%%SC_PLACEHOLDER_\d+%%$/.test(line)) {
            if (inList) { compiledBlocks.push(`</${listType}>`); inList = false; }
            compiledBlocks.push(line); // se restaurará con el div real después
        }
        // Párrafos convencionales de libro
        else {
            if (inList) {
                compiledBlocks.push(`</${listType}>`);
                inList = false;
            }
            compiledBlocks.push(`<p>${line}</p>`);
        }
    }

    if (inList) {
        compiledBlocks.push(`</${listType}>`);
    }

    // Restaurar placeholders de imágenes como bloques HTML reales
    let result = compiledBlocks.join('\n');
    for (const [key, rawHtml] of Object.entries(rawHtmlPlaceholders)) {
        // Des-envolver el placeholder de la etiqueta <p> si se generó
        result = result.replace(new RegExp(`<p>\\s*${key}\\s*<\\/p>`, 'g'), key);
        result = result.replace(key, rawHtml);
    }
    for (const [key, imgTag] of Object.entries(imgPlaceholders)) {
        result = result.replace(key, imgTag);
    }
    // Restaurar placeholders de shortcodes
    for (const [key, divTag] of Object.entries(shortcodePlaceholders)) {
        // Evitar que los shortcodes (como [box], [align]) queden envueltos en <p>
        result = result.replace(new RegExp(`<p>\\s*${key}\\s*<\\/p>`, 'g'), key);
        result = result.replace(key, divTag);
    }
    compiledBlocks = result.split('\n');

    // Agregar sección de notas al pie al final si es necesario (ej. para exportación HTML)
    if (appendFootnotes && footnoteRefs.length > 0) {
        compiledBlocks.push('<div class="footnotes-section" style="margin-top: 40px; border-top: 1px solid #cbd5e1; padding-top: 20px;">');
        compiledBlocks.push('<h3 style="font-size: 1.1em; margin-bottom: 10px; font-weight: bold; color: #1e293b;">Notas al pie</h3>');
        compiledBlocks.push('<ol style="font-size: 0.9em; padding-left: 20px; line-height: 1.5; color: #475569;">');
        footnoteRefs.forEach(id => {
            compiledBlocks.push(`<li id="fn-${id}" style="margin-bottom: 6px;">${footnoteDefs[id]}</li>`);
        });
        compiledBlocks.push('</ol>');
        compiledBlocks.push('</div>');
    }

    return compiledBlocks.join('\n');
}
