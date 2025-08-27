<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['nombre' => 'Usuario'],
            ['nombre' => 'Administrador'],
            ['nombre' => 'Supervisor'],
            ['nombre' => 'Técnico']
        ];

        $this->db->table('rol')->insertBatch($data);
    }
}
