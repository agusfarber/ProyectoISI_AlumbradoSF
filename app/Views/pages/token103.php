<div id="app" class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">Gestión de Tokens de Acceso</h2>
        </div>
    </div>

    <!-- Formulario de configuración -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-gear"></i> Configuración de Credenciales
                    </h5>
                </div>
                <div class="card-body">
                    <form @submit.prevent="guardarCredenciales">
                        <div class="mb-3">
                            <label for="client_id" class="form-label">Client ID</label>
                            <input 
                                type="text" 
                                id="client_id" 
                                class="form-control" 
                                v-model="credenciales.client_id" 
                                placeholder="Ingrese el Client ID"
                                required
                            >
                        </div>
                        <div class="mb-3">
                            <label for="client_secret" class="form-label">Client Secret</label>
                            <input 
                                type="password" 
                                id="client_secret" 
                                class="form-control" 
                                v-model="credenciales.client_secret" 
                                placeholder="Ingrese el Client Secret"
                                required
                            >
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar Credenciales
                            </button>
                            <button 
                                type="button" 
                                class="btn btn-success" 
                                @click="generarToken"
                                :disabled="!credencialesGuardadas"
                            >
                                <i class="bi bi-key"></i> Generar Token
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Información del token actual -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle"></i> Información del Token
                    </h5>
                </div>
                <div class="card-body">
                    <div v-if="tokenActual.access_token" class="alert alert-success">
                        <h6><i class="bi bi-check-circle"></i> Token Activo</h6>
                        <small class="text-muted">
                            Generado: {{ formatearFecha(tokenActual.fecha_generacion) }}
                        </small>
                    </div>
                    <div v-else class="alert alert-warning">
                        <h6><i class="bi bi-exclamation-triangle"></i> Sin Token Activo</h6>
                        <small class="text-muted">Configure las credenciales y genere un token</small>
                    </div>
                    
                    <div v-if="tokenActual.access_token" class="mt-3">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Tipo de Token:</label>
                            <p class="mb-1">{{ tokenActual.token_type || 'Bearer' }}</p>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">Expira en:</label>
                            <p class="mb-1">{{ tokenActual.expires_in || 'N/A' }} segundos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de tokens -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-table"></i> Historial de Tokens
                    </h5>
                </div>
                <div class="mx-3">
                    <div>
                        <table id="tabla_tokens" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client ID</th>
                                    <th>Client Secret</th>
                                    <th>Access Token</th>
                                    <th>Tipo</th>
                                    <th>Expira en</th>
                                    <th>Fecha Generación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Contenido de la tabla gestionado por DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver token completo -->
    <div class="modal fade" id="modalVerToken" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Token</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Client ID:</label>
                                <p class="text-break">{{ tokenSeleccionado.client_id }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Client Secret:</label>
                                <p class="text-break">{{ tokenSeleccionado.client_secret }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Tipo de Token:</label>
                                <p>{{ tokenSeleccionado.token_type || 'Bearer' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Expira en:</label>
                                <p>{{ tokenSeleccionado.expires_in || 'N/A' }} segundos</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Fecha de Generación:</label>
                                <p>{{ formatearFecha(tokenSeleccionado.fecha_generacion) }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Fecha de Creación:</label>
                                <p>{{ formatearFecha(tokenSeleccionado.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Access Token:</label>
                        <div class="input-group">
                            <input 
                                type="text" 
                                class="form-control" 
                                :value="tokenSeleccionado.access_token" 
                                readonly
                                id="tokenInput"
                            >
                            <button 
                                class="btn btn-outline-secondary" 
                                type="button"
                                @click="copiarToken"
                                title="Copiar token"
                            >
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <small class="text-muted">Haga clic en el botón para copiar el token</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
