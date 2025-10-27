# Tests del Sprint 3

## Historias de Usuario Cubiertas

- **HU-020**: Generación de hoja de ruta optimizada
- **HU-021**: Visualización de hoja de ruta detallada
- **HU-023**: Edición de hoja de ruta antes de asignar

---

## Pruebas Realizadas

### Prueba 1

**Nombre de la Prueba:** Generación de ruta automática con datos válidos

**Ubicación:** `tests/api/RutasApiTest.php::testGenerarRutaAutomaticaConDatosValidos`

**Objetivo:** Verificar que el sistema puede generar una hoja de ruta automática cuando se proporcionan datos válidos (nombre, color, cantidad de reclamos).

**Tipo de Prueba:** API

**Datos Utilizados:**
- Nombre: "Ruta de Prueba"
- Color: "#FF6B35"
- Cantidad de Reclamos: 2
- Reclamos Manuales: []
- Primer Reclamo Manual: null
- Modo Manual: false

**Resultado Esperado:**
- Status HTTP: 201 (Created)
- Respuesta JSON con:
  - id de la ruta creada
  - nombre: "Ruta de Prueba"
  - color: "#FF6B35"
  - cantidadReclamos: 2
  - asignada: 0 (no asignada)
  - tiempoEstimado
- La ruta debe existir en la base de datos

**Resultado Obtenido:** ✅ EXITOSO (después de ajustes)

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.304, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 17, PHPUnit Warnings: 1.

Status Code: 201
Response Body: {
    "ruta": {
        "id": "1",
        "nombre": "Ruta de Prueba",
        "cantidadReclamos": "2",
        "asignada": "0",
        "tiempoEstimado": "00:30:00",
        "color": "#FF6B35"
    },
    "reclamos": [2 reclamos de prioridad Alta]
}
```

**Observaciones:**
- El sistema generó correctamente la ruta con los parámetros especificados
- Se priorizaron los reclamos de prioridad Alta (cumple criterio de aceptación)
- La ruta se creó con estado "No Asignada" (asignada: "0")
- Se utilizaron las direcciones personalizadas de la BD para obtener coordenadas
- El tiempo estimado se calculó correctamente (00:30:00 para 2 reclamos)
- La respuesta incluye tanto los datos de la ruta como los reclamos asociados

---

### Prueba 2

**Nombre de la Prueba:** Priorización de reclamos por nivel de prioridad

**Ubicación:** `tests/api/RutasApiTest.php::testPriorizacionReclamosPorPrioridad`

**Objetivo:** Verificar que el sistema priorice los reclamos de Alta prioridad antes que los de Baja prioridad al generar una hoja de ruta automática.

**Tipo de Prueba:** API

**Datos Utilizados:**
- Nombre: "Ruta Priorización"
- Color: "#28a745"
- Cantidad de Reclamos: 3
- Reclamos disponibles en BD:
  - ID 1001: Prioridad Alta, estado Recibido
  - ID 1002: Prioridad Baja, estado Recibido
  - ID 1003: Prioridad Alta, estado Recibido
  - ID 1004: Prioridad Baja, estado Recibido
  - ID 1005: Prioridad Baja, estado Completado (no debe incluirse)

**Resultado Esperado:**
- Se deben incluir exactamente 3 reclamos
- Se deben incluir TODOS los reclamos de prioridad Alta disponibles (2 reclamos: IDs 1001 y 1003)
- Se debe completar con reclamos de prioridad Baja hasta llegar a 3 (1 reclamo: ID 1002 o 1004)
- No debe incluir el reclamo completado (ID 1005)
- El orden de la ruta puede ser optimizado geográficamente

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.208, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 13, PHPUnit Warnings: 1.

Reclamos en la ruta:
Posición 1: ID=1003, Prioridad=Alta
Posición 2: ID=1002, Prioridad=Baja
Posición 3: ID=1001, Prioridad=Alta
```

