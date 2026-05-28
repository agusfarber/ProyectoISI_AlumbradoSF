<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ReclamoModel;
use App\Models\Historial_reclamoModel;
use App\Models\Token103Model;

class CierreReclamos extends ResourceController
{
    protected $modelName = 'App\Models\ReclamoModel';
    protected $format = 'json';
    private $apiExternaUrl = 'https://reclamos.sanfrancisco.gov.ar/api/3.0/reclamos/';

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
     * Obtiene todos los reclamos cerrados (cerrado = 1)
     * Solo accesible para supervisores (rol = 2)
     */
    public function obtenerReclamosCerrados()
    {
        try {
            // Verificar permisos de supervisor
            if (!$this->validarPermisoSupervisor()) {
                return $this->failUnauthorized('No tiene permisos para acceder a esta función. Solo supervisores pueden ver reclamos cerrados.');
            }

            $reclamoModel = new ReclamoModel();
            
            // Obtener reclamos cerrados
            // Ordenados de menor a mayor por municipalidad_id (ID)
            $reclamos = $reclamoModel
                ->where('cerrado', 1)
                ->orderBy('CAST(municipalidad_id AS UNSIGNED)', 'ASC')
                ->findAll();

            return $this->respond([
                'success' => true,
                'reclamos' => $reclamos,
                'total' => count($reclamos)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al obtener reclamos cerrados: ' . $e->getMessage());
            return $this->failServerError('Error al obtener los reclamos cerrados.');
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
            $reclamosEnviadosExternos = [];
            $reclamosNoEnviadosExternos = [];

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

                    // PRIMERO: Enviar cierre al sistema externo 103
                    // Solo si se envía exitosamente, se registra el cierre en la BD local
                    $envioExterno = $this->enviarCierreASistema103($reclamo['municipalidad_id']);
                    
                    if ($envioExterno['success']) {
                        // Si el envío fue exitoso, entonces marcar como cerrado en BD local
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
                            
                            $reclamosEnviadosExternos[] = $reclamo['municipalidad_id'];
                            $cerrados++;
                            
                            log_message('info', "Reclamo {$reclamo['municipalidad_id']} cerrado exitosamente en sistema 103 y registrado en BD local");
                        } else {
                            $reclamosNoCerrados[] = "Reclamo {$reclamo['municipalidad_id']}: Error al actualizar en BD local (aunque se envió al sistema 103)";
                            $errores++;
                            log_message('error', "Reclamo {$reclamo['municipalidad_id']} se envió al sistema 103 pero falló al actualizar en BD local");
                        }
                    } else {
                        // Si el envío falló, NO se marca como cerrado en BD local
                        $reclamosNoEnviadosExternos[] = [
                            'id' => $reclamo['municipalidad_id'],
                            'error' => $envioExterno['error'] ?? 'Error desconocido'
                        ];
                        $errores++;
                        log_message('warning', "Reclamo {$reclamo['municipalidad_id']} NO se cerró porque falló el envío al sistema 103: " . ($envioExterno['error'] ?? 'Error desconocido'));
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
                'fecha_cierre' => $fechaCierre,
                'enviados_sistema103' => count($reclamosEnviadosExternos),
                'no_enviados_sistema103' => count($reclamosNoEnviadosExternos)
            ];

            if (count($reclamosNoCerrados) > 0) {
                $respuesta['detalles_errores'] = $reclamosNoCerrados;
            }

            if (count($reclamosEnviadosExternos) > 0) {
                $respuesta['reclamos_enviados_externos'] = $reclamosEnviadosExternos;
            }

            if (count($reclamosNoEnviadosExternos) > 0) {
                $respuesta['reclamos_no_enviados_externos'] = $reclamosNoEnviadosExternos;
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

    /**
     * Envía el cierre de un reclamo al sistema externo 103
     * Cambia el estado del reclamo a "CP" (Completado) en el sistema 103
     * 
     * @param string $municipalidadId ID del reclamo en el sistema 103
     * @return array ['success' => bool, 'error' => string|null]
     */
    private function enviarCierreASistema103($municipalidadId)
    {
        try {
            // Obtener credenciales Basic Auth
            $tokenModel = new Token103Model();
            $credenciales = $tokenModel->orderBy('id', 'DESC')->first();

            if (!$credenciales || empty($credenciales['username']) || empty($credenciales['password'])) {
                return [
                    'success' => false,
                    'error' => 'No hay credenciales configuradas para el sistema 103'
                ];
            }

            // Generar token Basic Auth
            $credencialesString = $credenciales['username'] . ':' . $credenciales['password'];
            $tokenBase64 = base64_encode($credencialesString);

            // Construir URL del endpoint (reemplazar :id con el ID del reclamo)
            $url = $this->apiExternaUrl . $municipalidadId . '/';
            
            log_message('info', "Enviando cierre al sistema 103 para reclamo: {$municipalidadId} - URL: {$url}");

            // Preparar el body con el estado "CP" (Completado)
            $body = json_encode(['estado' => 'CP']);

            // Hacer petición PATCH a la API externa usando cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $tokenBase64,
                'Accept: application/json',
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                log_message('error', "Error en petición cURL al enviar cierre: {$error}");
                return [
                    'success' => false,
                    'error' => 'Error al conectar con la API externa: ' . $error
                ];
            }

            if ($httpCode !== 200) {
                log_message('error', "HTTP Code: {$httpCode} - Response: {$response}");
                return [
                    'success' => false,
                    'error' => "Error en la API externa. Código: {$httpCode}"
                ];
            }

            // Decodificar respuesta para verificar
            $responseData = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('warning', "Respuesta del sistema 103 no es JSON válido para reclamo {$municipalidadId}");
            }

            log_message('info', "Cierre enviado exitosamente al sistema 103 para reclamo: {$municipalidadId}");
            
            return [
                'success' => true,
                'error' => null,
                'response' => $responseData
            ];

        } catch (\Exception $e) {
            log_message('error', 'Error al enviar cierre al sistema 103: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ];
        }
    }
}

