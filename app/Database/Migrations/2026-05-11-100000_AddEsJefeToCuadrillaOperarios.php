<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEsJefeToCuadrillaOperarios extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('cuadrilla_operarios') && !$this->db->fieldExists('es_jefe', 'cuadrilla_operarios')) {
            $this->forge->addColumn('cuadrilla_operarios', [
                'es_jefe' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => false,
                    'after' => 'usuario_id',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('cuadrilla_operarios') && $this->db->fieldExists('es_jefe', 'cuadrilla_operarios')) {
            $this->forge->dropColumn('cuadrilla_operarios', 'es_jefe');
        }
    }
}
