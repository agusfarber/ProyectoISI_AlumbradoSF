<?php

namespace App\Models;

use CodeIgniter\Model;

class Tiempo_promedio_motivoModel extends Model
{
    protected $table = 'tiempo_promedio_motivo';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'motivo',
        'tiempo_promedio_minutos',
        'cantidad_registros',
        'tiempo_total_minutos',
        'tiempo_default_minutos',
        'fecha_actualizacion'
    ];

    protected $useTimestamps = false;
}

