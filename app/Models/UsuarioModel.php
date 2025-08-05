<?php

namespace App\Models;
use CodeIgniter\Model;

class UsuarioModel extends Model
{
  protected $table = 'usuario';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = ['nombre','email','legajo','contrasena','idRol'];

}