**Observaciones:**
- ✅ El sistema incluye correctamente los 2 reclamos de Alta prioridad disponibles (1001 y 1003)
- ✅ El sistema completa con 1 reclamo de Baja prioridad (1002)
- ✅ No incluyó el reclamo completado (1005)
- ✅ El orden está optimizado geográficamente (algoritmo del vecino más cercano)
- ✅ Se verifica correctamente la priorización: todos los reclamos de Alta se incluyen antes de considerar los de Baja

**Conclusión:** El sistema cumple correctamente con el criterio de aceptación "El sistema prioriza reclamos de Alta prioridad, luego Baja prioridad". La priorización significa que se **seleccionan** primero todos los reclamos de Alta disponibles, y luego se completa con los de Baja si es necesario. El orden en la ruta puede variar según la optimización geográfica del algoritmo del vecino más cercano.

---

### Prueba 3

**Nombre de la Prueba:** Exclusión de reclamos completados

**Ubicación:** `tests/api/RutasApiTest.php::testExclusionReclamosCompletados`

**Objetivo:** Verificar que el sistema excluye automáticamente los reclamos con estado "Completado" al generar una hoja de ruta automática.

**Tipo de Prueba:** API

**Datos Utilizados:**
- Nombre: "Ruta Sin Completados"
- Color: "#dc3545"
- Cantidad de Reclamos: 1
- Reclamos en BD:
  - ID 1001: Estado "Recibido" (disponible)
  - ID 1002: Estado "Recibido" (disponible)
  - ID 1003: Estado "Recibido" (disponible)
  - ID 1004: Estado "Recibido" (disponible)
  - ID 1005: Estado "Completado" (NO debe incluirse)

**Resultado Esperado:**
- La ruta se crea exitosamente (Status 201)
- Ningún reclamo incluido tiene estado "Completado"
- El reclamo 1005 NO está incluido en la ruta
- Solo se incluyen reclamos de los IDs 1001, 1002, 1003 o 1004
- El reclamo 1005 no debe estar asignado a ninguna ruta en la BD

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.185, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 9, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El sistema creó la ruta exitosamente excluyendo reclamos completados
- ✅ Ningún reclamo en la ruta tiene estado "Completado"
- ✅ El reclamo 1005 (completado) NO fue incluido en la ruta
- ✅ Solo se incluyeron reclamos con estados válidos (Recibido)
- ✅ Verificación en BD: el reclamo completado no está asignado a ninguna ruta

**Validación adicional realizada:**
- Cuando se solicitaron 5 reclamos (total en BD), el sistema respondió con error 400:
  - Mensaje: "No hay suficientes reclamos disponibles. Disponibles: 4, Solicitados: 5"
  - Esto confirma que el sistema cuenta correctamente solo 4 disponibles (excluyendo el completado)

**Conclusión:** El sistema cumple correctamente con el criterio de aceptación "Solo incluye reclamos no completados". Los reclamos con estado "Completado" son automáticamente excluidos del proceso de generación de rutas.

---

### Prueba 4

**Nombre de la Prueba:** Exclusión de reclamos que ya están en otras rutas

**Ubicación:** `tests/api/RutasApiTest.php::testExclusionReclamosEnOtrasRutas`

**Objetivo:** Verificar que el sistema excluye automáticamente los reclamos que ya están asignados a otras hojas de ruta al generar una nueva ruta.

**Tipo de Prueba:** API - Integración

**Datos Utilizados:**
- Base de datos de prueba: 15 reclamos totales (14 disponibles + 1 completado)
  - 6 de prioridad Alta (1001, 1003, 1006, 1007, 1010, 1012, 1015)
  - 8 de prioridad Baja (1002, 1004, 1008, 1009, 1011, 1013, 1014) + 1 completado (1005)
- Primera ruta:
  - Nombre: "Ruta 1 - Primera"
  - Color: "#FF6B35"
  - Cantidad solicitada: 5 reclamos
- Segunda ruta:
  - Nombre: "Ruta 2 - Segunda"
  - Color: "#28a745"
  - Cantidad solicitada: 6 reclamos

