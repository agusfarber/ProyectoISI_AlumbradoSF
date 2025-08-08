const app = Vue.createApp({
    data() {
      return {
            usuarios: [],
            usuario: {
                nombre: '',
                email: '',
                contrasena: '',
                idRol: '',
            },
            usuarioSeleccionado: {},
            roles: [],
            tabla: null
        };
    },
  
    methods: {
        // Obtener datos iniciales
        async obtenerUsuarios() 
        {
            try {
                const urlUsuarios = BASE_URL + 'api/usuarios';
                console.log('URL Usuarios:', urlUsuarios);
                
                const response = await axios.get(urlUsuarios);
                console.log('Respuesta de la API usuarios:', response.data);
                this.usuarios = response.data;
                console.log('Usuarios después de asignar:', this.usuarios);
                this.$nextTick(() => {
                    console.log('Inicializando tabla con usuarios:', this.usuarios);
                    this.inicializarTabla();
                });
            } catch (error) {
                console.error('Error al obtener datos:', error);
                console.error('URL que falló:', error.config?.url);
            }
        },

        async obtenerRoles() 
        {
            try {
                const urlRoles = BASE_URL + 'api/roles';
                const response = await axios.get(urlRoles);
                this.roles = response.data;
            } catch (error) {
                console.error('Error al obtener roles:', error);
            }
        },


        // Inicializar o reiniciar DataTable
        inicializarTabla() 
        {
            if (this.tabla) {
                console.log('Destruyendo tabla anterior');
                this.tabla.destroy();
            }
            console.log('Creando nueva tabla con datos:', this.usuarios);
            this.tabla = $('#tabla_usuarios').DataTable({
                data: this.usuarios,
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/2.2.1/i18n/es-MX.json'
                },
                columns: [
                    { data: 'nombre' },
                    { 
                        data: null,
                        render: function(data, type, row) {
                            return data.email || data.legajo || 'No especificado';
                        }
                    },
                    { data: 'idRol' },
                    'acciones'
                ]
            });
        },

        // Abrir modal vacío
        abrirFormulario() 
        {
            this.usuario = {
                nombre: '',
                email: '',
                contrasena: '',
                idRol: '',
            };
            new bootstrap.Modal(document.getElementById('modalUsuario')).show();
        },

        // Cargar usuario en modal
        editarUsuario(user) 
        {
            this.usuario = { ...user };
            new bootstrap.Modal(document.getElementById('modalUsuario')).show();
        },

        // Ver detalles del usuario
        verUsuario(user) 
        {
            this.usuarioSeleccionado = { ...user };
            new bootstrap.Modal(document.getElementById('modalVerUsuario')).show();
        },

        // Crear o actualizar usuario
        guardarUsuario() 
        {
            const esNuevo = !this.usuario.id;
            const url = BASE_URL + 'api/usuarios' + (esNuevo ? '' : '/' + this.usuario.id);
            const metodo = esNuevo ? 'post' : 'put';
            axios[metodo](url, this.usuario).then(() => {
                this.obtenerUsuarios();
                bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
            });
        },

        // Eliminar usuario
        eliminarUsuario(user) 
        {
            if (confirm(`¿Seguro que deseas eliminar a ${user.nombre}?`)) {
                axios.delete(BASE_URL + 'api/usuarios/' + user.id).then(() => {
                this.obtenerUsuarios();
                });
            }
        },

        getNombreRol(id) 
        {
            const rol = this.roles.find(r => r.id === id);
            return rol ? rol.nombre : '';
        },
    },

    mounted() {
        this.obtenerUsuarios();
        this.obtenerRoles();
    },
  });