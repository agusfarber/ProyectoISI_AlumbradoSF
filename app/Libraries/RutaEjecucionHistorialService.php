<?php

namespace App\Libraries;

use App\Models\RutaEjecucionEventoModel;
use App\Models\RutaEjecucionModel;

/**
 * Registro de eventos de historial de ejecución de hojas de ruta (extensible por `tipo` + metadata JSON).
 */
class RutaEjecucionHistorialService
{
    public const TIPO_RUTA_INICIO          = 'ejecucion_ruta_inicio';
    public const TIPO_RUTA_FIN           = 'ejecucion_ruta_fin';
    public const TIPO_RECLAMO_INICIO     = 'ejecucion_reclamo_inicio';
    public const TIPO_RECLAMO_FIN        = 'ejecucion_reclamo_fin';
    public const TIPO_RECLAMO_ESTADO    = 'reclamo_cambio_estado';

    public static function findActiveEjecucionIdByRutaId(int $rutaId): ?int
    {
        $m = new RutaEjecucionModel();
        $row = $m->where('ruta_id', $rutaId)->where('fin_at', null)->orderBy('id', 'DESC')->first();

        return $row ? (int) $row['id'] : null;
    }

    public static function findActiveEjecucionIdByReclamoId(int $reclamoId): ?int
    {
        $link = self::findRutaReclamoLinkRutaAsignada($reclamoId);
        if (! $link) {
            return null;
        }

        return self::findActiveEjecucionIdByRutaId((int) $link['ruta_id']);
    }

