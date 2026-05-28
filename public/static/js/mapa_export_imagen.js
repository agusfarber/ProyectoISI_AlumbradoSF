/**
 * Utilidades para exportar la vista actual del mapa de reclamos como imagen PNG.
 */
window.MapaExportImagen = {
    colorMarcadorPorEstado(estado) {
        if (estado === 'Recibido') return '#808080';
        if (estado === 'Asignado') return '#0DCAF0';
        if (estado === 'Pendiente') return '#FF0000';
        if (estado === 'En ejecución') return '#FFD700';
        if (estado === 'Completado') return '#198754';
        return '#808080';
    },

    generarNombreArchivo(prefijo = 'mapa-reclamos') {
        const ahora = new Date();
        const fecha = ahora.toISOString().slice(0, 10);
        const hora = String(ahora.getHours()).padStart(2, '0')
            + String(ahora.getMinutes()).padStart(2, '0')
            + String(ahora.getSeconds()).padStart(2, '0');
        return `${prefijo}-${fecha}-${hora}.png`;
    },

    descargarDataUrl(dataUrl, nombreArchivo) {
        const link = document.createElement('a');
        link.download = nombreArchivo;
        link.href = dataUrl;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    },

    esperarMapaListo(map) {
        return new Promise((resolve) => {
            const finalizar = () => resolve();
            const timeout = window.setTimeout(finalizar, 4000);

            const alListo = () => {
                window.clearTimeout(timeout);
                if (typeof map.triggerRepaint === 'function') {
                    map.triggerRepaint();
                }
                if (typeof map.once === 'function') {
                    map.once('render', finalizar);
                    window.setTimeout(finalizar, 500);
                } else {
                    finalizar();
                }
            };

            if (map.loaded && map.loaded()) {
                alListo();
                return;
            }

            map.once('idle', alListo);
        });
    },

    esMapaMapbox(map) {
        return !!(map && typeof map.getCanvas === 'function');
    },

    marcadorMapboxVisible(marker, debeMostrarMarcador) {
        const reclamo = marker._reclamo;
        if (!reclamo || typeof debeMostrarMarcador !== 'function') {
            return false;
        }
        if (!debeMostrarMarcador(reclamo)) {
            return false;
        }
        const elemento = marker.getElement();
        return !!(elemento && elemento.style.display !== 'none');
    },

    async exportarMapbox(map, marcadores, debeMostrarMarcador) {
        await this.esperarMapaListo(map);

        const mapCanvas = map.getCanvas();
        const contenedor = map.getContainer();
        const escalaX = mapCanvas.width / contenedor.clientWidth;
        const escalaY = mapCanvas.height / contenedor.clientHeight;

        const composite = document.createElement('canvas');
        composite.width = mapCanvas.width;
        composite.height = mapCanvas.height;
        const ctx = composite.getContext('2d');
        if (!ctx) {
            throw new Error('No se pudo preparar el lienzo de exportación');
        }

        ctx.drawImage(mapCanvas, 0, 0);

        marcadores.forEach((marker) => {
            if (!this.marcadorMapboxVisible(marker, debeMostrarMarcador)) {
                return;
            }

            const reclamo = marker._reclamo;
            const lngLat = marker.getLngLat();
            const punto = map.project(lngLat);
            const x = punto.x * escalaX;
            const y = punto.y * escalaY;
            const radio = 9 * Math.max(escalaX, escalaY);
            const color = this.colorMarcadorPorEstado(reclamo.municipalidad_estado || 'Recibido');

            ctx.beginPath();
            ctx.arc(x, y, radio, 0, Math.PI * 2);
            ctx.fillStyle = color;
            ctx.fill();
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2 * Math.max(escalaX, escalaY);
            ctx.stroke();
        });

        return composite.toDataURL('image/png');
    },

    esperarGoogleMapListo(googleMap) {
        return new Promise((resolve) => {
            if (!googleMap || !window.google || !google.maps || !google.maps.event) {
                window.setTimeout(resolve, 500);
                return;
            }

            const timeout = window.setTimeout(resolve, 4000);
            google.maps.event.addListenerOnce(googleMap, 'idle', () => {
                window.clearTimeout(timeout);
                window.setTimeout(resolve, 300);
            });
        });
    },

    async exportarGoogle(elementoMapa, googleMap) {
        if (typeof html2canvas === 'undefined') {
            throw new Error('La librería de captura no está disponible');
        }
        if (!elementoMapa) {
            throw new Error('No se encontró el contenedor del mapa');
        }

        await this.esperarGoogleMapListo(googleMap);

        const canvas = await html2canvas(elementoMapa, {
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#e5e3df',
            scale: Math.min(window.devicePixelRatio || 1, 2),
            logging: false
        });

        return canvas.toDataURL('image/png');
    }
};
