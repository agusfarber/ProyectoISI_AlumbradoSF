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
$routes->resource('api/usuarios');
// Endpoint Roles
$routes->resource('api/roles');
// Endpoint Reclamos (sistema interno)
$routes->get('api/reclamos/(:num)/historial', 'Api\\Reclamos::historial/$1');
$routes->get('api/reclamos/materiales/por-tipo', 'Api\\Reclamos::getMaterialesPorTipo');
$routes->post('api/reclamos/(:num)/materiales', 'Api\\Reclamos::guardarMaterialReclamo/$1');
$routes->get('api/reclamos/(:num)/materiales', 'Api\\Reclamos::getMaterialesReclamo/$1');
$routes->get('api/reclamos/materiales/(:num)/detalle', 'Api\\Reclamos::getDetalleMaterialReclamo/$1');
$routes->get('api/reclamos/(:num)/tiempo-reparacion', 'Api\\Reclamos::getTiempoReparacion/$1');
$routes->post('api/reclamos/(:num)/tiempo-reparacion', 'Api\\Reclamos::guardarTiempoReparacionEndpoint/$1');
$routes->get('api/reclamos/tiempos-promedio', 'Api\\Reclamos::getTiemposPromedio');
$routes->resource('api/reclamos');
// Endpoints para Tipos de Materiales
$routes->get('api/materiales/tipos', 'Api\\Materiales::getTipos');
$routes->post('api/materiales/tipos', 'Api\\Materiales::createTipo');
$routes->delete('api/materiales/tipos/(:num)', 'Api\\Materiales::deleteTipo/$1');
// Endpoint Materiales
$routes->post('api/materiales/import', 'Api\\Materiales::import');
$routes->get('api/materiales/verificar', 'Api\\Materiales::verificarExistencia');
$routes->resource('api/materiales');
// Endpoint Token103
$routes->resource('api/token103');
$routes->post('api/token103/generar-externo', 'Api\\Token103::generarTokenExterno');
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
$routes->get('api/rutas/(:num)/reclamos', 'Api\\Rutas::getReclamosRuta/$1');
$routes->post('api/rutas/asignar', 'Api\\Rutas::asignarACuadrilla');
$routes->post('api/rutas/desasignar/(:num)', 'Api\\Rutas::desasignarDeCuadrilla/$1');
$routes->get('api/rutas/operario/mis-rutas', 'Api\\Rutas::getRutasPorOperario');
$routes->get('api/rutas/operario/mis-reclamos', 'Api\\Rutas::getReclamosPorOperario');
$routes->get('api/rutas/operario/reclamos-recibidos', 'Api\\Rutas::getReclamosRecibidos');
$routes->post('api/rutas/operario/add-reclamo', 'Api\\Rutas::añadirReclamoARuta');
$routes->resource('api/rutas');
// Endpoint Cierre de Reclamos
$routes->get('api/cierre-reclamos/completados', 'Api\\CierreReclamos::obtenerReclamosCompletados');
$routes->get('api/cierre-reclamos/cerrados', 'Api\\CierreReclamos::obtenerReclamosCerrados');
$routes->post('api/cierre-reclamos/cerrar', 'Api\\CierreReclamos::cerrarReclamos');


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