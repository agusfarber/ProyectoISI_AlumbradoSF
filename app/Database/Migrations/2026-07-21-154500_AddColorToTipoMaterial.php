<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColorToTipoMaterial extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tipo_material') && !$this->db->fieldExists('color', 'tipo_material')) {
            $this->forge->addColumn('tipo_material', [
                'color' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'default' => null,
                    'after' => 'icono',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tipo_material') && $this->db->fieldExists('color', 'tipo_material')) {
            $this->forge->dropColumn('tipo_material', 'color');
        }
    }
}
