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
