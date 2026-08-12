// JS vinculado a la vista rutas.php para ver el listado completo de las rutas que hay en una tabla

const app = Vue.createApp({
    data() {
        return {
            rutas: [],
            reclamos: [],
            reclamoSeleccionado: {},
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

            coloresDisponiblesRuta: [
                '#2a9d8f',
                '#3a3972',
                '#f0a202',
                '#e76f51',
                '#457b9d',
                '#bc6c25',
                '#118ab2',
                '#ef476f',
                '#06d6a0',
                '#264653',
                '#6c757d',
                '#9b2226',
                '#FF6B35',
            ],

            // Reclamos seleccionados para la nueva ruta
            reclamosSeleccionados: [],
            primerReclamoSeleccionado: null,
            idsReclamosEnRutasActivas: [],
            reclamosDisponibles: 0,
            
            // Modo de selección
            modoSeleccionManual: false,
            modoSeleccionPrimerReclamo: false,
            
            // Modo de edición
            modoEdicion: false,
            mostrarListaRutaVistaPrevia: false,
            indiceReclamoListaParada: {},
            rutaOriginal: [], // Guardar la ruta original antes de editar
            
            // Vista previa de la ruta
            vistaPrevia: {
                activa: false,
                reclamos: [],
                rutaOptimizada: [],
                marcadoresRuta: [],
                marcadoresOtros: [],
                marcadoresRutasActivas: [], // Marcadores de otras rutas activas
                polylineRuta: null,
                directionsRenderer: null
            },
            
            // Visualización de ruta
            rutaVisualizando: {},
            reclamosRutaVisualizando: [],
            mostrarListaRutaVisualizacion: false,
            indiceReclamoListaParadaVisualizacion: {},
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
            brilloRecorridoRutasActivas: { frameId: null, timeoutId: null, polylines: [] },
            brilloRecorridoMapboxRutasActivas: { frameId: null, timeoutId: null },
            capasRecorridoMapboxRutasActivas: [],
            
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
            cuadrillaDetalleAbiertaId: null,
            mostrarDetalleCuadrillaSupervisor: false,
            // Administración de asignaciones
            rutaSeleccionadaAdmin: null,
            
            // Selección de ruta en tabla principal
            rutaSeleccionada: '',
            filaSeleccionada: null,
            
            // Control de event listeners
            eventListenerConfigurado: false,

            /** Actualización en vivo del tiempo en ejecución (tabla supervisor) */
            _tickCronometroSupervisor: null,
            /** Sincronización periódica de hojas activas (supervisor) */
            _pollSupervisorActivas: null,
            _sincronizandoSupervisorActivas: false,
            /** Intervalo de polling del panel supervisor (ms) */
            intervaloPollSupervisorActivas: 5000,
            _ultimoFingerprintMapaDetalleSupervisor: null,
            _ultimoFingerprintMapaTodasRutas: null,
            _refrescandoMapaTodasRutas: false,

            /** Cronómetro en mapa/modal ver ruta (reclamos En ejecución con sesión de obra) */
            ahoraMsVisualizacionObra: Date.now(),
            intervalVisualizacionObra: null,
            mapboxObraVisualizacionRefs: [],
            _marcadoresVisualizacionMapbox: [],
            _mapboxObraVisualizacionMarkers: [],
            _googleObraVisualizacionMarkers: [],
            _googleObraRutasActivasMarkers: [],
            mapboxObraRutasActivasRefs: [],
            promediosTiempoMotivoMap: {},
            rutaSeleccionadaVisualizarTodasId: null,

            userRole: window.USER_ROLE || '3',
            solapaRutas: 'activas',
            historialEjecuciones: [],
            historialEjecucionesCargando: false,
            historialEjecucionDetalle: null,
            historialDetalleCargando: false,
            historialEjecucionMapa: null,
            historialMapaCargando: false,
            modoVistaHistorialMapa: 'mapa',
            proveedorMapaHistorial: 'google',
            mapaHistorial: null,
            mapaHistorialMapbox: null,
            marcadoresHistorial: [],
            _marcadoresHistorialMapbox: [],
            directionsRendererHistorial: null,
            infoWindowAbiertoHistorial: null,
            indiceReclamoListaParadaHistorial: {},
            observacionesPorReclamoHistorial: {},
            cambiosEstadoPorReclamoHistorial: {},
            materialesPorReclamoHistorial: {},

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
            ahoraCronometroSupervisor: Date.now(),

            bitacoraFotoAmpliadaUrl: '',
            bitacoraFotoAmpliadaCaption: '',
            bitacoraFotoAmpliadaActiva: false
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

        historialBitacoraSupervisorOrdenado() {
            const lista = Array.isArray(this.historialObservacionesSupervisor)
                ? [...this.historialObservacionesSupervisor]
                : [];
            lista.sort((a, b) => {
                const ta = new Date(a.created_at || 0).getTime();
                const tb = new Date(b.created_at || 0).getTime();
                if (ta !== tb) {
                    return ta - tb;
                }
                return String(a.id).localeCompare(String(b.id));
            });
            return lista;
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

        cuadrillaDetalleAbierta() {
            if (!this.cuadrillaDetalleAbiertaId) {
                return null;
            }
            return this.cuadrillasDisponibles.find(
                (c) => String(c.id) === String(this.cuadrillaDetalleAbiertaId)
            ) || null;
        },

        cuadrillaAsignadaDetalleSupervisor() {
            const id = this.rutaVisualizando?.cuadrilla_id;
            if (!id) {
                return null;
            }
            return this.cuadrillasDisponibles.find(
                (c) => String(c.id) === String(id)
            ) || null;
        },
        
        puedeVerVistaPrevia() {
            return this.nuevaRuta.cantidadReclamos > 0 &&
                   this.reclamosDisponibles >= this.nuevaRuta.cantidadReclamos &&
                   !this.vistaPrevia.activa; // Solo si no está activa aún
        },

        paradasListaVistaPrevia() {
            return this.agruparParadasRutaVistaPrevia(this.vistaPrevia.rutaOptimizada);
        },

        paradasListaVisualizacion() {
            return this.agruparParadasRutaVistaPrevia(this.reclamosRutaVisualizando);
        },

        paradasListaHistorial() {
            return this.agruparParadasRutaVistaPrevia(this.historialEjecucionMapa?.reclamos || []);
        },

        lineaTiempoRegistroHistorialEjecucion() {
            const detalle = this.historialEjecucionDetalle;
            if (!detalle) {
                return [];
            }
            const items = [];
            for (const ev of (detalle.eventos || [])) {
                items.push({
                    tipo: 'evento',
                    clave: 'ev-' + ev.id,
                    at: ev.ocurrido_at,
                    evento: ev
                });
            }
            for (const obs of (detalle.observaciones || [])) {
                items.push({
                    tipo: 'observacion',
                    clave: 'obs-' + obs.id,
                    at: obs.created_at,
                    observacion: obs
                });
            }
            items.sort((a, b) => {
                const diff = this._timestampHistorialEjecucion(a.at) - this._timestampHistorialEjecucion(b.at);
                if (diff !== 0) {
                    return diff;
                }
                if (a.tipo !== b.tipo) {
                    return a.tipo === 'evento' ? -1 : 1;
                }
                return String(a.clave).localeCompare(String(b.clave));
            });
            return items;
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
                this.iniciarPollingSupervisorActivas();
            } else {
                this.detenerPollingSupervisorActivas();
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

        async cargarDetalleHistorialEjecucion(id) {
            const response = await axios.get(BASE_URL + 'api/rutas/ejecuciones/' + id + '/detalle');
            return response.data;
        },

        prepararObservacionesHistorialEjecucion(detalle) {
            const obsMap = {};
            for (const o of (detalle?.observaciones || [])) {
                const rid = o.reclamo_id;
                if (!obsMap[rid]) {
                    obsMap[rid] = [];
                }
                obsMap[rid].push(o);
            }
            this.observacionesPorReclamoHistorial = obsMap;
        },

        prepararCambiosEstadoHistorialEjecucion(detalle) {
            const mapa = {};
            const rutaNombre = detalle?.ejecucion?.ruta_nombre || null;
            const rutaColor = detalle?.ejecucion?.ruta_color || null;
            for (const ev of (detalle?.eventos || [])) {
                if (ev.tipo !== 'reclamo_cambio_estado' || !ev.reclamo_id) {
                    continue;
                }
                const md = ev.metadata && typeof ev.metadata === 'object' ? ev.metadata : null;
                if (!md || md.estado_anterior == null || md.estado_nuevo == null) {
                    continue;
                }
                const rid = ev.reclamo_id;
                if (!mapa[rid]) {
                    mapa[rid] = [];
                }
                mapa[rid].push({
                    id: ev.id,
                    ocurrido_at: ev.ocurrido_at,
                    estado_anterior: md.estado_anterior,
                    estado_nuevo: md.estado_nuevo,
                    usuario_nombre: ev.usuario_nombre || null,
                    usuario_foto_perfil: ev.usuario_foto_perfil || null,
                    ruta_nombre: rutaNombre,
                    ruta_color: rutaColor
                });
            }
            this.cambiosEstadoPorReclamoHistorial = mapa;
        },

        async cargarMaterialesHistorialEjecucion(reclamos) {
            if (!reclamos?.length) {
                this.materialesPorReclamoHistorial = {};
                return;
            }
            const ejecucionId = this.historialEjecucionMapa?.ejecucion?.id;
            const params = ejecucionId ? { ruta_ejecucion_id: ejecucionId } : {};
            const materialesMap = {};
            await Promise.all(reclamos.map(async (reclamo) => {
                if (!reclamo?.id) {
                    return;
                }
                try {
                    const r = await axios.get(BASE_URL + 'api/reclamos/' + reclamo.id + '/materiales', { params });
                    materialesMap[reclamo.id] = Array.isArray(r.data) ? r.data : [];
                } catch (error) {
                    console.warn('No se pudieron cargar materiales del historial', reclamo.id, error);
                    materialesMap[reclamo.id] = [];
                }
            }));
            this.materialesPorReclamoHistorial = materialesMap;
        },

        materialesReclamoHistorialEjecucion(reclamo) {
            if (!reclamo?.id) {
                return [];
            }
            return this.materialesPorReclamoHistorial[reclamo.id] || [];
        },

        cantidadMaterialesReclamoHistorial(reclamo) {
            return this.materialesReclamoHistorialEjecucion(reclamo).length;
        },

        async abrirDetalleHistorialEjecucion(id) {
            if (!id) {
                return;
            }
            this.historialDetalleCargando = true;
            this.historialEjecucionDetalle = null;
            try {
                this.historialEjecucionDetalle = await this.cargarDetalleHistorialEjecucion(id);
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

        async abrirHistorialEjecucionMapa(id) {
            if (!id) {
                return;
            }
            this.historialMapaCargando = true;
            this.historialEjecucionMapa = null;
            this.indiceReclamoListaParadaHistorial = {};
            try {
                const detalle = await this.cargarDetalleHistorialEjecucion(id);
                if (!Object.keys(this.promediosTiempoMotivoMap || {}).length) {
                    await this.cargarPromediosTiempoMotivo();
                }
                this.historialEjecucionMapa = detalle;
                this.prepararObservacionesHistorialEjecucion(detalle);
                this.prepararCambiosEstadoHistorialEjecucion(detalle);
                await this.cargarMaterialesHistorialEjecucion(detalle?.reclamos || []);
                this.modoVistaHistorialMapa = 'mapa';
                this.historialMapaCargando = false;
                await this.$nextTick();
                const el = document.getElementById('modalHistorialEjecucionMapa');
                if (el && typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getOrCreateInstance(el);
                    await new Promise((resolve) => {
                        const onShown = () => {
                            el.removeEventListener('shown.bs.modal', onShown);
                            resolve();
                        };
                        el.addEventListener('shown.bs.modal', onShown);
                        modal.show();
                    });
                } else {
                    await new Promise((resolve) => setTimeout(resolve, 350));
                }
                await this.$nextTick();
                await this.restaurarMapaHistorialEjecucion();
            } catch (error) {
                console.error('Error al cargar mapa del historial:', error);
                this.mostrarMensaje('No se pudo cargar el recorrido histórico.', 'error');
            } finally {
                this.historialMapaCargando = false;
            }
        },

        abrirRegistroDesdeHistorialMapa() {
            const id = this.historialEjecucionMapa?.ejecucion?.id;
            if (!id) {
                return;
            }
            const elMapa = document.getElementById('modalHistorialEjecucionMapa');
            if (elMapa && typeof bootstrap !== 'undefined') {
                const inst = bootstrap.Modal.getInstance(elMapa);
                if (inst) {
                    inst.hide();
                }
            }
            this.abrirDetalleHistorialEjecucion(id);
        },

        configurarModalHistorialMapa() {
            const elModal = document.getElementById('modalHistorialEjecucionMapa');
            if (!elModal || elModal.dataset.historialMapaBound === '1') {
                return;
            }
            elModal.dataset.historialMapaBound = '1';
            elModal.addEventListener('hidden.bs.modal', () => {
                this.limpiarMapaHistorialEjecucion();
                this.historialEjecucionMapa = null;
                this.observacionesPorReclamoHistorial = {};
                this.cambiosEstadoPorReclamoHistorial = {};
                this.materialesPorReclamoHistorial = {};
            });
        },

        cambiarModoVistaHistorialMapa(modo) {
            this.modoVistaHistorialMapa = modo;
            if (modo === 'mapa') {
                this.$nextTick(() => this.restaurarMapaHistorialEjecucion());
            }
        },

        async alternarProveedorHistorialMapa() {
            this.proveedorMapaHistorial = this.proveedorMapaHistorial === 'google' ? 'mapbox' : 'google';
            this.limpiarMapaHistorialEjecucion();
            await this.$nextTick();
            await new Promise((resolve) => setTimeout(resolve, 200));
            await this.restaurarMapaHistorialEjecucion();
        },

        limpiarMapaHistorialEjecucion() {
            if (this.infoWindowAbiertoHistorial) {
                this.infoWindowAbiertoHistorial.close();
                this.infoWindowAbiertoHistorial = null;
            }
            if (this.marcadoresHistorial?.length) {
                this.marcadoresHistorial.forEach((marker) => {
                    marker.setMap(null);
                    google.maps.event.clearInstanceListeners(marker);
                });
            }
            this.marcadoresHistorial = [];
            if (this.directionsRendererHistorial) {
                this.directionsRendererHistorial.setMap(null);
                this.directionsRendererHistorial = null;
            }
            if (this.mapaHistorial) {
                google.maps.event.clearInstanceListeners(this.mapaHistorial);
                this.mapaHistorial = null;
            }
            if (this._marcadoresHistorialMapbox?.length) {
                this._marcadoresHistorialMapbox.forEach((marker) => marker.remove());
            }
            this._marcadoresHistorialMapbox = [];
            if (this.mapaHistorialMapbox) {
                this.mapaHistorialMapbox.remove();
                this.mapaHistorialMapbox = null;
            }
        },

        async restaurarMapaHistorialEjecucion() {
            if (!this.historialEjecucionMapa?.ejecucion || this.modoVistaHistorialMapa !== 'mapa') {
                return;
            }
            await this.$nextTick();
            await new Promise((resolve) => setTimeout(resolve, 200));
            if (this.proveedorMapaHistorial === 'mapbox') {
                if (this.mapaHistorialMapbox) {
                    this.mapaHistorialMapbox.resize();
                    if (!this._marcadoresHistorialMapbox?.length) {
                        await this.dibujarRutaHistorialMapbox();
                    }
                } else {
                    await this.inicializarMapaHistorialMapbox();
                    await this.dibujarRutaHistorialMapbox();
                }
                return;
            }
            if (this.mapaHistorial) {
                google.maps.event.trigger(this.mapaHistorial, 'resize');
                if (!this.marcadoresHistorial?.length) {
                    await this.dibujarRutaHistorialGoogle();
                }
                return;
            }
            await this.inicializarMapaHistorialGoogle();
        },

        async inicializarMapaHistorialGoogle() {
            const el = document.getElementById('mapaHistorialEjecucion');
            if (!el) {
                return;
            }
            try {
                this.mapaHistorial = new google.maps.Map(el, {
                    center: { lat: -31.427, lng: -62.082 },
                    zoom: 13,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                    styles: [{
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'off' }]
                    }]
                });
                await this.dibujarRutaHistorialGoogle();
                google.maps.event.trigger(this.mapaHistorial, 'resize');
            } catch (error) {
                console.error('Error al inicializar mapa historial (Google):', error);
                this.proveedorMapaHistorial = 'mapbox';
                await this.$nextTick();
                await this.inicializarMapaHistorialMapbox();
                await this.dibujarRutaHistorialMapbox();
                this.mostrarMensaje('Google Maps no disponible. Usando Mapbox.', 'warning');
            }
        },

        async inicializarMapaHistorialMapbox() {
            if (this.mapaHistorialMapbox) {
                this.mapaHistorialMapbox.remove();
            }
            mapboxgl.accessToken = this.mapboxToken;
            this.mapaHistorialMapbox = new mapboxgl.Map({
                container: 'mapaHistorialEjecucionMapbox',
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [-62.082, -31.427],
                zoom: 13
            });
            await new Promise((resolve) => this.mapaHistorialMapbox.on('load', resolve));
            this.mapaHistorialMapbox.setLayoutProperty('poi-label', 'visibility', 'none');
            this.mapaHistorialMapbox.setLayoutProperty('poi-scalerank', 'visibility', 'none');
        },

        async dibujarRutaHistorialGoogle() {
            if (!this.mapaHistorial) {
                return;
            }
            this.marcadoresHistorial.forEach((marker) => marker.setMap(null));
            this.marcadoresHistorial = [];

            const reclamos = this.historialEjecucionMapa?.reclamos || [];
            const paradasRuta = this.agruparParadasRutaVistaPrevia(reclamos);
            let contadorGruposHistorial = 0;

            for (const parada of paradasRuta) {
                const reclamoRef = parada.reclamos[0];
                const coordenadas = await this.obtenerCoordenadasReclamo(reclamoRef);
                if (!coordenadas) {
                    continue;
                }
                const colorEstado = this.colorEstadoReclamoHistorialEjecucion(reclamoRef);
                const esGrupo = parada.reclamos.length > 1;
                const marker = new google.maps.Marker({
                    position: { lat: coordenadas.lat, lng: coordenadas.lng },
                    map: this.mapaHistorial,
                    title: esGrupo
                        ? `Parada ${parada.paradaNumero}: ${parada.reclamos.length} reclamos`
                        : `Parada ${parada.paradaNumero}: Reclamo #${reclamoRef.municipalidad_id}`,
                    icon: this.crearIconoNumerado(
                        parada.paradaNumero,
                        colorEstado,
                        false,
                        null,
                        esGrupo ? null : reclamoRef.municipalidad_motivo,
                        esGrupo ? parada.reclamos.length : null
                    ),
                    zIndex: 1000,
                    optimized: false
                });
                marker._marcadorRecorridoPrincipal = true;
                marker._reclamosGrupo = parada.reclamos.map((r) => ({ ...r, posicion: parada.paradaNumero }));
                marker._reclamo = marker._reclamosGrupo[0];
                marker._indicePopup = 0;
                if (parada.reclamos.length > 1) {
                    marker._grupoId = `grupo-historial-${++contadorGruposHistorial}`;
                }
                marker.addListener('click', () => this.abrirPopupHistorialGoogle(marker));
                this.marcadoresHistorial.push(marker);
            }

            await this.trazarRutaHistorialGoogle();
            if (this.marcadoresHistorial.length) {
                const bounds = new google.maps.LatLngBounds();
                this.marcadoresHistorial.forEach((m) => bounds.extend(m.getPosition()));
                this.mapaHistorial.fitBounds(bounds);
            }
        },

        async trazarRutaHistorialGoogle() {
            const principales = this.marcadoresHistorial.filter((m) => m._marcadorRecorridoPrincipal);
            if (principales.length < 2 || !this.mapaHistorial) {
                return;
            }
            const colorRuta = this.historialEjecucionMapa?.ejecucion?.ruta_color || '#FF0000';
            const directionsService = new google.maps.DirectionsService();
            this.directionsRendererHistorial = new google.maps.DirectionsRenderer({
                suppressMarkers: true,
                preserveViewport: true,
                polylineOptions: {
                    strokeColor: colorRuta,
                    strokeOpacity: 1.0,
                    strokeWeight: 4
                }
            });
            this.directionsRendererHistorial.setMap(this.mapaHistorial);
            const coordenadas = principales.map((marker) => marker.getPosition());
            if (coordenadas.length === 2) {
                await this.trazarRutaSimpleHistorialGoogle(directionsService, coordenadas[0], coordenadas[1]);
            } else {
                await this.trazarRutaComplejaHistorialGoogle(directionsService, coordenadas);
            }
        },

        trazarRutaSimpleHistorialGoogle(directionsService, origin, destination) {
            return new Promise((resolve, reject) => {
                directionsService.route({
                    origin,
                    destination,
                    travelMode: google.maps.TravelMode.DRIVING
                }, (result, status) => {
                    if (status === 'OK') {
                        this.directionsRendererHistorial.setDirections(result);
                        resolve(result);
                    } else {
                        reject(new Error('Error al obtener direcciones: ' + status));
                    }
                });
            });
        },

        trazarRutaComplejaHistorialGoogle(directionsService, coordenadas) {
            const waypoints = coordenadas.slice(1, -1).map((c) => ({ location: c, stopover: true }));
            return new Promise((resolve, reject) => {
                directionsService.route({
                    origin: coordenadas[0],
                    destination: coordenadas[coordenadas.length - 1],
                    waypoints,
                    optimizeWaypoints: false,
                    travelMode: google.maps.TravelMode.DRIVING
                }, (result, status) => {
                    if (status === 'OK') {
                        this.directionsRendererHistorial.setDirections(result);
                        resolve(result);
                    } else {
                        reject(new Error('Error al obtener direcciones: ' + status));
                    }
                });
            });
        },

        async dibujarRutaHistorialMapbox() {
            if (!this.mapaHistorialMapbox) {
                return;
            }
            if (this.mapaHistorialMapbox.getLayer('route-historial')) {
                this.mapaHistorialMapbox.removeLayer('route-historial');
            }
            if (this.mapaHistorialMapbox.getSource('route-historial')) {
                this.mapaHistorialMapbox.removeSource('route-historial');
            }
            if (this._marcadoresHistorialMapbox?.length) {
                this._marcadoresHistorialMapbox.forEach((marker) => marker.remove());
            }
            this._marcadoresHistorialMapbox = [];

            const reclamos = this.historialEjecucionMapa?.reclamos || [];
            const paradasRuta = this.agruparParadasRutaVistaPrevia(reclamos);
            let contadorGruposHistorial = 0;

            for (const parada of paradasRuta) {
                const reclamoRef = parada.reclamos[0];
                const coordenadas = await this.obtenerCoordenadasReclamo(reclamoRef);
                if (!coordenadas) {
                    continue;
                }
                const esGrupo = parada.reclamos.length > 1;
                const el = this.crearElementoMapboxNumeradoMotivo(
                    parada.paradaNumero,
                    this.colorEstadoReclamoHistorialEjecucion(reclamoRef),
                    esGrupo ? null : reclamoRef.municipalidad_motivo,
                    32,
                    esGrupo ? parada.reclamos.length : null
                );
                const marker = this.agregarMarcadorMapboxRuta(
                    this.mapaHistorialMapbox,
                    el,
                    coordenadas,
                    'center'
                );
                marker._reclamosGrupo = parada.reclamos.map((r) => ({ ...r, posicion: parada.paradaNumero }));
                marker._reclamo = marker._reclamosGrupo[0];
                marker._indicePopup = 0;
                marker._marcadorRecorridoPrincipal = true;
                if (parada.reclamos.length > 1) {
                    marker._grupoId = `grupo-historial-mb-${++contadorGruposHistorial}`;
                }
                el.addEventListener('click', () => this.abrirPopupHistorialMapbox(marker));
                this._marcadoresHistorialMapbox.push(marker);
            }

            const reclamosTrazado = paradasRuta.map((parada) => ({
                ...parada.reclamos[0],
                posicion: parada.paradaNumero
            }));
            const colorRuta = this.historialEjecucionMapa?.ejecucion?.ruta_color || '#FF0000';
            if (reclamosTrazado.length > 1) {
                await this.trazarRutaMapboxConId(reclamosTrazado, this.mapaHistorialMapbox, colorRuta, 'route-historial');
            }
            await this.finalizarMarcadoresMapboxRuta(this.mapaHistorialMapbox);
        },

        abrirPopupHistorialGoogle(marker, indice = null) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length || !this.mapaHistorial) {
                return;
            }
            if (indice !== null) {
                marker._indicePopup = indice;
            } else if (marker._indicePopup === undefined) {
                marker._indicePopup = 0;
            }
            const reclamo = reclamos[marker._indicePopup];
            const reclamoVista = this.reclamoParaVistaHistorialEjecucion(reclamo);
            const infoWindow = marker._infoWindow || new google.maps.InfoWindow();
            marker._infoWindow = infoWindow;
            infoWindow.setContent(this.crearContenidoPopupReclamo(reclamoVista, {
                grupoId: marker._grupoId || null,
                indice: marker._indicePopup,
                total: reclamos.length,
                incluirTitulo: false,
                incluirDetalleHistorialEjecucion: true
            }));
            if (this.infoWindowAbiertoHistorial) {
                this.infoWindowAbiertoHistorial.close();
            }
            infoWindow.open(this.mapaHistorial, marker);
            this.infoWindowAbiertoHistorial = infoWindow;
            google.maps.event.addListenerOnce(infoWindow, 'domready', () => {
                setTimeout(() => this.vincularEventosPopupHistorialGoogle(marker, reclamo), 100);
            });
        },

        abrirPopupHistorialMapbox(marker, indice = null) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) {
                return;
            }
            if (indice !== null) {
                marker._indicePopup = indice;
            } else if (marker._indicePopup === undefined) {
                marker._indicePopup = 0;
            }
            const reclamo = reclamos[marker._indicePopup];
            const reclamoVista = this.reclamoParaVistaHistorialEjecucion(reclamo);
            let popup = marker.getPopup();
            if (!popup) {
                popup = new mapboxgl.Popup({ offset: 25 });
                marker.setPopup(popup);
            }
            popup.setHTML(this.crearContenidoPopupReclamo(reclamoVista, {
                grupoId: marker._grupoId || null,
                indice: marker._indicePopup,
                total: reclamos.length,
                incluirTitulo: true,
                incluirDetalleHistorialEjecucion: true
            }));
            if (!popup.isOpen()) {
                marker.togglePopup();
            } else {
                setTimeout(() => this.vincularEventosPopupHistorialMapbox(marker, reclamo), 0);
                return;
            }
            setTimeout(() => this.vincularEventosPopupHistorialMapbox(marker, reclamo), 0);
        },

        navegarPopupGrupoHistorialGoogle(marker, delta) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) {
                return;
            }
            let nuevoIndice = (marker._indicePopup || 0) + delta;
            if (nuevoIndice < 0) {
                nuevoIndice = reclamos.length - 1;
            }
            if (nuevoIndice >= reclamos.length) {
                nuevoIndice = 0;
            }
            this.abrirPopupHistorialGoogle(marker, nuevoIndice);
        },

        navegarPopupGrupoHistorialMapbox(marker, delta) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) {
                return;
            }
            let nuevoIndice = (marker._indicePopup || 0) + delta;
            if (nuevoIndice < 0) {
                nuevoIndice = reclamos.length - 1;
            }
            if (nuevoIndice >= reclamos.length) {
                nuevoIndice = 0;
            }
            this.abrirPopupHistorialMapbox(marker, nuevoIndice);
        },

        vincularEventosPopupHistorialGoogle(marker, reclamo) {
            const btnDetalle = document.querySelector(`.mapa-popup-detalle[data-reclamo-id="${reclamo.id}"]`);
            if (btnDetalle) {
                btnDetalle.onclick = () => this.verReclamoVistaPrevia(reclamo);
            }
            this.vincularAccionMaterialesPopupHistorial(reclamo);
            if (marker._grupoId) {
                const btnPrev = document.querySelector(`.mapa-popup-nav-prev[data-grupo-id="${marker._grupoId}"]`);
                if (btnPrev) {
                    btnPrev.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoHistorialGoogle(marker, -1);
                    };
                }
                const btnNext = document.querySelector(`.mapa-popup-nav-next[data-grupo-id="${marker._grupoId}"]`);
                if (btnNext) {
                    btnNext.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoHistorialGoogle(marker, 1);
                    };
                }
            }
            const headerElement = document.querySelector('.gm-style-iw-ch');
            if (headerElement) {
                headerElement.innerHTML = this.crearEncabezadoPopupReclamo(this.reclamoParaVistaHistorialEjecucion(reclamo));
            }
        },

        vincularEventosPopupHistorialMapbox(marker, reclamo) {
            const btnDetalle = document.querySelector(`.mapa-popup-detalle[data-reclamo-id="${reclamo.id}"]`);
            if (btnDetalle) {
                btnDetalle.onclick = () => this.verReclamoVistaPrevia(reclamo);
            }
            this.vincularAccionMaterialesPopupHistorial(reclamo);
            if (marker._grupoId) {
                const btnPrev = document.querySelector(`.mapa-popup-nav-prev[data-grupo-id="${marker._grupoId}"]`);
                if (btnPrev) {
                    btnPrev.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoHistorialMapbox(marker, -1);
                    };
                }
                const btnNext = document.querySelector(`.mapa-popup-nav-next[data-grupo-id="${marker._grupoId}"]`);
                if (btnNext) {
                    btnNext.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoHistorialMapbox(marker, 1);
                    };
                }
            }
        },

        async seleccionarReclamoHistorialMapa(reclamo) {
            if (this.modoVistaHistorialMapa !== 'mapa') {
                this.modoVistaHistorialMapa = 'mapa';
                await this.$nextTick();
                await this.restaurarMapaHistorialEjecucion();
            }
            await this.$nextTick();
            this.centrarEnReclamoHistorialMapa(reclamo);
        },

        centrarEnReclamoHistorialMapa(reclamo) {
            if (this.proveedorMapaHistorial === 'mapbox' && this.mapaHistorialMapbox) {
                const marker = this._marcadoresHistorialMapbox.find((m) => {
                    if (!m._marcadorRecorridoPrincipal) return false;
                    return (m._reclamosGrupo || []).some((r) => r.id === reclamo.id);
                });
                if (marker) {
                    if (marker._reclamosGrupo) {
                        const idx = marker._reclamosGrupo.findIndex((r) => r.id === reclamo.id);
                        if (idx >= 0) {
                            marker._indicePopup = idx;
                        }
                    }
                    const lngLat = marker.getLngLat();
                    this.mapaHistorialMapbox.flyTo({ center: lngLat, zoom: 16 });
                    this.abrirPopupHistorialMapbox(marker, marker._indicePopup || 0);
                }
                return;
            }
            const marker = this.marcadoresHistorial.find((m) => {
                if (!m._marcadorRecorridoPrincipal) return false;
                return (m._reclamosGrupo || []).some((r) => r.id === reclamo.id);
            });
            if (!marker || !this.mapaHistorial) {
                return;
            }
            const idx = marker._reclamosGrupo.findIndex((r) => r.id === reclamo.id);
            if (idx >= 0) {
                marker._indicePopup = idx;
            }
            this.mapaHistorial.setCenter(marker.getPosition());
            this.mapaHistorial.setZoom(16);
            marker.setAnimation(google.maps.Animation.BOUNCE);
            setTimeout(() => marker.setAnimation(null), 1500);
            this.abrirPopupHistorialGoogle(marker, marker._indicePopup || 0);
        },

        indiceReclamoEnParadaListaHistorial(parada) {
            const idx = this.indiceReclamoListaParadaHistorial[parada.clave];
            if (idx === undefined || idx >= parada.reclamos.length) {
                return 0;
            }
            return idx;
        },

        reclamoActivoEnParadaListaHistorial(parada) {
            return parada.reclamos[this.indiceReclamoEnParadaListaHistorial(parada)] || parada.reclamos[0];
        },

        navegarReclamoEnParadaListaHistorial(parada, delta) {
            if (parada.reclamos.length <= 1) {
                return;
            }
            const total = parada.reclamos.length;
            let idx = this.indiceReclamoEnParadaListaHistorial(parada);
            idx = (idx + delta + total) % total;
            this.indiceReclamoListaParadaHistorial = {
                ...this.indiceReclamoListaParadaHistorial,
                [parada.clave]: idx
            };
        },

        observacionesReclamoHistorialEjecucion(reclamo) {
            if (!reclamo?.id) {
                return [];
            }
            return this.observacionesPorReclamoHistorial[reclamo.id] || [];
        },

        cambiosEstadoReclamoHistorialEjecucion(reclamo) {
            if (!reclamo?.id) {
                return [];
            }
            return this.cambiosEstadoPorReclamoHistorial[reclamo.id] || [];
        },

        _timestampHistorialEjecucion(valor) {
            if (!valor) {
                return 0;
            }
            const t = new Date(String(valor).replace(' ', 'T')).getTime();
            return Number.isNaN(t) ? 0 : t;
        },

        lineaTiempoActividadReclamoHistorialEjecucion(reclamo) {
            if (!reclamo?.id) {
                return [];
            }
            const items = [];
            for (const c of this.cambiosEstadoReclamoHistorialEjecucion(reclamo)) {
                items.push({
                    tipo: 'cambio_estado',
                    clave: 'est-' + c.id,
                    at: c.ocurrido_at,
                    cambio: c
                });
            }
            for (const o of this.observacionesReclamoHistorialEjecucion(reclamo)) {
                items.push({
                    tipo: 'observacion',
                    clave: 'obs-' + o.id,
                    at: o.created_at,
                    observacion: o
                });
            }
            items.sort((a, b) => {
                const diff = this._timestampHistorialEjecucion(a.at) - this._timestampHistorialEjecucion(b.at);
                if (diff !== 0) {
                    return diff;
                }
                if (a.tipo !== b.tipo) {
                    return a.tipo === 'cambio_estado' ? -1 : 1;
                }
                return String(a.clave).localeCompare(String(b.clave));
            });
            return items;
        },

        crearHtmlBadgeEstadoHistorial(estado) {
            const e = String(estado || '—');
            const color = this.getColorEstado(e);
            const texto = this.colorTextoSobreEstadoReclamo(e);
            return `<span class="badge historial-mapa-estado-badge" style="background-color:${color};color:${texto}">${e}</span>`;
        },

        escHtmlBitacora(s) {
            return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
        },

        crearHtmlAvatarBitacora(entrada) {
            const nombre = entrada?.usuario_nombre || '—';
            const esc = (v) => this.escHtmlBitacora(v);
            if (entrada?.usuario_foto_perfil) {
                const url = esc(this.urlFotoOperario(entrada.usuario_foto_perfil));
                return `<img class="bitacora-obra-msg__avatar bitacora-obra-msg__avatar--img" src="${url}" alt="${esc(nombre)}" loading="lazy">`;
            }
            return `<span class="bitacora-obra-msg__avatar bitacora-obra-msg__avatar--iniciales" style="background-color:${esc(this.colorAvatarOperario(nombre))}">${esc(this.inicialesOperario(nombre))}</span>`;
        },

        crearHtmlEncabezadoBitacora(entrada, tipoIcon, tipoLabel) {
            const esc = (v) => this.escHtmlBitacora(v);
            let rutaHtml = '';
            if (entrada?.ruta_nombre) {
                rutaHtml = `<span class="bitacora-obra-msg__sep" aria-hidden="true">·</span>`
                    + `<span class="bitacora-obra-msg__ruta" style="color:${esc(entrada.ruta_color || '#6c757d')}">`
                    + ObraCronometroUtil.SVG_ICONO_RUTA.replace('cronometro-badge-ico-ruta', 'bitacora-obra-msg__ruta-ico cronometro-badge-ico-ruta')
                    + `<span>${esc(entrada.ruta_nombre)}</span></span>`;
            }
            return `<div class="bitacora-obra-msg__encabezado">`
                + `<span class="bitacora-obra-msg__usuario">${esc(entrada?.usuario_nombre || '—')}</span>`
                + `<span class="bitacora-obra-msg__sep" aria-hidden="true">·</span>`
                + `<span class="bitacora-obra-msg__tipo"><i class="bi ${tipoIcon}" aria-hidden="true"></i> ${tipoLabel}</span>`
                + rutaHtml
                + `</div>`;
        },

        wrapHtmlMensajeBitacora(entrada, encabezadoHtml, contenidoHtml, fecha) {
            return `<li class="bitacora-obra-msg bitacora-obra-msg--compact historial-mapa-observacion mb-2">`
                + `<div class="bitacora-obra-msg__layout">`
                + `<div class="bitacora-obra-msg__avatar-col" aria-hidden="true">${this.crearHtmlAvatarBitacora(entrada)}</div>`
                + `<div class="bitacora-obra-msg__stack">`
                + encabezadoHtml
                + `<div class="bitacora-obra-msg__bubble">`
                + `<div class="bitacora-obra-msg__contenido">${contenidoHtml}</div>`
                + `<time class="bitacora-obra-msg__hora">${this.formatearFecha(fecha)}</time>`
                + `</div></div></div></li>`;
        },

        crearHtmlEventoCambioEstadoBitacora(cambio, fecha) {
            const esc = (v) => this.escHtmlBitacora(v);
            let rutaHtml = '';
            if (cambio?.ruta_nombre) {
                rutaHtml = `<span class="bitacora-obra-msg__sep" aria-hidden="true">·</span>`
                    + `<span class="bitacora-obra-evento__ruta" style="color:${esc(cambio.ruta_color || '#6c757d')}">`
                    + ObraCronometroUtil.SVG_ICONO_RUTA.replace('cronometro-badge-ico-ruta', 'bitacora-obra-evento__ruta-ico cronometro-badge-ico-ruta')
                    + `<span>${esc(cambio.ruta_nombre)}</span></span>`;
            }
            const transicion = `<div class="bitacora-obra-evento__transicion historial-mapa-cambio-estado d-flex flex-wrap align-items-center gap-1">`
                + this.crearHtmlBadgeEstadoHistorial(cambio.estado_anterior)
                + `<i class="bi bi-arrow-right small text-muted px-1" aria-hidden="true"></i>`
                + this.crearHtmlBadgeEstadoHistorial(cambio.estado_nuevo)
                + `</div>`;
            return `<li class="bitacora-obra-evento-estado bitacora-obra-evento-estado--compact historial-mapa-observacion mb-2">`
                + `<div class="bitacora-obra-evento">`
                + `<div class="bitacora-obra-evento__fila">`
                + `<div class="bitacora-obra-evento__cuerpo">`
                + `<div class="bitacora-obra-evento__meta">`
                + `<span class="bitacora-obra-evento__usuario">${esc(cambio?.usuario_nombre || '—')}</span>`
                + `<span class="bitacora-obra-evento__etiqueta">Estado</span>`
                + rutaHtml
                + `</div>`
                + transicion
                + `<time class="bitacora-obra-evento__hora">${this.formatearFecha(fecha)}</time>`
                + `</div>`
                + `<span class="bitacora-obra-evento__ico" aria-hidden="true"><i class="bi bi-arrow-left-right"></i></span>`
                + `</div></div></li>`;
        },

        crearHtmlLineaCambioEstadoHistorial(cambio) {
            return this.crearHtmlEventoCambioEstadoBitacora(cambio, cambio.ocurrido_at);
        },

        crearHtmlLineaObservacionHistorial(obs) {
            if (this.esEntradaFotoBitacoraObra(obs)) {
                const esc = (s) => this.escHtmlBitacora(s);
                const url = this.urlFotoBitacoraObra(obs).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
                const caption = obs.texto ? esc(obs.texto) : '';
                let contenido = `<button type="button" class="bitacora-obra-foto-link d-inline-block" data-bitacora-foto-ampliar="${url}" data-bitacora-foto-caption="${caption}">`
                    + `<img src="${url}" alt="Foto en obra" class="bitacora-obra-foto-thumb" loading="lazy">`
                    + `</button>`;
                if (obs.texto) {
                    contenido += `<p class="mb-0 mt-2 text-break small">${esc(obs.texto)}</p>`;
                }
                return this.wrapHtmlMensajeBitacora(
                    obs,
                    this.crearHtmlEncabezadoBitacora(obs, 'bi-camera-fill', 'Foto'),
                    contenido,
                    obs.created_at
                );
            }
            const texto = this.escHtmlBitacora(obs.texto || '');
            return this.wrapHtmlMensajeBitacora(
                obs,
                this.crearHtmlEncabezadoBitacora(obs, 'bi-chat-left-text', 'Nota'),
                `<p class="mb-0 text-break">${texto}</p>`,
                obs.created_at
            );
        },

        esEntradaFotoBitacoraObra(entrada) {
            if (this.esEntradaCambioEstadoBitacoraObra(entrada)) {
                return false;
            }
            return !!(entrada && (entrada.tipo === 'foto' || entrada.archivo));
        },

        esEntradaCambioEstadoBitacoraObra(entrada) {
            return entrada?.bitacora_tipo === 'cambio_estado';
        },

        urlFotoBitacoraObra(entrada) {
            if (!entrada) {
                return '';
            }
            if (entrada.url_foto) {
                return entrada.url_foto;
            }
            if (entrada.archivo) {
                return BASE_URL + 'static/uploads/obra_reclamos/' + entrada.archivo;
            }
            return '';
        },

        abrirModalFotoBitacoraObra(url, caption = '') {
            if (!url) {
                return;
            }
            this.bitacoraFotoAmpliadaUrl = url;
            this.bitacoraFotoAmpliadaCaption = (caption || '').trim();
            this.bitacoraFotoAmpliadaActiva = true;
            document.body.classList.add('bitacora-foto-obra-lightbox-open');
            this._onEscapeFotoBitacora = (event) => {
                if (event.key === 'Escape') {
                    this.cerrarModalFotoBitacoraObra();
                }
            };
            document.addEventListener('keydown', this._onEscapeFotoBitacora);
        },

        cerrarModalFotoBitacoraObra() {
            this.bitacoraFotoAmpliadaActiva = false;
            this.bitacoraFotoAmpliadaUrl = '';
            this.bitacoraFotoAmpliadaCaption = '';
            document.body.classList.remove('bitacora-foto-obra-lightbox-open');
            if (this._onEscapeFotoBitacora) {
                document.removeEventListener('keydown', this._onEscapeFotoBitacora);
                this._onEscapeFotoBitacora = null;
            }
        },

        onClickBitacoraFotoAmpliar(event) {
            const btn = event.target.closest('[data-bitacora-foto-ampliar]');
            if (!btn) {
                return;
            }
            event.preventDefault();
            const url = btn.getAttribute('data-bitacora-foto-ampliar');
            const caption = btn.getAttribute('data-bitacora-foto-caption') || '';
            this.abrirModalFotoBitacoraObra(url, caption);
        },

        crearHtmlLineaTiempoHistorialEjecucion(item) {
            if (item.tipo === 'cambio_estado') {
                return this.crearHtmlLineaCambioEstadoHistorial(item.cambio);
            }
            if (item.tipo === 'observacion') {
                return this.crearHtmlLineaObservacionHistorial(item.observacion);
            }
            return '';
        },

        estadoReclamoHistorialEjecucion(reclamo) {
            if (!reclamo) {
                return '';
            }
            return reclamo.estado_al_cierre_ejecucion ?? reclamo.municipalidad_estado ?? '';
        },

        reclamoParaVistaHistorialEjecucion(reclamo) {
            if (!reclamo) {
                return reclamo;
            }
            return {
                ...reclamo,
                municipalidad_estado: this.estadoReclamoHistorialEjecucion(reclamo)
            };
        },

        colorEstadoReclamoHistorialEjecucion(reclamo) {
            return this.getColorEstado(this.estadoReclamoHistorialEjecucion(reclamo));
        },

        textoTiempoReparacionHistorialEjecucion(reclamo) {
            const sr = reclamo?.sesion_reparacion;
            if (!sr) {
                return '';
            }
            const ms = Number(sr.acumulado_ms) || 0;
            if (ms <= 0) {
                return '';
            }
            const sec = Math.max(0, Math.floor(ms / 1000));
            return this.formatearSegundosCronometroSupervisor(sec);
        },

        msObraReclamoHistorialEjecucion(reclamo) {
            const sr = reclamo?.sesion_reparacion;
            if (!sr) {
                return 0;
            }
            return Math.max(0, Number(sr.acumulado_ms) || 0);
        },

        nivelDemoraObraReclamoHistorial(reclamo) {
            const motivo = reclamo?.municipalidad_motivo || '';
            const promedio = ObraCronometroUtil.promedioMinutosMotivo(this.promediosTiempoMotivoMap, motivo);
            return ObraCronometroUtil.nivelDemoraObra(this.msObraReclamoHistorialEjecucion(reclamo), promedio);
        },

        claseCronometroListaObraHistorial(reclamo) {
            if (!this.textoTiempoReparacionHistorialEjecucion(reclamo)) {
                return '';
            }
            return ObraCronometroUtil.claseListaCronoObra(
                this.nivelDemoraObraReclamoHistorial(reclamo),
                true
            );
        },

        crearHtmlDetalleHistorialEjecucionPopup(reclamo) {
            const tiempo = this.textoTiempoReparacionHistorialEjecucion(reclamo);
            const linea = this.lineaTiempoActividadReclamoHistorialEjecucion(reclamo);
            const matCount = this.cantidadMaterialesReclamoHistorial(reclamo);
            const tituloMat = matCount > 0
                ? `Materiales utilizados (${matCount})`
                : 'Materiales utilizados';
            const badgeMat = this.textoObservacionesEjecucionBadge(matCount) || '0';
            const ocultoMat = matCount > 0 ? '' : ' btn-obs-ejecucion-count--oculto';
            let html = '<div class="map-detalle-iw-historial border-top pt-2 mt-2">';
            html += '<div class="map-detalle-iw-historial__acciones">';
            let htmlInicio = '';
            if (tiempo) {
                const nivel = this.nivelDemoraObraReclamoHistorial(reclamo);
                const claseCrono = ObraCronometroUtil.claseListaCronoObra(nivel, true);
                htmlInicio = ObraCronometroUtil.htmlSpanCronometroBadge(
                    `ruta-secuencia-crono-reparacion badge font-monospace ${claseCrono}`,
                    tiempo,
                    'reclamo'
                );
            }
            if (htmlInicio) {
                html += `<div class="map-detalle-iw-historial__inicio">${htmlInicio}</div>`;
            }
            html += `<div class="map-detalle-iw-historial__paneles"><button type="button" class="btn btn-sm btn-outline-secondary btn-con-badge-obs" data-map-accion-historial="materiales" data-reclamo-id="${reclamo.id}" title="${tituloMat}"><i class="bi bi-box-seam"></i><span class="btn-obs-ejecucion-count${ocultoMat}" aria-hidden="true">${badgeMat}</span></button></div>`;
            html += '</div>';
            if (linea.length) {
                html += '<p class="mb-1 small text-muted"><strong>Actividad en esta ejecución:</strong></p><ul class="list-unstyled mb-0 ps-0">';
                for (const item of linea) {
                    html += this.crearHtmlLineaTiempoHistorialEjecucion(item);
                }
                html += '</ul>';
            } else if (!tiempo) {
                html += '<p class="mb-0 small text-muted">Sin actividad registrada en esta ejecución.</p>';
            }
            html += '</div>';
            return html;
        },

        vincularAccionMaterialesPopupHistorial(reclamo) {
            document.querySelectorAll(`[data-map-accion-historial="materiales"][data-reclamo-id="${reclamo.id}"]`).forEach((btn) => {
                btn.onclick = (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    this.abrirModalMaterialesSupervisor(reclamo);
                };
            });
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
                const [responseReclamos, responseDisponibles] = await Promise.all([
                    axios.get(BASE_URL + 'api/reclamos'),
                    axios.get(BASE_URL + 'api/rutas/domicilios-disponibles')
                ]);
                this.reclamos = responseReclamos.data;
                this.idsReclamosEnRutasActivas = (responseDisponibles.data?.idsReclamosEnRutasActivas || [])
                    .map((id) => Number(id));
                this.reclamosDisponibles = responseDisponibles.data?.domiciliosDisponibles
                    ?? this.contarUnidadesDomicilioDisponibles();
                
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
                    ObraCronometroUtil.actualizarTextoCronometroBadge(el, '—', 'ruta');
                    ObraCronometroUtil.sincronizarClasesCronometroEjecucionRuta(el, null, 'cronometro-ruta-supervisor');
                    return;
                }
                const t0 = new Date(String(raw).replace(' ', 'T')).getTime();
                if (Number.isNaN(t0)) {
                    ObraCronometroUtil.actualizarTextoCronometroBadge(el, '—', 'ruta');
                    ObraCronometroUtil.sincronizarClasesCronometroEjecucionRuta(el, null, 'cronometro-ruta-supervisor');
                    return;
                }
                const ms = Math.max(0, ahora - t0);
                const sec = Math.floor(ms / 1000);
                ObraCronometroUtil.actualizarTextoCronometroBadge(
                    el,
                    this.formatearSegundosCronometroSupervisor(sec),
                    'ruta'
                );
                const nivel = ObraCronometroUtil.nivelDemoraEjecucionRuta(
                    ms,
                    el.getAttribute('data-tiempo-estimado') || ''
                );
                ObraCronometroUtil.sincronizarClasesCronometroEjecucionRuta(el, nivel, 'cronometro-ruta-supervisor');
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

        iniciarPollingSupervisorActivas() {
            if (!this.esSupervisorVistaTarjetas || this._pollSupervisorActivas) {
                return;
            }
            this._pollSupervisorActivas = setInterval(() => {
                if (document.hidden) {
                    return;
                }
                void this.sincronizarVistaSupervisorActivas();
            }, this.intervaloPollSupervisorActivas);
        },

        detenerPollingSupervisorActivas() {
            if (this._pollSupervisorActivas) {
                clearInterval(this._pollSupervisorActivas);
                this._pollSupervisorActivas = null;
            }
        },

        async sincronizarVistaSupervisorActivas() {
            if (!this.esSupervisorVistaTarjetas || this.solapaRutas !== 'activas') {
                return;
            }
            if (this._sincronizandoSupervisorActivas) {
                return;
            }
            this._sincronizandoSupervisorActivas = true;
            try {
                const response = await axios.get(BASE_URL + 'api/rutas');
                this.rutas = response.data || [];

                if (this.rutaDetalleSupervisorId) {
                    const ruta = this.rutas.find((r) => String(r.id) === String(this.rutaDetalleSupervisorId));
                    if (ruta) {
                        await this.recargarDatosDetalleSupervisor(ruta.id, { silencioso: true });
                    } else {
                        this.cerrarModalDetalleSupervisor();
                    }
                } else if (this.modalTodasHojasAbierto()) {
                    await this.refrescarVistaTodasHojasTrasSync();
                } else {
                    await this.actualizarPreviewsSupervisorTrasSync();
                }
            } catch (error) {
                console.warn('Sincronización supervisor (hojas activas):', error);
            } finally {
                this._sincronizandoSupervisorActivas = false;
            }
        },

        async actualizarPreviewsSupervisorTrasSync() {
            if (this.rutaDetalleSupervisorId || !this.esSupervisorVistaTarjetas) {
                return;
            }
            const rutasConMapa = this.rutasActivasPanel.filter((r) => this.mapasPreviewSupervisor[r.id]);
            for (const ruta of rutasConMapa) {
                await this.actualizarMapaPreviewSupervisor(ruta, { preservarVista: true });
                await new Promise((resolve) => setTimeout(resolve, 40));
            }
        },

        async _crearMarcadoresPreviewSupervisor(map, reclamos, colorRuta) {
            const markers = [];
            const bounds = new google.maps.LatLngBounds();
            const paradasRuta = this.agruparParadasRutaVistaPrevia(reclamos);

            for (const parada of paradasRuta) {
                const reclamoRef = parada.reclamos[0];
                const coords = await this.obtenerCoordenadasReclamo(reclamoRef);
                if (!coords) continue;

                const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(parada.reclamos);
                const cantidadParada = parada.reclamos.length;
                const esGrupo = cantidadParada > 1;
                const motivoBadge = esGrupo ? null : reclamoRef.municipalidad_motivo;
                const badgeCantidad = esGrupo ? cantidadParada : null;

                const marker = new google.maps.Marker({
                    position: { lat: coords.lat, lng: coords.lng },
                    map,
                    title: esGrupo
                        ? `Parada ${parada.paradaNumero}: ${cantidadParada} reclamos en el mismo domicilio`
                        : `Posición ${parada.paradaNumero}: Reclamo #${reclamoRef.municipalidad_id}`,
                    icon: this.crearIconoNumerado(
                        parada.paradaNumero,
                        colorEstado,
                        prioridadAlta,
                        26,
                        motivoBadge,
                        badgeCantidad
                    ),
                    zIndex: 100
                });
                marker._marcadorRecorridoPrincipal = true;
                marker._reclamosGrupo = parada.reclamos;
                markers.push(marker);
                bounds.extend({ lat: coords.lat, lng: coords.lng });
            }

            let directionsRenderer = null;
            const principales = markers.filter((m) => m._marcadorRecorridoPrincipal);
            if (principales.length >= 2) {
                directionsRenderer = new google.maps.DirectionsRenderer({
                    suppressMarkers: true,
                    preserveViewport: true,
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

            return { markers, bounds, directionsRenderer };
        },

        _fingerprintPreviewSupervisorRuta(reclamos, ruta) {
            const partes = (reclamos || []).map((r) =>
                `${r.id}|${String(r.municipalidad_estado || '').trim()}|${String(r.prioridad || '').trim()}`
            );
            const meta = ruta
                ? `${ruta.estado_ejecucion || ''}|${ruta.inicio_ejecucion_at || ''}|${ruta.color || ''}`
                : '';
            return `${meta}|${partes.join(';')}`;
        },

        _fingerprintEstructuraPreviewSupervisorRuta(reclamos) {
            return (reclamos || []).map((r) => r.id).join(',');
        },

        async _actualizarMarcadoresPreviewSupervisorInplace(ref, reclamos, colorRuta) {
            const paradas = this.agruparParadasRutaVistaPrevia(reclamos);
            const principales = (ref.markers || []).filter((m) => m._marcadorRecorridoPrincipal);
            if (!principales.length || principales.length !== paradas.length) {
                return false;
            }

            for (const marker of principales) {
                const parada = this._buscarParadaPorMarkerVisualizacion(marker, paradas);
                if (!parada) {
                    return false;
                }
                const reclamoRef = parada.reclamos[0];
                const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(parada.reclamos);
                const cantidadParada = parada.reclamos.length;
                const esGrupo = cantidadParada > 1;
                const motivoBadge = esGrupo ? null : reclamoRef.municipalidad_motivo;
                const badgeCantidad = esGrupo ? cantidadParada : null;

                marker.setIcon(this.crearIconoNumerado(
                    parada.paradaNumero,
                    colorEstado,
                    prioridadAlta,
                    26,
                    motivoBadge,
                    badgeCantidad
                ));
                marker._reclamosGrupo = parada.reclamos;
            }
            return true;
        },

        async actualizarMapaPreviewSupervisor(ruta, opciones = {}) {
            const { preservarVista = false } = opciones;
            const ref = this.mapasPreviewSupervisor[ruta.id];
            if (!ref?.map || !window.google?.maps) {
                return;
            }
            try {
                delete this.reclamosCachePorRutaId[ruta.id];
                const reclamos = await this.obtenerReclamosRutaCache(ruta.id);
                const colorRuta = ruta.color || '#FF6B35';
                const fingerprint = this._fingerprintPreviewSupervisorRuta(reclamos, ruta);
                const estructura = this._fingerprintEstructuraPreviewSupervisorRuta(reclamos);

                if (fingerprint === ref._ultimoFingerprint) {
                    return;
                }

                const puedeActualizarInplace = ref.markers?.length > 0
                    && ref.directionsRenderer
                    && estructura === ref._ultimaEstructura;

                if (puedeActualizarInplace) {
                    const ok = await this._actualizarMarcadoresPreviewSupervisorInplace(ref, reclamos, colorRuta);
                    if (ok) {
                        ref._ultimoFingerprint = fingerprint;
                        return;
                    }
                }

                ref.markers?.forEach((m) => m.setMap(null));
                if (ref.directionsRenderer) {
                    ref.directionsRenderer.setMap(null);
                }
                const { markers, bounds, directionsRenderer } = await this._crearMarcadoresPreviewSupervisor(
                    ref.map,
                    reclamos,
                    colorRuta
                );
                ref.markers = markers;
                ref.directionsRenderer = directionsRenderer;
                ref._ultimoFingerprint = fingerprint;
                ref._ultimaEstructura = estructura;
                if (markers.length > 0 && !preservarVista) {
                    ref.map.fitBounds(bounds, 20);
                }
            } catch (error) {
                console.warn('No se pudo actualizar vista previa de ruta', ruta.id, error);
            }
        },

        _fingerprintDatosMapaDetalleSupervisor() {
            const ruta = this.rutaVisualizando || {};
            const partes = (this.reclamosRutaVisualizando || []).map((r) => {
                const estado = String(r.municipalidad_estado || '').trim();
                const sr = r.sesion_reparacion || this.reparacionPorReclamoIdSupervisor?.[r.id];
                const ses = sr ? `${sr.activo ? 1 : 0}` : '';
                return `${r.id}|${estado}|${ses}`;
            });
            return `${ruta.estado_ejecucion || ''}|${ruta.inicio_ejecucion_at || ''}|${partes.join(';')}`;
        },

        capturarVistaMapaVisualizacion() {
            if (this.proveedorMapaVisualizacion === 'mapbox' && this.mapaVisualizacionMapbox) {
                return {
                    tipo: 'mapbox',
                    center: this.mapaVisualizacionMapbox.getCenter().toArray(),
                    zoom: this.mapaVisualizacionMapbox.getZoom(),
                    bearing: this.mapaVisualizacionMapbox.getBearing(),
                    pitch: this.mapaVisualizacionMapbox.getPitch()
                };
            }
            if (this.mapaVisualizacion) {
                const centro = this.mapaVisualizacion.getCenter();
                return {
                    tipo: 'google',
                    lat: centro.lat(),
                    lng: centro.lng(),
                    zoom: this.mapaVisualizacion.getZoom()
                };
            }
            return null;
        },

        restaurarVistaMapaVisualizacion(vista) {
            if (!vista) {
                return;
            }
            if (vista.tipo === 'mapbox' && this.mapaVisualizacionMapbox) {
                this.mapaVisualizacionMapbox.jumpTo({
                    center: vista.center,
                    zoom: vista.zoom,
                    bearing: vista.bearing,
                    pitch: vista.pitch
                });
            } else if (vista.tipo === 'google' && this.mapaVisualizacion) {
                this.mapaVisualizacion.setCenter({ lat: vista.lat, lng: vista.lng });
                this.mapaVisualizacion.setZoom(vista.zoom);
            }
        },

        _buscarParadaPorMarkerVisualizacion(marker, paradas) {
            const idsGrupo = new Set((marker._reclamosGrupo || []).map((r) => Number(r.id)));
            if (!idsGrupo.size && marker._reclamo?.id != null) {
                idsGrupo.add(Number(marker._reclamo.id));
            }
            return paradas.find((p) => p.reclamos.some((r) => idsGrupo.has(Number(r.id))));
        },

        _puedeActualizarMarcadoresVisualizacionInplace(paradas) {
            if (this.proveedorMapaVisualizacion === 'mapbox') {
                const principales = (this._marcadoresVisualizacionMapbox || []).filter((m) => m._marcadorRecorridoPrincipal);
                return principales.length > 0 && principales.length === paradas.length;
            }
            const principales = (this.marcadoresVisualizacion || []).filter((m) => m._marcadorRecorridoPrincipal);
            return principales.length > 0 && principales.length === paradas.length;
        },

        _limpiarMarcadoresObraMapboxVisualizacion() {
            if (this._mapboxObraVisualizacionMarkers?.length) {
                this._mapboxObraVisualizacionMarkers.forEach((marker) => marker.remove());
            }
            this._mapboxObraVisualizacionMarkers = [];
            this.mapboxObraVisualizacionRefs = [];
        },

        _reemplazarElementoMarcadorMapboxVisualizacion(marker, parada) {
            const reclamoRef = parada.reclamos[0];
            const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
            const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(parada.reclamos);
            const cantidadParada = parada.reclamos.length;
            const esGrupo = cantidadParada > 1;
            const motivoBadge = esGrupo ? null : reclamoRef.municipalidad_motivo;
            const badgeCantidad = esGrupo ? cantidadParada : null;
            const reclamosGrupo = parada.reclamos.map((r) => ({
                ...r,
                posicion: parada.paradaNumero
            }));

            const lngLat = marker.getLngLat();
            const grupoId = marker._grupoId || null;
            const indicePopup = marker._indicePopup || 0;

            marker.remove();

            const el = this.crearElementoMapboxNumeradoMotivo(
                parada.paradaNumero,
                colorEstado,
                motivoBadge,
                32,
                badgeCantidad,
                prioridadAlta
            );
            const nuevoMarker = this.agregarMarcadorMapboxRuta(
                this.mapaVisualizacionMapbox,
                el,
                { lng: lngLat.lng, lat: lngLat.lat },
                'center'
            );

            nuevoMarker._reclamo = reclamosGrupo[0];
            nuevoMarker._reclamosGrupo = reclamosGrupo;
            nuevoMarker._indicePopup = indicePopup;
            nuevoMarker._marcadorRecorridoPrincipal = true;
            if (grupoId) {
                nuevoMarker._grupoId = grupoId;
            } else if (esGrupo) {
                nuevoMarker._grupoId = `grupo-visualizacion-mb-${parada.clave}`;
            }

            el.addEventListener('click', () => {
                this.abrirPopupVisualizacionMapbox(nuevoMarker);
            });

            const idx = this._marcadoresVisualizacionMapbox.indexOf(marker);
            if (idx >= 0) {
                this._marcadoresVisualizacionMapbox[idx] = nuevoMarker;
            } else {
                this._marcadoresVisualizacionMapbox.push(nuevoMarker);
            }

            return nuevoMarker;
        },

        _agregarMarcadoresObraMapboxVisualizacionParada(parada, coordenadas) {
            for (let i = 0; i < parada.reclamos.length; i++) {
                const reclamo = parada.reclamos[i];
                if (!this.reclamoMuestraIndicadorObraSupervisorMapa(reclamo)) {
                    continue;
                }
                const hms = this.textoCronometroObraSupervisor(reclamo);
                const nivel = this.nivelDemoraObraReclamoSupervisor(reclamo);
                const { wrap, span } = ObraCronometroUtil.crearElementoIndicadorObraMapbox(hms, nivel);
                const offsetLng = 0.00028 + (i * 0.00006);
                const obraMarker = new mapboxgl.Marker({ element: wrap, anchor: 'left' })
                    .setLngLat([coordenadas.lng + offsetLng, coordenadas.lat])
                    .addTo(this.mapaVisualizacionMapbox);
                this._mapboxObraVisualizacionMarkers.push(obraMarker);
                if (span) {
                    this.mapboxObraVisualizacionRefs.push({ reclamoId: reclamo.id, span, wrap });
                }
            }
        },

        _reclamoFreshVisualizacion(reclamo) {
            if (!reclamo?.id) {
                return reclamo;
            }
            return this.reclamosRutaVisualizando.find((x) => Number(x.id) === Number(reclamo.id)) || reclamo;
        },

        _quitarCompanionObraGoogle(ref) {
            if (!ref) {
                return;
            }
            if (ref.overlay) {
                ref.overlay.setMap(null);
            } else if (typeof ref.setMap === 'function') {
                ref.setMap(null);
            }
        },

        _crearCompanionObraGoogleOverlay(latLng, map, reclamo) {
            const hms = this.textoCronometroObraSupervisor(reclamo);
            const nivel = this.nivelDemoraObraReclamoSupervisor(reclamo);
            const companion = ObraCronometroUtil.crearCompanionObraGoogleOverlay(latLng, map, hms, nivel);
            return {
                ...companion,
                _reclamoIdObra: reclamo.id,
                _marcadorObraCompanion: true
            };
        },

        _quitarMarkerGoogleDelMapa(marker) {
            if (!marker) {
                return;
            }
            try {
                marker.setMap(null);
                marker.setVisible(false);
            } catch (e) { /* ignore */ }
        },

        _limpiarTodosCompanionsObraGoogleVisualizacion() {
            (this._googleObraVisualizacionMarkers || []).forEach((ref) => this._quitarCompanionObraGoogle(ref));
            this._googleObraVisualizacionMarkers = [];

            (this.marcadoresVisualizacion || []).forEach((marker) => {
                if (!marker._marcadorRecorridoPrincipal) {
                    return;
                }
                (marker._companionsObra || []).forEach((c) => this._quitarCompanionObraGoogle(c));
                this._quitarCompanionObraGoogle(marker._companionObra);
                marker._companionsObra = [];
                marker._companionObra = null;
            });
        },

        _registrarCompanionObraGoogleVisualizacion(companionRef, principal) {
            if (!companionRef) {
                return;
            }
            if (!this._googleObraVisualizacionMarkers.includes(companionRef)) {
                this._googleObraVisualizacionMarkers.push(companionRef);
            }
            if (principal) {
                principal._companionsObra = principal._companionsObra || [];
                if (!principal._companionsObra.includes(companionRef)) {
                    principal._companionsObra.push(companionRef);
                }
                principal._companionObra = companionRef;
            }
        },

        _sincronizarCompanionsObraGoogleVisualizacion() {
            if (!this.mapaVisualizacion) {
                this._limpiarTodosCompanionsObraGoogleVisualizacion();
                return;
            }

            const esperados = new Map();
            const paradas = this.agruparParadasRutaVistaPrevia(this.reclamosRutaVisualizando);
            for (const marker of this.marcadoresVisualizacion) {
                if (!marker._marcadorRecorridoPrincipal) {
                    continue;
                }
                const parada = this._buscarParadaPorMarkerVisualizacion(marker, paradas);
                if (!parada) {
                    continue;
                }
                const coordenadas = marker.getPosition();
                if (!coordenadas) {
                    continue;
                }
                for (let i = 0; i < parada.reclamos.length; i++) {
                    const reclamo = this._reclamoFreshVisualizacion(parada.reclamos[i]);
                    if (!this.reclamoMuestraIndicadorObraSupervisorMapa(reclamo)) {
                        continue;
                    }
                    const offsetLng = 0.00028 + (i * 0.00006);
                    esperados.set(Number(reclamo.id), {
                        reclamo,
                        latLng: new google.maps.LatLng(coordenadas.lat(), coordenadas.lng() + offsetLng),
                        principal: marker
                    });
                }
            }

            this._googleObraVisualizacionMarkers = (this._googleObraVisualizacionMarkers || []).filter((ref) => {
                const id = Number(ref._reclamoIdObra);
                if (!esperados.has(id)) {
                    this._quitarCompanionObraGoogle(ref);
                    (this.marcadoresVisualizacion || []).forEach((m) => {
                        if (m._companionsObra?.length) {
                            m._companionsObra = m._companionsObra.filter((c) => c !== ref);
                            if (m._companionObra === ref) {
                                m._companionObra = m._companionsObra[0] || null;
                            }
                        }
                    });
                    return false;
                }
                return true;
            });

            const existentes = new Set(this._googleObraVisualizacionMarkers.map((r) => Number(r._reclamoIdObra)));
            for (const [id, data] of esperados) {
                if (existentes.has(id)) {
                    continue;
                }
                const companionRef = this._crearCompanionObraGoogleOverlay(
                    data.latLng,
                    this.mapaVisualizacion,
                    data.reclamo
                );
                this._registrarCompanionObraGoogleVisualizacion(companionRef, data.principal);
            }
        },

        _limpiarTodosCompanionsObraGoogleRutasActivas() {
            (this._googleObraRutasActivasMarkers || []).forEach((ref) => this._quitarCompanionObraGoogle(ref));
            this._googleObraRutasActivasMarkers = [];

            (this.marcadoresRutasActivas || []).forEach((marker) => {
                if (!marker._marcadorRecorridoPrincipal) {
                    return;
                }
                (marker._companionsObra || []).forEach((c) => this._quitarCompanionObraGoogle(c));
                this._quitarCompanionObraGoogle(marker._companionObra);
                marker._companionsObra = [];
                marker._companionObra = null;
            });
        },

        _registrarCompanionObraGoogleRutasActivas(companionRef, principal) {
            if (!companionRef) {
                return;
            }
            if (!this._googleObraRutasActivasMarkers.includes(companionRef)) {
                this._googleObraRutasActivasMarkers.push(companionRef);
            }
            if (principal) {
                principal._companionsObra = principal._companionsObra || [];
                if (!principal._companionsObra.includes(companionRef)) {
                    principal._companionsObra.push(companionRef);
                }
                principal._companionObra = companionRef;
            }
        },

        _sincronizarCompanionsObraGoogleRutasActivas() {
            if (!this.mapaRutasActivas) {
                this._limpiarTodosCompanionsObraGoogleRutasActivas();
                return;
            }

            const esperados = new Map();
            for (const marker of this.marcadoresRutasActivas) {
                if (!marker._marcadorRecorridoPrincipal || !marker._ruta) {
                    continue;
                }
                const coordenadas = marker.getPosition();
                if (!coordenadas) {
                    continue;
                }
                const reclamosGrupo = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
                for (let i = 0; i < reclamosGrupo.length; i++) {
                    const reclamo = reclamosGrupo[i];
                    if (!this.reclamoMuestraIndicadorObraEnRuta(reclamo, marker._ruta)) {
                        continue;
                    }
                    const offsetLng = 0.00028 + (i * 0.00006);
                    esperados.set(Number(reclamo.id), {
                        reclamo,
                        latLng: new google.maps.LatLng(coordenadas.lat(), coordenadas.lng() + offsetLng),
                        principal: marker
                    });
                }
            }

            this._googleObraRutasActivasMarkers = (this._googleObraRutasActivasMarkers || []).filter((ref) => {
                const id = Number(ref._reclamoIdObra);
                if (!esperados.has(id)) {
                    this._quitarCompanionObraGoogle(ref);
                    return false;
                }
                return true;
            });

            const existentes = new Set(this._googleObraRutasActivasMarkers.map((r) => Number(r._reclamoIdObra)));
            for (const [id, data] of esperados) {
                if (existentes.has(id)) {
                    continue;
                }
                const companionRef = this._crearCompanionObraGoogleOverlay(
                    data.latLng,
                    this.mapaRutasActivas,
                    data.reclamo
                );
                this._registrarCompanionObraGoogleRutasActivas(companionRef, data.principal);
            }
        },

        async _actualizarMarcadoresGoogleVisualizacionInplace(paradas) {
            for (const marker of this.marcadoresVisualizacion) {
                if (!marker._marcadorRecorridoPrincipal) {
                    continue;
                }
                const parada = this._buscarParadaPorMarkerVisualizacion(marker, paradas);
                if (!parada) {
                    continue;
                }
                const reclamoRef = parada.reclamos[0];
                const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(parada.reclamos);
                const cantidadParada = parada.reclamos.length;
                const esGrupo = cantidadParada > 1;
                const motivoBadge = esGrupo ? null : reclamoRef.municipalidad_motivo;
                const badgeCantidad = esGrupo ? cantidadParada : null;
                const reclamosGrupo = parada.reclamos.map((r) => ({
                    ...r,
                    posicion: parada.paradaNumero
                }));

                marker.setIcon(this.crearIconoNumerado(
                    parada.paradaNumero,
                    colorEstado,
                    prioridadAlta,
                    null,
                    motivoBadge,
                    badgeCantidad
                ));
                marker._reclamo = reclamosGrupo[0];
                marker._reclamosGrupo = reclamosGrupo;
            }
            this._sincronizarCompanionsObraGoogleVisualizacion();
        },

        async _actualizarMarcadoresMapboxVisualizacionInplace(paradas) {
            this._limpiarMarcadoresObraMapboxVisualizacion();

            const principales = (this._marcadoresVisualizacionMapbox || []).filter((m) => m._marcadorRecorridoPrincipal);
            for (const marker of principales) {
                const parada = this._buscarParadaPorMarkerVisualizacion(marker, paradas);
                if (!parada) {
                    continue;
                }
                const nuevoMarker = this._reemplazarElementoMarcadorMapboxVisualizacion(marker, parada);
                const lngLat = nuevoMarker.getLngLat();
                this._agregarMarcadoresObraMapboxVisualizacionParada(parada, { lat: lngLat.lat, lng: lngLat.lng });
            }
        },

        async actualizarMarcadoresVisualizacionInplace() {
            const paradas = this.agruparParadasRutaVistaPrevia(this.reclamosRutaVisualizando);
            if (this.proveedorMapaVisualizacion === 'mapbox') {
                await this._actualizarMarcadoresMapboxVisualizacionInplace(paradas);
            } else {
                await this._actualizarMarcadoresGoogleVisualizacionInplace(paradas);
            }
        },

        async _refrescarMapaDetalleSupervisorCompleto() {
            if (this.proveedorMapaVisualizacion === 'mapbox') {
                if (this.mapaVisualizacionMapbox) {
                    await this.mostrarRutaEnMapaMapbox();
                }
            } else if (this.mapaVisualizacion) {
                await this.agregarMarcadoresVisualizacion();
                await this.trazarRutaVisualizacion();
            }
            this.iniciarTickerVisualizacionObraSiCorresponde();
            this.refrescarCronometrosInfoWindowMapaSupervisor();
        },

        async refrescarMapaDetalleSupervisor(opciones = {}) {
            const { preservarVista = false } = opciones;
            if (!this.rutaDetalleSupervisorId || this.modoVistaDetalleSupervisor !== 'mapa') {
                return;
            }
            try {
                if (preservarVista) {
                    const fingerprint = this._fingerprintDatosMapaDetalleSupervisor();
                    if (fingerprint === this._ultimoFingerprintMapaDetalleSupervisor) {
                        this.refrescarCronometrosInfoWindowMapaSupervisor();
                        return;
                    }
                    this._ultimoFingerprintMapaDetalleSupervisor = fingerprint;

                    const paradas = this.agruparParadasRutaVistaPrevia(this.reclamosRutaVisualizando);
                    if (this._puedeActualizarMarcadoresVisualizacionInplace(paradas)) {
                        await this.actualizarMarcadoresVisualizacionInplace();
                        if (this.proveedorMapaVisualizacion === 'google') {
                            this._sincronizarCompanionsObraGoogleVisualizacion();
                        }
                        const hayMarcadoresMapbox = this.proveedorMapaVisualizacion !== 'mapbox'
                            || (this._marcadoresVisualizacionMapbox || []).some((m) => m._marcadorRecorridoPrincipal);
                        if (!hayMarcadoresMapbox && paradas.length > 0) {
                            const vista = this.capturarVistaMapaVisualizacion();
                            await this._refrescarMapaDetalleSupervisorCompleto();
                            this.restaurarVistaMapaVisualizacion(vista);
                        } else {
                            this.iniciarTickerVisualizacionObraSiCorresponde();
                            this.refrescarCronometrosInfoWindowMapaSupervisor();
                        }
                        return;
                    }

                    const vista = this.capturarVistaMapaVisualizacion();
                    await this._refrescarMapaDetalleSupervisorCompleto();
                    this.restaurarVistaMapaVisualizacion(vista);
                    return;
                }

                this._ultimoFingerprintMapaDetalleSupervisor = this._fingerprintDatosMapaDetalleSupervisor();
                await this._refrescarMapaDetalleSupervisorCompleto();
            } catch (error) {
                console.warn('Refresco mapa detalle supervisor:', error);
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

        /**
         * Eliminar hoja: permitido si no está en ejecución ni finalizada
         * (sin asignar o asignada).
         */
        puedeEliminarHojaRuta(ruta) {
            if (!ruta) return false;
            const k = this.claveEstadoEjecucionRuta(ruta);
            return k === 'asignada' || k === 'sin asignar';
        },

        motivoNoPuedeEliminarHojaRuta(ruta) {
            const k = this.claveEstadoEjecucionRuta(ruta);
            if (k === 'en ejecución') {
                return 'No se puede eliminar mientras la hoja está en ejecución.';
            }
            if (k === 'finalizada') {
                return 'No se puede eliminar una hoja finalizada.';
            }
            return '';
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

        msTranscurridoEjecucionRutaSupervisor(ruta) {
            if (!this.esEstadoEjecucionRuta(ruta)) {
                return 0;
            }
            const ini = ruta?.inicio_ejecucion_at;
            if (!ini) {
                return 0;
            }
            const t0 = new Date(String(ini).replace(' ', 'T')).getTime();
            if (Number.isNaN(t0)) {
                return 0;
            }
            return Math.max(0, this.ahoraCronometroSupervisor - t0);
        },

        nivelDemoraEjecucionRutaSupervisor(ruta) {
            return ObraCronometroUtil.nivelDemoraEjecucionRuta(
                this.msTranscurridoEjecucionRutaSupervisor(ruta),
                ruta?.tiempoEstimado
            );
        },

        claseCronometroEjecucionRutaSupervisor(ruta) {
            if (!this.esEstadoEjecucionRuta(ruta)) {
                return 'badge bg-dark font-monospace cronometro-ruta-supervisor cronometro-badge-con-ico';
            }
            const clases = ObraCronometroUtil.clasesBadgeCronometroEjecucionRuta(
                this.nivelDemoraEjecucionRutaSupervisor(ruta)
            );
            return `${clases} cronometro-ruta-supervisor`;
        },

        tiempoTranscurridoEjecucionSupervisor(ruta) {
            if (!this.esEstadoEjecucionRuta(ruta)) return '';
            const ini = ruta.inicio_ejecucion_at;
            if (!ini) return '—';
            const ms = this.msTranscurridoEjecucionRutaSupervisor(ruta);
            const sec = Math.floor(ms / 1000);
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

        estilosMapaPreviewCompacto() {
            return [
                { featureType: 'all', elementType: 'labels', stylers: [{ visibility: 'off' }] },
                { featureType: 'poi', stylers: [{ visibility: 'off' }] },
                { featureType: 'transit', stylers: [{ visibility: 'off' }] }
            ];
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
                    styles: this.estilosMapaPreviewCompacto()
                });

                const { markers, bounds, directionsRenderer } = await this._crearMarcadoresPreviewSupervisor(
                    map,
                    reclamos,
                    colorRuta
                );

                if (markers.length > 0) {
                    map.fitBounds(bounds, 20);
                }

                const fingerprint = this._fingerprintPreviewSupervisorRuta(reclamos, ruta);
                const estructura = this._fingerprintEstructuraPreviewSupervisorRuta(reclamos);
                this.mapasPreviewSupervisor = {
                    ...this.mapasPreviewSupervisor,
                    [ruta.id]: {
                        map,
                        markers,
                        directionsRenderer,
                        _ultimoFingerprint: fingerprint,
                        _ultimaEstructura: estructura
                    }
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

        async recargarDatosDetalleSupervisor(rutaId, opciones = {}) {
            const { silencioso = false } = opciones;
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
                const modalMapaAbierto = elModal?.classList.contains('show') && this.modoVistaDetalleSupervisor === 'mapa';

                if (modalMapaAbierto) {
                    await this.$nextTick();
                    if (silencioso && (this.mapaVisualizacion || this.mapaVisualizacionMapbox)) {
                        await this.refrescarMapaDetalleSupervisor({ preservarVista: true });
                    } else {
                        await new Promise((resolve) => setTimeout(resolve, 200));
                        await this.restaurarMapaDetalleSupervisor();
                    }
                    if (this.proveedorMapaVisualizacion === 'google' && this.mapaVisualizacion) {
                        this._sincronizarCompanionsObraGoogleVisualizacion();
                        this.iniciarTickerVisualizacionObraSiCorresponde();
                    }
                }
                this._ultimoFingerprintMapaDetalleSupervisor = this._fingerprintDatosMapaDetalleSupervisor();
            } catch (error) {
                if (silencioso) {
                    console.warn('Recarga silenciosa detalle supervisor:', error);
                } else {
                    console.error('Error al recargar detalle de ruta:', error);
                    throw error;
                }
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
            this.indiceReclamoListaParadaVisualizacion = {};
            this.mostrarDetalleCuadrillaSupervisor = false;

            try {
                if (this.cuadrillasDisponibles.length === 0) {
                    await this.obtenerCuadrillas();
                }
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
                    if (!this._marcadoresVisualizacionMapbox?.length) {
                        await this.mostrarRutaEnMapaMapbox();
                    }
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
                this.modoVistaDetalleSupervisor = 'mapa';
                await this.$nextTick();
                await this.restaurarMapaDetalleSupervisor();
            } else if (this.proveedorMapaVisualizacion === 'mapbox' && this.mapaVisualizacionMapbox) {
                if (!this._marcadoresVisualizacionMapbox?.length) {
                    await this.mostrarRutaEnMapaMapbox();
                }
            }
            await this.$nextTick();
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
                const est = (r.municipalidad_estado || '').trim();
                const sr = r.sesion_reparacion;
                if (!sr) {
                    continue;
                }
                const acum = Number(sr.acumulado_ms) || 0;
                const activo = est === 'Completado' ? false : !!sr.activo;
                if (!activo && acum <= 0) {
                    if (est === 'Completado') {
                        continue;
                    }
                    if (est !== 'Pendiente') {
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

        /** Reclamo con el cronómetro de obra corriendo: se destaca en la lista. */
        reclamoEnObraActivaSupervisor(reclamo) {
            const s = this.sesionReparacionReclamoSupervisor(reclamo);
            if (s) {
                return !!s.activo;
            }
            return !!reclamo?.sesion_reparacion?.activo;
        },

        textoCronometroReparacionReclamoSupervisor(reclamo) {
            const s = this.sesionReparacionReclamoSupervisor(reclamo);
            if (s) {
                let ms = s.acumuladoMs || 0;
                if (s.activo) {
                    ms += this.ahoraCronometroSupervisor - s.inicioSegmentoMs;
                }
                const sec = Math.max(0, Math.floor(ms / 1000));
                return this.formatearSegundosCronometroSupervisor(sec);
            }
            const sr = reclamo?.sesion_reparacion;
            const msApi = Number(sr?.acumulado_ms) || 0;
            if (msApi > 0) {
                return this.formatearSegundosCronometroSupervisor(Math.floor(msApi / 1000));
            }
            return '';
        },

        mostrarCronometroReparacionReclamoSupervisor(reclamo) {
            return !!this.textoCronometroReparacionReclamoSupervisor(reclamo);
        },

        claseCronometroListaObraSupervisor(reclamo) {
            const s = this.sesionReparacionReclamoSupervisor(reclamo);
            const srApi = reclamo?.sesion_reparacion;
            if (!s && !srApi) {
                return '';
            }
            const nivel = this.nivelDemoraObraReclamoSupervisor(reclamo);
            const pausado = s ? !s.activo : true;
            return ObraCronometroUtil.claseListaCronoObra(nivel, pausado);
        },

        colorTextoSobreEstadoReclamo(estado) {
            const e = (estado || '').trim();
            if (e === 'En ejecución' || e === 'Asignado') {
                return '#000';
            }
            return '#fff';
        },

        materialesReclamoSupervisorLista(reclamo) {
            if (!reclamo?.id) {
                return [];
            }
            return this.materialesPorReclamoSupervisor[reclamo.id] || [];
        },

        cantidadMaterialesReclamoSupervisor(reclamo) {
            return this.materialesReclamoSupervisorLista(reclamo).length;
        },

        urlFotoMaterialCatalogo(nombreArchivo) {
            if (!nombreArchivo) return '';
            return BASE_URL + 'static/uploads/materiales/' + nombreArchivo;
        },

        observacionesReclamoSupervisorLista(reclamo) {
            if (!reclamo?.id) {
                return [];
            }
            return this.observacionesPorReclamoSupervisor[reclamo.id] || [];
        },

        cantidadObservacionesEjecucionReclamoSupervisor(reclamo) {
            return this.observacionesReclamoSupervisorLista(reclamo)
                .filter((o) => !this.esEntradaCambioEstadoBitacoraObra(o))
                .length;
        },

        textoObservacionesEjecucionBadge(cantidad) {
            if (!cantidad || cantidad < 1) {
                return '';
            }
            return cantidad > 99 ? '99+' : String(cantidad);
        },

        htmlBadgeObservacionesEjecucionConId(reclamoId, cantidad) {
            const texto = this.textoObservacionesEjecucionBadge(cantidad);
            if (!texto) {
                return '';
            }
            return `<span class="btn-obs-ejecucion-count" data-map-iw-obs-count-id="${reclamoId}" aria-hidden="true">${texto}</span>`;
        },

        puedeVerMaterialesObservacionesSupervisor(reclamo) {
            return !!(this.rutaDetalleSupervisorId && reclamo?.id);
        },

        paramsObservacionesSupervisorReclamo() {
            const ejecId = this.rutaVisualizando?.ruta_ejecucion_activa_id;
            const rutaId = this.rutaVisualizando?.id || this.rutaDetalleSupervisorId;
            if (ejecId) {
                return { ruta_ejecucion_id: ejecId };
            }
            if (rutaId) {
                return { ruta_id: rutaId };
            }
            return null;
        },

        async cargarMaterialesYObservacionesDetalleSupervisor(reclamos) {
            if (!reclamos?.length) {
                this.materialesPorReclamoSupervisor = {};
                this.observacionesPorReclamoSupervisor = {};
                return;
            }
            const paramsObs = this.paramsObservacionesSupervisorReclamo();
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
                    if (paramsObs) {
                        peticiones.push(
                            axios.get(
                                BASE_URL + 'api/reclamos/' + reclamo.id + '/ejecucion-observaciones',
                                { params: paramsObs }
                            )
                        );
                    }
                    const resultados = await Promise.all(peticiones);
                    materialesMap[reclamo.id] = resultados[0]?.data || [];
                    observacionesMap[reclamo.id] = paramsObs ? (resultados[1]?.data || []) : [];
                } catch (error) {
                    console.warn('No se pudieron cargar materiales/observaciones del reclamo', reclamo.id, error);
                    materialesMap[reclamo.id] = [];
                    observacionesMap[reclamo.id] = [];
                }
            }));
            this.materialesPorReclamoSupervisor = materialesMap;
            this.observacionesPorReclamoSupervisor = observacionesMap;
            this.$nextTick(() => {
                this.refrescarBadgesObservacionesInfoWindowMapaSupervisor();
                this.refrescarBadgesMaterialesInfoWindowMapaSupervisor();
            });
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
                // Historial de ejecución: solo esa jornada (igual que bitácora del historial).
                // Seguimiento en vivo: historial completo del reclamo.
                const params = this.historialEjecucionMapa?.ejecucion?.id
                    ? { ruta_ejecucion_id: this.historialEjecucionMapa.ejecucion.id }
                    : {};
                const r = await axios.get(
                    BASE_URL + 'api/reclamos/' + this.reclamoSupervisorModal.id + '/materiales',
                    { params }
                );
                this.historialMaterialesSupervisor = Array.isArray(r.data) ? r.data : [];
                if (params.ruta_ejecucion_id) {
                    this.materialesPorReclamoHistorial = {
                        ...this.materialesPorReclamoHistorial,
                        [this.reclamoSupervisorModal.id]: this.historialMaterialesSupervisor
                    };
                } else {
                    this.materialesPorReclamoSupervisor = {
                        ...this.materialesPorReclamoSupervisor,
                        [this.reclamoSupervisorModal.id]: this.historialMaterialesSupervisor
                    };
                    this.$nextTick(() => this.refrescarBadgesMaterialesInfoWindowMapaSupervisor());
                }
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
            if (!this.paramsObservacionesSupervisorReclamo()) {
                this.mostrarMensaje('No se pudo determinar la hoja de ruta para consultar observaciones.', 'warning');
                return;
            }
            this.reclamoSupervisorModal = { ...reclamo };
            this.historialObservacionesSupervisor = [];
            const elModal = document.getElementById('modalObservacionesSupervisor');
            const modal = bootstrap.Modal.getOrCreateInstance(elModal);
            elModal.addEventListener('shown.bs.modal', () => {
                this.scrollBitacoraObraAlFinal('bitacoraObraFeedSupervisor');
            }, { once: true });
            modal.show();
            void this.cargarHistorialObservacionesSupervisor();
        },

        scrollBitacoraObraAlFinal(feedId = 'bitacoraObraFeedSupervisor') {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    const feed = document.getElementById(feedId);
                    if (!feed) {
                        return;
                    }
                    feed.scrollTop = feed.scrollHeight;
                });
            });
        },

        async cargarHistorialObservacionesSupervisor() {
            const params = this.paramsObservacionesSupervisorReclamo();
            if (!this.reclamoSupervisorModal?.id || !params) {
                return;
            }
            this.cargandoObservacionesSupervisor = true;
            try {
                const r = await axios.get(
                    BASE_URL + 'api/reclamos/' + this.reclamoSupervisorModal.id + '/ejecucion-observaciones',
                    { params }
                );
                this.historialObservacionesSupervisor = Array.isArray(r.data) ? r.data : [];
                this.observacionesPorReclamoSupervisor = {
                    ...this.observacionesPorReclamoSupervisor,
                    [this.reclamoSupervisorModal.id]: this.historialObservacionesSupervisor
                };
                this.refrescarBadgesObservacionesInfoWindowMapaSupervisor();
                this.scrollBitacoraObraAlFinal('bitacoraObraFeedSupervisor');
            } catch (error) {
                console.error('Error al cargar observaciones (supervisor):', error);
                this.mostrarMensaje('No se pudo cargar el historial de observaciones.', 'error');
                this.historialObservacionesSupervisor = [];
            } finally {
                this.cargandoObservacionesSupervisor = false;
                this.scrollBitacoraObraAlFinal('bitacoraObraFeedSupervisor');
            }
        },

        crearHtmlAccionesSupervisorDetallePopup(reclamo) {
            if (!this.puedeVerMaterialesObservacionesSupervisor(reclamo)) {
                return '';
            }

            const rid = String(reclamo.id);
            let html = '<div class="map-detalle-iw-acciones mapa-popup-acciones mapa-popup-acciones--supervisor border-top pt-2 mt-2">';
            let htmlInicio = '';
            let htmlPaneles = '';

            if (this.mostrarCronometroReparacionReclamoSupervisor(reclamo)) {
                const claseCrono = this.claseCronometroListaObraSupervisor(reclamo);
                htmlInicio += ObraCronometroUtil.htmlSpanCronometroBadge(
                    `badge font-monospace map-detalle-iw-crono ruta-secuencia-crono-reparacion ${claseCrono}`,
                    this.textoCronometroReparacionReclamoSupervisor(reclamo),
                    'reclamo',
                    `data-map-iw-crono-supervisor-id="${rid}" title="Tiempo en obra"`
                );
            }

            const matCount = this.cantidadMaterialesReclamoSupervisor(reclamo);
            const tituloMat = matCount > 0
                ? `Materiales utilizados (${matCount})`
                : 'Materiales utilizados';
            htmlPaneles += `<button type="button" class="btn btn-sm btn-outline-secondary btn-con-badge-obs" data-map-accion-supervisor="materiales" data-reclamo-id="${rid}" title="${tituloMat}"><i class="bi bi-box-seam"></i>${this.htmlBadgeMaterialesSupervisorConId(rid, matCount)}</button>`;
            const obsCount = this.cantidadObservacionesEjecucionReclamoSupervisor(reclamo);
            const tituloObs = obsCount > 0
                ? `Registro en obra (${obsCount})`
                : 'Registro en obra';
            htmlPaneles += `<button type="button" class="btn btn-sm btn-outline-secondary btn-con-badge-obs" data-map-accion-supervisor="observaciones" data-reclamo-id="${rid}" title="${tituloObs}"><i class="bi bi-journal-text"></i>${this.htmlBadgeObservacionesEjecucionConId(rid, obsCount)}</button>`;

            if (htmlInicio) {
                html += `<div class="map-detalle-iw-acciones__inicio">${htmlInicio}</div>`;
            }
            if (htmlPaneles) {
                html += `<div class="map-detalle-iw-acciones__paneles">${htmlPaneles}</div>`;
            }
            html += '</div>';
            return html;
        },

        vincularAccionesSupervisorDetallePopup(reclamo) {
            if (!this.rutaDetalleSupervisorId || !reclamo?.id) {
                return;
            }
            document.querySelectorAll(`[data-map-accion-supervisor][data-reclamo-id="${reclamo.id}"]`).forEach((btn) => {
                btn.onclick = (e) => this.onMapaDetalleSupervisorInfoWindowAccion(e);
            });
        },

        construirInfoWindowContentMapaDetalleSupervisor(reclamo) {
            const wrap = document.createElement('div');
            wrap.className = 'map-detalle-iw';
            wrap.innerHTML = this.crearContenidoInfoWindow(reclamo) + this.crearHtmlAccionesSupervisorDetallePopup(reclamo);
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
                if (!r || !this.mostrarCronometroReparacionReclamoSupervisor(r)) {
                    ObraCronometroUtil.actualizarTextoCronometroBadge(el, '—', 'reclamo');
                    ObraCronometroUtil.sincronizarClasesNivelCronoObra(el, '');
                    return;
                }
                ObraCronometroUtil.actualizarTextoCronometroBadge(
                    el,
                    this.textoCronometroReparacionReclamoSupervisor(r),
                    'reclamo'
                );
                ObraCronometroUtil.sincronizarClasesNivelCronoObra(el, this.claseCronometroListaObraSupervisor(r));
            });
            this.refrescarBadgesObservacionesInfoWindowMapaSupervisor();
            this.refrescarBadgesMaterialesInfoWindowMapaSupervisor();
        },

        htmlBadgeMaterialesSupervisorConId(reclamoId, cantidad) {
            const texto = this.textoObservacionesEjecucionBadge(cantidad) || '0';
            const oculto = cantidad > 0 ? '' : ' btn-obs-ejecucion-count--oculto';
            return `<span class="btn-obs-ejecucion-count${oculto}" data-map-iw-mat-count-id="${reclamoId}" aria-hidden="true">${texto}</span>`;
        },

        refrescarBadgesMaterialesInfoWindowMapaSupervisor() {
            if (!this.rutaDetalleSupervisorId) {
                return;
            }
            document.querySelectorAll('[data-map-iw-mat-count-id]').forEach((el) => {
                const rid = parseInt(el.getAttribute('data-map-iw-mat-count-id'), 10);
                if (Number.isNaN(rid)) {
                    return;
                }
                const r = this.reclamosRutaVisualizando.find((x) => Number(x.id) === rid);
                const count = r ? this.cantidadMaterialesReclamoSupervisor(r) : 0;
                const texto = this.textoObservacionesEjecucionBadge(count);
                if (!texto) {
                    el.classList.add('btn-obs-ejecucion-count--oculto');
                    el.textContent = '0';
                    return;
                }
                el.classList.remove('btn-obs-ejecucion-count--oculto');
                el.textContent = texto;
            });
        },

        refrescarBadgesObservacionesInfoWindowMapaSupervisor() {
            if (!this.rutaDetalleSupervisorId) {
                return;
            }
            document.querySelectorAll('[data-map-iw-obs-count-id]').forEach((el) => {
                const rid = parseInt(el.getAttribute('data-map-iw-obs-count-id'), 10);
                if (Number.isNaN(rid)) {
                    return;
                }
                const r = this.reclamosRutaVisualizando.find((x) => Number(x.id) === rid);
                const count = r ? this.cantidadObservacionesEjecucionReclamoSupervisor(r) : 0;
                const texto = this.textoObservacionesEjecucionBadge(count);
                if (!texto) {
                    el.remove();
                    return;
                }
                el.textContent = texto;
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
            this.mostrarDetalleCuadrillaSupervisor = false;
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
                                const tiempoEstimado = row.tiempoEstimado || '';
                                const escAttr = (s) => String(s ?? '').replace(/\\/g, '\\\\').replace(/"/g, '&quot;');
                                let initial = '—';
                                let msTranscurrido = 0;
                                if (inicio) {
                                    const t0 = new Date(String(inicio).replace(' ', 'T')).getTime();
                                    if (!Number.isNaN(t0)) {
                                        msTranscurrido = Math.max(0, Date.now() - t0);
                                        initial = vueComponent.formatearSegundosCronometroSupervisor(Math.floor(msTranscurrido / 1000));
                                    }
                                }
                                const nivelCrono = ObraCronometroUtil.nivelDemoraEjecucionRuta(msTranscurrido, tiempoEstimado);
                                const clasesCrono = `${ObraCronometroUtil.clasesBadgeCronometroEjecucionRuta(nivelCrono)} cronometro-ruta-supervisor`;
                                const cronoHtml = ObraCronometroUtil.htmlSpanCronometroBadge(
                                    clasesCrono,
                                    initial,
                                    'ruta',
                                    `style="font-size: 0.75rem; letter-spacing: 0.06em;" data-inicio-ejecucion-at="${escAttr(inicio)}" data-tiempo-estimado="${escAttr(tiempoEstimado)}"`
                                );
                                return `
                                    <span class="d-inline-flex align-items-center gap-1 flex-wrap">
                                        <span class="badge bg-success" style="font-size: 0.75rem;">
                                            <i class="bi bi-play-circle-fill text-white me-1"></i>En ejecución
                                        </span>
                                        ${cronoHtml}
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
            this.mostrarListaRutaVistaPrevia = false;
            this.indiceReclamoListaParada = {};
            this.rutaOriginal = [];
            this.cuadrillaSeleccionadaCrearRuta = '';
            this.cuadrillaDetalleAbiertaId = null;
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

        cuadrillaTieneOperarios(cuadrilla) {
            return !!(cuadrilla?.operarios && cuadrilla.operarios.length > 0);
        },

        cuadrillaTieneGestion(cuadrilla) {
            return (cuadrilla?.operarios || []).some((op) => Number(op.es_jefe) === 1);
        },

        cuadrillaEsAsignable(cuadrilla, excluirRutaId = null) {
            if (!cuadrilla) return false;
            if (this.cuadrillaTieneOtraHojaAsignada(cuadrilla.id, excluirRutaId)) return false;
            return this.cuadrillaTieneOperarios(cuadrilla) && this.cuadrillaTieneGestion(cuadrilla);
        },

        mensajeCuadrillaNoAsignable(cuadrilla, excluirRutaId = null) {
            if (!cuadrilla) return 'Cuadrilla no válida';
            const ocupada = this.mensajeCuadrillaOcupada(cuadrilla.id, excluirRutaId);
            if (ocupada) return ocupada;
            if (!this.cuadrillaTieneOperarios(cuadrilla)) {
                return 'La cuadrilla no tiene operarios asignados';
            }
            if (!this.cuadrillaTieneGestion(cuadrilla)) {
                return 'La cuadrilla debe tener al menos un operario con permisos de gestión';
            }
            return '';
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
            const cuadrilla = this.cuadrillasDisponibles.find(
                (c) => String(c.id) === String(cuadrillaId)
            );
            const msg = this.mensajeCuadrillaNoAsignable(cuadrilla);
            if (msg) {
                this.mostrarMensaje(msg, 'warning');
                return;
            }
            this.cuadrillaSeleccionadaCrearRuta = cuadrillaId;
        },

        seleccionarCuadrillaParaAsignar(cuadrillaId) {
            const rutaId = this.rutaParaAsignar?.id;
            const cuadrilla = this.cuadrillasDisponibles.find(
                (c) => String(c.id) === String(cuadrillaId)
            );
            const msg = this.mensajeCuadrillaNoAsignable(cuadrilla, rutaId);
            if (msg) {
                this.mostrarMensaje(msg, 'warning');
                return;
            }
            this.cuadrillaSeleccionadaParaAsignar = cuadrillaId;
        },

        toggleCuadrillaDetalleCrearRuta(cuadrillaId, event) {
            if (event) {
                event.stopPropagation();
            }
            const key = String(cuadrillaId);
            this.cuadrillaDetalleAbiertaId = this.cuadrillaDetalleAbiertaId === key ? null : key;
        },

        cuadrillaDetalleExpandida(cuadrillaId) {
            return this.cuadrillaDetalleAbiertaId === String(cuadrillaId);
        },

        cerrarDetalleCuadrillaCrearRuta() {
            this.cuadrillaDetalleAbiertaId = null;
        },

        async toggleDetalleCuadrillaSupervisor() {
            if (!this.rutaVisualizando?.cuadrilla_id) {
                return;
            }
            if (this.mostrarDetalleCuadrillaSupervisor) {
                this.cerrarDetalleCuadrillaSupervisor();
                return;
            }
            if (this.cuadrillasDisponibles.length === 0) {
                await this.obtenerCuadrillas();
            }
            if (!this.cuadrillaAsignadaDetalleSupervisor) {
                this.mostrarMensaje('No se encontró el detalle de la cuadrilla.', 'warning');
                return;
            }
            this.mostrarDetalleCuadrillaSupervisor = true;
        },

        cerrarDetalleCuadrillaSupervisor() {
            this.mostrarDetalleCuadrillaSupervisor = false;
        },

        urlFotoOperario(nombreArchivo) {
            return BASE_URL + 'static/uploads/perfiles/' + nombreArchivo;
        },

        inicialesOperario(nombre) {
            if (!nombre) return '?';
            const partes = nombre.trim().split(/\s+/);
            const primera = partes[0] ? partes[0][0] : '';
            const segunda = partes.length > 1 ? partes[partes.length - 1][0] : '';
            return (primera + segunda).toUpperCase();
        },

        colorAvatarOperario(nombre) {
            const paleta = ['#3A3972', '#6E6D99', '#2D6A6A', '#7A5C9E', '#A65A7A', '#4C6EA8', '#9E7B3A'];
            const texto = nombre || '';
            let hash = 0;
            for (let i = 0; i < texto.length; i++) {
                hash = texto.charCodeAt(i) + ((hash << 5) - hash);
            }
            return paleta[Math.abs(hash) % paleta.length];
        },

        jefeDeCuadrilla(cuadrilla) {
            if (!cuadrilla?.operarios) return null;
            return cuadrilla.operarios.find((op) => Number(op.es_jefe) === 1) || null;
        },

        operariosCuadrillaDeRuta(ruta) {
            if (!ruta?.cuadrilla_id) return [];
            const cuadrilla = this.cuadrillasDisponibles.find(
                (c) => String(c.id) === String(ruta.cuadrilla_id)
            );
            if (!cuadrilla?.operarios?.length) return [];
            return [...cuadrilla.operarios].sort(
                (a, b) => Number(b.es_jefe) - Number(a.es_jefe)
            );
        },

        tituloCuadrillaTarjetaRuta(ruta) {
            const ops = this.operariosCuadrillaDeRuta(ruta);
            if (!ops.length) {
                return ruta.cuadrilla_nombre || 'Sin asignar';
            }
            const nombres = ops.map((op) => {
                const rol = Number(op.es_jefe) === 1 ? ' (Gestión)' : '';
                return `${op.nombre}${rol}`;
            });
            return `${ruta.cuadrilla_nombre || 'Cuadrilla'}: ${nombres.join(', ')}`;
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
            this.mostrarListaRutaVistaPrevia = false;
            this.rutaOriginal = [];
        },

        /**
         * Activa el modo de edición de la ruta
         */
        activarModoEdicion() {
            this.modoEdicion = true;
            this.mostrarListaRutaVistaPrevia = false;
            // Guardar copia de la ruta original por si cancela
            this.rutaOriginal = JSON.parse(JSON.stringify(this.vistaPrevia.rutaOptimizada));
            this.mostrarMensaje('Modo edición activado. Agregá reclamos en el mapa y usá Ver lista para ordenarlos.', 'info');
            this.$nextTick(() => {
                this.restaurarMapaVistaPreviaCrearRuta();
            });
        },

        /**
         * Cancela la edición y vuelve a la ruta original
         */
        cancelarEdicion() {
            this.vistaPrevia.rutaOptimizada = JSON.parse(JSON.stringify(this.rutaOriginal));
            this.modoEdicion = false;
            this.mostrarListaRutaVistaPrevia = false;
            this.rutaOriginal = [];
            this.actualizarMapaVistaPrevia();
            this.mostrarMensaje('Edición cancelada. Se restauró la ruta original.', 'info');
        },

        /**
         * Mueve una parada (domicilio) hacia arriba en la lista
         */
        async moverParadaArriba(paradaIndex) {
            const paradas = this.agruparParadasRutaVistaPrevia(this.vistaPrevia.rutaOptimizada);
            if (paradaIndex === 0) return;

            [paradas[paradaIndex - 1], paradas[paradaIndex]] = [paradas[paradaIndex], paradas[paradaIndex - 1]];
            this.vistaPrevia.rutaOptimizada = paradas.flatMap((parada) => parada.reclamos);
            await this.actualizarMapaVistaPrevia();
        },

        /**
         * Mueve una parada (domicilio) hacia abajo en la lista
         */
        async moverParadaAbajo(paradaIndex) {
            const paradas = this.agruparParadasRutaVistaPrevia(this.vistaPrevia.rutaOptimizada);
            if (paradaIndex >= paradas.length - 1) return;

            [paradas[paradaIndex], paradas[paradaIndex + 1]] = [paradas[paradaIndex + 1], paradas[paradaIndex]];
            this.vistaPrevia.rutaOptimizada = paradas.flatMap((parada) => parada.reclamos);
            await this.actualizarMapaVistaPrevia();
        },

        /**
         * Elimina una parada completa (todos los reclamos del mismo domicilio)
         */
        async eliminarParadaDeRuta(paradaIndex) {
            const paradas = this.agruparParadasRutaVistaPrevia(this.vistaPrevia.rutaOptimizada);
            const parada = paradas[paradaIndex];
            if (!parada) return;

            paradas.splice(paradaIndex, 1);
            this.vistaPrevia.rutaOptimizada = paradas.flatMap((p) => p.reclamos);

            if (parada.reclamos.length === 1) {
                this.mostrarMensaje(`Reclamo #${parada.reclamos[0].municipalidad_id} eliminado de la ruta`, 'success');
            } else {
                const ids = parada.reclamos.map((r) => `#${r.municipalidad_id}`).join(', ');
                this.mostrarMensaje(`${parada.reclamos.length} reclamos del mismo domicilio eliminados (${ids})`, 'success');
            }

            await this.actualizarMapaVistaPrevia();
        },

        indiceReclamoEnParadaLista(parada) {
            const idx = this.indiceReclamoListaParada[parada.clave];
            if (idx === undefined || idx >= parada.reclamos.length) {
                return 0;
            }
            return idx;
        },

        reclamoActivoEnParadaLista(parada) {
            return parada.reclamos[this.indiceReclamoEnParadaLista(parada)] || parada.reclamos[0];
        },

        navegarReclamoEnParadaLista(parada, delta) {
            if (parada.reclamos.length <= 1) return;

            const total = parada.reclamos.length;
            let idx = this.indiceReclamoEnParadaLista(parada);
            idx = (idx + delta + total) % total;
            this.indiceReclamoListaParada = {
                ...this.indiceReclamoListaParada,
                [parada.clave]: idx
            };
        },

        /**
         * Agrega un reclamo a la ruta al hacer clic en el mapa (solo en modo edición).
         * Si hay varios reclamos en el mismo domicilio, agrega todos los elegibles como una sola parada.
         */
        async agregarReclamoARuta(reclamo) {
            if (!this.modoEdicion) return;

            const claveDomicilio = this.claveDomicilioReclamo(reclamo);
            const reclamosMismoDomicilio = this.reclamos.filter(
                (r) => this.claveDomicilioReclamo(r) === claveDomicilio
            );

            const reclamosParaAgregar = [];
            const omitidos = { enRuta: 0, completado: 0, otraRuta: 0 };

            for (const candidato of reclamosMismoDomicilio) {
                if (candidato.municipalidad_estado === 'Completado') {
                    omitidos.completado++;
                    continue;
                }

                if (this.vistaPrevia.rutaOptimizada.find((r) => r.id === candidato.id)) {
                    omitidos.enRuta++;
                    continue;
                }

                const estaEnOtraRuta = await this.verificarReclamoEnOtraRuta(candidato.id);
                if (estaEnOtraRuta) {
                    omitidos.otraRuta++;
                    continue;
                }

                reclamosParaAgregar.push(candidato);
            }

            if (reclamosParaAgregar.length === 0) {
                if (omitidos.enRuta > 0) {
                    this.mostrarMensaje('Este domicilio ya está en la ruta', 'warning');
                } else if (omitidos.otraRuta > 0) {
                    this.mostrarMensaje('Los reclamos de este domicilio ya están en otra hoja de ruta', 'warning');
                } else if (omitidos.completado > 0) {
                    this.mostrarMensaje('No se pueden agregar reclamos completados', 'warning');
                } else {
                    this.mostrarMensaje('No hay reclamos disponibles para agregar en este domicilio', 'warning');
                }
                return;
            }

            for (const candidato of reclamosParaAgregar) {
                this.vistaPrevia.rutaOptimizada.push(candidato);
            }

            if (reclamosParaAgregar.length === 1) {
                this.mostrarMensaje(`Reclamo #${reclamosParaAgregar[0].municipalidad_id} agregado a la ruta`, 'success');
            } else {
                const ids = reclamosParaAgregar.map((r) => `#${r.municipalidad_id}`).join(', ');
                this.mostrarMensaje(
                    `${reclamosParaAgregar.length} reclamos del mismo domicilio agregados a la ruta (${ids})`,
                    'success'
                );
            }

            await this.actualizarMapaVistaPrevia();
        },

        /**
         * Verifica si un reclamo está en otra ruta (asignada o no asignada)
         */
        async verificarReclamoEnOtraRuta(reclamoId) {
            try {
                if (!this.idsReclamosEnRutasActivas || this.idsReclamosEnRutasActivas.length === 0) {
                    const response = await axios.get(BASE_URL + 'api/rutas/domicilios-disponibles');
                    this.idsReclamosEnRutasActivas = (response.data?.idsReclamosEnRutasActivas || [])
                        .map((id) => Number(id));
                }
                return this.idsReclamosEnRutasActivas.includes(Number(reclamoId));
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
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
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
         * Actualiza el número de domicilios disponibles (mismo criterio que el backend)
         */
        async actualizarDisponibles() {
            try {
                const response = await axios.get(BASE_URL + 'api/rutas/domicilios-disponibles');
                this.idsReclamosEnRutasActivas = (response.data?.idsReclamosEnRutasActivas || [])
                    .map((id) => Number(id));
                this.reclamosDisponibles = response.data?.domiciliosDisponibles
                    ?? this.contarUnidadesDomicilioDisponibles();
            } catch (error) {
                console.error('Error al actualizar disponibles:', error);
                this.reclamosDisponibles = this.contarUnidadesDomicilioDisponibles();
            }
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
         * Muestra la vista previa en el mapa usando Google Directions Service
         */
        async mostrarVistaPreviaEnMapa() {
            // Limpiar completamente la vista previa anterior primero
            this.limpiarVistaPreviaCompleto();
            
            // Primero agregar todos los reclamos que NO están en la ruta (puntiagudos)
            const idsRutaPrevia = this.vistaPrevia.rutaOptimizada.map(r => r.id);
            const reclamosNoEnRuta = this.reclamos.filter(r =>
                !idsRutaPrevia.includes(r.id) && r.municipalidad_estado !== 'Completado'
            );
            
            // OPTIMIZACIÓN: Paralelizar obtención de coordenadas
            const promesasCoordenadas = reclamosNoEnRuta.map(reclamo => 
                this.obtenerCoordenadasReclamo(reclamo).then(coords => ({ reclamo, coords }))
            );
            
            const resultados = await Promise.all(promesasCoordenadas);
            const gruposOtros = this.agruparReclamosPorDomicilioVistaPrevia(resultados);
            let contadorGruposOtrosGoogle = 0;

            for (const grupo of gruposOtros) {
                const reclamoRef = grupo.reclamos[0];
                const coordenadas = grupo.coordenadas;
                const esGrupo = grupo.reclamos.length > 1;
                const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(grupo.reclamos);

                const marker = new google.maps.Marker({
                    position: { lat: coordenadas.lat, lng: coordenadas.lng },
                    map: this.mapa,
                    title: esGrupo
                        ? `${grupo.reclamos.length} reclamos en este domicilio`
                        : `Reclamo #${reclamoRef.municipalidad_id}${prioridadAlta ? ' - ⚠️ PRIORIDAD ALTA' : ''}`,
                    icon: this.crearIconoPinMotivo(
                        colorEstado,
                        prioridadAlta,
                        reclamoRef.municipalidad_motivo,
                        esGrupo ? grupo.reclamos.length : null
                    ),
                    optimized: false
                });

                marker._reclamo = reclamoRef;
                marker._indicePopup = 0;

                if (esGrupo) {
                    marker._reclamosGrupo = grupo.reclamos;
                    marker._grupoId = `grupo-vista-previa-otros-${++contadorGruposOtrosGoogle}`;
                }

                marker.addListener('click', () => {
                    if (this.modoEdicion) {
                        this.agregarReclamoARuta(reclamoRef);
                        return;
                    }
                    this.abrirPopupVistaPreviaGoogle(marker);
                });

                this.vistaPrevia.marcadoresOtros.push(marker);
            }
            
            // Marcadores numerados por parada (agrupa mismo domicilio)
            const paradasRuta = this.agruparParadasRutaVistaPrevia(this.vistaPrevia.rutaOptimizada);
            let contadorGruposVistaPrevia = 0;

            for (const parada of paradasRuta) {
                const reclamoRef = parada.reclamos[0];
                const coordenadas = await this.obtenerCoordenadasReclamo(reclamoRef);

                if (coordenadas) {
                    const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                    const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(parada.reclamos);
                    const cantidadParada = parada.reclamos.length;
                    const esGrupo = cantidadParada > 1;
                    const motivoBadge = esGrupo ? null : reclamoRef.municipalidad_motivo;
                    const badgeCantidad = esGrupo ? cantidadParada : null;

                    const marker = new google.maps.Marker({
                        position: { lat: coordenadas.lat, lng: coordenadas.lng },
                        map: this.mapa,
                        title: esGrupo
                            ? `Parada ${parada.paradaNumero}: ${cantidadParada} reclamos en el mismo domicilio`
                            : `Posición ${parada.paradaNumero}: Reclamo #${reclamoRef.municipalidad_id}`,
                        icon: this.crearIconoNumerado(
                            parada.paradaNumero,
                            colorEstado,
                            prioridadAlta,
                            null,
                            motivoBadge,
                            badgeCantidad
                        ),
                        zIndex: 1000,
                        optimized: false
                    });

                    const reclamosGrupo = parada.reclamos.map((r) => ({
                        ...r,
                        posicion: parada.paradaNumero
                    }));

                    marker._reclamo = reclamosGrupo[0];
                    marker._reclamosGrupo = reclamosGrupo;
                    marker._indicePopup = 0;
                    if (esGrupo) {
                        marker._grupoId = `grupo-vista-previa-${++contadorGruposVistaPrevia}`;
                    }

                    marker.addListener('click', () => {
                        this.abrirPopupVistaPreviaGoogle(marker);
                    });

                    this.vistaPrevia.marcadoresRuta.push(marker);
                }
            }
            
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
                                    opacity: 0.75,
                                    optimized: false
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
        crearIconoNumerado(numero, colorEstado, colorPrioridad, tamanoPersonalizado = null, motivo = null, badgeCantidad = null) {
            const tienePrioridadAlta = typeof colorPrioridad === 'boolean'
                ? colorPrioridad
                : (colorPrioridad !== null && colorPrioridad !== undefined);
            const badgePrioridadSvg = tienePrioridadAlta && !tamanoPersonalizado
                ? this.crearSvgBadgePrioridadAlta(7, 7)
                : '';
            const badgeSvg = badgeCantidad > 1
                ? this.crearSvgBadgeCantidad(badgeCantidad, tamanoPersonalizado ? tamanoPersonalizado - 6 : 25, tamanoPersonalizado ? 6 : 7, tamanoPersonalizado ? Math.max(4.5, tamanoPersonalizado * 0.17) : 6, tamanoPersonalizado ? Math.max(7, tamanoPersonalizado * 0.26) : 9)
                : this.crearSvgBadgeMotivo(motivo, tamanoPersonalizado ? tamanoPersonalizado - 6 : 25, tamanoPersonalizado ? 6 : 7, tamanoPersonalizado ? Math.max(4.5, tamanoPersonalizado * 0.17) : 6, tamanoPersonalizado ? Math.max(7, tamanoPersonalizado * 0.26) : 9);

            if (tamanoPersonalizado) {
                const size = tamanoPersonalizado;
                const half = size / 2;
                const r = Math.max(8, Math.floor(size * 0.42));
                const fontSize = Math.max(8, Math.floor(size * 0.38));
                const badgeMotivoOCantidad = badgeCantidad > 1
                    ? badgeSvg
                    : this.crearSvgBadgeMotivo(motivo, size - 6, 6, Math.max(4.5, size * 0.17), Math.max(7, size * 0.26));

                if (tienePrioridadAlta) {
                    const badgePrioridadX = Math.max(4, Math.round(size * 6 / 32));
                    const badgePrioridadY = badgePrioridadX;
                    const viewTop = -5;
                    const viewHeight = size + Math.abs(viewTop);
                    const displayH = viewHeight;
                    const anchorY = Math.round(((half - viewTop) / viewHeight) * displayH);

                    return {
                        url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                            <svg width="${size}" height="${displayH}" viewBox="0 ${viewTop} ${size} ${viewHeight}" xmlns="http://www.w3.org/2000/svg" overflow="visible">
                                <circle cx="${half}" cy="${half}" r="${r}" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="1.5"/>
                                <text x="${half}" y="${half + fontSize * 0.35}" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="${fontSize}" font-weight="bold">${numero}</text>
                                ${this.crearSvgBadgePrioridadAlta(badgePrioridadX, badgePrioridadY, true)}
                                ${badgeMotivoOCantidad}
                            </svg>
                        `)}`,
                        scaledSize: new google.maps.Size(size, displayH),
                        anchor: new google.maps.Point(half, anchorY)
                    };
                }

                return {
                    url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                        <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="${half}" cy="${half}" r="${r}" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="1.5"/>
                            <text x="${half}" y="${half + fontSize * 0.35}" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="${fontSize}" font-weight="bold">${numero}</text>
                            ${badgeMotivoOCantidad}
                        </svg>
                    `)}`,
                    scaledSize: new google.maps.Size(size, size),
                    anchor: new google.maps.Point(half, half)
                };
            }
            
            if (tienePrioridadAlta) {
                const viewTop = -7;
                const viewHeight = 39;
                const displayH = 39;
                const anchorY = Math.round(((16 - viewTop) / viewHeight) * displayH);

                return {
                    url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                        <svg width="32" height="${displayH}" viewBox="0 ${viewTop} 32 ${viewHeight}" xmlns="http://www.w3.org/2000/svg" overflow="visible">
                            <circle cx="16" cy="16" r="14" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                            <text x="16" y="20" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="12" font-weight="bold">${numero}</text>
                            ${badgePrioridadSvg}
                            ${badgeCantidad > 1 ? this.crearSvgBadgeCantidad(badgeCantidad, 25, 7, 6, 9) : this.crearSvgBadgeMotivo(motivo, 25, 7, 6, 9)}
                        </svg>
                    `)}`,
                    scaledSize: new google.maps.Size(32, displayH),
                    anchor: new google.maps.Point(16, anchorY)
                };
            }

            return {
                url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                    <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="16" cy="16" r="14" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                        <text x="16" y="20" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="12" font-weight="bold">${numero}</text>
                        ${badgeCantidad > 1 ? this.crearSvgBadgeCantidad(badgeCantidad, 25, 7, 6, 9) : this.crearSvgBadgeMotivo(motivo, 25, 7, 6, 9)}
                    </svg>
                `)}`,
                scaledSize: new google.maps.Size(32, 32),
                anchor: new google.maps.Point(16, 16)
            };
        },

        /**
         * Limpia la vista previa
         */
        limpiarVistaPrevia() {
            this.limpiarVistaPreviaCompleto();
            
            // Limpiar datos
            this.vistaPrevia.rutaOptimizada = [];
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
                const cuadrillaCrear = this.cuadrillasDisponibles.find(
                    (c) => String(c.id) === String(cuadrillaId)
                );
                const msgNoAsignableCrear = this.mensajeCuadrillaNoAsignable(cuadrillaCrear);
                if (msgNoAsignableCrear) {
                    this.mostrarMensaje(msgNoAsignableCrear, 'warning');
                    return;
                }

                if (this.modoEdicion) {
                    // Si está en modo edición, enviar la ruta editada manualmente
                    datosRuta = {
                        color: this.nuevaRuta.color,
                        cantidadReclamos: this.contarUnidadesDomicilioEnRuta(this.vistaPrevia.rutaOptimizada),
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

                await axios.post(BASE_URL + 'api/rutas/generar', datosRuta);

                this.mostrarMensaje('Hoja de ruta creada y asignada exitosamente', 'success');
                
                // Resetear modo edición
                this.modoEdicion = false;
                this.rutaOriginal = [];
                
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalCrearRuta'));
                modal.hide();
                
                // Resetear modal
                this.resetearModal();
                
                // Actualizar tabla y contador de disponibles
                await this.obtenerRutas();
                await this.actualizarDisponibles();

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
                this.indiceReclamoListaParadaVisualizacion = {};
                this.mostrarListaRutaVisualizacion = false;
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
                this.mapaVisualizacion = new google.maps.Map(el, {
                    center: { lat: lat, lng: lng },
                    zoom: 13,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
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

        indiceReclamoEnParadaListaVisualizacion(parada) {
            const idx = this.indiceReclamoListaParadaVisualizacion[parada.clave];
            if (idx === undefined || idx >= parada.reclamos.length) {
                return 0;
            }
            return idx;
        },

        reclamoActivoEnParadaListaVisualizacion(parada) {
            return parada.reclamos[this.indiceReclamoEnParadaListaVisualizacion(parada)] || parada.reclamos[0];
        },

        navegarReclamoEnParadaListaVisualizacion(parada, delta) {
            if (parada.reclamos.length <= 1) return;

            const total = parada.reclamos.length;
            let idx = this.indiceReclamoEnParadaListaVisualizacion(parada);
            idx = (idx + delta + total) % total;
            this.indiceReclamoListaParadaVisualizacion = {
                ...this.indiceReclamoListaParadaVisualizacion,
                [parada.clave]: idx
            };
        },

        vincularEventosPopupVisualizacionGoogle(marker, reclamo) {
            const btnDetalle = document.querySelector(`.mapa-popup-detalle[data-reclamo-id="${reclamo.id}"]`);
            if (btnDetalle) {
                btnDetalle.onclick = () => this.verReclamoVistaPrevia(reclamo);
            }

            if (marker._grupoId) {
                const btnPrev = document.querySelector(`.mapa-popup-nav-prev[data-grupo-id="${marker._grupoId}"]`);
                if (btnPrev) {
                    btnPrev.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoVisualizacion(marker, -1);
                    };
                }

                const btnNext = document.querySelector(`.mapa-popup-nav-next[data-grupo-id="${marker._grupoId}"]`);
                if (btnNext) {
                    btnNext.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoVisualizacion(marker, 1);
                    };
                }
            }

            const headerElement = document.querySelector('.gm-style-iw-ch');
            if (headerElement) {
                headerElement.innerHTML = this.crearEncabezadoPopupReclamo(reclamo);
            }

            this.vincularAccionesSupervisorDetallePopup(reclamo);
        },

        vincularEventosPopupVisualizacionMapbox(marker, reclamo) {
            const btnDetalle = document.querySelector(`.mapa-popup-detalle[data-reclamo-id="${reclamo.id}"]`);
            if (btnDetalle) {
                btnDetalle.onclick = () => this.verReclamoVistaPrevia(reclamo);
            }

            if (marker._grupoId) {
                const btnPrev = document.querySelector(`.mapa-popup-nav-prev[data-grupo-id="${marker._grupoId}"]`);
                if (btnPrev) {
                    btnPrev.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoVisualizacionMapbox(marker, -1);
                    };
                }

                const btnNext = document.querySelector(`.mapa-popup-nav-next[data-grupo-id="${marker._grupoId}"]`);
                if (btnNext) {
                    btnNext.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoVisualizacionMapbox(marker, 1);
                    };
                }
            }

            this.vincularAccionesSupervisorDetallePopup(reclamo);
        },

        navegarPopupGrupoVisualizacion(marker, delta) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) return;

            let nuevoIndice = (marker._indicePopup || 0) + delta;
            if (nuevoIndice < 0) nuevoIndice = reclamos.length - 1;
            if (nuevoIndice >= reclamos.length) nuevoIndice = 0;

            this.abrirPopupVisualizacionGoogle(marker, nuevoIndice);
        },

        abrirPopupVisualizacionGoogle(marker, indice = null) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length || !this.mapaVisualizacion) return;

            if (indice !== null) {
                marker._indicePopup = indice;
            } else if (marker._indicePopup === undefined) {
                marker._indicePopup = 0;
            }

            const reclamo = reclamos[marker._indicePopup];
            const infoWindow = marker._infoWindow || new google.maps.InfoWindow();
            marker._infoWindow = infoWindow;

            infoWindow.setContent(this.crearContenidoPopupReclamo(reclamo, {
                grupoId: marker._grupoId || null,
                indice: marker._indicePopup,
                total: reclamos.length,
                incluirTitulo: false,
                incluirAccionesSupervisorDetalle: !!this.rutaDetalleSupervisorId
            }));

            if (this.infoWindowAbiertoVisualizacion) {
                this.infoWindowAbiertoVisualizacion.close();
            }

            infoWindow.open(this.mapaVisualizacion, marker);
            this.infoWindowAbiertoVisualizacion = infoWindow;

            google.maps.event.addListenerOnce(infoWindow, 'domready', () => {
                setTimeout(() => this.vincularEventosPopupVisualizacionGoogle(marker, reclamo), 100);
            });
        },

        abrirPopupVisualizacionMapbox(marker, indice = null) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) return;

            if (indice !== null) {
                marker._indicePopup = indice;
            } else if (marker._indicePopup === undefined) {
                marker._indicePopup = 0;
            }

            const reclamo = reclamos[marker._indicePopup];
            let popup = marker.getPopup();
            if (!popup) {
                popup = new mapboxgl.Popup({ offset: 25 });
                marker.setPopup(popup);
            }

            popup.setHTML(this.crearContenidoPopupReclamo(reclamo, {
                grupoId: marker._grupoId || null,
                indice: marker._indicePopup,
                total: reclamos.length,
                incluirTitulo: true,
                incluirAccionesSupervisorDetalle: !!this.rutaDetalleSupervisorId
            }));

            if (!popup.isOpen()) {
                marker.togglePopup();
            }

            setTimeout(() => this.vincularEventosPopupVisualizacionMapbox(marker, reclamo), 0);
        },

        navegarPopupGrupoVisualizacionMapbox(marker, delta) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) return;

            let nuevoIndice = (marker._indicePopup || 0) + delta;
            if (nuevoIndice < 0) nuevoIndice = reclamos.length - 1;
            if (nuevoIndice >= reclamos.length) nuevoIndice = 0;

            this.abrirPopupVisualizacionMapbox(marker, nuevoIndice);
        },

        abrirPopupRutasActivasGoogle(marker, indice = null) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length || !this.mapaRutasActivas) return;

            if (indice !== null) {
                marker._indicePopup = indice;
            } else if (marker._indicePopup === undefined) {
                marker._indicePopup = 0;
            }

            const reclamo = reclamos[marker._indicePopup];
            const infoWindow = marker._infoWindow || new google.maps.InfoWindow();
            marker._infoWindow = infoWindow;

            infoWindow.setContent(this.crearContenidoPopupReclamo(reclamo, {
                grupoId: marker._grupoId || null,
                indice: marker._indicePopup,
                total: reclamos.length,
                incluirTitulo: false
            }));

            if (this.infoWindowAbiertoRutasActivas) {
                this.infoWindowAbiertoRutasActivas.close();
            }

            infoWindow.open(this.mapaRutasActivas, marker);
            this.infoWindowAbiertoRutasActivas = infoWindow;

            google.maps.event.addListenerOnce(infoWindow, 'domready', () => {
                setTimeout(() => this.vincularEventosPopupRutasActivasGoogle(marker, reclamo), 100);
            });
        },

        vincularEventosPopupRutasActivasGoogle(marker, reclamo) {
            const btnDetalle = document.querySelector(`.mapa-popup-detalle[data-reclamo-id="${reclamo.id}"]`);
            if (btnDetalle) {
                btnDetalle.onclick = () => this.verReclamoVistaPrevia(reclamo);
            }

            if (marker._grupoId) {
                const btnPrev = document.querySelector(`.mapa-popup-nav-prev[data-grupo-id="${marker._grupoId}"]`);
                if (btnPrev) {
                    btnPrev.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoRutasActivasGoogle(marker, -1);
                    };
                }

                const btnNext = document.querySelector(`.mapa-popup-nav-next[data-grupo-id="${marker._grupoId}"]`);
                if (btnNext) {
                    btnNext.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoRutasActivasGoogle(marker, 1);
                    };
                }
            }

            const headerElement = document.querySelector('.gm-style-iw-ch');
            if (headerElement) {
                headerElement.innerHTML = this.crearEncabezadoPopupReclamo(reclamo);
            }
        },

        navegarPopupGrupoRutasActivasGoogle(marker, delta) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) return;

            let nuevoIndice = (marker._indicePopup || 0) + delta;
            if (nuevoIndice < 0) nuevoIndice = reclamos.length - 1;
            if (nuevoIndice >= reclamos.length) nuevoIndice = 0;

            this.abrirPopupRutasActivasGoogle(marker, nuevoIndice);
        },

        abrirPopupRutasActivasMapbox(marker, indice = null) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) return;

            if (indice !== null) {
                marker._indicePopup = indice;
            } else if (marker._indicePopup === undefined) {
                marker._indicePopup = 0;
            }

            const reclamo = reclamos[marker._indicePopup];
            let popup = marker.getPopup();
            if (!popup) {
                popup = new mapboxgl.Popup({ offset: 25 });
                marker.setPopup(popup);
            }

            popup.setHTML(this.crearContenidoPopupReclamo(reclamo, {
                grupoId: marker._grupoId || null,
                indice: marker._indicePopup,
                total: reclamos.length,
                incluirTitulo: true
            }));

            if (!popup.isOpen()) {
                marker.togglePopup();
            }

            setTimeout(() => this.vincularEventosPopupRutasActivasMapbox(marker, reclamo), 0);
        },

        vincularEventosPopupRutasActivasMapbox(marker, reclamo) {
            const btnDetalle = document.querySelector(`.mapa-popup-detalle[data-reclamo-id="${reclamo.id}"]`);
            if (btnDetalle) {
                btnDetalle.onclick = () => this.verReclamoVistaPrevia(reclamo);
            }

            if (marker._grupoId) {
                const btnPrev = document.querySelector(`.mapa-popup-nav-prev[data-grupo-id="${marker._grupoId}"]`);
                if (btnPrev) {
                    btnPrev.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoRutasActivasMapbox(marker, -1);
                    };
                }

                const btnNext = document.querySelector(`.mapa-popup-nav-next[data-grupo-id="${marker._grupoId}"]`);
                if (btnNext) {
                    btnNext.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoRutasActivasMapbox(marker, 1);
                    };
                }
            }
        },

        navegarPopupGrupoRutasActivasMapbox(marker, delta) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) return;

            let nuevoIndice = (marker._indicePopup || 0) + delta;
            if (nuevoIndice < 0) nuevoIndice = reclamos.length - 1;
            if (nuevoIndice >= reclamos.length) nuevoIndice = 0;

            this.abrirPopupRutasActivasMapbox(marker, nuevoIndice);
        },

        /**
         * Agrega marcadores numerados de la ruta agrupados por domicilio
         */
        async agregarMarcadoresVisualizacion() {
            this.detenerTickerVisualizacionObra();
            this._limpiarTodosCompanionsObraGoogleVisualizacion();
            this.mapboxObraVisualizacionRefs = [];
            this.marcadoresVisualizacion.forEach((marker) => marker.setMap(null));
            this.marcadoresVisualizacion = [];

            const paradasRuta = this.agruparParadasRutaVistaPrevia(this.reclamosRutaVisualizando);
            let contadorGruposVisualizacion = 0;

            for (const parada of paradasRuta) {
                const reclamoRef = parada.reclamos[0];
                const coordenadas = await this.obtenerCoordenadasReclamo(reclamoRef);

                if (coordenadas) {
                    const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                    const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(parada.reclamos);
                    const cantidadParada = parada.reclamos.length;
                    const esGrupo = cantidadParada > 1;
                    const motivoBadge = esGrupo ? null : reclamoRef.municipalidad_motivo;
                    const badgeCantidad = esGrupo ? cantidadParada : null;

                    const marker = new google.maps.Marker({
                        position: { lat: coordenadas.lat, lng: coordenadas.lng },
                        map: this.mapaVisualizacion,
                        title: esGrupo
                            ? `Parada ${parada.paradaNumero}: ${cantidadParada} reclamos en el mismo domicilio`
                            : `Posición ${parada.paradaNumero}: Reclamo #${reclamoRef.municipalidad_id}`,
                        icon: this.crearIconoNumerado(
                            parada.paradaNumero,
                            colorEstado,
                            prioridadAlta,
                            null,
                            motivoBadge,
                            badgeCantidad
                        ),
                        zIndex: 1000,
                        optimized: false
                    });
                    marker._marcadorRecorridoPrincipal = true;

                    const reclamosGrupo = parada.reclamos.map((r) => ({
                        ...r,
                        posicion: parada.paradaNumero
                    }));

                    marker._reclamo = reclamosGrupo[0];
                    marker._reclamosGrupo = reclamosGrupo;
                    marker._indicePopup = 0;
                    if (esGrupo) {
                        marker._grupoId = `grupo-visualizacion-${++contadorGruposVisualizacion}`;
                    }

                    marker.addListener('click', () => {
                        this.abrirPopupVisualizacionGoogle(marker);
                    });

                    this.marcadoresVisualizacion.push(marker);
                }
            }

            this._sincronizarCompanionsObraGoogleVisualizacion();
            this.iniciarTickerVisualizacionObraSiCorresponde();
        },

        /**
         * Crea el contenido del info window para un reclamo
         */
        claveDomicilioReclamo(reclamo) {
            const domicilio = (reclamo.municipalidad_domicilio || '').trim().toLowerCase();
            const numero = (reclamo.municipalidad_numeroDomicilio || '').trim().toLowerCase();
            if (domicilio) {
                return `dom:${domicilio}|${numero}`;
            }
            return `id:${reclamo.id}`;
        },

        contarUnidadesDomicilioDisponibles() {
            const enRuta = new Set((this.idsReclamosEnRutasActivas || []).map(Number));
            const claves = new Set();
            for (const r of this.reclamos) {
                if (r.municipalidad_estado === 'Completado') continue;
                if (enRuta.has(Number(r.id))) continue;
                claves.add(this.claveDomicilioReclamo(r));
            }
            return claves.size;
        },

        contarUnidadesDomicilioEnRuta(reclamos) {
            const claves = new Set();
            for (const r of reclamos || []) {
                claves.add(this.claveDomicilioReclamo(r));
            }
            return claves.size;
        },

        agruparParadasRutaVistaPrevia(rutaOptimizada) {
            const paradas = [];
            for (const reclamo of rutaOptimizada) {
                const clave = this.claveDomicilioReclamo(reclamo);
                const ultima = paradas[paradas.length - 1];
                if (ultima && ultima.clave === clave) {
                    ultima.reclamos.push(reclamo);
                } else {
                    paradas.push({ clave, reclamos: [reclamo] });
                }
            }
            paradas.forEach((p, i) => { p.paradaNumero = i + 1; });
            return paradas;
        },

        agruparReclamosPorDomicilioVistaPrevia(resultados) {
            const mapa = new Map();

            for (const { reclamo, coords } of resultados) {
                if (!coords) continue;

                const clave = this.claveDomicilioReclamo(reclamo);
                if (!mapa.has(clave)) {
                    mapa.set(clave, { clave, reclamos: [], coordenadas: coords });
                }
                mapa.get(clave).reclamos.push(reclamo);
            }

            return Array.from(mapa.values()).map((grupo) => {
                grupo.reclamos.sort(
                    (a, b) => parseInt(b.municipalidad_id, 10) - parseInt(a.municipalidad_id, 10)
                );
                return grupo;
            });
        },

        crearEncabezadoPopupReclamo(reclamo) {
            const icono = this.iconoMotivoReclamo(reclamo.municipalidad_motivo);
            const color = this.getColorEstado(reclamo.municipalidad_estado || 'Recibido');
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
                        <button type="button" class="mapa-popup-btn mapa-popup-detalle" data-reclamo-id="${reclamo.id}">
                            <i class="bi bi-card-text"></i> Ver detalle
                        </button>
                    </div>
                    ${opciones.incluirAccionesSupervisorDetalle ? this.crearHtmlAccionesSupervisorDetallePopup(reclamo) : ''}
                    ${opciones.incluirDetalleHistorialEjecucion ? this.crearHtmlDetalleHistorialEjecucionPopup(reclamo) : ''}
                </div>
            `;
        },

        crearContenidoPopupGrupoVistaPrevia(reclamo, opciones = {}) {
            return this.crearContenidoPopupReclamo(reclamo, { ...opciones, incluirTitulo: false });
        },

        verReclamoVistaPrevia(reclamo) {
            const completo = this.reclamos.find((r) => r.id === reclamo.id)
                || this.reclamosRutaVisualizando.find((r) => r.id === reclamo.id)
                || (this.historialEjecucionMapa?.reclamos || []).find((r) => r.id === reclamo.id)
                || reclamo;
            this.reclamoSeleccionado = { ...completo };
            const modalEl = document.getElementById('modalVerReclamo');
            if (modalEl) {
                new bootstrap.Modal(modalEl).show();
            }
        },

        vincularEventosPopupVistaPreviaGoogle(marker, reclamo) {
            const btnDetalle = document.querySelector(`.mapa-popup-detalle[data-reclamo-id="${reclamo.id}"]`);
            if (btnDetalle) {
                btnDetalle.onclick = () => this.verReclamoVistaPrevia(reclamo);
            }

            if (marker._grupoId) {
                const btnPrev = document.querySelector(`.mapa-popup-nav-prev[data-grupo-id="${marker._grupoId}"]`);
                if (btnPrev) {
                    btnPrev.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoVistaPrevia(marker, -1);
                    };
                }

                const btnNext = document.querySelector(`.mapa-popup-nav-next[data-grupo-id="${marker._grupoId}"]`);
                if (btnNext) {
                    btnNext.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoVistaPrevia(marker, 1);
                    };
                }
            }

            const headerElement = document.querySelector('.gm-style-iw-ch');
            if (headerElement) {
                headerElement.innerHTML = this.crearEncabezadoPopupReclamo(reclamo);
            }
        },

        vincularEventosPopupVistaPreviaMapbox(marker, reclamo) {
            const btnDetalle = document.querySelector(`.mapa-popup-detalle[data-reclamo-id="${reclamo.id}"]`);
            if (btnDetalle) {
                btnDetalle.onclick = () => this.verReclamoVistaPrevia(reclamo);
            }

            if (marker._grupoId) {
                const btnPrev = document.querySelector(`.mapa-popup-nav-prev[data-grupo-id="${marker._grupoId}"]`);
                if (btnPrev) {
                    btnPrev.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoMapboxVistaPrevia(marker, -1);
                    };
                }

                const btnNext = document.querySelector(`.mapa-popup-nav-next[data-grupo-id="${marker._grupoId}"]`);
                if (btnNext) {
                    btnNext.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoMapboxVistaPrevia(marker, 1);
                    };
                }
            }
        },

        navegarPopupGrupoVistaPrevia(marker, delta) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) return;

            let nuevoIndice = (marker._indicePopup || 0) + delta;
            if (nuevoIndice < 0) nuevoIndice = reclamos.length - 1;
            if (nuevoIndice >= reclamos.length) nuevoIndice = 0;

            this.abrirPopupVistaPreviaGoogle(marker, nuevoIndice);
        },

        abrirPopupVistaPreviaGoogle(marker, indice = null) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) return;

            if (indice !== null) {
                marker._indicePopup = indice;
            } else if (marker._indicePopup === undefined) {
                marker._indicePopup = 0;
            }

            const reclamo = reclamos[marker._indicePopup];
            const infoWindow = marker._infoWindow || new google.maps.InfoWindow();
            marker._infoWindow = infoWindow;

            infoWindow.setContent(this.crearContenidoPopupReclamo(reclamo, {
                grupoId: marker._grupoId || null,
                indice: marker._indicePopup,
                total: reclamos.length,
                incluirTitulo: false
            }));

            if (this.infoWindowAbiertoVistaPrevia) {
                this.infoWindowAbiertoVistaPrevia.close();
            }

            infoWindow.open(this.mapa, marker);
            this.infoWindowAbiertoVistaPrevia = infoWindow;

            google.maps.event.addListenerOnce(infoWindow, 'domready', () => {
                setTimeout(() => this.vincularEventosPopupVistaPreviaGoogle(marker, reclamo), 100);
            });
        },

        abrirInfoWindowGrupoVistaPrevia(marker, indice = null) {
            this.abrirPopupVistaPreviaGoogle(marker, indice);
        },

        abrirPopupMapboxVistaPrevia(marker, indice = null) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) return;

            if (indice !== null) {
                marker._indicePopup = indice;
            } else if (marker._indicePopup === undefined) {
                marker._indicePopup = 0;
            }

            const reclamo = reclamos[marker._indicePopup];
            let popup = marker.getPopup();
            if (!popup) {
                popup = new mapboxgl.Popup({ offset: 25 });
                marker.setPopup(popup);
            }

            popup.setHTML(this.crearContenidoPopupReclamo(reclamo, {
                grupoId: marker._grupoId || null,
                indice: marker._indicePopup,
                total: reclamos.length,
                incluirTitulo: true
            }));

            if (!popup.isOpen()) {
                marker.togglePopup();
            }

            setTimeout(() => this.vincularEventosPopupVistaPreviaMapbox(marker, reclamo), 0);
        },

        navegarPopupGrupoMapboxVistaPrevia(marker, delta) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) return;

            let nuevoIndice = (marker._indicePopup || 0) + delta;
            if (nuevoIndice < 0) nuevoIndice = reclamos.length - 1;
            if (nuevoIndice >= reclamos.length) nuevoIndice = 0;

            this.abrirPopupMapboxVistaPrevia(marker, nuevoIndice);
        },

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
                    preserveViewport: true,
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
            if (this.proveedorMapaVisualizacion === 'mapbox' && this.mapaVisualizacionMapbox) {
                if (this.centrarEnReclamoVisualizacionMapbox(reclamo)) {
                    return;
                }
            }

            let marker = this.marcadoresVisualizacion.find((m) => {
                if (!m._marcadorRecorridoPrincipal) return false;
                if (m._reclamosGrupo) {
                    return m._reclamosGrupo.some((r) => r.id === reclamo.id);
                }
                return m._reclamo && m._reclamo.id === reclamo.id;
            });
            let mapa = this.mapaVisualizacion;

            if (!marker) {
                marker = this.marcadoresRutasActivas.find((m) => {
                    if (!m._marcadorRecorridoPrincipal) return false;
                    if (m._reclamosGrupo) {
                        return m._reclamosGrupo.some((r) => r.id === reclamo.id);
                    }
                    return m._reclamo && m._reclamo.id === reclamo.id;
                });
                mapa = this.mapaRutasActivas;
            }

            if (marker && mapa) {
                if (marker._reclamosGrupo) {
                    const idx = marker._reclamosGrupo.findIndex((r) => r.id === reclamo.id);
                    if (idx >= 0) {
                        marker._indicePopup = idx;
                    }
                }

                mapa.setCenter(marker.getPosition());
                mapa.setZoom(16);

                marker.setAnimation(null);
                marker.setAnimation(google.maps.Animation.BOUNCE);
                setTimeout(() => marker.setAnimation(null), 1500);

                if (mapa === this.mapaVisualizacion) {
                    this.abrirPopupVisualizacionGoogle(marker);
                } else if (mapa === this.mapaRutasActivas) {
                    this.abrirPopupRutasActivasGoogle(marker);
                }
            }
        },

        centrarEnReclamoVisualizacionMapbox(reclamo) {
            const markers = this._marcadoresVisualizacionMapbox || [];
            const marker = markers.find((m) => {
                if (!m._marcadorRecorridoPrincipal) return false;
                if (m._reclamosGrupo) {
                    return m._reclamosGrupo.some((r) => r.id === reclamo.id);
                }
                return m._reclamo && m._reclamo.id === reclamo.id;
            });

            if (!marker || !this.mapaVisualizacionMapbox) {
                return false;
            }

            if (marker._reclamosGrupo) {
                const idx = marker._reclamosGrupo.findIndex((r) => r.id === reclamo.id);
                if (idx >= 0) {
                    marker._indicePopup = idx;
                }
            }

            const lngLat = marker.getLngLat();
            this.mapaVisualizacionMapbox.flyTo({
                center: [lngLat.lng, lngLat.lat],
                zoom: 16,
                duration: 700
            });

            this.abrirPopupVisualizacionMapbox(marker);
            return true;
        },

        /**
         * Centra el mapa en un reclamo específico del modal de todas las rutas
         */
        centrarEnReclamoRutasActivas(reclamo) {
            const marker = this.marcadoresRutasActivas.find((m) => {
                if (!m._marcadorRecorridoPrincipal) return false;
                if (m._reclamosGrupo) {
                    return m._reclamosGrupo.some((r) => r.id === reclamo.id);
                }
                return m._reclamo && m._reclamo.id === reclamo.id;
            });
            if (marker && this.mapaRutasActivas) {
                if (marker._reclamosGrupo) {
                    const idx = marker._reclamosGrupo.findIndex((r) => r.id === reclamo.id);
                    if (idx >= 0) {
                        marker._indicePopup = idx;
                    }
                }

                this.mapaRutasActivas.setCenter(marker.getPosition());
                this.mapaRutasActivas.setZoom(16);

                marker.setAnimation(null);
                marker.setAnimation(google.maps.Animation.BOUNCE);
                setTimeout(() => marker.setAnimation(null), 1500);

                this.abrirPopupRutasActivasGoogle(marker);
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

        crearSvgBadgeCantidad(cantidad, x, y, radio = 6, fontSize = 9) {
            const texto = cantidad > 99 ? '99+' : String(cantidad);
            return `
                <circle cx="${x}" cy="${y}" r="${radio}" fill="#212529" stroke="#FFFFFF" stroke-width="1"/>
                <text x="${x}" y="${y + fontSize * 0.35}" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="${fontSize}" font-weight="700">${texto}</text>
            `;
        },

        agregarMarcadorMapboxRuta(mapa, elemento, coordenadas, anchor = 'center') {
            if (!mapa || !elemento || !coordenadas) {
                return null;
            }
            return new mapboxgl.Marker({ element: elemento, anchor })
                .setLngLat([coordenadas.lng, coordenadas.lat])
                .addTo(mapa);
        },

        finalizarMarcadoresMapboxRuta(mapa) {
            if (!mapa) {
                return Promise.resolve();
            }
            mapa.resize();
            return new Promise((resolve) => {
                const onIdle = () => {
                    mapa.off('idle', onIdle);
                    resolve();
                };
                if (mapa.loaded()) {
                    mapa.once('idle', onIdle);
                } else {
                    mapa.once('load', () => mapa.once('idle', onIdle));
                }
            });
        },

        crearSvgBadgeMotivo(motivo, x, y, radio = 6, fontSize = 9) {
            if (!motivo) return '';
            const icono = this.escaparTextoSvg(this.iconoMotivoReclamo(motivo));

            return `
                <circle cx="${x}" cy="${y}" r="${radio}" fill="#FFFFFF" stroke="#ADB5BD" stroke-width="1"/>
                <text x="${x + 0.4}" y="${y + 0.5}" text-anchor="middle" dominant-baseline="middle" font-family="Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif" font-size="${fontSize}">${icono}</text>
            `;
        },

        reclamoTienePrioridadAlta(reclamo) {
            return String(reclamo?.prioridad || '').trim().toLowerCase() === 'alta';
        },

        marcadorGrupoTienePrioridadAlta(reclamos) {
            return (Array.isArray(reclamos) ? reclamos : []).some((r) => this.reclamoTienePrioridadAlta(r));
        },

        crearSvgBadgePrioridadAlta(x = 26, y = 6, compacto = false) {
            const radio = compacto ? 5.5 : 7;
            const fontSize = compacto ? 8 : 10;
            const salto = compacto ? 2 : 3;
            const strokeWidth = compacto ? 1.25 : 1.5;
            const textoY = compacto ? 2.8 : 3.5;

            return `<g transform="translate(${x}, ${y})">
                <g>
                    <circle cx="0" cy="0" r="${radio}" fill="#DC3545" stroke="#FFFFFF" stroke-width="${strokeWidth}"/>
                    <text x="0" y="${textoY}" text-anchor="middle" font-family="Arial, sans-serif" font-size="${fontSize}" font-weight="bold" fill="#FFFFFF">!</text>
                    <animateTransform attributeName="transform" type="translate" values="0,0; 0,-${salto}; 0,0" dur="0.75s" repeatCount="indefinite"/>
                </g>
            </g>`;
        },

        crearIconoPinMotivo(colorEstado, prioridadAlta, motivo, cantidadGrupo = null) {
            const tienePrioridadAlta = !!prioridadAlta;
            const esNumero = cantidadGrupo !== null && cantidadGrupo > 1;
            const contenidoCentro = esNumero
                ? (cantidadGrupo > 99 ? '99+' : String(cantidadGrupo))
                : this.escaparTextoSvg(this.iconoMotivoReclamo(motivo));
            const fontFamily = esNumero
                ? 'Open Sans, Segoe UI, sans-serif'
                : 'Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif';
            const fontWeight = esNumero ? 'font-weight="700"' : '';
            const fontSize = esNumero ? 11 : 12;

            const pinSvgCore = `
                        <path d="M17 2.5C11.75 2.5 7.5 6.75 7.5 12c0 7.1 9.5 17.5 9.5 17.5S26.5 19.1 26.5 12C26.5 6.75 22.25 2.5 17 2.5Z" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                        <circle cx="17" cy="12" r="7.4" fill="#FFFFFF" opacity="0.94"/>
                        <text x="17.8" y="12.7" text-anchor="middle" dominant-baseline="middle" font-family="${fontFamily}" font-size="${fontSize}" ${fontWeight}>${contenidoCentro}</text>`;

            if (tienePrioridadAlta) {
                const viewTop = -7;
                const viewHeight = 45;
                const displayH = 45;
                const anchorY = Math.round(((30 - viewTop) / viewHeight) * displayH);

                return {
                    url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                        <svg width="34" height="${displayH}" viewBox="0 ${viewTop} 34 ${viewHeight}" xmlns="http://www.w3.org/2000/svg" overflow="visible">
                            ${pinSvgCore}
                            ${this.crearSvgBadgePrioridadAlta(26, 8)}
                        </svg>
                    `)}`,
                    scaledSize: new google.maps.Size(34, displayH),
                    anchor: new google.maps.Point(17, anchorY)
                };
            }

            return {
                url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(`
                    <svg width="34" height="38" viewBox="0 0 34 38" xmlns="http://www.w3.org/2000/svg" overflow="visible">
                        ${pinSvgCore}
                    </svg>
                `)}`,
                scaledSize: new google.maps.Size(34, 38),
                anchor: new google.maps.Point(17, 30)
            };
        },

        crearElementoMapboxPinMotivo(colorEstado, motivo, cantidadGrupo = null, prioridadAlta = false) {
            const esNumero = cantidadGrupo !== null && cantidadGrupo > 1;
            const contenidoCentro = esNumero
                ? (cantidadGrupo > 99 ? '99+' : String(cantidadGrupo))
                : this.escaparTextoSvg(this.iconoMotivoReclamo(motivo));
            const fontFamily = esNumero
                ? 'Open Sans, Segoe UI, sans-serif'
                : 'Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif';
            const fontWeight = esNumero ? 'font-weight="700"' : '';
            const fontSize = esNumero ? 11 : 12;

            const elemento = document.createElement('div');
            elemento.className = 'marker-mapbox-reclamo';
            elemento.style.width = '34px';
            elemento.style.height = '38px';
            elemento.style.lineHeight = '0';
            elemento.style.cursor = 'pointer';
            elemento.innerHTML = `
                <svg width="34" height="38" viewBox="0 0 34 38" xmlns="http://www.w3.org/2000/svg" overflow="visible" aria-hidden="true">
                    <path d="M17 2.5C11.75 2.5 7.5 6.75 7.5 12c0 7.1 9.5 17.5 9.5 17.5S26.5 19.1 26.5 12C26.5 6.75 22.25 2.5 17 2.5Z" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                    <circle cx="17" cy="12" r="7.4" fill="#FFFFFF" opacity="0.94"/>
                    <text x="17.8" y="12.7" text-anchor="middle" dominant-baseline="middle" font-family="${fontFamily}" font-size="${fontSize}" ${fontWeight}>${contenidoCentro}</text>
                    ${prioridadAlta ? this.crearSvgBadgePrioridadAlta(26, 8) : ''}
                </svg>
            `;
            return elemento;
        },

        crearElementoMapboxNumeradoMotivo(numero, colorEstado, motivo, size = 32, badgeCantidad = null, prioridadAlta = false) {
            const half = size / 2;
            const radio = Math.max(11, Math.floor(size * 0.43));
            const fontSize = Math.max(9, Math.floor(size * 0.36));
            const badgeX = size - 7;
            const badgeY = 7;
            const badgeMarkup = badgeCantidad > 1
                ? this.crearSvgBadgeCantidad(badgeCantidad, badgeX, badgeY, Math.max(5, Math.floor(size * 0.18)), Math.max(8, Math.floor(size * 0.28)))
                : this.crearSvgBadgeMotivo(motivo, badgeX, badgeY, Math.max(5, Math.floor(size * 0.18)), Math.max(8, Math.floor(size * 0.28)));
            const badgePrioridadSvg = prioridadAlta ? this.crearSvgBadgePrioridadAlta(7, 7) : '';
            const elemento = document.createElement('div');
            elemento.className = 'marker-mapbox-ruta';
            elemento.style.width = `${size}px`;
            elemento.style.height = `${size}px`;
            elemento.style.lineHeight = '0';
            elemento.style.cursor = 'pointer';
            elemento.innerHTML = `
                <svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg" overflow="visible" aria-hidden="true">
                    <circle cx="${half}" cy="${half}" r="${radio}" fill="${colorEstado}" stroke="#FFFFFF" stroke-width="2"/>
                    <text x="${half}" y="${half + fontSize * 0.35}" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="${fontSize}" font-weight="bold">${numero}</text>
                    ${badgePrioridadSvg}
                    ${badgeMarkup}
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

        async cargarPromediosTiempoMotivo() {
            try {
                const response = await axios.get(BASE_URL + 'api/reclamos/tiempos-promedio');
                this.promediosTiempoMotivoMap = ObraCronometroUtil.indexarPromediosMotivo(response.data);
            } catch (error) {
                console.warn('No se pudieron cargar promedios de tiempo por motivo:', error);
            }
        },

        nivelDemoraObraReclamoSupervisor(reclamo) {
            const motivo = reclamo?.municipalidad_motivo || '';
            const promedio = ObraCronometroUtil.promedioMinutosMotivo(this.promediosTiempoMotivoMap, motivo);
            const s = this.sesionReparacionReclamoSupervisor(reclamo);
            let ms = 0;
            if (s) {
                ms = s.acumuladoMs || 0;
                if (s.activo) {
                    ms += this.ahoraCronometroSupervisor - s.inicioSegmentoMs;
                }
            } else {
                ms = this.msObraSupervisorMapa(reclamo);
            }
            return ObraCronometroUtil.nivelDemoraObra(ms, promedio);
        },

        actualizarIndicadorObraSupervisor(ref, reclamo) {
            if (!ref || !reclamo) {
                return;
            }
            const hms = this.textoCronometroObraSupervisor(reclamo);
            const nivel = this.nivelDemoraObraReclamoSupervisor(reclamo);
            ObraCronometroUtil.actualizarIndicadorObraMapbox(ref.wrap, ref.span, hms, nivel);
        },

        detenerTickerVisualizacionObra() {
            if (this.intervalVisualizacionObra) {
                clearInterval(this.intervalVisualizacionObra);
                this.intervalVisualizacionObra = null;
            }
        },

        refrescarTickerMapaVisualizacionObra() {
            this.ahoraMsVisualizacionObra = Date.now();
            if (this.mapaVisualizacion && this._googleObraVisualizacionMarkers?.length) {
                this._googleObraVisualizacionMarkers.forEach((ref) => {
                    const reclamoId = ref._reclamoIdObra;
                    const r = this.reclamosRutaVisualizando.find((x) => Number(x.id) === Number(reclamoId));
                    if (r && this.reclamoMuestraIndicadorObraSupervisorMapa(r)) {
                        this.actualizarIndicadorObraSupervisor(ref, r);
                    }
                });
            }
            if (this.mapboxObraVisualizacionRefs?.length) {
                this.mapboxObraVisualizacionRefs.forEach((ref) => {
                    const r = this.reclamosRutaVisualizando.find((x) => x.id === ref.reclamoId);
                    if (r && ref.span && this.reclamoMuestraIndicadorObraSupervisorMapa(r)) {
                        this.actualizarIndicadorObraSupervisor(ref, r);
                    }
                });
            }
            if (this.mapaRutasActivas && this._googleObraRutasActivasMarkers?.length) {
                this._googleObraRutasActivasMarkers.forEach((ref) => {
                    const reclamoId = ref._reclamoIdObra;
                    const principal = (this.marcadoresRutasActivas || []).find((m) =>
                        m._companionsObra?.includes(ref)
                    );
                    const r = (principal?._reclamosGrupo || []).find((x) => Number(x.id) === Number(reclamoId));
                    if (r && principal?._ruta && this.reclamoMuestraIndicadorObraEnRuta(r, principal._ruta)) {
                        this.actualizarIndicadorObraSupervisor(ref, r);
                    }
                });
            }
            if (this.mapboxObraRutasActivasRefs?.length) {
                this.mapboxObraRutasActivasRefs.forEach((ref) => {
                    if (ref.reclamo && ref.ruta && ref.span && this.reclamoMuestraIndicadorObraEnRuta(ref.reclamo, ref.ruta)) {
                        this.actualizarIndicadorObraSupervisor(ref, ref.reclamo);
                    }
                });
            }
        },

        iniciarTickerVisualizacionObraSiCorresponde() {
            this.detenerTickerVisualizacionObra();
            const hayDetalle = this.rutaModalEnEjecucionVisualizacion()
                && this.reclamosRutaVisualizando.some((r) => this.reclamoMuestraIndicadorObraSupervisorMapa(r));
            const hayTodasGoogle = this.marcadoresRutasActivas?.some((m) =>
                m._marcadorRecorridoPrincipal && (m._reclamosGrupo || []).some((r) =>
                    m._ruta && this.reclamoMuestraIndicadorObraEnRuta(r, m._ruta)
                )
            );
            const hayTodasMapbox = (this.mapboxObraRutasActivasRefs?.length || 0) > 0;
            if (!hayDetalle && !hayTodasGoogle && !hayTodasMapbox) {
                this._sincronizarCompanionsObraGoogleVisualizacion();
                this._sincronizarCompanionsObraGoogleRutasActivas();
                this._limpiarMarcadoresObraMapboxVisualizacion();
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
            this._limpiarTodosCompanionsObraGoogleVisualizacion();
            this._limpiarMarcadoresObraMapboxVisualizacion();
            // Cerrar info window si está abierto
            if (this.infoWindowAbiertoVisualizacion) {
                this.infoWindowAbiertoVisualizacion.close();
                this.infoWindowAbiertoVisualizacion = null;
            }
            
            this.rutaVisualizando = {};
            this.reclamosRutaVisualizando = [];
            this.mostrarListaRutaVisualizacion = false;
            this.indiceReclamoListaParadaVisualizacion = {};
            this.marcadoresVisualizacion.forEach(marker => marker.setMap(null));
            this.marcadoresVisualizacion = [];
            if (this._marcadoresVisualizacionMapbox?.length) {
                this._marcadoresVisualizacionMapbox.forEach((marker) => marker.remove());
            }
            this._marcadoresVisualizacionMapbox = [];
            this._limpiarMarcadoresObraMapboxVisualizacion();
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

            if (!this.puedeEliminarHojaRuta(ruta)) {
                this.mostrarMensaje(this.motivoNoPuedeEliminarHojaRuta(ruta) || 'No se puede eliminar esta hoja.', 'warning');
                return;
            }

            const nombreRuta = ruta.nombre || 'Sin nombre';
            const confirmacion = await this.mostrarConfirmacion(
                `¿Está seguro que desea eliminar la hoja de ruta "${nombreRuta}"? Los reclamos asignados volverán a estado Recibido.`,
                'Eliminar Hoja de Ruta'
            );

            if (!confirmacion) return;

            try {
                await axios.delete(BASE_URL + 'api/rutas/' + id);
                this.mostrarMensaje('Hoja de ruta eliminada exitosamente', 'success');
                await this.obtenerRutas();
            } catch (error) {
                console.error('Error al eliminar ruta:', error);
                this.mostrarMensaje(
                    error.response?.data?.messages?.error
                        || error.response?.data?.message
                        || 'Error al eliminar la hoja de ruta',
                    'error'
                );
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

            if (!this.puedeEliminarHojaRuta(ruta)) {
                this.mostrarMensaje(this.motivoNoPuedeEliminarHojaRuta(ruta) || 'No se puede eliminar esta hoja.', 'warning');
                return;
            }

            const nombreRuta = ruta.nombre || 'Sin nombre';
            const mensajeConfirmacion = `¿Está seguro de que desea eliminar la hoja de ruta "${nombreRuta}"? Los reclamos en estado Asignado volverán a Recibido. Esta acción no se puede deshacer.`;
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Eliminar Hoja de Ruta');
            
            if (!confirmacion) {
                return;
            }

            try {
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
                this.mostrarMensaje('Error al eliminar la hoja de ruta: ' + (error.response?.data?.messages?.error || error.response?.data?.message || error.message), 'error');
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

            if (!this.puedeEliminarHojaRuta(ruta)) {
                this.mostrarMensaje(this.motivoNoPuedeEliminarHojaRuta(ruta) || 'No se puede eliminar esta hoja.', 'warning');
                return;
            }

            const nombreRuta = ruta.nombre || 'Sin nombre';
            const mensajeConfirmacion = `¿Está seguro de que desea eliminar la hoja de ruta "${nombreRuta}"? Los reclamos en estado Asignado volverán a Recibido. Esta acción no se puede deshacer.`;
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Eliminar Hoja de Ruta');
            
            if (!confirmacion) {
                return;
            }

            try {
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
                this.mostrarMensaje('Error al eliminar la hoja de ruta: ' + (error.response?.data?.messages?.error || error.response?.data?.message || error.message), 'error');
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
                <div class="alert ${alertClass} alert-dismissible fade show mensaje-notificacion" role="alert">
                    <div class="mensaje-notificacion__body">${mensaje}</div>
                    <button type="button" class="btn-close mensaje-notificacion__close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
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
                let resuelto = false;
                const modalHtml = `
                    <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rutas-modal reclamo-confirm-modal">
                                <div class="rutas-modal__header">
                                    <div class="rutas-modal__title">
                                        <span class="rutas-modal__icon"><i class="bi bi-question-circle"></i></span>
                                        <h5>${titulo}</h5>
                                    </div>
                                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p class="reclamo-confirm-modal__message">${mensaje}</p>
                                </div>
                                <div class="rutas-modal__footer rutas-modal__footer--end">
                                    <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal" id="btnCancelar">Cancelar</button>
                                    <button type="button" class="rutas-btn" id="btnConfirmar"><i class="bi bi-check-lg"></i> Confirmar</button>
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

        /**
         * Abre el modal para visualizar todas las rutas (asignadas y no asignadas)
         */
        async abrirModalVisualizarRutas() {
            try {
                this.rutasActivas = this.rutasActivasParaModalTodas();
                this.rutaSeleccionadaVisualizarTodasId = null;
                this.mapboxObraRutasActivasRefs = [];
                this._ultimoFingerprintMapaTodasRutas = null;

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

        rutasActivasParaModalTodas() {
            return (this.rutas || []).filter((ruta) => {
                const estado = (ruta.estado_ejecucion || '').toString().trim().toLowerCase();
                return estado !== 'finalizada';
            });
        },

        modalTodasHojasAbierto() {
            const el = document.getElementById('modalVisualizarRutas');
            if (!el || !el.classList.contains('show')) {
                return false;
            }
            return !!(this.mapaRutasActivas || this.mapaRutasActivasMapbox);
        },

        _fingerprintMapaTodasRutas(rutas, reclamosPorRuta) {
            const partesRuta = (rutas || []).map((r) =>
                [
                    r.id,
                    r.estado_ejecucion || '',
                    r.inicio_ejecucion_at || '',
                    r.color || '',
                    r.cantidadReclamos ?? '',
                    r.cuadrilla_id || '',
                    r.nombre || ''
                ].join('|')
            );
            const partesRec = (rutas || []).map((r) => {
                const recs = reclamosPorRuta?.[r.id] || [];
                return recs.map((x) => {
                    const sr = x.sesion_reparacion || {};
                    return [
                        x.id,
                        x.municipalidad_estado || '',
                        x.prioridad || '',
                        sr.activo ? 1 : 0,
                        sr.acumulado_ms || 0,
                        sr.inicio_segmento_at || ''
                    ].join(':');
                }).join(',');
            });
            return `${partesRuta.join(';')}||${partesRec.join(';')}`;
        },

        async _obtenerReclamosPorRutasActivas(rutas) {
            const out = {};
            const lista = rutas || [];
            await Promise.all(lista.map(async (ruta) => {
                try {
                    const response = await axios.get(BASE_URL + 'api/rutas/' + ruta.id + '/reclamos');
                    out[ruta.id] = response.data || [];
                } catch (error) {
                    console.warn('Error al obtener reclamos de ruta ' + ruta.id + ':', error);
                    out[ruta.id] = [];
                }
            }));
            return out;
        },

        capturarVistaMapaTodasRutas() {
            if (this.proveedorMapaRutasActivas === 'mapbox' && this.mapaRutasActivasMapbox) {
                const c = this.mapaRutasActivasMapbox.getCenter();
                return {
                    proveedor: 'mapbox',
                    lng: c.lng,
                    lat: c.lat,
                    zoom: this.mapaRutasActivasMapbox.getZoom()
                };
            }
            if (this.mapaRutasActivas) {
                const c = this.mapaRutasActivas.getCenter();
                return {
                    proveedor: 'google',
                    lat: c.lat(),
                    lng: c.lng(),
                    zoom: this.mapaRutasActivas.getZoom()
                };
            }
            return null;
        },

        restaurarVistaMapaTodasRutas(vista) {
            if (!vista) {
                return;
            }
            if (vista.proveedor === 'mapbox' && this.mapaRutasActivasMapbox) {
                this.mapaRutasActivasMapbox.jumpTo({
                    center: [vista.lng, vista.lat],
                    zoom: vista.zoom
                });
                return;
            }
            if (this.mapaRutasActivas) {
                this.mapaRutasActivas.setCenter({ lat: vista.lat, lng: vista.lng });
                this.mapaRutasActivas.setZoom(vista.zoom);
            }
        },

        async refrescarVistaTodasHojasTrasSync() {
            if (!this.modalTodasHojasAbierto() || this._refrescandoMapaTodasRutas) {
                return;
            }
            this._refrescandoMapaTodasRutas = true;
            try {
                this.rutasActivas = this.rutasActivasParaModalTodas();

                if (this.rutaSeleccionadaVisualizarTodasId) {
                    const sigue = this.rutasActivas.some(
                        (r) => String(r.id) === String(this.rutaSeleccionadaVisualizarTodasId)
                    );
                    if (!sigue) {
                        this.rutaSeleccionadaVisualizarTodasId = null;
                        this.detenerBrilloRecorridoRutasActivas();
                    }
                }

                const reclamosPorRuta = await this._obtenerReclamosPorRutasActivas(this.rutasActivas);
                const fingerprint = this._fingerprintMapaTodasRutas(this.rutasActivas, reclamosPorRuta);
                if (fingerprint === this._ultimoFingerprintMapaTodasRutas) {
                    return;
                }

                const vista = this.capturarVistaMapaTodasRutas();
                this._ultimoFingerprintMapaTodasRutas = fingerprint;

                if (this.proveedorMapaRutasActivas === 'mapbox') {
                    await this.mostrarTodasLasRutasActivasMapbox({
                        preservarVista: true,
                        reclamosPorRuta
                    });
                } else {
                    await this.mostrarTodasLasRutasActivas({
                        preservarVista: true,
                        reclamosPorRuta
                    });
                }
                this.restaurarVistaMapaTodasRutas(vista);
            } catch (error) {
                console.warn('Refresco mapa todas las hojas:', error);
            } finally {
                this._refrescandoMapaTodasRutas = false;
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
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
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
        async mostrarTodasLasRutasActivas(opciones = {}) {
            const { preservarVista = false, reclamosPorRuta = null } = opciones;
            this.detenerTickerVisualizacionObra();
            this.mapboxObraRutasActivasRefs = [];
            this.limpiarVisualizacionRutasActivas();

            const cacheReclamos = reclamosPorRuta || await this._obtenerReclamosPorRutasActivas(this.rutasActivas);

            for (const ruta of this.rutasActivas) {
                try {
                    const reclamosRuta = cacheReclamos[ruta.id] || [];
                    
                    const colorRuta = ruta.color || '#FF0000';
                    const paradasRuta = this.agruparParadasRutaVistaPrevia(reclamosRuta);
                    let contadorGruposRutasActivas = 0;

                    for (const parada of paradasRuta) {
                        const reclamoRef = parada.reclamos[0];
                        const coordenadas = await this.obtenerCoordenadasReclamo(reclamoRef);

                        if (coordenadas) {
                            const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                            const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(parada.reclamos);
                            const cantidadParada = parada.reclamos.length;
                            const esGrupo = cantidadParada > 1;
                            const motivoBadge = esGrupo ? null : reclamoRef.municipalidad_motivo;
                            const badgeCantidad = esGrupo ? cantidadParada : null;

                            const marker = new google.maps.Marker({
                                position: { lat: coordenadas.lat, lng: coordenadas.lng },
                                map: this.mapaRutasActivas,
                                title: esGrupo
                                    ? `${ruta.nombre || 'Sin nombre'} - Parada ${parada.paradaNumero}: ${cantidadParada} reclamos`
                                    : `${ruta.nombre || 'Sin nombre'} - Pos. ${parada.paradaNumero}`,
                                icon: this.crearIconoNumerado(
                                    parada.paradaNumero,
                                    colorEstado,
                                    prioridadAlta,
                                    null,
                                    motivoBadge,
                                    badgeCantidad
                                ),
                                zIndex: 1000,
                                optimized: false
                            });
                            marker._marcadorRecorridoPrincipal = true;

                            const reclamosGrupo = parada.reclamos.map((r) => ({
                                ...r,
                                posicion: parada.paradaNumero
                            }));

                            marker._reclamo = reclamosGrupo[0];
                            marker._reclamosGrupo = reclamosGrupo;
                            marker._indicePopup = 0;
                            marker._ruta = ruta;
                            if (esGrupo) {
                                marker._grupoId = `grupo-rutas-activas-${ruta.id}-${++contadorGruposRutasActivas}`;
                            }

                            marker.addListener('click', () => {
                                this.abrirPopupRutasActivasGoogle(marker);
                            });

                            this.marcadoresRutasActivas.push(marker);
                        }
                    }

                    const reclamosTrazado = paradasRuta.map((parada) => ({
                        ...parada.reclamos[0],
                        posicion: parada.paradaNumero
                    }));

                    if (reclamosTrazado.length > 1) {
                        const promesasCoords = reclamosTrazado.map((reclamo) =>
                            this.obtenerCoordenadasReclamo(reclamo).then((coords) => ({ reclamo, coords }))
                        );
                        const resultadosTrazado = await Promise.all(promesasCoords);
                        const coordenadas = resultadosTrazado
                            .filter((r) => r.coords)
                            .map((r) => ({ lat: r.coords.lat, lng: r.coords.lng }));

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
                                directionsRenderer._rutaId = ruta.id;
                                directionsRenderer._colorRuta = colorRuta;
                                directionsRenderer._pathCompleto = this.extraerPathRecorridoGoogle(result);
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

            this._sincronizarCompanionsObraGoogleRutasActivas();

            if (!preservarVista) {
                const principales = this.marcadoresRutasActivas.filter((m) => m._marcadorRecorridoPrincipal);
                if (principales.length > 0 && this.mapaRutasActivas) {
                    const bounds = new google.maps.LatLngBounds();
                    principales.forEach((m) => bounds.extend(m.getPosition()));
                    this.mapaRutasActivas.fitBounds(bounds);
                }
            }

            this._ultimoFingerprintMapaTodasRutas = this._fingerprintMapaTodasRutas(
                this.rutasActivas,
                cacheReclamos
            );
            this.iniciarTickerVisualizacionObraSiCorresponde();
        },

        /**
         * Centra el mapa en una ruta específica y anima el brillo sobre su recorrido
         */
        centrarEnRutaActiva(ruta) {
            if (this.proveedorMapaRutasActivas === 'mapbox') {
                this.centrarEnRutaActivaMapbox(ruta);
            } else {
                this.centrarEnRutaActivaGoogle(ruta);
            }
        },

        extraerPathRecorridoGoogle(directionsResult) {
            const route = directionsResult?.routes?.[0];
            if (!route) return [];
            if (route.overview_path?.length) return route.overview_path;
            const path = [];
            (route.legs || []).forEach((leg) => {
                (leg.steps || []).forEach((step) => {
                    (step.path || []).forEach((p) => path.push(p));
                });
            });
            return path;
        },

        pathParcialRecorrido(path, progreso) {
            if (!path?.length) return [];
            const count = Math.max(2, Math.ceil(path.length * Math.min(progreso, 1)));
            return path.slice(0, count);
        },

        normalizarPuntoRecorrido(punto) {
            if (!punto) return null;
            if (typeof punto.lat === 'function') {
                return { lat: punto.lat(), lng: punto.lng() };
            }
            if (Array.isArray(punto)) {
                return { lat: punto[1], lng: punto[0] };
            }
            return { lat: punto.lat, lng: punto.lng };
        },

        distanciaEntrePuntosRecorrido(a, b) {
            const dlat = b.lat - a.lat;
            const dlng = b.lng - a.lng;
            return Math.sqrt(dlat * dlat + dlng * dlng);
        },

        puntoGuiaRecorridoPorProgreso(path, progreso) {
            if (!path?.length) return null;
            const puntos = path.map((p) => this.normalizarPuntoRecorrido(p)).filter(Boolean);
            if (!puntos.length) return null;
            if (puntos.length === 1) return puntos[0];

            const distancias = [0];
            let total = 0;
            for (let i = 1; i < puntos.length; i++) {
                total += this.distanciaEntrePuntosRecorrido(puntos[i - 1], puntos[i]);
                distancias.push(total);
            }
            if (total === 0) return puntos[0];

            const objetivo = total * Math.min(Math.max(progreso, 0), 1);
            for (let i = 1; i < distancias.length; i++) {
                if (objetivo <= distancias[i]) {
                    const segLen = distancias[i] - distancias[i - 1];
                    const frac = segLen > 0 ? (objetivo - distancias[i - 1]) / segLen : 0;
                    return {
                        lat: puntos[i - 1].lat + (puntos[i].lat - puntos[i - 1].lat) * frac,
                        lng: puntos[i - 1].lng + (puntos[i].lng - puntos[i - 1].lng) * frac
                    };
                }
            }
            return puntos[puntos.length - 1];
        },

        crearIconoPuntoBrilloDoradoDataUrl(tamano = 24) {
            const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="${tamano}" height="${tamano}" viewBox="0 0 24 24">
                    <defs>
                        <radialGradient id="brilloDorado" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#FFFEF0"/>
                            <stop offset="40%" stop-color="#FFD700"/>
                            <stop offset="100%" stop-color="#FF8C00" stop-opacity="0.35"/>
                        </radialGradient>
                        <filter id="brilloGlow" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur in="SourceGraphic" stdDeviation="1.8" result="blur"/>
                            <feMerge>
                                <feMergeNode in="blur"/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                    </defs>
                    <circle cx="12" cy="12" r="10" fill="url(#brilloDorado)" filter="url(#brilloGlow)" opacity="0.95"/>
                    <circle cx="12" cy="12" r="4.5" fill="#FFFBE6" opacity="0.95"/>
                </svg>`;
            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg.trim()),
                scaledSize: new google.maps.Size(tamano, tamano),
                anchor: new google.maps.Point(tamano / 2, tamano / 2)
            };
        },

        crearElementoPuntoBrilloDoradoMapbox() {
            const el = document.createElement('div');
            el.className = 'recorrido-punto-brillo-dorado';
            el.innerHTML = '<span class="recorrido-punto-brillo-dorado__halo"></span><span class="recorrido-punto-brillo-dorado__core"></span>';
            return el;
        },

        refsBrilloRecorrido() {
            if (!this._animBrilloRecorridoRefs) {
                this._animBrilloRecorridoRefs = {
                    googleGuia: null,
                    googlePolylines: [],
                    mapboxGuia: null
                };
            }
            return this._animBrilloRecorridoRefs;
        },

        removerPuntoGuiaBrilloGoogle(marker) {
            const refs = this.refsBrilloRecorrido();
            const target = marker || refs.googleGuia;
            if (!target) return;
            target.setMap(null);
            if (refs.googleGuia === target) {
                refs.googleGuia = null;
            }
        },

        removerPuntoGuiaBrilloMapbox(marker) {
            const refs = this.refsBrilloRecorrido();
            const target = marker || refs.mapboxGuia;
            if (!target) return;
            target.remove();
            if (refs.mapboxGuia === target) {
                refs.mapboxGuia = null;
            }
        },

        detenerBrilloRecorridoRutasActivas() {
            const refs = this.refsBrilloRecorrido();

            if (this.brilloRecorridoRutasActivas?.frameId) {
                cancelAnimationFrame(this.brilloRecorridoRutasActivas.frameId);
            }
            if (this.brilloRecorridoRutasActivas?.timeoutId) {
                clearTimeout(this.brilloRecorridoRutasActivas.timeoutId);
            }
            refs.googlePolylines.forEach((p) => p.setMap(null));
            refs.googlePolylines = [];
            this.removerPuntoGuiaBrilloGoogle();
            this.brilloRecorridoRutasActivas = { frameId: null, timeoutId: null, polylines: [] };

            if (this.brilloRecorridoMapboxRutasActivas?.frameId) {
                cancelAnimationFrame(this.brilloRecorridoMapboxRutasActivas.frameId);
            }
            if (this.brilloRecorridoMapboxRutasActivas?.timeoutId) {
                clearTimeout(this.brilloRecorridoMapboxRutasActivas.timeoutId);
            }
            this.removerPuntoGuiaBrilloMapbox();
            this.brilloRecorridoMapboxRutasActivas = { frameId: null, timeoutId: null };

            const map = this.mapaRutasActivasMapbox;
            if (map) {
                ['route-brillo-glow-mb', 'route-brillo-core-mb'].forEach((id) => {
                    if (map.getLayer(id)) map.removeLayer(id);
                    if (map.getSource(id)) map.removeSource(id);
                });
            }
        },

        aplicarEstiloRecorridosGoogle(rutaIdSeleccionada) {
            (this.directionsRenderersRutasActivas || []).forEach((renderer) => {
                if (!renderer._rutaId) return;
                const selected = renderer._rutaId === rutaIdSeleccionada;
                renderer.setOptions({
                    polylineOptions: {
                        strokeColor: renderer._colorRuta || '#FF0000',
                        strokeOpacity: selected ? 0.45 : 0.18,
                        strokeWeight: selected ? 4 : 3
                    }
                });
            });
        },

        aplicarEstiloRecorridosMapbox(rutaIdSeleccionada) {
            const map = this.mapaRutasActivasMapbox;
            if (!map) return;
            (this.capasRecorridoMapboxRutasActivas || []).forEach((capa) => {
                if (!map.getLayer(capa.layerId)) return;
                const selected = capa.rutaId === rutaIdSeleccionada;
                map.setPaintProperty(capa.layerId, 'line-opacity', selected ? 0.45 : 0.18);
                map.setPaintProperty(capa.layerId, 'line-width', selected ? 4 : 3);
            });
        },

        animarBrilloRecorridoGoogle(path, colorRuta) {
            this.detenerBrilloRecorridoRutasActivas();
            if (!path.length || !this.mapaRutasActivas) return;

            const glowOuter = new google.maps.Polyline({
                path: [],
                map: this.mapaRutasActivas,
                strokeColor: '#ffffff',
                strokeOpacity: 0.9,
                strokeWeight: 10,
                zIndex: 2000
            });
            const glowInner = new google.maps.Polyline({
                path: [],
                map: this.mapaRutasActivas,
                strokeColor: colorRuta || '#FF0000',
                strokeOpacity: 1,
                strokeWeight: 5,
                zIndex: 2001
            });

            const puntoInicial = this.puntoGuiaRecorridoPorProgreso(path, 0);
            if (!puntoInicial) return;

            const guiaMarker = new google.maps.Marker({
                position: puntoInicial,
                map: this.mapaRutasActivas,
                icon: this.crearIconoPuntoBrilloDoradoDataUrl(26),
                zIndex: 2100,
                optimized: false
            });

            const refs = this.refsBrilloRecorrido();
            refs.googlePolylines = [glowOuter, glowInner];
            refs.googleGuia = guiaMarker;

            this.brilloRecorridoRutasActivas = {
                frameId: null,
                timeoutId: null,
                polylines: []
            };

            const duracion = 2200;
            const inicio = performance.now();
            const easeOut = (t) => 1 - Math.pow(1 - t, 3);
            let puntoGuiaRemovido = false;

            const tick = (now) => {
                const t = easeOut(Math.min((now - inicio) / duracion, 1));
                const parcial = this.pathParcialRecorrido(path, t);
                glowOuter.setPath(parcial);
                glowInner.setPath(parcial);
                if (!puntoGuiaRemovido) {
                    const guia = this.puntoGuiaRecorridoPorProgreso(path, t);
                    if (guia) {
                        guiaMarker.setPosition(guia);
                    }
                }
                if (t < 1) {
                    this.brilloRecorridoRutasActivas.frameId = requestAnimationFrame(tick);
                } else {
                    puntoGuiaRemovido = true;
                    this.removerPuntoGuiaBrilloGoogle(guiaMarker);
                    this.brilloRecorridoRutasActivas.timeoutId = setTimeout(() => {
                        this.detenerBrilloRecorridoRutasActivas();
                        this.finalizarResaltadoRecorridoRutasActivas();
                    }, 500);
                }
            };
            this.brilloRecorridoRutasActivas.frameId = requestAnimationFrame(tick);
        },

        finalizarResaltadoRecorridoRutasActivas() {
            const rutaId = this.rutaSeleccionadaVisualizarTodasId;
            if (!rutaId) return;

            if (this.proveedorMapaRutasActivas === 'mapbox') {
                const map = this.mapaRutasActivasMapbox;
                (this.capasRecorridoMapboxRutasActivas || []).forEach((capa) => {
                    if (!map?.getLayer(capa.layerId)) return;
                    const selected = capa.rutaId === rutaId;
                    map.setPaintProperty(capa.layerId, 'line-opacity', selected ? 0.92 : 0.18);
                    map.setPaintProperty(capa.layerId, 'line-width', selected ? 5 : 3);
                });
            } else {
                (this.directionsRenderersRutasActivas || []).forEach((renderer) => {
                    if (!renderer._rutaId) return;
                    const selected = renderer._rutaId === rutaId;
                    renderer.setOptions({
                        polylineOptions: {
                            strokeColor: renderer._colorRuta || '#FF0000',
                            strokeOpacity: selected ? 0.92 : 0.18,
                            strokeWeight: selected ? 5 : 3
                        }
                    });
                });
            }
        },

        animarBrilloRecorridoMapbox(coordinates, colorRuta) {
            const map = this.mapaRutasActivasMapbox;
            if (!map || !coordinates || coordinates.length < 2) return;

            this.detenerBrilloRecorridoRutasActivas();

            const glowSourceId = 'route-brillo-glow-mb';
            const coreSourceId = 'route-brillo-core-mb';
            const inicioCoords = coordinates.slice(0, 2);

            const crearFeature = (coords) => ({
                type: 'Feature',
                geometry: { type: 'LineString', coordinates: coords }
            });

            map.addSource(glowSourceId, { type: 'geojson', data: crearFeature(inicioCoords) });
            map.addSource(coreSourceId, { type: 'geojson', data: crearFeature(inicioCoords) });
            map.addLayer({
                id: glowSourceId,
                type: 'line',
                source: glowSourceId,
                layout: { 'line-join': 'round', 'line-cap': 'round' },
                paint: { 'line-color': '#ffffff', 'line-width': 8, 'line-opacity': 0.9 }
            });
            map.addLayer({
                id: coreSourceId,
                type: 'line',
                source: coreSourceId,
                layout: { 'line-join': 'round', 'line-cap': 'round' },
                paint: { 'line-color': colorRuta || '#FF0000', 'line-width': 4, 'line-opacity': 1 }
            });

            const puntoInicial = this.puntoGuiaRecorridoPorProgreso(coordinates, 0);
            if (!puntoInicial) return;

            const guiaEl = this.crearElementoPuntoBrilloDoradoMapbox();
            const guiaMarker = new mapboxgl.Marker({ element: guiaEl, anchor: 'center' })
                .setLngLat([puntoInicial.lng, puntoInicial.lat])
                .addTo(map);

            const refs = this.refsBrilloRecorrido();
            refs.mapboxGuia = guiaMarker;

            this.brilloRecorridoMapboxRutasActivas = { frameId: null, timeoutId: null };

            const duracion = 2200;
            const inicio = performance.now();
            const easeOut = (t) => 1 - Math.pow(1 - t, 3);
            let puntoGuiaRemovido = false;

            const tick = (now) => {
                const t = easeOut(Math.min((now - inicio) / duracion, 1));
                const parcial = this.pathParcialRecorrido(coordinates, t);
                map.getSource(glowSourceId)?.setData(crearFeature(parcial));
                map.getSource(coreSourceId)?.setData(crearFeature(parcial));
                if (!puntoGuiaRemovido) {
                    const guia = this.puntoGuiaRecorridoPorProgreso(coordinates, t);
                    if (guia) {
                        guiaMarker.setLngLat([guia.lng, guia.lat]);
                    }
                }
                if (t < 1) {
                    this.brilloRecorridoMapboxRutasActivas.frameId = requestAnimationFrame(tick);
                } else {
                    puntoGuiaRemovido = true;
                    this.removerPuntoGuiaBrilloMapbox(guiaMarker);
                    this.brilloRecorridoMapboxRutasActivas.timeoutId = setTimeout(() => {
                        this.detenerBrilloRecorridoRutasActivas();
                        this.finalizarResaltadoRecorridoRutasActivas();
                    }, 500);
                }
            };
            this.brilloRecorridoMapboxRutasActivas.frameId = requestAnimationFrame(tick);
        },

        centrarEnRutaActivaGoogle(ruta) {
            if (!this.mapaRutasActivas) return;

            const marcadoresRuta = this.marcadoresRutasActivas.filter(
                (m) => m._ruta && m._ruta.id === ruta.id && m._marcadorRecorridoPrincipal
            );

            if (marcadoresRuta.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                marcadoresRuta.forEach((marcador) => bounds.extend(marcador.getPosition()));
                this.mapaRutasActivas.fitBounds(bounds, 40);
            }

            this.aplicarEstiloRecorridosGoogle(ruta.id);

            const renderer = this.directionsRenderersRutasActivas.find((r) => r._rutaId === ruta.id);
            const path = renderer?._pathCompleto || [];
            if (path.length >= 2) {
                this.animarBrilloRecorridoGoogle(path, renderer._colorRuta || ruta.color || '#FF0000');
            }
        },

        centrarEnRutaActivaMapbox(ruta) {
            const map = this.mapaRutasActivasMapbox;
            if (!map) return;

            const capa = this.capasRecorridoMapboxRutasActivas.find((c) => c.rutaId === ruta.id);
            if (capa?.coordinates?.length) {
                const bounds = new mapboxgl.LngLatBounds();
                capa.coordinates.forEach((c) => bounds.extend(c));
                map.fitBounds(bounds, { padding: 40, duration: 600 });
            }

            this.aplicarEstiloRecorridosMapbox(ruta.id);

            if (capa?.coordinates?.length >= 2) {
                this.animarBrilloRecorridoMapbox(capa.coordinates, capa.color || ruta.color || '#FF0000');
            }
        },

        /**
         * Limpia la visualización de todas las rutas
         */
        limpiarVisualizacionRutasActivas() {
            this.detenerBrilloRecorridoRutasActivas();
            this.capasRecorridoMapboxRutasActivas = [];
            if (this.infoWindowAbiertoRutasActivas) {
                this.infoWindowAbiertoRutasActivas.close();
                this.infoWindowAbiertoRutasActivas = null;
            }

            this._limpiarTodosCompanionsObraGoogleRutasActivas();
            this.marcadoresRutasActivas.forEach((marker) => {
                if (marker) {
                    marker.setMap(null);
                }
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
            this._ultimoFingerprintMapaTodasRutas = null;
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
            
            this.mapaMapbox.resize();
            await this.finalizarMarcadoresMapboxRuta(this.mapaMapbox);
            
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
            
            this.mapaVisualizacionMapbox.resize();
            await this.finalizarMarcadoresMapboxRuta(this.mapaVisualizacionMapbox);
            
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
                zoom: 13,
                attributionControl: false
            });

            await new Promise(resolve => this.mapaRutasActivasMapbox.on('load', resolve));

            this.mapaRutasActivasMapbox.resize();
            await this.finalizarMarcadoresMapboxRuta(this.mapaRutasActivasMapbox);
            
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

            // Agregar marcadores de reclamos NO en ruta (agrupados por domicilio)
            const idsRutaPrevia = this.vistaPrevia.rutaOptimizada.map(r => r.id);
            const reclamosNoEnRuta = this.reclamos.filter(
                (r) => !idsRutaPrevia.includes(r.id) && r.municipalidad_estado !== 'Completado'
            );

            const promesasOtros = reclamosNoEnRuta.map((reclamo) =>
                this.obtenerCoordenadasReclamo(reclamo).then((coords) => ({ reclamo, coords }))
            );
            const resultadosOtros = await Promise.all(promesasOtros);
            const gruposOtros = this.agruparReclamosPorDomicilioVistaPrevia(resultadosOtros);
            let contadorGruposOtrosMapbox = 0;

            for (const grupo of gruposOtros) {
                const reclamoRef = grupo.reclamos[0];
                const coordenadas = grupo.coordenadas;
                const esGrupo = grupo.reclamos.length > 1;
                const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(grupo.reclamos);

                const el = this.crearElementoMapboxPinMotivo(
                    colorEstado,
                    reclamoRef.municipalidad_motivo,
                    esGrupo ? grupo.reclamos.length : null,
                    prioridadAlta
                );

                const marker = this.agregarMarcadorMapboxRuta(
                    this.mapaMapbox,
                    el,
                    coordenadas,
                    'bottom'
                );

                marker._reclamo = reclamoRef;
                marker._reclamosGrupo = esGrupo ? grupo.reclamos : [reclamoRef];
                marker._indicePopup = 0;

                if (esGrupo) {
                    marker._grupoId = `grupo-vista-previa-otros-mb-${++contadorGruposOtrosMapbox}`;
                }

                el.addEventListener('click', () => {
                    if (this.modoEdicion) {
                        this.agregarReclamoARuta(reclamoRef);
                        return;
                    }
                    this.abrirPopupMapboxVistaPrevia(marker);
                });
            }

            // Marcadores numerados por parada (agrupa mismo domicilio)
            const paradasRuta = this.agruparParadasRutaVistaPrevia(this.vistaPrevia.rutaOptimizada);
            let contadorGruposMapboxVistaPrevia = 0;

            for (const parada of paradasRuta) {
                const reclamoRef = parada.reclamos[0];
                const coordenadas = await this.obtenerCoordenadasReclamo(reclamoRef);

                if (coordenadas) {
                    const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                    const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(parada.reclamos);
                    const cantidadParada = parada.reclamos.length;
                    const esGrupo = cantidadParada > 1;
                    const motivoBadge = esGrupo ? null : reclamoRef.municipalidad_motivo;
                    const badgeCantidad = esGrupo ? cantidadParada : null;

                    const el = this.crearElementoMapboxNumeradoMotivo(
                        parada.paradaNumero,
                        colorEstado,
                        motivoBadge,
                        32,
                        badgeCantidad,
                        prioridadAlta
                    );

                    const marker = this.agregarMarcadorMapboxRuta(
                        this.mapaMapbox,
                        el,
                        coordenadas,
                        'center'
                    );

                    const reclamosGrupo = parada.reclamos.map((r) => ({
                        ...r,
                        posicion: parada.paradaNumero
                    }));

                    marker._reclamo = reclamosGrupo[0];
                    marker._reclamosGrupo = reclamosGrupo;
                    marker._indicePopup = 0;
                    if (esGrupo) {
                        marker._grupoId = `grupo-vista-previa-mb-${++contadorGruposMapboxVistaPrevia}`;
                    }

                    el.addEventListener('click', () => {
                        if (this.modoEdicion) {
                            this.agregarReclamoARuta(reclamoRef);
                            return;
                        }
                        this.abrirPopupMapboxVistaPrevia(marker);
                    });
                }
            }

            const reclamosTrazado = paradasRuta.map((parada) => ({
                ...parada.reclamos[0],
                posicion: parada.paradaNumero
            }));

            if (reclamosTrazado.length > 1) {
                await this.trazarRutaMapbox(reclamosTrazado, this.mapaMapbox, this.nuevaRuta.color);
            }

            await this.finalizarMarcadoresMapboxRuta(this.mapaMapbox);
        },

        /**
         * Muestra una ruta individual en Mapbox
         */
        async mostrarRutaEnMapaMapbox() {
            if (!this.mapaVisualizacionMapbox) return;

            this.detenerTickerVisualizacionObra();
            this._limpiarMarcadoresObraMapboxVisualizacion();

            if (this.mapaVisualizacionMapbox.getLayer('route')) this.mapaVisualizacionMapbox.removeLayer('route');
            if (this.mapaVisualizacionMapbox.getSource('route')) this.mapaVisualizacionMapbox.removeSource('route');

            if (this._marcadoresVisualizacionMapbox?.length) {
                this._marcadoresVisualizacionMapbox.forEach((marker) => marker.remove());
            }
            this._marcadoresVisualizacionMapbox = [];

            const paradasRuta = this.agruparParadasRutaVistaPrevia(this.reclamosRutaVisualizando);
            let contadorGruposMapboxVisualizacion = 0;

            for (const parada of paradasRuta) {
                const reclamoRef = parada.reclamos[0];
                const coordenadas = await this.obtenerCoordenadasReclamo(reclamoRef);

                if (coordenadas) {
                    const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                    const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(parada.reclamos);
                    const cantidadParada = parada.reclamos.length;
                    const esGrupo = cantidadParada > 1;
                    const motivoBadge = esGrupo ? null : reclamoRef.municipalidad_motivo;
                    const badgeCantidad = esGrupo ? cantidadParada : null;

                    const el = this.crearElementoMapboxNumeradoMotivo(
                        parada.paradaNumero,
                        colorEstado,
                        motivoBadge,
                        32,
                        badgeCantidad,
                        prioridadAlta
                    );

                    const marker = this.agregarMarcadorMapboxRuta(
                        this.mapaVisualizacionMapbox,
                        el,
                        coordenadas,
                        'center'
                    );

                    const reclamosGrupo = parada.reclamos.map((r) => ({
                        ...r,
                        posicion: parada.paradaNumero
                    }));

                    marker._reclamo = reclamosGrupo[0];
                    marker._reclamosGrupo = reclamosGrupo;
                    marker._indicePopup = 0;
                    marker._marcadorRecorridoPrincipal = true;
                    if (esGrupo) {
                        marker._grupoId = `grupo-visualizacion-mb-${++contadorGruposMapboxVisualizacion}`;
                    }

                    this._marcadoresVisualizacionMapbox.push(marker);

                    el.addEventListener('click', () => {
                        this.abrirPopupVisualizacionMapbox(marker);
                    });

                    this._agregarMarcadoresObraMapboxVisualizacionParada(parada, coordenadas);
                }
            }

            const reclamosTrazado = paradasRuta.map((parada) => ({
                ...parada.reclamos[0],
                posicion: parada.paradaNumero
            }));

            const colorRuta = this.rutaVisualizando.color || '#FF0000';
            if (reclamosTrazado.length > 1) {
                await this.trazarRutaMapbox(reclamosTrazado, this.mapaVisualizacionMapbox, colorRuta);
            }

            await this.finalizarMarcadoresMapboxRuta(this.mapaVisualizacionMapbox);
            this.iniciarTickerVisualizacionObraSiCorresponde();
        },

        /**
         * Muestra todas las rutas (asignadas y no asignadas) en Mapbox
         */
        async mostrarTodasLasRutasActivasMapbox(opciones = {}) {
            if (!this.mapaRutasActivasMapbox) return;

            const { reclamosPorRuta = null } = opciones;
            this.detenerTickerVisualizacionObra();
            this.mapboxObraRutasActivasRefs = [];
            this.detenerBrilloRecorridoRutasActivas();

            // Limpiar capas anteriores (por id de ruta o índice previo)
            const capasPrevias = [...(this.capasRecorridoMapboxRutasActivas || [])];
            capasPrevias.forEach((capa) => {
                const layerId = capa.layerId || capa.sourceId;
                const sourceId = capa.sourceId || capa.layerId;
                if (layerId && this.mapaRutasActivasMapbox.getLayer(layerId)) {
                    this.mapaRutasActivasMapbox.removeLayer(layerId);
                }
                if (sourceId && this.mapaRutasActivasMapbox.getSource(sourceId)) {
                    this.mapaRutasActivasMapbox.removeSource(sourceId);
                }
            });
            this.rutasActivas.forEach((ruta, idx) => {
                if (this.mapaRutasActivasMapbox.getLayer(`route-${idx}`)) {
                    this.mapaRutasActivasMapbox.removeLayer(`route-${idx}`);
                }
                if (this.mapaRutasActivasMapbox.getSource(`route-${idx}`)) {
                    this.mapaRutasActivasMapbox.removeSource(`route-${idx}`);
                }
            });
            this.capasRecorridoMapboxRutasActivas = [];
            
            const marcadoresAnteriores = document.querySelectorAll('#mapaVisualizarRutasMapbox .mapboxgl-marker');
            marcadoresAnteriores.forEach(m => m.remove());

            const cacheReclamos = reclamosPorRuta || await this._obtenerReclamosPorRutasActivas(this.rutasActivas);

            // Procesar cada ruta
            for (let rutaIdx = 0; rutaIdx < this.rutasActivas.length; rutaIdx++) {
                const ruta = this.rutasActivas[rutaIdx];
                
                try {
                    const reclamosRuta = cacheReclamos[ruta.id] || [];
                    const colorRuta = ruta.color || '#FF0000';
                    const paradasRuta = this.agruparParadasRutaVistaPrevia(reclamosRuta);
                    let contadorGruposRutasActivasMb = 0;

                    for (const parada of paradasRuta) {
                        const reclamoRef = parada.reclamos[0];
                        const coordenadas = await this.obtenerCoordenadasReclamo(reclamoRef);

                        if (coordenadas) {
                            const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                            const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(parada.reclamos);
                            const cantidadParada = parada.reclamos.length;
                            const esGrupo = cantidadParada > 1;
                            const motivoBadge = esGrupo ? null : reclamoRef.municipalidad_motivo;
                            const badgeCantidad = esGrupo ? cantidadParada : null;

                            const el = this.crearElementoMapboxNumeradoMotivo(
                                parada.paradaNumero,
                                colorEstado,
                                motivoBadge,
                                30,
                                badgeCantidad,
                                prioridadAlta
                            );

                            const marker = this.agregarMarcadorMapboxRuta(
                                this.mapaRutasActivasMapbox,
                                el,
                                coordenadas,
                                'center'
                            );

                            const reclamosGrupo = parada.reclamos.map((r) => ({
                                ...r,
                                posicion: parada.paradaNumero
                            }));

                            marker._reclamo = reclamosGrupo[0];
                            marker._reclamosGrupo = reclamosGrupo;
                            marker._indicePopup = 0;
                            marker._ruta = ruta;
                            if (esGrupo) {
                                marker._grupoId = `grupo-rutas-activas-mb-${ruta.id}-${++contadorGruposRutasActivasMb}`;
                            }

                            el.addEventListener('click', () => {
                                this.abrirPopupRutasActivasMapbox(marker);
                            });

                            for (let i = 0; i < parada.reclamos.length; i++) {
                                const reclamo = parada.reclamos[i];
                                if (this.reclamoMuestraIndicadorObraEnRuta(reclamo, ruta)) {
                                    const hms = this.textoCronometroObraSupervisor(reclamo);
                                    const nivel = this.nivelDemoraObraReclamoSupervisor(reclamo);
                                    const { wrap, span } = ObraCronometroUtil.crearElementoIndicadorObraMapbox(hms, nivel);
                                    const offsetLng = 0.00028 + (i * 0.00006);
                                    new mapboxgl.Marker({ element: wrap, anchor: 'left' })
                                        .setLngLat([coordenadas.lng + offsetLng, coordenadas.lat])
                                        .addTo(this.mapaRutasActivasMapbox);
                                    if (span) {
                                        this.mapboxObraRutasActivasRefs.push({ reclamo, ruta, span, wrap });
                                    }
                                }
                            }
                        }
                    }

                    const reclamosTrazado = paradasRuta.map((parada) => ({
                        ...parada.reclamos[0],
                        posicion: parada.paradaNumero
                    }));

                    const coordenadasRecorrido = await this.trazarRutaMapboxConId(
                        reclamosTrazado,
                        this.mapaRutasActivasMapbox,
                        colorRuta,
                        `route-${rutaIdx}`
                    );
                    if (coordenadasRecorrido?.length) {
                        this.capasRecorridoMapboxRutasActivas.push({
                            rutaId: ruta.id,
                            sourceId: `route-${rutaIdx}`,
                            layerId: `route-${rutaIdx}`,
                            color: colorRuta,
                            coordinates: coordenadasRecorrido
                        });
                    }
                    
                } catch (error) {
                    console.warn('Error al cargar ruta en Mapbox:', error);
                }
            }

            await this.finalizarMarcadoresMapboxRuta(this.mapaRutasActivasMapbox);
            this._ultimoFingerprintMapaTodasRutas = this._fingerprintMapaTodasRutas(
                this.rutasActivas,
                cacheReclamos
            );
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

                if (coordenadas.length < 2) return null;

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

                    return route.coordinates || null;
                }
            } catch (error) {
                console.error('Error al trazar ruta en Mapbox:', error);
            }
            return null;
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
                const nombreCuadrilla = cuadrilla ? cuadrilla.nombre : 'la cuadrilla';
                const accion = this.rutaParaAsignar.asignada == 1 ? 'cambiar' : 'asignar';
                const mensajeConfirmacion = `¿${accion === 'cambiar' ? 'Cambiar' : 'Asignar'} "${this.rutaParaAsignar.nombre}" a ${nombreCuadrilla}?`;
                const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, accion === 'cambiar' ? 'Cambiar cuadrilla' : 'Asignar cuadrilla');

                if (!confirmacion) return;

                const msgNoAsignable = this.mensajeCuadrillaNoAsignable(
                    cuadrilla,
                    this.rutaParaAsignar.id
                );
                if (msgNoAsignable) {
                    this.mostrarMensaje(msgNoAsignable, 'warning');
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
                    const cuadrillaTabla = this.cuadrillasDisponibles.find(
                        (c) => String(c.id) === String(cuadrillaId)
                    );
                    const msgNoAsignable = this.mensajeCuadrillaNoAsignable(cuadrillaTabla, rutaId);
                    if (msgNoAsignable) {
                        this.mostrarMensaje(msgNoAsignable, 'warning');
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
                await axios.post(BASE_URL + `api/rutas/desasignar/${rutaId}`);
                
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
        await this.cargarPromediosTiempoMotivo();
        await this.obtenerReclamos();
        await this.obtenerRutas();
        if (this.esSupervisorVistaTarjetas) {
            this.configurarModalDetalleSupervisor();
            if (this.solapaRutas === 'activas') {
                this.iniciarPollingSupervisorActivas();
            }
        }
        if (this.puedeVerHistorialEjecuciones) {
            this.configurarModalHistorialMapa();
        }
        this._onClickBitacoraFotoAmpliar = (event) => this.onClickBitacoraFotoAmpliar(event);
        document.addEventListener('click', this._onClickBitacoraFotoAmpliar);
    },

    beforeUnmount() {
        this.cerrarModalFotoBitacoraObra();
        if (this._onClickBitacoraFotoAmpliar) {
            document.removeEventListener('click', this._onClickBitacoraFotoAmpliar);
        }
        this.detenerCronometroSupervisorRutas();
        this.detenerPollingSupervisorActivas();
        this.detenerBrilloRecorridoRutasActivas();
        this.limpiarMapasPreviewSupervisor();
        this.limpiarMapaHistorialEjecucion();
    }
});