<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\CuadrillaModel;

class CuadrillaModelTest extends CIUnitTestCase
{
    protected $cuadrillaModel;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Inicializar el modelo
        $this->cuadrillaModel = new CuadrillaModel();
        
        // Configurar base de datos de prueba
        $this->db = \Config\Database::connect('tests');
        
        // Limpiar tabla antes de cada test
        $this->db->table('cuadrilla')->truncate();
        
        // Insertar datos de prueba
        $this->insertTestData();
    }

    protected function tearDown(): void
    {
        // Limpiar después de cada test
        $this->db->table('cuadrilla')->truncate();
        parent::tearDown();
    }

    /**
     * Inserta datos de prueba en la tabla
     */
    private function insertTestData(): void
    {
        $cuadrillasData = [
            [
                'nombre' => 'Cuadrilla Norte',
                'descripcion' => 'Cuadrilla especializada en mantenimiento de alumbrado público en zona norte'
            ],
            [
                'nombre' => 'Cuadrilla Sur',
                'descripcion' => 'Cuadrilla especializada en mantenimiento de alumbrado público en zona sur'
            ],
            [
                'nombre' => 'Cuadrilla Centro',
                'descripcion' => 'Cuadrilla especializada en mantenimiento de alumbrado público en zona centro'
            ],
            [
                'nombre' => 'Cuadrilla Emergencias',
                'descripcion' => 'Cuadrilla de respuesta rápida para emergencias de alumbrado'
            ],
            [
                'nombre' => 'Cuadrilla Sin Descripción',
                'descripcion' => null // Para probar campos opcionales
            ]
        ];

        foreach ($cuadrillasData as $cuadrilla) {
            $this->db->table('cuadrilla')->insert($cuadrilla);
        }
    }

    /**
     * Test: Validar inserción de cuadrillas
     */
    public function testInsertCuadrilla()
    {
        // Test 1: Inserción con todos los campos permitidos
        $cuadrillaCompleta = [
            'nombre' => 'Cuadrilla Nueva',
            'descripcion' => 'Nueva cuadrilla para mantenimiento general'
        ];
        
        $cuadrillaId = $this->cuadrillaModel->insert($cuadrillaCompleta);
        $this->assertNotFalse($cuadrillaId);
        
        // Verificar que se insertó correctamente
        $cuadrillaInsertada = $this->cuadrillaModel->find($cuadrillaId);
        $this->assertEquals('Cuadrilla Nueva', $cuadrillaInsertada['nombre']);
        $this->assertEquals('Nueva cuadrilla para mantenimiento general', $cuadrillaInsertada['descripcion']);
        
        // Test 2: Inserción con solo nombre (descripción opcional)
        $cuadrillaMinima = [
            'nombre' => 'Cuadrilla Mínima'
        ];
        
        $cuadrillaId2 = $this->cuadrillaModel->insert($cuadrillaMinima);
        $this->assertNotFalse($cuadrillaId2);
        
        // Verificar que se insertó correctamente
        $cuadrillaInsertada2 = $this->cuadrillaModel->find($cuadrillaId2);
        $this->assertEquals('Cuadrilla Mínima', $cuadrillaInsertada2['nombre']);
        $this->assertNull($cuadrillaInsertada2['descripcion']);
        
        // Test 3: Inserción con campos no permitidos mezclados
        $cuadrillaConCamposNoPermitidos = [
            'nombre' => 'Cuadrilla con Campos Extra',
            'descripcion' => 'Descripción válida',
            'id' => 999, // Campo no permitido (primary key)
            'fechaCreacion' => '2024-01-01', // Campo no permitido
            'usuarioModificacion' => 'admin', // Campo no permitido
            'campoInventado' => 'valorInventado' // Campo no permitido
        ];
        
        $cuadrillaId3 = $this->cuadrillaModel->insert($cuadrillaConCamposNoPermitidos);
        $this->assertNotFalse($cuadrillaId3);
        
        // Verificar que solo se insertaron los campos permitidos
        $cuadrillaInsertada3 = $this->cuadrillaModel->find($cuadrillaId3);
        $this->assertEquals('Cuadrilla con Campos Extra', $cuadrillaInsertada3['nombre']);
        $this->assertEquals('Descripción válida', $cuadrillaInsertada3['descripcion']);
        
        // Verificar que los campos no permitidos NO se insertaron
        $this->assertArrayNotHasKey('fechaCreacion', $cuadrillaInsertada3);
        $this->assertArrayNotHasKey('usuarioModificacion', $cuadrillaInsertada3);
        $this->assertArrayNotHasKey('campoInventado', $cuadrillaInsertada3);
        
        // Verificar que el ID es el correcto (no el que intentamos forzar)
        $this->assertNotEquals(999, $cuadrillaInsertada3['id']);
        $this->assertEquals($cuadrillaId3, $cuadrillaInsertada3['id']);
        
        // Test 4: Intentar insertar solo con campos no permitidos
        $soloCamposNoPermitidos = [
            'fechaCreacion' => '2024-01-01',
            'usuarioCreacion' => 'admin',
            'campoInventado' => 'valor'
        ];
        
        // Esto debería lanzar excepción
        $this->expectException(\CodeIgniter\Database\Exceptions\DataException::class);
        $this->expectExceptionMessage('There is no data to insert');
        $this->cuadrillaModel->insert($soloCamposNoPermitidos);
    }

    /**
     * Test: Validar campos permitidos en inserción y actualización
     */
    public function testAllowedFieldsValidation()
    {
        // Test 1: Inserción con campos permitidos y no permitidos mezclados
        $cuadrillaConCamposMixtos = [
            'nombre' => 'Cuadrilla Test Campos',
            'descripcion' => 'Descripción de prueba',
            'id' => 999, // Campo no permitido (primary key)
            'fechaCreacion' => '2024-01-01', // Campo no permitido
            'usuarioModificacion' => 'admin', // Campo no permitido
            'campoInventado' => 'valorInventado' // Campo no permitido
        ];
        
        $cuadrillaId = $this->cuadrillaModel->insert($cuadrillaConCamposMixtos);
        $this->assertNotFalse($cuadrillaId);
        
        // Verificar que solo se insertaron los campos permitidos
        $cuadrillaInsertada = $this->cuadrillaModel->find($cuadrillaId);
        $this->assertEquals('Cuadrilla Test Campos', $cuadrillaInsertada['nombre']);
        $this->assertEquals('Descripción de prueba', $cuadrillaInsertada['descripcion']);
        
        // Verificar que los campos no permitidos NO se insertaron
        $this->assertArrayNotHasKey('fechaCreacion', $cuadrillaInsertada);
        $this->assertArrayNotHasKey('usuarioModificacion', $cuadrillaInsertada);
        $this->assertArrayNotHasKey('campoInventado', $cuadrillaInsertada);
        
        // Verificar que el ID es el correcto (no el que intentamos forzar)
        $this->assertNotEquals(999, $cuadrillaInsertada['id']);
        $this->assertEquals($cuadrillaId, $cuadrillaInsertada['id']);
        
        // Test 2: Actualización con campos permitidos y no permitidos
        $datosActualizacion = [
            'nombre' => 'Cuadrilla Actualizada',
            'descripcion' => 'Descripción actualizada',
            'id' => 888, // Campo no permitido
            'fechaModificacion' => '2024-12-01', // Campo no permitido
            'campoFalso' => 'valorFalso' // Campo no permitido
        ];
        
        $resultadoUpdate = $this->cuadrillaModel->update($cuadrillaId, $datosActualizacion);
        $this->assertTrue($resultadoUpdate);
        
        // Verificar que solo se actualizaron los campos permitidos
        $cuadrillaActualizada = $this->cuadrillaModel->find($cuadrillaId);
        $this->assertEquals('Cuadrilla Actualizada', $cuadrillaActualizada['nombre']);
        $this->assertEquals('Descripción actualizada', $cuadrillaActualizada['descripcion']);
        
        // Verificar que los campos no permitidos NO se actualizaron
        $this->assertArrayNotHasKey('fechaModificacion', $cuadrillaActualizada);
        $this->assertArrayNotHasKey('campoFalso', $cuadrillaActualizada);
        
        // Verificar que el ID no cambió (no se puede actualizar)
        $this->assertEquals($cuadrillaId, $cuadrillaActualizada['id']);
        $this->assertNotEquals(888, $cuadrillaActualizada['id']);
        
        // Test 3: Verificar que el modelo respeta la configuración allowedFields
        $reflection = new \ReflectionClass($this->cuadrillaModel);
        $allowedFieldsProperty = $reflection->getProperty('allowedFields');
        $allowedFieldsProperty->setAccessible(true);
        $allowedFields = $allowedFieldsProperty->getValue($this->cuadrillaModel);
        
        $this->assertEquals(['nombre', 'descripcion'], $allowedFields);
        $this->assertCount(2, $allowedFields);
        $this->assertContains('nombre', $allowedFields);
        $this->assertContains('descripcion', $allowedFields);
        $this->assertNotContains('id', $allowedFields);
        $this->assertNotContains('fechaCreacion', $allowedFields);
        
        // Test 4: Actualizar con datos vacíos
        $datosVacios = [];
        
        // Esto debería lanzar excepción
        $this->expectException(\CodeIgniter\Database\Exceptions\DataException::class);
        $this->expectExceptionMessage('There is no data to update');
        $this->cuadrillaModel->update($cuadrillaId, $datosVacios);
    }

    /**
     * Test: Validar métodos de búsqueda básica heredados
     */
    public function testBasicSearchMethods()
    {
        // Test 1: find() con ID válido existente (Cuadrilla Norte)
        $cuadrilla = $this->cuadrillaModel->find(1);
        $this->assertNotNull($cuadrilla);
        $this->assertEquals('Cuadrilla Norte', $cuadrilla['nombre']);
        $this->assertStringContainsString('zona norte', $cuadrilla['descripcion']);
        
        // Test 2: find() con ID válido existente (Cuadrilla Sur)
        $cuadrilla2 = $this->cuadrillaModel->find(2);
        $this->assertNotNull($cuadrilla2);
        $this->assertEquals('Cuadrilla Sur', $cuadrilla2['nombre']);
        $this->assertStringContainsString('zona sur', $cuadrilla2['descripcion']);
        
        // Test 3: find() con ID inexistente
        $cuadrillaInexistente = $this->cuadrillaModel->find(999);
        $this->assertNull($cuadrillaInexistente);
        
        // Test 4: find() con ID inválido (0)
        $cuadrillaCero = $this->cuadrillaModel->find(0);
        $this->assertNull($cuadrillaCero);
        
        // Test 5: find() con ID inválido (negativo)
        $cuadrillaNegativo = $this->cuadrillaModel->find(-1);
        $this->assertNull($cuadrillaNegativo);
        
        // Test 6: find() con ID como string
        $cuadrillaString = $this->cuadrillaModel->find('1');
        $this->assertNotNull($cuadrillaString);
        $this->assertEquals('Cuadrilla Norte', $cuadrillaString['nombre']);
        
        // Test 7: findAll() - obtener todas las cuadrillas
        $todasLasCuadrillas = $this->cuadrillaModel->findAll();
        $this->assertIsArray($todasLasCuadrillas);
        $this->assertCount(5, $todasLasCuadrillas); // Debe haber 5 cuadrillas de prueba
        
        // Verificar que cada cuadrilla tiene la estructura correcta
        foreach ($todasLasCuadrillas as $cuadrilla) {
            $this->assertArrayHasKey('id', $cuadrilla);
            $this->assertArrayHasKey('nombre', $cuadrilla);
            $this->assertArrayHasKey('descripcion', $cuadrilla);
        }
        
        // Test 8: Verificar cuadrillas específicas en findAll()
        $nombresCuadrillas = array_column($todasLasCuadrillas, 'nombre');
        $this->assertContains('Cuadrilla Norte', $nombresCuadrillas);
        $this->assertContains('Cuadrilla Sur', $nombresCuadrillas);
        $this->assertContains('Cuadrilla Centro', $nombresCuadrillas);
        $this->assertContains('Cuadrilla Emergencias', $nombresCuadrillas);
        $this->assertContains('Cuadrilla Sin Descripción', $nombresCuadrillas);
        
        // Test 9: Verificar que los IDs son únicos
        $ids = array_column($todasLasCuadrillas, 'id');
        $this->assertEquals(5, count(array_unique($ids))); // Debe haber 5 IDs únicos
        
        // Test 10: Verificar que los IDs están en orden creciente
        $idsOrdenados = $ids;
        sort($idsOrdenados);
        $this->assertEquals($idsOrdenados, $ids);
    }

    /**
     * Test: Validar actualización de cuadrillas
     */
    public function testUpdateCuadrilla()
    {
        // Test 1: Actualizar solo el nombre
        $datosActualizacionNombre = [
            'nombre' => 'Cuadrilla Norte Actualizada'
        ];
        
        $resultado = $this->cuadrillaModel->update(1, $datosActualizacionNombre);
        $this->assertTrue($resultado);
        
        // Verificar que solo se actualizó el nombre
        $cuadrillaActualizada = $this->cuadrillaModel->find(1);
        $this->assertEquals('Cuadrilla Norte Actualizada', $cuadrillaActualizada['nombre']);
        $this->assertStringContainsString('zona norte', $cuadrillaActualizada['descripcion']); // No cambió
        
        // Test 2: Actualizar solo la descripción
        $datosActualizacionDescripcion = [
            'descripcion' => 'Nueva descripción para cuadrilla norte'
        ];
        
        $resultado2 = $this->cuadrillaModel->update(1, $datosActualizacionDescripcion);
        $this->assertTrue($resultado2);
        
        // Verificar que solo se actualizó la descripción
        $cuadrillaActualizada2 = $this->cuadrillaModel->find(1);
        $this->assertEquals('Cuadrilla Norte Actualizada', $cuadrillaActualizada2['nombre']); // No cambió
        $this->assertEquals('Nueva descripción para cuadrilla norte', $cuadrillaActualizada2['descripcion']);
        
        // Test 3: Actualización múltiple (ambos campos a la vez)
        $datosActualizacionMultiple = [
            'nombre' => 'Cuadrilla Norte Final',
            'descripcion' => 'Descripción final para cuadrilla norte'
        ];
        
        $resultado3 = $this->cuadrillaModel->update(1, $datosActualizacionMultiple);
        $this->assertTrue($resultado3);
        
        // Verificar que se actualizaron ambos campos
        $cuadrillaActualizada3 = $this->cuadrillaModel->find(1);
        $this->assertEquals('Cuadrilla Norte Final', $cuadrillaActualizada3['nombre']);
        $this->assertEquals('Descripción final para cuadrilla norte', $cuadrillaActualizada3['descripcion']);
        
        // Test 4: Actualizar con ID inexistente
        $datosActualizacionInexistente = [
            'nombre' => 'Cuadrilla Inexistente',
            'descripcion' => 'Esta cuadrilla no existe'
        ];
        
        $resultado4 = $this->cuadrillaModel->update(999, $datosActualizacionInexistente);
        $this->assertTrue($resultado4); // CodeIgniter retorna true aunque no actualice nada
        
        // Verificar que la cuadrilla original no cambió
        $cuadrillaOriginal = $this->cuadrillaModel->find(1);
        $this->assertEquals('Cuadrilla Norte Final', $cuadrillaOriginal['nombre']); // No cambió
        
        // Test 5: Actualizar con campos no permitidos mezclados
        $datosActualizacionMixtos = [
            'nombre' => 'Cuadrilla con Campos Mixtos',
            'descripcion' => 'Descripción con campos mixtos',
            'id' => 888, // Campo no permitido
            'fechaModificacion' => '2024-12-01', // Campo no permitido
            'campoInventado' => 'valorInventado' // Campo no permitido
        ];
        
        $resultado5 = $this->cuadrillaModel->update(1, $datosActualizacionMixtos);
        $this->assertTrue($resultado5);
        
        // Verificar que solo se actualizaron los campos permitidos
        $cuadrillaActualizada5 = $this->cuadrillaModel->find(1);
        $this->assertEquals('Cuadrilla con Campos Mixtos', $cuadrillaActualizada5['nombre']);
        $this->assertEquals('Descripción con campos mixtos', $cuadrillaActualizada5['descripcion']);
        
        // Verificar que los campos no permitidos NO se actualizaron
        $this->assertArrayNotHasKey('fechaModificacion', $cuadrillaActualizada5);
        $this->assertArrayNotHasKey('campoInventado', $cuadrillaActualizada5);
        
        // Verificar que el ID no cambió
        $this->assertEquals(1, $cuadrillaActualizada5['id']);
        $this->assertNotEquals(888, $cuadrillaActualizada5['id']);
        
        // Test 6: Actualizar con descripción null
        $datosActualizacionNull = [
            'descripcion' => null
        ];
        
        $resultado6 = $this->cuadrillaModel->update(2, $datosActualizacionNull);
        $this->assertTrue($resultado6);
        
        // Verificar que la descripción se actualizó a null
        $cuadrillaActualizada6 = $this->cuadrillaModel->find(2);
        $this->assertEquals('Cuadrilla Sur', $cuadrillaActualizada6['nombre']); // No cambió
        $this->assertNull($cuadrillaActualizada6['descripcion']);
    }

    /**
     * Test: Validar inserción con datos faltantes
     */
    public function testInsertWithMissingData()
    {
        // Test 1: Intentar insertar sin nombre (campo requerido)
        $cuadrillaSinNombre = [
            'descripcion' => 'Cuadrilla sin nombre'
        ];
        
        // Esto debería fallar porque nombre es requerido
        $resultado = $this->cuadrillaModel->insert($cuadrillaSinNombre);
        $this->assertFalse($resultado);
        
        // Test 2: Intentar insertar con nombre vacío
        $cuadrillaNombreVacio = [
            'nombre' => '',
            'descripcion' => 'Cuadrilla con nombre vacío'
        ];
        
        // Esto debería fallar porque nombre no puede estar vacío
        $resultado2 = $this->cuadrillaModel->insert($cuadrillaNombreVacio);
        $this->assertFalse($resultado2);
        
        // Test 3: Intentar insertar con nombre null
        $cuadrillaNombreNull = [
            'nombre' => null,
            'descripcion' => 'Cuadrilla con nombre null'
        ];
        
        // Esto debería fallar porque nombre no puede ser null
        $resultado3 = $this->cuadrillaModel->insert($cuadrillaNombreNull);
        $this->assertFalse($resultado3);
        
        // Test 4: Inserción válida con descripción vacía (permitido)
        $cuadrillaDescripcionVacia = [
            'nombre' => 'Cuadrilla Descripción Vacía',
            'descripcion' => ''
        ];
        
        $cuadrillaId = $this->cuadrillaModel->insert($cuadrillaDescripcionVacia);
        $this->assertNotFalse($cuadrillaId);
        
        // Verificar que se insertó correctamente
        $cuadrillaInsertada = $this->cuadrillaModel->find($cuadrillaId);
        $this->assertEquals('Cuadrilla Descripción Vacía', $cuadrillaInsertada['nombre']);
        $this->assertEquals('', $cuadrillaInsertada['descripcion']);
    }

    /**
     * Test: Validar configuración del modelo
     */
    public function testModelConfiguration()
    {
        // Test 1: Verificar configuración de tabla
        $reflection = new \ReflectionClass($this->cuadrillaModel);
        
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $table = $tableProperty->getValue($this->cuadrillaModel);
        $this->assertEquals('cuadrilla', $table);
        
        // Test 2: Verificar configuración de primary key
        $primaryKeyProperty = $reflection->getProperty('primaryKey');
        $primaryKeyProperty->setAccessible(true);
        $primaryKey = $primaryKeyProperty->getValue($this->cuadrillaModel);
        $this->assertEquals('id', $primaryKey);
        
        // Test 3: Verificar configuración de timestamps
        $useTimestampsProperty = $reflection->getProperty('useTimestamps');
        $useTimestampsProperty->setAccessible(true);
        $useTimestamps = $useTimestampsProperty->getValue($this->cuadrillaModel);
        $this->assertFalse($useTimestamps);
        
        // Test 4: Verificar configuración de allowedFields
        $allowedFieldsProperty = $reflection->getProperty('allowedFields');
        $allowedFieldsProperty->setAccessible(true);
        $allowedFields = $allowedFieldsProperty->getValue($this->cuadrillaModel);
        $this->assertEquals(['nombre', 'descripcion'], $allowedFields);
        
        // Test 5: Verificar configuración de validation rules
        $validationRulesProperty = $reflection->getProperty('validationRules');
        $validationRulesProperty->setAccessible(true);
        $validationRules = $validationRulesProperty->getValue($this->cuadrillaModel);
        $this->assertIsArray($validationRules);
        $this->assertEmpty($validationRules);
        
        // Test 6: Verificar configuración de validation messages
        $validationMessagesProperty = $reflection->getProperty('validationMessages');
        $validationMessagesProperty->setAccessible(true);
        $validationMessages = $validationMessagesProperty->getValue($this->cuadrillaModel);
        $this->assertIsArray($validationMessages);
        $this->assertEmpty($validationMessages);
    }
}

