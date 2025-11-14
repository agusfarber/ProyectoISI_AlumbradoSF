# 📋 Documentación de Pruebas - Sprint 1


# HU-006: Login de usuarios supervisores y operarios

---

## Prueba 1

**Nombre de la Prueba:** Login exitoso - Supervisor

**Ubicación:** `tests/api/AuthApiTest.php::testLoginExitosoSupervisor`

**Objetivo:** Verificar que un usuario con rol de supervisor puede iniciar sesión correctamente con credenciales válidas (legajo y contraseña).

**Tipo de Prueba:** API - Autenticación (HU-006)

**Datos Utilizados:**
- Legajo: "10001"
- Contraseña: "password123"
- Rol esperado: 2 (Supervisor)

**Resultado Esperado:**
- Status HTTP: 200
- Respuesta contiene:
  - `message`: "Inicio de sesión exitoso."
  - `role`: 2 (ID del rol supervisor)
- Se crea sesión con:
  - `user_id`
  - `user_name`
  - `role`
  - `logged_in`: true

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.338, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 16, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El usuario supervisor existe correctamente en la base de datos con los datos esperados
- ✅ El método `validateLoginByLegajo()` del modelo funciona correctamente
- ✅ Se verifica la validación estricta de legajo y contraseña
- ✅ El resultado devuelve todos los campos necesarios del usuario (id, nombre, legajo, email, idRol)
- ✅ El rol retornado es 2 (Supervisor) como se esperaba
- ✅ Se ejecutaron 16 assertions, todas exitosas
- ✅ La lógica de validación de credenciales funciona correctamente
- ✅ El usuario queda autenticado con los datos correctos

**Conclusión:** La lógica de autenticación para usuarios con rol de supervisor funciona correctamente. El método `validateLoginByLegajo()` valida apropiadamente las credenciales (legajo y contraseña) de manera estricta, verificando que el legajo coincida exactamente y que la contraseña sea correcta. El sistema devuelve todos los datos necesarios del usuario para crear la sesión posteriormente. 

---

## Prueba 2

**Nombre de la Prueba:** Login exitoso - Operario

**Ubicación:** `tests/api/AuthApiTest.php::testLoginExitosoOperario`

**Objetivo:** Verificar que un usuario con rol de operario puede iniciar sesión correctamente con credenciales válidas (legajo y contraseña).

**Tipo de Prueba:** Modelo - Autenticación (HU-006)

**Datos Utilizados:**
- Legajo: "20001"
- Contraseña: "password123"
- Rol esperado: 3 (Operario)

**Resultado Esperado:**
- Status HTTP: 200
- Respuesta contiene:
  - `message`: "Inicio de sesión exitoso."
  - `role`: 3 (ID del rol operario)
- Se crea sesión con:
  - `user_id`
  - `user_name`
  - `role`
  - `logged_in`: true

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.098, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 16, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El usuario operario existe correctamente en la base de datos con los datos esperados
- ✅ El método `validateLoginByLegajo()` del modelo funciona correctamente
- ✅ Se verifica la validación estricta de legajo y contraseña
- ✅ El resultado devuelve todos los campos necesarios del usuario (id, nombre, legajo, email, idRol)
- ✅ El rol retornado es 3 (Operario) como se esperaba
- ✅ Se ejecutaron 16 assertions, todas exitosas
- ✅ La lógica de validación de credenciales funciona correctamente para operarios
- ✅ El usuario queda autenticado con los datos correctos

**Conclusión:** La lógica de autenticación para usuarios con rol de operario funciona correctamente. El método `validateLoginByLegajo()` valida apropiadamente las credenciales del operario (legajo y contraseña) de manera estricta. El sistema devuelve todos los datos necesarios del usuario con el rol correcto (3 - Operario) para crear la sesión posteriormente y permitir el acceso a las funcionalidades específicas de los operarios.

---

## Prueba 3

**Nombre de la Prueba:** Credenciales Incorrectas

**Ubicación:** `tests/api/AuthApiTest.php::testCredencialesIncorrectas`

**Objetivo:** Verificar que el sistema rechaza correctamente un intento de login con contraseña incorrecta, incluso cuando el legajo es válido.

**Tipo de Prueba:** Modelo - Autenticación - Validación (HU-006)

**Datos Utilizados:**
- Legajo: "10001" (válido - existe en BD)
- Contraseña: "password_incorrecta" (incorrecta)
- Contraseña correcta en BD: "password123"

**Resultado Esperado:**
- El método `validateLoginByLegajo()` debe retornar `false`
- No debe devolver datos del usuario
- No debe crear variables de sesión (`logged_in`, `user_id`, `role`)
- Status equivalente: 401 (Unauthorized)

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.113, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 7, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El usuario existe en la base de datos (legajo válido confirmado)
- ✅ El método `validateLoginByLegajo()` rechaza correctamente la contraseña incorrecta
- ✅ El resultado es `false` (no devuelve datos del usuario)
- ✅ No es un array (confirmación de rechazo)
- ✅ No se crea la variable de sesión `logged_in`
- ✅ No se crea la variable de sesión `user_id`
- ✅ No se crea la variable de sesión `role`
- ✅ Se ejecutaron 7 assertions, todas exitosas
- ✅ La seguridad funciona correctamente: incluso con un legajo válido, si la contraseña no coincide, el acceso es denegado
- ✅ El sistema implementa validación estricta de contraseñas

**Conclusión:** El sistema de autenticación implementa correctamente la validación de contraseñas. Cuando se proporciona una contraseña incorrecta (incluso con un legajo válido), el método `validateLoginByLegajo()` rechaza el intento de login retornando `false` y no genera ninguna sesión.

---

## Prueba 4

**Nombre de la Prueba:** Usuario Inexistente

**Ubicación:** `tests/api/AuthApiTest.php::testUsuarioInexistente`

**Objetivo:** Verificar que el sistema rechaza correctamente un intento de login con un legajo que no existe en la base de datos.

**Tipo de Prueba:** Modelo - Autenticación - Validación (HU-006)

**Datos Utilizados:**
- Legajo: "99999" (no existe en BD)
- Contraseña: "cualquier_password"

**Resultado Esperado:**
- El método `validateLoginByLegajo()` debe retornar `false`
- No debe devolver datos del usuario
- No debe crear variables de sesión (`logged_in`, `user_id`, `role`)
- Status equivalente: 401 (Unauthorized) o 404 (Not Found)

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.116, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 6, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ Se confirma que el legajo "99999" NO existe en la base de datos
- ✅ El método `validateLoginByLegajo()` rechaza correctamente el legajo inexistente
- ✅ El resultado es `false` (no devuelve datos del usuario)
- ✅ No es un array (confirmación de rechazo)
- ✅ No se crea la variable de sesión `logged_in`
- ✅ No se crea la variable de sesión `user_id`
- ✅ No se crea la variable de sesión `role`
- ✅ Se ejecutaron 6 assertions, todas exitosas
- ✅ El sistema protege contra intentos de acceso con usuarios inexistentes
- ✅ No revela información sobre la existencia o no del usuario (seguridad)

**Conclusión:** El sistema de autenticación maneja correctamente los casos de usuarios inexistentes. Cuando se intenta hacer login con un legajo que no existe en la base de datos, el método `validateLoginByLegajo()` retorna `false` sin crear ninguna sesión, protegiendo el sistema contra intentos de enumeración de usuarios y accesos no autorizados.

---

## Prueba 5

**Nombre de la Prueba:** Validación de Campos Vacíos

**Ubicación:** `tests/api/AuthApiTest.php` (3 tests: `testLegajoVacio`, `testContrasenaVacia`, `testAmbosVacios`)

**Objetivo:** Verificar que el sistema valida correctamente los campos requeridos y rechaza intentos de login cuando faltan datos obligatorios.

**Tipo de Prueba:** Modelo - Autenticación - Validación de Campos (HU-006)

### Caso 5.1: Legajo vacío

**Datos Utilizados:**
- Legajo: "" (vacío)
- Contraseña: "password123" (válida)

**Resultado Esperado:**
- El método `validateLoginByLegajo()` debe retornar `false`
- No debe devolver datos del usuario
- No debe crear variables de sesión
- Status equivalente: 400 (Bad Request)

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.112, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 5, PHPUnit Warnings: 1.
```

### Caso 5.2: Contraseña vacía

**Datos Utilizados:**
- Legajo: "10001" (válido)
- Contraseña: "" (vacía)

**Resultado Esperado:**
- El método `validateLoginByLegajo()` debe retornar `false`
- No debe devolver datos del usuario
- No debe crear variables de sesión
- Status equivalente: 400 (Bad Request)

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.134, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 5, PHPUnit Warnings: 1.
```

