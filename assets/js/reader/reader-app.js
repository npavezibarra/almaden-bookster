const md = window.markdownit({ html: true, breaks: true }).use(window.markdownitFootnote);

function getReaderChapterItemDomId(chapterId) {
    return `chapter-item-${String(chapterId ?? '').replace(/[^a-zA-Z0-9_-]/g, '-')}`;
}

function escapeReaderIndexHtml(value) {
	return String(value ?? '').replace(/[&<>'"]/g, (char) => ({
		'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
	}[char]));
}

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
        item.id = getReaderChapterItemDomId(chapter.id);
        item.dataset.chapterId = String(chapter.id);
        item.className = 'reader-index-item flex justify-between items-center py-4 border-b border-gray-100 transition-colors group px-4 -mx-4 rounded-md';

		const accessLabel = chapter.locked
			? `<button type="button" class="reader-lock-trigger reader-index-access is-locked" data-lock-title="${escapeReaderIndexHtml(chapter.title || '')}" aria-label="Comprar ${escapeReaderIndexHtml(chapter.title || 'capítulo')}">
					<i class="fa-solid fa-lock" aria-hidden="true"></i>
					<span class="sr-only">Comprar</span>
				</button>`
			: chapter.is_sample && !bookData.userCanAccess
				? '<span class="reader-index-access is-sample reader-sample-indicator" aria-label="Muestra gratis"><i class="fa-solid fa-lock-open" aria-hidden="true"></i><span class="sr-only">Muestra gratis</span></span>'
				: `<span class="text-gray-400 font-medium">${escapeReaderIndexHtml(chapter.page || '')}</span>`;
		item.classList.toggle('is-locked', Boolean(chapter.locked));
		if (!chapter.locked) {
			item.classList.add('cursor-pointer');
			item.setAttribute('role', 'button');
			item.setAttribute('tabindex', '0');
			item.addEventListener('click', () => {
				if (typeof showChapterView === 'function') {
					showChapterView(index);
				}
			});
			item.addEventListener('keydown', (event) => {
				if ((event.key === 'Enter' || event.key === ' ') && typeof showChapterView === 'function') {
					event.preventDefault();
					showChapterView(index);
				}
			});
		} else {
			item.setAttribute('aria-disabled', 'true');
			item.classList.add('cursor-not-allowed', 'opacity-80');
		}
		item.innerHTML = `
			<span class="text-gray-800 font-medium group-hover:text-black text-lg transition-colors">${escapeReaderIndexHtml(chapter.title)}</span>
			${accessLabel}
		`;
        listContainer.appendChild(item);
    });

    listContainer.querySelectorAll('.reader-lock-trigger').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            openReaderPurchaseModal({
                title: button.getAttribute('data-lock-title') || '',
            });
        });
    });
}

// Initialize
renderIndex();
