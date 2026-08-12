<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ReclamoModel;
use App\Models\Tiempo_reparacionModel;
use App\Models\DireccionModel;

class Analisis extends ResourceController
{
    protected $format = 'json';
    private $reclamoModel;

    /** Catálogo de motivos del sistema (mismo listado que el alta de reclamos). */
    private const MOTIVOS_RECLAMO = [
        'Luminaria agotada (Prende y Apaga)',
        'Postes, cables caídos o por caer (Telecom, Epec, Monet)',
        'Semáforos - Arreglo y sincronización',
        'Luminarias quemadas o rotas',
        'Corte de ramas que tocan cables de alumbrado',
        'Columnas de alumbrado caídas o por caer',
        'Cables de alumbrado caídos',
    ];

    /**
     * Estados operativos usados en Análisis.
     * Recibido / Asignado / Pendiente / En ejecución / Completado: mapa-rutas.
     * Cerrado: cierre formal (flag cerrado=1), normalmente tras Completado.
     */
    private const ESTADOS_COLORES = [
        'Recibido' => '#808080',
        'Asignado' => '#0DCAF0',
        'Pendiente' => '#FF0000',
        'En ejecución' => '#FFD700',
        'Completado' => '#198754',
        'Cerrado' => '#000000',
    ];

    private function colorEstado(string $estado): string
    {
        return self::ESTADOS_COLORES[$estado] ?? '#808080';
    }

    /**
     * Builder de reclamos visibles (excluye los marcados localmente por el supervisor).
     */
    private function builderReclamosVisibles()
    {
        return $this->reclamoModel->builder()->where('excluido_local', 0);
    }

    /**
     * Estado analítico: si cerrado=1 cuenta como Cerrado; si no, el municipalidad_estado.
     */
    private function estadoAnalitico(?string $estado, $cerrado): string
    {
        if ((int) $cerrado === 1) {
            return 'Cerrado';
        }
        return $estado ?: 'Recibido';
    }

    /**
     * Aplica filtro de estado analítico (incluye Cerrado vía flag cerrado).
     */
    private function aplicarFiltroEstadoAnalitico($builder, ?string $estado): void
    {
        if ($estado === null || $estado === '' || $estado === 'Todos') {
            return;
        }

        if ($estado === 'Cerrado') {
            $builder->where('cerrado', 1);
            return;
        }

        $builder->where('municipalidad_estado', $estado);
        $builder->groupStart()
            ->where('cerrado', 0)
            ->orWhere('cerrado', null)
            ->groupEnd();
    }

    /**
     * Etiqueta legible de un período semanal usando fechas reales del grupo.
     * Ej: "07/07 – 13/07/2026" o "10/07/2026" si hay un solo día.
     */
    private function labelRangoFechas(?string $fechaMin, ?string $fechaMax): string
    {
        if (!$fechaMin) {
            return '';
        }
        $tsMin = strtotime($fechaMin);
        if ($tsMin === false) {
            return (string) $fechaMin;
        }
        if (!$fechaMax || $fechaMin === $fechaMax) {
            return date('d/m/Y', $tsMin);
        }
        $tsMax = strtotime($fechaMax);
        if ($tsMax === false) {
            return date('d/m/Y', $tsMin);
        }
        return date('d/m', $tsMin) . ' – ' . date('d/m/Y', $tsMax);
    }

