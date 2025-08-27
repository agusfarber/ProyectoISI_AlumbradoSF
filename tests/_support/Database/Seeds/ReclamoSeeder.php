<?php

namespace Tests\Support\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ReclamoSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'municipalidad_id' => 'REC001',
                'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
                'municipalidad_motivo' => 'Lámpara apagada',
                'municipalidad_fechaInicio' => '2024-12-01 10:00:00',
                'municipalidad_fechaModificacion' => '2024-12-01 10:00:00',
                'municipalidad_recepcion' => 'Web',
                'municipalidad_estado' => 'Pendiente',
                'municipalidad_telefono' => '123456789',
                'municipalidad_domicilio' => 'Av. San Martín',
                'municipalidad_numeroDomicilio' => '123',
                'municipalidad_entreCalleUno' => 'Belgrano',
                'municipalidad_entreCalleDos' => 'Mitre',
                'municipalidad_ciudadano' => 'Juan Pérez',
                'municipalidad_descripcion' => 'Lámpara de la esquina apagada',
                'prioridad' => 'Media'
            ],
            [
                'municipalidad_id' => 'REC002',
                'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
                'municipalidad_motivo' => 'Poste caído',
                'municipalidad_fechaInicio' => '2024-12-01 11:00:00',
                'municipalidad_fechaModificacion' => '2024-12-01 11:00:00',
                'municipalidad_recepcion' => 'Teléfono',
                'municipalidad_estado' => 'En Proceso',
                'municipalidad_telefono' => '987654321',
                'municipalidad_domicilio' => 'Calle Rivadavia',
                'municipalidad_numeroDomicilio' => '456',
                'municipalidad_entreCalleUno' => 'Sarmiento',
                'municipalidad_entreCalleDos' => 'Moreno',
                'municipalidad_ciudadano' => 'María García',
                'municipalidad_descripcion' => 'Poste de luz caído en la vereda',
                'prioridad' => 'Alta'
            ]
        ];

        $this->db->table('reclamo')->insertBatch($data);
    }
}
