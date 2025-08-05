<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UsuarioModel;

class Usuarios extends ResourceController
{
    protected $modelName = 'App\Models\UsuarioModel';
    protected $format = 'json';

    public function index()
    {
        return $this->respond($this->model->findAll());
    }

    public function create()
    {
        $data = $this->request->getJSON(true); // obtiene datos JSON enviados

        // Validar datos (puedes mejorar validación según necesidades)
        if (empty($data['nombre']) || empty($data['email']) || empty($data['contrasena']) || empty($data['idRol'])) {
            return $this->failValidationErrors('Faltan datos obligatorios.');
        }

        // Insertar usuario
        $usuarioId = $this->model->insert($data);

        if ($usuarioId === false) {
            return $this->failServerError('Error al guardar usuario.');
        }

        $usuarioCreado = $this->model->find($usuarioId);

        return $this->respondCreated($usuarioCreado);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);

        if (!$id || empty($data)) {
            return $this->failValidationErrors('Faltan datos obligatorios.');
        }

        $actualizado = $this->model->update($id, $data);

        if ($actualizado === false) {
            return $this->failServerError('Error al actualizar el usuario.');
        }

        $usuarioActualizado = $this->model->find($id);
        return $this->respond($usuarioActualizado);
    }

    public function delete($id = null)
    {
        if (!$id || !$this->model->find($id)) {
            return $this->failNotFound('Usuario no encontrado.');
        }

        $this->model->delete($id);
        return $this->respondDeleted(['mensaje' => 'Usuario eliminado con éxito.']);
    }

}
