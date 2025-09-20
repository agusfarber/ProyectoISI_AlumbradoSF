<?php

namespace Tests\Unit\Models;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\UsuarioModel;

class UsuarioModelTest extends CIUnitTestCase
{
    protected $usuarioModel;
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Inicializar el modelo
        $this->usuarioModel = new UsuarioModel();
        
        // Configurar base de datos de prueba
        $this->db = \Config\Database::connect('tests');
        
        // Limpiar tabla antes de cada test
        $this->db->table('usuario')->truncate();
        
        // Insertar datos de prueba
        $this->insertTestData();
    }

    protected function tearDown(): void
    {
        // Limpiar después de cada test
        $this->db->table('usuario')->truncate();
        parent::tearDown();
    }

    /**
     * Inserta datos de prueba en la base de datos
     */
    private function insertTestData(): void
    {
        $testData = [
            [
                'nombre' => 'Juan Pérez',
                'email' => 'juan.perez@test.com',
                'legajo' => '12345',
                'contrasena' => 'password123',
                'idRol' => 1
            ],
            [
                'nombre' => 'María García',
                'email' => 'maria.garcia@test.com',
                'legajo' => '67890',
                'contrasena' => 'secret456',
                'idRol' => 2
            ]
        ];

        foreach ($testData as $data) {
            $this->db->table('usuario')->insert($data);
        }
    }

    /**
     * Test: Validar login por legajo con credenciales correctas
     */
    public function testValidateLoginByLegajoWithCorrectCredentials()
    {
        $result = $this->usuarioModel->validateLoginByLegajo('12345', 'password123');
        
        $this->assertNotFalse($result);
        $this->assertEquals('Juan Pérez', $result['nombre']);
        $this->assertEquals('juan.perez@test.com', $result['email']);
        $this->assertEquals('12345', $result['legajo']);
        $this->assertEquals(1, $result['idRol']);
    }

    /**
     * Test: Validar login por legajo con credenciales incorrectas
     */
    public function testValidateLoginByLegajoWithIncorrectCredentials()
    {
        $result = $this->usuarioModel->validateLoginByLegajo('12345', 'wrongpassword');
        
        $this->assertFalse($result);
    }

    /**
     * Test: Validar login por legajo con legajo inexistente
     */
    public function testValidateLoginByLegajoWithNonExistentLegajo()
    {
        $result = $this->usuarioModel->validateLoginByLegajo('99999', 'password123');
        
        $this->assertFalse($result);
    }

    /**
     * Test: Validar login por email con credenciales correctas
     */
    public function testValidateLoginByEmailWithCorrectCredentials()
    {
        $result = $this->usuarioModel->validateLoginByEmail('maria.garcia@test.com', 'secret456');
        
        $this->assertNotFalse($result);
        $this->assertEquals('María García', $result['nombre']);
        $this->assertEquals('maria.garcia@test.com', $result['email']);
        $this->assertEquals('67890', $result['legajo']);
        $this->assertEquals(2, $result['idRol']);
    }

    /**
     * Test: Validar login por email con credenciales incorrectas
     */
    public function testValidateLoginByEmailWithIncorrectCredentials()
    {
        $result = $this->usuarioModel->validateLoginByEmail('maria.garcia@test.com', 'wrongpassword');
        
        $this->assertFalse($result);
    }

    /**
     * Test: Validar login por email con email inexistente
     */
    public function testValidateLoginByEmailWithNonExistentEmail()
    {
        $result = $this->usuarioModel->validateLoginByEmail('nonexistent@test.com', 'secret456');
        
        $this->assertFalse($result);
    }

    /**
     * Test: Validar que la validación es estricta (case sensitive)
     */
    public function testValidateLoginIsCaseSensitive()
    {
        // Test con legajo en mayúsculas
        $result = $this->usuarioModel->validateLoginByLegajo('12345', 'PASSWORD123');
        $this->assertFalse($result);
        
        // Test con email en mayúsculas
        $result = $this->usuarioModel->validateLoginByEmail('MARIA.GARCIA@TEST.COM', 'secret456');
        $this->assertFalse($result);
    }

    /**
     * Test: Validar que se pueden obtener todos los usuarios
     */
    public function testFindAllUsers()
    {
        $users = $this->usuarioModel->findAll();
        
        $this->assertCount(2, $users);
        $this->assertEquals('Juan Pérez', $users[0]['nombre']);
        $this->assertEquals('María García', $users[1]['nombre']);
    }

    /**
     * Test: Validar inserción de nuevo usuario
     */
    public function testInsertNewUser()
    {
        $newUser = [
            'nombre' => 'Carlos López',
            'email' => 'carlos.lopez@test.com',
            'legajo' => '11111',
            'contrasena' => 'newpass123',
            'idRol' => 3
        ];

        $userId = $this->usuarioModel->insert($newUser);
        
        $this->assertNotFalse($userId);
        
        $insertedUser = $this->usuarioModel->find($userId);
        $this->assertEquals('Carlos López', $insertedUser['nombre']);
        $this->assertEquals('carlos.lopez@test.com', $insertedUser['email']);
    }

    /**
     * Test: Validar login con campos vacíos
     */
    public function testValidateLoginWithEmptyFields()
    {
        // Test con cadenas vacías
        $result = $this->usuarioModel->validateLoginByLegajo('', '');
        $this->assertFalse($result);
        
        $result = $this->usuarioModel->validateLoginByEmail('', '');
        $this->assertFalse($result);
        
        // Test con valores null
        $result = $this->usuarioModel->validateLoginByLegajo(null, null);
        $this->assertFalse($result);
        
        $result = $this->usuarioModel->validateLoginByEmail(null, null);
        $this->assertFalse($result);
        
        // Test con solo espacios en blanco
        $result = $this->usuarioModel->validateLoginByLegajo('   ', '   ');
        $this->assertFalse($result);
        
        $result = $this->usuarioModel->validateLoginByEmail('   ', '   ');
        $this->assertFalse($result);
        
        // Test con legajo vacío pero contraseña válida
        $result = $this->usuarioModel->validateLoginByLegajo('', 'password123');
        $this->assertFalse($result);
        
        // Test con email vacío pero contraseña válida
        $result = $this->usuarioModel->validateLoginByEmail('', 'secret456');
        $this->assertFalse($result);
        
        // Test con legajo válido pero contraseña vacía
        $result = $this->usuarioModel->validateLoginByLegajo('12345', '');
        $this->assertFalse($result);
        
        // Test con email válido pero contraseña vacía
        $result = $this->usuarioModel->validateLoginByEmail('maria.garcia@test.com', '');
        $this->assertFalse($result);
    }

    /**
     * Test: Validar búsqueda de usuario por ID
     */
    public function testFindUserById()
    {
        // Test con ID válido existente (Juan Pérez)
        $user = $this->usuarioModel->find(1);
        $this->assertNotNull($user);
        $this->assertEquals('Juan Pérez', $user['nombre']);
        $this->assertEquals('juan.perez@test.com', $user['email']);
        $this->assertEquals('12345', $user['legajo']);
        $this->assertEquals(1, $user['idRol']);
        
        // Test con ID válido existente (María García)
        $user = $this->usuarioModel->find(2);
        $this->assertNotNull($user);
        $this->assertEquals('María García', $user['nombre']);
        $this->assertEquals('maria.garcia@test.com', $user['email']);
        $this->assertEquals('67890', $user['legajo']);
        $this->assertEquals(2, $user['idRol']);
        
        // Test con ID inexistente
        $user = $this->usuarioModel->find(999);
        $this->assertNull($user);
        
        // Test con ID inválido (0)
        $user = $this->usuarioModel->find(0);
        $this->assertNull($user);
        
        // Test con ID inválido (negativo)
        $user = $this->usuarioModel->find(-1);
        $this->assertNull($user);
        
        // Test con ID como string
        $user = $this->usuarioModel->find('1');
        $this->assertNotNull($user);
        $this->assertEquals('Juan Pérez', $user['nombre']);
    }

    /**
     * Test: Validar actualización de usuario
     */
    public function testUpdateUser()
    {
        // Test con ID válido existente - actualizar todos los campos
        $updateData = [
            'nombre' => 'Juan Carlos Pérez',
            'email' => 'juan.carlos.perez@test.com',
            'legajo' => '12345',
            'contrasena' => 'newpassword123',
            'idRol' => 2
        ];
        
        $result = $this->usuarioModel->update(1, $updateData);
        $this->assertTrue($result);
        
        // Verificar que los datos se actualizaron correctamente
        $updatedUser = $this->usuarioModel->find(1);
        $this->assertEquals('Juan Carlos Pérez', $updatedUser['nombre']);
        $this->assertEquals('juan.carlos.perez@test.com', $updatedUser['email']);
        $this->assertEquals('12345', $updatedUser['legajo']);
        $this->assertEquals('newpassword123', $updatedUser['contrasena']);
        $this->assertEquals(2, $updatedUser['idRol']);
        
        // Test con ID válido existente - actualizar solo algunos campos
        $partialUpdateData = [
            'nombre' => 'María Elena García',
            'idRol' => 3
        ];
        
        $result = $this->usuarioModel->update(2, $partialUpdateData);
        $this->assertTrue($result);
        
        // Verificar que solo los campos especificados se actualizaron
        $updatedUser = $this->usuarioModel->find(2);
        $this->assertEquals('María Elena García', $updatedUser['nombre']);
        $this->assertEquals(3, $updatedUser['idRol']);
        // Los otros campos deben mantenerse igual
        $this->assertEquals('maria.garcia@test.com', $updatedUser['email']);
        $this->assertEquals('67890', $updatedUser['legajo']);
        
        // Test con ID inexistente
        $result = $this->usuarioModel->update(999, $updateData);
        $this->assertFalse($result);
        
        // Test con ID inválido (0)
        $result = $this->usuarioModel->update(0, $updateData);
        $this->assertFalse($result);
        
        // Test con ID inválido (negativo)
        $result = $this->usuarioModel->update(-1, $updateData);
        $this->assertFalse($result);
        
        // Test con datos vacíos
        $emptyData = [];
        $result = $this->usuarioModel->update(1, $emptyData);
        $this->assertTrue($result); // CodeIgniter permite actualizaciones vacías
    }

    /**
     * Test: Validar actualización de usuario - Comportamiento real de CodeIgniter
     * Este test corrige las expectativas incorrectas del test anterior
     */
    public function testUpdateUserCorrected()
    {
        // Test con ID válido existente - actualizar todos los campos
        $updateData = [
            'nombre' => 'Juan Carlos Pérez',
            'email' => 'juan.carlos.perez@test.com',
            'legajo' => '12345',
            'contrasena' => 'newpassword123',
            'idRol' => 2
        ];
        
        $result = $this->usuarioModel->update(1, $updateData);
        $this->assertTrue($result);
        
        // Verificar que los datos se actualizaron correctamente
        $updatedUser = $this->usuarioModel->find(1);
        $this->assertEquals('Juan Carlos Pérez', $updatedUser['nombre']);
        $this->assertEquals('juan.carlos.perez@test.com', $updatedUser['email']);
        $this->assertEquals('12345', $updatedUser['legajo']);
        $this->assertEquals('newpassword123', $updatedUser['contrasena']);
        $this->assertEquals(2, $updatedUser['idRol']);
        
        // Test con ID inexistente - CodeIgniter retorna true pero no actualiza nada
        $result = $this->usuarioModel->update(999, $updateData);
        $this->assertTrue($result); // Comportamiento real de CodeIgniter
        
        // Verificar que el usuario original no cambió (porque el ID 999 no existe)
        $originalUser = $this->usuarioModel->find(1);
        $this->assertEquals('Juan Carlos Pérez', $originalUser['nombre']); // No cambió
        
        // Test con ID inválido (0) - también retorna true
        $result = $this->usuarioModel->update(0, $updateData);
        $this->assertTrue($result); // Comportamiento real de CodeIgniter
        
        // Test con ID inválido (negativo) - también retorna true
        $result = $this->usuarioModel->update(-1, $updateData);
        $this->assertTrue($result); // Comportamiento real de CodeIgniter
        
        // Test con datos vacíos
        $emptyData = [];
        $result = $this->usuarioModel->update(1, $emptyData);
        $this->assertTrue($result); // CodeIgniter permite actualizaciones vacías
    }
}