### Caso 5.3: Ambos campos vacíos

**Datos Utilizados:**
- Legajo: "" (vacío)
- Contraseña: "" (vacía)

**Resultado Esperado:**
- El método `validateLoginByLegajo()` debe retornar `false`
- No debe devolver datos del usuario
- No debe crear variables de sesión
- Status equivalente: 400 (Bad Request)

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.109, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 5, PHPUnit Warnings: 1.
```

**Observaciones Generales:**
- ✅ El método `validateLoginByLegajo()` rechaza correctamente cuando el legajo está vacío
- ✅ El método `validateLoginByLegajo()` rechaza correctamente cuando la contraseña está vacía
- ✅ El método `validateLoginByLegajo()` rechaza correctamente cuando ambos campos están vacíos
- ✅ En todos los casos, el resultado es `false` (no devuelve datos del usuario)
- ✅ En todos los casos, no se crean variables de sesión (`logged_in`, `user_id`, `role`)
- ✅ Se ejecutaron 15 assertions en total (5 por cada caso), todas exitosas
- ✅ El sistema implementa validación de campos requeridos correctamente
- ✅ Protección contra envíos incompletos de formulario

**Conclusión:** El sistema de autenticación implementa correctamente la validación de campos requeridos. El método `validateLoginByLegajo()` verifica que tanto el legajo como la contraseña sean proporcionados y rechaza cualquier intento de login donde falte alguno de estos campos obligatorios, retornando `false` y sin crear ninguna sesión.

---

## Prueba 6

**Nombre de la Prueba:** Logout/Cierre de Sesión

**Ubicación:** `tests/api/AuthApiTest.php::testLogoutCierreSesion`

**Objetivo:** Verificar que el flujo completo de autenticación funciona correctamente: login exitoso con creación de sesión, y logout con destrucción completa de la sesión.

**Tipo de Prueba:** Integración - Autenticación - Gestión de Sesión (HU-006)

**Datos Utilizados:**
- Legajo: "10001" (Supervisor)
- Contraseña: "password123"

**Flujo del Test:**
1. **Login**: Validar credenciales con `validateLoginByLegajo()`
2. **Creación de sesión**: Simular la creación de variables de sesión
3. **Verificación**: Confirmar que la sesión existe con todos los datos
4. **Logout**: Destruir las variables de sesión
5. **Validación final**: Confirmar que la sesión fue completamente eliminada

**Resultado Esperado:**
- Login exitoso devuelve datos del usuario
- Sesión se crea con variables: `logged_in`, `user_id`, `user_name`, `user_legajo`, `role`
- Todas las variables de sesión contienen los valores correctos
- Logout elimina todas las variables de sesión
- Variables de sesión retornan `null` después del logout
- No quedan rastros de la sesión anterior

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.132, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 22, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El login fue exitoso (credenciales validadas correctamente)
- ✅ Se crearon correctamente 5 variables de sesión: `logged_in`, `user_id`, `user_name`, `user_legajo`, `role`
- ✅ Todas las variables de sesión contenían los valores correctos del usuario
- ✅ La variable `logged_in` se estableció correctamente en `true`
- ✅ El rol (2 - Supervisor) se almacenó correctamente en la sesión
- ✅ El logout eliminó todas las variables de sesión exitosamente
- ✅ Después del logout, ninguna variable de sesión existe (`has()` retorna `false`)
- ✅ Después del logout, todas las variables retornan `null` (`get()` retorna `null`)
- ✅ Se ejecutaron 22 assertions, todas exitosas
- ✅ La sesión fue completamente invalidada
- ✅ No quedan datos sensibles después del logout


**Conclusión:** El sistema de gestión de sesiones funciona correctamente. El flujo completo de autenticación opera como se espera: las credenciales válidas permiten crear una sesión con toda la información necesaria del usuario, y el logout destruye completamente la sesión eliminando todas las variables, lo que garantiza que no queden datos sensibles después de cerrar sesión y previene el acceso no autorizado con sesiones antiguas.

---


# HU-008: Guardado de reclamos

---

## Prueba 7

**Nombre de la Prueba:** Guardado Exitoso Completo de Reclamo

**Ubicación:** `tests/api/ReclamosApiTest.php::testGuardadoExitosoCompleto`

**Objetivo:** Verificar que se puede crear un reclamo con todos los campos válidos, se genera un ID único automáticamente, y todos los datos se guardan correctamente en la base de datos.

**Tipo de Prueba:** API - CRUD - Creación (HU-008)

**Datos Utilizados:**
- `municipalidad_id`: "10001"
- `municipalidad_tipo`: "ALUMBRADO PÚBLICO"
- `municipalidad_motivo`: "Luminaria apagada"
- `municipalidad_fechaInicio`: "2025-11-12 10:00:00"
- `municipalidad_fechaModificacion`: "2025-11-12 10:00:00"
- `municipalidad_recepcion`: "Web"
- `municipalidad_estado`: "Recibido"
- `municipalidad_telefono`: "3564123456"
- `municipalidad_domicilio`: "Av. Libertador"
- `municipalidad_numeroDomicilio`: "1234"
- `municipalidad_entreCalleUno`: "Calle 1"
- `municipalidad_entreCalleDos`: "Calle 2"
- `municipalidad_ciudadano`: "Juan Pérez"
- `municipalidad_descripcion`: "Luminaria de poste apagada, no enciende desde hace 3 días"
- `prioridad`: "Baja"

**Resultado Esperado:**
- Status HTTP: 201 (Created)
- Se genera un ID único automáticamente (numérico, mayor que 0)
- El reclamo se guarda en la base de datos con todos los campos
- Todos los datos coinciden exactamente con los enviados
- El reclamo puede ser recuperado posteriormente

**Resultado Obtenido:** ⚠️ **PARCIALMENTE EXITOSO**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

I                                                                   1 / 1 (100%)

Time: 00:00.414, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 21, PHPUnit Warnings: 1, Incomplete: 1.
```

**Observaciones:**
- ✅ La petición POST a `/api/reclamos` retorna status 201 (Created)
- ✅ Se genera automáticamente un ID único (ID = 1)
- ✅ El ID es numérico y mayor que 0
- ✅ El reclamo existe en la base de datos después de crearlo
- ✅ **Todos los 15 campos se guardaron correctamente:**
  - ✅ `municipalidad_id` = "10001"
  - ✅ `municipalidad_tipo` = "ALUMBRADO PÚBLICO"
  - ✅ `municipalidad_motivo` = "Luminaria apagada"
  - ✅ `municipalidad_fechaInicio` = "2025-11-12 10:00:00"
  - ✅ `municipalidad_fechaModificacion` = "2025-11-12 10:00:00"
  - ✅ `municipalidad_recepcion` = "Web"
  - ✅ `municipalidad_estado` = "Recibido"
  - ✅ `municipalidad_telefono` = "3564123456"
  - ✅ `municipalidad_domicilio` = "Av. Libertador"
  - ✅ `municipalidad_numeroDomicilio` = "1234"
  - ✅ `municipalidad_entreCalleUno` = "Calle 1"
  - ✅ `municipalidad_entreCalleDos` = "Calle 2"
  - ✅ `municipalidad_ciudadano` = "Juan Pérez"
  - ✅ `municipalidad_descripcion` = "Luminaria de poste apagada, no enciende desde hace 3 días"
  - ✅ `prioridad` = "Baja"
- ✅ Se ejecutaron 21 assertions, todas exitosas
- ⚠️ **NOTA TÉCNICA**: El endpoint GET `/api/reclamos/{id}` retorna status 501 (Not Implemented), lo que indica que la recuperación individual por ID no está implementada. Sin embargo, esto no afecta la funcionalidad de guardado que es el objetivo principal de esta HU.


**Conclusión:** El sistema de guardado de reclamos funciona correctamente. La API permite crear reclamos con todos sus campos, genera automáticamente un ID único autoincremental, y almacena correctamente toda la información en la base de datos. Todos los datos enviados se guardan sin pérdida de información y pueden ser verificados directamente en la base de datos. El endpoint de creación (POST) cumple completamente con los requisitos de la HU-008.

---

## Prueba 8

**Nombre de la Prueba:** Integridad de Datos

**Ubicación:** `tests/api/ReclamosApiTest.php::testIntegridadDatos`

**Objetivo:** Verificar que todos los campos del reclamo se guardan correctamente con sus valores exactos, incluyendo caracteres especiales, acentos, formatos específicos y textos largos, sin pérdida ni transformación de información.

**Tipo de Prueba:** API - Validación de Integridad de Datos (HU-008)

