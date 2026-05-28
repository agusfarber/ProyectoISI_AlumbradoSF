<?php

namespace App\Models;

use CodeIgniter\Model;

class RutaEjecucionEventoModel extends Model
{
    protected $table            = 'ruta_ejecucion_evento';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'ruta_ejecucion_id',
        'tipo',
        'reclamo_id',
        'usuario_id',
        'ocurrido_at',
        'metadata',
    ];
}
