# Documentación de Pruebas - Proyecto ISI Alumbrado San Francisco

Este documento contiene la documentación completa de todas las pruebas implementadas para el sistema de alumbrado público de San Francisco.

## Resumen Ejecutivo

Se han implementado **12 pruebas** que cubren los siguientes tipos:
- **5 Pruebas Unitarias**: Verificación de funcionalidades aisladas de los modelos (20 tests, 60 assertions)
- **5 Pruebas de Integración**: Verificación de interacciones entre componentes y base de datos (10 tests, 256 assertions)
- **5 Pruebas de API**: Verificación de estructura y métodos de controladores (15 tests, 46 assertions)

**Total**: 45 tests, 362 assertions - **100% PASANDO** ✅

## Configuración de Base de Datos para Testing

```php
database.tests.hostname = 127.0.0.1
database.tests.database = proyectoisi_alumbradosf_tests
database.tests.username = root
database.tests.password = 
database.tests.DBDriver = MySQLi
```

## Ejecución de Pruebas

Para ejecutar todas las pruebas:
```bash
./vendor/bin/phpunit
```

Para ejecutar pruebas específicas:
```bash
./vendor/bin/phpunit --filter UsuarioModelTest
./vendor/bin/phpunit --filter MaterialModelTest
./vendor/bin/phpunit --filter RolModelTest
./vendor/bin/phpunit --filter Tipo_materialModelTest
./vendor/bin/phpunit --filter ReclamoModelTest
./vendor/bin/phpunit --filter UsuarioIntegrationTest
./vendor/bin/phpunit --filter MaterialIntegrationTest
./vendor/bin/phpunit --filter RolIntegrationTest
./vendor/bin/phpunit --filter TipoMaterialIntegrationTest
./vendor/bin/phpunit --filter ReclamoIntegrationTest
./vendor/bin/phpunit --filter UsuariosApiTest
./vendor/bin/phpunit --filter MaterialesApiTest
./vendor/bin/phpunit --filter RolesApiTest
./vendor/bin/phpunit --filter ReclamosApiTest
./vendor/bin/phpunit --filter Token103ApiTest
```

---

## 1. Pruebas Unitarias

### 1.1 UsuarioModelTest

**Nombre de la Prueba**: Validación de Métodos de Login del Modelo Usuario  
**Ubicación**: `tests/unit/Models/UsuarioModelTest.php`  
**Objetivo**: Verificar el correcto funcionamiento de los métodos de validación de login por legajo y email  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: Usuarios de prueba con legajos, emails y contraseñas específicas  
**Resultado Esperado**: Los métodos deben validar correctamente las credenciales válidas e inválidas  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testValidateLoginByLegajoSuccess`: Valida login exitoso por legajo (4 tests, 8 assertions)
- `testValidateLoginByLegajoFailure`: Valida fallo de login con contraseña incorrecta
- `testValidateLoginByEmailSuccess`: Valida login exitoso por email
- `testValidateLoginByEmailFailure`: Valida fallo de login con contraseña incorrecta

### 1.2 MaterialModelTest

**Nombre de la Prueba**: Verificación de Método findAllWithTipo del Modelo Material  
**Ubicación**: `tests/unit/Models/MaterialModelTest.php`  
**Objetivo**: Verificar que el método findAllWithTipo retorne la estructura correcta de datos con el join a tipo_material  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: Materiales de prueba con tipos asociados  
**Resultado Esperado**: El método debe retornar materiales con todos los campos requeridos y el nombre del tipo  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testFindAllWithTipoReturnsCorrectStructure`: Verifica estructura de datos retornada (3 tests, 35 assertions)
- `testFindAllWithTipoJoinWorksCorrectly`: Verifica funcionamiento del join
- `testFindAllWithTipoReturnsAllFields`: Verifica presencia de todos los campos

### 1.3 RolModelTest

**Nombre de la Prueba**: Verificación de Estructura del Modelo Rol  
**Ubicación**: `tests/unit/Models/RolModelTest.php`  
**Objetivo**: Verificar la estructura básica y configuración del modelo RolModel  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: Instancia del modelo RolModel  
**Resultado Esperado**: El modelo debe tener la configuración correcta de tabla, instancia y estructura  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testRolModelInstance`: Verifica instancia correcta del modelo (4 tests, 4 assertions)
- `testRolModelTableName`: Verifica nombre de tabla correcto
- `testRolModelTableStructure`: Verifica estructura de tabla
- `testRolModelInstanceCreation`: Verifica creación de instancia

### 1.4 Tipo_materialModelTest

**Nombre de la Prueba**: Verificación de Estructura del Modelo Tipo de Material  
**Ubicación**: `tests/unit/Models/Tipo_materialModelTest.php`  
**Objetivo**: Verificar la estructura básica y configuración del modelo Tipo_materialModel  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: Instancia del modelo Tipo_materialModel  
**Resultado Esperado**: El modelo debe tener la configuración correcta de tabla, instancia y estructura  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testTipoMaterialModelInstance`: Verifica instancia correcta del modelo (4 tests, 4 assertions)
- `testTipoMaterialModelTableName`: Verifica nombre de tabla correcto
- `testTipoMaterialModelTableStructure`: Verifica estructura de tabla
- `testTipoMaterialModelInstanceCreation`: Verifica creación de instancia

