# Tests del Sistema de Alumbrado San Francisco

## Pruebas de Cuadrillas

**Nombre de la Prueba:** Crear cuadrilla con datos válidos
**Ubicación:** tests/api/CuadrillasApiTest.php
**Objetivo:** Verificar que se puede crear una cuadrilla con nombre y descripción válidos
**Tipo de Prueba:** API
**Datos Utilizados:** {"nombre": "Cuadrilla Norte", "descripcion": "Cuadrilla asignada a la zona norte"}
**Resultado Esperado:** Status 201, cuadrilla creada con ID generado
**Resultado Obtenido:** Status 201, cuadrilla creada exitosamente con ID generado
**Evidencia:** Test ejecutado exitosamente - 5 assertions pasaron

---

**Nombre de la Prueba:** Crear cuadrilla sin nombre obligatorio
**Ubicación:** tests/api/CuadrillasApiTest.php
**Objetivo:** Verificar que se rechaza la creación de cuadrilla sin nombre
**Tipo de Prueba:** API
**Datos Utilizados:** {"descripcion": "Cuadrilla sin nombre"}
**Resultado Esperado:** Status 400, mensaje de error por nombre obligatorio
**Resultado Obtenido:** Status 400, mensaje de error correcto retornado
**Evidencia:** Test ejecutado exitosamente - 2 assertions pasaron

---

**Nombre de la Prueba:** Obtener lista de cuadrillas con operarios
**Ubicación:** tests/api/CuadrillasApiTest.php
**Objetivo:** Verificar que se obtienen todas las cuadrillas con sus operarios asignados
**Tipo de Prueba:** API
**Datos Utilizados:** GET /api/cuadrillas
**Resultado Esperado:** Status 200, array de cuadrillas con campo operarios
**Resultado Obtenido:** Status 200, cuadrillas retornadas con operarios correctamente
**Evidencia:** Test ejecutado exitosamente - 4 assertions pasaron

---

**Nombre de la Prueba:** Asignar operarios a cuadrilla
**Ubicación:** tests/api/CuadrillasApiTest.php
**Objetivo:** Verificar que se pueden asignar operarios a una cuadrilla existente
**Tipo de Prueba:** API
**Datos Utilizados:** {"cuadrillaId": 1, "operarios": [1, 2, 3]}
**Resultado Esperado:** Status 200, mensaje de éxito en asignación
**Resultado Obtenido:** Status 200, operarios asignados correctamente
**Evidencia:** Test ejecutado exitosamente - 4 assertions pasaron

---

**Nombre de la Prueba:** Asignar más de 4 operarios a cuadrilla
**Ubicación:** tests/api/CuadrillasApiTest.php
**Objetivo:** Verificar que se rechaza la asignación de más de 4 operarios por cuadrilla
**Tipo de Prueba:** API
**Datos Utilizados:** {"cuadrillaId": 1, "operarios": [1, 2, 3, 4, 5]}
**Resultado Esperado:** Status 400, mensaje de error por límite excedido
**Resultado Obtenido:** Status 400, límite de operarios respetado correctamente
**Evidencia:** Test ejecutado exitosamente - 3 assertions pasaron

---

## Pruebas de Integridad de Datos

**Nombre de la Prueba:** Eliminar cuadrilla elimina asignaciones de operarios
**Ubicación:** tests/api/CuadrillasApiTest.php
**Objetivo:** Verificar que al eliminar una cuadrilla se eliminan automáticamente las asignaciones de operarios
**Tipo de Prueba:** Integridad de Datos
**Datos Utilizados:** Cuadrilla con 2 operarios asignados
**Resultado Esperado:** Status 200, cuadrilla eliminada y asignaciones eliminadas automáticamente
**Resultado Obtenido:** Status 200, cuadrilla eliminada correctamente, asignaciones eliminadas
**Evidencia:** Test ejecutado exitosamente - 4 assertions pasaron

---

**Nombre de la Prueba:** Eliminar operario mantiene integridad de cuadrilla
**Ubicación:** tests/api/CuadrillasApiTest.php
**Objetivo:** Verificar que al eliminar un operario se mantiene la integridad de la cuadrilla
**Tipo de Prueba:** Integridad de Datos
**Datos Utilizados:** Cuadrilla con 2 operarios, eliminar operario ID 1
**Resultado Esperado:** Cuadrilla sigue existiendo, asignaciones se manejan según FK
**Resultado Obtenido:** ERROR - Foreign key constraint fails al eliminar operario
**Evidencia:** Test falló - Error de restricción de clave foránea: "Cannot delete or update a parent row: a foreign key constraint fails"

