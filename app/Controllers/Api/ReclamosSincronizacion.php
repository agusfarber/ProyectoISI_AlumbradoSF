<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Token103Model;
use App\Models\ReclamoModel;
use App\Models\DireccionModel;

class ReclamosSincronizacion extends ResourceController
{
    protected $format = 'json';
    private $apiExternaUrl = 'https://reclamostesting.sanfrancisco.gov.ar/api/3.0/reclamos/';
    
    // API Keys para geocodificación
    private $googleMapsApiKey = 'AIzaSyAOCwr8_hWX4aBE2JTHxREP7gUrYLadCgg';
    private $mapboxApiKey = 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ajJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw';

    /**
     * Sincroniza reclamos desde el último guardado hasta hoy
     */
    public function sincronizarHoy()
    {
        try {
            // Obtener la fecha actual en Argentina
            date_default_timezone_set('America/Argentina/Buenos_Aires');
            $fechaHoy = date('Y-m-d');
            
            // Obtener el último reclamo guardado (por ID de municipalidad para evitar duplicados)
            $reclamoModel = new ReclamoModel();
            $ultimoReclamo = $reclamoModel
                ->orderBy('CAST(municipalidad_id AS UNSIGNED)', 'DESC')
                ->first();
            
            $ultimoMunicipalidadId = 0;
            $fechaUltimoReclamo = date('Y-m-d', strtotime('-7 days')); // Por defecto 7 días
            
            // Si hay reclamos, usar la fecha del último (para la API) y guardar su ID
            if ($ultimoReclamo && !empty($ultimoReclamo['municipalidad_id'])) {
                $ultimoMunicipalidadId = (int)$ultimoReclamo['municipalidad_id'];
                // Usar la fecha del último reclamo menos 1 día para asegurar que no perdemos ninguno
                $fechaUltimoReclamo = date('Y-m-d', strtotime($ultimoReclamo['municipalidad_fechaInicio'] . ' -1 day'));
            }
            
            log_message('info', 'Sincronizando reclamos desde: ' . $fechaUltimoReclamo . ' hasta: ' . $fechaHoy . ' | Último ID guardado: ' . $ultimoMunicipalidadId);
            
            // Obtener credenciales Basic Auth
            $tokenModel = new Token103Model();
            $credenciales = $tokenModel->orderBy('id', 'DESC')->first();

            if (!$credenciales || empty($credenciales['username']) || empty($credenciales['password'])) {
                return $this->failNotFound('No hay credenciales configuradas. Configure username y password primero.');
            }

            // Generar token Basic Auth
            $credencialesString = $credenciales['username'] . ':' . $credenciales['password'];
            $tokenBase64 = base64_encode($credencialesString);

            // Construir URL con parámetros desde el último reclamo hasta hoy
            $url = $this->apiExternaUrl . '?created_after=' . $fechaUltimoReclamo . '&created_before=' . $fechaHoy;
            
            log_message('info', 'Intentando conectar a: ' . $url);

            // Hacer petición a la API externa usando cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
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
                log_message('error', 'Error en petición cURL: ' . $error);
                return $this->failServerError('Error al conectar con la API externa: ' . $error);
            }

            if ($httpCode !== 200) {
                log_message('error', 'HTTP Code: ' . $httpCode . ' - Response: ' . $response);
                return $this->fail('Error en la API externa. Código: ' . $httpCode, $httpCode);
            }

            // Decodificar respuesta
            $responseData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->failServerError('Error al decodificar respuesta de la API');
            }

            // La respuesta tiene paginación, obtener todos los resultados
            $todosLosReclamos = [];
            $paginaActual = $responseData;
            
            if (isset($paginaActual['results']) && is_array($paginaActual['results'])) {
                $todosLosReclamos = array_merge($todosLosReclamos, $paginaActual['results']);
            }
            
            // Obtener todas las páginas siguientes
            while (!empty($paginaActual['next'])) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $paginaActual['next']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Basic ' . $tokenBase64,
                    'Accept: application/json',
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $nextResponse = curl_exec($ch);
                curl_close($ch);
                
