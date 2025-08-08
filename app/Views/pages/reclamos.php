<div id="app" class="container-fluid">
    <div>Reclamos</div>

    <!-- Botón para agregar reclamo -->
    <button class="btn btn-primary mb-3" @click="abrirFormulario()">+ Nuevo Reclamo</button>

    <!-- Tabla de reclamos -->
    <table id="tabla_reclamos" class="table table-bordered table-hover w-100">
        <thead>
            <tr>
                <th>ID</th>
                <th>Motivo</th>
                <th>Fecha de Inicio</th>
                <th>Fecha de Modificación</th>
                <th>Recepción</th>
                <th>Estado</th>
                <th>Domicilio</th>
                <th>Número Domicilio</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="reclamo in reclamos" :key="reclamo.id">
                <td>{{ reclamo.municipalidad_id }}</td>
                <td>{{ reclamo.municipalidad_motivo }}</td>
                <td>{{ formatearFecha(reclamo.municipalidad_fechaInicio) }}</td>
                <td>{{ formatearFecha(reclamo.municipalidad_fechaModificacion) }}</td>
                <td>{{ reclamo.municipalidad_recepcion }}</td>
                <td>{{ reclamo.municipalidad_estado }}</td>
                <td>{{ reclamo.municipalidad_domicilio }}</td>
                <td>{{ reclamo.municipalidad_numeroDomicilio }}</td>
                <td>
                    <button class="btn btn-sm btn-info me-1" @click="verReclamo(reclamo)" title="Ver detalles">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning me-1" @click="editarReclamo(reclamo)" title="Editar">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" @click="eliminarReclamo(reclamo)" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        </tbody>
    </table>

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
                                <p>{{ reclamoSeleccionado.municipalidad_recepcion }}</p>
                            </div>
                            <div class="mb-3">
                                <label class="fw-bold">Estado:</label>
                                <p>{{ reclamoSeleccionado.municipalidad_estado }}</p>
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
