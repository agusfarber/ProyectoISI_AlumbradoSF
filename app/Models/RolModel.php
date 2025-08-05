<?php

namespace App\Models;
use CodeIgniter\Model;

class RolModel extends Model
{
  protected $table = 'rol';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = ['nombre'];

}