---

**Nombre de la Prueba:** Transacción en asignación de operarios
**Ubicación:** tests/api/CuadrillasApiTest.php
**Objetivo:** Verificar que las transacciones funcionan correctamente al asignar operarios inexistentes
**Tipo de Prueba:** Integridad de Datos
**Datos Utilizados:** {"cuadrillaId": 1, "operarios": [1, 2, 999]} (999 inexistente)
**Resultado Esperado:** Transacción falla, no se asignan operarios
**Resultado Obtenido:** Status 200, operarios asignados (sistema no valida existencia)
**Evidencia:** Test ejecutado exitosamente - 1 assertion pasó (pero comportamiento inesperado)

---

**Nombre de la Prueba:** Integridad al actualizar cuadrilla con operarios
**Ubicación:** tests/api/CuadrillasApiTest.php
**Objetivo:** Verificar que al actualizar una cuadrilla se mantienen las asignaciones de operarios
**Tipo de Prueba:** Integridad de Datos
**Datos Utilizados:** Cuadrilla con operarios asignados, actualizar nombre y descripción
**Resultado Esperado:** Status 200, cuadrilla actualizada, asignaciones mantenidas
**Resultado Obtenido:** Status 200, cuadrilla actualizada correctamente, asignaciones mantenidas
**Evidencia:** Test ejecutado exitosamente - 3 assertions pasaron

---

**Nombre de la Prueba:** Integridad con operarios duplicados
**Ubicación:** tests/api/CuadrillasApiTest.php
**Objetivo:** Verificar que no se permiten asignaciones duplicadas del mismo operario
**Tipo de Prueba:** Integridad de Datos
**Datos Utilizados:** {"cuadrillaId": 1, "operarios": [1, 1, 2]} (operario 1 duplicado)
**Resultado Esperado:** Status 200, solo 2 asignaciones únicas
**Resultado Obtenido:** Status 500, error interno del servidor
**Evidencia:** Test falló - Error interno del servidor al procesar operarios duplicados

---

## Pruebas de DireccionModel

**Nombre de la Prueba:** DireccionModel - Coordenadas Geográficas
**Ubicación:** tests/unit/Models/DireccionModelTest.php - método testGeographicCoordinates()
**Objetivo:** Verificar que el modelo DireccionModel maneja correctamente las coordenadas geográficas (latitud, longitud) con diferentes valores y rangos
**Tipo de Prueba:** Unitaria
**Datos Utilizados:** Coordenadas válidas para San Francisco (Santa Fe): lat -31.4280, lng -62.0826; Coordenadas extremas: Polo Norte (90.00000000, 180.00000000); Coordenadas extremas negativas: Polo Sur (-90.00000000, -180.00000000); Actualización de coordenadas existentes
**Resultado Esperado:** El modelo debe manejar correctamente las coordenadas geográficas válidas y extremas
**Resultado Obtenido:** ÉXITO - El manejo de coordenadas geográficas funciona correctamente
**Evidencia:** El test de coordenadas geográficas pasó exitosamente. El DireccionModel maneja correctamente las coordenadas: Inserta coordenadas válidas para ubicaciones reales; Maneja coordenadas extremas (Polos Norte y Sur); Maneja coordenadas extremas negativas (límites occidentales); Actualiza coordenadas existentes correctamente; Preserva la precisión decimal de las coordenadas. Se ejecutaron 7 tests con 164 aserciones, el test de coordenadas pasó exitosamente

---

## Pruebas de Normalización de Direcciones

**Nombre de la Prueba:** DireccionesNormalizacionTest - Normalización mayusculas minusculas
**Ubicación:** tests/integration/Api/DireccionesNormalizacionTest.php - método testNormalizacionMayusculasMinusculas()
**Objetivo:** Verificar que se normalizan correctamente las mayúsculas y minúsculas
**Tipo de Prueba:** Test de integración de base de datos
**Datos Utilizados:** Domicilio "Av. sAn Martín" con número "567"
**Resultado Esperado:** El texto debe convertirse a mayúsculas y normalizarse
**Resultado Obtenido:** PASÓ - Las mayúsculas y minúsculas se normalizan correctamente
**Evidencia:** "Av. sAn Martín" → "AV. SAN MARTIN"

