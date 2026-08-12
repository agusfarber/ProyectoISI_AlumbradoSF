<div id="app" class="usuarios-page">

    <div class="usuarios-toolbar">
        <button type="button" class="btn-nueva" @click="abrirFormulario()">
            <i class="bi bi-plus-lg"></i> Nuevo usuario
        </button>
        <div class="usuarios-chips">
            <button
                type="button"
                class="usuarios-chip"
                :class="{ active: filtroRol === 'Todos' }"
                @click="setFiltroRol('Todos')">
                Todos
                <span class="usuarios-chip__count">{{ usuarios.length }}</span>
            </button>
            <button
                v-for="rol in roles"
                :key="rol.id"
                type="button"
                class="usuarios-chip"
                :class="{ active: String(filtroRol) === String(rol.id) }"
                @click="setFiltroRol(rol.id)">
                {{ rol.nombre }}
                <span class="usuarios-chip__count">{{ contarPorRol(rol.id) }}</span>
            </button>
        </div>
    </div>

    <div v-if="cargando" class="usuarios-loading">
        <div class="spinner-border text-secondary" role="status"></div>
        <span>Cargando usuarios…</span>
    </div>

    <div v-show="!cargando" class="usuarios-table-section">
        <table id="tabla_usuarios" class="table table-hover table-sm align-middle w-100 mb-0 usuarios-table">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Acceso</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>


    <!-- Modal crear / editar -->
    <div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 560px;">
            <div class="modal-content usuario-edit">
                <form @submit.prevent="guardarUsuario">
                    <div class="usuario-edit__header">
                        <div class="usuario-edit__title">
                            <span class="usuario-edit__title-icon"><i class="bi bi-person-plus-fill"></i></span>
                            <h5>{{ usuario.id ? 'Editar usuario' : 'Nuevo usuario' }}</h5>
                        </div>
                        <button type="button" class="usuario-edit__close" data-bs-dismiss="modal" aria-label="Cerrar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <div class="usuario-edit__body">
                        <div class="ue-form">
                            <div class="ue-field">
                                <label for="usrNombre">Nombre completo</label>
                                <input id="usrNombre" type="text" v-model.trim="usuario.nombre" required placeholder="Ej: Juan Pérez">
                            </div>
                            <div class="ue-field">
                                <label for="usrRol">Rol</label>
                                <select id="usrRol" v-model="usuario.idRol" required @change="onCambioRolFormulario">
                                    <option value="" disabled>Seleccionar rol</option>
                                    <option v-for="rol in roles" :key="rol.id" :value="rol.id">{{ rol.nombre }}</option>
                                </select>
                            </div>
                            <div class="ue-field" v-if="esRolAdmin(usuario.idRol)">
                                <label for="usrEmail">Email</label>
                                <input id="usrEmail" type="email" v-model.trim="usuario.email" required placeholder="usuario@ejemplo.com">
                            </div>
                            <div class="ue-field" v-else-if="esRolConLegajo(usuario.idRol)">
                                <label for="usrLegajo">Legajo</label>
                                <input id="usrLegajo" type="text" v-model.trim="usuario.legajo" required placeholder="Ej: 12345">
                            </div>
                            <div class="ue-field">
                                <label for="usrPass">
                                    Contraseña
                                    <span class="ue-opt" v-if="usuario.id">dejar vacío para no cambiar</span>
                                </label>
                                <div class="ue-pass">
                                    <input
                                        id="usrPass"
                                        :type="mostrarPass ? 'text' : 'password'"
                                        v-model="usuario.contrasena"
                                        :required="!usuario.id"
                                        :placeholder="usuario.id ? '••••••••' : 'Mínimo 4 caracteres'"
                                        autocomplete="new-password">
                                    <button type="button" class="ue-pass__toggle" @click="mostrarPass = !mostrarPass" :title="mostrarPass ? 'Ocultar' : 'Mostrar'">
                                        <i :class="mostrarPass ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                                    </button>
                                </div>
                            </div>
                            <p class="ue-hint" v-if="usuario.idRol">
                                <i class="bi bi-info-circle"></i>
                                <span v-if="esRolAdmin(usuario.idRol)">Los administradores ingresan al sistema con email.</span>
                                <span v-else>Supervisores y operarios ingresan al sistema con legajo.</span>
                            </p>
                        </div>
                    </div>

                    <div class="cuadrilla-edit__footer reclamo-modal__footer--end">
                        <button type="button" class="ce-btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="ce-btn-guardar" :disabled="guardando">
                            <span v-if="guardando" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-check-lg"></i>
                            {{ guardando ? 'Guardando…' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal detalle -->
    <div class="modal fade" id="modalVerUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
            <div class="modal-content usuario-edit">
                <div class="usuario-edit__header">
                    <div class="usuario-edit__title">
                        <span class="usuario-edit__title-icon"><i class="bi bi-person-vcard"></i></span>
                        <h5>Detalle del usuario</h5>
                    </div>
                    <button type="button" class="usuario-edit__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="usuario-edit__body">
                    <div class="ue-detalle">
                        <div class="ue-detalle__avatar" @click="abrirModalFoto(usuarioSeleccionado)" title="Cambiar foto">
                            <img v-if="usuarioSeleccionado.foto_perfil" :src="urlFoto(usuarioSeleccionado.foto_perfil)" :alt="usuarioSeleccionado.nombre">
                            <span v-else :style="{ backgroundColor: colorAvatar(usuarioSeleccionado.nombre) }">
                                {{ iniciales(usuarioSeleccionado.nombre) }}
                            </span>
                            <i class="bi bi-camera"></i>
                        </div>
                        <h3>{{ usuarioSeleccionado.nombre }}</h3>
                        <span class="usuario-card__rol" :class="'usuario-card__rol--' + claseRol(usuarioSeleccionado.idRol)">
                            {{ getNombreRol(usuarioSeleccionado.idRol) || 'Sin rol' }}
                        </span>
                        <dl class="ue-detalle__list">
                            <div>
                                <dt>Email</dt>
                                <dd>{{ usuarioSeleccionado.email || '—' }}</dd>
                            </div>
                            <div>
                                <dt>Legajo</dt>
                                <dd>{{ usuarioSeleccionado.legajo || '—' }}</dd>
                            </div>
                            <div>
                                <dt>ID</dt>
                                <dd>#{{ usuarioSeleccionado.id }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
                <div class="cuadrilla-edit__footer">
                    <button
                        type="button"
                        class="ce-btn-eliminar"
                        @click="eliminarUsuario(usuarioSeleccionado)"
                        :disabled="esUsuarioActual(usuarioSeleccionado)">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                    <div class="ce-footer-acciones">
                        <button type="button" class="ce-btn-cancelar" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="ce-btn-guardar" @click="editarDesdeDetalle">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal foto -->
    <div class="modal fade" id="modalFotoUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
            <div class="modal-content usuario-edit">
                <div class="usuario-edit__header">
                    <div class="usuario-edit__title">
                        <span class="usuario-edit__title-icon"><i class="bi bi-camera"></i></span>
                        <h5>Foto de perfil</h5>
                    </div>
                    <button type="button" class="usuario-edit__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="usuario-edit__body">
                    <p class="ue-foto-nombre">{{ fotoUsuario.nombre }}</p>
                    <div class="foto-preview-wrap">
                        <img v-if="fotoPreview" :src="fotoPreview" class="foto-preview" alt="Vista previa">
                        <span v-else class="foto-preview foto-preview--initials" :style="{ backgroundColor: colorAvatar(fotoUsuario.nombre) }">
                            {{ iniciales(fotoUsuario.nombre) }}
                        </span>
                    </div>
                    <label class="ue-file">
                        <i class="bi bi-image"></i>
                        <span>{{ archivoFoto ? archivoFoto.name : 'Elegir imagen' }}</span>
                        <input type="file" id="inputFotoUsuario" accept="image/jpeg,image/png,image/webp" @change="onFotoSeleccionada">
                    </label>
                    <small class="ue-hint">JPG, PNG o WEBP. Máximo 2 MB.</small>
                </div>
                <div class="cuadrilla-edit__footer reclamo-modal__footer--end">
                    <button type="button" class="ce-btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="ce-btn-guardar" @click="guardarFoto" :disabled="!archivoFoto || subiendoFoto">
                        <span v-if="subiendoFoto" class="spinner-border spinner-border-sm"></span>
                        <i v-else class="bi bi-check-lg"></i>
                        {{ subiendoFoto ? 'Subiendo…' : 'Guardar foto' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
