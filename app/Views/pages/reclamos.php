<div id="app" class="container-fluid">
    <div>Gestión de Reclamos</div>

    <!-- Botones principal, filtros y sincronización -->
    <div class="d-flex justify-content-between mb-3">
        <!-- Botón a la izquierda 
        <button class="btn btn-primary mb-3" @click="abrirFormulario()">+ Nuevo Reclamo</button-->

        <!-- Botones a la derecha -->
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary mb-3" data-bs-toggle="collapse" data-bs-target="#filtrosPanel">
                <i class="bi bi-funnel"></i> Filtros
            </button>
            <button class="btn btn-outline-primary mb-3" data-bs-toggle="collapse" data-bs-target="#sincronizacionAvanzadaPanel">
                <i class="bi bi-sliders"></i> Opciones Avanzadas de Sincronización
            </button>
        </div>
    </div>

    <!-- Sincronización Rápida de Pendientes (visible siempre) -->
    <div class="alert mb-3" :class="sincronizando ? 'alert-primary' : 'alert-info'">
        <!-- Mostrar botón cuando NO está sincronizando -->
        <div v-if="!sincronizando" class="d-flex align-items-center justify-content-between">
            <div>
                <h6 class="mb-1"><i class="bi bi-lightning-charge"></i> Sincronización Rápida</h6>
                <small>Sincroniza automáticamente todos los reclamos pendientes desde el último guardado hasta hoy</small>
            </div>
            <button class="btn btn-success btn-lg" @click="sincronizarReclamosHoy" :disabled="!tokenDisponible">
                <i class="bi bi-arrow-repeat text-white"></i> Sincronizar Pendientes
            </button>
        </div>
        
        <!-- Mostrar progreso cuando SÍ está sincronizando -->
        <div v-else class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="spinner-border spinner-border-sm me-3" role="status">
                    <span class="visually-hidden">Procesando...</span>
                </div>
                <div>
                    <strong>Procesando reclamos:</strong>
                    <span class="ms-2 badge bg-primary fs-6">{{ progresoActual }} / {{ progresoTotal }}</span>
                </div>
            </div>
            <button class="btn btn-danger" @click="detenerSincronizacionEnCurso" :disabled="detenerSincronizacion">
                <i class="bi bi-stop-circle text-white"></i> 
                <span v-if="!detenerSincronizacion">Detener Sincronización</span>
                <span v-else>Deteniendo...</span>
            </button>
        </div>
    </div>

    <!-- Panel de Filtros colapsable -->
    <div class="collapse mb-3" id="filtrosPanel">
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
            <!-- Nuevo filtro para prioridad -->
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

    <!-- Panel de Opciones Avanzadas de Sincronización colapsable -->
    <div class="collapse mb-3" id="sincronizacionAvanzadaPanel">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-sliders"></i> Opciones Avanzadas de Sincronización</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Sincronización masiva por fechas -->
                    <div class="col-md-6">
                        <h6><i class="bi bi-calendar-range"></i> Sincronizar por Rango de Fechas</h6>
                        <p class="text-muted small">Sincroniza reclamos en un período específico</p>
                        <div class="mb-3">
                            <label for="syncFechaDesde" class="form-label">Fecha Desde</label>
                            <input type="date" id="syncFechaDesde" class="form-control" v-model="syncFechaDesde">
                        </div>
                        <div class="mb-3">
                            <label for="syncFechaHasta" class="form-label">Fecha Hasta</label>
                            <input type="date" id="syncFechaHasta" class="form-control" v-model="syncFechaHasta">
                        </div>
                        <button class="btn btn-primary w-100" @click="sincronizarReclamosPorFechas" :disabled="!tokenDisponible || sincronizando">
                            <i class="bi bi-download text-white"></i> Sincronizar por Fechas
                        </button>
                    </div>

                    <!-- Sincronización de reclamo específico -->
                    <div class="col-md-6">
                        <h6><i class="bi bi-search"></i> Sincronizar Reclamo Específico</h6>
                        <p class="text-muted small">Busca y sincroniza un reclamo por su número</p>
                        <div class="mb-3">
                            <label for="numeroReclamo" class="form-label">Número de Reclamo</label>
                            <input type="number" id="numeroReclamo" class="form-control" v-model="numeroReclamo" placeholder="Ej: 12345">
                        </div>
                        <button class="btn btn-info w-100" @click="sincronizarReclamoEspecifico" :disabled="!tokenDisponible || !numeroReclamo || sincronizando">
                            <i class="bi bi-search text-white"></i> Buscar y Sincronizar
                        </button>
                    </div>
                </div>

                <!-- Estado del token -->
                <div class="mt-4">
                    <div v-if="tokenDisponible" class="alert alert-success mb-0">
                        <i class="bi bi-check-circle"></i> Token disponible para sincronización
                    </div>
                    <div v-else class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i> No hay token disponible.
                        <a href="/token103" class="alert-link">Configure un token en la página de Tokens</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de reclamos -->
    <div class="table-responsive">
        <table id="tabla_reclamos" class="table table-bordered table-hover w-100">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Motivo</th>
                    <th>Fecha de Inicio</th>
                    <th>Fecha de Modificación</th>
                    <th>Estado</th>
                    <th>Domicilio</th>
                    <th>Número</th>
                    <!--th>Acciones</th-->
                </tr>
            </thead>
            <tbody>
                <!-- Contenido de la tabla gestionado por DataTables -->
            </tbody>
        </table>
    </div>

    <!-- Modal Reclamo -->
    <div class="modal fade" id="modalReclamo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form @submit.prevent="guardarReclamo">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ reclamo.id ? 'Editar Reclamo' : 'Nuevo Reclamo' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label>ID Municipalidad</label>
                                    <input type="text" class="form-control" v-model="reclamo.municipalidad_id" required>
                                </div>
                                <div class="mb-2">
                                    <label>Tipo</label>
                                    <input type="text" class="form-control" v-model="reclamo.municipalidad_tipo" readonly>
                                </div>
                                <div class="mb-2">
                                    <label>Motivo</label>
                                    <select class="form-control" v-model="reclamo.municipalidad_motivo" required>
                                        <option value="" disabled>Seleccionar motivo</option>
                                        <option value="Luminaria agotada (Prende y Apaga)">Luminaria agotada (Prende y Apaga)</option>
                                        <option value="Postes, cables caídos o por caer (Telecom, Epec, Monet)">Postes, cables caídos o por caer (Telecom, Epec, Monet)</option>
                                        <option value="Semáforos - Arreglo y sincronización">Semáforos - Arreglo y sincronización</option>
                                        <option value="Luminarias quemadas o rotas">Luminarias quemadas o rotas</option>
                                        <option value="Corte de ramas que tocan cables de alumbrado">Corte de ramas que tocan cables de alumbrado</option>
                                        <option value="Columnas de alumbrado caídas o por caer">Columnas de alumbrado caídas o por caer</option>
                                        <option value="Cables de alumbrado caídos">Cables de alumbrado caídos</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label>Fecha de Inicio</label>
                                    <input type="datetime-local" class="form-control" v-model="reclamo.municipalidad_fechaInicio" required>
                                </div>
                                <div class="mb-2">
                                    <label>Fecha de Modificación</label>
                                    <input type="datetime-local" class="form-control" v-model="reclamo.municipalidad_fechaModificacion" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label>Recepción</label>
                                    <select class="form-control" v-model="reclamo.municipalidad_recepcion" required>
                                        <option value="" disabled>Seleccionar recepción</option>
                                        <option value="llamada">Llamada</option>
                                        <option value="web">Web</option>
                                        <option value="whatsApp">WhatsApp</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label>Estado</label>
                                    <select class="form-control" v-model="reclamo.municipalidad_estado" required>
                                        <option value="" disabled>Seleccionar estado</option>
                                        <option value="Recibido">Recibido</option>
                                        <option value="Asignado">Asignado</option>
                                        <option value="En ejecución">En ejecución</option>
                                        <option value="Completado">Completado</option>
                                        <option value="En plan">En plan</option>
                                        <option value="Error de datos">Error de datos</option>
                                    </select>
                                </div>
                                <!-- Nuevo campo para la prioridad, ahora 'prioridad' -->
                                <div class="mb-2">
                                    <label>Prioridad</label>
                                    <select class="form-control" v-model="reclamo.prioridad" required>
                                        <option value="" disabled>Seleccionar prioridad</option>
                                        <option value="Baja">Baja</option>
                                        <option value="Alta">Alta</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label>Teléfono</label>
                                    <input type="text" class="form-control" v-model="reclamo.municipalidad_telefono">
                                </div>
                                <div class="mb-2">
                                    <label>Domicilio</label>
                                    <input type="text" class="form-control" v-model="reclamo.municipalidad_domicilio">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label>Número Domicilio</label>
                                    <input type="text" class="form-control" v-model="reclamo.municipalidad_numeroDomicilio">
                                </div>
                                <div class="mb-2">
                                    <label>Entre Calle Uno</label>
                                    <input type="text" class="form-control" v-model="reclamo.municipalidad_entreCalleUno">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label>Entre Calle Dos</label>
                                    <input type="text" class="form-control" v-model="reclamo.municipalidad_entreCalleDos">
                                </div>
                                <div class="mb-2">
                                    <label>Ciudadano</label>
                                    <input type="text" class="form-control" v-model="reclamo.municipalidad_ciudadano">
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label>Descripción</label>
                            <textarea class="form-control" v-model="reclamo.municipalidad_descripcion" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Guardar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
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
</div>
