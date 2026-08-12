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
            $usuarios = $this->model->orderBy('nombre', 'ASC')->findAll();
            foreach ($usuarios as &$u) {
                unset($u['contrasena']);
            }
            return $this->respond($usuarios);
        } catch (\Exception $e) {
            log_message('error', 'Error en index de usuarios: ' . $e->getMessage());
            return $this->failServerError('Error al obtener usuarios: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (!is_array($data)) {
            return $this->failValidationErrors('No se recibieron datos.');
        }

        $payload = $this->normalizarPayload($data);
        $error = $this->validarPayload($payload, null);
        if ($error !== null) {
            return $this->failValidationErrors($error);
        }
        $payload = $this->aplicarCredencialPorRol($payload);

        $usuarioId = $this->model->insert($payload);
        if ($usuarioId === false) {
            return $this->failServerError('Error al guardar usuario.');
        }

        $usuarioCreado = $this->model->find($usuarioId);
        if (is_array($usuarioCreado)) {
            unset($usuarioCreado['contrasena']);
        }

        return $this->respondCreated($usuarioCreado);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        if (!$id || !is_array($data) || empty($data)) {
            return $this->failValidationErrors('Faltan datos obligatorios.');
        }

        $existente = $this->model->find($id);
        if (!$existente) {
            return $this->failNotFound('Usuario no encontrado.');
        }

        $payload = $this->normalizarPayload($data, true);
        // Si no envían contraseña, no la tocamos
        if (!array_key_exists('contrasena', $payload) || $payload['contrasena'] === null || $payload['contrasena'] === '') {
            unset($payload['contrasena']);
        }

        $error = $this->validarPayload($payload, (int) $id);
        if ($error !== null) {
            return $this->failValidationErrors($error);
        }
        $payload = $this->aplicarCredencialPorRol($payload);

        $actualizado = $this->model->update($id, $payload);
        if ($actualizado === false) {
            return $this->failServerError('Error al actualizar el usuario.');
        }

        $usuarioActualizado = $this->model->find($id);
        if (is_array($usuarioActualizado)) {
            unset($usuarioActualizado['contrasena']);
        }

        return $this->respond($usuarioActualizado);
    }

    public function delete($id = null)
    {
        if (!$id || !$this->model->find($id)) {
            return $this->failNotFound('Usuario no encontrado.');
        }

        $session = \Config\Services::session();
        if ((string) $session->get('user_id') === (string) $id) {
            return $this->failValidationErrors('No podés eliminar tu propio usuario.');
        }

        $this->model->delete($id);
        return $this->respondDeleted(['mensaje' => 'Usuario eliminado con éxito.']);
    }

    /**
     * Sube/actualiza la foto de perfil de un usuario.
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

        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($archivo->getMimeType(), $tiposPermitidos, true)) {
            return $this->failValidationErrors('Formato no permitido. Use JPG, PNG o WEBP.');
        }

        if ($archivo->getSize() > 2 * 1024 * 1024) {
            return $this->failValidationErrors('La imagen no debe superar los 2 MB.');
        }

        $directorio = FCPATH . 'static/uploads/perfiles';
        if (!is_dir($directorio)) {
            mkdir($directorio, 0775, true);
        }

        $extension = $archivo->getExtension() ?: 'jpg';
        $nombreArchivo = 'u' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $extension;

        if (!$archivo->move($directorio, $nombreArchivo)) {
            return $this->failServerError('No se pudo guardar la imagen.');
        }

        if (!empty($usuario['foto_perfil'])) {
            $anterior = $directorio . DIRECTORY_SEPARATOR . $usuario['foto_perfil'];
            if (is_file($anterior)) {
                @unlink($anterior);
            }
        }

        $this->model->update($id, ['foto_perfil' => $nombreArchivo]);

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
            $db = \Config\Database::connect();

            $query = $db->table('usuario')
                        ->select('id, nombre, email, legajo, foto_perfil')
                        ->where('idRol', 3)
                        ->get();

            $operarios = $query->getResultArray();

            if (empty($operarios)) {
                log_message('info', 'No se encontraron operarios con rol 3, obteniendo todos los usuarios');
                $query = $db->table('usuario')
                            ->select('id, nombre, email, legajo, foto_perfil')
                            ->get();
                $operarios = $query->getResultArray();
            }

            foreach ($operarios as &$operario) {
                $cuadrillaQuery = $db->table('cuadrilla_operarios AS co')
                                    ->select('c.nombre as cuadrilla_nombre')
                                    ->join('cuadrilla AS c', 'c.id = co.cuadrilla_id')
                                    ->where('co.usuario_id', $operario['id'])
                                    ->get();

                $cuadrilla = $cuadrillaQuery->getRowArray();
                $operario['cuadrilla_nombre'] = $cuadrilla ? $cuadrilla['cuadrilla_nombre'] : null;
            }

            return $this->respond($operarios);
        } catch (\Exception $e) {
            log_message('error', 'Error en operarios: ' . $e->getMessage());
            return $this->failServerError('Error al obtener operarios: ' . $e->getMessage());
        }
    }

    private function normalizarPayload(array $data, bool $esUpdate = false): array
    {
        $out = [];

        if (array_key_exists('nombre', $data)) {
            $out['nombre'] = trim((string) $data['nombre']);
        }
        if (array_key_exists('email', $data)) {
            $email = trim((string) ($data['email'] ?? ''));
            $out['email'] = $email !== '' ? $email : null;
        }
        if (array_key_exists('legajo', $data)) {
            $legajo = trim((string) ($data['legajo'] ?? ''));
            $out['legajo'] = $legajo !== '' ? $legajo : null;
        }
        if (array_key_exists('idRol', $data)) {
            $out['idRol'] = $data['idRol'] !== '' && $data['idRol'] !== null
                ? (int) $data['idRol']
                : null;
        }
        if (array_key_exists('contrasena', $data)) {
            $pass = (string) ($data['contrasena'] ?? '');
            $out['contrasena'] = $pass !== '' ? $pass : null;
        }

        // En create forzamos campos presentes
        if (!$esUpdate) {
            $out['nombre'] = trim((string) ($data['nombre'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));
            $legajo = trim((string) ($data['legajo'] ?? ''));
            $out['email'] = $email !== '' ? $email : null;
            $out['legajo'] = $legajo !== '' ? $legajo : null;
            $out['idRol'] = isset($data['idRol']) && $data['idRol'] !== '' ? (int) $data['idRol'] : null;
            $out['contrasena'] = (string) ($data['contrasena'] ?? '');
        }

        return $out;
    }

    private function validarPayload(array $payload, ?int $idExcluir): ?string
    {
        if (array_key_exists('nombre', $payload) && $payload['nombre'] === '') {
            return 'El nombre es obligatorio.';
        }

        if (array_key_exists('idRol', $payload) && empty($payload['idRol'])) {
            return 'El rol es obligatorio.';
        }

        if ($idExcluir === null) {
            if (empty($payload['nombre'])) {
                return 'El nombre es obligatorio.';
            }
            if (empty($payload['idRol'])) {
                return 'El rol es obligatorio.';
            }
            if ($payload['contrasena'] === '' || strlen((string) $payload['contrasena']) < 4) {
                return 'La contraseña debe tener al menos 4 caracteres.';
            }
        } elseif (array_key_exists('contrasena', $payload) && $payload['contrasena'] !== null) {
            if (strlen((string) $payload['contrasena']) < 4) {
                return 'La contraseña debe tener al menos 4 caracteres.';
            }
        }

        // Credencial según rol: admin → email; supervisor/operario → legajo
        $idRol = array_key_exists('idRol', $payload)
            ? (int) $payload['idRol']
            : null;

        if ($idExcluir !== null && $idRol === null) {
            $actual = $this->model->find($idExcluir);
            $idRol = $actual ? (int) ($actual['idRol'] ?? 0) : null;
        }

        $email = array_key_exists('email', $payload) ? $payload['email'] : null;
        $legajo = array_key_exists('legajo', $payload) ? $payload['legajo'] : null;

        if ($idRol === 1) {
            if ($email === null || $email === '') {
                return 'El email es obligatorio para administradores.';
            }
        } elseif ($idRol === 2 || $idRol === 3) {
            if ($legajo === null || $legajo === '') {
                return 'El legajo es obligatorio para supervisores y operarios.';
            }
        } elseif ($idExcluir === null || (array_key_exists('email', $payload) && array_key_exists('legajo', $payload))) {
            if (($email === null || $email === '') && ($legajo === null || $legajo === '')) {
                return 'Indicá email (admin) o legajo (supervisor/operario).';
            }
        }

        if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return 'El email no es válido.';
            }
            $q = $this->model->where('email', $email);
            if ($idExcluir !== null) {
                $q->where('id !=', $idExcluir);
            }
            if ($q->first()) {
                return 'Ya existe un usuario con ese email.';
            }
        }

        if (!empty($legajo)) {
            $q = $this->model->where('legajo', $legajo);
            if ($idExcluir !== null) {
                $q->where('id !=', $idExcluir);
            }
            if ($q->first()) {
                return 'Ya existe un usuario con ese legajo.';
            }
        }

        return null;
    }

    private function aplicarCredencialPorRol(array $payload): array
    {
        $idRol = isset($payload['idRol']) ? (int) $payload['idRol'] : 0;
        if ($idRol === 1) {
            $payload['legajo'] = null;
        } elseif ($idRol === 2 || $idRol === 3) {
            $payload['email'] = null;
        }
        return $payload;
    }
}
