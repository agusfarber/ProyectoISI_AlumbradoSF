const app = Vue.createApp({
    data() {
        return {
            reclamosCompletados: [],
            reclamosCerrados: [],
            reclamosSeleccionados: [],
            reclamoSeleccionado: {},
            tabla: null,
            tablaCerrados: null,
            cargando: false,
            procesando: false,
            ultimaActualizacion: '',
            solapaCierre: 'pendientes'
        };
    },

    computed: {
        /**
         * Verifica si todos los reclamos están marcados
         */
        todosMarcados() {
            return this.reclamosCompletados.length > 0 && 
                   this.reclamosSeleccionados.length === this.reclamosCompletados.length;
        }
    },

    methods: {
        cambiarSolapaCierre(solapa) {
            this.solapaCierre = solapa;

            this.$nextTick(() => {
                if (solapa === 'pendientes' && this.tabla) {
                    this.tabla.columns.adjust().responsive.recalc();
                }

                if (solapa === 'cerrados' && this.tablaCerrados) {
                    this.tablaCerrados.columns.adjust().responsive.recalc();
                }
            });
        },

        /**
         * Obtiene los reclamos completados desde la API
         */
        async obtenerReclamosCompletados() {
            this.cargando = true;
            try {
                const url = BASE_URL + 'api/cierre-reclamos/completados';
                const response = await axios.get(url);

                if (response.data.success) {
                    // Ordenar reclamos de menor a mayor por municipalidad_id
                    this.reclamosCompletados = response.data.reclamos.sort((a, b) => {
                        return parseInt(a.municipalidad_id) - parseInt(b.municipalidad_id);
                    });
                    this.actualizarFechaActualizacion();
                    
                    // Inicializar o actualizar la tabla DataTables
                    this.$nextTick(() => {
                        this.inicializarTabla();
                    });
                } else {
                    this.mostrarMensaje('Error al cargar reclamos completados', 'error');
                }
            } catch (error) {
                console.error('Error al obtener reclamos completados:', error);
                if (error.response && error.response.status === 401) {
                    this.mostrarMensaje('No tiene permisos para acceder a esta función. Solo supervisores pueden cerrar reclamos.', 'error');
                } else {
                    this.mostrarMensaje('Error al cargar los reclamos completados', 'error');
                }
            } finally {
                this.cargando = false;
            }
        },

        /**
         * Obtiene los reclamos cerrados desde la API
         */
        async obtenerReclamosCerrados() {
            try {
                const url = BASE_URL + 'api/cierre-reclamos/cerrados';
                const response = await axios.get(url);

                if (response.data.success) {
                    // Ordenar reclamos de menor a mayor por municipalidad_id
                    this.reclamosCerrados = response.data.reclamos.sort((a, b) => {
                        return parseInt(a.municipalidad_id) - parseInt(b.municipalidad_id);
                    });
                    
                    // Inicializar o actualizar la tabla DataTables
                    this.$nextTick(() => {
                        this.inicializarTablaCerrados();
                    });
                } else {
                    this.mostrarMensaje('Error al cargar reclamos cerrados', 'error');
                }
            } catch (error) {
                console.error('Error al obtener reclamos cerrados:', error);
                if (error.response && error.response.status === 401) {
                    this.mostrarMensaje('No tiene permisos para acceder a esta función. Solo supervisores pueden ver reclamos cerrados.', 'error');
                } else {
                    this.mostrarMensaje('Error al cargar los reclamos cerrados', 'error');
                }
            }
        },

        /**
         * Inicializa o reinicia la DataTable con los datos actuales de reclamos completados
         */
        inicializarTabla() {
            if (this.tabla) {
                this.tabla.destroy();
            }
            
            this.tabla = $('#tabla_cierre_reclamos').DataTable({
                data: this.reclamosCompletados,
                responsive: true,
                pageLength: 30,
                pagingType: 'simple_numbers',
                lengthMenu: [
                    [15, 30, 50, 100],
                    ['15 por página', '30 por página', '50 por página', '100 por página']
                ],
                language: {
                    processing: 'Procesando...',
                    lengthMenu: '_MENU_',
                    zeroRecords: 'No hay reclamos pendientes con ese criterio',
                    emptyTable: 'No hay reclamos pendientes de cierre',
                    infoEmpty: 'Sin reclamos para mostrar',
                    infoFiltered: '(filtrado de _MAX_ reclamos)',
                    search: '',
                    searchPlaceholder: 'Buscar pendiente...',
                    loadingRecords: 'Cargando...',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ reclamos',
                    paginate: {
                        previous: 'Anterior',
                        next: 'Siguiente'
                    }
                },
                columns: [
                    {
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: (data, type, row) => {
                            const checked = this.esSeleccionado(row.id) ? 'checked' : '';
                            return `<input type="checkbox" class="form-check-input seleccionar-reclamo" data-id="${row.id}" ${checked}>`;
                        }
                    },
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
                        data: null,
                        className: 'text-start',
                        render: (data, type, row) => {
                            return `${row.municipalidad_domicilio} ${row.municipalidad_numeroDomicilio}`;
                        }
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
                    }
                ],
                order: [[1, 'asc']], // Ordenar por ID ascendente
                initComplete: function () {
                    const wrapper = $('#tabla_cierre_reclamos_wrapper');
                    wrapper.find('.dt-length select').addClass('form-select form-select-sm');
                    wrapper.find('.dt-search input').addClass('form-control form-control-sm').attr('aria-label', 'Buscar reclamo pendiente');
                }
            });

            // Evento para ver detalles al hacer clic en el ID
            $('#tabla_cierre_reclamos tbody').off('click', '.ver-reclamo-id').on('click', '.ver-reclamo-id', (e) => {
                e.preventDefault();
                const id = $(e.currentTarget).data('id');
                const reclamo = this.reclamosCompletados.find(r => r.id == id);
                if (reclamo) this.verDetalles(reclamo);
            });

            // Evento para seleccionar/deseleccionar checkboxes individuales
            $('#tabla_cierre_reclamos tbody').off('click', '.seleccionar-reclamo').on('click', '.seleccionar-reclamo', (e) => {
                const id = parseInt($(e.currentTarget).data('id'));
                const index = this.reclamosSeleccionados.indexOf(id);
                
                if (index > -1) {
                    this.reclamosSeleccionados.splice(index, 1);
                } else {
                    this.reclamosSeleccionados.push(id);
                }
                
                // Actualizar el estado del checkbox principal
                this.$forceUpdate();
            });
        },

        /**
         * Inicializa o reinicia la DataTable con los datos actuales de reclamos cerrados
         */
        inicializarTablaCerrados() {
            if (this.tablaCerrados) {
                this.tablaCerrados.destroy();
            }
            
            this.tablaCerrados = $('#tabla_reclamos_cerrados').DataTable({
                data: this.reclamosCerrados,
                responsive: true,
                pageLength: 30,
                pagingType: 'simple_numbers',
                lengthMenu: [
                    [15, 30, 50, 100],
                    ['15 por página', '30 por página', '50 por página', '100 por página']
                ],
                language: {
                    processing: 'Procesando...',
                    lengthMenu: '_MENU_',
                    zeroRecords: 'No hay reclamos cerrados con ese criterio',
                    emptyTable: 'No hay reclamos cerrados',
                    infoEmpty: 'Sin reclamos para mostrar',
                    infoFiltered: '(filtrado de _MAX_ reclamos)',
                    search: '',
                    searchPlaceholder: 'Buscar cerrado...',
                    loadingRecords: 'Cargando...',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ reclamos',
                    paginate: {
                        previous: 'Anterior',
                        next: 'Siguiente'
                    }
                },
                columns: [
                    {
                        data: 'municipalidad_id',
                        className: 'text-start',
                        render: (data, type, row) => {
                            return `<a href="#" class="ver-reclamo-cerrado-id text-primary fw-bold" data-id="${row.id}" style="text-decoration: none; cursor: pointer;">${data}</a>`;
                        }
                    },
                    { 
                        data: 'municipalidad_motivo',
                        className: 'text-start'
                    },
                    {
                        data: null,
                        className: 'text-start',
                        render: (data, type, row) => {
                            return `${row.municipalidad_domicilio} ${row.municipalidad_numeroDomicilio}`;
                        }
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
                        data: 'fecha_cierre',
                        className: 'text-start',
                        render: (data) => this.formatearFecha(data)
                    }
                ],
                order: [[0, 'asc']], // Ordenar por ID ascendente
                initComplete: function () {
                    const wrapper = $('#tabla_reclamos_cerrados_wrapper');
                    wrapper.find('.dt-length select').addClass('form-select form-select-sm');
                    wrapper.find('.dt-search input').addClass('form-control form-control-sm').attr('aria-label', 'Buscar reclamo cerrado');
                }
            });

            // Evento para ver detalles al hacer clic en el ID
            $('#tabla_reclamos_cerrados tbody').off('click', '.ver-reclamo-cerrado-id').on('click', '.ver-reclamo-cerrado-id', (e) => {
                e.preventDefault();
                const id = $(e.currentTarget).data('id');
                const reclamo = this.reclamosCerrados.find(r => r.id == id);
                if (reclamo) this.verDetalles(reclamo);
            });
        },

        /**
         * Cierra los reclamos seleccionados
         */
        async cerrarReclamosSeleccionados() {
            if (this.reclamosSeleccionados.length === 0) {
                this.mostrarMensaje('Debe seleccionar al menos un reclamo para cerrar', 'warning');
                return;
            }

            // Confirmar acción
            const confirmacion = await this.mostrarConfirmacion(
                `¿Está seguro que desea cerrar ${this.reclamosSeleccionados.length} reclamo(s)?<br>
                <small class="text-muted">Los reclamos cerrados quedarán bloqueados para edición y se registrará la fecha de cierre.</small>`,
                'Confirmar Cierre de Reclamos'
            );

            if (!confirmacion) {
                return;
            }

            this.procesando = true;

            try {
                const url = BASE_URL + 'api/cierre-reclamos/cerrar';
                const response = await axios.post(url, {
                    reclamos_ids: this.reclamosSeleccionados
                });

                if (response.data.success) {
                    // Mensaje de éxito
                    let mensaje = `<i class="bi bi-check-circle-fill"></i> <strong>Cierre exitoso</strong><br>`;
                    mensaje += `<strong>Reclamos cerrados:</strong> ${response.data.cerrados}<br>`;
                    mensaje += `<strong>Fecha de cierre:</strong> ${this.formatearFecha(response.data.fecha_cierre)}`;

                    // Información sobre envío al sistema 103
                    if (response.data.enviados_sistema103 !== undefined) {
                        mensaje += `<br><br><strong>Sincronización con Sistema 103:</strong><br>`;
                        
                        if (response.data.enviados_sistema103 > 0) {
                            mensaje += `<span class="text-success"><i class="bi bi-check-circle"></i> ${response.data.enviados_sistema103} reclamo(s) cerrado(s) y enviado(s) exitosamente al sistema 103</span>`;
                            
                            if (response.data.reclamos_enviados_externos && response.data.reclamos_enviados_externos.length > 0) {
                                mensaje += `<br><small class="text-muted">IDs: ${response.data.reclamos_enviados_externos.join(', ')}</small>`;
                            }
                        }
                        
                        if (response.data.no_enviados_sistema103 > 0) {
                            mensaje += `<br><span class="text-warning"><i class="bi bi-exclamation-triangle"></i> ${response.data.no_enviados_sistema103} reclamo(s) NO se cerraron porque falló el envío al sistema 103</span>`;
                            mensaje += `<br><small class="text-muted">Estos reclamos permanecen sin cerrar en la base de datos local hasta que se pueda enviar correctamente al sistema 103.</small>`;
                            
                            if (response.data.reclamos_no_enviados_externos && response.data.reclamos_no_enviados_externos.length > 0) {
                                mensaje += '<br><small class="text-muted">Detalles de errores:<ul class="mb-0">';
                                response.data.reclamos_no_enviados_externos.forEach(item => {
                                    mensaje += `<li>Reclamo ${item.id}: ${item.error}</li>`;
                                });
                                mensaje += '</ul></small>';
                            }
                        }
                    }

                    if (response.data.errores > 0) {
                        mensaje += `<br><br><strong class="text-warning">Advertencia:</strong> ${response.data.errores} reclamo(s) no pudieron ser cerrados`;
                        
                        if (response.data.detalles_errores) {
                            mensaje += '<br><small class="text-muted">Detalles:<ul class="mb-0">';
                            response.data.detalles_errores.forEach(detalle => {
                                mensaje += `<li>${detalle}</li>`;
                            });
                            mensaje += '</ul></small>';
                        }
                    }

                    this.mostrarMensaje(mensaje, 'success');

                    // Limpiar selección y recargar ambas tablas
                    this.reclamosSeleccionados = [];
                    await Promise.all([
                        this.obtenerReclamosCompletados(),
                        this.obtenerReclamosCerrados()
                    ]);

                } else {
                    let mensajeError = 'No se pudo cerrar ningún reclamo';
                    if (response.data.detalles_errores) {
                        mensajeError += ':<ul class="mb-0">';
                        response.data.detalles_errores.forEach(detalle => {
                            mensajeError += `<li>${detalle}</li>`;
                        });
                        mensajeError += '</ul>';
                    }
                    this.mostrarMensaje(mensajeError, 'error');
                }

            } catch (error) {
                console.error('Error al cerrar reclamos:', error);
                if (error.response && error.response.status === 401) {
                    this.mostrarMensaje('No tiene permisos para cerrar reclamos. Solo supervisores pueden realizar esta acción.', 'error');
                } else {
                    this.mostrarMensaje('Error al cerrar los reclamos. Por favor, intente nuevamente.', 'error');
                }
            } finally {
                this.procesando = false;
            }
        },

        /**
         * Selecciona todos los reclamos
         */
        seleccionarTodos() {
            this.reclamosSeleccionados = this.reclamosCompletados.map(r => r.id);
            // Reinicializar tabla para actualizar checkboxes visualmente
            this.$nextTick(() => {
                if (this.tabla) {
                    this.inicializarTabla();
                }
            });
        },

        /**
         * Deselecciona todos los reclamos
         */
        deseleccionarTodos() {
            this.reclamosSeleccionados = [];
            // Reinicializar tabla para actualizar checkboxes visualmente
            this.$nextTick(() => {
                if (this.tabla) {
                    this.inicializarTabla();
                }
            });
        },

        /**
         * Toggle para seleccionar/deseleccionar todos
         */
        toggleSeleccionTodos(event) {
            if (event.target.checked) {
                this.seleccionarTodos();
            } else {
                this.deseleccionarTodos();
            }
        },

        /**
         * Verifica si un reclamo está seleccionado
         */
        esSeleccionado(reclamoId) {
            return this.reclamosSeleccionados.includes(reclamoId);
        },

        /**
         * Muestra los detalles de un reclamo en un modal
         */
        verDetalles(reclamo) {
            this.reclamoSeleccionado = { ...reclamo };
            new bootstrap.Modal(document.getElementById('modalVerReclamo')).show();
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
         * Actualiza la hora de última actualización
         */
        actualizarFechaActualizacion() {
            const ahora = new Date();
            this.ultimaActualizacion = ahora.toLocaleString('es-AR', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                timeZone: 'America/Argentina/Buenos_Aires'
            });
        },

        /**
         * Muestra mensajes de notificación
         */
        mostrarMensaje(mensaje, tipo) {
            // Si es un mensaje de éxito, eliminar mensajes anteriores
            if (tipo === 'success') {
                $('.mensaje-notificacion').fadeOut(200, function() {
                    $(this).remove();
                });
            }

            const alertClass = tipo === 'success' ? 'alert-success' : 
                              tipo === 'warning' ? 'alert-warning' : 
                              tipo === 'info' ? 'alert-info' : 'alert-danger';

            const iconClass = tipo === 'success' ? 'bi-check-circle-fill' : 
                             tipo === 'warning' ? 'bi-exclamation-triangle-fill' : 
                             tipo === 'info' ? 'bi-info-circle-fill' : 'bi-x-circle-fill';

            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed mensaje-notificacion shadow-lg" 
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 350px; max-width: 500px;" role="alert">
                    <i class="bi ${iconClass} me-2"></i>
                    <div style="display: inline-block; vertical-align: top;">
                        ${mensaje}
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            $('body').append(alertHtml);

            // Auto-remover después de 8 segundos
            setTimeout(() => {
                $('.mensaje-notificacion').fadeOut(500, function() {
                    $(this).remove();
                });
            }, 8000);
        },

        /**
         * Muestra un diálogo de confirmación
         */
        mostrarConfirmacion(mensaje, titulo = 'Confirmar Acción') {
            return new Promise((resolve) => {
                const modalHtml = `
                    <div class="modal fade" id="modalConfirmacionCierre" tabindex="-1" aria-hidden="true">
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
                                        <i class="bi bi-lock-fill text-warning" style="font-size: 3rem;"></i>
                                        <div class="mt-3">${mensaje}</div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btnCancelarCierre">
                                        <i class="bi bi-x-circle me-1"></i>Cancelar
                                    </button>
                                    <button type="button" class="btn btn-warning" id="btnConfirmarCierre">
                                        <i class="bi bi-check-circle me-1"></i>Confirmar Cierre
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                // Remover modal anterior si existe
                $('#modalConfirmacionCierre').remove();

                // Agregar el modal al body
                $('body').append(modalHtml);

                // Mostrar el modal
                const modal = new bootstrap.Modal(document.getElementById('modalConfirmacionCierre'));
                modal.show();

                // Manejar botones
                $('#btnConfirmarCierre').on('click', () => {
                    modal.hide();
                    setTimeout(() => {
                        $('#modalConfirmacionCierre').remove();
                    }, 300);
                    resolve(true);
                });

                $('#btnCancelarCierre').on('click', () => {
                    modal.hide();
                    setTimeout(() => {
                        $('#modalConfirmacionCierre').remove();
                    }, 300);
                    resolve(false);
                });

                // Manejar cierre del modal (X o ESC)
                $('#modalConfirmacionCierre').on('hidden.bs.modal', () => {
                    $('#modalConfirmacionCierre').remove();
                    resolve(false);
                });
            });
        }
    },

    mounted() {
        // Cargar reclamos completados y cerrados al montar el componente
        this.obtenerReclamosCompletados();
        this.obtenerReclamosCerrados();

        // Actualizar cada 1 hora (3600000 ms)
        setInterval(() => {
            this.obtenerReclamosCompletados();
            this.obtenerReclamosCerrados();
        }, 3600000);
    }
});

app.mount('#app');

