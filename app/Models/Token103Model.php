<?php

namespace App\Models;
use CodeIgniter\Model;

class Token103Model extends Model
{
  protected $table = 'token103';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = [
    'client_id',
    'client_secret',
    'access_token',
    'token_type',
    'expires_in',
    'fecha_generacion'
  ];

  protected $useTimestamps = true;
  protected $createdField = 'created_at';
  protected $updatedField = 'updated_at';
}