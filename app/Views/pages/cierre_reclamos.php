<div id="app" class="container-fluid">
    <div>Cierre</div>

    <!-- Instrucciones -->
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Instrucciones:</strong> Seleccione uno o varios reclamos completados para cerrarlos formalmente. 
        Una vez cerrados, los reclamos quedarán bloqueados para edición y se registrará la fecha de cierre.
    </div>

    <!-- Resumen de reclamos -->
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card text-white bg-primary card-resumen">
                <div class="card-body p-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-list-check fs-4 me-2"></i>
                        <div>
                            <small class="d-block">Completados</small>
                            <strong class="fs-5">{{ reclamosCompletados.length }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success card-resumen">
                <div class="card-body p-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-lock-fill fs-4 me-2"></i>
                        <div>
                            <small class="d-block">Cerrados</small>
                            <strong class="fs-5">{{ reclamosCerrados.length }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--div class="col-md-3">
            <div class="card text-white bg-secondary card-resumen">
                <div class="card-body p-2">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-calendar-event fs-4 me-2"></i>
                        <div>
                            <small class="d-block">Actualización</small>
                            <strong class="fs-6">{{ ultimaActualizacion }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div-->
    </div>

    <!-- Botones de acción -->
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-success btn-lg" @click="cerrarReclamosSeleccionados" :disabled="reclamosSeleccionados.length === 0 || procesando">
            <span v-if="!procesando">
                <i class="bi bi-lock-fill me-2"></i>Cerrar Reclamos ({{ reclamosSeleccionados.length }})
            </span>
            <span v-else>
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Procesando...
            </span>
        </button>
    </div>

    <!-- Tabla de reclamos completados -->
    <div class="mb-4">
        <h5 class="mb-3">
            <i class="bi bi-list-check me-2"></i>Reclamos Completados (Pendientes de Cierre)
        </h5>
        <div class="table-responsive">
            <table id="tabla_cierre_reclamos" class="table table-bordered table-hover w-100">
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

    <!-- Tabla de reclamos cerrados -->
    <div class="mb-4">
        <h5 class="mb-3">
            <i class="bi bi-lock-fill me-2"></i>Reclamos Cerrados
        </h5>
        <div class="table-responsive">
            <table id="tabla_reclamos_cerrados" class="table table-bordered table-hover w-100">
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