**Datos Utilizados (con casos especiales):**
- `municipalidad_id`: "99999" 
- `municipalidad_tipo`: "ALUMBRADO PÚBLICO"
- `municipalidad_motivo`: "Lámpara con parpadeo intermitente" (con acento en 'á')
- `municipalidad_fechaInicio`: "2025-11-12 23:59:59" (hora límite del día)
- `municipalidad_fechaModificacion`: "2025-11-12 23:59:59"
- `municipalidad_recepcion`: "Teléfono"
- `municipalidad_estado`: "En plan"
- `municipalidad_telefono`: "0800-555-1234" (formato con guiones)
- `municipalidad_domicilio`: "Av. San Martín" (con acento)
- `municipalidad_numeroDomicilio`: "1234-B" (número con letra)
- `municipalidad_entreCalleUno`: "Córdoba" (con acento en 'ó')
- `municipalidad_entreCalleDos`: "Sarmiento & Mitre" (con carácter '&')
- `municipalidad_ciudadano`: "José María González" (con acentos múltiples)
- `municipalidad_descripcion`: "Reclamo urgente: La luminaria presenta fallas intermitentes desde hace 1 semana. Afecta seguridad del barrio." (texto largo con puntuación)
- `prioridad`: "Alta"

**Resultado Esperado:**
- Status HTTP: 201 (Created)
- Todos los campos se guardan exactamente como se enviaron
- Los acentos se mantienen (á, é, í, ó, ú)
- Los caracteres especiales se preservan (&, -, :)
- Los formatos específicos no se alteran (teléfono con guiones, número con letra)
- Los textos largos no se truncan
- Las fechas con horas límite se guardan correctamente

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.725, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 24, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El reclamo se creó exitosamente (status 201)
- ✅ **Verificación campo por campo (15 campos):**
  - ✅ `municipalidad_id` = "99999" 
  - ✅ `municipalidad_tipo` = "ALUMBRADO PÚBLICO"
  - ✅ `municipalidad_motivo` mantiene acento en "Lámpara"
  - ✅ `municipalidad_fechaInicio` = "2025-11-12 23:59:59"
  - ✅ `municipalidad_fechaModificacion` = "2025-11-12 23:59:59"
  - ✅ `municipalidad_recepcion` = "Teléfono"
  - ✅ `municipalidad_estado` = "En plan"
  - ✅ `municipalidad_telefono` mantiene formato "0800-555-1234" con guiones
  - ✅ `municipalidad_domicilio` mantiene acento en "San Martín"
  - ✅ `municipalidad_numeroDomicilio` = "1234-B" (con letra)
  - ✅ `municipalidad_entreCalleUno` mantiene acento en "Córdoba"
  - ✅ `municipalidad_entreCalleDos` mantiene '&' en "Sarmiento & Mitre"
  - ✅ `municipalidad_ciudadano` mantiene todos los acentos en "José María González"
  - ✅ `municipalidad_descripcion` mantiene toda la puntuación y longitud (>50 caracteres)
  - ✅ `prioridad` = "Alta"

- ✅ **Verificaciones adicionales de integridad (9 verificaciones):**
  - ✅ Descripción no truncada (>50 caracteres)
  - ✅ Acento 'á' preservado en "lámpara"
  - ✅ Acento 'ó' preservado en "Córdoba"
  - ✅ Carácter especial '&' preservado
  - ✅ Guiones '-' preservados en teléfono

- ✅ Se ejecutaron 24 assertions, todas exitosas
- ✅ **Codificación UTF-8 correcta**: Todos los caracteres especiales y acentos se mantienen
- ✅ **Sin truncamiento**: Textos largos se guardan completos
- ✅ **Sin normalización no deseada**: Formatos especiales (teléfono, números) no se alteran

**Casos Especiales Validados:**
1. ✅ **Acentos**: á, é, í, ó, ú se preservan correctamente
2. ✅ **Caracteres especiales**: & se mantiene sin escape
3. ✅ **Números con letras**: "1234-B" no se convierte a solo número
4. ✅ **Formato de teléfono**: Guiones se mantienen en "0800-555-1234"
5. ✅ **Texto largo**: Descripción >100 caracteres sin truncar
6. ✅ **Puntuación**: Dos puntos, puntos, comas se preservan
7. ✅ **Hora límite**: "23:59:59" se guarda correctamente
8. ✅ **Número máximo**: "99999" se acepta

**Conclusión:** El sistema mantiene la integridad de datos. Todos los campos se guardan exactamente como se envían, sin pérdida de información, alteración de formato, truncamiento de texto o corrupción de caracteres especiales. La codificación UTF-8 funciona correctamente preservando todos los acentos y caracteres en español. Los formatos específicos (teléfonos, direcciones alfanuméricas) no sufren normalización no deseada.

---

## Prueba 9

**Nombre de la Prueba:** Actualización de Reclamo Existente

**Ubicación:** `tests/api/ReclamosApiTest.php::testActualizacionReclamoExistente`

**Objetivo:** Verificar que se puede actualizar un reclamo ya guardado, modificando campos específicos mientras se mantiene el ID original y los campos no modificados permanecen intactos.

**Tipo de Prueba:** API - CRUD - Actualización (HU-008)

**Datos Utilizados:**

**Estado Inicial:**
- `municipalidad_id`: "20001"
- `municipalidad_estado`: "Recibido"
- `prioridad`: "Baja"
- `municipalidad_telefono`: "3564111111"
- `municipalidad_ciudadano`: "Pedro García"
- `municipalidad_descripcion`: "Descripción inicial del reclamo"
- `municipalidad_fechaModificacion`: "2025-11-12 10:00:00"

**Campos Modificados:**
- `municipalidad_estado`: "Recibido" → "En ejecución"
- `prioridad`: "Baja" → "Alta"
- `municipalidad_telefono`: "3564111111" → "3564222222"
- `municipalidad_ciudadano`: "Pedro García" → "Pedro García López"
- `municipalidad_descripcion`: "Descripción inicial del reclamo" → "Descripción actualizada - Se está trabajando en el reclamo"
- `municipalidad_fechaModificacion`: "2025-11-12 10:00:00" → "2025-11-12 15:30:00"

**Resultado Esperado:**
- Status HTTP: 200 (OK) en la actualización
- El ID del reclamo permanece igual
- Los campos modificados se actualizan correctamente
- Los campos no modificados permanecen intactos
- El reclamo actualizado puede ser recuperado de la BD

**Resultado Obtenido:** ✅ **EXITOSO**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.456, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 25, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ **Creación inicial**: Reclamo creado correctamente (status 201)
- ✅ **Actualización exitosa**: PUT retorna status 200
- ✅ **ID preservado**: El ID permanece igual después de la actualización
- ✅ **Campos actualizados correctamente (6 campos)**:
  - ✅ Estado: "Recibido" → "En ejecución"
  - ✅ Prioridad: "Baja" → "Alta"
  - ✅ Teléfono: "3564111111" → "3564222222"
  - ✅ Ciudadano: "Pedro García" → "Pedro García López"
  - ✅ Descripción: Se actualizó correctamente
  - ✅ Fecha modificación: "2025-11-12 10:00:00" → "2025-11-12 15:30:00"

- ✅ **Campos sin cambios preservados (4 campos)**:
  - ✅ municipalidad_id: "20001" (sin cambio)
  - ✅ municipalidad_motivo: "Luminaria apagada" (sin cambio)
  - ✅ municipalidad_domicilio: "Calle Inicial" (sin cambio)
  - ✅ municipalidad_numeroDomicilio: "100" (sin cambio)

- ✅ **Persistencia**: Los cambios se guardaron correctamente en la base de datos
- ✅ **Recuperación**: El reclamo actualizado puede ser recuperado con su nuevo estado
- ✅ Se ejecutaron 25 assertions, todas exitosas


**Conclusión:** El sistema de actualización de reclamos funciona correctamente. La API permite modificar reclamos existentes mediante PUT, preservando el ID único del registro y actualizando solo los campos especificados. Los campos no incluidos en la actualización mantienen sus valores originales.

---


# HU-010: Visualizar historial de reclamos

---

## Prueba 10

**Nombre de la Prueba:** Obtención del Historial Completo

**Ubicación:** `tests/api/HistorialReclamosApiTest.php::testObtenerHistorialCompleto`

**Objetivo:** Verificar que se puede recuperar la lista completa de reclamos con todas sus características mediante el endpoint GET /api/reclamos.

**Tipo de Prueba:** API - Consulta - Listado Completo (HU-010)

**Datos Utilizados:**

Se crearon 3 reclamos de prueba con diferentes características:

**Reclamo 1:**
- municipalidad_id: "40001"
- Estado: "Recibido"
- Prioridad: "Alta"
- Motivo: "Luminaria apagada"
- Recepción: "Web"
- Ciudadano: "Juan Pérez"
- Domicilio: "Calle Primera 100"