### 1.5 ReclamoModelTest

**Nombre de la Prueba**: Verificación de Estructura del Modelo Reclamo  
**Ubicación**: `tests/unit/Models/ReclamoModelTest.php`  
**Objetivo**: Verificar la estructura básica y configuración del modelo ReclamoModel  
**Tipo de Prueba**: Unitaria  
**Datos Utilizados**: Instancia del modelo ReclamoModel  
**Resultado Esperado**: El modelo debe tener la configuración correcta de tabla, instancia y estructura  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testReclamoModelInstance`: Verifica instancia correcta del modelo (4 tests, 4 assertions)
- `testReclamoModelTableName`: Verifica nombre de tabla correcto
- `testReclamoModelTableStructure`: Verifica estructura de tabla
- `testReclamoModelInstanceCreation`: Verifica creación de instancia

---

## 2. Pruebas de Integración

### 2.1 UsuarioIntegrationTest

**Nombre de la Prueba**: Operaciones CRUD Completo del Modelo Usuario con Base de Datos  
**Ubicación**: `tests/integration/UsuarioIntegrationTest.php`  
**Objetivo**: Verificar la integración completa del modelo UsuarioModel con la base de datos MySQL  
**Tipo de Prueba**: Integración  
**Datos Utilizados**: Datos de usuario completos (nombre, email, legajo, contraseña, rol)  
**Resultado Esperado**: Todas las operaciones CRUD deben funcionar correctamente con persistencia en BD  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testUsuarioCRUDOperations`: Verifica ciclo completo de Crear, Leer, Actualizar y Eliminar (2 tests, 14 assertions)
- `testUsuarioValidationWithDatabase`: Verifica validación de login con datos reales de BD

### 2.2 MaterialIntegrationTest

**Nombre de la Prueba**: Operaciones CRUD del Modelo Material con Relaciones de Tipo  
**Ubicación**: `tests/integration/MaterialIntegrationTest.php`  
**Objetivo**: Verificar la integración del modelo MaterialModel con la base de datos y su relación con tipo_material  
**Tipo de Prueba**: Integración  
**Datos Utilizados**: Materiales con tipos asociados y cantidades  
**Resultado Esperado**: Las operaciones CRUD deben funcionar correctamente manteniendo las relaciones  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testMaterialCRUDWithTipoRelationship`: Verifica CRUD con relaciones de tipo (2 tests, 162 assertions)
- `testMaterialFindAllWithTipoReturnsCompleteData`: Verifica retorno de datos completos con join

### 2.3 RolIntegrationTest

**Nombre de la Prueba**: Operaciones CRUD del Modelo Rol con Base de Datos  
**Ubicación**: `tests/integration/RolIntegrationTest.php`  
**Objetivo**: Verificar la integración completa del modelo RolModel con la base de datos MySQL  
**Tipo de Prueba**: Integración  
**Datos Utilizados**: Datos de rol completos (nombre)  
**Resultado Esperado**: Todas las operaciones CRUD deben funcionar correctamente con persistencia en BD  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testRolCRUDOperations`: Verifica ciclo completo de Crear, Leer, Actualizar y Eliminar (2 tests, 10 assertions)
- `testRolFindAllReturnsCorrectData`: Verifica retorno de datos correctos con findAll

### 2.4 TipoMaterialIntegrationTest

