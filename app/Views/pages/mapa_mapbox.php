<div id="app" class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>Mapa de Reclamos - Mapbox</div>
        <div class="d-flex gap-2">
            <!-- Filtro por estado 
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-funnel"></i> Filtrar por Estado
                </button>
                <div class="dropdown-menu" style="min-width: 200px; font-size: 0.6em; padding: 10px;">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="filtroTodos" @change="toggleTodosEstados" :checked="estadosSeleccionados.length === 0">
                        <label class="form-check-label" for="filtroTodos">
                            <i class="bi bi-eye"></i> Mostrar Todos
                        </label>
                    </div>
                    <hr class="dropdown-divider">
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="filtroRecibido" value="Recibido" @change="toggleEstado" :checked="estadosSeleccionados.includes('Recibido')">
                        <label class="form-check-label" for="filtroRecibido">
                            🔵 Recibido
                        </label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="filtroAsignado" value="Asignado" @change="toggleEstado" :checked="estadosSeleccionados.includes('Asignado')">
                        <label class="form-check-label" for="filtroAsignado">
                            🔴 Asignado
                        </label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="filtroEnEjecucion" value="En ejecución" @change="toggleEstado" :checked="estadosSeleccionados.includes('En ejecución')">
                        <label class="form-check-label" for="filtroEnEjecucion">
                            🟡 En ejecución
                        </label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="filtroCompletado" value="Completado" @change="toggleEstado" :checked="estadosSeleccionados.includes('Completado')">
                        <label class="form-check-label" for="filtroCompletado">
                            🟢 Completado
                        </label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="filtroEnPlan" value="En plan" @change="toggleEstado" :checked="estadosSeleccionados.includes('En plan')">
                        <label class="form-check-label" for="filtroEnPlan">
                            ⚫ En plan
                        </label>
                    </div>
                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" id="filtroErrorDatos" value="Error de datos" @change="toggleEstado" :checked="estadosSeleccionados.includes('Error de datos')">
                        <label class="form-check-label" for="filtroErrorDatos">
                            ⚫ Error de datos
                        </label>
                    </div>
                </div>
            </div-->
            <a href="<?= base_url('/mapa_google'); ?>" class="btn btn-success">
                <i class="bi bi-geo-alt-fill text-white"></i> Cambiar a Google Maps
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Mapa a la izquierda -->
        <div class="col-lg-8">
            <div id="map" style="width: 100%; height: calc(100vh - 200px); min-height: 500px;"></div>
        </div>
        
        <!-- Tabla de reclamos a la derecha -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                        <table id="tabla_reclamos_mapa" class="table table-bordered table-hover table-sm mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>ID</th>
                                    <th>Domicilio</th>
                                    <th>Número</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Contenido de la tabla gestionado por DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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

    <!-- Modal Estado de Ubicación -->
    <div class="modal fade" id="modalEstadoUbicacion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Estado de Ubicación del Reclamo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="fw-bold">Reclamo:</label>
                        <p>{{ reclamoParaReubicar.municipalidad_id }} - {{ reclamoParaReubicar.municipalidad_motivo }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Dirección:</label>
                        <p>{{ reclamoParaReubicar.municipalidad_domicilio }} {{ reclamoParaReubicar.municipalidad_numeroDomicilio }}</p>
                    </div>
                    
                    <!-- Estado de ubicación -->
                    <div class="mb-3">
                        <label class="fw-bold">Estado de Ubicación:</label>
                        <div v-if="ubicacionPersonalizada">
                            <div class="alert alert-info">
                                <i class="bi bi-geo-alt-fill text-primary"></i>
                                <strong>Ubicación Personalizada</strong>
                            </div>
                            <div class="mb-2">
                                <label class="fw-bold">Coordenadas personalizadas:</label>
                                <p class="mb-1">Latitud: {{ ubicacionPersonalizada.latitud }}</p>
                                <p class="mb-1">Longitud: {{ ubicacionPersonalizada.longitud }}</p>
                            </div>
                        </div>
                        <div v-else>
                            <div class="alert alert-warning">
                                <i class="bi bi-geo-alt text-warning"></i>
                                <strong>Ubicación por Defecto (Mapbox)</strong>
                            </div>
                            <p class="mb-0">El punto se ubica automáticamente usando las coordenadas obtenidas de Mapbox.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button v-if="ubicacionPersonalizada" type="button" class="btn btn-danger" @click="eliminarUbicacionPersonalizada">
                        <i class="bi bi-trash text-white"></i> Eliminar Ubicación Personalizada
                    </button>
                    <button v-if="!ubicacionPersonalizada" type="button" class="btn btn-primary" @click="iniciarReubicacionDesdeModal">
                        <i class="bi bi-geo-alt text-white"></i> Reubicar Punto
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

</div>
