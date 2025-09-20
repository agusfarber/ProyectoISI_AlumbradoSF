<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Controllers\Api\Token103;

class Token103ApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $migrate = true;

    public function testToken103ControllerInstance()
    {
        $controller = new Token103();
        $this->assertInstanceOf(Token103::class, $controller);
    }

    public function testToken103ControllerExtendsResourceController()
    {
        $controller = new Token103();
        $this->assertInstanceOf(\CodeIgniter\RESTful\ResourceController::class, $controller);
    }

    public function testToken103ControllerMethodsExist()
    {
        $controller = new Token103();
        
        $this->assertTrue(method_exists($controller, 'index'));
        $this->assertTrue(method_exists($controller, 'show'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'update'));
        $this->assertTrue(method_exists($controller, 'delete'));
    }

    public function testToken103ControllerFormat()
    {
        $controller = new Token103();
        $reflection = new \ReflectionClass($controller);
        $property = $reflection->getProperty('format');
        $property->setAccessible(true);
        $this->assertEquals('json', $property->getValue($controller));
    }

    public function testToken103ControllerHasGenerarTokenExternoMethod()
    {
        $controller = new Token103();
        $this->assertTrue(method_exists($controller, 'generarTokenExterno'));
    }
}
