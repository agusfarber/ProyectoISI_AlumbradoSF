const LIMITE_NOTAS = 30;

const nuevaVacia = () => ({
    titulo: '',
    contenido: '',
    fijada: false,
});

const app = Vue.createApp({
    data() {
        return {
            notas: [],
            filtro: 'activas',
            pagina: 1,
            total: 0,
            hayMas: false,
            cargando: true,
            cargandoMas: false,
            nueva: nuevaVacia(),
            guardandoNueva: false,
            editandoId: null,
            borrador: { titulo: '', contenido: '' },
            guardandoEdicion: false,
            confirmarEliminarId: null,
            eliminandoId: null,
        };
    },

    methods: {
        async obtenerNotas({ silencioso = false, append = false } = {}) {
            if (append) {
                this.cargandoMas = true;
            } else if (!silencioso) {
                this.cargando = true;
            }

            try {
                const page = append ? this.pagina : 1;
                const response = await axios.get(BASE_URL + 'api/notas', {
                    params: {
                        filtro: this.filtro,
                        page,
                        limit: LIMITE_NOTAS,
                    },
                });

                const lote = Array.isArray(response.data?.notas) ? response.data.notas : [];
                this.total = Number(response.data?.total) || 0;
                this.hayMas = Boolean(response.data?.hay_mas);
                this.pagina = page;

                if (append) {
                    const ids = new Set(this.notas.map((n) => n.id));
                    this.notas = this.notas.concat(lote.filter((n) => !ids.has(n.id)));
                } else {
                    this.notas = lote;
                }
            } catch (error) {
                console.error('Error al cargar notas:', error);
                this.mostrarMensaje(this.mensajeErrorApi(error) || 'No se pudieron cargar las notas.', 'danger');
                if (!append) {
                    this.notas = [];
                    this.total = 0;
                    this.hayMas = false;
                }
            } finally {
                this.cargando = false;
                this.cargandoMas = false;
            }
        },

        async cargarMas() {
            if (!this.hayMas || this.cargandoMas) return;
            this.pagina += 1;
            await this.obtenerNotas({ append: true });
        },

        setFiltro(filtro) {
            if (this.filtro === filtro) return;
            this.cancelarEdicion();
            this.cancelarEliminar();
            this.filtro = filtro;
            this.pagina = 1;
            this.obtenerNotas();
        },

        limpiarNueva() {
            this.nueva = nuevaVacia();
        },

        async crearNota() {
            if (!(this.nueva.contenido || '').trim()) {
                this.mostrarMensaje('Escribí el contenido de la nota.', 'warning');
                this.$refs.composerContenido?.focus();
                return;
            }
            if (this.guardandoNueva) return;

            this.guardandoNueva = true;
            try {
                await axios.post(BASE_URL + 'api/notas', {
                    titulo: (this.nueva.titulo || '').trim(),
                    contenido: (this.nueva.contenido || '').trim(),
                    fijada: this.nueva.fijada ? 1 : 0,
                });
                this.limpiarNueva();
                if (this.filtro === 'hechas') {
                    this.filtro = 'activas';
                }
                this.pagina = 1;
                await this.obtenerNotas({ silencioso: true });
                this.$nextTick(() => this.$refs.composerContenido?.focus());
            } catch (error) {
                console.error('Error al crear nota:', error);
                this.mostrarMensaje(this.mensajeErrorApi(error) || 'No se pudo guardar la nota.', 'danger');
            } finally {
                this.guardandoNueva = false;
            }
        },

        iniciarEdicion(n) {
            if (this.editandoId === n.id) return;
            this.cancelarEliminar();
            this.editandoId = n.id;
            this.borrador = {
                titulo: n.titulo || '',
                contenido: n.contenido || '',
            };
            this.$nextTick(() => {
                const refs = this.$refs.editContenido;
                const el = Array.isArray(refs) ? refs[0] : refs;
                if (el) {
                    el.focus();
                    const len = el.value.length;
                    el.setSelectionRange(len, len);
                }
            });
        },

        cancelarEdicion() {
            this.editandoId = null;
            this.borrador = { titulo: '', contenido: '' };
            this.guardandoEdicion = false;
        },

        async guardarEdicion() {
            if (!this.editandoId) return;
            if (!(this.borrador.contenido || '').trim()) {
                this.mostrarMensaje('El contenido es obligatorio.', 'warning');
                return;
            }
            if (this.guardandoEdicion) return;

            this.guardandoEdicion = true;
            try {
                await axios.put(BASE_URL + 'api/notas/' + this.editandoId, {
                    titulo: (this.borrador.titulo || '').trim(),
                    contenido: (this.borrador.contenido || '').trim(),
                });
                this.cancelarEdicion();
                await this.recargarListaActual();
            } catch (error) {
                console.error('Error al actualizar nota:', error);
                this.mostrarMensaje(this.mensajeErrorApi(error) || 'No se pudo actualizar la nota.', 'danger');
            } finally {
                this.guardandoEdicion = false;
            }
        },

        async toggleHecha(n) {
            if (this.editandoId === n.id) this.cancelarEdicion();
            this.cancelarEliminar();
            try {
                await axios.put(BASE_URL + 'api/notas/' + n.id, {
                    hecha: Number(n.hecha) === 1 ? 0 : 1,
                });
                await this.recargarListaActual();
            } catch (error) {
                console.error('Error al actualizar nota:', error);
                this.mostrarMensaje(this.mensajeErrorApi(error) || 'No se pudo actualizar la nota.', 'danger');
            }
        },

        async toggleFijada(n) {
            this.cancelarEliminar();
            try {
                await axios.put(BASE_URL + 'api/notas/' + n.id, {
                    fijada: Number(n.fijada) === 1 ? 0 : 1,
                });
                await this.recargarListaActual();
            } catch (error) {
                console.error('Error al fijar nota:', error);
                this.mostrarMensaje(this.mensajeErrorApi(error) || 'No se pudo actualizar la nota.', 'danger');
            }
        },

        pedirEliminar(n) {
            if (this.editandoId === n.id) this.cancelarEdicion();
            this.confirmarEliminarId = this.confirmarEliminarId === n.id ? null : n.id;
        },

        cancelarEliminar() {
            this.confirmarEliminarId = null;
            this.eliminandoId = null;
        },

        async confirmarEliminar(n) {
            if (this.eliminandoId) return;
            this.eliminandoId = n.id;
            try {
                await axios.delete(BASE_URL + 'api/notas/' + n.id);
                this.cancelarEliminar();
                await this.recargarListaActual();
            } catch (error) {
                console.error('Error al eliminar nota:', error);
                this.mostrarMensaje(this.mensajeErrorApi(error) || 'No se pudo eliminar la nota.', 'danger');
                this.eliminandoId = null;
            }
        },

        /** Recarga manteniendo la cantidad ya visible (hasta el tope de páginas cargadas). */
        async recargarListaActual() {
            const cantidad = Math.max(this.notas.length, LIMITE_NOTAS);
            const paginas = Math.ceil(cantidad / LIMITE_NOTAS);
            try {
                const response = await axios.get(BASE_URL + 'api/notas', {
                    params: {
                        filtro: this.filtro,
                        page: 1,
                        limit: paginas * LIMITE_NOTAS,
                    },
                });
                this.notas = Array.isArray(response.data?.notas) ? response.data.notas : [];
                this.total = Number(response.data?.total) || 0;
                this.hayMas = this.notas.length < this.total;
                this.pagina = Math.max(1, Math.ceil(this.notas.length / LIMITE_NOTAS) || 1);
            } catch (error) {
                console.error('Error al recargar notas:', error);
                await this.obtenerNotas({ silencioso: true });
            }
        },

        formatearFecha(valor) {
            if (!valor) return '';
            const d = new Date(String(valor).replace(' ', 'T'));
            if (Number.isNaN(d.getTime())) return String(valor);
            return d.toLocaleString('es-AR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        mensajeErrorApi(error) {
            const data = error?.response?.data;
            if (!data) return null;
            if (typeof data.message === 'string') return data.message;
            if (data.messages) {
                if (typeof data.messages === 'string') return data.messages;
                if (typeof data.messages === 'object') {
                    return Object.values(data.messages).flat().join(' ');
                }
            }
            if (typeof data.error === 'string') return data.error;
            return null;
        },

        mostrarMensaje(mensaje, tipo) {
            const alertClass = tipo === 'success'
                ? 'alert-success'
                : tipo === 'warning'
                    ? 'alert-warning'
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
            }, 2800);
        },

        onClickFuera(e) {
            if (!this.confirmarEliminarId) return;
            if (e.target.closest && e.target.closest('.nota-eliminar')) return;
            this.cancelarEliminar();
        },
    },

    mounted() {
        this.obtenerNotas();
        this.$nextTick(() => this.$refs.composerContenido?.focus());
        document.addEventListener('click', this.onClickFuera);
    },

    beforeUnmount() {
        document.removeEventListener('click', this.onClickFuera);
    },
});
