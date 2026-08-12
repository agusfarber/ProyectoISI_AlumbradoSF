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
use App\Libraries\ReclamoPrioridadService;

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
        ReclamoPrioridadService::sincronizarPrioridadesMasivas();

        return $this->respond($this->model->findAllActivos());
    }

    public function create()
    {
        $role = (string) (session()->get('role') ?? '');
        if ($role !== '2') {
            return $this->failForbidden('Solo supervisores pueden cargar reclamos desde el formulario.');
        }

        $data = $this->request->getJSON(true);
        if (!is_array($data) || empty($data)) {
            return $this->failValidationErrors('No se recibieron datos.');
        }

        if (empty(trim((string) ($data['municipalidad_motivo'] ?? '')))) {
            return $this->failValidationErrors('El motivo es obligatorio.');
        }
        if (empty(trim((string) ($data['municipalidad_domicilio'] ?? '')))) {
            return $this->failValidationErrors('El domicilio es obligatorio.');
        }
        if (empty(trim((string) ($data['municipalidad_numeroDomicilio'] ?? '')))) {
            return $this->failValidationErrors('El número de domicilio es obligatorio.');
        }

        $ahora = date('Y-m-d H:i:s');
        $payload = [
            'municipalidad_tipo' => 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => trim((string) $data['municipalidad_motivo']),
            'municipalidad_fechaInicio' => !empty($data['municipalidad_fechaInicio'])
                ? $this->formatearFecha($data['municipalidad_fechaInicio'])
                : $ahora,
            'municipalidad_fechaModificacion' => $ahora,
            'municipalidad_recepcion' => isset($data['municipalidad_recepcion']) && $data['municipalidad_recepcion'] !== ''
                ? trim((string) $data['municipalidad_recepcion'])
                : null,
            'municipalidad_estado' => 'Recibido',
            'municipalidad_telefono' => isset($data['municipalidad_telefono']) && $data['municipalidad_telefono'] !== ''
                ? trim((string) $data['municipalidad_telefono'])
                : null,
            'municipalidad_domicilio' => trim((string) $data['municipalidad_domicilio']),
            'municipalidad_numeroDomicilio' => trim((string) $data['municipalidad_numeroDomicilio']),
            'municipalidad_entreCalleUno' => isset($data['municipalidad_entreCalleUno']) && trim((string) $data['municipalidad_entreCalleUno']) !== ''
                ? trim((string) $data['municipalidad_entreCalleUno'])
                : null,
            'municipalidad_entreCalleDos' => isset($data['municipalidad_entreCalleDos']) && trim((string) $data['municipalidad_entreCalleDos']) !== ''
                ? trim((string) $data['municipalidad_entreCalleDos'])
                : null,
            'municipalidad_ciudadano' => isset($data['municipalidad_ciudadano']) && $data['municipalidad_ciudadano'] !== ''
                ? trim((string) $data['municipalidad_ciudadano'])
                : null,
            'municipalidad_descripcion' => isset($data['municipalidad_descripcion']) && $data['municipalidad_descripcion'] !== ''
                ? trim((string) $data['municipalidad_descripcion'])
                : null,
            'origen' => ReclamoModel::ORIGEN_LOCAL,
            'ficha_editada' => 1,
            'excluido_local' => 0,
            'cerrado' => 0,
            // Placeholder hasta tener el PK interno (columna debe ser VARCHAR)
            'municipalidad_id' => 'TMP',
        ];

        $payload['prioridad'] = ReclamoPrioridadService::evaluarPrioridad($payload);

        $reclamoId = $this->model->insert($payload);
        if ($reclamoId === false) {
            return $this->failServerError('Error al guardar reclamo.');
        }

        // ID visible distintivo: L{id} — no choca con IDs numéricos del 103
        $codigoLocal = 'L' . $reclamoId;
        $this->model->update($reclamoId, ['municipalidad_id' => $codigoLocal]);

        if (!empty($payload['municipalidad_domicilio']) && !empty($payload['municipalidad_numeroDomicilio'])) {
            $this->procesarDireccionReclamo(
                $payload['municipalidad_domicilio'],
                $payload['municipalidad_numeroDomicilio']
            );
        }

        return $this->respondCreated($this->model->find($reclamoId));
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

        $reclamoParaPrioridad = array_merge($reclamoActual, $data);
        $data['prioridad']      = ReclamoPrioridadService::evaluarPrioridad($reclamoParaPrioridad);

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

    /**
     * Edición de ficha por supervisor: corrige datos sin tocar estado/cierre/prioridad manual.
     * Marca ficha_editada para que el sync puntual del 103 no pise esas correcciones.
     */
    public function actualizarFicha($id = null)
    {
        $role = (string) (session()->get('role') ?? '');
        if ($role !== '2') {
            return $this->failForbidden('Solo supervisores pueden editar la ficha del reclamo.');
        }

        if (!$id) {
            return $this->failValidationErrors('ID de reclamo requerido.');
        }

        $reclamoActual = $this->model->find($id);
        if (!$reclamoActual) {
            return $this->failNotFound('Reclamo no encontrado.');
        }

        if ((int) ($reclamoActual['excluido_local'] ?? 0) === 1) {
            return $this->failValidationErrors('No se puede editar un reclamo excluido.');
        }

        $data = $this->request->getJSON(true);
        if (!is_array($data) || empty($data)) {
            return $this->failValidationErrors('No se recibieron datos.');
        }

        unset($data['observacion']);

        $camposEditables = [
            'municipalidad_motivo',
            'municipalidad_recepcion',
            'municipalidad_telefono',
            'municipalidad_domicilio',
            'municipalidad_numeroDomicilio',
            'municipalidad_entreCalleUno',
            'municipalidad_entreCalleDos',
            'municipalidad_ciudadano',
            'municipalidad_descripcion',
        ];

        $payload = [];
        foreach ($camposEditables as $campo) {
            if (array_key_exists($campo, $data)) {
                $valor = $data[$campo];
                if (is_string($valor)) {
                    $valor = trim($valor);
                }
                $payload[$campo] = ($valor === '') ? null : $valor;
            }
        }

        if (empty($payload)) {
            return $this->failValidationErrors('No hay cambios para guardar.');
        }

        $fichaFinal = array_merge($reclamoActual, $payload);
        if (empty(trim((string) ($fichaFinal['municipalidad_motivo'] ?? '')))) {
            return $this->failValidationErrors('El motivo es obligatorio.');
        }
        if (empty(trim((string) ($fichaFinal['municipalidad_domicilio'] ?? '')))) {
            return $this->failValidationErrors('El domicilio es obligatorio.');
        }
        if (empty(trim((string) ($fichaFinal['municipalidad_numeroDomicilio'] ?? '')))) {
            return $this->failValidationErrors('El número de domicilio es obligatorio.');
        }

        $payload['municipalidad_tipo'] = 'ALUMBRADO PÚBLICO';
        $payload['municipalidad_fechaModificacion'] = date('Y-m-d H:i:s');
        $payload['ficha_editada'] = 1;

        $reclamoParaPrioridad = array_merge($reclamoActual, $payload);
        $payload['prioridad'] = ReclamoPrioridadService::evaluarPrioridad($reclamoParaPrioridad);

        if ($this->model->update($id, $payload) === false) {
            return $this->failServerError('Error al actualizar la ficha del reclamo.');
        }

        if (
            array_key_exists('municipalidad_domicilio', $payload)
            || array_key_exists('municipalidad_numeroDomicilio', $payload)
        ) {
            $domicilio = $payload['municipalidad_domicilio'] ?? $reclamoActual['municipalidad_domicilio'] ?? null;
            $numero = $payload['municipalidad_numeroDomicilio'] ?? $reclamoActual['municipalidad_numeroDomicilio'] ?? null;
            $direccionCambio = (
                ($payload['municipalidad_domicilio'] ?? $reclamoActual['municipalidad_domicilio']) != ($reclamoActual['municipalidad_domicilio'] ?? null)
                || ($payload['municipalidad_numeroDomicilio'] ?? $reclamoActual['municipalidad_numeroDomicilio']) != ($reclamoActual['municipalidad_numeroDomicilio'] ?? null)
            );
            if ($direccionCambio && !empty($domicilio) && !empty($numero)) {
                $this->procesarDireccionReclamo($domicilio, $numero);
            }
        }

        return $this->respond($this->model->find($id));
    }

    /**
     * Exclusión lógica: no borra el registro (el sync 103 no lo recrea).
     * Solo supervisor. Bloqueado si cerrado, en ejecución o asignado a una ruta.
     */
    public function delete($id = null)
    {
        $role = (string) (session()->get('role') ?? '');
        if ($role !== '2') {
            return $this->failForbidden('Solo supervisores pueden eliminar reclamos.');
        }

        if (!$id) {
            return $this->failValidationErrors('ID de reclamo requerido.');
        }

        $reclamo = $this->model->find($id);
        if (!$reclamo) {
            return $this->failNotFound('Reclamo no encontrado.');
        }

        if ((int) ($reclamo['excluido_local'] ?? 0) === 1) {
            return $this->failValidationErrors('El reclamo ya fue excluido.');
        }

        if ((int) ($reclamo['cerrado'] ?? 0) === 1) {
            return $this->failValidationErrors('No se puede eliminar un reclamo cerrado.');
        }

        $estado = trim((string) ($reclamo['municipalidad_estado'] ?? ''));
        if ($estado === 'En ejecución') {
            return $this->failValidationErrors('No se puede eliminar un reclamo en ejecución.');
        }

        $enRuta = (new Ruta_reclamoModel())->where('reclamo_id', $id)->first();
        if ($enRuta) {
            return $this->failValidationErrors(
                'No se puede eliminar un reclamo que está en una hoja de ruta. Quítelo de la ruta primero.'
            );
        }

        $payload = $this->request->getJSON(true);
        if (!is_array($payload)) {
            $payload = [];
        }
        $observacion = trim((string) ($payload['observacion'] ?? ''));

        $ok = $this->model->update($id, [
            'excluido_local' => 1,
            'excluido_at' => date('Y-m-d H:i:s'),
            'excluido_observacion' => $observacion !== '' ? $observacion : null,
        ]);

        if ($ok === false) {
            return $this->failServerError('Error al excluir el reclamo.');
        }

        $textoHistorial = $observacion !== ''
            ? $observacion
            : 'Reclamo excluido localmente por supervisor.';
        $nroReclamo = $reclamo['municipalidad_id'] ?? '';
        $this->registrarCambioEstado($nroReclamo, $estado, $estado, $textoHistorial);

        return $this->respondDeleted([
            'mensaje' => 'Reclamo excluido con éxito.',
            'id' => (int) $id,
        ]);
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

        $zonaLocal = new \DateTimeZone('America/Argentina/Buenos_Aires');

        // Los inputs datetime-local envían la hora local sin zona: se toma como argentina.
        // Si el string trae zona explícita (Z u offset), DateTime la respeta y se convierte.
        $date = new \DateTime($fecha, $zonaLocal);
        $date->setTimezone($zonaLocal);

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

            // Validar datos obligatorios
            if (empty($data['material_id'])) {
                return $this->failValidationErrors('El material es obligatorio.');
            }

            $cantidadValor = isset($data['cantidad']) ? (int) $data['cantidad'] : 0;
            if ($cantidadValor < 1) {
                return $this->failValidationErrors('La cantidad es obligatoria y debe ser al menos 1.');
            }

            $materialModel = new MaterialModel();
            if (! $materialModel->find((int) $data['material_id'])) {
                return $this->failValidationErrors('El material indicado no existe.');
            }

            // Obtener el ID del usuario desde la sesión
            $usuarioId = session()->get('user_id');
            if (!$usuarioId) {
                $usuarioId = 0; // Sistema o no especificado
            }

            $rutaEjecucionId = RutaEjecucionHistorialService::findActiveEjecucionIdByReclamoId((int) $reclamoId);

            $materialReclamoModel = new Material_reclamoModel();
            
            $datosMaterialReclamo = [
                'reclamo_id' => $reclamoId,
                'ruta_ejecucion_id' => $rutaEjecucionId,
                'material_id' => (int) $data['material_id'],
                'cantidad' => $cantidadValor,
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
     * Obtiene el historial de materiales utilizados en un reclamo.
     * Query opcional: ruta_ejecucion_id | ruta_id para acotar a una jornada/hoja.
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
            
            // Obtener el historial con información del material, usuario y ruta de la ejecución
            $query = $db->table('material_reclamo mr')
                        ->select('mr.*, m.nombre as material_nombre, m.foto as material_foto, m.idTipo as material_tipo_id, tm.nombre as tipo_material_nombre, u.nombre as usuario_nombre, u.foto_perfil as usuario_foto_perfil, ruta.nombre as ruta_nombre, ruta.color as ruta_color')
                        ->join('material m', 'm.id = mr.material_id', 'left')
                        ->join('tipo_material tm', 'tm.id = m.idTipo', 'left')
                        ->join('usuario u', 'u.id = mr.usuario_id', 'left')
                        ->join('ruta_ejecucion re', 're.id = mr.ruta_ejecucion_id', 'left')
                        ->join('ruta', 'ruta.id = re.ruta_id', 'left')
                        ->where('mr.reclamo_id', $reclamoId);

            $rutaEjecucionId = $this->request->getGet('ruta_ejecucion_id');
            $rutaIdConsulta  = $this->request->getGet('ruta_id');
            if ($rutaEjecucionId !== null && $rutaEjecucionId !== '') {
                $this->aplicarFiltroMaterialesPorEjecucion($query, (int) $rutaEjecucionId);
            } elseif ($rutaIdConsulta !== null && $rutaIdConsulta !== '') {
                $this->aplicarFiltroMaterialesPorRuta($query, (int) $rutaIdConsulta);
            }

            $historial = $query->orderBy('mr.fecha', 'DESC')->get()->getResultArray();
            
            return $this->respond($historial);
            
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener materiales del reclamo: ' . $e->getMessage());
            return $this->failServerError('Error al obtener los materiales del reclamo.');
        }
    }

    /**
     * Elimina un registro material_reclamo (solo en obra / con permiso de edición).
     */
    public function eliminarMaterialReclamo($materialReclamoId = null)
    {
        try {
            if (! $materialReclamoId) {
                return $this->failValidationErrors('ID de material_reclamo requerido.');
            }

            $materialReclamoModel = new Material_reclamoModel();
            $existente = $materialReclamoModel->find($materialReclamoId);
            if (! $existente) {
                return $this->failNotFound('Registro de material no encontrado.');
            }

            $permisoEdicion = $this->validarPermisoEdicionOperario((int) $existente['reclamo_id']);
            if ($permisoEdicion !== true) {
                return $permisoEdicion;
            }

            if ($materialReclamoModel->delete($materialReclamoId) === false) {
                return $this->failServerError('Error al eliminar el material del reclamo.');
            }

            return $this->respondDeleted(['id' => (int) $materialReclamoId, 'message' => 'Material eliminado.']);
        } catch (\Exception $e) {
            log_message('error', 'Error al eliminar material_reclamo: ' . $e->getMessage());
            return $this->failServerError('Error al eliminar el material del reclamo.');
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
                    ->select('mr.*, m.nombre as material_nombre, m.foto as material_foto, tm.nombre as tipo_material_nombre, u.nombre as usuario_nombre, u.foto_perfil as usuario_foto_perfil, r.municipalidad_id as reclamo_municipalidad_id, ruta.nombre as ruta_nombre, ruta.color as ruta_color')
                    ->join('material m', 'm.id = mr.material_id', 'left')
                    ->join('tipo_material tm', 'tm.id = m.idTipo', 'left')
                    ->join('usuario u', 'u.id = mr.usuario_id', 'left')
                    ->join('reclamo r', 'r.id = mr.reclamo_id', 'left')
                    ->join('ruta_ejecucion re', 're.id = mr.ruta_ejecucion_id', 'left')
                    ->join('ruta', 'ruta.id = re.ruta_id', 'left')
                    ->where('mr.id', $materialReclamoId)
                    ->get();
        
        return $query->getRowArray();
    }

    /**
     * Filtra materiales de una ejecución concreta (incluye legados sin ruta_ejecucion_id por ventana de fechas).
     */
    private function aplicarFiltroMaterialesPorEjecucion($builder, int $rutaEjecucionId): void
    {
        $db = \Config\Database::connect();
        $ej = $db->table('ruta_ejecucion')->where('id', $rutaEjecucionId)->get()->getRowArray();
        if (! $ej) {
            $builder->where('1 = 0', null, false);
            return;
        }

        $inicio = $ej['inicio_at'] ?? null;
        $fin    = $ej['fin_at'] ?: date('Y-m-d H:i:s');
        if (! $inicio) {
            $builder->where('mr.ruta_ejecucion_id', $rutaEjecucionId);
            return;
        }

        $builder->groupStart()
            ->where('mr.ruta_ejecucion_id', $rutaEjecucionId)
            ->orGroupStart()
                ->where('mr.ruta_ejecucion_id IS NULL', null, false)
                ->where('mr.fecha >=', $inicio)
                ->where('mr.fecha <=', $fin)
            ->groupEnd()
        ->groupEnd();
    }

    /**
     * Filtra materiales asociados a cualquier ejecución de una hoja de ruta.
     */
    private function aplicarFiltroMaterialesPorRuta($builder, int $rutaId): void
    {
        $db = \Config\Database::connect();
        $ejecuciones = $db->table('ruta_ejecucion')
            ->where('ruta_id', $rutaId)
            ->get()
            ->getResultArray();

        if (! $ejecuciones) {
            $builder->where('1 = 0', null, false);
            return;
        }

        $ids = array_values(array_filter(array_map(static fn ($e) => (int) ($e['id'] ?? 0), $ejecuciones)));
        $builder->groupStart();
        if ($ids) {
            $builder->whereIn('mr.ruta_ejecucion_id', $ids);
        } else {
            $builder->where('1 = 0', null, false);
        }

        $builder->orGroupStart()
            ->where('mr.ruta_ejecucion_id IS NULL', null, false)
            ->groupStart();

        $primero = true;
        foreach ($ejecuciones as $ej) {
            $inicio = $ej['inicio_at'] ?? null;
            if (! $inicio) {
                continue;
            }
            $fin = $ej['fin_at'] ?: date('Y-m-d H:i:s');
            if ($primero) {
                $builder->groupStart()
                    ->where('mr.fecha >=', $inicio)
                    ->where('mr.fecha <=', $fin)
                    ->groupEnd();
                $primero = false;
            } else {
                $builder->orGroupStart()
                    ->where('mr.fecha >=', $inicio)
                    ->where('mr.fecha <=', $fin)
                    ->groupEnd();
            }
        }

        if ($primero) {
            $builder->where('1 = 0', null, false);
        }

        $builder->groupEnd()->groupEnd()->groupEnd();
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
     * ruta_ejecucion_id valida el contexto de la hoja en curso (operario).
     * Supervisores/administradores pueden usar ruta_id para consultar el historial completo del reclamo.
     */
    public function getEjecucionObservacionesReclamo($reclamoId = null)
    {
        try {
            if (! $reclamoId) {
                return $this->failValidationErrors('ID de reclamo requerido.');
            }

            $reclamoId = (int) $reclamoId;
            $reclamo   = $this->model->find($reclamoId);
            if (! $reclamo) {
                return $this->failNotFound('Reclamo no encontrado.');
            }

            $permiso = $this->validarAccesoBitacoraEjecucionReclamo($reclamoId);
            if ($permiso !== true) {
                return $permiso;
            }

            $rutaEjecucionId = $this->request->getGet('ruta_ejecucion_id');
            $rutaIdConsulta  = $this->request->getGet('ruta_id');
            $role            = (string) (session()->get('role') ?? '');

            if ($rutaEjecucionId !== null && $rutaEjecucionId !== '') {
                $ctx = $this->resolverContextoObservacionEjecucionReclamo($reclamoId, (int) $rutaEjecucionId);
                if ($ctx === null) {
                    return $this->failForbidden('No se encontró la ejecución indicada para este reclamo o no coincide con la hoja en curso.');
                }
            } elseif ($rutaIdConsulta !== null && $rutaIdConsulta !== '') {
                $link = RutaEjecucionHistorialService::findRutaReclamoLinkRutaAsignada($reclamoId);
                if (! $link || (int) $link['ruta_id'] !== (int) $rutaIdConsulta) {
                    return $this->failForbidden('El reclamo no pertenece a la hoja de ruta indicada.');
                }
                if ($role === '3') {
                    $acceso = $this->validarOperarioCuadrillaRutaReclamo($reclamoId);
                    if ($acceso !== true) {
                        return $acceso;
                    }
                }
            } else {
                return $this->failValidationErrors('Parámetro ruta_ejecucion_id o ruta_id requerido.');
            }

            $db   = \Config\Database::connect();
            $cols = 'o.id, o.ruta_ejecucion_id, o.ruta_id, o.reclamo_id, o.texto, o.created_at, o.usuario_id, u.nombre as usuario_nombre, u.foto_perfil as usuario_foto_perfil, r.nombre as ruta_nombre, r.color as ruta_color';
            if ($db->fieldExists('tipo', 'ruta_ejecucion_reclamo_observacion')) {
                $cols .= ', o.tipo, o.archivo';
            }

            $rows = $db->table('ruta_ejecucion_reclamo_observacion o')
                ->select($cols)
                ->join('usuario u', 'u.id = o.usuario_id', 'left')
                ->join('ruta r', 'r.id = o.ruta_id', 'left')
                ->where('o.reclamo_id', $reclamoId)
                ->orderBy('o.created_at', 'DESC')
                ->orderBy('o.id', 'DESC')
                ->get()
                ->getResultArray();

            $observaciones = $this->enriquecerFilasBitacoraEjecucion($rows);
            $cambiosEstado = $this->obtenerEntradasCambioEstadoBitacoraReclamo($reclamoId);

            return $this->respond($this->fusionarBitacoraEjecucionReclamo($observaciones, $cambiosEstado));
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

            $reclamoId = (int) $reclamoId;
            $reclamo   = $this->model->find($reclamoId);
            if (! $reclamo) {
                return $this->failNotFound('Reclamo no encontrado.');
            }

            $data                   = $this->request->getJSON(true) ?? [];
            $rutaEjecucionIdCliente = isset($data['ruta_ejecucion_id']) ? (int) $data['ruta_ejecucion_id'] : 0;
            if ($rutaEjecucionIdCliente < 1) {
                return $this->failValidationErrors('ruta_ejecucion_id es obligatorio.');
            }

            $ctx = $this->validarRegistroBitacoraEjecucionReclamo($reclamoId, $rutaEjecucionIdCliente);
            if (! is_array($ctx)) {
                return $ctx;
            }

            $texto = isset($data['texto']) ? trim((string) $data['texto']) : '';
            if ($texto === '') {
                return $this->failValidationErrors('El texto de la observación es obligatorio.');
            }

            $len = function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
            if ($len > 4000) {
                return $this->failValidationErrors('La observación no puede superar los 4000 caracteres.');
            }

            $row = $this->insertarEntradaBitacoraEjecucion($ctx, $reclamoId, 'texto', $texto, null);

            return $this->respondCreated($row);
        } catch (\Exception $e) {
            log_message('error', 'Error al guardar observación de ejecución: ' . $e->getMessage());

            return $this->failServerError('Error al guardar la observación.');
        }
    }

    /**
     * Registra una foto en la bitácora de obra del reclamo (multipart: foto, ruta_ejecucion_id, texto opcional).
     */
    public function guardarEjecucionFotoReclamo($reclamoId = null)
    {
        try {
            if (! $reclamoId) {
                return $this->failValidationErrors('ID de reclamo requerido.');
            }

            $reclamoId = (int) $reclamoId;
            $reclamo   = $this->model->find($reclamoId);
            if (! $reclamo) {
                return $this->failNotFound('Reclamo no encontrado.');
            }

            $rutaEjecucionIdCliente = (int) ($this->request->getPost('ruta_ejecucion_id') ?? 0);
            if ($rutaEjecucionIdCliente < 1) {
                return $this->failValidationErrors('ruta_ejecucion_id es obligatorio.');
            }

            $ctx = $this->validarRegistroBitacoraEjecucionReclamo($reclamoId, $rutaEjecucionIdCliente);
            if (! is_array($ctx)) {
                return $ctx;
            }

            $archivo = $this->request->getFile('foto');
            if (! $archivo || ! $archivo->isValid()) {
                return $this->failValidationErrors('No se recibió una imagen válida.');
            }

            $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            $mime = strtolower((string) $archivo->getMimeType());
            // iOS a veces reporta application/octet-stream; validar también por extensión
            $ext = strtolower((string) ($archivo->getExtension() ?: ''));
            $extOk = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
            if (! in_array($mime, $tiposPermitidos, true) && ! $extOk) {
                return $this->failValidationErrors('Formato no permitido. Use JPG, PNG o WEBP.');
            }

            $maxBytes = 5 * 1024 * 1024;
            if ($archivo->getSize() > $maxBytes) {
                return $this->failValidationErrors('La imagen no debe superar los 5 MB.');
            }

            $texto = trim((string) ($this->request->getPost('texto') ?? ''));
            if ($texto === '') {
                $texto = null;
            } else {
                $len = function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
                if ($len > 4000) {
                    return $this->failValidationErrors('El texto no puede superar los 4000 caracteres.');
                }
            }

            $directorio = FCPATH . 'static/uploads/obra_reclamos';
            if (! is_dir($directorio)) {
                mkdir($directorio, 0775, true);
            }

            if ($ext === '' || ! $extOk) {
                $ext = 'jpg';
            }
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            $nombreArchivo = 'rec' . $reclamoId . '_ej' . $ctx['ruta_ejecucion_id'] . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

            if (! $archivo->move($directorio, $nombreArchivo)) {
                return $this->failServerError('No se pudo guardar la imagen.');
            }

            $row = $this->insertarEntradaBitacoraEjecucion($ctx, $reclamoId, 'foto', $texto, $nombreArchivo);

            return $this->respondCreated($row);
        } catch (\Exception $e) {
            log_message('error', 'Error al guardar foto de ejecución: ' . $e->getMessage());

            return $this->failServerError('Error al guardar la foto.');
        }
    }

    /**
     * @param array{ruta_ejecucion_id: int, ruta_id: int, reclamo_id: int} $ctx
     */
    private function insertarEntradaBitacoraEjecucion(array $ctx, int $reclamoId, string $tipo, ?string $texto, ?string $archivo): array
    {
        $usuarioId = session()->get('user_id');
        if (! $usuarioId) {
            $usuarioId = 0;
        }

        $obsModel = new RutaEjecucionReclamoObservacionModel();
        $ahora    = date('Y-m-d H:i:s');
        $insert   = [
            'ruta_ejecucion_id' => $ctx['ruta_ejecucion_id'],
            'ruta_id'           => $ctx['ruta_id'],
            'reclamo_id'        => $reclamoId,
            'texto'             => $texto,
            'usuario_id'        => (int) $usuarioId > 0 ? (int) $usuarioId : null,
            'created_at'        => $ahora,
        ];

        $db = \Config\Database::connect();
        if ($db->fieldExists('tipo', 'ruta_ejecucion_reclamo_observacion')) {
            $insert['tipo']    = $tipo;
            $insert['archivo'] = $archivo;
        }

        $obsModel->insert($insert);
        $id = (int) $obsModel->getInsertID();
        if ($id < 1) {
            throw new \RuntimeException('Error al guardar la entrada de bitácora.');
        }

        $cols = 'o.id, o.ruta_ejecucion_id, o.ruta_id, o.reclamo_id, o.texto, o.created_at, o.usuario_id, u.nombre as usuario_nombre, u.foto_perfil as usuario_foto_perfil, r.nombre as ruta_nombre, r.color as ruta_color';
        if ($db->fieldExists('tipo', 'ruta_ejecucion_reclamo_observacion')) {
            $cols .= ', o.tipo, o.archivo';
        }

        $row = $db->table('ruta_ejecucion_reclamo_observacion o')
            ->select($cols)
            ->join('usuario u', 'u.id = o.usuario_id', 'left')
            ->join('ruta r', 'r.id = o.ruta_id', 'left')
            ->where('o.id', $id)
            ->get()
            ->getRowArray();

        $enriquecidas = $this->enriquecerFilasBitacoraEjecucion([$row ?? ['id' => $id]]);

        return $enriquecidas[0] ?? ['id' => $id];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function enriquecerFilasBitacoraEjecucion(array $rows): array
    {
        foreach ($rows as &$row) {
            $tipo = (string) ($row['tipo'] ?? 'texto');
            if ($tipo === '') {
                $tipo = 'texto';
            }
            $row['bitacora_tipo'] = 'observacion';
            $row['tipo']          = $tipo;
            $row['url_foto']      = null;
            if ($tipo === 'foto' && ! empty($row['archivo'])) {
                $row['url_foto'] = base_url('static/uploads/obra_reclamos/' . $row['archivo']);
            }
        }

        return $rows;
    }

    /**
     * Cambios de estado del reclamo para la bitácora en obra (misma línea de tiempo que notas/fotos).
     *
     * @return list<array<string, mixed>>
     */
    private function obtenerEntradasCambioEstadoBitacoraReclamo(int $reclamoId): array
    {
        $db = \Config\Database::connect();
        $rows = $db->table('ruta_ejecucion_evento e')
            ->select('e.id, e.ocurrido_at, e.metadata, u.nombre AS usuario_nombre, u.foto_perfil AS usuario_foto_perfil, r.nombre AS ruta_nombre, r.color AS ruta_color')
            ->join('usuario u', 'u.id = e.usuario_id', 'left')
            ->join('ruta_ejecucion re', 're.id = e.ruta_ejecucion_id', 'left')
            ->join('ruta r', 'r.id = re.ruta_id', 'left')
            ->where('e.reclamo_id', $reclamoId)
            ->where('e.tipo', RutaEjecucionHistorialService::TIPO_RECLAMO_ESTADO)
            ->orderBy('e.ocurrido_at', 'DESC')
            ->orderBy('e.id', 'DESC')
            ->get()
            ->getResultArray();

        $out = [];
        foreach ($rows as $ev) {
            $md = null;
            if (! empty($ev['metadata'])) {
                $decoded = json_decode($ev['metadata'], true);
                $md      = is_array($decoded) ? $decoded : null;
            }
            if ($md === null || $md['estado_anterior'] === null || $md['estado_nuevo'] === null) {
                continue;
            }
            $out[] = [
                'bitacora_tipo'   => 'cambio_estado',
                'id'              => 'est-' . $ev['id'],
                'created_at'      => $ev['ocurrido_at'],
                'estado_anterior' => (string) $md['estado_anterior'],
                'estado_nuevo'    => (string) $md['estado_nuevo'],
                'usuario_nombre'  => $ev['usuario_nombre'] ?? null,
                'usuario_foto_perfil' => $ev['usuario_foto_perfil'] ?? null,
                'ruta_nombre'     => $ev['ruta_nombre'] ?? null,
                'ruta_color'      => $ev['ruta_color'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $observaciones
     * @param list<array<string, mixed>> $cambiosEstado
     *
     * @return list<array<string, mixed>>
     */
    private function fusionarBitacoraEjecucionReclamo(array $observaciones, array $cambiosEstado): array
    {
        $fusion = array_merge($observaciones, $cambiosEstado);
        usort($fusion, static function (array $a, array $b): int {
            $ta = strtotime((string) ($a['created_at'] ?? '')) ?: 0;
            $tb = strtotime((string) ($b['created_at'] ?? '')) ?: 0;
            if ($ta !== $tb) {
                return $tb <=> $ta;
            }

            return strcmp((string) ($b['id'] ?? ''), (string) ($a['id'] ?? ''));
        });

        return $fusion;
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
     * Lectura de bitácora: supervisores/administradores o cualquier operario de la cuadrilla.
     *
     * @return true|\CodeIgniter\HTTP\ResponseInterface
     */
    private function validarAccesoBitacoraEjecucionReclamo(int $reclamoId)
    {
        $session = session();
        $userId  = (int) ($session->get('user_id') ?? 0);
        $role    = (string) ($session->get('role') ?? '');

        if (! $userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        if ($role !== '3') {
            return true;
        }

        return $this->validarOperarioCuadrillaRutaReclamo($reclamoId);
    }

    /**
     * Registro en bitácora: operario de la cuadrilla, hoja en ejecución y reclamo en obra.
     *
     * @return array{ruta_ejecucion_id: int, ruta_id: int, reclamo_id: int}|\CodeIgniter\HTTP\ResponseInterface
     */
    private function validarRegistroBitacoraEjecucionReclamo(int $reclamoId, int $rutaEjecucionIdCliente)
    {
        $session = session();
        $role    = (string) ($session->get('role') ?? '');

        if ($role !== '3') {
            return $this->failForbidden('Solo operarios de la cuadrilla pueden registrar entradas en obra.');
        }

        $acceso = $this->validarOperarioCuadrillaRutaReclamo($reclamoId);
        if ($acceso !== true) {
            return $acceso;
        }

        $ctx = $this->resolverContextoObservacionEjecucionReclamo($reclamoId, $rutaEjecucionIdCliente);
        if ($ctx === null) {
            return $this->failForbidden('La ejecución no es válida para esta hoja y reclamo, o la ruta no está en ejecución.');
        }

        if (! RutaEjecucionHistorialService::reclamoTieneObraActivaEnEjecucion($reclamoId, $ctx['ruta_ejecucion_id'])) {
            return $this->failForbidden('El reclamo no está en obra. Iniciá el trabajo antes de registrar.');
        }

        return $ctx;
    }

    /**
     * @return true|\CodeIgniter\HTTP\ResponseInterface
     */
    private function validarOperarioCuadrillaRutaReclamo(int $reclamoId)
    {
        $session = session();
        $userId  = (int) ($session->get('user_id') ?? 0);

        $vinculoRuta = RutaEjecucionHistorialService::findRutaReclamoLinkRutaAsignada($reclamoId);
        if (! $vinculoRuta) {
            return $this->failForbidden('El reclamo no está en ninguna hoja de ruta asignada a cuadrilla.');
        }

        $rutaModel = new RutaModel();
        $ruta      = $rutaModel->find($vinculoRuta['ruta_id']);
        if (! $ruta || (int) ($ruta['asignada'] ?? 0) !== 1 || empty($ruta['cuadrilla_id'])) {
            return $this->failForbidden('La hoja de ruta del reclamo no está asignada.');
        }

        $cuadrillaOperariosModel = new CuadrillaOperariosModel();
        $asignacion              = $cuadrillaOperariosModel
            ->where('usuario_id', $userId)
            ->where('cuadrilla_id', $ruta['cuadrilla_id'])
            ->first();

        if (! $asignacion) {
            return $this->failForbidden('No tiene permisos sobre tareas de esta cuadrilla.');
        }

        return true;
    }

    /**
     * Si el usuario es operario, solo quienes tienen permisos de gestión en su cuadrilla pueden editar acciones.
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
            return $this->failForbidden('Solo un operario con permisos de gestión puede editar las tareas de esta hoja de ruta.');
        }

        return true;
    }
}
