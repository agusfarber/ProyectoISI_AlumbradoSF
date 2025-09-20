<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\Tipo_materialModel;

class TipoMaterialIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $migrate = true;

    public function testTipoMaterialCRUDOperations()
    {
        $model = new Tipo_materialModel();
        
        // Crear un nuevo tipo de material
        $tipoData = [
            'nombre' => 'Transformador'
        ];
        
        $tipoId = $model->insert($tipoData);
        $this->assertIsInt($tipoId);
        $this->assertGreaterThan(0, $tipoId);
        
        // Leer el tipo creado
        $tipoCreado = $model->find($tipoId);
        $this->assertNotNull($tipoCreado);
        $this->assertEquals($tipoData['nombre'], $tipoCreado['nombre']);
        
        // Actualizar el tipo
        $actualizado = $model->update($tipoId, ['nombre' => 'Transformador de Potencia']);
        $this->assertTrue($actualizado);
        
        // Verificar la actualización
        $tipoActualizado = $model->find($tipoId);
        $this->assertEquals('Transformador de Potencia', $tipoActualizado['nombre']);
        
        // Eliminar el tipo
        $eliminado = $model->delete($tipoId);
        $this->assertTrue($eliminado);
        
        // Verificar que fue eliminado
        $tipoEliminado = $model->find($tipoId);
        $this->assertNull($tipoEliminado);
    }

    public function testTipoMaterialFindAllReturnsCorrectData()
    {
        $model = new Tipo_materialModel();
        
        // Crear varios tipos para probar
        $tipos = [
            ['nombre' => 'Fusible'],
            ['nombre' => 'Cableado'],
            ['nombre' => 'Iluminación']
        ];
        
        $ids = [];
        foreach ($tipos as $tipo) {
            $ids[] = $model->insert($tipo);
        }
        
        // Probar findAll
        $resultado = $model->findAll();
        
        $this->assertIsArray($resultado);
        $this->assertGreaterThanOrEqual(count($tipos), count($resultado));
        
        // Verificar que cada tipo tenga la estructura correcta
        foreach ($resultado as $tipo) {
            $this->assertArrayHasKey('id', $tipo);
            $this->assertArrayHasKey('nombre', $tipo);
        }
        
        // Limpiar
        foreach ($ids as $id) {
            $model->delete($id);
        }
    }
}
