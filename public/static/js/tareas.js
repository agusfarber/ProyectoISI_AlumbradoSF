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
            infoWindowAbiertoMapaDetalleOperario: null,
            proveedorMapaDetalleOperario: 'google',
            mapaDetalleOperarioMapbox: null,
            _marcadoresDetalleOperarioMapbox: [],
            _mapboxObraDetalleOperarioRefs: [],
            _googleObraDetalleOperarioRefs: [],

            /** Mini mapas en panel de hojas (operario) */
            mapasPreviewOperario: {},
            reclamosCachePorRutaId: {},
            cuadrillasDisponibles: [],
            /** Índice del reclamo visible en paradas con varios reclamos (vista lista operario) */
            indiceReclamoListaParadaOperario: {},
            
            // API Key de Mapbox
            mapboxToken: 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ejJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw',
            
            // Rol del usuario
            userRole: window.USER_ROLE || '3',
            
            // Variables para el modal de añadir reclamos
            reclamosRecibidos: [],
            reclamosRecibidosFiltrados: [],
            indiceReclamoParadaAñadir: {},
            filtroBusquedaReclamos: '',
            reclamoRecibidoSeleccionado: {},
            añadiendoReclamo: null,
            añadiendoParadaClave: null,
            
            // Variables para la solapa de materiales
            tiposMaterial: [],
            materialesFiltrados: [],
            materialSeleccionado: {
                tipo_id: '',
                material_id: '',
                cantidad: null,
                observacion: ''
            },
            filtroBusquedaMaterial: '',
            cargandoCatalogoMateriales: false,
            guardandoMaterialObra: false,
            historialMateriales: [],
            mostrarHistorialMateriales: true,
            cargandoMateriales: false,
            materialesCountPorReclamoOperario: {},
            detalleMaterial: null,
            cargandoDetalleMaterial: false,
            eliminandoMaterialReclamoId: null,

            /** Reloj para cronómetro de ejecución (persistente vía inicio_ejecucion_at del servidor) */
            ahoraCronometro: Date.now(),
            _tickCronometro: null,

            /** Sync liviano operario: visibility + polling espaciado */
            _pollOperarioLiviano: null,
            _sincronizandoOperarioLiviano: false,
            _omitirReinicioPreviewPorSync: false,
            intervaloPollOperarioLiviano: 10000,
            _ultimoFingerprintRutasOperario: null,
            _ultimoFingerprintReclamosOperario: null,
            _onVisibilityOperarioLiviano: null,

            /** Sesión de reparación en obra por reclamo (solo cliente; clave = id reclamo) */
            reparacionPorReclamoId: {},

            /** Popover confirmar acción de reclamo en lista: { clave, tipo } */
            confirmarAccionParada: null,

            /** ID de fila ruta_ejecucion abierta (servidor); para eventos de reclamo durante la ejecución */
            rutaEjecucionActivaId: null,

            /** Modal acciones: solo pestaña materiales (operario) */
            modalAccionesSoloMateriales: false,
            modalMaterialesSoloLectura: false,

            /** Prompt materiales antes de completar */
            promptMaterialesDetalle: '',
            _resolverPromptMateriales: null,

            /** Confirmación eliminar material */
            confirmarEliminarMaterialNombre: '',
            _resolverConfirmarEliminarMaterial: null,

            observacionEjecucionTexto: '',
            historialObservacionesEjecucion: [],
            observacionesPorReclamoOperario: {},
            archivoFotoBitacora: null,
            previewFotoBitacora: null,
            guardandoFotoBitacora: false,
            promediosTiempoMotivoMap: {},
            cargandoObservacionesEjecucion: false,
            guardandoObservacionEjecucion: false,

            bitacoraFotoAmpliadaUrl: '',
            bitacoraFotoAmpliadaCaption: '',
            bitacoraFotoAmpliadaActiva: false
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

        paradasOrdenRuta() {
            return this.agruparParadasRutaVistaPrevia(this.reclamosOrdenRuta);
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

        puedeAñadirReclamosRutaSeleccionada() {
            if (!this.esOperario) return true;
            if (!this.rutaSeleccionada) return false;
            const cid = Number(this.rutaSeleccionada.cuadrilla_id);
            if (!this.idsCuadrillasComoJefe.some((id) => Number(id) === cid)) {
                return false;
            }
            return this.claveEstadoEjecucionRuta(this.rutaSeleccionada) === 'en ejecución';
        },

        proximaPosicionAlAñadir() {
            if (!this.reclamos || this.reclamos.length === 0) return 1;
            const maxPos = Math.max(
                ...this.reclamos.map((r) => Number(r.posicion) || 0),
                0
            );
            return maxPos + 1;
        },

        paradasReclamosAñadir() {
            return this.agruparParadasPorDomicilio(this.reclamosRecibidosFiltrados);
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
            const cantidad = Number(this.materialSeleccionado.cantidad);
            return !!this.materialSeleccionado.material_id
                && Number.isFinite(cantidad)
                && cantidad >= 1;
        },

        materialesCatalogoFiltrados() {
            const termino = (this.filtroBusquedaMaterial || '').trim().toLowerCase();
            const lista = Array.isArray(this.materialesFiltrados) ? this.materialesFiltrados : [];
            if (!termino) return lista;
            return lista.filter((m) => (m.nombre || '').toLowerCase().includes(termino));
        },

        nombreMaterialSeleccionadoObra() {
            const id = this.materialSeleccionado.material_id;
            if (!id) return '';
            const mat = (this.materialesFiltrados || []).find((m) => String(m.id) === String(id));
            return mat ? mat.nombre : 'Material seleccionado';
        },

        historialBitacoraEjecucionOrdenado() {
            const lista = Array.isArray(this.historialObservacionesEjecucion)
                ? [...this.historialObservacionesEjecucion]
                : [];
            lista.sort((a, b) => {
                const ta = new Date(a.created_at || 0).getTime();
                const tb = new Date(b.created_at || 0).getTime();
                if (ta !== tb) {
                    return ta - tb;
                }
                return (Number(a.id) || 0) - (Number(b.id) || 0);
            });
            return lista;
        },

        puedeGuardarObservacionEjecucion() {
            if (!this.puedeRegistrarBitacoraEjecucion(this.reclamoSeleccionado)) {
                return false;
            }
            return (this.observacionEjecucionTexto || '').trim().length > 0;
        },

        puedeSubirFotoBitacoraEjecucion() {
            return this.puedeRegistrarBitacoraEjecucion(this.reclamoSeleccionado) && !!this.archivoFotoBitacora;
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
            if (!this.esOperario || this.vistaOperarioActual !== 'panel') {
                return;
            }
            if (this._omitirReinicioPreviewPorSync) {
                return;
            }
            this.$nextTick(() => this.inicializarMapasPreviewOperario());
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

        async obtenerReclamosPorRutaSeleccionada(opciones = {}) {
            const { silencioso = false } = opciones;
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

                const nuevos = this.eliminarDuplicadosReclamos(reclamosConRuta);
                const fpNuevo = this._fingerprintReclamosOperario(nuevos);
                const datosCambiaron = fpNuevo !== this._ultimoFingerprintReclamosOperario;

                // Sync liviano sin cambios: no tocar estado (evita parpadeo de lista/mapa/modales)
                if (silencioso && !datosCambiaron) {
                    return;
                }

                this.reclamos = nuevos;
                this.aplicarSesionesReparacionDesdeReclamos(this.reclamos);
                if (!silencioso || datosCambiaron) {
                    this.indiceReclamoListaParadaOperario = {};
                }

                await this.sincronizarRutaEjecucionActivaId();

                if (!silencioso || datosCambiaron) {
                    await Promise.all([
                        this.cargarObservacionesEjecucionOperario(this.reclamos),
                        this.cargarMaterialesCountOperario(this.reclamos)
                    ]);
                }

                this._ultimoFingerprintReclamosOperario = fpNuevo;

                if (this.esOperario && this.modoVistaRuta === 'mapa') {
                    const mapaListo = !!(this.mapaDetalleGoogle || this.mapaDetalleOperarioMapbox);
                    if (silencioso && mapaListo) {
                        if (datosCambiaron) {
                            this.$nextTick(() => this.redibujarMapaDetalleOperario());
                        }
                    } else {
                        this.$nextTick(() => this.inicializarMapaDetalleOperario());
                    }
                }
            } catch (error) {
                if (silencioso) {
                    console.warn('Recarga silenciosa reclamos operario:', error);
                    return;
                }
                console.error('Error al obtener reclamos de la ruta seleccionada:', error);
                this.reclamos = [];
                this.reparacionPorReclamoId = {};
                this.mostrarMensaje('Error al cargar los reclamos de la hoja de ruta', 'error');
            }
        },

        async obtenerTodasLasRutas() {
            try {
                const url = this.esOperario
                    ? BASE_URL + 'api/rutas/operario/mis-rutas'
                    : BASE_URL + 'api/rutas';
                const response = await axios.get(url);
                const rutas = response.data || [];
                this.rutasPanel = rutas;
                if (this.esOperario) {
                    this.rutas = rutas;
                }
                if (this.esOperario && this.vistaOperarioActual === 'panel') {
                    await this.$nextTick();
                    this.inicializarMapasPreviewOperario();
                }
            } catch (error) {
                console.error('Error al obtener hojas de ruta:', error);
                this.rutasPanel = [];
                if (this.esOperario) {
                    this.rutas = [];
                }
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
            this.indiceReclamoListaParadaOperario = {};
            this.rutaSeleccionadaId = ruta.id;
            this.vistaOperarioActual = 'detalle';
            this.modoVistaRuta = 'lista';
            await this.obtenerReclamosPorRutaSeleccionada();
            // Primera sync pronto por si la hoja ya no existe / cambió mientras abría
            void this.sincronizarVistaOperarioLiviana();
        },

        async iniciarEjecucionRutaSeleccionada() {
            if (!this.rutaSeleccionadaId) return;

            const nombreHoja = this.rutaSeleccionada?.nombre || 'esta hoja de ruta';
            const confirmacion = await this.mostrarConfirmacion(
                `¿Confirmás iniciar la ejecución de “${nombreHoja}”?`,
                'Iniciar ejecución'
            );
            if (!confirmacion) {
                return;
            }

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

            const nombreHoja = this.rutaSeleccionada?.nombre || 'esta hoja de ruta';
            const confirmacion = await this.mostrarConfirmacion(
                `¿Confirmás finalizar la ejecución de “${nombreHoja}”? Se cerrará la hoja y no se puede deshacer.`,
                'Finalizar ejecución'
            );
            if (!confirmacion) {
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
                    this.limpiarMapaDetalleOperario();
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

        msTranscurridoEjecucionRuta(ruta) {
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
            return Math.max(0, this.ahoraCronometro - t0);
        },

        nivelDemoraEjecucionRuta(ruta) {
            return ObraCronometroUtil.nivelDemoraEjecucionRuta(
                this.msTranscurridoEjecucionRuta(ruta),
                ruta?.tiempoEstimado
            );
        },

        claseCronometroEjecucionRuta(ruta) {
            if (!this.esEstadoEjecucionRuta(ruta)) {
                return 'badge bg-dark font-monospace cronometro-ejecucion cronometro-badge-con-ico';
            }
            return ObraCronometroUtil.clasesBadgeCronometroEjecucionRuta(this.nivelDemoraEjecucionRuta(ruta));
        },

        tiempoTranscurridoEjecucion(ruta) {
            if (!this.esEstadoEjecucionRuta(ruta)) return '';
            const ini = ruta.inicio_ejecucion_at;
            if (!ini) return '—';
            const sec = Math.floor(this.msTranscurridoEjecucionRuta(ruta) / 1000);
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

        _fingerprintRutasOperario(rutas) {
            return (rutas || [])
                .map((r) => [
                    r.id,
                    r.estado_ejecucion || '',
                    r.inicio_ejecucion_at || '',
                    r.cuadrilla_id || '',
                    r.cantidadReclamos ?? '',
                    r.nombre || '',
                    r.color || ''
                ].join(':'))
                .join('|');
        },

        _fingerprintReclamosOperario(reclamos) {
            return (reclamos || [])
                .map((r) => {
                    const sr = r.sesion_reparacion || {};
                    return [
                        r.id,
                        r.municipalidad_estado || '',
                        r.orden ?? r.orden_ruta ?? '',
                        sr.activo ? 1 : 0,
                        sr.acumulado_ms || 0,
                        sr.inicio_segmento_at || ''
                    ].join(':');
                })
                .join('|');
        },

        /** Polling continuo en panel y en detalle (detecta baja/desasignación de la hoja) */
        debePollingContinuoOperario() {
            return this.esOperario
                && (this.vistaOperarioActual === 'panel' || this.vistaOperarioActual === 'detalle');
        },

        iniciarSyncOperarioLiviano() {
            if (!this.esOperario) {
                return;
            }
            this.iniciarPollingOperarioLiviano();
            this.configurarVisibilitySyncOperario();
        },

        detenerSyncOperarioLiviano() {
            this.detenerPollingOperarioLiviano();
            this.quitarVisibilitySyncOperario();
        },

        iniciarPollingOperarioLiviano() {
            if (!this.esOperario || this._pollOperarioLiviano) {
                return;
            }
            this._pollOperarioLiviano = setInterval(() => {
                if (document.hidden || !this.debePollingContinuoOperario()) {
                    return;
                }
                void this.sincronizarVistaOperarioLiviana();
            }, this.intervaloPollOperarioLiviano);
        },

        detenerPollingOperarioLiviano() {
            if (this._pollOperarioLiviano) {
                clearInterval(this._pollOperarioLiviano);
                this._pollOperarioLiviano = null;
            }
        },

        configurarVisibilitySyncOperario() {
            if (!this.esOperario || this._onVisibilityOperarioLiviano) {
                return;
            }
            this._onVisibilityOperarioLiviano = () => {
                if (document.hidden || !this.esOperario) {
                    return;
                }
                void this.sincronizarVistaOperarioLiviana();
            };
            document.addEventListener('visibilitychange', this._onVisibilityOperarioLiviano);
        },

        quitarVisibilitySyncOperario() {
            if (this._onVisibilityOperarioLiviano) {
                document.removeEventListener('visibilitychange', this._onVisibilityOperarioLiviano);
                this._onVisibilityOperarioLiviano = null;
            }
        },

        async sincronizarVistaOperarioLiviana() {
            if (!this.esOperario || this._sincronizandoOperarioLiviano) {
                return;
            }
            this._sincronizandoOperarioLiviano = true;
            try {
                const response = await axios.get(BASE_URL + 'api/rutas/operario/mis-rutas');
                const rutas = response.data || [];
                const fpRutas = this._fingerprintRutasOperario(rutas);
                const rutasCambiaron = fpRutas !== this._ultimoFingerprintRutasOperario;

                this._omitirReinicioPreviewPorSync = true;
                this.rutas = rutas;
                this.rutasPanel = rutas;
                await this.$nextTick();
                this._omitirReinicioPreviewPorSync = false;
                this._ultimoFingerprintRutasOperario = fpRutas;

                if (this.vistaOperarioActual === 'detalle' && this.rutaSeleccionadaId) {
                    const ruta = rutas.find((r) => String(r.id) === String(this.rutaSeleccionadaId));
                    if (!ruta) {
                        this.volverAPanelRutas();
                        this.mostrarMensaje(
                            'Esta hoja de ruta ya no está disponible (fue eliminada o desasignada).',
                            'warning'
                        );
                        return;
                    }
                    // Con modal abierto no recargar reclamos/mapa: evita el parpadeo de bitácora/materiales
                    if (this.modalOperarioAbiertoQueEvitaSync()) {
                        return;
                    }
                    await this.obtenerReclamosPorRutaSeleccionada({ silencioso: true });
                    return;
                }

                if (this.vistaOperarioActual === 'panel' && rutasCambiaron) {
                    this.reclamosCachePorRutaId = {};
                    await this.$nextTick();
                    await this.inicializarMapasPreviewOperario();
                }
            } catch (error) {
                console.warn('Sincronización liviana operario:', error);
            } finally {
                this._omitirReinicioPreviewPorSync = false;
                this._sincronizandoOperarioLiviano = false;
            }
        },

        /** Modales donde un sync de fondo hace parpadear el contenido */
        modalOperarioAbiertoQueEvitaSync() {
            const ids = [
                'modalObservacionesEjecucionReclamo',
                'modalAcciones',
                'modalDetalleMaterial',
                'modalAñadirReclamos',
                'modalPromptMateriales',
                'modalConfirmarEliminarMaterial',
                'modalDetalles'
            ];
            return ids.some((id) => document.getElementById(id)?.classList.contains('show'));
        },

        volverAPanelRutas() {
            this.limpiarSesionesReparacionReclamos();
            this.rutaEjecucionActivaId = null;
            this.observacionesPorReclamoOperario = {};
            this.materialesCountPorReclamoOperario = {};
            this.indiceReclamoListaParadaOperario = {};
            this.vistaOperarioActual = 'panel';
            this.rutaSeleccionadaId = null;
            this.reclamos = [];
            this.limpiarMapaDetalleOperario();
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

        claveDomicilioReclamo(reclamo) {
            const domicilio = (reclamo.municipalidad_domicilio || '').trim().toLowerCase();
            const numero = (reclamo.municipalidad_numeroDomicilio || '').trim().toLowerCase();
            if (domicilio) {
                return `dom:${domicilio}|${numero}`;
            }
            return `id:${reclamo.id}`;
        },

        agruparParadasPorDomicilio(reclamos) {
            const mapa = new Map();
            for (const reclamo of reclamos || []) {
                const clave = this.claveDomicilioReclamo(reclamo);
                if (!mapa.has(clave)) {
                    mapa.set(clave, { clave, reclamos: [] });
                }
                mapa.get(clave).reclamos.push(reclamo);
            }
            return Array.from(mapa.values());
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

        indiceReclamoEnParadaOperario(parada) {
            const idx = this.indiceReclamoListaParadaOperario[parada.clave];
            if (idx === undefined || idx >= parada.reclamos.length) {
                return 0;
            }
            return idx;
        },

        reclamoActivoEnParadaOperario(parada) {
            return parada.reclamos[this.indiceReclamoEnParadaOperario(parada)] || parada.reclamos[0];
        },

        navegarReclamoEnParadaOperario(parada, delta) {
            if (parada.reclamos.length <= 1) return;

            const total = parada.reclamos.length;
            let idx = this.indiceReclamoEnParadaOperario(parada);
            idx = (idx + delta + total) % total;
            this.indiceReclamoListaParadaOperario = {
                ...this.indiceReclamoListaParadaOperario,
                [parada.clave]: idx
            };
        },

        indiceReclamoEnParadaAñadir(parada) {
            const idx = this.indiceReclamoParadaAñadir[parada.clave];
            if (idx === undefined || idx >= parada.reclamos.length) {
                return 0;
            }
            return idx;
        },

        reclamoActivoEnParadaAñadir(parada) {
            return parada.reclamos[this.indiceReclamoEnParadaAñadir(parada)] || parada.reclamos[0];
        },

        navegarReclamoEnParadaAñadir(parada, delta) {
            if (!parada || parada.reclamos.length <= 1) return;
            const total = parada.reclamos.length;
            let idx = this.indiceReclamoEnParadaAñadir(parada);
            idx = (idx + delta + total) % total;
            this.indiceReclamoParadaAñadir = {
                ...this.indiceReclamoParadaAñadir,
                [parada.clave]: idx,
            };
        },

        async iniciarReparacionParada(parada) {
            if (!parada?.reclamos?.length) return;
            const elegibles = parada.reclamos.filter((r) => this.puedeMostrarIniciarReparacionReclamo(r));
            if (!elegibles.length) return;
            for (const reclamo of elegibles) {
                await this.iniciarReparacionReclamo(reclamo);
            }
        },

        paradaReclamosConObraActiva(parada) {
            return (parada?.reclamos || []).filter((r) => {
                const s = this.sesionReparacionReclamo(r);
                return s && s.activo;
            });
        },

        paradaReclamosContinuables(parada) {
            return (parada?.reclamos || []).filter((r) => {
                const s = this.sesionReparacionReclamo(r);
                if (!s || s.activo) return false;
                return this.puedeMostrarContinuarReparacionReclamo(r);
            });
        },

        paradaTieneSesionReparacion(parada) {
            return (parada?.reclamos || []).some((r) => this.sesionReparacionReclamo(r));
        },

        paradaTieneObraActiva(parada) {
            return this.paradaReclamosConObraActiva(parada).length > 0;
        },

        puedeMostrarContinuarParada(parada) {
            return this.paradaReclamosContinuables(parada).length > 0;
        },

        pedirConfirmarAccionParada(parada, tipo) {
            if (!parada?.clave || !tipo) return;
            const actual = this.confirmarAccionParada;
            if (actual && actual.clave === parada.clave && actual.tipo === tipo) {
                this.confirmarAccionParada = null;
                return;
            }
            this.confirmarAccionParada = { clave: parada.clave, tipo };
        },

        cancelarConfirmarAccionParada() {
            this.confirmarAccionParada = null;
        },

        estaConfirmandoAccionParada(parada, tipo) {
            const actual = this.confirmarAccionParada;
            return !!(actual && parada?.clave && actual.clave === parada.clave && actual.tipo === tipo);
        },

        textoConfirmarAccionParada(parada, tipo) {
            const varios = (parada?.reclamos?.length || 0) > 1;
            if (tipo === 'iniciar') {
                return varios ? '¿Iniciar parada?' : '¿Iniciar?';
            }
            if (tipo === 'continuar') {
                return varios ? '¿Continuar parada?' : '¿Continuar?';
            }
            if (tipo === 'pendiente') {
                return varios ? '¿Pausar parada?' : '¿Pausar?';
            }
            return varios ? '¿Completar parada?' : '¿Completar?';
        },

        async confirmarAccionParadaElegida(parada) {
            const tipo = this.confirmarAccionParada?.tipo;
            this.confirmarAccionParada = null;
            if (!parada || !tipo) return;
            if (tipo === 'iniciar') {
                await this.iniciarReparacionParada(parada);
                return;
            }
            if (tipo === 'continuar') {
                await this.continuarReparacionParada(parada);
                return;
            }
            await this.ejecutarCierreParadaObra(parada, tipo);
        },

        onClickFueraConfirmarAccionParada(e) {
            if (!this.confirmarAccionParada) return;
            if (e.target.closest && e.target.closest('.reclamo-confirm-accion')) return;
            this.cancelarConfirmarAccionParada();
        },

        async ejecutarCierreParadaObra(parada, tipo) {
            if (!parada?.reclamos?.length) return;
            const targets = this.paradaReclamosConObraActiva(parada);
            if (!targets.length) {
                this.mostrarMensaje('No hay trabajo en curso en esta parada.', 'warning');
                return;
            }

            if (tipo === 'completado') {
                const decision = await this.ofrecerRegistroMaterialesAntesDeCerrar(targets);
                if (decision === 'cancelar') {
                    return;
                }
                if (decision === 'registrar') {
                    const sinMateriales = await this.filtrarReclamosSinMateriales(targets);
                    const reclamoDestino = sinMateriales[0] || targets[0];
                    this.abrirModalMaterialesReclamo(reclamoDestino);
                    return;
                }
            }

            let ok = 0;
            for (const reclamo of targets) {
                const resultado = await this.ejecutarCierreReclamoObra(reclamo, tipo, {
                    silencioso: true,
                    omitirPromptMateriales: true
                });
                if (resultado) ok++;
            }
            await this.obtenerReclamosPorRutaSeleccionada();
            if (this.esOperario && this.modoVistaRuta === 'mapa') {
                this.$nextTick(() => this.redibujarMapaDetalleOperario());
            }
            const n = targets.length;
            if (ok === 0) {
                this.mostrarMensaje('No se pudo cerrar el trabajo en la parada.', 'error');
                return;
            }
            const etiqueta = n > 1 ? `${ok} reclamos de la parada` : 'Reclamo';
            this.mostrarMensaje(
                tipo === 'completado'
                    ? `${etiqueta} completado${n > 1 ? 's' : ''}. Tiempo de reparación registrado automáticamente.`
                    : `${etiqueta} en estado Pendiente para continuar otro día.`,
                'success'
            );
        },

        async continuarReparacionParada(parada) {
            if (!parada?.reclamos?.length) return;
            const targets = this.paradaReclamosContinuables(parada);
            if (!targets.length) return;
            for (const reclamo of targets) {
                await this.continuarReparacionReclamo(reclamo, { silencioso: true });
            }
            if (this.esOperario && this.modoVistaRuta === 'mapa') {
                this.$nextTick(() => this.redibujarMapaDetalleOperario());
            }
        },

        estilosMapaPreviewCompacto() {
            return [
                { featureType: 'all', elementType: 'labels', stylers: [{ visibility: 'off' }] },
                { featureType: 'poi', stylers: [{ visibility: 'off' }] },
                { featureType: 'transit', stylers: [{ visibility: 'off' }] }
            ];
        },

        async obtenerCuadrillas() {
            try {
                const response = await axios.get(BASE_URL + 'api/cuadrillas');
                this.cuadrillasDisponibles = response.data || [];
            } catch (error) {
                console.error('Error al obtener cuadrillas:', error);
            }
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
                    styles: this.estilosMapaPreviewCompacto()
                });

                const markers = [];
                const bounds = new google.maps.LatLngBounds();
                const paradasRuta = this.agruparParadasRutaVistaPrevia(reclamos);

                for (const parada of paradasRuta) {
                    const reclamoRef = parada.reclamos[0];
                    const coords = this.coordenadasReclamoPreview(reclamoRef);
                    if (!coords) continue;

                    const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                    const colorPrioridad = this.getColorPrioridad(reclamoRef.prioridad || 'Baja');
                    const cantidadParada = parada.reclamos.length;
                    const esGrupo = cantidadParada > 1;
                    const motivoBadge = esGrupo ? null : reclamoRef.municipalidad_motivo;
                    const badgeCantidad = esGrupo ? cantidadParada : null;

                    const marker = new google.maps.Marker({
                        position: coords,
                        map,
                        title: esGrupo
                            ? `Parada ${parada.paradaNumero}: ${cantidadParada} reclamos en el mismo domicilio`
                            : `Posición ${parada.paradaNumero}: Reclamo #${reclamoRef.municipalidad_id}`,
                        icon: this.crearIconoNumerado(
                            parada.paradaNumero,
                            colorEstado,
                            colorPrioridad,
                            26,
                            motivoBadge,
                            badgeCantidad
                        ),
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
            if (!this.esOperario || !this.rutaSeleccionadaId) {
                this.rutaEjecucionActivaId = null;
                return;
            }
            if (!this.rutaSeleccionadaEnEjecucion) {
                this.rutaEjecucionActivaId = null;
                return;
            }
            try {
                const r = await axios.get(BASE_URL + 'api/rutas/' + this.rutaSeleccionadaId + '/ejecucion-activa');
                const nuevoId = r.data?.ruta_ejecucion_id ?? null;
                // No poner null antes del fetch: hace parpadear el composer de bitácora
                if (nuevoId !== this.rutaEjecucionActivaId) {
                    this.rutaEjecucionActivaId = nuevoId;
                }
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
            // Si el payload no trae sesiones, conservar las actuales (evita parpadeo del composer)
            if (!Object.prototype.hasOwnProperty.call(primera, 'sesion_reparacion')) {
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
            const fpNuevo = Object.keys(m).sort().map((id) => {
                const s = m[id];
                return `${id}:${s.activo ? 1 : 0}:${s.inicioSegmentoMs}:${s.acumuladoMs}`;
            }).join('|');
            const fpActual = Object.keys(this.reparacionPorReclamoId || {}).sort().map((id) => {
                const s = this.reparacionPorReclamoId[id];
                return `${id}:${s.activo ? 1 : 0}:${s.inicioSegmentoMs}:${s.acumuladoMs}`;
            }).join('|');
            if (fpNuevo !== fpActual) {
                this.reparacionPorReclamoId = m;
            }
        },

        sesionReparacionReclamo(reclamo) {
            if (!reclamo || reclamo.id == null) return null;
            return this.reparacionPorReclamoId[reclamo.id] || null;
        },

        /** Reclamo con el cronómetro de obra corriendo: se destaca en la lista. */
        reclamoEnObraActiva(reclamo) {
            const s = this.sesionReparacionReclamo(reclamo);
            if (s) {
                return !!s.activo;
            }
            return !!reclamo?.sesion_reparacion?.activo;
        },

        tiempoMsSesionReparacionReclamo(reclamo) {
            const s = this.sesionReparacionReclamo(reclamo);
            if (s) {
            let ms = s.acumuladoMs || 0;
            if (s.activo) {
                ms += this.ahoraCronometro - s.inicioSegmentoMs;
            }
            return Math.max(0, ms);
            }
            const sr = reclamo?.sesion_reparacion;
            return Math.max(0, Number(sr?.acumulado_ms) || 0);
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
                municipalidad_fechaModificacion: null
            };
            const response = await axios.put(BASE_URL + 'api/reclamos/' + reclamo.id, datos);
            const actualizado = response.data || {};
            const idx = this.reclamos.findIndex(r => r.id === reclamo.id);
            if (idx !== -1) {
                this.reclamos[idx].municipalidad_estado = actualizado.municipalidad_estado || 'En ejecución';
                this.reclamos[idx].municipalidad_fechaModificacion = actualizado.municipalidad_fechaModificacion
                    || this.reclamos[idx].municipalidad_fechaModificacion;
                if (Object.prototype.hasOwnProperty.call(actualizado, 'prioridad')) {
                    this.reclamos[idx].prioridad = actualizado.prioridad;
                }
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
            if (this.esOperario && this.modoVistaRuta === 'mapa') {
                this.$nextTick(() => this.redibujarMapaDetalleOperario());
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

        async ejecutarCierreReclamoObra(reclamo, tipo, opciones = {}) {
            const { silencioso = false, omitirPromptMateriales = false } = opciones;
            const s = this.sesionReparacionReclamo(reclamo);
            if (!s || !s.activo) {
                if (!silencioso) {
                this.mostrarMensaje('No hay trabajo en curso en este reclamo.', 'warning');
                }
                return false;
            }

            if (tipo === 'completado' && !omitirPromptMateriales) {
                const decision = await this.ofrecerRegistroMaterialesAntesDeCerrar([reclamo]);
                if (decision === 'cancelar') {
                    return false;
                }
                if (decision === 'registrar') {
                    this.abrirModalMaterialesReclamo(reclamo);
                    return false;
                }
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
                if (!silencioso) {
                this.mostrarMensaje(mensaje, 'error');
                }
                return false;
            }

            this._aplicarCierreSegmentoReclamoLocal(reclamo);

            const msFinal = this.tiempoMsSesionReparacionReclamo(reclamo);
            const idxLocal = this.reclamos.findIndex((r) => r.id === reclamo.id);
            if (idxLocal !== -1) {
                this.reclamos[idxLocal] = {
                    ...this.reclamos[idxLocal],
                    sesion_reparacion: {
                        activo: false,
                        acumulado_ms: msFinal,
                        inicio_segmento_at: null
                    }
                };
            }

            const datos = {
                ...reclamo,
                municipalidad_fechaModificacion: null
            };
            if (tipo === 'completado') {
                datos.municipalidad_estado = 'Completado';
            } else {
                datos.municipalidad_estado = 'Pendiente';
            }

            try {
                await axios.put(BASE_URL + 'api/reclamos/' + reclamo.id, datos);
            } catch (error) {
                console.error('Error al actualizar reclamo:', error);
                if (!silencioso) {
                this.mostrarMensaje(error?.response?.data?.message || 'No se pudo actualizar el reclamo.', 'error');
                await this.obtenerReclamosPorRutaSeleccionada();
                }
                return false;
            }

            try {
                if (tipo === 'completado') {
                await axios.post(BASE_URL + 'api/reclamos/' + reclamo.id + '/tiempo-reparacion', {
                    tiempo_reparacion_minutos: minutos
                });
                }
            } catch (error) {
                console.warn('Tiempo de reparación automático:', error);
                if (!silencioso && tipo === 'completado') {
                this.mostrarMensaje('Reclamo actualizado, pero no se pudo guardar el tiempo de reparación automático.', 'warning');
                }
            }

            if (!silencioso) {
            await this.obtenerReclamosPorRutaSeleccionada();
            this.mostrarMensaje(
                tipo === 'completado'
                    ? 'Reclamo completado. Tiempo de reparación registrado automáticamente.'
                        : 'Reclamo en estado Pendiente para continuar otro día.',
                'success'
            );
            }
            return true;
        },

        async reclamoTieneMaterialesRegistrados(reclamo) {
            if (!reclamo?.id) return false;
            // Igual que el historial visible: si el reclamo ya tuvo materiales (aunque sea en rutas viejas), no insistir.
            if (Object.prototype.hasOwnProperty.call(this.materialesCountPorReclamoOperario, reclamo.id)) {
                return (Number(this.materialesCountPorReclamoOperario[reclamo.id]) || 0) > 0;
            }
            try {
                const response = await axios.get(BASE_URL + 'api/reclamos/' + reclamo.id + '/materiales');
                const cantidad = Array.isArray(response.data) ? response.data.length : 0;
                this.actualizarCountMaterialesReclamoOperario(reclamo.id, cantidad);
                return cantidad > 0;
            } catch (error) {
                console.warn('No se pudo consultar materiales del reclamo:', error);
                // Si falla la consulta, no bloqueamos el cierre.
                return true;
            }
        },

        cantidadMaterialesReclamoOperario(reclamo) {
            if (!reclamo?.id) return 0;
            return Number(this.materialesCountPorReclamoOperario[reclamo.id]) || 0;
        },

        actualizarCountMaterialesReclamoOperario(reclamoId, cantidad) {
            if (reclamoId == null) return;
            this.materialesCountPorReclamoOperario = {
                ...this.materialesCountPorReclamoOperario,
                [reclamoId]: Math.max(0, Number(cantidad) || 0)
            };
            this.$nextTick(() => this.refrescarBadgesMaterialesInfoWindowMapaDetalleOperario());
        },

        async cargarMaterialesCountOperario(reclamos) {
            if (!reclamos?.length) {
                this.materialesCountPorReclamoOperario = {};
                return;
            }
            // Igual que bitácora: contador con el historial completo del reclamo.
            const counts = {};
            await Promise.all(reclamos.map(async (reclamo) => {
                if (!reclamo?.id) return;
                try {
                    const r = await axios.get(BASE_URL + 'api/reclamos/' + reclamo.id + '/materiales');
                    counts[reclamo.id] = Array.isArray(r.data) ? r.data.length : 0;
                } catch (error) {
                    console.warn('No se pudieron cargar materiales del reclamo', reclamo.id, error);
                    counts[reclamo.id] = 0;
                }
            }));
            this.materialesCountPorReclamoOperario = counts;
        },

        htmlBadgeMaterialesConId(reclamoId, cantidad) {
            const texto = this.textoObservacionesEjecucionBadge(cantidad) || '0';
            const oculto = cantidad > 0 ? '' : ' btn-obs-ejecucion-count--oculto';
            return `<span class="btn-obs-ejecucion-count${oculto}" data-map-iw-mat-count-id="${reclamoId}" aria-hidden="true">${texto}</span>`;
        },

        refrescarBadgesMaterialesInfoWindowMapaDetalleOperario() {
            document.querySelectorAll('[data-map-iw-mat-count-id]').forEach((el) => {
                const rid = parseInt(el.getAttribute('data-map-iw-mat-count-id'), 10);
                if (Number.isNaN(rid)) {
                    return;
                }
                const r = (this.reclamos || []).find((x) => Number(x.id) === rid);
                const count = r ? this.cantidadMaterialesReclamoOperario(r) : 0;
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

        async filtrarReclamosSinMateriales(reclamos) {
            const lista = Array.isArray(reclamos) ? reclamos : [];
            const resultados = await Promise.all(
                lista.map(async (reclamo) => ({
                    reclamo,
                    tiene: await this.reclamoTieneMaterialesRegistrados(reclamo)
                }))
            );
            return resultados.filter((item) => !item.tiene).map((item) => item.reclamo);
        },

        /**
         * Recordatorio antes de completar: si no hay materiales, ofrece registrarlos.
         * @returns {'registrar'|'omitir'|'cancelar'}
         */
        async ofrecerRegistroMaterialesAntesDeCerrar(reclamos) {
            const sinMateriales = await this.filtrarReclamosSinMateriales(reclamos);
            if (!sinMateriales.length) {
                return 'omitir';
            }

            const n = sinMateriales.length;
            this.promptMaterialesDetalle = n === 1
                ? `Todavía no hay materiales registrados en el reclamo #${sinMateriales[0].municipalidad_id || sinMateriales[0].id}.`
                : `Hay ${n} reclamos sin materiales registrados.`;

            return new Promise((resolve) => {
                this._resolverPromptMateriales = resolve;
                this.$nextTick(() => {
                    const el = document.getElementById('modalPromptMateriales');
                    if (!el) {
                        resolve('omitir');
                        return;
                    }
                    const modal = bootstrap.Modal.getOrCreateInstance(el, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    modal.show();
                });
            });
        },

        resolverPromptMateriales(decision) {
            const resolve = this._resolverPromptMateriales;
            this._resolverPromptMateriales = null;
            const el = document.getElementById('modalPromptMateriales');
            if (el) {
                const modal = bootstrap.Modal.getInstance(el);
                if (modal) modal.hide();
            }
            if (typeof resolve === 'function') {
                resolve(decision);
            }
        },

        onPromptMaterialesOculto() {
            if (typeof this._resolverPromptMateriales === 'function') {
                const resolve = this._resolverPromptMateriales;
                this._resolverPromptMateriales = null;
                resolve('cancelar');
            }
        },

        async continuarReparacionReclamo(reclamo, opciones = {}) {
            const { silencioso = false } = opciones;
            if (!reclamo || reclamo.id == null) return false;
            const s = this.sesionReparacionReclamo(reclamo);
            if (!s || s.activo) return false;
            try {
                await this.sincronizarEstadoReclamoEnEjecucion(reclamo);
                await this.registrarEventoEjecucionReclamo('ejecucion_reclamo_inicio', reclamo);
            } catch (error) {
                console.error('Error al registrar continuación de reclamo en ejecución:', error);
                const mensaje = error?.response?.data?.message
                    || (error?.response?.data?.messages && Object.values(error.response.data.messages).flat().join(' '))
                    || 'No se pudo registrar la continuación de trabajo en el reclamo.';
                if (!silencioso) {
                this.mostrarMensaje(mensaje, 'error');
                }
                return false;
            }
            this.reparacionPorReclamoId = {
                ...this.reparacionPorReclamoId,
                [reclamo.id]: {
                    activo: true,
                    inicioSegmentoMs: Date.now(),
                    acumuladoMs: s.acumuladoMs
                }
            };
            if (!silencioso && this.esOperario && this.modoVistaRuta === 'mapa') {
                this.$nextTick(() => this.redibujarMapaDetalleOperario());
            }
            return true;
        },

        onModalAccionesOculto() {
            this.modalAccionesSoloMateriales = false;
            this.modalMaterialesSoloLectura = false;
        },

        estadoReclamoOperario(reclamo) {
            return (reclamo?.municipalidad_estado || '').trim();
        },

        reclamoEstaCompletadoOperario(reclamo) {
            return this.estadoReclamoOperario(reclamo) === 'Completado';
        },

        reclamoEstaPendienteOperario(reclamo) {
            return this.estadoReclamoOperario(reclamo) === 'Pendiente';
        },

        puedeVerRegistrosObraReclamo(reclamo) {
            if (!this.puedeOperarRutaSeleccionada) {
                return false;
            }
            if (!reclamo?.id) {
                return false;
            }
            const est = this.estadoReclamoOperario(reclamo);

            if (!this.rutaSeleccionadaEnEjecucion) {
                if (est === 'Pendiente') {
                    return true;
                }
                if (est === 'Completado') {
                    return this.mostrarCronometroReparacionReclamo(reclamo)
                        || this.cantidadObservacionesEjecucionReclamoOperario(reclamo) > 0;
                }
                return false;
            }

            if (est === 'Completado' || est === 'Pendiente') {
                return true;
            }
            if (this.sesionReparacionReclamo(reclamo)) {
                return true;
            }
            return this.cantidadObservacionesEjecucionReclamoOperario(reclamo) > 0;
        },

        registrosObraReclamoSoloLectura(reclamo) {
            if (!reclamo) {
                return true;
            }
            if (this.reclamoEstaCompletadoOperario(reclamo)) {
                return true;
            }
            if (this.reclamoEstaPendienteOperario(reclamo) && !this.sesionReparacionReclamo(reclamo)?.activo) {
                return true;
            }
            return !this.puedeRegistrarBitacoraEjecucion(reclamo);
        },

        abrirModalMaterialesReclamo(reclamo) {
            if (!this.puedeVerRegistrosObraReclamo(reclamo)) {
                this.mostrarMensaje('No hay registros de materiales para este reclamo.', 'warning');
                return;
            }
            const soloLectura = this.registrosObraReclamoSoloLectura(reclamo)
                || !this.puedeEditarTareasRutaSeleccionada;
            if (!soloLectura) {
            if (!this.sesionReparacionReclamo(reclamo)) {
                this.mostrarMensaje('Inicie el reclamo en obra para registrar materiales.', 'warning');
                return;
                }
            }
            this.reclamoSeleccionado = { ...reclamo };
            this.modalAccionesSoloMateriales = true;
            this.modalMaterialesSoloLectura = soloLectura;
            this.materialSeleccionado = {
                tipo_id: '',
                material_id: '',
                cantidad: null,
                observacion: ''
            };
            this.filtroBusquedaMaterial = '';
            this.materialesFiltrados = [];
            this.historialMateriales = [];
            this.mostrarHistorialMateriales = true;

            const modal = new bootstrap.Modal(document.getElementById('modalAcciones'));
            modal.show();
            this.cargarMateriales();
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

        cantidadObservacionesEjecucionReclamoOperario(reclamo) {
            if (!reclamo?.id) {
                return 0;
            }
            return (this.observacionesPorReclamoOperario[reclamo.id] || [])
                .filter((o) => !this.esEntradaCambioEstadoBitacora(o))
                .length;
        },

        puedeRegistrarBitacoraEjecucion(reclamo) {
            if (!this.puedeOperarRutaSeleccionada || !this.rutaSeleccionadaEnEjecucion) {
                return false;
            }
            if (this.rutaEjecucionActivaId == null) {
                return false;
            }
            const s = this.sesionReparacionReclamo(reclamo);
            return !!(s && s.activo);
        },

        puedeAbrirBitacoraEjecucion(reclamo) {
            return this.puedeVerRegistrosObraReclamo(reclamo);
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

        paramsObservacionesEjecucionOperario() {
            if (this.rutaEjecucionActivaId != null) {
                return { ruta_ejecucion_id: this.rutaEjecucionActivaId };
            }
            if (this.rutaSeleccionadaId) {
                return { ruta_id: this.rutaSeleccionadaId };
            }
            return null;
        },

        async cargarObservacionesEjecucionOperario(reclamos) {
            const params = this.paramsObservacionesEjecucionOperario();
            if (!reclamos?.length || !params) {
                this.observacionesPorReclamoOperario = {};
                return;
            }
            const observacionesMap = {};
            await Promise.all(reclamos.map(async (reclamo) => {
                if (!reclamo?.id) {
                    return;
                }
                try {
                    const r = await axios.get(
                        `${BASE_URL}api/reclamos/${reclamo.id}/ejecucion-observaciones`,
                        { params }
                    );
                    observacionesMap[reclamo.id] = Array.isArray(r.data) ? r.data : [];
                } catch (error) {
                    console.warn('No se pudieron cargar observaciones del reclamo', reclamo.id, error);
                    observacionesMap[reclamo.id] = [];
                }
            }));
            this.observacionesPorReclamoOperario = observacionesMap;
        },

        abrirModalObservacionesEjecucionReclamo(reclamo) {
            if (!this.puedeOperarRutaSeleccionada) {
                this.mostrarMensaje('No tenés permisos sobre esta hoja de ruta.', 'warning');
                return;
            }
            if (!this.paramsObservacionesEjecucionOperario()) {
                this.mostrarMensaje('No se pudo determinar la hoja de ruta para consultar el registro.', 'warning');
                return;
            }
            if (!this.puedeAbrirBitacoraEjecucion(reclamo)) {
                this.mostrarMensaje('No hay registro en obra para este reclamo.', 'warning');
                return;
            }
            this.reclamoSeleccionado = { ...reclamo };
            this.observacionEjecucionTexto = '';
            this.archivoFotoBitacora = null;
            this.previewFotoBitacora = null;
            const cached = this.observacionesPorReclamoOperario[reclamo.id];
            this.historialObservacionesEjecucion = Array.isArray(cached) ? [...cached] : [];
            const elModal = document.getElementById('modalObservacionesEjecucionReclamo');
            const modal = bootstrap.Modal.getOrCreateInstance(elModal);
            elModal.addEventListener('shown.bs.modal', () => {
                this.scrollBitacoraObraAlFinal('bitacoraObraFeedOperario');
            }, { once: true });
            modal.show();
            void this.cargarHistorialObservacionesEjecucion({
                silencioso: this.historialObservacionesEjecucion.length > 0
            }).then(() => this.scrollBitacoraObraAlFinal('bitacoraObraFeedOperario'));
        },

        scrollBitacoraObraAlFinal(feedId = 'bitacoraObraFeedOperario') {
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

        esEntradaCambioEstadoBitacora(entrada) {
            return entrada?.bitacora_tipo === 'cambio_estado';
        },

        esEntradaFotoBitacora(entrada) {
            if (this.esEntradaCambioEstadoBitacora(entrada)) {
                return false;
            }
            return !!(entrada && (entrada.tipo === 'foto' || entrada.archivo));
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

        onSeleccionFotoBitacoraEjecucion(event) {
            const input = event?.target;
            const archivo = input?.files?.[0];
            if (!archivo) {
                return;
            }
            // Procesar async: iPhone suele mandar HEIC / fotos >5MB / type vacío
            void this.procesarFotoBitacoraSeleccionada(archivo, input);
        },

        /**
         * Normaliza la foto para iOS/Safari: convierte a JPEG comprimido ≤ ~5MB.
         */
        async procesarFotoBitacoraSeleccionada(archivo, input) {
            try {
                const nombre = String(archivo.name || '').toLowerCase();
                const tipo = String(archivo.type || '').toLowerCase();
                const esImagenPorTipo = tipo.startsWith('image/');
                const esImagenPorExt = /\.(jpe?g|png|webp|heic|heif|gif|bmp|tiff?)$/i.test(nombre);
                if (!esImagenPorTipo && !esImagenPorExt && tipo !== '') {
                    this.mostrarMensaje('Formato no permitido. Elegí una imagen desde la galería (JPG, PNG, WEBP o HEIC).', 'warning');
                    if (input) input.value = '';
                    return;
                }

                this.guardandoFotoBitacora = true;
                const normalizado = await this.normalizarImagenBitacoraParaSubida(archivo);
                if (this.previewFotoBitacora) {
                    URL.revokeObjectURL(this.previewFotoBitacora);
                }
                this.archivoFotoBitacora = normalizado;
                this.previewFotoBitacora = URL.createObjectURL(normalizado);
            } catch (error) {
                console.error('Error al procesar foto de bitácora:', error);
                this.mostrarMensaje(
                    error?.message || 'No se pudo procesar la foto. Probá otra o elegila desde la galería.',
                    'warning'
                );
                this.limpiarPreviewFotoBitacora();
            } finally {
                this.guardandoFotoBitacora = false;
                // Permite volver a elegir la misma foto en iOS
                if (input) input.value = '';
            }
        },

        /**
         * Redimensiona/comprime a JPEG. Safari puede dibujar HEIC en canvas.
         */
        async normalizarImagenBitacoraParaSubida(archivo) {
            const maxLado = 1600;
            const maxBytes = 5 * 1024 * 1024;
            const objectUrl = URL.createObjectURL(archivo);

            try {
                const img = await new Promise((resolve, reject) => {
                    const el = new Image();
                    el.onload = () => resolve(el);
                    el.onerror = () => reject(new Error(
                        'No se pudo leer la imagen. Si es HEIC, probá otra foto de la galería o convertí a JPG.'
                    ));
                    el.src = objectUrl;
                });

                let { width, height } = img;
                if (!width || !height) {
                    throw new Error('La imagen no tiene dimensiones válidas.');
                }
                const escala = Math.min(1, maxLado / Math.max(width, height));
                width = Math.max(1, Math.round(width * escala));
                height = Math.max(1, Math.round(height * escala));

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    throw new Error('No se pudo preparar la imagen.');
                }
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, width, height);
                ctx.drawImage(img, 0, 0, width, height);

                let quality = 0.85;
                let blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
                while (blob && blob.size > maxBytes && quality > 0.45) {
                    quality -= 0.1;
                    blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));
                }

                if (!blob) {
                    // Fallback: si ya era JPEG pequeño, usarlo tal cual
                    if (
                        archivo.size <= maxBytes
                        && (/image\/jpe?g/i.test(archivo.type) || /\.jpe?g$/i.test(archivo.name || ''))
                    ) {
                        return archivo;
                    }
                    throw new Error('No se pudo comprimir la foto.');
                }
                if (blob.size > maxBytes) {
                    throw new Error('La foto sigue siendo demasiado grande. Probá otra con menos resolución.');
                }

                const baseName = String(archivo.name || 'foto')
                    .replace(/\.[^.]+$/, '')
                    .replace(/[^\w\-]+/g, '_')
                    .slice(0, 40) || 'foto';
                return new File([blob], `${baseName}.jpg`, { type: 'image/jpeg', lastModified: Date.now() });
            } finally {
                URL.revokeObjectURL(objectUrl);
            }
        },

        limpiarPreviewFotoBitacora() {
            if (this.previewFotoBitacora) {
                URL.revokeObjectURL(this.previewFotoBitacora);
            }
            this.archivoFotoBitacora = null;
            this.previewFotoBitacora = null;
            const input = document.getElementById('inputFotoBitacoraEjecucion');
            if (input) {
                input.value = '';
            }
        },

        async guardarFotoBitacoraEjecucion() {
            if (!this.archivoFotoBitacora || this.rutaEjecucionActivaId == null || !this.reclamoSeleccionado?.id) {
                return;
            }
            if (!this.puedeRegistrarBitacoraEjecucion(this.reclamoSeleccionado)) {
                this.mostrarMensaje('Solo podés subir fotos mientras el reclamo está en obra.', 'warning');
                return;
            }
            this.guardandoFotoBitacora = true;
            try {
                const formData = new FormData();
                // Nombre explícito ayuda en algunos WebKit
                formData.append(
                    'foto',
                    this.archivoFotoBitacora,
                    this.archivoFotoBitacora.name || 'foto-obra.jpg'
                );
                formData.append('ruta_ejecucion_id', String(this.rutaEjecucionActivaId));
                const caption = (this.observacionEjecucionTexto || '').trim();
                if (caption) {
                    formData.append('texto', caption);
                }
                await axios.post(
                    `${BASE_URL}api/reclamos/${this.reclamoSeleccionado.id}/ejecucion-observaciones/foto`,
                    formData
                    // No forzar Content-Type: el boundary lo pone el navegador
                );
                this.observacionEjecucionTexto = '';
                this.limpiarPreviewFotoBitacora();
                this.mostrarMensaje('Foto registrada.', 'success');
                await this.cargarHistorialObservacionesEjecucion({ silencioso: true });
                this.scrollBitacoraObraAlFinal('bitacoraObraFeedOperario');
            } catch (error) {
                console.error('Error al subir foto de ejecución:', error);
                const mensaje = error?.response?.data?.message
                    || (error?.response?.data?.messages && Object.values(error.response.data.messages).flat().join(' '))
                    || 'No se pudo subir la foto.';
                this.mostrarMensaje(mensaje, 'error');
            } finally {
                this.guardandoFotoBitacora = false;
            }
        },

        async cargarHistorialObservacionesEjecucion(opciones = {}) {
            const { silencioso = false } = opciones;
            const params = this.paramsObservacionesEjecucionOperario();
            if (!this.reclamoSeleccionado?.id || !params) {
                return;
            }
            const mostrarSpinner = !silencioso || this.historialObservacionesEjecucion.length === 0;
            if (mostrarSpinner) {
                this.cargandoObservacionesEjecucion = true;
            }
            try {
                const r = await axios.get(
                    `${BASE_URL}api/reclamos/${this.reclamoSeleccionado.id}/ejecucion-observaciones`,
                    { params }
                );
                const nuevos = Array.isArray(r.data) ? r.data : [];
                const fpNuevo = this._fingerprintBitacoraOperario(nuevos);
                const fpActual = this._fingerprintBitacoraOperario(this.historialObservacionesEjecucion);
                if (fpNuevo !== fpActual) {
                    this.historialObservacionesEjecucion = nuevos;
                }
                this.observacionesPorReclamoOperario = {
                    ...this.observacionesPorReclamoOperario,
                    [this.reclamoSeleccionado.id]: nuevos
                };
                this.refrescarBadgesObservacionesInfoWindowMapaDetalleOperario();
                this.scrollBitacoraObraAlFinal('bitacoraObraFeedOperario');
            } catch (error) {
                console.error('Error al cargar observaciones de ejecución:', error);
                if (!silencioso) {
                    const mensaje = error?.response?.data?.message
                        || (error?.response?.data?.messages && Object.values(error.response.data.messages).flat().join(' '))
                        || 'No se pudo cargar el historial de observaciones.';
                    this.mostrarMensaje(mensaje, 'error');
                    this.historialObservacionesEjecucion = [];
                }
            } finally {
                this.cargandoObservacionesEjecucion = false;
                this.scrollBitacoraObraAlFinal('bitacoraObraFeedOperario');
            }
        },

        _fingerprintBitacoraOperario(entradas) {
            return (entradas || [])
                .map((o) => [
                    o.id,
                    o.tipo || '',
                    o.archivo || '',
                    o.texto || '',
                    o.created_at || '',
                    o.bitacora_tipo || ''
                ].join(':'))
                .join('|');
        },

        async guardarObservacionEjecucion() {
            const texto = (this.observacionEjecucionTexto || '').trim();
            if (!texto || this.rutaEjecucionActivaId == null || !this.reclamoSeleccionado?.id) {
                return;
            }
            if (!this.puedeRegistrarBitacoraEjecucion(this.reclamoSeleccionado)) {
                this.mostrarMensaje('Solo podés registrar notas mientras el reclamo está en obra.', 'warning');
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
                await this.cargarHistorialObservacionesEjecucion({ silencioso: true });
                this.scrollBitacoraObraAlFinal('bitacoraObraFeedOperario');
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
            this.filtroBusquedaMaterial = '';
            this.materialesFiltrados = [];
            this.historialMateriales = [];
            this.mostrarHistorialMateriales = true;

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
            if (s) {
            let ms = s.acumuladoMs;
            if (s.activo) {
                ms += this.ahoraCronometro - s.inicioSegmentoMs;
            }
            const sec = Math.max(0, Math.floor(ms / 1000));
            return this.formatearSegundosCronometro(sec);
            }
            const sr = reclamo?.sesion_reparacion;
            const msApi = Number(sr?.acumulado_ms) || 0;
            if (msApi > 0) {
                return this.formatearSegundosCronometro(Math.floor(msApi / 1000));
            }
            return '';
        },

        mostrarCronometroReparacionReclamo(reclamo) {
            return !!this.textoCronometroReparacionReclamo(reclamo);
        },

        claseCronometroListaObraOperario(reclamo) {
            const s = this.sesionReparacionReclamo(reclamo);
            const srApi = reclamo?.sesion_reparacion;
            if (!s && !srApi) {
                return '';
            }
            const nivel = this.nivelDemoraObraReclamoOperario(reclamo);
            const pausado = s ? !s.activo : true;
            return ObraCronometroUtil.claseListaCronoObra(nivel, pausado);
        },

        cambiarModoVistaRuta(modo) {
            this.modoVistaRuta = modo;
            if (modo === 'mapa') {
                this.$nextTick(() => this.inicializarMapaDetalleOperario());
            }
        },

        async alternarProveedorMapaDetalleOperario() {
            const nuevoProveedor = this.proveedorMapaDetalleOperario === 'google' ? 'mapbox' : 'google';
            if (nuevoProveedor === 'google') {
                this.limpiarMapaDetalleOperarioMapbox();
            } else {
                this.limpiarMapaDetalleOperarioGoogle();
            }
            this.proveedorMapaDetalleOperario = nuevoProveedor;
            await this.$nextTick();
            await this.inicializarMapaDetalleOperario();
        },

        async inicializarMapaDetalleOperario() {
            if (this.proveedorMapaDetalleOperario === 'mapbox') {
                await this.inicializarMapaDetalleOperarioMapbox();
            } else {
                await this.inicializarMapaDetalleOperarioGoogle();
            }
        },

        async redibujarMapaDetalleOperario() {
            if (this.proveedorMapaDetalleOperario === 'mapbox') {
                await this.dibujarRutaDetalleOperarioMapbox();
            } else {
                await this.dibujarRutaDetalleOperarioGoogle();
            }
        },

        limpiarMapaDetalleOperario() {
            this.limpiarMapaDetalleOperarioGoogle();
            this.limpiarMapaDetalleOperarioMapbox();
        },

        limpiarMapaDetalleOperarioGoogle() {
            if (this.infoWindowAbiertoMapaDetalleOperario) {
                this.infoWindowAbiertoMapaDetalleOperario.close();
                this.infoWindowAbiertoMapaDetalleOperario = null;
            }

            (this._googleObraDetalleOperarioRefs || []).forEach((ref) => {
                ObraCronometroUtil.quitarCompanionObraGoogle(ref);
            });
            this._googleObraDetalleOperarioRefs = [];

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
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                clickableIcons: false,
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
            (this._googleObraDetalleOperarioRefs || []).forEach((ref) => {
                ObraCronometroUtil.quitarCompanionObraGoogle(ref);
            });
            this._googleObraDetalleOperarioRefs = [];
            if (this.directionsRendererDetalleGoogle) {
                this.directionsRendererDetalleGoogle.setMap(null);
                this.directionsRendererDetalleGoogle = null;
            }

            const reclamosConCoords = [];
            const paradasRuta = this.paradasOrdenRuta || [];

            for (const parada of paradasRuta) {
                const reclamoRef = parada.reclamos[0];
                if (!reclamoRef?.coordenadas?.lat || !reclamoRef?.coordenadas?.lng) continue;
                const lat = parseFloat(reclamoRef.coordenadas.lat);
                const lng = parseFloat(reclamoRef.coordenadas.lng);
                if (Number.isNaN(lat) || Number.isNaN(lng)) continue;
                reclamosConCoords.push({ parada, reclamoRef, lat, lng });
            }

            const bounds = new google.maps.LatLngBounds();
            const waypoints = [];
            let contadorGruposDetalleOperario = 0;

            reclamosConCoords.forEach(({ parada, reclamoRef, lat, lng }) => {
                const position = { lat, lng };
                const colorEstado = this.getColorEstado(reclamoRef.municipalidad_estado);
                const prioridadAlta = this.marcadorGrupoTienePrioridadAlta(parada.reclamos);
                const cantidadParada = parada.reclamos.length;
                const esGrupo = cantidadParada > 1;
                const motivoBadge = esGrupo ? null : reclamoRef.municipalidad_motivo;
                const badgeCantidad = esGrupo ? cantidadParada : null;

                const marker = new google.maps.Marker({
                    position,
                    map: this.mapaDetalleGoogle,
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
                    zIndex: 1000
                });

                const reclamosGrupo = parada.reclamos.map((r) => ({
                    ...r,
                    posicion: parada.paradaNumero
                }));
                marker._reclamo = reclamosGrupo[0];
                marker._reclamosGrupo = reclamosGrupo;
                marker._parada = parada;
                marker._indicePopup = this.indiceReclamoEnParadaOperario(parada);
                if (esGrupo) {
                    marker._grupoId = `grupo-op-detalle-${++contadorGruposDetalleOperario}`;
                }

                marker.addListener('click', () => {
                    this.abrirPopupMapaDetalleOperarioGoogle(marker);
                });

                marker._reclamoIdDetalle = reclamoRef.id;
                const reclamoObra = parada.reclamos.find((r) => this.reclamoMuestraCamionObraMapaDetalle(r));
                if (reclamoObra) {
                    const hms = this.textoCronometroReparacionReclamo(reclamoObra);
                    const nivel = this.nivelDemoraObraReclamoOperario(reclamoObra);
                    const latLng = new google.maps.LatLng(lat, lng + 0.00032);
                    const companionRef = ObraCronometroUtil.crearCompanionObraGoogleOverlay(
                        latLng,
                        this.mapaDetalleGoogle,
                        hms,
                        nivel
                    );
                    companionRef._reclamoIdObra = reclamoObra.id;
                    marker._companionObraMapa = companionRef;
                    this._googleObraDetalleOperarioRefs.push(companionRef);
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

        limpiarMapaDetalleOperarioMapbox() {
            this._mapboxObraDetalleOperarioRefs = [];
            if (this._marcadoresDetalleOperarioMapbox?.length) {
                this._marcadoresDetalleOperarioMapbox.forEach((marker) => marker.remove());
            }
            this._marcadoresDetalleOperarioMapbox = [];

            if (this.mapaDetalleOperarioMapbox) {
                if (this.mapaDetalleOperarioMapbox.getLayer('route-op-detalle')) {
                    this.mapaDetalleOperarioMapbox.removeLayer('route-op-detalle');
                }
                if (this.mapaDetalleOperarioMapbox.getSource('route-op-detalle')) {
                    this.mapaDetalleOperarioMapbox.removeSource('route-op-detalle');
                }
                this.mapaDetalleOperarioMapbox.remove();
                this.mapaDetalleOperarioMapbox = null;
            }
        },

        async inicializarMapaDetalleOperarioMapbox() {
            const contenedor = document.getElementById('mapaRutaDetalleOperarioMapbox');
            if (!contenedor) return;

            if (this.mapaDetalleOperarioMapbox) {
                this.mapaDetalleOperarioMapbox.remove();
                this.mapaDetalleOperarioMapbox = null;
            }

            mapboxgl.accessToken = this.mapboxToken;
            this.mapaDetalleOperarioMapbox = new mapboxgl.Map({
                container: 'mapaRutaDetalleOperarioMapbox',
                style: 'mapbox://styles/mapbox/streets-v12',
                center: [-62.110954, -31.426516],
                zoom: 13
            });

            await new Promise((resolve) => this.mapaDetalleOperarioMapbox.on('load', resolve));
            this.mapaDetalleOperarioMapbox.setLayoutProperty('poi-label', 'visibility', 'none');
            this.mapaDetalleOperarioMapbox.setLayoutProperty('poi-scalerank', 'visibility', 'none');

            await this.dibujarRutaDetalleOperarioMapbox();
        },

        async dibujarRutaDetalleOperarioMapbox() {
            if (!this.mapaDetalleOperarioMapbox) return;

            this._mapboxObraDetalleOperarioRefs = [];
            if (this.mapaDetalleOperarioMapbox.getLayer('route-op-detalle')) {
                this.mapaDetalleOperarioMapbox.removeLayer('route-op-detalle');
            }
            if (this.mapaDetalleOperarioMapbox.getSource('route-op-detalle')) {
                this.mapaDetalleOperarioMapbox.removeSource('route-op-detalle');
            }

            document.querySelectorAll('#mapaRutaDetalleOperarioMapbox .mapboxgl-marker').forEach((m) => m.remove());
            if (this._marcadoresDetalleOperarioMapbox?.length) {
                this._marcadoresDetalleOperarioMapbox.forEach((marker) => marker.remove());
            }
            this._marcadoresDetalleOperarioMapbox = [];

            const reclamosConCoords = [];
            const paradasRuta = this.paradasOrdenRuta || [];

            for (const parada of paradasRuta) {
                const reclamoRef = parada.reclamos[0];
                if (!reclamoRef?.coordenadas?.lat || !reclamoRef?.coordenadas?.lng) continue;
                const lat = parseFloat(reclamoRef.coordenadas.lat);
                const lng = parseFloat(reclamoRef.coordenadas.lng);
                if (Number.isNaN(lat) || Number.isNaN(lng)) continue;
                reclamosConCoords.push({ parada, reclamoRef, lat, lng });
            }

            const bounds = new mapboxgl.LngLatBounds();
            let contadorGruposDetalleOperario = 0;

            reclamosConCoords.forEach(({ parada, reclamoRef, lat, lng }) => {
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

                const marker = new mapboxgl.Marker({ element: el, anchor: 'center' })
                    .setLngLat([lng, lat])
                    .addTo(this.mapaDetalleOperarioMapbox);

                const reclamosGrupo = parada.reclamos.map((r) => ({
                    ...r,
                    posicion: parada.paradaNumero
                }));

                marker._reclamo = reclamosGrupo[0];
                marker._reclamosGrupo = reclamosGrupo;
                marker._parada = parada;
                marker._indicePopup = this.indiceReclamoEnParadaOperario(parada);
                marker._reclamoIdDetalle = reclamoRef.id;
                marker._esMapboxDetalleOperario = true;
                if (esGrupo) {
                    marker._grupoId = `grupo-op-detalle-mb-${++contadorGruposDetalleOperario}`;
                }

                el.addEventListener('click', () => {
                    this.abrirPopupMapaDetalleOperarioMapbox(marker);
                });

                this._marcadoresDetalleOperarioMapbox.push(marker);
                bounds.extend([lng, lat]);

                for (let i = 0; i < parada.reclamos.length; i++) {
                    const reclamo = parada.reclamos[i];
                    if (this.reclamoMuestraCamionObraMapaDetalle(reclamo)) {
                        const hms = this.textoCronometroReparacionReclamo(reclamo);
                        const nivel = this.nivelDemoraObraReclamoOperario(reclamo);
                        const { wrap, span } = ObraCronometroUtil.crearElementoIndicadorObraMapbox(hms, nivel);
                        const offsetLng = 0.00028 + (i * 0.00006);
                        const companion = new mapboxgl.Marker({ element: wrap, anchor: 'left' })
                            .setLngLat([lng + offsetLng, lat])
                            .addTo(this.mapaDetalleOperarioMapbox);
                        this._marcadoresDetalleOperarioMapbox.push(companion);
                        if (span) {
                            this._mapboxObraDetalleOperarioRefs.push({ reclamoId: reclamo.id, span, wrap });
                        }
                    }
                }
            });

            const reclamosTrazado = paradasRuta.map((parada) => ({
                ...parada.reclamos[0],
                posicion: parada.paradaNumero
            }));
            const colorRuta = this.rutaSeleccionada?.color || this.rutaSeleccionada?.ruta_color || '#FF6B35';
            if (reclamosTrazado.length > 1) {
                await this.trazarRutaMapboxConId(reclamosTrazado, this.mapaDetalleOperarioMapbox, colorRuta, 'route-op-detalle');
            }

            if (!bounds.isEmpty()) {
                this.mapaDetalleOperarioMapbox.fitBounds(bounds, { padding: 40, maxZoom: 16 });
            } else {
                this.mapaDetalleOperarioMapbox.setCenter([-62.110954, -31.426516]);
                this.mapaDetalleOperarioMapbox.setZoom(13);
            }
        },

        abrirPopupMapaDetalleOperarioMapbox(marker, indice = null) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length) return;

            if (indice !== null) {
                marker._indicePopup = indice;
            } else if (marker._indicePopup === undefined) {
                marker._indicePopup = 0;
            }

            if (marker._parada?.clave) {
                this.indiceReclamoListaParadaOperario = {
                    ...this.indiceReclamoListaParadaOperario,
                    [marker._parada.clave]: marker._indicePopup
                };
            }

            const reclamoLista = (this.reclamos || []).find(
                (r) => Number(r.id) === Number(reclamos[marker._indicePopup].id)
            );
            const reclamo = reclamoLista
                ? { ...reclamos[marker._indicePopup], ...reclamoLista }
                : reclamos[marker._indicePopup];

            let popup = marker.getPopup();
            if (!popup) {
                popup = new mapboxgl.Popup({ offset: 25, maxWidth: '320px' });
                marker.setPopup(popup);
            }

            popup.setHTML(this.crearContenidoPopupReclamoOperario(reclamo, {
                grupoId: marker._grupoId || null,
                indice: marker._indicePopup,
                total: reclamos.length,
                incluirTitulo: true
            }));

            if (!popup.isOpen()) {
                marker.togglePopup();
            }

            setTimeout(() => this.vincularEventosPopupMapaDetalleOperarioMapbox(marker, reclamo), 0);
        },

        vincularEventosPopupMapaDetalleOperarioMapbox(marker, reclamo) {
            const btnDetalle = document.querySelector(`.mapa-popup-detalle[data-reclamo-id="${reclamo.id}"]`);
            if (btnDetalle) {
                btnDetalle.onclick = () => this.verDetalles(reclamo);
            }

            if (marker._grupoId) {
                const btnPrev = document.querySelector(`.mapa-popup-nav-prev[data-grupo-id="${marker._grupoId}"]`);
                if (btnPrev) {
                    btnPrev.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoMapaDetalleOperario(marker, -1);
                    };
                }

                const btnNext = document.querySelector(`.mapa-popup-nav-next[data-grupo-id="${marker._grupoId}"]`);
                if (btnNext) {
                    btnNext.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoMapaDetalleOperario(marker, 1);
                    };
                }
            }

            const popupRoot = document.querySelector('[data-map-popup-operario="1"]');
            if (popupRoot) {
                popupRoot.addEventListener('click', (e) => this.onMapaDetalleInfoWindowAccion(e));
            }
        },
        
        /**
         * Obtiene las rutas asignadas a la cuadrilla del operario (solo para operarios)
         */
        async obtenerRutasOperario() {
            try {
                if (this.esOperario) {
                    const response = await axios.get(BASE_URL + 'api/rutas/operario/mis-rutas');
                    const rutas = response.data || [];
                    this.rutas = rutas;
                    this.rutasPanel = rutas;
                    console.log('Rutas de mi cuadrilla obtenidas:', this.rutas);
                } else {
                    this.rutas = [];
                }
            } catch (error) {
                console.error('Error al obtener rutas:', error);
                this.rutas = [];
                this.rutasPanel = [];
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
                    municipalidad_fechaModificacion: null
                };

                if (nuevoEstadoSeleccionado) {
                    datosActualizacion.municipalidad_estado = nuevoEstadoSeleccionado;
                } else {
                    datosActualizacion.municipalidad_estado = this.reclamoSeleccionado.municipalidad_estado;
                }

                if (observacionLimpia) {
                    datosActualizacion.observacion = observacionLimpia;
                }

                // La prioridad la resuelve el backend según las reglas vigentes.
                const response = await axios.put(BASE_URL + 'api/reclamos/' + this.reclamoSeleccionado.id, datosActualizacion);
                const reclamoActualizado = response.data || {};
                
                // Actualizar el reclamo en la lista local
                const index = this.reclamos.findIndex(r => r.id === this.reclamoSeleccionado.id);
                if (index !== -1) {
                    this.reclamos[index].municipalidad_fechaModificacion = reclamoActualizado.municipalidad_fechaModificacion
                        || this.reclamos[index].municipalidad_fechaModificacion;
                    
                    if (nuevoEstadoSeleccionado) {
                        this.reclamos[index].municipalidad_estado = reclamoActualizado.municipalidad_estado
                            || nuevoEstadoSeleccionado;
                    }
                    if (Object.prototype.hasOwnProperty.call(reclamoActualizado, 'prioridad')) {
                        this.reclamos[index].prioridad = reclamoActualizado.prioridad;
                    }
                }

                this.reclamoSeleccionado.municipalidad_fechaModificacion = reclamoActualizado.municipalidad_fechaModificacion
                    || this.reclamoSeleccionado.municipalidad_fechaModificacion;
                if (nuevoEstadoSeleccionado) {
                    this.reclamoSeleccionado.municipalidad_estado = reclamoActualizado.municipalidad_estado
                        || nuevoEstadoSeleccionado;
                }
                if (Object.prototype.hasOwnProperty.call(reclamoActualizado, 'prioridad')) {
                    this.reclamoSeleccionado.prioridad = reclamoActualizado.prioridad;
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
                <div class="alert ${alertClass} alert-dismissible fade show mensaje-notificacion" role="alert">
                    <div class="mensaje-notificacion__body">${mensaje}</div>
                    <button type="button" class="btn-close mensaje-notificacion__close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
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
         * Confirmación modal (Cancelar / Confirmar)
         */
        mostrarConfirmacion(mensaje, titulo = 'Confirmar acción') {
            return new Promise((resolve) => {
                let resuelto = false;
                const modalHtml = `
                    <div class="modal fade" id="modalConfirmacionTareas" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rutas-modal tareas-modal reclamo-confirm-modal">
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
                                    <p class="reclamo-confirm-modal__message mb-0">${mensaje}</p>
                                </div>
                                <div class="rutas-modal__footer rutas-modal__footer--end">
                                    <button type="button" class="tareas-btn tareas-btn--outline" data-bs-dismiss="modal" id="btnCancelarConfirmacionTareas">Cancelar</button>
                                    <button type="button" class="tareas-btn tareas-btn--danger" id="btnConfirmarConfirmacionTareas">
                                        <i class="bi bi-check-lg"></i> Confirmar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#modalConfirmacionTareas').remove();
                $('body').append(modalHtml);

                const modal = new bootstrap.Modal(document.getElementById('modalConfirmacionTareas'));
                modal.show();

                const cerrarConfirmacion = (resultado) => {
                    if (resuelto) return;
                    resuelto = true;
                    modal.hide();
                    setTimeout(() => {
                        $('#modalConfirmacionTareas').remove();
                    }, 300);
                    resolve(resultado);
                };

                $('#btnConfirmarConfirmacionTareas').on('click', () => cerrarConfirmacion(true));
                $('#btnCancelarConfirmacionTareas').on('click', () => cerrarConfirmacion(false));

                $('#modalConfirmacionTareas').on('hidden.bs.modal', () => {
                    $('#modalConfirmacionTareas').remove();
                    if (!resuelto) {
                        resuelto = true;
                        resolve(false);
                    }
                });
            });
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
                        icon: this.crearIconoNumerado(reclamo.posicion, colorEstado, colorPrioridad, null, reclamo.municipalidad_motivo),
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

        colorTextoSobreEstadoReclamo(estado) {
            const e = (estado || '').trim();
            if (e === 'En ejecución' || e === 'Asignado') {
                return '#000';
            }
            return '#fff';
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

        crearSvgBadgeMotivo(motivo, x, y, radio = 6, fontSize = 9) {
            if (!motivo) return '';
            const icono = this.escaparTextoSvg(this.iconoMotivoReclamo(motivo));

            return `
                <circle cx="${x}" cy="${y}" r="${radio}" fill="#FFFFFF" stroke="#ADB5BD" stroke-width="1"/>
                <text x="${x + 0.4}" y="${y + 0.5}" text-anchor="middle" dominant-baseline="middle" font-family="Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif" font-size="${fontSize}">${icono}</text>
            `;
        },

        crearSvgBadgeCantidad(cantidad, x, y, radio = 6, fontSize = 9) {
            const texto = cantidad > 99 ? '99+' : String(cantidad);
            return `
                <circle cx="${x}" cy="${y}" r="${radio}" fill="#212529" stroke="#FFFFFF" stroke-width="1"/>
                <text x="${x}" y="${y + fontSize * 0.35}" text-anchor="middle" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="${fontSize}" font-weight="700">${texto}</text>
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

        /**
         * Crea un icono numerado para los marcadores de la ruta (igual que en rutas.js)
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

        /** Icono camión + cronómetro (misma idea que vista supervisor en rutas). */
        async cargarPromediosTiempoMotivo() {
            try {
                const response = await axios.get(BASE_URL + 'api/reclamos/tiempos-promedio');
                this.promediosTiempoMotivoMap = ObraCronometroUtil.indexarPromediosMotivo(response.data);
            } catch (error) {
                console.warn('No se pudieron cargar promedios de tiempo por motivo:', error);
            }
        },

        nivelDemoraObraReclamoOperario(reclamo) {
            const motivo = reclamo?.municipalidad_motivo || '';
            const promedio = ObraCronometroUtil.promedioMinutosMotivo(this.promediosTiempoMotivoMap, motivo);
            return ObraCronometroUtil.nivelDemoraObra(this.tiempoMsSesionReparacionReclamo(reclamo), promedio);
        },

        actualizarIndicadorObraOperario(ref, reclamo) {
            if (!ref || !reclamo) {
                return;
            }
            const hms = this.textoCronometroReparacionReclamo(reclamo);
            const nivel = this.nivelDemoraObraReclamoOperario(reclamo);
            ObraCronometroUtil.actualizarIndicadorObraMapbox(ref.wrap, ref.span, hms, nivel);
        },

        reclamoMuestraCamionObraMapaDetalle(reclamo) {
            if (!reclamo || reclamo.id == null) return false;
            if (!this.rutaSeleccionadaEnEjecucion) return false;
            if (String(reclamo.municipalidad_estado || '').trim() !== 'En ejecución') return false;
            return !!this.sesionReparacionReclamo(reclamo);
        },

        refrescarCompanionsObraMapaDetalleOperario() {
            if (this._googleObraDetalleOperarioRefs?.length) {
                this._googleObraDetalleOperarioRefs = this._googleObraDetalleOperarioRefs.filter((ref) => {
                    const r = (this.reclamos || []).find((x) => Number(x.id) === Number(ref._reclamoIdObra));
                if (!r || !this.reclamoMuestraCamionObraMapaDetalle(r)) {
                        ObraCronometroUtil.quitarCompanionObraGoogle(ref);
                        return false;
                    }
                    this.actualizarIndicadorObraOperario(ref, r);
                    return true;
                });
            }

            if (this._mapboxObraDetalleOperarioRefs?.length) {
                this._mapboxObraDetalleOperarioRefs.forEach((ref) => {
                    const r = (this.reclamos || []).find((x) => Number(x.id) === Number(ref.reclamoId));
                    if (!ref.span || !r || !this.reclamoMuestraCamionObraMapaDetalle(r)) {
                        if (ref.span) ref.span.textContent = '—';
                    return;
                }
                    this.actualizarIndicadorObraOperario(ref, r);
            });
            }
        },

        /** Actualiza HH:MM:SS en el globo del mapa (el HTML del InfoWindow no es reactivo). */
        refrescarCronometrosInfoWindowMapaDetalleOperario() {
            document.querySelectorAll('[data-map-iw-crono-reclamo-id]').forEach((el) => {
                const rid = parseInt(el.getAttribute('data-map-iw-crono-reclamo-id'), 10);
                if (Number.isNaN(rid)) {
                    return;
                }
                const r = (this.reclamos || []).find((x) => Number(x.id) === rid);
                if (!r || !this.mostrarCronometroReparacionReclamo(r)) {
                    ObraCronometroUtil.actualizarTextoCronometroBadge(el, '—', 'reclamo');
                    ObraCronometroUtil.sincronizarClasesNivelCronoObra(el, '');
                    return;
                }
                ObraCronometroUtil.actualizarTextoCronometroBadge(
                    el,
                    this.textoCronometroReparacionReclamo(r),
                    'reclamo'
                );
                ObraCronometroUtil.sincronizarClasesNivelCronoObra(el, this.claseCronometroListaObraOperario(r));
            });
            this.refrescarBadgesObservacionesInfoWindowMapaDetalleOperario();
            this.refrescarBadgesMaterialesInfoWindowMapaDetalleOperario();
        },

        refrescarBadgesObservacionesInfoWindowMapaDetalleOperario() {
            document.querySelectorAll('[data-map-iw-obs-count-id]').forEach((el) => {
                const rid = parseInt(el.getAttribute('data-map-iw-obs-count-id'), 10);
                if (Number.isNaN(rid)) {
                    return;
                }
                const r = (this.reclamos || []).find((x) => Number(x.id) === rid);
                const count = r ? this.cantidadObservacionesEjecucionReclamoOperario(r) : 0;
                const texto = this.textoObservacionesEjecucionBadge(count);
                if (!texto) {
                    el.remove();
                    return;
                }
                el.textContent = texto;
            });
        },

        paradaDeReclamoOperario(reclamo) {
            if (!reclamo?.id) return null;
            return (this.paradasOrdenRuta || []).find((parada) =>
                parada.reclamos.some((r) => Number(r.id) === Number(reclamo.id))
            ) || null;
        },

        crearEncabezadoPopupReclamoOperario(reclamo) {
            const icono = this.iconoMotivoReclamo(reclamo.municipalidad_motivo);
            const color = this.getColorEstado(reclamo.municipalidad_estado || 'Recibido');
            return `
                <div class="mapa-popup-header">
                    <span class="mapa-popup-motivo-icon" style="background-color: ${color};" aria-hidden="true">${icono}</span>
                    <h6>Reclamo #${reclamo.municipalidad_id}</h6>
                </div>
            `;
        },

        crearHtmlAccionesPopupMapaDetalleOperario(reclamo) {
            if (!this.esOperario || !this.puedeOperarRutaSeleccionada) {
                return '';
            }

            const rid = String(reclamo.id);
            const puedeVerRegistros = this.puedeVerRegistrosObraReclamo(reclamo);
            const enEjecucion = this.rutaSeleccionadaEnEjecucion;
            if (!puedeVerRegistros && !enEjecucion) {
                return '';
            }

            const parada = this.paradaDeReclamoOperario(reclamo);
            const obraActivaParada = parada ? this.paradaTieneObraActiva(parada) : false;
            const sesActivaReclamo = (() => {
                const ses = this.sesionReparacionReclamo(reclamo);
                return ses && ses.activo;
            })();
            const mostrarListoPendiente = enEjecucion && this.puedeEditarTareasRutaSeleccionada && (obraActivaParada || sesActivaReclamo);
            const mostrarContinuar = enEjecucion && this.puedeEditarTareasRutaSeleccionada && (parada
                ? this.puedeMostrarContinuarParada(parada)
                : this.puedeMostrarContinuarReparacionReclamo(reclamo));
            const tituloParada = parada && parada.reclamos.length > 1
                ? ' (todos los reclamos en este domicilio con obra activa)'
                : '';

            let html = '<div class="map-detalle-iw-acciones mapa-popup-acciones mapa-popup-acciones--obra border-top pt-2 mt-2">';
            let htmlInicio = '';
            let htmlPaneles = '';

            if (enEjecucion && this.puedeEditarTareasRutaSeleccionada && this.puedeMostrarIniciarReparacionReclamo(reclamo)) {
                htmlInicio += `<button type="button" class="btn btn-sm btn-accion-estado" data-map-accion="iniciar" data-reclamo-id="${rid}"><i class="bi bi-play-fill"></i> Iniciar</button>`;
            }

            if (mostrarListoPendiente) {
                htmlInicio += `<button type="button" class="btn btn-sm btn-accion-estado" data-map-accion="completado" data-reclamo-id="${rid}" title="Marcar como completado${tituloParada}"><i class="bi bi-check-lg"></i></button>`;
                htmlInicio += `<button type="button" class="btn btn-sm btn-accion-estado" data-map-accion="pendiente" data-reclamo-id="${rid}" title="Pendiente para otro día${tituloParada}"><i class="bi bi-pause-circle"></i></button>`;
            } else if (mostrarContinuar) {
                htmlInicio += `<button type="button" class="btn btn-sm btn-accion-estado" data-map-accion="continuar" data-reclamo-id="${rid}" title="Continuar ejecución${tituloParada}"><i class="bi bi-play-fill"></i></button>`;
            }

            if (puedeVerRegistros) {
                if (this.mostrarCronometroReparacionReclamo(reclamo)) {
                    const claseCrono = this.claseCronometroListaObraOperario(reclamo);
                    htmlInicio += ObraCronometroUtil.htmlSpanCronometroBadge(
                        `badge font-monospace map-detalle-iw-crono ruta-secuencia-crono-reparacion ${claseCrono}`,
                        this.textoCronometroReparacionReclamo(reclamo),
                        'reclamo',
                        `data-map-iw-crono-reclamo-id="${rid}" title="Tiempo en obra"`
                    );
                }
                const matCount = this.cantidadMaterialesReclamoOperario(reclamo);
                const tituloMat = matCount > 0
                    ? `Materiales utilizados (${matCount})`
                    : 'Materiales utilizados';
                htmlPaneles += `<button type="button" class="btn btn-sm btn-outline-secondary btn-con-badge-obs" data-map-accion="materiales" data-reclamo-id="${rid}" title="${tituloMat}"><i class="bi bi-box-seam"></i>${this.htmlBadgeMaterialesConId(rid, matCount)}</button>`;
                const obsCount = this.cantidadObservacionesEjecucionReclamoOperario(reclamo);
                const tituloObs = obsCount > 0
                    ? `Registro en obra (${obsCount})`
                    : 'Registro en obra';
                htmlPaneles += `<button type="button" class="btn btn-sm btn-outline-secondary btn-con-badge-obs" data-map-accion="observaciones" data-reclamo-id="${rid}" title="${tituloObs}"><i class="bi bi-journal-text"></i>${this.htmlBadgeObservacionesEjecucionConId(rid, obsCount)}</button>`;
            }

            if (htmlInicio) {
                html += `<div class="map-detalle-iw-acciones__inicio">${htmlInicio}</div>`;
            }
            if (htmlPaneles) {
                html += `<div class="map-detalle-iw-acciones__paneles">${htmlPaneles}</div>`;
            }

            html += '</div>';
            return html;
        },

        crearContenidoPopupReclamoOperario(reclamo, opciones = {}) {
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
            const encabezado = incluirTitulo ? this.crearEncabezadoPopupReclamoOperario(reclamo) : '';

            return `
                <div class="mapa-popup-reclamo map-detalle-iw" data-map-popup-operario="1">
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
                    ${this.crearHtmlAccionesPopupMapaDetalleOperario(reclamo)}
                </div>
            `;
        },

        abrirPopupMapaDetalleOperarioGoogle(marker, indice = null) {
            const reclamos = marker._reclamosGrupo || (marker._reclamo ? [marker._reclamo] : []);
            if (!reclamos.length || !this.mapaDetalleGoogle) {
                return;
            }

            if (indice !== null) {
                marker._indicePopup = indice;
            } else if (marker._indicePopup === undefined) {
                marker._indicePopup = 0;
            }

            if (marker._parada?.clave) {
                this.indiceReclamoListaParadaOperario = {
                    ...this.indiceReclamoListaParadaOperario,
                    [marker._parada.clave]: marker._indicePopup
                };
            }

            const reclamoLista = (this.reclamos || []).find((r) => Number(r.id) === Number(reclamos[marker._indicePopup].id));
            const reclamo = reclamoLista ? { ...reclamos[marker._indicePopup], ...reclamoLista } : reclamos[marker._indicePopup];

            const infoWindow = marker._infoWindow || new google.maps.InfoWindow();
            marker._infoWindow = infoWindow;

            infoWindow.setContent(this.crearContenidoPopupReclamoOperario(reclamo, {
                grupoId: marker._grupoId || null,
                indice: marker._indicePopup,
                total: reclamos.length,
                incluirTitulo: false
            }));

            if (this.infoWindowAbiertoMapaDetalleOperario) {
                this.infoWindowAbiertoMapaDetalleOperario.close();
            }

            infoWindow.open(this.mapaDetalleGoogle, marker);
            this.infoWindowAbiertoMapaDetalleOperario = infoWindow;

            google.maps.event.addListenerOnce(infoWindow, 'domready', () => {
                setTimeout(() => this.vincularEventosPopupMapaDetalleOperario(marker, reclamo), 100);
            });
        },

        vincularEventosPopupMapaDetalleOperario(marker, reclamo) {
            const btnDetalle = document.querySelector(`.mapa-popup-detalle[data-reclamo-id="${reclamo.id}"]`);
            if (btnDetalle) {
                btnDetalle.onclick = () => this.verDetalles(reclamo);
            }

            if (marker._grupoId) {
                const btnPrev = document.querySelector(`.mapa-popup-nav-prev[data-grupo-id="${marker._grupoId}"]`);
                if (btnPrev) {
                    btnPrev.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoMapaDetalleOperario(marker, -1);
                    };
                }

                const btnNext = document.querySelector(`.mapa-popup-nav-next[data-grupo-id="${marker._grupoId}"]`);
                if (btnNext) {
                    btnNext.onclick = (event) => {
                        event.preventDefault();
                        this.navegarPopupGrupoMapaDetalleOperario(marker, 1);
                    };
                }
            }

            const headerElement = document.querySelector('.gm-style-iw-ch');
            if (headerElement) {
                headerElement.innerHTML = this.crearEncabezadoPopupReclamoOperario(reclamo);
            }

            const popupRoot = document.querySelector('[data-map-popup-operario="1"]');
            if (popupRoot) {
                popupRoot.addEventListener('click', (e) => this.onMapaDetalleInfoWindowAccion(e));
            }
        },

        navegarPopupGrupoMapaDetalleOperario(marker, delta) {
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

            if (marker._esMapboxDetalleOperario) {
                this.abrirPopupMapaDetalleOperarioMapbox(marker, nuevoIndice);
            } else {
                this.abrirPopupMapaDetalleOperarioGoogle(marker, nuevoIndice);
            }
        },

        /**
         * Crea el contenido del info window para un reclamo (vistas legacy)
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
         * @deprecated Usar crearContenidoPopupReclamoOperario en mapa detalle operario.
         */
        construirInfoWindowContentMapaDetalleOperario(reclamo) {
            const wrap = document.createElement('div');
            wrap.innerHTML = this.crearContenidoPopupReclamoOperario(reclamo, {
                incluirTitulo: true,
                indice: 0,
                total: 1
            });
            wrap.querySelector('[data-map-popup-operario="1"]')
                ?.addEventListener('click', (e) => this.onMapaDetalleInfoWindowAccion(e));
            const btnDetalle = wrap.querySelector('.mapa-popup-detalle');
            if (btnDetalle) {
                btnDetalle.onclick = () => this.verDetalles(reclamo);
            }
            return wrap;
        },

        async onMapaDetalleInfoWindowAccion(e) {
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
                const parada = this.paradaDeReclamoOperario(r);
                const n = parada ? (parada.reclamos?.length || 1) : 1;
                const mensaje = n > 1
                    ? `¿Confirmás iniciar los reclamos de esta parada?`
                    : `¿Confirmás iniciar el reclamo #${r.municipalidad_id || r.id}?`;
                const ok = await this.mostrarConfirmacion(mensaje, 'Iniciar reclamo');
                if (!ok) return;
                if (parada) {
                    void this.iniciarReparacionParada(parada);
                } else {
                    void this.iniciarReparacionReclamo(r);
                }
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
                const parada = this.paradaDeReclamoOperario(r);
                const n = parada ? this.paradaReclamosConObraActiva(parada).length : 1;
                const mensaje = n > 1
                    ? `¿Confirmás completar los ${n} reclamos en obra de esta parada?`
                    : `¿Confirmás completar el reclamo #${r.municipalidad_id || r.id}?`;
                const ok = await this.mostrarConfirmacion(mensaje, 'Completar reclamo');
                if (!ok) return;
                if (parada) {
                    void this.ejecutarCierreParadaObra(parada, 'completado');
                } else {
                    void this.ejecutarCierreReclamoObra(r, 'completado');
                }
                return;
            }
            if (accion === 'pendiente') {
                const parada = this.paradaDeReclamoOperario(r);
                const n = parada ? this.paradaReclamosConObraActiva(parada).length : 1;
                const mensaje = n > 1
                    ? `¿Confirmás pausar los ${n} reclamos en obra de esta parada?`
                    : `¿Confirmás pausar el reclamo #${r.municipalidad_id || r.id}?`;
                const ok = await this.mostrarConfirmacion(mensaje, 'Pausar reclamo');
                if (!ok) return;
                if (parada) {
                    void this.ejecutarCierreParadaObra(parada, 'pendiente');
                } else {
                    void this.ejecutarCierreReclamoObra(r, 'pendiente');
                }
                return;
            }
            if (accion === 'continuar') {
                const parada = this.paradaDeReclamoOperario(r);
                const n = parada ? this.paradaReclamosContinuables(parada).length : 1;
                const mensaje = n > 1
                    ? `¿Confirmás continuar los ${n} reclamos de esta parada?`
                    : `¿Confirmás continuar el reclamo #${r.municipalidad_id || r.id}?`;
                const ok = await this.mostrarConfirmacion(mensaje, 'Continuar ejecución');
                if (!ok) return;
                if (parada) {
                    void this.continuarReparacionParada(parada);
                } else {
                    void this.continuarReparacionReclamo(r);
                }
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
        if (!this.rutaSeleccionadaId) {
            this.mostrarMensaje('Seleccioná una hoja de ruta antes de añadir reclamos', 'warning');
            return;
        }
        if (!this.puedeAñadirReclamosRutaSeleccionada) {
            this.mostrarMensaje('Solo un operario con permisos de gestión puede añadir reclamos', 'warning');
            return;
        }

        try {
            await this.obtenerReclamosRecibidos();
            
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
        this.añadiendoParadaClave = null;
        this.indiceReclamoParadaAñadir = {};
        
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
            this.indiceReclamoParadaAñadir = {};
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
                reclamo.municipalidad_descripcion,
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
     * Añade la parada completa (todos los reclamos del mismo domicilio) a la hoja.
     */
    async añadirParadaARuta(parada) {
        const reclamo = this.reclamoActivoEnParadaAñadir(parada) || parada?.reclamos?.[0];
        if (!reclamo || !reclamo.id) {
            this.mostrarMensaje('Error: Parada no válida', 'error');
            return;
        }

        if (!this.puedeAñadirReclamosRutaSeleccionada) {
            this.mostrarMensaje('Solo un operario con permisos de gestión puede añadir reclamos', 'warning');
            return;
        }

        const rutaId = this.rutaSeleccionadaId;
        if (!rutaId) {
            this.mostrarMensaje('Seleccioná una hoja de ruta antes de añadir reclamos', 'warning');
            return;
        }

        this.añadiendoReclamo = reclamo.id;
        this.añadiendoParadaClave = parada.clave;

        try {
            const response = await axios.post(BASE_URL + 'api/rutas/operario/add-reclamo', {
                reclamo_id: reclamo.id,
                ruta_id: rutaId
            });

            const añadidos = response.data.reclamos || (response.data.reclamo ? [response.data.reclamo] : []);
            for (const item of añadidos) {
                this.reclamos.push(item);
            }
            this.reclamos = this.eliminarDuplicadosReclamos(this.reclamos);

            const idsAñadidos = new Set(añadidos.map((r) => Number(r.id)));
            // Quitar también cualquier otro del mismo domicilio que haya quedado
            const clave = parada.clave;
            this.reclamosRecibidos = this.reclamosRecibidos.filter((r) => {
                if (idsAñadidos.has(Number(r.id))) return false;
                return this.claveDomicilioReclamo(r) !== clave;
            });
            this.filtrarReclamosRecibidos();

            if (response.data.cantidadReclamos != null) {
                this.actualizarContadoresRutas(response.data.cantidadReclamos);
            } else {
                this.actualizarContadoresRutas();
            }

            if (response.data.tiempoEstimado != null && this.rutaSeleccionadaId) {
                const t = response.data.tiempoEstimado;
                const id = this.rutaSeleccionadaId;
                const patchTiempo = (lista) => (lista || []).map((r) =>
                    Number(r.id) === Number(id) ? { ...r, tiempoEstimado: t } : r
                );
                this.rutas = patchTiempo(this.rutas);
                this.rutasPanel = patchTiempo(this.rutasPanel);
            }

            this.actualizarMapaDespuesDeAñadirReclamo();

            const nombreHoja = this.rutaSeleccionada?.nombre || 'la hoja de ruta';
            const n = añadidos.length;
            this.mostrarMensaje(
                n > 1
                    ? `${n} reclamos del mismo domicilio añadidos a ${nombreHoja}`
                    : `Reclamo #${reclamo.municipalidad_id} añadido a ${nombreHoja}`,
                'success'
            );

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
            this.añadiendoParadaClave = null;
        }
    },

    /**
     * Compatibilidad: añadir desde detalles usa la parada del domicilio.
     */
    async añadirReclamoARuta(reclamo) {
        if (!reclamo || !reclamo.id) {
            this.mostrarMensaje('Error: Reclamo no válido', 'error');
            return;
        }
        const clave = this.claveDomicilioReclamo(reclamo);
        const delDom = this.reclamosRecibidos.filter((r) => this.claveDomicilioReclamo(r) === clave);
        const parada = { clave, reclamos: delDom.length ? delDom : [reclamo] };
        this.indiceReclamoParadaAñadir = {
            ...this.indiceReclamoParadaAñadir,
            [clave]: Math.max(0, delDom.findIndex((r) => Number(r.id) === Number(reclamo.id))),
        };
        await this.añadirParadaARuta(parada);
    },

    /**
     * Actualiza el contador de la hoja seleccionada después de añadir un reclamo
     */
    actualizarContadoresRutas(cantidadOpcional) {
        const rutaId = this.rutaSeleccionadaId;
        if (!rutaId) return;

        const cantidad = cantidadOpcional != null ? cantidadOpcional : this.reclamos.length;
        const patch = (lista) => (lista || []).map(r =>
            Number(r.id) === Number(rutaId) ? { ...r, cantidadReclamos: cantidad } : r
        );
        this.rutas = patch(this.rutas);
        this.rutasPanel = patch(this.rutasPanel);
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
        this.cargandoCatalogoMateriales = true;
        try {
        if (this.tiposMaterial.length === 0) {
            await this.obtenerTiposMaterial();
        }
        await this.filtrarMaterialesPorTipo();
            await this.obtenerHistorialMateriales();
        } finally {
            this.cargandoCatalogoMateriales = false;
        }
    },

    urlFotoMaterialCatalogo(nombreArchivo) {
        if (!nombreArchivo) return '';
        return BASE_URL + 'static/uploads/materiales/' + nombreArchivo;
    },

    seleccionarTipoMaterialObra(tipoId) {
        this.materialSeleccionado.tipo_id = tipoId === '' || tipoId == null ? '' : tipoId;
        this.materialSeleccionado.material_id = '';
        this.filtrarMaterialesPorTipo();
    },

    seleccionarMaterialObra(mat) {
        if (!mat || !mat.id) return;
        this.materialSeleccionado.material_id = mat.id;
        this.materialSeleccionado.cantidad = 1;
    },

    limpiarSeleccionMaterialObra() {
            this.materialSeleccionado.material_id = '';
            this.materialSeleccionado.cantidad = null;
        this.materialSeleccionado.observacion = '';
    },

    ajustarCantidadMaterialObra(delta) {
        const actual = Number(this.materialSeleccionado.cantidad);
        const base = Number.isFinite(actual) && actual >= 1 ? actual : 1;
        this.materialSeleccionado.cantidad = Math.max(1, base + delta);
    },

    async eliminarMaterialObra(item) {
        if (!item?.id || this.modalMaterialesSoloLectura) return;

        const nombre = item.material_nombre || 'este material';
        const confirmar = await this.confirmarEliminarMaterialObra(nombre);
        if (!confirmar) return;

        this.eliminandoMaterialReclamoId = item.id;
        try {
            await axios.delete(BASE_URL + 'api/reclamos/materiales/' + item.id);
            this.mostrarMensaje('Material eliminado.', 'success');
            await this.obtenerHistorialMateriales();
        } catch (error) {
            console.error('Error al eliminar material:', error);
            const mensajeError = error.response?.data?.message
                || 'Error al eliminar el material';
            this.mostrarMensaje(mensajeError, 'error');
        } finally {
            this.eliminandoMaterialReclamoId = null;
        }
    },

    confirmarEliminarMaterialObra(nombre) {
        this.confirmarEliminarMaterialNombre = nombre || 'este material';
        return new Promise((resolve) => {
            this._resolverConfirmarEliminarMaterial = resolve;
            this.$nextTick(() => {
                const el = document.getElementById('modalConfirmarEliminarMaterial');
                if (!el) {
                    resolve(false);
                    return;
                }
                const modal = bootstrap.Modal.getOrCreateInstance(el, {
                    backdrop: 'static',
                    keyboard: false
                });
                modal.show();
            });
        });
    },

    resolverConfirmarEliminarMaterial(aceptar) {
        const resolve = this._resolverConfirmarEliminarMaterial;
        this._resolverConfirmarEliminarMaterial = null;
        const el = document.getElementById('modalConfirmarEliminarMaterial');
        if (el) {
            const modal = bootstrap.Modal.getInstance(el);
            if (modal) modal.hide();
        }
        if (typeof resolve === 'function') {
            resolve(!!aceptar);
        }
    },

    onConfirmarEliminarMaterialOculto() {
        if (typeof this._resolverConfirmarEliminarMaterial === 'function') {
            const resolve = this._resolverConfirmarEliminarMaterial;
            this._resolverConfirmarEliminarMaterial = null;
            resolve(false);
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
            this.materialesFiltrados = response.data || [];
            
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
            this.mostrarMensaje('Debe seleccionar un material y una cantidad válida (mínimo 1)', 'warning');
            return;
        }

        if (!this.reclamoSeleccionado.id) {
            this.mostrarMensaje('Error: No hay reclamo seleccionado', 'error');
            return;
        }

        this.guardandoMaterialObra = true;
        try {
            const datos = {
                material_id: this.materialSeleccionado.material_id,
                cantidad: Number(this.materialSeleccionado.cantidad),
                observacion: this.materialSeleccionado.observacion || null
            };

            await axios.post(
                BASE_URL + 'api/reclamos/' + this.reclamoSeleccionado.id + '/materiales',
                datos
            );
            this.mostrarMensaje('Material registrado exitosamente', 'success');
            
            this.materialSeleccionado = {
                tipo_id: this.materialSeleccionado.tipo_id,
                material_id: '',
                cantidad: null,
                observacion: ''
            };
            this.filtroBusquedaMaterial = '';
            
            this.mostrarHistorialMateriales = true;
                await this.obtenerHistorialMateriales();
            
        } catch (error) {
            console.error('Error al guardar material:', error);
            const mensajeError = error.response && error.response.data && error.response.data.message 
                ? error.response.data.message 
                : 'Error al guardar el material';
            this.mostrarMensaje(mensajeError, 'error');
        } finally {
            this.guardandoMaterialObra = false;
        }
    },

    /**
     * Obtiene el historial de materiales del reclamo
     */
    async obtenerHistorialMateriales(opciones = {}) {
        const { silencioso = false } = opciones;
        if (!this.reclamoSeleccionado.id) {
            return;
        }

        const mostrarSpinner = !silencioso || this.historialMateriales.length === 0;
        if (mostrarSpinner) {
            this.cargandoMateriales = true;
        }
        
        try {
            const response = await axios.get(
                BASE_URL + 'api/reclamos/' + this.reclamoSeleccionado.id + '/materiales'
            );
            const nuevos = response.data || [];
            const fpNuevo = (nuevos || []).map((m) => `${m.id}:${m.cantidad || ''}:${m.fecha || ''}:${m.observacion || ''}`).join('|');
            const fpActual = (this.historialMateriales || []).map((m) => `${m.id}:${m.cantidad || ''}:${m.fecha || ''}:${m.observacion || ''}`).join('|');
            if (fpNuevo !== fpActual) {
                this.historialMateriales = nuevos;
            }
            this.actualizarCountMaterialesReclamoOperario(
                this.reclamoSeleccionado.id,
                nuevos.length
            );
        } catch (error) {
            console.error('Error al obtener historial de materiales:', error);
            if (!silencioso) {
                this.mostrarMensaje('Error al cargar el historial de materiales', 'error');
                this.historialMateriales = [];
            }
        } finally {
            this.cargandoMateriales = false;
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
            await Promise.all([
                this.obtenerRutasOperario(),
                this.obtenerCuadrillas(),
                this.cargarPromediosTiempoMotivo()
            ]);
            this._ultimoFingerprintRutasOperario = this._fingerprintRutasOperario(this.rutasPanel);
            this.iniciarRelojEjecucionOperario();
            this.iniciarSyncOperarioLiviano();
            document.addEventListener('click', this.onClickFueraConfirmarAccionParada);
            await this.$nextTick();
            this.inicializarMapasPreviewOperario();
        } else {
            await this.obtenerReclamos();
            await this.obtenerRutasOperario();
        }
    },

    beforeUnmount() {
        this.cerrarModalFotoBitacoraObra();
        this.detenerRelojEjecucionOperario();
        this.detenerSyncOperarioLiviano();
        document.removeEventListener('click', this.onClickFueraConfirmarAccionParada);
        this.limpiarMapasPreviewOperario();
    }
});