<?php

namespace App\Libraries;

/**
 * Reglas de asignación de prioridad para reclamos.
 */
class ReclamoPrioridadService
{
    /** @deprecated Usar MOTIVOS_PRIORIDAD_ALTA */
    public const MOTIVO_PRIORIDAD_ALTA = 'Postes, cables caídos o por caer (Telecom, Epec, Monet)';

    public const MOTIVOS_PRIORIDAD_ALTA = [
        'Postes, cables caídos o por caer (Telecom, Epec, Monet)',
        'Semáforos - Arreglo y sincronización',
    ];

    public const DIAS_SIN_ATENDER_PARA_ALTA = 10;

    public static function debeTenerPrioridadNula(array $reclamo): bool
    {
        if (trim((string) ($reclamo['municipalidad_estado'] ?? '')) === 'Completado') {
            return true;
        }

        return (int) ($reclamo['cerrado'] ?? 0) === 1;
    }

    public static function motivoRequierePrioridadAlta(?string $motivo): bool
    {
        $motivo = trim((string) $motivo);
        if ($motivo === '') {
            return false;
        }

        return in_array($motivo, self::MOTIVOS_PRIORIDAD_ALTA, true);
    }

    public static function estadoRequierePrioridadAlta(?string $estado): bool
    {
        return trim((string) $estado) === 'Pendiente';
    }

    public static function diasSinAtenderRequierePrioridadAlta(array $reclamo): bool
    {
        if (self::debeTenerPrioridadNula($reclamo)) {
            return false;
        }

        $fechaInicio = $reclamo['municipalidad_fechaInicio'] ?? null;
        if (empty($fechaInicio)) {
            return false;
        }

        try {
            $inicio = new \DateTimeImmutable((string) $fechaInicio);
            $ahora  = new \DateTimeImmutable('now');
        } catch (\Exception $e) {
            return false;
        }

        return $inicio->diff($ahora)->days >= self::DIAS_SIN_ATENDER_PARA_ALTA;
    }

    public static function cumpleAlgunaReglaPrioridadAlta(array $reclamo): bool
    {
        if (self::debeTenerPrioridadNula($reclamo)) {
            return false;
        }

        if (self::motivoRequierePrioridadAlta($reclamo['municipalidad_motivo'] ?? null)) {
            return true;
        }

        if (self::diasSinAtenderRequierePrioridadAlta($reclamo)) {
            return true;
        }

        return self::estadoRequierePrioridadAlta($reclamo['municipalidad_estado'] ?? null);
    }

    /**
     * @return string|null Prioridad a persistir ('Alta', 'Baja' o null)
     */
    public static function evaluarPrioridad(array $reclamo): ?string
    {
        if (self::debeTenerPrioridadNula($reclamo)) {
            return null;
        }

        if (self::cumpleAlgunaReglaPrioridadAlta($reclamo)) {
            return 'Alta';
        }

        $actual = $reclamo['prioridad'] ?? null;
        if ($actual === 'Alta' || $actual === 'Baja') {
            return $actual;
        }

        return 'Baja';
    }

    /**
     * Aplica en lote motivo, antigüedad, pendiente y cierre/completado.
     */
    public static function sincronizarPrioridadesMasivas(): void
    {
        $db   = \Config\Database::connect();
        $dias = (int) self::DIAS_SIN_ATENDER_PARA_ALTA;

        $motivosEscapados = array_map(
            static fn(string $motivo): string => $db->escape($motivo),
            self::MOTIVOS_PRIORIDAD_ALTA
        );
        $motivosIn = implode(', ', $motivosEscapados);

        $db->query(
            "UPDATE reclamo SET prioridad = NULL
             WHERE municipalidad_estado = 'Completado' OR cerrado = 1"
        );

        $db->query(
            "UPDATE reclamo SET prioridad = 'Alta'
             WHERE municipalidad_motivo IN ({$motivosIn})
               AND municipalidad_estado != 'Completado'
               AND (cerrado IS NULL OR cerrado = 0)"
        );

        $db->query(
            "UPDATE reclamo SET prioridad = 'Alta'
             WHERE municipalidad_fechaInicio IS NOT NULL
               AND municipalidad_fechaInicio <= DATE_SUB(NOW(), INTERVAL {$dias} DAY)
               AND municipalidad_estado != 'Completado'
               AND (cerrado IS NULL OR cerrado = 0)"
        );

        $db->query(
            "UPDATE reclamo SET prioridad = 'Alta'
             WHERE municipalidad_estado = 'Pendiente'
               AND (cerrado IS NULL OR cerrado = 0)"
        );
    }
}
