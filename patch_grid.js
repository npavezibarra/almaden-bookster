const fs = require('fs');
const file = 'assets/js/pdf/editor-pdf-styles-base.js';
let content = fs.readFileSync(file, 'utf8');

content = content.replace(/grid-template-columns: max-content max-content;/g, 'grid-template-columns: ${widthPx + globalBleedPx}px ${widthPx + globalBleedPx}px;');

fs.writeFileSync(file, content);
