
const app = Vue.createApp({
    data() {
        return {
            cuadrillas: [],
            cuadrilla: {
                nombre: '',
                descripcion: ''
            },
            cuadrillaSeleccionada: '',
            operariosDisponiblesParaEdicion: [],
            operariosSeleccionadosEdicion: [],
            // Variables para filtros
            filtroBusqueda: '',
            filtroOperarios: '',
            filtroCantidadOperarios: '',
            cuadrillasFiltradas: [],
            tabla: null,
            // Variable para controlar la cuadrilla seleccionada por fila
            filaSeleccionada: null
        };
    },

    methods: {
        // Obtener datos iniciales
        async obtenerCuadrillas() {
            try {
                const urlCuadrillas = BASE_URL + 'api/cuadrillas';
                console.log('URL Cuadrillas:', urlCuadrillas);
                
                const response = await axios.get(urlCuadrillas);
                console.log('Respuesta de la API cuadrillas:', response.data);
                this.cuadrillas = response.data;
                console.log('Cuadrillas después de asignar:', this.cuadrillas);
                
                // Asegurarse de que el DOM esté actualizado antes de inicializar DataTables
                this.$nextTick(() => {
                    console.log('Inicializando tabla con cuadrillas:', this.cuadrillas);
                    this.inicializarTabla();
                });
            } catch (error) {
                console.error('Error al obtener cuadrillas:', error);
                console.error('URL que falló:', error.config?.url);
                this.mostrarMensaje('Error al cargar cuadrillas', 'error');
            }
        },


        // Inicializar o reiniciar DataTable
        inicializarTabla() {
            if (this.tabla) {
                console.log('Destruyendo tabla anterior');
                this.tabla.destroy();
            }
            
            console.log('Creando nueva tabla con datos:', this.cuadrillas);
            this.tabla = $('#tabla_cuadrillas').DataTable({
                data: this.cuadrillas,
                responsive: true,
                columns: [
                    { data: 'nombre' },
                    { 
                        data: 'descripcion',
                        render: (data) => data || '-'
                    },
                    {
                        data: 'operarios',
                        render: (data) => {
                            if (data && data.length > 0) {
                                return data.map(op => `<span class="badge bg-primary me-1 mb-1">${op.nombre}</span>`).join('');
                            }
                            return '<span class="text-muted">Sin operarios asignados</span>';
                        }
                    }
                ]
            });

            // Configurar eventos para clic en fila (selección de cuadrilla)
            
            // Configurar eventos para clic en fila (selección de cuadrilla)
            $('#tabla_cuadrillas tbody').off('click', 'tr').on('click', 'tr', (e) => {
                // Evitar selección si se hace clic en un botón de acción
                if ($(e.target).closest('button').length > 0) {
                    return;
                }
                
                const row = this.tabla.row(e.currentTarget);
                const data = row.data();
                if (data) {
                    this.seleccionarCuadrillaPorFila(data.id, e.currentTarget);
                }
            });
        },

        // Aplicar filtros
        aplicarFiltros() {
            if (!this.tabla) return;

            // Limpiar filtros anteriores
            while ($.fn.dataTable.ext.search.length > 0) {
                $.fn.dataTable.ext.search.pop();
            }

            // Filtro por búsqueda de nombre
            if (this.filtroBusqueda) {
                $.fn.dataTable.ext.search.push((settings, data, dataIndex) => {
                    const nombre = data[1].toLowerCase(); // Columna de nombre
                    return nombre.includes(this.filtroBusqueda.toLowerCase());
                });
            }

            // Filtro por operarios
            if (this.filtroOperarios === 'con-operarios') {
                $.fn.dataTable.ext.search.push((settings, data, dataIndex) => {
                    const cuadrilla = this.cuadrillas[dataIndex];
                    return cuadrilla.operarios && cuadrilla.operarios.length > 0;
                });
            } else if (this.filtroOperarios === 'sin-operarios') {
                $.fn.dataTable.ext.search.push((settings, data, dataIndex) => {
                    const cuadrilla = this.cuadrillas[dataIndex];
                    return !cuadrilla.operarios || cuadrilla.operarios.length === 0;
                });
            }

            // Filtro por cantidad de operarios
            if (this.filtroCantidadOperarios) {
                const cantidad = parseInt(this.filtroCantidadOperarios);
                $.fn.dataTable.ext.search.push((settings, data, dataIndex) => {
                    const cuadrilla = this.cuadrillas[dataIndex];
                    return cuadrilla.operarios && cuadrilla.operarios.length === cantidad;
                });
            }

            this.tabla.draw();
        },

        // Limpiar filtros
        limpiarFiltros() {
            this.filtroBusqueda = '';
            this.filtroOperarios = '';
            this.filtroCantidadOperarios = '';
            
            // Limpiar filtros de DataTable
            while ($.fn.dataTable.ext.search.length > 0) {
                $.fn.dataTable.ext.search.pop();
            }
            
            if (this.tabla) {
                this.tabla.search('');
                this.tabla.draw();
            }
        },

        // Seleccionar cuadrilla por clic en fila
        seleccionarCuadrillaPorFila(cuadrillaId, filaElement) {
            console.log('Seleccionando cuadrilla por fila:', cuadrillaId);
            
            // Remover selección anterior
            $('#tabla_cuadrillas tbody tr').removeClass('table-primary');
            
            // Agregar selección visual a la fila actual
            $(filaElement).addClass('table-primary');
            
            // Actualizar el estado
            this.cuadrillaSeleccionada = cuadrillaId;
            this.filaSeleccionada = filaElement;
            
            console.log('Cuadrilla seleccionada:', cuadrillaId);
            console.log('Estado de cuadrillaSeleccionada:', this.cuadrillaSeleccionada);
            
            // Forzar actualización de la vista
            this.$forceUpdate();
        },

        // Limpiar selección de cuadrilla
        limpiarSeleccion() {
            this.cuadrillaSeleccionada = '';
            this.filaSeleccionada = null;
            $('#tabla_cuadrillas tbody tr').removeClass('table-primary');
            this.$forceUpdate();
        },
        

        // Abrir modal vacío
        abrirFormulario() {
            this.cuadrilla = {
                nombre: '',
                descripcion: ''
            };
            new bootstrap.Modal(document.getElementById('modalCuadrilla')).show();
        },

        // Cargar cuadrilla en modal
        async editarCuadrilla(cuadrillaId) {
            // Buscar la cuadrilla por ID
            const cuadrilla = this.cuadrillas.find(c => c.id == cuadrillaId);
            if (!cuadrilla) {
                this.mostrarMensaje('Cuadrilla no encontrada', 'error');
                return;
            }

            this.cuadrilla = { ...cuadrilla };
            
            // Cargar operarios disponibles para edición (excluyendo los ya asignados)
            await this.cargarOperariosDisponiblesParaEdicion();
            
            new bootstrap.Modal(document.getElementById('modalCuadrilla')).show();
        },


        // Crear o actualizar cuadrilla
        async guardarCuadrilla() {
            try {
                const esNuevo = !this.cuadrilla.id;
                const url = BASE_URL + 'api/cuadrillas' + (esNuevo ? '' : '/' + this.cuadrilla.id);
                const metodo = esNuevo ? 'post' : 'put';
                
                // Validar datos antes de enviar (solo para nuevas cuadrillas)
                if (esNuevo && (!this.cuadrilla.nombre || this.cuadrilla.nombre.trim() === '')) {
                    this.mostrarMensaje('El nombre de la cuadrilla es obligatorio', 'error');
                    return;
                }
                
                // Para edición, verificar que al menos hay algo que actualizar
                if (!esNuevo) {
                    const tieneNombre = this.cuadrilla.nombre && this.cuadrilla.nombre.trim() !== '';
                    const tieneDescripcion = this.cuadrilla.descripcion !== undefined;
                    
                    if (!tieneNombre && !tieneDescripcion) {
                        this.mostrarMensaje('No hay cambios para guardar', 'warning');
                        return;
                    }
                }
                
                // Preparar datos para envío
                const datosEnvio = {};
                
                // Para edición, siempre incluir descripción
                if (!esNuevo) {
                    // En edición, incluir nombre si existe
                    if (this.cuadrilla.nombre !== undefined) {
                        datosEnvio.nombre = this.cuadrilla.nombre.trim();
                    }
                    // Siempre incluir descripción en edición (incluso si está vacía)
                    datosEnvio.descripcion = this.cuadrilla.descripcion ? this.cuadrilla.descripcion.trim() : '';
                } else {
                    // Para nueva cuadrilla, solo incluir nombre si no está vacío
                    if (this.cuadrilla.nombre && this.cuadrilla.nombre.trim() !== '') {
                        datosEnvio.nombre = this.cuadrilla.nombre.trim();
                    }
                    // Incluir descripción si existe
                    if (this.cuadrilla.descripcion) {
                        datosEnvio.descripcion = this.cuadrilla.descripcion.trim();
                    }
                }
                
                console.log('Enviando datos:', datosEnvio);
                console.log('URL:', url);
                console.log('Método:', metodo);
                
                // Limpiar datos para evitar problemas de codificación
                const datosLimpios = {};
                Object.keys(datosEnvio).forEach(key => {
                    if (datosEnvio[key] !== null && datosEnvio[key] !== undefined) {
                        // Limpiar caracteres problemáticos y asegurar codificación UTF-8
                        let valor = String(datosEnvio[key]);
                        // Remover caracteres de control y caracteres problemáticos
                        valor = valor.replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
                        // Normalizar caracteres especiales
                        valor = valor.normalize('NFC');
                        datosLimpios[key] = valor;
                    }
                });
                
                console.log('Datos limpios:', datosLimpios);
                
                // Guardar datos básicos de la cuadrilla
                const response = await axios[metodo](url, datosLimpios, {
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8',
                        'Accept': 'application/json'
                    }
                });
                
                console.log('Respuesta del servidor:', response.data);
                
                // Si es edición, siempre actualizar asignaciones de operarios (incluso si está vacío)
                if (!esNuevo) {
                    const operariosIds = this.cuadrilla.operarios ? 
                        this.cuadrilla.operarios.map(op => op.id).filter(id => id !== undefined && id !== null) : [];
                    
                    await axios.post(BASE_URL + 'api/cuadrillas/asignar', {
                        cuadrillaId: this.cuadrilla.id,
                        operarios: operariosIds
                    });
                }
                
                // Actualizar la cuadrilla en la lista local inmediatamente
                if (!esNuevo) {
                    // Para edición, actualizar la cuadrilla existente en la lista
                    const index = this.cuadrillas.findIndex(c => c.id == this.cuadrilla.id);
                    if (index !== -1) {
                        // Actualizar los datos de la cuadrilla en la lista incluyendo operarios
                        this.cuadrillas[index] = { 
                            ...this.cuadrillas[index], 
                            ...datosLimpios,
                            operarios: this.cuadrilla.operarios || []
                        };
                        // Refrescar la tabla inmediatamente
                        this.$nextTick(() => {
                            this.inicializarTabla();
                        });
                    }
                } else {
                    // Para nueva cuadrilla, recargar todas las cuadrillas
                    await this.obtenerCuadrillas();
                }
                
                bootstrap.Modal.getInstance(document.getElementById('modalCuadrilla')).hide();
                
                // Limpiar formulario
                this.cuadrilla = { nombre: '', descripcion: '' };
                this.operariosDisponiblesParaEdicion = [];
                this.operariosSeleccionadosEdicion = [];
                
                // Mostrar mensaje de éxito
                this.mostrarMensaje('Cuadrilla guardada correctamente', 'success');
            } catch (error) {
                console.error('Error al guardar cuadrilla:', error);
                console.error('Error response:', error.response?.data);
                console.error('Error status:', error.response?.status);
                console.error('Error headers:', error.response?.headers);
                
                let mensajeError = 'Error al guardar cuadrilla';
                
                // Manejar diferentes tipos de errores
                if (error.response?.status === 400) {
                    // Error de validación
                    if (error.response?.data?.message) {
                        mensajeError = error.response.data.message;
                    } else if (error.response?.data?.messages) {
                        const mensajes = error.response.data.messages;
                        if (typeof mensajes === 'object') {
                            mensajeError = Object.values(mensajes).join(', ');
                        } else {
                            mensajeError = mensajes;
                        }
                    }
                } else if (error.response?.status === 422) {
                    // Error de validación específico
                    mensajeError = error.response?.data?.message || 'Datos inválidos';
                } else if (error.response?.status === 500) {
                    // Error del servidor
                    mensajeError = 'Error interno del servidor';
                } else if (error.code === 'ERR_NETWORK') {
                    // Error de red
                    mensajeError = 'Error de conexión. Verifique su conexión a internet.';
                } else if (error.message) {
                    mensajeError += ': ' + error.message;
                }
                
                this.mostrarMensaje(mensajeError, 'error');
            }
        },

        // Eliminar cuadrilla completa desde el modal de administración
        async eliminarCuadrillaCompleta() {
            console.log('eliminarCuadrillaCompleta llamada');
            console.log('Cuadrilla actual:', this.cuadrilla);
            
            if (!this.cuadrilla.id) {
                this.mostrarMensaje('No hay cuadrilla seleccionada para eliminar', 'error');
                return;
            }

            const cuadrilla = this.cuadrillas.find(c => c.id == this.cuadrilla.id);
            if (!cuadrilla) {
                this.mostrarMensaje('Cuadrilla no encontrada', 'error');
                return;
            }

            console.log('Cuadrilla encontrada:', cuadrilla);

            // Usar confirmación nativa como en otros módulos (reclamos, usuarios, materiales)
            if (confirm(`¿Seguro que deseas eliminar la cuadrilla "${cuadrilla.nombre}"? Esta acción eliminará también todas las asignaciones de operarios y no se puede deshacer.`)) {
                await this.procesarEliminacionCuadrilla();
            }
        },

        // Procesar la eliminación de la cuadrilla
        async procesarEliminacionCuadrilla() {
            try {
                console.log('Eliminando cuadrilla ID:', this.cuadrilla.id);
                await axios.delete(BASE_URL + 'api/cuadrillas/' + this.cuadrilla.id);
                
                // Cerrar el modal
                bootstrap.Modal.getInstance(document.getElementById('modalCuadrilla')).hide();
                
                // Limpiar selección
                this.cuadrillaSeleccionada = '';
                this.filaSeleccionada = null;
                $('#tabla_cuadrillas tbody tr').removeClass('table-primary');
                
                // Recargar cuadrillas
                await this.obtenerCuadrillas();
                
                // Limpiar formulario
                this.cuadrilla = { nombre: '', descripcion: '' };
                this.operariosDisponiblesParaEdicion = [];
                this.operariosSeleccionadosEdicion = [];
                
                this.mostrarMensaje('Cuadrilla eliminada correctamente', 'success');
            } catch (error) {
                console.error('Error al eliminar cuadrilla:', error);
                this.mostrarMensaje('Error al eliminar cuadrilla: ' + (error.response?.data?.message || error.message), 'error');
            }
        },

        // Abrir modal de administración de cuadrilla
        async abrirAdministracionCuadrilla() {
            console.log('Abriendo modal de administración de cuadrilla');
            console.log('Cuadrilla seleccionada:', this.cuadrillaSeleccionada);
            
            if (!this.cuadrillaSeleccionada || this.cuadrillaSeleccionada === '' || this.cuadrillaSeleccionada === null) {
                this.mostrarMensaje('Debe seleccionar una cuadrilla antes de administrarla', 'warning');
                return;
            }
            
            // Cargar la cuadrilla seleccionada para edición
            await this.editarCuadrilla(this.cuadrillaSeleccionada);
        },

        // Obtener nombre de cuadrilla por ID
        getNombreCuadrilla(cuadrillaId) {
            const cuadrilla = this.cuadrillas.find(c => c.id == cuadrillaId);
            return cuadrilla ? cuadrilla.nombre : '';
        },


        // Cargar operarios disponibles para edición
        async cargarOperariosDisponiblesParaEdicion() {
            try {
                const response = await axios.get(BASE_URL + 'api/operarios');
                const todosOperarios = response.data;
                
                // Filtrar operarios que no están ya asignados a esta cuadrilla
                const operariosAsignadosIds = this.cuadrilla.operarios ? this.cuadrilla.operarios.map(op => op.id) : [];
                this.operariosDisponiblesParaEdicion = todosOperarios.filter(op => !operariosAsignadosIds.includes(op.id));
                
                // Limpiar selección
                this.operariosSeleccionadosEdicion = [];
            } catch (error) {
                console.error('Error al cargar operarios para edición:', error);
            }
        },

        // Toggle selección de todos los operarios en edición
        toggleSeleccionTodosOperariosEdicion(event) {
            const isChecked = event.target.checked;
            this.operariosSeleccionadosEdicion = [];
            
            if (isChecked) {
                this.operariosDisponiblesParaEdicion.forEach(operario => {
                    this.operariosSeleccionadosEdicion.push(operario.id);
                });
            }
        },

        // Agregar operarios seleccionados a la cuadrilla
        agregarOperariosSeleccionados() {
            if (this.operariosSeleccionadosEdicion.length === 0) {
                this.mostrarMensaje('Seleccione al menos un operario', 'warning');
                return;
            }

            // Agregar operarios a la cuadrilla localmente
            this.operariosSeleccionadosEdicion.forEach(operarioId => {
                const operario = this.operariosDisponiblesParaEdicion.find(op => op.id === operarioId);
                if (operario && !this.cuadrilla.operarios.find(op => op.id === operarioId)) {
                    if (!this.cuadrilla.operarios) {
                        this.cuadrilla.operarios = [];
                    }
                    this.cuadrilla.operarios.push(operario);
                }
            });

            // Recargar operarios disponibles
            this.cargarOperariosDisponiblesParaEdicion();
            
            this.mostrarMensaje('Operarios agregados correctamente', 'success');
        },

        // Quitar operario de la cuadrilla
        quitarOperario(operarioId) {
            // Usar confirmación nativa como en otros módulos (reclamos, usuarios, materiales)
            if (confirm('¿Está seguro de que desea quitar este operario de la cuadrilla?')) {
                // Eliminar operario del array local
                this.cuadrilla.operarios = this.cuadrilla.operarios.filter(op => op.id !== operarioId);
                // Recargar operarios disponibles para mostrar el operario removido
                this.cargarOperariosDisponiblesParaEdicion();
                this.mostrarMensaje('Operario removido correctamente', 'success');
            }
        },

        mostrarMensaje(mensaje, tipo) {
            // Crear y mostrar un toast o alert
            const alertClass = tipo === 'success' ? 'alert-success' : 
                              tipo === 'warning' ? 'alert-warning' : 'alert-danger';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                    ${mensaje}
                </div>
            `;
            
            $('body').append(alertHtml);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                $('.alert').fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    },

    computed: {
        cuadrillasConOperarios() {
            return this.cuadrillas.filter(cuadrilla => 
                cuadrilla.operarios && cuadrilla.operarios.length > 0
            ).length;
        },
        cuadrillasSinOperarios() {
            return this.cuadrillas.filter(cuadrilla => 
                !cuadrilla.operarios || cuadrilla.operarios.length === 0
            ).length;
        },
        totalOperarios() {
            return this.cuadrillas.reduce((total, cuadrilla) => {
                return total + (cuadrilla.operarios ? cuadrilla.operarios.length : 0);
            }, 0);
        }
    },

    watch: {
        cuadrillaSeleccionada: {
            handler(newVal, oldVal) {
                console.log('Cuadrilla seleccionada cambió de', oldVal, 'a', newVal);
                this.$forceUpdate();
            }
        }
    },

    async mounted() {
        await this.obtenerCuadrillas();
    },
});