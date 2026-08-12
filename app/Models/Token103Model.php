<?php

namespace App\Models;
use CodeIgniter\Model;

class Token103Model extends Model
{
  protected $table = 'token103';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = [
    'api_token',
  ];

  protected $useTimestamps = true;
  protected $createdField = 'created_at';
  protected $updatedField = 'updated_at';

  /**
   * Última configuración de token del 103.
   */
  public function obtenerConfiguracionActual(): ?array
  {
    $config = $this->orderBy('id', 'DESC')->first();
    return $config ?: null;
  }

  /**
   * Token crudo para Authorization: Token {valor}
   */
  public function obtenerApiToken(): ?string
  {
    $config = $this->obtenerConfiguracionActual();
    $token = trim((string) ($config['api_token'] ?? ''));
    return $token !== '' ? $token : null;
  }

  /**
   * Header completo Authorization para llamadas al 103.
   */
  public function obtenerHeaderAuthorization(): ?string
  {
    $token = $this->obtenerApiToken();
    return $token !== null ? 'Token ' . $token : null;
  }
}
