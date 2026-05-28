<?php

namespace App\Models;

use CodeIgniter\Model;

class RutaEjecucionModel extends Model
{
    protected $table            = 'ruta_ejecucion';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'ruta_id',
        'cuadrilla_id',
        'inicio_at',
        'fin_at',
    ];
}
