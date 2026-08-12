const usuarioVacio = () => ({
    id: null,
    nombre: '',
    email: '',
    legajo: '',
    contrasena: '',
    idRol: '',
});

const app = Vue.createApp({
    data() {
        return {
            usuarios: [],
            roles: [],
            tabla: null,
            cargando: true,
            guardando: false,
            filtroRol: 'Todos',
            mostrarPass: false,
            usuario: usuarioVacio(),
            usuarioSeleccionado: {},
            fotoUsuario: {},
            fotoPreview: null,
            archivoFoto: null,
            subiendoFoto: false,
            usuarioActualId: null,
        };
    },

    methods: {
        usuarioVacio() {
            return usuarioVacio();
        },

        usuariosFiltrados() {
            if (this.filtroRol === 'Todos') return this.usuarios || [];
            return (this.usuarios || []).filter((u) => String(u.idRol) === String(this.filtroRol));
        },

        setFiltroRol(rol) {
            this.filtroRol = rol;
            this.inicializarTabla();
        },

        async obtenerUsuarios() {
            this.cargando = true;
            try {
                const response = await axios.get(BASE_URL + 'api/usuarios');
                this.usuarios = Array.isArray(response.data) ? response.data : [];
                this.$nextTick(() => this.inicializarTabla());
            } catch (error) {
                console.error('Error al obtener usuarios:', error);
                this.mostrarMensaje('No se pudieron cargar los usuarios.', 'danger');
            } finally {
                this.cargando = false;
            }
        },

        inicializarTabla() {
            if (this.tabla) {
                try { this.tabla.destroy(); } catch (e) {}
                this.tabla = null;
            }

            const datos = this.usuariosFiltrados().slice().sort((a, b) =>
                String(a.nombre || '').localeCompare(String(b.nombre || ''), 'es', { sensitivity: 'base' })
            );

            this.tabla = $('#tabla_usuarios').DataTable({
                data: datos,
                responsive: true,
                ordering: true,
                pageLength: 30,
                pagingType: 'simple_numbers',
                lengthMenu: [
                    [15, 30, 50, 100],
                    ['15 por página', '30 por página', '50 por página', '100 por página']
                ],
                language: {
                    processing: 'Procesando...',
                    search: '',
                    searchPlaceholder: 'Buscar usuario...',
                    lengthMenu: '_MENU_',
                    zeroRecords: 'No se encontraron usuarios',
                    emptyTable: 'Todavía no hay usuarios cargados',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ usuarios',
                    infoEmpty: 'Sin usuarios para mostrar',
                    infoFiltered: '(filtrado de _MAX_ usuarios)',
                    loadingRecords: 'Cargando...',
                    paginate: {
                        previous: 'Anterior',
                        next: 'Siguiente'
                    }
                },
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        width: '56px',
                        render: (data, type, row) => this.avatarHtml(row)
                    },
                    {
                        data: 'nombre',
                        className: 'text-start',
                        render: (data, type, row) => {
                            if (type !== 'display') return data || '';
                            return `<a href="#" class="ver-usuario-nombre" data-id="${row.id}">${this.escapeHtml(data || '')}</a>`;
                        }
                    },
                    {
                        data: 'idRol',
                        className: 'text-start text-nowrap',
                        render: (data, type) => {
                            const nombre = this.getNombreRol(data) || 'Sin rol';
                            if (type !== 'display') return nombre;
                            return `<span class="usuario-rol-badge usuario-rol-badge--${this.claseRol(data)}">${this.escapeHtml(nombre)}</span>`;
                        }
                    },
                    {
                        data: null,
                        className: 'text-start',
                        render: (data, type, row) => {
                            const texto = this.textoAcceso(row);
                            if (type !== 'display') return texto;
                            const icono = this.esRolAdmin(row.idRol) ? 'bi-envelope' : 'bi-person-badge';
                            return `<span class="usuario-acceso"><i class="bi ${icono}"></i> ${this.escapeHtml(texto)}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center text-nowrap',
                        render: (data, type, row) => `
                            <button type="button" class="usuario-accion-btn btn-foto-usuario" data-id="${row.id}" title="Cambiar foto">
                                <i class="bi bi-camera"></i>
                            </button>
                            <button type="button" class="usuario-accion-btn btn-editar-usuario" data-id="${row.id}" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="usuario-accion-btn btn-ver-usuario" data-id="${row.id}" title="Ver detalle">
                                <i class="bi bi-eye"></i>
                            </button>
                        `
                    }
                ],
                columnDefs: [
                    { defaultContent: '—', targets: '_all' }
                ],
                order: [[1, 'asc']],
                initComplete() {
                    const wrapper = $('#tabla_usuarios_wrapper');
                    wrapper.find('.dt-length select').addClass('form-select form-select-sm');
                    wrapper.find('.dt-search input').addClass('form-control form-control-sm').attr('aria-label', 'Buscar usuario');
                }
            });

            const tbody = $('#tabla_usuarios tbody');
            tbody.off('click', '.ver-usuario-nombre').on('click', '.ver-usuario-nombre', (e) => {
                e.preventDefault();
                const user = this.buscarUsuario($(e.currentTarget).data('id'));
                if (user) this.verUsuario(user);
            });
            tbody.off('click', '.btn-foto-usuario').on('click', '.btn-foto-usuario', (e) => {
                const user = this.buscarUsuario($(e.currentTarget).data('id'));
                if (user) this.abrirModalFoto(user);
            });
            tbody.off('click', '.btn-editar-usuario').on('click', '.btn-editar-usuario', (e) => {
                const user = this.buscarUsuario($(e.currentTarget).data('id'));
                if (user) this.editarUsuario(user);
            });
            tbody.off('click', '.btn-ver-usuario').on('click', '.btn-ver-usuario', (e) => {
                const user = this.buscarUsuario($(e.currentTarget).data('id'));
                if (user) this.verUsuario(user);
            });

            this.$nextTick(() => {
                if (window.tableEnhancements) {
                    window.tableEnhancements.setupMobileTableTouch();
                }
            });
        },

        buscarUsuario(id) {
            return this.usuarios.find((u) => String(u.id) === String(id));
        },

        avatarHtml(user) {
            if (user.foto_perfil) {
                return `<img src="${this.urlFoto(user.foto_perfil)}" class="tabla-avatar-img" alt="">`;
            }
            const color = this.colorAvatar(user.nombre);
            return `<span class="tabla-avatar-iniciales" style="background-color:${color}">${this.iniciales(user.nombre)}</span>`;
        },

        textoAcceso(user) {
            if (this.esRolAdmin(user.idRol)) {
                return user.email || '—';
            }
            return user.legajo ? `Legajo ${user.legajo}` : '—';
        },

        async obtenerRoles() {
            try {
                const response = await axios.get(BASE_URL + 'api/roles');
                this.roles = Array.isArray(response.data) ? response.data : [];
            } catch (error) {
                console.error('Error al obtener roles:', error);
            }
        },

        contarPorRol(idRol) {
            return (this.usuarios || []).filter((u) => String(u.idRol) === String(idRol)).length;
        },

        getNombreRol(id) {
            const rol = this.roles.find((r) => String(r.id) === String(id));
            return rol ? rol.nombre : '';
        },

        claseRol(id) {
            const n = Number(id);
            if (n === 1) return 'admin';
            if (n === 2) return 'supervisor';
            if (n === 3) return 'operario';
            return 'otro';
        },

        esUsuarioActual(user) {
            return user && this.usuarioActualId != null
                && String(user.id) === String(this.usuarioActualId);
        },

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

        abrirFormulario() {
            this.usuario = this.usuarioVacio();
            this.mostrarPass = false;
            new bootstrap.Modal(document.getElementById('modalUsuario')).show();
        },

        editarUsuario(user) {
            this.usuario = {
                id: user.id,
                nombre: user.nombre || '',
                email: user.email || '',
                legajo: user.legajo || '',
                contrasena: '',
                idRol: user.idRol,
            };
            this.mostrarPass = false;
            const detalle = bootstrap.Modal.getInstance(document.getElementById('modalVerUsuario'));
            if (detalle) detalle.hide();
            new bootstrap.Modal(document.getElementById('modalUsuario')).show();
        },

        editarDesdeDetalle() {
            this.editarUsuario(this.usuarioSeleccionado);
        },

        verUsuario(user) {
            this.usuarioSeleccionado = { ...user };
            new bootstrap.Modal(document.getElementById('modalVerUsuario')).show();
        },

        esRolAdmin(idRol) {
            return Number(idRol) === 1;
        },

        esRolConLegajo(idRol) {
            const n = Number(idRol);
            return n === 2 || n === 3;
        },

        onCambioRolFormulario() {
            if (this.esRolAdmin(this.usuario.idRol)) {
                this.usuario.legajo = '';
            } else if (this.esRolConLegajo(this.usuario.idRol)) {
                this.usuario.email = '';
            }
        },

        validarFormularioLocal() {
            if (!(this.usuario.nombre || '').trim()) {
                this.mostrarMensaje('El nombre es obligatorio.', 'warning');
                return false;
            }
            if (!this.usuario.idRol) {
                this.mostrarMensaje('Seleccioná un rol.', 'warning');
                return false;
            }

            if (this.esRolAdmin(this.usuario.idRol)) {
                const email = (this.usuario.email || '').trim();
                if (!email) {
                    this.mostrarMensaje('El email es obligatorio para administradores.', 'warning');
                    return false;
                }
            } else if (this.esRolConLegajo(this.usuario.idRol)) {
                const legajo = (this.usuario.legajo || '').trim();
                if (!legajo) {
                    this.mostrarMensaje('El legajo es obligatorio para supervisores y operarios.', 'warning');
                    return false;
                }
            }

            if (!this.usuario.id) {
                if (!(this.usuario.contrasena || '').trim() || this.usuario.contrasena.length < 4) {
                    this.mostrarMensaje('La contraseña debe tener al menos 4 caracteres.', 'warning');
                    return false;
                }
            } else if (this.usuario.contrasena && this.usuario.contrasena.length < 4) {
                this.mostrarMensaje('La contraseña debe tener al menos 4 caracteres.', 'warning');
                return false;
            }
            return true;
        },

        async guardarUsuario() {
            if (!this.validarFormularioLocal() || this.guardando) return;

            this.guardando = true;
            const esNuevo = !this.usuario.id;
            const url = BASE_URL + 'api/usuarios' + (esNuevo ? '' : '/' + this.usuario.id);
            const metodo = esNuevo ? 'post' : 'put';

            const esAdmin = this.esRolAdmin(this.usuario.idRol);
            const payload = {
                nombre: this.usuario.nombre.trim(),
                email: esAdmin ? ((this.usuario.email || '').trim() || null) : null,
                legajo: esAdmin ? null : ((this.usuario.legajo || '').trim() || null),
                idRol: this.usuario.idRol,
            };
            if (this.usuario.contrasena) {
                payload.contrasena = this.usuario.contrasena;
            }

            try {
                await axios[metodo](url, payload);
                bootstrap.Modal.getInstance(document.getElementById('modalUsuario'))?.hide();
                this.mostrarMensaje(esNuevo ? 'Usuario creado correctamente.' : 'Usuario actualizado.', 'success');
                await this.obtenerUsuarios();
            } catch (error) {
                console.error('Error al guardar usuario:', error);
                const msg = this.mensajeErrorApi(error) || 'No se pudo guardar el usuario.';
                this.mostrarMensaje(msg, 'danger');
            } finally {
                this.guardando = false;
            }
        },

        async eliminarUsuario(user) {
            if (!user || !user.id) return;
            if (this.esUsuarioActual(user)) {
                this.mostrarMensaje('No podés eliminar tu propio usuario.', 'warning');
                return;
            }

            const ok = await this.mostrarConfirmacion(
                `¿Eliminar a <strong>${this.escapeHtml(user.nombre)}</strong>? Esta acción no se puede deshacer.`,
                'Eliminar usuario'
            );
            if (!ok) return;

            try {
                await axios.delete(BASE_URL + 'api/usuarios/' + user.id);
                bootstrap.Modal.getInstance(document.getElementById('modalVerUsuario'))?.hide();
                this.mostrarMensaje('Usuario eliminado.', 'success');
                await this.obtenerUsuarios();
            } catch (error) {
                console.error('Error al eliminar usuario:', error);
                this.mostrarMensaje(this.mensajeErrorApi(error) || 'No se pudo eliminar el usuario.', 'danger');
            }
        },

        abrirModalFoto(user) {
            if (!user || !user.id) return;
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
            if (archivo.size > 2 * 1024 * 1024) {
                this.mostrarMensaje('La imagen no debe superar los 2 MB.', 'warning');
                event.target.value = '';
                return;
            }
            this.archivoFoto = archivo;
            this.fotoPreview = URL.createObjectURL(archivo);
        },

        async guardarFoto() {
            if (!this.archivoFoto || !this.fotoUsuario.id) return;
            this.subiendoFoto = true;
            try {
                const formData = new FormData();
                formData.append('foto', this.archivoFoto);
                const response = await axios.post(
                    BASE_URL + 'api/usuarios/' + this.fotoUsuario.id + '/foto',
                    formData,
                    { headers: { 'Content-Type': 'multipart/form-data' } }
                );
                bootstrap.Modal.getInstance(document.getElementById('modalFotoUsuario'))?.hide();
                this.archivoFoto = null;
                this.fotoPreview = null;
                this.mostrarMensaje('Foto actualizada.', 'success');
                await this.obtenerUsuarios();

                const foto = response.data?.foto_perfil;
                if (foto && this.usuarioSeleccionado?.id === this.fotoUsuario.id) {
                    this.usuarioSeleccionado.foto_perfil = foto;
                }
            } catch (error) {
                console.error('Error al subir la foto:', error);
                this.mostrarMensaje(this.mensajeErrorApi(error) || 'No se pudo subir la foto.', 'danger');
            } finally {
                this.subiendoFoto = false;
            }
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

        escapeHtml(texto) {
            return String(texto || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },

        mostrarConfirmacion(mensaje, titulo = 'Confirmar acción') {
            return new Promise((resolve) => {
                let resuelto = false;
                const modalHtml = `
                    <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content usuario-edit reclamo-confirm-modal">
                                <div class="usuario-edit__header">
                                    <div class="usuario-edit__title">
                                        <span class="usuario-edit__title-icon"><i class="bi bi-question-circle"></i></span>
                                        <h5>${titulo}</h5>
                                    </div>
                                    <button type="button" class="usuario-edit__close" data-bs-dismiss="modal" aria-label="Cerrar">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p class="reclamo-confirm-modal__message">${mensaje}</p>
                                </div>
                                <div class="cuadrilla-edit__footer reclamo-modal__footer--end">
                                    <button type="button" class="ce-btn-cancelar" data-bs-dismiss="modal" id="btnCancelar">Cancelar</button>
                                    <button type="button" class="ce-btn-guardar" id="btnConfirmar"><i class="bi bi-check-lg"></i> Confirmar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#modalConfirmacion').remove();
                $('body').append(modalHtml);
                const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
                modal.show();

                const cerrar = (resultado) => {
                    if (resuelto) return;
                    resuelto = true;
                    modal.hide();
                    setTimeout(() => $('#modalConfirmacion').remove(), 300);
                    resolve(resultado);
                };

                $('#btnConfirmar').on('click', () => cerrar(true));
                $('#btnCancelar').on('click', () => cerrar(false));
                $('#modalConfirmacion').on('hidden.bs.modal', () => {
                    $('#modalConfirmacion').remove();
                    if (!resuelto) {
                        resuelto = true;
                        resolve(false);
                    }
                });
            });
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
            }, 3200);
        },
    },

    async mounted() {
        // tools.js monta la app; el id del usuario logueado puede venir del menú/sesión en DOM
        const meta = document.querySelector('meta[name="user-id"]');
        if (meta) {
            this.usuarioActualId = meta.getAttribute('content');
        } else if (window.USER_ID != null) {
            this.usuarioActualId = window.USER_ID;
        }

        await Promise.all([this.obtenerRoles(), this.obtenerUsuarios()]);
    },
});
