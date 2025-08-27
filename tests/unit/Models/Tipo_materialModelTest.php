<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\Tipo_materialModel;

class Tipo_materialModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = 'Tests\Support\Database\Seeds\TipoMaterialSeeder';
    protected $namespace = 'App';
    protected $migrate = true;

    public function testTipoMaterialModelInstance()
    {
        $model = new Tipo_materialModel();
        $this->assertInstanceOf(Tipo_materialModel::class, $model);
    }

    public function testTipoMaterialModelTableName()
    {
        $model = new Tipo_materialModel();
        $this->assertEquals('tipo_material', $model->getTable());
    }

    public function testTipoMaterialModelTableStructure()
    {
        $model = new Tipo_materialModel();
        $this->assertEquals('tipo_material', $model->getTable());
    }

    public function testTipoMaterialModelInstanceCreation()
    {
        $model = new Tipo_materialModel();
        $this->assertInstanceOf(Tipo_materialModel::class, $model);
    }
}
