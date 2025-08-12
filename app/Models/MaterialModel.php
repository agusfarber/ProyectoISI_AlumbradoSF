<?php

namespace App\Models;
use CodeIgniter\Model;

class MaterialModel extends Model
{
  protected $table = 'material';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = ['nombre','cantidad'];

}