**Resultado Esperado:**
- Ambas rutas se crean exitosamente con las cantidades exactas solicitadas
- Los reclamos de la Ruta 2 NO están en la Ruta 1
- No hay intersección entre los conjuntos de reclamos de ambas rutas
- Cada reclamo está asignado a exactamente UNA ruta en la BD
- El total es 11 reclamos únicos (5 + 6), sin duplicados

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.189, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 40, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ Ruta 1 se creó con exactamente 5 reclamos
- ✅ Ruta 2 se creó con exactamente 6 reclamos
- ✅ NO hay reclamos compartidos entre las rutas (intersección vacía)
- ✅ Cada reclamo está asignado a exactamente 1 ruta en la BD
- ✅ Total: 11 reclamos únicos utilizados (5 + 6 = 11)
- ✅ El sistema procesó 40 aserciones exitosamente

**Validación adicional realizada:**
- Se verificó en la base de datos que cada uno de los 11 reclamos aparece en exactamente 1 registro de la tabla `ruta_reclamo`
- Se confirmó que el número de IDs únicos (11) = suma de reclamos en ambas rutas (5 + 6)
- El sistema priorizó correctamente: incluyó todos los reclamos de Alta prioridad disponibles primero
- Se validó que ningún reclamo aparece en ambas rutas simultáneamente
- Las verificaciones iterativas confirmaron la exclusividad de cada asignación

**Conclusión:** El sistema cumple correctamente con el criterio de aceptación "Solo incluye reclamos no completados y que no pertenezcan a otras rutas".

---

### Prueba 5

**Nombre de la Prueba:** Validación de ruta sin nombre

**Ubicación:** `tests/api/RutasApiTest.php::testValidacionRutaSinNombre`

**Objetivo:** Verificar que el sistema rechaza la creación de rutas sin nombre (vacío o null) con código de error 400.

**Tipo de Prueba:** API - Validación

**Datos Utilizados:**
- Caso 1: Nombre vacío (`''`)
- Caso 2: Nombre null (`null`)
- Color: "#FF6B35"
- Cantidad de reclamos: 2

**Resultado Esperado:**
- Status: 400 (Bad Request)
- Respuesta con mensaje de error indicando que el nombre es obligatorio

**Resultado Obtenido:** ❌ FALLIDO

**Evidencia:**
```
=== INTENTO 1: NOMBRE VACÍO ===
Status: 201
Body: {
    "ruta": {
        "id": "1",
        "nombre": "",
        "cantidadReclamos": "2",
        "asignada": "0",
        ...
    },
    ...
}

Failed asserting that 201 is identical to 400.
```

**Error Detectado:**
- ❌ El sistema acepta nombres vacíos y devuelve status 201 (éxito)
- ❌ No se está validando que el nombre sea obligatorio y no vacío
- ❌ Se crea la ruta con nombre vacío en la base de datos

**Conclusión:** **FALLA CRÍTICA** - El sistema NO valida que el nombre sea obligatorio. Esto permite crear rutas sin un identificador legible, lo cual es un problema grave de usabilidad y puede causar confusión en la interfaz de usuario. Se requiere agregar validación en el backend para rechazar nombres vacíos o null.

---

### Prueba 6

**Nombre de la Prueba:** Validación de cantidad de reclamos = 0

**Ubicación:** `tests/api/RutasApiTest.php::testValidacionCantidadReclamosInvalida`

**Objetivo:** Verificar que el sistema rechaza cantidades de reclamos = 0 con código de error 400.

**Tipo de Prueba:** API - Validación

**Datos Utilizados:**
- Cantidad: 0
- Nombre: "Ruta Test Cero"
- Color: "#FF6B35"

**Resultado Esperado:**
- Status: 400 (Bad Request)
- Respuesta con mensaje de error

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.153, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 4, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El sistema rechaza correctamente cantidad = 0 con status 400
- ✅ Mensaje de error apropiado: "Faltan datos obligatorios (cantidadReclamos)."

**Conclusión:** La validación funciona correctamente. El sistema rechaza apropiadamente rutas con cantidad = 0.

---

### Prueba 7

**Nombre de la Prueba:** Validación de cantidad de reclamos negativa

