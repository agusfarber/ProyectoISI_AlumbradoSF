<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ReclamoModel;
use App\Models\Historial_reclamoModel;

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

        // Obtener el reclamo actual para comparar estados
        $reclamoActual = $this->model->find($id);
        if (!$reclamoActual) {
            return $this->failNotFound('Reclamo no encontrado.');
        }

        // Verificar si hay cambio de estado para registrar en historial
        $estadoAnterior = $reclamoActual['municipalidad_estado'] ?? '';
        $estadoNuevo = $data['municipalidad_estado'] ?? '';
        
        // Log de depuración para verificar los valores
        log_message('debug', 'Estado anterior: ' . $estadoAnterior);
        log_message('debug', 'Estado nuevo: ' . $estadoNuevo);
        log_message('debug', 'Datos recibidos: ' . json_encode($data));
        
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

        // Registrar cambio de estado en historial si hubo cambio
        if (!empty($estadoNuevo) && $estadoAnterior !== $estadoNuevo) {
            // Verificar que tenemos el nro_reclamo correcto
            $nroReclamo = $reclamoActual['municipalidad_id'] ?? $data['municipalidad_id'] ?? '';
            $this->registrarCambioEstado($nroReclamo, $estadoAnterior, $estadoNuevo);
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

    /**
     * Obtiene el historial de cambios de estado de un reclamo específico
     */
    public function historial($id = null)
    {
        if (!$id) {
            return $this->failValidationErrors('ID de reclamo requerido.');
        }

        try {
            // Obtener el reclamo para verificar que existe
            $reclamo = $this->model->find($id);
            if (!$reclamo) {
                return $this->failNotFound('Reclamo no encontrado.');
            }

            $historialModel = new Historial_reclamoModel();
            $db = \Config\Database::connect();
            
            // Obtener el historial con el nombre del usuario
            $query = $db->table('historial_reclamo h')
                        ->select('h.*, u.nombre as nombre_usuario')
                        ->join('usuario u', 'u.id = h.usuario_id', 'left')
                        ->where('h.nro_reclamo', $reclamo['municipalidad_id'])
                        ->orderBy('h.fecha_cambio', 'DESC')
                        ->get();
            
            $historial = $query->getResultArray();
            
            return $this->respond($historial);
            
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener historial: ' . $e->getMessage());
            return $this->failServerError('Error al obtener el historial del reclamo.');
        }
    }

    /**
     * Registra un cambio de estado en el historial de reclamos
     */
    private function registrarCambioEstado($nroReclamo, $estadoAnterior, $estadoNuevo)
    {
        try {
            $historialModel = new Historial_reclamoModel();
            
            // Log de depuración para verificar los parámetros recibidos
            log_message('debug', 'Registrando cambio - NroReclamo: ' . $nroReclamo . ', EstadoAnterior: ' . $estadoAnterior . ', EstadoNuevo: ' . $estadoNuevo);
            
            // Obtener el ID del usuario desde la sesión
            $usuarioId = session()->get('user_id');
            
            // Si no hay usuario en sesión, usar un valor por defecto o no registrar
            if (!$usuarioId) {
                // Opción 1: No registrar si no hay usuario
                // return;
                
                // Opción 2: Usar un valor por defecto (sistema)
                $usuarioId = 0; // 0 podría representar "sistema" o "no especificado"
            }

            $datosHistorial = [
                'nro_reclamo' => $nroReclamo,
                'estado_anterior' => $estadoAnterior,
                'estado_actual' => $estadoNuevo,
                'usuario_id' => $usuarioId,
                'fecha_cambio' => date('Y-m-d H:i:s')
            ];

            log_message('debug', 'Datos a insertar en historial: ' . json_encode($datosHistorial));
            $historialModel->insert($datosHistorial);
            
        } catch (\Exception $e) {
            // Log del error pero no interrumpir el flujo principal
            log_message('error', 'Error al registrar cambio de estado en historial: ' . $e->getMessage());
        }
    }
}
