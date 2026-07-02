<?php

namespace App\Models;
use CodeIgniter\Model;

class UsuarioModel extends Model
{
  protected $table = 'usuario';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = ['nombre','email','legajo','contrasena','idRol','foto_perfil'];

  /**
   * Valida el login por legajo con validación estricta
   */
  public function validateLoginByLegajo($legajo, $contrasena)
  {
    // Buscar usuario por legajo exacto
    $user = $this->where('legajo', $legajo)->first();
    
    // Validación estricta: legajo debe coincidir exactamente
    if ($user && $user['legajo'] === $legajo && $user['contrasena'] === $contrasena) {
      return $user;
    }
    
    return false;
  }

  /**
   * Valida el login por email con validación estricta
   */
  public function validateLoginByEmail($email, $contrasena)
  {
    // Buscar usuario por email exacto
    $user = $this->where('email', $email)->first();
    
    // Validación estricta: email debe coincidir exactamente
    if ($user && $user['email'] === $email && $user['contrasena'] === $contrasena) {
      return $user;
    }
    
    return false;
  }
}