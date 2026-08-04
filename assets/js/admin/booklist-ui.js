        // Modal functions
        const modal = document.getElementById('create-modal');
        const modalPanel = document.getElementById('modal-panel');
        const openBtn = document.getElementById('open-modal-btn');
        const closeBtn = document.getElementById('close-modal-btn');
        const cancelBtn = document.getElementById('cancel-modal-btn');
        
        function openModal() {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalPanel.classList.remove('opacity-0', 'scale-95');
                modalPanel.classList.add('opacity-100', 'scale-100');
            }, 10);
            document.getElementById('book_title').focus();
        }

        function closeModal() {
            modalPanel.classList.remove('opacity-100', 'scale-100');
            modalPanel.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        if (openBtn) openBtn.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        // Scale Cover Thumbnails
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
        // Call twice: once immediately, once on full load
        scaleThumbnails();
        window.addEventListener('load', scaleThumbnails);

        function closeAddModal() {
            document.getElementById('add-book-modal').classList.add('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            var isClickInside = false;
            document.querySelectorAll('.dropdown-container').forEach(function(container) {
                if (container.contains(event.target)) {
                    isClickInside = true;
                } else {
                    var dropdown = container.querySelector('.hidden.absolute');
                    if(dropdown && !dropdown.classList.contains('hidden')){
                        dropdown.classList.add('hidden');
                    }
                }
            });
            if(isClickInside) {
                // If a button was clicked, ensure others are closed
                var targetBtn = event.target.closest('button');
                if(targetBtn) {
                    var targetContainer = targetBtn.closest('.dropdown-container');
                    document.querySelectorAll('.dropdown-container').forEach(function(container) {
                        if(container !== targetContainer) {
                            var dropdown = container.querySelector('.hidden.absolute');
                            if(dropdown && !dropdown.classList.contains('hidden')){
                                dropdown.classList.add('hidden');
                            }
                        }
                    });
                }
            }
        });

        // Function to load and open settings
        function openBookSettings(bookId, nonce) {
            const spinner = document.querySelector('.settings-spinner-' + bookId);
            if (spinner) spinner.classList.remove('hidden');
            
            const formData = new FormData();
            formData.append('action', 'almaden_get_book_settings');
            formData.append('book_id', bookId);
            formData.append('nonce', nonce);

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (spinner) spinner.classList.add('hidden');
                if (data.success) {
                    bookState.bookId = bookId;
                    bookState.settings = data.data.settings;
                    bookState.bookAuthorsInputValue = data.data.book_authors_input_value || '';
                    bookState.settingsNonce = nonce;
                    toggleSettingsModal(true);
                } else {
                    alert('Error cargando los ajustes.');
                }
            })
            .catch(err => {
                if (spinner) spinner.classList.add('hidden');
                alert('Error de conexión.');
            });
        }

        // Function to export book to Google Drive
        function uploadBookToDrive(bookId) {
            const spinner = document.querySelector('.drive-spinner-' + bookId);
            const textSpan = document.querySelector('.drive-text-' + bookId);
            
            if (spinner) spinner.classList.remove('hidden');
            if (textSpan) textSpan.textContent = 'Subiendo...';
            
            const formData = new FormData();
            formData.append('action', 'almaden_export_book_to_drive');
            formData.append('book_id', bookId);
            // Reusing the general admin-ajax architecture. We can rely on user capabilities in backend.

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (spinner) spinner.classList.add('hidden');
                if (textSpan) textSpan.textContent = 'Subir a Google Drive';
                
                if (data.success) {
                    alert('¡Éxito! ' + data.data);
                } else {
                    alert('Error: ' + data.data);
                }
            })
            .catch(err => {
                if (spinner) spinner.classList.add('hidden');
                if (textSpan) textSpan.textContent = 'Subir a Google Drive';
                alert('Error de red al intentar subir a Google Drive.');
            });
        }

        // Function to toggle publish status
        function togglePublishBook(bookId, isPublished) {
            const checkbox = document.getElementById('publish-toggle-' + bookId);
            if (checkbox && checkbox.disabled) {
                return;
            }

            const spinner = document.querySelector('.publish-spinner-' + bookId);
            const textSpan = document.querySelector('.publish-text-' + bookId);
            
            if (spinner) spinner.classList.remove('hidden');
            
            const action = isPublished ? 'unpublish' : 'publish';
            const formData = new FormData();
            formData.append('action', 'almaden_toggle_publish_book');
            formData.append('book_id', bookId);
            formData.append('publish_action', action);

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload to show updated status UI
                } else {
                    if (spinner) spinner.classList.add('hidden');
                    alert('Error: ' + data.data);
                }
            })
            .catch(err => {
                if (spinner) spinner.classList.add('hidden');
                alert('Error de red.');
            });
        }
