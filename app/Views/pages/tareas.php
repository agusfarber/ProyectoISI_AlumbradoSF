<div id="app" class="tareas-page">
    <!-- Encabezado operario: detalle de hoja (compacto) -->
    <div v-if="vistaOperarioActual === 'detalle' && rutaSeleccionada" class="ruta-detalle-encabezado mb-3">
        <div class="ruta-detalle-fila-superior d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div
                class="ruta-detalle-titulo"
                :style="{
                    backgroundColor: rutaSeleccionada.color || '#808080',
                    color: textoSobreColorRuta(rutaSeleccionada.color)
                }"
            >
                {{ rutaSeleccionada.nombre || 'Hoja de ruta' }}
            </div>
            <div class="ruta-detalle-btns-navegacion d-flex flex-wrap gap-2 justify-content-end">
                <button type="button" class="tareas-btn tareas-btn--outline tareas-btn--sm" @click="volverAPanelRutas" title="Volver a Hojas" aria-label="Volver a Hojas">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i><span class="tareas-btn-label"> Volver a Hojas</span>
                </button>
                <button
                    type="button"
                    class="tareas-btn tareas-btn--success tareas-btn--sm"
                    @click="abrirModalAñadirReclamos"
                    v-if="rutas.length > 0 && puedeAñadirReclamosRutaSeleccionada"
                    title="Añadir paradas (solo con hoja en ejecución y permisos de gestión)"
                    aria-label="Añadir paradas"
                >
                    <i class="bi bi-plus-circle" aria-hidden="true"></i><span class="tareas-btn-label"> Añadir Reclamos</span>
                </button>
                <button
                    type="button"
                    class="tareas-btn tareas-btn--sm"
                    @click="cambiarModoVistaRuta('mapa')"
                    v-if="modoVistaRuta === 'lista'"
                    title="Ver mapa"
                    aria-label="Ver mapa"
                >
                    <i class="bi bi-map" aria-hidden="true"></i><span class="tareas-btn-label"> Ver mapa</span>
                </button>
                <button
                    type="button"
                    class="tareas-btn tareas-btn--sm"
                    @click="cambiarModoVistaRuta('lista')"
                    v-if="modoVistaRuta === 'mapa'"
                    title="Ver lista"
                    aria-label="Ver lista"
                >
                    <i class="bi bi-list-ul" aria-hidden="true"></i><span class="tareas-btn-label"> Ver lista</span>
                </button>
            </div>
        </div>
        <div class="ruta-detalle-fila-acciones d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <span class="ruta-detalle-pill">
                Cuadrilla: <strong>{{ rutaSeleccionada.cuadrilla_nombre || 'Sin asignar' }}</strong>
            </span>
            <div class="ruta-detalle-acciones-ejecucion d-inline-flex align-items-center flex-wrap gap-1 justify-content-end">
                <span class="badge" :class="claseBadgeEstadoEjecucionRuta(rutaSeleccionada)">
                    {{ textoEstadoEjecucionRuta(rutaSeleccionada) }}
                </span>
                <span
                    v-if="esEstadoEjecucionRuta(rutaSeleccionada)"
                    :class="claseCronometroEjecucionRuta(rutaSeleccionada)"
                ><svg class="cronometro-badge-ico cronometro-badge-ico-ruta" viewBox="0 0 20 12" aria-hidden="true" focusable="false"><path d="M1 9.5 H6 V2.5 H14 V9.5 H19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="cronometro-badge-txt">{{ tiempoTranscurridoEjecucion(rutaSeleccionada) }}</span></span>
                <button
                    type="button"
                    class="tareas-btn tareas-btn--success tareas-btn--sm text-nowrap"
                    @click="iniciarEjecucionRutaSeleccionada"
                    v-if="!rutaSeleccionadaEnEjecucion && puedeOperarRutaSeleccionada && idsCuadrillasComoJefe.includes(rutaSeleccionada.cuadrilla_id)"
                    title="Iniciar ejecución"
                    aria-label="Iniciar ejecución"
                >
                    <i class="bi bi-play-fill" aria-hidden="true"></i><span class="tareas-btn-label"> Iniciar ejecución</span>
                </button>
                <button
                    type="button"
                    class="tareas-btn tareas-btn--success tareas-btn--sm text-nowrap ruta-detalle-btn-finalizar"
                    @click="finalizarEjecucionRutaSeleccionada"
                    v-if="rutaSeleccionadaEnEjecucion && idsCuadrillasComoJefe.includes(rutaSeleccionada.cuadrilla_id)"
                    :disabled="!puedeFinalizarEjecucionRutaSeleccionada"
                    :title="!puedeFinalizarEjecucionRutaSeleccionada ? 'Hay reclamos con trabajo en curso. Marcá cada uno como Pendiente o Completado antes de finalizar la hoja.' : 'Finalizar ejecución'"
                    :aria-label="!puedeFinalizarEjecucionRutaSeleccionada ? 'Hay reclamos con trabajo en curso. Marcá cada uno como Pendiente o Completado antes de finalizar la hoja.' : 'Finalizar ejecución'"
                >
                    <i class="bi bi-stop-fill" aria-hidden="true"></i><span class="tareas-btn-label"> Finalizar ejecución</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Encabezado: panel operario / otros roles -->
    <div v-else class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <p class="tareas-section-subtitle mb-0" v-if="esOperario">Hojas de ruta</p>
            <p class="tareas-section-subtitle mb-0" v-else>Todos los reclamos</p>
        </div>
    </div>

    <!-- Panel de hojas de ruta en tarjetas (operario) -->
    <div v-if="esOperario && vistaOperarioActual === 'panel'" class="operario-rutas-panel mb-4">
        <p v-if="!rutasPanel.length" class="text-muted text-center py-5 mb-0">
            No tenés una hoja de ruta asignada a tu cuadrilla.
        </p>
        <div v-else class="supervisor-rutas-grid">
            <article
                v-for="ruta in rutasPanel"
                :key="ruta.id"
                class="supervisor-ruta-card"
                :class="{ 'supervisor-ruta-card--seleccionada': rutaSeleccionadaId === ruta.id }"
                @click="seleccionarRuta(ruta)"
            >
                <div
                    class="supervisor-ruta-card__nombre"
                    :style="{
                        backgroundColor: ruta.color || '#808080',
                        color: textoSobreColorRuta(ruta.color)
                    }"
                >
                    <span class="supervisor-ruta-card__nombre-texto">{{ ruta.nombre || 'Hoja de ruta' }}</span>
                    <span
                        class="supervisor-ruta-card__reclamos"
                        :title="(ruta.cantidadReclamos || 0) + ' reclamos'"
                        :aria-label="(ruta.cantidadReclamos || 0) + ' reclamos'"
                    >
                        <i class="bi bi-clipboard-data" aria-hidden="true"></i>
                        <span>{{ ruta.cantidadReclamos || 0 }}</span>
                    </span>
                </div>
                <div class="supervisor-ruta-card__mapa">
                    <div :id="'mapaPreviewOperarioRuta-' + ruta.id"></div>
                    <div v-if="!mapasPreviewOperario[ruta.id]" class="supervisor-ruta-card__mapa-placeholder">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Cargando mapa…
                    </div>
                </div>
                <div class="supervisor-ruta-card__meta">
                    <div
                        class="supervisor-ruta-card__cuadrilla-fila"
                        :title="tituloCuadrillaTarjetaRuta(ruta)"
                    >
                        <span class="supervisor-ruta-card__cuadrilla-izq">
                            <i class="bi bi-people-fill supervisor-ruta-card__cuadrilla-ico" aria-hidden="true"></i>
                            <span class="supervisor-ruta-card__cuadrilla-nombre">
                                {{ ruta.cuadrilla_nombre || 'Sin asignar' }}
                            </span>
                        </span>
                        <div
                            v-if="operariosCuadrillaDeRuta(ruta).length"
                            class="supervisor-ruta-card__cuadrilla-avatars"
                            aria-hidden="true"
                        >
                            <template v-for="op in operariosCuadrillaDeRuta(ruta)" :key="'ruta-card-op-' + ruta.id + '-' + op.id">
                                <img
                                    v-if="op.foto_perfil"
                                    class="crc-avatar crc-avatar--img"
                                    :class="{ 'crc-avatar--jefe': Number(op.es_jefe) === 1 }"
                                    :src="urlFotoOperario(op.foto_perfil)"
                                    :alt="op.nombre"
                                    loading="lazy"
                                >
                                <span
                                    v-else
                                    class="crc-avatar"
                                    :class="{ 'crc-avatar--jefe': Number(op.es_jefe) === 1 }"
                                    :style="{ backgroundColor: colorAvatarOperario(op.nombre) }"
                                >{{ inicialesOperario(op.nombre) }}</span>
                            </template>
                        </div>
                    </div>
                    <div class="supervisor-ruta-card__estado-inline">
                        <span class="badge" :class="claseBadgeEstadoEjecucionRuta(ruta)">
                            {{ textoEstadoEjecucionRuta(ruta) }}
                        </span>
                        <span
                            v-if="esEstadoEjecucionRuta(ruta)"
                            :class="claseCronometroEjecucionRuta(ruta)"
                        ><svg class="cronometro-badge-ico cronometro-badge-ico-ruta" viewBox="0 0 20 12" aria-hidden="true" focusable="false"><path d="M1 9.5 H6 V2.5 H14 V9.5 H19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="cronometro-badge-txt">{{ tiempoTranscurridoEjecucion(ruta) }}</span></span>
                    </div>
                </div>
            </article>
        </div>
    </div>

    <!-- Vista mapa para detalle de hoja de ruta (operario) -->
    <div v-if="esOperario && vistaOperarioActual === 'detalle' && modoVistaRuta === 'mapa'" class="card mb-4">
        <div class="card-body p-0 operario-ruta-detalle-mapa operario-ruta-detalle-vista-mapa">
            <div
                id="mapaRutaDetalleOperarioGoogle"
                v-show="proveedorMapaDetalleOperario === 'google'"
                class="operario-ruta-detalle-mapa__canvas"
            ></div>
            <div
                id="mapaRutaDetalleOperarioMapbox"
                v-show="proveedorMapaDetalleOperario === 'mapbox'"
                class="operario-ruta-detalle-mapa__canvas"
            ></div>
            <div class="crear-ruta-vista-controles">
                <button
                    type="button"
                    class="tareas-btn tareas-btn--sm tareas-btn--success"
                    @click="alternarProveedorMapaDetalleOperario"
                >
                    <i class="bi bi-arrow-repeat"></i>
                    {{ proveedorMapaDetalleOperario === 'google' ? 'Mapbox' : 'Google Maps' }}
                </button>
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

    <!-- Recorrido de ruta en lista vertical (operario) -->
    <div v-if="esOperario && vistaOperarioActual === 'detalle' && modoVistaRuta === 'lista' && paradasOrdenRuta.length > 0" class="ruta-secuencia-container supervisor-detalle-secuencia">
        <div v-for="(parada, idx) in paradasOrdenRuta" :key="'op-detalle-parada-' + parada.clave + '-' + idx" class="ruta-secuencia-item">
            <div
                class="card reclamo-card reclamo-card-secuencia"
                :class="[
                    getCardClass(reclamoActivoEnParadaOperario(parada)),
                    { 'reclamo-card-secuencia--en-obra': reclamoEnObraActiva(reclamoActivoEnParadaOperario(parada)) }
                ]"
                @click="verDetalles(reclamoActivoEnParadaOperario(parada))"
            >
                <div class="card-body ruta-secuencia-cardbody">
                    <div class="ruta-secuencia-fila">
                        <span class="ruta-secuencia-icon-wrap">
                            <button
                                v-if="parada.reclamos.length > 1"
                                type="button"
                                class="ruta-secuencia-grupo-badge"
                                :style="{ backgroundColor: getColorEstado(reclamoActivoEnParadaOperario(parada).municipalidad_estado) }"
                                :title="parada.reclamos.length + ' reclamos en este domicilio'"
                                @click.stop="navegarReclamoEnParadaOperario(parada, 1)"
                            >
                                {{ parada.reclamos.length }}
                            </button>
                            <span
                                v-else
                                class="ruta-secuencia-motivo-icon"
                                :style="{ backgroundColor: getColorEstado(parada.reclamos[0].municipalidad_estado) }"
                                :title="parada.reclamos[0].municipalidad_motivo || 'Motivo no especificado'"
                            >
                                {{ iconoMotivoReclamo(parada.reclamos[0].municipalidad_motivo) }}
                            </span>
                            <span
                                v-if="marcadorGrupoTienePrioridadAlta(parada.reclamos)"
                                class="mapa-prioridad-alta-badge ruta-secuencia-prioridad-badge"
                                aria-label="Prioridad alta"
                            >!</span>
                        </span>
                        <div class="ruta-secuencia-main">
                            <span class="ruta-secuencia-id">{{ reclamoActivoEnParadaOperario(parada).municipalidad_id }}</span>
                            <span class="ruta-secuencia-domicilio">
                                <span
                                    class="ruta-secuencia-calle"
                                    :title="(reclamoActivoEnParadaOperario(parada).municipalidad_domicilio || '') + ' ' + (reclamoActivoEnParadaOperario(parada).municipalidad_numeroDomicilio || '')"
                                >
                                    <i class="bi bi-geo-alt ruta-secuencia-calle-ico" aria-hidden="true"></i>
                                    {{ reclamoActivoEnParadaOperario(parada).municipalidad_domicilio }}
                                    {{ reclamoActivoEnParadaOperario(parada).municipalidad_numeroDomicilio }}
                                </span>
                                <span
                                    v-if="reclamoActivoEnParadaOperario(parada).municipalidad_descripcion"
                                    class="ruta-secuencia-descripcion"
                                    :title="reclamoActivoEnParadaOperario(parada).municipalidad_descripcion"
                                >
                                    {{ reclamoActivoEnParadaOperario(parada).municipalidad_descripcion }}
                                </span>
                                <div
                                    v-if="parada.reclamos.length > 1"
                                    class="ruta-secuencia-grupo-nav"
                                    @click.stop
                                >
                                    <button
                                        type="button"
                                        class="mapa-popup-nav mapa-popup-nav-prev"
                                        @click="navegarReclamoEnParadaOperario(parada, -1)"
                                        aria-label="Reclamo anterior"
                                    >
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <span class="ruta-secuencia-grupo-contador">
                                        {{ indiceReclamoEnParadaOperario(parada) + 1 }} de {{ parada.reclamos.length }}
                                    </span>
                                    <button
                                        type="button"
                                        class="mapa-popup-nav mapa-popup-nav-next"
                                        @click="navegarReclamoEnParadaOperario(parada, 1)"
                                        aria-label="Siguiente reclamo"
                                    >
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </span>
                        </div>
                        <div
                            v-if="esOperario && puedeOperarRutaSeleccionada && (rutaSeleccionadaEnEjecucion || puedeVerRegistrosObraReclamo(reclamoActivoEnParadaOperario(parada)))"
                            class="ruta-secuencia-toolbar"
                            @click.stop
                        >
                            <div
                                v-if="(puedeEditarTareasRutaSeleccionada && rutaSeleccionadaEnEjecucion && (puedeMostrarIniciarReparacionReclamo(reclamoActivoEnParadaOperario(parada)) || paradaTieneSesionReparacion(parada))) || (puedeVerRegistrosObraReclamo(reclamoActivoEnParadaOperario(parada)) && mostrarCronometroReparacionReclamo(reclamoActivoEnParadaOperario(parada)))"
                                class="ruta-secuencia-toolbar__inicio"
                            >
                                <template v-if="puedeEditarTareasRutaSeleccionada && rutaSeleccionadaEnEjecucion">
                                    <div
                                        v-if="puedeMostrarIniciarReparacionReclamo(reclamoActivoEnParadaOperario(parada))"
                                        class="reclamo-confirm-accion"
                                        @click.stop
                                    >
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-accion-estado"
                                            :class="{ 'is-open': estaConfirmandoAccionParada(parada, 'iniciar') }"
                                            title="Iniciar reclamo"
                                            aria-label="Iniciar reclamo"
                                            @click="pedirConfirmarAccionParada(parada, 'iniciar')"
                                        >
                                            <i class="bi bi-play-fill"></i><span class="ruta-secuencia-btn-iniciar-text"> Iniciar reclamo</span>
                                        </button>
                                        <div v-if="estaConfirmandoAccionParada(parada, 'iniciar')" class="reclamo-confirm-accion__pop" @click.stop>
                                            <span>{{ textoConfirmarAccionParada(parada, 'iniciar') }}</span>
                                            <button type="button" class="reclamo-confirm-accion__si" @click="confirmarAccionParadaElegida(parada)">Sí</button>
                                            <button type="button" class="reclamo-confirm-accion__no" @click="cancelarConfirmarAccionParada">No</button>
                                        </div>
                                    </div>
                                    <template v-else-if="paradaTieneSesionReparacion(parada)">
                                        <template v-if="paradaTieneObraActiva(parada)">
                                            <div class="reclamo-confirm-accion" @click.stop>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-accion-estado"
                                                    :class="{ 'is-open': estaConfirmandoAccionParada(parada, 'completado') }"
                                                    :title="parada.reclamos.length > 1 ? 'Marcar como completados todos los reclamos en obra en esta parada' : 'Marcar como completado'"
                                                    :aria-label="parada.reclamos.length > 1 ? 'Marcar como completados todos los reclamos en obra en esta parada' : 'Marcar como completado'"
                                                    @click="pedirConfirmarAccionParada(parada, 'completado')"
                                                >
                                                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                                                </button>
                                                <div v-if="estaConfirmandoAccionParada(parada, 'completado')" class="reclamo-confirm-accion__pop" @click.stop>
                                                    <span>{{ textoConfirmarAccionParada(parada, 'completado') }}</span>
                                                    <button type="button" class="reclamo-confirm-accion__si" @click="confirmarAccionParadaElegida(parada)">Sí</button>
                                                    <button type="button" class="reclamo-confirm-accion__no" @click="cancelarConfirmarAccionParada">No</button>
                                                </div>
                                            </div>
                                            <div class="reclamo-confirm-accion" @click.stop>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-accion-estado"
                                                    :class="{ 'is-open': estaConfirmandoAccionParada(parada, 'pendiente') }"
                                                    :title="parada.reclamos.length > 1 ? 'Pendiente para otro día (todos los reclamos en obra en esta parada)' : 'Pendiente para otro día'"
                                                    :aria-label="parada.reclamos.length > 1 ? 'Pendiente para otro día (todos los reclamos en obra en esta parada)' : 'Pendiente para otro día'"
                                                    @click="pedirConfirmarAccionParada(parada, 'pendiente')"
                                                >
                                                    <i class="bi bi-pause-circle" aria-hidden="true"></i>
                                                </button>
                                                <div v-if="estaConfirmandoAccionParada(parada, 'pendiente')" class="reclamo-confirm-accion__pop" @click.stop>
                                                    <span>{{ textoConfirmarAccionParada(parada, 'pendiente') }}</span>
                                                    <button type="button" class="reclamo-confirm-accion__si" @click="confirmarAccionParadaElegida(parada)">Sí</button>
                                                    <button type="button" class="reclamo-confirm-accion__no" @click="cancelarConfirmarAccionParada">No</button>
                                                </div>
                                            </div>
                                        </template>
                                        <div
                                            v-else-if="puedeMostrarContinuarParada(parada)"
                                            class="reclamo-confirm-accion"
                                            @click.stop
                                        >
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-accion-estado"
                                                :class="{ 'is-open': estaConfirmandoAccionParada(parada, 'continuar') }"
                                                :title="parada.reclamos.length > 1 ? 'Retomar el trabajo en todos los reclamos pendientes de esta parada' : 'Retomar el trabajo en obra con el tiempo ya acumulado'"
                                                aria-label="Continuar ejecución"
                                                @click="pedirConfirmarAccionParada(parada, 'continuar')"
                                            >
                                                <i class="bi bi-play-fill"></i><span class="ruta-secuencia-btn-continuar-text"> Continuar ejecución</span>
                                            </button>
                                            <div v-if="estaConfirmandoAccionParada(parada, 'continuar')" class="reclamo-confirm-accion__pop" @click.stop>
                                                <span>{{ textoConfirmarAccionParada(parada, 'continuar') }}</span>
                                                <button type="button" class="reclamo-confirm-accion__si" @click="confirmarAccionParadaElegida(parada)">Sí</button>
                                                <button type="button" class="reclamo-confirm-accion__no" @click="cancelarConfirmarAccionParada">No</button>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                                <span
                                    v-if="puedeVerRegistrosObraReclamo(reclamoActivoEnParadaOperario(parada)) && mostrarCronometroReparacionReclamo(reclamoActivoEnParadaOperario(parada))"
                                    class="ruta-secuencia-crono-reparacion badge font-monospace cronometro-badge-con-ico"
                                    :class="claseCronometroListaObraOperario(reclamoActivoEnParadaOperario(parada))"
                                    title="Tiempo en reparación"
                                ><i class="bi bi-truck cronometro-badge-ico" aria-hidden="true"></i><span class="cronometro-badge-txt">{{ textoCronometroReparacionReclamo(reclamoActivoEnParadaOperario(parada)) }}</span></span>
                            </div>
                            <div
                                v-if="puedeVerRegistrosObraReclamo(reclamoActivoEnParadaOperario(parada))"
                                class="ruta-secuencia-toolbar__paneles"
                            >
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary ruta-secuencia-btn-material btn-con-badge-obs"
                                    :title="cantidadMaterialesReclamoOperario(reclamoActivoEnParadaOperario(parada)) > 0
                                        ? 'Materiales utilizados (' + cantidadMaterialesReclamoOperario(reclamoActivoEnParadaOperario(parada)) + ')'
                                        : 'Materiales utilizados'"
                                    @click="abrirModalMaterialesReclamo(reclamoActivoEnParadaOperario(parada))"
                                >
                                    <i class="bi bi-box-seam"></i>
                                    <span
                                        class="btn-obs-ejecucion-count"
                                        :class="{ 'btn-obs-ejecucion-count--oculto': cantidadMaterialesReclamoOperario(reclamoActivoEnParadaOperario(parada)) < 1 }"
                                    >{{ textoObservacionesEjecucionBadge(cantidadMaterialesReclamoOperario(reclamoActivoEnParadaOperario(parada))) || '0' }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary ruta-secuencia-btn-obs-ejecucion btn-con-badge-obs"
                                    :title="cantidadObservacionesEjecucionReclamoOperario(reclamoActivoEnParadaOperario(parada)) > 0
                                        ? 'Registro en obra (' + cantidadObservacionesEjecucionReclamoOperario(reclamoActivoEnParadaOperario(parada)) + ')'
                                        : 'Registro en obra'"
                                    @click="abrirModalObservacionesEjecucionReclamo(reclamoActivoEnParadaOperario(parada))"
                                >
                                    <i class="bi bi-journal-text"></i>
                                    <span
                                        class="btn-obs-ejecucion-count"
                                        :class="{ 'btn-obs-ejecucion-count--oculto': cantidadObservacionesEjecucionReclamoOperario(reclamoActivoEnParadaOperario(parada)) < 1 }"
                                    >{{ textoObservacionesEjecucionBadge(cantidadObservacionesEjecucionReclamoOperario(reclamoActivoEnParadaOperario(parada))) || '0' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="idx < paradasOrdenRuta.length - 1" class="ruta-secuencia-flecha">
                <i class="bi bi-arrow-down"></i>
            </div>
        </div>
    </div>

    <!-- Mensajes de estado vacío -->
    <div v-if="esOperario && vistaOperarioActual === 'detalle' && modoVistaRuta === 'lista' && paradasOrdenRuta.length === 0" class="text-center py-5">
        <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
        <h4 class="text-muted mt-3">No hay reclamos disponibles</h4>
        <p class="text-muted">No se encontraron reclamos para la hoja de ruta seleccionada.</p>
    </div>
    <div v-if="esOperario && vistaOperarioActual === 'detalle' && modoVistaRuta === 'mapa' && reclamosOrdenRuta.length === 0" class="text-center py-5">
        <i class="bi bi-map text-muted" style="font-size: 3rem;"></i>
        <h4 class="text-muted mt-3">No hay puntos para mostrar en el mapa</h4>
        <p class="text-muted">La hoja seleccionada no tiene coordenadas disponibles.</p>
    </div>

    <!-- Modal Ver Detalles Reclamo -->
    <div class="modal fade" id="modalDetalles" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rutas-modal tareas-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-card-text"></i></span>
                        <h5>Detalles del Reclamo</h5>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
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
                <div class="tareas-modal__footer tareas-modal__footer--end">
                    <button type="button" class="tareas-btn tareas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Mapa de Rutas -->
    <div class="modal fade" id="modalMapaRutas" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rutas-modal tareas-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-map"></i></span>
                        <h5>
                            Mis Rutas Asignadas
                            <span class="badge bg-primary ms-2">{{ rutas.length }} ruta(s)</span>
                        </h5>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" @click="cerrarMapaRutas" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
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
                                                <span class="ruta-secuencia-motivo-icon" :title="reclamo.municipalidad_motivo || 'Motivo no especificado'">
                                                    {{ iconoMotivoReclamo(reclamo.municipalidad_motivo) }}
                                                </span>
                                                <div style="font-size: 0.85rem;">
                                                    <strong>{{ reclamo.municipalidad_id }}</strong>
                                                    <br>
                                                    <small class="text-muted" style="font-size: 0.75rem;">{{ reclamo.municipalidad_motivo }}</small>
                                                    <br>
                                                    <small :class="getEstadoTextClass(reclamo.municipalidad_estado)">
                                                        <i class="bi" :class="reclamo.municipalidad_estado === 'Completado' ? 'bi-check-circle' : 
                                                                           reclamo.municipalidad_estado === 'En ejecución' ? 'bi-clock' : 
                                                                           reclamo.municipalidad_estado === 'Pendiente' ? 'bi-pause-circle' :
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
                                    <button type="button" class="tareas-btn tareas-btn--sm tareas-btn--success" @click="alternarProveedorMapaRutas">
                                        <i class="bi bi-arrow-repeat"></i>
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
                <div class="tareas-modal__footer tareas-modal__footer--end">
                    <button type="button" class="tareas-btn tareas-btn--outline" data-bs-dismiss="modal" @click="cerrarMapaRutas">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal registro en obra (bitácora: notas y fotos) -->
    <div class="modal fade" id="modalObservacionesEjecucionReclamo" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered bitacora-obra-modal-dialog">
            <div class="modal-content rutas-modal tareas-modal bitacora-obra-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-journal-text"></i></span>
                        <h5>Registro en obra — Reclamo #{{ reclamoSeleccionado.municipalidad_id }}</h5>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body d-flex flex-column bitacora-obra-modal__body">
                    <div class="bitacora-obra-modal__feed" id="bitacoraObraFeedOperario">
                    <div v-if="cargandoObservacionesEjecucion" class="text-center py-3 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2">Cargando…</span>
                    </div>
                    <div v-else-if="!historialBitacoraEjecucionOrdenado.length" class="alert alert-light border mb-0">
                        Aún no hay registros para este reclamo.
                    </div>
                    <ul v-else class="list-group list-group-flush modal-obs-ejecucion-lista bitacora-obra-feed mb-0">
                        <li v-for="o in historialBitacoraEjecucionOrdenado" :key="o.id" class="list-group-item px-0 py-2" :class="esEntradaCambioEstadoBitacora(o) ? 'bitacora-obra-evento-estado' : 'bitacora-obra-msg'">
                            <template v-if="esEntradaCambioEstadoBitacora(o)">
                                <div class="bitacora-obra-evento">
                                    <div class="bitacora-obra-evento__fila">
                                        <div class="bitacora-obra-evento__cuerpo">
                                            <div class="bitacora-obra-evento__meta">
                                                <span class="bitacora-obra-evento__usuario">{{ o.usuario_nombre || '—' }}</span>
                                                <span class="bitacora-obra-evento__etiqueta">Estado</span>
                                                <template v-if="o.ruta_nombre">
                                                    <span class="bitacora-obra-msg__sep" aria-hidden="true">·</span>
                                                    <span class="bitacora-obra-evento__ruta" :style="{ color: o.ruta_color || '#6c757d' }">
                                                        <svg class="bitacora-obra-evento__ruta-ico cronometro-badge-ico cronometro-badge-ico-ruta" viewBox="0 0 20 12" aria-hidden="true" focusable="false"><path d="M1 9.5 H6 V2.5 H14 V9.5 H19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                        <span>{{ o.ruta_nombre }}</span>
                                                    </span>
                                                </template>
                                            </div>
                                            <div class="bitacora-obra-evento__transicion historial-mapa-cambio-estado d-flex flex-wrap align-items-center gap-1">
                                                <span class="badge historial-mapa-estado-badge"
                                                      :style="{
                                                          backgroundColor: getColorEstado(o.estado_anterior),
                                                          color: colorTextoSobreEstadoReclamo(o.estado_anterior)
                                                      }">{{ o.estado_anterior }}</span>
                                                <i class="bi bi-arrow-right small text-muted px-1" aria-hidden="true"></i>
                                                <span class="badge historial-mapa-estado-badge"
                                                      :style="{
                                                          backgroundColor: getColorEstado(o.estado_nuevo),
                                                          color: colorTextoSobreEstadoReclamo(o.estado_nuevo)
                                                      }">{{ o.estado_nuevo }}</span>
                                            </div>
                                            <time class="bitacora-obra-evento__hora">{{ formatearFecha(o.created_at) }}</time>
                                        </div>
                                        <span class="bitacora-obra-evento__ico" aria-hidden="true"><i class="bi bi-arrow-left-right"></i></span>
                                    </div>
                                </div>
                            </template>
                            <template v-else>
                            <div class="bitacora-obra-msg__layout">
                                <div class="bitacora-obra-msg__avatar-col" aria-hidden="true">
                                    <img
                                        v-if="o.usuario_foto_perfil"
                                        class="bitacora-obra-msg__avatar bitacora-obra-msg__avatar--img"
                                        :src="urlFotoOperario(o.usuario_foto_perfil)"
                                        :alt="o.usuario_nombre || 'Usuario'"
                                        loading="lazy"
                                    >
                                    <span
                                        v-else
                                        class="bitacora-obra-msg__avatar bitacora-obra-msg__avatar--iniciales"
                                        :style="{ backgroundColor: colorAvatarOperario(o.usuario_nombre) }"
                                    >{{ inicialesOperario(o.usuario_nombre) }}</span>
                                </div>
                                <div class="bitacora-obra-msg__stack">
                                    <div class="bitacora-obra-msg__encabezado">
                                        <span class="bitacora-obra-msg__usuario">{{ o.usuario_nombre || '—' }}</span>
                                        <span class="bitacora-obra-msg__sep" aria-hidden="true">·</span>
                                        <span class="bitacora-obra-msg__tipo">
                                            <template v-if="esEntradaFotoBitacora(o)">
                                                <i class="bi bi-camera-fill" aria-hidden="true"></i> Foto
                                            </template>
                                            <template v-else>
                                                <i class="bi bi-chat-left-text" aria-hidden="true"></i> Nota
                                            </template>
                                        </span>
                                        <template v-if="o.ruta_nombre">
                                            <span class="bitacora-obra-msg__sep" aria-hidden="true">·</span>
                                            <span class="bitacora-obra-msg__ruta" :style="{ color: o.ruta_color || '#6c757d' }">
                                                <svg class="bitacora-obra-msg__ruta-ico cronometro-badge-ico cronometro-badge-ico-ruta" viewBox="0 0 20 12" aria-hidden="true" focusable="false"><path d="M1 9.5 H6 V2.5 H14 V9.5 H19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                <span>{{ o.ruta_nombre }}</span>
                                            </span>
                                        </template>
                                    </div>
                                    <div class="bitacora-obra-msg__bubble">
                                        <div class="bitacora-obra-msg__contenido">
                                            <template v-if="esEntradaFotoBitacora(o)">
                                                <button type="button" class="bitacora-obra-foto-link d-inline-block" @click="abrirModalFotoBitacoraObra(urlFotoBitacoraObra(o), o.texto || '')">
                                                    <img :src="urlFotoBitacoraObra(o)" class="bitacora-obra-foto-thumb" alt="Foto en obra" loading="lazy">
                                                </button>
                                                <p v-if="o.texto" class="mb-0 mt-2 text-break small">{{ o.texto }}</p>
                                            </template>
                                            <p v-else class="mb-0 text-break">{{ o.texto }}</p>
                                        </div>
                                        <time class="bitacora-obra-msg__hora">{{ formatearFecha(o.created_at) }}</time>
                                    </div>
                                </div>
                            </div>
                            </template>
                        </li>
                    </ul>
                    </div>

                    <div v-if="puedeRegistrarBitacoraEjecucion(reclamoSeleccionado)" class="bitacora-obra-composer border-top pt-3 mt-auto">
                        <textarea
                            id="textareaObservacionEjecucion"
                            class="form-control bitacora-obra-composer__input"
                            rows="2"
                            v-model="observacionEjecucionTexto"
                            maxlength="4000"
                            aria-label="Nueva nota"
                            placeholder="Escribí una nota…"
                        ></textarea>
                        <div v-if="previewFotoBitacora" class="bitacora-obra-preview mb-2 mt-2">
                            <img :src="previewFotoBitacora" alt="Vista previa" class="bitacora-obra-foto-thumb">
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" @click="limpiarPreviewFotoBitacora">Quitar</button>
                        </div>
                        <div class="d-flex flex-wrap gap-2 bitacora-obra-composer__acciones">
                            <button
                                v-if="!archivoFotoBitacora"
                                type="button"
                                class="btn btn-primary btn-sm"
                                @click="guardarObservacionEjecucion"
                                :disabled="!puedeGuardarObservacionEjecucion || guardandoObservacionEjecucion"
                                title="Guardar nota"
                            >
                                <span v-if="guardandoObservacionEjecucion" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                <i v-else class="bi bi-check-lg me-1" aria-hidden="true"></i>
                                Guardar
                            </button>
                            <button
                                v-else
                                type="button"
                                class="btn btn-primary btn-sm"
                                @click="guardarFotoBitacoraEjecucion"
                                :disabled="!puedeSubirFotoBitacoraEjecucion || guardandoFotoBitacora"
                                title="Guardar foto en la bitácora"
                            >
                                <span v-if="guardandoFotoBitacora" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                <i v-else class="bi bi-check-lg me-1" aria-hidden="true"></i>
                                Guardar
                            </button>
                            <label class="btn btn-outline-primary btn-sm mb-0" :class="{ disabled: guardandoFotoBitacora || guardandoObservacionEjecucion }" title="Elegir foto de la galería">
                                <i class="bi bi-image me-1" aria-hidden="true"></i> Foto
                                <input
                                    id="inputFotoBitacoraEjecucion"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,image/heic,image/heif,.jpg,.jpeg,.png,.webp,.heic,.heif"
                                    class="d-none"
                                    @change="onSeleccionFotoBitacoraEjecucion"
                                >
                            </label>
                        </div>
                    </div>
                    <p v-else-if="registrosObraReclamoSoloLectura(reclamoSeleccionado)" class="text-muted small mb-0 border-top pt-3">Solo lectura.</p>
                    <p v-else class="text-muted small mb-0 border-top pt-3">Podés registrar mientras el reclamo está en obra.</p>
                </div>
                <div class="tareas-modal__footer tareas-modal__footer--end">
                    <button type="button" class="tareas-btn tareas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Acciones / Materiales -->
    <div class="modal fade" id="modalAcciones" tabindex="-1" @hidden.bs.modal="onModalAccionesOculto">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rutas-modal tareas-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-lightning-charge"></i></span>
                        <h5>
                            <template v-if="modalAccionesSoloMateriales && modalMaterialesSoloLectura">Materiales (consulta) — Reclamo #{{ reclamoSeleccionado.municipalidad_id }}</template>
                            <template v-else-if="modalAccionesSoloMateriales">Materiales — Reclamo #{{ reclamoSeleccionado.municipalidad_id }}</template>
                            <template v-else>Cambiar estado — Reclamo #{{ reclamoSeleccionado.municipalidad_id }}</template>
                        </h5>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Pestañas de navegación (solo si hay más de una sección) -->
                    <ul v-show="!modalAccionesSoloMateriales" class="nav nav-tabs rutas-tabs" id="accionesTabs" role="tablist">
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
                    </ul>
                    
                    <!-- Contenido de las pestañas -->
                    <div class="tab-content" id="accionesTabsContent">
                        <!-- Pestaña Cambiar Estado -->
                        <div v-show="!modalAccionesSoloMateriales" class="tab-pane fade" :class="{ 'show active': !modalAccionesSoloMateriales }" id="cambiar-estado" role="tabpanel" aria-labelledby="cambiar-estado-tab">
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
                                        <option value="Pendiente">Pendiente (obra pausada)</option>
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
                        <div class="tab-pane fade" :class="{ 'show active': modalAccionesSoloMateriales }" id="materiales" role="tabpanel" aria-labelledby="materiales-tab">
                            <div class="mat-obra mt-3">
                                <p v-if="modalMaterialesSoloLectura" class="mat-obra__hint">Registro de materiales (solo lectura).</p>

                                <!-- Registrar -->
                                <div v-if="!modalMaterialesSoloLectura" class="mat-obra-registrar">
                                    <div class="mat-obra-search">
                                        <i class="bi bi-search"></i>
                                        <input
                                            type="text"
                                            v-model="filtroBusquedaMaterial"
                                            placeholder="Buscar material…">
                                        <button
                                            v-if="filtroBusquedaMaterial"
                                            type="button"
                                            class="mat-obra-search__clear"
                                            @click="filtroBusquedaMaterial = ''"
                                            title="Limpiar">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>

                                    <div class="mat-obra-chips" v-if="tiposMaterial.length">
                                        <button
                                            type="button"
                                            class="mat-obra-chip"
                                            :class="{ 'is-active': !materialSeleccionado.tipo_id }"
                                            @click="seleccionarTipoMaterialObra('')">
                                            Todos
                                        </button>
                                        <button
                                            v-for="tipo in tiposMaterial"
                                            :key="tipo.id"
                                            type="button"
                                            class="mat-obra-chip"
                                            :class="{ 'is-active': String(materialSeleccionado.tipo_id) === String(tipo.id) }"
                                            :style="String(materialSeleccionado.tipo_id) === String(tipo.id) && tipo.color ? { background: tipo.color, borderColor: tipo.color, color: '#fff' } : null"
                                            @click="seleccionarTipoMaterialObra(tipo.id)">
                                            <i v-if="tipo.icono" :class="tipo.icono"></i>
                                            {{ tipo.nombre }}
                                        </button>
                                    </div>

                                    <div v-if="cargandoCatalogoMateriales" class="mat-obra-empty">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        <p>Cargando catálogo…</p>
                                    </div>

                                    <div v-else-if="materialesCatalogoFiltrados.length === 0" class="mat-obra-empty">
                                        <i class="bi bi-box"></i>
                                        <p v-if="filtroBusquedaMaterial">No hay materiales para “{{ filtroBusquedaMaterial }}”.</p>
                                        <p v-else>No hay materiales en el catálogo.</p>
                                    </div>

                                    <div v-else class="mat-obra-grid">
                                        <button
                                            v-for="mat in materialesCatalogoFiltrados"
                                            :key="mat.id"
                                            type="button"
                                            class="mat-obra-card"
                                            :class="{ 'is-selected': String(materialSeleccionado.material_id) === String(mat.id) }"
                                            @click="seleccionarMaterialObra(mat)">
                                            <span class="mat-obra-card__foto">
                                                <img v-if="mat.foto" :src="urlFotoMaterialCatalogo(mat.foto)" :alt="mat.nombre">
                                                <i v-else class="bi bi-image"></i>
                                            </span>
                                            <span class="mat-obra-card__nombre">{{ mat.nombre }}</span>
                                        </button>
                                    </div>

                                    <div v-if="materialSeleccionado.material_id" class="mat-obra-selected">
                                        <div class="mat-obra-selected__info">
                                            <strong>{{ nombreMaterialSeleccionadoObra }}</strong>
                                            <span>Cantidad</span>
                                            <div class="mat-obra-stepper">
                                                <button type="button" @click="ajustarCantidadMaterialObra(-1)" aria-label="Restar">−</button>
                                                <input type="number" min="1" v-model.number="materialSeleccionado.cantidad" required>
                                                <button type="button" @click="ajustarCantidadMaterialObra(1)" aria-label="Sumar">+</button>
                                            </div>
                                        </div>
                                        <textarea
                                            class="mat-obra-obs"
                                            v-model="materialSeleccionado.observacion"
                                            rows="2"
                                            maxlength="500"
                                            placeholder="Observación (opcional)"></textarea>
                                        <div class="mat-obra-selected__actions">
                                            <button type="button" class="mat-obra-btn mat-obra-btn--ghost" @click="limpiarSeleccionMaterialObra">
                                                Cancelar
                                            </button>
                                            <button
                                                type="button"
                                                class="mat-obra-btn mat-obra-btn--primary"
                                                :disabled="!puedeGuardarMaterial || guardandoMaterialObra"
                                                @click="guardarMaterialReclamo">
                                                <i class="bi bi-check-lg"></i>
                                                {{ guardandoMaterialObra ? 'Guardando…' : 'Registrar' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Historial -->
                                <div class="mat-obra-historial">
                                    <h6 class="mat-obra-historial__title">
                                        <i class="bi bi-clock-history"></i>
                                        Registrados en este reclamo
                                    </h6>

                                    <div v-if="cargandoMateriales" class="mat-obra-empty">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        <p>Cargando…</p>
                                    </div>

                                    <div v-else-if="historialMateriales.length === 0" class="mat-obra-empty mat-obra-empty--sm">
                                        <p>Todavía no se registraron materiales.</p>
                                    </div>

                                    <div v-else class="mat-obra-historial__list bitacora-obra-feed">
                                        <article
                                            v-for="item in historialMateriales"
                                            :key="item.id"
                                            class="bitacora-obra-msg mat-hist-msg"
                                        >
                                            <div class="bitacora-obra-msg__layout">
                                                <div class="bitacora-obra-msg__avatar-col" aria-hidden="true">
                                                    <img
                                                        v-if="item.usuario_foto_perfil"
                                                        class="bitacora-obra-msg__avatar bitacora-obra-msg__avatar--img"
                                                        :src="urlFotoOperario(item.usuario_foto_perfil)"
                                                        :alt="item.usuario_nombre || 'Usuario'"
                                                        loading="lazy"
                                                    >
                                                    <span
                                                        v-else
                                                        class="bitacora-obra-msg__avatar bitacora-obra-msg__avatar--iniciales"
                                                        :style="{ backgroundColor: colorAvatarOperario(item.usuario_nombre) }"
                                                    >{{ inicialesOperario(item.usuario_nombre) }}</span>
                                                </div>
                                                <div class="bitacora-obra-msg__stack">
                                                    <div class="bitacora-obra-msg__encabezado">
                                                        <span class="bitacora-obra-msg__usuario">{{ item.usuario_nombre || '—' }}</span>
                                                        <span class="bitacora-obra-msg__sep" aria-hidden="true">·</span>
                                                        <span class="bitacora-obra-msg__tipo">
                                                            <i class="bi bi-box-seam" aria-hidden="true"></i> Material
                                                        </span>
                                                        <template v-if="item.ruta_nombre">
                                                            <span class="bitacora-obra-msg__sep" aria-hidden="true">·</span>
                                                            <span class="bitacora-obra-msg__ruta" :style="{ color: item.ruta_color || '#6c757d' }">
                                                                <svg class="bitacora-obra-msg__ruta-ico cronometro-badge-ico cronometro-badge-ico-ruta" viewBox="0 0 20 12" aria-hidden="true" focusable="false"><path d="M1 9.5 H6 V2.5 H14 V9.5 H19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                                <span>{{ item.ruta_nombre }}</span>
                                                            </span>
                                                        </template>
                                                    </div>
                                                    <div class="bitacora-obra-msg__bubble">
                                                        <div class="bitacora-obra-msg__contenido">
                                                            <button
                                                                type="button"
                                                                class="mat-hist-msg__main"
                                                                @click="verDetalleMaterial(item.id)"
                                                            >
                                                                <span class="mat-hist-msg__foto">
                                                                    <img v-if="item.material_foto" :src="urlFotoMaterialCatalogo(item.material_foto)" :alt="item.material_nombre" loading="lazy">
                                                                    <i v-else class="bi bi-box-seam"></i>
                                                                </span>
                                                                <div class="mat-hist-msg__body">
                                                                    <strong>{{ item.material_nombre || 'Material' }}</strong>
                                                                    <small>
                                                                        <span v-if="item.cantidad">x{{ item.cantidad }}</span>
                                                                        <span v-else>Sin cantidad</span>
                                                                    </small>
                                                                </div>
                                                            </button>
                                                            <p v-if="item.observacion" class="mb-0 mt-2 text-break small">{{ item.observacion }}</p>
                                                        </div>
                                                        <time class="bitacora-obra-msg__hora">{{ formatearFecha(item.fecha) }}</time>
                                                    </div>
                                                </div>
                                                <button
                                                    v-if="!modalMaterialesSoloLectura"
                                                    type="button"
                                                    class="mat-hist-msg__delete"
                                                    title="Eliminar"
                                                    :disabled="eliminandoMaterialReclamoId === item.id"
                                                    @click.stop="eliminarMaterialObra(item)"
                                                >
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </article>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tareas-modal__footer tareas-modal__footer--end">
                    <button type="button" class="tareas-btn tareas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Prompt materiales antes de completar -->
    <div
        class="modal fade"
        id="modalPromptMateriales"
        tabindex="-1"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
        @hidden.bs.modal="onPromptMaterialesOculto"
    >
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rutas-modal tareas-modal mat-prompt-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-box-seam"></i></span>
                        <h5>Materiales</h5>
                    </div>
                    <button
                        type="button"
                        class="rutas-modal__close"
                        aria-label="Cerrar"
                        @click="resolverPromptMateriales('cancelar')"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body mat-prompt-modal__body">
                    <p class="mat-prompt-modal__title">¿Registraste materiales?</p>
                    <p class="mat-prompt-modal__detalle">{{ promptMaterialesDetalle }}</p>
                    <p class="mat-prompt-modal__hint">Podés cargarlos ahora o completar sin registrar.</p>
                </div>
                <div class="mat-prompt-modal__actions">
                    <button
                        type="button"
                        class="tareas-btn mat-prompt-modal__btn"
                        @click="resolverPromptMateriales('registrar')"
                    >
                        <i class="bi bi-box-seam"></i>
                        Registrar ahora
                    </button>
                    <button
                        type="button"
                        class="tareas-btn tareas-btn--outline mat-prompt-modal__btn"
                        @click="resolverPromptMateriales('omitir')"
                    >
                        Continuar sin materiales
                    </button>
                    <button
                        type="button"
                        class="mat-prompt-modal__cancel"
                        @click="resolverPromptMateriales('cancelar')"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmar eliminar material -->
    <div
        class="modal fade"
        id="modalConfirmarEliminarMaterial"
        tabindex="-1"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
        @hidden.bs.modal="onConfirmarEliminarMaterialOculto"
    >
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content rutas-modal tareas-modal mat-prompt-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-trash"></i></span>
                        <h5>Eliminar material</h5>
                    </div>
                    <button
                        type="button"
                        class="rutas-modal__close"
                        aria-label="Cerrar"
                        @click="resolverConfirmarEliminarMaterial(false)"
                    >
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body mat-prompt-modal__body">
                    <p class="mat-prompt-modal__title">¿Eliminar este registro?</p>
                    <p class="mat-prompt-modal__detalle">
                        Se va a quitar “{{ confirmarEliminarMaterialNombre }}” de esta intervención.
                    </p>
                    <p class="mat-prompt-modal__hint">Si hace falta, después podés registrarlo de nuevo.</p>
                </div>
                <div class="mat-prompt-modal__actions">
                    <button
                        type="button"
                        class="tareas-btn mat-prompt-modal__btn mat-prompt-modal__btn--danger"
                        @click="resolverConfirmarEliminarMaterial(true)"
                    >
                        <i class="bi bi-trash"></i>
                        Eliminar
                    </button>
                    <button
                        type="button"
                        class="tareas-btn tareas-btn--outline mat-prompt-modal__btn"
                        @click="resolverConfirmarEliminarMaterial(false)"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Añadir Reclamos a Hoja de Ruta -->
    <div class="modal fade" id="modalAñadirReclamos" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rutas-modal tareas-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-plus-lg"></i></span>
                        <h5>
                            Añadir paradas
                            <span v-if="rutaSeleccionada" class="text-muted fw-normal">· {{ rutaSeleccionada.nombre }}</span>
                        </h5>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" @click="cerrarModalAñadirReclamos" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
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
                                       placeholder="Buscar por ID, domicilio o descripción..." 
                                       v-model="filtroBusquedaReclamos"
                                       @input="filtrarReclamosRecibidos">
                            </div>
                        </div>
                    </div>

                    <!-- Lista vertical agrupada por domicilio -->
                    <div class="añadir-paradas-lista" v-if="paradasReclamosAñadir.length > 0">
                        <article
                            v-for="parada in paradasReclamosAñadir"
                            :key="'add-parada-' + parada.clave"
                            class="añadir-parada-item"
                            @click="verDetallesReclamoRecibido(reclamoActivoEnParadaAñadir(parada))"
                        >
                            <span class="añadir-parada-item__icon-wrap">
                                <button
                                    v-if="parada.reclamos.length > 1"
                                    type="button"
                                    class="ruta-secuencia-grupo-badge"
                                    :style="{ backgroundColor: getColorEstado(reclamoActivoEnParadaAñadir(parada).municipalidad_estado) }"
                                    :title="parada.reclamos.length + ' reclamos en este domicilio'"
                                    @click.stop="navegarReclamoEnParadaAñadir(parada, 1)"
                                >
                                    {{ parada.reclamos.length }}
                                </button>
                                <span
                                    v-else
                                    class="ruta-secuencia-motivo-icon"
                                    :style="{ backgroundColor: getColorEstado(parada.reclamos[0].municipalidad_estado) }"
                                    :title="parada.reclamos[0].municipalidad_motivo || 'Motivo'"
                                >
                                    {{ iconoMotivoReclamo(parada.reclamos[0].municipalidad_motivo) }}
                                </span>
                                <span
                                    v-if="marcadorGrupoTienePrioridadAlta(parada.reclamos)"
                                    class="mapa-prioridad-alta-badge ruta-secuencia-prioridad-badge"
                                    aria-label="Prioridad alta"
                                    title="Prioridad alta"
                                >!</span>
                            </span>

                            <div class="añadir-parada-item__main">
                                <div class="añadir-parada-item__id-row">
                                    <span class="añadir-parada-item__id">
                                        #{{ reclamoActivoEnParadaAñadir(parada).municipalidad_id }}
                                    </span>
                                    <span
                                        v-if="parada.reclamos.length > 1"
                                        class="añadir-parada-item__count-chip"
                                        :title="parada.reclamos.length + ' reclamos en este domicilio'"
                                    >
                                        ×{{ parada.reclamos.length }}
                                    </span>
                                </div>

                                <div class="añadir-parada-item__domicilio">
                                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                    {{ reclamoActivoEnParadaAñadir(parada).municipalidad_domicilio }}
                                    {{ reclamoActivoEnParadaAñadir(parada).municipalidad_numeroDomicilio }}
                                </div>

                                <div class="añadir-parada-item__descripcion" :title="reclamoActivoEnParadaAñadir(parada).municipalidad_descripcion || ''">
                                    {{ reclamoActivoEnParadaAñadir(parada).municipalidad_descripcion || 'Sin descripción' }}
                                </div>

                                <div
                                    v-if="parada.reclamos.length > 1"
                                    class="ruta-secuencia-grupo-nav añadir-parada-item__nav"
                                    @click.stop
                                >
                                    <button
                                        type="button"
                                        class="mapa-popup-nav mapa-popup-nav-prev"
                                        @click="navegarReclamoEnParadaAñadir(parada, -1)"
                                        aria-label="Reclamo anterior"
                                    >
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <span class="ruta-secuencia-grupo-contador">
                                        {{ indiceReclamoEnParadaAñadir(parada) + 1 }} de {{ parada.reclamos.length }}
                                    </span>
                                    <button
                                        type="button"
                                        class="mapa-popup-nav mapa-popup-nav-next"
                                        @click="navegarReclamoEnParadaAñadir(parada, 1)"
                                        aria-label="Siguiente reclamo"
                                    >
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="añadir-parada-item__accion" @click.stop>
                                <button
                                    type="button"
                                    class="tareas-btn tareas-btn--success tareas-btn--sm"
                                    @click="añadirParadaARuta(parada)"
                                    :disabled="añadiendoParadaClave === parada.clave"
                                    :title="parada.reclamos.length > 1 ? 'Añadir los ' + parada.reclamos.length + ' reclamos de esta parada' : 'Añadir a mi hoja de ruta'"
                                >
                                    <span v-if="añadiendoParadaClave === parada.clave" class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    <i v-else class="bi bi-plus-circle"></i>
                                    {{ añadiendoParadaClave === parada.clave ? 'Añadiendo…' : 'Añadir' }}
                                </button>
                            </div>
                        </article>
                    </div>

                    <!-- Mensaje cuando no hay reclamos -->
                    <div v-else class="text-center py-5 añadir-paradas-empty">
                        <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
                        <h4 class="text-muted mt-3">No hay paradas disponibles</h4>
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
            <div class="modal-content rutas-modal tareas-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-card-text"></i></span>
                        <h5>Detalles del Reclamo</h5>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" @click="cerrarModalDetallesReclamoRecibido" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
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
                <div class="tareas-modal__footer tareas-modal__footer--end">
                    <button type="button" class="tareas-btn tareas-btn--success" @click="añadirReclamoARuta(reclamoRecibidoSeleccionado)" :disabled="!!añadiendoParadaClave">
                        <span v-if="añadiendoParadaClave" class="spinner-border spinner-border-sm me-1" role="status"></span>
                        <i v-else class="bi bi-plus-circle"></i>
                        {{ añadiendoParadaClave ? 'Añadiendo…' : 'Añadir parada a mi hoja' }}
                    </button>
                    <button type="button" class="tareas-btn tareas-btn--outline" data-bs-dismiss="modal" @click="cerrarModalDetallesReclamoRecibido">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Material Reclamo -->
    <div class="modal fade" id="modalDetalleMaterial" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rutas-modal tareas-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-box-seam"></i></span>
                        <h5>Detalle del Material Utilizado</h5>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div v-if="cargandoDetalleMaterial" class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando detalle...</p>
                    </div>
                    <div v-else-if="detalleMaterial">
                        <div class="mat-obra-detalle-foto" v-if="detalleMaterial.material_foto">
                            <img :src="urlFotoMaterialCatalogo(detalleMaterial.material_foto)" :alt="detalleMaterial.material_nombre">
                        </div>
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
                                <div class="mb-3">
                                    <label class="fw-bold">Fecha de Registro:</label>
                                    <p>{{ formatearFecha(detalleMaterial.fecha) }}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Usuario:</label>
                                    <div class="mat-hist-msg__usuario-detalle">
                                        <img
                                            v-if="detalleMaterial.usuario_foto_perfil"
                                            class="bitacora-obra-msg__avatar bitacora-obra-msg__avatar--img"
                                            :src="urlFotoOperario(detalleMaterial.usuario_foto_perfil)"
                                            :alt="detalleMaterial.usuario_nombre || 'Usuario'"
                                        >
                                        <span
                                            v-else
                                            class="bitacora-obra-msg__avatar bitacora-obra-msg__avatar--iniciales"
                                            :style="{ backgroundColor: colorAvatarOperario(detalleMaterial.usuario_nombre) }"
                                        >{{ inicialesOperario(detalleMaterial.usuario_nombre) }}</span>
                                        <p class="mb-0">{{ detalleMaterial.usuario_nombre || 'Sistema' }}</p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-bold">Hoja de ruta:</label>
                                    <p v-if="detalleMaterial.ruta_nombre" class="mat-obra-hist-item__ruta mb-0" :style="{ color: detalleMaterial.ruta_color || '#6c757d' }">
                                        <svg class="mat-obra-hist-item__ruta-ico" viewBox="0 0 20 12" aria-hidden="true" focusable="false"><path d="M1 9.5 H6 V2.5 H14 V9.5 H19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <span>{{ detalleMaterial.ruta_nombre }}</span>
                                    </p>
                                    <p v-else class="text-muted mb-0">Sin ruta asociada</p>
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
                <div class="tareas-modal__footer tareas-modal__footer--end">
                    <button type="button" class="tareas-btn tareas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox foto de bitácora (teleport a body para quedar siempre encima y centrado) -->
    <teleport to="body">
        <div
            v-if="bitacoraFotoAmpliadaActiva"
            class="bitacora-foto-obra-lightbox"
            role="dialog"
            aria-modal="true"
            aria-label="Foto ampliada"
            @click.self="cerrarModalFotoBitacoraObra"
        >
            <div class="bitacora-foto-obra-modal__wrap">
                <button type="button" class="btn-close btn-close-white bitacora-foto-obra-modal__close" @click="cerrarModalFotoBitacoraObra" aria-label="Cerrar"></button>
                <img :src="bitacoraFotoAmpliadaUrl" alt="Foto en obra" class="bitacora-foto-obra-modal__img">
                <p v-if="bitacoraFotoAmpliadaCaption" class="bitacora-foto-obra-modal__caption">{{ bitacoraFotoAmpliadaCaption }}</p>
            </div>
        </div>
    </teleport>
</div>
