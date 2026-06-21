const fs = require('fs');
let code = fs.readFileSync('assets/js/editor/editor-ui.js', 'utf8');

const regex = /let html = '';\s*\/\/\s*Draw 0 at center[\s\S]*?ruler\.innerHTML = html;/;

const newHtml = `let html = '';
    // Draw 0 at center
    html += \`<div style="position: absolute; top: 0; bottom: 0; border-left: 1px solid #ef4444; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; left: \${center}px; z-index: 10;">
        <span style="font-size: 8px; color: #ef4444; font-weight: bold; line-height: 1; transform: translateX(-50%); margin-bottom: 2px;">0</span>
    </div>\`;

    for (let i = 1; i <= maxUnits; i++) {
        // Right
        html += \`<div style="position: absolute; top: 0; bottom: 0; border-left: 1px solid #9ca3af; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; left: \${center + (i * unitPixels)}px;">
            <span style="font-size: 8px; color: #4b5563; line-height: 1; transform: translateX(-50%); margin-bottom: 2px;">\${i}</span>
        </div>\`;
        // Left
        html += \`<div style="position: absolute; top: 0; bottom: 0; border-left: 1px solid #9ca3af; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; left: \${center - (i * unitPixels)}px;">
            <span style="font-size: 8px; color: #4b5563; line-height: 1; transform: translateX(-50%); margin-bottom: 2px;">-\${i}</span>
        </div>\`;

        // Sub-ticks
        for (let j = 1; j < 10; j++) {
            const subTickOffset = (i - 1 + (j / 10)) * unitPixels;
            const tickHeight = j === 5 ? '10px' : '6px';
            // Right subtick
            html += \`<div style="position: absolute; bottom: 0; border-left: 1px solid #d1d5db; height: \${tickHeight}; left: \${center + subTickOffset}px;"></div>\`;
            // Left subtick
            html += \`<div style="position: absolute; bottom: 0; border-left: 1px solid #d1d5db; height: \${tickHeight}; left: \${center - subTickOffset}px;"></div>\`;
        }
    }

    ruler.innerHTML = html;`;

code = code.replace(regex, newHtml);

fs.writeFileSync('assets/js/editor/editor-ui.js', code);