    /**
     * Fila ruta_reclamo del reclamo en una hoja actualmente asignada a cuadrilla.
     * Si el reclamo figuró en rutas viejas (finalizadas / desasignadas), puede haber varias filas: se toma la ruta asignada más reciente.
     *
     * @return array<string, mixed>|null
     */
    public static function findRutaReclamoLinkRutaAsignada(int $reclamoId): ?array
    {
        if ($reclamoId < 1) {
            return null;
        }

        $db = \Config\Database::connect();
        $row = $db->table('ruta_reclamo rr')
            ->select('rr.*')
            ->join('ruta r', 'r.id = rr.ruta_id')
            ->where('rr.reclamo_id', $reclamoId)
            ->where('r.asignada', 1)
            ->where('r.cuadrilla_id IS NOT NULL', null, false)
            ->orderBy('r.id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    public static function insertEvent(?int $ejecucionId, string $tipo, ?int $reclamoId, ?int $usuarioId, ?array $metadata = null): void
    {
        if (! $ejecucionId) {
            return;
        }

        $evt = new RutaEjecucionEventoModel();
        $evt->insert([
            'ruta_ejecucion_id' => $ejecucionId,
            'tipo'              => $tipo,
            'reclamo_id'        => $reclamoId,
            'usuario_id'        => $usuarioId,
            'ocurrido_at'       => date('Y-m-d H:i:s'),
            'metadata'          => $metadata !== null && $metadata !== []
                ? json_encode($metadata, JSON_UNESCAPED_UNICODE)
                : null,
        ]);
    }

    /**
     * Reconstruye el cronómetro de “obra” por reclamo desde eventos inicio/fin (tiempo real de reloj, no solo la sesión del navegador).
     *
     * @return array<int, array{activo: bool, acumulado_ms: int, inicio_segmento_at: ?string}>
     */
    public static function computeSesionesReparacionDesdeEventos(int $rutaEjecucionId): array
    {
        $evt = new RutaEjecucionEventoModel();
        $rows = $evt->where('ruta_ejecucion_id', $rutaEjecucionId)
            ->whereIn('tipo', [self::TIPO_RECLAMO_INICIO, self::TIPO_RECLAMO_FIN])
            ->orderBy('ocurrido_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return self::aggregateSesionesReparacionDesdeFilasEvento($rows);
    }

    /**
     * Indica si el reclamo tiene una sesión de obra activa (inicio sin fin) en la ejecución dada.
     */
    public static function reclamoTieneObraActivaEnEjecucion(int $reclamoId, int $rutaEjecucionId): bool
    {
        if ($reclamoId < 1 || $rutaEjecucionId < 1) {
            return false;
        }

        $sesiones = self::computeSesionesReparacionDesdeEventos($rutaEjecucionId);

        return ! empty($sesiones[$reclamoId]['activo']);
    }

    /**
     * Igual que {@see computeSesionesReparacionDesdeEventos} pero uniendo todas las ejecuciones de una misma ruta.
     * Así un reclamo dejado pendiente en un día conserva el acumulado al abrir una nueva hoja de ruta.
     *
     * @return array<int, array{activo: bool, acumulado_ms: int, inicio_segmento_at: ?string}>
     */
    public static function computeSesionesReparacionPorRutaId(int $rutaId): array
    {
        if ($rutaId < 1) {
            return [];
        }

        $db = \Config\Database::connect();
        $rows = $db->table('ruta_ejecucion_evento e')
            ->select('e.*')
            ->join('ruta_ejecucion re', 're.id = e.ruta_ejecucion_id')
            ->where('re.ruta_id', $rutaId)
            ->whereIn('e.tipo', [self::TIPO_RECLAMO_INICIO, self::TIPO_RECLAMO_FIN])
            ->orderBy('e.ocurrido_at', 'ASC')
            ->orderBy('e.id', 'ASC')
            ->get()
            ->getResultArray();

        return self::aggregateSesionesReparacionDesdeFilasEvento($rows);
    }

    /**
     * Cronómetro de obra por reclamo usando todos los eventos inicio/fin de ese reclamo (cualquier hoja de ruta / ejecución).
     * Necesario cuando el reclamo pasa a una hoja nueva: los tiempos quedaron registrados bajo ejecuciones de la ruta anterior.
     *
     * @param list<int> $reclamoIds
     *
     * @return array<int, array{activo: bool, acumulado_ms: int, inicio_segmento_at: ?string}>
     */
    public static function computeSesionesReparacionPorReclamoIds(array $reclamoIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $reclamoIds), static fn (int $id) => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $evt = new RutaEjecucionEventoModel();
        $rows = $evt->whereIn('reclamo_id', $ids)
            ->whereIn('tipo', [self::TIPO_RECLAMO_INICIO, self::TIPO_RECLAMO_FIN])
            ->orderBy('ocurrido_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return self::aggregateSesionesReparacionDesdeFilasEvento($rows);
    }

    /**
     * Cronómetro por reclamo al cierre de una ejecución: suma segmentos inicio/fin de ese reclamo
     * en cualquier hoja, con eventos hasta fin_at de esta ejecución (no incluye trabajo posterior).
     * Usado en historial para reclamos que llegaron pendientes desde ejecuciones anteriores.
     *
     * @param list<int> $reclamoIds
     *
     * @return array<int, array{activo: bool, acumulado_ms: int, inicio_segmento_at: ?string}>
     */
    public static function computeSesionesReparacionHastaCierreEjecucion(int $ejecucionId, array $reclamoIds): array
    {
        if ($ejecucionId < 1) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $reclamoIds), static fn (int $id) => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $ej = (new RutaEjecucionModel())->find($ejecucionId);
        $finAt = $ej['fin_at'] ?? null;
        if ($finAt === null || $finAt === '') {
            return self::computeSesionesReparacionDesdeEventos($ejecucionId);
        }

        $evt = new RutaEjecucionEventoModel();
        $rows = $evt->whereIn('reclamo_id', $ids)
            ->whereIn('tipo', [self::TIPO_RECLAMO_INICIO, self::TIPO_RECLAMO_FIN])
            ->where('ocurrido_at <=', (string) $finAt)
            ->orderBy('ocurrido_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return self::aggregateSesionesReparacionDesdeFilasEvento($rows);
    }

    /**
     * @param list<array<string, mixed>> $rows Filas de ruta_ejecucion_evento ya filtradas por tipo inicio/fin, orden global por tiempo.
     *
     * @return array<int, array{activo: bool, acumulado_ms: int, inicio_segmento_at: ?string}>
     */
    private static function aggregateSesionesReparacionDesdeFilasEvento(array $rows): array
    {
        $byReclamo = [];
        foreach ($rows as $row) {
            $rid = isset($row['reclamo_id']) ? (int) $row['reclamo_id'] : 0;
            if ($rid < 1) {
                continue;
            }
            if (! isset($byReclamo[$rid])) {
                $byReclamo[$rid] = [];
            }
            $byReclamo[$rid][] = $row;
        }

        $out = [];
        foreach ($byReclamo as $rid => $events) {
            $open       = false;
            $acumMs     = 0;
            $lastOpenAt = null;
            foreach ($events as $ev) {
                $tipo = (string) ($ev['tipo'] ?? '');
                $at   = $ev['ocurrido_at'] ?? null;
                if (! $at) {
                    continue;
                }
                if ($tipo === self::TIPO_RECLAMO_INICIO) {
                    if (! $open) {
                        $open       = true;
                        $lastOpenAt = (string) $at;
                    }

                    continue;
                }
                if ($tipo === self::TIPO_RECLAMO_FIN) {
                    if ($open && $lastOpenAt !== null) {
                        $acumMs += self::diffDatetimeMs($lastOpenAt, (string) $at);
                        $open       = false;
                        $lastOpenAt = null;
                    }
                }
            }
            $out[$rid] = [
                'activo'             => $open,
                'acumulado_ms'       => $acumMs,
                'inicio_segmento_at' => $open ? $lastOpenAt : null,
            ];
        }

        return $out;
    }

    /**
     * Reclamos de la hoja con un segmento de obra abierto (inicio sin fin) en la ejecución activa.
     *
     * @return list<array{reclamo_id: int, municipalidad_id: string|null}>
     */
    public static function findReclamosConObraActivaEnEjecucionActiva(int $rutaId): array
    {
        if ($rutaId < 1) {
            return [];
        }

        $ejecId = self::findActiveEjecucionIdByRutaId($rutaId);
        if (! $ejecId) {
            return [];
        }

        $sesiones   = self::computeSesionesReparacionDesdeEventos($ejecId);
        $idsActivos = [];
        foreach ($sesiones as $rid => $s) {
            if (! empty($s['activo'])) {
                $idsActivos[] = (int) $rid;
            }
        }

        if ($idsActivos === []) {
            return [];
        }

        $db = \Config\Database::connect();
        $enRuta = $db->table('ruta_reclamo')
            ->select('reclamo_id')
            ->where('ruta_id', $rutaId)
            ->whereIn('reclamo_id', $idsActivos)
            ->get()
            ->getResultArray();

        $idsEnRuta = array_values(array_unique(array_map(static fn ($r) => (int) $r['reclamo_id'], $enRuta)));
        if ($idsEnRuta === []) {
            return [];
        }

        return $db->table('reclamo')
            ->select('id AS reclamo_id, municipalidad_id')
            ->whereIn('id', $idsEnRuta)
            ->get()
            ->getResultArray();
    }

    /**
     * Estado de cada reclamo al cierre de una ejecución (congelado para historial visual).
     *
     * @param list<int> $reclamoIds
     *
     * @return array<int, string>
     */
    public static function computeEstadosReclamosAlCierreEjecucion(
        int $ejecucionId,
        ?string $inicioAt,
        ?string $finAt,
        array $reclamoIds
    ): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $reclamoIds), static fn (int $id) => $id > 0)));
        if ($ejecucionId < 1 || $finAt === null || $finAt === '' || $ids === []) {
            return [];
        }

        $db = \Config\Database::connect();
        $rows = $db->table('ruta_ejecucion_evento')
            ->whereIn('reclamo_id', $ids)
            ->where('tipo', self::TIPO_RECLAMO_ESTADO)
            ->where('ocurrido_at <=', $finAt)
            ->orderBy('ocurrido_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $nroPorId = [];
        $filasReclamo = $db->table('reclamo')
            ->select('id, municipalidad_id')
            ->whereIn('id', $ids)
            ->get()
            ->getResultArray();
        foreach ($filasReclamo as $fila) {
            $nroPorId[(int) $fila['id']] = (string) ($fila['municipalidad_id'] ?? '');
        }

        $out = [];
        foreach ($ids as $rid) {
            $estado = self::estadoReclamoEnInstanteDesdeEventos($rid, $inicioAt, $rows, $nroPorId[$rid] ?? null, true);

            foreach ($rows as $ev) {
                if ((int) ($ev['reclamo_id'] ?? 0) !== $rid) {
                    continue;
                }
                if ((int) ($ev['ruta_ejecucion_id'] ?? 0) !== $ejecucionId) {
                    continue;
                }
                $at = (string) ($ev['ocurrido_at'] ?? '');
                if ($inicioAt !== null && $inicioAt !== '' && $at < $inicioAt) {
                    continue;
                }
                if ($at > $finAt) {
                    continue;
                }
                $nuevo = self::estadoNuevoDesdeEvento($ev);
                if ($nuevo !== null) {
                    $estado = $nuevo;
                }
            }

            $out[$rid] = $estado ?? 'Asignado';
        }

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $eventosEstado
     */
    private static function estadoReclamoEnInstanteDesdeEventos(
        int $reclamoId,
        ?string $instante,
        array $eventosEstado,
        ?string $nroReclamo,
        bool $estrictoMenor
    ): ?string {
        $estado = null;
        foreach ($eventosEstado as $ev) {
            if ((int) ($ev['reclamo_id'] ?? 0) !== $reclamoId) {
                continue;
            }
            $at = (string) ($ev['ocurrido_at'] ?? '');
            if ($instante !== null && $instante !== '') {
                if ($estrictoMenor ? ($at >= $instante) : ($at > $instante)) {
                    continue;
                }
            }
            $nuevo = self::estadoNuevoDesdeEvento($ev);
            if ($nuevo !== null) {
                $estado = $nuevo;
            }
        }

        if ($estado !== null) {
            return $estado;
        }

        if ($nroReclamo === null || $nroReclamo === '' || $instante === null || $instante === '') {
            return null;
        }

        $hist = \Config\Database::connect()->table('historial_reclamo')
            ->where('nro_reclamo', $nroReclamo)
            ->where('fecha_cambio <', $instante)
            ->orderBy('fecha_cambio', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if ($hist && ! empty($hist['estado_actual'])) {
            return (string) $hist['estado_actual'];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $ev
     */
    private static function estadoNuevoDesdeEvento(array $ev): ?string
    {
        if (empty($ev['metadata'])) {
            return null;
        }
        $md = is_array($ev['metadata']) ? $ev['metadata'] : json_decode((string) $ev['metadata'], true);
        if (! is_array($md) || ! isset($md['estado_nuevo']) || $md['estado_nuevo'] === '') {
            return null;
        }

        return (string) $md['estado_nuevo'];
    }

    private static function diffDatetimeMs(string $start, string $end): int
    {
        try {
            $a = new \DateTimeImmutable($start);
            $b = new \DateTimeImmutable($end);
            $ms = ($b->getTimestamp() - $a->getTimestamp()) * 1000;

            return max(0, (int) $ms);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
