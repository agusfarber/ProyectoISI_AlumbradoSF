<div id="app" class="container-fluid">
    <div>Gestión de Tareas</div>
    <br>
    <!-- Panel de filtros comentado - HU para más adelante -->
    <!--
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#filtrosPanel">
                <i class="bi bi-funnel"></i> Filtros
            </button>
        </div>
    </div>

    <div class="collapse mb-3" id="filtrosPanel">
        <div class="card">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label for="filtroEstado" class="form-label">Filtrar por Estado</label>
                        <select id="filtroEstado" class="form-select" v-model="filtroEstado" @change="aplicarFiltros">
                            <option value="">Todos los estados</option>
                            <option value="Recibido">Recibido</option>
                            <option value="Asignado">Asignado</option>
                            <option value="En ejecución">En ejecución</option>
                            <option value="Completado">Completado</option>
                            <option value="En plan">En plan</option>
                            <option value="Error de datos">Error de datos</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label for="filtroPrioridad" class="form-label">Filtrar por Prioridad</label>
                        <select id="filtroPrioridad" class="form-select" v-model="filtroPrioridad" @change="aplicarFiltros">
                            <option value="">Todas las prioridades</option>
                            <option value="Baja">Baja</option>
                            <option value="Media">Media</option>
                            <option value="Alta">Alta</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label for="filtroFechaDesde" class="form-label">Fecha Desde</label>
                        <input type="date" id="filtroFechaDesde" class="form-control" v-model="filtroFechaDesde" @change="aplicarFiltros">
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label for="filtroFechaHasta" class="form-label">Fecha Hasta</label>
                        <input type="date" id="filtroFechaHasta" class="form-control" v-model="filtroFechaHasta" @change="aplicarFiltros">
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
        </div>
    </div>
    -->

    <!-- Listado ágil de reclamos -->
    <div class="row" v-if="reclamos.length > 0">
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2 mb-2" v-for="reclamo in reclamosFiltrados" :key="reclamo.id">
            <div class="card h-100 reclamo-card" :class="getCardClass(reclamo)" @click="verDetalles(reclamo)">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <h6 class="card-title mb-0 text-primary fw-bold">
                            {{ reclamo.municipalidad_id }}
                        </h6>
                        <span class="badge" :class="getEstadoBadgeClass(reclamo.municipalidad_estado)">
                            {{ reclamo.municipalidad_estado }}
                        </span>
                    </div>
                    
                    <div class="mb-1">
                        <small class="text-dark">
                            <i class="bi bi-geo-alt me-1"></i>
                            {{ reclamo.municipalidad_domicilio }} {{ reclamo.municipalidad_numeroDomicilio }}
                        </small>
                    </div>
                    
                    <div class="mb-1">
                        <small class="text-dark">
                            <i class="bi bi-tag me-1"></i>
                            {{ reclamo.municipalidad_motivo }}
                        </small>
                    </div>
                </div>
                
                <!-- Acciones rápidas -->
                <div class="card-footer bg-transparent">
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-primary flex-fill" @click.stop="cambiarEstado(reclamo)" title="Acciones">
                            Acciones
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensaje cuando no hay reclamos -->
    <div v-else class="text-center py-5">
        <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
        <h4 class="text-muted mt-3">No hay reclamos disponibles</h4>
        <p class="text-muted">No se encontraron reclamos que coincidan con los filtros aplicados.</p>
    </div>

    <!-- Modal Ver Detalles Reclamo -->
    <div class="modal fade" id="modalDetalles" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Reclamo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">ID Municipalidad:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_id }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Tipo:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_tipo }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Motivo:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_motivo }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Fecha de Inicio:</label>
                                <p>{{ formatearFecha(reclamoSeleccionado.municipalidad_fechaInicio) }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Fecha de Modificación:</label>
                                <p>{{ formatearFecha(reclamoSeleccionado.municipalidad_fechaModificacion) }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Recepción:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_recepcion }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Estado:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_estado }}</p>
                            </div>
                            <!-- Nuevo campo para visualizar la prioridad, ahora 'prioridad' -->
                            <div class="mb-3">
                                <label class="fw-bold">Prioridad:</label>
                                <p>{{ reclamoSeleccionado.prioridad || 'No especificado' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Teléfono:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_telefono || 'No especificado' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Domicilio:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_domicilio || 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Número Domicilio:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_numeroDomicilio || 'No especificado' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Entre Calle Uno:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_entreCalleUno || 'No especificado' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Entre Calle Dos:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_entreCalleDos || 'No especificado' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Ciudadano:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_ciudadano || 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Descripción:</label>
                        <p>{{ reclamoSeleccionado.municipalidad_descripcion || 'No especificado' }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Acciones -->
    <div class="modal fade" id="modalAcciones" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Acciones - Reclamo #{{ reclamoSeleccionado.municipalidad_id }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Estado Actual:</label>
                        <br>
                        <span class="badge" :class="getEstadoBadgeClass(reclamoSeleccionado.municipalidad_estado)">
                            {{ reclamoSeleccionado.municipalidad_estado }}
                        </span>
                    </div>
                    <div class="mb-3">
                        <label for="nuevoEstado" class="form-label">Nuevo Estado:</label>
                        <select id="nuevoEstado" class="form-select" v-model="nuevoEstado" required>
                            <option value="" disabled>Seleccionar nuevo estado</option>
                            <option value="Recibido">Recibido</option>
                            <option value="Asignado">Asignado</option>
                            <option value="En ejecución">En ejecución</option>
                            <option value="Completado">Completado</option>
                            <option value="En plan">En plan</option>
                            <option value="Error de datos">Error de datos</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="guardarCambioEstado">
                        <i class="bi bi-check-circle me-1 text-white"></i>Guardar Cambio de Estado
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
