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
            nuevoEstado: ''
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
            new bootstrap.Modal(document.getElementById('modalAcciones')).show();
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

                await axios.put(BASE_URL + 'api/reclamos/' + this.reclamoSeleccionado.id, datosActualizacion);
                
                // Actualizar el reclamo en la lista local
                const index = this.reclamos.findIndex(r => r.id === this.reclamoSeleccionado.id);
                if (index !== -1) {
                    this.reclamos[index].municipalidad_estado = this.nuevoEstado;
                    this.reclamos[index].municipalidad_fechaModificacion = datosActualizacion.municipalidad_fechaModificacion;
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
                case 'Media': return 'bg-warning';
                case 'Baja': return 'bg-success';
                default: return 'bg-secondary';
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

