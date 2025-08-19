const app = Vue.createApp({
    data() {
        return {
            credenciales: {
                client_id: '',
                client_secret: ''
            },
            tokenActual: {}, // Ahora solo un objeto
            credencialesGuardadas: false,
            mensajeCopiadoVisible: false,
            apiUrl: 'https://0d681142-41d3-4c17-a854-13e8da718ead.mock.pstmn.io'
        };
    },

    methods: {
        /**
         * Obtiene el token más reciente desde la API local
         */
        async obtenerTokenUnico() {
            try {
                const urlTokens = BASE_URL + 'api/token103';
                const response = await axios.get(urlTokens);

                // Si existe al menos un token, usa el último
                if (response.data.length > 0) {
                    this.tokenActual = response.data[response.data.length - 1];
                    this.credencialesGuardadas = true;
                    this.credenciales.client_id = this.tokenActual.client_id;
                    this.credenciales.client_secret = this.tokenActual.client_secret;
                } else {
                    this.tokenActual = {}; // No hay token, vaciar el objeto
                    this.credencialesGuardadas = false;
                }
            } catch (error) {
                console.error('Error al obtener tokens:', error);
            }
        },

        /**
         * Guarda o actualiza las credenciales
         */
        async guardarCredenciales() {
            try {
                const url = BASE_URL + 'api/token103';

                if (this.tokenActual.id) {
                    // Si ya existe un token, actualiza las credenciales
                    await axios.put(url + '/' + this.tokenActual.id, this.credenciales);
                } else {
                    // Si no, crea un nuevo registro (solo con credenciales)
                    await axios.post(url, this.credenciales);
                }

                this.credencialesGuardadas = true;
                this.obtenerTokenUnico(); // Recargar el token para actualizar la vista

                // Mensaje de éxito con alert()
                alert('Credenciales guardadas: Las credenciales se han guardado correctamente.');
            } catch (error) {
                console.error('Error al guardar credenciales:', error);
                // Mensaje de error con alert()
                alert('Error: No se pudieron guardar las credenciales.');
            }
        },

        /**
         * Genera un nuevo token llamando a la API externa
         */
        async generarToken() {
            if (!this.credencialesGuardadas) {
                // Mensaje de advertencia con alert()
                alert('Credenciales requeridas: Debe guardar las credenciales antes de generar un token.');
                return;
            }

            // Un simple alert para indicar que se está generando el token
            alert('Generando token... Por favor espere.');

            try {
                const response = await axios.post(this.apiUrl + '/generarToken', this.credenciales);

                const tokenData = {
                    client_id: this.credenciales.client_id,
                    client_secret: this.credenciales.client_secret,
                    access_token: response.data.access_token,
                    token_type: response.data.token_type || 'Bearer',
                    expires_in: response.data.expires_in || 3600,
                    fecha_generacion: new Date().toISOString().slice(0, 19).replace('T', ' ')
                };

                if (this.tokenActual.id) {
                    // Actualiza el token existente con el nuevo access_token
                    await axios.put(BASE_URL + 'api/token103/' + this.tokenActual.id, tokenData);
                } else {
                    // Si por alguna razón no había token, crea uno nuevo
                    await axios.post(BASE_URL + 'api/token103', tokenData);
                }

                // Mensaje de éxito con alert()
                alert('Token generado: El token se ha generado y guardado correctamente.');

                this.obtenerTokenUnico(); // Recargar los datos para mostrar el nuevo token

            } catch (error) {
                console.error('Error al generar token:', error);
                // Mensaje de error con alert()
                alert('Error al generar token: No se pudo generar el token. Verifique las credenciales y la conexión.');
            }
        },

        /**
         * Copia el token al portapapeles y cambia el ícono del botón
         */
        copiarToken() {
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
                // Puedes mantener una alerta nativa para los errores
                alert('No se pudo copiar el token.');
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
        this.obtenerTokenUnico();
    }
});

