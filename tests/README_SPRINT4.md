# Tests del Sprint 4

---

## Prueba 1

**Nombre de la Prueba:** Registro de material con datos válidos

**Ubicación:** `tests/api/ReclamosMaterialesApiTest.php::testRegistroMaterialConDatosValidos`

**Objetivo:** Verificar que se puede registrar un material utilizado en un reclamo cuando se proporcionan material_id, cantidad y observación válidos. Debe retornar 201 y vincular correctamente el material al reclamo.

**Tipo de Prueba:** API

**Datos Utilizados:**
- Reclamo: municipalidad_id="10001", municipalidad_tipo="ALUMBRADO PÚBLICO", municipalidad_motivo="Luminaria apagada", municipalidad_estado="Recibido", prioridad="Baja"
- Material: nombre="Lámpara LED 50W", idTipo=1 (Lámpara LED), cantidad=100
- Datos del registro: material_id={materialId}, cantidad=2, observacion="Se instalaron 2 lámparas nuevas"
- Endpoint: POST `/api/reclamos/{reclamoId}/materiales`

**Resultado Esperado:**
- Status HTTP: 201 (Created)
- Respuesta JSON con:
  - id del registro material_reclamo generado
  - reclamo_id coincidente con el reclamo
  - material_id coincidente con el material
  - cantidad=2
  - observacion="Se instalaron 2 lámparas nuevas"
  - fecha generada automáticamente
  - material_nombre (JOIN con tabla material)
  - tipo_material_nombre (JOIN con tabla tipo_material)
- El registro debe existir en la base de datos en la tabla material_reclamo
- El material debe quedar vinculado al reclamo

**Resultado Obtenido:** ✅ EXITOSO (después de corrección)

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:04.351, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 22, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 22 assertions
- El registro de material se guarda correctamente en la base de datos
- La respuesta incluye todos los campos esperados, incluyendo los JOINs con material, tipo_material y usuario
- El material queda correctamente vinculado al reclamo en la tabla `material_reclamo`

---

## Prueba 2

**Nombre de la Prueba:** Validación de material_id obligatorio

**Ubicación:** `tests/api/ReclamosMaterialesApiTest.php::testValidacionMaterialIdObligatorio`

**Objetivo:** Verificar que el sistema valida correctamente que el material_id es obligatorio. Debe retornar 400 cuando no se proporciona material_id o está vacío.

**Tipo de Prueba:** API

**Datos Utilizados:**
- Reclamo: municipalidad_id="10002", municipalidad_tipo="ALUMBRADO PÚBLICO", municipalidad_motivo="Luminaria apagada", municipalidad_estado="Recibido", prioridad="Baja"
- Caso 1: POST sin material_id (campo omitido), cantidad=2, observacion="Prueba sin material_id"
- Caso 2: POST con material_id="" (string vacío), cantidad=2, observacion="Prueba con material_id vacío"
- Caso 3: POST con material_id=null, cantidad=2, observacion="Prueba con material_id null"
- Caso 4: POST con material_id=0, cantidad=2, observacion="Prueba con material_id = 0"
- Endpoint: POST `/api/reclamos/{reclamoId}/materiales`

**Resultado Esperado:**
- Status HTTP: 400 (Bad Request) para todos los casos
- Respuesta JSON con mensaje de error indicando que el material es obligatorio
- No se debe crear ningún registro en la tabla `material_reclamo`
- El mensaje de error debe mencionar "material"

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:02.799, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 17, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 17 assertions
- Todos los casos probados (sin material_id, material_id vacío, material_id null, material_id = 0) retornan correctamente status 400
- Los mensajes de error mencionan que el material es obligatorio
- No se crea ningún registro en la tabla `material_reclamo` cuando el material_id es inválido
- La validación funciona correctamente para todos los casos de material_id inválido

---

## Prueba 3

**Nombre de la Prueba:** Registro de material con cantidad inválida (negativa o cero)

**Ubicación:** `tests/api/ReclamosMaterialesApiTest.php::testRegistroMaterialConCantidadInvalida`

**Objetivo:** Verificar que cuando se proporciona una cantidad inválida (<= 0), el sistema guarda la cantidad como null, ya que es opcional. El registro debe crearse exitosamente pero con cantidad = null.

**Tipo de Prueba:** API

**Datos Utilizados:**
- Reclamo: municipalidad_id="10003", municipalidad_tipo="ALUMBRADO PÚBLICO", municipalidad_motivo="Luminaria apagada", municipalidad_estado="Recibido", prioridad="Media"
- Material: nombre="Lámpara LED 100W", idTipo=1 (Lámpara LED), cantidad=50
- Caso 1: material_id={materialId}, cantidad=0, observacion="Prueba con cantidad = 0"
- Caso 2: material_id={materialId}, cantidad=-5, observacion="Prueba con cantidad negativa"
- Caso 3: material_id={materialId}, cantidad="", observacion="Prueba con cantidad string vacío"
- Caso 4: material_id={materialId} (sin campo cantidad), observacion="Prueba sin campo cantidad"
- Endpoint: POST `/api/reclamos/{reclamoId}/materiales`

