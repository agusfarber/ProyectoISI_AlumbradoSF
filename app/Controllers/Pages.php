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
        // 1 = Admin (solo usuarios), 2 = Supervisor (operación), 3 = Operario (obra)
        $rolePermissions = [
            'usuarios' => ['1'],
            'cuadrillas' => ['2'],
            'reclamos' => ['2'],
            'materiales' => ['2'],
            'mapa_google' => ['2'],
            'mapa_mapbox' => ['2'],
            'rutas' => ['2'],
            'ruta_google' => ['2'],
            'token103' => ['2'],
            'tareas' => ['3'],
            'cierre_reclamos' => ['2'],
            'analisis' => ['2'],
            'notas' => ['2'],
            'perfil' => ['1', '2', '3'],
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
        $data['userFoto'] = $this->session->get('foto_perfil');

        // Para la página de perfil, cargar los datos completos del usuario logueado
        if ($page === 'perfil') {
            $userId = $this->session->get('user_id');
            if ($userId) {
                $usuarioModel = new \App\Models\UsuarioModel();
                $perfil = $usuarioModel->find($userId);
                if ($perfil) {
                    unset($perfil['contrasena']);
                    $data['perfil'] = $perfil;
                }
            }
        }

        return view('templates/header', $headerData)
            . view('templates/menu', $data)  // Aquí le pasamos los datos con el rol
            . view('pages/' . $page, $data)
            . view('templates/footer', $footerData);
    }
}
