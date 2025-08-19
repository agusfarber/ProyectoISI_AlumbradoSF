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
            // Variables para sincronización
            tokenDisponible: false,
            tokenActual: null,
            syncFechaDesde: '',
            syncFechaHasta: '',
            numeroReclamo: '',
            // URL de la API externa
            apiUrl: 'https://0d681142-41d3-4c17-a854-13e8da718ead.mock.pstmn.io'
        };
    },

    methods: {
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
            console.log('Creando nueva tabla con datos:', this.reclamos);
            this.tabla = $('#tabla_reclamos').DataTable({
                data: this.reclamos,
                responsive: true,
                

                columns: [
                    { data: 'municipalidad_id' },
                    { data: 'municipalidad_motivo' },
                    {
                        data: 'municipalidad_fechaInicio',
                        render: (data) => this.formatearFecha(data)
                    },
                    {
                        data: 'municipalidad_fechaModificacion',
                        render: (data) => this.formatearFecha(data)
                    },
                    { data: 'municipalidad_recepcion' },
                    { data: 'municipalidad_estado' },
                    { data: 'prioridad' }, // Columna de prioridad, ahora simplemente 'prioridad'
                    { data: 'municipalidad_domicilio' },
                    { data: 'municipalidad_numeroDomicilio' },
                    {
                        data: null,
                        render: (data, type, row) => {
                            return `
                                <button class="btn btn-sm btn-info me-1 ver-reclamo" data-id="${row.id}" title="Ver detalles">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning me-1 editar-reclamo" data-id="${row.id}" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger eliminar-reclamo" data-id="${row.id}" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }
                    }
                ],
                // Ordenamiento inicial por 'Fecha de Inicio' (columna 2) en orden descendente
                order: [[2, 'desc']]
            });

            $('#tabla_reclamos tbody').off('click', '.ver-reclamo').on('click', '.ver-reclamo', (e) => {
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
                        const rawFechaInicioStr = data[2]; // Columna de Fecha de Inicio
                        const fechaInicioTabla = new Date(rawFechaInicioStr.replace(' ', 'T'));
                        if (isNaN(fechaInicioTabla.getTime())) {
                            return false;
                        }
                        const pasaFechaDesde = !fechaDesde || fechaInicioTabla.getTime() >= fechaDesde.getTime();
                        const pasaFechaHasta = !fechaHasta || fechaInicioTabla.getTime() <= fechaHasta.getTime();
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
                municipalidad_estado: '',
                prioridad: 'Baja', // Valor por defecto para nuevos reclamos, ahora 'prioridad'
                municipalidad_telefono: '',
                municipalidad_domicilio: '',
                municipalidad_numeroDomicilio: '',
                municipalidad_entreCalleUno: '',
                municipalidad_entreCalleDos: '',
                municipalidad_ciudadano: '',
                municipalidad_descripcion: ''
            };
            new bootstrap.Modal(document.getElementById('modalReclamo')).show();
        },

        /**
         * Carga los datos de un reclamo existente en el modal de edición.
         * @param {Object} reclamo El objeto reclamo a editar.
         */
        editarReclamo(reclamo) {
            const reclamoEditado = { ...reclamo };
            if (reclamoEditado.municipalidad_fechaInicio) {
                reclamoEditado.municipalidad_fechaInicio = this.formatearFechaParaInput(reclamoEditado.municipalidad_fechaInicio);
            }
            if (reclamoEditado.municipalidad_fechaModificacion) {
                reclamoEditado.municipalidad_fechaModificacion = this.formatearFechaParaInput(reclamoEditado.municipalidad_fechaModificacion);
            }

            this.reclamo = reclamoEditado;
            new bootstrap.Modal(document.getElementById('modalReclamo')).show();
        },

        /**
         * Muestra los detalles de un reclamo en un modal de solo lectura.
         * @param {Object} reclamo El objeto reclamo a visualizar.
         */
        verReclamo(reclamo) {
            this.reclamoSeleccionado = { ...reclamo };
            new bootstrap.Modal(document.getElementById('modalVerReclamo')).show();
        },

        /**
         * Guarda (crea o actualiza) un reclamo enviando los datos a la API.
         */
        guardarReclamo() {
            const esNuevo = !this.reclamo.id;
            const url = BASE_URL + 'api/reclamos' + (esNuevo ? '' : '/' + this.reclamo.id);
            const metodo = esNuevo ? 'post' : 'put';

            const datosParaEnviar = { ...this.reclamo };
            if (datosParaEnviar.municipalidad_fechaInicio) {
                datosParaEnviar.municipalidad_fechaInicio = this.convertirFechaParaBD(datosParaEnviar.municipalidad_fechaInicio);
            }
            if (datosParaEnviar.municipalidad_fechaModificacion) {
                datosParaEnviar.municipalidad_fechaModificacion = this.convertirFechaParaBD(datosParaEnviar.municipalidad_fechaModificacion);
            }

            axios[metodo](url, datosParaEnviar).then(() => {
                this.obtenerReclamos();
                bootstrap.Modal.getInstance(document.getElementById('modalReclamo')).hide();
            }).catch(error => {
                console.error('Error al guardar reclamo:', error);
                alert('Error al guardar el reclamo');
            });
        },

        /**
         * Elimina un reclamo de la base de datos.
         * @param {Object} reclamo El objeto reclamo a eliminar.
         */
        eliminarReclamo(reclamo) {
            if (confirm(`¿Seguro que deseas eliminar el reclamo ${reclamo.municipalidad_id}?`)) {
                axios.delete(BASE_URL + 'api/reclamos/' + reclamo.id).then(() => {
                    this.obtenerReclamos();
                }).catch(error => {
                    console.error('Error al eliminar reclamo:', error);
                    alert('Error al eliminar el reclamo');
                });
            }
        },

        /**
         * Obtiene la fecha y hora actual en la zona horaria de Argentina
         * y la formatea para un input de tipo datetime-local (YYYY-MM-DDTHH:MM).
         * @returns {string} La fecha y hora actual formateada.
         */
        obtenerFechaActualArgentina() {
            const ahora = new Date();
            const offset = ahora.getTimezoneOffset() + (3 * 60);
            const fechaArgentina = new Date(ahora.getTime() - offset * 60 * 1000);
            return fechaArgentina.toISOString().slice(0, 16);
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
         * Obtiene el token actual para la sincronización
         */
        async obtenerTokenActual() {
            try {
                const response = await axios.get(BASE_URL + 'api/token103');
                if (response.data && response.data.length > 0) {
                    const ultimoToken = response.data[response.data.length - 1];
                    if (ultimoToken.access_token) {
                        this.tokenActual = ultimoToken;
                        this.tokenDisponible = true;
                    } else {
                        this.tokenDisponible = false;
                    }
                } else {
                    this.tokenDisponible = false;
                }
            } catch (error) {
                console.error('Error al obtener token:', error);
                this.tokenDisponible = false;
            }
        },

        /**
         * Sincroniza reclamos por rango de fechas
         */
        async sincronizarReclamosPorFechas() {
            if (!this.tokenDisponible || !this.tokenActual) {
                alert('Token no disponible: Debe configurar un token válido para sincronizar');
                return;
            }

            if (!this.syncFechaDesde || !this.syncFechaHasta) {
                alert('Fechas requeridas: Debe seleccionar un rango de fechas');
                return;
            }

            // Mensaje para el usuario mientras se sincronizan los reclamos
            alert('Sincronizando reclamos... Por favor espere.');

            try {
                const response = await axios.get(this.apiUrl + '/recibirReclamos', {
                    params: {
                        fecha_desde: this.syncFechaDesde,
                        fecha_hasta: this.syncFechaHasta
                    },
                    headers: {
                        'Authorization': `Bearer ${this.tokenActual.access_token}`
                    }
                });

                console.log('Respuesta de la API externa:', response.data);

                const reclamosRecibidos = response.data;
                let reclamosGuardados = 0;
                let reclamosActualizados = 0;

                for (const reclamoExterno of reclamosRecibidos) {
                    try {
                        const resultado = await this.procesarYGuardarReclamo(reclamoExterno);
                        if (resultado === 'creado') {
                            reclamosGuardados++;
                        } else if (resultado === 'actualizado') {
                            reclamosActualizados++;
                        }
                    } catch (error) {
                        console.error('Error al procesar reclamo:', error);
                    }
                }

                // Mensaje de éxito con resumen
                alert(`Sincronización completada:
                    Se procesaron ${reclamosRecibidos.length} reclamos:
                    - Nuevos: ${reclamosGuardados}
                    - Actualizados: ${reclamosActualizados}`);

                this.obtenerReclamos();

            } catch (error) {
                console.error('Error al sincronizar reclamos:', error);
                // Mensaje de error
                alert('Error en sincronización: No se pudieron sincronizar los reclamos. Verifique el token y la conexión.');
            }
        },

        /**
         * Sincroniza un reclamo específico por número
         */
        async sincronizarReclamoEspecifico() {
            if (!this.tokenDisponible || !this.tokenActual) {
                alert('Token no disponible: Debe configurar un token válido para sincronizar');
                return;
            }

            if (!this.numeroReclamo) {
                alert('Número de reclamo requerido: Debe ingresar un número de reclamo');
                return;
            }

            // Mensaje para el usuario mientras se busca el reclamo
            alert('Buscando reclamo... Por favor espere.');

            try {
                const response = await axios.get(this.apiUrl + `/recibirReclamo/${this.numeroReclamo}`, {
                    headers: {
                        'Authorization': `Bearer ${this.tokenActual.access_token}`
                    }
                });

                console.log('Respuesta de la API externa:', response.data);

                await this.procesarYGuardarReclamo(response.data);

                // Mensaje de éxito
                alert(`Reclamo sincronizado: El reclamo ${this.numeroReclamo} se ha sincronizado correctamente.`);

                this.numeroReclamo = '';
                this.obtenerReclamos();

            } catch (error) {
                console.error('Error al sincronizar reclamo:', error);
                // Mensaje de error
                alert('Error en sincronización: No se pudo sincronizar el reclamo. Verifique el número y la conexión.');
            }
        },


        /**
         * Procesa y guarda un reclamo del sistema externo
         */
        async procesarYGuardarReclamo(reclamoExterno) {
            // Mapear campos del sistema externo a nuestra base de datos
            const reclamoMapeado = {
                municipalidad_id: reclamoExterno.nro_reclamo.toString(),
                municipalidad_tipo: reclamoExterno.tipo || 'ALUMBRADO PÚBLICO',
                municipalidad_motivo: reclamoExterno.motivo?.nombre || 'No especificado',
                municipalidad_fechaInicio: this.convertirFechaExterna(reclamoExterno.fecha_inicio),
                municipalidad_fechaModificacion: this.convertirFechaExterna(reclamoExterno.fecha_modificacion),
                municipalidad_recepcion: reclamoExterno.recepcion || 'No especificado',
                municipalidad_estado: reclamoExterno.estado || 'Recibido',
                prioridad: reclamoExterno.prioridad || 'Baja', // Asignar prioridad, ahora simplemente 'prioridad'
                municipalidad_telefono: reclamoExterno.telefono || '',
                municipalidad_domicilio: reclamoExterno.calle?.name || '',
                municipalidad_numeroDomicilio: reclamoExterno.numero_domicilio || '',
                municipalidad_entreCalleUno: reclamoExterno.entre_calle_uno || '',
                municipalidad_entreCalleDos: reclamoExterno.entre_calle_dos || '',
                municipalidad_ciudadano: reclamoExterno.ciudadano || '',
                municipalidad_descripcion: reclamoExterno.descripcion || ''
            };

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
        }
    },

    mounted() {
        this.obtenerReclamos();
        this.obtenerTokenActual();

        const hoy = new Date();
        const haceUnMes = new Date(hoy.getFullYear(), hoy.getMonth() - 1, hoy.getDate());
        this.syncFechaDesde = haceUnMes.toISOString().split('T')[0];
        this.syncFechaHasta = hoy.toISOString().split('T')[0];
    },
});


