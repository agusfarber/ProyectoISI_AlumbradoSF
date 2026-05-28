<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ReclamoModel;
use App\Models\Historial_reclamoModel;
use App\Models\DireccionModel;
use App\Models\MaterialModel;
use App\Models\Material_reclamoModel;
use App\Models\Tipo_materialModel;
use App\Models\Tiempo_reparacionModel;
use App\Models\Tiempo_promedio_motivoModel;
use App\Models\Ruta_reclamoModel;
use App\Models\RutaModel;
use App\Models\CuadrillaOperariosModel;
use App\Models\RutaEjecucionReclamoObservacionModel;
use App\Libraries\RutaEjecucionHistorialService;

class Reclamos extends ResourceController
{
    protected $modelName = 'App\Models\ReclamoModel';
    protected $format = 'json';

    // API Keys para geocodificación
    private $googleMapsApiKey = 'AIzaSyAOCwr8_hWX4aBE2JTHxREP7gUrYLadCgg'; // Reemplazar con tu key real
    private $mapboxApiKey = 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ejJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw';

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

        // Procesar y guardar la dirección del reclamo automáticamente
        if (!empty($data['municipalidad_domicilio']) && !empty($data['municipalidad_numeroDomicilio'])) {
            $this->procesarDireccionReclamo($data['municipalidad_domicilio'], $data['municipalidad_numeroDomicilio']);
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

        $permisoEdicion = $this->validarPermisoEdicionOperario((int)$id);
        if ($permisoEdicion !== true) {
            return $permisoEdicion;
        }

        // Capturar y limpiar observación si se envía
        $observacion = '';
        if (isset($data['observacion'])) {
            $observacion = trim((string) $data['observacion']);
            unset($data['observacion']);
        }
        if ($observacion === '') {
            $observacion = null;
        }


        // Obtener el reclamo actual para comparar estados
        $reclamoActual = $this->model->find($id);
        if (!$reclamoActual) {
            return $this->failNotFound('Reclamo no encontrado.');
        }

        // VALIDAR: Si el reclamo está cerrado (cerrado = 1), no permitir cambiar el estado
        $estaCerrado = isset($reclamoActual['cerrado']) && $reclamoActual['cerrado'] == 1;
        
        // Verificar si hay cambio de estado para registrar en historial
        $estadoAnterior = $reclamoActual['municipalidad_estado'] ?? '';
        $estadoNuevo = array_key_exists('municipalidad_estado', $data)
            ? (string) $data['municipalidad_estado']
            : $estadoAnterior;
        
        // Si el reclamo está cerrado y se intenta cambiar el estado, rechazar la operación
        if ($estaCerrado && $estadoNuevo !== $estadoAnterior) {
            return $this->failForbidden('No se puede cambiar el estado de un reclamo que ya ha sido cerrado formalmente.');
        }

        if ($estadoNuevo === '') {
            $estadoNuevo = $estadoAnterior;
            if (array_key_exists('municipalidad_estado', $data)) {
                unset($data['municipalidad_estado']);
            }
        } else {
            $data['municipalidad_estado'] = $estadoNuevo;
        }
        
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

        $reclamoActualizado = $this->model->find($id);
        $estadoFinal = $reclamoActualizado['municipalidad_estado'] ?? $estadoNuevo;
        $hayCambioEstado = $estadoAnterior !== $estadoFinal;

        // Registrar cambio de estado u observación en historial
        if ($hayCambioEstado || $observacion !== null) {
            $nroReclamo = $reclamoActual['municipalidad_id'] ?? $data['municipalidad_id'] ?? '';
            $this->registrarCambioEstado($nroReclamo, $estadoAnterior, $estadoFinal, $observacion);
        }

        if ($hayCambioEstado) {
            $ejId = RutaEjecucionHistorialService::findActiveEjecucionIdByReclamoId((int) $id);
            if ($ejId !== null) {
                $uid = (int) (session()->get('user_id') ?? 0);
                RutaEjecucionHistorialService::insertEvent(
                    $ejId,
                    RutaEjecucionHistorialService::TIPO_RECLAMO_ESTADO,
                    (int) $id,
                    $uid > 0 ? $uid : null,
                    [
                        'estado_anterior' => $estadoAnterior,
                        'estado_nuevo'    => $estadoFinal,
                    ]
                );
            }
        }

        // Procesar y guardar la dirección del reclamo si cambió o es nueva
        if (!empty($data['municipalidad_domicilio']) && !empty($data['municipalidad_numeroDomicilio'])) {
            // Solo procesar si la dirección cambió
            $direccionCambio = (
                $reclamoActual['municipalidad_domicilio'] !== $data['municipalidad_domicilio'] ||
                $reclamoActual['municipalidad_numeroDomicilio'] !== $data['municipalidad_numeroDomicilio']
            );
            
            if ($direccionCambio) {
                $this->procesarDireccionReclamo($data['municipalidad_domicilio'], $data['municipalidad_numeroDomicilio']);
            }
        }
        
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
     * Registra un evento en el historial de reclamos (cambio de estado y/o observación)
     */
    private function registrarCambioEstado($nroReclamo, $estadoAnterior, $estadoNuevo, $observacion = null)
    {
        try {
            $historialModel = new Historial_reclamoModel();
            
            // Log de depuración para verificar los parámetros recibidos
            log_message('debug', 'Registrando evento historial - NroReclamo: ' . $nroReclamo . ', EstadoAnterior: ' . $estadoAnterior . ', EstadoNuevo: ' . $estadoNuevo . ', Observacion: ' . ($observacion ?? 'N/A'));
            
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
                'observacion' => $observacion,
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

    /**
     * Geocodifica una dirección usando Google Maps API
     * Retorna las coordenadas (latitud, longitud) o null si falla
     */
    private function geocodificarConGoogleMaps($domicilio, $numeroDomicilio)
    {
        try {
            // Construir la dirección completa
            $direccionCompleta = trim($domicilio) . ' ' . trim($numeroDomicilio) . ', San Francisco, Córdoba, Argentina';
            
            // URL de la API de Google Maps Geocoding
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
                'address' => $direccionCompleta,
                'key' => $this->googleMapsApiKey,
                'region' => 'ar'
            ]);

            log_message('info', 'Geocodificando con Google Maps: ' . $direccionCompleta);

            // Realizar la petición
            $client = \Config\Services::curlrequest();
            $response = $client->get($url, ['timeout' => 10]);
            
            $data = json_decode($response->getBody(), true);

            // Verificar si la respuesta es válida
            if ($data && $data['status'] === 'OK' && !empty($data['results'][0])) {
                $location = $data['results'][0]['geometry']['location'];
                $lat = $location['lat'];
                $lng = $location['lng'];
                
                log_message('info', "Google Maps - Coordenadas encontradas: Lat {$lat}, Lng {$lng}");
                
                return [
                    'latitud' => $lat,
                    'longitud' => $lng,
                    'fuente' => 'google_maps'
                ];
            }

            log_message('warning', 'Google Maps no encontró resultados para: ' . $direccionCompleta);
            return null;

        } catch (\Exception $e) {
            log_message('error', 'Error en geocodificación con Google Maps: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Geocodifica una dirección usando Mapbox API como fallback
     * Retorna las coordenadas (latitud, longitud) o null si falla
     */
    private function geocodificarConMapbox($domicilio, $numeroDomicilio)
    {
        try {
            // Construir la dirección completa
            $direccionCompleta = trim($domicilio) . ' ' . trim($numeroDomicilio) . ', San Francisco, Córdoba, Argentina';
            
            // URL de la API de Mapbox Geocoding
            $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/" . urlencode($direccionCompleta) . ".json?" . http_build_query([
                'access_token' => $this->mapboxApiKey,
                'country' => 'AR',
                'limit' => 1
            ]);

            log_message('info', 'Geocodificando con Mapbox (fallback): ' . $direccionCompleta);

            // Realizar la petición
            $client = \Config\Services::curlrequest();
            $response = $client->get($url, ['timeout' => 10]);
            
            $data = json_decode($response->getBody(), true);

            // Verificar si la respuesta es válida
            if ($data && !empty($data['features'][0])) {
                $coordinates = $data['features'][0]['center'];
                $lng = $coordinates[0];
                $lat = $coordinates[1];
                
                log_message('info', "Mapbox - Coordenadas encontradas: Lat {$lat}, Lng {$lng}");
                
                return [
                    'latitud' => $lat,
                    'longitud' => $lng,
                    'fuente' => 'mapbox'
                ];
            }

            log_message('warning', 'Mapbox no encontró resultados para: ' . $direccionCompleta);
            return null;

        } catch (\Exception $e) {
            log_message('error', 'Error en geocodificación con Mapbox: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Guarda o actualiza una dirección en la tabla de direcciones
     * Solo guarda si no existe previamente
     */
    private function guardarDireccion($domicilio, $numeroDomicilio, $latitud, $longitud, $fuente = 'google_maps')
    {
        try {
            // Validar que tenemos todos los datos necesarios
            if (empty($domicilio) || empty($numeroDomicilio) || empty($latitud) || empty($longitud)) {
                log_message('debug', 'Dirección incompleta, no se guardará');
                return false;
            }

            $direccionModel = new DireccionModel();
            
            // Normalizar los datos para la búsqueda
            $domicilioNormalizado = strtoupper(trim($domicilio));
            $numeroNormalizado = trim($numeroDomicilio);

            // Verificar si ya existe esta dirección
            $direccionExistente = $direccionModel
                ->where('TRIM(UPPER(domicilio))', $domicilioNormalizado)
                ->where('TRIM(numero_domicilio)', $numeroNormalizado)
                ->first();

            if ($direccionExistente) {
                // Si ya existe, verificar si es personalizada
                if ($direccionExistente['personalizada'] == 1) {
                    log_message('info', "Dirección ya existe y es personalizada, no se actualiza: {$domicilio} {$numeroDomicilio}");
                    return false; // No actualizar direcciones personalizadas
                }
                
                // Si existe pero no es personalizada, no hacer nada (mantener la que ya está)
                log_message('info', "Dirección ya existe en la base de datos: {$domicilio} {$numeroDomicilio}");
                return false;
            }

            // Si no existe, crear nueva dirección con personalizada = 0
            $datosDireccion = [
                'domicilio' => $domicilio,
                'numero_domicilio' => $numeroDomicilio,
                'latitud' => $latitud,
                'longitud' => $longitud,
                'personalizada' => 0 // Marcada como no personalizada (geocodificación automática)
            ];

            $direccionId = $direccionModel->insert($datosDireccion);

            if ($direccionId) {
                log_message('info', "Nueva dirección guardada (fuente: {$fuente}): {$domicilio} {$numeroDomicilio} - Lat: {$latitud}, Lng: {$longitud}");
                return true;
            }

            return false;

        } catch (\Exception $e) {
            log_message('error', 'Error al guardar dirección: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesa y geocodifica una dirección de un reclamo
     * Intenta primero con Google Maps, luego con Mapbox si falla
     */
    private function procesarDireccionReclamo($domicilio, $numeroDomicilio)
    {
        // Validar que tenemos domicilio y número
        if (empty($domicilio) || empty($numeroDomicilio)) {
            log_message('debug', 'Reclamo sin domicilio completo, no se geocodificará');
            return;
        }

        // Intentar geocodificar con Google Maps primero
        $coordenadas = $this->geocodificarConGoogleMaps($domicilio, $numeroDomicilio);

        // Si Google Maps falla, intentar con Mapbox
        if ($coordenadas === null) {
            log_message('info', 'Google Maps falló, intentando con Mapbox...');
            $coordenadas = $this->geocodificarConMapbox($domicilio, $numeroDomicilio);
        }

        // Si se obtuvieron coordenadas, guardar la dirección
        if ($coordenadas !== null) {
            $this->guardarDireccion(
                $domicilio,
                $numeroDomicilio,
                $coordenadas['latitud'],
                $coordenadas['longitud'],
                $coordenadas['fuente']
            );
        } else {
            log_message('warning', "No se pudieron obtener coordenadas para: {$domicilio} {$numeroDomicilio}");
        }
    }

    /**
     * Obtiene materiales filtrados por tipo (o todos si no se especifica tipo)
     */
    public function getMaterialesPorTipo()
    {
        try {
            $tipoId = $this->request->getGet('tipo_id');
            $materialModel = new MaterialModel();
            
            if ($tipoId && $tipoId !== '') {
                $materiales = $materialModel->where('idTipo', $tipoId)->findAll();
            } else {
                $materiales = $materialModel->findAll();
            }
            
            return $this->respond($materiales);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener materiales: ' . $e->getMessage());
            return $this->failServerError('Error al obtener los materiales.');
        }
    }

    /**
     * Guarda un material utilizado en un reclamo
     */
    public function guardarMaterialReclamo($reclamoId = null)
    {
        try {
            if (!$reclamoId) {
                return $this->failValidationErrors('ID de reclamo requerido.');
            }

            $permisoEdicion = $this->validarPermisoEdicionOperario((int)$reclamoId);
            if ($permisoEdicion !== true) {
                return $permisoEdicion;
            }

            // Verificar que el reclamo existe
            $reclamo = $this->model->find($reclamoId);
            if (!$reclamo) {
                return $this->failNotFound('Reclamo no encontrado.');
            }

            $data = $this->request->getJSON(true);

            // Validar datos obligatorios - solo material_id es obligatorio
            if (empty($data['material_id'])) {
                return $this->failValidationErrors('El material es obligatorio.');
            }

            // Obtener el ID del usuario desde la sesión
            $usuarioId = session()->get('user_id');
            if (!$usuarioId) {
                $usuarioId = 0; // Sistema o no especificado
            }

            $materialReclamoModel = new Material_reclamoModel();
            
            // La cantidad es opcional - si no se proporciona o es <= 0, se guarda como null
            $cantidad = null;
            if (isset($data['cantidad']) && $data['cantidad'] !== '' && $data['cantidad'] !== null) {
                $cantidadValor = (int) $data['cantidad'];
                if ($cantidadValor > 0) {
                    $cantidad = $cantidadValor;
                }
            }
            
            $datosMaterialReclamo = [
                'reclamo_id' => $reclamoId,
                'material_id' => (int) $data['material_id'],
                'cantidad' => $cantidad,
                'observacion' => isset($data['observacion']) ? trim((string) $data['observacion']) : null,
                'fecha' => date('Y-m-d H:i:s'),
                'usuario_id' => $usuarioId
            ];

            $id = $materialReclamoModel->insert($datosMaterialReclamo);
            
            if ($id === false) {
                return $this->failServerError('Error al guardar el material del reclamo.');
            }

            // Obtener el registro completo con información del material y usuario
            $materialReclamoGuardado = $this->obtenerMaterialReclamoCompleto($id);
            
            return $this->respondCreated($materialReclamoGuardado);
            
        } catch (\Exception $e) {
            log_message('error', 'Error al guardar material de reclamo: ' . $e->getMessage());
            return $this->failServerError('Error al guardar el material del reclamo.');
        }
    }

    /**
     * Obtiene el historial de materiales utilizados en un reclamo
     */
    public function getMaterialesReclamo($reclamoId = null)
    {
        try {
            if (!$reclamoId) {
                return $this->failValidationErrors('ID de reclamo requerido.');
            }

            // Verificar que el reclamo existe
            $reclamo = $this->model->find($reclamoId);
            if (!$reclamo) {
                return $this->failNotFound('Reclamo no encontrado.');
            }

            $db = \Config\Database::connect();
            $materialReclamoModel = new Material_reclamoModel();
            
            // Obtener el historial con información del material y usuario
            $query = $db->table('material_reclamo mr')
                        ->select('mr.*, m.nombre as material_nombre, m.cantidad as material_cantidad_stock, tm.nombre as tipo_material_nombre, u.nombre as usuario_nombre')
                        ->join('material m', 'm.id = mr.material_id', 'left')
                        ->join('tipo_material tm', 'tm.id = m.idTipo', 'left')
                        ->join('usuario u', 'u.id = mr.usuario_id', 'left')
                        ->where('mr.reclamo_id', $reclamoId)
                        ->orderBy('mr.fecha', 'DESC')
                        ->get();
            
            $historial = $query->getResultArray();
            
            return $this->respond($historial);
            
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener materiales del reclamo: ' . $e->getMessage());
            return $this->failServerError('Error al obtener los materiales del reclamo.');
        }
    }

    /**
     * Obtiene el detalle completo de un material_reclamo específico
     */
    public function getDetalleMaterialReclamo($materialReclamoId = null)
    {
        try {
            if (!$materialReclamoId) {
                return $this->failValidationErrors('ID de material_reclamo requerido.');
            }

            $materialReclamo = $this->obtenerMaterialReclamoCompleto($materialReclamoId);
            
            if (!$materialReclamo) {
                return $this->failNotFound('Registro de material no encontrado.');
            }
            
            return $this->respond($materialReclamo);
            
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener detalle de material_reclamo: ' . $e->getMessage());
            return $this->failServerError('Error al obtener el detalle del material.');
        }
    }

    /**
     * Método auxiliar para obtener un material_reclamo completo con información relacionada
     */
    private function obtenerMaterialReclamoCompleto($materialReclamoId)
    {
        $db = \Config\Database::connect();
        
        $query = $db->table('material_reclamo mr')
                    ->select('mr.*, m.nombre as material_nombre, m.cantidad as material_cantidad_stock, tm.nombre as tipo_material_nombre, u.nombre as usuario_nombre, r.municipalidad_id as reclamo_municipalidad_id')
                    ->join('material m', 'm.id = mr.material_id', 'left')
                    ->join('tipo_material tm', 'tm.id = m.idTipo', 'left')
                    ->join('usuario u', 'u.id = mr.usuario_id', 'left')
                    ->join('reclamo r', 'r.id = mr.reclamo_id', 'left')
                    ->where('mr.id', $materialReclamoId)
                    ->get();
        
        return $query->getRowArray();
    }

    /**
     * Guarda el tiempo de reparación registrado por un operario
     */
    private function guardarTiempoReparacion($reclamoId, $motivoReclamo, $tiempoMinutos)
    {
        try {
            $tiempoReparacionModel = new Tiempo_reparacionModel();
            
            // Obtener el ID del usuario desde la sesión
            $usuarioId = session()->get('user_id');
            if (!$usuarioId) {
                $usuarioId = 0; // Sistema o no especificado
            }

            $datosTiempo = [
                'reclamo_id' => $reclamoId,
                'motivo_reclamo' => $motivoReclamo,
                'tiempo_minutos' => $tiempoMinutos,
                'usuario_id' => $usuarioId,
                'fecha_registro' => date('Y-m-d H:i:s')
            ];

            $tiempoReparacionModel->insert($datosTiempo);
            
            log_message('info', "Tiempo de reparación guardado: Reclamo ID {$reclamoId}, Motivo: {$motivoReclamo}, Tiempo: {$tiempoMinutos} minutos");
            
        } catch (\Exception $e) {
            log_message('error', 'Error al guardar tiempo de reparación: ' . $e->getMessage());
        }
    }

    /**
     * Recalcula el promedio de tiempo de reparación para un motivo específico
     */
    private function recalcularPromedioMotivo($motivo, $nuevoTiempoMinutos)
    {
        try {
            $promedioModel = new Tiempo_promedio_motivoModel();
            
            // Buscar si ya existe un registro para este motivo
            $promedioExistente = $promedioModel->where('motivo', $motivo)->first();
            
            if ($promedioExistente) {
                // Actualizar el promedio existente
                $nuevaCantidad = $promedioExistente['cantidad_registros'] + 1;
                $nuevoTiempoTotal = $promedioExistente['tiempo_total_minutos'] + $nuevoTiempoMinutos;
                $nuevoPromedio = $nuevoTiempoTotal / $nuevaCantidad;
                
                $promedioModel->update($promedioExistente['id'], [
                    'tiempo_promedio_minutos' => round($nuevoPromedio, 2),
                    'cantidad_registros' => $nuevaCantidad,
                    'tiempo_total_minutos' => $nuevoTiempoTotal,
                    'fecha_actualizacion' => date('Y-m-d H:i:s')
                ]);
                
                log_message('info', "Promedio actualizado para motivo '{$motivo}': {$nuevoPromedio} minutos (de {$promedioExistente['cantidad_registros']} registros)");
            } else {
                // Crear nuevo registro con el primer tiempo
                $promedioModel->insert([
                    'motivo' => $motivo,
                    'tiempo_promedio_minutos' => $nuevoTiempoMinutos,
                    'cantidad_registros' => 1,
                    'tiempo_total_minutos' => $nuevoTiempoMinutos,
                    'tiempo_default_minutos' => 15, // Valor por defecto
                    'fecha_actualizacion' => date('Y-m-d H:i:s')
                ]);
                
                log_message('info', "Nuevo promedio creado para motivo '{$motivo}': {$nuevoTiempoMinutos} minutos (primer registro)");
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Error al recalcular promedio de motivo: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene el tiempo de reparación registrado para un reclamo
     */
    public function getTiempoReparacion($reclamoId = null)
    {
        try {
            if (!$reclamoId) {
                return $this->failValidationErrors('ID de reclamo requerido.');
            }

            // Verificar que el reclamo existe
            $reclamo = $this->model->find($reclamoId);
            if (!$reclamo) {
                return $this->failNotFound('Reclamo no encontrado.');
            }

            $tiempoReparacionModel = new Tiempo_reparacionModel();
            $db = \Config\Database::connect();
            
            // Obtener el tiempo de reparación con el nombre del usuario
            $query = $db->table('tiempo_reparacion tr')
                        ->select('tr.*, u.nombre as usuario_nombre')
                        ->join('usuario u', 'u.id = tr.usuario_id', 'left')
                        ->where('tr.reclamo_id', $reclamoId)
                        ->orderBy('tr.fecha_registro', 'DESC')
                        ->limit(1)
                        ->get();
            
            $tiempoReparacion = $query->getRowArray();
            
            if (!$tiempoReparacion) {
                return $this->respond(null);
            }
            
            return $this->respond($tiempoReparacion);
            
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener tiempo de reparación: ' . $e->getMessage());
            return $this->failServerError('Error al obtener el tiempo de reparación del reclamo.');
        }
    }

    /**
     * Guarda o actualiza el tiempo de reparación de un reclamo
     */
    public function guardarTiempoReparacionEndpoint($reclamoId = null)
    {
        try {
            if (!$reclamoId) {
                return $this->failValidationErrors('ID de reclamo requerido.');
            }

            $permisoEdicion = $this->validarPermisoEdicionOperario((int)$reclamoId);
            if ($permisoEdicion !== true) {
                return $permisoEdicion;
            }

            $data = $this->request->getJSON(true);

            // Validar datos obligatorios
            if (empty($data['tiempo_reparacion_minutos'])) {
                return $this->failValidationErrors('El tiempo de reparación es obligatorio.');
            }

            $tiempoMinutos = (int) $data['tiempo_reparacion_minutos'];
            if ($tiempoMinutos <= 0) {
                return $this->failValidationErrors('El tiempo de reparación debe ser mayor a 0.');
            }

            // Verificar que el reclamo existe
            $reclamo = $this->model->find($reclamoId);
            if (!$reclamo) {
                return $this->failNotFound('Reclamo no encontrado.');
            }

            $motivoReclamo = $reclamo['municipalidad_motivo'] ?? '';
            if (empty($motivoReclamo)) {
                return $this->failValidationErrors('El reclamo no tiene motivo asociado.');
            }

            // Verificar si ya existe un tiempo registrado para este reclamo
            $tiempoReparacionModel = new Tiempo_reparacionModel();
            $tiempoExistente = $tiempoReparacionModel->where('reclamo_id', $reclamoId)->first();

            if ($tiempoExistente) {
                // Actualizar el tiempo existente
                $tiempoAnterior = $tiempoExistente['tiempo_minutos'];
                $motivoAnterior = $tiempoExistente['motivo_reclamo'] ?? '';
                
                // Si el motivo cambió, ajustar ambos promedios
                if (!empty($motivoAnterior) && $motivoAnterior !== $motivoReclamo) {
                    // Restar el tiempo anterior del motivo anterior (reduciendo cantidad_registros también)
                    $this->restarTiempoDelPromedio($motivoAnterior, $tiempoAnterior);
                    
                    // Agregar el nuevo tiempo al motivo nuevo (como si fuera nuevo)
                    $this->recalcularPromedioMotivo($motivoReclamo, $tiempoMinutos);
                    
                    log_message('info', "Tiempo de reparación actualizado con cambio de motivo: Reclamo ID {$reclamoId}, Motivo anterior: {$motivoAnterior}, Motivo nuevo: {$motivoReclamo}");
                } else {
                    // Solo ajustar el promedio del mismo motivo (mantener cantidad_registros igual)
                    $diferenciaTiempo = $tiempoMinutos - $tiempoAnterior;
                    $this->recalcularPromedioMotivoConDiferencia($motivoReclamo, $diferenciaTiempo);
                }
                
                // Actualizar el registro
                $tiempoReparacionModel->update($tiempoExistente['id'], [
                    'tiempo_minutos' => $tiempoMinutos,
                    'motivo_reclamo' => $motivoReclamo,
                    'fecha_registro' => date('Y-m-d H:i:s'),
                    'usuario_id' => session()->get('user_id') ?? 0
                ]);
                
                log_message('info', "Tiempo de reparación actualizado: Reclamo ID {$reclamoId}, Tiempo anterior: {$tiempoAnterior}, Tiempo nuevo: {$tiempoMinutos}");
            } else {
                // Crear nuevo registro
                $this->guardarTiempoReparacion($reclamoId, $motivoReclamo, $tiempoMinutos);
                $this->recalcularPromedioMotivo($motivoReclamo, $tiempoMinutos);
            }

            // Obtener el tiempo actualizado con el nombre del usuario
            $db = \Config\Database::connect();
            $query = $db->table('tiempo_reparacion tr')
                        ->select('tr.*, u.nombre as usuario_nombre')
                        ->join('usuario u', 'u.id = tr.usuario_id', 'left')
                        ->where('tr.reclamo_id', $reclamoId)
                        ->orderBy('tr.fecha_registro', 'DESC')
                        ->limit(1)
                        ->get();
            
            $tiempoActualizado = $query->getRowArray();
            
            return $this->respond($tiempoActualizado);
            
        } catch (\Exception $e) {
            log_message('error', 'Error al guardar tiempo de reparación: ' . $e->getMessage());
            return $this->failServerError('Error al guardar el tiempo de reparación.');
        }
    }

    /**
     * Recalcula el promedio de tiempo de reparación ajustando por diferencia (para actualizaciones)
     * Solo ajusta el tiempo total, mantiene cantidad_registros igual
     */
    private function recalcularPromedioMotivoConDiferencia($motivo, $diferenciaTiempo)
    {
        try {
            $promedioModel = new Tiempo_promedio_motivoModel();
            
            // Buscar el promedio existente
            $promedioExistente = $promedioModel->where('motivo', $motivo)->first();
            
            if ($promedioExistente) {
                // Ajustar el tiempo total y recalcular el promedio (cantidad_registros se mantiene igual)
                $nuevoTiempoTotal = $promedioExistente['tiempo_total_minutos'] + $diferenciaTiempo;
                
                // Asegurar que el tiempo total no sea negativo
                if ($nuevoTiempoTotal < 0) {
                    $nuevoTiempoTotal = 0;
                }
                
                $nuevoPromedio = $promedioExistente['cantidad_registros'] > 0 
                    ? $nuevoTiempoTotal / $promedioExistente['cantidad_registros'] 
                    : 0;
                
                $promedioModel->update($promedioExistente['id'], [
                    'tiempo_promedio_minutos' => round($nuevoPromedio, 2),
                    'tiempo_total_minutos' => $nuevoTiempoTotal,
                    'fecha_actualizacion' => date('Y-m-d H:i:s')
                ]);
                
                log_message('info', "Promedio actualizado (ajuste) para motivo '{$motivo}': {$nuevoPromedio} minutos");
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Error al recalcular promedio con diferencia: ' . $e->getMessage());
        }
    }

    /**
     * Resta un tiempo completo del promedio de un motivo (usado cuando cambia el motivo)
     * Reduce tanto el tiempo_total como la cantidad_registros
     */
    private function restarTiempoDelPromedio($motivo, $tiempoMinutos)
    {
        try {
            $promedioModel = new Tiempo_promedio_motivoModel();
            
            // Buscar el promedio existente
            $promedioExistente = $promedioModel->where('motivo', $motivo)->first();
            
            if ($promedioExistente && $promedioExistente['cantidad_registros'] > 0) {
                // Reducir el tiempo total y la cantidad de registros
                $nuevoTiempoTotal = $promedioExistente['tiempo_total_minutos'] - $tiempoMinutos;
                $nuevaCantidad = $promedioExistente['cantidad_registros'] - 1;
                
                // Asegurar que no sean negativos
                if ($nuevoTiempoTotal < 0) {
                    $nuevoTiempoTotal = 0;
                }
                if ($nuevaCantidad < 0) {
                    $nuevaCantidad = 0;
                }
                
                // Recalcular el promedio
                $nuevoPromedio = $nuevaCantidad > 0 ? $nuevoTiempoTotal / $nuevaCantidad : 0;
                
                $promedioModel->update($promedioExistente['id'], [
                    'tiempo_promedio_minutos' => round($nuevoPromedio, 2),
                    'cantidad_registros' => $nuevaCantidad,
                    'tiempo_total_minutos' => $nuevoTiempoTotal,
                    'fecha_actualizacion' => date('Y-m-d H:i:s')
                ]);
                
                log_message('info', "Tiempo restado del promedio para motivo '{$motivo}': {$tiempoMinutos} minutos removidos, nuevo promedio: {$nuevoPromedio} minutos");
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Error al restar tiempo del promedio: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene los tiempos promedio de reparación por motivo (para consulta de supervisores)
     */
    public function getTiemposPromedio()
    {
        try {
            $promedioModel = new Tiempo_promedio_motivoModel();
            $promedios = $promedioModel->orderBy('motivo', 'ASC')->findAll();
            
            return $this->respond($promedios);
            
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener tiempos promedio: ' . $e->getMessage());
            return $this->failServerError('Error al obtener los tiempos promedio de reparación.');
        }
    }

    /**
     * Lista observaciones de obra de un reclamo (todas las ejecuciones / hojas de ruta).
     * ruta_ejecucion_id valida el contexto de la hoja en curso; el listado no se limita a esa ejecución.
     */
    public function getEjecucionObservacionesReclamo($reclamoId = null)
    {
        try {
            if (! $reclamoId) {
                return $this->failValidationErrors('ID de reclamo requerido.');
            }

            $permisoEdicion = $this->validarPermisoEdicionOperario((int) $reclamoId);
            if ($permisoEdicion !== true) {
                return $permisoEdicion;
            }

            $reclamoId = (int) $reclamoId;
            $reclamo   = $this->model->find($reclamoId);
            if (! $reclamo) {
                return $this->failNotFound('Reclamo no encontrado.');
            }

            $rutaEjecucionId = $this->request->getGet('ruta_ejecucion_id');
            if ($rutaEjecucionId === null || $rutaEjecucionId === '') {
                return $this->failValidationErrors('Parámetro ruta_ejecucion_id requerido.');
            }

            $ctx = $this->resolverContextoObservacionEjecucionReclamo($reclamoId, (int) $rutaEjecucionId);
            if ($ctx === null) {
                return $this->failForbidden('No se encontró la ejecución indicada para este reclamo o no coincide con la hoja en curso.');
            }

            $db = \Config\Database::connect();
            $rows = $db->table('ruta_ejecucion_reclamo_observacion o')
                ->select('o.id, o.ruta_ejecucion_id, o.ruta_id, o.reclamo_id, o.texto, o.created_at, o.usuario_id, u.nombre as usuario_nombre, r.nombre as ruta_nombre, r.color as ruta_color')
                ->join('usuario u', 'u.id = o.usuario_id', 'left')
                ->join('ruta r', 'r.id = o.ruta_id', 'left')
                ->where('o.reclamo_id', $reclamoId)
                ->orderBy('o.created_at', 'DESC')
                ->orderBy('o.id', 'DESC')
                ->get()
                ->getResultArray();

            return $this->respond($rows);
        } catch (\Exception $e) {
            log_message('error', 'Error al listar observaciones de ejecución: ' . $e->getMessage());

            return $this->failServerError('Error al obtener las observaciones.');
        }
    }

    /**
     * Registra una observación sobre el reclamo durante la ejecución actual de la hoja de ruta.
     */
    public function guardarEjecucionObservacionReclamo($reclamoId = null)
    {
        try {
            if (! $reclamoId) {
                return $this->failValidationErrors('ID de reclamo requerido.');
            }

            $permisoEdicion = $this->validarPermisoEdicionOperario((int) $reclamoId);
            if ($permisoEdicion !== true) {
                return $permisoEdicion;
            }

            $reclamoId = (int) $reclamoId;
            $reclamo   = $this->model->find($reclamoId);
            if (! $reclamo) {
                return $this->failNotFound('Reclamo no encontrado.');
            }

            $data                  = $this->request->getJSON(true) ?? [];
            $rutaEjecucionIdCliente = isset($data['ruta_ejecucion_id']) ? (int) $data['ruta_ejecucion_id'] : 0;
            if ($rutaEjecucionIdCliente < 1) {
                return $this->failValidationErrors('ruta_ejecucion_id es obligatorio.');
            }

            $texto = isset($data['texto']) ? trim((string) $data['texto']) : '';
            if ($texto === '') {
                return $this->failValidationErrors('El texto de la observación es obligatorio.');
            }

            $len = function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
            if ($len > 4000) {
                return $this->failValidationErrors('La observación no puede superar los 4000 caracteres.');
            }

            $ctx = $this->resolverContextoObservacionEjecucionReclamo($reclamoId, $rutaEjecucionIdCliente);
            if ($ctx === null) {
                return $this->failForbidden('La ejecución no es válida para esta hoja y reclamo, o la ruta no está en ejecución.');
            }

            $usuarioId = session()->get('user_id');
            if (! $usuarioId) {
                $usuarioId = 0;
            }

            $obsModel = new RutaEjecucionReclamoObservacionModel();
            $ahora    = date('Y-m-d H:i:s');
            $obsModel->insert([
                'ruta_ejecucion_id' => $ctx['ruta_ejecucion_id'],
                'ruta_id'           => $ctx['ruta_id'],
                'reclamo_id'        => $reclamoId,
                'texto'             => $texto,
                'usuario_id'        => (int) $usuarioId > 0 ? (int) $usuarioId : null,
                'created_at'        => $ahora,
            ]);
            $id = (int) $obsModel->getInsertID();
            if ($id < 1) {
                return $this->failServerError('Error al guardar la observación.');
            }

            $db  = \Config\Database::connect();
            $row = $db->table('ruta_ejecucion_reclamo_observacion o')
                ->select('o.id, o.ruta_ejecucion_id, o.ruta_id, o.reclamo_id, o.texto, o.created_at, o.usuario_id, u.nombre as usuario_nombre, r.nombre as ruta_nombre, r.color as ruta_color')
                ->join('usuario u', 'u.id = o.usuario_id', 'left')
                ->join('ruta r', 'r.id = o.ruta_id', 'left')
                ->where('o.id', $id)
                ->get()
                ->getRowArray();

            return $this->respondCreated($row ?? ['id' => $id]);
        } catch (\Exception $e) {
            log_message('error', 'Error al guardar observación de ejecución: ' . $e->getMessage());

            return $this->failServerError('Error al guardar la observación.');
        }
    }

    /**
     * @return array{ruta_ejecucion_id: int, ruta_id: int, reclamo_id: int}|null
     */
    private function resolverContextoObservacionEjecucionReclamo(int $reclamoId, int $rutaEjecucionIdCliente): ?array
    {
        $link = RutaEjecucionHistorialService::findRutaReclamoLinkRutaAsignada($reclamoId);
        if (! $link) {
            return null;
        }

        $rutaId = (int) $link['ruta_id'];
        $activa = RutaEjecucionHistorialService::findActiveEjecucionIdByRutaId($rutaId);
        if (! $activa || $activa !== $rutaEjecucionIdCliente) {
            return null;
        }

        $db = \Config\Database::connect();
        $ej = $db->table('ruta_ejecucion')
            ->where('id', $activa)
            ->where('fin_at', null)
            ->get()
            ->getRowArray();
        if (! $ej || (int) $ej['ruta_id'] !== $rutaId) {
            return null;
        }

        return [
            'ruta_ejecucion_id' => $activa,
            'ruta_id'           => $rutaId,
            'reclamo_id'        => $reclamoId,
        ];
    }

    /**
     * Si el usuario es operario, solo el jefe de su cuadrilla puede editar acciones.
     */
    private function validarPermisoEdicionOperario(int $reclamoId)
    {
        $session = session();
        $userId = (int)($session->get('user_id') ?? 0);
        $role = (string)($session->get('role') ?? '');

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        // Supervisores y administradores mantienen permisos de edición.
        if ($role !== '3') {
            return true;
        }

        $rutaModel = new RutaModel();
        $cuadrillaOperariosModel = new CuadrillaOperariosModel();

        $vinculoRuta = RutaEjecucionHistorialService::findRutaReclamoLinkRutaAsignada($reclamoId);
        if (! $vinculoRuta) {
            return $this->failForbidden('El reclamo no está en ninguna hoja de ruta asignada a cuadrilla.');
        }

        $ruta = $rutaModel->find($vinculoRuta['ruta_id']);
        if (!$ruta || (int)($ruta['asignada'] ?? 0) !== 1 || empty($ruta['cuadrilla_id'])) {
            return $this->failForbidden('La hoja de ruta del reclamo no está asignada.');
        }

        $db = \Config\Database::connect();
        $tieneEstadoEjecucion = $db->fieldExists('estado_ejecucion', 'ruta');
        $estadoRuta = $tieneEstadoEjecucion
            ? ($ruta['estado_ejecucion'] ?? 'asignada')
            : 'asignada';

        if ($estadoRuta !== 'en ejecución') {
            return $this->failForbidden('La hoja de ruta está asignada, pero aún no inició su ejecución.');
        }

        $asignacion = $cuadrillaOperariosModel
            ->where('usuario_id', $userId)
            ->where('cuadrilla_id', $ruta['cuadrilla_id'])
            ->first();

        if (!$asignacion) {
            return $this->failForbidden('No tiene permisos para editar tareas de esta cuadrilla.');
        }

        if ((int)($asignacion['es_jefe'] ?? 0) !== 1) {
            return $this->failForbidden('Solo el jefe de cuadrilla puede editar las tareas de esta hoja de ruta.');
        }

        return true;
    }
}
