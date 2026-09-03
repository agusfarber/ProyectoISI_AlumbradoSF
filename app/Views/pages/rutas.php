<!-- Página para ver el listado de todas las hojas de ruta.

 Deben aparecer en una tabla las hojas de ruta, en donde se vea el id de la hoja de ruta, la cantidad de reclamos que tiene dicha hoja de ruta, si está activa, la cuadrilla a la que fue asignada, la fecha de creación y el tiempo estimado.

 También debe tener la tabla una columna de acciones en donde una de las acciones sea un ojito, el cual al darle clic muestre una modal de la hoja de ruta en el mapa de google maps

----------------------------------------------------------------------
 Debe también haber un botón arriba a la izquierda que sea para crear una nueva hoja de ruta:


 Al darle clic a este botón, debe solicitarle al usuario que ingrese la cantidad de reclamos que quiere incluir en la hoja de ruta.


 Luego de seleccionar la cantidad de reclamos que quiere incluir en la hoja de ruta, el algoritmo del backend se encargará de hacer esta seleccion de los reclamos que van a integrar la hoja de ruta y el orden de los mismos
 ----------------------------------------------------------------------
 (Más adelante incluiremos la hoja de ruta vista en mapbox, pero por ahora solo en google maps)
 -->

<div id="app" class="rutas-page">

    <div class="app-page-title">
        <span class="app-page-title__icon"><i class="bi bi-map"></i></span>
        <h1 class="app-page-title__text">Rutas</h1>
    </div>

    <div class="rutas-toolbar">
        <div class="rutas-toolbar__left">
            <ul v-if="puedeVerHistorialEjecuciones" class="nav rutas-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link" :class="{ active: solapaRutas === 'activas' }" @click="solapaRutas = 'activas'">Hojas activas</button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link" :class="{ active: solapaRutas === 'historial' }" @click="solapaRutas = 'historial'">Historial de rutas</button>
        </li>
    </ul>
        </div>
        <div class="rutas-toolbar__right" v-show="solapaRutas === 'activas'">
            <button class="rutas-btn" @click="abrirModalCrearRuta">
                <i class="bi bi-plus-lg"></i> Nueva hoja de ruta
            </button>
            <button class="rutas-btn rutas-btn--success" @click="abrirModalVisualizarRutas">
                <i class="bi bi-map"></i> Visualizar rutas
           </button>
      </div>
   </div>

   <div v-show="solapaRutas === 'activas'" class="rutas-tab-panel">

  <!-- Panel en tarjetas (supervisor) -->
  <div v-if="esSupervisorVistaTarjetas" class="supervisor-rutas-panel">
      <p v-if="!rutasActivasPanel.length" class="text-muted text-center py-5 mb-0">
          No hay hojas de ruta activas en este momento.
      </p>
      <div v-else class="supervisor-rutas-grid">
          <article
              v-for="ruta in rutasActivasPanel"
              :key="ruta.id"
              class="supervisor-ruta-card"
              :class="{ 'supervisor-ruta-card--seleccionada': rutaDetalleSupervisorId === ruta.id }"
              @click="abrirDetalleSupervisor(ruta)"
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
                  <div :id="'mapaPreviewRuta-' + ruta.id"></div>
                  <div v-if="!mapasPreviewSupervisor[ruta.id]" class="supervisor-ruta-card__mapa-placeholder">
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
                          :class="claseCronometroEjecucionRutaSupervisor(ruta)"
                      ><svg class="cronometro-badge-ico cronometro-badge-ico-ruta" viewBox="0 0 20 12" aria-hidden="true" focusable="false"><path d="M1 9.5 H6 V2.5 H14 V9.5 H19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="cronometro-badge-txt">{{ tiempoTranscurridoEjecucionSupervisor(ruta) }}</span></span>
                  </div>
              </div>
          </article>
      </div>
  </div>

  <!-- Modal detalle de hoja (supervisor) -->
  <div class="modal fade" id="modalDetalleSupervisorRuta" tabindex="-1" aria-labelledby="modalDetalleSupervisorRutaLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered supervisor-ruta-detalle-modal-dialog">
          <div class="modal-content rutas-modal supervisor-ruta-detalle-modal">
              <div
                  class="supervisor-ruta-detalle-modal__franja-superior"
                  :style="{
                      backgroundColor: rutaVisualizando.color || '#808080',
                      color: textoSobreColorRuta(rutaVisualizando.color)
                  }"
              >
                  <div class="supervisor-ruta-detalle-modal__franja-inner">
                      <h5 class="mb-0 supervisor-ruta-detalle-modal__nombre-ruta" id="modalDetalleSupervisorRutaLabel">
                          {{ rutaVisualizando.nombre || 'Hoja de ruta' }}
                      </h5>
                      <button
                          type="button"
                          class="btn-close flex-shrink-0"
                          :class="{ 'btn-close-white': textoSobreColorRuta(rutaVisualizando.color) === '#fff' }"
                          data-bs-dismiss="modal"
                          aria-label="Cerrar"
                      ></button>
                  </div>
              </div>
              <div class="modal-body p-0 supervisor-ruta-detalle__cuerpo">
                  <div class="supervisor-ruta-detalle-layout">
                      <aside class="supervisor-ruta-detalle-panel-izq">
                          <div class="ruta-detalle-encabezado supervisor-ruta-detalle-panel-izq__bloque supervisor-ruta-detalle-panel-izq__bloque--overlay">
                              <p class="supervisor-ruta-detalle-panel-izq__subtitulo mb-0">Detalle de hoja de ruta</p>
                              <div class="ruta-detalle-meta-fila">
                                  <span class="ruta-detalle-pill w-100">
                                      <i class="bi bi-clipboard-data"></i>
                                      <strong>{{ rutaVisualizando.cantidadReclamos || 0 }}</strong> domicilios
                      </span>
                  </div>
                              <div class="ruta-detalle-meta-fila">
                                  <span class="ruta-detalle-pill w-100">
                                      <i class="bi bi-clock-fill"></i>
                                      Estimado: <strong>{{ rutaVisualizando.tiempoEstimado || '—' }}</strong>
                      </span>
                  </div>
                              <div class="ruta-detalle-meta-fila">
                                  <button v-if="rutaVisualizando.cuadrilla_id"
                                          type="button"
                                          class="ruta-detalle-pill ruta-detalle-pill--clickable w-100"
                                          :class="{ 'ruta-detalle-pill--activa': mostrarDetalleCuadrillaSupervisor }"
                                          :title="mostrarDetalleCuadrillaSupervisor ? 'Ocultar detalle de cuadrilla' : 'Ver detalle de cuadrilla'"
                                          @click="toggleDetalleCuadrillaSupervisor">
                                      <i class="bi bi-people-fill"></i>
                                      <strong>{{ rutaVisualizando.cuadrilla_nombre || 'Sin asignar' }}</strong>
                                      <i class="bi ruta-detalle-pill__chevron ms-auto"
                                         :class="mostrarDetalleCuadrillaSupervisor ? 'bi-chevron-up' : 'bi-chevron-down'"
                                         aria-hidden="true"></i>
                                  </button>
                                  <span v-else class="ruta-detalle-pill w-100">
                                      <i class="bi bi-people-fill"></i>
                                      <strong>Sin asignar</strong>
                                  </span>
                              </div>
                              <div class="ruta-detalle-meta-fila">
                                  <span class="ruta-detalle-estado-grupo d-inline-flex align-items-center flex-wrap gap-1">
                                      <span class="badge" :class="claseBadgeEstadoEjecucionRuta(rutaVisualizando)">
                                          {{ textoEstadoEjecucionRuta(rutaVisualizando) }}
                                      </span>
                                      <span
                                          v-if="esEstadoEjecucionRuta(rutaVisualizando)"
                                          :class="claseCronometroEjecucionRutaSupervisor(rutaVisualizando)"
                                      ><svg class="cronometro-badge-ico cronometro-badge-ico-ruta" viewBox="0 0 20 12" aria-hidden="true" focusable="false"><path d="M1 9.5 H6 V2.5 H14 V9.5 H19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="cronometro-badge-txt">{{ tiempoTranscurridoEjecucionSupervisor(rutaVisualizando) }}</span></span>
                                  </span>
                              </div>
                              <div class="supervisor-ruta-detalle-panel-izq__acciones d-flex flex-column gap-2">
                  <button
                                      v-if="puedeAsignarOCambiarCuadrillaRuta(rutaVisualizando)"
                      type="button"
                                      class="rutas-btn rutas-btn--sm rutas-btn--outline w-100"
                                      @click="abrirModalAsignarRuta(rutaVisualizando.id)"
                  >
                      <i class="bi bi-people-fill"></i>
                                      {{ rutaVisualizando.asignada == 1 ? 'Cambiar cuadrilla' : 'Asignar cuadrilla' }}
                                  </button>
                                  <button
                                      type="button"
                                      class="rutas-btn rutas-btn--sm rutas-btn--danger w-100"
                                      :disabled="!puedeEliminarHojaRuta(rutaVisualizando)"
                                      :title="puedeEliminarHojaRuta(rutaVisualizando) ? 'Eliminar hoja de ruta' : motivoNoPuedeEliminarHojaRuta(rutaVisualizando)"
                                      @click="eliminarRutaDesdeVisualizacion(rutaVisualizando.id)"
                                  >
                                      <i class="bi bi-trash"></i> Eliminar hoja
                  </button>
  </div>

                              <div v-if="mostrarDetalleCuadrillaSupervisor && cuadrillaAsignadaDetalleSupervisor"
                                   class="crear-ruta-cuadrilla-overlay supervisor-cuadrilla-overlay">
                                  <button
                                      type="button"
                                      class="crear-ruta-cuadrilla-overlay__backdrop"
                                      aria-label="Cerrar detalle"
                                      @click="cerrarDetalleCuadrillaSupervisor"
                                  ></button>
                                  <div class="crear-ruta-cuadrilla-overlay__panel" role="dialog" aria-modal="true">
                                      <div class="crear-ruta-cuadrilla-overlay__header">
                                          <h4 class="crear-ruta-cuadrilla-overlay__title">{{ cuadrillaAsignadaDetalleSupervisor.nombre }}</h4>
                                          <button
                                              type="button"
                                              class="crear-ruta-cuadrilla-overlay__close"
                                              aria-label="Cerrar"
                                              @click="cerrarDetalleCuadrillaSupervisor"
                                          >
                                              <i class="bi bi-x-lg"></i>
              </button>
                                      </div>
                                      <div class="crear-ruta-cuadrilla-overlay__body">
                                          <p v-if="cuadrillaAsignadaDetalleSupervisor.descripcion" class="crear-ruta-cuadrilla-card__desc">{{ cuadrillaAsignadaDetalleSupervisor.descripcion }}</p>
                                          <div v-if="cuadrillaAsignadaDetalleSupervisor.operarios && cuadrillaAsignadaDetalleSupervisor.operarios.length"
                                               class="crear-ruta-cuadrilla-card__members">
                                              <div
                                                  v-for="op in cuadrillaAsignadaDetalleSupervisor.operarios"
                                                  :key="'sup-det-cuad-' + op.id"
                                                  class="crear-ruta-cuadrilla-card__member"
                                              >
                                                  <img
                                                      v-if="op.foto_perfil"
                                                      class="crc-avatar crc-avatar--img crc-avatar--row"
                                                      :src="urlFotoOperario(op.foto_perfil)"
                                                      :alt="op.nombre"
                                                  >
                  <span
                                                      v-else
                                                      class="crc-avatar crc-avatar--row"
                                                      :style="{ backgroundColor: colorAvatarOperario(op.nombre) }"
                                                  >
                                                      {{ inicialesOperario(op.nombre) }}
                                                  </span>
                                                  <span class="crear-ruta-cuadrilla-card__member-name">{{ op.nombre }}</span>
                                                  <span v-if="Number(op.es_jefe) === 1" class="crear-ruta-cuadrilla-card__member-role">Gestión</span>
          </div>
                                          </div>
                                          <p v-else class="crear-ruta-cuadrilla-card__empty-team">
                                              <i class="bi bi-person-dash"></i> Sin operarios asignados
                                          </p>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </aside>
                      <div class="supervisor-ruta-detalle-panel-der">
                          <div class="supervisor-ruta-detalle-vista-contenido">
                              <div v-show="modoVistaDetalleSupervisor === 'mapa'" class="supervisor-ruta-detalle-vista-mapa">
                                  <div id="mapaDetalleSupervisor" v-show="proveedorMapaVisualizacion === 'google'" class="supervisor-ruta-detalle-modal__mapa"></div>
                                  <div id="mapaDetalleSupervisorMapbox" v-show="proveedorMapaVisualizacion === 'mapbox'" class="supervisor-ruta-detalle-modal__mapa"></div>
                                  <div class="crear-ruta-vista-controles">
                                      <button
                                          type="button"
                                          class="rutas-btn rutas-btn--sm rutas-btn--success"
                                          @click="alternarProveedorVisualizacion"
                                      >
                                          <i class="bi bi-arrow-repeat"></i>
                                          {{ proveedorMapaVisualizacion === 'google' ? 'Mapbox' : 'Google Maps' }}
                                      </button>
                                  </div>
                                  <div class="crear-ruta-vista-controles crear-ruta-vista-controles--derecha">
                                      <button
                                          type="button"
                                          class="rutas-btn rutas-btn--sm rutas-btn--outline"
                                          @click="cambiarModoVistaDetalleSupervisor('lista')"
                                      >
                                          <i class="bi bi-list-ul"></i> Ver lista
                                      </button>
                                  </div>
                              </div>
                              <div v-show="modoVistaDetalleSupervisor === 'lista'" class="supervisor-ruta-detalle-vista-lista">
                                  <div class="crear-ruta-vista-controles crear-ruta-vista-controles--derecha">
                                      <button
                                          type="button"
                                          class="rutas-btn rutas-btn--sm rutas-btn--outline"
                                          @click="cambiarModoVistaDetalleSupervisor('mapa')"
                                      >
                                          <i class="bi bi-map"></i> Ver mapa
                                      </button>
                                  </div>
                                  <p v-if="!reclamosRutaVisualizando.length" class="text-muted text-center py-4 mb-0">
                                      Sin reclamos cargados.
                                  </p>
                                  <div v-else class="ruta-secuencia-container supervisor-detalle-secuencia">
                                      <div v-for="(parada, idx) in paradasListaVisualizacion"
                                           :key="'sup-detalle-parada-' + parada.clave + '-' + idx"
                                           class="ruta-secuencia-item">
                                          <div class="card reclamo-card reclamo-card-secuencia"
                                               :class="[
                                                   getCardClassCrearRuta(reclamoActivoEnParadaListaVisualizacion(parada)),
                                                   { 'reclamo-card-secuencia--en-obra': reclamoEnObraActivaSupervisor(reclamoActivoEnParadaListaVisualizacion(parada)) }
                                               ]">
                                              <div class="card-body ruta-secuencia-cardbody">
                                                  <div class="ruta-secuencia-fila">
                                                      <span class="ruta-secuencia-icon-wrap">
                                                          <button v-if="parada.reclamos.length > 1"
                                                                  type="button"
                                                                  class="ruta-secuencia-grupo-badge"
                                                                  :style="{ backgroundColor: getColorEstado(reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_estado) }"
                                                                  :title="parada.reclamos.length + ' reclamos en este domicilio'"
                                                                  @click.stop="navegarReclamoEnParadaListaVisualizacion(parada, 1)">
                                                              {{ parada.reclamos.length }}
                                                          </button>
                                                          <span v-else
                                                                class="ruta-secuencia-motivo-icon"
                                                                :style="{ backgroundColor: getColorEstado(parada.reclamos[0].municipalidad_estado) }"
                                                                :title="parada.reclamos[0].municipalidad_motivo || 'Motivo no especificado'">
                                                              {{ iconoMotivoReclamo(parada.reclamos[0].municipalidad_motivo) }}
                                                          </span>
                                                          <span v-if="marcadorGrupoTienePrioridadAlta(parada.reclamos)"
                                                                class="mapa-prioridad-alta-badge ruta-secuencia-prioridad-badge"
                                                                aria-label="Prioridad alta">!</span>
                                                      </span>
                                                      <div class="ruta-secuencia-main"
                                                           @click="seleccionarReclamoDetalleSupervisor(reclamoActivoEnParadaListaVisualizacion(parada))"
                                                           role="button"
                                                           tabindex="0">
                                                          <span class="ruta-secuencia-id">{{ reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_id }}</span>
                                                          <span class="ruta-secuencia-domicilio">
                                                              <span class="ruta-secuencia-calle"
                                                                    :title="(reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_domicilio || '') + ' ' + (reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_numeroDomicilio || '')">
                                                                  <i class="bi bi-geo-alt ruta-secuencia-calle-ico" aria-hidden="true"></i>
                                                                  {{ reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_domicilio }}
                                                                  {{ reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_numeroDomicilio }}
              </span>
              <span
                                                                  v-if="reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_descripcion"
                                                                  class="ruta-secuencia-descripcion"
                                                                  :title="reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_descripcion"
                                                              >
                                                                  {{ reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_descripcion }}
              </span>
                                                              <div v-if="parada.reclamos.length > 1"
                                                                   class="ruta-secuencia-grupo-nav"
                                                                   @click.stop>
                                                                  <button type="button"
                                                                          class="mapa-popup-nav mapa-popup-nav-prev"
                                                                          @click="navegarReclamoEnParadaListaVisualizacion(parada, -1)"
                                                                          aria-label="Reclamo anterior">
                                                                      <i class="bi bi-chevron-left"></i>
              </button>
                                                                  <span class="ruta-secuencia-grupo-contador">
                                                                      {{ indiceReclamoEnParadaListaVisualizacion(parada) + 1 }} de {{ parada.reclamos.length }}
                                                                  </span>
                                                                  <button type="button"
                                                                          class="mapa-popup-nav mapa-popup-nav-next"
                                                                          @click="navegarReclamoEnParadaListaVisualizacion(parada, 1)"
                                                                          aria-label="Siguiente reclamo">
                                                                      <i class="bi bi-chevron-right"></i>
              </button>
          </div>
                                                          </span>
      </div>
                                                      <div class="ruta-secuencia-toolbar" @click.stop>
                                                          <div
                                                              v-if="mostrarCronometroReparacionReclamoSupervisor(reclamoActivoEnParadaListaVisualizacion(parada))"
                                                              class="ruta-secuencia-toolbar__inicio"
                                                          >
                                                              <span
                                                                  class="ruta-secuencia-crono-reparacion badge font-monospace cronometro-badge-con-ico"
                                                                  :class="claseCronometroListaObraSupervisor(reclamoActivoEnParadaListaVisualizacion(parada))"
                                                                  title="Tiempo en reparación"
                                                              ><i class="bi bi-truck cronometro-badge-ico" aria-hidden="true"></i><span class="cronometro-badge-txt">{{ textoCronometroReparacionReclamoSupervisor(reclamoActivoEnParadaListaVisualizacion(parada)) }}</span></span>
                                  </div>
                                                          <div class="ruta-secuencia-toolbar__paneles">
                                                              <button
                                                                  type="button"
                                                                  class="btn btn-sm btn-outline-secondary ruta-secuencia-btn-material btn-con-badge-obs"
                                                                  :title="cantidadMaterialesReclamoSupervisor(reclamoActivoEnParadaListaVisualizacion(parada)) > 0
                                                                      ? 'Materiales utilizados (' + cantidadMaterialesReclamoSupervisor(reclamoActivoEnParadaListaVisualizacion(parada)) + ')'
                                                                      : 'Materiales utilizados'"
                                                                  @click="abrirModalMaterialesSupervisor(reclamoActivoEnParadaListaVisualizacion(parada))"
                                                              >
                                                                  <i class="bi bi-box-seam"></i>
                                                                  <span
                                                                      class="btn-obs-ejecucion-count"
                                                                      :class="{ 'btn-obs-ejecucion-count--oculto': cantidadMaterialesReclamoSupervisor(reclamoActivoEnParadaListaVisualizacion(parada)) < 1 }"
                                                                  >{{ textoObservacionesEjecucionBadge(cantidadMaterialesReclamoSupervisor(reclamoActivoEnParadaListaVisualizacion(parada))) || '0' }}</span>
                                                              </button>
                                                              <button
                                                                  type="button"
                                                                  class="btn btn-sm btn-outline-secondary ruta-secuencia-btn-obs-ejecucion btn-con-badge-obs"
                                                                  :title="cantidadObservacionesEjecucionReclamoSupervisor(reclamoActivoEnParadaListaVisualizacion(parada)) > 0
                                                                      ? 'Registro en obra (' + cantidadObservacionesEjecucionReclamoSupervisor(reclamoActivoEnParadaListaVisualizacion(parada)) + ')'
                                                                      : 'Registro en obra'"
                                                                  @click="abrirModalObservacionesSupervisor(reclamoActivoEnParadaListaVisualizacion(parada))"
                                                              >
                                                                  <i class="bi bi-journal-text"></i>
                                                                  <span
                                                                      class="btn-obs-ejecucion-count"
                                                                      :class="{ 'btn-obs-ejecucion-count--oculto': cantidadObservacionesEjecucionReclamoSupervisor(reclamoActivoEnParadaListaVisualizacion(parada)) < 1 }"
                                                                  >{{ textoObservacionesEjecucionBadge(cantidadObservacionesEjecucionReclamoSupervisor(reclamoActivoEnParadaListaVisualizacion(parada))) || '0' }}</span>
                                                              </button>
                              </div>
                              </div>
                          </div>
                          </div>
                      </div>
                                          <div v-if="idx < paradasListaVisualizacion.length - 1" class="ruta-secuencia-flecha">
                                              <i class="bi bi-arrow-down"></i>
                  </div>
              </div>
                      </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>

  <!-- Tabla de hojas de ruta (admin y demás roles) -->
  <div v-if="!esSupervisorVistaTarjetas" class="rutas-table-section">
      <table id="tabla_rutas" class="table table-hover table-sm align-middle w-100 mb-0 rutas-table">
          <thead>
              <tr>
                  <th>Nombre</th>
                  <th>Cantidad de Reclamos</th>
                  <th>Estado de Hoja</th>
                  <th>Asignación</th>
              </tr>
          </thead>
          <tbody>
              <!-- Contenido de la tabla gestionado por DataTables -->
          </tbody>
      </table>
  </div>

   </div>

   <div v-show="solapaRutas === 'historial' && puedeVerHistorialEjecuciones" class="rutas-tab-panel">
      <p class="rutas-historial-hint">Ejecuciones finalizadas. Consultá el registro de eventos o abrí el mapa para ver el recorrido, tiempos en obra, materiales y observaciones de cada reclamo.</p>
      <div v-if="historialEjecucionesCargando" class="rutas-loading">
          <div class="spinner-border" role="status"><span class="visually-hidden">Cargando…</span></div>
      </div>
      <div v-else class="rutas-table-wrap">
          <table class="table table-hover table-sm align-middle w-100 mb-0 rutas-table rutas-historial-table">
              <thead>
                  <tr>
                      <th>ID</th>
                      <th>Hoja</th>
                      <th>Cuadrilla</th>
                      <th>Inicio ejecución</th>
                      <th>Fin ejecución</th>
                      <th style="width: 240px;">Acciones</th>
                  </tr>
              </thead>
              <tbody>
                  <tr v-if="!historialEjecuciones.length">
                      <td colspan="6" class="text-center text-muted py-4">No hay ejecuciones finalizadas en el historial.</td>
                  </tr>
                  <tr v-for="h in historialEjecuciones" :key="h.id">
                      <td>{{ h.id }}</td>
                      <td><span class="badge" :style="{ backgroundColor: h.ruta_color || '#6c757d', color: '#fff' }">{{ h.ruta_nombre }}</span></td>
                      <td>{{ h.cuadrilla_nombre || '—' }}</td>
                      <td>{{ h.inicio_at || '—' }}</td>
                      <td>{{ h.fin_at || '—' }}</td>
                      <td>
                          <div class="d-flex flex-wrap gap-1">
                              <button type="button" class="rutas-btn rutas-btn--sm rutas-btn--outline" @click="abrirDetalleHistorialEjecucion(h.id)">
                                  <i class="bi bi-list-ul"></i> Registro
                          </button>
                              <button type="button" class="rutas-btn rutas-btn--sm rutas-btn--outline" @click="abrirHistorialEjecucionMapa(h.id)">
                                  <i class="bi bi-map"></i> Mapa
                              </button>
                          </div>
                      </td>
                  </tr>
              </tbody>
          </table>
      </div>
   </div>

  <!-- Modal detalle historial de ejecución -->
  <div class="modal fade" id="modalHistorialEjecucion" tabindex="-1" aria-labelledby="modalHistorialEjecucionLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
          <div class="modal-content rutas-modal">
              <div class="rutas-modal__header">
                  <div class="rutas-modal__title">
                      <span class="rutas-modal__icon"><i class="bi bi-clock-history"></i></span>
                      <h5 id="modalHistorialEjecucionLabel">Historial de la ejecución</h5>
              </div>
                  <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                      <i class="bi bi-x-lg"></i>
                  </button>
              </div>
              <div class="modal-body rutas-modal__body--scroll">
                  <div v-if="historialDetalleCargando" class="rutas-modal-loading">
                      <div class="spinner-border" role="status"><span class="visually-hidden">Cargando…</span></div>
                  </div>
                  <template v-else-if="historialEjecucionDetalle && historialEjecucionDetalle.ejecucion">
                      <dl class="row rutas-modal-meta mb-3">
                          <dt class="col-sm-3">Hoja de ruta</dt>
                          <dd class="col-sm-9">{{ historialEjecucionDetalle.ejecucion.ruta_nombre }}</dd>
                          <dt class="col-sm-3">Cuadrilla</dt>
                          <dd class="col-sm-9">{{ historialEjecucionDetalle.ejecucion.cuadrilla_nombre || '—' }}</dd>
                          <dt class="col-sm-3">Inicio</dt>
                          <dd class="col-sm-9">{{ historialEjecucionDetalle.ejecucion.inicio_at || '—' }}</dd>
                          <dt class="col-sm-3">Fin</dt>
                          <dd class="col-sm-9">{{ historialEjecucionDetalle.ejecucion.fin_at || '—' }}</dd>
                      </dl>
                      <p class="rutas-modal-section-title">Registro de actividad</p>
                      <div class="rutas-modal-table-wrap">
                          <table class="table table-sm align-middle mb-0 rutas-modal-table">
                              <thead>
                                  <tr>
                                      <th style="width: 170px;">Horario</th>
                                      <th style="width: 220px;">Tipo</th>
                                      <th style="width: 120px;">Reclamo</th>
                                      <th style="width: 180px;">Usuario</th>
                                      <th>Detalle</th>
                                  </tr>
                              </thead>
                              <tbody>
                                  <tr v-for="item in lineaTiempoRegistroHistorialEjecucion" :key="item.clave">
                                      <td class="text-nowrap small">
                                          {{ item.tipo === 'evento'
                                              ? item.evento.ocurrido_at
                                              : formatearFecha(item.observacion.created_at) }}
                                      </td>
                                      <td class="small">
                                          <template v-if="item.tipo === 'evento'">
                                              {{ textoTipoEventoHistorial(item.evento.tipo) }}
                                          </template>
                                          <span v-else class="badge bg-secondary historial-mapa-tipo-badge">
                                              <i :class="esEntradaFotoBitacoraObra(item.observacion) ? 'bi bi-camera-fill' : 'bi bi-chat-square-text'"></i>
                                              {{ esEntradaFotoBitacoraObra(item.observacion) ? 'Foto' : 'Nota' }}
                                          </span>
                                      </td>
                                      <td class="small">
                                          {{ item.tipo === 'evento'
                                              ? etiquetaReclamoEventoHistorial(item.evento)
                                              : etiquetaReclamoEventoHistorial(item.observacion) }}
                                      </td>
                                      <td class="small">
                                          {{ item.tipo === 'evento'
                                              ? (item.evento.usuario_nombre || '—')
                                              : (item.observacion.usuario_nombre || '—') }}
                                      </td>
                                      <td class="small text-break">
                                          <template v-if="item.tipo === 'evento' && item.evento.tipo === 'reclamo_cambio_estado'">
                                              <span class="d-inline-flex flex-wrap align-items-center gap-1">
                                                  <span class="badge historial-mapa-estado-badge"
                                                        :style="{
                                                            backgroundColor: getColorEstado(item.evento.metadata?.estado_anterior),
                                                            color: colorTextoSobreEstadoReclamo(item.evento.metadata?.estado_anterior)
                                                        }">{{ item.evento.metadata?.estado_anterior || '—' }}</span>
                                                  <i class="bi bi-arrow-right text-muted px-1" aria-hidden="true"></i>
                                                  <span class="badge historial-mapa-estado-badge"
                                                        :style="{
                                                            backgroundColor: getColorEstado(item.evento.metadata?.estado_nuevo),
                                                            color: colorTextoSobreEstadoReclamo(item.evento.metadata?.estado_nuevo)
                                                        }">{{ item.evento.metadata?.estado_nuevo || '—' }}</span>
                                              </span>
                                          </template>
                                          <template v-else-if="item.tipo === 'evento'">
                                              {{ detalleLegibleEventoHistorial(item.evento) }}
                                          </template>
                                          <template v-else>
                                              <template v-if="esEntradaFotoBitacoraObra(item.observacion)">
                                                  <button type="button" class="bitacora-obra-foto-link d-inline-block me-2" @click="abrirModalFotoBitacoraObra(urlFotoBitacoraObra(item.observacion), item.observacion.texto || '')">
                                                      <img :src="urlFotoBitacoraObra(item.observacion)" class="bitacora-obra-foto-thumb" alt="Foto en obra" loading="lazy">
                                                  </button>
                                                  <span v-if="item.observacion.texto">{{ item.observacion.texto }}</span>
                                              </template>
                                              <template v-else>
                                                  {{ item.observacion.texto }}
                                              </template>
                                          </template>
                                      </td>
                                  </tr>
                                  <tr v-if="!lineaTiempoRegistroHistorialEjecucion.length">
                                      <td colspan="5" class="text-muted text-center">Sin actividad registrada.</td>
                                  </tr>
                              </tbody>
                          </table>
                      </div>
                  </template>
                  <p v-else class="rutas-modal-empty mb-0">No hay datos para mostrar.</p>
              </div>
              <div class="rutas-modal__footer rutas-modal__footer--end">
                  <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal mapa historial de ejecución -->
  <div class="modal fade" id="modalHistorialEjecucionMapa" tabindex="-1" aria-labelledby="modalHistorialEjecucionMapaLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered supervisor-ruta-detalle-modal-dialog">
          <div class="modal-content rutas-modal supervisor-ruta-detalle-modal">
              <div
                  class="supervisor-ruta-detalle-modal__franja-superior"
                  :style="{
                      backgroundColor: historialEjecucionMapa?.ejecucion?.ruta_color || '#6c757d',
                      color: textoSobreColorRuta(historialEjecucionMapa?.ejecucion?.ruta_color)
                  }"
              >
                  <div class="supervisor-ruta-detalle-modal__franja-inner">
                      <h5 class="mb-0 supervisor-ruta-detalle-modal__nombre-ruta" id="modalHistorialEjecucionMapaLabel">
                          {{ historialEjecucionMapa?.ejecucion?.ruta_nombre || 'Historial de ejecución' }}
                      </h5>
                      <button
                          type="button"
                          class="btn-close flex-shrink-0"
                          :class="{ 'btn-close-white': textoSobreColorRuta(historialEjecucionMapa?.ejecucion?.ruta_color) === '#fff' }"
                          data-bs-dismiss="modal"
                          aria-label="Cerrar"
                      ></button>
                  </div>
              </div>
              <div class="modal-body p-0 supervisor-ruta-detalle__cuerpo">
                  <div v-if="historialMapaCargando" class="rutas-modal-loading py-5">
                      <div class="spinner-border" role="status"><span class="visually-hidden">Cargando…</span></div>
                  </div>
                  <div v-else-if="historialEjecucionMapa && historialEjecucionMapa.ejecucion" class="supervisor-ruta-detalle-layout">
                      <aside class="supervisor-ruta-detalle-panel-izq">
                          <div class="ruta-detalle-encabezado supervisor-ruta-detalle-panel-izq__bloque supervisor-ruta-detalle-panel-izq__bloque--overlay">
                              <p class="supervisor-ruta-detalle-panel-izq__subtitulo mb-0">Recorrido histórico</p>
                              <div class="ruta-detalle-meta-fila">
                                  <span class="ruta-detalle-pill w-100">
                                      <i class="bi bi-clipboard-data"></i>
                                      <strong>{{ historialEjecucionMapa.ejecucion.cantidadReclamos || 0 }}</strong> reclamos
                                  </span>
                              </div>
                              <div class="ruta-detalle-meta-fila">
                                  <span class="ruta-detalle-pill w-100">
                                      <i class="bi bi-people-fill"></i>
                                      <strong>{{ historialEjecucionMapa.ejecucion.cuadrilla_nombre || '—' }}</strong>
                                  </span>
                              </div>
                              <div class="ruta-detalle-meta-fila small text-muted">
                                  <div><i class="bi bi-play-circle"></i> {{ formatearFecha(historialEjecucionMapa.ejecucion.inicio_at) }}</div>
                                  <div class="mt-1"><i class="bi bi-stop-circle"></i> {{ formatearFecha(historialEjecucionMapa.ejecucion.fin_at) }}</div>
                              </div>
                              <div class="supervisor-ruta-detalle-panel-izq__acciones d-flex flex-column gap-2">
                                  <button type="button" class="rutas-btn rutas-btn--sm rutas-btn--outline w-100" @click="abrirRegistroDesdeHistorialMapa">
                                      <i class="bi bi-list-ul"></i> Ver registro
                                  </button>
                              </div>
                          </div>
                      </aside>
                      <div class="supervisor-ruta-detalle-panel-der">
                          <div class="supervisor-ruta-detalle-vista-contenido">
                              <div v-show="modoVistaHistorialMapa === 'mapa'" class="supervisor-ruta-detalle-vista-mapa">
                                  <div id="mapaHistorialEjecucion" v-show="proveedorMapaHistorial === 'google'" class="supervisor-ruta-detalle-modal__mapa"></div>
                                  <div id="mapaHistorialEjecucionMapbox" v-show="proveedorMapaHistorial === 'mapbox'" class="supervisor-ruta-detalle-modal__mapa"></div>
                                  <div class="crear-ruta-vista-controles">
                                      <button type="button" class="rutas-btn rutas-btn--sm rutas-btn--success" @click="alternarProveedorHistorialMapa">
                                          <i class="bi bi-arrow-repeat"></i>
                                          {{ proveedorMapaHistorial === 'google' ? 'Mapbox' : 'Google Maps' }}
                                      </button>
                                  </div>
                                  <div class="crear-ruta-vista-controles crear-ruta-vista-controles--derecha">
                                      <button type="button" class="rutas-btn rutas-btn--sm rutas-btn--outline" @click="modoVistaHistorialMapa = 'lista'">
                                          <i class="bi bi-list-ul"></i> Ver lista
                                      </button>
                                  </div>
                              </div>
                              <div v-show="modoVistaHistorialMapa === 'lista'" class="supervisor-ruta-detalle-vista-lista">
                                  <div class="crear-ruta-vista-controles crear-ruta-vista-controles--derecha">
                                      <button type="button" class="rutas-btn rutas-btn--sm rutas-btn--outline" @click="cambiarModoVistaHistorialMapa('mapa')">
                                          <i class="bi bi-map"></i> Ver mapa
                                      </button>
                                  </div>
                                  <p v-if="!(historialEjecucionMapa.reclamos && historialEjecucionMapa.reclamos.length)" class="text-muted text-center py-4 mb-0">
                                      Sin reclamos en esta ejecución.
                                  </p>
                                  <div v-else class="ruta-secuencia-container supervisor-detalle-secuencia">
                                      <div v-for="(parada, idx) in paradasListaHistorial"
                                           :key="'hist-mapa-parada-' + parada.clave + '-' + idx"
                                           class="ruta-secuencia-item">
                                          <div class="card reclamo-card reclamo-card-secuencia"
                                               :class="getCardClassCrearRuta(reclamoParaVistaHistorialEjecucion(reclamoActivoEnParadaListaHistorial(parada)))">
                                              <div class="card-body ruta-secuencia-cardbody">
                                                  <div class="ruta-secuencia-fila">
                                                      <button v-if="parada.reclamos.length > 1"
                                                              type="button"
                                                              class="ruta-secuencia-grupo-badge"
                                                              :style="{ backgroundColor: colorEstadoReclamoHistorialEjecucion(reclamoActivoEnParadaListaHistorial(parada)) }"
                                                              :title="parada.reclamos.length + ' reclamos en este domicilio'"
                                                              @click.stop="navegarReclamoEnParadaListaHistorial(parada, 1)">
                                                          {{ parada.reclamos.length }}
                                                      </button>
                                                      <span v-else
                                                            class="ruta-secuencia-motivo-icon"
                                                            :style="{ backgroundColor: colorEstadoReclamoHistorialEjecucion(parada.reclamos[0]) }"
                                                            :title="parada.reclamos[0].municipalidad_motivo || 'Motivo no especificado'">
                                                          {{ iconoMotivoReclamo(parada.reclamos[0].municipalidad_motivo) }}
                                                      </span>
                                                      <div class="ruta-secuencia-main"
                                                           @click="seleccionarReclamoHistorialMapa(reclamoActivoEnParadaListaHistorial(parada))"
                                                           role="button"
                                                           tabindex="0">
                                                          <span class="ruta-secuencia-id">{{ reclamoActivoEnParadaListaHistorial(parada).municipalidad_id }}</span>
                                                          <span class="ruta-secuencia-domicilio">
                                                              <span class="ruta-secuencia-calle"
                                                                    :title="(reclamoActivoEnParadaListaHistorial(parada).municipalidad_domicilio || '') + ' ' + (reclamoActivoEnParadaListaHistorial(parada).municipalidad_numeroDomicilio || '')">
                                                                  <i class="bi bi-geo-alt ruta-secuencia-calle-ico" aria-hidden="true"></i>
                                                                  {{ reclamoActivoEnParadaListaHistorial(parada).municipalidad_domicilio }}
                                                                  {{ reclamoActivoEnParadaListaHistorial(parada).municipalidad_numeroDomicilio }}
                                                              </span>
                                                              <span
                                                                  v-if="reclamoActivoEnParadaListaHistorial(parada).municipalidad_descripcion"
                                                                  class="ruta-secuencia-descripcion"
                                                                  :title="reclamoActivoEnParadaListaHistorial(parada).municipalidad_descripcion"
                                                              >
                                                                  {{ reclamoActivoEnParadaListaHistorial(parada).municipalidad_descripcion }}
                                                              </span>
                                                              <div v-if="parada.reclamos.length > 1"
                                                                   class="ruta-secuencia-grupo-nav"
                                                                   @click.stop>
                                                                  <button type="button"
                                                                          class="mapa-popup-nav mapa-popup-nav-prev"
                                                                          @click="navegarReclamoEnParadaListaHistorial(parada, -1)"
                                                                          aria-label="Reclamo anterior">
                                                                      <i class="bi bi-chevron-left"></i>
                                                                  </button>
                                                                  <span class="ruta-secuencia-grupo-contador">
                                                                      {{ indiceReclamoEnParadaListaHistorial(parada) + 1 }} de {{ parada.reclamos.length }}
                                                                  </span>
                                                                  <button type="button"
                                                                          class="mapa-popup-nav mapa-popup-nav-next"
                                                                          @click="navegarReclamoEnParadaListaHistorial(parada, 1)"
                                                                          aria-label="Siguiente reclamo">
                                                                      <i class="bi bi-chevron-right"></i>
                                                                  </button>
                                                              </div>
                                                          </span>
                                                      </div>
                                                      <div class="ruta-secuencia-toolbar" @click.stop>
                                                          <div
                                                              v-if="textoTiempoReparacionHistorialEjecucion(reclamoActivoEnParadaListaHistorial(parada))"
                                                              class="ruta-secuencia-toolbar__inicio"
                                                          >
                                                              <span
                                                                  class="ruta-secuencia-crono-reparacion badge font-monospace cronometro-badge-con-ico"
                                                                  :class="claseCronometroListaObraHistorial(reclamoActivoEnParadaListaHistorial(parada))"
                                                                  title="Tiempo en obra (incluye hojas anteriores si correspondía)"
                                                              ><i class="bi bi-truck cronometro-badge-ico" aria-hidden="true"></i><span class="cronometro-badge-txt">{{ textoTiempoReparacionHistorialEjecucion(reclamoActivoEnParadaListaHistorial(parada)) }}</span></span>
                                                          </div>
                                                          <div class="ruta-secuencia-toolbar__paneles">
                                                              <button
                                                                  type="button"
                                                                  class="btn btn-sm btn-outline-secondary ruta-secuencia-btn-material btn-con-badge-obs"
                                                                  :title="cantidadMaterialesReclamoHistorial(reclamoActivoEnParadaListaHistorial(parada)) > 0
                                                                      ? 'Materiales utilizados (' + cantidadMaterialesReclamoHistorial(reclamoActivoEnParadaListaHistorial(parada)) + ')'
                                                                      : 'Materiales utilizados'"
                                                                  @click="abrirModalMaterialesSupervisor(reclamoActivoEnParadaListaHistorial(parada))"
                                                              >
                                                                  <i class="bi bi-box-seam"></i>
                                                                  <span
                                                                      class="btn-obs-ejecucion-count"
                                                                      :class="{ 'btn-obs-ejecucion-count--oculto': cantidadMaterialesReclamoHistorial(reclamoActivoEnParadaListaHistorial(parada)) < 1 }"
                                                                  >{{ textoObservacionesEjecucionBadge(cantidadMaterialesReclamoHistorial(reclamoActivoEnParadaListaHistorial(parada))) || '0' }}</span>
                                                              </button>
                                                          </div>
                                                      </div>
                                                  </div>
                                                  <div v-if="lineaTiempoActividadReclamoHistorialEjecucion(reclamoActivoEnParadaListaHistorial(parada)).length"
                                                       class="historial-mapa-linea-tiempo mt-2 pt-2 border-top">
                                                      <p class="small text-muted mb-1"><i class="bi bi-clock-history"></i> Actividad en la ejecución</p>
                                                      <ul class="list-unstyled mb-0">
                                                          <li v-for="item in lineaTiempoActividadReclamoHistorialEjecucion(reclamoActivoEnParadaListaHistorial(parada))"
                                                              :key="'hist-lt-' + item.clave"
                                                              :class="item.tipo === 'cambio_estado' ? 'bitacora-obra-evento-estado bitacora-obra-evento-estado--compact mb-2' : 'bitacora-obra-msg bitacora-obra-msg--compact mb-2'">
                                                              <template v-if="item.tipo === 'cambio_estado'">
                                                                  <div class="bitacora-obra-evento">
                                                                      <div class="bitacora-obra-evento__fila">
                                                                          <div class="bitacora-obra-evento__cuerpo">
                                                                              <div class="bitacora-obra-evento__meta">
                                                                                  <span class="bitacora-obra-evento__usuario">{{ item.cambio.usuario_nombre || '—' }}</span>
                                                                                  <span class="bitacora-obra-evento__etiqueta">Estado</span>
                                                                                  <template v-if="item.cambio.ruta_nombre">
                                                                                      <span class="bitacora-obra-msg__sep" aria-hidden="true">·</span>
                                                                                      <span class="bitacora-obra-evento__ruta" :style="{ color: item.cambio.ruta_color || '#6c757d' }">
                                                                                          <svg class="bitacora-obra-evento__ruta-ico cronometro-badge-ico cronometro-badge-ico-ruta" viewBox="0 0 20 12" aria-hidden="true" focusable="false"><path d="M1 9.5 H6 V2.5 H14 V9.5 H19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                                                          <span>{{ item.cambio.ruta_nombre }}</span>
                                                                                      </span>
                                                                                  </template>
                                                                              </div>
                                                                              <div class="bitacora-obra-evento__transicion historial-mapa-cambio-estado d-flex flex-wrap align-items-center gap-1">
                                                                                  <span class="badge historial-mapa-estado-badge"
                                                                                        :style="{
                                                                                            backgroundColor: getColorEstado(item.cambio.estado_anterior),
                                                                                            color: colorTextoSobreEstadoReclamo(item.cambio.estado_anterior)
                                                                                        }">{{ item.cambio.estado_anterior }}</span>
                                                                                  <i class="bi bi-arrow-right small text-muted px-1" aria-hidden="true"></i>
                                                                                  <span class="badge historial-mapa-estado-badge"
                                                                                        :style="{
                                                                                            backgroundColor: getColorEstado(item.cambio.estado_nuevo),
                                                                                            color: colorTextoSobreEstadoReclamo(item.cambio.estado_nuevo)
                                                                                        }">{{ item.cambio.estado_nuevo }}</span>
                                                                              </div>
                                                                              <time class="bitacora-obra-evento__hora">{{ formatearFecha(item.cambio.ocurrido_at) }}</time>
                                                                          </div>
                                                                          <span class="bitacora-obra-evento__ico" aria-hidden="true"><i class="bi bi-arrow-left-right"></i></span>
                                                                      </div>
                                                                  </div>
                                                              </template>
                                                              <template v-else>
                                                                  <div class="bitacora-obra-msg__layout">
                                                                      <div class="bitacora-obra-msg__avatar-col" aria-hidden="true">
                                                                          <img
                                                                              v-if="item.observacion.usuario_foto_perfil"
                                                                              class="bitacora-obra-msg__avatar bitacora-obra-msg__avatar--img"
                                                                              :src="urlFotoOperario(item.observacion.usuario_foto_perfil)"
                                                                              :alt="item.observacion.usuario_nombre || 'Usuario'"
                                                                              loading="lazy"
                                                                          >
                                                                          <span
                                                                              v-else
                                                                              class="bitacora-obra-msg__avatar bitacora-obra-msg__avatar--iniciales"
                                                                              :style="{ backgroundColor: colorAvatarOperario(item.observacion.usuario_nombre) }"
                                                                          >{{ inicialesOperario(item.observacion.usuario_nombre) }}</span>
                                                                      </div>
                                                                      <div class="bitacora-obra-msg__stack">
                                                                          <div class="bitacora-obra-msg__encabezado">
                                                                              <span class="bitacora-obra-msg__usuario">{{ item.observacion.usuario_nombre || '—' }}</span>
                                                                              <span class="bitacora-obra-msg__sep" aria-hidden="true">·</span>
                                                                              <span class="bitacora-obra-msg__tipo">
                                                                                  <i :class="esEntradaFotoBitacoraObra(item.observacion) ? 'bi bi-camera-fill' : 'bi bi-chat-left-text'" aria-hidden="true"></i>
                                                                                  {{ esEntradaFotoBitacoraObra(item.observacion) ? 'Foto' : 'Nota' }}
                                                                              </span>
                                                                          </div>
                                                                          <div class="bitacora-obra-msg__bubble">
                                                                              <div class="bitacora-obra-msg__contenido">
                                                                                  <template v-if="esEntradaFotoBitacoraObra(item.observacion)">
                                                                                      <button type="button" class="bitacora-obra-foto-link d-inline-block" @click="abrirModalFotoBitacoraObra(urlFotoBitacoraObra(item.observacion), item.observacion.texto || '')">
                                                                                          <img :src="urlFotoBitacoraObra(item.observacion)" class="bitacora-obra-foto-thumb" alt="Foto en obra" loading="lazy">
                                                                                      </button>
                                                                                      <p v-if="item.observacion.texto" class="mb-0 mt-2 text-break small">{{ item.observacion.texto }}</p>
                                                                                  </template>
                                                                                  <p v-else class="mb-0 text-break small">{{ item.observacion.texto }}</p>
                                                                              </div>
                                                                              <time class="bitacora-obra-msg__hora">{{ formatearFecha(item.observacion.created_at) }}</time>
                                                                          </div>
                                                                      </div>
                                                                  </div>
                                                              </template>
                                                          </li>
                                                      </ul>
                                                  </div>
                                              </div>
                                          </div>
                                          <div v-if="idx < paradasListaHistorial.length - 1" class="ruta-secuencia-flecha">
                                              <i class="bi bi-arrow-down"></i>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
                  <p v-else class="rutas-modal-empty p-4 mb-0">No hay datos para mostrar.</p>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal para crear nueva hoja de ruta automática -->
  <div class="modal fade" id="modalCrearRuta" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered" :class="vistaPrevia.activa ? 'modal-xl' : 'crear-ruta-modal-dialog'">
          <div class="modal-content rutas-modal">
              <div class="rutas-modal__header">
                  <div class="rutas-modal__title">
                      <span class="rutas-modal__icon"><i class="bi bi-plus-lg"></i></span>
                      <h5>
                          Crear hoja de ruta automática
                      <button v-if="vistaPrevia.activa" 
                              type="button" 
                                  class="rutas-btn rutas-btn--sm rutas-btn--outline ms-2"
                              data-bs-toggle="popover"
                              data-bs-placement="bottom"
                              data-bs-html="true"
                              :data-bs-content="`
                                  <div class='text-start'>
                                          <p class='mb-0'><small><strong>Reclamos:</strong> ${vistaPrevia.rutaOptimizada.length}</small></p>
                                  </div>
                              `">
                          <i class="bi bi-info-circle"></i>
                      </button>
                  </h5>
              </div>
                  <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" @click="resetearModal" aria-label="Cerrar">
                      <i class="bi bi-x-lg"></i>
                  </button>
                              </div>
              <div class="modal-body">
                  <!-- PASO 1: Color y cantidad de reclamos -->
                  <div v-if="!vistaPrevia.activa" class="crear-ruta-form">
                      <div class="crear-ruta-form__field">
                          <label for="colorRuta">Color de la ruta</label>
                          <div class="crear-ruta-color">
                              <div class="crear-ruta-color__swatches">
                                  <button
                                      v-for="col in coloresDisponiblesRuta"
                                      :key="'ruta-color-' + col"
                                      type="button"
                                      class="crear-ruta-color__swatch"
                                      :class="{ 'is-selected': (nuevaRuta.color || '').toLowerCase() === col.toLowerCase() }"
                                      :style="{ background: col }"
                                      :title="col"
                                      @click="nuevaRuta.color = col">
                                  </button>
                                  <label class="crear-ruta-color__custom" for="colorRuta" title="Color personalizado">
                                      <i class="bi bi-eyedropper"></i>
                                      <input type="color"
                                             id="colorRuta"
                                             class="crear-ruta-color__input"
                                             v-model="nuevaRuta.color"
                                             aria-label="Color personalizado de la ruta">
                                  </label>
                                  </div>
                              <div class="crear-ruta-color__preview" aria-hidden="true">
                                  <svg class="crear-ruta-color__trazo" viewBox="0 0 120 48">
                                      <path class="crear-ruta-color__trazo-fondo"
                                            d="M10 36 L42 36 L42 14 L72 14 L72 30 L110 30"
                                            fill="none"
                                            stroke="#ffffff"
                                            stroke-width="9"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            opacity="0.95" />
                                      <path class="crear-ruta-color__trazo-linea"
                                            d="M10 36 L42 36 L42 14 L72 14 L72 30 L110 30"
                                            fill="none"
                                            :stroke="nuevaRuta.color"
                                            stroke-width="5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-opacity="0.9" />
                                  </svg>
                              </div>
                          </div>
                              </div>

                      <div class="crear-ruta-form__field">
                          <label for="cantidadReclamos">Cantidad de domicilios</label>
                          <input type="number"
                                 id="cantidadReclamos"
                                 class="form-control crear-ruta-form__input-cantidad"
                                         v-model.number="nuevaRuta.cantidadReclamos" 
                                         :min="1" 
                                         :max="reclamosDisponibles" 
                                 placeholder="Ej: 5"
                                         required>
                          <small class="text-muted">Disponibles: {{ reclamosDisponibles }}</small>
                      </div>
                              </div>

                  <!-- PASO 2: Vista Previa con Mapa / Lista -->
                  <div v-if="vistaPrevia.activa">
                      <!-- Panel cuadrillas (izq.) + mapa o lista (der.) -->
                      <div class="row crear-ruta-preview-layout">
                          <div class="col-md-3">
                              <div class="rutas-modal-panel h-100 crear-ruta-panel-cuadrillas">
                                  <div class="rutas-modal-panel__header">
                                      <span class="crear-ruta-cuadrillas-titulo"><i class="bi bi-people-fill"></i> Cuadrillas</span>
                                  </div>
                                  <div class="crear-ruta-cuadrillas-body">
                                  <div class="crear-ruta-cuadrillas-scroll">
                                      <article
                                          v-for="cuadrilla in cuadrillasDisponibles"
                                          :key="cuadrilla.id"
                                          class="crear-ruta-cuadrilla-card"
                                          :class="{
                                              'crear-ruta-cuadrilla-card--selected': String(cuadrillaSeleccionadaCrearRuta) === String(cuadrilla.id),
                                              'crear-ruta-cuadrilla-card--detalle-abierto': cuadrillaDetalleExpandida(cuadrilla.id),
                                              'crear-ruta-cuadrilla-card--ocupada': !cuadrillaEsAsignable(cuadrilla)
                                          }"
                                      >
                                          <div class="crear-ruta-cuadrilla-card__surface">
                                              <button
                                                  type="button"
                                                  class="crear-ruta-cuadrilla-card__select"
                                                  :disabled="!cuadrillaEsAsignable(cuadrilla)"
                                                  :title="mensajeCuadrillaNoAsignable(cuadrilla) || 'Asignar hoja a esta cuadrilla'"
                                                  @click="seleccionarCuadrillaCrearRuta(cuadrilla.id)"
                                              >
                                                  <div class="crear-ruta-cuadrilla-card__head">
                                                      <span
                                                          v-if="String(cuadrillaSeleccionadaCrearRuta) === String(cuadrilla.id)"
                                                          class="crear-ruta-cuadrilla-card__check"
                                                          aria-hidden="true"
                                                      >
                                                          <i class="bi bi-check-circle-fill"></i>
                                                      </span>
                                                      <h4 class="crear-ruta-cuadrilla-card__name">{{ cuadrilla.nombre }}</h4>
                              </div>

                                                  <div v-if="cuadrillaTieneOtraHojaAsignada(cuadrilla.id)" class="crear-ruta-cuadrilla-card__badge crear-ruta-cuadrilla-card__badge--ocupada">
                                                      <i class="bi bi-lock-fill"></i> Ocupada
                          </div>
                                                  <div v-else-if="!cuadrillaTieneOperarios(cuadrilla)" class="crear-ruta-cuadrilla-card__badge crear-ruta-cuadrilla-card__badge--ocupada">
                                                      <i class="bi bi-person-dash"></i> Sin operarios
                      </div>
                                                  <div v-else-if="!cuadrillaTieneGestion(cuadrilla)" class="crear-ruta-cuadrilla-card__badge crear-ruta-cuadrilla-card__badge--ocupada">
                                                      <i class="bi bi-shield-exclamation"></i> Sin gestión
                  </div>

                                                  <div v-if="cuadrilla.operarios && cuadrilla.operarios.length" class="crear-ruta-cuadrilla-card__team">
                                                      <div class="crear-ruta-cuadrilla-card__avatars">
                                                          <template v-for="op in cuadrilla.operarios.slice(0, 4)" :key="op.id">
                                                              <img
                                                                  v-if="op.foto_perfil"
                                                                  class="crc-avatar crc-avatar--img"
                                                                  :class="{ 'crc-avatar--jefe': Number(op.es_jefe) === 1 }"
                                                                  :src="urlFotoOperario(op.foto_perfil)"
                                                                  :alt="op.nombre"
                                                                  :title="op.nombre + (Number(op.es_jefe) === 1 ? ' (Gestión)' : '')"
                                                              >
                                                              <span
                                                                  v-else
                                                                  class="crc-avatar"
                                                                  :class="{ 'crc-avatar--jefe': Number(op.es_jefe) === 1 }"
                                                                  :style="{ backgroundColor: colorAvatarOperario(op.nombre) }"
                                                                  :title="op.nombre + (Number(op.es_jefe) === 1 ? ' (Gestión)' : '')"
                                                              >
                                                                  {{ inicialesOperario(op.nombre) }}
                                                              </span>
                                                          </template>
                                                          <span v-if="cuadrilla.operarios.length > 4" class="crc-avatar crc-avatar--more">
                                                              +{{ cuadrilla.operarios.length - 4 }}
                                                          </span>
                      </div>
                                                      <span class="crear-ruta-cuadrilla-card__count">
                                                          {{ cuadrilla.operarios.length }}
                                                          {{ cuadrilla.operarios.length === 1 ? 'operario' : 'operarios' }}
                                                      </span>
                                                  </div>
                                                  <p v-else class="crear-ruta-cuadrilla-card__empty-team">
                                                      <i class="bi bi-person-dash"></i> Sin operarios asignados
                                                  </p>
                                              </button>

                                              <button
                                                  type="button"
                                                  class="crear-ruta-cuadrilla-card__toggle"
                                                  :aria-expanded="cuadrillaDetalleExpandida(cuadrilla.id)"
                                                  :title="cuadrillaDetalleExpandida(cuadrilla.id) ? 'Ocultar detalle' : 'Ver detalle'"
                                                  @click="toggleCuadrillaDetalleCrearRuta(cuadrilla.id, $event)"
                                              >
                                                  <i class="bi" :class="cuadrillaDetalleExpandida(cuadrilla.id) ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                              </button>
                                  </div>
                                      </article>
                                      <div v-if="!cuadrillasDisponibles.length" class="crear-ruta-cuadrillas-vacio">
                                          <i class="bi bi-people"></i>
                                          <p>No hay cuadrillas disponibles.</p>
                                                  </div>
                                              </div>

                                  <div v-if="cuadrillaDetalleAbierta" class="crear-ruta-cuadrilla-overlay">
                                      <button
                                          type="button"
                                          class="crear-ruta-cuadrilla-overlay__backdrop"
                                          aria-label="Cerrar detalle"
                                          @click="cerrarDetalleCuadrillaCrearRuta"
                                      ></button>
                                      <div class="crear-ruta-cuadrilla-overlay__panel" role="dialog" aria-modal="true">
                                          <div class="crear-ruta-cuadrilla-overlay__header">
                                              <h4 class="crear-ruta-cuadrilla-overlay__title">{{ cuadrillaDetalleAbierta.nombre }}</h4>
                                              <button
                                                  type="button"
                                                  class="crear-ruta-cuadrilla-overlay__close"
                                                  aria-label="Cerrar"
                                                  @click="cerrarDetalleCuadrillaCrearRuta"
                                              >
                                                  <i class="bi bi-x-lg"></i>
                                                  </button>
                                              </div>
                                          <div class="crear-ruta-cuadrilla-overlay__body">
                                              <p v-if="cuadrillaTieneOtraHojaAsignada(cuadrillaDetalleAbierta.id)" class="crear-ruta-cuadrilla-card__alert">
                                                  <i class="bi bi-exclamation-circle-fill"></i>
                                                  Hoja activa: {{ hojaActivaDeCuadrilla(cuadrillaDetalleAbierta.id)?.nombre }}
                                              </p>
                                              <p v-if="cuadrillaDetalleAbierta.descripcion" class="crear-ruta-cuadrilla-card__desc">{{ cuadrillaDetalleAbierta.descripcion }}</p>
                                              <div v-if="cuadrillaDetalleAbierta.operarios && cuadrillaDetalleAbierta.operarios.length" class="crear-ruta-cuadrilla-card__members">
                                                  <div
                                                      v-for="op in cuadrillaDetalleAbierta.operarios"
                                                      :key="'det-' + op.id"
                                                      class="crear-ruta-cuadrilla-card__member"
                                                  >
                                                      <img
                                                          v-if="op.foto_perfil"
                                                          class="crc-avatar crc-avatar--img crc-avatar--row"
                                                          :src="urlFotoOperario(op.foto_perfil)"
                                                          :alt="op.nombre"
                                                      >
                                                      <span
                                                          v-else
                                                          class="crc-avatar crc-avatar--row"
                                                          :style="{ backgroundColor: colorAvatarOperario(op.nombre) }"
                                                      >
                                                          {{ inicialesOperario(op.nombre) }}
                                                      </span>
                                                      <span class="crear-ruta-cuadrilla-card__member-name">{{ op.nombre }}</span>
                                                      <span v-if="Number(op.es_jefe) === 1" class="crear-ruta-cuadrilla-card__member-role">Gestión</span>
                                                  </div>
                                              </div>
                                              <p v-else class="crear-ruta-cuadrilla-card__empty-team">
                                                  <i class="bi bi-person-dash"></i> Sin operarios asignados
                                              </p>
                                          </div>
                                          </div>
                                      </div>
                                  </div>
                              </div>
                          </div>

                          <div class="col-md-9">
                              <div class="rutas-modal-panel h-100 crear-ruta-panel-mapa">
                                  <div class="rutas-modal-panel__body--flush rutas-modal-map-wrap">
                                      <div id="mapaCrearRuta" v-show="proveedorMapaVistaPrevia === 'google'" class="rutas-modal-map"></div>
                                      <div id="mapaCrearRutaMapbox" v-show="proveedorMapaVistaPrevia === 'mapbox'" class="rutas-modal-map"></div>

                                      <div class="crear-ruta-vista-controles">
                                          <button type="button"
                                                  class="rutas-btn rutas-btn--sm rutas-btn--success"
                                                  @click="alternarProveedorVistaPrevia">
                                              <i class="bi bi-arrow-repeat"></i>
                                              {{ proveedorMapaVistaPrevia === 'google' ? 'Mapbox' : 'Google Maps' }}
                                      </button>
                                  </div>
                                      <div class="crear-ruta-vista-controles crear-ruta-vista-controles--derecha">
                                          <button type="button"
                                                  class="rutas-btn rutas-btn--sm rutas-btn--outline"
                                                  @click.stop="mostrarListaRutaVistaPrevia = !mostrarListaRutaVistaPrevia"
                                                  title="Mostrar u ocultar orden de la ruta">
                                              <i class="bi bi-list-ul"></i> Ver lista
                                          </button>
                                  </div>

                                      <div v-show="mostrarListaRutaVistaPrevia" class="crear-ruta-lista-overlay">
                                          <div class="crear-ruta-lista-overlay__header">
                                              <span>Orden de la ruta</span>
                                              
                                              <button type="button"
                                                      class="crear-ruta-lista-overlay__close"
                                                      @click="mostrarListaRutaVistaPrevia = false"
                                                      aria-label="Cerrar">
                                                  <i class="bi bi-x-lg"></i>
                                              </button>
                              </div>
                                          <div class="crear-ruta-lista-overlay__body">
                                              <p v-if="!vistaPrevia.rutaOptimizada.length" class="crear-ruta-lista-overlay__empty">
                                                  No hay reclamos en la ruta.
                                              </p>
                                              <div v-for="(parada, idx) in paradasListaVistaPrevia"
                                                   :key="'overlay-parada-' + parada.clave + '-' + idx"
                                                   class="crear-ruta-lista-overlay__item">
                                                  <span class="crear-ruta-lista-overlay__pos">{{ parada.paradaNumero }}</span>
                                                  <span class="crear-ruta-lista-overlay__icon-wrap">
                                                      <button v-if="parada.reclamos.length > 1"
                                                              type="button"
                                                              class="crear-ruta-lista-overlay__icon crear-ruta-lista-overlay__icon--grupo"
                                                              :style="{ backgroundColor: getColorEstado(reclamoActivoEnParadaLista(parada).municipalidad_estado) }"
                                                              :title="parada.reclamos.length + ' reclamos en este domicilio'"
                                                              @click="navegarReclamoEnParadaLista(parada, 1)">
                                                          {{ parada.reclamos.length }}
                                                      </button>
                                                      <span v-else
                                                            class="crear-ruta-lista-overlay__icon"
                                                            :style="{ backgroundColor: getColorEstado(parada.reclamos[0].municipalidad_estado) }"
                                                            :title="parada.reclamos[0].municipalidad_motivo || 'Motivo no especificado'">
                                                          {{ iconoMotivoReclamo(parada.reclamos[0].municipalidad_motivo) }}
                                                      </span>
                                                      <span v-if="marcadorGrupoTienePrioridadAlta(parada.reclamos)"
                                                            class="mapa-prioridad-alta-badge crear-ruta-lista-overlay__prioridad-badge"
                                                            aria-label="Prioridad alta">!</span>
                                                  </span>
                                                  <span class="crear-ruta-lista-overlay__text">
                                                      <div v-if="parada.reclamos.length > 1"
                                                           class="crear-ruta-lista-overlay__grupo-nav">
                                                          <button type="button"
                                                                  class="mapa-popup-nav mapa-popup-nav-prev"
                                                                  @click="navegarReclamoEnParadaLista(parada, -1)"
                                                                  aria-label="Reclamo anterior">
                                                              <i class="bi bi-chevron-left"></i>
                                                          </button>
                                                          <span class="crear-ruta-lista-overlay__grupo-contador">
                                                              {{ indiceReclamoEnParadaLista(parada) + 1 }} de {{ parada.reclamos.length }} en este domicilio
                                                          </span>
                                                          <button type="button"
                                                                  class="mapa-popup-nav mapa-popup-nav-next"
                                                                  @click="navegarReclamoEnParadaLista(parada, 1)"
                                                                  aria-label="Siguiente reclamo">
                                                              <i class="bi bi-chevron-right"></i>
                                                          </button>
                          </div>
                                                      <strong>#{{ reclamoActivoEnParadaLista(parada).municipalidad_id }}</strong>
                                                      <small>{{ reclamoActivoEnParadaLista(parada).municipalidad_domicilio || 'Sin domicilio' }} {{ reclamoActivoEnParadaLista(parada).municipalidad_numeroDomicilio || '' }}</small>
                                                  </span>
                                                  <div v-if="modoEdicion" class="crear-ruta-lista-overlay__actions" @click.stop>
                                                      <button type="button"
                                                              class="rutas-btn rutas-btn--sm rutas-btn--outline"
                                                              @click="moverParadaArriba(idx)"
                                                              :disabled="idx === 0"
                                                              title="Mover parada arriba">
                                                          <i class="bi bi-arrow-up"></i>
                                                      </button>
                                                      <button type="button"
                                                              class="rutas-btn rutas-btn--sm rutas-btn--outline"
                                                              @click="moverParadaAbajo(idx)"
                                                              :disabled="idx === paradasListaVistaPrevia.length - 1"
                                                              title="Mover parada abajo">
                                                          <i class="bi bi-arrow-down"></i>
                                                      </button>
                                                      <button type="button"
                                                              class="rutas-btn rutas-btn--sm rutas-btn--danger"
                                                              @click="eliminarParadaDeRuta(idx)"
                                                              title="Eliminar parada de la ruta">
                                                          <i class="bi bi-trash"></i>
                                                      </button>
                      </div>
                  </div>
              </div>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
              <div class="rutas-modal__footer rutas-modal__footer--end">
                  <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal" @click="resetearModal">
                      Cancelar
                  </button>
                  
                  <!-- Botón para generar vista previa (solo cuando no está activa) -->
                  <button v-if="!vistaPrevia.activa" 
                          type="button" 
                          class="rutas-btn" 
                          @click="mostrarVistaPrevia" 
                          :disabled="!puedeVerVistaPrevia">
                      <i class="bi bi-eye"></i> Generar vista previa
                  </button>
                  
                  <!-- Botón para volver a configurar (cuando vista previa está activa) 
                  <button v-if="vistaPrevia.activa && !modoEdicion" 
                          type="button" 
                          class="btn btn-outline-secondary" 
                          @click="volverAConfigurar">
                      <i class="bi bi-arrow-left"></i> Cambiar Cantidad
                  </button-->
                  
                  <!-- Botón Editar Ruta (solo en vista previa, no en modo edición) -->
                  <button v-if="vistaPrevia.activa && !modoEdicion" 
                          type="button" 
                          class="rutas-btn rutas-btn--outline" 
                          @click="activarModoEdicion">
                      <i class="bi bi-pencil"></i> Editar hoja de ruta
                  </button>
                  
                  <!-- Botón Cancelar Edición (solo cuando está en modo edición) -->
                  <button v-if="modoEdicion" 
                          type="button" 
                          class="rutas-btn rutas-btn--outline" 
                          @click="cancelarEdicion">
                      <i class="bi bi-x-circle"></i> Cancelar edición
                  </button>
                  
                  <!-- Botón Crear Ruta (solo cuando vista previa está activa) -->
                  <button v-if="vistaPrevia.activa" 
                          type="button" 
                          class="rutas-btn rutas-btn--success" 
                          @click="crearRutaAutomatica" 
                          :disabled="!puedeGenerarRuta">
                      <i class="bi bi-check-lg"></i> {{ modoEdicion ? 'Crear ruta personalizada' : 'Crear ruta automática' }}
                  </button>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal para ver hoja de ruta -->
  <div class="modal fade" id="modalVerRuta" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-xl modal-dialog-centered">
          <div class="modal-content rutas-modal">
              <div class="rutas-modal__header">
                  <div class="rutas-modal__title">
                      <span class="rutas-modal__icon"><i class="bi bi-map"></i></span>
                      <h5>
                          {{ rutaVisualizando.nombre || 'Hoja de ruta' }}
                          <span v-if="rutaVisualizando.asignada == 1" class="badge bg-success">Asignada</span>
                          <span v-else class="badge bg-secondary">No asignada</span>
                          <button type="button" 
                                  class="rutas-btn rutas-btn--sm rutas-btn--outline"
                                  data-bs-toggle="popover"
                                  data-bs-placement="bottom"
                                  data-bs-html="true"
                                  :data-bs-content="`
                                      <div class='text-start'>
                                          <p class='mb-1'><small><strong>ID:</strong> ${rutaVisualizando.id}</small></p>
                                          <p class='mb-1'><small><strong>Reclamos:</strong> ${rutaVisualizando.cantidadReclamos}</small></p>
                                          <p class='mb-1'><small><strong>Tiempo:</strong> ${rutaVisualizando.tiempoEstimado}</small></p>
                                          <p class='mb-1'><small><strong>Fecha:</strong> ${formatearFecha(rutaVisualizando.fecha)}</small></p>
                                          <p class='mb-0'><small><strong>Asignación:</strong> ${rutaVisualizando.asignada == 1 ? 'Asignada' : 'No asignada'}</small></p>
                                      </div>
                                  `">
                              <i class="bi bi-info-circle"></i>
                          </button>
                      </h5>
                  </div>
                  <div class="rutas-modal__header-actions">
                      <button type="button" 
                              class="rutas-btn rutas-btn--sm rutas-btn--danger"
                              :disabled="!puedeEliminarHojaRuta(rutaVisualizando)"
                              :title="puedeEliminarHojaRuta(rutaVisualizando) ? 'Eliminar hoja de ruta' : motivoNoPuedeEliminarHojaRuta(rutaVisualizando)"
                              @click="eliminarRutaDesdeVisualizacion(rutaVisualizando.id)">
                          <i class="bi bi-trash"></i> Eliminar hoja
                      </button>
                      <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" @click="cerrarVisualizacion" aria-label="Cerrar">
                          <i class="bi bi-x-lg"></i>
                      </button>
                  </div>
              </div>
              <div class="modal-body p-0">
                  <div class="rutas-modal-panel h-100 crear-ruta-panel-mapa ver-ruta-panel-mapa">
                      <div class="rutas-modal-panel__body--flush rutas-modal-map-wrap">
                          <div id="mapaVerRuta" v-show="proveedorMapaVisualizacion === 'google'" class="rutas-modal-map ver-ruta-modal-map"></div>
                          <div id="mapaVerRutaMapbox" v-show="proveedorMapaVisualizacion === 'mapbox'" class="rutas-modal-map ver-ruta-modal-map"></div>

                          <div class="crear-ruta-vista-controles">
                              <button type="button"
                                      class="rutas-btn rutas-btn--sm rutas-btn--success"
                                      @click="alternarProveedorVisualizacion">
                                  <i class="bi bi-arrow-repeat"></i>
                                  {{ proveedorMapaVisualizacion === 'google' ? 'Mapbox' : 'Google Maps' }}
                              </button>
                              </div>
                          <div class="crear-ruta-vista-controles crear-ruta-vista-controles--derecha">
                              <button type="button"
                                      class="rutas-btn rutas-btn--sm rutas-btn--outline"
                                      @click.stop="mostrarListaRutaVisualizacion = !mostrarListaRutaVisualizacion"
                                      title="Mostrar u ocultar orden de la ruta">
                                  <i class="bi bi-list-ul"></i> Ver lista
                              </button>
                      </div>

                          <div v-show="mostrarListaRutaVisualizacion" class="crear-ruta-lista-overlay">
                              <div class="crear-ruta-lista-overlay__header">
                                  <span>Orden de la ruta</span>
                                  <span class="crear-ruta-lista-overlay__badge">{{ paradasListaVisualizacion.length }}</span>
                                  <button type="button"
                                          class="crear-ruta-lista-overlay__close"
                                          @click="mostrarListaRutaVisualizacion = false"
                                          aria-label="Cerrar">
                                      <i class="bi bi-x-lg"></i>
                                  </button>
                              </div>
                              <div class="crear-ruta-lista-overlay__body">
                                  <p v-if="!reclamosRutaVisualizando.length" class="crear-ruta-lista-overlay__empty">
                                      No hay reclamos en la ruta.
                                  </p>
                                  <div v-for="(parada, idx) in paradasListaVisualizacion"
                                       :key="'ver-ruta-parada-' + parada.clave + '-' + idx"
                                       class="crear-ruta-lista-overlay__item crear-ruta-lista-overlay__item--clickable"
                                       @click="centrarEnReclamo(reclamoActivoEnParadaListaVisualizacion(parada))">
                                      <span class="crear-ruta-lista-overlay__pos">{{ parada.paradaNumero }}</span>
                                      <span class="crear-ruta-lista-overlay__icon-wrap">
                                          <button v-if="parada.reclamos.length > 1"
                                                  type="button"
                                                  class="crear-ruta-lista-overlay__icon crear-ruta-lista-overlay__icon--grupo"
                                                  :style="{ backgroundColor: getColorEstado(reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_estado) }"
                                                  :title="parada.reclamos.length + ' reclamos en este domicilio'"
                                                  @click.stop="navegarReclamoEnParadaListaVisualizacion(parada, 1)">
                                              {{ parada.reclamos.length }}
                                          </button>
                                          <span v-else
                                                class="crear-ruta-lista-overlay__icon"
                                                :style="{ backgroundColor: getColorEstado(parada.reclamos[0].municipalidad_estado) }"
                                                :title="parada.reclamos[0].municipalidad_motivo || 'Motivo no especificado'">
                                              {{ iconoMotivoReclamo(parada.reclamos[0].municipalidad_motivo) }}
                                          </span>
                                          <span v-if="marcadorGrupoTienePrioridadAlta(parada.reclamos)"
                                                class="mapa-prioridad-alta-badge crear-ruta-lista-overlay__prioridad-badge"
                                                aria-label="Prioridad alta">!</span>
                                      </span>
                                      <span class="crear-ruta-lista-overlay__text">
                                          <div v-if="parada.reclamos.length > 1"
                                               class="crear-ruta-lista-overlay__grupo-nav"
                                               @click.stop>
                                              <button type="button"
                                                      class="mapa-popup-nav mapa-popup-nav-prev"
                                                      @click="navegarReclamoEnParadaListaVisualizacion(parada, -1)"
                                                      aria-label="Reclamo anterior">
                                                  <i class="bi bi-chevron-left"></i>
                                              </button>
                                              <span class="crear-ruta-lista-overlay__grupo-contador">
                                                  {{ indiceReclamoEnParadaListaVisualizacion(parada) + 1 }} de {{ parada.reclamos.length }} en este domicilio
                                              </span>
                                              <button type="button"
                                                      class="mapa-popup-nav mapa-popup-nav-next"
                                                      @click="navegarReclamoEnParadaListaVisualizacion(parada, 1)"
                                                      aria-label="Siguiente reclamo">
                                                  <i class="bi bi-chevron-right"></i>
                                              </button>
                                          </div>
                                          <strong>#{{ reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_id }}</strong>
                                          <small>{{ reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_domicilio || 'Sin domicilio' }} {{ reclamoActivoEnParadaListaVisualizacion(parada).municipalidad_numeroDomicilio || '' }}</small>
                                      </span>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal para asignar ruta a cuadrilla -->
  <div class="modal fade" id="modalAsignarRuta" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered asignar-ruta-modal-dialog">
          <div class="modal-content rutas-modal asignar-ruta-modal">
              <div class="rutas-modal__header">
                  <div class="rutas-modal__title">
                      <span class="rutas-modal__icon"><i class="bi bi-people-fill"></i></span>
                      <h5>{{ rutaParaAsignar.asignada == 1 ? 'Cambiar cuadrilla' : 'Asignar cuadrilla' }}</h5>
              </div>
                  <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" @click="cerrarModalAsignar" aria-label="Cerrar">
                      <i class="bi bi-x-lg"></i>
                  </button>
                  </div>
              <div class="modal-body asignar-ruta-modal__body">
                  <div class="asignar-ruta-resumen"
                       :style="{ '--asignar-ruta-color': rutaParaAsignar.color || '#808080' }">
                      <span class="asignar-ruta-resumen__nombre">{{ rutaParaAsignar.nombre || 'Hoja de ruta' }}</span>
                      <span class="asignar-ruta-resumen__meta">
                          <span>{{ rutaParaAsignar.cantidadReclamos || 0 }} recl.</span>
                      </span>
                  </div>
                  
                  <p v-if="rutaParaAsignar.asignada == 1" class="asignar-ruta-actual mb-0">
                      Actual: <strong>{{ rutaParaAsignar.cuadrilla_nombre || '—' }}</strong>
                  </p>

                  <div class="asignar-ruta-cuadrillas">
                      <p v-if="!cuadrillasDisponibles.length" class="asignar-ruta-cuadrillas__vacio">
                          No hay cuadrillas disponibles.
                      </p>
                      <button
                          v-for="cuadrilla in cuadrillasDisponibles"
                          :key="'asignar-cuad-' + cuadrilla.id"
                          type="button"
                          class="asignar-ruta-cuadrilla-item"
                          :class="{
                              'asignar-ruta-cuadrilla-item--selected': String(cuadrillaSeleccionadaParaAsignar) === String(cuadrilla.id),
                              'asignar-ruta-cuadrilla-item--ocupada': !cuadrillaEsAsignable(cuadrilla, rutaParaAsignar.id)
                          }"
                          :disabled="!cuadrillaEsAsignable(cuadrilla, rutaParaAsignar.id)"
                          :title="mensajeCuadrillaNoAsignable(cuadrilla, rutaParaAsignar.id) || ''"
                          @click="seleccionarCuadrillaParaAsignar(cuadrilla.id)"
                      >
                          <span class="asignar-ruta-cuadrilla-item__check" aria-hidden="true">
                              <i v-if="String(cuadrillaSeleccionadaParaAsignar) === String(cuadrilla.id)" class="bi bi-check-circle-fill"></i>
                          </span>
                          <span class="asignar-ruta-cuadrilla-item__main">
                              <span class="asignar-ruta-cuadrilla-item__nombre">{{ cuadrilla.nombre }}</span>
                              <span v-if="cuadrillaTieneOtraHojaAsignada(cuadrilla.id, rutaParaAsignar.id)"
                                    class="asignar-ruta-cuadrilla-item__ocupada">
                                  Ocupada
                              </span>
                              <span v-else-if="!cuadrillaTieneOperarios(cuadrilla)"
                                    class="asignar-ruta-cuadrilla-item__ocupada">
                                  Sin operarios
                              </span>
                              <span v-else-if="!cuadrillaTieneGestion(cuadrilla)"
                                    class="asignar-ruta-cuadrilla-item__ocupada">
                                  Sin gestión
                              </span>
                              <span v-else-if="cuadrilla.operarios && cuadrilla.operarios.length"
                                    class="asignar-ruta-cuadrilla-item__operarios">
                                  {{ cuadrilla.operarios.length }}
                                  {{ cuadrilla.operarios.length === 1 ? 'operario' : 'operarios' }}
                              </span>
                          </span>
                          <span v-if="cuadrilla.operarios && cuadrilla.operarios.length"
                                class="asignar-ruta-cuadrilla-item__avatars">
                              <template v-for="op in cuadrilla.operarios.slice(0, 3)" :key="'asig-av-' + cuadrilla.id + '-' + op.id">
                                  <img v-if="op.foto_perfil"
                                       class="crc-avatar crc-avatar--img"
                                       :src="urlFotoOperario(op.foto_perfil)"
                                       :alt="op.nombre">
                                  <span v-else
                                        class="crc-avatar"
                                        :style="{ backgroundColor: colorAvatarOperario(op.nombre) }">
                                      {{ inicialesOperario(op.nombre) }}
                                  </span>
                              </template>
                          </span>
                      </button>
                  </div>
              </div>
              <div class="rutas-modal__footer rutas-modal__footer--end">
                  <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal" @click="cerrarModalAsignar">
                      Cancelar
                  </button>
                  <button type="button" 
                          class="rutas-btn rutas-btn--success"
                          @click="confirmarAsignacion" 
                          :disabled="!cuadrillaSeleccionadaParaAsignar">
                      <i class="bi bi-check-lg"></i>
                      {{ rutaParaAsignar.asignada == 1 ? 'Cambiar' : 'Asignar' }}
                  </button>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal para visualizar todas las rutas (activas e inactivas) -->
  <div class="modal fade" id="modalVisualizarRutas" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-xl modal-dialog-centered supervisor-visualizar-todas-modal-dialog">
          <div class="modal-content rutas-modal supervisor-visualizar-todas-modal">
              <div class="rutas-modal__header">
                  <div class="rutas-modal__title">
                      <span class="rutas-modal__icon"><i class="bi bi-map"></i></span>
                      <h5>Todas las hojas de ruta</h5>
              </div>
                  <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" @click="cerrarVisualizacionRutas" aria-label="Cerrar">
                      <i class="bi bi-x-lg"></i>
                  </button>
                              </div>
              <div class="modal-body rutas-modal__body--flush supervisor-visualizar-todas__cuerpo">
                  <div class="supervisor-visualizar-todas-layout">
                      <aside class="supervisor-visualizar-todas-panel-izq">
                          <p class="supervisor-visualizar-todas-panel-izq__subtitulo mb-2">Hojas de ruta</p>
                          <div v-if="rutasActivas.length === 0" class="text-muted small text-center py-4">
                              No hay hojas de ruta.
                                  </div>
                          <ul v-else class="list-unstyled mb-0 supervisor-visualizar-todas-lista">
                              <li
                                  v-for="ruta in rutasActivas"
                                       :key="ruta.id" 
                                  class="supervisor-visualizar-todas-item"
                                  :class="{ 'supervisor-visualizar-todas-item--activa': rutaSeleccionadaVisualizarTodasId === ruta.id }"
                                  @click="seleccionarRutaVisualizarTodas(ruta)"
                              >
                                  <div class="supervisor-visualizar-todas-item__inner">
                                      <div
                                          class="supervisor-visualizar-todas-item__franja"
                                          :style="{ backgroundColor: ruta.color || '#808080' }"
                                          aria-hidden="true"
                                      ></div>
                                      <div class="supervisor-visualizar-todas-item__cuerpo">
                                          <div class="supervisor-visualizar-todas-item__fila-superior">
                                              <span class="supervisor-visualizar-todas-item__nombre">{{ ruta.nombre || 'Hoja de ruta' }}</span>
                                              <span
                                                  class="supervisor-visualizar-todas-item__reclamos"
                                                  :title="(ruta.cantidadReclamos || 0) + ' reclamos'"
                                                  :aria-label="(ruta.cantidadReclamos || 0) + ' reclamos'"
                                              >
                                                  <i class="bi bi-clipboard-data" aria-hidden="true"></i>
                                                  <span>{{ ruta.cantidadReclamos || 0 }}</span>
                                              </span>
                                              </div>
                                          <div class="supervisor-visualizar-todas-item__fila-cuadrilla">
                                              <span class="supervisor-visualizar-todas-item__cuadrilla" :title="ruta.cuadrilla_nombre || 'Sin asignar'">
                                                  <i class="bi bi-people-fill supervisor-visualizar-todas-item__cuadrilla-ico" aria-hidden="true"></i>
                                                  <span class="supervisor-visualizar-todas-item__cuadrilla-nombre">{{ ruta.cuadrilla_nombre || 'Sin asignar' }}</span>
                                              </span>
                                          </div>
                                          <div class="supervisor-visualizar-todas-item__fila-estados">
                                              <span class="badge" :class="claseBadgeEstadoEjecucionRuta(ruta)">
                                                  {{ textoEstadoEjecucionRuta(ruta) }}
                                              </span>
                                              <span
                                                  v-if="esEstadoEjecucionRuta(ruta)"
                                                  :class="claseCronometroEjecucionRutaSupervisor(ruta)"
                                              ><svg class="cronometro-badge-ico cronometro-badge-ico-ruta" viewBox="0 0 20 12" aria-hidden="true" focusable="false"><path d="M1 9.5 H6 V2.5 H14 V9.5 H19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="cronometro-badge-txt">{{ tiempoTranscurridoEjecucionSupervisor(ruta) }}</span></span>
                                      </div>
                                  </div>
                              </div>
                              </li>
                          </ul>
                      </aside>
                      <div class="supervisor-visualizar-todas-panel-der">
                          <div class="supervisor-ruta-detalle-vista-mapa supervisor-visualizar-todas-vista-mapa">
                              <div id="mapaVisualizarRutas" v-show="proveedorMapaRutasActivas === 'google'" class="supervisor-visualizar-todas-modal__mapa"></div>
                              <div id="mapaVisualizarRutasMapbox" v-show="proveedorMapaRutasActivas === 'mapbox'" class="supervisor-visualizar-todas-modal__mapa"></div>
                              <div class="crear-ruta-vista-controles">
                                  <button type="button" class="rutas-btn rutas-btn--sm rutas-btn--success" @click="alternarProveedorRutasActivas">
                                      <i class="bi bi-arrow-repeat"></i>
                                      {{ proveedorMapaRutasActivas === 'google' ? 'Mapbox' : 'Google Maps' }}
                                  </button>
                              </div>
                              </div>
                          </div>
                      </div>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal para administrar asignaciones de una ruta específica -->
  <div class="modal fade" id="modalAdministrarAsignaciones" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered modal-lg">
          <div class="modal-content rutas-modal rutas-modal-admin">
              <div class="rutas-modal__header">
                  <div class="rutas-modal__title">
                      <span class="rutas-modal__icon"><i class="bi bi-gear-fill"></i></span>
                      <h5>Administrar hoja de ruta</h5>
                      </div>
                  <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" @click="cerrarModalAdministrarAsignaciones" aria-label="Cerrar">
                      <i class="bi bi-x-lg"></i>
                  </button>
              </div>
              
              <div class="modal-body">
                  <div v-if="rutaSeleccionadaAdmin">
                      <div class="rutas-admin-resumen">
                          <div class="rutas-admin-resumen__top">
                              <div class="rutas-admin-resumen__color" :style="{ background: rutaSeleccionadaAdmin.color || '#808080' }">
                                  <i class="bi bi-map-fill"></i>
                              </div>
                              <div class="flex-grow-1">
                                  <h5 class="rutas-admin-resumen__nombre">{{ rutaSeleccionadaAdmin.nombre || 'Sin nombre' }}</h5>
                                  <div class="rutas-admin-meta-chips">
                                      <span class="rutas-admin-meta-chip"><i class="bi bi-file-earmark-text-fill"></i> {{ rutaSeleccionadaAdmin.cantidadReclamos }} reclamos</span>
                                      <span class="rutas-admin-meta-chip"><i class="bi bi-calendar-check-fill"></i> {{ formatearFecha(rutaSeleccionadaAdmin.fecha) }}</span>
                                      </div>
                                      </div>
                                      </div>
                          <div class="rutas-admin-status" :class="rutaSeleccionadaAdmin.asignada == 1 ? 'rutas-admin-status--ok' : 'rutas-admin-status--pending'">
                              <span class="rutas-admin-status__icon">
                                  <i class="bi" :class="rutaSeleccionadaAdmin.asignada == 1 ? 'bi-check-circle-fill' : 'bi-info-circle-fill'"></i>
                              </span>
                              <div>
                                  <strong v-if="rutaSeleccionadaAdmin.asignada == 1">
                                      Asignada a: <i class="bi bi-people-fill"></i> {{ rutaSeleccionadaAdmin.cuadrilla_nombre }}
                                  </strong>
                                  <strong v-else>Esta hoja de ruta aún no está asignada a ninguna cuadrilla</strong>
                                  </div>
                              </div>
                          </div>
                          
                      <p class="rutas-admin-section-title"><i class="bi bi-lightning-charge-fill"></i> Acciones disponibles</p>

                      <div class="rutas-admin-acciones">
                          <button
                              v-if="puedeAsignarOCambiarCuadrillaRuta(rutaSeleccionadaAdmin)"
                              type="button"
                              class="rutas-admin-action rutas-admin-action--success"
                              @click="abrirModalAsignarRutaDesdeAdmin(rutaSeleccionadaAdmin.id)">
                              <span class="rutas-admin-action__icon"><i class="bi" :class="rutaSeleccionadaAdmin.asignada == 1 ? 'bi-arrow-repeat' : 'bi-people-fill'"></i></span>
                              <span class="rutas-admin-action__text">
                                  <strong>{{ rutaSeleccionadaAdmin.asignada == 1 ? 'Reasignar a otra cuadrilla' : 'Asignar a cuadrilla' }}</strong>
                                  <small>{{ rutaSeleccionadaAdmin.asignada == 1 ? 'Cambiar la cuadrilla asignada a esta ruta' : 'Asignar esta ruta a una cuadrilla de trabajo' }}</small>
                                          </span>
                              <i class="bi bi-chevron-right rutas-admin-action__chevron"></i>
                          </button>

                          <button
                              v-if="rutaSeleccionadaAdmin.asignada == 1"
                              type="button"
                              class="rutas-admin-action rutas-admin-action--warning"
                              @click="desasignarRutaDesdeAdmin(rutaSeleccionadaAdmin.id)">
                              <span class="rutas-admin-action__icon"><i class="bi bi-x-circle-fill"></i></span>
                              <span class="rutas-admin-action__text">
                                  <strong>Desasignar de cuadrilla</strong>
                                  <small>Liberar esta ruta de su cuadrilla actual</small>
                              </span>
                              <i class="bi bi-chevron-right rutas-admin-action__chevron"></i>
                          </button>

                          <button
                              type="button"
                              class="rutas-admin-action rutas-admin-action--danger"
                              :disabled="!puedeEliminarHojaRuta(rutaSeleccionadaAdmin)"
                              :title="puedeEliminarHojaRuta(rutaSeleccionadaAdmin) ? '' : motivoNoPuedeEliminarHojaRuta(rutaSeleccionadaAdmin)"
                              @click="eliminarRutaDesdeAdmin(rutaSeleccionadaAdmin.id)">
                              <span class="rutas-admin-action__icon"><i class="bi bi-trash-fill"></i></span>
                              <span class="rutas-admin-action__text">
                                  <strong>Eliminar hoja de ruta</strong>
                                  <small v-if="puedeEliminarHojaRuta(rutaSeleccionadaAdmin)">Los reclamos Asignados volverán a Recibido</small>
                                  <small v-else>{{ motivoNoPuedeEliminarHojaRuta(rutaSeleccionadaAdmin) }}</small>
                              </span>
                              <i class="bi bi-chevron-right rutas-admin-action__chevron"></i>
                          </button>
                                  </div>
                              </div>

                  <div v-else class="rutas-admin-empty">
                      <div class="rutas-admin-empty__icon"><i class="bi bi-info-circle"></i></div>
                      <h6>No hay ruta seleccionada</h6>
                      <p>Seleccioná una ruta de la tabla para administrarla</p>
                          </div>
                      </div>

              <div class="rutas-modal__footer rutas-modal__footer--end">
                  <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal" @click="cerrarModalAdministrarAsignaciones">
                      Cerrar
                  </button>
              </div>
          </div>
      </div>
                      </div>

  <!-- Modal materiales (supervisor, solo lectura) -->
  <div class="modal fade" id="modalMaterialesSupervisor" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
          <div class="modal-content rutas-modal">
              <div class="rutas-modal__header">
                  <div class="rutas-modal__title">
                      <span class="rutas-modal__icon"><i class="bi bi-box-seam"></i></span>
                      <h5>Materiales — Reclamo #{{ reclamoSupervisorModal.municipalidad_id }}</h5>
                                  </div>
                  <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                      <i class="bi bi-x-lg"></i>
                  </button>
                                      </div>
              <div class="modal-body rutas-modal__body--scroll">
                  <div v-if="cargandoMaterialesSupervisor" class="rutas-modal-loading">
                      <div class="spinner-border spinner-border-sm" role="status"></div>
                      <span class="ms-2">Cargando…</span>
                  </div>
                  <div v-else-if="!historialMaterialesSupervisor.length" class="rutas-modal-empty">
                      No hay materiales registrados para este reclamo.
                  </div>
                  <div v-else class="mat-sup-historial">
                      <div class="mat-sup-historial__head">
                          <span>{{ historialEjecucionMapa?.ejecucion?.id ? 'Registrados en esta ejecución' : 'Registrados en este reclamo' }}</span>
                      </div>
                      <div class="mat-sup-historial__list bitacora-obra-feed">
                          <article
                              v-for="item in historialMaterialesSupervisor"
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
                                              <div class="mat-hist-msg__main mat-hist-msg__main--static">
                                                  <span class="mat-hist-msg__foto">
                                                      <img
                                                          v-if="item.material_foto"
                                                          :src="urlFotoMaterialCatalogo(item.material_foto)"
                                                          :alt="item.material_nombre || 'Material'"
                                                          loading="lazy"
                                                      >
                                                      <i v-else class="bi bi-box-seam"></i>
                                                  </span>
                                                  <div class="mat-hist-msg__body">
                                                      <strong>{{ item.material_nombre || 'Material' }}</strong>
                                                      <small>
                                                          <span v-if="item.cantidad">x{{ item.cantidad }}</span>
                                                          <span v-else>Sin cantidad</span>
                                      </small>
                                  </div>
                              </div>
                                              <p v-if="item.observacion" class="mb-0 mt-2 text-break small">{{ item.observacion }}</p>
                                  </div>
                                          <time class="bitacora-obra-msg__hora">{{ formatearFecha(item.fecha) }}</time>
                                  </div>
                              </div>
                              </div>
                          </article>
                      </div>
                  </div>
              </div>
              <div class="rutas-modal__footer rutas-modal__footer--end">
                  <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal registro en obra (supervisor, solo lectura) -->
  <div class="modal fade" id="modalObservacionesSupervisor" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered bitacora-obra-modal-dialog">
          <div class="modal-content rutas-modal bitacora-obra-modal">
              <div class="rutas-modal__header">
                  <div class="rutas-modal__title">
                      <span class="rutas-modal__icon"><i class="bi bi-journal-text"></i></span>
                      <h5>Registro en obra — Reclamo #{{ reclamoSupervisorModal.municipalidad_id }}</h5>
                                  </div>
                  <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                      <i class="bi bi-x-lg"></i>
                  </button>
                                  </div>
              <div class="modal-body bitacora-obra-modal__body">
                  <div class="bitacora-obra-modal__feed" id="bitacoraObraFeedSupervisor">
                  <div v-if="cargandoObservacionesSupervisor" class="rutas-modal-loading">
                      <div class="spinner-border spinner-border-sm" role="status"></div>
                      <span class="ms-2">Cargando…</span>
                              </div>
                  <div v-else-if="!historialBitacoraSupervisorOrdenado.length" class="rutas-modal-empty">
                      Aún no hay registros para este reclamo.
                  </div>
                  <ul v-else class="list-group list-group-flush bitacora-obra-feed mb-0">
                      <li v-for="o in historialBitacoraSupervisorOrdenado" :key="o.id" class="list-group-item px-0 py-2" :class="esEntradaCambioEstadoBitacoraObra(o) ? 'bitacora-obra-evento-estado' : 'bitacora-obra-msg'">
                          <template v-if="esEntradaCambioEstadoBitacoraObra(o)">
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
                                          <template v-if="esEntradaFotoBitacoraObra(o)">
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
                                          <template v-if="esEntradaFotoBitacoraObra(o)">
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
              </div>
              <div class="rutas-modal__footer rutas-modal__footer--end">
                  <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
              </div>
          </div>
                  </div>
              </div>
              
  <!-- Modal ver detalle de reclamo (vista previa del mapa) -->
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
                  <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
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