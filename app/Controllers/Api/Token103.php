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
        // Configurar zona horaria de Argentina
        date_default_timezone_set('America/Argentina/Buenos_Aires');
    }

    /**
     * Obtiene todos los tokens
     */
    public function index()
    {
        try {
            $tokens = $this->model->orderBy('id', 'DESC')->findAll();
            return $this->respond($tokens);
        } catch (\Exception $e) {
            return $this->failServerError('Error al obtener los tokens: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene un token específico por ID
     */
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
     * Crea un nuevo token o credenciales
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validar datos obligatorios
            if (empty($data['client_id']) || empty($data['client_secret'])) {
                return $this->failValidationErrors('Client ID y Client Secret son obligatorios');
            }

            // Si se proporciona un access_token, validar que también venga token_type y expires_in
            if (!empty($data['access_token'])) {
                if (empty($data['token_type'])) {
                    $data['token_type'] = 'Bearer';
                }
                if (empty($data['expires_in'])) {
                    $data['expires_in'] = 3600;
                }
                if (empty($data['fecha_generacion'])) {
                    $data['fecha_generacion'] = date('Y-m-d H:i:s');
                }
            }

            // Insertar token
            $tokenId = $this->model->insert($data);

            if ($tokenId === false) {
                return $this->failServerError('Error al guardar el token');
            }

            $tokenCreado = $this->model->find($tokenId);
            return $this->respondCreated($tokenCreado);

        } catch (\Exception $e) {
            return $this->failServerError('Error al crear el token: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza un token existente
     */
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

            // Verificar que el token existe
            $tokenExistente = $this->model->find($id);
            if (!$tokenExistente) {
                return $this->failNotFound('Token no encontrado');
            }

            // Si se está actualizando el access_token, asegurar que vengan los campos relacionados
            if (!empty($data['access_token'])) {
                if (empty($data['token_type'])) {
                    $data['token_type'] = 'Bearer';
                }
                if (empty($data['expires_in'])) {
                    $data['expires_in'] = 3600;
                }
                if (empty($data['fecha_generacion'])) {
                    $data['fecha_generacion'] = date('Y-m-d H:i:s');
                }
            }

            // Actualizar token
            $actualizado = $this->model->update($id, $data);

            if ($actualizado === false) {
                return $this->failServerError('Error al actualizar el token');
            }

            $tokenActualizado = $this->model->find($id);
            return $this->respond($tokenActualizado);

        } catch (\Exception $e) {
            return $this->failServerError('Error al actualizar el token: ' . $e->getMessage());
        }
    }

    /**
     * Elimina un token
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('ID de token requerido');
            }

            // Verificar que el token existe
            $token = $this->model->find($id);
            if (!$token) {
                return $this->failNotFound('Token no encontrado');
            }

            // Eliminar token
            $this->model->delete($id);
            return $this->respondDeleted(['mensaje' => 'Token eliminado con éxito']);

        } catch (\Exception $e) {
            return $this->failServerError('Error al eliminar el token: ' . $e->getMessage());
        }
    }

    /**
     * Endpoint personalizado para generar token desde credenciales externas
     */
    public function generarTokenExterno()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validar datos obligatorios
            if (empty($data['client_id']) || empty($data['client_secret'])) {
                return $this->failValidationErrors('Client ID y Client Secret son obligatorios');
            }

            // Verificar si ya existen credenciales con este client_id
            $tokenExistente = $this->model->where('client_id', $data['client_id'])->first();

            if ($tokenExistente) {
                // Actualizar credenciales existentes
                $datosActualizados = [
                    'client_secret' => $data['client_secret']
                ];

                $this->model->update($tokenExistente['id'], $datosActualizados);
                $tokenActualizado = $this->model->find($tokenExistente['id']);

                return $this->respond([
                    'mensaje' => 'Credenciales actualizadas',
                    'token' => $tokenActualizado
                ]);
            } else {
                // Crear nuevas credenciales
                $nuevasCredenciales = [
                    'client_id' => $data['client_id'],
                    'client_secret' => $data['client_secret']
                ];

                $tokenId = $this->model->insert($nuevasCredenciales);
                $tokenCreado = $this->model->find($tokenId);

                return $this->respondCreated([
                    'mensaje' => 'Credenciales creadas',
                    'token' => $tokenCreado
                ]);
            }

        } catch (\Exception $e) {
            return $this->failServerError('Error al procesar las credenciales: ' . $e->getMessage());
        }
    }
}
