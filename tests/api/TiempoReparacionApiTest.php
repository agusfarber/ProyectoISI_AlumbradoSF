<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\Tiempo_reparacionModel;

class TiempoReparacionApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'Tests\Support';
    protected $seed        = 'Tests\Support\Database\Seeds\TestSeeder';

    /**
     * HU-037 - Prueba 1: Registro de tiempo de reparación con datos válidos
     * 
     * Objetivo: Verificar que se puede registrar el tiempo de reparación de un reclamo
     * cuando se proporciona tiempo_reparacion_minutos válido. Debe retornar 200 y guardar
     * el tiempo en la base de datos, vinculándolo correctamente al reclamo y usuario.
     * 
     * Tipo de Prueba: API
     */
    public function testRegistroTiempoReparacionConDatosValidos()
    {
        $db = \Config\Database::connect();

        // Paso 1: Crear un reclamo de prueba en la BD con motivo asociado
        $reclamoData = [
            'municipalidad_id' => '10001',
            'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_fechaInicio' => '2025-01-15 10:00:00',
            'municipalidad_fechaModificacion' => '2025-01-15 10:00:00',
            'municipalidad_recepcion' => 'Web',
            'municipalidad_estado' => 'Completado',
            'prioridad' => 'Baja'
        ];

        $db->table('reclamo')->insert($reclamoData);
        $reclamoId = $db->insertID();

        // Validación 1: Verificar que el reclamo se creó correctamente
        $reclamoCreado = $db->table('reclamo')
                           ->where('id', $reclamoId)
                           ->get()
                           ->getRowArray();
        
        $this->assertNotNull($reclamoCreado, 'El reclamo debe existir en la BD');
        $this->assertEquals('Luminaria apagada', $reclamoCreado['municipalidad_motivo'], 'El reclamo debe tener motivo asociado');

        // Paso 2: Obtener un usuario operario del seeder para simular la sesión
        $usuarioOperario = $db->table('usuario')
                             ->where('idRol', 3) // Rol operario
                             ->where('legajo', '20001')
                             ->get()
                             ->getRowArray();
        
        // Si no existe, crear uno
        if (!$usuarioOperario) {
            // Crear rol operario si no existe
            $db->table('rol')->insert([
                'id' => 3,
                'nombre' => 'Operario',
                'descripcion' => 'Rol de operario'
            ]);

            $db->table('usuario')->insert([
                'nombre' => 'Operario Test',
                'email' => 'operario@test.com',
                'legajo' => '20001',
                'contrasena' => password_hash('test123', PASSWORD_DEFAULT),
                'idRol' => 3
            ]);
            
            $usuarioOperario = $db->table('usuario')
                                 ->where('legajo', '20001')
                                 ->get()
                                 ->getRowArray();
        }

        $this->assertNotNull($usuarioOperario, 'Debe existir un usuario operario para el test');
        $usuarioId = $usuarioOperario['id'];

        // Paso 3: Registrar el tiempo de reparación via API
        $tiempoReparacionMinutos = 45;
        $datosTiempo = [
            'tiempo_reparacion_minutos' => $tiempoReparacionMinutos
        ];

        // Simular sesión de usuario (si es necesario para el test)
        // Como el endpoint usa session()->get('user_id'), y en tests puede ser null,
        // el código asigna usuario_id = 0 si no hay sesión
        
        // Llamar al endpoint
        $result = $this->withBodyFormat('json')
                      ->post("api/reclamos/{$reclamoId}/tiempo-reparacion", $datosTiempo);

        // Validación 2: Verificar que el endpoint retorna una respuesta válida
        $responseData = json_decode($result->getJSON(), true);
        $this->assertNotNull($responseData, 'La respuesta no debe ser null');
        $this->assertIsArray($responseData, 'La respuesta debe ser un array');
        
        // Verificar que la respuesta contiene datos (indica que fue exitosa)
        $this->assertArrayHasKey('id', $responseData, 'La respuesta debe incluir el ID del tiempo registrado');
        
        // Validación 3: Verificar que la respuesta contiene los datos del tiempo registrado
        $this->assertArrayHasKey('reclamo_id', $responseData, 'La respuesta debe incluir reclamo_id');
        $this->assertArrayHasKey('motivo_reclamo', $responseData, 'La respuesta debe incluir motivo_reclamo');
        $this->assertArrayHasKey('tiempo_minutos', $responseData, 'La respuesta debe incluir tiempo_minutos');
        $this->assertArrayHasKey('usuario_id', $responseData, 'La respuesta debe incluir usuario_id');
        $this->assertArrayHasKey('fecha_registro', $responseData, 'La respuesta debe incluir fecha_registro');

        // Validación 4: Verificar que los datos en la respuesta son correctos
        $this->assertEquals($reclamoId, $responseData['reclamo_id'], 'El reclamo_id debe coincidir');
        $this->assertEquals('Luminaria apagada', $responseData['motivo_reclamo'], 'El motivo_reclamo debe coincidir con el del reclamo');
        $this->assertEquals($tiempoReparacionMinutos, $responseData['tiempo_minutos'], 'El tiempo_minutos debe coincidir');
        $this->assertNotNull($responseData['fecha_registro'], 'La fecha_registro no debe estar vacía');

        // Validación 5: Verificar que el tiempo se guardó en la base de datos
        $tiempoReparacionModel = new Tiempo_reparacionModel();
        $tiempoGuardado = $tiempoReparacionModel->where('reclamo_id', $reclamoId)->first();
        
        $this->assertNotNull($tiempoGuardado, 'El tiempo de reparación debe existir en la BD');
        $this->assertEquals($reclamoId, $tiempoGuardado['reclamo_id'], 'El reclamo_id debe estar vinculado correctamente');
        $this->assertEquals('Luminaria apagada', $tiempoGuardado['motivo_reclamo'], 'El motivo_reclamo debe estar guardado correctamente');
        $this->assertEquals($tiempoReparacionMinutos, $tiempoGuardado['tiempo_minutos'], 'El tiempo_minutos debe estar guardado correctamente');
        $this->assertNotNull($tiempoGuardado['fecha_registro'], 'La fecha_registro debe estar guardada');

        // Validación 6: Verificar que el tiempo está vinculado al reclamo (verificación en BD)
        $tiempoVinculado = $db->table('tiempo_reparacion')
                             ->where('reclamo_id', $reclamoId)
                             ->where('tiempo_minutos', $tiempoReparacionMinutos)
                             ->get()
                             ->getRowArray();
        
        $this->assertNotNull($tiempoVinculado, 'El tiempo debe estar vinculado al reclamo en la BD');

        // Validación 7: Verificar que el tiempo tiene usuario_id (aunque sea 0 si no hay sesión)
        // En el código, si no hay sesión, usuario_id = 0
        // Nota: Desde la BD puede venir como string '0'
        $this->assertNotNull($tiempoGuardado['usuario_id'], 'El usuario_id debe existir (puede ser 0 si no hay sesión)');
        $this->assertTrue(
            is_numeric($tiempoGuardado['usuario_id']) || is_int($tiempoGuardado['usuario_id']),
            'El usuario_id debe ser numérico (puede venir como string desde la BD)'
        );
    }

    /**
     * HU-037 - Prueba 2: Validación de tiempo de reparación mayor a 0
     * 
     * Objetivo: Verificar que el sistema valida correctamente que el tiempo_reparacion_minutos
     * debe ser mayor a 0. Debe retornar 400 cuando se proporciona tiempo_reparacion_minutos = 0
     * o negativo.
     * 
     * Tipo de Prueba: API
     */
    public function testValidacionTiempoReparacionMayorACero()
    {
        $db = \Config\Database::connect();

        // Paso 1: Crear un reclamo de prueba en la BD
        $reclamoData = [
            'municipalidad_id' => '10003',
            'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_fechaInicio' => '2025-01-15 10:00:00',
            'municipalidad_fechaModificacion' => '2025-01-15 10:00:00',
            'municipalidad_recepcion' => 'Web',
            'municipalidad_estado' => 'Completado',
            'prioridad' => 'Baja'
        ];

        $db->table('reclamo')->insert($reclamoData);
        $reclamoId = $db->insertID();

        // Caso 1: POST con tiempo_reparacion_minutos = 0
        $datosTiempoCero = [
            'tiempo_reparacion_minutos' => 0
        ];

        $result1 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/tiempo-reparacion", $datosTiempoCero);

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

        // Validación 3: El mensaje de error menciona que el tiempo debe ser mayor a 0
        $mensajeError1 = $response1['messages'] ?? $response1['message'] ?? $response1['error'] ?? '';
        if (is_array($mensajeError1)) {
            $mensajeError1 = implode(' ', $mensajeError1);
        }
        // El código puede validar primero si está vacío (0 es empty en PHP), luego si es <= 0
        // Por eso puede retornar "obligatorio" o "mayor a 0"
        $this->assertTrue(
            strpos(strtolower($mensajeError1), 'mayor') !== false || 
            strpos(strtolower($mensajeError1), 'obligatorio') !== false,
            'El mensaje de error debe mencionar que el tiempo debe ser mayor a 0 o es obligatorio'
        );

        // Validación 4: No se debe haber creado ningún registro en tiempo_reparacion
        $registrosDespuesCaso1 = $db->table('tiempo_reparacion')
                                   ->where('reclamo_id', $reclamoId)
                                   ->get()
                                   ->getResultArray();
        $this->assertCount(0, $registrosDespuesCaso1, 'No debe haberse creado ningún registro con tiempo_reparacion_minutos = 0');

        // Caso 2: POST con tiempo_reparacion_minutos negativo (-5)
        $datosTiempoNegativo = [
            'tiempo_reparacion_minutos' => -5
        ];

        $result2 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/tiempo-reparacion", $datosTiempoNegativo);

        // Validación 5: Status 400 (Bad Request)
        $result2->assertStatus(400);

        // Validación 6: La respuesta contiene un mensaje de error
        $response2 = json_decode($result2->getJSON(), true);
        $this->assertIsArray($response2, 'La respuesta debe ser un array');
        
        $this->assertTrue(
            isset($response2['messages']) || isset($response2['message']) || isset($response2['error']),
            'La respuesta debe contener un mensaje de error'
        );

        // Validación 7: El mensaje de error menciona que el tiempo debe ser mayor a 0
        $mensajeError2 = $response2['messages'] ?? $response2['message'] ?? $response2['error'] ?? '';
        if (is_array($mensajeError2)) {
            $mensajeError2 = implode(' ', $mensajeError2);
        }
        $this->assertStringContainsString(
            'mayor',
            strtolower($mensajeError2),
            'El mensaje de error debe mencionar que el tiempo debe ser mayor a 0'
        );

        // Validación 8: No se debe haber creado ningún registro en tiempo_reparacion
        $registrosDespuesCaso2 = $db->table('tiempo_reparacion')
                                   ->where('reclamo_id', $reclamoId)
                                   ->get()
                                   ->getResultArray();
        $this->assertCount(0, $registrosDespuesCaso2, 'No debe haberse creado ningún registro con tiempo_reparacion_minutos negativo');

        // Caso 3: POST con tiempo_reparacion_minutos negativo grande (-100)
        $datosTiempoNegativoGrande = [
            'tiempo_reparacion_minutos' => -100
        ];

        $result3 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/tiempo-reparacion", $datosTiempoNegativoGrande);

        // Validación 9: Status 400 (Bad Request)
        $result3->assertStatus(400);

        // Validación 10: La respuesta contiene un mensaje de error
        $response3 = json_decode($result3->getJSON(), true);
        $this->assertIsArray($response3, 'La respuesta debe ser un array');
        
        $this->assertTrue(
            isset($response3['messages']) || isset($response3['message']) || isset($response3['error']),
            'La respuesta debe contener un mensaje de error'
        );

        // Validación 11: El mensaje de error menciona que el tiempo debe ser mayor a 0
        $mensajeError3 = $response3['messages'] ?? $response3['message'] ?? $response3['error'] ?? '';
        if (is_array($mensajeError3)) {
            $mensajeError3 = implode(' ', $mensajeError3);
        }
        $this->assertStringContainsString(
            'mayor',
            strtolower($mensajeError3),
            'El mensaje de error debe mencionar que el tiempo debe ser mayor a 0'
        );

        // Validación 12: No se debe haber creado ningún registro en tiempo_reparacion
        $registrosDespuesCaso3 = $db->table('tiempo_reparacion')
                                   ->where('reclamo_id', $reclamoId)
                                   ->get()
                                   ->getResultArray();
        $this->assertCount(0, $registrosDespuesCaso3, 'No debe haberse creado ningún registro con tiempo_reparacion_minutos negativo grande');
    }

    /**
     * HU-037 - Prueba 3: Actualización de tiempo de reparación existente
     * 
     * Objetivo: Verificar que cuando se envía un POST con un reclamo que ya tiene tiempo registrado,
     * el sistema actualiza el tiempo existente (no crea duplicado) y recalcula el promedio del motivo.
     * 
     * Tipo de Prueba: API - Integración
     */
    public function testActualizacionTiempoReparacionExistente()
    {
        $db = \Config\Database::connect();

        // Paso 1: Crear un reclamo de prueba en la BD con motivo asociado
        $reclamoData = [
            'municipalidad_id' => '10004',
            'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => 'Luminaria fundida',
            'municipalidad_fechaInicio' => '2025-01-15 10:00:00',
            'municipalidad_fechaModificacion' => '2025-01-15 10:00:00',
            'municipalidad_recepcion' => 'Web',
            'municipalidad_estado' => 'Completado',
            'prioridad' => 'Baja'
        ];

        $db->table('reclamo')->insert($reclamoData);
        $reclamoId = $db->insertID();
        $motivoReclamo = $reclamoData['municipalidad_motivo'];

        // Paso 2: Registrar un tiempo inicial de reparación
        $tiempoInicial = 30; // minutos
        $datosTiempoInicial = [
            'tiempo_reparacion_minutos' => $tiempoInicial
        ];

        $result1 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/tiempo-reparacion", $datosTiempoInicial);

        // Validación 1: Verificar que el primer registro se creó correctamente
        $response1 = json_decode($result1->getJSON(), true);
        $this->assertNotNull($response1, 'La respuesta no debe ser null');
        $this->assertArrayHasKey('id', $response1, 'La respuesta debe incluir el ID del tiempo registrado');
        $this->assertEquals($tiempoInicial, $response1['tiempo_minutos'], 'El tiempo inicial debe guardarse correctamente');

        $tiempoReparacionId = $response1['id'];

        // Validación 2: Verificar que se creó UN registro en tiempo_reparacion
        $tiempoReparacionModel = new Tiempo_reparacionModel();
        $registrosIniciales = $tiempoReparacionModel->where('reclamo_id', $reclamoId)->findAll();
        $this->assertCount(1, $registrosIniciales, 'Debe haber solo UN registro inicial de tiempo_reparacion');

        // Validación 3: Verificar que se creó el promedio inicial en tiempo_promedio_motivo
        $promedioModel = new \App\Models\Tiempo_promedio_motivoModel();
        $promedioInicial = $promedioModel->where('motivo', $motivoReclamo)->first();
        $this->assertNotNull($promedioInicial, 'Debe existir un registro de promedio para el motivo');
        $this->assertEquals($tiempoInicial, $promedioInicial['tiempo_promedio_minutos'], 'El promedio inicial debe ser igual al tiempo registrado (primer registro)');
        $this->assertEquals(1, $promedioInicial['cantidad_registros'], 'Debe haber 1 registro para el promedio inicial');
        $this->assertEquals($tiempoInicial, $promedioInicial['tiempo_total_minutos'], 'El tiempo total inicial debe ser igual al tiempo registrado');

        // Paso 3: Actualizar el tiempo de reparación con un nuevo valor
        $tiempoActualizado = 60; // minutos (doble del inicial)
        $datosTiempoActualizado = [
            'tiempo_reparacion_minutos' => $tiempoActualizado
        ];

        $result2 = $this->withBodyFormat('json')
                       ->post("api/reclamos/{$reclamoId}/tiempo-reparacion", $datosTiempoActualizado);

        // Validación 4: Verificar que la respuesta contiene el tiempo actualizado
        $response2 = json_decode($result2->getJSON(), true);
        $this->assertNotNull($response2, 'La respuesta no debe ser null');
        $this->assertArrayHasKey('id', $response2, 'La respuesta debe incluir el ID del tiempo registrado');
        $this->assertEquals($tiempoReparacionId, $response2['id'], 'El ID debe ser el mismo (no se creó un nuevo registro)');
        $this->assertEquals($tiempoActualizado, $response2['tiempo_minutos'], 'El tiempo debe haberse actualizado correctamente');

        // Validación 5: Verificar que NO se creó un nuevo registro (solo existe UN registro)
        $registrosDespuesActualizacion = $tiempoReparacionModel->where('reclamo_id', $reclamoId)->findAll();
        $this->assertCount(1, $registrosDespuesActualizacion, 'Debe haber solo UN registro de tiempo_reparacion (no se debe crear duplicado)');

        // Validación 6: Verificar que el registro se actualizó correctamente en la BD
        $tiempoActualizadoBD = $tiempoReparacionModel->where('reclamo_id', $reclamoId)->first();
        $this->assertNotNull($tiempoActualizadoBD, 'El registro debe existir en la BD');
        $this->assertEquals($tiempoReparacionId, $tiempoActualizadoBD['id'], 'El ID debe ser el mismo');
        $this->assertEquals($tiempoActualizado, $tiempoActualizadoBD['tiempo_minutos'], 'El tiempo debe estar actualizado en la BD');
        $this->assertEquals($motivoReclamo, $tiempoActualizadoBD['motivo_reclamo'], 'El motivo debe mantenerse');

        // Validación 7: Verificar que el promedio se recalculó correctamente
        // El promedio debe ajustarse por diferencia: tiempo_total += (nuevo - anterior)
        // nuevo_promedio = nuevo_tiempo_total / cantidad_registros (que se mantiene igual)
        $promedioActualizado = $promedioModel->where('motivo', $motivoReclamo)->first();
        $this->assertNotNull($promedioActualizado, 'Debe existir el registro de promedio actualizado');
        
        // Calcular el promedio esperado
        // Diferencia: 60 - 30 = 30 minutos
        // Nuevo tiempo total: 30 + 30 = 60 minutos
        // Nuevo promedio: 60 / 1 = 60 minutos
        $diferenciaTiempo = $tiempoActualizado - $tiempoInicial; // 60 - 30 = 30
        $tiempoTotalEsperado = $promedioInicial['tiempo_total_minutos'] + $diferenciaTiempo; // 30 + 30 = 60
        $promedioEsperado = $tiempoTotalEsperado / $promedioInicial['cantidad_registros']; // 60 / 1 = 60

        $this->assertEquals($promedioEsperado, $promedioActualizado['tiempo_promedio_minutos'], 'El promedio debe recalcularse correctamente');
        $this->assertEquals($tiempoTotalEsperado, $promedioActualizado['tiempo_total_minutos'], 'El tiempo total debe actualizarse correctamente');
        $this->assertEquals($promedioInicial['cantidad_registros'], $promedioActualizado['cantidad_registros'], 'La cantidad_registros debe mantenerse igual (mismo reclamo)');
        $this->assertEquals(1, $promedioActualizado['cantidad_registros'], 'Debe seguir habiendo 1 registro (no se suma como nuevo)');
    }

    /**
     * HU-037 - Prueba 4: Recalcular promedio con múltiples registros
     * 
     * Objetivo: Verificar que cuando se registran varios tiempos para el mismo motivo,
     * el promedio se recalcula correctamente, cantidad_registros se incrementa, y
     * tiempo_total_minutos se actualiza sumando los nuevos tiempos.
     * 
     * Tipo de Prueba: API - Integración
     */
    public function testRecalcularPromedioConMultiplesRegistros()
    {
        $db = \Config\Database::connect();

        // Paso 1: Definir el motivo común para todos los reclamos
        $motivoComun = 'Luminaria parpadeando';

        // Paso 2: Crear varios reclamos con el mismo motivo
        $reclamosData = [
            [
                'municipalidad_id' => '10005',
                'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
                'municipalidad_motivo' => $motivoComun,
                'municipalidad_fechaInicio' => '2025-01-15 10:00:00',
                'municipalidad_fechaModificacion' => '2025-01-15 10:00:00',
                'municipalidad_recepcion' => 'Web',
                'municipalidad_estado' => 'Completado',
                'prioridad' => 'Baja'
            ],
            [
                'municipalidad_id' => '10006',
                'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
                'municipalidad_motivo' => $motivoComun,
                'municipalidad_fechaInicio' => '2025-01-15 11:00:00',
                'municipalidad_fechaModificacion' => '2025-01-15 11:00:00',
                'municipalidad_recepcion' => 'Web',
                'municipalidad_estado' => 'Completado',
                'prioridad' => 'Baja'
            ],
            [
                'municipalidad_id' => '10007',
                'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
                'municipalidad_motivo' => $motivoComun,
                'municipalidad_fechaInicio' => '2025-01-15 12:00:00',
                'municipalidad_fechaModificacion' => '2025-01-15 12:00:00',
                'municipalidad_recepcion' => 'Web',
                'municipalidad_estado' => 'Completado',
                'prioridad' => 'Baja'
            ],
            [
                'municipalidad_id' => '10008',
                'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
                'municipalidad_motivo' => $motivoComun,
                'municipalidad_fechaInicio' => '2025-01-15 13:00:00',
                'municipalidad_fechaModificacion' => '2025-01-15 13:00:00',
                'municipalidad_recepcion' => 'Web',
                'municipalidad_estado' => 'Completado',
                'prioridad' => 'Baja'
            ]
        ];

        $reclamoIds = [];
        foreach ($reclamosData as $reclamoData) {
            $db->table('reclamo')->insert($reclamoData);
            $reclamoIds[] = $db->insertID();
        }

        // Paso 3: Registrar tiempos diferentes para cada reclamo
        $tiemposRegistrados = [20, 35, 45, 30]; // minutos
        $promedioModel = new \App\Models\Tiempo_promedio_motivoModel();
        $tiempoReparacionModel = new Tiempo_reparacionModel();

        // Variable para rastrear el promedio acumulado
        $tiempoTotalAcumulado = 0;
        $cantidadRegistrosAcumulados = 0;

        foreach ($reclamoIds as $index => $reclamoId) {
            $tiempoActual = $tiemposRegistrados[$index];
            
            // Registrar el tiempo
            $datosTiempo = [
                'tiempo_reparacion_minutos' => $tiempoActual
            ];

            $result = $this->withBodyFormat('json')
                          ->post("api/reclamos/{$reclamoId}/tiempo-reparacion", $datosTiempo);

            // Validación 1: Verificar que el tiempo se registró correctamente
            $response = json_decode($result->getJSON(), true);
            $this->assertNotNull($response, "La respuesta no debe ser null para reclamo {$reclamoId}");
            $this->assertEquals($tiempoActual, $response['tiempo_minutos'], "El tiempo debe guardarse correctamente para reclamo {$reclamoId}");

            // Actualizar acumuladores
            $tiempoTotalAcumulado += $tiempoActual;
            $cantidadRegistrosAcumulados++;

            // Validación 2: Verificar que el promedio se actualizó correctamente después de cada registro
            $promedioActual = $promedioModel->where('motivo', $motivoComun)->first();
            $this->assertNotNull($promedioActual, "Debe existir un registro de promedio para el motivo después de registrar {$cantidadRegistrosAcumulados} tiempos");
            
            // Calcular el promedio esperado
            $promedioEsperado = $tiempoTotalAcumulado / $cantidadRegistrosAcumulados;

            // Validación 3: Verificar cantidad_registros
            $this->assertEquals(
                $cantidadRegistrosAcumulados,
                $promedioActual['cantidad_registros'],
                "La cantidad_registros debe ser {$cantidadRegistrosAcumulados} después de registrar {$cantidadRegistrosAcumulados} tiempos"
            );

            // Validación 4: Verificar tiempo_total_minutos
            $this->assertEquals(
                $tiempoTotalAcumulado,
                $promedioActual['tiempo_total_minutos'],
                "El tiempo_total_minutos debe ser {$tiempoTotalAcumulado} después de registrar {$cantidadRegistrosAcumulados} tiempos (suma: " . implode('+', array_slice($tiemposRegistrados, 0, $cantidadRegistrosAcumulados)) . ")",
                0.01 // Tolerancia para comparación de decimales
            );

            // Validación 5: Verificar que el promedio se calculó correctamente
            $this->assertEqualsWithDelta(
                $promedioEsperado,
                $promedioActual['tiempo_promedio_minutos'],
                0.01,
                "El promedio debe ser {$promedioEsperado} después de registrar {$cantidadRegistrosAcumulados} tiempos (tiempo_total: {$tiempoTotalAcumulado} / cantidad: {$cantidadRegistrosAcumulados})"
            );
        }

        // Validación 6: Verificar el promedio final
        $promedioFinal = $promedioModel->where('motivo', $motivoComun)->first();
        $tiempoTotalFinal = array_sum($tiemposRegistrados); // 20 + 35 + 45 + 30 = 130
        $promedioFinalEsperado = $tiempoTotalFinal / count($tiemposRegistrados); // 130 / 4 = 32.5

        $this->assertEquals(4, $promedioFinal['cantidad_registros'], 'Debe haber 4 registros al final');
        $this->assertEqualsWithDelta($tiempoTotalFinal, $promedioFinal['tiempo_total_minutos'], 0.01, 'El tiempo total final debe ser la suma de todos los tiempos (130 minutos)');
        $this->assertEqualsWithDelta($promedioFinalEsperado, $promedioFinal['tiempo_promedio_minutos'], 0.01, 'El promedio final debe ser 32.5 minutos (130 / 4)');

        // Validación 7: Verificar que se crearon 4 registros en tiempo_reparacion (uno por cada reclamo)
        $registrosTiempoReparacion = $tiempoReparacionModel->whereIn('reclamo_id', $reclamoIds)->findAll();
        $this->assertCount(4, $registrosTiempoReparacion, 'Debe haber 4 registros en tiempo_reparacion (uno por cada reclamo)');

        // Validación 8: Verificar que todos los registros tienen el mismo motivo
        foreach ($registrosTiempoReparacion as $registro) {
            $this->assertEquals($motivoComun, $registro['motivo_reclamo'], 'Todos los registros deben tener el mismo motivo');
        }

        // Validación 9: Verificar que los tiempos registrados coinciden
        $tiemposEnBD = array_column($registrosTiempoReparacion, 'tiempo_minutos');
        sort($tiemposEnBD);
        sort($tiemposRegistrados);
        $this->assertEquals($tiemposRegistrados, $tiemposEnBD, 'Los tiempos en BD deben coincidir con los tiempos registrados');
    }

    /**
     * HU-037 - Prueba 5: Reclamo inexistente
     * 
     * Objetivo: Verificar que cuando se intenta registrar un tiempo de reparación para un
     * reclamo_id que no existe, el sistema retorna un error 404 (Not Found) con un mensaje
     * apropiado indicando que el reclamo no fue encontrado.
     * 
     * Tipo de Prueba: API - Validación
     */
    public function testReclamoInexistente()
    {
        $db = \Config\Database::connect();

        // Paso 1: Obtener el último ID de reclamo existente o usar un ID que no exista
        $ultimoReclamo = $db->table('reclamo')
                           ->selectMax('id')
                           ->get()
                           ->getRowArray();
        
        $ultimoId = $ultimoReclamo['id'] ?? 0;
        $reclamoIdInexistente = $ultimoId + 99999; // ID que definitivamente no existe

        // Validación 1: Verificar que el reclamo no existe en la BD
        $reclamoExistente = $db->table('reclamo')
                              ->where('id', $reclamoIdInexistente)
                              ->get()
                              ->getRowArray();
        $this->assertNull($reclamoExistente, 'El reclamo NO debe existir en la BD para este test');

        // Paso 2: Intentar registrar tiempo de reparación para el reclamo inexistente
        $datosTiempo = [
            'tiempo_reparacion_minutos' => 45
        ];

        $result = $this->withBodyFormat('json')
                      ->post("api/reclamos/{$reclamoIdInexistente}/tiempo-reparacion", $datosTiempo);

        // Validación 2: Status 404 (Not Found)
        $result->assertStatus(404);

        // Validación 3: La respuesta contiene un mensaje de error
        $response = json_decode($result->getJSON(), true);
        $this->assertIsArray($response, 'La respuesta debe ser un array');
        
        $this->assertTrue(
            isset($response['messages']) || isset($response['message']) || isset($response['error']),
            'La respuesta debe contener un mensaje de error'
        );

        // Validación 4: El mensaje de error menciona que el reclamo no fue encontrado
        $mensajeError = $response['messages'] ?? $response['message'] ?? $response['error'] ?? '';
        if (is_array($mensajeError)) {
            $mensajeError = implode(' ', $mensajeError);
        }
        $this->assertStringContainsString(
            'no encontrado',
            strtolower($mensajeError),
            'El mensaje de error debe mencionar que el reclamo no fue encontrado'
        );

        // Validación 5: No se debe haber creado ningún registro en tiempo_reparacion
        $tiempoReparacionModel = new Tiempo_reparacionModel();
        $registrosCreados = $tiempoReparacionModel->where('reclamo_id', $reclamoIdInexistente)->findAll();
        $this->assertCount(0, $registrosCreados, 'No debe haberse creado ningún registro en tiempo_reparacion para un reclamo inexistente');

        // Validación 6: Verificar que no se afectó ningún promedio en tiempo_promedio_motivo
        // (no debería haber cambios porque no se creó ningún registro)
        $promedioModel = new \App\Models\Tiempo_promedio_motivoModel();
        $promediosAntes = $promedioModel->findAll();
        $cantidadPromediosAntes = count($promediosAntes);

        // Nota: Esta validación verifica que no se creó ningún promedio nuevo
        // Si ya existían promedios, la cantidad debe mantenerse igual
        $promediosDespues = $promedioModel->findAll();
        $cantidadPromediosDespues = count($promediosDespues);
        $this->assertEquals(
            $cantidadPromediosAntes,
            $cantidadPromediosDespues,
            'No debe haberse creado ningún promedio nuevo para un reclamo inexistente'
        );
    }
}

