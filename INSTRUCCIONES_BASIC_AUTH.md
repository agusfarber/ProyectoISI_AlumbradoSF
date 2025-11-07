# Migración de OAuth a Basic Auth para Sincronización de Reclamos

## Resumen de Cambios

El sistema de sincronización de reclamos ha sido modificado para usar **Basic Authentication** en lugar de OAuth Bearer tokens. Esto simplifica el proceso de autenticación con la API externa del sistema 103.

## Cambios Realizados

### 1. Base de Datos
- **Tabla `token103`**: Se modificó para almacenar credenciales Basic Auth
  - **Antes**: `client_id`, `client_secret`, `access_token`, `token_type`, `expires_in`, `fecha_generacion`
  - **Ahora**: `username`, `password`

### 2. Backend (PHP)
- **Token103Model.php**: Actualizado para usar campos `username` y `password`
- **Token103.php (Controller)**: Simplificado para manejar credenciales Basic Auth
  - Método nuevo: `generarTokenBasicAuth()` - genera token en base64

### 3. Backend - Proxy para evitar CORS
- **ReclamosSincronizacion.php (Controller)**: Nuevo controlador que actúa como proxy
  - Hace las peticiones a la API externa usando cURL
  - Maneja la autenticación Basic Auth en el servidor
  - Evita problemas de CORS

### 4. Frontend (JavaScript)
- **token103.js**: 
  - Cambiado de `client_id`/`client_secret` a `username`/`password`
  - Genera token Base64 automáticamente con `btoa()`
  - Eliminado el botón "Generar Token" (ahora es automático)

- **reclamos.js**: 
  - Ahora llama al proxy del backend en lugar de la API externa directamente
  - Endpoint: `BASE_URL + 'api/sincronizacion/reclamos'`
  - El backend maneja la autenticación con la API externa

### 5. Vista
- **token103.php**: Interfaz actualizada para mostrar campos de username/password

### 6. Rutas
- **Routes.php**: Agregadas rutas para el controlador de sincronización
  - `GET api/sincronizacion/reclamos` - Proxy para obtener reclamos por fechas
  - `GET api/sincronizacion/reclamos/{numero}` - Proxy para obtener reclamo específico

## Instrucciones de Instalación

### Paso 1: Actualizar la Base de Datos

Ejecutar uno de los siguientes scripts SQL en la base de datos:

**Opción A - Script Seguro (Recomendado)**
```bash
mysql -u usuario -p nombre_base_datos < app/Database/Scripts/modificar_tabla_token103_seguro.sql
```
Este script crea un backup de la tabla anterior.

**Opción B - Script Directo**
```bash
mysql -u usuario -p nombre_base_datos < app/Database/Scripts/modificar_tabla_token103.sql
```
Este script modifica directamente la tabla existente.

### Paso 2: Insertar Credenciales

Después de ejecutar el script, insertar las credenciales de la API externa:

```sql
INSERT INTO token103 (username, password, created_at, updated_at) 
VALUES ('agusfarber@gmail.com', 'Alumbrado2025#!', NOW(), NOW());
```

**IMPORTANTE**: Cambiar estos valores por las credenciales reales de producción.

### Paso 3: Verificar los Archivos

Asegurarse de que los siguientes archivos están actualizados:
- ✅ `app/Models/Token103Model.php`
- ✅ `app/Controllers/Api/Token103.php`
- ✅ `public/static/js/token103.js`
- ✅ `public/static/js/reclamos.js`
- ✅ `app/Views/pages/token103.php`

## Cómo Usar el Sistema

### 1. Configurar Credenciales

1. Ir a la página de **Gestión de Credenciales Basic Auth** en el sistema
2. Ingresar el **Username** (ej: `agusfarber@gmail.com`)
3. Ingresar el **Password** (ej: `Alumbrado2025#!`)
4. Hacer clic en **"Guardar Credenciales"**
5. El token Base64 se generará automáticamente

### 2. Sincronizar Reclamos

