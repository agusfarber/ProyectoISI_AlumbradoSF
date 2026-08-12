const app = Vue.createApp({
    data() {
        return {
            materiales: [],
            tiposMaterial: [],
            material: {
                nombre: '',
                idTipo: '',
                foto: null,
            },
            fotoArchivo: null,
            fotoPreviewLocal: null,
            quitarFotoActual: false,
            tipo: {
                nombre: '',
                icono: 'bi bi-box-seam',
                color: '#2a9d8f',
            },
            tipoEdicion: {
                id: null,
                nombre: '',
                icono: 'bi bi-box-seam',
                color: '#2a9d8f',
            },
            iconosDisponibles: [
                'bi bi-box-seam',
                'bi bi-lightbulb',
                'bi bi-lamp',
                'bi bi-brightness-high',
                'bi bi-lightning-charge',
                'bi bi-bezier2',
                'bi bi-signpost-2',
                'bi bi-hammer',
                'bi bi-tools',
                'bi bi-wrench',
                'bi bi-nut',
                'bi bi-cpu',
                'bi bi-toggle-on',
                'bi bi-plugin',
                'bi bi-battery-charging',
                'bi bi-paint-bucket',
                'bi bi-droplet',
                'bi bi-gear',
                'bi bi-stack',
                'bi bi-grid-3x3-gap',
                'bi bi-building',
                'bi bi-truck',
                'bi bi-shield-check',
                'bi bi-folder',
            ],
            coloresDisponibles: [
                '#2a9d8f',
                '#3a3972',
                '#f0a202',
                '#e76f51',
                '#457b9d',
                '#bc6c25',
                '#118ab2',
                '#ef476f',
                '#06d6a0',
                '#264653',
                '#6c757d',
                '#9b2226',
            ],
            archivoSeleccionado: null,
            itemsImport: [],
            importDragOver: false,
            importando: false,
            filtroBusqueda: '',
            vista: 'categorias', // 'categorias' | 'detalle'
            categoriaActivaKey: null,
        };
    },

    computed: {
        categorias() {
            const porTipo = new Map();

            this.tiposMaterial.forEach((t) => {
                porTipo.set(String(t.id), {
                    key: String(t.id),
                    idTipo: t.id,
                    nombre: t.nombre,
                    materiales: [],
                    icono: this.iconoDeTipo(t),
                    color: this.colorDeTipo(t),
                });
            });

            const sinCategoria = {
                key: 'sin',
                idTipo: null,
                nombre: 'Sin categoría',
                materiales: [],
                icono: 'bi bi-folder',
                color: '#6c757d',
            };

            this.materiales.forEach((m) => {
                const idTipo = m.idTipo ?? m.id_tipo ?? null;
                if (idTipo === null || idTipo === '' || idTipo === undefined) {
                    sinCategoria.materiales.push(m);
                    return;
                }
                const key = String(idTipo);
                if (!porTipo.has(key)) {
                    const nombre = m.tipo_nombre || 'Categoría';
                    porTipo.set(key, {
                        key,
                        idTipo,
                        nombre,
                        materiales: [],
                        icono: this.iconoCategoria(nombre),
                        color: '#6c757d',
                    });
                }
                porTipo.get(key).materiales.push(m);
            });

            const lista = Array.from(porTipo.values()).map((cat) => ({
                ...cat,
                cantidadMateriales: cat.materiales.length,
            }));

            lista.sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'));

            if (sinCategoria.materiales.length > 0) {
                lista.push({
                    ...sinCategoria,
                    cantidadMateriales: sinCategoria.materiales.length,
                });
            }

            return lista;
        },

        categoriasMostradas() {
            const termino = (this.filtroBusqueda || '').trim().toLowerCase();
            if (!termino || this.vista !== 'categorias') {
                return this.categorias;
            }
            return this.categorias.filter((c) => c.nombre.toLowerCase().includes(termino));
        },

        categoriaActiva() {
            if (!this.categoriaActivaKey) {
                return {
                    key: null,
                    nombre: '',
                    icono: 'bi bi-folder',
                    color: '#6c757d',
                    materiales: [],
                };
            }
            return (
                this.categorias.find((c) => c.key === this.categoriaActivaKey) || {
                    key: this.categoriaActivaKey,
                    nombre: 'Categoría',
                    icono: 'bi bi-folder',
                    color: '#6c757d',
                    materiales: [],
                }
            );
        },

        materialesDeCategoria() {
            return this.categoriaActiva.materiales || [];
        },

        materialesDeCategoriaFiltrados() {
            const termino = (this.filtroBusqueda || '').trim().toLowerCase();
            const lista = this.materialesDeCategoria;
            if (!termino || this.vista !== 'detalle') return lista;
            return lista.filter((m) => (m.nombre || '').toLowerCase().includes(termino));
        },

        fotoPreviewUrl() {
            if (this.fotoPreviewLocal) return this.fotoPreviewLocal;
            if (!this.quitarFotoActual && this.material?.foto) {
                return this.urlFotoMaterial(this.material.foto);
            }
            return null;
        },
    },

    methods: {
        iconoDeTipo(tipo) {
            if (tipo && tipo.icono && /^bi bi-[a-z0-9-]+$/.test(tipo.icono)) {
                return tipo.icono;
            }
            return this.iconoCategoria(tipo?.nombre || '');
        },

        colorDeTipo(tipo) {
            if (tipo && tipo.color && /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(tipo.color)) {
                return tipo.color;
            }
            return '#6c757d';
        },

        iconoCategoria(nombre) {
            const n = (nombre || '').toLowerCase();
            if (/lumin|lampar|led|foco/.test(n)) return 'bi bi-lightbulb';
            if (/cable|conductor/.test(n)) return 'bi bi-bezier2';
            if (/poste|columna/.test(n)) return 'bi bi-signpost-2';
            if (/brazo|soporte|ménsula|mensula/.test(n)) return 'bi bi-hammer';
            if (/fotocel|sensor|rele|relé/.test(n)) return 'bi bi-cpu';
            if (/fusible|llave|tablero|breaker/.test(n)) return 'bi bi-toggle-on';
            if (/bulon|tornillo|tuerca|herraje/.test(n)) return 'bi bi-nut';
            if (/pintura|esmalte/.test(n)) return 'bi bi-paint-bucket';
            return 'bi bi-box-seam';
        },

        cantidadEnTipo(idTipo) {
            return this.materiales.filter((m) => String(m.idTipo ?? m.id_tipo) === String(idTipo)).length;
        },

        abrirCategoria(cat) {
            this.categoriaActivaKey = cat.key;
            this.vista = 'detalle';
            this.filtroBusqueda = '';
        },

        volverACategorias() {
            this.vista = 'categorias';
            this.categoriaActivaKey = null;
            this.filtroBusqueda = '';
        },

        abrirModalCategorias() {
            this.tipo = {
                nombre: '',
                icono: 'bi bi-box-seam',
                color: '#2a9d8f',
            };
            new bootstrap.Modal(document.getElementById('modalTiposMateriales')).show();
        },

        editarCategoria(cat) {
            if (cat.idTipo == null) return;
            const tipo = this.tiposMaterial.find((t) => String(t.id) === String(cat.idTipo));
            if (!tipo) return;
            this.abrirModalEditarCategoria(tipo);
        },

        abrirModalEditarCategoria(tipo) {
            this.tipoEdicion = {
                id: tipo.id,
                nombre: tipo.nombre || '',
                icono: this.iconoDeTipo(tipo),
                color: this.colorDeTipo(tipo),
            };
            this.$nextTick(() => {
                new bootstrap.Modal(document.getElementById('modalEditarCategoria')).show();
            });
        },

        async guardarCategoriaEditada() {
            if (!this.tipoEdicion.id) return;
            const nombre = (this.tipoEdicion.nombre || '').trim();
            if (!nombre) {
                this.mostrarMensaje('El nombre de la categoría es obligatorio', 'warning');
                return;
            }

            try {
                await axios.put(BASE_URL + 'api/materiales/tipos/' + this.tipoEdicion.id, {
                    nombre,
                    icono: this.tipoEdicion.icono || 'bi bi-box-seam',
                    color: this.tipoEdicion.color || '#6c757d',
                });
                this.mostrarMensaje(`Categoría "${nombre}" actualizada`, 'success');
                await this.obtenerTiposMaterial();
                await this.obtenerMateriales();
                bootstrap.Modal.getInstance(document.getElementById('modalEditarCategoria'))?.hide();
            } catch (e) {
                console.error('Error al actualizar categoría', e);
                const msg = e.response?.data?.messages || e.response?.data?.message || 'Error al actualizar la categoría';
                this.mostrarMensaje(typeof msg === 'string' ? msg : 'Error al actualizar la categoría', 'error');
            }
        },

        async eliminarDesdeEdicion() {
            if (!this.tipoEdicion.id) return;
            const tipo = {
                id: this.tipoEdicion.id,
                nombre: this.tipoEdicion.nombre,
            };
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarCategoria'));
            if (modal) modal.hide();
            await this.eliminarTipo(tipo);
        },

        async obtenerMateriales() {
            try {
                const response = await axios.get(BASE_URL + 'api/materiales');
                this.materiales = response.data || [];
            } catch (error) {
                console.error('Error al obtener materiales:', error);
                this.mostrarMensaje('No se pudieron cargar los materiales', 'error');
            }
        },

        async obtenerTiposMaterial() {
            try {
                const resp = await axios.get(BASE_URL + 'api/materiales/tipos');
                this.tiposMaterial = resp.data || [];
            } catch (e) {
                console.error('Error al obtener tipos de materiales', e);
                this.mostrarMensaje('No se pudieron cargar las categorías', 'error');
            }
        },

        urlFotoMaterial(nombreArchivo) {
            if (!nombreArchivo) return '';
            return BASE_URL + 'static/uploads/materiales/' + nombreArchivo;
        },

        resetFotoFormulario() {
            if (this.fotoPreviewLocal) {
                URL.revokeObjectURL(this.fotoPreviewLocal);
            }
            this.fotoArchivo = null;
            this.fotoPreviewLocal = null;
            this.quitarFotoActual = false;
            const input = document.getElementById('inputFotoMaterial');
            if (input) input.value = '';
        },

        onFotoSeleccionada(event) {
            const file = event?.target?.files?.[0] || null;
            if (!file) return;

            const tipos = ['image/jpeg', 'image/png', 'image/webp'];
            if (!tipos.includes(file.type)) {
                this.mostrarMensaje('Formato no permitido. Usá JPG, PNG o WEBP.', 'warning');
                event.target.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                this.mostrarMensaje('La imagen no debe superar los 2 MB.', 'warning');
                event.target.value = '';
                return;
            }

            if (this.fotoPreviewLocal) {
                URL.revokeObjectURL(this.fotoPreviewLocal);
            }
            this.fotoArchivo = file;
            this.fotoPreviewLocal = URL.createObjectURL(file);
            this.quitarFotoActual = false;
        },

        quitarFotoSeleccionada() {
            if (this.fotoPreviewLocal) {
                URL.revokeObjectURL(this.fotoPreviewLocal);
            }
            this.fotoArchivo = null;
            this.fotoPreviewLocal = null;
            this.quitarFotoActual = true;
            const input = document.getElementById('inputFotoMaterial');
            if (input) input.value = '';
        },

        async subirFotoMaterial(id) {
            if (!this.fotoArchivo || !id) return;
            const fd = new FormData();
            fd.append('foto', this.fotoArchivo);
            await axios.post(BASE_URL + 'api/materiales/' + id + '/foto', fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
        },

        abrirFormulario() {
            this.resetFotoFormulario();
            this.material = {
                nombre: '',
                idTipo: this.vista === 'detalle' && this.categoriaActiva.idTipo != null
                    ? this.categoriaActiva.idTipo
                    : '',
                foto: null,
            };
            new bootstrap.Modal(document.getElementById('modalMaterial')).show();
        },

        abrirFormularioEnCategoria() {
            this.resetFotoFormulario();
            this.material = {
                nombre: '',
                idTipo: this.categoriaActiva.idTipo != null ? this.categoriaActiva.idTipo : '',
                foto: null,
            };
            new bootstrap.Modal(document.getElementById('modalMaterial')).show();
        },

        editarMaterial(item) {
            this.resetFotoFormulario();
            this.material = {
                ...item,
                idTipo: item.idTipo ?? item.id_tipo ?? '',
            };
            new bootstrap.Modal(document.getElementById('modalMaterial')).show();
        },

        async guardarMaterial() {
            const esNuevo = !this.material.id;

            if (esNuevo) {
                // Siempre asignar la categoría activa al crear
                this.material.idTipo =
                    this.categoriaActiva.idTipo != null ? this.categoriaActiva.idTipo : null;

                const nombreMaterial = (this.material.nombre || '').trim();
                if (nombreMaterial) {
                    try {
                        const responseVerificacion = await axios.get(BASE_URL + 'api/materiales/verificar', {
                            params: { nombre: nombreMaterial },
                        });
                        if (responseVerificacion.data.existe) {
                            this.mostrarMensaje(
                                `El material "${nombreMaterial}" ya existe. Editá el material existente en lugar de crear uno nuevo.`,
                                'warning'
                            );
                            return;
                        }
                    } catch (error) {
                        console.error('Error al verificar material:', error);
                    }
                }
            }

            const url = BASE_URL + 'api/materiales' + (esNuevo ? '' : '/' + this.material.id);
            const metodo = esNuevo ? 'post' : 'put';
            const datosEnvio = {
                nombre: this.material.nombre,
                idTipo: this.material.idTipo || null,
            };
            if (!esNuevo && this.quitarFotoActual && !this.fotoArchivo) {
                datosEnvio.foto = null;
            }

            try {
                const resp = await axios[metodo](url, datosEnvio);
                const materialId = esNuevo
                    ? (resp.data?.id ?? resp.data?.data?.id)
                    : this.material.id;

                if (this.fotoArchivo && materialId) {
                    await this.subirFotoMaterial(materialId);
                }

                this.mostrarMensaje(
                    esNuevo
                        ? `Material "${this.material.nombre}" creado exitosamente`
                        : `Material "${this.material.nombre}" editado exitosamente`,
                    'success'
                );
                this.resetFotoFormulario();
                await this.obtenerMateriales();
                bootstrap.Modal.getInstance(document.getElementById('modalMaterial'))?.hide();
            } catch (error) {
                console.error('Error al guardar material:', error);
                let mensajeError = 'Error al guardar el material';
                if (error.response?.data?.messages) {
                    mensajeError = error.response.data.messages;
                } else if (error.response?.data?.message) {
                    mensajeError = error.response.data.message;
                }
                this.mostrarMensaje(mensajeError, 'error');
            }
        },

        async guardarTipo() {
            try {
                await axios.post(BASE_URL + 'api/materiales/tipos', {
                    nombre: this.tipo.nombre,
                    icono: this.tipo.icono || 'bi bi-box-seam',
                    color: this.tipo.color || '#2a9d8f',
                });
                this.mostrarMensaje(`Categoría "${this.tipo.nombre}" creada exitosamente`, 'success');
                await this.obtenerTiposMaterial();
                this.tipo = { nombre: '', icono: 'bi bi-box-seam', color: '#2a9d8f' };
                bootstrap.Modal.getInstance(document.getElementById('modalTiposMateriales'))?.hide();
            } catch (e) {
                console.error('Error al guardar tipo', e);
                const msg = e.response?.data?.messages || e.response?.data?.message || 'Error al crear la categoría';
                this.mostrarMensaje(typeof msg === 'string' ? msg : 'Error al crear la categoría', 'error');
            }
        },

        async eliminarMaterial(item) {
            const confirmacion = await this.mostrarConfirmacion(
                `¿Está seguro que desea eliminar el material "${item.nombre}"?`,
                'Eliminar material'
            );
            if (!confirmacion) return;

            try {
                await axios.delete(BASE_URL + 'api/materiales/' + item.id);
                this.mostrarMensaje(`Material "${item.nombre}" eliminado exitosamente`, 'success');
                await this.obtenerMateriales();
            } catch (e) {
                console.error('Error al eliminar material', e);
                this.mostrarMensaje('Error al eliminar el material', 'error');
            }
        },

        async eliminarTipo(tipo) {
            const confirmacion = await this.mostrarConfirmacion(
                `¿Está seguro que desea eliminar la categoría "${tipo.nombre}"?`,
                'Eliminar categoría'
            );
            if (!confirmacion) return;

            try {
                await axios.delete(BASE_URL + 'api/materiales/tipos/' + tipo.id);
                this.mostrarMensaje(`Categoría "${tipo.nombre}" eliminada exitosamente`, 'success');
                await this.obtenerTiposMaterial();
                await this.obtenerMateriales();
                if (this.categoriaActivaKey === String(tipo.id)) {
                    this.volverACategorias();
                }
            } catch (e) {
                console.error('Error al eliminar tipo', e);
                this.mostrarMensaje(
                    'No se pudo eliminar la categoría. Asegurate de que no tenga materiales asociados.',
                    'error'
                );
            }
        },

        abrirModalImportar() {
            this.limpiarArchivo();
            this.importDragOver = false;
            this.importando = false;
            new bootstrap.Modal(document.getElementById('modalImportarMateriales')).show();
        },

        abrirSelectorArchivo() {
            document.getElementById('inputArchivoMateriales')?.click();
        },

        asignarArchivoImport(file) {
            if (!file) return;
            const nombre = (file.name || '').toLowerCase();
            const ok =
                nombre.endsWith('.csv') ||
                nombre.endsWith('.xlsx') ||
                nombre.endsWith('.xls');
            if (!ok) {
                this.mostrarMensaje('Formato no soportado. Subí un CSV o Excel.', 'warning');
                return;
            }
            this.archivoSeleccionado = file;
            this.itemsImport = [];
            const input = document.getElementById('inputArchivoMateriales');
            if (input) input.value = '';
        },

        onArchivoSeleccionado(event) {
            const input = event?.target || document.getElementById('inputArchivoMateriales');
            const file = input.files && input.files[0] ? input.files[0] : null;
            this.asignarArchivoImport(file);
        },

        onImportDragEnter() {
            this.importDragOver = true;
        },

        onImportDragOver() {
            this.importDragOver = true;
        },

        onImportDragLeave(event) {
            if (event.currentTarget.contains(event.relatedTarget)) return;
            this.importDragOver = false;
        },

        onImportDrop(event) {
            this.importDragOver = false;
            const file = event.dataTransfer?.files?.[0] || null;
            this.asignarArchivoImport(file);
        },

        formatoTamanoArchivo(bytes) {
            const n = Number(bytes) || 0;
            if (n < 1024) return n + ' B';
            if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
            return (n / (1024 * 1024)).toFixed(1) + ' MB';
        },

        limpiarArchivo() {
            this.archivoSeleccionado = null;
            this.itemsImport = [];
            this.importDragOver = false;
            const input = document.getElementById('inputArchivoMateriales');
            if (input) input.value = '';
        },

        async importarArchivo() {
            if (!this.archivoSeleccionado) {
                this.mostrarMensaje('Debe seleccionar un archivo para importar', 'warning');
                return;
            }

            const file = this.archivoSeleccionado;
            const nombre = (file.name || '').toLowerCase();
            this.itemsImport = [];

            try {
                this.importando = true;

                if (nombre.endsWith('.csv')) {
                    await this.procesarCSV(file);
                } else if (nombre.endsWith('.xlsx') || nombre.endsWith('.xls')) {
                    await this.ensureXLSX();
                    await this.procesarExcel(file);
                } else {
                    this.mostrarMensaje('Formato no soportado. Subí un CSV o Excel.', 'warning');
                    return;
                }

                if (this.itemsImport.length === 0) {
                    this.mostrarMensaje(
                        'No se encontraron filas válidas. El archivo debe tener: nombre, tipo',
                        'warning'
                    );
                    return;
                }

                const confirmacion = await this.mostrarConfirmacion(
                    `¿Está seguro que desea importar ${this.itemsImport.length} materiales?`,
                    'Importar materiales'
                );
                if (!confirmacion) return;

                this.mostrarMensaje('Importando materiales…', 'info');
                const resp = await axios.post(BASE_URL + 'api/materiales/import', { items: this.itemsImport });

                let mensajeExito = `Importación completada<br><strong>Importados:</strong> ${resp.data.insertados}`;
                if (resp.data.errores?.length) {
                    mensajeExito += `<br><strong>Errores:</strong> ${resp.data.errores.length}`;
                }
                this.mostrarMensaje(mensajeExito, 'success');

                this.limpiarArchivo();
                bootstrap.Modal.getInstance(document.getElementById('modalImportarMateriales'))?.hide();
                await this.obtenerTiposMaterial();
                await this.obtenerMateriales();
            } catch (e) {
                console.error('Error al importar archivo', e);
                let mensajeError = 'Error al importar el archivo';
                if (e.response?.data?.messages) {
                    mensajeError += ': ' + JSON.stringify(e.response.data.messages);
                } else if (e.response?.data?.message) {
                    mensajeError += ': ' + e.response.data.message;
                }
                this.mostrarMensaje(mensajeError, 'error');
            } finally {
                this.importando = false;
            }
        },

        parseFilaImport(cols) {
            const c0 = String(cols[0] ?? '').trim();
            const c1 = String(cols[1] ?? '').trim();
            const c2 = String(cols[2] ?? '').trim();
            if (!c0) return null;

            // Formato nuevo: nombre, tipo
            // Formato viejo (compat): nombre, cantidad, tipo
            const pareceViejo = cols.length >= 3 && c1 !== '' && !Number.isNaN(parseInt(c1, 10));
            if (pareceViejo) {
                return { nombre: c0, tipo: c2 };
            }
            return { nombre: c0, tipo: c1 };
        },

        async procesarCSV(file) {
            const texto = await file.text();
            const lineas = texto.split(/\r?\n/).filter((l) => l.trim() !== '');
            if (lineas.length === 0) return;

            const separador = lineas[0].includes(';') ? ';' : ',';
            const primera = lineas[0].split(separador).map((h) => h.trim().toLowerCase());
            const tieneHeader = primera.includes('nombre') && primera.includes('tipo');
            const inicio = tieneHeader ? 1 : 0;

            for (let i = inicio; i < lineas.length; i++) {
                const cols = lineas[i].split(separador);
                if (cols.length < 2) continue;
                const item = this.parseFilaImport(cols);
                if (item) this.itemsImport.push(item);
            }
        },

        async procesarExcel(file) {
            const data = await file.arrayBuffer();
            const workbook = XLSX.read(data, { type: 'array' });
            const worksheet = workbook.Sheets[workbook.SheetNames[0]];
            const json = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
            if (!Array.isArray(json) || json.length === 0) return;

            const headers = (json[0] || []).map((h) => String(h || '').trim().toLowerCase());
            const tieneHeader = headers.includes('nombre') && headers.includes('tipo');
            const inicio = tieneHeader ? 1 : 0;

            for (let i = inicio; i < json.length; i++) {
                const row = json[i] || [];
                if (row.length < 2) continue;
                const item = this.parseFilaImport(row);
                if (item) this.itemsImport.push(item);
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

        mostrarMensaje(mensaje, tipo) {
            if (tipo === 'success') {
                $('.alert-info').fadeOut(200, function () {
                    $(this).remove();
                });
            }

            const alertClass =
                tipo === 'success'
                    ? 'alert-success'
                    : tipo === 'warning'
                      ? 'alert-warning'
                      : tipo === 'info'
                        ? 'alert-info'
                        : 'alert-danger';

            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show mensaje-notificacion" role="alert">
                    <div class="mensaje-notificacion__body">${mensaje}</div>
                    <button type="button" class="btn-close mensaje-notificacion__close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            `;

            $('body').append(alertHtml);
            setTimeout(() => {
                $('.alert').fadeOut(500, function () {
                    $(this).remove();
                });
            }, 5000);
        },

        mostrarConfirmacion(mensaje, titulo = 'Confirmar acción') {
            return new Promise((resolve) => {
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

                $('#modalConfirmacion').remove();
                $('body').append(modalHtml);
                const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
                modal.show();

                let resuelto = false;
                const finalizar = (valor) => {
                    if (resuelto) return;
                    resuelto = true;
                    modal.hide();
                    setTimeout(() => $('#modalConfirmacion').remove(), 300);
                    resolve(valor);
                };

                $('#btnConfirmar').on('click', () => finalizar(true));
                $('#btnCancelar').on('click', () => finalizar(false));
                $('#modalConfirmacion').on('hidden.bs.modal', () => finalizar(false));
            });
        },
    },

    mounted() {
        this.obtenerTiposMaterial();
        this.obtenerMateriales();
    },
});
