-- Notas personales del supervisor (módulo aislado)
CREATE TABLE IF NOT EXISTS `nota_supervisor` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) UNSIGNED NOT NULL,
  `titulo` VARCHAR(160) NULL DEFAULT NULL,
  `contenido` TEXT NOT NULL,
  `hecha` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `fijada` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nota_usuario` (`usuario_id`),
  KEY `idx_nota_usuario_hecha` (`usuario_id`, `hecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
