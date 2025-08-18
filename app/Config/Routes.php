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
$routes->resource('api/reclamos');
// Endpoint Materiales
$routes->post('api/materiales/import', 'Api\\Materiales::import');
$routes->resource('api/materiales');
// Endpoint Token103
$routes->resource('api/token103');
$routes->post('api/token103/generar-externo', 'Api\\Token103::generarTokenExterno');




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



