<div id="app" class="container-fluid">
    <div>Gestión de Materiales</div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button class="btn btn-primary" @click="abrirFormulario()">+ Nuevo Material</button>
        <div class="d-flex align-items-center gap-2">
            <input type="file" 
                id="inputArchivoMateriales" 
                class="form-control" 
                @change="onArchivoSeleccionado" 
                accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
            <button class="btn btn-outline-secondary" 
                :disabled="!archivoSeleccionado" 
                @click="importarArchivo">
                Importar
            </button>
            <button class="btn btn-success" @click="abrirModalTipos()">Gestionar Tipos</button>
        </div>
    </div>
    <div class="table-responsive">
        <table id="tabla_materiales" class="table table-bordered table-hover w-100">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Cantidad</th>
                    <th>Tipo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Contenido de la tabla gestionado por DataTables -->
            </tbody>
        </table>
    </div>
    
    <div class="modal fade" id="modalMaterial" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form @submit.prevent="guardarMaterial">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ material.id ? 'Editar Material' : 'Nuevo Material' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label>Nombre</label>
                            <input type="text" class="form-control" v-model="material.nombre" required>
                        </div>
                        <div class="mb-2">
                            <label>Cantidad</label>
                            <input type="number" min="0" class="form-control" v-model.number="material.cantidad" required>
                        </div>
                        <div class="mb-2">
                            <label>Tipo de Material</label>
                            <select class="form-select" v-model="material.idTipo">
                                <option value="">Sin tipo</option>
                                <option v-for="tipo in tiposMaterial" :key="tipo.id" :value="tipo.id">
                                    {{ tipo.nombre }}
                                </option>
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
    
    <div class="modal fade" id="modalTiposMateriales" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gestión de Tipos de Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="guardarTipo" class="mb-4">
                        <div class="input-group">
                            <input type="text" class="form-control" v-model="tipo.nombre" placeholder="Nombre del nuevo tipo" required>
                            <button type="submit" class="btn btn-success">Agregar Tipo</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table id="tabla_tipos" class="table table-bordered table-hover w-100">
                            <thead>
                                <tr>
                                    <th class="text-start">ID</th>
                                    <th class="text-start">Nombre</th>
                                    <th class="text-start">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Contenido de la tabla gestionado por DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>