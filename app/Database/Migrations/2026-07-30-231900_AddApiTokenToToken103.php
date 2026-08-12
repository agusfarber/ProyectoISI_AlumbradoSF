<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApiTokenToToken103 extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('token103')) {
            return;
        }

        if (!$this->db->fieldExists('api_token', 'token103')) {
            $this->forge->addColumn('token103', [
                'api_token' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'default' => null,
                    'after' => 'id',
                ],
            ]);
        }

        // Usuario/password ya no se usan para auth del 103; se dejan nullable por compatibilidad.
        if ($this->db->fieldExists('username', 'token103')) {
            $this->db->query('ALTER TABLE `token103` MODIFY `username` VARCHAR(255) NULL DEFAULT NULL');
        }
        if ($this->db->fieldExists('password', 'token103')) {
            $this->db->query('ALTER TABLE `token103` MODIFY `password` VARCHAR(255) NULL DEFAULT NULL');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('token103') && $this->db->fieldExists('api_token', 'token103')) {
            $this->forge->dropColumn('token103', 'api_token');
        }
    }
}
