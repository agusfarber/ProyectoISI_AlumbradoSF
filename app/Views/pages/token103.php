<div id="app" class="container-fluid">
    
    <div>Gestión de Credenciales Basic Auth</div>
    <br>

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
                            <label for="username" class="form-label">Username</label>
                            <input 
                                type="text" 
                                id="username" 
                                class="form-control" 
                                v-model="credenciales.username" 
                                @input="generarTokenBase64"
                                placeholder="Ingrese el username"
                                required
                            >
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input 
                                type="password" 
                                id="password" 
                                class="form-control" 
                                v-model="credenciales.password" 
                                @input="generarTokenBase64"
                                placeholder="Ingrese el password"
                                required
                            >
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar Credenciales
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
                    <div v-if="credencialesGuardadas && tokenBase64" class="alert alert-success">
                        <h6><i class="bi bi-check-circle"></i> Credenciales Configuradas</h6>
                        <small class="text-muted">
                            Token Basic Auth generado automáticamente
                        </small>
                    </div>
                    <div v-else class="alert alert-warning">
                        <h6><i class="bi bi-exclamation-triangle"></i> Sin Credenciales</h6>
                        <small class="text-muted">Configure el username y password para generar el token</small>
                    </div>
                    
                    <div v-if="credencialesGuardadas" class="mt-3">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Username:</label>
                            <p class="text-break">{{ tokenActual.username }}</p>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold">Password:</label>
                            <p class="text-break">{{ tokenActual.password ? '••••••••••' : '' }}</p>
                        </div>
                        <div class="mb-3" v-if="tokenBase64">
                            <label class="form-label fw-bold">Token Basic Auth (Base64):</label>
                            <div class="input-group position-relative">
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    :value="tokenBase64" 
                                    readonly
                                    id="tokenInput"
                                >
                                
                                <span 
                                    v-if="mensajeCopiadoVisible" 
                                    class="text-success small-text" 
                                    style="position: absolute; top: -25px; right: 5px; z-index: 10;"
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
                            <small class="text-muted">Haga clic en el botón para copiar el token. Use como: Authorization: Basic {token}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>