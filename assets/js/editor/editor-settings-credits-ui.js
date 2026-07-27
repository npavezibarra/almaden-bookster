function creditsGetFieldValue(root, selector, fallback = '') {
    const el = root ? root.querySelector(selector) : null;
    return el ? el.value : fallback;
}

function creditsGetFieldChecked(root, selector) {
    const el = root ? root.querySelector(selector) : null;
    return el && el.checked ? 1 : 0;
}

function creditsSetInputValue(root, selector, value) {
    const el = root ? root.querySelector(selector) : null;
    if (!el) return;
    el.value = value ?? '';
}

function creditsSetCheckboxValue(root, selector, value) {
    const el = root ? root.querySelector(selector) : null;
    if (!el) return;
    el.checked = value === 1 || value === '1' || value === true;
}

function creditsBuildPersonRow(person = {}) {
    const selectedRole = CREDITS_ROLE_OPTIONS.some((opt) => opt.value === String(person.role || 'author'))
        ? String(person.role || 'author')
        : 'author';
    return `
        <div class="credits-person-row rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/40 p-4 space-y-4" data-credits-row="person">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Nombre</label>
                    <input type="text" data-credits-field="name" value="${creditsEscapeHtml(person.name || '')}" placeholder="Fernando Villegas" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Rol</label>
                    <select data-credits-field="role" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                        ${creditsOptionMarkup(CREDITS_ROLE_OPTIONS, selectedRole)}
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Email</label>
                    <input type="email" data-credits-field="email" value="${creditsEscapeHtml(person.email || '')}" placeholder="correo@dominio.com" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Sitio Web</label>
                    <input type="url" data-credits-field="website" value="${creditsEscapeHtml(person.website || '')}" placeholder="https://..." class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                </div>
            </div>
            <div class="flex items-center justify-between gap-4 pt-1">
                <label class="inline-flex items-center gap-3 text-sm text-[var(--text-main)] font-medium">
                    <input type="checkbox" data-credits-field="show_contact" ${person.show_contact ? 'checked' : ''} class="h-4 w-4 rounded border-[var(--border-color)] text-black focus:ring-black">
                    Mostrar contacto en página de créditos
                </label>
                <button type="button" data-credits-action="remove-row" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-100 transition">
                    <i class="fa-solid fa-trash-can"></i>
                    Eliminar
                </button>
            </div>
        </div>
    `;
}

function creditsBuildCollaboratorRow(item = {}) {
    const logoUrl = item.logo_url || '';
    return `
        <div class="credits-collaborator-row rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/40 p-4 space-y-4" data-credits-row="collaborator">
            <div class="grid grid-cols-1 md:grid-cols-[160px_1fr] gap-4">
                <div class="space-y-2">
                    <div class="overflow-hidden rounded-xl border border-[var(--border-color)] bg-white p-2">
                        <img data-credits-preview-image src="${logoUrl ? creditsEscapeHtml(logoUrl) : 'data:image/gif;base64,R0lGODlhAQABAAAAACw='}" alt="" class="h-16 w-full rounded-xl object-contain bg-white border border-[var(--border-color)] p-2${logoUrl ? '' : ' hidden'}">
                        <div data-credits-preview-placeholder class="flex h-16 w-full items-center justify-center rounded-xl border border-dashed border-[var(--border-color)] bg-white text-[11px] font-semibold text-[var(--text-muted)]${logoUrl ? ' hidden' : ''}">Sin logo</div>
                    </div>
                    <button type="button" data-credits-action="choose-image" class="w-full rounded-xl bg-black px-3 py-2 text-xs font-semibold text-white hover:bg-neutral-800 transition" data-credits-target-input="logo_url" data-credits-target-preview="[data-credits-preview-image]">
                        <i class="fa-solid fa-image mr-1"></i>
                        Seleccionar logo
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Nombre empresa</label>
                        <input type="text" data-credits-field="name" value="${creditsEscapeHtml(item.name || '')}" placeholder="Editorial XXXXX" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Tipo</label>
                        <select data-credits-field="type" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            ${creditsOptionMarkup(CREDITS_COMPANY_TYPE_OPTIONS, item.type || 'company')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Sitio Web</label>
                        <input type="url" data-credits-field="website" value="${creditsEscapeHtml(item.website || '')}" placeholder="https://..." class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Texto opcional</label>
                        <input type="text" data-credits-field="text" value="${creditsEscapeHtml(item.text || '')}" placeholder="Agradecimiento o detalle" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">URL de logo</label>
                        <input type="url" data-credits-field="logo_url" value="${creditsEscapeHtml(item.logo_url || '')}" placeholder="https://..." class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" data-credits-action="remove-row" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-100 transition">
                    <i class="fa-solid fa-trash-can"></i>
                    Eliminar
                </button>
            </div>
        </div>
    `;
}

