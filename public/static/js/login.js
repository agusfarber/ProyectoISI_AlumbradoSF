const loginApp = Vue.createApp({
    data() {
      return {
        idioma: "es",
        rolSeleccionado: null, // Para controlar qué formulario mostrar
        usuario: {},
        usuarioLegajo: {},
        loading: false, // Para deshabilitar el botón mientras se procesa el login con email
        loadingLegajo: false, // Para deshabilitar el botón mientras se procesa el login con legajo
        errorMessage: "", // Para mostrar errores del login con email
        errorMessageLegajo: "", // Para mostrar errores del login con legajo
      };
    },
  
    methods: {
      // Seleccionar tipo de usuario
      seleccionarRol(rol) {
        this.rolSeleccionado = rol;
        // Limpiar mensajes de error al cambiar de rol
        this.errorMessage = "";
        this.errorMessageLegajo = "";
        // Limpiar formularios
        this.usuario = {};
        this.usuarioLegajo = {};
      },

      // Volver a la selección de rol
      volverSeleccion() {
        this.rolSeleccionado = null;
        this.errorMessage = "";
        this.errorMessageLegajo = "";
        this.usuario = {};
        this.usuarioLegajo = {};
      },
		
      login(){
        this.errorMessage = ""; // Resetear el mensaje de error
        this.loading = true;
              
        axios.post(BASE_URL + "auth/login", this.usuario)
          .then(response => {
            if (response.data.message) {
              // Redirigir según el rol del usuario
              const userRole = response.data.role;
              if (userRole == 3) {
                // Operarios van a materiales
                window.location.replace(BASE_URL+'materiales');
              } else {
                // Otros roles van a usuarios
                window.location.replace(BASE_URL+'usuarios');
              }
            }
          })
          .catch(error => {
            if (error.response && error.response.data && error.response.data.error) {
              this.errorMessage = error.response.data.error;
            } else {
              this.errorMessage = "Error al intentar iniciar sesión. Por favor, intente nuevamente.";
            }
          })
          .finally(() => {
            this.loading = false;
          });
      },

      loginLegajo(){
        this.errorMessageLegajo = ""; // Resetear el mensaje de error
        
        // Validar formato del legajo (debe ser un string de 5 dígitos)
        if (!this.usuarioLegajo.legajo || this.usuarioLegajo.legajo.toString().length < 5) {
          this.errorMessageLegajo = "El legajo debe tener 5 dígitos.";
          return;
        }

        // Validar formato del legajo (debe ser un string de 5 dígitos)
        if (!this.usuarioLegajo.legajo || this.usuarioLegajo.legajo.toString().length > 5) {
          this.errorMessageLegajo = "El legajo debe tener 5 dígitos.";
          return;
        }
        
        this.loadingLegajo = true;
              
        axios.post(BASE_URL + "auth/loginLegajo", this.usuarioLegajo)
          .then(response => {
            if (response.data.message) {
              // Redirigir según el rol del usuario
              const userRole = response.data.role;
              if (userRole == 3) {
                // Operarios van a materiales
                window.location.replace(BASE_URL+'materiales');
              } else {
                // Otros roles van a usuarios
                window.location.replace(BASE_URL+'usuarios');
              }
            }
          })
          .catch(error => {
            if (error.response && error.response.data && error.response.data.error) {
              this.errorMessageLegajo = error.response.data.error;
            } else {
              this.errorMessageLegajo = "Error al intentar iniciar sesión. Por favor, intente nuevamente.";
            }
          })
          .finally(() => {
            this.loadingLegajo = false;
          });
      }
    },
    mounted() {
  
    },
  });

// Montar la aplicación en el ID original
loginApp.mount('#app');
  
  