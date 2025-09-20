<div id="app" class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">Gestión de Tokens de Acceso</h2>
        </div>
    </div>

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
                            <label class="form-label fw-bold">Client ID:</label>
                            <p class="text-break">{{ tokenActual.client_id }}</p>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">Client Secret:</label>
                            <p class="text-break">{{ tokenActual.client_secret }}</p>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">Tipo de Token:</label>
                            <p class="mb-1">{{ tokenActual.token_type || 'Bearer' }}</p>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">Expira en:</label>
                            <p class="mb-1">{{ tokenActual.expires_in || 'N/A' }} segundos</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Access Token:</label>
                            <div class="input-group">
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    :value="tokenActual.access_token" 
                                    readonly
                                    id="tokenInput"
                                >
                                
                                <span 
                                    v-if="mensajeCopiadoVisible" 
                                    class="text-success small-text" 
                                    style="position: absolute; top: -25px; right: 5px;"
                                >
                                    Copiado
                                </span>
                                
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
                </div>
            </div>
        </div>
    </div>
</div>