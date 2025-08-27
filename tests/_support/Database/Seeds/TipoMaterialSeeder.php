<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TipoMaterialSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['nombre' => 'Cable Eléctrico'],
            ['nombre' => 'Lámpara'],
            ['nombre' => 'Interruptor'],
            ['nombre' => 'Conexión'],
            ['nombre' => 'Transformador'],
            ['nombre' => 'Fusible']
        ];

        $this->db->table('tipo_material')->insertBatch($data);
    }
}
