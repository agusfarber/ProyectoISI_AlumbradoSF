<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\MaterialModel;

class Materiales extends ResourceController
{
    protected $modelName = 'App\Models\MaterialModel';
    protected $format = 'json';

    public function index()
    {
        return $this->respond($this->model->findAll());
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        if (empty($data['nombre']) || !isset($data['cantidad'])) {
            return $this->failValidationErrors('Faltan datos obligatorios: nombre y cantidad.');
        }

        // Normalizar y validar
        $data['nombre'] = trim((string) $data['nombre']);
        $data['cantidad'] = (int) $data['cantidad'];
        if ($data['nombre'] === '' || $data['cantidad'] < 0) {
            return $this->failValidationErrors('Nombre inválido o cantidad negativa.');
        }

        $id = $this->model->insert($data);
        if ($id === false) {
            return $this->failServerError('Error al guardar material.');
        }

        $created = $this->model->find($id);
        return $this->respondCreated($created);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        if (!$id || empty($data)) {
            return $this->failValidationErrors('Faltan datos obligatorios.');
        }

        if (isset($data['nombre'])) {
            $data['nombre'] = trim((string) $data['nombre']);
            if ($data['nombre'] === '') {
                return $this->failValidationErrors('El nombre no puede estar vacío.');
            }
        }

        if (isset($data['cantidad'])) {
            $data['cantidad'] = (int) $data['cantidad'];
            if ($data['cantidad'] < 0) {
                return $this->failValidationErrors('La cantidad no puede ser negativa.');
            }
        }

        $ok = $this->model->update($id, $data);
        if ($ok === false) {
            return $this->failServerError('Error al actualizar el material.');
        }

        $updated = $this->model->find($id);
        return $this->respond($updated);
    }

    public function delete($id = null)
    {
        if (!$id || !$this->model->find($id)) {
            return $this->failNotFound('Material no encontrado.');
        }
        $this->model->delete($id);
        return $this->respondDeleted(['mensaje' => 'Material eliminado con éxito.']);
    }

    /**
     * Importa una lista de materiales desde un JSON (parseado en el front desde CSV/XLSX)
     * Formato esperado: { items: [ { nombre: string, cantidad: number }, ... ] }
     */
    public function import()
    {
        $payload = $this->request->getJSON(true);
        if (!$payload || !isset($payload['items']) || !is_array($payload['items'])) {
            return $this->failValidationErrors('Formato inválido. Se espera un objeto con el campo "items" (array).');
        }

        $items = $payload['items'];
        $errores = [];
        $validados = [];

        foreach ($items as $index => $item) {
            $nombre = isset($item['nombre']) ? trim((string) $item['nombre']) : '';
            $cantidad = isset($item['cantidad']) ? (int) $item['cantidad'] : null;

            if ($nombre === '' || $cantidad === null || $cantidad < 0) {
                $errores[] = "Fila " . ($index + 1) . ": datos inválidos (nombre requerido y cantidad >= 0).";
                continue;
            }

            $validados[] = [
                'nombre' => $nombre,
                'cantidad' => $cantidad,
            ];
        }

        if (empty($validados)) {
            return $this->failValidationErrors('No hay materiales válidos para importar.' . (!empty($errores) ? ' Detalles: ' . implode(' | ', $errores) : ''));
        }

        // Inserción en lote
        $this->model->insertBatch($validados);

        return $this->respond([
            'mensaje' => 'Importación completada.',
            'insertados' => count($validados),
            'errores' => $errores,
        ]);
    }
}