                $paginaActual = json_decode($nextResponse, true);
                
                if (isset($paginaActual['results']) && is_array($paginaActual['results'])) {
                    $todosLosReclamos = array_merge($todosLosReclamos, $paginaActual['results']);
                }
            }
            
            log_message('info', 'Total de reclamos obtenidos en el rango: ' . count($todosLosReclamos));
            
            // Filtrar solo los de ALUMBRADO PÚBLICO y que sean NUEVOS (ID mayor al último guardado)
            $reclamosFiltrados = [];
            $reclamosOmitidos = 0;
            
            foreach ($todosLosReclamos as $reclamo) {
                // Solo ALUMBRADO PÚBLICO
                if (isset($reclamo['motivo']['tipo']) && $reclamo['motivo']['tipo'] === 'ALUMBRADO PÚBLICO') {
                    $reclamoMapeado = $this->mapearReclamo($reclamo);
                    $idReclamo = (int)$reclamoMapeado['municipalidad_id'];
                    
                    // Solo agregar si el ID es MAYOR al último guardado (es decir, es nuevo)
                    if ($idReclamo > $ultimoMunicipalidadId) {
                        $reclamosFiltrados[] = $reclamoMapeado;
                    } else {
                        $reclamosOmitidos++;
                    }
                }
            }
            
            log_message('info', 'Total de reclamos de ALUMBRADO PÚBLICO en el rango: ' . (count($reclamosFiltrados) + $reclamosOmitidos));
            log_message('info', 'Reclamos nuevos (ID > ' . $ultimoMunicipalidadId . '): ' . count($reclamosFiltrados));
            log_message('info', 'Reclamos omitidos (ya existentes): ' . $reclamosOmitidos);
            
            // Devolver reclamos al frontend para que los procese progresivamente
            return $this->respond([
                'success' => true,
                'fecha_desde' => $fechaUltimoReclamo,
                'fecha_hasta' => $fechaHoy,
                'total_recibidos' => count($todosLosReclamos),
                'total_alumbrado' => count($reclamosFiltrados) + $reclamosOmitidos,
                'reclamos_nuevos' => count($reclamosFiltrados),
                'reclamos_omitidos' => $reclamosOmitidos,
                'ultimo_id_guardado' => $ultimoMunicipalidadId,
                'reclamos' => $reclamosFiltrados // Frontend los procesará uno por uno
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error en sincronizarHoy: ' . $e->getMessage());
            return $this->failServerError('Error al sincronizar reclamos pendientes: ' . $e->getMessage());
        }
    }

    /**
     * Sincroniza reclamos por rango de fechas desde la API externa
     */
    public function sincronizarPorFechas()
    {
        try {
            // Obtener parámetros de fecha
            $fechaDesde = $this->request->getGet('fecha_desde');
            $fechaHasta = $this->request->getGet('fecha_hasta');

            if (empty($fechaDesde) || empty($fechaHasta)) {
                return $this->failValidationErrors('Fecha desde y fecha hasta son obligatorias');
            }
            
            return $this->sincronizarPorRangoFechas($fechaDesde, $fechaHasta);
            
        } catch (\Exception $e) {
            log_message('error', 'Error en sincronizarPorFechas: ' . $e->getMessage());
            return $this->failServerError('Error al sincronizar reclamos: ' . $e->getMessage());
        }
    }

    /**
     * Método interno para sincronizar reclamos por rango de fechas
     */
    private function sincronizarPorRangoFechas($fechaDesde, $fechaHasta)
    {
        try {
            // Obtener el último reclamo guardado para evitar duplicados
            $reclamoModel = new ReclamoModel();
            $ultimoReclamo = $reclamoModel
                ->orderBy('CAST(municipalidad_id AS UNSIGNED)', 'DESC')
                ->first();
            
            $ultimoMunicipalidadId = 0;
            if ($ultimoReclamo && !empty($ultimoReclamo['municipalidad_id'])) {
                $ultimoMunicipalidadId = (int)$ultimoReclamo['municipalidad_id'];
            }
            
            log_message('info', 'Sincronización por fechas | Último ID guardado: ' . $ultimoMunicipalidadId);

            // Obtener credenciales Basic Auth
            $tokenModel = new Token103Model();
            $credenciales = $tokenModel->orderBy('id', 'DESC')->first();

            if (!$credenciales || empty($credenciales['username']) || empty($credenciales['password'])) {
                return $this->failNotFound('No hay credenciales configuradas. Configure username y password primero.');
            }

            // Generar token Basic Auth
            $credencialesString = $credenciales['username'] . ':' . $credenciales['password'];
            $tokenBase64 = base64_encode($credencialesString);

            // Construir URL con parámetros
            $url = $this->apiExternaUrl . '?created_after=' . $fechaDesde . '&created_before=' . $fechaHasta;
            
            // Log para debug
            log_message('info', 'Intentando conectar a: ' . $url);
            log_message('info', 'Token Base64: ' . substr($tokenBase64, 0, 20) . '...');

            // Hacer petición a la API externa usando cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Seguir redirecciones automáticamente
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // Máximo 10 redirecciones
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $tokenBase64,
                'Accept: application/json',
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para desarrollo, en producción cambiar a true
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Para desarrollo, en producción cambiar a 2
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $error = curl_error($ch);
            curl_close($ch);
            
            // Log para debug
            log_message('info', 'HTTP Code recibido: ' . $httpCode);
            log_message('info', 'URL final (después de redirecciones): ' . $effectiveUrl);
            log_message('info', 'Primeros 500 caracteres de respuesta: ' . substr($response, 0, 500));

            if ($error) {
                log_message('error', 'Error en petición cURL: ' . $error);
                return $this->failServerError('Error al conectar con la API externa: ' . $error);
            }

            if ($httpCode !== 200) {
                log_message('error', 'HTTP Code: ' . $httpCode . ' - Response completa: ' . $response);
                return $this->fail('Error en la API externa. Código: ' . $httpCode . '. Respuesta: ' . substr($response, 0, 200), $httpCode);
            }

            // Decodificar respuesta
            $responseData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->failServerError('Error al decodificar respuesta de la API');
            }

            // La respuesta tiene paginación, obtener todos los resultados
            $todosLosReclamos = [];
            $paginaActual = $responseData;
            
            // Procesar primera página
            if (isset($paginaActual['results']) && is_array($paginaActual['results'])) {
                $todosLosReclamos = array_merge($todosLosReclamos, $paginaActual['results']);
            }
            
            // Obtener todas las páginas siguientes
            while (!empty($paginaActual['next'])) {
                log_message('info', 'Obteniendo siguiente página: ' . $paginaActual['next']);
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $paginaActual['next']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Basic ' . $tokenBase64,
                    'Accept: application/json',
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $nextResponse = curl_exec($ch);
                curl_close($ch);
                
                $paginaActual = json_decode($nextResponse, true);
                
                if (isset($paginaActual['results']) && is_array($paginaActual['results'])) {
                    $todosLosReclamos = array_merge($todosLosReclamos, $paginaActual['results']);
                }
            }
            
            log_message('info', 'Total de reclamos obtenidos (todas las páginas): ' . count($todosLosReclamos));
            
            // Filtrar solo los de ALUMBRADO PÚBLICO y que sean NUEVOS (ID mayor al último guardado)
            $reclamosFiltrados = [];
            $reclamosOmitidos = 0;
            
            foreach ($todosLosReclamos as $reclamo) {
                // Solo ALUMBRADO PÚBLICO
                if (isset($reclamo['motivo']['tipo']) && $reclamo['motivo']['tipo'] === 'ALUMBRADO PÚBLICO') {
                    $reclamoMapeado = $this->mapearReclamo($reclamo);
                    $idReclamo = (int)$reclamoMapeado['municipalidad_id'];
                    
                    // Solo agregar si el ID es MAYOR al último guardado (es decir, es nuevo)
                    if ($idReclamo > $ultimoMunicipalidadId) {
                        $reclamosFiltrados[] = $reclamoMapeado;
                    } else {
                        $reclamosOmitidos++;
                    }
                }
            }
            
            log_message('info', 'Total de reclamos de ALUMBRADO PÚBLICO: ' . (count($reclamosFiltrados) + $reclamosOmitidos));
            log_message('info', 'Reclamos nuevos (ID > ' . $ultimoMunicipalidadId . '): ' . count($reclamosFiltrados));
            log_message('info', 'Reclamos omitidos (ya existentes): ' . $reclamosOmitidos);
            
            // Devolver reclamos al frontend para que los procese progresivamente
            return $this->respond([
                'success' => true,
                'total_recibidos' => count($todosLosReclamos),
                'total_alumbrado' => count($reclamosFiltrados) + $reclamosOmitidos,
                'reclamos_nuevos' => count($reclamosFiltrados),
                'reclamos_omitidos' => $reclamosOmitidos,
                'ultimo_id_guardado' => $ultimoMunicipalidadId,
                'reclamos' => $reclamosFiltrados // Frontend los procesará uno por uno
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error en sincronizarPorRangoFechas: ' . $e->getMessage());
            return $this->failServerError('Error al sincronizar reclamos: ' . $e->getMessage());
        }
    }

    /**
     * Sincroniza un reclamo específico por número
     */
    public function sincronizarEspecifico($numeroReclamo = null)
    {
        try {
            if (empty($numeroReclamo)) {
                return $this->failValidationErrors('Número de reclamo es obligatorio');
            }

            // Obtener credenciales Basic Auth
            $tokenModel = new Token103Model();
            $credenciales = $tokenModel->orderBy('id', 'DESC')->first();

            if (!$credenciales || empty($credenciales['username']) || empty($credenciales['password'])) {
                return $this->failNotFound('No hay credenciales configuradas. Configure username y password primero.');
            }

            // Generar token Basic Auth
            $credencialesString = $credenciales['username'] . ':' . $credenciales['password'];
            $tokenBase64 = base64_encode($credencialesString);

            // Construir URL
            $url = $this->apiExternaUrl . '/' . $numeroReclamo;

            // Hacer petición a la API externa usando cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Seguir redirecciones automáticamente
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // Máximo 10 redirecciones
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $tokenBase64,
                'Accept: application/json',
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para desarrollo, en producción cambiar a true
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Para desarrollo, en producción cambiar a 2
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                log_message('error', 'Error en petición cURL: ' . $error);
                return $this->failServerError('Error al conectar con la API externa: ' . $error);
            }

            if ($httpCode !== 200) {
                log_message('error', 'HTTP Code: ' . $httpCode . ' - Response: ' . $response);
                return $this->fail('Error en la API externa. Código: ' . $httpCode, $httpCode);
            }

            // Decodificar respuesta
            $reclamo = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->failServerError('Error al decodificar respuesta de la API');
            }

            // Verificar si es de ALUMBRADO PÚBLICO
            if (!isset($reclamo['motivo']['tipo']) || $reclamo['motivo']['tipo'] !== 'ALUMBRADO PÚBLICO') {
                return $this->fail('El reclamo ' . $numeroReclamo . ' no es de tipo ALUMBRADO PÚBLICO (es: ' . ($reclamo['motivo']['tipo'] ?? 'desconocido') . ')', 400);
            }

            // Mapear el reclamo
            $reclamoMapeado = $this->mapearReclamo($reclamo);

            // Guardar en la base de datos
            $reclamoModel = new ReclamoModel();
            try {
                // Verificar si el reclamo ya existe
                $existente = $reclamoModel->where('municipalidad_id', $reclamoMapeado['municipalidad_id'])->first();
                
                if ($existente) {
                    // Actualizar
                    $reclamoModel->update($existente['id'], $reclamoMapeado);
                    $accion = 'actualizado';
                } else {
                    // Crear
                    $reclamoModel->insert($reclamoMapeado);
                    $accion = 'creado';
                }

                // Geocodificar la dirección inmediatamente (solo 1 reclamo, es rápido)
                if (!empty($reclamoMapeado['municipalidad_domicilio']) && !empty($reclamoMapeado['municipalidad_numeroDomicilio'])) {
                    $this->procesarDireccionReclamo(
                        $reclamoMapeado['municipalidad_domicilio'],
                        $reclamoMapeado['municipalidad_numeroDomicilio']
                    );
                }

                return $this->respond([
                    'success' => true,
                    'accion' => $accion,
                    'reclamo' => $reclamoMapeado
                ]);

            } catch (\Exception $e) {
                log_message('error', 'Error al guardar reclamo específico: ' . $e->getMessage());
                return $this->failServerError('Error al guardar el reclamo: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            log_message('error', 'Error en sincronizarEspecifico: ' . $e->getMessage());
            return $this->failServerError('Error al sincronizar reclamo: ' . $e->getMessage());
        }
    }

    /**
     * Procesa un solo reclamo (guardar + geocodificar)
     * Para procesamiento progresivo desde el frontend
     */
    public function procesarUno()
    {
        try {
            $data = $this->request->getJSON(true);
            
            if (empty($data)) {
                return $this->failValidationErrors('Datos de reclamo requeridos');
            }
            
            // Asegurar que se asigne prioridad baja por defecto si no viene
            if (empty($data['prioridad'])) {
                $data['prioridad'] = 'Baja';
            }
            
            $reclamoModel = new ReclamoModel();
            
            // Verificar si el reclamo ya existe
            $existente = $reclamoModel->where('municipalidad_id', $data['municipalidad_id'])->first();
            
            if ($existente) {
                // Actualizar
                $reclamoModel->update($existente['id'], $data);
                $accion = 'actualizado';
            } else {
                // Crear
                $reclamoModel->insert($data);
                $accion = 'creado';
            }
            
            // Geocodificar la dirección
            if (!empty($data['municipalidad_domicilio']) && !empty($data['municipalidad_numeroDomicilio'])) {
                $this->procesarDireccionReclamo(
                    $data['municipalidad_domicilio'],
                    $data['municipalidad_numeroDomicilio']
                );
            }
            
            return $this->respond([
                'success' => true,
                'accion' => $accion,
                'municipalidad_id' => $data['municipalidad_id']
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Error al procesar reclamo: ' . $e->getMessage());
            return $this->failServerError('Error al procesar reclamo: ' . $e->getMessage());
        }
    }

    /**
     * Mapea un reclamo de la API externa a la estructura de nuestra BD
     */
    private function mapearReclamo($reclamoApi)
    {
        // Obtener el estado del reclamo
        $estadoOriginal = $reclamoApi['estado_nombre'] ?? 'Recibido';
        
        // Si el estado es "Asignado", cambiarlo automáticamente a "Recibido"
        $estado = ($estadoOriginal === 'Asignado') ? 'Recibido' : $estadoOriginal;
        
        return [
            'municipalidad_id' => (string)$reclamoApi['id'],
            'municipalidad_tipo' => $reclamoApi['motivo']['tipo'] ?? 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => $reclamoApi['motivo']['nombre'] ?? 'No especificado',
            'municipalidad_fechaInicio' => $this->convertirFechaApi($reclamoApi['fecha_inicio'] ?? null),
            'municipalidad_fechaModificacion' => $this->convertirFechaApi($reclamoApi['fecha_modificacion'] ?? null),
            'municipalidad_recepcion' => null, // No viene en la API
            'municipalidad_estado' => $estado,
            'municipalidad_telefono' => null, // No viene en la API
            'municipalidad_domicilio' => $reclamoApi['calle']['nombre'] ?? '',
            'municipalidad_numeroDomicilio' => (string)($reclamoApi['calle_altura'] ?? ''),
            'municipalidad_entreCalleUno' => $reclamoApi['desde_calle']['nombre'] ?? '',
            'municipalidad_entreCalleDos' => $reclamoApi['hasta_calle']['nombre'] ?? '',
            'municipalidad_ciudadano' => null, // No viene en la API
            'municipalidad_descripcion' => null, // No viene en la API
            'prioridad' => 'Baja' // Asignar prioridad baja por defecto para reclamos sincronizados
        ];
    }

    /**
     * Convierte fecha de la API (ISO 8601) a formato MySQL datetime
     */
    private function convertirFechaApi($fechaIso)
    {
        if (empty($fechaIso)) {
            return null;
        }

        try {
            // La fecha viene en formato: "2025-08-28T13:36:04.541033-03:00"
            $fecha = new \DateTime($fechaIso);
            // Convertir a formato MySQL: "YYYY-MM-DD HH:MM:SS"
            return $fecha->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            log_message('error', 'Error al convertir fecha: ' . $fechaIso . ' - ' . $e->getMessage());
            return null;
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
     * Geocodifica una dirección usando Google Maps API
     */
    private function geocodificarConGoogleMaps($domicilio, $numeroDomicilio)
    {
        try {
            $direccionCompleta = trim($domicilio) . ' ' . trim($numeroDomicilio) . ', San Francisco, Córdoba, Argentina';
            
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
                'address' => $direccionCompleta,
                'key' => $this->googleMapsApiKey,
                'region' => 'ar'
            ]);

            log_message('info', 'Geocodificando con Google Maps: ' . $direccionCompleta);

            $client = \Config\Services::curlrequest();
            $response = $client->get($url, ['timeout' => 10]);
            
            $data = json_decode($response->getBody(), true);

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
     */
    private function geocodificarConMapbox($domicilio, $numeroDomicilio)
    {
        try {
            $direccionCompleta = trim($domicilio) . ' ' . trim($numeroDomicilio) . ', San Francisco, Córdoba, Argentina';
            
            $url = "https://api.mapbox.com/geocoding/v5/mapbox.places/" . urlencode($direccionCompleta) . ".json?" . http_build_query([
                'access_token' => $this->mapboxApiKey,
                'country' => 'AR',
                'limit' => 1
            ]);

            log_message('info', 'Geocodificando con Mapbox (fallback): ' . $direccionCompleta);

            $client = \Config\Services::curlrequest();
            $response = $client->get($url, ['timeout' => 10]);
            
            $data = json_decode($response->getBody(), true);

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
     */
    private function guardarDireccion($domicilio, $numeroDomicilio, $latitud, $longitud, $fuente = 'google_maps')
    {
        try {
            if (empty($domicilio) || empty($numeroDomicilio) || empty($latitud) || empty($longitud)) {
                log_message('debug', 'Dirección incompleta, no se guardará');
                return false;
            }

            $direccionModel = new DireccionModel();
            
            $domicilioNormalizado = strtoupper(trim($domicilio));
            $numeroNormalizado = trim($numeroDomicilio);

            // Verificar si ya existe esta dirección
            $direccionExistente = $direccionModel
                ->where('TRIM(UPPER(domicilio))', $domicilioNormalizado)
                ->where('TRIM(numero_domicilio)', $numeroNormalizado)
                ->first();

            if ($direccionExistente) {
                if ($direccionExistente['personalizada'] == 1) {
                    log_message('info', "Dirección ya existe y es personalizada, no se actualiza: {$domicilio} {$numeroDomicilio}");
                    return false;
                }
                
                log_message('info', "Dirección ya existe en la base de datos: {$domicilio} {$numeroDomicilio}");
                return false;
            }

            // Crear nueva dirección
            $datosDireccion = [
                'domicilio' => $domicilio,
                'numero_domicilio' => $numeroDomicilio,
                'latitud' => $latitud,
                'longitud' => $longitud,
                'personalizada' => 0
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
}