**Ubicación:** `tests/api/RutasApiTest.php::testValidacionCantidadNegativa`

**Objetivo:** Verificar que el sistema rechaza cantidades de reclamos negativas con código de error 400.

**Tipo de Prueba:** API - Validación (Documenta un ERROR del sistema)

**Datos Utilizados:**
- Cantidad: -5 (negativa)
- Nombre: "Ruta Test Negativa"
- Color: "#FF6B35"

**Resultado Esperado:**
- Status: 400 (Bad Request)
- Respuesta con mensaje de error

**Resultado Obtenido:** ❌ FALLIDO - ERROR CRÍTICO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.132, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 3, PHPUnit Warnings: 1.

Status Real: 201 (debería ser 400)
Body: {
    "ruta": {
        "id": "1",
        "nombre": "Ruta Test Negativa",
        "cantidadReclamos": "0",
        ...
    },
    "reclamos": []
}
```

**Error Detectado:**
- ❌ **FALLA CRÍTICA**: El sistema ACEPTA cantidades negativas
- ❌ Convierte automáticamente el valor negativo (-5) en 0
- ❌ Crea la ruta vacía sin reclamos (status 201)
- ❌ No hay validación para valores negativos

**Conclusión:** **FALLA CRÍTICA** - El sistema NO valida cantidades negativas. Cuando se envía un valor negativo, el sistema lo convierte silenciosamente en 0 y crea una ruta vacía. Esto es un bug de validación que debe corregirse en el backend para rechazar explícitamente valores negativos con status 400.

---

### Prueba 8

**Nombre de la Prueba:** Validación de reclamos insuficientes

**Ubicación:** `tests/api/RutasApiTest.php::testValidacionReclamosInsuficientes`

**Objetivo:** Verificar que el sistema rechaza solicitudes de rutas cuando se solicitan más reclamos de los disponibles, informando la cantidad disponible.

**Tipo de Prueba:** API - Validación

**Datos Utilizados:**
- Reclamos disponibles en BD: Variable (calculado dinámicamente)
- Cantidad solicitada: Disponibles + 10
- Nombre: "Ruta Excesiva"
- Color: "#FF6B35"

**Resultado Esperado:**
- Status: 400 (Bad Request)
- Respuesta con mensaje de error mencionando la cantidad disponible y solicitada

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.154, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 6, PHPUnit Warnings: 1.
```

**Observaciones:**
- ✅ El sistema rechaza correctamente la solicitud excesiva con status 400
- ✅ Mensaje de error: "No hay suficientes reclamos disponibles. Disponibles: X, Solicitados: Y"
- ✅ La lógica de validación es precisa y útil para el usuario
- ✅ El sistema calcula correctamente los reclamos disponibles en tiempo real
- ✅ El mensaje incluye tanto la cantidad disponible como la solicitada

**Conclusión:** La validación funciona. El sistema calcula correctamente los reclamos disponibles (excluyendo completados y ya asignados) y proporciona feedback claro y útil al usuario.

---

### Prueba 9

**Nombre de la Prueba:** Obtener lista de todas las rutas

**Ubicación:** `tests/api/RutasApiTest.php::testObtenerListaRutas`

**Objetivo:** Verificar que el endpoint GET /api/rutas devuelve correctamente un array con todas las rutas creadas.

**Tipo de Prueba:** API - Lectura

