<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ReclamoModel;
use App\Models\Tiempo_promedio_motivoModel;
use App\Models\Tiempo_reparacionModel;
use App\Models\DireccionModel;

class Analisis extends ResourceController
{
    protected $format = 'json';
    private $reclamoModel;

    public function __construct()
    {
        $this->reclamoModel = new ReclamoModel();
        // Configurar zona horaria de Argentina
        date_default_timezone_set('America/Argentina/Buenos_Aires');
    }

    /**
     * Obtiene la distribución de reclamos por estado
     * Endpoint: GET /api/analisis/reclamos-por-estado
     * Filtros: fecha_desde, fecha_hasta, prioridad
     */
    public function getReclamosPorEstado()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $prioridad = $this->request->getGet('prioridad');

        // Construir la consulta base
        $builder = $this->reclamoModel->builder();

        // Aplicar filtros de fechas
        if ($fechaDesde) {
            $builder->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde);
        }
        if ($fechaHasta) {
            $builder->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);
        }
        if ($prioridad && $prioridad !== 'Todas') {
            $builder->where('prioridad', $prioridad);
        }

        // Contar reclamos por estado
        $resultados = $builder->select('municipalidad_estado, COUNT(*) as cantidad')
            ->groupBy('municipalidad_estado')
            ->get()
            ->getResultArray();

        // Definir estados posibles y colores
        $estadosConfig = [
            'Recibido' => '#6c757d',
            'Asignado' => '#ffc107',
            'En ejecución' => '#0dcaf0',
            'Completado' => '#198754',
            'En plan' => '#6610f2',
            'Error de datos' => '#dc3545'
        ];

        // Preparar datos para el gráfico
        $datos = [];
        $total = 0;

        foreach ($resultados as $resultado) {
            $estado = $resultado['municipalidad_estado'];
            $cantidad = (int) $resultado['cantidad'];
            $total += $cantidad;

            $datos[] = [
                'label' => $estado,
                'valor' => $cantidad,
                'color' => $estadosConfig[$estado] ?? '#6c757d'
            ];
        }

        // Incluir estados sin reclamos con valor 0
        foreach ($estadosConfig as $estado => $color) {
            $existe = false;
            foreach ($datos as $dato) {
                if ($dato['label'] === $estado) {
                    $existe = true;
                    break;
                }
            }
            if (!$existe) {
                $datos[] = [
                    'label' => $estado,
                    'valor' => 0,
                    'color' => $color
                ];
            }
        }

        // Ordenar por valor descendente
        usort($datos, function($a, $b) {
            return $b['valor'] - $a['valor'];
        });

        return $this->respond([
            'periodo' => date('Y-m'),
            'datos' => $datos,
            'total' => $total,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde ?? null,
                'fecha_hasta' => $fechaHasta ?? null,
                'prioridad' => $prioridad ?? 'Todas'
            ]
        ]);
    }

    /**
     * Obtiene la distribución de reclamos por motivo
     * Endpoint: GET /api/analisis/reclamos-por-motivo
     * Filtros: fecha_desde, fecha_hasta, estado, prioridad
     */
    public function getReclamosPorMotivo()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $estado = $this->request->getGet('estado');
        $prioridad = $this->request->getGet('prioridad');

        // Construir la consulta base
        $builder = $this->reclamoModel->builder();

        // Aplicar filtros de fechas
        if ($fechaDesde) {
            $builder->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde);
        }
        if ($fechaHasta) {
            $builder->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);
        }
        if ($estado && $estado !== 'Todos') {
            $builder->where('municipalidad_estado', $estado);
        }
        if ($prioridad && $prioridad !== 'Todas') {
            $builder->where('prioridad', $prioridad);
        }

        // Contar reclamos por motivo
        $resultados = $builder->select('municipalidad_motivo, COUNT(*) as cantidad')
            ->where('municipalidad_motivo IS NOT NULL')
            ->where('municipalidad_motivo !=', '')
            ->groupBy('municipalidad_motivo')
            ->orderBy('cantidad', 'DESC')
            ->get()
            ->getResultArray();

        // Generar colores dinámicamente
        $colores = [
            '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd',
            '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf',
            '#aec7e8', '#ffbb78', '#98df8a', '#ff9896', '#c5b0d5'
        ];

        // Preparar datos para el gráfico
        $datos = [];
        $total = 0;
        $colorIndex = 0;

        foreach ($resultados as $resultado) {
            $motivo = $resultado['municipalidad_motivo'];
            $cantidad = (int) $resultado['cantidad'];
            $total += $cantidad;

            $datos[] = [
                'label' => $motivo,
                'valor' => $cantidad,
                'color' => $colores[$colorIndex % count($colores)]
            ];
            $colorIndex++;
        }

        return $this->respond([
            'periodo' => date('Y-m'),
            'datos' => $datos,
            'total' => $total,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde ?? null,
                'fecha_hasta' => $fechaHasta ?? null,
                'estado' => $estado ?? 'Todos',
                'prioridad' => $prioridad ?? 'Todas'
            ]
        ]);
    }

    /**
     * Obtiene los KPIs de resumen
     * Endpoint: GET /api/analisis/kpi-resumen
     * Filtros: fecha_desde, fecha_hasta
     */
    public function getKpiResumen()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        
        $builder = $this->reclamoModel->builder();
        
        // Si no se especifica fecha desde, usar primer día del mes actual
        if (!$fechaDesde) {
            $fechaDesde = date('Y-m-01');
        }
        
        // Si no se especifica fecha hasta, usar hoy
        if (!$fechaHasta) {
            $fechaHasta = date('Y-m-d');
        }
        
        // Aplicar filtros de fechas
        $builder->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde);
        $builder->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);
        
        // Obtener totales por estado
        $resultados = $builder->select('municipalidad_estado, COUNT(*) as cantidad')
            ->groupBy('municipalidad_estado')
            ->get()
            ->getResultArray();
        
        // Inicializar contadores
        $totalActivos = 0;
        $totalPendientes = 0;
        $totalEnEjecucion = 0;
        $totalCompletados = 0;
        $total = 0;
        
        // Calcular métricas
        foreach ($resultados as $resultado) {
            $estado = $resultado['municipalidad_estado'];
            $cantidad = (int) $resultado['cantidad'];
            $total += $cantidad;
            
            // Reclamos activos (todos excepto completados y error de datos)
            if (!in_array($estado, ['Completado', 'Error de datos'])) {
                $totalActivos += $cantidad;
            }
            
            // Reclamos pendientes
            if (in_array($estado, ['Recibido', 'Asignado'])) {
                $totalPendientes += $cantidad;
            }
            
            // En ejecución
            if ($estado === 'En ejecución') {
                $totalEnEjecucion = $cantidad;
            }
            
            // Completados
            if ($estado === 'Completado') {
                $totalCompletados = $cantidad;
            }
        }
        
        // Calcular tasas
        $tasaResolucion = $total > 0 ? round(($totalCompletados / $total) * 100, 2) : 0;
        
        // Calcular tiempo promedio de resolución (en horas)
        // Buscar reclamos completados con fecha de cierre
        $builderTiempo = $this->reclamoModel->builder();
        $builderTiempo->select('municipalidad_fechaInicio, fecha_cierre')
            ->where('municipalidad_estado', 'Completado')
            ->where('fecha_cierre IS NOT NULL')
            ->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde)
            ->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);
        
        $reclamosCompletados = $builderTiempo->get()->getResultArray();
        
        $tiempoPromedioHoras = 0;
        $tiempoPromedioDias = 0;
        
        if (!empty($reclamosCompletados)) {
            $totalHoras = 0;
            $totalCompletadosTiempo = 0;
            
            foreach ($reclamosCompletados as $reclamo) {
                $fechaInicio = strtotime($reclamo['municipalidad_fechaInicio']);
                $fechaCierre = strtotime($reclamo['fecha_cierre']);
                
                if ($fechaInicio && $fechaCierre) {
                    $diferenciaHoras = ($fechaCierre - $fechaInicio) / 3600;
                    if ($diferenciaHoras > 0) {
                        $totalHoras += $diferenciaHoras;
                        $totalCompletadosTiempo++;
                    }
                }
            }
            
            if ($totalCompletadosTiempo > 0) {
                $tiempoPromedioHoras = round($totalHoras / $totalCompletadosTiempo, 2);
                $tiempoPromedioDias = round($tiempoPromedioHoras / 24, 2);
            }
        }
        
        return $this->respond([
            'total_activos' => $totalActivos,
            'total_pendientes' => $totalPendientes,
            'total_en_ejecucion' => $totalEnEjecucion,
            'total_completados' => $totalCompletados,
            'total' => $total,
            'tasa_resolucion' => $tasaResolucion,
            'tiempo_promedio_horas' => $tiempoPromedioHoras,
            'tiempo_promedio_dias' => $tiempoPromedioDias,
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta
            ]
        ]);
    }

    /**
     * Obtiene la evolución temporal de reclamos
     * Endpoint: GET /api/analisis/evolucion-temporal
     * Filtros: fecha_desde, fecha_hasta, granularidad (diario, semanal, mensual)
     */
    public function getEvolucionTemporal()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $granularidad = $this->request->getGet('granularidad') ?? 'diario';
        
        // Si no se especifica, usar últimos 30 días
        if (!$fechaDesde) {
            $fechaDesde = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$fechaHasta) {
            $fechaHasta = date('Y-m-d');
        }
        
        $builder = $this->reclamoModel->builder();
        $builder->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde)
                ->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);
        
        // Seleccionar según granularidad
        switch($granularidad) {
            case 'semanal':
                $builder->select("YEAR(municipalidad_fechaInicio) as año, WEEK(municipalidad_fechaInicio) as semana, municipalidad_estado, COUNT(*) as cantidad");
                $builder->groupBy('YEAR(municipalidad_fechaInicio), WEEK(municipalidad_fechaInicio), municipalidad_estado');
                $formatoFecha = 'Y-W';
                break;
            case 'mensual':
                $builder->select("YEAR(municipalidad_fechaInicio) as año, MONTH(municipalidad_fechaInicio) as mes, municipalidad_estado, COUNT(*) as cantidad");
                $builder->groupBy('YEAR(municipalidad_fechaInicio), MONTH(municipalidad_fechaInicio), municipalidad_estado');
                $formatoFecha = 'Y-m';
                break;
            case 'diario':
            default:
                $builder->select("DATE(municipalidad_fechaInicio) as fecha, municipalidad_estado, COUNT(*) as cantidad");
                $builder->groupBy('DATE(municipalidad_fechaInicio), municipalidad_estado');
                $formatoFecha = 'Y-m-d';
                break;
        }
        
        // Ordenar según granularidad
        if ($granularidad === 'semanal') {
            $builder->orderBy('año', 'ASC')->orderBy('semana', 'ASC');
        } elseif ($granularidad === 'mensual') {
            $builder->orderBy('año', 'ASC')->orderBy('mes', 'ASC');
        } else {
            $builder->orderBy('fecha', 'ASC');
        }
        
        $resultados = $builder->get()->getResultArray();
        
        // Organizar datos por fecha y estado
        $datosPorFecha = [];
        
        foreach ($resultados as $resultado) {
            $fecha = null;
            
            if ($granularidad === 'semanal') {
                $fecha = $resultado['año'] . '-W' . str_pad($resultado['semana'], 2, '0', STR_PAD_LEFT);
            } elseif ($granularidad === 'mensual') {
                $fecha = $resultado['año'] . '-' . str_pad($resultado['mes'], 2, '0', STR_PAD_LEFT);
            } else {
                $fecha = $resultado['fecha'];
            }
            
            if (!isset($datosPorFecha[$fecha])) {
                $datosPorFecha[$fecha] = [
                    'fecha' => $fecha,
                    'recibidos' => 0,
                    'asignados' => 0,
                    'en_ejecucion' => 0,
                    'completados' => 0,
                    'pendientes' => 0
                ];
            }
            
            $estado = $resultado['municipalidad_estado'];
            $cantidad = (int) $resultado['cantidad'];
            
            switch($estado) {
                case 'Recibido':
                    $datosPorFecha[$fecha]['recibidos'] = $cantidad;
                    $datosPorFecha[$fecha]['pendientes'] += $cantidad;
                    break;
                case 'Asignado':
                    $datosPorFecha[$fecha]['asignados'] = $cantidad;
                    $datosPorFecha[$fecha]['pendientes'] += $cantidad;
                    break;
                case 'En ejecución':
                    $datosPorFecha[$fecha]['en_ejecucion'] = $cantidad;
                    break;
                case 'Completado':
                    $datosPorFecha[$fecha]['completados'] = $cantidad;
                    break;
            }
        }
        
        // Ordenar por fecha
        ksort($datosPorFecha);
        
        // Preparar datos para el gráfico
        $labels = array_keys($datosPorFecha);
        $series = [
            [
                'name' => 'Recibidos',
                'data' => array_column($datosPorFecha, 'recibidos'),
                'color' => '#6c757d'
            ],
            [
                'name' => 'Pendientes',
                'data' => array_column($datosPorFecha, 'pendientes'),
                'color' => '#ffc107'
            ],
            [
                'name' => 'En Ejecución',
                'data' => array_column($datosPorFecha, 'en_ejecucion'),
                'color' => '#0dcaf0'
            ],
            [
                'name' => 'Completados',
                'data' => array_column($datosPorFecha, 'completados'),
                'color' => '#198754'
            ]
        ];
        
        return $this->respond([
            'periodo' => date('Y-m'),
            'granularidad' => $granularidad,
            'labels' => $labels,
            'series' => $series,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'granularidad' => $granularidad
            ]
        ]);
    }

    /**
     * Obtiene el tiempo promedio de resolución por motivo
     * Endpoint: GET /api/analisis/tiempo-promedio-por-motivo
     * Filtros: fecha_desde, fecha_hasta, motivo
     */
    public function getTiempoPromedioPorMotivo()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $motivo = $this->request->getGet('motivo');

        // Usar la tabla tiempo_promedio_motivo para obtener los promedios
        $tiempoPromedioModel = new Tiempo_promedio_motivoModel();
        $builder = $tiempoPromedioModel->builder();

        // Si se especifica fecha, necesitamos filtrar por tiempo_reparacion
        if ($fechaDesde || $fechaHasta) {
            // Usar tiempo_reparacion para obtener datos filtrados por fecha
            $tiempoReparacionModel = new Tiempo_reparacionModel();
            $builderTiempo = $tiempoReparacionModel->builder();
            
            // Aplicar filtros de fechas
            if ($fechaDesde) {
                $builderTiempo->where('DATE(fecha_registro) >=', $fechaDesde);
            }
            if ($fechaHasta) {
                $builderTiempo->where('DATE(fecha_registro) <=', $fechaHasta);
            }
            if ($motivo && $motivo !== 'Todos') {
                $builderTiempo->where('motivo_reclamo', $motivo);
            }

            // Obtener tiempos promedio por motivo desde tiempo_reparacion
            $resultados = $builderTiempo->select('motivo_reclamo, AVG(tiempo_minutos) as tiempo_promedio, COUNT(*) as cantidad_registros')
                ->where('motivo_reclamo IS NOT NULL')
                ->where('motivo_reclamo !=', '')
                ->groupBy('motivo_reclamo')
                ->orderBy('tiempo_promedio', 'DESC')
                ->get()
                ->getResultArray();

            // Preparar datos para el gráfico
            $datos = [];
            $total = 0;
            $colorIndex = 0;

            $colores = [
                '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd',
                '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf'
            ];

            foreach ($resultados as $resultado) {
                $motivoReclamo = $resultado['motivo_reclamo'];
                $tiempoPromedio = round((float) $resultado['tiempo_promedio'], 2);
                $cantidadRegistros = (int) $resultado['cantidad_registros'];
                $total += $cantidadRegistros;

                // Convertir minutos a horas si es mayor a 60 minutos
                $tiempoHoras = $tiempoPromedio >= 60 ? round($tiempoPromedio / 60, 2) : 0;
                $tiempoMinutos = $tiempoPromedio < 60 ? round($tiempoPromedio, 0) : round($tiempoPromedio % 60, 0);

                $datos[] = [
                    'label' => $motivoReclamo,
                    'valor' => $tiempoPromedio,
                    'valor_horas' => $tiempoHoras,
                    'valor_minutos' => $tiempoMinutos,
                    'cantidad_registros' => $cantidadRegistros,
                    'color' => $colores[$colorIndex % count($colores)]
                ];
                $colorIndex++;
            }
        } else {
            // Si no hay filtros de fecha, usar directamente tiempo_promedio_motivo
            $query = $builder->select('motivo, tiempo_promedio_minutos, cantidad_registros')
                ->orderBy('tiempo_promedio_minutos', 'DESC');
            
            if ($motivo && $motivo !== 'Todos') {
                $query->where('motivo', $motivo);
            }

            $resultados = $query->get()->getResultArray();

            // Preparar datos para el gráfico
            $datos = [];
            $total = 0;
            $colorIndex = 0;

            $colores = [
                '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd',
                '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf'
            ];

            foreach ($resultados as $resultado) {
                $motivoReclamo = $resultado['motivo'];
                $tiempoPromedio = (float) $resultado['tiempo_promedio_minutos'];
                $cantidadRegistros = (int) $resultado['cantidad_registros'];
                $total += $cantidadRegistros;

                // Convertir minutos a horas si es mayor a 60 minutos
                $tiempoHoras = $tiempoPromedio >= 60 ? round($tiempoPromedio / 60, 2) : 0;
                $tiempoMinutos = $tiempoPromedio < 60 ? round($tiempoPromedio, 0) : round($tiempoPromedio % 60, 0);

                $datos[] = [
                    'label' => $motivoReclamo,
                    'valor' => $tiempoPromedio,
                    'valor_horas' => $tiempoHoras,
                    'valor_minutos' => $tiempoMinutos,
                    'cantidad_registros' => $cantidadRegistros,
                    'color' => $colores[$colorIndex % count($colores)]
                ];
                $colorIndex++;
            }
        }

        return $this->respond([
            'periodo' => date('Y-m'),
            'datos' => $datos,
            'total' => $total,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde ?? null,
                'fecha_hasta' => $fechaHasta ?? null,
                'motivo' => $motivo ?? 'Todos'
            ]
        ]);
    }

    /**
     * Obtiene la evolución del tiempo promedio de resolución
     * Endpoint: GET /api/analisis/evolucion-tiempo-promedio
     * Filtros: fecha_desde, fecha_hasta, granularidad (semanal, mensual), motivo
     */
    public function getEvolucionTiempoPromedio()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $granularidad = $this->request->getGet('granularidad') ?? 'semanal';
        $motivo = $this->request->getGet('motivo');

        // Si no se especifica fecha, usar últimos 6 meses
        if (!$fechaDesde) {
            $fechaDesde = date('Y-m-d', strtotime('-6 months'));
        }
        if (!$fechaHasta) {
            $fechaHasta = date('Y-m-d');
        }

        $tiempoReparacionModel = new Tiempo_reparacionModel();
        $builder = $tiempoReparacionModel->builder();
        
        $builder->where('DATE(fecha_registro) >=', $fechaDesde)
                ->where('DATE(fecha_registro) <=', $fechaHasta);
        
        if ($motivo && $motivo !== 'Todos') {
            $builder->where('motivo_reclamo', $motivo);
        }

        // Seleccionar según granularidad y agrupar por motivo
        switch($granularidad) {
            case 'mensual':
                $builder->select("YEAR(fecha_registro) as año, MONTH(fecha_registro) as mes, motivo_reclamo, AVG(tiempo_minutos) as tiempo_promedio");
                $builder->groupBy('YEAR(fecha_registro), MONTH(fecha_registro), motivo_reclamo');
                $formatoFecha = 'Y-m';
                break;
            case 'semanal':
            default:
                $builder->select("YEAR(fecha_registro) as año, WEEK(fecha_registro) as semana, motivo_reclamo, AVG(tiempo_minutos) as tiempo_promedio");
                $builder->groupBy('YEAR(fecha_registro), WEEK(fecha_registro), motivo_reclamo');
                $formatoFecha = 'Y-W';
                break;
        }
        
        $resultados = $builder->orderBy('año', 'ASC')
                              ->orderBy($granularidad === 'mensual' ? 'mes' : 'semana', 'ASC')
                              ->orderBy('motivo_reclamo', 'ASC')
                              ->get()
                              ->getResultArray();

        // Obtener top 5 motivos más frecuentes
        $builderTopMotivos = $tiempoReparacionModel->builder();
        $builderTopMotivos->where('DATE(fecha_registro) >=', $fechaDesde)
                          ->where('DATE(fecha_registro) <=', $fechaHasta);
        
        $topMotivos = $builderTopMotivos->select('motivo_reclamo, COUNT(*) as cantidad')
            ->where('motivo_reclamo IS NOT NULL')
            ->where('motivo_reclamo !=', '')
            ->groupBy('motivo_reclamo')
            ->orderBy('cantidad', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();
        
        $motivosTop = array_column($topMotivos, 'motivo_reclamo');

        // Organizar datos por fecha y motivo
        $datosPorFecha = [];
        
        foreach ($resultados as $resultado) {
            $fecha = null;
            
            if ($granularidad === 'mensual') {
                $fecha = $resultado['año'] . '-' . str_pad($resultado['mes'], 2, '0', STR_PAD_LEFT);
            } else {
                $fecha = $resultado['año'] . '-W' . str_pad($resultado['semana'], 2, '0', STR_PAD_LEFT);
            }
            
            $motivoReclamo = $resultado['motivo_reclamo'];
            $tiempoPromedio = round((float) $resultado['tiempo_promedio'], 2);
            
            // Solo incluir si está en el top 5
            if (in_array($motivoReclamo, $motivosTop)) {
                if (!isset($datosPorFecha[$fecha])) {
                    $datosPorFecha[$fecha] = [];
                }
                
                if (!isset($datosPorFecha[$fecha][$motivoReclamo])) {
                    $datosPorFecha[$fecha][$motivoReclamo] = $tiempoPromedio;
                }
            }
        }

        // Ordenar por fecha
        ksort($datosPorFecha);

        // Preparar series para el gráfico
        $labels = array_keys($datosPorFecha);
        $series = [];
        
        $colores = [
            '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd'
        ];

        foreach ($motivosTop as $index => $motivoTop) {
            $data = [];
            foreach ($labels as $fecha) {
                $data[] = isset($datosPorFecha[$fecha][$motivoTop]) ? $datosPorFecha[$fecha][$motivoTop] : 0;
            }
            
            $series[] = [
                'name' => $motivoTop,
                'data' => $data,
                'color' => $colores[$index % count($colores)]
            ];
        }

        return $this->respond([
            'periodo' => date('Y-m'),
            'granularidad' => $granularidad,
            'labels' => $labels,
            'series' => $series,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'granularidad' => $granularidad,
                'motivo' => $motivo ?? 'Todos'
            ]
        ]);
    }

    /**
     * Obtiene la evolución de reclamos de alta prioridad
     * Endpoint: GET /api/analisis/evolucion-alta-prioridad
     * Filtros: fecha_desde, fecha_hasta, granularidad
     */
    public function getEvolucionAltaPrioridad()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $granularidad = $this->request->getGet('granularidad') ?? 'diario';

        if (!$fechaDesde) {
            $fechaDesde = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$fechaHasta) {
            $fechaHasta = date('Y-m-d');
        }

        $builder = $this->reclamoModel->builder();
        $builder->where('prioridad', 'Alta')
                ->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde)
                ->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);

        switch($granularidad) {
            case 'semanal':
                $builder->select("YEAR(municipalidad_fechaInicio) as año, WEEK(municipalidad_fechaInicio) as semana, COUNT(*) as cantidad");
                $builder->groupBy('YEAR(municipalidad_fechaInicio), WEEK(municipalidad_fechaInicio)');
                break;
            case 'diario':
            default:
                $builder->select("DATE(municipalidad_fechaInicio) as fecha, COUNT(*) as cantidad");
                $builder->groupBy('DATE(municipalidad_fechaInicio)');
                break;
        }

        $resultados = $builder->orderBy($granularidad === 'semanal' ? 'año' : 'fecha', 'ASC')
                              ->get()->getResultArray();

        $labels = [];
        $data = [];

        foreach ($resultados as $resultado) {
            if ($granularidad === 'semanal') {
                $labels[] = $resultado['año'] . '-W' . str_pad($resultado['semana'], 2, '0', STR_PAD_LEFT);
            } else {
                $labels[] = $resultado['fecha'];
            }
            $data[] = (int) $resultado['cantidad'];
        }

        // Calcular línea de tendencia (promedio móvil simple)
        $tendencia = [];
        $ventana = min(5, count($data));
        for ($i = 0; $i < count($data); $i++) {
            $inicio = max(0, $i - $ventana + 1);
            $valores = array_slice($data, $inicio, $i - $inicio + 1);
            $tendencia[] = round(array_sum($valores) / count($valores), 2);
        }

        // Meta objetivo (promedio histórico reducido en 20%)
        $promedio = count($data) > 0 ? array_sum($data) / count($data) : 0;
        $meta = round($promedio * 0.8, 2);

        return $this->respond([
            'labels' => $labels,
            'series' => [
                ['name' => 'Alta Prioridad', 'data' => $data, 'color' => '#dc3545'],
                ['name' => 'Tendencia', 'data' => $tendencia, 'color' => '#6c757d']
            ],
            'meta' => $meta,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'granularidad' => $granularidad
            ]
        ]);
    }

    /**
     * Obtiene el consumo de materiales por período
     * Endpoint: GET /api/analisis/consumo-materiales
     * Filtros: fecha_desde, fecha_hasta, material
     */
    public function getConsumoMateriales()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $material = $this->request->getGet('material');

        if (!$fechaDesde) {
            $fechaDesde = date('Y-m-d', strtotime('-6 months'));
        }
        if (!$fechaHasta) {
            $fechaHasta = date('Y-m-d');
        }

        $db = \Config\Database::connect();
        
        // Obtener top 5 materiales más usados desde material_reclamo
        $builderTop = $db->table('material_reclamo mr');
        $builderTop->select('m.nombre, SUM(mr.cantidad) as total')
                   ->join('material m', 'm.id = mr.material_id')
                   ->where('DATE(mr.fecha) >=', $fechaDesde)
                   ->where('DATE(mr.fecha) <=', $fechaHasta)
                   ->groupBy('m.id')
                   ->orderBy('total', 'DESC')
                   ->limit(5);

        $topMateriales = $builderTop->get()->getResultArray();
        $materialesTop = array_column($topMateriales, 'nombre');

        if (empty($materialesTop)) {
            return $this->respond([
                'labels' => [],
                'series' => [],
                'materiales_disponibles' => [],
                'mensaje' => 'No hay datos de consumo de materiales en el período seleccionado',
                'filtros_aplicados' => [
                    'fecha_desde' => $fechaDesde,
                    'fecha_hasta' => $fechaHasta,
                    'material' => $material ?? 'Todos'
                ]
            ]);
        }

        // Obtener consumo por mes desde material_reclamo
        $builder = $db->table('material_reclamo mr');
        $builder->select("YEAR(mr.fecha) as año, MONTH(mr.fecha) as mes, m.nombre as material, SUM(mr.cantidad) as cantidad")
                ->join('material m', 'm.id = mr.material_id')
                ->where('DATE(mr.fecha) >=', $fechaDesde)
                ->where('DATE(mr.fecha) <=', $fechaHasta)
                ->whereIn('m.nombre', $materialesTop)
                ->groupBy('YEAR(mr.fecha), MONTH(mr.fecha), m.id')
                ->orderBy('año', 'ASC')
                ->orderBy('mes', 'ASC');

        if ($material && $material !== 'Todos') {
            $builder->where('m.nombre', $material);
        }

        $resultados = $builder->get()->getResultArray();

        // Organizar datos por fecha y material
        $datosPorFecha = [];
        foreach ($resultados as $resultado) {
            $fecha = $resultado['año'] . '-' . str_pad($resultado['mes'], 2, '0', STR_PAD_LEFT);
            if (!isset($datosPorFecha[$fecha])) {
                $datosPorFecha[$fecha] = [];
            }
            $datosPorFecha[$fecha][$resultado['material']] = (int) $resultado['cantidad'];
        }

        ksort($datosPorFecha);
        $labels = array_keys($datosPorFecha);

        $colores = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd'];
        $series = [];

        foreach ($materialesTop as $index => $mat) {
            $data = [];
            foreach ($labels as $fecha) {
                $data[] = $datosPorFecha[$fecha][$mat] ?? 0;
            }
            $series[] = [
                'name' => $mat,
                'data' => $data,
                'color' => $colores[$index % count($colores)]
            ];
        }

        return $this->respond([
            'labels' => $labels,
            'series' => $series,
            'materiales_disponibles' => $materialesTop,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'material' => $material ?? 'Todos'
            ]
        ]);
    }

    /**
     * Obtiene reclamos cerrados vs abiertos
     * Endpoint: GET /api/analisis/reclamos-cerrados-abiertos
     * Filtros: fecha_desde, fecha_hasta, granularidad
     */
    public function getReclamosCerradosAbiertos()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $granularidad = $this->request->getGet('granularidad') ?? 'mensual';

        if (!$fechaDesde) {
            $fechaDesde = date('Y-m-d', strtotime('-6 months'));
        }
        if (!$fechaHasta) {
            $fechaHasta = date('Y-m-d');
        }

        $builder = $this->reclamoModel->builder();
        $builder->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde)
                ->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);

        switch($granularidad) {
            case 'semanal':
                $builder->select("YEAR(municipalidad_fechaInicio) as año, WEEK(municipalidad_fechaInicio) as periodo, cerrado, COUNT(*) as cantidad");
                $builder->groupBy('YEAR(municipalidad_fechaInicio), WEEK(municipalidad_fechaInicio), cerrado');
                break;
            case 'mensual':
            default:
                $builder->select("YEAR(municipalidad_fechaInicio) as año, MONTH(municipalidad_fechaInicio) as periodo, cerrado, COUNT(*) as cantidad");
                $builder->groupBy('YEAR(municipalidad_fechaInicio), MONTH(municipalidad_fechaInicio), cerrado');
                break;
        }

        $resultados = $builder->orderBy('año', 'ASC')->orderBy('periodo', 'ASC')->get()->getResultArray();

        $datosPorFecha = [];
        foreach ($resultados as $resultado) {
            if ($granularidad === 'semanal') {
                $fecha = $resultado['año'] . '-W' . str_pad($resultado['periodo'], 2, '0', STR_PAD_LEFT);
            } else {
                $fecha = $resultado['año'] . '-' . str_pad($resultado['periodo'], 2, '0', STR_PAD_LEFT);
            }
            
            if (!isset($datosPorFecha[$fecha])) {
                $datosPorFecha[$fecha] = ['cerrados' => 0, 'abiertos' => 0];
            }
            
            if ($resultado['cerrado'] == 1) {
                $datosPorFecha[$fecha]['cerrados'] = (int) $resultado['cantidad'];
            } else {
                $datosPorFecha[$fecha]['abiertos'] = (int) $resultado['cantidad'];
            }
        }

        ksort($datosPorFecha);
        $labels = array_keys($datosPorFecha);
        
        $cerrados = [];
        $abiertos = [];
        $tasas = [];

        foreach ($datosPorFecha as $datos) {
            $cerrados[] = $datos['cerrados'];
            $abiertos[] = $datos['abiertos'];
            $total = $datos['cerrados'] + $datos['abiertos'];
            $tasas[] = $total > 0 ? round(($datos['cerrados'] / $total) * 100, 1) : 0;
        }

        return $this->respond([
            'labels' => $labels,
            'series' => [
                ['name' => 'Cerrados', 'data' => $cerrados, 'color' => '#198754'],
                ['name' => 'Abiertos', 'data' => $abiertos, 'color' => '#dc3545']
            ],
            'tasas' => $tasas,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'granularidad' => $granularidad
            ]
        ]);
    }

    /**
     * Obtiene la tasa de cierre de reclamos
     * Endpoint: GET /api/analisis/tasa-cierre
     * Filtros: fecha_desde, fecha_hasta, granularidad
     */
    public function getTasaCierre()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $granularidad = $this->request->getGet('granularidad') ?? 'semanal';

        if (!$fechaDesde) {
            $fechaDesde = date('Y-m-d', strtotime('-3 months'));
        }
        if (!$fechaHasta) {
            $fechaHasta = date('Y-m-d');
        }

        $builder = $this->reclamoModel->builder();
        $builder->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde)
                ->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);

        switch($granularidad) {
            case 'mensual':
                $builder->select("YEAR(municipalidad_fechaInicio) as año, MONTH(municipalidad_fechaInicio) as periodo, 
                                  SUM(CASE WHEN cerrado = 1 THEN 1 ELSE 0 END) as cerrados,
                                  COUNT(*) as total");
                $builder->groupBy('YEAR(municipalidad_fechaInicio), MONTH(municipalidad_fechaInicio)');
                break;
            case 'semanal':
            default:
                $builder->select("YEAR(municipalidad_fechaInicio) as año, WEEK(municipalidad_fechaInicio) as periodo,
                                  SUM(CASE WHEN cerrado = 1 THEN 1 ELSE 0 END) as cerrados,
                                  COUNT(*) as total");
                $builder->groupBy('YEAR(municipalidad_fechaInicio), WEEK(municipalidad_fechaInicio)');
                break;
        }

        $resultados = $builder->orderBy('año', 'ASC')->orderBy('periodo', 'ASC')->get()->getResultArray();

        $labels = [];
        $tasas = [];

        foreach ($resultados as $resultado) {
            if ($granularidad === 'mensual') {
                $labels[] = $resultado['año'] . '-' . str_pad($resultado['periodo'], 2, '0', STR_PAD_LEFT);
            } else {
                $labels[] = $resultado['año'] . '-W' . str_pad($resultado['periodo'], 2, '0', STR_PAD_LEFT);
            }
            
            $total = (int) $resultado['total'];
            $cerrados = (int) $resultado['cerrados'];
            $tasas[] = $total > 0 ? round(($cerrados / $total) * 100, 1) : 0;
        }

        $meta = 95; // Meta objetivo del 95%

        return $this->respond([
            'labels' => $labels,
            'series' => [
                ['name' => 'Tasa de Cierre (%)', 'data' => $tasas, 'color' => '#0d6efd']
            ],
            'meta' => $meta,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'granularidad' => $granularidad
            ]
        ]);
    }

    /**
     * Obtiene datos para mapa de calor de zonas con mayor concentración de reclamos
     * Endpoint: GET /api/analisis/mapa-calor-zonas
     * Filtros: fecha_desde, fecha_hasta, estado, prioridad, motivo
     */
    public function getMapaCalorZonas()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $estado = $this->request->getGet('estado');
        $prioridad = $this->request->getGet('prioridad');
        $motivo = $this->request->getGet('motivo');

        // Construir la consulta base de reclamos
        $reclamoModel = new ReclamoModel();
        $builder = $reclamoModel->builder();

        // Aplicar filtros de fechas
        if ($fechaDesde) {
            $builder->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde);
        }
        if ($fechaHasta) {
            $builder->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);
        }
        if ($estado && $estado !== 'Todos') {
            $builder->where('municipalidad_estado', $estado);
        }
        if ($prioridad && $prioridad !== 'Todas') {
            $builder->where('prioridad', $prioridad);
        }
        if ($motivo && $motivo !== 'Todos') {
            $builder->where('municipalidad_motivo', $motivo);
        }

        // Obtener reclamos con domicilio válido
        $reclamos = $builder->select('municipalidad_domicilio, municipalidad_numeroDomicilio, COUNT(*) as cantidad')
            ->where('municipalidad_domicilio IS NOT NULL')
            ->where('municipalidad_domicilio !=', '')
            ->groupBy('municipalidad_domicilio, municipalidad_numeroDomicilio')
            ->having('cantidad', 1, '>=')
            ->get()
            ->getResultArray();

        // Obtener coordenadas de direcciones
        $direccionModel = new DireccionModel();
        $datos = [];
        $total = 0;

        foreach ($reclamos as $reclamo) {
            $domicilio = $reclamo['municipalidad_domicilio'];
            $numeroDomicilio = $reclamo['municipalidad_numeroDomicilio'] ?? '';
            $cantidad = (int) $reclamo['cantidad'];
            $total += $cantidad;

            // Buscar dirección con coordenadas
            $direccionBuilder = $direccionModel->builder();
            $direccionBuilder->where('domicilio', $domicilio);
            
            if (!empty($numeroDomicilio)) {
                $direccionBuilder->where('numero_domicilio', $numeroDomicilio);
            }
            
            $direccion = $direccionBuilder->get()->getRowArray();

            if ($direccion && !empty($direccion['latitud']) && !empty($direccion['longitud'])) {
                $datos[] = [
                    'lat' => (float) $direccion['latitud'],
                    'lng' => (float) $direccion['longitud'],
                    'cantidad' => $cantidad,
                    'domicilio' => $domicilio . ($numeroDomicilio ? ' ' . $numeroDomicilio : '')
                ];
            }
        }

        // Ordenar por cantidad descendente
        usort($datos, function($a, $b) {
            return $b['cantidad'] - $a['cantidad'];
        });

        return $this->respond([
            'periodo' => date('Y-m'),
            'datos' => $datos,
            'total' => $total,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde ?? null,
                'fecha_hasta' => $fechaHasta ?? null,
                'estado' => $estado ?? 'Todos',
                'prioridad' => $prioridad ?? 'Todas',
                'motivo' => $motivo ?? 'Todos'
            ]
        ]);
    }
}

