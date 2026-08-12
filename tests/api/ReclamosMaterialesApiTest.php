<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

class ReclamosMaterialesApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'Tests\Support';
    protected $seed        = 'Tests\Support\Database\Seeds\TestSeeder';

    /**
     * HU-028 - Prueba 1: Registro de material con datos válidos
     * 
     * Objetivo: Verificar que se puede registrar un material utilizado en un reclamo
     * cuando se proporcionan material_id, cantidad y observación válidos.
     * Debe retornar 201 y vincular correctamente el material al reclamo.
     * 
     * Tipo de Prueba: API
     */
    public function testRegistroMaterialConDatosValidos()
    {
        // Paso 1: Crear un reclamo de prueba en la BD
        $reclamoData = [
            'municipalidad_id' => '10001',
            'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_fechaInicio' => '2025-01-15 10:00:00',
            'municipalidad_fechaModificacion' => '2025-01-15 10:00:00',
            'municipalidad_recepcion' => 'Web',
            'municipalidad_estado' => 'Recibido',
            'prioridad' => 'Media'
        ];

        $db = \Config\Database::connect();
        $db->table('reclamo')->insert($reclamoData);
        $reclamoId = $db->insertID();

        // Paso 2: Crear un material de prueba en la BD
        $materialData = [
            'nombre' => 'Lámpara LED 50W',
            'idTipo' => 1, // Lámpara LED (del seeder)
        ];

        $db->table('material')->insert($materialData);
        $materialId = $db->insertID();

        // Paso 3: Registrar el material utilizado en el reclamo via API
        $datosMaterialReclamo = [
            'material_id' => $materialId,
            'cantidad' => 2,
            'observacion' => 'Se instalaron 2 lámparas nuevas'
        ];

        $result = $this->withBodyFormat('json')
                      ->post("api/reclamos/{$reclamoId}/materiales", $datosMaterialReclamo);

        // Validación 1: Status 201 (Created)
        $result->assertStatus(201);

        // Validación 2: La respuesta contiene los datos del material registrado
        $responseData = json_decode($result->getJSON(), true);
        $this->assertIsArray($responseData, 'La respuesta debe ser un array');

        // Validación 3: Verificar que se generó un ID para el registro material_reclamo
        $this->assertArrayHasKey('id', $responseData, 'La respuesta debe contener el ID del registro');
        $this->assertIsNumeric($responseData['id'], 'El ID debe ser numérico');
        $this->assertGreaterThan(0, $responseData['id'], 'El ID debe ser mayor que 0');

        $materialReclamoId = $responseData['id'];

        // Validación 4: Verificar que los datos enviados se guardaron correctamente
        $this->assertEquals($reclamoId, $responseData['reclamo_id'], 'El reclamo_id debe coincidir');
        $this->assertEquals($materialId, $responseData['material_id'], 'El material_id debe coincidir');
        $this->assertEquals($datosMaterialReclamo['cantidad'], $responseData['cantidad'], 'La cantidad debe coincidir');
        $this->assertEquals($datosMaterialReclamo['observacion'], $responseData['observacion'], 'La observación debe coincidir');

        // Validación 5: Verificar que el registro existe en la base de datos
        $materialReclamoEnBD = $db->table('material_reclamo')
                                  ->where('id', $materialReclamoId)
                                  ->get()
                                  ->getRowArray();

        $this->assertNotNull($materialReclamoEnBD, 'El registro debe existir en la base de datos');

        // Validación 6: Verificar que los datos en BD coinciden con lo enviado
        $this->assertEquals($reclamoId, $materialReclamoEnBD['reclamo_id'], 'El reclamo_id en BD debe coincidir');
        $this->assertEquals($materialId, $materialReclamoEnBD['material_id'], 'El material_id en BD debe coincidir');
        $this->assertEquals($datosMaterialReclamo['cantidad'], $materialReclamoEnBD['cantidad'], 'La cantidad en BD debe coincidir');
        $this->assertEquals($datosMaterialReclamo['observacion'], $materialReclamoEnBD['observacion'], 'La observación en BD debe coincidir');

        // Validación 7: Verificar que la fecha se generó automáticamente
        $this->assertArrayHasKey('fecha', $materialReclamoEnBD, 'Debe existir el campo fecha');
        $this->assertNotNull($materialReclamoEnBD['fecha'], 'La fecha no debe ser nula');

        // Validación 8: Verificar que la respuesta incluye información del material (JOIN)
        $this->assertArrayHasKey('material_nombre', $responseData, 'La respuesta debe incluir el nombre del material');
        $this->assertEquals('Lámpara LED 50W', $responseData['material_nombre'], 'El nombre del material debe coincidir');

        // Validación 9: Verificar que la respuesta incluye información del tipo de material (JOIN)
        $this->assertArrayHasKey('tipo_material_nombre', $responseData, 'La respuesta debe incluir el nombre del tipo de material');
        $this->assertEquals('Lámpara LED', $responseData['tipo_material_nombre'], 'El nombre del tipo de material debe coincidir');

        // Validación 10: Verificar vinculación - el material queda asociado al reclamo
        $materialesDelReclamo = $db->table('material_reclamo')
                                   ->where('reclamo_id', $reclamoId)
                                   ->get()
                                   ->getResultArray();

        $this->assertCount(1, $materialesDelReclamo, 'Debe haber exactamente 1 material asociado al reclamo');
        $this->assertEquals($materialId, $materialesDelReclamo[0]['material_id'], 'El material debe estar vinculado al reclamo');
    }

    /**
     * HU-028 - Prueba 2: Validación de material_id obligatorio
     * 
     * Objetivo: Verificar que el sistema valida que el material_id es obligatorio.
     * Debe retornar 400 cuando no se proporciona material_id o está vacío.
     * 
     * Tipo de Prueba: API
     */
    public function testValidacionMaterialIdObligatorio()
    {
        // Paso 1: Crear un reclamo de prueba en la BD
        $reclamoData = [
            'municipalidad_id' => '10002',
            'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_fechaInicio' => '2025-01-15 10:00:00',
            'municipalidad_fechaModificacion' => '2025-01-15 10:00:00',
            'municipalidad_recepcion' => 'Web',
            'municipalidad_estado' => 'Recibido',
            'prioridad' => 'Media'
        ];

        $db = \Config\Database::connect();
        $db->table('reclamo')->insert($reclamoData);
        $reclamoId = $db->insertID();

        // Caso 1: POST sin material_id (campo omitido)
        $datosSinMaterialId = [
            'cantidad' => 2,
            'observacion' => 'Prueba sin material_id'
        ];

        $result1 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/materiales", $datosSinMaterialId);

        // Validación 1: Status 400 (Bad Request)
        $result1->assertStatus(400);

        // Validación 2: La respuesta contiene un mensaje de error
        $response1 = json_decode($result1->getJSON(), true);
        $this->assertIsArray($response1, 'La respuesta debe ser un array');
        
        // Verificar que contiene mensaje de error
        $this->assertTrue(
            isset($response1['messages']) || isset($response1['message']) || isset($response1['error']),
            'La respuesta debe contener un mensaje de error'
        );

        // Validación 3: El mensaje de error menciona que el material es obligatorio
        $mensajeError1 = $response1['messages'] ?? $response1['message'] ?? $response1['error'] ?? '';
        if (is_array($mensajeError1)) {
            $mensajeError1 = implode(' ', $mensajeError1);
        }
        $this->assertStringContainsString(
            'material',
            strtolower($mensajeError1),
            'El mensaje de error debe mencionar que el material es obligatorio'
        );

        // Validación 4: No se debe haber creado ningún registro en material_reclamo
        $registrosDespuesCaso1 = $db->table('material_reclamo')
                                   ->where('reclamo_id', $reclamoId)
                                   ->get()
                                   ->getResultArray();
        $this->assertCount(0, $registrosDespuesCaso1, 'No debe haberse creado ningún registro sin material_id');

        // Caso 2: POST con material_id vacío (string vacío)
        $datosMaterialIdVacio = [
            'material_id' => '',
            'cantidad' => 2,
            'observacion' => 'Prueba con material_id vacío'
        ];

        $result2 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/materiales", $datosMaterialIdVacio);

        // Validación 5: Status 400 (Bad Request)
        $result2->assertStatus(400);

        // Validación 6: La respuesta contiene un mensaje de error
        $response2 = json_decode($result2->getJSON(), true);
        $this->assertIsArray($response2, 'La respuesta debe ser un array');
        
        $this->assertTrue(
            isset($response2['messages']) || isset($response2['message']) || isset($response2['error']),
            'La respuesta debe contener un mensaje de error'
        );

        // Validación 7: El mensaje de error menciona que el material es obligatorio
        $mensajeError2 = $response2['messages'] ?? $response2['message'] ?? $response2['error'] ?? '';
        if (is_array($mensajeError2)) {
            $mensajeError2 = implode(' ', $mensajeError2);
        }
        $this->assertStringContainsString(
            'material',
            strtolower($mensajeError2),
            'El mensaje de error debe mencionar que el material es obligatorio'
        );

        // Validación 8: No se debe haber creado ningún registro en material_reclamo
        $registrosDespuesCaso2 = $db->table('material_reclamo')
                                   ->where('reclamo_id', $reclamoId)
                                   ->get()
                                   ->getResultArray();
        $this->assertCount(0, $registrosDespuesCaso2, 'No debe haberse creado ningún registro con material_id vacío');

        // Caso 3: POST con material_id null
        $datosMaterialIdNull = [
            'material_id' => null,
            'cantidad' => 2,
            'observacion' => 'Prueba con material_id null'
        ];

        $result3 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/materiales", $datosMaterialIdNull);

        // Validación 9: Status 400 (Bad Request)
        $result3->assertStatus(400);

        // Validación 10: La respuesta contiene un mensaje de error
        $response3 = json_decode($result3->getJSON(), true);
        $this->assertIsArray($response3, 'La respuesta debe ser un array');
        
        $this->assertTrue(
            isset($response3['messages']) || isset($response3['message']) || isset($response3['error']),
            'La respuesta debe contener un mensaje de error'
        );

        // Validación 11: El mensaje de error menciona que el material es obligatorio
        $mensajeError3 = $response3['messages'] ?? $response3['message'] ?? $response3['error'] ?? '';
        if (is_array($mensajeError3)) {
            $mensajeError3 = implode(' ', $mensajeError3);
        }
        $this->assertStringContainsString(
            'material',
            strtolower($mensajeError3),
            'El mensaje de error debe mencionar que el material es obligatorio'
        );

        // Validación 12: No se debe haber creado ningún registro en material_reclamo
        $registrosDespuesCaso3 = $db->table('material_reclamo')
                                   ->where('reclamo_id', $reclamoId)
                                   ->get()
                                   ->getResultArray();
        $this->assertCount(0, $registrosDespuesCaso3, 'No debe haberse creado ningún registro con material_id null');

        // Caso 4: POST con material_id = 0
        $datosMaterialIdCero = [
            'material_id' => 0,
            'cantidad' => 2,
            'observacion' => 'Prueba con material_id = 0'
        ];

        $result4 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/materiales", $datosMaterialIdCero);

        // Validación 13: Status 400 (Bad Request) - 0 debería ser considerado inválido
        $result4->assertStatus(400);

        // Validación 14: No se debe haber creado ningún registro
        $registrosDespuesCaso4 = $db->table('material_reclamo')
                                   ->where('reclamo_id', $reclamoId)
                                   ->get()
                                   ->getResultArray();
        $this->assertCount(0, $registrosDespuesCaso4, 'No debe haberse creado ningún registro con material_id = 0');
    }

    /**
     * HU-028 - Prueba 3: Registro de material con cantidad inválida (negativa o cero)
     * 
     * Objetivo: Verificar que cuando se proporciona una cantidad inválida (<= 0),
     * el sistema guarda la cantidad como null, ya que es opcional.
     * 
     * Tipo de Prueba: API
     */
    public function testRegistroMaterialConCantidadInvalida()
    {
        // Paso 1: Crear un reclamo de prueba en la BD
        $reclamoData = [
            'municipalidad_id' => '10003',
            'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_fechaInicio' => '2025-01-15 10:00:00',
            'municipalidad_fechaModificacion' => '2025-01-15 10:00:00',
            'municipalidad_recepcion' => 'Web',
            'municipalidad_estado' => 'Recibido',
            'prioridad' => 'Media'
        ];

        $db = \Config\Database::connect();
        $db->table('reclamo')->insert($reclamoData);
        $reclamoId = $db->insertID();

        // Paso 2: Crear un material de prueba en la BD
        $materialData = [
            'nombre' => 'Lámpara LED 100W',
            'idTipo' => 1, // Lámpara LED (del seeder)
        ];

        $db->table('material')->insert($materialData);
        $materialId = $db->insertID();

        // Caso 1: POST con cantidad = 0
        $datosCantidadCero = [
            'material_id' => $materialId,
            'cantidad' => 0,
            'observacion' => 'Prueba con cantidad = 0'
        ];

        $result1 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/materiales", $datosCantidadCero);

        // Validación 1: Status 201 (Created) - Debe aceptar la petición
        $result1->assertStatus(201);

        // Validación 2: La respuesta contiene el registro creado
        $response1 = json_decode($result1->getJSON(), true);
        $this->assertIsArray($response1, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('id', $response1, 'Debe tener un ID');

        $materialReclamoId1 = $response1['id'];

        // Validación 3: La cantidad en la respuesta debe ser null
        $this->assertNull($response1['cantidad'], 'La cantidad debe ser null cuando se envía 0');

        // Validación 4: Verificar en BD que la cantidad se guardó como null
        $registroBD1 = $db->table('material_reclamo')
                          ->where('id', $materialReclamoId1)
                          ->get()
                          ->getRowArray();

        $this->assertNotNull($registroBD1, 'El registro debe existir en BD');
        $this->assertNull($registroBD1['cantidad'], 'La cantidad en BD debe ser null');

        // Validación 5: Los otros campos deben estar correctos
        $this->assertEquals($reclamoId, $registroBD1['reclamo_id'], 'El reclamo_id debe coincidir');
        $this->assertEquals($materialId, $registroBD1['material_id'], 'El material_id debe coincidir');
        $this->assertEquals('Prueba con cantidad = 0', $registroBD1['observacion'], 'La observación debe coincidir');

        // Caso 2: POST con cantidad negativa
        $datosCantidadNegativa = [
            'material_id' => $materialId,
            'cantidad' => -5,
            'observacion' => 'Prueba con cantidad negativa'
        ];

        $result2 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/materiales", $datosCantidadNegativa);

        // Validación 6: Status 201 (Created) - Debe aceptar la petición
        $result2->assertStatus(201);

        // Validación 7: La respuesta contiene el registro creado
        $response2 = json_decode($result2->getJSON(), true);
        $this->assertIsArray($response2, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('id', $response2, 'Debe tener un ID');

        $materialReclamoId2 = $response2['id'];

        // Validación 8: La cantidad en la respuesta debe ser null
        $this->assertNull($response2['cantidad'], 'La cantidad debe ser null cuando se envía negativa');

        // Validación 9: Verificar en BD que la cantidad se guardó como null
        $registroBD2 = $db->table('material_reclamo')
                          ->where('id', $materialReclamoId2)
                          ->get()
                          ->getRowArray();

        $this->assertNotNull($registroBD2, 'El registro debe existir en BD');
        $this->assertNull($registroBD2['cantidad'], 'La cantidad en BD debe ser null cuando es negativa');

        // Validación 10: Los otros campos deben estar correctos
        $this->assertEquals($reclamoId, $registroBD2['reclamo_id'], 'El reclamo_id debe coincidir');
        $this->assertEquals($materialId, $registroBD2['material_id'], 'El material_id debe coincidir');
        $this->assertEquals('Prueba con cantidad negativa', $registroBD2['observacion'], 'La observación debe coincidir');

        // Caso 3: POST con cantidad como string vacío (debe convertirse a null)
        $datosCantidadStringVacio = [
            'material_id' => $materialId,
            'cantidad' => '',
            'observacion' => 'Prueba con cantidad string vacío'
        ];

        $result3 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/materiales", $datosCantidadStringVacio);

        // Validación 11: Status 201 (Created) - Debe aceptar la petición
        $result3->assertStatus(201);

        // Validación 12: La cantidad debe ser null
        $response3 = json_decode($result3->getJSON(), true);
        $materialReclamoId3 = $response3['id'];
        $this->assertNull($response3['cantidad'], 'La cantidad debe ser null cuando se envía string vacío');

        // Validación 13: Verificar en BD
        $registroBD3 = $db->table('material_reclamo')
                          ->where('id', $materialReclamoId3)
                          ->get()
                          ->getRowArray();

        $this->assertNull($registroBD3['cantidad'], 'La cantidad en BD debe ser null para string vacío');

        // Caso 4: POST sin campo cantidad (debe ser null)
        $datosSinCantidad = [
            'material_id' => $materialId,
            'observacion' => 'Prueba sin campo cantidad'
        ];

        $result4 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/materiales", $datosSinCantidad);

        // Validación 14: Status 201 (Created)
        $result4->assertStatus(201);

        // Validación 15: La cantidad debe ser null
        $response4 = json_decode($result4->getJSON(), true);
        $materialReclamoId4 = $response4['id'];
        $this->assertNull($response4['cantidad'], 'La cantidad debe ser null cuando no se proporciona');

        // Validación 16: Verificar en BD
        $registroBD4 = $db->table('material_reclamo')
                          ->where('id', $materialReclamoId4)
                          ->get()
                          ->getRowArray();

        $this->assertNull($registroBD4['cantidad'], 'La cantidad en BD debe ser null cuando no se proporciona');

        // Validación 17: Verificar que se crearon todos los registros correctamente
        $todosLosRegistros = $db->table('material_reclamo')
                                ->where('reclamo_id', $reclamoId)
                                ->get()
                                ->getResultArray();

        $this->assertCount(4, $todosLosRegistros, 'Deben haberse creado 4 registros');

        // Validación 18: Todos los registros deben tener cantidad = null
        foreach ($todosLosRegistros as $registro) {
            $this->assertNull($registro['cantidad'], 'Todos los registros deben tener cantidad = null');
        }
    }

    /**
     * HU-028 - Prueba 4: Crear material nuevo y registrarlo en reclamo (flujo completo)
     * 
     * Objetivo: Verificar el flujo completo end-to-end de crear un material nuevo
     * y luego registrarlo en un reclamo. Debe verificar que ambos pasos funcionan
     * correctamente en secuencia.
     * 
     * Tipo de Prueba: API - Integración
     */
    public function testCrearMaterialNuevoYRegistrarloEnReclamo()
    {
        // Paso 1: Crear un reclamo de prueba en la BD
        $reclamoData = [
            'municipalidad_id' => '10004',
            'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_fechaInicio' => '2025-01-15 10:00:00',
            'municipalidad_fechaModificacion' => '2025-01-15 10:00:00',
            'municipalidad_recepcion' => 'Web',
            'municipalidad_estado' => 'Recibido',
            'prioridad' => 'Media'
        ];

        $db = \Config\Database::connect();
        $db->table('reclamo')->insert($reclamoData);
        $reclamoId = $db->insertID();

        // Validación 1: Verificar que el reclamo se creó correctamente
        $reclamoCreado = $db->table('reclamo')
                           ->where('id', $reclamoId)
                           ->get()
                           ->getRowArray();
        $this->assertNotNull($reclamoCreado, 'El reclamo debe existir en la BD');

        // Paso 2: Crear un material nuevo via API
        $datosMaterialNuevo = [
            'nombre' => 'Lámpara LED 75W Nueva',
            'idTipo' => 1, // Lámpara LED (del seeder)
        ];

        $resultCrearMaterial = $this->withBodyFormat('json')
                                   ->post('api/materiales', $datosMaterialNuevo);

        // Validación 2: El material se crea exitosamente
        $resultCrearMaterial->assertStatus(201);

        // Validación 3: La respuesta contiene el material creado
        $responseCrearMaterial = json_decode($resultCrearMaterial->getJSON(), true);
        $this->assertIsArray($responseCrearMaterial, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('id', $responseCrearMaterial, 'El material debe tener un ID generado');
        
        $materialId = $responseCrearMaterial['id'];
        $this->assertIsNumeric($materialId, 'El ID debe ser numérico');
        $this->assertGreaterThan(0, $materialId, 'El ID debe ser mayor que 0');

        // Validación 4: Los datos del material creado coinciden
        $this->assertEquals($datosMaterialNuevo['nombre'], $responseCrearMaterial['nombre'], 'El nombre debe coincidir');
        $this->assertEquals($datosMaterialNuevo['idTipo'], $responseCrearMaterial['idTipo'], 'El idTipo debe coincidir');

        // Validación 5: Verificar que el material existe en la BD
        $materialEnBD = $db->table('material')
                          ->where('id', $materialId)
                          ->get()
                          ->getRowArray();
        $this->assertNotNull($materialEnBD, 'El material debe existir en la BD');
        $this->assertEquals($datosMaterialNuevo['nombre'], $materialEnBD['nombre'], 'El nombre en BD debe coincidir');

        // Paso 3: Registrar el material nuevo en el reclamo via API
        $datosMaterialReclamo = [
            'material_id' => $materialId,
            'cantidad' => 3, // La cantidad utilizada en el reclamo
            'observacion' => 'Material nuevo creado y utilizado en el reclamo'
        ];

        $resultRegistrarMaterial = $this->withBodyFormat('json')
                                       ->post("api/reclamos/{$reclamoId}/materiales", $datosMaterialReclamo);

        // Validación 6: El registro en material_reclamo se crea exitosamente
        $resultRegistrarMaterial->assertStatus(201);

        // Validación 7: La respuesta contiene el registro material_reclamo creado
        $responseRegistrarMaterial = json_decode($resultRegistrarMaterial->getJSON(), true);
        $this->assertIsArray($responseRegistrarMaterial, 'La respuesta debe ser un array');
        $this->assertArrayHasKey('id', $responseRegistrarMaterial, 'El registro debe tener un ID generado');
        
        $materialReclamoId = $responseRegistrarMaterial['id'];
        $this->assertIsNumeric($materialReclamoId, 'El ID debe ser numérico');
        $this->assertGreaterThan(0, $materialReclamoId, 'El ID debe ser mayor que 0');

        // Validación 8: Los datos del registro material_reclamo coinciden
        $this->assertEquals($reclamoId, $responseRegistrarMaterial['reclamo_id'], 'El reclamo_id debe coincidir');
        $this->assertEquals($materialId, $responseRegistrarMaterial['material_id'], 'El material_id debe coincidir');
        $this->assertEquals($datosMaterialReclamo['cantidad'], $responseRegistrarMaterial['cantidad'], 'La cantidad debe coincidir');
        $this->assertEquals($datosMaterialReclamo['observacion'], $responseRegistrarMaterial['observacion'], 'La observación debe coincidir');

        // Validación 9: La respuesta incluye información del material (JOIN)
        $this->assertArrayHasKey('material_nombre', $responseRegistrarMaterial, 'Debe incluir el nombre del material');
        $this->assertEquals($datosMaterialNuevo['nombre'], $responseRegistrarMaterial['material_nombre'], 'El nombre del material debe coincidir');

        // Validación 10: La respuesta incluye información del tipo de material (JOIN)
        $this->assertArrayHasKey('tipo_material_nombre', $responseRegistrarMaterial, 'Debe incluir el nombre del tipo de material');
        $this->assertEquals('Lámpara LED', $responseRegistrarMaterial['tipo_material_nombre'], 'El tipo de material debe coincidir');

        // Validación 11: Verificar que el registro existe en la BD
        $materialReclamoEnBD = $db->table('material_reclamo')
                                  ->where('id', $materialReclamoId)
                                  ->get()
                                  ->getRowArray();
        $this->assertNotNull($materialReclamoEnBD, 'El registro material_reclamo debe existir en la BD');

        // Validación 12: Verificar que los datos en BD coinciden
        $this->assertEquals($reclamoId, $materialReclamoEnBD['reclamo_id'], 'El reclamo_id en BD debe coincidir');
        $this->assertEquals($materialId, $materialReclamoEnBD['material_id'], 'El material_id en BD debe coincidir');
        $this->assertEquals($datosMaterialReclamo['cantidad'], $materialReclamoEnBD['cantidad'], 'La cantidad en BD debe coincidir');
        $this->assertEquals($datosMaterialReclamo['observacion'], $materialReclamoEnBD['observacion'], 'La observación en BD debe coincidir');

        // Validación 13: Verificar vinculación completa - el material está asociado al reclamo
        $materialesDelReclamo = $db->table('material_reclamo')
                                   ->where('reclamo_id', $reclamoId)
                                   ->get()
                                   ->getResultArray();

        $this->assertCount(1, $materialesDelReclamo, 'Debe haber exactamente 1 material asociado al reclamo');
        $this->assertEquals($materialId, $materialesDelReclamo[0]['material_id'], 'El material debe estar vinculado al reclamo');

        // Validación 14: Verificar que el material nuevo está disponible en el catálogo
        $resultObtenerMateriales = $this->get('api/materiales');
        $resultObtenerMateriales->assertStatus(200);
        
        $materialesCatalogo = json_decode($resultObtenerMateriales->getJSON(), true);
        $this->assertIsArray($materialesCatalogo, 'El catálogo debe ser un array');
        
        $materialEnCatalogo = null;
        foreach ($materialesCatalogo as $material) {
            if ($material['id'] == $materialId) {
                $materialEnCatalogo = $material;
                break;
            }
        }
        
        $this->assertNotNull($materialEnCatalogo, 'El material nuevo debe estar en el catálogo');
        $this->assertEquals($datosMaterialNuevo['nombre'], $materialEnCatalogo['nombre'], 'El nombre del material en catálogo debe coincidir');

        // Validación 15: Verificar que se puede obtener el historial de materiales del reclamo
        $resultObtenerHistorial = $this->get("api/reclamos/{$reclamoId}/materiales");
        $resultObtenerHistorial->assertStatus(200);
        
        $historialMateriales = json_decode($resultObtenerHistorial->getJSON(), true);
        $this->assertIsArray($historialMateriales, 'El historial debe ser un array');
        $this->assertCount(1, $historialMateriales, 'Debe haber 1 material en el historial');
        
        $materialEnHistorial = $historialMateriales[0];
        $this->assertEquals($materialId, $materialEnHistorial['material_id'], 'El material_id en historial debe coincidir');
        $this->assertEquals($datosMaterialNuevo['nombre'], $materialEnHistorial['material_nombre'], 'El nombre del material en historial debe coincidir');
    }

    /**
     * HU-028 - Prueba 5: Múltiples materiales en un mismo reclamo
     * 
     * Objetivo: Verificar que se pueden registrar varios materiales diferentes
     * en el mismo reclamo y que todos quedan vinculados correctamente.
     * 
     * Tipo de Prueba: API
     */
    public function testMultiplesMaterialesEnMismoReclamo()
    {
        // Paso 1: Crear un reclamo de prueba en la BD
        $reclamoData = [
            'municipalidad_id' => '10005',
            'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => 'Múltiples materiales necesarios',
            'municipalidad_fechaInicio' => '2025-01-15 10:00:00',
            'municipalidad_fechaModificacion' => '2025-01-15 10:00:00',
            'municipalidad_recepcion' => 'Web',
            'municipalidad_estado' => 'Recibido',
            'prioridad' => 'Media'
        ];

        $db = \Config\Database::connect();
        $db->table('reclamo')->insert($reclamoData);
        $reclamoId = $db->insertID();

        // Paso 2: Crear múltiples materiales diferentes en la BD
        $materialesACrear = [
            [
                'nombre' => 'Lámpara LED 50W',
                'idTipo' => 1, // Lámpara LED
            ],
            [
                'nombre' => 'Cable Eléctrico 2x1.5mm',
                'idTipo' => 3, // Cable Eléctrico
            ],
            [
                'nombre' => 'Poste de Concreto 8m',
                'idTipo' => 4, // Poste
            ],
            [
                'nombre' => 'Lámpara de Sodio 150W',
                'idTipo' => 2, // Lámpara de Sodio
            ]
        ];

        $materialIds = [];
        foreach ($materialesACrear as $materialData) {
            $db->table('material')->insert($materialData);
            $materialIds[] = $db->insertID();
        }

        // Validación 1: Verificar que se crearon todos los materiales
        $this->assertCount(4, $materialIds, 'Deben haberse creado 4 materiales');
        foreach ($materialIds as $materialId) {
            $this->assertGreaterThan(0, $materialId, 'Cada material debe tener un ID válido');
        }

        // Paso 3: Registrar cada material en el reclamo via API
        $datosMaterialesReclamo = [
            [
                'material_id' => $materialIds[0],
                'cantidad' => 2,
                'observacion' => 'Instalación de lámpara LED'
            ],
            [
                'material_id' => $materialIds[1],
                'cantidad' => 10,
                'observacion' => 'Cable necesario para instalación'
            ],
            [
                'material_id' => $materialIds[2],
                'cantidad' => 1,
                'observacion' => 'Poste nuevo para reemplazo'
            ],
            [
                'material_id' => $materialIds[3],
                'cantidad' => 3,
                'observacion' => 'Lámparas de sodio de repuesto'
            ]
        ];

        $materialReclamoIds = [];
        foreach ($datosMaterialesReclamo as $index => $datosMaterialReclamo) {
            $result = $this->withBodyFormat('json')
                          ->post("api/reclamos/{$reclamoId}/materiales", $datosMaterialReclamo);

            // Validación 2: Cada registro se crea exitosamente
            $result->assertStatus(201, "El material {$index} debe registrarse exitosamente");

            // Validación 3: La respuesta contiene el registro creado
            $response = json_decode($result->getJSON(), true);
            $this->assertIsArray($response, 'La respuesta debe ser un array');
            $this->assertArrayHasKey('id', $response, 'Debe tener un ID generado');
            
            $materialReclamoId = $response['id'];
            $materialReclamoIds[] = $materialReclamoId;

            // Validación 4: Los datos coinciden
            $this->assertEquals($reclamoId, $response['reclamo_id'], "El reclamo_id debe coincidir para material {$index}");
            $this->assertEquals($datosMaterialReclamo['material_id'], $response['material_id'], "El material_id debe coincidir para material {$index}");
            $this->assertEquals($datosMaterialReclamo['cantidad'], $response['cantidad'], "La cantidad debe coincidir para material {$index}");
            $this->assertEquals($datosMaterialReclamo['observacion'], $response['observacion'], "La observación debe coincidir para material {$index}");

            // Validación 5: Verificar que incluye información del material (JOIN)
            $this->assertArrayHasKey('material_nombre', $response, "Debe incluir el nombre del material {$index}");
            $this->assertEquals($materialesACrear[$index]['nombre'], $response['material_nombre'], "El nombre del material debe coincidir para material {$index}");
        }

        // Validación 6: Verificar que se crearon todos los registros en BD
        $this->assertCount(4, $materialReclamoIds, 'Deben haberse creado 4 registros material_reclamo');

        // Validación 7: Verificar en BD que todos los materiales están vinculados al reclamo
        $materialesDelReclamoEnBD = $db->table('material_reclamo')
                                      ->where('reclamo_id', $reclamoId)
                                      ->orderBy('id', 'ASC')
                                      ->get()
                                      ->getResultArray();

        $this->assertCount(4, $materialesDelReclamoEnBD, 'Debe haber 4 materiales vinculados al reclamo en BD');

        // Validación 8: Verificar que cada material está correctamente vinculado
        foreach ($materialesDelReclamoEnBD as $index => $registroBD) {
            $this->assertEquals($reclamoId, $registroBD['reclamo_id'], "El reclamo_id en BD debe coincidir para registro {$index}");
            $this->assertEquals($materialIds[$index], $registroBD['material_id'], "El material_id en BD debe coincidir para registro {$index}");
            $this->assertEquals($datosMaterialesReclamo[$index]['cantidad'], $registroBD['cantidad'], "La cantidad en BD debe coincidir para registro {$index}");
            $this->assertEquals($datosMaterialesReclamo[$index]['observacion'], $registroBD['observacion'], "La observación en BD debe coincidir para registro {$index}");
        }

        // Validación 9: Verificar que todos los registros tienen fechas distintas (o válidas)
        foreach ($materialesDelReclamoEnBD as $registroBD) {
            $this->assertArrayHasKey('fecha', $registroBD, 'Cada registro debe tener una fecha');
            $this->assertNotNull($registroBD['fecha'], 'La fecha no debe ser nula');
        }

        // Validación 10: Verificar que se pueden obtener todos los materiales del reclamo via API
        $resultObtenerHistorial = $this->get("api/reclamos/{$reclamoId}/materiales");
        $resultObtenerHistorial->assertStatus(200);

        $historialMateriales = json_decode($resultObtenerHistorial->getJSON(), true);
        $this->assertIsArray($historialMateriales, 'El historial debe ser un array');
        $this->assertCount(4, $historialMateriales, 'El historial debe contener 4 materiales');

        // Validación 11: Verificar que todos los materiales están en el historial con la información correcta
        $materialesEnHistorial = [];
        foreach ($historialMateriales as $materialEnHistorial) {
            $this->assertArrayHasKey('material_id', $materialEnHistorial, 'Debe tener material_id');
            $this->assertArrayHasKey('material_nombre', $materialEnHistorial, 'Debe tener material_nombre');
            $this->assertArrayHasKey('tipo_material_nombre', $materialEnHistorial, 'Debe tener tipo_material_nombre');
            $materialesEnHistorial[$materialEnHistorial['material_id']] = $materialEnHistorial;
        }

        // Validación 12: Verificar que cada material creado está en el historial
        foreach ($materialIds as $index => $materialId) {
            $this->assertArrayHasKey($materialId, $materialesEnHistorial, "El material {$index} debe estar en el historial");
            
            $materialEnHistorial = $materialesEnHistorial[$materialId];
            $this->assertEquals($materialesACrear[$index]['nombre'], $materialEnHistorial['material_nombre'], "El nombre del material {$index} en historial debe coincidir");
            $this->assertEquals($datosMaterialesReclamo[$index]['cantidad'], $materialEnHistorial['cantidad'], "La cantidad del material {$index} en historial debe coincidir");
            $this->assertEquals($datosMaterialesReclamo[$index]['observacion'], $materialEnHistorial['observacion'], "La observación del material {$index} en historial debe coincidir");
        }

        // Validación 13: Verificar que los materiales tienen tipos diferentes correctamente
        $tiposEnHistorial = [];
        foreach ($historialMateriales as $materialEnHistorial) {
            $tiposEnHistorial[] = $materialEnHistorial['tipo_material_nombre'];
        }

        $this->assertContains('Lámpara LED', $tiposEnHistorial, 'Debe incluir Lámpara LED');
        $this->assertContains('Cable Eléctrico', $tiposEnHistorial, 'Debe incluir Cable Eléctrico');
        $this->assertContains('Poste', $tiposEnHistorial, 'Debe incluir Poste');
        $this->assertContains('Lámpara de Sodio', $tiposEnHistorial, 'Debe incluir Lámpara de Sodio');

        // Validación 14: Verificar que no se duplicaron registros (cada material_id aparece una vez)
        $materialIdsEnHistorial = array_column($historialMateriales, 'material_id');
        $materialIdsUnicos = array_unique($materialIdsEnHistorial);
        $this->assertCount(4, $materialIdsUnicos, 'No debe haber materiales duplicados en el historial');

        // Validación 15: Verificar que el historial está ordenado por fecha DESC (más reciente primero)
        $fechasEnHistorial = array_column($historialMateriales, 'fecha');
        $fechasOrdenadas = $fechasEnHistorial;
        rsort($fechasOrdenadas);
        $this->assertEquals($fechasOrdenadas, $fechasEnHistorial, 'El historial debe estar ordenado por fecha DESC');
    }
}

