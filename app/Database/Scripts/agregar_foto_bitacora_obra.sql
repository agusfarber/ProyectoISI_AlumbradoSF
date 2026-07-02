-- Bitácora en obra: soporte de fotos en ruta_ejecucion_reclamo_observacion
ALTER TABLE ruta_ejecucion_reclamo_observacion
    ADD COLUMN IF NOT EXISTS tipo VARCHAR(10) NOT NULL DEFAULT 'texto' AFTER reclamo_id,
    ADD COLUMN IF NOT EXISTS archivo VARCHAR(255) NULL AFTER texto;

ALTER TABLE ruta_ejecucion_reclamo_observacion
    MODIFY texto TEXT NULL;
