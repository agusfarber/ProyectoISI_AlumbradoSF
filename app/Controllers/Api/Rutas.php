<?php

/*
Este es el controlador para manejar todo lo relacionado a las rutas, su guardado en la base de datos y demás
En este controlador se va a manejar no solo el guardado en la tabla de rutas, sino también los guardados en la tabla de ruta_reclamo



Aca debe estar el algoritmo que genera las hojas de ruta también.

Para hacer las hojas de ruta se tiene que tener en cuenta:
* El usuario ingresará primeramente la cantidad de reclamos que quiere incluir en la hoja de ruta (este dato será pedido desde el front, archivo rutas.php)
* Tambien le dará la opción el sistema al usuario de seleccionar reclamos que quiera incluir en la hoja de ruta si o si (esto se pedirá desde el front,  archivo rutas.php), y si no quiere usar esta opción, simplemente los reclamos se seleccionan automáticamente por el algoritmo
* También, el sistema les va a dar la opción de si quieren seleccionar cual va a ser el primer reclamo del recorrido en la hoja de ruta (esto se pedirá desde el front), si el usuraio no lo selecciona, el sistema asigna uno que ya te voy a especificar como lo va a seleccionar.
    * Para seleccionar el primer reclamo del recorrido en caso de que el usuario no lo haya especificado manualmente:
        * Se debe darles prioridad primero a reclamos de prioridad
            1-Alta
            2-Baja
        * Además, luego de haber pasado ese criterio, debe seleccionarse el reclamo que esté más cercano al tanque de agua de la ciudad de San Francisco, Córdoba. Sus coordenadas son: -31.426516, -62.110954


Puede haber casos en los que el usuario ingrese que quiere que la hoja de ruta tenga 15 reclamos
* Luego que seleccione unos 6 reclamos de manera manual que quiera que estén incluidos si o si en la hoja de ruta (en ese caso hay 9 reclamos que faltaran seleccionarse, los cuales el algoritmo seleccionara automaticmante en base a los criterios descriptos luego)


(esto para los reclamos que el algoritmo selecciona automáticamente en caso de que el usuario no los haya seleccionado manualmente antes)
La hoja de ruta automática, para seleccionar que reclamos va a incluir debe tener en cuenta:
* Debe priorizar primero los reclamos con prioridad alta, luego los de prioridad baja.
    Es decir, por ejemplo, si el usuario elije que la hoja de ruta tenga 10 reclamos:
        Si hay más de 10 reclamos con prioridad alta actualmente en el sistema, todos los reclamos de la hoja de ruta en este caso van a tener prioridad alta logicamente
        Si hay 5 reclamos de prioridad alta, y hay muchos de prioridad baja. El sistema seleccionará si o si los de prioridad alta, y 5 de prioridad baja (en base a proximidad geográfica algunos van a ser mas convenientes que otros, 
        en este caso debe seleccionar 5 de prioridad baja que se encuentren más cercanos a los otros que se hayan seleccionado previamente y cercanos entre si también) para completar los 10 (cantidad que el usuario pidió que tenga la hoja de ruta)

* Luego el orden del recorrido de los reclamos que el algoritmo haya seleccionado. Debe ser en base a las proximidades geográficas de los mismos en base a sus coordenadas.



(quisiera que toda esta lógica la hagamos solamente para el caso de la hoja de ruta de google maps por ahora, no hagamos nada de mapbox)
(es decir, el algoritmo hagamoslo con las apis de google maps para hacer todos los calculos y obtención de datos necesarios)

(hay que tener en cuenta de que hay reclamos que tienen direcciones personalizadas en la base de datos, en la tabla DireccionModel - 
En estos casos, debe usarse esa dirección personalizada para hacer los cálculos pertinentes, y no las que proporciona la api de google maps - 
Para el resto de reclamos que no tengan direcciones personalizadas, usemos las que la api de google maps da por defecto)


*/

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\RutaModel;
use App\Models\Ruta_reclamoModel;
use App\Models\ReclamoModel;
use App\Models\DireccionModel;
use App\Models\CuadrillaModel;
use App\Models\Tiempo_promedio_motivoModel;
use App\Models\RutaEjecucionModel;
use App\Libraries\RutaEjecucionHistorialService;

class Rutas extends ResourceController
{
    protected $modelName = 'App\Models\RutaModel';
    protected $format = 'json';
    private $tieneEstadoEjecucion = false;
    private $tieneInicioEjecucionAt = false;

    // API Keys para cálculo de rutas
    private $googleMapsApiKey = 'AIzaSyAOCwr8_hWX4aBE2JTHxREP7gUrYLadCgg';
    private $mapboxApiKey = 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ajJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw';

    public function __construct()
    {
        // Configurar zona horaria de Argentina
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $db = \Config\Database::connect();
        $this->tieneEstadoEjecucion = $db->fieldExists('estado_ejecucion', 'ruta');
        $this->tieneInicioEjecucionAt = $db->fieldExists('inicio_ejecucion_at', 'ruta');
    }

    private function rutaEstaFinalizada(?array $ruta): bool
    {
        if (! $ruta || ! $this->tieneEstadoEjecucion) {
            return false;
        }

        return strtolower(trim((string) ($ruta['estado_ejecucion'] ?? ''))) === 'finalizada';
    }

    private function errorSiRutaFinalizada(?array $ruta)
    {
        if ($this->rutaEstaFinalizada($ruta)) {
            return $this->failForbidden('La hoja de ruta está finalizada y no puede modificarse.');
        }

        return null;
    }

    private function errorSiRutaEnEjecucion(?array $ruta)
    {
        if ($ruta && $this->tieneEstadoEjecucion && $this->normalizarEstadoEjecucion($ruta) === 'en ejecución') {
            return $this->failForbidden(
                'No se puede cambiar la cuadrilla mientras la hoja de ruta está en ejecución.'
            );
        }

        return null;
    }

    /**
     * Reclamos de la hoja que siguen en estado Asignado vuelven a Recibido (estado previo a la asignación).
     */
    private function revertirReclamosAsignadosDeRutaARecibido(int $rutaId): int
    {
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamoModel     = new ReclamoModel();
        $reclamosRuta     = $rutaReclamoModel->where('ruta_id', $rutaId)->findAll();
        $actualizados     = 0;
        $ahora            = date('Y-m-d H:i:s');

        foreach ($reclamosRuta as $rutaReclamo) {
            $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
            if ($reclamo && ($reclamo['municipalidad_estado'] ?? '') === 'Asignado') {
                $reclamoModel->update($rutaReclamo['reclamo_id'], [
                    'municipalidad_estado'            => 'Recibido',
                    'municipalidad_fechaModificacion' => $ahora,
                ]);
                $actualizados++;
            }
        }

        return $actualizados;
    }

    /**
     * Reclamos de la hoja en estado Recibido pasan a Asignado (al vincular la hoja a una cuadrilla).
     */
    private function marcarReclamosRecibidosDeRutaComoAsignados(int $rutaId): int
    {
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamoModel     = new ReclamoModel();
        $reclamosRuta     = $rutaReclamoModel->where('ruta_id', $rutaId)->findAll();
        $actualizados     = 0;
        $ahora            = date('Y-m-d H:i:s');

        foreach ($reclamosRuta as $rutaReclamo) {
            $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
            if ($reclamo && ($reclamo['municipalidad_estado'] ?? '') === 'Recibido') {
                $reclamoModel->update($rutaReclamo['reclamo_id'], [
                    'municipalidad_estado'           => 'Asignado',
                    'municipalidad_fechaModificacion' => $ahora,
                ]);
                $actualizados++;
            }
        }

        return $actualizados;
    }

