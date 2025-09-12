<?php

namespace App\Models;
use CodeIgniter\Model;

class DireccionModel extends Model
{
  protected $table = 'direccion';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = ['domicilio','numero_domicilio','latitud','longitud'];

}