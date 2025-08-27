<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\MaterialModel;

class MaterialIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';

    public function testMaterialCRUDWithTipoRelationship()
    {
        $model = new MaterialModel();
        
        // Crear un nuevo material
        $materialData = [
            'nombre' => 'Cable Eléctrico 2x1.5',
            'idTipo' => 1,
            'cantidad' => 100
        ];
        
        $materialId = $model->insert($materialData);
        $this->assertIsInt($materialId);
        $this->assertGreaterThan(0, $materialId);
        
        // Leer el material con su tipo
        $materialConTipo = $model->findAllWithTipo();
        $materialEncontrado = null;
        
        foreach ($materialConTipo as $material) {
            if ($material['id'] == $materialId) {
                $materialEncontrado = $material;
                break;
            }
        }
        
        $this->assertNotNull($materialEncontrado);
        $this->assertEquals($materialData['nombre'], $materialEncontrado['nombre']);
        $this->assertEquals($materialData['idTipo'], $materialEncontrado['idTipo']);
        $this->assertEquals($materialData['cantidad'], $materialEncontrado['cantidad']);
        $this->assertArrayHasKey('tipo_nombre', $materialEncontrado);
        
        // Actualizar la cantidad
        $actualizado = $model->update($materialId, ['cantidad' => 150]);
        $this->assertTrue($actualizado);
        
        // Verificar la actualización
        $materialActualizado = $model->find($materialId);
        $this->assertEquals(150, $materialActualizado['cantidad']);
        
        // Limpiar
        $model->delete($materialId);
    }

    public function testMaterialFindAllWithTipoReturnsCompleteData()
    {
        $model = new MaterialModel();
        
        // Crear varios materiales para probar
        $materiales = [
            ['nombre' => 'Lámpara LED', 'idTipo' => 1, 'cantidad' => 50],
            ['nombre' => 'Interruptor Simple', 'idTipo' => 2, 'cantidad' => 25],
            ['nombre' => 'Cable de Conexión', 'idTipo' => 1, 'cantidad' => 200]
        ];
        
        $ids = [];
        foreach ($materiales as $material) {
            $ids[] = $model->insert($material);
        }
        
        // Probar findAllWithTipo
        $resultado = $model->findAllWithTipo();
        
        $this->assertIsArray($resultado);
        $this->assertGreaterThanOrEqual(count($materiales), count($resultado));
        
        // Verificar que cada material tenga la estructura correcta
        foreach ($resultado as $material) {
            $this->assertArrayHasKey('id', $material);
            $this->assertArrayHasKey('nombre', $material);
            $this->assertArrayHasKey('idTipo', $material);
            $this->assertArrayHasKey('cantidad', $material);
            $this->assertArrayHasKey('tipo_nombre', $material);
        }
        
        // Limpiar
        foreach ($ids as $id) {
            $model->delete($id);
        }
    }
}
