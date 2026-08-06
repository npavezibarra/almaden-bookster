# Typst PDF Page Template UI

`editor-page-template-selector.js` handles page selection in the PDF.js preview
and assigns the selected Typst page-template preset to `bookState.settings`.
`editor-page-template-images.js` manages the slot-image overlay and the WordPress
media uploader for each rendered slot.
Neither file composes the template into the PDF; that remains the responsibility
of the PHP page-template composer.