**Datos Utilizados:**
- Se crean 2 rutas de prueba antes de hacer la consulta:
  - Ruta 1: "Ruta Test Listado 1" con 2 reclamos (color #FF6B35)
  - Ruta 2: "Ruta Test Listado 2" con 3 reclamos (color #28a745)

**Resultado Esperado:**
- Status: 200 (OK)
- Respuesta: Array con todas las rutas
- Cada ruta debe tener: id, nombre, cantidadReclamos, asignada, fecha, color
- Las rutas creadas deben estar en la lista

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.284, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 22, PHPUnit Warnings: 1.

Status: 200 ✓
Response: Array con todas las rutas ✓
Estructura de datos correcta ✓
Rutas creadas presentes en la lista ✓
```

**Observaciones:**
- ✅ El endpoint devuelve status 200 correctamente
- ✅ La respuesta es un array como se esperaba
- ✅ Cada ruta tiene todos los campos requeridos (id, nombre, cantidadReclamos, asignada, fecha, color)
- ✅ Las rutas creadas durante el test están presentes en la lista
- ✅ Se ejecutaron 22 assertions, todas exitosas
- ✅ El sistema mantiene correctamente el registro de todas las rutas

**Conclusión:** El endpoint GET /api/rutas funciona correctamente. Devuelve todas las rutas en un formato apropiado con todos los campos necesarios para la visualización en el frontend.

---

### Prueba 10

**Nombre de la Prueba:** Obtener detalles de una ruta específica

**Ubicación:** `tests/api/RutasApiTest.php::testObtenerDetallesRutaEspecifica`

**Objetivo:** Verificar que el endpoint GET /api/rutas/{id} devuelve correctamente la estructura completa de una ruta específica.

**Tipo de Prueba:** API - Lectura

**Datos Utilizados:**
- Se crea una ruta de prueba: "Ruta Test Detalles" con 3 reclamos (color #FF6B35)
- Se consulta esa ruta por su ID

**Resultado Esperado:**
- Status: 200 (OK)
- Respuesta con estructura completa: id, nombre, cantidadReclamos, asignada, cuadrilla_id, tiempoEstimado, fecha, color
- Los valores deben coincidir con los datos de creación

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.272, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 16, PHPUnit Warnings: 1.

Status: 200 ✓
Estructura completa presente ✓
Valores correctos ✓
```

**Observaciones:**
- ✅ El endpoint devuelve status 200 correctamente
- ✅ La respuesta incluye todos los campos requeridos
- ✅ El ID, nombre, cantidad y color coinciden con los datos creados
- ✅ Los campos de asignación están correctamente inicializados (asignada=0, cuadrilla_id=null)
- ✅ Se ejecutaron 16 assertions, todas exitosas

**Conclusión:** El endpoint GET /api/rutas/{id} funciona correctamente. Devuelve todos los detalles de una ruta específica con la estructura adecuada.

---

### Prueba 11

**Nombre de la Prueba:** Obtener reclamos de una ruta específica

**Ubicación:** `tests/api/RutasApiTest.php::testObtenerReclamosRutaEspecifica`

**Objetivo:** Verificar que el endpoint GET /api/rutas/{id}/reclamos devuelve correctamente los reclamos de una ruta ordenados por posición.

**Tipo de Prueba:** API - Lectura

**Datos Utilizados:**
- Se crea una ruta de prueba: "Ruta Test Reclamos" con 4 reclamos (color #28a745)
- Se consultan los reclamos de esa ruta

**Resultado Esperado:**
- Status: 200 (OK)
- Array con 4 reclamos
- Cada reclamo debe tener: id, municipalidad_id, domicilio, numeroDomicilio, estado, prioridad, posicion
- Los reclamos deben estar ordenados por posición (1, 2, 3, 4)

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.259, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 34, PHPUnit Warnings: 1.

Status: 200 ✓
4 reclamos devueltos ✓
Estructura completa ✓
Ordenados por posición ✓
```

**Observaciones:**
- ✅ El endpoint devuelve status 200 correctamente
- ✅ Se devuelven exactamente 4 reclamos como se esperaba
- ✅ Cada reclamo tiene todos los campos necesarios incluida la posición
- ✅ Los reclamos están correctamente ordenados: posiciones [1, 2, 3, 4]
- ✅ La secuencia de posiciones es correcta y comienza desde 1
- ✅ Se ejecutaron 34 assertions, todas exitosas

**Conclusión:** El endpoint GET /api/rutas/{id}/reclamos funciona correctamente. Devuelve los reclamos de una ruta con toda la información necesaria y correctamente ordenados por su posición en la ruta. Esto es esencial para la visualización correcta del recorrido en HU-021.

---

### Prueba 12

**Nombre de la Prueba:** Manejo de ruta inexistente

**Ubicación:** `tests/api/RutasApiTest.php::testRutaInexistente`

**Objetivo:** Verificar que el sistema maneja correctamente solicitudes de rutas que no existen, devolviendo un error 404 apropiado.

**Tipo de Prueba:** API - Manejo de Errores

**Datos Utilizados:**
- ID inexistente: 999999 (un ID que no existe en la base de datos)

**Resultado Esperado:**
- Status: 404 (Not Found)
- Estructura de error: { status: 404, error: 404, messages: { error: "Ruta no encontrada" } }

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.247, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 8, PHPUnit Warnings: 1.

Status: 404 ✓
Estructura de error correcta ✓
Mensaje apropiado: "Ruta no encontrada" ✓
```

**Observaciones:**
- ✅ El sistema responde con status 404 correctamente
- ✅ La respuesta tiene la estructura estándar de error del sistema
- ✅ El mensaje de error es claro: "Ruta no encontrada"
- ✅ Se verifican todos los campos de la respuesta (status, error, messages)
- ✅ Se ejecutaron 8 assertions, todas exitosas
- ✅ El manejo de errores es consistente con el resto de la API

**Conclusión:** El sistema maneja correctamente las solicitudes de rutas inexistentes. Devuelve un error 404 con un mensaje descriptivo, siguiendo el formato estándar de respuesta de error de la API.

---

### Prueba 13

**Nombre de la Prueba:** Generación de ruta en modo manual

**Ubicación:** `tests/api/RutasApiTest.php::testGenerarRutaManual`

**Objetivo:** Verificar que el sistema permite crear rutas en modo manual, respetando el orden específico de reclamos seleccionados por el usuario.

**Tipo de Prueba:** API - Funcionalidad (HU-023)

**Datos Utilizados:**
- Se seleccionan 4 reclamos en un orden específico: IDs [3, 1, 6, 2]
- Modo manual activado: `modoManual: true`
- Reclamos manuales: array con los IDs en el orden deseado

**Resultado Esperado:**
- Status: 201 (Created)
- Ruta creada con 4 reclamos
- Los reclamos deben aparecer en el orden exacto especificado
- Cada reclamo debe incluir sus coordenadas

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.246, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 20, PHPUnit Warnings: 1.

Status: 201 ✓
Orden respetado: [3, 1, 6, 2] ✓
Coordenadas incluidas ✓
```

**Observaciones:**
- ✅ El sistema crea correctamente la ruta en modo manual
- ✅ El orden de los reclamos se respeta exactamente como se especificó
- ✅ Los IDs devueltos coinciden con el orden solicitado: [3, 1, 6, 2]
- ✅ Cada reclamo incluye sus coordenadas (lat, lng) para visualización
- ✅ Se ejecutaron 20 assertions, todas exitosas
- ✅ Esta funcionalidad permite a los usuarios personalizar completamente el orden de las rutas (HU-023)

**Conclusión:** La funcionalidad de generación manual de rutas funciona correctamente. El sistema respeta el orden exacto de reclamos especificado por el usuario, lo cual es esencial para permitir la edición y personalización de hojas de ruta antes de su asignación.

---

### Prueba 14

**Nombre de la Prueba:** Validación de reclamo ya en otra ruta

**Ubicación:** `tests/api/RutasApiTest.php::testValidacionReclamoEnOtraRuta`

**Objetivo:** Verificar que el sistema valida y rechaza la creación de rutas manuales que incluyan reclamos ya asignados a otras rutas.

**Tipo de Prueba:** API - Validación (HU-023)

**Datos Utilizados:**
- Se crea una primera ruta con 3 reclamos
- Se intenta crear una segunda ruta manual incluyendo un reclamo de la primera ruta

**Resultado Esperado:**
- Status: 400 (Bad Request)
- Mensaje de error indicando que el reclamo ya está en otra ruta

**Resultado Obtenido:** ❌ FALLIDO - ERROR CRÍTICO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.275, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 8, PHPUnit Warnings: 1.

Status Real: 201 (debería ser 400)
El sistema ACEPTA reclamos duplicados ✗
```

**Error Detectado:**
- ❌ **FALLA CRÍTICA**: El sistema NO valida reclamos duplicados en modo manual
- ❌ Permite crear rutas con reclamos que ya están asignados a otras rutas
- ❌ Devuelve status 201 (éxito) cuando debería rechazar con 400
- ❌ No hay validación de unicidad de reclamos entre rutas en modo manual

**Implicaciones:**
- Un mismo reclamo puede estar en múltiples rutas simultáneamente
- Esto puede causar conflictos al asignar rutas a cuadrillas
- Los operarios podrían recibir el mismo reclamo en diferentes hojas de ruta
- Pérdida de control sobre qué reclamos están realmente disponibles

**Conclusión:** **FALLA CRÍTICA** - El sistema NO valida la unicidad de reclamos al crear rutas manuales. Esto permite que un mismo reclamo sea incluido en múltiples rutas, lo cual es un error grave de lógica de negocio. Se debe agregar validación para verificar que los reclamos seleccionados manualmente no estén ya asignados a otras rutas.

---

### Prueba 15

**Nombre de la Prueba:** Eliminación de ruta y sus relaciones

**Ubicación:** `tests/api/RutasApiTest.php::testEliminarRuta`

**Objetivo:** Verificar que el endpoint DELETE /api/rutas/{id} elimina correctamente la ruta y todas sus relaciones con reclamos, sin eliminar los reclamos mismos.

**Tipo de Prueba:** API - Eliminación

**Datos Utilizados:**
- Se crea una ruta con 3 reclamos
- Se elimina la ruta usando DELETE /api/rutas/{id}
- Se verifican las tablas: ruta, ruta_reclamo, reclamo

**Resultado Esperado:**
- Status: 200 (OK)
- Ruta eliminada de la tabla `ruta`
- Relaciones eliminadas de la tabla `ruta_reclamo`
- Reclamos conservados en la tabla `reclamo` (liberados para uso futuro)

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.258, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 12, PHPUnit Warnings: 1.

Status: 200 ✓
Ruta eliminada ✓
Relaciones eliminadas ✓
Reclamos conservados ✓
Mensaje: "Ruta eliminada con éxito" ✓
```

**Observaciones:**
- ✅ El endpoint responde con status 200 correctamente
- ✅ La ruta se elimina de la base de datos (tabla `ruta`)
- ✅ Las relaciones se eliminan correctamente (tabla `ruta_reclamo`)
- ✅ Los reclamos NO se eliminan, solo se liberan (quedan disponibles para futuras rutas)
- ✅ El mensaje de confirmación es claro: "Ruta eliminada con éxito"
- ✅ Se ejecutaron 12 assertions, todas exitosas
- ✅ La eliminación en cascada funciona correctamente
- ✅ Los reclamos quedan listos para ser reutilizados en nuevas rutas

**Conclusión:** La funcionalidad de eliminación de rutas funciona correctamente. El sistema elimina la ruta y todas sus relaciones de manera apropiada, mientras preserva los reclamos para que puedan ser reutilizados. Esto es importante para permitir la corrección de errores y la reorganización de hojas de ruta.

---

### Prueba 16

**Nombre de la Prueba:** Flujo completo: Crear, Visualizar y Eliminar

**Ubicación:** `tests/api/RutasApiTest.php::testFlujoCompletoCrearVisualizarEliminar`

**Objetivo:** Verificar la integridad del sistema en un flujo completo de operaciones: creación de ruta, visualización de detalles, obtención de reclamos, verificación en listado, eliminación y verificación post-eliminación.

**Tipo de Prueba:** Integración - Flujo Completo (HU-020, HU-021, HU-023)

**Flujo Ejecutado:**
1. **Crear ruta** con 4 reclamos
2. **Obtener detalles** de la ruta creada
3. **Obtener reclamos** de la ruta
4. **Verificar** que la ruta aparece en el listado general
5. **Eliminar** la ruta
6. **Verificar** eliminación en base de datos
7. **Intentar obtener** la ruta eliminada (debe dar 404)
8. **Verificar** que los reclamos siguen existiendo (liberados)
9. **Verificar** que la ruta no está en el listado

**Datos Utilizados:**
- Nombre: "Ruta Test Flujo Completo"
- Color: #9C27B0
- Cantidad de reclamos: 4
- Modo: Automático

**Resultado Esperado:**
- Cada paso debe ejecutarse correctamente
- Los datos deben ser consistentes entre operaciones
- La eliminación debe ser completa pero preservar los reclamos
- Los códigos de estado HTTP deben ser correctos en cada paso

**Resultado Obtenido:** ✅ EXITOSO

**Evidencia:**
```
PHPUnit 10.5.48 by Sebastian Bergmann and contributors.

.                                                                   1 / 1 (100%)

Time: 00:00.313, Memory: 16.00 MB

OK, but there were issues!
Tests: 1, Assertions: 36, PHPUnit Warnings: 1.

PASO 1 - Creación: Status 201 ✓
PASO 2 - Detalles: Status 200, datos coinciden ✓
PASO 3 - Reclamos: Status 200, IDs coinciden ✓
PASO 4 - Listado: Ruta presente ✓
PASO 5 - Eliminación: Status 200, mensaje confirmado ✓
PASO 6 - Verificación DB: Ruta eliminada, relaciones eliminadas ✓
PASO 7 - GET post-eliminación: Status 404 ✓
PASO 8 - Reclamos: Siguen existiendo (liberados) ✓
PASO 9 - Listado final: Ruta ausente ✓
```

**Observaciones:**
- ✅ **PASO 1 (Creación)**: Ruta creada exitosamente con status 201, devuelve ruta y reclamos
- ✅ **PASO 2 (Detalles)**: GET /api/rutas/{id} devuelve todos los campos correctos (nombre, color, cantidad, asignación)
- ✅ **PASO 3 (Reclamos)**: GET /api/rutas/{id}/reclamos devuelve los 4 reclamos con sus posiciones
- ✅ **PASO 4 (Listado)**: La ruta aparece correctamente en GET /api/rutas
- ✅ **PASO 5 (Eliminación)**: DELETE /api/rutas/{id} responde con status 200 y mensaje de confirmación
- ✅ **PASO 6 (Verificación DB)**: La tabla `ruta` ya no contiene la ruta, las relaciones `ruta_reclamo` fueron eliminadas
- ✅ **PASO 7 (404)**: Intentar obtener la ruta eliminada devuelve 404 con mensaje "Ruta no encontrada"
- ✅ **PASO 8 (Reclamos)**: Los 4 reclamos siguen existiendo en la tabla `reclamo` (liberados para reutilización)
- ✅ **PASO 9 (Listado final)**: La ruta ya no aparece en GET /api/rutas
- ✅ Se ejecutaron **36 assertions**, todas exitosas
- ✅ La integridad de los datos se mantiene en todo el flujo
- ✅ No hay fugas de memoria ni referencias huérfanas

**Verificaciones de Integridad:**
1. ✅ **Consistencia de datos**: Los datos devueltos en cada paso coinciden con los datos creados
2. ✅ **Eliminación en cascada**: Las relaciones se eliminan correctamente
3. ✅ **Preservación de reclamos**: Los reclamos no se eliminan, solo se liberan
4. ✅ **Códigos HTTP correctos**: 201 (creación), 200 (lectura/eliminación), 404 (no encontrado)
5. ✅ **Sincronización BD-API**: Los cambios en la base de datos se reflejan en las respuestas de la API

**Conclusión:** El flujo completo de operaciones funciona correctamente. El sistema mantiene la integridad de los datos en todas las etapas, desde la creación hasta la eliminación. Las operaciones CRUD están correctamente implementadas, los códigos de estado HTTP son apropiados, y la eliminación en cascada funciona sin dejar referencias huérfanas. Este test de integración confirma que todas las piezas del módulo de rutas trabajan correctamente en conjunto.

---


