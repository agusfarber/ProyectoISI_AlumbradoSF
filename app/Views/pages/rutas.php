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
    
    <div>
          Gestión de Hojas de Ruta
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
       <div class="d-flex gap-2">
             <button class="btn btn-primary" @click="abrirModalCrearRuta">
                <i class="bi bi-plus-circle text-white"></i> Nueva Hoja de Ruta
             </button>
             <button class="btn btn-success" @click="abrirModalVisualizarRutas">
                <i class="bi bi-map text-white"></i> Visualizar Rutas Activas
             </button>
       </div>
    </div>

  <!-- Tabla de hojas de ruta -->
  <div class="table-responsive">
      <table id="tabla_rutas" class="table table-bordered table-hover w-100">
          <thead>
              <tr>
                  <th>Nombre</th>
                  <th>Cantidad de Reclamos</th>
                  <th>Tiempo Estimado</th>
                  <th>Estado</th>
                  <th>Fecha de Creación</th>
                  <th>Acciones</th>
              </tr>
          </thead>
          <tbody>
              <!-- Contenido de la tabla gestionado por DataTables -->
          </tbody>
      </table>
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
                          <i class="bi bi-info-circle"></i> Info
                      </button>
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" @click="resetearModal"></button>
              </div>
              <div class="modal-body">
                  <!-- PASO 1: Nombre y cantidad de reclamos -->
                  <div v-if="!vistaPrevia.activa">
                      <div class="card">
                          <div class="card-body">
                              <!-- Nombre de la hoja de ruta -->
                              <div class="mb-3">
                                  <label for="nombreRuta" class="form-label">Nombre de la Hoja de Ruta</label>
                                  <input type="text" id="nombreRuta" class="form-control form-control-lg" 
                                         v-model="nuevaRuta.nombre" 
                                         placeholder="Ingrese un nombre para la hoja de ruta"
                                         required>
                              </div>

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

                  <!-- PASO 2: Vista Previa con Mapa -->
                  <div v-if="vistaPrevia.activa">
                      <!-- Mensaje de ayuda en modo edición -->
                      <div v-if="modoEdicion" class="alert alert-info mb-3">
                          <i class="bi bi-info-circle"></i>
                          <strong>Modo Edición Activo:</strong>
                          <ul class="mb-0 mt-2">
                              <li>Haga clic en los reclamos del mapa para <strong>agregarlos</strong> a la ruta</li>
                              <li>Use los botones de la lista para <strong>reordenar</strong> o <strong>eliminar</strong> reclamos</li>
                              <li>Los cambios se reflejan en tiempo real en el mapa</li>
                          </ul>
                      </div>
                      
                      <div class="row">
                          <!-- Panel de información a la izquierda -->
                          <div class="col-md-4">
                              <div class="card h-100">
                                  <div class="card-header py-1">
                                      <small class="mb-0"><strong>Reclamos en la Ruta</strong></small>
                                  </div>
                                  <div class="list-group list-group-flush" style="height: 500px; overflow-y: auto;">
                                      <div v-for="(reclamo, index) in vistaPrevia.rutaOptimizada" 
                                           :key="reclamo.id" 
                                           class="list-group-item py-1 px-2">
                                          <div class="d-flex align-items-center justify-content-between">
                                              <div class="d-flex align-items-center flex-grow-1">
                                                  <span class="badge bg-dark me-2" style="font-size: 0.7rem;">{{ index + 1 }}</span>
                                                  <div style="font-size: 0.85rem;">
                                                      <strong>{{ reclamo.municipalidad_id }}</strong><br>
                                                      <small class="text-muted" style="font-size: 0.75rem;">{{ reclamo.municipalidad_motivo }}</small>
                                                  </div>
                                              </div>
                                              <!-- Botones de edición (solo en modo edición) -->
                                              <div v-if="modoEdicion" class="d-flex gap-1">
                                                  <button class="btn btn-sm btn-outline-secondary" 
                                                          @click="moverReclamoArriba(index)"
                                                          :disabled="index === 0"
                                                          title="Mover arriba">
                                                      <i class="bi bi-arrow-up" style="font-size: 0.7rem;"></i>
                                                  </button>
                                                  <button class="btn btn-sm btn-outline-secondary" 
                                                          @click="moverReclamoAbajo(index)"
                                                          :disabled="index === vistaPrevia.rutaOptimizada.length - 1"
                                                          title="Mover abajo">
                                                      <i class="bi bi-arrow-down" style="font-size: 0.7rem;"></i>
                                                  </button>
                                                  <button class="btn btn-sm btn-outline-danger" 
                                                          @click="eliminarReclamoDeRuta(index)"
                                                          title="Eliminar de la ruta">
                                                      <i class="bi bi-trash" style="font-size: 0.7rem;"></i>
                                                  </button>
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
                                          <i class="bi bi-geo-alt"></i> Vista Previa del Recorrido
                                      </h6>
                                      <button class="btn btn-sm btn-success" @click="alternarProveedorVistaPrevia">
                                          <i class="bi bi-arrow-repeat text-white"></i> 
                                          {{ proveedorMapaVistaPrevia === 'google' ? 'Cambiar a Mapbox' : 'Cambiar a Google Maps' }}
                                      </button>
                                  </div>
                                  <div class="card-body p-0">
                                      <div id="mapaCrearRuta" v-show="proveedorMapaVistaPrevia === 'google'" style="width: 100%; height: 500px;"></div>
                                      <div id="mapaCrearRutaMapbox" v-show="proveedorMapaVistaPrevia === 'mapbox'" style="width: 100%; height: 500px;"></div>
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
                      <i class="bi bi-eye"></i> Generar Vista Previa
                  </button>
                  
                  <!-- Botón para volver a configurar (cuando vista previa está activa) -->
                  <button v-if="vistaPrevia.activa && !modoEdicion" 
                          type="button" 
                          class="btn btn-outline-secondary" 
                          @click="volverAConfigurar">
                      <i class="bi bi-arrow-left"></i> Cambiar Cantidad
                  </button>
                  
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
                      <i class="bi bi-check-circle"></i> {{ modoEdicion ? 'Crear Ruta Editada' : 'Crear Ruta Automática' }}
                  </button>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal para ver hoja de ruta -->
  <div class="modal fade" id="modalVerRuta" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-xl">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title">
                      <i class="bi bi-map"></i> {{ rutaVisualizando.nombre || 'Hoja de Ruta' }}
                      <span v-if="rutaVisualizando.activa == 1" class="badge bg-success ms-2">Activa</span>
                      <span v-else class="badge bg-secondary ms-2">Inactiva</span>
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
                                      <p class='mb-0'><small><strong>Estado:</strong> ${rutaVisualizando.activa == 1 ? 'Activa' : 'Inactiva'}</small></p>
                                  </div>
                              `">
                          <i class="bi bi-info-circle"></i> Info
                      </button>
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" @click="cerrarVisualizacion"></button>
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
                                          <div style="font-size: 0.85rem;">
                                              <strong>{{ reclamo.municipalidad_id }}</strong><br>
                                              <small class="text-muted" style="font-size: 0.75rem;">{{ reclamo.municipalidad_motivo }}</small>
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
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="cerrarVisualizacion">
                      Cerrar
                  </button>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal para visualizar todas las rutas activas -->
  <div class="modal fade" id="modalVisualizarRutas" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-xl">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title">
                      <i class="bi bi-map"></i> Rutas Activas
                      <span class="badge bg-success ms-2">{{ rutasActivas.length }} Activa(s)</span>
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" @click="cerrarVisualizacionRutas"></button>
              </div>
              <div class="modal-body">
                  <div class="row">
                      <!-- Panel de información a la izquierda -->
                      <div class="col-md-4">
                          <div class="card h-100">
                              <div class="card-header py-2">
                                  <small class="mb-0"><strong>Hojas de Ruta Activas</strong></small>
                              </div>
                              <div class="list-group list-group-flush" style="height: 500px; overflow-y: auto;">
                                  <div v-if="rutasActivas.length === 0" class="p-3 text-center text-muted">
                                      <i class="bi bi-info-circle" style="font-size: 2rem;"></i>
                                      <p class="mt-2">No hay rutas activas</p>
                                  </div>
                                  <div v-for="ruta in rutasActivas" 
                                       :key="ruta.id" 
                                       class="list-group-item py-2 px-3"
                                       style="cursor: pointer;"
                                       @click="centrarEnRutaActiva(ruta)">
                                      <div class="d-flex align-items-center gap-2">
                                          <div :style="`width: 16px; height: 16px; border-radius: 50%; background-color: ${ruta.color || '#808080'}; border: 2px solid #dee2e6; flex-shrink: 0;`"></div>
                                          <div style="font-size: 0.9rem;">
                                              <strong>{{ ruta.nombre || 'Sin nombre' }}</strong>
                                              <br>
                                              <small class="text-muted">{{ ruta.cantidadReclamos }} reclamo(s)</small>
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
                                      <i class="bi bi-geo-alt"></i> Visualización de Todas las Rutas
                                  </h6>
                                  <button class="btn btn-sm btn-success" @click="alternarProveedorRutasActivas">
                                      <i class="bi bi-arrow-repeat text-white"></i> 
                                      {{ proveedorMapaRutasActivas === 'google' ? 'Cambiar a Mapbox' : 'Cambiar a Google Maps' }}
                                  </button>
                              </div>
                              <div class="card-body p-0">
                                  <div id="mapaVisualizarRutas" v-show="proveedorMapaRutasActivas === 'google'" style="width: 100%; height: 500px;"></div>
                                  <div id="mapaVisualizarRutasMapbox" v-show="proveedorMapaRutasActivas === 'mapbox'" style="width: 100%; height: 500px;"></div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="cerrarVisualizacionRutas">
                      Cerrar
                  </button>
              </div>
          </div>
      </div>
  </div>

</div>
</div>