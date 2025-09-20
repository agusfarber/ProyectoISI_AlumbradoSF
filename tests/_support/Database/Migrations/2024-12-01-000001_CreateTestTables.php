<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTestTables extends Migration
{
    public function up()
    {
        // Crear tabla rol
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('rol');

        // Crear tabla usuario
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'legajo' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'contrasena' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'idRol' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('idRol', 'rol', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('usuario');

        // Crear tabla tipo_material
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tipo_material');

        // Crear tabla material
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'idTipo' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'cantidad' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('idTipo', 'tipo_material', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('material');

        // Crear tabla reclamo
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'municipalidad_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'municipalidad_tipo' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'municipalidad_motivo' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'municipalidad_fechaInicio' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'municipalidad_fechaModificacion' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'municipalidad_recepcion' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'municipalidad_estado' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'municipalidad_telefono' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'municipalidad_domicilio' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'municipalidad_numeroDomicilio' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'municipalidad_entreCalleUno' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'municipalidad_entreCalleDos' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'municipalidad_ciudadano' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'municipalidad_descripcion' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'prioridad' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('reclamo');
    }

    public function down()
    {
        $this->forge->dropTable('reclamo');
        $this->forge->dropTable('material');
        $this->forge->dropTable('tipo_material');
        $this->forge->dropTable('usuario');
        $this->forge->dropTable('rol');
    }
}
