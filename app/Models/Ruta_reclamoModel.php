<?php

/* 
Este es el modelo de un reclamo que pertenece a una ruta, acá se guardará:

* id del registro
* ruta_id: id de la ruta de la cual se trata
* reclamo_id: id de un reclamo que pertenece a la ruta
* posición: posición del reclamo en la hoja de ruta (si el reclamo es el primero del recorrido (1), si es el segundo (2), si es el tercero (3), y así sucesivamente)
*/

namespace App\Models;
use CodeIgniter\Model;

class Ruta_reclamoModel extends Model
{
  protected $table = 'ruta_reclamo';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = ['ruta_id','reclamo_id','posicion'];

}