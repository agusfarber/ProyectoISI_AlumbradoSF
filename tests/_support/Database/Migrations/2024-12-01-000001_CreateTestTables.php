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

        // Crear tabla ruta para los tests
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'color' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'cantidadReclamos' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'asignada' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'cuadrilla_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'tiempoEstimado' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'fecha' => [
                'type' => 'DATETIME',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('cuadrilla_id', 'cuadrilla', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('ruta');

        // Crear tabla ruta_reclamo para los tests
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ruta_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'reclamo_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'posicion' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('ruta_id', 'ruta', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('reclamo_id', 'reclamo', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('ruta_reclamo');

        // Crear tabla tipo_material para los tests
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

        // Crear tabla material para los tests
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
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

        // Crear tabla token103 para credenciales de API externa
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
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
        $this->forge->createTable('token103');
    }

    public function down()
    {
        $this->forge->dropTable('token103', true);
        $this->forge->dropTable('material', true);
        $this->forge->dropTable('tipo_material', true);
        $this->forge->dropTable('ruta_reclamo', true);
        $this->forge->dropTable('ruta', true);
        $this->forge->dropTable('direccion', true);
        $this->forge->dropTable('reclamo', true);
        $this->forge->dropTable('cuadrilla_operarios', true);
        $this->forge->dropTable('cuadrilla', true);
        $this->forge->dropTable('usuario', true);
        $this->forge->dropTable('rol', true);
    }
}
