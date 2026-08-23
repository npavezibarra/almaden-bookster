// reader-navigation.js

let currentChapterIndex = -1;
let readingMode = 'scroll'; // 'scroll' or 'flip'
let currentFlipPage = 0;

function normalizeReaderChapterImages(html) {
    if (!html) return html;

    const template = document.createElement('template');
    template.innerHTML = html;
    template.content.querySelectorAll('img[data-preview-src]').forEach((img) => {
        const previewSrc = img.getAttribute('data-preview-src') || '';
        if (previewSrc) {
            img.setAttribute('src', previewSrc);
        }
    });
    return template.innerHTML;
}

function renderReaderChapterStatus(root, message, className, role) {
    const status = document.createElement('p');
    status.className = className;
    status.setAttribute('role', role);
    status.textContent = message;
    root.replaceChildren(status);
}

function escapeReaderNavigationHtml(value) {
	return String(value ?? '').replace(/[&<>'"]/g, (char) => ({
		'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
	}[char]));
}

function ensureReaderPurchaseModal() {
	let modal = document.getElementById('reader-purchase-modal');
	if (modal) {
		return modal;
	}

	modal = document.createElement('div');
	modal.id = 'reader-purchase-modal';
	modal.className = 'reader-purchase-modal hidden';
	modal.innerHTML = `
		<div class="reader-purchase-modal-backdrop" data-reader-purchase-close></div>
		<div class="reader-purchase-modal-panel" role="dialog" aria-modal="true" aria-labelledby="reader-purchase-modal-title">
			<button type="button" class="reader-purchase-modal-close" data-reader-purchase-close aria-label="Cerrar">&times;</button>
			<div class="reader-purchase-modal-icon" aria-hidden="true">
				<i class="fa-solid fa-lock"></i>
			</div>
			<p class="reader-purchase-modal-kicker">Contenido protegido</p>
			<h2 id="reader-purchase-modal-title">Necesitas comprar este libro</h2>
			<p class="reader-purchase-modal-copy" data-reader-purchase-copy>Este capítulo forma parte del ebook completo. Compra el libro para continuar leyendo.</p>
			<div class="reader-purchase-modal-actions">
				<a class="reader-purchase-modal-button" data-reader-purchase-button href="/">Comprar libro</a>
			</div>
		</div>`;
	document.body.appendChild(modal);

	modal.querySelectorAll('[data-reader-purchase-close]').forEach((button) => {
		button.addEventListener('click', closeReaderPurchaseModal);
	});
	modal.addEventListener('click', (event) => {
		if (event.target === modal) {
			closeReaderPurchaseModal();
		}
	});
	return modal;
}

function openReaderPurchaseModal(options = {}) {
	const modal = ensureReaderPurchaseModal();
	const purchaseUrl = bookData.purchaseUrl || bookData.returnUrl || '/';
	const titleNode = modal.querySelector('#reader-purchase-modal-title');
	const copyNode = modal.querySelector('[data-reader-purchase-copy]');
	const button = modal.querySelector('[data-reader-purchase-button]');
	const isReadingToolsLock = options && options.reason === 'reading-tools';
	if (titleNode) {
		titleNode.hidden = !isReadingToolsLock;
		if (isReadingToolsLock) {
			titleNode.textContent = 'Desbloquea tus notas de lectura';
		}
	}
	if (copyNode) {
		copyNode.textContent = isReadingToolsLock
			? 'Compra este ebook para guardar highlights, escribir comentarios y acceder a todas las herramientas de lectura.'
			: 'Este capítulo forma parte del ebook completo. Compra el libro para desbloquearlo.';
	}
	if (button) {
		button.setAttribute('href', purchaseUrl);
		button.textContent = isReadingToolsLock ? 'Comprar ebook' : 'Ir al producto';
	}
	modal.classList.remove('hidden');
	modal.classList.add('is-open');
	document.body.classList.add('reader-modal-open');
}

function openReaderReadingToolsPurchaseModal() {
	if (typeof cancelReaderHighlight === 'function') {
		cancelReaderHighlight();
	}
	openReaderPurchaseModal({ reason: 'reading-tools' });
}

function closeReaderPurchaseModal() {
	const modal = document.getElementById('reader-purchase-modal');
	if (!modal) return;
	modal.classList.add('hidden');
	modal.classList.remove('is-open');
	document.body.classList.remove('reader-modal-open');
}

