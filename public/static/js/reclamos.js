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
            // Variables para progreso
            sincronizando: false,
            progresoActual: 0,
            progresoTotal: 0,
            detenerSincronizacion: false
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
                    {
                        data: 'municipalidad_id',
                        className: 'text-start',
                        render: (data, type, row) => {
                            return `<a href="#" class="ver-reclamo-id text-primary fw-bold" data-id="${row.id}" style="text-decoration: none; cursor: pointer;">${data}</a>`;
                        }
                    },
                    { 
                        data: 'municipalidad_motivo',
                        className: 'text-start'
                    },
                    {
                        data: 'municipalidad_fechaInicio',
                        className: 'text-start',
                        render: (data) => this.formatearFecha(data)
                    },
                    {
                        data: 'municipalidad_fechaModificacion',
                        className: 'text-start',
                        render: (data) => this.formatearFecha(data)
                    },
                    { 
                        data: 'municipalidad_estado',
                        className: 'text-start',
                        render: (data, type, row) => {
                            // Si el reclamo está cerrado, mostrar "Cerrado" en lugar de "Completado"
                            if (row.cerrado == 1 && data === 'Completado') {
                                return '<span class="badge bg-dark">Cerrado</span>';
                            }
                            // Colorear según estado
                            const estadoClass = {
                                'Recibido': 'bg-info',
                                'Asignado': 'bg-primary',
                                'En ejecución': 'bg-warning text-dark',
                                'Completado': 'bg-success',
                                'En plan': 'bg-secondary',
                                'Error de datos': 'bg-danger'
                            };
                            const badgeClass = estadoClass[data] || 'bg-secondary';
                            return `<span class="badge ${badgeClass}">${data}</span>`;
                        }
                    },
                    { 
                        data: 'municipalidad_domicilio',
                        className: 'text-start'
                    },
                    { 
                        data: 'municipalidad_numeroDomicilio',
                        className: 'text-start'
                    }
                    /*
                    ,
                    {
                        data: null,
                        className: 'text-start',
                        render: (data, type, row) => {
                            return `
                                <button class="btn btn-sm btn-warning me-1 editar-reclamo" data-id="${row.id}" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger eliminar-reclamo" data-id="${row.id}" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }
                    }
                    */
                ],
                // Ordenamiento inicial por 'Fecha de Inicio' (columna 2) en orden descendente
                order: [[2, 'desc']]
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
         * Verifica si hay credenciales guardadas para la sincronización
         */
        async obtenerTokenActual() {
            try {
                const response = await axios.get(BASE_URL + 'api/token103');
                if (response.data && response.data.length > 0) {
                    const ultimasCredenciales = response.data[response.data.length - 1];
                    if (ultimasCredenciales.username && ultimasCredenciales.password) {
                        this.tokenActual = ultimasCredenciales;
                        this.tokenDisponible = true;
                    } else {
                        this.tokenDisponible = false;
                    }
                } else {
                    this.tokenDisponible = false;
                }
            } catch (error) {
                console.error('Error al obtener credenciales:', error);
                this.tokenDisponible = false;
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

                if (!response.data.success) {
                    throw new Error('Error en la respuesta del servidor');
                }

                const resultado = response.data;
                const reclamosParaProcesar = resultado.reclamos || [];

                // Mostrar si hay reclamos omitidos (ya existentes)
                if (resultado.reclamos_omitidos > 0) {
                    console.log(`Se omitieron ${resultado.reclamos_omitidos} reclamos que ya existen en la base de datos (ID <= ${resultado.ultimo_id_guardado})`);
                }

                if (reclamosParaProcesar.length === 0) {
                    let mensaje = 'No hay reclamos nuevos para sincronizar';
                    if (resultado.reclamos_omitidos > 0) {
                        mensaje += `<br><small>Se encontraron ${resultado.reclamos_omitidos} reclamos, pero ya están guardados en la base de datos.</small>`;
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
                    reclamos_omitidos: resultado.reclamos_omitidos || 0
                });

            } catch (error) {
                console.error('Error al sincronizar reclamos pendientes:', error);
                this.mostrarMensaje('Error en sincronización: No se pudieron sincronizar los reclamos pendientes. Verifique el token y la conexión.', 'error');
            }
        },

        /**
         * Sincroniza reclamos por rango de fechas
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

            // Confirmación antes de sincronizar
            const mensajeConfirmacion = `¿Está seguro que desea sincronizar los reclamos del sistema 103?`;
            
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Sincronizar Reclamos por Fechas');
            
            if (!confirmacion) {
                return;
            }

            try {
                // Obtener reclamos del backend (sin guardarlos aún)
                const response = await axios.get(BASE_URL + 'api/sincronizacion/reclamos', {
                    params: {
                        fecha_desde: this.syncFechaDesde,
                        fecha_hasta: this.syncFechaHasta
                    }
                });

                console.log('Respuesta del backend:', response.data);

                if (!response.data.success) {
                    throw new Error('Error en la respuesta del servidor');
                }

                const resultado = response.data;
                const reclamosParaProcesar = resultado.reclamos || [];

                // Mostrar si hay reclamos omitidos (ya existentes)
                if (resultado.reclamos_omitidos > 0) {
                    console.log(`Se omitieron ${resultado.reclamos_omitidos} reclamos que ya existen en la base de datos (ID <= ${resultado.ultimo_id_guardado})`);
                }

                if (reclamosParaProcesar.length === 0) {
                    let mensaje = 'No hay reclamos nuevos en el rango de fechas seleccionado';
                    if (resultado.reclamos_omitidos > 0) {
                        mensaje += `<br><small>Se encontraron ${resultado.reclamos_omitidos} reclamos, pero ya están guardados en la base de datos.</small>`;
                    }
                    this.mostrarMensaje(mensaje, 'info');
                    return;
                }

                // Iniciar procesamiento progresivo
                await this.procesarReclamosProgresivamente(reclamosParaProcesar, {
                    total_recibidos: resultado.total_recibidos,
                    total_alumbrado: resultado.total_alumbrado,
                    reclamos_omitidos: resultado.reclamos_omitidos || 0
                });

            } catch (error) {
                console.error('Error al sincronizar reclamos:', error);
                this.mostrarMensaje('Error en sincronización: No se pudieron sincronizar los reclamos. Verifique el token y la conexión.', 'error');
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

                if (!response.data.success) {
                    throw new Error('Error en la respuesta del servidor');
                }

                // El backend ya guardó el reclamo
                const resultado = response.data;

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
                this.mostrarMensaje('Error en sincronización: No se pudo sincronizar el reclamo. Verifique el número y la conexión.', 'error');
            }
        },


        /**
         * Procesa reclamos uno por uno mostrando el progreso
         */
        async procesarReclamosProgresivamente(reclamos, metadatos) {
            this.sincronizando = true;
            this.detenerSincronizacion = false;
            this.progresoTotal = reclamos.length;
            this.progresoActual = 0;
            
            let nuevos = 0;
            let actualizados = 0;
            let errores = 0;
            let detenidoPorUsuario = false;

            for (const reclamo of reclamos) {
                try {
                    // Procesar un reclamo (guardar + geocodificar)
                    const response = await axios.post(BASE_URL + 'api/sincronizacion/reclamos/procesar-uno', reclamo);
                    
                    if (response.data.success) {
                        if (response.data.accion === 'creado') {
                            nuevos++;
                        } else {
                            actualizados++;
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
            this.sincronizando = false;
            this.detenerSincronizacion = false;

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
            
            if (metadatos.reclamos_omitidos > 0) {
                mensajeFinal += `<br><strong class="text-muted">Omitidos (ya existentes):</strong> ${metadatos.reclamos_omitidos}`;
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

        /**
         * Detiene la sincronización en curso
         */
        detenerSincronizacionEnCurso() {
            if (this.sincronizando) {
                this.detenerSincronizacion = true;
                this.mostrarMensaje('Deteniendo sincronización... Se completará el reclamo actual', 'info');
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
                municipalidad_domicilio: reclamoExterno.domicilio || '',
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


