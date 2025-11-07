<?php

namespace App\Models;
use CodeIgniter\Model;

class Token103Model extends Model
{
  protected $table = 'token103';
  protected $primaryKey = 'id';

  protected $useAutoIncrement = true;

  protected $allowedFields = [
    'username',
    'password'
  ];

  protected $useTimestamps = true;
  protected $createdField = 'created_at';
  protected $updatedField = 'updated_at';
}