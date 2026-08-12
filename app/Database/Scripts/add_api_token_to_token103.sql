-- Token de autenticación del sistema 103 (reemplaza Basic Auth user/pass)
-- Authorization: Token {api_token}

ALTER TABLE `token103`
  ADD COLUMN IF NOT EXISTS `api_token` VARCHAR(255) NULL DEFAULT NULL AFTER `id`;

ALTER TABLE `token103`
  MODIFY `username` VARCHAR(255) NULL DEFAULT NULL,
  MODIFY `password` VARCHAR(255) NULL DEFAULT NULL;

-- Ejemplo (reemplazar con el token de prod/staging):
-- INSERT INTO token103 (api_token, created_at, updated_at)
-- VALUES ('6f560d0559e9d32733781c050d5fd5d851e535c5', NOW(), NOW());
-- O actualizar el registro existente:
-- UPDATE token103 SET api_token = '6f560d0559e9d32733781c050d5fd5d851e535c5' ORDER BY id DESC LIMIT 1;
