<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run()
    {
        // Insertar rol básico para los tests
        $this->db->table('rol')->insert([
            'id' => 1,
            'nombre' => 'Operario',
            'descripcion' => 'Rol de operario para pruebas'
        ]);
    }
}

