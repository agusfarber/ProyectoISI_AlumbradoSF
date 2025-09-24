<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UsuarioModel;

class Auth extends Controller
{
    protected $session;  
  
    function __construct()
    {
      $this->session = \Config\Services::session();
      $this->session->start();
    }
  
    public function login()
    {
        $userModel = new UsuarioModel();
        $usuario = $this->request->getVar();

        if (!$usuario->email || !$usuario->contrasena) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => 'Por favor, complete todos los campos.'
            ]);
        }

        $user = $userModel->validateLoginByEmail($usuario->email, $usuario->contrasena);
            
        if ($user) {
           $this->session->set([
                'user_id' => $user['id'],
                'user_name' => $user['nombre'],
                'role' => $user['idRol'], // Guardamos el rol del usuario
                'logged_in' => true
            ]);
                   
          return $this->response->setStatusCode(200)->setJSON([
              'message' => 'Inicio de sesión exitoso.',
              'role' => $user['idRol']
          ]);
          
        } else {
          return $this->response->setStatusCode(401)->setJSON([
              'error' => 'Credenciales incorrectas. Por favor, verifique su correo y contraseña.'
          ]);
        }
    }//login

    public function loginLegajo()
    {
        $userModel = new UsuarioModel();
        $usuario = $this->request->getVar();

        if (!$usuario->legajo || !$usuario->contrasena) {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => 'Por favor, complete todos los campos.'
            ]);
        }

        $user = $userModel->validateLoginByLegajo($usuario->legajo, $usuario->contrasena);
            
        if ($user) {
           $this->session->set([
                'user_id' => $user['id'],
                'user_name' => $user['nombre'],
                'role' => $user['idRol'], // Guardamos el rol del usuario
                'logged_in' => true
            ]);
                   
          return $this->response->setStatusCode(200)->setJSON([
              'message' => 'Inicio de sesión exitoso.',
              'role' => $user['idRol']
          ]);
          
        } else {
          return $this->response->setStatusCode(401)->setJSON([
              'error' => 'Credenciales incorrectas. Por favor, verifique su legajo y contraseña.'
          ]);
        }
    }//loginLegajo

  /**
   * Finaliza la sesión del usuario actual.
   *
   * @return \CodeIgniter\HTTP\RedirectResponse Redirección a la página de inicio de sesión.
   */
  public function logout()
  {
    $this->session->destroy();
    return redirect()->to(base_url('/'));
  }
}


