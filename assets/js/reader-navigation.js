// reader-navigation.js

let currentChapterIndex = -1;
let readingMode = 'scroll'; // 'scroll' or 'flip'
let currentFlipPage = 0;

// Reading Mode Toggle
function toggleReadingMode(mode) {
    readingMode = mode;
    const scrollBtn = document.getElementById('btn-mode-scroll');
    const flipBtn = document.getElementById('btn-mode-flip');
    const viewChapter = document.getElementById('view-chapter');
    const chapterContent = document.getElementById('chapter-content');
    const footerNav = document.getElementById('chapter-footer-nav');
    
    // Reset transforms
    currentFlipPage = 0;
    if (chapterContent) chapterContent.style.transform = 'translateX(0)';
    
    if (mode === 'flip') {
        if (flipBtn) {
            flipBtn.classList.replace('text-gray-400', 'text-gray-800');
            flipBtn.classList.add('bg-gray-100');
        }
        if (scrollBtn) {
            scrollBtn.classList.replace('text-gray-800', 'text-gray-400');
            scrollBtn.classList.remove('bg-gray-100');
        }
        
        if (viewChapter) viewChapter.classList.add('mode-flip');
        if (footerNav) footerNav.classList.add('hidden'); // hide scroll footer nav
        
        // Need a tiny delay for CSS layout to calculate columns
        setTimeout(updateFlipButtons, 100);
    } else {
        if (scrollBtn) {
            scrollBtn.classList.replace('text-gray-400', 'text-gray-800');
            scrollBtn.classList.add('bg-gray-100');
        }
        if (flipBtn) {
            flipBtn.classList.replace('text-gray-800', 'text-gray-400');
            flipBtn.classList.remove('bg-gray-100');
        }
        
        if (viewChapter) viewChapter.classList.remove('mode-flip');
        if (footerNav) footerNav.classList.remove('hidden');
        const btnPrev = document.getElementById('btn-flip-prev');
        const btnNext = document.getElementById('btn-flip-next');
        if (btnPrev) btnPrev.classList.add('hidden');
        if (btnNext) btnNext.classList.add('hidden');
    }
}

function updateFlipButtons() {
    if (readingMode !== 'flip') return;
    const scrollArea = document.getElementById('chapter-scroll-area');
    const chapterContent = document.getElementById('chapter-content');
    if (!scrollArea || !chapterContent) return;
    
    // A "page" view is the exact visible width of the scroll area
    const viewWidth = scrollArea.clientWidth;
    const totalWidth = chapterContent.scrollWidth;
    
    const maxPages = Math.ceil((totalWidth - 10) / viewWidth) - 1; // -10px threshold to avoid empty last page
    
    const btnPrev = document.getElementById('btn-flip-prev');
    const btnNext = document.getElementById('btn-flip-next');
    
    // Show/Hide Previous Button
    if (btnPrev) {
        if (currentFlipPage > 0) {
            btnPrev.classList.remove('hidden');
        } else {
            btnPrev.classList.add('hidden');
        }
    }
    
    // Show/Hide Next Button
    if (btnNext) {
        if (currentFlipPage < maxPages) {
            btnNext.classList.remove('hidden');
        } else {
            btnNext.classList.add('hidden');
        }
    }
}

function flipNext() {
    const scrollArea = document.getElementById('chapter-scroll-area');
    const chapterContent = document.getElementById('chapter-content');
    if (!scrollArea || !chapterContent) return;
    
    const viewWidth = scrollArea.clientWidth;
    const maxPages = Math.ceil((chapterContent.scrollWidth - 10) / viewWidth) - 1;
    
    if (currentFlipPage < maxPages) {
        currentFlipPage++;
        chapterContent.style.transform = `translateX(-${currentFlipPage * viewWidth}px)`;
        updateFlipButtons();
    }
}

function flipPrev() {
    if (currentFlipPage > 0) {
        currentFlipPage--;
        const scrollArea = document.getElementById('chapter-scroll-area');
        const chapterContent = document.getElementById('chapter-content');
        if (!scrollArea || !chapterContent) return;
        
        const viewWidth = scrollArea.clientWidth;
        chapterContent.style.transform = `translateX(-${currentFlipPage * viewWidth}px)`;
        updateFlipButtons();
    }
}

window.addEventListener('resize', () => {
    if (readingMode === 'flip') {
        currentFlipPage = 0;
        const chapterContent = document.getElementById('chapter-content');
        if (chapterContent) chapterContent.style.transform = 'translateX(0)';
        updateFlipButtons();
    }
});

const scrollArea = document.getElementById('chapter-scroll-area');
if (scrollArea) {
    scrollArea.addEventListener('scroll', function() {
        const navTitle = document.getElementById('chapter-nav-title');
        if (navTitle) {
            if (this.scrollTop > 200) {
                navTitle.classList.remove('opacity-0');
                navTitle.classList.add('opacity-100');
            } else {
                navTitle.classList.remove('opacity-100');
                navTitle.classList.add('opacity-0');
            }
        }
        
        // Update Reading Progress
        const scrollHeight = this.scrollHeight - this.clientHeight;
        const progress = (scrollHeight > 0) ? (this.scrollTop / scrollHeight) * 100 : 0;
        const progressBar = document.getElementById('reading-progress-bar');
        if (progressBar) progressBar.style.width = progress + '%';
    });
}

function showIndexView() {
    document.getElementById('view-chapter').classList.add('hidden');
    const viewIndex = document.getElementById('view-index');
    viewIndex.classList.remove('hidden');
    
    // Animation reset
    viewIndex.classList.remove('fade-enter-active');
    viewIndex.classList.add('fade-enter');
    requestAnimationFrame(() => {
        viewIndex.classList.add('fade-enter-active');
    });
    
    currentChapterIndex = -1;
    if (typeof scaleThumbnails === 'function') scaleThumbnails(); // Re-scale if needed
}