---

**Nombre de la Prueba:** DireccionesNormalizacionTest - Normalización consistencia
**Ubicación:** tests/integration/Api/DireccionesNormalizacionTest.php - método testNormalizacionConsistencia()
**Objetivo:** Verificar que diferentes variaciones del mismo texto se normalizan igual
**Tipo de Prueba:** Test de integración de base de datos
**Datos Utilizados:** 6 variaciones de "José de San Martín" con diferentes tildes y espacios
**Resultado Esperado:** Todas las variaciones deben normalizarse al mismo resultado
**Resultado Obtenido:** PASÓ - Todas las variaciones se normalizan consistentemente
**Evidencia:** 6 variaciones diferentes → "JOSE DE SAN MARTIN"

---

**Nombre de la Prueba:** DireccionesNormalizacionTest - Normalización texto con enie
**Ubicación:** tests/integration/Api/DireccionesNormalizacionTest.php - método testNormalizacionTextoConEnie()
**Objetivo:** Verificar que Ñ se convierte a N en la normalización
**Tipo de Prueba:** Test de integración de base de datos
**Datos Utilizados:** Domicilio "Ñuñorco" con número "890"
**Resultado Esperado:** La Ñ debe convertirse a N
**Resultado Obtenido:** PASÓ - La Ñ se convierte correctamente
**Evidencia:** "Ñuñorco" → "NUNORCO"

---

**Nombre de la Prueba:** DireccionesNormalizacionTest - Normalización caracteres especiales complejos
**Ubicación:** tests/integration/Api/DireccionesNormalizacionTest.php - método testNormalizacionCaracteresEspecialesComplejos()
**Objetivo:** Verificar que se normalizan correctamente múltiples caracteres especiales
**Tipo de Prueba:** Test de integración de base de datos
**Datos Utilizados:** Domicilio "José María de Ávila y Ñuñorco" con número "1234"
**Resultado Esperado:** Todos los caracteres especiales deben normalizarse
**Resultado Obtenido:** PASÓ - Los caracteres especiales se normalizan correctamente
**Evidencia:** "José María de Ávila y Ñuñorco" → "JOSE MARIA DE AVILA Y NUNORCO"

---

**Nombre de la Prueba:** DireccionesNormalizacionTest - Normalización texto con tildes
**Ubicación:** tests/integration/Api/DireccionesNormalizacionTest.php - método testNormalizacionTextoConTildes()
**Objetivo:** Verificar que se normalizan correctamente las tildes
**Tipo de Prueba:** Test de integración de base de datos
**Datos Utilizados:** Domicilio "José de San Martín" con número "1234"
**Resultado Esperado:** La normalización debe convertir correctamente las tildes y espacios
**Resultado Obtenido:** PASÓ - La normalización funciona correctamente
**Evidencia:** "José de San Martín" → "JOSE DE SAN MARTIN"

---

**Nombre de la Prueba:** DireccionesNormalizacionTest - Normalización texto con espacios
**Ubicación:** tests/integration/Api/DireccionesNormalizacionTest.php - método testNormalizacionTextoConEspacios()
**Objetivo:** Verificar que se manejan correctamente los espacios al inicio y final
**Tipo de Prueba:** Test de integración de base de datos
**Datos Utilizados:** Domicilio " Av. Maipú " con número "567"
**Resultado Esperado:** Los espacios extra deben eliminarse y el texto normalizarse
**Resultado Obtenido:** PASÓ - Los espacios se eliminan correctamente
**Evidencia:** " Av. Maipú " → "AV. MAIPU"

---

## Pruebas de Geocoding API

**Nombre de la Prueba:** GeocodingApiTest - Google Maps Calle Córdoba
**Ubicación:** tests/integration/Api/GeocodingApiTest.php - método testGoogleMapsCalleCordoba()
**Objetivo:** Verificar que Google Maps API devuelve Calle Córdoba para coordenadas específicas
**Tipo de Prueba:** Test de integración con API externa
**Datos Utilizados:** Coordenadas "-31.442028, -62.090032" (Calle Córdoba altura 1800, San Francisco, Córdoba)
**Resultado Esperado:** La API debe devolver "Córdoba" o "Cordoba" en San Francisco, Córdoba, Argentina
**Resultado Obtenido:** PASÓ - Google Maps API devuelve la calle correcta
**Evidencia:** "Córdoba 1917, San Francisco, Córdoba, Argentina"

