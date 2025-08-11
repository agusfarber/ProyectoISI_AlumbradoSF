const app = Vue.createApp({
  data() {
    return {
      materiales: [],
      material: {
        nombre: '',
        cantidad: 0,
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

    inicializarTabla() {
      if (this.tabla) {
        this.tabla.destroy();
      }
      this.tabla = $('#tabla_materiales').DataTable({
        data: this.materiales,
        responsive: true,
        language: { url: '//cdn.datatables.net/plug-ins/2.2.1/i18n/es-MX.json' },
        columns: [
          { data: 'nombre' },
          { data: 'cantidad' },
          'acciones'
        ]
      });
    },

    abrirFormulario() {
      this.material = { nombre: '', cantidad: 0 };
      new bootstrap.Modal(document.getElementById('modalMaterial')).show();
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

        // Enviar al backend para validación y guardado masivo
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

      // Intentar detectar encabezados en primera fila
      const primera = lineas[0].split(',').map(h => h.trim().toLowerCase());
      const tieneHeader = primera.includes('nombre') || primera.includes('cantidad');

      const inicio = tieneHeader ? 1 : 0;
      for (let i = inicio; i < lineas.length; i++) {
        const cols = lineas[i].split(',');
        if (cols.length < 1) continue;
        const nombre = String(cols[0] || '').trim();
        const cantidad = cols.length > 1 ? parseInt(cols[1], 10) : 0;
        if (nombre !== '' && !Number.isNaN(cantidad) && cantidad >= 0) {
          this.itemsImport.push({ nombre, cantidad });
        }
      }
    },

    async procesarExcel(file) {
      const data = await file.arrayBuffer();
      const workbook = XLSX.read(data, { type: 'array' });
      const sheetName = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[sheetName];
      const json = XLSX.utils.sheet_to_json(worksheet, { header: 1 }); // array de filas
      if (!Array.isArray(json) || json.length === 0) return;

      // Detectar encabezados
      const headers = (json[0] || []).map(h => String(h || '').trim().toLowerCase());
      const tieneHeader = headers.includes('nombre') || headers.includes('cantidad');

      const inicio = tieneHeader ? 1 : 0;
      for (let i = inicio; i < json.length; i++) {
        const row = json[i] || [];
        const nombre = String((row[0] ?? '')).trim();
        const cantidadRaw = row[1] ?? 0;
        const cantidad = parseInt(cantidadRaw, 10);
        if (nombre !== '' && !Number.isNaN(cantidad) && cantidad >= 0) {
          this.itemsImport.push({ nombre, cantidad });
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
  },
  mounted() {
    this.obtenerMateriales();
  }
});