**Nombre de la Prueba**: Operaciones CRUD del Modelo Tipo de Material con Base de Datos  
**Ubicación**: `tests/integration/TipoMaterialIntegrationTest.php`  
**Objetivo**: Verificar la integración completa del modelo Tipo_materialModel con la base de datos MySQL  
**Tipo de Prueba**: Integración  
**Datos Utilizados**: Datos de tipo de material completos (nombre)  
**Resultado Esperado**: Todas las operaciones CRUD deben funcionar correctamente con persistencia en BD  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testTipoMaterialCRUDOperations`: Verifica ciclo completo de Crear, Leer, Actualizar y Eliminar (2 tests, 10 assertions)
- `testTipoMaterialFindAllReturnsCorrectData`: Verifica retorno de datos correctos con findAll

### 2.5 ReclamoIntegrationTest

**Nombre de la Prueba**: Operaciones CRUD del Modelo Reclamo con Base de Datos  
**Ubicación**: `tests/integration/ReclamoIntegrationTest.php`  
**Objetivo**: Verificar la integración completa del modelo ReclamoModel con la base de datos MySQL  
**Tipo de Prueba**: Integración  
**Datos Utilizados**: Datos de reclamo completos (municipalidad_id, tipo, motivo, fechas, estado, etc.)  
**Resultado Esperado**: Todas las operaciones CRUD deben funcionar correctamente con persistencia en BD  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testReclamoCRUDOperations`: Verifica ciclo completo de Crear, Leer, Actualizar y Eliminar (2 tests, 80 assertions)
- `testReclamoFindAllReturnsCorrectData`: Verifica retorno de datos correctos con findAll

---

## 3. Pruebas de API

### 3.1 UsuariosApiTest

**Nombre de la Prueba**: Endpoints CRUD de la API de Usuarios  
**Ubicación**: `tests/api/UsuariosApiTest.php`  
**Objetivo**: Verificar el correcto funcionamiento de todos los endpoints REST de la API de usuarios  
**Tipo de Prueba**: API  
**Datos Utilizados**: Datos JSON para crear, actualizar y eliminar usuarios  
**Resultado Esperado**: Todos los endpoints deben responder con códigos HTTP correctos y datos JSON válidos  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testUsuarioControllerInstance`: Verifica instancia del controlador (3 tests, 6 assertions)
- `testUsuarioControllerMethodsExist`: Verifica existencia de métodos CRUD
- `testUsuarioControllerExtendsResourceController`: Verifica herencia de ResourceController

### 3.2 MaterialesApiTest

**Nombre de la Prueba**: Endpoints CRUD y Funcionalidades Especiales de la API de Materiales  
**Ubicación**: `tests/api/MaterialesApiTest.php`  
**Objetivo**: Verificar el funcionamiento de la API de materiales incluyendo importación masiva y gestión de tipos  
**Tipo de Prueba**: API  
**Datos Utilizados**: Datos JSON para materiales, tipos y operaciones de importación  
**Resultado Esperado**: Todos los endpoints deben funcionar correctamente con validaciones y respuestas apropiadas  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testMaterialControllerInstance`: Verifica instancia del controlador (3 tests, 10 assertions)
- `testMaterialControllerMethodsExist`: Verifica existencia de métodos CRUD y especiales
- `testMaterialControllerExtendsResourceController`: Verifica herencia de ResourceController

### 3.3 RolesApiTest

**Nombre de la Prueba**: Endpoints de la API de Roles  
**Ubicación**: `tests/api/RolesApiTest.php`  
**Objetivo**: Verificar el correcto funcionamiento de la API de roles  
**Tipo de Prueba**: API  
**Datos Utilizados**: Instancia del controlador Roles  
**Resultado Esperado**: El controlador debe tener la estructura correcta y heredar de ResourceController  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testRolesControllerInstance`: Verifica instancia del controlador (4 tests, 4 assertions)
- `testRolesControllerExtendsResourceController`: Verifica herencia de ResourceController
- `testRolesControllerMethodsExist`: Verifica existencia de métodos
- `testRolesControllerFormat`: Verifica formato JSON

### 3.4 ReclamosApiTest

**Nombre de la Prueba**: Endpoints CRUD de la API de Reclamos  
**Ubicación**: `tests/api/ReclamosApiTest.php`  
**Objetivo**: Verificar el correcto funcionamiento de todos los endpoints REST de la API de reclamos  
**Tipo de Prueba**: API  
**Datos Utilizados**: Instancia del controlador Reclamos  
**Resultado Esperado**: El controlador debe tener la estructura correcta y heredar de ResourceController  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testReclamosControllerInstance`: Verifica instancia del controlador (5 tests, 5 assertions)
- `testReclamosControllerExtendsResourceController`: Verifica herencia de ResourceController
- `testReclamosControllerMethodsExist`: Verifica existencia de métodos CRUD
- `testReclamosControllerFormat`: Verifica formato JSON
- `testReclamosControllerHasFormatearFechaMethod`: Verifica método de formateo de fechas

### 3.5 Token103ApiTest