    /**
     * Si el reclamo ya está en alguna hoja activa (no finalizada), devuelve esa membresía.
     */
    private function membresiaReclamoEnRutaActiva(int $reclamoId): ?array
    {
        $db  = \Config\Database::connect();
        $row = $db->table('ruta_reclamo rr')
            ->select('rr.ruta_id, r.nombre')
            ->join('ruta r', 'r.id = rr.ruta_id')
            ->where('rr.reclamo_id', $reclamoId)
            ->where("COALESCE(r.estado_ejecucion, '') != 'finalizada'", null, false)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Otra hoja de ruta activa (no finalizada) ya asignada a la cuadrilla.
     */
    private function hojaActivaEnCuadrilla(int $cuadrillaId, ?int $excluirRutaId = null): ?array
    {
        $builder = $this->model->where('cuadrilla_id', $cuadrillaId);

        if ($this->tieneEstadoEjecucion) {
            $builder->where("(COALESCE(estado_ejecucion,'') <> 'finalizada')", null, false);
        } else {
            $builder->where('asignada', 1);
        }

        if ($excluirRutaId !== null) {
            $builder->where('id !=', $excluirRutaId);
        }

        $otra = $builder->first();

        return $otra ?: null;
    }

    /**
     * Genera el siguiente nombre incremental: R1, R2, R3, etc.
     * También contempla hojas viejas "Hoja de Ruta N" para no reiniciar la secuencia.
     */
    private function generarNombreIncrementalHojaRuta(): string
    {
        $maxNum = 0;
        $filas  = $this->model->select('nombre')->findAll();

        foreach ($filas as $fila) {
            $nombre = trim((string) ($fila['nombre'] ?? ''));
            if (preg_match('/^R(\d+)$/i', $nombre, $coincidencias)
                || preg_match('/^Hoja de Ruta (\d+)$/iu', $nombre, $coincidencias)
            ) {
                $maxNum = max($maxNum, (int) $coincidencias[1]);
            }
        }

        return 'R' . ($maxNum + 1);
    }

    private function errorSiCuadrillaConOtraHoja(int $cuadrillaId, ?int $excluirRutaId = null)
    {
        $otra = $this->hojaActivaEnCuadrilla($cuadrillaId, $excluirRutaId);
        if ($otra === null) {
            return null;
        }

        $nombre = $otra['nombre'] ?? ('Hoja #' . $otra['id']);

        return $this->failValidationErrors(
            'La cuadrilla ya tiene asignada la hoja de ruta "' . $nombre . '". Desasígnela antes de asignar otra.'
        );
    }

    public function index()
    {
        $rutas = $this->model->select('ruta.*, cuadrilla.nombre as cuadrilla_nombre')
                            ->join('cuadrilla', 'cuadrilla.id = ruta.cuadrilla_id', 'left')
                            ->where("(COALESCE(ruta.estado_ejecucion,'') <> 'finalizada')", null, false)
                            ->findAll();

        return $this->respond($this->enriquecerRutasCantidadReclamosPorDomicilio($rutas));
    }

    public function show($id = null)
    {
        $ruta = $this->model->select('ruta.*, cuadrilla.nombre as cuadrilla_nombre')
                           ->join('cuadrilla', 'cuadrilla.id = ruta.cuadrilla_id', 'left')
                           ->find($id);

        if (! $ruta) {
            return $this->failNotFound('Ruta no encontrada');
        }

        if ($this->tieneEstadoEjecucion && $this->normalizarEstadoEjecucion($ruta) === 'en ejecución') {
            $ruta['ruta_ejecucion_activa_id'] = RutaEjecucionHistorialService::findActiveEjecucionIdByRutaId((int) $ruta['id']);
        }

        $ruta['cantidadReclamos'] = $this->cantidadReclamosPorDomicilioRuta((int) $ruta['id']);

        return $this->respond($ruta);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        
        // Validar datos obligatorios
        if (empty($data['cantidadReclamos'])) {
            return $this->failValidationErrors('Faltan datos obligatorios (cantidadReclamos).');
        }

        // Nombre automático incremental
        $data['nombre'] = $this->generarNombreIncrementalHojaRuta();
        $data['color'] = $data['color'] ?? '#FF6B35';
        $data['asignada'] = 0; // No asignada hasta que se le asigne una cuadrilla
        $data['cuadrilla_id'] = $data['cuadrilla_id'] ?? null;
        $data['tiempoEstimado'] = '00:00:00';
        $data['fecha'] = date('Y-m-d H:i:s');

        $rutaId = $this->model->insert($data);
        
        if ($rutaId === false) {
            return $this->failServerError('Error al crear la ruta.');
        }

        $rutaCreada = $this->model->find($rutaId);
        return $this->respondCreated($rutaCreada);
    }

    public function update($id = null)
    {
        $data = $this->request->getJSON(true);

        if (! $id || empty($data)) {
            return $this->failValidationErrors('Faltan datos obligatorios.');
        }

        $rutaExistente = $this->model->find($id);
        $err = $this->errorSiRutaFinalizada($rutaExistente);
        if ($err !== null) {
            return $err;
        }

        $actualizado = $this->model->update($id, $data);
        
        if ($actualizado === false) {
            return $this->failServerError('Error al actualizar la ruta.');
        }

        $rutaActualizada = $this->model->find($id);
        return $this->respond($rutaActualizada);
    }

    public function delete($id = null)
    {
        $rutaExistente = $id ? $this->model->find($id) : null;
        if (! $id || ! $rutaExistente) {
            return $this->failNotFound('Ruta no encontrada.');
        }

        $err = $this->errorSiRutaFinalizada($rutaExistente);
        if ($err !== null) {
            return $err;
        }

        if ($this->tieneEstadoEjecucion && $this->normalizarEstadoEjecucion($rutaExistente) === 'en ejecución') {
            return $this->failForbidden(
                'No se puede eliminar la hoja de ruta mientras está en ejecución.'
            );
        }

        // Liberar reclamos Asignado → Recibido antes de quitar el vínculo
        $this->revertirReclamosAsignadosDeRutaARecibido((int) $id);

        $rutaReclamoModel = new Ruta_reclamoModel();
        $rutaReclamoModel->where('ruta_id', $id)->delete();

        $this->model->delete($id);
        return $this->respondDeleted(['mensaje' => 'Ruta eliminada con éxito.']);
    }

    /**
     * Genera una hoja de ruta optimizada
     */
    public function generarRuta()
    {
        $data = $this->request->getJSON(true);
        
        // Validar datos obligatorios
        if (empty($data['cantidadReclamos'])) {
            return $this->failValidationErrors('Faltan datos obligatorios (cantidadReclamos).');
        }

        $nombre = $this->generarNombreIncrementalHojaRuta();
        $color = $data['color'] ?? '#FF6B35';
        $cantidadReclamos = (int)$data['cantidadReclamos'];
        $cuadrillaId = !empty($data['cuadrilla_id']) ? (int) $data['cuadrilla_id'] : null;
        $reclamosManuales = $data['reclamosManuales'] ?? [];
        $primerReclamoManual = $data['primerReclamoManual'] ?? null;
        $modoManual = $data['modoManual'] ?? false;

        try {
            // Obtener todos los reclamos disponibles
            $reclamoModel = new ReclamoModel();
            $direccionModel = new DireccionModel();
            
            $reclamos = $reclamoModel->findAllActivos();
            
            $rutaOptimizada = [];
            
            // Si es modo manual (ruta editada), usar los reclamos en el orden especificado
            if ($modoManual && !empty($reclamosManuales)) {
                // Obtener coordenadas solo para los reclamos seleccionados
                $reclamosSeleccionados = [];
                foreach ($reclamosManuales as $reclamoId) {
                    $reclamo = array_filter($reclamos, function($r) use ($reclamoId) {
                        return $r['id'] == $reclamoId;
                    });
                    
                    if (!empty($reclamo)) {
                        $reclamosSeleccionados[] = array_values($reclamo)[0];
                    }
                }
                
                // Obtener coordenadas
                $reclamosConCoordenadas = $this->obtenerCoordenadasReclamos($reclamosSeleccionados, $direccionModel);
                
                // NO optimizar - mantener el orden exacto del usuario
                $rutaOptimizada = $reclamosConCoordenadas;
                
            } else {
                // Modo automático - algoritmo original
                // Filtrar reclamos que no están en otras rutas activas
                $reclamosDisponibles = $this->filtrarReclamosDisponibles($reclamos);
                
                if (count($reclamosDisponibles) < $cantidadReclamos) {
                    return $this->failValidationErrors('No hay suficientes reclamos disponibles. Disponibles: ' . count($reclamosDisponibles) . ', Solicitados: ' . $cantidadReclamos);
                }

                // Obtener coordenadas para todos los reclamos
                $reclamosConCoordenadas = $this->obtenerCoordenadasReclamos($reclamosDisponibles, $direccionModel);

                if ($this->contarUnidadesDomicilio($reclamosConCoordenadas) < $cantidadReclamos) {
                    return $this->failValidationErrors(
                        'No hay suficientes domicilios disponibles. Domicilios: '
                        . $this->contarUnidadesDomicilio($reclamosConCoordenadas)
                        . ', Solicitados: ' . $cantidadReclamos
                    );
                }
                
                // Seleccionar reclamos para la ruta
                $reclamosSeleccionados = $this->seleccionarReclamosParaRuta(
                    $reclamosConCoordenadas, 
                    $cantidadReclamos, 
                    $reclamosManuales, 
                    $primerReclamoManual
                );

                // Optimizar orden de la ruta
                $rutaOptimizada = $this->optimizarOrdenRuta($reclamosSeleccionados);
            }

            if ($cuadrillaId) {
                $errCuadrilla = $this->errorSiCuadrillaConOtraHoja($cuadrillaId);
                if ($errCuadrilla !== null) {
                    return $errCuadrilla;
                }
                $errOperativa = $this->errorSiCuadrillaNoOperativa($cuadrillaId);
                if ($errOperativa !== null) {
                    return $errOperativa;
                }
            }

            // Crear la ruta en la base de datos
            $rutaData = [
                'nombre' => $nombre,
                'color' => $color,
                'cantidadReclamos' => $this->contarUnidadesDomicilio($rutaOptimizada),
                'asignada' => $cuadrillaId ? 1 : 0,
                'cuadrilla_id' => $cuadrillaId,
                'tiempoEstimado' => $this->calcularTiempoEstimado($rutaOptimizada),
                'fecha' => date('Y-m-d H:i:s')
            ];
            if ($this->tieneEstadoEjecucion) {
                $rutaData['estado_ejecucion'] = $cuadrillaId ? 'asignada' : null;
            }
            if ($this->tieneInicioEjecucionAt) {
                $rutaData['inicio_ejecucion_at'] = null;
            }

            $rutaId = $this->model->insert($rutaData);
            
            if ($rutaId === false) {
                return $this->failServerError('Error al crear la ruta.');
            }

            // Guardar los reclamos de la ruta
            $rutaReclamoModel = new Ruta_reclamoModel();
            foreach ($rutaOptimizada as $posicion => $reclamo) {
                $rutaReclamoModel->insert([
                    'ruta_id' => $rutaId,
                    'reclamo_id' => $reclamo['id'],
                    'posicion' => $posicion + 1
                ]);
            }

            // Si ya se asigna a cuadrilla al crear, pasar reclamos Recibido → Asignado
            // (evita el doble POST generar+asignar y estados inconsistentes)
            if ($cuadrillaId) {
                $this->marcarReclamosRecibidosDeRutaComoAsignados((int) $rutaId);
            }

            // Obtener la ruta creada con información adicional
            $rutaCreada = $this->model->select('ruta.*, cuadrilla.nombre as cuadrilla_nombre')
                                     ->join('cuadrilla', 'cuadrilla.id = ruta.cuadrilla_id', 'left')
                                     ->find($rutaId);

            return $this->respondCreated([
                'ruta' => $rutaCreada,
                'reclamos' => $rutaOptimizada
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al generar ruta: ' . $e->getMessage());
            return $this->failServerError('Error interno al generar la ruta: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene los reclamos de una ruta específica
     */
    public function getReclamosRuta($id = null)
    {
        if (!$id) {
            return $this->failValidationErrors('ID de ruta requerido.');
        }

        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamoModel = new ReclamoModel();
        $direccionModel = new DireccionModel();

        $reclamosRuta = $rutaReclamoModel->where('ruta_id', $id)
                                        ->orderBy('posicion', 'ASC')
                                        ->findAll();

        $reclamosConDetalles = [];
        foreach ($reclamosRuta as $rutaReclamo) {
            $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
            if ($reclamo) {
                // Obtener coordenadas del reclamo
                $coordenadas = $this->obtenerCoordenadasReclamo($reclamo, $direccionModel);
                $reclamo['coordenadas'] = $coordenadas;
                $reclamo['posicion'] = $rutaReclamo['posicion'];
                $reclamosConDetalles[] = $reclamo;
            }
        }

        $ruta      = $this->model->find($id);
        $sesiones  = [];
        $estadoRuta = $ruta && $this->tieneEstadoEjecucion
            ? $this->normalizarEstadoEjecucion($ruta)
            : '';
        $incluirSesiones = in_array($estadoRuta, ['en ejecución', 'asignada'], true);
        if ($incluirSesiones) {
            $idsReclamos = [];
            foreach ($reclamosConDetalles as $rc) {
                $idr = (int) ($rc['id'] ?? 0);
                if ($idr > 0) {
                    $idsReclamos[] = $idr;
                }
            }
            try {
                // Por reclamo_id: incluye tiempos de hojas/ejecuciones anteriores del mismo reclamo
                $sesiones = RutaEjecucionHistorialService::computeSesionesReparacionPorReclamoIds($idsReclamos);
            } catch (\Throwable $e) {
                log_message('error', 'computeSesionesReparacionPorReclamoIds: ' . $e->getMessage());
                $sesiones = [];
            }
        }

        foreach ($reclamosConDetalles as &$reclamo) {
            if ($incluirSesiones) {
                $rid                         = (int) $reclamo['id'];
                $reclamo['sesion_reparacion'] = $sesiones[$rid] ?? [
                    'activo'               => false,
                    'acumulado_ms'         => 0,
                    'inicio_segmento_at'   => null,
                ];
            }
        }
        unset($reclamo);

        return $this->respond($reclamosConDetalles);
    }

    /**
     * IDs de reclamos vinculados a hojas de ruta no finalizadas.
     */
    private function idsReclamosEnRutasActivas(): array
    {
        $db   = \Config\Database::connect();
        $rows = $db->table('ruta_reclamo rr')
            ->select('rr.reclamo_id')
            ->join('ruta r', 'r.id = rr.ruta_id')
            ->where("COALESCE(r.estado_ejecucion, '') != 'finalizada'", null, false)
            ->get()
            ->getResultArray();

        return array_map('intval', array_column($rows, 'reclamo_id'));
    }

    /**
     * Filtra reclamos que no están en ninguna ruta (asignada o no asignada) Y que no están completados
     */
    private function filtrarReclamosDisponibles($reclamos)
    {
        $reclamosEnRutasIds = $this->idsReclamosEnRutasActivas();

        // Filtrar reclamos disponibles: NO en ninguna ruta activa Y NO completados
        return array_filter($reclamos, function ($reclamo) use ($reclamosEnRutasIds) {
            $estaEnRuta     = in_array((int) $reclamo['id'], $reclamosEnRutasIds, true);
            $estaCompletado = ($reclamo['municipalidad_estado'] ?? '') === 'Completado';

            return ! $estaEnRuta && ! $estaCompletado;
        });
    }

    /**
     * La cuadrilla debe tener operarios y al menos uno con permisos de gestión.
     */
    private function errorSiCuadrillaNoOperativa(int $cuadrillaId)
    {
        $cuadrillaOperariosModel = new \App\Models\CuadrillaOperariosModel();
        $asignaciones            = $cuadrillaOperariosModel->where('cuadrilla_id', $cuadrillaId)->findAll();

        if (empty($asignaciones)) {
            return $this->failValidationErrors(
                'La cuadrilla no tiene operarios asignados. Asigná operarios antes de darle una hoja de ruta.'
            );
        }

        foreach ($asignaciones as $asig) {
            if ((int) ($asig['es_jefe'] ?? 0) === 1) {
                return null;
            }
        }

        return $this->failValidationErrors(
            'La cuadrilla debe tener al menos un operario con permisos de gestión.'
        );
    }

    /**
     * Clave única de domicilio (calle + número). Sin domicilio, un reclamo = una unidad.
     */
    private function claveDomicilioReclamo(array $reclamo): string
    {
        $domicilio = mb_strtolower(trim($reclamo['municipalidad_domicilio'] ?? ''));
        $numero    = mb_strtolower(trim($reclamo['municipalidad_numeroDomicilio'] ?? ''));

        if ($domicilio !== '') {
            return 'dom:' . $domicilio . '|' . $numero;
        }

        return 'id:' . ($reclamo['id'] ?? '0');
    }

    private function idsReclamos(array $reclamos): array
    {
        return array_map('intval', array_column($reclamos, 'id'));
    }

    private function contarUnidadesDomicilio(array $reclamos): int
    {
        $claves = [];
        foreach ($reclamos as $reclamo) {
            $claves[$this->claveDomicilioReclamo($reclamo)] = true;
        }

        return count($claves);
    }

    private function cantidadReclamosPorDomicilioRuta(int $rutaId): int
    {
        $cantidades = $this->calcularCantidadesReclamosPorDomicilioParaRutas([$rutaId]);

        return $cantidades[$rutaId] ?? 0;
    }

    private function enriquecerRutasCantidadReclamosPorDomicilio(array $rutas): array
    {
        if ($rutas === []) {
            return $rutas;
        }

        $rutaIds    = array_map('intval', array_column($rutas, 'id'));
        $cantidades = $this->calcularCantidadesReclamosPorDomicilioParaRutas($rutaIds);

        foreach ($rutas as &$ruta) {
            $id = (int) ($ruta['id'] ?? 0);
            if (isset($cantidades[$id])) {
                $ruta['cantidadReclamos'] = $cantidades[$id];
            }
        }
        unset($ruta);

        return $rutas;
    }

    private function calcularCantidadesReclamosPorDomicilioParaRutas(array $rutaIds): array
    {
        if ($rutaIds === []) {
            return [];
        }

        $db   = \Config\Database::connect();
        $rows = $db->table('ruta_reclamo rr')
            ->select('rr.ruta_id, r.id, r.municipalidad_domicilio, r.municipalidad_numeroDomicilio')
            ->join('reclamo r', 'r.id = rr.reclamo_id')
            ->whereIn('rr.ruta_id', $rutaIds)
            ->get()
            ->getResultArray();

        $porRuta = [];
        foreach ($rows as $row) {
            $rutaId = (int) $row['ruta_id'];
            if (! isset($porRuta[$rutaId])) {
                $porRuta[$rutaId] = [];
            }
            $porRuta[$rutaId][] = $row;
        }

        $resultado = [];
        foreach ($porRuta as $rutaId => $reclamos) {
            $resultado[$rutaId] = $this->contarUnidadesDomicilio($reclamos);
        }

        return $resultado;
    }

    private function filtrarReclamosMismoDomicilio(array $reclamoRef, array $pool): array
    {
        $clave = $this->claveDomicilioReclamo($reclamoRef);

        return array_values(array_filter($pool, function ($reclamo) use ($clave) {
            return $this->claveDomicilioReclamo($reclamo) === $clave;
        }));
    }

    /**
     * Agrega todos los reclamos del mismo domicilio (elegibles en $poolCompleto) y los quita del pool de trabajo.
     */
    private function agregarUnidadDomicilio(array &$seleccionados, array &$pool, array $reclamoRef, array $poolCompleto): void
    {
        $grupo    = $this->filtrarReclamosMismoDomicilio($reclamoRef, $poolCompleto);
        $idsGrupo = $this->idsReclamos($grupo);
        $idsYa    = $this->idsReclamos($seleccionados);

        foreach ($grupo as $reclamo) {
            if (! in_array((int) $reclamo['id'], $idsYa, true)) {
                $seleccionados[] = $reclamo;
                $idsYa[]         = (int) $reclamo['id'];
            }
        }

        $pool = array_values(array_filter($pool, function ($reclamo) use ($idsGrupo) {
            return ! in_array((int) $reclamo['id'], $idsGrupo, true);
        }));
    }

    private function limitarReclamosPorUnidadesDomicilio(array $reclamos, int $cantidadUnidades): array
    {
        $resultado = [];
        $claves    = [];

        foreach ($reclamos as $reclamo) {
            $clave = $this->claveDomicilioReclamo($reclamo);
            if (! isset($claves[$clave])) {
                if (count($claves) >= $cantidadUnidades) {
                    continue;
                }
                $claves[$clave] = true;
            }
            $resultado[] = $reclamo;
        }

        return $resultado;
    }

    private function construirUnidadesDomicilioDesdeReclamos(array $reclamos): array
    {
        $mapa = [];

        foreach ($reclamos as $reclamo) {
            $clave = $this->claveDomicilioReclamo($reclamo);
            if (! isset($mapa[$clave])) {
                $mapa[$clave] = [
                    'clave'    => $clave,
                    'reclamo'  => $reclamo,
                    'reclamos' => [],
                ];
            }
            $mapa[$clave]['reclamos'][] = $reclamo;
        }

        return array_values($mapa);
    }

    private function expandirUnidadesDomicilioAReclamos(array $unidades): array
    {
        $resultado = [];

        foreach ($unidades as $unidad) {
            $reclamos = $unidad['reclamos'];
            usort($reclamos, function ($a, $b) {
                return (int) ($b['municipalidad_id'] ?? 0) <=> (int) ($a['municipalidad_id'] ?? 0);
            });
            foreach ($reclamos as $reclamo) {
                $resultado[] = $reclamo;
            }
        }

        return $resultado;
    }

    private function encontrarUnidadMasCercana(array $unidades, array $punto): ?array
    {
        $distanciaMinima   = PHP_FLOAT_MAX;
        $unidadMasCercana = null;

        foreach ($unidades as $unidad) {
            $reclamo = $unidad['reclamo'] ?? null;
            if (! $reclamo || ! isset($reclamo['coordenadas'])) {
                continue;
            }

            $distancia = $this->calcularDistancia(
                $punto['lat'],
                $punto['lng'],
                $reclamo['coordenadas']['lat'],
                $reclamo['coordenadas']['lng']
            );

            if ($distancia < $distanciaMinima) {
                $distanciaMinima   = $distancia;
                $unidadMasCercana = $unidad;
            }
        }

        return $unidadMasCercana;
    }

    private function optimizarOrdenUnidadesDomicilio(array $unidades): array
    {
        if (empty($unidades)) {
            return [];
        }

        $rutaOptimizada    = [];
        $unidadesRestantes = $unidades;
        $tanqueAgua        = $this->obtenerPuntoBase();

        $primerUnidad = $this->encontrarUnidadMasCercana($unidadesRestantes, $tanqueAgua);

        if ($primerUnidad) {
            $rutaOptimizada[] = $primerUnidad;
            $unidadActual     = $primerUnidad;
            $unidadesRestantes = array_values(array_filter($unidadesRestantes, function ($u) use ($primerUnidad) {
                return $u['clave'] !== $primerUnidad['clave'];
            }));
        } else {
            $unidadActual      = array_shift($unidadesRestantes);
            $rutaOptimizada[]  = $unidadActual;
        }

        while (! empty($unidadesRestantes)) {
            $punto = $unidadActual['reclamo']['coordenadas'] ?? null;
            if (! $punto) {
                break;
            }

            $unidadMasCercana = $this->encontrarUnidadMasCercana($unidadesRestantes, $punto);

            if ($unidadMasCercana) {
                $rutaOptimizada[] = $unidadMasCercana;
                $unidadActual     = $unidadMasCercana;
                $unidadesRestantes = array_values(array_filter($unidadesRestantes, function ($u) use ($unidadMasCercana) {
                    return $u['clave'] !== $unidadMasCercana['clave'];
                }));
            } else {
                break;
            }
        }

        return $rutaOptimizada;
    }

    /**
     * Obtiene coordenadas para una lista de reclamos
     */
    private function obtenerCoordenadasReclamos($reclamos, $direccionModel)
    {
        $reclamosConCoordenadas = [];
        
        foreach ($reclamos as $reclamo) {
            $coordenadas = $this->obtenerCoordenadasReclamo($reclamo, $direccionModel);
            if ($coordenadas) {
                $reclamo['coordenadas'] = $coordenadas;
                $reclamosConCoordenadas[] = $reclamo;
            }
        }
        
        return $reclamosConCoordenadas;
    }

    /**
     * Obtiene coordenadas para un reclamo específico
     */
    private function obtenerCoordenadasReclamo($reclamo, $direccionModel)
    {
        // Buscar dirección personalizada primero
        if ($reclamo['municipalidad_domicilio'] && $reclamo['municipalidad_numeroDomicilio']) {
            $direccionPersonalizada = $direccionModel->where('domicilio', $reclamo['municipalidad_domicilio'])
                                                   ->where('numero_domicilio', $reclamo['municipalidad_numeroDomicilio'])
                                                   ->first();
            
            if ($direccionPersonalizada && $direccionPersonalizada['latitud'] && $direccionPersonalizada['longitud']) {
                return [
                    'lat' => (float)$direccionPersonalizada['latitud'],
                    'lng' => (float)$direccionPersonalizada['longitud'],
                    'esPersonalizada' => true
                ];
            }
        }
        
        // Si no hay dirección personalizada, usar geocodificación de Google Maps
        // En un entorno real, aquí se haría la llamada a la API de Google Maps
        // Por ahora, retornamos null para reclamos sin coordenadas
        return null;
    }

    /**
     * Selecciona reclamos para la ruta basándose en prioridad y proximidad
     * Prioridad: Alta > Baja (Media se trata como Baja)
     */
    private function seleccionarReclamosParaRuta($reclamos, $cantidad, $reclamosManuales, $primerReclamoManual)
    {
        $reclamosSeleccionados = [];
        $poolCompleto          = array_values($reclamos);
        
        // Agregar reclamos manuales primero (incluye todos los del mismo domicilio)
        foreach ($reclamosManuales as $reclamoId) {
            $reclamo = array_filter($reclamos, function ($r) use ($reclamoId) {
                return $r['id'] == $reclamoId;
            });
            
            if (! empty($reclamo)) {
                $ref         = array_values($reclamo)[0];
                $poolTrabajo = array_values(array_filter($poolCompleto, function ($r) use ($reclamosSeleccionados) {
                    return ! in_array((int) $r['id'], $this->idsReclamos($reclamosSeleccionados), true);
                }));
                if (! empty($poolTrabajo)) {
                    $this->agregarUnidadDomicilio($reclamosSeleccionados, $poolTrabajo, $ref, $poolCompleto);
                }
            }
        }
        
        // Si ya tenemos suficientes unidades de domicilio, retornar
        if ($this->contarUnidadesDomicilio($reclamosSeleccionados) >= $cantidad) {
            return $this->limitarReclamosPorUnidadesDomicilio($reclamosSeleccionados, $cantidad);
        }

        // Filtrar reclamos ya seleccionados
        $reclamosDisponibles = array_filter($reclamos, function ($reclamo) use ($reclamosSeleccionados) {
            return ! in_array((int) $reclamo['id'], $this->idsReclamos($reclamosSeleccionados), true);
        });

        // Separar reclamos por prioridad (solo Alta y Baja)
        $reclamosAlta = array_filter($reclamosDisponibles, function ($r) {
            return ($r['prioridad'] ?? 'Baja') === 'Alta';
        });
        $reclamosBaja = array_filter($reclamosDisponibles, function ($r) {
            return ($r['prioridad'] ?? 'Baja') === 'Baja';
        });

        // Calcular cuántas unidades de domicilio necesitamos
        $cantidadNecesaria = $cantidad - $this->contarUnidadesDomicilio($reclamosSeleccionados);

        // Seleccionar reclamos por prioridad
        $reclamosSeleccionados = $this->seleccionarPorPrioridad(
            $reclamosSeleccionados,
            $reclamosAlta,
            $reclamosBaja,
            $cantidadNecesaria,
            $poolCompleto
        );

        return $reclamosSeleccionados;
    }

    /**
     * Selecciona reclamos por prioridad (solo Alta y Baja)
     * LÓGICA:
     * 1. Si hay >= N Alta: Selecciona los N Alta que formen la ruta más corta (vecino más cercano desde tanque)
     * 2. Si hay < N Alta: Incluye todos Alta + los Baja más cercanos a esos Alta
     * 3. Si no hay Alta: Selecciona solo Baja
     */
    private function seleccionarPorPrioridad($reclamosSeleccionados, $reclamosAlta, $reclamosBaja, $cantidadNecesaria, $poolCompleto = [])
    {
        if (empty($poolCompleto)) {
            $poolCompleto = array_merge(array_values($reclamosAlta), array_values($reclamosBaja));
        }

        $unidadesIniciales = $this->contarUnidadesDomicilio($reclamosSeleccionados);
        $cantidadAlta      = $this->contarUnidadesDomicilio(array_values($reclamosAlta));
        
        // CASO 1: Hay suficientes Alta para llenar la ruta completa
        if ($cantidadAlta >= $cantidadNecesaria) {
            $reclamosSeleccionadosAlta = $this->seleccionarReclamosCercanos(array_values($reclamosAlta), $cantidadNecesaria, $poolCompleto);
            foreach ($reclamosSeleccionadosAlta as $reclamo) {
                if (! in_array((int) $reclamo['id'], $this->idsReclamos($reclamosSeleccionados), true)) {
                    $reclamosSeleccionados[] = $reclamo;
                }
            }

            return $reclamosSeleccionados;
        }
        
        // CASO 2: Hay algunos Alta pero no suficientes, completar con Baja
        if ($cantidadAlta > 0) {
            $poolAlta = array_values($reclamosAlta);
            while (! empty($poolAlta)) {
                $this->agregarUnidadDomicilio($reclamosSeleccionados, $poolAlta, $poolAlta[0], $poolCompleto);
            }

            $reclamosBaja = array_values(array_filter($reclamosBaja, function ($r) use ($reclamosSeleccionados) {
                return ! in_array((int) $r['id'], $this->idsReclamos($reclamosSeleccionados), true);
            }));
            
            $cantidadBajaNecesaria = $cantidadNecesaria - ($this->contarUnidadesDomicilio($reclamosSeleccionados) - $unidadesIniciales);
            
            if ($cantidadBajaNecesaria > 0) {
                $reclamosBajaSeleccionados = $this->seleccionarReclamosCercanosAGrupo(
                    $reclamosBaja,
                    array_values($reclamosAlta),
                    $cantidadBajaNecesaria,
                    $poolCompleto
                );
                foreach ($reclamosBajaSeleccionados as $reclamo) {
                    if (! in_array((int) $reclamo['id'], $this->idsReclamos($reclamosSeleccionados), true)) {
                        $reclamosSeleccionados[] = $reclamo;
                    }
                }
            }
            
            return $reclamosSeleccionados;
        }
        
        // CASO 3: No hay Alta, solo Baja
        $reclamosBajaSeleccionados = $this->seleccionarReclamosCercanos(array_values($reclamosBaja), $cantidadNecesaria, $poolCompleto);
        foreach ($reclamosBajaSeleccionados as $reclamo) {
            if (! in_array((int) $reclamo['id'], $this->idsReclamos($reclamosSeleccionados), true)) {
                $reclamosSeleccionados[] = $reclamo;
            }
        }
        
        return $reclamosSeleccionados;
    }

    /**
     * Ordena reclamos por proximidad al tanque de agua (para selección inicial)
     */
    private function ordenarPorProximidadAlTanque($reclamos)
    {
        if (empty($reclamos)) {
            return [];
        }
        
        $tanqueAgua = $this->obtenerPuntoBase();
        
        usort($reclamos, function($a, $b) use ($tanqueAgua) {
            $distA = $this->calcularDistancia(
                $tanqueAgua['lat'], $tanqueAgua['lng'],
                $a['coordenadas']['lat'], $a['coordenadas']['lng']
            );
            $distB = $this->calcularDistancia(
                $tanqueAgua['lat'], $tanqueAgua['lng'],
                $b['coordenadas']['lat'], $b['coordenadas']['lng']
            );
            return $distA <=> $distB;
        });
        
        return $reclamos;
    }
    
    /**
     * Ordena reclamos por proximidad a un grupo de reclamos ya seleccionados
     */
    private function ordenarPorProximidadAGrupo($reclamos, $grupoBase)
    {
        if (empty($reclamos)) {
            return [];
        }
        
        usort($reclamos, function($a, $b) use ($grupoBase) {
            $distA = $this->calcularDistanciaMinimaAGrupo($a, $grupoBase);
            $distB = $this->calcularDistanciaMinimaAGrupo($b, $grupoBase);
            return $distA <=> $distB;
        });
        
        return $reclamos;
    }

    /**
     * Selecciona N reclamos cercanos usando algoritmo de vecino más cercano desde el tanque de agua
     * Este método se usa cuando todos los reclamos tienen la misma prioridad
     */
    private function seleccionarReclamosCercanos($reclamos, $cantidad, $poolCompleto = null)
    {
        if (empty($reclamos) || $cantidad <= 0) {
            return [];
        }

        $poolCompleto = $poolCompleto ?? array_values($reclamos);
        
        $reclamosSeleccionados = [];
        $reclamosRestantes     = array_values($reclamos);
        $tanqueAgua            = $this->obtenerPuntoBase();
        
        $primerReclamo = $this->encontrarReclamoMasCercano($reclamosRestantes, $tanqueAgua);
        if (! $primerReclamo) {
            return [];
        }
        
        $this->agregarUnidadDomicilio($reclamosSeleccionados, $reclamosRestantes, $primerReclamo, $poolCompleto);
        $reclamoActual = $reclamosSeleccionados[count($reclamosSeleccionados) - 1];
        
        while ($this->contarUnidadesDomicilio($reclamosSeleccionados) < $cantidad && ! empty($reclamosRestantes)) {
            $reclamoMasCercano = $this->encontrarReclamoMasCercano($reclamosRestantes, $reclamoActual['coordenadas']);
            
            if ($reclamoMasCercano) {
                $this->agregarUnidadDomicilio($reclamosSeleccionados, $reclamosRestantes, $reclamoMasCercano, $poolCompleto);
                $reclamoActual = $reclamosSeleccionados[count($reclamosSeleccionados) - 1];
            } else {
                break;
            }
        }
        
        return $reclamosSeleccionados;
    }

    /**
     * Selecciona N reclamos cercanos a un grupo base usando algoritmo de vecino más cercano
     * Este método se usa para completar con Baja cuando ya hay algunos Alta seleccionados
     */
    private function seleccionarReclamosCercanosAGrupo($reclamos, $grupoBase, $cantidad, $poolCompleto = null)
    {
        if (empty($reclamos) || $cantidad <= 0) {
            return [];
        }

        $poolCompleto = $poolCompleto ?? array_values($reclamos);
        
        $reclamosSeleccionados = [];
        $reclamosRestantes     = array_values($reclamos);
        
        $primerReclamo   = null;
        $distanciaMinima = PHP_FLOAT_MAX;
        
        foreach ($reclamosRestantes as $reclamo) {
            $distancia = $this->calcularDistanciaMinimaAGrupo($reclamo, $grupoBase);
            if ($distancia < $distanciaMinima) {
                $distanciaMinima = $distancia;
                $primerReclamo   = $reclamo;
            }
        }
        
        if (! $primerReclamo) {
            return [];
        }
        
        $this->agregarUnidadDomicilio($reclamosSeleccionados, $reclamosRestantes, $primerReclamo, $poolCompleto);
        $reclamoActual = $reclamosSeleccionados[count($reclamosSeleccionados) - 1];
        
        while ($this->contarUnidadesDomicilio($reclamosSeleccionados) < $cantidad && ! empty($reclamosRestantes)) {
            $reclamoMasCercano = $this->encontrarReclamoMasCercano($reclamosRestantes, $reclamoActual['coordenadas']);
            
            if ($reclamoMasCercano) {
                $this->agregarUnidadDomicilio($reclamosSeleccionados, $reclamosRestantes, $reclamoMasCercano, $poolCompleto);
                $reclamoActual = $reclamosSeleccionados[count($reclamosSeleccionados) - 1];
            } else {
                break;
            }
        }
        
        return $reclamosSeleccionados;
    }

    /**
     * Ordena reclamos por proximidad a los ya seleccionados (DEPRECADO - mantener por compatibilidad)
     */
    private function ordenarPorProximidad($reclamos, $reclamosSeleccionados, $primerReclamoManual)
    {
        if (empty($reclamos)) {
            return [];
        }
        
        // Si no hay reclamos seleccionados aún, ordenar por proximidad al tanque de agua
        if (empty($reclamosSeleccionados)) {
            return $this->ordenarPorProximidadAlTanque($reclamos);
        } else {
            // Ordenar por proximidad al grupo de reclamos ya seleccionados
            return $this->ordenarPorProximidadAGrupo($reclamos, $reclamosSeleccionados);
        }
    }

    /**
     * Calcula la distancia mínima de un reclamo a un grupo de reclamos
     */
    private function calcularDistanciaMinimaAGrupo($reclamo, $grupoReclamos)
    {
        $distanciaMinima = PHP_FLOAT_MAX;
        
        foreach ($grupoReclamos as $reclamoGrupo) {
            $distancia = $this->calcularDistancia(
                $reclamo['coordenadas']['lat'], $reclamo['coordenadas']['lng'],
                $reclamoGrupo['coordenadas']['lat'], $reclamoGrupo['coordenadas']['lng']
            );
            
            if ($distancia < $distanciaMinima) {
                $distanciaMinima = $distancia;
            }
        }
        
        return $distanciaMinima;
    }

    /**
     * Encuentra el reclamo más cercano a un punto específico
     */
    private function encontrarReclamoMasCercano($reclamos, $punto)
    {
        $distanciaMinima = PHP_FLOAT_MAX;
        $reclamoMasCercano = null;
        
        foreach ($reclamos as $reclamo) {
            if (!isset($reclamo['coordenadas'])) continue;
            
            $distancia = $this->calcularDistancia(
                $punto['lat'], $punto['lng'],
                $reclamo['coordenadas']['lat'], $reclamo['coordenadas']['lng']
            );
            
            if ($distancia < $distanciaMinima) {
                $distanciaMinima = $distancia;
                $reclamoMasCercano = $reclamo;
            }
        }
        
        return $reclamoMasCercano;
    }

    /**
     * Encuentra el reclamo más cercano a un grupo de reclamos
     */
    private function encontrarReclamoMasCercanoAGrupo($reclamos, $grupoReclamos)
    {
        $distanciaMinima = PHP_FLOAT_MAX;
        $reclamoMasCercano = null;
        
        foreach ($reclamos as $reclamo) {
            if (!isset($reclamo['coordenadas'])) continue;
            
            $distanciaTotal = 0;
            foreach ($grupoReclamos as $reclamoGrupo) {
                if (!isset($reclamoGrupo['coordenadas'])) continue;
                
                $distanciaTotal += $this->calcularDistancia(
                    $reclamo['coordenadas']['lat'], $reclamo['coordenadas']['lng'],
                    $reclamoGrupo['coordenadas']['lat'], $reclamoGrupo['coordenadas']['lng']
                );
            }
            
            $distanciaPromedio = $distanciaTotal / count($grupoReclamos);
            
            if ($distanciaPromedio < $distanciaMinima) {
                $distanciaMinima = $distanciaPromedio;
                $reclamoMasCercano = $reclamo;
            }
        }
        
        return $reclamoMasCercano;
    }

    /**
     * Optimiza el orden de la ruta usando algoritmo de vecino más cercano
     * El primer reclamo siempre será el más cercano al tanque de agua de San Francisco
     */
    private function optimizarOrdenRuta($reclamos)
    {
        if (empty($reclamos)) {
            return [];
        }

        $unidades          = $this->construirUnidadesDomicilioDesdeReclamos(array_values($reclamos));
        $unidadesOrdenadas = $this->optimizarOrdenUnidadesDomicilio($unidades);

        return $this->expandirUnidadesDomicilioAReclamos($unidadesOrdenadas);
    }

    /**
     * Calcula el tiempo de desplazamiento usando APIs de mapas (Google Maps primero, Mapbox como fallback)
     */
    private function calcularTiempoDesplazamientoConAPI($reclamos)
    {
        if (count($reclamos) < 2) {
            return ['exito' => false, 'error' => 'Se necesitan al menos 2 reclamos'];
        }

        // Intentar con Google Maps Directions API primero
        $resultadoGoogle = $this->calcularTiempoConGoogleMaps($reclamos);
        if ($resultadoGoogle['exito']) {
            return $resultadoGoogle;
        }

        log_message('info', "CALCULO TIEMPO ESTIMADO: Google Maps falló ({$resultadoGoogle['error']}), intentando con Mapbox...");

        // Fallback a Mapbox Directions API
        $resultadoMapbox = $this->calcularTiempoConMapbox($reclamos);
        if ($resultadoMapbox['exito']) {
            return $resultadoMapbox;
        }

        log_message('warning', "CALCULO TIEMPO ESTIMADO: Mapbox también falló ({$resultadoMapbox['error']})");

        // Si ambas APIs fallan, retornar error
        return ['exito' => false, 'error' => 'Ambas APIs fallaron'];
    }

    /**
     * Calcula el tiempo de desplazamiento usando Google Maps Directions API
     */
    private function calcularTiempoConGoogleMaps($reclamos)
    {
        try {
            $client = \Config\Services::curlrequest();
            $tiempoTotalMinutos = 0;
            $distanciaTotalKm = 0;
            $tramos = [];

            // Google Maps Directions API tiene límite de 25 waypoints
            // Para rutas largas, calculamos por tramos
            for ($i = 0; $i < count($reclamos) - 1; $i++) {
                $reclamoActual = $reclamos[$i];
                $reclamoSiguiente = $reclamos[$i + 1];

                if (!isset($reclamoActual['coordenadas']) || !isset($reclamoSiguiente['coordenadas'])) {
                    continue;
                }

                $origin = $reclamoActual['coordenadas']['lat'] . ',' . $reclamoActual['coordenadas']['lng'];
                $destination = $reclamoSiguiente['coordenadas']['lat'] . ',' . $reclamoSiguiente['coordenadas']['lng'];

                $url = 'https://maps.googleapis.com/maps/api/directions/json';
                $params = [
                    'origin' => $origin,
                    'destination' => $destination,
                    'mode' => 'driving',
                    'key' => $this->googleMapsApiKey,
                    'language' => 'es'
                ];

                $response = $client->get($url, ['query' => $params]);
                $data = json_decode($response->getBody(), true);

                if ($response->getStatusCode() !== 200 || $data['status'] !== 'OK') {
                    throw new \Exception('Google Maps API error: ' . ($data['status'] ?? 'Unknown'));
                }

                if (empty($data['routes']) || empty($data['routes'][0]['legs'])) {
                    throw new \Exception('No route found');
                }

                $leg = $data['routes'][0]['legs'][0];
                $distanciaMetros = $leg['distance']['value'] ?? 0;
                $tiempoSegundos = $leg['duration']['value'] ?? 0;

                $distanciaKm = $distanciaMetros / 1000;
                $tiempoMinutos = $tiempoSegundos / 60;

                $tiempoTotalMinutos += $tiempoMinutos;
                $distanciaTotalKm += $distanciaKm;

                $tramos[] = [
                    'reclamo_origen' => $reclamoActual['municipalidad_id'] ?? $reclamoActual['id'] ?? 'N/A',
                    'reclamo_destino' => $reclamoSiguiente['municipalidad_id'] ?? $reclamoSiguiente['id'] ?? 'N/A',
                    'distancia_km' => $distanciaKm,
                    'tiempo_minutos' => $tiempoMinutos
                ];
            }

            return [
                'exito' => true,
                'proveedor' => 'Google Maps',
                'tiempo_minutos' => $tiempoTotalMinutos,
                'distancia_km' => $distanciaTotalKm,
                'tramos' => $tramos
            ];

        } catch (\Exception $e) {
            log_message('error', 'Error al calcular tiempo con Google Maps: ' . $e->getMessage());
            return [
                'exito' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calcula el tiempo de desplazamiento usando Mapbox Directions API
     */
    private function calcularTiempoConMapbox($reclamos)
    {
        try {
            $client = \Config\Services::curlrequest();
            $tiempoTotalMinutos = 0;
            $distanciaTotalKm = 0;
            $tramos = [];

            // Mapbox Directions API requiere coordenadas en formato lng,lat
            for ($i = 0; $i < count($reclamos) - 1; $i++) {
                $reclamoActual = $reclamos[$i];
                $reclamoSiguiente = $reclamos[$i + 1];

                if (!isset($reclamoActual['coordenadas']) || !isset($reclamoSiguiente['coordenadas'])) {
                    continue;
                }

                // Mapbox usa formato lng,lat (al revés que Google)
                $coordinates = [
                    [$reclamoActual['coordenadas']['lng'], $reclamoActual['coordenadas']['lat']],
                    [$reclamoSiguiente['coordenadas']['lng'], $reclamoSiguiente['coordenadas']['lat']]
                ];

                $coordsString = implode(';', array_map(function($coord) {
                    return $coord[0] . ',' . $coord[1];
                }, $coordinates));

                $url = "https://api.mapbox.com/directions/v5/mapbox/driving/{$coordsString}";
                $params = [
                    'access_token' => $this->mapboxApiKey,
                    'geometries' => 'geojson',
                    'steps' => 'false'
                ];

                $response = $client->get($url, ['query' => $params]);
                $data = json_decode($response->getBody(), true);

                if ($response->getStatusCode() !== 200 || empty($data['routes'])) {
                    throw new \Exception('Mapbox API error: ' . ($data['message'] ?? 'Unknown'));
                }

                $route = $data['routes'][0];
                $distanciaMetros = $route['distance'] ?? 0;
                $tiempoSegundos = $route['duration'] ?? 0;

                $distanciaKm = $distanciaMetros / 1000;
                $tiempoMinutos = $tiempoSegundos / 60;

                $tiempoTotalMinutos += $tiempoMinutos;
                $distanciaTotalKm += $distanciaKm;

                $tramos[] = [
                    'reclamo_origen' => $reclamoActual['municipalidad_id'] ?? $reclamoActual['id'] ?? 'N/A',
                    'reclamo_destino' => $reclamoSiguiente['municipalidad_id'] ?? $reclamoSiguiente['id'] ?? 'N/A',
                    'distancia_km' => $distanciaKm,
                    'tiempo_minutos' => $tiempoMinutos
                ];
            }

            return [
                'exito' => true,
                'proveedor' => 'Mapbox',
                'tiempo_minutos' => $tiempoTotalMinutos,
                'distancia_km' => $distanciaTotalKm,
                'tramos' => $tramos
            ];

        } catch (\Exception $e) {
            log_message('error', 'Error al calcular tiempo con Mapbox: ' . $e->getMessage());
            return [
                'exito' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Punto de base operativa (tanque de agua de San Francisco, Córdoba).
     */
    private function obtenerPuntoBase(): array
    {
        return ['lat' => -31.426516, 'lng' => -62.110954];
    }

    /**
     * Calcula tiempo y distancia de un tramo entre dos coordenadas (Google → Mapbox → Haversine).
     */
    private function calcularTiempoTramo(array $origen, array $destino): array
    {
        if (! isset($origen['lat'], $origen['lng'], $destino['lat'], $destino['lng'])) {
            return [
                'exito' => false,
                'tiempo_minutos' => 0,
                'distancia_km' => 0,
                'proveedor' => null,
            ];
        }

        $resultadoGoogle = $this->calcularTiempoTramoGoogle($origen, $destino);
        if ($resultadoGoogle['exito']) {
            return $resultadoGoogle;
        }

        $resultadoMapbox = $this->calcularTiempoTramoMapbox($origen, $destino);
        if ($resultadoMapbox['exito']) {
            return $resultadoMapbox;
        }

        $distanciaKm = $this->calcularDistancia(
            $origen['lat'],
            $origen['lng'],
            $destino['lat'],
            $destino['lng']
        );
        $tiempoMinutos = ($distanciaKm / 30) * 60;

        return [
            'exito' => true,
            'proveedor' => 'Haversine',
            'tiempo_minutos' => $tiempoMinutos,
            'distancia_km' => $distanciaKm,
        ];
    }

    /**
     * Calcula un tramo con Google Maps Directions API.
     */
    private function calcularTiempoTramoGoogle(array $origen, array $destino): array
    {
        try {
            $client = \Config\Services::curlrequest();
            $url = 'https://maps.googleapis.com/maps/api/directions/json';
            $params = [
                'origin' => $origen['lat'] . ',' . $origen['lng'],
                'destination' => $destino['lat'] . ',' . $destino['lng'],
                'mode' => 'driving',
                'key' => $this->googleMapsApiKey,
                'language' => 'es',
            ];

            $response = $client->get($url, ['query' => $params]);
            $data = json_decode($response->getBody(), true);

            if ($response->getStatusCode() !== 200 || ($data['status'] ?? '') !== 'OK') {
                throw new \Exception('Google Maps API error: ' . ($data['status'] ?? 'Unknown'));
            }

            if (empty($data['routes']) || empty($data['routes'][0]['legs'])) {
                throw new \Exception('No route found');
            }

            $leg = $data['routes'][0]['legs'][0];
            $distanciaKm = ($leg['distance']['value'] ?? 0) / 1000;
            $tiempoMinutos = ($leg['duration']['value'] ?? 0) / 60;

            return [
                'exito' => true,
                'proveedor' => 'Google Maps',
                'tiempo_minutos' => $tiempoMinutos,
                'distancia_km' => $distanciaKm,
            ];
        } catch (\Exception $e) {
            return [
                'exito' => false,
                'error' => $e->getMessage(),
                'tiempo_minutos' => 0,
                'distancia_km' => 0,
                'proveedor' => null,
            ];
        }
    }

    /**
     * Calcula un tramo con Mapbox Directions API.
     */
    private function calcularTiempoTramoMapbox(array $origen, array $destino): array
    {
        try {
            $client = \Config\Services::curlrequest();
            $coordsString = $origen['lng'] . ',' . $origen['lat'] . ';' . $destino['lng'] . ',' . $destino['lat'];
            $url = "https://api.mapbox.com/directions/v5/mapbox/driving/{$coordsString}";
            $params = [
                'access_token' => $this->mapboxApiKey,
                'geometries' => 'geojson',
                'steps' => 'false',
            ];

            $response = $client->get($url, ['query' => $params]);
            $data = json_decode($response->getBody(), true);

            if ($response->getStatusCode() !== 200 || empty($data['routes'])) {
                throw new \Exception('Mapbox API error: ' . ($data['message'] ?? 'Unknown'));
            }

            $route = $data['routes'][0];
            $distanciaKm = ($route['distance'] ?? 0) / 1000;
            $tiempoMinutos = ($route['duration'] ?? 0) / 60;

            return [
                'exito' => true,
                'proveedor' => 'Mapbox',
                'tiempo_minutos' => $tiempoMinutos,
                'distancia_km' => $distanciaKm,
            ];
        } catch (\Exception $e) {
            return [
                'exito' => false,
                'error' => $e->getMessage(),
                'tiempo_minutos' => 0,
                'distancia_km' => 0,
                'proveedor' => null,
            ];
        }
    }

    /**
     * Suma desplazamiento base → primer reclamo y último reclamo → base.
     */
    private function sumarDesplazamientoBase(array $reclamos): array
    {
        $tiempoMinutos = 0;
        $distanciaKm = 0;
        $tramos = [];

        if (empty($reclamos)) {
            return [
                'tiempo_minutos' => 0,
                'distancia_km' => 0,
                'tramos' => [],
            ];
        }

        $base = $this->obtenerPuntoBase();
        $primerReclamo = $reclamos[0];
        $ultimoReclamo = $reclamos[count($reclamos) - 1];

        if (isset($primerReclamo['coordenadas'])) {
            $tramoIda = $this->calcularTiempoTramo($base, $primerReclamo['coordenadas']);
            if ($tramoIda['exito']) {
                $tiempoMinutos += $tramoIda['tiempo_minutos'];
                $distanciaKm += $tramoIda['distancia_km'];
                $tramos[] = [
                    'origen' => 'Base',
                    'destino' => $primerReclamo['municipalidad_id'] ?? $primerReclamo['id'] ?? 'N/A',
                    'distancia_km' => $tramoIda['distancia_km'],
                    'tiempo_minutos' => $tramoIda['tiempo_minutos'],
                    'proveedor' => $tramoIda['proveedor'],
                ];
            }
        }

        if (isset($ultimoReclamo['coordenadas'])) {
            $tramoVuelta = $this->calcularTiempoTramo($ultimoReclamo['coordenadas'], $base);
            if ($tramoVuelta['exito']) {
                $tiempoMinutos += $tramoVuelta['tiempo_minutos'];
                $distanciaKm += $tramoVuelta['distancia_km'];
                $tramos[] = [
                    'origen' => $ultimoReclamo['municipalidad_id'] ?? $ultimoReclamo['id'] ?? 'N/A',
                    'destino' => 'Base',
                    'distancia_km' => $tramoVuelta['distancia_km'],
                    'tiempo_minutos' => $tramoVuelta['tiempo_minutos'],
                    'proveedor' => $tramoVuelta['proveedor'],
                ];
            }
        }

        return [
            'tiempo_minutos' => $tiempoMinutos,
            'distancia_km' => $distanciaKm,
            'tramos' => $tramos,
        ];
    }

    /**
     * Calcula la distancia entre dos puntos geográficos (fórmula de Haversine)
     * Usado como último fallback si las APIs de mapas fallan
     */
    private function calcularDistancia($lat1, $lng1, $lat2, $lng2)
    {
        $radioTierra = 6371; // Radio de la Tierra en kilómetros
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $radioTierra * $c;
    }

    /**
     * Calcula el tiempo estimado de la ruta usando promedios por motivo
     */
    private function calcularTiempoEstimado($reclamos)
    {
        if (empty($reclamos)) {
            return '00:00:00';
        }
        
        log_message('info', "CALCULO TIEMPO ESTIMADO: Iniciando cálculo para ruta con " . count($reclamos) . " reclamos");
        
        $tiempoTotalMinutos = 0;
        $promedioModel = new Tiempo_promedio_motivoModel();
        
        // Cargar todos los promedios en memoria para optimizar consultas
        $promedios = [];
        $promediosData = $promedioModel->findAll();
        foreach ($promediosData as $promedio) {
            $promedios[$promedio['motivo']] = $promedio;
        }
        
        log_message('info', 'CALCULO TIEMPO ESTIMADO: Promedios cargados - ' . count($promedios) . ' motivos con promedio registrado');
        if (count($promedios) > 0) {
            foreach ($promedios as $motivo => $promedio) {
                log_message('info', "  - Motivo: '{$motivo}' | Promedio: {$promedio['tiempo_promedio_minutos']} min | Registros: {$promedio['cantidad_registros']}");
            }
        }
        
        // Tiempo por reclamo según el promedio del motivo
        $detalleReclamos = [];
        foreach ($reclamos as $index => $reclamo) {
            $motivo = $reclamo['municipalidad_motivo'] ?? '';
            $reclamoId = $reclamo['municipalidad_id'] ?? $reclamo['id'] ?? 'N/A';
            $tiempoEstimado = 15; // Tiempo por defecto si no hay promedio
            $fuenteTiempo = 'defecto (15 min)';
            
            if (!empty($motivo) && isset($promedios[$motivo])) {
                // Usar el promedio calculado para este motivo
                $tiempoEstimado = (float) $promedios[$motivo]['tiempo_promedio_minutos'];
                $fuenteTiempo = "promedio calculado ({$tiempoEstimado} min)";
            } elseif (!empty($motivo)) {
                // Motivo existe pero no tiene promedio
                $fuenteTiempo = "defecto (15 min) - motivo sin promedio: '{$motivo}'";
            } else {
                $fuenteTiempo = "defecto (15 min) - motivo vacío";
            }
            
            $detalleReclamos[] = [
                'reclamo' => $reclamoId,
                'motivo' => $motivo,
                'tiempo' => $tiempoEstimado,
                'fuente' => $fuenteTiempo
            ];
            
            $tiempoTotalMinutos += $tiempoEstimado;
        }
        
        log_message('info', 'CALCULO TIEMPO ESTIMADO: Tiempos por reclamo:');
        foreach ($detalleReclamos as $detalle) {
            log_message('info', "  - Reclamo #{$detalle['reclamo']} | Motivo: '{$detalle['motivo']}' | Tiempo: {$detalle['tiempo']} min ({$detalle['fuente']})");
        }
        log_message('info', "CALCULO TIEMPO ESTIMADO: Tiempo total de reparación (sin desplazamiento): {$tiempoTotalMinutos} minutos");
        
        // Tiempo de desplazamiento: base → 1ª parada, entre reclamos y última parada → base
        $tiempoDesplazamientoTotal = 0;
        $distanciaTotal = 0;

        $desplazamientoBase = $this->sumarDesplazamientoBase($reclamos);
        $tiempoDesplazamientoTotal += $desplazamientoBase['tiempo_minutos'];
        $distanciaTotal += $desplazamientoBase['distancia_km'];

        foreach ($desplazamientoBase['tramos'] as $tramo) {
            log_message(
                'info',
                '  - Desplazamiento (' . ($tramo['proveedor'] ?? 'N/A') . '): '
                . $tramo['origen'] . ' → ' . $tramo['destino']
                . ' | Distancia: ' . round($tramo['distancia_km'], 2) . ' km'
                . ' | Tiempo: ' . round($tramo['tiempo_minutos'], 2) . ' min'
            );
        }
        
        if (count($reclamos) > 1) {
            // Intentar obtener tiempos usando Google Maps Directions API
            $resultadoAPI = $this->calcularTiempoDesplazamientoConAPI($reclamos);
            
            if ($resultadoAPI['exito']) {
                $tiempoDesplazamientoTotal += $resultadoAPI['tiempo_minutos'];
                $distanciaTotal += $resultadoAPI['distancia_km'];
                log_message('info', "CALCULO TIEMPO ESTIMADO: Tiempos obtenidos usando {$resultadoAPI['proveedor']} API");
                
                // Log detallado de cada tramo
                foreach ($resultadoAPI['tramos'] as $tramo) {
                    $reclamoActualId = $tramo['reclamo_origen'] ?? 'N/A';
                    $reclamoSiguienteId = $tramo['reclamo_destino'] ?? 'N/A';
                    log_message('info', "  - Desplazamiento ({$resultadoAPI['proveedor']}): Reclamo #{$reclamoActualId} → Reclamo #{$reclamoSiguienteId} | Distancia: " . round($tramo['distancia_km'], 2) . " km | Tiempo: " . round($tramo['tiempo_minutos'], 2) . " min");
                }
            } else {
                // Fallback: usar Haversine si las APIs fallan
                log_message('warning', "CALCULO TIEMPO ESTIMADO: APIs de mapas no disponibles, usando cálculo Haversine (línea recta)");
                for ($i = 0; $i < count($reclamos) - 1; $i++) {
                    $reclamoActual = $reclamos[$i];
                    $reclamoSiguiente = $reclamos[$i + 1];
                    
                    if (isset($reclamoActual['coordenadas']) && isset($reclamoSiguiente['coordenadas'])) {
                        $distancia = $this->calcularDistancia(
                            $reclamoActual['coordenadas']['lat'], $reclamoActual['coordenadas']['lng'],
                            $reclamoSiguiente['coordenadas']['lat'], $reclamoSiguiente['coordenadas']['lng']
                        );
                        
                        // Estimación: 30 km/h promedio en ciudad
                        $tiempoDesplazamiento = ($distancia / 30) * 60; // Convertir a minutos
                        $tiempoDesplazamientoTotal += $tiempoDesplazamiento;
                        $distanciaTotal += $distancia;
                        
                        $reclamoActualId = $reclamoActual['municipalidad_id'] ?? $reclamoActual['id'] ?? 'N/A';
                        $reclamoSiguienteId = $reclamoSiguiente['municipalidad_id'] ?? $reclamoSiguiente['id'] ?? 'N/A';
                        log_message('info', "  - Desplazamiento (Haversine): Reclamo #{$reclamoActualId} → Reclamo #{$reclamoSiguienteId} | Distancia: " . round($distancia, 2) . " km | Tiempo: " . round($tiempoDesplazamiento, 2) . " min");
                    }
                }
            }
        }
        
        $tiempoTotalMinutos += $tiempoDesplazamientoTotal;
        log_message('info', "CALCULO TIEMPO ESTIMADO: Tiempo total de desplazamiento (incl. base): " . round($tiempoDesplazamientoTotal, 2) . " minutos | Distancia total: " . round($distanciaTotal, 2) . " km");
        log_message('info', "CALCULO TIEMPO ESTIMADO: Tiempo total estimado (reparación + desplazamiento): {$tiempoTotalMinutos} minutos");
        
        // Convertir a formato HH:MM:SS
        $tiempoTotalMinutos = (int) round($tiempoTotalMinutos);
        $horas = intdiv($tiempoTotalMinutos, 60);
        $minutos = $tiempoTotalMinutos % 60;
        
        $tiempoFormateado = sprintf('%02d:%02d:00', $horas, $minutos);
        log_message('info', "CALCULO TIEMPO ESTIMADO: Tiempo final formateado: {$tiempoFormateado} ({$horas}h {$minutos}m)");
        
        return $tiempoFormateado;
    }

    /**
     * Genera una vista previa de la ruta usando el mismo algoritmo del backend
     */
    public function vistaPreviaRuta()
    {
        $data = $this->request->getJSON(true);
        
        if (!$data) {
            return $this->failValidationErrors('Datos requeridos para la vista previa.');
        }

        $cantidadReclamos = $data['cantidadReclamos'] ?? 5;
        $reclamosManuales = $data['reclamosManuales'] ?? [];
        $primerReclamoManual = $data['primerReclamoManual'] ?? null;

        try {
            $reclamoModel = new ReclamoModel();
            $direccionModel = new DireccionModel();
            $reclamos = $reclamoModel->findAllActivos();
            $reclamosDisponibles = $this->filtrarReclamosDisponibles($reclamos);
            $reclamosConCoordenadas = $this->obtenerCoordenadasReclamos($reclamosDisponibles, $direccionModel);
            $reclamosSeleccionados = $this->seleccionarReclamosParaRuta(
                $reclamosConCoordenadas,
                $cantidadReclamos,
                $reclamosManuales,
                $primerReclamoManual
            );
            $rutaOptimizada = $this->optimizarOrdenRuta($reclamosSeleccionados);

            return $this->respond([
                'rutaOptimizada' => $rutaOptimizada,
                'cantidadReclamos' => $this->contarUnidadesDomicilio($rutaOptimizada),
                'domiciliosDisponibles' => $this->contarUnidadesDomicilio($reclamosConCoordenadas),
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al generar vista previa: ' . $e->getMessage());
            return $this->failServerError('Error interno al generar la vista previa: ' . $e->getMessage());
        }
    }

    /**
     * Cantidad de domicilios disponibles para armar hojas (mismo criterio que generar/vista previa).
     */
    public function getDomiciliosDisponibles()
    {
        try {
            $reclamoModel = new ReclamoModel();
            $reclamos     = $reclamoModel->findAllActivos();
            $disponibles  = array_values($this->filtrarReclamosDisponibles($reclamos));

            return $this->respond([
                'domiciliosDisponibles' => $this->contarUnidadesDomicilio($disponibles),
                'reclamosDisponibles'   => count($disponibles),
                'idsReclamosEnRutasActivas' => $this->idsReclamosEnRutasActivas(),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener domicilios disponibles: ' . $e->getMessage());
            return $this->failServerError('Error interno al obtener domicilios disponibles.');
        }
    }

    /**
     * Calcula la distancia total de una ruta
     */
    private function calcularDistanciaTotal($reclamos)
    {
        if (empty($reclamos)) {
            return 0;
        }

        $distanciaTotal = 0;
        $base = $this->obtenerPuntoBase();
        $primerReclamo = $reclamos[0];
        $ultimoReclamo = $reclamos[count($reclamos) - 1];

        if (isset($primerReclamo['coordenadas'])) {
            $distanciaTotal += $this->calcularDistancia(
                $base['lat'], $base['lng'],
                $primerReclamo['coordenadas']['lat'], $primerReclamo['coordenadas']['lng']
            );
        }

        for ($i = 0; $i < count($reclamos) - 1; $i++) {
            $actual = $reclamos[$i];
            $siguiente = $reclamos[$i + 1];

            if (! isset($actual['coordenadas'], $siguiente['coordenadas'])) {
                continue;
            }
            
            $distanciaTotal += $this->calcularDistancia(
                $actual['coordenadas']['lat'], $actual['coordenadas']['lng'],
                $siguiente['coordenadas']['lat'], $siguiente['coordenadas']['lng']
            );
        }

        if (isset($ultimoReclamo['coordenadas'])) {
            $distanciaTotal += $this->calcularDistancia(
                $ultimoReclamo['coordenadas']['lat'], $ultimoReclamo['coordenadas']['lng'],
                $base['lat'], $base['lng']
            );
        }

        return round($distanciaTotal, 2);
    }

    /**
     * Asigna una hoja de ruta a una cuadrilla
     */
    public function asignarACuadrilla()
    {
        $data = $this->request->getJSON(true);
        
        // Validar datos obligatorios
        if (empty($data['ruta_id']) || empty($data['cuadrilla_id'])) {
            return $this->failValidationErrors('Faltan datos obligatorios (ruta_id, cuadrilla_id).');
        }

        $rutaId = $data['ruta_id'];
        $cuadrillaId = $data['cuadrilla_id'];

        try {
            // Verificar que la ruta existe
            $ruta = $this->model->find($rutaId);
            if (! $ruta) {
                return $this->failNotFound('Ruta no encontrada.');
            }

            $err = $this->errorSiRutaFinalizada($ruta);
            if ($err !== null) {
                return $err;
            }

            $err = $this->errorSiRutaEnEjecucion($ruta);
            if ($err !== null) {
                return $err;
            }

            // Verificar que la cuadrilla existe
            $cuadrillaModel = new CuadrillaModel();
            $cuadrilla = $cuadrillaModel->find($cuadrillaId);
            if (!$cuadrilla) {
                return $this->failNotFound('Cuadrilla no encontrada.');
            }

            $errCuadrilla = $this->errorSiCuadrillaConOtraHoja((int) $cuadrillaId, (int) $rutaId);
            if ($errCuadrilla !== null) {
                return $errCuadrilla;
            }

            $errOperativa = $this->errorSiCuadrillaNoOperativa((int) $cuadrillaId);
            if ($errOperativa !== null) {
                return $errOperativa;
            }

            // Actualizar la ruta para asignarla a la cuadrilla
            $datosActualizacionRuta = [
                'asignada' => 1,
                'cuadrilla_id' => $cuadrillaId
            ];
            if ($this->tieneEstadoEjecucion) {
                $datosActualizacionRuta['estado_ejecucion'] = 'asignada';
            }
            if ($this->tieneInicioEjecucionAt) {
                $datosActualizacionRuta['inicio_ejecucion_at'] = null;
            }

            $actualizado = $this->model->update($rutaId, $datosActualizacionRuta);

            if ($actualizado === false) {
                return $this->failServerError('Error al asignar la ruta a la cuadrilla.');
            }

            // Actualizar el estado de los reclamos de "Recibido" a "Asignado"
            $reclamosActualizados = $this->marcarReclamosRecibidosDeRutaComoAsignados((int) $rutaId);

            // Obtener la ruta actualizada con información de la cuadrilla
            $rutaActualizada = $this->model->select('ruta.*, cuadrilla.nombre as cuadrilla_nombre')
                                         ->join('cuadrilla', 'cuadrilla.id = ruta.cuadrilla_id', 'left')
                                         ->find($rutaId);

            return $this->respond([
                'mensaje' => 'Hoja de ruta asignada exitosamente a la cuadrilla.',
                'ruta' => $rutaActualizada,
                'reclamos_actualizados' => $reclamosActualizados
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al asignar ruta: ' . $e->getMessage());
            return $this->failServerError('Error interno al asignar la ruta: ' . $e->getMessage());
        }
    }

    /**
     * Desasigna una hoja de ruta de una cuadrilla
     */
    public function desasignarDeCuadrilla($rutaId = null)
    {
        if (!$rutaId) {
            return $this->failValidationErrors('ID de ruta requerido.');
        }

        try {
            // Verificar que la ruta existe
            $ruta = $this->model->find($rutaId);
            if (! $ruta) {
                return $this->failNotFound('Ruta no encontrada.');
            }

            $err = $this->errorSiRutaFinalizada($ruta);
            if ($err !== null) {
                return $err;
            }

            $err = $this->errorSiRutaEnEjecucion($ruta);
            if ($err !== null) {
                return $err;
            }

            // Actualizar la ruta para desasignarla
            $datosDesasignacion = [
                'asignada' => 0,
                'cuadrilla_id' => null
            ];
            if ($this->tieneEstadoEjecucion) {
                $datosDesasignacion['estado_ejecucion'] = null;
            }
            if ($this->tieneInicioEjecucionAt) {
                $datosDesasignacion['inicio_ejecucion_at'] = null;
            }

            $actualizado = $this->model->update($rutaId, $datosDesasignacion);

            if ($actualizado === false) {
                return $this->failServerError('Error al desasignar la ruta.');
            }

            $reclamosActualizados = $this->revertirReclamosAsignadosDeRutaARecibido($rutaId);

            $rutaActualizada = $this->model->find($rutaId);

            return $this->respond([
                'mensaje' => 'Hoja de ruta desasignada exitosamente.',
                'ruta' => $rutaActualizada,
                'reclamos_actualizados' => $reclamosActualizados
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al desasignar ruta: ' . $e->getMessage());
            return $this->failServerError('Error interno al desasignar la ruta: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene las rutas asignadas a la(s) cuadrilla(s) de un operario específico
     * CORRECCIÓN: Ahora obtiene de TODAS las cuadrillas del operario (por robustez)
     */
    public function getRutasPorOperario()
    {
        $session = \Config\Services::session();
        $userId = $session->get('user_id');

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        try {
            // CORRECCIÓN: Obtener TODAS las cuadrillas del operario (en lugar de solo la primera)
            $cuadrillaOperariosModel = new \App\Models\CuadrillaOperariosModel();
            $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $userId)->findAll();

            if (empty($asignaciones)) {
                return $this->respond([]);
            }

            // Extraer IDs de todas las cuadrillas
            $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');

            // Obtener rutas asignadas a cualquiera de esas cuadrillas (no finalizadas)
            $rutas = $this->model->select('ruta.*, cuadrilla.nombre as cuadrilla_nombre')
                                ->join('cuadrilla', 'cuadrilla.id = ruta.cuadrilla_id', 'left')
                                ->whereIn('ruta.cuadrilla_id', $cuadrillaIds)
                                ->where('ruta.asignada', 1)
                                ->where("(COALESCE(ruta.estado_ejecucion,'') <> 'finalizada')", null, false)
                                ->findAll();

            // Agregar bandera de jefe según la asignación del operario en esa cuadrilla
            $asignacionesPorCuadrilla = [];
            foreach ($asignaciones as $asignacion) {
                $asignacionesPorCuadrilla[$asignacion['cuadrilla_id']] = (int)($asignacion['es_jefe'] ?? 0);
            }

            foreach ($rutas as &$ruta) {
                $ruta['operario_es_jefe'] = (int)($asignacionesPorCuadrilla[$ruta['cuadrilla_id']] ?? 0);
                $ruta['estado_ejecucion'] = $this->normalizarEstadoEjecucion($ruta);
            }

            $rutas = $this->enriquecerRutasCantidadReclamosPorDomicilio($rutas);

            return $this->respond($rutas);

        } catch (\Exception $e) {
            log_message('error', 'Error al obtener rutas del operario: ' . $e->getMessage());
            return $this->failServerError('Error interno al obtener las rutas: ' . $e->getMessage());
        }
    }

    /**
     * Inicia la ejecución de una hoja de ruta (operario con permisos de gestión).
     */
    public function iniciarEjecucionOperario()
    {
        $session = \Config\Services::session();
        $userId = (int)$session->get('user_id');

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        $data = $this->request->getJSON(true);
        $rutaId = isset($data['ruta_id']) ? (int)$data['ruta_id'] : 0;
        if (!$rutaId) {
            return $this->failValidationErrors('Debe enviar ruta_id.');
        }

        try {
            $cuadrillaOperariosModel = new \App\Models\CuadrillaOperariosModel();
            $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $userId)->findAll();
            if (empty($asignaciones)) {
                return $this->failForbidden('No tiene cuadrillas asignadas.');
            }

            $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');
            $esJefePorCuadrilla = [];
            foreach ($asignaciones as $asig) {
                $esJefePorCuadrilla[$asig['cuadrilla_id']] = (int)($asig['es_jefe'] ?? 0);
            }

            $ruta = $this->model->where('id', $rutaId)
                ->whereIn('cuadrilla_id', $cuadrillaIds)
                ->where('asignada', 1)
                ->first();

            if (!$ruta) {
                return $this->failForbidden('No tiene permisos sobre esta hoja de ruta.');
            }

            if ((int)($esJefePorCuadrilla[$ruta['cuadrilla_id']] ?? 0) !== 1) {
                return $this->failForbidden('Solo un operario con permisos de gestión puede iniciar la ejecución de la hoja de ruta.');
            }

            if (!$this->tieneEstadoEjecucion) {
                return $this->failServerError('Falta la columna estado_ejecucion en tabla ruta.');
            }

            $estadoActual = $this->normalizarEstadoEjecucion($ruta);
            if ($estadoActual === 'en ejecución') {
                $rutaActual = $this->model->find($rutaId);
                if ($rutaActual) {
                    $rutaActual['estado_ejecucion'] = $this->normalizarEstadoEjecucion($rutaActual);
                }
                $rutaOut = $rutaActual ?? $ruta;
                $ejId    = RutaEjecucionHistorialService::findActiveEjecucionIdByRutaId($rutaId);

                return $this->respond([
                    'mensaje'           => 'La hoja de ruta ya está en ejecución.',
                    'ruta'              => $rutaOut,
                    'ruta_ejecucion_id' => $ejId,
                ]);
            }

            $ahora = date('Y-m-d H:i:s');
            $datosUpdate = ['estado_ejecucion' => 'en ejecución'];
            if ($this->tieneInicioEjecucionAt) {
                $datosUpdate['inicio_ejecucion_at'] = $ahora;
            }

            $this->model->update($rutaId, $datosUpdate);

            $ejecucionModel = new RutaEjecucionModel();
            $ejecucionModel->insert([
                'ruta_id'      => $rutaId,
                'cuadrilla_id' => ! empty($ruta['cuadrilla_id']) ? (int) $ruta['cuadrilla_id'] : null,
                'inicio_at'    => $ahora,
                'fin_at'       => null,
            ]);
            $rutaEjecucionId = (int) $ejecucionModel->getInsertID();
            RutaEjecucionHistorialService::insertEvent(
                $rutaEjecucionId,
                RutaEjecucionHistorialService::TIPO_RUTA_INICIO,
                null,
                $userId,
                null
            );

            $rutaActualizada = $this->model->find($rutaId);
            $rutaActualizada['estado_ejecucion'] = $this->normalizarEstadoEjecucion($rutaActualizada);
            $rutaActualizada['ruta_ejecucion_activa_id'] = $rutaEjecucionId;

            return $this->respond([
                'mensaje'              => 'Hoja de ruta iniciada en ejecución.',
                'ruta'                 => $rutaActualizada,
                'ruta_ejecucion_id'    => $rutaEjecucionId,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error al iniciar ejecución de ruta: ' . $e->getMessage());
            return $this->failServerError('Error interno al iniciar ejecución.');
        }
    }

    /**
     * Finaliza la ejecución de la hoja de ruta: cierra historial, desasigna cuadrilla y marca la ruta como finalizada.
     */
    public function finalizarEjecucionOperario()
    {
        $session = \Config\Services::session();
        $userId = (int) $session->get('user_id');

        if (! $userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        $data   = $this->request->getJSON(true);
        $rutaId = isset($data['ruta_id']) ? (int) $data['ruta_id'] : 0;
        if (! $rutaId) {
            return $this->failValidationErrors('Debe enviar ruta_id.');
        }

        try {
            $cuadrillaOperariosModel = new \App\Models\CuadrillaOperariosModel();
            $asignaciones            = $cuadrillaOperariosModel->where('usuario_id', $userId)->findAll();
            if (empty($asignaciones)) {
                return $this->failForbidden('No tiene cuadrillas asignadas.');
            }

            $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');
            $esJefePorCuadrilla = [];
            foreach ($asignaciones as $asig) {
                $esJefePorCuadrilla[$asig['cuadrilla_id']] = (int) ($asig['es_jefe'] ?? 0);
            }

            $ruta = $this->model->where('id', $rutaId)
                ->whereIn('cuadrilla_id', $cuadrillaIds)
                ->where('asignada', 1)
                ->first();

            if (! $ruta) {
                return $this->failForbidden('No tiene permisos sobre esta hoja de ruta.');
            }

            if ((int) ($esJefePorCuadrilla[$ruta['cuadrilla_id']] ?? 0) !== 1) {
                return $this->failForbidden('Solo un operario con permisos de gestión puede finalizar la ejecución de la hoja de ruta.');
            }

            if (! $this->tieneEstadoEjecucion) {
                return $this->failServerError('Falta la columna estado_ejecucion en tabla ruta.');
            }

            $estadoActual = $this->normalizarEstadoEjecucion($ruta);
            if ($estadoActual !== 'en ejecución') {
                return $this->failValidationErrors(['estado' => 'La hoja de ruta no está en ejecución.']);
            }

            $reclamosEnObra = RutaEjecucionHistorialService::findReclamosConObraActivaEnEjecucionActiva($rutaId);
            if ($reclamosEnObra !== []) {
                $refs = array_map(static function (array $r): string {
                    $mid = $r['municipalidad_id'] ?? null;

                    return ($mid !== null && $mid !== '') ? '#' . $mid : 'ID ' . $r['reclamo_id'];
                }, $reclamosEnObra);

                return $this->failValidationErrors([
                    'reclamos' => 'No se puede finalizar la hoja mientras haya reclamos con trabajo en curso. '
                        . 'Marcá cada uno como Pendiente o Completado antes de continuar: '
                        . implode(', ', $refs),
                ]);
            }

            $db             = \Config\Database::connect();
            $ejecucionModel = new RutaEjecucionModel();
            $ahoraFin       = date('Y-m-d H:i:s');

            $db->transStart();

            $ejec = $ejecucionModel->where('ruta_id', $rutaId)->where('fin_at', null)->orderBy('id', 'DESC')->first();
            if (! $ejec) {
                $ini = ($this->tieneInicioEjecucionAt && ! empty($ruta['inicio_ejecucion_at']))
                    ? $ruta['inicio_ejecucion_at']
                    : $ahoraFin;
                $ejecucionModel->insert([
                    'ruta_id'      => $rutaId,
                    'cuadrilla_id' => ! empty($ruta['cuadrilla_id']) ? (int) $ruta['cuadrilla_id'] : null,
                    'inicio_at'    => $ini,
                    'fin_at'       => null,
                ]);
                $ejec = ['id' => $ejecucionModel->getInsertID()];
            }

            $ejecId = (int) $ejec['id'];
            $ejecucionModel->update($ejecId, ['fin_at' => $ahoraFin]);

            RutaEjecucionHistorialService::insertEvent(
                $ejecId,
                RutaEjecucionHistorialService::TIPO_RUTA_FIN,
                null,
                $userId,
                null
            );

            $datosUpdate = [
                'estado_ejecucion' => 'finalizada',
                'asignada'         => 0,
                'cuadrilla_id'     => null,
            ];
            if ($this->tieneInicioEjecucionAt) {
                $datosUpdate['inicio_ejecucion_at'] = null;
            }
            $this->model->update($rutaId, $datosUpdate);

            $reclamosRevertidos = $this->revertirReclamosAsignadosDeRutaARecibido($rutaId);

            $db->transComplete();
            if ($db->transStatus() === false) {
                return $this->failServerError('Error al cerrar la ejecución de la hoja de ruta.');
            }

            $rutaActualizada = $this->model->select('ruta.*, cuadrilla.nombre as cuadrilla_nombre')
                ->join('cuadrilla', 'cuadrilla.id = ruta.cuadrilla_id', 'left')
                ->find($rutaId);
            $rutaActualizada['estado_ejecucion'] = $this->normalizarEstadoEjecucion($rutaActualizada);

            return $this->respond([
                'mensaje'              => 'Ejecución finalizada. La hoja quedó archivada y desasignada.',
                'ruta'                 => $rutaActualizada,
                'ruta_ejecucion_id'    => $ejecId,
                'reclamos_revertidos'  => $reclamosRevertidos,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error al finalizar ejecución de ruta: ' . $e->getMessage());
            return $this->failServerError('Error interno al finalizar ejecución.');
        }
    }

    /**
     * ID de ejecución activa (sin cerrar) para una hoja en curso. Operario de la cuadrilla o supervisor/admin.
     */
    public function getEjecucionActiva($rutaId = null)
    {
        $rutaId = (int) $rutaId;
        if (! $rutaId) {
            return $this->failValidationErrors('ID de ruta requerido.');
        }

        $session = \Config\Services::session();
        $userId  = (int) $session->get('user_id');
        $role    = (string) ($session->get('role') ?? '');

        if (! $userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        $ruta = $this->model->find($rutaId);
        if (! $ruta) {
            return $this->failNotFound('Ruta no encontrada.');
        }

        if ($role === '3') {
            $cuadrillaOperariosModel = new \App\Models\CuadrillaOperariosModel();
            $asignaciones             = $cuadrillaOperariosModel->where('usuario_id', $userId)->findAll();
            $cuadrillaIds             = array_column($asignaciones, 'cuadrilla_id');
            if (empty($ruta['cuadrilla_id']) || ! in_array((int) $ruta['cuadrilla_id'], array_map('intval', $cuadrillaIds), true)) {
                return $this->failForbidden('No tiene permisos sobre esta hoja de ruta.');
            }
        } elseif (! in_array($role, ['1', '2'], true)) {
            return $this->failForbidden('No autorizado.');
        }

        if ($this->normalizarEstadoEjecucion($ruta) !== 'en ejecución') {
            return $this->respond(['ruta_ejecucion_id' => null]);
        }

        $id = RutaEjecucionHistorialService::findActiveEjecucionIdByRutaId($rutaId);

        return $this->respond(['ruta_ejecucion_id' => $id]);
    }

    /**
     * Registra inicio o fin de trabajo sobre un reclamo durante una ejecución
     * (operario con permisos de gestión, ruta en ejecución).
     */
    public function registrarEventoEjecucionOperario()
    {
        $session = \Config\Services::session();
        $userId  = (int) $session->get('user_id');
        if (! $userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        $data      = $this->request->getJSON(true);
        $tipo      = isset($data['tipo']) ? (string) $data['tipo'] : '';
        $reclamoId = isset($data['reclamo_id']) ? (int) $data['reclamo_id'] : 0;

        $tiposOk = [
            RutaEjecucionHistorialService::TIPO_RECLAMO_INICIO,
            RutaEjecucionHistorialService::TIPO_RECLAMO_FIN,
        ];
        if (! in_array($tipo, $tiposOk, true) || $reclamoId <= 0) {
            return $this->failValidationErrors('tipo o reclamo_id inválidos.');
        }

        $cuadrillaOperariosModel = new \App\Models\CuadrillaOperariosModel();
        $asignaciones            = $cuadrillaOperariosModel->where('usuario_id', $userId)->findAll();
        if (empty($asignaciones)) {
            return $this->failForbidden('No tiene cuadrillas asignadas.');
        }

        $cuadrillaIds       = array_column($asignaciones, 'cuadrilla_id');
        $esJefePorCuadrilla = [];
        foreach ($asignaciones as $asig) {
            $esJefePorCuadrilla[$asig['cuadrilla_id']] = (int) ($asig['es_jefe'] ?? 0);
        }

        $vinculo = RutaEjecucionHistorialService::findRutaReclamoLinkRutaAsignada($reclamoId);
        if (! $vinculo) {
            return $this->failForbidden('El reclamo no está en una hoja de ruta asignada.');
        }

        $ruta = $this->model->where('id', (int) $vinculo['ruta_id'])
            ->whereIn('cuadrilla_id', $cuadrillaIds)
            ->where('asignada', 1)
            ->first();
        if (! $ruta) {
            return $this->failForbidden('No tiene permisos sobre esta hoja de ruta.');
        }

        if ((int) ($esJefePorCuadrilla[$ruta['cuadrilla_id']] ?? 0) !== 1) {
            return $this->failForbidden('Solo un operario con permisos de gestión puede registrar estos eventos.');
        }

        if ($this->normalizarEstadoEjecucion($ruta) !== 'en ejecución') {
            return $this->failForbidden('La hoja de ruta no está en ejecución.');
        }

        $ejId = RutaEjecucionHistorialService::findActiveEjecucionIdByRutaId((int) $ruta['id']);
        if (! $ejId) {
            return $this->failValidationErrors('No hay ejecución activa registrada para esta hoja.');
        }

        RutaEjecucionHistorialService::insertEvent($ejId, $tipo, $reclamoId, $userId, null);

        return $this->respond(['mensaje' => 'Evento registrado.']);
    }

    /**
     * Listado de ejecuciones finalizadas (historial) para supervisor o administrador.
     */
    public function historialEjecuciones()
    {
        $role = (string) (\Config\Services::session()->get('role') ?? '');
        if (! in_array($role, ['1', '2'], true)) {
            return $this->failForbidden('Solo supervisores pueden consultar el historial de ejecuciones.');
        }

        $db = \Config\Database::connect();
        $rows = $db->table('ruta_ejecucion re')
            ->select('re.id, re.ruta_id, re.cuadrilla_id, re.inicio_at, re.fin_at, r.nombre AS ruta_nombre, r.color AS ruta_color, c.nombre AS cuadrilla_nombre')
            ->join('ruta r', 'r.id = re.ruta_id', 'left')
            ->join('cuadrilla c', 'c.id = re.cuadrilla_id', 'left')
            ->where('re.fin_at IS NOT NULL', null, false)
            ->orderBy('re.fin_at', 'DESC')
            ->limit(500)
            ->get()
            ->getResultArray();

        return $this->respond($rows);
    }

    /**
     * Detalle de una ejecución (cabecera + eventos ordenados) para supervisor o administrador.
     */
    public function historialEjecucionDetalle($ejecucionId = null)
    {
        $role = (string) (\Config\Services::session()->get('role') ?? '');
        if (! in_array($role, ['1', '2'], true)) {
            return $this->failForbidden('Solo supervisores pueden consultar el historial de ejecuciones.');
        }

        $ejecucionId = (int) $ejecucionId;
        if (! $ejecucionId) {
            return $this->failValidationErrors('ID de ejecución requerido.');
        }

        $db = \Config\Database::connect();

        $cab = $db->table('ruta_ejecucion re')
            ->select('re.*, r.nombre AS ruta_nombre, r.color AS ruta_color, r.cantidadReclamos, c.nombre AS cuadrilla_nombre')
            ->join('ruta r', 'r.id = re.ruta_id', 'left')
            ->join('cuadrilla c', 'c.id = re.cuadrilla_id', 'left')
            ->where('re.id', $ejecucionId)
            ->get()
            ->getRowArray();

        if (! $cab) {
            return $this->failNotFound('Ejecución no encontrada.');
        }

        $eventos = $db->table('ruta_ejecucion_evento e')
            ->select('e.*, u.nombre AS usuario_nombre, u.foto_perfil AS usuario_foto_perfil, rec.municipalidad_id AS reclamo_municipalidad_id')
            ->join('usuario u', 'u.id = e.usuario_id', 'left')
            ->join('reclamo rec', 'rec.id = e.reclamo_id', 'left')
            ->where('e.ruta_ejecucion_id', $ejecucionId)
            ->orderBy('e.ocurrido_at', 'ASC')
            ->orderBy('e.id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($eventos as &$ev) {
            if (! empty($ev['metadata'])) {
                $decoded = json_decode($ev['metadata'], true);
                $ev['metadata'] = is_array($decoded) ? $decoded : null;
            }
        }

        $observaciones = $db->table('ruta_ejecucion_reclamo_observacion o')
            ->select('o.id, o.ruta_ejecucion_id, o.reclamo_id, o.texto, o.created_at, o.usuario_id, u.nombre AS usuario_nombre, u.foto_perfil AS usuario_foto_perfil, rec.municipalidad_id AS reclamo_municipalidad_id' . ($db->fieldExists('tipo', 'ruta_ejecucion_reclamo_observacion') ? ', o.tipo, o.archivo' : ''))
            ->join('usuario u', 'u.id = o.usuario_id', 'left')
            ->join('reclamo rec', 'rec.id = o.reclamo_id', 'left')
            ->where('o.ruta_ejecucion_id', $ejecucionId)
            ->orderBy('o.created_at', 'ASC')
            ->orderBy('o.id', 'ASC')
            ->get()
            ->getResultArray();

        $rutaId              = (int) ($cab['ruta_id'] ?? 0);
        $reclamosConDetalles = [];
        if ($rutaId > 0) {
            $rutaReclamoModel = new Ruta_reclamoModel();
            $reclamoModel     = new ReclamoModel();
            $direccionModel   = new DireccionModel();
            $reclamosRuta     = $rutaReclamoModel->where('ruta_id', $rutaId)
                ->orderBy('posicion', 'ASC')
                ->findAll();

            $idsReclamos = [];
            foreach ($reclamosRuta as $rutaReclamo) {
                $idr = (int) ($rutaReclamo['reclamo_id'] ?? 0);
                if ($idr > 0) {
                    $idsReclamos[] = $idr;
                }
            }

            $sesiones = RutaEjecucionHistorialService::computeSesionesReparacionHastaCierreEjecucion(
                $ejecucionId,
                $idsReclamos
            );

            $estadosAlCierre = RutaEjecucionHistorialService::computeEstadosReclamosAlCierreEjecucion(
                $ejecucionId,
                $cab['inicio_at'] ?? null,
                $cab['fin_at'] ?? null,
                $idsReclamos
            );

            foreach ($reclamosRuta as $rutaReclamo) {
                $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
                if (! $reclamo) {
                    continue;
                }
                $reclamo['coordenadas'] = $this->obtenerCoordenadasReclamo($reclamo, $direccionModel);
                $reclamo['posicion']    = $rutaReclamo['posicion'];
                $rid                    = (int) $reclamo['id'];
                $sr                     = $sesiones[$rid] ?? [
                    'activo'             => false,
                    'acumulado_ms'       => 0,
                    'inicio_segmento_at' => null,
                ];
                $sr['activo']                         = false;
                $reclamo['sesion_reparacion']         = $sr;
                $reclamo['estado_al_cierre_ejecucion'] = $estadosAlCierre[$rid]
                    ?? ($reclamo['municipalidad_estado'] ?? 'Asignado');
                $reclamosConDetalles[]                = $reclamo;
            }
        }

        return $this->respond([
            'ejecucion'     => $cab,
            'eventos'       => $eventos,
            'observaciones' => $observaciones,
            'reclamos'      => $reclamosConDetalles,
        ]);
    }

    private function normalizarEstadoEjecucion(array $ruta): string
    {
        if ($this->tieneEstadoEjecucion && !empty($ruta['estado_ejecucion'])) {
            return $ruta['estado_ejecucion'];
        }

        return ((int)($ruta['asignada'] ?? 0) === 1) ? 'asignada' : 'sin asignar';
    }

    /**
     * Obtiene los reclamos de las rutas asignadas a la(s) cuadrilla(s) del operario
     * CORRECCIÓN: Ahora obtiene de TODAS las cuadrillas del operario (por robustez)
     */
    public function getReclamosPorOperario()
    {
        $session = \Config\Services::session();
        $userId = $session->get('user_id');

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        try {
            // CORRECCIÓN: Obtener TODAS las cuadrillas del operario (en lugar de solo la primera)
            $cuadrillaOperariosModel = new \App\Models\CuadrillaOperariosModel();
            $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $userId)->findAll();

            if (empty($asignaciones)) {
                return $this->respond([]);
            }

            // Extraer IDs de todas las cuadrillas
            $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');

            // Obtener rutas asignadas a cualquiera de esas cuadrillas (no finalizadas)
            $rutas = $this->model->whereIn('cuadrilla_id', $cuadrillaIds)
                                ->where('asignada', 1)
                                ->where("(COALESCE(estado_ejecucion,'') <> 'finalizada')", null, false)
                                ->findAll();

            if (empty($rutas)) {
                return $this->respond([]);
            }

            // Obtener todos los reclamos de esas rutas
            $rutaReclamoModel = new Ruta_reclamoModel();
            $reclamoModel = new ReclamoModel();
            $direccionModel = new DireccionModel();

            $todosLosReclamos = [];
            $reclamosYaProcesados = []; // Para evitar duplicados si un reclamo está en múltiples rutas

            foreach ($rutas as $ruta) {
                $reclamosRuta = $rutaReclamoModel->where('ruta_id', $ruta['id'])
                                                ->orderBy('posicion', 'ASC')
                                                ->findAll();

                foreach ($reclamosRuta as $rutaReclamo) {
                    $reclamoId = $rutaReclamo['reclamo_id'];
                    
                    // Verificar si el reclamo existe antes de procesarlo
                    $reclamo = $reclamoModel->find($reclamoId);
                    
                    if ($reclamo) {
                        // FILTRAR RECLAMOS CERRADOS: No mostrar reclamos que están cerrados (cerrado = 1)
                        // Solo incluir reclamos que NO están cerrados (cerrado = 0 o cerrado IS NULL)
                        $estaCerrado = isset($reclamo['cerrado']) && $reclamo['cerrado'] == 1;
                        
                        if (!$estaCerrado) {
                            // Obtener coordenadas del reclamo
                            $coordenadas = $this->obtenerCoordenadasReclamo($reclamo, $direccionModel);
                            $reclamo['coordenadas'] = $coordenadas;
                            $reclamo['posicion'] = $rutaReclamo['posicion'];
                            $reclamo['ruta_id'] = $ruta['id'];
                            $reclamo['ruta_nombre'] = $ruta['nombre'];
                            $reclamo['ruta_color'] = $ruta['color'];
                            $todosLosReclamos[] = $reclamo;
                            $reclamosYaProcesados[] = $reclamoId;
                        }
                    } else {
                        // Log si un reclamo no existe (posible inconsistencia de datos)
                        log_message('warning', "Reclamo ID {$reclamoId} no encontrado pero está en ruta ID {$ruta['id']}");
                    }
                }
            }

            return $this->respond($todosLosReclamos);

        } catch (\Exception $e) {
            log_message('error', 'Error al obtener reclamos del operario: ' . $e->getMessage());
            return $this->failServerError('Error interno al obtener los reclamos: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene todos los reclamos con estado "Recibido" para que el operario pueda añadirlos a su hoja de ruta
     */
    public function getReclamosRecibidos()
    {
        $session = \Config\Services::session();
        $userId = $session->get('user_id');

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        try {
            $reclamoModel = new ReclamoModel();
            $direccionModel = new DireccionModel();
            
            // Recibidos activos y que no estén ya en una hoja no finalizada
            $reclamosRecibidos = $reclamoModel
                ->soloActivos()
                ->where('municipalidad_estado', 'Recibido')
                ->findAll();
            $reclamosRecibidos = array_values($this->filtrarReclamosDisponibles($reclamosRecibidos));
            
            // Obtener coordenadas para cada reclamo
            $reclamosConCoordenadas = [];
            foreach ($reclamosRecibidos as $reclamo) {
                $coordenadas = $this->obtenerCoordenadasReclamo($reclamo, $direccionModel);
                $reclamo['coordenadas'] = $coordenadas;
                $reclamosConCoordenadas[] = $reclamo;
            }

            return $this->respond($reclamosConCoordenadas);

        } catch (\Exception $e) {
            log_message('error', 'Error al obtener reclamos recibidos: ' . $e->getMessage());
            return $this->failServerError('Error interno al obtener los reclamos recibidos: ' . $e->getMessage());
        }
    }

    /**
     * Recalcula cantidad (domicilios) y tiempo estimado de una hoja.
     *
     * @return array{cantidadReclamos: int, tiempoEstimado: string}
     */
    private function recalcularMetricasRuta(int $rutaId): array
    {
        $rutaReclamoModel = new Ruta_reclamoModel();
        $reclamoModel     = new ReclamoModel();
        $direccionModel   = new DireccionModel();

        $links    = $rutaReclamoModel->where('ruta_id', $rutaId)->orderBy('posicion', 'ASC')->findAll();
        $reclamos = [];
        foreach ($links as $link) {
            $reclamo = $reclamoModel->find($link['reclamo_id']);
            if (! $reclamo) {
                continue;
            }
            $reclamo['coordenadas'] = $this->obtenerCoordenadasReclamo($reclamo, $direccionModel);
            $reclamos[]             = $reclamo;
        }

        $cantidad = $this->contarUnidadesDomicilio($reclamos);
        $tiempo   = $this->calcularTiempoEstimado($reclamos);

        $this->model->update($rutaId, [
            'cantidadReclamos' => $cantidad,
            'tiempoEstimado'   => $tiempo,
        ]);

        return [
            'cantidadReclamos' => $cantidad,
            'tiempoEstimado'   => $tiempo,
        ];
    }

    /**
     * Añade un reclamo (y todos los del mismo domicilio elegibles) a la hoja del operario.
     * Solo rol 3 con permisos de gestión; pensado para autoasignación en campo.
     */
    public function añadirReclamoARuta()
    {
        $session = \Config\Services::session();
        $userId  = (int) $session->get('user_id');
        $role    = (string) ($session->get('role') ?? '');

        if (! $userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        if ($role !== '3') {
            return $this->failForbidden(
                'Solo un operario con permisos de gestión puede añadir reclamos a la hoja en campo.'
            );
        }

        $data = $this->request->getJSON(true);

        if (empty($data['reclamo_id']) || empty($data['ruta_id'])) {
            return $this->failValidationErrors('Faltan datos obligatorios (reclamo_id, ruta_id).');
        }

        $reclamoId = (int) $data['reclamo_id'];
        $rutaId    = (int) $data['ruta_id'];

        try {
            $ruta = $this->model->find($rutaId);
            if (! $ruta) {
                return $this->failNotFound('Hoja de ruta no encontrada.');
            }

            $err = $this->errorSiRutaFinalizada($ruta);
            if ($err !== null) {
                return $err;
            }

            $cuadrillaOperariosModel = new \App\Models\CuadrillaOperariosModel();
            $asignaciones            = $cuadrillaOperariosModel->where('usuario_id', $userId)->findAll();

            if (empty($asignaciones)) {
                return $this->failForbidden('Operario no tiene cuadrillas asignadas.');
            }

            $esJefePorCuadrilla = [];
            foreach ($asignaciones as $asig) {
                $esJefePorCuadrilla[(int) $asig['cuadrilla_id']] = (int) ($asig['es_jefe'] ?? 0);
            }

            $cuadrillaRuta = (int) ($ruta['cuadrilla_id'] ?? 0);
            if ((int) ($ruta['asignada'] ?? 0) !== 1 || $cuadrillaRuta <= 0) {
                return $this->failForbidden('La hoja de ruta debe estar asignada a tu cuadrilla.');
            }

            if (! isset($esJefePorCuadrilla[$cuadrillaRuta])) {
                return $this->failForbidden('No tiene permisos para modificar esta hoja de ruta.');
            }

            if ((int) ($esJefePorCuadrilla[$cuadrillaRuta] ?? 0) !== 1) {
                return $this->failForbidden(
                    'Solo un operario con permisos de gestión puede añadir reclamos a la hoja de ruta.'
                );
            }

            $estadoEjec = $this->tieneEstadoEjecucion
                ? $this->normalizarEstadoEjecucion($ruta)
                : 'asignada';
            if ($estadoEjec !== 'en ejecución') {
                return $this->failValidationErrors(
                    'Solo se pueden añadir reclamos cuando la hoja de ruta está en ejecución.'
                );
            }

            $reclamoModel = new ReclamoModel();
            $reclamo      = $reclamoModel->find($reclamoId);

            if (! $reclamo) {
                return $this->failNotFound('Reclamo no encontrado.');
            }

            if (($reclamo['municipalidad_estado'] ?? '') !== 'Recibido') {
                return $this->failValidationErrors('El reclamo debe tener estado "Recibido" para ser añadido a la ruta.');
            }

            $membresiaActiva = $this->membresiaReclamoEnRutaActiva($reclamoId);
            if ($membresiaActiva) {
                $nombreHoja = $membresiaActiva['nombre'] ?? ('#' . $membresiaActiva['ruta_id']);

                return $this->failValidationErrors(
                    'El reclamo ya está en la hoja de ruta activa "' . $nombreHoja . '".'
                );
            }

            // Misma lógica que al crear: toda la parada (mismo domicilio) de una
            $claveRef       = $this->claveDomicilioReclamo($reclamo);
            $idsEnRutaActiva = $this->idsReclamosEnRutasActivas();
            $candidatos     = $reclamoModel
                ->soloActivos()
                ->where('municipalidad_estado', 'Recibido')
                ->findAll();

            $grupo = [];
            foreach ($candidatos as $candidato) {
                if ($this->claveDomicilioReclamo($candidato) !== $claveRef) {
                    continue;
                }
                if (in_array((int) $candidato['id'], $idsEnRutaActiva, true)) {
                    continue;
                }
                $grupo[] = $candidato;
            }

            if ($grupo === []) {
                return $this->failValidationErrors('No hay reclamos disponibles en ese domicilio para añadir.');
            }

            // Priorizar el reclamo elegido primero en el orden de posiciones
            usort($grupo, static function ($a, $b) use ($reclamoId) {
                if ((int) $a['id'] === $reclamoId) {
                    return -1;
                }
                if ((int) $b['id'] === $reclamoId) {
                    return 1;
                }

                return ((int) $a['id']) <=> ((int) $b['id']);
            });

            $rutaReclamoModel = new Ruta_reclamoModel();
            $ultimaPosicion   = $rutaReclamoModel->where('ruta_id', $rutaId)
                ->orderBy('posicion', 'DESC')
                ->first();
            $posicion = $ultimaPosicion ? ((int) $ultimaPosicion['posicion'] + 1) : 1;

            $direccionModel   = new DireccionModel();
            $ahora            = date('Y-m-d H:i:s');
            $reclamosAñadidos = [];

            foreach ($grupo as $item) {
                $rutaReclamoModel->insert([
                    'ruta_id'    => $rutaId,
                    'reclamo_id' => (int) $item['id'],
                    'posicion'   => $posicion,
                ]);

                $reclamoModel->update((int) $item['id'], [
                    'municipalidad_estado'           => 'Asignado',
                    'municipalidad_fechaModificacion' => $ahora,
                ]);

                $actualizado = $reclamoModel->find((int) $item['id']);
                $actualizado['coordenadas'] = $this->obtenerCoordenadasReclamo($actualizado, $direccionModel);
                $actualizado['posicion']    = $posicion;
                $actualizado['ruta_id']     = $rutaId;
                $actualizado['ruta_nombre'] = $ruta['nombre'];
                $actualizado['ruta_color']  = $ruta['color'];
                $reclamosAñadidos[]         = $actualizado;
                $posicion++;
            }

            $metricas = $this->recalcularMetricasRuta($rutaId);
            $primero  = $reclamosAñadidos[0];

            $mensaje = count($reclamosAñadidos) === 1
                ? 'Reclamo añadido exitosamente a la hoja de ruta.'
                : (count($reclamosAñadidos) . ' reclamos del mismo domicilio añadidos a la hoja de ruta.');

            return $this->respondCreated([
                'mensaje'          => $mensaje,
                'reclamo'          => $primero,
                'reclamos'         => $reclamosAñadidos,
                'cantidadReclamos' => $metricas['cantidadReclamos'],
                'tiempoEstimado'   => $metricas['tiempoEstimado'],
                'posicion'         => $primero['posicion'] ?? null,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error al añadir reclamo a ruta: ' . $e->getMessage());

            return $this->failServerError('Error interno al añadir el reclamo a la ruta: ' . $e->getMessage());
        }
    }
}