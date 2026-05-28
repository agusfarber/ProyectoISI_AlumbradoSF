<div id="app" class="container-fluid">
    <div>Cierre</div>

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link" :class="{ active: solapaCierre === 'pendientes' }" @click="cambiarSolapaCierre('pendientes')">
                Completados
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link" :class="{ active: solapaCierre === 'cerrados' }" @click="cambiarSolapaCierre('cerrados')">
                Cerrados
            </button>
        </li>
    </ul>

    <!-- Reclamos pendientes de cierre -->
    <div v-show="solapaCierre === 'pendientes'">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <span v-if="reclamosSeleccionados.length > 0" class="badge bg-secondary">
                {{ reclamosSeleccionados.length }} seleccionado(s)
            </span>
            <span v-else class="text-muted small">Seleccioná reclamos completados para cerrar formalmente.</span>

            <button class="btn btn-success btn-sm" @click="cerrarReclamosSeleccionados" :disabled="reclamosSeleccionados.length === 0 || procesando">
                <span v-if="!procesando">
                    <i class="bi bi-lock-fill me-1"></i>Cerrar seleccionados
                </span>
                <span v-else>
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Cerrando...
                </span>
            </button>
        </div>

        <div class="table-responsive">
            <table id="tabla_cierre_reclamos" class="table table-bordered table-hover table-sm align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">
                            <input type="checkbox" class="form-check-input" @change="toggleSeleccionTodos" :checked="todosMarcados">
                        </th>
                        <th>ID</th>
                        <th>Motivo</th>
                        <th>Domicilio</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Modificación</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Contenido de la tabla gestionado por DataTables -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reclamos cerrados -->
    <div v-show="solapaCierre === 'cerrados'">
        <div class="d-flex justify-content-end mb-2">
            <small class="text-muted">Última actualización: {{ ultimaActualizacion }}</small>
        </div>
        <div class="table-responsive">
            <table id="tabla_reclamos_cerrados" class="table table-bordered table-hover table-sm align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Motivo</th>
                        <th>Domicilio</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Modificación</th>
                        <th>Fecha Cierre</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Contenido de la tabla gestionado por DataTables -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Ver Detalles Reclamo -->
    <div class="modal fade" id="modalVerReclamo" tabindex="-1">
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
                            <div class="mb-3" v-if="reclamoSeleccionado.fecha_cierre">
                                <label class="fw-bold">Fecha de Cierre:</label>
                                <p><span class="badge bg-success">{{ formatearFecha(reclamoSeleccionado.fecha_cierre) }}</span></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Recepción:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_recepcion || 'No especificado' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Estado:</label>
                                <p>
                                    <span class="badge bg-success" v-if="reclamoSeleccionado.cerrado == 1">Cerrado</span>
                                    <span class="badge bg-primary" v-else>{{ reclamoSeleccionado.municipalidad_estado }}</span>
                                </p>
                            </div>
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
                                <p>{{ reclamoSeleccionado.municipalidad_domicilio || 'No especificado' }} {{ reclamoSeleccionado.municipalidad_numeroDomicilio }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
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
                        </div>
                        <div class="col-12">
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
</div>

