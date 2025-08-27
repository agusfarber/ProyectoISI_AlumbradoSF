<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\UsuarioModel;

class UsuarioIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';

    public function testUsuarioCRUDOperations()
    {
        $model = new UsuarioModel();
        
        // Crear un nuevo usuario
        $usuarioData = [
            'nombre' => 'Juan Pérez',
            'email' => 'juan.perez@test.com',
            'legajo' => 'EMP001',
            'contrasena' => 'password123',
            'idRol' => 1
        ];
        
        $usuarioId = $model->insert($usuarioData);
        $this->assertIsInt($usuarioId);
        $this->assertGreaterThan(0, $usuarioId);
        
        // Leer el usuario creado
        $usuarioCreado = $model->find($usuarioId);
        $this->assertNotNull($usuarioCreado);
        $this->assertEquals($usuarioData['nombre'], $usuarioCreado['nombre']);
        $this->assertEquals($usuarioData['email'], $usuarioCreado['email']);
        $this->assertEquals($usuarioData['legajo'], $usuarioCreado['legajo']);
        
        // Actualizar el usuario
        $usuarioData['nombre'] = 'Juan Carlos Pérez';
        $usuarioData['id'] = $usuarioId;
        
        $actualizado = $model->update($usuarioId, ['nombre' => 'Juan Carlos Pérez']);
        $this->assertTrue($actualizado);
        
        // Verificar la actualización
        $usuarioActualizado = $model->find($usuarioId);
        $this->assertEquals('Juan Carlos Pérez', $usuarioActualizado['nombre']);
        
        // Eliminar el usuario
        $eliminado = $model->delete($usuarioId);
        $this->assertTrue($eliminado);
        
        // Verificar que fue eliminado
        $usuarioEliminado = $model->find($usuarioId);
        $this->assertNull($usuarioEliminado);
    }

    public function testUsuarioValidationWithDatabase()
    {
        $model = new UsuarioModel();
        
        // Crear un usuario para pruebas de validación
        $usuarioData = [
            'nombre' => 'María García',
            'email' => 'maria.garcia@test.com',
            'legajo' => 'EMP002',
            'contrasena' => 'securepass456',
            'idRol' => 2
        ];
        
        $usuarioId = $model->insert($usuarioData);
        
        // Probar validación por legajo
        $resultadoLegajo = $model->validateLoginByLegajo('EMP002', 'securepass456');
        $this->assertNotFalse($resultadoLegajo);
        $this->assertEquals($usuarioId, $resultadoLegajo['id']);
        
        // Probar validación por email
        $resultadoEmail = $model->validateLoginByEmail('maria.garcia@test.com', 'securepass456');
        $this->assertNotFalse($resultadoEmail);
        $this->assertEquals($usuarioId, $resultadoEmail['id']);
        
        // Limpiar
        $model->delete($usuarioId);
    }
}
