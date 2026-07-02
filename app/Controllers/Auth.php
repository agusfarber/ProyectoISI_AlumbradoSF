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
        $usuario = (array) $this->request->getVar();

        $credencial = trim((string)($usuario['credencial'] ?? ''));
        $contrasena = (string)($usuario['contrasena'] ?? '');

        if ($credencial === '' || $contrasena === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => 'Por favor, complete todos los campos.'
            ]);
        }

        // Login unificado: primero intenta por email; si no corresponde o falla, intenta por legajo.
        $user = null;
        if (filter_var($credencial, FILTER_VALIDATE_EMAIL)) {
            $user = $userModel->validateLoginByEmail($credencial, $contrasena);
        }

        if (!$user) {
            $user = $userModel->validateLoginByLegajo($credencial, $contrasena);
        }
            
        if ($user) {
           $this->session->set([
                'user_id' => $user['id'],
                'user_name' => $user['nombre'],
                'role' => $user['idRol'], // Guardamos el rol del usuario
                'foto_perfil' => $user['foto_perfil'] ?? null,
                'logged_in' => true
            ]);
                   
          return $this->response->setStatusCode(200)->setJSON([
              'message' => 'Inicio de sesión exitoso.',
              'role' => $user['idRol']
          ]);
          
        } else {
          return $this->response->setStatusCode(401)->setJSON([
              'error' => 'Credenciales incorrectas. Verifique su usuario y contraseña.'
          ]);
        }
    }//login

    public function loginLegajo()
    {
        // Compatibilidad temporal para clientes viejos que aún envían legajo.
        $userModel = new UsuarioModel();
        $usuario = (array) $this->request->getVar();
        $legajo = trim((string)($usuario['legajo'] ?? ''));
        $contrasena = (string)($usuario['contrasena'] ?? '');

        if ($legajo === '' || $contrasena === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'error' => 'Por favor, complete todos los campos.'
            ]);
        }

        $user = $userModel->validateLoginByLegajo($legajo, $contrasena);

        if ($user) {
            $this->session->set([
                'user_id' => $user['id'],
                'user_name' => $user['nombre'],
                'role' => $user['idRol'],
                'foto_perfil' => $user['foto_perfil'] ?? null,
                'logged_in' => true
            ]);

            return $this->response->setStatusCode(200)->setJSON([
                'message' => 'Inicio de sesión exitoso.',
                'role' => $user['idRol']
            ]);
        }

        return $this->response->setStatusCode(401)->setJSON([
            'error' => 'Credenciales incorrectas. Verifique su usuario y contraseña.'
        ]);
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