function creditsBuildLogoRow(item = {}) {
    const logoUrl = item.logo_url || '';
    const logoPosition = creditsNormalizeLogoPosition(item.position || 'center');
    const logoSize = creditsNormalizeLogoSize(item.size_px || 120);
    const logoJustify = creditsLogoPositionJustify(logoPosition);
    const hasLogo = !!logoUrl;
    return `
        <div class="credits-logo-row rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/40 p-4 space-y-4" data-credits-row="logo">
            <div class="grid grid-cols-1 md:grid-cols-[190px_1fr] gap-4">
                <div class="space-y-2">
                    <div data-credits-logo-preview-box class="flex min-h-36 w-full overflow-hidden rounded-xl border border-[var(--border-color)] bg-white p-3" style="justify-content: ${logoJustify}; align-items: center;">
                        <img data-credits-preview-image src="${hasLogo ? creditsEscapeHtml(logoUrl) : 'data:image/gif;base64,R0lGODlhAQABAAAAACw='}" alt="" class="rounded-xl object-contain bg-white border border-[var(--border-color)] p-2${hasLogo ? '' : ' hidden'}" style="width: ${logoSize}px; height: auto; max-width: 100%;">
                        <div data-credits-preview-placeholder class="flex h-20 w-full items-center justify-center rounded-xl border border-dashed border-[var(--border-color)] bg-white text-[11px] font-semibold text-[var(--text-muted)]${hasLogo ? ' hidden' : ''}">Sin imagen</div>
                    </div>
                    <button type="button" data-credits-action="choose-image" class="w-full rounded-xl bg-black px-3 py-2 text-xs font-semibold text-white hover:bg-neutral-800 transition" data-credits-target-input="logo_url" data-credits-target-preview="[data-credits-preview-image]">
                        <i class="fa-solid fa-image mr-1"></i>
                        Subir logo
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Nombre</label>
                        <input type="text" data-credits-field="name" value="${creditsEscapeHtml(item.name || '')}" placeholder="Logo editorial" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Posición</label>
                        <select data-credits-field="position" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            ${creditsOptionMarkup(CREDITS_LOGO_POSITION_OPTIONS, logoPosition)}
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <label class="block text-[11px] font-semibold text-[var(--text-muted)]">Tamaño (px)</label>
                            <span data-credits-size-label class="text-[11px] font-semibold text-[var(--text-muted)]">${logoSize} px</span>
                        </div>
                        <input type="range" data-credits-field="size_px" min="24" max="400" step="1" value="${creditsEscapeHtml(logoSize)}" class="w-full accent-black">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">URL</label>
                        <input type="url" data-credits-field="url" value="${creditsEscapeHtml(item.url || '')}" placeholder="https://..." class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">URL de imagen</label>
                        <input type="url" data-credits-field="logo_url" value="${creditsEscapeHtml(item.logo_url || '')}" placeholder="https://..." class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" data-credits-action="remove-row" class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-100 transition">
                    <i class="fa-solid fa-trash-can"></i>
                    Eliminar
                </button>
            </div>
        </div>
    `;
}

