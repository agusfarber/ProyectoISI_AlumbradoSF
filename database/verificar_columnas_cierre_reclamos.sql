-- Script de verificación y creación de columnas para HU-032: Cierre de Reclamos
-- Base de datos: proyectoisi_alumbradosf
-- Tabla: reclamo

-- NOTA: El usuario indica que las columnas ya existen, pero este script sirve como respaldo

USE proyectoisi_alumbradosf;

-- Verificar si la columna 'cerrado' existe, si no, crearla
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'proyectoisi_alumbradosf' 
  AND TABLE_NAME = 'reclamo' 
  AND COLUMN_NAME = 'cerrado';

-- Si no existe, ejecutar:
-- ALTER TABLE reclamo ADD COLUMN cerrado INT DEFAULT 0 NOT NULL COMMENT '0 = No cerrado, 1 = Cerrado formalmente';

-- Verificar si la columna 'fecha_cierre' existe, si no, crearla
SELECT COLUMN_NAME 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'proyectoisi_alumbradosf' 
  AND TABLE_NAME = 'reclamo' 
  AND COLUMN_NAME = 'fecha_cierre';

-- Si no existe, ejecutar:
-- ALTER TABLE reclamo ADD COLUMN fecha_cierre DATETIME NULL COMMENT 'Fecha y hora del cierre formal del reclamo';

-- Script completo para agregar las columnas (ejecutar solo si no existen):
/*
ALTER TABLE reclamo 
ADD COLUMN IF NOT EXISTS cerrado INT DEFAULT 0 NOT NULL COMMENT '0 = No cerrado, 1 = Cerrado formalmente',
ADD COLUMN IF NOT EXISTS fecha_cierre DATETIME NULL COMMENT 'Fecha y hora del cierre formal del reclamo';
*/

-- Verificar la estructura de la tabla después de las modificaciones
DESCRIBE reclamo;

-- Verificar reclamos completados sin cerrar (para pruebas)
SELECT 
    id,
    municipalidad_id,
    municipalidad_motivo,
    municipalidad_estado,
    cerrado,
    fecha_cierre
FROM reclamo 
WHERE municipalidad_estado = 'Completado' 
  AND cerrado = 0
LIMIT 10;

-- Verificar reclamos cerrados
SELECT 
    id,
    municipalidad_id,
    municipalidad_motivo,
    municipalidad_estado,
    cerrado,
    fecha_cierre
FROM reclamo 
WHERE cerrado = 1
ORDER BY fecha_cierre DESC
LIMIT 10;

-- Verificar historial de cierres
SELECT 
    h.id,
    h.nro_reclamo,
    h.estado_anterior,
    h.estado_actual,
    h.observacion,
    h.fecha_cambio,
    u.nombre as usuario
FROM historial_reclamo h
LEFT JOIN usuario u ON u.id = h.usuario_id
WHERE h.estado_actual = 'Cerrado'
ORDER BY h.fecha_cambio DESC
LIMIT 10;

-- Estadísticas de reclamos
SELECT 
    municipalidad_estado as estado,
    cerrado,
    COUNT(*) as cantidad
FROM reclamo
GROUP BY municipalidad_estado, cerrado
ORDER BY municipalidad_estado, cerrado;