**Resultado Esperado:**
- Status HTTP: 201 (Created) para todos los casos
- La cantidad debe guardarse como null en la base de datos cuando es <= 0, string vacío o no se proporciona
- El registro debe crearse exitosamente con los demás campos correctos
- Todos los registros deben tener cantidad = null en BD

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:04.286, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 29, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 29 assertions
- Todos los casos (cantidad = 0, cantidad negativa, cantidad string vacío, sin cantidad) retornan status 201 correctamente
- La cantidad se guarda como null en la base de datos cuando es inválida (<= 0) o no se proporciona
- El registro se crea exitosamente con los demás campos (material_id, observacion, fecha) correctos
- La funcionalidad cumple con el requerimiento de que la cantidad es opcional y se normaliza a null cuando es inválida

---

## Prueba 4

**Nombre de la Prueba:** Crear material nuevo y registrarlo en reclamo (flujo completo)

**Ubicación:** `tests/api/ReclamosMaterialesApiTest.php::testCrearMaterialNuevoYRegistrarloEnReclamo`

**Objetivo:** Verificar el flujo completo end-to-end de crear un material nuevo y luego registrarlo en un reclamo. Debe verificar que ambos pasos funcionan correctamente en secuencia.

**Tipo de Prueba:** API - Integración

**Datos Utilizados:**
- Reclamo: municipalidad_id="10004", municipalidad_tipo="ALUMBRADO PÚBLICO", municipalidad_motivo="Luminaria apagada", municipalidad_estado="Recibido", prioridad="Baja"
- Material nuevo: nombre="Lámpara LED 75W Nueva", idTipo=1 (Lámpara LED), cantidad=0
- Datos del registro: material_id={materialId}, cantidad=3, observacion="Material nuevo creado y utilizado en el reclamo"
- Endpoint 1: POST `/api/materiales` (crear material)
- Endpoint 2: POST `/api/reclamos/{reclamoId}/materiales` (registrar material en reclamo)
- Endpoint 3: GET `/api/materiales` (verificar que el material está en el catálogo)
- Endpoint 4: GET `/api/reclamos/{reclamoId}/materiales` (verificar historial)

**Resultado Esperado:**
- Paso 1: Crear material nuevo → Status 201, material creado con ID generado
- Paso 2: Registrar material en reclamo → Status 201, registro material_reclamo creado
- El material debe existir en la base de datos
- El registro material_reclamo debe existir en la base de datos
- El material debe estar vinculado al reclamo
- El material nuevo debe estar disponible en el catálogo
- El historial de materiales del reclamo debe incluir el material nuevo

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:37.167, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 40, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 40 assertions
- El flujo completo funciona correctamente: crear material → registrar en reclamo
- El material se crea exitosamente y queda disponible en el catálogo
- El material se registra correctamente en el reclamo
- La respuesta incluye todos los campos esperados, incluyendo los JOINs con material, tipo_material y usuario
- El material queda correctamente vinculado al reclamo en la tabla `material_reclamo`
- El historial de materiales del reclamo incluye correctamente el material nuevo
- El flujo end-to-end funciona como se espera, permitiendo crear un material nuevo y utilizarlo inmediatamente en un reclamo

---

## Prueba 5

**Nombre de la Prueba:** Múltiples materiales en un mismo reclamo

**Ubicación:** `tests/api/ReclamosMaterialesApiTest.php::testMultiplesMaterialesEnMismoReclamo`

**Objetivo:** Verificar que se pueden registrar varios materiales diferentes en el mismo reclamo y que todos quedan vinculados correctamente.

**Tipo de Prueba:** API

**Datos Utilizados:**
- Reclamo: municipalidad_id="10005", municipalidad_tipo="ALUMBRADO PÚBLICO", municipalidad_motivo="Múltiples materiales necesarios", municipalidad_estado="Recibido", prioridad="Baja"
- Material 1: nombre="Lámpara LED 50W", idTipo=1 (Lámpara LED), cantidad=2, observacion="Instalación de lámpara LED"
- Material 2: nombre="Cable Eléctrico 2x1.5mm", idTipo=3 (Cable Eléctrico), cantidad=10, observacion="Cable necesario para instalación"
- Material 3: nombre="Poste de Concreto 8m", idTipo=4 (Poste), cantidad=1, observacion="Poste nuevo para reemplazo"
- Material 4: nombre="Lámpara de Sodio 150W", idTipo=2 (Lámpara de Sodio), cantidad=3, observacion="Lámparas de sodio de repuesto"
- Endpoint: POST `/api/reclamos/{reclamoId}/materiales` (múltiples veces con diferentes materiales)
- Endpoint verificación: GET `/api/reclamos/{reclamoId}/materiales`

