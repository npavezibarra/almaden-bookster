function getSelectableStyles(analysis) {
    if (Array.isArray(analysis?.style_counts) && analysis.style_counts.length) {
        return analysis.style_counts;
    }
    if (Array.isArray(analysis?.separator_options) && analysis.separator_options.length) {
        return analysis.separator_options;
    }
    return [];
}

function getSeparatorOptions(analysis) {
    if (Array.isArray(analysis?.separator_options) && analysis.separator_options.length) {
        return analysis.separator_options;
    }
    return [];
}

function getDefaultStyleKey(options, preferredKeys) {
    const keys = Array.isArray(preferredKeys) ? preferredKeys : [preferredKeys];
    for (const preferred of keys) {
        const found = options.find((option) => option && option.key === preferred);
        if (found) return found.key;
    }
    return options.length ? options[0].key : '';
}

function getUniqueDefaultStyleKey(options, preferredKeys, usedKeys) {
    const keys = Array.isArray(preferredKeys) ? preferredKeys : [preferredKeys];
    const used = new Set(Array.isArray(usedKeys) ? usedKeys.filter(Boolean) : []);
    for (const preferred of keys) {
        const found = options.find((option) => option && option.key === preferred && !used.has(option.key));
        if (found) return found.key;
    }
    const fallback = options.find((option) => option && !used.has(option.key));
    return fallback ? fallback.key : '';
}

function validateMapping(analysis, mapping) {
    const errors = [];
    const warnings = [];
    const available = getSelectableStyles(analysis);
    const usage = new Map();
    const fields = ['title_style', 'subtitle_style', 'heading_1_style', 'heading_2_style', 'heading_3_style'];

    if (!String(mapping?.chapter_separator || '').trim()) {
        errors.push('Debes elegir un separador de capítulo.');
    }

    fields.forEach((fieldKey) => {
        const value = String(mapping?.[fieldKey] || '').trim();
        if (!value) {
            warnings.push(`No se asignó ${getFieldLabel(fieldKey)}.`);
            return;
        }

        if (!usage.has(value)) {
            usage.set(value, []);
        }
        usage.get(value).push(fieldKey);
    });

    usage.forEach((fieldsUsed, styleKey) => {
        if (fieldsUsed.length <= 1) return;
        warnings.push(`El estilo ${styleKey} está asignado a más de una función: ${fieldsUsed.map(getFieldLabel).join(', ')}.`);
    });

    const semanticCount = SEMANTIC_FIELDS.reduce((count, field) => count + (mapping?.[field.key] ? 1 : 0), 0);
    if (semanticCount < 2) {
        warnings.push('Solo hay un nivel interno asignado; la jerarquía quedará muy plana.');
    }
    if (available.length < 2) {
        warnings.push('Se detectaron muy pocos estilos de encabezado.');
    }

    return {
        errors,
        warnings,
        valid: errors.length === 0
    };
}

function buildDefaultMapping(analysis) {
    const selectable = getSelectableStyles(analysis);
    const separatorOptions = getSeparatorOptions(analysis);
    const used = [];
    const chapterSeparator = getDefaultStyleKey(separatorOptions, [
        analysis?.recommended_separator,
        'title',
        'heading-1',
        'heading-2',
        'heading-3',
        'subtitle'
    ]);
    if (chapterSeparator) used.push(chapterSeparator);

    const titleStyle = getUniqueDefaultStyleKey(selectable, ['title', 'heading-1', 'heading-2', 'heading-3'], used);
    if (titleStyle) used.push(titleStyle);

    const subtitleStyle = getUniqueDefaultStyleKey(selectable, ['subtitle', 'heading-2', 'heading-3'], used);
    if (subtitleStyle) used.push(subtitleStyle);

    const heading1Style = getUniqueDefaultStyleKey(selectable, ['heading-1', 'heading-2', 'heading-3'], used);
    if (heading1Style) used.push(heading1Style);

    const heading2Style = getUniqueDefaultStyleKey(selectable, ['heading-2', 'heading-3'], used);
    if (heading2Style) used.push(heading2Style);

    const heading3Style = getUniqueDefaultStyleKey(selectable, ['heading-3'], used);

    return {
        chapter_separator: chapterSeparator,
        title_style: titleStyle,
        subtitle_style: subtitleStyle,
        heading_1_style: heading1Style,
        heading_2_style: heading2Style,
        heading_3_style: heading3Style
    };
}

function getSemanticLevel(styleKey, mapping) {
    if (!styleKey || !mapping) return 0;
    if (mapping.chapter_separator && mapping.chapter_separator === styleKey) return 1;
    if (mapping.title_style && mapping.title_style === styleKey) return 1;
    if (mapping.heading_1_style && mapping.heading_1_style === styleKey) return 1;
    if (mapping.subtitle_style && mapping.subtitle_style === styleKey) return 2;
    if (mapping.heading_2_style && mapping.heading_2_style === styleKey) return 2;
    if (mapping.heading_3_style && mapping.heading_3_style === styleKey) return 3;
    return 0;
}

function buildChapterPreview(blocks, mapping) {
    if (!Array.isArray(blocks) || !blocks.length) {
        return [];
    }

    const chapters = [];
    let current = null;

    const finalize = () => {
        if (current && current.content.trim()) {
            current.content = current.content.trim();
            if (!current.title) {
                current.title = `Capítulo ${chapters.length + 1}`;
            }
            chapters.push(current);
        }
        current = null;
    };

    const ensureCurrent = () => {
        if (!current) {
            current = { title: '', content: '', blocks: 0, outline: [] };
        }
    };

    const appendBlock = (block) => {
        ensureCurrent();

        if (block.type === 'blank') {
            current.content = `${current.content.trim()}\n\n`;
            return;
        }

        if (block.type === 'heading') {
            const level = getSemanticLevel(block.style_key, mapping);
            if (level > 0) {
                const headingText = cleanHeadingText(block.text);
                current.content += `${'#'.repeat(level)} ${headingText}\n\n`;
                current.outline.push({
                    level,
                    label: block.style_label || block.style_key || 'Heading',
                    text: headingText
                });
                current.blocks++;
                return;
            }
        }

        current.content += `${block.text}\n\n`;
        current.outline.push({
            level: 0,
            label: block.style_label || 'Texto',
            text: block.text
        });
        current.blocks++;
    };

    blocks.forEach((block) => {
        const isSeparator = block.type === 'heading' && mapping && mapping.chapter_separator && block.style_key === mapping.chapter_separator;
        if (isSeparator) {
            const chapterTitle = cleanHeadingText(block.text);
            finalize();
            current = {
                title: chapterTitle || `Capítulo ${chapters.length + 1}`,
                content: `# ${chapterTitle}\n\n`,
                blocks: 1,
                outline: [{
                    level: 1,
                    label: 'Capítulo',
                    text: chapterTitle
                }]
            };
            return;
        }

        appendBlock(block);
    });

    finalize();
    return chapters;
}
