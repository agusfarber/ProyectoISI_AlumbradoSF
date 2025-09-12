<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CuadrillaModel;
use App\Models\CuadrillaOperariosModel;
use App\Models\UsuarioModel;

class Cuadrillas extends ResourceController
{
    protected $modelName = 'App\Models\CuadrillaModel';
    protected $format = 'json';

    public function index()
    {
        try {
            // Obtener todas las cuadrillas
            $cuadrillas = $this->model->findAll();
            
            // Para cada cuadrilla, obtener los operarios asignados
            $db = \Config\Database::connect();
            foreach ($cuadrillas as &$cuadrilla) {
                $query = $db->table('cuadrilla_operarios AS co')
                            ->select('u.id, u.nombre, u.email, u.legajo')
                            ->join('usuario AS u', 'u.id = co.usuario_id')
                            ->where('co.cuadrilla_id', $cuadrilla['id'])
                            ->get();
                
                $cuadrilla['operarios'] = $query->getResultArray();
            }
            
            return $this->respond($cuadrillas);
        } catch (\Exception $e) {
            log_message('error', 'Error en index de cuadrillas: ' . $e->getMessage());
            return $this->failServerError('Error al obtener cuadrillas: ' . $e->getMessage());
        }
    }

    public function create()
    {
        // Verificar que el contenido JSON sea válido
        $jsonString = $this->request->getBody();
        if (empty($jsonString)) {
            return $this->failValidationErrors('No se recibieron datos para crear la cuadrilla.');
        }
        
        // Intentar decodificar JSON con manejo de errores
        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'Error JSON en create cuadrilla: ' . json_last_error_msg());
            return $this->failValidationErrors('Error en el formato de datos enviados.');
        }

        // Validar datos
        if (empty($data['nombre'])) {
            return $this->failValidationErrors('El nombre de la cuadrilla es obligatorio.');
        }

        // Insertar cuadrilla
        $cuadrillaId = $this->model->insert($data);

        if ($cuadrillaId === false) {
            return $this->failServerError('Error al guardar cuadrilla.');
        }

        $cuadrillaCreada = $this->model->find($cuadrillaId);

        return $this->respondCreated($cuadrillaCreada);
    }

    public function update($id = null)
    {
        try {
            // Verificar que el contenido JSON sea válido
            $jsonString = $this->request->getBody();
            if (empty($jsonString)) {
                return $this->failValidationErrors('No se recibieron datos para actualizar.');
            }
            
            // Intentar decodificar JSON con manejo de errores
            $data = json_decode($jsonString, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'Error JSON en update cuadrilla: ' . json_last_error_msg());
                return $this->failValidationErrors('Error en el formato de datos enviados.');
            }
            
            // Log para debugging
            log_message('info', 'Update cuadrilla - ID: ' . $id . ' - Datos recibidos: ' . json_encode($data));

            if (!$id) {
                return $this->failValidationErrors('ID de cuadrilla es obligatorio.');
            }

            // Para edición, no validar que haya datos - puede ser solo descripción
            // if (empty($data)) {
            //     return $this->failValidationErrors('No se recibieron datos para actualizar.');
            // }

            // Validar que la cuadrilla existe
            $cuadrillaExistente = $this->model->find($id);
            if (!$cuadrillaExistente) {
                return $this->failNotFound('Cuadrilla no encontrada.');
            }

            // Preparar datos para actualización (solo campos permitidos)
            $datosActualizacion = [];
            if (isset($data['nombre'])) {
                $datosActualizacion['nombre'] = trim($data['nombre']);
            }
            if (isset($data['descripcion'])) {
                $datosActualizacion['descripcion'] = trim($data['descripcion']);
            }

            // Para edición, permitir actualización incluso si solo se modifica la descripción
            // No validar que haya datos obligatorios en edición

            // Si no hay datos para actualizar, retornar la cuadrilla actual sin cambios
            if (empty($datosActualizacion)) {
                log_message('info', 'No hay datos para actualizar, retornando cuadrilla actual');
                return $this->respond($cuadrillaExistente);
            }

            log_message('info', 'Datos preparados para actualización: ' . json_encode($datosActualizacion));

            log_message('info', 'Actualizando cuadrilla ID: ' . $id . ' con datos: ' . json_encode($datosActualizacion));
            
            // Intentar actualización directa con la base de datos
            $db = \Config\Database::connect();
            $builder = $db->table('cuadrilla');
            
            foreach ($datosActualizacion as $campo => $valor) {
                $builder->set($campo, $valor);
            }
            
            $builder->where('id', $id);
            $actualizado = $builder->update();
            
            log_message('info', 'Resultado de actualización: ' . ($actualizado ? 'true' : 'false'));

            if ($actualizado === false) {
                log_message('error', 'Error al actualizar cuadrilla ID: ' . $id . ' - Datos: ' . json_encode($datosActualizacion));
                return $this->failServerError('Error al actualizar la cuadrilla.');
            }

            $cuadrillaActualizada = $this->model->find($id);
            return $this->respond($cuadrillaActualizada);

        } catch (\Exception $e) {
            log_message('error', 'Error en update de cuadrillas: ' . $e->getMessage());
            return $this->failServerError('Error interno al actualizar cuadrilla: ' . $e->getMessage());
        }
    }

    public function delete($id = null)
    {
        if (!$id || !$this->model->find($id)) {
            return $this->failNotFound('Cuadrilla no encontrada.');
        }

        // Primero eliminar las asignaciones de operarios
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $cuadrillaOperariosModel->where('cuadrilla_id', $id)->delete();

        // Luego eliminar la cuadrilla
        $this->model->delete($id);
        return $this->respondDeleted(['mensaje' => 'Cuadrilla eliminada con éxito.']);
    }

    public function asignar()
    {
        $data = $this->request->getJSON(true);
        
        log_message('info', 'Método asignar llamado con datos: ' . json_encode($data));

        if (empty($data['cuadrillaId'])) {
            log_message('error', 'ID de cuadrilla no proporcionado');
            return $this->failValidationErrors('ID de cuadrilla es obligatorio.');
        }
        
        // Si no hay operarios, simplemente eliminar todas las asignaciones existentes
        if (empty($data['operarios']) || !is_array($data['operarios'])) {
            $data['operarios'] = [];
        }

        log_message('info', 'Operarios a asignar: ' . json_encode($data['operarios']));

        // Validar máximo 4 operarios por cuadrilla
        if (count($data['operarios']) > 4) {
            log_message('error', 'Demasiados operarios: ' . count($data['operarios']));
            return $this->failValidationErrors('Solo se pueden asignar máximo 4 operarios por cuadrilla.');
        }

        try {
            $cuadrillaOperariosModel = new CuadrillaOperariosModel();
            $db = \Config\Database::connect();
            
            // Iniciar transacción
            $db->transStart();

            // Primero eliminar todas las asignaciones existentes de la cuadrilla
            $cuadrillaOperariosModel->where('cuadrilla_id', $data['cuadrillaId'])->delete();

            // Luego asignar los operarios a la cuadrilla
            foreach ($data['operarios'] as $operarioId) {
                $cuadrillaOperariosModel->insert([
                    'cuadrilla_id' => $data['cuadrillaId'],
                    'usuario_id' => $operarioId
                ]);
            }

            // Confirmar transacción
            $db->transComplete();

            if ($db->transStatus() === false) {
                log_message('error', 'Error en transacción de asignación de operarios');
                return $this->failServerError('Error al asignar operarios.');
            }

            log_message('info', 'Operarios asignados correctamente a cuadrilla ID: ' . $data['cuadrillaId']);
            return $this->respond(['status' => 'success', 'mensaje' => 'Operarios asignados correctamente.']);

        } catch (\Exception $e) {
            log_message('error', 'Error al asignar operarios: ' . $e->getMessage());
            return $this->failServerError('Error al asignar operarios: ' . $e->getMessage());
        }
    }
}