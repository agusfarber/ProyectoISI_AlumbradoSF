<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Controllers\Api\Materiales;

class MaterialesApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $migrate = true;

    public function testMaterialControllerInstance()
    {
        $controller = new Materiales();
        $this->assertInstanceOf(Materiales::class, $controller);
    }

    public function testMaterialControllerMethodsExist()
    {
        $controller = new Materiales();
        
        $this->assertTrue(method_exists($controller, 'index'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'update'));
        $this->assertTrue(method_exists($controller, 'delete'));
        $this->assertTrue(method_exists($controller, 'import'));
        $this->assertTrue(method_exists($controller, 'getTipos'));
        $this->assertTrue(method_exists($controller, 'createTipo'));
        $this->assertTrue(method_exists($controller, 'deleteTipo'));
    }

    public function testMaterialControllerExtendsResourceController()
    {
        $controller = new Materiales();
        $this->assertInstanceOf(\CodeIgniter\RESTful\ResourceController::class, $controller);
    }
}
