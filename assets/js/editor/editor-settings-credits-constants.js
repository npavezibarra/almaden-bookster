let creditsMediaFrame = null;
let creditsRemoteSaveTimer = null;
let creditsRemoteSaveQueuedConfig = null;
let creditsRemoteSavePromise = Promise.resolve(null);
let creditsSuppressRemoteSave = false;

const CREDITS_ROLE_OPTIONS = [
    { value: 'author', label: 'Autor' },
    { value: 'coauthor', label: 'Coautor' },
    { value: 'editor', label: 'Editor' },
    { value: 'translator', label: 'Traductor' },
    { value: 'designer', label: 'Diseñador' },
    { value: 'proofreader', label: 'Corrector' },
    { value: 'photographer', label: 'Fotógrafo' },
    { value: 'other', label: 'Otro' },
];

const CREDITS_COMPANY_TYPE_OPTIONS = [
    { value: 'company', label: 'Empresa' },
    { value: 'foundation', label: 'Fundación' },
    { value: 'patron', label: 'Mecenas' },
    { value: 'university', label: 'Universidad' },
];

const CREDITS_LOGO_POSITION_OPTIONS = [
    { value: 'left', label: 'Izquierda' },
    { value: 'center', label: 'Centro' },
    { value: 'right', label: 'Derecha' },
];

const CREDITS_LICENSE_OPTIONS = [
    { value: 'all_rights_reserved', label: 'Todos los derechos reservados' },
    { value: 'creative_commons', label: 'Creative Commons' },
];

const CREDITS_FALLBACK_FONT_FAMILIES = [
    'Inter',
    'Urbanist',
    'Merriweather',
    'Newsreader',
    'Playfair Display',
    'Lora',
    'Cinzel',
    'Cormorant Garamond',
    'Outfit',
    'Roboto Slab',
    'Source Serif 4',
    'Libre Baskerville',
    'Alegreya',
    'PT Serif',
];