**Resultado Esperado:**
- Todos los registros deben retornar status 201 (Created)
- Debe haber 4 registros en la tabla `material_reclamo` vinculados al mismo reclamo
- Cada material debe estar correctamente vinculado con sus datos (cantidad, observacion)
- El historial de materiales del reclamo debe incluir los 4 materiales
- Los materiales deben tener tipos diferentes correctamente asociados
- No debe haber materiales duplicados
- El historial debe estar ordenado por fecha DESC

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:04.164, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 104, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 104 assertions
- Todos los materiales se registraron exitosamente en el mismo reclamo
- Cada material quedó correctamente vinculado al reclamo con sus datos específicos
- El historial de materiales del reclamo incluye correctamente los 4 materiales diferentes
- Los materiales tienen tipos diferentes correctamente asociados (Lámpara LED, Cable Eléctrico, Poste, Lámpara de Sodio)
- No se duplicaron registros, cada material aparece una sola vez
- El historial está ordenado por fecha DESC (más reciente primero)
- La funcionalidad permite registrar múltiples materiales diferentes en un mismo reclamo sin problemas

---

# HU-033: Enviar reclamos cerrados al sistema 103

---

## Prueba 6

**Nombre de la Prueba:** Envío fallido por falta de credenciales

**Ubicación:** `tests/api/CierreReclamosSistema103Test.php::testEnvioFallidoPorFaltaDeCredenciales`

**Objetivo:** Verificar que cuando no hay credenciales configuradas para el sistema 103, el método enviarCierreASistema103() retorna un error apropiado indicando la falta de credenciales, y el reclamo NO se marca como cerrado cuando falla el envío.

**Tipo de Prueba:** API - Integración

**Datos Utilizados:**
- Reclamo: municipalidad_id="50002", municipalidad_tipo="ALUMBRADO PÚBLICO", municipalidad_motivo="Luminaria apagada - Test credenciales", municipalidad_estado="Completado", prioridad="Baja", cerrado=0, fecha_cierre=null
- Credenciales: NO existen en la tabla token103 (simulado truncando la tabla)
- Método probado: `enviarCierreASistema103($municipalidadId)` usando Reflection para acceder al método privado

**Resultado Esperado:**
- El método enviarCierreASistema103() debe retornar un array con:
  - success=false
  - error="No hay credenciales configuradas para el sistema 103"
- El reclamo NO debe cambiar su estado (cerrado=0 debe permanecer)
- El reclamo NO debe tener fecha_cierre (debe permanecer null)
- El estado del reclamo debe seguir siendo "Completado"
- NO debe existir un registro en el historial de cierre

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:03.747, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 14, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 14 assertions
- El método enviarCierreASistema103() retorna correctamente success=false cuando no hay credenciales
- El error retornado es apropiado: "No hay credenciales configuradas para el sistema 103"
- El reclamo NO se marcó como cerrado cuando falla el envío por falta de credenciales (cerrado=0 se mantiene)
- El reclamo NO tiene fecha_cierre cuando falla el envío (fecha_cierre=null se mantiene)
- El estado del reclamo permanece en "Completado" (no cambia)
- NO se creó registro en el historial de cierre cuando falla el envío
- El sistema maneja correctamente la ausencia de credenciales y previene el cierre de reclamos cuando no se puede enviar al sistema 103

---

## Prueba 7

**Nombre de la Prueba:** Validación de Basic Auth en el envío

**Ubicación:** `tests/api/CierreReclamosSistema103Test.php::testValidacionBasicAuthEnElEnvio`

**Objetivo:** Verificar que el sistema obtiene correctamente las credenciales del Token103Model, genera correctamente el token Basic Auth (base64) según el estándar RFC 7617, y que los headers HTTP incluyen Authorization correctamente con el formato "Basic {tokenBase64}".

**Tipo de Prueba:** API - Unit - Autenticación

**Datos Utilizados:**
- Credenciales: username="testuser@example.com", password="TestPassword123#!"
- Token103Model: credenciales insertadas en la tabla token103
- Método de obtención: `$tokenModel->orderBy('id', 'DESC')->first()`
- Generación de token: `base64_encode(username:password)`
- Formato del header: `"Authorization: Basic {tokenBase64}"`

