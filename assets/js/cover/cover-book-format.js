// cover-book-format.js - Accordion Manager for Book Cover Left Sidebar
document.addEventListener('DOMContentLoaded', () => {
    window.CoverEditor = window.CoverEditor || {};
    window.CoverEditor.actions = window.CoverEditor.actions || {};

    const sections = [
        {
            key: 'book-format',
            toggle: document.getElementById('toggle-book-format-section'),
            content: document.getElementById('book-format-section-content'),
            icon: document.getElementById('book-format-section-icon')
        },
        {
            key: 'images',
            toggle: document.getElementById('toggle-images-section'),
            content: document.getElementById('images-section-content'),
            icon: document.getElementById('images-section-icon')
        },
        {
            key: 'flaps',
            toggle: document.getElementById('toggle-flaps-section'),
            content: document.getElementById('flaps-section-content'),
            icon: document.getElementById('flaps-section-icon')
        },
        {
            key: 'texts',
            toggle: document.getElementById('toggle-texts-section'),
            content: document.getElementById('texts-section-content'),
            icon: document.getElementById('texts-section-icon')
        }
    ];

    function closeAllSections() {
        sections.forEach(sec => {
            if (sec.content) {
                sec.content.classList.add('hidden');
                sec.content.classList.remove('flex');
            }
            if (sec.icon) {
                sec.icon.classList.add('-rotate-90');
            }
        });
    }

    function openSection(key) {
        sections.forEach(sec => {
            const isTarget = (sec.key === key);
            if (sec.content) {
                sec.content.classList.toggle('hidden', !isTarget);
                sec.content.classList.toggle('flex', isTarget);
            }
            if (sec.icon) {
                sec.icon.classList.toggle('-rotate-90', !isTarget);
            }
        });
    }

    function toggleSection(key) {
        const sec = sections.find(s => s.key === key);
        if (!sec || !sec.content) return;

        const isOpen = !sec.content.classList.contains('hidden');
        if (isOpen) {
            sec.content.classList.add('hidden');
            sec.content.classList.remove('flex');
            if (sec.icon) sec.icon.classList.add('-rotate-90');
        } else {
            openSection(key);
        }
    }

    window.CoverEditor.actions.openSidebarSection = openSection;
    window.CoverEditor.actions.closeAllSidebarSections = closeAllSections;
    window.CoverEditor.actions.toggleSidebarSection = toggleSection;

    sections.forEach(sec => {
        if (sec.toggle) {
            sec.toggle.addEventListener('click', (e) => {
                e.preventDefault();
                toggleSection(sec.key);

                if (sec.key === 'images' && sec.content && !sec.content.classList.contains('hidden')) {
                    if (typeof window.refreshImageDiagnostics === 'function') {
                        window.refreshImageDiagnostics(true);
                    }
                }
            });
        }
    });

    // Default: Open 'Formato del libro' and close all others
    openSection('book-format');
});
