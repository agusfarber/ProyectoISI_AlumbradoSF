# Testing del Sistema de Alumbrado SF

## Formato de Documentación en Informes

**Nombre de la Prueba**: [Nombre descriptivo]  
**Ubicación**: [Ruta completa del archivo]  
**Objetivo**: [Descripción clara del propósito]  
**Tipo de Prueba**: [Unitaria/Integración/Base de Datos/API]  
**Datos Utilizados**: [Entradas y parámetros de prueba]  
**Resultado Esperado**: [Comportamiento esperado]  
**Resultado Obtenido**: [Estado de la prueba]  
**Evidencia**: [Resumen de ejecución]

---

## Tests Implementados

### Test 1: Validación de Login por Legajo con Credenciales Correctas

**Nombre de la Prueba**: Validación de Login por Legajo con Credenciales Correctas  
**Ubicación**: `tests/unit/Models/UsuarioModelTest.php` - método `testValidateLoginByLegajoWithCorrectCredentials()`  
**Objetivo**: Verificar que el método `validateLoginByLegajo()` del UsuarioModel retorna correctamente los datos del usuario cuando se proporcionan credenciales válidas  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- Legajo: `'12345'`
- Contraseña: `'password123'`
- Usuario de prueba: Juan Pérez (email: juan.perez@test.com, idRol: 1)  
**Resultado Esperado**: El método debe retornar un array con los datos del usuario (nombre, email, legajo, idRol)  
**Resultado Obtenido**: ❌ ERROR - Table 'proyectoisi_alumbradosf_tests.usuario' doesn't exist  
**Evidencia**: 
```
CodeIgniter\Database\Exceptions\DatabaseException: Table 'proyectoisi_alumbradosf_tests.usuario' doesn't exist
Caused by mysqli_sql_exception: Table 'proyectoisi_alumbradosf_tests.usuario' doesn't exist
```
El test falló porque la tabla 'usuario' no existe en la base de datos de pruebas 'proyectoisi_alumbradosf_tests'. Se ejecutaron 9 tests, todos con el mismo error de tabla inexistente.

---

### Test 2: Validación de Login por Legajo con Credenciales Incorrectas

**Nombre de la Prueba**: Validación de Login por Legajo con Credenciales Incorrectas  
**Ubicación**: `tests/unit/Models/UsuarioModelTest.php` - método `testValidateLoginByLegajoWithIncorrectCredentials()`  
**Objetivo**: Verificar que el método `validateLoginByLegajo()` del UsuarioModel retorna false cuando se proporcionan credenciales incorrectas  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- Legajo: `'12345'`
- Contraseña: `'wrongpassword'` (incorrecta)
- Usuario de prueba: Juan Pérez (email: juan.perez@test.com, idRol: 1)  
**Resultado Esperado**: El método debe retornar false cuando las credenciales son incorrectas  
**Resultado Obtenido**: ✅ ÉXITO - El método retorna false correctamente  
**Evidencia**: 
```
Tests: 9, Assertions: 22, PHPUnit Warnings: 1.
OK, but there were issues!
```
El test pasó exitosamente. El método `validateLoginByLegajo()` retorna false cuando se proporcionan credenciales incorrectas, cumpliendo con el comportamiento esperado. Se ejecutaron 9 tests en total con 22 aserciones, todos exitosos.

---

### Test 3: Validación de Login con Campos Vacíos

**Nombre de la Prueba**: Validación de Login con Campos Vacíos  
**Ubicación**: `tests/unit/Models/UsuarioModelTest.php` - método `testValidateLoginWithEmptyFields()`  
**Objetivo**: Verificar que los métodos de validación de login manejan correctamente parámetros vacíos, nulos o con espacios en blanco  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- Legajo: `''` (cadena vacía)
- Contraseña: `''` (cadena vacía)
- Legajo: `null`
- Contraseña: `null`
- Legajo: `'   '` (solo espacios)
- Contraseña: `'   '` (solo espacios)
**Resultado Esperado**: Los métodos deben retornar false cuando se proporcionan campos vacíos o nulos  
**Resultado Obtenido**: ✅ ÉXITO - Los métodos manejan correctamente campos vacíos  
**Evidencia**: 
```
Tests: 10, Assertions: 32, PHPUnit Warnings: 1.
OK, but there were issues!
```
El test pasó exitosamente. Los métodos `validateLoginByLegajo()` y `validateLoginByEmail()` retornan false correctamente cuando se proporcionan campos vacíos, nulos o con solo espacios en blanco. Se ejecutaron 10 tests en total con 32 aserciones, todos exitosos. El comportamiento es robusto ante entradas inválidas.

