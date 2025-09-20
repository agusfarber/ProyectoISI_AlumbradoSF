<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\ReclamoModel;

class ReclamoModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = 'Tests\Support\Database\Seeds\ReclamoSeeder';
    protected $namespace = 'App';
    protected $migrate = true;

    public function testReclamoModelInstance()
    {
        $model = new ReclamoModel();
        $this->assertInstanceOf(ReclamoModel::class, $model);
    }

    public function testReclamoModelTableName()
    {
        $model = new ReclamoModel();
        $this->assertEquals('reclamo', $model->getTable());
    }

    public function testReclamoModelTableStructure()
    {
        $model = new ReclamoModel();
        $this->assertEquals('reclamo', $model->getTable());
    }

    public function testReclamoModelInstanceCreation()
    {
        $model = new ReclamoModel();
        $this->assertInstanceOf(ReclamoModel::class, $model);
    }
}
