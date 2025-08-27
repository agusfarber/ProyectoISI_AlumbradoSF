<?php

namespace Tests\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Controllers\Api\Usuarios;

class UsuariosApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $migrate = true;

    public function testUsuarioControllerInstance()
    {
        $controller = new Usuarios();
        $this->assertInstanceOf(Usuarios::class, $controller);
    }

    public function testUsuarioControllerMethodsExist()
    {
        $controller = new Usuarios();
        
        $this->assertTrue(method_exists($controller, 'index'));
        $this->assertTrue(method_exists($controller, 'create'));
        $this->assertTrue(method_exists($controller, 'update'));
        $this->assertTrue(method_exists($controller, 'delete'));
    }

    public function testUsuarioControllerExtendsResourceController()
    {
        $controller = new Usuarios();
        $this->assertInstanceOf(\CodeIgniter\RESTful\ResourceController::class, $controller);
    }
}
