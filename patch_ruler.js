const fs = require('fs');
const file = 'assets/js/editor/editor-ui.js';
let content = fs.readFileSync(file, 'utf8');

content = content.replace(/transform: translateX\(-50%\);/g, '');

fs.writeFileSync(file, content);
