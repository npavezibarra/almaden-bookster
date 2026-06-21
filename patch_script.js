const fs = require('fs');
let code = fs.readFileSync('assets/js/editor/editor-settings-api.js', 'utf8');

// Replace standard document.getElementById(...).value with getVal(...)
code = code.replace(/document\.getElementById\('([^']+)'\)\.value/g, "getVal('$1')");
// Replace ?.value with getVal(...)
code = code.replace(/document\.getElementById\('([^']+)'\)\?\.value/g, "getVal('$1')");
// Replace document.getElementById(...).checked with getChecked(...)
code = code.replace(/document\.getElementById\('([^']+)'\)\.checked \? 1 : 0/g, "getChecked('$1')");
code = code.replace(/document\.getElementById\('([^']+)'\)\?\.checked \? 1 : 0/g, "getChecked('$1')");
code = code.replace(/document\.getElementById\('([^']+)'\)\.checked/g, "(getChecked('$1') === 1)");

fs.writeFileSync('assets/js/editor/editor-settings-api.js', code);
