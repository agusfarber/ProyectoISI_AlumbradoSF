<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Controllers\Api\Roles;

class RolesApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $migrate = true;

    public function testRolesControllerInstance()
    {
        $controller = new Roles();
        $this->assertInstanceOf(Roles::class, $controller);
    }

    public function testRolesControllerExtendsResourceController()
    {
        $controller = new Roles();
        $this->assertInstanceOf(\CodeIgniter\RESTful\ResourceController::class, $controller);
    }

    public function testRolesControllerMethodsExist()
    {
        $controller = new Roles();
        
        $this->assertTrue(method_exists($controller, 'index'));
    }

    public function testRolesControllerFormat()
    {
        $controller = new Roles();
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('format');
        $property->setAccessible(true);
        $this->assertEquals('json', $property->getValue($controller));
    }
}
