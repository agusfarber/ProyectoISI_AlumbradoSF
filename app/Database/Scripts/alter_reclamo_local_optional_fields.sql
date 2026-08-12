-- Permite IDs locales (L123) y campos de ficha opcionales en alta local.
ALTER TABLE `reclamo`
  MODIFY COLUMN `municipalidad_id` VARCHAR(50) NOT NULL;

ALTER TABLE `reclamo`
  MODIFY COLUMN `municipalidad_domicilio` VARCHAR(300) NULL DEFAULT NULL,
  MODIFY COLUMN `municipalidad_numeroDomicilio` VARCHAR(25) NULL DEFAULT NULL,
  MODIFY COLUMN `municipalidad_entreCalleUno` VARCHAR(300) NULL DEFAULT NULL,
  MODIFY COLUMN `municipalidad_entreCalleDos` VARCHAR(300) NULL DEFAULT NULL;
