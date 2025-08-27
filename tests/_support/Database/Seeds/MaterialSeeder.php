<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MaterialSeeder extends Seeder
{
    public function run()
    {
        // Primero crear tipos de material
        $tiposData = [
            ['nombre' => 'Cable Eléctrico'],
            ['nombre' => 'Lámpara'],
            ['nombre' => 'Interruptor'],
            ['nombre' => 'Conexión']
        ];

        $this->db->table('tipo_material')->insertBatch($tiposData);

        // Obtener los IDs de los tipos creados
        $tipos = $this->db->table('tipo_material')->get()->getResultArray();
        $tiposMap = array_column($tipos, 'id', 'nombre');

        // Crear materiales
        $materialesData = [
            [
                'nombre' => 'Cable 2x1.5',
                'idTipo' => $tiposMap['Cable Eléctrico'],
                'cantidad' => 100
            ],
            [
                'nombre' => 'Lámpara LED 15W',
                'idTipo' => $tiposMap['Lámpara'],
                'cantidad' => 50
            ],
            [
                'nombre' => 'Interruptor Simple',
                'idTipo' => $tiposMap['Interruptor'],
                'cantidad' => 25
            ]
        ];

        $this->db->table('material')->insertBatch($materialesData);
    }
}