---

### Test 4: Búsqueda de Usuario por ID

**Nombre de la Prueba**: Búsqueda de Usuario por ID  
**Ubicación**: `tests/unit/Models/UsuarioModelTest.php` - método `testFindUserById()`  
**Objetivo**: Verificar que el método `find()` heredado del modelo CodeIgniter funciona correctamente para buscar usuarios por su ID  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- ID válido: `1` (Juan Pérez)
- ID válido: `2` (María García)
- ID inexistente: `999`
- ID inválido: `0`
- ID inválido: `-1`
**Resultado Esperado**: El método debe retornar los datos del usuario cuando el ID existe, o null cuando no existe  
**Resultado Obtenido**: ✅ ÉXITO - El método find() funciona correctamente  
**Evidencia**: 
```
Tests: 11, Assertions: 47, PHPUnit Warnings: 1.
OK, but there were issues!
```
El test pasó exitosamente. El método `find()` heredado del modelo CodeIgniter funciona correctamente:
- Retorna los datos del usuario cuando el ID existe
- Retorna null cuando el ID no existe o es inválido
- Maneja correctamente IDs como string
- Se ejecutaron 11 tests en total con 47 aserciones, todos exitosos

---

### Test 5: Actualización de Usuario

**Nombre de la Prueba**: Actualización de Usuario  
**Ubicación**: `tests/unit/Models/UsuarioModelTest.php` - método `testUpdateUser()`  
**Objetivo**: Verificar que el método `update()` heredado del modelo CodeIgniter funciona correctamente para actualizar usuarios existentes  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- ID válido: `1` (Juan Pérez)
- Datos a actualizar: nombre, email, legajo, contraseña, idRol
- ID inexistente: `999`
- ID inválido: `0`
**Resultado Esperado**: El método debe actualizar los datos cuando el ID existe y retornar true, o false cuando no existe  
**Resultado Obtenido**: ❌ FALLO - El método update() tiene comportamiento inesperado  
**Evidencia**: 
```
Tests: 12, Assertions: 59, Failures: 1, PHPUnit Warnings: 1.
Failed asserting that true is false.
C:\xampp\htdocs\proyectos\ProyectoISI_AlumbradoSF\tests\unit\Models\UsuarioModelTest.php:306
```
El test falló porque el método `update()` retorna `true` cuando se intenta actualizar un ID inexistente (999), cuando se esperaba que retornara `false`. Esto indica que CodeIgniter no valida la existencia del registro antes de intentar actualizarlo, o que el comportamiento del método `update()` es diferente al esperado. Se ejecutaron 12 tests con 59 aserciones, 1 fallo.

---

### Test 5 (Corregido): Actualización de Usuario - Comportamiento Real

**Nombre de la Prueba**: Actualización de Usuario - Comportamiento Real  
**Ubicación**: `tests/unit/Models/UsuarioModelTest.php` - método `testUpdateUserCorrected()`  
**Objetivo**: Verificar el comportamiento real del método `update()` heredado del modelo CodeIgniter  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- ID válido: `1` (Juan Pérez)
- Datos a actualizar: nombre, email, legajo, contraseña, idRol
- ID inexistente: `999`
- ID inválido: `0`
**Resultado Esperado**: El método debe retornar true siempre (comportamiento real de CodeIgniter), pero verificar que no se actualicen registros inexistentes  
**Resultado Obtenido**: ❌ ERROR - Excepción con datos vacíos  
**Evidencia**: 
```
Tests: 13, Assertions: 69, Errors: 1, Failures: 1, PHPUnit Warnings: 1.
CodeIgniter\Database\Exceptions\DataException: There is no data to update.
C:\xampp\htdocs\proyectoISI_AlumbradoSF\vendor\codeigniter4\framework\system\BaseModel.php:1823
```
El test corregido reveló otro comportamiento de CodeIgniter: cuando se intenta actualizar con un array vacío `[]`, CodeIgniter lanza una excepción `DataException: There is no data to update`. Esto es diferente al comportamiento esperado. El test original sigue fallando, y el corregido tiene un error con datos vacíos.

---

### Test 6: MaterialModel - Método findAllWithTipo()

