const app = Vue.createApp({
    data() {
        return {
            materiales: [],
            tiposMaterial: [],
            material: {
                nombre: '',
                cantidad: 0,
                idTipo: ''
            },
            tipo: {
                nombre: ''
            },
            tabla: null,
            tablaTipos: null,
            archivoSeleccionado: null,
            itemsImport: [],
        };
    },
    methods: {
        async obtenerMateriales() {
            try {
                const urlMateriales = BASE_URL + 'api/materiales';
                console.log('URL Materiales:', urlMateriales);

                const response = await axios.get(urlMateriales);
                console.log('Respuesta de la API materiales:', response.data);
                this.materiales = response.data;
                console.log('Materiales después de asignar:', this.materiales);
                
                // Asegurarse de que el DOM esté actualizado antes de inicializar DataTables
                this.$nextTick(() => {
                    console.log('Inicializando tabla con materiales:', this.materiales);
                    this.inicializarTabla();
                });
            } catch (error) {
                console.error('Error al obtener datos:', error);
                console.error('URL que falló:', error.config?.url);
            }
        },

        async obtenerTiposMaterial() {
            try {
                const resp = await axios.get(BASE_URL + 'api/materiales/tipos');
                this.tiposMaterial = resp.data;
            } catch (e) {
                console.error('Error al obtener tipos de materiales', e);
                alert('No se pudieron cargar los tipos de materiales');
            }
        },

        abrirModalTipos() {
            // Muestra el modal de tipos
            var modalTipos = new bootstrap.Modal(document.getElementById('modalTiposMateriales'));
            modalTipos.show();

            // Espera a que el modal esté visible para inicializar la tabla
            // Usamos $nextTick para asegurarnos de que el DOM se actualizó
            this.$nextTick(() => {
                this.inicializarTablaTipos();
            });
        },

        toggleTiposMateriales() {
            this.mostrarTipos = !this.mostrarTipos;
            if (this.mostrarTipos) {
                this.obtenerTiposMaterial();
            }
        },
        

        inicializarTabla() {
            if (this.tabla) {
                console.log('Destruyendo tabla anterior');
                this.tabla.destroy();
            }
            console.log('Creando nueva tabla con datos:', this.materiales);
            this.tabla = $('#tabla_materiales').DataTable({
                data: this.materiales,
                responsive: true,
                columns: [
                    { 
                        data: 'nombre',
                        className: 'text-start'
                    },
                    { 
                        data: 'cantidad',
                        className: 'text-start'
                    },
                    { 
                        data: 'tipo_nombre',
                        defaultContent: 'Sin tipo',
                        className: 'text-start'
                    },
                    { 
                        data: null,
                        className: 'text-start',
                        render: (data, type, row) => {
                            return `
                                <button class="btn btn-sm btn-warning me-1 editar-material" data-id="${row.id}" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger eliminar-material" data-id="${row.id}" title="Eliminar">
                                    <i class="bi bi-trash text-white"></i>
                                </button>
                            `;
                        }
                    }
                ]
            });

            // Configurar eventos directamente (igual que en reclamos)
            $('#tabla_materiales tbody').off('click', '.editar-material').on('click', '.editar-material', (e) => {
                const id = $(e.currentTarget).data('id');
                const material = this.materiales.find(m => m.id == id);
                if (material) this.editarMaterial(material);
            });
            $('#tabla_materiales tbody').off('click', '.eliminar-material').on('click', '.eliminar-material', (e) => {
                const id = $(e.currentTarget).data('id');
                const material = this.materiales.find(m => m.id == id);
                if (material) this.eliminarMaterial(material);
            });
        },

        inicializarTablaTipos() {
            if (this.tablaTipos) {
                console.log('Destruyendo tabla de tipos anterior');
                this.tablaTipos.destroy();
            }
            console.log('Creando nueva tabla de tipos con datos:', this.tiposMaterial);
            this.tablaTipos = $('#tabla_tipos').DataTable({
                data: this.tiposMaterial,
                responsive: true,
                columns: [
                    { 
                        data: 'id',
                        className: 'text-start'
                    },
                    { 
                        data: 'nombre',
                        className: 'text-start'
                    },
                    { 
                        data: null,
                        className: 'text-start',
                        render: (data, type, row) => {
                            return `
                                <button class="btn btn-sm btn-danger eliminar-tipo" data-id="${row.id}" title="Eliminar">
                                    <i class="bi bi-trash text-white"></i>
                                </button>
                            `;
                        }
                    }
                ]
            });

            // Configurar eventos directamente
            $('#tabla_tipos tbody').off('click', '.eliminar-tipo').on('click', '.eliminar-tipo', (e) => {
                const id = $(e.currentTarget).data('id');
                const tipo = this.tiposMaterial.find(t => t.id == id);
                if (tipo) this.eliminarTipo(tipo);
            });
        },

        abrirFormulario() {
            this.material = { nombre: '', cantidad: 0, idTipo: '' };
            new bootstrap.Modal(document.getElementById('modalMaterial')).show();
        },

        
        abrirFormularioTipo() {
            this.tipo = { nombre: '' };
            new bootstrap.Modal(document.getElementById('modalTipoMaterial')).show();
        },

        editarMaterial(item) {
            this.material = { ...item };
            new bootstrap.Modal(document.getElementById('modalMaterial')).show();
        },

        async guardarMaterial() {
            const esNuevo = !this.material.id;
            const url = BASE_URL + 'api/materiales' + (esNuevo ? '' : '/' + this.material.id);
            const metodo = esNuevo ? 'post' : 'put';
            
            // Preparar datos para envío
            const datosEnvio = { ...this.material };
            
            // Si no hay tipo seleccionado, permitir crear con "Sin tipo"
            if (!datosEnvio.idTipo || datosEnvio.idTipo === '') {
                datosEnvio.idTipo = null; // o el valor que represente "Sin tipo" en tu API
            }
            
            axios[metodo](url, datosEnvio).then(() => {
                // Mensaje de éxito
                if (esNuevo) {
                    this.mostrarMensaje(`Material "${this.material.nombre}" creado exitosamente`, 'success');
                } else {
                    this.mostrarMensaje(`Material "${this.material.nombre}" editado exitosamente`, 'success');
                }
                
                // Actualizar la tabla ANTES de cerrar el modal (igual que en reclamos)
                this.obtenerMateriales();
                bootstrap.Modal.getInstance(document.getElementById('modalMaterial')).hide();
            }).catch(error => {
                console.error('Error al guardar material:', error);
                
                // Mensaje de error
                if (esNuevo) {
                    this.mostrarMensaje('Error al crear el material', 'error');
                } else {
                    this.mostrarMensaje('Error al editar el material', 'error');
                }
            });
        },
        
        async guardarTipo() {
            try {
                await axios.post(BASE_URL + 'api/materiales/tipos', this.tipo);
                
                // Mensaje de éxito
                this.mostrarMensaje(`Tipo "${this.tipo.nombre}" creado exitosamente`, 'success');
                
                // Actualizar los datos y la tabla
                await this.obtenerTiposMaterial();
                this.tipo.nombre = ''; // Limpiar el input
                
                // Actualizar la tabla de tipos si está inicializada
                if (this.tablaTipos) {
                    this.$nextTick(() => {
                        this.inicializarTablaTipos();
                    });
                }
            } catch (e) {
                console.error('Error al guardar tipo', e);
                this.mostrarMensaje('Error al crear el tipo de material', 'error');
            }
        },

        async eliminarMaterial(item) {
            // Confirmación personalizada
            const mensajeConfirmacion = `¿Está seguro que desea eliminar el material "${item.nombre}"?`;
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Eliminar Material');
            
            if (!confirmacion) {
                return;
            }

            try {
                await axios.delete(BASE_URL + 'api/materiales/' + item.id);
                
                // Mensaje de éxito
                this.mostrarMensaje(`Material "${item.nombre}" eliminado exitosamente`, 'success');
                
                // Actualizar la tabla después de eliminar (igual que en reclamos)
                this.obtenerMateriales();
            } catch (e) {
                console.error('Error al eliminar material', e);
                this.mostrarMensaje('Error al eliminar el material', 'error');
            }
        },

        onArchivoSeleccionado(event) {
            const input = event?.target || document.getElementById('inputArchivoMateriales');
            this.archivoSeleccionado = input.files && input.files[0] ? input.files[0] : null;
            this.itemsImport = [];
        },

        async importarArchivo() {
            if (!this.archivoSeleccionado) {
                this.mostrarMensaje('Debe seleccionar un archivo para importar', 'warning');
                return;
            }
            
            const file = this.archivoSeleccionado;
            const nombre = (file.name || '').toLowerCase();

            try {
                console.log('Iniciando importación de archivo:', file.name);
                
                if (nombre.endsWith('.csv')) {
                    await this.procesarCSV(file);
                } else if (nombre.endsWith('.xlsx') || nombre.endsWith('.xls')) {
                    await this.ensureXLSX();
                    await this.procesarExcel(file);
                } else {
                    this.mostrarMensaje('Formato no soportado. Sube un CSV o Excel.', 'warning');
                    return;
                }

                console.log('Items procesados para importar:', this.itemsImport);

                if (this.itemsImport.length === 0) {
                    this.mostrarMensaje('No se encontraron filas válidas para importar. Verifica que el archivo tenga la estructura correcta: nombre, cantidad, tipo', 'warning');
                    return;
                }

                // Confirmación antes de importar
                const mensajeConfirmacion = `¿Está seguro que desea importar ${this.itemsImport.length} materiales?`;
                const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Importar Materiales');
                
                if (!confirmacion) {
                    return;
                }

                // Mensaje de progreso
                this.mostrarMensaje('Importando materiales', 'info');

                console.log('Enviando datos a la API...');
                const resp = await axios.post(BASE_URL + 'api/materiales/import', { items: this.itemsImport });
                console.log('Respuesta de la API:', resp.data);
                
                // Mensaje de éxito con detalles
                let mensajeExito = `Importación completada exitosamente<br>
                    <strong>Materiales importados:</strong> ${resp.data.insertados}`;
                
                if (resp.data.errores && resp.data.errores.length > 0) {
                    mensajeExito += `<br><strong>Errores:</strong> ${resp.data.errores.length}`;
                }
                
                this.mostrarMensaje(mensajeExito, 'success');
                
                this.archivoSeleccionado = null;
                document.getElementById('inputArchivoMateriales').value = '';
                
                // Limpiar items de importación
                this.itemsImport = [];
                
                // Actualizar la tabla después de la importación (igual que en reclamos)
                this.obtenerMateriales();
            } catch (e) {
                console.error('Error al importar archivo', e);
                let mensajeError = 'Error al importar el archivo';
                
                if (e.response && e.response.data) {
                    console.error('Error del servidor:', e.response.data);
                    if (e.response.data.messages) {
                        mensajeError += ': ' + JSON.stringify(e.response.data.messages);
                    } else if (e.response.data.message) {
                        mensajeError += ': ' + e.response.data.message;
                    }
                }
                
                this.mostrarMensaje(mensajeError, 'error');
            }
        },

        async procesarCSV(file) {
            try {
                const texto = await file.text();
                console.log('Contenido CSV:', texto.substring(0, 200) + '...');
                
                const lineas = texto.split(/\r?\n/).filter(l => l.trim() !== '');
                console.log('Líneas encontradas:', lineas.length);
                
                if (lineas.length === 0) {
                    console.log('Archivo CSV vacío');
                    return;
                }

                // Detectar separador (coma o punto y coma)
                const primeraLinea = lineas[0];
                const separador = primeraLinea.includes(';') ? ';' : ',';
                console.log('Separador detectado:', separador);

                const primera = primeraLinea.split(separador).map(h => h.trim().toLowerCase());
                console.log('Primera línea:', primera);
                
                const tieneHeader = primera.includes('nombre') && primera.includes('cantidad') && primera.includes('tipo');
                console.log('Tiene header:', tieneHeader);
                
                const inicio = tieneHeader ? 1 : 0;
                
                for (let i = inicio; i < lineas.length; i++) {
                    const cols = lineas[i].split(separador);
                    console.log(`Procesando línea ${i + 1}:`, cols);
                    
                    if (cols.length < 3) {
                        console.log(`Línea ${i + 1} ignorada: menos de 3 columnas`);
                        continue;
                    }
                    
                    const nombre = String(cols[0] || '').trim();
                    const cantidad = parseInt(cols[1], 10);
                    const tipo = String(cols[2] || '').trim();
                    
                    console.log(`Línea ${i + 1} procesada:`, { nombre, cantidad, tipo });
                    
                    if (nombre !== '' && !Number.isNaN(cantidad) && cantidad >= 0) {
                        this.itemsImport.push({ nombre, cantidad, tipo });
                        console.log(`Línea ${i + 1} agregada a import`);
                    } else {
                        console.log(`Línea ${i + 1} rechazada: datos inválidos`);
                    }
                }
                
                console.log('Total items para importar:', this.itemsImport.length);
            } catch (error) {
                console.error('Error procesando CSV:', error);
                throw error;
            }
        },

        async procesarExcel(file) {
            const data = await file.arrayBuffer();
            const workbook = XLSX.read(data, { type: 'array' });
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const json = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
            if (!Array.isArray(json) || json.length === 0) return;

            const headers = (json[0] || []).map(h => String(h || '').trim().toLowerCase());
            const tieneHeader = headers.includes('nombre') && headers.includes('cantidad') && headers.includes('tipo');
            const inicio = tieneHeader ? 1 : 0;
            
            for (let i = inicio; i < json.length; i++) {
                const row = json[i] || [];
                const nombre = String((row[0] ?? '')).trim();
                const cantidadRaw = row[1] ?? 0;
                const cantidad = parseInt(cantidadRaw, 10);
                const tipo = String((row[2] ?? '')).trim();
                
                if (nombre !== '' && !Number.isNaN(cantidad) && cantidad >= 0) {
                    this.itemsImport.push({ nombre, cantidad, tipo });
                }
            }
        },
        async ensureXLSX() {
            if (typeof XLSX !== 'undefined') return;
            await new Promise((resolve, reject) => {
                const existing = document.querySelector('script[data-loader="xlsx"]');
                if (existing) {
                    existing.addEventListener('load', () => resolve());
                    existing.addEventListener('error', () => reject(new Error('No se pudo cargar XLSX.')));
                    return;
                }
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js';
                s.async = true;
                s.setAttribute('data-loader', 'xlsx');
                s.onload = () => resolve();
                s.onerror = () => reject(new Error('No se pudo cargar XLSX.'));
                document.head.appendChild(s);
            });
        },

        async eliminarTipo(tipo) {
            // Confirmación personalizada
            const mensajeConfirmacion = `¿Está seguro que desea eliminar el tipo "${tipo.nombre}"?`;
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Eliminar Tipo');
            
            if (!confirmacion) {
                return;
            }

            try {
                const url = BASE_URL + 'api/materiales/tipos/' + tipo.id;
                await axios.delete(url);
                
                // Mensaje de éxito
                this.mostrarMensaje(`Tipo "${tipo.nombre}" eliminado exitosamente`, 'success');
                
                // Actualizar los datos y la tabla
                await this.obtenerTiposMaterial();
                
                // Actualizar la tabla de tipos si está inicializada
                if (this.tablaTipos) {
                    this.$nextTick(() => {
                        this.inicializarTablaTipos();
                    });
                }
            } catch (e) {
                console.error('Error al eliminar tipo', e);
                this.mostrarMensaje('Error al eliminar el tipo de material. Asegúrate de que no haya materiales asociados a este tipo.', 'error');
            }
        },

        /**
         * Muestra mensajes de notificación estilo cuadrillas
         */
        mostrarMensaje(mensaje, tipo) {
            // Si es un mensaje de éxito, eliminar mensajes de progreso anteriores
            if (tipo === 'success') {
                $('.alert-info').fadeOut(200, function() {
                    $(this).remove();
                });
            }
            
            // Crear y mostrar un toast o alert
            const alertClass = tipo === 'success' ? 'alert-success' : 
                              tipo === 'warning' ? 'alert-warning' : 
                              tipo === 'info' ? 'alert-info' : 'alert-danger';
            
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
        this.obtenerTiposMaterial();
        this.obtenerMateriales();
    }
});