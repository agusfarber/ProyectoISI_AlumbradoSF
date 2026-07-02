/* Exportación de mapa — ver mapa_export_imagen.js */
if (!window.MapaExportImagen) {
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
                    if (typeof map.triggerRepaint === 'function') map.triggerRepaint();
                    if (typeof map.once === 'function') {
                        map.once('render', finalizar);
                        window.setTimeout(finalizar, 500);
                    } else finalizar();
                };
                if (map.loaded && map.loaded()) { alListo(); return; }
                map.once('idle', alListo);
            });
        },
        esMapaMapbox(map) {
            return !!(map && typeof map.getCanvas === 'function');
        },
        marcadorMapboxVisible(marker, debeMostrarMarcador) {
            const reclamo = marker._reclamo;
            if (!reclamo || typeof debeMostrarMarcador !== 'function') return false;
            if (!debeMostrarMarcador(reclamo)) return false;
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
            if (!ctx) throw new Error('No se pudo preparar el lienzo de exportación');
            ctx.drawImage(mapCanvas, 0, 0);
            marcadores.forEach((marker) => {
                if (!this.marcadorMapboxVisible(marker, debeMostrarMarcador)) return;
                const reclamo = marker._reclamo;
                const punto = map.project(marker.getLngLat());
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
                if (!googleMap || !window.google?.maps?.event) {
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
            if (typeof html2canvas === 'undefined') throw new Error('La librería de captura no está disponible');
            if (!elementoMapa) throw new Error('No se encontró el contenedor del mapa');
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
}

window.app = Vue.createApp({
    data() {
        return {
            map: null,
            reclamos: [],
            marcadores: [],
            tabla: null,
            reclamoSeleccionado: {},
            reclamoParaReubicar: {},
            nuevaUbicacion: null,
            modoReubicacion: false,
            direcciones: [], // Cache de direcciones personalizadas
            ubicacionPersonalizada: null, // Para almacenar la ubicación personalizada actual
            filtroEstado: '', // Filtro por estado del reclamo (deprecated)
            estadosSeleccionados: ['Recibido', 'Asignado', 'Pendiente', 'En ejecución', 'Completado'],
            prioridadesSeleccionadas: ['Alta', 'Baja'], // Por defecto ambas prioridades del filtro
            exportandoMapa: false,
            cacheCoordenadasReclamos: {}, // OPTIMIZACIÓN: Cache de coordenadas por reclamo ID
            mostrarListaReclamosMapa: false,
            busquedaReclamosMapa: ''
        };
    },
    computed: {
        reclamosVisiblesMapa() {
            const busqueda = this.normalizarTextoBusqueda(this.busquedaReclamosMapa);
            const reclamosVisibles = this.marcadores
                .map(marker => marker._reclamo)
                .filter(reclamo => reclamo && this.debeMostrarMarcador(reclamo));

            if (!busqueda) {
                return reclamosVisibles;
            }

            return reclamosVisibles.filter(reclamo => {
                const texto = this.normalizarTextoBusqueda([
                    reclamo.municipalidad_id,
                    reclamo.municipalidad_motivo,
                    reclamo.municipalidad_estado,
                    reclamo.municipalidad_domicilio,
                    reclamo.municipalidad_numeroDomicilio
                ].join(' '));

                return texto.includes(busqueda);
            });
        }
    },
    methods: {
        normalizarTextoBusqueda(texto) {
            return (texto || '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        },

        normalizarMotivoReclamo(motivo) {
            return (motivo || '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();
        },

        colorEstadoReclamo(estado) {
            if (estado === 'Recibido') return '#808080';
            if (estado === 'Asignado') return '#0DCAF0';
            if (estado === 'Pendiente') return '#FF0000';
            if (estado === 'En ejecución') return '#FFD700';
            if (estado === 'Completado') return '#198754';
            return '#808080';
        },

        iconoMotivoReclamo(motivo) {
            const motivoNormalizado = this.normalizarMotivoReclamo(motivo);

            if (motivoNormalizado.includes('pedido de alumbrado')) return '🌃';
            if (motivoNormalizado.includes('semaforo')) return '🚦';
            if (motivoNormalizado.includes('rama')) return '🌳';
            if (motivoNormalizado.includes('cable')) return '🔌';
            if (motivoNormalizado.includes('poste')) return '⚠️';
            if (motivoNormalizado.includes('columna')) return '⚠️';
            if (motivoNormalizado.includes('agotada')) return '💡';
            if (motivoNormalizado.includes('quemada') || motivoNormalizado.includes('rota')) return '💡';

            return '💡';
        },

        escaparTextoSvg(texto) {
            return (texto || '')
                .toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },

        claveDomicilioReclamo(reclamo, coordenadas) {
            const domicilio = (reclamo.municipalidad_domicilio || '').trim().toLowerCase();
            const numero = (reclamo.municipalidad_numeroDomicilio || '').trim().toLowerCase();
            if (domicilio) {
                return `dom:${domicilio}|${numero}`;
            }
            if (coordenadas) {
                return `coord:${coordenadas.lat.toFixed(6)},${coordenadas.lng.toFixed(6)}`;
            }
            return `id:${reclamo.id}`;
        },

        agruparReclamosPorDomicilio(resultados) {
            const mapa = new Map();

            for (const { reclamo, coords } of resultados) {
                if (!coords) continue;

                const clave = this.claveDomicilioReclamo(reclamo, coords);
                if (!mapa.has(clave)) {
                    mapa.set(clave, { reclamos: [], coordenadas: coords });
                }
                mapa.get(clave).reclamos.push(reclamo);
            }

            return Array.from(mapa.values()).map((grupo) => {
                grupo.reclamos.sort((a, b) => parseInt(b.municipalidad_id, 10) - parseInt(a.municipalidad_id, 10));

                const coordsActualizadas = resultados.find(
                    (item) => item.coords && item.reclamo.id === grupo.reclamos[0].id
                );
                if (coordsActualizadas?.coords) {
                    grupo.coordenadas = coordsActualizadas.coords;
                }

                return grupo;
            });
        },

        obtenerReclamosVisiblesGrupo(reclamos) {
            return reclamos.filter((reclamo) => this.debeMostrarMarcador(reclamo));
        },

        reclamoTienePrioridadAlta(reclamo) {
            return String(reclamo?.prioridad || '').trim().toLowerCase() === 'alta';
        },

        marcadorMuestraPrioridadAlta(reclamos) {
            const lista = Array.isArray(reclamos) ? reclamos : [reclamos];
            const visibles = this.obtenerReclamosVisiblesGrupo(lista);
            const fuente = visibles.length ? visibles : lista;
            return fuente.some((r) => this.reclamoTienePrioridadAlta(r));
        },

        pintarContenidoMarcadorMapbox(wrap, pin, { color, contenido, esNumero, prioridadAlta }) {
            pin.innerHTML = this.crearSvgMarcador(color, contenido, esNumero);
            wrap.classList.toggle('mapa-motivo-marker-wrap--prioridad-alta', !!prioridadAlta);
            wrap.querySelectorAll('.mapa-prioridad-alta-badge').forEach((el) => el.remove());
            if (prioridadAlta) {
                const badge = document.createElement('span');
                badge.className = 'mapa-prioridad-alta-badge';
                badge.setAttribute('aria-hidden', 'true');
                badge.textContent = '!';
                pin.appendChild(badge);
            }
        },

        crearElementoMarcador(color, contenidoCentro, esNumero = false, titulo = 'Reclamo', prioridadAlta = false) {
            const wrap = document.createElement('div');
            wrap.className = 'mapa-motivo-marker-wrap';
            const pin = document.createElement('div');
            pin.className = 'mapa-motivo-marker';
            wrap.appendChild(pin);
            wrap.title = titulo;
            this.pintarContenidoMarcadorMapbox(wrap, pin, {
                color,
                contenido: contenidoCentro,
                esNumero,
                prioridadAlta
            });
            return wrap;
        },

        crearSvgMarcador(color, contenidoCentro, esNumero = false) {
            const fontFamily = esNumero
                ? 'Open Sans, Segoe UI, sans-serif'
                : 'Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif';
            const fontWeight = esNumero ? 'font-weight="700"' : '';

            return `
                <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" overflow="visible">
                    <path d="M16 2.5C10.75 2.5 6.5 6.75 6.5 12c0 7.1 9.5 17.5 9.5 17.5S25.5 19.1 25.5 12C25.5 6.75 21.25 2.5 16 2.5Z" fill="${color}" stroke="#FFFFFF" stroke-width="2"/>
                    <circle cx="16" cy="12" r="7.4" fill="#FFFFFF" opacity="0.94"/>
                    <text x="16.8" y="12.7" text-anchor="middle" dominant-baseline="middle" font-family="${fontFamily}" font-size="${esNumero ? 11 : 12}" ${fontWeight}>${contenidoCentro}</text>
                </svg>
            `;
        },

        crearElementoMarcadorMotivo(color, motivo) {
            const iconoMotivo = this.escaparTextoSvg(this.iconoMotivoReclamo(motivo));
            return this.crearElementoMarcador(color, iconoMotivo, false, motivo || 'Reclamo');
        },

        crearContenidoCentroMarcador(reclamo, cantidadVisible) {
            if (cantidadVisible > 1) {
                return cantidadVisible > 99 ? '99+' : String(cantidadVisible);
            }
            return this.escaparTextoSvg(this.iconoMotivoReclamo(reclamo.municipalidad_motivo));
        },

        crearEncabezadoPopupReclamo(reclamo) {
            const icono = this.iconoMotivoReclamo(reclamo.municipalidad_motivo);
            const color = this.colorEstadoReclamo(reclamo.municipalidad_estado || 'Recibido');
            return `
                <div class="mapa-popup-header">
                    <span class="mapa-popup-motivo-icon" style="background-color: ${color};" aria-hidden="true">${icono}</span>
                    <h6>Reclamo #${reclamo.municipalidad_id}</h6>
                </div>
            `;
        },

        crearContenidoPopupReclamo(reclamo, opciones = {}) {
            const { grupoId = null, indice = 0, total = 1, incluirTitulo = false } = opciones;
            const causasPrioridadAlta = typeof MapaPrioridadUtil !== 'undefined'
                ? MapaPrioridadUtil.obtenerCausasPrioridadAlta(reclamo)
                : [];
            const lineaPopup = (campo, etiqueta, valor) => (
                typeof MapaPrioridadUtil !== 'undefined'
                    ? MapaPrioridadUtil.crearLineaPopupCampo(etiqueta, valor, causasPrioridadAlta.includes(campo))
                    : `<p><strong>${etiqueta}:</strong> ${valor}</p>`
            );
            const navegacionGrupo = total > 1 ? `
                <div class="mapa-popup-grupo-nav">
                    <button type="button" class="mapa-popup-nav mapa-popup-nav-prev" data-grupo-id="${grupoId}" aria-label="Reclamo anterior">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <span class="mapa-popup-grupo-contador">${indice + 1} de ${total} en este domicilio</span>
                    <button type="button" class="mapa-popup-nav mapa-popup-nav-next" data-grupo-id="${grupoId}" aria-label="Siguiente reclamo">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            ` : '';
            const encabezado = incluirTitulo ? this.crearEncabezadoPopupReclamo(reclamo) : '';

            return `
                <div class="mapa-popup-reclamo">
                    ${encabezado}
                    ${navegacionGrupo}
                    ${lineaPopup('motivo', 'Motivo', reclamo.municipalidad_motivo || 'No especificado')}
                    ${lineaPopup('estado', 'Estado', reclamo.municipalidad_estado || 'No especificado')}
                    <p><strong>Prioridad:</strong> ${reclamo.prioridad || 'No especificado'}</p>
                    <p><strong>Dirección:</strong> ${reclamo.municipalidad_domicilio || 'No especificado'} ${reclamo.municipalidad_numeroDomicilio || ''}</p>
                    ${lineaPopup('fecha', 'Fecha', this.formatearFecha(reclamo.municipalidad_fechaInicio))}
                    <p><strong>Ciudadano:</strong> ${reclamo.municipalidad_ciudadano || 'No especificado'}</p>
                    ${lineaPopup('descripcion', 'Descripción', reclamo.municipalidad_descripcion || 'No especificado')}
                    <div class="mapa-popup-acciones">
                        <button type="button" class="mapa-popup-btn mapa-popup-reubicar" data-reclamo-id="${reclamo.id}">
                            <i class="bi bi-geo-alt"></i> Reubicar
                        </button>
                        <button type="button" class="mapa-popup-btn mapa-popup-detalle" data-reclamo-id="${reclamo.id}">
                            <i class="bi bi-card-text"></i> Ver detalle
                        </button>
                    </div>
                </div>
            `;
        },

        vincularEventosPopupMapbox(marker, reclamo) {
            const btnReubicar = document.querySelector(`.mapa-popup-reubicar[data-reclamo-id="${reclamo.id}"]`);
            if (btnReubicar) {
                btnReubicar.onclick = () => this.iniciarReubicacion(reclamo);
            }

            const btnDetalle = document.querySelector(`.mapa-popup-detalle[data-reclamo-id="${reclamo.id}"]`);
            if (btnDetalle) {
                btnDetalle.onclick = () => this.verReclamo(reclamo);
            }

            if (marker._grupoId) {
                const btnPrev = document.querySelector(`.mapa-popup-nav-prev[data-grupo-id="${marker._grupoId}"]`);
                if (btnPrev) {
                    btnPrev.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoMapbox(marker, -1);
                    };
                }

                const btnNext = document.querySelector(`.mapa-popup-nav-next[data-grupo-id="${marker._grupoId}"]`);
                if (btnNext) {
                    btnNext.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoMapbox(marker, 1);
                    };
                }
            }
        },

        navegarPopupGrupoMapbox(marker, delta) {
            const visibles = this.obtenerReclamosVisiblesGrupo(marker._reclamosGrupo);
            if (!visibles.length) return;

            let nuevoIndice = (marker._indicePopup || 0) + delta;
            if (nuevoIndice < 0) nuevoIndice = visibles.length - 1;
            if (nuevoIndice >= visibles.length) nuevoIndice = 0;

            this.abrirPopupMarcadorMapbox(marker, nuevoIndice);
        },

        abrirPopupMarcadorMapbox(marker, indice = null) {
            const visibles = marker._reclamosGrupo
                ? this.obtenerReclamosVisiblesGrupo(marker._reclamosGrupo)
                : [marker._reclamo];

            if (!visibles.length) return;

            if (indice !== null) {
                marker._indicePopup = indice;
            } else if (marker._indicePopup === undefined || marker._indicePopup >= visibles.length) {
                marker._indicePopup = 0;
            }

            const reclamo = visibles[marker._indicePopup];
            const popup = marker.getPopup();
            popup.setHTML(this.crearContenidoPopupReclamo(reclamo, {
                grupoId: marker._grupoId || null,
                indice: marker._indicePopup,
                total: visibles.length,
                incluirTitulo: true
            }));

            if (!popup.isOpen()) {
                marker.togglePopup();
            }

            setTimeout(() => this.vincularEventosPopupMapbox(marker, reclamo), 0);
        },

        actualizarIconoMarcadorMapbox(marker) {
            const reclamos = marker._reclamosGrupo || [marker._reclamo];
            const visibles = this.obtenerReclamosVisiblesGrupo(reclamos);
            marker._reclamosVisibles = visibles;

            if (!visibles.length) return;

            const reclamoRef = visibles[0];
            const color = this.colorEstadoReclamo(reclamoRef.municipalidad_estado || 'Recibido');
            const esNumero = visibles.length > 1;
            const contenido = this.crearContenidoCentroMarcador(reclamoRef, visibles.length);
            const wrap = marker.getElement();
            const pin = wrap.querySelector('.mapa-motivo-marker') || wrap;
            const prioridadAlta = this.marcadorMuestraPrioridadAlta(reclamos);

            wrap.title = marker._reclamosGrupo
                ? `${visibles.length} reclamos en este domicilio`
                : `Reclamo #${reclamoRef.municipalidad_id}`;
            this.pintarContenidoMarcadorMapbox(wrap, pin, {
                color,
                contenido,
                esNumero,
                prioridadAlta
            });
            marker._reclamo = reclamoRef;

            if (marker._indicePopup !== undefined && marker._indicePopup >= visibles.length) {
                marker._indicePopup = 0;
            }
        },

        crearMarcadorReclamoMapbox(reclamo, coordenadas, grupo = null) {
            const esGrupo = grupo && grupo.reclamos.length > 1;
            const visibles = esGrupo
                ? this.obtenerReclamosVisiblesGrupo(grupo.reclamos)
                : [reclamo];
            const reclamoRef = visibles[0] || reclamo;
            const cantidadVisible = visibles.length || grupo.reclamos.length;
            const esNumero = esGrupo && cantidadVisible > 1;
            const color = this.colorEstadoReclamo(reclamoRef.municipalidad_estado || 'Recibido');
            const contenido = esGrupo
                ? this.crearContenidoCentroMarcador(reclamoRef, cantidadVisible)
                : this.escaparTextoSvg(this.iconoMotivoReclamo(reclamo.municipalidad_motivo));
            const listaReclamos = esGrupo ? grupo.reclamos : [reclamo];
            const prioridadAlta = this.marcadorMuestraPrioridadAlta(listaReclamos);

            const popup = new mapboxgl.Popup({ closeOnClick: true });
            const marker = new mapboxgl.Marker({
                element: this.crearElementoMarcador(
                    color,
                    contenido,
                    esNumero,
                    esGrupo ? `${cantidadVisible} reclamos en este domicilio` : (reclamo.municipalidad_motivo || 'Reclamo'),
                    prioridadAlta
                ),
                anchor: 'bottom'
            })
                .setLngLat([coordenadas.lng, coordenadas.lat])
                .setPopup(popup)
                .addTo(this.map);

            marker._reclamo = reclamoRef;
            marker._indicePopup = 0;

            if (esGrupo) {
                marker._reclamosGrupo = grupo.reclamos;
                marker._reclamosVisibles = visibles;
                marker._grupoId = grupo.grupoId;
            }

            popup.on('open', () => {
                this.abrirPopupMarcadorMapbox(marker, marker._indicePopup || 0);
            });

            this.marcadores.push(marker);
        },

        invalidarCacheCoordenadasPorDomicilio(domicilio, numeroDomicilio) {
            const domicilioNormalizado = (domicilio || '').trim().toLowerCase();
            const numeroNormalizado = (numeroDomicilio || '').trim().toLowerCase();

            this.reclamos.forEach((reclamo) => {
                const dom = (reclamo.municipalidad_domicilio || '').trim().toLowerCase();
                const num = (reclamo.municipalidad_numeroDomicilio || '').trim().toLowerCase();
                if (dom === domicilioNormalizado && num === numeroNormalizado) {
                    delete this.cacheCoordenadasReclamos[reclamo.id];
                }
            });
        },

        waitForMapbox() {
            return new Promise((resolve, reject) => {
                const start = Date.now();
                const timeoutMs = 15000;
                const interval = setInterval(() => {
                    if (window.mapboxgl) {
                        clearInterval(interval);
                        resolve();
                    } else if (Date.now() - start > timeoutMs) {
                        clearInterval(interval);
                        reject(new Error('Timeout esperando Mapbox'));
                    }
                }, 100);
            });
        },

        async obtenerReclamos() {
            try {
                const urlReclamos = BASE_URL + 'api/reclamos';
                const response = await axios.get(urlReclamos);
                this.reclamos = response.data;
                console.log('Reclamos obtenidos:', this.reclamos);
                return this.reclamos;
            } catch (error) {
                console.error('Error al obtener reclamos:', error);
                return [];
            }
        },

        async obtenerDirecciones() {
            try {
                const urlDirecciones = BASE_URL + 'api/direcciones';
                const response = await axios.get(urlDirecciones);
                this.direcciones = response.data;
                console.log('Direcciones obtenidas:', this.direcciones);
                return this.direcciones;
            } catch (error) {
                console.error('Error al obtener direcciones:', error);
                return [];
            }
        },

        async buscarDireccionPersonalizada(domicilio, numeroDomicilio) {
            try {
                // Limpiar BASE_URL para evitar dobles slashes
                const baseUrl = BASE_URL.endsWith('/') ? BASE_URL.slice(0, -1) : BASE_URL;
                const urlBuscar = `${baseUrl}/api/direcciones/buscar?domicilio=${encodeURIComponent(domicilio)}&numero_domicilio=${encodeURIComponent(numeroDomicilio)}`;
                console.log('🔍 Buscando en API:', urlBuscar);
                
                const response = await axios.get(urlBuscar);
                console.log('📡 Respuesta completa de la API:', response);
                console.log('📡 Solo response.data:', response.data);
                console.log('📡 Tipo de response.data:', typeof response.data);
                console.log('📡 Es array?', Array.isArray(response.data));
                
                // Verificar si la respuesta tiene datos válidos (ahora siempre es array)
                if (response.data && Array.isArray(response.data) && response.data.length > 0) {
                    console.log('✅ Encontrado en array:', response.data[0]);
                    return response.data[0]; // Retornar el primer resultado
                } else {
                    console.log('❌ No se encontró dirección personalizada - Array vacío o sin datos');
                    return null;
                }
                
            } catch (error) {
                console.error('❌ Error al buscar dirección personalizada:', error);
                return null;
            }
        },

        async obtenerCoordenadasReclamo(reclamo) {
            try {
                // OPTIMIZACIÓN: Verificar si ya está en caché
                if (this.cacheCoordenadasReclamos[reclamo.id]) {
                    return this.cacheCoordenadasReclamos[reclamo.id];
                }
                
                // Buscar dirección personalizada en datos pre-cargados (más rápido que hacer petición HTTP)
                if (reclamo.municipalidad_domicilio && reclamo.municipalidad_numeroDomicilio) {
                    const direccionPersonalizada = this.direcciones.find(dir => 
                        dir.domicilio === reclamo.municipalidad_domicilio && 
                        dir.numero_domicilio === reclamo.municipalidad_numeroDomicilio
                    );

                    if (direccionPersonalizada && direccionPersonalizada.latitud && direccionPersonalizada.longitud) {
                        let lat = parseFloat(direccionPersonalizada.latitud);
                        let lng = parseFloat(direccionPersonalizada.longitud);
                        
                        if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                            const coordenadas = {
                                lat: lat,
                                lng: lng,
                                esPersonalizada: direccionPersonalizada.personalizada == 1
                            };
                            // Guardar en caché
                            this.cacheCoordenadasReclamos[reclamo.id] = coordenadas;
                            return coordenadas;
                        }
                    }
                }

                // Si no hay dirección personalizada, usar geocodificación
                const coordenadas = await this.geocodificarDireccion(reclamo);
                
                // Guardar en caché si se obtuvo resultado
                if (coordenadas) {
                    this.cacheCoordenadasReclamos[reclamo.id] = coordenadas;
                }
                
                return coordenadas;

            } catch (error) {
                console.error('Error al obtener coordenadas del reclamo:', error);
                return null;
            }
        },

        async geocodificarDireccion(reclamo) {
            try {
                // Construir la dirección principal (donde se marca el punto)
                let direccionPrincipal = '';
                if (reclamo.municipalidad_domicilio) {
                    direccionPrincipal += reclamo.municipalidad_domicilio;
                }
                if (reclamo.municipalidad_numeroDomicilio) {
                    direccionPrincipal += ' ' + reclamo.municipalidad_numeroDomicilio;
                }
                direccionPrincipal += ', San Francisco, Córdoba, Argentina';

                console.log('Geocodificando dirección:', direccionPrincipal);

                // Usar la API de geocodificación de Mapbox para la dirección principal
                const response = await fetch(
                    `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(direccionPrincipal)}.json?access_token=${mapboxgl.accessToken}&country=AR&limit=1`
                );
                
                const data = await response.json();
                
                if (data.features && data.features.length > 0) {
                    const [lng, lat] = data.features[0].center;
                    return {
                        lat: lat,
                        lng: lng,
                        direccion: direccionPrincipal,
                        confianza: data.features[0].relevance,
                        esPersonalizada: false
                    };
                } else {
                    console.warn('No se encontró la dirección:', direccionPrincipal);
                    return null;
                }
            } catch (error) {
                console.error('Error en geocodificación:', error);
                return null;
            }
        },

        async agregarMarcadoresReclamos() {
            // Limpiar marcadores existentes
            this.marcadores.forEach(marker => marker.remove());
            this.marcadores = [];

            let contadorEstados = {
                'Recibido': 0,
                'Asignado': 0,
                'Pendiente': 0,
                'En ejecución': 0,
                'Completado': 0,
                'En plan': 0,
                'Error de datos': 0
            };

            // OPTIMIZACIÓN: Paralelizar obtención de coordenadas para carga más rápida
            const promesasCoordenadas = this.reclamos.map(reclamo => 
                this.obtenerCoordenadasReclamo(reclamo).then(coords => ({ reclamo, coords }))
            );
            
            const resultados = await Promise.all(promesasCoordenadas);
            const grupos = this.agruparReclamosPorDomicilio(resultados);
            let contadorGrupos = 0;

            for (const grupo of grupos) {
                for (const reclamo of grupo.reclamos) {
                    const estado = reclamo.municipalidad_estado || 'Recibido';
                    if (contadorEstados.hasOwnProperty(estado)) {
                        contadorEstados[estado]++;
                    } else {
                        contadorEstados['Recibido']++;
                    }
                }

                if (grupo.reclamos.length > 1) {
                    this.crearMarcadorReclamoMapbox(grupo.reclamos[0], grupo.coordenadas, {
                        reclamos: grupo.reclamos,
                        grupoId: `grupo-mapbox-${++contadorGrupos}`
                    });
                } else {
                    this.crearMarcadorReclamoMapbox(grupo.reclamos[0], grupo.coordenadas);
                }
            }

            console.log(`📍 Marcadores agregados: ${this.marcadores.length} total`);
            console.log(`   - ⚫ Recibido: ${contadorEstados['Recibido']}`);
            console.log(`   - 🔵 Asignado: ${contadorEstados['Asignado']}`);
            console.log(`   - 🔴 Pendiente: ${contadorEstados['Pendiente']}`);
            console.log(`   - 🟡 En ejecución: ${contadorEstados['En ejecución']}`);
            console.log(`   - 🟢 Completado: ${contadorEstados['Completado']}`);
            console.log(`   - ⚫ En plan: ${contadorEstados['En plan']}`);
            console.log(`   - ⚫ Error de datos: ${contadorEstados['Error de datos']}`);
            
            this.aplicarFiltroMarcadores();
            this.actualizarTextoBoton();
        },

        inicializarTabla() {
            if (!document.getElementById('tabla_reclamos_mapa')) {
                return;
            }

            if (this.tabla) {
                this.tabla.destroy();
            }

            this.tabla = $('#tabla_reclamos_mapa').DataTable({
                data: this.reclamos,
                responsive: true,
                columnDefs: [
                    { width: "15%", targets: 0 }, // ID
                    { width: "45%", targets: 1 }, // Domicilio
                    { width: "10%", targets: 2 }, // Número (reducido)
                    { width: "30%", targets: 3 }  // Acciones
                ],
                columns: [
                    {
                        data: 'municipalidad_id',
                        render: (data, type, row) => {
                            // Determinar el color según el estado
                            let color = '#808080'; // Gris por defecto
                            if (row.municipalidad_estado === 'Recibido') color = '#808080'; // Gris
                            else if (row.municipalidad_estado === 'Asignado') color = '#0DCAF0'; // Celeste
                            else if (row.municipalidad_estado === 'Pendiente') color = '#FF0000'; // Rojo
                            else if (row.municipalidad_estado === 'En ejecución') color = '#FFD700'; // Amarillo
                            else if (row.municipalidad_estado === 'Completado') color = '#198754'; // Verde Bootstrap success
                            else if (row.municipalidad_estado === 'En plan') color = '#808080'; // Gris
                            else if (row.municipalidad_estado === 'Error de datos') color = '#808080'; // Gris
                            
                            return `<a href="#" class="id-clickeable text-primary fw-bold" data-id="${row.id}" style="text-decoration: none; cursor: pointer;" title="Clic para ver detalles">
                                        <svg width="16" height="20" viewBox="0 0 24 24" style="margin-right: 5px; vertical-align: middle;" fill="${color}">
                                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                        </svg>${data}
                                    </a>`;
                        }
                    },
                    { data: 'municipalidad_domicilio' },
                    { data: 'municipalidad_numeroDomicilio' },
                    {
                        data: null,
                        render: (data, type, row) => {
                            return `
                                <button class="btn btn-sm btn-info me-1 ver-reclamo" data-id="${row.id}" title="Resaltar en mapa">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning reubicar-reclamo" data-id="${row.id}" title="Reubicar punto">
                                    <i class="bi bi-geo-alt"></i>
                                </button>
                            `;
                        }
                    }
                ],
                order: [[0, 'asc']]
            });

            // Eventos de la tabla
            $('#tabla_reclamos_mapa tbody').off('click', '.ver-reclamo').on('click', '.ver-reclamo', (e) => {
                const id = $(e.currentTarget).data('id');
                const reclamo = this.reclamos.find(r => r.id == id);
                if (reclamo) this.resaltarReclamoEnMapa(reclamo);
            });

            $('#tabla_reclamos_mapa tbody').off('click', '.reubicar-reclamo').on('click', '.reubicar-reclamo', (e) => {
                const id = $(e.currentTarget).data('id');
                const reclamo = this.reclamos.find(r => r.id == id);
                if (reclamo) this.iniciarReubicacion(reclamo);
            });

            $('#tabla_reclamos_mapa tbody').off('click', '.id-clickeable').on('click', '.id-clickeable', (e) => {
                e.preventDefault();
                const id = $(e.currentTarget).data('id');
                const reclamo = this.reclamos.find(r => r.id == id);
                if (reclamo) this.verReclamo(reclamo);
            });
        },

        verReclamo(reclamo) {
            this.reclamoSeleccionado = { ...reclamo };
            new bootstrap.Modal(document.getElementById('modalVerReclamo')).show();
        },

        async resaltarReclamoEnMapa(reclamo) {
            try {
                console.log('🎯 Resaltando reclamo en mapa:', reclamo.municipalidad_id);
                
                // Obtener las coordenadas del reclamo
                const coordenadas = await this.obtenerCoordenadasReclamo(reclamo);
                
                if (!coordenadas) {
                    this.mostrarMensaje('No se pudieron obtener las coordenadas del reclamo', 'error');
                    return;
                }

                // Buscar el marcador correspondiente (individual o agrupado)
                const marcador = this.marcadores.find((marker) => {
                    if (marker._reclamosGrupo) {
                        return marker._reclamosGrupo.some((item) => item.id === reclamo.id);
                    }
                    return marker._reclamo && marker._reclamo.id === reclamo.id;
                });

                if (marcador) {
                    // Centrar el mapa en el marcador, igual que en Google Maps.
                    this.map.flyTo({
                        center: [coordenadas.lng, coordenadas.lat],
                        zoom: Math.max(this.map.getZoom(), 16),
                        speed: 1.2,
                        curve: 1.2,
                        essential: true
                    });

                    // Crear animación sobre el marcador completo, incluyendo el icono del motivo.
                    const elementoOriginal = marcador.getElement();
                    
                    // Agregar estilos iniciales
                    elementoOriginal.style.transition = 'margin-top 0.2s ease-in-out';
                    
                    // Función para hacer el efecto de salto
                    let contador = 0;
                    const intervalo = setInterval(() => {
                        if (contador % 2 === 0) {
                            elementoOriginal.style.marginTop = '-14px';
                        } else {
                            elementoOriginal.style.marginTop = '0';
                        }
                        contador++;
                        
                        if (contador >= 6) { // 3 saltos completos
                            clearInterval(intervalo);
                            // Restaurar estilos originales
                            elementoOriginal.style.marginTop = '0';
                            elementoOriginal.style.transition = '';
                        }
                    }, 200);

                } else {
                    // Si no se encuentra el marcador, mostrar mensaje simple
                    this.mostrarMensaje(`Reclamo ${reclamo.municipalidad_id} resaltado (marcador no encontrado)`, 'info');
                }

            } catch (error) {
                console.error('Error al resaltar reclamo:', error);
                this.mostrarMensaje('Error al resaltar el reclamo en el mapa', 'error');
            }
        },

        async iniciarReubicacion(reclamo) {
            this.reclamoParaReubicar = { ...reclamo };
            this.ubicacionPersonalizada = null;
            
            // Verificar si tiene ubicación personalizada
            if (reclamo.municipalidad_domicilio && reclamo.municipalidad_numeroDomicilio) {
                const direccionEncontrada = await this.buscarDireccionPersonalizada(
                    reclamo.municipalidad_domicilio, 
                    reclamo.municipalidad_numeroDomicilio
                );
                
                // Solo marcar como personalizada si el campo 'personalizada' es 1
                if (direccionEncontrada && direccionEncontrada.latitud && direccionEncontrada.longitud && direccionEncontrada.personalizada == 1) {
                    this.ubicacionPersonalizada = direccionEncontrada;
                }
            }
            
            // Mostrar modal de estado de ubicación
            new bootstrap.Modal(document.getElementById('modalEstadoUbicacion')).show();
        },

        iniciarReubicacionDesdeModal() {
            // Cerrar modal de estado
            const modalEstado = bootstrap.Modal.getInstance(document.getElementById('modalEstadoUbicacion'));
            if (modalEstado) {
                modalEstado.hide();
            }
            
            // Iniciar modo de reubicación
            this.nuevaUbicacion = null;
            this.modoReubicacion = true;
            
            // Configurar el mapa para modo reubicación
            this.map.getCanvas().style.cursor = 'crosshair';
            
            // Agregar evento de clic en el mapa
            this.map.on('click', this.onMapClick);
            
            // Mostrar modal de instrucción
            this.mostrarModalInstruccion();
            
            // Centrar el mapa en el reclamo actual si tiene coordenadas
            if (this.reclamoParaReubicar.municipalidad_domicilio && this.reclamoParaReubicar.municipalidad_numeroDomicilio) {
                this.buscarYCentrarEnReclamo(this.reclamoParaReubicar);
            }
        },

        async eliminarUbicacionPersonalizada() {
            if (!this.ubicacionPersonalizada || !this.ubicacionPersonalizada.id) {
                this.mostrarMensaje('No se puede eliminar la ubicación personalizada', 'warning');
                return;
            }

            // Mostrar modal de confirmación
            const mensajeConfirmacion = '¿Está seguro de que desea eliminar la ubicación personalizada? El punto volverá a la ubicación automática según la dirección del reclamo (Mapbox).';
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Eliminar Ubicación Personalizada');
            
            if (!confirmacion) {
                return;
            }

            try {
                const reclamoAfectado = { ...this.reclamoParaReubicar };
                const idDireccion = this.ubicacionPersonalizada.id;
                const coordsAutomaticas = await this.geocodificarDireccion(reclamoAfectado);

                const datosActualizacion = { personalizada: 0 };
                if (coordsAutomaticas) {
                    datosActualizacion.latitud = coordsAutomaticas.lat;
                    datosActualizacion.longitud = coordsAutomaticas.lng;
                }

                await axios.put(BASE_URL + 'api/direcciones/' + idDireccion, datosActualizacion);
                
                // Cerrar modal de estado
                const modalEstado = bootstrap.Modal.getInstance(document.getElementById('modalEstadoUbicacion'));
                if (modalEstado) {
                    modalEstado.hide();
                }
                
                // Guardar referencia antes de limpiar el modo reubicación
                const domicilioAfectado = this.reclamoParaReubicar.municipalidad_domicilio;
                const numeroAfectado = this.reclamoParaReubicar.municipalidad_numeroDomicilio;

                // Limpiar estado
                this.limpiarModoReubicacion();

                // Recargar marcadores y tabla
                await this.obtenerDirecciones();
                this.invalidarCacheCoordenadasPorDomicilio(domicilioAfectado, numeroAfectado);
                await this.agregarMarcadoresReclamos();
                this.inicializarTabla();
                
                if (coordsAutomaticas) {
                    this.mostrarMensaje('Ubicación personalizada eliminada. El punto volvió a la ubicación automática.', 'success');
                } else {
                    this.mostrarMensaje('Ubicación personalizada eliminada, pero no se pudo recalcular la posición automática.', 'warning');
                }
                
            } catch (error) {
                console.error('Error al eliminar ubicación personalizada:', error);
                this.mostrarMensaje('Error al eliminar la ubicación personalizada', 'error');
            }
        },

        async buscarYCentrarEnReclamo(reclamo) {
            const coordenadas = await this.obtenerCoordenadasReclamo(reclamo);
            if (coordenadas) {
                this.map.flyTo({
                    center: [coordenadas.lng, coordenadas.lat],
                    zoom: 16
                });
            }
        },

        onMapClick(e) {
            if (this.modoReubicacion) {
                this.nuevaUbicacion = {
                    lat: e.lngLat.lat,
                    lng: e.lngLat.lng
                };
                console.log('Nueva ubicación seleccionada:', this.nuevaUbicacion);
                
                // Mostrar modal de confirmación
                this.mostrarModalConfirmacion();
            }
        },

        mostrarModalConfirmacion() {
            // Crear contenido del modal dinámicamente
            const modalContent = `
                <div class="modal fade" id="modalConfirmarReubicacion" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content mapa-modal">
                            <div class="mapa-modal__header">
                                <div class="mapa-modal__title">
                                    <span class="mapa-modal__icon"><i class="bi bi-geo-alt-fill"></i></span>
                                    <h5>Confirmar reubicación</h5>
                                </div>
                                <button type="button" class="mapa-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="fw-bold">Reclamo:</label>
                                    <p>${this.reclamoParaReubicar.municipalidad_id} - ${this.reclamoParaReubicar.municipalidad_motivo}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Dirección actual:</label>
                                    <p>${this.reclamoParaReubicar.municipalidad_domicilio} ${this.reclamoParaReubicar.municipalidad_numeroDomicilio}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Nueva ubicación:</label>
                                    <p>Latitud: ${this.nuevaUbicacion.lat.toFixed(6)}</p>
                                    <p>Longitud: ${this.nuevaUbicacion.lng.toFixed(6)}</p>
                                </div>
                                <div class="mapa-modal-alert mapa-modal-alert--info">
                                    <i class="bi bi-info-circle"></i>
                                    <div><strong>¿Confirma que desea reubicar el punto del reclamo en esta nueva ubicación?</strong></div>
                                </div>
                            </div>
                            <div class="mapa-modal__footer mapa-modal__footer--end">
                                <button type="button" class="reclamos-btn reclamos-btn--outline" id="btnCancelarReubicacion">Cancelar</button>
                                <button type="button" class="reclamos-btn" id="btnConfirmarReubicacion">
                                    <i class="bi bi-geo-alt"></i> Confirmar reubicación
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remover modal anterior si existe
            const modalAnterior = document.getElementById('modalConfirmarReubicacion');
            if (modalAnterior) {
                modalAnterior.remove();
            }
            
            // Agregar nuevo modal al DOM
            document.body.insertAdjacentHTML('beforeend', modalContent);
            
            // Agregar event listeners a los botones
            document.getElementById('btnConfirmarReubicacion').addEventListener('click', () => {
                this.confirmarReubicacion();
            });
            
            document.getElementById('btnCancelarReubicacion').addEventListener('click', () => {
                this.cancelarReubicacion();
            });
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmarReubicacion'));
            modal.show();
        },

        async confirmarReubicacion() {
            if (!this.nuevaUbicacion) {
                this.mostrarMensaje('Por favor seleccione una nueva ubicación en el mapa', 'warning');
                return;
            }

            try {
                // Guardar la nueva ubicación en la tabla de direcciones
                const datosDireccion = {
                    domicilio: this.reclamoParaReubicar.municipalidad_domicilio,
                    numero_domicilio: this.reclamoParaReubicar.municipalidad_numeroDomicilio,
                    latitud: this.nuevaUbicacion.lat,
                    longitud: this.nuevaUbicacion.lng,
                    personalizada: 1 // Marcar como personalizada
                };

                await axios.post(BASE_URL + 'api/direcciones', datosDireccion);
                
                // Cerrar modal de confirmación
                const modalConfirmacion = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarReubicacion'));
                if (modalConfirmacion) {
                    modalConfirmacion.hide();
                }
                
                // Guardar referencia antes de limpiar el modo reubicación
                const domicilioAfectado = this.reclamoParaReubicar.municipalidad_domicilio;
                const numeroAfectado = this.reclamoParaReubicar.municipalidad_numeroDomicilio;

                // Limpiar estado
                this.limpiarModoReubicacion();

                // Recargar marcadores y tabla
                await this.obtenerDirecciones();
                this.invalidarCacheCoordenadasPorDomicilio(domicilioAfectado, numeroAfectado);
                await this.agregarMarcadoresReclamos();
                this.inicializarTabla();
                
                this.mostrarMensaje('Ubicación actualizada correctamente', 'success');
                
            } catch (error) {
                console.error('Error al guardar nueva ubicación:', error);
                this.mostrarMensaje('Error al guardar la nueva ubicación', 'error');
            }
        },

        cancelarReubicacion() {
            // Cerrar modal de confirmación
            const modalConfirmacion = bootstrap.Modal.getInstance(document.getElementById('modalConfirmarReubicacion'));
            if (modalConfirmacion) {
                modalConfirmacion.hide();
            }
            
            // Limpiar estado
            this.limpiarModoReubicacion();
        },

        limpiarModoReubicacion() {
            this.modoReubicacion = false;
            this.nuevaUbicacion = null;
            this.reclamoParaReubicar = {};
            this.ubicacionPersonalizada = null;
            
            // Restaurar cursor del mapa
            this.map.getCanvas().style.cursor = '';
            
            // Remover evento de clic
            this.map.off('click', this.onMapClick);
        },

        formatearFecha(fecha) {
            if (!fecha) return 'No especificada';
            try {
                const date = new Date(fecha);
                return date.toLocaleString('es-AR', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    timeZone: 'America/Argentina/Buenos_Aires'
                });
            } catch (error) {
                return fecha;
            }
        },

        filtrarPorEstado(estado) {
            this.filtroEstado = estado;
            this.aplicarFiltroMarcadores();
            
            // Actualizar el texto del botón del dropdown
            const dropdownButton = document.querySelector('.mapa-filtro-estados-toggle');
            if (dropdownButton) {
                if (estado === '') {
                    dropdownButton.innerHTML = '<i class="bi bi-funnel"></i> Filtrar por Estado';
                } else {
                    const iconos = {
                        'Recibido': '⚫',
                        'Asignado': '🔵',
                        'Pendiente': '🔴',
                        'En ejecución': '🟡',
                        'Completado': '🟢',
                        'En plan': '⚫',
                        'Error de datos': '⚫'
                    };
                    dropdownButton.innerHTML = `<i class="bi bi-funnel"></i> ${iconos[estado]} ${estado}`;
                }
            }
        },

        toggleEstado(event) {
            const estado = event.target.value;
            const index = this.estadosSeleccionados.indexOf(estado);
            
            if (index > -1) {
                // Remover estado si ya está seleccionado
                this.estadosSeleccionados.splice(index, 1);
            } else {
                // Agregar estado si no está seleccionado
                this.estadosSeleccionados.push(estado);
            }
            
            this.aplicarFiltroMarcadores();
            this.actualizarTextoBoton();
        },

        toggleTodosEstados(event) {
            if (event.target.checked) {
                // Deseleccionar todos los estados específicos
                this.estadosSeleccionados = [];
            } else {
                // Seleccionar todos los estados disponibles
                this.estadosSeleccionados = ['Recibido', 'Asignado', 'Pendiente', 'En ejecución', 'Completado'];
            }
            
            this.aplicarFiltroMarcadores();
            this.actualizarTextoBoton();
        },

        togglePrioridad(event) {
            const prioridad = event.target.value;
            const index = this.prioridadesSeleccionadas.indexOf(prioridad);

            if (index > -1) {
                this.prioridadesSeleccionadas.splice(index, 1);
            } else {
                this.prioridadesSeleccionadas.push(prioridad);
            }

            this.aplicarFiltroMarcadores();
        },

        debeMostrarMarcador(reclamo) {
            const estadoReclamo = reclamo.municipalidad_estado || 'Recibido';
            const cumpleEstado = this.estadosSeleccionados.length === 0 || this.estadosSeleccionados.includes(estadoReclamo);

            const prioridadReclamo = (reclamo.prioridad || 'Baja').trim();
            const cumplePrioridad = this.prioridadesSeleccionadas.length === 0 || this.prioridadesSeleccionadas.includes(prioridadReclamo);

            return cumpleEstado && cumplePrioridad;
        },

        actualizarTextoBoton() {
            const dropdownButton = document.querySelector('.mapa-filtro-estados-toggle');
            if (dropdownButton) {
                if (this.estadosSeleccionados.length === 0) {
                    dropdownButton.innerHTML = '<i class="bi bi-funnel"></i> Filtrar por Estado';
                } else if (this.estadosSeleccionados.length === 5) {
                    dropdownButton.innerHTML = '<i class="bi bi-funnel"></i> Todos los Estados';
                } else {
                    const iconos = {
                        'Recibido': '⚫',
                        'Asignado': '🔵',
                        'Pendiente': '🔴',
                        'En ejecución': '🟡',
                        'Completado': '🟢',
                        'En plan': '⚫',
                        'Error de datos': '⚫'
                    };
                    const iconosSeleccionados = this.estadosSeleccionados.map(estado => iconos[estado]).join(' ');
                    dropdownButton.innerHTML = `<i class="bi bi-funnel"></i> ${iconosSeleccionados}`;
                }
            }
        },

        aplicarFiltroMarcadores() {
            this.marcadores.forEach((marker) => {
                if (marker._reclamosGrupo) {
                    const visibles = this.obtenerReclamosVisiblesGrupo(marker._reclamosGrupo);
                    marker.getElement().style.display = visibles.length > 0 ? 'block' : 'none';
                    if (visibles.length > 0) {
                        this.actualizarIconoMarcadorMapbox(marker);
                    }
                    return;
                }

                const reclamo = marker._reclamo;
                if (reclamo) {
                    if (this.debeMostrarMarcador(reclamo)) {
                        marker.getElement().style.display = 'block';
                    } else {
                        marker.getElement().style.display = 'none';
                    }
                }
            });
            
            // Actualizar estadísticas en consola
            this.mostrarEstadisticasFiltradas();
        },

        mostrarEstadisticasFiltradas() {
            let contadorVisible = 0;
            let contadorEstados = {
                'Recibido': 0,
                'Asignado': 0,
                'Pendiente': 0,
                'En ejecución': 0,
                'Completado': 0,
                'En plan': 0,
                'Error de datos': 0
            };

            this.marcadores.forEach((marker) => {
                const elemento = marker.getElement();
                if (!elemento || elemento.style.display === 'none') return;

                const reclamos = marker._reclamosGrupo
                    ? this.obtenerReclamosVisiblesGrupo(marker._reclamosGrupo)
                    : (marker._reclamo ? [marker._reclamo] : []);

                reclamos.forEach((reclamo) => {
                    contadorVisible++;
                    const estado = reclamo.municipalidad_estado || 'Recibido';
                    if (contadorEstados.hasOwnProperty(estado)) {
                        contadorEstados[estado]++;
                    }
                });
            });

            if (this.estadosSeleccionados.length === 0) {
                console.log(`📍 Marcadores visibles: ${contadorVisible} de ${this.marcadores.length} total (Mostrando todos)`);
            } else {
                console.log(`📍 Marcadores visibles: ${contadorVisible} (filtros: ${this.estadosSeleccionados.join(', ')})`);
            }
            
            console.log(`   - ⚫ Recibido: ${contadorEstados['Recibido']}`);
            console.log(`   - 🔵 Asignado: ${contadorEstados['Asignado']}`);
            console.log(`   - 🔴 Pendiente: ${contadorEstados['Pendiente']}`);
            console.log(`   - 🟡 En ejecución: ${contadorEstados['En ejecución']}`);
            console.log(`   - 🟢 Completado: ${contadorEstados['Completado']}`);
            console.log(`   - ⚫ En plan: ${contadorEstados['En plan']}`);
            console.log(`   - ⚫ Error de datos: ${contadorEstados['Error de datos']}`);
        },

        async probarDirecciones() {
            console.log('🧪 PRUEBA: Verificando todas las direcciones personalizadas...');
            
            try {
                // Probar el endpoint de direcciones
                const urlDirecciones = BASE_URL + 'api/direcciones';
                const response = await axios.get(urlDirecciones);
                console.log('📋 Todas las direcciones en la BD:', response.data);
                
                // Probar cada dirección personalizada
                for (const direccion of response.data) {
                    console.log(`🔍 Probando dirección: ${direccion.domicilio} ${direccion.numero_domicilio}`);
                    
                    const urlBuscar = `${baseUrl}/api/direcciones/buscar?domicilio=${encodeURIComponent(direccion.domicilio)}&numero_domicilio=${encodeURIComponent(direccion.numero_domicilio)}`;
                    console.log('🔍 URL de búsqueda:', urlBuscar);
                    
                    try {
                        const responseBuscar = await axios.get(urlBuscar);
                        console.log(`📡 Resultado de búsqueda:`, responseBuscar.data);
                    } catch (buscarError) {
                        console.error(`❌ Error en búsqueda para ${direccion.domicilio} ${direccion.numero_domicilio}:`, buscarError);
                    }
                }
                
            } catch (error) {
                console.error('❌ Error en prueba:', error);
            }
        },

        async iniciarMapaSF() {
            // Coordenadas de San Francisco, Córdoba, AR
            const lat = -31.427;
            const lon = -62.082;

            // Asegurarse de que el contenedor del mapa existe y tiene dimensiones
            const contenedorMapa = document.getElementById('map');
            if (!contenedorMapa) {
                console.error('Contenedor del mapa no encontrado');
                return;
            }
            
            // Esperar a que el contenedor esté visible y tenga dimensiones (con timeout de seguridad)
            await new Promise(resolve => {
                const inicioEspera = Date.now();
                const timeoutMax = 5000; // 5 segundos máximo
                
                const verificarDimensiones = () => {
                    const rect = contenedorMapa.getBoundingClientRect();
                    const tiempoTranscurrido = Date.now() - inicioEspera;
                    
                    if (rect.width > 0 && rect.height > 0) {
                        console.log('✅ Contenedor del mapa listo');
                        resolve();
                    } else if (tiempoTranscurrido > timeoutMax) {
                        console.warn('⚠️ Timeout esperando dimensiones del contenedor, inicializando de todas formas');
                        resolve();
                    } else {
                        requestAnimationFrame(verificarDimensiones);
                    }
                };
                verificarDimensiones();
            });

            // Configurar el token de acceso de Mapbox
            mapboxgl.accessToken = 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ejJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw';

            // Crear el mapa
            this.map = new mapboxgl.Map({
                container: 'map', // ID del contenedor
                style: 'mapbox://styles/mapbox/streets-v12', // Estilo del mapa
                center: [lon, lat], // Centro del mapa [longitud, latitud]
                zoom: 13, // Nivel de zoom
                preserveDrawingBuffer: true // Necesario para exportar el mapa como imagen
            });

            // Evento cuando el mapa esté completamente cargado
            this.map.on('load', async () => {
                console.log('Mapa de San Francisco cargado correctamente');
                
                // Agregar marcadores y tabla (los datos ya están precargados)
                await this.agregarMarcadoresReclamos();
                this.inicializarTabla();
            });
        },

        /**
         * Muestra un modal de instrucción para el modo de reubicación
         */
        mostrarModalInstruccion() {
            const modalHtml = `
                <div class="modal fade" id="modalInstruccionReubicacion" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content mapa-modal mapa-confirm-modal">
                            <div class="mapa-modal__header">
                                <div class="mapa-modal__title">
                                    <span class="mapa-modal__icon"><i class="bi bi-geo-alt-fill"></i></span>
                                    <h5>Modo de reubicación activado</h5>
                                </div>
                                <button type="button" class="mapa-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="mapa-confirm-modal__message">
                                    <strong>Instrucciones:</strong><br>
                                    Haga clic en cualquier lugar del mapa para seleccionar la nueva ubicación del reclamo.
                                </p>
                            </div>
                            <div class="mapa-modal__footer mapa-modal__footer--end">
                                <button type="button" class="reclamos-btn" data-bs-dismiss="modal" id="btnEntendido">
                                    <i class="bi bi-check-lg"></i> Entendido
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remover modal anterior si existe
            $('#modalInstruccionReubicacion').remove();
            
            // Agregar el modal al body
            $('body').append(modalHtml);
            
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('modalInstruccionReubicacion'));
            modal.show();

            // Manejar cierre del modal
            $('#modalInstruccionReubicacion').on('hidden.bs.modal', () => {
                $('#modalInstruccionReubicacion').remove();
            });
        },

        /**
         * Muestra una confirmación personalizada estilo cuadrillas
         */
        mostrarConfirmacion(mensaje, titulo = 'Confirmar Acción') {
            return new Promise((resolve) => {
                let resuelto = false;
                const modalHtml = `
                    <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content mapa-modal mapa-confirm-modal">
                                <div class="mapa-modal__header">
                                    <div class="mapa-modal__title">
                                        <span class="mapa-modal__icon"><i class="bi bi-question-circle"></i></span>
                                        <h5>${titulo}</h5>
                                    </div>
                                    <button type="button" class="mapa-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p class="mapa-confirm-modal__message">${mensaje}</p>
                                </div>
                                <div class="mapa-modal__footer mapa-modal__footer--end">
                                    <button type="button" class="reclamos-btn reclamos-btn--outline" data-bs-dismiss="modal" id="btnCancelar">Cancelar</button>
                                    <button type="button" class="reclamos-btn" id="btnConfirmar"><i class="bi bi-check-lg"></i> Confirmar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#modalConfirmacion').remove();
                $('body').append(modalHtml);

                const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
                modal.show();

                const cerrarConfirmacion = (resultado) => {
                    if (resuelto) return;
                    resuelto = true;
                    modal.hide();
                    setTimeout(() => {
                        $('#modalConfirmacion').remove();
                    }, 300);
                    resolve(resultado);
                };

                $('#btnConfirmar').on('click', () => cerrarConfirmacion(true));
                $('#btnCancelar').on('click', () => cerrarConfirmacion(false));

                $('#modalConfirmacion').on('hidden.bs.modal', () => {
                    $('#modalConfirmacion').remove();
                    if (!resuelto) {
                        resuelto = true;
                        resolve(false);
                    }
                });
            });
        },

        cerrarMenusToolbarMapa() {
            document.querySelectorAll('.mapa-reclamos-toolbar .dropdown-menu.show').forEach((menu) => {
                menu.classList.remove('show');
            });
            document.querySelectorAll('.mapa-reclamos-toolbar .dropdown-toggle.show').forEach((toggle) => {
                toggle.classList.remove('show');
                toggle.setAttribute('aria-expanded', 'false');
            });
        },

        async exportarMapaImagen() {
            if (this.exportandoMapa) {
                return;
            }
            if (!this.map) {
                this.mostrarMensaje('El mapa aún no terminó de cargar. Espere un momento e intente de nuevo.', 'warning');
                return;
            }
            if (!window.MapaExportImagen) {
                this.mostrarMensaje('No se pudo cargar el módulo de exportación del mapa', 'error');
                return;
            }

            this.exportandoMapa = true;
            this.cerrarMenusToolbarMapa();
            this.mostrarMensaje('Generando imagen del mapa...', 'info');

            try {
                const esMapbox = window.MapaExportImagen.esMapaMapbox(this.map);
                let dataUrl;

                if (esMapbox) {
                    dataUrl = await window.MapaExportImagen.exportarMapbox(
                        this.map,
                        this.marcadores,
                        (reclamo) => this.debeMostrarMarcador(reclamo)
                    );
                } else {
                    dataUrl = await window.MapaExportImagen.exportarGoogle(
                        document.getElementById('map'),
                        this.map
                    );
                }

                const nombreArchivo = window.MapaExportImagen.generarNombreArchivo(
                    esMapbox ? 'mapa-reclamos-mapbox' : 'mapa-reclamos-google'
                );
                window.MapaExportImagen.descargarDataUrl(dataUrl, nombreArchivo);
                this.mostrarMensaje(`Imagen exportada correctamente: ${nombreArchivo}`, 'success');
            } catch (error) {
                console.error('Error al exportar mapa:', error);
                this.mostrarMensaje(
                    'No se pudo exportar el mapa. Espere a que cargue por completo e intente nuevamente.',
                    'error'
                );
            } finally {
                this.exportandoMapa = false;
            }
        },

        /**
         * Muestra mensajes de notificación estilo cuadrillas
         */
        mostrarMensaje(mensaje, tipo) {
            // Si es un mensaje de éxito, eliminar mensajes de progreso anteriores
            if (tipo === 'success') {
                $('.alert-info.mensaje-notificacion').fadeOut(200, function() {
                    $(this).remove();
                });
            }
            
            // Crear y mostrar un toast o alert
            const alertClass = tipo === 'success' ? 'alert-success' : 
                              tipo === 'warning' ? 'alert-warning' : 
                              tipo === 'info' ? 'alert-info' : 'alert-danger';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed mensaje-notificacion" 
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                    ${mensaje}
                </div>
            `;
            
            $('body').append(alertHtml);
            
            // Auto-remover después de 5 segundos - solo los mensajes de notificación flotantes
            setTimeout(() => {
                $('.mensaje-notificacion').fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    },
    async mounted() {
        try {
            // Esperar a que Vue termine de renderizar completamente el DOM
            await this.$nextTick();
            
            // Pequeño delay adicional para asegurar que el navegador termine de pintar
            await new Promise(resolve => setTimeout(resolve, 100));
            
            // OPTIMIZACIÓN: Cargar datos en paralelo ANTES de inicializar el mapa
            const [reclamos, direcciones] = await Promise.all([
                this.obtenerReclamos(),
                this.obtenerDirecciones(),
                this.waitForMapbox()
            ]);
            
            // Ahora que los datos están en memoria, inicializar el mapa
            await this.iniciarMapaSF();
        } catch (e) {
            console.error(e);
            alert('Error: No se pudo cargar Mapbox');
        }
    }
});


