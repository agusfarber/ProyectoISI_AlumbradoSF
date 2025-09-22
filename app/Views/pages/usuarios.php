<div id="app" class="container-fluid">
    <div>Usuarios</div>
    <br>

    <!-- Botón para agregar usuario 
    <button class="btn btn-primary mb-3" @click="abrirFormulario()">+ Nuevo Usuario</button-->

    <!-- Tabla de usuarios -->
    <div class="table-responsive">
        <table id="tabla_usuarios" class="table table-bordered table-hover w-100">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email / Legajo</th>
                    <th>Rol</th>
                    <!--th>Acciones</th-->
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in usuarios" :key="user.id">
                    <td>{{ user.nombre }}</td>
                    <td>{{ user.email || user.legajo || 'No especificado' }}</td>
                    <td>{{ getNombreRol(user.idRol) }}</td>
                    <!--
                    <td>
                        <button class="btn btn-sm btn-info me-1" @click="verUsuario(user)" title="Ver detalles">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-warning me-1" @click="editarUsuario(user)" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" @click="eliminarUsuario(user)" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                    -->
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Modal Usuario -->
    <div class="modal fade" id="modalUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form @submit.prevent="guardarUsuario">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ usuario.id ? 'Editar Usuario' : 'Nuevo Usuario' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label>Nombre</label>
                            <input type="text" class="form-control" v-model="usuario.nombre" required>
                        </div>
                        <div class="mb-2">
                            <label>Email</label>
                            <input type="email" class="form-control" v-model="usuario.email" required>
                        </div>
                        <div class="mb-2">
                            <label>Contraseña</label>
                            <input type="password" class="form-control" v-model="usuario.contrasena" required>
                        </div>
                        <div class="mb-2">
                            <label>Rol</label>
                            <select class="form-control" v-model="usuario.idRol" required>
                                <option value="" disabled>Seleccionar</option>
                                <option v-for="rol in roles" :value="rol.id">{{ rol.nombre }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Guardar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>    
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalles Usuario -->
    <div class="modal fade" id="modalVerUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="fw-bold">ID:</label>
                        <p>{{ usuarioSeleccionado.id }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Nombre:</label>
                        <p>{{ usuarioSeleccionado.nombre }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Email:</label>
                        <p>{{ usuarioSeleccionado.email || 'No especificado' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Legajo:</label>
                        <p>{{ usuarioSeleccionado.legajo || 'No especificado' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Rol:</label>
                        <p>{{ getNombreRol(usuarioSeleccionado.idRol) }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

