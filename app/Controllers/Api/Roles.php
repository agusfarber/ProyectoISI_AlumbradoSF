<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\RolModel;

class Roles extends ResourceController
{
    protected $modelName = 'App\Models\RolModel';
    protected $format = 'json';

    public function index()
    {
        $roles = $this->model->findAll();
        return $this->respond($roles);
    }
}