function showChapterView(index) {
    currentChapterIndex = index;
    const chapter = bookData.chapters[index];
    
    document.getElementById('view-index').classList.add('hidden');
    const viewChapter = document.getElementById('view-chapter');
    viewChapter.classList.remove('hidden');
    
    // Pre-process shortcodes
    let processedContent = chapter.content;
    
    // Handle [lang:*]...[/lang] shortcodes -> italics
    processedContent = processedContent.replace(/\[lang:[^\]]+\](.*?)\[\/lang\]/gs, '<i>$1</i>');
    
    // Handle [font="..."]...[/font] shortcodes -> span with font-family
    processedContent = processedContent.replace(/\[font="([^"]+)"\](.*?)\[\/font\]/gs, '<span style="font-family: \'$1\', serif;">$2</span>');
    
    // Handle [align=...]...[/align] shortcodes -> div with text-align
    processedContent = processedContent.replace(/\[align=([a-z]+)\](.*?)\[\/align\]/gs, '<div style="text-align: $1;">$2</div>');

    // Content injection
    document.getElementById('chapter-nav-title').textContent = chapter.title;
    
    let finalHtml = md.render(processedContent);
    if (chapter.hide_title !== '1') {
        finalHtml = `<div class="reader-chapter-title">${chapter.title}</div>` + finalHtml;
    }
    
    document.getElementById('chapter-content').innerHTML = finalHtml;
    
    // Post-process footnotes to create interactive popups
    const footnotesSection = document.querySelector('#chapter-content .footnotes');
    if (footnotesSection) {
        const footnoteItems = footnotesSection.querySelectorAll('.footnote-item');
        const footnotesMap = {};
        
        footnoteItems.forEach(item => {
            const idMatch = item.id.match(/fn(\d+)/);
            if (idMatch) {
                const backref = item.querySelector('.footnote-backref');
                if (backref) backref.remove();
                footnotesMap[idMatch[1]] = item.innerHTML.trim();
            }
        });

        // Remove the default footnotes block from the bottom
        footnotesSection.remove();
        const sep = document.querySelector('#chapter-content .footnotes-sep');
        if (sep) sep.remove();
        
        // Transform the inline references into circular buttons
        const refs = document.querySelectorAll('#chapter-content .footnote-ref');
        refs.forEach(ref => {
            const a = ref.querySelector('a');
            if (a) {
                const idMatch = a.id.match(/fnref(\d+)/);
                if (idMatch && footnotesMap[idMatch[1]]) {
                    const id = idMatch[1];
                    const btn = document.createElement('button');
                    btn.className = "footnote-btn relative inline-flex items-center justify-center w-[1.125rem] h-[1.125rem] rounded-full bg-gray-200 hover:bg-gray-300 text-[10px] font-bold text-gray-700 transition-colors mx-0.5 align-super cursor-pointer select-none";
                    btn.textContent = id;
                    btn.onclick = (e) => {
                        if (typeof showFootnote === 'function') {
                            showFootnote(e, btn, footnotesMap[id]);
                        }
                    };
                    ref.replaceWith(btn);
                }
            }
        });
    }
    
    // Reset state
    document.getElementById('chapter-scroll-area').scrollTop = 0;
    document.getElementById('reading-progress-bar').style.width = '0%';
    const navTitle = document.getElementById('chapter-nav-title');
    navTitle.classList.remove('opacity-100');
    navTitle.classList.add('opacity-0');
    currentFlipPage = 0;
    document.getElementById('chapter-content').style.transform = 'translateX(0)';
    if (readingMode === 'flip') {
        setTimeout(updateFlipButtons, 100);
    }

    // Nav buttons
    const btnPrev = document.getElementById('btn-prev-chapter');
    const btnNext = document.getElementById('btn-next-chapter');
    
    let prevIndex = index - 1;
    while (prevIndex >= 0 && (bookData.chapters[prevIndex].is_toc === '1' || bookData.chapters[prevIndex].is_credits === '1')) {
        prevIndex--;
    }

    if (prevIndex >= 0) {
        btnPrev.classList.remove('hidden');
    } else {
        btnPrev.classList.add('hidden');
    }

    let nextIndex = index + 1;
    while (nextIndex < bookData.chapters.length && (bookData.chapters[nextIndex].is_toc === '1' || bookData.chapters[nextIndex].is_credits === '1')) {
        nextIndex++;
    }

    if (nextIndex < bookData.chapters.length) {
        btnNext.classList.remove('hidden');
    } else {
        btnNext.classList.add('hidden');
    }
    
    // Animation
    viewChapter.classList.remove('fade-enter-active');
    viewChapter.classList.add('fade-enter');
    requestAnimationFrame(() => {
        viewChapter.classList.add('fade-enter-active');
    });
}

function goToNextChapter() {
    let nextIndex = currentChapterIndex + 1;
    while (nextIndex < bookData.chapters.length && (bookData.chapters[nextIndex].is_toc === '1' || bookData.chapters[nextIndex].is_credits === '1')) {
        nextIndex++;
    }
    if (nextIndex < bookData.chapters.length) {
        showChapterView(nextIndex);
    }
}

function goToPrevChapter() {
    let prevIndex = currentChapterIndex - 1;
    while (prevIndex >= 0 && (bookData.chapters[prevIndex].is_toc === '1' || bookData.chapters[prevIndex].is_credits === '1')) {
        prevIndex--;
    }
    if (prevIndex >= 0) {
        showChapterView(prevIndex);
    }
}
