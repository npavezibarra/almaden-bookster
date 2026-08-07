// assets/js/editor/editor-chapter-settings-guide.js

function getChapterNomenclatureGuideText() {
    return `Guía rápida de nomenclatura para el editor raw de Almaden Bookster

Regla general
- Usa una sola forma canónica por concepto.
- No inventes tags nuevos.
- Si existe un alias viejo, úsalo sólo para compatibilidad de lectura, no como salida preferida.

1. Idioma extranjero
Forma preferida:
<foreign lang="la">carpe diem</foreign>

Lenguajes principales:
es = español
en = inglés
fr = francés
de = alemán
it = italiano
pt = portugués

2. Citas
> Esta es una cita.
> Puede ocupar varias líneas.

3. Notas al pie
Texto con nota[^1].

[^1]: Explicación de la nota al pie.

4. Maquetación
[box]
Contenido destacado.
[/box]

[columns]
[col]Columna izquierda[/col]
[col]Columna derecha[/col]
[/columns]

[align=center]
Texto centrado.
[/align]

[gap:10mm]
[pagebreak]
[logo]

[html]
<div>HTML crudo</div>
[/html]

5. Formato inline
[size=12px]texto[/size]
[font="Merriweather"]texto[/font]

Preferencia final
- Para idioma, usa siempre <foreign lang="xx"> cuando generes contenido nuevo.
- Para citas, usa bloque Markdown con >.
- Para notas, usa [^id] y su definición al final.`;
}

function openChapterNomenclatureGuideModal() {
    const modal = document.getElementById('chapter-nomenclature-modal');
    if (!modal) return;

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        const panel = modal.querySelector('div');
        if (panel) {
            panel.classList.remove('scale-95');
            panel.classList.add('scale-100');
        }
    }, 10);
}

function closeChapterNomenclatureGuideModal() {
    const modal = document.getElementById('chapter-nomenclature-modal');
    if (!modal) return;

    modal.classList.add('opacity-0');
    const panel = modal.querySelector('div');
    if (panel) {
        panel.classList.remove('scale-100');
        panel.classList.add('scale-95');
    }

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function copyChapterNomenclatureGuide() {
    const text = getChapterNomenclatureGuideText();
    const done = () => {
        if (typeof showToast === 'function') {
            showToast('Guía copiada al portapapeles.', 'fa-solid fa-copy');
        }
    };
    const failed = () => {
        if (typeof showToast === 'function') {
            showToast('No se pudo copiar la guía.', 'fa-solid fa-triangle-exclamation');
        }
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(done).catch(failed);
        return;
    }

    try {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', 'true');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        const ok = document.execCommand('copy');
        textarea.remove();
        if (ok) {
            done();
        } else {
            failed();
        }
    } catch (error) {
        failed();
    }
}