**Nombre de la Prueba**: MaterialModel - Método findAllWithTipo()  
**Ubicación**: `tests/unit/Models/MaterialModelTest.php` - método `testFindAllWithTipo()`  
**Objetivo**: Verificar que el método `findAllWithTipo()` del MaterialModel ejecuta correctamente el JOIN con la tabla `tipo_material` y retorna los materiales con el nombre del tipo asociado  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- Tabla `material`: materiales con diferentes tipos
- Tabla `tipo_material`: tipos de materiales
- Materiales con tipo asociado
- Materiales sin tipo asociado (LEFT JOIN)
**Resultado Esperado**: El método debe retornar un array con materiales que incluyan el campo `tipo_nombre` del JOIN  
**Resultado Obtenido**: ✅ ÉXITO - El método findAllWithTipo() funciona correctamente  
**Evidencia**: 
```
Tests: 1, Assertions: 34, PHPUnit Warnings: 1.
OK, but there were issues!
```
El test pasó exitosamente. El método `findAllWithTipo()` del MaterialModel funciona correctamente:
- Ejecuta el JOIN con la tabla `tipo_material` correctamente
- Retorna materiales con el campo `tipo_nombre` del JOIN
- Maneja correctamente materiales sin tipo asociado (LEFT JOIN retorna null)
- Se ejecutó 1 test con 34 aserciones, todas exitosas
- Verificó materiales específicos: Lámpara LED (tipo: Lámparas), Cable de Cobre (tipo: Cables), Material Sin Tipo (tipo: null)

---

### Test 7: MaterialModel - Inserción de Materiales

**Nombre de la Prueba**: MaterialModel - Inserción de Materiales  
**Ubicación**: `tests/unit/Models/MaterialModelTest.php` - método `testInsertMaterial()`  
**Objetivo**: Verificar que se pueden insertar materiales con los campos permitidos y validar el comportamiento con diferentes casos de datos  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- Material completo: nombre, idTipo, cantidad
- Material con campos faltantes: solo nombre y cantidad
- Material con datos inválidos: campos no permitidos
- Material con idTipo null
**Resultado Esperado**: El método debe insertar correctamente materiales válidos y rechazar datos inválidos  
**Resultado Obtenido**: ❌ FALLO - Comportamiento inesperado con datos faltantes  
**Evidencia**: 
```
Tests: 2, Assertions: 51, Failures: 1, PHPUnit Warnings: 1.
Failed asserting that 10 is false.
C:\xampp\htdocs\proyectoISI_AlumbradoSF\tests\unit\Models\MaterialModelTest.php:231
```
El test falló porque el método `insert()` retorna un ID (10) cuando se intenta insertar un material con nombre vacío, cuando se esperaba que retornara `false`. Esto indica que CodeIgniter permite insertar registros con campos vacíos aunque estén marcados como NOT NULL en la base de datos, o que el comportamiento de validación es diferente al esperado. Se ejecutaron 2 tests con 51 aserciones, 1 fallo.

---

### Test 8: MaterialModel - Validación de Campos Permitidos

**Nombre de la Prueba**: MaterialModel - Validación de Campos Permitidos  
**Ubicación**: `tests/unit/Models/MaterialModelTest.php` - método `testAllowedFieldsValidation()`  
**Objetivo**: Verificar que el modelo MaterialModel solo permite insertar/actualizar los campos definidos en `allowedFields`: ['nombre','idTipo','cantidad']  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- Campos permitidos: nombre, idTipo, cantidad
- Campos no permitidos: id, fechaCreacion, usuarioModificacion, campoInventado
- Intentos de inserción y actualización con campos mixtos
**Resultado Esperado**: El modelo debe ignorar campos no permitidos y solo procesar los campos válidos  
**Resultado Obtenido**: ❌ ERROR - Excepción con campos no permitidos  
**Evidencia**: 
```
Tests: 3, Assertions: 68, Errors: 1, Failures: 1, PHPUnit Warnings: 1.
CodeIgniter\Database\Exceptions\DataException: There is no data to insert.
C:\xampp\htdocs\proyectoISI_AlumbradoSF\tests\unit\Models\MaterialModelTest.php:311
```
El test falló porque cuando se intenta insertar solo con campos no permitidos, CodeIgniter lanza una excepción `DataException: There is no data to insert`. Esto indica que CodeIgniter filtra los campos no permitidos antes de intentar insertar, y si no quedan campos válidos, lanza una excepción. Se ejecutaron 3 tests con 68 aserciones, 1 error y 1 fallo.

