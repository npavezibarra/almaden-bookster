// assets/js/editor/editor-chapter-settings-labels.js

function getChapterSettingsModalLabels(chapter) {
    if (!chapter) {
        return {
            title: 'Ajustes del Capítulo de Contenido',
            subtitle: 'Estas configuraciones sobrescriben las reglas globales solo para el capítulo de contenido actual.',
            startLabel: '¿Dónde debe iniciar el contenido de este capítulo?',
            startSubtitle: 'Define el lado donde empieza el contenido. La apertura se configura aparte en la pestaña "Apertura".',
            saveLabel: 'Aplicar al Capítulo de Contenido'
        };
    }

    if (chapter.is_toc === '1') {
        return {
            title: 'Ajustes del Índice',
            subtitle: 'Estas configuraciones sobrescriben las reglas globales solo para el índice actual.',
            startLabel: '¿Dónde debe iniciar el contenido del Índice?',
            startSubtitle: 'Define el lado donde empieza el Índice. La apertura se configura aparte en la pestaña "Apertura".',
            saveLabel: 'Aplicar al Índice'
        };
    }

    if (chapter.is_credits === '1') {
        return {
            title: 'Ajustes de la Página de Créditos',
            subtitle: 'Estas configuraciones sobrescriben las reglas globales solo para la página de créditos actual.',
            startLabel: '¿Dónde debe iniciar el contenido de la Página de Créditos?',
            startSubtitle: 'Define el lado donde empieza la Página de Créditos. La apertura se configura aparte en la pestaña "Apertura".',
            saveLabel: 'Aplicar a Créditos'
        };
    }

    return {
        title: 'Ajustes del Capítulo de Contenido',
        subtitle: 'Estas configuraciones sobrescriben las reglas globales solo para el capítulo de contenido actual.',
        startLabel: '¿Dónde debe iniciar el contenido de este capítulo?',
        startSubtitle: 'Define el lado donde empieza el contenido. La apertura se configura aparte en la pestaña "Apertura".',
        saveLabel: 'Aplicar al Capítulo de Contenido'
    };
}

function applyChapterSettingsModalLabels(chapter) {
    const labels = getChapterSettingsModalLabels(chapter);
    const titleEl = document.getElementById('chapter-settings-modal-title');
    const subtitleEl = document.getElementById('chapter-settings-modal-subtitle');
    const startLabelEl = document.getElementById('chapter-settings-modal-start-label');
    const startSubtitleEl = document.getElementById('chapter-settings-modal-start-subtitle');
    const saveBtn = document.querySelector('#chapter-settings-modal button[onclick="saveChapterSettings()"]');

    if (titleEl) {
        titleEl.innerHTML = `<i class="fa-solid fa-gear text-black dark:text-white"></i> ${labels.title}`;
    }
    if (subtitleEl) subtitleEl.textContent = labels.subtitle;
    if (startLabelEl) startLabelEl.textContent = labels.startLabel;
    if (startSubtitleEl) startSubtitleEl.textContent = labels.startSubtitle;
    if (saveBtn) {
        saveBtn.dataset.defaultLabel = labels.saveLabel;
        saveBtn.innerHTML = `<i class="fa-solid fa-check"></i> ${labels.saveLabel}`;
    }
}
