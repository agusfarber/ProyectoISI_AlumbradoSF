<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\DireccionModel;

class DireccionModelTest extends CIUnitTestCase
{
    protected $direccionModel;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Inicializar el modelo
        $this->direccionModel = new DireccionModel();
        
        // Configurar base de datos de prueba
        $this->db = \Config\Database::connect('tests');
        
        // Limpiar tabla antes de cada test
        $this->db->table('direccion')->truncate();
        
        // Insertar datos de prueba
        $this->insertTestData();
    }

    protected function tearDown(): void
    {
        // Limpiar después de cada test
        $this->db->table('direccion')->truncate();
        parent::tearDown();
    }

    /**
     * Inserta datos de prueba en la tabla
     */
    private function insertTestData(): void
    {
        $direccionesData = [
            [
                'domicilio' => 'Av. San Martín',
                'numero_domicilio' => '1234',
                'latitud' => -31.6333,
                'longitud' => -60.7000
            ],
            [
                'domicilio' => 'Calle Mitre',
                'numero_domicilio' => '567',
                'latitud' => -31.6400,
                'longitud' => -60.7100
            ],
            [
                'domicilio' => 'Boulevard Pellegrini',
                'numero_domicilio' => '890',
                'latitud' => -31.6500,
                'longitud' => -60.7200
            ],
            [
                'domicilio' => 'Calle Urquiza',
                'numero_domicilio' => '234',
                'latitud' => -31.6200,
                'longitud' => -60.6800
            ],
            [
                'domicilio' => 'Dirección Sin Coordenadas',
                'numero_domicilio' => '100',
                'latitud' => null,
                'longitud' => null
            ]
        ];

        foreach ($direccionesData as $direccion) {
            $this->db->table('direccion')->insert($direccion);
        }
    }

    /**
     * Test: Validar inserción de direcciones
     */
    public function testInsertDireccion()
    {
        // Test 1: Inserción con todos los campos permitidos
        $direccionCompleta = [
            'domicilio' => 'Av. 9 de Julio',
            'numero_domicilio' => '1000',
            'latitud' => -31.6600,
            'longitud' => -60.7300
        ];
        
        $direccionId = $this->direccionModel->insert($direccionCompleta);
        $this->assertNotFalse($direccionId);
        
        // Verificar que se insertó correctamente
        $direccionInsertada = $this->direccionModel->find($direccionId);
        $this->assertEquals('Av. 9 de Julio', $direccionInsertada['domicilio']);
        $this->assertEquals('1000', $direccionInsertada['numero_domicilio']);
        $this->assertEquals(-31.6600, $direccionInsertada['latitud']);
        $this->assertEquals(-60.7300, $direccionInsertada['longitud']);
        
        // Test 2: Inserción con solo domicilio (campos opcionales)
        $direccionMinima = [
            'domicilio' => 'Calle Mínima'
        ];
        
        $direccionId2 = $this->direccionModel->insert($direccionMinima);
        $this->assertNotFalse($direccionId2);
        
        // Verificar que se insertó correctamente
        $direccionInsertada2 = $this->direccionModel->find($direccionId2);
        $this->assertEquals('Calle Mínima', $direccionInsertada2['domicilio']);
        $this->assertNull($direccionInsertada2['numero_domicilio']);
        $this->assertNull($direccionInsertada2['latitud']);
        $this->assertNull($direccionInsertada2['longitud']);
        
        // Test 3: Inserción con coordenadas específicas
        $direccionConCoordenadas = [
            'domicilio' => 'Calle con Coordenadas',
            'numero_domicilio' => '500',
            'latitud' => -31.5000,
            'longitud' => -60.5000
        ];
        
        $direccionId3 = $this->direccionModel->insert($direccionConCoordenadas);
        $this->assertNotFalse($direccionId3);
        
        // Verificar que se insertaron las coordenadas correctamente
        $direccionInsertada3 = $this->direccionModel->find($direccionId3);
        $this->assertEquals(-31.5000, $direccionInsertada3['latitud']);
        $this->assertEquals(-60.5000, $direccionInsertada3['longitud']);
        
        // Test 4: Inserción con campos no permitidos mezclados
        $direccionConCamposNoPermitidos = [
            'domicilio' => 'Calle con Campo Extra',
            'numero_domicilio' => '999',
            'latitud' => -31.7000,
            'longitud' => -60.8000,
            'id' => 999, // Campo no permitido (primary key)
            'fechaCreacion' => '2024-01-01', // Campo no permitido
            'usuarioModificacion' => 'admin', // Campo no permitido
            'campoInventado' => 'valorInventado' // Campo no permitido
        ];
        
        $direccionId4 = $this->direccionModel->insert($direccionConCamposNoPermitidos);
        $this->assertNotFalse($direccionId4);
        
        // Verificar que solo se insertaron los campos permitidos
        $direccionInsertada4 = $this->direccionModel->find($direccionId4);
        $this->assertEquals('Calle con Campo Extra', $direccionInsertada4['domicilio']);
        $this->assertEquals('999', $direccionInsertada4['numero_domicilio']);
        $this->assertEquals(-31.7000, $direccionInsertada4['latitud']);
        $this->assertEquals(-60.8000, $direccionInsertada4['longitud']);
        
        // Verificar que los campos no permitidos NO se insertaron
        $this->assertArrayNotHasKey('fechaCreacion', $direccionInsertada4);
        $this->assertArrayNotHasKey('usuarioModificacion', $direccionInsertada4);
        $this->assertArrayNotHasKey('campoInventado', $direccionInsertada4);
        
        // Verificar que el ID es el correcto (no el que intentamos forzar)
        $this->assertNotEquals(999, $direccionInsertada4['id']);
        $this->assertEquals($direccionId4, $direccionInsertada4['id']);
        
        // Test 5: Intentar insertar solo con campos no permitidos
        $soloCamposNoPermitidos = [
            'fechaCreacion' => '2024-01-01',
            'usuarioCreacion' => 'admin',
            'campoInventado' => 'valor'
        ];
        
        // Esto debería lanzar excepción
        $this->expectException(\CodeIgniter\Database\Exceptions\DataException::class);
        $this->expectExceptionMessage('There is no data to insert');
        $this->direccionModel->insert($soloCamposNoPermitidos);
    }

    /**
     * Test: Validar campos permitidos en inserción y actualización
     */
    public function testAllowedFieldsValidation()
    {
        // Test 1: Inserción con campos permitidos y no permitidos mezclados
        $direccionConCamposMixtos = [
            'domicilio' => 'Calle Test Campos',
            'numero_domicilio' => '200',
            'latitud' => -31.8000,
            'longitud' => -60.9000,
            'id' => 999, // Campo no permitido (primary key)
            'fechaCreacion' => '2024-01-01', // Campo no permitido
            'usuarioModificacion' => 'admin', // Campo no permitido
            'campoInventado' => 'valorInventado' // Campo no permitido
        ];
        
        $direccionId = $this->direccionModel->insert($direccionConCamposMixtos);
        $this->assertNotFalse($direccionId);
        
        // Verificar que solo se insertaron los campos permitidos
        $direccionInsertada = $this->direccionModel->find($direccionId);
        $this->assertEquals('Calle Test Campos', $direccionInsertada['domicilio']);
        $this->assertEquals('200', $direccionInsertada['numero_domicilio']);
        $this->assertEquals(-31.8000, $direccionInsertada['latitud']);
        $this->assertEquals(-60.9000, $direccionInsertada['longitud']);
        
        // Verificar que los campos no permitidos NO se insertaron
        $this->assertArrayNotHasKey('fechaCreacion', $direccionInsertada);
        $this->assertArrayNotHasKey('usuarioModificacion', $direccionInsertada);
        $this->assertArrayNotHasKey('campoInventado', $direccionInsertada);
        
        // Verificar que el ID es el correcto (no el que intentamos forzar)
        $this->assertNotEquals(999, $direccionInsertada['id']);
        $this->assertEquals($direccionId, $direccionInsertada['id']);
        
        // Test 2: Actualización con campos permitidos y no permitidos
        $datosActualizacion = [
            'domicilio' => 'Calle Actualizada',
            'numero_domicilio' => '300',
            'latitud' => -31.9000,
            'longitud' => -61.0000,
            'id' => 888, // Campo no permitido
            'fechaModificacion' => '2024-12-01', // Campo no permitido
            'campoFalso' => 'valorFalso' // Campo no permitido
        ];
        
        $resultadoUpdate = $this->direccionModel->update($direccionId, $datosActualizacion);
        $this->assertTrue($resultadoUpdate);
        
        // Verificar que solo se actualizaron los campos permitidos
        $direccionActualizada = $this->direccionModel->find($direccionId);
        $this->assertEquals('Calle Actualizada', $direccionActualizada['domicilio']);
        $this->assertEquals('300', $direccionActualizada['numero_domicilio']);
        $this->assertEquals(-31.9000, $direccionActualizada['latitud']);
        $this->assertEquals(-61.0000, $direccionActualizada['longitud']);
        
        // Verificar que los campos no permitidos NO se actualizaron
        $this->assertArrayNotHasKey('fechaModificacion', $direccionActualizada);
        $this->assertArrayNotHasKey('campoFalso', $direccionActualizada);
        
        // Verificar que el ID no cambió (no se puede actualizar)
        $this->assertEquals($direccionId, $direccionActualizada['id']);
        $this->assertNotEquals(888, $direccionActualizada['id']);
        
        // Test 3: Verificar que el modelo respeta la configuración allowedFields
        $reflection = new \ReflectionClass($this->direccionModel);
        $allowedFieldsProperty = $reflection->getProperty('allowedFields');
        $allowedFieldsProperty->setAccessible(true);
        $allowedFields = $allowedFieldsProperty->getValue($this->direccionModel);
        
        $this->assertEquals(['domicilio', 'numero_domicilio', 'latitud', 'longitud'], $allowedFields);
        $this->assertCount(4, $allowedFields);
        $this->assertContains('domicilio', $allowedFields);
        $this->assertContains('numero_domicilio', $allowedFields);
        $this->assertContains('latitud', $allowedFields);
        $this->assertContains('longitud', $allowedFields);
        $this->assertNotContains('id', $allowedFields);
        $this->assertNotContains('fechaCreacion', $allowedFields);
        
        // Test 4: Actualizar con datos vacíos
        $datosVacios = [];
        
        // Esto debería lanzar excepción
        $this->expectException(\CodeIgniter\Database\Exceptions\DataException::class);
        $this->expectExceptionMessage('There is no data to update');
        $this->direccionModel->update($direccionId, $datosVacios);
    }

    /**
     * Test: Validar métodos de búsqueda básica heredados
     */
    public function testBasicSearchMethods()
    {
        // Test 1: find() con ID válido existente (Av. San Martín)
        $direccion = $this->direccionModel->find(1);
        $this->assertNotNull($direccion);
        $this->assertEquals('Av. San Martín', $direccion['domicilio']);
        $this->assertEquals('1234', $direccion['numero_domicilio']);
        $this->assertEquals(-31.6333, $direccion['latitud']);
        $this->assertEquals(-60.7000, $direccion['longitud']);
        
        // Test 2: find() con ID válido existente (Calle Mitre)
        $direccion2 = $this->direccionModel->find(2);
        $this->assertNotNull($direccion2);
        $this->assertEquals('Calle Mitre', $direccion2['domicilio']);
        $this->assertEquals('567', $direccion2['numero_domicilio']);
        $this->assertEquals(-31.6400, $direccion2['latitud']);
        $this->assertEquals(-60.7100, $direccion2['longitud']);
        
        // Test 3: find() con ID inexistente
        $direccionInexistente = $this->direccionModel->find(999);
        $this->assertNull($direccionInexistente);
        
        // Test 4: find() con ID inválido (0)
        $direccionCero = $this->direccionModel->find(0);
        $this->assertNull($direccionCero);
        
        // Test 5: find() con ID inválido (negativo)
        $direccionNegativo = $this->direccionModel->find(-1);
        $this->assertNull($direccionNegativo);
        
        // Test 6: find() con ID como string
        $direccionString = $this->direccionModel->find('1');
        $this->assertNotNull($direccionString);
        $this->assertEquals('Av. San Martín', $direccionString['domicilio']);
        $this->assertEquals('1234', $direccionString['numero_domicilio']);
        
        // Test 7: findAll() - obtener todas las direcciones
        $todasLasDirecciones = $this->direccionModel->findAll();
        $this->assertIsArray($todasLasDirecciones);
        $this->assertCount(5, $todasLasDirecciones); // Debe haber 5 direcciones de prueba
        
        // Verificar que cada dirección tiene la estructura correcta
        foreach ($todasLasDirecciones as $direccion) {
            $this->assertArrayHasKey('id', $direccion);
            $this->assertArrayHasKey('domicilio', $direccion);
            $this->assertArrayHasKey('numero_domicilio', $direccion);
            $this->assertArrayHasKey('latitud', $direccion);
            $this->assertArrayHasKey('longitud', $direccion);
        }
        
        // Test 8: Verificar direcciones específicas en findAll()
        $domicilios = array_column($todasLasDirecciones, 'domicilio');
        $this->assertContains('Av. San Martín', $domicilios);
        $this->assertContains('Calle Mitre', $domicilios);
        $this->assertContains('Boulevard Pellegrini', $domicilios);
        $this->assertContains('Calle Urquiza', $domicilios);
        $this->assertContains('Dirección Sin Coordenadas', $domicilios);
        
        // Test 9: Verificar que los IDs son únicos
        $ids = array_column($todasLasDirecciones, 'id');
        $this->assertEquals(5, count(array_unique($ids))); // Debe haber 5 IDs únicos
        
        // Test 10: Verificar que los IDs están en orden creciente
        $idsOrdenados = $ids;
        sort($idsOrdenados);
        $this->assertEquals($idsOrdenados, $ids);
    }

    /**
     * Test: Validar actualización de direcciones
     */
    public function testUpdateDireccion()
    {
        // Test 1: Actualizar solo el domicilio
        $datosActualizacionDomicilio = [
            'domicilio' => 'Av. San Martín Actualizada'
        ];
        
        $resultado = $this->direccionModel->update(1, $datosActualizacionDomicilio);
        $this->assertTrue($resultado);
        
        // Verificar que solo se actualizó el domicilio
        $direccionActualizada = $this->direccionModel->find(1);
        $this->assertEquals('Av. San Martín Actualizada', $direccionActualizada['domicilio']);
        $this->assertEquals('1234', $direccionActualizada['numero_domicilio']); // No cambió
        $this->assertEquals(-31.6333, $direccionActualizada['latitud']); // No cambió
        $this->assertEquals(-60.7000, $direccionActualizada['longitud']); // No cambió
        
        // Test 2: Actualizar solo el número de domicilio
        $datosActualizacionNumero = [
            'numero_domicilio' => '9999'
        ];
        
        $resultado2 = $this->direccionModel->update(1, $datosActualizacionNumero);
        $this->assertTrue($resultado2);
        
        // Verificar que solo se actualizó el número
        $direccionActualizada2 = $this->direccionModel->find(1);
        $this->assertEquals('Av. San Martín Actualizada', $direccionActualizada2['domicilio']); // No cambió
        $this->assertEquals('9999', $direccionActualizada2['numero_domicilio']); // Sí cambió
        $this->assertEquals(-31.6333, $direccionActualizada2['latitud']); // No cambió
        $this->assertEquals(-60.7000, $direccionActualizada2['longitud']); // No cambió
        
        // Test 3: Actualizar solo las coordenadas
        $datosActualizacionCoordenadas = [
            'latitud' => -31.5000,
            'longitud' => -60.5000
        ];
        
        $resultado3 = $this->direccionModel->update(1, $datosActualizacionCoordenadas);
        $this->assertTrue($resultado3);
        
        // Verificar que solo se actualizaron las coordenadas
        $direccionActualizada3 = $this->direccionModel->find(1);
        $this->assertEquals('Av. San Martín Actualizada', $direccionActualizada3['domicilio']); // No cambió
        $this->assertEquals('9999', $direccionActualizada3['numero_domicilio']); // No cambió
        $this->assertEquals(-31.5000, $direccionActualizada3['latitud']); // Sí cambió
        $this->assertEquals(-60.5000, $direccionActualizada3['longitud']); // Sí cambió
        
        // Test 4: Actualización múltiple (varios campos a la vez)
        $datosActualizacionMultiple = [
            'domicilio' => 'Av. San Martín Final',
            'numero_domicilio' => '5555',
            'latitud' => -31.6000,
            'longitud' => -60.6000
        ];
        
        $resultado4 = $this->direccionModel->update(1, $datosActualizacionMultiple);
        $this->assertTrue($resultado4);
        
        // Verificar que se actualizaron todos los campos
        $direccionActualizada4 = $this->direccionModel->find(1);
        $this->assertEquals('Av. San Martín Final', $direccionActualizada4['domicilio']);
        $this->assertEquals('5555', $direccionActualizada4['numero_domicilio']);
        $this->assertEquals(-31.6000, $direccionActualizada4['latitud']);
        $this->assertEquals(-60.6000, $direccionActualizada4['longitud']);
        
        // Test 5: Actualizar con ID inexistente
        $datosActualizacionInexistente = [
            'domicilio' => 'Dirección Inexistente',
            'numero_domicilio' => '0000'
        ];
        
        $resultado5 = $this->direccionModel->update(999, $datosActualizacionInexistente);
        $this->assertTrue($resultado5); // CodeIgniter retorna true aunque no actualice nada
        
        // Verificar que la dirección original no cambió
        $direccionOriginal = $this->direccionModel->find(1);
        $this->assertEquals('Av. San Martín Final', $direccionOriginal['domicilio']); // No cambió
        
        // Test 6: Actualizar con campos no permitidos mezclados
        $datosActualizacionMixtos = [
            'domicilio' => 'Dirección con Campos Mixtos',
            'numero_domicilio' => '7777',
            'latitud' => -31.7000,
            'longitud' => -60.7000,
            'id' => 888, // Campo no permitido
            'fechaModificacion' => '2024-12-01', // Campo no permitido
            'campoInventado' => 'valorInventado' // Campo no permitido
        ];
        
        $resultado6 = $this->direccionModel->update(1, $datosActualizacionMixtos);
        $this->assertTrue($resultado6);
        
        // Verificar que solo se actualizaron los campos permitidos
        $direccionActualizada6 = $this->direccionModel->find(1);
        $this->assertEquals('Dirección con Campos Mixtos', $direccionActualizada6['domicilio']);
        $this->assertEquals('7777', $direccionActualizada6['numero_domicilio']);
        $this->assertEquals(-31.7000, $direccionActualizada6['latitud']);
        $this->assertEquals(-60.7000, $direccionActualizada6['longitud']);
        
        // Verificar que los campos no permitidos NO se actualizaron
        $this->assertArrayNotHasKey('fechaModificacion', $direccionActualizada6);
        $this->assertArrayNotHasKey('campoInventado', $direccionActualizada6);
        
        // Verificar que el ID no cambió
        $this->assertEquals(1, $direccionActualizada6['id']);
        $this->assertNotEquals(888, $direccionActualizada6['id']);
        
        // Test 7: Actualizar con coordenadas null
        $datosActualizacionNull = [
            'latitud' => null,
            'longitud' => null
        ];
        
        $resultado7 = $this->direccionModel->update(2, $datosActualizacionNull);
        $this->assertTrue($resultado7);
        
        // Verificar que las coordenadas se actualizaron a null
        $direccionActualizada7 = $this->direccionModel->find(2);
        $this->assertEquals('Calle Mitre', $direccionActualizada7['domicilio']); // No cambió
        $this->assertEquals('567', $direccionActualizada7['numero_domicilio']); // No cambió
        $this->assertNull($direccionActualizada7['latitud']);
        $this->assertNull($direccionActualizada7['longitud']);
    }

    /**
     * Test: Validar inserción con datos faltantes
     */
    public function testInsertWithMissingData()
    {
        // Test 1: Intentar insertar sin domicilio (campo requerido)
        $direccionSinDomicilio = [
            'numero_domicilio' => '123',
            'latitud' => -31.6000,
            'longitud' => -60.6000
        ];
        
        // Esto debería fallar porque domicilio es requerido
        $resultado = $this->direccionModel->insert($direccionSinDomicilio);
        $this->assertFalse($resultado);
        
        // Test 2: Intentar insertar con domicilio vacío
        $direccionDomicilioVacio = [
            'domicilio' => '',
            'numero_domicilio' => '456',
            'latitud' => -31.6000,
            'longitud' => -60.6000
        ];
        
        // Esto debería fallar porque domicilio no puede estar vacío
        $resultado2 = $this->direccionModel->insert($direccionDomicilioVacio);
        $this->assertFalse($resultado2);
        
        // Test 3: Intentar insertar con domicilio null
        $direccionDomicilioNull = [
            'domicilio' => null,
            'numero_domicilio' => '789',
            'latitud' => -31.6000,
            'longitud' => -60.6000
        ];
        
        // Esto debería fallar porque domicilio no puede ser null
        $resultado3 = $this->direccionModel->insert($direccionDomicilioNull);
        $this->assertFalse($resultado3);
        
        // Test 4: Inserción válida con campos opcionales vacíos (permitido)
        $direccionCamposOpcionalesVacios = [
            'domicilio' => 'Calle Campos Opcionales Vacíos',
            'numero_domicilio' => '',
            'latitud' => null,
            'longitud' => null
        ];
        
        $direccionId = $this->direccionModel->insert($direccionCamposOpcionalesVacios);
        $this->assertNotFalse($direccionId);
        
        // Verificar que se insertó correctamente
        $direccionInsertada = $this->direccionModel->find($direccionId);
        $this->assertEquals('Calle Campos Opcionales Vacíos', $direccionInsertada['domicilio']);
        $this->assertEquals('', $direccionInsertada['numero_domicilio']);
        $this->assertNull($direccionInsertada['latitud']);
        $this->assertNull($direccionInsertada['longitud']);
    }

    /**
     * Test: Validar configuración del modelo
     */
    public function testModelConfiguration()
    {
        // Test 1: Verificar configuración de tabla
        $reflection = new \ReflectionClass($this->direccionModel);
        
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $table = $tableProperty->getValue($this->direccionModel);
        $this->assertEquals('direccion', $table);
        
        // Test 2: Verificar configuración de primary key
        $primaryKeyProperty = $reflection->getProperty('primaryKey');
        $primaryKeyProperty->setAccessible(true);
        $primaryKey = $primaryKeyProperty->getValue($this->direccionModel);
        $this->assertEquals('id', $primaryKey);
        
        // Test 3: Verificar configuración de auto increment
        $useAutoIncrementProperty = $reflection->getProperty('useAutoIncrement');
        $useAutoIncrementProperty->setAccessible(true);
        $useAutoIncrement = $useAutoIncrementProperty->getValue($this->direccionModel);
        $this->assertTrue($useAutoIncrement);
        
        // Test 4: Verificar configuración de allowedFields
        $allowedFieldsProperty = $reflection->getProperty('allowedFields');
        $allowedFieldsProperty->setAccessible(true);
        $allowedFields = $allowedFieldsProperty->getValue($this->direccionModel);
        $this->assertEquals(['domicilio', 'numero_domicilio', 'latitud', 'longitud'], $allowedFields);
        
        // Test 5: Verificar que contiene todos los campos esperados
        $this->assertCount(4, $allowedFields);
        $this->assertContains('domicilio', $allowedFields);
        $this->assertContains('numero_domicilio', $allowedFields);
        $this->assertContains('latitud', $allowedFields);
        $this->assertContains('longitud', $allowedFields);
        $this->assertNotContains('id', $allowedFields);
        $this->assertNotContains('fechaCreacion', $allowedFields);
    }

    /**
     * Test: Validar coordenadas geográficas
     */
    public function testGeographicCoordinates()
    {
        // Test 1: Coordenadas válidas de San Francisco (Santa Fe)
        $direccionSanFrancisco = [
            'domicilio' => 'Plaza Principal',
            'numero_domicilio' => '1',
            'latitud' => -31.4280,
            'longitud' => -62.0826
        ];
        
        $direccionId = $this->direccionModel->insert($direccionSanFrancisco);
        $this->assertNotFalse($direccionId);
        
        // Verificar que las coordenadas se guardaron correctamente
        $direccionInsertada = $this->direccionModel->find($direccionId);
        $this->assertEquals(-31.4280, $direccionInsertada['latitud']);
        $this->assertEquals(-62.0826, $direccionInsertada['longitud']);
        
        // Test 2: Coordenadas extremas (límites del mundo)
        $direccionCoordenadasExtremas = [
            'domicilio' => 'Coordenadas Extremas',
            'numero_domicilio' => '999',
            'latitud' => 90.00000000, // Polo Norte
            'longitud' => 180.00000000 // Límite este
        ];
        
        $direccionId2 = $this->direccionModel->insert($direccionCoordenadasExtremas);
        $this->assertNotFalse($direccionId2);
        
        // Verificar que las coordenadas extremas se guardaron
        $direccionInsertada2 = $this->direccionModel->find($direccionId2);
        $this->assertEquals(90.00000000, $direccionInsertada2['latitud']);
        $this->assertEquals(180.00000000, $direccionInsertada2['longitud']);
        
        // Test 3: Coordenadas negativas extremas
        $direccionCoordenadasNegativas = [
            'domicilio' => 'Coordenadas Negativas',
            'numero_domicilio' => '888',
            'latitud' => -90.00000000, // Polo Sur
            'longitud' => -180.00000000 // Límite oeste
        ];
        
        $direccionId3 = $this->direccionModel->insert($direccionCoordenadasNegativas);
        $this->assertNotFalse($direccionId3);
        
        // Verificar que las coordenadas negativas se guardaron
        $direccionInsertada3 = $this->direccionModel->find($direccionId3);
        $this->assertEquals(-90.00000000, $direccionInsertada3['latitud']);
        $this->assertEquals(-180.00000000, $direccionInsertada3['longitud']);
        
        // Test 4: Actualizar coordenadas existentes
        $datosActualizacionCoordenadas = [
            'latitud' => -31.5000,
            'longitud' => -62.1000
        ];
        
        $resultado = $this->direccionModel->update($direccionId, $datosActualizacionCoordenadas);
        $this->assertTrue($resultado);
        
        // Verificar que las coordenadas se actualizaron
        $direccionActualizada = $this->direccionModel->find($direccionId);
        $this->assertEquals(-31.5000, $direccionActualizada['latitud']);
        $this->assertEquals(-62.1000, $direccionActualizada['longitud']);
    }
}