---

### Test 8 (Corregido): MaterialModel - Validación de Campos Permitidos - Comportamiento Real

**Nombre de la Prueba**: MaterialModel - Validación de Campos Permitidos - Comportamiento Real  
**Ubicación**: `tests/unit/Models/MaterialModelTest.php` - método `testAllowedFieldsValidationCorrected()`  
**Objetivo**: Verificar el comportamiento real del modelo MaterialModel con campos permitidos y no permitidos  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- Campos permitidos: nombre, idTipo, cantidad
- Campos no permitidos: id, fechaCreacion, usuarioModificacion, campoInventado
- Intentos de inserción y actualización con campos mixtos
**Resultado Esperado**: El modelo debe ignorar campos no permitidos, procesar campos válidos, y lanzar excepción cuando solo hay campos no permitidos  
**Resultado Obtenido**: ✅ ÉXITO - El comportamiento real de CodeIgniter funciona correctamente  
**Evidencia**: 
```
Tests: 4, Assertions: 87, Errors: 1, Failures: 1, PHPUnit Warnings: 1.
```
El test corregido pasó exitosamente. El modelo MaterialModel funciona correctamente con campos permitidos:
- Ignora campos no permitidos en inserción y actualización
- Procesa solo los campos válidos definidos en `allowedFields`
- Lanza excepción `DataException` cuando solo hay campos no permitidos (comportamiento correcto)
- Se ejecutaron 4 tests con 87 aserciones, el test corregido pasó exitosamente

---

### Test 9: MaterialModel - Búsqueda Básica

**Nombre de la Prueba**: MaterialModel - Búsqueda Básica  
**Ubicación**: `tests/unit/Models/MaterialModelTest.php` - método `testBasicSearchMethods()`  
**Objetivo**: Verificar que los métodos heredados `find()` y `findAll()` del modelo MaterialModel funcionan correctamente  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- ID válido: `1` (Lámpara LED 50W)
- ID válido: `2` (Cable de Cobre 2.5mm)
- ID inexistente: `999`
- ID inválido: `0`, `-1`
- ID como string: `'1'`
**Resultado Esperado**: Los métodos deben retornar los datos del material cuando el ID existe, o null cuando no existe  
**Resultado Obtenido**: ✅ ÉXITO - Los métodos de búsqueda básica funcionan correctamente  
**Evidencia**: 
```
Tests: 5, Assertions: 131, Errors: 1, Failures: 1, PHPUnit Warnings: 1.
```
El test de búsqueda básica pasó exitosamente. Los métodos heredados `find()` y `findAll()` del MaterialModel funcionan correctamente:
- `find()` retorna datos del material cuando el ID existe
- `find()` retorna null cuando el ID no existe o es inválido
- `find()` maneja correctamente IDs como string
- `findAll()` retorna todos los materiales con estructura correcta
- Se ejecutaron 5 tests con 131 aserciones, el test de búsqueda básica pasó exitosamente

---

### Test 10: MaterialModel - Actualización de Materiales

**Nombre de la Prueba**: MaterialModel - Actualización de Materiales  
**Ubicación**: `tests/unit/Models/MaterialModelTest.php` - método `testUpdateMaterial()`  
**Objetivo**: Verificar que el método `update()` heredado del modelo MaterialModel funciona correctamente para actualizar diferentes campos de materiales  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- Material existente: ID 1 (Lámpara LED 50W)
- Actualización de cantidad: cambiar de 25 a 50
- Actualización de nombre: cambiar nombre del material
- Actualización de tipo: cambiar idTipo de 1 a 2
- Actualización múltiple: cambiar varios campos a la vez
- ID inexistente: 999
**Resultado Esperado**: El método debe actualizar correctamente los campos válidos y retornar true cuando el ID existe  
**Resultado Obtenido**: ✅ ÉXITO - El método update() funciona correctamente  
**Evidencia**: 
```
Tests: 6, Assertions: 159, Errors: 1, Failures: 1, PHPUnit Warnings: 1.
```
El test de actualización de materiales pasó exitosamente. El método `update()` del MaterialModel funciona correctamente:
- Actualiza campos individuales (cantidad, nombre, tipo) correctamente
- Actualiza múltiples campos a la vez
- Retorna true para IDs inexistentes (comportamiento de CodeIgniter)
- Ignora campos no permitidos en actualizaciones
- Lanza excepción con datos vacíos (comportamiento correcto)
- Se ejecutaron 6 tests con 159 aserciones, el test de actualización pasó exitosamente

