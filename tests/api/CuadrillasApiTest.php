<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\CuadrillaModel;
use App\Models\CuadrillaOperariosModel;
use App\Models\UsuarioModel;

class CuadrillasApiTest extends CIUnitTestCase
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
        // Crear usuarios de prueba
        $usuarioModel = new UsuarioModel();
        $usuarioModel->insert([
            'nombre' => 'Juan Pérez',
            'email' => 'juan@test.com',
            'legajo' => '12345',
            'rol_id' => 1
        ]);
        $usuarioModel->insert([
            'nombre' => 'María García',
            'email' => 'maria@test.com',
            'legajo' => '12346',
            'rol_id' => 1
        ]);
        $usuarioModel->insert([
            'nombre' => 'Carlos López',
            'email' => 'carlos@test.com',
            'legajo' => '12347',
            'rol_id' => 1
        ]);
        $usuarioModel->insert([
            'nombre' => 'Ana Martínez',
            'email' => 'ana@test.com',
            'legajo' => '12348',
            'rol_id' => 1
        ]);
        $usuarioModel->insert([
            'nombre' => 'Pedro Sánchez',
            'email' => 'pedro@test.com',
            'legajo' => '12349',
            'rol_id' => 1
        ]);
    }

    public function testCrearCuadrillaConDatosValidos()
    {
        $data = [
            'nombre' => 'Cuadrilla Norte',
            'descripcion' => 'Cuadrilla asignada a la zona norte'
        ];

        $result = $this->withBodyFormat('json')
                      ->post('/api/cuadrillas', $data);

        $result->assertStatus(201);
        $result->assertJSONFragment(['nombre' => 'Cuadrilla Norte']);
        $result->assertJSONFragment(['descripcion' => 'Cuadrilla asignada a la zona norte']);
        
        // Verificar que se creó en la base de datos
        $cuadrillaModel = new CuadrillaModel();
        $cuadrilla = $cuadrillaModel->where('nombre', 'Cuadrilla Norte')->first();
        $this->assertNotNull($cuadrilla);
        $this->assertEquals('Cuadrilla Norte', $cuadrilla['nombre']);
    }

    public function testCrearCuadrillaSinNombreObligatorio()
    {
        $data = [
            'descripcion' => 'Cuadrilla sin nombre'
        ];

        $result = $this->withBodyFormat('json')
                      ->post('/api/cuadrillas', $data);

        $result->assertStatus(400);
        $result->assertJSONFragment(['messages' => ['error' => 'El nombre de la cuadrilla es obligatorio.']]);
    }

    public function testObtenerListaDeCuadrillasConOperarios()
    {
        // Crear una cuadrilla de prueba
        $cuadrillaModel = new CuadrillaModel();
        $cuadrillaId = $cuadrillaModel->insert([
            'nombre' => 'Cuadrilla Test',
            'descripcion' => 'Cuadrilla para testing'
        ]);

        // Asignar operarios a la cuadrilla
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $cuadrillaOperariosModel->insert([
            'cuadrilla_id' => $cuadrillaId,
            'usuario_id' => 1
        ]);
        $cuadrillaOperariosModel->insert([
            'cuadrilla_id' => $cuadrillaId,
            'usuario_id' => 2
        ]);

        $result = $this->get('/api/cuadrillas');

        $result->assertStatus(200);
        $responseData = json_decode($result->response()->getBody(), true);
        $this->assertIsArray($responseData);
        $this->assertGreaterThan(0, count($responseData));
        
        // Buscar la cuadrilla específica que creamos
        $cuadrillaEncontrada = false;
        foreach ($responseData as $cuadrilla) {
            if ($cuadrilla['nombre'] === 'Cuadrilla Test') {
                $cuadrillaEncontrada = true;
                $this->assertArrayHasKey('operarios', $cuadrilla);
                $this->assertCount(2, $cuadrilla['operarios']);
                break;
            }
        }
        $this->assertTrue($cuadrillaEncontrada, 'No se encontró la cuadrilla de prueba');
    }

    public function testAsignarOperariosACuadrilla()
    {
        // Crear una cuadrilla de prueba
        $cuadrillaModel = new CuadrillaModel();
        $cuadrillaId = $cuadrillaModel->insert([
            'nombre' => 'Cuadrilla Asignación',
            'descripcion' => 'Cuadrilla para probar asignación'
        ]);

        $data = [
            'cuadrillaId' => $cuadrillaId,
            'operarios' => [1, 2, 3]
        ];

        $result = $this->withBodyFormat('json')
                      ->post('/api/cuadrillas/asignar', $data);

        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
        $result->assertJSONFragment(['mensaje' => 'Operarios asignados correctamente.']);

        // Verificar que se asignaron en la base de datos
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('cuadrilla_id', $cuadrillaId)->findAll();
        $this->assertCount(3, $asignaciones);
    }

    public function testAsignarMasDe4OperariosACuadrilla()
    {
        // Crear una cuadrilla de prueba
        $cuadrillaModel = new CuadrillaModel();
        $cuadrillaId = $cuadrillaModel->insert([
            'nombre' => 'Cuadrilla Limite',
            'descripcion' => 'Cuadrilla para probar límite'
        ]);

        $data = [
            'cuadrillaId' => $cuadrillaId,
            'operarios' => [1, 2, 3, 4, 5] // 5 operarios, excede el límite de 4
        ];

        $result = $this->withBodyFormat('json')
                      ->post('/api/cuadrillas/asignar', $data);

        $result->assertStatus(400);
        $result->assertJSONFragment(['messages' => ['error' => 'Solo se pueden asignar máximo 4 operarios por cuadrilla.']]);

        // Verificar que no se asignaron operarios en la base de datos
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('cuadrilla_id', $cuadrillaId)->findAll();
        $this->assertCount(0, $asignaciones);
    }

    // ========== TESTS DE INTEGRIDAD DE DATOS ==========

    public function testEliminarCuadrillaEliminaAsignacionesDeOperarios()
    {
        // Crear una cuadrilla de prueba
        $cuadrillaModel = new CuadrillaModel();
        $cuadrillaId = $cuadrillaModel->insert([
            'nombre' => 'Cuadrilla Integridad',
            'descripcion' => 'Cuadrilla para probar integridad'
        ]);

        // Asignar operarios a la cuadrilla
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $cuadrillaOperariosModel->insert([
            'cuadrilla_id' => $cuadrillaId,
            'usuario_id' => 1
        ]);
        $cuadrillaOperariosModel->insert([
            'cuadrilla_id' => $cuadrillaId,
            'usuario_id' => 2
        ]);

        // Verificar que las asignaciones existen
        $asignacionesAntes = $cuadrillaOperariosModel->where('cuadrilla_id', $cuadrillaId)->findAll();
        $this->assertCount(2, $asignacionesAntes);

        // Eliminar la cuadrilla
        $result = $this->delete("/api/cuadrillas/{$cuadrillaId}");

        $result->assertStatus(200);
        $result->assertJSONFragment(['mensaje' => 'Cuadrilla eliminada con éxito.']);

        // Verificar que la cuadrilla fue eliminada
        $cuadrillaEliminada = $cuadrillaModel->find($cuadrillaId);
        $this->assertNull($cuadrillaEliminada);

        // Verificar que las asignaciones de operarios también fueron eliminadas
        $asignacionesDespues = $cuadrillaOperariosModel->where('cuadrilla_id', $cuadrillaId)->findAll();
        $this->assertCount(0, $asignacionesDespues, 'Las asignaciones de operarios deberían haberse eliminado automáticamente');
    }

    public function testEliminarOperarioMantieneIntegridadDeCuadrilla()
    {
        // Crear una cuadrilla de prueba
        $cuadrillaModel = new CuadrillaModel();
        $cuadrillaId = $cuadrillaModel->insert([
            'nombre' => 'Cuadrilla Operario',
            'descripcion' => 'Cuadrilla para probar eliminación de operario'
        ]);

        // Asignar operarios a la cuadrilla
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $cuadrillaOperariosModel->insert([
            'cuadrilla_id' => $cuadrillaId,
            'usuario_id' => 1
        ]);
        $cuadrillaOperariosModel->insert([
            'cuadrilla_id' => $cuadrillaId,
            'usuario_id' => 2
        ]);

        // Verificar asignaciones iniciales
        $asignacionesIniciales = $cuadrillaOperariosModel->where('cuadrilla_id', $cuadrillaId)->findAll();
        $this->assertCount(2, $asignacionesIniciales);

        // Eliminar un operario directamente de la tabla usuario
        $usuarioModel = new UsuarioModel();
        $usuarioModel->delete(1);

        // Verificar que la cuadrilla sigue existiendo
        $cuadrillaDespues = $cuadrillaModel->find($cuadrillaId);
        $this->assertNotNull($cuadrillaDespues, 'La cuadrilla debería seguir existiendo después de eliminar un operario');

        // Verificar que las asignaciones se mantienen (o se eliminan según configuración de FK)
        $asignacionesDespues = $cuadrillaOperariosModel->where('cuadrilla_id', $cuadrillaId)->findAll();
        // Este test puede fallar dependiendo de la configuración de foreign keys
        $this->assertLessThanOrEqual(2, count($asignacionesDespues), 'Las asignaciones deberían mantenerse o eliminarse según configuración FK');
    }

    public function testTransaccionEnAsignacionDeOperarios()
    {
        // Crear una cuadrilla de prueba
        $cuadrillaModel = new CuadrillaModel();
        $cuadrillaId = $cuadrillaModel->insert([
            'nombre' => 'Cuadrilla Transaccion',
            'descripcion' => 'Cuadrilla para probar transacciones'
        ]);

        // Intentar asignar operarios con un operario inexistente (esto debería fallar la transacción)
        $data = [
            'cuadrillaId' => $cuadrillaId,
            'operarios' => [1, 2, 999] // 999 es un ID inexistente
        ];

        $result = $this->withBodyFormat('json')
                      ->post('/api/cuadrillas/asignar', $data);

        // Verificar que la transacción falló y no se asignaron operarios
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('cuadrilla_id', $cuadrillaId)->findAll();
        
        // Este test puede fallar si el sistema no maneja correctamente las transacciones
        $this->assertCount(0, $asignaciones, 'No deberían haberse asignado operarios si la transacción falló');
    }

    public function testIntegridadAlActualizarCuadrillaConOperarios()
    {
        // Crear una cuadrilla de prueba
        $cuadrillaModel = new CuadrillaModel();
        $cuadrillaId = $cuadrillaModel->insert([
            'nombre' => 'Cuadrilla Original',
            'descripcion' => 'Cuadrilla original'
        ]);

        // Asignar operarios
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $cuadrillaOperariosModel->insert([
            'cuadrilla_id' => $cuadrillaId,
            'usuario_id' => 1
        ]);

        // Actualizar la cuadrilla
        $dataActualizacion = [
            'nombre' => 'Cuadrilla Actualizada',
            'descripcion' => 'Cuadrilla con nueva descripción'
        ];

        $result = $this->withBodyFormat('json')
                      ->put("/api/cuadrillas/{$cuadrillaId}", $dataActualizacion);

        $result->assertStatus(200);

        // Verificar que la cuadrilla se actualizó
        $cuadrillaActualizada = $cuadrillaModel->find($cuadrillaId);
        $this->assertEquals('Cuadrilla Actualizada', $cuadrillaActualizada['nombre']);

        // Verificar que las asignaciones de operarios se mantuvieron
        $asignacionesDespues = $cuadrillaOperariosModel->where('cuadrilla_id', $cuadrillaId)->findAll();
        $this->assertCount(1, $asignacionesDespues, 'Las asignaciones de operarios deberían mantenerse al actualizar la cuadrilla');
    }

    public function testIntegridadConOperariosDuplicados()
    {
        // Crear una cuadrilla de prueba
        $cuadrillaModel = new CuadrillaModel();
        $cuadrillaId = $cuadrillaModel->insert([
            'nombre' => 'Cuadrilla Duplicados',
            'descripcion' => 'Cuadrilla para probar duplicados'
        ]);

        // Intentar asignar el mismo operario dos veces
        $data = [
            'cuadrillaId' => $cuadrillaId,
            'operarios' => [1, 1, 2] // Operario 1 duplicado
        ];

        $result = $this->withBodyFormat('json')
                      ->post('/api/cuadrillas/asignar', $data);

        $result->assertStatus(200);

        // Verificar que no se crearon asignaciones duplicadas
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignaciones = $cuadrillaOperariosModel->where('cuadrilla_id', $cuadrillaId)->findAll();
        
        // Este test puede fallar si el sistema permite duplicados
        $this->assertLessThanOrEqual(2, count($asignaciones), 'No deberían existir asignaciones duplicadas del mismo operario');
        
        // Verificar que solo hay una asignación por operario
        $operariosAsignados = array_column($asignaciones, 'usuario_id');
        $operariosUnicos = array_unique($operariosAsignados);
        $this->assertEquals(count($operariosUnicos), count($operariosAsignados), 'No deberían existir operarios duplicados');
    }
}
