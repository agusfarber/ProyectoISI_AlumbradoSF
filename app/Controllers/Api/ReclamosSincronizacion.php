<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Token103Model;
use App\Models\ReclamoModel;
use App\Models\DireccionModel;
use App\Libraries\ReclamoPrioridadService;

class ReclamosSincronizacion extends ResourceController
{
    protected $format = 'json';
    private const ESTADO_INVALIDO_SINCRONIZACION = 'Inválido (N/A)';
    private $apiExternaUrl = 'https://reclamos.sanfrancisco.gov.ar/api/3.0/reclamos/';
    
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
            
            // Último ID del 103 (ignora creados localmente) para no bloquear el sync
            $reclamoModel = new ReclamoModel();
            $ultimoMunicipalidadId = $reclamoModel->ultimoMunicipalidadId103();
            $fechaUltimoReclamo = date('Y-m-d', strtotime('-7 days'));

            $ultimoReclamo103 = $reclamoModel
                ->where('origen', ReclamoModel::ORIGEN_103)
                ->orderBy('municipalidad_fechaInicio', 'DESC')
                ->first();
            if ($ultimoReclamo103 && !empty($ultimoReclamo103['municipalidad_fechaInicio'])) {
                $fechaUltimoReclamo = date('Y-m-d', strtotime($ultimoReclamo103['municipalidad_fechaInicio'] . ' -1 day'));
            }
            
            log_message('info', 'Sincronizando reclamos desde: ' . $fechaUltimoReclamo . ' hasta: ' . $fechaHoy . ' | Último ID 103: ' . $ultimoMunicipalidadId);
            
            $apiToken = $this->obtenerApiToken103();
            if ($apiToken === null) {
                return $this->failNotFound('No hay token configurado para el sistema 103. Configure el token primero.');
            }

            // Construir URL con parámetros desde el último reclamo hasta hoy
            $url = $this->apiExternaUrl . '?created_after=' . $fechaUltimoReclamo . '&created_before=' . $fechaHoy;
            
            log_message('info', 'Intentando conectar a: ' . $url);

            // Hacer petición a la API externa usando cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headersAutorizacion103($apiToken));
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
            $resultadoPaginacion = $this->obtenerTodasLasPaginas103($responseData, $apiToken);
            $todosLosReclamos = $resultadoPaginacion['reclamos'];
            $respuesta103Cruda = $resultadoPaginacion['paginas_crudas'];
            
            log_message('info', 'Total de reclamos obtenidos en el rango: ' . count($todosLosReclamos));
            
            $resultadoFiltrado = $this->filtrarReclamosAlumbradoNuevos($todosLosReclamos, $ultimoMunicipalidadId);

            log_message('info', 'Total de reclamos de ALUMBRADO PÚBLICO en el rango: ' . $resultadoFiltrado['total_alumbrado']);
            log_message('info', 'Reclamos nuevos (ID > ' . $ultimoMunicipalidadId . '): ' . count($resultadoFiltrado['reclamos']));
            log_message('info', 'Reclamos omitidos (ya existentes): ' . $resultadoFiltrado['reclamos_omitidos']);
            log_message('info', 'Reclamos omitidos (estado inválido): ' . $resultadoFiltrado['reclamos_invalidos']);