    private function labelMes(int $anio, int $mes): string
    {
        $nombres = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];
        return ($nombres[$mes] ?? str_pad((string) $mes, 2, '0', STR_PAD_LEFT)) . ' ' . $anio;
    }

    /**
     * Catálogo de motivos + extras del período en reclamos + extras puntuales (p. ej. tiempos).
     * Misma base para "Reclamos por motivo" y gráficos de tiempo de reparación.
     */
    private function listaMotivosAnalisis(?string $fechaDesde = null, ?string $fechaHasta = null, array $extras = []): array
    {
        $lista = self::MOTIVOS_RECLAMO;

        $builder = $this->builderReclamosVisibles()
            ->select('municipalidad_motivo')
            ->where('municipalidad_motivo IS NOT NULL')
            ->where('municipalidad_motivo !=', '');

        if ($fechaDesde) {
            $builder->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde);
        }
        if ($fechaHasta) {
            $builder->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);
        }

        $enReclamos = $builder->groupBy('municipalidad_motivo')->get()->getResultArray();
        foreach ($enReclamos as $row) {
            $motivo = trim((string) ($row['municipalidad_motivo'] ?? ''));
            if ($motivo !== '' && !in_array($motivo, $lista, true)) {
                $lista[] = $motivo;
            }
        }

        foreach ($extras as $extra) {
            $motivo = trim((string) $extra);
            if ($motivo !== '' && !in_array($motivo, $lista, true)) {
                $lista[] = $motivo;
            }
        }

        return $lista;
    }

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
        $builder = $this->builderReclamosVisibles();

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

        // Contar por estado analítico (Cerrado = flag cerrado=1)
        $resultados = $builder->select(
                "CASE WHEN cerrado = 1 THEN 'Cerrado' ELSE municipalidad_estado END as estado_analitico, COUNT(*) as cantidad",
                false
            )
            ->groupBy('estado_analitico')
            ->get()
            ->getResultArray();

        $conteos = [];
        $total = 0;
        foreach ($resultados as $resultado) {
            $estado = $resultado['estado_analitico'] ?: 'Recibido';
            $cantidad = (int) $resultado['cantidad'];
            $conteos[$estado] = ($conteos[$estado] ?? 0) + $cantidad;
            $total += $cantidad;
        }

        $datos = [];
        foreach (self::ESTADOS_COLORES as $estado => $color) {
            $datos[] = [
                'label' => $estado,
                'valor' => $conteos[$estado] ?? 0,
                'color' => $color,
            ];
            unset($conteos[$estado]);
        }
        // Estados fuera de catálogo (p. ej. En plan), si quedaran
        foreach ($conteos as $estado => $cantidad) {
            $datos[] = [
                'label' => $estado,
                'valor' => $cantidad,
                'color' => $this->colorEstado($estado),
            ];
        }

        usort($datos, static function ($a, $b) {
            return $b['valor'] <=> $a['valor'];
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
        $builder = $this->builderReclamosVisibles();

        // Aplicar filtros de fechas
        if ($fechaDesde) {
            $builder->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde);
        }
        if ($fechaHasta) {
            $builder->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);
        }
        $this->aplicarFiltroEstadoAnalitico($builder, $estado);
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

        // Mapa de conteos reales del período
        $conteos = [];
        foreach ($resultados as $resultado) {
            $motivo = trim((string) $resultado['municipalidad_motivo']);
            if ($motivo === '') {
                continue;
            }
            $conteos[$motivo] = (int) $resultado['cantidad'];
        }

        // Catálogo + motivos extras del período (misma lógica que gráficos de tiempo)
        $motivosOrden = $this->listaMotivosAnalisis($fechaDesde, $fechaHasta, array_keys($conteos));

        // Preparar datos: todos los motivos, aunque el período tenga 0
        $datos = [];
        $total = 0;
        $colorIndex = 0;

        foreach ($motivosOrden as $motivo) {
            $cantidad = $conteos[$motivo] ?? 0;
            $total += $cantidad;

            $datos[] = [
                'label' => $motivo,
                'valor' => $cantidad,
                'color' => $colores[$colorIndex % count($colores)]
            ];
            $colorIndex++;
        }

        // Ordenar por cantidad descendente (ceros al final)
        usort($datos, function ($a, $b) {
            if ($b['valor'] === $a['valor']) {
                return strcmp($a['label'], $b['label']);
            }
            return $b['valor'] <=> $a['valor'];
        });

        return $this->respond([
            'periodo' => date('Y-m'),
            'datos' => $datos,
            'total' => $total,
            'motivos_catalogo' => self::MOTIVOS_RECLAMO,
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
        
        $builder = $this->builderReclamosVisibles();
        
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
        
        // Totales por estado analítico (incluye Cerrado)
        $resultados = $builder->select(
                "CASE WHEN cerrado = 1 THEN 'Cerrado' ELSE municipalidad_estado END as estado_analitico, COUNT(*) as cantidad",
                false
            )
            ->groupBy('estado_analitico')
            ->get()
            ->getResultArray();

        $totalRecibidos = 0;
        $totalAsignados = 0;
        $totalPendientes = 0;
        $totalEnEjecucion = 0;
        $totalCompletados = 0;
        $totalCerrados = 0;
        $totalActivos = 0;
        $total = 0;

        foreach ($resultados as $resultado) {
            $estado = $resultado['estado_analitico'] ?: 'Recibido';
            $cantidad = (int) $resultado['cantidad'];
            $total += $cantidad;

            if (in_array($estado, ['Recibido', 'Asignado', 'Pendiente', 'En ejecución'], true)) {
                $totalActivos += $cantidad;
            }

            switch ($estado) {
                case 'Recibido':
                    $totalRecibidos = $cantidad;
                    break;
                case 'Asignado':
                    $totalAsignados = $cantidad;
                    break;
                case 'Pendiente':
                    $totalPendientes = $cantidad;
                    break;
                case 'En ejecución':
                    $totalEnEjecucion = $cantidad;
                    break;
                case 'Completado':
                    $totalCompletados = $cantidad;
                    break;
                case 'Cerrado':
                    $totalCerrados = $cantidad;
                    break;
            }
        }

        // Cierre formal sobre el total ingresado en el período
        $tasaResolucion = $total > 0
            ? round(($totalCerrados / $total) * 100, 2)
            : 0;
        
        // Tiempo promedio hasta cierre formal (flag cerrado / fecha_cierre)
        $builderTiempo = $this->builderReclamosVisibles();
        $builderTiempo->select('municipalidad_fechaInicio, fecha_cierre')
            ->where('cerrado', 1)
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
            'total_recibidos' => $totalRecibidos,
            'total_asignados' => $totalAsignados,
            'total_pendientes' => $totalPendientes,
            'total_en_ejecucion' => $totalEnEjecucion,
            'total_completados' => $totalCompletados,
            'total_cerrados' => $totalCerrados,
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
     * Obtiene la evolución temporal de ingresos vs cerrados.
     * Endpoint: GET /api/analisis/evolucion-temporal
     * - Ingresos: por municipalidad_fechaInicio
     * - Cerrados: por fecha_cierre (solo cerrado = 1)
     * Filtros: fecha_desde, fecha_hasta, granularidad (diario, semanal, mensual)
     */
    public function getEvolucionTemporal()
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

        $ingresos = $this->agregarSerieTemporal('municipalidad_fechaInicio', $fechaDesde, $fechaHasta, $granularidad);
        $cerrados = $this->agregarSerieTemporal('fecha_cierre', $fechaDesde, $fechaHasta, $granularidad, true);

        $claves = array_unique(array_merge(array_keys($ingresos), array_keys($cerrados)));
        sort($claves);

        $datosPorFecha = [];
        $metaPeriodo = [];
        foreach ($claves as $clave) {
            $metaIng = $ingresos[$clave]['meta'] ?? [];
            $metaCer = $cerrados[$clave]['meta'] ?? [];
            $datosPorFecha[$clave] = [
                'ingresos' => (int) ($ingresos[$clave]['cantidad'] ?? 0),
                'cerrados' => (int) ($cerrados[$clave]['cantidad'] ?? 0),
            ];
            $metaPeriodo[$clave] = [
                'fecha_min' => $this->minFecha($metaIng['fecha_min'] ?? null, $metaCer['fecha_min'] ?? null),
                'fecha_max' => $this->maxFecha($metaIng['fecha_max'] ?? null, $metaCer['fecha_max'] ?? null),
                'mes' => $metaIng['mes'] ?? $metaCer['mes'] ?? null,
                'año' => $metaIng['año'] ?? $metaCer['año'] ?? null,
            ];
        }

        $labels = [];
        foreach ($claves as $clave) {
            if ($granularidad === 'semanal') {
                $labels[] = $this->labelRangoFechas(
                    $metaPeriodo[$clave]['fecha_min'] ?? null,
                    $metaPeriodo[$clave]['fecha_max'] ?? null
                ) ?: $clave;
            } elseif ($granularidad === 'mensual') {
                $labels[] = $this->labelMes(
                    (int) ($metaPeriodo[$clave]['año'] ?? 0),
                    (int) ($metaPeriodo[$clave]['mes'] ?? 0)
                );
            } else {
                $ts = strtotime((string) $clave);
                $labels[] = $ts ? date('d/m/Y', $ts) : $clave;
            }
        }

        $series = [
            [
                'name' => 'Ingresos',
                'data' => array_column($datosPorFecha, 'ingresos'),
                'color' => '#0d6efd',
            ],
            [
                'name' => 'Cierres',
                'data' => array_column($datosPorFecha, 'cerrados'),
                'color' => $this->colorEstado('Cerrado'),
            ],
        ];

        return $this->respond([
            'periodo' => date('Y-m'),
            'granularidad' => $granularidad,
            'labels' => $labels,
            'series' => $series,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'granularidad' => $granularidad,
            ],
        ]);
    }

    /**
     * Agrupa cantidades por período sobre una columna de fecha.
     * @return array<string, array{cantidad:int, meta:array}>
     */
    private function agregarSerieTemporal(
        string $columnaFecha,
        string $fechaDesde,
        string $fechaHasta,
        string $granularidad,
        bool $soloCerrados = false
    ): array {
        $builder = $this->builderReclamosVisibles();
        $builder->where("DATE({$columnaFecha}) >=", $fechaDesde)
                ->where("DATE({$columnaFecha}) <=", $fechaHasta)
                ->where("{$columnaFecha} IS NOT NULL");

        if ($soloCerrados) {
            $builder->where('cerrado', 1);
        }

        switch ($granularidad) {
            case 'semanal':
                $builder->select(
                    "YEAR({$columnaFecha}) as año, WEEK({$columnaFecha}) as semana, COUNT(*) as cantidad, "
                    . "MIN(DATE({$columnaFecha})) as fecha_min, MAX(DATE({$columnaFecha})) as fecha_max",
                    false
                );
                $builder->groupBy("YEAR({$columnaFecha}), WEEK({$columnaFecha})");
                $builder->orderBy('año', 'ASC')->orderBy('semana', 'ASC');
                break;
            case 'mensual':
                $builder->select(
                    "YEAR({$columnaFecha}) as año, MONTH({$columnaFecha}) as mes, COUNT(*) as cantidad",
                    false
                );
                $builder->groupBy("YEAR({$columnaFecha}), MONTH({$columnaFecha})");
                $builder->orderBy('año', 'ASC')->orderBy('mes', 'ASC');
                break;
            case 'diario':
            default:
                $builder->select("DATE({$columnaFecha}) as fecha, COUNT(*) as cantidad", false);
                $builder->groupBy("DATE({$columnaFecha})");
                $builder->orderBy('fecha', 'ASC');
                break;
        }

        $resultados = $builder->get()->getResultArray();
        $agrupado = [];

        foreach ($resultados as $resultado) {
            if ($granularidad === 'semanal') {
                $clave = $resultado['año'] . '-W' . str_pad((string) $resultado['semana'], 2, '0', STR_PAD_LEFT);
            } elseif ($granularidad === 'mensual') {
                $clave = $resultado['año'] . '-' . str_pad((string) $resultado['mes'], 2, '0', STR_PAD_LEFT);
            } else {
                $clave = $resultado['fecha'];
            }

            $agrupado[$clave] = [
                'cantidad' => (int) $resultado['cantidad'],
                'meta' => [
                    'fecha_min' => $resultado['fecha_min'] ?? ($granularidad === 'diario' ? $clave : null),
                    'fecha_max' => $resultado['fecha_max'] ?? ($granularidad === 'diario' ? $clave : null),
                    'mes' => isset($resultado['mes']) ? (int) $resultado['mes'] : null,
                    'año' => isset($resultado['año']) ? (int) $resultado['año'] : null,
                ],
            ];
        }

        return $agrupado;
    }

    private function minFecha(?string $a, ?string $b): ?string
    {
        if (!$a) {
            return $b;
        }
        if (!$b) {
            return $a;
        }
        return $a <= $b ? $a : $b;
    }

    private function maxFecha(?string $a, ?string $b): ?string
    {
        if (!$a) {
            return $b;
        }
        if (!$b) {
            return $a;
        }
        return $a >= $b ? $a : $b;
    }

    /**
     * Antigüedad de reclamos abiertos (sin cierre formal).
     * Snapshot actual: no filtra por período de ingreso (así no oculta backlog viejo).
     * Endpoint: GET /api/analisis/antiguedad-abiertos
     * Filtros: prioridad (Alta / Baja)
     */
    public function getAntiguedadAbiertos()
    {
        $prioridad = $this->request->getGet('prioridad');

        $builder = $this->builderReclamosVisibles();
        $builder->groupStart()
            ->where('cerrado', 0)
            ->orWhere('cerrado', null)
            ->groupEnd()
            ->where('municipalidad_fechaInicio IS NOT NULL');

        if ($prioridad && $prioridad !== 'Todas' && $prioridad !== '') {
            $builder->where('prioridad', $prioridad);
        }

        $diasExpr = 'GREATEST(0, DATEDIFF(CURDATE(), DATE(municipalidad_fechaInicio)))';
        $rangoExpr = "CASE
            WHEN {$diasExpr} <= 3 THEN '0–3 días'
            WHEN {$diasExpr} <= 7 THEN '4–7 días'
            WHEN {$diasExpr} <= 15 THEN '8–15 días'
            ELSE '+15 días'
        END";

        $resultados = $builder->select(
                "{$rangoExpr} as rango, COUNT(*) as cantidad",
                false
            )
            ->groupBy('rango')
            ->get()
            ->getResultArray();

        $orden = ['0–3 días', '4–7 días', '8–15 días', '+15 días'];
        $colores = [
            '0–3 días' => '#198754',
            '4–7 días' => '#ffc107',
            '8–15 días' => '#fd7e14',
            '+15 días' => '#dc3545',
        ];

        $conteos = array_fill_keys($orden, 0);
        foreach ($resultados as $fila) {
            $rango = $fila['rango'] ?? '';
            if (isset($conteos[$rango])) {
                $conteos[$rango] = (int) $fila['cantidad'];
            }
        }

        $total = array_sum($conteos);
        $datos = [];
        $valores = [];
        foreach ($orden as $rango) {
            $cantidad = $conteos[$rango];
            $valores[] = $cantidad;
            $datos[] = [
                'label' => $rango,
                'valor' => $cantidad,
                'porcentaje' => $total > 0 ? round(($cantidad / $total) * 100, 1) : 0,
                'color' => $colores[$rango],
            ];
        }

        return $this->respond([
            'labels' => $orden,
            'series' => [
                [
                    'name' => 'Abiertos',
                    'data' => $valores,
                    'color' => '#3A3972',
                ],
            ],
            'colors' => array_values($colores),
            'datos' => $datos,
            'total' => $total,
            'filtros_aplicados' => [
                'prioridad' => $prioridad ?: null,
            ],
        ]);
    }

    /**
     * Tiempo promedio de reparación en obra (cronómetro) por motivo, en minutos.
     * Endpoint: GET /api/analisis/tiempo-promedio-por-motivo
     * Filtros: fecha_desde, fecha_hasta, motivo
     */
    public function getTiempoPromedioPorMotivo()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $motivo = $this->request->getGet('motivo');

        $tiempoReparacionModel = new Tiempo_reparacionModel();
        $builderTiempo = $tiempoReparacionModel->builder();

        if ($fechaDesde) {
            $builderTiempo->where('DATE(fecha_registro) >=', $fechaDesde);
        }
        if ($fechaHasta) {
            $builderTiempo->where('DATE(fecha_registro) <=', $fechaHasta);
        }
        if ($motivo && $motivo !== 'Todos') {
            $builderTiempo->where('motivo_reclamo', $motivo);
        }

        $resultados = $builderTiempo->select('motivo_reclamo, AVG(tiempo_minutos) as tiempo_promedio, COUNT(*) as cantidad_registros')
            ->where('motivo_reclamo IS NOT NULL')
            ->where('motivo_reclamo !=', '')
            ->groupBy('motivo_reclamo')
            ->get()
            ->getResultArray();

        $porMotivo = [];
        foreach ($resultados as $resultado) {
            $porMotivo[$resultado['motivo_reclamo']] = [
                'valor' => round((float) $resultado['tiempo_promedio'], 1),
                'cantidad_registros' => (int) $resultado['cantidad_registros'],
            ];
        }

        $colores = [
            '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd',
            '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf',
        ];

        // Catálogo + motivos de reclamos del período + extras solo en tiempos
        $listaMotivos = $this->listaMotivosAnalisis($fechaDesde, $fechaHasta, array_keys($porMotivo));

        // Si filtran un motivo puntual, mostrar solo ese (pero con ceros si no hay datos)
        if ($motivo && $motivo !== 'Todos') {
            $listaMotivos = [$motivo];
        }

        $datos = [];
        $total = 0;
        foreach ($listaMotivos as $index => $motivoItem) {
            $valor = $porMotivo[$motivoItem]['valor'] ?? 0;
            $cantidad = $porMotivo[$motivoItem]['cantidad_registros'] ?? 0;
            $total += $cantidad;
            $datos[] = [
                'label' => $motivoItem,
                'valor' => $valor,
                'valor_minutos' => $valor,
                'cantidad_registros' => $cantidad,
                'color' => $colores[$index % count($colores)],
            ];
        }

        usort($datos, static function ($a, $b) {
            return $b['valor'] <=> $a['valor'];
        });

        return $this->respond([
            'periodo' => date('Y-m'),
            'unidad' => 'minutos',
            'datos' => $datos,
            'total' => $total,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde ?? null,
                'fecha_hasta' => $fechaHasta ?? null,
                'motivo' => $motivo ?? 'Todos',
            ],
        ]);
    }

    /**
     * Evolución del tiempo promedio de reparación en obra (cronómetro), en minutos.
     * Endpoint: GET /api/analisis/evolucion-tiempo-promedio
     * Filtros: fecha_desde, fecha_hasta, granularidad (diario, semanal, mensual), motivo
     */
    public function getEvolucionTiempoPromedio()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $granularidad = $this->request->getGet('granularidad') ?? 'semanal';
        $motivo = $this->request->getGet('motivo');

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

        switch ($granularidad) {
            case 'diario':
                $builder->select("DATE(fecha_registro) as fecha, motivo_reclamo, AVG(tiempo_minutos) as tiempo_promedio, MIN(DATE(fecha_registro)) as fecha_min, MAX(DATE(fecha_registro)) as fecha_max");
                $builder->groupBy('DATE(fecha_registro), motivo_reclamo');
                break;
            case 'mensual':
                $builder->select("YEAR(fecha_registro) as año, MONTH(fecha_registro) as mes, motivo_reclamo, AVG(tiempo_minutos) as tiempo_promedio, MIN(DATE(fecha_registro)) as fecha_min, MAX(DATE(fecha_registro)) as fecha_max");
                $builder->groupBy('YEAR(fecha_registro), MONTH(fecha_registro), motivo_reclamo');
                break;
            case 'semanal':
            default:
                $granularidad = 'semanal';
                $builder->select("YEAR(fecha_registro) as año, WEEK(fecha_registro) as semana, motivo_reclamo, AVG(tiempo_minutos) as tiempo_promedio, MIN(DATE(fecha_registro)) as fecha_min, MAX(DATE(fecha_registro)) as fecha_max");
                $builder->groupBy('YEAR(fecha_registro), WEEK(fecha_registro), motivo_reclamo');
                break;
        }

        if ($granularidad === 'diario') {
            $resultados = $builder->orderBy('fecha', 'ASC')
                ->orderBy('motivo_reclamo', 'ASC')
                ->get()
                ->getResultArray();
        } elseif ($granularidad === 'mensual') {
            $resultados = $builder->orderBy('año', 'ASC')
                ->orderBy('mes', 'ASC')
                ->orderBy('motivo_reclamo', 'ASC')
                ->get()
                ->getResultArray();
        } else {
            $resultados = $builder->orderBy('año', 'ASC')
                ->orderBy('semana', 'ASC')
                ->orderBy('motivo_reclamo', 'ASC')
                ->get()
                ->getResultArray();
        }

        $datosPorFecha = [];
        $metaPeriodo = [];
        foreach ($resultados as $resultado) {
            if ($granularidad === 'diario') {
                $clave = $resultado['fecha'];
                $etiqueta = date('d/m/Y', strtotime($resultado['fecha']));
            } elseif ($granularidad === 'mensual') {
                $clave = $resultado['año'] . '-' . str_pad($resultado['mes'], 2, '0', STR_PAD_LEFT);
                $etiqueta = $this->labelMes((int) $resultado['año'], (int) $resultado['mes']);
            } else {
                $clave = $resultado['año'] . '-W' . str_pad($resultado['semana'], 2, '0', STR_PAD_LEFT);
                $etiqueta = null; // se arma con min/max
            }

            if (!isset($datosPorFecha[$clave])) {
                $datosPorFecha[$clave] = [];
                $metaPeriodo[$clave] = [
                    'label' => $etiqueta,
                    'fecha_min' => $resultado['fecha_min'] ?? null,
                    'fecha_max' => $resultado['fecha_max'] ?? null,
                ];
            } else {
                $minActual = $metaPeriodo[$clave]['fecha_min'];
                $maxActual = $metaPeriodo[$clave]['fecha_max'];
                $minNuevo = $resultado['fecha_min'] ?? null;
                $maxNuevo = $resultado['fecha_max'] ?? null;
                if ($minNuevo && (!$minActual || $minNuevo < $minActual)) {
                    $metaPeriodo[$clave]['fecha_min'] = $minNuevo;
                }
                if ($maxNuevo && (!$maxActual || $maxNuevo > $maxActual)) {
                    $metaPeriodo[$clave]['fecha_max'] = $maxNuevo;
                }
            }
            $datosPorFecha[$clave][$resultado['motivo_reclamo']] = round((float) $resultado['tiempo_promedio'], 1);
        }

        ksort($datosPorFecha);
        $labels = [];
        foreach (array_keys($datosPorFecha) as $clave) {
            if ($granularidad === 'diario' || $granularidad === 'mensual') {
                $labels[] = $metaPeriodo[$clave]['label'] ?: $clave;
            } else {
                $labels[] = $this->labelRangoFechas(
                    $metaPeriodo[$clave]['fecha_min'] ?? null,
                    $metaPeriodo[$clave]['fecha_max'] ?? null
                ) ?: $clave;
            }
        }

        $extrasTiempo = [];
        foreach ($datosPorFecha as $porFecha) {
            foreach (array_keys($porFecha) as $motivoExtra) {
                $extrasTiempo[] = $motivoExtra;
            }
        }
        $listaMotivos = $this->listaMotivosAnalisis($fechaDesde, $fechaHasta, $extrasTiempo);
        if ($motivo && $motivo !== 'Todos') {
            $listaMotivos = [$motivo];
        }

        $colores = [
            '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd',
            '#8c564b', '#e377c2', '#17becf', '#bcbd22', '#7f7f7f',
            '#aec7e8', '#ffbb78', '#98df8a', '#ff9896', '#c5b0d5',
        ];

        $claves = array_keys($datosPorFecha);
        $series = [];
        foreach ($listaMotivos as $index => $motivoItem) {
            $data = [];
            foreach ($claves as $clave) {
                $data[] = isset($datosPorFecha[$clave][$motivoItem])
                    ? $datosPorFecha[$clave][$motivoItem]
                    : null;
            }
            $series[] = [
                'name' => $motivoItem,
                'data' => $data,
                'color' => $colores[$index % count($colores)],
            ];
        }

        return $this->respond([
            'periodo' => date('Y-m'),
            'unidad' => 'minutos',
            'granularidad' => $granularidad,
            'labels' => $labels,
            'series' => $series,
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'granularidad' => $granularidad,
                'motivo' => $motivo ?? 'Todos',
            ],
        ]);
    }

    /**
     * Obtiene la evolución de reclamos de alta prioridad.
     * Endpoint conservado por si se reactiva; no se muestra en la UI de Análisis.
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

        $builder = $this->builderReclamosVisibles();
        $builder->where('prioridad', 'Alta')
                ->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde)
                ->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);

        switch($granularidad) {
            case 'semanal':
                $builder->select("YEAR(municipalidad_fechaInicio) as año, WEEK(municipalidad_fechaInicio) as semana, COUNT(*) as cantidad, MIN(DATE(municipalidad_fechaInicio)) as fecha_min, MAX(DATE(municipalidad_fechaInicio)) as fecha_max");
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
                $labels[] = $this->labelRangoFechas($resultado['fecha_min'] ?? null, $resultado['fecha_max'] ?? null);
            } else {
                $ts = strtotime((string) $resultado['fecha']);
                $labels[] = $ts ? date('d/m/Y', $ts) : $resultado['fecha'];
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
     * Filtros: fecha_desde, fecha_hasta, granularidad (diario|semanal|mensual),
     *          categoria (nombre de tipo_material), material
     *
     * - categorias_disponibles: tipos con consumo en el período
     * - materiales_disponibles: materiales con consumo (acotados por categoría si hay filtro)
     * - series: top 5 si material=Todos; solo el seleccionado si hay filtro
     */
    public function getConsumoMateriales()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $material = $this->request->getGet('material');
        $categoria = $this->request->getGet('categoria');
        $granularidad = $this->request->getGet('granularidad') ?? 'mensual';

        if (!$fechaDesde) {
            $fechaDesde = date('Y-m-d', strtotime('-6 months'));
        }
        if (!$fechaHasta) {
            $fechaHasta = date('Y-m-d');
        }

        if (!in_array($granularidad, ['diario', 'semanal', 'mensual'], true)) {
            $granularidad = 'mensual';
        }

        $filtroCategoria = $categoria && $categoria !== 'Todas' && $categoria !== 'Todos';
        $filtroMaterial = $material && $material !== 'Todos';

        $db = \Config\Database::connect();

        $filtrosBase = [
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
            'granularidad' => $granularidad,
            'categoria' => $filtroCategoria ? $categoria : 'Todas',
            'material' => $filtroMaterial ? $material : 'Todos',
        ];

        // Categorías con consumo en el período (desplegable)
        $categoriasRows = $db->table('material_reclamo mr')
            ->select('tm.nombre as categoria, SUM(mr.cantidad) as total')
            ->join('material m', 'm.id = mr.material_id')
            ->join('tipo_material tm', 'tm.id = m.idTipo', 'left')
            ->where('DATE(mr.fecha) >=', $fechaDesde)
            ->where('DATE(mr.fecha) <=', $fechaHasta)
            ->where('tm.nombre IS NOT NULL')
            ->where('tm.nombre !=', '')
            ->groupBy('tm.id, tm.nombre')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        $categoriasDisponibles = array_column($categoriasRows, 'categoria');

        // Materiales con consumo (opcionalmente acotados por categoría)
        $builderMats = $db->table('material_reclamo mr')
            ->select('m.nombre, SUM(mr.cantidad) as total')
            ->join('material m', 'm.id = mr.material_id')
            ->join('tipo_material tm', 'tm.id = m.idTipo', 'left')
            ->where('DATE(mr.fecha) >=', $fechaDesde)
            ->where('DATE(mr.fecha) <=', $fechaHasta)
            ->groupBy('m.id, m.nombre')
            ->orderBy('total', 'DESC');

        if ($filtroCategoria) {
            $builderMats->where('tm.nombre', $categoria);
        }

        $todosMateriales = $builderMats->get()->getResultArray();
        $materialesDisponibles = array_column($todosMateriales, 'nombre');

        $respuestaVacia = static function (
            array $categorias,
            array $materiales,
            array $filtros,
            string $mensaje
        ) {
            return [
                'labels' => [],
                'series' => [],
                'categorias_disponibles' => $categorias,
                'materiales_disponibles' => $materiales,
                'mensaje' => $mensaje,
                'filtros_aplicados' => $filtros,
            ];
        };

        if ($filtroCategoria && !in_array($categoria, $categoriasDisponibles, true)) {
            return $this->respond($respuestaVacia(
                $categoriasDisponibles,
                [],
                $filtrosBase,
                'La categoría seleccionada no tiene consumo en el período'
            ));
        }

        if (empty($materialesDisponibles)) {
            return $this->respond($respuestaVacia(
                $categoriasDisponibles,
                [],
                $filtrosBase,
                'No hay datos de consumo de materiales en el período seleccionado'
            ));
        }

        if ($filtroMaterial) {
            if (!in_array($material, $materialesDisponibles, true)) {
                return $this->respond($respuestaVacia(
                    $categoriasDisponibles,
                    $materialesDisponibles,
                    $filtrosBase,
                    'El material seleccionado no tiene consumo en el período'
                ));
            }
            $materialesSerie = [$material];
        } else {
            $materialesSerie = array_slice($materialesDisponibles, 0, 5);
        }

        $builder = $db->table('material_reclamo mr');
        $builder->join('material m', 'm.id = mr.material_id')
            ->join('tipo_material tm', 'tm.id = m.idTipo', 'left')
            ->where('DATE(mr.fecha) >=', $fechaDesde)
            ->where('DATE(mr.fecha) <=', $fechaHasta)
            ->whereIn('m.nombre', $materialesSerie);

        if ($filtroCategoria) {
            $builder->where('tm.nombre', $categoria);
        }

        switch ($granularidad) {
            case 'diario':
                $builder->select(
                    "DATE(mr.fecha) as fecha, m.nombre as material, SUM(mr.cantidad) as cantidad",
                    false
                )
                    ->groupBy('DATE(mr.fecha), m.id, m.nombre')
                    ->orderBy('fecha', 'ASC');
                break;
            case 'semanal':
                $builder->select(
                    "YEAR(mr.fecha) as año, WEEK(mr.fecha) as semana, m.nombre as material, "
                    . "SUM(mr.cantidad) as cantidad, "
                    . "MIN(DATE(mr.fecha)) as fecha_min, MAX(DATE(mr.fecha)) as fecha_max",
                    false
                )
                    ->groupBy('YEAR(mr.fecha), WEEK(mr.fecha), m.id, m.nombre')
                    ->orderBy('año', 'ASC')
                    ->orderBy('semana', 'ASC');
                break;
            case 'mensual':
            default:
                $builder->select(
                    "YEAR(mr.fecha) as año, MONTH(mr.fecha) as mes, m.nombre as material, SUM(mr.cantidad) as cantidad",
                    false
                )
                    ->groupBy('YEAR(mr.fecha), MONTH(mr.fecha), m.id, m.nombre')
                    ->orderBy('año', 'ASC')
                    ->orderBy('mes', 'ASC');
                break;
        }

        $resultados = $builder->get()->getResultArray();

        $datosPorFecha = [];
        $metaPeriodo = [];
        foreach ($resultados as $resultado) {
            if ($granularidad === 'diario') {
                $clave = $resultado['fecha'];
                $etiqueta = date('d/m/Y', strtotime($resultado['fecha']));
            } elseif ($granularidad === 'semanal') {
                $clave = $resultado['año'] . '-W' . str_pad((string) $resultado['semana'], 2, '0', STR_PAD_LEFT);
                $etiqueta = null;
            } else {
                $clave = $resultado['año'] . '-' . str_pad((string) $resultado['mes'], 2, '0', STR_PAD_LEFT);
                $etiqueta = $this->labelMes((int) $resultado['año'], (int) $resultado['mes']);
            }

            if (!isset($datosPorFecha[$clave])) {
                $datosPorFecha[$clave] = [];
                $metaPeriodo[$clave] = [
                    'label' => $etiqueta,
                    'fecha_min' => $resultado['fecha_min'] ?? ($granularidad === 'diario' ? $clave : null),
                    'fecha_max' => $resultado['fecha_max'] ?? ($granularidad === 'diario' ? $clave : null),
                    'año' => isset($resultado['año']) ? (int) $resultado['año'] : null,
                    'mes' => isset($resultado['mes']) ? (int) $resultado['mes'] : null,
                ];
            } elseif ($granularidad === 'semanal') {
                $minNuevo = $resultado['fecha_min'] ?? null;
                $maxNuevo = $resultado['fecha_max'] ?? null;
                if ($minNuevo && (!$metaPeriodo[$clave]['fecha_min'] || $minNuevo < $metaPeriodo[$clave]['fecha_min'])) {
                    $metaPeriodo[$clave]['fecha_min'] = $minNuevo;
                }
                if ($maxNuevo && (!$metaPeriodo[$clave]['fecha_max'] || $maxNuevo > $metaPeriodo[$clave]['fecha_max'])) {
                    $metaPeriodo[$clave]['fecha_max'] = $maxNuevo;
                }
            }

            $datosPorFecha[$clave][$resultado['material']] = (int) $resultado['cantidad'];
        }

        ksort($datosPorFecha);
        $labels = [];
        foreach (array_keys($datosPorFecha) as $clave) {
            if ($granularidad === 'diario' || $granularidad === 'mensual') {
                $labels[] = $metaPeriodo[$clave]['label'] ?: $clave;
            } else {
                $labels[] = $this->labelRangoFechas(
                    $metaPeriodo[$clave]['fecha_min'] ?? null,
                    $metaPeriodo[$clave]['fecha_max'] ?? null
                ) ?: $clave;
            }
        }

        $colores = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#17becf'];
        $claves = array_keys($datosPorFecha);
        $series = [];
        foreach ($materialesSerie as $index => $mat) {
            $data = [];
            foreach ($claves as $clave) {
                $data[] = $datosPorFecha[$clave][$mat] ?? 0;
            }
            $series[] = [
                'name' => $mat,
                'data' => $data,
                'color' => $colores[$index % count($colores)],
            ];
        }

        return $this->respond([
            'labels' => $labels,
            'series' => $series,
            'categorias_disponibles' => $categoriasDisponibles,
            'materiales_disponibles' => $materialesDisponibles,
            'filtros_aplicados' => $filtrosBase,
        ]);
    }

    /**
     * Obtiene reclamos cerrados vs abiertos (cohorte por fecha de alta).
     * Endpoint conservado por si se reactiva; no se muestra en la UI de Análisis.
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

        $builder = $this->builderReclamosVisibles();
        $builder->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde)
                ->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);

        switch($granularidad) {
            case 'semanal':
                $builder->select("YEAR(municipalidad_fechaInicio) as año, WEEK(municipalidad_fechaInicio) as periodo, cerrado, COUNT(*) as cantidad, MIN(DATE(municipalidad_fechaInicio)) as fecha_min, MAX(DATE(municipalidad_fechaInicio)) as fecha_max");
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
        $metaPeriodo = [];
        foreach ($resultados as $resultado) {
            if ($granularidad === 'semanal') {
                $fecha = $resultado['año'] . '-W' . str_pad($resultado['periodo'], 2, '0', STR_PAD_LEFT);
            } else {
                $fecha = $resultado['año'] . '-' . str_pad($resultado['periodo'], 2, '0', STR_PAD_LEFT);
            }
            
            if (!isset($datosPorFecha[$fecha])) {
                $datosPorFecha[$fecha] = ['cerrados' => 0, 'abiertos' => 0];
                $metaPeriodo[$fecha] = [
                    'fecha_min' => $resultado['fecha_min'] ?? null,
                    'fecha_max' => $resultado['fecha_max'] ?? null,
                    'año' => (int) $resultado['año'],
                    'periodo' => (int) $resultado['periodo'],
                ];
            } elseif ($granularidad === 'semanal') {
                $minNuevo = $resultado['fecha_min'] ?? null;
                $maxNuevo = $resultado['fecha_max'] ?? null;
                if ($minNuevo && (!$metaPeriodo[$fecha]['fecha_min'] || $minNuevo < $metaPeriodo[$fecha]['fecha_min'])) {
                    $metaPeriodo[$fecha]['fecha_min'] = $minNuevo;
                }
                if ($maxNuevo && (!$metaPeriodo[$fecha]['fecha_max'] || $maxNuevo > $metaPeriodo[$fecha]['fecha_max'])) {
                    $metaPeriodo[$fecha]['fecha_max'] = $maxNuevo;
                }
            }
            
            if ($resultado['cerrado'] == 1) {
                $datosPorFecha[$fecha]['cerrados'] = (int) $resultado['cantidad'];
            } else {
                $datosPorFecha[$fecha]['abiertos'] = (int) $resultado['cantidad'];
            }
        }

        ksort($datosPorFecha);
        $labels = [];
        foreach (array_keys($datosPorFecha) as $clave) {
            if ($granularidad === 'semanal') {
                $labels[] = $this->labelRangoFechas(
                    $metaPeriodo[$clave]['fecha_min'] ?? null,
                    $metaPeriodo[$clave]['fecha_max'] ?? null
                ) ?: $clave;
            } else {
                $labels[] = $this->labelMes(
                    (int) ($metaPeriodo[$clave]['año'] ?? 0),
                    (int) ($metaPeriodo[$clave]['periodo'] ?? 0)
                );
            }
        }
        
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
                ['name' => 'Cerrados', 'data' => $cerrados, 'color' => '#000000'],
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
     * Obtiene la tasa de cierre de reclamos (cohorte por fecha de alta).
     * Endpoint conservado por si se reactiva; no se muestra en la UI de Análisis.
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

        $builder = $this->builderReclamosVisibles();
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
                                  COUNT(*) as total,
                                  MIN(DATE(municipalidad_fechaInicio)) as fecha_min,
                                  MAX(DATE(municipalidad_fechaInicio)) as fecha_max");
                $builder->groupBy('YEAR(municipalidad_fechaInicio), WEEK(municipalidad_fechaInicio)');
                break;
        }

        $resultados = $builder->orderBy('año', 'ASC')->orderBy('periodo', 'ASC')->get()->getResultArray();

        $labels = [];
        $tasas = [];

        foreach ($resultados as $resultado) {
            if ($granularidad === 'mensual') {
                $labels[] = $this->labelMes((int) $resultado['año'], (int) $resultado['periodo']);
            } else {
                $labels[] = $this->labelRangoFechas($resultado['fecha_min'] ?? null, $resultado['fecha_max'] ?? null);
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
     * Datos para mapa de calor de zonas (Análisis).
     * Endpoint: GET /api/analisis/mapa-calor-zonas
     * Filtros: fecha_desde, fecha_hasta, estado, prioridad, motivo
     *
     * Cruza reclamo (conteo) con direccion (lat/lng). Solo puntos con coordenadas.
     */
    public function getMapaCalorZonas()
    {
        $fechaDesde = $this->request->getGet('fecha_desde');
        $fechaHasta = $this->request->getGet('fecha_hasta');
        $estado = $this->request->getGet('estado');
        $prioridad = $this->request->getGet('prioridad');
        $motivo = $this->request->getGet('motivo');

        $builder = $this->builderReclamosVisibles();

        if ($fechaDesde) {
            $builder->where('DATE(municipalidad_fechaInicio) >=', $fechaDesde);
        }
        if ($fechaHasta) {
            $builder->where('DATE(municipalidad_fechaInicio) <=', $fechaHasta);
        }
        $this->aplicarFiltroEstadoAnalitico($builder, $estado);
        if ($prioridad && $prioridad !== 'Todas' && $prioridad !== '') {
            $builder->where('prioridad', $prioridad);
        }
        if ($motivo && $motivo !== 'Todos' && $motivo !== '') {
            $builder->where('municipalidad_motivo', $motivo);
        }

        $reclamos = $builder->select(
                'municipalidad_domicilio, municipalidad_numeroDomicilio, COUNT(*) as cantidad',
                false
            )
            ->where('municipalidad_domicilio IS NOT NULL')
            ->where('municipalidad_domicilio !=', '')
            ->groupBy('municipalidad_domicilio, municipalidad_numeroDomicilio')
            ->get()
            ->getResultArray();

        $direcciones = (new DireccionModel())->builder()
            ->select('domicilio, numero_domicilio, latitud, longitud')
            ->where('latitud IS NOT NULL')
            ->where('longitud IS NOT NULL')
            ->where('latitud !=', '')
            ->where('longitud !=', '')
            ->get()
            ->getResultArray();

        $lookup = [];
        foreach ($direcciones as $dir) {
            $clave = $this->claveDireccionMapaCalor(
                $dir['domicilio'] ?? '',
                $dir['numero_domicilio'] ?? ''
            );
            if ($clave === '') {
                continue;
            }
            $lookup[$clave] = $dir;
        }

        $datos = [];
        $totalReclamos = 0;
        $conCoordenadas = 0;
        $sinCoordenadas = 0;

        foreach ($reclamos as $fila) {
            $cantidad = (int) ($fila['cantidad'] ?? 0);
            $totalReclamos += $cantidad;
            $clave = $this->claveDireccionMapaCalor(
                $fila['municipalidad_domicilio'] ?? '',
                $fila['municipalidad_numeroDomicilio'] ?? ''
            );
            $dir = $lookup[$clave] ?? null;
            if (!$dir) {
                $sinCoordenadas += $cantidad;
                continue;
            }

            $lat = (float) $dir['latitud'];
            $lng = (float) $dir['longitud'];
            if ($lat == 0.0 && $lng == 0.0) {
                $sinCoordenadas += $cantidad;
                continue;
            }

            $conCoordenadas += $cantidad;
            $nro = trim((string) ($fila['municipalidad_numeroDomicilio'] ?? ''));
            $datos[] = [
                'lat' => $lat,
                'lng' => $lng,
                'cantidad' => $cantidad,
                'domicilio' => trim((string) ($fila['municipalidad_domicilio'] ?? ''))
                    . ($nro !== '' ? ' ' . $nro : ''),
            ];
        }

        usort($datos, static function ($a, $b) {
            return $b['cantidad'] <=> $a['cantidad'];
        });

        return $this->respond([
            'centro' => [
                'lat' => -31.427,
                'lng' => -62.082,
                'zoom' => 13,
                'ciudad' => 'San Francisco, Córdoba',
            ],
            'datos' => $datos,
            'total' => $totalReclamos,
            'con_coordenadas' => $conCoordenadas,
            'sin_coordenadas' => $sinCoordenadas,
            'puntos' => count($datos),
            'filtros_aplicados' => [
                'fecha_desde' => $fechaDesde ?: null,
                'fecha_hasta' => $fechaHasta ?: null,
                'estado' => $estado ?: null,
                'prioridad' => $prioridad ?: null,
                'motivo' => $motivo ?: null,
            ],
        ]);
    }

    private function claveDireccionMapaCalor($domicilio, $numero): string
    {
        $dom = mb_strtoupper(trim((string) $domicilio), 'UTF-8');
        if ($dom === '') {
            return '';
        }
        $nro = trim((string) $numero);
        return $dom . '|' . $nro;
    }
}

