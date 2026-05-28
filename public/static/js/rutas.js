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
            mapboxToken: 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ejJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw',
            
            // Asignación de rutas a cuadrillas
            rutaParaAsignar: {},
            cuadrillaSeleccionadaParaAsignar: '',
            cuadrillasDisponibles: [],
            cuadrillaSeleccionadaCrearRuta: '',
            modoVistaCrearRuta: 'mapa',

            // Administración de asignaciones
            rutaSeleccionadaAdmin: null,
            
            // Selección de ruta en tabla principal
            rutaSeleccionada: '',
            filaSeleccionada: null,
            
            // Control de event listeners
            eventListenerConfigurado: false,

            /** Actualización en vivo del tiempo en ejecución (tabla supervisor) */
            _tickCronometroSupervisor: null,

            /** Cronómetro en mapa/modal ver ruta (reclamos En ejecución con sesión de obra) */
            ahoraMsVisualizacionObra: Date.now(),
            intervalVisualizacionObra: null,
            mapboxObraVisualizacionRefs: [],
            mapboxObraRutasActivasRefs: [],
            rutaSeleccionadaVisualizarTodasId: null,

            userRole: window.USER_ROLE || '3',
            solapaRutas: 'activas',
            historialEjecuciones: [],
            historialEjecucionesCargando: false,
            historialEjecucionDetalle: null,
            historialDetalleCargando: false,

            /** Panel en tarjetas (supervisor) */
            vistaSupervisorPanel: 'grid',
            rutaDetalleSupervisorId: null,
            modoVistaDetalleSupervisor: 'mapa',
            reparacionPorReclamoIdSupervisor: {},
            materialesPorReclamoSupervisor: {},
            observacionesPorReclamoSupervisor: {},
            reclamoSupervisorModal: {},
            historialMaterialesSupervisor: [],
            historialObservacionesSupervisor: [],
            cargandoMaterialesSupervisor: false,
            cargandoObservacionesSupervisor: false,
            mapasPreviewSupervisor: {},
            reclamosCachePorRutaId: {},
            ahoraCronometroSupervisor: Date.now()
        };
    },

    computed: {
        esSupervisorVistaTarjetas() {
            return this.userRole === '2';
        },

        rutasActivasPanel() {
            return (this.rutas || []).filter((ruta) => {
                const estado = (ruta.estado_ejecucion || '').toString().trim().toLowerCase();
                return estado !== 'finalizada';
            });
        },

        contenedorMapaVisualizacionGoogle() {
            return this.esSupervisorVistaTarjetas && this.rutaDetalleSupervisorId
                ? 'mapaDetalleSupervisor'
                : 'mapaVerRuta';
        },

        contenedorMapaVisualizacionMapbox() {
            return this.esSupervisorVistaTarjetas && this.rutaDetalleSupervisorId
                ? 'mapaDetalleSupervisorMapbox'
                : 'mapaVerRutaMapbox';
        },

        puedeVerHistorialEjecuciones() {
            return this.userRole === '1' || this.userRole === '2';
        },

        puedeGenerarRuta() {
            return this.nuevaRuta.cantidadReclamos > 0 &&
                   this.reclamosDisponibles >= this.nuevaRuta.cantidadReclamos &&
                   this.vistaPrevia.activa &&
                   !!this.cuadrillaSeleccionadaCrearRuta;
        },

        nombreCuadrillaCrearRuta() {
            if (!this.cuadrillaSeleccionadaCrearRuta) {
                return '';
            }
            const c = this.cuadrillasDisponibles.find(
                (x) => String(x.id) === String(this.cuadrillaSeleccionadaCrearRuta)
            );
            return c ? c.nombre : '';
        },
        
        puedeVerVistaPrevia() {
            return this.nuevaRuta.cantidadReclamos > 0 &&
                   this.reclamosDisponibles >= this.nuevaRuta.cantidadReclamos &&
                   !this.vistaPrevia.activa; // Solo si no está activa aún
        }
    },

    watch: {
        solapaRutas(val) {
            if (val === 'historial' && this.puedeVerHistorialEjecuciones) {
                this.limpiarMapasPreviewSupervisor();
                if (this.esSupervisorVistaTarjetas) {
                    this.cerrarModalDetalleSupervisor();
                }
                this.cargarHistorialEjecuciones();
            }
            if (val === 'activas' && this.esSupervisorVistaTarjetas) {
                this.$nextTick(() => this.inicializarMapasPreviewSupervisor());
            }
        }
    },

    methods: {
        /**
         * Obtiene las rutas desde la API
         */
        async obtenerRutas() {
            try {
                const response = await axios.get(BASE_URL + 'api/rutas');
                this.rutas = response.data;
                this.reclamosCachePorRutaId = {};
                
                // Asegurarse de que las cuadrillas estén cargadas antes de inicializar la tabla
                if (this.cuadrillasDisponibles.length === 0) {
                    await this.obtenerCuadrillas();
                }
                
                this.$nextTick(() => {
                    if (this.esSupervisorVistaTarjetas) {
                        if (this.rutaDetalleSupervisorId) {
                            const ruta = this.rutas.find((r) => r.id == this.rutaDetalleSupervisorId);
                            if (ruta) {
                                this.recargarDatosDetalleSupervisor(ruta.id);
                            } else {
                                this.cerrarModalDetalleSupervisor();
                            }
                        } else {
                            this.inicializarMapasPreviewSupervisor();
                        }
                        this.iniciarCronometroSupervisorRutas();
                    } else {
                        this.inicializarTabla();
                    }
                });
            } catch (error) {
                console.error('Error al obtener rutas:', error);
                this.mostrarMensaje('Error al obtener las rutas', 'error');
            }
        },

        async cargarHistorialEjecuciones() {
            if (!this.puedeVerHistorialEjecuciones) {
                return;
            }
            this.historialEjecucionesCargando = true;
            try {
                const response = await axios.get(BASE_URL + 'api/rutas/ejecuciones/historial');
                this.historialEjecuciones = response.data || [];
            } catch (error) {
                console.error('Error al cargar historial de ejecuciones:', error);
                this.historialEjecuciones = [];
                this.mostrarMensaje('No se pudo cargar el historial de ejecuciones.', 'error');
            } finally {
                this.historialEjecucionesCargando = false;
            }
        },

        async abrirDetalleHistorialEjecucion(id) {
            if (!id) {
                return;
            }
            this.historialDetalleCargando = true;
            this.historialEjecucionDetalle = null;
            try {
                const response = await axios.get(BASE_URL + 'api/rutas/ejecuciones/' + id + '/detalle');
                this.historialEjecucionDetalle = response.data;
                this.$nextTick(() => {
                    const el = document.getElementById('modalHistorialEjecucion');
                    if (el && typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getOrCreateInstance(el).show();
                    }
                });
            } catch (error) {
                console.error('Error al cargar detalle de ejecución:', error);
                this.mostrarMensaje('No se pudo cargar el detalle del historial.', 'error');
            } finally {
                this.historialDetalleCargando = false;
            }
        },

        textoTipoEventoHistorial(tipo) {
            const mapa = {
                ejecucion_ruta_inicio: 'Inicio de la hoja de ruta',
                ejecucion_ruta_fin: 'Fin de la hoja de ruta',
                ejecucion_reclamo_inicio: 'Inicio de trabajo en reclamo',
                ejecucion_reclamo_fin: 'Fin de trabajo en reclamo',
                reclamo_cambio_estado: 'Cambio de estado del reclamo',
            };
            return mapa[tipo] || tipo;
        },

        detalleLegibleEventoHistorial(ev) {
            const md = ev && ev.metadata && typeof ev.metadata === 'object' ? ev.metadata : null;
            if (!md) {
                return '—';
            }
            if (md.estado_anterior != null && md.estado_nuevo != null) {
                return `"${md.estado_anterior}" → "${md.estado_nuevo}"`;
            }
            try {
                return JSON.stringify(md);
            } catch {
                return '—';
            }
        },

        etiquetaReclamoEventoHistorial(ev) {
            if (ev.reclamo_municipalidad_id) {
                return '#' + ev.reclamo_municipalidad_id;
            }
            if (ev.reclamo_id) {
                return 'ID interno ' + ev.reclamo_id;
            }
            return '—';
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

        formatearSegundosCronometroSupervisor(totalSeconds) {
            const pad = n => String(n).padStart(2, '0');
            const h = Math.floor(totalSeconds / 3600);
            const m = Math.floor((totalSeconds % 3600) / 60);
            const s = totalSeconds % 60;
            return `${pad(h)}:${pad(m)}:${pad(s)}`;
        },

        actualizarCronometrosTablaRutas() {
            const ahora = Date.now();
            document.querySelectorAll('#tabla_rutas .cronometro-ruta-supervisor').forEach((el) => {
                const raw = el.getAttribute('data-inicio-ejecucion-at');
                if (!raw) {
                    el.textContent = '—';
                    return;
                }
                const t0 = new Date(String(raw).replace(' ', 'T')).getTime();
                if (Number.isNaN(t0)) {
                    el.textContent = '—';
                    return;
                }
                const sec = Math.max(0, Math.floor((ahora - t0) / 1000));
                el.textContent = this.formatearSegundosCronometroSupervisor(sec);
            });
        },

        iniciarCronometroSupervisorRutas() {
            if (this._tickCronometroSupervisor) {
                return;
            }
            this._tickCronometroSupervisor = setInterval(() => {
                this.ahoraCronometroSupervisor = Date.now();
                this.actualizarCronometrosTablaRutas();
                this.refrescarCronometrosInfoWindowMapaSupervisor();
            }, 1000);
        },

        detenerCronometroSupervisorRutas() {
            if (this._tickCronometroSupervisor) {
                clearInterval(this._tickCronometroSupervisor);
                this._tickCronometroSupervisor = null;
            }
        },

        esEstadoEjecucionRuta(ruta) {
            if (!ruta) return false;
            const e = (ruta.estado_ejecucion || '').toString().trim().toLowerCase();
            return e === 'en ejecución' || e === 'en ejecucion';
        },

        /** Asignar o reasignar cuadrilla solo antes de iniciar ejecución */
        puedeAsignarOCambiarCuadrillaRuta(ruta) {
            if (!ruta) return false;
            const k = this.claveEstadoEjecucionRuta(ruta);
            return k === 'asignada' || k === 'sin asignar';
        },

        claveEstadoEjecucionRuta(ruta) {
            if (!ruta) return 'sin asignar';
            const fallback = Number(ruta.asignada) === 1 ? 'asignada' : 'sin asignar';
            const raw = (ruta.estado_ejecucion || fallback).toString().trim().toLowerCase();
            if (raw === 'en ejecución' || raw === 'en ejecucion') return 'en ejecución';
            if (raw === 'asignada') return 'asignada';
            if (raw === 'finalizada') return 'finalizada';
            return 'sin asignar';
        },

        textoEstadoEjecucionRuta(ruta) {
            const k = this.claveEstadoEjecucionRuta(ruta);
            if (k === 'en ejecución') return 'En ejecución';
            if (k === 'asignada') return 'Asignada';
            if (k === 'finalizada') return 'Finalizada';
            return 'Sin asignar';
        },

        textoSobreColorRuta(hex) {
            if (!hex || typeof hex !== 'string') return '#fff';
            let h = hex.trim().replace('#', '');
            if (h.length === 3) {
                h = h.split('').map((c) => c + c).join('');
            }
            if (h.length !== 6) return '#fff';
            const r = parseInt(h.slice(0, 2), 16);
            const g = parseInt(h.slice(2, 4), 16);
            const b = parseInt(h.slice(4, 6), 16);
            if ([r, g, b].some((n) => Number.isNaN(n))) return '#fff';
            const luminancia = 0.299 * r + 0.587 * g + 0.114 * b;
            return luminancia > 165 ? '#1a1a1a' : '#fff';
        },

        claseBadgeEstadoEjecucionRuta(ruta) {
            const k = this.claveEstadoEjecucionRuta(ruta);
            if (k === 'en ejecución') return 'bg-success';
            if (k === 'asignada') return 'bg-warning text-dark';
            if (k === 'finalizada') return 'bg-dark';
            return 'bg-secondary';
        },

        tiempoTranscurridoEjecucionSupervisor(ruta) {
            if (!this.esEstadoEjecucionRuta(ruta)) return '';
            const ini = ruta.inicio_ejecucion_at;
            if (!ini) return '—';
            const t0 = new Date(String(ini).replace(' ', 'T')).getTime();
            if (Number.isNaN(t0)) return '—';
            const sec = Math.max(0, Math.floor((this.ahoraCronometroSupervisor - t0) / 1000));
            return this.formatearSegundosCronometroSupervisor(sec);
        },

        limpiarMapasPreviewSupervisor() {
            Object.values(this.mapasPreviewSupervisor).forEach((ref) => {
                if (!ref) return;
                ref.markers?.forEach((m) => m.setMap(null));
                if (ref.directionsRenderer) {
                    ref.directionsRenderer.setMap(null);
                }
            });
            this.mapasPreviewSupervisor = {};
        },

        async obtenerReclamosRutaCache(rutaId) {
            if (this.reclamosCachePorRutaId[rutaId]) {
                return this.reclamosCachePorRutaId[rutaId];
            }
            const response = await axios.get(BASE_URL + 'api/rutas/' + rutaId + '/reclamos');
            const reclamos = response.data || [];
            this.reclamosCachePorRutaId[rutaId] = reclamos;
            return reclamos;
        },

        async esperarGoogleMaps(timeoutMs = 15000) {
            const inicio = Date.now();
            while (!(window.google && window.google.maps)) {
                if (Date.now() - inicio > timeoutMs) {
                    throw new Error('Timeout esperando Google Maps');
                }
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
        },

        async inicializarMapasPreviewSupervisor() {
            if (!this.esSupervisorVistaTarjetas || this.solapaRutas !== 'activas') {
                return;
            }
            this.limpiarMapasPreviewSupervisor();
            await this.$nextTick();
            try {
                await this.esperarGoogleMaps();
            } catch (error) {
                console.warn('Google Maps no disponible para vistas previas:', error);
                return;
            }
            for (const ruta of this.rutasActivasPanel) {
                if (this.solapaRutas !== 'activas' || this.rutaDetalleSupervisorId) {
                    break;
                }
                await this.cargarMapaPreviewSupervisor(ruta);
                await new Promise((resolve) => setTimeout(resolve, 80));
            }
        },

        async cargarMapaPreviewSupervisor(ruta) {
            const elId = 'mapaPreviewRuta-' + ruta.id;
            const el = document.getElementById(elId);
            if (!el || !window.google?.maps) {
                return;
            }

            try {
                const reclamos = await this.obtenerReclamosRutaCache(ruta.id);
                const colorRuta = ruta.color || '#FF6B35';
                const map = new google.maps.Map(el, {
                    center: { lat: -31.427, lng: -62.082 },
                    zoom: 13,
                    disableDefaultUI: true,
                    gestureHandling: 'none',
                    clickableIcons: false,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                    zoomControl: false,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    styles: [
                        {
                            featureType: 'poi',
                            elementType: 'labels',
                            stylers: [{ visibility: 'off' }]
                        }
                    ]
                });

                const markers = [];
                const bounds = new google.maps.LatLngBounds();
                const promesas = reclamos.map((reclamo) =>
                    this.obtenerCoordenadasReclamo(reclamo).then((coords) => ({ reclamo, coords }))
                );
                const resultados = await Promise.all(promesas);

                for (const { reclamo, coords } of resultados) {
                    if (!coords) continue;
                    const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                    const colorPrioridad = this.getColorPrioridad(reclamo.prioridad || 'Baja');
                    const marker = new google.maps.Marker({
                        position: { lat: coords.lat, lng: coords.lng },
                        map,
                        icon: this.crearIconoNumerado(reclamo.posicion, colorEstado, colorPrioridad, 26, reclamo.municipalidad_motivo),
                        zIndex: 100
                    });
                    marker._marcadorRecorridoPrincipal = true;
                    markers.push(marker);
                    bounds.extend({ lat: coords.lat, lng: coords.lng });
                }

                if (markers.length > 0) {
                    map.fitBounds(bounds, 20);
                }

                let directionsRenderer = null;
                const principales = markers.filter((m) => m._marcadorRecorridoPrincipal);
                if (principales.length >= 2) {
                    directionsRenderer = new google.maps.DirectionsRenderer({
                        suppressMarkers: true,
                        polylineOptions: {
                            strokeColor: colorRuta,
                            strokeOpacity: 0.95,
                            strokeWeight: 3
                        }
                    });
                    directionsRenderer.setMap(map);
                    await this.trazarRutaEnDirectionsRenderer(
                        directionsRenderer,
                        principales.map((m) => m.getPosition())
                    );
                }

                this.mapasPreviewSupervisor = {
                    ...this.mapasPreviewSupervisor,
                    [ruta.id]: { map, markers, directionsRenderer }
                };
            } catch (error) {
                console.error('Error al cargar vista previa de ruta', ruta.id, error);
            }
        },

        async trazarRutaEnDirectionsRenderer(directionsRenderer, coordenadas) {
            if (!coordenadas || coordenadas.length < 2) {
                return;
            }
            const directionsService = new google.maps.DirectionsService();
            const origin = coordenadas[0];
            const destination = coordenadas[coordenadas.length - 1];
            const waypoints = coordenadas.slice(1, -1).map((coord) => ({
                location: coord,
                stopover: true
            }));

            const request = {
                origin,
                destination,
                waypoints: coordenadas.length > 2 ? waypoints : [],
                travelMode: google.maps.TravelMode.DRIVING,
                optimizeWaypoints: false
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

        configurarModalDetalleSupervisor() {
            const elModal = document.getElementById('modalDetalleSupervisorRuta');
            if (!elModal || elModal.dataset.supervisorDetalleBound === '1') {
                return;
            }
            elModal.dataset.supervisorDetalleBound = '1';
            elModal.addEventListener('hidden.bs.modal', () => {
                this.finalizarCierreModalDetalleSupervisor();
            });
        },

        async recargarDatosDetalleSupervisor(rutaId) {
            if (!rutaId) {
                return;
            }
            try {
                const responseRuta = await axios.get(BASE_URL + 'api/rutas/' + rutaId);
                this.rutaVisualizando = responseRuta.data;

                const responseReclamos = await axios.get(BASE_URL + 'api/rutas/' + rutaId + '/reclamos');
                const reclamosUnicos = responseReclamos.data.filter((reclamo, index, self) =>
                    index === self.findIndex((r) => r.id === reclamo.id)
                );
                this.reclamosRutaVisualizando = reclamosUnicos;
                this.reclamosCachePorRutaId[rutaId] = reclamosUnicos;
                this.aplicarSesionesReparacionSupervisor(reclamosUnicos);
                await this.cargarMaterialesYObservacionesDetalleSupervisor(reclamosUnicos);

                const elModal = document.getElementById('modalDetalleSupervisorRuta');
                if (elModal?.classList.contains('show') && this.modoVistaDetalleSupervisor === 'mapa') {
                    await this.$nextTick();
                    await new Promise((resolve) => setTimeout(resolve, 200));
                    await this.restaurarMapaDetalleSupervisor();
                }
            } catch (error) {
                console.error('Error al recargar detalle de ruta:', error);
                throw error;
            }
        },

        async abrirDetalleSupervisor(ruta) {
            if (!ruta?.id) {
                return;
            }
            this.rutaDetalleSupervisorId = ruta.id;
            this.modoVistaDetalleSupervisor = 'mapa';
            this.reparacionPorReclamoIdSupervisor = {};
            this.materialesPorReclamoSupervisor = {};
            this.observacionesPorReclamoSupervisor = {};
            this.reclamosRutaVisualizando = [];
            this.rutaVisualizando = {};

            try {
                await this.recargarDatosDetalleSupervisor(ruta.id);

                const elModal = document.getElementById('modalDetalleSupervisorRuta');
                let modal = bootstrap.Modal.getInstance(elModal);
                if (!modal) {
                    modal = new bootstrap.Modal(elModal, { backdrop: true, keyboard: true });
                }

                await this.$nextTick();
                modal.show();

                await new Promise((resolve) => setTimeout(resolve, 350));
                if (this.modoVistaDetalleSupervisor === 'mapa') {
                    await this.restaurarMapaDetalleSupervisor();
                }
                this.$nextTick(() => {
                    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                    popoverTriggerList.map((popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl));
                });
            } catch (error) {
                console.error('Error al abrir detalle de ruta:', error);
                this.mostrarMensaje('Error al cargar la hoja de ruta', 'error');
                this.cerrarModalDetalleSupervisor();
            }
        },

        cambiarModoVistaDetalleSupervisor(modo) {
            this.modoVistaDetalleSupervisor = modo;
            if (modo === 'mapa') {
                this.$nextTick(() => this.restaurarMapaDetalleSupervisor());
            }
        },

        async restaurarMapaDetalleSupervisor() {
            if (!this.rutaDetalleSupervisorId || this.modoVistaDetalleSupervisor !== 'mapa') {
                return;
            }

            await this.$nextTick();
            await new Promise((resolve) => setTimeout(resolve, 200));

            if (this.proveedorMapaVisualizacion === 'mapbox') {
                if (this.mapaVisualizacionMapbox) {
                    this.mapaVisualizacionMapbox.resize();
                } else {
                    await this.inicializarMapaVisualizacionMapbox();
                    await this.mostrarRutaEnMapaMapbox();
                }
                return;
            }

            if (this.mapaVisualizacion) {
                google.maps.event.trigger(this.mapaVisualizacion, 'resize');
                return;
            }

            await this.inicializarMapaVisualizacion();
        },

        async seleccionarReclamoDetalleSupervisor(reclamo) {
            if (this.modoVistaDetalleSupervisor !== 'mapa') {
                this.cambiarModoVistaDetalleSupervisor('mapa');
                await this.$nextTick();
                await new Promise((resolve) => setTimeout(resolve, 350));
            }
            this.centrarEnReclamo(reclamo);
        },

        aplicarSesionesReparacionSupervisor(reclamos) {
            if (!reclamos?.length) {
                this.reparacionPorReclamoIdSupervisor = {};
                return;
            }
            const primera = reclamos[0];
            if (!Object.prototype.hasOwnProperty.call(primera, 'sesion_reparacion')) {
                this.reparacionPorReclamoIdSupervisor = {};
                return;
            }
            const m = {};
            for (const r of reclamos) {
                if ((r.municipalidad_estado || '').trim() === 'Completado') {
                    continue;
                }
                const sr = r.sesion_reparacion;
                if (!sr) {
                    continue;
                }
                const acum = Number(sr.acumulado_ms) || 0;
                const activo = !!sr.activo;
                if (!activo && acum <= 0) {
                    if ((r.municipalidad_estado || '').trim() !== 'Pendiente') {
                        continue;
                    }
                }
                let inicioMs = this.ahoraCronometroSupervisor;
                if (activo && sr.inicio_segmento_at) {
                    const t = new Date(String(sr.inicio_segmento_at).replace(' ', 'T')).getTime();
                    if (!Number.isNaN(t)) {
                        inicioMs = t;
                    }
                }
                m[r.id] = {
                    activo,
                    inicioSegmentoMs: inicioMs,
                    acumuladoMs: acum
                };
            }
            this.reparacionPorReclamoIdSupervisor = m;
        },

        sesionReparacionReclamoSupervisor(reclamo) {
            if (!reclamo || reclamo.id == null) {
                return null;
            }
            return this.reparacionPorReclamoIdSupervisor[reclamo.id] || null;
        },

        textoCronometroReparacionReclamoSupervisor(reclamo) {
            const s = this.sesionReparacionReclamoSupervisor(reclamo);
            if (!s) {
                return '';
            }
            let ms = s.acumuladoMs || 0;
            if (s.activo) {
                ms += this.ahoraCronometroSupervisor - s.inicioSegmentoMs;
            }
            const sec = Math.max(0, Math.floor(ms / 1000));
            return this.formatearSegundosCronometroSupervisor(sec);
        },

        claseBadgeEstadoReclamoSupervisor(estado) {
            const e = (estado || '').trim();
            if (e === 'Recibido') return 'bg-secondary';
            if (e === 'Asignado') return 'bg-info text-dark';
            if (e === 'Pendiente') return 'bg-danger';
            if (e === 'En ejecución') return 'bg-warning text-dark';
            if (e === 'Completado') return 'bg-success';
            if (e === 'En plan') return 'bg-secondary';
            if (e === 'Error de datos') return 'bg-secondary';
            return 'bg-secondary';
        },

        materialesReclamoSupervisorLista(reclamo) {
            if (!reclamo?.id) {
                return [];
            }
            return this.materialesPorReclamoSupervisor[reclamo.id] || [];
        },

        observacionesReclamoSupervisorLista(reclamo) {
            if (!reclamo?.id) {
                return [];
            }
            return this.observacionesPorReclamoSupervisor[reclamo.id] || [];
        },

        async cargarMaterialesYObservacionesDetalleSupervisor(reclamos) {
            this.materialesPorReclamoSupervisor = {};
            this.observacionesPorReclamoSupervisor = {};
            if (!reclamos?.length) {
                return;
            }
            const ejecId = this.rutaVisualizando?.ruta_ejecucion_activa_id;
            const materialesMap = {};
            const observacionesMap = {};
            await Promise.all(reclamos.map(async (reclamo) => {
                if (!reclamo?.id) {
                    return;
                }
                try {
                    const peticiones = [
                        axios.get(BASE_URL + 'api/reclamos/' + reclamo.id + '/materiales')
                    ];
                    if (ejecId) {
                        peticiones.push(
                            axios.get(
                                BASE_URL + 'api/reclamos/' + reclamo.id + '/ejecucion-observaciones',
                                { params: { ruta_ejecucion_id: ejecId } }
                            )
                        );
                    }
                    const resultados = await Promise.all(peticiones);
                    materialesMap[reclamo.id] = resultados[0]?.data || [];
                    observacionesMap[reclamo.id] = ejecId ? (resultados[1]?.data || []) : [];
                } catch (error) {
                    console.warn('No se pudieron cargar materiales/observaciones del reclamo', reclamo.id, error);
                    materialesMap[reclamo.id] = [];
                    observacionesMap[reclamo.id] = [];
                }
            }));
            this.materialesPorReclamoSupervisor = materialesMap;
            this.observacionesPorReclamoSupervisor = observacionesMap;
        },

        puedeVerAccionesObraSupervisorEnReclamo(reclamo) {
            if (!this.rutaDetalleSupervisorId || !reclamo) {
                return false;
            }
            if (this.sesionReparacionReclamoSupervisor(reclamo)) {
                return true;
            }
            if (!this.rutaModalEnEjecucionVisualizacion()) {
                return false;
            }
            const est = (reclamo.municipalidad_estado || '').trim();
            return est === 'En ejecución' || est === 'Pendiente';
        },

        abrirModalMaterialesSupervisor(reclamo) {
            if (!reclamo?.id) {
                return;
            }
            this.reclamoSupervisorModal = { ...reclamo };
            this.historialMaterialesSupervisor = [];
            const elModal = document.getElementById('modalMaterialesSupervisor');
            const modal = bootstrap.Modal.getOrCreateInstance(elModal);
            modal.show();
            this.cargarHistorialMaterialesSupervisor();
        },

        async cargarHistorialMaterialesSupervisor() {
            if (!this.reclamoSupervisorModal?.id) {
                return;
            }
            this.cargandoMaterialesSupervisor = true;
            try {
                const r = await axios.get(
                    BASE_URL + 'api/reclamos/' + this.reclamoSupervisorModal.id + '/materiales'
                );
                this.historialMaterialesSupervisor = Array.isArray(r.data) ? r.data : [];
                this.materialesPorReclamoSupervisor = {
                    ...this.materialesPorReclamoSupervisor,
                    [this.reclamoSupervisorModal.id]: this.historialMaterialesSupervisor
                };
            } catch (error) {
                console.error('Error al cargar materiales (supervisor):', error);
                this.mostrarMensaje('No se pudo cargar el historial de materiales.', 'error');
                this.historialMaterialesSupervisor = [];
            } finally {
                this.cargandoMaterialesSupervisor = false;
            }
        },

        abrirModalObservacionesSupervisor(reclamo) {
            if (!reclamo?.id) {
                return;
            }
            const ejecId = this.rutaVisualizando?.ruta_ejecucion_activa_id;
            if (!ejecId) {
                this.mostrarMensaje('No hay ejecución activa de la hoja para consultar observaciones.', 'warning');
                return;
            }
            this.reclamoSupervisorModal = { ...reclamo };
            this.historialObservacionesSupervisor = [];
            const elModal = document.getElementById('modalObservacionesSupervisor');
            const modal = bootstrap.Modal.getOrCreateInstance(elModal);
            modal.show();
            this.cargarHistorialObservacionesSupervisor();
        },

        async cargarHistorialObservacionesSupervisor() {
            const ejecId = this.rutaVisualizando?.ruta_ejecucion_activa_id;
            if (!this.reclamoSupervisorModal?.id || !ejecId) {
                return;
            }
            this.cargandoObservacionesSupervisor = true;
            try {
                const r = await axios.get(
                    BASE_URL + 'api/reclamos/' + this.reclamoSupervisorModal.id + '/ejecucion-observaciones',
                    { params: { ruta_ejecucion_id: ejecId } }
                );
                this.historialObservacionesSupervisor = Array.isArray(r.data) ? r.data : [];
                this.observacionesPorReclamoSupervisor = {
                    ...this.observacionesPorReclamoSupervisor,
                    [this.reclamoSupervisorModal.id]: this.historialObservacionesSupervisor
                };
            } catch (error) {
                console.error('Error al cargar observaciones (supervisor):', error);
                this.mostrarMensaje('No se pudo cargar el historial de observaciones.', 'error');
                this.historialObservacionesSupervisor = [];
            } finally {
                this.cargandoObservacionesSupervisor = false;
            }
        },

        construirInfoWindowContentMapaDetalleSupervisor(reclamo) {
            const wrap = document.createElement('div');
            wrap.className = 'map-detalle-iw';
            wrap.innerHTML = this.crearContenidoInfoWindow(reclamo);

            const acciones = document.createElement('div');
            acciones.className = 'map-detalle-iw-acciones border-top pt-2 mt-2 d-flex flex-wrap align-items-center gap-1';

            const rid = String(reclamo.id);
            const ses = this.sesionReparacionReclamoSupervisor(reclamo);

            if (ses) {
                const crono = document.createElement('span');
                crono.className = 'badge bg-dark font-monospace map-detalle-iw-crono';
                crono.setAttribute('data-map-iw-crono-supervisor-id', rid);
                crono.textContent = this.textoCronometroReparacionReclamoSupervisor(reclamo);
                crono.title = 'Tiempo en obra';
                acciones.appendChild(crono);
            }

            if (this.puedeVerAccionesObraSupervisorEnReclamo(reclamo)) {
                const bMat = document.createElement('button');
                bMat.type = 'button';
                bMat.className = 'btn btn-sm btn-outline-secondary';
                bMat.innerHTML = '<i class="bi bi-box-seam"></i>';
                bMat.title = 'Materiales utilizados';
                bMat.setAttribute('data-map-accion-supervisor', 'materiales');
                bMat.setAttribute('data-reclamo-id', rid);
                acciones.appendChild(bMat);

                const bObs = document.createElement('button');
                bObs.type = 'button';
                bObs.className = 'btn btn-sm btn-outline-secondary';
                bObs.innerHTML = '<i class="bi bi-chat-square-text"></i>';
                bObs.title = 'Observaciones en esta ejecución';
                bObs.setAttribute('data-map-accion-supervisor', 'observaciones');
                bObs.setAttribute('data-reclamo-id', rid);
                acciones.appendChild(bObs);
            }

            if (acciones.childNodes.length) {
                wrap.appendChild(acciones);
            }

            wrap.addEventListener('click', (e) => this.onMapaDetalleSupervisorInfoWindowAccion(e));

            return wrap;
        },

        onMapaDetalleSupervisorInfoWindowAccion(e) {
            const btn = e.target.closest('[data-map-accion-supervisor]');
            if (!btn) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            const rid = parseInt(btn.getAttribute('data-reclamo-id'), 10);
            const accion = btn.getAttribute('data-map-accion-supervisor');
            const r = this.reclamosRutaVisualizando.find((x) => Number(x.id) === rid);
            if (!r) {
                return;
            }
            if (accion === 'materiales') {
                this.abrirModalMaterialesSupervisor(r);
                return;
            }
            if (accion === 'observaciones') {
                this.abrirModalObservacionesSupervisor(r);
            }
        },

        refrescarCronometrosInfoWindowMapaSupervisor() {
            if (!this.rutaDetalleSupervisorId) {
                return;
            }
            document.querySelectorAll('[data-map-iw-crono-supervisor-id]').forEach((el) => {
                const rid = parseInt(el.getAttribute('data-map-iw-crono-supervisor-id'), 10);
                if (Number.isNaN(rid)) {
                    return;
                }
                const r = this.reclamosRutaVisualizando.find((x) => Number(x.id) === rid);
                if (!r || !this.sesionReparacionReclamoSupervisor(r)) {
                    el.textContent = '—';
                    return;
                }
                el.textContent = this.textoCronometroReparacionReclamoSupervisor(r);
            });
        },

        cerrarModalDetalleSupervisor() {
            const elModal = document.getElementById('modalDetalleSupervisorRuta');
            const modal = elModal ? bootstrap.Modal.getInstance(elModal) : null;
            if (modal) {
                modal.hide();
            } else {
                this.finalizarCierreModalDetalleSupervisor();
            }
        },

        finalizarCierreModalDetalleSupervisor() {
            this.cerrarVisualizacion();
            this.rutaDetalleSupervisorId = null;
            this.modoVistaDetalleSupervisor = 'mapa';
            this.reparacionPorReclamoIdSupervisor = {};
            this.materialesPorReclamoSupervisor = {};
            this.observacionesPorReclamoSupervisor = {};
            this.$nextTick(() => {
                if (this.esSupervisorVistaTarjetas && this.solapaRutas === 'activas') {
                    this.inicializarMapasPreviewSupervisor();
                }
            });
        },

        volverPanelSupervisor() {
            this.cerrarModalDetalleSupervisor();
        },

        /**
         * Inicializa la tabla de rutas
         */
        inicializarTabla() {
            if (this.esSupervisorVistaTarjetas) {
                return;
            }
            if (this.tabla) {
                this.tabla.destroy();
            }

            // Guardar referencia al componente Vue para usar en las funciones render
            const vueComponent = this;
            console.log('Inicializando tabla con', this.rutas.length, 'rutas y', this.cuadrillasDisponibles.length, 'cuadrillas');

            // Configurar event listener para botones de asignación (solo una vez)
            if (!this.eventListenerConfigurado) {
                $('#tabla_rutas tbody').on('click', '.asignacion-btn-moderno', async function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    
                    const button = $(this);
                    const rutaId = button.data('ruta-id');
                    const rutaNombre = button.data('ruta-nombre');
                    const cuadrillaActual = button.data('cuadrilla-actual');
                    const cuadrillaIdActual = button.data('cuadrilla-id');
                    
                    console.log('Click en botón de asignación:', { rutaId, rutaNombre, cuadrillaActual, cuadrillaIdActual });
                    
                    // Abrir popup de asignación
                    await vueComponent.abrirPopupAsignacion(rutaId, rutaNombre, cuadrillaActual, cuadrillaIdActual, button[0]);
                });
                this.eventListenerConfigurado = true;
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
                                <div class="d-flex align-items-center gap-2" style="cursor: pointer;" title="Click para ver en el mapa">
                                    <div style="width: 20px; height: 20px; border-radius: 50%; background-color: ${color}; border: 2px solid #dee2e6; flex-shrink: 0;"></div>
                                    <span class="text-primary fw-bold">${nombre}</span>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'cantidadReclamos',
                        className: 'text-start'
                    },
                    {
                        data: 'estado_ejecucion',
                        className: 'text-start',
                        render: (data, type, row) => {
                            let estado = (data || '').toString().trim().toLowerCase();
                            if (!estado) {
                                estado = row.asignada == 1 ? 'asignada' : 'sin asignar';
                            }

                            if (type && type !== 'display') {
                                if (estado === 'en ejecución' || estado === 'en ejecucion') {
                                    return 'en ejecución';
                                }
                                if (estado === 'asignada') {
                                    return 'asignada';
                                }
                                return 'sin asignar';
                            }

                            if (estado === 'en ejecución' || estado === 'en ejecucion') {
                                const inicio = row.inicio_ejecucion_at || '';
                                const escAttr = (s) => String(s ?? '').replace(/\\/g, '\\\\').replace(/"/g, '&quot;');
                                let initial = '—';
                                if (inicio) {
                                    const t0 = new Date(String(inicio).replace(' ', 'T')).getTime();
                                    if (!Number.isNaN(t0)) {
                                        const sec = Math.max(0, Math.floor((Date.now() - t0) / 1000));
                                        initial = vueComponent.formatearSegundosCronometroSupervisor(sec);
                                    }
                                }
                                return `
                                    <span class="d-inline-flex align-items-center gap-1 flex-wrap">
                                        <span class="badge bg-success" style="font-size: 0.75rem;">
                                            <i class="bi bi-play-circle-fill text-white me-1"></i>En ejecución
                                        </span>
                                        <span class="badge bg-dark cronometro-ruta-supervisor font-monospace" style="font-size: 0.75rem; letter-spacing: 0.06em;" data-inicio-ejecucion-at="${escAttr(inicio)}">${initial}</span>
                                    </span>
                                `;
                            }

                            if (estado === 'asignada') {
                                return `
                                    <span class="badge bg-warning text-dark" style="font-size: 0.75rem;">
                                        <i class="bi bi-hourglass-split me-1"></i>Asignada
                                    </span>
                                `;
                            }

                            return `
                                <span class="badge bg-secondary" style="font-size: 0.75rem;">
                                    <i class="bi bi-dash-circle me-1"></i>Sin asignar
                                </span>
                            `;
                        }
                    },
                    {
                        data: 'asignada',
                        className: 'text-start',
                        render: (data, type, row) => {
                            const cuadrillaId = row.cuadrilla_id || '';
                            const cuadrillaNombre = row.cuadrilla_nombre || '';
                            const isAsignada = data == 1 && cuadrillaNombre;
                            const escHtml = (s) => String(s ?? '')
                                .replace(/&/g, '&amp;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/"/g, '&quot;');
                            const escAttr = (s) => String(s ?? '').replace(/\\/g, '\\\\').replace(/"/g, '&quot;');
                            
                            // Determinar el estilo basado en el estado
                            const buttonStyle = isAsignada 
                                ? `background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                                   color: white;
                                   border: none;
                                   box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);`
                                : `background: linear-gradient(135deg, #A8A8C5 0%, #6c757d 100%);
                                   color: white;
                                   border: none;
                                   box-shadow: 0 3px 10px rgba(168, 168, 197, 0.3);`;
                            
                            // Icono y texto basado en el estado (asignada: mostrar nombre de la cuadrilla)
                            const icono = isAsignada ? '<i class="bi bi-people-fill"></i>' : '<i class="bi bi-person-plus-fill"></i>';
                            const texto = isAsignada ? escHtml(cuadrillaNombre) : 'Sin asignar';

                            if (!vueComponent.puedeAsignarOCambiarCuadrillaRuta(row)) {
                                return `
                                    <span class="badge ${isAsignada ? 'bg-warning text-dark' : 'bg-secondary'}"
                                          style="font-size: 0.75rem; max-width: 11rem; overflow: hidden; text-overflow: ellipsis;"
                                          title="${isAsignada ? escHtml(cuadrillaNombre) : 'Sin asignar'}">
                                        ${isAsignada ? `<i class="bi bi-people-fill me-1"></i>${texto}` : texto}
                                    </span>
                                `;
                            }
                            
                            return `
                                <div class="asignacion-selector-container">
                                    <button type="button" 
                                            class="btn btn-sm asignacion-btn-moderno" 
                                            data-ruta-id="${row.id}"
                                            data-ruta-nombre="${(row.nombre || 'Sin nombre').replace(/"/g, '&quot;')}"
                                            data-cuadrilla-actual="${escAttr(cuadrillaNombre)}"
                                            data-cuadrilla-id="${cuadrillaId}"
                                            style="${buttonStyle}
                                                   padding: 0.5rem 1rem;
                                                   border-radius: 12px;
                                                   font-weight: 600;
                                                   font-size: 0.75rem;
                                                   transition: all 0.3s ease;
                                                   display: flex;
                                                   align-items: center;
                                                   gap: 0.4rem;
                                                   white-space: nowrap;
                                                   text-transform: uppercase;
                                                   letter-spacing: 0.3px;
                                                   position: relative;
                                                   overflow: hidden;
                                                   cursor: pointer;"
                                            title="${isAsignada ? escHtml(cuadrillaNombre) : 'Asignar a cuadrilla'}"
                                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(6, 4, 75, 0.4)';"
                                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='${isAsignada ? '0 3px 10px rgba(40, 167, 69, 0.3)' : '0 3px 10px rgba(58, 57, 114, 0.3)'}';">
                                        ${icono}
                                        <span style="font-size: 0.7rem; text-transform: none; letter-spacing: normal; max-width: 11rem; overflow: hidden; text-overflow: ellipsis;">${texto}</span>
                                        <i class="bi bi-chevron-down" style="font-size: 0.7rem; margin-left: 0.2rem;"></i>
                                    </button>
                                </div>
                            `;
                        }
                    },
                ],
                order: [[0, 'asc']]
            });

            // Configurar eventos para clic en fila
            $('#tabla_rutas tbody').off('click', 'tr').on('click', 'tr', (e) => {
                // Evitar acción si se hace clic en un botón o en el selector de asignación
                if ($(e.target).closest('button').length > 0 || 
                    $(e.target).closest('.asignacion-selector-container').length > 0 ||
                    $(e.target).hasClass('asignacion-btn-moderno')) {
                    return;
                }
                
                const row = this.tabla.row(e.currentTarget);
                const data = row.data();
                if (data) {
                    // Si se hace click en el nombre (primera columna), abrir modal de visualización
                    if ($(e.target).closest('td').index() === 0 || $(e.target).hasClass('text-primary')) {
                        this.verRuta(data.id);
                    }
                }
            });

            this.$nextTick(() => {
                this.actualizarCronometrosTablaRutas();
                this.iniciarCronometroSupervisorRutas();
            });
        },

        /**
         * Abre el modal para crear una nueva ruta automática
         */
        async abrirModalCrearRuta() {
            // Resetear datos
            this.resetearModal();
            if (this.cuadrillasDisponibles.length === 0) {
                await this.obtenerCuadrillas();
            }

            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalCrearRuta'));
            modal.show();
        },

        /**
         * Resetea todos los datos del modal
         */
        resetearModal() {
            this.nuevaRuta = {
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
            this.cuadrillaSeleccionadaCrearRuta = '';
            this.modoVistaCrearRuta = 'mapa';
            this.limpiarVistaPrevia();
        },

        rutaEstaActivaNoFinalizada(ruta) {
            if (!ruta || !ruta.cuadrilla_id) {
                return false;
            }
            const estado = (ruta.estado_ejecucion || '').toString().trim().toLowerCase();
            if (estado === 'finalizada') {
                return false;
            }
            return Number(ruta.asignada) === 1 || !!ruta.cuadrilla_id;
        },

        hojaActivaDeCuadrilla(cuadrillaId, excluirRutaId = null) {
            if (!cuadrillaId) {
                return null;
            }
            return this.rutas.find((r) => {
                if (excluirRutaId != null && String(r.id) === String(excluirRutaId)) {
                    return false;
                }
                if (String(r.cuadrilla_id) !== String(cuadrillaId)) {
                    return false;
                }
                return this.rutaEstaActivaNoFinalizada(r);
            }) || null;
        },

        cuadrillaTieneOtraHojaAsignada(cuadrillaId, excluirRutaId = null) {
            return !!this.hojaActivaDeCuadrilla(cuadrillaId, excluirRutaId);
        },

        mensajeCuadrillaOcupada(cuadrillaId, excluirRutaId = null) {
            const otra = this.hojaActivaDeCuadrilla(cuadrillaId, excluirRutaId);
            if (!otra) {
                return '';
            }
            const nombreHoja = otra.nombre || `Hoja #${otra.id}`;
            return `La cuadrilla ya tiene asignada la hoja de ruta "${nombreHoja}". Desasígnela antes de asignar otra.`;
        },

        extraerMensajeErrorApi(error) {
            const data = error?.response?.data;
            if (!data) {
                return error?.message || 'Error desconocido';
            }
            if (data.messages) {
                const m = data.messages;
                if (typeof m === 'string') {
                    return m;
                }
                if (m.error) {
                    return m.error;
                }
                if (Array.isArray(m)) {
                    return m.join(' ');
                }
                return Object.values(m).flat().join(' ');
            }
            return data.message || error?.message || 'Error desconocido';
        },

        seleccionarCuadrillaCrearRuta(cuadrillaId) {
            const msg = this.mensajeCuadrillaOcupada(cuadrillaId);
            if (msg) {
                this.mostrarMensaje(msg, 'warning');
                return;
            }
            this.cuadrillaSeleccionadaCrearRuta = cuadrillaId;
        },

        mapaCrearRutaNecesitaReinicio() {
            if (this.proveedorMapaVistaPrevia === 'mapbox') {
                return !this.mapaMapbox;
            }
            const el = document.getElementById('mapaCrearRuta');
            if (!el) {
                return true;
            }
            if (!this.mapa) {
                return true;
            }
            try {
                return this.mapa.getDiv() !== el;
            } catch {
                return true;
            }
        },

        async restaurarMapaVistaPreviaCrearRuta() {
            if (!this.vistaPrevia.activa) {
                return;
            }

            await this.$nextTick();
            await new Promise((resolve) => setTimeout(resolve, 200));

            if (this.proveedorMapaVistaPrevia === 'mapbox') {
                if (this.mapaCrearRutaNecesitaReinicio()) {
                    if (this.mapaMapbox) {
                        this.mapaMapbox.remove();
                        this.mapaMapbox = null;
                    }
                    await this.inicializarMapaMapbox();
                } else {
                    this.mapaMapbox.resize();
                }
                await this.mostrarVistaPreviaEnMapaMapbox();
                return;
            }

            if (this.mapaCrearRutaNecesitaReinicio()) {
                this.mapa = null;
                await this.inicializarMapa();
            } else if (this.mapa) {
                google.maps.event.trigger(this.mapa, 'resize');
            }

            await this.mostrarVistaPreviaEnMapa();
        },

        cambiarModoVistaCrearRuta(modo) {
            this.modoVistaCrearRuta = modo;
            if (modo === 'mapa' && this.vistaPrevia.activa) {
                this.restaurarMapaVistaPreviaCrearRuta();
            }
        },

        getCardClassCrearRuta(reclamo) {
            const estado = reclamo?.municipalidad_estado;
            if (estado === 'Recibido') return 'border-secondary';
            if (estado === 'Asignado') return 'border-info';
            if (estado === 'Pendiente') return 'border-danger';
            if (estado === 'En ejecución') return 'border-warning';
            if (estado === 'Completado') return 'border-success';
            if (estado === 'En plan') return 'border-secondary';
            if (estado === 'Error de datos') return 'border-secondary';
            return 'border-secondary';
        },

        /**
         * Vuelve a la configuración inicial (limpia vista previa)
         */
        volverAConfigurar() {
            this.limpiarVistaPrevia();
            this.vistaPrevia.activa = false;
            this.modoEdicion = false;
            this.rutaOriginal = [];
            this.modoVistaCrearRuta = 'mapa';
        },

        /**
         * Activa el modo de edición de la ruta
         */
        activarModoEdicion() {
            this.modoEdicion = true;
            // Guardar copia de la ruta original por si cancela
            this.rutaOriginal = JSON.parse(JSON.stringify(this.vistaPrevia.rutaOptimizada));
            this.mostrarMensaje('Modo edición activado. Use el mapa o la vista lista para ajustar los reclamos.', 'info');
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
                this.modoVistaCrearRuta = 'mapa';
                
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
                            icon: this.crearIconoPinMotivo(colorEstado, colorPrioridad, reclamo.municipalidad_motivo)
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
                        icon: this.crearIconoNumerado(i + 1, colorEstado, colorPrioridad, null, reclamo.municipalidad_motivo),
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
                                    icon: this.crearIconoNumerado(reclamo.posicion, '#909090', null, 24, reclamo.municipalidad_motivo),
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
        crearIconoNumerado(numero, colorEstado, colorPrioridad, tamanoPersonalizado = null, motivo = null) {
            const tienePrioridadAlta = colorPrioridad !== null && !tamanoPersonalizado;

            if (tamanoPersonalizado) {
                const size = tamanoPersonalizado;
                const half = size / 2;
                const r = Math.max(8, Math.floor(size * 0.42));
                const fontSize = Math.max(8, Math.floor(size * 0.38));
                return {
                    url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                        <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="${half}" cy="${half}" r="${r}" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="1.5"/>
                            <text x="${half}" y="${half + fontSize * 0.35}" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="${fontSize}" font-weight="bold">${numero}</text>
                            ${this.crearSvgBadgeMotivo(motivo, size - 6, 6, Math.max(4.5, size * 0.17), Math.max(7, size * 0.26))}
                        </svg>
                    `)}`,
                    scaledSize: new google.maps.Size(size, size),
                    anchor: new google.maps.Point(half, half)
                };
            }
            
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
                            ${this.crearSvgBadgeMotivo(motivo, 31, 9, 7, 10)}
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
                            ${this.crearSvgBadgeMotivo(motivo, 25, 7, 6, 9)}
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
                if (!this.vistaPrevia.activa) {
                    this.mostrarMensaje('Debe ver la vista previa antes de crear la ruta', 'warning');
                } else if (!this.cuadrillaSeleccionadaCrearRuta) {
                    this.mostrarMensaje('Debe seleccionar una cuadrilla para asignar la hoja de ruta', 'warning');
                } else {
                    this.mostrarMensaje('Complete los datos requeridos antes de crear la ruta', 'warning');
                }
                return;
            }

            // Validar que haya al menos un reclamo en la ruta
            if (this.vistaPrevia.rutaOptimizada.length === 0) {
                this.mostrarMensaje('La ruta debe tener al menos un reclamo', 'warning');
                return;
            }

            try {
                let datosRuta;
                
                const cuadrillaId = parseInt(this.cuadrillaSeleccionadaCrearRuta, 10);
                const msgOcupadaCrear = this.mensajeCuadrillaOcupada(cuadrillaId);
                if (msgOcupadaCrear) {
                    this.mostrarMensaje(msgOcupadaCrear, 'warning');
                    return;
                }

                if (this.modoEdicion) {
                    // Si está en modo edición, enviar la ruta editada manualmente
                    datosRuta = {
                        color: this.nuevaRuta.color,
                        cantidadReclamos: this.vistaPrevia.rutaOptimizada.length,
                        reclamosManuales: this.vistaPrevia.rutaOptimizada.map(r => r.id),
                        primerReclamoManual: null,
                        modoManual: true,
                        cuadrilla_id: cuadrillaId
                    };
                } else {
                    // Ruta automática normal
                    datosRuta = {
                        color: this.nuevaRuta.color,
                        cantidadReclamos: parseInt(this.nuevaRuta.cantidadReclamos),
                        reclamosManuales: [],
                        primerReclamoManual: null,
                        modoManual: false,
                        cuadrilla_id: cuadrillaId
                    };
                }

                this.mostrarMensaje(this.modoEdicion ? 'Creando hoja de ruta editada...' : 'Creando hoja de ruta automática...', 'info');

                const response = await axios.post(BASE_URL + 'api/rutas/generar', datosRuta);
                const rutaCreada = response.data?.ruta;

                if (rutaCreada?.id && cuadrillaId) {
                    await axios.post(BASE_URL + 'api/rutas/asignar', {
                        ruta_id: rutaCreada.id,
                        cuadrilla_id: cuadrillaId
                    });
                }

                this.mostrarMensaje('Hoja de ruta creada y asignada exitosamente', 'success');
                
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
                this.mostrarMensaje(this.extraerMensajeErrorApi(error), 'error');
            }
        },

        /**
         * Ver una ruta en el modal
         */
        async verRuta(id) {
            try {
                this.detenerTickerVisualizacionObra();
                this.mapboxObraVisualizacionRefs = [];
                // Limpiar datos anteriores
                this.reclamosRutaVisualizando = [];
                this.rutaVisualizando = {};
                
                // Obtener datos de la ruta
                const responseRuta = await axios.get(BASE_URL + 'api/rutas/' + id);
                this.rutaVisualizando = responseRuta.data;
                
                // Obtener reclamos de la ruta
                const responseReclamos = await axios.get(BASE_URL + 'api/rutas/' + id + '/reclamos');
                // Filtrar duplicados por ID para evitar reclamos repetidos
                const reclamosUnicos = responseReclamos.data.filter((reclamo, index, self) => 
                    index === self.findIndex(r => r.id === reclamo.id)
                );
                this.reclamosRutaVisualizando = reclamosUnicos;
                
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
            const containerId = this.contenedorMapaVisualizacionGoogle;
            const el = document.getElementById(containerId);
            if (!el) {
                return;
            }
            try {
                const lat = -31.427;
                const lng = -62.082;
                const esMapaDetalleSupervisor = containerId === 'mapaDetalleSupervisor';

                this.mapaVisualizacion = new google.maps.Map(el, {
                    center: { lat: lat, lng: lng },
                    zoom: 13,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    streetViewControl: !esMapaDetalleSupervisor,
                    fullscreenControl: !esMapaDetalleSupervisor,
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
            this.detenerTickerVisualizacionObra();
            this.mapboxObraVisualizacionRefs = [];
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
                        icon: this.crearIconoNumerado(reclamo.posicion, colorEstado, colorPrioridad, null, reclamo.municipalidad_motivo),
                        zIndex: 1000
                    });
                    marker._marcadorRecorridoPrincipal = true;

                    const contenidoIw = this.rutaDetalleSupervisorId
                        ? this.construirInfoWindowContentMapaDetalleSupervisor(reclamo)
                        : this.crearContenidoInfoWindow(reclamo);

                    const infoWindow = new google.maps.InfoWindow({
                        content: contenidoIw
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

                    if (this.reclamoMuestraIndicadorObraSupervisorMapa(reclamo)) {
                        const hms = this.textoCronometroObraSupervisor(reclamo);
                        const offsetLng = 0.00028;
                        const companion = new google.maps.Marker({
                            position: { lat: coordenadas.lat, lng: coordenadas.lng + offsetLng },
                            map: this.mapaVisualizacion,
                            title: `En obra — ${hms}`,
                            icon: this.crearIconoCamionHmsDataUrl(hms),
                            zIndex: 1001,
                            optimized: false
                        });
                        marker._companionObra = companion;
                        this.marcadoresVisualizacion.push(companion);
                    }
                }
            }

            this.iniciarTickerVisualizacionObraSiCorresponde();
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
            const principales = this.marcadoresVisualizacion.filter((m) => m._marcadorRecorridoPrincipal);
            if (principales.length < 2) {
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

                const coordenadas = principales.map(marker => marker.getPosition());

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
         * Centra el mapa en un reclamo específico (funciona en ambos modales)
         */
        centrarEnReclamo(reclamo) {
            // Buscar en marcadores de visualización individual (excluir marcador compañero obra)
            let marker = this.marcadoresVisualizacion.find(m => m._marcadorRecorridoPrincipal && m._reclamo && m._reclamo.id === reclamo.id);
            let mapa = this.mapaVisualizacion;
            let infoWindowAbierto = this.infoWindowAbiertoVisualizacion;
            
            // Si no se encuentra, buscar en marcadores de todas las rutas
            if (!marker) {
                marker = this.marcadoresRutasActivas.find(m => m._reclamo.id === reclamo.id);
                mapa = this.mapaRutasActivas;
                infoWindowAbierto = this.infoWindowAbiertoRutasActivas;
            }
            
            if (marker && mapa) {
                // Cerrar cualquier info window abierto anteriormente
                if (infoWindowAbierto) {
                    infoWindowAbierto.close();
                }
                
                mapa.setCenter(marker.getPosition());
                mapa.setZoom(16);
                
                // Detener cualquier animación previa
                marker.setAnimation(null);
                
                // Aplicar animación de rebote
                marker.setAnimation(google.maps.Animation.BOUNCE);
                
                // Detener la animación después de 1.5 segundos
                setTimeout(() => {
                    marker.setAnimation(null);
                }, 1500);
                
                // Abrir el info window del marcador
                if (marker._infoWindow) {
                    marker._infoWindow.open(mapa, marker);
                    
                    // Actualizar la referencia del info window abierto
                    if (mapa === this.mapaVisualizacion) {
                        this.infoWindowAbiertoVisualizacion = marker._infoWindow;
                    } else if (mapa === this.mapaRutasActivas) {
                        this.infoWindowAbiertoRutasActivas = marker._infoWindow;
                    }
                }
            }
        },

        /**
         * Centra el mapa en un reclamo específico del modal de todas las rutas
         */
        centrarEnReclamoRutasActivas(reclamo) {
            const marker = this.marcadoresRutasActivas.find(m => m._reclamo.id === reclamo.id);
            if (marker) {
                // Cerrar cualquier info window abierto anteriormente
                if (this.infoWindowAbiertoRutasActivas) {
                    this.infoWindowAbiertoRutasActivas.close();
                }
                
                this.mapaRutasActivas.setCenter(marker.getPosition());
                this.mapaRutasActivas.setZoom(16);
                
                // Detener cualquier animación previa
                marker.setAnimation(null);
                
                // Aplicar animación de rebote
                marker.setAnimation(google.maps.Animation.BOUNCE);
                
                // Detener la animación después de 1.5 segundos
                setTimeout(() => {
                    marker.setAnimation(null);
                }, 1500);
                
                // Abrir el info window del marcador
                if (marker._infoWindow) {
                    marker._infoWindow.open(this.mapaRutasActivas, marker);
                    this.infoWindowAbiertoRutasActivas = marker._infoWindow;
                }
            }
        },

        /**
         * Obtiene el color según el estado del reclamo
         */
        getColorEstado(estado) {
            const colores = {
                'Recibido': '#808080',
                'Asignado': '#0DCAF0',
                'Pendiente': '#FF0000',
                'En ejecución': '#FFD700',
                'Completado': '#198754',
                'En plan': '#808080',
                'Error de datos': '#808080'
            };
            return colores[estado] || '#808080';
        },

        normalizarMotivoReclamo(motivo) {
            return (motivo || '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();
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

        crearSvgBadgeMotivo(motivo, x, y, radio = 6, fontSize = 9) {
            if (!motivo) return '';
            const icono = this.escaparTextoSvg(this.iconoMotivoReclamo(motivo));

            return `
                <circle cx="${x}" cy="${y}" r="${radio}" fill="#FFFFFF" stroke="#ADB5BD" stroke-width="1"/>
                <text x="${x + 0.4}" y="${y + 0.5}" text-anchor="middle" dominant-baseline="middle" font-family="Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif" font-size="${fontSize}">${icono}</text>
            `;
        },

        crearIconoPinMotivo(colorEstado, colorPrioridad, motivo) {
            const tienePrioridadAlta = colorPrioridad !== null;
            const icono = this.escaparTextoSvg(this.iconoMotivoReclamo(motivo));

            return {
                url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                    <svg width="34" height="38" viewBox="0 0 34 38" xmlns="http://www.w3.org/2000/svg">
                        ${tienePrioridadAlta ? `
                            <circle cx="17" cy="15" r="0" fill="#B71C1C" opacity="0.65">
                                <animate attributeName="r" values="0;21;0" dur="2.5s" repeatCount="indefinite"/>
                                <animate attributeName="opacity" values="0.65;0;0.65" dur="2.5s" repeatCount="indefinite"/>
                            </circle>
                        ` : ''}
                        <path d="M17 2.5C11.75 2.5 7.5 6.75 7.5 12c0 7.1 9.5 17.5 9.5 17.5S26.5 19.1 26.5 12C26.5 6.75 22.25 2.5 17 2.5Z" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                        <circle cx="17" cy="12" r="7.4" fill="#FFFFFF" opacity="0.94"/>
                        <text x="17.8" y="12.7" text-anchor="middle" dominant-baseline="middle" font-family="Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif" font-size="12">${icono}</text>
                    </svg>
                `)}`,
                scaledSize: new google.maps.Size(34, 38),
                anchor: new google.maps.Point(17, 30)
            };
        },

        crearElementoMapboxPinMotivo(colorEstado, motivo) {
            const elemento = document.createElement('div');
            elemento.className = 'marker-mapbox-reclamo';
            elemento.innerHTML = `
                <svg width="34" height="38" viewBox="0 0 34 38" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17 2.5C11.75 2.5 7.5 6.75 7.5 12c0 7.1 9.5 17.5 9.5 17.5S26.5 19.1 26.5 12C26.5 6.75 22.25 2.5 17 2.5Z" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                    <circle cx="17" cy="12" r="7.4" fill="#FFFFFF" opacity="0.94"/>
                    <text x="17.8" y="12.7" text-anchor="middle" dominant-baseline="middle" font-family="Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif" font-size="12">${this.escaparTextoSvg(this.iconoMotivoReclamo(motivo))}</text>
                </svg>
            `;
            elemento.style.cursor = 'pointer';
            return elemento;
        },

        crearElementoMapboxNumeradoMotivo(numero, colorEstado, motivo, size = 32) {
            const half = size / 2;
            const radio = Math.max(11, Math.floor(size * 0.43));
            const fontSize = Math.max(9, Math.floor(size * 0.36));
            const badgeX = size - 7;
            const badgeY = 7;
            const elemento = document.createElement('div');
            elemento.className = 'marker-mapbox-ruta';
            elemento.innerHTML = `
                <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="${half}" cy="${half}" r="${radio}" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                    <text x="${half}" y="${half + fontSize * 0.35}" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="${fontSize}" font-weight="bold">${numero}</text>
                    ${this.crearSvgBadgeMotivo(motivo, badgeX, badgeY, Math.max(5, Math.floor(size * 0.18)), Math.max(8, Math.floor(size * 0.28)))}
                </svg>
            `;
            return elemento;
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

        /** La hoja abierta en el modal está en ejecución (API devuelve sesion_reparacion en reclamos). */
        rutaModalEnEjecucionVisualizacion() {
            const e = String(this.rutaVisualizando?.estado_ejecucion || '').toLowerCase().trim();
            return e === 'en ejecución' || e === 'en ejecucion';
        },

        reclamoMuestraIndicadorObraEnRuta(reclamo, ruta) {
            if (!reclamo || reclamo.id == null || !ruta) return false;
            const e = String(ruta.estado_ejecucion || '').toLowerCase().trim();
            if (e !== 'en ejecución' && e !== 'en ejecucion') return false;
            return String(reclamo.municipalidad_estado || '').trim() === 'En ejecución';
        },

        reclamoMuestraIndicadorObraSupervisorMapa(reclamo) {
            return this.reclamoMuestraIndicadorObraEnRuta(reclamo, this.rutaVisualizando);
        },

        msObraSupervisorMapa(reclamo) {
            const sr = reclamo?.sesion_reparacion;
            if (!sr) return 0;
            let ms = Number(sr.acumulado_ms) || 0;
            if (sr.activo && sr.inicio_segmento_at) {
                const t = new Date(String(sr.inicio_segmento_at).replace(' ', 'T')).getTime();
                if (!Number.isNaN(t)) {
                    ms += Math.max(0, this.ahoraMsVisualizacionObra - t);
                }
            }
            return Math.max(0, ms);
        },

        formatoCronometroHMSVisualizacion(ms) {
            const totalS = Math.floor(ms / 1000);
            const h = Math.floor(totalS / 3600);
            const m = Math.floor((totalS % 3600) / 60);
            const s = totalS % 60;
            return [h, m, s].map(n => String(n).padStart(2, '0')).join(':');
        },

        textoCronometroObraSupervisor(reclamo) {
            return this.formatoCronometroHMSVisualizacion(this.msObraSupervisorMapa(reclamo));
        },

        crearIconoCamionHmsDataUrl(hms) {
            const esc = String(hms).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const truck = String.fromCodePoint(0x1F69A);
            const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="92" height="38" viewBox="0 0 92 38">
                <rect x="1" y="1" width="90" height="36" rx="8" fill="#212529" stroke="#ffc107" stroke-width="2"/>
                <text x="10" y="26" font-size="15">${truck}</text>
                <text x="34" y="25" font-size="11" font-family="monospace" fill="#ffc107">${esc}</text>
            </svg>`;
            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg),
                scaledSize: new google.maps.Size(92, 38),
                anchor: new google.maps.Point(4, 19)
            };
        },

        detenerTickerVisualizacionObra() {
            if (this.intervalVisualizacionObra) {
                clearInterval(this.intervalVisualizacionObra);
                this.intervalVisualizacionObra = null;
            }
        },

        refrescarTickerMapaVisualizacionObra() {
            this.ahoraMsVisualizacionObra = Date.now();
            if (this.mapaVisualizacion && this.marcadoresVisualizacion?.length) {
                this.marcadoresVisualizacion.forEach((m) => {
                    if (m._companionObra && m._reclamo && this.reclamoMuestraIndicadorObraSupervisorMapa(m._reclamo)) {
                        const r = this.reclamosRutaVisualizando.find((x) => x.id === m._reclamo.id);
                        if (r) {
                            m._reclamo = r;
                            const hms = this.textoCronometroObraSupervisor(r);
                            m._companionObra.setIcon(this.crearIconoCamionHmsDataUrl(hms));
                        }
                    }
                });
            }
            if (this.mapboxObraVisualizacionRefs?.length) {
                this.mapboxObraVisualizacionRefs.forEach((ref) => {
                    const r = this.reclamosRutaVisualizando.find((x) => x.id === ref.reclamoId);
                    if (r && ref.span && this.reclamoMuestraIndicadorObraSupervisorMapa(r)) {
                        ref.span.textContent = this.textoCronometroObraSupervisor(r);
                    }
                });
            }
            if (this.mapaRutasActivas && this.marcadoresRutasActivas?.length) {
                this.marcadoresRutasActivas.forEach((m) => {
                    if (m._companionObra && m._reclamo && m._ruta && this.reclamoMuestraIndicadorObraEnRuta(m._reclamo, m._ruta)) {
                        const hms = this.textoCronometroObraSupervisor(m._reclamo);
                        m._companionObra.setIcon(this.crearIconoCamionHmsDataUrl(hms));
                    }
                });
            }
            if (this.mapboxObraRutasActivasRefs?.length) {
                this.mapboxObraRutasActivasRefs.forEach((ref) => {
                    if (ref.reclamo && ref.ruta && ref.span && this.reclamoMuestraIndicadorObraEnRuta(ref.reclamo, ref.ruta)) {
                        ref.span.textContent = this.textoCronometroObraSupervisor(ref.reclamo);
                    }
                });
            }
        },

        iniciarTickerVisualizacionObraSiCorresponde() {
            this.detenerTickerVisualizacionObra();
            const hayDetalle = this.rutaModalEnEjecucionVisualizacion()
                && this.reclamosRutaVisualizando.some((r) => this.reclamoMuestraIndicadorObraSupervisorMapa(r));
            const hayTodasGoogle = this.marcadoresRutasActivas?.some((m) => m._companionObra);
            const hayTodasMapbox = (this.mapboxObraRutasActivasRefs?.length || 0) > 0;
            if (!hayDetalle && !hayTodasGoogle && !hayTodasMapbox) {
                return;
            }
            this.refrescarTickerMapaVisualizacionObra();
            this.intervalVisualizacionObra = setInterval(() => this.refrescarTickerMapaVisualizacionObra(), 1000);
        },

        /**
         * Cierra el modal de visualización
         */
        cerrarVisualizacion() {
            this.detenerTickerVisualizacionObra();
            this.mapboxObraVisualizacionRefs = [];
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
         * Elimina una ruta desde el modal de visualización
         */
        async eliminarRutaDesdeVisualizacion(rutaId) {
            const ruta = this.rutas.find(r => r.id == rutaId);
            if (!ruta) {
                this.mostrarMensaje('Ruta no encontrada', 'error');
                return;
            }

            const nombreRuta = ruta.nombre || 'Sin nombre';
            const mensajeConfirmacion = `¿Está seguro de que desea eliminar la hoja de ruta "${nombreRuta}"? Esta acción eliminará también todas las asignaciones de reclamos y no se puede deshacer.`;
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Eliminar Hoja de Ruta');
            
            if (!confirmacion) {
                return;
            }

            try {
                console.log('Eliminando ruta ID:', rutaId);
                await axios.delete(BASE_URL + 'api/rutas/' + rutaId);
                
                // Cerrar el modal de visualización
                const modalVisualizacion = bootstrap.Modal.getInstance(document.getElementById('modalVerRuta'));
                if (modalVisualizacion) {
                    modalVisualizacion.hide();
                }

                if (this.esSupervisorVistaTarjetas && this.rutaDetalleSupervisorId) {
                    this.cerrarModalDetalleSupervisor();
                }
                
                // Limpiar datos de visualización
                this.cerrarVisualizacion();
                
                // Recargar rutas
                await this.obtenerRutas();
                
                this.mostrarMensaje('Hoja de ruta eliminada correctamente', 'success');
            } catch (error) {
                console.error('Error al eliminar ruta:', error);
                this.mostrarMensaje('Error al eliminar la hoja de ruta: ' + (error.response?.data?.message || error.message), 'error');
            }
        },

        /**
         * Selecciona una ruta al hacer clic en una fila de la tabla
         */
        seleccionarRutaPorFila(rutaId, filaElement) {
            console.log('Seleccionando ruta por fila:', rutaId);
            
            // Remover selección anterior
            $('#tabla_rutas tbody tr').removeClass('table-primary');
            
            // Agregar selección visual a la fila actual
            $(filaElement).addClass('table-primary');
            
            // Actualizar el estado
            this.rutaSeleccionada = rutaId;
            this.filaSeleccionada = filaElement;
            
            console.log('Ruta seleccionada:', rutaId);
            console.log('Estado de rutaSeleccionada:', this.rutaSeleccionada);
            
            // Forzar actualización de la vista
            this.$forceUpdate();
        },

        /**
         * Limpia la selección de ruta
         */
        limpiarSeleccion() {
            this.rutaSeleccionada = '';
            this.filaSeleccionada = null;
            $('#tabla_rutas tbody tr').removeClass('table-primary');
            this.$forceUpdate();
        },

        /**
         * Elimina una ruta desde el modal de administración
         */
        async eliminarRutaDesdeAdmin(rutaId) {
            const ruta = this.rutas.find(r => r.id == rutaId);
            if (!ruta) {
                this.mostrarMensaje('Ruta no encontrada', 'error');
                return;
            }

            const nombreRuta = ruta.nombre || 'Sin nombre';
            const mensajeConfirmacion = `¿Está seguro de que desea eliminar la hoja de ruta "${nombreRuta}"? Esta acción eliminará también todas las asignaciones de reclamos y no se puede deshacer.`;
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Eliminar Hoja de Ruta');
            
            if (!confirmacion) {
                return;
            }

            try {
                console.log('Eliminando ruta ID:', rutaId);
                await axios.delete(BASE_URL + 'api/rutas/' + rutaId);
                
                // Cerrar el modal de administración
                const modalAdmin = bootstrap.Modal.getInstance(document.getElementById('modalAdministrarAsignaciones'));
                if (modalAdmin) {
                    modalAdmin.hide();
                }
                
                // Limpiar selección
                this.rutaSeleccionadaAdmin = null;
                this.limpiarSeleccion();
                
                // Recargar rutas
                await this.obtenerRutas();
                
                this.mostrarMensaje('Hoja de ruta eliminada correctamente', 'success');
            } catch (error) {
                console.error('Error al eliminar ruta:', error);
                this.mostrarMensaje('Error al eliminar la hoja de ruta: ' + (error.response?.data?.message || error.message), 'error');
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
                this.rutasActivas = this.rutas;
                this.rutaSeleccionadaVisualizarTodasId = null;
                this.mapboxObraRutasActivasRefs = [];

                const modal = new bootstrap.Modal(document.getElementById('modalVisualizarRutas'));
                modal.show();

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

        seleccionarRutaVisualizarTodas(ruta) {
            if (!ruta) return;
            this.rutaSeleccionadaVisualizarTodasId = ruta.id;
            this.centrarEnRutaActiva(ruta);
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
            this.detenerTickerVisualizacionObra();
            this.mapboxObraRutasActivasRefs = [];
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
                                icon: this.crearIconoNumerado(reclamo.posicion, colorEstado, colorPrioridad, null, reclamo.municipalidad_motivo),
                                zIndex: 1000
                            });
                            marker._marcadorRecorridoPrincipal = true;

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
                            marker._infoWindow = infoWindow;
                            this.marcadoresRutasActivas.push(marker);

                            if (this.reclamoMuestraIndicadorObraEnRuta(reclamo, ruta)) {
                                const hms = this.textoCronometroObraSupervisor(reclamo);
                                const offsetLng = 0.00028;
                                const companion = new google.maps.Marker({
                                    position: { lat: coordenadas.lat, lng: coordenadas.lng + offsetLng },
                                    map: this.mapaRutasActivas,
                                    title: `En obra — ${hms}`,
                                    icon: this.crearIconoCamionHmsDataUrl(hms),
                                    zIndex: 1001,
                                    optimized: false
                                });
                                marker._companionObra = companion;
                                this.marcadoresRutasActivas.push(companion);
                            }
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

            const principales = this.marcadoresRutasActivas.filter((m) => m._reclamo && !m._companionObra);
            if (principales.length > 0 && this.mapaRutasActivas) {
                const bounds = new google.maps.LatLngBounds();
                principales.forEach((m) => bounds.extend(m.getPosition()));
                this.mapaRutasActivas.fitBounds(bounds);
            }

            this.iniciarTickerVisualizacionObraSiCorresponde();
        },

        /**
         * Centra el mapa en una ruta específica
         */
        async centrarEnRutaActiva(ruta) {
            if (!this.mapaRutasActivas) return;
            
            // Buscar todos los marcadores de esta ruta
            const marcadoresRuta = this.marcadoresRutasActivas.filter(m => m._ruta && m._ruta.id === ruta.id);
            
            if (marcadoresRuta.length > 0) {
                // Crear bounds para centrar el mapa en todos los marcadores de la ruta
                const bounds = new google.maps.LatLngBounds();
                
                // Agregar todas las posiciones de los marcadores al bounds
                marcadoresRuta.forEach(marcador => {
                    bounds.extend(marcador.getPosition());
                });
                
                // Centrar el mapa en todos los marcadores de la ruta
                this.mapaRutasActivas.fitBounds(bounds);
                
                // Aplicar animación de rebote a todos los marcadores de la ruta
                marcadoresRuta.forEach(marcador => {
                    // Detener cualquier animación previa
                    marcador.setAnimation(null);
                    
                    // Aplicar animación de rebote
                    marcador.setAnimation(google.maps.Animation.BOUNCE);
                    
                    // Detener la animación después de 2 segundos
                    setTimeout(() => {
                        marcador.setAnimation(null);
                    }, 2000);
                });
                
            }
        },

        /**
         * Limpia la visualización de todas las rutas
         */
        limpiarVisualizacionRutasActivas() {
            if (this.infoWindowAbiertoRutasActivas) {
                this.infoWindowAbiertoRutasActivas.close();
                this.infoWindowAbiertoRutasActivas = null;
            }

            this.marcadoresRutasActivas.forEach(marker => {
                if (marker && marker._companionObra) {
                    marker._companionObra.setMap(null);
                }
                if (marker) marker.setMap(null);
            });
            this.marcadoresRutasActivas = [];
            this.mapboxObraRutasActivasRefs = [];
            
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
            this.detenerTickerVisualizacionObra();
            this.limpiarVisualizacionRutasActivas();
            this.rutasActivas = [];
            this.rutaSeleccionadaVisualizarTodasId = null;
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
                this.detenerTickerVisualizacionObra();
                this.mapboxObraVisualizacionRefs = [];
                if (this.rutaDetalleSupervisorId) {
                    await this.restaurarMapaDetalleSupervisor();
                } else {
                    await this.inicializarMapaVisualizacion();
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
                this.detenerTickerVisualizacionObra();
                if (!this.mapaRutasActivas) {
                    await this.inicializarMapaRutasActivas();
                } else {
                    google.maps.event.trigger(this.mapaRutasActivas, 'resize');
                    await this.mostrarTodasLasRutasActivas();
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
            
            // Ocultar POI (Points of Interest) para que solo se vean los reclamos
            this.mapaMapbox.setLayoutProperty('poi-label', 'visibility', 'none');
            this.mapaMapbox.setLayoutProperty('poi-scalerank', 'visibility', 'none');
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
                container: this.contenedorMapaVisualizacionMapbox,
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [-62.082, -31.427],
                zoom: 13
            });

            await new Promise(resolve => this.mapaVisualizacionMapbox.on('load', resolve));
            
            // Ocultar POI (Points of Interest) para que solo se vean los reclamos
            this.mapaVisualizacionMapbox.setLayoutProperty('poi-label', 'visibility', 'none');
            this.mapaVisualizacionMapbox.setLayoutProperty('poi-scalerank', 'visibility', 'none');
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
            
            // Ocultar POI (Points of Interest) para que solo se vean los reclamos
            this.mapaRutasActivasMapbox.setLayoutProperty('poi-label', 'visibility', 'none');
            this.mapaRutasActivasMapbox.setLayoutProperty('poi-scalerank', 'visibility', 'none');
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
                        const el = this.crearElementoMapboxPinMotivo(colorEstado, reclamo.municipalidad_motivo);
                        
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
                    
                    const el = this.crearElementoMapboxNumeradoMotivo(i + 1, colorEstado, reclamo.municipalidad_motivo, 32);
                    
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

            this.detenerTickerVisualizacionObra();
            this.mapboxObraVisualizacionRefs = [];

            // Limpiar capas anteriores
            if (this.mapaVisualizacionMapbox.getLayer('route')) this.mapaVisualizacionMapbox.removeLayer('route');
            if (this.mapaVisualizacionMapbox.getSource('route')) this.mapaVisualizacionMapbox.removeSource('route');
            
            const marcadoresAnteriores = document.querySelectorAll('#' + this.contenedorMapaVisualizacionMapbox + ' .mapboxgl-marker');
            marcadoresAnteriores.forEach(m => m.remove());

            // Agregar marcadores
            for (const reclamo of this.reclamosRutaVisualizando) {
                const coordenadas = await this.obtenerCoordenadasReclamo(reclamo);
                
                if (coordenadas) {
                    const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                    
                    const el = this.crearElementoMapboxNumeradoMotivo(reclamo.posicion, colorEstado, reclamo.municipalidad_motivo, 32);
                    
                    new mapboxgl.Marker(el)
                        .setLngLat([coordenadas.lng, coordenadas.lat])
                        .setPopup(new mapboxgl.Popup().setHTML(this.crearContenidoInfoWindow(reclamo)))
                        .addTo(this.mapaVisualizacionMapbox);

                    if (this.reclamoMuestraIndicadorObraSupervisorMapa(reclamo)) {
                        const obraWrap = document.createElement('div');
                        obraWrap.style.cssText = 'display:flex;align-items:center;gap:4px;background:#212529;border:2px solid #ffc107;border-radius:8px;padding:2px 8px;box-shadow:0 2px 6px rgba(0,0,0,.35);pointer-events:none;';
                        const hms = this.textoCronometroObraSupervisor(reclamo);
                        obraWrap.innerHTML = `<span style="font-size:15px;line-height:1" aria-hidden="true">🚚</span><span class="cron-obra-hms" style="font-family:monospace;font-size:11px;color:#ffc107;font-weight:600;">${hms}</span>`;
                        const span = obraWrap.querySelector('.cron-obra-hms');
                        const offsetLng = 0.00028;
                        new mapboxgl.Marker({ element: obraWrap, anchor: 'left' })
                            .setLngLat([coordenadas.lng + offsetLng, coordenadas.lat])
                            .addTo(this.mapaVisualizacionMapbox);
                        if (span) {
                            this.mapboxObraVisualizacionRefs.push({ reclamoId: reclamo.id, span });
                        }
                    }
                }
            }

            // Trazar ruta
            const colorRuta = this.rutaVisualizando.color || '#FF0000';
            await this.trazarRutaMapbox(this.reclamosRutaVisualizando, this.mapaVisualizacionMapbox, colorRuta);

            this.iniciarTickerVisualizacionObraSiCorresponde();
        },

        /**
         * Muestra todas las rutas (asignadas y no asignadas) en Mapbox
         */
        async mostrarTodasLasRutasActivasMapbox() {
            if (!this.mapaRutasActivasMapbox) return;

            this.detenerTickerVisualizacionObra();
            this.mapboxObraRutasActivasRefs = [];

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
                            
                            const el = this.crearElementoMapboxNumeradoMotivo(reclamo.posicion, colorEstado, reclamo.municipalidad_motivo, 30);
                            
                            new mapboxgl.Marker(el)
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

                            if (this.reclamoMuestraIndicadorObraEnRuta(reclamo, ruta)) {
                                const obraWrap = document.createElement('div');
                                obraWrap.style.cssText = 'display:flex;align-items:center;gap:4px;background:#212529;border:2px solid #ffc107;border-radius:8px;padding:2px 8px;box-shadow:0 2px 6px rgba(0,0,0,.35);pointer-events:none;';
                                const hms = this.textoCronometroObraSupervisor(reclamo);
                                obraWrap.innerHTML = `<span style="font-size:15px;line-height:1" aria-hidden="true">🚚</span><span class="cron-obra-hms" style="font-family:monospace;font-size:11px;color:#ffc107;font-weight:600;">${hms}</span>`;
                                const span = obraWrap.querySelector('.cron-obra-hms');
                                const offsetLng = 0.00028;
                                new mapboxgl.Marker({ element: obraWrap, anchor: 'left' })
                                    .setLngLat([coordenadas.lng + offsetLng, coordenadas.lat])
                                    .addTo(this.mapaRutasActivasMapbox);
                                if (span) {
                                    this.mapboxObraRutasActivasRefs.push({ reclamo, ruta, span });
                                }
                            }
                        }
                    }

                    await this.trazarRutaMapboxConId(reclamosRuta, this.mapaRutasActivasMapbox, colorRuta, `route-${rutaIdx}`);
                    
                } catch (error) {
                    console.warn('Error al cargar ruta en Mapbox:', error);
                }
            }

            this.iniciarTickerVisualizacionObraSiCorresponde();
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
        },

        /**
         * Obtiene todas las cuadrillas disponibles
         */
        async obtenerCuadrillas() {
            try {
                const response = await axios.get(BASE_URL + 'api/cuadrillas');
                this.cuadrillasDisponibles = response.data;
                console.log('Cuadrillas cargadas:', this.cuadrillasDisponibles.length, this.cuadrillasDisponibles);
            } catch (error) {
                console.error('Error al obtener cuadrillas:', error);
                this.mostrarMensaje('Error al obtener las cuadrillas', 'error');
            }
        },

        /**
         * Abre el modal para asignar una ruta a una cuadrilla
         */
        async abrirModalAsignarRuta(rutaId) {
            try {
                // Obtener información de la ruta
                const ruta = this.rutas.find(r => r.id == rutaId)
                    || (this.rutaVisualizando?.id == rutaId ? this.rutaVisualizando : null);
                if (!ruta) {
                    this.mostrarMensaje('Ruta no encontrada', 'error');
                    return;
                }

                if (!this.puedeAsignarOCambiarCuadrillaRuta(ruta)) {
                    this.mostrarMensaje(
                        'No se puede cambiar la cuadrilla mientras la hoja está en ejecución.',
                        'warning'
                    );
                    return;
                }

                this.rutaParaAsignar = ruta;
                this.cuadrillaSeleccionadaParaAsignar = ruta.cuadrilla_id || '';

                // Cargar cuadrillas si no están cargadas
                if (this.cuadrillasDisponibles.length === 0) {
                    await this.obtenerCuadrillas();
                }

                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalAsignarRuta'));
                modal.show();
            } catch (error) {
                console.error('Error al abrir modal de asignación:', error);
                this.mostrarMensaje('Error al abrir el modal', 'error');
            }
        },

        /**
         * Confirma la asignación de la ruta a la cuadrilla seleccionada
         */
        async confirmarAsignacion() {
            if (!this.cuadrillaSeleccionadaParaAsignar) {
                this.mostrarMensaje('Debe seleccionar una cuadrilla', 'warning');
                return;
            }

            try {
                const cuadrilla = this.cuadrillasDisponibles.find(c => c.id == this.cuadrillaSeleccionadaParaAsignar);
                const nombreCuadrilla = cuadrilla ? cuadrilla.nombre : 'la cuadrilla seleccionada';

                const mensajeConfirmacion = `¿Está seguro que desea asignar la hoja de ruta "${this.rutaParaAsignar.nombre}" a la cuadrilla "${nombreCuadrilla}"?`;
                const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Asignar Hoja de Ruta');

                if (!confirmacion) return;

                const msgOcupada = this.mensajeCuadrillaOcupada(
                    this.cuadrillaSeleccionadaParaAsignar,
                    this.rutaParaAsignar.id
                );
                if (msgOcupada) {
                    this.mostrarMensaje(msgOcupada, 'warning');
                    return;
                }

                const response = await axios.post(BASE_URL + 'api/rutas/asignar', {
                    ruta_id: this.rutaParaAsignar.id,
                    cuadrilla_id: this.cuadrillaSeleccionadaParaAsignar
                });

                if (response.data) {
                    this.mostrarMensaje('Hoja de ruta asignada correctamente', 'success');
                    
                    // Cerrar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalAsignarRuta'));
                    if (modal) modal.hide();

                    // Recargar rutas
                    await this.obtenerRutas();
                }
            } catch (error) {
                console.error('Error al asignar ruta:', error);
                this.mostrarMensaje(this.extraerMensajeErrorApi(error), 'error');
            }
        },

        /**
         * Desasigna una ruta de su cuadrilla
         */
        async desasignarRuta(rutaId) {
            try {
                const ruta = this.rutas.find(r => r.id == rutaId);
                if (!ruta) {
                    this.mostrarMensaje('Ruta no encontrada', 'error');
                    return;
                }

                const response = await axios.post(BASE_URL + `api/rutas/desasignar/${rutaId}`);

                if (response.data) {
                    this.mostrarMensaje('Hoja de ruta desasignada correctamente', 'success');
                    
                    // Recargar rutas
                    await this.obtenerRutas();
                }
            } catch (error) {
                console.error('Error al desasignar ruta:', error);
                this.mostrarMensaje('Error al desasignar la hoja de ruta', 'error');
            }
        },

        /**
         * Cierra el modal de asignación
         */
        cerrarModalAsignar() {
            this.rutaParaAsignar = {};
            this.cuadrillaSeleccionadaParaAsignar = '';
        },

        htmlOpcionesCuadrillaPopup(rutaId, cuadrillaIdActual) {
            return this.cuadrillasDisponibles.map((cuadrilla) => {
                const esActual = String(cuadrilla.id) === String(cuadrillaIdActual);
                const ocupada = this.cuadrillaTieneOtraHojaAsignada(cuadrilla.id, rutaId);
                const otraHoja = this.hojaActivaDeCuadrilla(cuadrilla.id, rutaId);
                const borde = esActual ? '#28a745' : (ocupada ? '#dc3545' : 'rgba(110, 109, 153, 0.2)');
                const fondo = esActual ? 'linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%)' : (ocupada ? '#f8f9fa' : 'white');
                const subtitulo = ocupada
                    ? `Ocupada: ${otraHoja?.nombre || 'otra hoja'}`
                    : (cuadrilla.descripcion || 'Sin descripción');
                const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');

                return `
                                    <button class="btn-cuadrilla-option ${esActual ? 'cuadrilla-actual' : ''} ${ocupada ? 'cuadrilla-ocupada' : ''}"
                                            data-cuadrilla-id="${cuadrilla.id}"
                                            data-ruta-id="${rutaId}"
                                            data-nombre="${esc(cuadrilla.nombre).toLowerCase()}"
                                            data-descripcion="${esc(cuadrilla.descripcion || '').toLowerCase()}"
                                            ${ocupada ? 'disabled' : ''}
                                            style="width: 100%;
                                                   padding: 0.75rem 1rem;
                                                   margin: 0.25rem 0;
                                                   border: 2px solid ${borde};
                                                   background: ${fondo};
                                                   border-radius: 10px;
                                                   display: flex;
                                                   align-items: center;
                                                   gap: 0.75rem;
                                                   cursor: ${ocupada ? 'not-allowed' : 'pointer'};
                                                   opacity: ${ocupada ? '0.7' : '1'};
                                                   transition: all 0.2s ease;
                                                   font-weight: 600;
                                                   color: ${esActual ? '#155724' : (ocupada ? '#842029' : '#06044B')};"
                                            onmouseover="if(!this.disabled && !this.classList.contains('cuadrilla-actual')) { this.style.background='linear-gradient(135deg, #F8F9FE 0%, #E0E0E9 100%)'; this.style.transform='translateX(4px)'; this.style.borderColor='#3A3972'; }"
                                            onmouseout="if(!this.disabled && !this.classList.contains('cuadrilla-actual')) { this.style.background='white'; this.style.transform='translateX(0)'; this.style.borderColor='rgba(110, 109, 153, 0.2)'; }">
                                        <div style="width: 32px; height: 32px; background: linear-gradient(135deg, ${ocupada ? '#adb5bd' : '#6E6D99'} 0%, ${ocupada ? '#6c757d' : '#3A3972'} 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                            ${esActual ? '<i class="bi bi-check-circle-fill" style="color: white;"></i>' : (ocupada ? '<i class="bi bi-slash-circle" style="color: white;"></i>' : '<i class="bi bi-people-fill" style="color: white;"></i>')}
                                        </div>
                                        <div style="flex: 1; text-align: left;">
                                            <div style="font-size: 0.85rem; font-weight: 700;">${esc(cuadrilla.nombre)}</div>
                                            <div style="font-size: 0.7rem; opacity: 0.85; color: ${ocupada ? '#dc3545' : 'inherit'};">${esc(subtitulo)}</div>
                                        </div>
                                        ${esActual ? '<i class="bi bi-check-lg" style="font-size: 1.2rem; color: #28a745;"></i>' : ''}
                                    </button>`;
            }).join('');
        },

        /**
         * Abre un popup moderno para seleccionar la asignación de cuadrilla
         */
        async abrirPopupAsignacion(rutaId, rutaNombre, cuadrillaActual, cuadrillaIdActual, buttonElement) {
            try {
                const ruta = this.rutas.find((r) => r.id == rutaId);
                if (ruta && !this.puedeAsignarOCambiarCuadrillaRuta(ruta)) {
                    this.mostrarMensaje(
                        'No se puede cambiar la cuadrilla mientras la hoja está en ejecución.',
                        'warning'
                    );
                    return;
                }

                // Cargar cuadrillas si no están cargadas
                if (this.cuadrillasDisponibles.length === 0) {
                    await this.obtenerCuadrillas();
                }

                // Cerrar cualquier popup anterior
                $('.popup-asignacion-overlay').remove();

                // Obtener la posición del botón
                const buttonOffset = $(buttonElement).offset();
                const buttonHeight = $(buttonElement).outerHeight();
                const buttonWidth = $(buttonElement).outerWidth();
                
                // Calcular posición del popup (ajustar si está cerca del borde)
                const windowWidth = $(window).width();
                const windowHeight = $(window).height();
                const popupWidth = 450; // Ancho aumentado para incluir filtro
                const popupMaxHeight = 500;
                
                let topPos = buttonOffset.top + buttonHeight + 10;
                let leftPos = buttonOffset.left;
                
                // Ajustar si se sale por la derecha
                if (leftPos + popupWidth > windowWidth - 20) {
                    leftPos = windowWidth - popupWidth - 20;
                }
                
                // Ajustar si se sale por abajo
                if (topPos + popupMaxHeight > windowHeight - 20) {
                    topPos = buttonOffset.top - popupMaxHeight - 10;
                    if (topPos < 20) {
                        topPos = 20;
                    }
                }

                console.log('Posición del popup:', { topPos, leftPos, buttonOffset, windowWidth, windowHeight });

                // Crear el HTML del popup
                let popupHTML = `
                    <div class="popup-asignacion-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9998; background: rgba(6, 4, 75, 0.3); backdrop-filter: blur(2px);">
                        <div class="popup-asignacion-content" style="position: absolute; 
                             top: ${topPos}px; 
                             left: ${leftPos}px;
                             width: ${popupWidth}px;
                             background: white;
                             border-radius: 16px;
                             box-shadow: 0 10px 40px rgba(6, 4, 75, 0.3);
                             z-index: 9999;
                             animation: popupSlideIn 0.3s ease;
                             overflow: hidden;
                             border: 1px solid rgba(110, 109, 153, 0.2);">
                            
                            <!-- Header del popup -->
                            <div style="background: linear-gradient(135deg, #3A3972 0%, #06044B 100%); 
                                        padding: 1rem 1.25rem;
                                        color: white;
                                        font-weight: 700;
                                        font-size: 0.9rem;
                                        display: flex;
                                        align-items: center;
                                        justify-content: space-between;
                                        letter-spacing: 0.3px;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="bi bi-people-fill" style="font-size: 1.1rem; color: white;"></i>
                                    <span>Asignar Cuadrilla</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    ${cuadrillaActual ? `
                                        <button class="btn-desasignar-header" 
                                                data-cuadrilla-id="" 
                                                data-ruta-id="${rutaId}"
                                                style="background: rgba(220, 53, 69, 0.9); 
                                                       border: none; 
                                                       color: white; 
                                                       padding: 0.4rem 0.8rem;
                                                       border-radius: 8px; 
                                                       display: flex; 
                                                       align-items: center; 
                                                       gap: 0.4rem;
                                                       cursor: pointer;
                                                       transition: all 0.2s ease;
                                                       font-size: 0.8rem;
                                                       font-weight: 600;"
                                                onmouseover="this.style.background='rgba(220, 53, 69, 1)'; this.style.transform='scale(1.05)';"
                                                onmouseout="this.style.background='rgba(220, 53, 69, 0.9)'; this.style.transform='scale(1)';">
                                            <i class="bi bi-x-circle-fill" style="color: white;"></i>
                                            <span>Desasignar</span>
                                        </button>
                                    ` : ''}
                                    <button class="btn-close-popup" style="background: rgba(255,255,255,0.2); 
                                                                           border: none; 
                                                                           color: white; 
                                                                           width: 24px; 
                                                                           height: 24px; 
                                                                           border-radius: 50%; 
                                                                           display: flex; 
                                                                           align-items: center; 
                                                                           justify-content: center; 
                                                                           cursor: pointer;
                                                                           transition: all 0.2s ease;"
                                            onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='rotate(90deg)';"
                                            onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='rotate(0deg)';">
                                        <i class="bi bi-x-lg" style="font-size: 0.85rem; color: white;"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Información de la ruta -->
                            <div style="padding: 1rem 1.25rem; background: linear-gradient(135deg, #F8F9FE 0%, #FFFFFF 100%); border-bottom: 1px solid rgba(110, 109, 153, 0.1);">
                                <div style="font-size: 0.75rem; color: #6E6D99; font-weight: 600; margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                    Hoja de Ruta
                                </div>
                                <div style="font-size: 0.9rem; color: #06044B; font-weight: 700;">
                                    ${rutaNombre}
                                </div>
                                ${cuadrillaActual ? `
                                    <div style="margin-top: 0.5rem; padding: 0.5rem; background: rgba(40, 167, 69, 0.1); border-radius: 8px; display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="bi bi-check-circle-fill" style="color: #28a745; font-size: 0.9rem;"></i>
                                        <span style="font-size: 0.75rem; color: #155724; font-weight: 600;">Asignada a: ${cuadrillaActual}</span>
                                    </div>
                                ` : ''}
                            </div>

                            <!-- Filtro de búsqueda -->
                            <div style="padding: 1rem 1.25rem; background: white; border-bottom: 1px solid rgba(110, 109, 153, 0.1);">
                                <div style="position: relative;">
                                    <i class="bi bi-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6E6D99; font-size: 0.9rem;"></i>
                                    <input type="text" 
                                           class="input-filtro-cuadrillas" 
                                           placeholder="Buscar cuadrilla..."
                                           style="width: 100%;
                                                  padding: 0.6rem 0.75rem 0.6rem 2.5rem;
                                                  border: 2px solid rgba(110, 109, 153, 0.2);
                                                  border-radius: 10px;
                                                  font-size: 0.85rem;
                                                  color: #06044B;
                                                  transition: all 0.2s ease;
                                                  outline: none;"
                                           onfocus="this.style.borderColor='#3A3972'; this.style.boxShadow='0 0 0 3px rgba(58, 57, 114, 0.1)';"
                                           onblur="this.style.borderColor='rgba(110, 109, 153, 0.2)'; this.style.boxShadow='none';">
                                </div>
                            </div>

                            <!-- Lista de cuadrillas -->
                            <div class="lista-cuadrillas-container" style="max-height: 300px; overflow-y: auto; padding: 0.5rem;">
                                ${this.htmlOpcionesCuadrillaPopup(rutaId, cuadrillaIdActual)}
                            </div>
                        </div>
                    </div>

                    <style>
                        @keyframes popupSlideIn {
                            from {
                                opacity: 0;
                                transform: translateY(-10px);
                            }
                            to {
                                opacity: 1;
                                transform: translateY(0);
                            }
                        }
                        
                        .popup-asignacion-content::-webkit-scrollbar {
                            width: 6px;
                        }
                        
                        .popup-asignacion-content::-webkit-scrollbar-track {
                            background: rgba(110, 109, 153, 0.1);
                            border-radius: 3px;
                        }
                        
                        .popup-asignacion-content::-webkit-scrollbar-thumb {
                            background: rgba(110, 109, 153, 0.3);
                            border-radius: 3px;
                        }
                        
                        .popup-asignacion-content::-webkit-scrollbar-thumb:hover {
                            background: rgba(110, 109, 153, 0.5);
                        }
                    </style>
                `;

                // Agregar el popup al body
                $('body').append(popupHTML);

                // Cerrar al hacer clic en el overlay
                $('.popup-asignacion-overlay').on('click', function(e) {
                    if (e.target === this) {
                        $(this).remove();
                    }
                });

                // Cerrar al hacer clic en el botón de cerrar
                $('.btn-close-popup').on('click', function() {
                    $('.popup-asignacion-overlay').remove();
                });

                // Manejar clic en botón de desasignar del header
                const vueComponent = this;
                $('.btn-desasignar-header').on('click', async function() {
                    const cuadrillaId = $(this).data('cuadrilla-id');
                    const rutaId = $(this).data('ruta-id');
                    
                    // Cerrar el popup
                    $('.popup-asignacion-overlay').remove();
                    
                    // Llamar a la función de cambio de asignación (desasignar)
                    await vueComponent.cambiarAsignacionRuta(rutaId, cuadrillaId);
                });

                // Manejar clic en opciones de cuadrilla
                $('.btn-cuadrilla-option').on('click', async function() {
                    if ($(this).prop('disabled') || $(this).hasClass('cuadrilla-ocupada')) {
                        return;
                    }
                    const cuadrillaId = $(this).data('cuadrilla-id');
                    const rutaId = $(this).data('ruta-id');
                    
                    // Cerrar el popup
                    $('.popup-asignacion-overlay').remove();
                    
                    // Llamar a la función de cambio de asignación
                    await vueComponent.cambiarAsignacionRuta(rutaId, cuadrillaId);
                });

                // Funcionalidad del filtro de búsqueda
                $('.input-filtro-cuadrillas').on('input', function() {
                    const filtroTexto = $(this).val().toLowerCase().trim();
                    
                    $('.btn-cuadrilla-option').each(function() {
                        const nombreCuadrilla = $(this).data('nombre') || '';
                        const descripcionCuadrilla = $(this).data('descripcion') || '';
                        
                        // Mostrar u ocultar según el filtro
                        if (nombreCuadrilla.includes(filtroTexto) || descripcionCuadrilla.includes(filtroTexto)) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                    
                    // Mostrar mensaje si no hay resultados
                    const cuadrillasVisibles = $('.btn-cuadrilla-option:visible').length;
                    if (cuadrillasVisibles === 0 && filtroTexto !== '') {
                        if ($('.no-resultados-filtro').length === 0) {
                            $('.lista-cuadrillas-container').append(`
                                <div class="no-resultados-filtro" style="padding: 2rem 1rem; text-align: center; color: #6E6D99;">
                                    <i class="bi bi-search" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p style="margin-top: 0.5rem; font-size: 0.85rem;">No se encontraron cuadrillas</p>
                                </div>
                            `);
                        }
                    } else {
                        $('.no-resultados-filtro').remove();
                    }
                });

            } catch (error) {
                console.error('Error al abrir popup de asignación:', error);
                this.mostrarMensaje('Error al abrir el selector de cuadrillas', 'error');
            }
        },

        /**
         * Cambia la asignación de una ruta directamente desde el selector de la tabla
         */
        async cambiarAsignacionRuta(rutaId, cuadrillaId) {
            try {
                const ruta = this.rutas.find(r => r.id == rutaId);
                if (!ruta) {
                    this.mostrarMensaje('Ruta no encontrada', 'error');
                    return;
                }

                if (!this.puedeAsignarOCambiarCuadrillaRuta(ruta)) {
                    this.mostrarMensaje(
                        'No se puede cambiar la cuadrilla mientras la hoja está en ejecución.',
                        'warning'
                    );
                    return;
                }

                // Si cuadrillaId está vacío, desasignar
                if (!cuadrillaId || cuadrillaId === '') {
                    // Verificar si la ruta está asignada
                    if (ruta.asignada != 1) {
                        this.mostrarMensaje('La ruta ya está sin asignar', 'info');
                        return;
                    }

                    const response = await axios.post(BASE_URL + `api/rutas/desasignar/${rutaId}`);

                    if (response.data) {
                        this.mostrarMensaje('Hoja de ruta desasignada correctamente', 'success');
                        await this.obtenerRutas();
                        
                        // Aplicar animación de éxito al botón
                        this.$nextTick(() => {
                            const button = $(`.asignacion-btn-moderno[data-ruta-id="${rutaId}"]`);
                            button.css({
                                'animation': 'pulse 0.5s ease',
                                'transform': 'scale(1.05)'
                            });
                            setTimeout(() => {
                                button.css({
                                    'animation': '',
                                    'transform': ''
                                });
                            }, 500);
                        });
                    }
                } else {
                    const msgOcupada = this.mensajeCuadrillaOcupada(cuadrillaId, rutaId);
                    if (msgOcupada) {
                        this.mostrarMensaje(msgOcupada, 'warning');
                        return;
                    }

                    const response = await axios.post(BASE_URL + 'api/rutas/asignar', {
                        ruta_id: rutaId,
                        cuadrilla_id: cuadrillaId
                    });

                    if (response.data) {
                        const mensaje = ruta.asignada == 1 ? 'Hoja de ruta reasignada correctamente' : 'Hoja de ruta asignada correctamente';
                        this.mostrarMensaje(mensaje, 'success');
                        await this.obtenerRutas();
                        
                        // Aplicar animación de éxito al botón
                        this.$nextTick(() => {
                            const button = $(`.asignacion-btn-moderno[data-ruta-id="${rutaId}"]`);
                            button.css({
                                'animation': 'pulse 0.5s ease',
                                'transform': 'scale(1.05)'
                            });
                            setTimeout(() => {
                                button.css({
                                    'animation': '',
                                    'transform': ''
                                });
                            }, 500);
                        });
                    }
                }
            } catch (error) {
                console.error('Error al cambiar asignación de ruta:', error);
                this.mostrarMensaje(this.extraerMensajeErrorApi(error), 'error');
                // Recargar la tabla para restaurar el estado correcto
                await this.obtenerRutas();
            }
        },

        /**
         * Abre el modal para administrar todas las asignaciones
         */
        async abrirModalAdministrarAsignaciones() {
            try {
                // Verificar si hay una ruta seleccionada
                if (!this.rutaSeleccionada || this.rutaSeleccionada === '' || this.rutaSeleccionada === null) {
                    this.mostrarMensaje('Debe seleccionar una hoja de ruta antes de administrar asignaciones', 'warning');
                    return;
                }
                
                // Cargar cuadrillas si no están cargadas
                if (this.cuadrillasDisponibles.length === 0) {
                    await this.obtenerCuadrillas();
                }

                // Seleccionar automáticamente la ruta que se seleccionó en la tabla principal
                const rutaSeleccionada = this.rutas.find(r => r.id == this.rutaSeleccionada);
                if (rutaSeleccionada) {
                    this.rutaSeleccionadaAdmin = rutaSeleccionada;
                }

                // Mostrar modal
                const modal = new bootstrap.Modal(document.getElementById('modalAdministrarAsignaciones'));
                modal.show();
            } catch (error) {
                console.error('Error al abrir modal de administración:', error);
                this.mostrarMensaje('Error al abrir el modal de administración', 'error');
            }
        },

        /**
         * Cierra el modal de administrar asignaciones
         */
        cerrarModalAdministrarAsignaciones() {
            this.rutaSeleccionadaAdmin = null;
        },

        /**
         * Abre el modal de asignar ruta desde el modal de administración
         */
        async abrirModalAsignarRutaDesdeAdmin(rutaId) {
            // Cerrar el modal de administración
            const modalAdmin = bootstrap.Modal.getInstance(document.getElementById('modalAdministrarAsignaciones'));
            if (modalAdmin) {
                modalAdmin.hide();
            }

            // Abrir el modal de asignación
            await this.abrirModalAsignarRuta(rutaId);

            // Cuando se cierre el modal de asignación, reabrir el de administración
            const modalAsignar = document.getElementById('modalAsignarRuta');
            const handleClose = async () => {
                // Recargar las rutas para reflejar los cambios
                await this.obtenerRutas();
                
                // Actualizar la ruta seleccionada si aún existe
                if (this.rutaSeleccionadaAdmin) {
                    const rutaActualizada = this.rutas.find(r => r.id === this.rutaSeleccionadaAdmin.id);
                    if (rutaActualizada) {
                        this.rutaSeleccionadaAdmin = rutaActualizada;
                    }
                }
                
                // Reabrir el modal de administración
                const modalAdminReabrir = new bootstrap.Modal(document.getElementById('modalAdministrarAsignaciones'));
                modalAdminReabrir.show();
                
                // Remover este listener
                modalAsignar.removeEventListener('hidden.bs.modal', handleClose);
            };
            
            modalAsignar.addEventListener('hidden.bs.modal', handleClose);
        },

        /**
         * Desasigna una ruta desde el modal de administración
         */
        async desasignarRutaDesdeAdmin(rutaId) {
            const ruta = this.rutas.find(r => r.id == rutaId);
            if (!ruta) {
                this.mostrarMensaje('Ruta no encontrada', 'error');
                return;
            }

            const nombreRuta = ruta.nombre || 'Sin nombre';
            const confirmacion = await this.mostrarConfirmacion(
                `¿Está seguro de que desea desasignar la hoja de ruta "${nombreRuta}" de la cuadrilla "${ruta.cuadrilla_nombre}"?`,
                'Desasignar Hoja de Ruta'
            );
            
            if (!confirmacion) {
                return;
            }

            try {
                await axios.delete(BASE_URL + 'api/rutas/' + rutaId + '/desasignar');
                
                // Recargar las rutas
                await this.obtenerRutas();
                
                // Actualizar la ruta seleccionada en el modal
                if (this.rutaSeleccionadaAdmin && this.rutaSeleccionadaAdmin.id === rutaId) {
                    const rutaActualizada = this.rutas.find(r => r.id === rutaId);
                    if (rutaActualizada) {
                        this.rutaSeleccionadaAdmin = rutaActualizada;
                    }
                }
                
                this.mostrarMensaje('Hoja de ruta desasignada correctamente', 'success');
            } catch (error) {
                console.error('Error al desasignar ruta:', error);
                this.mostrarMensaje('Error al desasignar la hoja de ruta: ' + (error.response?.data?.message || error.message), 'error');
            }
        }
    },

    async mounted() {
        // Cargar cuadrillas primero antes de inicializar la tabla
        await this.obtenerCuadrillas();
        await this.obtenerReclamos();
        await this.obtenerRutas();
        if (this.esSupervisorVistaTarjetas) {
            this.configurarModalDetalleSupervisor();
        }
    },

    beforeUnmount() {
        this.detenerCronometroSupervisorRutas();
        this.limpiarMapasPreviewSupervisor();
    }
});