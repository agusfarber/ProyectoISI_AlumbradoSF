<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run()
    {
        // Crear roles primero
        $rolesData = [
            ['nombre' => 'Usuario'],
            ['nombre' => 'Administrador']
        ];
        $this->db->table('rol')->insertBatch($rolesData);

        $data = [
            [
                'nombre' => 'Usuario Test',
                'email' => 'test@example.com',
                'legajo' => '12345',
                'contrasena' => 'password123',
                'idRol' => 1
            ],
            [
                'nombre' => 'Admin Test',
                'email' => 'admin@example.com',
                'legajo' => '67890',
                'contrasena' => 'adminpass',
                'idRol' => 2
            ]
        ];

        $this->db->table('usuario')->insertBatch($data);
    }
}
