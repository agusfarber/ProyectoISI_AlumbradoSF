<?php

namespace App\Models;
use CodeIgniter\Model;

class Historial_reclamoModel extends Model
{
  protected $table = 'historial_reclamo';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = ['nro_reclamo','estado_anterior','estado_actual','usuario_id','fecha_cambio'];

}