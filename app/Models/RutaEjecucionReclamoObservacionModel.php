<?php

namespace App\Models;

use CodeIgniter\Model;

class RutaEjecucionReclamoObservacionModel extends Model
{
    protected $table            = 'ruta_ejecucion_reclamo_observacion';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'ruta_ejecucion_id',
        'ruta_id',
        'reclamo_id',
        'texto',
        'usuario_id',
        'created_at',
    ];
}
