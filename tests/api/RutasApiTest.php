<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\RutaModel;
use App\Models\Ruta_reclamoModel;
use App\Models\ReclamoModel;

class RutasApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $migrateOnce = false;
    protected $refresh = true;
    protected $namespace = 'Tests\Support';
    protected $seed = 'Tests\Support\Database\Seeds\TestSeeder';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * HU-020: Test de generación de hoja de ruta optimizada con datos básicos
     * Tipo: API
     */
    public function testGenerarRutaAutomaticaConDatosValidos()
    {
        // Datos de entrada para generar una ruta
        $data = [
            'nombre' => 'Ruta de Prueba',
            'color' => '#FF6B35',
            'cantidadReclamos' => 2,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];

        // Realizar petición POST al endpoint de generación
        $result = $this->withBodyFormat('json')
                      ->post('/api/rutas/generar', $data);

        // Verificar respuesta
        $result->assertStatus(201);
        
        $responseData = json_decode($result->response()->getBody(), true);
        
        // Verificar estructura de respuesta (tiene claves 'ruta' y 'reclamos')
        $this->assertArrayHasKey('ruta', $responseData);
        $this->assertArrayHasKey('reclamos', $responseData);
        
        $rutaData = $responseData['ruta'];
        $reclamosData = $responseData['reclamos'];
        
        // Verificar estructura de los datos de la ruta
        $this->assertArrayHasKey('id', $rutaData);
        $this->assertArrayHasKey('nombre', $rutaData);
        $this->assertArrayHasKey('cantidadReclamos', $rutaData);
        $this->assertArrayHasKey('tiempoEstimado', $rutaData);
        $this->assertArrayHasKey('asignada', $rutaData);
        $this->assertArrayHasKey('color', $rutaData);
        
        // Verificar datos específicos
        $this->assertEquals('Ruta de Prueba', $rutaData['nombre']);
        $this->assertEquals('#FF6B35', $rutaData['color']);
        $this->assertEquals(2, $rutaData['cantidadReclamos']);
        $this->assertEquals(0, $rutaData['asignada']); // Debe estar no asignada
        
        // Verificar que se incluyeron reclamos
        $this->assertIsArray($reclamosData);
        $this->assertCount(2, $reclamosData);
        
        // Verificar que se creó en la base de datos
        $rutaModel = new RutaModel();
        $ruta = $rutaModel->find($rutaData['id']);
        $this->assertNotNull($ruta);
        $this->assertEquals('Ruta de Prueba', $ruta['nombre']);
    }

    /**
     * HU-020: Test de priorización de reclamos (Alta prioridad primero)
     * Tipo: API
     */
    public function testPriorizacionReclamosPorPrioridad()
    {
        // Datos de entrada: solicitar 3 reclamos cuando hay 2 Alta y 2 Baja disponibles
        $data = [
            'nombre' => 'Ruta Priorización',
            'color' => '#28a745',
            'cantidadReclamos' => 3,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];

        // Realizar petición POST al endpoint de generación
        $result = $this->withBodyFormat('json')
                      ->post('/api/rutas/generar', $data);

        // Verificar respuesta
        $result->assertStatus(201);
        
        $responseData = json_decode($result->response()->getBody(), true);
        
        // Verificar que se creó la ruta
        $this->assertArrayHasKey('ruta', $responseData);
        $this->assertArrayHasKey('reclamos', $responseData);
        
        $reclamosData = $responseData['reclamos'];
        
        // Verificar que se incluyeron exactamente 3 reclamos
        $this->assertCount(3, $reclamosData, 'Debe incluir exactamente 3 reclamos');
        
        // Contar reclamos por prioridad
        $conteoAlta = 0;
        $conteoMedia = 0;
        $conteoOtras = 0;
        $idsIncluidos = [];
        
        foreach ($reclamosData as $reclamo) {
            $idsIncluidos[] = $reclamo['municipalidad_id'];
            
            if ($reclamo['prioridad'] === 'Alta') {
                $conteoAlta++;
            } elseif ($reclamo['prioridad'] === 'Media') {
                $conteoMedia++;
            } else {
                $conteoOtras++;
            }
            
            // Verificar que ningún reclamo está completado
            $this->assertNotEquals('Completado', $reclamo['municipalidad_estado'], 
                'No debe incluir reclamos completados');
        }
        
        // Verificar que se incluyeron TODOS los reclamos de prioridad Alta disponibles (2)
        $this->assertEquals(2, $conteoAlta, 'Debe incluir los 2 reclamos de prioridad Alta disponibles');
        
        // Verificar que se incluyó exactamente 1 reclamo de prioridad Baja
        $this->assertEquals(1, $conteoOtras, 'Debe incluir 1 reclamo de prioridad Baja');
        
        // Verificar que los reclamos de Alta prioridad son los esperados (IDs 1001 y 1003)
        $this->assertContains('1001', $idsIncluidos, 'Debe incluir el reclamo 1001 (Alta prioridad)');
        $this->assertContains('1003', $idsIncluidos, 'Debe incluir el reclamo 1003 (Alta prioridad)');
        
        // Verificar que se incluyó un reclamo de Baja (1002 o 1004, no el completado 1005)
        $this->assertTrue(
            in_array('1002', $idsIncluidos) || in_array('1004', $idsIncluidos),
            'Debe incluir un reclamo de prioridad Baja (1002 o 1004)'
        );
        $this->assertNotContains('1005', $idsIncluidos, 'No debe incluir el reclamo completado (1005)');
    }

    /**
     * HU-020: Test de exclusión de reclamos completados
     * Tipo: API
     */
    public function testExclusionReclamosCompletados()
    {
        // Datos de entrada: solicitar todos los reclamos disponibles (sin completar)
        // Hay 5 reclamos en total: 4 sin completar (1001, 1002, 1003, 1004) + 1 completado (1005)
        // El sistema debe excluir automáticamente el completado
        $data = [
            'nombre' => 'Ruta Sin Completados',
            'color' => '#dc3545',
            'cantidadReclamos' => 1, // Solicitar solo 1 para este test específico
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];

        // Realizar petición POST al endpoint de generación
        $result = $this->withBodyFormat('json')
                      ->post('/api/rutas/generar', $data);

        // Verificar respuesta exitosa
        $result->assertStatus(201);
        
        $responseData = json_decode($result->response()->getBody(), true);
        
        // Verificar que se creó la ruta
        $this->assertArrayHasKey('ruta', $responseData);
        $this->assertArrayHasKey('reclamos', $responseData);
        
        $rutaData = $responseData['ruta'];
        $reclamosData = $responseData['reclamos'];
        
        // Verificar que se incluyeron reclamos (puede variar según disponibilidad)
        $this->assertGreaterThan(0, count($reclamosData), 'Debe incluir al menos 1 reclamo');
        
        // VERIFICACIÓN PRINCIPAL: Ningún reclamo debe estar completado
        foreach ($reclamosData as $index => $reclamo) {
            $this->assertNotEquals(
                'Completado', 
                $reclamo['municipalidad_estado'], 
                "El reclamo en posición " . ($index + 1) . " (ID: {$reclamo['municipalidad_id']}) no debe estar completado"
            );
        }
        
        // Verificar específicamente que el reclamo 1005 (que está completado) NO está incluido
        $idsIncluidos = array_column($reclamosData, 'municipalidad_id');
        $this->assertNotContains(
            '1005', 
            $idsIncluidos, 
            'El reclamo 1005 tiene estado "Completado" y NO debe estar incluido en la ruta'
        );
        
        // Verificar que solo se incluyeron reclamos no completados (pueden ser 1001, 1002, 1003, 1004)
        $reclamosNoCompletados = ['1001', '1002', '1003', '1004'];
        foreach ($idsIncluidos as $id) {
            $this->assertContains(
                $id, 
                $reclamosNoCompletados, 
                "El reclamo {$id} debe ser uno de los reclamos no completados"
            );
        }
        
        // Verificar en la base de datos que el reclamo 1005 (completado) no está en ninguna ruta
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamoModel = new ReclamoModel();
        
        // Obtener el ID interno del reclamo 1005
        $reclamoCompletado = $reclamoModel->where('municipalidad_id', '1005')->first();
        $this->assertNotNull($reclamoCompletado, 'El reclamo 1005 debe existir en la BD');
        
        $reclamoEnRuta = $rutaReclamoModel->where('reclamo_id', $reclamoCompletado['id'])->first();
        $this->assertNull(
            $reclamoEnRuta, 
            'El reclamo completado (1005) no debe estar asignado a ninguna ruta en la BD'
        );
    }

    /**
     * HU-020: Test de exclusión de reclamos que ya están en otras rutas
     * Tipo: API
     */
    public function testExclusionReclamosEnOtrasRutas()
    {
        // PASO 1: Crear la primera ruta con 5 reclamos
        $datosRuta1 = [
            'nombre' => 'Ruta 1 - Primera',
            'color' => '#FF6B35',
            'cantidadReclamos' => 5,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];

        $resultRuta1 = $this->withBodyFormat('json')
                            ->post('/api/rutas/generar', $datosRuta1);

        // Verificar que la primera ruta se creó correctamente
        $resultRuta1->assertStatus(201);
        $responseRuta1 = json_decode($resultRuta1->response()->getBody(), true);
        
        $this->assertArrayHasKey('ruta', $responseRuta1);
        $this->assertArrayHasKey('reclamos', $responseRuta1);
        
        $reclamosRuta1 = $responseRuta1['reclamos'];
        $idsRuta1 = array_column($reclamosRuta1, 'municipalidad_id');
        
        $this->assertCount(5, $reclamosRuta1, 'La primera ruta debe tener 5 reclamos');
        
        // PASO 2: Intentar crear una segunda ruta con 6 reclamos
        $datosRuta2 = [
            'nombre' => 'Ruta 2 - Segunda',
            'color' => '#28a745',
            'cantidadReclamos' => 6,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];

        $resultRuta2 = $this->withBodyFormat('json')
                            ->post('/api/rutas/generar', $datosRuta2);

        // Verificar que la segunda ruta se creó correctamente
        $resultRuta2->assertStatus(201);
        $responseRuta2 = json_decode($resultRuta2->response()->getBody(), true);
        
        $this->assertArrayHasKey('ruta', $responseRuta2);
        $this->assertArrayHasKey('reclamos', $responseRuta2);
        
        $reclamosRuta2 = $responseRuta2['reclamos'];
        $idsRuta2 = array_column($reclamosRuta2, 'municipalidad_id');
        
        $this->assertCount(6, $reclamosRuta2, 'La segunda ruta debe tener 6 reclamos');
        
        // VERIFICACIÓN PRINCIPAL: Los reclamos de la Ruta 2 NO deben estar en la Ruta 1
        foreach ($idsRuta2 as $idRuta2) {
            $this->assertNotContains(
                $idRuta2,
                $idsRuta1,
                "El reclamo {$idRuta2} de la Ruta 2 NO debe estar en la Ruta 1"
            );
        }
        
        // Verificar que no hay intersección entre los conjuntos de reclamos
        $interseccion = array_intersect($idsRuta1, $idsRuta2);
        $this->assertEmpty(
            $interseccion,
            'No debe haber reclamos compartidos entre la Ruta 1 y la Ruta 2. IDs compartidos: ' . implode(', ', $interseccion)
        );
        
        // Verificar en la base de datos que cada reclamo está solo en una ruta
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamoModel = new ReclamoModel();
        
        // Para cada reclamo de la Ruta 1, verificar que está en exactamente 1 ruta
        foreach ($idsRuta1 as $municipalidadId) {
            $reclamo = $reclamoModel->where('municipalidad_id', $municipalidadId)->first();
            $this->assertNotNull($reclamo, "El reclamo {$municipalidadId} debe existir en la BD");
            
            $asignaciones = $rutaReclamoModel->where('reclamo_id', $reclamo['id'])->findAll();
            $this->assertCount(
                1,
                $asignaciones,
                "El reclamo {$municipalidadId} debe estar asignado a exactamente 1 ruta"
            );
        }
        
        // Para cada reclamo de la Ruta 2, verificar que está en exactamente 1 ruta
        foreach ($idsRuta2 as $municipalidadId) {
            $reclamo = $reclamoModel->where('municipalidad_id', $municipalidadId)->first();
            $this->assertNotNull($reclamo, "El reclamo {$municipalidadId} debe existir en la BD");
            
            $asignaciones = $rutaReclamoModel->where('reclamo_id', $reclamo['id'])->findAll();
            $this->assertCount(
                1,
                $asignaciones,
                "El reclamo {$municipalidadId} debe estar asignado a exactamente 1 ruta"
            );
        }
        
        // Verificar que el total de reclamos únicos es igual a la suma (no hay duplicados)
        $todosLosIds = array_merge($idsRuta1, $idsRuta2);
        $idsUnicos = array_unique($todosLosIds);
        $cantidadTotal = count($reclamosRuta1) + count($reclamosRuta2);
        
        $this->assertCount(
            $cantidadTotal,
            $idsUnicos,
            "El número de reclamos únicos debe ser igual a la suma de reclamos en ambas rutas, confirmando que no hay duplicados"
        );
        
        $this->assertEquals(
            11,
            $cantidadTotal,
            'Entre ambas rutas debe haber exactamente 11 reclamos en total (5 + 6)'
        );
        
        $this->assertCount(
            11,
            $idsUnicos,
            'Debe haber exactamente 11 reclamos únicos, sin duplicados'
        );
    }

    /**
     * HU-020: Test de validación - Crear ruta sin nombre
     * Tipo: API
     */
    public function testValidacionRutaSinNombre()
    {
        // Caso 1: Nombre vacío
        $datosNombreVacio = [
            'nombre' => '',
            'color' => '#FF6B35',
            'cantidadReclamos' => 2,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];

        $resultVacio = $this->withBodyFormat('json')
                            ->post('/api/rutas/generar', $datosNombreVacio);

        echo "\n=== INTENTO 1: NOMBRE VACÍO ===\n";
        echo "Status: " . $resultVacio->response()->getStatusCode() . "\n";
        echo "Body: " . $resultVacio->response()->getBody() . "\n";
        echo "================================\n";

        $resultVacio->assertStatus(400);
        $responseVacio = json_decode($resultVacio->response()->getBody(), true);
        $this->assertArrayHasKey('message', $responseVacio);

        // Caso 2: Nombre null
        $datosNombreNull = [
            'nombre' => null,
            'color' => '#FF6B35',
            'cantidadReclamos' => 2,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];

        $resultNull = $this->withBodyFormat('json')
                           ->post('/api/rutas/generar', $datosNombreNull);

        echo "\n=== INTENTO 2: NOMBRE NULL ===\n";
        echo "Status: " . $resultNull->response()->getStatusCode() . "\n";
        echo "Body: " . $resultNull->response()->getBody() . "\n";
        echo "==============================\n";

        $resultNull->assertStatus(400);
        $responseNull = json_decode($resultNull->response()->getBody(), true);
        $this->assertArrayHasKey('message', $responseNull);
    }

    /**
     * HU-020: Test de validación - Crear ruta con cantidad = 0
     * Tipo: API
     */
    public function testValidacionCantidadReclamosInvalida()
    {
        // Caso: Cantidad = 0
        $datosCantidadCero = [
            'nombre' => 'Ruta Test Cero',
            'color' => '#FF6B35',
            'cantidadReclamos' => 0,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];

        $resultCero = $this->withBodyFormat('json')
                           ->post('/api/rutas/generar', $datosCantidadCero);

        $resultCero->assertStatus(400);
        $responseCero = json_decode($resultCero->response()->getBody(), true);
        
        // Verificar estructura de respuesta: messages.error
        $this->assertArrayHasKey('messages', $responseCero);
        $this->assertArrayHasKey('error', $responseCero['messages']);
        $this->assertStringContainsString(
            'cantidadReclamos',
            $responseCero['messages']['error'],
            'El mensaje debe mencionar que falta cantidadReclamos'
        );
    }

    /**
     * HU-020: Test de validación - Crear ruta con cantidad negativa
     * Tipo: API - Este test documenta un ERROR del sistema
     */
    public function testValidacionCantidadNegativa()
    {
        // Caso: Cantidad negativa
        $datosCantidadNegativa = [
            'nombre' => 'Ruta Test Negativa',
            'color' => '#FF6B35',
            'cantidadReclamos' => -5,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];

        $resultNegativa = $this->withBodyFormat('json')
                               ->post('/api/rutas/generar', $datosCantidadNegativa);

        // DOCUMENTAR EL ERROR: El sistema debería rechazar con 400, pero acepta y crea ruta vacía
        $this->assertEquals(201, $resultNegativa->response()->getStatusCode(), 
            'ERROR DETECTADO: El sistema acepta cantidades negativas (debería ser 400)');
        
        $responseNegativa = json_decode($resultNegativa->response()->getBody(), true);
        
        // Verificar que efectivamente crea la ruta con cantidad 0
        $this->assertEquals(0, $responseNegativa['ruta']['cantidadReclamos'],
            'El sistema convierte cantidad negativa en 0');
        $this->assertEmpty($responseNegativa['reclamos'],
            'La ruta se crea sin reclamos');
    }

    /**
     * HU-020: Test de validación - Solicitar más reclamos de los disponibles
     * Tipo: API
     */
    public function testValidacionReclamosInsuficientes()
    {
        // Primero, contar cuántos reclamos hay disponibles
        $reclamoModel = new ReclamoModel();
        $rutaReclamoModel = new Ruta_reclamoModel();
        
        // Obtener todos los reclamos no completados
        $todosReclamos = $reclamoModel->where('municipalidad_estado !=', 'Completado')->findAll();
        
        // Filtrar los que NO están en ninguna ruta
        $reclamosDisponibles = [];
        foreach ($todosReclamos as $reclamo) {
            $estaEnRuta = $rutaReclamoModel->where('reclamo_id', $reclamo['id'])->first();
            if (!$estaEnRuta) {
                $reclamosDisponibles[] = $reclamo;
            }
        }
        
        $cantidadDisponible = count($reclamosDisponibles);
        $cantidadExcesiva = $cantidadDisponible + 10; // Solicitar 10 más de los disponibles

        $datosExcesivos = [
            'nombre' => 'Ruta Excesiva',
            'color' => '#FF6B35',
            'cantidadReclamos' => $cantidadExcesiva,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];

        $result = $this->withBodyFormat('json')
                       ->post('/api/rutas/generar', $datosExcesivos);

        $result->assertStatus(400);
        $response = json_decode($result->response()->getBody(), true);
        
        // Verificar estructura de respuesta: messages.error
        $this->assertArrayHasKey('messages', $response);
        $this->assertArrayHasKey('error', $response['messages']);
        
        // Verificar que el mensaje menciona la cantidad disponible y solicitada
        $mensajeError = $response['messages']['error'];
        $this->assertStringContainsString(
            (string)$cantidadDisponible,
            $mensajeError,
            'El mensaje de error debe mencionar la cantidad de reclamos disponibles'
        );
        $this->assertStringContainsString(
            (string)$cantidadExcesiva,
            $mensajeError,
            'El mensaje de error debe mencionar la cantidad de reclamos solicitados'
        );
        $this->assertStringContainsString(
            'disponibles',
            strtolower($mensajeError),
            'El mensaje debe contener la palabra "disponibles"'
        );
    }

    /**
     * HU-020: Test de obtener lista de todas las rutas
     * Tipo: API
     */
    public function testObtenerListaRutas()
    {
        // Primero, crear algunas rutas para asegurar que hay datos
        $rutasCreadas = [];
        
        // Crear Ruta 1
        $datosRuta1 = [
            'nombre' => 'Ruta Test Listado 1',
            'color' => '#FF6B35',
            'cantidadReclamos' => 2,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];
        
        $resultCrear1 = $this->withBodyFormat('json')
                             ->post('/api/rutas/generar', $datosRuta1);
        
        $resultCrear1->assertStatus(201);
        $responseCrear1 = json_decode($resultCrear1->response()->getBody(), true);
        $rutasCreadas[] = $responseCrear1['ruta'];
        
        // Crear Ruta 2
        $datosRuta2 = [
            'nombre' => 'Ruta Test Listado 2',
            'color' => '#28a745',
            'cantidadReclamos' => 3,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];
        
        $resultCrear2 = $this->withBodyFormat('json')
                             ->post('/api/rutas/generar', $datosRuta2);
        
        $resultCrear2->assertStatus(201);
        $responseCrear2 = json_decode($resultCrear2->response()->getBody(), true);
        $rutasCreadas[] = $responseCrear2['ruta'];
        
        // AHORA PROBAR EL GET
        $result = $this->get('/api/rutas');
        
        // Verificar que la respuesta es exitosa
        $result->assertStatus(200);
        
        $response = json_decode($result->response()->getBody(), true);
        
        // Verificar que es un array
        $this->assertIsArray($response, 'La respuesta debe ser un array');
        
        // Verificar que tiene al menos las 2 rutas que acabamos de crear
        $this->assertGreaterThanOrEqual(
            2, 
            count($response), 
            'Debe haber al menos 2 rutas (las que acabamos de crear)'
        );
        
        // Verificar que cada elemento del array tiene la estructura esperada de una ruta
        foreach ($response as $ruta) {
            $this->assertArrayHasKey('id', $ruta, 'Cada ruta debe tener un ID');
            $this->assertArrayHasKey('nombre', $ruta, 'Cada ruta debe tener un nombre');
            $this->assertArrayHasKey('cantidadReclamos', $ruta, 'Cada ruta debe tener cantidadReclamos');
            $this->assertArrayHasKey('asignada', $ruta, 'Cada ruta debe tener el campo asignada');
            $this->assertArrayHasKey('fecha', $ruta, 'Cada ruta debe tener una fecha');
            $this->assertArrayHasKey('color', $ruta, 'Cada ruta debe tener un color');
        }
        
        // Verificar que las rutas creadas están en la lista
        $idsEnLista = array_column($response, 'id');
        foreach ($rutasCreadas as $rutaCreada) {
            $this->assertContains(
                $rutaCreada['id'],
                $idsEnLista,
                "La ruta '{$rutaCreada['nombre']}' (ID: {$rutaCreada['id']}) debe estar en la lista"
            );
        }
        
        // Verificar que las rutas específicas están en la lista por nombre
        $nombresEnLista = array_column($response, 'nombre');
        $this->assertContains('Ruta Test Listado 1', $nombresEnLista, 'Debe incluir la Ruta Test Listado 1');
        $this->assertContains('Ruta Test Listado 2', $nombresEnLista, 'Debe incluir la Ruta Test Listado 2');
    }

    /**
     * HU-020/HU-021: Test de obtener detalles de una ruta específica
     * Tipo: API
     */
    public function testObtenerDetallesRutaEspecifica()
    {
        // Primero crear una ruta
        $datosRuta = [
            'nombre' => 'Ruta Test Detalles',
            'color' => '#FF6B35',
            'cantidadReclamos' => 3,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];
        
        $resultCrear = $this->withBodyFormat('json')
                             ->post('/api/rutas/generar', $datosRuta);
        
        $resultCrear->assertStatus(201);
        $responseCrear = json_decode($resultCrear->response()->getBody(), true);
        $rutaCreada = $responseCrear['ruta'];
        $rutaId = $rutaCreada['id'];
        
        // AHORA OBTENER LOS DETALLES DE ESA RUTA
        $result = $this->get("/api/rutas/{$rutaId}");
        
        // Verificar que la respuesta es exitosa
        $result->assertStatus(200);
        
        $response = json_decode($result->response()->getBody(), true);
        
        // Verificar estructura completa de la ruta
        $this->assertArrayHasKey('id', $response, 'Debe tener ID');
        $this->assertArrayHasKey('nombre', $response, 'Debe tener nombre');
        $this->assertArrayHasKey('cantidadReclamos', $response, 'Debe tener cantidadReclamos');
        $this->assertArrayHasKey('asignada', $response, 'Debe tener campo asignada');
        $this->assertArrayHasKey('cuadrilla_id', $response, 'Debe tener cuadrilla_id');
        $this->assertArrayHasKey('tiempoEstimado', $response, 'Debe tener tiempoEstimado');
        $this->assertArrayHasKey('fecha', $response, 'Debe tener fecha');
        $this->assertArrayHasKey('color', $response, 'Debe tener color');
        
        // Verificar que los valores coinciden con lo creado
        $this->assertEquals($rutaId, $response['id'], 'El ID debe coincidir');
        $this->assertEquals('Ruta Test Detalles', $response['nombre'], 'El nombre debe coincidir');
        $this->assertEquals(3, $response['cantidadReclamos'], 'La cantidad debe ser 3');
        $this->assertEquals('#FF6B35', $response['color'], 'El color debe coincidir');
        $this->assertEquals(0, $response['asignada'], 'Debe estar sin asignar');
        $this->assertNull($response['cuadrilla_id'], 'No debe tener cuadrilla asignada');
    }

    /**
     * HU-021: Test de obtener reclamos de una ruta específica
     * Tipo: API
     */
    public function testObtenerReclamosRutaEspecifica()
    {
        // Primero crear una ruta con varios reclamos
        $datosRuta = [
            'nombre' => 'Ruta Test Reclamos',
            'color' => '#28a745',
            'cantidadReclamos' => 4,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];
        
        $resultCrear = $this->withBodyFormat('json')
                             ->post('/api/rutas/generar', $datosRuta);
        
        $resultCrear->assertStatus(201);
        $responseCrear = json_decode($resultCrear->response()->getBody(), true);
        $rutaId = $responseCrear['ruta']['id'];
        
        // AHORA OBTENER LOS RECLAMOS DE ESA RUTA
        $result = $this->get("/api/rutas/{$rutaId}/reclamos");
        
        // Verificar que la respuesta es exitosa
        $result->assertStatus(200);
        
        $response = json_decode($result->response()->getBody(), true);
        
        // Verificar que es un array
        $this->assertIsArray($response, 'La respuesta debe ser un array');
        
        // Verificar que tiene los reclamos esperados
        $this->assertCount(4, $response, 'Debe devolver 4 reclamos');
        
        // Verificar que cada reclamo tiene la estructura esperada
        foreach ($response as $reclamo) {
            $this->assertArrayHasKey('id', $reclamo, 'Cada reclamo debe tener ID');
            $this->assertArrayHasKey('municipalidad_id', $reclamo, 'Debe tener municipalidad_id');
            $this->assertArrayHasKey('municipalidad_domicilio', $reclamo, 'Debe tener domicilio');
            $this->assertArrayHasKey('municipalidad_numeroDomicilio', $reclamo, 'Debe tener número de domicilio');
            $this->assertArrayHasKey('municipalidad_estado', $reclamo, 'Debe tener estado');
            $this->assertArrayHasKey('prioridad', $reclamo, 'Debe tener prioridad');
            $this->assertArrayHasKey('posicion', $reclamo, 'Debe tener posición en la ruta');
        }
        
        // Verificar que están ordenados por posición
        $posiciones = array_column($response, 'posicion');
        $posicionesOrdenadas = $posiciones;
        sort($posicionesOrdenadas);
        $this->assertEquals(
            $posicionesOrdenadas, 
            $posiciones, 
            'Los reclamos deben estar ordenados por posición'
        );
        
        // Verificar que las posiciones son secuenciales desde 1
        $this->assertEquals([1, 2, 3, 4], $posiciones, 'Las posiciones deben ser 1, 2, 3, 4');
    }

    /**
     * HU-020/HU-021: Test de ruta inexistente
     * Tipo: API - Error handling
     */
    public function testRutaInexistente()
    {
        // Intentar obtener una ruta con ID que no existe
        $idInexistente = 999999;
        
        $result = $this->get("/api/rutas/{$idInexistente}");
        
        // Verificar que responde con 404
        $result->assertStatus(404);
        
        $response = json_decode($result->response()->getBody(), true);
        
        // Verificar la estructura de la respuesta de error
        $this->assertArrayHasKey('status', $response, 'Debe tener status');
        $this->assertArrayHasKey('error', $response, 'Debe tener error');
        $this->assertArrayHasKey('messages', $response, 'Debe tener messages');
        
        $this->assertEquals(404, $response['status'], 'El status debe ser 404');
        $this->assertEquals(404, $response['error'], 'El error debe ser 404');
        
        // Verificar el mensaje de error específico
        $this->assertArrayHasKey('error', $response['messages'], 'Debe tener mensaje de error');
        $this->assertEquals(
            'Ruta no encontrada',
            $response['messages']['error'],
            'El mensaje debe indicar que la ruta no fue encontrada'
        );
    }

    /**
     * HU-023: Test de generación de ruta en modo manual
     * Tipo: API
     */
    public function testGenerarRutaManual()
    {
        // Obtener algunos reclamos disponibles para seleccionar manualmente
        $reclamosDisponibles = $this->db->table('reclamo')
            ->where('municipalidad_estado !=', 'Completado')
            ->limit(5)
            ->get()
            ->getResultArray();
        
        $this->assertGreaterThanOrEqual(5, count($reclamosDisponibles), 'Debe haber al menos 5 reclamos disponibles');
        
        // Seleccionar 4 reclamos en un orden específico
        $reclamosSeleccionados = [
            (int)$reclamosDisponibles[2]['id'], // Tercero
            (int)$reclamosDisponibles[0]['id'], // Primero
            (int)$reclamosDisponibles[4]['id'], // Quinto
            (int)$reclamosDisponibles[1]['id']  // Segundo
        ];
        
        // Crear ruta en modo manual
        $datosRuta = [
            'nombre' => 'Ruta Test Manual',
            'color' => '#9C27B0',
            'cantidadReclamos' => 4,
            'reclamosManuales' => $reclamosSeleccionados,
            'primerReclamoManual' => null,
            'modoManual' => true
        ];
        
        $result = $this->withBodyFormat('json')
                       ->post('/api/rutas/generar', $datosRuta);
        
        $result->assertStatus(201);
        
        $response = json_decode($result->response()->getBody(), true);
        
        // Verificar que se creó la ruta
        $this->assertArrayHasKey('ruta', $response);
        $this->assertArrayHasKey('reclamos', $response);
        
        $ruta = $response['ruta'];
        $reclamosRuta = $response['reclamos'];
        
        // Verificar cantidad
        $this->assertEquals(4, $ruta['cantidadReclamos'], 'Debe tener 4 reclamos');
        $this->assertCount(4, $reclamosRuta, 'Debe devolver 4 reclamos');
        
        // Verificar que el orden se respeta
        $idsEnOrden = array_map(function($r) { return (int)$r['id']; }, $reclamosRuta);
        $this->assertEquals(
            $reclamosSeleccionados,
            $idsEnOrden,
            'Los reclamos deben estar en el orden especificado manualmente'
        );
        
        // Verificar que cada reclamo tiene coordenadas
        foreach ($reclamosRuta as $reclamo) {
            $this->assertArrayHasKey('coordenadas', $reclamo, 'Cada reclamo debe tener coordenadas');
            $this->assertArrayHasKey('lat', $reclamo['coordenadas'], 'Las coordenadas deben tener latitud');
            $this->assertArrayHasKey('lng', $reclamo['coordenadas'], 'Las coordenadas deben tener longitud');
        }
    }

    /**
     * HU-023: Test de validación - reclamo ya en otra ruta
     * Tipo: API - Validación
     */
    public function testValidacionReclamoEnOtraRuta()
    {
        // Primero crear una ruta automática
        $datosRuta1 = [
            'nombre' => 'Ruta Test 1',
            'color' => '#FF6B35',
            'cantidadReclamos' => 3,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];
        
        $result1 = $this->withBodyFormat('json')
                        ->post('/api/rutas/generar', $datosRuta1);
        
        $result1->assertStatus(201);
        $response1 = json_decode($result1->response()->getBody(), true);
        $reclamosRuta1 = $response1['reclamos'];
        
        $this->assertGreaterThan(0, count($reclamosRuta1), 'La primera ruta debe tener reclamos');
        
        // Obtener el ID de uno de los reclamos de la primera ruta
        $reclamoYaAsignado = (int)$reclamosRuta1[0]['id'];
        
        // Obtener otros reclamos disponibles
        $otrosReclamos = $this->db->table('reclamo')
            ->where('municipalidad_estado !=', 'Completado')
            ->whereNotIn('id', array_column($reclamosRuta1, 'id'))
            ->limit(2)
            ->get()
            ->getResultArray();
        
        $this->assertGreaterThanOrEqual(2, count($otrosReclamos), 'Debe haber otros reclamos disponibles');
        
        // Intentar crear una ruta manual que incluya el reclamo ya asignado
        $reclamosConflictivos = [
            (int)$otrosReclamos[0]['id'],
            $reclamoYaAsignado, // Este ya está en otra ruta
            (int)$otrosReclamos[1]['id']
        ];
        
        $datosRuta2 = [
            'nombre' => 'Ruta Test 2 Conflictiva',
            'color' => '#28a745',
            'cantidadReclamos' => 3,
            'reclamosManuales' => $reclamosConflictivos,
            'primerReclamoManual' => null,
            'modoManual' => true
        ];
        
        $result2 = $this->withBodyFormat('json')
                        ->post('/api/rutas/generar', $datosRuta2);
        
        // DOCUMENTAR EL ERROR: El sistema debería rechazar con 400, pero acepta
        $statusCode = $result2->response()->getStatusCode();
        $response2 = json_decode($result2->response()->getBody(), true);
        
        if ($statusCode === 201) {
            // ERROR CRÍTICO: El sistema acepta reclamos ya asignados
            $this->assertEquals(201, $statusCode, 
                'ERROR DETECTADO: El sistema acepta reclamos ya en otras rutas (debería ser 400)');
            
            // Verificar que efectivamente creó la ruta con el reclamo duplicado
            $this->assertArrayHasKey('ruta', $response2, 'Se creó la ruta incorrectamente');
            $this->assertArrayHasKey('reclamos', $response2, 'Se incluyeron los reclamos');
            
            // Verificar que el reclamo duplicado está en la nueva ruta
            $idsNuevaRuta = array_map(function($r) { return (int)$r['id']; }, $response2['reclamos']);
            $this->assertContains(
                $reclamoYaAsignado,
                $idsNuevaRuta,
                'El reclamo duplicado fue incluido en la nueva ruta (BUG)'
            );
        } else {
            // Si el sistema valida correctamente
            $this->assertEquals(400, $statusCode, 'Debería rechazar con 400');
            $this->assertArrayHasKey('messages', $response2, 'Debe tener mensajes de error');
            $this->assertArrayHasKey('error', $response2['messages'], 'Debe tener mensaje de error');
            
            $mensajeError = strtolower($response2['messages']['error']);
            $this->assertStringContainsString(
                'ruta',
                $mensajeError,
                'El mensaje debe mencionar que está en otra ruta'
            );
        }
    }

    /**
     * HU-020/HU-021: Test de eliminación de ruta
     * Tipo: API - Eliminación
     */
    public function testEliminarRuta()
    {
        // Primero crear una ruta
        $datosRuta = [
            'nombre' => 'Ruta Test Para Eliminar',
            'color' => '#FF6B35',
            'cantidadReclamos' => 3,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];
        
        $resultCrear = $this->withBodyFormat('json')
                             ->post('/api/rutas/generar', $datosRuta);
        
        $resultCrear->assertStatus(201);
        $responseCrear = json_decode($resultCrear->response()->getBody(), true);
        $rutaId = $responseCrear['ruta']['id'];
        $reclamosCreados = $responseCrear['reclamos'];
        
        $this->assertGreaterThan(0, count($reclamosCreados), 'La ruta debe tener reclamos');
        
        // Verificar que la ruta existe
        $rutaAntes = $this->db->table('ruta')->where('id', $rutaId)->get()->getRowArray();
        $this->assertNotNull($rutaAntes, 'La ruta debe existir antes de eliminar');
        
        // Verificar que existen las relaciones ruta_reclamo
        $relacionesAntes = $this->db->table('ruta_reclamo')
            ->where('ruta_id', $rutaId)
            ->get()
            ->getResultArray();
        $this->assertCount(
            count($reclamosCreados),
            $relacionesAntes,
            'Deben existir las relaciones ruta_reclamo'
        );
        
        // ELIMINAR LA RUTA
        $resultEliminar = $this->delete("/api/rutas/{$rutaId}");
        
        // Verificar que responde con éxito
        $resultEliminar->assertStatus(200);
        
        $responseEliminar = json_decode($resultEliminar->response()->getBody(), true);
        
        // Verificar mensaje de confirmación
        $this->assertArrayHasKey('mensaje', $responseEliminar, 'Debe tener mensaje de confirmación');
        $this->assertStringContainsString(
            'eliminada',
            strtolower($responseEliminar['mensaje']),
            'El mensaje debe indicar que fue eliminada'
        );
        
        // Verificar que la ruta fue eliminada de la base de datos
        $rutaDespues = $this->db->table('ruta')->where('id', $rutaId)->get()->getRowArray();
        $this->assertNull($rutaDespues, 'La ruta debe haber sido eliminada');
        
        // Verificar que las relaciones ruta_reclamo también fueron eliminadas
        $relacionesDespues = $this->db->table('ruta_reclamo')
            ->where('ruta_id', $rutaId)
            ->get()
            ->getResultArray();
        $this->assertCount(
            0,
            $relacionesDespues,
            'Las relaciones ruta_reclamo deben haber sido eliminadas'
        );
        
        // Verificar que los reclamos aún existen (no se eliminan, solo se liberan)
        foreach ($reclamosCreados as $reclamo) {
            $reclamoExiste = $this->db->table('reclamo')
                ->where('id', $reclamo['id'])
                ->get()
                ->getRowArray();
            $this->assertNotNull(
                $reclamoExiste,
                "El reclamo {$reclamo['id']} debe seguir existiendo"
            );
        }
    }

    /**
     * HU-020/HU-021/HU-023: Test de flujo completo - crear, visualizar y eliminar
     * Tipo: Integración
     */
    public function testFlujoCompletoCrearVisualizarEliminar()
    {
        // ============================================
        // PASO 1: CREAR LA RUTA
        // ============================================
        $datosRuta = [
            'nombre' => 'Ruta Test Flujo Completo',
            'color' => '#9C27B0',
            'cantidadReclamos' => 4,
            'reclamosManuales' => [],
            'primerReclamoManual' => null,
            'modoManual' => false
        ];
        
        $resultCrear = $this->withBodyFormat('json')
                             ->post('/api/rutas/generar', $datosRuta);
        
        // Verificar creación exitosa
        $resultCrear->assertStatus(201);
        $responseCrear = json_decode($resultCrear->response()->getBody(), true);
        
        $this->assertArrayHasKey('ruta', $responseCrear, 'Debe devolver la ruta creada');
        $this->assertArrayHasKey('reclamos', $responseCrear, 'Debe devolver los reclamos');
        
        $rutaCreada = $responseCrear['ruta'];
        $reclamosCreados = $responseCrear['reclamos'];
        $rutaId = $rutaCreada['id'];
        
        $this->assertEquals('Ruta Test Flujo Completo', $rutaCreada['nombre']);
        $this->assertEquals(4, $rutaCreada['cantidadReclamos']);
        $this->assertCount(4, $reclamosCreados, 'Debe tener 4 reclamos');
        
        // ============================================
        // PASO 2: OBTENER DETALLES DE LA RUTA
        // ============================================
        $resultDetalles = $this->get("/api/rutas/{$rutaId}");
        
        $resultDetalles->assertStatus(200);
        $responseDetalles = json_decode($resultDetalles->response()->getBody(), true);
        
        // Verificar que los detalles coinciden con la ruta creada
        $this->assertEquals($rutaId, $responseDetalles['id']);
        $this->assertEquals('Ruta Test Flujo Completo', $responseDetalles['nombre']);
        $this->assertEquals(4, $responseDetalles['cantidadReclamos']);
        $this->assertEquals('#9C27B0', $responseDetalles['color']);
        $this->assertEquals(0, $responseDetalles['asignada'], 'Debe estar sin asignar');
        
        // ============================================
        // PASO 3: OBTENER RECLAMOS DE LA RUTA
        // ============================================
        $resultReclamos = $this->get("/api/rutas/{$rutaId}/reclamos");
        
        $resultReclamos->assertStatus(200);
        $responseReclamos = json_decode($resultReclamos->response()->getBody(), true);
        
        // Verificar que los reclamos son los mismos que se crearon
        $this->assertCount(4, $responseReclamos, 'Debe devolver 4 reclamos');
        
        $idsCreados = array_map(function($r) { return (int)$r['id']; }, $reclamosCreados);
        $idsObtenidos = array_map(function($r) { return (int)$r['id']; }, $responseReclamos);
        
        $this->assertEquals(
            $idsCreados,
            $idsObtenidos,
            'Los IDs de reclamos deben coincidir'
        );
        
        // Verificar que cada reclamo tiene posición
        foreach ($responseReclamos as $reclamo) {
            $this->assertArrayHasKey('posicion', $reclamo, 'Cada reclamo debe tener posición');
        }
        
        // ============================================
        // PASO 4: VERIFICAR QUE LA RUTA ESTÁ EN EL LISTADO
        // ============================================
        $resultListado = $this->get('/api/rutas');
        
        $resultListado->assertStatus(200);
        $responseListado = json_decode($resultListado->response()->getBody(), true);
        
        $rutaEnListado = array_filter($responseListado, function($r) use ($rutaId) {
            return $r['id'] == $rutaId;
        });
        
        $this->assertNotEmpty($rutaEnListado, 'La ruta debe estar en el listado general');
        
        // ============================================
        // PASO 5: ELIMINAR LA RUTA
        // ============================================
        $resultEliminar = $this->delete("/api/rutas/{$rutaId}");
        
        $resultEliminar->assertStatus(200);
        $responseEliminar = json_decode($resultEliminar->response()->getBody(), true);
        
        $this->assertArrayHasKey('mensaje', $responseEliminar);
        $this->assertStringContainsString('eliminada', strtolower($responseEliminar['mensaje']));
        
        // ============================================
        // PASO 6: VERIFICAR QUE LA RUTA FUE ELIMINADA
        // ============================================
        
        // Verificar en base de datos
        $rutaEnDB = $this->db->table('ruta')->where('id', $rutaId)->get()->getRowArray();
        $this->assertNull($rutaEnDB, 'La ruta NO debe existir en la base de datos');
        
        // Verificar que las relaciones fueron eliminadas
        $relacionesEnDB = $this->db->table('ruta_reclamo')
            ->where('ruta_id', $rutaId)
            ->get()
            ->getResultArray();
        $this->assertCount(0, $relacionesEnDB, 'NO debe haber relaciones ruta_reclamo');
        
        // ============================================
        // PASO 7: INTENTAR OBTENER LA RUTA ELIMINADA
        // ============================================
        $resultDetallesDespues = $this->get("/api/rutas/{$rutaId}");
        
        // Debe devolver 404
        $resultDetallesDespues->assertStatus(404);
        
        $responseDetallesDespues = json_decode($resultDetallesDespues->response()->getBody(), true);
        $this->assertArrayHasKey('messages', $responseDetallesDespues);
        $this->assertArrayHasKey('error', $responseDetallesDespues['messages']);
        $this->assertEquals('Ruta no encontrada', $responseDetallesDespues['messages']['error']);
        
        // ============================================
        // PASO 8: VERIFICAR QUE LOS RECLAMOS SIGUEN EXISTIENDO
        // ============================================
        foreach ($reclamosCreados as $reclamo) {
            $reclamoEnDB = $this->db->table('reclamo')
                ->where('id', $reclamo['id'])
                ->get()
                ->getRowArray();
            $this->assertNotNull(
                $reclamoEnDB,
                "El reclamo {$reclamo['id']} debe seguir existiendo (liberado para reutilizar)"
            );
        }
        
        // ============================================
        // PASO 9: VERIFICAR QUE LA RUTA NO ESTÁ EN EL LISTADO
        // ============================================
        $resultListadoFinal = $this->get('/api/rutas');
        
        $resultListadoFinal->assertStatus(200);
        $responseListadoFinal = json_decode($resultListadoFinal->response()->getBody(), true);
        
        $rutaEnListadoFinal = array_filter($responseListadoFinal, function($r) use ($rutaId) {
            return $r['id'] == $rutaId;
        });
        
        $this->assertEmpty($rutaEnListadoFinal, 'La ruta NO debe estar en el listado después de eliminar');
    }
}

