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
    const isMobileFootnote = window.matchMedia && window.matchMedia('(max-width: 768px)').matches;

    if (isMobileFootnote) {
        popup.style.width = '90vw';
        popup.style.maxWidth = '90vw';
        popup.style.left = '50%';
        popup.style.right = 'auto';
    } else {
        popup.style.width = '';
        popup.style.maxWidth = '';
        popup.style.right = '';
        popup.style.left = (rect.left + scrollLeft + (rect.width / 2)) + 'px';
    }
    
    popup.style.top = (rect.top + scrollTop) + 'px';
    popup.style.boxSizing = 'border-box';
    
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
        item.className = 'reader-index-item grid grid-cols-[2.25rem_minmax(0,1fr)_auto] items-center gap-3 py-5 px-4 -mx-4 border-b border-gray-100 transition-colors group rounded-md';

		const chapterProgress = window.ALMADEN_READER_CHAPTER_PROGRESS;
		const isRead = chapterProgress && typeof chapterProgress.isChapterRead === 'function' && chapterProgress.isChapterRead(chapter.id);
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

        const chapterNumber = escapeReaderIndexHtml(chapter.page || (index + 1));
        const statusLabel = isRead
            ? '<span class="reader-index-read-status inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-500 text-white flex-shrink-0" aria-label="Leído" title="Leído"><i class="fa-solid fa-check text-[11px] leading-none"></i></span>'
            : '<span class="reader-index-read-status inline-flex items-center justify-center w-5 h-5 rounded-full bg-transparent flex-shrink-0" aria-hidden="true"></span>';
        const lockLabel = chapter.locked
            ? `<button type="button" class="reader-lock-trigger reader-index-access is-locked ml-auto" data-lock-title="${escapeReaderIndexHtml(chapter.title || '')}" aria-label="Comprar ${escapeReaderIndexHtml(chapter.title || 'capítulo')}">
					<i class="fa-solid fa-lock" aria-hidden="true"></i>
					<span class="sr-only">Comprar</span>
				</button>`
			: statusLabel;
		item.innerHTML = `
			<div class="text-gray-400 font-normal text-[18px] leading-none">${chapterNumber}</div>
			<div class="min-w-0 text-gray-700 group-hover:text-black text-[18px] leading-none font-normal truncate">${escapeReaderIndexHtml(chapter.title)}</div>
			${chapter.locked ? lockLabel : statusLabel}
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

window.addEventListener('almaden:chapter-read-status-updated', () => {
	renderIndex();
});
