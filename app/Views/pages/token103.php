<div id="app" class="container-fluid">

    <div>Token de autenticación Sistema 103</div>
    <br>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-gear"></i> Configuración del token
                    </h5>
                </div>
                <div class="card-body">
                    <form @submit.prevent="guardarToken">
                        <div class="mb-3">
                            <label for="apiToken" class="form-label">API Token</label>
                            <input
                                type="text"
                                id="apiToken"
                                class="form-control"
                                v-model.trim="form.api_token"
                                placeholder="Pegá el token provisto por el 103"
                                required
                            >
                            <small class="text-muted">Se envía como: Authorization: Token {valor}</small>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar token
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
                        <i class="bi bi-info-circle"></i> Estado
                    </h5>
                </div>
                <div class="card-body">
                    <div v-if="tokenGuardado" class="alert alert-success">
                        <h6><i class="bi bi-check-circle"></i> Token configurado</h6>
                        <small class="text-muted">Listo para sincronizar y cerrar reclamos con el 103.</small>
                    </div>
                    <div v-else class="alert alert-warning">
                        <h6><i class="bi bi-exclamation-triangle"></i> Sin token</h6>
                        <small class="text-muted">Configurá el token para poder usar la API del 103.</small>
                    </div>

                    <div v-if="tokenGuardado" class="mt-3">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Token guardado:</label>
                            <div class="input-group position-relative">
                                <input
                                    type="text"
                                    class="form-control"
                                    :value="tokenEnmascarado"
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
                            <small class="text-muted">Authorization: Token {token}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
