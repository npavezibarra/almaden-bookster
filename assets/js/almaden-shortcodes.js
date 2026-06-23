// assets/js/almaden-shortcodes.js

window.AlmadenShortcodes = {

    /**
     * Parse inline shortcodes like [lang], [size], [font]
     * These can be used inside paragraphs or headings.
     */
    parseInline: function(text) {
        let t = text;

        // [lang:es] ... [/lang]
        t = t.replace(/\[lang:([a-zA-Z]{2})\]([\s\S]*?)\[\/lang\]/gi, '<span lang="$1"><em>$2</em></span>');
        
        // [size=12px] ... [/size]
        t = t.replace(/\[size=([0-9]+(?:\.[0-9]+)?)(px|pt|em|rem)?\]([\s\S]*?)\[\/size\]/gi, (match, val, unit, content) => {
            const u = unit || 'px';
            return `<span style="font-size: ${val}${u};">${content}</span>`;
        });
        
        // [font="..."] ... [/font] (Handles single/double quotes and HTML entities)
        t = t.replace(/\[font=(?:&quot;|&#039;|"|')([^\]]+?)(?:&quot;|&#039;|"|')\]([\s\S]*?)\[\/font\]/gi, (match, fontName, content) => {
            return `<span style="font-family: '${fontName}', serif;">${content}</span>`;
        });

        return t;
    },

    /**
     * Parse structural block-level shortcodes like [box], [align], [columns], [gap], [page_break].
     * @param {string} text The markdown text to parse
     * @param {boolean} usePlaceholders If true, replaces HTML with a placeholder key (useful for PDF markdown compiler)
     * @param {function} placeholderHandler Callback function(htmlString) that returns the placeholder key. Required if usePlaceholders=true.
     */
    parseStructural: function(text, usePlaceholders = false, placeholderHandler = null) {
        let t = text;

        const processReplacement = (html) => {
            if (usePlaceholders && typeof placeholderHandler === 'function') {
                return placeholderHandler(html);
            }
            return html;
        };

        // [box style="..."] ... [/box]
        t = t.replace(/\[box\s*([^\]]*)\]([\s\S]*?)\[\/box\]/gi, (match, attrs, content) => {
            return processReplacement(`<div class="almaden-box" ${attrs}>`) + content + processReplacement(`</div>`);
        });

        // [columns style="..."] ... [/columns]
        t = t.replace(/\[columns\s*([^\]]*)\]([\s\S]*?)\[\/columns\]/gi, (match, attrs, content) => {
            return processReplacement(`<div class="almaden-columns" style="display:flex; gap: 1rem; width: 100%;" ${attrs}>`) + content + processReplacement(`</div>`);
        });

        // [col style="..."] ... [/col]
        t = t.replace(/\[col\s*([^\]]*)\]([\s\S]*?)\[\/col\]/gi, (match, attrs, content) => {
            return processReplacement(`<div class="almaden-col" style="flex:1;" ${attrs}>`) + content + processReplacement(`</div>`);
        });

        // [align=center] ... [/align]
        t = t.replace(/\[align=([a-zA-Z]+)\]([\s\S]*?)\[\/align\]/gi, (match, align, content) => {
            return processReplacement(`<div class="almaden-align-${align}" style="text-align: ${align};">`) + content + processReplacement(`</div>`);
        });

        // [page_break] o [pagebreak]
        t = t.replace(/\[page[-_]?break\]/gi, () => {
            return processReplacement(`<div class="pdf-page-break"></div>`);
        });

        // [gap:X]
        t = t.replace(/\[gap:\s*([0-9]+(?:\.[0-9]+)?)(px|mm|cm|pt|em|rem|in)?\s*\]/gi, (match, val, unit) => {
            const u = unit || 'mm';
            return processReplacement(`<div class="almaden-gap" style="display: block; width: 100%; height: ${val}${u}; border: none; margin: 0; padding: 0; clear: both; background: transparent; color: transparent; pointer-events: none;" aria-hidden="true"></div>`);
        });

        // [book_logo] o [book-logo] o [logo]
        t = t.replace(/\[(?:book[-_]?)?logo\s*([^\]]*)\]/gi, (match, attrs) => {
            let width = '40mm'; // Default fallback
            let align = 'center'; // Default fallback
            if (attrs) {
                const wMatch = attrs.match(/width=(?:"|')?([0-9]+(?:\.[0-9]+)?)(mm|px|pt|em|rem|cm|in)?(?:"|')?/i);
                if (wMatch) {
                    const val = wMatch[1];
                    const unit = wMatch[2] || 'mm';
                    width = `${val}${unit}`;
                }
                const aMatch = attrs.match(/align=(?:"|')?(left|center|right)(?:"|')?/i);
                if (aMatch) {
                    align = aMatch[1].toLowerCase();
                }
            }
            return processReplacement(window.AlmadenShortcodes.generateBookLogoHtml(width, align));
        });

        return t;
    },

    generateBookLogoHtml: function(targetWidth = '40mm', align = 'center') {
        let coverSettings = {};
        try {
            const state = (typeof bookState !== 'undefined') ? bookState : (window.bookState || null);
            if (state && state.coverSettings) {
                coverSettings = state.coverSettings;
                if (typeof coverSettings === 'string' && coverSettings.trim() !== '') {
                    coverSettings = JSON.parse(coverSettings);
                }
            }
        } catch (e) {
            console.error("Error parsing coverSettings:", e);
        }
        if (!coverSettings || typeof coverSettings !== 'object') {
            coverSettings = {};
        }
        
        let textLayers = coverSettings.text_layers || [];
        try {
            if (typeof textLayers === 'string' && textLayers.trim() !== '') {
                textLayers = JSON.parse(textLayers);
            }
        } catch (e) {
            console.error("Error parsing text_layers:", e);
        }
        if (!Array.isArray(textLayers)) {
            textLayers = [];
        }

        if (textLayers.length === 0) return '';

        // Buscar el grupo marcado como logo
        const logoGroup = textLayers.find(l => l && l.type === 'group' && (l.isBookLogo === true || l.isBookLogo === 'true'));
        


        if (!logoGroup) return '';

        // Obtener hijos del grupo
        const children = textLayers.filter(l => l && l.parentId && String(l.parentId) === String(logoGroup.id));
        
        if (children.length === 0) return '';

        // Calcular dimensiones absolutas del canvas en píxeles (basado en la maquetación del cover spread)
        const pxPerCm = 96 / 2.54; // ~37.795
        const state = (typeof bookState !== 'undefined') ? bookState : (window.bookState || {});
        const bookSettings = state.settings || {};
        const pageWidthCm = parseFloat(bookSettings.page_width) || 14;
        const pageHeightCm = parseFloat(bookSettings.page_height) || 21;
        const pageWidthPx = pageWidthCm * pxPerCm;
        const pageHeightPx = pageHeightCm * pxPerCm;
        
        const BLEED_MM = 5;
        const bleedPx = (BLEED_MM / 10) * pxPerCm;
        const actualHeightPx = pageHeightPx + (2 * bleedPx);

        const thicknessMmPerPage = parseFloat(coverSettings.paper_type) || 0.06;
        const pagesEl = document.getElementById('total-pages-sidebar');
        let pages = pagesEl ? parseInt(pagesEl.textContent, 10) : 100;
        if (isNaN(pages) || pages < 20) pages = 100;
        
        const spineWidthMm = thicknessMmPerPage * pages;
        const spineWidthPx = (spineWidthMm / 10) * pxPerCm;

        const frontFlapMm = parseFloat(coverSettings.front_flap_width) || 0;
        const backFlapMm = parseFloat(coverSettings.back_flap_width) || 0;
        
        const frontFlapPx = frontFlapMm > 0 ? ((frontFlapMm / 10) * pxPerCm) + bleedPx : 0;
        const backFlapPx = backFlapMm > 0 ? ((backFlapMm / 10) * pxPerCm) + bleedPx : 0;
        
        const frontCoverPx = pageWidthPx + (frontFlapMm > 0 ? 0 : bleedPx);
        const backCoverPx = pageWidthPx + (backFlapMm > 0 ? 0 : bleedPx);
        
        const totalSpreadWidth = frontCoverPx + backCoverPx + spineWidthPx + frontFlapPx + backFlapPx;

        // Calcular cajas contenedoras absolutas del lienzo en píxeles para cada hijo
        const calculatedChildren = [];
        children.forEach(c => {
            if (!c) return;
            const left = ((parseFloat(c.x) || 0) / 100) * totalSpreadWidth;
            const top = ((parseFloat(c.y) || 0) / 100) * actualHeightPx;
            
            const childW = parseFloat(c.width) || (c.type === 'image' ? 200 : (c.type === 'shape' ? 150 : (c.text ? c.text.length * (parseFloat(c.fontSize) || 24) * 0.55 : 100)));
            const childH = parseFloat(c.height) || (c.type === 'image' ? 200 : (c.type === 'shape' ? 150 : (parseFloat(c.fontSize) || 24) * 1.2));
            
            calculatedChildren.push({
                ...c,
                left,
                top,
                widthPx: childW,
                heightPx: childH
            });
        });

        if (calculatedChildren.length === 0) return '';

        const leftCoords = calculatedChildren.map(c => c.left);
        const rightCoords = calculatedChildren.map(c => c.left + c.widthPx);
        const topCoords = calculatedChildren.map(c => c.top);
        const bottomCoords = calculatedChildren.map(c => c.top + c.heightPx);

        const minX = Math.min(...leftCoords);
        const maxX = Math.max(...rightCoords);
        const minY = Math.min(...topCoords);
        const maxY = Math.max(...bottomCoords);

        const groupW = (maxX - minX) || 10;
        const groupH = (maxY - minY) || 10;
        const aspectRatio = groupW / groupH;

        // Formatear dimensiones y unidades
        let targetWidthVal = parseFloat(targetWidth) || 40;
        let unit = 'mm';
        const unitMatch = String(targetWidth).match(/[a-zA-Z]+/);
        if (unitMatch) {
            unit = unitMatch[0];
        }

        const targetHeightVal = targetWidthVal / aspectRatio;
        const targetHeight = `${targetHeightVal}${unit}`;

        // Convertir el ancho deseado a pixeles para sacar el ratio transform: scale
        let targetWidthPx = targetWidthVal * 3.779527; // mm por defecto
        if (unit === 'px') targetWidthPx = targetWidthVal;
        else if (unit === 'cm') targetWidthPx = targetWidthVal * 37.79527;
        else if (unit === 'pt') targetWidthPx = targetWidthVal * 1.333333;
        else if (unit === 'in') targetWidthPx = targetWidthVal * 96;

        const scale = targetWidthPx / groupW;

        let marginStyle = '15px auto';
        if (align === 'left') {
            marginStyle = '15px auto 15px 0';
        } else if (align === 'right') {
            marginStyle = '15px 0 15px auto';
        }

        // Generar contenedor
        let html = `<div class="almaden-book-logo-block" style="position: relative; width: ${targetWidthVal}${unit}; height: ${targetHeight}; overflow: visible; display: block; margin: ${marginStyle}; clear: both;">`;
        html += `<div style="position: absolute; left: 0; top: 0; width: ${groupW}px; height: ${groupH}px; transform: scale(${scale}); transform-origin: top left; overflow: visible;">`;

        calculatedChildren.forEach(child => {
            const relLeft = child.left - minX;
            const relTop = child.top - minY;
            
            let childStyle = `position: absolute; left: ${relLeft}px; top: ${relTop}px; transform: rotate(${child.rotation || 0}deg); transform-origin: top left; white-space: nowrap; line-height: 1.1;`;
            
            if (child.type === 'image') {
                html += `<img src="${child.url}" style="${childStyle} width: ${child.widthPx}px; height: ${child.heightPx}px; object-fit: contain;" alt="Logo Part" />`;
            } else if (child.type === 'shape') {
                const borderRadius = child.shapeType === 'circle' ? '50%' : '0';
                const opacity = (child.opacity !== undefined ? parseFloat(child.opacity) : 100) / 100;
                
                let bgStyle = '';
                if (child.isGradient) {
                    const c1 = child.color1 || '#000000';
                    const c2 = child.color2 || '#ffffff';
                    bgStyle = `background: linear-gradient(${child.gradientAngle || 90}deg, ${c1}, ${c2});`;
                } else {
                    bgStyle = `background: ${child.color1 || '#cccccc'};`;
                }
                
                html += `<div style="${childStyle} width: ${child.widthPx}px; height: ${child.heightPx}px; border-radius: ${borderRadius}; opacity: ${opacity}; ${bgStyle}"></div>`;
            } else {
                // Texto
                const fontSize = parseFloat(child.fontSize) || 24;
                let color = child.color || '#000000';
                
                // Si el color es blanco o muy claro, cambiar a negro/gris oscuro para que sea visible en páginas internas blancas
                const normColor = color.trim().toLowerCase();
                if (normColor === '#ffffff' || normColor === '#fff' || normColor === 'rgb(255,255,255)' || normColor === 'rgb(255, 255, 255)') {
                    color = '#000000';
                } else {
                    const hex = color.replace('#', '').trim();
                    if (hex.length === 6) {
                        const r = parseInt(hex.substring(0, 2), 16);
                        const g = parseInt(hex.substring(2, 4), 16);
                        const b = parseInt(hex.substring(4, 6), 16);
                        const luma = 0.2126 * r + 0.7152 * g + 0.0722 * b;
                        if (luma > 220) {
                            color = '#000000'; // Forzar a negro si es demasiado claro
                        }
                    } else if (hex.length === 3) {
                        const r = parseInt(hex[0] + hex[0], 16);
                        const g = parseInt(hex[1] + hex[1], 16);
                        const b = parseInt(hex[2] + hex[2], 16);
                        const luma = 0.2126 * r + 0.7152 * g + 0.0722 * b;
                        if (luma > 220) {
                            color = '#000000';
                        }
                    }
                }

                const fontFamily = child.fontFamily || 'Inter';
                const textAlign = child.textAlign || 'left';
                
                html += `<div style="${childStyle} font-family: '${fontFamily}', serif; font-size: ${fontSize}px; color: ${color}; text-align: ${textAlign};">${child.text}</div>`;
            }
        });

        html += `</div></div>`;
        return html;
    }
};
