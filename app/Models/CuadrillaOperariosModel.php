<?php namespace App\Models;

use CodeIgniter\Model;

class CuadrillaOperariosModel extends Model
{
    protected $table = 'cuadrilla_operarios';
    protected $primaryKey = 'id';
    protected $allowedFields = ['cuadrilla_id','usuario_id','es_jefe'];
    protected $useTimestamps = false;
}