<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIconoToTipoMaterial extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tipo_material') && !$this->db->fieldExists('icono', 'tipo_material')) {
            $this->forge->addColumn('tipo_material', [
                'icono' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => true,
                    'default' => null,
                    'after' => 'nombre',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tipo_material') && $this->db->fieldExists('icono', 'tipo_material')) {
            $this->forge->dropColumn('tipo_material', 'icono');
        }
    }
}
