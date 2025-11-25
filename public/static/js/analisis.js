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
                        total_pendientes: 0,
                        total_en_ejecucion: 0,
                        total_completados: 0,
                        total: 0,
                        tasa_resolucion: 0,
                        tiempo_promedio_horas: 0,
                        tiempo_promedio_dias: 0
                    },
                    kpiFiltros: {
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
                        prioridad: ''
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
                        motivo: 'Todos'
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

                    // Gráfico de Evolución Alta Prioridad
                    chartAltaPrioridad: null,
                    previewChartAltaPrioridad: null,
                    datosAltaPrioridad: {
                        labels: [],
                        series: [],
                        meta: 0,
                        filtros_aplicados: {}
                    },
                    filtrosAltaPrioridad: {
                        fechaDesde: '',
                        fechaHasta: '',
                        granularidad: 'diario'
                    },

                    // Gráfico de Consumo de Materiales
                    chartConsumoMateriales: null,
                    previewChartConsumoMateriales: null,
                    datosConsumoMateriales: {
                        labels: [],
                        series: [],
                        materiales_disponibles: [],
                        filtros_aplicados: {}
                    },
                    filtrosConsumoMateriales: {
                        fechaDesde: '',
                        fechaHasta: '',
                        material: 'Todos'
                    },

                    // Gráfico de Reclamos Cerrados vs Abiertos
                    chartCerradosAbiertos: null,
                    previewChartCerradosAbiertos: null,
                    datosCerradosAbiertos: {
                        labels: [],
                        series: [],
                        tasas: [],
                        filtros_aplicados: {}
                    },
                    filtrosCerradosAbiertos: {
                        fechaDesde: '',
                        fechaHasta: '',
                        granularidad: 'mensual'
                    },

                    // Gráfico de Tasa de Cierre
                    chartTasaCierre: null,
                    previewChartTasaCierre: null,
                    datosTasaCierre: {
                        labels: [],
                        series: [],
                        meta: 95,
                        filtros_aplicados: {}
                    },
                    filtrosTasaCierre: {
                        fechaDesde: '',
                        fechaHasta: '',
                        granularidad: 'semanal'
                    }
                };
            },
            mounted() {
                this.inicializarFiltrosRapidos();
                this.inicializarFiltrosKpi();
                this.cargarKpiResumen();
                this.$nextTick(() => {
                    this.cargarMotivosDisponibles();
                    this.cargarPreviewEstado();
                    this.cargarPreviewMotivo();
                    this.cargarPreviewEvolucion();
                    this.cargarPreviewTiempoPromedio();
                    this.cargarPreviewEvolucionTiempo();
                    this.cargarPreviewAltaPrioridad();
                    this.cargarPreviewConsumoMateriales();
                    this.cargarPreviewCerradosAbiertos();
                    this.cargarPreviewTasaCierre();
                });
            },
            methods: {
                /**
                 * Inicializa los filtros rápidos con valores por defecto
                 */
                inicializarFiltrosRapidos() {
                    // Por defecto: fecha desde = hoy, fecha hasta = mañana
                    const fechaDesde = new Date();
                    const fechaHasta = new Date();
                    fechaHasta.setDate(fechaHasta.getDate() + 1);

                    const desde = fechaDesde.toISOString().split('T')[0];
                    const hasta = fechaHasta.toISOString().split('T')[0];

                    this.filtrosEstado.fechaDesde = desde;
                    this.filtrosEstado.fechaHasta = hasta;
                    this.filtrosMotivo.fechaDesde = desde;
                    this.filtrosMotivo.fechaHasta = hasta;
                    this.filtrosEvolucion.fechaDesde = desde;
                    this.filtrosEvolucion.fechaHasta = hasta;
                    this.filtrosTiempo.fechaDesde = desde;
                    this.filtrosTiempo.fechaHasta = hasta;
                    this.filtrosEvolucionTiempo.fechaDesde = desde;
                    this.filtrosEvolucionTiempo.fechaHasta = hasta;
                    this.filtrosConsumoMateriales.fechaDesde = desde;
                    this.filtrosConsumoMateriales.fechaHasta = hasta;
                    this.filtrosCerradosAbiertos.fechaDesde = desde;
                    this.filtrosCerradosAbiertos.fechaHasta = hasta;
                    this.filtrosAltaPrioridad.fechaDesde = desde;
                    this.filtrosAltaPrioridad.fechaHasta = hasta;
                    this.filtrosTasaCierre.fechaDesde = desde;
                    this.filtrosTasaCierre.fechaHasta = hasta;
                },

                /**
                 * Inicializa los filtros KPI con valores por defecto (mes actual)
                 */
                inicializarFiltrosKpi() {
                    const fechaDesde = new Date();
                    const fechaHasta = new Date();
                    fechaHasta.setDate(fechaHasta.getDate() + 1);

                    this.kpiFiltros.fechaDesde = fechaDesde.toISOString().split('T')[0];
                    this.kpiFiltros.fechaHasta = fechaHasta.toISOString().split('T')[0];
                },

                /**
                 * Carga los KPIs de resumen
                 */
                async cargarKpiResumen() {
                    try {
                        const params = new URLSearchParams();
                        if (this.kpiFiltros.fechaDesde) params.append('fecha_desde', this.kpiFiltros.fechaDesde);
                        if (this.kpiFiltros.fechaHasta) params.append('fecha_hasta', this.kpiFiltros.fechaHasta);

                        const response = await axios.get(`${BASE_URL}api/analisis/kpi-resumen?${params.toString()}`);
                        this.kpiResumen = response.data;
                    } catch (error) {
                        console.error('Error al cargar KPIs de resumen:', error);
                    }
                },

                /**
                 * Establece filtros rápidos para los KPIs
                 */
                setFiltroRapidoKpi(tipo) {
                    const fechaHasta = new Date();
                    const fechaDesde = new Date();

                    switch(tipo) {
                        case 'hoy':
                            // Hoy: desde inicio del día hasta fin del día
                            fechaDesde.setHours(0, 0, 0, 0);
                            fechaHasta.setHours(23, 59, 59, 999);
                            break;
                        case 'semana':
                            // Últimos 7 días
                            fechaDesde.setDate(fechaDesde.getDate() - 7);
                            fechaDesde.setHours(0, 0, 0, 0);
                            fechaHasta.setHours(23, 59, 59, 999);
                            break;
                        case 'mes':
                            // Mes actual: desde el primer día del mes
                            fechaDesde.setDate(1);
                            fechaDesde.setHours(0, 0, 0, 0);
                            fechaHasta.setHours(23, 59, 59, 999);
                            break;
                        case 'año':
                            // Año actual: desde el primer día del año
                            fechaDesde.setMonth(0, 1);
                            fechaDesde.setHours(0, 0, 0, 0);
                            fechaHasta.setHours(23, 59, 59, 999);
                            break;
                    }

                    this.kpiFiltros.fechaDesde = fechaDesde.toISOString().split('T')[0];
                    this.kpiFiltros.fechaHasta = fechaHasta.toISOString().split('T')[0];
                    this.cargarKpiResumen();
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
                        this.datosEstado = response.data;

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
                        if (this.filtrosMotivo.prioridad) params.append('prioridad', this.filtrosMotivo.prioridad);

                        const response = await axios.get(`${BASE_URL}api/analisis/reclamos-por-motivo?${params.toString()}`);
                        this.datosMotivo = response.data;

                        // Limitar a los primeros 5 para el preview
                        const datosLimitados = this.datosMotivo.datos.slice(0, 5);
                        this.$nextTick(() => {
                            this.crearPreviewGraficoBarras('previewChartMotivo', datosLimitados);
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
                        this.datosEstado = response.data;

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
                        const params = new URLSearchParams();
                        if (this.filtrosMotivo.fechaDesde) params.append('fecha_desde', this.filtrosMotivo.fechaDesde);
                        if (this.filtrosMotivo.fechaHasta) params.append('fecha_hasta', this.filtrosMotivo.fechaHasta);
                        if (this.filtrosMotivo.estado) params.append('estado', this.filtrosMotivo.estado);
                        if (this.filtrosMotivo.prioridad) params.append('prioridad', this.filtrosMotivo.prioridad);

                        const response = await axios.get(`${BASE_URL}api/analisis/reclamos-por-motivo?${params.toString()}`);
                        this.datosMotivo = response.data;

                        this.$nextTick(() => {
                            this.crearGraficoBarras('chartMotivo', this.datosMotivo.datos);
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

                    const options = {
                        series: datosFiltrados.map(d => d.valor),
                        chart: {
                            type: 'donut',
                            width: '100%',
                            height: '100%',
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
                            enabled: false
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '70%'
                                }
                            }
                        },
                        tooltip: {
                            y: {
                                formatter: function(val, { seriesIndex, w }) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                    return val + ' (' + percentage + '%)';
                                }
                            }
                        }
                    };

                    const chart = new ApexCharts(elemento, options);
                    chart.render();

                    if (elementId === 'previewChartEstado') {
                        this.previewChartEstado = chart;
                    }
                },

                /**
                 * Crea un preview de gráfico de barras
                 */
                crearPreviewGraficoBarras(elementId, datos) {
                    const elemento = document.getElementById(elementId);
                    if (!elemento) return;

                    // Destruir gráfico anterior si existe
                    if (this.previewChartMotivo && elementId === 'previewChartMotivo') {
                        this.previewChartMotivo.destroy();
                    }

                    const options = {
                        series: [{
                            name: 'Cantidad de Reclamos',
                            data: datos.map(d => d.valor)
                        }],
                        chart: {
                            type: 'bar',
                            width: '100%',
                            height: '100%',
                            horizontal: true,
                            toolbar: {
                                show: false
                            }
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                barHeight: '60%'
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return val;
                            }
                        },
                        xaxis: {
                            categories: datos.map(d => d.label.length > 25 ? d.label.substring(0, 25) + '...' : d.label)
                        },
                        colors: datos.map(d => d.color),
                        tooltip: {
                            y: {
                                formatter: function(val) {
                                    return val + ' reclamos';
                                }
                            }
                        }
                    };

                    const chart = new ApexCharts(elemento, options);
                    chart.render();

                    if (elementId === 'previewChartMotivo') {
                        this.previewChartMotivo = chart;
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
                                show: true,
                                tools: {
                                    download: true
                                }
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
                            formatter: function(val, opts) {
                                const label = opts.w.globals.labels[opts.seriesIndex];
                                // Calcular el porcentaje correctamente: obtener el valor absoluto y el total
                                // En ApexCharts pie charts, opts.w.globals.series contiene los valores absolutos
                                const valorAbsoluto = opts.w.globals.series[opts.seriesIndex];
                                const total = opts.w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((valorAbsoluto / total) * 100).toFixed(1) : 0;
                                return label + ': ' + percentage + '%';
                            },
                            style: {
                                fontSize: '12px',
                                fontWeight: 'bold',
                                colors: ['#fff']
                            }
                        },
                        tooltip: {
                            y: {
                                formatter: function(val, opts) {
                                    // En ApexCharts para pie charts, 'val' es el valor absoluto
                                    // Calculamos el porcentaje correctamente
                                    const total = opts.w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                    return val + ' reclamos (' + percentage + '%)';
                                }
                            }
                        },
                        title: {
                            text: 'Distribución de Reclamos por Estado',
                            align: 'center',
                            style: {
                                fontSize: '18px',
                                fontWeight: 'bold',
                                color: '#333'
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
                 * Crea el gráfico completo de barras
                 */
                crearGraficoBarras(elementId, datos) {
                    const elemento = document.getElementById(elementId);
                    if (!elemento) return;

                    // Destruir gráfico anterior si existe
                    if (this.chartMotivo) {
                        this.chartMotivo.destroy();
                    }

                    const options = {
                        series: [{
                            name: 'Cantidad de Reclamos',
                            data: datos.map(d => d.valor)
                        }],
                        chart: {
                            type: 'bar',
                            width: '100%',
                            height: 500,
                            horizontal: true,
                            toolbar: {
                                show: true,
                                tools: {
                                    download: true
                                }
                            }
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                barHeight: '70%',
                                dataLabels: {
                                    position: 'bottom'
                                }
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return val;
                            },
                            style: {
                                fontSize: '12px',
                                fontWeight: 'bold',
                                colors: ['#fff']
                            }
                        },
                        xaxis: {
                            categories: datos.map(d => d.label),
                            labels: {
                                style: {
                                    fontSize: '12px'
                                },
                                maxHeight: 120
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    fontSize: '12px'
                                }
                            }
                        },
                        colors: datos.map(d => d.color),
                        tooltip: {
                            y: {
                                formatter: function(val, { seriesIndex, w }) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                    return val + ' reclamos (' + percentage + '%)';
                                }
                            }
                        },
                        title: {
                            text: 'Reclamos por Motivo',
                            align: 'center',
                            style: {
                                fontSize: '18px',
                                fontWeight: 'bold',
                                color: '#333'
                            }
                        }
                    };

                    this.chartMotivo = new ApexCharts(elemento, options);
                    this.chartMotivo.render();
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
                        this.datosEvolucion = response.data;

                        this.$nextTick(() => {
                            this.crearPreviewGraficoLineas('previewChartEvolucion', response.data);
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
                        this.datosEvolucion = response.data;

                        this.$nextTick(() => {
                            this.crearGraficoLineas('chartEvolucion', response.data);
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
                            categories: datos.labels || [],
                            labels: {
                                rotate: -45,
                                style: {
                                    fontSize: '10px'
                                }
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
                            shared: true
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

                    const options = {
                        series: seriesFormateadas,
                        chart: {
                            type: 'line',
                            width: '100%',
                            height: 500,
                            toolbar: {
                                show: true,
                                tools: {
                                    download: true
                                }
                            },
                            zoom: {
                                enabled: true
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
                            categories: datos.labels || [],
                            labels: {
                                rotate: -45,
                                style: {
                                    fontSize: '12px'
                                }
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
                            shared: true
                        },
                        colors: colores,
                        title: {
                            text: 'Evolución Temporal de Reclamos',
                            align: 'center',
                            style: {
                                fontSize: '18px',
                                fontWeight: 'bold',
                                color: '#333'
                            }
                        }
                    };

                    this.chartEvolucion = new ApexCharts(elemento, options);
                    this.chartEvolucion.render();
                },

                /**
                 * Abre el modal del gráfico seleccionado
                 */
                abrirModalGrafico(tipo) {
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
                    } else if (tipo === 'altaPrioridad') {
                        const modal = new bootstrap.Modal(document.getElementById('modalGraficoAltaPrioridad'));
                        modal.show();
                        setTimeout(() => this.cargarGraficoAltaPrioridad(), 300);
                    } else if (tipo === 'consumoMateriales') {
                        const modal = new bootstrap.Modal(document.getElementById('modalGraficoConsumoMateriales'));
                        modal.show();
                        setTimeout(() => this.cargarGraficoConsumoMateriales(), 300);
                    } else if (tipo === 'cerradosAbiertos') {
                        const modal = new bootstrap.Modal(document.getElementById('modalGraficoCerradosAbiertos'));
                        modal.show();
                        setTimeout(() => this.cargarGraficoCerradosAbiertos(), 300);
                    } else if (tipo === 'tasaCierre') {
                        const modal = new bootstrap.Modal(document.getElementById('modalGraficoTasaCierre'));
                        modal.show();
                        setTimeout(() => this.cargarGraficoTasaCierre(), 300);
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
                    const fechaHasta = new Date();
                    const fechaDesde = new Date();

                    switch(tipo) {
                        case '7dias':
                            fechaDesde.setDate(fechaDesde.getDate() - 7);
                            break;
                        case '30dias':
                            fechaDesde.setDate(fechaDesde.getDate() - 30);
                            break;
                        case 'mes':
                            fechaDesde.setDate(1);
                            break;
                        case 'año':
                            fechaDesde.setMonth(0, 1);
                            break;
                    }

                    this.filtrosEstado.fechaDesde = fechaDesde.toISOString().split('T')[0];
                    this.filtrosEstado.fechaHasta = fechaHasta.toISOString().split('T')[0];
                    this.cargarGraficoEstado();
                },

                /**
                 * Establece filtros rápidos para el gráfico de motivo
                 */
                setFiltroRapidoMotivo(tipo) {
                    const fechaHasta = new Date();
                    const fechaDesde = new Date();

                    switch(tipo) {
                        case '7dias':
                            fechaDesde.setDate(fechaDesde.getDate() - 7);
                            break;
                        case '30dias':
                            fechaDesde.setDate(fechaDesde.getDate() - 30);
                            break;
                        case 'mes':
                            fechaDesde.setDate(1);
                            break;
                        case 'año':
                            fechaDesde.setMonth(0, 1);
                            break;
                    }

                    this.filtrosMotivo.fechaDesde = fechaDesde.toISOString().split('T')[0];
                    this.filtrosMotivo.fechaHasta = fechaHasta.toISOString().split('T')[0];
                    this.cargarGraficoMotivo();
                },

                /**
                 * Establece filtros rápidos para el gráfico de evolución
                 */
                setFiltroRapidoEvolucion(tipo) {
                    const fechaHasta = new Date();
                    const fechaDesde = new Date();

                    switch(tipo) {
                        case '7dias':
                            fechaDesde.setDate(fechaDesde.getDate() - 7);
                            this.filtrosEvolucion.granularidad = 'diario';
                            this.filtrosEvolucion.periodo = '7dias';
                            break;
                        case '30dias':
                            fechaDesde.setDate(fechaDesde.getDate() - 30);
                            this.filtrosEvolucion.granularidad = 'diario';
                            this.filtrosEvolucion.periodo = '30dias';
                            break;
                        case '3meses':
                            fechaDesde.setMonth(fechaDesde.getMonth() - 3);
                            this.filtrosEvolucion.granularidad = 'semanal';
                            this.filtrosEvolucion.periodo = '3meses';
                            break;
                        case '6meses':
                            fechaDesde.setMonth(fechaDesde.getMonth() - 6);
                            this.filtrosEvolucion.granularidad = 'semanal';
                            this.filtrosEvolucion.periodo = '6meses';
                            break;
                        case 'año':
                            fechaDesde.setMonth(0, 1);
                            this.filtrosEvolucion.granularidad = 'mensual';
                            this.filtrosEvolucion.periodo = 'año';
                            break;
                    }

                    this.filtrosEvolucion.fechaDesde = fechaDesde.toISOString().split('T')[0];
                    this.filtrosEvolucion.fechaHasta = fechaHasta.toISOString().split('T')[0];
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
                async cargarPreviewTiempoPromedio() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosTiempo.fechaDesde) params.append('fecha_desde', this.filtrosTiempo.fechaDesde);
                        if (this.filtrosTiempo.fechaHasta) params.append('fecha_hasta', this.filtrosTiempo.fechaHasta);
                        if (this.filtrosTiempo.motivo && this.filtrosTiempo.motivo !== 'Todos') {
                            params.append('motivo', this.filtrosTiempo.motivo);
                        }

                        const response = await axios.get(`${BASE_URL}api/analisis/tiempo-promedio-por-motivo?${params.toString()}`);
                        this.datosTiempo = response.data;
                        this.$nextTick(() => {
                            this.crearPreviewGraficoTiempoPromedio();
                        });
                    } catch (error) {
                        console.error('Error al cargar preview de tiempo promedio:', error);
                    }
                },

                /**
                 * Carga datos para el gráfico completo de tiempo promedio por motivo
                 */
                async cargarGraficoTiempoPromedio() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosTiempo.fechaDesde) params.append('fecha_desde', this.filtrosTiempo.fechaDesde);
                        if (this.filtrosTiempo.fechaHasta) params.append('fecha_hasta', this.filtrosTiempo.fechaHasta);
                        if (this.filtrosTiempo.motivo && this.filtrosTiempo.motivo !== 'Todos') {
                            params.append('motivo', this.filtrosTiempo.motivo);
                        }

                        const response = await axios.get(`${BASE_URL}api/analisis/tiempo-promedio-por-motivo?${params.toString()}`);
                        this.datosTiempo = response.data;
                        // Crear el gráfico directamente, ya que el timing se maneja en crearGraficoTiempoPromedio
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
                 * Crea el preview del gráfico de tiempo promedio por motivo
                 */
                crearPreviewGraficoTiempoPromedio() {
                    // Verificar que ApexCharts esté disponible
                    if (typeof ApexCharts === 'undefined') {
                        console.error('ApexCharts no está disponible');
                        return;
                    }

                    const elemento = document.getElementById('previewChartTiempoPromedio');
                    if (!elemento) {
                        console.error('Elemento previewChartTiempoPromedio no encontrado');
                        return;
                    }

                    if (!this.datosTiempo.datos || this.datosTiempo.datos.length === 0) {
                        elemento.innerHTML = '<div class="text-center p-4 text-muted">No hay datos para mostrar</div>';
                        return;
                    }

                    // Destruir gráfico anterior si existe
                    if (this.previewChartTiempoPromedio) {
                        try {
                            this.previewChartTiempoPromedio.destroy();
                        } catch (e) {
                            console.log('Error al destruir preview anterior:', e);
                        }
                        this.previewChartTiempoPromedio = null;
                    }

                    // Limpiar contenido
                    elemento.innerHTML = '';

                    const datosOrdenados = [...this.datosTiempo.datos].sort((a, b) => b.valor - a.valor).slice(0, 5);
                    
                    // Convertir valores a horas para el gráfico
                    const valoresGrafico = datosOrdenados.map(d => {
                        if (d.valor_horas && d.valor_horas > 0) {
                            return parseFloat(d.valor_horas.toFixed(2));
                        }
                        return parseFloat((d.valor / 60).toFixed(2)); // Convertir minutos a horas
                    });

                    const labels = datosOrdenados.map(d => d.label.length > 25 ? d.label.substring(0, 25) + '...' : d.label);
                    const colores = datosOrdenados.map(d => d.color);
                    
                    // Usar ApexCharts para el preview también
                    const options = {
                        series: [{
                            name: 'Tiempo Promedio (horas)',
                            data: valoresGrafico
                        }],
                        chart: {
                            type: 'bar',
                            width: '100%',
                            height: '100%',
                            horizontal: true,
                            toolbar: {
                                show: false
                            }
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                barHeight: '60%'
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return val.toFixed(1) + ' h';
                            },
                            style: {
                                fontSize: '10px',
                                fontWeight: 'bold',
                                colors: ['#fff']
                            }
                        },
                        xaxis: {
                            categories: labels,
                            labels: {
                                style: {
                                    fontSize: '10px'
                                },
                                formatter: function(val) {
                                    return parseFloat(val).toFixed(1) + ' h';
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    fontSize: '10px'
                                }
                            }
                        },
                        colors: colores,
                        tooltip: {
                            y: {
                                formatter: function(val, { dataPointIndex, w }) {
                                    const index = dataPointIndex;
                                    const dato = datosOrdenados[index];
                                    const tiempo = dato.valor_horas && dato.valor_horas > 0 
                                        ? `${dato.valor_horas.toFixed(2)} horas` 
                                        : `${(dato.valor / 60).toFixed(2)} horas`;
                                    return `${tiempo} (${dato.cantidad_registros} registros)`;
                                }
                            }
                        }
                    };

                    // Renderizar cuando el elemento esté disponible
                    this.$nextTick(() => {
                        setTimeout(() => {
                            try {
                                this.previewChartTiempoPromedio = new ApexCharts(elemento, options);
                                this.previewChartTiempoPromedio.render();
                            } catch (error) {
                                console.error('Error al crear preview de tiempo promedio:', error);
                                elemento.innerHTML = '<div class="text-center p-4 text-danger">Error al crear el gráfico.</div>';
                            }
                        }, 100);
                    });
                },

                /**
                 * Crea el gráfico completo de tiempo promedio por motivo
                 */
                crearGraficoTiempoPromedio() {
                    // Verificar que ApexCharts esté disponible
                    if (typeof ApexCharts === 'undefined') {
                        console.error('ApexCharts no está disponible');
                        const elemento = document.getElementById('chartTiempoPromedio');
                        if (elemento) {
                            elemento.innerHTML = '<div class="alert alert-danger">ApexCharts no está disponible. Por favor, recargue la página.</div>';
                        }
                        return;
                    }

                    const elemento = document.getElementById('chartTiempoPromedio');
                    if (!elemento) {
                        console.error('Elemento chartTiempoPromedio no encontrado');
                        return;
                    }

                    if (!this.datosTiempo.datos || this.datosTiempo.datos.length === 0) {
                        elemento.innerHTML = '<div class="text-center p-4 text-muted">No hay datos para mostrar</div>';
                        return;
                    }

                    // Destruir gráfico anterior si existe
                    if (this.chartTiempoPromedio) {
                        try {
                            this.chartTiempoPromedio.destroy();
                        } catch (e) {
                            console.log('Error al destruir gráfico anterior:', e);
                        }
                        this.chartTiempoPromedio = null;
                    }

                    // Limpiar contenido
                    elemento.innerHTML = '';

                    const datosOrdenados = [...this.datosTiempo.datos].sort((a, b) => b.valor - a.valor);
                    
                    // Convertir valores a horas para el gráfico
                    const valoresGrafico = datosOrdenados.map(d => {
                        if (d.valor_horas && d.valor_horas > 0) {
                            return parseFloat(d.valor_horas.toFixed(2));
                        }
                        // Convertir minutos a horas
                        const horas = d.valor / 60;
                        return parseFloat(horas.toFixed(2));
                    });

                    const labels = datosOrdenados.map(d => d.label);
                    const colores = datosOrdenados.map(d => d.color);
                    
                    // Usar ApexCharts para consistencia
                    const options = {
                        series: [{
                            name: 'Tiempo Promedio (horas)',
                            data: valoresGrafico
                        }],
                        chart: {
                            type: 'bar',
                            width: '100%',
                            height: 500,
                            horizontal: true,
                            toolbar: {
                                show: true,
                                tools: {
                                    download: true
                                }
                            },
                            animations: {
                                enabled: true,
                                easing: 'easeinout',
                                speed: 800
                            }
                        },
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                barHeight: '70%',
                                dataLabels: {
                                    position: 'bottom'
                                }
                            }
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: function(val) {
                                return val.toFixed(1) + ' h';
                            },
                            style: {
                                fontSize: '12px',
                                fontWeight: 'bold',
                                colors: ['#fff']
                            }
                        },
                        xaxis: {
                            categories: labels,
                            title: {
                                text: 'Tiempo Promedio (horas)',
                                style: {
                                    fontSize: '14px'
                                }
                            },
                            labels: {
                                style: {
                                    fontSize: '12px'
                                },
                                formatter: function(val) {
                                    return parseFloat(val).toFixed(1) + ' h';
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    fontSize: '12px'
                                }
                            }
                        },
                        colors: colores,
                        tooltip: {
                            y: {
                                formatter: function(val, { dataPointIndex, w }) {
                                    const index = dataPointIndex;
                                    const dato = datosOrdenados[index];
                                    const tiempo = dato.valor_horas && dato.valor_horas > 0 
                                        ? `${dato.valor_horas.toFixed(2)} horas` 
                                        : `${(dato.valor / 60).toFixed(2)} horas`;
                                    return `${tiempo} (${dato.cantidad_registros} registros)`;
                                }
                            }
                        },
                        title: {
                            text: 'Tiempo Promedio de Resolución por Motivo',
                            align: 'center',
                            style: {
                                fontSize: '18px',
                                fontWeight: 'bold',
                                color: '#333'
                            }
                        }
                    };

                    // Función para renderizar cuando el elemento esté visible y tenga dimensiones
                    const renderizarGrafico = (intentos = 0) => {
                        const maxIntentos = 20;
                        
                        // Verificar que el elemento exista y esté en el DOM
                        if (!elemento || !elemento.isConnected) {
                            if (intentos < maxIntentos) {
                                setTimeout(() => renderizarGrafico(intentos + 1), 100);
                            } else {
                                elemento.innerHTML = '<div class="alert alert-danger">No se pudo encontrar el elemento del gráfico.</div>';
                            }
                            return;
                        }

                        const rect = elemento.getBoundingClientRect();
                        const style = window.getComputedStyle(elemento);
                        
                        // Verificar que el elemento tenga dimensiones válidas y sea visible
                        if ((rect.width === 0 || rect.height === 0) || 
                            style.display === 'none' || 
                            style.visibility === 'hidden' ||
                            style.opacity === '0') {
                            if (intentos < maxIntentos) {
                                setTimeout(() => renderizarGrafico(intentos + 1), 100);
                                return;
                            } else {
                                elemento.innerHTML = '<div class="alert alert-warning">El elemento del gráfico no está visible. Por favor, intente nuevamente.</div>';
                                return;
                            }
                        }

                        try {
                            // Asegurar que el elemento tenga dimensiones mínimas
                            if (elemento.offsetHeight < 100) {
                                elemento.style.minHeight = '500px';
                                elemento.style.height = '500px';
                            }

                            this.chartTiempoPromedio = new ApexCharts(elemento, options);
                            this.chartTiempoPromedio.render().then(() => {
                                console.log('Gráfico de tiempo promedio renderizado correctamente');
                            }).catch((error) => {
                                console.error('Error al renderizar gráfico:', error);
                                elemento.innerHTML = '<div class="alert alert-danger">Error al renderizar el gráfico. Por favor, intente nuevamente.</div>';
                            });
                        } catch (error) {
                            console.error('Error al crear gráfico de tiempo promedio:', error);
                            elemento.innerHTML = '<div class="alert alert-danger">Error al crear el gráfico: ' + (error.message || 'Error desconocido') + '</div>';
                        }
                    };

                    // Iniciar el proceso de renderizado
                    this.$nextTick(() => {
                        renderizarGrafico();
                    });
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
                        this.datosEvolucionTiempo = response.data;
                        this.$nextTick(() => {
                            this.crearPreviewGraficoEvolucionTiempo();
                        });
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
                        this.datosEvolucionTiempo = response.data;
                        this.$nextTick(() => {
                            this.crearGraficoEvolucionTiempo();
                        });
                    } catch (error) {
                        console.error('Error al cargar gráfico de evolución tiempo:', error);
                    }
                },

                /**
                 * Crea el preview del gráfico de evolución del tiempo promedio
                 */
                crearPreviewGraficoEvolucionTiempo() {
                    const elemento = document.getElementById('previewChartEvolucionTiempo');
                    if (!elemento || !this.datosEvolucionTiempo.series || this.datosEvolucionTiempo.series.length === 0) {
                        if (elemento) {
                            elemento.innerHTML = '<div class="text-center p-4 text-muted">No hay datos para mostrar</div>';
                        }
                        return;
                    }

                    if (this.previewChartEvolucionTiempo) {
                        this.previewChartEvolucionTiempo.destroy();
                    }

                    // Formatear series correctamente
                    const seriesFormateadas = this.datosEvolucionTiempo.series.map(s => ({
                        name: s.name,
                        data: s.data.map(d => d / 60) // Convertir minutos a horas
                    }));

                    const options = {
                        series: seriesFormateadas,
                        chart: {
                            type: 'line',
                            height: 250,
                            toolbar: { show: false },
                            zoom: { enabled: false }
                        },
                        stroke: { curve: 'smooth', width: 2 },
                        dataLabels: {
                            enabled: false
                        },
                        xaxis: {
                            categories: this.datosEvolucionTiempo.labels || [],
                            labels: { 
                                rotate: -45, 
                                style: { fontSize: '10px' },
                                maxHeight: 80
                            }
                        },
                        yaxis: {
                            title: { 
                                text: 'Tiempo Promedio (horas)',
                                style: {
                                    fontSize: '11px'
                                }
                            },
                            labels: { 
                                formatter: function(val) { 
                                    return val.toFixed(1) + ' h'; 
                                },
                                style: {
                                    fontSize: '10px'
                                }
                            }
                        },
                        legend: { 
                            position: 'top',
                            fontSize: '11px'
                        },
                        colors: this.datosEvolucionTiempo.series.map(s => s.color),
                        tooltip: {
                            shared: true,
                            y: { 
                                formatter: function(val) { 
                                    return val.toFixed(2) + ' horas'; 
                                } 
                            }
                        }
                    };

                    this.previewChartEvolucionTiempo = new ApexCharts(elemento, options);
                    this.previewChartEvolucionTiempo.render();
                },

                /**
                 * Crea el gráfico completo de evolución del tiempo promedio
                 */
                crearGraficoEvolucionTiempo() {
                    const elemento = document.getElementById('chartEvolucionTiempo');
                    if (!elemento || !this.datosEvolucionTiempo.series || this.datosEvolucionTiempo.series.length === 0) {
                        if (elemento) {
                            elemento.innerHTML = '<div class="text-center p-4 text-muted">No hay datos para mostrar</div>';
                        }
                        return;
                    }

                    if (this.chartEvolucionTiempo) {
                        this.chartEvolucionTiempo.destroy();
                    }

                    // Formatear series correctamente
                    const seriesFormateadas = this.datosEvolucionTiempo.series.map(s => ({
                        name: s.name,
                        data: s.data.map(d => d / 60) // Convertir minutos a horas
                    }));

                    const options = {
                        series: seriesFormateadas,
                        chart: {
                            type: 'line',
                            height: 500,
                            toolbar: { 
                                show: true,
                                tools: {
                                    download: true
                                }
                            },
                            zoom: { enabled: true }
                        },
                        stroke: { curve: 'smooth', width: 3 },
                        dataLabels: {
                            enabled: false
                        },
                        xaxis: {
                            categories: this.datosEvolucionTiempo.labels || [],
                            labels: { 
                                rotate: -45, 
                                style: { fontSize: '12px' },
                                maxHeight: 120
                            }
                        },
                        yaxis: {
                            title: { 
                                text: 'Tiempo Promedio (horas)',
                                style: {
                                    fontSize: '14px'
                                }
                            },
                            labels: { 
                                formatter: function(val) { 
                                    return val.toFixed(1) + ' h'; 
                                },
                                style: {
                                    fontSize: '12px'
                                }
                            }
                        },
                        legend: { 
                            position: 'top',
                            fontSize: '14px'
                        },
                        colors: this.datosEvolucionTiempo.series.map(s => s.color),
                        tooltip: {
                            shared: true,
                            y: { 
                                formatter: function(val) { 
                                    return val.toFixed(2) + ' horas'; 
                                } 
                            }
                        },
                        title: {
                            text: 'Evolución del Tiempo Promedio de Resolución',
                            align: 'center',
                            style: {
                                fontSize: '18px',
                                fontWeight: 'bold',
                                color: '#333'
                            }
                        }
                    };

                    this.chartEvolucionTiempo = new ApexCharts(elemento, options);
                    this.chartEvolucionTiempo.render();
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

                // ==================== GRÁFICO ALTA PRIORIDAD ====================
                async cargarPreviewAltaPrioridad() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosAltaPrioridad.fechaDesde) params.append('fecha_desde', this.filtrosAltaPrioridad.fechaDesde);
                        if (this.filtrosAltaPrioridad.fechaHasta) params.append('fecha_hasta', this.filtrosAltaPrioridad.fechaHasta);
                        params.append('granularidad', this.filtrosAltaPrioridad.granularidad);

                        const response = await axios.get(`${BASE_URL}api/analisis/evolucion-alta-prioridad?${params.toString()}`);
                        this.datosAltaPrioridad = response.data;
                        this.$nextTick(() => this.crearPreviewAltaPrioridad());
                    } catch (error) {
                        console.error('Error al cargar preview alta prioridad:', error);
                    }
                },

                async cargarGraficoAltaPrioridad() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosAltaPrioridad.fechaDesde) params.append('fecha_desde', this.filtrosAltaPrioridad.fechaDesde);
                        if (this.filtrosAltaPrioridad.fechaHasta) params.append('fecha_hasta', this.filtrosAltaPrioridad.fechaHasta);
                        params.append('granularidad', this.filtrosAltaPrioridad.granularidad);

                        const response = await axios.get(`${BASE_URL}api/analisis/evolucion-alta-prioridad?${params.toString()}`);
                        this.datosAltaPrioridad = response.data;
                        this.$nextTick(() => this.crearGraficoAltaPrioridad());
                    } catch (error) {
                        console.error('Error al cargar gráfico alta prioridad:', error);
                    }
                },

                crearPreviewAltaPrioridad() {
                    const elemento = document.getElementById('previewChartAltaPrioridad');
                    if (!elemento) return;

                    if (this.previewChartAltaPrioridad) {
                        this.previewChartAltaPrioridad.destroy();
                    }

                    const options = {
                        series: this.datosAltaPrioridad.series?.map(s => ({ name: s.name, data: s.data })) || [],
                        chart: { type: 'line', height: '100%', toolbar: { show: false }, zoom: { enabled: false } },
                        stroke: { curve: 'smooth', width: 2 },
                        colors: this.datosAltaPrioridad.series?.map(s => s.color) || [],
                        xaxis: { categories: this.datosAltaPrioridad.labels || [], labels: { show: false } },
                        yaxis: { labels: { style: { fontSize: '10px' } } },
                        legend: { show: true, position: 'top', fontSize: '10px' },
                        annotations: {
                            yaxis: [{ y: this.datosAltaPrioridad.meta, borderColor: '#28a745', strokeDashArray: 5,
                                label: { text: 'Meta', style: { fontSize: '10px' } } }]
                        }
                    };

                    this.previewChartAltaPrioridad = new ApexCharts(elemento, options);
                    this.previewChartAltaPrioridad.render();
                },

                crearGraficoAltaPrioridad() {
                    const elemento = document.getElementById('chartAltaPrioridad');
                    if (!elemento) return;

                    if (this.chartAltaPrioridad) this.chartAltaPrioridad.destroy();

                    const options = {
                        series: this.datosAltaPrioridad.series?.map(s => ({ name: s.name, data: s.data })) || [],
                        chart: { type: 'line', height: 450, toolbar: { show: true }, zoom: { enabled: true } },
                        stroke: { curve: 'smooth', width: [3, 2] },
                        colors: this.datosAltaPrioridad.series?.map(s => s.color) || [],
                        xaxis: { categories: this.datosAltaPrioridad.labels || [], labels: { rotate: -45, style: { fontSize: '11px' } } },
                        yaxis: { title: { text: 'Cantidad de Reclamos' }, labels: { style: { fontSize: '12px' } } },
                        legend: { show: true, position: 'top', fontSize: '14px' },
                        annotations: {
                            yaxis: [{ y: this.datosAltaPrioridad.meta, borderColor: '#28a745', strokeDashArray: 5,
                                label: { text: 'Meta Objetivo', style: { fontSize: '12px', background: '#28a745', color: '#fff' } } }]
                        },
                        title: { text: 'Evolución de Reclamos de Alta Prioridad', align: 'center', style: { fontSize: '18px', fontWeight: 'bold' } }
                    };

                    this.chartAltaPrioridad = new ApexCharts(elemento, options);
                    this.chartAltaPrioridad.render();
                },

                exportarGraficoAltaPrioridad() {
                    if (!this.chartAltaPrioridad) { alert('El gráfico no está cargado.'); return; }
                    this.chartAltaPrioridad.dataURI({ scale: 2 }).then(result => {
                        const link = document.createElement('a');
                        link.download = 'evolucion-alta-prioridad-' + new Date().toISOString().slice(0, 10) + '.png';
                        link.href = result.imgURI || result;
                        link.click();
                    });
                },

                // ==================== GRÁFICO CONSUMO MATERIALES ====================
                async cargarPreviewConsumoMateriales() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosConsumoMateriales.fechaDesde) params.append('fecha_desde', this.filtrosConsumoMateriales.fechaDesde);
                        if (this.filtrosConsumoMateriales.fechaHasta) params.append('fecha_hasta', this.filtrosConsumoMateriales.fechaHasta);
                        if (this.filtrosConsumoMateriales.material !== 'Todos') params.append('material', this.filtrosConsumoMateriales.material);

                        const response = await axios.get(`${BASE_URL}api/analisis/consumo-materiales?${params.toString()}`);
                        this.datosConsumoMateriales = response.data;
                        this.$nextTick(() => this.crearPreviewConsumoMateriales());
                    } catch (error) {
                        console.error('Error al cargar preview consumo materiales:', error);
                    }
                },

                async cargarGraficoConsumoMateriales() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosConsumoMateriales.fechaDesde) params.append('fecha_desde', this.filtrosConsumoMateriales.fechaDesde);
                        if (this.filtrosConsumoMateriales.fechaHasta) params.append('fecha_hasta', this.filtrosConsumoMateriales.fechaHasta);
                        if (this.filtrosConsumoMateriales.material !== 'Todos') params.append('material', this.filtrosConsumoMateriales.material);

                        const response = await axios.get(`${BASE_URL}api/analisis/consumo-materiales?${params.toString()}`);
                        this.datosConsumoMateriales = response.data;
                        this.$nextTick(() => this.crearGraficoConsumoMateriales());
                    } catch (error) {
                        console.error('Error al cargar gráfico consumo materiales:', error);
                    }
                },

                crearPreviewConsumoMateriales() {
                    const elemento = document.getElementById('previewChartConsumoMateriales');
                    if (!elemento) return;

                    if (this.previewChartConsumoMateriales) {
                        try { this.previewChartConsumoMateriales.destroy(); } catch(e) {}
                        this.previewChartConsumoMateriales = null;
                    }

                    elemento.innerHTML = '';

                    if (!this.datosConsumoMateriales.series || this.datosConsumoMateriales.series.length === 0) {
                        elemento.innerHTML = '<div class="text-center p-4 text-muted"><i class="bi bi-box-seam" style="font-size: 2rem;"></i><p class="mt-2">No hay datos de materiales</p></div>';
                        return;
                    }

                    const options = {
                        series: this.datosConsumoMateriales.series.map(s => ({ name: s.name, data: s.data })),
                        chart: { type: 'line', height: 220, toolbar: { show: false }, zoom: { enabled: false } },
                        stroke: { curve: 'smooth', width: 2 },
                        colors: this.datosConsumoMateriales.series.map(s => s.color),
                        xaxis: { categories: this.datosConsumoMateriales.labels || [], labels: { show: false } },
                        yaxis: { labels: { style: { fontSize: '10px' } } },
                        legend: { show: true, position: 'top', fontSize: '10px' },
                        dataLabels: { enabled: false }
                    };

                    try {
                        this.previewChartConsumoMateriales = new ApexCharts(elemento, options);
                        this.previewChartConsumoMateriales.render();
                    } catch(e) {
                        console.error('Error al crear preview consumo materiales:', e);
                    }
                },

                crearGraficoConsumoMateriales() {
                    const elemento = document.getElementById('chartConsumoMateriales');
                    if (!elemento) return;

                    if (this.chartConsumoMateriales) {
                        try { this.chartConsumoMateriales.destroy(); } catch(e) {}
                        this.chartConsumoMateriales = null;
                    }

                    elemento.innerHTML = '';

                    if (!this.datosConsumoMateriales.series || this.datosConsumoMateriales.series.length === 0) {
                        elemento.innerHTML = '<div class="text-center p-5 text-muted"><i class="bi bi-box-seam" style="font-size: 3rem;"></i><p class="mt-3">No hay datos de consumo de materiales para el período seleccionado</p><small>Verifique que existan registros en la tabla material_reclamo</small></div>';
                        return;
                    }

                    elemento.style.minHeight = '450px';

                    const options = {
                        series: this.datosConsumoMateriales.series.map(s => ({ name: s.name, data: s.data })),
                        chart: { type: 'line', height: 450, toolbar: { show: true }, zoom: { enabled: true } },
                        stroke: { curve: 'smooth', width: 3 },
                        colors: this.datosConsumoMateriales.series.map(s => s.color),
                        xaxis: { categories: this.datosConsumoMateriales.labels || [], labels: { rotate: -45, style: { fontSize: '11px' } } },
                        yaxis: { title: { text: 'Cantidad Utilizada' }, labels: { style: { fontSize: '12px' } } },
                        legend: { show: true, position: 'top', fontSize: '14px' },
                        dataLabels: { enabled: false },
                        title: { text: 'Consumo de Materiales por Período', align: 'center', style: { fontSize: '18px', fontWeight: 'bold' } }
                    };

                    try {
                        this.chartConsumoMateriales = new ApexCharts(elemento, options);
                        this.chartConsumoMateriales.render();
                    } catch(e) {
                        console.error('Error al crear gráfico consumo materiales:', e);
                        elemento.innerHTML = '<div class="alert alert-danger">Error al crear el gráfico</div>';
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

                // ==================== GRÁFICO CERRADOS VS ABIERTOS ====================
                async cargarPreviewCerradosAbiertos() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosCerradosAbiertos.fechaDesde) params.append('fecha_desde', this.filtrosCerradosAbiertos.fechaDesde);
                        if (this.filtrosCerradosAbiertos.fechaHasta) params.append('fecha_hasta', this.filtrosCerradosAbiertos.fechaHasta);
                        params.append('granularidad', this.filtrosCerradosAbiertos.granularidad);

                        const response = await axios.get(`${BASE_URL}api/analisis/reclamos-cerrados-abiertos?${params.toString()}`);
                        this.datosCerradosAbiertos = response.data;
                        this.$nextTick(() => this.crearPreviewCerradosAbiertos());
                    } catch (error) {
                        console.error('Error al cargar preview cerrados/abiertos:', error);
                    }
                },

                async cargarGraficoCerradosAbiertos() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosCerradosAbiertos.fechaDesde) params.append('fecha_desde', this.filtrosCerradosAbiertos.fechaDesde);
                        if (this.filtrosCerradosAbiertos.fechaHasta) params.append('fecha_hasta', this.filtrosCerradosAbiertos.fechaHasta);
                        params.append('granularidad', this.filtrosCerradosAbiertos.granularidad);

                        const response = await axios.get(`${BASE_URL}api/analisis/reclamos-cerrados-abiertos?${params.toString()}`);
                        this.datosCerradosAbiertos = response.data;
                        this.$nextTick(() => this.crearGraficoCerradosAbiertos());
                    } catch (error) {
                        console.error('Error al cargar gráfico cerrados/abiertos:', error);
                    }
                },

                crearPreviewCerradosAbiertos() {
                    const elemento = document.getElementById('previewChartCerradosAbiertos');
                    if (!elemento) return;

                    if (this.previewChartCerradosAbiertos) this.previewChartCerradosAbiertos.destroy();

                    const options = {
                        series: this.datosCerradosAbiertos.series?.map(s => ({ name: s.name, data: s.data })) || [],
                        chart: { type: 'bar', height: '100%', toolbar: { show: false }, stacked: false },
                        plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
                        colors: this.datosCerradosAbiertos.series?.map(s => s.color) || [],
                        xaxis: { categories: this.datosCerradosAbiertos.labels || [], labels: { show: false } },
                        yaxis: { labels: { style: { fontSize: '10px' } } },
                        legend: { show: true, position: 'top', fontSize: '10px' }
                    };

                    this.previewChartCerradosAbiertos = new ApexCharts(elemento, options);
                    this.previewChartCerradosAbiertos.render();
                },

                crearGraficoCerradosAbiertos() {
                    const elemento = document.getElementById('chartCerradosAbiertos');
                    if (!elemento) return;

                    if (this.chartCerradosAbiertos) this.chartCerradosAbiertos.destroy();

                    const options = {
                        series: this.datosCerradosAbiertos.series?.map(s => ({ name: s.name, data: s.data })) || [],
                        chart: { type: 'bar', height: 450, toolbar: { show: true }, stacked: false },
                        plotOptions: { bar: { horizontal: false, columnWidth: '55%', dataLabels: { position: 'top' } } },
                        dataLabels: { enabled: true, style: { fontSize: '11px' } },
                        colors: this.datosCerradosAbiertos.series?.map(s => s.color) || [],
                        xaxis: { categories: this.datosCerradosAbiertos.labels || [], labels: { rotate: -45, style: { fontSize: '11px' } } },
                        yaxis: { title: { text: 'Cantidad de Reclamos' }, labels: { style: { fontSize: '12px' } } },
                        legend: { show: true, position: 'top', fontSize: '14px' },
                        title: { text: 'Reclamos Cerrados vs Abiertos', align: 'center', style: { fontSize: '18px', fontWeight: 'bold' } }
                    };

                    this.chartCerradosAbiertos = new ApexCharts(elemento, options);
                    this.chartCerradosAbiertos.render();
                },

                exportarGraficoCerradosAbiertos() {
                    if (!this.chartCerradosAbiertos) { alert('El gráfico no está cargado.'); return; }
                    this.chartCerradosAbiertos.dataURI({ scale: 2 }).then(result => {
                        const link = document.createElement('a');
                        link.download = 'reclamos-cerrados-abiertos-' + new Date().toISOString().slice(0, 10) + '.png';
                        link.href = result.imgURI || result;
                        link.click();
                    });
                },

                // ==================== GRÁFICO TASA DE CIERRE ====================
                async cargarPreviewTasaCierre() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosTasaCierre.fechaDesde) params.append('fecha_desde', this.filtrosTasaCierre.fechaDesde);
                        if (this.filtrosTasaCierre.fechaHasta) params.append('fecha_hasta', this.filtrosTasaCierre.fechaHasta);
                        params.append('granularidad', this.filtrosTasaCierre.granularidad);

                        const response = await axios.get(`${BASE_URL}api/analisis/tasa-cierre?${params.toString()}`);
                        this.datosTasaCierre = response.data;
                        this.$nextTick(() => this.crearPreviewTasaCierre());
                    } catch (error) {
                        console.error('Error al cargar preview tasa cierre:', error);
                    }
                },

                async cargarGraficoTasaCierre() {
                    try {
                        const params = new URLSearchParams();
                        if (this.filtrosTasaCierre.fechaDesde) params.append('fecha_desde', this.filtrosTasaCierre.fechaDesde);
                        if (this.filtrosTasaCierre.fechaHasta) params.append('fecha_hasta', this.filtrosTasaCierre.fechaHasta);
                        params.append('granularidad', this.filtrosTasaCierre.granularidad);

                        const response = await axios.get(`${BASE_URL}api/analisis/tasa-cierre?${params.toString()}`);
                        this.datosTasaCierre = response.data;
                        this.$nextTick(() => this.crearGraficoTasaCierre());
                    } catch (error) {
                        console.error('Error al cargar gráfico tasa cierre:', error);
                    }
                },

                crearPreviewTasaCierre() {
                    const elemento = document.getElementById('previewChartTasaCierre');
                    if (!elemento) return;

                    if (this.previewChartTasaCierre) this.previewChartTasaCierre.destroy();

                    const options = {
                        series: this.datosTasaCierre.series?.map(s => ({ name: s.name, data: s.data })) || [],
                        chart: { type: 'line', height: '100%', toolbar: { show: false }, zoom: { enabled: false } },
                        stroke: { curve: 'smooth', width: 2 },
                        colors: this.datosTasaCierre.series?.map(s => s.color) || [],
                        xaxis: { categories: this.datosTasaCierre.labels || [], labels: { show: false } },
                        yaxis: { min: 0, max: 100, labels: { formatter: v => v + '%', style: { fontSize: '10px' } } },
                        legend: { show: false },
                        annotations: {
                            yaxis: [{ y: this.datosTasaCierre.meta, borderColor: '#28a745', strokeDashArray: 5,
                                label: { text: 'Meta 95%', style: { fontSize: '10px' } } }]
                        }
                    };

                    this.previewChartTasaCierre = new ApexCharts(elemento, options);
                    this.previewChartTasaCierre.render();
                },

                crearGraficoTasaCierre() {
                    const elemento = document.getElementById('chartTasaCierre');
                    if (!elemento) return;

                    if (this.chartTasaCierre) this.chartTasaCierre.destroy();

                    const options = {
                        series: this.datosTasaCierre.series?.map(s => ({ name: s.name, data: s.data })) || [],
                        chart: { type: 'line', height: 450, toolbar: { show: true }, zoom: { enabled: true } },
                        stroke: { curve: 'smooth', width: 3 },
                        colors: this.datosTasaCierre.series?.map(s => s.color) || [],
                        xaxis: { categories: this.datosTasaCierre.labels || [], labels: { rotate: -45, style: { fontSize: '11px' } } },
                        yaxis: { min: 0, max: 100, title: { text: 'Tasa de Cierre (%)' }, labels: { formatter: v => v + '%', style: { fontSize: '12px' } } },
                        legend: { show: true, position: 'top', fontSize: '14px' },
                        annotations: {
                            yaxis: [{ y: this.datosTasaCierre.meta, borderColor: '#28a745', strokeDashArray: 5,
                                label: { text: 'Meta Objetivo 95%', style: { fontSize: '12px', background: '#28a745', color: '#fff' } } }]
                        },
                        title: { text: 'Tasa de Cierre de Reclamos', align: 'center', style: { fontSize: '18px', fontWeight: 'bold' } }
                    };

                    this.chartTasaCierre = new ApexCharts(elemento, options);
                    this.chartTasaCierre.render();
                },

                exportarGraficoTasaCierre() {
                    if (!this.chartTasaCierre) { alert('El gráfico no está cargado.'); return; }
                    this.chartTasaCierre.dataURI({ scale: 2 }).then(result => {
                        const link = document.createElement('a');
                        link.download = 'tasa-cierre-' + new Date().toISOString().slice(0, 10) + '.png';
                        link.href = result.imgURI || result;
                        link.click();
                    });
                },

                // Filtros rápidos para Tiempo Promedio
                setFiltroRapidoTiempoPromedio(tipo) {
                    const fechaHasta = new Date();
                    const fechaDesde = new Date();
                    switch(tipo) {
                        case 'hoy': break;
                        case '7dias': fechaDesde.setDate(fechaDesde.getDate() - 7); break;
                        case 'mes': fechaDesde.setDate(1); break;
                        case 'año': fechaDesde.setMonth(0, 1); break;
                    }
                    this.filtrosTiempo.fechaDesde = fechaDesde.toISOString().split('T')[0];
                    this.filtrosTiempo.fechaHasta = fechaHasta.toISOString().split('T')[0];
                    this.cargarGraficoTiempoPromedio();
                },

                // Filtros rápidos para Evolución Tiempo
                setFiltroRapidoEvolucionTiempo(tipo) {
                    const fechaHasta = new Date();
                    const fechaDesde = new Date();
                    switch(tipo) {
                        case 'hoy': break;
                        case '7dias': fechaDesde.setDate(fechaDesde.getDate() - 7); break;
                        case 'mes': fechaDesde.setDate(1); break;
                        case 'año': fechaDesde.setMonth(0, 1); break;
                    }
                    this.filtrosEvolucionTiempo.fechaDesde = fechaDesde.toISOString().split('T')[0];
                    this.filtrosEvolucionTiempo.fechaHasta = fechaHasta.toISOString().split('T')[0];
                    this.cargarGraficoEvolucionTiempo();
                },

                // Filtros rápidos para Alta Prioridad
                setFiltroRapidoAltaPrioridad(tipo) {
                    const fechaHasta = new Date();
                    const fechaDesde = new Date();
                    switch(tipo) {
                        case 'hoy': break;
                        case '7dias': fechaDesde.setDate(fechaDesde.getDate() - 7); break;
                        case 'mes': fechaDesde.setDate(1); break;
                        case 'año': fechaDesde.setMonth(0, 1); break;
                    }
                    this.filtrosAltaPrioridad.fechaDesde = fechaDesde.toISOString().split('T')[0];
                    this.filtrosAltaPrioridad.fechaHasta = fechaHasta.toISOString().split('T')[0];
                    this.cargarGraficoAltaPrioridad();
                },

                // Filtros rápidos para Consumo Materiales
                setFiltroRapidoConsumoMateriales(tipo) {
                    const fechaHasta = new Date();
                    const fechaDesde = new Date();
                    switch(tipo) {
                        case 'hoy': break;
                        case '7dias': fechaDesde.setDate(fechaDesde.getDate() - 7); break;
                        case 'mes': fechaDesde.setDate(1); break;
                        case 'año': fechaDesde.setMonth(0, 1); break;
                    }
                    this.filtrosConsumoMateriales.fechaDesde = fechaDesde.toISOString().split('T')[0];
                    this.filtrosConsumoMateriales.fechaHasta = fechaHasta.toISOString().split('T')[0];
                    this.cargarGraficoConsumoMateriales();
                },

                // Filtros rápidos para Cerrados/Abiertos
                setFiltroRapidoCerradosAbiertos(tipo) {
                    const fechaHasta = new Date();
                    const fechaDesde = new Date();
                    switch(tipo) {
                        case 'hoy': break;
                        case '7dias': fechaDesde.setDate(fechaDesde.getDate() - 7); break;
                        case 'mes': fechaDesde.setDate(1); break;
                        case 'año': fechaDesde.setMonth(0, 1); break;
                    }
                    this.filtrosCerradosAbiertos.fechaDesde = fechaDesde.toISOString().split('T')[0];
                    this.filtrosCerradosAbiertos.fechaHasta = fechaHasta.toISOString().split('T')[0];
                    this.cargarGraficoCerradosAbiertos();
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
