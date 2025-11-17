<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTiempoPromedioMotivo extends Migration
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
            'motivo' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'tiempo_promedio_minutos' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
            ],
            'cantidad_registros' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'tiempo_total_minutos' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'tiempo_default_minutos' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 15,
                'comment' => 'Valor por defecto si no hay registros',
            ],
            'fecha_actualizacion' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('motivo');
        $this->forge->createTable('tiempo_promedio_motivo');
    }

    public function down()
    {
        $this->forge->dropTable('tiempo_promedio_motivo');
    }
}

