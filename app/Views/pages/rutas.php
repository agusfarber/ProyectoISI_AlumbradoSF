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

 <div id="app" class="container-fluid">
   <style>
       #tabla_rutas tbody tr {
           cursor: pointer;
           transition: background-color 0.2s ease;
       }
       #tabla_rutas tbody tr:hover {
           background-color: #f8f9fa !important;
       }
       #tabla_rutas tbody tr.table-primary {
           background-color: #0d6efd !important;
           color: white;
       }
       #tabla_rutas tbody tr.table-primary:hover {
           background-color: #0b5ed7 !important;
       }
       
      /* Estilos para el selector de asignación moderno */
      .asignacion-selector-container {
          position: relative;
          display: inline-block;
          width: 100%;
      }
      
      .asignacion-btn-moderno {
          position: relative;
      }
      
      .asignacion-btn-moderno::before {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
          transition: left 0.5s;
          left: -100%;
      }
      
      .asignacion-btn-moderno:hover::before {
          left: 100%;
      }
      
      /* Animaciones para estados */
      @keyframes pulse {
          0% { 
              transform: scale(1);
          }
          50% { 
              transform: scale(1.05);
          }
          100% { 
              transform: scale(1);
          }
      }
   </style>
    <div>
          Gestión de Hojas de Ruta
    </div>

    <ul v-if="puedeVerHistorialEjecuciones" class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link" :class="{ active: solapaRutas === 'activas' }" @click="solapaRutas = 'activas'">Hojas activas</button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link" :class="{ active: solapaRutas === 'historial' }" @click="solapaRutas = 'historial'">Historial de rutas</button>
        </li>
    </ul>

   <div v-show="solapaRutas === 'activas'">
   <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex gap-2">
            <button class="btn btn-primary" @click="abrirModalCrearRuta">
               <i class="bi bi-plus-circle text-white"></i> Nueva Hoja de Ruta
            </button>
           <button class="btn btn-success" @click="abrirModalVisualizarRutas">
              <i class="bi bi-map text-white"></i> Visualizar Rutas
           </button>
      </div>
   </div>

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
                  {{ ruta.nombre || 'Hoja de ruta' }}
              </div>
              <div class="supervisor-ruta-card__mapa">
                  <div :id="'mapaPreviewRuta-' + ruta.id"></div>
                  <div v-if="!mapasPreviewSupervisor[ruta.id]" class="supervisor-ruta-card__mapa-placeholder">
                      <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                      Cargando mapa…
                  </div>
              </div>
              <div class="supervisor-ruta-card__meta">
                  <div class="supervisor-ruta-card__meta-row">
                      <span class="supervisor-ruta-card__meta-label">Reclamos</span>
                      <strong>{{ ruta.cantidadReclamos || 0 }}</strong>
                  </div>
                  <div class="supervisor-ruta-card__meta-row">
                      <span class="supervisor-ruta-card__meta-label">Cuadrilla</span>
                      <span class="supervisor-ruta-card__cuadrilla" :title="ruta.cuadrilla_nombre || 'Sin asignar'">
                          {{ ruta.cuadrilla_nombre || 'Sin asignar' }}
                      </span>
                  </div>
                  <div class="supervisor-ruta-card__estado">
                      <span class="badge" :class="claseBadgeEstadoEjecucionRuta(ruta)">
                          {{ textoEstadoEjecucionRuta(ruta) }}
                      </span>
                      <span
                          v-if="esEstadoEjecucionRuta(ruta)"
                          class="badge bg-dark cronometro-ruta-supervisor font-monospace"
                          :data-inicio-ejecucion-at="ruta.inicio_ejecucion_at || ''"
                      >{{ tiempoTranscurridoEjecucionSupervisor(ruta) }}</span>
                  </div>
              </div>
          </article>
      </div>
  </div>

  <!-- Modal detalle de hoja (supervisor) -->
  <div class="modal fade" id="modalDetalleSupervisorRuta" tabindex="-1" aria-labelledby="modalDetalleSupervisorRutaLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered supervisor-ruta-detalle-modal-dialog">
          <div class="modal-content supervisor-ruta-detalle-modal">
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
                          <div class="ruta-detalle-encabezado supervisor-ruta-detalle-panel-izq__bloque">
                              <p class="supervisor-ruta-detalle-panel-izq__subtitulo mb-0">Detalle de hoja de ruta</p>
                              <div class="ruta-detalle-meta-fila">
                                  <span class="ruta-detalle-pill w-100">
                                      <i class="bi bi-clipboard-data"></i>
                                      <strong>{{ rutaVisualizando.cantidadReclamos || 0 }}</strong> reclamo(s)
                                  </span>
                              </div>
                              <div class="ruta-detalle-meta-fila">
                                  <span class="ruta-detalle-pill w-100">
                                      <i class="bi bi-people-fill"></i>
                                      <strong>{{ rutaVisualizando.cuadrilla_nombre || 'Sin asignar' }}</strong>
                                  </span>
                              </div>
                              <div class="ruta-detalle-meta-fila">
                                  <span class="ruta-detalle-estado-grupo d-inline-flex align-items-center flex-wrap gap-1">
                                      <span class="badge" :class="claseBadgeEstadoEjecucionRuta(rutaVisualizando)">
                                          {{ textoEstadoEjecucionRuta(rutaVisualizando) }}
                                      </span>
                                      <span
                                          v-if="esEstadoEjecucionRuta(rutaVisualizando)"
                                          class="badge bg-dark cronometro-ruta-supervisor font-monospace"
                                          :data-inicio-ejecucion-at="rutaVisualizando.inicio_ejecucion_at || ''"
                                      >{{ tiempoTranscurridoEjecucionSupervisor(rutaVisualizando) }}</span>
                                  </span>
                              </div>
                              <div class="supervisor-ruta-detalle-panel-izq__acciones d-flex flex-column gap-2">
                                  <button
                                      v-if="puedeAsignarOCambiarCuadrillaRuta(rutaVisualizando)"
                                      type="button"
                                      class="btn btn-sm btn-outline-primary w-100"
                                      @click="abrirModalAsignarRuta(rutaVisualizando.id)"
                                  >
                                      <i class="bi bi-people-fill"></i>
                                      {{ rutaVisualizando.asignada == 1 ? 'Cambiar cuadrilla' : 'Asignar cuadrilla' }}
                                  </button>
                                  <button type="button" class="btn btn-sm btn-outline-danger w-100" @click="eliminarRutaDesdeVisualizacion(rutaVisualizando.id)">
                                      <i class="bi bi-trash"></i> Eliminar hoja
                                  </button>
                              </div>
                          </div>
                      </aside>
                      <div class="supervisor-ruta-detalle-panel-der">
                          <div class="supervisor-ruta-detalle-vista-contenido">
                              <div v-show="modoVistaDetalleSupervisor === 'mapa'" class="supervisor-ruta-detalle-vista-mapa">
                                  <div class="supervisor-ruta-detalle-vista-toolbar supervisor-ruta-detalle-vista-toolbar--flotante">
                                      <button
                                          type="button"
                                          class="btn btn-sm btn-primary"
                                          @click="cambiarModoVistaDetalleSupervisor('lista')"
                                      >
                                          <i class="bi bi-list-ul text-white"></i> Ver lista
                                      </button>
                                      <button
                                          type="button"
                                          class="btn btn-sm btn-success"
                                          @click="alternarProveedorVisualizacion"
                                      >
                                          <i class="bi bi-arrow-repeat text-white"></i>
                                          {{ proveedorMapaVisualizacion === 'google' ? 'Mapbox' : 'Google Maps' }}
                                      </button>
                                  </div>
                                  <div id="mapaDetalleSupervisor" v-show="proveedorMapaVisualizacion === 'google'" class="supervisor-ruta-detalle-modal__mapa"></div>
                                  <div id="mapaDetalleSupervisorMapbox" v-show="proveedorMapaVisualizacion === 'mapbox'" class="supervisor-ruta-detalle-modal__mapa"></div>
                              </div>
                              <div v-show="modoVistaDetalleSupervisor === 'lista'" class="supervisor-ruta-detalle-vista-lista">
                                  <div class="supervisor-ruta-detalle-vista-toolbar supervisor-ruta-detalle-vista-toolbar--lista">
                                      <button
                                          type="button"
                                          class="btn btn-sm btn-primary"
                                          @click="cambiarModoVistaDetalleSupervisor('mapa')"
                                      >
                                          <i class="bi bi-map text-white"></i> Ver mapa
                                      </button>
                                  </div>
                                  <p v-if="!reclamosRutaVisualizando.length" class="text-muted text-center py-4 mb-0">
                                      Sin reclamos cargados.
                                  </p>
                                  <div v-else class="ruta-secuencia-container supervisor-detalle-secuencia">
                                      <div v-for="(reclamo, idx) in reclamosRutaVisualizando" :key="reclamo.id" class="ruta-secuencia-item">
                                          <div class="card reclamo-card reclamo-card-secuencia" :class="getCardClassCrearRuta(reclamo)">
                                              <div class="card-body ruta-secuencia-cardbody">
                                                  <div class="ruta-secuencia-fila">
                                                      <div class="ruta-secuencia-main" @click="seleccionarReclamoDetalleSupervisor(reclamo)" role="button" tabindex="0">
                                                          <span class="ruta-secuencia-motivo-icon" :title="reclamo.municipalidad_motivo || 'Motivo no especificado'">
                                                              {{ iconoMotivoReclamo(reclamo.municipalidad_motivo) }}
                                                          </span>
                                                          <span class="ruta-secuencia-id">{{ reclamo.municipalidad_id }}</span>
                                                          <span
                                                              class="ruta-secuencia-calle"
                                                              :title="(reclamo.municipalidad_domicilio || '') + ' ' + (reclamo.municipalidad_numeroDomicilio || '')"
                                                          >
                                                              <i class="bi bi-geo-alt ruta-secuencia-calle-ico" aria-hidden="true"></i>
                                                              {{ reclamo.municipalidad_domicilio }} {{ reclamo.municipalidad_numeroDomicilio }}
                                                          </span>
                                                      </div>
                                                      <div class="ruta-secuencia-toolbar" @click.stop>
                                                          <span class="badge" :class="claseBadgeEstadoReclamoSupervisor(reclamo.municipalidad_estado)">
                                                              {{ reclamo.municipalidad_estado || '—' }}
                                                          </span>
                                                          <span
                                                              v-if="sesionReparacionReclamoSupervisor(reclamo)"
                                                              class="ruta-secuencia-crono-reparacion badge font-monospace"
                                                              :class="sesionReparacionReclamoSupervisor(reclamo).activo ? 'bg-dark text-white' : 'bg-secondary'"
                                                              title="Tiempo en reparación"
                                                          >{{ textoCronometroReparacionReclamoSupervisor(reclamo) }}</span>
                                                          <template v-if="puedeVerAccionesObraSupervisorEnReclamo(reclamo)">
                                                              <button
                                                                  type="button"
                                                                  class="btn btn-sm btn-outline-secondary ruta-secuencia-btn-material"
                                                                  title="Materiales utilizados"
                                                                  @click="abrirModalMaterialesSupervisor(reclamo)"
                                                              >
                                                                  <i class="bi bi-box-seam"></i>
                                                              </button>
                                                              <button
                                                                  type="button"
                                                                  class="btn btn-sm btn-outline-secondary ruta-secuencia-btn-obs-ejecucion"
                                                                  title="Observaciones en esta ejecución"
                                                                  @click="abrirModalObservacionesSupervisor(reclamo)"
                                                              >
                                                                  <i class="bi bi-chat-square-text"></i>
                                                              </button>
                                                          </template>
                                                      </div>
                                                  </div>
                                              </div>
                                          </div>
                                          <div v-if="idx < reclamosRutaVisualizando.length - 1" class="ruta-secuencia-flecha">
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
  <div v-if="!esSupervisorVistaTarjetas" class="table-responsive">
      <table id="tabla_rutas" class="table table-bordered table-hover w-100">
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

   <div v-show="solapaRutas === 'historial' && puedeVerHistorialEjecuciones" class="mb-4">
      <p class="text-muted small mb-2">Ejecuciones finalizadas. Solo lectura; incluye inicio y fin de hoja, trabajo por reclamo y cambios de estado registrados durante la salida a campo.</p>
      <div v-if="historialEjecucionesCargando" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando…</span></div>
      </div>
      <div v-else class="table-responsive">
          <table class="table table-bordered table-hover table-sm w-100">
              <thead class="table-light">
                  <tr>
                      <th>ID</th>
                      <th>Hoja</th>
                      <th>Cuadrilla</th>
                      <th>Inicio ejecución</th>
                      <th>Fin ejecución</th>
                      <th style="width: 140px;">Acciones</th>
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
                          <button type="button" class="btn btn-sm btn-outline-primary" @click="abrirDetalleHistorialEjecucion(h.id)">
                              <i class="bi bi-list-ul"></i> Ver detalle
                          </button>
                      </td>
                  </tr>
              </tbody>
          </table>
      </div>
   </div>

  <!-- Modal detalle historial de ejecución -->
  <div class="modal fade" id="modalHistorialEjecucion" tabindex="-1" aria-labelledby="modalHistorialEjecucionLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="modalHistorialEjecucionLabel"><i class="bi bi-clock-history"></i> Historial de la ejecución</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
              </div>
              <div class="modal-body">
                  <div v-if="historialDetalleCargando" class="text-center py-5">
                      <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando…</span></div>
                  </div>
                  <template v-else-if="historialEjecucionDetalle && historialEjecucionDetalle.ejecucion">
                      <dl class="row small mb-3">
                          <dt class="col-sm-3">Hoja de ruta</dt>
                          <dd class="col-sm-9">{{ historialEjecucionDetalle.ejecucion.ruta_nombre }}</dd>
                          <dt class="col-sm-3">Cuadrilla</dt>
                          <dd class="col-sm-9">{{ historialEjecucionDetalle.ejecucion.cuadrilla_nombre || '—' }}</dd>
                          <dt class="col-sm-3">Inicio</dt>
                          <dd class="col-sm-9">{{ historialEjecucionDetalle.ejecucion.inicio_at || '—' }}</dd>
                          <dt class="col-sm-3">Fin</dt>
                          <dd class="col-sm-9">{{ historialEjecucionDetalle.ejecucion.fin_at || '—' }}</dd>
                      </dl>
                      <h6 class="border-bottom pb-2">Eventos registrados</h6>
                      <div class="table-responsive">
                          <table class="table table-sm table-bordered align-middle">
                              <thead class="table-light">
                                  <tr>
                                      <th style="width: 170px;">Horario</th>
                                      <th style="width: 220px;">Tipo</th>
                                      <th style="width: 120px;">Reclamo</th>
                                      <th>Usuario</th>
                                      <th>Detalle</th>
                                  </tr>
                              </thead>
                              <tbody>
                                  <tr v-for="ev in (historialEjecucionDetalle.eventos || [])" :key="ev.id">
                                      <td class="text-nowrap small">{{ ev.ocurrido_at }}</td>
                                      <td class="small">{{ textoTipoEventoHistorial(ev.tipo) }}</td>
                                      <td class="small">{{ etiquetaReclamoEventoHistorial(ev) }}</td>
                                      <td class="small">{{ ev.usuario_nombre || '—' }}</td>
                                      <td class="small">{{ detalleLegibleEventoHistorial(ev) }}</td>
                                  </tr>
                                  <tr v-if="!(historialEjecucionDetalle.eventos && historialEjecucionDetalle.eventos.length)">
                                      <td colspan="5" class="text-muted text-center">Sin eventos.</td>
                                  </tr>
                              </tbody>
                          </table>
                      </div>
                  </template>
                  <p v-else class="text-muted mb-0">No hay datos para mostrar.</p>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal para crear nueva hoja de ruta automática -->
  <div class="modal fade" id="modalCrearRuta" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog" :class="vistaPrevia.activa ? 'modal-xl' : 'modal-dialog-centered'">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title">
                      <i class="bi bi-plus-circle"></i> Crear Hoja de Ruta Automática
                      <button v-if="vistaPrevia.activa" 
                              type="button" 
                              class="btn btn-sm btn-outline-info ms-2" 
                              data-bs-toggle="popover"
                              data-bs-placement="bottom"
                              data-bs-html="true"
                              :data-bs-content="`
                                  <div class='text-start'>
                                      <p class='mb-1'><small><strong>Reclamos:</strong> ${vistaPrevia.rutaOptimizada.length}</small></p>
                                      <p class='mb-1'><small><strong>Tiempo:</strong> ${vistaPrevia.tiempoEstimado} min</small></p>
                                      <p class='mb-0'><small><strong>Distancia:</strong> ${vistaPrevia.distanciaTotal} km</small></p>
                                  </div>
                              `">
                          <i class="bi bi-info-circle"></i>
                      </button>
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" @click="resetearModal"></button>
              </div>
              <div class="modal-body">
                  <!-- PASO 1: Color y cantidad de reclamos -->
                  <div v-if="!vistaPrevia.activa">
                      <div class="card">
                          <div class="card-body">
                              <!-- Color de la ruta -->
                              <div class="mb-3">
                                  <label for="colorRuta" class="form-label">Color de la Ruta</label>
                                  <div class="d-flex align-items-center gap-2">
                                      <input type="color" id="colorRuta" class="form-control form-control-color" 
                                             v-model="nuevaRuta.color"
                                             style="width: 80px; height: 45px;">
                                      <span class="text-muted">{{ nuevaRuta.color }}</span>
                                  </div>
                                  <div class="form-text">Seleccione el color que se usará para dibujar la ruta en el mapa</div>
                              </div>

                              <!-- Cantidad de reclamos -->
                              <div class="mb-3">
                                  <label for="cantidadReclamos" class="form-label">¿Cuántos reclamos desea incluir en la hoja de ruta?</label>
                                  <input type="number" id="cantidadReclamos" class="form-control form-control-lg" 
                                         v-model.number="nuevaRuta.cantidadReclamos" 
                                         :min="1" 
                                         :max="reclamosDisponibles" 
                                         required>
                              </div>
                          </div>
                      </div>
                  </div>

                  <!-- PASO 2: Vista Previa con Mapa / Lista -->
                  <div v-if="vistaPrevia.activa">
                      <div v-if="modoEdicion" class="alert alert-info mb-3">
                          <i class="bi bi-info-circle"></i>
                          <strong>Modo Edición Activo:</strong>
                          <ul class="mb-0 mt-2">
                              <li>Haga clic en los reclamos del mapa para <strong>agregarlos</strong> a la ruta</li>
                              <li>En la vista lista use las flechas para <strong>reordenar</strong> o el botón para <strong>eliminar</strong> reclamos</li>
                          </ul>
                      </div>

                      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                          <small v-if="cuadrillaSeleccionadaCrearRuta" class="text-muted">
                              Cuadrilla seleccionada:
                              <strong>{{ nombreCuadrillaCrearRuta }}</strong>
                          </small>
                          <small v-else class="text-warning">
                              <i class="bi bi-exclamation-triangle"></i> Seleccione una cuadrilla en el panel izquierdo
                          </small>
                          <div class="d-flex gap-2 ms-auto">
                              <button
                                  v-if="modoVistaCrearRuta === 'mapa'"
                                  type="button"
                                  class="btn btn-sm btn-outline-primary"
                                  @click="cambiarModoVistaCrearRuta('lista')"
                              >
                                  <i class="bi bi-list-ul"></i> Ver lista
                              </button>
                              <button
                                  v-else
                                  type="button"
                                  class="btn btn-sm btn-outline-primary"
                                  @click="cambiarModoVistaCrearRuta('mapa')"
                              >
                                  <i class="bi bi-map"></i> Ver mapa
                              </button>
                          </div>
                      </div>

                      <!-- Panel cuadrillas (izq.) + mapa o lista (der.) -->
                      <div class="row crear-ruta-preview-layout">
                          <div class="col-md-3">
                              <div class="card h-100 crear-ruta-panel-cuadrillas">
                                  <div class="card-header py-1 px-2">
                                      <small class="mb-0 crear-ruta-cuadrillas-titulo"><i class="bi bi-people-fill"></i> Cuadrillas</small>
                                  </div>
                                  <div class="list-group list-group-flush crear-ruta-cuadrillas-scroll">
                                      <button
                                          v-for="cuadrilla in cuadrillasDisponibles"
                                          :key="cuadrilla.id"
                                          type="button"
                                          class="list-group-item list-group-item-action crear-ruta-cuadrilla-item"
                                          :class="{
                                              active: String(cuadrillaSeleccionadaCrearRuta) === String(cuadrilla.id),
                                              'crear-ruta-cuadrilla-item--ocupada': cuadrillaTieneOtraHojaAsignada(cuadrilla.id)
                                          }"
                                          :disabled="cuadrillaTieneOtraHojaAsignada(cuadrilla.id)"
                                          :title="mensajeCuadrillaOcupada(cuadrilla.id) || 'Asignar hoja a esta cuadrilla'"
                                          @click="seleccionarCuadrillaCrearRuta(cuadrilla.id)"
                                      >
                                          <div class="d-flex align-items-center gap-1 w-100">
                                              <i class="bi bi-people-fill flex-shrink-0 crear-ruta-cuadrilla-ico"></i>
                                              <div class="text-start min-w-0 flex-grow-1">
                                                  <div class="crear-ruta-cuadrilla-nombre text-truncate">{{ cuadrilla.nombre }}</div>
                                                  <small v-if="cuadrillaTieneOtraHojaAsignada(cuadrilla.id)" class="d-block text-danger">
                                                      Ocupada: {{ hojaActivaDeCuadrilla(cuadrilla.id)?.nombre }}
                                                  </small>
                                                  <small v-else-if="cuadrilla.descripcion" class="d-block text-muted text-truncate">{{ cuadrilla.descripcion }}</small>
                                              </div>
                                          </div>
                                      </button>
                                      <div v-if="!cuadrillasDisponibles.length" class="list-group-item text-muted small text-center py-3">
                                          No hay cuadrillas disponibles.
                                      </div>
                                  </div>
                              </div>
                          </div>
                          <div class="col-md-9">
                              <div v-show="modoVistaCrearRuta === 'mapa'" class="card h-100">
                                  <div class="card-header d-flex justify-content-between align-items-center py-2">
                                      <h6 class="mb-0"><i class="bi bi-geo-alt"></i> Vista previa del recorrido</h6>
                                      <button class="btn btn-sm btn-success" @click="alternarProveedorVistaPrevia">
                                          <i class="bi bi-arrow-repeat text-white"></i>
                                          {{ proveedorMapaVistaPrevia === 'google' ? 'Mapbox' : 'Google Maps' }}
                                      </button>
                                  </div>
                                  <div class="card-body p-0">
                                      <div id="mapaCrearRuta" v-show="proveedorMapaVistaPrevia === 'google'" style="width: 100%; height: 500px;"></div>
                                      <div id="mapaCrearRutaMapbox" v-show="proveedorMapaVistaPrevia === 'mapbox'" style="width: 100%; height: 500px;"></div>
                                  </div>
                              </div>

                              <div v-show="modoVistaCrearRuta === 'lista'" class="card h-100">
                                  <div class="card-header py-2">
                                      <h6 class="mb-0"><i class="bi bi-list-ul"></i> Orden de reclamos en la ruta</h6>
                                  </div>
                                  <div class="card-body crear-ruta-lista-panel p-2">
                                      <p v-if="!vistaPrevia.rutaOptimizada.length" class="text-muted text-center py-4 mb-0">
                                          No hay reclamos en la ruta.
                                      </p>
                                      <div v-else class="ruta-secuencia-container crear-ruta-secuencia">
                                          <div v-for="(reclamo, idx) in vistaPrevia.rutaOptimizada" :key="reclamo.id" class="ruta-secuencia-item">
                                              <div class="card reclamo-card reclamo-card-secuencia" :class="getCardClassCrearRuta(reclamo)">
                                                  <div class="card-body ruta-secuencia-cardbody">
                                                      <div class="ruta-secuencia-fila">
                                                          <div class="ruta-secuencia-main">
                                                              <span class="badge bg-dark me-1">{{ idx + 1 }}</span>
                                                              <span class="ruta-secuencia-motivo-icon" :title="reclamo.municipalidad_motivo || 'Motivo no especificado'">
                                                                  {{ iconoMotivoReclamo(reclamo.municipalidad_motivo) }}
                                                              </span>
                                                              <span class="ruta-secuencia-id">{{ reclamo.municipalidad_id }}</span>
                                                              <span
                                                                  class="ruta-secuencia-calle"
                                                                  :title="(reclamo.municipalidad_domicilio || '') + ' ' + (reclamo.municipalidad_numeroDomicilio || '')"
                                                              >
                                                                  <i class="bi bi-geo-alt ruta-secuencia-calle-ico" aria-hidden="true"></i>
                                                                  {{ reclamo.municipalidad_domicilio }} {{ reclamo.municipalidad_numeroDomicilio }}
                                                              </span>
                                                          </div>
                                                          <div v-if="modoEdicion" class="d-flex gap-1 flex-shrink-0" @click.stop>
                                                              <button
                                                                  type="button"
                                                                  class="btn btn-sm btn-outline-secondary"
                                                                  @click="moverReclamoArriba(idx)"
                                                                  :disabled="idx === 0"
                                                                  title="Mover arriba"
                                                              >
                                                                  <i class="bi bi-arrow-up"></i>
                                                              </button>
                                                              <button
                                                                  type="button"
                                                                  class="btn btn-sm btn-outline-secondary"
                                                                  @click="moverReclamoAbajo(idx)"
                                                                  :disabled="idx === vistaPrevia.rutaOptimizada.length - 1"
                                                                  title="Mover abajo"
                                                              >
                                                                  <i class="bi bi-arrow-down"></i>
                                                              </button>
                                                              <button
                                                                  type="button"
                                                                  class="btn btn-sm btn-outline-danger"
                                                                  @click="eliminarReclamoDeRuta(idx)"
                                                                  title="Eliminar de la ruta"
                                                              >
                                                                  <i class="bi bi-trash"></i>
                                                              </button>
                                                          </div>
                                                      </div>
                                                      <small v-if="reclamo.municipalidad_motivo" class="text-muted d-block mt-1 ps-1">{{ reclamo.municipalidad_motivo }}</small>
                                                  </div>
                                              </div>
                                              <div v-if="idx < vistaPrevia.rutaOptimizada.length - 1" class="ruta-secuencia-flecha">
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
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="resetearModal">
                      Cancelar
                  </button>
                  
                  <!-- Botón para generar vista previa (solo cuando no está activa) -->
                  <button v-if="!vistaPrevia.activa" 
                          type="button" 
                          class="btn btn-primary" 
                          @click="mostrarVistaPrevia" 
                          :disabled="!puedeVerVistaPrevia">
                      <i class="bi bi-eye text-white"></i> Generar Vista Previa
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
                          class="btn btn-warning" 
                          @click="activarModoEdicion">
                      <i class="bi bi-pencil"></i> Editar Hoja de Ruta
                  </button>
                  
                  <!-- Botón Cancelar Edición (solo cuando está en modo edición) -->
                  <button v-if="modoEdicion" 
                          type="button" 
                          class="btn btn-outline-secondary" 
                          @click="cancelarEdicion">
                      <i class="bi bi-x-circle"></i> Cancelar Edición
                  </button>
                  
                  <!-- Botón Crear Ruta (solo cuando vista previa está activa) -->
                  <button v-if="vistaPrevia.activa" 
                          type="button" 
                          class="btn btn-success" 
                          @click="crearRutaAutomatica" 
                          :disabled="!puedeGenerarRuta">
                      <i class="bi bi-check-circle text-white"></i> {{ modoEdicion ? 'Crear Ruta Personalizada' : 'Crear Ruta Automática' }}
                  </button>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal para ver hoja de ruta -->
  <div class="modal fade" id="modalVerRuta" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-xl">
          <div class="modal-content">
              <div class="modal-header d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center">
                      <h5 class="modal-title mb-0">
                          <i class="bi bi-map"></i> {{ rutaVisualizando.nombre || 'Hoja de Ruta' }}
                          <span v-if="rutaVisualizando.asignada == 1" class="badge bg-success ms-2">Asignada</span>
                          <span v-else class="badge bg-secondary ms-2">No Asignada</span>
                          <button type="button" 
                                  class="btn btn-sm btn-outline-info ms-2" 
                                  data-bs-toggle="popover"
                                  data-bs-placement="bottom"
                                  data-bs-html="true"
                                  :data-bs-content="`
                                      <div class='text-start'>
                                          <p class='mb-1'><small><strong>ID:</strong> ${rutaVisualizando.id}</small></p>
                                          <p class='mb-1'><small><strong>Reclamos:</strong> ${rutaVisualizando.cantidadReclamos}</small></p>
                                          <p class='mb-1'><small><strong>Tiempo:</strong> ${rutaVisualizando.tiempoEstimado}</small></p>
                                          <p class='mb-1'><small><strong>Fecha:</strong> ${formatearFecha(rutaVisualizando.fecha)}</small></p>
                                          <p class='mb-0'><small><strong>Asignación:</strong> ${rutaVisualizando.asignada == 1 ? 'Asignada' : 'No Asignada'}</small></p>
                                      </div>
                                  `">
                              <i class="bi bi-info-circle"></i>
                          </button>
                      </h5>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                      <button type="button" 
                              class="btn btn-danger btn-sm" 
                              @click="eliminarRutaDesdeVisualizacion(rutaVisualizando.id)"
                              style="font-weight: 600; padding: 0.5rem 1rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3); transition: all 0.2s ease;"
                              @mouseover="$event.target.style.transform = 'translateY(-2px)'; $event.target.style.boxShadow = '0 4px 12px rgba(220, 53, 69, 0.4)'"
                              @mouseout="$event.target.style.transform = 'translateY(0)'; $event.target.style.boxShadow = '0 2px 8px rgba(220, 53, 69, 0.3)'">
                          <i class="bi bi-trash-fill" style="color: white;"></i> Eliminar Hoja de Ruta
                      </button>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" @click="cerrarVisualizacion"></button>
                  </div>
              </div>
              <div class="modal-body">
                  <div class="row">
                      <!-- Panel de información a la izquierda -->
                      <div class="col-md-4">
                          <div class="card h-100">
                              <div class="card-header py-1">
                                  <small class="mb-0"><strong>Reclamos en la Ruta</strong></small>
                              </div>
                              <div class="list-group list-group-flush" style="height: 500px; overflow-y: auto;">
                                  <div v-for="(reclamo, index) in reclamosRutaVisualizando" 
                                       :key="reclamo.id" 
                                       class="list-group-item py-1 px-2"
                                       style="cursor: pointer;"
                                       @click="centrarEnReclamo(reclamo)">
                                      <div class="d-flex align-items-center">
                                          <span class="badge bg-dark me-2" style="font-size: 0.7rem;">{{ reclamo.posicion }}</span>
                                          <span class="ruta-secuencia-motivo-icon me-2" :title="reclamo.municipalidad_motivo || 'Motivo no especificado'">
                                              {{ iconoMotivoReclamo(reclamo.municipalidad_motivo) }}
                                          </span>
                                          <div style="font-size: 0.85rem;">
                                              <strong>{{ reclamo.municipalidad_id }}</strong><br>
                                              <small class="text-muted" style="font-size: 0.75rem;">{{ reclamo.municipalidad_motivo }}</small>
                                          </div>
                                      </div>
                                      <div v-if="reclamoMuestraIndicadorObraSupervisorMapa(reclamo)" class="d-flex align-items-center mt-1 pt-1 border-top border-secondary border-opacity-25">
                                          <span style="font-size: 1rem;" aria-hidden="true">🚚</span>
                                          <span class="ms-1 font-monospace text-warning fw-semibold small">{{ textoCronometroObraSupervisor(reclamo) }}</span>
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
                                      <i class="bi bi-geo-alt"></i> Visualización del Recorrido
                                  </h6>
                                  <button class="btn btn-sm btn-success" @click="alternarProveedorVisualizacion">
                                      <i class="bi bi-arrow-repeat text-white"></i> 
                                      {{ proveedorMapaVisualizacion === 'google' ? 'Cambiar a Mapbox' : 'Cambiar a Google Maps' }}
                                  </button>
                              </div>
                              <div class="card-body p-0">
                                  <div id="mapaVerRuta" v-show="proveedorMapaVisualizacion === 'google'" style="width: 100%; height: 500px;"></div>
                                  <div id="mapaVerRutaMapbox" v-show="proveedorMapaVisualizacion === 'mapbox'" style="width: 100%; height: 500px;"></div>
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
      <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title">
                      <i class="bi bi-people-fill"></i> Asignar Hoja de Ruta a Cuadrilla
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" @click="cerrarModalAsignar"></button>
              </div>
              <div class="modal-body">
                  <div class="alert alert-info">
                      <i class="bi bi-info-circle"></i>
                      <strong>Ruta seleccionada:</strong> {{ rutaParaAsignar.nombre || 'Sin nombre' }}
                      <br>
                      <small>{{ rutaParaAsignar.cantidadReclamos }} reclamo(s) | {{ rutaParaAsignar.tiempoEstimado }}</small>
                  </div>
                  
                  <div class="mb-3">
                      <label for="selectCuadrilla" class="form-label"><strong>Seleccione una Cuadrilla</strong></label>
                      <select id="selectCuadrilla" class="form-select" v-model="cuadrillaSeleccionadaParaAsignar" required>
                          <option value="">-- Seleccione una cuadrilla --</option>
                          <option
                              v-for="cuadrilla in cuadrillasDisponibles"
                              :key="cuadrilla.id"
                              :value="cuadrilla.id"
                              :disabled="cuadrillaTieneOtraHojaAsignada(cuadrilla.id, rutaParaAsignar.id)"
                          >
                              {{ cuadrilla.nombre }}
                              <template v-if="cuadrillaTieneOtraHojaAsignada(cuadrilla.id, rutaParaAsignar.id)">
                                  (ocupada: {{ hojaActivaDeCuadrilla(cuadrilla.id, rutaParaAsignar.id)?.nombre }})
                              </template>
                              <template v-else-if="cuadrilla.descripcion"> — {{ cuadrilla.descripcion }}</template>
                          </option>
                      </select>
                  </div>
                  
                  <div v-if="rutaParaAsignar.asignada == 1" class="alert alert-warning">
                      <i class="bi bi-exclamation-triangle"></i>
                      Esta ruta ya está asignada a: <strong>{{ rutaParaAsignar.cuadrilla_nombre }}</strong>
                      <br>
                      <small>Al reasignar, se quitará de la cuadrilla anterior.</small>
                  </div>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="cerrarModalAsignar">
                      Cancelar
                  </button>
                  <button type="button" 
                          class="btn btn-success" 
                          @click="confirmarAsignacion" 
                          :disabled="!cuadrillaSeleccionadaParaAsignar">
                      <i class="bi bi-check-circle text-white"></i> Asignar Ruta
                  </button>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal para visualizar todas las rutas (activas e inactivas) -->
  <div class="modal fade" id="modalVisualizarRutas" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-xl modal-dialog-centered supervisor-visualizar-todas-modal-dialog">
          <div class="modal-content supervisor-visualizar-todas-modal">
              <div class="modal-header supervisor-visualizar-todas-modal__header py-2 px-3">
                  <h5 class="modal-title mb-0">
                      <i class="bi bi-map text-white"></i> Todas las hojas de ruta
                  </h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" @click="cerrarVisualizacionRutas"></button>
              </div>
              <div class="modal-body p-0 supervisor-visualizar-todas__cuerpo">
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
                                              <span class="supervisor-visualizar-todas-item__reclamos">{{ ruta.cantidadReclamos || 0 }} recl.</span>
                                          </div>
                                          <div class="supervisor-visualizar-todas-item__fila-cuadrilla">
                                              <span class="supervisor-visualizar-todas-item__cuadrilla" :title="ruta.cuadrilla_nombre || 'Sin asignar'">
                                                  <span class="supervisor-visualizar-todas-item__cuadrilla-ico" aria-hidden="true">👥</span>
                                                  <span class="supervisor-visualizar-todas-item__cuadrilla-nombre">{{ ruta.cuadrilla_nombre || 'Sin asignar' }}</span>
                                              </span>
                                          </div>
                                          <div class="supervisor-visualizar-todas-item__fila-estados">
                                              <span class="badge" :class="claseBadgeEstadoEjecucionRuta(ruta)">
                                                  {{ textoEstadoEjecucionRuta(ruta) }}
                                              </span>
                                              <span
                                                  v-if="esEstadoEjecucionRuta(ruta)"
                                                  class="badge bg-dark cronometro-ruta-supervisor font-monospace"
                                                  :data-inicio-ejecucion-at="ruta.inicio_ejecucion_at || ''"
                                              >{{ tiempoTranscurridoEjecucionSupervisor(ruta) }}</span>
                                          </div>
                                      </div>
                                  </div>
                              </li>
                          </ul>
                      </aside>
                      <div class="supervisor-visualizar-todas-panel-der">
                          <div class="supervisor-ruta-detalle-vista-mapa supervisor-visualizar-todas-vista-mapa">
                              <div class="supervisor-ruta-detalle-vista-toolbar supervisor-ruta-detalle-vista-toolbar--flotante">
                                  <button type="button" class="btn btn-sm btn-success" @click="alternarProveedorRutasActivas">
                                      <i class="bi bi-arrow-repeat text-white"></i>
                                      {{ proveedorMapaRutasActivas === 'google' ? 'Mapbox' : 'Google Maps' }}
                                  </button>
                              </div>
                              <div id="mapaVisualizarRutas" v-show="proveedorMapaRutasActivas === 'google'" class="supervisor-visualizar-todas-modal__mapa"></div>
                              <div id="mapaVisualizarRutasMapbox" v-show="proveedorMapaRutasActivas === 'mapbox'" class="supervisor-visualizar-todas-modal__mapa"></div>
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
          <div class="modal-content" style="border-radius: 24px; overflow: hidden; border: none; box-shadow: 0 10px 40px rgba(6, 4, 75, 0.25);">
              <!-- Header con diseño moderno -->
              <div class="modal-header" style="background: linear-gradient(135deg, #3A3972 0%, #06044B 100%); color: white; border: none; padding: 2rem; position: relative; overflow: hidden;">
                  <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; filter: blur(50px);"></div>
                  <div style="position: absolute; bottom: -30px; left: -30px; width: 150px; height: 150px; background: rgba(255, 255, 255, 0.03); border-radius: 50%; filter: blur(40px);"></div>
                  <h5 class="modal-title" style="font-weight: 800; display: flex; align-items: center; gap: 0.75rem; font-size: 1.5rem; z-index: 1; position: relative;">
                      <div style="background: rgba(255, 255, 255, 0.15); padding: 0.5rem; border-radius: 12px; backdrop-filter: blur(10px);">
                          <i class="bi bi-gear-fill" style="font-size: 1.5rem;"></i>
                      </div>
                      <span style="letter-spacing: 0.3px;">Administrar Hoja de Ruta</span>
                  </h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" @click="cerrarModalAdministrarAsignaciones" style="z-index: 1; position: relative; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"></button>
              </div>
              
              <!-- Body con diseño mejorado -->
              <div class="modal-body" style="padding: 2.5rem; background: linear-gradient(135deg, #FFFFFF 0%, #E0E0E9 100%); min-height: 400px;">
                  <div v-if="rutaSeleccionadaAdmin">
                      <!-- Panel de información de la ruta seleccionada con diseño mejorado -->
                      <div class="mb-4 p-4" style="background: linear-gradient(135deg, #FFFFFF 0%, #F8F9FE 100%); border-radius: 20px; box-shadow: 0 6px 24px rgba(6, 4, 75, 0.12); border: 1px solid rgba(110, 109, 153, 0.1); position: relative; overflow: hidden;">
                          <!-- Decoración de fondo -->
                          <div style="position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; background: radial-gradient(circle, rgba(58, 57, 114, 0.08) 0%, transparent 70%); border-radius: 50%;"></div>
                          
                          <!-- Header del panel con color de ruta prominente -->
                          <div class="d-flex align-items-center gap-3 mb-4 pb-3" style="border-bottom: 2px solid rgba(110, 109, 153, 0.1); position: relative;">
                              <div style="position: relative;">
                                  <div :style="`width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, ${rutaSeleccionadaAdmin.color || '#808080'} 0%, ${rutaSeleccionadaAdmin.color || '#808080'}dd 100%); flex-shrink: 0; box-shadow: 0 4px 16px ${rutaSeleccionadaAdmin.color || '#808080'}40, inset 0 -2px 8px rgba(0,0,0,0.1); display: flex; align-items: center; justify-content: center;`">
                                      <i class="bi bi-map-fill" style="font-size: 1.5rem; color: white; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));"></i>
                                  </div>
                                  <div style="position: absolute; bottom: -4px; right: -4px; width: 20px; height: 20px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                      <i class="bi" :class="rutaSeleccionadaAdmin.asignada == 1 ? 'bi-check-circle-fill' : 'bi-clock-fill'" :style="`color: ${rutaSeleccionadaAdmin.asignada == 1 ? '#28a745' : '#6c757d'}; font-size: 0.9rem;`"></i>
                                  </div>
                              </div>
                              <div class="flex-grow-1">
                                  <h5 class="mb-2" style="font-weight: 800; color: #06044B; font-size: 1.4rem; letter-spacing: 0.2px; line-height: 1.2;">
                                      {{ rutaSeleccionadaAdmin.nombre || 'Sin nombre' }}
                                  </h5>
                                  <div class="d-flex flex-wrap gap-3">
                                      <div style="background: rgba(110, 109, 153, 0.08); padding: 0.35rem 0.75rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.4rem;">
                                          <i class="bi bi-file-earmark-text-fill" style="color: #6E6D99; font-size: 0.9rem;"></i>
                                          <span style="color: #06044B; font-weight: 600; font-size: 0.85rem;">{{ rutaSeleccionadaAdmin.cantidadReclamos }} reclamos</span>
                                      </div>
                                      <div style="background: rgba(110, 109, 153, 0.08); padding: 0.35rem 0.75rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.4rem;">
                                          <i class="bi bi-clock-fill" style="color: #6E6D99; font-size: 0.9rem;"></i>
                                          <span style="color: #06044B; font-weight: 600; font-size: 0.85rem;">{{ rutaSeleccionadaAdmin.tiempoEstimado }}</span>
                                      </div>
                                      <div style="background: rgba(110, 109, 153, 0.08); padding: 0.35rem 0.75rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.4rem;">
                                          <i class="bi bi-calendar-check-fill" style="color: #6E6D99; font-size: 0.9rem;"></i>
                                          <span style="color: #06044B; font-weight: 600; font-size: 0.85rem;">{{ formatearFecha(rutaSeleccionadaAdmin.fecha) }}</span>
                                      </div>
                                  </div>
                              </div>
                          </div>
                          
                          <!-- Estado de asignación mejorado -->
                          <div class="alert mb-0" :class="rutaSeleccionadaAdmin.asignada == 1 ? 'alert-success' : 'alert-secondary'" 
                               style="border-radius: 14px; border: none; padding: 1rem 1.25rem; background: linear-gradient(135deg, rgba(40, 167, 69, 0.12) 0%, rgba(32, 201, 151, 0.08) 100%); box-shadow: 0 2px 8px rgba(40, 167, 69, 0.15);"
                               :style="rutaSeleccionadaAdmin.asignada != 1 ? 'background: linear-gradient(135deg, rgba(108, 117, 125, 0.12) 0%, rgba(108, 117, 125, 0.08) 100%); box-shadow: 0 2px 8px rgba(108, 117, 125, 0.15);' : ''">
                              <div class="d-flex align-items-center gap-3">
                                  <div style="background: white; border-radius: 12px; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                      <i class="bi" :class="rutaSeleccionadaAdmin.asignada == 1 ? 'bi-check-circle-fill' : 'bi-info-circle-fill'" 
                                         :style="`font-size: 1.5rem; color: ${rutaSeleccionadaAdmin.asignada == 1 ? '#28a745' : '#6c757d'};`"></i>
                                  </div>
                                  <div class="flex-grow-1">
                                      <strong v-if="rutaSeleccionadaAdmin.asignada == 1" style="color: #155724; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem;">
                                          <span>Asignada a:</span>
                                          <span style="background: rgba(40, 167, 69, 0.2); padding: 0.25rem 0.75rem; border-radius: 8px; font-weight: 700;">
                                              <i class="bi bi-people-fill"></i> {{ rutaSeleccionadaAdmin.cuadrilla_nombre }}
                                          </span>
                                      </strong>
                                      <strong v-else style="color: #383d41; font-size: 1.05rem;">
                                          <i class="bi bi-info-circle"></i> Esta hoja de ruta aún no está asignada a ninguna cuadrilla
                                      </strong>
                                  </div>
                              </div>
                          </div>
                      </div>

                      <!-- Título de sección -->
                      <div class="mb-3 px-1">
                          <h6 style="color: #06044B; font-weight: 700; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">
                              <i class="bi bi-lightning-charge-fill"></i> Acciones Disponibles
                          </h6>
                      </div>

                      <!-- Botones de acción mejorados -->
                      <div class="d-flex flex-column gap-3">
                          <!-- Asignar o Reasignar (solo si aún no está en ejecución) -->
                          <button
                              v-if="puedeAsignarOCambiarCuadrillaRuta(rutaSeleccionadaAdmin)"
                              class="btn btn-lg btn-accion-admin" 
                              style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; font-weight: 700; padding: 1.25rem 1.5rem; border-radius: 16px; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.35); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; text-align: left;"
                              @click="abrirModalAsignarRutaDesdeAdmin(rutaSeleccionadaAdmin.id)"
                              @mouseover="$event.target.style.transform = 'translateY(-3px)'; $event.target.style.boxShadow = '0 8px 24px rgba(40, 167, 69, 0.45)'"
                              @mouseout="$event.target.style.transform = 'translateY(0)'; $event.target.style.boxShadow = '0 4px 12px rgba(40, 167, 69, 0.35)'">
                              <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 100%);"></div>
                              <div class="d-flex align-items-center gap-3" style="position: relative;">
                                  <div style="background: rgba(255, 255, 255, 0.25); border-radius: 12px; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                      <i class="bi" :class="rutaSeleccionadaAdmin.asignada == 1 ? 'bi-arrow-repeat' : 'bi-people-fill'" style="font-size: 1.5rem;"></i>
                                  </div>
                                  <div class="flex-grow-1">
                                      <div style="font-size: 1.1rem; letter-spacing: 0.3px;">
                                          {{ rutaSeleccionadaAdmin.asignada == 1 ? 'Reasignar a Otra Cuadrilla' : 'Asignar a Cuadrilla' }}
                                      </div>
                                      <small style="opacity: 0.9; font-weight: 500; font-size: 0.85rem;">
                                          {{ rutaSeleccionadaAdmin.asignada == 1 ? 'Cambiar la cuadrilla asignada a esta ruta' : 'Asignar esta ruta a una cuadrilla de trabajo' }}
                                      </small>
                                  </div>
                                  <i class="bi bi-chevron-right" style="font-size: 1.2rem; opacity: 0.7;"></i>
                              </div>
                          </button>

                          <!-- Desasignar (solo si está asignada) -->
                          <button 
                              v-if="rutaSeleccionadaAdmin.asignada == 1"
                              class="btn btn-lg btn-accion-admin" 
                              style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: white; border: none; font-weight: 700; padding: 1.25rem 1.5rem; border-radius: 16px; box-shadow: 0 4px 12px rgba(255, 193, 7, 0.35); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; text-align: left;"
                              @click="desasignarRutaDesdeAdmin(rutaSeleccionadaAdmin.id)"
                              @mouseover="$event.target.style.transform = 'translateY(-3px)'; $event.target.style.boxShadow = '0 8px 24px rgba(255, 193, 7, 0.45)'"
                              @mouseout="$event.target.style.transform = 'translateY(0)'; $event.target.style.boxShadow = '0 4px 12px rgba(255, 193, 7, 0.35)'">
                              <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 100%);"></div>
                              <div class="d-flex align-items-center gap-3" style="position: relative;">
                                  <div style="background: rgba(255, 255, 255, 0.25); border-radius: 12px; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                      <i class="bi bi-x-circle-fill" style="font-size: 1.5rem;"></i>
                                  </div>
                                  <div class="flex-grow-1">
                                      <div style="font-size: 1.1rem; letter-spacing: 0.3px;">Desasignar de Cuadrilla</div>
                                      <small style="opacity: 0.9; font-weight: 500; font-size: 0.85rem;">Liberar esta ruta de su cuadrilla actual</small>
                                  </div>
                                  <i class="bi bi-chevron-right" style="font-size: 1.2rem; opacity: 0.7;"></i>
                              </div>
                          </button>

                          <!-- Eliminar ruta -->
                          <button 
                              class="btn btn-lg btn-accion-admin" 
                              style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; font-weight: 700; padding: 1.25rem 1.5rem; border-radius: 16px; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.35); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; text-align: left;"
                              @click="eliminarRutaDesdeAdmin(rutaSeleccionadaAdmin.id)"
                              @mouseover="$event.target.style.transform = 'translateY(-3px)'; $event.target.style.boxShadow = '0 8px 24px rgba(220, 53, 69, 0.45)'"
                              @mouseout="$event.target.style.transform = 'translateY(0)'; $event.target.style.boxShadow = '0 4px 12px rgba(220, 53, 69, 0.35)'">
                              <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, transparent 100%);"></div>
                              <div class="d-flex align-items-center gap-3" style="position: relative;">
                                  <div style="background: rgba(255, 255, 255, 0.25); border-radius: 12px; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                      <i class="bi bi-trash-fill" style="font-size: 1.5rem;"></i>
                                  </div>
                                  <div class="flex-grow-1">
                                      <div style="font-size: 1.1rem; letter-spacing: 0.3px;">Eliminar Hoja de Ruta</div>
                                      <small style="opacity: 0.9; font-weight: 500; font-size: 0.85rem;">Esta acción no se puede deshacer</small>
                                  </div>
                                  <i class="bi bi-chevron-right" style="font-size: 1.2rem; opacity: 0.7;"></i>
                              </div>
                          </button>
                      </div>
                  </div>
                  
                  <!-- Estado sin ruta seleccionada mejorado -->
                  <div v-else class="p-5 text-center" style="background: linear-gradient(135deg, #FFFFFF 0%, #F8F9FE 100%); border-radius: 20px; box-shadow: 0 6px 24px rgba(6, 4, 75, 0.12); border: 2px dashed rgba(110, 109, 153, 0.2);">
                      <div style="background: linear-gradient(135deg, rgba(168, 168, 197, 0.15) 0%, rgba(110, 109, 153, 0.1) 100%); width: 96px; height: 96px; border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                          <i class="bi bi-info-circle" style="font-size: 3rem; color: #A8A8C5;"></i>
                      </div>
                      <p class="mt-3 mb-1" style="color: #06044B; font-size: 1.3rem; font-weight: 700;">No hay ruta seleccionada</p>
                      <p class="mb-0" style="color: #6E6D99; font-size: 1rem; font-weight: 500;">Seleccione una ruta de la tabla para administrarla</p>
                  </div>
              </div>
              
              <!-- Footer mejorado -->
              <div class="modal-footer" style="border: none; background: linear-gradient(135deg, #FFFFFF 0%, #E0E0E9 100%); padding: 1.5rem 2.5rem; border-top: 1px solid rgba(110, 109, 153, 0.1);">
                  <button type="button" class="btn" data-bs-dismiss="modal" @click="cerrarModalAdministrarAsignaciones" 
                          style="background: linear-gradient(135deg, #6E6D99 0%, #3A3972 100%); color: white; border: none; padding: 0.75rem 2rem; border-radius: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; box-shadow: 0 3px 10px rgba(6, 4, 75, 0.2); transition: all 0.3s ease; font-size: 0.9rem;"
                          @mouseover="$event.target.style.transform = 'translateY(-2px)'; $event.target.style.boxShadow = '0 5px 16px rgba(6, 4, 75, 0.3)'"
                          @mouseout="$event.target.style.transform = 'translateY(0)'; $event.target.style.boxShadow = '0 3px 10px rgba(6, 4, 75, 0.2)'">
                      <i class="bi bi-x-circle-fill"></i> Cerrar
                  </button>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal materiales (supervisor, solo lectura) -->
  <div class="modal fade" id="modalMaterialesSupervisor" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title"><i class="bi bi-box-seam me-1"></i> Materiales — Reclamo #{{ reclamoSupervisorModal.municipalidad_id }}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
              </div>
              <div class="modal-body">
                  <div v-if="cargandoMaterialesSupervisor" class="text-center py-3 text-muted">
                      <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                      <span class="ms-2">Cargando…</span>
                  </div>
                  <div v-else-if="!historialMaterialesSupervisor.length" class="alert alert-light border mb-0">
                      No hay materiales registrados para este reclamo.
                  </div>
                  <div v-else class="table-responsive">
                      <table class="table table-sm table-hover mb-0">
                          <thead class="table-light">
                              <tr>
                                  <th>Material</th>
                                  <th>Cantidad</th>
                                  <th>Fecha</th>
                                  <th>Usuario</th>
                                  <th>Observación</th>
                              </tr>
                          </thead>
                          <tbody>
                              <tr v-for="item in historialMaterialesSupervisor" :key="item.id">
                                  <td>{{ item.material_nombre || '—' }}</td>
                                  <td>{{ item.cantidad != null ? item.cantidad : '—' }}</td>
                                  <td>{{ formatearFecha(item.fecha) }}</td>
                                  <td>{{ item.usuario_nombre || '—' }}</td>
                                  <td>{{ item.observacion || '—' }}</td>
                              </tr>
                          </tbody>
                      </table>
                  </div>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal observaciones ejecución (supervisor, solo lectura) -->
  <div class="modal fade" id="modalObservacionesSupervisor" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title"><i class="bi bi-chat-square-text me-1"></i> Observaciones — Reclamo #{{ reclamoSupervisorModal.municipalidad_id }}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
              </div>
              <div class="modal-body">
                  <div v-if="cargandoObservacionesSupervisor" class="text-center py-3 text-muted">
                      <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                      <span class="ms-2">Cargando…</span>
                  </div>
                  <div v-else-if="!historialObservacionesSupervisor.length" class="alert alert-light border mb-0">
                      Aún no hay observaciones registradas para este reclamo.
                  </div>
                  <ul v-else class="list-group list-group-flush">
                      <li v-for="o in historialObservacionesSupervisor" :key="o.id" class="list-group-item px-0 py-3">
                          <div class="d-flex justify-content-between align-items-start gap-2 mb-1 flex-wrap">
                              <span class="text-muted small">{{ formatearFecha(o.created_at) }}</span>
                              <span class="d-flex flex-wrap gap-1 justify-content-end">
                                  <span v-if="o.ruta_nombre" class="badge" :style="{ backgroundColor: o.ruta_color || '#6c757d', color: '#fff' }">{{ o.ruta_nombre }}</span>
                                  <span class="badge bg-secondary">{{ o.usuario_nombre || '—' }}</span>
                              </span>
                          </div>
                          <p class="mb-0 text-break">{{ o.texto }}</p>
                      </li>
                  </ul>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
              </div>
          </div>
      </div>
  </div>


</div>
</div>