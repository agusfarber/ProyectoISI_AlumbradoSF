<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\UsuarioModel;

class UsuarioModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = 'Tests\Support\Database\Seeds\UsuarioSeeder';
    protected $namespace = 'App';
    protected $migrate = true;

    public function testValidateLoginByLegajoSuccess()
    {
        $model = new UsuarioModel();
        
        // Datos de prueba válidos
        $legajo = '12345';
        $contrasena = 'password123';
        
        $result = $model->validateLoginByLegajo($legajo, $contrasena);
        
        $this->assertNotFalse($result);
        $this->assertEquals($legajo, $result['legajo']);
        $this->assertEquals($contrasena, $result['contrasena']);
    }

    public function testValidateLoginByLegajoFailure()
    {
        $model = new UsuarioModel();
        
        // Datos de prueba inválidos
        $legajo = '12345';
        $contrasena = 'wrongpassword';
        
        $result = $model->validateLoginByLegajo($legajo, $contrasena);
        
        $this->assertFalse($result);
    }

    public function testValidateLoginByEmailSuccess()
    {
        $model = new UsuarioModel();
        
        // Datos de prueba válidos
        $email = 'test@example.com';
        $contrasena = 'password123';
        
        $result = $model->validateLoginByEmail($email, $contrasena);
        
        $this->assertNotFalse($result);
        $this->assertEquals($email, $result['email']);
        $this->assertEquals($contrasena, $result['contrasena']);
    }

    public function testValidateLoginByEmailFailure()
    {
        $model = new UsuarioModel();
        
        // Datos de prueba inválidos
        $email = 'test@example.com';
        $contrasena = 'wrongpassword';
        
        $result = $model->validateLoginByEmail($email, $contrasena);
        
        $this->assertFalse($result);
    }
}
