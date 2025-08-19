<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ReclamoModel;

class Reclamos extends ResourceController
{
    protected $modelName = 'App\Models\ReclamoModel';
    protected $format = 'json';

    public function __construct()
    {
        // Configurar zona horaria de Argentina
        date_default_timezone_set('America/Argentina/Buenos_Aires');
    }

    public function index()
    {
        return $this->respond($this->model->findAll());
    }

    public function create()
    {
        $data = $this->request->getJSON(true);

        // Validar datos obligatorios
        if (empty($data['municipalidad_id']) || empty($data['municipalidad_motivo']) || empty($data['municipalidad_estado'])) {
            return $this->failValidationErrors('Faltan datos obligatorios (ID, Motivo, Estado).');
        }

        // Establecer tipo fijo
        $data['municipalidad_tipo'] = 'ALUMBRADO PÚBLICO';

        // Validar y formatear fechas
        if (!empty($data['municipalidad_fechaInicio'])) {
            $data['municipalidad_fechaInicio'] = $this->formatearFecha($data['municipalidad_fechaInicio']);
        } else {
            $data['municipalidad_fechaInicio'] = date('Y-m-d H:i:s');
        }

        if (!empty($data['municipalidad_fechaModificacion'])) {
            $data['municipalidad_fechaModificacion'] = $this->formatearFecha($data['municipalidad_fechaModificacion']);
        } else {
            $data['municipalidad_fechaModificacion'] = date('Y-m-d H:i:s');
        }

        // Si la prioridad no viene, asignar un valor por defecto
        if (empty($data['prioridad'])) { // Cambiado a 'prioridad'
            $data['prioridad'] = 'Baja'; // Puedes elegir el valor por defecto que prefieras
        }

        // Insertar reclamo
        $reclamoId = $this->model->insert($data);

        if ($reclamoId === false) {
            return $this->failServerError('Error al guardar reclamo.');
        }

        $reclamoCreado = $this->model->find($reclamoId);

        return $this->respondCreated($reclamoCreado);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);

        if (!$id || empty($data)) {
            return $this->failValidationErrors('Faltan datos obligatorios.');
        }

        // Establecer tipo fijo
        $data['municipalidad_tipo'] = 'ALUMBRADO PÚBLICO';

        // Actualizar fecha de modificación
        if (!empty($data['municipalidad_fechaModificacion'])) {
            $data['municipalidad_fechaModificacion'] = $this->formatearFecha($data['municipalidad_fechaModificacion']);
        } else {
            $data['municipalidad_fechaModificacion'] = date('Y-m-d H:i:s');
        }

        // Formatear fecha de inicio si se proporciona
        if (!empty($data['municipalidad_fechaInicio'])) {
            $data['municipalidad_fechaInicio'] = $this->formatearFecha($data['municipalidad_fechaInicio']);
        }

        // La prioridad se manejará si se envía en $data. No se necesita un valor por defecto explícito
        // aquí porque el campo ya existe en la base de datos y se actualizará si se proporciona.

        $actualizado = $this->model->update($id, $data);

        if ($actualizado === false) {
            return $this->failServerError('Error al actualizar el reclamo.');
        }

        $reclamoActualizado = $this->model->find($id);
        return $this->respond($reclamoActualizado);
    }

    public function delete($id = null)
    {
        if (!$id || !$this->model->find($id)) {
            return $this->failNotFound('Reclamo no encontrado.');
        }

        $this->model->delete($id);
        return $this->respondDeleted(['mensaje' => 'Reclamo eliminado con éxito.']);
    }

    /**
     * Formatea una fecha al formato de base de datos con zona horaria de Argentina
     */
    private function formatearFecha($fecha)
    {
        if (empty($fecha)) {
            return date('Y-m-d H:i:s');
        }

        // Si ya está en formato correcto, retornarlo
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fecha)) {
            return $fecha;
        }

        // Si es formato ISO, convertirlo considerando zona horaria de Argentina
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $fecha)) {
            $date = new \DateTime($fecha, new \DateTimeZone('UTC'));
            $date->setTimezone(new \DateTimeZone('America/Argentina/Buenos_Aires'));
            return $date->format('Y-m-d H:i:s');
        }

        // Intentar parsear la fecha con zona horaria de Argentina
        $date = new \DateTime($fecha, new \DateTimeZone('America/Argentina/Buenos_Aires'));
        return $date->format('Y-m-d H:i:s');
    }
}
