<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Observaciones de obra por reclamo, vinculadas a una ejecución concreta de hoja de ruta.
 */
class CreateRutaEjecucionReclamoObservacion extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'ruta_ejecucion_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'ruta_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'reclamo_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'texto' => [
                'type' => 'TEXT',
            ],
            'usuario_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['ruta_ejecucion_id', 'reclamo_id']);
        $this->forge->addKey('ruta_id');
        $this->forge->addForeignKey('ruta_ejecucion_id', 'ruta_ejecucion', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('ruta_id', 'ruta', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('reclamo_id', 'reclamo', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ruta_ejecucion_reclamo_observacion');
    }

    public function down()
    {
        $this->forge->dropTable('ruta_ejecucion_reclamo_observacion', true);
    }
}