function creditsBuildEditorMarkup(config) {
    if (typeof creditsBuildAdvancedEditorMarkup === 'function') {
        return creditsBuildAdvancedEditorMarkup(config);
    }

    const editorial = config.editorial || creditsGetDefaultConfig().editorial;
    const legal = config.legal || creditsGetDefaultConfig().legal;
    const people = Array.isArray(config.people) ? config.people : [];
    const collaborators = Array.isArray(config.collaborators) ? config.collaborators : [];
    const logos = Array.isArray(config.logos) ? config.logos : [];
    const collaboratorsVisible = config.collaborators_visible === 1 || config.collaborators_visible === '1' || config.collaborators_visible === true;

    return `
        <div class="border border-[var(--border-color)] rounded-[28px] bg-[var(--bg-app)] shadow-sm overflow-hidden">
            <div class="border-b border-[var(--border-color)] px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="h-11 w-11 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center">
                        <i class="fa-solid fa-copyright"></i>
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-black dark:text-white">Configuración de Página de Créditos</h4>
                        <p class="text-xs text-[var(--text-muted)]">Edita la información estructurada que se usará para generar la página de créditos.</p>
                    </div>
                </div>
            </div>

            <div class="px-6 pt-5">
                <div class="flex flex-wrap gap-2 border-b border-[var(--border-color)] pb-4" data-credits-tabs>
                    <button type="button" data-credits-tab="editorial" class="credits-tab-btn rounded-xl border px-4 py-2 text-sm font-semibold transition bg-black text-white border-black">Editorial</button>
                    <button type="button" data-credits-tab="people" class="credits-tab-btn rounded-xl border px-4 py-2 text-sm font-semibold transition bg-white text-[var(--text-main)] border-[var(--border-color)]">Personas</button>
                    <button type="button" data-credits-tab="collaborators" class="credits-tab-btn rounded-xl border px-4 py-2 text-sm font-semibold transition bg-white text-[var(--text-main)] border-[var(--border-color)]">Colaboradores</button>
                    <button type="button" data-credits-tab="logos" class="credits-tab-btn rounded-xl border px-4 py-2 text-sm font-semibold transition bg-white text-[var(--text-main)] border-[var(--border-color)]">Logos</button>
                    <button type="button" data-credits-tab="legal" class="credits-tab-btn rounded-xl border px-4 py-2 text-sm font-semibold transition bg-white text-[var(--text-main)] border-[var(--border-color)]">Legal</button>
                </div>
            </div>

            <div class="px-6 py-6 space-y-6">
                <section data-credits-panel="editorial" class="space-y-5">
                    <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/30 p-5">
                        <h5 class="text-lg font-bold text-[var(--text-main)]">Editorial</h5>
                        <p class="mt-1 text-sm text-[var(--text-muted)]">Datos generales del libro que aparecerán en la página de créditos.</p>
                        <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Número de edición</label>
                                <input id="setting-credits-edition" data-credits-field="edition_number" type="text" placeholder="Primera edición" value="${creditsEscapeHtml(editorial.edition_number || '')}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Fecha de publicación</label>
                                <input id="setting-credits-date" data-credits-field="publication_date" type="month" value="${creditsEscapeHtml(creditsNormalizePublicationDate(editorial.publication_date || ''))}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">ISBN</label>
                                <input id="setting-credits-isbn" data-credits-field="isbn" type="text" placeholder="978-..." value="${creditsEscapeHtml(editorial.isbn || '')}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Imprenta</label>
                                <input id="setting-credits-printer" data-credits-field="printer" type="text" placeholder="Impreso en..." value="${creditsEscapeHtml(editorial.printer || '')}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Páginas blancas iniciales</label>
                                <input id="setting-credits-blank-before" data-credits-field="blank_before" type="number" min="0" value="${creditsEscapeHtml(editorial.blank_before ?? 0)}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Páginas blancas finales</label>
                                <input id="setting-credits-blank-after" data-credits-field="blank_after" type="number" min="0" value="${creditsEscapeHtml(editorial.blank_after ?? 0)}" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                        </div>
                    </div>
                </section>

                <section data-credits-panel="people" class="hidden space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h5 class="text-lg font-bold text-[var(--text-main)]">Personas</h5>
                            <p class="text-sm text-[var(--text-muted)]">Autores, editores y demás roles personales del libro.</p>
                        </div>
                        <button type="button" data-credits-action="add-person" class="rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-800 transition">
                            <i class="fa-solid fa-plus mr-1"></i> Agregar persona
                        </button>
                    </div>
                    <div id="credits-people-container" class="space-y-3">
                        ${people.length ? people.map((person) => creditsBuildPersonRow(person)).join('') : creditsBuildPersonRow()}
                    </div>
                </section>

                <section data-credits-panel="collaborators" class="hidden space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h5 class="text-lg font-bold text-[var(--text-main)]">Colaboradores</h5>
                            <p class="text-sm text-[var(--text-muted)]">Sellos, fundaciones, mecenas o entidades asociadas.</p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <label class="inline-flex items-center gap-3 text-sm font-semibold text-[var(--text-main)]">
                                <input type="checkbox" data-credits-field="collaborators_visible" ${collaboratorsVisible ? 'checked' : ''} class="h-5 w-5 rounded border-[var(--border-color)] text-black focus:ring-black">
                                Visible
                            </label>
                            <button type="button" data-credits-action="add-collaborator" ${collaboratorsVisible ? '' : 'disabled'} class="rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-800 transition ${collaboratorsVisible ? '' : 'opacity-50 cursor-not-allowed'}">
                                <i class="fa-solid fa-plus mr-1"></i> Agregar colaborador
                            </button>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/30 p-5 space-y-4">
                        <div data-credits-collaborators-body class="${collaboratorsVisible ? '' : 'pointer-events-none opacity-50'} space-y-4">
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Título de la sección</label>
                                <input type="text" data-credits-field="collaborators_title" value="${creditsEscapeHtml(config.collaborators_title || '')}" placeholder="Colaboradores" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                            </div>
                            <div id="credits-collaborators-container" class="space-y-3">
                                ${collaborators.length ? collaborators.map((item) => creditsBuildCollaboratorRow(item)).join('') : creditsBuildCollaboratorRow()}
                            </div>
                        </div>
                        <p class="text-[11px] text-[var(--text-muted)]">Si lo ocultas, el contenido se mantiene guardado pero no se mostrará en PDF ni en ebook.</p>
                    </div>
                </section>

                <section data-credits-panel="logos" class="hidden space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h5 class="text-lg font-bold text-[var(--text-main)]">Logos</h5>
                            <p class="text-sm text-[var(--text-muted)]">Logos adicionales que quieres registrar para la página de créditos.</p>
                        </div>
                        <button type="button" data-credits-action="add-logo" class="rounded-xl bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-neutral-800 transition">
                            <i class="fa-solid fa-plus mr-1"></i> Agregar logo
                        </button>
                    </div>
                    <div id="credits-logos-container" class="space-y-3">
                        ${logos.length ? logos.map((item) => creditsBuildLogoRow(item)).join('') : ''}
                    </div>
                </section>

                <section data-credits-panel="legal" class="hidden space-y-4">
                    <div class="rounded-2xl border border-[var(--border-color)] bg-[var(--bg-sidebar)]/30 p-5">
                        <h5 class="text-lg font-bold text-[var(--text-main)]">Legal</h5>
                        <p class="mt-1 text-sm text-[var(--text-muted)]">Texto legal y tipo de licencia para esta edición.</p>
                        <div class="mt-5 space-y-4">
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Texto Copyright</label>
                                <textarea id="setting-credits-copyright" data-credits-field="copyright_text" rows="4" class="w-full rounded-2xl border border-[var(--border-color)] bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-black resize-y">${creditsEscapeHtml(legal.copyright_text || '')}</textarea>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-[var(--text-muted)] mb-1">Licencia</label>
                                <select id="setting-credits-license" data-credits-field="license" class="w-full rounded-xl border border-[var(--border-color)] bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black">
                                    ${creditsOptionMarkup(CREDITS_LICENSE_OPTIONS, legal.license || 'all_rights_reserved')}
                                </select>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    `;
}

