<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRutaEjecucionIdToMaterialReclamo extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('material_reclamo')) {
            return;
        }

        if (! $this->db->fieldExists('ruta_ejecucion_id', 'material_reclamo')) {
            $this->forge->addColumn('material_reclamo', [
                'ruta_ejecucion_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'reclamo_id',
                ],
            ]);
        }

        if ($this->db->tableExists('ruta_ejecucion')) {
            try {
                $this->db->query(
                    'ALTER TABLE material_reclamo
                     ADD CONSTRAINT material_reclamo_ruta_ejecucion_id_foreign
                     FOREIGN KEY (ruta_ejecucion_id) REFERENCES ruta_ejecucion(id)
                     ON DELETE SET NULL ON UPDATE CASCADE'
                );
            } catch (\Throwable $e) {
                // Puede existir si se re-ejecuta parcialmente.
                log_message('debug', 'FK material_reclamo.ruta_ejecucion_id: ' . $e->getMessage());
            }
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('material_reclamo')) {
            return;
        }

        try {
            $this->db->query('ALTER TABLE material_reclamo DROP FOREIGN KEY material_reclamo_ruta_ejecucion_id_foreign');
        } catch (\Throwable $e) {
            // ignore
        }

        if ($this->db->fieldExists('ruta_ejecucion_id', 'material_reclamo')) {
            $this->forge->dropColumn('material_reclamo', 'ruta_ejecucion_id');
        }
    }
}