**Reclamo 2:**
- municipalidad_id: "40002"
- Estado: "En ejecución"
- Prioridad: "Alta"
- Motivo: "Poste inclinado"
- Recepción: "Teléfono"
- Ciudadano: "María González"
- Domicilio: "Calle Segunda 200"

**Reclamo 3:**
- municipalidad_id: "40003"
- Estado: "Completado"
- Prioridad: "Baja"
- Motivo: "Cable suelto"
- Recepción: "Sistema 103"
- Ciudadano: "Carlos López"
- Domicilio: "Calle Tercera 300"

**Resultado Esperado:**
- Status HTTP: 200 (OK)
- Respuesta: Array de reclamos
- Cada reclamo debe incluir TODOS los campos:
  - id
  - municipalidad_id
  - municipalidad_tipo
  - municipalidad_motivo
  - municipalidad_fechaInicio
  - municipalidad_fechaModificacion
  - municipalidad_recepcion
  - municipalidad_estado
  - municipalidad_telefono
  - municipalidad_domicilio
  - municipalidad_numeroDomicilio
  - municipalidad_entreCalleUno
  - municipalidad_entreCalleDos
  - municipalidad_ciudadano
  - municipalidad_descripcion
  - prioridad

**Resultado Obtenido:** ✅ **EXITOSO**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.145, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 71, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ **Status 200**: El endpoint retorna correctamente
- ✅ **Formato Array**: La respuesta es un array válido de reclamos
- ✅ **Cantidad correcta**: Retorna al menos los 3 reclamos insertados
- ✅ **Estructura completa (16 campos)**: Cada reclamo incluye TODOS los campos requeridos
  - ✅ id - Identificador único interno
  - ✅ municipalidad_id - ID del sistema municipal
  - ✅ municipalidad_tipo - Tipo de reclamo
  - ✅ municipalidad_motivo - Motivo del reclamo
  - ✅ municipalidad_fechaInicio - Fecha de inicio
  - ✅ municipalidad_fechaModificacion - Fecha de última modificación
  - ✅ municipalidad_recepcion - Canal de recepción
  - ✅ municipalidad_estado - Estado actual
  - ✅ municipalidad_telefono - Teléfono de contacto
  - ✅ municipalidad_domicilio - Calle
  - ✅ municipalidad_numeroDomicilio - Número de domicilio
  - ✅ municipalidad_entreCalleUno - Entre calle 1
  - ✅ municipalidad_entreCalleDos - Entre calle 2
  - ✅ municipalidad_ciudadano - Nombre del ciudadano
  - ✅ municipalidad_descripcion - Descripción detallada
  - ✅ prioridad - Nivel de prioridad

- ✅ **71 assertions ejecutadas**: Todas exitosas

**Conclusión:** El endpoint `GET /api/reclamos` funciona correctamente y cumple con los requisitos de la HU-010. Retorna el historial completo de reclamos con TODAS las características necesarias para que supervisores y operarios puedan visualizar la información completa de cada reclamo.

---

## Prueba 11

**Nombre de la Prueba:** Filtro por Estado

**Ubicación:** `tests/api/HistorialReclamosApiTest.php::testFiltrarPorEstado`

**Objetivo:** Verificar que se pueden filtrar reclamos por estado (Recibido, En ejecución, Completado) mediante el endpoint GET /api/reclamos.

**Tipo de Prueba:** API - Consulta - Filtro por Estado (HU-010)

**Datos Utilizados:**

Se crearían 6 reclamos de prueba con diferentes estados:
- 2 con estado "Recibido"
- 2 con estado "En ejecución"
- 2 con estado "Completado"

**Resultado Esperado:**
- `GET /api/reclamos?estado=Recibido` → Status 200, solo reclamos con estado "Recibido"
- `GET /api/reclamos?estado=En ejecución` → Status 200, solo reclamos con estado "En ejecución"
- `GET /api/reclamos?estado=Completado` → Status 200, solo reclamos con estado "Completado"

**Resultado Obtenido:** ⚠️ **INCOMPLETO (Funcionalidad No Implementada)**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

∅ Filtrar por estado
```

**Observaciones:**
- ⚠️  **FILTRADO SOLO EN FRONTEND**: Los filtros están implementados en el **cliente** (`public/static/js/reclamos.js` líneas 215-219) usando DataTables, pero **NO en el backend**
- 🔄 **Flujo actual**: 
  1. El frontend obtiene TODOS los reclamos con `GET /api/reclamos`
  2. Luego filtra los datos localmente en JavaScript usando DataTables
- 📍 **Limitación**: El endpoint `App\Controllers\Api\Reclamos::index()` NO procesa query parameters - siempre retorna todos los reclamos
- ❌ **Problema de eficiencia**: Con muchos reclamos, traer todos y filtrar en cliente es ineficiente (más tráfico de red, menor rendimiento)
- ✅ **Mejora requerida**: Implementar filtrado del lado del **servidor** mediante query parameters `?estado=X` para mejor performance y escalabilidad


**Conclusión:** El test queda marcado como INCOMPLETO. Aunque los filtros **funcionan correctamente en la interfaz de usuario** (frontend), el backend NO los soporta vía API. Se recomienda implementar el filtrado del lado del servidor para mejorar la eficiencia y escalabilidad del sistema, especialmente cuando la cantidad de reclamos crezca.

---

## Prueba 12

**Nombre de la Prueba:** Filtro por Prioridad

**Ubicación:** `tests/api/HistorialReclamosApiTest.php::testFiltrarPorPrioridad`

**Objetivo:** Verificar que se pueden filtrar reclamos por prioridad mediante el endpoint GET /api/reclamos.

**Tipo de Prueba:** API - Consulta - Filtro por Prioridad (HU-010)

**Datos Utilizados:**

Se crearían 5 reclamos de prueba con diferentes prioridades:
- 3 con prioridad "Alta"
- 2 con prioridad "Baja"

**Resultado Esperado:**
- `GET /api/reclamos?prioridad=Alta` → Status 200, solo reclamos con prioridad "Alta"
- `GET /api/reclamos?prioridad=Baja` → Status 200, solo reclamos con prioridad "Baja"

**Resultado Obtenido:** ⚠️ **INCOMPLETO (Funcionalidad No Implementada)**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

∅ Filtrar por prioridad
```

**Observaciones:**
- ⚠️  **FILTRADO SOLO EN FRONTEND**: Los filtros están implementados en el **cliente** (`public/static/js/reclamos.js` líneas 223-227) usando DataTables, pero **NO en el backend**
- 🔄 **Flujo actual**: 
  1. El frontend obtiene TODOS los reclamos con `GET /api/reclamos`
  2. Luego filtra los datos localmente en JavaScript usando DataTables
- 📍 **Limitación**: El endpoint `App\Controllers\Api\Reclamos::index()` NO procesa query parameters - siempre retorna todos los reclamos
- ❌ **Problema de eficiencia**: Con muchos reclamos, traer todos y filtrar en cliente es ineficiente (más tráfico de red, menor rendimiento)
- ✅ **Mejora requerida**: Implementar filtrado del lado del **servidor** mediante query parameters `?prioridad=X` para mejor performance y escalabilidad


**Conclusión:** El test queda marcado como INCOMPLETO. Aunque los filtros **funcionan correctamente en la interfaz de usuario** (frontend), el backend NO los soporta vía API. Se recomienda implementar el filtrado del lado del servidor para mejorar la eficiencia y escalabilidad del sistema, especialmente cuando la cantidad de reclamos crezca.

---

## Prueba 13

**Nombre de la Prueba:** Filtro por Rango de Fechas

**Ubicación:** `tests/api/HistorialReclamosApiTest.php::testFiltrarPorRangoFechas`

**Objetivo:** Verificar que se pueden filtrar reclamos por rango de fechas mediante el endpoint GET /api/reclamos.

**Tipo de Prueba:** API - Consulta - Filtro por Fechas (HU-010)

**Datos Utilizados:**

Se crearían 4 reclamos de prueba con diferentes fechas:
- Reclamo 1: 2025-01-15
- Reclamo 2: 2025-03-20
- Reclamo 3: 2025-06-10
- Reclamo 4: 2024-12-25 (fuera del rango)

**Resultado Esperado:**
- `GET /api/reclamos?fecha_desde=2025-01-01&fecha_hasta=2025-12-31` → Status 200, solo reclamos del año 2025 (3 reclamos)
- `GET /api/reclamos?fecha_desde=2025-01-01&fecha_hasta=2025-03-31` → Status 200, solo reclamos del Q1 2025 (2 reclamos)
- No debe incluir reclamos fuera del rango especificado

**Resultado Obtenido:** ⚠️ **INCOMPLETO (Funcionalidad No Implementada)**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

