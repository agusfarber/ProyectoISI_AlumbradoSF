<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\ReclamoModel;

class ReclamoIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $migrate = true;

    public function testReclamoCRUDOperations()
    {
        $model = new ReclamoModel();
        
        // Crear un nuevo reclamo
        $reclamoData = [
            'municipalidad_id' => 'REC001',
            'municipalidad_tipo' => 'Alumbrado',
            'municipalidad_motivo' => 'Lámpara apagada',
            'municipalidad_fechaInicio' => '2024-12-01',
            'municipalidad_fechaModificacion' => '2024-12-01',
            'municipalidad_recepcion' => 'Web',
            'municipalidad_estado' => 'Pendiente',
            'municipalidad_telefono' => '123456789',
            'municipalidad_domicilio' => 'Av. San Martín',
            'municipalidad_numeroDomicilio' => '123',
            'municipalidad_entreCalleUno' => 'Belgrano',
            'municipalidad_entreCalleDos' => 'Mitre',
            'municipalidad_ciudadano' => 'Juan Pérez',
            'municipalidad_descripcion' => 'Lámpara de la esquina apagada',
            'prioridad' => 'Media'
        ];
        
        $reclamoId = $model->insert($reclamoData);
        $this->assertIsInt($reclamoId);
        $this->assertGreaterThan(0, $reclamoId);
        
        // Leer el reclamo creado
        $reclamoCreado = $model->find($reclamoId);
        $this->assertNotNull($reclamoCreado);
        $this->assertEquals($reclamoData['municipalidad_id'], $reclamoCreado['municipalidad_id']);
        $this->assertEquals($reclamoData['municipalidad_tipo'], $reclamoCreado['municipalidad_tipo']);
        
        // Actualizar el reclamo
        $actualizado = $model->update($reclamoId, ['municipalidad_estado' => 'En Proceso']);
        $this->assertTrue($actualizado);
        
        // Verificar la actualización
        $reclamoActualizado = $model->find($reclamoId);
        $this->assertEquals('En Proceso', $reclamoActualizado['municipalidad_estado']);
        
        // Eliminar el reclamo
        $eliminado = $model->delete($reclamoId);
        $this->assertTrue($eliminado);
        
        // Verificar que fue eliminado
        $reclamoEliminado = $model->find($reclamoId);
        $this->assertNull($reclamoEliminado);
    }

    public function testReclamoFindAllReturnsCorrectData()
    {
        $model = new ReclamoModel();
        
        // Crear varios reclamos para probar
        $reclamos = [
            [
                'municipalidad_id' => 'REC002',
                'municipalidad_tipo' => 'Alumbrado',
                'municipalidad_motivo' => 'Poste caído',
                'municipalidad_fechaInicio' => '2024-12-01',
                'municipalidad_fechaModificacion' => '2024-12-01',
                'municipalidad_recepcion' => 'Teléfono',
                'municipalidad_estado' => 'Pendiente',
                'municipalidad_telefono' => '987654321',
                'municipalidad_domicilio' => 'Calle Rivadavia',
                'municipalidad_numeroDomicilio' => '456',
                'municipalidad_entreCalleUno' => 'Sarmiento',
                'municipalidad_entreCalleDos' => 'Moreno',
                'municipalidad_ciudadano' => 'María García',
                'municipalidad_descripcion' => 'Poste de luz caído en la vereda',
                'prioridad' => 'Alta'
            ]
        ];
        
        $ids = [];
        foreach ($reclamos as $reclamo) {
            $ids[] = $model->insert($reclamo);
        }
        
        // Probar findAll
        $resultado = $model->findAll();
        
        $this->assertIsArray($resultado);
        $this->assertGreaterThanOrEqual(count($reclamos), count($resultado));
        
        // Verificar que cada reclamo tenga la estructura correcta
        foreach ($resultado as $reclamo) {
            $this->assertArrayHasKey('id', $reclamo);
            $this->assertArrayHasKey('municipalidad_id', $reclamo);
            $this->assertArrayHasKey('municipalidad_tipo', $reclamo);
            $this->assertArrayHasKey('municipalidad_estado', $reclamo);
        }
        
        // Limpiar
        foreach ($ids as $id) {
            $model->delete($id);
        }
    }
}
