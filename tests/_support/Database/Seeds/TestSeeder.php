<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run()
    {
        // Insertar roles para los tests
        $this->db->table('rol')->insertBatch([
            ['id' => 1, 'nombre' => 'Administrador', 'descripcion' => 'Rol de administrador para pruebas'],
            ['id' => 2, 'nombre' => 'Supervisor', 'descripcion' => 'Rol de supervisor para pruebas'],
            ['id' => 3, 'nombre' => 'Operario', 'descripcion' => 'Rol de operario para pruebas']
        ]);

        // Insertar usuarios de prueba
        $this->db->table('usuario')->insertBatch([
            [
                'nombre' => 'Supervisor Test',
                'email' => 'supervisor@test.com',
                'legajo' => '10001',
                'contrasena' => 'password123',
                'idRol' => 2
            ],
            [
                'nombre' => 'Operario Test',
                'email' => 'operario@test.com',
                'legajo' => '20001',
                'contrasena' => 'password123',
                'idRol' => 3
            ],
            [
                'nombre' => 'Usuario Inactivo',
                'email' => 'inactivo@test.com',
                'legajo' => '30001',
                'contrasena' => 'password123',
                'idRol' => 3
            ]
        ]);

        // Insertar tipos de materiales para los tests
        $this->db->table('tipo_material')->insertBatch([
            ['id' => 1, 'nombre' => 'Lámpara LED'],
            ['id' => 2, 'nombre' => 'Lámpara de Sodio'],
            ['id' => 3, 'nombre' => 'Cable Eléctrico'],
            ['id' => 4, 'nombre' => 'Poste']
        ]);
    }
}