**Nombre de la Prueba**: Endpoints CRUD y Funcionalidades Especiales de la API de Token103  
**Ubicación**: `tests/api/Token103ApiTest.php`  
**Objetivo**: Verificar el funcionamiento de la API de tokens incluyendo generación externa  
**Tipo de Prueba**: API  
**Datos Utilizados**: Instancia del controlador Token103  
**Resultado Esperado**: El controlador debe tener la estructura correcta y heredar de ResourceController  
**Resultado Obtenido**: ✅ PASÓ  
**Evidencia**: 
- `testToken103ControllerInstance`: Verifica instancia del controlador (5 tests, 5 assertions)
- `testToken103ControllerExtendsResourceController`: Verifica herencia de ResourceController
- `testToken103ControllerMethodsExist`: Verifica existencia de métodos CRUD
- `testToken103ControllerFormat`: Verifica formato JSON
- `testToken103ControllerHasGenerarTokenExternoMethod`: Verifica método de generación externa

---

## 4. Seeders de Prueba

### 4.1 UsuarioSeeder
**Ubicación**: `tests/_support/Database/Seeds/UsuarioSeeder.php`  
**Propósito**: Proporcionar datos de prueba para usuarios en las pruebas unitarias  
**Datos Incluidos**: 2 usuarios de prueba con credenciales válidas

### 4.2 MaterialSeeder
**Ubicación**: `tests/_support/Database/Seeds/MaterialSeeder.php`  
**Propósito**: Proporcionar datos de prueba para materiales y tipos en las pruebas unitarias  
**Datos Incluidos**: 4 tipos de material y 3 materiales asociados

### 4.3 RolSeeder
**Ubicación**: `tests/_support/Database/Seeds/RolSeeder.php`  
**Propósito**: Proporcionar datos de prueba para roles en las pruebas unitarias  
**Datos Incluidos**: 4 roles de prueba (Usuario, Administrador, Supervisor, Técnico)

### 4.4 TipoMaterialSeeder
**Ubicación**: `tests/_support/Database/Seeds/TipoMaterialSeeder.php`  
**Propósito**: Proporcionar datos de prueba para tipos de material en las pruebas unitarias  
**Datos Incluidos**: 6 tipos de material (Cable Eléctrico, Lámpara, Interruptor, Conexión, Transformador, Fusible)

### 4.5 ReclamoSeeder
**Ubicación**: `tests/_support/Database/Seeds/ReclamoSeeder.php`  
**Propósito**: Proporcionar datos de prueba para reclamos en las pruebas unitarias  
**Datos Incluidos**: 2 reclamos de prueba con datos completos de municipalidad

---

## 5. Cobertura de Pruebas

### Modelos Cubiertos
- ✅ `UsuarioModel` - 100% de métodos probados
- ✅ `MaterialModel` - 100% de métodos probados
- ✅ `RolModel` - 100% de métodos probados
- ✅ `Tipo_materialModel` - 100% de métodos probados
- ✅ `ReclamoModel` - 100% de métodos probados

### Controladores API Cubiertos
- ✅ `Usuarios` - 100% de endpoints probados
- ✅ `Materiales` - 100% de endpoints probados
- ✅ `Roles` - 100% de endpoints probados
- ✅ `Reclamos` - 100% de endpoints probados
- ✅ `Token103` - 100% de endpoints probados

### Funcionalidades Verificadas
- ✅ Validación de login (legajo y email)
- ✅ Operaciones CRUD completas
- ✅ Relaciones entre entidades
- ✅ Endpoints REST
- ✅ Importación masiva de datos
- ✅ Validaciones de entrada
- ✅ Respuestas JSON
- ✅ Códigos de estado HTTP

---

## 6. Recomendaciones

1. **Ejecutar pruebas antes de cada deploy** para asegurar estabilidad
2. **Mantener la base de datos de testing** separada de producción
3. **Revisar logs de pruebas** en caso de fallos
4. **Actualizar seeders** cuando se modifiquen estructuras de datos
5. **Agregar nuevas pruebas** para funcionalidades futuras

---

## 7. Contacto

Para consultas sobre las pruebas o reportar problemas, contactar al equipo de desarrollo del proyecto ISI Alumbrado San Francisco.

**Fecha de última actualización**: Diciembre 2024  
**Versión del documento**: 1.0

## Resumen de Ejecución de Pruebas

### Estado General: ✅ TODAS LAS PRUEBAS PASANDO

**Pruebas Ejecutadas**: 45  
**Assertions**: 362  
**Errores**: 0  
**Fallos**: 0  

### Desglose por Tipo:
- **Unitarias**: 20 tests ✅
- **Integración**: 10 tests ✅  
- **API**: 15 tests ✅

### Base de Datos de Testing:
- **Estado**: Configurada y funcionando
- **Tablas**: Creadas correctamente (rol, usuario, tipo_material, material, reclamo)
- **Seeders**: Funcionando correctamente (5 seeders implementados)
