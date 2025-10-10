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
       </div>
    </div>

  <!-- Tabla de hojas de ruta -->
  <div class="table-responsive">
      <table id="tabla_rutas" class="table table-bordered table-hover w-100">
          <thead>
              <tr>
                  <th>ID</th>
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
                  <!-- PASO 1: Solo cantidad de reclamos -->
                  <div v-if="!vistaPrevia.activa">
                      <div class="card">
                          <div class="card-body">
                              <!-- Cantidad de reclamos -->
                              <div class="mb-3">
                                  <label for="cantidadReclamos" class="form-label">¿Cuántos reclamos desea incluir en la hoja de ruta? *</label>
                                  <input type="number" id="cantidadReclamos" class="form-control form-control-lg" 
                                         v-model.number="nuevaRuta.cantidadReclamos" 
                                         :min="1" 
                                         :max="reclamosDisponibles" 
                                         required>
                                  <div class="form-text">
                                      Reclamos disponibles (sin estado "Completado"): <strong>{{ reclamosDisponibles }}</strong>
                                  </div>
                              </div>

                              <!-- Información de prioridades -->
                              <div class="alert alert-info">
                                  <h6 class="mb-2">
                                      <i class="bi bi-info-circle"></i> Distribución de Reclamos por Prioridad
                                  </h6>
                                  <div class="row text-center">
                                      <div class="col-4">
                                          <span class="badge bg-danger mb-1">Alta</span><br>
                                          <strong class="fs-4">{{ contarPorPrioridad('Alta') }}</strong>
                                      </div>
                                      <div class="col-4">
                                          <span class="badge bg-warning mb-1">Media</span><br>
                                          <strong class="fs-4">{{ contarPorPrioridad('Media') }}</strong>
                                      </div>
                                      <div class="col-4">
                                          <span class="badge bg-success mb-1">Baja</span><br>
                                          <strong class="fs-4">{{ contarPorPrioridad('Baja') }}</strong>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>

                  <!-- PASO 2: Vista Previa con Mapa -->
                  <div v-if="vistaPrevia.activa">
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
                                          <div class="d-flex align-items-center">
                                              <span class="badge bg-dark me-2" style="font-size: 0.7rem;">{{ index + 1 }}</span>
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
                                  <div class="card-header">
                                      <h6 class="mb-0">
                                          <i class="bi bi-geo-alt"></i> Vista Previa del Recorrido
                                      </h6>
                                  </div>
                                  <div class="card-body p-0">
                                      <div id="mapaCrearRuta" style="width: 100%; height: 500px;"></div>
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
                  <button v-if="vistaPrevia.activa" 
                          type="button" 
                          class="btn btn-outline-secondary" 
                          @click="volverAConfigurar">
                      <i class="bi bi-arrow-left"></i> Cambiar Cantidad
                  </button>
                  
                  <!-- Botón Crear Ruta (solo cuando vista previa está activa) -->
                  <button v-if="vistaPrevia.activa" 
                          type="button" 
                          class="btn btn-success" 
                          @click="crearRutaAutomatica" 
                          :disabled="!puedeGenerarRuta">
                      <i class="bi bi-check-circle"></i> Crear Ruta Automática
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
                      <i class="bi bi-map"></i> Hoja de Ruta #{{ rutaVisualizando.id }}
                      <span v-if="rutaVisualizando.activa == 1" class="badge bg-success ms-2">Activa</span>
                      <span v-else class="badge bg-secondary ms-2">Inactiva</span>
                      <button type="button" 
                              class="btn btn-sm btn-outline-info ms-2" 
                              data-bs-toggle="popover"
                              data-bs-placement="bottom"
                              data-bs-html="true"
                              :data-bs-content="`
                                  <div class='text-start'>
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
                              <div class="card-header">
                                  <h6 class="mb-0">
                                      <i class="bi bi-geo-alt"></i> Visualización del Recorrido
                                  </h6>
                              </div>
                              <div class="card-body p-0">
                                  <div id="mapaVerRuta" style="width: 100%; height: 500px;"></div>
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

</div>
</div>