function creditsRenderTabState(root, activeTab) {
    const tabs = root ? root.querySelectorAll('[data-credits-tab]') : [];
    const panels = root ? root.querySelectorAll('[data-credits-panel]') : [];
    tabs.forEach((tab) => {
        const isActive = tab.getAttribute('data-credits-tab') === activeTab;
        tab.classList.toggle('bg-black', isActive);
        tab.classList.toggle('text-white', isActive);
        tab.classList.toggle('border-black', isActive);
        tab.classList.toggle('bg-white', !isActive);
        tab.classList.toggle('text-[var(--text-main)]', !isActive);
        tab.classList.toggle('border-[var(--border-color)]', !isActive);
    });
    panels.forEach((panel) => {
        panel.classList.toggle('hidden', panel.getAttribute('data-credits-panel') !== activeTab);
    });
    if (root) {
        root.dataset.activeCreditsTab = activeTab;
    }
}

function creditsAppendRow(rowType, data = {}) {
    const containerMap = {
        person: '#credits-people-container',
        collaborator: '#credits-collaborators-container',
        logo: '#credits-logos-container',
    };
    const renderMap = {
        person: creditsBuildPersonRow,
        collaborator: creditsBuildCollaboratorRow,
        logo: creditsBuildLogoRow,
    };
    const container = document.querySelector(containerMap[rowType] || '');
    if (!container || !renderMap[rowType]) return;
    container.insertAdjacentHTML('beforeend', renderMap[rowType](data));
    creditsSyncStateFromForm();
}

