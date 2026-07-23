function setModalVisible(visible) {
    const modal = docImportEl('document-import-modal');
    if (!modal) return;
    modal.classList.toggle('hidden', !visible);
    modal.classList.toggle('flex', visible);
    modal.classList.toggle('opacity-100', visible);
    modal.classList.toggle('opacity-0', !visible);
    setTimeout(() => {
        const panel = modal.firstElementChild;
        if (panel) {
            panel.classList.toggle('opacity-100', visible);
            panel.classList.toggle('opacity-0', !visible);
            panel.classList.toggle('scale-100', visible);
            panel.classList.toggle('scale-95', !visible);
        }
    }, 10);
}

function renderValidationSection(analysis, mapping) {
    const section = docImportEl('document-import-validation-section');
    const list = docImportEl('document-import-validation-list');
    const icon = docImportEl('document-import-validation-icon');
    const title = docImportEl('document-import-validation-title');
    if (!section || !list || !icon || !title) return null;

    const validation = validateMapping(analysis, mapping);
    documentImportState.validation = validation;
    list.innerHTML = '';

    if (!validation.errors.length && !validation.warnings.length) {
        section.className = 'hidden rounded-2xl border border-[var(--border-color)] bg-[var(--bg-app)] p-5 border-emerald-200 bg-emerald-50';
        section.classList.remove('hidden');
        icon.className = 'mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-emerald-700';
        icon.innerHTML = '<i class="fa-solid fa-circle-check"></i>';
        title.textContent = 'Estructura lista para importar';
        list.innerHTML = '<p class="text-sm text-emerald-800">No se detectaron conflictos en la asignación de estilos.</p>';
        return validation;
    }

    section.classList.remove('hidden');
    if (validation.errors.length) {
        section.classList.remove('border-amber-200', 'bg-amber-50', 'border-emerald-200', 'bg-emerald-50');
        section.classList.add('border-rose-200', 'bg-rose-50');
        icon.className = 'mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 text-rose-700';
        icon.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
        title.textContent = 'Hay conflictos que deben corregirse';
    } else {
        section.classList.remove('border-rose-200', 'bg-rose-50', 'border-emerald-200', 'bg-emerald-50');
        section.classList.add('border-amber-200', 'bg-amber-50');
        icon.className = 'mt-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700';
        icon.innerHTML = '<i class="fa-solid fa-circle-info"></i>';
        title.textContent = 'Revisión recomendada';
    }

    validation.errors.forEach((message) => {
        const item = document.createElement('div');
        item.className = 'rounded-xl border border-rose-200 bg-white px-3 py-2 text-rose-800';
        item.textContent = message;
        list.appendChild(item);
    });
    validation.warnings.forEach((message) => {
        const item = document.createElement('div');
        item.className = 'rounded-xl border border-amber-200 bg-white px-3 py-2 text-amber-800';
        item.textContent = message;
        list.appendChild(item);
    });

    return validation;
}

function clearAnalysisUI() {
    ['document-import-analysis', 'document-import-separator-section', 'document-import-mapping-section', 'document-import-validation-section', 'document-import-preview-section'].forEach((id) => {
        const node = docImportEl(id);
        if (node) node.classList.add('hidden');
    });
    const styles = docImportEl('document-import-style-cards');
    const options = docImportEl('document-import-separator-options');
    const mapping = docImportEl('document-import-mapping-options');
    const validation = docImportEl('document-import-validation-list');
    const preview = docImportEl('document-import-preview-list');
    if (styles) styles.innerHTML = '';
    if (options) options.innerHTML = '';
    if (mapping) mapping.innerHTML = '';
    if (validation) validation.innerHTML = '';
    if (preview) preview.innerHTML = '';
    const previewCount = docImportEl('document-import-preview-count');
    if (previewCount) previewCount.textContent = '0';
}

function setBusy(busy, label) {
    documentImportState.busy = busy;
    const analyzeBtn = docImportEl('document-import-analyze-btn');
    const commitBtn = docImportEl('document-import-commit-btn');
    if (analyzeBtn) analyzeBtn.disabled = busy || !documentImportState.file;
    if (commitBtn) commitBtn.disabled = busy || !documentImportState.file || !documentImportState.analysis || !documentImportState.mapping || !documentImportState.mapping.chapter_separator;
    const status = docImportEl('document-import-status');
    if (status) {
        const text = label || (busy ? 'Analizando...' : (documentImportState.analysis ? 'Analizado' : 'Esperando archivo'));
        status.innerHTML = busy
            ? `<i class="fa-solid fa-circle-notch fa-spin"></i> ${text}`
            : `<i class="fa-solid fa-circle-check"></i> ${text}`;
    }
}

