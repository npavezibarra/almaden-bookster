<!-- Modal de Guía de Nomenclatura -->
<div id="chapter-nomenclature-modal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm opacity-0 transition-opacity no-print" style="z-index: 60;" onclick="if (event.target === this) closeChapterNomenclatureGuideModal();">
    <div class="absolute left-1/2 top-6 w-[min(920px,calc(100vw-1.5rem))] -translate-x-1/2 rounded-2xl border border-[var(--border-color)] bg-[var(--bg-app)] shadow-2xl scale-95 transform transition-transform duration-200 overflow-hidden flex flex-col max-h-[calc(100vh-1.5rem)]">
        <div class="flex items-start justify-between gap-4 border-b border-[var(--border-color)] bg-[var(--bg-sidebar)] px-6 py-4">
            <div>
                <h3 class="text-lg font-bold text-[var(--text-main)] flex items-center gap-2">
                    <i class="fa-solid fa-circle-question text-black dark:text-white"></i>
                    Guía rápida de nomenclatura
                </h3>
                <p class="mt-1 text-xs text-[var(--text-muted)]">
                    Usa esta sintaxis para que un LLM genere texto listo para pegar en el editor raw.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="copyChapterNomenclatureGuide()" class="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-neutral-800 transition">
                    <i class="fa-solid fa-copy"></i>
                    <span>Copiar guía</span>
                </button>
                <button type="button" onclick="closeChapterNomenclatureGuideModal()" class="p-2 text-[var(--text-muted)] hover:text-rose-500 transition-colors" aria-label="Cerrar guía">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            <div class="space-y-4 text-sm text-[var(--text-main)]">
                <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] p-4">
                    <p class="font-semibold mb-2">1. Idioma extranjero</p>
                    <p class="text-xs text-[var(--text-muted)] mb-3">Forma preferida para contenido en otro idioma. Mantiene la semántica y ayuda al PDF con la hyphenation.</p>
                    <pre class="overflow-x-auto rounded-lg bg-[var(--bg-app)] p-3 font-mono text-[11px] leading-5 text-[var(--text-main)]">Ejemplo:
&lt;foreign lang="la"&gt;carpe diem&lt;/foreign&gt;

Lenguajes principales:
es = español
en = inglés
fr = francés
de = alemán
it = italiano
pt = portugués</pre>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] p-4">
                        <p class="font-semibold mb-2">2. Citas</p>
                        <p class="text-xs text-[var(--text-muted)] mb-3">Para citas textuales usa Markdown de bloque.</p>
                        <pre class="overflow-x-auto rounded-lg bg-[var(--bg-app)] p-3 font-mono text-[11px] leading-5 text-[var(--text-main)]">&gt; Esta es una cita.
&gt; Puede ocupar varias líneas.</pre>
                    </div>

                    <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] p-4">
                        <p class="font-semibold mb-2">3. Notas al pie</p>
                        <p class="text-xs text-[var(--text-muted)] mb-3">La referencia va dentro del texto y la definición al final.</p>
                        <pre class="overflow-x-auto rounded-lg bg-[var(--bg-app)] p-3 font-mono text-[11px] leading-5 text-[var(--text-main)]">Texto con nota[^1].

[^1]: Explicación de la nota al pie.</pre>
                    </div>
                </div>

                <div class="rounded-xl border border-[var(--border-color)] bg-[var(--bg-sidebar)] p-4">
                    <p class="font-semibold mb-2">4. Maquetación</p>
                    <p class="text-xs text-[var(--text-muted)] mb-3">Úsalos sólo cuando cambie la estructura visual del capítulo.</p>
                    <pre class="overflow-x-auto rounded-lg bg-[var(--bg-app)] p-3 font-mono text-[11px] leading-5 text-[var(--text-main)]">[box]
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
&lt;div&gt;HTML crudo&lt;/div&gt;
[/html]</pre>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
                    <p class="font-semibold mb-1">Regla recomendada</p>
                    <p class="text-xs leading-5">
                        Si existe una forma canónica, usa siempre esa. En idioma, la forma preferida es <code class="rounded bg-amber-100 px-1 py-0.5">&lt;foreign lang="xx"&gt;</code>.
                        Los alias viejos se aceptan para compatibilidad, pero no deberían salir en contenido nuevo.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
