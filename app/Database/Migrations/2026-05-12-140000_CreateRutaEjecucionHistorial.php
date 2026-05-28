<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Historial escalable de ejecuciones de hoja de ruta (eventos con tipo + metadata JSON).
 */
class CreateRutaEjecucionHistorial extends Migration
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
            'ruta_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'cuadrilla_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'inicio_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'fin_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('ruta_id');
        $this->forge->addKey(['ruta_id', 'fin_at']);
        $this->forge->createTable('ruta_ejecucion');

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
            'tipo' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'reclamo_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'usuario_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'ocurrido_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'metadata' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['ruta_ejecucion_id', 'ocurrido_at']);
        $this->forge->addKey('tipo');
        $this->forge->addForeignKey('ruta_ejecucion_id', 'ruta_ejecucion', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ruta_ejecucion_evento');
    }

    public function down()
    {
        $this->forge->dropTable('ruta_ejecucion_evento', true);
        $this->forge->dropTable('ruta_ejecucion', true);
    }
}
