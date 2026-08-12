<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterReclamoForLocalOptionalFields extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('reclamo')) {
            return;
        }

        $this->forge->modifyColumn('reclamo', [
            'municipalidad_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'municipalidad_domicilio' => [
                'type' => 'VARCHAR',
                'constraint' => 300,
                'null' => true,
            ],
            'municipalidad_numeroDomicilio' => [
                'type' => 'VARCHAR',
                'constraint' => 25,
                'null' => true,
            ],
            'municipalidad_entreCalleUno' => [
                'type' => 'VARCHAR',
                'constraint' => 300,
                'null' => true,
            ],
            'municipalidad_entreCalleDos' => [
                'type' => 'VARCHAR',
                'constraint' => 300,
                'null' => true,
            ],
        ]);
    }

    public function down()
    {
        // No se revierte a INT: podría romper IDs locales L*
    }
}
