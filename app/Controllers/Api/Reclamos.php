<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ReclamoModel;
use App\Models\Historial_reclamoModel;
use App\Models\DireccionModel;

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
}
