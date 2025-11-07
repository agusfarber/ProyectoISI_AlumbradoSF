-- Script SEGURO para modificar la tabla token103 a estructura Basic Auth
-- Este script crea una nueva tabla y mantiene la anterior como backup
-- Ejecutar este script en la base de datos

-- Paso 1: Renombrar la tabla antigua como backup
RENAME TABLE token103 TO token103_backup_oauth;

-- Paso 2: Crear la nueva tabla con la estructura Basic Auth
CREATE TABLE IF NOT EXISTS token103 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Paso 3: Insertar credenciales iniciales (CAMBIAR ESTOS VALORES)
-- Descomentar y modificar con las credenciales reales:
-- INSERT INTO token103 (username, password, created_at, updated_at) 
-- VALUES ('agusfarber@gmail.com', 'Alumbrado2025#!', NOW(), NOW());

-- Paso 4: Verificar la estructura de la tabla
-- DESCRIBE token103;

-- Paso 5: Si todo funciona correctamente, puedes eliminar el backup
-- DROP TABLE IF EXISTS token103_backup_oauth;



