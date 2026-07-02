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
            tabla: null,
            // Foto de perfil
            fotoUsuario: {},
            fotoPreview: null,
            archivoFoto: null,
            subiendoFoto: false
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
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: (data, type, row) => this.avatarHtml(row)
                    },
                    { data: 'nombre' },
                    { 
                        data: null,
                        render: function(data, type, row) {
                            return data.email || data.legajo || 'No especificado';
                        }
                    },
                    {
                        data: 'idRol',
                        render: (data) => this.getNombreRol(data) || data
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: (data, type, row) => {
                            return `<button class="btn btn-sm btn-outline-primary btn-foto" data-id="${row.id}" title="Cambiar foto de perfil">
                                        <i class="bi bi-camera"></i>
                                    </button>`;
                        }
                    }
                ]
            });

            // Delegación de clic para el botón de cambiar foto
            $('#tabla_usuarios tbody').off('click', '.btn-foto').on('click', '.btn-foto', (e) => {
                const id = $(e.currentTarget).data('id');
                const user = this.usuarios.find(u => String(u.id) === String(id));
                if (user) {
                    this.abrirModalFoto(user);
                }
            });
            
            // Inicializar mejoras de tabla después de que DataTable esté listo
            this.$nextTick(() => {
                if (window.tableEnhancements) {
                    window.tableEnhancements.setupMobileTableTouch();
                }
            });
        },

        // ---------- Foto de perfil ----------
        urlFoto(nombreArchivo) {
            return BASE_URL + 'static/uploads/perfiles/' + nombreArchivo;
        },

        iniciales(nombre) {
            if (!nombre) return '?';
            const partes = nombre.trim().split(/\s+/);
            const primera = partes[0] ? partes[0][0] : '';
            const segunda = partes.length > 1 ? partes[partes.length - 1][0] : '';
            return (primera + segunda).toUpperCase();
        },

        colorAvatar(nombre) {
            const paleta = ['#3A3972', '#6E6D99', '#2D6A6A', '#7A5C9E', '#A65A7A', '#4C6EA8', '#9E7B3A'];
            const texto = nombre || '';
            let hash = 0;
            for (let i = 0; i < texto.length; i++) {
                hash = texto.charCodeAt(i) + ((hash << 5) - hash);
            }
            return paleta[Math.abs(hash) % paleta.length];
        },

        avatarHtml(user) {
            if (user.foto_perfil) {
                return `<img src="${this.urlFoto(user.foto_perfil)}" class="tabla-avatar-img" alt="Foto">`;
            }
            const color = this.colorAvatar(user.nombre);
            return `<span class="tabla-avatar-iniciales" style="background-color:${color}">${this.iniciales(user.nombre)}</span>`;
        },

        abrirModalFoto(user) {
            this.fotoUsuario = { ...user };
            this.archivoFoto = null;
            this.fotoPreview = user.foto_perfil ? this.urlFoto(user.foto_perfil) : null;
            const input = document.getElementById('inputFotoUsuario');
            if (input) input.value = '';
            new bootstrap.Modal(document.getElementById('modalFotoUsuario')).show();
        },

        onFotoSeleccionada(event) {
            const archivo = event.target.files && event.target.files[0];
            if (!archivo) return;
            this.archivoFoto = archivo;
            this.fotoPreview = URL.createObjectURL(archivo);
        },

        async guardarFoto() {
            if (!this.archivoFoto || !this.fotoUsuario.id) return;
            this.subiendoFoto = true;
            try {
                const formData = new FormData();
                formData.append('foto', this.archivoFoto);
                await axios.post(BASE_URL + 'api/usuarios/' + this.fotoUsuario.id + '/foto', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });
                bootstrap.Modal.getInstance(document.getElementById('modalFotoUsuario')).hide();
                this.archivoFoto = null;
                this.fotoPreview = null;
                await this.obtenerUsuarios();
            } catch (error) {
                console.error('Error al subir la foto:', error);
                const msg = error.response?.data?.messages
                    ? Object.values(error.response.data.messages).join(', ')
                    : (error.response?.data?.message || 'No se pudo subir la foto.');
                alert(msg);
            } finally {
                this.subiendoFoto = false;
            }
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