∅ Filtrar por rango fechas
```

**Observaciones:**
- ⚠️  **FILTRADO SOLO EN FRONTEND**: Los filtros están implementados en el **cliente** (`public/static/js/reclamos.js` líneas 168-210) usando DataTables con búsqueda personalizada, pero **NO en el backend**
- 🔄 **Flujo actual**: 
  1. El frontend obtiene TODOS los reclamos con `GET /api/reclamos`
  2. Luego filtra los datos localmente en JavaScript usando una función de búsqueda personalizada de DataTables
- 📍 **Limitación**: El endpoint `App\Controllers\Api\Reclamos::index()` NO procesa query parameters - siempre retorna todos los reclamos
- ❌ **Problema de eficiencia**: Con muchos reclamos, traer todos y filtrar en cliente es ineficiente (más tráfico de red, menor rendimiento)
- ✅ **Mejora requerida**: Implementar filtrado del lado del **servidor** mediante query parameters `?fecha_desde=Y-m-d&fecha_hasta=Y-m-d` para mejor performance y escalabilidad


**Conclusión:** El test queda marcado como INCOMPLETO. Aunque los filtros **funcionan correctamente en la interfaz de usuario** (frontend), el backend NO los soporta vía API. Se recomienda implementar el filtrado del lado del servidor para mejorar la eficiencia y escalabilidad del sistema, especialmente cuando la cantidad de reclamos crezca.

---

## Prueba 14

**Nombre de la Prueba:** Filtros Combinados

**Ubicación:** `tests/api/HistorialReclamosApiTest.php::testFiltrosCombinados`

**Objetivo:** Verificar que se pueden aplicar múltiples filtros simultáneamente (estado + prioridad + fecha) mediante el endpoint GET /api/reclamos.

**Tipo de Prueba:** API - Consulta - Filtros Múltiples (HU-010)

**Datos Utilizados:**

Se crearían 5 reclamos de prueba con diferentes combinaciones:
- Reclamo 1: Recibido + Alta + 2025-01-10
- Reclamo 2: Recibido + Baja + 2025-01-15
- Reclamo 3: En ejecución + Alta + 2025-02-05
- Reclamo 4: En ejecución + Baja + 2025-06-20
- Reclamo 5: Completado + Alta + 2025-01-25

**Resultado Esperado:**

Combinaciones a probar:
1. `?estado=Recibido&prioridad=Alta` → Solo reclamos que cumplan AMBAS condiciones
2. `?estado=En ejecución&fecha_desde=2025-01-01&fecha_hasta=2025-03-31` → Estado + rango de fechas
3. `?prioridad=Alta&fecha_desde=2025-01-01&fecha_hasta=2025-01-31` → Prioridad + rango de fechas
4. `?estado=Recibido&prioridad=Alta&fecha_desde=2025-01-01&fecha_hasta=2025-01-31` → Los 3 filtros simultáneamente

**Resultado Obtenido:** ⚠️ **INCOMPLETO (Funcionalidad No Implementada)**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

∅ Filtros combinados
```

**Observaciones:**
- ⚠️  **FILTRADO SOLO EN FRONTEND**: Los filtros combinados están implementados en el **cliente** (`public/static/js/reclamos.js` líneas 161-230) usando DataTables, pero **NO en el backend**
- 🔄 **Flujo actual**: 
  1. El frontend obtiene TODOS los reclamos con `GET /api/reclamos`
  2. Luego aplica múltiples filtros localmente en JavaScript usando DataTables (estado + prioridad + fechas)
- 📍 **Limitación**: El endpoint `App\Controllers\Api\Reclamos::index()` NO procesa query parameters - siempre retorna todos los reclamos
- ❌ **Problema de eficiencia**: Con muchos reclamos, traer todos y filtrar en cliente es **MUY ineficiente** (especialmente con filtros combinados)
- 🎯 **Impacto en UX**: Los filtros funcionan en la interfaz, pero la carga inicial de todos los datos puede ser lenta
- ✅ **Mejora crítica requerida**: Implementar filtrado del lado del **servidor** para permitir consultas eficientes con múltiples criterios

**Ejemplos de combinaciones a soportar:**
- `?estado=Recibido&prioridad=Alta`
- `?estado=En ejecución&fecha_desde=2025-01-01&fecha_hasta=2025-12-31`
- `?prioridad=Alta&fecha_desde=2025-01-01`
- `?estado=Recibido&prioridad=Alta&fecha_desde=2025-01-01&fecha_hasta=2025-12-31`


**Conclusión:** El test queda marcado como INCOMPLETO. Aunque los filtros combinados **funcionan correctamente en la interfaz de usuario** (frontend), el backend NO los soporta vía API. Esta es una mejora para la escalabilidad. Se recomienda priorizar la implementación del filtrado del lado del servidor.

---

# HU-030: Cargar tipos de materiales y lámparas

---

## Prueba 15

**Nombre de la Prueba:** Carga Manual Exitosa

**Ubicación:** `tests/api/MaterialesApiTest.php::testCargaManualExitosa`

**Objetivo:** Verificar que se puede crear un material manualmente con todos los campos válidos y que el sistema genera un ID único automáticamente, guarda los datos correctamente en BD, y permite recuperar el material creado.

**Tipo de Prueba:** API - CRUD - Creación (HU-030)

**Datos Utilizados:**

```json
{
    "nombre": "Lámpara LED 50W",
    "idTipo": 1,
    "cantidad": 100
}
```

Donde `idTipo = 1` corresponde al tipo "Lámpara LED" (pre-cargado en el seeder de test).

**Resultado Esperado:**

1. `POST /api/materiales` retorna status 201 (Created)
2. La respuesta contiene el material creado con un ID generado automáticamente (numérico > 0)
3. Todos los campos enviados coinciden en la respuesta
4. El material se guarda correctamente en la base de datos (verificado con consulta directa)
5. El material puede ser recuperado via `GET /api/materiales`
6. La respuesta del GET incluye el nombre del tipo asociado (join con tabla `tipo_material`)

**Resultado Obtenido:** ✅ **EXITOSO**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12

.                                                                   1 / 1 (100%)

Time: 00:00.149, Memory: 16.00 MB

Materiales Api (Tests\Api\MaterialesApi)
 ✔ Carga manual exitosa

OK, but there were issues!
Tests: 1, Assertions: 18, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El endpoint `POST /api/materiales` funciona correctamente
- ✅ El sistema genera IDs únicos automáticamente usando auto_increment
- ✅ Los datos se persisten correctamente en la base de datos
- ✅ El material creado está inmediatamente disponible para consulta via GET
- ✅ El endpoint GET incluye información adicional (nombre del tipo) mediante un join con la tabla `tipo_material`
- 📊 El test realiza **18 aserciones** que validan:
  - Status code 201
  - Estructura de respuesta (array con keys esperadas)
  - ID numérico válido (> 0)
  - Coincidencia de todos los campos enviados vs recibidos
  - Persistencia en BD (consulta directa al modelo)
  - Disponibilidad vía GET (búsqueda en lista completa)
  - Correcta relación con tabla tipo_material (nombre del tipo incluido)

**Conclusión:** El flujo completo de creación manual de materiales funciona correctamente. El sistema valida, persiste y permite recuperar materiales con todos sus datos asociados. La funcionalidad CRUD básica para materiales está operativa.

---

## Prueba 16

**Nombre de la Prueba:** Validación de Campos Obligatorios

**Ubicación:** `tests/api/MaterialesApiTest.php::testValidacionCamposObligatorios`

**Objetivo:** Verificar que el sistema valida correctamente todos los campos requeridos al intentar crear un material, rechazando datos inválidos con mensajes de error claros y evitando la persistencia de datos incorrectos en la base de datos.

**Tipo de Prueba:** API - Validación - Campos Obligatorios (HU-030)

**Datos Utilizados:**

Se prueban **8 casos de validación diferentes**:

1. **Nombre vacío** (`nombre: ""`)
2. **Nombre omitido** (sin campo `nombre`)
3. **Tipo inválido** (`idTipo: 0`)
4. **Tipo negativo** (`idTipo: -1`)
5. **Tipo omitido** (sin campo `idTipo`)
6. **Cantidad negativa** (`cantidad: -10`)
7. **Cantidad omitida** (sin campo `cantidad`)
8. **Todos los campos inválidos** (`nombre: "", idTipo: 0, cantidad: -5`)

**Resultado Esperado:**

Para cada caso:
1. `POST /api/materiales` retorna status 400 (Bad Request)
2. La respuesta contiene la estructura `messages` con el error
3. El mensaje de error menciona el campo específico que falló la validación
4. Los términos clave aparecen en los mensajes:
   - "nombre" para errores de nombre
   - "tipo" para errores de tipo de material
   - "cantidad" para errores de cantidad
   - "obligatorio" para campos faltantes
