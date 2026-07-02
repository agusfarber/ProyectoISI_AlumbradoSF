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

    /**
     * Sube/actualiza la foto de perfil de un usuario.
     * Recibe el archivo en el campo 'foto' (multipart/form-data).
     * Guarda el archivo en public/static/uploads/perfiles/ y almacena
     * solo el nombre del archivo en la columna foto_perfil.
     */
    public function subirFoto($id = null)
    {
        $usuario = $id ? $this->model->find($id) : null;
        if (!$usuario) {
            return $this->failNotFound('Usuario no encontrado.');
        }

        $archivo = $this->request->getFile('foto');
        if (!$archivo || !$archivo->isValid()) {
            return $this->failValidationErrors('No se recibió un archivo válido.');
        }

        // Validar tipo de imagen permitido
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($archivo->getMimeType(), $tiposPermitidos, true)) {
            return $this->failValidationErrors('Formato no permitido. Use JPG, PNG o WEBP.');
        }

        // Validar tamaño máximo (2 MB)
        if ($archivo->getSize() > 2 * 1024 * 1024) {
            return $this->failValidationErrors('La imagen no debe superar los 2 MB.');
        }

        // Carpeta de destino dentro de public/static/uploads/perfiles
        $directorio = FCPATH . 'static/uploads/perfiles';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        // Nombre único: u{id}_{random}.{ext}
        $extension = $archivo->getExtension() ?: 'jpg';
        $nombreArchivo = 'u' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $extension;

        if (!$archivo->move($directorio, $nombreArchivo)) {
            return $this->failServerError('No se pudo guardar la imagen.');
        }

        // Eliminar la foto anterior si existía
        if (!empty($usuario['foto_perfil'])) {
            $anterior = $directorio . DIRECTORY_SEPARATOR . $usuario['foto_perfil'];
            if (is_file($anterior)) {
                @unlink($anterior);
            }
        }

        $this->model->update($id, ['foto_perfil' => $nombreArchivo]);

        // Si el usuario que actualiza su foto es el logueado, refrescar la sesión
        $session = \Config\Services::session();
        if ((string) $session->get('user_id') === (string) $id) {
            $session->set('foto_perfil', $nombreArchivo);
        }

        return $this->respond([
            'mensaje' => 'Foto actualizada correctamente.',
            'foto_perfil' => $nombreArchivo,
            'url' => base_url('static/uploads/perfiles/' . $nombreArchivo),
        ]);
    }

    public function operarios()
    {
        try {
            // Obtener usuarios con rol de operario (asumiendo que el rol 3 es operario)
            $db = \Config\Database::connect();
            
            // Obtener todos los usuarios con rol 3 (operarios)
            $query = $db->table('usuario')
                        ->select('id, nombre, email, legajo, foto_perfil')
                        ->where('idRol', 3) // Asumiendo que el rol 3 es operario
                        ->get();
            
            $operarios = $query->getResultArray();
            
            // Si no hay operarios con rol 3, obtener todos los usuarios para testing
            if (empty($operarios)) {
                log_message('info', 'No se encontraron operarios con rol 3, obteniendo todos los usuarios');
                $query = $db->table('usuario')
                            ->select('id, nombre, email, legajo, foto_perfil')
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
