<?php namespace App\Models;

use CodeIgniter\Model;

class CuadrillaModel extends Model
{
    protected $table = 'cuadrilla';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre', 'descripcion'];
    protected $useTimestamps = false;
    
    protected $validationRules = [];
    
    protected $validationMessages = [];
}