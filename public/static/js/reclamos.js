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
            tabla: null
        };
    },

    methods: {
        // Obtener datos iniciales
        async obtenerReclamos() {
            try {
                const urlReclamos = BASE_URL + 'api/reclamos';
                console.log('URL Reclamos:', urlReclamos);
                
                const response = await axios.get(urlReclamos);
                console.log('Respuesta de la API reclamos:', response.data);
                this.reclamos = response.data;
                console.log('Reclamos después de asignar:', this.reclamos);
                this.$nextTick(() => {
                    console.log('Inicializando tabla con reclamos:', this.reclamos);
                    this.inicializarTabla();
                });
            } catch (error) {
                console.error('Error al obtener datos:', error);
                console.error('URL que falló:', error.config?.url);
            }
        },

        // Inicializar o reiniciar DataTable
        inicializarTabla() {
            if (this.tabla) {
                console.log('Destruyendo tabla anterior');
                this.tabla.destroy();
            }
            console.log('Creando nueva tabla con datos:', this.reclamos);
            this.tabla = $('#tabla_reclamos').DataTable({
                data: this.reclamos,
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/2.2.1/i18n/es-MX.json'
                },
                columns: [
                    { data: 'municipalidad_id' },
                    { data: 'municipalidad_motivo' },
                    { data: 'municipalidad_fechaInicio' },
                    { data: 'municipalidad_fechaModificacion' },
                    { data: 'municipalidad_recepcion' },
                    { data: 'municipalidad_estado' },
                    { data: 'municipalidad_domicilio' },
                    { data: 'municipalidad_numeroDomicilio' },
                    'acciones'
                ]
            });
            
            // Inicializar mejoras de tabla después de que DataTable esté listo
            this.$nextTick(() => {
                if (window.tableEnhancements) {
                    window.tableEnhancements.setupMobileTableTouch();
                }
            });
        },

        // Abrir modal vacío
        abrirFormulario() {
            const ahora = this.obtenerFechaActualArgentina();
            this.reclamo = {
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

        // Cargar reclamo en modal
        editarReclamo(reclamo) {
            // Convertir fechas al formato datetime-local
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

        // Ver detalles del reclamo
        verReclamo(reclamo) {
            this.reclamoSeleccionado = { ...reclamo };
            new bootstrap.Modal(document.getElementById('modalVerReclamo')).show();
        },

        // Crear o actualizar reclamo
        guardarReclamo() {
            const esNuevo = !this.reclamo.id;
            const url = BASE_URL + 'api/reclamos' + (esNuevo ? '' : '/' + this.reclamo.id);
            const metodo = esNuevo ? 'post' : 'put';
            
            // Convertir fechas al formato correcto para la base de datos
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

        // Eliminar reclamo
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

        // Obtener fecha actual en zona horaria de Argentina
        obtenerFechaActualArgentina() {
            const ahora = new Date();
            // Convertir a zona horaria de Argentina (UTC-3)
            const fechaArgentina = new Date(ahora.getTime() - (3 * 60 * 60 * 1000));
            return fechaArgentina.toISOString().slice(0, 16);
        },

        // Formatear fecha para mostrar en la tabla con zona horaria de Argentina
        formatearFecha(fecha) {
            if (!fecha) return '';
            
            try {
                const date = new Date(fecha);
                // Ajustar a zona horaria de Argentina (UTC-3)
                const fechaArgentina = new Date(date.getTime() - (3 * 60 * 60 * 1000));
                
                return fechaArgentina.toLocaleDateString('es-AR', {
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

        // Formatear fecha para input datetime-local con zona horaria de Argentina
        formatearFechaParaInput(fecha) {
            if (!fecha) return '';
            
            try {
                const date = new Date(fecha);
                // Ajustar a zona horaria de Argentina (UTC-3)
                const fechaArgentina = new Date(date.getTime() - (3 * 60 * 60 * 1000));
                return fechaArgentina.toISOString().slice(0, 16);
            } catch (error) {
                console.error('Error al formatear fecha para input:', error);
                return fecha;
            }
        },

        // Convertir fecha de input a formato de base de datos con zona horaria de Argentina
        convertirFechaParaBD(fechaInput) {
            if (!fechaInput) return '';
            
            try {
                const date = new Date(fechaInput);
                // Ajustar a zona horaria de Argentina (UTC-3)
                const fechaArgentina = new Date(date.getTime() - (3 * 60 * 60 * 1000));
                return fechaArgentina.toISOString().slice(0, 19).replace('T', ' ');
            } catch (error) {
                console.error('Error al convertir fecha para BD:', error);
                return fechaInput;
            }
        }
    },

    mounted() {
        this.obtenerReclamos();
    },
});
