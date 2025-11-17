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

class Rutas extends ResourceController
{
    protected $modelName = 'App\Models\RutaModel';
    protected $format = 'json';

    // API Keys para cálculo de rutas
    private $googleMapsApiKey = 'AIzaSyAOCwr8_hWX4aBE2JTHxREP7gUrYLadCgg';
    private $mapboxApiKey = 'pk.eyJ1IjoicHJveWVjdG9maW5hbGFsdW1icmFkb3B1YmxpY28iLCJhIjoiY21mY3FpanE3MDB6ajJub3ByZmpldm1mYSJ9.sjk91HIU-CxPuXoj9oVRiw';

    public function __construct()
    {
        // Configurar zona horaria de Argentina
        date_default_timezone_set('America/Argentina/Buenos_Aires');
    }

    public function index()
    {
        $rutas = $this->model->select('ruta.*, cuadrilla.nombre as cuadrilla_nombre')
                            ->join('cuadrilla', 'cuadrilla.id = ruta.cuadrilla_id', 'left')
                            ->findAll();
        
        return $this->respond($rutas);
    }

    public function show($id = null)
    {
        $ruta = $this->model->select('ruta.*, cuadrilla.nombre as cuadrilla_nombre')
                           ->join('cuadrilla', 'cuadrilla.id = ruta.cuadrilla_id', 'left')
                           ->find($id);
        
        if (!$ruta) {
            return $this->failNotFound('Ruta no encontrada');
        }
        
        return $this->respond($ruta);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        
        // Validar datos obligatorios
        if (empty($data['cantidadReclamos'])) {
            return $this->failValidationErrors('Faltan datos obligatorios (cantidadReclamos).');
        }

        // Establecer valores por defecto
        $data['nombre'] = $data['nombre'] ?? 'Hoja de ruta';
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
        
        if (!$id || empty($data)) {
            return $this->failValidationErrors('Faltan datos obligatorios.');
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
        if (!$id || !$this->model->find($id)) {
            return $this->failNotFound('Ruta no encontrada.');
        }

        // Eliminar también los reclamos asociados
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

        $nombre = $data['nombre'] ?? 'Hoja de ruta';
        $color = $data['color'] ?? '#FF6B35';
        $cantidadReclamos = (int)$data['cantidadReclamos'];
        $cuadrillaId = $data['cuadrilla_id'] ?? null;
        $reclamosManuales = $data['reclamosManuales'] ?? [];
        $primerReclamoManual = $data['primerReclamoManual'] ?? null;
        $modoManual = $data['modoManual'] ?? false;

        try {
            // Obtener todos los reclamos disponibles
            $reclamoModel = new ReclamoModel();
            $direccionModel = new DireccionModel();
            
            $reclamos = $reclamoModel->findAll();
            
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

            // Crear la ruta en la base de datos
            $rutaData = [
                'nombre' => $nombre,
                'color' => $color,
                'cantidadReclamos' => count($rutaOptimizada),
                'asignada' => 0, // No asignada hasta que se le asigne una cuadrilla
                'cuadrilla_id' => $cuadrillaId,
                'tiempoEstimado' => $this->calcularTiempoEstimado($rutaOptimizada),
                'fecha' => date('Y-m-d H:i:s')
            ];

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

        return $this->respond($reclamosConDetalles);
    }

    /**
     * Filtra reclamos que no están en ninguna ruta (asignada o no asignada) Y que no están completados
     */
    private function filtrarReclamosDisponibles($reclamos)
    {
        $rutaReclamoModel = new Ruta_reclamoModel();
        
        // Obtener IDs de reclamos que ya están en CUALQUIER ruta (asignada o no asignada)
        // Una ruta no asignada significa que aún no se asignó a una cuadrilla, pero los reclamos están reservados
        $reclamosEnRutas = $rutaReclamoModel->select('reclamo_id')
                                           ->findAll();
        
        $reclamosEnRutasIds = array_column($reclamosEnRutas, 'reclamo_id');
        
        // Filtrar reclamos disponibles: NO en ninguna ruta Y NO completados
        return array_filter($reclamos, function($reclamo) use ($reclamosEnRutasIds) {
            $estaEnRuta = in_array($reclamo['id'], $reclamosEnRutasIds);
            $estaCompletado = ($reclamo['municipalidad_estado'] ?? '') === 'Completado';
            
            // Solo disponible si NO está en ninguna ruta Y NO está completado
            return !$estaEnRuta && !$estaCompletado;
        });
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
        
        // Agregar reclamos manuales primero
        foreach ($reclamosManuales as $reclamoId) {
            $reclamo = array_filter($reclamos, function($r) use ($reclamoId) {
                return $r['id'] == $reclamoId;
            });
            
            if (!empty($reclamo)) {
                $reclamosSeleccionados[] = array_values($reclamo)[0];
            }
        }
        
        // Si ya tenemos suficientes reclamos, retornar solo los necesarios
        if (count($reclamosSeleccionados) >= $cantidad) {
            return array_slice($reclamosSeleccionados, 0, $cantidad);
        }
        
        // Filtrar reclamos ya seleccionados
        $reclamosDisponibles = array_filter($reclamos, function($reclamo) use ($reclamosSeleccionados) {
            return !in_array($reclamo['id'], array_column($reclamosSeleccionados, 'id'));
        });
        
        // Separar reclamos por prioridad (solo Alta y Baja)
        $reclamosAlta = array_filter($reclamosDisponibles, function($r) {
            return ($r['prioridad'] ?? 'Baja') === 'Alta';
        });
        $reclamosBaja = array_filter($reclamosDisponibles, function($r) {
            return ($r['prioridad'] ?? 'Baja') === 'Baja';
        });
        
        // Calcular cuántos reclamos necesitamos
        $cantidadNecesaria = $cantidad - count($reclamosSeleccionados);
        
        // Seleccionar reclamos por prioridad
        $reclamosSeleccionados = $this->seleccionarPorPrioridad(
            $reclamosSeleccionados, 
            $reclamosAlta, 
            $reclamosBaja, 
            $cantidadNecesaria
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
    private function seleccionarPorPrioridad($reclamosSeleccionados, $reclamosAlta, $reclamosBaja, $cantidadNecesaria)
    {
        $cantidadAlta = count($reclamosAlta);
        
        // CASO 1: Hay suficientes Alta para llenar la ruta completa
        if ($cantidadAlta >= $cantidadNecesaria) {
            // Seleccionar los N de Alta que formen la ruta más corta usando vecino más cercano
            $reclamosSeleccionadosAlta = $this->seleccionarReclamosCercanos($reclamosAlta, $cantidadNecesaria);
            foreach ($reclamosSeleccionadosAlta as $reclamo) {
                $reclamosSeleccionados[] = $reclamo;
            }
            return $reclamosSeleccionados;
        }
        
        // CASO 2: Hay algunos Alta pero no suficientes, completar con Baja
        if ($cantidadAlta > 0) {
            // Incluir TODOS los Alta
            foreach ($reclamosAlta as $reclamo) {
                $reclamosSeleccionados[] = $reclamo;
            }
            
            // Calcular cuántos Baja necesitamos
            $cantidadBajaNecesaria = $cantidadNecesaria - $cantidadAlta;
            
            // Seleccionar los Baja más cercanos al grupo de Alta usando vecino más cercano
            $reclamosBajaSeleccionados = $this->seleccionarReclamosCercanosAGrupo($reclamosBaja, $reclamosAlta, $cantidadBajaNecesaria);
            foreach ($reclamosBajaSeleccionados as $reclamo) {
                $reclamosSeleccionados[] = $reclamo;
            }
            
            return $reclamosSeleccionados;
        }
        
        // CASO 3: No hay Alta, solo Baja
        $reclamosBajaSeleccionados = $this->seleccionarReclamosCercanos($reclamosBaja, $cantidadNecesaria);
        foreach ($reclamosBajaSeleccionados as $reclamo) {
            $reclamosSeleccionados[] = $reclamo;
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
        
        $tanqueAgua = ['lat' => -31.426516, 'lng' => -62.110954];
        
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
    private function seleccionarReclamosCercanos($reclamos, $cantidad)
    {
        if (empty($reclamos) || $cantidad <= 0) {
            return [];
        }
        
        $reclamosSeleccionados = [];
        $reclamosRestantes = $reclamos;
        $tanqueAgua = ['lat' => -31.426516, 'lng' => -62.110954];
        
        // Empezar por el más cercano al tanque
        $primerReclamo = $this->encontrarReclamoMasCercano($reclamosRestantes, $tanqueAgua);
        if (!$primerReclamo) {
            return [];
        }
        
        $reclamosSeleccionados[] = $primerReclamo;
        $reclamoActual = $primerReclamo;
        
        // Remover el primer reclamo
        $reclamosRestantes = array_filter($reclamosRestantes, function($r) use ($primerReclamo) {
            return $r['id'] != $primerReclamo['id'];
        });
        
        // Continuar con vecino más cercano hasta tener N reclamos
        while (count($reclamosSeleccionados) < $cantidad && !empty($reclamosRestantes)) {
            $reclamoMasCercano = $this->encontrarReclamoMasCercano($reclamosRestantes, $reclamoActual['coordenadas']);
            
            if ($reclamoMasCercano) {
                $reclamosSeleccionados[] = $reclamoMasCercano;
                $reclamoActual = $reclamoMasCercano;
                
                $reclamosRestantes = array_filter($reclamosRestantes, function($r) use ($reclamoMasCercano) {
                    return $r['id'] != $reclamoMasCercano['id'];
                });
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
    private function seleccionarReclamosCercanosAGrupo($reclamos, $grupoBase, $cantidad)
    {
        if (empty($reclamos) || $cantidad <= 0) {
            return [];
        }
        
        $reclamosSeleccionados = [];
        $reclamosRestantes = $reclamos;
        
        // Empezar por el Baja más cercano a cualquiera de los Alta
        $primerReclamo = null;
        $distanciaMinima = PHP_FLOAT_MAX;
        
        foreach ($reclamosRestantes as $reclamo) {
            $distancia = $this->calcularDistanciaMinimaAGrupo($reclamo, $grupoBase);
            if ($distancia < $distanciaMinima) {
                $distanciaMinima = $distancia;
                $primerReclamo = $reclamo;
            }
        }
        
        if (!$primerReclamo) {
            return [];
        }
        
        $reclamosSeleccionados[] = $primerReclamo;
        $reclamoActual = $primerReclamo;
        
        // Remover el primer reclamo
        $reclamosRestantes = array_filter($reclamosRestantes, function($r) use ($primerReclamo) {
            return $r['id'] != $primerReclamo['id'];
        });
        
        // Continuar con vecino más cercano
        while (count($reclamosSeleccionados) < $cantidad && !empty($reclamosRestantes)) {
            $reclamoMasCercano = $this->encontrarReclamoMasCercano($reclamosRestantes, $reclamoActual['coordenadas']);
            
            if ($reclamoMasCercano) {
                $reclamosSeleccionados[] = $reclamoMasCercano;
                $reclamoActual = $reclamoMasCercano;
                
                $reclamosRestantes = array_filter($reclamosRestantes, function($r) use ($reclamoMasCercano) {
                    return $r['id'] != $reclamoMasCercano['id'];
                });
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
        
        $rutaOptimizada = [];
        $reclamosRestantes = $reclamos;
        
        // Coordenadas del tanque de agua de San Francisco
        $tanqueAgua = ['lat' => -31.426516, 'lng' => -62.110954];
        
        // Encontrar el reclamo más cercano al tanque de agua para empezar la ruta
        $primerReclamo = $this->encontrarReclamoMasCercano($reclamosRestantes, $tanqueAgua);
        
        if ($primerReclamo) {
            $rutaOptimizada[] = $primerReclamo;
            $reclamoActual = $primerReclamo;
            
            // Remover el primer reclamo de la lista de restantes
            $reclamosRestantes = array_filter($reclamosRestantes, function($r) use ($primerReclamo) {
                return $r['id'] != $primerReclamo['id'];
            });
        } else {
            // Fallback: usar el primer reclamo de la lista si no se encuentra el más cercano
            $reclamoActual = array_shift($reclamosRestantes);
            $rutaOptimizada[] = $reclamoActual;
        }
        
        // Continuar con el algoritmo del vecino más cercano
        while (!empty($reclamosRestantes)) {
            $reclamoMasCercano = $this->encontrarReclamoMasCercano($reclamosRestantes, $reclamoActual['coordenadas']);
            
            if ($reclamoMasCercano) {
                $rutaOptimizada[] = $reclamoMasCercano;
                $reclamoActual = $reclamoMasCercano;
                
                // Remover el reclamo seleccionado
                $reclamosRestantes = array_filter($reclamosRestantes, function($r) use ($reclamoMasCercano) {
                    return $r['id'] != $reclamoMasCercano['id'];
                });
            } else {
                break;
            }
        }
        
        return $rutaOptimizada;
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
        if (count($reclamos) < 2) {
            log_message('info', 'CALCULO TIEMPO ESTIMADO: Menos de 2 reclamos, retornando 30 minutos mínimo');
            return '00:30:00'; // 30 minutos mínimo
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
        
        // Tiempo de desplazamiento entre reclamos usando APIs de mapas
        $tiempoDesplazamientoTotal = 0;
        $distanciaTotal = 0;
        
        if (count($reclamos) > 1) {
            // Intentar obtener tiempos usando Google Maps Directions API
            $resultadoAPI = $this->calcularTiempoDesplazamientoConAPI($reclamos);
            
            if ($resultadoAPI['exito']) {
                $tiempoDesplazamientoTotal = $resultadoAPI['tiempo_minutos'];
                $distanciaTotal = $resultadoAPI['distancia_km'];
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
        log_message('info', "CALCULO TIEMPO ESTIMADO: Tiempo total de desplazamiento: " . round($tiempoDesplazamientoTotal, 2) . " minutos | Distancia total: " . round($distanciaTotal, 2) . " km");
        log_message('info', "CALCULO TIEMPO ESTIMADO: Tiempo total estimado (reparación + desplazamiento): {$tiempoTotalMinutos} minutos");
        
        // Convertir a formato HH:MM:SS
        $horas = floor($tiempoTotalMinutos / 60);
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
            $reclamos = $reclamoModel->findAll();
            $reclamosDisponibles = $this->filtrarReclamosDisponibles($reclamos);
            $reclamosConCoordenadas = $this->obtenerCoordenadasReclamos($reclamosDisponibles, $direccionModel);
            $reclamosSeleccionados = $this->seleccionarReclamosParaRuta(
                $reclamosConCoordenadas,
                $cantidadReclamos,
                $reclamosManuales,
                $primerReclamoManual
            );
            $rutaOptimizada = $this->optimizarOrdenRuta($reclamosSeleccionados);

            // Calcular estadísticas
            $tiempoEstimado = $this->calcularTiempoEstimado($rutaOptimizada);
            $distanciaTotal = $this->calcularDistanciaTotal($rutaOptimizada);

            return $this->respond([
                'rutaOptimizada' => $rutaOptimizada,
                'tiempoEstimado' => $tiempoEstimado,
                'distanciaTotal' => $distanciaTotal,
                'cantidadReclamos' => count($rutaOptimizada)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al generar vista previa: ' . $e->getMessage());
            return $this->failServerError('Error interno al generar la vista previa: ' . $e->getMessage());
        }
    }

    /**
     * Calcula la distancia total de una ruta
     */
    private function calcularDistanciaTotal($reclamos)
    {
        if (count($reclamos) < 2) {
            return 0;
        }

        $distanciaTotal = 0;
        for ($i = 0; $i < count($reclamos) - 1; $i++) {
            $actual = $reclamos[$i];
            $siguiente = $reclamos[$i + 1];
            
            $distanciaTotal += $this->calcularDistancia(
                $actual['coordenadas']['lat'], $actual['coordenadas']['lng'],
                $siguiente['coordenadas']['lat'], $siguiente['coordenadas']['lng']
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
            if (!$ruta) {
                return $this->failNotFound('Ruta no encontrada.');
            }

            // Verificar que la cuadrilla existe
            $cuadrillaModel = new CuadrillaModel();
            $cuadrilla = $cuadrillaModel->find($cuadrillaId);
            if (!$cuadrilla) {
                return $this->failNotFound('Cuadrilla no encontrada.');
            }

            // Actualizar la ruta para asignarla a la cuadrilla
            $actualizado = $this->model->update($rutaId, [
                'asignada' => 1,
                'cuadrilla_id' => $cuadrillaId
            ]);

            if ($actualizado === false) {
                return $this->failServerError('Error al asignar la ruta a la cuadrilla.');
            }

            // Actualizar el estado de los reclamos de "Recibido" a "Asignado"
            $rutaReclamoModel = new Ruta_reclamoModel();
            $reclamoModel = new ReclamoModel();
            
            // Obtener todos los reclamos de esta ruta
            $reclamosRuta = $rutaReclamoModel->where('ruta_id', $rutaId)->findAll();
            $reclamosActualizados = 0;
            
            foreach ($reclamosRuta as $rutaReclamo) {
                // Obtener el reclamo
                $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
                
                // Si el estado es "Recibido", cambiarlo a "Asignado"
                if ($reclamo && $reclamo['municipalidad_estado'] === 'Recibido') {
                    $reclamoModel->update($rutaReclamo['reclamo_id'], [
                        'municipalidad_estado' => 'Asignado'
                    ]);
                    $reclamosActualizados++;
                }
            }

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
            if (!$ruta) {
                return $this->failNotFound('Ruta no encontrada.');
            }

            // Actualizar la ruta para desasignarla
            $actualizado = $this->model->update($rutaId, [
                'asignada' => 0,
                'cuadrilla_id' => null
            ]);

            if ($actualizado === false) {
                return $this->failServerError('Error al desasignar la ruta.');
            }

            // Actualizar el estado de los reclamos de "Asignado" a "Recibido"
            $rutaReclamoModel = new Ruta_reclamoModel();
            $reclamoModel = new ReclamoModel();
            
            // Obtener todos los reclamos de esta ruta
            $reclamosRuta = $rutaReclamoModel->where('ruta_id', $rutaId)->findAll();
            $reclamosActualizados = 0;
            
            foreach ($reclamosRuta as $rutaReclamo) {
                // Obtener el reclamo
                $reclamo = $reclamoModel->find($rutaReclamo['reclamo_id']);
                
                // Si el estado es "Asignado", cambiarlo a "Recibido"
                if ($reclamo && $reclamo['municipalidad_estado'] === 'Asignado') {
                    $reclamoModel->update($rutaReclamo['reclamo_id'], [
                        'municipalidad_estado' => 'Recibido'
                    ]);
                    $reclamosActualizados++;
                }
            }

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

            // Obtener rutas asignadas a cualquiera de esas cuadrillas
            $rutas = $this->model->select('ruta.*, cuadrilla.nombre as cuadrilla_nombre')
                                ->join('cuadrilla', 'cuadrilla.id = ruta.cuadrilla_id', 'left')
                                ->whereIn('ruta.cuadrilla_id', $cuadrillaIds)
                                ->where('ruta.asignada', 1)
                                ->findAll();

            return $this->respond($rutas);

        } catch (\Exception $e) {
            log_message('error', 'Error al obtener rutas del operario: ' . $e->getMessage());
            return $this->failServerError('Error interno al obtener las rutas: ' . $e->getMessage());
        }
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

            // Obtener rutas asignadas a cualquiera de esas cuadrillas
            $rutas = $this->model->whereIn('cuadrilla_id', $cuadrillaIds)
                                ->where('asignada', 1)
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
            
            // Obtener todos los reclamos con estado "Recibido"
            $reclamosRecibidos = $reclamoModel->where('municipalidad_estado', 'Recibido')->findAll();
            
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
     * Añade un reclamo específico a una ruta asignada al operario
     */
    public function añadirReclamoARuta()
    {
        $session = \Config\Services::session();
        $userId = $session->get('user_id');

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado.');
        }

        $data = $this->request->getJSON(true);
        
        // Validar datos obligatorios
        if (empty($data['reclamo_id']) || empty($data['ruta_id'])) {
            return $this->failValidationErrors('Faltan datos obligatorios (reclamo_id, ruta_id).');
        }

        $reclamoId = $data['reclamo_id'];
        $rutaId = $data['ruta_id'];

        try {
            // Verificar que el operario tiene acceso a esta ruta
            $cuadrillaOperariosModel = new \App\Models\CuadrillaOperariosModel();
            $asignaciones = $cuadrillaOperariosModel->where('usuario_id', $userId)->findAll();

            if (empty($asignaciones)) {
                return $this->failForbidden('Operario no tiene cuadrillas asignadas.');
            }

            $cuadrillaIds = array_column($asignaciones, 'cuadrilla_id');

            // Verificar que la ruta pertenece a una de las cuadrillas del operario
            $ruta = $this->model->whereIn('cuadrilla_id', $cuadrillaIds)
                               ->where('id', $rutaId)
                               ->where('asignada', 1)
                               ->first();

            if (!$ruta) {
                return $this->failForbidden('No tiene permisos para modificar esta ruta.');
            }

            // Verificar que el reclamo existe y tiene estado "Recibido"
            $reclamoModel = new ReclamoModel();
            $reclamo = $reclamoModel->find($reclamoId);
            
            if (!$reclamo) {
                return $this->failNotFound('Reclamo no encontrado.');
            }

            if ($reclamo['municipalidad_estado'] !== 'Recibido') {
                return $this->failValidationErrors('El reclamo debe tener estado "Recibido" para ser añadido a la ruta.');
            }

            // Verificar que el reclamo no esté ya en esta ruta
            $rutaReclamoModel = new Ruta_reclamoModel();
            $reclamoEnRuta = $rutaReclamoModel->where('ruta_id', $rutaId)
                                            ->where('reclamo_id', $reclamoId)
                                            ->first();

            if ($reclamoEnRuta) {
                return $this->failValidationErrors('El reclamo ya está en esta ruta.');
            }

            // Obtener la siguiente posición en la ruta
            $ultimaPosicion = $rutaReclamoModel->where('ruta_id', $rutaId)
                                             ->orderBy('posicion', 'DESC')
                                             ->first();

            $nuevaPosicion = $ultimaPosicion ? $ultimaPosicion['posicion'] + 1 : 1;

            // Añadir el reclamo a la ruta
            $rutaReclamoModel->insert([
                'ruta_id' => $rutaId,
                'reclamo_id' => $reclamoId,
                'posicion' => $nuevaPosicion
            ]);

            // Cambiar el estado del reclamo de "Recibido" a "Asignado"
            $reclamoModel->update($reclamoId, [
                'municipalidad_estado' => 'Asignado',
                'municipalidad_fechaModificacion' => date('Y-m-d H:i:s')
            ]);

            // Actualizar la cantidad de reclamos en la ruta
            $this->model->update($rutaId, [
                'cantidadReclamos' => $nuevaPosicion
            ]);

            // Obtener el reclamo actualizado con información de la ruta
            $reclamoActualizado = $reclamoModel->find($reclamoId);
            $direccionModel = new DireccionModel();
            $coordenadas = $this->obtenerCoordenadasReclamo($reclamoActualizado, $direccionModel);
            $reclamoActualizado['coordenadas'] = $coordenadas;
            $reclamoActualizado['posicion'] = $nuevaPosicion;
            $reclamoActualizado['ruta_id'] = $rutaId;
            $reclamoActualizado['ruta_nombre'] = $ruta['nombre'];
            $reclamoActualizado['ruta_color'] = $ruta['color'];

            return $this->respondCreated([
                'mensaje' => 'Reclamo añadido exitosamente a la hoja de ruta.',
                'reclamo' => $reclamoActualizado
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error al añadir reclamo a ruta: ' . $e->getMessage());
            return $this->failServerError('Error interno al añadir el reclamo a la ruta: ' . $e->getMessage());
        }
    }
}