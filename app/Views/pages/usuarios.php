<div id="app" class="container-fluid">
    <div>Usuarios</div>

    <!-- Botón para agregar usuario -->
    <button class="btn btn-primary mb-3" @click="abrirFormulario()">+ Nuevo Usuario</button>

    <!-- Tabla de usuarios -->
    <table id="tabla_usuarios" class="table table-bordered table-hover w-100">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="user in usuarios" :key="user.id">
                <td>{{ user.nombre }}</td>
                <td>{{ user.email }}</td>
                <td>{{ getNombreRol(user.idRol) }}</td>
                <td>
                    <button class="btn btn-sm btn-warning me-1" @click="editarUsuario(user)">Editar</button>
                    <button class="btn btn-sm btn-danger" @click="eliminarUsuario(user)">Eliminar</button>
                </td>
            </tr>
        </tbody>
    </table>

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
</div>

