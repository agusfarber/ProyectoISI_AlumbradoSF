<?php

namespace App\Models;

use CodeIgniter\Model;

class NotaSupervisorModel extends Model
{
    protected $table = 'nota_supervisor';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'usuario_id',
        'titulo',
        'contenido',
        'hecha',
        'fijada',
    ];

    protected $validationRules = [
        'usuario_id' => 'required|is_natural_no_zero',
        'contenido' => 'required|min_length[1]',
        'hecha' => 'permit_empty|in_list[0,1]',
        'fijada' => 'permit_empty|in_list[0,1]',
    ];
}
