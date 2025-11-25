<div id="app" class="container-fluid">
    <div>Análisis de Reclamos</div>

    <!-- Tarjetas KPI de Resumen -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h6 class="mb-0 mb-2 mb-md-0"><i class="bi bi-speedometer2"></i> Indicadores Clave</h6>
                        <div>
                            <div class="d-flex gap-2 align-items-center flex-wrap mb-2">
                                <div class="d-flex gap-2 align-items-center">
                                    <label class="form-label text-white mb-0 small">Desde:</label>
                                    <input type="date" class="form-control form-control-sm" style="width: 150px;" v-model="kpiFiltros.fechaDesde" @change="cargarKpiResumen">
                                </div>
                                <div class="d-flex gap-2 align-items-center">
                                    <label class="form-label text-white mb-0 small">Hasta:</label>
                                    <input type="date" class="form-control form-control-sm" style="width: 150px;" v-model="kpiFiltros.fechaHasta" @change="cargarKpiResumen">
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-light me-1" @click="setFiltroRapidoKpi('hoy')">Hoy</button>
                                <button type="button" class="btn btn-sm btn-outline-light me-1" @click="setFiltroRapidoKpi('semana')">Últimos 7 días</button>
                                <button type="button" class="btn btn-sm btn-outline-light me-1" @click="setFiltroRapidoKpi('mes')">Mes actual</button>
                                <button type="button" class="btn btn-sm btn-outline-light" @click="setFiltroRapidoKpi('año')">Año actual</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Total Activos -->
                        <div class="col-md-4 col-lg-2">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-info">
                                    <i class="bi bi-lightning-charge"></i>
                                </div>
                                <div class="kpi-content">
                                    <h6 class="kpi-label">Activos</h6>
                                    <h3 class="kpi-value text-info">{{ kpiResumen.total_activos || 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <!-- Pendientes -->
                        <div class="col-md-4 col-lg-2">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-warning">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                                <div class="kpi-content">
                                    <h6 class="kpi-label">Pendientes</h6>
                                    <h3 class="kpi-value text-warning">{{ kpiResumen.total_pendientes || 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <!-- En Ejecución -->
                        <div class="col-md-4 col-lg-2">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-primary">
                                    <i class="bi bi-gear"></i>
                                </div>
                                <div class="kpi-content">
                                    <h6 class="kpi-label">En Ejecución</h6>
                                    <h3 class="kpi-value text-primary">{{ kpiResumen.total_en_ejecucion || 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <!-- Completados -->
                        <div class="col-md-4 col-lg-2">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-success">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div class="kpi-content">
                                    <h6 class="kpi-label">Completados</h6>
                                    <h3 class="kpi-value text-success">{{ kpiResumen.total_completados || 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <!-- Tasa de Resolución -->
                        <div class="col-md-4 col-lg-2">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-success">
                                    <i class="bi bi-percent"></i>
                                </div>
                                <div class="kpi-content">
                                    <h6 class="kpi-label">Tasa Resolución</h6>
                                    <h3 class="kpi-value text-success">{{ kpiResumen.tasa_resolucion || 0 }}%</h3>
                                </div>
                            </div>
                        </div>
                        <!-- Tiempo Promedio -->
                        <div class="col-md-4 col-lg-2">
                            <div class="kpi-card">
                                <div class="kpi-icon bg-danger">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div class="kpi-content">
                                    <h6 class="kpi-label">Tiempo Promedio</h6>
                                    <h3 class="kpi-value text-danger">{{ kpiResumen.tiempo_promedio_dias || 0 }} días</h3>
                                    <small class="text-muted">{{ kpiResumen.tiempo_promedio_horas || 0 }} horas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid de Gráficos -->
    <div class="row g-4">
        <!-- Gráfico 1: Distribución de Reclamos por Estado (Torta/Pie) -->
        <div class="col-lg-6 col-md-12">
            <div class="card h-100 chart-card" @click="abrirModalGrafico('estado')">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-pie-chart-fill text-primary"></i>
                        Distribución de Reclamos por Estado
                    </h5>
                    <p class="card-text text-muted small">
                        Vista rápida del estado general del sistema de reclamos
                    </p>
                    <div class="chart-illustration">
                        <svg viewBox="0 0 200 200" width="180" height="180">
                            <circle cx="100" cy="100" r="80" fill="#e9ecef"/>
                            <path d="M100,100 L100,20 A80,80 0 0,1 172,140 Z" fill="#0d6efd"/>
                            <path d="M100,100 L172,140 A80,80 0 0,1 60,168 Z" fill="#198754"/>
                            <path d="M100,100 L60,168 A80,80 0 0,1 28,60 Z" fill="#ffc107"/>
                            <path d="M100,100 L28,60 A80,80 0 0,1 100,20 Z" fill="#dc3545"/>
                            <circle cx="100" cy="100" r="40" fill="#fff"/>
                        </svg>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <small class="text-muted">
                        <i class="bi bi-eye"></i> Haga clic para ver en detalle
                    </small>
                </div>
            </div>
        </div>

        <!-- Gráfico 2: Reclamos por Motivo (Barras Horizontales) -->
        <div class="col-lg-6 col-md-12">
            <div class="card h-100 chart-card" @click="abrirModalGrafico('motivo')">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-bar-chart-fill text-success"></i>
                        Reclamos por Motivo
                    </h5>
                    <p class="card-text text-muted small">
                        Identifica los tipos de problemas más frecuentes
                    </p>
                    <div class="chart-illustration">
                        <svg viewBox="0 0 220 180" width="220" height="180">
                            <rect x="10" y="20" width="160" height="24" rx="4" fill="#0d6efd"/>
                            <rect x="10" y="54" width="130" height="24" rx="4" fill="#198754"/>
                            <rect x="10" y="88" width="100" height="24" rx="4" fill="#ffc107"/>
                            <rect x="10" y="122" width="70" height="24" rx="4" fill="#dc3545"/>
                            <rect x="10" y="156" width="45" height="24" rx="4" fill="#6f42c1"/>
                        </svg>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <small class="text-muted">
                        <i class="bi bi-eye"></i> Haga clic para ver en detalle
                    </small>
                </div>
            </div>
        </div>

        <!-- Gráfico 3: Evolución Temporal (Líneas múltiples) -->
        <div class="col-lg-12 col-md-12">
            <div class="card h-100 chart-card" @click="abrirModalGrafico('evolucion')">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-graph-up-arrow text-info"></i>
                        Evolución Temporal de Reclamos
                    </h5>
                    <p class="card-text text-muted small">
                        Analiza tendencias y patrones temporales de los reclamos
                    </p>
                    <div class="chart-illustration">
                        <svg viewBox="0 0 300 150" width="300" height="150">
                            <line x1="30" y1="130" x2="280" y2="130" stroke="#dee2e6" stroke-width="2"/>
                            <line x1="30" y1="20" x2="30" y2="130" stroke="#dee2e6" stroke-width="2"/>
                            <polyline points="30,100 70,80 110,90 150,60 190,70 230,40 270,50" fill="none" stroke="#0d6efd" stroke-width="3"/>
                            <polyline points="30,110 70,95 110,100 150,85 190,90 230,75 270,80" fill="none" stroke="#198754" stroke-width="3"/>
                            <polyline points="30,90 70,110 110,85 150,95 190,65 230,85 270,60" fill="none" stroke="#ffc107" stroke-width="3"/>
                            <circle cx="270" cy="50" r="4" fill="#0d6efd"/>
                            <circle cx="270" cy="80" r="4" fill="#198754"/>
                            <circle cx="270" cy="60" r="4" fill="#ffc107"/>
                        </svg>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <small class="text-muted">
                        <i class="bi bi-eye"></i> Haga clic para ver en detalle
                    </small>
                </div>
            </div>
        </div>

        <!-- Gráfico 4: Tiempo Promedio de Resolución por Motivo (Barras Horizontales) -->
        <div class="col-lg-6 col-md-12">
            <div class="card h-100 chart-card" @click="abrirModalGrafico('tiempoPromedio')">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-clock-history text-warning"></i>
                        Tiempo Promedio de Resolución por Motivo
                    </h5>
                    <p class="card-text text-muted small">
                        Identifica qué tipos de reclamos toman más tiempo resolver
                    </p>
                    <div class="chart-illustration">
                        <svg viewBox="0 0 220 180" width="220" height="180">
                            <rect x="10" y="20" width="180" height="24" rx="4" fill="#fd7e14"/>
                            <rect x="10" y="54" width="140" height="24" rx="4" fill="#ffc107"/>
                            <rect x="10" y="88" width="110" height="24" rx="4" fill="#20c997"/>
                            <rect x="10" y="122" width="80" height="24" rx="4" fill="#0dcaf0"/>
                            <rect x="10" y="156" width="50" height="24" rx="4" fill="#6c757d"/>
                            <text x="195" y="37" font-size="12" fill="#333">48h</text>
                            <text x="155" y="71" font-size="12" fill="#333">36h</text>
                            <text x="125" y="105" font-size="12" fill="#333">24h</text>
                        </svg>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <small class="text-muted">
                        <i class="bi bi-eye"></i> Haga clic para ver en detalle
                    </small>
                </div>
            </div>
        </div>

        <!-- Gráfico 5: Evolución del Tiempo Promedio de Resolución (Líneas) -->
        <div class="col-lg-6 col-md-12">
            <div class="card h-100 chart-card" @click="abrirModalGrafico('evolucionTiempo')">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-graph-up-arrow text-success"></i>
                        Evolución del Tiempo Promedio de Resolución
                    </h5>
                    <p class="card-text text-muted small">
                        Monitorea si los tiempos mejoran o empeoran
                    </p>
                    <div class="chart-illustration">
                        <svg viewBox="0 0 220 150" width="220" height="150">
                            <line x1="30" y1="130" x2="200" y2="130" stroke="#dee2e6" stroke-width="2"/>
                            <line x1="30" y1="20" x2="30" y2="130" stroke="#dee2e6" stroke-width="2"/>
                            <polyline points="30,40 60,50 90,45 120,65 150,55 180,70" fill="none" stroke="#198754" stroke-width="3"/>
                            <polyline points="30,60 60,70 90,55 120,75 150,65 180,80" fill="none" stroke="#0d6efd" stroke-width="3"/>
                            <circle cx="180" cy="70" r="4" fill="#198754"/>
                            <circle cx="180" cy="80" r="4" fill="#0d6efd"/>
                            <text x="40" y="145" font-size="10" fill="#6c757d">Ene</text>
                            <text x="90" y="145" font-size="10" fill="#6c757d">Mar</text>
                            <text x="140" y="145" font-size="10" fill="#6c757d">May</text>
                        </svg>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <small class="text-muted">
                        <i class="bi bi-eye"></i> Haga clic para ver en detalle
                    </small>
                </div>
            </div>
        </div>

        <!-- Gráfico 7: Evolución de Reclamos de Alta Prioridad (Línea con tendencia) -->
        <div class="col-lg-6 col-md-12">
            <div class="card h-100 chart-card" @click="abrirModalGrafico('altaPrioridad')">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                        Evolución de Reclamos de Alta Prioridad
                    </h5>
                    <p class="card-text text-muted small">
                        Asegura que los reclamos críticos se resuelven rápidamente
                    </p>
                    <div class="chart-illustration">
                        <svg viewBox="0 0 220 150" width="220" height="150">
                            <line x1="30" y1="130" x2="200" y2="130" stroke="#dee2e6" stroke-width="2"/>
                            <line x1="30" y1="20" x2="30" y2="130" stroke="#dee2e6" stroke-width="2"/>
                            <line x1="30" y1="90" x2="200" y2="90" stroke="#198754" stroke-width="2" stroke-dasharray="5,5"/>
                            <polyline points="30,50 60,70 90,45 120,80 150,60 180,75" fill="none" stroke="#dc3545" stroke-width="3"/>
                            <polyline points="30,55 60,60 90,58 120,65 150,68 180,72" fill="none" stroke="#6c757d" stroke-width="2" stroke-dasharray="3,3"/>
                            <circle cx="180" cy="75" r="4" fill="#dc3545"/>
                            <text x="185" y="93" font-size="9" fill="#198754">Meta</text>
                        </svg>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <small class="text-muted">
                        <i class="bi bi-eye"></i> Haga clic para ver en detalle
                    </small>
                </div>
            </div>
        </div>

        <!-- Gráfico 8: Consumo de Materiales por Período (Líneas múltiples) -->
        <div class="col-lg-6 col-md-12">
            <div class="card h-100 chart-card" @click="abrirModalGrafico('consumoMateriales')">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-box-seam text-primary"></i>
                        Consumo de Materiales por Período
                    </h5>
                    <p class="card-text text-muted small">
                        Predice necesidades futuras de materiales
                    </p>
                    <div class="chart-illustration">
                        <svg viewBox="0 0 220 150" width="220" height="150">
                            <line x1="30" y1="130" x2="200" y2="130" stroke="#dee2e6" stroke-width="2"/>
                            <line x1="30" y1="20" x2="30" y2="130" stroke="#dee2e6" stroke-width="2"/>
                            <polyline points="30,90 60,70 90,85 120,50 150,65 180,40" fill="none" stroke="#0d6efd" stroke-width="3"/>
                            <polyline points="30,100 60,95 90,80 120,90 150,75 180,85" fill="none" stroke="#fd7e14" stroke-width="3"/>
                            <polyline points="30,110 60,100 90,105 120,95 150,100 180,90" fill="none" stroke="#198754" stroke-width="3"/>
                            <circle cx="180" cy="40" r="4" fill="#0d6efd"/>
                            <circle cx="180" cy="85" r="4" fill="#fd7e14"/>
                            <circle cx="180" cy="90" r="4" fill="#198754"/>
                        </svg>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <small class="text-muted">
                        <i class="bi bi-eye"></i> Haga clic para ver en detalle
                    </small>
                </div>
            </div>
        </div>

        <!-- Gráfico 9: Reclamos Cerrados vs Abiertos (Barras agrupadas) -->
        <div class="col-lg-6 col-md-12">
            <div class="card h-100 chart-card" @click="abrirModalGrafico('cerradosAbiertos')">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-bar-chart-fill text-success"></i>
                        Reclamos Cerrados vs Abiertos
                    </h5>
                    <p class="card-text text-muted small">
                        Monitorea el proceso de cierre formal de reclamos
                    </p>
                    <div class="chart-illustration">
                        <svg viewBox="0 0 220 150" width="220" height="150">
                            <line x1="30" y1="130" x2="200" y2="130" stroke="#dee2e6" stroke-width="2"/>
                            <rect x="35" y="50" width="20" height="80" rx="2" fill="#198754"/>
                            <rect x="58" y="70" width="20" height="60" rx="2" fill="#dc3545"/>
                            <rect x="95" y="40" width="20" height="90" rx="2" fill="#198754"/>
                            <rect x="118" y="85" width="20" height="45" rx="2" fill="#dc3545"/>
                            <rect x="155" y="55" width="20" height="75" rx="2" fill="#198754"/>
                            <rect x="178" y="95" width="20" height="35" rx="2" fill="#dc3545"/>
                            <text x="45" y="145" font-size="9" fill="#6c757d">Ene</text>
                            <text x="105" y="145" font-size="9" fill="#6c757d">Feb</text>
                            <text x="165" y="145" font-size="9" fill="#6c757d">Mar</text>
                        </svg>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <small class="text-muted">
                        <i class="bi bi-eye"></i> Haga clic para ver en detalle
                    </small>
                </div>
            </div>
        </div>

        <!-- Gráfico 10: Tasa de Cierre de Reclamos (Línea con meta) -->
        <div class="col-lg-6 col-md-12">
            <div class="card h-100 chart-card" @click="abrirModalGrafico('tasaCierre')">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-percent text-info"></i>
                        Tasa de Cierre de Reclamos
                    </h5>
                    <p class="card-text text-muted small">
                        Asegura que los reclamos se cierran correctamente
                    </p>
                    <div class="chart-illustration">
                        <svg viewBox="0 0 220 150" width="220" height="150">
                            <line x1="30" y1="130" x2="200" y2="130" stroke="#dee2e6" stroke-width="2"/>
                            <line x1="30" y1="20" x2="30" y2="130" stroke="#dee2e6" stroke-width="2"/>
                            <line x1="30" y1="35" x2="200" y2="35" stroke="#198754" stroke-width="2" stroke-dasharray="5,5"/>
                            <polyline points="30,80 50,70 70,60 90,55 110,50 130,45 150,40 170,42 190,38" fill="none" stroke="#0d6efd" stroke-width="3"/>
                            <circle cx="190" cy="38" r="4" fill="#0d6efd"/>
                            <text x="185" y="30" font-size="9" fill="#198754">95%</text>
                            <text x="15" y="35" font-size="8" fill="#6c757d">100%</text>
                            <text x="15" y="80" font-size="8" fill="#6c757d">50%</text>
                            <text x="15" y="130" font-size="8" fill="#6c757d">0%</text>
                        </svg>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <small class="text-muted">
                        <i class="bi bi-eye"></i> Haga clic para ver en detalle
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Gráfico de Estado -->
    <div class="modal fade" id="modalGraficoEstado" tabindex="-1" aria-labelledby="modalGraficoEstadoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalGraficoEstadoLabel">
                        <i class="bi bi-pie-chart-fill"></i>
                        Distribución de Reclamos por Estado
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Descripción -->
                    <div class="alert alert-info mb-3">
                        <h6><i class="bi bi-info-circle"></i> ¿Qué muestra este gráfico?</h6>
                        <p class="mb-2">Este gráfico de torta muestra la distribución porcentual de los reclamos según su estado actual (Recibido, Asignado, En ejecución, Completado, En plan, Error de datos).</p>
                        <p class="mb-0"><strong>Utilidad:</strong> Permite evaluar rápidamente el estado general del sistema, identificar cuellos de botella y priorizar acciones según la cantidad de reclamos en cada estado.</p>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="filtroFechaDesdeEstado" class="form-label">Fecha Desde</label>
                                    <input type="date" id="filtroFechaDesdeEstado" class="form-control" v-model="filtrosEstado.fechaDesde" @change="cargarGraficoEstado">
                                </div>
                                <div class="col-md-4">
                                    <label for="filtroFechaHastaEstado" class="form-label">Fecha Hasta</label>
                                    <input type="date" id="filtroFechaHastaEstado" class="form-control" v-model="filtrosEstado.fechaHasta" @change="cargarGraficoEstado">
                                </div>
                                <div class="col-md-4">
                                    <label for="filtroPrioridadEstado" class="form-label">Prioridad</label>
                                    <select id="filtroPrioridadEstado" class="form-select" v-model="filtrosEstado.prioridad" @change="cargarGraficoEstado">
                                        <option value="">Todas</option>
                                        <option value="Alta">Alta</option>
                                        <option value="Baja">Baja</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoEstado('7dias')">Últimos 7 días</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoEstado('30dias')">Últimos 30 días</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoEstado('mes')">Mes actual</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="setFiltroRapidoEstado('año')">Año actual</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico -->
                    <div class="chart-container">
                        <div id="chartEstado"></div>
                    </div>

                    <!-- Información adicional -->
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Total de Reclamos</h6>
                                        <h3 class="text-primary">{{ datosEstado.total || 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Período</h6>
                                        <p class="mb-0">{{ datosEstado.periodo || '-' }}</p>
                                        <small class="text-muted" v-if="datosEstado.filtros_aplicados">
                                            Desde: {{ datosEstado.filtros_aplicados.fecha_desde || 'Sin filtro' }} | 
                                            Hasta: {{ datosEstado.filtros_aplicados.fecha_hasta || 'Sin filtro' }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="exportarGraficoEstado">
                        <i class="bi bi-download"></i> Exportar como Imagen
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Gráfico de Motivo -->
    <div class="modal fade" id="modalGraficoMotivo" tabindex="-1" aria-labelledby="modalGraficoMotivoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalGraficoMotivoLabel">
                        <i class="bi bi-bar-chart-fill"></i>
                        Reclamos por Motivo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Descripción -->
                    <div class="alert alert-info mb-3">
                        <h6><i class="bi bi-info-circle"></i> ¿Qué muestra este gráfico?</h6>
                        <p class="mb-2">Este gráfico de barras muestra la cantidad de reclamos agrupados por motivo, ordenados de mayor a menor frecuencia.</p>
                        <p class="mb-0"><strong>Utilidad:</strong> Permite identificar los tipos de problemas más frecuentes, facilitando la planificación de recursos, la asignación de cuadrillas y la toma de decisiones estratégicas para mejorar el servicio de alumbrado público.</p>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="filtroFechaDesdeMotivo" class="form-label">Fecha Desde</label>
                                    <input type="date" id="filtroFechaDesdeMotivo" class="form-control" v-model="filtrosMotivo.fechaDesde" @change="cargarGraficoMotivo">
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroFechaHastaMotivo" class="form-label">Fecha Hasta</label>
                                    <input type="date" id="filtroFechaHastaMotivo" class="form-control" v-model="filtrosMotivo.fechaHasta" @change="cargarGraficoMotivo">
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroEstadoMotivo" class="form-label">Estado</label>
                                    <select id="filtroEstadoMotivo" class="form-select" v-model="filtrosMotivo.estado" @change="cargarGraficoMotivo">
                                        <option value="">Todos</option>
                                        <option value="Recibido">Recibido</option>
                                        <option value="Asignado">Asignado</option>
                                        <option value="En ejecución">En ejecución</option>
                                        <option value="Completado">Completado</option>
                                        <option value="En plan">En plan</option>
                                        <option value="Error de datos">Error de datos</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroPrioridadMotivo" class="form-label">Prioridad</label>
                                    <select id="filtroPrioridadMotivo" class="form-select" v-model="filtrosMotivo.prioridad" @change="cargarGraficoMotivo">
                                        <option value="">Todas</option>
                                        <option value="Alta">Alta</option>
                                        <option value="Baja">Baja</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoMotivo('7dias')">Últimos 7 días</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoMotivo('30dias')">Últimos 30 días</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoMotivo('mes')">Mes actual</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="setFiltroRapidoMotivo('año')">Año actual</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico -->
                    <div class="chart-container">
                        <div id="chartMotivo"></div>
                    </div>

                    <!-- Información adicional -->
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Total de Reclamos</h6>
                                        <h3 class="text-success">{{ datosMotivo.total || 0 }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Período</h6>
                                        <p class="mb-0">{{ datosMotivo.periodo || '-' }}</p>
                                        <small class="text-muted" v-if="datosMotivo.filtros_aplicados">
                                            Estado: {{ datosMotivo.filtros_aplicados.estado || 'Todos' }} | 
                                            Prioridad: {{ datosMotivo.filtros_aplicados.prioridad || 'Todas' }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="exportarGraficoMotivo">
                        <i class="bi bi-download"></i> Exportar como Imagen
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Gráfico de Evolución Temporal -->
    <div class="modal fade" id="modalGraficoEvolucion" tabindex="-1" aria-labelledby="modalGraficoEvolucionLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalGraficoEvolucionLabel">
                        <i class="bi bi-graph-up-arrow"></i>
                        Evolución Temporal de Reclamos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Descripción -->
                    <div class="alert alert-info mb-3">
                        <h6><i class="bi bi-info-circle"></i> ¿Qué muestra este gráfico?</h6>
                        <p class="mb-2">Este gráfico de líneas muestra la evolución temporal de los reclamos con múltiples series: Recibidos, Pendientes, En Ejecución y Completados, permitiendo analizar tendencias y patrones temporales.</p>
                        <p class="mb-0"><strong>Utilidad:</strong> Permite identificar tendencias, picos de actividad, patrones estacionales y la eficiencia del sistema a lo largo del tiempo, facilitando la planificación de recursos y la toma de decisiones estratégicas.</p>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="filtroFechaDesdeEvolucion" class="form-label">Fecha Desde</label>
                                    <input type="date" id="filtroFechaDesdeEvolucion" class="form-control" v-model="filtrosEvolucion.fechaDesde" @change="cargarGraficoEvolucion">
                                </div>
                                <div class="col-md-4">
                                    <label for="filtroFechaHastaEvolucion" class="form-label">Fecha Hasta</label>
                                    <input type="date" id="filtroFechaHastaEvolucion" class="form-control" v-model="filtrosEvolucion.fechaHasta" @change="cargarGraficoEvolucion">
                                </div>
                                <div class="col-md-4">
                                    <label for="filtroGranularidadEvolucion" class="form-label">Granularidad</label>
                                    <select id="filtroGranularidadEvolucion" class="form-select" v-model="filtrosEvolucion.granularidad" @change="cargarGraficoEvolucion">
                                        <option value="diario">Diario</option>
                                        <option value="semanal">Semanal</option>
                                        <option value="mensual">Mensual</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoEvolucion('7dias')">Últimos 7 días</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoEvolucion('30dias')">Últimos 30 días</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoEvolucion('3meses')">3 meses</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoEvolucion('6meses')">6 meses</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="setFiltroRapidoEvolucion('año')">Año actual</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico -->
                    <div class="chart-container">
                        <div id="chartEvolucion"></div>
                    </div>

                    <!-- Información adicional -->
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Período</h6>
                                        <p class="mb-0">{{ datosEvolucion.periodo || '-' }}</p>
                                        <small class="text-muted" v-if="datosEvolucion.filtros_aplicados">
                                            Granularidad: {{ datosEvolucion.filtros_aplicados.granularidad || 'Diario' }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Rango de Fechas</h6>
                                        <p class="mb-0" v-if="datosEvolucion.filtros_aplicados">
                                            Desde: {{ datosEvolucion.filtros_aplicados.fecha_desde || 'Sin filtro' }}<br>
                                            Hasta: {{ datosEvolucion.filtros_aplicados.fecha_hasta || 'Sin filtro' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="exportarGraficoEvolucion">
                        <i class="bi bi-download"></i> Exportar como Imagen
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Tiempo Promedio de Resolución por Motivo -->
    <div class="modal fade" id="modalGraficoTiempoPromedio" tabindex="-1" aria-labelledby="modalGraficoTiempoPromedioLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalGraficoTiempoPromedioLabel">
                        <i class="bi bi-clock-history"></i>
                        Tiempo Promedio de Resolución por Motivo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Descripción -->
                    <div class="alert alert-warning mb-3">
                        <h6><i class="bi bi-info-circle"></i> ¿Qué muestra este gráfico?</h6>
                        <p class="mb-0">Muestra el tiempo promedio (en horas o minutos) que toma resolver cada tipo de reclamo. Permite identificar qué motivos requieren más tiempo de resolución, lo que ayuda a planificar recursos y mejorar la eficiencia del servicio.</p>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="filtroFechaDesdeTiempo" class="form-label">Fecha Desde</label>
                                    <input type="date" id="filtroFechaDesdeTiempo" class="form-control" v-model="filtrosTiempo.fechaDesde" @change="cargarGraficoTiempoPromedio">
                                </div>
                                <div class="col-md-4">
                                    <label for="filtroFechaHastaTiempo" class="form-label">Fecha Hasta</label>
                                    <input type="date" id="filtroFechaHastaTiempo" class="form-control" v-model="filtrosTiempo.fechaHasta" @change="cargarGraficoTiempoPromedio">
                                </div>
                                <div class="col-md-4">
                                    <label for="filtroMotivoTiempo" class="form-label">Motivo</label>
                                    <select id="filtroMotivoTiempo" class="form-select" v-model="filtrosTiempo.motivo" @change="cargarGraficoTiempoPromedio">
                                        <option value="Todos">Todos</option>
                                        <option v-for="motivo in motivosDisponibles" :key="motivo" :value="motivo">{{ motivo }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoTiempoPromedio('hoy')">Hoy</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoTiempoPromedio('7dias')">Últimos 7 días</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoTiempoPromedio('mes')">Mes actual</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="setFiltroRapidoTiempoPromedio('año')">Año actual</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico -->
                    <div class="chart-container">
                        <div id="chartTiempoPromedio"></div>
                    </div>

                    <!-- Información adicional -->
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Total de Registros</h6>
                                        <p class="mb-0">{{ datosTiempo.total || 0 }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Rango de Fechas</h6>
                                        <p class="mb-0" v-if="datosTiempo.filtros_aplicados">
                                            Desde: {{ datosTiempo.filtros_aplicados.fecha_desde || 'Sin filtro' }}<br>
                                            Hasta: {{ datosTiempo.filtros_aplicados.fecha_hasta || 'Sin filtro' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="exportarGraficoTiempoPromedio">
                        <i class="bi bi-download"></i> Exportar como Imagen
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Evolución del Tiempo Promedio -->
    <div class="modal fade" id="modalGraficoEvolucionTiempo" tabindex="-1" aria-labelledby="modalGraficoEvolucionTiempoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalGraficoEvolucionTiempoLabel">
                        <i class="bi bi-graph-up-arrow"></i>
                        Evolución del Tiempo Promedio de Resolución
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Descripción -->
                    <div class="alert alert-success mb-3">
                        <h6><i class="bi bi-info-circle"></i> ¿Qué muestra este gráfico?</h6>
                        <p class="mb-0">Muestra la evolución del tiempo promedio de resolución a lo largo del tiempo. Permite monitorear si los tiempos de resolución mejoran o empeoran, y comparar el rendimiento entre diferentes motivos de reclamos (top 5).</p>
                    </div>

                    <!-- Filtros -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="filtroFechaDesdeEvolucionTiempo" class="form-label">Fecha Desde</label>
                                    <input type="date" id="filtroFechaDesdeEvolucionTiempo" class="form-control" v-model="filtrosEvolucionTiempo.fechaDesde" @change="cargarGraficoEvolucionTiempo">
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroFechaHastaEvolucionTiempo" class="form-label">Fecha Hasta</label>
                                    <input type="date" id="filtroFechaHastaEvolucionTiempo" class="form-control" v-model="filtrosEvolucionTiempo.fechaHasta" @change="cargarGraficoEvolucionTiempo">
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroGranularidadEvolucionTiempo" class="form-label">Granularidad</label>
                                    <select id="filtroGranularidadEvolucionTiempo" class="form-select" v-model="filtrosEvolucionTiempo.granularidad" @change="cargarGraficoEvolucionTiempo">
                                        <option value="semanal">Semanal</option>
                                        <option value="mensual">Mensual</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filtroMotivoEvolucionTiempo" class="form-label">Motivo</label>
                                    <select id="filtroMotivoEvolucionTiempo" class="form-select" v-model="filtrosEvolucionTiempo.motivo" @change="cargarGraficoEvolucionTiempo">
                                        <option value="Todos">Todos</option>
                                        <option v-for="motivo in motivosDisponibles" :key="motivo" :value="motivo">{{ motivo }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoEvolucionTiempo('hoy')">Hoy</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoEvolucionTiempo('7dias')">Últimos 7 días</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoEvolucionTiempo('mes')">Mes actual</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="setFiltroRapidoEvolucionTiempo('año')">Año actual</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico -->
                    <div class="chart-container">
                        <div id="chartEvolucionTiempo"></div>
                    </div>

                    <!-- Información adicional -->
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Granularidad</h6>
                                        <p class="mb-0">{{ datosEvolucionTiempo.granularidad || 'Semanal' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">Rango de Fechas</h6>
                                        <p class="mb-0" v-if="datosEvolucionTiempo.filtros_aplicados">
                                            Desde: {{ datosEvolucionTiempo.filtros_aplicados.fecha_desde || 'Sin filtro' }}<br>
                                            Hasta: {{ datosEvolucionTiempo.filtros_aplicados.fecha_hasta || 'Sin filtro' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="exportarGraficoEvolucionTiempo">
                        <i class="bi bi-download"></i> Exportar como Imagen
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Gráfico de Alta Prioridad -->
    <div class="modal fade" id="modalGraficoAltaPrioridad" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-danger"></i> Evolución de Reclamos de Alta Prioridad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger mb-3">
                        <h6><i class="bi bi-info-circle"></i> ¿Qué muestra este gráfico?</h6>
                        <p class="mb-0">Muestra la evolución de reclamos de alta prioridad con línea de tendencia y meta objetivo. Permite asegurar que los reclamos críticos se resuelven rápidamente.</p>
                    </div>
                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Desde</label>
                                    <input type="date" class="form-control" v-model="filtrosAltaPrioridad.fechaDesde" @change="cargarGraficoAltaPrioridad">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Hasta</label>
                                    <input type="date" class="form-control" v-model="filtrosAltaPrioridad.fechaHasta" @change="cargarGraficoAltaPrioridad">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Granularidad</label>
                                    <select class="form-select" v-model="filtrosAltaPrioridad.granularidad" @change="cargarGraficoAltaPrioridad">
                                        <option value="diario">Diario</option>
                                        <option value="semanal">Semanal</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoAltaPrioridad('hoy')">Hoy</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoAltaPrioridad('7dias')">Últimos 7 días</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoAltaPrioridad('mes')">Mes actual</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="setFiltroRapidoAltaPrioridad('año')">Año actual</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container"><div id="chartAltaPrioridad"></div></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="exportarGraficoAltaPrioridad"><i class="bi bi-download"></i> Exportar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Gráfico de Consumo de Materiales -->
    <div class="modal fade" id="modalGraficoConsumoMateriales" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-seam text-primary"></i> Consumo de Materiales por Período</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-primary mb-3">
                        <h6><i class="bi bi-info-circle"></i> ¿Qué muestra este gráfico?</h6>
                        <p class="mb-0">Muestra el consumo de los 5 materiales más utilizados por mes. Permite predecir necesidades futuras de materiales y planificar compras.</p>
                    </div>
                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Desde</label>
                                    <input type="date" class="form-control" v-model="filtrosConsumoMateriales.fechaDesde" @change="cargarGraficoConsumoMateriales">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Hasta</label>
                                    <input type="date" class="form-control" v-model="filtrosConsumoMateriales.fechaHasta" @change="cargarGraficoConsumoMateriales">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Material</label>
                                    <select class="form-select" v-model="filtrosConsumoMateriales.material" @change="cargarGraficoConsumoMateriales">
                                        <option value="Todos">Todos (Top 5)</option>
                                        <option v-for="mat in datosConsumoMateriales.materiales_disponibles" :key="mat" :value="mat">{{ mat }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoConsumoMateriales('hoy')">Hoy</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoConsumoMateriales('7dias')">Últimos 7 días</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoConsumoMateriales('mes')">Mes actual</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="setFiltroRapidoConsumoMateriales('año')">Año actual</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container"><div id="chartConsumoMateriales"></div></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="exportarGraficoConsumoMateriales"><i class="bi bi-download"></i> Exportar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Gráfico de Cerrados vs Abiertos -->
    <div class="modal fade" id="modalGraficoCerradosAbiertos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bar-chart-fill text-success"></i> Reclamos Cerrados vs Abiertos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success mb-3">
                        <h6><i class="bi bi-info-circle"></i> ¿Qué muestra este gráfico?</h6>
                        <p class="mb-0">Compara la cantidad de reclamos cerrados (cerrado=1) vs abiertos (cerrado=0) por período. Permite monitorear el proceso de cierre formal de reclamos.</p>
                    </div>
                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Desde</label>
                                    <input type="date" class="form-control" v-model="filtrosCerradosAbiertos.fechaDesde" @change="cargarGraficoCerradosAbiertos">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Hasta</label>
                                    <input type="date" class="form-control" v-model="filtrosCerradosAbiertos.fechaHasta" @change="cargarGraficoCerradosAbiertos">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Granularidad</label>
                                    <select class="form-select" v-model="filtrosCerradosAbiertos.granularidad" @change="cargarGraficoCerradosAbiertos">
                                        <option value="semanal">Semanal</option>
                                        <option value="mensual">Mensual</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoCerradosAbiertos('hoy')">Hoy</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoCerradosAbiertos('7dias')">Últimos 7 días</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" @click="setFiltroRapidoCerradosAbiertos('mes')">Mes actual</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="setFiltroRapidoCerradosAbiertos('año')">Año actual</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container"><div id="chartCerradosAbiertos"></div></div>
                    <div class="mt-3">
                        <div class="card">
                            <div class="card-body">
                                <h6>Tasas de Cierre por Período</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <span v-for="(tasa, idx) in datosCerradosAbiertos.tasas" :key="idx" class="badge" :class="tasa >= 50 ? 'bg-success' : 'bg-warning'">
                                        {{ datosCerradosAbiertos.labels[idx] }}: {{ tasa }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="exportarGraficoCerradosAbiertos"><i class="bi bi-download"></i> Exportar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Gráfico de Tasa de Cierre -->
    <div class="modal fade" id="modalGraficoTasaCierre" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-percent text-info"></i> Tasa de Cierre de Reclamos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <h6><i class="bi bi-info-circle"></i> ¿Qué muestra este gráfico?</h6>
                        <p class="mb-0">Muestra el porcentaje de reclamos cerrados sobre el total por período, con una meta objetivo del 95%. Permite asegurar que los reclamos se cierran correctamente.</p>
                    </div>
                    <div class="card mb-3">
                        <div class="card-header"><h6 class="mb-0"><i class="bi bi-funnel"></i> Filtros</h6></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Desde</label>
                                    <input type="date" class="form-control" v-model="filtrosTasaCierre.fechaDesde" @change="cargarGraficoTasaCierre">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fecha Hasta</label>
                                    <input type="date" class="form-control" v-model="filtrosTasaCierre.fechaHasta" @change="cargarGraficoTasaCierre">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Granularidad</label>
                                    <select class="form-select" v-model="filtrosTasaCierre.granularidad" @change="cargarGraficoTasaCierre">
                                        <option value="semanal">Semanal</option>
                                        <option value="mensual">Mensual</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container"><div id="chartTasaCierre"></div></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" @click="exportarGraficoTasaCierre"><i class="bi bi-download"></i> Exportar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

