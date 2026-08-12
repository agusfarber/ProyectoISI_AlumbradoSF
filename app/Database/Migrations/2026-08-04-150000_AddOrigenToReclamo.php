<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrigenToReclamo extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('reclamo')) {
            return;
        }

        if (!$this->db->fieldExists('origen', 'reclamo')) {
            $this->forge->addColumn('reclamo', [
                'origen' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => '103',
                    'null' => false,
                    'after' => 'excluido_observacion',
                ],
            ]);
        }

        // Reclamos previos se consideran provenientes del 103
        $this->db->table('reclamo')
            ->where('origen', '')
            ->orWhere('origen', null)
            ->update(['origen' => '103']);
    }

    public function down()
    {
        if ($this->db->tableExists('reclamo') && $this->db->fieldExists('origen', 'reclamo')) {
            $this->forge->dropColumn('reclamo', 'origen');
        }
    }
}