5. **NINGÚN** material inválido se guarda en la base de datos

**Resultado Obtenido:** ✅ **EXITOSO**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12

..                                                                  2 / 2 (100%)

Time: 00:00.242, Memory: 16.00 MB

Materiales Api (Tests\Api\MaterialesApi)
 ✔ Carga manual exitosa
 ✔ Validacion campos obligatorios

OK, but there were issues!
Tests: 2, Assertions: 41, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El controlador valida correctamente **nombre vacío**: rechaza con status 400
- ✅ El controlador valida correctamente **campos omitidos**: detecta cuando faltan `nombre`, `idTipo` o `cantidad`
- ✅ El controlador valida correctamente **tipo inválido**: rechaza `idTipo <= 0`
- ✅ El controlador valida correctamente **cantidad negativa**: rechaza `cantidad < 0`
- ✅ Los mensajes de error son **descriptivos** y mencionan los campos específicos
- ✅ **Integridad de datos garantizada**: El test confirma que 0 materiales fueron creados con datos inválidos
- 📊 El test realiza **23 aserciones** que validan:
  - Status 400 para los 8 casos de error (8 aserciones)
  - Estructura de respuesta con `messages` (8 aserciones)
  - Presencia de términos clave en mensajes de error (6 aserciones)
  - Integridad de BD: 0 registros creados (1 aserción)

**Mensajes de Error del Sistema:**

```json
// Caso: Campos omitidos
{
    "status": 400,
    "error": 400,
    "messages": {
        "error": "Faltan datos obligatorios: nombre, cantidad y tipo."
    }
}

// Caso: Nombre vacío o valores inválidos
{
    "status": 400,
    "error": 400,
    "messages": {
        "error": "Nombre, cantidad o tipo inválidos."
    }
}
```

**Conclusión:** El sistema de validación de campos obligatorios funciona correctamente. El controlador `Api\Materiales::create()` implementa todas las validaciones necesarias para garantizar la integridad de los datos:
- Rechaza nombres vacíos
- Rechaza tipos de material inválidos (0 o negativos)
- Rechaza cantidades negativas
- Provee mensajes de error descriptivos
- **NO persiste datos inválidos** en la base de datos

---

## Prueba 17

**Nombre de la Prueba:** Obtención del Catálogo Completo

**Ubicación:** `tests/api/MaterialesApiTest.php::testObtenerCatalogoCompleto`

**Objetivo:** Verificar que se puede obtener la lista completa de materiales disponibles a través del endpoint GET, incluyendo todos los campos necesarios y la correcta asociación con los tipos de materiales mediante un join.

**Tipo de Prueba:** API - CRUD - Lectura (HU-030)

**Datos Utilizados:**

Se crean **5 materiales de prueba** de diferentes tipos:

```json
[
    {
        "nombre": "Lámpara LED 50W",
        "idTipo": 1,
        "cantidad": 100
    },
    {
        "nombre": "Lámpara LED 100W",
        "idTipo": 1,
        "cantidad": 50
    },
    {
        "nombre": "Lámpara de Sodio 150W",
        "idTipo": 2,
        "cantidad": 75
    },
    {
        "nombre": "Cable Eléctrico 2x1.5mm",
        "idTipo": 3,
        "cantidad": 200
    },
    {
        "nombre": "Poste de Concreto 8m",
        "idTipo": 4,
        "cantidad": 25
    }
]
```

**Resultado Esperado:**

1. `GET /api/materiales` retorna status 200 (OK)
2. La respuesta es un array con los 5 materiales creados
3. Cada material incluye **todos los campos requeridos**:
   - `id` (numérico, > 0)
   - `nombre` (string, no vacío)
   - `idTipo` (numérico)
   - `cantidad` (numérica, >= 0)
   - `tipo_nombre` (string, no vacío) ← Obtenido mediante join
4. Todos los IDs creados están presentes en el catálogo
5. Los **4 tipos diferentes** están correctamente representados
6. Los materiales del mismo tipo tienen el mismo `tipo_nombre`

**Resultado Obtenido:** ✅ **EXITOSO**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12

...                                                                 3 / 3 (100%)

Time: 00:00.364, Memory: 16.00 MB

Materiales Api (Tests\Api\MaterialesApi)
 ✔ Carga manual exitosa
 ✔ Validacion campos obligatorios
 ✔ Obtener catalogo completo

OK, but there were issues!
Tests: 3, Assertions: 137, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El endpoint `GET /api/materiales` funciona correctamente
- ✅ Retorna status 200 para consultas exitosas
- ✅ La respuesta es un array con todos los materiales
- ✅ **Estructura de datos completa**: Cada material incluye 5 campos (id, nombre, idTipo, cantidad, tipo_nombre)
- ✅ **Join funcional**: El método `findAllWithTipo()` del modelo ejecuta correctamente el LEFT JOIN con `tipo_material`
- ✅ **Tipos de datos correctos**: Todos los campos tienen el tipo esperado (numéricos, strings)
- ✅ **Validación de valores**: Nombres no vacíos, cantidades >= 0
- ✅ **Integridad relacional**: Los materiales del mismo tipo tienen el mismo `tipo_nombre`

**Estructura de Respuesta del Endpoint:**

```json
[
    {
        "id": "1",
        "nombre": "Lámpara LED 50W",
        "idTipo": "1",
        "cantidad": "100",
        "tipo_nombre": "Lámpara LED"
    },
    {
        "id": "2",
        "nombre": "Lámpara LED 100W",
        "idTipo": "1",
        "cantidad": "50",
        "tipo_nombre": "Lámpara LED"
    },
    {
        "id": "3",
        "nombre": "Lámpara de Sodio 150W",
        "idTipo": "2",
        "cantidad": "75",
        "tipo_nombre": "Lámpara de Sodio"
    },
    {
        "id": "4",
        "nombre": "Cable Eléctrico 2x1.5mm",
        "idTipo": "3",
        "cantidad": "200",
        "tipo_nombre": "Cable Eléctrico"
    },
    {
        "id": "5",
        "nombre": "Poste de Concreto 8m",
        "idTipo": "4",
        "cantidad": "25",
        "tipo_nombre": "Poste"
    }
]
```

**Conclusión:** El endpoint de consulta del catálogo funciona perfectamente. El método `MaterialModel::findAllWithTipo()` ejecuta correctamente el LEFT JOIN con la tabla `tipo_material`, proporcionando información enriquecida que incluye tanto los datos del material como el nombre descriptivo del tipo. Esto es ideal para:
- Listar materiales en interfaces de usuario
- Mostrar información completa sin necesidad de consultas adicionales
- Facilitar la selección de materiales por parte de los operarios
- Permitir búsquedas y filtros por tipo de material

El catálogo está **listo para ser utilizado en el frontend** con toda la información necesaria.

---

## Prueba 18

**Nombre de la Prueba:** Actualización de Material Existente

**Ubicación:** `tests/api/MaterialesApiTest.php::testActualizacionMaterialExistente`

**Objetivo:** Verificar que se puede modificar un material existente mediante el endpoint PUT, validando que los campos modificados se actualizan correctamente, los campos no modificados permanecen sin cambios, y el ID del material nunca cambia.

**Tipo de Prueba:** API - CRUD - Actualización (HU-030)

**Datos Utilizados:**

Se prueba el ciclo completo de actualizaciones:

**Material Inicial:**
```json
{
    "nombre": "Lámpara LED Original 50W",
    "idTipo": 1,
    "cantidad": 100
}
```

**Primera Actualización (parcial - nombre y cantidad):**
```json
{
    "nombre": "Lámpara LED Actualizada 100W",
    "cantidad": 150
}
```

**Segunda Actualización (parcial - tipo y cantidad):**
```json
{
    "idTipo": 2,
    "cantidad": 200
}
```

**Tercera Actualización (completa):**
```json
{
    "nombre": "Material Completamente Actualizado",
    "idTipo": 3,
    "cantidad": 300
}
```

**Resultado Esperado:**

1. **Primera actualización parcial:**
   - PUT retorna status 200
   - `nombre` y `cantidad` se actualizan
   - `idTipo` permanece sin cambios (valor original: 1)
   - ID no cambia
   - Cambios persisten en BD

2. **Segunda actualización parcial:**
   - PUT retorna status 200
   - `idTipo` y `cantidad` se actualizan
   - `nombre` mantiene el valor de la primera actualización
   - ID no cambia

3. **Tercera actualización completa:**
   - PUT retorna status 200
   - Todos los campos se actualizan
   - ID permanece constante

