/**
 * Book Share Modal Handler for Almaden Bookster.
 */
(function() {
    'use strict';

    var currentBookId = 0;
    var selectedUser = null;
    var searchDebounceTimer = null;

    function getElements() {
        return {
            modal: document.getElementById('share-modal'),
            panel: document.getElementById('share-modal-panel'),
            bookTitle: document.getElementById('share-modal-book-title'),
            searchInput: document.getElementById('share-user-search-input'),
            searchSpinner: document.getElementById('share-search-spinner'),
            dropdown: document.getElementById('share-autocomplete-dropdown'),
            selectedBox: document.getElementById('share-selected-user-box'),
            selectedAvatar: document.getElementById('share-selected-user-avatar'),
            selectedName: document.getElementById('share-selected-user-name'),
            selectedEmail: document.getElementById('share-selected-user-email'),
            submitBtn: document.getElementById('share-submit-btn'),
            submitSpinner: document.getElementById('share-submit-spinner'),
            submitText: document.getElementById('share-submit-text'),
            existingList: document.getElementById('share-existing-list')
        };
    }

    window.openShareModal = function(bookId, bookTitle) {
        currentBookId = parseInt(bookId, 10) || 0;
        if (!currentBookId) {
            return;
        }

        var els = getElements();
        if (!els.modal) {
            return;
        }

        if (els.bookTitle) {
            els.bookTitle.textContent = bookTitle || 'Libro #' + currentBookId;
        }

        clearSelectedShareUser();
        if (els.searchInput) {
            els.searchInput.value = '';
        }
        if (els.dropdown) {
            els.dropdown.classList.add('hidden');
            els.dropdown.innerHTML = '';
        }

        els.modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Animate modal open
        setTimeout(function() {
            if (els.panel) {
                els.panel.classList.remove('opacity-0', 'scale-95');
                els.panel.classList.add('opacity-100', 'scale-100');
            }
            if (els.searchInput) {
                els.searchInput.focus();
            }
        }, 10);

        loadBookShares(currentBookId);
    };

    window.closeShareModal = function() {
        var els = getElements();
        if (!els.modal) {
            return;
        }

        if (els.panel) {
            els.panel.classList.remove('opacity-100', 'scale-100');
            els.panel.classList.add('opacity-0', 'scale-95');
        }

        setTimeout(function() {
            els.modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            currentBookId = 0;
            clearSelectedShareUser();
        }, 200);
    };

    window.clearSelectedShareUser = function() {
        selectedUser = null;
        var els = getElements();
        if (els.selectedBox) {
            els.selectedBox.classList.add('hidden');
        }
        if (els.submitBtn) {
            els.submitBtn.disabled = true;
        }
        if (els.searchInput) {
            els.searchInput.value = '';
        }
    };

    function selectShareUser(user) {
        selectedUser = user;
        var els = getElements();

        if (els.dropdown) {
            els.dropdown.classList.add('hidden');
        }

        if (els.selectedAvatar) {
            els.selectedAvatar.src = user.avatar || '';
        }
        if (els.selectedName) {
            els.selectedName.textContent = user.name || user.login || '';
        }
        if (els.selectedEmail) {
            els.selectedEmail.textContent = user.email ? user.email : '@' + (user.login || '');
        }

        if (els.selectedBox) {
            els.selectedBox.classList.remove('hidden');
        }
        if (els.submitBtn) {
            els.submitBtn.disabled = false;
        }
    }

    function searchUsers(term) {
        var els = getElements();
        var config = window.almadenShareConfig || {};

        if (!config.ajaxUrl || !config.nonce || !currentBookId) {
            return;
        }

        if (!term || term.trim().length < 1) {
            if (els.dropdown) {
                els.dropdown.classList.add('hidden');
                els.dropdown.innerHTML = '';
            }
            return;
        }

        if (els.searchSpinner) {
            els.searchSpinner.classList.remove('hidden');
        }

        var formData = new FormData();
        formData.append('action', 'almaden_search_users_for_share');
        formData.append('nonce', config.nonce);
        formData.append('book_id', currentBookId);
        formData.append('term', term);

        fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(res) {
            return res.json();
        })
        .then(function(data) {
            if (els.searchSpinner) {
                els.searchSpinner.classList.add('hidden');
            }

            if (!els.dropdown) {
                return;
            }

            els.dropdown.innerHTML = '';

            if (data && data.success && Array.isArray(data.data.users) && data.data.users.length > 0) {
                data.data.users.forEach(function(user) {
                    var item = document.createElement('div');
                    item.className = 'p-3 hover:bg-slate-50 cursor-pointer flex items-center gap-3 transition';
                    
                    var avatarImg = document.createElement('img');
                    avatarImg.src = user.avatar;
                    avatarImg.className = 'w-7 h-7 rounded-full object-cover shrink-0';
                    avatarImg.alt = '';

                    var textWrap = document.createElement('div');
                    textWrap.className = 'min-w-0 flex-1';

                    var nameP = document.createElement('p');
                    nameP.className = 'text-xs font-semibold text-slate-900 truncate';
                    nameP.textContent = user.name;

                    var metaP = document.createElement('p');
                    metaP.className = 'text-[11px] text-slate-500 truncate';
                    metaP.textContent = user.email ? user.email : '@' + user.login;

                    textWrap.appendChild(nameP);
                    textWrap.appendChild(metaP);

                    item.appendChild(avatarImg);
                    item.appendChild(textWrap);

                    item.addEventListener('click', function() {
                        selectShareUser(user);
                    });

                    els.dropdown.appendChild(item);
                });
                els.dropdown.classList.remove('hidden');
            } else {
                var emptyMsg = document.createElement('div');
                emptyMsg.className = 'p-4 text-xs text-slate-500 text-center font-medium';
                emptyMsg.textContent = 'No se encontraron usuarios con ese nombre o correo';
                els.dropdown.appendChild(emptyMsg);
                els.dropdown.classList.remove('hidden');
            }
        })
        .catch(function() {
            if (els.searchSpinner) {
                els.searchSpinner.classList.add('hidden');
            }
        });
    }

    function renderExistingShares(shares) {
        var els = getElements();
        if (!els.existingList) {
            return;
        }

        els.existingList.innerHTML = '';

        if (!Array.isArray(shares) || shares.length === 0) {
            var emptyRow = document.createElement('p');
            emptyRow.className = 'text-xs text-slate-400 italic py-1';
            emptyRow.textContent = 'Este libro no está compartido con otros usuarios.';
            els.existingList.appendChild(emptyRow);
            return;
        }

        shares.forEach(function(share) {
            var row = document.createElement('div');
            row.className = 'flex items-center justify-between p-2.5 bg-slate-50/70 border border-slate-100 rounded-xl';

            var left = document.createElement('div');
            left.className = 'flex items-center gap-2.5 min-w-0';

            var avatar = document.createElement('img');
            avatar.src = share.avatar_url;
            avatar.className = 'w-7 h-7 rounded-full object-cover shrink-0';
            avatar.alt = '';

            var info = document.createElement('div');
            info.className = 'min-w-0';

            var nameSpan = document.createElement('p');
            nameSpan.className = 'text-xs font-semibold text-slate-800 truncate';
            nameSpan.textContent = share.display_name;

            var emailSpan = document.createElement('p');
            emailSpan.className = 'text-[11px] text-slate-500 truncate';
            emailSpan.textContent = share.user_email ? share.user_email : '@' + share.user_login;

            info.appendChild(nameSpan);
            info.appendChild(emailSpan);

            left.appendChild(avatar);
            left.appendChild(info);

            var right = document.createElement('div');
            right.className = 'flex items-center gap-2 shrink-0 ml-2';

            var badge = document.createElement('span');
            badge.className = 'text-[10px] font-semibold text-slate-500 uppercase tracking-wider bg-slate-200/60 px-2 py-0.5 rounded-full';
            badge.textContent = 'Editor';

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'text-slate-400 hover:text-red-500 p-1 transition rounded-lg hover:bg-white';
            removeBtn.title = 'Revocar acceso';
            removeBtn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>';

            removeBtn.addEventListener('click', function() {
                if (confirm('¿Deseas revocar el acceso a ' + share.display_name + '?')) {
                    unshareBook(share.user_id);
                }
            });

            right.appendChild(badge);
            right.appendChild(removeBtn);

            row.appendChild(left);
            row.appendChild(right);

            els.existingList.appendChild(row);
        });
    }

    function loadBookShares(bookId) {
        var els = getElements();
        var config = window.almadenShareConfig || {};

        if (!config.ajaxUrl || !config.nonce || !bookId) {
            return;
        }

        if (els.existingList) {
            els.existingList.innerHTML = '<p class="text-xs text-slate-400 py-1"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Cargando usuarios con acceso...</p>';
        }

        var formData = new FormData();
        formData.append('action', 'almaden_get_book_shares');
        formData.append('nonce', config.nonce);
        formData.append('book_id', bookId);

        fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(res) {
            return res.json();
        })
        .then(function(data) {
            if (data && data.success && Array.isArray(data.data.shares)) {
                renderExistingShares(data.data.shares);
            } else {
                renderExistingShares([]);
            }
        })
        .catch(function() {
            renderExistingShares([]);
        });
    }

    window.submitShareBook = function() {
        if (!selectedUser || !currentBookId) {
            return;
        }

        var els = getElements();
        var config = window.almadenShareConfig || {};

        if (els.submitBtn) {
            els.submitBtn.disabled = true;
        }
        if (els.submitSpinner) {
            els.submitSpinner.classList.remove('hidden');
        }
        if (els.submitText) {
            els.submitText.textContent = 'Compartiendo...';
        }

        var formData = new FormData();
        formData.append('action', 'almaden_share_book');
        formData.append('nonce', config.nonce);
        formData.append('book_id', currentBookId);
        formData.append('target_user_id', selectedUser.id);

        fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(res) {
            return res.json();
        })
        .then(function(data) {
            if (els.submitSpinner) {
                els.submitSpinner.classList.add('hidden');
            }
            if (els.submitText) {
                els.submitText.textContent = 'Compartir';
            }

            if (data && data.success) {
                clearSelectedShareUser();
                if (Array.isArray(data.data.shares)) {
                    renderExistingShares(data.data.shares);
                } else {
                    loadBookShares(currentBookId);
                }
            } else {
                alert((data && data.data && data.data.message) ? data.data.message : 'Error al compartir libro.');
                if (els.submitBtn) {
                    els.submitBtn.disabled = false;
                }
            }
        })
        .catch(function(err) {
            if (els.submitSpinner) {
                els.submitSpinner.classList.add('hidden');
            }
            if (els.submitText) {
                els.submitText.textContent = 'Compartir';
            }
            if (els.submitBtn) {
                els.submitBtn.disabled = false;
            }
            alert('Error de conexión al compartir el libro.');
        });
    };

    function unshareBook(targetUserId) {
        var config = window.almadenShareConfig || {};
        if (!config.ajaxUrl || !config.nonce || !currentBookId || !targetUserId) {
            return;
        }

        var formData = new FormData();
        formData.append('action', 'almaden_unshare_book');
        formData.append('nonce', config.nonce);
        formData.append('book_id', currentBookId);
        formData.append('target_user_id', targetUserId);

        fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(res) {
            return res.json();
        })
        .then(function(data) {
            if (data && data.success && Array.isArray(data.data.shares)) {
                renderExistingShares(data.data.shares);
            } else {
                loadBookShares(currentBookId);
            }
        })
        .catch(function() {
            alert('Error al desvincular usuario.');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var els = getElements();

        if (els.searchInput) {
            els.searchInput.addEventListener('input', function(e) {
                var term = e.target.value;
                if (searchDebounceTimer) {
                    clearTimeout(searchDebounceTimer);
                }
                searchDebounceTimer = setTimeout(function() {
                    searchUsers(term);
                }, 250);
            });

            els.searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    if (els.dropdown && !els.dropdown.classList.contains('hidden')) {
                        els.dropdown.classList.add('hidden');
                        e.stopPropagation();
                    } else {
                        closeShareModal();
                    }
                }
            });
        }

        document.addEventListener('click', function(e) {
            if (els.dropdown && !els.dropdown.classList.contains('hidden')) {
                if (!els.dropdown.contains(e.target) && e.target !== els.searchInput) {
                    els.dropdown.classList.add('hidden');
                }
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && els.modal && !els.modal.classList.contains('hidden')) {
                closeShareModal();
            }
        });
    });
})();
