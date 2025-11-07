-- Script para modificar la tabla token103 a estructura Basic Auth
-- Ejecutar este script en la base de datos

-- Paso 1: Hacer backup de los datos existentes (opcional)
-- CREATE TABLE token103_backup AS SELECT * FROM token103;

-- Paso 2: Eliminar columnas antiguas relacionadas con OAuth
ALTER TABLE token103 
DROP COLUMN IF EXISTS client_id,
DROP COLUMN IF EXISTS client_secret,
DROP COLUMN IF EXISTS access_token,
DROP COLUMN IF EXISTS token_type,
DROP COLUMN IF EXISTS expires_in,
DROP COLUMN IF EXISTS fecha_generacion;

-- Paso 3: Agregar nuevas columnas para Basic Auth
ALTER TABLE token103 
ADD COLUMN IF NOT EXISTS username VARCHAR(255) NOT NULL,
ADD COLUMN IF NOT EXISTS password VARCHAR(255) NOT NULL;

-- Paso 4: Insertar credenciales de ejemplo (CAMBIAR ESTOS VALORES)
-- Descomentar y modificar con las credenciales reales:
-- INSERT INTO token103 (username, password, created_at, updated_at) 
-- VALUES ('agusfarber@gmail.com', 'Alumbrado2025#!', NOW(), NOW());

-- Verificar la estructura de la tabla
-- DESCRIBE token103;



