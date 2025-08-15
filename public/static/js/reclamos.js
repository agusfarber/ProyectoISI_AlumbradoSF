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
            filtroBusqueda: ''
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
                // 'dom: "rtp"' elimina los elementos por defecto de DataTables (barra de búsqueda, info)
                // Usamos nuestros propios controles de filtro.
                dom: 'rtp', 
                columns: [
                    { data: 'municipalidad_id' },
                    { data: 'municipalidad_motivo' },
                    { 
                        data: 'municipalidad_fechaInicio', 
                        render: (data) => this.formatearFecha(data) // Formatear fecha para la visualización
                    },
                    { 
                        data: 'municipalidad_fechaModificacion',
                        render: (data) => this.formatearFecha(data) // Formatear fecha para la visualización
                    },
                    { data: 'municipalidad_recepcion' },
                    { data: 'municipalidad_estado' },
                    { data: 'municipalidad_domicilio' },
                    { data: 'municipalidad_numeroDomicilio' },
                    { 
                        // Columna de Acciones - Se genera HTML para los botones
                        data: null, // No se enlaza a un campo de datos específico
                        render: (data, type, row) => {
                            // `row` contiene el objeto de reclamo completo para la fila actual
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
                // Ordenamiento inicial por 'Fecha de Inicio' (columna 2, índice 0-based) en orden descendente
                order: [[2, 'desc']] 
            });

            // Re-vincular los eventos de los botones a los métodos de Vue
            // Es crucial hacer esto después de cada inicialización/redibujado de la tabla,
            // ya que DataTables reemplaza el tbody, eliminando los listeners anteriores.
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
         * Aplica los filtros de búsqueda global, estado y rango de fechas a la tabla.
         */
        aplicarFiltros() {
            if (!this.tabla) return;
            
            // Eliminar cualquier filtro de fecha personalizado anterior para evitar acumulaciones
            // Se usa un bucle porque pueden haber múltiples filtros personalizados en la pila
            while ($.fn.dataTable.ext.search.length > 0) {
                $.fn.dataTable.ext.search.pop();
            }

            // Lógica de filtro por rango de fechas (usando la columna 2: 'Fecha de Inicio')
            // Convertir las fechas de input (YYYY-MM-DD) a objetos Date que representen
            // el inicio y fin del día en la zona horaria local del usuario.
            let fechaDesde = null;
            if (this.filtroFechaDesde) {
                // Al concatenar 'T00:00:00', Date() intentará parsear esto como ISO 8601
                // usando la zona horaria local del navegador.
                fechaDesde = new Date(this.filtroFechaDesde + 'T00:00:00'); 
            }
            let fechaHasta = null;
            if (this.filtroFechaHasta) {
                // Para incluir todo el día, se establece hasta el último segundo.
                fechaHasta = new Date(this.filtroFechaHasta + 'T23:59:59'); 
            }

            if (fechaDesde || fechaHasta) {
                $.fn.dataTable.ext.search.push(
                    (settings, data, dataIndex) => {
                        const rawFechaInicioStr = data[2]; // e.g., "2024-08-15 10:30:00"
                        
                        // Parsear la fecha de la tabla como objeto Date.
                        // Reemplazar espacio por 'T' para asegurar un parsing ISO 8601,
                        // lo que interpreta la fecha en la zona horaria local del navegador.
                        const fechaInicioTabla = new Date(rawFechaInicioStr.replace(' ', 'T'));
                        
                        // Si el parseo falla (fecha inválida), no incluir la fila
                        if (isNaN(fechaInicioTabla.getTime())) {
                            return false;
                        }

                        // Comparar las marcas de tiempo (getTime() retorna milisegundos desde epoch)
                        const pasaFechaDesde = !fechaDesde || fechaInicioTabla.getTime() >= fechaDesde.getTime();
                        const pasaFechaHasta = !fechaHasta || fechaInicioTabla.getTime() <= fechaHasta.getTime();
                        
                        return pasaFechaDesde && pasaFechaHasta;
                    }
                );
            }

            // Aplicar búsqueda global. Esto filtra en todas las columnas visibles.
            // DataTables tiene una búsqueda global que se encadena con los filtros de columna/personalizados.
            this.tabla.search(this.filtroBusqueda);

            // Aplicar filtro por estado en la columna específica (índice 5 para 'Estado')
            // Se usa '^' + estadoFiltro + '$' y regex (true) para una coincidencia EXACTA de la celda.
            if (this.filtroEstado) {
                this.tabla.column(5).search('^' + this.filtroEstado + '$', true, false);
            } else {
                this.tabla.column(5).search(''); // Limpiar el filtro de estado si no hay selección
            }

            // Redibujar la tabla una sola vez después de aplicar todos los filtros
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

            // Eliminar todos los filtros de búsqueda personalizados
            while ($.fn.dataTable.ext.search.length > 0) {
                $.fn.dataTable.ext.search.pop();
            }

            if (this.tabla) {
                this.tabla.search(''); // Limpiar la búsqueda global
                this.tabla.columns().search(''); // Limpiar todos los filtros de columna
                this.tabla.draw(); // Redibujar la tabla
            }
        },

        /**
         * Abre el formulario para agregar un nuevo reclamo, inicializando los campos.
         */
        abrirFormulario() {
            const ahora = this.obtenerFechaActualArgentina();
            this.reclamo = {
                id: null, // Asegurar que es un nuevo reclamo
                municipalidad_id: '',
                municipalidad_tipo: 'ALUMBRADO PÚBLICO',
                municipalidad_motivo: '',
                municipalidad_fechaInicio: ahora,
                municipalidad_fechaModificacion: ahora,
                municipalidad_recepcion: '',
                municipalidad_estado: '',
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
            // Se crea una copia para evitar mutar el objeto original en la tabla directamente
            const reclamoEditado = { ...reclamo };
            // Convertir fechas al formato datetime-local para el input del formulario
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
            
            // Crear una copia de los datos del reclamo para enviar a la API
            const datosParaEnviar = { ...this.reclamo };
            // Convertir fechas al formato correcto para la base de datos (YYYY-MM-DD HH:MM:SS)
            if (datosParaEnviar.municipalidad_fechaInicio) {
                datosParaEnviar.municipalidad_fechaInicio = this.convertirFechaParaBD(datosParaEnviar.municipalidad_fechaInicio);
            }
            if (datosParaEnviar.municipalidad_fechaModificacion) {
                datosParaEnviar.municipalidad_fechaModificacion = this.convertirFechaParaBD(datosParaEnviar.municipalidad_fechaModificacion);
            }
            
            axios[metodo](url, datosParaEnviar).then(() => {
                this.obtenerReclamos(); // Recargar la tabla después de guardar
                bootstrap.Modal.getInstance(document.getElementById('modalReclamo')).hide();
            }).catch(error => {
                console.error('Error al guardar reclamo:', error);
                // Nota: Se mantiene el alert() existente. En un entorno de producción,
                // se debería usar un modal o notificación más amigable.
                alert('Error al guardar el reclamo'); 
            });
        },

        /**
         * Elimina un reclamo de la base de datos.
         * @param {Object} reclamo El objeto reclamo a eliminar.
         */
        eliminarReclamo(reclamo) {
            // Nota: Se mantiene el confirm() existente. En un entorno de producción,
            // se debería usar un modal o notificación más amigable.
            if (confirm(`¿Seguro que deseas eliminar el reclamo ${reclamo.municipalidad_id}?`)) {
                axios.delete(BASE_URL + 'api/reclamos/' + reclamo.id).then(() => {
                    this.obtenerReclamos(); // Recargar la tabla después de eliminar
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
            // Calcula el offset para convertir a la hora de Argentina (UTC-3)
            // ajustando la diferencia de la zona horaria local del navegador
            const offset = ahora.getTimezoneOffset() + (3 * 60); // Diferencia en minutos entre UTC y Argentina (UTC-3)
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
                // Se crea un objeto Date a partir de la cadena.
                // Si la cadena es "YYYY-MM-DD HH:MM:SS", Date() la interpreta en la zona horaria local.
                const date = new Date(fecha);
                
                // Formatear la fecha para la zona horaria de Argentina (UTC-3)
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
                return fecha; // Devolver la original si hay error
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
                // Ajustar a la zona horaria de Argentina (UTC-3) para asegurar que el input muestre la hora correcta
                const offset = date.getTimezoneOffset() + (3 * 60); // Diferencia en minutos entre UTC y Argentina (UTC-3)
                const fechaArgentina = new Date(date.getTime() - offset * 60 * 1000);
                
                return fechaArgentina.toISOString().slice(0, 16); // Formato YYYY-MM-DDTHH:MM
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
                // Ajustar a la zona horaria de Argentina (UTC-3)
                const offset = date.getTimezoneOffset() + (3 * 60); // Diferencia en minutos entre UTC y Argentina (UTC-3)
                const fechaArgentina = new Date(date.getTime() - offset * 60 * 1000);

                // Formatear a YYYY-MM-DD HH:MM:SS
                return fechaArgentina.toISOString().slice(0, 19).replace('T', ' ');
            } catch (error) {
                console.error('Error al convertir fecha para BD:', error);
                return fechaInput;
            }
        }
    },

    mounted() {
        // Al montar la aplicación, obtener los reclamos y configurar la tabla.
        this.obtenerReclamos();
    },
});
