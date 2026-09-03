<div id="app" class="reclamos-page">

    <div class="app-page-title">
        <span class="app-page-title__icon"><i class="bi bi-exclamation-triangle"></i></span>
        <h1 class="app-page-title__text">Reclamos</h1>
    </div>

    <!-- Acciones rápidas -->
    <div class="reclamos-toolbar">
        <div class="reclamos-toolbar__left">
            <button type="button" class="reclamos-btn" @click="abrirFormulario">
                <i class="bi bi-plus-lg"></i> Nuevo reclamo
            </button>
            <button class="reclamos-btn reclamos-btn--outline" data-bs-toggle="collapse" data-bs-target="#filtrosPanel">
                <i class="bi bi-funnel"></i> Filtros
            </button>
            <div class="dropdown">
                <button class="reclamos-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-arrow-repeat"></i> Sincronizar
                </button>
                <ul class="dropdown-menu reclamos-dropdown-menu">
                    <li>
                        <button class="dropdown-item" type="button" @click="sincronizarReclamosHoy" :disabled="!tokenDisponible || sincronizando">
                            <i class="bi bi-lightning-charge"></i> Pendientes
                        </button>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button class="dropdown-item" type="button" @click="mostrarOpcionesSincronizacion('fechas')" :disabled="sincronizando">
                            <i class="bi bi-calendar-range"></i> Por fechas
                        </button>
                    </li>
                    <li>
                        <button class="dropdown-item" type="button" @click="mostrarOpcionesSincronizacion('numero')" :disabled="sincronizando">
                            <i class="bi bi-search"></i> Por número
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <button type="button"
                class="reclamos-token"
                :class="tokenDisponible ? 'reclamos-token--ok' : 'reclamos-token--warn'"
                @click="abrirModalToken"
                title="Configurar credenciales del sistema 103">
            <i class="bi" :class="tokenDisponible ? 'bi-check-circle' : 'bi-exclamation-triangle'"></i>
            {{ tokenDisponible ? 'Token listo' : 'Configurar token' }}
        </button>
    </div>

    <!-- Progreso de sincronización -->
    <div v-if="sincronizando" class="reclamos-sync">
        <div class="reclamos-sync__main">
            <div class="reclamos-sync__info">
                <div class="reclamos-sync__spinner" role="status">
                    <span class="visually-hidden">{{ syncEtiqueta }}...</span>
                </div>
                <div class="reclamos-sync__texts">
                    <strong>{{ syncEtiqueta }}</strong>
                    <span class="reclamos-sync__count">{{ syncContadorTexto }} · {{ syncPorcentaje }}%</span>
                    <small v-if="syncDetalle" class="reclamos-sync__detalle">{{ syncDetalle }}</small>
                </div>
            </div>
            <div class="reclamos-sync__bar" aria-hidden="true">
                <div class="reclamos-sync__bar-fill" :style="{ width: syncPorcentaje + '%' }"></div>
            </div>
        </div>
        <button class="reclamos-btn reclamos-btn--danger" @click="detenerSincronizacionEnCurso" :disabled="detenerSincronizacion">
            <i class="bi bi-stop-circle"></i>
            <span v-if="!detenerSincronizacion">Detener</span>
            <span v-else>Deteniendo...</span>
        </button>
    </div>

    <!-- Panel de Filtros colapsable -->
    <div class="collapse reclamos-collapse" id="filtrosPanel">
        <div class="reclamos-panel reclamos-filters">
        <div class="row align-items-end">

            <div class="col-md-3 mb-2 mb-md-0">
                <label for="filtroEstado" class="form-label">Filtrar por Estado</label>
                <select id="filtroEstado" class="form-select" v-model="filtroEstado" @change="aplicarFiltros">
                    <option value="">Todos los estados</option>
                    <option value="Recibido">Recibido</option>
                    <option value="Asignado">Asignado</option>
                    <option value="Pendiente">Pendiente</option>
                    <option value="En ejecución">En ejecución</option>
                    <option value="Completado">Completado</option>
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
                    <button class="reclamos-btn reclamos-btn--outline w-100" @click="limpiarFiltros">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Panel de sincronización colapsable -->
    <div class="collapse reclamos-collapse" id="sincronizacionAvanzadaPanel">
        <div class="reclamos-panel">
                <div class="reclamos-panel__header">
                    <span class="reclamos-panel__title">
                        <i class="bi" :class="syncOpcionActiva === 'fechas' ? 'bi-calendar-range' : 'bi-search'"></i>
                        {{ syncOpcionActiva === 'fechas' ? 'Sincronizar por fechas' : 'Sincronizar por número' }}
                    </span>
                    <button type="button"
                            class="reclamos-panel__close"
                            @click="ocultarOpcionesSincronizacion"
                            aria-label="Ocultar opciones de sincronización">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="row g-3 align-items-end">
                    <div v-if="syncOpcionActiva === 'fechas'" class="col-lg-8">
                        
                        <div class="row g-2 align-items-end">
                            <div class="col-sm-4">
                                <input type="date" id="syncFechaDesde" class="form-control form-control-sm" v-model="syncFechaDesde" aria-label="Fecha desde">
                            </div>
                            <div class="col-sm-4">
                                <input type="date" id="syncFechaHasta" class="form-control form-control-sm" v-model="syncFechaHasta" aria-label="Fecha hasta">
                            </div>
                            <div class="col-sm-4">
                                <button class="reclamos-btn w-100" @click="sincronizarReclamosPorFechas" :disabled="!tokenDisponible || sincronizando">
                                    <i class="bi bi-download"></i> Sincronizar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="syncOpcionActiva === 'numero'" class="col-lg-5">
                        
                        <div class="input-group input-group-sm">
                            <input type="number" id="numeroReclamo" class="form-control" v-model="numeroReclamo" placeholder="Nro. reclamo">
                            <button class="reclamos-btn" @click="sincronizarReclamoEspecifico" :disabled="!tokenDisponible || !numeroReclamo || sincronizando">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="!tokenDisponible" class="reclamos-alert mt-3">
                    <i class="bi bi-exclamation-triangle"></i> No hay token disponible.
                    <button type="button" class="reclamos-alert__link" @click="abrirModalToken">Configure un token aquí</button>
                </div>
        </div>
    </div>

    <!-- Tabla de reclamos (controles DataTables quedan fuera del recuadro) -->
    <div class="reclamos-table-section">
        <table id="tabla_reclamos" class="table table-hover table-sm align-middle w-100 mb-0 reclamos-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Motivo</th>
                    <th>Fecha de Inicio</th>
                    <th>Fecha de Modificación</th>
                    <th>Estado</th>
                    <th>Domicilio</th>
                    <th>Número</th>
                    <th style="width: 70px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Contenido de la tabla gestionado por DataTables -->
            </tbody>
        </table>
    </div>

    <!-- Modal Editar ficha de reclamo -->
    <div class="modal fade" id="modalReclamo" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content reclamo-modal">
                <form @submit.prevent="guardarReclamo">
                    <div class="reclamo-modal__header">
                        <div class="reclamo-modal__title">
                            <span class="reclamo-modal__icon">
                                <i class="bi" :class="modoCreacion ? 'bi-plus-lg' : 'bi-pencil-square'"></i>
                            </span>
                            <h5>{{ modoCreacion ? 'Nuevo reclamo' : 'Editar ficha del reclamo' }}</h5>
                            <span v-if="!modoCreacion" class="reclamos-info-tip" tabindex="0" aria-label="Explicación de la edición de ficha">
                                <i class="bi bi-info-circle" aria-hidden="true"></i>
                                <span class="reclamos-info-tip__popup" role="tooltip">
                                    <strong>¿Qué se puede editar?</strong>
                                    <p>Corrección de datos de ficha. El estado y el cierre se gestionan en Tareas / Cierre. La prioridad se recalcula automáticamente.</p>
                                    <p>Si el reclamo ya fue corregido, un sync por número del 103 no pisará estos campos.</p>
                                </span>
                            </span>
                        </div>
                        <button type="button" class="reclamo-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <label class="form-label text-muted mb-0">ID</label>
                                <p class="fw-bold mb-2" v-if="modoCreacion">
                                    <span class="reclamo-origen reclamo-origen--local">Local</span>
                                </p>
                                <p class="fw-bold mb-2" v-else>
                                    <span v-if="esOrigenLocal(reclamo)" class="reclamo-origen reclamo-origen--local me-1">Local</span>
                                    <span v-else class="reclamo-origen reclamo-origen--103 me-1">103</span>
                                    #{{ reclamo.municipalidad_id }}
                                </p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted mb-0">Estado</label>
                                <p class="mb-2">{{ modoCreacion ? 'Recibido' : (reclamo.municipalidad_estado || '—') }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted mb-0">Prioridad</label>
                                <p class="mb-2">{{ modoCreacion ? 'Automática' : (reclamo.prioridad || '—') }}</p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label>Motivo <span class="campo-obligatorio">*</span></label>
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
                                    <label>Recepción</label>
                                    <select class="form-control" v-model="reclamo.municipalidad_recepcion">
                                        <option value="">Sin especificar</option>
                                        <option value="llamada">Llamada</option>
                                        <option value="web">Web</option>
                                        <option value="whatsApp">WhatsApp</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label>Teléfono</label>
                                    <input type="text" class="form-control" v-model="reclamo.municipalidad_telefono">
                                </div>
                                <div class="mb-2">
                                    <label>Ciudadano</label>
                                    <input type="text" class="form-control" v-model="reclamo.municipalidad_ciudadano">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label>Domicilio <span class="campo-obligatorio">*</span></label>
                                    <input type="text"
                                           id="inputDomicilioReclamo"
                                           class="form-control"
                                           v-model="reclamo.municipalidad_domicilio"
                                           autocomplete="off"
                                           required
                                           placeholder="Empezá a escribir la calle…">
                                </div>
                                <div class="mb-2">
                                    <label>Número <span class="campo-obligatorio">*</span></label>
                                    <input type="text"
                                           id="inputNumeroReclamo"
                                           class="form-control"
                                           v-model="reclamo.municipalidad_numeroDomicilio"
                                           autocomplete="off"
                                           required
                                           placeholder="Altura">
                                </div>
                                <div class="mb-2">
                                    <label>Entre Calle Uno</label>
                                    <input type="text" class="form-control" v-model="reclamo.municipalidad_entreCalleUno">
                                </div>
                                <div class="mb-2">
                                    <label>Entre Calle Dos</label>
                                    <input type="text" class="form-control" v-model="reclamo.municipalidad_entreCalleDos">
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label>Descripción</label>
                            <textarea class="form-control" v-model="reclamo.municipalidad_descripcion" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="reclamo-modal__footer reclamo-modal__footer--end">
                        <button type="button" class="reclamos-btn reclamos-btn--outline" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="reclamos-btn" :disabled="guardandoFicha">
                            <span v-if="guardandoFicha" class="spinner-border spinner-border-sm"></span>
                            <i v-else class="bi bi-check-lg"></i>
                            {{ guardandoFicha ? 'Guardando…' : (modoCreacion ? 'Crear reclamo' : 'Guardar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Configuración Token 103 -->
    <div class="modal fade" id="modalToken103" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content reclamo-modal">
                <div class="reclamo-modal__header">
                    <div class="reclamo-modal__title">
                        <span class="reclamo-modal__icon"><i class="bi bi-key"></i></span>
                        <h5>Token Sistema 103</h5>
                    </div>
                    <button type="button" class="reclamo-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <form @submit.prevent="guardarCredencialesToken">
                    <div class="modal-body">
                        <div v-if="credencialesGuardadas" class="reclamos-token-status reclamos-token-status--ok">
                            <i class="bi bi-check-circle"></i>
                            <span>Token configurado. Listo para sincronizar con el 103.</span>
                        </div>
                        <div v-else class="reclamos-token-status reclamos-token-status--warn">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span>Configurá el token para sincronizar reclamos.</span>
                        </div>
                        <div class="mb-0">
                            <label for="tokenApi103" class="form-label">API Token</label>
                            <input type="text"
                                   id="tokenApi103"
                                   class="form-control"
                                   v-model.trim="credenciales.api_token"
                                   placeholder="Pegá el token del 103"
                                   required>
                            <small class="reclamos-token-hint">Authorization: Token {valor}</small>
                        </div>
                    </div>
                    <div class="reclamo-modal__footer reclamo-modal__footer--end">
                        <button type="button" class="reclamos-btn reclamos-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="reclamos-btn">
                            <i class="bi bi-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalles Reclamo -->
    <div class="modal fade" id="modalVerReclamo" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content reclamo-modal">
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
                                <label class="fw-bold">ID:</label>
                                <p>
                                    <span v-if="esOrigenLocal(reclamoSeleccionado)" class="reclamo-origen reclamo-origen--local me-1">Local</span>
                                    <span v-else class="reclamo-origen reclamo-origen--103 me-1">103</span>
                                    #{{ reclamoSeleccionado.municipalidad_id }}
                                </p>
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
                <div class="reclamo-modal__footer reclamo-modal__footer--end">
                    <button type="button" class="reclamos-btn reclamos-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="reclamos-btn reclamos-btn--outline reclamos-btn--danger" @click="eliminarDesdeDetalle">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                    <button type="button" class="reclamos-btn" @click="editarDesdeDetalle">
                        <i class="bi bi-pencil"></i> Editar ficha
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal confirmar exclusión de reclamo -->
    <div class="modal fade" id="modalEliminarReclamo" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content reclamo-modal">
                <div class="reclamo-modal__header">
                    <div class="reclamo-modal__title">
                        <span class="reclamo-modal__icon"><i class="bi bi-trash"></i></span>
                        <h5>Eliminar reclamo</h5>
                    </div>
                    <button type="button" class="reclamo-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2" v-if="reclamoAEliminar">
                        ¿Excluir el reclamo <strong>#{{ reclamoAEliminar.municipalidad_id }}</strong> de la plataforma?
                    </p>
                    <label class="form-label">Motivo <span class="text-muted">(opcional)</span></label>
                    <textarea class="form-control" v-model="observacionEliminacion" rows="2"
                              placeholder="Ej: Duplicado / datos inválidos / no corresponde a alumbrado"></textarea>
                </div>
                <div class="reclamo-modal__footer reclamo-modal__footer--end">
                    <button type="button" class="reclamos-btn reclamos-btn--outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="reclamos-btn reclamos-btn--danger" :disabled="eliminandoReclamo" @click="confirmarEliminarReclamo">
                        <span v-if="eliminandoReclamo" class="spinner-border spinner-border-sm"></span>
                        <i v-else class="bi bi-trash"></i>
                        {{ eliminandoReclamo ? 'Eliminando…' : 'Eliminar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
