const md = window.markdownit({ html: true, breaks: true }).use(window.markdownitFootnote);

function showFootnote(event, btn, htmlContent) {
    event.stopPropagation();
    
    const popup = document.getElementById('footnote-popup');
    const content = document.getElementById('footnote-popup-content');
    if (!popup || !content) return;
    
    content.innerHTML = htmlContent;
    
    // Position the popup
    popup.classList.remove('hidden');
    
    // Calculate position
    const rect = btn.getBoundingClientRect();
    const scrollTop = window.scrollY || document.documentElement.scrollTop;
    const scrollLeft = window.scrollX || document.documentElement.scrollLeft;
    
    popup.style.top = (rect.top + scrollTop) + 'px';
    popup.style.left = (rect.left + scrollLeft + (rect.width / 2)) + 'px';
    
    // Animate in
    requestAnimationFrame(() => {
        popup.classList.remove('opacity-0', 'pointer-events-none');
        popup.classList.add('opacity-100');
    });
}

// Cover thumbnail scaling
function scaleThumbnails() {
    document.querySelectorAll('.cover-thumbnail-wrapper').forEach(wrapper => {
        const targetWidth = wrapper.clientWidth;
        const frontCoverPx = parseFloat(wrapper.getAttribute('data-front-cover-px'));
        const startPx = parseFloat(wrapper.getAttribute('data-start-px'));
        if (frontCoverPx > 0) {
            const scale = targetWidth / frontCoverPx;
            const spread = wrapper.querySelector('.cover-spread-container');
            if (spread) {
                spread.style.transform = `scale(${scale}) translateX(${-startPx}px)`;
            }
        }
    });
}
window.addEventListener('resize', scaleThumbnails);
window.addEventListener('load', scaleThumbnails);

// Timeout to ensure fonts loaded before scaling
setTimeout(scaleThumbnails, 100);
setTimeout(scaleThumbnails, 500);

// Render chapters list
function renderIndex() {
    const listContainer = document.getElementById('chapters-list');
    if (!listContainer) return;
    listContainer.innerHTML = '';

    if (!bookData.chapters || bookData.chapters.length === 0) {
        listContainer.innerHTML = '<p class="text-gray-400 italic">Este libro no tiene capítulos aún.</p>';
        return;
    }

    bookData.chapters.forEach((chapter, index) => {
        if (chapter.is_toc === '1' || chapter.is_credits === '1') return; // Skip TOC and Credits chapter in Ebook

        const item = document.createElement('div');
        item.className = 'flex justify-between items-center py-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors group px-4 -mx-4 rounded-md';
        item.onclick = () => {
            if (typeof showChapterView === 'function') {
                showChapterView(index);
            }
        };

        item.innerHTML = `
            <span class="text-gray-800 font-medium group-hover:text-black text-lg transition-colors">${chapter.title}</span>
            <span class="text-gray-400 font-medium">${chapter.page || ''}</span>
        `;
        listContainer.appendChild(item);
    });
}

// Initialize
renderIndex();