---

**Nombre de la Prueba:** GeocodingApiTest - Mapbox Calle Córdoba
**Ubicación:** tests/integration/Api/GeocodingApiTest.php - método testMapboxCalleCordoba()
**Objetivo:** Verificar que Mapbox API devuelve Calle Córdoba para coordenadas específicas
**Tipo de Prueba:** Test de integración con API externa
**Datos Utilizados:** Coordenadas "-31.442028, -62.090032" (Calle Córdoba altura 1800, San Francisco, Córdoba)
**Resultado Esperado:** La API debe devolver "Córdoba" o "Cordoba" en San Francisco, Córdoba, Argentina
**Resultado Obtenido:** FALLÓ - Mapbox API devuelve calle incorrecta
**Evidencia:** "Rioja, San Francisco, Provincia de Córdoba, X2400, Argentina"

---

**Nombre de la Prueba:** GeocodingApiTest - Google Maps Av Maipú
**Ubicación:** tests/integration/Api/GeocodingApiTest.php - método testGoogleMapsAvMaipu()
**Objetivo:** Verificar que Google Maps API devuelve Av Maipú para coordenadas específicas
**Tipo de Prueba:** Test de integración con API externa
**Datos Utilizados:** Coordenadas "-31.414374, -62.094000" (Av Maipú, San Francisco, Córdoba)
**Resultado Esperado:** La API debe devolver "Maipú" o "Maipu" en San Francisco, Córdoba, Argentina
**Resultado Obtenido:** PASÓ - Google Maps API devuelve la calle correcta
**Evidencia:** "Av. Maipú 1433, San Francisco, Córdoba, Argentina"

---

**Nombre de la Prueba:** GeocodingApiTest - Mapbox Av Maipú
**Ubicación:** tests/integration/Api/GeocodingApiTest.php - método testMapboxAvMaipu()
**Objetivo:** Verificar que Mapbox API devuelve Av Maipú para coordenadas específicas
**Tipo de Prueba:** Test de integración con API externa
**Datos Utilizados:** Coordenadas "-31.414374, -62.094000" (Av Maipú, San Francisco, Córdoba)
**Resultado Esperado:** La API debe devolver "Maipú" o "Maipu" en San Francisco, Córdoba, Argentina
**Resultado Obtenido:** FALLÓ - Mapbox API no devuelve la calle esperada
**Evidencia:** "Empalme Rp1, San Francisco, Provincia de Córdoba, X2400, Argentina"

---

**Nombre de la Prueba:** GeocodingApiTest - Google Maps Reverse Geocoding
**Ubicación:** tests/integration/Api/GeocodingApiTest.php - método testGoogleMapsReverseGeocoding()
**Objetivo:** Verificar que Google Maps API devuelve la dirección correcta para coordenadas específicas
**Tipo de Prueba:** Test de integración con API externa
**Datos Utilizados:** Coordenadas "-31.420207, -62.108582" (Juan Jose Paso Sur, San Francisco, Córdoba)
**Resultado Esperado:** La API debe devolver "Juan Jose Paso Sur" en San Francisco, Córdoba, Argentina
**Resultado Obtenido:** PASÓ - Google Maps API devuelve la dirección correcta
**Evidencia:** "Juan Jose Paso Sur 4078, San Francisco, Córdoba, Argentina"

---

**Nombre de la Prueba:** GeocodingApiTest - Mapbox Reverse Geocoding
**Ubicación:** tests/integration/Api/GeocodingApiTest.php - método testMapboxReverseGeocoding()
**Objetivo:** Verificar que Mapbox API devuelve la dirección correcta para coordenadas específicas
**Tipo de Prueba:** Test de integración con API externa
**Datos Utilizados:** Coordenadas "-31.420207, -62.108582" (Juan Jose Paso Sur, San Francisco, Córdoba)
**Resultado Esperado:** La API debe devolver "Juan Jose Paso Sur" en San Francisco, Córdoba, Argentina
**Resultado Obtenido:** PASÓ - Mapbox API devuelve la dirección correcta
**Evidencia:** "Juan Jose Paso (Sur), San Francisco, Provincia de Córdoba, X2400, Argentina"

---