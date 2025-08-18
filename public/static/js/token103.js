const app = Vue.createApp({
    data() {
        return {
            tokens: [],
            credenciales: {
                client_id: '',
                client_secret: ''
            },
            tokenActual: {},
            tokenSeleccionado: {},
            tabla: null,
            credencialesGuardadas: false,
            // URL de la API externa para generar tokens
            apiUrl: 'https://0d681142-41d3-4c17-a854-13e8da718ead.mock.pstmn.io'
        };
    },

    methods: {
        /**
         * Obtiene todos los tokens desde la API local
         */
        async obtenerTokens() {
            try {
                const urlTokens = BASE_URL + 'api/token103';
                console.log('URL Tokens:', urlTokens);
                
                const response = await axios.get(urlTokens);
                console.log('Respuesta de la API tokens:', response.data);
                this.tokens = response.data;
                
                // Obtener el token más reciente como token actual
                if (this.tokens.length > 0) {
                    this.tokenActual = this.tokens[this.tokens.length - 1];
                    this.credencialesGuardadas = true;
                    
                    // Cargar las credenciales en el formulario
                    this.credenciales.client_id = this.tokenActual.client_id;
                    this.credenciales.client_secret = this.tokenActual.client_secret;
                }
                
                this.$nextTick(() => {
                    this.inicializarTabla();
                });
            } catch (error) {
                console.error('Error al obtener tokens:', error);
            }
        },

        /**
         * Inicializa la tabla DataTable
         */
        inicializarTabla() {
            if (this.tabla) {
                this.tabla.destroy();
            }
            
            this.tabla = $('#tabla_tokens').DataTable({
                data: this.tokens,
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/2.2.1/i18n/es-MX.json'
                },
                columns: [
                    { data: 'id' },
                    { data: 'client_id' },
                    { 
                        data: 'client_secret',
                        render: function(data) {
                            // Mostrar solo los primeros 8 caracteres por seguridad
                            return data ? data.substring(0, 8) + '...' : '';
                        }
                    },
                    { 
                        data: 'access_token',
                        render: function(data) {
                            // Mostrar solo los primeros 20 caracteres del token
                            return data ? data.substring(0, 20) + '...' : '';
                        }
                    },
                    { data: 'token_type' },
                    { data: 'expires_in' },
                    { 
                        data: 'fecha_generacion',
                        render: (data) => this.formatearFecha(data)
                    },
                    { 
                        data: null,
                        render: (data, type, row) => {
                            return `
                                <button class="btn btn-sm btn-info me-1 ver-token" data-id="${row.id}" title="Ver detalles">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-danger eliminar-token" data-id="${row.id}" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            `;
                        }
                    }
                ],
                order: [[0, 'desc']] // Ordenar por ID descendente (más reciente primero)
            });

            // Vincular eventos de los botones
            $('#tabla_tokens tbody').off('click', '.ver-token').on('click', '.ver-token', (e) => {
                const id = $(e.currentTarget).data('id');
                const token = this.tokens.find(t => t.id == id);
                if (token) this.verToken(token);
            });
            
            $('#tabla_tokens tbody').off('click', '.eliminar-token').on('click', '.eliminar-token', (e) => {
                const id = $(e.currentTarget).data('id');
                const token = this.tokens.find(t => t.id == id);
                if (token) this.eliminarToken(token);
            });
        },

        /**
         * Guarda las credenciales en la base de datos
         */
        async guardarCredenciales() {
            try {
                // Verificar si ya existen credenciales
                if (this.tokens.length > 0) {
                    // Actualizar el registro existente
                    const tokenId = this.tokens[this.tokens.length - 1].id;
                    const url = BASE_URL + 'api/token103/' + tokenId;
                    
                    const datosActualizados = {
                        client_id: this.credenciales.client_id,
                        client_secret: this.credenciales.client_secret
                    };
                    
                    await axios.put(url, datosActualizados);
                } else {
                    // Crear nuevo registro
                    const url = BASE_URL + 'api/token103';
                    const datosNuevos = {
                        client_id: this.credenciales.client_id,
                        client_secret: this.credenciales.client_secret
                    };
                    
                    await axios.post(url, datosNuevos);
                }
                
                this.credencialesGuardadas = true;
                this.obtenerTokens(); // Recargar datos
                
                // Mostrar mensaje de éxito
                Swal.fire({
                    icon: 'success',
                    title: 'Credenciales guardadas',
                    text: 'Las credenciales se han guardado correctamente'
                });
                
            } catch (error) {
                console.error('Error al guardar credenciales:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron guardar las credenciales'
                });
            }
        },

        /**
         * Genera un nuevo token llamando a la API externa
         */
        async generarToken() {
            if (!this.credencialesGuardadas) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Credenciales requeridas',
                    text: 'Debe guardar las credenciales antes de generar un token'
                });
                return;
            }

            try {
                // Mostrar indicador de carga
                Swal.fire({
                    title: 'Generando token...',
                    text: 'Por favor espere',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Llamar a la API externa
                const response = await axios.post(this.apiUrl + '/generarToken', {
                    client_id: this.credenciales.client_id,
                    client_secret: this.credenciales.client_secret
                });

                console.log('Respuesta de la API externa:', response.data);

                // Guardar el token en la base de datos local
                const tokenData = {
                    client_id: this.credenciales.client_id,
                    client_secret: this.credenciales.client_secret,
                    access_token: response.data.access_token,
                    token_type: response.data.token_type || 'Bearer',
                    expires_in: response.data.expires_in || 3600,
                    fecha_generacion: new Date().toISOString().slice(0, 19).replace('T', ' ')
                };

                // Si ya existe un registro, actualizarlo; si no, crear uno nuevo
                if (this.tokens.length > 0) {
                    const tokenId = this.tokens[this.tokens.length - 1].id;
                    const url = BASE_URL + 'api/token103/' + tokenId;
                    await axios.put(url, tokenData);
                } else {
                    const url = BASE_URL + 'api/token103';
                    await axios.post(url, tokenData);
                }

                // Cerrar indicador de carga
                Swal.close();

                // Mostrar mensaje de éxito
                Swal.fire({
                    icon: 'success',
                    title: 'Token generado',
                    text: 'El token se ha generado y guardado correctamente'
                });

                // Recargar datos
                this.obtenerTokens();

            } catch (error) {
                console.error('Error al generar token:', error);
                Swal.close();
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error al generar token',
                    text: 'No se pudo generar el token. Verifique las credenciales y la conexión.'
                });
            }
        },

        /**
         * Muestra los detalles de un token en el modal
         */
        verToken(token) {
            this.tokenSeleccionado = { ...token };
            new bootstrap.Modal(document.getElementById('modalVerToken')).show();
        },

        /**
         * Copia el token al portapapeles
         */
        copiarToken() {
            const tokenInput = document.getElementById('tokenInput');
            tokenInput.select();
            tokenInput.setSelectionRange(0, 99999); // Para dispositivos móviles
            
            try {
                document.execCommand('copy');
                Swal.fire({
                    icon: 'success',
                    title: 'Token copiado',
                    text: 'El token se ha copiado al portapapeles',
                    timer: 2000,
                    showConfirmButton: false
                });
            } catch (err) {
                console.error('Error al copiar:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo copiar el token'
                });
            }
        },

        /**
         * Elimina un token
         */
        eliminarToken(token) {
            Swal.fire({
                title: '¿Está seguro?',
                text: `¿Desea eliminar el token con ID ${token.id}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.confirmarEliminacion(token);
                }
            });
        },

        /**
         * Confirma la eliminación del token
         */
        async confirmarEliminacion(token) {
            try {
                await axios.delete(BASE_URL + 'api/token103/' + token.id);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Token eliminado',
                    text: 'El token se ha eliminado correctamente'
                });
                
                this.obtenerTokens(); // Recargar datos
            } catch (error) {
                console.error('Error al eliminar token:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo eliminar el token'
                });
            }
        },

        /**
         * Formatea una fecha para mostrar en la interfaz
         */
        formatearFecha(fecha) {
            if (!fecha) return '';
            
            try {
                const date = new Date(fecha);
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
        // Cargar datos al montar la aplicación
        this.obtenerTokens();
    }
});
