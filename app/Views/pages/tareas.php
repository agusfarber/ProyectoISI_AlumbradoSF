<div id="app" class="container-fluid">
    <div>Gestión de Tareas</div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <p class="text-muted mb-0" v-if="esOperario">Reclamos de mi cuadrilla</p>
            <p class="text-muted mb-0" v-else>Todos los reclamos</p>
        </div>
        <div>
            <button class="btn btn-success me-2" @click="abrirModalAñadirReclamos" v-if="esOperario && rutas.length > 0">
                <i class="bi bi-plus-circle text-white"></i> Añadir Reclamos
            </button>
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
                                <p>{{ reclamoSeleccionado.municipalidad_recepcion || 'No especificado' }}</p>
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
                        <div class="col-md-4 d-none d-md-block">
                            <div class="card h-100">
                                <div class="card-header py-2">
                                    <small class="mb-0"><strong>Rutas y Reclamos</strong></small>
                                </div>
                                <div class="list-group list-group-flush rutas-reclamos-scroll">
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
                                             class="list-group-item py-1 px-3 reclamo-lista-item"
                                             @click="centrarEnReclamoMapa(reclamo)">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge" 
                                                      :class="getEstadoBadgeClass(reclamo.municipalidad_estado)" 
                                                      style="font-size: 0.7rem;">
                                                    {{ reclamo.posicion }}
                                                </span>
                                                <div style="font-size: 0.85rem;">
                                                    <strong>{{ reclamo.municipalidad_id }}</strong>
                                                    <br>
                                                    <small class="text-muted" style="font-size: 0.75rem;">{{ reclamo.municipalidad_motivo }}</small>
                                                    <br>
                                                    <small :class="getEstadoTextClass(reclamo.municipalidad_estado)">
                                                        <i class="bi" :class="reclamo.municipalidad_estado === 'Completado' ? 'bi-check-circle' : 
                                                                           reclamo.municipalidad_estado === 'En ejecución' ? 'bi-clock' : 
                                                                           reclamo.municipalidad_estado === 'Asignado' ? 'bi-exclamation-triangle' : 'bi-clock'"></i>
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
                        <div class="col-12 col-md-8">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="bi bi-geo-alt"></i> Visualización de Rutas
                                    </h6>
                                    <button class="btn btn-sm btn-success" @click="alternarProveedorMapaRutas">
                                        <i class="bi bi-arrow-repeat text-white"></i> 
                                        {{ proveedorMapaRutas === 'google' ? 'Cambiar a Mapbox' : 'Cambiar a Google Maps' }}
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div id="mapaRutasOperario" v-show="proveedorMapaRutas === 'google'" style="width: 100%; height: 500px;"></div>
                                    <div id="mapaRutasOperarioMapbox" v-show="proveedorMapaRutas === 'mapbox'" style="width: 100%; height: 500px;"></div>
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
                            <button class="nav-link" id="materiales-tab" data-bs-toggle="tab" data-bs-target="#materiales" type="button" role="tab" aria-controls="materiales" aria-selected="false" @click="cargarMateriales">
                                Materiales
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tiempo-reparacion-tab" data-bs-toggle="tab" data-bs-target="#tiempo-reparacion" type="button" role="tab" aria-controls="tiempo-reparacion" aria-selected="false" @click="cargarTiempoReparacion">
                                Tiempo de Reparación
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
                                <div class="mb-3">
                                    <label for="observacionReclamo" class="form-label">Observaciones</label>
                                    <textarea id="observacionReclamo"
                                              class="form-control"
                                              v-model="nuevaObservacion"
                                              rows="3"
                                              maxlength="500"
                                              placeholder="Detalle las tareas realizadas, materiales utilizados u otra información relevante."></textarea>
                                    
                                </div>
                                
                                <!-- Botón para guardar -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-primary w-100" @click="guardarCambioEstado" :disabled="!puedeGuardarAccion">
                                        <i class="bi bi-check-circle me-1 text-white"></i>Guardar
                                    </button>
                                </div>
                                
                                <!-- Botón para ver historial -->
                                <div class="mb-3">
                                    <button class="btn btn-outline-primary w-100" @click="toggleHistorialEstado">
                                        <i class="bi" :class="mostrarHistorialEstado ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                        {{ mostrarHistorialEstado ? 'Ocultar' : 'Ver' }} Historial de Cambios de Estado
                                    </button>
                                </div>
                                
                                <!-- Tabla de historial de cambios de estado -->
                                <div v-if="mostrarHistorialEstado" class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Cambios de Estado</h6>
                                    </div>
                                    <div class="card-body">
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
                                                        <th>Observación</th>
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
                                                        <td>
                                                            <span v-if="item.observacion" class="observacion-texto">{{ item.observacion }}</span>
                                                            <span v-else class="text-muted">Sin observaciones</span>
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
                        
                        <!-- Pestaña Materiales -->
                        <div class="tab-pane fade" id="materiales" role="tabpanel" aria-labelledby="materiales-tab">
                            <div class="mt-3">
                                <!-- Formulario para registrar material -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-box-seam"></i> Registrar Material Utilizado</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Modo: Material Existente -->
                                        <div v-if="!modoMaterialNuevo">
                                            <!-- Fila con los tres selects/inputs -->
                                            <div class="row g-2 mb-3">
                                                <div class="col-md-4">
                                                    <label for="tipoMaterialSelect" class="form-label">Tipo de Material</label>
                                                    <select id="tipoMaterialSelect" class="form-select" v-model="materialSeleccionado.tipo_id" @change="filtrarMaterialesPorTipo">
                                                        <option value="">Todos los tipos</option>
                                                        <option v-for="tipo in tiposMaterial" :key="tipo.id" :value="tipo.id">
                                                            {{ tipo.nombre }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="materialSelect" class="form-label">Material</label>
                                                    <select id="materialSelect" class="form-select" v-model="materialSeleccionado.material_id" :disabled="materialesFiltrados.length === 0">
                                                        <option value="">Seleccionar material</option>
                                                        <option v-for="material in materialesFiltrados" :key="material.id" :value="material.id">
                                                            {{ material.nombre }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="cantidadMaterial" class="form-label">Cantidad</label>
                                                    <input type="number" id="cantidadMaterial" class="form-control" v-model.number="materialSeleccionado.cantidad" min="0" placeholder="Cantidad (opcional)">
                                                </div>
                                            </div>
                                            
                                            <!-- Campo de observación -->
                                            <div class="mb-3">
                                                <label for="observacionMaterial" class="form-label">Observación</label>
                                                <textarea id="observacionMaterial" class="form-control" v-model="materialSeleccionado.observacion" rows="3" placeholder="Observaciones sobre el material utilizado (opcional)"></textarea>
                                            </div>
                                            
                                            <!-- Botón para guardar -->
                                            <div class="mb-3">
                                                <button class="btn btn-success w-100" @click="guardarMaterialReclamo" :disabled="!puedeGuardarMaterial">
                                                    <i class="bi bi-check-circle me-1 text-white"></i> Guardar Material
                                                </button>
                                            </div>
                                            
                                            <!-- Botón para cambiar a modo crear material nuevo -->
                                            <div class="mb-0">
                                                <button class="btn btn-outline-secondary w-100" @click="alternarModoMaterial">
                                                    <i class="bi bi-plus-circle me-1"></i> El material no existe, crear uno nuevo
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Modo: Crear Material Nuevo -->
                                        <div v-else>
                                            <!-- Fila para crear material nuevo -->
                                            <div class="row g-2 mb-3">
                                                <div class="col-md-4">
                                                    <label for="nuevoTipoMaterialSelect" class="form-label">Tipo de Material <small class="text-muted">(opcional)</small></label>
                                                    <select id="nuevoTipoMaterialSelect" class="form-select" v-model="materialNuevo.tipo_id">
                                                        <option value="">Sin tipo</option>
                                                        <option v-for="tipo in tiposMaterial" :key="tipo.id" :value="tipo.id">
                                                            {{ tipo.nombre }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="nuevoNombreMaterial" class="form-label">Nombre del Material</label>
                                                    <input type="text" id="nuevoNombreMaterial" class="form-control" v-model="materialNuevo.nombre" placeholder="Nombre del material">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="nuevoCantidadMaterial" class="form-label">Cantidad</label>
                                                    <input type="number" id="nuevoCantidadMaterial" class="form-control" v-model.number="materialNuevo.cantidad" min="0" placeholder="Cantidad">
                                                </div>
                                            </div>
                                            
                                            <!-- Campo de observación -->
                                            <div class="mb-3">
                                                <label for="observacionMaterialNuevo" class="form-label">Observación</label>
                                                <textarea id="observacionMaterialNuevo" class="form-control" v-model="materialSeleccionado.observacion" rows="3" placeholder="Observaciones sobre el material utilizado (opcional)"></textarea>
                                            </div>
                                            
                                            <!-- Botón para guardar -->
                                            <div class="mb-3">
                                                <button class="btn btn-primary w-100" @click="guardarMaterialNuevoYReclamo" :disabled="!puedeGuardarMaterialNuevo">
                                                    <i class="bi bi-plus-circle me-1 text-white"></i> Crear y Guardar Material Nuevo
                                                </button>
                                            </div>
                                            
                                            <!-- Botón para cambiar a modo material existente -->
                                            <div class="mb-0">
                                                <button class="btn btn-outline-secondary w-100" @click="alternarModoMaterial">
                                                    <i class="bi bi-arrow-left me-1"></i> Seleccionar material existente
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Botón para ver historial -->
                                <div class="mb-3">
                                    <button class="btn btn-outline-primary w-100" @click="toggleHistorialMateriales">
                                        <i class="bi" :class="mostrarHistorialMateriales ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                        {{ mostrarHistorialMateriales ? 'Ocultar' : 'Ver' }} Historial de Materiales
                                    </button>
                                </div>
                                
                                <!-- Tabla de historial de materiales -->
                                <div v-if="mostrarHistorialMateriales" class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Materiales</h6>
                                    </div>
                                    <div class="card-body">
                                        <div v-if="cargandoMateriales" class="text-center py-3">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Cargando...</span>
                                            </div>
                                            <p class="mt-2 text-muted">Cargando materiales...</p>
                                        </div>
                                        
                                        <div v-else-if="historialMateriales.length === 0" class="text-center py-4">
                                            <i class="bi bi-box text-muted" style="font-size: 2rem;"></i>
                                            <p class="text-muted mt-2">No hay materiales registrados para este reclamo.</p>
                                        </div>
                                        
                                        <div v-else class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Material</th>
                                                        <th>Cantidad</th>
                                                        <th>Fecha</th>
                                                        <th>Usuario</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="item in historialMateriales" :key="item.id">
                                                        <td>
                                                            <a href="#" class="text-primary text-decoration-none" @click.prevent="verDetalleMaterial(item.id)">
                                                                <i class="bi bi-info-circle me-1"></i>{{ item.material_nombre || 'N/A' }}
                                                            </a>
                                                        </td>
                                                        <td>{{ item.cantidad || 'No especificada' }}</td>
                                                        <td>{{ formatearFecha(item.fecha) }}</td>
                                                        <td>{{ item.usuario_nombre || 'Sistema' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pestaña Tiempo de Reparación -->
                        <div class="tab-pane fade" id="tiempo-reparacion" role="tabpanel" aria-labelledby="tiempo-reparacion-tab">
                            <div class="mt-3">
                                <!-- Formulario para registrar tiempo de reparación -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-clock"></i> Registrar Tiempo de Reparación</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="tiempoReparacion" class="form-label">Tiempo de Reparación</label>
                                            <div class="row g-2">
                                                <div class="col-8">
                                                    <input type="number" 
                                                           id="tiempoReparacion" 
                                                           class="form-control" 
                                                           v-model.number="tiempoReparacion.valor" 
                                                           min="0" 
                                                           step="0.5"
                                                           placeholder="Ingrese el tiempo de reparación">
                                                </div>
                                                <div class="col-4">
                                                    <select class="form-select" v-model="tiempoReparacion.unidad">
                                                        <option value="minutos">Minutos</option>
                                                        <option value="horas">Horas</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <small class="text-muted">Este tiempo ayudará a mejorar las estimaciones futuras para este tipo de reclamo</small>
                                        </div>
                                        
                                        <!-- Botón para guardar tiempo -->
                                        <div class="mb-3">
                                            <button class="btn btn-primary w-100" @click="guardarTiempoReparacion" :disabled="!puedeGuardarTiempoReparacion">
                                                <i class="bi bi-check-circle me-1 text-white"></i> Guardar Tiempo de Reparación
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tiempo registrado actualmente -->
                                <div v-if="cargandoTiempoReparacion" class="card">
                                    <div class="card-body text-center py-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Cargando...</span>
                                        </div>
                                        <p class="mt-2 text-muted mb-0">Cargando tiempo de reparación...</p>
                                    </div>
                                </div>
                                
                                <div class="card" v-else-if="tiempoReparacionRegistrado">
                                    <div class="card-header">
                                        <h6 class="mb-0"><i class="bi bi-info-circle"></i> Tiempo Registrado</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info mb-0">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-clock-history me-2" style="font-size: 1.5rem;"></i>
                                                <div>
                                                    <strong>Tiempo registrado:</strong> 
                                                    <span class="fs-5 ms-2">
                                                        {{ formatearTiempo(tiempoReparacionRegistrado.tiempo_minutos) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <small>
                                                    <i class="bi bi-calendar me-1"></i>
                                                    Registrado el {{ formatearFecha(tiempoReparacionRegistrado.fecha_registro) }}
                                                    <span v-if="tiempoReparacionRegistrado.usuario_nombre">
                                                        por {{ tiempoReparacionRegistrado.usuario_nombre }}
                                                    </span>
                                                </small>
                                            </div>
                                        </div>
                                        <p class="text-muted small mt-2 mb-0">
                                            Si ingresa un nuevo tiempo, se actualizará este registro.
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Mensaje cuando no hay tiempo registrado -->
                                <div class="card" v-else>
                                    <div class="card-body text-center py-4">
                                        <i class="bi bi-clock text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-2 mb-0">No hay tiempo de reparación registrado para este reclamo.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Añadir Reclamos a Hoja de Ruta -->
    <div class="modal fade" id="modalAñadirReclamos" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle"></i> Añadir Reclamos a Mi Hoja de Ruta
                        <span class="badge bg-success ms-2">{{ reclamosRecibidosFiltrados.length }} reclamo(s) recibido(s)</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" @click="cerrarModalAñadirReclamos"></button>
                </div>
                <div class="modal-body">
                    <!-- Filtro de búsqueda -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       placeholder="Buscar por ID, motivo, domicilio o prioridad..." 
                                       v-model="filtroBusquedaReclamos"
                                       @input="filtrarReclamosRecibidos">
                            </div>
                        </div>
                    </div>

                    <!-- Lista de reclamos recibidos -->
                    <div class="row" v-if="reclamosRecibidosFiltrados.length > 0">
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 col-xl-2 mb-2" 
                             v-for="reclamo in reclamosRecibidosFiltrados" 
                             :key="reclamo.id">
                            <div class="card h-100 reclamo-card border-secondary" 
                                 @click="verDetallesReclamoRecibido(reclamo)">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="card-title mb-0 text-primary fw-bold">
                                            {{ reclamo.municipalidad_id }}
                                        </h6>
                                        <span class="badge bg-secondary">
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
                                
                                <!-- Botón para añadir a la ruta -->
                                <div class="card-footer bg-transparent">
                                    <button class="btn btn-sm btn-success w-100" 
                                            @click.stop="añadirReclamoARuta(reclamo)" 
                                            :disabled="añadiendoReclamo === reclamo.id"
                                            title="Añadir a mi hoja de ruta">
                                        <span v-if="añadiendoReclamo === reclamo.id" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                        <i v-else class="bi bi-plus-circle text-white"></i> 
                                        {{ añadiendoReclamo === reclamo.id ? 'Añadiendo...' : 'Añadir' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensaje cuando no hay reclamos -->
                    <div v-else class="text-center py-5">
                        <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
                        <h4 class="text-muted mt-3">No hay reclamos recibidos</h4>
                        <p class="text-muted" v-if="filtroBusquedaReclamos">
                            No se encontraron reclamos que coincidan con la búsqueda "{{ filtroBusquedaReclamos }}".
                        </p>
                        <p class="text-muted" v-else>
                            No hay reclamos con estado "Recibido" disponibles para añadir a la hoja de ruta.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalles Reclamo Recibido (dentro del modal de añadir) -->
    <div class="modal fade" id="modalDetallesReclamoRecibido" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Reclamo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" @click="cerrarModalDetallesReclamoRecibido"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">ID Municipalidad:</label>
                                <p>{{ reclamoRecibidoSeleccionado.municipalidad_id }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Tipo:</label>
                                <p>{{ reclamoRecibidoSeleccionado.municipalidad_tipo }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Motivo:</label>
                                <p>{{ reclamoRecibidoSeleccionado.municipalidad_motivo }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Fecha de Inicio:</label>
                                <p>{{ formatearFecha(reclamoRecibidoSeleccionado.municipalidad_fechaInicio) }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Fecha de Modificación:</label>
                                <p>{{ formatearFecha(reclamoRecibidoSeleccionado.municipalidad_fechaModificacion) }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Recepción:</label>
                                <p>{{ reclamoRecibidoSeleccionado.municipalidad_recepcion || 'No especificado' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Estado:</label>
                                <p>{{ reclamoRecibidoSeleccionado.municipalidad_estado }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Prioridad:</label>
                                <p>{{ reclamoRecibidoSeleccionado.prioridad || 'No especificado' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Teléfono:</label>
                                <p>{{ reclamoRecibidoSeleccionado.municipalidad_telefono || 'No especificado' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Domicilio:</label>
                                <p>{{ reclamoRecibidoSeleccionado.municipalidad_domicilio || 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Número Domicilio:</label>
                                <p>{{ reclamoRecibidoSeleccionado.municipalidad_numeroDomicilio || 'No especificado' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Entre Calle Uno:</label>
                                <p>{{ reclamoRecibidoSeleccionado.municipalidad_entreCalleUno || 'No especificado' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Entre Calle Dos:</label>
                                <p>{{ reclamoRecibidoSeleccionado.municipalidad_entreCalleDos || 'No especificado' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Ciudadano:</label>
                                <p>{{ reclamoRecibidoSeleccionado.municipalidad_ciudadano || 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Descripción:</label>
                        <p>{{ reclamoRecibidoSeleccionado.municipalidad_descripcion || 'No especificado' }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" @click="añadirReclamoARuta(reclamoRecibidoSeleccionado)" :disabled="añadiendoReclamo === reclamoRecibidoSeleccionado.id">
                        <span v-if="añadiendoReclamo === reclamoRecibidoSeleccionado.id" class="spinner-border spinner-border-sm me-1" role="status"></span>
                        <i v-else class="bi bi-plus-circle text-white"></i> 
                        {{ añadiendoReclamo === reclamoRecibidoSeleccionado.id ? 'Añadiendo...' : 'Añadir a Mi Hoja de Ruta' }}
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="cerrarModalDetallesReclamoRecibido">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Material Reclamo -->
    <div class="modal fade" id="modalDetalleMaterial" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle del Material Utilizado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div v-if="cargandoDetalleMaterial" class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando detalle...</p>
                    </div>
                    <div v-else-if="detalleMaterial">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="fw-bold">Reclamo:</label>
                                    <p>{{ detalleMaterial.reclamo_municipalidad_id || 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Material:</label>
                                    <p>{{ detalleMaterial.material_nombre || 'N/A' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Tipo de Material:</label>
                                    <p>{{ detalleMaterial.tipo_material_nombre || 'No especificado' }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Cantidad Utilizada:</label>
                                    <p>{{ detalleMaterial.cantidad || 'No especificada' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!--div class="mb-3">
                                    <label class="fw-bold">Stock Disponible:</label>
                                    <p>{{ detalleMaterial.material_cantidad_stock || 'N/A' }}</p>
                                </div-->
                                <div class="mb-3">
                                    <label class="fw-bold">Fecha de Registro:</label>
                                    <p>{{ formatearFecha(detalleMaterial.fecha) }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Usuario:</label>
                                    <p>{{ detalleMaterial.usuario_nombre || 'Sistema' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold">Observación:</label>
                            <p v-if="detalleMaterial.observacion" class="border p-3 rounded bg-light">{{ detalleMaterial.observacion }}</p>
                            <p v-else class="text-muted">Sin observaciones</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
