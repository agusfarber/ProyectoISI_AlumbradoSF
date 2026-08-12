-- Distingue reclamos del sistema 103 vs creados localmente por el supervisor.
-- origen = '103' | 'local'
-- Los locales usan municipalidad_id con prefijo L{id} para no interferir el sync.
ALTER TABLE `reclamo`
  ADD COLUMN IF NOT EXISTS `origen` VARCHAR(20) NOT NULL DEFAULT '103' AFTER `excluido_observacion`;

UPDATE `reclamo` SET `origen` = '103' WHERE `origen` IS NULL OR `origen` = '';
