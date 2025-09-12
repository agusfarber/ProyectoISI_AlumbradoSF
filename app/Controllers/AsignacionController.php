<?php

namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use App\Models\UsuarioModel;

class AsignacionController extends ResourceController
{
    public function asignarCuadrilla()
    {
        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        $operarios = $data['operarios'] ?? [];
        $cuadrillaId = $data['cuadrillaId'] ?? null;

        if (!$cuadrillaId || empty($operarios)) {
            return $this->fail('Datos inválidos');
        }

        $usuarioModel = new UsuarioModel();

        foreach ($operarios as $idOperario) {
            $usuarioModel->update($idOperario, ['idCuadrilla' => $cuadrillaId]);
        }

        return $this->respond(['status' => 'success', 'message' => 'Asignación realizada']);
    }
}