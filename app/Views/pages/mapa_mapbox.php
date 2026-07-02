<script>
    document.body.classList.add('pagina-mapa-reclamos');
</script>

<div id="app" class="container-fluid">
    <div class="mapa-reclamos-contenedor">
        <div class="mapa-cambiar-proveedor-wrap">
            <a href="<?= base_url('/mapa_google'); ?>" class="reclamos-btn reclamos-btn--sm mapa-cambiar-proveedor-btn">
                <i class="bi bi-geo-alt-fill"></i>
                <span class="d-none d-md-inline">Google Maps</span>
            </a>
        </div>
        <div class="mapa-reclamos-toolbar">
            <button type="button"
                    class="reclamos-btn reclamos-btn--outline reclamos-btn--sm mapa-lista-toggle"
                    @click.stop="mostrarListaReclamosMapa = !mostrarListaReclamosMapa"
                    title="Mostrar u ocultar reclamos visibles">
                <i class="bi bi-list-ul"></i>
                <span class="d-none d-md-inline">Reclamos</span>
            </button>
            <?php if (in_array((string)($userRole ?? ''), ['1', '2', '3'], true)): ?>
            <button type="button"
                    class="reclamos-btn reclamos-btn--outline reclamos-btn--sm mapa-exportar-imagen-btn"
                    @click.stop="exportarMapaImagen"
                    :disabled="exportandoMapa"
                    title="Exportar vista del mapa como imagen">
                <i class="bi bi-download"></i>
                <span class="d-none d-md-inline">Exportar</span>
            </button>
            <?php endif; ?>
        </div>
        <div class="mapa-filtros-inferior-panel">
            <div class="mapa-filtro-prioridades-panel">
                <div class="form-check mapa-filtro-prioridad mapa-filtro-prioridad--alta">
                    <input class="form-check-input" type="checkbox" id="filtroPrioridadAlta" value="Alta" @change="togglePrioridad" :checked="prioridadesSeleccionadas.includes('Alta')">
                    <label class="form-check-label" for="filtroPrioridadAlta" title="Prioridad alta">Alta</label>
                </div>
                <div class="form-check mapa-filtro-prioridad mapa-filtro-prioridad--baja">
                    <input class="form-check-input" type="checkbox" id="filtroPrioridadBaja" value="Baja" @change="togglePrioridad" :checked="prioridadesSeleccionadas.includes('Baja')">
                    <label class="form-check-label" for="filtroPrioridadBaja" title="Prioridad baja">Baja</label>
                </div>
            </div>
            <div class="mapa-filtro-estados-panel">
            <div class="form-check mapa-filtro-estado mapa-filtro-estado--recibido">
                <input class="form-check-input" type="checkbox" id="filtroRecibido" value="Recibido" @change="toggleEstado" :checked="estadosSeleccionados.includes('Recibido')">
                <label class="form-check-label" for="filtroRecibido">Recibido</label>
            </div>
            <div class="form-check mapa-filtro-estado mapa-filtro-estado--asignado">
                <input class="form-check-input" type="checkbox" id="filtroAsignado" value="Asignado" @change="toggleEstado" :checked="estadosSeleccionados.includes('Asignado')">
                <label class="form-check-label" for="filtroAsignado">Asignado</label>
            </div>
            <div class="form-check mapa-filtro-estado mapa-filtro-estado--pendiente">
                <input class="form-check-input" type="checkbox" id="filtroPendiente" value="Pendiente" @change="toggleEstado" :checked="estadosSeleccionados.includes('Pendiente')">
                <label class="form-check-label" for="filtroPendiente">Pendiente</label>
            </div>
            <div class="form-check mapa-filtro-estado mapa-filtro-estado--en-ejecucion">
                <input class="form-check-input" type="checkbox" id="filtroEnEjecucion" value="En ejecución" @change="toggleEstado" :checked="estadosSeleccionados.includes('En ejecución')">
                <label class="form-check-label" for="filtroEnEjecucion">En ejecución</label>
            </div>
            <div class="form-check mapa-filtro-estado mapa-filtro-estado--completado">
                <input class="form-check-input" type="checkbox" id="filtroCompletado" value="Completado" @change="toggleEstado" :checked="estadosSeleccionados.includes('Completado')">
                <label class="form-check-label" for="filtroCompletado">Completado</label>
            </div>
            </div>
        </div>
        <div v-show="mostrarListaReclamosMapa" class="mapa-reclamos-lista-overlay">
            <div class="mapa-reclamos-lista-header">
                <span>Reclamos visibles</span>
                <span class="mapa-lista-badge">{{ reclamosVisiblesMapa.length }}</span>
                <button type="button" class="mapa-modal__close" @click="mostrarListaReclamosMapa = false" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
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
                    <span class="mapa-reclamos-lista-icon-wrap">
                        <span class="mapa-reclamos-lista-icon" :style="{ backgroundColor: colorEstadoReclamo(reclamo.municipalidad_estado) }">
                            {{ iconoMotivoReclamo(reclamo.municipalidad_motivo) }}
                        </span>
                        <span
                            v-if="reclamo.prioridad === 'Alta'"
                            class="mapa-prioridad-alta-badge mapa-reclamos-lista-prioridad-badge"
                            aria-label="Prioridad alta"
                        >!</span>
                    </span>
                    <span class="mapa-reclamos-lista-text">
                        <strong>#{{ reclamo.municipalidad_id }}</strong>
                        <small>{{ reclamo.municipalidad_domicilio || 'Sin domicilio' }} {{ reclamo.municipalidad_numeroDomicilio || '' }}</small>
                    </span>
                </button>
                <p v-if="reclamosVisiblesMapa.length === 0" class="mapa-reclamos-lista-empty">
                    No hay reclamos visibles con los filtros actuales.
                </p>
            </div>
        </div>
        <div id="map"></div>
    </div>

    <!-- Modal Ver Detalles Reclamo -->
    <div class="modal fade" id="modalVerReclamo" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content mapa-modal reclamo-modal">
                <div class="reclamo-modal__header">
                    <div class="reclamo-modal__title">
                        <span class="reclamo-modal__icon"><i class="bi bi-card-text"></i></span>
                        <h5>Detalles del reclamo</h5>
                    </div>
                    <button type="button" class="reclamo-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
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
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold">Recepción:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_recepcion || 'No especificado' }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Estado:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_estado || 'No especificado' }}</p>
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
                <div class="reclamo-modal__footer reclamo-modal__footer--end">
                    <button type="button" class="reclamos-btn reclamos-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Estado de Ubicación -->
    <div class="modal fade" id="modalEstadoUbicacion" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content mapa-modal">
                <div class="mapa-modal__header">
                    <div class="mapa-modal__title">
                        <span class="mapa-modal__icon"><i class="bi bi-geo-alt-fill"></i></span>
                        <h5>Estado de ubicación</h5>
                    </div>
                    <button type="button" class="mapa-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
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
                    <div class="mb-3">
                        <label class="fw-bold">Estado de ubicación:</label>
                        <div v-if="ubicacionPersonalizada">
                            <div class="mapa-modal-alert mapa-modal-alert--info">
                                <i class="bi bi-geo-alt-fill"></i>
                                <div>
                                    <strong>Ubicación personalizada</strong>
                                </div>
                            </div>
                            <div class="mapa-modal-coords">
                                <label class="fw-bold">Coordenadas personalizadas:</label>
                                <p>Latitud: {{ ubicacionPersonalizada.latitud }}</p>
                                <p>Longitud: {{ ubicacionPersonalizada.longitud }}</p>
                            </div>
                        </div>
                        <div v-else>
                            <div class="mapa-modal-alert mapa-modal-alert--default">
                                <i class="bi bi-geo-alt"></i>
                                <div>
                                    <strong>Ubicación por defecto (Mapbox)</strong>
                                </div>
                            </div>
                            <p class="mb-0" style="font-size: 0.82rem; font-weight: 500; color: var(--color-medium-gray);">El punto se ubica automáticamente usando las coordenadas obtenidas de Mapbox.</p>
                        </div>
                    </div>
                </div>
                <div class="mapa-modal__footer mapa-modal__footer--between">
                    <div>
                        <button v-if="ubicacionPersonalizada" type="button" class="ce-btn-eliminar" @click="eliminarUbicacionPersonalizada">
                            <i class="bi bi-trash"></i> Eliminar ubicación
                        </button>
                    </div>
                    <div class="d-flex gap-2 flex-wrap justify-content-end">
                        <button v-if="!ubicacionPersonalizada" type="button" class="reclamos-btn" @click="iniciarReubicacionDesdeModal">
                            <i class="bi bi-geo-alt"></i> Reubicar punto
                        </button>
                        <button type="button" class="reclamos-btn reclamos-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
