-- Marca reclamos cuya ficha fue corregida por el supervisor.
-- Si ficha_editada = 1, el sync por número del 103 no pisa esos campos de ficha.
ALTER TABLE `reclamo`
  ADD COLUMN IF NOT EXISTS `ficha_editada` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `fecha_cierre`;