            // Devolver reclamos al frontend para que los procese progresivamente
            return $this->respond([
                'success' => true,
                'fecha_desde' => $fechaUltimoReclamo,
                'fecha_hasta' => $fechaHoy,
                'total_recibidos' => count($todosLosReclamos),
                'total_alumbrado' => $resultadoFiltrado['total_alumbrado'],
                'reclamos_nuevos' => count($resultadoFiltrado['reclamos']),
                'reclamos_omitidos' => $resultadoFiltrado['reclamos_omitidos'],
                'reclamos_invalidos' => $resultadoFiltrado['reclamos_invalidos'],
                'ultimo_id_guardado' => $ultimoMunicipalidadId,
                'reclamos' => $resultadoFiltrado['reclamos'], // Frontend los procesará uno por uno
                'debug_respuesta_103' => $respuesta103Cruda,
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
            // Rangos medianos pueden paginar varias veces contra el 103
            @set_time_limit(120);
            ini_set('max_execution_time', '120');

            // Último ID del 103 (ignora creados localmente)
            $reclamoModel = new ReclamoModel();
            $ultimoMunicipalidadId = $reclamoModel->ultimoMunicipalidadId103();
            
            log_message('info', 'Sincronización por fechas | Último ID 103: ' . $ultimoMunicipalidadId);

            $apiToken = $this->obtenerApiToken103();
            if ($apiToken === null) {
                return $this->failNotFound('No hay token configurado para el sistema 103. Configure el token primero.');
            }

            // Construir URL con parámetros
            $url = $this->apiExternaUrl . '?created_after=' . $fechaDesde . '&created_before=' . $fechaHasta;
            
            // Log para debug
            log_message('info', 'Intentando conectar a: ' . $url);

            // Hacer petición a la API externa usando cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Seguir redirecciones automáticamente
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // Máximo 10 redirecciones
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headersAutorizacion103($apiToken));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Para desarrollo, en producción cambiar a true
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Para desarrollo, en producción cambiar a 2
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

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
            $resultadoPaginacion = $this->obtenerTodasLasPaginas103($responseData, $apiToken);
            $todosLosReclamos = $resultadoPaginacion['reclamos'];
            $respuesta103Cruda = $resultadoPaginacion['paginas_crudas'];
            
            log_message('info', 'Total de reclamos obtenidos (todas las páginas): ' . count($todosLosReclamos));
            
            $resultadoFiltrado = $this->filtrarReclamosAlumbradoNuevos($todosLosReclamos, $ultimoMunicipalidadId);

            log_message('info', 'Total de reclamos de ALUMBRADO PÚBLICO: ' . $resultadoFiltrado['total_alumbrado']);
            log_message('info', 'Reclamos nuevos (ID > ' . $ultimoMunicipalidadId . '): ' . count($resultadoFiltrado['reclamos']));
            log_message('info', 'Reclamos omitidos (ya existentes): ' . $resultadoFiltrado['reclamos_omitidos']);
            log_message('info', 'Reclamos omitidos (estado inválido): ' . $resultadoFiltrado['reclamos_invalidos']);

            // Evitar respuestas enormes: solo devolver debug crudo de la 1ª página
            $debugCrudo = [];
            if (! empty($respuesta103Cruda[0])) {
                $debugCrudo[] = $respuesta103Cruda[0];
            }

            // Devolver reclamos al frontend para que los procese progresivamente
            return $this->respond([
                'success' => true,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'total_recibidos' => count($todosLosReclamos),
                'total_alumbrado' => $resultadoFiltrado['total_alumbrado'],
                'reclamos_nuevos' => count($resultadoFiltrado['reclamos']),
                'reclamos_omitidos' => $resultadoFiltrado['reclamos_omitidos'],
                'reclamos_invalidos' => $resultadoFiltrado['reclamos_invalidos'],
                'ultimo_id_guardado' => $ultimoMunicipalidadId,
                'reclamos' => $resultadoFiltrado['reclamos'], // Frontend los procesará uno por uno
                'debug_respuesta_103' => $debugCrudo,
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

            $apiToken = $this->obtenerApiToken103();
            if ($apiToken === null) {
                return $this->failNotFound('No hay token configurado para el sistema 103. Configure el token primero.');
            }

            // Construir URL
            $url = rtrim($this->apiExternaUrl, '/') . '/' . $numeroReclamo;

            // Hacer petición a la API externa usando cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Seguir redirecciones automáticamente
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // Máximo 10 redirecciones
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headersAutorizacion103($apiToken));
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

            if ($this->esReclamoEstadoInvalido($reclamo['estado_nombre'] ?? null)) {
                return $this->fail('El reclamo ' . $numeroReclamo . ' tiene estado "' . self::ESTADO_INVALIDO_SINCRONIZACION . '" y no se guardará.', 400);
            }

            // Mapear el reclamo
            $reclamoMapeado = $this->mapearReclamo($reclamo);

            // Guardar en la base de datos
            $reclamoModel = new ReclamoModel();
            try {
                // Verificar si el reclamo ya existe
                $existente = $reclamoModel->where('municipalidad_id', $reclamoMapeado['municipalidad_id'])->first();

                if ($existente && (int) ($existente['excluido_local'] ?? 0) === 1) {
                    return $this->respond([
                        'success' => true,
                        'accion' => 'omitido',
                        'motivo' => 'excluido_local',
                        'mensaje' => 'El reclamo está excluido localmente y no se sincroniza.',
                        'reclamo' => $existente,
                        'ficha_protegida' => (int) ($existente['ficha_editada'] ?? 0) === 1,
                        'debug_respuesta_103' => $reclamo,
                    ]);
                }

                if ($existente && ($existente['origen'] ?? '') === ReclamoModel::ORIGEN_LOCAL) {
                    return $this->respond([
                        'success' => true,
                        'accion' => 'omitido',
                        'motivo' => 'origen_local',
                        'mensaje' => 'Ese número corresponde a un reclamo creado localmente; no se sincroniza desde el 103.',
                        'reclamo' => $existente,
                        'ficha_protegida' => true,
                        'debug_respuesta_103' => $reclamo,
                    ]);
                }
                
                if ($existente) {
                    // Si el supervisor corrigió la ficha, no pisar esos campos con el 103
                    $reclamoParaGuardar = $this->fusionarSyncConFichaLocal($existente, $reclamoMapeado);
                    $reclamoModel->update($existente['id'], $reclamoParaGuardar);
                    $accion = 'actualizado';
                    $reclamoRespuesta = array_merge($existente, $reclamoParaGuardar);
                } else {
                    // Crear
                    $reclamoModel->insert($reclamoMapeado);
                    $accion = 'creado';
                    $reclamoRespuesta = $reclamoMapeado;
                }

                // Geocodificar la dirección inmediatamente (solo 1 reclamo, es rápido)
                $domicilioGeo = $reclamoRespuesta['municipalidad_domicilio'] ?? null;
                $numeroGeo = $reclamoRespuesta['municipalidad_numeroDomicilio'] ?? null;
                if (!empty($domicilioGeo) && !empty($numeroGeo)) {
                    $this->procesarDireccionReclamo($domicilioGeo, $numeroGeo);
                }

                return $this->respond([
                    'success' => true,
                    'accion' => $accion,
                    'reclamo' => $reclamoRespuesta,
                    'ficha_protegida' => $existente ? ((int) ($existente['ficha_editada'] ?? 0) === 1) : false,
                    'debug_respuesta_103' => $reclamo,
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

            if ($this->esReclamoEstadoInvalido($data['municipalidad_estado'] ?? null)) {
                log_message('info', 'Reclamo omitido por estado inválido: ' . ($data['municipalidad_id'] ?? 'sin id'));
                return $this->respond([
                    'success' => true,
                    'accion' => 'omitido',
                    'motivo' => 'estado_invalido',
                    'municipalidad_id' => $data['municipalidad_id'] ?? null
                ]);
            }
            
            if (empty($data['prioridad'])) {
                $data['prioridad'] = 'Baja';
            }

            // Completado desde el 103 = cierre formal (defensa si el payload no trae cerrado)
            if (($data['municipalidad_estado'] ?? '') === 'Completado') {
                $data['cerrado'] = 1;
                if (empty($data['fecha_cierre'])) {
                    $data['fecha_cierre'] = $data['municipalidad_fechaModificacion']
                        ?? $data['municipalidad_fechaInicio']
                        ?? null;
                }
            }

            $reclamoModel = new ReclamoModel();

            $existente = $reclamoModel->where('municipalidad_id', $data['municipalidad_id'])->first();

            if ($existente && (int) ($existente['excluido_local'] ?? 0) === 1) {
                log_message('info', 'Reclamo omitido por exclusión local: ' . ($data['municipalidad_id'] ?? 'sin id'));
                return $this->respond([
                    'success' => true,
                    'accion' => 'omitido',
                    'motivo' => 'excluido_local',
                    'municipalidad_id' => $data['municipalidad_id'] ?? null,
                ]);
            }

            if ($existente && ($existente['origen'] ?? '') === ReclamoModel::ORIGEN_LOCAL) {
                log_message('info', 'Reclamo omitido: ID coincide con un reclamo local: ' . ($data['municipalidad_id'] ?? 'sin id'));
                return $this->respond([
                    'success' => true,
                    'accion' => 'omitido',
                    'motivo' => 'origen_local',
                    'municipalidad_id' => $data['municipalidad_id'] ?? null,
                ]);
            }

            if ($existente) {
                $data = $this->fusionarSyncConFichaLocal($existente, $data);
                $reclamoModel->update($existente['id'], $data);
                $accion = 'actualizado';
            } else {
                $data['prioridad'] = ReclamoPrioridadService::evaluarPrioridad($data);
                if (empty($data['origen'])) {
                    $data['origen'] = ReclamoModel::ORIGEN_103;
                }
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
     * Recorre la paginación del 103 y devuelve reclamos + JSON crudo por página (solo debug).
     */
    private function obtenerTodasLasPaginas103(array $primeraPagina, string $apiToken): array
    {
        $todosLosReclamos = [];
        $paginasCrudas = [];
        $paginaActual = $primeraPagina;

        if (is_array($paginaActual)) {
            $paginasCrudas[] = $paginaActual;
            if (isset($paginaActual['results']) && is_array($paginaActual['results'])) {
                $todosLosReclamos = array_merge($todosLosReclamos, $paginaActual['results']);
            }
        }

        while (!empty($paginaActual['next'])) {
            log_message('info', 'Obteniendo siguiente página: ' . $paginaActual['next']);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $paginaActual['next']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headersAutorizacion103($apiToken));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            $nextResponse = curl_exec($ch);
            curl_close($ch);

            $paginaActual = json_decode($nextResponse, true);

            if (is_array($paginaActual)) {
                $paginasCrudas[] = $paginaActual;
                if (isset($paginaActual['results']) && is_array($paginaActual['results'])) {
                    $todosLosReclamos = array_merge($todosLosReclamos, $paginaActual['results']);
                }
            } else {
                break;
            }
        }

        return [
            'reclamos' => $todosLosReclamos,
            'paginas_crudas' => $paginasCrudas,
        ];
    }

    private function obtenerApiToken103(): ?string
    {
        $tokenModel = new Token103Model();
        return $tokenModel->obtenerApiToken();
    }

    private function headersAutorizacion103(string $apiToken): array
    {
        return [
            'Authorization: Token ' . $apiToken,
            'Accept: application/json',
            'Content-Type: application/json',
        ];
    }

    /**
     * Filtra reclamos de ALUMBRADO PÚBLICO nuevos, omitiendo existentes e inválidos.
     */
    private function filtrarReclamosAlumbradoNuevos(array $reclamosApi, int $ultimoMunicipalidadId): array
    {
        $reclamosFiltrados = [];
        $reclamosOmitidos = 0;
        $reclamosInvalidos = 0;

        foreach ($reclamosApi as $reclamo) {
            if (!isset($reclamo['motivo']['tipo']) || $reclamo['motivo']['tipo'] !== 'ALUMBRADO PÚBLICO') {
                continue;
            }

            if ($this->esReclamoEstadoInvalido($reclamo['estado_nombre'] ?? null)) {
                $reclamosInvalidos++;
                continue;
            }

            $reclamoMapeado = $this->mapearReclamo($reclamo);
            $idReclamo = (int)$reclamoMapeado['municipalidad_id'];

            if ($idReclamo > $ultimoMunicipalidadId) {
                $reclamosFiltrados[] = $reclamoMapeado;
            } else {
                $reclamosOmitidos++;
            }
        }

        return [
            'reclamos' => $reclamosFiltrados,
            'reclamos_omitidos' => $reclamosOmitidos,
            'reclamos_invalidos' => $reclamosInvalidos,
            'total_alumbrado' => count($reclamosFiltrados) + $reclamosOmitidos + $reclamosInvalidos,
        ];
    }

    /**
     * Si la ficha fue corregida localmente, conserva esos campos y aplica del 103
     * solo estado/cierre/fechas (y prioridad recalculada).
     */
    private function fusionarSyncConFichaLocal(array $existente, array $mapeado103): array
    {
        if ((int) ($existente['ficha_editada'] ?? 0) !== 1) {
            $mapeado103['prioridad'] = ReclamoPrioridadService::evaluarPrioridad(
                array_merge($existente, $mapeado103)
            );
            return $mapeado103;
        }

        $camposFichaProtegidos = [
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

        $resultado = $mapeado103;
        foreach ($camposFichaProtegidos as $campo) {
            if (array_key_exists($campo, $existente)) {
                $resultado[$campo] = $existente[$campo];
            }
        }

        // Mantener la marca para no perder la protección en próximos syncs
        $resultado['ficha_editada'] = 1;

        $resultado['prioridad'] = ReclamoPrioridadService::evaluarPrioridad(
            array_merge($existente, $resultado)
        );

        return $resultado;
    }

    /**
     * Determina si un reclamo no debe sincronizarse por su estado.
     */
    private function esReclamoEstadoInvalido($estado): bool
    {
        return trim((string)$estado) === self::ESTADO_INVALIDO_SINCRONIZACION;
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

        $fechaInicio = $this->convertirFechaApi($reclamoApi['fecha_inicio'] ?? null);
        $fechaModificacion = $this->convertirFechaApi($reclamoApi['fecha_modificacion'] ?? null);

        $mapeado = [
            'municipalidad_id' => (string)$reclamoApi['id'],
            'municipalidad_tipo' => $reclamoApi['motivo']['tipo'] ?? 'ALUMBRADO PÚBLICO',
            'municipalidad_motivo' => $reclamoApi['motivo']['nombre'] ?? 'No especificado',
            'municipalidad_fechaInicio' => $fechaInicio,
            'municipalidad_fechaModificacion' => $fechaModificacion,
            'municipalidad_recepcion' => null, // No viene en la API
            'municipalidad_estado' => $estado,
            'municipalidad_telefono' => isset($reclamoApi['telefono']) ? (string)$reclamoApi['telefono'] : null,
            'municipalidad_domicilio' => $reclamoApi['calle']['nombre'] ?? '',
            'municipalidad_numeroDomicilio' => (string)($reclamoApi['calle_altura'] ?? ''),
            'municipalidad_entreCalleUno' => $reclamoApi['desde_calle']['nombre'] ?? '',
            'municipalidad_entreCalleDos' => $reclamoApi['hasta_calle']['nombre'] ?? '',
            'municipalidad_ciudadano' => null, // No viene en la API
            'municipalidad_descripcion' => isset($reclamoApi['descripcion']) ? (string)$reclamoApi['descripcion'] : null,
            'prioridad' => 'Baja', // Asignar prioridad baja por defecto para reclamos sincronizados
            'origen' => ReclamoModel::ORIGEN_103,
        ];

        // En el 103, Completado implica cierre formal → cerrado=1 en nuestra BD
        if ($estado === 'Completado') {
            $mapeado['cerrado'] = 1;
            $mapeado['fecha_cierre'] = $fechaModificacion ?? $fechaInicio;
        }

        return $mapeado;
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

