<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ReclamoModel;
use App\Models\Historial_reclamoModel;

class CierreReclamos extends ResourceController
{
    protected $modelName = 'App\Models\ReclamoModel';
    protected $format = 'json';

    public function __construct()
    {
        // Configurar zona horaria de Argentina
        date_default_timezone_set('America/Argentina/Buenos_Aires');
    }

    /**
     * Obtiene todos los reclamos con estado "Completado" y cerrado = 0
     * Solo accesible para supervisores (rol = 2)
     */
    public function obtenerReclamosCompletados()
    {
        try {
            // Verificar permisos de supervisor
            if (!$this->validarPermisoSupervisor()) {
                return $this->failUnauthorized('No tiene permisos para acceder a esta función. Solo supervisores pueden cerrar reclamos.');
            }

            $reclamoModel = new ReclamoModel();
            
            // Obtener reclamos completados que no han sido cerrados
            // Ordenados de menor a mayor por municipalidad_id (ID)
            $reclamos = $reclamoModel
                ->where('municipalidad_estado', 'Completado')
                ->where('cerrado', 0)
                ->orderBy('CAST(municipalidad_id AS UNSIGNED)', 'ASC')
                ->findAll();

            return $this->respond([
                'success' => true,
                'reclamos' => $reclamos,
                'total' => count($reclamos)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al obtener reclamos completados: ' . $e->getMessage());
            return $this->failServerError('Error al obtener los reclamos completados.');
        }
    }

    /**
     * Cierra uno o varios reclamos (marca cerrado = 1 y registra fecha_cierre)
     * Solo accesible para supervisores (rol = 2)
     */
    public function cerrarReclamos()
    {
        try {
            // Verificar permisos de supervisor
            if (!$this->validarPermisoSupervisor()) {
                return $this->failUnauthorized('No tiene permisos para cerrar reclamos. Solo supervisores pueden realizar esta acción.');
            }

            $data = $this->request->getJSON(true);

            // Validar que se envíen IDs de reclamos
            if (empty($data['reclamos_ids']) || !is_array($data['reclamos_ids'])) {
                return $this->failValidationErrors('Debe proporcionar al menos un ID de reclamo para cerrar.');
            }

            $reclamoModel = new ReclamoModel();
            $historialModel = new Historial_reclamoModel();
            $fechaCierre = date('Y-m-d H:i:s');
            $usuarioId = session()->get('user_id');

            $cerrados = 0;
            $errores = 0;
            $reclamosNoCerrados = [];

            foreach ($data['reclamos_ids'] as $reclamoId) {
                try {
                    // Obtener el reclamo
                    $reclamo = $reclamoModel->find($reclamoId);

                    if (!$reclamo) {
                        $reclamosNoCerrados[] = "Reclamo ID {$reclamoId}: No encontrado";
                        $errores++;
                        continue;
                    }

                    // Verificar que el reclamo esté en estado "Completado"
                    if ($reclamo['municipalidad_estado'] !== 'Completado') {
                        $reclamosNoCerrados[] = "Reclamo {$reclamo['municipalidad_id']}: No está en estado Completado (Estado actual: {$reclamo['municipalidad_estado']})";
                        $errores++;
                        continue;
                    }

                    // Verificar que no esté ya cerrado
                    if ($reclamo['cerrado'] == 1) {
                        $reclamosNoCerrados[] = "Reclamo {$reclamo['municipalidad_id']}: Ya está cerrado";
                        $errores++;
                        continue;
                    }

                    // Marcar como cerrado
                    $actualizado = $reclamoModel->update($reclamoId, [
                        'cerrado' => 1,
                        'fecha_cierre' => $fechaCierre
                    ]);

                    if ($actualizado) {
                        // Registrar en el historial
                        $this->registrarCierreEnHistorial(
                            $reclamo['municipalidad_id'],
                            $usuarioId,
                            $fechaCierre
                        );
                        $cerrados++;
                    } else {
                        $reclamosNoCerrados[] = "Reclamo {$reclamo['municipalidad_id']}: Error al actualizar";
                        $errores++;
                    }

                } catch (\Exception $e) {
                    log_message('error', "Error al cerrar reclamo ID {$reclamoId}: " . $e->getMessage());
                    $reclamosNoCerrados[] = "Reclamo ID {$reclamoId}: Error interno";
                    $errores++;
                }
            }

            // Preparar respuesta
            $respuesta = [
                'success' => $cerrados > 0,
                'cerrados' => $cerrados,
                'errores' => $errores,
                'total_procesados' => count($data['reclamos_ids']),
                'fecha_cierre' => $fechaCierre
            ];

            if (count($reclamosNoCerrados) > 0) {
                $respuesta['detalles_errores'] = $reclamosNoCerrados;
            }

            if ($cerrados > 0) {
                return $this->respond($respuesta);
            } else {
                return $this->failValidationErrors('No se pudo cerrar ningún reclamo.', $respuesta);
            }

        } catch (\Exception $e) {
            log_message('error', 'Error en cerrarReclamos: ' . $e->getMessage());
            return $this->failServerError('Error al cerrar los reclamos.');
        }
    }

    /**
     * Valida que el usuario actual sea supervisor (rol = 2 o rol = 1 admin)
     * @return bool
     */
    private function validarPermisoSupervisor()
    {
        $userRole = session()->get('role');
        // Permitir a supervisores (rol 2) y administradores (rol 1)
        return in_array($userRole, ['1', '2']);
    }

    /**
     * Registra el cierre de un reclamo en el historial
     */
    private function registrarCierreEnHistorial($nroReclamo, $usuarioId, $fechaCierre)
    {
        try {
            $historialModel = new Historial_reclamoModel();

            $datosHistorial = [
                'nro_reclamo' => $nroReclamo,
                'estado_anterior' => 'Completado',
                'estado_actual' => 'Cerrado',
                'observacion' => 'Reclamo cerrado formalmente por el supervisor',
                'usuario_id' => $usuarioId ?? 0,
                'fecha_cambio' => $fechaCierre
            ];

            $historialModel->insert($datosHistorial);
            log_message('info', "Cierre registrado en historial para reclamo: {$nroReclamo}");

        } catch (\Exception $e) {
            log_message('error', 'Error al registrar cierre en historial: ' . $e->getMessage());
        }
    }
}

