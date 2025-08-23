<?php

namespace App\Models;
use CodeIgniter\Model;

class MaterialModel extends Model
{
    protected $table = 'material';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    // Se agrega 'idTipo' a los campos permitidos
    protected $allowedFields = ['nombre','idTipo','cantidad'];

    /**
     * Obtiene todos los materiales y el nombre de su tipo asociado.
     * Es crucial para mostrar la tabla con el nombre del tipo en lugar del ID.
     */
    public function findAllWithTipo()
    {
        return $this->select('material.*, tipo_material.nombre as tipo_nombre')
                    ->join('tipo_material', 'tipo_material.id = material.idTipo', 'left')
                    ->findAll();
    }
}