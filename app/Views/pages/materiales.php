<div id="app" class="container-fluid">
    <div>Materiales</div>

    <div class="d-flex gap-2 mb-3">
        <button class="btn btn-primary" @click="abrirFormulario()">+ Nuevo Material</button>

        <div class="d-flex align-items-center gap-2">
            <input type="file" id="inputArchivoMateriales" class="form-control" @change="onArchivoSeleccionado" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
            <button class="btn btn-success" :disabled="!archivoSeleccionado" @click="importarArchivo">Importar</button>
        </div>
    </div>

    <table id="tabla_materiales" class="table table-bordered table-hover w-100">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Cantidad</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="material in materiales" :key="material.id">
                <td>{{ material.nombre }}</td>
                <td>{{ material.cantidad }}</td>
                <td>
                    <button class="btn btn-sm btn-warning me-1" @click="editarMaterial(material)" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" @click="eliminarMaterial(material)" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Modal Material -->
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

