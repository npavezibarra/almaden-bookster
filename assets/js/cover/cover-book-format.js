// cover-book-format.js
document.addEventListener('DOMContentLoaded', () => {
    const el = window.CoverEditor.elements;

    if (!el.bookFormatSectionToggle || !el.bookFormatSectionContent || !el.bookFormatSectionIcon) {
        return;
    }

    function setExpanded(isExpanded) {
        el.bookFormatSectionContent.classList.toggle('hidden', !isExpanded);
        el.bookFormatSectionContent.classList.toggle('flex', isExpanded);
        el.bookFormatSectionIcon.classList.toggle('-rotate-90', !isExpanded);
    }

    setExpanded(true);

    el.bookFormatSectionToggle.addEventListener('click', () => {
        const isExpanded = !el.bookFormatSectionContent.classList.contains('hidden');
        setExpanded(!isExpanded);
    });
});
