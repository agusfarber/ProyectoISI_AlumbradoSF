// Esperar a que Vue y ApexCharts estén listos
(function() {
    // Verificar si ApexCharts ya está cargado
    function initApp() {
        const app = Vue.createApp({
            data() {
                return {
                    // KPIs de Resumen
                    kpiResumen: {
                        total_activos: 0,
                        total_recibidos: 0,
                        total_asignados: 0,
                        total_pendientes: 0,
                        total_en_ejecucion: 0,
                        total_completados: 0,
                        total_cerrados: 0,
                        total: 0,
                        tasa_resolucion: 0,
                        tiempo_promedio_horas: 0,
                        tiempo_promedio_dias: 0
                    },
                    // Período global: afecta KPI + todas las vistas previas
                    filtrosPeriodo: {
                        fechaDesde: '',
                        fechaHasta: ''
                    },

                    // Gráfico de Estado
                    chartEstado: null,
                    previewChartEstado: null,
                    datosEstado: {
                        datos: [],
                        total: 0,
                        periodo: '',
                        filtros_aplicados: {}
                    },
                    filtrosEstado: {
                        fechaDesde: '',
                        fechaHasta: '',
                        prioridad: ''
                    },

                    // Gráfico de Motivo
                    chartMotivo: null,
                    chartMotivoB: null,
                    previewChartMotivo: null,
                    datosMotivo: {
                        datos: [],
                        total: 0,
                        periodo: '',
                        filtros_aplicados: {}
                    },
                    filtrosMotivo: {
                        fechaDesde: '',
                        fechaHasta: '',
                        estado: '',
                        comparar: false,
                        fechaDesdeB: '',
                        fechaHastaB: ''
                    },

                    // Gráfico de Evolución Temporal
                    chartEvolucion: null,
                    previewChartEvolucion: null,
                    datosEvolucion: {
                        labels: [],
                        series: [],
                        periodo: '',
                        granularidad: '',
                        filtros_aplicados: {}
                    },
                    filtrosEvolucion: {
                        fechaDesde: '',
                        fechaHasta: '',
                        granularidad: 'diario',
                        periodo: ''
                    },

                    // Gráfico de Tiempo Promedio por Motivo
                    chartTiempoPromedio: null,
                    chartTiempoPromedioB: null,
                    previewChartTiempoPromedio: null,
                    datosTiempo: {
                        datos: [],
                        total: 0,
                        periodo: '',
                        filtros_aplicados: {}
                    },
                    filtrosTiempo: {
                        fechaDesde: '',
                        fechaHasta: '',
                        motivo: 'Todos',
                        comparar: false,
                        fechaDesdeB: '',
                        fechaHastaB: ''
                    },

                    // Gráfico de Evolución del Tiempo Promedio
                    chartEvolucionTiempo: null,
                    previewChartEvolucionTiempo: null,
                    datosEvolucionTiempo: {
                        labels: [],
                        series: [],
                        periodo: '',
                        granularidad: '',
                        filtros_aplicados: {}
                    },
                    filtrosEvolucionTiempo: {
                        fechaDesde: '',
                        fechaHasta: '',
                        granularidad: 'semanal',
                        motivo: 'Todos'
                    },

                    // Motivos disponibles (se cargarán dinámicamente)
                    motivosDisponibles: [],

                    // Gráfico de Antigüedad de Abiertos
                    chartAntiguedad: null,
                    previewChartAntiguedad: null,
                    datosAntiguedad: {
                        labels: [],
                        series: [],
                        colors: [],
                        datos: [],
                        total: 0,
                        filtros_aplicados: {}
                    },
                    filtrosAntiguedad: {
                        prioridad: ''
                    },

                    // Gráfico de Consumo de Materiales
                    chartConsumoMateriales: null,
                    chartConsumoMaterialesB: null,
                    previewChartConsumoMateriales: null,
                    datosConsumoMateriales: {
                        labels: [],
                        series: [],
                        categorias_disponibles: [],
                        materiales_disponibles: [],
                        filtros_aplicados: {}
                    },
                    filtrosConsumoMateriales: {
                        fechaDesde: '',
                        fechaHasta: '',
                        granularidad: 'mensual',
                        categoria: 'Todas',
                        material: 'Todos',
                        comparar: false,
                        fechaDesdeB: '',
                        fechaHastaB: ''
                    },

                    // Mapa de calor de zonas (solo Análisis — Mapbox)
                    datosMapaCalor: {
                        datos: [],
                        total: 0,
                        puntos: 0,
                        con_coordenadas: 0,
                        sin_coordenadas: 0,
                        centro: { lat: -31.427, lng: -62.082, zoom: 13 }
                    },
                    filtrosMapaCalor: {
                        fechaDesde: '',
                        fechaHasta: '',
                        estado: '',
                        prioridad: '',
                        motivo: ''
                    },
                    mapaCalorPreviewMapbox: null,
                    mapaCalorMapbox: null,

                    // Auto-refresh (sin recargar la página)
                    intervaloAutoRefreshAnalisisMs: 30000,
                    modalAnalisisAbierto: null,
                    _pollAnalisis: null,
                    _refrescandoAnalisis: false,
                    _firmasAnalisis: {}
                };
            },
            mounted() {
                this.inicializarPeriodoGlobal();
                this.aplicarPeriodoGlobal();
                this.$nextTick(() => {
                    this.cargarMotivosDisponibles();
                    this.iniciarAutoRefreshAnalisis();
                });
            },
            unmounted() {
                this.detenerAutoRefreshAnalisis();
            },
            methods: {
                /**
                 * Inicializa el período global (mes actual) y lo propaga a todos los filtros.
                 */
                inicializarPeriodoGlobal() {
                    const rango = this.rangoPeriodo('año');
                    this.filtrosPeriodo.fechaDesde = rango.desde;
                    this.filtrosPeriodo.fechaHasta = rango.hasta;
                    this.sincronizarPeriodoEnFiltros();
                },

                /**
                 * Copia las fechas globales a los filtros de cada gráfico (preview + modal).
                 */
                sincronizarPeriodoEnFiltros() {
                    const desde = this.filtrosPeriodo.fechaDesde;
                    const hasta = this.filtrosPeriodo.fechaHasta;
                    const destinos = [
                        this.filtrosEstado,
                        this.filtrosMotivo,
                        this.filtrosEvolucion,
                        this.filtrosTiempo,
                        this.filtrosEvolucionTiempo,
                        this.filtrosConsumoMateriales,
                        this.filtrosMapaCalor,
                    ];
                    destinos.forEach((filtro) => {
                        filtro.fechaDesde = desde;
                        filtro.fechaHasta = hasta;
                    });
                },

                /**
                 * Aplica el período global: KPI + todas las vistas previas.
                 */
                aplicarPeriodoGlobal() {
                    this._firmasAnalisis = {};
                    this.sincronizarPeriodoEnFiltros();
                    this.cargarKpiResumen();
                    this.cargarTodasLasPreviews();
                },

                async cargarTodasLasPreviews() {
                    await Promise.all([
                        this.cargarPreviewEstado(),
                        this.cargarPreviewMotivo(),
                        this.cargarPreviewEvolucion(),
                        this.cargarPreviewTiempoPromedio(),
                        this.cargarPreviewEvolucionTiempo(),
                        this.cargarPreviewAntiguedad(),
                        this.cargarPreviewMapaCalor(),
                        this.cargarPreviewConsumoMateriales()
                    ]);
                },

                iniciarAutoRefreshAnalisis() {
                    if (this._pollAnalisis) return;

                    this._onVisibilityAnalisis = () => {
                        if (!document.hidden) {
                            void this.refrescarAnalisisEnVivo();
                        }
                    };
                    document.addEventListener('visibilitychange', this._onVisibilityAnalisis);

                    document.querySelectorAll('[id^="modalGrafico"]').forEach((el) => {
                        el.addEventListener('hidden.bs.modal', this._onModalAnalisisHidden);
                    });

                    this._pollAnalisis = setInterval(() => {
                        if (document.hidden) return;
                        void this.refrescarAnalisisEnVivo();
                    }, this.intervaloAutoRefreshAnalisisMs);
                },

                detenerAutoRefreshAnalisis() {
                    if (this._pollAnalisis) {
                        clearInterval(this._pollAnalisis);
                        this._pollAnalisis = null;
                    }
                    if (this._onVisibilityAnalisis) {
                        document.removeEventListener('visibilitychange', this._onVisibilityAnalisis);
                        this._onVisibilityAnalisis = null;
                    }
                    document.querySelectorAll('[id^="modalGrafico"]').forEach((el) => {
                        el.removeEventListener('hidden.bs.modal', this._onModalAnalisisHidden);
                    });
                },

                _onModalAnalisisHidden() {
                    this.modalAnalisisAbierto = null;
                },

                /**
                 * Evita redibujar si la respuesta es igual a la última aplicada.
                 * chartExistente: instancia Apex/mapa ya montada (si no hay, hay que dibujar igual).
                 */
                debeRedibujarAnalisis(clave, data, chartExistente = null) {
                    let firma = '';
                    try {
                        firma = JSON.stringify(data);
                    } catch (e) {
                        return true;
                    }
                    if (this._firmasAnalisis[clave] === firma && chartExistente) {
                        return false;
                    }
                    this._firmasAnalisis[clave] = firma;
                    return true;
                },

                /**
                 * Recarga KPI + previews (+ modal abierto) sin F5.
                 */
                async refrescarAnalisisEnVivo() {
                    if (this._refrescandoAnalisis) return;
                    this._refrescandoAnalisis = true;
                    try {
                        await this.cargarKpiResumen();
                        await this.cargarTodasLasPreviews();
                        await this.refrescarModalAnalisisAbierto();
                    } catch (error) {
                        console.warn('Auto-refresh Análisis:', error);
                    } finally {
                        this._refrescandoAnalisis = false;
                    }
                },

                async refrescarModalAnalisisAbierto() {
                    const tipo = this.modalAnalisisAbierto;
                    if (!tipo) return;

                    const visible = document.querySelector('.modal.show[id^="modalGrafico"]');
                    if (!visible) return;

                    switch (tipo) {
                        case 'estado':
                            await this.cargarGraficoEstado();
                            break;
                        case 'motivo':
                            await this.cargarGraficoMotivo();
                            break;
                        case 'evolucion':
                            await this.cargarGraficoEvolucion();
                            break;
                        case 'tiempoPromedio':
                            await this.cargarGraficoTiempoPromedio();
                            break;
                        case 'evolucionTiempo':
                            await this.cargarGraficoEvolucionTiempo();
                            break;
                        case 'antiguedad':
                            await this.cargarGraficoAntiguedad();
                            break;
                        case 'mapaCalor':
                            await this.cargarGraficoMapaCalor();
                            break;
                        case 'consumoMateriales':
                            await this.cargarGraficoConsumoMateriales();
                            break;
                        default:
                            break;
                    }
                },

                setFiltroPeriodoGlobal(tipo) {
                    const rango = this.rangoPeriodo(tipo);
                    this.filtrosPeriodo.fechaDesde = rango.desde;
                    this.filtrosPeriodo.fechaHasta = rango.hasta;
                    this.aplicarPeriodoGlobal();
                },

                /**
                 * % de un KPI de estado sobre el total del período.
                 */
                porcentajeSobreTotal(cantidad) {
                    const total = Number(this.kpiResumen.total) || 0;
                    const valor = Number(cantidad) || 0;
                    if (total <= 0) return '0.0';
                    return ((valor / total) * 100).toFixed(1);
                },

                /**
                 * Rango de fechas esperado para un preset rápido (misma lógica que setFiltroRapido*).
                 */
                rangoPeriodo(tipo) {
                    const fechaHasta = new Date();
                    const fechaDesde = new Date();

                    switch (tipo) {
                        case 'hoy':
                            break;
                        case '7dias':
                        case 'semana':
                            fechaDesde.setDate(fechaDesde.getDate() - 7);
                            break;
                        case '30dias':
                            fechaDesde.setDate(fechaDesde.getDate() - 30);
                            break;
                        case '3meses':
                            fechaDesde.setMonth(fechaDesde.getMonth() - 3);
                            break;
                        case '6meses':
                            fechaDesde.setMonth(fechaDesde.getMonth() - 6);
                            break;
                        case 'mes':
                            fechaDesde.setDate(1);
                            break;
                        case 'año':
                            fechaDesde.setMonth(0, 1);
                            break;
                        default:
                            return { desde: null, hasta: null };
                    }

                    return {
                        desde: fechaDesde.toISOString().split('T')[0],
                        hasta: fechaHasta.toISOString().split('T')[0]
                    };
                },

                /**
                 * True si las fechas del filtro coinciden exactamente con el preset (chip activo).
                 */
                periodoActivo(filtros, tipo) {
                    if (!filtros || !filtros.fechaDesde || !filtros.fechaHasta) return false;
                    const esperado = this.rangoPeriodo(tipo);
                    return filtros.fechaDesde === esperado.desde && filtros.fechaHasta === esperado.hasta;
                },

                fechaIsoLocal(date) {
                    const y = date.getFullYear();
                    const m = String(date.getMonth() + 1).padStart(2, '0');
                    const d = String(date.getDate()).padStart(2, '0');
                    return `${y}-${m}-${d}`;
                },

                etiquetaRangoFechas(desde, hasta) {
                    const fmt = (s) => {
                        if (!s) return '…';
                        const p = String(s).split('-');
                        if (p.length !== 3) return s;
                        return `${p[2]}/${p[1]}/${p[0]}`;
                    };
                    if (!desde && !hasta) return 'Sin fechas';
                    return `${fmt(desde)} – ${fmt(hasta)}`;
                },

                periodoComparacionActivo(filtros) {
                    return !!(filtros && filtros.comparar && filtros.fechaDesdeB && filtros.fechaHastaB);
                },

                /**
                 * Período B = ventana anterior de la misma duración que A.
                 */
                periodoAnteriorIgualDuracion(desde, hasta) {
                    if (!desde || !hasta) return { desde: '', hasta: '' };
                    const d1 = new Date(`${desde}T12:00:00`);
                    const d2 = new Date(`${hasta}T12:00:00`);
                    if (Number.isNaN(d1.getTime()) || Number.isNaN(d2.getTime()) || d2 < d1) {
                        return { desde: '', hasta: '' };
                    }
                    const dias = Math.round((d2 - d1) / 86400000) + 1;
                    const hastaB = new Date(d1);
                    hastaB.setDate(hastaB.getDate() - 1);
                    const desdeB = new Date(hastaB);
                    desdeB.setDate(desdeB.getDate() - (dias - 1));
                    return {
                        desde: this.fechaIsoLocal(desdeB),
                        hasta: this.fechaIsoLocal(hastaB)
                    };
                },

                onToggleComparacion(filtros, reloadFn) {
                    if (filtros.comparar && (!filtros.fechaDesdeB || !filtros.fechaHastaB)) {
                        const ant = this.periodoAnteriorIgualDuracion(filtros.fechaDesde, filtros.fechaHasta);
                        filtros.fechaDesdeB = ant.desde;
                        filtros.fechaHastaB = ant.hasta;
                    }
                    if (typeof reloadFn === 'function') reloadFn();
                },

                usarPeriodoAnteriorComparacion(filtros, reloadFn) {
                    const ant = this.periodoAnteriorIgualDuracion(filtros.fechaDesde, filtros.fechaHasta);
                    filtros.fechaDesdeB = ant.desde;
                    filtros.fechaHastaB = ant.hasta;
                    filtros.comparar = true;
                    if (typeof reloadFn === 'function') reloadFn();
                },

                fusionarSeriesCategoricas(datosA, datosB) {
                    const mapA = {};
                    const mapB = {};
                    (datosA || []).forEach((d) => {
                        if (d && d.label) mapA[d.label] = d;
                    });
                    (datosB || []).forEach((d) => {
                        if (d && d.label) mapB[d.label] = d;
                    });
                    const labels = [...new Set([...Object.keys(mapA), ...Object.keys(mapB)])];
                    labels.sort((a, b) => {
                        const maxB = Math.max(Number(mapA[b]?.valor) || 0, Number(mapB[b]?.valor) || 0);
                        const maxA = Math.max(Number(mapA[a]?.valor) || 0, Number(mapB[a]?.valor) || 0);
                        if (maxB !== maxA) return maxB - maxA;
                        return String(a).localeCompare(String(b), 'es');
                    });
                    return {
                        labels,
                        valoresA: labels.map((l) => Number(mapA[l]?.valor) || 0),
                        valoresB: labels.map((l) => Number(mapB[l]?.valor) || 0),
                        metaA: labels.map((l) => mapA[l] || { label: l, valor: 0 }),
                        metaB: labels.map((l) => mapB[l] || { label: l, valor: 0 }),
                    };
                },

                totalesConsumoPorMaterial(data) {
                    const out = {};
                    (data?.series || []).forEach((s) => {
                        const nombre = s?.name || '';
                        if (!nombre) return;
                        out[nombre] = (s.data || []).reduce((acc, v) => acc + (Number(v) || 0), 0);
                    });
                    return out;
                },

                opcionesGraficoComparacionBarras({
                    labels = [],
                    valoresA = [],
                    valoresB = [],
                    nameA = 'Período A',
                    nameB = 'Período B',
                    unidad = '',
                    usarIconosMotivo = false,
                    horizontal = true,
                    metaA = [],
                    metaB = [],
                } = {}) {
                    const categorias = usarIconosMotivo
                        ? labels.map((l) => this.iconoMotivoReclamo(l))
                        : labels;
                    const emojiFont = 'Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif';
                    const altura = horizontal
                        ? Math.max(360, labels.length * 52 + 40)
                        : Math.min(Math.max(380, 280 + labels.length * 12), 560);
                    const fmtVal = (val) => {
                        const n = Number(val) || 0;
                        if (unidad === 'min') return `${Math.round(n)} min`;
                        return String(Math.round(n));
                    };
                    const escapeHtml = (s) => String(s || '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');

                    return {
                        series: [
                            { name: nameA, data: valoresA },
                            { name: nameB, data: valoresB },
                        ],
                        chart: {
                            type: 'bar',
                            width: '100%',
                            height: altura,
                            toolbar: { show: false, tools: { download: true } },
                            fontFamily: 'Open Sans, Segoe UI, Tahoma, sans-serif',
                        },
                        plotOptions: {
                            bar: {
                                horizontal,
                                columnWidth: '55%',
                                barHeight: '68%',
                                borderRadius: 3,
                                dataLabels: { position: horizontal ? 'center' : 'top' },
                            },
                        },
                        colors: ['#3A3972', '#5B9BD5'],
                        dataLabels: {
                            enabled: true,
                            formatter(val) { return fmtVal(val); },
                            style: {
                                fontSize: '11px',
                                fontWeight: 'bold',
                                colors: horizontal ? ['#fff', '#fff'] : ['#3A3972', '#2f6f9f'],
                            },
                        },
                        xaxis: {
                            categories: categorias,
                            labels: {
                                style: {
                                    fontSize: usarIconosMotivo ? '18px' : '11px',
                                    fontFamily: usarIconosMotivo ? emojiFont : undefined,
                                },
                                rotate: horizontal ? 0 : -35,
                                trim: !usarIconosMotivo,
                                maxHeight: horizontal ? 48 : 90,
                            },
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    fontSize: usarIconosMotivo && horizontal ? '18px' : '11px',
                                    fontFamily: usarIconosMotivo ? emojiFont : undefined,
                                },
                                maxWidth: usarIconosMotivo ? 48 : 140,
                                formatter: horizontal
                                    ? undefined
                                    : (val) => Math.round(Number(val) || 0),
                            },
                        },
                        legend: {
                            show: true,
                            position: 'top',
                            fontSize: '12px',
                        },
                        grid: { padding: { left: 8, right: 16, top: 8, bottom: 8 } },
                        tooltip: {
                            shared: true,
                            intersect: false,
                            custom: ({ dataPointIndex }) => {
                                const label = labels[dataPointIndex] || '';
                                const icono = usarIconosMotivo ? (this.iconoMotivoReclamo(label) + ' ') : '';
                                const a = Number(valoresA[dataPointIndex]) || 0;
                                const b = Number(valoresB[dataPointIndex]) || 0;
                                const delta = a - b;
                                const deltaPct = b !== 0 ? ((delta / b) * 100) : (a !== 0 ? 100 : 0);
                                const signo = delta > 0 ? '+' : '';
                                const regsA = metaA[dataPointIndex]?.cantidad_registros;
                                const regsB = metaB[dataPointIndex]?.cantidad_registros;
                                const extraRegs = (regsA != null || regsB != null)
                                    ? `<div class="analisis-motivo-tooltip__valor" style="opacity:.85">${regsA ?? 0} reg. · ${regsB ?? 0} reg.</div>`
                                    : '';
                                return `<div class="analisis-motivo-tooltip">`
                                    + `<div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__nombre">${icono}${escapeHtml(label)}</span></div>`
                                    + `<div class="analisis-motivo-tooltip__valor"><strong>${escapeHtml(nameA)}</strong>: ${fmtVal(a)}</div>`
                                    + `<div class="analisis-motivo-tooltip__valor"><strong>${escapeHtml(nameB)}</strong>: ${fmtVal(b)}</div>`
                                    + `<div class="analisis-motivo-tooltip__valor">Δ ${signo}${fmtVal(delta)} (${signo}${deltaPct.toFixed(1)}%)</div>`
                                    + extraRegs
                                    + `</div>`;
                            },
                        },
                    };
                },

                /**
                 * Carga los KPIs de resumen
                 */
                async cargarKpiResumen() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosPeriodo.fechaDesde) params.append('fecha_desde', this.filtrosPeriodo.fechaDesde);
                        if (this.filtrosPeriodo.fechaHasta) params.append('fecha_hasta', this.filtrosPeriodo.fechaHasta);

                        const response = await axios.get(`${BASE_URL}api/analisis/kpi-resumen?${params.toString()}`);
                        const data = response.data || {};
                        if (!this.debeRedibujarAnalisis('kpi', data, this.kpiResumen && this.kpiResumen.total !== undefined)) {
                            return;
                        }
                        this.kpiResumen = data;
                    } catch (error) {
                        console.error('Error al cargar KPIs de resumen:', error);
                    }
                },

                /**
                 * Carga el preview del gráfico de estado
                 */
                async cargarPreviewEstado() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosEstado.fechaDesde) params.append('fecha_desde', this.filtrosEstado.fechaDesde);
                        if (this.filtrosEstado.fechaHasta) params.append('fecha_hasta', this.filtrosEstado.fechaHasta);
                        if (this.filtrosEstado.prioridad) params.append('prioridad', this.filtrosEstado.prioridad);

                        const response = await axios.get(`${BASE_URL}api/analisis/reclamos-por-estado?${params.toString()}`);
                        const data = response.data || {};
                        if (!this.debeRedibujarAnalisis('estado', data, this.previewChartEstado)) {
                            return;
                        }
                        this.datosEstado = data;

                        this.$nextTick(() => {
                            this.crearPreviewGraficoTorta('previewChartEstado', this.datosEstado.datos);
                        });
                    } catch (error) {
                        console.error('Error al cargar datos de estado:', error);
                    }
                },

                /**
                 * Carga el preview del gráfico de motivo
                 */
                async cargarPreviewMotivo() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosMotivo.fechaDesde) params.append('fecha_desde', this.filtrosMotivo.fechaDesde);
                        if (this.filtrosMotivo.fechaHasta) params.append('fecha_hasta', this.filtrosMotivo.fechaHasta);
                        if (this.filtrosMotivo.estado) params.append('estado', this.filtrosMotivo.estado);

                        const response = await axios.get(`${BASE_URL}api/analisis/reclamos-por-motivo?${params.toString()}`);
                        const data = response.data || {};
                        if (!this.debeRedibujarAnalisis('motivo', data, this.previewChartMotivo)) {
                            return;
                        }
                        this.datosMotivo = data;

                        this.motivosDisponibles = (this.datosMotivo.datos || []).map(d => d.label);
                        await this.$nextTick();
                        requestAnimationFrame(() => {
                            this.crearPreviewGraficoBarras('previewChartMotivo', this.datosMotivo.datos || []);
                        });
                    } catch (error) {
                        console.error('Error al cargar datos de motivo:', error);
                    }
                },

                /**
                 * Carga el gráfico completo de estado
                 */
                async cargarGraficoEstado() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosEstado.fechaDesde) params.append('fecha_desde', this.filtrosEstado.fechaDesde);
                        if (this.filtrosEstado.fechaHasta) params.append('fecha_hasta', this.filtrosEstado.fechaHasta);
                        if (this.filtrosEstado.prioridad) params.append('prioridad', this.filtrosEstado.prioridad);

                        const response = await axios.get(`${BASE_URL}api/analisis/reclamos-por-estado?${params.toString()}`);
                        const data = response.data || {};
                        if (!this.debeRedibujarAnalisis('estadoModal', data, this.chartEstado)) {
                            return;
                        }
                        this.datosEstado = data;

                        this.$nextTick(() => {
                            this.crearGraficoTorta('chartEstado', this.datosEstado.datos);
                        });
                    } catch (error) {
                        console.error('Error al cargar gráfico de estado:', error);
                    }
                },

                /**
                 * Carga el gráfico completo de motivo
                 */
                async cargarGraficoMotivo() {
                    try {
                        const paramsA = new URLSearchParams();
                        if (this.filtrosMotivo.fechaDesde) paramsA.append('fecha_desde', this.filtrosMotivo.fechaDesde);
                        if (this.filtrosMotivo.fechaHasta) paramsA.append('fecha_hasta', this.filtrosMotivo.fechaHasta);
                        if (this.filtrosMotivo.estado) paramsA.append('estado', this.filtrosMotivo.estado);

                        const responseA = await axios.get(`${BASE_URL}api/analisis/reclamos-por-motivo?${paramsA.toString()}`);
                        const dataA = responseA.data || {};

                        let data = { ...dataA, comparacion: null };
                        if (this.periodoComparacionActivo(this.filtrosMotivo)) {
                            const paramsB = new URLSearchParams();
                            paramsB.append('fecha_desde', this.filtrosMotivo.fechaDesdeB);
                            paramsB.append('fecha_hasta', this.filtrosMotivo.fechaHastaB);
                            if (this.filtrosMotivo.estado) paramsB.append('estado', this.filtrosMotivo.estado);
                            const responseB = await axios.get(`${BASE_URL}api/analisis/reclamos-por-motivo?${paramsB.toString()}`);
                            const dataB = responseB.data || {};
                            data = {
                                ...dataA,
                                comparacion: {
                                    activa: true,
                                    nameA: this.etiquetaRangoFechas(this.filtrosMotivo.fechaDesde, this.filtrosMotivo.fechaHasta),
                                    nameB: this.etiquetaRangoFechas(this.filtrosMotivo.fechaDesdeB, this.filtrosMotivo.fechaHastaB),
                                    datosA: dataA.datos || [],
                                    datosB: dataB.datos || [],
                                },
                            };
                        }

                        if (!this.debeRedibujarAnalisis('motivoModal', data, this.chartMotivo || this.chartMotivoB)) {
                            return;
                        }
                        this.datosMotivo = data;

                        this.$nextTick(() => {
                            this.crearGraficoBarras('chartMotivo', this.datosMotivo);
                        });
                    } catch (error) {
                        console.error('Error al cargar gráfico de motivo:', error);
                    }
                },

                /**
                 * Crea un preview de gráfico de torta (donut)
                 */
                crearPreviewGraficoTorta(elementId, datos) {
                    const elemento = document.getElementById(elementId);
                    if (!elemento) return;

                    // Destruir gráfico anterior si existe
                    if (this.previewChartEstado && elementId === 'previewChartEstado') {
                        this.previewChartEstado.destroy();
                    }

                    // Filtrar datos con valor mayor a 0
                    const datosFiltrados = datos.filter(d => d.valor > 0);
                    const contenedor = elemento.parentElement;
                    const altoDisponible = Math.max(
                        contenedor?.clientHeight || 0,
                        elemento.clientHeight || 0,
                        320
                    );

                    const options = {
                        series: datosFiltrados.map(d => d.valor),
                        chart: {
                            type: 'donut',
                            width: '100%',
                            height: altoDisponible,
                            toolbar: {
                                show: false
                            }
                        },
                        labels: datosFiltrados.map(d => d.label),
                        colors: datosFiltrados.map(d => d.color),
                        legend: {
                            show: false
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: function(_val, opts) {
                                return opts.w.globals.series[opts.seriesIndex];
                            },
                            style: {
                                fontSize: '12px',
                                fontWeight: 'bold',
                                colors: datosFiltrados.map(d =>
                                    String(d.color || '').toUpperCase() === '#FFD700' ? '#212529' : '#fff'
                                )
                            },
                            dropShadow: { enabled: false }
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '48%'
                                },
                                dataLabels: {
                                    minAngleToShowLabel: 8
                                }
                            }
                        },
                        tooltip: {
                            enabled: true,
                            fillSeriesColor: false,
                            marker: { show: false },
                            custom({ series, seriesIndex, w }) {
                                const label = (w.globals.labels && w.globals.labels[seriesIndex]) || '';
                                const val = series[seriesIndex] || 0;
                                const total = series.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((val / total) * 100).toFixed(1) : '0.0';
                                const labelSafe = String(label)
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/"/g, '&quot;');
                                return `<div class="analisis-motivo-tooltip"><div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__nombre">${labelSafe}</span></div><div class="analisis-motivo-tooltip__valor">${val} reclamos (${percentage}%)</div></div>`;
                            }
                        }
                    };

                    const chart = new ApexCharts(elemento, options);
                    chart.render().then(() => {
                        // Si la card creció por la de motivos, reajustar el donut
                        requestAnimationFrame(() => {
                            const h = Math.max(contenedor?.clientHeight || 0, 320);
                            if (h !== altoDisponible) {
                                chart.updateOptions({ chart: { height: h } }, false, true);
                            }
                        });
                    });

                    if (elementId === 'previewChartEstado') {
                        this.previewChartEstado = chart;
                    }
                },

                
                normalizarMotivoReclamo(motivo) {
                    return (motivo || '')
                        .toString()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .toLowerCase();
                },

                iconoMotivoReclamo(motivo) {
                    const motivoNormalizado = this.normalizarMotivoReclamo(motivo);
                    if (motivoNormalizado.includes('pedido de alumbrado')) return '🌃';
                    if (motivoNormalizado.includes('semaforo')) return '🚦';
                    if (motivoNormalizado.includes('rama')) return '🌳';
                    if (motivoNormalizado.includes('cable')) return '🔌';
                    if (motivoNormalizado.includes('poste')) return '⚠️';
                    if (motivoNormalizado.includes('columna')) return '⚠️';
                    if (motivoNormalizado.includes('agotada')) return '💡';
                    if (motivoNormalizado.includes('quemada') || motivoNormalizado.includes('rota')) return '💡';
                    return '💡';
                },

                /**
                 * Fallback si el API aún manda claves técnicas; los labels nuevos ya vienen con fechas.
                 */
                formatearLabelPeriodo(label) {
                    const raw = String(label || '');
                    if (raw.includes('–') || raw.includes('/')) return raw;
                    const semana = raw.match(/^(\d{4})-W(\d{1,2})$/i);
                    if (semana) {
                        // Fallback: lunes aproximado ISO de esa semana
                        const d = new Date(Date.UTC(Number(semana[1]), 0, 1 + (Number(semana[2]) - 1) * 7));
                        const day = d.getUTCDay() || 7;
                        if (day !== 1) d.setUTCDate(d.getUTCDate() + (1 - day));
                        const fin = new Date(d);
                        fin.setUTCDate(fin.getUTCDate() + 6);
                        const fmt = (x) => `${String(x.getUTCDate()).padStart(2, '0')}/${String(x.getUTCMonth() + 1).padStart(2, '0')}`;
                        return `${fmt(d)} – ${fmt(fin)}/${d.getUTCFullYear()}`;
                    }
                    const mes = raw.match(/^(\d{4})-(\d{2})$/);
                    if (mes) {
                        const nombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                        const idx = Number(mes[2]) - 1;
                        return `${nombres[idx] || mes[2]} ${mes[1]}`;
                    }
                    const dia = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                    if (dia) return `${dia[3]}/${dia[2]}/${dia[1]}`;
                    return raw;
                },

                formatearLabelsPeriodo(labels) {
                    return (labels || []).map(l => this.formatearLabelPeriodo(l));
                },

                opcionesGraficoMotivoBarras(datos, { preview = false } = {}) {
                    const lista = Array.isArray(datos) ? datos : [];
                    const labelsCompletos = lista.map(d => d.label || '');
                    const iconos = labelsCompletos.map(label => this.iconoMotivoReclamo(label));
                    const valores = lista.map(d => Number(d.valor) || 0);
                    // Preview: una fila por motivo + espacio fijo para la escala inferior
                    const fila = preview ? 36 : 48;
                    const margenEje = preview ? 52 : 24;
                    const altura = Math.max(
                        preview ? 280 : 360,
                        lista.length * fila + margenEje
                    );
                    const emojiFont = 'Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif';

                    const options = {
                        series: [{
                            name: 'Cantidad de Reclamos',
                            data: valores
                        }],
                        chart: {
                            type: 'bar',
                            width: '100%',
                            height: altura,
                            parentHeightOffset: 0,
                            toolbar: { show: false },
                            animations: { enabled: !preview },
                            fontFamily: 'Open Sans, Segoe UI, Tahoma, sans-serif'
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                distributed: true,
                                barHeight: preview ? '58%' : '70%',
                                borderRadius: 3,
                                dataLabels: {
                                    position: 'center'
                                }
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            formatter(val) {
                                return val;
                            },
                            offsetX: 0,
                            style: {
                                fontSize: preview ? '10px' : '12px',
                                fontWeight: 'bold',
                                colors: valores.map((v) => (v > 0 ? '#fff' : '#6c757d'))
                            },
                            background: {
                                enabled: false
                            }
                        },
                        xaxis: {
                            categories: iconos,
                            labels: {
                                show: true,
                                style: { fontSize: preview ? '9px' : '11px', colors: '#6c757d' },
                                maxHeight: preview ? 28 : 40,
                                hideOverlappingLabels: true,
                                trim: true
                            },
                            axisBorder: { show: true },
                            axisTicks: { show: true },
                            tickAmount: preview ? 4 : undefined
                        },
                        yaxis: {
                            labels: {
                                show: true,
                                style: { fontSize: preview ? '16px' : '20px', fontFamily: emojiFont },
                                maxWidth: preview ? 40 : 48
                            }
                        },
                        colors: lista.map(d => d.color || '#3A3972'),
                        legend: { show: false },
                        grid: {
                            padding: {
                                left: preview ? 4 : 8,
                                right: preview ? 12 : 16,
                                bottom: preview ? 12 : 10,
                                top: preview ? 4 : 0
                            }
                        },
                        tooltip: {
                            custom({ series, seriesIndex, dataPointIndex }) {
                                const motivo = labelsCompletos[dataPointIndex] || '';
                                const icono = iconos[dataPointIndex] || '';
                                const serie = series[seriesIndex] || [];
                                const val = serie[dataPointIndex] || 0;
                                const total = serie.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((val / total) * 100).toFixed(1) : '0.0';
                                const motivoSafe = String(motivo)
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/"/g, '&quot;');
                                return `<div class="analisis-motivo-tooltip"><div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__icono">${icono}</span><span class="analisis-motivo-tooltip__nombre">${motivoSafe}</span></div><div class="analisis-motivo-tooltip__valor">${val} reclamos (${percentage}%)</div></div>`;
                            }
                        }
                    };

                    if (!preview) {
                        options.chart.toolbar.tools = { download: true };
                    }
                    return options;
                },

                opcionesGraficoTiempoReparacionBarras(datos, { preview = false } = {}) {
                    const lista = Array.isArray(datos) ? [...datos] : [];
                    lista.sort((a, b) => (Number(b.valor) || 0) - (Number(a.valor) || 0));
                    const labelsCompletos = lista.map(d => d.label || '');
                    const iconos = labelsCompletos.map(label => this.iconoMotivoReclamo(label));
                    const valores = lista.map(d => Number(d.valor) || 0);
                    const emojiFont = 'Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif';
                    // Preview: altura fija 220 (igual que antigüedad). Evita canvas en blanco.
                    const altura = preview ? 220 : Math.min(Math.max(360, lista.length * 46), 560);

                    const options = {
                        series: [{
                            name: 'Tiempo promedio (min)',
                            data: valores
                        }],
                        chart: {
                            type: 'bar',
                            width: '100%',
                            height: altura,
                            parentHeightOffset: 0,
                            toolbar: { show: false },
                            animations: { enabled: !preview },
                            fontFamily: 'Open Sans, Segoe UI, Tahoma, sans-serif'
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                distributed: true,
                                barHeight: preview ? '55%' : '70%',
                                borderRadius: 3
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            formatter(val) {
                                return (Number(val) || 0).toFixed(0) + ' min';
                            },
                            style: {
                                fontSize: preview ? '9px' : '12px',
                                fontWeight: 'bold',
                                colors: ['#fff']
                            }
                        },
                        xaxis: {
                            categories: iconos,
                            title: preview ? undefined : {
                                text: 'Minutos de reparación en obra',
                                style: { fontSize: '13px' }
                            },
                            labels: {
                                show: !preview,
                                style: { fontSize: '11px' },
                                formatter(val) { return Math.round(Number(val) || 0) + ' min'; }
                            }
                        },
                        yaxis: {
                            labels: {
                                show: true,
                                style: { fontSize: preview ? '14px' : '20px', fontFamily: emojiFont },
                                maxWidth: preview ? 36 : 48
                            }
                        },
                        colors: lista.map(d => d.color || '#3A3972'),
                        legend: { show: false },
                        grid: { padding: { left: preview ? 4 : 8, right: preview ? 8 : 12, top: 0, bottom: 0 } },
                        tooltip: {
                            custom({ series, seriesIndex, dataPointIndex }) {
                                const motivo = labelsCompletos[dataPointIndex] || '';
                                const icono = iconos[dataPointIndex] || '';
                                const serie = series[seriesIndex] || [];
                                const val = serie[dataPointIndex] || 0;
                                const regs = lista[dataPointIndex]?.cantidad_registros || 0;
                                const motivoSafe = String(motivo)
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/"/g, '&quot;');
                                const regsTxt = regs > 0 ? `${regs} registro${regs === 1 ? '' : 's'}` : 'Sin registros en el período';
                                return `<div class="analisis-motivo-tooltip"><div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__icono">${icono}</span><span class="analisis-motivo-tooltip__nombre">${motivoSafe}</span></div><div class="analisis-motivo-tooltip__valor">${Number(val).toFixed(0)} min promedio · ${regsTxt}</div></div>`;
                            }
                        }
                    };
                    if (!preview) {
                        options.chart.toolbar.tools = { download: true };
                    }
                    return options;
                },


                crearPreviewGraficoBarras(elementId, datos) {
                    const elemento = document.getElementById(elementId);
                    if (!elemento) return;

                    try {
                        if (this.previewChartMotivo && elementId === 'previewChartMotivo') {
                            try { this.previewChartMotivo.destroy(); } catch (e) {}
                            this.previewChartMotivo = null;
                        }
                        elemento.innerHTML = '';
                        if (!datos || !datos.length) {
                            elemento.innerHTML = '<div class="analisis-preview-empty">Sin datos de motivos</div>';
                            return;
                        }
                        const options = this.opcionesGraficoMotivoBarras(datos, { preview: true });
                        // Asegura que el contenedor no recorte el eje inferior
                        if (options.chart && options.chart.height) {
                            elemento.style.height = `${options.chart.height}px`;
                            elemento.style.minHeight = `${options.chart.height}px`;
                        }
                        const chart = new ApexCharts(elemento, options);
                        chart.render().then(() => {
                            // Al crecer esta card, reescalar el donut de estado en la misma fila
                            if (this.previewChartEstado) {
                                const elEstado = document.getElementById('previewChartEstado');
                                const contEstado = elEstado?.parentElement;
                                const h = Math.max(contEstado?.clientHeight || 0, 320);
                                this.previewChartEstado.updateOptions({ chart: { height: h } }, false, true);
                            }
                        });
                        if (elementId === 'previewChartMotivo') this.previewChartMotivo = chart;
                    } catch (error) {
                        console.error('Error al crear preview de motivo:', error);
                        elemento.innerHTML = '<div class="analisis-preview-empty">No se pudo cargar el gráfico</div>';
                    }
                },

                /**
                 * Crea el gráfico completo de torta/pie
                 */
                crearGraficoTorta(elementId, datos) {
                    const elemento = document.getElementById(elementId);
                    if (!elemento) return;

                    // Destruir gráfico anterior si existe
                    if (this.chartEstado) {
                        this.chartEstado.destroy();
                    }

                    // Filtrar datos con valor mayor a 0
                    const datosFiltrados = datos.filter(d => d.valor > 0);

                    const options = {
                        series: datosFiltrados.map(d => d.valor),
                        chart: {
                            type: 'pie',
                            width: '100%',
                            height: 500,
                            toolbar: {
                                show: false
                            }
                        },
                        labels: datosFiltrados.map(d => d.label),
                        colors: datosFiltrados.map(d => d.color),
                        legend: {
                            position: 'right',
                            fontSize: '14px',
                            labels: {
                                colors: '#333'
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: function(_val, opts) {
                                return opts.w.globals.series[opts.seriesIndex];
                            },
                            style: {
                                fontSize: '13px',
                                fontWeight: 'bold',
                                // Texto oscuro sobre amarillo (En ejecución), blanco en el resto
                                colors: datosFiltrados.map(d =>
                                    String(d.color || '').toUpperCase() === '#FFD700' ? '#212529' : '#fff'
                                )
                            },
                            dropShadow: { enabled: false }
                        },
                        plotOptions: {
                            pie: {
                                dataLabels: {
                                    minAngleToShowLabel: 6
                                }
                            }
                        },
                        tooltip: {
                            enabled: true,
                            fillSeriesColor: false,
                            marker: { show: false },
                            custom({ series, seriesIndex, w }) {
                                const label = (w.globals.labels && w.globals.labels[seriesIndex]) || '';
                                const val = series[seriesIndex] || 0;
                                const total = series.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((val / total) * 100).toFixed(1) : '0.0';
                                const labelSafe = String(label)
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/"/g, '&quot;');
                                return `<div class="analisis-motivo-tooltip"><div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__nombre">${labelSafe}</span></div><div class="analisis-motivo-tooltip__valor">${val} reclamos (${percentage}%)</div></div>`;
                            }
                        },
                        responsive: [{
                            breakpoint: 768,
                            options: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }]
                    };

                    this.chartEstado = new ApexCharts(elemento, options);
                    this.chartEstado.render();
                },

                /**
                 * Crea el gráfico completo de barras (motivo; en comparación: un gráfico por período)
                 */
                destruirChartMotivo() {
                    if (this.chartMotivo) {
                        try { this.chartMotivo.destroy(); } catch (e) {}
                        this.chartMotivo = null;
                    }
                    if (this.chartMotivoB) {
                        try { this.chartMotivoB.destroy(); } catch (e) {}
                        this.chartMotivoB = null;
                    }
                },

                crearGraficoBarras(elementId, payload) {
                    this.destruirChartMotivo();
                    const elementoA = document.getElementById(elementId || 'chartMotivo');
                    if (!elementoA) return;
                    elementoA.innerHTML = '';

                    const comparacion = payload && payload.comparacion && payload.comparacion.activa
                        ? payload.comparacion
                        : null;
                    const datosA = comparacion
                        ? (comparacion.datosA || [])
                        : (Array.isArray(payload) ? payload : (payload?.datos || []));

                    const renderA = () => {
                        if (!datosA.length) {
                            elementoA.innerHTML = '<div class="text-center p-4 text-muted">Sin datos en el período A</div>';
                            return;
                        }
                        this.chartMotivo = new ApexCharts(
                            elementoA,
                            this.opcionesGraficoMotivoBarras(datosA, { preview: false })
                        );
                        this.chartMotivo.render();
                    };

                    if (!comparacion) {
                        renderA();
                        return;
                    }

                    this.$nextTick(() => {
                        renderA();
                        const elementoB = document.getElementById('chartMotivoB');
                        if (!elementoB) return;
                        elementoB.innerHTML = '';
                        const datosB = comparacion.datosB || [];
                        if (!datosB.length) {
                            elementoB.innerHTML = '<div class="text-center p-4 text-muted">Sin datos en el período B</div>';
                            return;
                        }
                        this.chartMotivoB = new ApexCharts(
                            elementoB,
                            this.opcionesGraficoMotivoBarras(datosB, { preview: false })
                        );
                        this.chartMotivoB.render();
                    });
                },

                /**
                 * Carga el preview del gráfico de evolución temporal
                 */
                async cargarPreviewEvolucion() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosEvolucion.fechaDesde) params.append('fecha_desde', this.filtrosEvolucion.fechaDesde);
                        if (this.filtrosEvolucion.fechaHasta) params.append('fecha_hasta', this.filtrosEvolucion.fechaHasta);
                        if (this.filtrosEvolucion.granularidad) params.append('granularidad', this.filtrosEvolucion.granularidad);

                        const response = await axios.get(`${BASE_URL}api/analisis/evolucion-temporal?${params.toString()}`);
                        const data = response.data || {};
                        if (!this.debeRedibujarAnalisis('evolucion', data, this.previewChartEvolucion)) {
                            return;
                        }
                        this.datosEvolucion = data;

                        this.$nextTick(() => {
                            this.crearPreviewGraficoLineas('previewChartEvolucion', data);
                        });
                    } catch (error) {
                        console.error('Error al cargar datos de evolución:', error);
                    }
                },

                /**
                 * Carga el gráfico completo de evolución temporal
                 */
                async cargarGraficoEvolucion() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosEvolucion.fechaDesde) params.append('fecha_desde', this.filtrosEvolucion.fechaDesde);
                        if (this.filtrosEvolucion.fechaHasta) params.append('fecha_hasta', this.filtrosEvolucion.fechaHasta);
                        if (this.filtrosEvolucion.granularidad) params.append('granularidad', this.filtrosEvolucion.granularidad);

                        const response = await axios.get(`${BASE_URL}api/analisis/evolucion-temporal?${params.toString()}`);
                        const data = response.data || {};
                        if (!this.debeRedibujarAnalisis('evolucionModal', data, this.chartEvolucion)) {
                            return;
                        }
                        this.datosEvolucion = data;

                        this.$nextTick(() => {
                            this.crearGraficoLineas('chartEvolucion', data);
                        });
                    } catch (error) {
                        console.error('Error al cargar gráfico de evolución:', error);
                    }
                },

                /**
                 * Crea un preview de gráfico de líneas
                 */
                crearPreviewGraficoLineas(elementId, datos) {
                    const elemento = document.getElementById(elementId);
                    if (!elemento) return;

                    if (this.previewChartEvolucion && elementId === 'previewChartEvolucion') {
                        this.previewChartEvolucion.destroy();
                    }

                    // Formatear series para ApexCharts
                    const seriesFormateadas = datos.series ? datos.series.map(s => ({
                        name: s.name,
                        data: s.data || []
                    })) : [];
                    
                    const colores = datos.series ? datos.series.map(s => s.color) : [];
                    const labelsFormateados = this.formatearLabelsPeriodo(datos.labels);

                    const options = {
                        series: seriesFormateadas,
                        chart: {
                            type: 'line',
                            width: '100%',
                            height: '100%',
                            toolbar: {
                                show: false
                            },
                            zoom: {
                                enabled: false
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 2
                        },
                        xaxis: {
                            categories: labelsFormateados,
                            labels: {
                                rotate: -45,
                                style: {
                                    fontSize: '10px'
                                }
                            },
                            tooltip: {
                                enabled: false
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    fontSize: '10px'
                                }
                            }
                        },
                        legend: {
                            show: true,
                            position: 'top',
                            fontSize: '11px'
                        },
                        tooltip: {
                            enabled: true,
                            shared: true,
                            custom: ({ dataPointIndex }) => this.htmlTooltipEvolucionIngresosCierres(
                                labelsFormateados,
                                seriesFormateadas,
                                colores,
                                dataPointIndex
                            )
                        },
                        colors: colores
                    };

                    const chart = new ApexCharts(elemento, options);
                    chart.render();

                    if (elementId === 'previewChartEvolucion') {
                        this.previewChartEvolucion = chart;
                    }
                },

                /**
                 * Crea el gráfico completo de líneas
                 */
                crearGraficoLineas(elementId, datos) {
                    const elemento = document.getElementById(elementId);
                    if (!elemento) return;

                    if (this.chartEvolucion) {
                        this.chartEvolucion.destroy();
                    }

                    // Formatear series para ApexCharts
                    const seriesFormateadas = datos.series ? datos.series.map(s => ({
                        name: s.name,
                        data: s.data || []
                    })) : [];
                    
                    const colores = datos.series ? datos.series.map(s => s.color) : [];
                    const labelsFormateados = this.formatearLabelsPeriodo(datos.labels);

                    const options = {
                        series: seriesFormateadas,
                        chart: {
                            type: 'line',
                            width: '100%',
                            height: 500,
                            toolbar: {
                                show: false
                            },
                            zoom: {
                                enabled: false
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            curve: 'smooth',
                            width: 3
                        },
                        xaxis: {
                            categories: labelsFormateados,
                            labels: {
                                rotate: -45,
                                style: {
                                    fontSize: '12px'
                                }
                            },
                            tooltip: {
                                enabled: false
                            }
                        },
                        yaxis: {
                            title: {
                                text: 'Cantidad de Reclamos',
                                style: {
                                    fontSize: '12px'
                                }
                            },
                            labels: {
                                style: {
                                    fontSize: '12px'
                                }
                            }
                        },
                        legend: {
                            show: true,
                            position: 'top',
                            fontSize: '14px'
                        },
                        tooltip: {
                            enabled: true,
                            shared: true,
                            custom: ({ dataPointIndex }) => this.htmlTooltipEvolucionIngresosCierres(
                                labelsFormateados,
                                seriesFormateadas,
                                colores,
                                dataPointIndex
                            )
                        },
                        colors: colores
                    };

                    this.chartEvolucion = new ApexCharts(elemento, options);
                    this.chartEvolucion.render();
                },

                htmlTooltipEvolucionIngresosCierres(labels, series, colores, dataPointIndex) {
                    const escapeHtml = (texto) => String(texto ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;');
                    const periodo = escapeHtml((labels && labels[dataPointIndex]) || '');
                    let filas = '';
                    (series || []).forEach((s, idx) => {
                        const val = (s.data || [])[dataPointIndex];
                        if (val === null || val === undefined) return;
                        const nombre = escapeHtml(s.name || '');
                        const color = (colores && colores[idx]) || '#6c757d';
                        filas += `<div class="analisis-motivo-tooltip__serie">`
                            + `<span class="analisis-motivo-tooltip__punto" style="background:${color}"></span>`
                            + `<span><strong>${nombre}</strong>: ${Number(val)}</span></div>`;
                    });
                    if (!filas) {
                        filas = '<div>Sin datos</div>';
                    }
                    return `<div class="analisis-motivo-tooltip">`
                        + `<div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__nombre">${periodo}</span></div>`
                        + `<div class="analisis-motivo-tooltip__valor">${filas}</div></div>`;
                },

                /**
                 * Abre el modal del gráfico seleccionado
                 */
                abrirModalGrafico(tipo) {
                    this.modalAnalisisAbierto = tipo;
                    if (tipo === 'estado') {
                        const modal = new bootstrap.Modal(document.getElementById('modalGraficoEstado'));
                        modal.show();
                        // Esperar a que el modal esté completamente visible
                        setTimeout(() => {
                            this.$nextTick(() => {
                                this.cargarGraficoEstado();
                            });
                        }, 300);
                    } else if (tipo === 'motivo') {
                        const modal = new bootstrap.Modal(document.getElementById('modalGraficoMotivo'));
                        modal.show();
                        setTimeout(() => {
                            this.$nextTick(() => {
                                this.cargarGraficoMotivo();
                            });
                        }, 300);
                    } else if (tipo === 'evolucion') {
                        const modal = new bootstrap.Modal(document.getElementById('modalGraficoEvolucion'));
                        modal.show();
                        setTimeout(() => {
                            this.$nextTick(() => {
                                this.cargarGraficoEvolucion();
                            });
                        }, 300);
                    } else if (tipo === 'tiempoPromedio') {
                        const modalElement = document.getElementById('modalGraficoTiempoPromedio');
                        const modal = new bootstrap.Modal(modalElement);
                        
                        // Usar evento 'shown.bs.modal' para asegurar que el modal esté completamente visible
                        const onShown = () => {
                            modalElement.removeEventListener('shown.bs.modal', onShown);
                            this.$nextTick(() => {
                                // Esperar un poco más para asegurar que el DOM esté completamente renderizado
                                setTimeout(() => {
                                    this.cargarGraficoTiempoPromedio();
                                }, 500);
                            });
                        };
                        modalElement.addEventListener('shown.bs.modal', onShown);
                        modal.show();
                    } else if (tipo === 'evolucionTiempo') {
                        const modal = new bootstrap.Modal(document.getElementById('modalGraficoEvolucionTiempo'));
                        modal.show();
                        setTimeout(() => {
                            this.$nextTick(() => {
                                this.cargarGraficoEvolucionTiempo();
                            });
                        }, 300);
                    } else if (tipo === 'antiguedad') {
                        const modal = new bootstrap.Modal(document.getElementById('modalGraficoAntiguedad'));
                        modal.show();
                        setTimeout(() => this.cargarGraficoAntiguedad(), 300);
                    } else if (tipo === 'mapaCalor') {
                        const modalEl = document.getElementById('modalGraficoMapaCalor');
                        const modal = new bootstrap.Modal(modalEl);
                        const onShown = () => {
                            modalEl.removeEventListener('shown.bs.modal', onShown);
                            this.cargarGraficoMapaCalor();
                        };
                        modalEl.addEventListener('shown.bs.modal', onShown);
                        modal.show();
                    } else if (tipo === 'consumoMateriales') {
                        const modal = new bootstrap.Modal(document.getElementById('modalGraficoConsumoMateriales'));
                        modal.show();
                        setTimeout(() => this.cargarGraficoConsumoMateriales(), 300);
                    }
                },

                /**
                 * Carga los motivos disponibles desde la API
                 */
                async cargarMotivosDisponibles() {
                    try {
                        const response = await axios.get(`${BASE_URL}api/analisis/reclamos-por-motivo?fecha_desde=${this.filtrosMotivo.fechaDesde}&fecha_hasta=${this.filtrosMotivo.fechaHasta}`);
                        if (response.data && response.data.datos) {
                            this.motivosDisponibles = response.data.datos.map(d => d.label);
                        }
                    } catch (error) {
                        console.error('Error al cargar motivos disponibles:', error);
                    }
                },

                /**
                 * Establece filtros rápidos para el gráfico de estado
                 */
                setFiltroRapidoEstado(tipo) {
                    const rango = this.rangoPeriodo(tipo);
                    this.filtrosEstado.fechaDesde = rango.desde;
                    this.filtrosEstado.fechaHasta = rango.hasta;
                    this.cargarGraficoEstado();
                },

                /**
                 * Establece filtros rápidos para el gráfico de motivo
                 */
                setFiltroRapidoMotivo(tipo) {
                    const rango = this.rangoPeriodo(tipo);
                    this.filtrosMotivo.fechaDesde = rango.desde;
                    this.filtrosMotivo.fechaHasta = rango.hasta;
                    this.cargarGraficoMotivo();
                },

                /**
                 * Establece filtros rápidos para el gráfico de evolución
                 */
                setFiltroRapidoEvolucion(tipo) {
                    const rango = this.rangoPeriodo(tipo);

                    switch(tipo) {
                        case '7dias':
                        case '30dias':
                            this.filtrosEvolucion.granularidad = 'diario';
                            break;
                        case '3meses':
                        case '6meses':
                            this.filtrosEvolucion.granularidad = 'semanal';
                            break;
                        case 'año':
                            this.filtrosEvolucion.granularidad = 'mensual';
                            break;
                    }
                    this.filtrosEvolucion.periodo = tipo;
                    this.filtrosEvolucion.fechaDesde = rango.desde;
                    this.filtrosEvolucion.fechaHasta = rango.hasta;
                    this.cargarGraficoEvolucion();
                },

                /**
                 * Exporta el gráfico de estado como imagen
                 */
                exportarGraficoEstado() {
                    if (!this.chartEstado) {
                        alert('El gráfico no está cargado. Por favor, espere un momento y vuelva a intentar.');
                        return;
                    }

                    try {
                        // Usar dataURI para exportar como PNG (método más confiable de ApexCharts)
                        this.chartEstado.dataURI({
                            scale: 2
                        }).then((result) => {
                            const imgURI = result.imgURI || result;
                            const link = document.createElement('a');
                            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
                            link.download = 'grafico-reclamos-por-estado-' + timestamp + '.png';
                            link.href = imgURI;
                            document.body.appendChild(link);
                            link.click();
                            setTimeout(() => {
                                document.body.removeChild(link);
                                // Liberar memoria
                                if (link.href && link.href.startsWith('data:')) {
                                    link.href = '';
                                }
                            }, 100);
                        }).catch((error) => {
                            console.error('Error al exportar gráfico de estado:', error);
                            alert('Error al exportar el gráfico: ' + (error.message || 'Error desconocido'));
                        });
                    } catch (error) {
                        console.error('Error al exportar gráfico de estado:', error);
                        alert('Error al exportar el gráfico. Por favor, intente nuevamente.');
                    }
                },

                /**
                 * Exporta el gráfico de motivo como imagen
                 */
                exportarGraficoMotivo() {
                    if (!this.chartMotivo) {
                        alert('El gráfico no está cargado. Por favor, espere un momento y vuelva a intentar.');
                        return;
                    }

                    try {
                        // Usar dataURI para exportar como PNG (método más confiable de ApexCharts)
                        this.chartMotivo.dataURI({
                            scale: 2
                        }).then((result) => {
                            const imgURI = result.imgURI || result;
                            const link = document.createElement('a');
                            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
                            link.download = 'grafico-reclamos-por-motivo-' + timestamp + '.png';
                            link.href = imgURI;
                            document.body.appendChild(link);
                            link.click();
                            setTimeout(() => {
                                document.body.removeChild(link);
                                // Liberar memoria
                                if (link.href && link.href.startsWith('data:')) {
                                    link.href = '';
                                }
                            }, 100);
                        }).catch((error) => {
                            console.error('Error al exportar gráfico de motivo:', error);
                            alert('Error al exportar el gráfico: ' + (error.message || 'Error desconocido'));
                        });
                    } catch (error) {
                        console.error('Error al exportar gráfico de motivo:', error);
                        alert('Error al exportar el gráfico. Por favor, intente nuevamente.');
                    }
                },

                /**
                 * Exporta el gráfico de evolución como imagen
                 */
                exportarGraficoEvolucion() {
                    if (!this.chartEvolucion) {
                        alert('El gráfico no está cargado. Por favor, espere un momento y vuelva a intentar.');
                        return;
                    }

                    try {
                        // Usar dataURI para exportar como PNG (método más confiable de ApexCharts)
                        this.chartEvolucion.dataURI({
                            scale: 2
                        }).then((result) => {
                            const imgURI = result.imgURI || result;
                            const link = document.createElement('a');
                            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
                            link.download = 'grafico-evolucion-temporal-' + timestamp + '.png';
                            link.href = imgURI;
                            document.body.appendChild(link);
                            link.click();
                            setTimeout(() => {
                                document.body.removeChild(link);
                                // Liberar memoria
                                if (link.href && link.href.startsWith('data:')) {
                                    link.href = '';
                                }
                            }, 100);
                        }).catch((error) => {
                            console.error('Error al exportar gráfico de evolución:', error);
                            alert('Error al exportar el gráfico: ' + (error.message || 'Error desconocido'));
                        });
                    } catch (error) {
                        console.error('Error al exportar gráfico de evolución:', error);
                        alert('Error al exportar el gráfico. Por favor, intente nuevamente.');
                    }
                },

                /**
                 * Carga datos para el preview del gráfico de tiempo promedio por motivo
                 */
                previewChartTiempoPromedioValido() {
                    const el = document.getElementById('previewChartTiempoPromedio');
                    if (!this.previewChartTiempoPromedio || !el) return false;
                    return el.clientHeight > 40 && !!el.querySelector('.apexcharts-canvas');
                },

                previewChartEvolucionTiempoValido() {
                    const el = document.getElementById('previewChartEvolucionTiempo');
                    if (!this.previewChartEvolucionTiempo || !el) return false;
                    return el.clientHeight > 40 && !!el.querySelector('.apexcharts-canvas');
                },

                async cargarPreviewTiempoPromedio() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosTiempo.fechaDesde) params.append('fecha_desde', this.filtrosTiempo.fechaDesde);
                        if (this.filtrosTiempo.fechaHasta) params.append('fecha_hasta', this.filtrosTiempo.fechaHasta);
                        if (this.filtrosTiempo.motivo && this.filtrosTiempo.motivo !== 'Todos') {
                            params.append('motivo', this.filtrosTiempo.motivo);
                        }

                        const response = await axios.get(`${BASE_URL}api/analisis/tiempo-promedio-por-motivo?${params.toString()}`);
                        const data = response.data || {};
                        if (!this.debeRedibujarAnalisis('tiempo', data, this.previewChartTiempoPromedioValido())) {
                            return;
                        }
                        this.datosTiempo = data;
                        await this.$nextTick();
                        requestAnimationFrame(() => this.crearPreviewGraficoTiempoPromedio());
                    } catch (error) {
                        console.error('Error al cargar preview de tiempo promedio:', error);
                    }
                },

                /**
                 * Carga datos para el gráfico completo de tiempo promedio por motivo
                 */
                async cargarGraficoTiempoPromedio() {
                    try {
                        const paramsA = new URLSearchParams();
                        if (this.filtrosTiempo.fechaDesde) paramsA.append('fecha_desde', this.filtrosTiempo.fechaDesde);
                        if (this.filtrosTiempo.fechaHasta) paramsA.append('fecha_hasta', this.filtrosTiempo.fechaHasta);
                        if (this.filtrosTiempo.motivo && this.filtrosTiempo.motivo !== 'Todos') {
                            paramsA.append('motivo', this.filtrosTiempo.motivo);
                        }

                        const responseA = await axios.get(`${BASE_URL}api/analisis/tiempo-promedio-por-motivo?${paramsA.toString()}`);
                        const dataA = responseA.data || {};

                        let data = { ...dataA, comparacion: null };
                        if (this.periodoComparacionActivo(this.filtrosTiempo)) {
                            const paramsB = new URLSearchParams();
                            paramsB.append('fecha_desde', this.filtrosTiempo.fechaDesdeB);
                            paramsB.append('fecha_hasta', this.filtrosTiempo.fechaHastaB);
                            if (this.filtrosTiempo.motivo && this.filtrosTiempo.motivo !== 'Todos') {
                                paramsB.append('motivo', this.filtrosTiempo.motivo);
                            }
                            const responseB = await axios.get(`${BASE_URL}api/analisis/tiempo-promedio-por-motivo?${paramsB.toString()}`);
                            const dataB = responseB.data || {};
                            data = {
                                ...dataA,
                                comparacion: {
                                    activa: true,
                                    nameA: this.etiquetaRangoFechas(this.filtrosTiempo.fechaDesde, this.filtrosTiempo.fechaHasta),
                                    nameB: this.etiquetaRangoFechas(this.filtrosTiempo.fechaDesdeB, this.filtrosTiempo.fechaHastaB),
                                    datosA: dataA.datos || [],
                                    datosB: dataB.datos || [],
                                    totalA: dataA.total || 0,
                                    totalB: dataB.total || 0,
                                },
                            };
                        }

                        const hayVista = this.chartTiempoPromedio || this.chartTiempoPromedioB
                            || document.querySelector('#chartTiempoPromedio .text-muted');
                        if (!this.debeRedibujarAnalisis('tiempoModal', data, hayVista)) {
                            return;
                        }
                        this.datosTiempo = data;
                        this.$nextTick(() => {
                            this.crearGraficoTiempoPromedio();
                        });
                    } catch (error) {
                        console.error('Error al cargar gráfico de tiempo promedio:', error);
                        const elemento = document.getElementById('chartTiempoPromedio');
                        if (elemento) {
                            elemento.innerHTML = '<div class="alert alert-danger">Error al cargar los datos del gráfico. Por favor, intente nuevamente.</div>';
                        }
                    }
                },

                /**
                 * Crea el preview del gráfico de tiempo de reparación por motivo
                 */
                /**
                 * Preview de tiempo: barras verticales (mismo patrón que antigüedad).
                 * El horizontal en card chica quedaba en blanco.
                 */
                crearPreviewGraficoTiempoPromedio() {
                    const elemento = document.getElementById('previewChartTiempoPromedio');
                    if (!elemento) return;
                    if (this.previewChartTiempoPromedio) {
                        try { this.previewChartTiempoPromedio.destroy(); } catch (e) {}
                        this.previewChartTiempoPromedio = null;
                    }
                    elemento.innerHTML = '';

                    const datos = (this.datosTiempo.datos || [])
                        .filter(d => (Number(d.cantidad_registros) || 0) > 0 || (Number(d.valor) || 0) > 0)
                        .sort((a, b) => (Number(b.valor) || 0) - (Number(a.valor) || 0));

                    if (!datos.length) {
                        elemento.innerHTML = '<div class="analisis-preview-empty">Sin datos de reparación en el período</div>';
                        return;
                    }

                    elemento.style.height = '220px';
                    elemento.style.minHeight = '220px';

                    const iconos = datos.map(d => this.iconoMotivoReclamo(d.label));
                    const valores = datos.map(d => Number(d.valor) || 0);
                    const colores = datos.map(d => d.color || '#3A3972');
                    const labelsCompletos = datos.map(d => d.label || '');

                    try {
                    const options = {
                            series: [{ name: 'Minutos', data: valores }],
                        chart: {
                            type: 'bar',
                                height: 220,
                            width: '100%',
                                toolbar: { show: false },
                                animations: { enabled: false },
                                fontFamily: 'Open Sans, Segoe UI, Tahoma, sans-serif'
                        },
                        plotOptions: {
                            bar: {
                                    distributed: true,
                                    borderRadius: 3,
                                    columnWidth: '55%'
                            }
                        },
                            colors: colores,
                        dataLabels: {
                            enabled: true,
                                formatter(val) { return Math.round(Number(val) || 0) + 'm'; },
                                offsetY: -4,
                                style: { fontSize: '10px', fontWeight: 700, colors: ['#3A3972'] }
                        },
                        xaxis: {
                                categories: iconos,
                            labels: {
                                style: {
                                        fontSize: '16px',
                                        fontFamily: 'Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif'
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                    formatter(val) { return Math.round(Number(val) || 0); },
                                    style: { fontSize: '10px' }
                                },
                                min: 0
                            },
                            legend: { show: false },
                            grid: { padding: { top: 8, bottom: 0 } },
                        tooltip: {
                                custom({ series, seriesIndex, dataPointIndex }) {
                                    const motivo = labelsCompletos[dataPointIndex] || '';
                                    const icono = iconos[dataPointIndex] || '';
                                    const val = (series[seriesIndex] || [])[dataPointIndex] || 0;
                                    const regs = datos[dataPointIndex]?.cantidad_registros || 0;
                                    const motivoSafe = String(motivo)
                                        .replace(/&/g, '&amp;')
                                        .replace(/</g, '&lt;')
                                        .replace(/>/g, '&gt;');
                                    return `<div class="analisis-motivo-tooltip"><div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__icono">${icono}</span><span class="analisis-motivo-tooltip__nombre">${motivoSafe}</span></div><div class="analisis-motivo-tooltip__valor">${Math.round(Number(val))} min · ${regs} reg.</div></div>`;
                                }
                            }
                        };
                                this.previewChartTiempoPromedio = new ApexCharts(elemento, options);
                        this.previewChartTiempoPromedio.render().then(() => {
                            try { this.previewChartTiempoPromedio.windowResize(); } catch (e) {}
                        });
                            } catch (error) {
                        console.error('Error al crear preview de tiempo de reparación:', error);
                        elemento.innerHTML = '<div class="analisis-preview-empty">No se pudo cargar el gráfico</div>';
                            }
                },

                /**
                 * Crea el gráfico completo de tiempo de reparación por motivo
                 */
                crearGraficoTiempoPromedio() {
                    const destruir = () => {
                        if (this.chartTiempoPromedio) {
                            try { this.chartTiempoPromedio.destroy(); } catch (e) {}
                            this.chartTiempoPromedio = null;
                        }
                        if (this.chartTiempoPromedioB) {
                            try { this.chartTiempoPromedioB.destroy(); } catch (e) {}
                            this.chartTiempoPromedioB = null;
                        }
                    };
                    destruir();

                    const elementoA = document.getElementById('chartTiempoPromedio');
                    if (!elementoA) return;
                    elementoA.innerHTML = '';

                    const renderUno = (el, datos, totalHint, emptyMsg) => {
                        const hayRegistros = (Number(totalHint) || 0) > 0
                            || (datos || []).some(d => (Number(d.cantidad_registros) || 0) > 0)
                            || (datos || []).some(d => (Number(d.valor) || 0) > 0);
                        if (!datos?.length || !hayRegistros) {
                            el.innerHTML = `<div class="text-center p-4 text-muted">${emptyMsg}</div>`;
                            return null;
                        }
                        const chart = new ApexCharts(
                            el,
                            this.opcionesGraficoTiempoReparacionBarras(datos, { preview: false })
                        );
                        chart.render();
                        return chart;
                    };

                    const comparacion = this.datosTiempo.comparacion;
                    if (comparacion && comparacion.activa) {
                        this.$nextTick(() => {
                            this.chartTiempoPromedio = renderUno(
                                elementoA,
                                comparacion.datosA || [],
                                comparacion.totalA,
                                'Sin datos de reparación en el período A'
                            );
                            const elementoB = document.getElementById('chartTiempoPromedioB');
                            if (!elementoB) return;
                            elementoB.innerHTML = '';
                            this.chartTiempoPromedioB = renderUno(
                                elementoB,
                                comparacion.datosB || [],
                                comparacion.totalB,
                                'Sin datos de reparación en el período B'
                            );
                        });
                        return;
                    }

                    const datos = this.datosTiempo.datos || [];
                    this.$nextTick(() => requestAnimationFrame(() => {
                        this.chartTiempoPromedio = renderUno(
                            elementoA,
                            datos,
                            this.datosTiempo.total,
                            'Sin datos de reparación en el período (cronómetro de obra)'
                        );
                    }));
                },

                /**
                 * Exporta el gráfico de tiempo promedio como imagen
                 */
                exportarGraficoTiempoPromedio() {
                    if (!this.chartTiempoPromedio) {
                        alert('El gráfico no está cargado. Por favor, espere un momento y vuelva a intentar.');
                        return;
                    }

                    try {
                        // ApexCharts usa dataURI para exportar
                        this.chartTiempoPromedio.dataURI({
                            scale: 2
                        }).then((result) => {
                            const imgURI = result.imgURI || result;
                            const link = document.createElement('a');
                            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
                            link.download = 'grafico-tiempo-promedio-' + timestamp + '.png';
                            link.href = imgURI;
                            document.body.appendChild(link);
                            link.click();
                            setTimeout(() => {
                                document.body.removeChild(link);
                                if (link.href && link.href.startsWith('data:')) {
                                    link.href = '';
                                }
                            }, 100);
                        }).catch((error) => {
                            console.error('Error al exportar gráfico de tiempo promedio:', error);
                            alert('Error al exportar el gráfico: ' + (error.message || 'Error desconocido'));
                        });
                    } catch (error) {
                        console.error('Error al exportar gráfico de tiempo promedio:', error);
                        alert('Error al exportar el gráfico. Por favor, intente nuevamente.');
                    }
                },

                /**
                 * Carga datos para el preview del gráfico de evolución del tiempo promedio
                 */
                async cargarPreviewEvolucionTiempo() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosEvolucionTiempo.fechaDesde) params.append('fecha_desde', this.filtrosEvolucionTiempo.fechaDesde);
                        if (this.filtrosEvolucionTiempo.fechaHasta) params.append('fecha_hasta', this.filtrosEvolucionTiempo.fechaHasta);
                        params.append('granularidad', this.filtrosEvolucionTiempo.granularidad || 'semanal');
                        if (this.filtrosEvolucionTiempo.motivo && this.filtrosEvolucionTiempo.motivo !== 'Todos') {
                            params.append('motivo', this.filtrosEvolucionTiempo.motivo);
                        }

                        const response = await axios.get(`${BASE_URL}api/analisis/evolucion-tiempo-promedio?${params.toString()}`);
                        const data = response.data || {};
                        if (!this.debeRedibujarAnalisis('evolucionTiempo', data, this.previewChartEvolucionTiempoValido())) {
                            return;
                        }
                        this.datosEvolucionTiempo = data;
                        await this.$nextTick();
                        requestAnimationFrame(() => this.crearPreviewGraficoEvolucionTiempo());
                    } catch (error) {
                        console.error('Error al cargar preview de evolución tiempo:', error);
                    }
                },

                /**
                 * Carga datos para el gráfico completo de evolución del tiempo promedio
                 */
                async cargarGraficoEvolucionTiempo() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosEvolucionTiempo.fechaDesde) params.append('fecha_desde', this.filtrosEvolucionTiempo.fechaDesde);
                        if (this.filtrosEvolucionTiempo.fechaHasta) params.append('fecha_hasta', this.filtrosEvolucionTiempo.fechaHasta);
                        params.append('granularidad', this.filtrosEvolucionTiempo.granularidad || 'semanal');
                        if (this.filtrosEvolucionTiempo.motivo && this.filtrosEvolucionTiempo.motivo !== 'Todos') {
                            params.append('motivo', this.filtrosEvolucionTiempo.motivo);
                        }

                        const response = await axios.get(`${BASE_URL}api/analisis/evolucion-tiempo-promedio?${params.toString()}`);
                        const data = response.data || {};
                        const hayVista = this.chartEvolucionTiempo
                            || document.querySelector('#chartEvolucionTiempo .text-muted');
                        if (!this.debeRedibujarAnalisis('evolucionTiempoModal', data, hayVista)) {
                            return;
                        }
                        this.datosEvolucionTiempo = data;
                        this.$nextTick(() => {
                            this.crearGraficoEvolucionTiempo();
                        });
                    } catch (error) {
                        console.error('Error al cargar gráfico de evolución tiempo:', error);
                    }
                },

                /**
                 * Separa levemente los puntos que coinciden en un mismo período para que
                 * no queden apilados. El desfase se reparte alrededor del valor real y solo
                 * afecta a las series empatadas, así el resto queda en su valor exacto.
                 */
                seriesEvolucionTiempoConDesfase(seriesList) {
                    const lista = Array.isArray(seriesList) ? seriesList : [];
                    const valores = [];
                    lista.forEach((s) => {
                        (s.data || []).forEach((v) => {
                            if (v !== null && v !== undefined && Number.isFinite(Number(v))) {
                                valores.push(Number(v));
                            }
                        });
                    });
                    const maxVal = Math.max(1, ...(valores.length ? valores : [0]));
                    const step = Math.min(0.2, Math.max(0.04, maxVal * 0.02));

                    const series = lista.map((s) => {
                        const originales = (s.data || []).map((d) =>
                            (d === null || d === undefined) ? null : Number(d)
                        );
                        return {
                            name: s.name,
                            color: s.color,
                            originales,
                            data: originales.slice()
                        };
                    });

                    const cantidadPuntos = series.reduce((max, s) => Math.max(max, s.originales.length), 0);
                    for (let punto = 0; punto < cantidadPuntos; punto++) {
                        const empates = new Map();
                        series.forEach((s, idx) => {
                            const val = s.originales[punto];
                            if (val === null || val === undefined || !Number.isFinite(val)) return;
                            const clave = val.toFixed(4);
                            if (!empates.has(clave)) empates.set(clave, []);
                            empates.get(clave).push(idx);
                        });

                        empates.forEach((indices) => {
                            if (indices.length < 2) return;
                            const mid = (indices.length - 1) / 2;
                            indices.forEach((idx, posicion) => {
                                series[idx].data[punto] = series[idx].originales[punto] + (posicion - mid) * step;
                            });
                        });
                    }

                    return series;
                },

                /**
                 * Crea el preview del gráfico de evolución del tiempo de reparación
                 */
                crearPreviewGraficoEvolucionTiempo() {
                    const elemento = document.getElementById('previewChartEvolucionTiempo');
                    if (!elemento) return;

                    if (this.previewChartEvolucionTiempo) {
                        try { this.previewChartEvolucionTiempo.destroy(); } catch (e) {}
                        this.previewChartEvolucionTiempo = null;
                    }

                    const seriesSrc = this.datosEvolucionTiempo.series || [];
                    const labels = this.datosEvolucionTiempo.labels || [];
                    const seriesConDatos = seriesSrc.filter(s =>
                        (s.data || []).some(v => v !== null && v !== undefined && Number(v) > 0)
                    );

                    if (!labels.length || !seriesConDatos.length) {
                        elemento.innerHTML = '<div class="analisis-preview-empty">Sin datos de reparación en el período</div>';
                        return;
                    }

                    elemento.innerHTML = '';
                    elemento.style.height = '220px';
                    elemento.style.minHeight = '220px';

                    const nombres = seriesConDatos.map(s => s.name || '');
                    const iconos = nombres.map(n => this.iconoMotivoReclamo(n));
                    const colores = seriesConDatos.map(s => s.color || '#3A3972');
                    const emojiFont = 'Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif';
                    const seriesDesfasadas = this.seriesEvolucionTiempoConDesfase(seriesConDatos);
                    const seriesFormateadas = seriesDesfasadas.map((s, idx) => ({
                        name: iconos[idx] || s.name,
                        data: s.data
                    }));

                    try {
                        const chart = new ApexCharts(elemento, {
                        series: seriesFormateadas,
                        chart: {
                            type: 'line',
                                height: 220,
                                width: '100%',
                            toolbar: { show: false },
                                zoom: { enabled: false },
                                animations: { enabled: false },
                                fontFamily: 'Open Sans, Segoe UI, Tahoma, sans-serif'
                            },
                            stroke: {
                                curve: 'smooth',
                                width: 2
                            },
                            markers: {
                                size: 4,
                                strokeWidth: 1.5,
                                strokeColors: '#fff',
                                hover: { sizeOffset: 2 }
                            },
                            dataLabels: { enabled: false },
                        xaxis: {
                                categories: this.formatearLabelsPeriodo(labels),
                            labels: { 
                                    rotate: -35,
                                    style: { fontSize: '9px' },
                                    maxHeight: 40
                                },
                                tooltip: { enabled: false }
                            },
                            yaxis: {
                            labels: { 
                                    formatter(val) { return Math.round(Number(val) || 0); },
                                    style: { fontSize: '9px' }
                                },
                                min: 0
                        },
                        legend: { 
                                show: true,
                            position: 'top',
                                fontSize: '14px',
                                fontFamily: emojiFont,
                                itemMargin: { horizontal: 6, vertical: 0 },
                                height: 26
                        },
                            colors: seriesConDatos.map(s => s.color),
                            grid: { padding: { top: 0, bottom: 0 } },
                        tooltip: {
                            shared: true,
                                custom({ dataPointIndex }) {
                                    const periodo = (labels && labels[dataPointIndex]) || '';
                                    let rows = '';
                                    seriesDesfasadas
                                        .map((s, seriesIndex) => ({
                                            seriesIndex,
                                            val: s.originales[dataPointIndex],
                                            alturaEnGrafico: Number(s.data[dataPointIndex])
                                        }))
                                        .filter(item => item.val !== null && item.val !== undefined)
                                        .sort((a, b) => b.alturaEnGrafico - a.alturaEnGrafico)
                                        .forEach(({ seriesIndex, val }) => {
                                        const icono = iconos[seriesIndex] || '';
                                        const nombre = String(nombres[seriesIndex] || '')
                                            .replace(/&/g, '&amp;')
                                            .replace(/</g, '&lt;')
                                            .replace(/>/g, '&gt;');
                                        const punto = `<span class="analisis-motivo-tooltip__punto" style="background:${colores[seriesIndex]}"></span>`;
                                        rows += `<div class="analisis-motivo-tooltip__serie">${punto}<span><strong>${icono} ${nombre}</strong>: ${Math.round(Number(val))} min</span></div>`;
                                    });
                                    if (!rows) rows = '<div>Sin datos</div>';
                                    return `<div class="analisis-motivo-tooltip"><div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__nombre">${periodo}</span></div><div class="analisis-motivo-tooltip__valor">${rows}</div></div>`;
                                }
                            }
                        });
                        chart.render().then(() => {
                            try { chart.windowResize(); } catch (e) {}
                        });
                        this.previewChartEvolucionTiempo = chart;
                    } catch (error) {
                        console.error('Error al crear preview evolución tiempo:', error);
                        elemento.innerHTML = '<div class="analisis-preview-empty">No se pudo cargar el gráfico</div>';
                    }
                },

                /**
                 * Crea el gráfico completo de evolución del tiempo de reparación
                 */
                crearGraficoEvolucionTiempo() {
                    this.renderGraficoEvolucionTiempo('chartEvolucionTiempo', false);
                },

                renderGraficoEvolucionTiempo(elementId, preview) {
                    const elemento = document.getElementById(elementId);
                    const seriesSrc = this.datosEvolucionTiempo.series || [];
                    const labels = this.datosEvolucionTiempo.labels || [];
                    const hayDatos = labels.length > 0 && seriesSrc.some(s =>
                        (s.data || []).some(v => v !== null && v !== undefined && Number(v) > 0)
                    );

                    if (!elemento) return;

                    if (preview && this.previewChartEvolucionTiempo) {
                        try { this.previewChartEvolucionTiempo.destroy(); } catch (e) {}
                        this.previewChartEvolucionTiempo = null;
                    }
                    if (!preview && this.chartEvolucionTiempo) {
                        try { this.chartEvolucionTiempo.destroy(); } catch (e) {}
                        this.chartEvolucionTiempo = null;
                    }

                    if (!hayDatos) {
                        elemento.innerHTML = preview
                            ? '<div class="analisis-preview-empty">Sin datos de reparación en el período</div>'
                            : '<div class="text-center p-4 text-muted">Sin datos de reparación en el período (cronómetro de obra)</div>';
                        return;
                    }

                    elemento.innerHTML = '';
                    if (preview) {
                        elemento.style.height = '220px';
                        elemento.style.minHeight = '220px';
                    }

                    const nombres = seriesSrc.map(s => s.name || '');
                    const iconos = nombres.map(n => this.iconoMotivoReclamo(n));
                    const colores = seriesSrc.map(s => s.color || '#3A3972');
                    const emojiFont = 'Apple Color Emoji, Segoe UI Emoji, Noto Color Emoji, sans-serif';
                    const seriesDesfasadas = this.seriesEvolucionTiempoConDesfase(seriesSrc);
                    const seriesFormateadas = seriesDesfasadas.map((s, idx) => ({
                        name: iconos[idx] || s.name,
                        data: s.data
                    }));

                    const options = {
                        series: seriesFormateadas,
                        chart: {
                            type: 'line',
                            height: preview ? 220 : 450,
                            toolbar: { show: false },
                            zoom: { enabled: false },
                            animations: { enabled: !preview },
                            fontFamily: 'Open Sans, Segoe UI, Tahoma, sans-serif'
                        },
                        stroke: {
                            curve: 'smooth',
                            width: preview ? 2 : 2.5
                        },
                        markers: {
                            size: preview ? 3 : 5,
                            strokeWidth: 2,
                            strokeColors: '#fff',
                            hover: { sizeOffset: 2 }
                        },
                        dataLabels: { enabled: false },
                        xaxis: {
                            categories: this.formatearLabelsPeriodo(this.datosEvolucionTiempo.labels),
                            labels: { 
                                rotate: -45, 
                                style: { fontSize: preview ? '9px' : '12px' },
                                maxHeight: preview ? 48 : 80
                            },
                            tooltip: {
                                enabled: false
                            }
                        },
                        yaxis: {
                            title: preview ? undefined : {
                                text: 'Minutos de reparación',
                                style: { fontSize: '13px' }
                            },
                            labels: { 
                                formatter(val) { return Math.round(Number(val) || 0) + ' min'; },
                                style: { fontSize: preview ? '9px' : '12px' }
                                },
                            min: 0
                        },
                        legend: { 
                            show: true,
                            position: 'top',
                            fontSize: preview ? '14px' : '18px',
                            fontFamily: emojiFont,
                            itemMargin: { horizontal: preview ? 4 : 8, vertical: 2 },
                            height: preview ? 28 : undefined
                        },
                        colors: seriesSrc.map(s => s.color),
                        tooltip: {
                            shared: true,
                                custom({ dataPointIndex, w }) {
                                    const periodo = (w.globals.categoryLabels && w.globals.categoryLabels[dataPointIndex])
                                    || (w.config.xaxis.categories && w.config.xaxis.categories[dataPointIndex])
                                    || '';
                                let rows = '';
                                seriesDesfasadas
                                    .map((s, seriesIndex) => ({
                                        seriesIndex,
                                        val: s.originales[dataPointIndex],
                                        alturaEnGrafico: Number(s.data[dataPointIndex])
                                    }))
                                    .filter(item => item.val !== null && item.val !== undefined)
                                    .sort((a, b) => b.alturaEnGrafico - a.alturaEnGrafico)
                                    .forEach(({ seriesIndex, val }) => {
                                    const icono = iconos[seriesIndex] || '';
                                    const nombre = String(nombres[seriesIndex] || '')
                                        .replace(/&/g, '&amp;')
                                        .replace(/</g, '&lt;')
                                        .replace(/>/g, '&gt;');
                                    const punto = `<span class="analisis-motivo-tooltip__punto" style="background:${colores[seriesIndex]}"></span>`;
                                    rows += `<div class="analisis-motivo-tooltip__serie">${punto}<span><strong>${icono} ${nombre}</strong>: ${Math.round(Number(val))} min</span></div>`;
                                });
                                if (!rows) rows = '<div>Sin datos en este período</div>';
                                return `<div class="analisis-motivo-tooltip"><div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__nombre">${periodo}</span></div><div class="analisis-motivo-tooltip__valor">${rows}</div></div>`;
                            }
                        }
                    };

                    const chart = new ApexCharts(elemento, options);
                    chart.render();
                    if (preview) this.previewChartEvolucionTiempo = chart;
                    else this.chartEvolucionTiempo = chart;
                },

                /**
                 * Exporta el gráfico de evolución tiempo como imagen
                 */
                exportarGraficoEvolucionTiempo() {
                    if (!this.chartEvolucionTiempo) {
                        alert('El gráfico no está cargado. Por favor, espere un momento y vuelva a intentar.');
                        return;
                    }

                    try {
                        this.chartEvolucionTiempo.dataURI({ scale: 2 }).then((result) => {
                            const imgURI = result.imgURI || result;
                            const link = document.createElement('a');
                            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
                            link.download = 'grafico-evolucion-tiempo-' + timestamp + '.png';
                            link.href = imgURI;
                            document.body.appendChild(link);
                            link.click();
                            setTimeout(() => {
                                document.body.removeChild(link);
                                if (link.href && link.href.startsWith('data:')) {
                                    link.href = '';
                                }
                            }, 100);
                        }).catch((error) => {
                            console.error('Error al exportar gráfico de evolución tiempo:', error);
                            alert('Error al exportar el gráfico: ' + (error.message || 'Error desconocido'));
                        });
                    } catch (error) {
                        console.error('Error al exportar gráfico de evolución tiempo:', error);
                        alert('Error al exportar el gráfico. Por favor, intente nuevamente.');
                    }
                },

                // ==================== GRÁFICO CONSUMO MATERIALES ====================
                paramsConsumoMateriales(overrides = {}) {
                    const f = { ...this.filtrosConsumoMateriales, ...overrides };
                    const params = new URLSearchParams();
                    if (f.fechaDesde) params.append('fecha_desde', f.fechaDesde);
                    if (f.fechaHasta) params.append('fecha_hasta', f.fechaHasta);
                    params.append('granularidad', f.granularidad || 'mensual');
                    if (f.categoria && f.categoria !== 'Todas') {
                        params.append('categoria', f.categoria);
                    }
                    if (f.material && f.material !== 'Todos') {
                        params.append('material', f.material);
                    }
                    return params;
                },

                aplicarDatosConsumoMateriales(data) {
                    const categorias = Array.isArray(data.categorias_disponibles) ? data.categorias_disponibles : [];
                    const disponibles = Array.isArray(data.materiales_disponibles) ? data.materiales_disponibles : [];
                    let necesitaReload = false;

                    if (
                        this.filtrosConsumoMateriales.categoria !== 'Todas'
                        && categorias.length
                        && !categorias.includes(this.filtrosConsumoMateriales.categoria)
                    ) {
                        this.filtrosConsumoMateriales.categoria = 'Todas';
                        this.filtrosConsumoMateriales.material = 'Todos';
                        necesitaReload = true;
                    } else if (
                        this.filtrosConsumoMateriales.material !== 'Todos'
                        && disponibles.length
                        && !disponibles.includes(this.filtrosConsumoMateriales.material)
                    ) {
                        this.filtrosConsumoMateriales.material = 'Todos';
                        necesitaReload = true;
                    }

                    this.datosConsumoMateriales = {
                        ...data,
                        categorias_disponibles: categorias,
                        materiales_disponibles: disponibles,
                    };
                    return necesitaReload;
                },

                onCambioCategoriaConsumoMateriales() {
                    this.filtrosConsumoMateriales.material = 'Todos';
                    this.cargarGraficoConsumoMateriales();
                },

                async cargarPreviewConsumoMateriales() {
                    try {
                        const response = await axios.get(
                            `${BASE_URL}api/analisis/consumo-materiales?${this.paramsConsumoMateriales().toString()}`
                        );
                        const data = response.data || {};
                        const hayVista = this.previewChartConsumoMateriales
                            || document.querySelector('#previewChartConsumoMateriales .text-muted');
                        if (!this.debeRedibujarAnalisis('consumo', data, hayVista)) {
                            return;
                        }
                        if (this.aplicarDatosConsumoMateriales({ ...data, comparacion: null })) {
                            return this.cargarPreviewConsumoMateriales();
                        }
                        this.$nextTick(() => this.crearPreviewConsumoMateriales());
                    } catch (error) {
                        console.error('Error al cargar preview consumo materiales:', error);
                    }
                },

                async cargarGraficoConsumoMateriales() {
                    try {
                        const responseA = await axios.get(
                            `${BASE_URL}api/analisis/consumo-materiales?${this.paramsConsumoMateriales().toString()}`
                        );
                        const dataA = responseA.data || {};

                        let data = { ...dataA, comparacion: null };
                        if (this.periodoComparacionActivo(this.filtrosConsumoMateriales)) {
                            const responseB = await axios.get(
                                `${BASE_URL}api/analisis/consumo-materiales?${this.paramsConsumoMateriales({
                                    fechaDesde: this.filtrosConsumoMateriales.fechaDesdeB,
                                    fechaHasta: this.filtrosConsumoMateriales.fechaHastaB,
                                }).toString()}`
                            );
                            const dataB = responseB.data || {};
                            const categorias = [...new Set([
                                ...(dataA.categorias_disponibles || []),
                                ...(dataB.categorias_disponibles || []),
                            ])];
                            const materiales = [...new Set([
                                ...(dataA.materiales_disponibles || []),
                                ...(dataB.materiales_disponibles || []),
                            ])];
                            data = {
                                ...dataA,
                                categorias_disponibles: categorias,
                                materiales_disponibles: materiales,
                                comparacion: {
                                    activa: true,
                                    nameA: this.etiquetaRangoFechas(
                                        this.filtrosConsumoMateriales.fechaDesde,
                                        this.filtrosConsumoMateriales.fechaHasta
                                    ),
                                    nameB: this.etiquetaRangoFechas(
                                        this.filtrosConsumoMateriales.fechaDesdeB,
                                        this.filtrosConsumoMateriales.fechaHastaB
                                    ),
                                    datosA: {
                                        labels: dataA.labels || [],
                                        series: dataA.series || [],
                                    },
                                    datosB: {
                                        labels: dataB.labels || [],
                                        series: dataB.series || [],
                                    },
                                },
                            };
                        }

                        if (!this.debeRedibujarAnalisis(
                            'consumoModal',
                            data,
                            this.chartConsumoMateriales || this.chartConsumoMaterialesB
                        )) {
                            return;
                        }
                        if (this.aplicarDatosConsumoMateriales(data)) {
                            return this.cargarGraficoConsumoMateriales();
                        }
                        this.$nextTick(() => this.crearGraficoConsumoMateriales());
                    } catch (error) {
                        console.error('Error al cargar gráfico consumo materiales:', error);
                    }
                },

                crearPreviewConsumoMateriales() {
                    const elemento = document.getElementById('previewChartConsumoMateriales');
                    if (!elemento) return;

                    if (this.previewChartConsumoMateriales) {
                        try { this.previewChartConsumoMateriales.destroy(); } catch (e) {}
                        this.previewChartConsumoMateriales = null;
                    }

                    elemento.innerHTML = '';

                    if (!this.datosConsumoMateriales.series || this.datosConsumoMateriales.series.length === 0) {
                        elemento.innerHTML = '<div class="text-center p-4 text-muted"><i class="bi bi-box-seam" style="font-size: 2rem;"></i><p class="mt-2">No hay datos de materiales</p></div>';
                        return;
                    }

                    const periodos = this.formatearLabelsPeriodo(this.datosConsumoMateriales.labels);
                    const materiales = this.datosConsumoMateriales.series.map(s => ({
                        nombre: s.name || '',
                        color: s.color || '#3A3972',
                        data: s.data || []
                    }));

                    const options = {
                        series: this.datosConsumoMateriales.series.map(s => ({ name: s.name, data: s.data })),
                        chart: { type: 'line', height: 220, toolbar: { show: false }, zoom: { enabled: false } },
                        stroke: { curve: 'smooth', width: 2 },
                        markers: {
                            size: 4,
                            strokeWidth: 1.5,
                            strokeColors: '#fff',
                            hover: { sizeOffset: 2 }
                        },
                        colors: this.datosConsumoMateriales.series.map(s => s.color),
                        xaxis: { categories: this.formatearLabelsPeriodo(this.datosConsumoMateriales.labels), labels: { show: false } },
                        yaxis: {
                            min: 0,
                            decimalsInFloat: 0,
                            labels: {
                                formatter(val) { return String(Math.round(Number(val) || 0)); },
                                style: { fontSize: '10px' }
                            }
                        },
                        tooltip: {
                            shared: true,
                            custom({ dataPointIndex }) {
                                const periodo = String(periodos[dataPointIndex] ?? '')
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;');
                                let filas = '';
                                materiales
                                    .map(m => ({ ...m, valor: Number(m.data[dataPointIndex]) || 0 }))
                                    .sort((a, b) => b.valor - a.valor)
                                    .forEach((m) => {
                                        const nombre = String(m.nombre)
                                            .replace(/&/g, '&amp;')
                                            .replace(/</g, '&lt;')
                                            .replace(/>/g, '&gt;');
                                        filas += `<div class="analisis-motivo-tooltip__serie">`
                                            + `<span class="analisis-motivo-tooltip__punto" style="background:${m.color}"></span>`
                                            + `<span><strong>${nombre}</strong>: ${Math.round(m.valor)}</span>`
                                            + `</div>`;
                                    });
                                if (!filas) filas = '<div>Sin datos en este período</div>';
                                return `<div class="analisis-motivo-tooltip">`
                                    + `<div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__nombre">${periodo}</span></div>`
                                    + `<div class="analisis-motivo-tooltip__valor">${filas}</div></div>`;
                            }
                        },
                        legend: { show: true, position: 'top', fontSize: '10px' },
                        dataLabels: { enabled: false }
                    };

                    try {
                        this.previewChartConsumoMateriales = new ApexCharts(elemento, options);
                        this.previewChartConsumoMateriales.render();
                    } catch (e) {
                        console.error('Error al crear preview consumo materiales:', e);
                    }
                },

                opcionesGraficoConsumoLineas(datos, { altura = 450 } = {}) {
                    const periodos = this.formatearLabelsPeriodo(datos.labels || []);
                    const materiales = (datos.series || []).map(s => ({
                        nombre: s.name || '',
                        color: s.color || '#3A3972',
                        data: s.data || []
                    }));
                    return {
                        series: (datos.series || []).map(s => ({ name: s.name, data: s.data })),
                        chart: { type: 'line', height: altura, toolbar: { show: false }, zoom: { enabled: false } },
                        stroke: { curve: 'smooth', width: 2.5 },
                        markers: {
                            size: 5,
                            strokeWidth: 2,
                            strokeColors: '#fff',
                            hover: { sizeOffset: 2 }
                        },
                        colors: (datos.series || []).map(s => s.color),
                        xaxis: {
                            categories: this.formatearLabelsPeriodo(datos.labels || []),
                            labels: { rotate: -45, style: { fontSize: '11px' } }
                        },
                        yaxis: {
                            min: 0,
                            decimalsInFloat: 0,
                            title: { text: 'Cantidad utilizada', style: { fontSize: '13px' } },
                            labels: {
                                formatter(val) { return String(Math.round(Number(val) || 0)); },
                                style: { fontSize: '12px' }
                            }
                        },
                        tooltip: {
                            shared: true,
                            custom({ dataPointIndex }) {
                                const periodo = String(periodos[dataPointIndex] ?? '')
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;');
                                let filas = '';
                                materiales
                                    .map(m => ({ ...m, valor: Number(m.data[dataPointIndex]) || 0 }))
                                    .sort((a, b) => b.valor - a.valor)
                                    .forEach((m) => {
                                        const nombre = String(m.nombre)
                                            .replace(/&/g, '&amp;')
                                            .replace(/</g, '&lt;')
                                            .replace(/>/g, '&gt;');
                                        filas += `<div class="analisis-motivo-tooltip__serie">`
                                            + `<span class="analisis-motivo-tooltip__punto" style="background:${m.color}"></span>`
                                            + `<span><strong>${nombre}</strong>: ${Math.round(m.valor)}</span>`
                                            + `</div>`;
                                    });
                                if (!filas) filas = '<div>Sin datos en este período</div>';
                                return `<div class="analisis-motivo-tooltip">`
                                    + `<div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__nombre">${periodo}</span></div>`
                                    + `<div class="analisis-motivo-tooltip__valor">${filas}</div></div>`;
                            }
                        },
                        legend: { show: true, position: 'top', fontSize: '13px' },
                        dataLabels: { enabled: false }
                    };
                },

                crearGraficoConsumoMateriales() {
                    if (this.chartConsumoMateriales) {
                        try { this.chartConsumoMateriales.destroy(); } catch (e) {}
                        this.chartConsumoMateriales = null;
                    }
                    if (this.chartConsumoMaterialesB) {
                        try { this.chartConsumoMaterialesB.destroy(); } catch (e) {}
                        this.chartConsumoMaterialesB = null;
                    }

                    const elementoA = document.getElementById('chartConsumoMateriales');
                    if (!elementoA) return;
                    elementoA.innerHTML = '';

                    const renderLineas = (el, datos, emptyMsg) => {
                        if (!datos?.series?.length) {
                            el.innerHTML = `<div class="text-center p-4 text-muted"><i class="bi bi-box-seam" style="font-size: 2rem;"></i><p class="mt-2 mb-0">${emptyMsg}</p></div>`;
                            return null;
                        }
                        el.style.minHeight = '360px';
                        const chart = new ApexCharts(el, this.opcionesGraficoConsumoLineas(datos, { altura: 360 }));
                        chart.render();
                        return chart;
                    };

                    const comparacion = this.datosConsumoMateriales.comparacion;
                    if (comparacion && comparacion.activa) {
                        this.$nextTick(() => {
                            this.chartConsumoMateriales = renderLineas(
                                elementoA,
                                comparacion.datosA,
                                'Sin consumo en el período A'
                            );
                            const elementoB = document.getElementById('chartConsumoMaterialesB');
                            if (!elementoB) return;
                            elementoB.innerHTML = '';
                            this.chartConsumoMaterialesB = renderLineas(
                                elementoB,
                                comparacion.datosB,
                                'Sin consumo en el período B'
                            );
                        });
                        return;
                    }

                    if (!this.datosConsumoMateriales.series || this.datosConsumoMateriales.series.length === 0) {
                        elementoA.innerHTML = '<div class="text-center p-5 text-muted"><i class="bi bi-box-seam" style="font-size: 3rem;"></i><p class="mt-3">No hay datos de consumo de materiales para el período seleccionado</p><small>Verifique que existan registros en la tabla material_reclamo</small></div>';
                        return;
                    }

                    elementoA.style.minHeight = '450px';
                    try {
                        this.chartConsumoMateriales = new ApexCharts(
                            elementoA,
                            this.opcionesGraficoConsumoLineas(this.datosConsumoMateriales, { altura: 450 })
                        );
                        this.chartConsumoMateriales.render();
                    } catch (e) {
                        console.error('Error al crear gráfico consumo materiales:', e);
                        elementoA.innerHTML = '<div class="alert alert-danger">Error al crear el gráfico</div>';
                    }
                },

                exportarGraficoConsumoMateriales() {
                    if (!this.chartConsumoMateriales) { alert('El gráfico no está cargado.'); return; }
                    this.chartConsumoMateriales.dataURI({ scale: 2 }).then(result => {
                        const link = document.createElement('a');
                        link.download = 'consumo-materiales-' + new Date().toISOString().slice(0, 10) + '.png';
                        link.href = result.imgURI || result;
                        link.click();
                    });
                },

                // ==================== ANTIGÜEDAD DE ABIERTOS ====================
                async cargarPreviewAntiguedad() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosAntiguedad.prioridad) {
                            params.append('prioridad', this.filtrosAntiguedad.prioridad);
                        }
                        const qs = params.toString();
                        const response = await axios.get(`${BASE_URL}api/analisis/antiguedad-abiertos${qs ? `?${qs}` : ''}`);
                        const data = response.data || {};
                        if (!this.debeRedibujarAnalisis('antiguedad', data, this.previewChartAntiguedad)) {
                            return;
                        }
                        this.datosAntiguedad = data;
                        this.$nextTick(() => this.crearPreviewAntiguedad());
                    } catch (error) {
                        console.error('Error al cargar preview antigüedad:', error);
                    }
                },

                async cargarGraficoAntiguedad() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosAntiguedad.prioridad) {
                            params.append('prioridad', this.filtrosAntiguedad.prioridad);
                        }
                        const qs = params.toString();
                        const response = await axios.get(`${BASE_URL}api/analisis/antiguedad-abiertos${qs ? `?${qs}` : ''}`);
                        const data = response.data || {};
                        if (!this.debeRedibujarAnalisis('antiguedadModal', data, this.chartAntiguedad)) {
                            return;
                        }
                        this.datosAntiguedad = data;
                        this.$nextTick(() => this.crearGraficoAntiguedad());
                    } catch (error) {
                        console.error('Error al cargar gráfico antigüedad:', error);
                    }
                },

                /**
                 * Preview ApexCharts con el mismo patrón que consumo/motivo:
                 * altura fija en px (no height: '100%').
                 */
                crearPreviewAntiguedad() {
                    const elemento = document.getElementById('previewChartAntiguedad');
                    if (!elemento) return;

                    if (this.previewChartAntiguedad) {
                        try { this.previewChartAntiguedad.destroy(); } catch (e) {}
                        this.previewChartAntiguedad = null;
                    }

                    elemento.innerHTML = '';

                    const labels = this.datosAntiguedad.labels || [];
                    const valores = (this.datosAntiguedad.series && this.datosAntiguedad.series[0]
                        ? this.datosAntiguedad.series[0].data
                        : []) || [];
                    const colores = this.datosAntiguedad.colors || ['#198754', '#ffc107', '#fd7e14', '#dc3545'];
                    const total = Number(this.datosAntiguedad.total) || 0;

                    if (total === 0) {
                        elemento.innerHTML = '<div class="analisis-preview-empty">Sin reclamos abiertos</div>';
                        return;
                    }

                    elemento.style.height = '220px';
                    elemento.style.minHeight = '220px';

                    const options = {
                        series: [{ name: 'Abiertos', data: valores }],
                        chart: {
                            type: 'bar',
                            height: 220,
                            toolbar: { show: false },
                            animations: { enabled: false },
                            fontFamily: 'Open Sans, Segoe UI, Tahoma, sans-serif'
                        },
                        plotOptions: {
                            bar: {
                                distributed: true,
                                borderRadius: 3,
                                columnWidth: '55%'
                            }
                        },
                        colors: colores,
                        dataLabels: {
                            enabled: true,
                            formatter(val) { return val; },
                            offsetY: -4,
                            style: { fontSize: '10px', fontWeight: 700, colors: ['#3A3972'] }
                        },
                        xaxis: {
                            categories: labels,
                            labels: {
                                rotate: -30,
                                style: { fontSize: '9px' }
                            }
                        },
                        yaxis: {
                            labels: { style: { fontSize: '10px' } },
                            min: 0
                        },
                        legend: { show: false },
                        grid: {
                            padding: { top: 10, bottom: 0, left: 4, right: 4 }
                        },
                        tooltip: {
                            custom({ dataPointIndex }) {
                                const rango = String(labels[dataPointIndex] ?? '')
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;');
                                const val = Number(valores[dataPointIndex]) || 0;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : '0.0';
                                const color = colores[dataPointIndex % colores.length] || '#3A3972';
                                return `<div class="analisis-motivo-tooltip">`
                                    + `<div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__nombre">${rango}</span></div>`
                                    + `<div class="analisis-motivo-tooltip__valor"><div class="analisis-motivo-tooltip__serie">`
                                    + `<span class="analisis-motivo-tooltip__punto" style="background:${color}"></span>`
                                    + `<span><strong>${val} abiertos</strong> (${pct}%)</span>`
                                    + `</div></div></div>`;
                            }
                        }
                    };

                    try {
                        this.previewChartAntiguedad = new ApexCharts(elemento, options);
                        this.previewChartAntiguedad.render();
                    } catch (e) {
                        console.error('Error al crear preview antigüedad:', e);
                        elemento.innerHTML = '<div class="analisis-preview-empty">No se pudo cargar el gráfico</div>';
                    }
                },

                crearGraficoAntiguedad() {
                    const elemento = document.getElementById('chartAntiguedad');
                    if (!elemento) return;

                    if (this.chartAntiguedad) {
                        try { this.chartAntiguedad.destroy(); } catch (e) {}
                        this.chartAntiguedad = null;
                    }

                    elemento.innerHTML = '';

                    const labels = this.datosAntiguedad.labels || [];
                    const valores = (this.datosAntiguedad.series && this.datosAntiguedad.series[0]
                        ? this.datosAntiguedad.series[0].data
                        : []) || [];
                    const colores = this.datosAntiguedad.colors || ['#198754', '#ffc107', '#fd7e14', '#dc3545'];
                    const total = Number(this.datosAntiguedad.total) || 0;

                    if (total === 0) {
                        elemento.innerHTML = '<div class="analisis-preview-empty">Sin reclamos abiertos</div>';
                        return;
                    }

                    const options = {
                        series: [{ name: 'Abiertos', data: valores }],
                        chart: {
                            type: 'bar',
                            height: 450,
                            toolbar: { show: false },
                            animations: { enabled: false },
                            fontFamily: 'Open Sans, Segoe UI, Tahoma, sans-serif'
                        },
                        plotOptions: {
                            bar: {
                                distributed: true,
                                borderRadius: 4,
                                columnWidth: '45%'
                            }
                        },
                        colors: colores,
                        dataLabels: {
                            enabled: true,
                            formatter(val) { return val; },
                            offsetY: -10,
                            style: { fontSize: '13px', fontWeight: 700, colors: ['#3A3972'] }
                        },
                        xaxis: {
                            categories: labels,
                            labels: { style: { fontSize: '12px' } }
                        },
                        yaxis: {
                            min: 0,
                            title: {
                                text: 'Cantidad de reclamos abiertos',
                                style: { fontSize: '12px' }
                            },
                            labels: {
                                formatter(val) { return Math.round(Number(val) || 0); },
                                style: { fontSize: '12px' }
                            }
                        },
                        legend: { show: false },
                        grid: {
                            padding: { top: 24, right: 8, left: 8, bottom: 0 }
                        },
                        tooltip: {
                            custom({ dataPointIndex }) {
                                const rango = String(labels[dataPointIndex] ?? '')
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;');
                                const val = Number(valores[dataPointIndex]) || 0;
                                const pct = total > 0 ? ((val / total) * 100).toFixed(1) : '0.0';
                                const color = colores[dataPointIndex % colores.length] || '#3A3972';
                                return `<div class="analisis-motivo-tooltip">`
                                    + `<div class="analisis-motivo-tooltip__titulo"><span class="analisis-motivo-tooltip__nombre">${rango}</span></div>`
                                    + `<div class="analisis-motivo-tooltip__valor"><div class="analisis-motivo-tooltip__serie">`
                                    + `<span class="analisis-motivo-tooltip__punto" style="background:${color}"></span>`
                                    + `<span><strong>${val} abiertos</strong> (${pct}% del backlog)</span>`
                                    + `</div></div></div>`;
                            }
                        }
                    };

                    try {
                        this.chartAntiguedad = new ApexCharts(elemento, options);
                        this.chartAntiguedad.render();
                    } catch (e) {
                        console.error('Error al crear gráfico antigüedad:', e);
                        elemento.innerHTML = '<div class="analisis-preview-empty">Error al dibujar el gráfico</div>';
                    }
                },

                exportarGraficoAntiguedad() {
                    if (!this.chartAntiguedad) {
                        alert('El gráfico no está cargado.');
                        return;
                    }
                    this.chartAntiguedad.dataURI({ scale: 2 }).then(result => {
                        const link = document.createElement('a');
                        link.download = 'antiguedad-abiertos-' + new Date().toISOString().slice(0, 10) + '.png';
                        link.href = result.imgURI || result;
                        link.click();
                    });
                },

                // ==================== MAPA DE CALOR (solo Análisis) ====================
                paramsMapaCalor() {
                        const params = new URLSearchParams();
                    if (this.filtrosMapaCalor.fechaDesde) params.append('fecha_desde', this.filtrosMapaCalor.fechaDesde);
                    if (this.filtrosMapaCalor.fechaHasta) params.append('fecha_hasta', this.filtrosMapaCalor.fechaHasta);
                    if (this.filtrosMapaCalor.estado) params.append('estado', this.filtrosMapaCalor.estado);
                    if (this.filtrosMapaCalor.prioridad) params.append('prioridad', this.filtrosMapaCalor.prioridad);
                    if (this.filtrosMapaCalor.motivo) params.append('motivo', this.filtrosMapaCalor.motivo);
                    return params;
                },

                tokenMapboxCalor() {
                    return 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ejJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw';
                },

                geojsonMapaCalor(puntos) {
                    return {
                        type: 'FeatureCollection',
                        features: (puntos || []).map(p => ({
                            type: 'Feature',
                            properties: { weight: Math.max(1, Number(p.cantidad) || 1) },
                            geometry: {
                                type: 'Point',
                                coordinates: [Number(p.lng), Number(p.lat)]
                            }
                        }))
                    };
                },

                agregarCapaHeatmapMapbox(map) {
                    if (!map.getLayer('analisis-calor-heat')) {
                        map.addLayer({
                            id: 'analisis-calor-heat',
                            type: 'heatmap',
                            source: 'analisis-calor',
                            maxzoom: 16,
                            paint: {
                                'heatmap-weight': [
                                    'interpolate', ['linear'], ['get', 'weight'],
                                    1, 0.3,
                                    10, 1
                                ],
                                'heatmap-intensity': [
                                    'interpolate', ['linear'], ['zoom'],
                                    11, 0.7,
                                    15, 1.4
                                ],
                                'heatmap-radius': [
                                    'interpolate', ['linear'], ['zoom'],
                                    11, 18,
                                    15, 36
                                ],
                                'heatmap-opacity': 0.75,
                                'heatmap-color': [
                                    'interpolate', ['linear'], ['heatmap-density'],
                                    0, 'rgba(0, 0, 255, 0)',
                                    0.1, 'royalblue',
                                    0.3, 'cyan',
                                    0.5, 'lime',
                                    0.7, 'yellow',
                                    1, 'red'
                                ]
                            }
                        });
                    }
                },

                destruirMapaCalorMapbox(ref) {
                    if (this[ref]) {
                        try { this[ref].remove(); } catch (e) {}
                        this[ref] = null;
                    }
                },

                centroMapaCalor() {
                    const c = this.datosMapaCalor.centro || {};
                    return {
                        lat: Number(c.lat) || -31.427,
                        lng: Number(c.lng) || -62.082,
                        zoom: Number(c.zoom) || 13
                    };
                },

                async cargarPreviewMapaCalor() {
                    try {
                        const params = this.paramsMapaCalor();
                        const response = await axios.get(`${BASE_URL}api/analisis/mapa-calor-zonas?${params.toString()}`);
                        const data = response.data || {};
                        if (!this.debeRedibujarAnalisis('mapaCalor', data, this.mapaCalorPreviewMapbox)) {
                            return;
                        }
                        this.datosMapaCalor = Object.assign({}, this.datosMapaCalor, data);
                        this.$nextTick(() => {
                            setTimeout(() => this.renderPreviewMapaCalorMapbox(), 80);
                        });
                    } catch (error) {
                        console.error('Error al cargar preview mapa calor:', error);
                    }
                },

                async cargarGraficoMapaCalor() {
                    try {
                        const params = this.paramsMapaCalor();
                        const response = await axios.get(`${BASE_URL}api/analisis/mapa-calor-zonas?${params.toString()}`);
                        const data = response.data || {};
                        if (!this.debeRedibujarAnalisis('mapaCalorModal', data, this.mapaCalorMapbox)) {
                            return;
                        }
                        this.datosMapaCalor = Object.assign({}, this.datosMapaCalor, data);
                        this.$nextTick(() => {
                            setTimeout(() => this.renderMapaCalorModal(), 50);
                        });
                    } catch (error) {
                        console.error('Error al cargar mapa calor:', error);
                    }
                },

                setFiltroRapidoMapaCalor(tipo) {
                    const rango = this.rangoPeriodo(tipo);
                    this.filtrosMapaCalor.fechaDesde = rango.desde;
                    this.filtrosMapaCalor.fechaHasta = rango.hasta;
                    this.cargarGraficoMapaCalor();
                },

                async renderPreviewMapaCalorMapbox() {
                    const el = document.getElementById('previewMapaCalor');
                    if (!el || typeof mapboxgl === 'undefined') {
                        if (el) el.innerHTML = '<div class="analisis-preview-empty">Mapa no disponible</div>';
                        return;
                    }

                    const centro = this.centroMapaCalor();
                    const puntos = this.datosMapaCalor.datos || [];
                    mapboxgl.accessToken = this.tokenMapboxCalor();

                    this.destruirMapaCalorMapbox('mapaCalorPreviewMapbox');
                    el.innerHTML = '';

                    this.mapaCalorPreviewMapbox = new mapboxgl.Map({
                        container: el,
                        style: 'mapbox://styles/mapbox/streets-v12',
                        center: [centro.lng, centro.lat],
                        zoom: centro.zoom,
                        interactive: false,
                        attributionControl: false
                    });

                    this.mapaCalorPreviewMapbox.on('load', () => {
                        this.mapaCalorPreviewMapbox.addSource('analisis-calor', {
                            type: 'geojson',
                            data: this.geojsonMapaCalor(puntos)
                        });
                        this.agregarCapaHeatmapMapbox(this.mapaCalorPreviewMapbox);
                        this.mapaCalorPreviewMapbox.resize();
                    });
                },

                async renderMapaCalorModal() {
                    await this.renderMapaCalorMapbox();
                },

                exportarMapaCalor() {
                    if (!this.mapaCalorMapbox) {
                        alert('El mapa no está cargado.');
                        return;
                    }
                    try {
                        const canvas = this.mapaCalorMapbox.getCanvas();
                        const link = document.createElement('a');
                        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
                        link.download = 'mapa-calor-zonas-' + timestamp + '.png';
                        link.href = canvas.toDataURL('image/png');
                        document.body.appendChild(link);
                        link.click();
                        setTimeout(() => {
                            document.body.removeChild(link);
                            if (link.href && link.href.startsWith('data:')) {
                                link.href = '';
                            }
                        }, 100);
                    } catch (error) {
                        console.error('Error al exportar mapa de calor:', error);
                        alert('Error al exportar el mapa. Por favor, intente nuevamente.');
                    }
                },

                async renderMapaCalorMapbox() {
                    const el = document.getElementById('mapaCalorMapbox');
                    if (!el || typeof mapboxgl === 'undefined') {
                        if (el) el.innerHTML = '<div class="analisis-preview-empty">Mapbox no disponible</div>';
                        return;
                    }

                    const centro = this.centroMapaCalor();
                    const puntos = this.datosMapaCalor.datos || [];
                    mapboxgl.accessToken = this.tokenMapboxCalor();

                    this.destruirMapaCalorMapbox('mapaCalorMapbox');

                    this.mapaCalorMapbox = new mapboxgl.Map({
                        container: el,
                        style: 'mapbox://styles/mapbox/streets-v12',
                        center: [centro.lng, centro.lat],
                        zoom: centro.zoom,
                        attributionControl: false,
                        preserveDrawingBuffer: true
                    });

                    this.mapaCalorMapbox.addControl(new mapboxgl.NavigationControl(), 'top-right');

                    this.mapaCalorMapbox.on('load', () => {
                        this.mapaCalorMapbox.addSource('analisis-calor', {
                            type: 'geojson',
                            data: this.geojsonMapaCalor(puntos)
                        });
                        this.agregarCapaHeatmapMapbox(this.mapaCalorMapbox);
                        this.mapaCalorMapbox.resize();
                    });
                },

                // Filtros rápidos para Tiempo Promedio
                setFiltroRapidoTiempoPromedio(tipo) {
                    const rango = this.rangoPeriodo(tipo);
                    this.filtrosTiempo.fechaDesde = rango.desde;
                    this.filtrosTiempo.fechaHasta = rango.hasta;
                    this.cargarGraficoTiempoPromedio();
                },

                // Filtros rápidos para Evolución Tiempo
                setFiltroRapidoEvolucionTiempo(tipo) {
                    const rango = this.rangoPeriodo(tipo);
                    this.filtrosEvolucionTiempo.fechaDesde = rango.desde;
                    this.filtrosEvolucionTiempo.fechaHasta = rango.hasta;
                    this.cargarGraficoEvolucionTiempo();
                },

                // Filtros rápidos para Consumo Materiales
                setFiltroRapidoConsumoMateriales(tipo) {
                    const rango = this.rangoPeriodo(tipo);
                    switch (tipo) {
                        case 'hoy':
                        case '7dias':
                        case '30dias':
                        case 'mes':
                            this.filtrosConsumoMateriales.granularidad = 'diario';
                            break;
                        case 'año':
                            this.filtrosConsumoMateriales.granularidad = 'mensual';
                            break;
                        default:
                            this.filtrosConsumoMateriales.granularidad = 'semanal';
                            break;
                    }
                    this.filtrosConsumoMateriales.fechaDesde = rango.desde;
                    this.filtrosConsumoMateriales.fechaHasta = rango.hasta;
                    this.cargarGraficoConsumoMateriales();
                }
            }
        });

        // Montar la aplicación Vue
        app.mount('#app');
    }

    // Verificar si ApexCharts ya está disponible
    if (typeof ApexCharts !== 'undefined') {
        initApp();
    } else {
        // Esperar a que se cargue ApexCharts
        let intentos = 0;
        const maxIntentos = 50;
        const verificarApexCharts = setInterval(() => {
            intentos++;
            if (typeof ApexCharts !== 'undefined') {
                clearInterval(verificarApexCharts);
                initApp();
            } else if (intentos >= maxIntentos) {
                clearInterval(verificarApexCharts);
                console.error('ApexCharts no se pudo cargar después de varios intentos');
            }
        }, 100);
    }
})();
