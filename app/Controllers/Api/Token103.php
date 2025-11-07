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
     * Crea nuevas credenciales Basic Auth
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true);

            // Validar datos obligatorios
            if (empty($data['username']) || empty($data['password'])) {
                return $this->failValidationErrors('Username y Password son obligatorios');
            }

            // Insertar credenciales
            $tokenId = $this->model->insert($data);

            if ($tokenId === false) {
                return $this->failServerError('Error al guardar las credenciales');
            }

            $tokenCreado = $this->model->find($tokenId);
            return $this->respondCreated($tokenCreado);

        } catch (\Exception $e) {
            return $this->failServerError('Error al crear las credenciales: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza credenciales existentes
     */
    public function update($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('ID de credenciales requerido');
            }

            $data = $this->request->getJSON(true);

            if (empty($data)) {
                return $this->failValidationErrors('No se proporcionaron datos para actualizar');
            }

            // Verificar que las credenciales existen
            $tokenExistente = $this->model->find($id);
            if (!$tokenExistente) {
                return $this->failNotFound('Credenciales no encontradas');
            }

            // Actualizar credenciales
            $actualizado = $this->model->update($id, $data);

            if ($actualizado === false) {
                return $this->failServerError('Error al actualizar las credenciales');
            }

            $tokenActualizado = $this->model->find($id);
            return $this->respond($tokenActualizado);

        } catch (\Exception $e) {
            return $this->failServerError('Error al actualizar las credenciales: ' . $e->getMessage());
        }
    }

    /**
     * Elimina credenciales
     */
    public function delete($id = null)
    {
        try {
            if (!$id) {
                return $this->failValidationErrors('ID de credenciales requerido');
            }

            // Verificar que las credenciales existen
            $token = $this->model->find($id);
            if (!$token) {
                return $this->failNotFound('Credenciales no encontradas');
            }

            // Eliminar credenciales
            $this->model->delete($id);
            return $this->respondDeleted(['mensaje' => 'Credenciales eliminadas con éxito']);

        } catch (\Exception $e) {
            return $this->failServerError('Error al eliminar las credenciales: ' . $e->getMessage());
        }
    }

    /**
     * Genera el token Basic Auth codificado en base64
     */
    public function generarTokenBasicAuth()
    {
        try {
            // Obtener las credenciales guardadas (la más reciente)
            $credenciales = $this->model->orderBy('id', 'DESC')->first();

            if (!$credenciales) {
                return $this->failNotFound('No hay credenciales guardadas. Debe guardar username y password primero.');
            }

            // Generar el token Basic Auth: "username:password" codificado en base64
            $credencialesString = $credenciales['username'] . ':' . $credenciales['password'];
            $tokenBase64 = base64_encode($credencialesString);

            return $this->respond([
                'mensaje' => 'Token Basic Auth generado exitosamente',
                'token_base64' => $tokenBase64,
                'authorization_header' => 'Basic ' . $tokenBase64
            ]);

        } catch (\Exception $e) {
            return $this->failServerError('Error al generar el token: ' . $e->getMessage());
        }
    }
}
