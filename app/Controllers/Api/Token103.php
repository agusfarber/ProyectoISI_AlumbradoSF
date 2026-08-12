<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Token103Model;

class Token103 extends ResourceController
{
    protected $modelName = 'App\Models\Token103Model';
    protected $format = 'json';

    public function __construct()
    {
        date_default_timezone_set('America/Argentina/Buenos_Aires');
    }

    public function index()
    {
        try {
            $tokens = $this->model->orderBy('id', 'DESC')->findAll();
            return $this->respond($tokens);
        } catch (\Exception $e) {
            return $this->failServerError('Error al obtener los tokens: ' . $e->getMessage());
        }
    }

    public function show($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('ID de token requerido');
            }

            $token = $this->model->find($id);
            if (!$token) {
                return $this->failNotFound('Token no encontrado');
            }

            return $this->respond($token);
        } catch (\Exception $e) {
            return $this->failServerError('Error al obtener el token: ' . $e->getMessage());
        }
    }

    /**
     * Guarda el token de API del sistema 103.
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);
            $apiToken = trim((string) ($data['api_token'] ?? ''));

            if ($apiToken === '') {
                return $this->failValidationErrors('El token de la API es obligatorio');
            }

            $tokenId = $this->model->insert(['api_token' => $apiToken]);

            if ($tokenId === false) {
                return $this->failServerError('Error al guardar el token');
            }

            return $this->respondCreated($this->model->find($tokenId));
        } catch (\Exception $e) {
            return $this->failServerError('Error al crear el token: ' . $e->getMessage());
        }
    }

    public function update($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('ID de token requerido');
            }

            $data = $this->request->getJSON(true);
            if (empty($data)) {
                return $this->failValidationErrors('No se proporcionaron datos para actualizar');
            }

            $tokenExistente = $this->model->find($id);
            if (!$tokenExistente) {
                return $this->failNotFound('Token no encontrado');
            }

            $payload = [];
            if (array_key_exists('api_token', $data)) {
                $apiToken = trim((string) $data['api_token']);
                if ($apiToken === '') {
                    return $this->failValidationErrors('El token de la API es obligatorio');
                }
                $payload['api_token'] = $apiToken;
            }

            if (empty($payload)) {
                return $this->failValidationErrors('No hay cambios para guardar');
            }

            if ($this->model->update($id, $payload) === false) {
                return $this->failServerError('Error al actualizar el token');
            }

            return $this->respond($this->model->find($id));
        } catch (\Exception $e) {
            return $this->failServerError('Error al actualizar el token: ' . $e->getMessage());
        }
    }

    public function delete($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('ID de token requerido');
            }

            $token = $this->model->find($id);
            if (!$token) {
                return $this->failNotFound('Token no encontrado');
            }

            $this->model->delete($id);
            return $this->respondDeleted(['mensaje' => 'Token eliminado con éxito']);
        } catch (\Exception $e) {
            return $this->failServerError('Error al eliminar el token: ' . $e->getMessage());
        }
    }
}
