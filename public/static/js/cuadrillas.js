
const app = Vue.createApp({
    data() {
        return {
            cuadrillas: [],
            cuadrilla: {
                nombre: '',
                descripcion: '',
                operarios: []
            },
            cuadrillaSeleccionada: '',
            operariosDisponiblesParaEdicion: [],
            operariosSeleccionadosEdicion: [],
            // Buscador
            filtroBusqueda: '',
            filaSeleccionada: null,
            // Cuadrilla mostrada en el modal de detalle
            detalle: null,
            // Buscador de operarios disponibles dentro del modal de edición
            filtroDisponibles: ''
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
            } catch (error) {
                console.error('Error al obtener cuadrillas:', error);
                console.error('URL que falló:', error.config?.url);
                this.mostrarMensaje('Error al cargar cuadrillas', 'error');
            }
        },


        // Abrir el modal de administración directamente desde una tarjeta
        administrarCuadrilla(cuadrillaId) {
            this.cuadrillaSeleccionada = cuadrillaId;
            this.editarCuadrilla(cuadrillaId);
        },

        // Mostrar el detalle de una cuadrilla (vista en grande, solo lectura)
        verDetalle(cuadrillaId) {
            const cuadrilla = this.cuadrillas.find(c => c.id == cuadrillaId);
            if (!cuadrilla) {
                this.mostrarMensaje('Cuadrilla no encontrada', 'error');
                return;
            }
            this.detalle = cuadrilla;
            new bootstrap.Modal(document.getElementById('modalDetalleCuadrilla')).show();
        },

        // Pasar del detalle al modal de edición
        editarDesdeDetalle() {
            if (!this.detalle) return;
            const id = this.detalle.id;
            const modalEl = document.getElementById('modalDetalleCuadrilla');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modalEl.addEventListener('hidden.bs.modal', () => this.editarCuadrilla(id), { once: true });
                modal.hide();
            } else {
                this.editarCuadrilla(id);
            }
        },

        // Limpiar selección de cuadrilla
        limpiarSeleccion() {
            this.cuadrillaSeleccionada = '';
            this.filaSeleccionada = null;
        },

        // URL de la foto de perfil de un operario
        urlFoto(nombreArchivo) {
            return BASE_URL + 'static/uploads/perfiles/' + nombreArchivo;
        },

        // Iniciales para el avatar de un operario
        iniciales(nombre) {
            if (!nombre) return '?';
            const partes = nombre.trim().split(/\s+/);
            const primera = partes[0] ? partes[0][0] : '';
            const segunda = partes.length > 1 ? partes[partes.length - 1][0] : '';
            return (primera + segunda).toUpperCase();
        },

        // Color determinístico para el avatar según el nombre
        colorAvatar(nombre) {
            const paleta = ['#3A3972', '#6E6D99', '#2D6A6A', '#7A5C9E', '#A65A7A', '#4C6EA8', '#9E7B3A'];
            const texto = nombre || '';
            let hash = 0;
            for (let i = 0; i < texto.length; i++) {
                hash = texto.charCodeAt(i) + ((hash << 5) - hash);
            }
            return paleta[Math.abs(hash) % paleta.length];
        },

        // Operarios con permisos de gestión en una cuadrilla
        operariosConGestion(cuadrilla) {
            if (!cuadrilla?.operarios) return [];
            return cuadrilla.operarios.filter(op => Number(op.es_jefe) === 1);
        },

        etiquetaGestionCuadrilla(cuadrilla) {
            const conGestion = this.operariosConGestion(cuadrilla);
            if (!conGestion.length) return '';
            if (conGestion.length === 1) return conGestion[0].nombre;
            return conGestion.length + ' con gestión';
        },

        idsOperariosConPermiso() {
            return (this.cuadrilla.operarios || [])
                .filter(op => Number(op.es_jefe) === 1)
                .map(op => op.id);
        },
        

        // Abrir modal vacío (nueva cuadrilla con selección de operarios)
        async abrirFormulario() {
            this.cuadrilla = {
                nombre: '',
                descripcion: '',
                operarios: []
            };
            this.filtroDisponibles = '';
            await this.cargarOperariosDisponiblesParaEdicion();
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

            this.cuadrilla = { ...cuadrilla, operarios: (cuadrilla.operarios || []).map(op => ({ ...op })) };
            this.filtroDisponibles = '';
            
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

                // Si hay operarios, al menos uno con permisos de gestión
                const operariosSeleccionados = (this.cuadrilla.operarios || [])
                    .map(op => op.id)
                    .filter(id => id !== undefined && id !== null);
                const operariosConPermiso = this.idsOperariosConPermiso();
                if (operariosSeleccionados.length > 0 && operariosConPermiso.length === 0) {
                    this.mostrarMensaje('Otorgá permisos de gestión a al menos un operario', 'warning');
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

                // Determinar el ID de la cuadrilla (en nuevo viene en la respuesta de creación)
                const cuadrillaId = esNuevo ? (response.data && response.data.id) : this.cuadrilla.id;

                // En edición siempre se sincronizan las asignaciones (incluso vacías);
                // en nuevo solo se asignan si se eligieron operarios.
                if (cuadrillaId && (!esNuevo || operariosSeleccionados.length > 0)) {
                    await axios.post(BASE_URL + 'api/cuadrillas/asignar', {
                        cuadrillaId: cuadrillaId,
                        operarios: operariosSeleccionados,
                        operariosConPermiso: operariosConPermiso
                    });
                }
                
                // Cerrar modal antes de recargar para evitar conflictos
                bootstrap.Modal.getInstance(document.getElementById('modalCuadrilla')).hide();
                
                // Limpiar formulario
                this.cuadrilla = { nombre: '', descripcion: '', operarios: [] };
                this.operariosDisponiblesParaEdicion = [];
                this.operariosSeleccionadosEdicion = [];
                this.filtroDisponibles = '';
                
                // Recargar todas las cuadrillas para actualizar automáticamente
                // las otras cuadrillas que perdieron operarios
                await this.obtenerCuadrillas();
                
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

            // Mostrar modal de confirmación personalizado
            const mensajeConfirmacion = `¿Está seguro de que desea eliminar la cuadrilla "${cuadrilla.nombre}"? Esta acción eliminará también todas las asignaciones de operarios y no se puede deshacer.`;
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Eliminar Cuadrilla');
            
            if (!confirmacion) {
                return;
            }

            await this.procesarEliminacionCuadrilla();
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
                
                // Recargar cuadrillas
                await this.obtenerCuadrillas();
                
                // Limpiar formulario
                this.cuadrilla = { nombre: '', descripcion: '', operarios: [] };
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

        // Agregar un operario directamente desde la lista de disponibles
        agregarOperarioDirecto(operario) {
            if (!operario) return;
            if (!this.cuadrilla.operarios) {
                this.cuadrilla.operarios = [];
            }
            if (this.cuadrilla.operarios.find(op => op.id === operario.id)) {
                return;
            }
            // Límite máximo de 4 operarios por cuadrilla
            if (this.cuadrilla.operarios.length >= 4) {
                this.mostrarMensaje('Una cuadrilla puede tener como máximo 4 operarios', 'warning');
                return;
            }
            this.cuadrilla.operarios.push({ ...operario, es_jefe: 0 });
            this.cargarOperariosDisponiblesParaEdicion();
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
                    operario.es_jefe = 0;
                    this.cuadrilla.operarios.push(operario);
                }
            });

            // Recargar operarios disponibles
            this.cargarOperariosDisponiblesParaEdicion();
            
            this.mostrarMensaje('Operarios agregados correctamente', 'success');
        },

        // Quitar operario de la cuadrilla
        quitarOperario(operarioId) {
            // Eliminar operario del array local directamente sin confirmación
            this.cuadrilla.operarios = this.cuadrilla.operarios.filter(op => op.id !== operarioId);
            // Recargar operarios disponibles para mostrar el operario removido
            this.cargarOperariosDisponiblesParaEdicion();
            this.mostrarMensaje('Operario removido correctamente', 'success');
        },

        // Otorgar / quitar permisos de gestión (pueden tenerlos varios)
        toggleJefeOperario(operarioId) {
            const operario = (this.cuadrilla.operarios || []).find(op => Number(op.id) === Number(operarioId));
            if (!operario) return;

            if (Number(operario.es_jefe) === 1) {
                operario.es_jefe = 0;
                this.mostrarMensaje('Permisos de gestión removidos', 'warning');
                return;
            }

            operario.es_jefe = 1;
            this.mostrarMensaje('Permisos de gestión otorgados', 'success');
        },

        esJefeOperario(operarioId) {
            const operario = (this.cuadrilla.operarios || []).find(op => Number(op.id) === Number(operarioId));
            return Number(operario?.es_jefe) === 1;
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
                            <div class="modal-content cuadrilla-edit reclamo-confirm-modal">
                                <div class="cuadrilla-edit__header">
                                    <div class="cuadrilla-edit__title">
                                        <span class="cuadrilla-edit__title-icon"><i class="bi bi-question-circle"></i></span>
                                        <h5>${titulo}</h5>
                                    </div>
                                    <button type="button" class="cuadrilla-edit__close" data-bs-dismiss="modal" aria-label="Cerrar">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p class="reclamo-confirm-modal__message">${mensaje}</p>
                                </div>
                                <div class="cuadrilla-edit__footer reclamo-modal__footer--end">
                                    <button type="button" class="ce-btn-cancelar" data-bs-dismiss="modal" id="btnCancelar">Cancelar</button>
                                    <button type="button" class="ce-btn-guardar" id="btnConfirmar"><i class="bi bi-check-lg"></i> Confirmar</button>
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
        },

        mostrarMensaje(mensaje, tipo) {
            // Crear y mostrar un toast o alert
            const alertClass = tipo === 'success' ? 'alert-success' : 
                              tipo === 'warning' ? 'alert-warning' : 'alert-danger';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show mensaje-notificacion" role="alert">
                    <div class="mensaje-notificacion__body">${mensaje}</div>
                    <button type="button" class="btn-close mensaje-notificacion__close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
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
        cuadrillasMostradas() {
            const termino = (this.filtroBusqueda || '').trim().toLowerCase();
            if (!termino) {
                return this.cuadrillas;
            }
            return this.cuadrillas.filter(c => {
                const nombre = (c.nombre || '').toLowerCase();
                const descripcion = (c.descripcion || '').toLowerCase();
                const operarios = (c.operarios || []).map(op => (op.nombre || '').toLowerCase()).join(' ');
                return nombre.includes(termino) || descripcion.includes(termino) || operarios.includes(termino);
            });
        },
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
        },
        // Operarios disponibles filtrados por el buscador del modal de edición
        operariosDisponiblesFiltrados() {
            const termino = (this.filtroDisponibles || '').trim().toLowerCase();
            const lista = this.operariosDisponiblesParaEdicion || [];
            if (!termino) return lista;
            return lista.filter(op => {
                const nombre = (op.nombre || '').toLowerCase();
                const legajo = (op.legajo || '').toString().toLowerCase();
                const email = (op.email || '').toLowerCase();
                return nombre.includes(termino) || legajo.includes(termino) || email.includes(termino);
            });
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