**Por rango de fechas:**
1. Seleccionar fecha desde y fecha hasta
2. Hacer clic en "Sincronizar por Fechas"
3. El sistema usará automáticamente las credenciales guardadas

**Por número específico:**
1. Ingresar el número de reclamo
2. Hacer clic en "Sincronizar Reclamo"
3. El sistema buscará ese reclamo específico

## Formato de Autenticación

### Antes (OAuth Bearer)
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### Ahora (Basic Auth)
```
Authorization: Basic YWd1c2ZhcmJlckBnbWFpbC5jb206QWx1bWJyYWRvMjAyNSMh
```

El token se genera codificando `username:password` en Base64:
```javascript
const token = btoa('agusfarber@gmail.com:Alumbrado2025#!');
// Resultado: YWd1c2ZhcmJlckBnbWFpbC5jb206QWx1bWJyYWRvMjAyNSMh
```

## Endpoints de la API Externa

- **Listar reclamos**: `GET /api/3.0/reclamos?created_after=YYYY-MM-DD&created_before=YYYY-MM-DD`
- **Reclamo específico**: `GET /api/3.0/reclamos/{numero_reclamo}`

## Ejemplo de Petición con cURL

```bash
curl -H 'Authorization: Basic YWd1c2ZhcmJlckBnbWFpbC5jb206QWx1bWJyYWRvMjAyNSMh' \
'https://reclamostesting.sanfrancisco.gov.ar/api/3.0/reclamos/?created_after=2025-01-01&created_before=2025-12-31'
```

## Verificación

Para verificar que todo funciona correctamente:

1. ✅ La página de credenciales muestra el token Base64 generado
2. ✅ Al copiar el token, se puede usar en Postman para probar la API
3. ✅ La sincronización de reclamos funciona sin errores
4. ✅ Los reclamos se guardan correctamente en la base de datos local

## Solución de Problemas CORS (IMPORTANTE)

### ✅ Problema de CORS Resuelto

Si ves este error en la consola del navegador:
```
Access to XMLHttpRequest has been blocked by CORS policy
```

**Solución implementada**: Hemos creado un **proxy en el backend PHP** que actúa como intermediario. Ahora las peticiones funcionan así:

1. Frontend (JavaScript) → Backend PHP (tu servidor local)
2. Backend PHP → API Externa (reclamostesting.sanfrancisco.gov.ar)
3. Backend PHP → Frontend (JavaScript)

Esto evita completamente el problema de CORS porque las peticiones servidor-a-servidor no tienen restricciones.

### Archivos Creados para Resolver CORS

- **`app/Controllers/Api/ReclamosSincronizacion.php`**: Controlador proxy que hace las peticiones a la API externa
- **Rutas agregadas en `app/Config/Routes.php`**:
  - `GET api/sincronizacion/reclamos` - Para sincronizar por fechas
  - `GET api/sincronizacion/reclamos/{numero}` - Para sincronizar reclamo específico

### Error: "Token no disponible"
- Verificar que las credenciales están guardadas en la base de datos
- Verificar que los campos `username` y `password` no están vacíos

### Error: "401 Unauthorized" en la API
- Verificar que las credenciales son correctas
- Verificar que el token Base64 se está generando correctamente en el backend
- Verificar los logs en `writable/logs/`

### Error: "No se pudieron sincronizar los reclamos"
- Verificar que la API externa está disponible
- Verificar los logs en `writable/logs/` para ver el error específico
- Verificar que las credenciales son correctas

## Notas Adicionales

- El token se genera **automáticamente** cada vez que se modifican las credenciales
- No es necesario "generar token" manualmente como antes
- Las credenciales se almacenan en texto plano en la base de datos (considerar encriptación para producción)
- El token Base64 es visible en el frontend (es normal para Basic Auth)

## Seguridad

⚠️ **IMPORTANTE**: 
- Las credenciales se almacenan en texto plano en la base de datos
- Considerar implementar encriptación para entornos de producción
- Limitar el acceso a la página de gestión de credenciales solo a administradores
- Usar HTTPS siempre para las comunicaciones con la API externa