**Resultado Esperado:**
- Token103Model debe obtener las credenciales correctamente usando `orderBy('id', 'DESC')->first()`
- El token Base64 debe generarse correctamente con el formato `base64_encode(username:password)`
- El token Base64 debe ser decodificable y devolver la cadena original "username:password"
- El token Base64 debe ser determinístico (mismas credenciales = mismo token)
- El header Authorization debe tener el formato "Authorization: Basic {tokenBase64}"
- El token Base64 debe contener solo caracteres válidos de Base64

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:04.108, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 23, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 23 assertions
- Token103Model obtiene correctamente las credenciales usando `orderBy('id', 'DESC')->first()`
- El token Base64 se genera correctamente según el estándar Basic Auth (RFC 7617): `base64_encode(username:password)`
- El token Base64 es decodificable y devuelve correctamente la cadena original "username:password"
- Las credenciales decodificadas (username y password) coinciden con las originales
- El token Base64 contiene solo caracteres válidos de Base64 (A-Z, a-z, 0-9, +, /, =)
- El header Authorization tiene el formato correcto: "Authorization: Basic {tokenBase64}"
- El token Base64 es determinístico (mismas credenciales siempre producen el mismo token)
- El sistema genera correctamente la autenticación Basic Auth según el estándar RFC 7617
- Los headers HTTP incluyen Authorization correctamente con el formato esperado

---

## Prueba 8

**Nombre de la Prueba:** Validación de que solo reclamos "Completado" se envían

**Ubicación:** `tests/api/CierreReclamosSistema103Test.php::testValidacionQueSoloReclamosCompletadoSeEnvian`

**Objetivo:** Verificar que cuando se intenta cerrar un reclamo que NO está en estado "Completado", el sistema NO lo envía al sistema 103 y NO lo marca como cerrado. Debe retornar un error apropiado indicando que el reclamo no está en el estado correcto para ser cerrado.

**Tipo de Prueba:** API - Integración - Validación

**Datos Utilizados:**
- Credenciales: username="testuser@example.com", password="TestPassword123#!" (existen en Token103Model)
- Reclamos de prueba con estados inválidos para cierre:
  - Estado 1: "Recibido"
  - Estado 2: "En Proceso"
  - Estado 3: "Pendiente"
  - Estado 4: "Cancelado"
- Cada reclamo: municipalidad_id único, municipalidad_tipo="ALUMBRADO PÚBLICO", municipalidad_motivo="Luminaria apagada - Test estado {estado}", municipalidad_estado={estadoInvalido}, prioridad="Baja", cerrado=0, fecha_cierre=null
- Validación probada: El código verifica `if ($reclamo['municipalidad_estado'] !== 'Completado')` antes de llamar a `enviarCierreASistema103()`

**Resultado Esperado:**
- Los reclamos que NO están en estado "Completado" NO deben enviarse al sistema 103
- Los reclamos que NO están en estado "Completado" NO deben marcarse como cerrados (cerrado=0 debe mantenerse)
- Los reclamos que NO están en estado "Completado" NO deben tener fecha_cierre (debe permanecer null)
- El estado del reclamo NO debe cambiar (debe mantener su estado original)
- NO debe crearse registro en el historial de cierre para reclamos que no están en estado "Completado"
- El mensaje de error debe tener el formato: "Reclamo {municipalidad_id}: No está en estado Completado (Estado actual: {estado})"

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:06.558, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 58, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 58 assertions
- Se probaron 4 estados diferentes que NO son "Completado": "Recibido", "En Proceso", "Pendiente", "Cancelado"
- Todos los reclamos con estados diferentes a "Completado" NO se enviaron al sistema 103
- Ningún reclamo se marcó como cerrado cuando no estaba en estado "Completado" (cerrado=0 se mantiene)
- Ningún reclamo tiene fecha_cierre cuando no está en estado "Completado" (fecha_cierre=null se mantiene)
- El estado del reclamo permanece inalterado (no cambia)
- NO se creó registro en el historial de cierre para ningún reclamo que no estaba en estado "Completado"
- El mensaje de error tiene el formato correcto: "Reclamo {municipalidad_id}: No está en estado Completado (Estado actual: {estado})"
- El mensaje de error incluye el municipalidad_id y el estado actual del reclamo
- La validación funciona correctamente para todos los estados probados
- El sistema previene correctamente el envío de reclamos que no están en estado "Completado" al sistema 103
- El sistema valida correctamente que solo los reclamos en estado "Completado" pueden ser cerrados

---

## Prueba 9

**Nombre de la Prueba:** Validación de que no se reenvían reclamos ya cerrados

**Ubicación:** `tests/api/CierreReclamosSistema103Test.php::testValidacionQueNoSeReenvianReclamosYaCerrados`

**Objetivo:** Verificar que cuando se intenta cerrar un reclamo que ya está cerrado (cerrado = 1), el sistema NO lo envía nuevamente al sistema 103, NO modifica su fecha_cierre, y retorna un mensaje apropiado indicando que el reclamo ya está cerrado.

**Tipo de Prueba:** API - Integración - Validación

