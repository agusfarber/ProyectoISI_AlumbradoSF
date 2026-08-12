<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\Token103Model;
use App\Models\ReclamoModel;
use App\Controllers\Api\ReclamosSincronizacion;
use ReflectionClass;
use ReflectionMethod;

class ReclamosSincronizacion103Test extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'Tests\Support';
    protected $migrate = true;
    protected $migrateOnce = false;
    protected $refresh = true;
    protected $seed = ''; // No usar seeder, estos tests no lo necesitan

    /**
     * Prueba 21: Configuración de Token de API del 103
     *
     * Objetivo: Verificar que se pueden guardar y recuperar el api_token
     * usado en Authorization: Token {valor}.
     */
    public function testConfiguracionCredenciales()
    {
        $apiToken = '6f560d0559e9d32733781c050d5fd5d851e535c5';

        $tokenModel = new Token103Model();
        $tokenId = $tokenModel->insert([
            'api_token' => $apiToken,
        ]);

        $this->assertIsInt($tokenId);
        $this->assertGreaterThan(0, $tokenId);

        $config = $tokenModel->find($tokenId);

        $this->assertNotNull($config);
        $this->assertEquals($apiToken, $config['api_token']);
        $this->assertEquals($apiToken, $tokenModel->obtenerApiToken());
        $this->assertEquals('Token ' . $apiToken, $tokenModel->obtenerHeaderAuthorization());

        $this->assertArrayHasKey('created_at', $config);
        $this->assertArrayHasKey('updated_at', $config);
    }

    /**
     * Prueba 22: Mapeo de Reclamo de API Externa
     * 
     * Objetivo: Verificar que el mapeo de datos de la API externa
     * a nuestra estructura interna es correcto y maneja correctamente
     * campos nulos y transformaciones de estado.
     */
    public function testMapeoReclamoApiExterna()
    {
        // Arrange: Crear un reclamo simulado de la API externa
        $reclamoApiExterna = [
            'id' => 12345,
            'motivo' => [
                'tipo' => 'ALUMBRADO PÚBLICO',
                'nombre' => 'Luminaria que no enciende'
            ],
            'fecha_inicio' => '2025-11-12T14:30:00.000000-03:00',
            'fecha_modificacion' => '2025-11-12T15:45:30.500000-03:00',
            'estado_nombre' => 'Asignado', // Este debe cambiarse a "Recibido"
            'calle' => [
                'nombre' => 'San Martin'
            ],
            'calle_altura' => 1250,
            'desde_calle' => [
                'nombre' => 'Belgrano'
            ],
            'hasta_calle' => [
                'nombre' => 'Rivadavia'
            ],
            'telefono' => '3564123456',
            'descripcion' => 'La luminaria no enciende desde hace dos noches',
        ];
        
        // Act: Usar Reflection para acceder al método privado mapearReclamo
        $controller = new ReclamosSincronizacion();
        $reflection = new ReflectionClass($controller);
        $metodoMapear = $reflection->getMethod('mapearReclamo');
        $metodoMapear->setAccessible(true);
        
        $reclamoMapeado = $metodoMapear->invoke($controller, $reclamoApiExterna);
        
        // Assert 1: Verificar ID
        $this->assertEquals('12345', $reclamoMapeado['municipalidad_id']);
        
        // Assert 2: Verificar tipo y motivo
        $this->assertEquals('ALUMBRADO PÚBLICO', $reclamoMapeado['municipalidad_tipo']);
        $this->assertEquals('Luminaria que no enciende', $reclamoMapeado['municipalidad_motivo']);
        
        // Assert 3: Verificar conversión de fechas
        $this->assertStringContainsString('2025-11-12', $reclamoMapeado['municipalidad_fechaInicio']);
        $this->assertStringContainsString('2025-11-12', $reclamoMapeado['municipalidad_fechaModificacion']);
        
        // Assert 4: CRÍTICO - Verificar que el estado "Asignado" se cambió a "Recibido"
        $this->assertEquals('Recibido', $reclamoMapeado['municipalidad_estado']);
        
        // Assert 5: Verificar dirección
        $this->assertEquals('San Martin', $reclamoMapeado['municipalidad_domicilio']);
        $this->assertEquals('1250', $reclamoMapeado['municipalidad_numeroDomicilio']);
        
        // Assert 6: Verificar entre calles
        $this->assertEquals('Belgrano', $reclamoMapeado['municipalidad_entreCalleUno']);
        $this->assertEquals('Rivadavia', $reclamoMapeado['municipalidad_entreCalleDos']);
        
        // Assert 7: Verificar teléfono y descripción del 103
        $this->assertEquals('3564123456', $reclamoMapeado['municipalidad_telefono']);
        $this->assertEquals('La luminaria no enciende desde hace dos noches', $reclamoMapeado['municipalidad_descripcion']);

        // Assert 8: Verificar campos que deben ser null
        $this->assertNull($reclamoMapeado['municipalidad_recepcion']);
        $this->assertNull($reclamoMapeado['municipalidad_ciudadano']);

        // Assert 9: Sin Completado no se marca cierre formal
        $this->assertArrayNotHasKey('cerrado', $reclamoMapeado);
        $this->assertArrayNotHasKey('fecha_cierre', $reclamoMapeado);
    }

    /**
     * Completado en el 103 → cerrado=1 y fecha_cierre desde fecha_modificacion.
     */
    public function testMapeoReclamoCompletadoMarcaCerrado()
    {
        $reclamoApiExterna = [
            'id' => 54321,
            'motivo' => [
                'tipo' => 'ALUMBRADO PÚBLICO',
                'nombre' => 'Luminarias quemadas o rotas',
            ],
            'fecha_inicio' => '2025-11-10T10:00:00.000000-03:00',
            'fecha_modificacion' => '2025-11-12T18:30:00.000000-03:00',
            'estado_nombre' => 'Completado',
            'calle' => ['nombre' => 'San Martin'],
            'calle_altura' => 500,
        ];

        $controller = new ReclamosSincronizacion();
        $reflection = new ReflectionClass($controller);
        $metodoMapear = $reflection->getMethod('mapearReclamo');
        $metodoMapear->setAccessible(true);

        $reclamoMapeado = $metodoMapear->invoke($controller, $reclamoApiExterna);

        $this->assertEquals('Completado', $reclamoMapeado['municipalidad_estado']);
        $this->assertEquals(1, $reclamoMapeado['cerrado']);
        $this->assertEquals('2025-11-12 18:30:00', $reclamoMapeado['fecha_cierre']);
    }

    /**
     * Si falta fecha_modificacion, fecha_cierre usa fecha_inicio.
     */
    public function testMapeoReclamoCompletadoFechaCierreFallbackInicio()
    {
        $reclamoApiExterna = [
            'id' => 54322,
            'motivo' => [
                'tipo' => 'ALUMBRADO PÚBLICO',
                'nombre' => 'Luminarias quemadas o rotas',
            ],
            'fecha_inicio' => '2025-11-10T10:00:00.000000-03:00',
            'fecha_modificacion' => null,
            'estado_nombre' => 'Completado',
            'calle' => ['nombre' => 'Belgrano'],
            'calle_altura' => 100,
        ];

        $controller = new ReclamosSincronizacion();
        $reflection = new ReflectionClass($controller);
        $metodoMapear = $reflection->getMethod('mapearReclamo');
        $metodoMapear->setAccessible(true);

        $reclamoMapeado = $metodoMapear->invoke($controller, $reclamoApiExterna);

        $this->assertEquals(1, $reclamoMapeado['cerrado']);
        $this->assertEquals('2025-11-10 10:00:00', $reclamoMapeado['fecha_cierre']);
    }

    public function testFiltrarReclamosOmiteEstadoInvalido()
    {
        $controller = new ReclamosSincronizacion();
        $reflection = new ReflectionClass($controller);
        $metodoFiltrar = $reflection->getMethod('filtrarReclamosAlumbradoNuevos');
        $metodoFiltrar->setAccessible(true);

        $reclamosApi = [
            [
                'id' => 1001,
                'motivo' => ['tipo' => 'ALUMBRADO PÚBLICO', 'nombre' => 'Luminaria apagada'],
                'estado_nombre' => 'Inválido (N/A)',
                'fecha_inicio' => '2025-11-12T14:30:00.000000-03:00',
                'fecha_modificacion' => '2025-11-12T15:45:30.500000-03:00',
                'calle' => ['nombre' => 'San Martin'],
                'calle_altura' => 100,
            ],
            [
                'id' => 1002,
                'motivo' => ['tipo' => 'ALUMBRADO PÚBLICO', 'nombre' => 'Poste caído'],
                'estado_nombre' => 'Recibido',
                'fecha_inicio' => '2025-11-12T14:30:00.000000-03:00',
                'fecha_modificacion' => '2025-11-12T15:45:30.500000-03:00',
                'calle' => ['nombre' => 'Belgrano'],
                'calle_altura' => 200,
            ],
        ];

        $resultado = $metodoFiltrar->invoke($controller, $reclamosApi, 1000);

        $this->assertEquals(1, $resultado['reclamos_invalidos']);
        $this->assertCount(1, $resultado['reclamos']);
        $this->assertEquals('1002', $resultado['reclamos'][0]['municipalidad_id']);
    }

    public function testProcesarUnoNoGuardaEstadoInvalido()
    {
        $result = $this->withBodyFormat('json')->post('api/sincronizacion/reclamos/procesar-uno', [
            'municipalidad_id' => '99001',
            'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => 'Luminaria apagada',
            'municipalidad_estado' => 'Inválido (N/A)',
            'municipalidad_fechaInicio' => '2025-11-12 14:30:00',
            'municipalidad_fechaModificacion' => '2025-11-12 15:45:30',
            'municipalidad_domicilio' => 'San Martin',
            'municipalidad_numeroDomicilio' => '100',
            'prioridad' => 'Baja',
        ]);

        $result->assertStatus(200);
        $json = json_decode($result->getJSON(), true);
        $this->assertEquals('omitido', $json['accion']);
        $this->assertEquals('estado_invalido', $json['motivo']);

        $reclamoModel = new ReclamoModel();
        $this->assertNull($reclamoModel->where('municipalidad_id', '99001')->first());
    }

    public function testConversionFechasISO8601()
    {
        // Arrange: Preparar diversas fechas ISO 8601
        $fechasISO = [
            '2025-08-28T13:36:04.541033-03:00', // Fecha con microsegundos y timezone
            '2025-11-12T09:15:30.000000-03:00', // Fecha de mañana
            '2025-12-25T23:59:59.999999-03:00', // Fecha de fin de año
            '2025-01-01T00:00:00.000000-03:00'  // Fecha de inicio de año
        ];
        
        $fechasEsperadas = [
            '2025-08-28 13:36:04',
            '2025-11-12 09:15:30',
            '2025-12-25 23:59:59',
            '2025-01-01 00:00:00'
        ];
        
        // Act & Assert: Usar Reflection para acceder al método privado
        $controller = new ReclamosSincronizacion();
        $reflection = new ReflectionClass($controller);
        $metodoConvertir = $reflection->getMethod('convertirFechaApi');
        $metodoConvertir->setAccessible(true);
        
        foreach ($fechasISO as $index => $fechaISO) {
            $fechaConvertida = $metodoConvertir->invoke($controller, $fechaISO);
            
            // Assert: Verificar formato correcto
            $this->assertEquals($fechasEsperadas[$index], $fechaConvertida, 
                "La fecha ISO '{$fechaISO}' debe convertirse a '{$fechasEsperadas[$index]}'");
        }
    }

}

