<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Extiende la bitácora de obra (observaciones) para soportar fotos.
 */
class AddFotoBitacoraObraReclamo extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('ruta_ejecucion_reclamo_observacion')) {
            return;
        }

        if (! $this->db->fieldExists('tipo', 'ruta_ejecucion_reclamo_observacion')) {
            $this->forge->addColumn('ruta_ejecucion_reclamo_observacion', [
                'tipo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'default'    => 'texto',
                    'after'      => 'reclamo_id',
                ],
            ]);
        }

        if (! $this->db->fieldExists('archivo', 'ruta_ejecucion_reclamo_observacion')) {
            $this->forge->addColumn('ruta_ejecucion_reclamo_observacion', [
                'archivo' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'texto',
                ],
            ]);
        }

        // Fotos pueden no tener texto; las notas de solo texto siguen requiriendo contenido en la API.
        $this->db->query('ALTER TABLE ruta_ejecucion_reclamo_observacion MODIFY texto TEXT NULL');
    }

    public function down()
    {
        if (! $this->db->tableExists('ruta_ejecucion_reclamo_observacion')) {
            return;
        }

        if ($this->db->fieldExists('archivo', 'ruta_ejecucion_reclamo_observacion')) {
            $this->forge->dropColumn('ruta_ejecucion_reclamo_observacion', 'archivo');
        }

        if ($this->db->fieldExists('tipo', 'ruta_ejecucion_reclamo_observacion')) {
            $this->forge->dropColumn('ruta_ejecucion_reclamo_observacion', 'tipo');
        }
    }
}
