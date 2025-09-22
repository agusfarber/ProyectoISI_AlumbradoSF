<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class Pages extends BaseController
{
    private $noMenuPages = array("login");
    protected $session;  
  
    function __construct()
    {
      $this->session = \Config\Services::session();
      $this->session->start();
    }
   
    public function view(string $page = 'dashboard')
    {
        // Definir qué roles pueden acceder a qué páginas
        $rolePermissions = [
            'usuarios' => ['1', '2', '3'],
            'pages' => ['1'],
            'cuadrillas' => ['2'],
            'reclamos' => ['1', '2', '3'],
            'materiales' => ['1', '2'],
            'token103' => ['1', '2', '3'],
            'mapa' => ['1', '2', '3'],
            'mapa_google' => ['1', '2', '3'],
        ];
      
        // Obtener el rol del usuario
        $userRole = $this->session->get('role');
        $username = $this->session->get('user_name');
      
        if(!$userRole && !$username){
            $page = 'login';
        }
      
        $data = array("required"=>!in_array($page,$this->noMenuPages));
      
        // Verificar si la página tiene restricciones y si el usuario tiene permiso
        if (isset($rolePermissions[$page]) && !in_array($userRole, $rolePermissions[$page]) && $userRole) {
            return redirect()->to('/unauthorized')->with('error', 'No tienes permisos para acceder a esta página.');
        }
      

        // Verifica si existe la página
        if (!is_file(APPPATH . 'Views/pages/' . $page . '.php')) {
            // Whoops, no tenemos una página para eso!
            throw new PageNotFoundException($page);
        }

        $data['title'] = ucfirst($page); // Capitaliza la primera letra
        $headerData['title'] = ucfirst($page);

        if (is_file(FCPATH . '/static/css/' . $page . '.css')) {
            $headerData['cssPageFile'] = '/static/css/' . $page . '.css';
        }

        $footerData['title'] = ucfirst($page);
        if (is_file(FCPATH . '/static/js/' . $page . '.js')) {
            $footerData['jsPageFile'] = '/static/js/' . $page . '.js';
        }

        // Pasar datos a la vista del menú
        $data['userRole'] = $userRole;  // Pasamos el rol para que se utilice en el menú
        $data['username'] = $username;
        return view('templates/header', $headerData)
            . view('templates/menu', $data)  // Aquí le pasamos los datos con el rol
            . view('pages/' . $page, $data)
            . view('templates/footer', $footerData);
    }
}
