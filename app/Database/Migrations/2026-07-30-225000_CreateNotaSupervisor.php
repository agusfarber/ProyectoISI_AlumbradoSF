<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotaSupervisor extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('nota_supervisor')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'titulo' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
                'default' => null,
            ],
            'contenido' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'hecha' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'unsigned' => true,
                'default' => 0,
            ],
            'fijada' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'unsigned' => true,
                'default' => 0,
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
        $this->forge->addKey('usuario_id');
        $this->forge->addKey(['usuario_id', 'hecha']);
        $this->forge->createTable('nota_supervisor', true);
    }

    public function down()
    {
        if ($this->db->tableExists('nota_supervisor')) {
            $this->forge->dropTable('nota_supervisor', true);
        }
    }
}
