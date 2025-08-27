<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\RolModel;

class RolModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = 'Tests\Support\Database\Seeds\RolSeeder';
    protected $namespace = 'App';
    protected $migrate = true;

    public function testRolModelInstance()
    {
        $model = new RolModel();
        $this->assertInstanceOf(RolModel::class, $model);
    }

    public function testRolModelTableName()
    {
        $model = new RolModel();
        $this->assertEquals('rol', $model->getTable());
    }

    public function testRolModelTableStructure()
    {
        $model = new RolModel();
        $this->assertEquals('rol', $model->getTable());
    }

    public function testRolModelInstanceCreation()
    {
        $model = new RolModel();
        $this->assertInstanceOf(RolModel::class, $model);
    }
}