function updateReaderChapterNavigation(index) {
	const btnPrev = document.getElementById('btn-prev-chapter');
	const btnNext = document.getElementById('btn-next-chapter');
	let prevIndex = index - 1;
	while (prevIndex >= 0 && (bookData.chapters[prevIndex].is_toc === '1' || bookData.chapters[prevIndex].is_credits === '1')) prevIndex--;
	let nextIndex = index + 1;
	while (nextIndex < bookData.chapters.length && (bookData.chapters[nextIndex].is_toc === '1' || bookData.chapters[nextIndex].is_credits === '1')) nextIndex++;
	btnPrev?.classList.toggle('hidden', prevIndex < 0);
	btnNext?.classList.toggle('hidden', nextIndex >= bookData.chapters.length);
}

function renderLockedReaderChapter(chapter, index) {
	openReaderPurchaseModal(chapter);
}

// Reading Mode Toggle
function toggleReadingMode(mode) {
    readingMode = mode;
    const scrollBtn = document.getElementById('btn-mode-scroll');
    const flipBtn = document.getElementById('btn-mode-flip');
    const viewChapter = document.getElementById('almaden-view-chapter');
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
    const scrollArea = document.getElementById('almaden-chapter-scroll-area');
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
    const scrollArea = document.getElementById('almaden-chapter-scroll-area');
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
        const scrollArea = document.getElementById('almaden-chapter-scroll-area');
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

const scrollArea = document.getElementById('almaden-chapter-scroll-area');
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
    document.getElementById('almaden-view-chapter').classList.add('hidden');
    const viewHighlights = document.getElementById('almaden-view-highlights');
    if (viewHighlights) viewHighlights.classList.add('hidden');
    const viewIndex = document.getElementById('almaden-view-index');
    viewIndex.classList.remove('hidden');
    
    // Animation reset
    viewIndex.classList.remove('fade-enter-active');
    viewIndex.classList.add('fade-enter');
    requestAnimationFrame(() => {
        viewIndex.classList.add('fade-enter-active');
    });
    
    currentChapterIndex = -1;
    if (window.AlmadenChapterLoader) window.AlmadenChapterLoader.release();
    if (typeof scaleThumbnails === 'function') scaleThumbnails(); // Re-scale if needed
}

