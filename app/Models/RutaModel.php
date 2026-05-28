<?php

/* 
Este es el modelo de rutas, acá se guardará:

* id de la hoja de ruta
* cantidadReclamos, es la cantidad de reclamos que tiene esa hoja de ruta
* asignada == 1, significa que la ruta está asignada a una cuadrilla y está siendo utilizada actualmente; asignada == 0, significa que la ruta no está asignada a ninguna cuadrilla aún
* cuadrilla_id, sería el id de la cuadrilla a la cual se le asignó la hoja de ruta en cuestión
* tiempoEstimado, sería el tiempo estimado que se tardaría en seguir esa ruta, el campo es tipo de dato TIME
* fecha, es un campo datetime en donde se guarda la fecha y hora de creación de la ruta (en horario argentino)
*/

namespace App\Models;
use CodeIgniter\Model;

class RutaModel extends Model
{
  protected $table = 'ruta';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = ['nombre','cantidadReclamos','asignada','cuadrilla_id','tiempoEstimado','fecha','color','estado_ejecucion','inicio_ejecucion_at'];

}