const app = Vue.createApp({
    data() {
        return {
            reclamos: [],
            rutas: [],
            rutasPanel: [],
            rutaSeleccionadaId: null,
            vistaOperarioActual: 'panel', // panel | detalle
            modoVistaRuta: 'lista', // lista | mapa
            reclamoSeleccionado: {},
            
            // Variables para filtros - Comentadas para HU futura
            // filtroEstado: '',
            // filtroPrioridad: '',
            // filtroFechaDesde: '',
            // filtroFechaHasta: '',
            
            // Variables para modales
            nuevoEstado: '',
            nuevaObservacion: '',
            
            // Variables para tiempo de reparación
            tiempoReparacion: {
                valor: null,
                unidad: 'minutos' // 'minutos' o 'horas'
            },
            tiempoReparacionRegistrado: null,
            cargandoTiempoReparacion: false,
            
            // Variables para historial
            historialReclamo: [],
            cargandoHistorial: false,
            mostrarHistorialEstado: false,
            
            // Variables para el mapa
            mapaRutas: null,
            marcadoresRutas: [],
            directionsRenderersRutas: [],
            infoWindowAbierto: null,
            
            // Control de proveedores de mapa
            proveedorMapaRutas: 'google', // 'google' o 'mapbox'
            
            // Mapa Mapbox
            mapaRutasMapbox: null,

            // Mapa Google en vista detalle operario
            mapaDetalleGoogle: null,
            marcadoresDetalleGoogle: [],
            directionsRendererDetalleGoogle: null,

            /** Mini mapas en panel de hojas (operario) */
            mapasPreviewOperario: {},
            reclamosCachePorRutaId: {},
            
            // API Key de Mapbox
            mapboxToken: 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ejJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw',
            
            // Rol del usuario
            userRole: window.USER_ROLE || '3',
            
            // Variables para el modal de añadir reclamos
            reclamosRecibidos: [],
            reclamosRecibidosFiltrados: [],
            filtroBusquedaReclamos: '',
            reclamoRecibidoSeleccionado: {},
            añadiendoReclamo: null,
            
            // Variables para la solapa de materiales
            tiposMaterial: [],
            materialesFiltrados: [],
            materialSeleccionado: {
                tipo_id: '',
                material_id: '',
                cantidad: null,
                observacion: ''
            },
            materialNuevo: {
                tipo_id: '',
                nombre: '',
                cantidad: null
            },
            modoMaterialNuevo: false, // false = material existente, true = crear material nuevo
            historialMateriales: [],
            mostrarHistorialMateriales: false,
            cargandoMateriales: false,
            detalleMaterial: null,
            cargandoDetalleMaterial: false,

            /** Reloj para cronómetro de ejecución (persistente vía inicio_ejecucion_at del servidor) */
            ahoraCronometro: Date.now(),
            _tickCronometro: null,

            /** Sesión de reparación en obra por reclamo (solo cliente; clave = id reclamo) */
            reparacionPorReclamoId: {},

            /** ID de fila ruta_ejecucion abierta (servidor); para eventos de reclamo durante la ejecución */
            rutaEjecucionActivaId: null,

            /** Modal acciones: solo pestaña materiales (operario) */
            modalAccionesSoloMateriales: false,

            observacionEjecucionTexto: '',
            historialObservacionesEjecucion: [],
            cargandoObservacionesEjecucion: false,
            guardandoObservacionEjecucion: false
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
                'Pendiente': 2,
                'En ejecución': 3,
                'Completado': 4,
                'Recibido': 5,
                'En plan': 6,
                'Error de datos': 7
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

        reclamosOrdenRuta() {
            if (!this.reclamos || this.reclamos.length === 0) {
                return [];
            }

            const reclamosOrdenados = [...this.reclamos];
            return reclamosOrdenados.sort((a, b) => {
                const posA = Number(a.posicion ?? 999999);
                const posB = Number(b.posicion ?? 999999);

                if (posA === posB) {
                    const fechaA = new Date(a.municipalidad_fechaModificacion || a.municipalidad_fechaInicio || 0);
                    const fechaB = new Date(b.municipalidad_fechaModificacion || b.municipalidad_fechaInicio || 0);
                    return fechaB - fechaA;
                }

                return posA - posB;
            });
        },

        rutaSeleccionada() {
            if (!this.rutaSeleccionadaId) return null;
            return this.rutasPanel.find(r => r.id == this.rutaSeleccionadaId) || null;
        },

        estadoRutaSeleccionada() {
            if (!this.rutaSeleccionada) return 'sin asignar';
            return this.claveEstadoEjecucionRuta(this.rutaSeleccionada);
        },

        rutaSeleccionadaEnEjecucion() {
            return this.estadoRutaSeleccionada === 'en ejecución';
        },

        reclamosConObraActivaEnRuta() {
            if (!this.reclamos || !this.reclamos.length) {
                return [];
            }
            return this.reclamos.filter((r) => {
                const s = this.sesionReparacionReclamo(r);
                return s && s.activo;
            });
        },

        puedeFinalizarEjecucionRutaSeleccionada() {
            return this.reclamosConObraActivaEnRuta.length === 0;
        },

        idsCuadrillasOperario() {
            return this.rutas.map(r => r.cuadrilla_id).filter(Boolean);
        },

        idsCuadrillasComoJefe() {
            return this.rutas
                .filter(r => Number(r.operario_es_jefe) === 1)
                .map(r => r.cuadrilla_id)
                .filter(Boolean);
        },

        puedeOperarRutaSeleccionada() {
            if (!this.esOperario) return true;
            if (!this.rutaSeleccionada) return false;
            return this.idsCuadrillasOperario.includes(this.rutaSeleccionada.cuadrilla_id);
        },

        puedeEditarTareasRutaSeleccionada() {
            if (!this.esOperario) return true;
            if (!this.rutaSeleccionada) return false;
            return this.idsCuadrillasComoJefe.includes(this.rutaSeleccionada.cuadrilla_id) && this.rutaSeleccionadaEnEjecucion;
        },
        
        // Verifica si el usuario es operario
        esOperario() {
            return this.userRole === '3';
        },

        puedeGuardarAccion() {
            if (!this.puedeEditarTareasRutaSeleccionada) return false;
            const tieneEstado = !!this.nuevoEstado;
            const tieneObservacion = this.nuevaObservacion && this.nuevaObservacion.trim().length > 0;
            return tieneEstado || tieneObservacion;
        },
        
        puedeGuardarMaterial() {
            if (!this.puedeEditarTareasRutaSeleccionada) return false;
            // Solo el material es obligatorio, la cantidad es opcional
            return !!this.materialSeleccionado.material_id;
        },
        
        puedeGuardarMaterialNuevo() {
            if (!this.puedeEditarTareasRutaSeleccionada) return false;
            // Para crear material nuevo, solo el nombre es obligatorio
            return !!this.materialNuevo.nombre && this.materialNuevo.nombre.trim().length > 0;
        },
        
        puedeGuardarMaterialSegunModo() {
            // Retorna true si puede guardar según el modo actual
            if (this.modoMaterialNuevo) {
                return this.puedeGuardarMaterialNuevo();
            } else {
                return this.puedeGuardarMaterial();
            }
        },

        puedeGuardarObservacionEjecucion() {
            if (!this.puedeEditarTareasRutaSeleccionada) return false;
            if (this.rutaEjecucionActivaId == null) return false;
            return (this.observacionEjecucionTexto || '').trim().length > 0;
        }
    },

    watch: {
        vistaOperarioActual(val) {
            if (!this.esOperario) return;
            if (val === 'panel') {
                this.$nextTick(() => this.inicializarMapasPreviewOperario());
            } else {
                this.limpiarMapasPreviewOperario();
            }
        },
        rutasPanel() {
            if (this.esOperario && this.vistaOperarioActual === 'panel') {
                this.$nextTick(() => this.inicializarMapasPreviewOperario());
            }
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

        async obtenerReclamosPorRutaSeleccionada() {
            if (!this.rutaSeleccionadaId) {
                this.reclamos = [];
                return;
            }

            try {
                const response = await axios.get(BASE_URL + 'api/rutas/' + this.rutaSeleccionadaId + '/reclamos');
                const rutaActual = this.rutaSeleccionada;
                const reclamosConRuta = (response.data || []).map(reclamo => ({
                    ...reclamo,
                    ruta_id: this.rutaSeleccionadaId,
                    ruta_nombre: rutaActual?.nombre || reclamo.ruta_nombre || 'Ruta',
                    ruta_color: rutaActual?.color || reclamo.ruta_color || '#808080'
                }));

                this.reclamos = this.eliminarDuplicadosReclamos(reclamosConRuta);
                this.aplicarSesionesReparacionDesdeReclamos(this.reclamos);

                await this.sincronizarRutaEjecucionActivaId();

                if (this.esOperario && this.modoVistaRuta === 'mapa') {
                    this.$nextTick(() => this.inicializarMapaDetalleOperarioGoogle());
                }
            } catch (error) {
                console.error('Error al obtener reclamos de la ruta seleccionada:', error);
                this.reclamos = [];
                this.reparacionPorReclamoId = {};
                this.mostrarMensaje('Error al cargar los reclamos de la hoja de ruta', 'error');
            }
        },

        async obtenerTodasLasRutas() {
            try {
                const response = await axios.get(BASE_URL + 'api/rutas');
                this.rutasPanel = response.data || [];
                if (this.esOperario && this.vistaOperarioActual === 'panel') {
                    await this.$nextTick();
                    this.inicializarMapasPreviewOperario();
                }
            } catch (error) {
                console.error('Error al obtener hojas de ruta:', error);
                this.rutasPanel = [];
                this.mostrarMensaje('Error al cargar las hojas de ruta', 'error');
            }
        },

        esRutaDeMiCuadrilla(ruta) {
            if (!ruta || !ruta.cuadrilla_id) return false;
            return this.idsCuadrillasOperario.includes(ruta.cuadrilla_id);
        },

        async seleccionarRuta(ruta) {
            if (!ruta || !ruta.id) return;
            this.limpiarMapasPreviewOperario();
            this.limpiarSesionesReparacionReclamos();
            this.rutaSeleccionadaId = ruta.id;
            this.vistaOperarioActual = 'detalle';
            this.modoVistaRuta = 'lista';
            await this.obtenerReclamosPorRutaSeleccionada();
        },

        async iniciarEjecucionRutaSeleccionada() {
            if (!this.rutaSeleccionadaId) return;
            try {
                const response = await axios.post(BASE_URL + 'api/rutas/operario/iniciar-ejecucion', {
                    ruta_id: this.rutaSeleccionadaId
                });

                const rutaActualizada = response.data?.ruta || null;
                if (rutaActualizada) {
                    this.rutasPanel = this.rutasPanel.map(r => Number(r.id) === Number(rutaActualizada.id)
                        ? { ...r, ...rutaActualizada }
                        : r);
                    this.rutas = this.rutas.map(r => Number(r.id) === Number(rutaActualizada.id)
                        ? { ...r, ...rutaActualizada }
                        : r);
                } else {
                    this.rutasPanel = this.rutasPanel.map(r => Number(r.id) === Number(this.rutaSeleccionadaId)
                        ? { ...r, estado_ejecucion: 'en ejecución' }
                        : r);
                }

                this.rutaEjecucionActivaId = response.data?.ruta_ejecucion_id ?? null;
                if (this.rutaEjecucionActivaId == null) {
                    await this.sincronizarRutaEjecucionActivaId();
                }

                if (this.vistaOperarioActual === 'detalle' && this.rutaSeleccionadaId) {
                    await this.obtenerReclamosPorRutaSeleccionada();
                } else {
                    this.limpiarSesionesReparacionReclamos();
                }
                this.mostrarMensaje(response.data?.mensaje || 'Hoja de ruta iniciada en ejecución.', 'success');
            } catch (error) {
                console.error('Error al iniciar ejecución de ruta:', error);
                const mensaje = error?.response?.data?.message || 'No se pudo iniciar la ejecución de la hoja de ruta.';
                this.mostrarMensaje(mensaje, 'error');
            }
        },

        async finalizarEjecucionRutaSeleccionada() {
            if (!this.rutaSeleccionadaId) return;
            const activos = this.reclamosConObraActivaEnRuta;
            if (activos.length > 0) {
                const refs = activos.map((r) => '#' + (r.municipalidad_id || r.id)).join(', ');
                this.mostrarMensaje(
                    'No se puede finalizar la hoja mientras haya reclamos con trabajo en curso. '
                    + 'Marcá cada uno como Pendiente o Completado antes de continuar: ' + refs,
                    'warning'
                );
                return;
            }
            try {
                const response = await axios.post(BASE_URL + 'api/rutas/operario/finalizar-ejecucion', {
                    ruta_id: this.rutaSeleccionadaId
                });

                const rutaActualizada = response.data?.ruta || null;
                const idFin = this.rutaSeleccionadaId;
                const esFinalizada = rutaActualizada
                    ? this.claveEstadoEjecucionRuta(rutaActualizada) === 'finalizada'
                    : false;

                if (rutaActualizada && !esFinalizada) {
                    this.rutasPanel = this.rutasPanel.map(r => Number(r.id) === Number(rutaActualizada.id)
                        ? { ...r, ...rutaActualizada }
                        : r);
                    this.rutas = this.rutas.map(r => Number(r.id) === Number(rutaActualizada.id)
                        ? { ...r, ...rutaActualizada }
                        : r);
                } else if (rutaActualizada && esFinalizada) {
                    this.rutasPanel = this.rutasPanel.filter(r => Number(r.id) !== Number(idFin));
                    this.rutas = this.rutas.filter(r => Number(r.id) !== Number(idFin));
                }

                this.rutaEjecucionActivaId = null;
                this.limpiarSesionesReparacionReclamos();

                if (esFinalizada) {
                    this.rutaSeleccionadaId = null;
                    this.reclamos = [];
                    this.vistaOperarioActual = 'panel';
                    this.limpiarMapaDetalleOperarioGoogle();
                }

                this.mostrarMensaje(response.data?.mensaje || 'Ejecución finalizada.', 'success');
            } catch (error) {
                console.error('Error al finalizar ejecución de ruta:', error);
                const mensaje = error?.response?.data?.message
                    || (error?.response?.data?.messages && Object.values(error.response.data.messages).flat().join(' '))
                    || 'No se pudo finalizar la ejecución de la hoja de ruta.';
                this.mostrarMensaje(mensaje, 'error');
            }
        },

        esEstadoEjecucionRuta(ruta) {
            if (!ruta) return false;
            const e = (ruta.estado_ejecucion || '').toString().trim().toLowerCase();
            return e === 'en ejecución' || e === 'en ejecucion';
        },

        textoSobreColorRuta(hex) {
            if (!hex || typeof hex !== 'string') return '#fff';
            let h = hex.trim().replace('#', '');
            if (h.length === 3) {
                h = h.split('').map(c => c + c).join('');
            }
            if (h.length !== 6) return '#fff';
            const r = parseInt(h.slice(0, 2), 16);
            const g = parseInt(h.slice(2, 4), 16);
            const b = parseInt(h.slice(4, 6), 16);
            if ([r, g, b].some(n => Number.isNaN(n))) return '#fff';
            const luminancia = 0.299 * r + 0.587 * g + 0.114 * b;
            return luminancia > 165 ? '#1a1a1a' : '#fff';
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

        claseBadgeEstadoEjecucionRuta(ruta) {
            const k = this.claveEstadoEjecucionRuta(ruta);
            if (k === 'en ejecución') return 'bg-success';
            if (k === 'asignada') return 'bg-warning text-dark';
            if (k === 'finalizada') return 'bg-dark';
            return 'bg-secondary';
        },

        tiempoTranscurridoEjecucion(ruta) {
            if (!this.esEstadoEjecucionRuta(ruta)) return '';
            const ini = ruta.inicio_ejecucion_at;
            if (!ini) return '—';
            const t0 = new Date(String(ini).replace(' ', 'T')).getTime();
            if (Number.isNaN(t0)) return '—';
            const sec = Math.max(0, Math.floor((this.ahoraCronometro - t0) / 1000));
            return this.formatearSegundosCronometro(sec);
        },

        formatearSegundosCronometro(totalSeconds) {
            const pad = n => String(n).padStart(2, '0');
            const h = Math.floor(totalSeconds / 3600);
            const m = Math.floor((totalSeconds % 3600) / 60);
            const s = totalSeconds % 60;
            return `${pad(h)}:${pad(m)}:${pad(s)}`;
        },

        iniciarRelojEjecucionOperario() {
            if (!this.esOperario || this._tickCronometro) return;
            this._tickCronometro = setInterval(() => {
                this.ahoraCronometro = Date.now();
                this.refrescarCompanionsObraMapaDetalleOperario();
                this.refrescarCronometrosInfoWindowMapaDetalleOperario();
            }, 1000);
        },

        detenerRelojEjecucionOperario() {
            if (this._tickCronometro) {
                clearInterval(this._tickCronometro);
                this._tickCronometro = null;
            }
        },

        volverAPanelRutas() {
            this.limpiarSesionesReparacionReclamos();
            this.rutaEjecucionActivaId = null;
            this.vistaOperarioActual = 'panel';
            this.rutaSeleccionadaId = null;
            this.reclamos = [];
            this.limpiarMapaDetalleOperarioGoogle();
            this.$nextTick(() => this.inicializarMapasPreviewOperario());
        },

        limpiarMapasPreviewOperario() {
            Object.values(this.mapasPreviewOperario).forEach((ref) => {
                if (!ref) return;
                ref.markers?.forEach((m) => m.setMap(null));
                if (ref.directionsRenderer) {
                    ref.directionsRenderer.setMap(null);
                }
            });
            this.mapasPreviewOperario = {};
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

        coordenadasReclamoPreview(reclamo) {
            if (!reclamo?.coordenadas?.lat || !reclamo?.coordenadas?.lng) {
                return null;
            }
            const lat = parseFloat(reclamo.coordenadas.lat);
            const lng = parseFloat(reclamo.coordenadas.lng);
            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                return null;
            }
            return { lat, lng };
        },

        async esperarGoogleMapsOperario(timeoutMs = 15000) {
            const inicio = Date.now();
            while (!(window.google && window.google.maps)) {
                if (Date.now() - inicio > timeoutMs) {
                    throw new Error('Timeout esperando Google Maps');
                }
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
        },

        async inicializarMapasPreviewOperario() {
            if (!this.esOperario || this.vistaOperarioActual !== 'panel') {
                return;
            }
            this.limpiarMapasPreviewOperario();
            await this.$nextTick();
            try {
                await this.esperarGoogleMapsOperario();
            } catch (error) {
                console.warn('Google Maps no disponible para vistas previas del operario:', error);
                return;
            }
            for (const ruta of this.rutasPanel) {
                if (this.vistaOperarioActual !== 'panel') {
                    break;
                }
                await this.cargarMapaPreviewOperario(ruta);
                await new Promise((resolve) => setTimeout(resolve, 80));
            }
        },

        async cargarMapaPreviewOperario(ruta) {
            const elId = 'mapaPreviewOperarioRuta-' + ruta.id;
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

                for (const reclamo of reclamos) {
                    const coords = this.coordenadasReclamoPreview(reclamo);
                    if (!coords) continue;
                    const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                    const colorPrioridad = this.getColorPrioridad(reclamo.prioridad || 'Baja');
                    const marker = new google.maps.Marker({
                        position: coords,
                        map,
                        icon: this.crearIconoNumerado(reclamo.posicion, colorEstado, colorPrioridad, reclamo.municipalidad_motivo),
                        zIndex: 100
                    });
                    marker._marcadorRecorridoPrincipal = true;
                    markers.push(marker);
                    bounds.extend(coords);
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
                    await this.trazarRutaPreviewOperario(
                        directionsRenderer,
                        principales.map((m) => m.getPosition())
                    );
                }

                this.mapasPreviewOperario = {
                    ...this.mapasPreviewOperario,
                    [ruta.id]: { map, markers, directionsRenderer }
                };
            } catch (error) {
                console.error('Error al cargar vista previa de ruta (operario)', ruta.id, error);
            }
        },

        trazarRutaPreviewOperario(directionsRenderer, coordenadas) {
            if (!coordenadas || coordenadas.length < 2) {
                return Promise.resolve();
            }
            const directionsService = new google.maps.DirectionsService();
            const origin = coordenadas[0];
            const destination = coordenadas[coordenadas.length - 1];
            const waypoints = coordenadas.slice(1, -1).map((coord) => ({
                location: coord,
                stopover: true
            }));

            return new Promise((resolve, reject) => {
                directionsService.route(
                    {
                        origin,
                        destination,
                        waypoints: coordenadas.length > 2 ? waypoints : [],
                        travelMode: google.maps.TravelMode.DRIVING,
                        optimizeWaypoints: false
                    },
                    (result, status) => {
                        if (status === 'OK') {
                            directionsRenderer.setDirections(result);
                            resolve(result);
                        } else {
                            reject(new Error('Error al obtener direcciones: ' + status));
                        }
                    }
                );
            });
        },

        async sincronizarRutaEjecucionActivaId() {
            this.rutaEjecucionActivaId = null;
            if (!this.esOperario || !this.rutaSeleccionadaId) {
                return;
            }
            if (!this.rutaSeleccionadaEnEjecucion) {
                return;
            }
            try {
                const r = await axios.get(BASE_URL + 'api/rutas/' + this.rutaSeleccionadaId + '/ejecucion-activa');
                this.rutaEjecucionActivaId = r.data?.ruta_ejecucion_id ?? null;
            } catch (e) {
                console.warn('No se pudo obtener ejecución activa:', e);
            }
        },

        async registrarEventoEjecucionReclamo(tipo, reclamo) {
            if (!this.puedeEditarTareasRutaSeleccionada || !reclamo || reclamo.id == null) {
                return;
            }
            await axios.post(BASE_URL + 'api/rutas/operario/ejecucion-evento', {
                tipo,
                reclamo_id: reclamo.id
            });
        },

        limpiarSesionesReparacionReclamos() {
            this.reparacionPorReclamoId = {};
        },

        /**
         * Restaura cronómetros de reclamo desde el servidor (eventos inicio/fin), para que sigan al reabrir sesión o la app.
         */
        aplicarSesionesReparacionDesdeReclamos(reclamos) {
            if (!this.esOperario || !reclamos || reclamos.length === 0) {
                this.reparacionPorReclamoId = {};
                return;
            }
            const primera = reclamos[0];
            if (!Object.prototype.hasOwnProperty.call(primera, 'sesion_reparacion')) {
                this.reparacionPorReclamoId = {};
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
                let inicioMs = this.ahoraCronometro;
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
            this.reparacionPorReclamoId = m;
        },

        sesionReparacionReclamo(reclamo) {
            if (!reclamo || reclamo.id == null) return null;
            return this.reparacionPorReclamoId[reclamo.id] || null;
        },

        tiempoMsSesionReparacionReclamo(reclamo) {
            const s = this.sesionReparacionReclamo(reclamo);
            if (!s) return 0;
            let ms = s.acumuladoMs || 0;
            if (s.activo) {
                ms += this.ahoraCronometro - s.inicioSegmentoMs;
            }
            return Math.max(0, ms);
        },

        _aplicarCierreSegmentoReclamoLocal(reclamo) {
            const s = this.sesionReparacionReclamo(reclamo);
            if (!s || !s.activo) return;
            const delta = Date.now() - s.inicioSegmentoMs;
            this.reparacionPorReclamoId = {
                ...this.reparacionPorReclamoId,
                [reclamo.id]: {
                    activo: false,
                    inicioSegmentoMs: s.inicioSegmentoMs,
                    acumuladoMs: s.acumuladoMs + delta
                }
            };
        },

        async sincronizarEstadoReclamoEnEjecucion(reclamo) {
            if (!reclamo || reclamo.id == null) return;
            const est = (reclamo.municipalidad_estado || '').trim();
            if (est === 'En ejecución') {
                return;
            }
            const datos = {
                ...reclamo,
                municipalidad_estado: 'En ejecución',
                municipalidad_fechaModificacion: this.obtenerFechaActualArgentina(),
                prioridad: 'Alta'
            };
            await axios.put(BASE_URL + 'api/reclamos/' + reclamo.id, datos);
            const idx = this.reclamos.findIndex(r => r.id === reclamo.id);
            if (idx !== -1) {
                this.reclamos[idx].municipalidad_estado = 'En ejecución';
                this.reclamos[idx].prioridad = 'Alta';
                this.reclamos[idx].municipalidad_fechaModificacion = datos.municipalidad_fechaModificacion;
            }
        },

        async iniciarReparacionReclamo(reclamo) {
            if (!reclamo || reclamo.id == null) return;
            if (!this.puedeEditarTareasRutaSeleccionada) return;
            if (this.sesionReparacionReclamo(reclamo)) return;
            try {
                await this.sincronizarEstadoReclamoEnEjecucion(reclamo);
                await this.registrarEventoEjecucionReclamo('ejecucion_reclamo_inicio', reclamo);
            } catch (error) {
                console.error('Error al iniciar reclamo en obra:', error);
                const mensaje = error?.response?.data?.message
                    || (error?.response?.data?.messages && Object.values(error.response.data.messages).flat().join(' '))
                    || 'No se pudo iniciar el trabajo en el reclamo.';
                this.mostrarMensaje(mensaje, 'error');
                return;
            }
            this.reparacionPorReclamoId = {
                ...this.reparacionPorReclamoId,
                [reclamo.id]: {
                    activo: true,
                    inicioSegmentoMs: Date.now(),
                    acumuladoMs: 0
                }
            };
            if (this.esOperario && this.modoVistaRuta === 'mapa' && this.mapaDetalleGoogle) {
                this.$nextTick(() => this.dibujarRutaDetalleOperarioGoogle());
            }
        },

        puedeMostrarIniciarReparacionReclamo(reclamo) {
            if (!reclamo || reclamo.id == null) return false;
            if (this.sesionReparacionReclamo(reclamo)) return false;
            const est = (reclamo.municipalidad_estado || '').trim();
            if (est === 'Completado' || est === 'Pendiente') return false;
            const sr = reclamo.sesion_reparacion;
            if (sr && (sr.activo || (Number(sr.acumulado_ms) || 0) > 0)) {
                return false;
            }
            return true;
        },

        puedeMostrarContinuarReparacionReclamo(reclamo) {
            if (!reclamo || reclamo.id == null) return false;
            const est = (reclamo.municipalidad_estado || '').trim();
            return est === 'Pendiente' || est === 'En ejecución';
        },

        async ejecutarCierreReclamoObra(reclamo, tipo) {
            const s = this.sesionReparacionReclamo(reclamo);
            if (!s || !s.activo) {
                this.mostrarMensaje('No hay trabajo en curso en este reclamo.', 'warning');
                return;
            }
            const msTotal = this.tiempoMsSesionReparacionReclamo(reclamo);
            const minutos = Math.max(1, Math.round(msTotal / 60000));

            try {
                await this.registrarEventoEjecucionReclamo('ejecucion_reclamo_fin', reclamo);
            } catch (error) {
                console.error('Error al registrar fin de reclamo en ejecución:', error);
                const mensaje = error?.response?.data?.message
                    || (error?.response?.data?.messages && Object.values(error.response.data.messages).flat().join(' '))
                    || 'No se pudo registrar el fin de trabajo en el reclamo.';
                this.mostrarMensaje(mensaje, 'error');
                return;
            }

            this._aplicarCierreSegmentoReclamoLocal(reclamo);

            const datos = {
                ...reclamo,
                municipalidad_fechaModificacion: this.obtenerFechaActualArgentina()
            };
            if (tipo === 'completado') {
                datos.municipalidad_estado = 'Completado';
                datos.prioridad = null;
            } else {
                datos.municipalidad_estado = 'Pendiente';
                datos.prioridad = 'Alta';
            }

            try {
                await axios.put(BASE_URL + 'api/reclamos/' + reclamo.id, datos);
            } catch (error) {
                console.error('Error al actualizar reclamo:', error);
                this.mostrarMensaje(error?.response?.data?.message || 'No se pudo actualizar el reclamo.', 'error');
                await this.obtenerReclamosPorRutaSeleccionada();
                return;
            }

            try {
                await axios.post(BASE_URL + 'api/reclamos/' + reclamo.id + '/tiempo-reparacion', {
                    tiempo_reparacion_minutos: minutos
                });
            } catch (error) {
                console.warn('Tiempo de reparación automático:', error);
                this.mostrarMensaje('Reclamo actualizado, pero no se pudo guardar el tiempo de reparación automático.', 'warning');
            }

            await this.obtenerReclamosPorRutaSeleccionada();
            this.mostrarMensaje(
                tipo === 'completado'
                    ? 'Reclamo completado. Tiempo de reparación registrado automáticamente.'
                    : 'Reclamo en estado Pendiente para continuar otro día. Tiempo de esta salida registrado.',
                'success'
            );
        },

        async continuarReparacionReclamo(reclamo) {
            if (!reclamo || reclamo.id == null) return;
            const s = this.sesionReparacionReclamo(reclamo);
            if (!s || s.activo) return;
            try {
                await this.sincronizarEstadoReclamoEnEjecucion(reclamo);
                await this.registrarEventoEjecucionReclamo('ejecucion_reclamo_inicio', reclamo);
            } catch (error) {
                console.error('Error al registrar continuación de reclamo en ejecución:', error);
                const mensaje = error?.response?.data?.message
                    || (error?.response?.data?.messages && Object.values(error.response.data.messages).flat().join(' '))
                    || 'No se pudo registrar la continuación de trabajo en el reclamo.';
                this.mostrarMensaje(mensaje, 'error');
                return;
            }
            this.reparacionPorReclamoId = {
                ...this.reparacionPorReclamoId,
                [reclamo.id]: {
                    activo: true,
                    inicioSegmentoMs: Date.now(),
                    acumuladoMs: s.acumuladoMs
                }
            };
            if (this.esOperario && this.modoVistaRuta === 'mapa' && this.mapaDetalleGoogle) {
                this.$nextTick(() => this.dibujarRutaDetalleOperarioGoogle());
            }
        },

        onModalAccionesOculto() {
            this.modalAccionesSoloMateriales = false;
        },

        abrirModalMaterialesReclamo(reclamo) {
            if (!this.puedeEditarTareasRutaSeleccionada) {
                this.mostrarMensaje('Solo el jefe de cuadrilla puede registrar materiales.', 'warning');
                return;
            }
            if (!this.sesionReparacionReclamo(reclamo)) {
                this.mostrarMensaje('Inicie el reclamo en obra para registrar materiales.', 'warning');
                return;
            }
            this.reclamoSeleccionado = { ...reclamo };
            this.modalAccionesSoloMateriales = true;
            this.materialSeleccionado = {
                tipo_id: '',
                material_id: '',
                cantidad: null,
                observacion: ''
            };
            this.materialNuevo = { tipo_id: '', nombre: '', cantidad: null };
            this.modoMaterialNuevo = false;
            this.materialesFiltrados = [];
            this.historialMateriales = [];
            this.mostrarHistorialMateriales = false;

            const modal = new bootstrap.Modal(document.getElementById('modalAcciones'));
            modal.show();
            this.$nextTick(() => {
                const materialesTab = document.getElementById('materiales-tab');
                const cambiarEstadoTab = document.getElementById('cambiar-estado-tab');
                const materialesPane = document.getElementById('materiales');
                const cambiarEstadoPane = document.getElementById('cambiar-estado');
                if (materialesTab && materialesPane) {
                    if (cambiarEstadoTab) {
                        cambiarEstadoTab.classList.remove('active');
                        cambiarEstadoTab.setAttribute('aria-selected', 'false');
                    }
                    if (cambiarEstadoPane) {
                        cambiarEstadoPane.classList.remove('show', 'active');
                    }
                    materialesTab.classList.add('active');
                    materialesTab.setAttribute('aria-selected', 'true');
                    materialesPane.classList.add('show', 'active');
                }
            });
        },

        abrirModalObservacionesEjecucionReclamo(reclamo) {
            if (!this.puedeEditarTareasRutaSeleccionada) {
                this.mostrarMensaje('Solo el jefe de cuadrilla puede registrar observaciones.', 'warning');
                return;
            }
            if (!this.sesionReparacionReclamo(reclamo)) {
                this.mostrarMensaje('Inicie el reclamo en obra para registrar observaciones.', 'warning');
                return;
            }
            if (this.rutaEjecucionActivaId == null) {
                this.mostrarMensaje('No hay ejecución activa de la hoja. Espere a sincronizar o reinicie la vista.', 'warning');
                return;
            }
            this.reclamoSeleccionado = { ...reclamo };
            this.observacionEjecucionTexto = '';
            this.historialObservacionesEjecucion = [];
            const modal = new bootstrap.Modal(document.getElementById('modalObservacionesEjecucionReclamo'));
            modal.show();
            this.cargarHistorialObservacionesEjecucion();
        },

        async cargarHistorialObservacionesEjecucion() {
            if (!this.reclamoSeleccionado?.id || this.rutaEjecucionActivaId == null) {
                return;
            }
            this.cargandoObservacionesEjecucion = true;
            try {
                const r = await axios.get(
                    `${BASE_URL}api/reclamos/${this.reclamoSeleccionado.id}/ejecucion-observaciones`,
                    { params: { ruta_ejecucion_id: this.rutaEjecucionActivaId } }
                );
                this.historialObservacionesEjecucion = Array.isArray(r.data) ? r.data : [];
            } catch (error) {
                console.error('Error al cargar observaciones de ejecución:', error);
                const mensaje = error?.response?.data?.message
                    || (error?.response?.data?.messages && Object.values(error.response.data.messages).flat().join(' '))
                    || 'No se pudo cargar el historial de observaciones.';
                this.mostrarMensaje(mensaje, 'error');
                this.historialObservacionesEjecucion = [];
            } finally {
                this.cargandoObservacionesEjecucion = false;
            }
        },

        async guardarObservacionEjecucion() {
            const texto = (this.observacionEjecucionTexto || '').trim();
            if (!texto || this.rutaEjecucionActivaId == null || !this.reclamoSeleccionado?.id) {
                return;
            }
            this.guardandoObservacionEjecucion = true;
            try {
                await axios.post(
                    `${BASE_URL}api/reclamos/${this.reclamoSeleccionado.id}/ejecucion-observaciones`,
                    {
                        texto,
                        ruta_ejecucion_id: this.rutaEjecucionActivaId
                    }
                );
                this.observacionEjecucionTexto = '';
                this.mostrarMensaje('Observación registrada.', 'success');
                await this.cargarHistorialObservacionesEjecucion();
            } catch (error) {
                console.error('Error al guardar observación de ejecución:', error);
                const mensaje = error?.response?.data?.message
                    || (error?.response?.data?.messages && Object.values(error.response.data.messages).flat().join(' '))
                    || 'No se pudo guardar la observación.';
                this.mostrarMensaje(mensaje, 'error');
            } finally {
                this.guardandoObservacionEjecucion = false;
            }
        },

        abrirModalCambioEstadoSupervisor(reclamo) {
            if (this.esOperario) {
                return;
            }
            this.reclamoSeleccionado = { ...reclamo };
            this.modalAccionesSoloMateriales = false;
            this.nuevoEstado = '';
            this.nuevaObservacion = '';
            this.tiempoReparacion = { valor: null, unidad: 'minutos' };
            this.tiempoReparacionRegistrado = null;
            this.historialReclamo = [];
            this.mostrarHistorialEstado = false;
            this.materialSeleccionado = {
                tipo_id: '',
                material_id: '',
                cantidad: null,
                observacion: ''
            };
            this.materialNuevo = { tipo_id: '', nombre: '', cantidad: null };
            this.modoMaterialNuevo = false;
            this.materialesFiltrados = [];
            this.historialMateriales = [];
            this.mostrarHistorialMateriales = false;

            const modal = new bootstrap.Modal(document.getElementById('modalAcciones'));
            modal.show();
            this.$nextTick(() => {
                const cambiarEstadoTab = document.getElementById('cambiar-estado-tab');
                const materialesTab = document.getElementById('materiales-tab');
                const cambiarEstadoPane = document.getElementById('cambiar-estado');
                const materialesPane = document.getElementById('materiales');
                if (cambiarEstadoTab && materialesTab && cambiarEstadoPane && materialesPane) {
                    materialesTab.classList.remove('active');
                    materialesPane.classList.remove('show', 'active');
                    cambiarEstadoTab.classList.add('active');
                    cambiarEstadoPane.classList.add('show', 'active');
                    cambiarEstadoTab.setAttribute('aria-selected', 'true');
                    materialesTab.setAttribute('aria-selected', 'false');
                }
            });
        },

        textoCronometroReparacionReclamo(reclamo) {
            const s = this.sesionReparacionReclamo(reclamo);
            if (!s) return '';
            let ms = s.acumuladoMs;
            if (s.activo) {
                ms += this.ahoraCronometro - s.inicioSegmentoMs;
            }
            const sec = Math.max(0, Math.floor(ms / 1000));
            return this.formatearSegundosCronometro(sec);
        },

        cambiarModoVistaRuta(modo) {
            this.modoVistaRuta = modo;
            if (modo === 'mapa') {
                this.$nextTick(() => this.inicializarMapaDetalleOperarioGoogle());
            }
        },

        limpiarMapaDetalleOperarioGoogle() {
            this.marcadoresDetalleGoogle.forEach(marker => {
                marker.setMap(null);
                google.maps.event.clearInstanceListeners(marker);
            });
            this.marcadoresDetalleGoogle = [];

            if (this.directionsRendererDetalleGoogle) {
                this.directionsRendererDetalleGoogle.setMap(null);
                this.directionsRendererDetalleGoogle = null;
            }

            if (this.mapaDetalleGoogle) {
                google.maps.event.clearInstanceListeners(this.mapaDetalleGoogle);
                this.mapaDetalleGoogle = null;
            }
        },

        async inicializarMapaDetalleOperarioGoogle() {
            const contenedor = document.getElementById('mapaRutaDetalleOperarioGoogle');
            if (!contenedor) return;

            const centro = { lat: -31.426516, lng: -62.110954 };
            this.mapaDetalleGoogle = new google.maps.Map(contenedor, {
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

            await this.dibujarRutaDetalleOperarioGoogle();
        },

        async dibujarRutaDetalleOperarioGoogle() {
            if (!this.mapaDetalleGoogle) return;

            this.marcadoresDetalleGoogle.forEach((marker) => {
                marker.setMap(null);
                google.maps.event.clearInstanceListeners(marker);
            });
            this.marcadoresDetalleGoogle = [];
            if (this.directionsRendererDetalleGoogle) {
                this.directionsRendererDetalleGoogle.setMap(null);
                this.directionsRendererDetalleGoogle = null;
            }

            const reclamosConCoords = (this.reclamosOrdenRuta || []).filter(r => r.coordenadas && r.coordenadas.lat && r.coordenadas.lng);
            const bounds = new google.maps.LatLngBounds();
            const waypoints = [];

            reclamosConCoords.forEach((reclamo) => {
                const lat = parseFloat(reclamo.coordenadas.lat);
                const lng = parseFloat(reclamo.coordenadas.lng);
                if (Number.isNaN(lat) || Number.isNaN(lng)) return;

                const position = { lat, lng };
                const colorEstado = this.getColorEstado(reclamo.municipalidad_estado);
                const colorPrioridad = this.getColorPrioridad(reclamo.prioridad || 'Baja');
                const marker = new google.maps.Marker({
                    position,
                    map: this.mapaDetalleGoogle,
                    title: `Posición ${reclamo.posicion}: Reclamo #${reclamo.municipalidad_id}`,
                    icon: this.crearIconoNumerado(reclamo.posicion, colorEstado, colorPrioridad, reclamo.municipalidad_motivo),
                    zIndex: 1000
                });

                const infowindow = new google.maps.InfoWindow();

                marker.addListener('click', () => {
                    const rActual = (this.reclamos || []).find((x) => Number(x.id) === Number(reclamo.id)) || reclamo;
                    const contenido = this.construirInfoWindowContentMapaDetalleOperario(rActual);
                    infowindow.setContent(contenido);
                    infowindow.open(this.mapaDetalleGoogle, marker);
                    this.$nextTick(() => this.refrescarCronometrosInfoWindowMapaDetalleOperario());
                });

                marker._reclamoIdDetalle = reclamo.id;
                if (this.reclamoMuestraCamionObraMapaDetalle(reclamo)) {
                    const hms = this.textoCronometroReparacionReclamo(reclamo);
                    const offsetLng = 0.00032;
                    const companion = new google.maps.Marker({
                        position: { lat, lng: lng + offsetLng },
                        map: this.mapaDetalleGoogle,
                        title: `En obra — ${hms}`,
                        icon: this.crearIconoCamionHmsDataUrl(hms),
                        zIndex: 1001,
                        optimized: false
                    });
                    marker._companionObraMapa = companion;
                    this.marcadoresDetalleGoogle.push(companion);
                }

                this.marcadoresDetalleGoogle.push(marker);
                bounds.extend(position);
                waypoints.push(position);
            });

            if (waypoints.length >= 2) {
                const directionsService = new google.maps.DirectionsService();
                this.directionsRendererDetalleGoogle = new google.maps.DirectionsRenderer({
                    map: this.mapaDetalleGoogle,
                    suppressMarkers: true,
                    polylineOptions: {
                        strokeColor: this.rutaSeleccionada?.color || this.rutaSeleccionada?.ruta_color || '#FF6B35',
                        strokeWeight: 4,
                        strokeOpacity: 0.8
                    }
                });

                const origin = waypoints[0];
                const destination = waypoints[waypoints.length - 1];
                const intermediateWaypoints = waypoints.slice(1, -1).map(w => ({ location: w, stopover: true }));

                try {
                    const result = await directionsService.route({
                        origin,
                        destination,
                        waypoints: intermediateWaypoints,
                        travelMode: google.maps.TravelMode.DRIVING
                    });
                    this.directionsRendererDetalleGoogle.setDirections(result);
                } catch (error) {
                    console.error('Error al trazar la ruta detalle en Google Maps:', error);
                }
            }

            if (waypoints.length > 0) {
                this.mapaDetalleGoogle.fitBounds(bounds);
            } else {
                this.mapaDetalleGoogle.setCenter({ lat: -31.426516, lng: -62.110954 });
                this.mapaDetalleGoogle.setZoom(13);
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
         * Compatibilidad: solo supervisores usan el modal de cambio de estado.
         */
        cambiarEstado(reclamo) {
            if (this.esOperario) {
                this.mostrarMensaje('Use los botones Listo o Pendiente junto al reclamo en la hoja en ejecución.', 'info');
                return;
            }
            this.abrirModalCambioEstadoSupervisor(reclamo);
        },

        /**
         * Guarda el cambio de estado
         */
        async guardarCambioEstado() {
            const nuevoEstadoSeleccionado = this.nuevoEstado;
            const observacionLimpia = this.nuevaObservacion ? this.nuevaObservacion.trim() : '';

            if (!nuevoEstadoSeleccionado && !observacionLimpia) {
                this.mostrarMensaje('Debe seleccionar un nuevo estado o ingresar una observación', 'warning');
                return;
            }

            try {
                const datosActualizacion = {
                    ...this.reclamoSeleccionado,
                    municipalidad_fechaModificacion: this.obtenerFechaActualArgentina()
                };

                if (nuevoEstadoSeleccionado) {
                    datosActualizacion.municipalidad_estado = nuevoEstadoSeleccionado;
                } else {
                    datosActualizacion.municipalidad_estado = this.reclamoSeleccionado.municipalidad_estado;
                }

                if (observacionLimpia) {
                    datosActualizacion.observacion = observacionLimpia;
                }

                // Actualizar prioridad según el nuevo estado
                if (nuevoEstadoSeleccionado === 'En ejecución' || nuevoEstadoSeleccionado === 'Pendiente') {
                    datosActualizacion.prioridad = 'Alta';
                } else if (nuevoEstadoSeleccionado === 'Completado') {
                    datosActualizacion.prioridad = null;
                }

                await axios.put(BASE_URL + 'api/reclamos/' + this.reclamoSeleccionado.id, datosActualizacion);
                
                // Actualizar el reclamo en la lista local
                const index = this.reclamos.findIndex(r => r.id === this.reclamoSeleccionado.id);
                if (index !== -1) {
                    this.reclamos[index].municipalidad_fechaModificacion = datosActualizacion.municipalidad_fechaModificacion;
                    
                    if (nuevoEstadoSeleccionado) {
                        this.reclamos[index].municipalidad_estado = nuevoEstadoSeleccionado;
                    
                        // También actualizar la prioridad en la lista local
                        if (nuevoEstadoSeleccionado === 'En ejecución' || nuevoEstadoSeleccionado === 'Pendiente') {
                            this.reclamos[index].prioridad = 'Alta';
                        } else if (nuevoEstadoSeleccionado === 'Completado') {
                            this.reclamos[index].prioridad = null;
                        }
                    }
                }

                this.reclamoSeleccionado.municipalidad_fechaModificacion = datosActualizacion.municipalidad_fechaModificacion;
                if (nuevoEstadoSeleccionado) {
                    this.reclamoSeleccionado.municipalidad_estado = nuevoEstadoSeleccionado;
                    if (nuevoEstadoSeleccionado === 'En ejecución' || nuevoEstadoSeleccionado === 'Pendiente') {
                        this.reclamoSeleccionado.prioridad = 'Alta';
                    } else if (nuevoEstadoSeleccionado === 'Completado') {
                        this.reclamoSeleccionado.prioridad = null;
                    }
                }

                const mensajeExito = nuevoEstadoSeleccionado
                    ? `Estado actualizado a: ${nuevoEstadoSeleccionado}${observacionLimpia ? ' y observación registrada.' : ''}`
                    : 'Observación registrada correctamente.';
                
                this.mostrarMensaje(mensajeExito, 'success');

                // Si el historial está visible, actualizarlo
                if (this.mostrarHistorialEstado) {
                    await this.cargarHistorial();
                }

                // Limpiar formulario
                this.nuevoEstado = '';
                this.nuevaObservacion = '';

            } catch (error) {
                console.error('Error al cambiar estado:', error);
                
                // Manejar error específico cuando el reclamo está cerrado
                if (error.response && error.response.status === 403) {
                    const mensajeError = error.response.data.message || 'No se puede cambiar el estado de un reclamo que ya ha sido cerrado formalmente.';
                    this.mostrarMensaje(mensajeError, 'error');
                    
                    // Cerrar el modal si está abierto
                    const modalAcciones = bootstrap.Modal.getInstance(document.getElementById('modalAcciones'));
                    if (modalAcciones) {
                        modalAcciones.hide();
                    }
                    
                    // Recargar los reclamos para actualizar la lista
                    if (this.esOperario && this.rutaSeleccionadaId) {
                        this.obtenerReclamosPorRutaSeleccionada();
                    } else {
                        this.obtenerReclamos();
                    }
                } else {
                    // Error genérico
                    const mensajeError = error.response && error.response.data.message 
                        ? error.response.data.message 
                        : 'Error al actualizar el reclamo';
                    this.mostrarMensaje(mensajeError, 'error');
                }
            }
        },

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
            if (estado === 'Asignado') return 'border-info'; // Celeste #0DCAF0
            if (estado === 'Pendiente') return 'border-danger'; // Rojo #FF0000
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
                case 'Asignado': return 'bg-info text-dark'; // Celeste #0DCAF0
                case 'Pendiente': return 'bg-danger'; // Rojo #FF0000
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
                case 'Asignado': return 'text-info'; // Celeste #0DCAF0
                case 'Pendiente': return 'text-danger'; // Rojo #FF0000
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
         * Alterna la visualización del historial de cambios de estado
         */
        async toggleHistorialEstado() {
            this.mostrarHistorialEstado = !this.mostrarHistorialEstado;
            
            if (this.mostrarHistorialEstado && this.historialReclamo.length === 0) {
                await this.cargarHistorial();
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
            if (this.esOperario) {
                // El mapa del operario muestra todas las rutas de su cuadrilla.
                await this.obtenerReclamos();
            }

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
                        icon: this.crearIconoNumerado(reclamo.posicion, colorEstado, colorPrioridad, reclamo.municipalidad_motivo),
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

            if (this.esOperario && this.rutaSeleccionadaId) {
                this.obtenerReclamosPorRutaSeleccionada();
            }
        },

        /**
         * Obtiene el color según el estado del reclamo (igual que en rutas.js)
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

        /**
         * Crea un icono numerado para los marcadores de la ruta (igual que en rutas.js)
         * Si tiene prioridad Alta, muestra animación de pulso
         */
        crearIconoNumerado(numero, colorEstado, colorPrioridad, motivo = null) {
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

        /** Icono camión + cronómetro (misma idea que vista supervisor en rutas). */
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

        reclamoMuestraCamionObraMapaDetalle(reclamo) {
            if (!reclamo || reclamo.id == null) return false;
            if (!this.rutaSeleccionadaEnEjecucion) return false;
            if (String(reclamo.municipalidad_estado || '').trim() !== 'En ejecución') return false;
            return !!this.sesionReparacionReclamo(reclamo);
        },

        refrescarCompanionsObraMapaDetalleOperario() {
            if (!this.mapaDetalleGoogle || !this.marcadoresDetalleGoogle?.length) return;
            this.marcadoresDetalleGoogle.forEach((m) => {
                if (!m._companionObraMapa || m._reclamoIdDetalle == null) return;
                const r = (this.reclamos || []).find((x) => Number(x.id) === Number(m._reclamoIdDetalle));
                if (!r || !this.reclamoMuestraCamionObraMapaDetalle(r)) {
                    m._companionObraMapa.setMap(null);
                    m._companionObraMapa = null;
                    return;
                }
                const hms = this.textoCronometroReparacionReclamo(r);
                m._companionObraMapa.setIcon(this.crearIconoCamionHmsDataUrl(hms));
            });
        },

        /** Actualiza HH:MM:SS en el globo del mapa (el HTML del InfoWindow no es reactivo). */
        refrescarCronometrosInfoWindowMapaDetalleOperario() {
            document.querySelectorAll('[data-map-iw-crono-reclamo-id]').forEach((el) => {
                const rid = parseInt(el.getAttribute('data-map-iw-crono-reclamo-id'), 10);
                if (Number.isNaN(rid)) {
                    return;
                }
                const r = (this.reclamos || []).find((x) => Number(x.id) === rid);
                if (!r || !this.sesionReparacionReclamo(r)) {
                    el.textContent = '—';
                    return;
                }
                el.textContent = this.textoCronometroReparacionReclamo(r);
            });
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
         * InfoWindow del mapa de hoja (operario): datos + mismas acciones que en vista lista.
         */
        construirInfoWindowContentMapaDetalleOperario(reclamo) {
            const wrap = document.createElement('div');
            wrap.className = 'map-detalle-iw';
            wrap.innerHTML = this.crearContenidoInfoWindow(reclamo);

            if (!this.esOperario || !this.puedeEditarTareasRutaSeleccionada) {
                return wrap;
            }

            const acciones = document.createElement('div');
            acciones.className = 'map-detalle-iw-acciones border-top pt-2 mt-2 d-flex flex-wrap align-items-center gap-1';

            const rid = String(reclamo.id);

            if (this.puedeMostrarIniciarReparacionReclamo(reclamo)) {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'btn btn-sm btn-success';
                b.innerHTML = '<i class="bi bi-play-fill text-white"></i> Iniciar';
                b.setAttribute('data-map-accion', 'iniciar');
                b.setAttribute('data-reclamo-id', rid);
                acciones.appendChild(b);
            }

            const ses = this.sesionReparacionReclamo(reclamo);
            if (ses) {
                const crono = document.createElement('span');
                crono.className = 'badge bg-dark font-monospace map-detalle-iw-crono';
                crono.setAttribute('data-map-iw-crono-reclamo-id', rid);
                crono.textContent = this.textoCronometroReparacionReclamo(reclamo);
                crono.title = 'Tiempo en obra';
                acciones.appendChild(crono);

                const bMat = document.createElement('button');
                bMat.type = 'button';
                bMat.className = 'btn btn-sm btn-outline-secondary';
                bMat.innerHTML = '<i class="bi bi-box-seam"></i>';
                bMat.title = 'Materiales utilizados';
                bMat.setAttribute('data-map-accion', 'materiales');
                bMat.setAttribute('data-reclamo-id', rid);
                acciones.appendChild(bMat);

                const bObs = document.createElement('button');
                bObs.type = 'button';
                bObs.className = 'btn btn-sm btn-outline-secondary';
                bObs.innerHTML = '<i class="bi bi-chat-left-text"></i>';
                bObs.title = 'Observaciones en esta ejecución';
                bObs.setAttribute('data-map-accion', 'observaciones');
                bObs.setAttribute('data-reclamo-id', rid);
                acciones.appendChild(bObs);

                if (ses.activo) {
                    const bOk = document.createElement('button');
                    bOk.type = 'button';
                    bOk.className = 'btn btn-sm btn-success';
                    bOk.innerHTML = '<i class="bi bi-check-lg"></i>';
                    bOk.title = 'Marcar como completado';
                    bOk.setAttribute('data-map-accion', 'completado');
                    bOk.setAttribute('data-reclamo-id', rid);
                    acciones.appendChild(bOk);

                    const bPen = document.createElement('button');
                    bPen.type = 'button';
                    bPen.className = 'btn btn-sm btn-warning text-dark';
                    bPen.innerHTML = '<i class="bi bi-pause-circle"></i>';
                    bPen.title = 'Pendiente para otro día';
                    bPen.setAttribute('data-map-accion', 'pendiente');
                    bPen.setAttribute('data-reclamo-id', rid);
                    acciones.appendChild(bPen);
                } else if (this.puedeMostrarContinuarReparacionReclamo(reclamo)) {
                    const bCont = document.createElement('button');
                    bCont.type = 'button';
                    bCont.className = 'btn btn-sm btn-success';
                    bCont.innerHTML = '<i class="bi bi-play-fill text-white"></i>';
                    bCont.title = 'Continuar ejecución';
                    bCont.setAttribute('data-map-accion', 'continuar');
                    bCont.setAttribute('data-reclamo-id', rid);
                    acciones.appendChild(bCont);
                }
            }

            if (acciones.childNodes.length) {
                wrap.appendChild(acciones);
            }

            wrap.addEventListener('click', (e) => this.onMapaDetalleInfoWindowAccion(e));

            return wrap;
        },

        onMapaDetalleInfoWindowAccion(e) {
            const btn = e.target.closest('[data-map-accion]');
            if (!btn) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            const rid = parseInt(btn.getAttribute('data-reclamo-id'), 10);
            const accion = btn.getAttribute('data-map-accion');
            const r = (this.reclamos || []).find((x) => Number(x.id) === rid);
            if (!r || r.id == null) {
                return;
            }
            if (accion === 'iniciar') {
                void this.iniciarReparacionReclamo(r);
                return;
            }
            if (accion === 'materiales') {
                this.abrirModalMaterialesReclamo(r);
                return;
            }
            if (accion === 'observaciones') {
                this.abrirModalObservacionesEjecucionReclamo(r);
                return;
            }
            if (accion === 'completado') {
                void this.ejecutarCierreReclamoObra(r, 'completado');
                return;
            }
            if (accion === 'pendiente') {
                void this.ejecutarCierreReclamoObra(r, 'pendiente');
                return;
            }
            if (accion === 'continuar') {
                void this.continuarReparacionReclamo(r);
            }
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
    },

    /**
     * Carga los tipos de materiales y materiales cuando se abre la solapa de materiales
     */
    async cargarMateriales() {
        if (this.tiposMaterial.length === 0) {
            await this.obtenerTiposMaterial();
        }
        await this.filtrarMaterialesPorTipo();
    },

    /**
     * Alterna entre el modo de material existente y crear material nuevo
     */
    alternarModoMaterial() {
        this.modoMaterialNuevo = !this.modoMaterialNuevo;
        
        // Limpiar campos al cambiar de modo
        if (this.modoMaterialNuevo) {
            // Cambiando a modo crear nuevo - limpiar campos de material existente
            this.materialSeleccionado.material_id = '';
            this.materialSeleccionado.cantidad = null;
        } else {
            // Cambiando a modo existente - limpiar campos de material nuevo
            this.materialNuevo = {
                tipo_id: '',
                nombre: '',
                cantidad: null
            };
        }
    },

    /**
     * Obtiene los tipos de materiales
     */
    async obtenerTiposMaterial() {
        try {
            const response = await axios.get(BASE_URL + 'api/materiales/tipos');
            this.tiposMaterial = response.data;
        } catch (error) {
            console.error('Error al obtener tipos de materiales:', error);
            this.mostrarMensaje('Error al cargar los tipos de materiales', 'error');
            this.tiposMaterial = [];
        }
    },

    /**
     * Filtra los materiales por tipo seleccionado
     */
    async filtrarMaterialesPorTipo() {
        try {
            const params = {};
            if (this.materialSeleccionado.tipo_id) {
                params.tipo_id = this.materialSeleccionado.tipo_id;
            }
            
            const response = await axios.get(BASE_URL + 'api/reclamos/materiales/por-tipo', { params });
            this.materialesFiltrados = response.data;
            
            // Si cambió el tipo, limpiar la selección de material
            if (this.materialSeleccionado.material_id) {
                const materialExiste = this.materialesFiltrados.find(m => m.id == this.materialSeleccionado.material_id);
                if (!materialExiste) {
                    this.materialSeleccionado.material_id = '';
                }
            }
        } catch (error) {
            console.error('Error al filtrar materiales:', error);
            this.mostrarMensaje('Error al cargar los materiales', 'error');
            this.materialesFiltrados = [];
        }
    },

    /**
     * Guarda un material utilizado en el reclamo
     */
    async guardarMaterialReclamo() {
        if (!this.puedeGuardarMaterial) {
            this.mostrarMensaje('Debe seleccionar un material', 'warning');
            return;
        }

        if (!this.reclamoSeleccionado.id) {
            this.mostrarMensaje('Error: No hay reclamo seleccionado', 'error');
            return;
        }

        try {
            const datos = {
                material_id: this.materialSeleccionado.material_id,
                cantidad: this.materialSeleccionado.cantidad || null, // Cantidad opcional
                observacion: this.materialSeleccionado.observacion || null
            };

            await axios.post(BASE_URL + 'api/reclamos/' + this.reclamoSeleccionado.id + '/materiales', datos);
            
            this.mostrarMensaje('Material registrado exitosamente', 'success');
            
            // Limpiar el formulario
            this.materialSeleccionado = {
                tipo_id: this.materialSeleccionado.tipo_id, // Mantener el tipo seleccionado
                material_id: '',
                cantidad: null,
                observacion: ''
            };
            
            // Si el historial está visible, actualizarlo
            if (this.mostrarHistorialMateriales) {
                await this.obtenerHistorialMateriales();
            }
            
        } catch (error) {
            console.error('Error al guardar material:', error);
            const mensajeError = error.response && error.response.data && error.response.data.message 
                ? error.response.data.message 
                : 'Error al guardar el material';
            this.mostrarMensaje(mensajeError, 'error');
        }
    },

    /**
     * Crea un material nuevo y lo registra en material_reclamo
     */
    async guardarMaterialNuevoYReclamo() {
        if (!this.puedeGuardarMaterialNuevo) {
            this.mostrarMensaje('Debe ingresar el nombre del material', 'warning');
            return;
        }

        if (!this.reclamoSeleccionado.id) {
            this.mostrarMensaje('Error: No hay reclamo seleccionado', 'error');
            return;
        }

        try {
            const nombreMaterial = this.materialNuevo.nombre.trim();
            
            // Paso 0: Verificar si el material ya existe
            const responseVerificacion = await axios.get(BASE_URL + 'api/materiales/verificar', {
                params: { nombre: nombreMaterial }
            });
            
            if (responseVerificacion.data.existe) {
                this.mostrarMensaje(
                    `El material "${nombreMaterial}" ya existe. Por favor, selecciónelo de la lista de materiales existentes.`,
                    'warning'
                );
                // Cambiar automáticamente al modo de material existente
                this.modoMaterialNuevo = false;
                // Limpiar el campo de nombre nuevo
                this.materialNuevo.nombre = '';
                // Recargar materiales para que aparezca en la lista
                await this.filtrarMaterialesPorTipo();
                return;
            }
            
            // Guardar la cantidad ingresada para usarla en material_reclamo
            const cantidadParaReclamo = this.materialNuevo.cantidad || null;
            
            // Paso 1: Crear el material nuevo (siempre con cantidad 0 en la tabla material)
            const datosMaterial = {
                nombre: nombreMaterial,
                idTipo: this.materialNuevo.tipo_id || null,
                cantidad: 0 // Siempre 0 cuando se crea desde esta interfaz
            };

            const responseMaterial = await axios.post(BASE_URL + 'api/materiales', datosMaterial);
            const materialCreado = responseMaterial.data;
            
            // Paso 2: Registrar el material en material_reclamo con la cantidad ingresada
            const datosMaterialReclamo = {
                material_id: materialCreado.id,
                cantidad: cantidadParaReclamo, // La cantidad ingresada solo va a material_reclamo
                observacion: this.materialSeleccionado.observacion || null
            };

            await axios.post(BASE_URL + 'api/reclamos/' + this.reclamoSeleccionado.id + '/materiales', datosMaterialReclamo);
            
            this.mostrarMensaje(`Material "${materialCreado.nombre}" creado y registrado exitosamente`, 'success');
            
            // Limpiar los formularios
            this.materialSeleccionado = {
                tipo_id: '',
                material_id: '',
                cantidad: null,
                observacion: ''
            };
            this.materialNuevo = {
                tipo_id: '',
                nombre: '',
                cantidad: null
            };
            
            // Recargar materiales para que aparezca el nuevo material en la lista
            await this.filtrarMaterialesPorTipo();
            
            // Si el historial está visible, actualizarlo
            if (this.mostrarHistorialMateriales) {
                await this.obtenerHistorialMateriales();
            }
            
        } catch (error) {
            console.error('Error al crear y guardar material nuevo:', error);
            const mensajeError = error.response && error.response.data && error.response.data.message 
                ? error.response.data.message 
                : 'Error al crear y guardar el material nuevo';
            this.mostrarMensaje(mensajeError, 'error');
        }
    },

    /**
     * Obtiene el historial de materiales del reclamo
     */
    async obtenerHistorialMateriales() {
        if (!this.reclamoSeleccionado.id) {
            return;
        }

        this.cargandoMateriales = true;
        
        try {
            const response = await axios.get(BASE_URL + 'api/reclamos/' + this.reclamoSeleccionado.id + '/materiales');
            this.historialMateriales = response.data;
        } catch (error) {
            console.error('Error al obtener historial de materiales:', error);
            this.mostrarMensaje('Error al cargar el historial de materiales', 'error');
            this.historialMateriales = [];
        } finally {
            this.cargandoMateriales = false;
        }
    },

    /**
     * Alterna la visualización del historial de materiales
     */
    async toggleHistorialMateriales() {
        this.mostrarHistorialMateriales = !this.mostrarHistorialMateriales;
        
        if (this.mostrarHistorialMateriales && this.historialMateriales.length === 0) {
            await this.obtenerHistorialMateriales();
        }
    },

    /**
     * Muestra el detalle completo de un material_reclamo
     */
    async verDetalleMaterial(materialReclamoId) {
        this.cargandoDetalleMaterial = true;
        this.detalleMaterial = null;
        
        try {
            const response = await axios.get(BASE_URL + 'api/reclamos/materiales/' + materialReclamoId + '/detalle');
            this.detalleMaterial = response.data;
            
            const modal = new bootstrap.Modal(document.getElementById('modalDetalleMaterial'));
            modal.show();
        } catch (error) {
            console.error('Error al obtener detalle de material:', error);
            this.mostrarMensaje('Error al cargar el detalle del material', 'error');
        } finally {
            this.cargandoDetalleMaterial = false;
        }
    },

    /**
     * Carga el tiempo de reparación registrado para el reclamo actual
     */
    async cargarTiempoReparacion() {
        if (!this.reclamoSeleccionado.id) {
            return;
        }

        this.cargandoTiempoReparacion = true;
        this.tiempoReparacionRegistrado = null;
        
        try {
            const response = await axios.get(BASE_URL + 'api/reclamos/' + this.reclamoSeleccionado.id + '/tiempo-reparacion');
            if (response.data) {
                this.tiempoReparacionRegistrado = response.data;
            }
        } catch (error) {
            console.error('Error al cargar tiempo de reparación:', error);
            // Si no existe tiempo registrado, no es un error crítico
            if (error.response && error.response.status !== 404) {
                this.mostrarMensaje('Error al cargar el tiempo de reparación', 'error');
            }
        } finally {
            this.cargandoTiempoReparacion = false;
        }
    },

    /**
     * Guarda o actualiza el tiempo de reparación de un reclamo
     */
    async guardarTiempoReparacion() {
        if (!this.puedeGuardarTiempoReparacion) {
            this.mostrarMensaje('Debe ingresar un tiempo de reparación válido', 'warning');
            return;
        }

        if (!this.reclamoSeleccionado.id) {
            this.mostrarMensaje('Error: No hay reclamo seleccionado', 'error');
            return;
        }

        try {
            // Convertir a minutos si viene en horas
            let tiempoMinutos = this.tiempoReparacion.valor;
            if (this.tiempoReparacion.unidad === 'horas') {
                tiempoMinutos = tiempoMinutos * 60;
            }
            tiempoMinutos = Math.round(tiempoMinutos);

            const datos = {
                tiempo_reparacion_minutos: tiempoMinutos
            };

            await axios.post(BASE_URL + 'api/reclamos/' + this.reclamoSeleccionado.id + '/tiempo-reparacion', datos);
            
            this.mostrarMensaje('Tiempo de reparación registrado correctamente. Promedios actualizados.', 'success');
            
            // Recargar el tiempo registrado
            await this.cargarTiempoReparacion();
            
            // Limpiar el formulario
            this.tiempoReparacion = {
                valor: null,
                unidad: 'minutos'
            };
            
        } catch (error) {
            console.error('Error al guardar tiempo de reparación:', error);
            const mensajeError = error.response && error.response.data && error.response.data.message 
                ? error.response.data.message 
                : 'Error al guardar el tiempo de reparación';
            this.mostrarMensaje(mensajeError, 'error');
        }
    },

    /**
     * Formatea el tiempo en minutos a formato legible (minutos/horas)
     */
    formatearTiempo(tiempoMinutos) {
        if (!tiempoMinutos) {
            return 'No especificado';
        }

        const minutos = parseInt(tiempoMinutos);
        
        if (minutos < 60) {
            return `${minutos} ${minutos === 1 ? 'minuto' : 'minutos'}`;
        } else {
            const horas = Math.floor(minutos / 60);
            const minutosRestantes = minutos % 60;
            
            if (minutosRestantes === 0) {
                return `${horas} ${horas === 1 ? 'hora' : 'horas'}`;
            } else {
                return `${horas} ${horas === 1 ? 'hora' : 'horas'} y ${minutosRestantes} ${minutosRestantes === 1 ? 'minuto' : 'minutos'}`;
            }
        }
    }
},

    async mounted() {
        if (this.esOperario) {
            await this.obtenerRutasOperario();
            await this.obtenerTodasLasRutas();
            this.iniciarRelojEjecucionOperario();
            await this.$nextTick();
            this.inicializarMapasPreviewOperario();
        } else {
            await this.obtenerReclamos();
            await this.obtenerRutasOperario();
        }
    },

    beforeUnmount() {
        this.detenerRelojEjecucionOperario();
        this.limpiarMapasPreviewOperario();
    }
});