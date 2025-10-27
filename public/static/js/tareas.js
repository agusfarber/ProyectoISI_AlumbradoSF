const app = Vue.createApp({
    data() {
        return {
            reclamos: [],
            rutas: [],
            reclamoSeleccionado: {},
            
            // Variables para filtros - Comentadas para HU futura
            // filtroEstado: '',
            // filtroPrioridad: '',
            // filtroFechaDesde: '',
            // filtroFechaHasta: '',
            
            // Variables para modales
            nuevoEstado: '',
            
            // Variables para historial
            historialReclamo: [],
            cargandoHistorial: false,
            
            // Variables para el mapa
            mapaRutas: null,
            marcadoresRutas: [],
            directionsRenderersRutas: [],
            infoWindowAbierto: null,
            
            // Control de proveedores de mapa
            proveedorMapaRutas: 'google', // 'google' o 'mapbox'
            
            // Mapa Mapbox
            mapaRutasMapbox: null,
            
            // API Key de Mapbox
            mapboxToken: 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ejJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw',
            
            // Rol del usuario
            userRole: window.USER_ROLE || '3',
            
            // Variables para el modal de añadir reclamos
            reclamosRecibidos: [],
            reclamosRecibidosFiltrados: [],
            filtroBusquedaReclamos: '',
            reclamoRecibidoSeleccionado: {},
            añadiendoReclamo: null
        };
    },

    computed: {
        // Computed property de filtros comentado para HU futura
        // reclamosFiltrados() {
        //     let filtrados = [...this.reclamos];
        //     // ... lógica de filtros comentada
        //     return filtrados;
        // }
        
        // Mostrar reclamos ordenados por estado: Asignado -> En ejecución -> Completado
        reclamosFiltrados() {
            if (!this.reclamos || this.reclamos.length === 0) {
                return [];
            }
            
            // Crear una copia del array para no modificar el original
            const reclamosOrdenados = [...this.reclamos];
            
            // Definir el orden de prioridad de los estados
            const ordenEstados = {
                'Asignado': 1,
                'En ejecución': 2,
                'Completado': 3,
                'Recibido': 4,
                'En plan': 5,
                'Error de datos': 6
            };
            
            // Ordenar por estado y luego por fecha de modificación (más reciente primero)
            return reclamosOrdenados.sort((a, b) => {
                const ordenA = ordenEstados[a.municipalidad_estado] || 999;
                const ordenB = ordenEstados[b.municipalidad_estado] || 999;
                
                // Si tienen el mismo estado, ordenar por fecha de modificación (más reciente primero)
                if (ordenA === ordenB) {
                    const fechaA = new Date(a.municipalidad_fechaModificacion || a.municipalidad_fechaInicio || 0);
                    const fechaB = new Date(b.municipalidad_fechaModificacion || b.municipalidad_fechaInicio || 0);
                    return fechaB - fechaA;
                }
                
                return ordenA - ordenB;
            });
        },
        
        totalReclamos() {
            return this.reclamos.length;
        },
        
        reclamosCompletados() {
            return this.reclamos.filter(r => r.municipalidad_estado === 'Completado').length;
        },
        
        reclamosPendientes() {
            return this.totalReclamos - this.reclamosCompletados;
        },
        
        // Verifica si el usuario es operario
        esOperario() {
            return this.userRole === '3';
        }
    },

    methods: {
        /**
         * Obtiene los reclamos según el rol del usuario
         */
        async obtenerReclamos() {
            try {
                let reclamosObtenidos = [];
                
                // Si es operario (rol 3), usar endpoint filtrado
                if (this.esOperario) {
                    const response = await axios.get(BASE_URL + 'api/rutas/operario/mis-reclamos');
                    reclamosObtenidos = response.data;
                    console.log('Reclamos de mi cuadrilla obtenidos:', reclamosObtenidos);
                } else {
                    // Para supervisor y admin, obtener todos los reclamos
                    const response = await axios.get(BASE_URL + 'api/reclamos');
                    reclamosObtenidos = response.data;
                    console.log('Todos los reclamos obtenidos:', reclamosObtenidos);
                }
                
                // Eliminar duplicados antes de asignar
                this.reclamos = this.eliminarDuplicadosReclamos(reclamosObtenidos);
                console.log('Reclamos después de eliminar duplicados:', this.reclamos.length, 'de', reclamosObtenidos.length);
                
            } catch (error) {
                console.error('Error al obtener reclamos:', error);
                this.mostrarMensaje('Error al cargar los reclamos', 'error');
            }
        },
        
        /**
         * Obtiene las rutas asignadas a la cuadrilla del operario (solo para operarios)
         */
        async obtenerRutasOperario() {
            try {
                // Solo obtener rutas si es operario
                if (this.esOperario) {
                    const response = await axios.get(BASE_URL + 'api/rutas/operario/mis-rutas');
                    this.rutas = response.data;
                    console.log('Rutas de mi cuadrilla obtenidas:', this.rutas);
                } else {
                    this.rutas = [];
                }
            } catch (error) {
                console.error('Error al obtener rutas:', error);
                this.mostrarMensaje('Error al cargar las rutas', 'error');
            }
        },



        /**
         * Muestra los detalles de un reclamo
         */
        verDetalles(reclamo) {
            this.reclamoSeleccionado = { ...reclamo };
            new bootstrap.Modal(document.getElementById('modalDetalles')).show();
        },

        /**
         * Abre el modal de acciones
         */
        cambiarEstado(reclamo) {
            this.reclamoSeleccionado = { ...reclamo };
            this.nuevoEstado = '';
            this.historialReclamo = []; // Limpiar historial anterior
            
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('modalAcciones'));
            modal.show();
            
            // Asegurar que la pestaña "Cambiar Estado" esté activa
            this.$nextTick(() => {
                // Activar la pestaña "Cambiar Estado"
                const cambiarEstadoTab = document.getElementById('cambiar-estado-tab');
                const historialTab = document.getElementById('historial-tab');
                const cambiarEstadoPane = document.getElementById('cambiar-estado');
                const historialPane = document.getElementById('historial');
                
                if (cambiarEstadoTab && historialTab && cambiarEstadoPane && historialPane) {
                    // Remover clases activas de ambas pestañas
                    cambiarEstadoTab.classList.remove('active');
                    historialTab.classList.remove('active');
                    cambiarEstadoPane.classList.remove('show', 'active');
                    historialPane.classList.remove('show', 'active');
                    
                    // Activar la pestaña "Cambiar Estado"
                    cambiarEstadoTab.classList.add('active');
                    cambiarEstadoPane.classList.add('show', 'active');
                    
                    // Actualizar atributos ARIA
                    cambiarEstadoTab.setAttribute('aria-selected', 'true');
                    historialTab.setAttribute('aria-selected', 'false');
                }
            });
        },

        /**
         * Guarda el cambio de estado
         */
        async guardarCambioEstado() {
            if (!this.nuevoEstado) {
                this.mostrarMensaje('Debe seleccionar un nuevo estado', 'warning');
                return;
            }

            try {
                const datosActualizacion = {
                    ...this.reclamoSeleccionado,
                    municipalidad_estado: this.nuevoEstado,
                    municipalidad_fechaModificacion: this.obtenerFechaActualArgentina()
                };

                // Actualizar prioridad según el nuevo estado
                if (this.nuevoEstado === 'En ejecución') {
                    datosActualizacion.prioridad = 'Alta';
                } else if (this.nuevoEstado === 'Completado') {
                    datosActualizacion.prioridad = null;
                }

                await axios.put(BASE_URL + 'api/reclamos/' + this.reclamoSeleccionado.id, datosActualizacion);
                
                // Actualizar el reclamo en la lista local
                const index = this.reclamos.findIndex(r => r.id === this.reclamoSeleccionado.id);
                if (index !== -1) {
                    this.reclamos[index].municipalidad_estado = this.nuevoEstado;
                    this.reclamos[index].municipalidad_fechaModificacion = datosActualizacion.municipalidad_fechaModificacion;
                    
                    // También actualizar la prioridad en la lista local
                    if (this.nuevoEstado === 'En ejecución') {
                        this.reclamos[index].prioridad = 'Alta';
                    } else if (this.nuevoEstado === 'Completado') {
                        this.reclamos[index].prioridad = null;
                    }
                }

                bootstrap.Modal.getInstance(document.getElementById('modalAcciones')).hide();
                this.mostrarMensaje(`Estado cambiado a: ${this.nuevoEstado}`, 'success');

            } catch (error) {
                console.error('Error al cambiar estado:', error);
                this.mostrarMensaje('Error al cambiar el estado del reclamo', 'error');
            }
        },





        // Métodos de filtros comentados para HU futura
        // /**
        //  * Aplica los filtros (ya manejado por computed)
        //  */
        // aplicarFiltros() {
        //     // Los filtros se aplican automáticamente por el computed property
        // },

        // /**
        //  * Limpia todos los filtros
        //  */
        // limpiarFiltros() {
        //     this.filtroEstado = '';
        //     this.filtroPrioridad = '';
        //     this.filtroFechaDesde = '';
        //     this.filtroFechaHasta = '';
        // },

        /**
         * Obtiene la fecha actual en formato para inputs datetime-local
         */
        obtenerFechaActualArgentina() {
            const ahora = new Date();
            const offset = ahora.getTimezoneOffset() + (3 * 60);
            const fechaArgentina = new Date(ahora.getTime() - offset * 60 * 1000);
            return fechaArgentina.toISOString().slice(0, 16);
        },

        /**
         * Formatea una fecha para mostrar
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
                console.error('Error al formatear fecha:', error);
                return fecha;
            }
        },

        /**
         * Obtiene la clase CSS para las tarjetas según el estado
         */
        getCardClass(reclamo) {
            const estado = reclamo.municipalidad_estado;
            if (estado === 'Recibido') return 'border-secondary'; // Gris #808080
            if (estado === 'Asignado') return 'border-danger'; // Rojo #FF0000
            if (estado === 'En ejecución') return 'border-warning'; // Amarillo #FFD700
            if (estado === 'Completado') return 'border-success'; // Verde #198754
            if (estado === 'En plan') return 'border-secondary'; // Gris #808080
            if (estado === 'Error de datos') return 'border-secondary'; // Gris #808080
            return 'border-secondary';
        },

        /**
         * Obtiene la clase CSS para los badges de estado
         */
        getEstadoBadgeClass(estado) {
            switch (estado) {
                case 'Recibido': return 'bg-secondary'; // Gris #808080
                case 'Asignado': return 'bg-danger'; // Rojo #FF0000
                case 'En ejecución': return 'bg-warning'; // Amarillo #FFD700
                case 'Completado': return 'bg-success'; // Verde #00FF00
                case 'En plan': return 'bg-secondary'; // Gris #808080
                case 'Error de datos': return 'bg-secondary'; // Gris #808080
                default: return 'bg-secondary';
            }
        },

        /**
         * Obtiene la clase CSS para el texto de estado
         */
        getEstadoTextClass(estado) {
            switch (estado) {
                case 'Recibido': return 'text-secondary'; // Gris #808080
                case 'Asignado': return 'text-danger'; // Rojo #FF0000
                case 'En ejecución': return 'text-warning'; // Amarillo #FFD700
                case 'Completado': return 'text-success'; // Verde #198754
                case 'En plan': return 'text-secondary'; // Gris #808080
                case 'Error de datos': return 'text-secondary'; // Gris #808080
                default: return 'text-secondary';
            }
        },

        /**
         * Obtiene la clase CSS para los badges de prioridad
         */
        getPriorityBadgeClass(prioridad) {
            switch (prioridad) {
                case 'Alta': return 'bg-danger';
                case 'Baja': return 'bg-success';
                default: return 'bg-secondary';
            }
        },

        /**
         * Carga el historial de cambios de estado de un reclamo
         */
        async cargarHistorial() {
            if (!this.reclamoSeleccionado.id) {
                return;
            }

            this.cargandoHistorial = true;
            
            try {
                const response = await axios.get(BASE_URL + 'api/reclamos/' + this.reclamoSeleccionado.id + '/historial');
                this.historialReclamo = response.data;
                console.log('Historial cargado:', this.historialReclamo);
            } catch (error) {
                console.error('Error al cargar historial:', error);
                this.mostrarMensaje('Error al cargar el historial del reclamo', 'error');
                this.historialReclamo = [];
            } finally {
                this.cargandoHistorial = false;
            }
        },

        /**
         * Muestra mensajes de notificación
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
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                $('.mensaje-notificacion').fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Elimina reclamos duplicados basándose en el ID único
         */
        eliminarDuplicadosReclamos(reclamos) {
            const reclamosUnicos = [];
            const idsVistos = new Set();
            
            for (const reclamo of reclamos) {
                if (!idsVistos.has(reclamo.id)) {
                    idsVistos.add(reclamo.id);
                    reclamosUnicos.push(reclamo);
                } else {
                    console.warn('Reclamo duplicado encontrado:', reclamo.municipalidad_id, 'ID:', reclamo.id);
                }
            }
            
            return reclamosUnicos;
        },

        /**
         * Obtiene los reclamos de una ruta específica
         */
        obtenerReclamosPorRuta(rutaId) {
            const reclamosRuta = this.reclamos.filter(r => r.ruta_id == rutaId);
            // Aplicar deduplicación adicional por si acaso
            return this.eliminarDuplicadosReclamos(reclamosRuta);
        },

        /**
         * Abre el modal del mapa de rutas
         */
        async verMapaRutas() {
            const modal = new bootstrap.Modal(document.getElementById('modalMapaRutas'));
            modal.show();

            // Esperar a que el modal se muestre completamente
            await this.$nextTick();
            
            // Inicializar el mapa
            setTimeout(() => {
                this.inicializarMapaRutas();
            }, 300);
        },

        /**
         * Inicializa el mapa de Google Maps con las rutas (con fallback a Mapbox)
         */
        async inicializarMapaRutas() {
            try {
                if (!this.mapaRutas) {
                    // Configurar el mapa centrado en San Francisco, Córdoba
                    const centro = { lat: -31.426516, lng: -62.110954 };
                    
                    this.mapaRutas = new google.maps.Map(document.getElementById('mapaRutasOperario'), {
                        zoom: 13,
                        center: centro,
                        mapTypeId: 'roadmap',
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
                }

                // Limpiar marcadores y rutas anteriores
                this.limpiarMapaRutas();

                // Dibujar cada ruta con su color
                for (const ruta of this.rutas) {
                    const reclamosRuta = this.obtenerReclamosPorRuta(ruta.id);
                    if (reclamosRuta.length > 0) {
                        await this.dibujarRutaEnMapa(reclamosRuta, ruta.color || '#FF6B35');
                    }
                }
                
                // Limpiar marcadores duplicados después de dibujar todas las rutas
                this.limpiarMarcadoresDuplicados();
            } catch (error) {
                console.error('Error al inicializar mapa Google Maps:', error);
                console.log('Intentando fallback a Mapbox...');
                
                // FALLBACK AUTOMÁTICO: Cambiar a Mapbox si Google Maps falla
                this.proveedorMapaRutas = 'mapbox';
                await this.$nextTick();
                try {
                    await this.inicializarMapaRutasMapbox();
                    await this.mostrarRutasEnMapaMapbox();
                    this.mostrarMensaje('Google Maps no disponible. Usando Mapbox como alternativa.', 'warning');
                } catch (mapboxError) {
                    console.error('Error al inicializar Mapbox:', mapboxError);
                    this.mostrarMensaje('Error: No se pudo inicializar ningún proveedor de mapas', 'error');
                }
            }
        },

        /**
         * Dibuja una ruta en el mapa con sus marcadores
         */
        async dibujarRutaEnMapa(reclamos, color) {
            const waypoints = [];
            const bounds = new google.maps.LatLngBounds();

            // Agregar marcadores para cada reclamo
            for (const reclamo of reclamos) {
                if (reclamo.coordenadas) {
                    const position = {
                        lat: parseFloat(reclamo.coordenadas.lat),
                        lng: parseFloat(reclamo.coordenadas.lng)
                    };

                    // Verificar si ya existe un marcador para este reclamo específico
                    const marcadorExistente = this.marcadoresRutas.find(m => {
                        // Buscar por ID del reclamo en el título del marcador
                        const title = m.getTitle();
                        return title && title.includes(`Reclamo #${reclamo.municipalidad_id}`);
                    });

                    if (marcadorExistente) {
                        console.log('Marcador ya existe para reclamo:', reclamo.municipalidad_id);
                        bounds.extend(position);
                        waypoints.push(position);
                        continue;
                    }

                    // Obtener colores según estado y prioridad (igual que en rutas.js)
                    const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                    const colorPrioridad = this.getColorPrioridad(reclamo.prioridad || 'Baja');

                    const marker = new google.maps.Marker({
                        position: position,
                        map: this.mapaRutas,
                        title: `Posición ${reclamo.posicion}: Reclamo #${reclamo.municipalidad_id}${colorPrioridad !== null ? ' - ⚠️ PRIORIDAD ALTA' : ''}`,
                        icon: this.crearIconoNumerado(reclamo.posicion, colorEstado, colorPrioridad),
                        zIndex: 1000
                    });

                    // Info window para el marcador (igual que en rutas.js)
                    const infoContent = this.crearContenidoInfoWindow(reclamo);

                    const infowindow = new google.maps.InfoWindow({
                        content: infoContent
                    });

                    marker.addListener('click', () => {
                        if (this.infoWindowAbierto) {
                            this.infoWindowAbierto.close();
                        }
                        infowindow.open(this.mapaRutas, marker);
                        this.infoWindowAbierto = infowindow;
                    });

                    this.marcadoresRutas.push(marker);
                    bounds.extend(position);

                    // Agregar a waypoints para dibujar la ruta
                    waypoints.push(position);
                }
            }

            // Dibujar la ruta con Directions API
            if (waypoints.length >= 2) {
                const directionsService = new google.maps.DirectionsService();
                const directionsRenderer = new google.maps.DirectionsRenderer({
                    map: this.mapaRutas,
                    suppressMarkers: true,
                    polylineOptions: {
                        strokeColor: color,
                        strokeWeight: 4,
                        strokeOpacity: 0.7
                    }
                });

                const origin = waypoints[0];
                const destination = waypoints[waypoints.length - 1];
                const intermediateWaypoints = waypoints.slice(1, -1).map(w => ({
                    location: w,
                    stopover: true
                }));

                try {
                    const result = await directionsService.route({
                        origin: origin,
                        destination: destination,
                        waypoints: intermediateWaypoints,
                        travelMode: google.maps.TravelMode.DRIVING
                    });

                    directionsRenderer.setDirections(result);
                    this.directionsRenderersRutas.push(directionsRenderer);
                } catch (error) {
                    console.error('Error al dibujar ruta:', error);
                }
            }

            // Ajustar el mapa a los bounds
            if (waypoints.length > 0) {
                this.mapaRutas.fitBounds(bounds);
            }
        },

        /**
         * Centra el mapa en un reclamo específico
         */
        centrarEnReclamoMapa(reclamo) {
            if (reclamo.coordenadas && this.mapaRutas) {
                const position = {
                    lat: parseFloat(reclamo.coordenadas.lat),
                    lng: parseFloat(reclamo.coordenadas.lng)
                };
                
                this.mapaRutas.setCenter(position);
                this.mapaRutas.setZoom(16);

                // Encontrar el marcador correspondiente con tolerancia para coordenadas
                const marker = this.marcadoresRutas.find(m => {
                    const markerPos = m.getPosition();
                    const latDiff = Math.abs(markerPos.lat() - position.lat);
                    const lngDiff = Math.abs(markerPos.lng() - position.lng);
                    // Tolerancia de 0.0001 grados (aproximadamente 10 metros)
                    return latDiff < 0.0001 && lngDiff < 0.0001;
                });

                if (marker) {
                    // Detener cualquier animación previa
                    marker.setAnimation(null);
                    
                    // Aplicar animación de rebote
                    marker.setAnimation(google.maps.Animation.BOUNCE);
                    
                    // Detener la animación después de 1.5 segundos
                    setTimeout(() => {
                        marker.setAnimation(null);
                    }, 1500);
                    
                    google.maps.event.trigger(marker, 'click');
                } else {
                    console.warn('No se encontró marcador para el reclamo:', reclamo.municipalidad_id);
                }
            }
        },

        /**
         * Limpia marcadores duplicados del mapa
         */
        limpiarMarcadoresDuplicados() {
            const marcadoresUnicos = [];
            const idsVistos = new Set();
            
            this.marcadoresRutas.forEach(marker => {
                const title = marker.getTitle();
                // Extraer ID del reclamo del título
                const match = title.match(/Reclamo #(\d+)/);
                if (match) {
                    const reclamoId = match[1];
                    if (!idsVistos.has(reclamoId)) {
                        idsVistos.add(reclamoId);
                        marcadoresUnicos.push(marker);
                    } else {
                        console.warn('Marcador duplicado encontrado para reclamo:', reclamoId);
                        marker.setMap(null);
                        google.maps.event.clearInstanceListeners(marker);
                    }
                } else {
                    // Si no se puede extraer el ID, mantener el marcador
                    marcadoresUnicos.push(marker);
                }
            });
            
            this.marcadoresRutas = marcadoresUnicos;
        },

        /**
         * Limpia marcadores y rutas del mapa
         */
        limpiarMapaRutas() {
            // Limpiar marcadores
            this.marcadoresRutas.forEach(marker => {
                marker.setMap(null);
                // Limpiar también los listeners para evitar memory leaks
                google.maps.event.clearInstanceListeners(marker);
            });
            this.marcadoresRutas = [];

            // Limpiar directions renderers
            this.directionsRenderersRutas.forEach(renderer => {
                renderer.setMap(null);
                // Limpiar también los listeners
                google.maps.event.clearInstanceListeners(renderer);
            });
            this.directionsRenderersRutas = [];

            // Cerrar info windows
            if (this.infoWindowAbierto) {
                this.infoWindowAbierto.close();
                this.infoWindowAbierto = null;
            }
        },

        /**
         * Cierra el modal del mapa
         */
        cerrarMapaRutas() {
            this.limpiarMapaRutas();
            
            // Limpiar mapa Mapbox si existe
            if (this.mapaRutasMapbox) {
                this.mapaRutasMapbox.remove();
                this.mapaRutasMapbox = null;
            }
            
            // Resetear proveedor
            this.proveedorMapaRutas = 'google';
        },

        /**
         * Obtiene el color según el estado del reclamo (igual que en rutas.js)
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
         * Obtiene el color del borde según la prioridad (igual que en rutas.js)
         * Solo resalta prioridad Alta con borde rojo, los demás sin borde especial
         */
        getColorPrioridad(prioridad) {
            if (prioridad === 'Alta') {
                return '#DC3545'; // Rojo intenso para prioridad Alta
            }
            return null; // Sin borde especial para Media y Baja
        },

        /**
         * Crea un icono numerado para los marcadores de la ruta (igual que en rutas.js)
         * Si tiene prioridad Alta, muestra animación de pulso
         */
        crearIconoNumerado(numero, colorEstado, colorPrioridad) {
            const tienePrioridadAlta = colorPrioridad !== null;
            
            if (tienePrioridadAlta) {
                // Con animación de pulso doble más grande y lenta con rojo oscuro para prioridad Alta
                return {
                    url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                        <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                            <!-- Pulso exterior rojo oscuro grande y lento -->
                            <circle cx="20" cy="20" r="0" fill="#B71C1C" opacity="0.7">
                                <animate attributeName="r" values="0;24;0" dur="2.5s" repeatCount="indefinite"/>
                                <animate attributeName="opacity" values="0.7;0;0.7" dur="2.5s" repeatCount="indefinite"/>
                            </circle>
                            <!-- Pulso medio rojo intenso con retardo -->
                            <circle cx="20" cy="20" r="0" fill="#C62828" opacity="0.9">
                                <animate attributeName="r" values="0;19;0" dur="2.5s" begin="0.4s" repeatCount="indefinite"/>
                                <animate attributeName="opacity" values="0.9;0.3;0.9" dur="2.5s" begin="0.4s" repeatCount="indefinite"/>
                            </circle>
                            <!-- Círculo principal del marcador -->
                            <circle cx="20" cy="20" r="15" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                            <!-- Número -->
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
         * Crea el contenido del info window para un reclamo (igual que en rutas.js)
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
         * Alterna entre Google Maps y Mapbox en el mapa de rutas
         */
        async alternarProveedorMapaRutas() {
            const nuevoProveedor = this.proveedorMapaRutas === 'google' ? 'mapbox' : 'google';
            
            this.proveedorMapaRutas = nuevoProveedor;
            
            await this.$nextTick();
            
            if (nuevoProveedor === 'mapbox') {
                await this.inicializarMapaRutasMapbox();
                await this.mostrarRutasEnMapaMapbox();
            } else {
                await this.inicializarMapaRutas();
            }
        },

        /**
         * Inicializa el mapa Mapbox para rutas
         */
        async inicializarMapaRutasMapbox() {
            if (this.mapaRutasMapbox) {
                this.mapaRutasMapbox.remove();
            }

            mapboxgl.accessToken = this.mapboxToken;
            
            this.mapaRutasMapbox = new mapboxgl.Map({
                container: 'mapaRutasOperarioMapbox',
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [-62.110954, -31.426516],
                zoom: 13
            });

            await new Promise(resolve => this.mapaRutasMapbox.on('load', resolve));
            
            // Ocultar POI (Points of Interest) para que solo se vean los reclamos
            this.mapaRutasMapbox.setLayoutProperty('poi-label', 'visibility', 'none');
            this.mapaRutasMapbox.setLayoutProperty('poi-scalerank', 'visibility', 'none');
        },

        /**
         * Muestra las rutas en el mapa Mapbox
         */
        async mostrarRutasEnMapaMapbox() {
            if (!this.mapaRutasMapbox) return;

            // Limpiar capas anteriores
            this.rutas.forEach((ruta, idx) => {
                if (this.mapaRutasMapbox.getLayer(`route-${idx}`)) 
                    this.mapaRutasMapbox.removeLayer(`route-${idx}`);
                if (this.mapaRutasMapbox.getSource(`route-${idx}`)) 
                    this.mapaRutasMapbox.removeSource(`route-${idx}`);
            });
            
            const marcadoresAnteriores = document.querySelectorAll('#mapaRutasOperarioMapbox .mapboxgl-marker');
            marcadoresAnteriores.forEach(m => m.remove());

            // Procesar cada ruta
            for (let rutaIdx = 0; rutaIdx < this.rutas.length; rutaIdx++) {
                const ruta = this.rutas[rutaIdx];
                const reclamosRuta = this.obtenerReclamosPorRuta(ruta.id);
                const colorRuta = ruta.color || '#FF6B35';

                // Agregar marcadores
                for (const reclamo of reclamosRuta) {
                    if (reclamo.coordenadas) {
                        const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                        const colorPrioridad = this.getColorPrioridad(reclamo.prioridad || 'Baja');
                        
                        const el = document.createElement('div');
                        el.innerHTML = `
                            <svg width="32" height="32" viewBox="0 0 32 32">
                                <circle cx="16" cy="16" r="14" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                                <text x="16" y="20" text-anchor="middle" fill="#FFFFFF" font-family="Arial" font-size="12" font-weight="bold">${reclamo.posicion}</text>
                            </svg>
                        `;
                        
                        const marker = new mapboxgl.Marker(el)
                            .setLngLat([reclamo.coordenadas.lng, reclamo.coordenadas.lat])
                            .setPopup(new mapboxgl.Popup().setHTML(this.crearContenidoInfoWindow(reclamo)))
                            .addTo(this.mapaRutasMapbox);
                    }
                }

                // Trazar ruta
                await this.trazarRutaMapboxConId(reclamosRuta, this.mapaRutasMapbox, colorRuta, `route-${rutaIdx}`);
            }
        },

        /**
         * Traza una ruta en Mapbox con ID personalizado (para múltiples rutas)
         */
        async trazarRutaMapboxConId(reclamos, mapa, color, routeId) {
            try {
                const coordenadas = [];
                for (const reclamo of reclamos) {
                    if (reclamo.coordenadas) {
                        coordenadas.push([reclamo.coordenadas.lng, reclamo.coordenadas.lat]);
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
    },

    /**
     * Abre el modal para añadir reclamos recibidos a la hoja de ruta
     */
    async abrirModalAñadirReclamos() {
        try {
            // Cargar reclamos recibidos
            await this.obtenerReclamosRecibidos();
            
            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('modalAñadirReclamos'));
            modal.show();
            
        } catch (error) {
            console.error('Error al abrir modal de añadir reclamos:', error);
            this.mostrarMensaje('Error al cargar los reclamos recibidos', 'error');
        }
    },

    /**
     * Cierra el modal de añadir reclamos
     */
    cerrarModalAñadirReclamos() {
        // Limpiar filtros y datos
        this.filtroBusquedaReclamos = '';
        this.reclamosRecibidosFiltrados = [];
        this.reclamoRecibidoSeleccionado = {};
        this.añadiendoReclamo = null;
        
        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAñadirReclamos'));
        if (modal) {
            modal.hide();
        }
    },

    /**
     * Obtiene los reclamos recibidos del servidor
     */
    async obtenerReclamosRecibidos() {
        try {
            const response = await axios.get(BASE_URL + 'api/rutas/operario/reclamos-recibidos');
            this.reclamosRecibidos = response.data;
            this.reclamosRecibidosFiltrados = [...this.reclamosRecibidos];
            console.log('Reclamos recibidos obtenidos:', this.reclamosRecibidos);
        } catch (error) {
            console.error('Error al obtener reclamos recibidos:', error);
            this.mostrarMensaje('Error al cargar los reclamos recibidos', 'error');
            this.reclamosRecibidos = [];
            this.reclamosRecibidosFiltrados = [];
        }
    },

    /**
     * Filtra los reclamos recibidos según el término de búsqueda
     * Busca en todos los campos visibles en las tarjetas
     */
    filtrarReclamosRecibidos() {
        if (!this.filtroBusquedaReclamos.trim()) {
            this.reclamosRecibidosFiltrados = [...this.reclamosRecibidos];
            return;
        }

        const termino = this.filtroBusquedaReclamos.toLowerCase().trim();
        
        this.reclamosRecibidosFiltrados = this.reclamosRecibidos.filter(reclamo => {
            // Campos visibles en las tarjetas de reclamos recibidos
            const camposVisibles = [
                reclamo.municipalidad_id,
                reclamo.municipalidad_estado,
                reclamo.municipalidad_domicilio,
                reclamo.municipalidad_numeroDomicilio,
                reclamo.municipalidad_motivo,
                reclamo.prioridad
            ];
            
            // Buscar el término en todos los campos visibles
            return camposVisibles.some(campo => {
                if (campo && typeof campo === 'string') {
                    return campo.toLowerCase().includes(termino);
                }
                return false;
            });
        });
    },

    /**
     * Muestra los detalles de un reclamo recibido
     */
    verDetallesReclamoRecibido(reclamo) {
        this.reclamoRecibidoSeleccionado = { ...reclamo };
        
        // Cerrar el modal de añadir reclamos temporalmente
        const modalAñadir = bootstrap.Modal.getInstance(document.getElementById('modalAñadirReclamos'));
        if (modalAñadir) {
            modalAñadir.hide();
        }
        
        // Mostrar el modal de detalles
        const modalDetalles = new bootstrap.Modal(document.getElementById('modalDetallesReclamoRecibido'));
        modalDetalles.show();
    },

    /**
     * Cierra el modal de detalles de reclamo recibido y vuelve al modal de añadir
     */
    cerrarModalDetallesReclamoRecibido() {
        // Cerrar modal de detalles
        const modalDetalles = bootstrap.Modal.getInstance(document.getElementById('modalDetallesReclamoRecibido'));
        if (modalDetalles) {
            modalDetalles.hide();
        }
        
        // Volver a mostrar el modal de añadir reclamos
        setTimeout(() => {
            const modalAñadir = new bootstrap.Modal(document.getElementById('modalAñadirReclamos'));
            modalAñadir.show();
        }, 300);
    },

    /**
     * Añade un reclamo recibido a la hoja de ruta del operario
     */
    async añadirReclamoARuta(reclamo) {
        if (!reclamo || !reclamo.id) {
            this.mostrarMensaje('Error: Reclamo no válido', 'error');
            return;
        }

        // Verificar que hay rutas asignadas
        if (!this.rutas || this.rutas.length === 0) {
            this.mostrarMensaje('No tiene rutas asignadas para añadir reclamos', 'warning');
            return;
        }

        // Usar la primera ruta asignada (en el futuro se podría permitir seleccionar)
        const rutaId = this.rutas[0].id;

        this.añadiendoReclamo = reclamo.id;

        try {
            const response = await axios.post(BASE_URL + 'api/rutas/operario/add-reclamo', {
                reclamo_id: reclamo.id,
                ruta_id: rutaId
            });

            // Añadir el reclamo a la lista local de reclamos
            const reclamoAñadido = response.data.reclamo;
            this.reclamos.push(reclamoAñadido);
            
            // Aplicar deduplicación después de añadir
            this.reclamos = this.eliminarDuplicadosReclamos(this.reclamos);

            // Remover el reclamo de la lista de recibidos
            this.reclamosRecibidos = this.reclamosRecibidos.filter(r => r.id !== reclamo.id);
            this.filtrarReclamosRecibidos();

            // Actualizar contadores
            this.actualizarContadoresRutas();

            // Actualizar el mapa si está abierto
            this.actualizarMapaDespuesDeAñadirReclamo();

            this.mostrarMensaje(`Reclamo #${reclamo.municipalidad_id} añadido exitosamente a la hoja de ruta`, 'success');

            // Si estamos en el modal de detalles, cerrarlo y volver al de añadir
            const modalDetalles = bootstrap.Modal.getInstance(document.getElementById('modalDetallesReclamoRecibido'));
            if (modalDetalles) {
                this.cerrarModalDetallesReclamoRecibido();
            }

        } catch (error) {
            console.error('Error al añadir reclamo a ruta:', error);
            
            let mensajeError = 'Error al añadir el reclamo a la hoja de ruta';
            if (error.response && error.response.data && error.response.data.message) {
                mensajeError = error.response.data.message;
            }
            
            this.mostrarMensaje(mensajeError, 'error');
        } finally {
            this.añadiendoReclamo = null;
        }
    },

    /**
     * Actualiza los contadores de rutas después de añadir un reclamo
     */
    actualizarContadoresRutas() {
        // Actualizar la cantidad de reclamos en la ruta
        if (this.rutas && this.rutas.length > 0) {
            this.rutas[0].cantidadReclamos = this.reclamos.length;
        }
    },

    /**
     * Actualiza el mapa después de añadir un reclamo a la ruta
     */
    actualizarMapaDespuesDeAñadirReclamo() {
        // Verificar si el modal del mapa está abierto
        const modalMapa = document.getElementById('modalMapaRutas');
        if (modalMapa && modalMapa.classList.contains('show')) {
            // Si el mapa de Google Maps está activo, actualizarlo
            if (this.proveedorMapaRutas === 'google' && this.mapaRutas) {
                this.limpiarMapaRutas();
                
                // Redibujar todas las rutas con el nuevo reclamo
                setTimeout(async () => {
                    for (const ruta of this.rutas) {
                        const reclamosRuta = this.obtenerReclamosPorRuta(ruta.id);
                        if (reclamosRuta.length > 0) {
                            await this.dibujarRutaEnMapa(reclamosRuta, ruta.color || '#FF6B35');
                        }
                    }
                    // Limpiar marcadores duplicados después de redibujar
                    this.limpiarMarcadoresDuplicados();
                }, 100);
            }
            // Si el mapa de Mapbox está activo, actualizarlo
            else if (this.proveedorMapaRutas === 'mapbox' && this.mapaRutasMapbox) {
                this.mostrarRutasEnMapaMapbox();
            }
        }
    }
},

    async mounted() {
        await this.obtenerReclamos();
        await this.obtenerRutasOperario();
    }
});