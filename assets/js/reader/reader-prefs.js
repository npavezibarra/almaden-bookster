// reader-prefs.js

let userPrefs = { fontSizeOffset: 0, lineHeightOffset: 0, theme: '' };
try {
    if (typeof window.userDBPrefs !== 'undefined' && window.userDBPrefs) {
        userPrefs = window.userDBPrefs;
        localStorage.setItem('almaden_bookster_prefs_v2', JSON.stringify(userPrefs));
    } else {
        const saved = localStorage.getItem('almaden_bookster_prefs_v2');
        if (saved) userPrefs = JSON.parse(saved);
    }
} catch(e) {}

function savePrefs() {
    localStorage.setItem('almaden_bookster_prefs_v2', JSON.stringify(userPrefs));
    if (typeof generateDynamicStyles === 'function') generateDynamicStyles();
    if (typeof applyReaderHighlightsToCurrentChapter === 'function') {
        window.setTimeout(() => applyReaderHighlightsToCurrentChapter(), 0);
    }
    
    if (typeof window.almadenAjaxUrl !== 'undefined') {
        const formData = new FormData();
        formData.append('action', 'almaden_save_user_prefs');
        formData.append('prefs', JSON.stringify(userPrefs));
        
        fetch(window.almadenAjaxUrl, {
            method: 'POST',
            body: formData
        }).catch(err => console.error('Error saving prefs to DB', err));
    }
}

function togglePrefsPanel() {
    const panel = document.getElementById('reader-prefs-panel');
    if (!panel) return;
    if (panel.classList.contains('hidden')) {
        panel.classList.remove('hidden');
        panel.classList.add('flex');
    } else {
        panel.classList.add('hidden');
        panel.classList.remove('flex');
    }
}

function changeFontSize(dir) {
    let currentOffset = userPrefs.fontSizeOffset || 0;
    const newOffset = currentOffset + dir;
    if (newOffset >= -10 && newOffset <= 30) {
        userPrefs.fontSizeOffset = newOffset;
        savePrefs();
    }
}


function changeLineHeight(dir) {
    userPrefs.lineHeightOffset = (userPrefs.lineHeightOffset || 0) + dir;
    savePrefs();
}

function changeTheme(theme) {
    userPrefs.theme = theme;
    savePrefs();
}

// Close panels when clicking outside
document.addEventListener('click', function(event) {
    // Prefs panel
    const prefsPanel = document.getElementById('reader-prefs-panel');
    const prefsBtn = document.getElementById('btn-reader-prefs');
    if (prefsPanel && !prefsPanel.classList.contains('hidden') && prefsBtn && !prefsPanel.contains(event.target) && !prefsBtn.contains(event.target)) {
        prefsPanel.classList.add('hidden');
        prefsPanel.classList.remove('flex');
    }

    // Footnote popup
    const fnPopup = document.getElementById('footnote-popup');
    if (fnPopup && !fnPopup.classList.contains('hidden') && !fnPopup.contains(event.target) && !event.target.closest('.footnote-btn')) {
        fnPopup.classList.remove('opacity-100');
        fnPopup.classList.add('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            if (fnPopup.classList.contains('opacity-0')) {
                fnPopup.classList.add('hidden');
            }
        }, 200);
    }
});
