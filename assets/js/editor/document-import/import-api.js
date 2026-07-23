async function postImportAction(action) {
    const form = new FormData();
    form.append('action', action);
    form.append('book_id', String(bookState.bookId));
    form.append('nonce', bookState.documentImportNonce);
    form.append('chapter_separator', documentImportState.mapping?.chapter_separator || '');
    form.append('import_mapping', JSON.stringify(documentImportState.mapping || {}));
    form.append('document_file', documentImportState.file, documentImportState.file.name);

    const response = await fetch(bookState.ajaxUrl, {
        method: 'POST',
        body: form
    });
    return response.json();
}

function applyAnalysis(analysis) {
    documentImportState.analysis = analysis;
    documentImportState.mapping = buildDefaultMapping(analysis);
    updateAnalysisSummary(analysis);
    renderStyleCards(analysis.style_counts || []);
    renderSeparatorOptions(analysis);
    renderMappingSection(analysis);
    renderValidationSection(analysis, documentImportState.mapping);
    renderPreview(analysis, documentImportState.mapping);
    setHint(analysis.hint || 'Revisa la separación y corrige la jerarquía antes de importar.');
    updateCommitState();
}

async function analyzeDocumentImport() {
    if (!documentImportState.file || documentImportState.busy) return;

    const form = new FormData();
    form.append('action', 'almaden_analyze_document_import');
    form.append('book_id', String(bookState.bookId));
    form.append('nonce', bookState.documentImportNonce);
    form.append('document_file', documentImportState.file, documentImportState.file.name);

    setBusy(true);
    setHint('Analizando estilos del archivo...');

    try {
        const response = await fetch(bookState.ajaxUrl, {
            method: 'POST',
            body: form
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.data || 'No se pudo analizar el archivo.');
        }

        applyAnalysis(data.data);
        showToast('Archivo analizado correctamente', 'fa-solid fa-magnifying-glass');
    } catch (error) {
        console.error(error);
        setHint(error.message || 'No se pudo analizar el archivo.');
        showToast('Error al analizar el documento', 'fa-solid fa-triangle-exclamation');
    } finally {
        setBusy(false);
    }
}

async function commitDocumentImport() {
    if (!documentImportState.file || !documentImportState.analysis || !documentImportState.mapping || !documentImportState.mapping.chapter_separator || documentImportState.busy) return;
    if (documentImportState.validation && Array.isArray(documentImportState.validation.errors) && documentImportState.validation.errors.length) {
        setHint('Corrige los conflictos de jerarquía antes de importar.');
        showToast('Hay errores en la estructura', 'fa-solid fa-triangle-exclamation');
        return;
    }

    setBusy(true);
    setHint('Importando capítulos al libro...');

    try {
        const result = await postImportAction('almaden_import_document');
        if (!result.success) {
            throw new Error(result.data || 'No se pudo importar el documento.');
        }

        closeDocumentImportModal();
        if (Array.isArray(result.data?.warnings) && result.data.warnings.length) {
            showToast(result.data.warnings[0], 'fa-solid fa-circle-info');
        } else {
            showToast(result.data?.message || 'Documento importado', 'fa-solid fa-file-import');
        }
        setTimeout(() => window.location.reload(), 900);
    } catch (error) {
        console.error(error);
        setHint(error.message || 'No se pudo importar el documento.');
        showToast('Error al importar el documento', 'fa-solid fa-triangle-exclamation');
    } finally {
        setBusy(false);
    }
}
