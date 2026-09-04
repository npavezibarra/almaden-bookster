// Decodes the binary preview envelope: UTF-8 JSON followed by raw PDF bytes.
(function () {
    if (window.almadenTypstResponse) return;

    function decode(buffer, metadataLength) {
        if (!(buffer instanceof ArrayBuffer)) {
            throw new Error('La respuesta de composición no es binaria.');
        }
        const length = Number.parseInt(metadataLength, 10);
        if (!Number.isFinite(length) || length < 2 || length >= buffer.byteLength) {
            throw new Error('El servidor devolvió metadatos de composición inválidos.');
        }

        let metadata = null;
        try {
            metadata = JSON.parse(new TextDecoder().decode(buffer.slice(0, length)));
        } catch (error) {
            throw new Error('No se pudieron leer los metadatos de composición.');
        }
        const pdfBytes = buffer.slice(length);
        const signature = new TextDecoder('ascii').decode(pdfBytes.slice(0, 5));
        if ('%PDF-' !== signature) {
            throw new Error('El servidor no devolvió un archivo PDF.');
        }
        return { metadata, pdfBytes };
    }

    window.almadenTypstResponse = { decode };
})();
