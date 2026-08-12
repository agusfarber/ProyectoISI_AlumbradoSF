const app = Vue.createApp({
    data() {
        return {
            reclamos: [],
            reclamo: {
                municipalidad_id: '',
                municipalidad_tipo: 'ALUMBRADO PÚBLICO',
                municipalidad_motivo: '',
                municipalidad_fechaInicio: '',
                municipalidad_fechaModificacion: '',
                municipalidad_recepcion: '',
                municipalidad_estado: '',
                prioridad: '', // Campo para la prioridad, ahora simplemente 'prioridad'
                municipalidad_telefono: '',
                municipalidad_domicilio: '',
                municipalidad_numeroDomicilio: '',
                municipalidad_entreCalleUno: '',
                municipalidad_entreCalleDos: '',
                municipalidad_ciudadano: '',
                municipalidad_descripcion: ''
            },
            reclamoSeleccionado: {},
            tabla: null,
            // Variables para los filtros
            filtroEstado: '',
            filtroFechaDesde: '',
            filtroFechaHasta: '',
            filtroBusqueda: '',
            filtroPrioridad: '', // Filtro de prioridad
            // Variables para sincronización y token 103
            tokenDisponible: false,
            tokenActual: null,
            credenciales: {
                api_token: ''
            },
            credencialesGuardadas: false,
            guardandoFicha: false,
            observacionEliminacion: '',
            eliminandoReclamo: false,
            reclamoAEliminar: null,
            syncFechaDesde: '',
            syncFechaHasta: '',
            numeroReclamo: '',
            syncOpcionActiva: 'fechas',
            // Variables para progreso
            sincronizando: false,
            syncFase: '', // 'descarga' | 'procesando'
            syncTramoActual: 0,
            syncTramosTotal: 0,
            syncDetalle: '',
            progresoActual: 0,
            progresoTotal: 0,
            detenerSincronizacion: false,
            domicilioAutocomplete: null,
            domicilioAutocompleteListo: false
        };
    },

    computed: {
        modoCreacion() {
            return !this.reclamo?.id;
        },

        syncPorcentaje() {
            if (this.syncFase === 'descarga' && this.syncTramosTotal > 0) {
                return Math.min(100, Math.round((this.syncTramoActual / this.syncTramosTotal) * 100));
            }
            if (this.syncFase === 'procesando' && this.progresoTotal > 0) {
                return Math.min(100, Math.round((this.progresoActual / this.progresoTotal) * 100));
            }
            return 0;
        },

        syncEtiqueta() {
            if (this.syncFase === 'descarga') {
                return 'Descargando del 103';
            }
            if (this.syncFase === 'procesando') {
                return 'Guardando reclamos';
            }
            return 'Procesando';
        },

        syncContadorTexto() {
            if (this.syncFase === 'descarga' && this.syncTramosTotal > 0) {
                return `Tramo ${this.syncTramoActual} / ${this.syncTramosTotal}`;
            }
            if (this.syncFase === 'procesando' && this.progresoTotal > 0) {
                return `${this.progresoActual} / ${this.progresoTotal}`;
            }
            return '…';
        }
    },

    methods: {
        esOrigenLocal(reclamo) {
            if (!reclamo) return false;
            if (reclamo.origen === 'local') return true;
            return String(reclamo.municipalidad_id || '').startsWith('L');
        },

        /**
         * Obtiene los reclamos desde la API.
         * Después de obtener los datos, inicializa o actualiza la DataTable.
         */
        async obtenerReclamos() {
            try {
                const urlReclamos = BASE_URL + 'api/reclamos';
                console.log('URL Reclamos:', urlReclamos);

                const response = await axios.get(urlReclamos);
                console.log('Respuesta de la API reclamos:', response.data);
                this.reclamos = response.data;
                console.log('Reclamos después de asignar:', this.reclamos);
                // Asegurarse de que el DOM esté actualizado antes de inicializar DataTables
                this.$nextTick(() => {
                    console.log('Inicializando tabla con reclamos:', this.reclamos);
                    this.inicializarTabla();
                });
            } catch (error) {
                console.error('Error al obtener datos:', error);
                console.error('URL que falló:', error.config?.url);
            }
        },

        /**
         * Inicializa o reinicia la DataTable con los datos actuales de reclamos.
         * Configura el renderizado de columnas y vincula eventos para los botones de acción.
         */
        inicializarTabla() {
            if (this.tabla) {
                console.log('Destruyendo tabla anterior');
                this.tabla.destroy();
            }
            this.reclamos.sort((a, b) => {
                const fa = a.municipalidad_fechaInicio ? new Date(a.municipalidad_fechaInicio).getTime() : 0;
                const fb = b.municipalidad_fechaInicio ? new Date(b.municipalidad_fechaInicio).getTime() : 0;
                return fb - fa;
            });
            console.log('Creando nueva tabla con datos:', this.reclamos);
            this.tabla = $('#tabla_reclamos').DataTable({
                data: this.reclamos,
                responsive: true,
                ordering: false,
                pageLength: 30,
                pagingType: 'simple_numbers',
                lengthMenu: [
                    [15, 30, 50, 100],
                    ['15 por página', '30 por página', '50 por página', '100 por página']
                ],
                language: {
                    processing: 'Procesando...',
                    search: '',
                    searchPlaceholder: 'Buscar reclamo...',
                    lengthMenu: '_MENU_',
                    zeroRecords: 'No se encontraron reclamos',
                    emptyTable: 'Todavía no hay reclamos cargados',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ reclamos',
                    infoEmpty: 'Sin reclamos para mostrar',
                    infoFiltered: '(filtrado de _MAX_ reclamos)',
                    loadingRecords: 'Cargando...',
                    paginate: {
                        previous: 'Anterior',
                        next: 'Siguiente'
                    }
                },

                columns: [
                    {
                        data: 'municipalidad_id',
                        type: 'num',
                        className: 'text-start text-nowrap',
                        render: (data, type, row) => {
                            const esLocal = row.origen === 'local' || String(data || '').startsWith('L');
                            const badge = esLocal
                                ? '<span class="reclamo-origen reclamo-origen--local me-1">Local</span>'
                                : '<span class="reclamo-origen reclamo-origen--103 me-1">103</span>';
                            return `${badge}<a href="#" class="ver-reclamo-id text-primary fw-bold" data-id="${row.id}" style="text-decoration: none; cursor: pointer;">${data}</a>`;
                        }
                    },
                    { 
                        data: 'municipalidad_motivo',
                        className: 'text-start'
                    },
                    {
                        data: 'municipalidad_fechaInicio',
                        className: 'text-start text-nowrap',
                        render: (data) => this.formatearFecha(data)
                    },
                    {
                        data: 'municipalidad_fechaModificacion',
                        className: 'text-start text-nowrap',
                        render: (data) => this.formatearFecha(data)
                    },
                    { 
                        data: 'municipalidad_estado',
                        className: 'text-start text-nowrap',
                        render: (data, type, row) => {
                            // Si el reclamo está cerrado, mostrar "Cerrado" en lugar de "Completado"
                            if (row.cerrado == 1 && data === 'Completado') {
                                return '<span class="badge reclamo-estado reclamo-estado--cerrado">Cerrado</span>';
                            }
                            // Colores alineados al mapa de reclamos
                            const estadoClass = {
                                'Recibido': 'reclamo-estado--recibido',
                                'Asignado': 'reclamo-estado--asignado',
                                'Pendiente': 'reclamo-estado--pendiente',
                                'En ejecución': 'reclamo-estado--en-ejecucion',
                                'Completado': 'reclamo-estado--completado',
                                'En plan': 'reclamo-estado--recibido',
                                'Error de datos': 'reclamo-estado--recibido'
                            };
                            const badgeClass = estadoClass[data] || 'reclamo-estado--recibido';
                            return `<span class="badge reclamo-estado ${badgeClass}">${data}</span>`;
                        }
                    },
                    { 
                        data: 'municipalidad_domicilio',
                        className: 'text-start'
                    },
                    { 
                        data: 'municipalidad_numeroDomicilio',
                        className: 'text-start'
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center text-nowrap',
                        render: (data, type, row) => {
                            return `
                                <button type="button" class="reclamos-btn reclamos-btn--outline reclamos-btn--sm editar-reclamo" data-id="${row.id}" title="Editar ficha">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="reclamos-btn reclamos-btn--sm reclamos-btn--danger eliminar-reclamo" data-id="${row.id}" title="Eliminar reclamo">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }
                    }
                ],
                columnDefs: [
                    { defaultContent: '-', targets: '_all' }
                ],
                order: [[0, 'desc']],
                initComplete: function () {
                    const wrapper = $('#tabla_reclamos_wrapper');
                    wrapper.find('.dt-length select').addClass('form-select form-select-sm');
                    wrapper.find('.dt-search input').addClass('form-control form-control-sm').attr('aria-label', 'Buscar reclamo');
                }
            });

            $('#tabla_reclamos tbody').off('click', '.ver-reclamo-id').on('click', '.ver-reclamo-id', (e) => {
                e.preventDefault();
                const id = $(e.currentTarget).data('id');
                const reclamo = this.reclamos.find(r => r.id == id);
                if (reclamo) this.verReclamo(reclamo);
            });
            $('#tabla_reclamos tbody').off('click', '.editar-reclamo').on('click', '.editar-reclamo', (e) => {
                const id = $(e.currentTarget).data('id');
                const reclamo = this.reclamos.find(r => r.id == id);
                if (reclamo) this.editarReclamo(reclamo);
            });
            $('#tabla_reclamos tbody').off('click', '.eliminar-reclamo').on('click', '.eliminar-reclamo', (e) => {
                const id = $(e.currentTarget).data('id');
                const reclamo = this.reclamos.find(r => r.id == id);
                if (reclamo) this.eliminarReclamo(reclamo);
            });
        },

        /**
         * Aplica los filtros de búsqueda global, estado, prioridad y rango de fechas a la tabla.
         */
        aplicarFiltros() {
            if (!this.tabla) return;

            while ($.fn.dataTable.ext.search.length > 0) {
                $.fn.dataTable.ext.search.pop();
            }

            // Filtro por rango de fechas
            let fechaDesde = null;
            if (this.filtroFechaDesde) {
                fechaDesde = new Date(this.filtroFechaDesde + 'T00:00:00');
            }
            let fechaHasta = null;
            if (this.filtroFechaHasta) {
                fechaHasta = new Date(this.filtroFechaHasta + 'T23:59:59');
            }

            if (fechaDesde || fechaHasta) {
                $.fn.dataTable.ext.search.push(
                    (settings, data, dataIndex) => {
                        // Obtener la fecha de inicio desde los datos originales (sin formatear)
                        const reclamo = this.reclamos[dataIndex];
                        if (!reclamo || !reclamo.municipalidad_fechaInicio) {
                            return false;
                        }
                        
                        // Crear objeto Date desde la fecha de inicio original
                        const fechaInicioTabla = new Date(reclamo.municipalidad_fechaInicio);
                        if (isNaN(fechaInicioTabla.getTime())) {
                            return false;
                        }
                        
                        // Comparar solo la parte de fecha (ignorando hora)
                        const fechaInicioSoloFecha = new Date(fechaInicioTabla.getFullYear(), fechaInicioTabla.getMonth(), fechaInicioTabla.getDate());
                        const fechaDesdeSoloFecha = fechaDesde ? new Date(fechaDesde.getFullYear(), fechaDesde.getMonth(), fechaDesde.getDate()) : null;
                        const fechaHastaSoloFecha = fechaHasta ? new Date(fechaHasta.getFullYear(), fechaHasta.getMonth(), fechaHasta.getDate()) : null;
                        
                        // Verificar si la fecha está dentro del rango (inclusivo en ambos extremos)
                        const pasaFechaDesde = !fechaDesdeSoloFecha || fechaInicioSoloFecha.getTime() >= fechaDesdeSoloFecha.getTime();
                        const pasaFechaHasta = !fechaHastaSoloFecha || fechaInicioSoloFecha.getTime() <= fechaHastaSoloFecha.getTime();
                        
                        // Log para depuración (puedes comentar esta línea después de verificar que funciona)
                        if (fechaDesdeSoloFecha && fechaHastaSoloFecha) {
                            console.log(`Reclamo ${reclamo.municipalidad_id}: ${fechaInicioSoloFecha.toDateString()} - Desde: ${fechaDesdeSoloFecha.toDateString()} Hasta: ${fechaHastaSoloFecha.toDateString()} - Pasa: ${pasaFechaDesde && pasaFechaHasta}`);
                        }
                        
                        return pasaFechaDesde && pasaFechaHasta;
                    }
                );
            }

            this.tabla.search(this.filtroBusqueda);

            // Aplicar filtro por estado (columna 5)
            if (this.filtroEstado) {
                this.tabla.column(5).search('^' + this.filtroEstado + '$', true, false);
            } else {
                this.tabla.column(5).search('');
            }

            // Aplicar filtro por prioridad (columna 6)
            // Asegúrate que el índice de la columna es correcto, contando desde 0
            if (this.filtroPrioridad) {
                this.tabla.column(6).search('^' + this.filtroPrioridad + '$', true, false);
            } else {
                this.tabla.column(6).search('');
            }

            this.tabla.draw();
        },

        /**
         * Limpia todos los filtros de búsqueda y restablece la tabla.
         */
        limpiarFiltros() {
            this.filtroEstado = '';
            this.filtroFechaDesde = '';
            this.filtroFechaHasta = '';
            this.filtroBusqueda = '';
            this.filtroPrioridad = ''; // Limpiar el filtro de prioridad

            while ($.fn.dataTable.ext.search.length > 0) {
                $.fn.dataTable.ext.search.pop();
            }

            if (this.tabla) {
                this.tabla.search('');
                this.tabla.columns().search('');
                this.tabla.draw();
            }
        },

        /**
         * Abre el formulario para agregar un nuevo reclamo, inicializando los campos.
         */
        abrirFormulario() {
            const ahora = this.obtenerFechaActualArgentina();
            this.reclamo = {
                id: null,
                municipalidad_id: '',
                municipalidad_tipo: 'ALUMBRADO PÚBLICO',
                municipalidad_motivo: '',
                municipalidad_fechaInicio: ahora,
                municipalidad_fechaModificacion: ahora,
                municipalidad_recepcion: '',
                municipalidad_estado: 'Recibido',
                prioridad: '',
                municipalidad_telefono: '',
                municipalidad_domicilio: '',
                municipalidad_numeroDomicilio: '',
                municipalidad_entreCalleUno: '',
                municipalidad_entreCalleDos: '',
                municipalidad_ciudadano: '',
                municipalidad_descripcion: '',
                origen: 'local'
            };
            new bootstrap.Modal(document.getElementById('modalReclamo')).show();
        },

        /**
         * Carga los datos de un reclamo existente en el modal de edición de ficha.
         */
        editarReclamo(reclamo) {
            this.reclamo = {
                id: reclamo.id,
                municipalidad_id: reclamo.municipalidad_id,
                municipalidad_tipo: reclamo.municipalidad_tipo || 'ALUMBRADO PÚBLICO',
                municipalidad_motivo: reclamo.municipalidad_motivo || '',
                municipalidad_recepcion: reclamo.municipalidad_recepcion || '',
                municipalidad_estado: reclamo.municipalidad_estado || '',
                prioridad: reclamo.prioridad || '',
                municipalidad_telefono: reclamo.municipalidad_telefono || '',
                municipalidad_domicilio: reclamo.municipalidad_domicilio || '',
                municipalidad_numeroDomicilio: reclamo.municipalidad_numeroDomicilio || '',
                municipalidad_entreCalleUno: reclamo.municipalidad_entreCalleUno || '',
                municipalidad_entreCalleDos: reclamo.municipalidad_entreCalleDos || '',
                municipalidad_ciudadano: reclamo.municipalidad_ciudadano || '',
                municipalidad_descripcion: reclamo.municipalidad_descripcion || '',
                ficha_editada: reclamo.ficha_editada,
                origen: reclamo.origen || '103',
            };
            new bootstrap.Modal(document.getElementById('modalReclamo')).show();
        },

        editarDesdeDetalle() {
            const reclamo = this.reclamoSeleccionado;
            if (!reclamo || !reclamo.id) return;
            bootstrap.Modal.getInstance(document.getElementById('modalVerReclamo'))?.hide();
            this.$nextTick(() => this.editarReclamo(reclamo));
        },

        eliminarDesdeDetalle() {
            const reclamo = this.reclamoSeleccionado;
            if (!reclamo || !reclamo.id) return;
            bootstrap.Modal.getInstance(document.getElementById('modalVerReclamo'))?.hide();
            this.$nextTick(() => this.eliminarReclamo(reclamo));
        },

        /**
         * Muestra los detalles de un reclamo en un modal de solo lectura.
         */
        verReclamo(reclamo) {
            this.reclamoSeleccionado = { ...reclamo };
            new bootstrap.Modal(document.getElementById('modalVerReclamo')).show();
        },

        /**
         * Crea un reclamo local o guarda correcciones de ficha.
         */
        async guardarReclamo() {
            if (!(this.reclamo.municipalidad_motivo || '').trim()) {
                this.mostrarMensaje('El motivo es obligatorio.', 'warning');
                return;
            }
            if (!(this.reclamo.municipalidad_domicilio || '').trim()) {
                this.mostrarMensaje('El domicilio es obligatorio.', 'warning');
                return;
            }
            if (!(this.reclamo.municipalidad_numeroDomicilio || '').trim()) {
                this.mostrarMensaje('El número de domicilio es obligatorio.', 'warning');
                return;
            }
            if (this.guardandoFicha) return;

            this.guardandoFicha = true;

            try {
                if (this.modoCreacion) {
                    const payload = {
                        municipalidad_motivo: this.reclamo.municipalidad_motivo,
                        municipalidad_recepcion: this.reclamo.municipalidad_recepcion,
                        municipalidad_telefono: this.reclamo.municipalidad_telefono,
                        municipalidad_domicilio: this.reclamo.municipalidad_domicilio,
                        municipalidad_numeroDomicilio: this.reclamo.municipalidad_numeroDomicilio,
                        municipalidad_entreCalleUno: this.reclamo.municipalidad_entreCalleUno,
                        municipalidad_entreCalleDos: this.reclamo.municipalidad_entreCalleDos,
                        municipalidad_ciudadano: this.reclamo.municipalidad_ciudadano,
                        municipalidad_descripcion: this.reclamo.municipalidad_descripcion,
                        municipalidad_fechaInicio: this.reclamo.municipalidad_fechaInicio,
                    };
                    await axios.post(BASE_URL + 'api/reclamos', payload);
                    bootstrap.Modal.getInstance(document.getElementById('modalReclamo'))?.hide();
                    this.mostrarMensaje('Reclamo local creado correctamente.', 'success');
                } else {
                    const payload = {
                        municipalidad_motivo: this.reclamo.municipalidad_motivo,
                        municipalidad_recepcion: this.reclamo.municipalidad_recepcion,
                        municipalidad_telefono: this.reclamo.municipalidad_telefono,
                        municipalidad_domicilio: this.reclamo.municipalidad_domicilio,
                        municipalidad_numeroDomicilio: this.reclamo.municipalidad_numeroDomicilio,
                        municipalidad_entreCalleUno: this.reclamo.municipalidad_entreCalleUno,
                        municipalidad_entreCalleDos: this.reclamo.municipalidad_entreCalleDos,
                        municipalidad_ciudadano: this.reclamo.municipalidad_ciudadano,
                        municipalidad_descripcion: this.reclamo.municipalidad_descripcion,
                    };
                    await axios.put(BASE_URL + 'api/reclamos/' + this.reclamo.id + '/ficha', payload);
                    bootstrap.Modal.getInstance(document.getElementById('modalReclamo'))?.hide();
                    this.mostrarMensaje('Ficha actualizada correctamente.', 'success');
                }
                await this.obtenerReclamos();
            } catch (error) {
                console.error('Error al guardar reclamo:', error);
                const msg = error?.response?.data?.messages
                    || error?.response?.data?.message
                    || 'Error al guardar el reclamo';
                const texto = typeof msg === 'object' ? Object.values(msg).flat().join(' ') : String(msg);
                this.mostrarMensaje(texto, 'danger');
            } finally {
                this.guardandoFicha = false;
            }
        },

        /**
         * Abre el modal de confirmación para excluir un reclamo.
         */
        eliminarReclamo(reclamo) {
            this.reclamoAEliminar = { ...reclamo };
            this.observacionEliminacion = '';
            this.eliminandoReclamo = false;
            new bootstrap.Modal(document.getElementById('modalEliminarReclamo')).show();
        },

        /**
         * Confirma la exclusión lógica del reclamo (no hard delete).
         */
        async confirmarEliminarReclamo() {
            if (!this.reclamoAEliminar?.id || this.eliminandoReclamo) return;

            this.eliminandoReclamo = true;
            try {
                await axios.delete(BASE_URL + 'api/reclamos/' + this.reclamoAEliminar.id, {
                    data: { observacion: this.observacionEliminacion || '' }
                });
                bootstrap.Modal.getInstance(document.getElementById('modalEliminarReclamo'))?.hide();
                this.mostrarMensaje('Reclamo excluido correctamente.', 'success');
                this.reclamoAEliminar = null;
                this.observacionEliminacion = '';
                await this.obtenerReclamos();
            } catch (error) {
                console.error('Error al eliminar reclamo:', error);
                const texto = error.response?.data?.messages?.error
                    || error.response?.data?.messages
                    || error.response?.data?.message
                    || 'Error al eliminar el reclamo';
                const mensaje = typeof texto === 'object' ? Object.values(texto).flat().join(' ') : texto;
                this.mostrarMensaje(mensaje, 'danger');
            } finally {
                this.eliminandoReclamo = false;
            }
        },

        /**
         * Obtiene la fecha y hora actual en la zona horaria de Argentina
         * y la formatea para un input de tipo datetime-local (YYYY-MM-DDTHH:MM).
         * @returns {string} La fecha y hora actual formateada.
         */
        obtenerFechaActualArgentina() {
            const partes = new Intl.DateTimeFormat('sv-SE', {
                timeZone: 'America/Argentina/Buenos_Aires',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            }).format(new Date());
            return partes.replace(' ', 'T');
        },

        /**
         * Formatea una cadena de fecha a un formato legible para mostrar en la tabla.
         * Considera la zona horaria de Argentina.
         * @param {string} fecha La cadena de fecha a formatear.
         * @returns {string} La fecha formateada o cadena vacía si no es válida.
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
                console.error('Error al formatear fecha para mostrar:', error);
                return fecha;
            }
        },

        /**
         * Formatea una cadena de fecha para que sea compatible con un input datetime-local.
         * Considera la zona horaria de Argentina.
         * @param {string} fecha La cadena de fecha a formatear.
         * @returns {string} La fecha formateada (YYYY-MM-DDTHH:MM) o cadena vacía.
         */
        formatearFechaParaInput(fecha) {
            if (!fecha) return '';

            try {
                const date = new Date(fecha);
                const offset = date.getTimezoneOffset() + (3 * 60);
                const fechaArgentina = new Date(date.getTime() - offset * 60 * 1000);
                return fechaArgentina.toISOString().slice(0, 16);
            } catch (error) {
                console.error('Error al formatear fecha para input:', error);
                return fecha;
            }
        },

        /**
         * Convierte una fecha de input (YYYY-MM-DDTHH:MM) a un formato de base de datos (YYYY-MM-DD HH:MM:SS).
         * Considera la zona horaria de Argentina.
         * @param {string} fechaInput La cadena de fecha del input datetime-local.
         * @returns {string} La fecha formateada para la base de datos.
         */
        convertirFechaParaBD(fechaInput) {
            if (!fechaInput) return '';

            try {
                const date = new Date(fechaInput);
                const offset = date.getTimezoneOffset() + (3 * 60);
                const fechaArgentina = new Date(date.getTime() - offset * 60 * 1000);

                return fechaArgentina.toISOString().slice(0, 19).replace('T', ' ');
            }
            catch (error) {
                console.error('Error al convertir fecha para BD:', error);
                return fechaInput;
            }
        },

        /**
         * Abre el panel de sincronización con la opción elegida desde el menú.
         * Si ya está abierto con la misma opción, lo oculta.
         */
        mostrarOpcionesSincronizacion(opcion) {
            const panel = document.getElementById('sincronizacionAvanzadaPanel');
            const collapse = panel && window.bootstrap
                ? bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false })
                : null;
            const yaVisible = panel?.classList.contains('show');

            if (yaVisible && this.syncOpcionActiva === opcion) {
                collapse?.hide();
                return;
            }

            this.syncOpcionActiva = opcion;

            this.$nextTick(() => {
                collapse?.show();
            });
        },

        /**
         * Oculta el panel de sincronización avanzada.
         */
        ocultarOpcionesSincronizacion() {
            const panel = document.getElementById('sincronizacionAvanzadaPanel');
            if (panel && window.bootstrap) {
                bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).hide();
            }
        },

        /**
         * Verifica si hay credenciales guardadas para la sincronización
         */
        async obtenerTokenActual() {
            try {
                const response = await axios.get(BASE_URL + 'api/token103');
                if (response.data && response.data.length > 0) {
                    const ultimo = response.data[0];
                    if ((ultimo.api_token || '').trim()) {
                        this.tokenActual = ultimo;
                        this.tokenDisponible = true;
                        this.credencialesGuardadas = true;
                        this.credenciales.api_token = ultimo.api_token;
                        return;
                    }
                }
                this.limpiarEstadoCredenciales();
            } catch (error) {
                console.error('Error al obtener token:', error);
                this.limpiarEstadoCredenciales();
            }
        },

        logRespuesta103Cruda(data) {
            if (data && Object.prototype.hasOwnProperty.call(data, 'debug_respuesta_103')) {
                console.log('[Sistema 103] JSON crudo recibido:', data.debug_respuesta_103);
            }
        },

        limpiarEstadoCredenciales() {
            this.tokenActual = null;
            this.tokenDisponible = false;
            this.credencialesGuardadas = false;
            this.credenciales.api_token = '';
        },

        async abrirModalToken() {
            await this.obtenerTokenActual();
            const modalEl = document.getElementById('modalToken103');
            if (modalEl && window.bootstrap) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        },

        async guardarCredencialesToken() {
            if (!(this.credenciales.api_token || '').trim()) {
                this.mostrarMensaje('Debés ingresar el token', 'warning');
                return;
            }

            const confirmacion = await this.mostrarConfirmacion(
                '¿Guardar el token del sistema 103?',
                'Guardar token'
            );
            if (!confirmacion) {
                return;
            }

            try {
                const url = BASE_URL + 'api/token103';
                const payload = { api_token: this.credenciales.api_token.trim() };
                if (this.tokenActual && this.tokenActual.id) {
                    await axios.put(url + '/' + this.tokenActual.id, payload);
                } else {
                    await axios.post(url, payload);
                }

                await this.obtenerTokenActual();
                this.mostrarMensaje('Token guardado correctamente', 'success');
            } catch (error) {
                console.error('Error al guardar token:', error);
                this.mostrarMensaje('Error al guardar el token', 'error');
            }
        },

        /**
         * Sincroniza reclamos desde el último guardado hasta hoy
         */
        async sincronizarReclamosHoy() {
            if (!this.tokenDisponible || !this.tokenActual) {
                this.mostrarMensaje('Token no disponible: Debe configurar un token válido para sincronizar', 'warning');
                return;
            }

            // Confirmación antes de sincronizar
            const mensajeConfirmacion = `¿Está seguro que desea sincronizar todos los reclamos pendientes desde el último hasta hoy?`;
            
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Sincronizar Reclamos Pendientes');
            
            if (!confirmacion) {
                return;
            }

            try {
                // Obtener reclamos del backend (sin guardarlos aún)
                const response = await axios.get(BASE_URL + 'api/sincronizacion/reclamos/pendientes');

                console.log('Respuesta del backend:', response.data);
                this.logRespuesta103Cruda(response.data);

                if (!response.data.success) {
                    throw new Error('Error en la respuesta del servidor');
                }

                const resultado = response.data;
                const reclamosParaProcesar = resultado.reclamos || [];

                // Mostrar si hay reclamos omitidos (ya existentes)
                if (resultado.reclamos_omitidos > 0) {
                    console.log(`Se omitieron ${resultado.reclamos_omitidos} reclamos que ya existen en la base de datos (ID <= ${resultado.ultimo_id_guardado})`);
                }
                if (resultado.reclamos_invalidos > 0) {
                    console.log(`Se omitieron ${resultado.reclamos_invalidos} reclamos con estado "Inválido (N/A)"`);
                }

                if (reclamosParaProcesar.length === 0) {
                    let mensaje = 'No hay reclamos nuevos para sincronizar';
                    if (resultado.reclamos_omitidos > 0) {
                        mensaje += `<br><small>Se encontraron ${resultado.reclamos_omitidos} reclamos, pero ya están guardados en la base de datos.</small>`;
                    }
                    if (resultado.reclamos_invalidos > 0) {
                        mensaje += `<br><small>Se omitieron ${resultado.reclamos_invalidos} reclamos con estado "Inválido (N/A)".</small>`;
                    }
                    this.mostrarMensaje(mensaje, 'info');
                    return;
                }

                // Iniciar procesamiento progresivo
                await this.procesarReclamosProgresivamente(reclamosParaProcesar, {
                    fecha_desde: resultado.fecha_desde,
                    fecha_hasta: resultado.fecha_hasta,
                    total_recibidos: resultado.total_recibidos,
                    total_alumbrado: resultado.total_alumbrado,
                    reclamos_omitidos: resultado.reclamos_omitidos || 0,
                    reclamos_invalidos: resultado.reclamos_invalidos || 0
                });

            } catch (error) {
                console.error('Error al sincronizar reclamos pendientes:', error);
                this.mostrarMensaje('Error en sincronización: No se pudieron sincronizar los reclamos pendientes. Verifique el token y la conexión.', 'error');
            }
        },

        /**
         * Parte un rango [desde, hasta] en tramos de N días (inclusive).
         * Evita timeouts del backend al sincronizar períodos largos.
         */
        partirRangoFechasSync(fechaDesde, fechaHasta, diasPorTramo = 14) {
            const parseLocal = (s) => {
                const [y, m, d] = String(s).split('-').map(Number);
                return new Date(y, m - 1, d);
            };
            const fmt = (date) => {
                const y = date.getFullYear();
                const m = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            };

            const inicio = parseLocal(fechaDesde);
            const fin = parseLocal(fechaHasta);
            if (Number.isNaN(inicio.getTime()) || Number.isNaN(fin.getTime()) || fin < inicio) {
                return [];
            }

            const tramos = [];
            let cursor = new Date(inicio);
            while (cursor <= fin) {
                const desde = new Date(cursor);
                const hasta = new Date(cursor);
                hasta.setDate(hasta.getDate() + (diasPorTramo - 1));
                if (hasta > fin) {
                    hasta.setTime(fin.getTime());
                }
                tramos.push({ desde: fmt(desde), hasta: fmt(hasta) });
                cursor = new Date(hasta);
                cursor.setDate(cursor.getDate() + 1);
            }
            return tramos;
        },

        /**
         * Sincroniza reclamos por rango de fechas (en tramos para rangos largos)
         */
        async sincronizarReclamosPorFechas() {
            if (!this.tokenDisponible || !this.tokenActual) {
                this.mostrarMensaje('Token no disponible: Debe configurar un token válido para sincronizar', 'warning');
                return;
            }

            if (!this.syncFechaDesde || !this.syncFechaHasta) {
                this.mostrarMensaje('Fechas requeridas: Debe seleccionar un rango de fechas', 'warning');
                return;
            }

            if (this.syncFechaDesde > this.syncFechaHasta) {
                this.mostrarMensaje('El rango de fechas no es válido (desde > hasta).', 'warning');
                return;
            }

            const mensajeConfirmacion = `¿Está seguro que desea sincronizar los reclamos del sistema 103?`;
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Sincronizar Reclamos por Fechas');
            if (!confirmacion) {
                return;
            }

            const tramos = this.partirRangoFechasSync(this.syncFechaDesde, this.syncFechaHasta, 14);
            if (!tramos.length) {
                this.mostrarMensaje('No se pudo armar el rango de fechas.', 'warning');
                return;
            }

            this.sincronizando = true;
            this.detenerSincronizacion = false;
            this.syncFase = 'descarga';
            this.syncTramoActual = 0;
            this.syncTramosTotal = tramos.length;
            this.syncDetalle = '';
            this.progresoTotal = 0;
            this.progresoActual = 0;

            try {
                const reclamosPorId = new Map();
                let totalRecibidos = 0;
                let totalAlumbrado = 0;
                let reclamosOmitidos = 0;
                let reclamosInvalidos = 0;
                let tramosOk = 0;

                for (let i = 0; i < tramos.length; i++) {
                    if (this.detenerSincronizacion) {
                        break;
                    }

                    const tramo = tramos[i];
                    this.syncTramoActual = i + 1;
                    this.syncDetalle = `${tramo.desde} → ${tramo.hasta}`;

                    try {
                        const response = await axios.get(BASE_URL + 'api/sincronizacion/reclamos', {
                            params: {
                                fecha_desde: tramo.desde,
                                fecha_hasta: tramo.hasta
                            },
                            timeout: 120000
                        });

                        console.log(`Respuesta tramo ${i + 1}/${tramos.length}:`, response.data);
                        this.logRespuesta103Cruda(response.data);

                        if (!response.data?.success) {
                            throw new Error(`El tramo ${tramo.desde} → ${tramo.hasta} no devolvió success`);
                        }

                        const resultado = response.data;
                        totalRecibidos += Number(resultado.total_recibidos) || 0;
                        totalAlumbrado += Number(resultado.total_alumbrado) || 0;
                        reclamosOmitidos += Number(resultado.reclamos_omitidos) || 0;
                        reclamosInvalidos += Number(resultado.reclamos_invalidos) || 0;

                        (resultado.reclamos || []).forEach((r) => {
                            const id = r?.municipalidad_id ?? r?.id;
                            if (id == null) return;
                            reclamosPorId.set(String(id), r);
                        });

                        tramosOk++;
                    } catch (errorTramo) {
                        console.error(`Error en tramo ${tramo.desde} → ${tramo.hasta}:`, errorTramo);
                        const status = errorTramo?.response?.status;
                        const detalle = errorTramo?.response?.data?.messages?.error
                            || errorTramo?.response?.data?.message
                            || errorTramo?.message
                            || 'Error desconocido';
                        this.mostrarMensaje(
                            `Falló el tramo ${i + 1}/${tramos.length} (${tramo.desde} → ${tramo.hasta}).`
                            + (status ? ` HTTP ${status}.` : '')
                            + `<br><small>${detalle}</small>`
                            + (tramosOk > 0 ? '<br><small>Se continuará con los tramos ya descargados.</small>' : ''),
                            'warning'
                        );
                        if (tramosOk === 0 && i === 0) {
                            throw errorTramo;
                        }
                    }
                }

                const reclamosParaProcesar = Array.from(reclamosPorId.values());

                if (reclamosOmitidos > 0) {
                    console.log(`Se omitieron ${reclamosOmitidos} reclamos que ya existen en la base de datos`);
                }
                if (reclamosInvalidos > 0) {
                    console.log(`Se omitieron ${reclamosInvalidos} reclamos con estado "Inválido (N/A)"`);
                }

                if (reclamosParaProcesar.length === 0) {
                    this.resetEstadoSyncUI();
                    let mensaje = 'No hay reclamos nuevos en el rango de fechas seleccionado';
                    if (reclamosOmitidos > 0) {
                        mensaje += `<br><small>Se encontraron ${reclamosOmitidos} reclamos, pero ya estaban guardados.</small>`;
                    }
                    if (reclamosInvalidos > 0) {
                        mensaje += `<br><small>Se omitieron ${reclamosInvalidos} reclamos con estado "Inválido (N/A)".</small>`;
                    }
                    if (this.detenerSincronizacion) {
                        mensaje = 'Descarga detenida. No quedaron reclamos nuevos para procesar.';
                    }
                    this.mostrarMensaje(mensaje, 'info');
                    return;
                }

                await this.procesarReclamosProgresivamente(reclamosParaProcesar, {
                    fecha_desde: this.syncFechaDesde,
                    fecha_hasta: this.syncFechaHasta,
                    total_recibidos: totalRecibidos,
                    total_alumbrado: totalAlumbrado,
                    reclamos_omitidos: reclamosOmitidos,
                    reclamos_invalidos: reclamosInvalidos,
                    tramos_descargados: tramosOk,
                    tramos_totales: tramos.length
                });

            } catch (error) {
                console.error('Error al sincronizar reclamos:', error);
                this.resetEstadoSyncUI();
                const status = error?.response?.status;
                const detalle = error?.response?.data?.messages?.error
                    || error?.response?.data?.message
                    || '';
                let mensaje = 'Error en sincronización: No se pudieron sincronizar los reclamos.';
                if (status === 500) {
                    mensaje += ' El servidor agotó el tiempo al consultar el 103 (rango demasiado grande o API lenta).';
                } else {
                    mensaje += ' Verifique el token y la conexión.';
                }
                if (detalle) {
                    mensaje += `<br><small>${detalle}</small>`;
                }
                this.mostrarMensaje(mensaje, 'error');
            }
        },

        /**
         * Sincroniza un reclamo específico por número
         */
        async sincronizarReclamoEspecifico() {
            if (!this.tokenDisponible || !this.tokenActual) {
                this.mostrarMensaje('Token no disponible: Debe configurar un token válido para sincronizar', 'warning');
                return;
            }

            if (!this.numeroReclamo) {
                this.mostrarMensaje('Número de reclamo requerido: Debe ingresar un número de reclamo', 'warning');
                return;
            }

            // Confirmación antes de sincronizar
            const mensajeConfirmacion = `¿Está seguro que desea sincronizar el reclamo específico?`;
            
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Sincronizar Reclamo Específico');
            
            if (!confirmacion) {
                return;
            }

            // Mensaje de progreso
            this.mostrarMensaje('Buscando reclamo', 'info');

            try {
                // Llamar al proxy del backend que maneja la autenticación y guarda en BD
                const response = await axios.get(BASE_URL + `api/sincronizacion/reclamos/${this.numeroReclamo}`);

                console.log('Respuesta del backend:', response.data);
                this.logRespuesta103Cruda(response.data);

                if (!response.data.success) {
                    throw new Error('Error en la respuesta del servidor');
                }

                // El backend ya guardó el reclamo
                const resultado = response.data;

                if (resultado.accion === 'omitido' && resultado.motivo === 'excluido_local') {
                    this.mostrarMensaje(
                        `El reclamo #${this.numeroReclamo} está excluido localmente y no se sincroniza.`,
                        'warning'
                    );
                    this.numeroReclamo = '';
                    return;
                }

                // Mensaje de éxito con detalles
                const accionTexto = resultado.accion === 'creado' ? 'Nuevo reclamo creado' : 'Reclamo actualizado';
                const mensajeExito = `Reclamo sincronizado exitosamente<br>
                    <strong>Número:</strong> ${this.numeroReclamo}<br>
                    <strong>Estado:</strong> ${accionTexto}<br>
                    <strong>Motivo:</strong> ${resultado.reclamo.municipalidad_motivo}`;

                this.mostrarMensaje(mensajeExito, 'success');
                this.numeroReclamo = '';
                
                // Recargar la tabla de reclamos
                this.obtenerReclamos();

            } catch (error) {
                console.error('Error al sincronizar reclamo:', error);
                const mensajeApi = error.response?.data?.messages?.error
                    || error.response?.data?.message
                    || error.response?.data?.messages;
                const mensajeError = (typeof mensajeApi === 'string' && mensajeApi.includes('Inválido (N/A)'))
                    ? mensajeApi
                    : 'Error en sincronización: No se pudo sincronizar el reclamo. Verifique el número y la conexión.';
                this.mostrarMensaje(mensajeError, 'error');
            }
        },


        /**
         * Procesa reclamos uno por uno mostrando el progreso
         */
        async procesarReclamosProgresivamente(reclamos, metadatos) {
            this.sincronizando = true;
            this.detenerSincronizacion = false;
            this.syncFase = 'procesando';
            this.syncDetalle = 'Guardando y geocodificando en el sistema local';
            this.progresoTotal = reclamos.length;
            this.progresoActual = 0;
            
            let nuevos = 0;
            let actualizados = 0;
            let omitidos = 0;
            let errores = 0;
            let detenidoPorUsuario = false;

            for (const reclamo of reclamos) {
                try {
                    // Procesar un reclamo (guardar + geocodificar)
                    const response = await axios.post(BASE_URL + 'api/sincronizacion/reclamos/procesar-uno', reclamo);
                    
                    if (response.data.success) {
                        if (response.data.accion === 'creado') {
                            nuevos++;
                        } else if (response.data.accion === 'actualizado') {
                            actualizados++;
                        } else if (response.data.accion === 'omitido') {
                            omitidos++;
                        }
                    }

                    // Actualizar progreso
                    this.progresoActual++;

                    // Actualizar tabla después de cada 5 reclamos o al final
                    if (this.progresoActual % 5 === 0 || this.progresoActual === this.progresoTotal) {
                        await this.obtenerReclamos();
                    }

                } catch (error) {
                    console.error('Error al procesar reclamo:', error);
                    errores++;
                    this.progresoActual++;
                }

                // Verificar si el usuario pidió detener (DESPUÉS de completar el reclamo)
                if (this.detenerSincronizacion) {
                    detenidoPorUsuario = true;
                    console.log('Sincronización detenida por el usuario en el reclamo', this.progresoActual);
                    break;
                }
            }

            // Finalizar
            this.resetEstadoSyncUI();

            // Mensaje final
            let mensajeFinal = '';
            
            if (detenidoPorUsuario) {
                mensajeFinal = `<i class="bi bi-pause-circle"></i> Sincronización detenida por el usuario<br>`;
            } else {
                mensajeFinal = `<i class="bi bi-check-circle"></i> Sincronización completada exitosamente<br>`;
            }
            
            if (metadatos.fecha_desde && metadatos.fecha_hasta) {
                mensajeFinal += `<strong>Rango:</strong> ${metadatos.fecha_desde} → ${metadatos.fecha_hasta}<br>`;
            }
            
            // Mostrar solo los números relevantes (Alumbrado Público)
            mensajeFinal += `<strong>Reclamos de Alumbrado Público:</strong> ${metadatos.total_alumbrado}<br>
                <strong>Procesados:</strong> ${this.progresoActual} de ${this.progresoTotal}<br>
                <strong>Nuevos:</strong> ${nuevos}<br>
                <strong>Actualizados:</strong> ${actualizados}`;
            
            if (omitidos > 0) {
                mensajeFinal += `<br><strong class="text-muted">Omitidos (excluidos locales):</strong> ${omitidos}`;
            }

            if (metadatos.reclamos_omitidos > 0) {
                mensajeFinal += `<br><strong class="text-muted">Omitidos (ya existentes):</strong> ${metadatos.reclamos_omitidos}`;
            }

            if (metadatos.reclamos_invalidos > 0) {
                mensajeFinal += `<br><strong class="text-muted">Omitidos (Inválido N/A):</strong> ${metadatos.reclamos_invalidos}`;
            }
            
            if (errores > 0) {
                mensajeFinal += `<br><strong class="text-danger">Errores:</strong> ${errores}`;
            }

            if (detenidoPorUsuario) {
                mensajeFinal += `<br><small class="text-muted">Los reclamos restantes (${this.progresoTotal - this.progresoActual}) no fueron procesados.</small>`;
            }
            
            // Nota informativa sobre otros tipos (si hay diferencia significativa)
            if (metadatos.total_recibidos && metadatos.total_recibidos > metadatos.total_alumbrado) {
                const otrosTipos = metadatos.total_recibidos - metadatos.total_alumbrado;
                mensajeFinal += `<br><small class="text-muted"><i class="bi bi-info-circle"></i> Se recibieron ${metadatos.total_recibidos} reclamos en total (${otrosTipos} de otros tipos fueron filtrados)</small>`;
            }

            this.mostrarMensaje(mensajeFinal, detenidoPorUsuario ? 'warning' : 'success');

            // Recargar tabla final
            await this.obtenerReclamos();
        },

        resetEstadoSyncUI() {
            this.sincronizando = false;
            this.detenerSincronizacion = false;
            this.syncFase = '';
            this.syncTramoActual = 0;
            this.syncTramosTotal = 0;
            this.syncDetalle = '';
        },

        /**
         * Detiene la sincronización en curso
         */
        detenerSincronizacionEnCurso() {
            if (this.sincronizando) {
                this.detenerSincronizacion = true;
                if (this.syncFase === 'descarga') {
                    this.mostrarMensaje('Deteniendo… Se termina el tramo actual y no se descargan más.', 'info');
                } else {
                    this.mostrarMensaje('Deteniendo sincronización… Se completa el reclamo actual.', 'info');
                }
            }
        },

        onBeforeUnloadSync(event) {
            if (!this.sincronizando) {
                return;
            }
            event.preventDefault();
            event.returnValue = '';
        },

        /**
         * Procesa y guarda un reclamo del sistema externo
         */
        async procesarYGuardarReclamo(reclamoExterno) {
            // Mapear campos del sistema externo a nuestra base de datos
            const estado = reclamoExterno.estado || 'Recibido';
            const fechaInicio = this.convertirFechaExterna(reclamoExterno.fecha_inicio);
            const fechaModificacion = this.convertirFechaExterna(reclamoExterno.fecha_modificacion);
            const reclamoMapeado = {
                municipalidad_id: reclamoExterno.nro_reclamo.toString(),
                municipalidad_tipo: reclamoExterno.tipo || 'ALUMBRADO PÚBLICO',
                municipalidad_motivo: reclamoExterno.motivo?.nombre || 'No especificado',
                municipalidad_fechaInicio: fechaInicio,
                municipalidad_fechaModificacion: fechaModificacion,
                municipalidad_recepcion: reclamoExterno.recepcion || 'No especificado',
                municipalidad_estado: estado,
                prioridad: reclamoExterno.prioridad || 'Baja', // Asignar prioridad, ahora simplemente 'prioridad'
                municipalidad_telefono: reclamoExterno.telefono || '',
                municipalidad_domicilio: reclamoExterno.domicilio || '',
                municipalidad_numeroDomicilio: reclamoExterno.numero_domicilio || '',
                municipalidad_entreCalleUno: reclamoExterno.entre_calle_uno || '',
                municipalidad_entreCalleDos: reclamoExterno.entre_calle_dos || '',
                municipalidad_ciudadano: reclamoExterno.ciudadano || '',
                municipalidad_descripcion: reclamoExterno.descripcion || ''
            };

            // En el 103, Completado implica cierre formal
            if (estado === 'Completado') {
                reclamoMapeado.cerrado = 1;
                reclamoMapeado.fecha_cierre = fechaModificacion || fechaInicio || null;
            }

            const reclamoExistente = this.reclamos.find(r => r.municipalidad_id === reclamoMapeado.municipalidad_id);

            if (reclamoExistente) {
                await axios.put(BASE_URL + 'api/reclamos/' + reclamoExistente.id, reclamoMapeado);
                console.log(`Reclamo ${reclamoMapeado.municipalidad_id} actualizado`);
                return 'actualizado';
            } else {
                await axios.post(BASE_URL + 'api/reclamos', reclamoMapeado);
                console.log(`Reclamo ${reclamoMapeado.municipalidad_id} creado`);
                return 'creado';
            }
        },

        /**
         * Convierte fecha del formato externo al formato de nuestra base de datos
         */
        convertirFechaExterna(fechaExterna) {
            if (!fechaExterna) return '';

            try {
                const date = new Date(fechaExterna);
                return date.toISOString().slice(0, 19).replace('T', ' ');
            } catch (error) {
                console.error('Error al convertir fecha externa:', error);
                return '';
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
                <div class="alert ${alertClass} alert-dismissible fade show mensaje-notificacion" role="alert">
                    <div class="mensaje-notificacion__body">${mensaje}</div>
                    <button type="button" class="btn-close mensaje-notificacion__close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            `;
            
            $('body').append(alertHtml);
            
            // Auto-remover después de 5 segundos - solo los mensajes de notificación flotantes
            setTimeout(() => {
                $('.mensaje-notificacion').fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Espera a que Google Places esté disponible (script async en footer).
         */
        async esperarGooglePlaces(timeoutMs = 12000) {
            const inicio = Date.now();
            while (!(window.google && google.maps && google.maps.places)) {
                if (Date.now() - inicio > timeoutMs) {
                    return false;
                }
                await new Promise((resolve) => setTimeout(resolve, 100));
            }
            return true;
        },

        /**
         * Autocomplete de calles/direcciones de San Francisco, Córdoba (Google Places).
         */
        async inicializarAutocompleteDomicilio() {
            if (this.domicilioAutocompleteListo) {
                return;
            }

            const ok = await this.esperarGooglePlaces();
            if (!ok) {
                console.warn('Google Places no disponible; el domicilio queda manual.');
                return;
            }

            const input = document.getElementById('inputDomicilioReclamo');
            if (!input) {
                return;
            }

            // Área aprox. de San Francisco, Córdoba
            const bounds = new google.maps.LatLngBounds(
                { lat: -31.52, lng: -62.20 },
                { lat: -31.35, lng: -61.98 }
            );

            this.domicilioAutocomplete = new google.maps.places.Autocomplete(input, {
                bounds,
                strictBounds: false,
                componentRestrictions: { country: 'ar' },
                fields: ['address_components', 'formatted_address', 'geometry', 'name'],
                types: ['address'],
            });
            this.domicilioAutocomplete.setBounds(bounds);

            this.domicilioAutocomplete.addListener('place_changed', () => {
                this.aplicarLugarAutocomplete();
            });

            this.domicilioAutocompleteListo = true;
        },

        /**
         * Completa domicilio y número desde la sugerencia elegida.
         */
        aplicarLugarAutocomplete() {
            if (!this.domicilioAutocomplete) {
                return;
            }

            const place = this.domicilioAutocomplete.getPlace();
            if (!place || !place.address_components) {
                return;
            }

            let ruta = '';
            let numero = '';
            let localidad = '';

            place.address_components.forEach((comp) => {
                const tipos = comp.types || [];
                if (tipos.includes('route')) {
                    ruta = comp.long_name;
                }
                if (tipos.includes('street_number')) {
                    numero = comp.long_name;
                }
                if (tipos.includes('locality') || tipos.includes('administrative_area_level_2')) {
                    localidad = comp.long_name;
                }
            });

            // Preferir el nombre de calle; si no viene, usar el name del place
            const domicilio = (ruta || place.name || '').trim();
            if (domicilio) {
                this.reclamo.municipalidad_domicilio = domicilio;
            }
            if (numero) {
                this.reclamo.municipalidad_numeroDomicilio = numero;
            }

            // Aviso suave si la sugerencia no parece de San Francisco
            const loc = (localidad || '').toLowerCase();
            if (loc && !loc.includes('san francisco') && !loc.includes('francisco')) {
                console.warn('Sugerencia fuera de San Francisco:', place.formatted_address);
            }
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
                            <div class="modal-content reclamo-modal reclamo-confirm-modal">
                                <div class="reclamo-modal__header">
                                    <div class="reclamo-modal__title">
                                        <span class="reclamo-modal__icon"><i class="bi bi-question-circle"></i></span>
                                        <h5>${titulo}</h5>
                                    </div>
                                    <button type="button" class="reclamo-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p class="reclamo-confirm-modal__message">${mensaje}</p>
                                </div>
                                <div class="reclamo-modal__footer reclamo-modal__footer--end">
                                    <button type="button" class="reclamos-btn reclamos-btn--outline" data-bs-dismiss="modal" id="btnCancelar">
                                        Cancelar
                                    </button>
                                    <button type="button" class="reclamos-btn" id="btnConfirmar">
                                        <i class="bi bi-check-lg"></i> Confirmar
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
        }
    },

    mounted() {
        this.obtenerReclamos();
        this.obtenerTokenActual();

        const hoy = new Date();
        const haceUnMes = new Date(hoy.getFullYear(), hoy.getMonth() - 1, hoy.getDate());
        this.syncFechaDesde = haceUnMes.toISOString().split('T')[0];
        this.syncFechaHasta = hoy.toISOString().split('T')[0];

        this._onBeforeUnloadSync = (event) => this.onBeforeUnloadSync(event);
        window.addEventListener('beforeunload', this._onBeforeUnloadSync);

        const modalReclamo = document.getElementById('modalReclamo');
        if (modalReclamo) {
            modalReclamo.addEventListener('shown.bs.modal', () => {
                this.inicializarAutocompleteDomicilio();
            });
        }
    },

    beforeUnmount() {
        if (this._onBeforeUnloadSync) {
            window.removeEventListener('beforeunload', this._onBeforeUnloadSync);
        }
    },
});