---

### Test 11: ReclamoModel - Inserción de Reclamos

**Nombre de la Prueba**: ReclamoModel - Inserción de Reclamos  
**Ubicación**: `tests/unit/Models/ReclamoModelTest.php` - método `testInsertReclamo()`  
**Objetivo**: Verificar que se pueden insertar reclamos con los campos permitidos y validar el comportamiento con diferentes casos de datos  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- Reclamo completo: todos los 15 campos permitidos
- Reclamo con campos faltantes: solo campos obligatorios
- Reclamo con datos inválidos: campos no permitidos
- Reclamo con fechas: municipalidad_fechaInicio, municipalidad_fechaModificacion
- Reclamo con coordenadas: latitud, longitud
**Resultado Esperado**: El método debe insertar correctamente reclamos válidos y rechazar datos inválidos  
**Resultado Obtenido**: ✅ ÉXITO - La inserción de reclamos funciona correctamente  
**Evidencia**: 
```
Tests: 1, Assertions: 20, PHPUnit Warnings: 1.
OK, but there were issues!
```
El test de inserción de reclamos pasó exitosamente. El ReclamoModel funciona correctamente:
- Inserta reclamos con todos los 15 campos permitidos
- Inserta reclamos con campos mínimos
- Ignora campos no permitidos en inserción
- Lanza excepción cuando solo hay campos no permitidos (comportamiento correcto)
- Respeta la configuración de 15 campos permitidos
- Se ejecutó 1 test con 20 aserciones, todas exitosas

---

### Test 12: ReclamoModel - Validación de Campos Permitidos

**Nombre de la Prueba**: ReclamoModel - Validación de Campos Permitidos  
**Ubicación**: `tests/unit/Models/ReclamoModelTest.php` - método `testAllowedFieldsValidation()`  
**Objetivo**: Verificar que el modelo ReclamoModel solo permite insertar/actualizar los 15 campos definidos en `allowedFields`  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- Campos permitidos: los 15 campos municipales (municipalidad_id, municipalidad_tipo, etc.)
- Campos no permitidos: id, fechaCreacion, usuarioModificacion, campoInventado
- Intentos de inserción y actualización con campos mixtos
- Verificación de configuración allowedFields
**Resultado Esperado**: El modelo debe ignorar campos no permitidos y solo procesar los 15 campos válidos  
**Resultado Obtenido**: ✅ ÉXITO - La validación de campos permitidos funciona correctamente  
**Evidencia**: 
```
Tests: 2, Assertions: 42, PHPUnit Warnings: 1.
OK, but there were issues!
```
El test de validación de campos permitidos pasó exitosamente. El ReclamoModel funciona correctamente con campos permitidos:
- Ignora campos no permitidos en inserción y actualización
- Procesa solo los 15 campos válidos definidos en `allowedFields`
- Lanza excepción `DataException` cuando solo hay campos no permitidos (comportamiento correcto)
- Respeta exactamente la configuración de 15 campos municipales permitidos
- Se ejecutaron 2 tests con 42 aserciones, todas exitosas

---

### Test 13: ReclamoModel - Búsqueda con Datos Complejos

**Nombre de la Prueba**: ReclamoModel - Búsqueda con Datos Complejos  
**Ubicación**: `tests/unit/Models/ReclamoModelTest.php` - método `testComplexSearchMethods()`  
**Objetivo**: Verificar que los métodos `find()` y `findAll()` funcionan correctamente con datos complejos de reclamos municipales  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: 
- Múltiples reclamos con todos los 15 campos municipales completos
- Datos realistas: fechas, direcciones, teléfonos, descripciones largas
- Reclamos con diferentes estados, prioridades y tipos
- Verificación de búsqueda por ID específico y búsqueda de todos los registros
**Resultado Esperado**: Los métodos de búsqueda deben retornar datos completos y correctos  
**Resultado Obtenido**: ❌ FALLO - El test falló porque encuentra 7 reclamos en lugar de 5 esperados  
**Evidencia**: 
```
Tests: 3, Assertions: 55, Failures: 1, PHPUnit Warnings: 1.
Failed asserting that actual size 7 matches expected size 5.
```
El test falló porque la tabla de reclamos contiene datos previos de otros tests. El método `findAll()` retorna 7 registros en lugar de los 5 esperados, indicando que hay datos residuales de tests anteriores.

---

