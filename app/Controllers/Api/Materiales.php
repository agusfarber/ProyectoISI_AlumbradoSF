<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\MaterialModel;
use App\Models\Tipo_materialModel;

class Materiales extends ResourceController
{
    protected $modelName = 'App\Models\MaterialModel';
    protected $format = 'json';

    public function __construct()
    {
        // Se carga el modelo de tipos de material en el constructor
        $this->tipoMaterialModel = new Tipo_materialModel();
    }

    // Métodos para Materiales (CRUD)
    // ------------------------------------

    public function index()
    {
        return $this->respond($this->model->findAllWithTipo());
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        if (empty($data['nombre']) || !isset($data['cantidad']) || !isset($data['idTipo'])) {
            return $this->failValidationErrors('Faltan datos obligatorios: nombre, cantidad y tipo.');
        }

        $data['nombre'] = trim((string) $data['nombre']);
        $data['cantidad'] = (int) $data['cantidad'];
        $data['idTipo'] = (int) $data['idTipo'];

        // La validación de creación sigue requiriendo un tipo
        if ($data['nombre'] === '' || $data['cantidad'] < 0 || $data['idTipo'] <= 0) {
            return $this->failValidationErrors('Nombre, cantidad o tipo inválidos.');
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
        
        if (isset($data['idTipo'])) {
            $data['idTipo'] = (int) $data['idTipo'];
            // Permite un valor de 0 (o nulo) para idTipo
            if ($data['idTipo'] < 0) {
                return $this->failValidationErrors('El tipo de material es inválido.');
            }
        }
        
        // Asigna el valor NULL si el idTipo es 0
        if (isset($data['idTipo']) && $data['idTipo'] === 0) {
            $data['idTipo'] = null;
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

    public function import()
    {
        $payload = $this->request->getJSON(true);
        if (!$payload || !isset($payload['items']) || !is_array($payload['items'])) {
            return $this->failValidationErrors('Formato inválido. Se espera un objeto con el campo "items" (array).');
        }

        $items = $payload['items'];
        $errores = [];
        $validados = [];
        
        $tiposExistentes = $this->tipoMaterialModel->findAll();
        $tiposMap = array_column($tiposExistentes, 'id', 'nombre');
        
        foreach ($items as $index => $item) {
            $nombre = isset($item['nombre']) ? trim((string) $item['nombre']) : '';
            $cantidad = isset($item['cantidad']) ? (int) $item['cantidad'] : null;
            $tipoNombre = isset($item['tipo']) ? trim((string) $item['tipo']) : '';
            $idTipo = $tiposMap[strtolower($tipoNombre)] ?? 0;
            
            if ($nombre === '' || $cantidad === null || $cantidad < 0 || $idTipo === 0) {
                $errores[] = "Fila " . ($index + 1) . ": datos inválidos (nombre, cantidad >= 0 y tipo requerido).";
                continue;
            }

            $validados[] = [
                'nombre' => $nombre,
                'cantidad' => $cantidad,
                'idTipo' => $idTipo,
            ];
        }

        if (empty($validados)) {
            return $this->failValidationErrors('No hay materiales válidos para importar.' . (!empty($errores) ? ' Detalles: ' . implode(' | ', $errores) : ''));
        }

        $this->model->insertBatch($validados);

        return $this->respond([
            'mensaje' => 'Importación completada.',
            'insertados' => count($validados),
            'errores' => $errores,
        ]);
    }

    // Métodos para Tipos de Materiales
    // ------------------------------------

    public function getTipos()
    {
        return $this->respond($this->tipoMaterialModel->findAll());
    }

    public function createTipo()
    {
        $data = $this->request->getJSON(true);
        if (empty($data['nombre'])) {
            return $this->failValidationErrors('El nombre del tipo de material es obligatorio.');
        }

        $data['nombre'] = trim((string) $data['nombre']);
        if ($data['nombre'] === '') {
            return $this->failValidationErrors('El nombre del tipo de material no puede estar vacío.');
        }

        $id = $this->tipoMaterialModel->insert($data);
        if ($id === false) {
            return $this->failServerError('Error al guardar tipo de material.');
        }

        $created = $this->tipoMaterialModel->find($id);
        return $this->respondCreated($created);
    }


    public function deleteTipo($id = null)
    {
        if (!$id || !$this->tipoMaterialModel->find($id)) {
            return $this->failNotFound('Tipo de material no encontrado.');
        }

        // Verifica si hay materiales asociados a este tipo antes de borrar
        $materialesAsociados = $this->model->where('idTipo', $id)->first();
        if ($materialesAsociados) {
            return $this->failConflict('No se puede eliminar el tipo porque tiene materiales asociados.');
        }

        $this->tipoMaterialModel->delete($id);
        return $this->respondDeleted(['mensaje' => 'Tipo de material eliminado con éxito.']);
    }
}