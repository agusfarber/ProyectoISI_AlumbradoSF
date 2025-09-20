<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Controllers\Api\Reclamos;

class ReclamosApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $migrate = true;

    public function testReclamosControllerInstance()
    {
        $controller = new Reclamos();
        $this->assertInstanceOf(Reclamos::class, $controller);
    }

    public function testReclamosControllerExtendsResourceController()
    {
        $controller = new Reclamos();
        $this->assertInstanceOf(\CodeIgniter\RESTful\ResourceController::class, $controller);
    }

    public function testReclamosControllerMethodsExist()
    {
        $controller = new Reclamos();
        
        $this->assertTrue(method_exists($controller, 'index'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'update'));
        $this->assertTrue(method_exists($controller, 'delete'));
    }

    public function testReclamosControllerFormat()
    {
        $controller = new Reclamos();
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('format');
        $property->setAccessible(true);
        $this->assertEquals('json', $property->getValue($controller));
    }

    public function testReclamosControllerHasFormatearFechaMethod()
    {
        $controller = new Reclamos();
        $this->assertTrue(method_exists($controller, 'formatearFecha'));
    }
}
