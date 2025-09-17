<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\MaterialModel;

class MaterialModelTest extends CIUnitTestCase
{
    protected $materialModel;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Inicializar el modelo
        $this->materialModel = new MaterialModel();
        
        // Configurar base de datos de prueba
        $this->db = \Config\Database::connect('tests');
        
        // Limpiar tablas antes de cada test
        $this->db->table('material')->truncate();
        $this->db->table('tipo_material')->truncate();
        
        // Insertar datos de prueba
        $this->insertTestData();
    }

    protected function tearDown(): void
    {
        // Limpiar después de cada test
        $this->db->table('material')->truncate();
        $this->db->table('tipo_material')->truncate();
        parent::tearDown();
    }

    /**
     * Inserta datos de prueba en las tablas
     */
    private function insertTestData(): void
    {
        // Insertar tipos de materiales
        $tiposData = [
            [
                'id' => 1,
                'nombre' => 'Lámparas'
            ],
            [
                'id' => 2,
                'nombre' => 'Cables'
            ],
            [
                'id' => 3,
                'nombre' => 'Transformadores'
            ]
        ];

        foreach ($tiposData as $tipo) {
            $this->db->table('tipo_material')->insert($tipo);
        }

        // Insertar materiales
        $materialesData = [
            [
                'nombre' => 'Lámpara LED 50W',
                'idTipo' => 1,
                'cantidad' => 25
            ],
            [
                'nombre' => 'Cable de Cobre 2.5mm',
                'idTipo' => 2,
                'cantidad' => 100
            ],
            [
                'nombre' => 'Transformador 220V/12V',
                'idTipo' => 3,
                'cantidad' => 15
            ],
            [
                'nombre' => 'Lámpara Halógena 100W',
                'idTipo' => 1,
                'cantidad' => 30
            ],
            [
                'nombre' => 'Material Sin Tipo',
                'idTipo' => null, // Para probar LEFT JOIN
                'cantidad' => 5
            ]
        ];

        foreach ($materialesData as $material) {
            $this->db->table('material')->insert($material);
        }
    }

    /**
     * Test: Validar método findAllWithTipo()
     */
    public function testFindAllWithTipo()
    {
        $materiales = $this->materialModel->findAllWithTipo();
        
        // Verificar que retorna un array
        $this->assertIsArray($materiales);
        
        // Verificar que hay materiales
        $this->assertCount(5, $materiales);
        
        // Verificar que cada material tiene el campo tipo_nombre
        foreach ($materiales as $material) {
            $this->assertArrayHasKey('tipo_nombre', $material);
            $this->assertArrayHasKey('nombre', $material);
            $this->assertArrayHasKey('idTipo', $material);
            $this->assertArrayHasKey('cantidad', $material);
        }
        
        // Verificar materiales específicos con tipo asociado
        $lamparaLED = null;
        $cableCobre = null;
        $materialSinTipo = null;
        
        foreach ($materiales as $material) {
            if ($material['nombre'] === 'Lámpara LED 50W') {
                $lamparaLED = $material;
            } elseif ($material['nombre'] === 'Cable de Cobre 2.5mm') {
                $cableCobre = $material;
            } elseif ($material['nombre'] === 'Material Sin Tipo') {
                $materialSinTipo = $material;
            }
        }
        
        // Verificar material con tipo asociado
        $this->assertNotNull($lamparaLED);
        $this->assertEquals('Lámparas', $lamparaLED['tipo_nombre']);
        $this->assertEquals(1, $lamparaLED['idTipo']);
        $this->assertEquals(25, $lamparaLED['cantidad']);
        
        // Verificar otro material con tipo asociado
        $this->assertNotNull($cableCobre);
        $this->assertEquals('Cables', $cableCobre['tipo_nombre']);
        $this->assertEquals(2, $cableCobre['idTipo']);
        $this->assertEquals(100, $cableCobre['cantidad']);
        
        // Verificar material sin tipo (LEFT JOIN)
        $this->assertNotNull($materialSinTipo);
        $this->assertNull($materialSinTipo['tipo_nombre']); // Debe ser null por LEFT JOIN
        $this->assertNull($materialSinTipo['idTipo']);
        $this->assertEquals(5, $materialSinTipo['cantidad']);
    }

    /**
     * Test: Validar inserción de materiales
     */
    public function testInsertMaterial()
    {
        // Test 1: Inserción con todos los campos permitidos
        $materialCompleto = [
            'nombre' => 'Nueva Lámpara LED',
            'idTipo' => 1,
            'cantidad' => 50
        ];
        
        $materialId = $this->materialModel->insert($materialCompleto);
        $this->assertNotFalse($materialId);
        
        // Verificar que se insertó correctamente
        $materialInsertado = $this->materialModel->find($materialId);
        $this->assertEquals('Nueva Lámpara LED', $materialInsertado['nombre']);
        $this->assertEquals(1, $materialInsertado['idTipo']);
        $this->assertEquals(50, $materialInsertado['cantidad']);
        
        // Test 2: Inserción con idTipo null (campo opcional)
        $materialSinTipo = [
            'nombre' => 'Material Sin Tipo',
            'idTipo' => null,
            'cantidad' => 25
        ];
        
        $materialId2 = $this->materialModel->insert($materialSinTipo);
        $this->assertNotFalse($materialId2);
        
        // Verificar que se insertó correctamente
        $materialInsertado2 = $this->materialModel->find($materialId2);
        $this->assertEquals('Material Sin Tipo', $materialInsertado2['nombre']);
        $this->assertNull($materialInsertado2['idTipo']);
        $this->assertEquals(25, $materialInsertado2['cantidad']);
        
        // Test 3: Inserción con solo campos obligatorios (sin idTipo)
        $materialMinimo = [
            'nombre' => 'Material Mínimo',
            'cantidad' => 10
        ];
        
        $materialId3 = $this->materialModel->insert($materialMinimo);
        $this->assertNotFalse($materialId3);
        
        // Verificar que se insertó correctamente
        $materialInsertado3 = $this->materialModel->find($materialId3);
        $this->assertEquals('Material Mínimo', $materialInsertado3['nombre']);
        $this->assertEquals(10, $materialInsertado3['cantidad']);
        
        // Test 4: Intentar insertar con campos no permitidos
        $materialConCamposNoPermitidos = [
            'nombre' => 'Material con Campo Extra',
            'idTipo' => 1,
            'cantidad' => 15,
            'campoNoPermitido' => 'valor' // Este campo no está en allowedFields
        ];
        
        // CodeIgniter debería ignorar campos no permitidos
        $materialId4 = $this->materialModel->insert($materialConCamposNoPermitidos);
        $this->assertNotFalse($materialId4);
        
        // Verificar que se insertó sin el campo no permitido
        $materialInsertado4 = $this->materialModel->find($materialId4);
        $this->assertEquals('Material con Campo Extra', $materialInsertado4['nombre']);
        $this->assertEquals(1, $materialInsertado4['idTipo']);
        $this->assertEquals(15, $materialInsertado4['cantidad']);
        $this->assertArrayNotHasKey('campoNoPermitido', $materialInsertado4);
        
        // Test 5: Intentar insertar con datos faltantes (nombre vacío)
        $materialSinNombre = [
            'nombre' => '',
            'cantidad' => 5
        ];
        
        // Esto debería fallar porque nombre es NOT NULL
        $resultado = $this->materialModel->insert($materialSinNombre);
        $this->assertFalse($resultado);
        
        // Test 6: Intentar insertar con cantidad faltante
        $materialSinCantidad = [
            'nombre' => 'Material Sin Cantidad'
        ];
        
        // Esto debería fallar porque cantidad es NOT NULL
        $resultado2 = $this->materialModel->insert($materialSinCantidad);
        $this->assertFalse($resultado2);
    }

    /**
     * Test: Validar campos permitidos en inserción y actualización
     */
    public function testAllowedFieldsValidation()
    {
        // Test 1: Inserción con campos permitidos y no permitidos mezclados
        $materialConCamposMixtos = [
            'nombre' => 'Material Test Campos',
            'idTipo' => 1,
            'cantidad' => 20,
            'id' => 999, // Campo no permitido (primary key)
            'fechaCreacion' => '2024-01-01', // Campo no permitido
            'usuarioModificacion' => 'admin', // Campo no permitido
            'campoInventado' => 'valorInventado' // Campo no permitido
        ];
        
        $materialId = $this->materialModel->insert($materialConCamposMixtos);
        $this->assertNotFalse($materialId);
        
        // Verificar que solo se insertaron los campos permitidos
        $materialInsertado = $this->materialModel->find($materialId);
        $this->assertEquals('Material Test Campos', $materialInsertado['nombre']);
        $this->assertEquals(1, $materialInsertado['idTipo']);
        $this->assertEquals(20, $materialInsertado['cantidad']);
        
        // Verificar que los campos no permitidos NO se insertaron
        $this->assertArrayNotHasKey('fechaCreacion', $materialInsertado);
        $this->assertArrayNotHasKey('usuarioModificacion', $materialInsertado);
        $this->assertArrayNotHasKey('campoInventado', $materialInsertado);
        
        // Verificar que el ID es el correcto (no el que intentamos forzar)
        $this->assertNotEquals(999, $materialInsertado['id']);
        $this->assertEquals($materialId, $materialInsertado['id']);
        
        // Test 2: Actualización con campos permitidos y no permitidos
        $datosActualizacion = [
            'nombre' => 'Material Actualizado',
            'cantidad' => 35,
            'id' => 888, // Campo no permitido
            'fechaModificacion' => '2024-12-01', // Campo no permitido
            'campoFalso' => 'valorFalso' // Campo no permitido
        ];
        
        $resultadoUpdate = $this->materialModel->update($materialId, $datosActualizacion);
        $this->assertTrue($resultadoUpdate);
        
        // Verificar que solo se actualizaron los campos permitidos
        $materialActualizado = $this->materialModel->find($materialId);
        $this->assertEquals('Material Actualizado', $materialActualizado['nombre']);
        $this->assertEquals(35, $materialActualizado['cantidad']);
        $this->assertEquals(1, $materialActualizado['idTipo']); // Debe mantenerse igual
        
        // Verificar que los campos no permitidos NO se actualizaron
        $this->assertArrayNotHasKey('fechaModificacion', $materialActualizado);
        $this->assertArrayNotHasKey('campoFalso', $materialActualizado);
        
        // Verificar que el ID no cambió (no se puede actualizar)
        $this->assertEquals($materialId, $materialActualizado['id']);
        $this->assertNotEquals(888, $materialActualizado['id']);
        
        // Test 3: Intentar insertar solo con campos no permitidos
        $soloCamposNoPermitidos = [
            'fechaCreacion' => '2024-01-01',
            'usuarioCreacion' => 'admin',
            'campoInventado' => 'valor'
        ];
        
        // Esto debería fallar porque no hay campos permitidos
        $resultadoSoloNoPermitidos = $this->materialModel->insert($soloCamposNoPermitidos);
        $this->assertFalse($resultadoSoloNoPermitidos);
        
        // Test 4: Verificar que el modelo respeta la configuración allowedFields
        $reflection = new \ReflectionClass($this->materialModel);
        $allowedFieldsProperty = $reflection->getProperty('allowedFields');
        $allowedFieldsProperty->setAccessible(true);
        $allowedFields = $allowedFieldsProperty->getValue($this->materialModel);
        
        $this->assertEquals(['nombre', 'idTipo', 'cantidad'], $allowedFields);
        $this->assertCount(3, $allowedFields);
        $this->assertContains('nombre', $allowedFields);
        $this->assertContains('idTipo', $allowedFields);
        $this->assertContains('cantidad', $allowedFields);
        $this->assertNotContains('id', $allowedFields);
        $this->assertNotContains('fechaCreacion', $allowedFields);
    }

    /**
     * Test: Validar campos permitidos - Comportamiento real de CodeIgniter
     * Este test corrige las expectativas del test anterior
     */
    public function testAllowedFieldsValidationCorrected()
    {
        // Test 1: Inserción con campos permitidos y no permitidos mezclados
        $materialConCamposMixtos = [
            'nombre' => 'Material Test Campos Corregido',
            'idTipo' => 2,
            'cantidad' => 25,
            'id' => 999, // Campo no permitido (primary key)
            'fechaCreacion' => '2024-01-01', // Campo no permitido
            'usuarioModificacion' => 'admin', // Campo no permitido
            'campoInventado' => 'valorInventado' // Campo no permitido
        ];
        
        $materialId = $this->materialModel->insert($materialConCamposMixtos);
        $this->assertNotFalse($materialId);
        
        // Verificar que solo se insertaron los campos permitidos
        $materialInsertado = $this->materialModel->find($materialId);
        $this->assertEquals('Material Test Campos Corregido', $materialInsertado['nombre']);
        $this->assertEquals(2, $materialInsertado['idTipo']);
        $this->assertEquals(25, $materialInsertado['cantidad']);
        
        // Verificar que los campos no permitidos NO se insertaron
        $this->assertArrayNotHasKey('fechaCreacion', $materialInsertado);
        $this->assertArrayNotHasKey('usuarioModificacion', $materialInsertado);
        $this->assertArrayNotHasKey('campoInventado', $materialInsertado);
        
        // Verificar que el ID es el correcto (no el que intentamos forzar)
        $this->assertNotEquals(999, $materialInsertado['id']);
        $this->assertEquals($materialId, $materialInsertado['id']);
        
        // Test 2: Actualización con campos permitidos y no permitidos
        $datosActualizacion = [
            'nombre' => 'Material Actualizado Corregido',
            'cantidad' => 40,
            'id' => 888, // Campo no permitido
            'fechaModificacion' => '2024-12-01', // Campo no permitido
            'campoFalso' => 'valorFalso' // Campo no permitido
        ];
        
        $resultadoUpdate = $this->materialModel->update($materialId, $datosActualizacion);
        $this->assertTrue($resultadoUpdate);
        
        // Verificar que solo se actualizaron los campos permitidos
        $materialActualizado = $this->materialModel->find($materialId);
        $this->assertEquals('Material Actualizado Corregido', $materialActualizado['nombre']);
        $this->assertEquals(40, $materialActualizado['cantidad']);
        $this->assertEquals(2, $materialActualizado['idTipo']); // Debe mantenerse igual
        
        // Verificar que los campos no permitidos NO se actualizaron
        $this->assertArrayNotHasKey('fechaModificacion', $materialActualizado);
        $this->assertArrayNotHasKey('campoFalso', $materialActualizado);
        
        // Verificar que el ID no cambió (no se puede actualizar)
        $this->assertEquals($materialId, $materialActualizado['id']);
        $this->assertNotEquals(888, $materialActualizado['id']);
        
        // Test 3: Intentar insertar solo con campos no permitidos - CORREGIDO
        $soloCamposNoPermitidos = [
            'fechaCreacion' => '2024-01-01',
            'usuarioCreacion' => 'admin',
            'campoInventado' => 'valor'
        ];
        
        // CORREGIDO: Esperar excepción en lugar de false
        $this->expectException(\CodeIgniter\Database\Exceptions\DataException::class);
        $this->expectExceptionMessage('There is no data to insert');
        $this->materialModel->insert($soloCamposNoPermitidos);
        
        // Test 4: Verificar que el modelo respeta la configuración allowedFields
        $reflection = new \ReflectionClass($this->materialModel);
        $allowedFieldsProperty = $reflection->getProperty('allowedFields');
        $allowedFieldsProperty->setAccessible(true);
        $allowedFields = $allowedFieldsProperty->getValue($this->materialModel);
        
        $this->assertEquals(['nombre', 'idTipo', 'cantidad'], $allowedFields);
        $this->assertCount(3, $allowedFields);
        $this->assertContains('nombre', $allowedFields);
        $this->assertContains('idTipo', $allowedFields);
        $this->assertContains('cantidad', $allowedFields);
        $this->assertNotContains('id', $allowedFields);
        $this->assertNotContains('fechaCreacion', $allowedFields);
    }

    /**
     * Test: Validar métodos de búsqueda básica heredados
     */
    public function testBasicSearchMethods()
    {
        // Test 1: find() con ID válido existente (Lámpara LED 50W)
        $material = $this->materialModel->find(1);
        $this->assertNotNull($material);
        $this->assertEquals('Lámpara LED 50W', $material['nombre']);
        $this->assertEquals(1, $material['idTipo']);
        $this->assertEquals(25, $material['cantidad']);
        
        // Test 2: find() con ID válido existente (Cable de Cobre 2.5mm)
        $material2 = $this->materialModel->find(2);
        $this->assertNotNull($material2);
        $this->assertEquals('Cable de Cobre 2.5mm', $material2['nombre']);
        $this->assertEquals(2, $material2['idTipo']);
        $this->assertEquals(100, $material2['cantidad']);
        
        // Test 3: find() con ID inexistente
        $materialInexistente = $this->materialModel->find(999);
        $this->assertNull($materialInexistente);
        
        // Test 4: find() con ID inválido (0)
        $materialCero = $this->materialModel->find(0);
        $this->assertNull($materialCero);
        
        // Test 5: find() con ID inválido (negativo)
        $materialNegativo = $this->materialModel->find(-1);
        $this->assertNull($materialNegativo);
        
        // Test 6: find() con ID como string
        $materialString = $this->materialModel->find('1');
        $this->assertNotNull($materialString);
        $this->assertEquals('Lámpara LED 50W', $materialString['nombre']);
        $this->assertEquals(1, $materialString['idTipo']);
        $this->assertEquals(25, $materialString['cantidad']);
        
        // Test 7: findAll() - obtener todos los materiales
        $todosLosMateriales = $this->materialModel->findAll();
        $this->assertIsArray($todosLosMateriales);
        $this->assertCount(5, $todosLosMateriales); // Debe haber 5 materiales de prueba
        
        // Verificar que cada material tiene la estructura correcta
        foreach ($todosLosMateriales as $material) {
            $this->assertArrayHasKey('id', $material);
            $this->assertArrayHasKey('nombre', $material);
            $this->assertArrayHasKey('idTipo', $material);
            $this->assertArrayHasKey('cantidad', $material);
        }
        
        // Test 8: Verificar materiales específicos en findAll()
        $nombresMateriales = array_column($todosLosMateriales, 'nombre');
        $this->assertContains('Lámpara LED 50W', $nombresMateriales);
        $this->assertContains('Cable de Cobre 2.5mm', $nombresMateriales);
        $this->assertContains('Transformador 220V/12V', $nombresMateriales);
        $this->assertContains('Lámpara Halógena 100W', $nombresMateriales);
        $this->assertContains('Material Sin Tipo', $nombresMateriales);
        
        // Test 9: Verificar que los IDs son únicos
        $ids = array_column($todosLosMateriales, 'id');
        $this->assertEquals(5, count(array_unique($ids))); // Debe haber 5 IDs únicos
        
        // Test 10: Verificar que los IDs están en orden creciente
        $idsOrdenados = $ids;
        sort($idsOrdenados);
        $this->assertEquals($idsOrdenados, $ids);
    }

    /**
     * Test: Validar actualización de materiales
     */
    public function testUpdateMaterial()
    {
        // Test 1: Actualizar solo la cantidad
        $datosActualizacionCantidad = [
            'cantidad' => 50
        ];
        
        $resultado = $this->materialModel->update(1, $datosActualizacionCantidad);
        $this->assertTrue($resultado);
        
        // Verificar que solo se actualizó la cantidad
        $materialActualizado = $this->materialModel->find(1);
        $this->assertEquals('Lámpara LED 50W', $materialActualizado['nombre']); // No cambió
        $this->assertEquals(1, $materialActualizado['idTipo']); // No cambió
        $this->assertEquals(50, $materialActualizado['cantidad']); // Sí cambió
        
        // Test 2: Actualizar solo el nombre
        $datosActualizacionNombre = [
            'nombre' => 'Lámpara LED 50W Actualizada'
        ];
        
        $resultado2 = $this->materialModel->update(1, $datosActualizacionNombre);
        $this->assertTrue($resultado2);
        
        // Verificar que solo se actualizó el nombre
        $materialActualizado2 = $this->materialModel->find(1);
        $this->assertEquals('Lámpara LED 50W Actualizada', $materialActualizado2['nombre']); // Sí cambió
        $this->assertEquals(1, $materialActualizado2['idTipo']); // No cambió
        $this->assertEquals(50, $materialActualizado2['cantidad']); // No cambió
        
        // Test 3: Actualizar solo el tipo
        $datosActualizacionTipo = [
            'idTipo' => 2
        ];
        
        $resultado3 = $this->materialModel->update(1, $datosActualizacionTipo);
        $this->assertTrue($resultado3);
        
        // Verificar que solo se actualizó el tipo
        $materialActualizado3 = $this->materialModel->find(1);
        $this->assertEquals('Lámpara LED 50W Actualizada', $materialActualizado3['nombre']); // No cambió
        $this->assertEquals(2, $materialActualizado3['idTipo']); // Sí cambió
        $this->assertEquals(50, $materialActualizado3['cantidad']); // No cambió
        
        // Test 4: Actualización múltiple (varios campos a la vez)
        $datosActualizacionMultiple = [
            'nombre' => 'Lámpara LED 50W Final',
            'idTipo' => 3,
            'cantidad' => 75
        ];
        
        $resultado4 = $this->materialModel->update(1, $datosActualizacionMultiple);
        $this->assertTrue($resultado4);
        
        // Verificar que se actualizaron todos los campos
        $materialActualizado4 = $this->materialModel->find(1);
        $this->assertEquals('Lámpara LED 50W Final', $materialActualizado4['nombre']);
        $this->assertEquals(3, $materialActualizado4['idTipo']);
        $this->assertEquals(75, $materialActualizado4['cantidad']);
        
        // Test 5: Actualizar con ID inexistente
        $datosActualizacionInexistente = [
            'nombre' => 'Material Inexistente',
            'cantidad' => 100
        ];
        
        $resultado5 = $this->materialModel->update(999, $datosActualizacionInexistente);
        $this->assertTrue($resultado5); // CodeIgniter retorna true aunque no actualice nada
        
        // Verificar que el material original no cambió
        $materialOriginal = $this->materialModel->find(1);
        $this->assertEquals('Lámpara LED 50W Final', $materialOriginal['nombre']); // No cambió
        
        // Test 6: Actualizar con campos no permitidos mezclados
        $datosActualizacionMixtos = [
            'nombre' => 'Material con Campos Mixtos',
            'cantidad' => 30,
            'id' => 888, // Campo no permitido
            'fechaModificacion' => '2024-12-01', // Campo no permitido
            'campoInventado' => 'valorInventado' // Campo no permitido
        ];
        
        $resultado6 = $this->materialModel->update(1, $datosActualizacionMixtos);
        $this->assertTrue($resultado6);
        
        // Verificar que solo se actualizaron los campos permitidos
        $materialActualizado6 = $this->materialModel->find(1);
        $this->assertEquals('Material con Campos Mixtos', $materialActualizado6['nombre']);
        $this->assertEquals(30, $materialActualizado6['cantidad']);
        $this->assertEquals(3, $materialActualizado6['idTipo']); // No cambió
        
        // Verificar que los campos no permitidos NO se actualizaron
        $this->assertArrayNotHasKey('fechaModificacion', $materialActualizado6);
        $this->assertArrayNotHasKey('campoInventado', $materialActualizado6);
        
        // Verificar que el ID no cambió
        $this->assertEquals(1, $materialActualizado6['id']);
        $this->assertNotEquals(888, $materialActualizado6['id']);
        
        // Test 7: Actualizar con datos vacíos
        $datosVacios = [];
        
        // Esto debería lanzar excepción según el comportamiento que vimos antes
        $this->expectException(\CodeIgniter\Database\Exceptions\DataException::class);
        $this->expectExceptionMessage('There is no data to update');
        $this->materialModel->update(1, $datosVacios);
    }
}
