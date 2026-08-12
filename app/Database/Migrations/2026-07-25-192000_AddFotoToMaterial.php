<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFotoToMaterial extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('material') && !$this->db->fieldExists('foto', 'material')) {
            $this->forge->addColumn('material', [
                'foto' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'default' => null,
                    'after' => 'cantidad',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('material') && $this->db->fieldExists('foto', 'material')) {
            $this->forge->dropColumn('material', 'foto');
        }
    }
}
