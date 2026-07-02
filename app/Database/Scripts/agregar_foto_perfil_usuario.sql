-- ============================================================
-- Agrega la columna foto_perfil a la tabla usuario
-- Guarda el NOMBRE del archivo de la imagen (no la imagen en sí).
-- El archivo se almacena en: public/static/uploads/perfiles/
-- ============================================================

-- Ejecutar sobre la base de datos del proyecto:
-- USE proyecto_alumbradoSF;

ALTER TABLE `usuario`
    ADD COLUMN `foto_perfil` VARCHAR(255) NULL DEFAULT NULL AFTER `idRol`;
