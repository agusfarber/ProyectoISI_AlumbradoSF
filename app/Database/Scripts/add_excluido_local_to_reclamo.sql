-- Exclusión lógica de reclamos (no hard delete).
-- Si excluido_local = 1, el reclamo no aparece en listados/mapa/rutas
-- y el sync del 103 no lo recrea ni lo actualiza.
ALTER TABLE `reclamo`
  ADD COLUMN IF NOT EXISTS `excluido_local` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `ficha_editada`,
  ADD COLUMN IF NOT EXISTS `excluido_at` DATETIME NULL DEFAULT NULL AFTER `excluido_local`,
  ADD COLUMN IF NOT EXISTS `excluido_observacion` VARCHAR(500) NULL DEFAULT NULL AFTER `excluido_at`;
