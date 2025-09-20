<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\ReclamoModel;

class ReclamoModelTest extends CIUnitTestCase
{
    protected $reclamoModel;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Inicializar el modelo
        $this->reclamoModel = new ReclamoModel();
        
        // Configurar base de datos de prueba
        $this->db = \Config\Database::connect('tests');
        
        // Limpiar tabla antes de cada test
        $this->db->table('reclamo')->truncate();
        
        // Insertar datos de prueba
        $this->insertTestData();
    }

    protected function tearDown(): void
    {
        // Limpiar después de cada test
        $this->db->table('reclamo')->truncate();
        parent::tearDown();
    }

    /**
     * Inserta datos de prueba en la tabla
     */
    private function insertTestData(): void
    {
        $reclamosData = [
            [
                'municipalidad_id' => 'REC001',
                'municipalidad_tipo' => 'Alumbrado Público',
                'municipalidad_motivo' => 'Lámpara fundida',
                'municipalidad_fechaInicio' => '2024-01-15 10:30:00',
                'municipalidad_fechaModificacion' => '2024-01-15 10:30:00',
                'municipalidad_recepcion' => 'Teléfono',
                'municipalidad_estado' => 'Pendiente',
                'municipalidad_telefono' => '1234567890',
                'municipalidad_domicilio' => 'Av. San Martín',
                'municipalidad_numeroDomicilio' => '1234',
                'municipalidad_entreCalleUno' => 'Av. Corrientes',
                'municipalidad_entreCalleDos' => 'Av. Rivadavia',
                'municipalidad_ciudadano' => 'Juan Pérez',
                'municipalidad_descripcion' => 'Lámpara LED fundida en esquina',
                'prioridad' => 'Media'
            ],
            [
                'municipalidad_id' => 'REC002',
                'municipalidad_tipo' => 'Alumbrado Público',
                'municipalidad_motivo' => 'Cable cortado',
                'municipalidad_fechaInicio' => '2024-01-16 14:20:00',
                'municipalidad_fechaModificacion' => '2024-01-16 14:20:00',
                'municipalidad_recepcion' => 'Web',
                'municipalidad_estado' => 'En Proceso',
                'municipalidad_telefono' => '0987654321',
                'municipalidad_domicilio' => 'Calle Mitre',
                'municipalidad_numeroDomicilio' => '567',
                'municipalidad_entreCalleUno' => 'Calle Sarmiento',
                'municipalidad_entreCalleDos' => 'Calle Belgrano',
                'municipalidad_ciudadano' => 'María García',
                'municipalidad_descripcion' => 'Cable de alimentación cortado por vehículo',
                'prioridad' => 'Alta'
            ]
        ];

        foreach ($reclamosData as $reclamo) {
            $this->db->table('reclamo')->insert($reclamo);
        }
    }

    /**
     * Test: Validar inserción de reclamos
     */
    public function testInsertReclamo()
    {
        // Test 1: Inserción con todos los campos permitidos
        $reclamoCompleto = [
            'municipalidad_id' => 'REC003',
            'municipalidad_tipo' => 'Alumbrado Público',
            'municipalidad_motivo' => 'Transformador dañado',
            'municipalidad_fechaInicio' => '2024-01-17 09:15:00',
            'municipalidad_fechaModificacion' => '2024-01-17 09:15:00',
            'municipalidad_recepcion' => 'Presencial',
            'municipalidad_estado' => 'Nuevo',
            'municipalidad_telefono' => '5555555555',
            'municipalidad_domicilio' => 'Av. 9 de Julio',
            'municipalidad_numeroDomicilio' => '1000',
            'municipalidad_entreCalleUno' => 'Av. Córdoba',
            'municipalidad_entreCalleDos' => 'Av. Santa Fe',
            'municipalidad_ciudadano' => 'Carlos López',
            'municipalidad_descripcion' => 'Transformador con falla en el sector norte',
            'prioridad' => 'Crítica'
        ];
        
        $reclamoId = $this->reclamoModel->insert($reclamoCompleto);
        $this->assertNotFalse($reclamoId);
        
        // Verificar que se insertó correctamente
        $reclamoInsertado = $this->reclamoModel->find($reclamoId);
        $this->assertEquals('REC003', $reclamoInsertado['municipalidad_id']);
        $this->assertEquals('Alumbrado Público', $reclamoInsertado['municipalidad_tipo']);
        $this->assertEquals('Transformador dañado', $reclamoInsertado['municipalidad_motivo']);
        $this->assertEquals('Crítica', $reclamoInsertado['prioridad']);
        
        // Test 2: Inserción con solo algunos campos (campos mínimos)
        $reclamoMinimo = [
            'municipalidad_id' => 'REC004',
            'municipalidad_tipo' => 'Alumbrado Público',
            'municipalidad_motivo' => 'Lámpara parpadeando',
            'municipalidad_fechaInicio' => '2024-01-18 16:45:00',
            'municipalidad_estado' => 'Pendiente',
            'municipalidad_ciudadano' => 'Ana Martínez',
            'prioridad' => 'Baja'
        ];
        
        $reclamoId2 = $this->reclamoModel->insert($reclamoMinimo);
        $this->assertNotFalse($reclamoId2);
        
        // Verificar que se insertó correctamente
        $reclamoInsertado2 = $this->reclamoModel->find($reclamoId2);
        $this->assertEquals('REC004', $reclamoInsertado2['municipalidad_id']);
        $this->assertEquals('Ana Martínez', $reclamoInsertado2['municipalidad_ciudadano']);
        $this->assertEquals('Baja', $reclamoInsertado2['prioridad']);
        
        // Test 3: Inserción con campos no permitidos mezclados
        $reclamoConCamposNoPermitidos = [
            'municipalidad_id' => 'REC005',
            'municipalidad_tipo' => 'Alumbrado Público',
            'municipalidad_motivo' => 'Poste inclinado',
            'municipalidad_fechaInicio' => '2024-01-19 11:30:00',
            'municipalidad_estado' => 'Pendiente',
            'municipalidad_ciudadano' => 'Pedro Rodríguez',
            'prioridad' => 'Media',
            'id' => 999, // Campo no permitido
            'fechaCreacion' => '2024-01-01', // Campo no permitido
            'usuarioModificacion' => 'admin', // Campo no permitido
            'campoInventado' => 'valorInventado' // Campo no permitido
        ];
        
        $reclamoId3 = $this->reclamoModel->insert($reclamoConCamposNoPermitidos);
        $this->assertNotFalse($reclamoId3);
        
        // Verificar que solo se insertaron los campos permitidos
        $reclamoInsertado3 = $this->reclamoModel->find($reclamoId3);
        $this->assertEquals('REC005', $reclamoInsertado3['municipalidad_id']);
        $this->assertEquals('Pedro Rodríguez', $reclamoInsertado3['municipalidad_ciudadano']);
        $this->assertEquals('Media', $reclamoInsertado3['prioridad']);
        
        // Verificar que los campos no permitidos NO se insertaron
        $this->assertArrayNotHasKey('fechaCreacion', $reclamoInsertado3);
        $this->assertArrayNotHasKey('usuarioModificacion', $reclamoInsertado3);
        $this->assertArrayNotHasKey('campoInventado', $reclamoInsertado3);
        
        // Verificar que el ID es el correcto (no el que intentamos forzar)
        $this->assertNotEquals(999, $reclamoInsertado3['id']);
        $this->assertEquals($reclamoId3, $reclamoInsertado3['id']);
        
        // Test 4: Intentar insertar solo con campos no permitidos
        $soloCamposNoPermitidos = [
            'fechaCreacion' => '2024-01-01',
            'usuarioCreacion' => 'admin',
            'campoInventado' => 'valor'
        ];
        
        // Esto debería lanzar excepción
        $this->expectException(\CodeIgniter\Database\Exceptions\DataException::class);
        $this->expectExceptionMessage('There is no data to insert');
        $this->reclamoModel->insert($soloCamposNoPermitidos);
        
        // Test 5: Verificar que el modelo respeta la configuración allowedFields
        $reflection = new \ReflectionClass($this->reclamoModel);
        $allowedFieldsProperty = $reflection->getProperty('allowedFields');
        $allowedFieldsProperty->setAccessible(true);
        $allowedFields = $allowedFieldsProperty->getValue($this->reclamoModel);
        
        // Verificar que tiene los 15 campos permitidos
        $this->assertCount(15, $allowedFields);
        $this->assertContains('municipalidad_id', $allowedFields);
        $this->assertContains('municipalidad_tipo', $allowedFields);
        $this->assertContains('municipalidad_motivo', $allowedFields);
        $this->assertContains('municipalidad_fechaInicio', $allowedFields);
        $this->assertContains('municipalidad_fechaModificacion', $allowedFields);
        $this->assertContains('municipalidad_recepcion', $allowedFields);
        $this->assertContains('municipalidad_estado', $allowedFields);
        $this->assertContains('municipalidad_telefono', $allowedFields);
        $this->assertContains('municipalidad_domicilio', $allowedFields);
        $this->assertContains('municipalidad_numeroDomicilio', $allowedFields);
        $this->assertContains('municipalidad_entreCalleUno', $allowedFields);
        $this->assertContains('municipalidad_entreCalleDos', $allowedFields);
        $this->assertContains('municipalidad_ciudadano', $allowedFields);
        $this->assertContains('municipalidad_descripcion', $allowedFields);
        $this->assertContains('prioridad', $allowedFields);
        $this->assertNotContains('id', $allowedFields);
        $this->assertNotContains('fechaCreacion', $allowedFields);
    }

    /**
     * Test: Validar campos permitidos en inserción y actualización
     */
    public function testAllowedFieldsValidation()
    {
        // Test 1: Inserción con campos permitidos y no permitidos mezclados
        $reclamoConCamposMixtos = [
            'municipalidad_id' => 'REC006',
            'municipalidad_tipo' => 'Alumbrado Público',
            'municipalidad_motivo' => 'Test Campos Mixtos',
            'municipalidad_fechaInicio' => '2024-01-20 10:00:00',
            'municipalidad_estado' => 'Pendiente',
            'municipalidad_ciudadano' => 'Test User',
            'prioridad' => 'Media',
            'id' => 999, // Campo no permitido (primary key)
            'fechaCreacion' => '2024-01-01', // Campo no permitido
            'usuarioModificacion' => 'admin', // Campo no permitido
            'campoInventado' => 'valorInventado' // Campo no permitido
        ];
        
        $reclamoId = $this->reclamoModel->insert($reclamoConCamposMixtos);
        $this->assertNotFalse($reclamoId);
        
        // Verificar que solo se insertaron los campos permitidos
        $reclamoInsertado = $this->reclamoModel->find($reclamoId);
        $this->assertEquals('REC006', $reclamoInsertado['municipalidad_id']);
        $this->assertEquals('Alumbrado Público', $reclamoInsertado['municipalidad_tipo']);
        $this->assertEquals('Test Campos Mixtos', $reclamoInsertado['municipalidad_motivo']);
        $this->assertEquals('Media', $reclamoInsertado['prioridad']);
        
        // Verificar que los campos no permitidos NO se insertaron
        $this->assertArrayNotHasKey('fechaCreacion', $reclamoInsertado);
        $this->assertArrayNotHasKey('usuarioModificacion', $reclamoInsertado);
        $this->assertArrayNotHasKey('campoInventado', $reclamoInsertado);
        
        // Verificar que el ID es el correcto (no el que intentamos forzar)
        $this->assertNotEquals(999, $reclamoInsertado['id']);
        $this->assertEquals($reclamoId, $reclamoInsertado['id']);
        
        // Test 2: Actualización con campos permitidos y no permitidos
        $datosActualizacion = [
            'municipalidad_estado' => 'En Proceso',
            'municipalidad_descripcion' => 'Descripción actualizada',
            'prioridad' => 'Alta',
            'id' => 888, // Campo no permitido
            'fechaModificacion' => '2024-12-01', // Campo no permitido
            'campoFalso' => 'valorFalso' // Campo no permitido
        ];
        
        $resultadoUpdate = $this->reclamoModel->update($reclamoId, $datosActualizacion);
        $this->assertTrue($resultadoUpdate);
        
        // Verificar que solo se actualizaron los campos permitidos
        $reclamoActualizado = $this->reclamoModel->find($reclamoId);
        $this->assertEquals('En Proceso', $reclamoActualizado['municipalidad_estado']);
        $this->assertEquals('Descripción actualizada', $reclamoActualizado['municipalidad_descripcion']);
        $this->assertEquals('Alta', $reclamoActualizado['prioridad']);
        
        // Los otros campos deben mantenerse igual
        $this->assertEquals('REC006', $reclamoActualizado['municipalidad_id']);
        $this->assertEquals('Alumbrado Público', $reclamoActualizado['municipalidad_tipo']);
        
        // Verificar que los campos no permitidos NO se actualizaron
        $this->assertArrayNotHasKey('fechaModificacion', $reclamoActualizado);
        $this->assertArrayNotHasKey('campoFalso', $reclamoActualizado);
        
        // Verificar que el ID no cambió (no se puede actualizar)
        $this->assertEquals($reclamoId, $reclamoActualizado['id']);
        $this->assertNotEquals(888, $reclamoActualizado['id']);
        
        // Test 3: Intentar insertar solo con campos no permitidos
        $soloCamposNoPermitidos = [
            'fechaCreacion' => '2024-01-01',
            'usuarioCreacion' => 'admin',
            'campoInventado' => 'valor'
        ];
        
        // Esto debería lanzar excepción
        $this->expectException(\CodeIgniter\Database\Exceptions\DataException::class);
        $this->expectExceptionMessage('There is no data to insert');
        $this->reclamoModel->insert($soloCamposNoPermitidos);
        
        // Test 4: Verificar que el modelo respeta la configuración allowedFields
        $reflection = new \ReflectionClass($this->reclamoModel);
        $allowedFieldsProperty = $reflection->getProperty('allowedFields');
        $allowedFieldsProperty->setAccessible(true);
        $allowedFields = $allowedFieldsProperty->getValue($this->reclamoModel);
        
        // Verificar que tiene exactamente los 15 campos permitidos
        $expectedFields = [
            'municipalidad_id', 'municipalidad_tipo', 'municipalidad_motivo',
            'municipalidad_fechaInicio', 'municipalidad_fechaModificacion', 'municipalidad_recepcion',
            'municipalidad_estado', 'municipalidad_telefono', 'municipalidad_domicilio',
            'municipalidad_numeroDomicilio', 'municipalidad_entreCalleUno', 'municipalidad_entreCalleDos',
            'municipalidad_ciudadano', 'municipalidad_descripcion', 'prioridad'
        ];
        
        $this->assertEquals($expectedFields, $allowedFields);
        $this->assertCount(15, $allowedFields);
        
        // Verificar que contiene todos los campos municipales
        foreach ($expectedFields as $field) {
            $this->assertContains($field, $allowedFields);
        }
        
        // Verificar que NO contiene campos no permitidos
        $this->assertNotContains('id', $allowedFields);
        $this->assertNotContains('fechaCreacion', $allowedFields);
        $this->assertNotContains('usuarioModificacion', $allowedFields);
        $this->assertNotContains('campoInventado', $allowedFields);
        
        // Test 5: Actualizar con datos vacíos
        $datosVacios = [];
        
        // Esto debería lanzar excepción
        $this->expectException(\CodeIgniter\Database\Exceptions\DataException::class);
        $this->expectExceptionMessage('There is no data to update');
        $this->reclamoModel->update($reclamoId, $datosVacios);
    }

    /**
     * Test: Búsqueda con datos complejos
     */
    public function testComplexSearchMethods()
    {
        // Limpiar completamente la tabla antes del test
        $this->db->table('reclamo')->emptyTable();
        
        // Insertar múltiples reclamos con datos complejos y realistas
        $reclamosComplejos = [
            [
                'municipalidad_id' => 'REC-2024-001',
                'municipalidad_tipo' => 'Alumbrado Público',
                'municipalidad_motivo' => 'Foco quemado en poste de luz',
                'municipalidad_fechaInicio' => '2024-01-15 08:30:00',
                'municipalidad_fechaModificacion' => '2024-01-15 14:20:00',
                'municipalidad_recepcion' => 'Llamada telefónica',
                'municipalidad_estado' => 'En Proceso',
                'municipalidad_telefono' => '342-555-1234',
                'municipalidad_domicilio' => 'Avenida San Martín',
                'municipalidad_numeroDomicilio' => '1234',
                'municipalidad_entreCalleUno' => 'Calle Mitre',
                'municipalidad_entreCalleDos' => 'Calle Belgrano',
                'municipalidad_ciudadano' => 'Juan Carlos Pérez',
                'municipalidad_descripcion' => 'El foco del poste de luz ubicado en la esquina de Av. San Martín y Mitre no funciona desde hace 3 días. Es un problema de seguridad para los vecinos que transitan por la zona durante la noche.',
                'prioridad' => 'Alta'
            ],
            [
                'municipalidad_id' => 'REC-2024-002',
                'municipalidad_tipo' => 'Alumbrado Público',
                'municipalidad_motivo' => 'Poste de luz inclinado',
                'municipalidad_fechaInicio' => '2024-01-16 09:15:00',
                'municipalidad_fechaModificacion' => '2024-01-16 09:15:00',
                'municipalidad_recepcion' => 'Aplicación móvil',
                'municipalidad_estado' => 'Pendiente',
                'municipalidad_telefono' => '342-555-5678',
                'municipalidad_domicilio' => 'Calle 25 de Mayo',
                'municipalidad_numeroDomicilio' => '567',
                'municipalidad_entreCalleUno' => 'Calle Sarmiento',
                'municipalidad_entreCalleDos' => 'Calle Rivadavia',
                'municipalidad_ciudadano' => 'María Elena González',
                'municipalidad_descripcion' => 'El poste de luz en la cuadra 500 de 25 de Mayo está inclinado hacia la calle, posiblemente por el viento fuerte de la semana pasada. Representa un peligro para vehículos y peatones.',
                'prioridad' => 'Media'
            ],
            [
                'municipalidad_id' => 'REC-2024-003',
                'municipalidad_tipo' => 'Alumbrado Público',
                'municipalidad_motivo' => 'Cableado expuesto',
                'municipalidad_fechaInicio' => '2024-01-17 16:45:00',
                'municipalidad_fechaModificacion' => '2024-01-17 16:45:00',
                'municipalidad_recepcion' => 'Presencial',
                'municipalidad_estado' => 'Resuelto',
                'municipalidad_telefono' => '342-555-9012',
                'municipalidad_domicilio' => 'Boulevard Pellegrini',
                'municipalidad_numeroDomicilio' => '890',
                'municipalidad_entreCalleUno' => 'Calle Corrientes',
                'municipalidad_entreCalleDos' => 'Calle Entre Ríos',
                'municipalidad_ciudadano' => 'Carlos Alberto Rodríguez',
                'municipalidad_descripcion' => 'Se observan cables eléctricos expuestos en el poste ubicado en Boulevard Pellegrini entre Corrientes y Entre Ríos. Los cables están colgando y pueden representar un riesgo eléctrico para la población.',
                'prioridad' => 'Crítica'
            ],
            [
                'municipalidad_id' => 'REC-2024-004',
                'municipalidad_tipo' => 'Alumbrado Público',
                'municipalidad_motivo' => 'Falta de iluminación',
                'municipalidad_fechaInicio' => '2024-01-18 19:00:00',
                'municipalidad_fechaModificacion' => '2024-01-18 19:00:00',
                'municipalidad_recepcion' => 'Email',
                'municipalidad_estado' => 'Nuevo',
                'municipalidad_telefono' => '342-555-3456',
                'municipalidad_domicilio' => 'Calle Urquiza',
                'municipalidad_numeroDomicilio' => '234',
                'municipalidad_entreCalleUno' => 'Calle San Luis',
                'municipalidad_entreCalleDos' => 'Calle Santa Fe',
                'municipalidad_ciudadano' => 'Ana Patricia López',
                'municipalidad_descripcion' => 'La cuadra entre San Luis y Santa Fe en calle Urquiza carece completamente de iluminación nocturna. Es una zona residencial con mucho tránsito peatonal, especialmente de estudiantes que regresan tarde de la universidad.',
                'prioridad' => 'Media'
            ],
            [
                'municipalidad_id' => 'REC-2024-005',
                'municipalidad_tipo' => 'Alumbrado Público',
                'municipalidad_motivo' => 'Transformador con ruido',
                'municipalidad_fechaInicio' => '2024-01-19 11:30:00',
                'municipalidad_fechaModificacion' => '2024-01-19 11:30:00',
                'municipalidad_recepcion' => 'WhatsApp',
                'municipalidad_estado' => 'En Revisión',
                'municipalidad_telefono' => '342-555-7890',
                'municipalidad_domicilio' => 'Avenida Circunvalación',
                'municipalidad_numeroDomicilio' => '1500',
                'municipalidad_entreCalleUno' => 'Calle Córdoba',
                'municipalidad_entreCalleDos' => 'Calle Tucumán',
                'municipalidad_ciudadano' => 'Roberto Miguel Fernández',
                'municipalidad_descripcion' => 'El transformador ubicado en Av. Circunvalación emite un ruido constante muy molesto que se escucha durante todo el día y la noche. Los vecinos de la zona no pueden descansar adecuadamente. Además, se observan chispas ocasionales.',
                'prioridad' => 'Alta'
            ]
        ];

        // Insertar todos los reclamos
        $idsInsertados = [];
        foreach ($reclamosComplejos as $reclamo) {
            $id = $this->reclamoModel->insert($reclamo);
            $this->assertNotFalse($id);
            $idsInsertados[] = $id;
        }

        // Test 1: Verificar que se insertaron todos los reclamos
        $this->assertCount(5, $idsInsertados);

        // Test 2: Búsqueda por ID específico (find)
        $reclamoEncontrado = $this->reclamoModel->find($idsInsertados[0]);
        $this->assertNotNull($reclamoEncontrado);
        $this->assertEquals('REC-2024-001', $reclamoEncontrado['municipalidad_id']);
        $this->assertEquals('Juan Carlos Pérez', $reclamoEncontrado['municipalidad_ciudadano']);
        $this->assertEquals('Alta', $reclamoEncontrado['prioridad']);
        $this->assertEquals('En Proceso', $reclamoEncontrado['municipalidad_estado']);
        $this->assertStringContainsString('foco del poste de luz', $reclamoEncontrado['municipalidad_descripcion']);

        // Test 3: Búsqueda de todos los reclamos (findAll)
        $todosLosReclamos = $this->reclamoModel->findAll();
        $this->assertCount(5, $todosLosReclamos);

        // Test 4: Verificar que todos los reclamos tienen todos los campos
        foreach ($todosLosReclamos as $reclamo) {
            // Verificar campos obligatorios
            $this->assertArrayHasKey('id', $reclamo);
            $this->assertArrayHasKey('municipalidad_id', $reclamo);
            $this->assertArrayHasKey('municipalidad_tipo', $reclamo);
            $this->assertArrayHasKey('municipalidad_motivo', $reclamo);
            $this->assertArrayHasKey('municipalidad_fechaInicio', $reclamo);
            $this->assertArrayHasKey('municipalidad_estado', $reclamo);
            $this->assertArrayHasKey('municipalidad_ciudadano', $reclamo);
            $this->assertArrayHasKey('prioridad', $reclamo);

            // Verificar que los IDs municipales son únicos
            $this->assertStringStartsWith('REC-2024-', $reclamo['municipalidad_id']);
        }

        // Test 5: Búsqueda específica por diferentes IDs
        $reclamoCritico = $this->reclamoModel->find($idsInsertados[2]); // REC-2024-003
        $this->assertEquals('Crítica', $reclamoCritico['prioridad']);
        $this->assertEquals('Resuelto', $reclamoCritico['municipalidad_estado']);
        $this->assertEquals('Carlos Alberto Rodríguez', $reclamoCritico['municipalidad_ciudadano']);

        $reclamoNuevo = $this->reclamoModel->find($idsInsertados[3]); // REC-2024-004
        $this->assertEquals('Nuevo', $reclamoNuevo['municipalidad_estado']);
        $this->assertEquals('Ana Patricia López', $reclamoNuevo['municipalidad_ciudadano']);
        $this->assertStringContainsString('estudiantes que regresan tarde', $reclamoNuevo['municipalidad_descripcion']);

        // Test 6: Verificar fechas y timestamps
        foreach ($todosLosReclamos as $reclamo) {
            $this->assertNotEmpty($reclamo['municipalidad_fechaInicio']);
            $this->assertStringMatchesFormat('%d-%d-%d %d:%d:%d', $reclamo['municipalidad_fechaInicio']);
        }

        // Test 7: Verificar diferentes tipos de recepción
        $tiposRecepcion = array_unique(array_column($todosLosReclamos, 'municipalidad_recepcion'));
        $this->assertContains('Llamada telefónica', $tiposRecepcion);
        $this->assertContains('Aplicación móvil', $tiposRecepcion);
        $this->assertContains('Presencial', $tiposRecepcion);
        $this->assertContains('Email', $tiposRecepcion);
        $this->assertContains('WhatsApp', $tiposRecepcion);

        // Test 8: Verificar diferentes estados
        $estados = array_unique(array_column($todosLosReclamos, 'municipalidad_estado'));
        $this->assertContains('En Proceso', $estados);
        $this->assertContains('Pendiente', $estados);
        $this->assertContains('Resuelto', $estados);
        $this->assertContains('Nuevo', $estados);
        $this->assertContains('En Revisión', $estados);

        // Test 9: Verificar diferentes prioridades
        $prioridades = array_unique(array_column($todosLosReclamos, 'prioridad'));
        $this->assertContains('Alta', $prioridades);
        $this->assertContains('Media', $prioridades);
        $this->assertContains('Crítica', $prioridades);

        // Test 10: Búsqueda de ID inexistente
        $reclamoInexistente = $this->reclamoModel->find(99999);
        $this->assertNull($reclamoInexistente);

        // Test 11: Verificar que las descripciones son largas y detalladas
        foreach ($todosLosReclamos as $reclamo) {
            $this->assertGreaterThan(50, strlen($reclamo['municipalidad_descripcion']));
            // Verificar que contiene palabras clave relacionadas con alumbrado
            $descripcion = strtolower($reclamo['municipalidad_descripcion']);
            $this->assertTrue(
                strpos($descripcion, 'zona') !== false || 
                strpos($descripcion, 'calle') !== false || 
                strpos($descripcion, 'poste') !== false ||
                strpos($descripcion, 'luz') !== false,
                'La descripción debe contener palabras clave relacionadas con alumbrado'
            );
        }

        // Test 12: Verificar números de teléfono
        foreach ($todosLosReclamos as $reclamo) {
            // Verificar formato de teléfono: XXX-XXX-XXXX
            $this->assertMatchesRegularExpression('/^\d{3}-\d{3}-\d{4}$/', $reclamo['municipalidad_telefono']);
            $this->assertStringStartsWith('342-555-', $reclamo['municipalidad_telefono']);
        }
    }
}
