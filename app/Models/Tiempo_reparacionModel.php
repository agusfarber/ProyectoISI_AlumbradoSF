<?php

namespace App\Models;

use CodeIgniter\Model;

class Tiempo_reparacionModel extends Model
{
    protected $table = 'tiempo_reparacion';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'reclamo_id',
        'motivo_reclamo',
        'tiempo_minutos',
        'usuario_id',
        'fecha_registro'
    ];

    protected $useTimestamps = false;
}