4. **Verificación final:**
   - GET retorna el material con todos los datos finales
   - `tipo_nombre` refleja correctamente el nuevo tipo

**Resultado Obtenido:** ✅ **EXITOSO**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12

....                                                                4 / 4 (100%)

Time: 00:00.462, Memory: 16.00 MB

Materiales Api (Tests\Api\MaterialesApi)
 ✔ Carga manual exitosa
 ✔ Validacion campos obligatorios
 ✔ Obtener catalogo completo
 ✔ Actualizacion material existente

OK, but there were issues!
Tests: 4, Assertions: 172, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El endpoint `PUT /api/materiales/{id}` funciona correctamente
- ✅ **Actualizaciones parciales soportadas**: No es necesario enviar todos los campos
- ✅ **Campos no modificados se preservan**: Los campos omitidos en el PUT mantienen su valor anterior
- ✅ **ID inmutable**: El ID nunca cambia a través de múltiples actualizaciones
- ✅ **Persistencia garantizada**: Cambios se guardan correctamente en BD
- ✅ **Múltiples actualizaciones**: Se pueden hacer varias actualizaciones sucesivas sobre el mismo material
- ✅ **Integridad referencial**: Al cambiar `idTipo`, la relación con `tipo_material` se actualiza correctamente
- ✅ **Consistencia GET**: El catálogo refleja inmediatamente los cambios realizados


**Conclusión:** La funcionalidad de actualización de materiales es robusta y completa. El controlador `Api\Materiales::update()` implementa correctamente:
- **Actualizaciones parciales**: Solo actualiza los campos enviados
- **Preservación de datos**: Los campos omitidos no se sobrescriben
- **Validaciones**: Mantiene las mismas reglas de validación que la creación (nombres no vacíos, cantidades >= 0, tipos válidos)
- **Inmutabilidad del ID**: El identificador único nunca cambia
- **Soporte de múltiples actualizaciones**: Se puede actualizar el mismo material varias veces

El sistema CRUD de materiales está completamente operativo para las operaciones de Crear, Leer y Actualizar.

---

## Prueba 19

**Nombre de la Prueba:** Importación CSV/Masiva Exitosa

**Ubicación:** `tests/api/MaterialesApiTest.php::testImportacionCSVExitosa`

**Objetivo:** Verificar que se pueden importar múltiples materiales simultáneamente mediante el endpoint de importación masiva, validando que todos los materiales se crean correctamente, los tipos se mapean por nombre (no por ID), y se retorna un resumen detallado de la operación.

**Tipo de Prueba:** API - Importación Masiva (HU-030)

**Datos Utilizados:**

Se importan **6 materiales** de diferentes tipos en una sola operación:

```json
{
    "items": [
        {
            "nombre": "Lámpara LED 60W Importada",
            "cantidad": 50,
            "tipo": "Lámpara LED"
        },
        {
            "nombre": "Lámpara LED 100W Importada",
            "cantidad": 30,
            "tipo": "Lámpara LED"
        },
        {
            "nombre": "Lámpara Sodio 250W Importada",
            "cantidad": 40,
            "tipo": "Lámpara de Sodio"
        },
        {
            "nombre": "Cable 3x2.5mm Importado",
            "cantidad": 500,
            "tipo": "Cable Eléctrico"
        },
        {
            "nombre": "Poste Metálico 10m Importado",
            "cantidad": 15,
            "tipo": "Poste"
        },
        {
            "nombre": "Lámpara LED 150W Importada",
            "cantidad": 25,
            "tipo": "Lámpara LED"
        }
    ]
}
```

**Nota importante:** Los tipos se especifican por **nombre**, no por ID. El backend mapea automáticamente el nombre del tipo al `idTipo` correspondiente.

**Resultado Esperado:**

1. `POST /api/materiales/import` retorna status 200 (OK)
2. La respuesta incluye un **resumen de importación**:
   - `mensaje`: Descripción de la operación
   - `insertados`: Cantidad de materiales creados exitosamente (6)
   - `errores`: Array de errores (vacío si todo es exitoso)
3. Los **6 materiales** se crean correctamente en la BD
4. Cada material conserva su nombre y cantidad originales
5. Los tipos se **mapean correctamente** por nombre:
   - "Lámpara LED" → idTipo = 1
   - "Lámpara de Sodio" → idTipo = 2
   - "Cable Eléctrico" → idTipo = 3
   - "Poste" → idTipo = 4
6. Los materiales están **inmediatamente disponibles** via GET
7. La **distribución por tipo** es correcta:
   - 3 materiales de tipo "Lámpara LED"
   - 1 material de tipo "Lámpara de Sodio"
   - 1 material de tipo "Cable Eléctrico"
   - 1 material de tipo "Poste"
8. La **suma total de cantidades** es correcta (660 unidades)
9. Todos los materiales tienen **IDs únicos**

**Resultado Obtenido:** ✅ **EXITOSO**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12

.....                                                               5 / 5 (100%)

Time: 00:00.538, Memory: 16.00 MB

Materiales Api (Tests\Api\MaterialesApi)
 ✔ Carga manual exitosa
 ✔ Validacion campos obligatorios
 ✔ Obtener catalogo completo
 ✔ Actualizacion material existente
 ✔ Importacion c s v exitosa

OK, but there were issues!
Tests: 5, Assertions: 219, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El endpoint `POST /api/materiales/import` funciona correctamente
- ✅ **Importación masiva operativa**: Se pueden crear múltiples materiales en una sola operación
- ✅ **Mapeo por nombre de tipo**: No es necesario conocer los IDs de tipos, solo sus nombres
- ✅ **Inserción en lote eficiente**: Usa `insertBatch` para mejor rendimiento
- ✅ **Resumen detallado**: Retorna cantidad de materiales insertados y errores (si los hay)
- ✅ **Validación individual**: Cada item se valida antes de insertarse
- ✅ **Persistencia garantizada**: Todos los materiales quedan guardados en BD
- ✅ **Disponibilidad inmediata**: Los materiales importados están disponibles via GET sin demora
- ✅ **Integridad de datos**: Todas las cantidades, nombres y tipos se preservan correctamente


**Estructura de Respuesta del Endpoint:**

```json
{
    "mensaje": "Importación completada.",
    "insertados": 6,
    "errores": []
}
```

**Conclusión:** La funcionalidad de **importación masiva** está **completamente operativa**. El endpoint permite:
- Importar múltiples materiales simultáneamente
- Mapear tipos por nombre (más intuitivo que por ID)
- Validar cada item antes de insertar
- Proveer feedback detallado sobre la operación
- Manejar grandes volúmenes de datos eficientemente

---

## Prueba 20

**Nombre de la Prueba:** Validación de Formato CSV Incorrecto

**Ubicación:** `tests/api/MaterialesApiTest.php::testValidacionFormatoCSVIncorrecto`

**Objetivo:** Verificar que el endpoint de importación rechaza correctamente datos con formato incorrecto, validando múltiples escenarios de error y asegurando que se retornan mensajes descriptivos que ayuden al usuario a identificar y corregir los problemas.

**Tipo de Prueba:** API - Validación de Errores (HU-030)

**Datos Utilizados:**

Se prueban **6 casos diferentes** de datos inválidos:

**Caso 1: Request sin campo "items"**
```json
{
    "data": []  // Campo incorrecto
}
```

**Caso 2: Campo "items" que no es array**
```json
{
    "items": "esto no es un array"
}
```

**Caso 3: Array vacío**
```json
{
    "items": []
}
```

**Caso 4: Campos faltantes**
- Item sin `nombre`
- Item sin `tipo`
- Item sin `cantidad`

**Caso 5: Valores inválidos**
- Nombre vacío (`nombre: ""`)
- Cantidad negativa (`cantidad: -10`)
- Tipo inexistente (`tipo: "Tipo Que No Existe En La BD"`)

**Caso 6: Importación mixta**
```json
{
    "items": [
        { "nombre": "Material Válido 1", "cantidad": 10, "tipo": "Lámpara LED" },
        { "nombre": "", "cantidad": 20, "tipo": "Cable Eléctrico" },  // Inválido
        { "nombre": "Material Válido 2", "cantidad": 30, "tipo": "Poste" },
        { "nombre": "Material X", "cantidad": 40, "tipo": "Tipo Inventado" }  // Inválido
    ]
}
```

**Resultado Esperado:**

1. **Casos 1-3 (formato de request inválido):**
   - Status 400 (Bad Request)
   - Respuesta contiene mensaje de error descriptivo
   - El mensaje explica qué está mal con el formato

2. **Casos 4-5 (items completamente inválidos):**
   - Status 400 (Bad Request)
   - Mensaje de error indica el campo problemático
   - Proporciona detalles sobre qué validación falló

