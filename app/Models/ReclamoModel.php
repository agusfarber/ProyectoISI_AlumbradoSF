<?php

namespace App\Models;
use CodeIgniter\Model;

class ReclamoModel extends Model
{
  protected $table = 'reclamo';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = [
    'municipalidad_id',
    'municipalidad_tipo',
    'municipalidad_motivo',
    'municipalidad_fechaInicio',
    'municipalidad_fechaModificacion',
    'municipalidad_recepcion',
    'municipalidad_estado',
    'municipalidad_telefono',
    'municipalidad_domicilio',
    'municipalidad_numeroDomicilio',
    'municipalidad_entreCalleUno',
    'municipalidad_entreCalleDos',
    'municipalidad_ciudadano',
    'municipalidad_descripcion',
    'prioridad',
    'cerrado',
    'fecha_cierre',
    'ficha_editada',
    'excluido_local',
    'excluido_at',
    'excluido_observacion',
    'origen',
  ];

  public const ORIGEN_103 = '103';
  public const ORIGEN_LOCAL = 'local';

  /**
   * Reclamos visibles en la app (no excluidos localmente por el supervisor).
   */
  public function findAllActivos(): array
  {
    return $this->where('excluido_local', 0)->findAll();
  }

  /**
   * Aplica el filtro de no excluidos al builder/query actual.
   */
  public function soloActivos()
  {
    return $this->where('excluido_local', 0);
  }

  /**
   * Último ID numérico del 103 (ignora reclamos creados localmente).
   */
  public function ultimoMunicipalidadId103(): int
  {
    $fila = $this->builder()
      ->select('municipalidad_id')
      ->where('origen', self::ORIGEN_103)
      ->orderBy('CAST(municipalidad_id AS UNSIGNED)', 'DESC')
      ->limit(1)
      ->get()
      ->getRowArray();

    if (!$fila || empty($fila['municipalidad_id'])) {
      return 0;
    }

    return (int) $fila['municipalidad_id'];
  }
}
