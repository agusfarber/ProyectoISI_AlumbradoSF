const app = Vue.createApp({
    data() {
        return {
            reclamos: [],
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
            cargandoHistorial: false
        };
    },

    computed: {
        // Computed property de filtros comentado para HU futura
        // reclamosFiltrados() {
        //     let filtrados = [...this.reclamos];
        //     // ... lógica de filtros comentada
        //     return filtrados;
        // }
        
        // Por ahora, mostrar todos los reclamos sin filtrar
        reclamosFiltrados() {
            return this.reclamos;
        }
    },

    methods: {
        /**
         * Obtiene los reclamos desde la API
         */
        async obtenerReclamos() {
            try {
                const response = await axios.get(BASE_URL + 'api/reclamos');
                this.reclamos = response.data;
                console.log('Reclamos obtenidos:', this.reclamos);
            } catch (error) {
                console.error('Error al obtener reclamos:', error);
                this.mostrarMensaje('Error al cargar los reclamos', 'error');
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
        }
    },

    mounted() {
        this.obtenerReclamos();
    }
});

