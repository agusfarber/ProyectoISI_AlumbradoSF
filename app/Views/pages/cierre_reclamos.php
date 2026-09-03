<div id="app" class="cierre-page">

    <div class="app-page-title">
        <span class="app-page-title__icon"><i class="bi bi-lock-fill"></i></span>
        <h1 class="app-page-title__text">Cierre</h1>
    </div>

    <div class="cierre-toolbar">
        <div class="cierre-toolbar__left">
            <ul class="nav cierre-tabs" role="tablist">
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
            <span v-if="solapaCierre === 'pendientes' && reclamosSeleccionados.length > 0" class="cierre-badge">
                <i class="bi bi-check2-square"></i>
                {{ reclamosSeleccionados.length }} seleccionado(s)
            </span>
        </div>

        <div class="cierre-toolbar__right">
            <div v-if="solapaCierre === 'cerrados'" class="cierre-meta">
                <i class="bi bi-clock-history"></i>
                Última actualización: {{ ultimaActualizacion }}
            </div>
            <button v-if="solapaCierre === 'pendientes'" class="cierre-btn cierre-btn--success" @click="cerrarReclamosSeleccionados" :disabled="reclamosSeleccionados.length === 0 || procesando">
                <span v-if="!procesando">
                    <i class="bi bi-lock-fill"></i> Cerrar seleccionados
                </span>
                <span v-else>
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                    Cerrando...
                </span>
            </button>
        </div>
    </div>

    <!-- Reclamos pendientes de cierre -->
    <div v-show="solapaCierre === 'pendientes'" class="cierre-tab-panel">

        <div class="cierre-table-section">
            <table id="tabla_cierre_reclamos" class="table table-hover table-sm align-middle w-100 mb-0 cierre-table">
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
    <div v-show="solapaCierre === 'cerrados'" class="cierre-tab-panel">
        <div class="cierre-table-section">
            <table id="tabla_reclamos_cerrados" class="table table-hover table-sm align-middle w-100 mb-0 cierre-table">
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
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content cierre-modal">
                <div class="cierre-modal__header">
                    <div class="cierre-modal__title">
                        <span class="cierre-modal__icon"><i class="bi bi-card-text"></i></span>
                        <h5>Detalles del reclamo</h5>
                    </div>
                    <button type="button" class="cierre-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
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
                                    <span v-if="reclamoSeleccionado.cerrado == 1" class="badge reclamo-estado reclamo-estado--cerrado">Cerrado</span>
                                    <span v-else>{{ reclamoSeleccionado.municipalidad_estado || 'No especificado' }}</span>
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
                <div class="cierre-modal__footer cierre-modal__footer--end">
                    <button type="button" class="cierre-btn cierre-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
