<div id="app" class="container-fluid">
    <style>
        #tabla_cuadrillas tbody tr {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        #tabla_cuadrillas tbody tr:hover {
            background-color: #f8f9fa !important;
        }
        #tabla_cuadrillas tbody tr.table-primary {
            background-color: #0d6efd !important;
            color: white;
        }
        #tabla_cuadrillas tbody tr.table-primary:hover {
            background-color: #0b5ed7 !important;
        }
    </style>
    <div>
        Gestión de Cuadrillas
    </div>

    <!-- Botones de acción y filtros -->
    <div class="d-flex justify-content-between mb-3">
        <!-- Botones a la izquierda -->
        <div>
            <button class="btn btn-primary me-2" @click="abrirFormulario()">+ Nueva Cuadrilla</button>
            <button class="btn btn-success" 
                    @click="abrirAdministracionCuadrilla()"
                    :disabled="!cuadrillaSeleccionada"
                    :title="cuadrillaSeleccionada ? 'Administrar la cuadrilla seleccionada' : 'Seleccione una cuadrilla primero'">
                <i class="bi bi-gear-fill"></i> ADMINISTRAR CUADRILLA
            </button>
        </div>
        
        <!-- Botón de filtros a la derecha -->
        <button class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#filtrosPanel">
            <i class="bi bi-funnel"></i> Filtros
        </button>
    </div>

    <!-- Panel de Filtros colapsable -->
    <div class="collapse mb-3" id="filtrosPanel">
        <div class="row align-items-end">
            <div class="col-md-4 mb-2 mb-md-0">
                <label for="filtroBusqueda" class="form-label">Buscar por nombre</label>
                <input type="text" id="filtroBusqueda" class="form-control" v-model="filtroBusqueda" @input="aplicarFiltros" placeholder="Buscar cuadrilla...">
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label for="filtroOperarios" class="form-label">Filtrar por operarios</label>
                <select id="filtroOperarios" class="form-select" v-model="filtroOperarios" @change="aplicarFiltros">
                    <option value="">Todas las cuadrillas</option>
                    <option value="con-operarios">Con operarios asignados</option>
                    <option value="sin-operarios">Sin operarios asignados</option>
                </select>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label for="filtroCantidadOperarios" class="form-label">Cantidad de operarios</label>
                <select id="filtroCantidadOperarios" class="form-select" v-model="filtroCantidadOperarios" @change="aplicarFiltros">
                    <option value="">Cualquier cantidad</option>
                    <option value="1">1 operario</option>
                    <option value="2">2 operarios</option>
                    <option value="3">3 operarios</option>
                    <option value="4">4 operarios</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-grid gap-2 w-100">
                    <button class="btn btn-outline-secondary" @click="limpiarFiltros">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>


    <!-- Tabla de cuadrillas -->
    <div class="table-responsive">
        <table id="tabla_cuadrillas" class="table table-bordered table-hover w-100">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Operarios</th>
                </tr>
            </thead>
            <tbody>
                <!-- Contenido de la tabla gestionado por DataTables -->
            </tbody>
        </table>
    </div>

    <!-- Modal Cuadrilla -->
    <div class="modal fade" id="modalCuadrilla" tabindex="-1">
        <div class="modal-dialog modal-lg" style="max-width: 70vw; max-height: 90vh;">
            <div class="modal-content">
                <form @submit.prevent="guardarCuadrilla">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ cuadrilla.id ? 'Administrar Cuadrilla' : 'Nueva Cuadrilla' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Nombre de la Cuadrilla</strong></label>
                                    <input type="text" class="form-control" v-model="cuadrilla.nombre" :required="!cuadrilla.id" placeholder="Ingrese el nombre de la cuadrilla">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><strong>Descripción</strong> <small class="text-muted">(Opcional)</small></label>
                                    <textarea class="form-control" v-model="cuadrilla.descripcion" rows="4" placeholder="Descripción de la cuadrilla..."></textarea>
                                </div>
                            </div>
                            <div class="col-md-4" v-if="cuadrilla.id">
                                <!-- Sección Agregar Operarios expandida hacia la derecha -->
                                <h6 class="mb-3"><i class="bi bi-person-plus text-success"></i> <strong>Agregar Operarios</strong></h6>
                                <div class="table-responsive" style="height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                                    <table class="table table-hover table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 15%;">
                                                    <input type="checkbox" @change="toggleSeleccionTodosOperariosEdicion($event)" title="Seleccionar todos">
                                                </th>
                                                <th style="width: 40%;">Nombre</th>
                                                <th style="width: 45%;">Legajo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="operario in operariosDisponiblesParaEdicion" :key="operario.id">
                                                <td>
                                                    <input type="checkbox" v-model="operariosSeleccionadosEdicion" :value="operario.id">
                                                </td>
                                                <td>{{ operario.nombre }}</td>
                                                <td>{{ operario.email || operario.legajo }}</td>
                                            </tr>
                                            <tr v-if="!operariosDisponiblesParaEdicion || operariosDisponiblesParaEdicion.length === 0">
                                                <td colspan="3" class="text-muted text-center py-3">
                                                    <i class="bi bi-info-circle"></i> No hay operarios disponibles para asignar
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-success btn-sm" @click="agregarOperariosSeleccionados" :disabled="operariosSeleccionadosEdicion.length === 0">
                                        <i class="bi bi-plus-circle"></i> Agregar Seleccionados 
                                        <span v-if="operariosSeleccionadosEdicion.length > 0" class="badge bg-light text-dark ms-1">{{ operariosSeleccionadosEdicion.length }}</span>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-5" v-if="cuadrilla.id">
                                <h6 class="mb-3"><i class="bi bi-people-fill text-primary"></i> <strong>Operarios Asignados</strong></h6>
                                <div class="table-responsive" style="height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem;">
                                    <table class="table table-hover table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40%;">Nombre</th>
                                                <th style="width: 40%;">Legajo</th>
                                                <th style="width: 20%;" class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="operario in cuadrilla.operarios" :key="operario.id">
                                                <td><strong>{{ operario.nombre }}</strong></td>
                                                <td>{{ operario.email || operario.legajo }}</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-danger" @click="quitarOperario(operario.id)" title="Quitar operario">
                                                        <i class="bi bi-trash text-white"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="!cuadrilla.operarios || cuadrilla.operarios.length === 0">
                                                <td colspan="3" class="text-muted text-center py-4">
                                                    <i class="bi bi-info-circle"></i> Sin operarios asignados
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex justify-content-between w-100">
                            <div>
                                <button type="button" class="btn btn-danger" @click="eliminarCuadrillaCompleta" v-if="cuadrilla.id">
                                    <i class="bi bi-trash text-white"></i> Eliminar Cuadrilla
                                </button>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-success">Guardar</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            </div>
                        </div>
                    </div>    
                </form>
            </div>
        </div>
    </div>



</div>