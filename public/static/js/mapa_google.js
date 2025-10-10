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
            estadosSeleccionados: [] // Array de estados seleccionados para filtrado múltiple
        };
    },
    methods: {
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
            // Primero verificar si existe una dirección personalizada
            if (reclamo.municipalidad_domicilio && reclamo.municipalidad_numeroDomicilio) {
                console.log(`Buscando dirección personalizada para: ${reclamo.municipalidad_domicilio} ${reclamo.municipalidad_numeroDomicilio}`);
                
                const direccionPersonalizada = await this.buscarDireccionPersonalizada(
                    reclamo.municipalidad_domicilio, 
                    reclamo.municipalidad_numeroDomicilio
                );

                if (direccionPersonalizada && direccionPersonalizada.latitud && direccionPersonalizada.longitud) {
                    // Manejar diferentes formatos de coordenadas (decimal, string, etc.)
                    let lat = direccionPersonalizada.latitud;
                    let lng = direccionPersonalizada.longitud;
                    
                    // Convertir a número si es necesario
                    if (typeof lat === 'string') {
                        lat = parseFloat(lat);
                    }
                    if (typeof lng === 'string') {
                        lng = parseFloat(lng);
                    }
                    
                    console.log('🔍 Validando coordenadas:', {
                        latOriginal: direccionPersonalizada.latitud,
                        lngOriginal: direccionPersonalizada.longitud,
                        latTipo: typeof direccionPersonalizada.latitud,
                        lngTipo: typeof direccionPersonalizada.longitud,
                        latConvertido: lat,
                        lngConvertido: lng,
                        latEsNaN: isNaN(lat),
                        lngEsNaN: isNaN(lng)
                    });
                    
                    // Validar que las coordenadas sean números válidos
                    if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                        console.log('✅ Usando coordenadas personalizadas:', {
                            domicilio: reclamo.municipalidad_domicilio,
                            numero: reclamo.municipalidad_numeroDomicilio,
                            lat: lat,
                            lng: lng,
                            latOriginal: direccionPersonalizada.latitud,
                            lngOriginal: direccionPersonalizada.longitud
                        });
                        return {
                            lat: lat,
                            lng: lng,
                            esPersonalizada: true
                        };
                    } else {
                        console.log('⚠️ Coordenadas personalizadas inválidas:', {
                            lat: direccionPersonalizada.latitud,
                            lng: direccionPersonalizada.longitud,
                            latConvertido: lat,
                            lngConvertido: lng,
                            latEsNaN: isNaN(lat),
                            lngEsNaN: isNaN(lng),
                            latEsCero: lat === 0,
                            lngEsCero: lng === 0
                        });
                    }
                } else {
                    console.log('❌ No se encontró dirección personalizada válida, usando Google Maps');
                }
            } else {
                console.log('⚠️ Reclamo sin domicilio completo, usando Google Maps');
            }

            // Si no hay dirección personalizada, usar geocodificación de Google Maps
            return await this.geocodificarDireccion(reclamo);
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
                'En ejecución': 0,
                'Completado': 0,
                'En plan': 0,
                'Error de datos': 0
            };


            for (const reclamo of this.reclamos) {
                const coordenadas = await this.obtenerCoordenadasReclamo(reclamo);
                
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
                        <div style="min-width: 200px;">
                            <p style="margin-bottom: 4px;"><strong>Motivo:</strong> ${reclamo.municipalidad_motivo || 'No especificado'}</p>
                            <p style="margin-bottom: 4px;"><strong>Estado:</strong> ${reclamo.municipalidad_estado || 'No especificado'}</p>
                            <p style="margin-bottom: 4px;"><strong>Prioridad:</strong> ${reclamo.prioridad || 'No especificado'}</p>
                            <p style="margin-bottom: 4px;"><strong>Dirección:</strong> ${reclamo.municipalidad_domicilio || 'No especificado'} ${reclamo.municipalidad_numeroDomicilio || ''}</p>
                            <p style="margin-bottom: 4px;"><strong>Fecha:</strong> ${this.formatearFecha(reclamo.municipalidad_fechaInicio)}</p>
                            <p style="margin-bottom: 4px;"><strong>Ciudadano:</strong> ${reclamo.municipalidad_ciudadano || 'No especificado'}</p>
                            
                        </div>
                    `;

                    // Crear el marcador con color según estado del reclamo
                    let color = '#808080'; // Gris por defecto
                    if (reclamo.municipalidad_estado === 'Recibido') color = '#808080'; // Gris
                    else if (reclamo.municipalidad_estado === 'Asignado') color = '#FF0000'; // Rojo
                    else if (reclamo.municipalidad_estado === 'En ejecución') color = '#FFD700'; // Amarillo
                    else if (reclamo.municipalidad_estado === 'Completado') color = '#198754'; // Verde Bootstrap success
                    else if (reclamo.municipalidad_estado === 'En plan') color = '#808080'; // Gris
                    else if (reclamo.municipalidad_estado === 'Error de datos') color = '#808080'; // Gris

                    // Crear marcador de Google Maps con icono de ubicación
                    const marker = new google.maps.Marker({
                        position: { lat: coordenadas.lat, lng: coordenadas.lng },
                        map: this.map,
                        title: `Reclamo #${reclamo.municipalidad_id}`,
                        // Usar un icono personalizado de ubicación
                        icon: {
                            url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                                <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="${color}" stroke="#FFFFFF" stroke-width="1"/>
                                </svg>
                            `)}`,
                            scaledSize: new google.maps.Size(24, 24),
                            anchor: new google.maps.Point(12, 24)
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
            console.log(`   - 🔴 Asignado: ${contadorEstados['Asignado']}`);
            console.log(`   - 🟡 En ejecución: ${contadorEstados['En ejecución']}`);
            console.log(`   - 🟢 Completado: ${contadorEstados['Completado']}`);
            console.log(`   - ⚫ En plan: ${contadorEstados['En plan']}`);
            console.log(`   - ⚫ Error de datos: ${contadorEstados['Error de datos']}`);
            
            // Aplicar filtro si está activo
            if (this.filtroEstado) {
                this.aplicarFiltroMarcadores();
            }
        },

        inicializarTabla() {
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
                            else if (row.municipalidad_estado === 'Asignado') color = '#FF0000'; // Rojo
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
                    // Centrar el mapa en el marcador
                    this.map.setCenter(marcador.getPosition());
                    this.map.setZoom(16);

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
                
                // Limpiar estado
                this.ubicacionPersonalizada = null;
                this.reclamoParaReubicar = {};
                
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
            const dropdownButton = document.querySelector('.dropdown-toggle');
            if (dropdownButton) {
                if (estado === '') {
                    dropdownButton.innerHTML = '<i class="bi bi-funnel"></i> Filtrar por Estado';
                } else {
                    const iconos = {
                        'Recibido': '⚫',
                        'Asignado': '🔴',
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
                this.estadosSeleccionados = ['Recibido', 'Asignado', 'En ejecución', 'Completado', 'En plan', 'Error de datos'];
            }
            
            this.aplicarFiltroMarcadores();
            this.actualizarTextoBoton();
        },

        actualizarTextoBoton() {
            const dropdownButton = document.querySelector('.dropdown-toggle');
            if (dropdownButton) {
                if (this.estadosSeleccionados.length === 0) {
                    dropdownButton.innerHTML = '<i class="bi bi-funnel"></i> Filtrar por Estado';
                } else if (this.estadosSeleccionados.length === 6) {
                    dropdownButton.innerHTML = '<i class="bi bi-funnel"></i> Todos los Estados';
                } else {
                    const iconos = {
                        'Recibido': '⚫',
                        'Asignado': '🔴',
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
                    const estadoReclamo = reclamo.municipalidad_estado || 'Recibido';
                    const debeMostrar = this.estadosSeleccionados.length === 0 || this.estadosSeleccionados.includes(estadoReclamo);
                    
                    if (debeMostrar) {
                        marker.setVisible(true);
                    } else {
                        marker.setVisible(false);
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
            console.log(`   - 🔴 Asignado: ${contadorEstados['Asignado']}`);
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

            // Crear el mapa
            this.map = new google.maps.Map(document.getElementById('map'), {
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
                fullscreenControl: true
            });

            console.log('Mapa de San Francisco (Google Maps) cargado correctamente');
            
            // Ejecutar prueba de direcciones
            await this.probarDirecciones();
            
            // Obtener datos y inicializar
            await this.obtenerReclamos();
            await this.obtenerDirecciones();
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
            await this.waitForGoogleMaps();
            this.iniciarMapaSF();
        } catch (e) {
            console.error(e);
            alert('Error: No se pudo cargar Google Maps');
        }
    }
});


