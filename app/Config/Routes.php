<?php

namespace Config;


// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (is_file(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();

// INICIO ENDPOINTS ---------------------------------------------------

$routes->get('/', 'Pages::view');

// Endpoint Usuarios
$routes->post('api/usuarios/(:num)/foto', 'Api\\Usuarios::subirFoto/$1');
$routes->resource('api/usuarios');
// Endpoint Roles
$routes->resource('api/roles');
// Endpoint Reclamos (sistema interno)
$routes->get('api/reclamos/(:num)/historial', 'Api\\Reclamos::historial/$1');
$routes->get('api/reclamos/materiales/por-tipo', 'Api\\Reclamos::getMaterialesPorTipo');
$routes->post('api/reclamos/(:num)/materiales', 'Api\\Reclamos::guardarMaterialReclamo/$1');
$routes->get('api/reclamos/(:num)/materiales', 'Api\\Reclamos::getMaterialesReclamo/$1');
$routes->delete('api/reclamos/materiales/(:num)', 'Api\\Reclamos::eliminarMaterialReclamo/$1');
$routes->get('api/reclamos/materiales/(:num)/detalle', 'Api\\Reclamos::getDetalleMaterialReclamo/$1');
$routes->get('api/reclamos/(:num)/tiempo-reparacion', 'Api\\Reclamos::getTiempoReparacion/$1');
$routes->post('api/reclamos/(:num)/tiempo-reparacion', 'Api\\Reclamos::guardarTiempoReparacionEndpoint/$1');
$routes->get('api/reclamos/(:num)/ejecucion-observaciones', 'Api\\Reclamos::getEjecucionObservacionesReclamo/$1');
$routes->post('api/reclamos/(:num)/ejecucion-observaciones', 'Api\\Reclamos::guardarEjecucionObservacionReclamo/$1');
$routes->post('api/reclamos/(:num)/ejecucion-observaciones/foto', 'Api\\Reclamos::guardarEjecucionFotoReclamo/$1');
$routes->get('api/reclamos/tiempos-promedio', 'Api\\Reclamos::getTiemposPromedio');
$routes->put('api/reclamos/(:num)/ficha', 'Api\\Reclamos::actualizarFicha/$1');
$routes->resource('api/reclamos');
// Endpoints para Tipos de Materiales
$routes->get('api/materiales/tipos', 'Api\\Materiales::getTipos');
$routes->post('api/materiales/tipos', 'Api\\Materiales::createTipo');
$routes->put('api/materiales/tipos/(:num)', 'Api\\Materiales::updateTipo/$1');
$routes->delete('api/materiales/tipos/(:num)', 'Api\\Materiales::deleteTipo/$1');
// Endpoint Materiales
$routes->post('api/materiales/(:num)/foto', 'Api\\Materiales::subirFoto/$1');
$routes->post('api/materiales/import', 'Api\\Materiales::import');
$routes->get('api/materiales/verificar', 'Api\\Materiales::verificarExistencia');
$routes->resource('api/materiales');
// Endpoint Token103
$routes->resource('api/token103');
// Endpoint Sincronización de Reclamos (proxy para evitar CORS)
$routes->get('api/sincronizacion/reclamos/pendientes', 'Api\\ReclamosSincronizacion::sincronizarHoy');
$routes->post('api/sincronizacion/reclamos/procesar-uno', 'Api\\ReclamosSincronizacion::procesarUno');
$routes->get('api/sincronizacion/reclamos', 'Api\\ReclamosSincronizacion::sincronizarPorFechas');
$routes->get('api/sincronizacion/reclamos/(:segment)', 'Api\\ReclamosSincronizacion::sincronizarEspecifico/$1');
// Endpoint Direcciones
$routes->get('api/direcciones/buscar', 'Api\\Direcciones::buscarPorDomicilio');
$routes->resource('api/direcciones');
// Endpoint Cuadrillas
$routes->resource('api/cuadrillas', ['controller' => 'Api\\Cuadrillas']);
$routes->post('api/cuadrillas/asignar', 'Api\\Cuadrillas::asignar');
$routes->get('api/usuarios/operarios', 'Api\\Usuarios::operarios');
$routes->get('api/operarios', 'Api\\Usuarios::operarios');
// Endpoint Rutas
$routes->post('api/rutas/generar', 'Api\\Rutas::generarRuta');
$routes->post('api/rutas/vista-previa', 'Api\\Rutas::vistaPreviaRuta');
$routes->get('api/rutas/domicilios-disponibles', 'Api\\Rutas::getDomiciliosDisponibles');
$routes->get('api/rutas/(:num)/reclamos', 'Api\\Rutas::getReclamosRuta/$1');
$routes->post('api/rutas/asignar', 'Api\\Rutas::asignarACuadrilla');
$routes->post('api/rutas/desasignar/(:num)', 'Api\\Rutas::desasignarDeCuadrilla/$1');
$routes->get('api/rutas/operario/mis-rutas', 'Api\\Rutas::getRutasPorOperario');
$routes->get('api/rutas/operario/mis-reclamos', 'Api\\Rutas::getReclamosPorOperario');
$routes->get('api/rutas/operario/reclamos-recibidos', 'Api\\Rutas::getReclamosRecibidos');
$routes->post('api/rutas/operario/iniciar-ejecucion', 'Api\\Rutas::iniciarEjecucionOperario');
$routes->post('api/rutas/operario/finalizar-ejecucion', 'Api\\Rutas::finalizarEjecucionOperario');
$routes->post('api/rutas/operario/ejecucion-evento', 'Api\\Rutas::registrarEventoEjecucionOperario');
$routes->get('api/rutas/(:num)/ejecucion-activa', 'Api\\Rutas::getEjecucionActiva/$1');
$routes->get('api/rutas/ejecuciones/historial', 'Api\\Rutas::historialEjecuciones');
$routes->get('api/rutas/ejecuciones/(:num)/detalle', 'Api\\Rutas::historialEjecucionDetalle/$1');
$routes->post('api/rutas/operario/add-reclamo', 'Api\\Rutas::añadirReclamoARuta');
$routes->resource('api/rutas');
// Endpoint Cierre de Reclamos
$routes->get('api/cierre-reclamos/completados', 'Api\\CierreReclamos::obtenerReclamosCompletados');
$routes->get('api/cierre-reclamos/cerrados', 'Api\\CierreReclamos::obtenerReclamosCerrados');
$routes->post('api/cierre-reclamos/cerrar', 'Api\\CierreReclamos::cerrarReclamos');
// Endpoint Análisis de Reclamos
$routes->get('api/analisis/reclamos-por-estado', 'Api\\Analisis::getReclamosPorEstado');
$routes->get('api/analisis/reclamos-por-motivo', 'Api\\Analisis::getReclamosPorMotivo');
$routes->get('api/analisis/kpi-resumen', 'Api\\Analisis::getKpiResumen');
$routes->get('api/analisis/evolucion-temporal', 'Api\\Analisis::getEvolucionTemporal');
$routes->get('api/analisis/antiguedad-abiertos', 'Api\\Analisis::getAntiguedadAbiertos');
$routes->get('api/analisis/tiempo-promedio-por-motivo', 'Api\\Analisis::getTiempoPromedioPorMotivo');
$routes->get('api/analisis/evolucion-tiempo-promedio', 'Api\\Analisis::getEvolucionTiempoPromedio');
$routes->get('api/analisis/mapa-calor-zonas', 'Api\\Analisis::getMapaCalorZonas');
$routes->get('api/analisis/evolucion-alta-prioridad', 'Api\\Analisis::getEvolucionAltaPrioridad');
$routes->get('api/analisis/consumo-materiales', 'Api\\Analisis::getConsumoMateriales');
$routes->get('api/analisis/reclamos-cerrados-abiertos', 'Api\\Analisis::getReclamosCerradosAbiertos');
$routes->get('api/analisis/tasa-cierre', 'Api\\Analisis::getTasaCierre');

// Endpoint Notas (supervisor — módulo aislado)
$routes->resource('api/notas', ['controller' => 'Api\\Notas', 'only' => ['index', 'create', 'update', 'delete']]);

// Autenticación y configuración de páginas
$routes->get('(:any)', 'Pages::view/$1');
$routes->get('unauthorized', 'Errors::unauthorized');
$routes->post('auth/login', 'Auth::login');
$routes->post('auth/loginLegajo', 'Auth::loginLegajo');
$routes->post('auth/logout', 'Auth::logout');
$routes->get('auth/update-passwords', 'Auth::updatePasswords');

// FIN ENDPOINTS -----------------------------------------------------


if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}