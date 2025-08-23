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
            archivoSeleccionado: null,
            itemsImport: [],
        };
    },
    methods: {
        async obtenerMateriales() {
            try {
                const resp = await axios.get(BASE_URL + 'api/materiales');
                this.materiales = resp.data;
                this.$nextTick(() => this.inicializarTabla());
            } catch (e) {
                console.error('Error al obtener materiales', e);
                alert('No se pudieron cargar los materiales');
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
                // Destruye cualquier instancia previa para evitar errores
                if ($.fn.DataTable.isDataTable('#tabla_tipos')) {
                    $('#tabla_tipos').DataTable().destroy();
                }

                // Inicializa la tabla de tipos con DataTables
                $('#tabla_tipos').DataTable({
                    // Opciones de configuración si es necesario
                });
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
                this.tabla.destroy();
            }
            this.tabla = $('#tabla_materiales').DataTable({
                data: this.materiales,
                responsive: true,
                columns: [
                    { data: 'nombre' },
                    { data: 'cantidad' },
                    { 
                        data: 'tipo_nombre',
                        defaultContent: 'Sin tipo'
                    },
                    { 
                        data: null,
                        render: (data, type, row) => {
                            return `
                                <button class="btn btn-sm btn-warning me-1 editar-material" data-id="${row.id}" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger eliminar-material" data-id="${row.id}" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }
                    }
                ]
            });

            const tableInstance = this.tabla;
            const vueApp = this;
            $('#tabla_materiales').on('click', '.editar-material', function() {
                const data = tableInstance.row($(this).closest('tr')).data();
                vueApp.editarMaterial(data);
            });

            $('#tabla_materiales').on('click', '.eliminar-material', function() {
                const data = tableInstance.row($(this).closest('tr')).data();
                vueApp.eliminarMaterial(data);
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
            try {
                await axios[metodo](url, this.material);
                await this.obtenerMateriales();
                bootstrap.Modal.getInstance(document.getElementById('modalMaterial')).hide();
            } catch (e) {
                console.error('Error al guardar material', e);
                alert('No se pudo guardar el material');
            }
        },
        
        async guardarTipo() {
            try {
                await axios.post(BASE_URL + 'api/materiales/tipos', this.tipo);
                await this.obtenerTiposMaterial();
                this.tipo.nombre = ''; // Limpiar el input
            } catch (e) {
                console.error('Error al guardar tipo', e);
                alert('No se pudo guardar el tipo de material');
            }
        },

        async eliminarMaterial(item) {
            if (!confirm(`¿Seguro que deseas eliminar "${item.nombre}"?`)) return;
            try {
                await axios.delete(BASE_URL + 'api/materiales/' + item.id);
                await this.obtenerMateriales();
            } catch (e) {
                console.error('Error al eliminar material', e);
                alert('No se pudo eliminar el material');
            }
        },

        onArchivoSeleccionado(event) {
            const input = event?.target || document.getElementById('inputArchivoMateriales');
            this.archivoSeleccionado = input.files && input.files[0] ? input.files[0] : null;
            this.itemsImport = [];
        },

        async importarArchivo() {
            if (!this.archivoSeleccionado) return;
            const file = this.archivoSeleccionado;
            const nombre = (file.name || '').toLowerCase();

            try {
                if (nombre.endsWith('.csv')) {
                    await this.procesarCSV(file);
                } else if (nombre.endsWith('.xlsx') || nombre.endsWith('.xls')) {
                    await this.ensureXLSX();
                    await this.procesarExcel(file);
                } else {
                    alert('Formato no soportado. Sube un CSV o Excel.');
                    return;
                }

                if (this.itemsImport.length === 0) {
                    alert('No se encontraron filas válidas para importar.');
                    return;
                }

                const resp = await axios.post(BASE_URL + 'api/materiales/import', { items: this.itemsImport });
                console.log('Import result:', resp.data);
                alert(`Importación completada. Insertados: ${resp.data.insertados}. ${resp.data.errores?.length ? 'Errores: ' + resp.data.errores.length : ''}`);
                this.archivoSeleccionado = null;
                document.getElementById('inputArchivoMateriales').value = '';
                await this.obtenerMateriales();
            } catch (e) {
                console.error('Error al importar archivo', e);
                alert('No se pudo importar el archivo. Verifica el formato.');
            }
        },

        async procesarCSV(file) {
            const texto = await file.text();
            const lineas = texto.split(/\r?\n/).filter(l => l.trim() !== '');
            if (lineas.length === 0) return;

            const primera = lineas[0].split(',').map(h => h.trim().toLowerCase());
            const tieneHeader = primera.includes('nombre') && primera.includes('cantidad') && primera.includes('tipo');
            const inicio = tieneHeader ? 1 : 0;
            
            for (let i = inicio; i < lineas.length; i++) {
                const cols = lineas[i].split(',');
                if (cols.length < 3) continue; // Ahora se esperan 3 columnas
                
                const nombre = String(cols[0] || '').trim();
                const cantidad = parseInt(cols[1], 10);
                const tipo = String(cols[2] || '').trim(); // Nuevo campo
                
                if (nombre !== '' && !Number.isNaN(cantidad) && cantidad >= 0 && tipo !== '') {
                    this.itemsImport.push({ nombre, cantidad, tipo });
                }
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
                
                if (nombre !== '' && !Number.isNaN(cantidad) && cantidad >= 0 && tipo !== '') {
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
            if (!confirm(`¿Seguro que deseas eliminar el tipo "${tipo.nombre}"?`)) return;

            try {
                const url = BASE_URL + 'api/materiales/tipos/' + tipo.id;
                await axios.delete(url);
                await this.obtenerTiposMaterial();
                alert('Tipo de material eliminado con éxito.');
            } catch (e) {
                console.error('Error al eliminar tipo', e);
                alert('No se pudo eliminar el tipo de material. Asegúrate de que no haya materiales asociados a este tipo.');
            }
        }
    },
    mounted() {
        this.obtenerTiposMaterial();
        this.obtenerMateriales();
    }
});