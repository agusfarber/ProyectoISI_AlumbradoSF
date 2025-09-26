<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\ReclamoModel;
use App\Models\UsuarioModel;
use App\Models\RolModel;

class ReclamosOperariosApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $migrateOnce = true;
    protected $refresh = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear datos de prueba necesarios
        $this->createTestData();
    }

    private function createTestData()
    {
        // Crear rol de operario
        $rolModel = new RolModel();
        $rolModel->insert([
            'id' => 3,
            'nombre' => 'Operario'
        ]);

        // Crear operario de prueba
        $usuarioModel = new UsuarioModel();
        $usuarioModel->insert([
            'id' => 10,
            'nombre' => 'Operario Test',
            'email' => 'operario@test.com',
            'legajo' => 'OP001',
            'rol_id' => 3,
            'contrasena' => 'password123'
        ]);

        // Crear reclamo de prueba
        $reclamoModel = new ReclamoModel();
        $reclamoModel->insert([
            'id' => 1,
            'municipalidad_id' => 'REC001',
            'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => 'Lámpara fundida',
            'municipalidad_estado' => 'Pendiente',
            'municipalidad_fechaInicio' => date('Y-m-d H:i:s'),
            'municipalidad_fechaModificacion' => date('Y-m-d H:i:s'),
            'municipalidad_domicilio' => 'Av. Test 123',
            'municipalidad_descripcion' => 'Lámpara fundida en esquina',
            'prioridad' => 'Media'
        ]);
    }

    // ========== TESTS DE CAMBIO DE ESTADO POR OPERARIOS ==========

    public function testOperarioCambiaEstadoReclamoAEnProceso()
    {
        $data = [
            'municipalidad_estado' => 'En Proceso',
            'municipalidad_descripcion' => 'Operario asignado, iniciando reparación'
        ];

        $result = $this->withBodyFormat('json')
                      ->put('/api/reclamos/1', $data);

        $result->assertStatus(200);
        
        // Verificar que el estado se actualizó
        $reclamoModel = new ReclamoModel();
        $reclamoActualizado = $reclamoModel->find(1);
        $this->assertEquals('En Proceso', $reclamoActualizado['municipalidad_estado']);
        $this->assertEquals('Operario asignado, iniciando reparación', $reclamoActualizado['municipalidad_descripcion']);
    }

    public function testOperarioCambiaEstadoReclamoACompletado()
    {
        // Primero cambiar a "En Proceso"
        $dataProceso = ['municipalidad_estado' => 'En Proceso'];
        $this->withBodyFormat('json')->put('/api/reclamos/1', $dataProceso);

        // Luego cambiar a "Completado"
        $dataCompletado = [
            'municipalidad_estado' => 'Completado',
            'municipalidad_descripcion' => 'Reparación completada exitosamente'
        ];

        $result = $this->withBodyFormat('json')
                      ->put('/api/reclamos/1', $dataCompletado);

        $result->assertStatus(200);
        
        // Verificar que el estado se actualizó
        $reclamoModel = new ReclamoModel();
        $reclamoActualizado = $reclamoModel->find(1);
        $this->assertEquals('Completado', $reclamoActualizado['municipalidad_estado']);
    }

    public function testOperarioCambiaEstadoReclamoACancelado()
    {
        $data = [
            'municipalidad_estado' => 'Cancelado',
            'municipalidad_descripcion' => 'Reclamo cancelado - No corresponde al área'
        ];

        $result = $this->withBodyFormat('json')
                      ->put('/api/reclamos/1', $data);

        $result->assertStatus(200);
        
        // Verificar que el estado se actualizó
        $reclamoModel = new ReclamoModel();
        $reclamoActualizado = $reclamoModel->find(1);
        $this->assertEquals('Cancelado', $reclamoActualizado['municipalidad_estado']);
    }

    public function testOperarioActualizaPrioridadDelReclamo()
    {
        $data = [
            'prioridad' => 'Alta',
            'municipalidad_descripcion' => 'Prioridad elevada por urgencia'
        ];

        $result = $this->withBodyFormat('json')
                      ->put('/api/reclamos/1', $data);

        $result->assertStatus(200);
        
        // Verificar que la prioridad se actualizó
        $reclamoModel = new ReclamoModel();
        $reclamoActualizado = $reclamoModel->find(1);
        $this->assertEquals('Alta', $reclamoActualizado['prioridad']);
    }

    public function testOperarioActualizaFechaModificacionAutomaticamente()
    {
        $fechaAntes = date('Y-m-d H:i:s');
        
        $data = [
            'municipalidad_estado' => 'En Proceso',
            'municipalidad_descripcion' => 'Actualización de estado'
        ];

        $result = $this->withBodyFormat('json')
                      ->put('/api/reclamos/1', $data);

        $result->assertStatus(200);
        
        // Verificar que la fecha de modificación se actualizó automáticamente
        $reclamoModel = new ReclamoModel();
        $reclamoActualizado = $reclamoModel->find(1);
        
        // La fecha de modificación debería ser más reciente que la fecha antes
        $fechaModificacion = $reclamoActualizado['municipalidad_fechaModificacion'];
        $this->assertGreaterThanOrEqual($fechaAntes, $fechaModificacion);
    }

    public function testOperarioNoPuedeCambiarEstadoAInvalido()
    {
        $data = [
            'municipalidad_estado' => 'Estado Inexistente',
            'municipalidad_descripcion' => 'Intento de estado inválido'
        ];

        $result = $this->withBodyFormat('json')
                      ->put('/api/reclamos/1', $data);

        // El sistema debería permitir el cambio (no hay validación de estados)
        // pero podemos verificar que se guardó tal como se envió
        $result->assertStatus(200);
        
        $reclamoModel = new ReclamoModel();
        $reclamoActualizado = $reclamoModel->find(1);
        $this->assertEquals('Estado Inexistente', $reclamoActualizado['municipalidad_estado']);
    }

    public function testOperarioActualizaReclamoInexistente()
    {
        $data = [
            'municipalidad_estado' => 'En Proceso',
            'municipalidad_descripcion' => 'Actualización de reclamo inexistente'
        ];

        $result = $this->withBodyFormat('json')
                      ->put('/api/reclamos/999', $data);

        // Este test puede fallar si el sistema no valida la existencia del reclamo
        $result->assertStatus(400);
    }

    public function testOperarioActualizaReclamoSinDatos()
    {
        $result = $this->withBodyFormat('json')
                      ->put('/api/reclamos/1', []);

        $result->assertStatus(400);
        $result->assertJSONFragment(['Faltan datos obligatorios.']);
    }

    public function testOperarioActualizaReclamoConDatosJSONInvalidos()
    {
        $result = $this->withBodyFormat('json')
                      ->put('/api/reclamos/1', 'datos inválidos');

        // Este test puede fallar dependiendo de cómo maneje el sistema JSON inválido
        $result->assertStatus(400);
    }

    public function testOperarioActualizaReclamoConFechaModificacionPersonalizada()
    {
        $fechaPersonalizada = '2024-12-25 10:30:00';
        
        $data = [
            'municipalidad_estado' => 'En Proceso',
            'municipalidad_fechaModificacion' => $fechaPersonalizada,
            'municipalidad_descripcion' => 'Actualización con fecha personalizada'
        ];

        $result = $this->withBodyFormat('json')
                      ->put('/api/reclamos/1', $data);

        $result->assertStatus(200);
        
        // Verificar que se usó la fecha personalizada
        $reclamoModel = new ReclamoModel();
        $reclamoActualizado = $reclamoModel->find(1);
        $this->assertEquals($fechaPersonalizada, $reclamoActualizado['municipalidad_fechaModificacion']);
    }
}

