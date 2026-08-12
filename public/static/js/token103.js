const app = Vue.createApp({
    data() {
        return {
            form: {
                api_token: '',
            },
            tokenActual: {},
            tokenGuardado: false,
            mensajeCopiadoVisible: false,
        };
    },

    computed: {
        tokenEnmascarado() {
            const token = this.tokenActual.api_token || '';
            if (token.length <= 10) {
                return token ? '••••••••' : '';
            }
            return token.slice(0, 6) + '…' + token.slice(-4);
        },
    },

    methods: {
        async obtenerTokenUnico() {
            try {
                const response = await axios.get(BASE_URL + 'api/token103');
                if (response.data.length > 0) {
                    this.tokenActual = response.data[0];
                    this.tokenGuardado = !!(this.tokenActual.api_token || '').trim();
                    this.form.api_token = this.tokenActual.api_token || '';
                } else {
                    this.tokenActual = {};
                    this.tokenGuardado = false;
                    this.form.api_token = '';
                }
            } catch (error) {
                console.error('Error al obtener token:', error);
            }
        },

        async guardarToken() {
            if (!(this.form.api_token || '').trim()) {
                this.mostrarMensaje('Debés ingresar el token', 'warning');
                return;
            }

            const confirmacion = await this.mostrarConfirmacion(
                '¿Guardar el token del sistema 103?',
                'Guardar token'
            );
            if (!confirmacion) return;

            try {
                const url = BASE_URL + 'api/token103';
                const payload = { api_token: this.form.api_token.trim() };

                if (this.tokenActual.id) {
                    await axios.put(url + '/' + this.tokenActual.id, payload);
                } else {
                    await axios.post(url, payload);
                }

                await this.obtenerTokenUnico();
                this.mostrarMensaje('Token guardado correctamente', 'success');
            } catch (error) {
                console.error('Error al guardar token:', error);
                this.mostrarMensaje('Error al guardar el token', 'error');
            }
        },

        copiarToken() {
            const token = this.tokenActual.api_token;
            if (!token) {
                this.mostrarMensaje('No hay token para copiar', 'warning');
                return;
            }

            const copiar = async () => {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(token);
                } else {
                    const input = document.getElementById('tokenInput');
                    input.value = token;
                    input.select();
                    document.execCommand('copy');
                    input.value = this.tokenEnmascarado;
                }
                this.mensajeCopiadoVisible = true;
                setTimeout(() => {
                    this.mensajeCopiadoVisible = false;
                }, 2000);
            };

            copiar().catch((err) => {
                console.error('Error al copiar:', err);
                this.mostrarMensaje('No se pudo copiar el token', 'error');
            });
        },

        mostrarMensaje(mensaje, tipo) {
            if (tipo === 'success') {
                $('.alert-info.mensaje-notificacion').fadeOut(200, function () {
                    $(this).remove();
                });
            }

            const alertClass = tipo === 'success'
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
                $('.mensaje-notificacion').fadeOut(500, function () {
                    $(this).remove();
                });
            }, 5000);
        },

        mostrarConfirmacion(mensaje, titulo = 'Confirmar Acción') {
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

                $('#btnConfirmar').on('click', () => {
                    modal.hide();
                    setTimeout(() => $('#modalConfirmacion').remove(), 300);
                    resolve(true);
                });

                $('#btnCancelar').on('click', () => {
                    modal.hide();
                    setTimeout(() => $('#modalConfirmacion').remove(), 300);
                    resolve(false);
                });

                $('#modalConfirmacion').on('hidden.bs.modal', () => {
                    $('#modalConfirmacion').remove();
                    resolve(false);
                });
            });
        },
    },

    mounted() {
        this.obtenerTokenUnico();
    },
});

