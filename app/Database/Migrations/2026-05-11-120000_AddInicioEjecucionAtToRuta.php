<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInicioEjecucionAtToRuta extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('inicio_ejecucion_at', 'ruta')) {
            return;
        }

        $this->forge->addColumn('ruta', [
            'inicio_ejecucion_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Momento en que la hoja pasó a en ejecución (cronómetro persistente)',
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->fieldExists('inicio_ejecucion_at', 'ruta')) {
            $this->forge->dropColumn('ruta', 'inicio_ejecucion_at');
        }
    }
}
