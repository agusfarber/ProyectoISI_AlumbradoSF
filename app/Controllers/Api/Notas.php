<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\NotaSupervisorModel;

/**
 * Notas personales del supervisor.
 * Módulo aislado: solo rol supervisor (2) y solo sus propias notas.
 */
class Notas extends ResourceController
{
    protected $modelName = NotaSupervisorModel::class;
    protected $format = 'json';

    public function index()
    {
        if (!$this->esSupervisor()) {
            return $this->failForbidden('Solo supervisores pueden ver notas.');
        }

        $usuarioId = $this->usuarioId();
        $filtro = $this->request->getGet('filtro') ?? 'activas';
        $limit = (int) ($this->request->getGet('limit') ?? 30);
        $page = (int) ($this->request->getGet('page') ?? 1);

        if ($limit < 1) {
            $limit = 30;
        }
        if ($limit > 100) {
            $limit = 100;
        }
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $limit;

        $builder = $this->model->where('usuario_id', $usuarioId);

        if ($filtro === 'activas') {
            $builder->where('hecha', 0);
        } elseif ($filtro === 'hechas') {
            $builder->where('hecha', 1);
        }

        $total = $builder->countAllResults(false);

        $notas = $builder
            ->orderBy('fijada', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->findAll($limit, $offset);

        return $this->respond([
            'notas' => $notas,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'hay_mas' => ($offset + count($notas)) < $total,
            'filtro' => $filtro,
        ]);
    }

    public function create()
    {
        if (!$this->esSupervisor()) {
            return $this->failForbidden('Solo supervisores pueden crear notas.');
        }

        $data = $this->request->getJSON(true);
        if (!is_array($data)) {
            return $this->failValidationErrors('No se recibieron datos.');
        }

        $contenido = trim((string) ($data['contenido'] ?? ''));
        if ($contenido === '') {
            return $this->failValidationErrors('El contenido de la nota es obligatorio.');
        }

        $titulo = trim((string) ($data['titulo'] ?? ''));
        $payload = [
            'usuario_id' => $this->usuarioId(),
            'titulo' => $titulo !== '' ? mb_substr($titulo, 0, 160) : null,
            'contenido' => $contenido,
            'hecha' => 0,
            'fijada' => !empty($data['fijada']) ? 1 : 0,
        ];

        $id = $this->model->insert($payload);
        if ($id === false) {
            return $this->failServerError('No se pudo guardar la nota.');
        }

        return $this->respondCreated($this->model->find($id));
    }

    public function update($id = null)
    {
        if (!$this->esSupervisor()) {
            return $this->failForbidden('Solo supervisores pueden editar notas.');
        }

        $nota = $this->obtenerNotaPropia($id);
        if ($nota === null) {
            return $this->failNotFound('Nota no encontrada.');
        }

        $data = $this->request->getJSON(true);
        if (!is_array($data) || empty($data)) {
            return $this->failValidationErrors('No se recibieron datos.');
        }

        $payload = [];

        if (array_key_exists('titulo', $data)) {
            $titulo = trim((string) $data['titulo']);
            $payload['titulo'] = $titulo !== '' ? mb_substr($titulo, 0, 160) : null;
        }

        if (array_key_exists('contenido', $data)) {
            $contenido = trim((string) $data['contenido']);
            if ($contenido === '') {
                return $this->failValidationErrors('El contenido de la nota es obligatorio.');
            }
            $payload['contenido'] = $contenido;
        }

        if (array_key_exists('hecha', $data)) {
            $payload['hecha'] = !empty($data['hecha']) ? 1 : 0;
        }

        if (array_key_exists('fijada', $data)) {
            $payload['fijada'] = !empty($data['fijada']) ? 1 : 0;
        }

        if (empty($payload)) {
            return $this->failValidationErrors('No hay cambios para guardar.');
        }

        if ($this->model->update($id, $payload) === false) {
            return $this->failServerError('No se pudo actualizar la nota.');
        }

        return $this->respond($this->model->find($id));
    }

    public function delete($id = null)
    {
        if (!$this->esSupervisor()) {
            return $this->failForbidden('Solo supervisores pueden eliminar notas.');
        }

        $nota = $this->obtenerNotaPropia($id);
        if ($nota === null) {
            return $this->failNotFound('Nota no encontrada.');
        }

        $this->model->delete($id);
        return $this->respondDeleted(['mensaje' => 'Nota eliminada.']);
    }

    private function esSupervisor(): bool
    {
        return (string) session()->get('role') === '2';
    }

    private function usuarioId(): int
    {
        return (int) session()->get('user_id');
    }

    private function obtenerNotaPropia($id): ?array
    {
        if (!$id) {
            return null;
        }
        $nota = $this->model->find($id);
        if (!$nota || (int) ($nota['usuario_id'] ?? 0) !== $this->usuarioId()) {
            return null;
        }
        return $nota;
    }
}
