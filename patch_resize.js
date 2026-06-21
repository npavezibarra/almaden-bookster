const fs = require('fs');
let code = fs.readFileSync('assets/js/editor/editor-ui.js', 'utf8');

if (!code.includes('ResizeObserver')) {
    code = code.replace(
        /document\.addEventListener\('DOMContentLoaded', \(\) => \{[\s\S]*?scroller\.addEventListener\('scroll'[\s\S]*?\}\);/m,
        `document.addEventListener('DOMContentLoaded', () => {
    const scroller = document.getElementById('pdf-scroller');
    if (scroller) {
        scroller.addEventListener('scroll', () => {
            const wrapper = document.getElementById('pdf-ruler-wrapper');
            const ruler = document.getElementById('pdf-ruler');
            if (wrapper && !wrapper.classList.contains('hidden') && ruler) {
                ruler.style.left = -scroller.scrollLeft + 'px';
            }
        });

        // Add ResizeObserver to re-render ruler when scroller size changes
        const resizeObserver = new ResizeObserver(() => {
            const wrapper = document.getElementById('pdf-ruler-wrapper');
            if (wrapper && !wrapper.classList.contains('hidden')) {
                if (typeof window.renderRuler === 'function') window.renderRuler();
            }
        });
        resizeObserver.observe(scroller);
    }
});`
    );
    fs.writeFileSync('assets/js/editor/editor-ui.js', code);
}
