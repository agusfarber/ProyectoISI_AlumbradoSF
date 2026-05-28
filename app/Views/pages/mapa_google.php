<script>
    document.body.classList.add('pagina-mapa-reclamos');
</script>

<div id="app" class="container-fluid">
    <div class="mapa-reclamos-contenedor">
                <div class="mapa-reclamos-toolbar">
                    <a href="<?= base_url('/mapa_mapbox'); ?>" class="btn btn-sm btn-success shadow-sm mapa-cambiar-proveedor-btn">
                        <i class="bi bi-geo-alt-fill text-white"></i>
                        <span class="d-none d-md-inline">Mapbox</span>
                    </a>
                    <button type="button"
                            class="btn btn-sm btn-light shadow-sm mapa-lista-toggle"
                            @click.stop="mostrarListaReclamosMapa = !mostrarListaReclamosMapa"
                            title="Mostrar u ocultar reclamos visibles">
                        <i class="bi bi-list-ul"></i>
                        <span class="d-none d-md-inline">Reclamos</span>
                    </button>
                    <?php if (in_array((string)($userRole ?? ''), ['1', '2', '3'], true)): ?>
                    <button type="button"
                            class="btn btn-sm btn-light shadow-sm mapa-exportar-imagen-btn"
                            @click.stop="exportarMapaImagen"
                            :disabled="exportandoMapa"
                            title="Exportar vista del mapa como imagen">
                        <i class="bi bi-download"></i>
                        <span class="d-none d-md-inline">Exportar</span>
                    </button>
                    <?php endif; ?>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-light shadow-sm dropdown-toggle mapa-filtro-prioridad-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                            <i class="bi bi-exclamation-triangle"></i> Filtrar por Prioridad
                        </button>
                        <div class="dropdown-menu dropdown-menu-end mapa-filtro-prioridad-menu p-2">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="filtroTodasPrioridades" @change="toggleTodasPrioridades" :checked="prioridadesSeleccionadas.length === 0">
                                <label class="form-check-label" for="filtroTodasPrioridades">
                                    <i class="bi bi-eye"></i> Mostrar todas
                                </label>
                            </div>
                            <hr class="dropdown-divider my-2">
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" id="filtroPrioridadAlta" value="Alta" @change="togglePrioridad" :checked="prioridadesSeleccionadas.includes('Alta')">
                                <label class="form-check-label" for="filtroPrioridadAlta">🔺 Alta</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="filtroPrioridadBaja" value="Baja" @change="togglePrioridad" :checked="prioridadesSeleccionadas.includes('Baja')">
                                <label class="form-check-label" for="filtroPrioridadBaja">🔻 Baja</label>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button type="button" class="btn btn-sm btn-light shadow-sm dropdown-toggle mapa-filtro-estados-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside">
                            <i class="bi bi-funnel"></i> Filtrar por Estado
                        </button>
                        <div class="dropdown-menu dropdown-menu-end mapa-filtro-estados-menu p-2">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="filtroTodos" @change="toggleTodosEstados" :checked="estadosSeleccionados.length === 0">
                                <label class="form-check-label" for="filtroTodos">
                                    <i class="bi bi-eye"></i> Mostrar todos
                                </label>
                            </div>
                            <hr class="dropdown-divider my-2">
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" id="filtroRecibido" value="Recibido" @change="toggleEstado" :checked="estadosSeleccionados.includes('Recibido')">
                                <label class="form-check-label" for="filtroRecibido">⚫ Recibido</label>
                            </div>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" id="filtroAsignado" value="Asignado" @change="toggleEstado" :checked="estadosSeleccionados.includes('Asignado')">
                                <label class="form-check-label" for="filtroAsignado">🔵 Asignado</label>
                            </div>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" id="filtroPendiente" value="Pendiente" @change="toggleEstado" :checked="estadosSeleccionados.includes('Pendiente')">
                                <label class="form-check-label" for="filtroPendiente">🔴 Pendiente</label>
                            </div>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" id="filtroEnEjecucion" value="En ejecución" @change="toggleEstado" :checked="estadosSeleccionados.includes('En ejecución')">
                                <label class="form-check-label" for="filtroEnEjecucion">🟡 En ejecución</label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="filtroCompletado" value="Completado" @change="toggleEstado" :checked="estadosSeleccionados.includes('Completado')">
                                <label class="form-check-label" for="filtroCompletado">🟢 Completado</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-show="mostrarListaReclamosMapa" class="mapa-reclamos-lista-overlay shadow">
                    <div class="mapa-reclamos-lista-header">
                        <strong>Reclamos visibles</strong>
                        <span class="badge bg-secondary">{{ reclamosVisiblesMapa.length }}</span>
                        <button type="button" class="btn-close btn-close-sm ms-auto" @click="mostrarListaReclamosMapa = false" aria-label="Cerrar"></button>
                    </div>
                    <div class="mapa-reclamos-lista-search">
                        <input
                            type="search"
                            class="form-control form-control-sm"
                            v-model="busquedaReclamosMapa"
                            placeholder="Buscar por ID, calle o motivo..."
                        >
                    </div>
                    <div class="mapa-reclamos-lista-body">
                        <button
                            v-for="reclamo in reclamosVisiblesMapa"
                            :key="reclamo.id"
                            type="button"
                            class="mapa-reclamos-lista-item"
                            @click="resaltarReclamoEnMapa(reclamo)"
                        >
                            <span class="mapa-reclamos-lista-icon" :style="{ backgroundColor: colorEstadoReclamo(reclamo.municipalidad_estado) }">
                                {{ iconoMotivoReclamo(reclamo.municipalidad_motivo) }}
                            </span>
                            <span class="mapa-reclamos-lista-text">
                                <strong>#{{ reclamo.municipalidad_id }}</strong>
                                <small>{{ reclamo.municipalidad_domicilio || 'Sin domicilio' }} {{ reclamo.municipalidad_numeroDomicilio || '' }}</small>
                            </span>
                        </button>
                        <p v-if="reclamosVisiblesMapa.length === 0" class="text-muted small text-center my-3">
                            No hay reclamos visibles con los filtros actuales.
                        </p>
                    </div>
                </div>
                <div id="map"></div>
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
                                <p>{{ reclamoSeleccionado.municipalidad_recepcion || 'No especificado' }}</p>
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
                                <strong>Ubicación por Defecto (Google Maps)</strong>
                            </div>
                            <p class="mb-0">El punto se ubica automáticamente usando las coordenadas obtenidas de Google Maps.</p>
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
