<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\RolModel;

class RolIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $migrate = true;

    public function testRolCRUDOperations()
    {
        $model = new RolModel();
        
        // Crear un nuevo rol
        $rolData = [
            'nombre' => 'Supervisor'
        ];
        
        $rolId = $model->insert($rolData);
        $this->assertIsInt($rolId);
        $this->assertGreaterThan(0, $rolId);
        
        // Leer el rol creado
        $rolCreado = $model->find($rolId);
        $this->assertNotNull($rolCreado);
        $this->assertEquals($rolData['nombre'], $rolCreado['nombre']);
        
        // Actualizar el rol
        $actualizado = $model->update($rolId, ['nombre' => 'Supervisor Senior']);
        $this->assertTrue($actualizado);
        
        // Verificar la actualización
        $rolActualizado = $model->find($rolId);
        $this->assertEquals('Supervisor Senior', $rolActualizado['nombre']);
        
        // Eliminar el rol
        $eliminado = $model->delete($rolId);
        $this->assertTrue($eliminado);
        
        // Verificar que fue eliminado
        $rolEliminado = $model->find($rolId);
        $this->assertNull($rolEliminado);
    }

    public function testRolFindAllReturnsCorrectData()
    {
        $model = new RolModel();
        
        // Crear varios roles para probar
        $roles = [
            ['nombre' => 'Operador'],
            ['nombre' => 'Técnico'],
            ['nombre' => 'Coordinador']
        ];
        
        $ids = [];
        foreach ($roles as $rol) {
            $ids[] = $model->insert($rol);
        }
        
        // Probar findAll
        $resultado = $model->findAll();
        
        $this->assertIsArray($resultado);
        $this->assertGreaterThanOrEqual(count($roles), count($resultado));
        
        // Verificar que cada rol tenga la estructura correcta
        foreach ($resultado as $rol) {
            $this->assertArrayHasKey('id', $rol);
            $this->assertArrayHasKey('nombre', $rol);
        }
        
        // Limpiar
        foreach ($ids as $id) {
            $model->delete($id);
        }
    }
}
