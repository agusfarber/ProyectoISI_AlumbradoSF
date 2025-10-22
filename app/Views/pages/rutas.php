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

  <!-- Tabla de hojas de ruta -->
  <div class="table-responsive">
      <table id="tabla_rutas" class="table table-bordered table-hover w-100">
          <thead>
              <tr>
                  <th>Nombre</th>
                  <th>Cantidad de Reclamos</th>
                  <th>Tiempo Estimado</th>
                  <th>Asignación</th>
                  <th>Fecha de Creación</th>
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
                          <i class="bi bi-info-circle"></i>
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
                          <option v-for="cuadrilla in cuadrillasDisponibles" :key="cuadrilla.id" :value="cuadrilla.id">
                              {{ cuadrilla.nombre }} - {{ cuadrilla.descripcion || 'Sin descripción' }}
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
      <div class="modal-dialog modal-xl">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title">
                      <i class="bi bi-map"></i> Todas las Hojas de Ruta
                      <span class="badge bg-primary ms-2">{{ rutasActivas.length }} Ruta(s)</span>
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" @click="cerrarVisualizacionRutas"></button>
              </div>
              <div class="modal-body">
                  <div class="row">
                      <!-- Panel de información a la izquierda -->
                      <div class="col-md-4">
                          <div class="card h-100">
                              <div class="card-header py-2">
                                  <small class="mb-0"><strong>Todas las Hojas de Ruta</strong></small>
                              </div>
                              <div class="list-group list-group-flush" style="height: 500px; overflow-y: auto;">
                                  <div v-if="rutasActivas.length === 0" class="p-3 text-center text-muted">
                                      <i class="bi bi-info-circle" style="font-size: 2rem;"></i>
                                      <p class="mt-2">No hay rutas creadas</p>
                                  </div>
                                  <div v-for="ruta in rutasActivas" 
                                       :key="ruta.id" 
                                       class="list-group-item py-2 px-3"
                                       style="cursor: pointer;"
                                       @click="centrarEnRutaActiva(ruta)">
                                      <div class="d-flex align-items-center justify-content-between gap-2">
                                          <div class="d-flex align-items-center gap-2">
                                              <div :style="`width: 16px; height: 16px; border-radius: 50%; background-color: ${ruta.color || '#808080'}; border: 2px solid #dee2e6; flex-shrink: 0;`"></div>
                                              <div style="font-size: 0.9rem;">
                                                  <strong>{{ ruta.nombre || 'Sin nombre' }}</strong>
                                                  <br>
                                                  <small class="text-muted">{{ ruta.cantidadReclamos }} reclamo(s)</small>
                                              </div>
                                          </div>
                                          <span v-if="ruta.asignada == 1" class="badge bg-success" style="font-size: 0.7rem;">Asignada</span>
                                          <span v-else class="badge bg-secondary" style="font-size: 0.7rem;">No Asignada</span>
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
                          <!-- Asignar o Reasignar -->
                          <button 
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


</div>
</div>