**Datos Utilizados:**
- Credenciales: username="testuser@example.com", password="TestPassword123#!" (existen en Token103Model)
- Reclamo ya cerrado:
  - municipalidad_id="50010"
  - municipalidad_tipo="ALUMBRADO PÚBLICO"
  - municipalidad_motivo="Luminaria apagada - Test reclamo ya cerrado"
  - municipalidad_estado="Completado"
  - prioridad="Baja"
  - cerrado=1 (ya está cerrado)
  - fecha_cierre="2025-01-10 14:30:00" (fecha de cierre original)
- Historial de cierre original: registro en historial_reclamo indicando que el reclamo ya fue cerrado anteriormente
- Validación probada: El código verifica `if ($reclamo['cerrado'] == 1)` antes de llamar a `enviarCierreASistema103()`

**Resultado Esperado:**
- El reclamo que ya está cerrado NO debe enviarse nuevamente al sistema 103
- El reclamo debe seguir cerrado (cerrado=1 debe mantenerse)
- La fecha_cierre NO debe cambiar (debe mantener la fecha original del primer cierre)
- El estado del reclamo debe seguir siendo "Completado" (no cambia)
- NO debe crearse un nuevo registro en el historial de cierre (debe haber solo UN registro del cierre original)
- El mensaje de error debe tener el formato: "Reclamo {municipalidad_id}: Ya está cerrado"

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:05.643, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 22, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 22 assertions
- El reclamo que ya está cerrado NO se envió nuevamente al sistema 103
- El reclamo mantiene su estado de cerrado (cerrado=1 se mantiene)
- La fecha_cierre NO cambió, mantiene la fecha original del primer cierre
- El estado del reclamo permanece en "Completado" (no cambia)
- NO se creó un nuevo registro en el historial de cierre (solo existe UN registro del cierre original)
- El historial mantiene la fecha de cierre original y los estados correctos (anterior: "Completado", actual: "Cerrado")
- El mensaje de error tiene el formato correcto: "Reclamo {municipalidad_id}: Ya está cerrado"
- El mensaje de error incluye el municipalidad_id del reclamo
- La validación funciona correctamente para prevenir el reenvío de reclamos ya cerrados
- El sistema previene correctamente el reenvío de reclamos ya cerrados al sistema 103
- El sistema protege la integridad de los datos de cierre (fecha_cierre original se mantiene)
- El sistema evita duplicar registros en el historial de cierre

---

## Prueba 10

**Nombre de la Prueba:** Transacción - Verificar que si falla el envío, el reclamo no se marca como cerrado

**Ubicación:** `tests/api/CierreReclamosSistema103Test.php::testTransaccionSiFallaEnvioReclamoNoSeMarcaComoCerrado`

**Objetivo:** Verificar que cuando el envío al sistema 103 falla (por ejemplo, por credenciales inválidas o error de conexión), el reclamo NO se marca como cerrado en la base de datos local, NO se registra en el historial, y el estado se mantiene en "Completado" con cerrado=0. Esto asegura la integridad transaccional: solo se cierra localmente si el envío externo es exitoso.

**Tipo de Prueba:** API - Integración - Transacción

**Datos Utilizados:**
- Reclamo: municipalidad_id="50020", municipalidad_tipo="ALUMBRADO PÚBLICO", municipalidad_motivo="Luminaria apagada - Test transacción fallo envío", municipalidad_estado="Completado", prioridad="Baja", cerrado=0, fecha_cierre=null
- Credenciales inválidas: username="invalid_user@example.com", password="InvalidPassword123!" (para forzar un fallo en el envío)
- Método probado: `enviarCierreASistema103($municipalidadId)` usando Reflection para acceder al método privado

**Resultado Esperado:**
- El envío al sistema 103 debe fallar (success=false, error no vacío)
- El reclamo NO debe marcarse como cerrado cuando falla el envío (cerrado=0 debe mantenerse)
- El reclamo NO debe tener fecha_cierre cuando falla el envío (fecha_cierre=null debe mantenerse)
- El estado del reclamo debe seguir siendo "Completado" (no cambia)
- NO debe crearse registro en el historial de cierre cuando falla el envío
- El estado del reclamo debe mantenerse igual al inicial después del fallo
- Debe haber un mensaje de error cuando falla el envío

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:06.086, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 16, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 16 assertions
- El envío al sistema 103 falló correctamente (success=false, error no vacío)
- El reclamo NO se marcó como cerrado cuando falló el envío (cerrado=0 se mantiene)
- El reclamo NO tiene fecha_cierre cuando falla el envío (fecha_cierre=null se mantiene)
- El estado del reclamo permanece en "Completado" (no cambia)
- NO se creó registro en el historial de cierre cuando falla el envío
- El estado del reclamo se mantiene igual al inicial después del fallo (cerrado=0, fecha_cierre=null, estado="Completado")
- Hay un mensaje de error cuando falla el envío
- La integridad transaccional se mantiene: el reclamo solo se cierra localmente si el envío externo es exitoso
- El sistema previene correctamente el cierre local cuando falla el envío al sistema 103
- El sistema asegura que los datos locales y externos permanecen sincronizados (no se cierra localmente sin éxito externo)

