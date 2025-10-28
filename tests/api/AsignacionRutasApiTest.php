<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\RutaModel;
use App\Models\Ruta_reclamoModel;
use App\Models\ReclamoModel;
use App\Models\CuadrillaModel;
use App\Models\CuadrillaOperariosModel;
use App\Models\UsuarioModel;
use App\Models\RolModel;

/**
 * Tests para HU-022: Asignación de hoja de ruta a cuadrilla
 * 
 * Tests de la funcionalidad que permite a un supervisor asignar hojas de ruta
 * a cuadrillas específicas, validando existencia, estado y registrando
 * la asignación con fecha y hora.
 */
class AsignacionRutasApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $migrateOnce = false;
    protected $refresh = true;
    protected $namespace = 'Tests\Support';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear datos de prueba necesarios
        $this->createTestData();
    }

    /**
     * Crea los datos de prueba necesarios para los tests
     */
    private function createTestData()
    {
        // Crear roles usando la base de datos directamente para evitar problemas con los IDs
        $db = \Config\Database::connect();
        $db->table('rol')->insert([
            'id' => 1,
            'nombre' => 'Operario',
            'descripcion' => 'Rol de operario'
        ]);
        $db->table('rol')->insert([
            'id' => 2,
            'nombre' => 'Supervisor',
            'descripcion' => 'Rol de supervisor'
        ]);

        // Crear usuarios operarios usando la base de datos directamente
        for ($i = 1; $i <= 10; $i++) {
            $db->table('usuario')->insert([
                'nombre' => "Operario $i",
                'email' => "operario$i@test.com",
                'legajo' => "OP$i",
                'rol_id' => 1
            ]);
        }

        // Crear cuadrillas activas
        $db->table('cuadrilla')->insert([
            'nombre' => 'Cuadrilla Norte',
            'descripcion' => 'Cuadrilla de zona norte - ACTIVA'
        ]);
        $db->table('cuadrilla')->insert([
            'nombre' => 'Cuadrilla Sur',
            'descripcion' => 'Cuadrilla de zona sur - ACTIVA'
        ]);

        // Asignar operarios a cuadrillas
        // Asignar operarios 1,2,3 a Cuadrilla Norte
        for ($i = 1; $i <= 3; $i++) {
            $db->table('cuadrilla_operarios')->insert([
                'cuadrilla_id' => 1,
                'usuario_id' => $i
            ]);
        }
        // Asignar operarios 4,5,6 a Cuadrilla Sur
        for ($i = 4; $i <= 6; $i++) {
            $db->table('cuadrilla_operarios')->insert([
                'cuadrilla_id' => 2,
                'usuario_id' => $i
            ]);
        }

        // Crear reclamos de prueba
        for ($i = 1; $i <= 20; $i++) {
            $db->table('reclamo')->insert([
                'municipalidad_id' => (string)(1000 + $i),
                'municipalidad_domicilio' => "Calle $i",
                'municipalidad_numeroDomicilio' => (string)$i,
                'municipalidad_estado' => 'Recibido',
                'prioridad' => ($i <= 10 ? 'Alta' : 'Baja'),
                'municipalidad_motivo' => 'Motivo de prueba',
                'municipalidad_tipo' => 'Luminaria',
                'municipalidad_fechaInicio' => date('Y-m-d H:i:s'),
                'municipalidad_fechaModificacion' => date('Y-m-d H:i:s')
            ]);
        }

        // Crear una ruta de prueba sin asignar
        $rutaId = $db->table('ruta')->insert([
            'nombre' => 'Ruta Test Asignación',
            'color' => '#FF6B35',
            'cantidadReclamos' => 5,
            'asignada' => 0,
            'cuadrilla_id' => null,
            'tiempoEstimado' => '02:30:00',
            'fecha' => date('Y-m-d H:i:s')
        ]);

        // Asignar reclamos a la ruta
        for ($i = 1; $i <= 5; $i++) {
            $db->table('ruta_reclamo')->insert([
                'ruta_id' => $rutaId,
                'reclamo_id' => $i,
                'posicion' => $i
            ]);
        }
    }

    /**
     * Test 1: HU-022
     * Nombre: Asignación exitosa de ruta a cuadrilla activa
     * Ubicación: tests/api/AsignacionRutasApiTest.php::testAsignarRutaACuadrillaActiva
     * Objetivo: Verificar que el supervisor puede asignar una ruta a una cuadrilla activa
     * Tipo de Prueba: API - Integración
     */
    public function testAsignarRutaACuadrillaActiva()
    {
        // Datos de asignación
        $data = [
            'ruta_id' => 1,
            'cuadrilla_id' => 1  // Cuadrilla Norte (activa)
        ];

        // Realizar la asignación
        $result = $this->withBodyFormat('json')
                      ->post('/api/rutas/asignar', $data);

        // Verificar respuesta exitosa
        $result->assertStatus(200);
        
        $responseData = json_decode($result->response()->getBody(), true);
        
        // Verificar estructura de respuesta
        $this->assertArrayHasKey('mensaje', $responseData);
        $this->assertArrayHasKey('ruta', $responseData);
        $this->assertArrayHasKey('reclamos_actualizados', $responseData);
        
        // Verificar mensaje
        $this->assertStringContainsString('asignada exitosamente', $responseData['mensaje']);
        
        // Verificar datos de la ruta
        $ruta = $responseData['ruta'];
        $this->assertEquals(1, $ruta['asignada'], 'La ruta debe estar asignada');
        $this->assertEquals(1, $ruta['cuadrilla_id'], 'La ruta debe estar asignada a Cuadrilla Norte');
        $this->assertEquals('Cuadrilla Norte', $ruta['cuadrilla_nombre']);
        
        // Verificar que se actualizaron reclamos
        $this->assertGreaterThan(0, $responseData['reclamos_actualizados']);
        
        // Verificar en la base de datos
        $rutaModel = new RutaModel();
        $rutaActualizada = $rutaModel->find(1);
        $this->assertEquals(1, $rutaActualizada['asignada']);
        $this->assertEquals(1, $rutaActualizada['cuadrilla_id']);
        
        // Verificar que los reclamos pasaron a "Asignado"
        $reclamoModel = new ReclamoModel();
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamosRuta = $rutaReclamoModel->where('ruta_id', 1)->findAll();
        
        foreach ($reclamosRuta as $rutaReclamo) {
            $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
            $this->assertEquals('Asignado', $reclamo['municipalidad_estado']);
        }
    }

    /**
     * Test 2: HU-022
     * Nombre: Validación de cuadrilla inexistente
     * Ubicación: tests/api/AsignacionRutasApiTest.php::testValidacionCuadrillaInexistente
     * Objetivo: Verificar que el sistema rechaza la asignación cuando la cuadrilla no existe
     * Tipo de Prueba: API - Validación
     */
    public function testValidacionCuadrillaInexistente()
    {
        // Datos de asignación con cuadrilla inexistente
        $data = [
            'ruta_id' => 1,
            'cuadrilla_id' => 999  // Cuadrilla que no existe
        ];

        // Realizar la asignación
        $result = $this->withBodyFormat('json')
                      ->post('/api/rutas/asignar', $data);

        // Verificar respuesta de error
        $result->assertStatus(404);
        
        $responseData = json_decode($result->response()->getBody(), true);
        
        // Verificar estructura de respuesta
        $this->assertArrayHasKey('messages', $responseData);
        $this->assertStringContainsString('Cuadrilla no encontrada', $responseData['messages']['error']);
        
        // Verificar que la ruta NO fue asignada en la base de datos
        $rutaModel = new RutaModel();
        $rutaActualizada = $rutaModel->find(1);
        $this->assertEquals(0, $rutaActualizada['asignada'], 'La ruta NO debe estar asignada');
        $this->assertNull($rutaActualizada['cuadrilla_id'], 'La ruta NO debe tener cuadrilla asignada');
        
        // Verificar que los reclamos siguen en estado "Recibido"
        $reclamoModel = new ReclamoModel();
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamosRuta = $rutaReclamoModel->where('ruta_id', 1)->findAll();
        
        foreach ($reclamosRuta as $rutaReclamo) {
            $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
            $this->assertEquals('Recibido', $reclamo['municipalidad_estado'], 
                'Los reclamos deben seguir en estado Recibido cuando la asignación falla');
        }
    }

    /**
     * Test 3: HU-022
     * Nombre: Validación de ruta inexistente
     * Ubicación: tests/api/AsignacionRutasApiTest.php::testValidacionRutaInexistente
     * Objetivo: Verificar que el sistema rechaza la asignación cuando la ruta no existe
     * Tipo de Prueba: API - Validación
     */
    public function testValidacionRutaInexistente()
    {
        // Datos de asignación con ruta inexistente
        $data = [
            'ruta_id' => 999,  // Ruta que no existe
            'cuadrilla_id' => 1
        ];

        // Realizar la asignación
        $result = $this->withBodyFormat('json')
                      ->post('/api/rutas/asignar', $data);

        // Verificar respuesta de error
        $result->assertStatus(404);
        
        $responseData = json_decode($result->response()->getBody(), true);
        
        // Verificar estructura de respuesta
        $this->assertArrayHasKey('messages', $responseData);
        $this->assertStringContainsString('Ruta no encontrada', $responseData['messages']['error']);
    }

    /**
     * Test 4: HU-022
     * Nombre: Reasignación de ruta a otra cuadrilla
     * Ubicación: tests/api/AsignacionRutasApiTest.php::testReasignarRutaAotraCuadrilla
     * Objetivo: Verificar que se puede reasignar una ruta de una cuadrilla a otra
     * Tipo de Prueba: API - Integración
     */
    public function testReasignarRutaAotraCuadrilla()
    {
        // PASO 1: Asignar la ruta inicialmente a Cuadrilla Norte (ID=1)
        $dataInicial = [
            'ruta_id' => 1,
            'cuadrilla_id' => 1
        ];

        $resultInicial = $this->withBodyFormat('json')
                            ->post('/api/rutas/asignar', $dataInicial);
        
        $resultInicial->assertStatus(200);
        $responseInicial = json_decode($resultInicial->response()->getBody(), true);
        $this->assertEquals(1, $responseInicial['ruta']['cuadrilla_id']);

        // PASO 2: Reasignar la ruta a Cuadrilla Sur (ID=2)
        $dataReasignacion = [
            'ruta_id' => 1,
            'cuadrilla_id' => 2
        ];

        $resultReasignacion = $this->withBodyFormat('json')
                                  ->post('/api/rutas/asignar', $dataReasignacion);
        
        $resultReasignacion->assertStatus(200);
        $responseReasignacion = json_decode($resultReasignacion->response()->getBody(), true);
        
        // Verificar que la ruta ahora está asignada a Cuadrilla Sur
        $this->assertEquals(2, $responseReasignacion['ruta']['cuadrilla_id'], 
            'La ruta debe estar asignada a Cuadrilla Sur');
        $this->assertEquals('Cuadrilla Sur', $responseReasignacion['ruta']['cuadrilla_nombre']);
        $this->assertEquals(1, $responseReasignacion['ruta']['asignada']);
        
        // Verificar en la base de datos
        $rutaModel = new RutaModel();
        $ruta = $rutaModel->find(1);
        $this->assertEquals(2, $ruta['cuadrilla_id'], 
            'La ruta debe estar asignada a cuadrilla_id=2 en la BD');
    }

    /**
     * Test 5: HU-022
     * Nombre: Desasignación de ruta de una cuadrilla
     * Ubicación: tests/api/AsignacionRutasApiTest.php::testDesasignarRutaDeCuadrilla
     * Objetivo: Verificar que el supervisor puede desasignar una ruta de una cuadrilla, volviendo los reclamos a estado "Recibido"
     * Tipo de Prueba: API - Integración
     */
    public function testDesasignarRutaDeCuadrilla()
    {
        // PASO 1: Primero asignar la ruta a una cuadrilla
        $dataAsignacion = [
            'ruta_id' => 1,
            'cuadrilla_id' => 1
        ];

        $resultAsignacion = $this->withBodyFormat('json')
                                ->post('/api/rutas/asignar', $dataAsignacion);
        
        $resultAsignacion->assertStatus(200);
        
        // Verificar que la ruta está asignada
        $rutaModel = new RutaModel();
        $rutaAsignada = $rutaModel->find(1);
        $this->assertEquals(1, $rutaAsignada['asignada']);
        $this->assertEquals(1, $rutaAsignada['cuadrilla_id']);
        
        // Verificar que los reclamos están en estado "Asignado"
        $reclamoModel = new ReclamoModel();
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamosRuta = $rutaReclamoModel->where('ruta_id', 1)->findAll();
        
        foreach ($reclamosRuta as $rutaReclamo) {
            $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
            $this->assertEquals('Asignado', $reclamo['municipalidad_estado']);
        }
        
        // PASO 2: Desasignar la ruta
        $resultDesasignacion = $this->post('/api/rutas/desasignar/1');
        
        $resultDesasignacion->assertStatus(200);
        
        $responseDesasignacion = json_decode($resultDesasignacion->response()->getBody(), true);
        
        // Verificar que se registraron reclamos actualizados
        $this->assertArrayHasKey('reclamos_actualizados', $responseDesasignacion);
        $this->assertGreaterThan(0, $responseDesasignacion['reclamos_actualizados']);
        
        // Verificar que la ruta ya no está asignada
        $rutaDesasignada = $rutaModel->find(1);
        $this->assertEquals(0, $rutaDesasignada['asignada'], 
            'La ruta NO debe estar asignada después de desasignar');
        $this->assertNull($rutaDesasignada['cuadrilla_id'], 
            'La ruta NO debe tener cuadrilla asignada');
        
        // Verificar que los reclamos volvieron a estado "Recibido"
        foreach ($reclamosRuta as $rutaReclamo) {
            $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
            $this->assertEquals('Recibido', $reclamo['municipalidad_estado'], 
                'Los reclamos deben volver a estado Recibido al desasignar');
        }
    }

    // Los otros tests continuarán aquí...
}

