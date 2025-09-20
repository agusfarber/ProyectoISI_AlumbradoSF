<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveIdcuadrillaFromUsuario extends Migration
{
    public function up()
    {
        // Eliminar la columna idCuadrilla de la tabla usuario
        $this->forge->dropColumn('usuario', 'idCuadrilla');
    }

    public function down()
    {
        // Agregar la columna idCuadrilla de vuelta si es necesario hacer rollback
        $fields = [
            'idCuadrilla' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'comment' => 'ID de la cuadrilla asignada al usuario'
            ]
        ];
        
        $this->forge->addColumn('usuario', $fields);
    }
}