---

## Prueba 11

**Nombre de la Prueba:** Generación de token Basic Auth

**Ubicación:** `tests/api/CierreReclamosSistema103Test.php::testGeneracionTokenBasicAuth`

**Objetivo:** Verificar que el sistema genera correctamente el token Base64 a partir de username:password según el estándar Basic Auth (RFC 7617), y que el formato del token es correcto y válido.

**Tipo de Prueba:** API - Unit - Autenticación

**Datos Utilizados:**
- Credenciales: username="testuser@example.com", password="TestPassword123#!"
- Formato esperado: base64_encode(username:password)
- Validaciones: formato Base64 válido, decodificable, determinístico

**Resultado Esperado:**
- El token Base64 se genera correctamente a partir de username:password
- El token Base64 contiene solo caracteres válidos de Base64 (A-Z, a-z, 0-9, +, /, =)
- El token Base64 es decodificable y devuelve la cadena original "username:password"
- Las credenciales decodificadas (username y password) coinciden con las originales
- La longitud del token Base64 es múltiplo de 4 (requisito del formato Base64)
- El token Base64 es determinístico (mismas credenciales = mismo token)
- Diferentes credenciales producen diferentes tokens Base64
- El token Base64 maneja correctamente caracteres especiales en el password

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:04.501, Memory: 14.00 MB

OK, but there were issues!
Tests: 1, Assertions: 13, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 13 assertions
- El token Base64 se genera correctamente a partir de username:password según el estándar Basic Auth (RFC 7617)
- El token Base64 contiene solo caracteres válidos de Base64 (A-Z, a-z, 0-9, +, /, =)
- El token Base64 es decodificable y devuelve correctamente la cadena original "username:password"
- Las credenciales decodificadas (username y password) coinciden con las originales
- La longitud del token Base64 es múltiplo de 4 (cumple con el requisito del formato Base64)
- El token Base64 es determinístico (mismas credenciales siempre producen el mismo token)
- Diferentes credenciales producen diferentes tokens Base64
- El token Base64 maneja correctamente caracteres especiales en el password sin afectar la generación
- El formato del token es correcto y válido según el estándar Basic Auth
- La generación del token cumple con todos los requisitos del estándar RFC 7617

---

# HU-037: Registro y análisis del tiempo de reparación de reclamos

---

## Prueba 12

**Nombre de la Prueba:** Registro de tiempo de reparación con datos válidos

**Ubicación:** `tests/api/TiempoReparacionApiTest.php::testRegistroTiempoReparacionConDatosValidos`

**Objetivo:** Verificar que se puede registrar el tiempo de reparación de un reclamo cuando se proporciona tiempo_reparacion_minutos válido. Debe retornar 200 y guardar el tiempo en la base de datos, vinculándolo correctamente al reclamo y usuario.

**Tipo de Prueba:** API

**Datos Utilizados:**
- Reclamo: municipalidad_id="10001", municipalidad_tipo="ALUMBRADO PÚBLICO", municipalidad_motivo="Luminaria apagada", municipalidad_estado="Completado", prioridad="Baja"
- Tiempo de reparación: tiempo_reparacion_minutos=45
- Endpoint: POST `/api/reclamos/{reclamoId}/tiempo-reparacion`
- Payload: `{"tiempo_reparacion_minutos": 45}`

**Resultado Esperado:**
- El endpoint debe retornar una respuesta válida con los datos del tiempo registrado
- La respuesta debe incluir: id, reclamo_id, motivo_reclamo, tiempo_minutos, usuario_id, fecha_registro
- El tiempo debe guardarse en la base de datos en la tabla tiempo_reparacion
- El tiempo debe estar vinculado correctamente al reclamo (reclamo_id)
- El motivo_reclamo debe coincidir con el motivo del reclamo
- El tiempo_minutos debe coincidir con el valor enviado

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:05.142, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 23, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 23 assertions
- El endpoint registra correctamente el tiempo de reparación en la base de datos
- La respuesta incluye todos los campos esperados: id, reclamo_id, motivo_reclamo, tiempo_minutos, usuario_id, fecha_registro
- El tiempo queda correctamente vinculado al reclamo (reclamo_id coincide)
- El motivo_reclamo se obtiene correctamente del reclamo asociado
- El tiempo_minutos se guarda correctamente con el valor enviado
- La fecha_registro se genera automáticamente al guardar
- El usuario_id se asigna correctamente (0 si no hay sesión activa, como está implementado)
- El registro se guarda correctamente en la tabla tiempo_reparacion
- Todos los datos de la respuesta coinciden con los datos guardados en la base de datos

---

