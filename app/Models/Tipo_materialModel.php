<?php

namespace App\Models;
use CodeIgniter\Model;

class Tipo_materialModel extends Model
{
  protected $table = 'tipo_material';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = ['nombre'];

}