function creditsEnsureMediaFrame(onSelect) {
    if (typeof wp === 'undefined' || !wp.media) {
        alert('La biblioteca multimedia de WordPress no está disponible.');
        return null;
    }

    creditsMediaFrame = wp.media({
        title: 'Seleccionar imagen',
        button: {
            text: 'Usar imagen',
        },
        multiple: false,
    });

    creditsMediaFrame.on('select', () => {
        const attachment = creditsMediaFrame.state().get('selection').first().toJSON();
        if (attachment && attachment.url && typeof onSelect === 'function') {
            onSelect(attachment.url);
        }
    });

    return creditsMediaFrame;
}

function creditsUpdateImagePreview(inputEl) {
    if (!inputEl) return;
    const row = inputEl.closest('[data-credits-row]');
    if (!row) return;
    const preview = row.querySelector('[data-credits-preview-image]');
    const placeholder = row.querySelector('[data-credits-preview-placeholder]');
    const value = String(inputEl.value || '').trim();
    if (preview) {
        preview.src = value || 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
        preview.classList.toggle('hidden', !value);
    }
    if (placeholder) {
        placeholder.classList.toggle('hidden', !!value);
    }
}

function creditsUpdateLogoRowPreview(row) {
    if (!row || row.getAttribute('data-credits-row') !== 'logo') return;
    const positionInput = row.querySelector('[data-credits-field="position"]');
    const sizeInput = row.querySelector('[data-credits-field="size_px"]');
    const logoInput = row.querySelector('[data-credits-field="logo_url"]');
    const previewBox = row.querySelector('[data-credits-logo-preview-box]');
    const preview = row.querySelector('[data-credits-preview-image]');
    const placeholder = row.querySelector('[data-credits-preview-placeholder]');
    const sizeLabel = row.querySelector('[data-credits-size-label]');
    const position = creditsNormalizeLogoPosition(positionInput ? positionInput.value : 'center');
    const size = creditsNormalizeLogoSize(sizeInput ? sizeInput.value : 120);
    const hasLogo = !!(logoInput && String(logoInput.value || '').trim());

    if (previewBox) {
        previewBox.style.justifyContent = creditsLogoPositionJustify(position);
    }
    if (preview) {
        preview.style.width = `${size}px`;
        preview.style.height = 'auto';
        preview.style.maxWidth = '100%';
        preview.classList.toggle('hidden', !hasLogo);
    }
    if (placeholder) {
        placeholder.classList.toggle('hidden', hasLogo);
    }
    if (sizeLabel) {
        sizeLabel.textContent = `${size} px`;
    }
}

function creditsUpdateCollaboratorsVisibilityState(root) {
    if (!root) return;
    const toggle = root.querySelector('[data-credits-field="collaborators_visible"]');
    const body = root.querySelector('[data-credits-collaborators-body]');
    const addButton = root.querySelector('[data-credits-action="add-collaborator"]');
    if (!toggle || !body) return;

    const enabled = !!toggle.checked;
    body.classList.toggle('pointer-events-none', !enabled);
    body.classList.toggle('opacity-50', !enabled);
    body.querySelectorAll('input, select, textarea, button').forEach((element) => {
        if (element === toggle) return;
        element.disabled = !enabled;
    });

    if (addButton) {
        addButton.disabled = !enabled;
        addButton.classList.toggle('opacity-50', !enabled);
        addButton.classList.toggle('cursor-not-allowed', !enabled);
    }
}