## Prueba 13

**Nombre de la Prueba:** Validación de tiempo de reparación mayor a 0

**Ubicación:** `tests/api/TiempoReparacionApiTest.php::testValidacionTiempoReparacionMayorACero`

**Objetivo:** Verificar que el sistema valida correctamente que el tiempo_reparacion_minutos debe ser mayor a 0. Debe retornar 400 cuando se proporciona tiempo_reparacion_minutos = 0 o negativo.

**Tipo de Prueba:** API

**Datos Utilizados:**
- Reclamo: municipalidad_id="10003", municipalidad_tipo="ALUMBRADO PÚBLICO", municipalidad_motivo="Luminaria apagada", municipalidad_estado="Completado", prioridad="Baja"
- Caso 1: POST con tiempo_reparacion_minutos=0
- Caso 2: POST con tiempo_reparacion_minutos=-5 (negativo pequeño)
- Caso 3: POST con tiempo_reparacion_minutos=-100 (negativo grande)
- Endpoint: POST `/api/reclamos/{reclamoId}/tiempo-reparacion`

**Resultado Esperado:**
- Status HTTP: 400 (Bad Request) para todos los casos
- Respuesta JSON con mensaje de error indicando que el tiempo de reparación debe ser mayor a 0
- No se debe crear ningún registro en la tabla `tiempo_reparacion`
- El mensaje de error debe mencionar "mayor" o "obligatorio"

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:03.996, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 15, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 15 assertions
- Todos los casos probados (tiempo_reparacion_minutos = 0, negativo pequeño, negativo grande) retornan correctamente status 400
- Los mensajes de error mencionan que el tiempo debe ser mayor a 0 (o es obligatorio en el caso de 0)
- No se crea ningún registro en la tabla `tiempo_reparacion` cuando el tiempo_reparacion_minutos es <= 0
- La validación funciona correctamente para todos los casos de tiempo_reparacion_minutos inválido (0 o negativo)
- El caso tiempo_reparacion_minutos = 0 puede ser capturado por la validación `empty()` (porque 0 es considerado empty en PHP) antes de llegar a la validación `<= 0`, retornando "obligatorio" en lugar de "mayor a 0"

---

## Prueba 14

**Nombre de la Prueba:** Actualización de tiempo de reparación existente

**Ubicación:** `tests/api/TiempoReparacionApiTest.php::testActualizacionTiempoReparacionExistente`

**Objetivo:** Verificar que cuando se envía un POST con un reclamo que ya tiene tiempo registrado, el sistema actualiza el tiempo existente (no crea duplicado) y recalcula el promedio del motivo correctamente.

**Tipo de Prueba:** API - Integración

**Datos Utilizados:**
- Reclamo: municipalidad_id="10004", municipalidad_tipo="ALUMBRADO PÚBLICO", municipalidad_motivo="Luminaria prende y apaga", municipalidad_estado="Completado", prioridad="Baja"
- Tiempo inicial: tiempo_reparacion_minutos=30 (minutos)
- Tiempo actualizado: tiempo_reparacion_minutos=60 (minutos)
- Endpoint: POST `/api/reclamos/{reclamoId}/tiempo-reparacion` (dos veces: creación inicial y actualización)

**Resultado Esperado:**
- Paso 1: Registrar tiempo inicial → Debe crear UN registro en tiempo_reparacion y crear/actualizar el promedio en tiempo_promedio_motivo
- Paso 2: Actualizar tiempo → Debe actualizar el registro existente (mismo ID), NO crear un nuevo registro
- Debe haber solo UN registro en tiempo_reparacion para el reclamo (no duplicados)
- El tiempo debe actualizarse correctamente en la BD
- El promedio debe recalcularse correctamente: nuevo_tiempo_total = tiempo_total_anterior + diferencia
- La cantidad_registros debe mantenerse igual (mismo reclamo, no se suma como nuevo)
- El nuevo promedio debe ser: nuevo_tiempo_total / cantidad_registros

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:03.015, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 22, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 22 assertions
- El primer registro se creó correctamente con el tiempo inicial (30 minutos)
- Se creó el promedio inicial en tiempo_promedio_motivo con valor igual al tiempo inicial (30 minutos) y cantidad_registros=1
- Al actualizar el tiempo, NO se creó un nuevo registro (solo existe UN registro en tiempo_reparacion)
- El registro existente se actualizó correctamente (mismo ID, nuevo tiempo de 60 minutos)
- El promedio se recalculó correctamente: diferencia = 30 minutos, nuevo tiempo total = 60 minutos, nuevo promedio = 60 minutos
- La cantidad_registros se mantuvo igual (1 registro, no se suma como nuevo porque es el mismo reclamo)
- El tiempo_total_minutos se actualizó correctamente (30 + 30 = 60)
- El sistema actualiza correctamente el tiempo existente en lugar de crear duplicados
- El sistema recalcula correctamente el promedio del motivo cuando se actualiza un tiempo existente

