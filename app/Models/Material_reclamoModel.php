<?php

namespace App\Models;
use CodeIgniter\Model;

class Material_reclamoModel extends Model
{
  protected $table = 'material_reclamo';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = ['reclamo_id','material_id','observacion','cantidad','fecha','usuario_id'];

}