/* Exportación de mapa — ver también mapa_export_imagen.js */
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
            geocoder: null, // Geocoder de Google Maps
            infoWindowAbierto: null, // Referencia al info window actualmente abierto
            filtroEstado: '', // Filtro por estado del reclamo (deprecated)
            estadosSeleccionados: ['Recibido', 'Asignado', 'Pendiente', 'En ejecución'], // Por defecto sin Completado
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

        waitForGoogleMaps() {
            return new Promise((resolve, reject) => {
                const start = Date.now();
                const timeoutMs = 15000;
                const interval = setInterval(() => {
                    if (window.google && window.google.maps) {
                        clearInterval(interval);
                        resolve();
                    } else if (Date.now() - start > timeoutMs) {
                        clearInterval(interval);
                        reject(new Error('Timeout esperando Google Maps'));
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
                                esPersonalizada: true
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

                return new Promise((resolve, reject) => {
                    this.geocoder.geocode({ address: direccionPrincipal }, (results, status) => {
                        if (status === 'OK' && results[0]) {
                            const location = results[0].geometry.location;
                            resolve({
                                lat: location.lat(),
                                lng: location.lng(),
                                direccion: direccionPrincipal,
                                confianza: results[0].geometry.location_type,
                                esPersonalizada: false
                            });
                        } else {
                            console.warn('No se encontró la dirección:', direccionPrincipal, 'Status:', status);
                            resolve(null);
                        }
                    });
                });
            } catch (error) {
                console.error('Error en geocodificación:', error);
                return null;
            }
        },

        async agregarMarcadoresReclamos() {
            // Limpiar marcadores existentes
            this.marcadores.forEach(marker => {
                marker.setMap(null);
                // Forzar eliminación del DOM como en Mapbox
                if (marker.setVisible) {
                    marker.setVisible(false);
                }
            });
            this.marcadores = [];
            
            // Limpiar referencia al info window abierto
            if (this.infoWindowAbierto) {
                this.infoWindowAbierto.close();
                this.infoWindowAbierto = null;
            }
            
            // Limpieza adicional del DOM de Google Maps
            setTimeout(() => {
                const mapContainer = document.getElementById('map');
                if (mapContainer) {
                    // Buscar y eliminar elementos huérfanos de Google Maps
                    const orphanElements = mapContainer.querySelectorAll('[style*="position: absolute"]');
                    orphanElements.forEach(element => {
                        if (element.style && element.style.zIndex && element.style.zIndex > 1000) {
                            element.remove();
                        }
                    });
                }
            }, 50);

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

            for (const { reclamo, coords: coordenadas } of resultados) {
                if (coordenadas) {
                    // Contar estados
                    const estado = reclamo.municipalidad_estado || 'Recibido';
                    if (contadorEstados.hasOwnProperty(estado)) {
                        contadorEstados[estado]++;
                    } else {
                        contadorEstados['Recibido']++; // Estado por defecto
                    }

                    // Crear el contenido del info window (sin el título)
                    const infoWindowContent = `
                        <div class="mapa-popup-reclamo">
                            <p style="margin-bottom: 4px;"><strong>Motivo:</strong> ${reclamo.municipalidad_motivo || 'No especificado'}</p>
                            <p style="margin-bottom: 4px;"><strong>Estado:</strong> ${reclamo.municipalidad_estado || 'No especificado'}</p>
                            <p style="margin-bottom: 4px;"><strong>Prioridad:</strong> ${reclamo.prioridad || 'No especificado'}</p>
                            <p style="margin-bottom: 4px;"><strong>Dirección:</strong> ${reclamo.municipalidad_domicilio || 'No especificado'} ${reclamo.municipalidad_numeroDomicilio || ''}</p>
                            <p style="margin-bottom: 4px;"><strong>Fecha:</strong> ${this.formatearFecha(reclamo.municipalidad_fechaInicio)}</p>
                            <p style="margin-bottom: 4px;"><strong>Ciudadano:</strong> ${reclamo.municipalidad_ciudadano || 'No especificado'}</p>
                            <div class="mapa-popup-acciones d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-warning mapa-popup-reubicar" data-reclamo-id="${reclamo.id}">
                                    <i class="bi bi-geo-alt"></i> Reubicar
                                </button>
                                <button type="button" class="btn btn-sm btn-primary mapa-popup-detalle" data-reclamo-id="${reclamo.id}">
                                    <i class="bi bi-card-text text-white"></i> Ver detalle
                                </button>
                            </div>
                        </div>
                    `;

                    // Crear el marcador con color según estado del reclamo
                    let color = '#808080'; // Gris por defecto
                    if (reclamo.municipalidad_estado === 'Recibido') color = '#808080'; // Gris
                    else if (reclamo.municipalidad_estado === 'Asignado') color = '#0DCAF0'; // Celeste
                    else if (reclamo.municipalidad_estado === 'Pendiente') color = '#FF0000'; // Rojo (obra pausada)
                    else if (reclamo.municipalidad_estado === 'En ejecución') color = '#FFD700'; // Amarillo
                    else if (reclamo.municipalidad_estado === 'Completado') color = '#198754'; // Verde Bootstrap success
                    else if (reclamo.municipalidad_estado === 'En plan') color = '#808080'; // Gris
                    else if (reclamo.municipalidad_estado === 'Error de datos') color = '#808080'; // Gris

                    const iconoMotivo = this.escaparTextoSvg(this.iconoMotivoReclamo(reclamo.municipalidad_motivo));

                    // Crear marcador de Google Maps con color por estado e icono por motivo
                    const marker = new google.maps.Marker({
                        position: { lat: coordenadas.lat, lng: coordenadas.lng },
                        map: this.map,
                        title: `Reclamo #${reclamo.municipalidad_id}`,
                        icon: {
                            url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                                <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M16 2.5C10.75 2.5 6.5 6.75 6.5 12c0 7.1 9.5 17.5 9.5 17.5S25.5 19.1 25.5 12C25.5 6.75 21.25 2.5 16 2.5Z" fill="${color}" stroke="#FFFFFF" stroke-width="2"/>
                                    <circle cx="16" cy="12" r="7.4" fill="#FFFFFF" opacity="0.94"/>
                                    <text x="16.8" y="12.7" text-anchor="middle" dominant-baseline="middle" font-family="Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif" font-size="12">${iconoMotivo}</text>
                                </svg>
                            `)}`,
                            scaledSize: new google.maps.Size(32, 32),
                            anchor: new google.maps.Point(16, 32)
                        }
                    });

                    // Crear info window con título personalizado
                    const infoWindow = new google.maps.InfoWindow({
                        content: infoWindowContent
                    });

                    // Agregar evento de clic al marcador
                    marker.addListener('click', () => {
                        // Cerrar cualquier info window abierto anteriormente
                        if (this.infoWindowAbierto) {
                            this.infoWindowAbierto.close();
                        }
                        
                        // Abrir el nuevo info window
                        infoWindow.open(this.map, marker);
                        
                        // Guardar referencia al info window actualmente abierto
                        this.infoWindowAbierto = infoWindow;
                        
                        // Después de abrir el info window, modificar el encabezado
                        setTimeout(() => {
                            const headerElement = document.querySelector('.gm-style-iw-ch');
                            if (headerElement) {
                                headerElement.innerHTML = `<h6>Reclamo #${reclamo.municipalidad_id}</h6>`;
                            }

                            const btnReubicar = document.querySelector(`.mapa-popup-reubicar[data-reclamo-id="${reclamo.id}"]`);
                            if (btnReubicar) {
                                btnReubicar.onclick = () => this.iniciarReubicacion(reclamo);
                            }

                            const btnDetalle = document.querySelector(`.mapa-popup-detalle[data-reclamo-id="${reclamo.id}"]`);
                            if (btnDetalle) {
                                btnDetalle.onclick = () => this.verReclamo(reclamo);
                            }
                        }, 100);
                    });

                    // Guardar referencia al reclamo en el marcador
                    marker._reclamo = reclamo;
                    marker._infoWindow = infoWindow;
                    this.marcadores.push(marker);
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
            this.actualizarTextoBotonPrioridad();
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

                // Buscar el marcador correspondiente
                const marcador = this.marcadores.find(marker => 
                    marker._reclamo && marker._reclamo.id === reclamo.id
                );

                if (marcador) {
                    // Mover el mapa suavemente hacia el marcador.
                    const posicionMarcador = marcador.getPosition();
                    this.map.panTo(posicionMarcador);

                    if (this.map.getZoom() < 16) {
                        setTimeout(() => {
                            this.map.setZoom(16);
                        }, 350);
                    }

                    // Crear animación de rebote
                    marcador.setAnimation(google.maps.Animation.BOUNCE);
                    
                    // Detener la animación después de 2 segundos
                    setTimeout(() => {
                        marcador.setAnimation(null);
                    }, 2000);

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
            this.map.setOptions({ draggableCursor: 'crosshair' });
            
            // Agregar evento de clic en el mapa
            this.map.addListener('click', this.onMapClick);
            
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
            const mensajeConfirmacion = '¿Está seguro de que desea eliminar la ubicación personalizada? El punto volverá a usar las coordenadas automáticas de Google Maps.';
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Eliminar Ubicación Personalizada');
            
            if (!confirmacion) {
                return;
            }

            try {
                // Eliminar la dirección personalizada
                await axios.delete(BASE_URL + 'api/direcciones/' + this.ubicacionPersonalizada.id);
                
                // Cerrar modal de estado
                const modalEstado = bootstrap.Modal.getInstance(document.getElementById('modalEstadoUbicacion'));
                if (modalEstado) {
                    modalEstado.hide();
                }
                
                // Limpiar estado y caché del reclamo afectado
                const idReclamoAfectado = this.reclamoParaReubicar.id;
                this.ubicacionPersonalizada = null;
                this.reclamoParaReubicar = {};
                
                // Limpiar caché de coordenadas del reclamo para forzar recálculo
                if (idReclamoAfectado) {
                    delete this.cacheCoordenadasReclamos[idReclamoAfectado];
                }
                
                // Recargar marcadores y tabla
                await this.obtenerDirecciones();
                await this.agregarMarcadoresReclamos();
                this.inicializarTabla();
                
                this.mostrarMensaje('Ubicación personalizada eliminada correctamente', 'success');
                
            } catch (error) {
                console.error('Error al eliminar ubicación personalizada:', error);
                this.mostrarMensaje('Error al eliminar la ubicación personalizada', 'error');
            }
        },

        async buscarYCentrarEnReclamo(reclamo) {
            const coordenadas = await this.obtenerCoordenadasReclamo(reclamo);
            if (coordenadas) {
                this.map.setCenter({ lat: coordenadas.lat, lng: coordenadas.lng });
                this.map.setZoom(16);
            }
        },

        onMapClick(event) {
            if (this.modoReubicacion) {
                this.nuevaUbicacion = {
                    lat: event.latLng.lat(),
                    lng: event.latLng.lng()
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
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmar Reubicación</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>¿Confirma que desea reubicar el punto del reclamo en esta nueva ubicación?</strong>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-success" id="btnConfirmarReubicacion">
                                    <i class="bi bi-geo-alt text-white"></i> Confirmar Reubicación
                                </button>
                                <button type="button" class="btn btn-secondary" id="btnCancelarReubicacion">
                                    Cancelar
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
                
                // Limpiar caché de coordenadas del reclamo para forzar recálculo
                const idReclamoAfectado = this.reclamoParaReubicar.id;
                if (idReclamoAfectado) {
                    delete this.cacheCoordenadasReclamos[idReclamoAfectado];
                }
                
                // Limpiar estado
                this.limpiarModoReubicacion();
                
                // Recargar marcadores y tabla
                await this.obtenerDirecciones();
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
            this.map.setOptions({ draggableCursor: null });
            
            // Remover evento de clic
            google.maps.event.clearListeners(this.map, 'click');
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
            this.actualizarTextoBotonPrioridad();
        },

        toggleTodasPrioridades(event) {
            if (event.target.checked) {
                this.prioridadesSeleccionadas = [];
            } else {
                this.prioridadesSeleccionadas = ['Alta', 'Baja'];
            }

            this.aplicarFiltroMarcadores();
            this.actualizarTextoBotonPrioridad();
        },

        debeMostrarMarcador(reclamo) {
            const estadoReclamo = reclamo.municipalidad_estado || 'Recibido';
            const cumpleEstado = this.estadosSeleccionados.length === 0 || this.estadosSeleccionados.includes(estadoReclamo);

            const prioridadReclamo = (reclamo.prioridad || 'Baja').trim();
            const cumplePrioridad = this.prioridadesSeleccionadas.length === 0 || this.prioridadesSeleccionadas.includes(prioridadReclamo);

            return cumpleEstado && cumplePrioridad;
        },

        actualizarTextoBotonPrioridad() {
            const dropdownButton = document.querySelector('.mapa-filtro-prioridad-toggle');
            if (!dropdownButton) {
                return;
            }

            if (this.prioridadesSeleccionadas.length === 0) {
                dropdownButton.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Filtrar por Prioridad';
            } else if (this.prioridadesSeleccionadas.length === 2) {
                dropdownButton.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Todas las Prioridades';
            } else {
                const iconos = {
                    'Alta': '🔺',
                    'Baja': '🔻'
                };
                const iconosSeleccionados = this.prioridadesSeleccionadas.map(prioridad => iconos[prioridad]).join(' ');
                dropdownButton.innerHTML = `<i class="bi bi-exclamation-triangle"></i> ${iconosSeleccionados}`;
            }
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
            this.marcadores.forEach(marker => {
                const reclamo = marker._reclamo;
                if (reclamo) {
                    marker.setVisible(this.debeMostrarMarcador(reclamo));
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

            this.marcadores.forEach(marker => {
                if (marker.getVisible()) {
                    const reclamo = marker._reclamo;
                    if (reclamo) {
                        contadorVisible++;
                        const estado = reclamo.municipalidad_estado || 'Recibido';
                        if (contadorEstados.hasOwnProperty(estado)) {
                            contadorEstados[estado]++;
                        }
                    }
                }
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
                    
                    const baseUrl = BASE_URL.endsWith('/') ? BASE_URL.slice(0, -1) : BASE_URL;
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
            const lng = -62.082;

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

            // Crear el mapa
            this.map = new google.maps.Map(contenedorMapa, {
                center: { lat: lat, lng: lng },
                zoom: 13,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                // Desactivar marcadores de POI (estaciones de servicio, negocios, etc.)
                styles: [
                    {
                        featureType: "poi",
                        elementType: "labels",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "poi.business",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "poi.government",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "poi.medical",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "poi.place_of_worship",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "poi.school",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "poi.sports_complex",
                        stylers: [{ visibility: "off" }]
                    },
                    {
                        featureType: "transit.station",
                        stylers: [{ visibility: "off" }]
                    }
                ]
            });

            // Inicializar el geocoder
            this.geocoder = new google.maps.Geocoder();

            // Agregar controles de navegación
            this.map.setOptions({
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: false
            });

            console.log('Mapa de San Francisco (Google Maps) cargado correctamente');
            
            // Agregar marcadores y tabla (los datos ya están precargados)
            await this.agregarMarcadoresReclamos();
            this.inicializarTabla();
        },

        /**
         * Muestra un modal de instrucción para el modo de reubicación
         */
        mostrarModalInstruccion() {
            const modalHtml = `
                <div class="modal fade" id="modalInstruccionReubicacion" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title">
                                    <i class="bi bi-geo-alt me-2"></i>Modo de Reubicación Activado
                                </h5>
                                <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center">
                                    <i class="bi bi-cursor-fill text-info" style="font-size: 3rem;"></i>
                                    <p class="mt-3 mb-0">
                                        <strong>Instrucciones:</strong><br>
                                        Haga clic en cualquier lugar del mapa para seleccionar la nueva ubicación del reclamo.
                                    </p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-info" data-bs-dismiss="modal" id="btnEntendido">
                                    <i class="bi bi-check-circle me-1"></i>Entendido
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
                // Crear el modal de confirmación
                const modalHtml = `
                    <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-warning text-dark">
                                    <h5 class="modal-title">
                                        <i class="bi bi-question-circle me-2"></i>${titulo}
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center">
                                        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                                        <p class="mt-3 mb-0">${mensaje}</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnCancelar">
                                        <i class="bi bi-x-circle me-1 text-white"></i>Cancelar
                                    </button>
                                    <button type="button" class="btn btn-warning" id="btnConfirmar">
                                        <i class="bi bi-check-circle me-1"></i>Confirmar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Remover modal anterior si existe
                $('#modalConfirmacion').remove();
                
                // Agregar el modal al body
                $('body').append(modalHtml);
                
                // Mostrar el modal
                const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
                modal.show();

                // Manejar botones
                $('#btnConfirmar').on('click', () => {
                    modal.hide();
                    setTimeout(() => {
                        $('#modalConfirmacion').remove();
                    }, 300);
                    resolve(true);
                });

                $('#btnCancelar').on('click', () => {
                    modal.hide();
                    setTimeout(() => {
                        $('#modalConfirmacion').remove();
                    }, 300);
                    resolve(false);
                });

                // Manejar cierre del modal (X o ESC)
                $('#modalConfirmacion').on('hidden.bs.modal', () => {
                    $('#modalConfirmacion').remove();
                    resolve(false);
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
                this.waitForGoogleMaps()
            ]);
            
            // Ahora que los datos están en memoria, inicializar el mapa
            await this.iniciarMapaSF();
        } catch (e) {
            console.error(e);
            alert('Error: No se pudo cargar Google Maps');
        }
    }
});


