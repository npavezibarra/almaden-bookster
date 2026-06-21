const fs = require('fs');
let code = fs.readFileSync('assets/js/editor/editor-ui.js', 'utf8');

const regex = /const totalWidth = Math\.max\(scroller\.clientWidth, scroller\.scrollWidth\);[\s\S]*?const maxUnits = Math\.ceil\(center \/ unitPixels\);/;

const replacement = `const totalWidth = Math.max(scroller.clientWidth, scroller.scrollWidth) + 1000; // Extra width for scrolling safety
    ruler.style.width = totalWidth + 'px';
    
    // Align ruler with horizontal scroll
    ruler.style.left = -scroller.scrollLeft + 'px';
    
    let center = totalWidth / 2;
    
    // Exact spine calculation based on DOM
    const oddPage = scroller.querySelector('.pdf-page.page-odd');
    const evenPage = scroller.querySelector('.pdf-page.page-even');
    
    if (scroller.classList.contains('spread-view')) {
        // Spine is the boundary between even and odd
        if (oddPage) {
            center = oddPage.offsetLeft;
        } else if (evenPage) {
            center = evenPage.offsetLeft + evenPage.offsetWidth;
        }
    } else {
        // Single page view: user probably wants 0 at the left edge of odd, or right edge of even
        // Or center of the page? "medio del spread" implies the spine.
        if (oddPage) {
            center = oddPage.offsetLeft; // Spine is on the left
        } else if (evenPage) {
            center = evenPage.offsetLeft + evenPage.offsetWidth; // Spine is on the right
        } else {
            // Fallback to exactly center of first page
            const firstPage = scroller.querySelector('.pdf-page');
            if (firstPage) {
                center = firstPage.offsetLeft + (firstPage.offsetWidth / 2);
            }
        }
    }

    const maxUnitsRight = Math.ceil((totalWidth - center) / unitPixels) + 2;
    const maxUnitsLeft = Math.ceil(center / unitPixels) + 2;
    const maxUnits = Math.max(maxUnitsRight, maxUnitsLeft);`;

code = code.replace(regex, replacement);
fs.writeFileSync('assets/js/editor/editor-ui.js', code);
