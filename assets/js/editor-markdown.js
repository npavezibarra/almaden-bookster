// Compila texto de sintaxis markdown básica a HTML estructurado
function compileMarkdownToHTML(markdownText, appendFootnotes = false) {
    if (!markdownText) return '<p class="text-slate-400 italic">Comienza a escribir en el editor para ver tu contenido maquetado aquí...</p>';

    // Extraer definiciones de notas al pie
    const footnoteDefs = {};
    const footnoteRefs = [];
    const idMap = {};
    let refCounter = 1;

    // Limpiar definiciones del markdown y guardarlas
    let cleanMarkdown = markdownText.replace(/(?:^|\n)\[\^([^\]]+)\]:\s*([^\n]+)/g, (match, id, text) => {
        footnoteDefs[id] = text.trim();
        return '';
    });

    // Escapar etiquetas HTML para evitar rotura del DOM
    let html = cleanMarkdown
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt bridge;"); // Fix placeholder or actual escape

    // Restablecer el escape de las etiquetas controladas
    html = cleanMarkdown
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

    // Volver a habilitar etiquetas básicas controladas para visualización limpia
    html = html.replace(/&lt;u&gt;/g, "<u>").replace(/&lt;\/u&gt;/g, "</u>");

    // Convertir negritas: **texto**
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    // Convertir itálicas: *texto*
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

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