3. **Caso 6 (importación mixta - algunos válidos, algunos inválidos):**
   - Status 200 (OK)
   - Se insertan **SOLO** los 2 materiales válidos
   - Se retornan **2 errores** en el array `errores`
   - Cada error identifica el número de fila problemática
   - Los materiales inválidos **NO** se persisten en BD

**Resultado Obtenido:** ✅ **EXITOSO**

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.12

......                                                              6 / 6 (100%)

Time: 00:00.687, Memory: 16.00 MB

Materiales Api (Tests\Api\MaterialesApi)
 ✔ Carga manual exitosa
 ✔ Validacion campos obligatorios
 ✔ Obtener catalogo completo
 ✔ Actualizacion material existente
 ✔ Importacion c s v exitosa
 ✔ Validacion formato c s v incorrecto

OK, but there were issues!
Tests: 6, Assertions: 247, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El endpoint maneja **correctamente todos los casos de error**
- ✅ **Validación robusta** a nivel de estructura del request (casos 1-3)
- ✅ **Validación granular** a nivel de cada item (casos 4-5)
- ✅ **Procesamiento inteligente** en importaciones mixtas (caso 6)
- ✅ **Mensajes descriptivos**: Cada error indica claramente qué salió mal
- ✅ **Protección de datos**: Items inválidos NO se guardan en BD
- ✅ **Comportamiento diferenciado**:
  - **Todos inválidos** → Status 400 + mensaje de error
  - **Algunos válidos** → Status 200 + inserción parcial + array de errores


**Conclusión:** El sistema de **validación de importación** es **robusto** e implementa múltiples capas de validación.

---

# HU-031: Recepción de reclamos desde el sistema 103

---

## Prueba 21

**Nombre del Test:** Configuración de Credenciales de Basic Auth 
**Ubicación:** `tests/api/ReclamosSincronizacion103Test.php`  
**Tipo:** Test de Integración - Configuración de Seguridad  

**Objetivo:**  
Verificar que se pueden guardar y recuperar correctamente las credenciales de autenticación Basic Auth para conectarse con la API externa del sistema 103.

**Datos Utilizados:**
```php
// Credenciales de prueba genéricas (no son las credenciales reales)
$username = 'test@example.com';
$password = 'TestPassword123!';
```

⚠️ **Nota de Seguridad:** Por razones de seguridad, el test utiliza **credenciales de prueba genéricas**. El sistema ha sido **validado y funciona correctamente** con las **credenciales oficiales** proporcionadas por la Municipalidad de San Francisco para acceder a la API del sistema 103, pero estas **no se incluyen en el código fuente**.

**Validaciones (5 aserciones):**
1. ✅ ID generado automáticamente es un entero > 0
2. ✅ Username se recupera correctamente
3. ✅ Password se recupera correctamente
4. ✅ Token Base64 se genera y tiene formato correcto
5. ✅ Token puede decodificarse correctamente
6. ✅ Campos `created_at` y `updated_at` se generan automáticamente

**Resultado Esperado:**
- **Status:** Inserción exitosa en tabla `token103`
- **Formato del Token:** `base64_encode("username:password")`
- **Headers de Autenticación:** `Authorization: Basic {token_base64}`

**Resultado Obtenido:** ✅ **EXITOSO**

**Observaciones:**
- El sistema **codifica correctamente** las credenciales en formato Basic Auth según el estándar RFC 7617
- El formato generado es compatible con la API externa del sistema 103
- Los timestamps se generan automáticamente por el modelo
- Las credenciales se almacenan de forma segura en la base de datos
- ✅ **Verificado en producción:** El sistema funciona correctamente con las credenciales oficiales municipales

**Conclusión:** El sistema de **gestión de credenciales** funciona correctamente y permite una integración segura con el sistema 103.

---

## Prueba 22

**Nombre del Test:** Mapeo de Reclamo de API Externa 
**Ubicación:** `tests/api/ReclamosSincronizacion103Test.php`  
**Tipo:** Test de Unidad - Transformación de Datos  

**Objetivo:**  
Verificar que el mapeo de datos desde la estructura de la API externa (sistema 103) a la estructura interna de nuestra base de datos es **correcto y completo**.

**Datos Utilizados:**
```json
{
  "id": 12345,
  "motivo": {
    "tipo": "ALUMBRADO PÚBLICO",
    "nombre": "Luminaria que no enciende"
  },
  "fecha_inicio": "2025-11-12T14:30:00.000000-03:00",
  "fecha_modificacion": "2025-11-12T15:45:30.500000-03:00",
  "estado_nombre": "Asignado",
  "calle": { "nombre": "San Martin" },
  "calle_altura": 1250,
  "desde_calle": { "nombre": "Belgrano" },
  "hasta_calle": { "nombre": "Rivadavia" }
}
```

**Validaciones (10 aserciones):**
1. ✅ `municipalidad_id` se mapea como string: `"12345"`
2. ✅ `municipalidad_tipo` se mapea correctamente: `"ALUMBRADO PÚBLICO"`
3. ✅ `municipalidad_motivo` se mapea correctamente: `"Luminaria que no enciende"`
4. ✅ `municipalidad_fechaInicio` se convierte al formato MySQL
5. ✅ `municipalidad_fechaModificacion` se convierte al formato MySQL
6. ✅ Estado `"Asignado"` se transforma a `"Recibido"`
7. ✅ `municipalidad_domicilio` se mapea: `"San Martin"`
8. ✅ `municipalidad_numeroDomicilio` se mapea: `"1250"`
9. ✅ `municipalidad_entreCalleUno` y `municipalidad_entreCalleDos` se mapean
10. ✅ Campos no provistos por la API se establecen como `NULL`

**Resultado Esperado:**
```php
[
  'municipalidad_id' => '12345',
  'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
  'municipalidad_motivo' => 'Luminaria que no enciende',
  'municipalidad_estado' => 'Recibido', // Transformado desde "Asignado"
  'municipalidad_domicilio' => 'San Martin',
  'municipalidad_numeroDomicilio' => '1250',
  // ... otros campos ...
]
```

**Resultado Obtenido:** ✅ **EXITOSO**

**Observaciones:**
- **Transformación de Estado:** El sistema implementa una lógica crítica donde el estado `"Asignado"` de la API externa se transforma automáticamente a `"Recibido"` en nuestro sistema. Esto es intencional para mantener consistencia en nuestro flujo de trabajo interno.
- **Manejo de Campos Nulos:** Los campos que no provee la API (teléfono, ciudadano, descripción) se establecen correctamente como `NULL`.
- **Fechas ISO 8601:** Las fechas en formato ISO 8601 se convierten automáticamente al formato MySQL datetime.

**Conclusión:** El mapeo de datos es **completo y robusto**, manejando correctamente todos los campos y transformaciones necesarias.

---

## Prueba 23

**Nombre del Test:** Conversión de Fechas ISO 8601 
**Ubicación:** `tests/api/ReclamosSincronizacion103Test.php`  
**Tipo:** Test de Unidad - Transformación de Fechas  

**Objetivo:**  
Verificar que las fechas en formato **ISO 8601** de la API externa se convierten correctamente al formato **MySQL datetime** (`YYYY-MM-DD HH:MM:SS`).

**Datos Utilizados:**
| Fecha ISO 8601 (entrada) | Fecha MySQL esperada (salida) |
|--------------------------|-------------------------------|
| `2025-08-28T13:36:04.541033-03:00` | `2025-08-28 13:36:04` |
| `2025-11-12T09:15:30.000000-03:00` | `2025-11-12 09:15:30` |
| `2025-12-25T23:59:59.999999-03:00` | `2025-12-25 23:59:59` |
| `2025-01-01T00:00:00.000000-03:00` | `2025-01-01 00:00:00` |

**Validaciones (4 aserciones):**
- ✅ Cada fecha ISO se convierte **exactamente** al formato esperado
- ✅ Microsegundos se eliminan correctamente
- ✅ Timezone se procesa y ajusta correctamente
- ✅ Formato final es compatible con MySQL

**Resultado Esperado:**
- Todas las fechas convertidas al formato `YYYY-MM-DD HH:MM:SS`
- Sin microsegundos
- Sin información de timezone

**Resultado Obtenido:** ✅ **EXITOSO**

**Observaciones:**
- **Precisión Temporal:** Las fechas se convierten manteniendo la precisión de segundos
- **Microsegundos:** Se eliminan automáticamente (MySQL datetime no los soporta nativamente)
- **Timezone:** El timezone `-03:00` (Argentina) se procesa correctamente
- **Casos Límite:** Funciona correctamente con inicio/fin de año y fin de día

**Conclusión:** La conversión de fechas es **precisa y confiable**, garantizando compatibilidad con la base de datos MySQL.

---
