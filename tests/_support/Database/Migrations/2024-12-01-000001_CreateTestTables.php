<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTestTables extends Migration
{
    public function up()
    {
        // Crear tabla rol para los tests
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
            'descripcion' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('rol');

        // Crear tabla usuario para los tests
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
                'constraint' => 20,
            ],
            'rol_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('rol_id', 'rol', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('usuario');

        // Crear tabla cuadrilla para los tests
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
            'descripcion' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('cuadrilla');

        // Crear tabla cuadrilla_operarios para los tests
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'cuadrilla_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('cuadrilla_id', 'cuadrilla', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'usuario', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('cuadrilla_operarios');

        // Crear tabla reclamo para los tests
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
                'constraint' => 200,
            ],
            'municipalidad_fechaInicio' => [
                'type' => 'DATETIME',
            ],
            'municipalidad_fechaModificacion' => [
                'type' => 'DATETIME',
            ],
            'municipalidad_recepcion' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'municipalidad_estado' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'municipalidad_telefono' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'municipalidad_domicilio' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
                'null' => true,
            ],
            'municipalidad_numeroDomicilio' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
            ],
            'municipalidad_entreCalleUno' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'municipalidad_entreCalleDos' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'municipalidad_ciudadano' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'municipalidad_descripcion' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'prioridad' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('reclamo');
    }

    public function down()
    {
        $this->forge->dropTable('reclamo');
        $this->forge->dropTable('cuadrilla_operarios');
        $this->forge->dropTable('cuadrilla');
        $this->forge->dropTable('usuario');
        $this->forge->dropTable('rol');
    }
}