---

## Prueba 15

**Nombre de la Prueba:** Recalcular promedio con múltiples registros

**Ubicación:** `tests/api/TiempoReparacionApiTest.php::testRecalcularPromedioConMultiplesRegistros`

**Objetivo:** Verificar que cuando se registran varios tiempos para el mismo motivo de reclamo, el promedio se recalcula correctamente, cantidad_registros se incrementa apropiadamente, y tiempo_total_minutos se actualiza sumando los nuevos tiempos.

**Tipo de Prueba:** API - Integración

**Datos Utilizados:**
- Reclamos con el mismo motivo: 4 reclamos con municipalidad_motivo="Luminaria parpadeando", municipalidad_estado="Completado"
  - Reclamo 1: municipalidad_id="10005", tiempo_reparacion_minutos=20
  - Reclamo 2: municipalidad_id="10006", tiempo_reparacion_minutos=35
  - Reclamo 3: municipalidad_id="10007", tiempo_reparacion_minutos=45
  - Reclamo 4: municipalidad_id="10008", tiempo_reparacion_minutos=30
- Endpoint: POST `/api/reclamos/{reclamoId}/tiempo-reparacion` (llamado 4 veces con diferentes reclamos)

**Resultado Esperado:**
- Después del primer registro (20 minutos): cantidad_registros=1, tiempo_total=20, promedio=20
- Después del segundo registro (35 minutos): cantidad_registros=2, tiempo_total=55 (20+35), promedio=27.5
- Después del tercer registro (45 minutos): cantidad_registros=3, tiempo_total=100 (20+35+45), promedio=33.33
- Después del cuarto registro (30 minutos): cantidad_registros=4, tiempo_total=130 (20+35+45+30), promedio=32.5
- Debe haber 4 registros en tiempo_reparacion (uno por cada reclamo)
- Todos los registros deben tener el mismo motivo
- El promedio final debe calcularse correctamente: tiempo_total / cantidad_registros

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:04.630, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 33, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 33 assertions
- Se registraron correctamente 4 tiempos diferentes para 4 reclamos con el mismo motivo
- Después de cada registro, cantidad_registros se incrementó correctamente (1, 2, 3, 4)
- Después de cada registro, tiempo_total_minutos se actualizó correctamente sumando el nuevo tiempo (20, 55, 100, 130)
- Después de cada registro, el promedio se recalculó correctamente: tiempo_total / cantidad_registros (20, 27.5, 33.33, 32.5)
- El promedio final es correcto: 32.5 minutos (130 / 4)
- Se crearon 4 registros en tiempo_reparacion (uno por cada reclamo)
- Todos los registros tienen el mismo motivo asociado
- Los tiempos registrados coinciden con los tiempos enviados
- El sistema calcula correctamente el promedio progresivo cuando se registran múltiples tiempos para el mismo motivo

---

## Prueba 16

**Nombre de la Prueba:** Reclamo inexistente

**Ubicación:** `tests/api/TiempoReparacionApiTest.php::testReclamoInexistente`

**Objetivo:** Verificar que cuando se intenta registrar un tiempo de reparación para un reclamo_id que no existe, el sistema retorna un error 404 (Not Found) con un mensaje apropiado indicando que el reclamo no fue encontrado.

**Tipo de Prueba:** API - Validación

**Datos Utilizados:**
- Reclamo inexistente: reclamo_id que no existe en la base de datos (último ID + 99999)
- Tiempo de reparación: tiempo_reparacion_minutos=45
- Endpoint: POST `/api/reclamos/{reclamoIdInexistente}/tiempo-reparacion`
- Payload: `{"tiempo_reparacion_minutos": 45}`

**Resultado Esperado:**
- Status HTTP: 404 (Not Found)
- Respuesta JSON con mensaje de error indicando que el reclamo no fue encontrado
- No se debe crear ningún registro en la tabla `tiempo_reparacion`
- No se debe crear ningún promedio nuevo en `tiempo_promedio_motivo`
- El mensaje de error debe mencionar "no encontrado" o similar

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                  1 / 1 (100%)

Time: 00:07.817, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 7, PHPUnit Warnings: 1.
```

**Observaciones:**
- El test pasó exitosamente con 7 assertions
- El endpoint retorna correctamente status 404 cuando el reclamo no existe
- El mensaje de error menciona que el reclamo no fue encontrado
- No se crea ningún registro en la tabla `tiempo_reparacion` para un reclamo inexistente
- No se afecta ningún promedio en `tiempo_promedio_motivo` (no se crea ningún promedio nuevo)
- El sistema maneja correctamente el caso de reclamo inexistente y previene la creación de registros inválidos
- La validación funciona correctamente para proteger la integridad de los datos

---

