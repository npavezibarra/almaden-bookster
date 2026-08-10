// assets/js/cover/cover-image-diagnostics-format.js

(function() {
    const Diagnostics = window.CoverEditorDiagnostics = window.CoverEditorDiagnostics || {};

    function formatPx(value) {
        return new Intl.NumberFormat('es-ES').format(Math.round(value || 0));
    }

    function formatDpi(value) {
        return new Intl.NumberFormat('es-ES', {
            maximumFractionDigits: 0
        }).format(Math.round(value || 0));
    }

    function formatCm(value) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: 1,
            maximumFractionDigits: 2
        }).format(Number(value) || 0);
    }

    function formatMm(value) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 1
        }).format(Number(value) || 0);
    }

    function formatPercent(value) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }).format(Number(value) || 0);
    }

    function normalizeHex(hex) {
        if (!hex) return '#000000';
        let value = String(hex).trim();
        if (value.length === 4 && value[0] === '#') {
            value = `#${value[1]}${value[1]}${value[2]}${value[2]}${value[3]}${value[3]}`;
        }
        if (/^#[0-9a-fA-F]{6}$/.test(value)) {
            return value.toUpperCase();
        }
        return '#000000';
    }

    function hexToRgb(hex) {
        const value = normalizeHex(hex);
        const match = value.match(/^#([0-9A-F]{2})([0-9A-F]{2})([0-9A-F]{2})$/);
        if (!match) {
            return { r: 0, g: 0, b: 0 };
        }
        return {
            r: parseInt(match[1], 16),
            g: parseInt(match[2], 16),
            b: parseInt(match[3], 16)
        };
    }

    function isPureBlack(hex) {
        return normalizeHex(hex) === '#000000';
    }

    function isNearBlack(hex) {
        const { r, g, b } = hexToRgb(hex);
        const luminance = (0.299 * r) + (0.587 * g) + (0.114 * b);
        return luminance <= 60;
    }

    Object.assign(Diagnostics, {
        formatPx,
        formatDpi,
        formatCm,
        formatMm,
        formatPercent,
        normalizeHex,
        hexToRgb,
        isPureBlack,
        isNearBlack
    });
})();
