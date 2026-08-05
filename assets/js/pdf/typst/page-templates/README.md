# Typst PDF Page Template UI

`editor-page-template-selector.js` handles page selection in the PDF.js preview
and assigns the selected Typst page-template preset to `bookState.settings`.
It does not compose the template into the PDF; that remains the responsibility
of the PHP page-template composer.
