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
        t = t.replace(/\[gap:([0-9]+(?:\.[0-9]+)?)\]/gi, (match, val) => {
            return processReplacement(`<hr class="almaden-gap" style="height: ${val}mm; border: none; margin: 0; padding: 0; clear: both; background: transparent;" />`);
        });

        return t;
    }
};