function setHint(text) {
    const hint = docImportEl('document-import-hint');
    if (hint) hint.textContent = text;
}

function updateFileMeta(file) {
    const meta = docImportEl('document-import-file-meta');
    const name = docImportEl('document-import-file-name');
    const size = docImportEl('document-import-file-size');
    const type = docImportEl('document-import-file-type');
    if (!meta || !name || !size || !type) return;

    if (!file) {
        meta.classList.add('hidden');
        return;
    }

    meta.classList.remove('hidden');
    name.textContent = file.name;
    size.textContent = formatBytes(file.size);
    type.textContent = (file.name.split('.').pop() || 'file').toUpperCase();
}

function renderStyleCards(styleCounts) {
    const wrap = docImportEl('document-import-style-cards');
    if (!wrap) return;
    wrap.innerHTML = '';

    if (!Array.isArray(styleCounts) || !styleCounts.length) {
        wrap.innerHTML = '<span class="text-xs text-[var(--text-muted)]">No se detectaron estilos suficientes.</span>';
        return;
    }

    styleCounts.forEach((style) => {
        const pill = document.createElement('button');
        pill.type = 'button';
        pill.className = 'rounded-full border border-[var(--border-color)] bg-[var(--bg-app)] px-3 py-1.5 text-xs font-semibold text-[var(--text-main)] hover:bg-slate-100 transition';
        pill.textContent = `${style.label} · ${style.count}`;
        pill.addEventListener('click', () => {
            if (documentImportState.mapping) {
                documentImportState.mapping.chapter_separator = style.key;
                renderSeparatorOptions(documentImportState.analysis);
                renderMappingSection(documentImportState.analysis);
                renderValidationSection(documentImportState.analysis, documentImportState.mapping);
                renderPreview(documentImportState.analysis, documentImportState.mapping);
                updateCommitState();
            }
        });
        wrap.appendChild(pill);
    });
}

function renderSeparatorOptions(analysis) {
    const wrap = docImportEl('document-import-separator-options');
    if (!wrap) return;
    wrap.innerHTML = '';

    const options = getSeparatorOptions(analysis);
    if (!options.length) {
        wrap.innerHTML = '<div class="text-sm text-[var(--text-muted)]">No hay estilos detectados para separar capítulos.</div>';
    }

    options.forEach((option) => {
        const checked = documentImportState.mapping && documentImportState.mapping.chapter_separator === option.key;
        const btn = document.createElement('label');
        btn.className = `cursor-pointer rounded-2xl border p-4 transition ${checked ? 'border-sky-500 bg-sky-50 ring-1 ring-sky-200' : 'border-[var(--border-color)] bg-[var(--bg-sidebar)] hover:bg-[var(--bg-app)]'}`;
        btn.innerHTML = `
            <div class="flex items-start gap-3">
                <input type="radio" name="document-import-separator" class="mt-1" ${checked ? 'checked' : ''}>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-[var(--text-main)]">${escapeHtml(option.label)}</p>
                    <p class="mt-1 text-xs text-[var(--text-muted)]">${option.count ? `${option.count} coincidencias detectadas` : 'Disponible como separador'}</p>
                </div>
            </div>
        `;
        btn.addEventListener('click', () => {
            if (!documentImportState.mapping) return;
            documentImportState.mapping.chapter_separator = option.key;
            renderSeparatorOptions(documentImportState.analysis);
            renderMappingSection(documentImportState.analysis);
            renderValidationSection(documentImportState.analysis, documentImportState.mapping);
            renderPreview(documentImportState.analysis, documentImportState.mapping);
            updateCommitState();
        });
        wrap.appendChild(btn);
    });

    const section = docImportEl('document-import-separator-section');
    if (section) section.classList.remove('hidden');
}

