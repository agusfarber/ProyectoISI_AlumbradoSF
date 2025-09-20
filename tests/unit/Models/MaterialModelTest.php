<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\MaterialModel;

class MaterialModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = 'Tests\Support\Database\Seeds\MaterialSeeder';
    protected $namespace = 'App';
    protected $migrate = true;

    public function testFindAllWithTipoReturnsCorrectStructure()
    {
        $model = new MaterialModel();
        
        $result = $model->findAllWithTipo();
        
        $this->assertIsArray($result);
        
        if (!empty($result)) {
            $firstItem = $result[0];
            $this->assertArrayHasKey('id', $firstItem);
            $this->assertArrayHasKey('nombre', $firstItem);
            $this->assertArrayHasKey('idTipo', $firstItem);
            $this->assertArrayHasKey('cantidad', $firstItem);
            $this->assertArrayHasKey('tipo_nombre', $firstItem);
        }
    }

    public function testFindAllWithTipoJoinWorksCorrectly()
    {
        $model = new MaterialModel();
        
        $result = $model->findAllWithTipo();
        
        if (!empty($result)) {
            foreach ($result as $item) {
                // Verificar que tipo_nombre no esté vacío si idTipo existe
                if (!empty($item['idTipo'])) {
                    $this->assertNotEmpty($item['tipo_nombre']);
                }
            }
        }
    }

    public function testFindAllWithTipoReturnsAllFields()
    {
        $model = new MaterialModel();
        
        $result = $model->findAllWithTipo();
        
        if (!empty($result)) {
            $firstItem = $result[0];
            
            // Verificar que todos los campos del material estén presentes
            $this->assertArrayHasKey('id', $firstItem);
            $this->assertArrayHasKey('nombre', $firstItem);
            $this->assertArrayHasKey('idTipo', $firstItem);
            $this->assertArrayHasKey('cantidad', $firstItem);
            
            // Verificar que el campo del join esté presente
            $this->assertArrayHasKey('tipo_nombre', $firstItem);
        }
    }
}
