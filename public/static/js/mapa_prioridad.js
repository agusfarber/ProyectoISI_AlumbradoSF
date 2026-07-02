/**
 * Reglas de prioridad alta para el mapa (espejo de ReclamoPrioridadService).
 */
const MapaPrioridadUtil = (function () {
    const MOTIVO_PRIORIDAD_ALTA = 'Postes, cables caídos o por caer (Telecom, Epec, Monet)';
    const DIAS_SIN_ATENDER_PARA_ALTA = 10;

    function escaparHtml(texto) {
        return String(texto ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function debeTenerPrioridadNula(reclamo) {
        if (String(reclamo?.municipalidad_estado || '').trim() === 'Completado') {
            return true;
        }
        return Number(reclamo?.cerrado ?? 0) === 1;
    }

    function motivoRequierePrioridadAlta(motivo) {
        return String(motivo ?? '').trim() === MOTIVO_PRIORIDAD_ALTA;
    }

    function estadoRequierePrioridadAlta(estado) {
        return String(estado ?? '').trim() === 'Pendiente';
    }

    function diasCalendarioEntre(inicio, fin) {
        const utc1 = Date.UTC(inicio.getFullYear(), inicio.getMonth(), inicio.getDate());
        const utc2 = Date.UTC(fin.getFullYear(), fin.getMonth(), fin.getDate());
        return Math.floor((utc2 - utc1) / (1000 * 60 * 60 * 24));
    }

    function diasSinAtenderRequierePrioridadAlta(reclamo) {
        if (debeTenerPrioridadNula(reclamo)) {
            return false;
        }

        const fechaInicio = reclamo?.municipalidad_fechaInicio;
        if (!fechaInicio) {
            return false;
        }

        const inicio = new Date(fechaInicio);
        if (Number.isNaN(inicio.getTime())) {
            return false;
        }

        return diasCalendarioEntre(inicio, new Date()) >= DIAS_SIN_ATENDER_PARA_ALTA;
    }

    /**
     * @returns {string[]} 'motivo' | 'estado' | 'fecha'
     */
    function obtenerCausasPrioridadAlta(reclamo) {
        if (!reclamo || String(reclamo.prioridad || '').trim().toLowerCase() !== 'alta') {
            return [];
        }

        if (debeTenerPrioridadNula(reclamo)) {
            return [];
        }

        const causas = [];

        if (motivoRequierePrioridadAlta(reclamo.municipalidad_motivo)) {
            causas.push('motivo');
        }
        if (estadoRequierePrioridadAlta(reclamo.municipalidad_estado)) {
            causas.push('estado');
        }
        if (diasSinAtenderRequierePrioridadAlta(reclamo)) {
            causas.push('fecha');
        }

        return causas;
    }

    function crearLineaPopupCampo(etiqueta, valorTexto, esCausaPrioridadAlta) {
        const valor = escaparHtml(valorTexto);
        const claseDescripcion = etiqueta === 'Descripción' ? ' mapa-popup-campo--descripcion' : '';

        if (!esCausaPrioridadAlta) {
            return `<p class="mapa-popup-campo${claseDescripcion}"><strong>${etiqueta}:</strong> ${valor}</p>`;
        }

        return `<p class="mapa-popup-campo--prioridad-alta${claseDescripcion}"><strong>${etiqueta}:</strong> <span class="mapa-popup-valor-prioridad-alta"><span class="mapa-popup-prioridad-alta-badge" aria-hidden="true">!</span>${valor}</span></p>`;
    }

    return {
        obtenerCausasPrioridadAlta,
        crearLineaPopupCampo
    };
})();