function renderMappingSection(analysis) {
    const wrap = docImportEl('document-import-mapping-options');
    if (!wrap) return;
    wrap.innerHTML = '';

    const options = getSelectableStyles(analysis);
    const available = Array.isArray(options) ? options : [];

    if (!available.length) {
        wrap.innerHTML = '<div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-app)] p-4 text-sm text-[var(--text-muted)]">No hay estilos suficientes para corregir la jerarquía.</div>';
        const section = docImportEl('document-import-mapping-section');
        if (section) section.classList.remove('hidden');
        return;
    }

    SEMANTIC_FIELDS.forEach((field) => {
        const selectedValue = documentImportState.mapping ? (documentImportState.mapping[field.key] || '') : '';
        const row = document.createElement('div');
        row.className = 'rounded-2xl border border-[var(--border-color)] bg-[var(--bg-app)] p-4';
        const selectId = `document-import-${field.key}`;
        row.innerHTML = `
            <div class="flex flex-col gap-3">
                <div>
                    <p class="text-sm font-semibold text-[var(--text-main)]">${escapeHtml(field.label)}</p>
                    <p class="mt-1 text-xs text-[var(--text-muted)]">${escapeHtml(field.description)}</p>
                </div>
                <select id="${selectId}" class="w-full rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-sm text-[var(--text-main)] focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="">No usar</option>
                    ${available.map((option) => `<option value="${escapeHtml(option.key)}"${option.key === selectedValue ? ' selected' : ''}>${escapeHtml(option.label)}${option.count !== undefined ? ` · ${option.count}` : ''}</option>`).join('')}
                </select>
            </div>
        `;
        wrap.appendChild(row);

        const select = row.querySelector('select');
        if (select) {
            select.addEventListener('change', () => {
                if (!documentImportState.mapping) return;
                documentImportState.mapping[field.key] = select.value;
                if (field.key === 'title_style' && !documentImportState.mapping.chapter_separator) {
                    documentImportState.mapping.chapter_separator = select.value;
                }
                renderSeparatorOptions(documentImportState.analysis);
                renderValidationSection(documentImportState.analysis, documentImportState.mapping);
                renderPreview(documentImportState.analysis, documentImportState.mapping);
                updateCommitState();
            });
        }
    });

    const section = docImportEl('document-import-mapping-section');
    if (section) section.classList.remove('hidden');
}

function renderPreview(analysis, mapping = documentImportState.mapping || buildDefaultMapping(analysis)) {
    const section = docImportEl('document-import-preview-section');
    const wrap = docImportEl('document-import-preview-list');
    const count = docImportEl('document-import-preview-count');
    const chapterMetric = docImportEl('document-import-chapters');
    if (!section || !wrap || !count) return;

    const preview = buildChapterPreview(Array.isArray(analysis?.blocks) ? analysis.blocks : [], mapping);
    count.textContent = String(preview.length);
    if (chapterMetric) {
        chapterMetric.textContent = String(preview.length);
    }
    wrap.innerHTML = '';

    if (!preview.length) {
        wrap.innerHTML = '<div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-app)] p-4 text-sm text-[var(--text-muted)]">No hay capítulos separados todavía.</div>';
        section.classList.remove('hidden');
        return;
    }

    preview.slice(0, 12).forEach((chapter, index) => {
        const item = document.createElement('div');
        item.className = 'rounded-xl border border-[var(--border-color)] bg-[var(--bg-app)] px-4 py-3';

        const outlineHtml = Array.isArray(chapter.outline) && chapter.outline.length
            ? `<div class="mt-3 space-y-2 border-t border-[var(--border-color)] pt-3">
                    ${chapter.outline.slice(0, 4).map((entry) => `
                        <div class="flex items-start gap-2 text-xs">
                            <span class="mt-0.5 rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-600">${escapeHtml(entry.label)}</span>
                            <span class="flex-1 text-[var(--text-main)]">${escapeHtml(entry.text)}</span>
                        </div>
                    `).join('')}
                </div>`
            : '';

        item.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-[var(--text-muted)]">Capítulo ${index + 1}</p>
                    <p class="mt-1 text-sm font-semibold text-[var(--text-main)]">${escapeHtml(chapter.title || 'Sin título')}</p>
                </div>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">${chapter.blocks || 0} bloques</span>
            </div>
            ${outlineHtml}
        `;
        wrap.appendChild(item);
    });

    section.classList.remove('hidden');
}

function updateAnalysisSummary(analysis) {
    const format = docImportEl('document-import-format');
    const blocks = docImportEl('document-import-blocks');
    const chapters = docImportEl('document-import-chapters');
    const confidence = docImportEl('document-import-confidence');
    const analysisSection = docImportEl('document-import-analysis');
    const status = docImportEl('document-import-status');

    if (analysisSection) analysisSection.classList.remove('hidden');
    if (format) format.textContent = analysis.format_label || analysis.format || '-';
    if (blocks) blocks.textContent = String(analysis.block_count ?? 0);
    if (chapters) chapters.textContent = String(analysis.chapter_count ?? 0);
    if (confidence) confidence.textContent = analysis.confidence_label || '-';
    if (status) {
        status.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${analysis.status_label || 'Analizado'}`;
    }
}

function updateCommitState() {
    const commitBtn = docImportEl('document-import-commit-btn');
    const hasErrors = documentImportState.validation && Array.isArray(documentImportState.validation.errors) && documentImportState.validation.errors.length > 0;
    if (commitBtn) commitBtn.disabled = documentImportState.busy || !documentImportState.file || !documentImportState.analysis || !documentImportState.mapping || !documentImportState.mapping.chapter_separator || hasErrors;
}
