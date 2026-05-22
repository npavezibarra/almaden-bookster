const fs = require('fs');
const path = './templates/editor-settings-modal.php';
const content = fs.readFileSync(path, 'utf8');

const functionsMatch = content.match(/<\?php\n([\s\S]*?)\n\?>/);
if (functionsMatch) {
    fs.writeFileSync('./templates/settings-tabs/functions.php', '<?php\n' + functionsMatch[1] + '\n?>\n');
}

const tab1Match = content.match(/<div id="tab-page" class="setting-tab-content space-y-4">([\s\S]*?)<\/div>\s*<!-- PESTAÑA 2:/);
if (tab1Match) {
    fs.writeFileSync('./templates/settings-tabs/tab-page.php', '<div id="tab-page" class="setting-tab-content space-y-4">' + tab1Match[1] + '</div>\n');
}

const tab2Match = content.match(/<div id="tab-typography" class="setting-tab-content space-y-4 hidden">([\s\S]*?)<\/div>\s*<!-- PESTAÑA 3:/);
if (tab2Match) {
    fs.writeFileSync('./templates/settings-tabs/tab-typography.php', '<div id="tab-typography" class="setting-tab-content space-y-4 hidden">' + tab2Match[1] + '</div>\n');
}

const tab3Match = content.match(/<div id="tab-header-footer" class="setting-tab-content space-y-4 hidden">([\s\S]*?)<\/div>\s*<!-- PESTAÑA 4:/);
if (tab3Match) {
    fs.writeFileSync('./templates/settings-tabs/tab-header-footer.php', '<div id="tab-header-footer" class="setting-tab-content space-y-4 hidden">' + tab3Match[1] + '</div>\n');
}

const tab4Match = content.match(/<div id="tab-chapters" class="setting-tab-content space-y-4 hidden">([\s\S]*?)<\/div>\s*<\/div>\s*<!-- Footer Modal -->/);
if (tab4Match) {
    fs.writeFileSync('./templates/settings-tabs/tab-chapters.php', '<div id="tab-chapters" class="setting-tab-content space-y-4 hidden">' + tab4Match[1] + '</div>\n');
}

// Now replace the original file
let newContent = content.replace(/<\?php\n[\s\S]*?\n\?>\n/, '<?php include plugin_dir_path( __FILE__ ) . \'settings-tabs/functions.php\'; ?>\n');

newContent = newContent.replace(/<div id="tab-page" class="setting-tab-content space-y-4">[\s\S]*?<\/div>\s*<!-- PESTAÑA 2:/, '<?php include plugin_dir_path( __FILE__ ) . \'settings-tabs/tab-page.php\'; ?>\n\n            <!-- PESTAÑA 2:');

newContent = newContent.replace(/<div id="tab-typography" class="setting-tab-content space-y-4 hidden">[\s\S]*?<\/div>\s*<!-- PESTAÑA 3:/, '<?php include plugin_dir_path( __FILE__ ) . \'settings-tabs/tab-typography.php\'; ?>\n\n            <!-- PESTAÑA 3:');

newContent = newContent.replace(/<div id="tab-header-footer" class="setting-tab-content space-y-4 hidden">[\s\S]*?<\/div>\s*<!-- PESTAÑA 4:/, '<?php include plugin_dir_path( __FILE__ ) . \'settings-tabs/tab-header-footer.php\'; ?>\n\n            <!-- PESTAÑA 4:');

newContent = newContent.replace(/<div id="tab-chapters" class="setting-tab-content space-y-4 hidden">[\s\S]*?<\/div>\s*<\/div>\s*<!-- Footer Modal -->/, '<?php include plugin_dir_path( __FILE__ ) . \'settings-tabs/tab-chapters.php\'; ?>\n\n        </div>\n\n        <!-- Footer Modal -->');

fs.writeFileSync(path, newContent);
console.log('Split completed');
