const loginApp = Vue.createApp({
    data() {
      return {
        idioma: "es",
        usuario: {
          credencial: "",
          contrasena: "",
        },
        loading: false,
        errorMessage: "",
      };
    },
  
    methods: {
      login(){
        this.errorMessage = "";
        this.loading = true;
              
        axios.post(BASE_URL + "auth/login", this.usuario)
          .then(response => {
            if (response.data.message) {
              // Redirigir según el rol del usuario
              const userRole = response.data.role;
              if (userRole == 1) {
                window.location.replace(BASE_URL+'usuarios');
              } else if (userRole == 2) {
                window.location.replace(BASE_URL+'cuadrillas');
              } else if (userRole == 3) {
                window.location.replace(BASE_URL+'materiales');
              } else {
                window.location.replace(BASE_URL+'cuadrillas');
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
      }
    },
    mounted() {
  
    },
  });

// Montar la aplicación en el ID original
loginApp.mount('#app');
  
  