function creditsReadRepeaterRows(containerSelector, rowType) {
    const container = document.querySelector(containerSelector);
    if (!container) return [];
    return Array.from(container.querySelectorAll(`[data-credits-row="${rowType}"]`)).map((row) => {
        const getField = (field) => {
            const input = row.querySelector(`[data-credits-field="${field}"]`);
            if (!input) return '';
            if (input.type === 'checkbox') return input.checked ? 1 : 0;
            return String(input.value || '').trim();
        };

        if (rowType === 'person') {
            const item = {
                name: getField('name'),
                role: getField('role') || 'author',
                email: getField('email'),
                website: getField('website'),
                show_contact: getField('show_contact') ? 1 : 0,
            };
            return (item.name || item.email || item.website) ? item : null;
        }

        if (rowType === 'collaborator') {
            const item = {
                logo_url: getField('logo_url'),
                name: getField('name'),
                type: getField('type') || 'company',
                website: getField('website'),
                text: getField('text'),
            };
            return (item.logo_url || item.name || item.website || item.text) ? item : null;
        }

        const item = {
            logo_url: getField('logo_url'),
            name: getField('name'),
            position: creditsNormalizeLogoPosition(getField('position') || 'center'),
            size_px: creditsNormalizeLogoSize(getField('size_px') || 120),
            url: getField('url'),
        };
        return (item.logo_url || item.name || item.url) ? item : null;
    }).filter(Boolean);
}
function creditsGetConfigFromForm() {
    const root = document.getElementById('credits-editor-root');
    if (!root) {
        return creditsNormalizeConfig(bookState.settings.credits_config || bookState.settings || {});
    }

    const config = creditsGetDefaultConfig();
    config.editorial.edition_number = creditsGetFieldValue(root, '#setting-credits-edition');
    config.editorial.publication_date = creditsNormalizePublicationDate(creditsGetFieldValue(root, '#setting-credits-date'));
    config.editorial.isbn = creditsGetFieldValue(root, '#setting-credits-isbn');
    config.editorial.printer = creditsGetFieldValue(root, '#setting-credits-printer');
    config.editorial.blank_before = parseInt(creditsGetFieldValue(root, '#setting-credits-blank-before', '0'), 10) || 0;
    config.editorial.blank_after = parseInt(creditsGetFieldValue(root, '#setting-credits-blank-after', '0'), 10) || 0;
    config.collaborators_visible = creditsGetFieldChecked(root, '[data-credits-field="collaborators_visible"]');
    config.people = creditsReadRepeaterRows('#credits-people-container', 'person');
    config.collaborators = creditsReadRepeaterRows('#credits-collaborators-container', 'collaborator');
    config.logos = creditsReadRepeaterRows('#credits-logos-container', 'logo');
    config.legal.copyright_text = creditsGetFieldValue(root, '#setting-credits-copyright', config.legal.copyright_text);
    config.legal.license = creditsGetFieldValue(root, '#setting-credits-license', 'all_rights_reserved') || 'all_rights_reserved';

    if (typeof creditsReadAdvancedCreditsConfig === 'function') {
        const advanced = creditsReadAdvancedCreditsConfig(root);
        if (advanced && typeof advanced === 'object') {
            Object.assign(config, advanced);
            if (advanced.editorial && typeof advanced.editorial === 'object') {
                config.editorial = Object.assign({}, config.editorial, advanced.editorial);
            }
            if (advanced.legal && typeof advanced.legal === 'object') {
                config.legal = Object.assign({}, config.legal, advanced.legal);
            }
            if (Array.isArray(advanced.people)) {
                config.people = advanced.people;
            }
            if (Array.isArray(advanced.collaborators)) {
                config.collaborators = advanced.collaborators;
            }
            if (Array.isArray(advanced.logos)) {
                config.logos = advanced.logos;
            }
        }
    }

    return creditsNormalizeConfig(config);
}
