<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFichaEditadaToReclamo extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('reclamo')) {
            return;
        }

        if (!$this->db->fieldExists('ficha_editada', 'reclamo')) {
            $this->forge->addColumn('reclamo', [
                'ficha_editada' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'unsigned' => true,
                    'default' => 0,
                    'null' => false,
                    'after' => 'fecha_cierre',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('reclamo') && $this->db->fieldExists('ficha_editada', 'reclamo')) {
            $this->forge->dropColumn('reclamo', 'ficha_editada');
        }
    }
}
