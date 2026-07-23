function openDocumentImportModal() {
    const input = docImportEl('document-import-file');
    if (input) input.value = '';
    documentImportState.file = null;
    documentImportState.analysis = null;
    documentImportState.mapping = null;
    documentImportState.validation = null;
    clearAnalysisUI();
    updateFileMeta(null);
    setHint('Primero selecciona un archivo para analizarlo.');
    setBusy(false);
    setModalVisible(true);
}

function closeDocumentImportModal() {
    setModalVisible(false);
}

document.addEventListener('DOMContentLoaded', () => {
    const input = docImportEl('document-import-file');
    if (input) {
        input.addEventListener('change', () => {
            documentImportState.file = input.files && input.files[0] ? input.files[0] : null;
            documentImportState.analysis = null;
            documentImportState.mapping = null;
            documentImportState.validation = null;
            clearAnalysisUI();
            updateFileMeta(documentImportState.file);
            setHint(documentImportState.file ? 'Pulsa “Analizar archivo” para detectar estilos y capítulos.' : 'Primero selecciona un archivo para analizarlo.');
            const analyzeBtn = docImportEl('document-import-analyze-btn');
            if (analyzeBtn) analyzeBtn.disabled = !documentImportState.file || documentImportState.busy;
            updateCommitState();
        });
    }

    const modal = docImportEl('document-import-modal');
    if (modal) {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeDocumentImportModal();
            }
        });
    }
});

window.openDocumentImportModal = openDocumentImportModal;
window.closeDocumentImportModal = closeDocumentImportModal;
window.analyzeDocumentImport = analyzeDocumentImport;
window.commitDocumentImport = commitDocumentImport;
