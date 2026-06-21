const fs = require('fs');
const file = 'assets/js/editor/editor-settings-tabs.js';
let content = fs.readFileSync(file, 'utf8');

// Replace || with ?? for padding and margin values that can be 0
content = content.replace(/value = settings\.padding_left \|\| 1\.0;/g, 'value = settings.padding_left ?? 0;');
content = content.replace(/value = settings\.padding_right \|\| 1\.0;/g, 'value = settings.padding_right ?? 0;');
content = content.replace(/value = settings\.padding_top \|\| 0;/g, 'value = settings.padding_top ?? 0;');
content = content.replace(/value = settings\.padding_bottom \|\| 0;/g, 'value = settings.padding_bottom ?? 0;');

content = content.replace(/value = settings\.margin_top \|\| 2\.5;/g, 'value = settings.margin_top ?? 2.5;');
content = content.replace(/value = settings\.margin_bottom \|\| 2\.5;/g, 'value = settings.margin_bottom ?? 2.5;');

fs.writeFileSync(file, content);
