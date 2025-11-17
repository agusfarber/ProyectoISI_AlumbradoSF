-- Script para limpiar las migraciones fallidas y permitir re-ejecutarlas
-- Ejecutar este script en la base de datos antes de volver a correr: php spark migrate

-- Eliminar registros de migraciones que fallaron
DELETE FROM migrations WHERE version IN ('2025-01-20-000001', '2025-01-20-000002');

-- Eliminar las tablas si existen (por si acaso)
DROP TABLE IF EXISTS tiempo_reparacion;
DROP TABLE IF EXISTS tiempo_promedio_motivo;

