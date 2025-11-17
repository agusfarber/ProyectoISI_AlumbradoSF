<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTiempoReparacion extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'reclamo_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'motivo_reclamo' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'tiempo_minutos' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'fecha_registro' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tiempo_reparacion');
    }

    public function down()
    {
        $this->forge->dropTable('tiempo_reparacion');
    }
}

