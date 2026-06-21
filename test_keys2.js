const fs = require('fs');
const ajaxContent = fs.readFileSync('includes/ajax/ajax-settings.php', 'utf8');
const match = ajaxContent.match(/\$data\s*=\s*array\s*\(([\s\S]*?)\);/);
const lines = match[1].split('\n');
const keys = [];
lines.forEach(line => {
    const m = line.match(/'([a-zA-Z0-9_]+)'\s*=>/);
    if (m) keys.push(m[1]);
});

const dbContent = fs.readFileSync('almaden-bookster.php', 'utf8');
const missing = [];
keys.forEach(key => {
    if (key === 'book_id') return;
    const regex = new RegExp(`\\s${key}\\s`);
    if (!regex.test(dbContent) && !dbContent.includes(`'${key}'`)) {
        missing.push(key);
    }
});
console.log("Missing columns in schema:");
console.log(missing);
