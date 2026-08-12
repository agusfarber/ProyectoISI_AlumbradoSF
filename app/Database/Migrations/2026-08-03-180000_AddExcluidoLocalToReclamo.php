<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExcluidoLocalToReclamo extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('reclamo')) {
            return;
        }

        if (!$this->db->fieldExists('excluido_local', 'reclamo')) {
            $this->forge->addColumn('reclamo', [
                'excluido_local' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'unsigned' => true,
                    'default' => 0,
                    'null' => false,
                    'after' => 'ficha_editada',
                ],
            ]);
        }

        if (!$this->db->fieldExists('excluido_at', 'reclamo')) {
            $this->forge->addColumn('reclamo', [
                'excluido_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'excluido_local',
                ],
            ]);
        }

        if (!$this->db->fieldExists('excluido_observacion', 'reclamo')) {
            $this->forge->addColumn('reclamo', [
                'excluido_observacion' => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                    'after' => 'excluido_at',
                ],
            ]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('reclamo')) {
            return;
        }

        foreach (['excluido_observacion', 'excluido_at', 'excluido_local'] as $columna) {
            if ($this->db->fieldExists($columna, 'reclamo')) {
                $this->forge->dropColumn('reclamo', $columna);
            }
        }
    }
}
