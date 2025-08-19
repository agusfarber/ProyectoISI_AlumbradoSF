<?php

namespace App\Models;
use CodeIgniter\Model;

class ReclamoModel extends Model
{
  protected $table = 'reclamo';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = ['municipalidad_id','municipalidad_tipo','municipalidad_motivo','municipalidad_fechaInicio','municipalidad_fechaModificacion','municipalidad_recepcion','municipalidad_estado','municipalidad_telefono','municipalidad_domicilio','municipalidad_numeroDomicilio','municipalidad_entreCalleUno','municipalidad_entreCalleDos','municipalidad_ciudadano','municipalidad_descripcion','prioridad'];

}