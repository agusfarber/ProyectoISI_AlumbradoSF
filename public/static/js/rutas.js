// JS vinculado a la vista rutas.php para ver el listado completo de las rutas que hay en una tabla

const app = Vue.createApp({
    data() {
        return {
            rutas: [],
            reclamos: [],
            marcadores: [],
            tabla: null,
            mapa: null,
            geocoder: null,
            
            // Datos para nueva ruta
            nuevaRuta: {
                nombre: 'Hoja de ruta',
                color: '#FF6B35',
                cantidadReclamos: 5,
                seleccionManual: false,
                primerReclamoManual: false
            },
            
            // Reclamos seleccionados para la nueva ruta
            reclamosSeleccionados: [],
            primerReclamoSeleccionado: null,
            reclamosDisponibles: 0,
            
            // Modo de selección
            modoSeleccionManual: false,
            modoSeleccionPrimerReclamo: false,
            
            // Modo de edición
            modoEdicion: false,
            rutaOriginal: [], // Guardar la ruta original antes de editar
            
            // Vista previa de la ruta
            vistaPrevia: {
                activa: false,
                reclamos: [],
                rutaOptimizada: [],
                tiempoEstimado: 0,
                distanciaTotal: 0,
                marcadoresRuta: [],
                marcadoresOtros: [],
                marcadoresRutasActivas: [], // Marcadores de otras rutas activas
                polylineRuta: null,
                directionsRenderer: null
            },
            
            // Visualización de ruta
            rutaVisualizando: {},
            reclamosRutaVisualizando: [],
            mapaVisualizacion: null,
            marcadoresVisualizacion: [],
            directionsRendererVisualizacion: null,
            infoWindowAbiertoVisualizacion: null,
            infoWindowAbiertoVistaPrevia: null,
            
            // Visualización de todas las rutas (asignadas y no asignadas)
            rutasActivas: [],
            mapaRutasActivas: null,
            marcadoresRutasActivas: [],
            directionsRenderersRutasActivas: [],
            infoWindowAbiertoRutasActivas: null,
            
            // Cache de coordenadas y optimización
            cacheCoordenadasReclamos: {}, // Cache de coordenadas por reclamo ID
            direccionesPersonalizadas: [], // Todas las direcciones personalizadas pre-cargadas
            
            // Control de proveedores de mapa
            proveedorMapaVistaPrevia: 'google', // 'google' o 'mapbox'
            proveedorMapaVisualizacion: 'google', // Para modal de ver ruta individual
            proveedorMapaRutasActivas: 'google', // Para modal de todas las rutas activas
            
            // Mapas Mapbox
            mapaMapbox: null,
            mapaVisualizacionMapbox: null,
            mapaRutasActivasMapbox: null,
            
            // API Key de Mapbox
            mapboxToken: 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ejJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw'
        };
    },

    computed: {
        puedeGenerarRuta() {
            return this.nuevaRuta.nombre && 
                   this.nuevaRuta.nombre.trim() !== '' &&
                   this.nuevaRuta.cantidadReclamos > 0 && 
                   this.reclamosDisponibles >= this.nuevaRuta.cantidadReclamos &&
                   this.vistaPrevia.activa; // Solo puede generar si ya vio la vista previa
        },
        
        puedeVerVistaPrevia() {
            return this.nuevaRuta.nombre && 
                   this.nuevaRuta.nombre.trim() !== '' &&
                   this.nuevaRuta.cantidadReclamos > 0 && 
                   this.reclamosDisponibles >= this.nuevaRuta.cantidadReclamos &&
                   !this.vistaPrevia.activa; // Solo si no está activa aún
        }
    },

    watch: {
        // Watchers eliminados - la vista previa ahora es manual
    },

    methods: {
        /**
         * Obtiene las rutas desde la API
         */
        async obtenerRutas() {
            try {
                const response = await axios.get(BASE_URL + 'api/rutas');
                this.rutas = response.data;
                this.$nextTick(() => {
                    this.inicializarTabla();
                });
            } catch (error) {
                console.error('Error al obtener rutas:', error);
                this.mostrarMensaje('Error al obtener las rutas', 'error');
            }
        },

        /**
         * Obtiene los reclamos disponibles desde la API
         */
        async obtenerReclamos() {
            try {
                const response = await axios.get(BASE_URL + 'api/reclamos');
                this.reclamos = response.data;
                // Contar solo reclamos no completados
                this.reclamosDisponibles = this.reclamos.filter(r => r.municipalidad_estado !== 'Completado').length;
                
                // Pre-cargar todas las direcciones personalizadas
                await this.preCargarDireccionesPersonalizadas();
            } catch (error) {
                console.error('Error al obtener reclamos:', error);
            }
        },

        /**
         * Pre-carga todas las direcciones personalizadas de una vez
         */
        async preCargarDireccionesPersonalizadas() {
            try {
                const baseUrl = BASE_URL.endsWith('/') ? BASE_URL.slice(0, -1) : BASE_URL;
                const response = await axios.get(`${baseUrl}/api/direcciones`);
                this.direccionesPersonalizadas = response.data;
                console.log(`Direcciones personalizadas pre-cargadas: ${this.direccionesPersonalizadas.length}`);
            } catch (error) {
                console.error('Error al pre-cargar direcciones:', error);
                this.direccionesPersonalizadas = [];
            }
        },

        /**
         * Inicializa la tabla de rutas
         */
        inicializarTabla() {
            if (this.tabla) {
                this.tabla.destroy();
            }

            this.tabla = $('#tabla_rutas').DataTable({
                data: this.rutas,
                responsive: true,
                columns: [
                    {
                        data: 'nombre',
                        className: 'text-start',
                        render: (data, type, row) => {
                            const nombre = data || 'Sin nombre';
                            const color = row.color || '#808080';
                            return `
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width: 20px; height: 20px; border-radius: 50%; background-color: ${color}; border: 2px solid #dee2e6; flex-shrink: 0;"></div>
                                    <span>${nombre}</span>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'cantidadReclamos',
                        className: 'text-start'
                    },
                    {
                        data: 'tiempoEstimado',
                        className: 'text-start'
                    },
                    {
                        data: 'asignada',
                        className: 'text-start',
                        render: (data) => {
                            return data == 1 ? 
                                '<span class="badge bg-success">Asignada</span>' : 
                                '<span class="badge bg-secondary">No Asignada</span>';
                        }
                    },
                    {
                        data: 'fecha',
                        className: 'text-start',
                        render: (data) => this.formatearFecha(data)
                    },
                    {
                        data: null,
                        className: 'text-start',
                        render: (data, type, row) => {
                            return `
                                <button class="btn btn-sm btn-info me-1 ver-ruta" data-id="${row.id}" title="Ver ruta en mapa">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-danger eliminar-ruta" data-id="${row.id}" title="Eliminar ruta">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }
                    }
                ],
                order: [[4, 'desc']]
            });

            // Eventos de la tabla
            $('#tabla_rutas tbody').off('click', '.ver-ruta').on('click', '.ver-ruta', (e) => {
                const id = $(e.currentTarget).data('id');
                this.verRuta(id);
            });

            $('#tabla_rutas tbody').off('click', '.eliminar-ruta').on('click', '.eliminar-ruta', (e) => {
                const id = $(e.currentTarget).data('id');
                this.eliminarRuta(id);
            });
        },

        /**
         * Abre el modal para crear una nueva ruta automática
         */
        async abrirModalCrearRuta() {
            // Resetear datos
            this.resetearModal();
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalCrearRuta'));
            modal.show();
        },

        /**
         * Resetea todos los datos del modal
         */
        resetearModal() {
            this.nuevaRuta = {
                nombre: 'Hoja de ruta',
                color: '#FF6B35',
                cantidadReclamos: 5,
                seleccionManual: false,
                primerReclamoManual: false
            };
            this.reclamosSeleccionados = [];
            this.primerReclamoSeleccionado = null;
            this.modoSeleccionManual = false;
            this.modoSeleccionPrimerReclamo = false;
            this.modoEdicion = false;
            this.rutaOriginal = [];
            this.limpiarVistaPrevia();
        },

        /**
         * Vuelve a la configuración inicial (limpia vista previa)
         */
        volverAConfigurar() {
            this.limpiarVistaPrevia();
            this.vistaPrevia.activa = false;
            this.modoEdicion = false;
            this.rutaOriginal = [];
        },

        /**
         * Activa el modo de edición de la ruta
         */
        activarModoEdicion() {
            this.modoEdicion = true;
            // Guardar copia de la ruta original por si cancela
            this.rutaOriginal = JSON.parse(JSON.stringify(this.vistaPrevia.rutaOptimizada));
            this.mostrarMensaje('Modo edición activado. Haga clic en los reclamos del mapa para agregarlos.', 'info');
        },

        /**
         * Cancela la edición y vuelve a la ruta original
         */
        cancelarEdicion() {
            this.vistaPrevia.rutaOptimizada = JSON.parse(JSON.stringify(this.rutaOriginal));
            this.modoEdicion = false;
            this.rutaOriginal = [];
            // Actualizar el mapa para reflejar la ruta original
            this.actualizarMapaVistaPrevia();
            this.mostrarMensaje('Edición cancelada. Se restauró la ruta original.', 'info');
        },

        /**
         * Mueve un reclamo hacia arriba en la lista
         */
        moverReclamoArriba(index) {
            if (index === 0) return;
            
            const temp = this.vistaPrevia.rutaOptimizada[index];
            this.vistaPrevia.rutaOptimizada[index] = this.vistaPrevia.rutaOptimizada[index - 1];
            this.vistaPrevia.rutaOptimizada[index - 1] = temp;
            
            // Forzar actualización de Vue
            this.vistaPrevia.rutaOptimizada = [...this.vistaPrevia.rutaOptimizada];
            
            // Actualizar el mapa
            this.actualizarMapaVistaPrevia();
        },

        /**
         * Mueve un reclamo hacia abajo en la lista
         */
        moverReclamoAbajo(index) {
            if (index === this.vistaPrevia.rutaOptimizada.length - 1) return;
            
            const temp = this.vistaPrevia.rutaOptimizada[index];
            this.vistaPrevia.rutaOptimizada[index] = this.vistaPrevia.rutaOptimizada[index + 1];
            this.vistaPrevia.rutaOptimizada[index + 1] = temp;
            
            // Forzar actualización de Vue
            this.vistaPrevia.rutaOptimizada = [...this.vistaPrevia.rutaOptimizada];
            
            // Actualizar el mapa
            this.actualizarMapaVistaPrevia();
        },

        /**
         * Elimina un reclamo de la ruta
         */
        eliminarReclamoDeRuta(index) {
            const reclamo = this.vistaPrevia.rutaOptimizada[index];
            this.vistaPrevia.rutaOptimizada.splice(index, 1);
            
            this.mostrarMensaje(`Reclamo #${reclamo.municipalidad_id} eliminado de la ruta`, 'success');
            
            // Actualizar el mapa
            this.actualizarMapaVistaPrevia();
        },

        /**
         * Agrega un reclamo a la ruta al hacer clic en el mapa (solo en modo edición)
         */
        async agregarReclamoARuta(reclamo) {
            if (!this.modoEdicion) return;
            
            // Verificar si el reclamo ya está en la ruta
            const yaEstaEnRuta = this.vistaPrevia.rutaOptimizada.find(r => r.id === reclamo.id);
            if (yaEstaEnRuta) {
                this.mostrarMensaje('Este reclamo ya está en la ruta', 'warning');
                return;
            }
            
            // Verificar si el reclamo está en otra ruta
            const estaEnOtraRuta = await this.verificarReclamoEnOtraRuta(reclamo.id);
            if (estaEnOtraRuta) {
                this.mostrarMensaje('Este reclamo ya está en otra hoja de ruta', 'warning');
                return;
            }
            
            // Verificar si el reclamo está completado
            if (reclamo.municipalidad_estado === 'Completado') {
                this.mostrarMensaje('No se pueden agregar reclamos completados', 'warning');
                return;
            }
            
            // Agregar el reclamo al final de la ruta
            this.vistaPrevia.rutaOptimizada.push(reclamo);
            
            this.mostrarMensaje(`Reclamo #${reclamo.municipalidad_id} agregado a la ruta`, 'success');
            
            // Actualizar el mapa
            this.actualizarMapaVistaPrevia();
        },

        /**
         * Verifica si un reclamo está en otra ruta (asignada o no asignada)
         */
        async verificarReclamoEnOtraRuta(reclamoId) {
            try {
                // Verificar en TODAS las rutas (asignadas y no asignadas)
                // Las rutas no asignadas son las que aún no se asignaron a una cuadrilla, pero los reclamos están reservados
                const todasLasRutas = this.rutas;
                
                for (const ruta of todasLasRutas) {
                    const response = await axios.get(BASE_URL + 'api/rutas/' + ruta.id + '/reclamos');
                    const reclamosRuta = response.data;
                    
                    const estaEnEstaRuta = reclamosRuta.find(r => r.id === reclamoId);
                    if (estaEnEstaRuta) {
                        return true;
                    }
                }
                
                return false;
            } catch (error) {
                console.error('Error al verificar reclamo en rutas:', error);
                return false;
            }
        },

        /**
         * Actualiza el mapa de vista previa (redibuja marcadores y ruta)
         */
        async actualizarMapaVistaPrevia() {
            if (this.proveedorMapaVistaPrevia === 'mapbox') {
                // Actualizar mapa Mapbox
                if (!this.mapaMapbox) return;
                await this.mostrarVistaPreviaEnMapaMapbox();
            } else {
                // Actualizar mapa Google
                if (!this.mapa) return;
                
                // Limpiar completamente la vista previa
                this.limpiarVistaPreviaCompleto();
                
                // Volver a mostrar la ruta actualizada
                await this.mostrarVistaPreviaEnMapa();
            }
        },

        /**
         * Inicializa el mapa de Google Maps (con fallback a Mapbox)
         */
        async inicializarMapa() {
            try {
                // Coordenadas de San Francisco, Córdoba
                const lat = -31.427;
                const lng = -62.082;

                // Crear el mapa
                this.mapa = new google.maps.Map(document.getElementById('mapaCrearRuta'), {
                    center: { lat: lat, lng: lng },
                    zoom: 13,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    styles: [
                        {
                            featureType: "poi",
                            elementType: "labels",
                            stylers: [{ visibility: "off" }]
                        },
                        {
                            featureType: "poi.business",
                            stylers: [{ visibility: "off" }]
                        }
                    ]
                });

                // Inicializar geocoder
                this.geocoder = new google.maps.Geocoder();

                // NO agregar marcadores aquí - se agregarán cuando se genere la vista previa

            } catch (error) {
                console.error('Error al inicializar mapa Google Maps:', error);
                console.log('Intentando fallback a Mapbox...');
                
                // FALLBACK AUTOMÁTICO: Cambiar a Mapbox si Google Maps falla
                this.proveedorMapaVistaPrevia = 'mapbox';
                await this.$nextTick();
                try {
                    await this.inicializarMapaMapbox();
                    this.mostrarMensaje('Google Maps no disponible. Usando Mapbox como alternativa.', 'warning');
                } catch (mapboxError) {
                    console.error('Error al inicializar Mapbox:', mapboxError);
                    this.mostrarMensaje('Error: No se pudo inicializar ningún proveedor de mapas', 'error');
                }
            }
        },

        /**
         * Agrega marcadores de reclamos al mapa
         */
        async agregarMarcadoresReclamos() {
            // Limpiar marcadores existentes
            this.marcadores.forEach(marker => marker.setMap(null));
            this.marcadores = [];

            for (const reclamo of this.reclamos) {
                const coordenadas = await this.obtenerCoordenadasReclamo(reclamo);
                
                if (coordenadas) {
                    // Obtener color según el estado
                    const color = this.getColorEstado(reclamo.municipalidad_estado);
                    
                    // Crear marcador puntiagudo (forma de pin)
                    const marker = new google.maps.Marker({
                        position: { lat: coordenadas.lat, lng: coordenadas.lng },
                        map: this.mapa,
                        title: `Reclamo #${reclamo.municipalidad_id}`,
                        icon: {
                            url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                                <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" 
                                          fill="${color}" stroke="#FFFFFF" stroke-width="1"/>
                                </svg>
                            `)}`,
                            scaledSize: new google.maps.Size(24, 24),
                            anchor: new google.maps.Point(12, 24)
                        }
                    });

                    // Crear info window
                    const infoWindow = new google.maps.InfoWindow({
                        content: this.crearContenidoInfoWindow(reclamo)
                    });

                    // Agregar evento de clic
                    marker.addListener('click', () => {
                        // Cerrar cualquier info window abierto
                        if (this.infoWindowAbiertoVistaPrevia) {
                            this.infoWindowAbiertoVistaPrevia.close();
                        }
                        
                        // Abrir el nuevo info window
                        infoWindow.open(this.mapa, marker);
                        this.infoWindowAbiertoVistaPrevia = infoWindow;
                    });

                    // Guardar referencia al reclamo
                    marker._reclamo = reclamo;
                    marker._infoWindow = infoWindow;
                    this.marcadores.push(marker);
                }
            }
        },

        /**
         * Obtiene coordenadas para un reclamo (optimizado con cache)
         */
        async obtenerCoordenadasReclamo(reclamo) {
            try {
                // Verificar si ya está en cache
                if (this.cacheCoordenadasReclamos[reclamo.id]) {
                    return this.cacheCoordenadasReclamos[reclamo.id];
                }
                
                // Buscar dirección personalizada en datos pre-cargados
                if (reclamo.municipalidad_domicilio && reclamo.municipalidad_numeroDomicilio) {
                    const direccionPersonalizada = this.direccionesPersonalizadas.find(dir => 
                        dir.domicilio.toUpperCase().trim() === reclamo.municipalidad_domicilio.toUpperCase().trim() &&
                        dir.numero_domicilio.toString().trim() === reclamo.municipalidad_numeroDomicilio.toString().trim()
                    );
                    
                    if (direccionPersonalizada && direccionPersonalizada.latitud && direccionPersonalizada.longitud) {
                        const coordenadas = {
                            lat: parseFloat(direccionPersonalizada.latitud),
                            lng: parseFloat(direccionPersonalizada.longitud),
                            esPersonalizada: true
                        };
                        // Guardar en cache
                        this.cacheCoordenadasReclamos[reclamo.id] = coordenadas;
                        return coordenadas;
                    }
                }

                // Si no hay dirección personalizada, usar geocodificación
                const coordenadas = await this.geocodificarDireccion(reclamo);
                
                // Guardar en cache si se obtuvo resultado
                if (coordenadas) {
                    this.cacheCoordenadasReclamos[reclamo.id] = coordenadas;
                }
                
                return coordenadas;

            } catch (error) {
                console.error('Error al obtener coordenadas:', error);
                return null;
            }
        },

        /**
         * Geocodifica una dirección usando Google Maps (con fallback a Mapbox)
         */
        async geocodificarDireccion(reclamo) {
            let direccion = '';
            if (reclamo.municipalidad_domicilio) {
                direccion += reclamo.municipalidad_domicilio;
            }
            if (reclamo.municipalidad_numeroDomicilio) {
                direccion += ' ' + reclamo.municipalidad_numeroDomicilio;
            }
            direccion += ', San Francisco, Córdoba, Argentina';

            // Intentar primero con Google Maps
            try {
                const resultadoGoogle = await new Promise((resolve, reject) => {
                    if (!this.geocoder) {
                        reject(new Error('Geocoder no disponible'));
                        return;
                    }
                    
                    this.geocoder.geocode({ address: direccion }, (results, status) => {
                        if (status === 'OK' && results[0]) {
                            const location = results[0].geometry.location;
                            resolve({
                                lat: location.lat(),
                                lng: location.lng(),
                                esPersonalizada: false,
                                fuente: 'google'
                            });
                        } else {
                            reject(new Error('Google Geocoding falló: ' + status));
                        }
                    });
                });
                
                return resultadoGoogle;
                
            } catch (errorGoogle) {
                console.warn('Google Geocoding falló, intentando con Mapbox...', errorGoogle);
                
                // FALLBACK: Intentar con Mapbox Geocoding API
                try {
                    const direccionCompleta = encodeURIComponent(direccion);
                    const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${direccionCompleta}.json?access_token=${this.mapboxToken}&country=AR&limit=1`;
                    
                    const response = await fetch(url);
                    const data = await response.json();
                    
                    if (data.features && data.features.length > 0) {
                        const [lng, lat] = data.features[0].center;
                        console.log(`Geocodificado con Mapbox: ${direccion}`);
                        return {
                            lat: lat,
                            lng: lng,
                            esPersonalizada: false,
                            fuente: 'mapbox'
                        };
                    } else {
                        console.error('Mapbox tampoco encontró resultados para:', direccion);
                        return null;
                    }
                } catch (errorMapbox) {
                    console.error('Error en geocodificación con Mapbox:', errorMapbox);
                    return null;
                }
            }
        },

        /**
         * Selecciona un reclamo en el mapa
         */
        seleccionarReclamo(reclamo, marker) {
            if (this.modoSeleccionPrimerReclamo) {
                this.primerReclamoSeleccionado = reclamo;
                this.modoSeleccionPrimerReclamo = false;
                
                // Cambiar color del marcador
                marker.setIcon({
                    url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                        <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" 
                                  fill="#28a745" stroke="#FFFFFF" stroke-width="1"/>
                        </svg>
                    `)}`,
                    scaledSize: new google.maps.Size(24, 24),
                    anchor: new google.maps.Point(12, 24)
                });

                this.mostrarMensaje(`Primer reclamo seleccionado: ${reclamo.municipalidad_id}`, 'success');
                return;
            }

            if (this.modoSeleccionManual) {
                // Verificar si ya está seleccionado
                const yaSeleccionado = this.reclamosSeleccionados.find(r => r.id === reclamo.id);
                
                if (yaSeleccionado) {
                    // Deseleccionar
                    this.reclamosSeleccionados = this.reclamosSeleccionados.filter(r => r.id !== reclamo.id);
                    
                    // Cambiar color del marcador
                    marker.setIcon({
                        url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                            <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" 
                                      fill="#808080" stroke="#FFFFFF" stroke-width="1"/>
                            </svg>
                        `)}`,
                        scaledSize: new google.maps.Size(24, 24),
                        anchor: new google.maps.Point(12, 24)
                    });
                } else {
                    // Verificar límite
                    if (this.reclamosSeleccionados.length >= this.nuevaRuta.cantidadReclamos) {
                        this.mostrarMensaje(`Solo puede seleccionar ${this.nuevaRuta.cantidadReclamos} reclamos`, 'warning');
                        return;
                    }

                    // Seleccionar
                    this.reclamosSeleccionados.push(reclamo);
                    
                    // Cambiar color del marcador
                    marker.setIcon({
                        url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                            <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" 
                                      fill="#007bff" stroke="#FFFFFF" stroke-width="1"/>
                            </svg>
                        `)}`,
                        scaledSize: new google.maps.Size(24, 24),
                        anchor: new google.maps.Point(12, 24)
                    });
                }
            }
        },

        /**
         * Actualiza el número de reclamos disponibles
         */
        actualizarDisponibles() {
            // Contar solo reclamos no completados
            this.reclamosDisponibles = this.reclamos.filter(r => r.municipalidad_estado !== 'Completado').length;
        },

        /**
         * Activa/desactiva la selección manual de reclamos
         */
        toggleSeleccionManual() {
            this.modoSeleccionManual = this.nuevaRuta.seleccionManual;
            
            if (!this.nuevaRuta.seleccionManual) {
                // Si cambia a automática, limpiar selecciones manuales
                this.reclamosSeleccionados = [];
                // Restaurar colores de marcadores
                if (this.marcadores.length > 0) {
                    this.restaurarColoresMarcadores();
                }
            } else {
                // Si cambia a manual, limpiar vista previa
                this.limpiarVistaPrevia();
            }
        },

        /**
         * Activa/desactiva la selección manual del primer reclamo
         */
        togglePrimerReclamoManual() {
            this.modoSeleccionPrimerReclamo = this.nuevaRuta.primerReclamoManual;
            
            if (!this.nuevaRuta.primerReclamoManual) {
                this.primerReclamoSeleccionado = null;
            }
        },

        /**
         * Muestra la vista previa de la ruta usando el algoritmo del backend
         */
        async mostrarVistaPrevia() {
            if (!this.puedeVerVistaPrevia) {
                this.mostrarMensaje('Complete la configuración para ver la vista previa', 'warning');
                return;
            }

            try {
                // Mostrar mensaje de carga
                this.mostrarMensaje('Generando vista previa...', 'info');
                
                // Limpiar vista previa anterior
                this.limpiarVistaPrevia();
                
                // Preparar datos para enviar al backend
                const datosVistaPrevia = {
                    cantidadReclamos: this.nuevaRuta.cantidadReclamos,
                    reclamosManuales: [],
                    primerReclamoManual: null
                };

                // Obtener vista previa del backend usando el mismo algoritmo
                const response = await axios.post(BASE_URL + 'api/rutas/vista-previa', datosVistaPrevia);
                const datosRespuesta = response.data;

                if (!datosRespuesta.rutaOptimizada || datosRespuesta.rutaOptimizada.length === 0) {
                    this.vistaPrevia.activa = false;
                    this.mostrarMensaje('No hay reclamos disponibles para la vista previa', 'warning');
                    return;
                }

                // Asignar datos del backend
                this.vistaPrevia.rutaOptimizada = datosRespuesta.rutaOptimizada;
                this.vistaPrevia.tiempoEstimado = this.convertirTiempoAMinutos(datosRespuesta.tiempoEstimado);
                this.vistaPrevia.distanciaTotal = datosRespuesta.distanciaTotal;
                this.vistaPrevia.activa = true;
                
                // Inicializar mapa cuando se activa la vista previa
                this.$nextTick(() => {
                    setTimeout(async () => {
                        await this.inicializarMapa();
                        // Mostrar marcadores y ruta en el mapa
                        await this.mostrarVistaPreviaEnMapa();
                        this.mostrarMensaje('Vista previa generada correctamente', 'success');
                        
                        // Inicializar popovers de Bootstrap
                        this.$nextTick(() => {
                            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                            popoverTriggerList.map(function (popoverTriggerEl) {
                                return new bootstrap.Popover(popoverTriggerEl);
                            });
                        });
                    }, 300);
                });
                
            } catch (error) {
                console.error('Error al mostrar vista previa:', error);
                this.vistaPrevia.activa = false;
                this.mostrarMensaje('Error al generar la vista previa: ' + (error.response?.data?.message || error.message), 'error');
            }
        },

        /**
         * Convierte tiempo en formato HH:MM:SS a minutos
         */
        convertirTiempoAMinutos(tiempoString) {
            if (!tiempoString) return 0;
            
            const partes = tiempoString.split(':');
            const horas = parseInt(partes[0]) || 0;
            const minutos = parseInt(partes[1]) || 0;
            
            return (horas * 60) + minutos;
        },


        /**
         * Muestra la vista previa en el mapa usando Google Directions Service
         */
        async mostrarVistaPreviaEnMapa() {
            // Limpiar completamente la vista previa anterior primero
            this.limpiarVistaPreviaCompleto();
            
            // PASO 1: Mostrar todas las rutas existentes en gris (discretas)
            await this.mostrarRutasActivasEnVistaPrevia();
            
            // Primero agregar todos los reclamos que NO están en la ruta (puntiagudos)
            const idsRutaPrevia = this.vistaPrevia.rutaOptimizada.map(r => r.id);
            const reclamosNoEnRuta = this.reclamos.filter(r => !idsRutaPrevia.includes(r.id));
            
            // OPTIMIZACIÓN: Paralelizar obtención de coordenadas
            const promesasCoordenadas = reclamosNoEnRuta.map(reclamo => 
                this.obtenerCoordenadasReclamo(reclamo).then(coords => ({ reclamo, coords }))
            );
            
            const resultados = await Promise.all(promesasCoordenadas);
            
            // Crear marcadores con los resultados
            for (const { reclamo, coords: coordenadas } of resultados) {
                    if (coordenadas) {
                        const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                        const colorPrioridad = this.getColorPrioridad(reclamo.prioridad || 'Baja');
                        const tienePrioridadAlta = colorPrioridad !== null;
                        
                        const marker = new google.maps.Marker({
                            position: { lat: coordenadas.lat, lng: coordenadas.lng },
                            map: this.mapa,
                            title: `Reclamo #${reclamo.municipalidad_id}${tienePrioridadAlta ? ' - ⚠️ PRIORIDAD ALTA' : ''}`,
                            icon: {
                                url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                                    <svg width="28" height="32" viewBox="0 0 28 32" xmlns="http://www.w3.org/2000/svg">
                                        ${tienePrioridadAlta ? `
                                        <circle cx="14" cy="14" r="0" fill="${colorPrioridad}" opacity="0.3">
                                            <animate attributeName="r" values="0;12;0" dur="1.5s" repeatCount="indefinite"/>
                                        </circle>
                                        ` : ''}
                                        <path d="M14 2C10.13 2 7 5.13 7 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" 
                                              fill="${colorEstado}" stroke="#FFFFFF" stroke-width="1"/>
                                    </svg>
                                `)}`,
                                scaledSize: new google.maps.Size(28, 32),
                                anchor: new google.maps.Point(14, 28)
                            }
                        });

                        const infoWindow = new google.maps.InfoWindow({
                            content: this.crearContenidoInfoWindow(reclamo)
                        });

                        marker.addListener('click', () => {
                        // Si está en modo edición, agregar reclamo a la ruta
                        if (this.modoEdicion) {
                            this.agregarReclamoARuta(reclamo);
                        } else {
                            // Si no está en modo edición, solo mostrar info window
                            if (this.infoWindowAbiertoVistaPrevia) {
                                this.infoWindowAbiertoVistaPrevia.close();
                            }
                            infoWindow.open(this.mapa, marker);
                            this.infoWindowAbiertoVistaPrevia = infoWindow;
                        }
                        });

                        marker._reclamo = reclamo;
                        marker._infoWindow = infoWindow;
                        this.vistaPrevia.marcadoresOtros.push(marker);
                }
            }
            
            // Luego agregar marcadores numerados circulares para la ruta
            // OPTIMIZACIÓN: Paralelizar obtención de coordenadas
            const promesasRuta = this.vistaPrevia.rutaOptimizada.map((reclamo, i) => 
                this.obtenerCoordenadasReclamo(reclamo).then(coords => ({ reclamo, coords, index: i }))
            );
            
            const resultadosRuta = await Promise.all(promesasRuta);
            
            for (const { reclamo, coords: coordenadas, index: i } of resultadosRuta) {
                if (coordenadas) {
                    const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                    const colorPrioridad = this.getColorPrioridad(reclamo.prioridad || 'Baja');
                    const tienePrioridadAlta = colorPrioridad !== null;
                    
                    const marker = new google.maps.Marker({
                        position: { lat: coordenadas.lat, lng: coordenadas.lng },
                        map: this.mapa,
                        title: `Posición ${i + 1}: Reclamo #${reclamo.municipalidad_id}${tienePrioridadAlta ? ' - ⚠️ PRIORIDAD ALTA' : ''}`,
                        icon: this.crearIconoNumerado(i + 1, colorEstado, colorPrioridad),
                        zIndex: 1000
                    });

                    // Crear info window con los detalles del reclamo
                    const reclamoConPosicion = { ...reclamo, posicion: i + 1 };
                    const infoWindow = new google.maps.InfoWindow({
                        content: this.crearContenidoInfoWindow(reclamoConPosicion)
                    });

                    // Agregar evento de clic para mostrar info window
                    marker.addListener('click', () => {
                        // Cerrar cualquier info window abierto
                        if (this.infoWindowAbiertoVistaPrevia) {
                            this.infoWindowAbiertoVistaPrevia.close();
                        }
                        
                        // Abrir el nuevo info window
                        infoWindow.open(this.mapa, marker);
                        this.infoWindowAbiertoVistaPrevia = infoWindow;
                    });
                    
                    marker._reclamo = reclamoConPosicion;
                    marker._infoWindow = infoWindow;
                    this.vistaPrevia.marcadoresRuta.push(marker);
                }
            }
            
            // Usar Google Directions Service para mostrar la ruta real por las calles
            // SOLO para los marcadores de la ruta (no los otros)
            if (this.vistaPrevia.marcadoresRuta.length > 1) {
                await this.trazarRutaConDirections();
            }
        },

        /**
         * Muestra todas las rutas existentes (asignadas y no asignadas) en gris (discretas) en la vista previa
         */
        async mostrarRutasActivasEnVistaPrevia() {
            try {
                // Obtener TODAS las rutas (asignadas y no asignadas)
                // Las rutas no asignadas son las que aún no se asignaron a una cuadrilla, pero están reservadas
                const rutasActivas = this.rutas;
                
                for (const ruta of rutasActivas) {
                    try {
                        // Obtener reclamos de esta ruta
                        const response = await axios.get(BASE_URL + 'api/rutas/' + ruta.id + '/reclamos');
                        const reclamosRuta = response.data;
                        
                        // OPTIMIZACIÓN: Paralelizar obtención de coordenadas para marcadores
                        const promesasMarcadores = reclamosRuta.map(reclamo => 
                            this.obtenerCoordenadasReclamo(reclamo).then(coords => ({ reclamo, coords }))
                        );
                        
                        const resultadosMarcadores = await Promise.all(promesasMarcadores);
                        
                        // Crear marcadores discretos (pequeños, grises, con opacidad)
                        for (const { reclamo, coords: coordenadas } of resultadosMarcadores) {
                            if (coordenadas) {
                                // Marcador discreto en gris (un poco más visible)
                                const marker = new google.maps.Marker({
                                    position: { lat: coordenadas.lat, lng: coordenadas.lng },
                                    map: this.mapa,
                                    title: `Ruta #${ruta.id} - Reclamo #${reclamo.municipalidad_id}`,
                                    icon: {
                                        url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                                            <svg width="22" height="22" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="11" cy="11" r="9" fill="#909090" stroke="#FFFFFF" stroke-width="1.5" opacity="0.75"/>
                                                <text x="11" y="14" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="10" font-weight="bold">${reclamo.posicion}</text>
                                            </svg>
                                        `)}`,
                                        scaledSize: new google.maps.Size(22, 22),
                                        anchor: new google.maps.Point(11, 11)
                                    },
                                    zIndex: 100, // Bajo z-index para que esté detrás de todo
                                    opacity: 0.75
                                });
                                
                                // Info window discreto
                                const estadoRuta = ruta.asignada == 1 ? 'Asignada' : 'No Asignada';
                                const infoWindow = new google.maps.InfoWindow({
                                    content: `
                                        <div style="min-width: 200px; opacity: 0.9;">
                                            <h6 style="margin-bottom: 8px; color: #666;">
                                                <strong>${ruta.nombre || 'Ruta #' + ruta.id}</strong>
                                                <span class="badge bg-${ruta.asignada == 1 ? 'success' : 'secondary'} ms-1" style="font-size: 0.7rem;">${estadoRuta}</span>
                                            </h6>
                                            <p style="margin-bottom: 4px; font-size: 0.85rem;"><strong>Reclamo:</strong> #${reclamo.municipalidad_id}</p>
                                            <p style="margin-bottom: 4px; font-size: 0.85rem;"><strong>Posición:</strong> ${reclamo.posicion}</p>
                                            <p style="margin-bottom: 0; font-size: 0.75rem; color: #999;">Esta ruta ya está creada</p>
                                        </div>
                                    `
                                });
                                
                                marker.addListener('click', () => {
                                    if (this.infoWindowAbiertoVistaPrevia) {
                                        this.infoWindowAbiertoVistaPrevia.close();
                                    }
                                    infoWindow.open(this.mapa, marker);
                                    this.infoWindowAbiertoVistaPrevia = infoWindow;
                                });
                                
                                this.vistaPrevia.marcadoresRutasActivas.push(marker);
                            }
                        }
                        
                        // Trazar ruta por las calles usando Google Directions Service
                        // OPTIMIZACIÓN: Obtener coordenadas en paralelo primero
                        if (reclamosRuta.length > 1) {
                            // Ya tenemos las coordenadas de resultadosMarcadores, reutilizarlas
                            const coordenadas = resultadosMarcadores
                                .filter(r => r.coords)
                                .map(r => ({ lat: r.coords.lat, lng: r.coords.lng }));
                            
                            if (coordenadas.length > 1) {
                                // Usar el color de la ruta o gris por defecto
                                const colorRuta = ruta.color || '#909090';
                                
                                // Usar DirectionsService para trazar por las calles
                                const directionsService = new google.maps.DirectionsService();
                                const directionsRenderer = new google.maps.DirectionsRenderer({
                                    suppressMarkers: true, // No mostrar marcadores por defecto
                                    preserveViewport: true, // No ajustar el viewport automáticamente
                                    polylineOptions: {
                                        strokeColor: colorRuta,
                                        strokeOpacity: 0.6,
                                        strokeWeight: 3,
                                        zIndex: 50
                                    }
                                });
                                
                                directionsRenderer.setMap(this.mapa);
                                
                                // Construir la solicitud
                                const origin = coordenadas[0];
                                const destination = coordenadas[coordenadas.length - 1];
                                const waypoints = coordenadas.slice(1, -1).map(coord => ({
                                    location: coord,
                                    stopover: true
                                }));
                                
                                const request = {
                                    origin: origin,
                                    destination: destination,
                                    waypoints: waypoints,
                                    travelMode: google.maps.TravelMode.DRIVING,
                                    unitSystem: google.maps.UnitSystem.METRIC,
                                    optimizeWaypoints: false
                                };
                                
                                try {
                                    const result = await new Promise((resolve, reject) => {
                                        directionsService.route(request, (result, status) => {
                                            if (status === 'OK') {
                                                resolve(result);
                                            } else {
                                                reject(new Error('Error al obtener direcciones: ' + status));
                                            }
                                        });
                                    });
                                    
                                    directionsRenderer.setDirections(result);
                                    this.vistaPrevia.marcadoresRutasActivas.push(directionsRenderer);
                                    
                                } catch (error) {
                                    console.warn('Error al trazar ruta activa ' + ruta.id + ' por calles, usando línea recta:', error);
                                    // Fallback: usar línea recta si falla
                                const polyline = new google.maps.Polyline({
                                    path: coordenadas,
                                    geodesic: true,
                                        strokeColor: colorRuta,
                                    strokeOpacity: 0.6,
                                    strokeWeight: 3,
                                        zIndex: 50
                                });
                                polyline.setMap(this.mapa);
                                    this.vistaPrevia.marcadoresRutasActivas.push(polyline);
                                }
                            }
                        }
                        
                    } catch (error) {
                        console.warn('Error al cargar ruta activa ' + ruta.id + ':', error);
                    }
                }
                
            } catch (error) {
                console.error('Error al mostrar rutas activas:', error);
            }
        },

        /**
         * Limpia completamente todos los elementos de la vista previa
         */
        limpiarVistaPreviaCompleto() {
            console.log('Limpiando vista previa completa');
            
            // Cerrar info window si está abierto
            if (this.infoWindowAbiertoVistaPrevia) {
                this.infoWindowAbiertoVistaPrevia.close();
                this.infoWindowAbiertoVistaPrevia = null;
            }
            
            // Limpiar marcadores de la ruta
            if (this.vistaPrevia.marcadoresRuta && this.vistaPrevia.marcadoresRuta.length > 0) {
                this.vistaPrevia.marcadoresRuta.forEach(marker => {
                    if (marker) {
                        marker.setMap(null);
                        marker.setVisible(false);
                    }
                });
                this.vistaPrevia.marcadoresRuta = [];
            }
            
            // Limpiar marcadores de otros reclamos
            if (this.vistaPrevia.marcadoresOtros && this.vistaPrevia.marcadoresOtros.length > 0) {
                this.vistaPrevia.marcadoresOtros.forEach(marker => {
                    if (marker) {
                        marker.setMap(null);
                        marker.setVisible(false);
                    }
                });
                this.vistaPrevia.marcadoresOtros = [];
            }
            
            // Limpiar marcadores de rutas activas (grises)
            if (this.vistaPrevia.marcadoresRutasActivas && this.vistaPrevia.marcadoresRutasActivas.length > 0) {
                this.vistaPrevia.marcadoresRutasActivas.forEach(item => {
                    if (item) {
                        item.setMap(null);
                        if (item.setVisible) {
                            item.setVisible(false);
                        }
                    }
                });
                this.vistaPrevia.marcadoresRutasActivas = [];
            }
            
            // Limpiar polyline
            if (this.vistaPrevia.polylineRuta) {
                this.vistaPrevia.polylineRuta.setMap(null);
                this.vistaPrevia.polylineRuta = null;
            }
            
            // Limpiar directions renderer
            if (this.vistaPrevia.directionsRenderer) {
                this.vistaPrevia.directionsRenderer.setMap(null);
                this.vistaPrevia.directionsRenderer.setDirections({ routes: [] });
                this.vistaPrevia.directionsRenderer = null;
            }
            
            // Forzar actualización del mapa
            if (this.mapa) {
                google.maps.event.trigger(this.mapa, 'resize');
            }
        },

        /**
         * Traza la ruta usando Google Directions Service (con fallback a Mapbox)
         */
        async trazarRutaConDirections() {
            try {
                const directionsService = new google.maps.DirectionsService();
                
                // Crear directions renderer con el color seleccionado
                this.vistaPrevia.directionsRenderer = new google.maps.DirectionsRenderer({
                    suppressMarkers: true, // No mostrar marcadores por defecto (ya tenemos los nuestros)
                    polylineOptions: {
                        strokeColor: this.nuevaRuta.color,
                        strokeOpacity: 0.8,
                        strokeWeight: 4
                    }
                });

                // Configurar el renderer en el mapa
                this.vistaPrevia.directionsRenderer.setMap(this.mapa);

                const coordenadas = this.vistaPrevia.marcadoresRuta.map(marker => marker.getPosition());

                // Si hay más de 2 puntos, usar waypoints
                if (coordenadas.length === 2) {
                    // Ruta simple entre 2 puntos
                    await this.trazarRutaSimpleVistaPrevia(directionsService, this.vistaPrevia.directionsRenderer, coordenadas[0], coordenadas[1]);
                } else {
                    // Ruta compleja con múltiples paradas
                    await this.trazarRutaComplejaVistaPrevia(directionsService, this.vistaPrevia.directionsRenderer, coordenadas);
                }

                console.log('Vista previa de ruta trazada correctamente por las calles con Google Maps');
            } catch (error) {
                console.error('Error al trazar ruta con Google Maps:', error);
                console.log('Intentando fallback a Mapbox...');
                
                // FALLBACK AUTOMÁTICO: Cambiar a Mapbox si Google Directions falla
                try {
                    this.proveedorMapaVistaPrevia = 'mapbox';
                    await this.$nextTick();
                    await this.inicializarMapaMapbox();
                    await this.mostrarVistaPreviaEnMapaMapbox();
                    this.mostrarMensaje('Google Maps no responde. Usando Mapbox como alternativa.', 'warning');
                } catch (mapboxError) {
                    console.error('Error con Mapbox:', mapboxError);
                    // Último fallback: línea recta
                    this.trazarRutaRectaVistaPrevia();
                    this.mostrarMensaje('Ambos servicios de mapas fallaron. Mostrando ruta simplificada.', 'warning');
                }
            }
        },

        /**
         * Traza una ruta simple entre dos puntos para vista previa
         */
        async trazarRutaSimpleVistaPrevia(directionsService, directionsRenderer, origin, destination) {
            const request = {
                origin: origin,
                destination: destination,
                travelMode: google.maps.TravelMode.DRIVING,
                unitSystem: google.maps.UnitSystem.METRIC,
                avoidHighways: false,
                avoidTolls: false
            };

            return new Promise((resolve, reject) => {
                directionsService.route(request, (result, status) => {
                    if (status === 'OK') {
                        directionsRenderer.setDirections(result);
                        resolve(result);
                    } else {
                        reject(new Error('Error al obtener direcciones: ' + status));
                    }
                });
            });
        },

        /**
         * Traza una ruta compleja con múltiples paradas para vista previa
         */
        async trazarRutaComplejaVistaPrevia(directionsService, directionsRenderer, coordenadas) {
            // Para rutas con múltiples paradas, usamos waypoints
            const origin = coordenadas[0];
            const destination = coordenadas[coordenadas.length - 1];
            const waypoints = coordenadas.slice(1, -1).map(coord => ({
                location: coord,
                stopover: true
            }));

            const request = {
                origin: origin,
                destination: destination,
                waypoints: waypoints,
                travelMode: google.maps.TravelMode.DRIVING,
                unitSystem: google.maps.UnitSystem.METRIC,
                avoidHighways: false,
                avoidTolls: false,
                optimizeWaypoints: false // Mantener el orden específico de la ruta
            };

            return new Promise((resolve, reject) => {
                directionsService.route(request, (result, status) => {
                    if (status === 'OK') {
                        directionsRenderer.setDirections(result);
                        resolve(result);
                    } else {
                        reject(new Error('Error al obtener direcciones: ' + status));
                    }
                });
            });
        },

        /**
         * Fallback: traza línea recta si falla el servicio de direcciones
         */
        trazarRutaRectaVistaPrevia() {
            console.log('Usando fallback: línea recta en vista previa');
            
            const coordenadas = this.vistaPrevia.marcadoresRuta.map(marker => marker.getPosition());

            const polyline = new google.maps.Polyline({
                path: coordenadas,
                geodesic: true,
                strokeColor: this.nuevaRuta.color,
                strokeOpacity: 0.8,
                strokeWeight: 4
            });

            polyline.setMap(this.mapa);
            this.vistaPrevia.polylineRuta = polyline;
        },

        /**
         * Crea un icono numerado para los marcadores de la ruta
         * Si tiene prioridad Alta, muestra animación de pulso
         */
        crearIconoNumerado(numero, colorEstado, colorPrioridad) {
            const tienePrioridadAlta = colorPrioridad !== null;
            
            if (tienePrioridadAlta) {
                // Con animación de pulso para prioridad Alta
                return {
                    url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                        <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="20" cy="20" r="0" fill="${colorPrioridad}" opacity="0.3">
                                <animate attributeName="r" values="0;18;0" dur="1.5s" repeatCount="indefinite"/>
                            </circle>
                            <circle cx="20" cy="20" r="15" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                            <text x="20" y="25" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="14" font-weight="bold">${numero}</text>
                        </svg>
                    `)}`,
                    scaledSize: new google.maps.Size(40, 40),
                    anchor: new google.maps.Point(20, 20)
                };
            } else {
                // Sin animación para prioridad Baja
                return {
                    url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                        <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="16" cy="16" r="14" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                            <text x="16" y="20" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="12" font-weight="bold">${numero}</text>
                        </svg>
                    `)}`,
                    scaledSize: new google.maps.Size(32, 32),
                    anchor: new google.maps.Point(16, 16)
                };
            }
        },

        /**
         * Limpia la vista previa
         */
        limpiarVistaPrevia() {
            this.limpiarVistaPreviaCompleto();
            
            // Limpiar datos
            this.vistaPrevia.rutaOptimizada = [];
            this.vistaPrevia.tiempoEstimado = 0;
            this.vistaPrevia.distanciaTotal = 0;
            this.vistaPrevia.activa = false; // Resetear el estado activa
            
            // Limpiar mapa Mapbox si existe
            if (this.mapaMapbox) {
                this.mapaMapbox.remove();
                this.mapaMapbox = null;
            }
            
            // Resetear proveedor
            this.proveedorMapaVistaPrevia = 'google';
        },

        /**
         * Restaura los colores originales de los marcadores
         */
        restaurarColoresMarcadores() {
            this.marcadores.forEach(marker => {
                marker.setIcon({
                    url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                        <svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" 
                                  fill="#808080" stroke="#FFFFFF" stroke-width="1"/>
                        </svg>
                    `)}`,
                    scaledSize: new google.maps.Size(24, 24),
                    anchor: new google.maps.Point(12, 24)
                });
            });
        },

        /**
         * Crea la hoja de ruta automática o editada
         */
        async crearRutaAutomatica() {
            if (!this.puedeGenerarRuta) {
                this.mostrarMensaje('Debe ver la vista previa antes de crear la ruta', 'warning');
                return;
            }

            // Validar que el nombre no esté vacío
            if (!this.nuevaRuta.nombre || this.nuevaRuta.nombre.trim() === '') {
                this.mostrarMensaje('Debe ingresar un nombre para la hoja de ruta', 'warning');
                return;
            }
            
            // Validar que haya al menos un reclamo en la ruta
            if (this.vistaPrevia.rutaOptimizada.length === 0) {
                this.mostrarMensaje('La ruta debe tener al menos un reclamo', 'warning');
                return;
            }

            try {
                let datosRuta;
                
                if (this.modoEdicion) {
                    // Si está en modo edición, enviar la ruta editada manualmente
                    datosRuta = {
                        nombre: this.nuevaRuta.nombre.trim(),
                        color: this.nuevaRuta.color,
                        cantidadReclamos: this.vistaPrevia.rutaOptimizada.length,
                        reclamosManuales: this.vistaPrevia.rutaOptimizada.map(r => r.id),
                        primerReclamoManual: null,
                        modoManual: true // Flag para indicar que es una ruta editada manualmente
                    };
                } else {
                    // Ruta automática normal
                    datosRuta = {
                        nombre: this.nuevaRuta.nombre.trim(),
                        color: this.nuevaRuta.color,
                        cantidadReclamos: parseInt(this.nuevaRuta.cantidadReclamos),
                        reclamosManuales: [],
                        primerReclamoManual: null,
                        modoManual: false
                    };
                }

                this.mostrarMensaje(this.modoEdicion ? 'Creando hoja de ruta editada...' : 'Creando hoja de ruta automática...', 'info');

                const response = await axios.post(BASE_URL + 'api/rutas/generar', datosRuta);
                
                this.mostrarMensaje('Hoja de ruta creada exitosamente', 'success');
                
                // Resetear modo edición
                this.modoEdicion = false;
                this.rutaOriginal = [];
                
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalCrearRuta'));
                modal.hide();
                
                // Resetear modal
                this.resetearModal();
                
                // Actualizar tabla
                await this.obtenerRutas();

            } catch (error) {
                console.error('Error al crear ruta:', error);
                this.mostrarMensaje('Error al crear la hoja de ruta: ' + (error.response?.data?.message || error.message), 'error');
            }
        },

        /**
         * Ver una ruta en el modal
         */
        async verRuta(id) {
            try {
                // Obtener datos de la ruta
                const responseRuta = await axios.get(BASE_URL + 'api/rutas/' + id);
                this.rutaVisualizando = responseRuta.data;
                
                // Obtener reclamos de la ruta
                const responseReclamos = await axios.get(BASE_URL + 'api/rutas/' + id + '/reclamos');
                this.reclamosRutaVisualizando = responseReclamos.data;
                
                // Abrir modal
                const modal = new bootstrap.Modal(document.getElementById('modalVerRuta'));
                modal.show();
                
                // Inicializar mapa de visualización
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.inicializarMapaVisualizacion();
                        
                        // Inicializar popovers de Bootstrap
                        this.$nextTick(() => {
                            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                            popoverTriggerList.map(function (popoverTriggerEl) {
                                return new bootstrap.Popover(popoverTriggerEl);
                            });
                        });
                    }, 300);
                });
                
            } catch (error) {
                console.error('Error al cargar ruta:', error);
                this.mostrarMensaje('Error al cargar la ruta', 'error');
            }
        },

        /**
         * Inicializa el mapa para visualizar la ruta (con fallback a Mapbox)
         */
        async inicializarMapaVisualizacion() {
            try {
                const lat = -31.427;
                const lng = -62.082;

                this.mapaVisualizacion = new google.maps.Map(document.getElementById('mapaVerRuta'), {
                    center: { lat: lat, lng: lng },
                    zoom: 13,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    styles: [
                        {
                            featureType: "poi",
                            elementType: "labels",
                            stylers: [{ visibility: "off" }]
                        }
                    ]
                });

                // Inicializar geocoder
                this.geocoder = new google.maps.Geocoder();
                
                // Agregar marcadores de los reclamos de la ruta (circulares numerados)
                await this.agregarMarcadoresVisualizacion();
                
                // Trazar la ruta
                await this.trazarRutaVisualizacion();

            } catch (error) {
                console.error('Error al inicializar mapa Google Maps:', error);
                console.log('Intentando fallback a Mapbox...');
                
                // FALLBACK AUTOMÁTICO
                this.proveedorMapaVisualizacion = 'mapbox';
                await this.$nextTick();
                try {
                    await this.inicializarMapaVisualizacionMapbox();
                    await this.mostrarRutaEnMapaMapbox();
                    this.mostrarMensaje('Google Maps no disponible. Usando Mapbox como alternativa.', 'warning');
                } catch (mapboxError) {
                    console.error('Error al inicializar Mapbox:', mapboxError);
                    this.mostrarMensaje('Error: No se pudo inicializar el mapa', 'error');
                }
            }
        },

        /**
         * Agrega marcadores numerados de la ruta (optimizado)
         */
        async agregarMarcadoresVisualizacion() {
            // Limpiar marcadores anteriores
            this.marcadoresVisualizacion.forEach(marker => marker.setMap(null));
            this.marcadoresVisualizacion = [];

            // OPTIMIZACIÓN: Paralelizar obtención de coordenadas
            const promesasCoordenadas = this.reclamosRutaVisualizando.map(reclamo => 
                this.obtenerCoordenadasReclamo(reclamo).then(coords => ({ reclamo, coords }))
            );
            
            const resultados = await Promise.all(promesasCoordenadas);
            
            for (const { reclamo, coords: coordenadas } of resultados) {
                if (coordenadas) {
                    const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                    const colorPrioridad = this.getColorPrioridad(reclamo.prioridad || 'Baja');
                    const tienePrioridadAlta = colorPrioridad !== null;
                    
                    const marker = new google.maps.Marker({
                        position: { lat: coordenadas.lat, lng: coordenadas.lng },
                        map: this.mapaVisualizacion,
                        title: `Reclamo #${reclamo.municipalidad_id} - Posición ${reclamo.posicion}${tienePrioridadAlta ? ' - ⚠️ PRIORIDAD ALTA' : ''}`,
                        icon: this.crearIconoNumerado(reclamo.posicion, colorEstado, colorPrioridad),
                        zIndex: 1000
                    });

                    // Crear info window con los detalles del reclamo
                    const infoWindow = new google.maps.InfoWindow({
                        content: this.crearContenidoInfoWindow(reclamo)
                    });

                    // Agregar evento de clic para mostrar info window
                    marker.addListener('click', () => {
                        // Cerrar cualquier info window abierto
                        if (this.infoWindowAbiertoVisualizacion) {
                            this.infoWindowAbiertoVisualizacion.close();
                        }
                        
                        // Abrir el nuevo info window
                        infoWindow.open(this.mapaVisualizacion, marker);
                        this.infoWindowAbiertoVisualizacion = infoWindow;
                    });

                    marker._reclamo = reclamo;
                    marker._infoWindow = infoWindow;
                    this.marcadoresVisualizacion.push(marker);
                }
            }
        },

        /**
         * Crea el contenido del info window para un reclamo
         */
        crearContenidoInfoWindow(reclamo) {
            return `
                <div style="min-width: 250px;">
                    <h6 style="margin-bottom: 8px; color: #06044B;">
                        <strong>Reclamo #${reclamo.municipalidad_id}</strong>
                        
                    </h6>
                    <p style="margin-bottom: 4px;"><strong>Motivo:</strong> ${reclamo.municipalidad_motivo || 'No especificado'}</p>
                    <p style="margin-bottom: 4px;"><strong>Estado:</strong> ${reclamo.municipalidad_estado || 'No especificado'}</p>
                    <p style="margin-bottom: 4px;"><strong>Prioridad:</strong> ${reclamo.prioridad || 'No especificado'}</p>
                    <p style="margin-bottom: 4px;"><strong>Dirección:</strong> ${reclamo.municipalidad_domicilio || 'No especificado'} ${reclamo.municipalidad_numeroDomicilio || ''}</p>
                    <p style="margin-bottom: 4px;"><strong>Fecha:</strong> ${this.formatearFecha(reclamo.municipalidad_fechaInicio)}</p>
                    <p style="margin-bottom: 4px;"><strong>Ciudadano:</strong> ${reclamo.municipalidad_ciudadano || 'No especificado'}</p>
                </div>
            `;
        },

        /**
         * Traza la ruta en el mapa de visualización
         */
        async trazarRutaVisualizacion() {
            if (this.marcadoresVisualizacion.length < 2) {
                return;
            }

            try {
                const directionsService = new google.maps.DirectionsService();
                
                // Usar el color de la ruta o rojo por defecto
                const colorRuta = this.rutaVisualizando.color || '#FF0000';
                
                this.directionsRendererVisualizacion = new google.maps.DirectionsRenderer({
                    suppressMarkers: true,
                    polylineOptions: {
                        strokeColor: colorRuta,
                        strokeOpacity: 1.0,
                        strokeWeight: 4
                    }
                });

                this.directionsRendererVisualizacion.setMap(this.mapaVisualizacion);

                const coordenadas = this.marcadoresVisualizacion.map(marker => marker.getPosition());

                if (coordenadas.length === 2) {
                    await this.trazarRutaSimpleVisualizacion(directionsService, coordenadas[0], coordenadas[1]);
                } else {
                    await this.trazarRutaComplejaVisualizacion(directionsService, coordenadas);
                }

            } catch (error) {
                console.error('Error al trazar ruta:', error);
            }
        },

        /**
         * Traza ruta simple para visualización
         */
        async trazarRutaSimpleVisualizacion(directionsService, origin, destination) {
            const request = {
                origin: origin,
                destination: destination,
                travelMode: google.maps.TravelMode.DRIVING
            };

            return new Promise((resolve, reject) => {
                directionsService.route(request, (result, status) => {
                    if (status === 'OK') {
                        this.directionsRendererVisualizacion.setDirections(result);
                        resolve(result);
                    } else {
                        reject(new Error('Error al obtener direcciones: ' + status));
                    }
                });
            });
        },

        /**
         * Traza ruta compleja para visualización
         */
        async trazarRutaComplejaVisualizacion(directionsService, coordenadas) {
            const origin = coordenadas[0];
            const destination = coordenadas[coordenadas.length - 1];
            const waypoints = coordenadas.slice(1, -1).map(coord => ({
                location: coord,
                stopover: true
            }));

            const request = {
                origin: origin,
                destination: destination,
                waypoints: waypoints,
                travelMode: google.maps.TravelMode.DRIVING,
                optimizeWaypoints: false
            };

            return new Promise((resolve, reject) => {
                directionsService.route(request, (result, status) => {
                    if (status === 'OK') {
                        this.directionsRendererVisualizacion.setDirections(result);
                        resolve(result);
                    } else {
                        reject(new Error('Error al obtener direcciones: ' + status));
                    }
                });
            });
        },

        /**
         * Centra el mapa en un reclamo específico
         */
        centrarEnReclamo(reclamo) {
            const marker = this.marcadoresVisualizacion.find(m => m._reclamo.id === reclamo.id);
            if (marker) {
                this.mapaVisualizacion.setCenter(marker.getPosition());
                this.mapaVisualizacion.setZoom(16);
                
                // Animación de rebote
                marker.setAnimation(google.maps.Animation.BOUNCE);
                setTimeout(() => {
                    marker.setAnimation(null);
                }, 1500);
            }
        },

        /**
         * Obtiene el color según el estado del reclamo
         */
        getColorEstado(estado) {
            const colores = {
                'Recibido': '#808080',
                'Asignado': '#FF0000',
                'En ejecución': '#FFD700',
                'Completado': '#198754',
                'En plan': '#808080',
                'Error de datos': '#808080'
            };
            return colores[estado] || '#808080';
        },

        /**
         * Obtiene el color del borde según la prioridad
         * Solo resalta prioridad Alta con borde rojo, los demás sin borde especial
         */
        getColorPrioridad(prioridad) {
            if (prioridad === 'Alta') {
                return '#DC3545'; // Rojo intenso para prioridad Alta
            }
            return null; // Sin borde especial para Media y Baja
        },

        /**
         * Cierra el modal de visualización
         */
        cerrarVisualizacion() {
            // Cerrar info window si está abierto
            if (this.infoWindowAbiertoVisualizacion) {
                this.infoWindowAbiertoVisualizacion.close();
                this.infoWindowAbiertoVisualizacion = null;
            }
            
            this.rutaVisualizando = {};
            this.reclamosRutaVisualizando = [];
            this.marcadoresVisualizacion.forEach(marker => marker.setMap(null));
            this.marcadoresVisualizacion = [];
            if (this.directionsRendererVisualizacion) {
                this.directionsRendererVisualizacion.setMap(null);
                this.directionsRendererVisualizacion = null;
            }
            this.mapaVisualizacion = null;
            
            // Limpiar mapa Mapbox si existe
            if (this.mapaVisualizacionMapbox) {
                this.mapaVisualizacionMapbox.remove();
                this.mapaVisualizacionMapbox = null;
            }
            
            // Resetear proveedor
            this.proveedorMapaVisualizacion = 'google';
        },


        /**
         * Eliminar una ruta
         */
        async eliminarRuta(id) {
            const ruta = this.rutas.find(r => r.id == id);
            if (!ruta) return;

            const nombreRuta = ruta.nombre || 'Sin nombre';
            const confirmacion = await this.mostrarConfirmacion(
                `¿Está seguro que desea eliminar la hoja de ruta "${nombreRuta}"?`,
                'Eliminar Hoja de Ruta'
            );

            if (!confirmacion) return;

            try {
                await axios.delete(BASE_URL + 'api/rutas/' + id);
                this.mostrarMensaje('Hoja de ruta eliminada exitosamente', 'success');
                await this.obtenerRutas();
            } catch (error) {
                console.error('Error al eliminar ruta:', error);
                this.mostrarMensaje('Error al eliminar la hoja de ruta', 'error');
            }
        },

        /**
         * Formatea una fecha
         */
        formatearFecha(fecha) {
            if (!fecha) return '';
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

        /**
         * Muestra mensajes de notificación
         */
        mostrarMensaje(mensaje, tipo) {
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
            
            setTimeout(() => {
                $('.mensaje-notificacion').fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Muestra una confirmación personalizada
         */
        mostrarConfirmacion(mensaje, titulo = 'Confirmar Acción') {
            return new Promise((resolve) => {
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
                                        <i class="bi bi-x-circle me-1"></i>Cancelar
                                    </button>
                                    <button type="button" class="btn btn-warning" id="btnConfirmar">
                                        <i class="bi bi-check-circle me-1"></i>Confirmar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#modalConfirmacion').remove();
                $('body').append(modalHtml);
                
                const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
                modal.show();

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

                $('#modalConfirmacion').on('hidden.bs.modal', () => {
                    $('#modalConfirmacion').remove();
                    resolve(false);
                });
            });
        },

        /**
         * Abre el modal para visualizar todas las rutas (asignadas y no asignadas)
         */
        async abrirModalVisualizarRutas() {
            try {
                // Mostrar TODAS las rutas (asignadas y no asignadas)
                this.rutasActivas = this.rutas;
                
                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalVisualizarRutas'));
                modal.show();
                
                // Inicializar mapa después de que el modal se muestre
                this.$nextTick(() => {
                    setTimeout(async () => {
                        await this.inicializarMapaRutasActivas();
                    }, 300);
                });
                
            } catch (error) {
                console.error('Error al abrir visualización de rutas:', error);
                this.mostrarMensaje('Error al cargar las rutas', 'error');
            }
        },

        /**
         * Inicializa el mapa para visualizar todas las rutas (asignadas y no asignadas) (con fallback a Mapbox)
         */
        async inicializarMapaRutasActivas() {
            try {
                const lat = -31.427;
                const lng = -62.082;

                this.mapaRutasActivas = new google.maps.Map(document.getElementById('mapaVisualizarRutas'), {
                    center: { lat: lat, lng: lng },
                    zoom: 13,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    styles: [
                        {
                            featureType: "poi",
                            elementType: "labels",
                            stylers: [{ visibility: "off" }]
                        }
                    ]
                });

                // Inicializar geocoder
                this.geocoder = new google.maps.Geocoder();
                
                // Mostrar todas las rutas activas
                await this.mostrarTodasLasRutasActivas();

            } catch (error) {
                console.error('Error al inicializar mapa Google Maps:', error);
                console.log('Intentando fallback a Mapbox...');
                
                // FALLBACK AUTOMÁTICO
                this.proveedorMapaRutasActivas = 'mapbox';
                await this.$nextTick();
                try {
                    await this.inicializarMapaRutasActivasMapbox();
                    await this.mostrarTodasLasRutasActivasMapbox();
                    this.mostrarMensaje('Google Maps no disponible. Usando Mapbox como alternativa.', 'warning');
                } catch (mapboxError) {
                    console.error('Error al inicializar Mapbox:', mapboxError);
                    this.mostrarMensaje('Error: No se pudo inicializar el mapa', 'error');
                }
            }
        },

        /**
         * Muestra todas las rutas (asignadas y no asignadas) en el mapa
         */
        async mostrarTodasLasRutasActivas() {
            // Limpiar marcadores y renderers anteriores
            this.limpiarVisualizacionRutasActivas();
            
            for (const ruta of this.rutasActivas) {
                try {
                    // Obtener reclamos de esta ruta
                    const response = await axios.get(BASE_URL + 'api/rutas/' + ruta.id + '/reclamos');
                    const reclamosRuta = response.data;
                    
                    const colorRuta = ruta.color || '#FF0000';
                    
                    // OPTIMIZACIÓN: Paralelizar obtención de coordenadas
                    const promesasCoordenadas = reclamosRuta.map(reclamo => 
                        this.obtenerCoordenadasReclamo(reclamo).then(coords => ({ reclamo, coords }))
                    );
                    
                    const resultados = await Promise.all(promesasCoordenadas);
                    
                    // Agregar marcadores numerados para esta ruta
                    for (const { reclamo, coords: coordenadas } of resultados) {
                        if (coordenadas) {
                            const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                            const colorPrioridad = this.getColorPrioridad(reclamo.prioridad || 'Baja');
                            
                            const marker = new google.maps.Marker({
                                position: { lat: coordenadas.lat, lng: coordenadas.lng },
                                map: this.mapaRutasActivas,
                                title: `${ruta.nombre || 'Sin nombre'} - Pos. ${reclamo.posicion}`,
                                icon: this.crearIconoNumerado(reclamo.posicion, colorEstado, colorPrioridad),
                                zIndex: 1000
                            });

                            // Crear info window
                            const infoWindow = new google.maps.InfoWindow({
                                content: `
                                    <div style="min-width: 250px;">
                                        <h6 style="margin-bottom: 8px; color: ${colorRuta};">
                                            <strong>${ruta.nombre || 'Sin nombre'}</strong>
                                        </h6>
                                        <p style="margin-bottom: 4px;"><strong>Posición:</strong> ${reclamo.posicion}</p>
                                        <p style="margin-bottom: 4px;"><strong>Reclamo:</strong> #${reclamo.municipalidad_id}</p>
                                        <p style="margin-bottom: 4px;"><strong>Motivo:</strong> ${reclamo.municipalidad_motivo || 'No especificado'}</p>
                                        <p style="margin-bottom: 4px;"><strong>Estado:</strong> ${reclamo.municipalidad_estado || 'No especificado'}</p>
                                        <p style="margin-bottom: 4px;"><strong>Dirección:</strong> ${reclamo.municipalidad_domicilio || 'No especificado'} ${reclamo.municipalidad_numeroDomicilio || ''}</p>
                                    </div>
                                `
                            });

                            marker.addListener('click', () => {
                                if (this.infoWindowAbiertoRutasActivas) {
                                    this.infoWindowAbiertoRutasActivas.close();
                                }
                                infoWindow.open(this.mapaRutasActivas, marker);
                                this.infoWindowAbiertoRutasActivas = infoWindow;
                            });

                            marker._reclamo = reclamo;
                            marker._ruta = ruta;
                            this.marcadoresRutasActivas.push(marker);
                        }
                    }
                    
                    // Trazar la ruta con su color
                    // OPTIMIZACIÓN: Reutilizar coordenadas ya obtenidas
                    if (reclamosRuta.length > 1) {
                        const coordenadas = resultados
                            .filter(r => r.coords)
                            .map(r => ({ lat: r.coords.lat, lng: r.coords.lng }));
                        
                        if (coordenadas.length > 1) {
                            const directionsService = new google.maps.DirectionsService();
                            const directionsRenderer = new google.maps.DirectionsRenderer({
                                suppressMarkers: true,
                                preserveViewport: true,
                                polylineOptions: {
                                    strokeColor: colorRuta,
                                    strokeOpacity: 0.8,
                                    strokeWeight: 4
                                }
                            });
                            
                            directionsRenderer.setMap(this.mapaRutasActivas);
                            
                            const origin = coordenadas[0];
                            const destination = coordenadas[coordenadas.length - 1];
                            const waypoints = coordenadas.slice(1, -1).map(coord => ({
                                location: coord,
                                stopover: true
                            }));
                            
                            const request = {
                                origin: origin,
                                destination: destination,
                                waypoints: waypoints,
                                travelMode: google.maps.TravelMode.DRIVING,
                                optimizeWaypoints: false
                            };
                            
                            try {
                                const result = await new Promise((resolve, reject) => {
                                    directionsService.route(request, (result, status) => {
                                        if (status === 'OK') {
                                            resolve(result);
                                        } else {
                                            reject(new Error('Error al obtener direcciones: ' + status));
                                        }
                                    });
                                });
                                
                                directionsRenderer.setDirections(result);
                                this.directionsRenderersRutasActivas.push(directionsRenderer);
                                
                            } catch (error) {
                                console.warn('Error al trazar ruta ' + ruta.id + ' por calles:', error);
                            }
                        }
                    }
                    
                } catch (error) {
                    console.warn('Error al cargar ruta ' + ruta.id + ':', error);
                }
            }
        },

        /**
         * Centra el mapa en una ruta específica
         */
        async centrarEnRutaActiva(ruta) {
            if (!this.mapaRutasActivas) return;
            
            // Buscar el primer marcador de esta ruta
            const marcador = this.marcadoresRutasActivas.find(m => m._ruta && m._ruta.id === ruta.id);
            
            if (marcador) {
                this.mapaRutasActivas.setCenter(marcador.getPosition());
                this.mapaRutasActivas.setZoom(15);
                
                // Animación de rebote
                marcador.setAnimation(google.maps.Animation.BOUNCE);
                setTimeout(() => {
                    marcador.setAnimation(null);
                }, 1500);
            }
        },

        /**
         * Limpia la visualización de todas las rutas
         */
        limpiarVisualizacionRutasActivas() {
            // Cerrar info window si está abierto
            if (this.infoWindowAbiertoRutasActivas) {
                this.infoWindowAbiertoRutasActivas.close();
                this.infoWindowAbiertoRutasActivas = null;
            }
            
            // Limpiar marcadores
            this.marcadoresRutasActivas.forEach(marker => {
                if (marker) marker.setMap(null);
            });
            this.marcadoresRutasActivas = [];
            
            // Limpiar direction renderers
            this.directionsRenderersRutasActivas.forEach(renderer => {
                if (renderer) {
                    renderer.setMap(null);
                    renderer.setDirections({ routes: [] });
                }
            });
            this.directionsRenderersRutasActivas = [];
        },

        /**
         * Cierra el modal de visualización de todas las rutas
         */
        cerrarVisualizacionRutas() {
            this.limpiarVisualizacionRutasActivas();
            this.rutasActivas = [];
            this.mapaRutasActivas = null;
            if (this.mapaRutasActivasMapbox) {
                this.mapaRutasActivasMapbox.remove();
                this.mapaRutasActivasMapbox = null;
            }
        },

        /**
         * Alterna entre Google Maps y Mapbox en vista previa
         */
        async alternarProveedorVistaPrevia() {
            const nuevoProveedor = this.proveedorMapaVistaPrevia === 'google' ? 'mapbox' : 'google';
            
            this.proveedorMapaVistaPrevia = nuevoProveedor;
            
            // Esperar a que el DOM se actualice
            await this.$nextTick();
            
            if (nuevoProveedor === 'mapbox') {
                // Inicializar o actualizar mapa Mapbox
                await this.inicializarMapaMapbox();
                await this.mostrarVistaPreviaEnMapaMapbox();
            } else {
                // Ya tenemos el mapa de Google, solo asegurar que esté visible
                if (this.mapa) {
                    google.maps.event.trigger(this.mapa, 'resize');
                }
            }
        },

        /**
         * Alterna entre Google Maps y Mapbox en visualización de ruta
         */
        async alternarProveedorVisualizacion() {
            const nuevoProveedor = this.proveedorMapaVisualizacion === 'google' ? 'mapbox' : 'google';
            
            this.proveedorMapaVisualizacion = nuevoProveedor;
            
            await this.$nextTick();
            
            if (nuevoProveedor === 'mapbox') {
                await this.inicializarMapaVisualizacionMapbox();
                await this.mostrarRutaEnMapaMapbox();
            } else {
                if (this.mapaVisualizacion) {
                    google.maps.event.trigger(this.mapaVisualizacion, 'resize');
                }
            }
        },

        /**
         * Alterna entre Google Maps y Mapbox en visualización de todas las rutas
         */
        async alternarProveedorRutasActivas() {
            const nuevoProveedor = this.proveedorMapaRutasActivas === 'google' ? 'mapbox' : 'google';
            
            this.proveedorMapaRutasActivas = nuevoProveedor;
            
            await this.$nextTick();
            
            if (nuevoProveedor === 'mapbox') {
                await this.inicializarMapaRutasActivasMapbox();
                await this.mostrarTodasLasRutasActivasMapbox();
            } else {
                if (this.mapaRutasActivas) {
                    google.maps.event.trigger(this.mapaRutasActivas, 'resize');
                }
            }
        },

        /**
         * Inicializa el mapa Mapbox para vista previa
         */
        async inicializarMapaMapbox() {
            if (this.mapaMapbox) {
                this.mapaMapbox.remove();
            }

            mapboxgl.accessToken = this.mapboxToken;
            
            this.mapaMapbox = new mapboxgl.Map({
                container: 'mapaCrearRutaMapbox',
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [-62.082, -31.427],
                zoom: 13
            });

            await new Promise(resolve => this.mapaMapbox.on('load', resolve));
        },

        /**
         * Inicializa el mapa Mapbox para visualización de ruta
         */
        async inicializarMapaVisualizacionMapbox() {
            if (this.mapaVisualizacionMapbox) {
                this.mapaVisualizacionMapbox.remove();
            }

            mapboxgl.accessToken = this.mapboxToken;
            
            this.mapaVisualizacionMapbox = new mapboxgl.Map({
                container: 'mapaVerRutaMapbox',
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [-62.082, -31.427],
                zoom: 13
            });

            await new Promise(resolve => this.mapaVisualizacionMapbox.on('load', resolve));
        },

        /**
         * Inicializa el mapa Mapbox para visualización de todas las rutas
         */
        async inicializarMapaRutasActivasMapbox() {
            if (this.mapaRutasActivasMapbox) {
                this.mapaRutasActivasMapbox.remove();
            }

            mapboxgl.accessToken = this.mapboxToken;
            
            this.mapaRutasActivasMapbox = new mapboxgl.Map({
                container: 'mapaVisualizarRutasMapbox',
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [-62.082, -31.427],
                zoom: 13
            });

            await new Promise(resolve => this.mapaRutasActivasMapbox.on('load', resolve));
        },

        /**
         * Muestra la vista previa en Mapbox
         */
        async mostrarVistaPreviaEnMapaMapbox() {
            if (!this.mapaMapbox) return;

            // Limpiar capas y fuentes anteriores
            if (this.mapaMapbox.getLayer('route')) this.mapaMapbox.removeLayer('route');
            if (this.mapaMapbox.getSource('route')) this.mapaMapbox.removeSource('route');
            
            // Eliminar marcadores anteriores
            const marcadoresAnteriores = document.querySelectorAll('#mapaCrearRutaMapbox .mapboxgl-marker');
            marcadoresAnteriores.forEach(m => m.remove());

            // Agregar marcadores de reclamos NO en ruta
            const idsRutaPrevia = this.vistaPrevia.rutaOptimizada.map(r => r.id);
            
            for (const reclamo of this.reclamos) {
                if (!idsRutaPrevia.includes(reclamo.id)) {
                    const coordenadas = await this.obtenerCoordenadasReclamo(reclamo);
                    
                    if (coordenadas) {
                        const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                        
                        // Crear elemento del marcador
                        const el = document.createElement('div');
                        el.className = 'marker-mapbox-reclamo';
                        el.innerHTML = `
                            <svg width="28" height="32" viewBox="0 0 28 32">
                                <path d="M14 2C10.13 2 7 5.13 7 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" 
                                      fill="${colorEstado}" stroke="#FFFFFF" stroke-width="1"/>
                            </svg>
                        `;
                        el.style.cursor = 'pointer';
                        
                        const marker = new mapboxgl.Marker(el)
                            .setLngLat([coordenadas.lng, coordenadas.lat])
                            .addTo(this.mapaMapbox);
                        
                        // Popup para el marcador
                        const popup = new mapboxgl.Popup({ offset: 25 })
                            .setHTML(this.crearContenidoInfoWindow(reclamo));
                        
                        el.addEventListener('click', () => {
                            if (this.modoEdicion) {
                                this.agregarReclamoARuta(reclamo);
                            } else {
                                marker.setPopup(popup);
                                marker.togglePopup();
                            }
                        });
                    }
                }
            }

            // Agregar marcadores numerados para la ruta
            for (let i = 0; i < this.vistaPrevia.rutaOptimizada.length; i++) {
                const reclamo = this.vistaPrevia.rutaOptimizada[i];
                const coordenadas = await this.obtenerCoordenadasReclamo(reclamo);
                
                if (coordenadas) {
                    const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                    
                    const el = document.createElement('div');
                    el.className = 'marker-mapbox-ruta';
                    el.innerHTML = `
                        <svg width="32" height="32" viewBox="0 0 32 32">
                            <circle cx="16" cy="16" r="14" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                            <text x="16" y="20" text-anchor="middle" fill="#FFFFFF" font-family="Arial" font-size="12" font-weight="bold">${i + 1}</text>
                        </svg>
                    `;
                    
                    const marker = new mapboxgl.Marker(el)
                        .setLngLat([coordenadas.lng, coordenadas.lat])
                        .addTo(this.mapaMapbox);
                    
                    const popup = new mapboxgl.Popup({ offset: 25 })
                        .setHTML(this.crearContenidoInfoWindow({ ...reclamo, posicion: i + 1 }));
                    
                    marker.setPopup(popup);
                }
            }

            // Trazar ruta en Mapbox
            if (this.vistaPrevia.rutaOptimizada.length > 1) {
                await this.trazarRutaMapbox(this.vistaPrevia.rutaOptimizada, this.mapaMapbox, this.nuevaRuta.color);
            }
        },

        /**
         * Muestra una ruta individual en Mapbox
         */
        async mostrarRutaEnMapaMapbox() {
            if (!this.mapaVisualizacionMapbox) return;

            // Limpiar capas anteriores
            if (this.mapaVisualizacionMapbox.getLayer('route')) this.mapaVisualizacionMapbox.removeLayer('route');
            if (this.mapaVisualizacionMapbox.getSource('route')) this.mapaVisualizacionMapbox.removeSource('route');
            
            const marcadoresAnteriores = document.querySelectorAll('#mapaVerRutaMapbox .mapboxgl-marker');
            marcadoresAnteriores.forEach(m => m.remove());

            // Agregar marcadores
            for (const reclamo of this.reclamosRutaVisualizando) {
                const coordenadas = await this.obtenerCoordenadasReclamo(reclamo);
                
                if (coordenadas) {
                    const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                    
                    const el = document.createElement('div');
                    el.innerHTML = `
                        <svg width="32" height="32" viewBox="0 0 32 32">
                            <circle cx="16" cy="16" r="14" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                            <text x="16" y="20" text-anchor="middle" fill="#FFFFFF" font-family="Arial" font-size="12" font-weight="bold">${reclamo.posicion}</text>
                        </svg>
                    `;
                    
                    const marker = new mapboxgl.Marker(el)
                        .setLngLat([coordenadas.lng, coordenadas.lat])
                        .setPopup(new mapboxgl.Popup().setHTML(this.crearContenidoInfoWindow(reclamo)))
                        .addTo(this.mapaVisualizacionMapbox);
                }
            }

            // Trazar ruta
            const colorRuta = this.rutaVisualizando.color || '#FF0000';
            await this.trazarRutaMapbox(this.reclamosRutaVisualizando, this.mapaVisualizacionMapbox, colorRuta);
        },

        /**
         * Muestra todas las rutas (asignadas y no asignadas) en Mapbox
         */
        async mostrarTodasLasRutasActivasMapbox() {
            if (!this.mapaRutasActivasMapbox) return;

            // Limpiar capas anteriores
            this.rutasActivas.forEach((ruta, idx) => {
                if (this.mapaRutasActivasMapbox.getLayer(`route-${idx}`)) 
                    this.mapaRutasActivasMapbox.removeLayer(`route-${idx}`);
                if (this.mapaRutasActivasMapbox.getSource(`route-${idx}`)) 
                    this.mapaRutasActivasMapbox.removeSource(`route-${idx}`);
            });
            
            const marcadoresAnteriores = document.querySelectorAll('#mapaVisualizarRutasMapbox .mapboxgl-marker');
            marcadoresAnteriores.forEach(m => m.remove());

            // Procesar cada ruta
            for (let rutaIdx = 0; rutaIdx < this.rutasActivas.length; rutaIdx++) {
                const ruta = this.rutasActivas[rutaIdx];
                
                try {
                    const response = await axios.get(BASE_URL + 'api/rutas/' + ruta.id + '/reclamos');
                    const reclamosRuta = response.data;
                    const colorRuta = ruta.color || '#FF0000';

                    // Agregar marcadores
                    for (const reclamo of reclamosRuta) {
                        const coordenadas = await this.obtenerCoordenadasReclamo(reclamo);
                        
                        if (coordenadas) {
                            const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                            
                            const el = document.createElement('div');
                            el.innerHTML = `
                                <svg width="28" height="28" viewBox="0 0 28 28">
                                    <circle cx="14" cy="14" r="12" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                                    <text x="14" y="17" text-anchor="middle" fill="#FFFFFF" font-family="Arial" font-size="10" font-weight="bold">${reclamo.posicion}</text>
                                </svg>
                            `;
                            
                            const marker = new mapboxgl.Marker(el)
                                .setLngLat([coordenadas.lng, coordenadas.lat])
                                .setPopup(new mapboxgl.Popup().setHTML(`
                                    <div style="min-width: 200px;">
                                        <h6 style="color: ${colorRuta};"><strong>${ruta.nombre}</strong></h6>
                                        <p><strong>Posición:</strong> ${reclamo.posicion}</p>
                                        <p><strong>Reclamo:</strong> #${reclamo.municipalidad_id}</p>
                                        <p><strong>Motivo:</strong> ${reclamo.municipalidad_motivo}</p>
                                    </div>
                                `))
                                .addTo(this.mapaRutasActivasMapbox);
                        }
                    }

                    // Trazar ruta
                    await this.trazarRutaMapboxConId(reclamosRuta, this.mapaRutasActivasMapbox, colorRuta, `route-${rutaIdx}`);
                    
                } catch (error) {
                    console.warn('Error al cargar ruta en Mapbox:', error);
                }
            }
        },

        /**
         * Traza una ruta en Mapbox usando Directions API
         */
        async trazarRutaMapbox(reclamos, mapa, color) {
            try {
                const coordenadas = [];
                for (const reclamo of reclamos) {
                    const coords = await this.obtenerCoordenadasReclamo(reclamo);
                    if (coords) {
                        coordenadas.push([coords.lng, coords.lat]);
                    }
                }

                if (coordenadas.length < 2) return;

                // Construir URL de Mapbox Directions API
                const coordinates = coordenadas.map(c => `${c[0]},${c[1]}`).join(';');
                const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${coordinates}?geometries=geojson&access_token=${this.mapboxToken}`;

                const response = await fetch(url);
                const data = await response.json();

                if (data.routes && data.routes[0]) {
                    const route = data.routes[0].geometry;

                    // Agregar ruta al mapa
                    if (mapa.getSource('route')) {
                        mapa.getSource('route').setData(route);
                    } else {
                        mapa.addSource('route', {
                            type: 'geojson',
                            data: route
                        });

                        mapa.addLayer({
                            id: 'route',
                            type: 'line',
                            source: 'route',
                            layout: {
                                'line-join': 'round',
                                'line-cap': 'round'
                            },
                            paint: {
                                'line-color': color,
                                'line-width': 4,
                                'line-opacity': 0.8
                            }
                        });
                    }
                }
            } catch (error) {
                console.error('Error al trazar ruta en Mapbox:', error);
            }
        },

        /**
         * Traza una ruta en Mapbox con ID personalizado (para múltiples rutas)
         */
        async trazarRutaMapboxConId(reclamos, mapa, color, routeId) {
            try {
                const coordenadas = [];
                for (const reclamo of reclamos) {
                    const coords = await this.obtenerCoordenadasReclamo(reclamo);
                    if (coords) {
                        coordenadas.push([coords.lng, coords.lat]);
                    }
                }

                if (coordenadas.length < 2) return;

                const coordinates = coordenadas.map(c => `${c[0]},${c[1]}`).join(';');
                const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${coordinates}?geometries=geojson&access_token=${this.mapboxToken}`;

                const response = await fetch(url);
                const data = await response.json();

                if (data.routes && data.routes[0]) {
                    const route = data.routes[0].geometry;

                    mapa.addSource(routeId, {
                        type: 'geojson',
                        data: route
                    });

                    mapa.addLayer({
                        id: routeId,
                        type: 'line',
                        source: routeId,
                        layout: {
                            'line-join': 'round',
                            'line-cap': 'round'
                        },
                        paint: {
                            'line-color': color,
                            'line-width': 4,
                            'line-opacity': 0.8
                        }
                    });
                }
            } catch (error) {
                console.error('Error al trazar ruta en Mapbox:', error);
            }
        }
    },

    async mounted() {
        await this.obtenerRutas();
        await this.obtenerReclamos();
    }
});

