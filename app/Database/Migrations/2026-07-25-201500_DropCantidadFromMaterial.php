<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropCantidadFromMaterial extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('material') && $this->db->fieldExists('cantidad', 'material')) {
            $this->forge->dropColumn('material', 'cantidad');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('material') && !$this->db->fieldExists('cantidad', 'material')) {
            $this->forge->addColumn('material', [
                'cantidad' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => false,
                    'default' => 0,
                    'after' => 'idTipo',
                ],
            ]);
        }
    }
}
