const SEMANTIC_FIELDS = [
    {
        key: 'title_style',
        label: 'Title / Título',
        description: 'Nivel principal dentro del capítulo.'
    },
    {
        key: 'subtitle_style',
        label: 'Subtitle / Subtítulo',
        description: 'Nivel secundario dentro del capítulo.'
    },
    {
        key: 'heading_1_style',
        label: 'Heading 1',
        description: 'Primer nivel de jerarquía interna.'
    },
    {
        key: 'heading_2_style',
        label: 'Heading 2',
        description: 'Segundo nivel de jerarquía interna.'
    },
    {
        key: 'heading_3_style',
        label: 'Heading 3',
        description: 'Tercer nivel de jerarquía interna.'
    }
];

const documentImportState = {
    file: null,
    analysis: null,
    mapping: null,
    validation: null,
    busy: false
};

window.almadenDocumentImportState = documentImportState;
