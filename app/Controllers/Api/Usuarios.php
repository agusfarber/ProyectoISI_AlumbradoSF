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
        try {
            // Obtener todos los usuarios
            $usuarios = $this->model->findAll();
            
            return $this->respond($usuarios);
        } catch (\Exception $e) {
            log_message('error', 'Error en index de usuarios: ' . $e->getMessage());
            return $this->failServerError('Error al obtener usuarios: ' . $e->getMessage());
        }
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

    public function operarios()
    {
        try {
            // Obtener usuarios con rol de operario (asumiendo que el rol 3 es operario)
            $db = \Config\Database::connect();
            
            // Obtener todos los usuarios con rol 3 (operarios)
            $query = $db->table('usuario')
                        ->select('id, nombre, email, legajo')
                        ->where('idRol', 3) // Asumiendo que el rol 3 es operario
                        ->get();
            
            $operarios = $query->getResultArray();
            
            // Si no hay operarios con rol 3, obtener todos los usuarios para testing
            if (empty($operarios)) {
                log_message('info', 'No se encontraron operarios con rol 3, obteniendo todos los usuarios');
                $query = $db->table('usuario')
                            ->select('id, nombre, email, legajo')
                            ->get();
                $operarios = $query->getResultArray();
            }
            
            // Para cada operario, obtener su cuadrilla actual
            foreach ($operarios as &$operario) {
                $cuadrillaQuery = $db->table('cuadrilla_operarios AS co')
                                    ->select('c.nombre as cuadrilla_nombre')
                                    ->join('cuadrilla AS c', 'c.id = co.cuadrilla_id')
                                    ->where('co.usuario_id', $operario['id'])
                                    ->get();
                
                $cuadrilla = $cuadrillaQuery->getRowArray();
                $operario['cuadrilla_nombre'] = $cuadrilla ? $cuadrilla['cuadrilla_nombre'] : null;
            }
            
            log_message('info', 'Operarios obtenidos: ' . json_encode($operarios));
            return $this->respond($operarios);
        } catch (\Exception $e) {
            log_message('error', 'Error en operarios: ' . $e->getMessage());
            return $this->failServerError('Error al obtener operarios: ' . $e->getMessage());
        }
    }

}