async function showChapterView(index) {
    const chapter = bookData.chapters[index];
    if (window.console && console.log) {
        console.log('[AlmadenBookster Reader] showChapterView', {
            index,
            chapterId: chapter ? chapter.id : null,
            title: chapter ? chapter.title : null,
            quizId: chapter ? chapter.quiz_id : null,
            isToc: chapter ? chapter.is_toc : null,
            isCredits: chapter ? chapter.is_credits : null
        });
    }

	if (!chapter || chapter.locked) {
		renderLockedReaderChapter(chapter || {}, index);
		return;
	}

    currentChapterIndex = index;
    
    document.getElementById('almaden-view-index').classList.add('hidden');
    const viewHighlights = document.getElementById('almaden-view-highlights');
    if (viewHighlights) viewHighlights.classList.add('hidden');
    const viewChapter = document.getElementById('almaden-view-chapter');
    viewChapter.classList.remove('hidden');
    
    // Pre-process shortcodes
    let processedContent = chapter.content || '';
    if (window.AlmadenChapterLoader) {
        const contentRoot = document.getElementById('chapter-content');
        renderReaderChapterStatus(contentRoot, window.almadenContentProtectionConfig.loadingNotice, 'reader-chapter-loading', 'status');
        try {
            processedContent = await window.AlmadenChapterLoader.ensureChapterContent(index);
        } catch (error) {
            if (currentChapterIndex !== index || (error && error.name === 'AbortError')) return;
            renderReaderChapterStatus(contentRoot, window.almadenContentProtectionConfig.chapterError, 'reader-chapter-load-error', 'alert');
            return;
        }
        if (currentChapterIndex !== index) return;
    }
    if (window.AlmadenWatermark) window.AlmadenWatermark.update(chapter.id);
    
    // Process all inline shortcodes (lang, font, size, etc.)
    processedContent = window.AlmadenShortcodes.parseInline(processedContent);
    
    // Process all structural shortcodes (align, gap, etc.) directly into HTML
    processedContent = window.AlmadenShortcodes.parseStructural(processedContent, false);

    // Content injection
    document.getElementById('chapter-nav-title').textContent = chapter.title;
    
    let finalHtml = normalizeReaderChapterImages(md.render(processedContent));
    
    if (chapter.hide_title !== '1' && chapter.is_credits !== '1') {
        let prefixHtml = '';
        const settings = bookData.settings || {};
        
        if (settings.ebook_chapter_prefix_show == 1 && chapter.is_toc != '1' && chapter.exclude_from_numbering !== '1') {
            let chapterNumber = 0;
            for (let i = 0; i <= index; i++) {
                const c = bookData.chapters[i];
                if (c.is_toc !== '1' && c.is_credits !== '1' && c.exclude_from_numbering !== '1') {
                    chapterNumber++;
                }
            }
            
            let prefixText = settings.ebook_chapter_prefix_template || 'Capítulo {N}';
            prefixText = prefixText.replace('{N}', chapterNumber);
            
            if (prefixText.includes('{R}')) {
                const toRoman = (num) => {
                    const roman = {M:1000,CM:900,D:500,CD:400,C:100,XC:90,L:50,XL:40,X:10,IX:9,V:5,IV:4,I:1};
                    let str = '';
                    for (let i of Object.keys(roman)) {
                        let q = Math.floor(num / roman[i]);
                        num -= q * roman[i];
                        str += i.repeat(q);
                    }
                    return str;
                };
                prefixText = prefixText.replace('{R}', toRoman(chapterNumber));
            }
            
            let ornamentHtml = '';
            if (settings.ebook_chapter_prefix_ornament === 'line_below') {
                ornamentHtml = '<div class="reader-chapter-ornament-line"></div>';
            } else if (settings.ebook_chapter_prefix_ornament === 'line_above_below') {
                ornamentHtml = '<div class="reader-chapter-ornament-line"></div>';
                prefixText = '<div class="reader-chapter-ornament-line"></div>' + prefixText;
            } else if (settings.ebook_chapter_prefix_ornament === 'asterisks') {
                ornamentHtml = '<div class="reader-chapter-ornament-asterisks">***</div>';
            }
            
            const position = settings.ebook_chapter_prefix_position || 'above';
            const align = ['left', 'center', 'right'].includes(String(settings.ebook_chapter_prefix_align || '').toLowerCase())
                ? String(settings.ebook_chapter_prefix_align).toLowerCase()
                : 'center';
            const extraClass = position === 'below' ? ' prefix-below' : '';
            prefixHtml = `<div class="reader-chapter-prefix${extraClass}" style="text-align: ${align};">${prefixText}${ornamentHtml}</div>`;
            
            let subtitleHtml = '';
            const showGlobalSubtitle = settings.ebook_subtitle_show == 1 || settings.ebook_subtitle_show === undefined;
            if (chapter.subtitle_text && chapter.subtitle_text.trim() !== '' && chapter.is_toc !== '1' && showGlobalSubtitle) {
                const subText = chapter.subtitle_text.trim().replace(/\n/g, '<br>');
                let subStyles = [];
                
                const fontF = settings.ebook_subtitle_font_family;
                if (fontF) subStyles.push(`font-family: '${fontF}', serif !important`);
                
                const fontSz = settings.ebook_subtitle_font_size;
                if (fontSz) subStyles.push(`font-size: ${fontSz}pt !important`);
                
                const align = settings.ebook_subtitle_align;
                if (align) subStyles.push(`text-align: ${align} !important`);
                
                const fStyle = settings.ebook_subtitle_font_style;
                if (fStyle) subStyles.push(`font-style: ${fStyle} !important`);
                
                const fWeight = settings.ebook_subtitle_font_weight;
                if (fWeight) subStyles.push(`font-weight: ${fWeight} !important`);
                
                const tTransform = settings.ebook_subtitle_text_transform;
                if (tTransform) subStyles.push(`text-transform: ${tTransform} !important`);
                
                const lSpacing = settings.ebook_subtitle_letter_spacing;
                if (lSpacing) subStyles.push(`letter-spacing: ${lSpacing}px !important`);
                
                const mTop = settings.ebook_subtitle_padding_top;
                if (mTop !== undefined && mTop !== '') subStyles.push(`padding-top: ${mTop}em !important; margin-top:0!important`);
                
                const mBot = settings.ebook_subtitle_padding_bottom;
                if (mBot !== undefined && mBot !== '') subStyles.push(`padding-bottom: ${mBot}em !important; margin-bottom:0!important`);
                
                subtitleHtml = `<div class="reader-chapter-subtitle" style="line-height: 1.4; width: 100%; opacity: 0.85; ${subStyles.join('; ')}">${subText}</div>`;
            }
            
            if (position === 'below') {
                finalHtml = `<div class="reader-chapter-title" ${subtitleHtml ? 'style="padding-bottom:0!important"' : ''}>${chapter.title.trim()}</div>` + subtitleHtml + prefixHtml + finalHtml;
            } else {
                finalHtml = prefixHtml + `<div class="reader-chapter-title" ${subtitleHtml ? 'style="padding-bottom:0!important"' : ''}>${chapter.title.trim()}</div>` + subtitleHtml + finalHtml;
            }
        } else {
            let subtitleHtml = '';
            const showGlobalSubtitle = settings.ebook_subtitle_show == 1 || settings.ebook_subtitle_show === undefined;
            if (chapter.subtitle_text && chapter.subtitle_text.trim() !== '' && chapter.is_toc !== '1' && showGlobalSubtitle) {
                const subText = chapter.subtitle_text.trim().replace(/\n/g, '<br>');
                let subStyles = [];
                
                const fontF = settings.ebook_subtitle_font_family;
                if (fontF) subStyles.push(`font-family: '${fontF}', serif !important`);
                
                const fontSz = settings.ebook_subtitle_font_size;
                if (fontSz) subStyles.push(`font-size: ${fontSz}pt !important`);
                
                const align = settings.ebook_subtitle_align;
                if (align) subStyles.push(`text-align: ${align} !important`);
                
                const fStyle = settings.ebook_subtitle_font_style;
                if (fStyle) subStyles.push(`font-style: ${fStyle} !important`);
                
                const fWeight = settings.ebook_subtitle_font_weight;
                if (fWeight) subStyles.push(`font-weight: ${fWeight} !important`);
                
                const tTransform = settings.ebook_subtitle_text_transform;
                if (tTransform) subStyles.push(`text-transform: ${tTransform} !important`);
                
                const lSpacing = settings.ebook_subtitle_letter_spacing;
                if (lSpacing) subStyles.push(`letter-spacing: ${lSpacing}px !important`);
                
                const mTop = settings.ebook_subtitle_padding_top;
                if (mTop !== undefined && mTop !== '') subStyles.push(`padding-top: ${mTop}em !important; margin-top:0!important`);
                
                const mBot = settings.ebook_subtitle_padding_bottom;
                if (mBot !== undefined && mBot !== '') subStyles.push(`padding-bottom: ${mBot}em !important; margin-bottom:0!important`);
                
                subtitleHtml = `<div class="reader-chapter-subtitle" style="line-height: 1.4; width: 100%; opacity: 0.85; ${subStyles.join('; ')}">${subText}</div>`;
            }
            finalHtml = `<div class="reader-chapter-title" ${subtitleHtml ? 'style="padding-bottom:0!important"' : ''}>${chapter.title.trim()}</div>` + subtitleHtml + finalHtml;
        }
    }
    
    // Letra Capitular (Drop Cap)
    if (chapter.drop_cap_enabled === '1') {
        finalHtml = finalHtml.replace(/<p>/, '<p class="drop-cap">');
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

    if (typeof applyReaderHighlightsToCurrentChapter === 'function') {
        applyReaderHighlightsToCurrentChapter();
    }
    
    // Reset state
    document.getElementById('almaden-chapter-scroll-area').scrollTop = 0;
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
	updateReaderChapterNavigation(index);
	if (window.ALMADEN_READER_CHAPTER_PROGRESS && typeof window.ALMADEN_READER_CHAPTER_PROGRESS.updateToggleButton === 'function') {
		window.ALMADEN_READER_CHAPTER_PROGRESS.updateToggleButton();
	}

    if (window.ALMADEN_READER_QUIZZES && typeof window.ALMADEN_READER_QUIZZES.updateTakeQuizButton === 'function') {
        window.ALMADEN_READER_QUIZZES.updateTakeQuizButton(index);
    }
    
    // Animation
    viewChapter.classList.remove('fade-enter-active');
    viewChapter.classList.add('fade-enter');
    requestAnimationFrame(() => {
        viewChapter.classList.add('fade-enter-active');
    });
}

function goToNextChapter() {
	if (window.ALMADEN_READER_CHAPTER_PROGRESS && typeof window.ALMADEN_READER_CHAPTER_PROGRESS.markCurrentChapterRead === 'function') {
		window.ALMADEN_READER_CHAPTER_PROGRESS.markCurrentChapterRead();
	}
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
