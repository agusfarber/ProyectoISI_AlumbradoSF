<div id="app" class="analisis-page">
    <div class="app-page-title">
        <span class="app-page-title__icon"><i class="bi bi-graph-up"></i></span>
        <h1 class="app-page-title__text">Análisis</h1>
    </div>

    <!-- Filtro de período global (KPI + vistas previas) -->
    <section class="analisis-periodo-bar">
        <div class="analisis-periodo-bar__title">
            <span class="analisis-periodo-bar__icon"><i class="bi bi-calendar3"></i></span>
            <h6>Período</h6>
        </div>
        <div class="analisis-periodo-bar__filters">
            <div class="analisis-periodo-bar__dates">
                <label>Desde:</label>
                <input type="date" class="form-control" v-model="filtrosPeriodo.fechaDesde" @change="aplicarPeriodoGlobal">
                <label>Hasta:</label>
                <input type="date" class="form-control" v-model="filtrosPeriodo.fechaHasta" @change="aplicarPeriodoGlobal">
            </div>
            <div class="analisis-periodo-bar__quick">
                <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosPeriodo, 'hoy') }" @click="setFiltroPeriodoGlobal('hoy')">Hoy</button>
                <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosPeriodo, 'semana') }" @click="setFiltroPeriodoGlobal('semana')">Últimos 7 días</button>
                <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosPeriodo, 'mes') }" @click="setFiltroPeriodoGlobal('mes')">Mes actual</button>
                <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosPeriodo, 'año') }" @click="setFiltroPeriodoGlobal('año')">Año actual</button>
            </div>
        </div>
    </section>

    <!-- Panel KPI -->
    <section class="analisis-kpi-panel">
        <div class="analisis-kpi-body">
            <div class="analisis-kpi-grid analisis-kpi-grid--estados">
                <div class="kpi-card kpi-card--estado" title="Ingresados al sistema, sin asignar a cuadrilla">
                    <div class="kpi-icon kpi-icon--recibido">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <div class="kpi-content">
                        <h6 class="kpi-label">Recibidos</h6>
                        <div class="kpi-card__nums">
                            <h3 class="kpi-value kpi-value--recibido">{{ kpiResumen.total_recibidos || 0 }}</h3>
                            <span class="kpi-pct">{{ porcentajeSobreTotal(kpiResumen.total_recibidos) }}%</span>
                        </div>
                    </div>
                </div>
                <div class="kpi-card kpi-card--estado" title="En hoja de ruta asignada a una cuadrilla">
                    <div class="kpi-icon kpi-icon--asignado">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="kpi-content">
                        <h6 class="kpi-label">Asignados</h6>
                        <div class="kpi-card__nums">
                            <h3 class="kpi-value kpi-value--asignado">{{ kpiResumen.total_asignados || 0 }}</h3>
                            <span class="kpi-pct">{{ porcentajeSobreTotal(kpiResumen.total_asignados) }}%</span>
                        </div>
                    </div>
                </div>
                <div class="kpi-card kpi-card--estado" title="Obra pausada / para otro día">
                    <div class="kpi-icon kpi-icon--pendiente">
                        <i class="bi bi-pause-circle"></i>
                    </div>
                    <div class="kpi-content">
                        <h6 class="kpi-label">Pendientes</h6>
                        <div class="kpi-card__nums">
                            <h3 class="kpi-value kpi-value--pendiente">{{ kpiResumen.total_pendientes || 0 }}</h3>
                            <span class="kpi-pct">{{ porcentajeSobreTotal(kpiResumen.total_pendientes) }}%</span>
                        </div>
                    </div>
                </div>
                <div class="kpi-card kpi-card--estado" title="Trabajo activo en obra">
                    <div class="kpi-icon kpi-icon--ejecucion">
                        <i class="bi bi-gear"></i>
                    </div>
                    <div class="kpi-content">
                        <h6 class="kpi-label">En ejecución</h6>
                        <div class="kpi-card__nums">
                            <h3 class="kpi-value kpi-value--ejecucion">{{ kpiResumen.total_en_ejecucion || 0 }}</h3>
                            <span class="kpi-pct">{{ porcentajeSobreTotal(kpiResumen.total_en_ejecucion) }}%</span>
                        </div>
                    </div>
                </div>
                <div class="kpi-card kpi-card--estado" title="Trabajo terminado en campo (aún sin cierre formal)">
                    <div class="kpi-icon kpi-icon--completado">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="kpi-content">
                        <h6 class="kpi-label">Completados</h6>
                        <div class="kpi-card__nums">
                            <h3 class="kpi-value kpi-value--completado">{{ kpiResumen.total_completados || 0 }}</h3>
                            <span class="kpi-pct">{{ porcentajeSobreTotal(kpiResumen.total_completados) }}%</span>
                        </div>
                    </div>
                </div>
                <div class="kpi-card kpi-card--estado" title="Cierre formal del reclamo">
                    <div class="kpi-icon kpi-icon--cerrado">
                        <i class="bi bi-check2-all"></i>
                    </div>
                    <div class="kpi-content">
                        <h6 class="kpi-label">Cerrados</h6>
                        <div class="kpi-card__nums">
                            <h3 class="kpi-value kpi-value--cerrado">{{ kpiResumen.total_cerrados || 0 }}</h3>
                            <span class="kpi-pct">{{ porcentajeSobreTotal(kpiResumen.total_cerrados) }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="analisis-kpi-grid analisis-kpi-grid--metricas">
                <div class="kpi-card kpi-card--metric" title="Cerrado / total del período">
                    <div class="kpi-icon kpi-icon--tasa">
                        <i class="bi bi-percent"></i>
                    </div>
                    <div class="kpi-content">
                        <h6 class="kpi-label">Tasa de cierre</h6>
                        <h3 class="kpi-value kpi-value--completado">{{ kpiResumen.tasa_resolucion || 0 }}%</h3>
                    </div>
                </div>
                <div class="kpi-card kpi-card--metric" title="Promedio desde el ingreso del reclamo hasta el cierre formal">
                    <div class="kpi-icon kpi-icon--tiempo">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="kpi-content">
                        <h6 class="kpi-label">Tiempo promedio de cierre</h6>
                        <h3 class="kpi-value kpi-value--tiempo">{{ kpiResumen.tiempo_promedio_dias || 0 }} días</h3>
                        <small class="text-muted">{{ kpiResumen.tiempo_promedio_horas || 0 }} horas</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Grid de Gráficos -->
    <div class="analisis-charts-grid">
        <article class="analisis-chart-card" @click="abrirModalGrafico('estado')">
            <div class="analisis-chart-card__top">
                <div class="analisis-chart-card__icon"><i class="bi bi-pie-chart-fill"></i></div>
                <h5 class="analisis-chart-card__title">Distribución de Reclamos por Estado</h5>
            </div>
            <p class="analisis-chart-card__desc">Distribución por estado operativo, incluyendo cierre formal</p>
            <div class="chart-preview-container">
                <div id="previewChartEstado"></div>
            </div>
        </article>

        <article class="analisis-chart-card" @click="abrirModalGrafico('motivo')">
            <div class="analisis-chart-card__top">
                <div class="analisis-chart-card__icon"><i class="bi bi-bar-chart-fill"></i></div>
                <h5 class="analisis-chart-card__title">Reclamos por Motivo</h5>
            </div>
            <p class="analisis-chart-card__desc">Identifica los tipos de problemas más frecuentes</p>
            <div class="chart-preview-container">
                <div id="previewChartMotivo"></div>
            </div>
        </article>

        <article class="analisis-chart-card analisis-chart-card--wide" @click="abrirModalGrafico('evolucion')">
            <div class="analisis-chart-card__top">
                <div class="analisis-chart-card__icon"><i class="bi bi-graph-up-arrow"></i></div>
                <h5 class="analisis-chart-card__title">Ingresos vs Cierres</h5>
            </div>
            <p class="analisis-chart-card__desc">Ingresos vs cierres a lo largo del tiempo</p>
            <div class="chart-preview-container">
                <div id="previewChartEvolucion"></div>
            </div>
        </article>

        <article class="analisis-chart-card" @click="abrirModalGrafico('tiempoPromedio')">
            <div class="analisis-chart-card__top">
                <div class="analisis-chart-card__icon"><i class="bi bi-clock-history"></i></div>
                <h5 class="analisis-chart-card__title">Tiempo promedio de reparación por motivo</h5>
            </div>
            <p class="analisis-chart-card__desc">Minutos de obra (cronómetro) promedio según el motivo</p>
            <div class="chart-preview-container chart-preview-container--tiempo">
                <div id="previewChartTiempoPromedio"></div>
            </div>
        </article>

        <article class="analisis-chart-card" @click="abrirModalGrafico('evolucionTiempo')">
            <div class="analisis-chart-card__top">
                <div class="analisis-chart-card__icon"><i class="bi bi-graph-up-arrow"></i></div>
                <h5 class="analisis-chart-card__title">Evolución del tiempo promedio de reparación</h5>
            </div>
            <p class="analisis-chart-card__desc">Cómo cambian los minutos de obra por motivo en el tiempo</p>
            <div class="chart-preview-container chart-preview-container--tiempo">
                <div id="previewChartEvolucionTiempo"></div>
            </div>
        </article>

        <article class="analisis-chart-card" @click="abrirModalGrafico('antiguedad')">
            <div class="analisis-chart-card__top">
                <div class="analisis-chart-card__icon"><i class="bi bi-hourglass-split"></i></div>
                <h5 class="analisis-chart-card__title">Antigüedad de abiertos</h5>
            </div>
            <p class="analisis-chart-card__desc">Backlog sin cierre formal por rango de días</p>
            <div class="chart-preview-container">
                <div id="previewChartAntiguedad"></div>
            </div>
        </article>

        <article class="analisis-chart-card" @click="abrirModalGrafico('mapaCalor')">
            <div class="analisis-chart-card__top">
                <div class="analisis-chart-card__icon"><i class="bi bi-geo-alt-fill"></i></div>
                <h5 class="analisis-chart-card__title">Mapa de calor de zonas</h5>
            </div>
            <p class="analisis-chart-card__desc">Dónde se concentran los reclamos en San Francisco</p>
            <div class="chart-preview-container chart-preview-container--mapa">
                <div id="previewMapaCalor" class="analisis-mapa-calor analisis-mapa-calor--preview" @click.stop="abrirModalGrafico('mapaCalor')"></div>
            </div>
        </article>

        <article class="analisis-chart-card" @click="abrirModalGrafico('consumoMateriales')">
            <div class="analisis-chart-card__top">
                <div class="analisis-chart-card__icon"><i class="bi bi-box-seam"></i></div>
                <h5 class="analisis-chart-card__title">Consumo de Materiales por Período</h5>
            </div>
            <p class="analisis-chart-card__desc">Cantidades registradas en obra por período</p>
            <div class="chart-preview-container">
                <div id="previewChartConsumoMateriales"></div>
            </div>
        </article>
    </div>

    <!-- Modal para Gráfico de Estado -->
    <div class="modal fade" id="modalGraficoEstado" tabindex="-1" aria-labelledby="modalGraficoEstadoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rutas-modal analisis-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-pie-chart-fill"></i></span>
                        <h5 id="modalGraficoEstadoLabel">Distribución de Reclamos por Estado</h5>
                        <span class="analisis-info-tip" tabindex="0" aria-label="Explicación del gráfico">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <span class="analisis-info-tip__popup" role="tooltip">
                                <strong>¿Qué muestra este gráfico?</strong>
                                <p>Distribución por estado operativo: Recibido (ingreso), Asignado (en hoja de ruta), Pendiente (obra pausada), En ejecución (trabajo activo), Completado (terminado en campo) y Cerrado (cierre formal).</p><p>Utilidad: ver el estado general del sistema e identificar cuellos de botella.</p>
                            </span>
                        </span>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body analisis-modal-body--split">
                    <aside class="analisis-filtros-side">
                        <h6 class="analisis-filtros-side__title"><i class="bi bi-funnel"></i> Filtros</h6>
                        <div class="analisis-filtros-side__body">
                            <div class="analisis-filtros__quick">
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEstado, '7dias') }" @click="setFiltroRapidoEstado('7dias')">Últimos 7 días</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEstado, '30dias') }" @click="setFiltroRapidoEstado('30dias')">Últimos 30 días</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEstado, 'mes') }" @click="setFiltroRapidoEstado('mes')">Mes actual</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEstado, 'año') }" @click="setFiltroRapidoEstado('año')">Año actual</button>
                                    </div>
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
                                    </div>
                    </aside>
                    <div class="analisis-modal-main">
                        <div class="chart-container">
                            <div id="chartEstado"></div>
                        </div>
                        </div>
                </div>
                <div class="analisis-modal-actions">
                            <button type="button" class="rutas-btn" @click="exportarGraficoEstado">
                                <i class="bi bi-download"></i> Exportar como Imagen
                            </button>
                            <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                        </div>

            </div>
        </div>
    </div>

    <!-- Modal para Gráfico de Motivo -->
    <div class="modal fade" id="modalGraficoMotivo" tabindex="-1" aria-labelledby="modalGraficoMotivoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rutas-modal analisis-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-bar-chart-fill"></i></span>
                        <h5 id="modalGraficoMotivoLabel">Reclamos por Motivo</h5>
                        <span class="analisis-info-tip" tabindex="0" aria-label="Explicación del gráfico">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <span class="analisis-info-tip__popup" role="tooltip">
                                <strong>¿Qué muestra este gráfico?</strong>
                                <p>Este gráfico de barras muestra la cantidad de reclamos agrupados por motivo, ordenados de mayor a menor frecuencia.</p><p>Utilidad: Permite identificar los tipos de problemas más frecuentes, facilitando la planificación de recursos, la asignación de cuadrillas y la toma de decisiones estratégicas para mejorar el servicio de alumbrado público.</p><p>Podés activar <strong>Comparar con otro período</strong> para ver un gráfico por cada ventana de fechas.</p>
                            </span>
                        </span>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body analisis-modal-body--split">
                    <aside class="analisis-filtros-side">
                        <h6 class="analisis-filtros-side__title"><i class="bi bi-funnel"></i> Filtros</h6>
                        <div class="analisis-filtros-side__body">
                            <div class="analisis-filtros__quick">
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosMotivo, '7dias') }" @click="setFiltroRapidoMotivo('7dias')">Últimos 7 días</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosMotivo, '30dias') }" @click="setFiltroRapidoMotivo('30dias')">Últimos 30 días</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosMotivo, 'mes') }" @click="setFiltroRapidoMotivo('mes')">Mes actual</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosMotivo, 'año') }" @click="setFiltroRapidoMotivo('año')">Año actual</button>
                                    </div>
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
                                                <option value="Pendiente">Pendiente</option>
                                                <option value="En ejecución">En ejecución</option>
                                                <option value="Completado">Completado</option>
                                                <option value="Cerrado">Cerrado</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="analisis-comparacion mt-3">
                                        <label class="analisis-comparacion__toggle">
                                            <input type="checkbox" v-model="filtrosMotivo.comparar" @change="onToggleComparacion(filtrosMotivo, () => cargarGraficoMotivo())">
                                            <span>Comparar con otro período</span>
                                        </label>
                                        <div v-if="filtrosMotivo.comparar" class="analisis-comparacion__body">
                                            <button type="button" class="analisis-chip analisis-chip--soft" @click="usarPeriodoAnteriorComparacion(filtrosMotivo, () => cargarGraficoMotivo())">Vs período anterior</button>
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label">Desde (B)</label>
                                                    <input type="date" class="form-control" v-model="filtrosMotivo.fechaDesdeB" @change="cargarGraficoMotivo">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Hasta (B)</label>
                                                    <input type="date" class="form-control" v-model="filtrosMotivo.fechaHastaB" @change="cargarGraficoMotivo">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                    </aside>
                    <div class="analisis-modal-main">
                        <div class="analisis-charts-compare" :class="{ 'is-dual': periodoComparacionActivo(filtrosMotivo) }">
                            <div class="analisis-charts-compare__pane">
                                <h6 v-if="periodoComparacionActivo(filtrosMotivo)" class="analisis-charts-compare__title">
                                    Período A · {{ etiquetaRangoFechas(filtrosMotivo.fechaDesde, filtrosMotivo.fechaHasta) }}
                                </h6>
                                <div class="chart-container">
                                    <div id="chartMotivo"></div>
                                </div>
                            </div>
                            <div v-if="periodoComparacionActivo(filtrosMotivo)" class="analisis-charts-compare__pane">
                                <h6 class="analisis-charts-compare__title analisis-charts-compare__title--b">
                                    Período B · {{ etiquetaRangoFechas(filtrosMotivo.fechaDesdeB, filtrosMotivo.fechaHastaB) }}
                                </h6>
                                <div class="chart-container">
                                    <div id="chartMotivoB"></div>
                                </div>
                            </div>
                        </div>
                        </div>
                </div>
                <div class="analisis-modal-actions">
                            <button type="button" class="rutas-btn" @click="exportarGraficoMotivo">
                                <i class="bi bi-download"></i> Exportar como Imagen
                            </button>
                            <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                        </div>

            </div>
        </div>
    </div>

    <!-- Modal para Gráfico de Evolución Temporal -->
    <div class="modal fade" id="modalGraficoEvolucion" tabindex="-1" aria-labelledby="modalGraficoEvolucionLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rutas-modal analisis-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-graph-up-arrow"></i></span>
                        <h5 id="modalGraficoEvolucionLabel">Ingresos vs Cierres</h5>
                        <span class="analisis-info-tip" tabindex="0" aria-label="Explicación del gráfico">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <span class="analisis-info-tip__popup" role="tooltip">
                                <strong>¿Qué muestra este gráfico?</strong>
                                <p>Dos series: <strong>Ingresos</strong> (por fecha de alta) y <strong>Cierres</strong> (por fecha de cierre formal). Compara cuántos reclamos entran y cuántos se cierran en cada período.</p><p>Utilidad: detectar picos de demanda, retrasos de cierre y si el flujo de salida acompaña al de entrada.</p>
                            </span>
                        </span>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body analisis-modal-body--split">
                    <aside class="analisis-filtros-side">
                        <h6 class="analisis-filtros-side__title"><i class="bi bi-funnel"></i> Filtros</h6>
                        <div class="analisis-filtros-side__body">
                            <div class="analisis-filtros__quick">
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEvolucion, '7dias') }" @click="setFiltroRapidoEvolucion('7dias')">Últimos 7 días</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEvolucion, '30dias') }" @click="setFiltroRapidoEvolucion('30dias')">Últimos 30 días</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEvolucion, '3meses') }" @click="setFiltroRapidoEvolucion('3meses')">3 meses</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEvolucion, '6meses') }" @click="setFiltroRapidoEvolucion('6meses')">6 meses</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEvolucion, 'año') }" @click="setFiltroRapidoEvolucion('año')">Año actual</button>
                                    </div>
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
                                    </div>
                    </aside>
                    <div class="analisis-modal-main">
                        <div class="chart-container">
                            <div id="chartEvolucion"></div>
                        </div>
                        </div>
                </div>
                <div class="analisis-modal-actions">
                            <button type="button" class="rutas-btn" @click="exportarGraficoEvolucion">
                                <i class="bi bi-download"></i> Exportar como Imagen
                            </button>
                            <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                        </div>

            </div>
        </div>
    </div>

    <!-- Modal: tiempo promedio de reparación por motivo -->
    <div class="modal fade" id="modalGraficoTiempoPromedio" tabindex="-1" aria-labelledby="modalGraficoTiempoPromedioLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rutas-modal analisis-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-clock-history"></i></span>
                        <h5 id="modalGraficoTiempoPromedioLabel">Tiempo promedio de reparación por motivo</h5>
                        <span class="analisis-info-tip" tabindex="0" aria-label="Explicación del gráfico">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <span class="analisis-info-tip__popup" role="tooltip">
                                <strong>¿Qué muestra este gráfico?</strong>
                                <p>Promedio de minutos de trabajo en obra (cronómetro) por motivo. No es la demora hasta el cierre del reclamo, sino el tiempo de reparación en campo. Se muestran todos los motivos del catálogo.</p><p>Con <strong>Comparar con otro período</strong> se muestra un gráfico por cada ventana de fechas.</p>
                            </span>
                        </span>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body analisis-modal-body--split">
                    <aside class="analisis-filtros-side">
                        <h6 class="analisis-filtros-side__title"><i class="bi bi-funnel"></i> Filtros</h6>
                        <div class="analisis-filtros-side__body">
                            <div class="analisis-filtros__quick">
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosTiempo, 'hoy') }" @click="setFiltroRapidoTiempoPromedio('hoy')">Hoy</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosTiempo, '7dias') }" @click="setFiltroRapidoTiempoPromedio('7dias')">Últimos 7 días</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosTiempo, '30dias') }" @click="setFiltroRapidoTiempoPromedio('30dias')">Últimos 30 días</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosTiempo, 'mes') }" @click="setFiltroRapidoTiempoPromedio('mes')">Mes actual</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosTiempo, 'año') }" @click="setFiltroRapidoTiempoPromedio('año')">Año actual</button>
                                    </div>
                        <div class="row g-3">
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
                                    <div class="analisis-comparacion mt-3">
                                        <label class="analisis-comparacion__toggle">
                                            <input type="checkbox" v-model="filtrosTiempo.comparar" @change="onToggleComparacion(filtrosTiempo, () => cargarGraficoTiempoPromedio())">
                                            <span>Comparar con otro período</span>
                                        </label>
                                        <div v-if="filtrosTiempo.comparar" class="analisis-comparacion__body">
                                            <button type="button" class="analisis-chip analisis-chip--soft" @click="usarPeriodoAnteriorComparacion(filtrosTiempo, () => cargarGraficoTiempoPromedio())">Vs período anterior</button>
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label">Desde (B)</label>
                                                    <input type="date" class="form-control" v-model="filtrosTiempo.fechaDesdeB" @change="cargarGraficoTiempoPromedio">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Hasta (B)</label>
                                                    <input type="date" class="form-control" v-model="filtrosTiempo.fechaHastaB" @change="cargarGraficoTiempoPromedio">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                    </aside>
                    <div class="analisis-modal-main">
                        <div class="analisis-charts-compare" :class="{ 'is-dual': periodoComparacionActivo(filtrosTiempo) }">
                            <div class="analisis-charts-compare__pane">
                                <h6 v-if="periodoComparacionActivo(filtrosTiempo)" class="analisis-charts-compare__title">
                                    Período A · {{ etiquetaRangoFechas(filtrosTiempo.fechaDesde, filtrosTiempo.fechaHasta) }}
                                </h6>
                                <div class="chart-container">
                                    <div id="chartTiempoPromedio"></div>
                                </div>
                            </div>
                            <div v-if="periodoComparacionActivo(filtrosTiempo)" class="analisis-charts-compare__pane">
                                <h6 class="analisis-charts-compare__title analisis-charts-compare__title--b">
                                    Período B · {{ etiquetaRangoFechas(filtrosTiempo.fechaDesdeB, filtrosTiempo.fechaHastaB) }}
                                </h6>
                                <div class="chart-container">
                                    <div id="chartTiempoPromedioB"></div>
                                </div>
                            </div>
                        </div>
                        </div>
                </div>
                <div class="analisis-modal-actions">
                            <button type="button" class="rutas-btn" @click="exportarGraficoTiempoPromedio">
                                <i class="bi bi-download"></i> Exportar como Imagen
                            </button>
                            <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                        </div>

            </div>
        </div>
    </div>

    <!-- Modal: evolución del tiempo promedio de reparación -->
    <div class="modal fade" id="modalGraficoEvolucionTiempo" tabindex="-1" aria-labelledby="modalGraficoEvolucionTiempoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rutas-modal analisis-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-graph-up-arrow"></i></span>
                        <h5 id="modalGraficoEvolucionTiempoLabel">Evolución del tiempo promedio de reparación</h5>
                        <span class="analisis-info-tip" tabindex="0" aria-label="Explicación del gráfico">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <span class="analisis-info-tip__popup" role="tooltip">
                                <strong>¿Qué muestra este gráfico?</strong>
                                <p>Evolución diaria, semanal o mensual del promedio de minutos de obra (cronómetro) por motivo. Incluye todos los motivos del catálogo; al pasar el mouse se ve el nombre completo y el valor en minutos.</p>
                            </span>
                        </span>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body analisis-modal-body--split">
                    <aside class="analisis-filtros-side">
                        <h6 class="analisis-filtros-side__title"><i class="bi bi-funnel"></i> Filtros</h6>
                        <div class="analisis-filtros-side__body">
                            <div class="analisis-filtros__quick">
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEvolucionTiempo, 'hoy') }" @click="setFiltroRapidoEvolucionTiempo('hoy')">Hoy</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEvolucionTiempo, '7dias') }" @click="setFiltroRapidoEvolucionTiempo('7dias')">Últimos 7 días</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEvolucionTiempo, '30dias') }" @click="setFiltroRapidoEvolucionTiempo('30dias')">Últimos 30 días</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEvolucionTiempo, 'mes') }" @click="setFiltroRapidoEvolucionTiempo('mes')">Mes actual</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosEvolucionTiempo, 'año') }" @click="setFiltroRapidoEvolucionTiempo('año')">Año actual</button>
                                    </div>
                        <div class="row g-3">
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
                                                <option value="diario">Diario</option>
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
                                    </div>
                    </aside>
                    <div class="analisis-modal-main">
                        <div class="chart-container">
                            <div id="chartEvolucionTiempo"></div>
                        </div>
                        </div>
                </div>
                <div class="analisis-modal-actions">
                            <button type="button" class="rutas-btn" @click="exportarGraficoEvolucionTiempo">
                                <i class="bi bi-download"></i> Exportar como Imagen
                            </button>
                            <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                        </div>

            </div>
        </div>
    </div>

    <!-- Modal para Gráfico de Antigüedad de Abiertos -->
    <div class="modal fade" id="modalGraficoAntiguedad" tabindex="-1" aria-labelledby="modalGraficoAntiguedadLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rutas-modal analisis-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-hourglass-split"></i></span>
                        <h5 id="modalGraficoAntiguedadLabel">Antigüedad de abiertos</h5>
                        <span class="analisis-info-tip" tabindex="0" aria-label="Explicación del gráfico">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <span class="analisis-info-tip__popup" role="tooltip">
                                <strong>¿Qué muestra este gráfico?</strong>
                                <p>Foto actual del backlog sin cierre formal, agrupado por antigüedad desde la fecha de alta: 0–3, 4–7, 8–15 y +15 días.</p><p>No usa el período global: incluye todos los abiertos para no ocultar los más viejos. Podés filtrar por prioridad.</p>
                            </span>
                        </span>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body analisis-modal-body--split">
                    <aside class="analisis-filtros-side">
                        <h6 class="analisis-filtros-side__title"><i class="bi bi-funnel"></i> Filtros</h6>
                        <div class="analisis-filtros-side__body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="filtroPrioridadAntiguedad" class="form-label">Prioridad</label>
                                    <select id="filtroPrioridadAntiguedad" class="form-select" v-model="filtrosAntiguedad.prioridad" @change="cargarGraficoAntiguedad">
                                        <option value="">Todas</option>
                                        <option value="Alta">Alta</option>
                                        <option value="Baja">Baja</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <p class="text-muted small mb-0">Total abiertos: <strong>{{ datosAntiguedad.total || 0 }}</strong></p>
                                </div>
                            </div>
                        </div>
                    </aside>
                    <div class="analisis-modal-main">
                        <div class="chart-container">
                            <div id="chartAntiguedad"></div>
                        </div>
                    </div>
                </div>
                <div class="analisis-modal-actions">
                    <button type="button" class="rutas-btn" @click="exportarGraficoAntiguedad">
                        <i class="bi bi-download"></i> Exportar como Imagen
                    </button>
                    <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Mapa de calor de zonas -->
    <div class="modal fade" id="modalGraficoMapaCalor" tabindex="-1" aria-labelledby="modalGraficoMapaCalorLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rutas-modal analisis-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-geo-alt-fill"></i></span>
                        <h5 id="modalGraficoMapaCalorLabel">Mapa de calor de zonas</h5>
                        <span class="analisis-info-tip" tabindex="0" aria-label="Explicación del gráfico">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <span class="analisis-info-tip__popup" role="tooltip">
                                <strong>¿Qué muestra este gráfico?</strong>
                                <p>Concentración de reclamos en San Francisco (Córdoba) según domicilio geocodificado. Usa reclamos + coordenadas de la tabla direcciones.</p>
                            </span>
                        </span>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body analisis-modal-body--split">
                    <aside class="analisis-filtros-side">
                        <h6 class="analisis-filtros-side__title"><i class="bi bi-funnel"></i> Filtros</h6>
                        <div class="analisis-filtros-side__body">
                            <div class="analisis-filtros__quick">
                                <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosMapaCalor, '7dias') }" @click="setFiltroRapidoMapaCalor('7dias')">Últimos 7 días</button>
                                <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosMapaCalor, '30dias') }" @click="setFiltroRapidoMapaCalor('30dias')">Últimos 30 días</button>
                                <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosMapaCalor, 'mes') }" @click="setFiltroRapidoMapaCalor('mes')">Mes actual</button>
                                <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosMapaCalor, 'año') }" @click="setFiltroRapidoMapaCalor('año')">Año actual</button>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Fecha Desde</label>
                                    <input type="date" class="form-control" v-model="filtrosMapaCalor.fechaDesde" @change="cargarGraficoMapaCalor">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Fecha Hasta</label>
                                    <input type="date" class="form-control" v-model="filtrosMapaCalor.fechaHasta" @change="cargarGraficoMapaCalor">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select" v-model="filtrosMapaCalor.estado" @change="cargarGraficoMapaCalor">
                                        <option value="">Todos</option>
                                        <option value="Recibido">Recibido</option>
                                        <option value="Asignado">Asignado</option>
                                        <option value="Pendiente">Pendiente</option>
                                        <option value="En ejecución">En ejecución</option>
                                        <option value="Completado">Completado</option>
                                        <option value="Cerrado">Cerrado</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Prioridad</label>
                                    <select class="form-select" v-model="filtrosMapaCalor.prioridad" @change="cargarGraficoMapaCalor">
                                        <option value="">Todas</option>
                                        <option value="Alta">Alta</option>
                                        <option value="Baja">Baja</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Motivo</label>
                                    <select class="form-select" v-model="filtrosMapaCalor.motivo" @change="cargarGraficoMapaCalor">
                                        <option value="">Todos</option>
                                        <option v-for="motivo in motivosDisponibles" :key="'mc-' + motivo" :value="motivo">{{ motivo }}</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <p class="text-muted small mb-1">Puntos en mapa: <strong>{{ datosMapaCalor.puntos || 0 }}</strong></p>
                                    <p class="text-muted small mb-1">Con coordenadas: <strong>{{ datosMapaCalor.con_coordenadas || 0 }}</strong></p>
                                    <p class="text-muted small mb-0">Sin coordenadas: <strong>{{ datosMapaCalor.sin_coordenadas || 0 }}</strong></p>
                                </div>
                            </div>
                        </div>
                    </aside>
                    <div class="analisis-modal-main">
                        <div class="analisis-mapa-calor-wrap">
                            <div id="mapaCalorMapbox" class="analisis-mapa-calor"></div>
                        </div>
                    </div>
                </div>
                <div class="analisis-modal-actions">
                    <button type="button" class="rutas-btn" @click="exportarMapaCalor">
                        <i class="bi bi-download"></i> Exportar como Imagen
                    </button>
                    <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Gráfico de Consumo de Materiales -->
    <div class="modal fade" id="modalGraficoConsumoMateriales" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content rutas-modal analisis-modal">
                <div class="rutas-modal__header">
                    <div class="rutas-modal__title">
                        <span class="rutas-modal__icon"><i class="bi bi-box-seam"></i></span>
                        <h5>Consumo de Materiales por Período</h5>
                        <span class="analisis-info-tip" tabindex="0" aria-label="Explicación del gráfico">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <span class="analisis-info-tip__popup" role="tooltip">
                                <strong>¿Qué muestra este gráfico?</strong>
                                <p>Evolución del consumo real registrado en obra (suma de cantidades). Se puede acotar por categoría y/o material. Sin material específico muestra el Top 5 del recorte actual. La granularidad (día, semana o mes) define el eje temporal.</p><p>En modo comparación se muestran <strong>dos gráficos</strong>, uno por período.</p>
                            </span>
                        </span>
                    </div>
                    <button type="button" class="rutas-modal__close" data-bs-dismiss="modal" aria-label="Cerrar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="modal-body analisis-modal-body--split">
                    <aside class="analisis-filtros-side">
                        <h6 class="analisis-filtros-side__title"><i class="bi bi-funnel"></i> Filtros</h6>
                        <div class="analisis-filtros-side__body">
                            <div class="analisis-filtros__quick">
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosConsumoMateriales, 'hoy') }" @click="setFiltroRapidoConsumoMateriales('hoy')">Hoy</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosConsumoMateriales, '7dias') }" @click="setFiltroRapidoConsumoMateriales('7dias')">Últimos 7 días</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosConsumoMateriales, '30dias') }" @click="setFiltroRapidoConsumoMateriales('30dias')">Últimos 30 días</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosConsumoMateriales, 'mes') }" @click="setFiltroRapidoConsumoMateriales('mes')">Mes actual</button>
                                        <button type="button" class="analisis-chip" :class="{ active: periodoActivo(filtrosConsumoMateriales, 'año') }" @click="setFiltroRapidoConsumoMateriales('año')">Año actual</button>
                                    </div>
                        <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="filtroFechaDesdeConsumoMat">Fecha Desde</label>
                                            <input type="date" id="filtroFechaDesdeConsumoMat" class="form-control" v-model="filtrosConsumoMateriales.fechaDesde" @change="cargarGraficoConsumoMateriales">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="filtroFechaHastaConsumoMat">Fecha Hasta</label>
                                            <input type="date" id="filtroFechaHastaConsumoMat" class="form-control" v-model="filtrosConsumoMateriales.fechaHasta" @change="cargarGraficoConsumoMateriales">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="filtroGranularidadConsumoMat">Granularidad</label>
                                            <select id="filtroGranularidadConsumoMat" class="form-select" v-model="filtrosConsumoMateriales.granularidad" @change="cargarGraficoConsumoMateriales">
                                                <option value="diario">Diario</option>
                                                <option value="semanal">Semanal</option>
                                                <option value="mensual">Mensual</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="filtroCategoriaConsumoMat">Categoría</label>
                                            <select id="filtroCategoriaConsumoMat" class="form-select" v-model="filtrosConsumoMateriales.categoria" @change="onCambioCategoriaConsumoMateriales">
                                                <option value="Todas">Todas</option>
                                                <option v-for="cat in datosConsumoMateriales.categorias_disponibles" :key="cat" :value="cat">{{ cat }}</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="filtroMaterialConsumoMat">Material</label>
                                            <select id="filtroMaterialConsumoMat" class="form-select" v-model="filtrosConsumoMateriales.material" @change="cargarGraficoConsumoMateriales">
                                                <option value="Todos">Todos (Top 5)</option>
                                                <option v-for="mat in datosConsumoMateriales.materiales_disponibles" :key="mat" :value="mat">{{ mat }}</option>
                                            </select>
                                            <small class="text-muted d-block mt-1" v-if="datosConsumoMateriales.materiales_disponibles && datosConsumoMateriales.materiales_disponibles.length">
                                                {{ datosConsumoMateriales.materiales_disponibles.length }} con consumo
                                                <span v-if="filtrosConsumoMateriales.categoria !== 'Todas'"> en {{ filtrosConsumoMateriales.categoria }}</span>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="analisis-comparacion mt-3">
                                        <label class="analisis-comparacion__toggle">
                                            <input type="checkbox" v-model="filtrosConsumoMateriales.comparar" @change="onToggleComparacion(filtrosConsumoMateriales, () => cargarGraficoConsumoMateriales())">
                                            <span>Comparar con otro período</span>
                                        </label>
                                        <div v-if="filtrosConsumoMateriales.comparar" class="analisis-comparacion__body">
                                            <p class="analisis-comparacion__hint mb-1">Se muestran dos gráficos: uno por cada período.</p>
                                            <button type="button" class="analisis-chip analisis-chip--soft" @click="usarPeriodoAnteriorComparacion(filtrosConsumoMateriales, () => cargarGraficoConsumoMateriales())">Vs período anterior</button>
                                            <div class="row g-2 mt-1">
                                                <div class="col-6">
                                                    <label class="form-label">Desde (B)</label>
                                                    <input type="date" class="form-control" v-model="filtrosConsumoMateriales.fechaDesdeB" @change="cargarGraficoConsumoMateriales">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Hasta (B)</label>
                                                    <input type="date" class="form-control" v-model="filtrosConsumoMateriales.fechaHastaB" @change="cargarGraficoConsumoMateriales">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </div>
                    </aside>
                    <div class="analisis-modal-main">
                        <div class="analisis-charts-compare" :class="{ 'is-dual': periodoComparacionActivo(filtrosConsumoMateriales) }">
                            <div class="analisis-charts-compare__pane">
                                <h6 v-if="periodoComparacionActivo(filtrosConsumoMateriales)" class="analisis-charts-compare__title">
                                    Período A · {{ etiquetaRangoFechas(filtrosConsumoMateriales.fechaDesde, filtrosConsumoMateriales.fechaHasta) }}
                                </h6>
                                <div class="chart-container">
                                    <div id="chartConsumoMateriales"></div>
                                </div>
                            </div>
                            <div v-if="periodoComparacionActivo(filtrosConsumoMateriales)" class="analisis-charts-compare__pane">
                                <h6 class="analisis-charts-compare__title analisis-charts-compare__title--b">
                                    Período B · {{ etiquetaRangoFechas(filtrosConsumoMateriales.fechaDesdeB, filtrosConsumoMateriales.fechaHastaB) }}
                                </h6>
                                <div class="chart-container">
                                    <div id="chartConsumoMaterialesB"></div>
                                </div>
                            </div>
                        </div>
                        </div>
                </div>
                <div class="analisis-modal-actions">
                            <button type="button" class="rutas-btn" @click="exportarGraficoConsumoMateriales">
                                <i class="bi bi-download"></i> Exportar como Imagen
                            </button>
                            <button type="button" class="rutas-btn rutas-btn--outline" data-bs-dismiss="modal">Cerrar</button>
                        </div>

            </div>
        </div>
    </div>
</div>
