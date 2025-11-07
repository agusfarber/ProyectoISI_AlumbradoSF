const app = Vue.createApp({
    data() {
        return {
            credenciales: {
                username: '',
                password: ''
            },
            tokenActual: {}, // Ahora solo un objeto
            tokenBase64: '', // Token Basic Auth en base64
            credencialesGuardadas: false,
            mensajeCopiadoVisible: false
        };
    },

    methods: {
        /**
         * Obtiene las credenciales más recientes desde la API local
         */
        async obtenerTokenUnico() {
            try {
                const urlTokens = BASE_URL + 'api/token103';
                const response = await axios.get(urlTokens);

                // Si existe al menos una credencial, usa la última
                if (response.data.length > 0) {
                    this.tokenActual = response.data[response.data.length - 1];
                    this.credencialesGuardadas = true;
                    this.credenciales.username = this.tokenActual.username;
                    this.credenciales.password = this.tokenActual.password;
                    // Generar el token base64 automáticamente
                    this.generarTokenBase64();
                } else {
                    this.tokenActual = {}; // No hay credenciales, vaciar el objeto
                    this.credencialesGuardadas = false;
                    this.tokenBase64 = '';
                }
            } catch (error) {
                console.error('Error al obtener credenciales:', error);
            }
        },

        /**
         * Guarda o actualiza las credenciales
         */
        async guardarCredenciales() {
            // Validar campos
            if (!this.credenciales.username || !this.credenciales.password) {
                this.mostrarMensaje('Debe ingresar username y password', 'warning');
                return;
            }

            // Confirmación antes de guardar
            const mensajeConfirmacion = `¿Está seguro que desea guardar las credenciales?`;
            const confirmacion = await this.mostrarConfirmacion(mensajeConfirmacion, 'Guardar Credenciales');
            
            if (!confirmacion) {
                return;
            }

            try {
                const url = BASE_URL + 'api/token103';

                if (this.tokenActual.id) {
                    // Si ya existe, actualiza las credenciales
                    await axios.put(url + '/' + this.tokenActual.id, this.credenciales);
                } else {
                    // Si no, crea un nuevo registro
                    await axios.post(url, this.credenciales);
                }

                this.credencialesGuardadas = true;
                await this.obtenerTokenUnico(); // Recargar las credenciales para actualizar la vista

                // Mensaje de éxito personalizado
                this.mostrarMensaje('Credenciales guardadas correctamente', 'success');
            } catch (error) {
                console.error('Error al guardar credenciales:', error);
                // Mensaje de error personalizado
                this.mostrarMensaje('Error al guardar las credenciales', 'error');
            }
        },

        /**
         * Genera el token Basic Auth en base64 localmente
         */
        generarTokenBase64() {
            if (!this.credenciales.username || !this.credenciales.password) {
                this.tokenBase64 = '';
                return;
            }
            
            // Generar token Basic Auth: "username:password" codificado en base64
            const credencialesString = this.credenciales.username + ':' + this.credenciales.password;
            this.tokenBase64 = btoa(credencialesString);
        },

        /**
         * Copia el token al portapapeles
         */
        copiarToken() {
            if (!this.tokenBase64) {
                this.mostrarMensaje('No hay token para copiar', 'warning');
                return;
            }

            const tokenInput = document.getElementById('tokenInput');
            tokenInput.select();
            tokenInput.setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');

                // Mostrar el mensaje
                this.mensajeCopiadoVisible = true;

                // Ocultar el mensaje después de 2 segundos
                setTimeout(() => {
                    this.mensajeCopiadoVisible = false;
                }, 2000);

            } catch (err) {
                console.error('Error al copiar:', err);
                // Mensaje de error personalizado
                this.mostrarMensaje('No se pudo copiar el token', 'error');
            }
        },

        /**
         * Muestra mensajes de notificación estilo cuadrillas
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
            
            // Auto-remover después de 5 segundos - solo los mensajes de notificación flotantes
            setTimeout(() => {
                $('.mensaje-notificacion').fadeOut(500, function() {
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
        },

        /**
         * Formatea una fecha para mostrar en la interfaz
         */
        formatearFecha(fecha) {
            if (!fecha) return '';

            try {
                // Si la fecha viene en formato YYYY-MM-DD HH:MM:SS, crear el objeto Date correctamente
                let date;
                if (typeof fecha === 'string' && fecha.includes(' ')) {
                    // Formato de base de datos: YYYY-MM-DD HH:MM:SS
                    date = new Date(fecha.replace(' ', 'T'));
                } else {
                    date = new Date(fecha);
                }
                
                return date.toLocaleString('es-AR', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    timeZone: 'America/Argentina/Buenos_Aires'
                });
            } catch (error) {
                console.error('Error al formatear fecha:', error);
                return fecha;
            }
        }
    },

    mounted() {
        this.obtenerTokenUnico();
    }
});

