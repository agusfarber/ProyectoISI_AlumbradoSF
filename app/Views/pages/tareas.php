<div id="app" class="container-fluid">
    <div>Gestión de Tareas</div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <p class="text-muted mb-0" v-if="esOperario">Reclamos de mi cuadrilla</p>
            <p class="text-muted mb-0" v-else>Todos los reclamos</p>
        </div>
        <div>
            <button class="btn btn-primary" @click="verMapaRutas" v-if="esOperario && rutas.length > 0">
                <i class="bi bi-map text-white"></i> Ver Mapa de Rutas
            </button>
        </div>
    </div>

    <!-- Información de las rutas asignadas (solo para operarios) -->
    <div v-if="esOperario && rutas.length > 0" class="alert alert-info mb-3">
        <div class="d-flex align-items-center">
            <i class="bi bi-info-circle me-2" style="font-size: 1.5rem;"></i>
            <div>
                <strong>{{ rutas.length }} ruta(s) asignada(s) a mi cuadrilla</strong>
                <br>
                <small>{{ totalReclamos }} reclamo(s) en total | {{ reclamosCompletados }} completado(s) | {{ reclamosPendientes }} pendiente(s)</small>
            </div>
        </div>
    </div>

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
                <!-- Indicador de ruta (solo para operarios) -->
                <div v-if="esOperario && reclamo.ruta_nombre" class="card-header py-1 px-2" :style="`background-color: ${reclamo.ruta_color || '#808080'}; color: white;`">
                    <small class="d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-map"></i> {{ reclamo.ruta_nombre || 'Ruta' }}</span>
                        <span class="badge bg-light text-dark">#{{ reclamo.posicion }}</span>
                    </small>
                </div>
                
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

                    <div class="mb-1" v-if="reclamo.prioridad">
                        <small class="text-dark">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Prioridad: <strong>{{ reclamo.prioridad }}</strong>
                        </small>
                    </div>
                </div>
                
                <!-- Acciones rápidas -->
                <div class="card-footer bg-transparent">
                    <button class="btn btn-sm btn-primary w-100" @click.stop="cambiarEstado(reclamo)" title="Acciones">
                        <i class="bi bi-pencil-square text-white"></i> Acciones
                    </button>
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

    <!-- Modal Mapa de Rutas -->
    <div class="modal fade" id="modalMapaRutas" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-map"></i> Mis Rutas Asignadas
                        <span class="badge bg-primary ms-2">{{ rutas.length }} ruta(s)</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" @click="cerrarMapaRutas"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Panel de información a la izquierda -->
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-header py-2">
                                    <small class="mb-0"><strong>Rutas y Reclamos</strong></small>
                                </div>
                                <div class="list-group list-group-flush" style="height: 500px; overflow-y: auto;">
                                    <div v-for="ruta in rutas" :key="ruta.id" class="mb-2">
                                        <!-- Encabezado de ruta -->
                                        <div class="list-group-item py-2 px-3" :style="`background-color: ${ruta.color}20; border-left: 4px solid ${ruta.color};`">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div :style="`width: 16px; height: 16px; border-radius: 50%; background-color: ${ruta.color}; border: 2px solid #dee2e6;`"></div>
                                                    <strong>{{ ruta.nombre }}</strong>
                                                </div>
                                                <span class="badge bg-secondary">{{ ruta.cantidadReclamos }} reclamos</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Reclamos de esta ruta -->
                                        <div v-for="reclamo in obtenerReclamosPorRuta(ruta.id)" 
                                             :key="reclamo.id"
                                             class="list-group-item py-1 px-3"
                                             style="cursor: pointer;"
                                             @click="centrarEnReclamoMapa(reclamo)">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge" :class="reclamo.municipalidad_estado === 'Completado' ? 'bg-success' : 'bg-secondary'" style="font-size: 0.7rem;">
                                                    {{ reclamo.posicion }}
                                                </span>
                                                <div style="font-size: 0.85rem;">
                                                    <strong>{{ reclamo.municipalidad_id }}</strong>
                                                    <br>
                                                    <small class="text-muted" style="font-size: 0.75rem;">{{ reclamo.municipalidad_motivo }}</small>
                                                    <br>
                                                    <small :class="reclamo.municipalidad_estado === 'Completado' ? 'text-success' : 'text-warning'">
                                                        <i class="bi" :class="reclamo.municipalidad_estado === 'Completado' ? 'bi-check-circle' : 'bi-clock'"></i>
                                                        {{ reclamo.municipalidad_estado }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mapa a la derecha -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="bi bi-geo-alt"></i> Visualización de Rutas
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div id="mapaRutasOperario" style="width: 100%; height: 500px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="cerrarMapaRutas">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Acciones -->
    <div class="modal fade" id="modalAcciones" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Acciones - Reclamo #{{ reclamoSeleccionado.municipalidad_id }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Pestañas de navegación -->
                    <ul class="nav nav-tabs" id="accionesTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="cambiar-estado-tab" data-bs-toggle="tab" data-bs-target="#cambiar-estado" type="button" role="tab" aria-controls="cambiar-estado" aria-selected="true">
                                Cambiar Estado
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button" role="tab" aria-controls="historial" aria-selected="false" @click="cargarHistorial">
                                Historial
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Contenido de las pestañas -->
                    <div class="tab-content" id="accionesTabsContent">
                        <!-- Pestaña Cambiar Estado -->
                        <div class="tab-pane fade show active" id="cambiar-estado" role="tabpanel" aria-labelledby="cambiar-estado-tab">
                            <div class="mt-3">
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
                                        <!--option value="Recibido">Recibido</option>
                                        <option value="Asignado">Asignado</option-->
                                        <option value="En ejecución">En ejecución</option>
                                        <option value="Completado">Completado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pestaña Historial -->
                        <div class="tab-pane fade" id="historial" role="tabpanel" aria-labelledby="historial-tab">
                            <div class="mt-3">
                                <div v-if="cargandoHistorial" class="text-center py-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Cargando...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Cargando historial...</p>
                                </div>
                                
                                <div v-else-if="historialReclamo.length === 0" class="text-center py-4">
                                    <i class="bi bi-clock-history text-muted" style="font-size: 2rem;"></i>
                                    <p class="text-muted mt-2">No hay historial disponible para este reclamo.</p>
                                </div>
                                
                                <div v-else class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Estado Anterior</th>
                                                <th>Estado Actual</th>
                                                <th>Usuario</th>
                                                <th>Fecha de Cambio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in historialReclamo" :key="item.id">
                                                <td>
                                                    <span class="badge" :class="getEstadoBadgeClass(item.estado_anterior)">
                                                        {{ item.estado_anterior || 'N/A' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge" :class="getEstadoBadgeClass(item.estado_actual)">
                                                        {{ item.estado_actual }}
                                                    </span>
                                                </td>
                                                <td>{{ item.nombre_usuario || 'Sistema' }}</td>
                                                <td>{{ formatearFecha(item.fecha_cambio) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" @click="guardarCambioEstado" v-if="nuevoEstado">
                        <i class="bi bi-check-circle me-1 text-white"></i>Guardar Cambio de Estado
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
